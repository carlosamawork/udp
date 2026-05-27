<?php
/**
 * Home — Sección 7: Vida Universitaria
 *
 * ACF fields: vida_titulo, vida_texto, vida_links (repeater),
 *             vida_imagen (array).
 * Sub-fields vida_links: link_texto, link_url, link_externo (bool).
 *
 * @package starter-bs5
 */

$post_id = $args['post_id'] ?? (int) get_option( 'page_on_front' );

$titulo  = get_field( 'vida_titulo', $post_id );
$texto   = get_field( 'vida_texto', $post_id );
$links   = get_field( 'vida_links', $post_id );
$video   = get_field( 'vida_video', $post_id );
$imagen  = get_field( 'vida_imagen', $post_id );

if ( ! $titulo && ! $texto ) {
    return;
}

$video_url = ! empty( $video['url'] ) ? $video['url'] : '';
$img_url   = ! empty( $imagen['url'] ) ? $imagen['url'] : '';
$img_alt   = ! empty( $imagen['alt'] ) ? $imagen['alt'] : '';
$has_media = (bool) ( $video_url || $img_url );
?>
<section class="udp-home-vida">
    <div class="container">
        <div class="udp-home-vida__inner row g-5 align-items-center">
            <div class="col-lg-<?php echo $has_media ? '5' : '12'; ?> udp-home-vida__content">
                <?php if ( $titulo ) : ?>
                    <h2 class="udp-home__titulo"><?php echo esc_html( $titulo ); ?></h2>
                <?php endif; ?>
                <?php if ( $texto ) : ?>
                    <p class="udp-home-vida__texto"><?php echo esc_html( $texto ); ?></p>
                <?php endif; ?>
                <?php if ( ! empty( $links ) ) : ?>
                    <ul class="udp-home-vida__links list-unstyled">
                        <?php foreach ( $links as $item ) : ?>
                            <?php if ( empty( $item['link_url'] ) ) continue; ?>
                            <li class="udp-home-vida__links-item">
                                <a
                                    href="<?php echo esc_url( $item['link_url'] ); ?>"
                                    class="udp-home-vida__link"
                                    <?php if ( ! empty( $item['link_externo'] ) ) : ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>
                                >
                                    <?php echo esc_html( $item['link_texto'] ); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <?php if ( $has_media ) : ?>
                <div class="col-lg-7 udp-home-vida__media">
                    <?php if ( $video_url ) : ?>
                        <video
                            class="udp-home-vida__video"
                            autoplay
                            muted
                            loop
                            playsinline
                            preload="none"
                        >
                            <source src="<?php echo esc_url( $video_url ); ?>" type="<?php echo esc_attr( $video['mime_type'] ?? 'video/mp4' ); ?>">
                        </video>
                    <?php elseif ( $img_url ) : ?>
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
