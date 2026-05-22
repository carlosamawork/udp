<?php
/**
 * Simple Accordion — columna central: the_content() + acordeón ACF.
 *
 * Renderiza el layout 3-col. Las columnas laterales son <aside> vacíos —
 * puntos de extensión para la fase posterior (tarjetas de compañero).
 *
 * @package Starter_Theme
 *
 * @var array $args {
 *     @type array $acordeon  Rows del repeater ACF 'acordeon'.
 * }
 */
$acordeon = isset( $args['acordeon'] ) ? $args['acordeon'] : array();
?>
<div class="udp-simple-accordion__layout">

	<aside class="udp-simple-accordion__col-left" aria-hidden="true"></aside>

	<div class="udp-simple-accordion__col-center">

		<div class="udp-simple-accordion__body">
			<?php the_content(); ?>
		</div>

		<?php if ( ! empty( $acordeon ) ) : ?>
			<div class="udp-simple-accordion__accordion">
				<ul class="udp-block-accordion__list">
					<?php foreach ( $acordeon as $item ) :
						$item_titulo    = isset( $item['titulo'] )    ? $item['titulo']    : '';
						$item_contenido = isset( $item['contenido'] ) ? $item['contenido'] : '';
						if ( ! $item_titulo ) continue;
					?>
						<li class="udp-block-accordion__item">
							<details class="udp-block-accordion__details">
								<summary class="udp-block-accordion__summary">
									<span class="udp-block-accordion__summary-title"><?php echo esc_html( $item_titulo ); ?></span>
									<span class="udp-block-accordion__summary-icon" aria-hidden="true">
										<svg width="14" height="14" viewBox="0 0 14 14" fill="none">
											<path d="M3 5l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
									</span>
								</summary>
								<?php if ( $item_contenido ) : ?>
									<div class="udp-block-accordion__content">
										<?php echo wp_kses_post( $item_contenido ); ?>
									</div>
								<?php endif; ?>
							</details>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

	</div>

	<aside class="udp-simple-accordion__col-right" aria-hidden="true"></aside>

</div>
