<?php
/**
 * Layout — Links cuadrados (legacy `links_cuadrados` / `links_cuadrados_externos`).
 *
 * Mismas cards que "También te puede interesar" (Section Landing: gris → azul,
 * con ícono, sin necesitar imagen). Sin card "Volver" (parent_id = 0).
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$data   = $args['data'] ?? array();
$anchor = $args['anchor'] ?? null;

$title = $data['title'] ?? '';
$cards = is_array( $data['cards'] ?? null ) ? $data['cards'] : array();
$id    = $anchor['id'] ?? '';

if ( empty( $cards ) ) {
	return;
}
?>
<section
	<?php if ( $id ) : ?>id="<?php echo esc_attr( $id ); ?>"<?php endif; ?>
	class="udp-inst-section udp-inst-related"
	style="scroll-margin-top: var(--udp-anchor-offset, 168px);"
>
	<?php if ( $title ) : ?>
		<div class="udp-inst-related__head">
			<h2 class="udp-inst-related__title"><?php echo esc_html( $title ); ?></h2>
		</div>
	<?php endif; ?>
	<?php
	get_template_part(
		'template-parts/sections/section-landing-cards',
		null,
		array(
			'cards'     => $cards,
			'display'   => 'swiper',
			'parent_id' => 0,
		)
	);
	?>
</section>
