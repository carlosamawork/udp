# F5a — Calendario Académico archive — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement this plan task-by-task.

**Goal:** Construir archive de Calendario en `/calendario-academico/` con sidebar sticky de meses-anchor + main grouped por mes + filtros publico/tipo/búsqueda + ICS download por entry. Reusa el endpoint `?udp_ics=ID` extendido para soportar `calendario` post type. No hay single template ni paginación (un año entero por página).

**Architecture:** Page template asignable a la página existente "Calendario Académico" (ID 74). Helper `udp_query_calendario()` devuelve entries agrupadas por mes (no paginadas). Reusa breadcrumb F3, ICS endpoint F4c (extender whitelist), filters pattern dark de F4b2. Theme dark.

**Reference:** Spec `docs/superpowers/specs/2026-04-29-f5a-calendario-archive-design.md`.

---

## Inventario

**Crear:**
- `templates/page-calendario.php`
- `template-parts/archive/calendario-sidebar.php`
- `template-parts/archive/calendario-filters.php`
- `template-parts/archive/calendario-month-section.php`
- `template-parts/blocks/parts/entry-calendario.php`
- `src/scss/templates/_calendario-archive.scss`

**Modificar:**
- `inc/udp-cards.php` — add `udp_query_calendario` + `udp_calendario_data_from_post` + `udp_get_calendario_years`.
- `inc/udp-ics.php` — extend whitelist + all-day mode for calendario.
- `src/scss/main.scss` — `@import "templates/calendario-archive";`.

---

## Task 1: Helpers + ICS extension

**File:** `inc/udp-cards.php`

- [ ] **Step 1: Append AT END del archivo**

```php

/**
 * Devuelve los años con entradas de calendario (DESC). Cache 1 día.
 *
 * @return int[]
 */
function udp_get_calendario_years(): array {
    $cache = get_transient( 'udp_calendario_years' );
    if ( $cache !== false ) {
        return $cache;
    }
    global $wpdb;
    $years = $wpdb->get_col( "
        SELECT DISTINCT LEFT(pm.meta_value, 4) AS y
        FROM {$wpdb->postmeta} pm
        JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = 'fecha'
          AND pm.meta_value REGEXP '^[0-9]{4}'
          AND p.post_type = 'calendario'
          AND p.post_status = 'publish'
        ORDER BY y DESC
    " );
    $years = array_map( 'intval', (array) $years );
    set_transient( 'udp_calendario_years', $years, DAY_IN_SECONDS );
    return $years;
}

/**
 * Convierte WP_Post (calendario) a Entry shape.
 * SIEMPRE devuelve array (no null) — no requiere featured image.
 *
 * @return array
 */
function udp_calendario_data_from_post( WP_Post $post ): array {
    $fecha_raw       = (string) get_post_meta( $post->ID, 'fecha', true );
    $fecha_amistosa  = (string) get_post_meta( $post->ID, 'fecha_amistosa', true );
    $destacado_raw   = get_post_meta( $post->ID, 'destacado', true );

    $fecha_iso = '';
    $fecha_disp_default = '';
    if ( $fecha_raw ) {
        $dt = DateTime::createFromFormat( 'Ymd', $fecha_raw );
        if ( ! $dt ) {
            $ts = strtotime( $fecha_raw );
            if ( $ts ) {
                $dt = ( new DateTime() )->setTimestamp( $ts );
            }
        }
        if ( $dt ) {
            $fecha_iso          = $dt->format( 'Y-m-d' );
            $fecha_disp_default = date_i18n( 'j \d\e F', $dt->getTimestamp() );
        }
    }

    $fecha_display = $fecha_amistosa !== '' ? $fecha_amistosa : $fecha_disp_default;

    $tipo_name = '';
    $tipos = get_the_terms( $post->ID, 'tipo-udp' );
    if ( ! is_wp_error( $tipos ) && ! empty( $tipos ) ) {
        $tipo_name = $tipos[0]->name;
    }

    $excerpt = wp_strip_all_tags( get_the_excerpt( $post ) );
    if ( strlen( $excerpt ) > 160 ) {
        $excerpt = mb_substr( $excerpt, 0, 157 ) . '…';
    }

    return array(
        'post_id'       => (int) $post->ID,
        'titulo'        => get_the_title( $post ),
        'fecha'         => $fecha_iso,
        'fecha_display' => $fecha_display,
        'destacado'     => (bool) $destacado_raw,
        'descripcion'   => $excerpt,
        'tipo'          => $tipo_name,
        'href_ics'      => add_query_arg( 'udp_ics', $post->ID, home_url( '/' ) ),
    );
}

/**
 * Wrapper sobre WP_Query para archive Calendario.
 * NO PAGINATES — devuelve TODAS las entries del año, agrupadas por mes.
 *
 * @param array $filters {
 *     @type int    $publico  term_id de publico-udp.
 *     @type int    $tipo     term_id de tipo-udp.
 *     @type int    $year     YYYY. Default año actual.
 *     @type string $s        Búsqueda.
 * }
 * @return array { entries_by_month: array<string,array>, total: int, year: int }
 */
function udp_query_calendario( array $filters ): array {
    $publico = (int) ( $filters['publico'] ?? 0 );
    $tipo    = (int) ( $filters['tipo']    ?? 0 );
    $year    = (int) ( $filters['year']    ?? (int) date( 'Y' ) );
    $s       = trim( (string) ( $filters['s'] ?? '' ) );

    $args = array(
        'post_type'      => 'calendario',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_key'       => 'fecha',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'meta_query'     => array(
            array(
                'key'     => 'fecha',
                'value'   => sprintf( '%04d', $year ),
                'compare' => 'LIKE',
            ),
        ),
        'no_found_rows'  => true,
    );

    $tax_query = array();
    if ( $publico > 0 ) {
        $tax_query[] = array( 'taxonomy' => 'publico-udp', 'field' => 'term_id', 'terms' => array( $publico ) );
    }
    if ( $tipo > 0 ) {
        $tax_query[] = array( 'taxonomy' => 'tipo-udp', 'field' => 'term_id', 'terms' => array( $tipo ) );
    }
    if ( count( $tax_query ) > 1 ) {
        $tax_query['relation'] = 'AND';
    }
    if ( ! empty( $tax_query ) ) {
        $args['tax_query'] = $tax_query;
    }

    if ( $s !== '' ) {
        $args['s'] = $s;
    }

    $q = new WP_Query( $args );

    $entries_by_month = array();
    foreach ( $q->posts as $post ) {
        $entry = udp_calendario_data_from_post( $post );
        if ( ! $entry['fecha'] ) {
            continue;
        }
        $month_key = substr( $entry['fecha'], 5, 2 );  // 'YYYY-MM-DD' → 'MM'
        if ( ! isset( $entries_by_month[ $month_key ] ) ) {
            $entries_by_month[ $month_key ] = array();
        }
        $entries_by_month[ $month_key ][] = $entry;
    }
    ksort( $entries_by_month );  // 01 → 12

    return array(
        'entries_by_month' => $entries_by_month,
        'total'            => count( $q->posts ),
        'year'             => $year,
    );
}
```

