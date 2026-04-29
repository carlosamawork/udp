<?php
/**
 * Archive Eventos > Filtros (facultad + año + búsqueda)
 *
 * Form GET con auto-submit en change. View toggle preserva los filtros.
 *
 * @package Starter_Theme
 *
 * @var array $args ['facultad' => int, 'year' => int, 's' => string]
 */
$facultad_active = isset( $args['facultad'] ) ? (int) $args['facultad'] : 0;
$year_active     = isset( $args['year'] )     ? (int) $args['year']     : 0;
$s_active        = isset( $args['s'] )        ? (string) $args['s']     : '';

$action_url = get_permalink( get_the_ID() );

$facultades = get_terms( array(
    'taxonomy'   => 'facultad',
    'hide_empty' => true,
    'orderby'    => 'name',
    'order'      => 'ASC',
) );
if ( is_wp_error( $facultades ) ) {
    $facultades = array();
}

global $wpdb;
$years = wp_cache_get( 'udp_agenda_years' );
if ( $years === false ) {
    $years = $wpdb->get_col( "
        SELECT DISTINCT LEFT(pm.meta_value, 4) AS y
        FROM {$wpdb->postmeta} pm
        JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = 'fecha'
          AND pm.meta_value REGEXP '^[0-9]{4}'
          AND p.post_type = 'agenda'
          AND p.post_status = 'publish'
        ORDER BY y DESC
    " );
    $years = array_map( 'intval', (array) $years );
    wp_cache_set( 'udp_agenda_years', $years, '', DAY_IN_SECONDS );
}

$view_active = isset( $_GET['view'] ) && $_GET['view'] === 'list' ? 'list' : 'grid';
?>
<form class="udp-archive-filters" method="get" action="<?php echo esc_url( $action_url ); ?>" role="search">

    <input type="hidden" name="view" value="<?php echo esc_attr( $view_active ); ?>" />

    <div class="udp-archive-filters__group">
        <label for="udp-filter-facultad" class="visually-hidden"><?php esc_html_e( 'Selecciona facultad', 'starter-theme' ); ?></label>
        <select id="udp-filter-facultad" name="udp_facultad" class="udp-archive-filters__select" data-udp-autosubmit>
            <option value=""><?php esc_html_e( 'Selecciona facultad', 'starter-theme' ); ?></option>
            <?php foreach ( $facultades as $term ) : ?>
                <option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $facultad_active, $term->term_id ); ?>>
                    <?php echo esc_html( $term->name ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="udp-archive-filters__group">
        <label for="udp-filter-year" class="visually-hidden"><?php esc_html_e( 'Selecciona año', 'starter-theme' ); ?></label>
        <select id="udp-filter-year" name="udp_year" class="udp-archive-filters__select" data-udp-autosubmit>
            <option value=""><?php esc_html_e( 'Selecciona año', 'starter-theme' ); ?></option>
            <?php foreach ( $years as $y ) : ?>
                <option value="<?php echo esc_attr( $y ); ?>" <?php selected( $year_active, $y ); ?>>
                    <?php echo esc_html( $y ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="udp-archive-filters__group udp-archive-filters__group--search">
        <label for="udp-filter-s" class="visually-hidden"><?php esc_html_e( 'Buscar', 'starter-theme' ); ?></label>
        <input
            id="udp-filter-s"
            type="search"
            name="udp_s"
            class="udp-archive-filters__input"
            placeholder="<?php esc_attr_e( 'Buscar', 'starter-theme' ); ?>"
            value="<?php echo esc_attr( $s_active ); ?>"
        />
        <button type="submit" class="udp-archive-filters__submit" aria-label="<?php esc_attr_e( 'Buscar', 'starter-theme' ); ?>">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                <circle cx="8" cy="8" r="5.5" stroke="currentColor" stroke-width="1.5"/>
                <path d="m12 12 4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </button>
    </div>

</form>
<script>
(function () {
    document.querySelectorAll('.udp-archive-filters [data-udp-autosubmit]').forEach(function (el) {
        el.addEventListener('change', function () { el.form.submit(); });
    });
})();
</script>
