<?php
/**
 * Single Post > Hero
 *
 * Back link + título + meta (fecha + eyebrow categoría) + featured image.
 *
 * @package Starter_Theme
 *
 * @var array $args ['post_id' => int]
 */
$post_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();
if ( ! $post_id ) {
    return;
}

$archive_url = get_permalink( get_page_by_path( 'noticias' ) );
if ( ! $archive_url ) {
    $archive_url = home_url( '/noticias/' );
}

$fecha_iso     = get_the_date( 'Y-m-d', $post_id );
$fecha_display = function_exists( 'udp_card_format_date' ) ? udp_card_format_date( $fecha_iso ) : $fecha_iso;

$terms = get_the_terms( $post_id, 'category' );
$primary_term_name = '';
if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
    $primary_term_name = $terms[0]->name;
}

$thumb_id = get_post_thumbnail_id( $post_id );
$thumb_url = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'large' ) : '';
$thumb_alt = $thumb_id ? (string) get_post_meta( $thumb_id, '_wp_attachment_image_alt', true ) : '';
?>
<header class="udp-single-post__hero">
    <div class="udp-single-post__hero-inner">

        <a class="udp-single-post__back" href="<?php echo esc_url( $archive_url ); ?>">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                <path d="M9 3L5 7l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <?php esc_html_e( 'Volver a Noticias', 'starter-theme' ); ?>
        </a>

        <h1 class="udp-single-post__title"><?php the_title(); ?></h1>

        <div class="udp-single-post__meta">
            <span class="udp-single-post__meta-label"><?php esc_html_e( 'Fecha', 'starter-theme' ); ?></span>
            <time class="udp-single-post__date" datetime="<?php echo esc_attr( $fecha_iso ); ?>"><?php echo esc_html( $fecha_display ); ?></time>
            <?php if ( $primary_term_name ) : ?>
                <span class="udp-card-noticia__eyebrow udp-card-noticia__eyebrow--yellow"><?php echo esc_html( $primary_term_name ); ?></span>
            <?php endif; ?>
        </div>

        <?php if ( $thumb_url ) : ?>
            <figure class="udp-single-post__featured">
                <img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $thumb_alt ); ?>" />
            </figure>
        <?php endif; ?>

    </div>
</header>
