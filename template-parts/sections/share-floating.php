<?php
/**
 * Botón share flotante a la derecha.
 *
 * Usa Web Share API si está disponible; fallback dropdown con
 * copiar enlace, email, WhatsApp, LinkedIn, X y Facebook.
 *
 * Se oculta bajo 576px de ancho.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$share_url   = esc_url( get_permalink() );
$share_title = esc_attr( wp_strip_all_tags( get_the_title() ) );
?>
<aside class="udp-inst-share" data-udp-share data-share-url="<?php echo $share_url; ?>" data-share-title="<?php echo $share_title; ?>" aria-label="<?php esc_attr_e( 'Compartir esta página', 'starter-theme' ); ?>">
    <button type="button" class="udp-inst-share__trigger" aria-haspopup="menu" aria-expanded="false">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
            <path d="M15 7a2.5 2.5 0 100-5 2.5 2.5 0 000 5zM5 12.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5zM15 18a2.5 2.5 0 100-5 2.5 2.5 0 000 5zM7.15 11.25l5.7 3.5M12.85 5.25l-5.7 3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span class="udp-inst-share__label"><?php esc_html_e( 'Compartir', 'starter-theme' ); ?></span>
    </button>

    <ul class="udp-inst-share__menu" role="menu" hidden>
        <li role="none"><button type="button" role="menuitem" data-share-action="copy"><?php esc_html_e( 'Copiar enlace', 'starter-theme' ); ?></button></li>
        <li role="none"><a role="menuitem" data-share-action="email" href="#" target="_blank" rel="noopener"><?php esc_html_e( 'Email', 'starter-theme' ); ?></a></li>
        <li role="none"><a role="menuitem" data-share-action="whatsapp" href="#" target="_blank" rel="noopener"><?php esc_html_e( 'WhatsApp', 'starter-theme' ); ?></a></li>
        <li role="none"><a role="menuitem" data-share-action="linkedin" href="#" target="_blank" rel="noopener"><?php esc_html_e( 'LinkedIn', 'starter-theme' ); ?></a></li>
        <li role="none"><a role="menuitem" data-share-action="x" href="#" target="_blank" rel="noopener"><?php esc_html_e( 'X', 'starter-theme' ); ?></a></li>
        <li role="none"><a role="menuitem" data-share-action="facebook" href="#" target="_blank" rel="noopener"><?php esc_html_e( 'Facebook', 'starter-theme' ); ?></a></li>
    </ul>
</aside>
