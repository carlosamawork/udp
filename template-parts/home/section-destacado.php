<?php
/**
 * Home — Sección 6: Destacado azul (foto + texto)
 *
 * ACF fields: posttitulos_titulo (wysiwyg), posttitulos_descripcion (wysiwyg),
 *             posttitulos_link_texto, posttitulos_link_url,
 *             posttitulos_imagen (array).
 *
 * @package starter-bs5
 */

$titulo      = get_field( 'posttitulos_titulo' );
$descripcion = get_field( 'posttitulos_descripcion' );
$link_texto  = get_field( 'posttitulos_link_texto' );
$link_url    = get_field( 'posttitulos_link_url' );
$imagen      = get_field( 'posttitulos_imagen' );

if ( ! $titulo && ! $descripcion ) {
    return;
}

$img_url = ! empty( $imagen['url'] ) ? $imagen['url'] : '';
$img_alt = ! empty( $imagen['alt'] ) ? $imagen['alt'] : '';
?>
<section class="udp-home-destacado">
    <div class="container">
        <div class="udp-home-destacado__inner row align-items-center g-5">
            <div class="col-lg-6 udp-home-destacado__content">
                <?php if ( $titulo ) : ?>
                    <div class="udp-home-destacado__titulo"><?php echo wp_kses_post( $titulo ); ?></div>
                <?php endif; ?>
                <?php if ( $descripcion ) : ?>
                    <div class="udp-home-destacado__descripcion">
                        <?php echo wp_kses_post( $descripcion ); ?>
                    </div>
                <?php endif; ?>
                <?php if ( $link_texto && $link_url ) : ?>
                    <a href="<?php echo esc_url( $link_url ); ?>" class="udp-home-destacado__cta btn btn-outline-light mt-4">
                        <?php echo esc_html( $link_texto ); ?>
                    </a>
                <?php endif; ?>
            </div>
            <?php if ( $img_url ) : ?>
                <div class="col-lg-6 udp-home-destacado__media">
                    <img
                        src="<?php echo esc_url( $img_url ); ?>"
                        alt="<?php echo esc_attr( $img_alt ); ?>"
                        loading="lazy"
                        decoding="async"
                        class="udp-home-destacado__imagen img-fluid"
                        width="<?php echo esc_attr( $imagen['width'] ?? '' ); ?>"
                        height="<?php echo esc_attr( $imagen['height'] ?? '' ); ?>"
                    >
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
