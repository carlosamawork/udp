<?php
/**
 * Layout — Números destacados (legacy `numeros_destacados`).
 *
 * Banda oscura con cifras grandes (número + título + subtítulo).
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$nums   = is_array( $args['data']['numeros'] ?? null ) ? $args['data']['numeros'] : array();
$anchor = $args['anchor'] ?? null;
$id     = $anchor['id'] ?? '';

if ( empty( $nums ) ) {
	return;
}
?>
<section
	<?php if ( $id ) : ?>id="<?php echo esc_attr( $id ); ?>"<?php endif; ?>
	class="udp-inst-section udp-inst-stats"
	style="scroll-margin-top: var(--udp-anchor-offset, 168px);"
>
	<ul class="udp-inst-stats__list">
		<?php foreach ( $nums as $n ) : ?>
			<li class="udp-inst-stats__item">
				<span class="udp-inst-stats__num"><?php echo esc_html( $n['numero'] ); ?></span>
				<?php if ( ! empty( $n['titulo'] ) ) : ?>
					<span class="udp-inst-stats__title"><?php echo esc_html( $n['titulo'] ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $n['subtitulo'] ) ) : ?>
					<span class="udp-inst-stats__sub"><?php echo esc_html( $n['subtitulo'] ); ?></span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
