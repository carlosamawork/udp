<?php
/**
 * Simple Accordion — sección "También te puede interesar".
 *
 * Swiper horizontal de link cards (sin imagen). Reutiliza initSectionLandingSwiper()
 * de F3 mediante la clase udp-section-cards--swiper.
 *
 * @package Starter_Theme
 *
 * @var array $args {
 *     @type array $relacionados  Rows del repeater ACF 'relacionados'.
 * }
 */
$relacionados = isset( $args['relacionados'] ) ? $args['relacionados'] : array();

if ( empty( $relacionados ) ) {
	return;
}
?>
<section class="udp-simple-accordion__related">
	<div class="container">
		<h2 class="udp-simple-accordion__related-title">
			<?php esc_html_e( 'También te puede interesar', 'starter-theme' ); ?>
		</h2>
	</div>

	<div class="udp-section-cards--swiper">
		<div class="swiper">
			<div class="swiper-wrapper">
				<?php foreach ( $relacionados as $item ) :
					$titulo = isset( $item['titulo'] ) ? $item['titulo'] : '';
					$link   = isset( $item['link'] )   ? $item['link']   : array();
					if ( ! $titulo || empty( $link['url'] ) ) {
						continue;
					}
					$target = ! empty( $link['target'] ) ? $link['target'] : '_self';
					$rel    = '_blank' === $target ? 'noopener noreferrer' : '';
				?>
					<div class="swiper-slide">
						<a
							class="udp-simple-accordion__related-card"
							href="<?php echo esc_url( $link['url'] ); ?>"
							target="<?php echo esc_attr( $target ); ?>"
							<?php if ( $rel ) : ?>rel="<?php echo esc_attr( $rel ); ?>"<?php endif; ?>
						>
							<span class="udp-simple-accordion__related-card-title">
								<?php echo esc_html( $titulo ); ?>
							</span>
							<span class="udp-simple-accordion__related-card-arrow" aria-hidden="true">
								<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
									<path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
								</svg>
							</span>
						</a>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>
