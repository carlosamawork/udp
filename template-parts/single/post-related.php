<?php
/**
 * Single Post > Te podría interesar
 *
 * 3 posts relacionados por categoría primaria. Si <3, fallback a más recientes.
 *
 * @package Starter_Theme
 *
 * @var array $args ['post_id' => int]
 */
$current_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();
if ( ! $current_id ) {
    return;
}

$primary_term_id = 0;
$terms = get_the_terms( $current_id, 'category' );
if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
    $primary_term_id = (int) $terms[0]->term_id;
}

$base_args = array(
    'post_type'      => 'post',
    'posts_per_page' => 3,
    'post__not_in'   => array( $current_id ),
    'orderby'        => 'date',
    'order'          => 'DESC',
    'no_found_rows'  => true,
);

if ( $primary_term_id ) {
    $args1 = $base_args;
    $args1['tax_query'] = array(
        array( 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => array( $primary_term_id ) ),
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
    $card = function_exists( 'udp_card_data_from_post' ) ? udp_card_data_from_post( $post ) : null;
    if ( $card ) {
        $cards[] = $card;
    }
}

if ( empty( $cards ) ) {
    return;
}
?>
<section class="udp-single-post__related">
    <div class="udp-single-post__related-inner">
        <h2 class="udp-single-post__related-title"><?php esc_html_e( 'Te podría interesar', 'starter-theme' ); ?></h2>
        <ul class="udp-single-post__related-list">
            <?php foreach ( $cards as $card ) : ?>
                <li class="udp-single-post__related-item">
                    <?php
                    get_template_part(
                        'template-parts/blocks/parts/card-noticia',
                        null,
                        array( 'card' => $card, 'theme' => 'light' )
                    );
                    ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
