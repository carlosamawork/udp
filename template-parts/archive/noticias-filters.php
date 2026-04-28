<?php
/**
 * Archive Noticias > Filtros (categoría + año + búsqueda)
 *
 * Form GET con auto-submit en change de los selects. Search input requiere
 * submit explícito (botón con icono de lupa).
 *
 * @package Starter_Theme
 *
 * @var array $args ['cat' => int, 'year' => int, 's' => string]
 */
$cat_active  = isset( $args['cat'] )  ? (int) $args['cat']  : 0;
$year_active = isset( $args['year'] ) ? (int) $args['year'] : 0;
$s_active    = isset( $args['s'] )    ? (string) $args['s'] : '';

$action_url = get_permalink( get_the_ID() );

$categories = get_categories( array(
    'taxonomy'   => 'category',
    'hide_empty' => true,
    'orderby'    => 'name',
    'order'      => 'ASC',
) );

$years = function_exists( 'udp_get_post_years' ) ? udp_get_post_years() : array();
?>
<form class="udp-archive-filters" method="get" action="<?php echo esc_url( $action_url ); ?>" role="search">

    <div class="udp-archive-filters__group">
        <label for="udp-filter-cat" class="visually-hidden"><?php esc_html_e( 'Selecciona categoría', 'starter-theme' ); ?></label>
        <select id="udp-filter-cat" name="cat" class="udp-archive-filters__select" data-udp-autosubmit>
            <option value=""><?php esc_html_e( 'Selecciona categoría', 'starter-theme' ); ?></option>
            <?php foreach ( $categories as $term ) : ?>
                <option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $cat_active, $term->term_id ); ?>>
                    <?php echo esc_html( $term->name ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="udp-archive-filters__group">
        <label for="udp-filter-year" class="visually-hidden"><?php esc_html_e( 'Selecciona año', 'starter-theme' ); ?></label>
        <select id="udp-filter-year" name="year" class="udp-archive-filters__select" data-udp-autosubmit>
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
