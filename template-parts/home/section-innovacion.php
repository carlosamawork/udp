<?php
/**
 * Home — Sección 10: Innovación e Investigación
 *
 * Carousel horizontal con drag (Swiper freeMode) de posts de categorías
 * 'investigacion' e 'innovacion'. Acotado al container con overflow hidden.
 * Eyebrow chip: siglas ACF del primer término de taxonomía 'facultad'.
 *
 * JS: home-innovacion.js — Swiper freeMode con drag libre.
 *
 * @package starter-bs5
 */

$post_id = $args['post_id'] ?? (int) get_option( 'page_on_front' );

$slugs   = [ 'investigacion', 'innovacion' ];
$cat_ids = [];
foreach ( $slugs as $slug ) {
    $term = get_term_by( 'slug', $slug, 'category' );
    if ( $term && ! is_wp_error( $term ) ) {
        $cat_ids[] = $term->term_id;
    }
}

if ( empty( $cat_ids ) ) {
    return;
}

$query = new WP_Query( [
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => 8,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'no_found_rows'  => true,
    'tax_query'      => [
        [
            'taxonomy' => 'category',
            'field'    => 'term_id',
            'terms'    => $cat_ids,
            'operator' => 'IN',
        ],
    ],
] );

if ( ! $query->have_posts() ) {
    return;
}

$posts = $query->posts;
wp_reset_postdata();

$titulo_seccion = get_field( 'innovacion_titulo', $post_id ) ?: 'Innovación e Investigación';

// Construir URL de "Ver todo" con las mismas categorías que filtra la sección.
$base_url     = get_field( 'innovacion_url', $post_id ) ?: home_url( '/noticias/' );
$ver_todo_ids = array();
foreach ( $slugs as $slug ) {
    $t = get_term_by( 'slug', $slug, 'category' );
    if ( $t && ! is_wp_error( $t ) ) {
        $ver_todo_ids[] = $t->term_id;
    }
}
$ver_todo_url = ! empty( $ver_todo_ids )
    ? add_query_arg( 'udp_cats', implode( ',', $ver_todo_ids ), $base_url )
    : $base_url;
?>
<section class="udp-home-innovacion">
    <div class="container udp-home-innovacion__wrap">
        <div class="udp-home-innovacion__header">
            <h2 class="udp-home-innovacion__titulo udp-home__titulo"><?php echo esc_html( $titulo_seccion ); ?></h2>
            <a href="<?php echo esc_url( $ver_todo_url ); ?>" class="udp-home-innovacion__ver-todo">
                Ver todo
            </a>
        </div>

        <div class="js-innovacion-swiper swiper udp-home-innovacion__swiper">
            <div class="swiper-wrapper">
                <?php foreach ( $posts as $post ) : ?>
                    <?php
                    $facs   = get_the_terms( $post->ID, 'facultad' );
                    $siglas = '';
                    if ( ! is_wp_error( $facs ) && ! empty( $facs ) ) {
                        $siglas = (string) get_field( 'siglas', 'facultad_' . $facs[0]->term_id );
                    }

                    $thumb_url = get_the_post_thumbnail_url( $post->ID, 'medium_large' );
                    ?>
                    <div class="swiper-slide udp-home-innovacion__slide">
                        <a
                            href="<?php echo esc_url( get_permalink( $post ) ); ?>"
                            class="udp-home-innovacion__card"
                            aria-label="<?php echo esc_attr( get_the_title( $post ) ); ?>"
                        >
                            <?php /* Chip siempre presente — vacío reserva el espacio */ ?>
                            <span class="udp-home-innovacion__chip"><?php echo esc_html( $siglas ); ?></span>

                            <div class="udp-home-innovacion__card-media">
                                <div class="udp-home-innovacion__card-img">
                                    <?php if ( $thumb_url ) : ?>
                                        <img
                                            src="<?php echo esc_url( $thumb_url ); ?>"
                                            alt=""
                                            loading="lazy"
                                            decoding="async"
                                        >
                                    <?php else : ?>
                                        <div class="udp-media-placeholder"></div>
                                    <?php endif; ?>
                                </div>

                                <p class="udp-home-innovacion__card-titulo">
                                    <?php echo esc_html( get_the_title( $post ) ); ?>
                                </p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
