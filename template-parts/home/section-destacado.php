<?php
/**
 * Home — Sección 6: Destacado azul (panel + foto)
 *
 * Layout: panel azul $brand-blue a izquierda (flex:1) + imagen cuadrada a derecha.
 * Container en __inner — consistente con el resto de secciones home.
 *
 * ACF fields: destacado_titulo (wysiwyg), destacado_descripcion (wysiwyg),
 *             destacado_link_texto, destacado_link_url, destacado_link_externo (bool),
 *             destacado_imagen (array).
 *
 * @package starter-bs5
 */

$post_id = $args['post_id'] ?? (int) get_option( 'page_on_front' );

$titulo      = get_field( 'destacado_titulo', $post_id );
$descripcion = get_field( 'destacado_descripcion', $post_id );
$link_texto    = get_field( 'destacado_link_texto', $post_id );
$link_url      = get_field( 'destacado_link_url', $post_id );
$link_externo  = get_field( 'destacado_link_externo', $post_id );
$imagen        = get_field( 'destacado_imagen', $post_id );

// Fallback de desarrollo — asegura que la sección sea visible aunque la BD esté vacía.
if ( ! $titulo ) {
    return;
}

$img_url = ! empty( $imagen['url'] ) ? $imagen['url'] : '';
$img_alt = ! empty( $imagen['alt'] ) ? $imagen['alt'] : '';
?>
<section class="udp-home-destacado">
    <div class="udp-home-destacado__inner container">

        <div class="udp-home-destacado__content">
            <div class="udp-home-destacado__titulo">
                <?php echo wp_kses_post( $titulo ); ?>
            </div>

            <div class="udp-home-destacado__body">
                <?php if ( $descripcion ) : ?>
                    <div class="udp-home-destacado__descripcion">
                        <?php echo wp_kses_post( $descripcion ); ?>
                    </div>
                <?php endif; ?>

                <?php if ( $link_texto && $link_url ) : ?>
                    <a
                        href="<?php echo esc_url( $link_url ); ?>"
                        class="udp-home-destacado__cta"
                        <?php if ( $link_externo ) : ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>
                    >
                        <?php echo esc_html( $link_texto ); ?>
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                            <path d="M3.5 12.5L12.5 3.5M12.5 3.5H6.5M12.5 3.5V9.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ( $img_url ) : ?>
            <div class="udp-home-destacado__media">
                <img
                    src="<?php echo esc_url( $img_url ); ?>"
                    alt="<?php echo esc_attr( $img_alt ); ?>"
                    loading="lazy"
                    decoding="async"
                    class="udp-home-destacado__imagen"
                    width="<?php echo esc_attr( $imagen['width'] ?? '' ); ?>"
                    height="<?php echo esc_attr( $imagen['height'] ?? '' ); ?>"
                >
            </div>
        <?php endif; ?>

    </div>
</section>
