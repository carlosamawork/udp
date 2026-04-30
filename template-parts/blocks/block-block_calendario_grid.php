<?php
/**
 * Block: Calendario Grid (flexible content layout)
 *
 * Renderiza N entries del CPT calendario filtradas por año + mes + taxonomías.
 * Reusa entry-calendario.php partial.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$titulo  = get_sub_field( 'titulo' );
$eyebrow = get_sub_field( 'eyebrow' );
$year    = (int) get_sub_field( 'year' );
$mes     = (string) get_sub_field( 'mes' );
$n_items = (int) get_sub_field( 'n_items' );
$theme   = get_sub_field( 'theme' ) ?: 'dark';

$filtros = get_sub_field( 'filtros' ) ?: array();
$tipo    = isset( $filtros['tipo'] ) ? (int) $filtros['tipo'] : 0;
$publico = isset( $filtros['publico'] ) ? (int) $filtros['publico'] : 0;

if ( $year <= 0 ) {
    $year = (int) date( 'Y' );
}
if ( $n_items <= 0 ) {
    $n_items = 10;
}

$entries = function_exists( 'udp_query_calendario_flat' )
    ? udp_query_calendario_flat( array(
        'year'    => $year,
        'mes'     => $mes,
        'tipo'    => $tipo,
        'publico' => $publico,
        'limit'   => $n_items,
    ) )
    : array();

if ( empty( $entries ) ) {
    return;
}

$container_class = 'udp-block-calendario-grid udp-block-calendario-grid--' . $theme;
?>
<section class="<?php echo esc_attr( $container_class ); ?>">
    <div class="udp-block-calendario-grid__inner">
        <?php if ( $titulo || $eyebrow ) : ?>
            <header class="udp-block-calendario-grid__header">
                <?php if ( $eyebrow ) : ?>
                    <p class="udp-block-calendario-grid__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
                <?php endif; ?>
                <?php if ( $titulo ) : ?>
                    <h2 class="udp-block-calendario-grid__title"><?php echo esc_html( $titulo ); ?></h2>
                <?php endif; ?>
            </header>
        <?php endif; ?>

        <ul class="udp-block-calendario-grid__list">
            <?php foreach ( $entries as $entry ) : ?>
                <?php
                get_template_part(
                    'template-parts/blocks/parts/entry-calendario',
                    null,
                    array( 'entry' => $entry )
                );
                ?>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
