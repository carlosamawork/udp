<?php
/**
 * Template Name: Eventos (Archive)
 *
 * Page template asignable a la página "Agenda" (ID 91). Renderiza
 * filtros (facultad + año + búsqueda) + view toggle (grid|list) +
 * cards en el modo seleccionado + paginación. Theme dark.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$facultad = isset( $_GET['udp_facultad'] ) ? (int) $_GET['udp_facultad'] : 0;
$year     = isset( $_GET['udp_year'] )     ? (int) $_GET['udp_year']     : 0;
$s        = isset( $_GET['udp_s'] )    ? sanitize_text_field( wp_unslash( $_GET['udp_s'] ) ) : '';
$paged    = max( 1, (int) ( get_query_var( 'paged' ) ?: ( $_GET['paged'] ?? 1 ) ) );
$view     = ( isset( $_GET['view'] ) && $_GET['view'] === 'list' ) ? 'list' : 'grid';

$result = function_exists( 'udp_query_agenda' )
    ? udp_query_agenda( array(
        'facultad' => $facultad,
        'year'     => $year,
        's'        => $s,
        'paged'    => $paged,
        'limit'    => $view === 'list' ? 12 : 6,
    ) )
    : array( 'cards' => array(), 'total' => 0, 'max_pages' => 0, 'paged' => 1 );

$cards     = $result['cards'];
$max_pages = $result['max_pages'];

$base_args = array_filter( array(
    'udp_facultad' => $facultad ?: null,
    'udp_year'     => $year     ?: null,
    'udp_s'        => $s        ?: null,
) );
$url_grid = add_query_arg( array_merge( $base_args, array( 'view' => 'grid' ) ), get_permalink( get_the_ID() ) );
$url_list = add_query_arg( array_merge( $base_args, array( 'view' => 'list' ) ), get_permalink( get_the_ID() ) );

get_header();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-eventos-archive udp-eventos-archive--' . esc_attr( $view ) ); ?>>

    <header class="udp-eventos-archive__header">
        <?php
        get_template_part(
            'template-parts/sections/breadcrumb',
            null,
            array( 'page_id' => get_the_ID() )
        );
        ?>
        <h1 class="udp-eventos-archive__title"><?php the_title(); ?></h1>
        <div class="udp-eventos-archive__toggle" role="tablist" aria-label="<?php esc_attr_e( 'Vista de eventos', 'starter-theme' ); ?>">
            <a href="<?php echo esc_url( $url_grid ); ?>" class="udp-eventos-archive__toggle-btn<?php echo $view === 'grid' ? ' udp-eventos-archive__toggle-btn--active' : ''; ?>" aria-label="<?php esc_attr_e( 'Vista grid', 'starter-theme' ); ?>" aria-pressed="<?php echo $view === 'grid' ? 'true' : 'false'; ?>">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><rect x="2" y="2" width="5" height="5" stroke="currentColor" stroke-width="1.4"/><rect x="9" y="2" width="5" height="5" stroke="currentColor" stroke-width="1.4"/><rect x="2" y="9" width="5" height="5" stroke="currentColor" stroke-width="1.4"/><rect x="9" y="9" width="5" height="5" stroke="currentColor" stroke-width="1.4"/></svg>
            </a>
            <a href="<?php echo esc_url( $url_list ); ?>" class="udp-eventos-archive__toggle-btn<?php echo $view === 'list' ? ' udp-eventos-archive__toggle-btn--active' : ''; ?>" aria-label="<?php esc_attr_e( 'Vista lista', 'starter-theme' ); ?>" aria-pressed="<?php echo $view === 'list' ? 'true' : 'false'; ?>">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><line x1="3" y1="4" x2="13" y2="4" stroke="currentColor" stroke-width="1.4"/><line x1="3" y1="8" x2="13" y2="8" stroke="currentColor" stroke-width="1.4"/><line x1="3" y1="12" x2="13" y2="12" stroke="currentColor" stroke-width="1.4"/></svg>
            </a>
        </div>
    </header>

    <hr class="udp-eventos-archive__separator" aria-hidden="true" />

    <?php
    get_template_part(
        'template-parts/archive/eventos-filters',
        null,
        array( 'facultad' => $facultad, 'year' => $year, 's' => $s )
    );
    ?>

    <hr class="udp-eventos-archive__separator" aria-hidden="true" />

    <?php if ( ! empty( $cards ) ) : ?>
        <ul class="udp-eventos-archive__list udp-eventos-archive__list--<?php echo esc_attr( $view ); ?>">
            <?php foreach ( $cards as $card ) : ?>
                <li class="udp-eventos-archive__item">
                    <?php
                    get_template_part(
                        'template-parts/blocks/parts/card-evento',
                        null,
                        array( 'card' => $card, 'theme' => 'dark', 'mode' => $view )
                    );
                    ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <p class="udp-eventos-archive__empty">
            <?php esc_html_e( 'No se encontraron eventos con esos filtros.', 'starter-theme' ); ?>
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
