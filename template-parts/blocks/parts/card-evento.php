<?php
/**
 * Card primitive — Evento
 *
 * Soporta 2 modos: 'grid' (image-left + body con CTA circular) y
 * 'list' (row sin imagen, eyebrow + title + date columns).
 *
 * @package Starter_Theme
 *
 * @var array $args ['card' => array, 'theme' => 'dark'|'light', 'mode' => 'grid'|'list']
 */
$card  = isset( $args['card'] ) && is_array( $args['card'] ) ? $args['card'] : array();
$theme = isset( $args['theme'] ) && in_array( $args['theme'], array( 'dark', 'light' ), true ) ? $args['theme'] : 'dark';
$mode  = isset( $args['mode'] )  && in_array( $args['mode'],  array( 'grid', 'list' ), true ) ? $args['mode']  : 'grid';

$href      = $card['href'] ?? '';
$titulo    = $card['titulo'] ?? '';
$imagen    = is_array( $card['imagen'] ?? null ) ? $card['imagen'] : array();
$eyebrow   = $card['eyebrow'] ?? '';
$fecha_iso = $card['fecha'] ?? '';
$fecha_d   = $card['fecha_display'] ?? '';
$hora_d    = $card['hora_display'] ?? '';
$lugar     = $card['lugar'] ?? '';

if ( empty( $href ) || empty( $titulo ) ) {
    return;
}
if ( $mode === 'grid' && empty( $imagen['url'] ?? '' ) ) {
    return;
}

$class = 'udp-card-evento udp-card-evento--' . $mode . ' udp-card-evento--' . $theme;
$datetime_combined = trim( $fecha_d . ( $hora_d ? ', ' . $hora_d : '' ) );
?>
<?php if ( $mode === 'list' ) : ?>
<a href="<?php echo esc_url( $href ); ?>" class="<?php echo esc_attr( $class ); ?>">
    <?php if ( $eyebrow ) : ?>
        <span class="udp-card-evento__eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
    <?php endif; ?>
    <h3 class="udp-card-evento__title"><?php echo esc_html( $titulo ); ?></h3>
    <?php if ( $fecha_d ) : ?>
        <time class="udp-card-evento__date" datetime="<?php echo esc_attr( $fecha_iso ); ?>"><?php echo esc_html( $fecha_d ); ?></time>
    <?php endif; ?>
</a>
<?php else : ?>
<a href="<?php echo esc_url( $href ); ?>" class="<?php echo esc_attr( $class ); ?>">
    <figure class="udp-card-evento__media">
        <img
            src="<?php echo esc_url( $imagen['url'] ); ?>"
            alt="<?php echo esc_attr( $imagen['alt'] ?? '' ); ?>"
            loading="lazy"
            decoding="async"
        />
    </figure>
    <div class="udp-card-evento__body">
        <h3 class="udp-card-evento__title"><?php echo esc_html( $titulo ); ?></h3>
        <?php if ( $eyebrow ) : ?>
            <p class="udp-card-evento__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
        <?php endif; ?>
        <?php if ( $datetime_combined ) : ?>
            <p class="udp-card-evento__datetime"><?php echo esc_html( $datetime_combined ); ?></p>
        <?php endif; ?>
        <?php if ( $lugar ) : ?>
            <p class="udp-card-evento__lugar"><?php echo esc_html( $lugar ); ?></p>
        <?php endif; ?>
        <span class="udp-card-evento__cta" aria-hidden="true">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M5 3h8v8M13 3 3 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </span>
    </div>
</a>
<?php endif; ?>
