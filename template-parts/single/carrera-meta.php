<?php
/**
 * Single Carrera > Sidebar meta.
 * Eyebrow facultad + atributos repeater (titulo+valor) + 2 buttons.
 *
 * @var array $args ['post_id' => int]
 */
$post_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();
if ( ! $post_id ) return;

$eyebrow_text = '';
$facultades = get_the_terms( $post_id, 'facultad' );
if ( ! is_wp_error( $facultades ) && ! empty( $facultades ) ) {
    $eyebrow_text = $facultades[0]->name;
}

$atributos = function_exists( 'get_field' ) ? get_field( 'atributos', $post_id ) : array();
if ( ! is_array( $atributos ) ) $atributos = array();

$url_admision = (string) get_post_meta( $post_id, 'url_admision', true );
$url_facultad = (string) get_post_meta( $post_id, 'url_facultad', true );
?>
<div class="udp-carrera-meta">

    <?php if ( $eyebrow_text ) : ?>
        <span class="udp-card-noticia__eyebrow udp-card-noticia__eyebrow--yellow"><?php echo esc_html( $eyebrow_text ); ?></span>
    <?php endif; ?>

    <?php foreach ( $atributos as $attr ) :
        $titulo = $attr['titulo'] ?? '';
        $valor  = $attr['valor']  ?? '';
        if ( ! $titulo && ! $valor ) continue;
    ?>
        <div class="udp-carrera-meta__row">
            <?php if ( $titulo ) : ?>
                <span class="udp-carrera-meta__label"><?php echo esc_html( $titulo ); ?></span>
            <?php endif; ?>
            <?php if ( $valor ) : ?>
                <span class="udp-carrera-meta__value"><?php echo esc_html( $valor ); ?></span>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <?php if ( $url_admision || $url_facultad ) : ?>
        <div class="udp-carrera-meta__actions">
            <?php if ( $url_admision ) : ?>
                <a class="udp-carrera-meta__btn udp-carrera-meta__btn--primary" href="<?php echo esc_url( $url_admision ); ?>" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e( 'Información de admisión', 'starter-theme' ); ?>
                </a>
            <?php endif; ?>
            <?php if ( $url_facultad ) : ?>
                <a class="udp-carrera-meta__btn udp-carrera-meta__btn--outline" href="<?php echo esc_url( $url_facultad ); ?>" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e( 'Sitio de la facultad', 'starter-theme' ); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>
