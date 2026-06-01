<?php
/**
 * Card: Anuario UDP
 *
 * @package Starter_Theme
 * @var array $args { titulo, fecha, pdf_url, imagen }
 */

defined( 'ABSPATH' ) || exit;

$titulo  = $args['titulo'] ?? '';
$fecha   = $args['fecha'] ?? '';
$pdf_url = $args['pdf_url'] ?? '';
$imagen  = $args['imagen'] ?? [];

if ( empty( $titulo ) || empty( $pdf_url ) ) {
	return;
}

$fecha_display = '';
if ( $fecha ) {
	$dt = DateTime::createFromFormat( 'Ymd', $fecha );
	if ( $dt ) {
		$fecha_display = date_i18n( 'F Y', $dt->getTimestamp() );
	}
}

$has_image       = ! empty( $imagen['url'] );
$media_class     = 'udp-card-anuario__media' . ( $has_image ? '' : ' udp-card-anuario__media--placeholder' );
?>
<a class="udp-card-anuario"
   href="<?php echo esc_url( $pdf_url ); ?>"
   target="_blank"
   rel="noopener noreferrer"
   aria-label="<?php echo esc_attr( $titulo ); ?> (PDF)">

	<figure class="<?php echo esc_attr( $media_class ); ?>">
		<?php if ( $has_image ) : ?>
			<img
				src="<?php echo esc_url( $imagen['url'] ); ?>"
				alt="<?php echo esc_attr( $imagen['alt'] ?: $titulo ); ?>"
				width="<?php echo (int) ( $imagen['width'] ?? 317 ); ?>"
				height="<?php echo (int) ( $imagen['height'] ?? 391 ); ?>"
				loading="lazy"
			/>
		<?php else : ?>
			<div class="udp-media-placeholder"></div>
		<?php endif; ?>
	</figure>

	<div class="udp-card-anuario__body">
		<p class="udp-card-anuario__title"><?php echo esc_html( $titulo ); ?></p>
		<?php if ( $fecha_display ) : ?>
			<time class="udp-card-anuario__date"><?php echo esc_html( $fecha_display ); ?></time>
		<?php endif; ?>
	</div>

</a>
