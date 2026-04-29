<?php
/**
 * Single Concurso > Sidebar meta (Fecha + facultad eyebrow).
 * @var array $args ['post_id' => int]
 */
$post_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();
if ( ! $post_id ) return;

$fecha_iso     = get_the_date( 'Y-m-d', $post_id );
$fecha_display = function_exists( 'udp_card_format_date' ) ? udp_card_format_date( $fecha_iso ) : $fecha_iso;

$eyebrow_text = '';
$facultades = get_the_terms( $post_id, 'facultad' );
if ( ! is_wp_error( $facultades ) && ! empty( $facultades ) ) {
    $eyebrow_text = $facultades[0]->name;
}
?>
<div class="udp-concurso-meta">
    <?php if ( $fecha_display ) : ?>
        <div class="udp-concurso-meta__row">
            <span class="udp-concurso-meta__label"><?php esc_html_e( 'Fecha', 'starter-theme' ); ?></span>
            <time class="udp-concurso-meta__value" datetime="<?php echo esc_attr( $fecha_iso ); ?>">
                <?php echo esc_html( $fecha_display ); ?>
            </time>
        </div>
    <?php endif; ?>
    <?php if ( $eyebrow_text ) : ?>
        <span class="udp-card-noticia__eyebrow udp-card-noticia__eyebrow--yellow"><?php echo esc_html( $eyebrow_text ); ?></span>
    <?php endif; ?>
</div>
