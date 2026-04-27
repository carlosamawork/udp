<?php
/**
 * Section Landing > Cards container (grid o swiper)
 *
 * @package Starter_Theme
 *
 * @var array $args ['cards' => array, 'display' => string ('grid'|'swiper')]
 */
$cards   = isset( $args['cards'] ) && is_array( $args['cards'] ) ? $args['cards'] : array();
$display = isset( $args['display'] ) && in_array( $args['display'], array( 'grid', 'swiper' ), true ) ? $args['display'] : 'grid';

if ( empty( $cards ) ) {
	return;
}

$container_class = 'udp-section-cards udp-section-cards--' . $display;
?>
<section class="<?php echo esc_attr( $container_class ); ?>"<?php echo $display === 'swiper' ? ' data-udp-swiper' : ''; ?>>
	<?php if ( $display === 'swiper' ) : ?>
		<div class="udp-section-cards__viewport swiper">
			<ul class="udp-section-cards__list swiper-wrapper">
				<?php foreach ( $cards as $index => $card ) : ?>
					<li class="udp-section-cards__item swiper-slide">
						<?php
						get_template_part(
							'template-parts/sections/section-landing-card',
							null,
							array( 'card' => $card, 'index' => $index )
						);
						?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php else : ?>
		<ul class="udp-section-cards__list">
			<?php foreach ( $cards as $index => $card ) : ?>
				<li class="udp-section-cards__item">
					<?php
					get_template_part(
						'template-parts/sections/section-landing-card',
						null,
						array( 'card' => $card, 'index' => $index )
					);
					?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>
