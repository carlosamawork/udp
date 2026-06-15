<?php
/**
 * Single Event > Te podría interesar
 *
 * 3 eventos relacionados por facultad primaria. Si <3, fallback a más
 * próximos (ASC desde hoy).
 *
 * @package Starter_Theme
 *
 * @var array $args ['post_id' => int]
 */
$current_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();
if ( ! $current_id ) {
    return;
}

$primary_facultad = 0;
$facultades = get_the_terms( $current_id, 'facultad' );
if ( ! is_wp_error( $facultades ) && ! empty( $facultades ) ) {
    $primary_facultad = (int) $facultades[0]->term_id;
}

$today = gmdate( 'Ymd' );

/**
 * Trae eventos relacionados sin perder cards. Excluye los de `fecha` vacía
 * (para que la card siempre muestre fecha) y ordena por fecha DESC: como la
 * fecha es Ymd, DESC pone primero los próximos y luego los pasados recientes.
 * El catálogo es casi todo histórico (3613/3626 con fecha, ~1 futuro), así que
 * un filtro estricto `>= hoy` vaciaría la sección — por eso no se restringe a
 * futuros, solo se prefieren via el orden.
 *
 * @param int   $facultad  term_id de facultad (0 = sin filtro de facultad).
 * @param int   $limit     nº de eventos a traer.
 * @param int[] $exclude   IDs a excluir.
 * @return WP_Post[]
 */
$fetch_related = static function ( $facultad, $limit, $exclude ) {
    $args = array(
        'post_type'      => 'agenda',
        'posts_per_page' => $limit,
        'post__not_in'   => $exclude,
        'meta_key'       => 'fecha',
        'orderby'        => 'meta_value',
        'order'          => 'DESC',
        'no_found_rows'  => true,
        'meta_query'     => array(
            array( 'key' => 'fecha', 'value' => '', 'compare' => '!=' ),
        ),
    );
    if ( $facultad ) {
        $args['tax_query'] = array(
            array( 'taxonomy' => 'facultad', 'field' => 'term_id', 'terms' => array( $facultad ) ),
        );
    }
    $q = new WP_Query( $args );
    return $q->posts;
};

$exclude = array( $current_id );
$posts   = array();

// 1) Misma facultad (más recientes primero).
if ( $primary_facultad ) {
    $posts   = $fetch_related( $primary_facultad, 3, $exclude );
    $exclude = array_merge( $exclude, wp_list_pluck( $posts, 'ID' ) );
}

// 2) Relleno global hasta 3 (más recientes primero).
if ( count( $posts ) < 3 ) {
    $fill  = $fetch_related( 0, 3 - count( $posts ), $exclude );
    $posts = array_merge( $posts, $fill );
}

$cards = array();
foreach ( $posts as $post ) {
    $card = function_exists( 'udp_card_data_from_agenda' ) ? udp_card_data_from_agenda( $post ) : null;
    if ( $card ) {
        $cards[] = $card;
    }
}

if ( empty( $cards ) ) {
    return;
}
?>
<section class="udp-single-event__related" data-udp-event-related-carousel>
    <div class="udp-single-event__related-inner">
        <h2 class="udp-single-event__related-title"><?php esc_html_e( 'Te podría interesar', 'starter-theme' ); ?></h2>
        <div class="udp-single-event__related-viewport">
            <ul class="udp-single-event__related-list">
                <?php foreach ( $cards as $card ) : ?>
                    <li class="udp-single-event__related-item">
                        <?php
                        get_template_part(
                            'template-parts/blocks/parts/card-evento',
                            null,
                            array( 'card' => $card, 'theme' => 'dark', 'mode' => 'grid' )
                        );
                        ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="udp-single-event__related-dots"></div>
    </div>
</section>
