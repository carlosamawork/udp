<?php
/**
 * Layout — Premio / Laureado
 *
 * Bloque light: retrato a la izquierda + nombre + premio/año + botones
 * (LinkedIn / web) + biografía. Un bloque por laureado (cada uno es un anchor).
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$data   = $args['data']   ?? array();
$anchor = $args['anchor'] ?? null;

$nombre   = $data['nombre'] ?? '';
$premio   = $data['premio'] ?? '';
$bio      = $data['bio']    ?? '';
$img      = is_array( $data['imagen'] ?? null ) ? $data['imagen'] : array();
$linkedin = trim( (string) ( $data['linkedin'] ?? '' ) );
$web      = trim( (string) ( $data['web'] ?? '' ) );

$id = $anchor['id'] ?? '';

if ( ! $nombre && ! $bio ) {
	return;
}
?>
<section
	<?php if ( $id ) : ?>id="<?php echo esc_attr( $id ); ?>"<?php endif; ?>
	class="udp-inst-section udp-inst-premio"
	style="scroll-margin-top: var(--udp-anchor-offset, 168px);"
>
	<div class="udp-inst-premio__inner">
		<div class="udp-inst-premio__media<?php echo empty( $img['url'] ) ? ' udp-inst-premio__media--placeholder' : ''; ?>">
			<?php if ( ! empty( $img['url'] ) ) : ?>
				<img src="<?php echo esc_url( $img['url'] ); ?>" alt="<?php echo esc_attr( $img['alt'] ?: $nombre ); ?>" loading="lazy">
			<?php endif; ?>
		</div>

		<div class="udp-inst-premio__content">
			<?php if ( $nombre ) : ?>
				<h2 class="udp-inst-premio__name"><?php echo esc_html( $nombre ); ?></h2>
			<?php endif; ?>

			<?php if ( $premio || $linkedin || $web ) : ?>
				<div class="udp-inst-premio__head">
					<?php if ( $premio ) : ?>
						<p class="udp-inst-premio__award"><?php echo esc_html( $premio ); ?></p>
					<?php endif; ?>

					<?php if ( $linkedin || $web ) : ?>
						<div class="udp-inst-premio__links">
							<?php if ( $linkedin ) : ?>
								<a class="udp-inst-premio__link" href="<?php echo esc_url( $linkedin ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( sprintf( __( 'LinkedIn de %s', 'starter-theme' ), $nombre ) ); ?>">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.94v5.67H9.35V9h3.41v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.46v6.28zM5.34 7.43a2.06 2.06 0 1 1 0-4.13 2.06 2.06 0 0 1 0 4.13zM7.12 20.45H3.56V9h3.56v11.45zM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.22 0z"/></svg>
								</a>
							<?php endif; ?>
							<?php if ( $web ) : ?>
								<a class="udp-inst-premio__link" href="<?php echo esc_url( $web ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( sprintf( __( 'Sitio web de %s', 'starter-theme' ), $nombre ) ); ?>">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9.5" stroke="currentColor" stroke-width="1.5"/><path d="M2.5 12h19M12 2.5c2.5 2.5 3.8 6 3.8 9.5S14.5 19 12 21.5C9.5 19 8.2 15.5 8.2 12S9.5 5 12 2.5z" stroke="currentColor" stroke-width="1.5"/></svg>
								</a>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( $bio ) : ?>
				<div class="udp-inst-premio__bio"><?php echo wp_kses_post( $bio ); ?></div>
			<?php endif; ?>
		</div>
	</div>
</section>
