<?php
/**
 * Template Name: Concursos académicos (Archive)
 *
 * Page template asignable a la página "Concursos Académicos" (ID 76).
 * Hero light + purple/blue header. Filters facultad + udp_s.
 * Grid 2-col cards horizontales (card-noticia variant=horizontal theme=light).
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$facultad = isset( $_GET['udp_facultad'] ) ? (int) $_GET['udp_facultad'] : 0;
$s        = isset( $_GET['udp_s'] )        ? sanitize_text_field( wp_unslash( $_GET['udp_s'] ) ) : '';
$paged    = max( 1, (int) ( get_query_var( 'paged' ) ?: ( $_GET['paged'] ?? 1 ) ) );

$result = function_exists( 'udp_query_concursos' )
    ? udp_query_concursos( array(
        'facultad'        => $facultad,
        's'               => $s,
        'paged'           => $paged,
        'limit'           => 6,
        'need_pagination' => true,
    ) )
    : array( 'cards' => array(), 'total' => 0, 'max_pages' => 0, 'paged' => 1 );

$cards     = $result['cards'];
$max_pages = $result['max_pages'];

get_header();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-concursos-archive' ); ?>>

    <header class="udp-concursos-archive__header">
        <div class="udp-concursos-archive__header-inner">
            <?php
            get_template_part(
                'template-parts/sections/breadcrumb',
                null,
                array( 'page_id' => get_the_ID() )
            );
            ?>
            <h1 class="udp-concursos-archive__title"><?php the_title(); ?></h1>
        </div>
    </header>

    <?php
    get_template_part(
        'template-parts/archive/concursos-filters',
        null,
        array( 'facultad' => $facultad, 's' => $s )
    );
    ?>

    <hr class="udp-concursos-archive__separator" aria-hidden="true" />

    <?php if ( ! empty( $cards ) ) : ?>
        <ul class="udp-concursos-archive__list">
            <?php foreach ( $cards as $card ) : ?>
                <li class="udp-concursos-archive__item">
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
        <p class="udp-concursos-archive__empty">
            <?php esc_html_e( 'No se encontraron concursos con esos filtros.', 'starter-theme' ); ?>
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
