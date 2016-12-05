<?php
/*
Plugin Name: Buscador de actividad p&uacute;blica de la Municipalidad de C&oacute;rdoba
Plugin URI: https://github.com/ModernizacionMuniCBA/plugin-wordpress-actividad-publica
Description: Este plugin genera una plantilla para incluir en una p&aacute;gina un buscador de actividades p&uacute;blicas de la Municipalidad de C&oacute;rdoba.
Version: 1.0.0
Author: Florencia Peretti
Author URI: https://github.com/florenperetti/
*/

add_action( 'plugins_loaded', array( 'ActividadesMuniCordoba', 'get_instancia' ) );

class ActividadesMuniCordoba {
	public static $instancia = null;
	
	private static $MESES = array("Ene", "Abr", "Ago", "Dic");
	private static $MONTHS = array("Jan", "Apr", "Aug", "Dec");
	
	protected $plantillas;
	
	public $nonce = ''; // Hash para llamadas via ajax
	
	public static function get_instancia() {
		if ( null == self::$instancia ) {
			self::$instancia = new ActividadesMuniCordoba();
		} 
		return self::$instancia;
	}
	
	private function __construct() {
		$this->plantillas = array();
		// Agrega un filtro al metabox de atributos para inyectar la plantilla en el cache.
			if ( version_compare( floatval( get_bloginfo( 'version' ) ), '4.7', '<' ) ) { // 4.6 y anteriores
					add_filter(
						'page_attributes_dropdown_pages_args',
						array( $this, 'registrar_plantillas' )
					);
			} else { // Version 4.7
					add_filter(
						'theme_page_templates', array( $this, 'agregar_plantilla_nueva' )
					);
			}
		// Agregar un filtro al save post para inyectar plantilla al cache de la página
		add_filter(
			'wp_insert_post_data', 
			array( $this, 'registrar_plantillas' ) 
		);
		// Agrega un filtro al template include para determinar si la página tiene la plantilla 
		// asignada y devolver su ruta
		add_filter(
			'template_include', 
			array( $this, 'ver_plantillas') 
		);
		// Agrega plantillas al arreglo
		$this->plantillas = array(
			'buscador-actividades-template.php' => 'Buscador de Actividades',
		);
		
		add_action( 'wp_ajax_buscar_actividad', array( $this, 'buscar_actividad' ) ); 
		add_action( 'wp_ajax_nopriv_buscar_actividad', array( $this, 'buscar_actividad' ) );
		add_action( 'wp_enqueue_scripts', array($this, 'cargar_assets') );
			
		add_action( 'template_redirect', array($this, 'buscador_template_redirect') );
	}
	
	public function agregar_plantilla_nueva( $posts_plantillas ) {
		$posts_plantillas = array_merge( $posts_plantillas, $this->plantillas );
		return $posts_plantillas;
	}
	
	public function registrar_plantillas( $atts ) {
		// Crea la key usada para el cacheo de temas
		$cache_key = 'page_templates-' . md5( get_theme_root() . '/' . get_stylesheet() );
		// Recuperar la lista de caché. 
		// Si no existe o está vacío, prepara un arreglo
		$plantillas = wp_get_theme()->get_page_templates();
		empty( $plantillas ) && $plantillas = array();
		// Nuevo caché, por lo que se borra el anterior
		wp_cache_delete( $cache_key , 'themes');
		// Agrega las plantillas nuevas a la lista fusionándolas
		// con el arreglo de plantillas del caché.
		$plantillas = array_merge( $plantillas, $this->plantillas );
		// Se agrega el caché modificado para permitir que WordPress lo tome para
		// listar las plantillas disponibles
		wp_cache_add( $cache_key, $plantillas, 'themes', 1800 );
		return $atts;
	}

	/**
	 * Se fija si la plantilla está asignada a la página
	 */
	public function ver_plantillas( $plantilla ) {
		global $post;
		
		if ( ! $post ) {
			return $plantilla;
		}
		
		if ( !isset( $this->plantillas[get_post_meta( 
			$post->ID, '_wp_page_template', true 
		)] ) ) {
			return $plantilla;
		} 
		$archivo = plugin_dir_path(__FILE__). get_post_meta( 
			$post->ID, '_wp_page_template', true
		);
		
		if ( file_exists( $archivo ) ) {
			return $archivo;
		} else {
			echo $archivo;
		}
		
		return $plantilla;
	}	
	
