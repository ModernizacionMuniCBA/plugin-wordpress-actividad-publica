<?php
/*
Plugin Name: Buscador de actividad p&uacute;blica de la Municipalidad de C&oacute;rdoba
Plugin URI: https://github.com/ModernizacionMuniCBA/plugin-wordpress-actividad-publica
Description: Este plugin genera una plantilla para incluir en una p&aacute;gina un buscador de actividades p&uacute;blicas de la Municipalidad de C&oacute;rdoba.
Version: 1.3.0
Author: Florencia Peretti
Author URI: https://github.com/florenperetti/
*/

add_action('plugins_loaded', array('ActividadesMuniCordoba', 'get_instancia'));

class ActividadesMuniCordoba
{
	public static $instancia = null;

	private static $MESES = array("Ene", "Abr", "Ago", "Dic");
	private static $MONTHS = array("Jan", "Apr", "Aug", "Dec");

	private static $META_KEY_COLOR = 'color-buscador';
	private static $META_KEY_LOGO = 'logo-buscador';
	private static $META_KEY_AUDIENCIA = 'audiencia-buscador';

	private static $URL_API_GOB_AB = 'https://gobiernoabierto.cordoba.gob.ar/api';
	private static $ID_AUDIENCIA_CULTURA = 4;

	private static $IMAGEN_PREDETERMINADA = '/images/evento-predeterminado.png';

	protected $plantillas;

	public $nonce_busquedas = '';
	public $nonce_reiniciar_opciones = '';

	public static function get_instancia() {
		if (null == self::$instancia) {
			self::$instancia = new ActividadesMuniCordoba();
		} 
		return self::$instancia;
	}

	private function __construct()
	{
		$this->plantillas = array();
		// Agrega un filtro al metabox de atributos para inyectar la plantilla en el cache.
			if (version_compare(floatval(get_bloginfo('version')), '4.7', '<')) { // 4.6 y anteriores
				add_filter(
					'page_attributes_dropdown_pages_args',
					array($this, 'registrar_plantillas')
				);
			} else { // Version 4.7
					add_filter(
						'theme_page_templates', array($this, 'agregar_plantilla_nueva')
					);
			}
		// Agregar un filtro al save post para inyectar plantilla al cache de la p�gina
		add_filter(
			'wp_insert_post_data', 
			array($this, 'registrar_plantillas') 
		);
		// Agrega un filtro al template include para determinar si la p�gina tiene la plantilla 
		// asignada y devolver su ruta
		add_filter(
			'template_include', 
			array($this, 'ver_plantillas') 
		);
		// Agrega plantillas al arreglo
		$this->plantillas = array(
			'buscador-actividades-template.php' => 'Buscador de Actividades',
		);
		
		add_action('wp_ajax_buscar_actividad', array($this, 'buscar_actividad')); 
		add_action('wp_ajax_nopriv_buscar_actividad', array($this, 'buscar_actividad'));
		add_action('wp_ajax_reiniciar_opciones', array($this, 'reiniciar_opciones')); 
		add_action('wp_ajax_nopriv_reiniciar_opciones', array($this, 'reiniciar_opciones'));
		add_action('wp_enqueue_scripts', array($this, 'cargar_assets'));
		add_action('template_redirect', array($this, 'buscador_template_redirect'));
		add_action('add_meta_boxes_page', array($this, 'agregar_meta_box_buscador'));
		add_action('admin_enqueue_styles', array($this, 'cargar_estilos_admin'));
		add_action('admin_enqueue_scripts', array($this, 'cargar_scripts_admin'));
		add_action('save_post', array($this, 'save_custom_meta_data'));

		add_shortcode('lista_actividades_cba', array($this, 'render_shortcode_lista_actividades'));
		add_action('init', array($this, 'boton_shortcode_lista_actividades'));
	}

