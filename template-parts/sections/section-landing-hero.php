<?php
/**
 * Section Landing > Hero
 *
 * @package Starter_Theme
 *
 * @var array $args ['hero' => array, 'page_id' => int, 'page_title' => string]
 */
$hero        = isset( $args['hero'] ) && is_array( $args['hero'] ) ? $args['hero'] : array();
$eyebrow     = isset( $hero['eyebrow'] ) ? $hero['eyebrow'] : '';
$titulo      = isset( $hero['titulo'] ) && ! empty( $hero['titulo'] ) ? $hero['titulo'] : ( $args['page_title'] ?? '' );
$bajada      = isset( $hero['bajada'] ) ? $hero['bajada'] : '';
$imagen      = isset( $hero['imagen_fondo'] ) ? $hero['imagen_fondo'] : '';
$has_image   = ! empty( $imagen );
$style_attr  = $has_image ? ' style="background-image: url(' . esc_url( $imagen ) . ');"' : '';
$class_extra = $has_image ? ' udp-section-hero--has-image' : '';
?>
<section class="udp-section-hero<?php echo esc_attr( $class_extra ); ?>"<?php echo $style_attr; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="udp-section-hero__inner">
		<?php if ( ! empty( $eyebrow ) ) : ?>
			<p class="udp-section-hero__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
		<?php endif; ?>

		<?php if ( ! empty( $titulo ) ) : ?>
			<h1 class="udp-section-hero__title"><?php echo esc_html( $titulo ); ?></h1>
		<?php endif; ?>

		<?php if ( ! empty( $bajada ) ) : ?>
			<p class="udp-section-hero__bajada"><?php echo esc_html( $bajada ); ?></p>
		<?php endif; ?>
	</div>
</section>
