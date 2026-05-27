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
        <h2 class="udp-home-cifras__titulo udp-home__titulo"><?php echo esc_html( $titulo_seccion ); ?></h2>

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
                                <svg class="udp-home-cifras__quote-icon" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="white" aria-hidden="true">
                                    <path d="M13.7779 2.3771V7.57257C13.7779 8.27719 13.1909 8.84811 12.4667 8.84811C9.88286 8.84811 8.47737 11.4266 8.28257 16.5156H12.4667C13.1909 16.5156 13.7779 17.0871 13.7779 17.7914L13.7779 28.7614C13.7779 29.4657 13.1909 30.0367 12.4667 30.0367H1.31097C0.586505 30.0367 0 29.4652 0 28.7614L0 17.7914C0 15.3518 0.252459 13.1131 0.750136 11.1367C1.26055 9.11016 2.04406 7.33826 3.07819 5.87006C4.14229 4.36119 5.47334 3.17716 7.03467 2.35231C8.60653 1.52246 10.4344 1.10156 12.4671 1.10156C13.1909 1.10156 13.7779 1.67248 13.7779 2.3771ZM30.6887 8.8482C31.4125 8.8482 32 8.27674 32 7.57265V2.37719C32 1.67257 31.4126 1.10165 30.6887 1.10165C28.6568 1.10165 26.8286 1.52264 25.2573 2.3524C23.6957 3.17725 22.3639 4.36128 21.2996 5.87015C20.2659 7.33835 19.4824 9.11033 18.9719 11.1373C18.4744 13.1144 18.222 15.3531 18.222 17.7915V28.7615C18.222 29.4657 18.8092 30.0368 19.5332 30.0368H30.6886C31.4124 30.0368 31.9994 29.4653 31.9994 28.7615V17.7914C31.9994 17.0872 31.4125 16.5156 30.6886 16.5156H26.5641C26.756 11.4267 28.141 8.8482 30.6887 8.8482Z" fill="white"/>
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
