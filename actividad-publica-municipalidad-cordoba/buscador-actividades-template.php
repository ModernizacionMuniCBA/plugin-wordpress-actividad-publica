<?php

/*
 * Template Name: Buscador de Actividades
 * Description: 
 */
get_header();

$disciplinas = $_POST['datos']['disciplinas']['results'];
$tipo_actividad = $_POST['datos']['tipo_actividad']['results'];
$eventos = $_POST['datos']['eventos']['results'];
$lugares = $_POST['datos']['lugares']['results'];
$actividades = $_POST['datos']['actividades']['results'];

global $post;
$color_buscador = get_post_meta($post->ID, 'color-buscador', true );
$logo_buscador = get_post_meta($post->ID, 'logo-buscador', true );
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
							<?php foreach($actividades as $key => $a) { ?>
								<li data-id="<?= $a['id'] ?>" class="o-actividad" data-lugar="<?= $a['lugar']['id'] ?>" data-disciplina="<?= $a['disciplinas_ids'] ?>" data-tipo="<?= $a['tipos_ids'] ?>" data-evento="<?= $a['agrupador']['id'] ?>" >
									<div class="o-actividad__informacion">
										<h3 title="<?= $a['titulo'] ?>" class="o-actividad__titulo"><?= $a['nombre_corto'] ?></h3><span class="o-actividad__fecha-actividad"><?= $a['fecha_actividad'] ?></span>
										<p><?= $a['descripcion'] ?></p>
									</div>
									<div class="o-actividad__contenedor-imagen"><img class="o-actividad__imagen" src="<?= $a['imagen_final'] ?>" /></div>
								</li>
							<?php } ?>
							</ul>
						</div>
						<img class="c-loading" src="<?=$_POST['URL_PLUGIN']."/images/loading.gif"?>" alt="Cargando..." />
						<div class="c-actividades--particular">
							<div data-id="" class="o-actividad o-actividad--particular">
								<div class="o-actividad--particular__informacion">
									<h3 title="" class="o-actividad__titulo"></h3><span class="o-actividad__fecha-actividad"></span>
									<p class="o-actividad__evento"></p>
									<p class="c-tipos"></p>
									<p class="o-actividad__descripcion"></p>
									<p class="o-actividad__lugar"></p>
									<p class="o-actividad__fecha-inicia"></p>
									<p class="o-actividad__fecha-termina"></p>
								</div>
								<div class="c-social">
									<span>Compartir: </span> <button class="c-social__boton c-social__boton--twitter">Twitter</button> <button class="c-social__boton c-social__boton--facebook">Facebook</button>
								</div>
								<a class="c-atras" href="#">Atrás</a>
								<div class="o-actividad__contenedor-imagen"><img class="o-actividad__imagen" alt="Evento" src="<?=$_POST['URL_PLUGIN']."/images/evento-predeterminado.png"?>"></div>
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
						<ul class="c-sidebar__nav">
							<li class="c-dropdown">
								<a href="#" class="c-dropdown__link" data-toggle="dropdown">
									Categoría<!-- <span class="c-sidebar__badge"></span>-->
									<b class="c-dropdown__caret"></b>
								</a>
								<ul class="c-dropdown__menu">
									<?php foreach($eventos as $key => $e) { ?>
									<li class="c-dropdown__item" data-filtro="evento" data-id="<?= $e['id'] ?>">
										<a class="c-dropdown__link" href="#" tabindex="-1">
											<?= $e['nombre'] ?>
										</a>
									</li>
									<?php } ?>
								</ul>
							</li>
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