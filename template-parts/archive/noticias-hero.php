<?php
/**
 * Archive Noticias > Hero band (página 1)
 *
 * Featured card grande (variant=featured) + 2 side compactas
 * (variant=horizontal) apiladas a la derecha. Solo se muestra
 * en página 1 sin filtros activos.
 *
 * @package Starter_Theme
 *
 * @var array $args ['featured' => array|null, 'side' => array]
 */
$featured = isset( $args['featured'] ) && is_array( $args['featured'] ) ? $args['featured'] : null;
$side     = isset( $args['side'] )     && is_array( $args['side'] )     ? $args['side']     : array();

if ( ! $featured && empty( $side ) ) {
    return;
}
?>
<section class="udp-noticias-hero">
    <div class="udp-noticias-hero__inner">

        <?php if ( $featured ) : ?>
            <div class="udp-noticias-hero__featured">
                <?php
                get_template_part(
                    'template-parts/blocks/parts/card-noticia',
                    null,
                    array( 'card' => $featured, 'theme' => 'dark', 'variant' => 'featured' )
                );
                ?>
            </div>
        <?php endif; ?>

        <?php if ( ! empty( $side ) ) : ?>
            <div class="udp-noticias-hero__side">
                <?php foreach ( $side as $card ) : ?>
                    <div class="udp-noticias-hero__side-item">
                        <?php
                        get_template_part(
                            'template-parts/blocks/parts/card-noticia',
                            null,
                            array( 'card' => $card, 'theme' => 'dark', 'variant' => 'horizontal' )
                        );
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</section>
