<?php
/**
 * Section Landing > Page Header
 *
 * Breadcrumb dinámico + (opcional) eyebrow + título + descripción wysiwyg + separador inferior.
 * NO usa imagen de fondo en este template.
 *
 * @package Starter_Theme
 *
 * @var array $args ['header' => array, 'page_id' => int, 'page_title' => string]
 */
$header      = isset( $args['header'] ) && is_array( $args['header'] ) ? $args['header'] : array();
$eyebrow     = isset( $header['eyebrow'] ) ? $header['eyebrow'] : '';
$titulo      = isset( $header['titulo'] ) && ! empty( $header['titulo'] ) ? $header['titulo'] : ( $args['page_title'] ?? '' );
$descripcion = isset( $header['descripcion'] ) ? $header['descripcion'] : '';
$page_id     = isset( $args['page_id'] ) ? (int) $args['page_id'] : 0;
?>
<section class="udp-section-header">
	<div class="udp-section-header__inner">

		<?php
		get_template_part(
			'template-parts/sections/breadcrumb',
			null,
			array( 'page_id' => $page_id )
		);
		?>

		<div class="udp-section-header__content">
			<?php if ( ! empty( $eyebrow ) ) : ?>
				<p class="udp-section-header__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $titulo ) ) : ?>
				<h1 class="udp-section-header__title"><?php echo esc_html( $titulo ); ?></h1>
			<?php endif; ?>

			<?php if ( ! empty( $descripcion ) ) : ?>
				<div class="udp-section-header__desc">
					<?php echo wp_kses_post( $descripcion ); ?>
				</div>
			<?php endif; ?>
		</div>

	</div>
	<hr class="udp-section-header__separator" aria-hidden="true" />
</section>
