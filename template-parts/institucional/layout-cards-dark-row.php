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
                $c_title = $card['title']   ?? '';
                $c_link  = is_array( $card['link'] ?? null ) ? $card['link'] : array();
                $c_url   = $c_link['url']    ?? '';
                $c_tgt   = $c_link['target'] ?? '';
                if ( ! $c_title ) {
                    continue;
                }
                // Card estilo Figma (4041-41179 "autoridades"): fondo oscuro, sin
                // imagen, título abajo-izquierda + flecha circular arriba-derecha.
                $tag      = $c_url ? 'a' : 'div';
                $tag_attrs = $c_url ? ' href="' . esc_url( $c_url ) . '"' . ( $c_tgt ? ' target="' . esc_attr( $c_tgt ) . '" rel="noopener noreferrer"' : '' ) : '';
            ?>
                <li class="udp-inst-dark__card">
                    <<?php echo $tag; ?> class="udp-inst-dark__card-link"<?php echo $tag_attrs; ?>>
                        <span class="udp-inst-dark__card-cta" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M5 3h8v8M13 3 3 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <div class="udp-inst-dark__card-content">
                            <h3 class="udp-inst-dark__card-title"><?php echo esc_html( $c_title ); ?></h3>
                        </div>
                    </<?php echo $tag; ?>>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
