<?php
/**
 * Render institucional reutilizable: hero + breadcrumb + chips + share + secciones.
 *
 * Lo usan `templates/page-institucional.php` (campo ACF `sections`) y `page.php`
 * (contenido legacy transformado por udp_institucional_sections_from_legacy()).
 *
 * @param array $args {
 *   @type array  $sections        Secciones (shape de get_field('sections')).
 *   @type array  $anchors         Anchors de udp_institucional_collect_anchors().
 *   @type bool   $show_breadcrumb  Mostrar breadcrumb en el hero.
 *   @type string $page_title       Título (H1).
 * }
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$sections        = is_array( $args['sections'] ?? null ) ? $args['sections'] : array();
$anchors         = is_array( $args['anchors'] ?? null ) ? $args['anchors'] : array();
$show_breadcrumb = isset( $args['show_breadcrumb'] ) ? (bool) $args['show_breadcrumb'] : true;
$page_title      = $args['page_title'] ?? get_the_title();
$show_nav        = count( $anchors ) >= 2; // mínimo "Inicio" + 1 sección real

$allowed = array( 'rich_text_sidebar', 'rich_text', 'cards_dark_row', 'people_carousel', 'premio_block', 'text_accordion', 'featured_carousel', 'buttons', 'stats', 'video', 'gallery', 'related', 'back_link' );
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-inst' ); ?>>

	<?php
	get_template_part(
		'template-parts/institucional/header',
		null,
		array(
			'show_breadcrumb' => $show_breadcrumb,
			'page_title'      => $page_title,
		)
	);

	if ( $show_nav ) {
		get_template_part( 'template-parts/institucional/nav-chips', null, array( 'anchors' => $anchors ) );
	}

	get_template_part( 'template-parts/sections/share-floating' );

	foreach ( $sections as $i => $section ) :
		$layout = $section['acf_fc_layout'] ?? '';
		if ( ! in_array( $layout, $allowed, true ) ) {
			continue;
		}
		$anchor = function_exists( 'udp_institucional_anchor_for_index' )
			? udp_institucional_anchor_for_index( $anchors, $i )
			: null;

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
	?>

</article>
