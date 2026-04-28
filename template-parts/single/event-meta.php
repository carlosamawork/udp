<?php
/**
 * Single Event > Sidebar meta
 *
 * Eyebrow + Día + Hora + Dirección + Entrada + Unidad Académica + 2 CTAs.
 *
 * IMPORTANT: ACF `fecha` field stores raw as Ymd. Use get_post_meta + DateTime
 * to parse — `get_field('fecha')` returns ACF return-format (locale string,
 * unparseable). Same pattern in udp-cards.php and udp-ics.php.
 *
 * @package Starter_Theme
 *
 * @var array $args ['post_id' => int]
 */
$post_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();
if ( ! $post_id ) {
    return;
}

$eyebrow_text = '';
$tags = get_the_terms( $post_id, 'post_tag' );
if ( ! is_wp_error( $tags ) && ! empty( $tags ) ) {
    $eyebrow_text = $tags[0]->name;
}

// Raw postmeta (Ymd / time strings)
$fecha_raw     = (string) get_post_meta( $post_id, 'fecha', true );
$hora_inicio   = (string) get_post_meta( $post_id, 'hora_inicio', true );
$hora_fin      = (string) get_post_meta( $post_id, 'hora_termino', true );
$lugar         = (string) get_post_meta( $post_id, 'lugar', true );
$inscrip_url   = (string) get_post_meta( $post_id, 'inscripciones', true );

$fecha_disp = '';
if ( $fecha_raw ) {
    $dt = DateTime::createFromFormat( 'Ymd', $fecha_raw );
    if ( ! $dt ) {
        $ts = strtotime( $fecha_raw );
        if ( $ts ) {
            $dt = ( new DateTime() )->setTimestamp( $ts );
        }
    }
    if ( $dt ) {
        $fecha_disp = date_i18n( 'j \d\e F \d\e Y', $dt->getTimestamp() );
    }
}

$hora_disp = '';
if ( $hora_inicio ) {
    $hi = strtotime( $hora_inicio );
    if ( $hi ) {
        $hora_disp = date_i18n( 'H:i', $hi ) . ' hrs';
        if ( $hora_fin ) {
            $hf = strtotime( $hora_fin );
            if ( $hf ) {
                $hora_disp = date_i18n( 'H:i', $hi ) . ' - ' . date_i18n( 'H:i', $hf ) . ' hrs';
            }
        }
    }
}

$unidad = '';
$facultades = get_the_terms( $post_id, 'facultad' );
if ( ! is_wp_error( $facultades ) && ! empty( $facultades ) ) {
    $unidad = $facultades[0]->name;
}

$ics_url = add_query_arg( 'udp_ics', $post_id, home_url( '/' ) );
?>
<div class="udp-event-meta">

    <?php if ( $eyebrow_text ) : ?>
        <span class="udp-card-noticia__eyebrow udp-card-noticia__eyebrow--yellow"><?php echo esc_html( $eyebrow_text ); ?></span>
    <?php endif; ?>

    <?php if ( $fecha_disp ) : ?>
        <div class="udp-event-meta__row">
            <span class="udp-event-meta__label"><?php esc_html_e( 'Día', 'starter-theme' ); ?></span>
            <span class="udp-event-meta__value"><?php echo esc_html( $fecha_disp ); ?></span>
        </div>
    <?php endif; ?>

    <?php if ( $hora_disp ) : ?>
        <div class="udp-event-meta__row">
            <span class="udp-event-meta__label"><?php esc_html_e( 'Hora', 'starter-theme' ); ?></span>
            <span class="udp-event-meta__value"><?php echo esc_html( $hora_disp ); ?></span>
        </div>
    <?php endif; ?>

    <?php if ( $lugar ) : ?>
        <div class="udp-event-meta__row">
            <span class="udp-event-meta__label"><?php esc_html_e( 'Dirección', 'starter-theme' ); ?></span>
            <span class="udp-event-meta__value"><?php echo esc_html( $lugar ); ?></span>
        </div>
    <?php endif; ?>

    <div class="udp-event-meta__row">
        <span class="udp-event-meta__label"><?php esc_html_e( 'Entrada', 'starter-theme' ); ?></span>
        <span class="udp-event-meta__value"><?php esc_html_e( 'Entrada liberada para todo público', 'starter-theme' ); ?></span>
    </div>

    <?php if ( $unidad ) : ?>
        <div class="udp-event-meta__row">
            <span class="udp-event-meta__label"><?php esc_html_e( 'Unidad Académica relacionada', 'starter-theme' ); ?></span>
            <span class="udp-event-meta__value"><?php echo esc_html( $unidad ); ?></span>
        </div>
    <?php endif; ?>

    <div class="udp-event-meta__actions">
        <a class="udp-event-meta__btn udp-event-meta__btn--outline" href="<?php echo esc_url( $ics_url ); ?>">
            <?php esc_html_e( 'Agregar al calendario', 'starter-theme' ); ?>
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><rect x="2" y="3" width="10" height="9" rx="1" stroke="currentColor" stroke-width="1.2"/><line x1="2" y1="6" x2="12" y2="6" stroke="currentColor" stroke-width="1.2"/></svg>
        </a>
        <?php if ( $inscrip_url ) : ?>
            <a class="udp-event-meta__btn udp-event-meta__btn--primary" href="<?php echo esc_url( $inscrip_url ); ?>" target="_blank" rel="noopener noreferrer">
                <?php esc_html_e( 'Inscríbete aquí', 'starter-theme' ); ?>
            </a>
        <?php endif; ?>
    </div>

</div>
