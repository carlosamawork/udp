<?php
/**
 * Layout — Texto a una sola columna (legacy `contenido` suelto).
 *
 * Sin columna de título lateral: el contenido (con su encabezado) fluye en una
 * única columna legible. Reutiliza el estilo `.udp-inst-plain`.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$data   = $args['data'] ?? array();
$anchor = $args['anchor'] ?? null;

$body = $data['body'] ?? '';
$id   = $anchor['id'] ?? '';

if ( '' === trim( (string) $body ) ) {
	return;
}
?>
<section
	<?php if ( $id ) : ?>id="<?php echo esc_attr( $id ); ?>"<?php endif; ?>
	class="udp-inst-section udp-inst-text"
	style="scroll-margin-top: var(--udp-anchor-offset, 168px);"
>
	<div class="udp-inst-plain"><?php echo wp_kses_post( $body ); ?></div>
</section>
