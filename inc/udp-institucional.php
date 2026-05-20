<?php
/**
 * Helpers para el template page-institucional.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Recolecta los anchors a partir del flexible content `sections` del post actual.
 *
 * Cada layout que tenga `anchor_label` no vacío genera una entrada.
 * El layout `back_link` solo aparece si `display_in_anchors=true`.
 * Se prepende un anchor "Inicio" que apunta al hero.
 *
 * @param int|null $post_id Post a consultar; null usa el actual.
 * @return array<int,array{id:string,label:string,icon:?array,order:int,layout_key:string}>
 */
function udp_institucional_collect_anchors( $post_id = null ) {
    if ( ! function_exists( 'get_field' ) ) {
        return array();
    }

    $sections = get_field( 'sections', $post_id );
    if ( ! is_array( $sections ) ) {
        $sections = array();
    }

    $anchors = array();
    $used_ids = array();

    // Anchor "Inicio" auto al inicio
    $anchors[] = array(
        'id'         => 'section-inicio',
        'label'      => __( 'Inicio', 'starter-theme' ),
        'icon'       => null,
        'order'      => 0,
        'layout_key' => '__hero__',
    );
    $used_ids['section-inicio'] = 1;

    $order = 1;
    foreach ( $sections as $section ) {
        $layout = $section['acf_fc_layout'] ?? '';
        $label  = trim( (string) ( $section['anchor_label'] ?? '' ) );

        if ( $label === '' ) {
            continue;
        }

        if ( $layout === 'back_link' && empty( $section['display_in_anchors'] ) ) {
            continue;
        }

        $base_id = 'section-' . sanitize_title( $label );
        $id      = $base_id;
        $suffix  = 2;
        while ( isset( $used_ids[ $id ] ) ) {
            $id = $base_id . '-' . $suffix;
            $suffix++;
        }
        $used_ids[ $id ] = 1;

        $icon = $section['anchor_icon'] ?? null;
        if ( ! is_array( $icon ) ) {
            $icon = null;
        }

        $anchors[] = array(
            'id'         => $id,
            'label'      => $label,
            'icon'       => $icon,
            'order'      => $order,
            'layout_key' => $layout,
        );

        $order++;
    }

    return $anchors;
}

/**
 * Devuelve el id de anchor para una sección dada del flexible content,
 * tomando como referencia el array completo de anchors.
 *
 * Útil dentro de cada partial layout-*.php para obtener su id sin re-derivar.
 *
 * @param array $anchors       Array completo devuelto por udp_institucional_collect_anchors().
 * @param int   $section_index Índice (0-based) de la sección dentro del flexible content.
 * @return array|null
 */
function udp_institucional_anchor_for_index( array $anchors, $section_index ) {
    foreach ( $anchors as $a ) {
        if ( $a['order'] === ( $section_index + 1 ) ) {
            return $a;
        }
    }
    return null;
}
