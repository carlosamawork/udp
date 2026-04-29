<?php
/**
 * Single Concurso Académico
 *
 * Light theme. Layout 2-col sidebar meta + content con featured image,
 * caption (post_excerpt), body, y 2 buttons de descarga.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
    the_post();
    ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-single-concurso' ); ?>>

        <header class="udp-single-concurso__header">
            <?php
            $archive_url = get_permalink( 76 );
            if ( ! $archive_url ) {
                $archive_url = home_url( '/concursos-academicos/' );
            }
            ?>
            <a class="udp-single-concurso__back" href="<?php echo esc_url( $archive_url ); ?>">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                    <path d="M9 3L5 7l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <?php esc_html_e( 'Volver a Concursos académicos', 'starter-theme' ); ?>
            </a>
            <h1 class="udp-single-concurso__title"><?php the_title(); ?></h1>
        </header>

        <hr class="udp-single-concurso__separator" aria-hidden="true" />

        <div class="udp-single-concurso__body">

            <aside class="udp-single-concurso__sidebar">
                <?php get_template_part( 'template-parts/single/concurso-meta', null, array( 'post_id' => get_the_ID() ) ); ?>
            </aside>

            <div class="udp-single-concurso__content">
                <?php if ( has_post_thumbnail() ) : ?>
                    <figure class="udp-single-concurso__featured">
                        <?php the_post_thumbnail( 'large' ); ?>
                    </figure>
                <?php endif; ?>

                <?php $caption = get_the_excerpt(); ?>
                <?php if ( $caption ) : ?>
                    <p class="udp-single-concurso__caption"><?php echo esc_html( $caption ); ?></p>
                <?php endif; ?>

                <div class="udp-single-concurso__entry-content">
                    <?php the_content(); ?>
                </div>

                <?php get_template_part( 'template-parts/single/concurso-files', null, array( 'post_id' => get_the_ID() ) ); ?>
            </div>

        </div>

        <?php get_template_part( 'template-parts/single/post-share', null, array( 'post_id' => get_the_ID() ) ); ?>

    </article>

    <?php
endwhile;

get_footer();
