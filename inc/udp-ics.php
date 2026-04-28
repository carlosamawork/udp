<?php
/**
 * ICS calendar endpoint
 *
 * Detecta `?udp_ics={post_id}` en init y emite un VCALENDAR/VEVENT del
 * evento (CPT agenda). Permite que el botón "Agregar al calendario" del
 * single-event genere el archivo .ics descargable.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Parse a raw postmeta date (Ymd or Y-m-d) into a Unix timestamp. Returns 0 if can't parse.
 */
function udp_ics_parse_date_raw( $raw ): int {
    if ( ! $raw ) {
        return 0;
    }
    $raw = (string) $raw;
    // Try Ymd (ACF date_picker default storage)
    $dt = DateTime::createFromFormat( 'Ymd', $raw );
    if ( $dt ) {
        return $dt->getTimestamp();
    }
    // Fallback to strtotime (handles Y-m-d, ISO, etc.)
    $ts = strtotime( $raw );
    return $ts ?: 0;
}

/**
 * Parse a raw postmeta time (H:i:s or g:i a) into seconds since midnight.
 */
function udp_ics_parse_time_raw( $raw ): int {
    if ( ! $raw ) {
        return 0;
    }
    $raw = (string) $raw;
    // Try common formats
    foreach ( array( 'H:i:s', 'H:i', 'g:i a', 'g:i A' ) as $fmt ) {
        $dt = DateTime::createFromFormat( $fmt, $raw );
        if ( $dt ) {
            return ( (int) $dt->format( 'H' ) * 3600 ) + ( (int) $dt->format( 'i' ) * 60 ) + (int) $dt->format( 's' );
        }
    }
    $ts = strtotime( $raw );
    if ( $ts ) {
        return ( (int) date( 'H', $ts ) * 3600 ) + ( (int) date( 'i', $ts ) * 60 ) + (int) date( 's', $ts );
    }
    return 0;
}

add_action( 'init', function () {
    if ( ! isset( $_GET['udp_ics'] ) ) {
        return;
    }
    $post_id = (int) $_GET['udp_ics'];
    if ( $post_id <= 0 ) {
        return;
    }
    $post = get_post( $post_id );
    if ( ! $post || $post->post_type !== 'agenda' || $post->post_status !== 'publish' ) {
        return;
    }

    // Use raw postmeta values to avoid ACF's locale-formatted return strings.
    $fecha_raw = get_post_meta( $post_id, 'fecha', true );
    $hora_raw  = get_post_meta( $post_id, 'hora_inicio', true );
    $hora_fin_raw = get_post_meta( $post_id, 'hora_termino', true );
    $lugar     = (string) get_post_meta( $post_id, 'lugar', true );

    if ( ! $fecha_raw ) {
        wp_die( esc_html__( 'El evento no tiene fecha definida.', 'starter-theme' ), 404 );
    }

    $date_ts  = udp_ics_parse_date_raw( $fecha_raw );
    if ( ! $date_ts ) {
        wp_die( esc_html__( 'Fecha del evento inválida.', 'starter-theme' ), 500 );
    }

    $hora_secs    = udp_ics_parse_time_raw( $hora_raw );
    $hora_fin_secs = udp_ics_parse_time_raw( $hora_fin_raw );

    $start_ts = $date_ts + $hora_secs;  // 00:00 si no hay hora
    $end_ts   = $hora_fin_secs > 0 ? ( $date_ts + $hora_fin_secs ) : ( $start_ts + 3600 );
    if ( $end_ts <= $start_ts ) {
        $end_ts = $start_ts + 3600;
    }

    $title = wp_strip_all_tags( get_the_title( $post ) );
    $desc  = wp_strip_all_tags( get_the_excerpt( $post ) );
    $uid   = $post_id . '@' . wp_parse_url( home_url(), PHP_URL_HOST );

    nocache_headers();
    header( 'Content-Type: text/calendar; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="evento-' . $post->post_name . '.ics"' );

    $ics  = "BEGIN:VCALENDAR\r\n";
    $ics .= "VERSION:2.0\r\n";
    $ics .= "PRODID:-//UDP//Eventos//ES\r\n";
    $ics .= "METHOD:PUBLISH\r\n";
    $ics .= "BEGIN:VEVENT\r\n";
    $ics .= "UID:" . $uid . "\r\n";
    $ics .= "DTSTAMP:" . gmdate( 'Ymd\THis\Z' ) . "\r\n";
    $ics .= "DTSTART:" . gmdate( 'Ymd\THis\Z', $start_ts ) . "\r\n";
    $ics .= "DTEND:" . gmdate( 'Ymd\THis\Z', $end_ts ) . "\r\n";
    $ics .= "SUMMARY:" . str_replace( array( "\r", "\n" ), ' ', $title ) . "\r\n";
    if ( $desc ) {
        $ics .= "DESCRIPTION:" . str_replace( array( "\r", "\n" ), ' ', $desc ) . "\r\n";
    }
    if ( $lugar ) {
        $ics .= "LOCATION:" . str_replace( array( "\r", "\n" ), ' ', $lugar ) . "\r\n";
    }
    $ics .= "URL:" . get_permalink( $post ) . "\r\n";
    $ics .= "END:VEVENT\r\n";
    $ics .= "END:VCALENDAR\r\n";

    echo $ics; // phpcs:ignore — text/calendar contenido construido por nosotros
    exit;
} );
