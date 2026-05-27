<?php
/**
 * Layout — Galería de imágenes (legacy `galeria_de_imagenes`).
 *
 * Grid CSS (sin JS). Reutiliza las clases del bloque F7
 * `.udp-block-image-gallery--grid` (SCSS ya existente).
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$data   = $args['data'] ?? array();
$anchor = $args['anchor'] ?? null;

$title  = $data['title'] ?? '';
$images = is_array( $data['images'] ?? null ) ? $data['images'] : array();
$id     = $anchor['id'] ?? '';

if ( empty( $images ) ) {
	return;
}
?>
<section
	<?php if ( $id ) : ?>id="<?php echo esc_attr( $id ); ?>"<?php endif; ?>
	class="udp-block-image-gallery udp-block-image-gallery--grid udp-block-image-gallery--light"
	style="scroll-margin-top: var(--udp-anchor-offset, 168px);"
>
	<div class="udp-block-image-gallery__inner">
		<?php if ( $title ) : ?>
			<header class="udp-block-image-gallery__header">
				<h2 class="udp-block-image-gallery__title"><?php echo esc_html( $title ); ?></h2>
			</header>
		<?php endif; ?>

		<ul class="udp-block-image-gallery__list">
			<?php foreach ( $images as $img ) :
				if ( empty( $img['url'] ) ) {
					continue;
				}
			?>
				<li class="udp-block-image-gallery__item">
					<img src="<?php echo esc_url( $img['url'] ); ?>" alt="<?php echo esc_attr( $img['alt'] ?? '' ); ?>" loading="lazy" decoding="async">
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
