<?php
/**
 * Barra de acción mobile GLOBAL (fija abajo, solo <md)
 *
 * Compartir (nativo + fallback popover) + Menú (dispara el mega-menú del header)
 * + volver-arriba. Comparte la página ACTUAL. Se incluye desde footer.php en
 * todas las páginas; visible solo en <md vía CSS.
 *
 * @package Starter_Theme
 */
defined( 'ABSPATH' ) || exit;

if ( is_singular() ) {
    $url   = get_permalink();
    $title = get_the_title();
} else {
    // URL desde origen de confianza: home_url() fuerza el scheme+host del sitio
    // (no manipulables vía cabecera Host). Evita Host Header Injection / cache-poisoning.
    global $wp;
    $path  = isset( $wp->request ) ? $wp->request : '';
    $url   = home_url( add_query_arg( array(), $path ) );
    $title = wp_get_document_title();
}

$facebook = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url );
$twitter  = 'https://twitter.com/intent/tweet?url=' . rawurlencode( $url ) . '&text=' . rawurlencode( $title );
$whatsapp = 'https://api.whatsapp.com/send?text=' . rawurlencode( $title . ' ' . $url );
$linkedin = 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( $url );
?>
<div
    class="udp-mobile-bar"
    data-udp-mobile-bar
    data-share-url="<?php echo esc_attr( $url ); ?>"
    data-share-title="<?php echo esc_attr( $title ); ?>"
>
    <button type="button" class="udp-mobile-bar__share" data-udp-mobile-share
        aria-label="<?php esc_attr_e( 'Compartir', 'starter-theme' ); ?>" aria-expanded="false">
        <svg width="20" height="20" viewBox="0 0 18 18" fill="none" aria-hidden="true">
            <circle cx="4" cy="9" r="2" stroke="currentColor" stroke-width="1.3"/>
            <circle cx="13" cy="4" r="2" stroke="currentColor" stroke-width="1.3"/>
            <circle cx="13" cy="14" r="2" stroke="currentColor" stroke-width="1.3"/>
            <path d="M5.7 8l5.6-3M5.7 10l5.6 3" stroke="currentColor" stroke-width="1.3"/>
        </svg>
    </button>

    <button type="button" class="udp-mobile-bar__menu" data-udp-mobile-menu
        aria-label="<?php esc_attr_e( 'Abrir menú', 'starter-theme' ); ?>">
        <span class="udp-mobile-bar__menu-icon" aria-hidden="true">
            <svg width="24" height="24" viewBox="0 0 26 26" fill="none">
                <line x1="5" y1="9"  x2="21" y2="9"  stroke="currentColor" stroke-width="1.5"/>
                <line x1="5" y1="13" x2="21" y2="13" stroke="currentColor" stroke-width="1.5"/>
                <line x1="5" y1="17" x2="21" y2="17" stroke="currentColor" stroke-width="1.5"/>
            </svg>
        </span>
        <span class="udp-mobile-bar__menu-label"><?php esc_html_e( 'Menú', 'starter-theme' ); ?></span>
    </button>

    <button type="button" class="udp-mobile-bar__top" data-udp-mobile-top
        aria-label="<?php esc_attr_e( 'Volver arriba', 'starter-theme' ); ?>">
        <svg width="24" height="24" viewBox="0 0 18 18" fill="none" aria-hidden="true">
            <path d="M9 14V4M5 8l4-4 4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </button>

    <div class="udp-mobile-bar__sheet" data-udp-mobile-sheet hidden>
        <button type="button" class="udp-mobile-bar__sheet-action" data-udp-copy-url data-url="<?php echo esc_attr( $url ); ?>">
            <?php esc_html_e( 'Copiar enlace', 'starter-theme' ); ?>
            <span class="udp-mobile-bar__sheet-toast" data-udp-copy-toast hidden><?php esc_html_e( 'Copiado', 'starter-theme' ); ?></span>
        </button>
        <a class="udp-mobile-bar__sheet-action" href="<?php echo esc_url( $facebook ); ?>" target="_blank" rel="noopener noreferrer">Facebook</a>
        <a class="udp-mobile-bar__sheet-action" href="<?php echo esc_url( $twitter ); ?>" target="_blank" rel="noopener noreferrer">X</a>
        <a class="udp-mobile-bar__sheet-action" href="<?php echo esc_url( $whatsapp ); ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a>
        <a class="udp-mobile-bar__sheet-action" href="<?php echo esc_url( $linkedin ); ?>" target="_blank" rel="noopener noreferrer">LinkedIn</a>
    </div>
</div>
