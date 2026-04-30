<?php
/**
 * Template Name: Facultades (Mosaic)
 *
 * Page template asignable a "Facultades" (ID 14). Mosaico 5-col de
 * cards facultad. Tema dark.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$cards = function_exists( 'udp_query_facultades' ) ? udp_query_facultades() : array();

get_header();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-facultades-archive' ); ?>>

    <header class="udp-facultades-archive__header">
        <?php
        get_template_part(
            'template-parts/sections/breadcrumb',
            null,
            array( 'page_id' => get_the_ID() )
        );
        ?>
        <h1 class="udp-facultades-archive__title"><?php the_title(); ?></h1>

        <?php $intro = get_the_content(); ?>
        <?php if ( $intro ) : ?>
            <div class="udp-facultades-archive__intro">
                <?php echo apply_filters( 'the_content', $intro ); ?>
            </div>
        <?php endif; ?>
    </header>

    <hr class="udp-facultades-archive__separator" aria-hidden="true" />

    <?php if ( ! empty( $cards ) ) : ?>
        <ul class="udp-facultades-archive__list">
            <?php foreach ( $cards as $card ) : ?>
                <li class="udp-facultades-archive__item">
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
        <p class="udp-facultades-archive__empty">
            <?php esc_html_e( 'No hay facultades para mostrar.', 'starter-theme' ); ?>
        </p>
    <?php endif; ?>

</article>

<?php
get_footer();
