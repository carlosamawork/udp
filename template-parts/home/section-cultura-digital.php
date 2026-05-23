<?php
/**
 * Home — Sección 9: Cultura Digital
 *
 * ACF fields: cd_titulo, cd_texto, cd_items (repeater).
 * Sub-fields de cd_items: cd_item_titulo, cd_item_imagen (array),
 *                         cd_item_recuento (text), cd_item_url (url).
 *
 * JS: home-cultura-digital.js — Swiper freeMode con drag libre.
 *
 * @package starter-bs5
 */

$titulo = get_field( 'cd_titulo' );
$texto  = get_field( 'cd_texto' );
$items  = get_field( 'cd_items' );

if ( ! $titulo && ! $texto && empty( $items ) ) {
    return;
}
?>
<section class="udp-home-cultura-digital">
    <div class="udp-home-cultura-digital__layout">
        <div class="udp-home-cultura-digital__sidebar">
            <?php if ( $titulo ) : ?>
                <h2 class="udp-home__titulo"><?php echo esc_html( $titulo ); ?></h2>
            <?php endif; ?>
            <?php if ( $texto ) : ?>
                <p class="udp-home-cultura-digital__texto"><?php echo esc_html( $texto ); ?></p>
            <?php endif; ?>
        </div>

        <?php if ( ! empty( $items ) ) : ?>
            <div class="udp-home-cultura-digital__slider js-cultura-digital-swiper swiper">
                <div class="swiper-wrapper">
                    <?php foreach ( $items as $item ) : ?>
                        <?php
                        $img_url  = ! empty( $item['cd_item_imagen']['url'] ) ? $item['cd_item_imagen']['url'] : '';
                        $img_alt  = ! empty( $item['cd_item_imagen']['alt'] ) ? $item['cd_item_imagen']['alt'] : '';
                        $card_url = ! empty( $item['cd_item_url'] ) ? $item['cd_item_url'] : '#';
                        ?>
                        <div class="swiper-slide udp-home-cultura-digital__slide">
                            <a
                                href="<?php echo esc_url( $card_url ); ?>"
                                class="udp-home-cultura-digital__card"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <?php if ( $img_url ) : ?>
                                    <div class="udp-home-cultura-digital__card-img">
                                        <img
                                            src="<?php echo esc_url( $img_url ); ?>"
                                            alt="<?php echo esc_attr( $img_alt ); ?>"
                                            loading="lazy"
                                            decoding="async"
                                        >
                                    </div>
                                <?php endif; ?>
                                <div class="udp-home-cultura-digital__card-footer">
                                    <?php if ( ! empty( $item['cd_item_titulo'] ) ) : ?>
                                        <p class="udp-home-cultura-digital__card-titulo">
                                            <?php echo esc_html( $item['cd_item_titulo'] ); ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $item['cd_item_recuento'] ) ) : ?>
                                        <p class="udp-home-cultura-digital__card-recuento">
                                            <?php echo esc_html( $item['cd_item_recuento'] ); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
