<?php
/**
 * Block: Botones grandes (grid de big CTAs)
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$titulo   = get_sub_field( 'titulo' );
$eyebrow  = get_sub_field( 'eyebrow' );
$columnas = get_sub_field( 'columnas' ) ?: '3';
$buttons  = get_sub_field( 'buttons' ) ?: array();
$theme    = get_sub_field( 'theme' ) ?: 'dark';

if ( empty( $buttons ) ) {
    return;
}

$container_class = sprintf( 'udp-block-big-buttons udp-block-big-buttons--cols-%s udp-block-big-buttons--%s', esc_attr( $columnas ), esc_attr( $theme ) );
?>
<section class="<?php echo esc_attr( $container_class ); ?>">
    <div class="udp-block-big-buttons__inner">
        <?php if ( $eyebrow || $titulo ) : ?>
            <header class="udp-block-big-buttons__header">
                <?php if ( $eyebrow ) : ?>
                    <p class="udp-block-big-buttons__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
                <?php endif; ?>
                <?php if ( $titulo ) : ?>
                    <h2 class="udp-block-big-buttons__title"><?php echo esc_html( $titulo ); ?></h2>
                <?php endif; ?>
            </header>
        <?php endif; ?>

        <ul class="udp-block-big-buttons__list">
            <?php foreach ( $buttons as $btn ) :
                $label       = $btn['label']       ?? '';
                $descripcion = $btn['descripcion'] ?? '';
                $url         = $btn['url']         ?? '';
                $target      = ! empty( $btn['target_blank'] );
                if ( ! $label || ! $url ) continue;
                $rel = $target ? 'noopener noreferrer' : '';
            ?>
                <li class="udp-block-big-buttons__item">
                    <a
                        class="udp-block-big-buttons__btn"
                        href="<?php echo esc_url( $url ); ?>"
                        <?php if ( $target ) : ?>target="_blank" rel="<?php echo esc_attr( $rel ); ?>"<?php endif; ?>
                    >
                        <span class="udp-block-big-buttons__btn-content">
                            <span class="udp-block-big-buttons__btn-label"><?php echo esc_html( $label ); ?></span>
                            <?php if ( $descripcion ) : ?>
                                <span class="udp-block-big-buttons__btn-desc"><?php echo esc_html( $descripcion ); ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="udp-block-big-buttons__btn-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                                <path d="M6 4h8v8M14 4 4 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
