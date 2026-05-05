<?php
/**
 * Block: Premios List — lista de premios con año destacado.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$titulo  = get_sub_field( 'titulo' );
$eyebrow = get_sub_field( 'eyebrow' );
$premios = get_sub_field( 'premios' ) ?: array();
$orden   = get_sub_field( 'orden' ) ?: 'desc';
$theme   = get_sub_field( 'theme' ) ?: 'light';

if ( empty( $premios ) ) {
    return;
}

// Sort según orden (cuando no es 'manual')
if ( $orden === 'desc' ) {
    usort( $premios, function ( $a, $b ) {
        return ( (int) ( $b['ano'] ?? 0 ) ) - ( (int) ( $a['ano'] ?? 0 ) );
    } );
} elseif ( $orden === 'asc' ) {
    usort( $premios, function ( $a, $b ) {
        return ( (int) ( $a['ano'] ?? 0 ) ) - ( (int) ( $b['ano'] ?? 0 ) );
    } );
}

$container_class = 'udp-block-premios-list udp-block-premios-list--' . $theme;
?>
<section class="<?php echo esc_attr( $container_class ); ?>">
    <div class="udp-block-premios-list__inner">
        <?php if ( $eyebrow || $titulo ) : ?>
            <header class="udp-block-premios-list__header">
                <?php if ( $eyebrow ) : ?>
                    <p class="udp-block-premios-list__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
                <?php endif; ?>
                <?php if ( $titulo ) : ?>
                    <h2 class="udp-block-premios-list__title"><?php echo esc_html( $titulo ); ?></h2>
                <?php endif; ?>
            </header>
        <?php endif; ?>

        <ul class="udp-block-premios-list__list">
            <?php foreach ( $premios as $p ) :
                $ano         = (int) ( $p['ano'] ?? 0 );
                $premio      = $p['titulo']      ?? '';
                $persona     = $p['persona']     ?? '';
                $descripcion = $p['descripcion'] ?? '';
                if ( ! $premio ) continue;
            ?>
                <li class="udp-block-premios-list__item">
                    <div class="udp-block-premios-list__year">
                        <?php echo $ano > 0 ? esc_html( $ano ) : ''; ?>
                    </div>
                    <div class="udp-block-premios-list__body">
                        <h3 class="udp-block-premios-list__premio"><?php echo esc_html( $premio ); ?></h3>
                        <?php if ( $persona ) : ?>
                            <p class="udp-block-premios-list__persona"><?php echo esc_html( $persona ); ?></p>
                        <?php endif; ?>
                        <?php if ( $descripcion ) : ?>
                            <p class="udp-block-premios-list__desc"><?php echo esc_html( $descripcion ); ?></p>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
