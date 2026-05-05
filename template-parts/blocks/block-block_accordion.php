<?php
/**
 * Block: Accordion (collapsible list usando <details><summary> HTML5).
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$titulo = get_sub_field( 'titulo' );
$items  = get_sub_field( 'items' ) ?: array();
$theme  = get_sub_field( 'theme' ) ?: 'dark';

if ( empty( $items ) ) {
    return;
}

$container_class = 'udp-block-accordion udp-block-accordion--' . $theme;
?>
<section class="<?php echo esc_attr( $container_class ); ?>">
    <div class="udp-block-accordion__inner">
        <?php if ( $titulo ) : ?>
            <h2 class="udp-block-accordion__title"><?php echo esc_html( $titulo ); ?></h2>
        <?php endif; ?>

        <ul class="udp-block-accordion__list">
            <?php foreach ( $items as $item ) :
                $item_titulo    = $item['titulo']       ?? '';
                $item_contenido = $item['contenido']    ?? '';
                $open_default   = ! empty( $item['open_default'] );
                if ( ! $item_titulo ) continue;
            ?>
                <li class="udp-block-accordion__item">
                    <details class="udp-block-accordion__details" <?php if ( $open_default ) echo 'open'; ?>>
                        <summary class="udp-block-accordion__summary">
                            <span class="udp-block-accordion__summary-title"><?php echo esc_html( $item_titulo ); ?></span>
                            <span class="udp-block-accordion__summary-icon" aria-hidden="true">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                                    <path d="M3 5l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        </summary>
                        <?php if ( $item_contenido ) : ?>
                            <div class="udp-block-accordion__content">
                                <?php echo wp_kses_post( $item_contenido ); ?>
                            </div>
                        <?php endif; ?>
                    </details>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
