<?php
/**
 * Single Evento (CPT agenda)
 *
 * Light theme. Layout 2-col: sidebar meta + main content.
 * Reusa post-share partial (igual que single-post).
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
    the_post();
    ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-single-event' ); ?>>

        <header class="udp-single-event__header">
            <?php
            $archive_url = get_permalink( 91 );
            if ( ! $archive_url ) {
                $archive_url = home_url( '/agenda-udp/' );
            }
            ?>
            <a class="udp-single-event__back" href="<?php echo esc_url( $archive_url ); ?>">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                    <path d="M9 3L5 7l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <?php esc_html_e( 'Volver a Eventos', 'starter-theme' ); ?>
            </a>
            <h1 class="udp-single-event__title"><?php the_title(); ?></h1>
        </header>

        <hr class="udp-single-event__separator" aria-hidden="true" />

        <div class="udp-single-event__body">

            <aside class="udp-single-event__sidebar">
                <?php get_template_part( 'template-parts/single/event-meta', null, array( 'post_id' => get_the_ID() ) ); ?>
            </aside>

            <div class="udp-single-event__content">
                <?php if ( has_post_thumbnail() ) : ?>
                    <figure class="udp-single-event__featured">
                        <?php the_post_thumbnail( 'large' ); ?>
                    </figure>
                <?php endif; ?>

                <?php
                $subtitulo = function_exists( 'get_field' ) ? (string) get_field( 'subtitulo' ) : '';
                if ( $subtitulo ) :
                ?>
                    <p class="udp-single-event__subtitulo"><?php echo esc_html( $subtitulo ); ?></p>
                <?php endif; ?>

                <div class="udp-single-event__entry-content">
                    <?php the_content(); ?>
                </div>
            </div>

        </div>

        <?php get_template_part( 'template-parts/single/post-share', null, array( 'post_id' => get_the_ID() ) ); ?>

        <?php get_template_part( 'template-parts/single/event-related', null, array( 'post_id' => get_the_ID() ) ); ?>

    </article>

    <?php
endwhile;

get_footer();
