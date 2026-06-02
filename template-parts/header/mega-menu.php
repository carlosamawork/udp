<?php
/**
 * Header > Mega-menú panel
 *
 * 3 columnas:
 *  - Col 1: secciones principales (menu_principal repeater, botones no clickables)
 *  - Col 2: apartados de la sección activa (links directos o triggers de col-3)
 *  - Col 3: sub-items del apartado en hover (sub_items nested repeater, vacío por defecto)
 *
 * Footer: quick links + redes sociales.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$menu_items  = function_exists( 'starter_get_option' ) ? starter_get_option( 'menu_principal' ) : [];
$quick_links = function_exists( 'starter_get_option' ) ? starter_get_option( 'mega_menu_quick_links' ) : [];
$socials     = function_exists( 'udp_get_social_urls' ) ? udp_get_social_urls() : [];

if ( ! is_array( $menu_items ) )  $menu_items  = [];
if ( ! is_array( $quick_links ) ) $quick_links = [];

/**
 * Detect if URL is external (different host from WP home).
 * Internal localhost URLs and same-domain URLs return false.
 */
function udp_megamenu_is_external( string $url ): bool {
	if ( empty( $url ) ) return false;
	$home_host = parse_url( home_url(), PHP_URL_HOST );
	$url_host  = parse_url( $url, PHP_URL_HOST );
	return $url_host && $url_host !== $home_host;
}

// Flecha gorda → para apartados con sub-items (col-2), color #B0B0B0
$svg_arrow_right = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M2 8h12M9 3l5 5-5 5" stroke="#B0B0B0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
// Flecha ↗ externa (Figma arrow-up-right, 20×20, color fijo #B0B0B0)
$svg_ext         = '<svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M14.1663 14.1673V5.83398H5.83301M14.1663 5.83398L5.83301 14.1673" stroke="#B0B0B0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
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
			$logo = function_exists( 'udp_get_logo_url' ) ? udp_get_logo_url( 'udp' ) : '';
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

			<!-- COL 1: Secciones principales (botones, no links) -->
			<ul class="udp-megamenu__primary" role="menu">
				<?php foreach ( $menu_items as $idx => $item ) :
					$titulo = $item['titulo_main_link'] ?? '';
					if ( ! $titulo ) continue;
					$is_active = false; // nada activo al abrir — JS lo gestiona con hover
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

			<!-- COL 2 + COL 3: Un detail panel por sección -->
			<?php foreach ( $menu_items as $idx => $item ) :
				$titulo  = $item['titulo_main_link'] ?? '';
				$submenu = is_array( $item['submenu'] ?? null ) ? $item['submenu'] : [];
				if ( ! $titulo ) continue;
				$is_active = false;
			?>
				<div
					id="udp-megamenu-panel-<?php echo (int) $idx; ?>"
					class="udp-megamenu__detail<?php echo $is_active ? ' udp-megamenu__detail--active' : ''; ?>"
					data-udp-megamenu-detail="<?php echo esc_attr( $idx ); ?>"
					<?php echo $is_active ? '' : 'hidden'; ?>
				>

					<!-- COL 2: Apartados -->
					<ul class="udp-megamenu__submenu">
						<?php foreach ( $submenu as $sub_idx => $sub ) :
							$sub_titulo = $sub['titulo']                     ?? '';
							$sub_link   = $sub['url'] ?? $sub['link']        ?? '';
							$sub_new    = ! empty( $sub['new_tab_check'] );
							$sub_items  = is_array( $sub['sub_items'] ?? null ) ? $sub['sub_items'] : [];
							$has_sub    = ! empty( $sub_items );

							if ( ! $sub_titulo ) continue;

							$is_ext = $sub_new || ( $sub_link ? udp_megamenu_is_external( $sub_link ) : false );
							// Con sub-items → flecha gorda →. Externo sin sub → ↗. Interno sin sub → sin icono.
						$svg    = $has_sub ? $svg_arrow_right : ( $is_ext ? $svg_ext : '' );
						?>
							<li
								class="udp-megamenu__submenu-item"
								data-udp-sub-idx="<?php echo esc_attr( $sub_idx ); ?>"
							>
								<?php if ( $sub_link ) : ?>
									<a
										class="udp-megamenu__submenu-link<?php echo $has_sub ? ' udp-megamenu__submenu-link--has-sub' : ''; ?>"
										href="<?php echo esc_url( $sub_link ); ?>"
										<?php if ( $is_ext ) : ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>
									>
										<?php echo esc_html( $sub_titulo ); ?>
										<?php echo $svg; // phpcs:ignore ?>
									</a>
								<?php else : ?>
									<span class="udp-megamenu__submenu-link udp-megamenu__submenu-link--no-url">
										<?php echo esc_html( $sub_titulo ); ?>
										<?php echo $svg; // phpcs:ignore ?>
									</span>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>

					<!-- COL 3: Sub-panels (uno por apartado con sub-items, ocultos por defecto) -->
					<div class="udp-megamenu__col3">
						<?php foreach ( $submenu as $sub_idx => $sub ) :
							$sub_items = is_array( $sub['sub_items'] ?? null ) ? $sub['sub_items'] : [];
							if ( empty( $sub_items ) ) continue;
						?>
							<ul
								class="udp-megamenu__sub-panel"
								data-udp-sub-panel="<?php echo esc_attr( $sub_idx ); ?>"
								hidden
							>
								<?php foreach ( $sub_items as $si ) :
									$si_tipo = $si['tipo'] ?? 'externo';
									if ( $si_tipo === 'interno' && ! empty( $si['pagina'] ) ) {
										$si_titulo  = $si['titulo_alt'] ?: get_the_title( $si['pagina'] );
										$si_anchor  = ltrim( $si['anchor'] ?? '', '#' );
										$si_link    = get_permalink( $si['pagina'] ) . ( $si_anchor ? '#' . $si_anchor : '' );
										$si_nueva   = false;
									} else {
										$si_titulo = $si['titulo']             ?? '';
										$si_link   = $si['url'] ?? $si['link'] ?? '';
										$si_nueva  = ! empty( $si['nueva_pestana'] );
									}
									if ( ! $si_titulo || ! $si_link ) continue;
								?>
									<li class="udp-megamenu__sub-item">
										<a
											class="udp-megamenu__sub-link"
											href="<?php echo esc_url( $si_link ); ?>"
											<?php if ( $si_nueva ) : ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>
										>
											<?php echo esc_html( $si_titulo ); ?>
											<?php echo $si_nueva ? $svg_ext : ''; // phpcs:ignore ?>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endforeach; ?>
					</div>

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
						<?php echo esc_html( $ql_titulo ); ?>
						<?php if ( $ql_new ) : echo $svg_ext; endif; // phpcs:ignore ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>

		<?php if ( ! empty( $socials ) ) : ?>
			<ul class="udp-megamenu__socials">
				<?php foreach ( [ 'linkedin', 'instagram', 'youtube' ] as $key ) :
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