	public function render_shortcode_lista_actividades($atributos = [], $content = null, $tag = '')
	{
	    $atributos = array_change_key_case((array)$atributos, CASE_LOWER);
	    $atr = shortcode_atts([
            'disciplina' => 3,
            'cant' => 3,
            'titulo' => ''
        ], $atributos, $tag);

	    $url = self::$URL_API_GOB_AB.'/actividad-publica/?audiencia_id='.self::$ID_AUDIENCIA_CULTURA.'&disciplina_id='.$atr['disciplina'].'&page_size='.$atr['cant'];

    	$api_response = wp_remote_get($url);
    	$nombre_transient = 'actividades_disciplina_' . $atr['disciplina'];
		$resultado = $this->chequear_respuesta($api_response, 'las disciplinas', $nombre_transient);
		
		// TODO: template
		if ($atr['titulo']) {
			echo '<h2>'.$atr['titulo'].'</h2>';
		}

		foreach ($resultado['results'] as $key => $actividad) {
			echo $actividad['titulo'].'<br>';
		}
	}

	public function boton_shortcode_lista_actividades() {
		if (!current_user_can('edit_posts') && !current_user_can('edit_pages') && get_user_option('rich_editing') == 'true')
			return;

		add_filter("mce_external_plugins", array($this, "registrar_tinymce_plugin")); 
		add_filter('mce_buttons', array($this, 'agregar_boton_tinymce_shortcode_lista_actividades'));
	}

	public function registrar_tinymce_plugin($plugin_array) {
		$plugin_array['lactivcba_button'] = $this->cargar_url_asset('/js/shortcode.js');
	    return $plugin_array;
	}

	public function agregar_boton_tinymce_shortcode_lista_actividades($buttons) {
	    $buttons[] = "lactivcba_button";
	    return $buttons;
	}

	public function agregar_plantilla_nueva($posts_plantillas)
	{
		$posts_plantillas = array_merge($posts_plantillas, $this->plantillas);
		return $posts_plantillas;
	}

	public function registrar_plantillas($atts)
	{
		// Crea la key usada para el cacheo de temas
		$cache_key = 'page_templates-' . md5(get_theme_root() . '/' . get_stylesheet());
		// Recuperar la lista de cach�. 
		// Si no existe o est� vac�o, prepara un arreglo
		$plantillas = wp_get_theme()->get_page_templates();
		empty($plantillas) && $plantillas = array();
		// Nuevo cach�, por lo que se borra el anterior
		wp_cache_delete($cache_key , 'themes');
		// Agrega las plantillas nuevas a la lista fusion�ndolas
		// con el arreglo de plantillas del cach�.
		$plantillas = array_merge($plantillas, $this->plantillas);
		// Se agrega el cach� modificado para permitir que WordPress lo tome para
		// listar las plantillas disponibles
		wp_cache_add($cache_key, $plantillas, 'themes', 1800);
		return $atts;
	}

	/**
	 * Se fija si la plantilla est� asignada a la p�gina
	 */
	public function ver_plantillas($plantilla)
	{
		global $post;
		
		if (! $post) {
			return $plantilla;
		}
		
		if (!isset($this->plantillas[get_post_meta(
			$post->ID, '_wp_page_template', true 
		)])) {
			return $plantilla;
		} 
		$archivo = plugin_dir_path(__FILE__). get_post_meta(
			$post->ID, '_wp_page_template', true
		);
		
		if (file_exists($archivo)) {
			return $archivo;
		} else {
			echo $archivo;
		}
		
		return $plantilla;
	}

	public function buscador_template_redirect()
	{
		if (is_page_template('buscador-actividades-template.php')) {
			$_POST["datos"] = $this->obtener_datos_para_buscador();
			$_POST['URL_PLUGIN'] = plugins_url('' , __FILE__);
		}
	}

	public function cargar_assets()
	{
		$urlCSSBuscador = $this->cargar_url_asset('/css/buscador.css');
		$urlJSBuscador = $this->cargar_url_asset('/js/buscador.js');

		wp_register_style('buscador_actividades.css', $urlCSSBuscador);
		wp_register_script('buscador_actividades.js', $urlJSBuscador);

		if (is_page_template('buscador-actividades-template.php')) {

			wp_enqueue_style('buscador_actividades.css', $urlCSSBuscador);

			wp_enqueue_script(
				'buscar_actividades_ajax', 
				$urlJSBuscador, 
				array('jquery'), 
				'1.0.0',
				TRUE 
			);

			$nonce_busquedas = wp_create_nonce("buscar_actividad_nonce");

			global $post;
			$audiencia_buscador = get_post_meta($post->ID, 'audiencia-buscador', true);
			
			wp_localize_script(
				'buscar_actividades_ajax', 
				'buscarActividad', 
				array(
					'url'   => admin_url('admin-ajax.php'),
					'nonce' => $nonce_busquedas,
					'imagen' => plugins_url(self::$IMAGEN_PREDETERMINADA, __FILE__),
					'audiencia' => $audiencia_buscador
				)
			);
		}
	}

