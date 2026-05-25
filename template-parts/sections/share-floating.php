<?php
/**
 * Botón share flotante a la derecha (píldora vertical blanca).
 *
 * Strip de 5 acciones directas: copiar enlace, email, Facebook, X, WhatsApp.
 * Las URLs de los enlaces las completa `share-floating.js`.
 * Se oculta bajo 576px de ancho.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$share_url   = esc_attr( esc_url( get_permalink() ) );
$share_title = esc_attr( wp_strip_all_tags( get_the_title() ) );
?>
<aside class="udp-inst-share" data-udp-share data-share-url="<?php echo $share_url; ?>" data-share-title="<?php echo $share_title; ?>" aria-label="<?php esc_attr_e( 'Compartir esta página', 'starter-theme' ); ?>">
	<button type="button" class="udp-inst-share__btn" data-share-action="copy" aria-label="<?php esc_attr_e( 'Copiar enlace', 'starter-theme' ); ?>">
		<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
			<path d="M8.6 11.4a3 3 0 004.24 0l2.5-2.5a3 3 0 10-4.24-4.24l-1.1 1.1M11.4 8.6a3 3 0 00-4.24 0l-2.5 2.5a3 3 0 104.24 4.24l1.1-1.1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
		</svg>
	</button>
	<a class="udp-inst-share__btn" data-share-action="email" href="#" aria-label="<?php esc_attr_e( 'Compartir por email', 'starter-theme' ); ?>">
		<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
			<rect x="2.5" y="4.5" width="15" height="11" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
			<path d="M3.2 5.5L10 10.5l6.8-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
		</svg>
	</a>
	<a class="udp-inst-share__btn" data-share-action="facebook" href="#" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Compartir en Facebook', 'starter-theme' ); ?>">
		<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
			<path d="M12.6 3.3h-2.1c-1.9 0-3.2 1.27-3.2 3.2V8.4H5.2v2.86h2.1V18h2.95v-6.74h2.18l.4-2.86h-2.58V6.86c0-.55.27-.92.96-.92h1.4V3.3z"/>
		</svg>
	</a>
	<a class="udp-inst-share__btn" data-share-action="x" href="#" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Compartir en X', 'starter-theme' ); ?>">
		<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
			<path d="M15.3 2.8h2.4l-5.23 5.98L18.6 17.2h-4.83l-3.78-4.94-4.33 4.94H3.26l5.6-6.4L2.6 2.8h4.95l3.42 4.52L15.3 2.8zm-.84 12.97h1.33L7.07 4.15H5.64l8.82 11.62z"/>
		</svg>
	</a>
	<a class="udp-inst-share__btn" data-share-action="whatsapp" href="#" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Compartir por WhatsApp', 'starter-theme' ); ?>">
		<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
			<path d="M10 2.5a7.5 7.5 0 00-6.46 11.3L2.5 17.5l3.8-1a7.5 7.5 0 103.7-14zm0 13.6a6.1 6.1 0 01-3.1-.85l-.22-.13-2.26.6.6-2.2-.14-.23A6.1 6.1 0 1110 16.1zm3.4-4.57c-.18-.1-1.1-.54-1.27-.6-.17-.07-.3-.1-.42.09-.12.18-.48.6-.59.72-.1.12-.21.13-.4.05a5 5 0 01-1.46-.9 5.5 5.5 0 01-1.02-1.26c-.1-.18 0-.28.08-.37l.28-.32c.09-.11.12-.18.18-.3.06-.13.03-.24-.02-.33-.04-.09-.42-1-.57-1.37-.15-.36-.3-.31-.42-.32h-.36c-.12 0-.32.05-.48.23-.17.18-.64.62-.64 1.52s.65 1.76.75 1.88c.09.12 1.3 1.98 3.14 2.78.44.19.78.3 1.05.39.44.14.84.12 1.16.07.35-.05 1.1-.45 1.25-.88.16-.43.16-.8.11-.88-.04-.08-.16-.13-.35-.22z"/>
		</svg>
	</a>
</aside>
