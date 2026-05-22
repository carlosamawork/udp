<?php
/**
 * Home — Sección 4: Facultades
 *
 * Marquee de nombres de facultades (CSS loop, doble copia) + lista de links.
 * Data: get_terms('facultad').
 *
 * @package starter-bs5
 */

$facultades = get_terms( [
    'taxonomy'   => 'facultad',
    'hide_empty' => false,
    'orderby'    => 'name',
    'order'      => 'ASC',
] );

if ( is_wp_error( $facultades ) || empty( $facultades ) ) {
    return;
}

$titulo_seccion = get_field( 'facultades_titulo' ) ?: 'Facultades';
?>
<section class="udp-home-facultades">
    <div class="container">
        <h2 class="udp-home-facultades__titulo"><?php echo esc_html( $titulo_seccion ); ?></h2>
    </div>
    <div class="udp-home-facultades__marquee" aria-hidden="true">
        <div class="udp-home-facultades__track">
            <?php foreach ( $facultades as $fac ) : ?>
                <span class="udp-home-facultades__marquee-item"><?php echo esc_html( $fac->name ); ?></span>
            <?php endforeach; ?>
            <?php // Segunda copia para loop infinito sin salto visual ?>
            <?php foreach ( $facultades as $fac ) : ?>
                <span class="udp-home-facultades__marquee-item" aria-hidden="true"><?php echo esc_html( $fac->name ); ?></span>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="container">
        <nav class="udp-home-facultades__nav" aria-label="Facultades UDP">
            <ul class="udp-home-facultades__lista list-unstyled">
                <?php foreach ( $facultades as $fac ) : ?>
                    <?php $url = get_term_link( $fac ); ?>
                    <li>
                        <a
                            href="<?php echo esc_url( is_wp_error( $url ) ? home_url( '/' ) : $url ); ?>"
                            class="udp-home-facultades__link"
                        >
                            <?php echo esc_html( $fac->name ); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </div>
</section>
