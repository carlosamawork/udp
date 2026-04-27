<?php
/**
 * Footer > Columnas
 *
 * @package Starter_Theme
 */
$columns = udp_get_footer_columns();
if ( empty( $columns ) ) {
	return;
}
?>
<div class="udp-footer-columns">
	<?php foreach ( $columns as $col ) : ?>
		<div class="udp-footer-columns__col">
			<?php if ( ! empty( $col['titulo'] ) ) : ?>
				<h3 class="udp-footer-columns__title"><?php echo esc_html( $col['titulo'] ); ?></h3>
			<?php endif; ?>
			<?php if ( ! empty( $col['links'] ) && is_array( $col['links'] ) ) : ?>
				<ul class="udp-footer-columns__links">
					<?php foreach ( $col['links'] as $link ) : ?>
						<?php
						$url   = isset( $link['url'] ) ? $link['url'] : '';
						$label = isset( $link['label'] ) ? $link['label'] : '';
						if ( empty( $url ) || empty( $label ) ) {
							continue;
						}
						?>
						<li>
							<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>
