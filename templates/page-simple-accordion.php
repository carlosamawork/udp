<?php
/**
 * Template Name: Simple Accordion
 *
 * Layout: breadcrumb + título / 3 columnas (laterales vacías + columna central con
 * the_content() + acordeón ACF) / sección "También te puede interesar" (Swiper).
 * Columnas laterales son placeholders para fase posterior (tarjetas de compañero).
 *
 * @package Starter_Theme
 */

get_header();

$acordeon    = function_exists( 'get_field' ) ? get_field( 'acordeon' )    : array();
$relacionados = function_exists( 'get_field' ) ? get_field( 'relacionados' ) : array();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-simple-accordion' ); ?>>

	<?php
	get_template_part( 'template-parts/simple-accordion/page-header' );

	get_template_part(
		'template-parts/simple-accordion/main-content',
		null,
		array( 'acordeon' => $acordeon ?: array() )
	);

	if ( ! empty( $relacionados ) ) {
		get_template_part(
			'template-parts/simple-accordion/related',
			null,
			array( 'relacionados' => $relacionados )
		);
	}
	?>

</article>

<?php
get_template_part(
	'template-parts/single/post-share',
	null,
	array( 'post_id' => get_the_ID() )
);

get_footer();
