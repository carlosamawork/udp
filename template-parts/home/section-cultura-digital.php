<?php
/**
 * Home — Sección 9: Cultura Digital
 *
 * ACF fields: cd_titulo, cd_texto, fondo (imagen de fondo, array),
 *             cd_link_texto, cd_link_url.
 *
 * @package starter-bs5
 */

$titulo     = get_field( 'cd_titulo' );
$texto      = get_field( 'cd_texto' );
$fondo      = get_field( 'fondo' );
$link_texto = get_field( 'cd_link_texto' );
$link_url   = get_field( 'cd_link_url' );

if ( ! $titulo && ! $texto ) {
    return;
}

$fondo_url = ! empty( $fondo['url'] ) ? $fondo['url'] : '';
$fondo_alt = ! empty( $fondo['alt'] ) ? $fondo['alt'] : '';

$style = $fondo_url
    ? 'style="background-image: url(' . esc_url( $fondo_url ) . ');"'
    : '';
?>
<section class="udp-home-cultura-digital" <?php echo $style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- URL ya escapada con esc_url() arriba ?>>
    <div class="udp-home-cultura-digital__overlay"></div>
    <div class="container udp-home-cultura-digital__inner">
        <?php if ( $titulo ) : ?>
            <h2 class="udp-home-cultura-digital__titulo"><?php echo esc_html( $titulo ); ?></h2>
        <?php endif; ?>
        <?php if ( $texto ) : ?>
            <p class="udp-home-cultura-digital__texto"><?php echo esc_html( $texto ); ?></p>
        <?php endif; ?>
        <?php if ( $link_texto && $link_url ) : ?>
            <a href="<?php echo esc_url( $link_url ); ?>" class="udp-home-cultura-digital__cta btn btn-outline-light">
                <?php echo esc_html( $link_texto ); ?>
            </a>
        <?php endif; ?>
    </div>
</section>
