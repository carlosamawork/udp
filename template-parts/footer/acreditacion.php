<?php
/**
 * Footer > Sello de acreditación (Figma 4401:23290 — bottom-right)
 *
 * Renderiza el sello SelloAcreditacion_UDP_CNA si está configurado en la
 * options page General (campo `logo_acreditacion`). Si no, no imprime nada.
 *
 * TODO (F-options): añadir campo `texto_acreditacion` en el grupo
 * options_footer para el texto descriptivo a la izquierda del sello
 * ("Universidad Diego Portales acreditada en nivel de Excelencia..."),
 * o aceptar que el sello ya incluye el texto y dejarlo como única imagen.
 *
 * @package Starter_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sello = function_exists( 'udp_get_logo_url' ) ? udp_get_logo_url( 'acreditacion' ) : '';
if ( empty( $sello ) ) {
	return;
}
?>
<div class="udp-footer-acreditacion" aria-label="Sello de acreditación">
	<img
		src="<?php echo esc_url( $sello ); ?>"
		alt="<?php esc_attr_e( 'Sello de acreditación CNA — Universidad Diego Portales', 'starter-theme' ); ?>"
		loading="lazy"
		decoding="async"
	/>
</div>
