<?php
/**
 * Card primitive — Noticia
 *
 * Recibe data ya normalizada en shape `Card` (ver inc/udp-cards.php).
 * No sabe de WP_Query ni del bloque contenedor; reutilizable por archives.
 *
 * @package Starter_Theme
 *
 * @var array $args ['card' => array, 'theme' => string, 'variant' => string]
 *                  variant: '' (default) | 'horizontal' (image-left 201×275 para archive)
 */
$card  = isset( $args['card'] ) && is_array( $args['card'] ) ? $args['card'] : array();
$theme = isset( $args['theme'] ) && in_array( $args['theme'], array( 'dark', 'light' ), true ) ? $args['theme'] : 'dark';

$href = $card['href'] ?? '';
$titulo = $card['titulo'] ?? '';
$imagen = is_array( $card['imagen'] ?? null ) ? $card['imagen'] : array();

if ( empty( $href ) || empty( $titulo ) || empty( $imagen['url'] ?? '' ) ) {
    return;
}

$eyebrow       = $card['eyebrow'] ?? '';
$eyebrow_color = in_array( ( $card['eyebrow_color'] ?? '' ), array( 'yellow', 'red', 'blue' ), true ) ? $card['eyebrow_color'] : '';
$fecha_iso     = $card['fecha'] ?? '';
$fecha_display = function_exists( 'udp_card_format_date' ) ? udp_card_format_date( $fecha_iso ) : '';
$target        = $card['target'] ?? '';
$rel           = $target === '_blank' ? 'noopener noreferrer' : '';

$variant = isset( $args['variant'] ) && in_array( $args['variant'], array( 'horizontal' ), true ) ? $args['variant'] : '';
$class = 'udp-card-noticia udp-card-noticia--' . $theme . ( $variant ? ' udp-card-noticia--' . $variant : '' );
?>
<a
    href="<?php echo esc_url( $href ); ?>"
    class="<?php echo esc_attr( $class ); ?>"
    <?php if ( $target ) : ?>target="<?php echo esc_attr( $target ); ?>"<?php endif; ?>
    <?php if ( $rel ) : ?>rel="<?php echo esc_attr( $rel ); ?>"<?php endif; ?>
>
    <figure class="udp-card-noticia__media">
        <img
            src="<?php echo esc_url( $imagen['url'] ); ?>"
            alt="<?php echo esc_attr( $imagen['alt'] ?? '' ); ?>"
            loading="lazy"
            decoding="async"
        />
    </figure>
    <div class="udp-card-noticia__body">
        <header class="udp-card-noticia__meta">
            <?php if ( $eyebrow ) : ?>
                <span class="udp-card-noticia__eyebrow<?php echo $eyebrow_color ? ' udp-card-noticia__eyebrow--' . esc_attr( $eyebrow_color ) : ''; ?>"><?php echo esc_html( $eyebrow ); ?></span>
            <?php endif; ?>
            <?php if ( $fecha_iso && $fecha_display ) : ?>
                <time class="udp-card-noticia__date" datetime="<?php echo esc_attr( $fecha_iso ); ?>"><?php echo esc_html( $fecha_display ); ?></time>
            <?php endif; ?>
        </header>
        <h3 class="udp-card-noticia__title"><?php echo esc_html( $titulo ); ?></h3>
        <span class="udp-card-noticia__more" aria-hidden="true">
            <?php esc_html_e( 'Leer más', 'starter-theme' ); ?>
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                <path d="M3.5 2.5h6v6M9.5 2.5l-7 7" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </span>
    </div>
</a>
