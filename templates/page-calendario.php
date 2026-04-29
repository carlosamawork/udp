<?php
/**
 * Template Name: Calendario Académico
 *
 * Page template asignable a la página "Calendario Académico" (ID 74).
 * Sidebar sticky año + meses anchor. Main intro + secciones por mes.
 * Filtros publico-udp + tipo-udp + udp_s. Sin paginación — un año por página.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$publico = isset( $_GET['udp_publico'] ) ? (int) $_GET['udp_publico'] : 0;
$tipo    = isset( $_GET['udp_tipo'] )    ? (int) $_GET['udp_tipo']    : 0;
$year    = isset( $_GET['udp_year'] )    ? (int) $_GET['udp_year']    : (int) date( 'Y' );
$s       = isset( $_GET['udp_s'] )       ? sanitize_text_field( wp_unslash( $_GET['udp_s'] ) ) : '';

$result = function_exists( 'udp_query_calendario' )
    ? udp_query_calendario( array(
        'publico' => $publico,
        'tipo'    => $tipo,
        'year'    => $year,
        's'       => $s,
    ) )
    : array( 'entries_by_month' => array(), 'total' => 0, 'year' => $year );

$entries_by_month = $result['entries_by_month'];

$months_es = array(
    '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
    '05' => 'Mayo',  '06' => 'Junio',   '07' => 'Julio', '08' => 'Agosto',
    '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre',
);

get_header();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-calendario-archive' ); ?>>

    <header class="udp-calendario-archive__header">
        <?php
        get_template_part(
            'template-parts/sections/breadcrumb',
            null,
            array( 'page_id' => get_the_ID() )
        );
        ?>
        <h1 class="udp-calendario-archive__title"><?php the_title(); ?></h1>
    </header>

    <hr class="udp-calendario-archive__separator" aria-hidden="true" />

    <?php
    get_template_part(
        'template-parts/archive/calendario-filters',
        null,
        array( 'publico' => $publico, 'tipo' => $tipo, 's' => $s, 'year' => $year )
    );
    ?>

    <hr class="udp-calendario-archive__separator" aria-hidden="true" />

    <div class="udp-calendario-archive__body">

        <aside class="udp-calendario-archive__sidebar">
            <?php
            get_template_part(
                'template-parts/archive/calendario-sidebar',
                null,
                array(
                    'year'             => $year,
                    'publico'          => $publico,
                    'tipo'             => $tipo,
                    's'                => $s,
                    'entries_by_month' => $entries_by_month,
                    'months_es'        => $months_es,
                )
            );
            ?>
        </aside>

        <main class="udp-calendario-archive__main">
            <?php $intro = get_the_content(); ?>
            <?php if ( $intro ) : ?>
                <div class="udp-calendario-archive__intro">
                    <?php echo apply_filters( 'the_content', $intro ); ?>
                </div>
            <?php endif; ?>

            <?php if ( empty( $entries_by_month ) ) : ?>
                <p class="udp-calendario-archive__empty">
                    <?php esc_html_e( 'No hay fechas registradas en este año con los filtros aplicados.', 'starter-theme' ); ?>
                </p>
            <?php else : ?>
                <?php foreach ( $entries_by_month as $month_num => $entries ) :
                    $month_name = $months_es[ $month_num ] ?? '';
                    $month_slug = sanitize_title( $month_name );
                ?>
                    <?php
                    get_template_part(
                        'template-parts/archive/calendario-month-section',
                        null,
                        array(
                            'month_name' => $month_name,
                            'month_slug' => $month_slug,
                            'entries'    => $entries,
                        )
                    );
                    ?>
                <?php endforeach; ?>
            <?php endif; ?>

            <a href="#top" class="udp-calendario-archive__back-to-top">
                <?php esc_html_e( '↑ Volver arriba', 'starter-theme' ); ?>
            </a>
        </main>

    </div>

</article>

<?php
get_footer();
