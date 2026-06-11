<?php
/**
 * Single Post (Noticia)
 *
 * Hero light con back link + título + meta + featured image.
 * Body con post_content. Share floating sticky derecha. Related
 * posts (3 cards) al final.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
    the_post();
    ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-single-post' ); ?>>

        <?php
        get_template_part( 'template-parts/single/post-hero', null, array( 'post_id' => get_the_ID() ) );
        get_template_part( 'template-parts/single/post-share', null, array( 'post_id' => get_the_ID() ) );
        get_template_part( 'template-parts/single/post-mobile-bar', null, array( 'post_id' => get_the_ID() ) );
        ?>

        <div class="udp-single-post__body">
            <div class="udp-single-post__body-grid">
                <aside class="udp-single-post__aside">
                    <?php get_template_part( 'template-parts/single/post-meta', null, array( 'post_id' => get_the_ID() ) ); ?>
                </aside>
                <div class="udp-single-post__main">
                    <?php
                    $thumb_id  = get_post_thumbnail_id( get_the_ID() );
                    $thumb_url = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'large' ) : '';
                    $thumb_alt = $thumb_id ? (string) get_post_meta( $thumb_id, '_wp_attachment_image_alt', true ) : '';
                    $thumb_cap = $thumb_id ? wp_get_attachment_caption( $thumb_id ) : '';
                    if ( $thumb_url ) :
                        ?>
                        <figure class="udp-single-post__featured">
                            <img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $thumb_alt ); ?>" />
                            <?php if ( $thumb_cap ) : ?>
                                <figcaption class="udp-single-post__featured-caption"><?php echo esc_html( $thumb_cap ); ?></figcaption>
                            <?php endif; ?>
                        </figure>
                    <?php endif; ?>
                    <div class="udp-single-post__content">
                        <?php the_content(); ?>
                    </div>
                </div>
            </div>
        </div>

        <?php get_template_part( 'template-parts/single/post-gallery', null, array( 'post_id' => get_the_ID() ) ); ?>

        <?php
        get_template_part( 'template-parts/single/post-related', null, array( 'post_id' => get_the_ID() ) );
        ?>

    </article>

    <?php
endwhile;

get_footer();
