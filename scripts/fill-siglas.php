<?php
/**
 * Script WP-CLI: rellena el campo ACF "siglas" para términos de taxonomía
 * "facultad" que lo tengan vacío. Genera iniciales a partir del nombre
 * (omite stopwords comunes).
 *
 * Uso: wp eval-file fill-siglas.php --url=http://localhost:8888/udp
 */

$stopwords = [ 'de', 'del', 'la', 'las', 'los', 'el', 'y', 'e', 'en', 'a', 'con', 'por', 'para', 'o', 'u' ];

function generar_siglas( string $nombre, array $stopwords ): string {
    $palabras = preg_split( '/\s+/', trim( $nombre ) );
    $iniciales = '';
    foreach ( $palabras as $p ) {
        $p = strtolower( trim( $p, ".,;:-\u{00AB}\u{00BB}" ) );
        if ( $p === '' || in_array( $p, $stopwords, true ) ) {
            continue;
        }
        $iniciales .= mb_strtoupper( mb_substr( $p, 0, 1 ) );
    }
    return $iniciales;
}

$terms = get_terms( [
    'taxonomy'   => 'facultad',
    'hide_empty' => false,
] );

if ( is_wp_error( $terms ) || empty( $terms ) ) {
    WP_CLI::error( 'No se encontraron términos de taxonomía "facultad".' );
    return;
}

$updated = 0;
$skipped = 0;

foreach ( $terms as $term ) {
    $existing = get_field( 'siglas', 'facultad_' . $term->term_id );
    if ( ! empty( $existing ) ) {
        WP_CLI::log( "  SKIP  [{$term->term_id}] {$term->name} → ya tiene: {$existing}" );
        $skipped++;
        continue;
    }

    $siglas = generar_siglas( $term->name, $stopwords );
    if ( $siglas === '' ) {
        WP_CLI::warning( "  WARN  [{$term->term_id}] {$term->name} → no se pudo generar siglas" );
        continue;
    }

    update_field( 'siglas', $siglas, 'facultad_' . $term->term_id );
    WP_CLI::log( "  SET   [{$term->term_id}] {$term->name} → {$siglas}" );
    $updated++;
}

WP_CLI::success( "Listo. Actualizados: {$updated} | Omitidos (ya tenían): {$skipped}" );
