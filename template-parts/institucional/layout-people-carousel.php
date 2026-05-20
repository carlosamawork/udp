<?php
/**
 * Layout C — Carrusel de personas (Swiper)
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$data   = $args['data']   ?? array();
$anchor = $args['anchor'] ?? null;

$title    = $data['title']    ?? '';
$subtitle = $data['subtitle'] ?? '';
$personas = is_array( $data['personas'] ?? null ) ? $data['personas'] : array();

$id = $anchor['id'] ?? '';

if ( empty( $personas ) ) return;
?>
<section
    <?php if ( $id ) : ?>id="<?php echo esc_attr( $id ); ?>"<?php endif; ?>
    class="udp-inst-section udp-inst-people"
    style="scroll-margin-top: var(--udp-anchor-offset, 168px);"
>
    <div class="udp-inst-people__inner">
        <?php if ( $title ) : ?>
            <header class="udp-inst-people__header">
                <h2 class="udp-inst-people__title"><?php echo esc_html( $title ); ?></h2>
                <?php if ( $subtitle ) : ?>
                    <p class="udp-inst-people__subtitle"><?php echo esc_html( $subtitle ); ?></p>
                <?php endif; ?>
            </header>
        <?php endif; ?>

        <div class="udp-inst-people__carousel">
            <div class="swiper" role="region" aria-label="<?php echo esc_attr( $title ? $title : __( 'Carrusel de personas', 'starter-theme' ) ); ?>">
                <ul class="swiper-wrapper">
                    <?php foreach ( $personas as $persona ) :
                        $foto   = is_array( $persona['foto'] ?? null ) ? $persona['foto'] : array();
                        $nombre = $persona['nombre'] ?? '';
                        $cargo  = $persona['cargo']  ?? '';
                        if ( ! $nombre ) continue;
                    ?>
                        <li class="swiper-slide udp-inst-people__card">
                            <?php if ( ! empty( $foto['url'] ) ) : ?>
                                <div class="udp-inst-people__photo">
                                    <img src="<?php echo esc_url( $foto['url'] ); ?>" alt="<?php echo esc_attr( $foto['alt'] ?? $nombre ); ?>" loading="lazy">
                                </div>
                            <?php endif; ?>
                            <div class="udp-inst-people__info">
                                <p class="udp-inst-people__name"><?php echo esc_html( $nombre ); ?></p>
                                <?php if ( $cargo ) : ?>
                                    <p class="udp-inst-people__role"><?php echo esc_html( $cargo ); ?></p>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</section>
