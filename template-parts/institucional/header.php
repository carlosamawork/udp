<?php
/**
 * Institucional > Header (hero morado + breadcrumb + H1)
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$show_breadcrumb = isset( $args['show_breadcrumb'] ) ? (bool) $args['show_breadcrumb'] : true;
$page_title      = $args['page_title'] ?? get_the_title();
?>
<section id="section-inicio" class="udp-inst-hero" style="scroll-margin-top: var(--udp-anchor-offset, 168px);">
	<div class="udp-inst-hero__inner">
		<?php if ( $show_breadcrumb ) : ?>
			<div class="udp-inst-hero__breadcrumb">
				<?php get_template_part( 'template-parts/sections/breadcrumb' ); ?>
			</div>
		<?php endif; ?>

		<h1 class="udp-inst-hero__title"><?php echo esc_html( $page_title ); ?></h1>
	</div>
</section>
