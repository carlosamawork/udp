<?php
/**
 * Template Name: Anuarios
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

get_template_part( 'template-parts/institucional/header', null, [
	'page_title'      => get_the_title(),
	'show_breadcrumb' => true,
] );

get_template_part( 'template-parts/institucional/share-floating' );

$items = get_field( 'anuarios_items' ) ?: [];
?>

<main>
	<section class="udp-anuarios">
		<div class="container">
			<?php if ( empty( $items ) ) : ?>
				<p class="udp-anuarios__empty">No hay anuarios disponibles.</p>
			<?php else : ?>
				<div class="udp-anuarios__grid">
					<?php foreach ( $items as $item ) :
						get_template_part( 'template-parts/anuarios/card-anuario', null, [
							'titulo'  => $item['anuario_titulo'] ?? '',
							'fecha'   => $item['anuario_fecha'] ?? '',
							'pdf_url' => $item['anuario_pdf'] ?? '',
							'imagen'  => $item['anuario_imagen'] ?: [],
						] );
					endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>

<?php get_footer(); ?>
