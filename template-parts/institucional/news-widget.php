<?php
/**
 * Widget "Noticias" para el sidebar institucional.
 *
 * Muestra el post más reciente con imagen destacada (tarjeta beige: eyebrow +
 * imagen + título + fecha + chip de categoría). Toda la tarjeta es clickable.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$n = function_exists( 'udp_institucional_latest_noticia' ) ? udp_institucional_latest_noticia() : null;
if ( empty( $n ) || empty( $n['url'] ) ) {
	return;
}
?>
<a class="udp-inst-news" href="<?php echo esc_url( $n['url'] ); ?>">
	<span class="udp-inst-news__eyebrow">
		<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
			<path d="M2.5 11a2.5 2.5 0 012.5 2.5M2.5 7.5a6 6 0 016 6M2.5 4a9.5 9.5 0 019.5 9.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
		</svg>
		<?php esc_html_e( 'Noticias', 'starter-theme' ); ?>
	</span>

	<?php if ( ! empty( $n['image'] ) ) : ?>
		<span class="udp-inst-news__media">
			<img src="<?php echo esc_url( $n['image'] ); ?>" alt="" loading="lazy">
		</span>
	<?php endif; ?>

	<span class="udp-inst-news__body">
		<span class="udp-inst-news__title"><?php echo esc_html( $n['title'] ); ?></span>
		<?php if ( ! empty( $n['date'] ) ) : ?>
			<span class="udp-inst-news__date"><?php echo esc_html( $n['date'] ); ?></span>
		<?php endif; ?>
	</span>

	<?php if ( ! empty( $n['category'] ) ) : ?>
		<span class="udp-inst-news__cat"><?php echo esc_html( $n['category'] ); ?></span>
	<?php endif; ?>
</a>
