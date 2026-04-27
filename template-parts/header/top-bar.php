<?php
/**
 * Header > Top bar (única fila)
 * Trigger mega-menú izq + logo centro + buscador der.
 *
 * @package Starter_Theme
 */
?>
<div class="udp-top-bar">
	<div class="udp-top-bar__inner">

		<button
			type="button"
			class="udp-top-bar__menu"
			data-udp-megamenu-toggle
			aria-expanded="false"
			aria-controls="udp-megamenu-panel"
		>
			<span class="udp-top-bar__menu-circle" aria-hidden="true">
				<svg width="26" height="26" viewBox="0 0 26 26" fill="none">
					<line x1="5" y1="9" x2="21" y2="9" stroke="currentColor" stroke-width="1.5"/>
					<line x1="5" y1="13" x2="21" y2="13" stroke="currentColor" stroke-width="1.5"/>
					<line x1="5" y1="17" x2="21" y2="17" stroke="currentColor" stroke-width="1.5"/>
				</svg>
			</span>
			<span class="udp-top-bar__menu-label"><?php esc_html_e( 'Menú', 'starter-theme' ); ?></span>
		</button>

		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="udp-top-bar__logo" aria-label="<?php bloginfo( 'name' ); ?>">
			<?php
			$logo = udp_get_logo_url( 'blanco' );
			if ( ! empty( $logo ) ) :
				?>
				<img src="<?php echo esc_url( $logo ); ?>" alt="<?php bloginfo( 'name' ); ?>" />
			<?php else : ?>
				<span class="udp-top-bar__logo-text"><?php bloginfo( 'name' ); ?></span>
			<?php endif; ?>
		</a>

		<button type="button" class="udp-top-bar__search" aria-label="<?php esc_attr_e( 'Buscar', 'starter-theme' ); ?>">
			<span class="udp-top-bar__search-label"><?php esc_html_e( 'Buscador', 'starter-theme' ); ?></span>
			<span class="udp-top-bar__search-circle" aria-hidden="true">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none">
					<circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.5"/>
					<line x1="16" y1="16" x2="20" y2="20" stroke="currentColor" stroke-width="1.5"/>
				</svg>
			</span>
		</button>

	</div>
</div>
