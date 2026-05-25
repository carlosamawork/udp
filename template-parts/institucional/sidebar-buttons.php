<?php
/**
 * Botones (legacy `botones_con_links_externos`) renderizados en el aside derecho
 * de una sección de texto, como pills verticales. Usado por rich_text_sidebar
 * y text_accordion.
 *
 * @param array $args ['buttons' => array de {label, url}]
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$buttons = is_array( $args['buttons'] ?? null ) ? $args['buttons'] : array();
if ( empty( $buttons ) ) {
	return;
}
?>
<div class="udp-inst-sidebar-btns">
	<?php foreach ( $buttons as $b ) :
		$label = $b['label'] ?? '';
		$url   = $b['url']   ?? '';
		if ( ! $label || ! $url ) {
			continue;
		}
	?>
		<a class="udp-inst-sidebar-btn" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">
			<span class="udp-inst-sidebar-btn__label"><?php echo esc_html( $label ); ?></span>
			<svg class="udp-inst-sidebar-btn__icon" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
				<path d="M5 3h8v8M13 3 4 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
			</svg>
		</a>
	<?php endforeach; ?>
</div>