	function cargar_scripts_admin()
	{
		global $post;
		if ($post && get_post_meta($post->ID, '_wp_page_template', true) === 'buscador-actividades-template.php') {
			wp_enqueue_script('media-upload');
			wp_enqueue_script('thickbox');
			wp_register_script('subir-logo', plugins_url('/js/script_admin.js', __FILE__), array('jquery', 'media-upload', 'thickbox'));
			wp_enqueue_script('subir-logo');
			
			$nonce_reiniciar_opciones = wp_create_nonce("reiniciar_opciones_nonce");
			
			wp_localize_script(
				'subir-logo', 
				'reiniciarOpciones', 
				array(
					'url'   => admin_url('admin-ajax.php'),
					'nonce' => $nonce_reiniciar_opciones,
				)
			);
		}
	}

	function cargar_estilos_admin()
	{
		global $post;
		if (get_post_meta($post->ID, '_wp_page_template', true) === 'buscador-actividades-template.php') {
			wp_enqueue_style('thickbox');
		}
	}

	public function obtener_datos_para_buscador()
	{
		$datos = [];

		// Se buscan los datos en cache, sino se llama a la api

		$datos['audiencias'] = $this->buscar_transient('audiencias');

		$datos['disciplinas'] = $this->buscar_transient('disciplinas');

		$datos['tipo_actividad'] = $this->buscar_transient('tipo_actividad');

		$datos['eventos'] = $this->buscar_transient('eventos');

		$datos['lugares'] = $this->buscar_transient('lugares');

		$datos['actividades'] = $this->buscar_transient('actividades');

		$primerDiaSemana = date("Y-m-d", strtotime('monday last week'));
		$ultimoDiaSemana = date("Y-m-d", strtotime('monday this week'));
		$primerDiaMes = date("Y-m-d", strtotime('first day of this month'));
		$ultimoDiaMes = date("Y-m-d", strtotime('last day of this month'));

		foreach($datos['actividades']['results'] as $key => $ac) {

			$inicia = date("Y-m-d", strtotime($ac['inicia']));
			$termina = date("Y-m-d", strtotime($ac['termina']));
			if ((($primerDiaSemana >= $inicia) && ($primerDiaSemana <= $termina)) || (($ultimoDiaSemana >= $inicia) && ($ultimoDiaSemana <= $termina))) {
				// Semana en rango
				$datos['actividades']['results'][$key]['rango_fecha'] = 'semana|mes|';
			} else if ((($primerDiaMes >= $inicia) && ($primerDiaMes <= $termina)) || (($ultimoDiaMes >= $inicia) && ($ultimoDiaMes <= $termina))) {
				$datos['actividades']['results'][$key]['rango_fecha'] = 'mes|';
			} else {
				$datos['actividades']['results'][$key]['rango_fecha'] = 'ninguno|';
			}
			
			$nombre = $ac['titulo'];
			if (strlen($nombre)>25) {
				$nombre = $this->quitar_palabras($ac['titulo'],5);
			} else {
				$nombre = $this->quitar_palabras($ac['titulo'],8);
			}
			$datos['actividades']['results'][$key]['nombre_corto'] = $nombre;
			
			$datos['actividades']['results'][$key]['imagen_final'] = $ac['imagen'] ? $ac['imagen']['thumbnail'] : plugins_url(self::$IMAGEN_PREDETERMINADA, __FILE__);
			
			$datos['actividades']['results'][$key]['descripcion'] = $this->quitar_palabras($this->url_a_link($ac['descripcion']), 20);
			
			if ($ac['inicia']) {
				$iniciaFormat = $this->formatear_fecha_tres_caracteres($ac['inicia']);
				$terminaFormat = $this->formatear_fecha_tres_caracteres($ac['termina']);

				$datos['actividades']['results'][$key]['fecha_actividad'] = $iniciaFormat == $terminaFormat ? $iniciaFormat : $iniciaFormat . ' / ' . $terminaFormat;
			}
			// Cadena con los ids de los tipos de la actividad.
			$ids = "";
			foreach ($ac['tipos'] as $keyTipo => $tipo) {
				$ids .= $tipo["id"]."|";
			}
			$datos['actividades']['results'][$key]['tipos_ids'] = $ids;
			
			// Cadena con los ids de las audiencias.
			$ids = "";
			foreach ($ac['audiencias'] as $keyDisc => $disc) {
				$ids .= $disc["id"]."|";
			}
			$datos['actividades']['results'][$key]['audiencias_ids'] = $ids;

			// Cadena con los ids de los tipos de la actividad.
			$ids = "";
			foreach ($ac['disciplinas'] as $keyDisc => $disc) {
				$ids .= $disc["id"]."|";
			}
			$datos['actividades']['results'][$key]['disciplinas_ids'] = $ids;
		}
		
		return $datos;
	}