	public function obtener_info() {
		$datos = [];
	
		$api_response = wp_remote_get( 'https://gobiernoabierto.cordoba.gob.ar/api/disciplina-actividad/' );
		$datos['disciplinas'] = $this->chequear_respuesta($api_response, 'las disciplinas');
		
		$api_response = wp_remote_get( 'https://gobiernoabierto.cordoba.gob.ar/api/tipo-actividad/' );
		$datos['tipo_actividad'] = $this->chequear_respuesta($api_response, 'los tipos de actividades');
		
		$api_response = wp_remote_get( 'https://gobiernoabierto.cordoba.gob.ar/api/agrupador-actividad/' );
		$datos['eventos'] = $this->chequear_respuesta($api_response, 'los eventos');
		
		$api_response = wp_remote_get( 'https://gobiernoabierto.cordoba.gob.ar/api/lugar-actividad/?audiencia_id=4' );
		$datos['lugares'] = $this->chequear_respuesta($api_response, 'lugares');
		
		$api_response = wp_remote_get( 'https://gobiernoabierto.cordoba.gob.ar/api/actividad-publica/' );
		$datos['actividades'] = $this->chequear_respuesta($api_response, 'las actividades');
		
		foreach($datos['actividades']['results'] as $key => $ac) {
			$nombre = $ac['titulo'];
			if (strlen($nombre)>25) {
				$nombre = $this->quitar_palabras($ac['titulo'],5);
			} else {
				$nombre = $this->quitar_palabras($ac['titulo'],8);
			}
			$datos['actividades']['results'][$key]['nombre_corto'] = $nombre;
			
			$datos['actividades']['results'][$key]['imagen_final'] = $ac['imagen'] ? $ac['imagen'] : plugins_url( '/images/evento-predeterminado.png', __FILE__ );
			
			$datos['actividades']['results'][$key]['descripcion'] = $this->quitar_palabras($this->url_a_link($ac['descripcion']), 20);
			
			$datos['actividades']['results'][$key]['fecha_inicio'] = $ac['inicia'] ? $this->formatear_fecha_tres_caracteres($ac['inicia']) : '';
			
			// Cadena con los ids de los tipos de la actividad.
			$ids = "";
			foreach ($ac['tipos'] as $keyTipo => $tipo) {
				$ids .= $tipo["id"]."|";
			}
			$datos['actividades']['results'][$key]['tipos_ids'] = $ids;
			
			// Cadena con los ids de los tipos de la actividad.
			$ids = "";
			foreach ($ac['disciplinas'] as $keyDisc => $disc) {
				$ids .= $disc["id"]."|";
			}
			$datos['actividades']['results'][$key]['disciplinas_ids'] = $ids;
		}
		
		return $datos;
	}
	
	private function chequear_respuesta($api_response, $tipoObjeto) {
		if (is_null($api_response)) {
			return [ 'results' => [] ];
		} else if (is_wp_error($api_response)) {
			return [ 'results' => [], 'error' => 'Ocurrió un error al cargar '.$tipoObjeto.'.' ];
		} else {
			return json_decode( wp_remote_retrieve_body( $api_response ), true );
		}
	}
	
	private function quitar_palabras($texto, $palabras_devueltas)
	{
		$resultado = $texto;
		$texto = preg_replace('/(?<=\S,)(?=\S)/', ' ', $texto);
		$texto = str_replace("\n", " ", $texto);
		$arreglo = explode(" ", $texto);
		if (count($arreglo) <= $palabras_devueltas) {
			$resultado = $texto;
		} else {
			array_splice($arreglo, $palabras_devueltas);
			$resultado = implode(" ", $arreglo) . "...";
		}
		return $resultado;
	}
	
	public function buscador_template_redirect()
	{
		if (is_page_template( 'buscador-actividades-template.php' )) {
			$_POST["datos"] = $this->obtener_info();
			$_POST['URL_PLUGIN'] = plugins_url( '' , __FILE__);
		}
	}
	
	public function cargar_assets() {
		wp_enqueue_style( 'buscador', plugins_url( '/css/buscador.css', __FILE__ ) );
		
		wp_enqueue_script( 
			'buscar_actividades_ajax', 
			plugins_url( '/js/buscador.js',__FILE__ ), 
			array('jquery'), 
			'1.0.0',
			TRUE 
		);
		
		$nonce = wp_create_nonce( "buscar_actividad_nonce" );
			
		wp_localize_script( 
			'buscar_actividades_ajax', 
			'buscarActividad', 
			array(
				'url'   => admin_url( 'admin-ajax.php' ),
				'nonce' => $nonce,
			)
		);
	}
	
	public function buscar_actividad() {
		
		$id = $_REQUEST['id'];
		
		check_ajax_referer( 'buscar_actividad_nonce', 'nonce' );
		
		if( true && $id > 0) {
			$api_response = wp_remote_get( 'https://gobiernoabierto.cordoba.gob.ar/api/actividad-publica/'.$id );
			$api_data = json_decode( wp_remote_retrieve_body( $api_response ), true );
			
			$api_data = $this->mejorar_contenido_actividad($api_data);
			
			wp_send_json_success( $api_data );
		} else {
			wp_send_json_error( array( 'error' => $custom_error ) );
		}
		
		die();
	}
	
	private function mejorar_contenido_actividad($actividad) {
		
		$actividad['descripcion'] = $this->url_a_link($actividad['descripcion']);
		$actividad['fecha_actividad'] = $actividad['inicia'] ? $this->formatear_fecha_tres_caracteres($actividad['inicia']) : '';
		$actividad['fecha_inicia'] = $actividad['inicia'] ? $this->formatear_fecha_inicio_fin($actividad['inicia']) : '';
		$actividad['fecha_termina'] = $actividad['termina'] ? $this->formatear_fecha_inicio_fin($actividad['termina']) : '';
		
		return $actividad;
	}
	
	private function url_a_link($texto) {
		$reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
		return preg_match($reg_exUrl, $texto, $url) ? preg_replace($reg_exUrl, "<a target='_blank' href='" . $url[0] . "'>" . (strlen($url[0]) > 30 ? substr($url[0], 0, 29).'...' : $url[0]) . "</a> ", $texto) : $texto;
	}
	
	private function formatear_fecha_tres_caracteres($timestamp) {
		$fecha = date_format(date_create($timestamp),"M j");
		$fecha = $this->traducir_meses($fecha); // Ene 1
		return $fecha;
	}
	
	private function formatear_fecha_inicio_fin($timestamp) {
		$fecha = date_format(date_create($timestamp),'j \d\e M\, g:i A');
		$fecha = $this->traducir_meses($fecha); // 1 de Ene, 7:00 PM
		return $fecha;
	}
	
	private function traducir_meses($texto) {
		return str_ireplace(self::$MONTHS, self::$MESES, $texto);
	}
}