<?php
/**
 * Template Name: Institucional
 *
 * Página institucional con hero morado + breadcrumb + secciones flexibles
 * con navegación por anchors (chips bar sticky + rail vertical flotante)
 * y botón share flotante.
 *
 * @package Starter_Theme
 */

get_header();

$page_header = function_exists( 'get_field' ) ? get_field( 'page_header' ) : array();
$sections    = function_exists( 'get_field' ) ? get_field( 'sections' ) : array();
$anchors     = function_exists( 'udp_institucional_collect_anchors' ) ? udp_institucional_collect_anchors() : array();
$show_nav    = count( $anchors ) >= 2; // mínimo "Inicio" + 1 sección real
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-inst' ); ?>>

	<?php
	get_template_part(
		'template-parts/institucional/header',
		null,
		array(
			'show_breadcrumb' => ! empty( $page_header['show_breadcrumb'] ),
			'page_title'      => get_the_title(),
		)
	);

	if ( $show_nav ) {
		get_template_part( 'template-parts/institucional/nav-chips', null, array( 'anchors' => $anchors ) );
		get_template_part( 'template-parts/institucional/nav-rail',  null, array( 'anchors' => $anchors ) );
	}

	get_template_part( 'template-parts/sections/share-floating' );

	if ( is_array( $sections ) && ! empty( $sections ) ) :
		foreach ( $sections as $i => $section ) :
			$layout = $section['acf_fc_layout'] ?? '';
			$anchor = function_exists( 'udp_institucional_anchor_for_index' )
				? udp_institucional_anchor_for_index( $anchors, $i )
				: null;

			$allowed = array( 'rich_text_sidebar', 'cards_dark_row', 'people_carousel', 'back_link' );
			if ( ! in_array( $layout, $allowed, true ) ) {
				continue;
			}

			$slug = 'layout-' . str_replace( '_', '-', $layout );
			get_template_part(
				'template-parts/institucional/' . $slug,
				null,
				array(
					'data'   => $section,
					'anchor' => $anchor,
				)
			);
		endforeach;
	endif;
	?>

</article>

<?php
get_footer();
