<?php

/*
 * Template Name: Buscador de Actividades
 * Description: 
 */
get_header();

$audiencias = $_POST['datos']['audiencias']['results'];
$disciplinas = $_POST['datos']['disciplinas']['results'];
$tipo_actividad = $_POST['datos']['tipo_actividad']['results'];
$lugares = $_POST['datos']['lugares']['results'];
$actividades = $_POST['datos']['actividades']['results'];

global $post;
$color_buscador = get_post_meta($post->ID, 'color-buscador', true );
$logo_buscador = get_post_meta($post->ID, 'logo-buscador', true );
$audiencia_buscador = get_post_meta($post->ID, 'audiencia-buscador', true );
$logo_buscador = $logo_buscador ? $logo_buscador : $_POST['URL_PLUGIN']."/images/logo-horizontal-blanco.png";
?>

<?php if (!is_null($color_buscador)) : ?>
<style>
#bmc .c-sidebar__header,
#bmc .c-sidebar__barra-superior {
    background-color: <?php echo $color_buscador; ?>;
}

#bmc .o-actividad__titulo,
#bmc .c-atras,
#bmc .c-atras:hover {
	color: <?php echo $color_buscador; ?>;
}
</style>
<?php endif; ?>
<div id="main-content" class="main-content">
	<div id="primary" class="content-area">
		<div id="content" class="site-content" role="main">
			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<?php
					while ( have_posts() ) : the_post();
						global $more;
						$more = 1;
						the_content();
					endwhile;
				?>
				<div id="bmc" class="c-buscador">
					<div class="c-buscador__cuerpo">
						<div class="c-buscador__contenido">
							<ul class="c-actividades">
							<?php
							foreach($actividades as $key => $a) {
								$lugar = isset($a['lugar']['nombre']) ? $a['lugar']['nombre'] : '';
								?>
								<li data-id="<?= $a['id'] ?>" class="o-actividad" data-lugar="<?= $a['lugar']['id'] ?>" data-fecha="<?= $a['rango_fecha'] ?>" data-disciplina="<?= $a['disciplinas_ids'] ?>" data-audiencia="<?= $a['audiencias_ids'] ?>" data-tipo="<?= $a['tipos_ids'] ?>" >
								  <div class="o-actividad__informacion">
									<div class="o-actividad__contenedor-link">
										<div class="o-actividad__contenedor-imagen"><img class="o-actividad__imagen" src="<?= $a['imagen_final'] ?>" /></div>
										<div class="o-actividad__contenedor-datos">
											<div class="o-actividad__contenedor-fecha">
											<?php
												$fecha_separada = explode(' / ',$a['fecha_actividad']);
												$calendario1 = '';
												$calendario2 = '';
												if (count($fecha_separada) == 2) {
													$fechas1 = explode(" ",$fecha_separada[0]);
													$fechas2 = explode(" ",$fecha_separada[1]);
													$calendario1 = '<div class="e-fecha"><div class="mes">' . $fechas1[0] . '</div><div class="dia">' . $fechas1[1] . '</div></div>';
													$calendario2 = '<div class="e-fecha"><div class="mes">' . $fechas2[0] . '</div><div class="dia">' . $fechas2[1] . '</div></div>';
												} else {
													$fechas1 = explode(" ",$fecha_separada[0]);
													$calendario1 = '<div class="e-fecha"><div class="mes">' . $fechas1[0] . '</div><div class="dia">' . $fechas1[1] . '</div></div>';
												}
												
											?>
												<span class="o-actividad__fecha-actividad"><?= $calendario1.$calendario2 ?></span>
											</div>
											<div class="o-actividad__contenedor-descripcion">
												<h3 title="<?= $a['titulo'] ?>" class="o-actividad__titulo"><?= $a['nombre_corto'] ?></h3>
												<p class="o-actividad__lugar"><?= $lugar ?></p>
											</div>
										</div>
									</div>
									<div class="o-actividad__contenedor-botones">
										<?php $inicia = substr($a['inicia'], 0, -6); $termina = $a['termina'] ? substr($a['termina'], 0, -6) : $a['inicia'];?>
										<a href="http://www.google.com/calendar/event?action=TEMPLATE&text=<?= str_replace('"','&quot;',$a['titulo']) ?>&dates=<?=str_replace([':','-'], "",$inicia)?>/<?=str_replace([':','-'], "",$termina)?>&details=<?= strip_tags($a['descripcion']) ?>&location=<?= $lugar ?>&trp=false&sprop=&sprop=name:" target="_blank" rel="nofollow" class="o-actividad__boton-calendario"><span class="icono icono-calendario"></span> <span>Agendar</span></a>
									</div>
								  </div>
								</li>
							<?php } ?>
							</ul>
						</div>
						<img class="c-loading" src="<?=$_POST['URL_PLUGIN']."/images/loading.gif"?>" alt="Cargando..." />
						<div class="c-actividades--particular">
							<div data-id="" class="o-actividad o-actividad--particular">
								<div class="o-actividad--particular__contenedor-imagen"><img class="o-actividad--particular__imagen" alt="Evento" src="<?=$_POST['URL_PLUGIN']."/images/evento-predeterminado.png"?>"></div>
								<div class="o-actividad--particular__informacion">
									<h3 title="" class="o-actividad__titulo"></h3>
									<a class="c-atras" href="#">Atrás</a>
									<p class="o-actividad__evento"></p>
									<p class="c-tipos"></p>
									<div class="o-actividad__contenedor">
										<div class="o-actividad__icono"><span class="icono icono-pin"></span></div>
										<div>
											<p class="o-actividad__lugar"></p>
										</div>
									</div>
									<div class="o-actividad__contenedor">
										<div class="o-actividad__icono"><span class="icono icono-reloj"></span></div>
										<div>
											<p class="o-actividad__fecha-inicia"></p>
											<p class="o-actividad__fecha-termina"></p>
										</div>
									</div>
									<div class="o-actividad__contenedor --precios" style="display:none">
										<div class="o-actividad__icono"><span class="icono icono-precio"></span></div>
										<div class="o-actividad--particular__precios">
										</div>
									</div>
									<div class="c-social">
										<a href="#" target="_blank" rel="nofollow" class="o-actividad__boton-calendario"><span class="icono icono-calendario"></span> <span>Agendar</span></a>
										<button class="c-social__boton c-social__boton--link"></button>
										<button class="c-social__boton c-social__boton--twitter">Twitter</button>
										<button class="c-social__boton c-social__boton--facebook">Facebook</button>
										<input class="c-social__link" type="text">
									</div>
									<p class="o-actividad__descripcion"></p>
								</div>
							</div>
						</div>
						<div class="c-mensaje"><p></p><a class="c-atras" href="#">Atrás</a></div>
					</div>
					<a id="volver-ver-todo" class="c-atras c-ver-todo" href="#">Volver</a>
					
					<div class="l-contenedor-sidebar">
					<!-- Barra lateral -->
						<aside class="c-sidebar" role="navigation">
						<div class="c-sidebar__header">
							<button class="c-sidebar__toggle">
								<span class="c-cruz c-cruz--fino"></span>
							</button>
							<img class="c-sidebar__imagen" src="<?=$logo_buscador?>">
						</div>
						<div class="c-sidebar__nav">
							<button class="c-boton_fecha c-boton_fecha--semana" data-filtro="fecha" data-id="semana">Semana</button>
							<button class="c-boton_fecha c-boton_fecha--mes" data-filtro="fecha" data-id="mes">Mes</button>
							<button class="c-boton_fecha c-boton_fecha--ninguno">Todo</button>
						</div>
						<ul class="c-sidebar__nav">
							<?php if(!$audiencia_buscador || $audiencia_buscador < 1) { ?>
							<li class="c-dropdown">
								<a href="#" class="c-dropdown__link" data-toggle="dropdown">
									Público
									<b class="c-dropdown__caret"></b>
								</a>
								<ul class="c-dropdown__menu">
									<?php foreach($audiencias as $key => $a) { ?>
									<li class="c-dropdown__item" data-filtro="audiencia" data-id="<?= $a['id'] ?>">
										<a class="c-dropdown__link" href="#" tabindex="-1">
											<?= $a['nombre'] ?>
										</a>
									</li>
									<?php } ?>
								</ul>
							</li>
							<?php } ?>
							<li class="c-dropdown">
								<a href="#" class="c-dropdown__link" data-toggle="dropdown">
									Tipo de Actividad
									<b class="c-dropdown__caret"></b>
								</a>
								<ul class="c-dropdown__menu">
									<?php foreach($tipo_actividad as $key => $ta) { ?>
									<li class="c-dropdown__item" data-filtro="tipo" data-id="<?= $ta['id'] ?>">
										<a class="c-dropdown__link" href="#" tabindex="-1">
											<?= $ta['nombre'] ?>
										</a>
									</li>
									<?php } ?>
								</ul>
							</li>
							<li class="c-dropdown">
								<a href="#" class="c-dropdown__link" data-toggle="dropdown">
									Disciplinas
									<b class="c-dropdown__caret"></b>
								</a>
								<ul class="c-dropdown__menu">
									<?php foreach($disciplinas as $key => $d) { ?>
									<li class="c-dropdown__item" data-filtro="disciplina" data-id="<?= $d['id'] ?>">
										<a class="c-dropdown__link" href="#" tabindex="-1">
											<?= $d['nombre'] ?>
										</a>
									</li>
									<?php } ?>
								</ul>
							</li>
							<li class="c-dropdown">
								<a href="#" class="c-dropdown__link" data-toggle="dropdown">
									Lugares
									<b class="c-dropdown__caret"></b>
								</a>
								<ul class="c-dropdown__menu">
									<?php foreach($lugares as $key => $l) { ?>
									<li class="c-dropdown__item" data-filtro="lugar" data-id="<?= $l['id'] ?>">
										<a class="c-dropdown__link" href="#" tabindex="-1">
											<?= $l['nombre'] ?>
										</a>
									</li>
									<?php } ?>
								</ul>
							</li>
						</aside>
						<span style="margin: 9px;font-size: 10px;">MENU</span>
						<button class="c-hamburger c-hamburger--3dx" tabindex="0" type="button">
							<span class="c-hamburger__contenido">
								<span class="c-hamburger__interno"></span>
							</span>
						</button>
					</div>
				</div>
				<?php
					$errores = '';
					foreach ($_POST['datos'] as $key => $d) {
						$errores .= isset($d['error']) ? '<li class="c-errores__error">' . $d['error'] .'</li>' : '';
					}
					if (strlen($errores) > 0) {
						?>
						<ul class="c-errores">
							<?= $errores ?>
						</ul>
						<?php
					}
					
					wp_link_pages( array(
					'before'      => '<div class="page-links"><span class="page-links-title">' . __( 'Pages:', 'twentysixteen' ) . '</span>',
					'after'       => '</div>',
					'link_before' => '<span>',
					'link_after'  => '</span>',
					'pagelink'    => '<span class="screen-reader-text">' . __( 'Page', 'twentysixteen' ) . ' </span>%',
					'separator'   => '<span class="screen-reader-text">, </span>',
				) );
			?>
			</article>
		</div><!-- #content -->
	</div><!-- #primary -->
</div><!-- #main-content -->

<?php
get_sidebar();
get_footer();

if (isset($_GET['ac']) && $_GET['ac'] > 0) {
	echo '<script>buscarActividad.actividad='.$_GET['ac'].'</script>';
}