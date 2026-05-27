<?php
/**
 * Layout — Video (legacy `video` oembed).
 *
 * Reutiliza las clases del bloque F7 `.udp-block-embed` (SCSS ya existente).
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$embed = $args['data']['embed'] ?? '';
if ( empty( $embed ) ) {
	return;
}
?>
<section class="udp-block-embed udp-block-embed--light udp-block-embed--ratio-16-9">
	<div class="udp-block-embed__inner">
		<div class="udp-block-embed__media">
			<?php echo $embed; // phpcs:ignore WordPress.Security.EscapeOutput -- oembed HTML de proveedor confiable. ?>
		</div>
	</div>
</section>
