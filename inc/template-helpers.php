<?php
/**
 * Template helpers — UDP starter-theme
 *
 * Funciones reutilizables por header, footer y bloques flexible content.
 *
 * @package Starter_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Devuelve la clase de tema del body según contexto.
 *
 * Reglas (sección 4.5 del spec):
 * - is-dark: home, archives, post-type-archives, search results
 * - is-light: singles, pages, 404
 *
 * @return string 'is-dark' | 'is-light'
 */
function udp_body_theme_class() {
	if ( is_front_page() || is_home() || is_archive() || is_post_type_archive() || is_search() ) {
		return 'is-dark';
	}
	return 'is-light';
}

/**
 * Devuelve la URL del logo desde options page General.
 *
 * @param string $variant 'color' | 'blanco' | 'udp' | 'acreditacion'
 * @return string URL del logo o cadena vacía si no está configurado.
 */
function udp_get_logo_url( $variant = 'color' ) {
	$valid_variants = array( 'color', 'blanco', 'udp', 'acreditacion' );
	if ( ! in_array( $variant, $valid_variants, true ) ) {
		$variant = 'color';
	}
	$field = 'logo_' . $variant;
	$url   = function_exists( 'get_field' ) ? get_field( $field, 'option' ) : '';
	return is_string( $url ) ? $url : '';
}

/**
 * Devuelve el `style="--faculty-color: #XXX;"` para inyectar en un wrapper
 * relacionado a una facultad (card, badge, sección).
 *
 * @param int $term_id ID del término de la taxonomía 'facultad'.
 * @return string Atributo style listo para echo, o cadena vacía si no hay color.
 */
function udp_render_faculty_color_var( $term_id ) {
	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}
	$color = get_field( 'color', 'facultad_' . (int) $term_id );
	if ( empty( $color ) || ! is_string( $color ) ) {
		return '';
	}
	return ' style="--faculty-color: ' . esc_attr( $color ) . ';"';
}

/**
 * Devuelve un array con las URLs de redes sociales configuradas en options
 * page Redes Sociales. Solo incluye las que tienen valor (no vacías).
 *
 * @return array Asociativo: ['facebook' => 'https://...', 'twitter' => '...', ...]
 */
function udp_get_social_urls() {
	if ( ! function_exists( 'get_field' ) ) {
		return array();
	}
	$networks = array( 'facebook', 'twitter', 'instagram', 'youtube', 'linkedin', 'tiktok' );
	$out      = array();
	foreach ( $networks as $net ) {
		$url = get_field( $net, 'option' );
		if ( ! empty( $url ) && is_string( $url ) ) {
			$out[ $net ] = $url;
		}
	}
	return $out;
}

/**
 * Devuelve las columnas del footer desde options page Footer.
 *
 * @return array Array de columnas, cada una con 'titulo' y 'links' (array de label+url).
 */
function udp_get_footer_columns() {
	if ( ! function_exists( 'get_field' ) ) {
		return array();
	}
	$cols = get_field( 'columnas_footer', 'option' );
	return is_array( $cols ) ? $cols : array();
}
