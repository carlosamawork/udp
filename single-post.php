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
        ?>

        <div class="udp-single-post__body">
            <div class="udp-single-post__content">
                <?php the_content(); ?>
            </div>
        </div>

        <?php
        get_template_part( 'template-parts/single/post-related', null, array( 'post_id' => get_the_ID() ) );
        ?>

    </article>

    <?php
endwhile;

get_footer();
