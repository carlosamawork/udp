<?php
/**
 * Layout D — Link de volver
 *
 * Si target está vacío, usa wp_get_post_parent_id().
 * Si no hay padre, link a home.
 * {parent_title} en link_text se reemplaza por el título efectivo.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$data   = $args['data']   ?? array();
$anchor = $args['anchor'] ?? null;

$target_url = trim( (string) ( $data['target'] ?? '' ) );
$link_text  = trim( (string) ( $data['link_text'] ?? '' ) );

$parent_id    = (int) wp_get_post_parent_id( get_the_ID() );
$parent_title = $parent_id ? get_the_title( $parent_id ) : __( 'Inicio', 'starter-theme' );

if ( $target_url === '' ) {
    $parent_url = $parent_id ? get_permalink( $parent_id ) : '';
    $target_url = $parent_url ?: home_url( '/' );
}

$link_text = str_replace( '{parent_title}', $parent_title, $link_text );
if ( $link_text === '' ) {
    $link_text = __( 'Volver', 'starter-theme' );
}

$id = $anchor['id'] ?? '';
?>
<section
    <?php if ( $id ) : ?>id="<?php echo esc_attr( $id ); ?>"<?php endif; ?>
    class="udp-inst-section udp-inst-back"
    style="scroll-margin-top: var(--udp-anchor-offset, 168px);"
>
    <div class="udp-inst-back__inner">
        <a class="udp-inst-back__link" href="<?php echo esc_url( $target_url ); ?>">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <path d="M13 8H3M7 4L3 8l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span><?php echo esc_html( $link_text ); ?></span>
        </a>
    </div>
</section>
