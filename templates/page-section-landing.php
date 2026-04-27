<?php
/**
 * Template Name: Section Landing
 *
 * Hero + grid o swiper de cards (link interno/externo).
 *
 * @package Starter_Theme
 */

get_header();

$hero          = function_exists( 'get_field' ) ? get_field( 'hero' ) : array();
$cards         = function_exists( 'get_field' ) ? get_field( 'cards' ) : array();
$cards_display = function_exists( 'get_field' ) ? get_field( 'cards_display' ) : 'grid';
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-section-landing' ); ?>>

	<?php
	get_template_part(
		'template-parts/sections/section-landing-hero',
		null,
		array(
			'hero'       => $hero,
			'page_id'    => get_the_ID(),
			'page_title' => get_the_title(),
		)
	);

	get_template_part(
		'template-parts/sections/section-landing-cards',
		null,
		array(
			'cards'   => $cards,
			'display' => $cards_display ?: 'grid',
		)
	);
	?>

</article>

<?php
get_footer();
