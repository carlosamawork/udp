<?php
/**
 * Home — Sección 4: Facultades
 *
 * Marquee de nombres de facultades (CSS loop, doble copia) + lista de links.
 * Data: términos de taxonomía 'facultad' (nombre, color). Links resueltos a las
 * páginas de WordPress cuyo post_name coincida con el slug del término (independiente
 * de la jerarquía de URLs). Fallback a get_term_link() si no existe la página.
 *
 * @package starter-bs5
 */

$post_id = $args['post_id'] ?? (int) get_option( 'page_on_front' );

$facultades = get_terms( [
    'taxonomy'   => 'facultad',
    'hide_empty' => false,
    'orderby'    => 'name',
    'order'      => 'ASC',
] );

if ( is_wp_error( $facultades ) || empty( $facultades ) ) {
    return;
}

// Pre-fetch páginas cuyo slug coincide con el slug de algún término de facultad.
// Se busca por post_name directamente (sin depender de la jerarquía de URLs).
$term_slugs   = wp_list_pluck( $facultades, 'slug' );
$pages        = get_posts( [
    'post_type'      => 'page',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'no_found_rows'  => true,
    'post_name__in'  => $term_slugs,
    'fields'         => 'ids',
] );
$urls_by_slug = [];
foreach ( $pages as $page_id ) {
    $slug                  = get_post_field( 'post_name', $page_id );
    $urls_by_slug[ $slug ] = get_permalink( $page_id );
}

$titulo_seccion = get_field( 'facultades_titulo', $post_id ) ?: 'Facultades';
?>
<section class="udp-home-facultades">
    <div class="container">
        <h2 class="udp-home__titulo"><?php echo esc_html( $titulo_seccion ); ?></h2>
    </div>
    <div class="container">
        <nav class="udp-home-facultades__nav" aria-label="Facultades UDP">
            <ul class="udp-home-facultades__lista list-unstyled">
                <?php foreach ( $facultades as $fac ) : ?>
                    <?php
                    $color = function_exists( 'get_field' ) ? (string) get_field( 'color', $fac ) : '';
                    if ( isset( $urls_by_slug[ $fac->slug ] ) ) {
                        $url = $urls_by_slug[ $fac->slug ];
                    } else {
                        $term_link = get_term_link( $fac );
                        $url       = is_wp_error( $term_link ) ? home_url( '/' ) : $term_link;
                    }
                    ?>
                    <li>
                        <a
                            href="<?php echo esc_url( $url ); ?>"
                            class="udp-home-facultades__link"
                            style="color: <?php echo esc_attr( $color ?: 'white' ); ?>"
                        >
                            <?php echo esc_html( $fac->name ); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </div>
</section>
