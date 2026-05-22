<?php
/**
 * Home — Sección 7: Vida Universitaria
 *
 * ACF fields: vida_titulo, vida_texto, vida_links (repeater),
 *             vida_imagen (array).
 *
 * @package starter-bs5
 */

$titulo  = get_field( 'vida_titulo' );
$texto   = get_field( 'vida_texto' );
$links   = get_field( 'vida_links' );
$imagen  = get_field( 'vida_imagen' );

if ( ! $titulo && ! $texto ) {
    return;
}

$img_url  = ! empty( $imagen['url'] ) ? $imagen['url'] : '';
$img_alt  = ! empty( $imagen['alt'] ) ? $imagen['alt'] : '';
$has_media = (bool) $img_url;
?>
<section class="udp-home-vida">
    <div class="container">
        <div class="udp-home-vida__inner row g-5 align-items-center">
            <div class="col-lg-<?php echo $has_media ? '5' : '12'; ?> udp-home-vida__content">
                <?php if ( $titulo ) : ?>
                    <h2 class="udp-home-vida__titulo"><?php echo esc_html( $titulo ); ?></h2>
                <?php endif; ?>
                <?php if ( $texto ) : ?>
                    <p class="udp-home-vida__texto"><?php echo esc_html( $texto ); ?></p>
                <?php endif; ?>
                <?php if ( ! empty( $links ) ) : ?>
                    <ul class="udp-home-vida__links list-unstyled">
                        <?php foreach ( $links as $item ) : ?>
                            <?php if ( empty( $item['link_url'] ) ) continue; ?>
                            <li class="udp-home-vida__links-item">
                                <a href="<?php echo esc_url( $item['link_url'] ); ?>" class="udp-home-vida__link">
                                    <?php echo esc_html( $item['link_texto'] ); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <?php if ( $has_media ) : ?>
                <div class="col-lg-7 udp-home-vida__media">
                    <?php if ( $img_url ) : ?>
                        <img
                            src="<?php echo esc_url( $img_url ); ?>"
                            alt="<?php echo esc_attr( $img_alt ); ?>"
                            class="udp-home-vida__imagen img-fluid"
                            loading="lazy"
                            decoding="async"
                            width="<?php echo esc_attr( $imagen['width'] ?? '' ); ?>"
                            height="<?php echo esc_attr( $imagen['height'] ?? '' ); ?>"
                        >
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
