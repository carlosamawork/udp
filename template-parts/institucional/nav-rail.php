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
            $icon  = is_array( $a['icon'] ?? null ) ? $a['icon'] : null;
            $order = (int) ( $a['order'] ?? 0 );
            $aria  = sprintf( __( 'Ir a %s', 'starter-theme' ), $a['label'] );
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
                    <?php elseif ( $order === 0 ) : ?>
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M3 9l7-6 7 6v8a1 1 0 01-1 1h-4v-5H8v5H4a1 1 0 01-1-1V9z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    <?php else : ?>
                        <span class="udp-inst-rail__num"><?php echo $order; ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
