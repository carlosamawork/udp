<?php
/**
 * Template Name: Institucional
 *
 * Página institucional con hero morado + breadcrumb + secciones flexibles
 * con navegación por anchors (chips bar sticky) y botón share flotante.
 *
 * El render vive en template-parts/institucional/article.php (compartido con
 * page.php, que aplica el mismo estilo a las páginas legacy).
 *
 * @package Starter_Theme
 */

get_header();

$page_header = function_exists( 'get_field' ) ? get_field( 'page_header' ) : array();
$sections    = function_exists( 'udp_institucional_get_sections' ) ? udp_institucional_get_sections() : array();
$anchors     = function_exists( 'udp_institucional_collect_anchors' ) ? udp_institucional_collect_anchors( null, $sections ) : array();

get_template_part(
	'template-parts/institucional/article',
	null,
	array(
		'sections'        => $sections,
		'anchors'         => $anchors,
		'show_breadcrumb' => ! empty( $page_header['show_breadcrumb'] ),
		'page_title'      => get_the_title(),
	)
);

get_footer();
