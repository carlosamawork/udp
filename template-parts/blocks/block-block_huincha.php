<?php
/**
 * Block: Huincha (marquee horizontal)
 *
 * CSS-only marquee con pause on hover + prefers-reduced-motion.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$titulo    = get_sub_field( 'titulo' );
$items     = get_sub_field( 'items' ) ?: array();
$direccion = get_sub_field( 'direccion' ) ?: 'left';
$speed     = (int) get_sub_field( 'speed' );
$theme     = get_sub_field( 'theme' ) ?: 'dark';

if ( empty( $items ) ) {
    return;
}
if ( $speed <= 0 ) {
    $speed = 30;
}

$container_class = 'udp-block-huincha udp-block-huincha--' . $theme . ' udp-block-huincha--' . $direccion;
?>
<section class="<?php echo esc_attr( $container_class ); ?>" style="--udp-huincha-speed: <?php echo (int) $speed; ?>s;">
    <?php if ( $titulo ) : ?>
        <h2 class="udp-block-huincha__title"><?php echo esc_html( $titulo ); ?></h2>
    <?php endif; ?>

    <div class="udp-block-huincha__viewport" aria-hidden="false">
        <ul class="udp-block-huincha__track">
            <?php
            // Render items twice for seamless infinite scroll
            for ( $rep = 0; $rep < 2; $rep++ ) :
                foreach ( $items as $item ) :
                    $text  = $item['text']  ?? '';
                    $image = is_array( $item['image'] ?? null ) ? $item['image'] : array();
                    $link  = $item['link']  ?? '';
                    $img_url = $image['sizes']['thumbnail'] ?? ( $image['url'] ?? '' );
                    $img_alt = $image['alt'] ?? $text;
                    $tag = $link ? 'a' : 'span';
            ?>
                    <li class="udp-block-huincha__item">
                        <<?php echo $tag; ?> class="udp-block-huincha__item-inner"
                            <?php if ( $link ) : ?> href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener noreferrer"<?php endif; ?>
                            <?php if ( $rep > 0 ) : ?> aria-hidden="true"<?php endif; ?>
                        >
                            <?php if ( $img_url ) : ?>
                                <img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $img_alt ); ?>" loading="lazy" />
                            <?php endif; ?>
                            <?php if ( $text ) : ?>
                                <span class="udp-block-huincha__item-text"><?php echo esc_html( $text ); ?></span>
                            <?php endif; ?>
                        </<?php echo $tag; ?>>
                    </li>
                <?php endforeach; ?>
            <?php endfor; ?>
        </ul>
    </div>
</section>
