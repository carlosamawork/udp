<?php
/**
 * Buscador — endpoint AJAX
 *
 * Acción: udp_search
 * POST params: q (string), nonce (string)
 * Respuesta: { success: true, data: { sections: [{id, label, items: [{title, url}]}] } }
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

define( 'UDP_SEARCH_MAX_PER_SECTION', 10 );

add_action( 'wp_ajax_nopriv_udp_search', 'udp_search_handler' );
add_action( 'wp_ajax_udp_search',        'udp_search_handler' );

function udp_search_handler(): void {
    if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'starter_bs5_nonce' ) ) {
        wp_send_json_error( [ 'message' => 'Invalid nonce' ], 403 );
    }

    $q = sanitize_text_field( wp_unslash( $_POST['q'] ?? '' ) );

    if ( $q === '' ) {
        wp_send_json_error( [ 'message' => 'Empty query' ], 400 );
    }

    $base = [
        's'                      => $q,
        'posts_per_page'         => UDP_SEARCH_MAX_PER_SECTION,
        'no_found_rows'          => true,
        'update_post_term_cache' => false,
        'update_post_meta_cache' => false,
    ];

    // Resolvemos el ID de la página Facultades por path (resiliente entre entornos)
    $fac_page = get_page_by_path( 'facultades' );
    $fac_pid  = $fac_page ? (int) $fac_page->ID : 0;

    $definitions = [
        [ 'pages',      'Páginas',             'page',        [ 'post_parent__not_in' => [ $fac_pid ], 'post__not_in' => [ $fac_pid ] ] ],
        [ 'facultades', 'Facultades',           'page',        [ 'post_parent'         => $fac_pid ] ],
        [ 'carreras',   'Carreras',             'carrera-udp', [] ],
        [ 'centros',    'Centros',              'centro-udp',  [] ],
        [ 'noticias',   'Noticias',             'post',        [] ],
        [ 'eventos',    'Eventos',              'agenda',      [] ],
        [ 'calendario', 'Calendario Académico', 'calendario',  [] ],
    ];

    $sections = [];

    foreach ( $definitions as [ $id, $label, $post_type, $extra ] ) {
        $query = new WP_Query( array_merge( $base, [ 'post_type' => $post_type ], $extra ) );

        if ( ! $query->have_posts() ) {
            continue;
        }

        $items = [];
        foreach ( $query->posts as $post ) {
            $title = get_the_title( $post );
            if ( ! $title ) {
                continue;
            }
            $items[] = [
                'title' => $title,
                'url'   => get_permalink( $post ),
            ];
        }

        if ( ! empty( $items ) ) {
            $sections[] = compact( 'id', 'label', 'items' );
        }
    }

    wp_send_json_success( [ 'sections' => $sections ] );
}
