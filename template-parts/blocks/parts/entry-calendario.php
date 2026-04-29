<?php
/**
 * @var array $args ['entry' => array]
 */
$entry = isset( $args['entry'] ) && is_array( $args['entry'] ) ? $args['entry'] : array();
$titulo = $entry['titulo'] ?? '';
if ( ! $titulo ) {
    return;
}

$fecha_iso     = $entry['fecha'] ?? '';
$fecha_display = $entry['fecha_display'] ?? '';
$destacado     = ! empty( $entry['destacado'] );
$descripcion   = $entry['descripcion'] ?? '';
$href_ics      = $entry['href_ics'] ?? '';
$tipo          = $entry['tipo'] ?? '';

$class = 'udp-entry-calendario' . ( $destacado ? ' udp-entry-calendario--destacado' : '' );
?>
<li class="<?php echo esc_attr( $class ); ?>">
    <?php if ( $fecha_display ) : ?>
        <div class="udp-entry-calendario__date">
            <time datetime="<?php echo esc_attr( $fecha_iso ); ?>"><?php echo esc_html( $fecha_display ); ?></time>
        </div>
    <?php endif; ?>
    <div class="udp-entry-calendario__body">
        <div class="udp-entry-calendario__meta">
            <?php if ( $destacado ) : ?>
                <span class="udp-entry-calendario__tag"><?php esc_html_e( 'Destacado', 'starter-theme' ); ?></span>
            <?php endif; ?>
            <?php if ( $tipo ) : ?>
                <span class="udp-entry-calendario__tipo"><?php echo esc_html( $tipo ); ?></span>
            <?php endif; ?>
        </div>
        <h3 class="udp-entry-calendario__title"><?php echo esc_html( $titulo ); ?></h3>
        <?php if ( $descripcion ) : ?>
            <p class="udp-entry-calendario__desc"><?php echo esc_html( $descripcion ); ?></p>
        <?php endif; ?>
        <?php if ( $href_ics ) : ?>
            <a class="udp-entry-calendario__ics" href="<?php echo esc_url( $href_ics ); ?>">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                    <rect x="2" y="3" width="10" height="9" rx="1" stroke="currentColor" stroke-width="1.2"/>
                    <line x1="2" y1="6" x2="12" y2="6" stroke="currentColor" stroke-width="1.2"/>
                </svg>
                <?php esc_html_e( 'Agregar al calendario', 'starter-theme' ); ?>
            </a>
        <?php endif; ?>
    </div>
</li>
