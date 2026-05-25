<?php
/**
 * Template por defecto para páginas estáticas.
 *
 * Aplica el estilo institucional (hero morado + breadcrumb + secciones) a TODAS
 * las páginas que no usan un template propio del tema. Si la página tiene
 * contenido legacy (campo ACF `secciones` del tema antiguo), lo transforma a los
 * layouts institucionales vía udp_institucional_sections_from_legacy(); si es una
 * página moderna sin ese contenido, muestra el hero + el contenido nativo.
 *
 * @package Starter_Theme
 */

get_header();

while ( have_posts() ) :
	the_post();

	$legacy     = function_exists( 'get_field' ) ? get_field( 'secciones' ) : null;
	$has_legacy = is_array( $legacy ) && ! empty( $legacy );

	if ( $has_legacy && function_exists( 'udp_institucional_get_sections' ) ) :

		$sections = udp_institucional_get_sections();
		$anchors  = udp_institucional_collect_anchors( null, $sections );

		get_template_part(
			'template-parts/institucional/article',
			null,
			array(
				'sections'        => $sections,
				'anchors'         => $anchors,
				'show_breadcrumb' => true,
				'page_title'      => get_the_title(),
			)
		);

	else :
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-inst' ); ?>>
			<?php
			get_template_part(
				'template-parts/institucional/header',
				null,
				array(
					'show_breadcrumb' => true,
					'page_title'      => get_the_title(),
				)
			);
			?>
			<div class="udp-inst-section">
				<div class="udp-inst-plain">
					<?php the_content(); ?>
				</div>
			</div>
		</article>
		<?php
	endif;

endwhile;

get_footer();
