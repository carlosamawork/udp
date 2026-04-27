<?php
/**
 * Footer > Contact strip
 * Renderiza los bloques de contacto desde ACF (options page Footer → contact_blocks).
 *
 * @package Starter_Theme
 */
$blocks = function_exists( 'get_field' ) ? get_field( 'contact_blocks', 'option' ) : array();

if ( empty( $blocks ) || ! is_array( $blocks ) ) {
	return;
}
?>
<div class="udp-footer-contact">
	<?php foreach ( $blocks as $block ) :
		$titulo  = isset( $block['titulo'] ) ? $block['titulo'] : '';
		$valor   = isset( $block['valor'] ) ? $block['valor'] : '';
		$enlace  = isset( $block['enlace'] ) ? $block['enlace'] : '';

		if ( empty( $titulo ) && empty( $valor ) ) {
			continue;
		}
		?>
		<div class="udp-footer-contact__block">
			<?php if ( ! empty( $titulo ) ) : ?>
				<p class="udp-footer-contact__title"><?php echo esc_html( $titulo ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $valor ) ) : ?>
				<?php if ( ! empty( $enlace ) ) : ?>
					<a class="udp-footer-contact__value udp-footer-contact__value--link" href="<?php echo esc_url( $enlace ); ?>">
						<?php echo nl2br( esc_html( $valor ) ); ?>
					</a>
				<?php else : ?>
					<p class="udp-footer-contact__value"><?php echo nl2br( esc_html( $valor ) ); ?></p>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>
