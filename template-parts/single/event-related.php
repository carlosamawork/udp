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

$base_args = array(
    'post_type'      => 'agenda',
    'posts_per_page' => 3,
    'post__not_in'   => array( $current_id ),
    'meta_key'       => 'fecha',
    'orderby'        => 'meta_value',
    'order'          => 'ASC',
    'no_found_rows'  => true,
);

if ( $primary_facultad ) {
    $args1 = $base_args;
    $args1['tax_query'] = array(
        array( 'taxonomy' => 'facultad', 'field' => 'term_id', 'terms' => array( $primary_facultad ) ),
    );
    $q = new WP_Query( $args1 );
    $posts = $q->posts;
} else {
    $posts = array();
}

if ( count( $posts ) < 3 ) {
    $needed   = 3 - count( $posts );
    $exclude  = array_merge( array( $current_id ), wp_list_pluck( $posts, 'ID' ) );
    $args2    = $base_args;
    $args2['post__not_in']   = $exclude;
    $args2['posts_per_page'] = $needed;
    $q2 = new WP_Query( $args2 );
    $posts = array_merge( $posts, $q2->posts );
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
<section class="udp-single-event__related">
    <div class="udp-single-event__related-inner">
        <h2 class="udp-single-event__related-title"><?php esc_html_e( 'Te podría interesar', 'starter-theme' ); ?></h2>
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
</section>
