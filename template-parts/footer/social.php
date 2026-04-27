<?php
/**
 * Footer > Iconos sociales (Figma 4401:23290 — bottom center)
 *
 * Renderiza círculos 44px con SVG inline. Figma muestra LinkedIn,
 * Instagram, YouTube — pero respetamos lo que esté configurado en la
 * options page Redes Sociales y mantenemos el orden Figma (li, ig, yt
 * primero, luego el resto si existieran).
 *
 * TODO (F10 polish): SVGs son placeholder simple monocromo blanco. El
 * Figma usa íconos custom — reemplazar por los oficiales si difieren.
 *
 * @package Starter_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$socials = function_exists( 'udp_get_social_urls' ) ? udp_get_social_urls() : array();
if ( empty( $socials ) ) {
	return;
}

// Orden preferido según Figma (los que no estén se omiten al iterar).
$priority   = array( 'linkedin', 'instagram', 'youtube' );
$ordered    = array();
foreach ( $priority as $key ) {
	if ( isset( $socials[ $key ] ) ) {
		$ordered[ $key ] = $socials[ $key ];
		unset( $socials[ $key ] );
	}
}
$ordered = array_merge( $ordered, $socials );

$labels = array(
	'facebook'  => 'Facebook',
	'twitter'   => 'Twitter / X',
	'instagram' => 'Instagram',
	'youtube'   => 'YouTube',
	'linkedin'  => 'LinkedIn',
	'tiktok'    => 'TikTok',
);

$icons = array(
	'linkedin'  => '<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" focusable="false"><path d="M3.58 4.31a1.31 1.31 0 1 1 0-2.62 1.31 1.31 0 0 1 0 2.62Zm-1.13 1.5h2.26v8.43H2.45V5.81Zm3.92 0h2.16v1.15h.03c.3-.57 1.04-1.17 2.13-1.17 2.28 0 2.7 1.5 2.7 3.45v4.99h-2.25V9.65c0-1.04-.02-2.38-1.45-2.38-1.45 0-1.67 1.13-1.67 2.3v4.67H6.37V5.81Z"/></svg>',
	'instagram' => '<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" focusable="false"><path d="M8 1.44c2.14 0 2.39.01 3.23.05.78.04 1.2.17 1.49.28.37.14.64.32.92.6.28.28.46.55.6.92.11.28.24.71.28 1.49.04.84.05 1.1.05 3.22 0 2.14-.01 2.39-.05 3.23-.04.78-.17 1.2-.28 1.49-.14.37-.32.64-.6.92a2.5 2.5 0 0 1-.92.6c-.28.11-.71.24-1.49.28-.84.04-1.1.05-3.23.05-2.14 0-2.39-.01-3.22-.05-.78-.04-1.2-.17-1.49-.28a2.5 2.5 0 0 1-.92-.6 2.5 2.5 0 0 1-.6-.92c-.11-.28-.24-.71-.28-1.49C1.45 10.39 1.44 10.14 1.44 8c0-2.14.01-2.39.05-3.22.04-.78.17-1.2.28-1.49.14-.37.32-.64.6-.92.28-.28.55-.46.92-.6.28-.11.71-.24 1.49-.28C5.61 1.45 5.86 1.44 8 1.44Zm0-1.44C5.83 0 5.55.01 4.7.05c-.85.04-1.43.18-1.94.37a3.9 3.9 0 0 0-1.42.92A3.9 3.9 0 0 0 .42 2.76C.23 3.27.09 3.85.05 4.7.01 5.55 0 5.83 0 8c0 2.17.01 2.45.05 3.3.04.85.18 1.43.37 1.94.21.55.49 1.02.92 1.42.4.43.87.71 1.42.92.51.19 1.09.33 1.94.37C5.55 15.99 5.83 16 8 16s2.45-.01 3.3-.05c.85-.04 1.43-.18 1.94-.37.55-.21 1.02-.49 1.42-.92.43-.4.71-.87.92-1.42.19-.51.33-1.09.37-1.94.04-.85.05-1.13.05-3.3s-.01-2.45-.05-3.3c-.04-.85-.18-1.43-.37-1.94a3.9 3.9 0 0 0-.92-1.42 3.9 3.9 0 0 0-1.42-.92c-.51-.19-1.09-.33-1.94-.37C10.45.01 10.17 0 8 0Zm0 3.89A4.11 4.11 0 1 0 8 12.11 4.11 4.11 0 0 0 8 3.89Zm0 6.78a2.67 2.67 0 1 1 0-5.34 2.67 2.67 0 0 1 0 5.34Zm5.23-6.94a.96.96 0 1 1-1.92 0 .96.96 0 0 1 1.92 0Z"/></svg>',
	'youtube'   => '<svg width="16" height="12" viewBox="0 0 16 12" fill="currentColor" aria-hidden="true" focusable="false"><path d="M15.65 1.86a2 2 0 0 0-1.41-1.42C12.97.1 8 .1 8 .1s-4.97 0-6.24.34A2 2 0 0 0 .35 1.86C0 3.13 0 5.78 0 5.78s0 2.65.35 3.92a2 2 0 0 0 1.41 1.42c1.27.34 6.24.34 6.24.34s4.97 0 6.24-.34a2 2 0 0 0 1.41-1.42C16 8.43 16 5.78 16 5.78s0-2.65-.35-3.92ZM6.4 8.21V3.35l4.16 2.43L6.4 8.21Z"/></svg>',
	'facebook'  => '<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" focusable="false"><path d="M16 8.05A8 8 0 1 0 6.75 16v-5.62H4.72V8.05h2.03V6.28c0-2.01 1.2-3.12 3.02-3.12.88 0 1.79.16 1.79.16v1.97h-1c-.99 0-1.3.62-1.3 1.25v1.51h2.21l-.35 2.33H9.26V16A8 8 0 0 0 16 8.05Z"/></svg>',
	'twitter'   => '<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" focusable="false"><path d="M9.52 6.78 15.48 0h-1.41L8.89 5.88 4.76 0H0l6.25 8.9L0 16h1.41l5.47-6.21L11.24 16H16L9.52 6.78Zm-1.94 2.2L6.95 8.1 1.92 1.04h2.17l4.07 5.71.63.89 5.29 7.42h-2.17L7.58 8.98Z"/></svg>',
	'tiktok'    => '<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" focusable="false"><path d="M11.4 0h-2.5v10.07a2 2 0 1 1-2-2.07V5.45a4.55 4.55 0 1 0 4.55 4.55V5.34a5.7 5.7 0 0 0 3.31 1.06V3.86a3.41 3.41 0 0 1-3.36-3.41V0Z"/></svg>',
);
?>
<ul class="udp-footer-social" role="list">
	<?php foreach ( $ordered as $key => $url ) :
		$icon  = isset( $icons[ $key ] ) ? $icons[ $key ] : '';
		$label = isset( $labels[ $key ] ) ? $labels[ $key ] : ucfirst( $key );
		?>
		<li>
			<a
				href="<?php echo esc_url( $url ); ?>"
				class="udp-footer-social__link"
				target="_blank"
				rel="noopener noreferrer"
				aria-label="<?php echo esc_attr( $label ); ?>"
			>
				<?php if ( '' !== $icon ) : ?>
					<?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php else : ?>
					<span aria-hidden="true"><?php echo esc_html( strtoupper( substr( $key, 0, 2 ) ) ); ?></span>
				<?php endif; ?>
			</a>
		</li>
	<?php endforeach; ?>
</ul>
