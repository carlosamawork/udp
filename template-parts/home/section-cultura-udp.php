<?php
/**
 * Home — Sección 8: Cultura UDP
 *
 * ACF fields: cultura_titulo (text), cultura_texto (textarea), cultura_udp_items (repeater).
 * Sub-fields: cu_nombre, cu_contador (number), cu_imagen (array), cu_link (url).
 *
 * JS: home-cultura-udp.js maneja el hover fade de imágenes.
 *
 * @package starter-bs5
 */

$items = get_field( 'cultura_udp_items' );
$cultura_titulo = get_field( 'cultura_titulo' );
$cultura_texto  = get_field( 'cultura_texto' );

if ( empty( $items ) ) {
    return;
}

$first = $items[0];
?>
<section class="udp-home-cultura-udp js-cultura-udp">
    <?php if ( $cultura_titulo || $cultura_texto ) : ?>
        <div class="udp-home-cultura-udp__header container">
            <?php if ( $cultura_titulo ) : ?>
                <h2 class="udp-home__titulo"><?php echo esc_html( $cultura_titulo ); ?></h2>
            <?php endif; ?>
            <?php if ( $cultura_texto ) : ?>
                <p class="udp-home-cultura-udp__section-texto"><?php echo esc_html( $cultura_texto ); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="udp-home-cultura-udp__media" aria-hidden="true">
        <?php foreach ( $items as $i => $item ) : ?>
            <?php
            $img_url = ! empty( $item['cu_imagen']['url'] ) ? $item['cu_imagen']['url'] : '';
            $img_alt = ! empty( $item['cu_imagen']['alt'] ) ? $item['cu_imagen']['alt'] : '';
            ?>
            <img
                src="<?php echo esc_url( $img_url ); ?>"
                alt="<?php echo esc_attr( $img_alt ); ?>"
                class="udp-home-cultura-udp__img js-cultura-img <?php echo $i === 0 ? 'is-active' : ''; ?>"
                data-index="<?php echo esc_attr( $i ); ?>"
                loading="<?php echo $i === 0 ? 'eager' : 'lazy'; ?>"
                decoding="async"
            >
        <?php endforeach; ?>
    </div>

    <div class="udp-home-cultura-udp__panel">
        <ul class="udp-home-cultura-udp__lista list-unstyled" role="list">
            <?php foreach ( $items as $i => $item ) : ?>
                <li
                    class="udp-home-cultura-udp__item js-cultura-item <?php echo $i === 0 ? 'is-active' : ''; ?>"
                    data-index="<?php echo esc_attr( $i ); ?>"
                >
                    <a
                        href="<?php echo esc_url( $item['cu_link'] ?? '#' ); ?>"
                        class="udp-home-cultura-udp__link"
                    >
                        <span class="udp-home-cultura-udp__nombre">
                            <?php echo esc_html( $item['cu_nombre'] ); ?>
                        </span>
                        <?php if ( ! empty( $item['cu_contador'] ) ) : ?>
                            <span class="udp-home-cultura-udp__contador">
                                <?php echo esc_html( $item['cu_contador'] ); ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
