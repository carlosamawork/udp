<?php
/**
 * Header > Top bar
 * Logo + buscador + CTA accesibilidad + acceso usuario.
 *
 * @package Starter_Theme
 */
?>
<div class="udp-top-bar">
	<div class="udp-top-bar__inner">
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

		<div class="udp-top-bar__actions">
			<button type="button" class="btn-icon-circle" aria-label="<?php esc_attr_e( 'Buscar', 'starter-theme' ); ?>">
				<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
					<circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="1.5"/>
					<line x1="13.5" y1="13.5" x2="17" y2="17" stroke="currentColor" stroke-width="1.5"/>
				</svg>
			</button>

			<a href="#" class="btn btn-udp-primary btn-udp-md">
				<?php esc_html_e( 'Accesibilidad UDP', 'starter-theme' ); ?>
			</a>

			<a href="#" class="btn-icon-circle" aria-label="<?php esc_attr_e( 'Acceso usuario', 'starter-theme' ); ?>">
				<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
					<circle cx="10" cy="7" r="3" stroke="currentColor" stroke-width="1.5"/>
					<path d="M4 17c0-3 3-5 6-5s6 2 6 5" stroke="currentColor" stroke-width="1.5"/>
				</svg>
			</a>
		</div>
	</div>
</div>
