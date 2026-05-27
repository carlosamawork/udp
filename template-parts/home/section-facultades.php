<?php
/**
 * Home — Sección 4: Facultades
 *
 * Marquee de nombres de facultades (CSS loop, doble copia) + lista de links.
 * Data: get_terms('facultad').
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
                        $url = get_term_link( $fac ); 
                        $color  = function_exists( 'get_field' ) ? (string) get_field( 'color', $fac ) : 'white';
                    ?>
                    <li>
                        <a
                            href="<?php echo esc_url( is_wp_error( $url ) ? home_url( '/' ) : $url ); ?>"
                            class="udp-home-facultades__link"
                            style="color: <?php echo $color ? $color : 'white' ?>"
                        >
                            <?php echo esc_html( $fac->name ); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </div>
</section>
