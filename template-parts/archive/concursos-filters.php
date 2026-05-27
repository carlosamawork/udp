<?php
/**
 * @var array $args ['facultad' => int, 's' => string]
 */
$facultad_active = isset( $args['facultad'] ) ? (int) $args['facultad'] : 0;
$s_active        = isset( $args['s'] )        ? (string) $args['s']    : '';

$action_url = get_permalink( get_the_ID() );

$facultades = get_terms( array( 'taxonomy' => 'facultad', 'hide_empty' => true, 'orderby' => 'name' ) );
if ( is_wp_error( $facultades ) ) { $facultades = array(); }
?>
<form class="udp-form-filterbar" method="get" action="<?php echo esc_url( $action_url ); ?>" role="search">

    <div class="udp-form-filterbar__group">
        <label for="udp-filter-facultad" class="visually-hidden"><?php esc_html_e( 'Selecciona facultad', 'starter-theme' ); ?></label>
        <select id="udp-filter-facultad" name="udp_facultad" class="udp-form-select udp-form-select--slim udp-form-select--light" data-udp-autosubmit>
            <option value=""><?php esc_html_e( 'Selecciona facultad', 'starter-theme' ); ?></option>
            <?php foreach ( $facultades as $term ) : ?>
                <option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $facultad_active, $term->term_id ); ?>>
                    <?php echo esc_html( $term->name ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="udp-form-filterbar__group udp-form-filterbar__group--search udp-form-search-group udp-form-search-group--light">
        <label for="udp-filter-s" class="visually-hidden"><?php esc_html_e( 'Buscar por palabra clave', 'starter-theme' ); ?></label>
        <input id="udp-filter-s" type="search" name="udp_s" class="udp-form-input udp-form-input--slim udp-form-input--light"
            placeholder="<?php esc_attr_e( 'Buscar por palabra clave', 'starter-theme' ); ?>"
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
    document.querySelectorAll('.udp-concursos-archive .udp-form-filterbar [data-udp-autosubmit]').forEach(function (el) {
        el.addEventListener('change', function () { el.form.submit(); });
    });
})();
</script>
