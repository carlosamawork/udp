<?php
/**
 * Card primitive — Mosaic (image + title)
 *
 * Card simple para mosaicos: facultades, centros, carreras (con eyebrow opcional).
 * Imagen opcional — placeholder hatching si no hay.
 *
 * @package Starter_Theme
 *
 * @var array $args ['card' => array, 'theme' => 'dark'|'light']
 */
$card  = isset( $args['card'] ) && is_array( $args['card'] ) ? $args['card'] : array();
$theme = isset( $args['theme'] ) && in_array( $args['theme'], array( 'dark', 'light' ), true ) ? $args['theme'] : 'dark';

$titulo  = $card['titulo'] ?? '';
$href    = $card['href'] ?? '';
$imagen  = is_array( $card['imagen'] ?? null ) ? $card['imagen'] : array();
$has_img = ! empty( $card['has_image'] );
$eyebrow = $card['eyebrow'] ?? '';
$target  = $card['target'] ?? '';
$rel     = $target === '_blank' ? 'noopener noreferrer' : '';

if ( ! $titulo || ! $href ) {
    return;
}

$class = 'udp-card-mosaic udp-card-mosaic--' . $theme;
$media_class = 'udp-card-mosaic__media' . ( $has_img ? '' : ' udp-card-mosaic__media--placeholder' );
?>
<a
    href="<?php echo esc_url( $href ); ?>"
    class="<?php echo esc_attr( $class ); ?>"
    <?php if ( $target ) : ?>target="<?php echo esc_attr( $target ); ?>"<?php endif; ?>
    <?php if ( $rel ) : ?>rel="<?php echo esc_attr( $rel ); ?>"<?php endif; ?>
>
    <figure class="<?php echo esc_attr( $media_class ); ?>">
        <?php if ( $has_img ) : ?>
            <img
                src="<?php echo esc_url( $imagen['url'] ?? '' ); ?>"
                alt="<?php echo esc_attr( $imagen['alt'] ?? '' ); ?>"
                loading="lazy"
                decoding="async"
            />
        <?php endif; ?>
    </figure>
    <div class="udp-card-mosaic__body">
        <?php if ( $eyebrow ) : ?>
            <span class="udp-card-mosaic__eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
        <?php endif; ?>
        <h3 class="udp-card-mosaic__title"><?php echo esc_html( $titulo ); ?></h3>
    </div>
</a>