	/*
	* Mira si la respuesta es un error, si no lo es, cachea por una hora el resultado.
	*/
	private function chequear_respuesta($api_response, $tipoObjeto, $nombre_transient)
	{
		if (is_null($api_response)) {
			return [ 'results' => [] ];
		} else if (is_wp_error($api_response)) {
			$mensaje = WP_DEBUG ? ' '.$this->mostrar_error($api_response) : '';
			return [ 'results' => [], 'error' => 'Ocurri&oacute; un error al cargar '.$tipoObjeto.'.'.$mensaje];
		} else {
			$respuesta = json_decode(wp_remote_retrieve_body($api_response), true);
			set_site_transient('api_muni_cba_'.$nombre_transient, $respuesta, HOUR_IN_SECONDS);
			return $respuesta;
		}
	}

	private function buscar_transient($nombre_transient)
	{
		$transient = get_site_transient('api_muni_cba_'.$nombre_transient);
		global $post;
		$audiencia_buscador = get_post_meta($post->ID, self::$META_KEY_AUDIENCIA, true);
		$audiencia_buscador = $audiencia_buscador && $audiencia_buscador > 0 ? '?audiencia_id='.$audiencia_buscador : '';
		$api_response = null;
		$resultado = null;
		if(!empty($transient)) {
			return $transient;
		} else {
			switch($nombre_transient) {
				case 'audiencias': {
					$api_response = wp_remote_get(self::$URL_API_GOB_AB.'/audiencia-actividad/');
					$resultado = $this->chequear_respuesta($api_response, 'las audiencias', $nombre_transient);
				} break;
				case 'disciplinas': {
					$api_response = wp_remote_get(self::$URL_API_GOB_AB.'/disciplina-actividad/'.$audiencia_buscador);
					$resultado = $this->chequear_respuesta($api_response, 'las disciplinas', $nombre_transient);
				} break;
				case 'tipo_actividad': {
					$api_response = wp_remote_get(self::$URL_API_GOB_AB.'/tipo-actividad/'.$audiencia_buscador);
					$resultado = $this->chequear_respuesta($api_response, 'los tipos de actividad', $nombre_transient);
				} break;
				case 'eventos': {
					$api_response = wp_remote_get(self::$URL_API_GOB_AB.'/agrupador-actividad/'.$audiencia_buscador);
					$resultado = $this->chequear_respuesta($api_response, 'los eventos', $nombre_transient);
				} break;
				case 'lugares': {
					$api_response = wp_remote_get(self::$URL_API_GOB_AB.'/lugar-actividad/'.$audiencia_buscador);
					$resultado = $this->chequear_respuesta($api_response, 'los lugares', $nombre_transient);
				} break;
				case 'actividades': {
					$api_response = wp_remote_get(self::$URL_API_GOB_AB.'/actividad-publica/'.$audiencia_buscador);
					$resultado = $this->chequear_respuesta($api_response, 'las actividades', $nombre_transient);
				} break;
				case 'todas_disciplinas': {
					$api_response = wp_remote_get(self::$URL_API_GOB_AB.'/disciplina-actividad/?audiencia_id='.self::$ID_AUDIENCIA_CULTURA);
					$resultado = $this->chequear_respuesta($api_response, 'las disciplinas', $nombre_transient);
				} break;
			}
		}
		return $resultado;
	}

