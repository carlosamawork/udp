<?php
/**
 * Template Name: Noticias (Archive)
 *
 * Page template asignable a la página "Noticias" (ID 97). Renderiza
 * filtros (categoría + año + búsqueda) + hero band (página 1) + grid
 * 2-col de cards horizontales + paginación. Theme dark.
 *
 * Lógica:
 *   - Página 1 sin filtros: featured + 2 side cards + 6 grid (9 total).
 *   - Página 1 con filtros: 9 cards en grid (sin hero).
 *   - Página 2+: 9 cards en grid (sin hero).
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$cat   = isset( $_GET['cat'] ) ? (int) $_GET['cat'] : 0;
$year  = isset( $_GET['year'] ) ? (int) $_GET['year'] : 0;
$s     = isset( $_GET['udp_s'] ) ? sanitize_text_field( wp_unslash( $_GET['udp_s'] ) ) : '';
$paged = max( 1, (int) ( get_query_var( 'paged' ) ?: ( $_GET['paged'] ?? 1 ) ) );

$page_id      = get_the_ID();
$is_first_pg  = ( $paged === 1 );
$has_filters  = ( $cat > 0 || $year > 0 || $s !== '' );
$show_hero    = $is_first_pg && ! $has_filters;

$featured_card = null;
$side_cards    = array();
$grid_cards    = array();
$max_pages     = 0;

if ( $show_hero && function_exists( 'udp_query_noticias' ) && function_exists( 'udp_card_data_from_post' ) ) {
    // Resolver featured: ACF o fallback al más reciente con featured image
    $featured_id = (int) get_field( 'featured_post', $page_id );
    if ( $featured_id <= 0 ) {
        $latest = get_posts( array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'meta_query'     => array(
                array( 'key' => '_thumbnail_id', 'compare' => 'EXISTS' ),
            ),
            'fields'         => 'ids',
        ) );
        $featured_id = ! empty( $latest ) ? (int) $latest[0] : 0;
    }

    if ( $featured_id > 0 ) {
        $featured_post = get_post( $featured_id );
        if ( $featured_post ) {
            $featured_card = udp_card_data_from_post( $featured_post );
        }
    }

    // Side: 2 más recientes excluyendo featured
    $exclude_for_side = $featured_id > 0 ? array( $featured_id ) : array();
    $side_result = udp_query_noticias( array(
        'paged'   => 1,
        'limit'   => 2,
        'exclude' => $exclude_for_side,
    ) );
    $side_cards = $side_result['cards'];

    // Grid: 6 más recientes excluyendo featured + side
    $exclude_for_grid = $exclude_for_side;
    foreach ( $side_cards as $card ) {
        if ( ! empty( $card['post_id'] ) ) {
            $exclude_for_grid[] = (int) $card['post_id'];
        }
    }
    $grid_result = udp_query_noticias( array(
        'paged'   => 1,
        'limit'   => 6,
        'exclude' => $exclude_for_grid,
    ) );
    $grid_cards = $grid_result['cards'];
    $max_pages  = $grid_result['max_pages'];
} else {
    // Page 2+ o con filtros: solo grid 9 sin featured/side
    $grid_result = function_exists( 'udp_query_noticias' )
        ? udp_query_noticias( array(
            'cat'   => $cat,
            'year'  => $year,
            's'     => $s,
            'paged' => $paged,
            'limit' => 9,
        ) )
        : array( 'cards' => array(), 'total' => 0, 'max_pages' => 0, 'paged' => $paged );

    $grid_cards = $grid_result['cards'];
    $max_pages  = $grid_result['max_pages'];
}

get_header();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-noticias-archive' ); ?>>

    <header class="udp-noticias-archive__header">
        <?php
        get_template_part(
            'template-parts/sections/breadcrumb',
            null,
            array( 'page_id' => get_the_ID() )
        );
        ?>
        <h1 class="udp-noticias-archive__title"><?php the_title(); ?></h1>
    </header>

    <hr class="udp-noticias-archive__separator" aria-hidden="true" />

    <?php
    get_template_part(
        'template-parts/archive/noticias-filters',
        null,
        array( 'cat' => $cat, 'year' => $year, 's' => $s )
    );
    ?>

    <hr class="udp-noticias-archive__separator" aria-hidden="true" />

    <?php if ( $show_hero && ( $featured_card || ! empty( $side_cards ) ) ) : ?>
        <?php
        get_template_part(
            'template-parts/archive/noticias-hero',
            null,
            array( 'featured' => $featured_card, 'side' => $side_cards )
        );
        ?>
    <?php endif; ?>

    <?php if ( ! empty( $grid_cards ) ) : ?>
        <ul class="udp-noticias-archive__list">
            <?php foreach ( $grid_cards as $card ) : ?>
                <li class="udp-noticias-archive__item">
                    <?php
                    get_template_part(
                        'template-parts/blocks/parts/card-noticia',
                        null,
                        array( 'card' => $card, 'theme' => 'dark', 'variant' => 'horizontal' )
                    );
                    ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php elseif ( ! $featured_card && empty( $side_cards ) ) : ?>
        <p class="udp-noticias-archive__empty">
            <?php esc_html_e( 'No se encontraron noticias con esos filtros.', 'starter-theme' ); ?>
        </p>
    <?php endif; ?>

    <?php
    get_template_part(
        'template-parts/archive/pagination',
        null,
        array( 'paged' => $paged, 'max_pages' => $max_pages )
    );
    ?>

</article>

<?php
get_footer();
