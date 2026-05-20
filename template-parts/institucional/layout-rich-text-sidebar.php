<?php
/**
 * Layout A — Texto + sidebar
 *
 * 3 columnas en ≥992px: título / WYSIWYG / sidebar cards
 * Stack vertical en <992px.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$data   = $args['data']   ?? array();
$anchor = $args['anchor'] ?? null;

$title         = $data['title']         ?? '';
$body          = $data['body']          ?? '';
$sidebar_cards = is_array( $data['sidebar_cards'] ?? null ) ? $data['sidebar_cards'] : array();

$id = $anchor['id'] ?? '';
?>
<section
    <?php if ( $id ) : ?>id="<?php echo esc_attr( $id ); ?>"<?php endif; ?>
    class="udp-inst-section udp-inst-rts"
    style="scroll-margin-top: var(--udp-anchor-offset, 168px);"
>
    <div class="udp-inst-rts__inner">
        <header class="udp-inst-rts__title-col">
            <h2 class="udp-inst-rts__title"><?php echo esc_html( $title ); ?></h2>
        </header>

        <div class="udp-inst-rts__body-col">
            <div class="udp-inst-rts__body"><?php echo wp_kses_post( $body ); ?></div>
        </div>

        <?php if ( ! empty( $sidebar_cards ) ) : ?>
            <aside class="udp-inst-rts__sidebar">
                <?php foreach ( $sidebar_cards as $card ) :
                    $c_title = $card['title'] ?? '';
                    $c_body  = $card['body']  ?? '';
                    $c_cta   = is_array( $card['cta'] ?? null ) ? $card['cta'] : array();
                    $c_url   = $c_cta['url']    ?? '';
                    $c_label = ! empty( $c_cta['title'] ) ? $c_cta['title'] : __( 'Conoce más', 'starter-theme' );
                    $c_tgt   = $c_cta['target'] ?? '';

                    if ( ! $c_url ) continue;
                ?>
                    <article class="udp-inst-rts__card">
                        <?php if ( $c_title ) : ?>
                            <h3 class="udp-inst-rts__card-title"><?php echo esc_html( $c_title ); ?></h3>
                        <?php endif; ?>
                        <?php if ( $c_body ) : ?>
                            <p class="udp-inst-rts__card-body"><?php echo esc_html( $c_body ); ?></p>
                        <?php endif; ?>
                        <a class="udp-inst-rts__card-cta" href="<?php echo esc_url( $c_url ); ?>"<?php echo $c_tgt ? ' target="' . esc_attr( $c_tgt ) . '" rel="noopener noreferrer"' : ''; ?>>
                            <span><?php echo esc_html( $c_label ); ?></span>
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    </article>
                <?php endforeach; ?>
            </aside>
        <?php endif; ?>
    </div>
</section>
