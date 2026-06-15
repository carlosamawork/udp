<?php
/**
 * Institucional > Header (hero morado + breadcrumb + H1)
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$show_breadcrumb = isset( $args['show_breadcrumb'] ) ? (bool) $args['show_breadcrumb'] : true;
$page_title      = $args['page_title'] ?? get_the_title();

// Back-link mobile (Figma 4041-41179): flecha "volver" + eyebrow con el nombre
// de la página padre. Si no hay padre, vuelve al inicio.
$parent_id    = (int) wp_get_post_parent_id( get_the_ID() );
$back_label   = $parent_id ? get_the_title( $parent_id ) : __( 'Inicio', 'starter-theme' );
$back_url     = $parent_id ? get_permalink( $parent_id ) : home_url( '/' );
?>
<section id="section-inicio" class="udp-inst-hero" style="scroll-margin-top: var(--udp-anchor-offset, 168px);">
	<div class="udp-inst-hero__inner">
		<?php if ( $show_breadcrumb ) : ?>
			<div class="udp-inst-hero__breadcrumb">
				<?php get_template_part( 'template-parts/sections/breadcrumb' ); ?>
			</div>
		<?php endif; ?>

		<a class="udp-inst-hero__back" href="<?php echo esc_url( $back_url ); ?>">
			<svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
				<path d="M9 3L5 7l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
			</svg>
			<span><?php echo esc_html( $back_label ); ?></span>
		</a>

		<h1 class="udp-inst-hero__title"><?php echo esc_html( $page_title ); ?></h1>
	</div>
</section>
