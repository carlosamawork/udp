<?php
/**
 * Block: People List — grid de personas (foto + nombre + cargo + descripcion).
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$titulo   = get_sub_field( 'titulo' );
$eyebrow  = get_sub_field( 'eyebrow' );
$columnas = get_sub_field( 'columnas' ) ?: '4';
$personas = get_sub_field( 'personas' ) ?: array();
$theme    = get_sub_field( 'theme' ) ?: 'light';

if ( empty( $personas ) ) {
    return;
}

$container_class = sprintf( 'udp-block-people-list udp-block-people-list--cols-%s udp-block-people-list--%s', esc_attr( $columnas ), esc_attr( $theme ) );
?>
<section class="<?php echo esc_attr( $container_class ); ?>">
    <div class="udp-block-people-list__inner">
        <?php if ( $eyebrow || $titulo ) : ?>
            <header class="udp-block-people-list__header">
                <?php if ( $eyebrow ) : ?>
                    <p class="udp-block-people-list__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
                <?php endif; ?>
                <?php if ( $titulo ) : ?>
                    <h2 class="udp-block-people-list__title"><?php echo esc_html( $titulo ); ?></h2>
                <?php endif; ?>
            </header>
        <?php endif; ?>

        <ul class="udp-block-people-list__list">
            <?php foreach ( $personas as $persona ) :
                $foto        = is_array( $persona['foto'] ?? null ) ? $persona['foto'] : array();
                $nombre      = $persona['nombre'] ?? '';
                $cargo       = $persona['cargo']  ?? '';
                $descripcion = $persona['descripcion'] ?? '';
                if ( ! $nombre ) continue;
                $foto_url = $foto['sizes']['medium'] ?? ( $foto['url'] ?? '' );
                $foto_alt = $foto['alt'] ?? $nombre;
                $has_foto = $foto_url !== '';
            ?>
                <li class="udp-block-people-list__item">
                    <figure class="udp-block-people-list__media<?php echo $has_foto ? '' : ' udp-block-people-list__media--placeholder'; ?>">
                        <?php if ( $has_foto ) : ?>
                            <img src="<?php echo esc_url( $foto_url ); ?>" alt="<?php echo esc_attr( $foto_alt ); ?>" loading="lazy" decoding="async" />
                        <?php endif; ?>
                    </figure>
                    <div class="udp-block-people-list__body">
                        <h3 class="udp-block-people-list__nombre"><?php echo esc_html( $nombre ); ?></h3>
                        <?php if ( $cargo ) : ?>
                            <p class="udp-block-people-list__cargo"><?php echo esc_html( $cargo ); ?></p>
                        <?php endif; ?>
                        <?php if ( $descripcion ) : ?>
                            <p class="udp-block-people-list__desc"><?php echo esc_html( $descripcion ); ?></p>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
