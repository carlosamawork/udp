<?php
/**
 * Mobile nav — barra fija inferior (solo mobile < md)
 * Trigger del mega-menú en mobile.
 *
 * @package Starter_Theme
 */
?>
<nav class="udp-mobile-nav" aria-label="<?php esc_attr_e( 'Menú principal', 'starter-theme' ); ?>">
	<button
		type="button"
		class="udp-mobile-nav__trigger"
		data-udp-megamenu-toggle
		aria-expanded="false"
		aria-controls="udp-megamenu-panel"
		aria-label="<?php esc_attr_e( 'Abrir menú principal', 'starter-theme' ); ?>"
	>
		<span class="udp-mobile-nav__circle" aria-hidden="true">
			<svg width="26" height="26" viewBox="0 0 26 26" fill="none">
				<line x1="5" y1="9" x2="21" y2="9" stroke="currentColor" stroke-width="1.5"/>
				<line x1="5" y1="13" x2="21" y2="13" stroke="currentColor" stroke-width="1.5"/>
				<line x1="5" y1="17" x2="21" y2="17" stroke="currentColor" stroke-width="1.5"/>
			</svg>
		</span>
		<span class="udp-mobile-nav__label"><?php esc_html_e( 'Menú', 'starter-theme' ); ?></span>
	</button>
	<button
		type="button"
		class="udp-mobile-nav__close"
		data-udp-megamenu-close
		aria-label="<?php esc_attr_e( 'Cerrar menú principal', 'starter-theme' ); ?>"
	>
		<span class="udp-mobile-nav__close-circle" aria-hidden="true">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none">
				<line x1="5" y1="5" x2="19" y2="19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
				<line x1="19" y1="5" x2="5" y2="19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
			</svg>
		</span>
		<span class="udp-mobile-nav__label"><?php esc_html_e( 'Cerrar', 'starter-theme' ); ?></span>
	</button>
</nav>
