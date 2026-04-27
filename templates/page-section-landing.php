<?php
/**
 * Template Name: Section Landing
 *
 * Cabecera (breadcrumb auto + título + descripción wysiwyg) + grid o swiper de cards.
 * Sin imagen de fondo en el header. Cards en gris (#232323), hover lila (#4539F2).
 * Si la página tiene un padre, se prepende una card "Volver a {padre}" automática.
 *
 * @package Starter_Theme
 */

get_header();

$page_header   = function_exists( 'get_field' ) ? get_field( 'page_header' ) : array();
$cards         = function_exists( 'get_field' ) ? get_field( 'cards' ) : array();
$cards_display = function_exists( 'get_field' ) ? get_field( 'cards_display' ) : 'grid';
$page_id       = get_the_ID();
$parent_id     = (int) wp_get_post_parent_id( $page_id );
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-section-landing' ); ?>>

	<?php
	get_template_part(
		'template-parts/sections/section-landing-header',
		null,
		array(
			'header'     => $page_header,
			'page_id'    => $page_id,
			'page_title' => get_the_title(),
		)
	);

	get_template_part(
		'template-parts/sections/section-landing-cards',
		null,
		array(
			'cards'     => $cards,
			'display'   => $cards_display ?: 'grid',
			'parent_id' => $parent_id,
		)
	);
	?>

</article>

<?php
get_footer();
