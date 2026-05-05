<?php
/**
 * Single Centro (CPT centro-udp)
 *
 * Light theme, layout simple: featured + content + button externo si existe.
 * Reusa post-share (igual que single-post).
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
    the_post();
    $link_externo = (string) get_post_meta( get_the_ID(), 'link_externo', true );
    $archive_url  = get_permalink( 16 );
    if ( ! $archive_url ) {
        $archive_url = home_url( '/centros-interdisciplinarios/' );
    }
    ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-single-centro' ); ?>>

        <header class="udp-single-centro__header">
            <a class="udp-single-centro__back" href="<?php echo esc_url( $archive_url ); ?>">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                    <path d="M9 3L5 7l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <?php esc_html_e( 'Volver a Centros', 'starter-theme' ); ?>
            </a>
            <h1 class="udp-single-centro__title"><?php the_title(); ?></h1>
        </header>

        <hr class="udp-single-centro__separator" aria-hidden="true" />

        <div class="udp-single-centro__body">
            <?php if ( has_post_thumbnail() ) : ?>
                <figure class="udp-single-centro__featured">
                    <?php the_post_thumbnail( 'large' ); ?>
                </figure>
            <?php endif; ?>

            <div class="udp-single-centro__content">
                <?php the_content(); ?>
            </div>

            <?php if ( $link_externo ) : ?>
                <div class="udp-single-centro__actions">
                    <a class="udp-single-centro__btn" href="<?php echo esc_url( $link_externo ); ?>" target="_blank" rel="noopener noreferrer">
                        <?php esc_html_e( 'Visitar sitio del centro', 'starter-theme' ); ?>
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                            <path d="M3 3h6v6M9 3 3 9" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php get_template_part( 'template-parts/single/post-share', null, array( 'post_id' => get_the_ID() ) ); ?>

    </article>

    <?php
endwhile;

get_footer();