	public function buscar_actividad()
	{
		$id = $_REQUEST['id'];
		
		check_ajax_referer('buscar_actividad_nonce', 'nonce');
		
		if(true && $id > 0) {
			$api_response = wp_remote_get(self::$URL_API_GOB_AB.'/actividad-publica/'.$id);
			$api_data = json_decode(wp_remote_retrieve_body($api_response), true);
			
			$api_data = $this->mejorar_contenido_actividad($api_data);
			
			wp_send_json_success($api_data);
		} else {
			wp_send_json_error(array('error' => $custom_error));
		}
		
		die();
	}

	public function reiniciar_opciones()
	{
		$id = $_REQUEST['id'];
		
		check_ajax_referer('reiniciar_opciones_nonce', 'nonce');
		
		if (true) {
			delete_post_meta($id, self::$META_KEY_COLOR);
			delete_post_meta($id, self::$META_KEY_LOGO);
			delete_post_meta($id, self::$META_KEY_AUDIENCIA);
			echo admin_url('post.php?post=' . $id).'&action=edit';
		} else {
			echo 0;
		}
		
		wp_die();
	}

	private function mejorar_contenido_actividad($actividad)
	{
		$actividad['descripcion'] = $this->url_a_link($actividad['descripcion']);
		if ($actividad['inicia']) {
			$iniciaFormat = $this->formatear_fecha_tres_caracteres($actividad['inicia']);
			$terminaFormat = $this->formatear_fecha_tres_caracteres($actividad['termina']);

			$actividad['fecha_actividad'] = $iniciaFormat == $terminaFormat ? $iniciaFormat : $iniciaFormat . ' / ' . $terminaFormat;
		}
		$actividad['fecha_inicia'] = $actividad['inicia'] ? $this->formatear_fecha_inicio_fin($actividad['inicia']) : '';
		$actividad['fecha_termina'] = $actividad['termina'] ? $this->formatear_fecha_inicio_fin($actividad['termina']) : '';

		return $actividad;
	}

	public function agregar_meta_box_buscador()
	{
		global $post;
		if ('buscador-actividades-template.php' == get_post_meta($post->ID, '_wp_page_template', true)) {
			add_meta_box('meta_box_buscador', 'Ajustes del Buscador', array($this, 'generar_meta_box_estilos'), 'page', 'side', 'high');
		}
    }

