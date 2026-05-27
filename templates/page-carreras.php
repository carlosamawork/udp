<?php
/**
 * Template Name: Carreras (Archive)
 *
 * Page template asignable a "Carreras" (ID 12). Theme dark.
 * Mosaico 5-col de cards carrera con eyebrow facultad. Filtros
 * legacy: facultad dropdown + udp_s.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$facultad = isset( $_GET['udp_facultad'] ) ? (int) $_GET['udp_facultad'] : 0;
$carrera  = isset( $_GET['udp_carrera'] ) ? (int) $_GET['udp_carrera'] : '';
$s        = isset( $_GET['udp_s'] )        ? sanitize_text_field( wp_unslash( $_GET['udp_s'] ) ) : '';

$cards = function_exists( 'udp_query_carreras' )
    ? udp_query_carreras( array( 'facultad' => $facultad, 'carrera' => $carrera, 's' => $s ) )
    : array();

get_header();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-carreras-archive' ); ?>>

    <header class="udp-carreras-archive__header">
        <?php
        get_template_part( 'template-parts/sections/breadcrumb', null, array( 'page_id' => get_the_ID() ) );
        ?>
        <h1 class="udp-carreras-archive__title"><?php the_title(); ?></h1>

        <?php $intro = get_the_content(); ?>
        <?php if ( $intro ) : ?>
            <div class="udp-carreras-archive__intro">
                <?php echo apply_filters( 'the_content', $intro ); ?>
            </div>
        <?php endif; ?>
    </header>

    <hr class="udp-carreras-archive__separator" aria-hidden="true" />

    <?php
    get_template_part(
        'template-parts/archive/carreras-filters',
        null,
        array( 'facultad' => $facultad, 's' => $s )
    );
    ?>

    <hr class="udp-carreras-archive__separator" aria-hidden="true" />

    <?php if ( ! empty( $cards ) ) : ?>
        <ul class="udp-carreras-archive__list">
            <?php foreach ( $cards as $card ) : ?>
                <li class="udp-carreras-archive__item">
                    <?php
                    get_template_part(
                        'template-parts/blocks/parts/card-mosaic',
                        null,
                        array( 'card' => $card, 'theme' => 'dark' )
                    );
                    ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <p class="udp-carreras-archive__empty">
            <?php esc_html_e( 'No se encontraron carreras con esos filtros.', 'starter-theme' ); ?>
        </p>
    <?php endif; ?>

</article>

<?php
get_footer();
