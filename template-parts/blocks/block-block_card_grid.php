<?php
/**
 * Block: Card Grid (flexible content layout)
 *
 * Lee sub-fields del row activo de content_blocks, llama a udp_query_cards()
 * y renderiza el grid usando el partial card-noticia.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$titulo   = get_sub_field( 'titulo' );
$eyebrow  = get_sub_field( 'eyebrow' );
$fuente   = get_sub_field( 'fuente' ) ?: 'manual';
$columnas = get_sub_field( 'columnas' ) ?: '3col';
$theme    = get_sub_field( 'theme' ) ?: 'dark';

$args = array( 'source' => $fuente );

if ( $fuente === 'manual' ) {
    $args['manual_cards'] = get_sub_field( 'cards_manuales' ) ?: array();
    $args['limit']        = 24;
} else {
    $filtros = get_sub_field( 'filtros' ) ?: array();
    $args['taxonomies'] = isset( $filtros['taxonomias'] ) && is_array( $filtros['taxonomias'] ) ? $filtros['taxonomias'] : array();
    $args['limit']      = isset( $filtros['n_items'] ) ? (int) $filtros['n_items'] : 6;
    $args['orden']      = $filtros['orden'] ?? 'date_desc';
}

$result = function_exists( 'udp_query_cards' ) ? udp_query_cards( $args ) : array( 'cards' => array() );
$cards  = $result['cards'];

if ( empty( $cards ) ) {
    return;
}

$container_class = sprintf( 'udp-card-grid udp-card-grid--%s udp-card-grid--%s', $columnas, $theme );
?>
<section class="<?php echo esc_attr( $container_class ); ?>">
    <div class="udp-card-grid__inner">
        <?php if ( $eyebrow || $titulo ) : ?>
            <header class="udp-card-grid__header">
                <?php if ( $eyebrow ) : ?>
                    <p class="udp-card-grid__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
                <?php endif; ?>
                <?php if ( $titulo ) : ?>
                    <h2 class="udp-card-grid__title"><?php echo esc_html( $titulo ); ?></h2>
                <?php endif; ?>
            </header>
        <?php endif; ?>

        <ul class="udp-card-grid__list">
            <?php foreach ( $cards as $card ) : ?>
                <li class="udp-card-grid__item">
                    <?php
                    get_template_part(
                        'template-parts/blocks/parts/card-noticia',
                        null,
                        array( 'card' => $card, 'theme' => $theme )
                    );
                    ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
