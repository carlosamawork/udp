<?php
/**
 * Footer > Contact strip (Figma 4401:23290 — top section)
 *
 * 6 mini-bloques en línea: dirección + 4 teléfonos + 2 emails.
 *
 * TODO (F-options): mover este contenido a un grupo ACF nuevo en
 * `udp-options-footer` (sub-fields: direccion_label, direccion_value,
 * mesa_central_label, mesa_central_value, mesa_ayuda_*, admision_*,
 * matricula_*, email_principal, email_legal). Por ahora va hardcoded
 * según Figma para alcanzar pixel-perfect en F2.
 *
 * @package Starter_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Permite override vía filtro `udp_footer_contact_blocks` para cuando
// se cree el grupo ACF correspondiente.
$blocks = apply_filters(
	'udp_footer_contact_blocks',
	array(
		array(
			'label' => 'Dirección',
			'value' => 'Manuel Rodríguez Sur 415, Santiago, Chile',
			'width' => '317px',
		),
		array(
			'label' => 'Mesa Central',
			'value' => '(+56) 226762000',
			'width' => '175px',
		),
		array(
			'label' => 'Mesa de ayuda',
			'value' => '(+56) 222130800',
			'width' => '175px',
		),
		array(
			'label' => 'Admisión',
			'value' => '(+56) 226762020 – 2015',
			'width' => '175px',
		),
		array(
			'label' => 'Matrícula',
			'value' => '(+56) 226762012 – 2013',
			'width' => '175px',
		),
		array(
			'label' => '',
			'value' => "mesa.ayuda@mail.udp.cl\nlegal@udp.cl",
			'width' => '175px',
		),
	)
);

if ( empty( $blocks ) ) {
	return;
}
?>
<div class="udp-footer-contact">
	<?php foreach ( $blocks as $block ) :
		$label = isset( $block['label'] ) ? (string) $block['label'] : '';
		$value = isset( $block['value'] ) ? (string) $block['value'] : '';
		$width = isset( $block['width'] ) ? (string) $block['width'] : '';
		if ( '' === $value ) {
			continue;
		}
		$style = '' !== $width ? ' style="--udp-footer-contact-w: ' . esc_attr( $width ) . ';"' : '';
		?>
		<div class="udp-footer-contact__block"<?php echo $style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php if ( '' !== $label ) : ?>
				<p class="udp-footer-contact__label"><?php echo esc_html( $label ); ?></p>
			<?php endif; ?>
			<?php
			$lines = preg_split( "/\r\n|\n|\r/", $value );
			foreach ( $lines as $line ) :
				if ( '' === trim( $line ) ) {
					continue;
				}
				?>
				<p class="udp-footer-contact__value"><?php echo esc_html( $line ); ?></p>
			<?php endforeach; ?>
		</div>
	<?php endforeach; ?>
</div>
