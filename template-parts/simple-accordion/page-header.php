<?php
/**
 * Simple Accordion — cabecera de página
 *
 * Breadcrumb automático + título de la página.
 *
 * @package Starter_Theme
 */
?>
<header class="udp-simple-accordion__header">
	<div class="container">
		<?php
		get_template_part(
			'template-parts/sections/breadcrumb',
			null,
			array( 'home_label' => __( 'Inicio', 'starter-theme' ) )
		);
		?>
		<h1 class="udp-simple-accordion__title"><?php the_title(); ?></h1>
	</div>
</header>
