<?php
/**
 * Template Name: Centros (Archive)
 *
 * Page template asignable a "Centros Interdisciplinarios" (ID 16).
 * Theme dark, mosaico 5-col reusando card-mosaic. Sin filtros.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$cards = function_exists( 'udp_query_centros' ) ? udp_query_centros() : array();

get_header();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-centros-archive' ); ?>>

    <header class="udp-centros-archive__header">
        <?php
        get_template_part( 'template-parts/sections/breadcrumb', null, array( 'page_id' => get_the_ID() ) );
        ?>
        <h1 class="udp-centros-archive__title"><?php the_title(); ?></h1>

        <?php $intro = get_the_content(); ?>
        <?php if ( $intro ) : ?>
            <div class="udp-centros-archive__intro">
                <?php echo apply_filters( 'the_content', $intro ); ?>
            </div>
        <?php endif; ?>
    </header>

    <hr class="udp-centros-archive__separator" aria-hidden="true" />

    <?php if ( ! empty( $cards ) ) : ?>
        <ul class="udp-centros-archive__list">
            <?php foreach ( $cards as $card ) : ?>
                <li class="udp-centros-archive__item">
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
        <p class="udp-centros-archive__empty">
            <?php esc_html_e( 'No hay centros para mostrar.', 'starter-theme' ); ?>
        </p>
    <?php endif; ?>

</article>

<?php
get_footer();
