<?php
/**
 * Header > Mega-menú panel
 *
 * Panel light fixed full-viewport con 3 columnas:
 *  - Col 1: items principales (menu_principal repeater)
 *  - Col 2: submenu del item activo
 *  - Col 3: links_externos del item activo
 * Top bar interno: × Cerrar + logo. Footer: quick links + social.
 *
 * El primer item recibe `--active` por defecto (col 2 + 3 lo muestran).
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$menu_items   = function_exists( 'starter_get_option' ) ? starter_get_option( 'menu_principal' ) : array();
$quick_links  = function_exists( 'starter_get_option' ) ? starter_get_option( 'mega_menu_quick_links' ) : array();
$socials      = function_exists( 'udp_get_social_urls' ) ? udp_get_social_urls() : array();

if ( ! is_array( $menu_items ) ) $menu_items  = array();
if ( ! is_array( $quick_links ) ) $quick_links = array();
?>
<div
	id="udp-megamenu-panel"
	class="udp-megamenu"
	role="dialog"
	aria-modal="true"
	aria-labelledby="udp-megamenu-title"
	hidden
>
	<div class="udp-megamenu__top">
		<button type="button" class="udp-megamenu__close" data-udp-megamenu-close aria-label="<?php esc_attr_e( 'Cerrar menú', 'starter-theme' ); ?>">
			<span class="udp-megamenu__close-circle" aria-hidden="true">
				<svg width="14" height="14" viewBox="0 0 14 14" fill="none">
					<path d="M3 3l8 8M11 3l-8 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
				</svg>
			</span>
			<span class="udp-megamenu__close-label"><?php esc_html_e( 'Cerrar', 'starter-theme' ); ?></span>
		</button>

		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="udp-megamenu__logo" aria-label="<?php bloginfo( 'name' ); ?>">
			<?php
			$logo = function_exists( 'udp_get_logo_url' ) ? udp_get_logo_url( 'negro' ) : '';
			if ( ! empty( $logo ) ) :
				?>
				<img src="<?php echo esc_url( $logo ); ?>" alt="<?php bloginfo( 'name' ); ?>" />
			<?php else : ?>
				<span class="udp-megamenu__logo-text"><?php bloginfo( 'name' ); ?></span>
			<?php endif; ?>
		</a>
	</div>

	<h2 id="udp-megamenu-title" class="visually-hidden"><?php esc_html_e( 'Menú principal', 'starter-theme' ); ?></h2>

	<?php if ( empty( $menu_items ) ) : ?>
		<p class="udp-megamenu__empty"><?php esc_html_e( 'No hay items configurados en el menú principal.', 'starter-theme' ); ?></p>
	<?php else : ?>
		<div class="udp-megamenu__body">

			<ul class="udp-megamenu__primary" role="menu">
				<?php foreach ( $menu_items as $idx => $item ) :
					$titulo    = $item['titulo_main_link'] ?? '';
					$main_link = $item['main_link'] ?? '';
					$new_tab   = ! empty( $item['new_tab'] );
					if ( ! $titulo ) continue;
					$is_active = $idx === 0;  // first item active by default
				?>
					<li class="udp-megamenu__primary-item<?php echo $is_active ? ' udp-megamenu__primary-item--active' : ''; ?>" role="none">
						<button
							type="button"
							class="udp-megamenu__primary-btn"
							data-udp-megamenu-item="<?php echo esc_attr( $idx ); ?>"
							aria-expanded="<?php echo $is_active ? 'true' : 'false'; ?>"
							aria-controls="udp-megamenu-panel-<?php echo (int) $idx; ?>"
						>
							<?php echo esc_html( wp_strip_all_tags( $titulo ) ); ?>
						</button>
					</li>
				<?php endforeach; ?>
			</ul>

			<?php foreach ( $menu_items as $idx => $item ) :
				$titulo         = $item['titulo_main_link'] ?? '';
				$main_link      = $item['main_link'] ?? '';
				$new_tab_main   = ! empty( $item['new_tab'] );
				$submenu        = is_array( $item['submenu'] ?? null ) ? $item['submenu'] : array();
				$links_externos = is_array( $item['links_externos'] ?? null ) ? $item['links_externos'] : array();
				if ( ! $titulo ) continue;
				$is_active = $idx === 0;
			?>
				<div
					id="udp-megamenu-panel-<?php echo (int) $idx; ?>"
					class="udp-megamenu__detail<?php echo $is_active ? ' udp-megamenu__detail--active' : ''; ?>"
					data-udp-megamenu-detail="<?php echo esc_attr( $idx ); ?>"
					<?php echo $is_active ? '' : 'hidden'; ?>
				>
					<ul class="udp-megamenu__submenu">
						<?php if ( $main_link ) : ?>
							<li class="udp-megamenu__submenu-item udp-megamenu__submenu-item--main">
								<a class="udp-megamenu__submenu-link" href="<?php echo esc_url( $main_link ); ?>"
									<?php if ( $new_tab_main ) : ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>>
									<?php printf( esc_html__( 'Conoce %s', 'starter-theme' ), esc_html( wp_strip_all_tags( $titulo ) ) ); ?>
									<svg width="12" height="12" viewBox="0 0 12 12" fill="none">
										<path d="M3 3h6v6M9 3 3 9" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
									</svg>
								</a>
							</li>
						<?php endif; ?>
						<?php foreach ( $submenu as $sub ) :
							$sub_titulo = $sub['titulo'] ?? '';
							$sub_link   = $sub['link']   ?? '';
							$sub_new    = ! empty( $sub['new_tab_check'] );
							if ( ! $sub_titulo || ! $sub_link ) continue;
						?>
							<li class="udp-megamenu__submenu-item">
								<a class="udp-megamenu__submenu-link" href="<?php echo esc_url( $sub_link ); ?>"
									<?php if ( $sub_new ) : ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>>
									<?php echo esc_html( wp_strip_all_tags( $sub_titulo ) ); ?>
									<?php if ( $sub_new ) : ?>
										<svg width="12" height="12" viewBox="0 0 12 12" fill="none">
											<path d="M3 3h6v6M9 3 3 9" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
									<?php else : ?>
										<svg width="12" height="12" viewBox="0 0 12 12" fill="none">
											<path d="M4 3l3 3-3 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
									<?php endif; ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>

					<ul class="udp-megamenu__externos">
						<?php foreach ( $links_externos as $ext ) :
							$ext_titulo = $ext['titulo'] ?? '';
							$ext_link   = $ext['link']   ?? '';
							if ( ! $ext_titulo || ! $ext_link ) continue;
						?>
							<li class="udp-megamenu__externos-item">
								<a class="udp-megamenu__externos-link" href="<?php echo esc_url( $ext_link ); ?>" target="_blank" rel="noopener noreferrer">
									<?php echo esc_html( wp_strip_all_tags( $ext_titulo ) ); ?>
									<svg width="12" height="12" viewBox="0 0 12 12" fill="none">
										<path d="M3 3h6v6M9 3 3 9" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
									</svg>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>

		</div>
	<?php endif; ?>

	<footer class="udp-megamenu__footer">
		<ul class="udp-megamenu__quick-links">
			<?php foreach ( $quick_links as $ql ) :
				$ql_titulo = $ql['titulo'] ?? '';
				$ql_link   = $ql['link']   ?? '';
				$ql_new    = ! empty( $ql['new_tab'] );
				if ( ! $ql_titulo || ! $ql_link ) continue;
			?>
				<li class="udp-megamenu__quick-item">
					<a class="udp-megamenu__quick-link" href="<?php echo esc_url( $ql_link ); ?>"
						<?php if ( $ql_new ) : ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>>
						<?php echo esc_html( wp_strip_all_tags( $ql_titulo ) ); ?>
						<?php if ( $ql_new ) : ?>
							<svg width="12" height="12" viewBox="0 0 12 12" fill="none">
								<path d="M3 3h6v6M9 3 3 9" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						<?php endif; ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>

		<?php if ( ! empty( $socials ) ) : ?>
			<ul class="udp-megamenu__socials">
				<?php foreach ( array( 'linkedin', 'instagram', 'youtube' ) as $key ) :
					$url = $socials[ $key ] ?? '';
					if ( ! $url ) continue;
				?>
					<li class="udp-megamenu__social-item">
						<a class="udp-megamenu__social-link" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( ucfirst( $key ) ); ?>">
							<?php
							switch ( $key ) {
								case 'linkedin':
									echo '<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M3 5.5h2.5v8H3v-8zM4.25 1.8a1.45 1.45 0 1 1 0 2.9 1.45 1.45 0 0 1 0-2.9zM7 5.5h2.4v1.1h0a2.6 2.6 0 0 1 2.4-1.3c2.5 0 3 1.6 3 3.7V13.5h-2.5V9.4c0-1 0-2.3-1.4-2.3s-1.6 1.1-1.6 2.2V13.5H7V5.5z"/></svg>';
									break;
								case 'instagram':
									echo '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="2" width="12" height="12" rx="3"/><circle cx="8" cy="8" r="2.5"/><circle cx="11.5" cy="4.5" r="0.5" fill="currentColor"/></svg>';
									break;
								case 'youtube':
									echo '<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M14.7 4.5c-.2-.6-.7-1-1.3-1.2C12.2 3 8 3 8 3s-4.2 0-5.4.3c-.6.2-1.1.6-1.3 1.2C1 5.7 1 8 1 8s0 2.3.3 3.5c.2.6.7 1 1.3 1.2C3.8 13 8 13 8 13s4.2 0 5.4-.3c.6-.2 1.1-.6 1.3-1.2.3-1.2.3-3.5.3-3.5s0-2.3-.3-3.5zM6.5 10.2V5.8L10.4 8l-3.9 2.2z"/></svg>';
									break;
							}
							?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</footer>
</div>
