<?php
/**
 * Home — Sección 11: Cifras
 *
 * ACF field: cifras_items (flexible_content).
 * Layouts:
 *   - numero:     cifra_numero, cifra_titulo, cifra_subtitulo
 *   - testimonio: cifra_cita (wysiwyg), cifra_autor_nombre,
 *                 cifra_autor_descripcion, cifra_autor_imagen (array)
 *
 * @package starter-bs5
 */

$bloques = get_field( 'cifras_items' );

if ( empty( $bloques ) ) {
    return;
}

// Separar números de testimonios para layouts distintos.
$numeros     = array_filter( $bloques, fn( $b ) => $b['acf_fc_layout'] === 'numero' );
$testimonios = array_filter( $bloques, fn( $b ) => $b['acf_fc_layout'] === 'testimonio' );
?>
<section class="udp-home-cifras">
    <div class="container">

        <?php if ( ! empty( $numeros ) ) : ?>
            <ul class="udp-home-cifras__grid list-unstyled row g-4" role="list">
                <?php foreach ( $numeros as $bloque ) : ?>
                    <li class="col-6 col-md-4 col-lg-3 udp-home-cifras__item">
                        <?php if ( ! empty( $bloque['cifra_numero'] ) ) : ?>
                            <span class="udp-home-cifras__numero" aria-hidden="true">
                                <?php echo esc_html( $bloque['cifra_numero'] ); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ( ! empty( $bloque['cifra_titulo'] ) ) : ?>
                            <span class="udp-home-cifras__titulo">
                                <?php echo esc_html( $bloque['cifra_titulo'] ); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ( ! empty( $bloque['cifra_subtitulo'] ) ) : ?>
                            <span class="udp-home-cifras__subtitulo">
                                <?php echo esc_html( $bloque['cifra_subtitulo'] ); ?>
                            </span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ( ! empty( $testimonios ) ) : ?>
            <div class="udp-home-cifras__testimonios row g-4 <?php echo ! empty( $numeros ) ? 'mt-5' : ''; ?>">
                <?php foreach ( $testimonios as $bloque ) : ?>
                    <div class="col-md-6 udp-home-cifras__testimonio">
                        <?php if ( ! empty( $bloque['cifra_cita'] ) ) : ?>
                            <blockquote class="udp-home-cifras__cita">
                                <?php echo wp_kses_post( $bloque['cifra_cita'] ); ?>
                            </blockquote>
                        <?php endif; ?>
                        <?php
                        $autor_nombre = $bloque['cifra_autor_nombre'] ?? '';
                        $autor_desc   = $bloque['cifra_autor_descripcion'] ?? '';
                        $autor_img    = $bloque['cifra_autor_imagen'] ?? [];
                        if ( $autor_nombre || $autor_img ) :
                        ?>
                            <footer class="udp-home-cifras__autor">
                                <?php if ( ! empty( $autor_img['url'] ) ) : ?>
                                    <img
                                        src="<?php echo esc_url( $autor_img['url'] ); ?>"
                                        alt="<?php echo esc_attr( $autor_img['alt'] ?? '' ); ?>"
                                        class="udp-home-cifras__autor-img"
                                        loading="lazy"
                                        decoding="async"
                                        width="<?php echo esc_attr( $autor_img['width'] ?? '' ); ?>"
                                        height="<?php echo esc_attr( $autor_img['height'] ?? '' ); ?>"
                                    >
                                <?php endif; ?>
                                <div class="udp-home-cifras__autor-info">
                                    <?php if ( $autor_nombre ) : ?>
                                        <p class="udp-home-cifras__autor-nombre"><?php echo esc_html( $autor_nombre ); ?></p>
                                    <?php endif; ?>
                                    <?php if ( $autor_desc ) : ?>
                                        <p class="udp-home-cifras__autor-desc"><?php echo esc_html( $autor_desc ); ?></p>
                                    <?php endif; ?>
                                </div>
                            </footer>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</section>
