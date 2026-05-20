<?php
/**
 * Institucional > Rail vertical flotante (desktop ≥992px).
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$anchors = is_array( $args['anchors'] ?? null ) ? $args['anchors'] : array();
if ( count( $anchors ) < 2 ) return;
?>
<nav class="udp-inst-rail" aria-label="<?php esc_attr_e( 'Navegación rápida por sección', 'starter-theme' ); ?>">
    <ul class="udp-inst-rail__list" role="list">
        <?php foreach ( $anchors as $a ) :
            $icon = is_array( $a['icon'] ) ? $a['icon'] : null;
            $aria = sprintf( __( 'Ir a %s', 'starter-theme' ), $a['label'] );
        ?>
            <li class="udp-inst-rail__item">
                <a
                    class="udp-inst-rail__link"
                    href="#<?php echo esc_attr( $a['id'] ); ?>"
                    data-udp-anchor="<?php echo esc_attr( $a['id'] ); ?>"
                    aria-label="<?php echo esc_attr( $aria ); ?>"
                    title="<?php echo esc_attr( $a['label'] ); ?>"
                >
                    <?php if ( $icon && ! empty( $icon['url'] ) ) : ?>
                        <img src="<?php echo esc_url( $icon['url'] ); ?>" alt="" width="32" height="32" loading="lazy">
                    <?php else : ?>
                        <span class="udp-inst-rail__num"><?php echo (int) $a['order']; ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
