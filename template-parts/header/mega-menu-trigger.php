<?php
/**
 * Header > Mega-menu trigger (stub)
 * F8 cablea la apertura/cierre del panel de mega-menú completo.
 *
 * @package Starter_Theme
 */
?>
<button
	type="button"
	class="udp-megamenu-trigger"
	data-udp-megamenu-toggle
	aria-expanded="false"
	aria-controls="udp-megamenu-panel"
>
	<span class="udp-megamenu-trigger__icon" aria-hidden="true">
		<span></span>
		<span></span>
		<span></span>
	</span>
	<span class="udp-megamenu-trigger__label"><?php esc_html_e( 'Menú', 'starter-theme' ); ?></span>
</button>