- [ ] **Step 2: Validar PHP**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/inc/udp-cards.php
```

- [ ] **Step 3: Extender ICS endpoint para calendario all-day**

Edit `inc/udp-ics.php`. Find:

```php
    if ( ! $post || $post->post_type !== 'agenda' || $post->post_status !== 'publish' ) {
        return;
    }
```

Replace with:

```php
    if ( ! $post || ! in_array( $post->post_type, array( 'agenda', 'calendario' ), true ) || $post->post_status !== 'publish' ) {
        return;
    }
```

Find the block that emits `DTSTART` / `DTEND` lines (around `gmdate( 'Ymd\THis\Z', $start_ts )`) and refactor for all-day calendario:

Find:

```php
    $ics .= "DTSTART:" . gmdate( 'Ymd\THis\Z', $start_ts ) . "\r\n";
    $ics .= "DTEND:" . gmdate( 'Ymd\THis\Z', $end_ts ) . "\r\n";
```

Replace with:

```php
    if ( $post->post_type === 'calendario' ) {
        // All-day event: VALUE=DATE format (YYYYMMDD)
        $start_date = gmdate( 'Ymd', $date_ts );
        $end_date   = gmdate( 'Ymd', $date_ts + DAY_IN_SECONDS );
        $ics .= "DTSTART;VALUE=DATE:" . $start_date . "\r\n";
        $ics .= "DTEND;VALUE=DATE:" . $end_date . "\r\n";
    } else {
        $ics .= "DTSTART:" . gmdate( 'Ymd\THis\Z', $start_ts ) . "\r\n";
        $ics .= "DTEND:" . gmdate( 'Ymd\THis\Z', $end_ts ) . "\r\n";
    }
```

- [ ] **Step 4: Validar PHP del ICS**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/inc/udp-ics.php
```

- [ ] **Step 5: Smoke test calendario helpers**

Crear `/tmp/test-udp-query-calendario.php`:

