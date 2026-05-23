<?php
/**
 * Home — Sección 10: Innovación e Investigación
 *
 * Query: post_type=post, categorías 'investigacion' y/o 'innovacion' (OR).
 * Eyebrow: campo ACF 'siglas' del primer término de taxonomía 'facultad' del post.
 * Requiere que las categorías existan con esos slugs.
 *
 * @package starter-bs5
 */

// Resolver IDs dinámicamente para no hardcodear.
$slugs   = [ 'investigacion', 'innovacion' ];
$cat_ids = [];
foreach ( $slugs as $slug ) {
    $term = get_term_by( 'slug', $slug, 'category' );
    if ( $term && ! is_wp_error( $term ) ) {
        $cat_ids[] = $term->term_id;
    }
}

if ( empty( $cat_ids ) ) {
    // Las categorías no existen aún — silencio para no romper el front.
    return;
}

$query = new WP_Query( [
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => 4,
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

$titulo_seccion = get_field( 'innovacion_titulo' ) ?: 'Innovación e Investigación';
?>
<section class="udp-home-innovacion">
    <div class="container">
        <div class="udp-home-innovacion__header">
            <h2 class="udp-home__titulo"><?php echo esc_html( $titulo_seccion ); ?></h2>
            <a href="<?php echo esc_url( home_url( '/noticias/' ) ); ?>" class="udp-home-innovacion__ver-mas">
                Ver todas
            </a>
        </div>

        <div class="udp-home-innovacion__grid row g-4">
            <?php foreach ( $posts as $post ) : ?>
                <?php
                // Eyebrow: siglas de la primera facultad asociada al post.
                $facs   = get_the_terms( $post->ID, 'facultad' );
                $siglas = '';
                if ( ! is_wp_error( $facs ) && ! empty( $facs ) ) {
                    $siglas = get_field( 'siglas', 'facultad_' . $facs[0]->term_id );
                }

                $thumb_url = get_the_post_thumbnail_url( $post->ID, 'medium_large' );
                ?>
                <article class="col-md-6 col-lg-3">
                    <a href="<?php echo esc_url( get_permalink( $post ) ); ?>" class="udp-home-innovacion__card">
                        <?php if ( $thumb_url ) : ?>
                            <div class="udp-home-innovacion__card-img">
                                <img
                                    src="<?php echo esc_url( $thumb_url ); ?>"
                                    alt=""
                                    loading="lazy"
                                    decoding="async"
                                >
                            </div>
                        <?php endif; ?>
                        <div class="udp-home-innovacion__card-body">
                            <?php if ( $siglas ) : ?>
                                <span class="eyebrow udp-home-innovacion__eyebrow"><?php echo esc_html( $siglas ); ?></span>
                            <?php endif; ?>
                            <h3 class="udp-home-innovacion__card-titulo"><?php echo esc_html( get_the_title( $post ) ); ?></h3>
                            <time
                                class="udp-home-innovacion__fecha"
                                datetime="<?php echo esc_attr( get_the_date( 'Y-m-d', $post ) ); ?>"
                            >
                                <?php echo esc_html( get_the_date( 'j \d\e F \d\e Y', $post ) ); ?>
                            </time>
                        </div>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
