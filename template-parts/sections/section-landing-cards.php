<?php
/**
 * Section Landing > Cards container (grid o swiper, con back-to-parent auto)
 *
 * Si la página tiene un padre, prepende una card "Volver a {padre}" sintética
 * antes del array de cards manuales.
 *
 * @package Starter_Theme
 *
 * @var array $args ['cards' => array, 'display' => string ('grid'|'swiper'), 'parent_id' => int]
 */
$cards     = isset( $args['cards'] ) && is_array( $args['cards'] ) ? $args['cards'] : array();
$display   = isset( $args['display'] ) && in_array( $args['display'], array( 'grid', 'swiper' ), true ) ? $args['display'] : 'grid';
$parent_id = isset( $args['parent_id'] ) ? (int) $args['parent_id'] : 0;

// Back-to-parent card sintética (solo si la página tiene padre).
$back_card = null;
if ( $parent_id > 0 ) {
	$parent_title = get_the_title( $parent_id );
	$back_card    = array(
		'variant' => 'back',
		'titulo'  => sprintf( __( 'Volver a %s', 'starter-theme' ), $parent_title ),
		'link'    => array(
			'url'    => get_permalink( $parent_id ),
			'title'  => $parent_title,
			'target' => '',
		),
	);
}

$all_cards = $back_card ? array_merge( array( $back_card ), $cards ) : $cards;

if ( empty( $all_cards ) ) {
	return;
}

$container_class = 'udp-section-cards udp-section-cards--' . $display;
?>
<section class="<?php echo esc_attr( $container_class ); ?>"<?php echo $display === 'swiper' ? ' data-udp-swiper' : ''; ?>>
	<?php if ( $display === 'swiper' ) : ?>
		<div class="udp-section-cards__viewport swiper">
			<ul class="udp-section-cards__list swiper-wrapper">
				<?php foreach ( $all_cards as $index => $card ) : ?>
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
			<?php foreach ( $all_cards as $index => $card ) : ?>
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