	public function generar_meta_box_estilos()
	{
		global $post;
		wp_nonce_field(plugin_basename(__FILE__), 'opciones_buscador_nonce');
		$color_buscador = get_post_meta($post->ID, self::$META_KEY_COLOR, true);
		$logo_buscador = get_post_meta($post->ID, self::$META_KEY_LOGO, true);
		$audiencia_buscador = get_post_meta($post->ID, self::$META_KEY_AUDIENCIA, true);

		$api_response = wp_remote_get(self::$URL_API_GOB_AB.'/audiencia-actividad/');
		$resultadoAudiencia = $this->chequear_respuesta($api_response, 'las audiencias', 'audiencias');

		?>
		<style>
			#meta_box_buscador .inside{
				margin: 0;
				padding: 0;
			}
			.meta-box-buscador-muni #vista_previa {
				display:none;
				height:50px;
				margin-bottom: 5px;
			}
			.meta-box-buscador-muni #vista_previa img {
				height: 100% !important;
				width: auto !important;
			}
			.meta-box-buscador-muni .opciones {
				padding: 0 12px 12px;
				margin: 6px 0 0;
			}
			.meta-box-buscador-muni .reiniciar {
				padding: 10px;
				clear: both;
				border-top: 1px solid #ddd;
				background: #f5f5f5;
			}
			.meta-box-buscador-muni .reiniciar a {
				color: #a00;
			}
			.meta-box-buscador-muni .reiniciar a:hover {
				color: red;
			}
		</style>
		<div class="meta-box-buscador-muni">
			<div class="opciones">
				<p>Seleccione una audiencia:</p>
				<select name="audiencia_buscador">
					<option value="false">Todas</option>
					<?php foreach ($resultadoAudiencia['results'] as $key => $audiencia): ?>
						<option <?php echo $audiencia_buscador && $audiencia_buscador == $audiencia['id'] ? 'selected' : '';?> value="<?php echo $audiencia['id']; ?>"><?php echo $audiencia['nombre']; ?></option>
					<?php endforeach ?>
				</select>
				<p>Personalice los estilos del Buscador de Actividades:</p>
				<p class="post-attributes-label-wrapper">
					<label class="post-attributes-label" for="color_buscador">Color Predominante</label>
				</p>
				<input type="color" value="<?php echo $color_buscador ? $color_buscador : '#00a665'; ?>" name="color_buscador" />
				<p class="post-attributes-label-wrapper">
					<label class="post-attributes-label" for="upload_logo_buscador">Logo</label>
				</p>
				<p class="hide-if-no-js">
					<div id="vista_previa"<?php echo $logo_buscador ? ' style="display:block"':''; ?>>
						<?php echo $logo_buscador ? '<img src="'.$logo_buscador.'" />' : ''; ?>
					</div>
					<input id="upload_logo_buscador" type="text" size="36" name="upload_logo_buscador" value="<?php echo $logo_buscador ? $logo_buscador : ''; ?>" />
					<input class="button" id="upload_image_button" type="button" value="Subir logo" />
				</p>
				<p><i>Para guardar sus cambios, guarde la p&aacute;gina con el bot&oacute;n, como lo hace normalmente.</i></p>
				<p><a href="https://github.com/ModernizacionMuniCBA/plugin-wordpress-actividad-publica#uso" target="_blank">M&aacute;s ayuda</a></p>
			</div>
			<div class="reiniciar">
				<div id="reiniciar-buscador">
					<a href="#" data-id="<?=$post->ID?>">Reiniciar Estilos</a>
				</div>
			</div>
		</div>
		<?php
	}

	function save_custom_meta_data($id) {
		if (get_post_meta($id, '_wp_page_template', true) === 'buscador-actividades-template.php') {
			if(!wp_verify_nonce($_POST['opciones_buscador_nonce'], plugin_basename(__FILE__))) {
				return $id;
			}

			if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
				return $id;
			}

			if('page' == $_POST['post_type']) {
				if(!current_user_can('edit_page', $id)) {
					return $id;
				}
			} else {
				if(!current_user_can('edit_page', $id)) {
					return $id;
				}
			}

			update_post_meta($id, self::$META_KEY_COLOR, $_POST['color_buscador']);
			update_post_meta($id, self::$META_KEY_LOGO, $_POST['upload_logo_buscador']);
			update_post_meta($id, self::$META_KEY_AUDIENCIA, $_POST['audiencia_buscador']);
		}
	}

	/* Funciones de utilidad */

	private function mostrar_error($error)
	{
		if (WP_DEBUG === true) {
			return $error->get_error_message();
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

	private function url_a_link($texto)
	{
		$texto = strip_tags($texto, '<br><p>');
		$reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
		return preg_match($reg_exUrl, $texto, $url) ? preg_replace($reg_exUrl, "<a target='_blank' href='" . $url[0] . "'>" . (strlen($url[0]) > 30 ? substr($url[0], 0, 29).'...' : $url[0]) . "</a> ", $texto) : $texto;
	}

	private function formatear_fecha_tres_caracteres($timestamp)
	{
		$fecha = date_format(date_create($timestamp),"M j");
		$fecha = $this->traducir_meses($fecha); // Ene 1
		return $fecha;
	}

	private function formatear_fecha_inicio_fin($timestamp)
	{
		$fecha = date_format(date_create($timestamp),'j \d\e M\, g:i A');
		$fecha = $this->traducir_meses($fecha); // 1 de Ene, 7:00 PM
		return $fecha;
	}

	private function traducir_meses($texto)
	{
		return str_ireplace(self::$MONTHS, self::$MESES, $texto);
	}

	private function cargar_url_asset($ruta_archivo)
	{
		return plugins_url($this->minified($ruta_archivo), __FILE__);
	}

	// Se usan archivos minificados en producci�n.
	private function minified($ruta_archivo)
	{
		if (WP_DEBUG === true) {
			return $ruta_archivo;
		} else {
			$extension = strrchr($ruta_archivo, '.');
			return substr_replace($ruta_archivo, '.min'.$extension, strrpos($ruta_archivo, $extension), strlen($extension));
		}
	}
}