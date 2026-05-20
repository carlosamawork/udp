<?php
/**
 * Layout B — Fila de cards sobre banda oscura
 *
 * Full-width con fondo oscuro. Título a la izquierda, cards en grid.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$data   = $args['data']   ?? array();
$anchor = $args['anchor'] ?? null;

$title = $data['title'] ?? '';
$cards = is_array( $data['cards'] ?? null ) ? $data['cards'] : array();

$id = $anchor['id'] ?? '';

if ( empty( $cards ) ) return;
?>
<section
    <?php if ( $id ) : ?>id="<?php echo esc_attr( $id ); ?>"<?php endif; ?>
    class="udp-inst-section udp-inst-dark"
    style="scroll-margin-top: var(--udp-anchor-offset, 168px);"
>
    <div class="udp-inst-dark__inner">
        <?php if ( $title ) : ?>
            <header class="udp-inst-dark__header">
                <h2 class="udp-inst-dark__title"><?php echo esc_html( $title ); ?></h2>
            </header>
        <?php endif; ?>

        <ul class="udp-inst-dark__cards">
            <?php foreach ( $cards as $card ) :
                $img     = is_array( $card['image'] ?? null ) ? $card['image'] : array();
                $c_title = $card['title']   ?? '';
                $c_exc   = $card['excerpt'] ?? '';
                $c_link  = is_array( $card['link'] ?? null ) ? $card['link'] : array();
                $c_url   = $c_link['url']    ?? '';
                $c_tgt   = $c_link['target'] ?? '';
            ?>
                <li class="udp-inst-dark__card">
                    <?php if ( ! empty( $img['url'] ) ) : ?>
                        <div class="udp-inst-dark__card-image">
                            <img src="<?php echo esc_url( $img['url'] ); ?>" alt="<?php echo esc_attr( $img['alt'] ?? '' ); ?>" loading="lazy">
                        </div>
                    <?php endif; ?>
                    <div class="udp-inst-dark__card-body">
                        <?php if ( $c_title ) : ?>
                            <?php if ( $c_url ) : ?>
                                <h3 class="udp-inst-dark__card-title">
                                    <a href="<?php echo esc_url( $c_url ); ?>"<?php echo $c_tgt ? ' target="' . esc_attr( $c_tgt ) . '" rel="noopener noreferrer"' : ''; ?>>
                                        <?php echo esc_html( $c_title ); ?>
                                    </a>
                                </h3>
                            <?php else : ?>
                                <h3 class="udp-inst-dark__card-title"><?php echo esc_html( $c_title ); ?></h3>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if ( $c_exc ) : ?>
                            <p class="udp-inst-dark__card-excerpt"><?php echo esc_html( $c_exc ); ?></p>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
