<?php
/**
 * Single Carrera (CPT carrera-udp)
 *
 * Light theme. Layout 2-col: sidebar meta + content.
 * Sidebar: facultad eyebrow + atributos repeater + 2 buttons (admisión + facultad).
 * Content: featured + post_content + links repeater al final.
 * Reusa post-share.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
    the_post();
    ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-single-carrera' ); ?>>

        <header class="udp-single-carrera__header">
            <?php
            $archive_url = get_permalink( 12 );
            if ( ! $archive_url ) {
                $archive_url = home_url( '/carreras/' );
            }
            ?>
            <a class="udp-single-carrera__back" href="<?php echo esc_url( $archive_url ); ?>">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                    <path d="M9 3L5 7l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <?php esc_html_e( 'Volver a Carreras', 'starter-theme' ); ?>
            </a>
            <h1 class="udp-single-carrera__title"><?php the_title(); ?></h1>
        </header>

        <hr class="udp-single-carrera__separator" aria-hidden="true" />

        <div class="udp-single-carrera__body">

            <aside class="udp-single-carrera__sidebar">
                <?php get_template_part( 'template-parts/single/carrera-meta', null, array( 'post_id' => get_the_ID() ) ); ?>
            </aside>

            <div class="udp-single-carrera__content">
                <?php if ( has_post_thumbnail() ) : ?>
                    <figure class="udp-single-carrera__featured">
                        <?php the_post_thumbnail( 'large' ); ?>
                    </figure>
                <?php endif; ?>

                <div class="udp-single-carrera__entry-content">
                    <?php the_content(); ?>
                </div>

                <?php get_template_part( 'template-parts/single/carrera-links', null, array( 'post_id' => get_the_ID() ) ); ?>
            </div>

        </div>

        <?php get_template_part( 'template-parts/single/post-share', null, array( 'post_id' => get_the_ID() ) ); ?>

    </article>

    <?php
endwhile;

get_footer();
