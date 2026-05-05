<?php
/**
 * Block: Image Gallery (Swiper carrusel o grid).
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$titulo  = get_sub_field( 'titulo' );
$eyebrow = get_sub_field( 'eyebrow' );
$images  = get_sub_field( 'images' ) ?: array();
$layout  = get_sub_field( 'layout' ) ?: 'carousel';
$theme   = get_sub_field( 'theme' ) ?: 'dark';

if ( empty( $images ) ) {
    return;
}

$container_class = 'udp-block-image-gallery udp-block-image-gallery--' . $layout . ' udp-block-image-gallery--' . $theme;
?>
<section class="<?php echo esc_attr( $container_class ); ?>" <?php if ( $layout === 'carousel' ) : ?>data-udp-block-gallery<?php endif; ?>>
    <div class="udp-block-image-gallery__inner">
        <?php if ( $eyebrow || $titulo ) : ?>
            <header class="udp-block-image-gallery__header">
                <?php if ( $eyebrow ) : ?>
                    <p class="udp-block-image-gallery__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
                <?php endif; ?>
                <?php if ( $titulo ) : ?>
                    <h2 class="udp-block-image-gallery__title"><?php echo esc_html( $titulo ); ?></h2>
                <?php endif; ?>
            </header>
        <?php endif; ?>

        <?php if ( $layout === 'carousel' ) : ?>
            <div class="udp-block-image-gallery__viewport swiper">
                <ul class="udp-block-image-gallery__list swiper-wrapper">
                    <?php foreach ( $images as $image ) :
                        $url = $image['sizes']['large'] ?? ( $image['url'] ?? '' );
                        $alt = $image['alt'] ?? '';
                        if ( empty( $url ) ) continue;
                    ?>
                        <li class="udp-block-image-gallery__item swiper-slide">
                            <img src="<?php echo esc_url( $url ); ?>" alt="<?php echo esc_attr( $alt ); ?>" loading="lazy" decoding="async" />
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="udp-block-image-gallery__nav">
                <button type="button" class="udp-block-image-gallery__prev" aria-label="<?php esc_attr_e( 'Anterior', 'starter-theme' ); ?>">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M9 3L5 7l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <button type="button" class="udp-block-image-gallery__next" aria-label="<?php esc_attr_e( 'Siguiente', 'starter-theme' ); ?>">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M5 3l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
        <?php else : ?>
            <ul class="udp-block-image-gallery__list">
                <?php foreach ( $images as $image ) :
                    $url = $image['sizes']['large'] ?? ( $image['url'] ?? '' );
                    $alt = $image['alt'] ?? '';
                    if ( empty( $url ) ) continue;
                ?>
                    <li class="udp-block-image-gallery__item">
                        <img src="<?php echo esc_url( $url ); ?>" alt="<?php echo esc_attr( $alt ); ?>" loading="lazy" decoding="async" />
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>
