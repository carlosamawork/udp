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
 * JS: home-cifras.js — Swiper freeMode.
 *
 * @package starter-bs5
 */

$post_id = $args['post_id'] ?? (int) get_option( 'page_on_front' );

$bloques = get_field( 'cifras_items', $post_id );

if ( empty( $bloques ) ) {
    return;
}

$titulo_seccion = get_field( 'cifras_titulo', $post_id ) ?: 'Cifras';
?>
<section class="udp-home-cifras">
    <div class="container udp-home-cifras__wrap">
        <h2 class="udp-home__titulo"><?php echo esc_html( $titulo_seccion ); ?></h2>

        <div class="js-cifras-swiper swiper udp-home-cifras__swiper">
            <div class="swiper-wrapper">
                <?php foreach ( $bloques as $bloque ) : ?>
                    <div class="swiper-slide">

                        <?php if ( 'numero' === $bloque['acf_fc_layout'] ) : ?>

                            <div class="udp-home-cifras__card udp-home-cifras__card--numero">
                                <?php if ( ! empty( $bloque['cifra_numero'] ) ) : ?>
                                    <span class="udp-home-cifras__numero" aria-hidden="true">
                                        <?php echo esc_html( $bloque['cifra_numero'] ); ?>
                                    </span>
                                <?php endif; ?>
                                <div class="udp-home-cifras__info">
                                    <?php if ( ! empty( $bloque['cifra_titulo'] ) ) : ?>
                                        <p class="udp-home-cifras__titulo-numero">
                                            <?php echo esc_html( $bloque['cifra_titulo'] ); ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $bloque['cifra_subtitulo'] ) ) : ?>
                                        <p class="udp-home-cifras__subtitulo-numero">
                                            <?php echo esc_html( $bloque['cifra_subtitulo'] ); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>

                        <?php elseif ( 'testimonio' === $bloque['acf_fc_layout'] ) : ?>

                            <?php
                            $autor_nombre = $bloque['cifra_autor_nombre'] ?? '';
                            $autor_desc   = $bloque['cifra_autor_descripcion'] ?? '';
                            $autor_img    = $bloque['cifra_autor_imagen'] ?? [];
                            ?>
                            <div class="udp-home-cifras__card udp-home-cifras__card--cita">
                                <svg
                                    class="udp-home-cifras__quote-icon"
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="32" height="32" viewBox="0 0 24 24"
                                    fill="white" aria-hidden="true"
                                >
                                    <path d="M11.192 15.757c0-.88-.23-1.618-.69-2.217-.326-.412-.768-.683-1.327-.812-.55-.128-1.07-.137-1.54-.028-.16-.95.1-1.95.78-3 .53-.81 1.24-1.48 2.13-2.01L9.57 6c-.86.49-1.65 1.13-2.38 1.92-.73.79-1.3 1.67-1.73 2.63-.43.97-.65 1.96-.65 2.97 0 1.49.44 2.65 1.32 3.49.88.84 1.95 1.26 3.22 1.26 1.06 0 1.91-.35 2.56-1.05.65-.7.97-1.56.97-2.59zm8.818 0c0-.88-.23-1.618-.69-2.217-.326-.42-.77-.69-1.33-.82-.55-.13-1.07-.14-1.54-.029-.16-.95.1-1.95.78-3 .53-.81 1.24-1.48 2.13-2.01L18.388 6c-.86.49-1.65 1.13-2.38 1.92-.73.79-1.3 1.67-1.73 2.63-.43.97-.65 1.96-.65 2.97 0 1.49.44 2.65 1.32 3.49.88.84 1.95 1.26 3.22 1.26 1.06 0 1.91-.35 2.56-1.05.65-.7.97-1.56.97-2.59z"/>
                                </svg>

                                <div class="udp-home-cifras__cita-body">
                                    <?php if ( ! empty( $bloque['cifra_cita'] ) ) : ?>
                                        <div class="udp-home-cifras__cita-texto">
                                            <?php echo wp_kses_post( $bloque['cifra_cita'] ); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ( $autor_nombre || ! empty( $autor_img['url'] ) ) : ?>
                                        <div class="udp-home-cifras__autor">
                                            <?php if ( ! empty( $autor_img['url'] ) ) : ?>
                                                <img
                                                    src="<?php echo esc_url( $autor_img['url'] ); ?>"
                                                    alt="<?php echo esc_attr( $autor_img['alt'] ?? '' ); ?>"
                                                    class="udp-home-cifras__autor-img"
                                                    loading="lazy"
                                                    decoding="async"
                                                    width="40"
                                                    height="40"
                                                >
                                            <?php endif; ?>
                                            <div class="udp-home-cifras__autor-info">
                                                <?php if ( $autor_nombre ) : ?>
                                                    <p class="udp-home-cifras__autor-nombre">
                                                        <?php echo esc_html( $autor_nombre ); ?>
                                                    </p>
                                                <?php endif; ?>
                                                <?php if ( $autor_desc ) : ?>
                                                    <p class="udp-home-cifras__autor-desc">
                                                        <?php echo esc_html( $autor_desc ); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
