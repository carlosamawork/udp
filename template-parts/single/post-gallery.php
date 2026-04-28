<?php
/**
 * Single Post > Image gallery (Swiper)
 *
 * Renderiza el campo ACF `galeria_de_imagenes` como carousel Swiper.
 *
 * @package Starter_Theme
 *
 * @var array $args ['post_id' => int]
 */
$post_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();
if ( ! $post_id ) {
    return;
}

$gallery = function_exists( 'get_field' ) ? get_field( 'galeria_de_imagenes', $post_id ) : null;
if ( ! is_array( $gallery ) || empty( $gallery ) ) {
    return;
}
?>
<section class="udp-single-post__gallery" data-udp-post-gallery>
    <div class="udp-single-post__gallery-viewport swiper">
        <ul class="udp-single-post__gallery-list swiper-wrapper">
            <?php foreach ( $gallery as $image ) :
                $url = $image['sizes']['large'] ?? ( $image['url'] ?? '' );
                $alt = $image['alt'] ?? '';
                if ( empty( $url ) ) {
                    continue;
                }
            ?>
                <li class="udp-single-post__gallery-item swiper-slide">
                    <img src="<?php echo esc_url( $url ); ?>" alt="<?php echo esc_attr( $alt ); ?>" loading="lazy" decoding="async" />
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="udp-single-post__gallery-nav">
        <button type="button" class="udp-single-post__gallery-prev" aria-label="<?php esc_attr_e( 'Anterior', 'starter-theme' ); ?>">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M9 3L5 7l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <button type="button" class="udp-single-post__gallery-next" aria-label="<?php esc_attr_e( 'Siguiente', 'starter-theme' ); ?>">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M5 3l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
    </div>
</section>
