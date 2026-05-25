<?php
/**
 * Institucional > Chips bar sticky (todos los breakpoints).
 *
 * Recibe $args['anchors'] (output de udp_institucional_collect_anchors).
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$anchors = is_array( $args['anchors'] ?? null ) ? $args['anchors'] : array();
if ( count( $anchors ) < 2 ) return;
?>
<nav class="udp-inst-chips" aria-label="<?php esc_attr_e( 'Navegación de sección', 'starter-theme' ); ?>">
    <div class="udp-inst-chips__inner">
        <ul class="udp-inst-chips__list" role="list">
            <?php foreach ( $anchors as $a ) : ?>
                <li class="udp-inst-chips__item">
                    <a class="udp-inst-chips__link" href="#<?php echo esc_attr( $a['id'] ); ?>" data-udp-anchor="<?php echo esc_attr( $a['id'] ); ?>">
                        <?php echo esc_html( $a['label'] ); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>