```php
<?php
$result = udp_query_calendario( array( 'year' => 2026 ) );
WP_CLI::log( 'total: ' . $result['total'] );
WP_CLI::log( 'year: ' . $result['year'] );
WP_CLI::log( 'months count: ' . count( $result['entries_by_month'] ) );
foreach ( $result['entries_by_month'] as $month => $entries ) {
    WP_CLI::log( sprintf( '  month %s: %d entries', $month, count( $entries ) ) );
}
$years = udp_get_calendario_years();
WP_CLI::log( 'years available: ' . implode( ',', $years ) );
WP_CLI::success( 'Calendario OK' );
```

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval-file /tmp/test-udp-query-calendario.php --path=/Applications/MAMP/htdocs/udp/cms 2>&1 | tail -10
```

Expected: total > 0, months > 0 con conteos por mes, years populated.

- [ ] **Step 6: Smoke test ICS calendario**

```bash
export MYSQL_PWD=root
CAL_ID=$(/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot udp -sN -e "
SELECT p.ID FROM wp_fnku4yposts p
JOIN wp_fnku4ypostmeta pm ON p.ID=pm.post_id AND pm.meta_key='fecha' AND pm.meta_value != ''
WHERE p.post_type='calendario' AND p.post_status='publish' LIMIT 1;")
echo "CAL_ID=$CAL_ID"
curl -sI "http://localhost:8888/udp/?udp_ics=$CAL_ID" 2>&1 | head -3
echo ""
curl -s "http://localhost:8888/udp/?udp_ics=$CAL_ID" | head -10
```

Expected: HTTP 200, body contiene `DTSTART;VALUE=DATE:YYYYMMDD` (no T-time format).

---

## Task 2: Page template

**File:** `templates/page-calendario.php`

- [ ] **Step 1: Crear page template**

```php
<?php
/**
 * Template Name: Calendario Académico
 *
 * Page template asignable a la página "Calendario Académico" (ID 74).
 * Sidebar sticky año + meses anchor. Main intro + secciones por mes.
 * Filtros publico-udp + tipo-udp + udp_s. Sin paginación — un año por página.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$publico = isset( $_GET['udp_publico'] ) ? (int) $_GET['udp_publico'] : 0;
$tipo    = isset( $_GET['udp_tipo'] )    ? (int) $_GET['udp_tipo']    : 0;
$year    = isset( $_GET['udp_year'] )    ? (int) $_GET['udp_year']    : (int) date( 'Y' );
$s       = isset( $_GET['udp_s'] )       ? sanitize_text_field( wp_unslash( $_GET['udp_s'] ) ) : '';

$result = function_exists( 'udp_query_calendario' )
    ? udp_query_calendario( array(
        'publico' => $publico,
        'tipo'    => $tipo,
        'year'    => $year,
        's'       => $s,
    ) )
    : array( 'entries_by_month' => array(), 'total' => 0, 'year' => $year );

$entries_by_month = $result['entries_by_month'];

$months_es = array(
    '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
    '05' => 'Mayo',  '06' => 'Junio',   '07' => 'Julio', '08' => 'Agosto',
    '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre',
);

get_header();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-calendario-archive' ); ?>>

    <header class="udp-calendario-archive__header">
        <?php
        get_template_part(
            'template-parts/sections/breadcrumb',
            null,
            array( 'page_id' => get_the_ID() )
        );
        ?>
        <h1 class="udp-calendario-archive__title"><?php the_title(); ?></h1>
    </header>

    <hr class="udp-calendario-archive__separator" aria-hidden="true" />

    <?php
    get_template_part(
        'template-parts/archive/calendario-filters',
        null,
        array( 'publico' => $publico, 'tipo' => $tipo, 's' => $s, 'year' => $year )
    );
    ?>

    <hr class="udp-calendario-archive__separator" aria-hidden="true" />

    <div class="udp-calendario-archive__body">

        <aside class="udp-calendario-archive__sidebar">
            <?php
            get_template_part(
                'template-parts/archive/calendario-sidebar',
                null,
                array(
                    'year'             => $year,
                    'publico'          => $publico,
                    'tipo'             => $tipo,
                    's'                => $s,
                    'entries_by_month' => $entries_by_month,
                    'months_es'        => $months_es,
                )
            );
            ?>
        </aside>

        <main class="udp-calendario-archive__main">
            <?php $intro = get_the_content(); ?>
            <?php if ( $intro ) : ?>
                <div class="udp-calendario-archive__intro">
                    <?php echo apply_filters( 'the_content', $intro ); ?>
                </div>
            <?php endif; ?>

            <?php if ( empty( $entries_by_month ) ) : ?>
                <p class="udp-calendario-archive__empty">
                    <?php esc_html_e( 'No hay fechas registradas en este año con los filtros aplicados.', 'starter-theme' ); ?>
                </p>
            <?php else : ?>
                <?php foreach ( $entries_by_month as $month_num => $entries ) :
                    $month_name = $months_es[ $month_num ] ?? '';
                    $month_slug = sanitize_title( $month_name );
                ?>
                    <?php
                    get_template_part(
                        'template-parts/archive/calendario-month-section',
                        null,
                        array(
                            'month_name' => $month_name,
                            'month_slug' => $month_slug,
                            'entries'    => $entries,
                        )
                    );
                    ?>
                <?php endforeach; ?>
            <?php endif; ?>

            <a href="#top" class="udp-calendario-archive__back-to-top">
                <?php esc_html_e( '↑ Volver arriba', 'starter-theme' ); ?>
            </a>
        </main>

    </div>

</article>

<?php
get_footer();
```

- [ ] **Step 2: Asignar template a página 74**

```bash
export MYSQL_PWD=root
SOCK=/Applications/MAMP/tmp/mysql/mysql.sock
MYSQL=/Applications/MAMP/Library/bin/mysql80/bin/mysql

EXISTING=$($MYSQL --socket=$SOCK -uroot udp -sN -e "SELECT meta_value FROM wp_fnku4ypostmeta WHERE post_id=74 AND meta_key='_wp_page_template' LIMIT 1;")
if [ -n "$EXISTING" ]; then
    $MYSQL --socket=$SOCK -uroot udp -e "UPDATE wp_fnku4ypostmeta SET meta_value='templates/page-calendario.php' WHERE post_id=74 AND meta_key='_wp_page_template';"
else
    $MYSQL --socket=$SOCK -uroot udp -e "INSERT INTO wp_fnku4ypostmeta (post_id, meta_key, meta_value) VALUES (74, '_wp_page_template', 'templates/page-calendario.php');"
fi
$MYSQL --socket=$SOCK -uroot udp -sN -e "SELECT meta_value FROM wp_fnku4ypostmeta WHERE post_id=74 AND meta_key='_wp_page_template';"
```

Expected: `templates/page-calendario.php`.

- [ ] **Step 3: Validar PHP**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/templates/page-calendario.php
```

---

## Task 3: 4 partials

- [ ] **Step 1: Sidebar — `calendario-sidebar.php`**

Create `template-parts/archive/calendario-sidebar.php`:

```php
<?php
/**
 * @var array $args ['year' => int, 'publico' => int, 'tipo' => int, 's' => string,
 *                   'entries_by_month' => array, 'months_es' => array]
 */
$year      = isset( $args['year'] ) ? (int) $args['year'] : (int) date( 'Y' );
$publico   = isset( $args['publico'] ) ? (int) $args['publico'] : 0;
$tipo      = isset( $args['tipo'] ) ? (int) $args['tipo'] : 0;
$s         = isset( $args['s'] ) ? (string) $args['s'] : '';
$by_month  = isset( $args['entries_by_month'] ) && is_array( $args['entries_by_month'] ) ? $args['entries_by_month'] : array();
$months_es = isset( $args['months_es'] ) && is_array( $args['months_es'] ) ? $args['months_es'] : array();

$years = function_exists( 'udp_get_calendario_years' ) ? udp_get_calendario_years() : array();
$action_url = get_permalink( get_the_ID() );
?>
<form class="udp-calendario-sidebar__year-form" method="get" action="<?php echo esc_url( $action_url ); ?>">
    <?php if ( $publico ) : ?><input type="hidden" name="udp_publico" value="<?php echo esc_attr( $publico ); ?>"><?php endif; ?>
    <?php if ( $tipo )    : ?><input type="hidden" name="udp_tipo" value="<?php echo esc_attr( $tipo ); ?>"><?php endif; ?>
    <?php if ( $s !== '' ): ?><input type="hidden" name="udp_s" value="<?php echo esc_attr( $s ); ?>"><?php endif; ?>

    <label for="udp-calendario-year" class="visually-hidden"><?php esc_html_e( 'Selecciona año', 'starter-theme' ); ?></label>
    <select id="udp-calendario-year" name="udp_year" class="udp-calendario-sidebar__year-select" data-udp-autosubmit>
        <?php foreach ( $years as $y ) : ?>
            <option value="<?php echo esc_attr( $y ); ?>" <?php selected( $year, $y ); ?>>
                <?php echo esc_html( $y ); ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<ul class="udp-calendario-sidebar__months-nav">
    <?php foreach ( $months_es as $num => $name ) :
        $has_entries = isset( $by_month[ $num ] ) && ! empty( $by_month[ $num ] );
        $slug = sanitize_title( $name );
    ?>
        <li class="udp-calendario-sidebar__month<?php echo $has_entries ? '' : ' udp-calendario-sidebar__month--empty'; ?>">
            <?php if ( $has_entries ) : ?>
                <a href="#<?php echo esc_attr( $slug ); ?>" data-udp-month-link="<?php echo esc_attr( $num ); ?>"><?php echo esc_html( $name ); ?></a>
            <?php else : ?>
                <span><?php echo esc_html( $name ); ?></span>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>

<script>
(function () {
    document.querySelectorAll('.udp-calendario-sidebar__year-form [data-udp-autosubmit]').forEach(function (el) {
        el.addEventListener('change', function () { el.form.submit(); });
    });
})();
</script>
```

- [ ] **Step 2: Filters — `calendario-filters.php`**

Create `template-parts/archive/calendario-filters.php`:

```php
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
<form class="udp-archive-filters" method="get" action="<?php echo esc_url( $action_url ); ?>" role="search">

    <?php if ( $year_active ) : ?>
        <input type="hidden" name="udp_year" value="<?php echo esc_attr( $year_active ); ?>" />
    <?php endif; ?>

    <div class="udp-archive-filters__group">
        <label for="udp-filter-publico" class="visually-hidden"><?php esc_html_e( 'Selecciona público', 'starter-theme' ); ?></label>
        <select id="udp-filter-publico" name="udp_publico" class="udp-archive-filters__select" data-udp-autosubmit>
            <option value=""><?php esc_html_e( 'Selecciona público', 'starter-theme' ); ?></option>
            <?php foreach ( $publico_terms as $term ) : ?>
                <option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $publico_active, $term->term_id ); ?>>
                    <?php echo esc_html( $term->name ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="udp-archive-filters__group">
        <label for="udp-filter-tipo" class="visually-hidden"><?php esc_html_e( 'Selecciona tipo', 'starter-theme' ); ?></label>
        <select id="udp-filter-tipo" name="udp_tipo" class="udp-archive-filters__select" data-udp-autosubmit>
            <option value=""><?php esc_html_e( 'Selecciona tipo', 'starter-theme' ); ?></option>
            <?php foreach ( $tipo_terms as $term ) : ?>
                <option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $tipo_active, $term->term_id ); ?>>
                    <?php echo esc_html( $term->name ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="udp-archive-filters__group udp-archive-filters__group--search">
        <label for="udp-filter-s" class="visually-hidden"><?php esc_html_e( 'Buscar', 'starter-theme' ); ?></label>
        <input id="udp-filter-s" type="search" name="udp_s" class="udp-archive-filters__input"
            placeholder="<?php esc_attr_e( 'Buscar', 'starter-theme' ); ?>"
            value="<?php echo esc_attr( $s_active ); ?>" />
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
```

- [ ] **Step 3: Month section — `calendario-month-section.php`**

Create `template-parts/archive/calendario-month-section.php`:

```php
<?php
/**
 * @var array $args ['month_name' => string, 'month_slug' => string, 'entries' => array]
 */
$month_name = isset( $args['month_name'] ) ? (string) $args['month_name'] : '';
$month_slug = isset( $args['month_slug'] ) ? (string) $args['month_slug'] : '';
$entries    = isset( $args['entries'] ) && is_array( $args['entries'] ) ? $args['entries'] : array();

if ( empty( $entries ) || ! $month_name ) {
    return;
}
?>
<section id="<?php echo esc_attr( $month_slug ); ?>" class="udp-calendario-month">
    <h2 class="udp-calendario-month__title"><?php echo esc_html( $month_name ); ?></h2>
    <ul class="udp-calendario-month__list">
        <?php foreach ( $entries as $entry ) : ?>
            <?php
            get_template_part(
                'template-parts/blocks/parts/entry-calendario',
                null,
                array( 'entry' => $entry )
            );
            ?>
        <?php endforeach; ?>
    </ul>
</section>
```

- [ ] **Step 4: Entry — `entry-calendario.php`**

Create `template-parts/blocks/parts/entry-calendario.php`:

```php
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
```

- [ ] **Step 5: Validar los 4 partials**

```bash
PHP=/Applications/MAMP/bin/php/php8.4.1/bin/php
$PHP -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/archive/calendario-sidebar.php
$PHP -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/archive/calendario-filters.php
$PHP -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/archive/calendario-month-section.php
$PHP -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/blocks/parts/entry-calendario.php
```

Expected: 4× `No syntax errors detected`.

---

## Task 4: SCSS

**File:** `src/scss/templates/_calendario-archive.scss`

- [ ] **Step 1: Crear el SCSS**

```scss
// ==========================================================================
// CALENDARIO ARCHIVE — page template `page-calendario.php`
// Theme dark. Sidebar sticky de meses + main intro + secciones por mes.
// ==========================================================================

.udp-calendario-archive {
    background-color: $dark-1;
    color: $white;
    padding-bottom: $space-3xl;

    &__header {
        max-width: 1440px;
        margin-inline: auto;
        padding: $space-2xl $space-3xl 0;

        @include media-down(md) {
            padding: $space-xl $space-sm 0;
        }

        .udp-breadcrumb__item,
        .udp-breadcrumb__link,
        .udp-breadcrumb__current { color: $white; }
        .udp-breadcrumb__sep { color: $white-70; }
    }

    &__title {
        margin: $space-md 0 0;
        font-family: $font-family-display;
        font-weight: 500;
        font-size: 64px;
        line-height: 1.05;
        color: $white;

        @include media-down(md) { font-size: 40px; }
    }

    &__separator {
        max-width: 1360px;
        margin: $space-2xl auto 0;
        border: 0;
        height: 1px;
        background-color: rgba($white, 0.15);
    }

    &__body {
        max-width: 1440px;
        margin: $space-2xl auto 0;
        padding: 0 $space-3xl;
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 60px;

        @include media-down(lg) {
            grid-template-columns: 1fr;
            padding: 0 $space-sm;
            gap: $space-xl;
        }
    }

    &__sidebar {
        position: sticky;
        top: 100px;
        align-self: start;
        max-height: calc(100vh - 120px);
        overflow-y: auto;

        @include media-down(lg) {
            position: static;
            max-height: none;
            overflow: visible;
        }
    }

    &__main {
        min-width: 0;
    }

    &__intro {
        font-family: $font-family-body;
        font-size: 16px;
        line-height: 24px;
        color: $white-70;
        margin-bottom: $space-2xl;

        p { margin: 0 0 $space-md; }
        p:last-child { margin-bottom: 0; }
    }

    &__empty {
        font-family: $font-family-body;
        font-size: 16px;
        color: $white-70;
    }

    &__back-to-top {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: $space-2xl;
        font-family: $font-family-body;
        font-size: 14px;
        color: $white-70;
        text-decoration: none;

        &:hover, &:focus-visible {
            color: $white;
        }
    }
}

// --------------------------------------------------------------------------
// SIDEBAR — año + meses
// --------------------------------------------------------------------------
.udp-calendario-sidebar {
    &__year-form {
        margin-bottom: $space-md;
    }

    &__year-select {
        appearance: none;
        -webkit-appearance: none;
        width: 100%;
        height: 40px;
        padding: 0 36px 0 16px;
        font-family: $font-family-body;
        font-size: 14px;
        color: $white;
        background-color: transparent;
        border: 1px solid rgba($white, 0.2);
        border-radius: 0;
        cursor: pointer;

        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12' fill='none'%3E%3Cpath d='M3 4.5l3 3 3-3' stroke='%23FFFFFF' stroke-width='1.2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 14px center;

        option { color: $dark-1; background-color: $white; }
    }

    &__months-nav {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    &__month {
        a, span {
            display: block;
            padding: 8px 0;
            font-family: $font-family-body;
            font-size: 14px;
            color: $white;
            text-decoration: none;
            border-bottom: 1px solid rgba($white, 0.08);
        }
        a:hover, a:focus-visible {
            color: $brand-yellow;
            outline: none;
        }
        &--empty span { color: rgba($white, 0.3); cursor: default; }
    }
}

// --------------------------------------------------------------------------
// MONTH SECTION
// --------------------------------------------------------------------------
.udp-calendario-month {
    margin-top: $space-2xl;

    &__title {
        margin: 0 0 $space-lg;
        font-family: $font-family-display;
        font-weight: 500;
        font-size: 32px;
        line-height: 1.1;
        color: $white;
    }

    &__list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: $space-md;
    }
}

// --------------------------------------------------------------------------
// ENTRY CALENDARIO
// --------------------------------------------------------------------------
.udp-entry-calendario {
    display: grid;
    grid-template-columns: 140px 1fr;
    gap: $space-md;
    padding: $space-md 0;
    border-bottom: 1px solid rgba($white, 0.1);

    @include media-down(md) {
        grid-template-columns: 1fr;
        gap: $space-2xs;
    }

    &--destacado {
        padding-inline: $space-md;
        border-left: 3px solid $brand-yellow;
        background-color: rgba($brand-yellow, 0.05);
    }

    &__date {
        font-family: $font-family-body;
        font-weight: 500;
        font-size: 14px;
        color: $white-70;
    }

    &__body {
        display: flex;
        flex-direction: column;
        gap: $space-2xs;
    }

    &__meta {
        display: flex;
        gap: $space-2xs;
        flex-wrap: wrap;
    }

    &__tag {
        display: inline-block;
        padding: 2px 8px;
        font-family: $font-family-mono;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: $dark-1;
        background-color: $brand-yellow;
    }

    &__tipo {
        display: inline-block;
        padding: 2px 8px;
        font-family: $font-family-mono;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: $white-70;
        border: 1px solid rgba($white, 0.2);
    }

    &__title {
        margin: 0;
        font-family: $font-family-body;
        font-weight: 500;
        font-size: 16px;
        line-height: 1.4;
        color: $white;
    }

    &__desc {
        margin: 0;
        font-family: $font-family-body;
        font-size: 14px;
        line-height: 20px;
        color: $white-70;
    }

    &__ics {
        margin-top: $space-2xs;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-family: $font-family-body;
        font-size: 12px;
        color: $white;
        text-decoration: none;
        border: 1px solid rgba($white, 0.2);
        padding: 6px 12px;
        border-radius: 9999px;
        align-self: flex-start;
        transition: background-color $transition-base, color $transition-base, border-color $transition-base;

        &:hover, &:focus-visible {
            background-color: $white;
            color: $dark-1;
            border-color: $white;
            outline: none;
        }
    }
}
```

- [ ] **Step 2: Importar en main.scss + build**

Edit `src/scss/main.scss`. Después del último `@import "templates/...";`, añadir:

```scss
@import "templates/calendario-archive";
```

Build:

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -3
```

Expected: build OK.

---

## Task 5: E2E + MEMORY + commit

- [ ] **Step 1: Verificar archive**

```bash
TS=$(date +%s)
echo "=== HTTP /calendario-academico/ ==="
curl -sI "http://localhost:8888/udp/calendario-academico/?nocache=$TS" 2>&1 | head -3
echo ""
echo "=== Markup classes ==="
curl -s "http://localhost:8888/udp/calendario-academico/?nocache=$TS" | grep -oE "udp-(calendario-archive|calendario-sidebar|calendario-month|entry-calendario|archive-filters)[a-z_-]*" | sort -u
echo ""
echo "=== Entries count ==="
curl -s "http://localhost:8888/udp/calendario-academico/?nocache=$TS" | grep -cE 'class="udp-entry-calendario'
echo ""
echo "=== Months sections ==="
curl -s "http://localhost:8888/udp/calendario-academico/?nocache=$TS" | grep -oE '<section id="[a-z]+" class="udp-calendario-month"' | head -5
```

Expected: HTTP 200, classes presentes, entries > 0, secciones de meses.

- [ ] **Step 2: Verificar ICS calendario all-day**

```bash
export MYSQL_PWD=root
CAL_ID=$(/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot udp -sN -e "
SELECT p.ID FROM wp_fnku4yposts p
JOIN wp_fnku4ypostmeta pm ON p.ID=pm.post_id AND pm.meta_key='fecha' AND pm.meta_value != ''
WHERE p.post_type='calendario' AND p.post_status='publish' LIMIT 1;")
echo "CAL_ID=$CAL_ID"
curl -s "http://localhost:8888/udp/?udp_ics=$CAL_ID" | head -10
```

Expected: body contiene `DTSTART;VALUE=DATE:YYYYMMDD` (all-day, no T-time).

- [ ] **Step 3: Filtros**

```bash
TS=$(date +%s)
echo "=== Filtro publico ==="
PUB_ID=$(/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot udp -sN -e "
SELECT t.term_id FROM wp_fnku4yterms t JOIN wp_fnku4yterm_taxonomy tt ON t.term_id=tt.term_id
WHERE tt.taxonomy='publico-udp' AND tt.count > 0 ORDER BY tt.count DESC LIMIT 1;")
curl -s "http://localhost:8888/udp/calendario-academico/?udp_publico=$PUB_ID&nocache=$TS" | grep -cE 'class="udp-entry-calendario'
echo "(con publico filter)"

echo ""
echo "=== Año diferente ==="
curl -s "http://localhost:8888/udp/calendario-academico/?udp_year=2025&nocache=$TS" | grep -cE 'class="udp-entry-calendario'
echo "(año 2025)"
```

- [ ] **Step 4: Cleanup**

```bash
rm -f /tmp/test-udp-query-calendario.php
```

- [ ] **Step 5: MEMORY.md**

Append:

```markdown

### 2026-04-29 — F5a Calendario Académico archive

**Hechos**:
- `templates/page-calendario.php` asignado a página "Calendario Académico" (ID 74). Theme dark, layout 2-col: sidebar sticky con dropdown año + lista de meses anchor + main con intro + secciones por mes.
- Helpers nuevos en `inc/udp-cards.php`: `udp_query_calendario` (no pagina, devuelve entries_by_month), `udp_calendario_data_from_post` (siempre devuelve array, no requiere featured image), `udp_get_calendario_years` (transient 1 día).
- ICS endpoint extendido en `inc/udp-ics.php` para soportar `calendario` post_type además de `agenda`. Para calendario emite all-day events con `DTSTART;VALUE=DATE:YYYYMMDD` y `DTEND` día siguiente.
- 4 partials en `template-parts/archive/`: `calendario-sidebar.php`, `calendario-filters.php`, `calendario-month-section.php`, y `template-parts/blocks/parts/entry-calendario.php`.
- Filtros: `udp_publico` (taxonomía publico-udp) + `udp_tipo` (tipo-udp) + `udp_s` + `udp_year` (en sidebar). Sidebar preserva los demás filtros via hidden inputs.
- Entry destacado: border-left amarillo + bg sutil `rgba($brand-yellow, 0.05)` + tag yellow "Destacado".
- Una sola página por año — no hay paginación. El usuario cambia el año vía dropdown del sidebar.
- SCSS nuevo `_calendario-archive.scss` con: dark theme, 2-col grid (280/1fr), sidebar sticky 100px top, month sections con título Arizona Flare 32px, entries con grid 140/1fr (date + body).

**Decisiones clave**:
- No paginación — calendario académico es un documento "completo" del año. Todos los entries cargan en una página (~500 entries está bien, performance OK).
- Año vía dropdown sidebar en lugar de top filter bar — coincide con el Figma y semánticamente "el año contextualiza toda la página".
- ICS all-day para calendario: `VALUE=DATE` format (YYYYMMDD sin T-time) compatible con Google Calendar / Outlook / Apple. Diferente del ICS de agenda que usa hora_inicio.
- Eyebrow en entries usa primer término de `tipo-udp` (mostrado como text + border, no yellow pill — el yellow está reservado para destacado).

**Pendientes**:
- F5b: Concursos archive + single (con descarga PDF).
- `block_calendario_grid` flex content: diferido a iteración futura.
- JS active-month tracking en sidebar (IntersectionObserver): defer.
```

- [ ] **Step 6: Commit**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add \
  inc/udp-cards.php \
  inc/udp-ics.php \
  templates/page-calendario.php \
  template-parts/archive/calendario-sidebar.php \
  template-parts/archive/calendario-filters.php \
  template-parts/archive/calendario-month-section.php \
  template-parts/blocks/parts/entry-calendario.php \
  src/scss/templates/_calendario-archive.scss \
  src/scss/main.scss \
  MEMORY.md
git commit -m "$(cat <<'EOF'
feat(calendario): F5a archive Calendario Académico

- templates/page-calendario.php asignado a página Calendario Académico
  (ID 74). Theme dark, sidebar sticky con año + meses anchor + main
  intro + secciones por mes con entries.
- inc/udp-cards.php: udp_query_calendario (no pagina, entries_by_month)
  + udp_calendario_data_from_post (siempre array, no requiere featured
  image) + udp_get_calendario_years (transient).
- inc/udp-ics.php: extendida whitelist a 'calendario' + all-day mode
  con DTSTART;VALUE=DATE:YYYYMMDD para entries de calendario.
- 4 partials: sidebar, filters (publico + tipo + udp_s), month-section,
  entry-calendario (con tag DESTACADO + ICS button).
- SCSS dark theme con entry-calendario layout grid 140/1fr.
EOF
)"
```

---

## Verification end-to-end

1. `/calendario-academico/` HTTP 200, dark theme, sidebar sticky, main con intro + meses + entries.
2. Click en mes en sidebar → scroll a sección.
3. `?udp_year=2025` → cambia año, recarga con entries de 2025.
4. `?udp_publico=X` filtra. `?udp_tipo=Y` filtra. Combinaciones AND.
5. Entry destacado visualmente diferenciado.
6. ICS button click → descarga `.ics` all-day.
7. Mobile: sidebar pasa a static, main full width.
