<?php
/**
 * Template Name: Noticias (Archive)
 *
 * Page template asignable a la página "Noticias" (ID 97). Renderiza
 * filtros (categoría + año + búsqueda) + grid 2-col de cards horizontales
 * + paginación. Reutiliza F4a card-noticia con variant 'horizontal'.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$cat   = isset( $_GET['cat'] ) ? (int) $_GET['cat'] : 0;
$year  = isset( $_GET['year'] ) ? (int) $_GET['year'] : 0;
$s     = isset( $_GET['udp_s'] ) ? sanitize_text_field( wp_unslash( $_GET['udp_s'] ) ) : '';
$paged = max( 1, (int) ( get_query_var( 'paged' ) ?: ( $_GET['paged'] ?? 1 ) ) );

$result = function_exists( 'udp_query_noticias' )
    ? udp_query_noticias( array(
        'cat'   => $cat,
        'year'  => $year,
        's'     => $s,
        'paged' => $paged,
        'limit' => 6,
    ) )
    : array( 'cards' => array(), 'total' => 0, 'max_pages' => 0, 'paged' => 1 );

$cards     = $result['cards'];
$max_pages = $result['max_pages'];

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

    <?php if ( ! empty( $cards ) ) : ?>
        <ul class="udp-noticias-archive__list">
            <?php foreach ( $cards as $card ) : ?>
                <li class="udp-noticias-archive__item">
                    <?php
                    get_template_part(
                        'template-parts/blocks/parts/card-noticia',
                        null,
                        array( 'card' => $card, 'theme' => 'light', 'variant' => 'horizontal' )
                    );
                    ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else : ?>
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
