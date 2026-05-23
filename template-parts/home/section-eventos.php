<?php
/**
 * Home — Sección 5: Próximos Eventos
 *
 * 2 cards grandes destacadas + tabla de hasta 5 eventos adicionales.
 * Usa udp_query_agenda con fecha_desde=hoy, order=ASC, limit=7.
 * Card shape: href, titulo, imagen['url'], imagen['alt'],
 *             fecha (Y-m-d ISO), fecha_display, hora_display, lugar.
 *
 * @package starter-bs5
 */

$hoy    = current_time( 'Ymd' );
$result = udp_query_agenda( [
    'fecha_desde' => $hoy,
    'order'       => 'ASC',
    'limit'       => 7,
] );

$cards = $result['cards'] ?? [];

if ( empty( $cards ) ) {
    return;
}

$titulo_seccion = get_field( 'eventos_titulo' ) ?: 'Próximos eventos';

$destacados = array_slice( $cards, 0, 2 );
$lista      = array_slice( $cards, 2, 5 );
?>
<section class="udp-home-eventos">
    <div class="container">
        <div class="udp-home-eventos__header">
            <h2 class="udp-home__titulo"><?php echo esc_html( $titulo_seccion ); ?></h2>
            <a href="<?php echo esc_url( home_url( '/agenda/' ) ); ?>" class="udp-home-eventos__ver-mas">
                Ver agenda completa
            </a>
        </div>

        <?php if ( $destacados ) : ?>
            <div class="udp-home-eventos__destacados row g-4">
                <?php foreach ( $destacados as $card ) : ?>
                    <div class="col-md-6">
                        <a href="<?php echo esc_url( $card['href'] ); ?>" class="udp-home-eventos__card">
                            <?php if ( ! empty( $card['imagen']['url'] ) ) : ?>
                                <div class="udp-home-eventos__card-img">
                                    <img
                                        src="<?php echo esc_url( $card['imagen']['url'] ); ?>"
                                        alt="<?php echo esc_attr( $card['imagen']['alt'] ?? '' ); ?>"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                </div>
                            <?php endif; ?>
                            <div class="udp-home-eventos__card-body">
                                <?php if ( ! empty( $card['fecha_display'] ) ) : ?>
                                    <time class="udp-home-eventos__card-fecha" datetime="<?php echo esc_attr( $card['fecha'] ); ?>">
                                        <?php echo esc_html( $card['fecha_display'] ); ?>
                                    </time>
                                <?php endif; ?>
                                <h3 class="udp-home-eventos__card-titulo"><?php echo esc_html( $card['titulo'] ); ?></h3>
                                <?php if ( ! empty( $card['lugar'] ) ) : ?>
                                    <span class="udp-home-eventos__card-lugar"><?php echo esc_html( $card['lugar'] ); ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ( $lista ) : ?>
            <table class="udp-home-eventos__tabla">
                <caption class="visually-hidden">Próximos eventos adicionales</caption>
                <tbody>
                    <?php foreach ( $lista as $card ) : ?>
                        <tr>
                            <td class="udp-home-eventos__tabla-fecha">
                                <time datetime="<?php echo esc_attr( $card['fecha'] ); ?>">
                                    <?php echo esc_html( $card['fecha_display'] ?? '' ); ?>
                                </time>
                            </td>
                            <td class="udp-home-eventos__tabla-titulo">
                                <a href="<?php echo esc_url( $card['href'] ); ?>"><?php echo esc_html( $card['titulo'] ); ?></a>
                            </td>
                            <?php if ( ! empty( $card['lugar'] ) ) : ?>
                                <td class="udp-home-eventos__tabla-lugar">
                                    <?php echo esc_html( $card['lugar'] ); ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>
