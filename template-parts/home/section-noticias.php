<?php
/**
 * Home — Sección 3: Noticias
 *
 * 1 card destacada (full height 580px) + pares de cards regulares (275px × 2).
 * Swiper horizontal, nav custom en header.
 *
 * @package starter-bs5
 */

$result   = udp_query_noticias( [ 'limit' => 9 ] );
$cards    = $result['cards'] ?? [];

if ( empty( $cards ) ) {
    return;
}

$titulo_seccion = get_field( 'noticias_titulo' ) ?: 'UDP hoy / Noticias';
$noticias_url   = home_url( '/noticias/' );

$featured = array_shift( $cards );
$pares    = array_chunk( $cards, 2 );
?>
<section class="udp-home-noticias">

    <div class="udp-home-noticias__header">
        <h2 class="udp-home__titulo udp-home-noticias__title"><?php echo esc_html( $titulo_seccion ); ?></h2>
        <div class="udp-home-noticias__nav">
            <button class="udp-home-noticias__nav-btn udp-home-noticias__nav-btn--prev js-noticias-prev" aria-label="Anterior noticia">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                    <path d="M10 3 5 8l5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            <button class="udp-home-noticias__nav-btn js-noticias-next" aria-label="Siguiente noticia">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                    <path d="M6 3l5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
    </div>

    <div class="udp-home-noticias__swiper-wrap">
        <div class="swiper js-home-noticias-swiper">
            <div class="swiper-wrapper">

                <div class="swiper-slide udp-home-noticias__slide--featured">
                    <div class="udp-home-noticias__featured">
                        <div class="udp-home-noticias__featured-img">
                            <span class="udp-home-noticias__badge">Destacado</span>
                            <?php if ( ! empty( $featured['fecha'] ) ) : ?>
                                <time class="udp-home-noticias__featured-date" datetime="<?php echo esc_attr( $featured['fecha'] ); ?>">
                                    <?php echo esc_html( date_i18n( 'd / m / Y', strtotime( $featured['fecha'] ) ) ); ?>
                                </time>
                            <?php endif; ?>
                            <a href="<?php echo esc_url( $featured['href'] ); ?>">
                                <img
                                    src="<?php echo esc_url( $featured['imagen']['url'] ); ?>"
                                    alt="<?php echo esc_attr( $featured['imagen']['alt'] ?? '' ); ?>"
                                    loading="eager"
                                    decoding="async"
                                >
                            </a>
                            <div class="udp-home-noticias__featured-overlay">
                                <h3 class="udp-home-noticias__featured-overlay-title">
                                    <?php echo esc_html( $featured['titulo'] ); ?>
                                </h3>
                            </div>
                        </div>
                        <div class="udp-home-noticias__featured-body">
                            <p class="udp-home-noticias__featured-text"><?php echo esc_html( $featured['titulo'] ); ?></p>
                            <a href="<?php echo esc_url( $featured['href'] ); ?>" class="udp-home-noticias__leer-mas">
                                Leer más
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                    <path d="M4 12 12 4M12 4H6M12 4v6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                <?php foreach ( $pares as $par ) : ?>
                    <div class="swiper-slide udp-home-noticias__slide--pair">
                        <?php foreach ( $par as $card ) : ?>
                            <div class="udp-home-noticias__card">
                                <div class="udp-home-noticias__card-img">
                                    <a href="<?php echo esc_url( $featured['href'] ); ?>">
                                        <img
                                            src="<?php echo esc_url( $card['imagen']['url'] ); ?>"
                                            alt="<?php echo esc_attr( $card['imagen']['alt'] ?? '' ); ?>"
                                            loading="lazy"
                                            decoding="async"
                                        >
                                    </a>
                                </div>
                                <div class="udp-home-noticias__card-body">
                                    <div class="udp-home-noticias__card-text">
                                        <h3 class="udp-home-noticias__card-titulo"><?php echo esc_html( $card['titulo'] ); ?></h3>
                                        <?php if ( ! empty( $card['fecha'] ) ) : ?>
                                            <time class="udp-home-noticias__card-fecha" datetime="<?php echo esc_attr( $card['fecha'] ); ?>">
                                                <?php echo esc_html( date_i18n( 'd / m / Y', strtotime( $card['fecha'] ) ) ); ?>
                                            </time>
                                        <?php endif; ?>
                                    </div>
                                    <a href="<?php echo esc_url( $card['href'] ); ?>" class="udp-home-noticias__leer-mas">
                                        Leer más
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                            <path d="M4 12 12 4M12 4H6M12 4v6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

            </div>
        </div>
    </div>

    <div class="udp-home-noticias__ver-todas">
        <a href="<?php echo esc_url( $noticias_url ); ?>" class="udp-home-noticias__ver-todas-btn">
            Ver todas las noticias
        </a>
    </div>

</section>
