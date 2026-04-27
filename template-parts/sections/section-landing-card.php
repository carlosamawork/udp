<?php
/**
 * Section Landing > Card individual
 *
 * @package Starter_Theme
 *
 * @var array $args ['card' => array, 'index' => int]
 */
$card        = isset( $args['card'] ) && is_array( $args['card'] ) ? $args['card'] : array();
$eyebrow     = isset( $card['eyebrow'] ) ? $card['eyebrow'] : '';
$titulo      = isset( $card['titulo'] ) ? $card['titulo'] : '';
$descripcion = isset( $card['descripcion'] ) ? $card['descripcion'] : '';
$imagen      = isset( $card['imagen'] ) ? $card['imagen'] : '';
$link        = isset( $card['link'] ) && is_array( $card['link'] ) ? $card['link'] : array();

$href   = isset( $link['url'] ) ? $link['url'] : '';
$target = isset( $link['target'] ) ? $link['target'] : '';
$rel    = $target === '_blank' ? 'noopener noreferrer' : '';

if ( empty( $href ) || empty( $titulo ) ) {
	return;
}

$is_external = $target === '_blank';
?>
<a
	href="<?php echo esc_url( $href ); ?>"
	class="udp-section-card<?php echo $imagen ? ' udp-section-card--has-image' : ''; ?>"
	<?php if ( $target ) : ?>target="<?php echo esc_attr( $target ); ?>"<?php endif; ?>
	<?php if ( $rel ) : ?>rel="<?php echo esc_attr( $rel ); ?>"<?php endif; ?>
>
	<?php if ( $imagen ) : ?>
		<div class="udp-section-card__image" style="background-image: url(<?php echo esc_url( $imagen ); ?>);" aria-hidden="true"></div>
	<?php endif; ?>

	<div class="udp-section-card__content">
		<?php if ( ! empty( $eyebrow ) ) : ?>
			<p class="udp-section-card__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
		<?php endif; ?>

		<h3 class="udp-section-card__title"><?php echo esc_html( $titulo ); ?></h3>

		<?php if ( ! empty( $descripcion ) ) : ?>
			<p class="udp-section-card__desc"><?php echo esc_html( $descripcion ); ?></p>
		<?php endif; ?>
	</div>

	<span class="udp-section-card__cta" aria-hidden="true">
		<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
			<?php if ( $is_external ) : ?>
				<path d="M5 3h8v8M13 3L3 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
			<?php else : ?>
				<path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
			<?php endif; ?>
		</svg>
	</span>
</a>
