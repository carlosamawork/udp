<?php
/**
 * @var array $args ['publico' => int, 'tipo' => int, 's' => string, 'year' => int]
 */
$publico_active = isset( $args['publico'] ) ? (int) $args['publico'] : 0;
$tipo_active    = isset( $args['tipo'] )    ? (int) $args['tipo']    : 0;
$s_active       = isset( $args['s'] )       ? (string) $args['s']    : '';
$year_active    = isset( $args['year'] )    ? (int) $args['year']    : 0;

$publico_terms = get_terms( array( 'taxonomy' => 'publico-udp', 'hide_empty' => true, 'orderby' => 'name' ) );
$tipo_terms    = get_terms( array( 'taxonomy' => 'tipo-udp',    'hide_empty' => true, 'orderby' => 'name' ) );
if ( is_wp_error( $publico_terms ) ) { $publico_terms = array(); }
if ( is_wp_error( $tipo_terms ) )    { $tipo_terms    = array(); }

$action_url = get_permalink( get_the_ID() );
?>
<form class="udp-form-filterbar" method="get" action="<?php echo esc_url( $action_url ); ?>" role="search">

    <?php if ( $year_active ) : ?>
        <input type="hidden" name="udp_year" value="<?php echo esc_attr( $year_active ); ?>" />
    <?php endif; ?>

    <div class="udp-form-filterbar__group">
        <label for="udp-filter-publico" class="visually-hidden"><?php esc_html_e( 'Selecciona público', 'starter-theme' ); ?></label>
        <select id="udp-filter-publico" name="udp_publico" class="udp-form-select udp-form-select--slim" data-udp-autosubmit>
            <option value=""><?php esc_html_e( 'Selecciona público', 'starter-theme' ); ?></option>
            <?php foreach ( $publico_terms as $term ) : ?>
                <option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $publico_active, $term->term_id ); ?>>
                    <?php echo esc_html( $term->name ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="udp-form-filterbar__group">
        <label for="udp-filter-tipo" class="visually-hidden"><?php esc_html_e( 'Selecciona tipo', 'starter-theme' ); ?></label>
        <select id="udp-filter-tipo" name="udp_tipo" class="udp-form-select udp-form-select--slim" data-udp-autosubmit>
            <option value=""><?php esc_html_e( 'Selecciona tipo', 'starter-theme' ); ?></option>
            <?php foreach ( $tipo_terms as $term ) : ?>
                <option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $tipo_active, $term->term_id ); ?>>
                    <?php echo esc_html( $term->name ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="udp-form-filterbar__group udp-form-filterbar__group--search udp-form-search-group">
        <label for="udp-filter-s" class="visually-hidden"><?php esc_html_e( 'Buscar', 'starter-theme' ); ?></label>
        <input id="udp-filter-s" type="search" name="udp_s" class="udp-form-input udp-form-input--slim"
            placeholder="<?php esc_attr_e( 'Buscar', 'starter-theme' ); ?>"
            value="<?php echo esc_attr( $s_active ); ?>" />
        <button type="submit" class="udp-form-search-group__btn" aria-label="<?php esc_attr_e( 'Buscar', 'starter-theme' ); ?>">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                <circle cx="8" cy="8" r="5.5" stroke="currentColor" stroke-width="1.5"/>
                <path d="m12 12 4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </button>
    </div>

</form>
<script>
(function () {
    document.querySelectorAll('.udp-form-filterbar [data-udp-autosubmit]').forEach(function (el) {
        el.addEventListener('change', function () { el.form.submit(); });
    });
})();
</script>
