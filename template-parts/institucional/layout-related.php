<?php
/**
 * Layout — "También te puede interesar"
 *
 * Banda oscura: encabezado + carrusel de páginas relacionadas (hermanas).
 * Reutiliza el sistema de cards + swiper de Section Landing; la card
 * "Volver a {padre}" se antepone automáticamente vía `parent_id`.
 *
 * Se genera automáticamente en el render por defecto (page.php) — no es un
 * layout ACF editable.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$data      = $args['data'] ?? array();
$cards     = is_array( $data['cards'] ?? null ) ? $data['cards'] : array();
$parent_id = (int) ( $data['parent_id'] ?? 0 );

if ( empty( $cards ) && ! $parent_id ) {
	return;
}
?>
<section class="udp-inst-section udp-inst-related">
	<div class="udp-inst-related__head">
		<h2 class="udp-inst-related__title"><?php esc_html_e( 'También te puede interesar', 'starter-theme' ); ?></h2>
	</div>
	<?php
	get_template_part(
		'template-parts/sections/section-landing-cards',
		null,
		array(
			'cards'     => $cards,
			'display'   => 'swiper',
			'parent_id' => $parent_id,
		)
	);
	?>
</section>
