<?php
/**
 * Layout — Destacados carrusel (legacy `destacados_carrusel`).
 *
 * Carrusel horizontal (scroll-snap CSS, sin JS) de cards con imagen + título.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$data   = $args['data'] ?? array();
$anchor = $args['anchor'] ?? null;

$title = $data['title'] ?? '';
$items = is_array( $data['items'] ?? null ) ? $data['items'] : array();
$id    = $anchor['id'] ?? '';

if ( empty( $items ) ) {
	return;
}
?>
<section
	<?php if ( $id ) : ?>id="<?php echo esc_attr( $id ); ?>"<?php endif; ?>
	class="udp-inst-section udp-inst-featured"
	style="scroll-margin-top: var(--udp-anchor-offset, 168px);"
>
	<div class="udp-inst-featured__inner">
		<?php if ( $title ) : ?>
			<h2 class="udp-inst-featured__title"><?php echo esc_html( $title ); ?></h2>
		<?php endif; ?>

		<ul class="udp-inst-featured__list">
			<?php
			foreach ( $items as $it ) :
				$href = $it['link']   ?? '';
				$img  = $it['imagen'] ?? '';
				$t    = $it['titulo'] ?? '';
				$tag  = $href ? 'a' : 'div';
			?>
				<li class="udp-inst-featured__item">
					<<?php echo $tag; ?> class="udp-inst-featured__card"<?php echo $href ? ' href="' . esc_url( $href ) . '"' : ''; ?>>
						<span class="udp-inst-featured__media<?php echo $img ? '' : ' udp-inst-featured__media--placeholder'; ?>">
							<?php if ( $img ) : ?>
								<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $t ); ?>" loading="lazy">
							<?php endif; ?>
						</span>
						<?php if ( $t ) : ?>
							<span class="udp-inst-featured__name"><?php echo esc_html( $t ); ?></span>
						<?php endif; ?>
					</<?php echo $tag; ?>>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
