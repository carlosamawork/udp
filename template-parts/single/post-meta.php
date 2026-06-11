<?php
/**
 * Single Post > Meta
 *
 * Fecha + categoría (chip amarillo). En desktop es la columna izquierda
 * del cuerpo (~317px); en mobile se apila bajo el título.
 *
 * @package Starter_Theme
 *
 * @var array $args ['post_id' => int]
 */
$post_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();
if ( ! $post_id ) {
    return;
}

$fecha_iso     = get_the_date( 'Y-m-d', $post_id );
$fecha_display = function_exists( 'udp_card_format_date' ) ? udp_card_format_date( $fecha_iso ) : $fecha_iso;

$terms             = get_the_terms( $post_id, 'category' );
$primary_term_name = ( ! is_wp_error( $terms ) && ! empty( $terms ) ) ? $terms[0]->name : '';
?>
<div class="udp-single-post__meta">
    <span class="udp-single-post__meta-label"><?php esc_html_e( 'Fecha', 'starter-theme' ); ?></span>
    <time class="udp-single-post__date" datetime="<?php echo esc_attr( $fecha_iso ); ?>"><?php echo esc_html( $fecha_display ); ?></time>
    <?php if ( $primary_term_name ) : ?>
        <span class="udp-card-noticia__eyebrow udp-card-noticia__eyebrow--yellow"><?php echo esc_html( $primary_term_name ); ?></span>
    <?php endif; ?>
</div>
