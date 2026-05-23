<?php
/**
 * Home — Sección 1: Portada
 *
 * ACF fields: portada_titulo, portada_cta_texto,
 *             portada_cta_url, portada_imagen (array).
 *
 * @package starter-bs5
 */

$titulo    = get_field( 'portada_titulo' );
$cta_texto = get_field( 'portada_cta_texto' );
$cta_url   = get_field( 'portada_cta_url' );
$imagen    = get_field( 'portada_imagen' );

if ( ! $titulo ) {
    return;
}

$img_url = ! empty( $imagen['url'] ) ? $imagen['url'] : '';
$img_alt = ! empty( $imagen['alt'] ) ? $imagen['alt'] : '';
?>
<section class="udp-home-portada js-home-portada" aria-label="Portada">
    <div class="udp-home-portada__inner container">
        <div class="udp-home-portada__content">
            <h1 class="udp-home-portada__titulo"><?php echo esc_html( $titulo ); ?></h1>
            <?php if ( $cta_texto && $cta_url ) : ?>
                <a href="<?php echo esc_url( $cta_url ); ?>" class="udp-home-portada__cta btn btn-outline-light"><?php echo esc_html( $cta_texto ); ?></a>
            <?php endif; ?>
        </div>
        <?php if ( $img_url ) : ?>
            <div class="udp-home-portada__media js-portada-media" aria-hidden="true">
                <img
                    src="<?php echo esc_url( $img_url ); ?>"
                    alt="<?php echo esc_attr( $img_alt ); ?>"
                    class="udp-home-portada__imagen"
                    loading="eager"
                    decoding="async"
                    width="<?php echo esc_attr( $imagen['width'] ?? '' ); ?>"
                    height="<?php echo esc_attr( $imagen['height'] ?? '' ); ?>"
                >
            </div>
        <?php endif; ?>
    </div>
</section>
