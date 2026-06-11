<?php
/**
 * Single Post > Hero
 *
 * Back link + título (ancho completo del contenedor). La meta (fecha +
 * categoría) y la imagen destacada se renderizan en el cuerpo:
 * meta = columna izquierda (post-meta), imagen = inicio de la columna
 * de contenido. En mobile todo se apila.
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

    </div>
</header>
