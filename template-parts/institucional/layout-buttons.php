<?php
/**
 * Layout — Botones grandes (legacy `botones_con_links_externos`).
 *
 * Reutiliza las clases del bloque F7 `.udp-block-big-buttons` (SCSS existente).
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$buttons = is_array( $args['data']['buttons'] ?? null ) ? $args['data']['buttons'] : array();
if ( empty( $buttons ) ) {
	return;
}
?>
<section class="udp-block-big-buttons udp-block-big-buttons--cols-3 udp-block-big-buttons--dark">
	<div class="udp-block-big-buttons__inner">
		<ul class="udp-block-big-buttons__list">
			<?php foreach ( $buttons as $btn ) :
				$label = $btn['label'] ?? '';
				$url   = $btn['url']   ?? '';
				if ( ! $label || ! $url ) {
					continue;
				}
			?>
				<li class="udp-block-big-buttons__item">
					<a class="udp-block-big-buttons__btn" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">
						<span class="udp-block-big-buttons__btn-content">
							<span class="udp-block-big-buttons__btn-label"><?php echo esc_html( $label ); ?></span>
						</span>
						<span class="udp-block-big-buttons__btn-icon" aria-hidden="true">
							<svg width="18" height="18" viewBox="0 0 18 18" fill="none">
								<path d="M6 4h8v8M14 4 4 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
