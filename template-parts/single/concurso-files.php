<?php
/**
 * Single Concurso > Buttons descarga (Formato + Bases).
 * @var array $args ['post_id' => int]
 */
$post_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();
if ( ! $post_id ) return;

$archivo_concurso  = function_exists( 'get_field' ) ? get_field( 'archivo_concurso', $post_id ) : null;
$archivo_formato   = function_exists( 'get_field' ) ? get_field( 'archivo_formato_propuestas', $post_id ) : null;

$bases_url   = is_array( $archivo_concurso ) ? ( $archivo_concurso['url'] ?? '' ) : ( is_string( $archivo_concurso ) ? $archivo_concurso : '' );
$formato_url = is_array( $archivo_formato )  ? ( $archivo_formato['url']  ?? '' ) : '';

if ( empty( $bases_url ) && empty( $formato_url ) ) {
    return;
}
?>
<div class="udp-concurso-files">
    <?php if ( $formato_url ) : ?>
        <a class="udp-concurso-files__btn udp-concurso-files__btn--outline" href="<?php echo esc_url( $formato_url ); ?>" target="_blank" rel="noopener noreferrer">
            <?php esc_html_e( 'Formato de propuestas', 'starter-theme' ); ?>
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                <path d="M3 1.5h5l3 3v8H3v-11z" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/>
                <path d="M8 1.5v3h3" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/>
            </svg>
        </a>
    <?php endif; ?>
    <?php if ( $bases_url ) : ?>
        <a class="udp-concurso-files__btn udp-concurso-files__btn--primary" href="<?php echo esc_url( $bases_url ); ?>" target="_blank" rel="noopener noreferrer">
            <?php esc_html_e( 'Descargar bases', 'starter-theme' ); ?>
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                <path d="M7 2v8M3.5 6.5L7 10l3.5-3.5M2.5 12h9" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </a>
    <?php endif; ?>
</div>
