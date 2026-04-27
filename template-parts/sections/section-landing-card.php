<?php
/**
 * Section Landing > Card individual (variantes default | back)
 *
 * Sin imagen — fondo gris ($dark-2) que pasa a lila ($brand-blue) en hover.
 *
 * @package Starter_Theme
 *
 * @var array $args ['card' => array, 'index' => int]
 */
$card        = isset( $args['card'] ) && is_array( $args['card'] ) ? $args['card'] : array();
$variant     = isset( $card['variant'] ) && $card['variant'] === 'back' ? 'back' : 'default';
$eyebrow     = isset( $card['eyebrow'] ) ? $card['eyebrow'] : '';
$titulo      = isset( $card['titulo'] ) ? $card['titulo'] : '';
$descripcion = isset( $card['descripcion'] ) ? $card['descripcion'] : '';
$link        = isset( $card['link'] ) && is_array( $card['link'] ) ? $card['link'] : array();

$href   = isset( $link['url'] ) ? $link['url'] : '';
$target = isset( $link['target'] ) ? $link['target'] : '';
$rel    = $target === '_blank' ? 'noopener noreferrer' : '';

if ( empty( $href ) || empty( $titulo ) ) {
	return;
}

$class = 'udp-section-card udp-section-card--' . $variant;
?>
<a
	href="<?php echo esc_url( $href ); ?>"
	class="<?php echo esc_attr( $class ); ?>"
	<?php if ( $target ) : ?>target="<?php echo esc_attr( $target ); ?>"<?php endif; ?>
	<?php if ( $rel ) : ?>rel="<?php echo esc_attr( $rel ); ?>"<?php endif; ?>
>
	<span class="udp-section-card__cta" aria-hidden="true">
		<?php if ( $variant === 'back' ) : ?>
			<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
				<path d="M9 4 5 8l4 4M5 8h6.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
			</svg>
		<?php else : ?>
			<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
				<path d="M5 3h8v8M13 3 3 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
			</svg>
		<?php endif; ?>
	</span>

	<div class="udp-section-card__content">
		<?php if ( $variant !== 'back' && ! empty( $eyebrow ) ) : ?>
			<p class="udp-section-card__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
		<?php endif; ?>

		<h3 class="udp-section-card__title"><?php echo esc_html( $titulo ); ?></h3>

		<?php if ( $variant !== 'back' && ! empty( $descripcion ) ) : ?>
			<p class="udp-section-card__desc"><?php echo esc_html( $descripcion ); ?></p>
		<?php endif; ?>
	</div>
</a>
