<?php
/**
 * Home — Sección 5: Próximos Eventos
 *
 * 2 cards destacadas (imagen izq + cuerpo oscuro der) + lista de hasta 5 eventos.
 * Diseño: Figma node 3706:20036.
 *
 * @package starter-bs5
 */

$hoy    = current_time( 'Ymd' );
$result = udp_query_agenda( [
	'fecha_desde' => $hoy,
	'order'       => 'ASC',
	'limit'       => 7,
] );

$cards = $result['cards'] ?? [];

if ( empty( $cards ) ) {
	return;
}

$titulo_seccion = get_field( 'eventos_titulo' ) ?: 'Próximos eventos';

$destacados = array_slice( $cards, 0, 2 );
$lista      = array_slice( $cards, 2, 5 );
$agenda_url = get_permalink( 91 ) ?: home_url( '/agenda-udp/' );

$arrow_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg>';
?>
<section class="udp-home-eventos">
	<div class="container">

		<header class="udp-home-eventos__header">
			<h2 class="udp-home-eventos__titulo udp-home__titulo"><?php echo esc_html( $titulo_seccion ); ?></h2>
			<a href="<?php echo esc_url( $agenda_url ); ?>" class="udp-home-eventos__ver-mas">
				Ver todos los eventos
			</a>
		</header>

		<?php if ( $destacados ) : ?>
		<div class="udp-home-eventos__destacados">
			<?php foreach ( $destacados as $card ) :
				$fecha_hora = $card['fecha_display'] ?? '';
				if ( ! empty( $card['hora_display'] ) ) {
					$fecha_hora .= ', ' . $card['hora_display'];
				}
			?>
			<a href="<?php echo esc_url( $card['href'] ); ?>" class="udp-home-eventos__card">
				<?php if ( empty( $card['imagen']['url'] ) ) : ?>
				<div class="udp-home-eventos__card-img">
					<figure class="udp-home-eventos__card-img-placeholder"></figure>
				</div>
				<?php else : ?>
				<div class="udp-home-eventos__card-img">
					<img
						src="<?php echo esc_url( $card['imagen']['url'] ); ?>"
						alt="<?php echo esc_attr( $card['imagen']['alt'] ?? '' ); ?>"
						loading="lazy"
						decoding="async"
					>
				</div>
				<?php endif; ?>
				<div class="udp-home-eventos__card-body">
					<div class="udp-home-eventos__card-title-wrap">
						<p class="udp-home-eventos__card-titulo"><?php echo esc_html( $card['titulo'] ); ?></p>
					</div>
					<div class="udp-home-eventos__card-footer">
						<div class="udp-home-eventos__card-meta">
							<?php if ( ! empty( $card['eyebrow'] ) ) : ?>
							<span class="udp-home-eventos__card-eyebrow"><?php echo esc_html( $card['eyebrow'] ); ?></span>
							<?php endif; ?>
							<div class="udp-home-eventos__card-datetime">
								<?php if ( $fecha_hora ) : ?>
								<time class="udp-home-eventos__card-fecha" datetime="<?php echo esc_attr( $card['fecha'] ?? '' ); ?>">
									<?php echo esc_html( $fecha_hora ); ?>
								</time>
								<?php endif; ?>
								<?php if ( ! empty( $card['lugar'] ) ) : ?>
								<span class="udp-home-eventos__card-lugar"><?php echo esc_html( $card['lugar'] ); ?></span>
								<?php endif; ?>
							</div>
						</div>
						<span class="udp-home-eventos__card-btn" aria-hidden="true">
							<?php echo $arrow_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</span>
					</div>
				</div>
			</a>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php if ( $lista ) : ?>
		<div class="udp-home-eventos__lista" role="list">
			<?php foreach ( $lista as $card ) :
				$fecha_corta = $card['fecha_display'] ?? '';
				if ( empty( $fecha_corta ) && ! empty( $card['fecha'] ) ) {
					$ts = strtotime( $card['fecha'] );
					$fecha_corta = $ts ? date_i18n( 'j F Y', $ts ) : '';
				}
			?>
			<a href="<?php echo esc_url( $card['href'] ); ?>" class="udp-home-eventos__lista-row" role="listitem">
				<span class="udp-home-eventos__lista-eyebrow"><?php echo esc_html( $card['eyebrow'] ?? '' ); ?></span>
				<span class="udp-home-eventos__lista-titulo"><?php echo esc_html( $card['titulo'] ); ?></span>
				<span class="udp-home-eventos__lista-fecha"><?php echo esc_html( $fecha_corta ); ?></span>
			</a>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

	</div>
</section>
