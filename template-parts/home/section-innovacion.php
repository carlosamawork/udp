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
    <div class="container">
        <div class="udp-home-innovacion__wrap">
            <div class="udp-home-innovacion__header">
                <h2 class="udp-home-innovacion__titulo udp-home__titulo"><?php echo esc_html( $titulo_seccion ); ?></h2>
                <a href="<?php echo esc_url( $ver_todo_url ); ?>" class="udp-home-innovacion__ver-todo udp-home-innovacion__ver-todo--desktop">
                    Ver todo
                </a>
            </div>

            <div class="js-innovacion-swiper swiper udp-home-innovacion__swiper">
                <div class="swiper-wrapper">
                    <?php foreach ( $posts as $post ) : ?>
                        <?php
                        $thumb_url = get_the_post_thumbnail_url( $post->ID, 'medium_large' );

                        // Etiqueta de categoría sobre la imagen: categoría de la
                        // sección (investigación/innovación); fallback a la 1ª.
                        // Fondo = color ACF del término; texto negro.
                        $cat_label = '';
                        $cat_color = '';
                        $post_cats = get_the_terms( $post->ID, 'category' );
                        if ( ! is_wp_error( $post_cats ) && ! empty( $post_cats ) ) {
                            $chosen = null;
                            foreach ( $post_cats as $pc ) {
                                if ( in_array( $pc->term_id, $cat_ids, true ) ) {
                                    $chosen = $pc;
                                    break;
                                }
                            }
                            if ( ! $chosen ) {
                                $chosen = $post_cats[0];
                            }
                            $cat_label = $chosen->name;
                            $cat_color = function_exists( 'get_field' ) ? (string) get_field( 'color', 'category_' . $chosen->term_id ) : '';
                        }
                        ?>
                        <div class="swiper-slide udp-home-innovacion__slide">
                            <a
                                href="<?php echo esc_url( get_permalink( $post ) ); ?>"
                                class="udp-home-innovacion__card"
                                aria-label="<?php echo esc_attr( get_the_title( $post ) ); ?>"
                            >
                                <?php /* Indicador de categoría ENCIMA de la imagen: fondo = color de
                                         la categoría (default #FF7064), texto negro. Vacío reserva el espacio. */ ?>
                                <span class="udp-home-innovacion__chip"<?php echo $cat_color ? ' style="background-color:' . esc_attr( $cat_color ) . '"' : ''; ?>><?php echo esc_html( $cat_label ); ?></span>

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
        <a href="<?php echo esc_url( $ver_todo_url ); ?>" class="udp-home-innovacion__ver-todo udp-home-innovacion__ver-todo--mobile">
            Ver todo
        </a>
    </div>
</section>
