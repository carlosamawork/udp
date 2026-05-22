<?php
/**
 * Home — Sección 3: Noticias
 *
 * Usa udp_query_noticias(['limit'=>8]).
 * Card shape: href, titulo, imagen['url'], imagen['alt'], eyebrow, fecha (Y-m-d ISO).
 *
 * @package starter-bs5
 */

$result = udp_query_noticias( [ 'limit' => 8 ] );
$cards  = $result['cards'] ?? [];

if ( empty( $cards ) ) {
    return;
}

$titulo_seccion = get_field( 'noticias_titulo' ) ?: 'Noticias';
?>
<section class="udp-home-noticias">
    <div class="container">
        <div class="udp-home-noticias__header">
            <h2 class="udp-home-noticias__titulo"><?php echo esc_html( $titulo_seccion ); ?></h2>
            <a href="<?php echo esc_url( home_url( '/noticias/' ) ); ?>" class="udp-home-noticias__ver-mas">
                Ver todas
            </a>
        </div>
    </div>

    <div class="udp-home-noticias__swiper-wrap">
        <div class="swiper js-home-noticias-swiper">
            <div class="swiper-wrapper">
                <?php foreach ( $cards as $card ) : ?>
                    <article class="swiper-slide udp-home-noticias__slide">
                        <a href="<?php echo esc_url( $card['href'] ); ?>" class="udp-home-noticias__card">
                            <?php if ( ! empty( $card['imagen']['url'] ) ) : ?>
                                <div class="udp-home-noticias__card-img">
                                    <img
                                        src="<?php echo esc_url( $card['imagen']['url'] ); ?>"
                                        alt="<?php echo esc_attr( $card['imagen']['alt'] ?? '' ); ?>"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                </div>
                            <?php endif; ?>

                            <div class="udp-home-noticias__card-body">
                                <?php if ( ! empty( $card['eyebrow'] ) ) : ?>
                                    <span class="eyebrow udp-home-noticias__card-eyebrow">
                                        <?php echo esc_html( $card['eyebrow'] ); ?>
                                    </span>
                                <?php endif; ?>
                                <h3 class="udp-home-noticias__card-titulo"><?php echo esc_html( $card['titulo'] ); ?></h3>
                                <?php if ( ! empty( $card['fecha'] ) ) : ?>
                                    <time
                                        class="udp-home-noticias__card-fecha"
                                        datetime="<?php echo esc_attr( $card['fecha'] ); ?>"
                                    >
                                        <?php echo esc_html( date_i18n( 'j \d\e F \d\e Y', strtotime( $card['fecha'] ) ) ); ?>
                                    </time>
                                <?php endif; ?>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>

            <button class="swiper-button-prev" aria-label="Anterior noticia"></button>
            <button class="swiper-button-next" aria-label="Siguiente noticia"></button>
        </div>
    </div>
</section>
