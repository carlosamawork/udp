<?php
/**
 * Lazy migration: secciones (legacy) → acordeon (Simple Accordion template)
 *
 * Cuando una página se guarda con el template page-simple-accordion.php y el
 * campo acordeon está vacío, copia los items del layout "desplegable" del
 * flexible_content "secciones" al nuevo repeater "acordeon".
 *
 * - No destructivo: secciones NO se toca.
 * - Idempotente: si acordeon ya tiene datos no hace nada.
 * - link_externo / titulo_de_link: omitidos por ahora (se tratarán con las
 *   tarjetas laterales en fase posterior).
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

add_action( 'save_post_page', 'udp_migrate_desplegable_to_acordeon', 20, 1 );

/**
 * @param int $post_id
 */
function udp_migrate_desplegable_to_acordeon( $post_id ) {
    // Bail on autosaves, revisions y bulk-edit
    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
        return;
    }

    // Solo si el template es simple-accordion
    $template = get_post_meta( $post_id, '_wp_page_template', true );
    if ( 'templates/page-simple-accordion.php' !== $template ) {
        return;
    }

    // Idempotente: salir si acordeon ya tiene filas
    if ( ! function_exists( 'get_field' ) ) {
        return;
    }
    $existing = get_field( 'acordeon', $post_id );
    if ( ! empty( $existing ) ) {
        return;
    }

    // Leer secciones legacy
    $secciones = get_field( 'secciones', $post_id );
    if ( empty( $secciones ) || ! is_array( $secciones ) ) {
        return;
    }

    $acordeon_rows = array();

    foreach ( $secciones as $seccion ) {
        if ( ! isset( $seccion['acf_fc_layout'] ) || 'desplegable' !== $seccion['acf_fc_layout'] ) {
            continue;
        }

        $items = isset( $seccion['desplegable'] ) ? $seccion['desplegable'] : array();
        foreach ( $items as $item ) {
            $titulo    = isset( $item['titulo'] )    ? trim( $item['titulo'] )    : '';
            $contenido = isset( $item['contenido'] ) ? trim( $item['contenido'] ) : '';

            if ( ! $titulo ) {
                continue;
            }

            $acordeon_rows[] = array(
                'titulo'    => $titulo,
                'contenido' => $contenido,
            );
        }
    }

    if ( empty( $acordeon_rows ) ) {
        return;
    }

    // Desactivar este hook mientras update_field guarda para evitar re-entradas
    remove_action( 'save_post_page', 'udp_migrate_desplegable_to_acordeon', 20 );
    update_field( 'acordeon', $acordeon_rows, $post_id );
    add_action( 'save_post_page', 'udp_migrate_desplegable_to_acordeon', 20 );
}
