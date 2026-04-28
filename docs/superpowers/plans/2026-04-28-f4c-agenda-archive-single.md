# F4c — Agenda archive (grid + list) + single-evento — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implementar el listado público de Eventos en la página existente "Agenda" (ID 91, slug `agenda-udp`) con dos vistas toggleables (grid + list), filtros (facultad + año + búsqueda), paginación, y el template `single-agenda.php` (light, 2-col sidebar + content + share + related). Helper nuevo `udp_query_agenda()` + `udp_card_data_from_agenda()` siguiendo el patrón de F4b1.

**Architecture:** Page template asignable + nuevo card primitive `card-evento.php` (grid + list modes en un solo partial via `mode` arg) + helper que agrupa la query con orden por meta ACF `fecha`. Single sigue el patrón de F4b1 single-post pero con sidebar meta. ICS endpoint inline via `init` hook para "Agregar al calendario".

**Tech Stack:** WordPress, ACF Pro, WP_Query (con meta_query/orderby=meta_value), paginate_links, SCSS BEM.

**Reference:** Spec `docs/superpowers/specs/2026-04-28-f4c-agenda-archive-single-design.md`.

---

## Inventario de archivos

**Crear:**
- `wp-content/themes/starter-theme/templates/page-eventos.php`
- `wp-content/themes/starter-theme/template-parts/archive/eventos-filters.php`
- `wp-content/themes/starter-theme/template-parts/blocks/parts/card-evento.php`
- `wp-content/themes/starter-theme/single-agenda.php`
- `wp-content/themes/starter-theme/template-parts/single/event-meta.php`
- `wp-content/themes/starter-theme/template-parts/single/event-related.php`
- `wp-content/themes/starter-theme/inc/udp-ics.php` — endpoint `?udp_ics=ID`
- `wp-content/themes/starter-theme/src/scss/blocks/_card-evento.scss`
- `wp-content/themes/starter-theme/src/scss/templates/_eventos-archive.scss`
- `wp-content/themes/starter-theme/src/scss/templates/_eventos-single.scss`

**Modificar:**
- `wp-content/themes/starter-theme/inc/udp-cards.php` — `udp_query_agenda()` + `udp_card_data_from_agenda()`.
- `wp-content/themes/starter-theme/functions.php` — `require_once inc/udp-ics.php`.
- `wp-content/themes/starter-theme/src/scss/main.scss` — 3 imports nuevos.

---

## Task 1: Helpers `udp_query_agenda` + `udp_card_data_from_agenda`

**Files:**
- Modify: `inc/udp-cards.php`

- [ ] **Step 1: Añadir AL FINAL del archivo**

```php

/**
 * Convierte un WP_Post (CPT agenda) a la forma Card adaptada para evento.
 * Devuelve null si no hay featured image (igual que noticias).
 *
 * Card shape evento:
 *   - eyebrow:       primer post_tag (case original)
 *   - eyebrow_color: 'yellow' (hardcoded)
 *   - titulo, imagen, href, target, post_id: igual que noticia
 *   - fecha:         del campo ACF `fecha` (Y-m-d)
 *   - fecha_display: human readable "10 Marzo 2026"
 *   - hora_display:  del ACF hora_inicio "12:00 hrs"
 *   - lugar:         ACF lugar
 */
function udp_card_data_from_agenda( WP_Post $post ): ?array {
    $thumb_id = get_post_thumbnail_id( $post->ID );
    if ( ! $thumb_id ) {
        return null;
    }

    $imagen_url = wp_get_attachment_image_url( $thumb_id, 'medium_large' );
    if ( ! $imagen_url ) {
        return null;
    }

    $metadata = wp_get_attachment_metadata( $thumb_id );

    // Eyebrow desde primer post_tag
    $eyebrow_text = '';
    $tags = get_the_terms( $post->ID, 'post_tag' );
    if ( ! is_wp_error( $tags ) && ! empty( $tags ) ) {
        $eyebrow_text = $tags[0]->name;
    }

    // Fecha ACF (puede no existir)
    $fecha_acf = function_exists( 'get_field' ) ? get_field( 'fecha', $post->ID ) : '';
    $hora_acf  = function_exists( 'get_field' ) ? get_field( 'hora_inicio', $post->ID ) : '';
    $lugar     = function_exists( 'get_field' ) ? (string) get_field( 'lugar', $post->ID ) : '';

    $fecha_iso = '';
    $fecha_disp = '';
    if ( $fecha_acf ) {
        $ts = strtotime( $fecha_acf );
        if ( $ts ) {
            $fecha_iso  = date( 'Y-m-d', $ts );
            $fecha_disp = date_i18n( 'j \d\e F \d\e Y', $ts );  // "10 de Marzo de 2026"
        }
    }

    $hora_disp = '';
    if ( $hora_acf ) {
        $ts_h = strtotime( $hora_acf );
        if ( $ts_h ) {
            $hora_disp = date_i18n( 'H:i', $ts_h ) . ' hrs';
        }
    }

    return array(
        'post_id'       => (int) $post->ID,
        'eyebrow'       => $eyebrow_text,
        'eyebrow_color' => 'yellow',
        'titulo'        => get_the_title( $post ),
        'imagen'        => array(
            'id'    => (int) $thumb_id,
            'url'   => $imagen_url,
            'alt'   => (string) get_post_meta( $thumb_id, '_wp_attachment_image_alt', true ),
            'sizes' => is_array( $metadata ) && isset( $metadata['sizes'] ) ? $metadata['sizes'] : array(),
        ),
        'fecha'         => $fecha_iso,
        'fecha_display' => $fecha_disp,
        'hora_display'  => $hora_disp,
        'lugar'         => $lugar,
        'href'          => get_permalink( $post ),
        'target'        => '',
    );
}

/**
 * Wrapper sobre WP_Query para archive Agenda.
 * Order por meta `fecha` ASC (próximos primero).
 *
 * @param array $filters {
 *     @type int    $facultad   term_id de facultad. 0 = sin filtro.
 *     @type int    $year       Año (YYYY) — filtra por meta `fecha`. 0 = sin filtro.
 *     @type string $s          Texto búsqueda. '' = sin búsqueda.
 *     @type int    $paged      Página 1-based. Default 1.
 *     @type int    $limit      Items por página. Default 6.
 *     @type array  $exclude    Post IDs a excluir.
 * }
 * @return array { cards, total, max_pages, paged }
 */
function udp_query_agenda( array $filters ): array {
    $facultad = (int) ( $filters['facultad'] ?? 0 );
    $year     = (int) ( $filters['year']     ?? 0 );
    $s        = trim( (string) ( $filters['s'] ?? '' ) );
    $paged    = max( 1, (int) ( $filters['paged'] ?? 1 ) );
    $limit    = max( 1, (int) ( $filters['limit'] ?? 6 ) );
    $exclude  = isset( $filters['exclude'] ) && is_array( $filters['exclude'] ) ? array_map( 'intval', $filters['exclude'] ) : array();

    $args = array(
        'post_type'      => 'agenda',
        'post_status'    => 'publish',
        'posts_per_page' => $limit,
        'paged'          => $paged,
        'meta_key'       => 'fecha',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
    );

    $tax_query = array();
    if ( $facultad > 0 ) {
        $tax_query[] = array( 'taxonomy' => 'facultad', 'field' => 'term_id', 'terms' => array( $facultad ) );
    }
    if ( ! empty( $tax_query ) ) {
        $args['tax_query'] = $tax_query;
    }

    if ( $year > 0 ) {
        // Filtrar por meta fecha YYYY-MM-DD que empieza con el año
        $args['meta_query'] = array(
            array(
                'key'     => 'fecha',
                'value'   => sprintf( '%04d', $year ),
                'compare' => 'LIKE',
            ),
        );
    }

    if ( $s !== '' ) {
        $args['s'] = $s;
    }

    if ( ! empty( $exclude ) ) {
        $args['post__not_in'] = $exclude;
    }

    $q = new WP_Query( $args );

    $cards = array();
    foreach ( $q->posts as $post ) {
        $card = udp_card_data_from_agenda( $post );
        if ( $card ) {
            $cards[] = $card;
        }
    }

    return array(
        'cards'     => $cards,
        'total'     => (int) $q->found_posts,
        'max_pages' => $q->found_posts > 0 ? (int) ceil( $q->found_posts / $limit ) : 0,
        'paged'     => $paged,
    );
}
```

- [ ] **Step 2: Validar PHP**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/inc/udp-cards.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 3: Smoke test**

Crear `/tmp/test-udp-query-agenda.php`:

```php
<?php
$result = udp_query_agenda( array( 'paged' => 1, 'limit' => 6 ) );
WP_CLI::log( 'cards: ' . count( $result['cards'] ) );
WP_CLI::log( 'total: ' . $result['total'] );
WP_CLI::log( 'max_pages: ' . $result['max_pages'] );
if ( ! empty( $result['cards'] ) ) {
    $c = $result['cards'][0];
    WP_CLI::log( 'first titulo: ' . $c['titulo'] );
    WP_CLI::log( 'first eyebrow: ' . $c['eyebrow'] );
    WP_CLI::log( 'first fecha_display: ' . $c['fecha_display'] );
    WP_CLI::log( 'first hora_display: ' . $c['hora_display'] );
    WP_CLI::log( 'first lugar: ' . $c['lugar'] );
}
WP_CLI::success( 'Agenda OK' );
```

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval-file /tmp/test-udp-query-agenda.php --path=/Applications/MAMP/htdocs/udp/cms 2>&1 | tail -10
```

Expected: cards >= 1, total > 0, datos del primer evento (titulo, eyebrow del post_tag, fecha_display human, hora_display, lugar). Si cards = 0 y total > 0, posible que los eventos más cercanos no tengan featured image — documentar.

---

## Task 2: ICS endpoint

**Files:**
- Create: `inc/udp-ics.php`
- Modify: `functions.php`

- [ ] **Step 1: Crear handler ICS**

Create `inc/udp-ics.php`:

```php
<?php
/**
 * ICS calendar endpoint
 *
 * Detecta `?udp_ics={post_id}` en init y emite un VCALENDAR/VEVENT del
 * evento (CPT agenda). Permite que el botón "Agregar al calendario" del
 * single-event genere el archivo .ics descargable.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
    if ( ! isset( $_GET['udp_ics'] ) ) {
        return;
    }
    $post_id = (int) $_GET['udp_ics'];
    if ( $post_id <= 0 ) {
        return;
    }
    $post = get_post( $post_id );
    if ( ! $post || $post->post_type !== 'agenda' || $post->post_status !== 'publish' ) {
        return;
    }

    $fecha       = function_exists( 'get_field' ) ? get_field( 'fecha', $post_id ) : '';
    $hora_inicio = function_exists( 'get_field' ) ? get_field( 'hora_inicio', $post_id ) : '';
    $hora_fin    = function_exists( 'get_field' ) ? get_field( 'hora_termino', $post_id ) : '';
    $lugar       = function_exists( 'get_field' ) ? (string) get_field( 'lugar', $post_id ) : '';

    if ( ! $fecha ) {
        wp_die( esc_html__( 'El evento no tiene fecha definida.', 'starter-theme' ), 404 );
    }

    // Construir DTSTART y DTEND
    $start_str = $fecha . ( $hora_inicio ? ' ' . $hora_inicio : ' 00:00:00' );
    $end_str   = $fecha . ( $hora_fin    ? ' ' . $hora_fin    : ' 23:59:00' );
    $start_ts  = strtotime( $start_str );
    $end_ts    = strtotime( $end_str );
    if ( ! $start_ts ) {
        $start_ts = strtotime( $fecha );
    }
    if ( ! $end_ts || $end_ts <= $start_ts ) {
        $end_ts = $start_ts + 3600;
    }

    $title = wp_strip_all_tags( get_the_title( $post ) );
    $desc  = wp_strip_all_tags( get_the_excerpt( $post ) );
    $uid   = $post_id . '@' . wp_parse_url( home_url(), PHP_URL_HOST );

    nocache_headers();
    header( 'Content-Type: text/calendar; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="evento-' . $post->post_name . '.ics"' );

    $ics  = "BEGIN:VCALENDAR\r\n";
    $ics .= "VERSION:2.0\r\n";
    $ics .= "PRODID:-//UDP//Eventos//ES\r\n";
    $ics .= "METHOD:PUBLISH\r\n";
    $ics .= "BEGIN:VEVENT\r\n";
    $ics .= "UID:" . $uid . "\r\n";
    $ics .= "DTSTAMP:" . gmdate( 'Ymd\THis\Z' ) . "\r\n";
    $ics .= "DTSTART:" . gmdate( 'Ymd\THis\Z', $start_ts ) . "\r\n";
    $ics .= "DTEND:" . gmdate( 'Ymd\THis\Z', $end_ts ) . "\r\n";
    $ics .= "SUMMARY:" . str_replace( array( "\r", "\n" ), ' ', $title ) . "\r\n";
    if ( $desc ) {
        $ics .= "DESCRIPTION:" . str_replace( array( "\r", "\n" ), ' ', $desc ) . "\r\n";
    }
    if ( $lugar ) {
        $ics .= "LOCATION:" . str_replace( array( "\r", "\n" ), ' ', $lugar ) . "\r\n";
    }
    $ics .= "URL:" . get_permalink( $post ) . "\r\n";
    $ics .= "END:VEVENT\r\n";
    $ics .= "END:VCALENDAR\r\n";

    echo $ics; // phpcs:ignore — text/calendar contenido construido por nosotros
    exit;
} );
```

- [ ] **Step 2: Wire en functions.php**

Edit `functions.php`. Localizar la línea `require_once STARTER_BS5_DIR . 'inc/udp-cards.php';` (añadida en F4a) y AÑADIR DESPUÉS:

```php
require_once STARTER_BS5_DIR . 'inc/udp-ics.php';
```

- [ ] **Step 3: Validar PHP**

```bash
PHP=/Applications/MAMP/bin/php/php8.4.1/bin/php
$PHP -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/inc/udp-ics.php
$PHP -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/functions.php
```

Expected: 2× `No syntax errors detected`.

- [ ] **Step 4: Smoke test del endpoint**

Encontrar un evento publicado con fecha:

```bash
export MYSQL_PWD=root
EVENT_ID=$(/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot udp -sN -e "
SELECT p.ID FROM wp_fnku4yposts p
JOIN wp_fnku4ypostmeta pm ON p.ID=pm.post_id AND pm.meta_key='fecha' AND pm.meta_value != ''
WHERE p.post_type='agenda' AND p.post_status='publish'
ORDER BY p.post_date DESC LIMIT 1;")
echo "EVENT_ID=$EVENT_ID"

curl -sI "http://localhost:8888/udp/?udp_ics=$EVENT_ID" 2>&1 | head -5
echo ""
echo "=== ICS content (head) ==="
curl -s "http://localhost:8888/udp/?udp_ics=$EVENT_ID" | head -10
```

Expected: HTTP 200, `Content-Type: text/calendar`, body comienza con `BEGIN:VCALENDAR` y contiene `BEGIN:VEVENT`, `SUMMARY`, `DTSTART`, `DTEND`.

---

## Task 3: Card primitive `card-evento.php`

**Files:**
- Create: `template-parts/blocks/parts/card-evento.php`

- [ ] **Step 1: Crear el partial**

```php
<?php
/**
 * Card primitive — Evento
 *
 * Soporta 2 modos: 'grid' (image-left + body con CTA circular) y
 * 'list' (row sin imagen, eyebrow + title + date columns).
 *
 * @package Starter_Theme
 *
 * @var array $args ['card' => array, 'theme' => 'dark'|'light', 'mode' => 'grid'|'list']
 */
$card  = isset( $args['card'] ) && is_array( $args['card'] ) ? $args['card'] : array();
$theme = isset( $args['theme'] ) && in_array( $args['theme'], array( 'dark', 'light' ), true ) ? $args['theme'] : 'dark';
$mode  = isset( $args['mode'] )  && in_array( $args['mode'],  array( 'grid', 'list' ), true ) ? $args['mode']  : 'grid';

$href      = $card['href'] ?? '';
$titulo    = $card['titulo'] ?? '';
$imagen    = is_array( $card['imagen'] ?? null ) ? $card['imagen'] : array();
$eyebrow   = $card['eyebrow'] ?? '';
$fecha_iso = $card['fecha'] ?? '';
$fecha_d   = $card['fecha_display'] ?? '';
$hora_d    = $card['hora_display'] ?? '';
$lugar     = $card['lugar'] ?? '';

if ( empty( $href ) || empty( $titulo ) ) {
    return;
}
if ( $mode === 'grid' && empty( $imagen['url'] ?? '' ) ) {
    return;
}

$class = 'udp-card-evento udp-card-evento--' . $mode . ' udp-card-evento--' . $theme;
$datetime_combined = trim( $fecha_d . ( $hora_d ? ', ' . $hora_d : '' ) );
?>
<?php if ( $mode === 'list' ) : ?>
<a href="<?php echo esc_url( $href ); ?>" class="<?php echo esc_attr( $class ); ?>">
    <?php if ( $eyebrow ) : ?>
        <span class="udp-card-evento__eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
    <?php endif; ?>
    <h3 class="udp-card-evento__title"><?php echo esc_html( $titulo ); ?></h3>
    <?php if ( $fecha_d ) : ?>
        <time class="udp-card-evento__date" datetime="<?php echo esc_attr( $fecha_iso ); ?>"><?php echo esc_html( $fecha_d ); ?></time>
    <?php endif; ?>
</a>
<?php else : ?>
<a href="<?php echo esc_url( $href ); ?>" class="<?php echo esc_attr( $class ); ?>">
    <figure class="udp-card-evento__media">
        <img
            src="<?php echo esc_url( $imagen['url'] ); ?>"
            alt="<?php echo esc_attr( $imagen['alt'] ?? '' ); ?>"
            loading="lazy"
            decoding="async"
        />
    </figure>
    <div class="udp-card-evento__body">
        <h3 class="udp-card-evento__title"><?php echo esc_html( $titulo ); ?></h3>
        <?php if ( $eyebrow ) : ?>
            <p class="udp-card-evento__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
        <?php endif; ?>
        <?php if ( $datetime_combined ) : ?>
            <p class="udp-card-evento__datetime"><?php echo esc_html( $datetime_combined ); ?></p>
        <?php endif; ?>
        <?php if ( $lugar ) : ?>
            <p class="udp-card-evento__lugar"><?php echo esc_html( $lugar ); ?></p>
        <?php endif; ?>
        <span class="udp-card-evento__cta" aria-hidden="true">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M5 3h8v8M13 3 3 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </span>
    </div>
</a>
<?php endif; ?>
```

- [ ] **Step 2: Validar PHP**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/blocks/parts/card-evento.php
```

Expected: `No syntax errors detected`.

---

## Task 4: Eventos filters partial

**Files:**
- Create: `template-parts/archive/eventos-filters.php`

- [ ] **Step 1: Crear el partial**

```php
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

// Años con eventos (basado en meta `fecha` ACF)
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

// Conservar `view` como hidden input para preservar la vista al filtrar
$view_active = isset( $_GET['view'] ) && $_GET['view'] === 'list' ? 'list' : 'grid';
?>
<form class="udp-archive-filters" method="get" action="<?php echo esc_url( $action_url ); ?>" role="search">

    <input type="hidden" name="view" value="<?php echo esc_attr( $view_active ); ?>" />

    <div class="udp-archive-filters__group">
        <label for="udp-filter-facultad" class="visually-hidden"><?php esc_html_e( 'Selecciona facultad', 'starter-theme' ); ?></label>
        <select id="udp-filter-facultad" name="facultad" class="udp-archive-filters__select" data-udp-autosubmit>
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
```

- [ ] **Step 2: Validar PHP**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/archive/eventos-filters.php
```

Expected: `No syntax errors detected`.

---

## Task 5: Page template `page-eventos.php`

**Files:**
- Create: `templates/page-eventos.php`

- [ ] **Step 1: Crear el page template**

```php
<?php
/**
 * Template Name: Eventos (Archive)
 *
 * Page template asignable a la página "Agenda" (ID 91). Renderiza
 * filtros (facultad + año + búsqueda) + view toggle (grid|list) +
 * cards en el modo seleccionado + paginación. Theme dark.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$facultad = isset( $_GET['facultad'] ) ? (int) $_GET['facultad'] : 0;
$year     = isset( $_GET['year'] )     ? (int) $_GET['year']     : 0;
$s        = isset( $_GET['udp_s'] )    ? sanitize_text_field( wp_unslash( $_GET['udp_s'] ) ) : '';
$paged    = max( 1, (int) ( get_query_var( 'paged' ) ?: ( $_GET['paged'] ?? 1 ) ) );
$view     = ( isset( $_GET['view'] ) && $_GET['view'] === 'list' ) ? 'list' : 'grid';

$result = function_exists( 'udp_query_agenda' )
    ? udp_query_agenda( array(
        'facultad' => $facultad,
        'year'     => $year,
        's'        => $s,
        'paged'    => $paged,
        'limit'    => $view === 'list' ? 12 : 6,
    ) )
    : array( 'cards' => array(), 'total' => 0, 'max_pages' => 0, 'paged' => 1 );

$cards     = $result['cards'];
$max_pages = $result['max_pages'];

// URLs del toggle (preservan filtros)
$base_args = array_filter( array(
    'facultad' => $facultad ?: null,
    'year'     => $year     ?: null,
    'udp_s'    => $s        ?: null,
) );
$url_grid = add_query_arg( array_merge( $base_args, array( 'view' => 'grid' ) ), get_permalink( get_the_ID() ) );
$url_list = add_query_arg( array_merge( $base_args, array( 'view' => 'list' ) ), get_permalink( get_the_ID() ) );

get_header();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-eventos-archive udp-eventos-archive--' . esc_attr( $view ) ); ?>>

    <header class="udp-eventos-archive__header">
        <?php
        get_template_part(
            'template-parts/sections/breadcrumb',
            null,
            array( 'page_id' => get_the_ID() )
        );
        ?>
        <h1 class="udp-eventos-archive__title"><?php the_title(); ?></h1>
        <div class="udp-eventos-archive__toggle" role="tablist" aria-label="<?php esc_attr_e( 'Vista de eventos', 'starter-theme' ); ?>">
            <a href="<?php echo esc_url( $url_grid ); ?>" class="udp-eventos-archive__toggle-btn<?php echo $view === 'grid' ? ' udp-eventos-archive__toggle-btn--active' : ''; ?>" aria-label="<?php esc_attr_e( 'Vista grid', 'starter-theme' ); ?>" aria-pressed="<?php echo $view === 'grid' ? 'true' : 'false'; ?>">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><rect x="2" y="2" width="5" height="5" stroke="currentColor" stroke-width="1.4"/><rect x="9" y="2" width="5" height="5" stroke="currentColor" stroke-width="1.4"/><rect x="2" y="9" width="5" height="5" stroke="currentColor" stroke-width="1.4"/><rect x="9" y="9" width="5" height="5" stroke="currentColor" stroke-width="1.4"/></svg>
            </a>
            <a href="<?php echo esc_url( $url_list ); ?>" class="udp-eventos-archive__toggle-btn<?php echo $view === 'list' ? ' udp-eventos-archive__toggle-btn--active' : ''; ?>" aria-label="<?php esc_attr_e( 'Vista lista', 'starter-theme' ); ?>" aria-pressed="<?php echo $view === 'list' ? 'true' : 'false'; ?>">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><line x1="3" y1="4" x2="13" y2="4" stroke="currentColor" stroke-width="1.4"/><line x1="3" y1="8" x2="13" y2="8" stroke="currentColor" stroke-width="1.4"/><line x1="3" y1="12" x2="13" y2="12" stroke="currentColor" stroke-width="1.4"/></svg>
            </a>
        </div>
    </header>

    <hr class="udp-eventos-archive__separator" aria-hidden="true" />

    <?php
    get_template_part(
        'template-parts/archive/eventos-filters',
        null,
        array( 'facultad' => $facultad, 'year' => $year, 's' => $s )
    );
    ?>

    <hr class="udp-eventos-archive__separator" aria-hidden="true" />

    <?php if ( ! empty( $cards ) ) : ?>
        <ul class="udp-eventos-archive__list udp-eventos-archive__list--<?php echo esc_attr( $view ); ?>">
            <?php foreach ( $cards as $card ) : ?>
                <li class="udp-eventos-archive__item">
                    <?php
                    get_template_part(
                        'template-parts/blocks/parts/card-evento',
                        null,
                        array( 'card' => $card, 'theme' => 'dark', 'mode' => $view )
                    );
                    ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <p class="udp-eventos-archive__empty">
            <?php esc_html_e( 'No se encontraron eventos con esos filtros.', 'starter-theme' ); ?>
        </p>
    <?php endif; ?>

    <?php
    get_template_part(
        'template-parts/archive/pagination',
        null,
        array( 'paged' => $paged, 'max_pages' => $max_pages )
    );
    ?>

</article>

<?php
get_footer();
```

- [ ] **Step 2: Asignar template a página Agenda (ID 91)**

```bash
export MYSQL_PWD=root
/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot udp -e "
UPDATE wp_fnku4ypostmeta SET meta_value='templates/page-eventos.php'
WHERE post_id=91 AND meta_key='_wp_page_template';"

/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot udp -sN -e "
SELECT meta_value FROM wp_fnku4ypostmeta WHERE post_id=91 AND meta_key='_wp_page_template';"
```

Si la query devuelve vacío (no existía la row), insertar:

```bash
/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot udp -e "
INSERT INTO wp_fnku4ypostmeta (post_id, meta_key, meta_value)
VALUES (91, '_wp_page_template', 'templates/page-eventos.php');"
```

Verificar 1 row:

```bash
/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot udp -sN -e "
SELECT COUNT(*), meta_value FROM wp_fnku4ypostmeta WHERE post_id=91 AND meta_key='_wp_page_template';"
```

Expected: `1 templates/page-eventos.php`.

- [ ] **Step 3: Validar PHP**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/templates/page-eventos.php
```

Expected: `No syntax errors detected`.

---

## Task 6: Single-event template + partials + ICS button

**Files:**
- Create: `single-agenda.php`
- Create: `template-parts/single/event-meta.php`
- Create: `template-parts/single/event-related.php`

- [ ] **Step 1: Crear `single-agenda.php`**

```php
<?php
/**
 * Single Evento (CPT agenda)
 *
 * Light theme. Layout 2-col: sidebar meta + main content.
 * Reusa post-share partial (igual que single-post).
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
    the_post();
    ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-single-event' ); ?>>

        <header class="udp-single-event__header">
            <?php
            $archive_url = get_permalink( 91 );  // Página Agenda ID 91
            if ( ! $archive_url ) {
                $archive_url = home_url( '/agenda-udp/' );
            }
            ?>
            <a class="udp-single-event__back" href="<?php echo esc_url( $archive_url ); ?>">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                    <path d="M9 3L5 7l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <?php esc_html_e( 'Volver a Eventos', 'starter-theme' ); ?>
            </a>
            <h1 class="udp-single-event__title"><?php the_title(); ?></h1>
        </header>

        <hr class="udp-single-event__separator" aria-hidden="true" />

        <div class="udp-single-event__body">

            <aside class="udp-single-event__sidebar">
                <?php get_template_part( 'template-parts/single/event-meta', null, array( 'post_id' => get_the_ID() ) ); ?>
            </aside>

            <div class="udp-single-event__content">
                <?php if ( has_post_thumbnail() ) : ?>
                    <figure class="udp-single-event__featured">
                        <?php the_post_thumbnail( 'large' ); ?>
                    </figure>
                <?php endif; ?>

                <?php
                $subtitulo = function_exists( 'get_field' ) ? (string) get_field( 'subtitulo' ) : '';
                if ( $subtitulo ) :
                ?>
                    <p class="udp-single-event__subtitulo"><?php echo esc_html( $subtitulo ); ?></p>
                <?php endif; ?>

                <div class="udp-single-event__entry-content">
                    <?php the_content(); ?>
                </div>
            </div>

        </div>

        <?php get_template_part( 'template-parts/single/post-share', null, array( 'post_id' => get_the_ID() ) ); ?>

        <?php get_template_part( 'template-parts/single/event-related', null, array( 'post_id' => get_the_ID() ) ); ?>

    </article>

    <?php
endwhile;

get_footer();
```

- [ ] **Step 2: Crear `template-parts/single/event-meta.php`**

```php
<?php
/**
 * Single Event > Sidebar meta
 *
 * Eyebrow + Día + Hora + Dirección + Entrada + Unidad Académica + 2 CTAs.
 *
 * @package Starter_Theme
 *
 * @var array $args ['post_id' => int]
 */
$post_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();
if ( ! $post_id ) {
    return;
}

// Eyebrow desde post_tag
$eyebrow_text = '';
$tags = get_the_terms( $post_id, 'post_tag' );
if ( ! is_wp_error( $tags ) && ! empty( $tags ) ) {
    $eyebrow_text = $tags[0]->name;
}

// Datos ACF
$fecha       = function_exists( 'get_field' ) ? get_field( 'fecha', $post_id ) : '';
$hora_inicio = function_exists( 'get_field' ) ? get_field( 'hora_inicio', $post_id ) : '';
$hora_fin    = function_exists( 'get_field' ) ? get_field( 'hora_termino', $post_id ) : '';
$lugar       = function_exists( 'get_field' ) ? (string) get_field( 'lugar', $post_id ) : '';
$inscrip_url = function_exists( 'get_field' ) ? (string) get_field( 'inscripciones', $post_id ) : '';

$fecha_disp = '';
if ( $fecha ) {
    $ts = strtotime( $fecha );
    if ( $ts ) {
        $fecha_disp = date_i18n( 'j \d\e F \d\e Y', $ts );
    }
}

$hora_disp = '';
if ( $hora_inicio ) {
    $ts_h = strtotime( $hora_inicio );
    if ( $ts_h ) {
        $hora_disp = date_i18n( 'H:i', $ts_h ) . ' hrs';
        if ( $hora_fin ) {
            $ts_f = strtotime( $hora_fin );
            if ( $ts_f ) {
                $hora_disp = date_i18n( 'H:i', $ts_h ) . ' - ' . date_i18n( 'H:i', $ts_f ) . ' hrs';
            }
        }
    }
}

// Unidad académica = primer término de facultad
$unidad = '';
$facultades = get_the_terms( $post_id, 'facultad' );
if ( ! is_wp_error( $facultades ) && ! empty( $facultades ) ) {
    $unidad = $facultades[0]->name;
}

// ICS endpoint
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
```

- [ ] **Step 3: Crear `template-parts/single/event-related.php`**

```php
<?php
/**
 * Single Event > Te podría interesar
 *
 * 3 eventos relacionados por facultad primaria. Si <3, fallback a más
 * próximos (ASC desde hoy).
 *
 * @package Starter_Theme
 *
 * @var array $args ['post_id' => int]
 */
$current_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();
if ( ! $current_id ) {
    return;
}

$primary_facultad = 0;
$facultades = get_the_terms( $current_id, 'facultad' );
if ( ! is_wp_error( $facultades ) && ! empty( $facultades ) ) {
    $primary_facultad = (int) $facultades[0]->term_id;
}

$base_args = array(
    'post_type'      => 'agenda',
    'posts_per_page' => 3,
    'post__not_in'   => array( $current_id ),
    'meta_key'       => 'fecha',
    'orderby'        => 'meta_value',
    'order'          => 'ASC',
    'no_found_rows'  => true,
);

if ( $primary_facultad ) {
    $args1 = $base_args;
    $args1['tax_query'] = array(
        array( 'taxonomy' => 'facultad', 'field' => 'term_id', 'terms' => array( $primary_facultad ) ),
    );
    $q = new WP_Query( $args1 );
    $posts = $q->posts;
} else {
    $posts = array();
}

if ( count( $posts ) < 3 ) {
    $needed   = 3 - count( $posts );
    $exclude  = array_merge( array( $current_id ), wp_list_pluck( $posts, 'ID' ) );
    $args2    = $base_args;
    $args2['post__not_in']   = $exclude;
    $args2['posts_per_page'] = $needed;
    $q2 = new WP_Query( $args2 );
    $posts = array_merge( $posts, $q2->posts );
}

$cards = array();
foreach ( $posts as $post ) {
    $card = function_exists( 'udp_card_data_from_agenda' ) ? udp_card_data_from_agenda( $post ) : null;
    if ( $card ) {
        $cards[] = $card;
    }
}

if ( empty( $cards ) ) {
    return;
}
?>
<section class="udp-single-event__related">
    <div class="udp-single-event__related-inner">
        <h2 class="udp-single-event__related-title"><?php esc_html_e( 'Te podría interesar', 'starter-theme' ); ?></h2>
        <ul class="udp-single-event__related-list">
            <?php foreach ( $cards as $card ) : ?>
                <li class="udp-single-event__related-item">
                    <?php
                    get_template_part(
                        'template-parts/blocks/parts/card-evento',
                        null,
                        array( 'card' => $card, 'theme' => 'dark', 'mode' => 'grid' )
                    );
                    ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
```

- [ ] **Step 4: Validar PHP de los 3 archivos**

```bash
PHP=/Applications/MAMP/bin/php/php8.4.1/bin/php
$PHP -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/single-agenda.php
$PHP -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/single/event-meta.php
$PHP -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/single/event-related.php
```

Expected: 3× `No syntax errors detected`.

---

## Task 7: SCSS — card-evento + archive + single

**Files:**
- Create: `src/scss/blocks/_card-evento.scss`
- Create: `src/scss/templates/_eventos-archive.scss`
- Create: `src/scss/templates/_eventos-single.scss`
- Modify: `src/scss/main.scss`

- [ ] **Step 1: `_card-evento.scss`**

```scss
// ==========================================================================
// CARD EVENTO — primitive con 2 modos: grid (image-left + CTA) y list (row)
// Usado en archive Eventos + related en single-event.
// ==========================================================================

.udp-card-evento {
    display: block;
    color: inherit;
    text-decoration: none;
    position: relative;

    // ---- GRID MODE ----
    &--grid {
        display: flex;
        gap: $space-md;
        align-items: flex-start;

        .udp-card-evento__media {
            flex: 0 0 228px;
            width: 228px;
            height: 275px;
            margin: 0;
            overflow: hidden;
            background: $dark-2;

            img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
                transition: transform 0.4s ease;
            }
        }

        .udp-card-evento__body {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: $space-2xs;
            padding-right: 60px;  // espacio para el CTA absoluto
            position: relative;
            min-height: 275px;
        }

        .udp-card-evento__title {
            margin: 0;
            font-family: $font-family-body;
            font-weight: 500;
            font-size: 18px;
            line-height: 1.3;
            color: inherit;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .udp-card-evento__eyebrow {
            margin: $space-sm 0 0;
            font-family: $font-family-mono;
            font-size: 12px;
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: $white-70;
        }

        .udp-card-evento__datetime,
        .udp-card-evento__lugar {
            margin: 0;
            font-family: $font-family-body;
            font-size: 14px;
            line-height: 20px;
            color: $white;
        }

        .udp-card-evento__lugar {
            color: $white-70;
            font-size: 12px;
        }

        .udp-card-evento__cta {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 48px;
            height: 48px;
            border-radius: 9999px;
            border: 1px solid $gray-medium;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: $white;
            transition:
                background-color $transition-base,
                color $transition-base,
                border-color $transition-base;
        }

        @media (prefers-reduced-motion: no-preference) {
            &:hover .udp-card-evento__media img,
            &:focus-visible .udp-card-evento__media img {
                transform: scale(1.04);
            }
        }

        &:hover .udp-card-evento__cta,
        &:focus-visible .udp-card-evento__cta {
            background-color: $white;
            color: $dark-1;
            border-color: $white;
        }

        @include media-down(md) {
            flex-direction: column;
            gap: $space-sm;

            .udp-card-evento__media {
                width: 100%;
                height: auto;
                aspect-ratio: 16 / 9;
                flex: 0 0 auto;
            }

            .udp-card-evento__body {
                padding-right: 0;
                min-height: 0;
            }

            .udp-card-evento__cta {
                position: static;
                margin-top: $space-sm;
            }
        }
    }

    // ---- LIST MODE ----
    &--list {
        display: grid;
        grid-template-columns: 140px 1fr 200px;
        gap: $space-md;
        align-items: center;
        padding: $space-md 0;
        border-bottom: 1px solid rgba($white, 0.15);
        transition: background-color $transition-base;

        &:hover,
        &:focus-visible {
            background-color: rgba($white, 0.03);
            outline: none;
        }

        .udp-card-evento__eyebrow {
            margin: 0;
            font-family: $font-family-mono;
            font-size: 12px;
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: $white-70;
        }

        .udp-card-evento__title {
            margin: 0;
            font-family: $font-family-body;
            font-weight: 500;
            font-size: 16px;
            line-height: 1.4;
            color: $white;
        }

        .udp-card-evento__date {
            font-family: $font-family-body;
            font-size: 14px;
            color: $white-70;
            text-align: right;
        }

        @include media-down(md) {
            grid-template-columns: 1fr;
            gap: $space-2xs;
            padding: $space-sm 0;

            .udp-card-evento__date {
                text-align: left;
            }
        }
    }

    // ---- LIGHT THEME (related en single-event) ----
    &--light {
        // Grid mode con texto dark
        &.udp-card-evento--grid {
            .udp-card-evento__title { color: $dark-1; }
            .udp-card-evento__eyebrow { color: $dark-2; }
            .udp-card-evento__datetime { color: $dark-1; }
            .udp-card-evento__lugar { color: $dark-2; }
            .udp-card-evento__cta {
                color: $dark-1;
                border-color: rgba($dark-1, 0.2);
            }
            &:hover .udp-card-evento__cta,
            &:focus-visible .udp-card-evento__cta {
                background-color: $dark-1;
                color: $white;
                border-color: $dark-1;
            }
        }
    }
}
```

- [ ] **Step 2: `_eventos-archive.scss`**

```scss
// ==========================================================================
// EVENTOS ARCHIVE — page template `page-eventos.php`
// Theme dark. Header con toggle grid/list. Filtros + body + paginación.
// Reusa estilos de filtros y paginación de `_noticias-archive.scss`.
// ==========================================================================

.udp-eventos-archive {
    background-color: $dark-1;
    color: $white;
    padding-bottom: $space-3xl;

    &__header {
        max-width: 1440px;
        margin-inline: auto;
        padding: $space-2xl $space-3xl 0;
        position: relative;

        @include media-down(md) {
            padding: $space-xl $space-sm 0;
        }

        .udp-breadcrumb__item,
        .udp-breadcrumb__link,
        .udp-breadcrumb__current {
            color: $white;
        }
        .udp-breadcrumb__sep { color: $white-70; }
    }

    &__title {
        margin: $space-md 0 0;
        font-family: $font-family-display;
        font-weight: 500;
        font-size: 64px;
        line-height: 1.05;
        color: $white;

        @include media-down(md) {
            font-size: 40px;
        }
    }

    &__toggle {
        position: absolute;
        right: $space-3xl;
        bottom: 0;
        display: inline-flex;
        gap: 8px;

        @include media-down(md) {
            position: static;
            margin-top: $space-sm;
            right: auto;
        }
    }

    &__toggle-btn {
        width: 40px;
        height: 40px;
        border-radius: 9999px;
        border: 1px solid rgba($white, 0.3);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: $white;
        text-decoration: none;
        transition:
            background-color $transition-base,
            color $transition-base,
            border-color $transition-base;

        &:hover,
        &:focus-visible {
            border-color: $white;
            outline: none;
        }

        &--active {
            background-color: $white;
            color: $dark-1;
            border-color: $white;
        }
    }

    &__separator {
        max-width: 1360px;
        margin: $space-2xl auto 0;
        border: 0;
        height: 1px;
        background-color: rgba($white, 0.15);
    }

    &__list {
        list-style: none;
        margin: $space-2xl auto 0;
        padding: 0 $space-3xl;
        max-width: 1440px;

        @include media-down(md) {
            padding: 0 $space-sm;
        }

        &--grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: $space-2xl 50px;

            @include media-down(lg) {
                grid-template-columns: 1fr;
                gap: $space-2xl;
            }
        }

        &--list {
            display: flex;
            flex-direction: column;
            gap: 0;
        }
    }

    &__item {
        display: block;
    }

    &__empty {
        max-width: 1440px;
        margin: $space-2xl auto 0;
        padding: 0 $space-3xl;
        font-family: $font-family-body;
        font-size: 16px;
        color: $white-70;

        @include media-down(md) {
            padding: 0 $space-sm;
        }
    }
}
```

- [ ] **Step 3: `_eventos-single.scss`**

```scss
// ==========================================================================
// SINGLE EVENT — `single-agenda.php`
// Light theme. Layout 2-col sidebar + content + share + related.
// ==========================================================================

.udp-single-event {
    background-color: $white;
    color: $dark-1;
    padding-bottom: $space-3xl;
    position: relative;

    &__header {
        max-width: 1440px;
        margin-inline: auto;
        padding: $space-2xl $space-3xl 0;

        @include media-down(md) {
            padding: $space-xl $space-sm 0;
        }
    }

    &__back {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-family: $font-family-body;
        font-size: 14px;
        font-weight: 500;
        color: $dark-1;
        text-decoration: none;
        margin-bottom: $space-lg;

        &:hover, &:focus-visible {
            text-decoration: underline;
            color: $dark-1;
        }
    }

    &__title {
        margin: 0;
        font-family: $font-family-display;
        font-weight: 500;
        font-size: 48px;
        line-height: 1.1;
        color: $dark-1;

        @include media-down(md) {
            font-size: 32px;
        }
    }

    &__separator {
        max-width: 1360px;
        margin: $space-2xl auto 0;
        border: 0;
        height: 1px;
        background-color: rgba($dark-1, 0.15);
    }

    &__body {
        max-width: 1440px;
        margin: $space-2xl auto 0;
        padding: 0 $space-3xl;
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 60px;

        @include media-down(lg) {
            grid-template-columns: 1fr;
            padding: 0 $space-sm;
            gap: $space-2xl;
        }
    }

    &__sidebar {
        // event-meta inside
    }

    &__content {
        max-width: 720px;

        @include media-down(lg) {
            max-width: none;
        }
    }

    &__featured {
        margin: 0 0 $space-md;

        img {
            width: 100%;
            height: auto;
            display: block;
        }
    }

    &__subtitulo {
        margin: 0 0 $space-md;
        font-family: $font-family-body;
        font-size: 16px;
        font-weight: 500;
        color: $dark-1;
    }

    &__entry-content {
        font-family: $font-family-body;
        font-size: 16px;
        line-height: 24px;
        color: $dark-1;

        p { margin: 0 0 $space-md; }
        p:last-child { margin-bottom: 0; }
        a { color: $brand-blue; text-decoration: underline; }
        strong { font-weight: 600; }
        ul, ol { margin: 0 0 $space-md; padding-inline-start: $space-lg; }
        h2, h3, h4 {
            margin: $space-2xl 0 $space-md;
            font-family: $font-family-body;
            font-weight: 600;
            line-height: 1.3;
        }
        h2 { font-size: 24px; }
        h3 { font-size: 20px; }
        h4 { font-size: 18px; }
    }

    // ---- Share buttons (reusa post-share, pero override del color sticky) ----
    .udp-single-post__share {
        // Hereda fixed sticky right del CSS de single-post.
        // En single-event el theme es light igual que single-post, así que funciona idéntico.
    }

    // ---- Related (cards horizontales evento) ----
    &__related {
        background-color: rgba($dark-1, 0.04);
        margin-top: $space-3xl;
        padding: $space-3xl 0;
    }

    &__related-inner {
        max-width: 1440px;
        margin-inline: auto;
        padding-inline: $space-3xl;

        @include media-down(md) {
            padding-inline: $space-sm;
        }
    }

    &__related-title {
        margin: 0 0 $space-2xl;
        font-family: $font-family-body;
        font-weight: 500;
        font-size: 24px;
        color: $dark-1;
    }

    &__related-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: $space-2xl;

        @include media-down(lg) {
            grid-template-columns: 1fr;
        }

        // Las cards de related son grid mode pero con theme light
        .udp-card-evento--grid {
            // override colores light (heredan del modifier --light pero aquí evidenciamos)
        }
    }
}

// --------------------------------------------------------------------------
// EVENT META (sidebar) — light theme
// --------------------------------------------------------------------------
.udp-event-meta {
    display: flex;
    flex-direction: column;
    gap: $space-md;

    &__row {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    &__label {
        font-family: $font-family-body;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: rgba($dark-1, 0.5);
    }

    &__value {
        font-family: $font-family-body;
        font-size: 14px;
        line-height: 1.4;
        color: $dark-1;
    }

    &__actions {
        display: flex;
        flex-direction: column;
        gap: $space-2xs;
        margin-top: $space-md;
    }

    &__btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        height: 44px;
        padding: 0 $space-md;
        font-family: $font-family-body;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        border-radius: 9999px;
        border: 1px solid transparent;
        cursor: pointer;
        transition:
            background-color $transition-base,
            color $transition-base,
            border-color $transition-base;

        &--outline {
            color: $dark-1;
            border-color: $dark-1;
            background-color: transparent;

            &:hover, &:focus-visible {
                background-color: $dark-1;
                color: $white;
                outline: none;
            }
        }

        &--primary {
            color: $white;
            background-color: $dark-1;
            border-color: $dark-1;

            &:hover, &:focus-visible {
                background-color: $brand-blue;
                border-color: $brand-blue;
                outline: none;
            }
        }
    }
}
```

- [ ] **Step 4: Imports en main.scss + build**

Edit `src/scss/main.scss`. AÑADIR después del último @import existente (al final de la sección de templates / blocks):

```scss
@import "blocks/card-evento";
@import "templates/eventos-archive";
@import "templates/eventos-single";
```

Build:

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -5
```

Expected: build OK, CSS sube ~8-10 kB.

---

## Task 8: E2E + MEMORY + commit

**Files:**
- Modify: `MEMORY.md`

- [ ] **Step 1: Validar archive grid (default)**

```bash
TS=$(date +%s)
echo "=== HTTP status ==="
curl -sI "http://localhost:8888/udp/agenda-udp/?nocache=$TS" 2>&1 | head -3
echo ""
echo "=== Markup classes ==="
curl -s "http://localhost:8888/udp/agenda-udp/?nocache=$TS" | grep -oE "udp-(eventos-archive|archive-filters|pagination|card-evento)[a-z_-]*" | sort -u | head -25
echo ""
echo "=== Card count grid (expected 6) ==="
curl -s "http://localhost:8888/udp/agenda-udp/?nocache=$TS" | grep -cE 'udp-card-evento--grid'
```

Expected: HTTP 200, classes presentes (`udp-eventos-archive`, `udp-eventos-archive--grid`, `udp-eventos-archive__toggle`, `udp-card-evento--grid`, etc.), card count = 6.

- [ ] **Step 2: Validar archive list**

```bash
TS=$(date +%s)
curl -s "http://localhost:8888/udp/agenda-udp/?view=list&nocache=$TS" | grep -oE "udp-eventos-archive--[a-z]+|udp-card-evento--[a-z]+" | sort -u
echo "(expected: udp-eventos-archive--list, udp-card-evento--list)"
echo ""
echo "Card count list (expected 12):"
curl -s "http://localhost:8888/udp/agenda-udp/?view=list&nocache=$TS" | grep -cE 'udp-card-evento--list'
```

Expected: classes incluyen `--list`. Card count = 12 (el limit en list es 12 vs 6 en grid según `page-eventos.php`).

- [ ] **Step 3: Validar single de un evento**

```bash
export MYSQL_PWD=root
SLUG=$(/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot udp -sN -e "
SELECT p.post_name FROM wp_fnku4yposts p
JOIN wp_fnku4ypostmeta pm ON p.ID=pm.post_id AND pm.meta_key='_thumbnail_id'
WHERE p.post_type='agenda' AND p.post_status='publish'
ORDER BY p.post_date DESC LIMIT 1;")
echo "SLUG=$SLUG"

TS=$(date +%s)
echo "=== HTTP ==="
curl -sI "http://localhost:8888/udp/agenda/$SLUG/?nocache=$TS" 2>&1 | head -3
echo ""
echo "=== Markup classes ==="
curl -s "http://localhost:8888/udp/agenda/$SLUG/?nocache=$TS" | grep -oE "udp-single-event[a-z_-]*|udp-event-meta[a-z_-]*" | sort -u
echo ""
echo "=== Back link + Inscríbete + ICS ==="
curl -s "http://localhost:8888/udp/agenda/$SLUG/?nocache=$TS" | grep -E "Volver a Eventos|Agregar al calendario|Te podría interesar|udp_ics" | head -5
```

Expected: HTTP 200, classes `udp-single-event*` y `udp-event-meta*` presentes, "Volver a Eventos" + "Agregar al calendario" + "Te podría interesar" en el HTML, link `udp_ics=ID`.

- [ ] **Step 4: Validar ICS endpoint**

```bash
EVENT_ID=$(/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot udp -sN -e "
SELECT p.ID FROM wp_fnku4yposts p
JOIN wp_fnku4ypostmeta pm ON p.ID=pm.post_id AND pm.meta_key='fecha' AND pm.meta_value != ''
WHERE p.post_type='agenda' AND p.post_status='publish'
ORDER BY p.post_date DESC LIMIT 1;")
echo "EVENT_ID=$EVENT_ID"

curl -sI "http://localhost:8888/udp/?udp_ics=$EVENT_ID" 2>&1 | head -5
echo ""
curl -s "http://localhost:8888/udp/?udp_ics=$EVENT_ID" | head -10
```

Expected: HTTP 200, `Content-Type: text/calendar`, body comienza `BEGIN:VCALENDAR` y contiene `BEGIN:VEVENT`, `SUMMARY`, `DTSTART`, `DTEND`.

- [ ] **Step 5: Validar filtro facultad**

```bash
FAC_ID=$(/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot udp -sN -e "
SELECT t.term_id FROM wp_fnku4yterms t
JOIN wp_fnku4yterm_taxonomy tt ON t.term_id=tt.term_id
WHERE tt.taxonomy='facultad' AND tt.count > 50
ORDER BY tt.count DESC LIMIT 1;")
echo "FAC_ID=$FAC_ID"
TS=$(date +%s)
curl -s "http://localhost:8888/udp/agenda-udp/?facultad=$FAC_ID&nocache=$TS" | grep -E "selected.*$FAC_ID|udp-card-evento--grid" | head -5
```

Expected: option con `value=$FAC_ID` selected. Cards visibles.

- [ ] **Step 6: Cleanup**

```bash
rm -f /tmp/test-udp-query-agenda.php
echo "Cleanup OK"
```

- [ ] **Step 7: Update MEMORY.md**

Append:

```markdown

### 2026-04-28 — F4c Agenda archive (grid + list) + single-evento

**Hechos**:
- `templates/page-eventos.php` asignado a página "Agenda" (ID 91, slug `agenda-udp` → URL `/agenda-udp/`). Theme dark. 2 vistas: grid (6/page con cards image-left + CTA) y list (12/page con rows table-like). View toggle preserva filtros vía `add_query_arg`.
- `single-agenda.php` enrutado para CPT `agenda`. Light theme. Layout 2-col sidebar (event-meta) + content (featured + subtitulo + the_content). Reusa `post-share.php` partial de F4b1.
- Helper `udp_query_agenda($filters)` en `inc/udp-cards.php`: WP_Query sobre post_type=agenda con `meta_key=fecha`, `orderby=meta_value`, `order=ASC`. Filtros: facultad (tax_query), year (meta_query LIKE 'YYYY%'), s, exclude.
- Helper `udp_card_data_from_agenda($post)`: añade `fecha_display` ("10 de Marzo de 2026"), `hora_display` ("12:00 hrs") y `lugar` al shape Card. Eyebrow desde primer post_tag (no category).
- Card primitive `card-evento.php` con 2 modes: grid (image-left 228×275 + body con title/eyebrow/datetime/lugar + CTA circular bottom-right) y list (grid 3-col 140/1fr/200, eyebrow + title + date, separador 1px abajo).
- Filtros eventos: facultad (taxonomía `facultad`, 84% coverage en agenda) + año (DISTINCT YEAR del meta `fecha` cacheado 1 día) + búsqueda (`udp_s` igual que F4b1). Hidden input `view` preserva la vista al filtrar.
- ICS endpoint `/?udp_ics={post_id}` registrado en `init` hook (`inc/udp-ics.php`). Genera VCALENDAR/VEVENT con DTSTART, DTEND, SUMMARY, LOCATION, URL, DESCRIPTION. Headers `text/calendar; charset=utf-8` + `Content-Disposition: attachment`. Botón "Agregar al calendario" en sidebar del single linkea a este endpoint.
- Sidebar event-meta: eyebrow + Día + Hora (rango si hay hora_termino) + Dirección + Entrada (hardcoded "Entrada liberada para todo público") + Unidad Académica (primer término facultad) + 2 CTAs (outline ICS + primary inscripciones URL). Inscripciones solo aparece si el ACF está populado.
- "Te podría interesar" en single: 3 eventos próximos por facultad primaria (orderby meta fecha ASC), fallback a más próximos globales si <3. Renderiza con card-evento mode=grid theme=dark.
- 3 SCSS nuevos: `_card-evento.scss` (grid + list + light variant), `_eventos-archive.scss` (header dark + toggle + filters dark + body grid/list), `_eventos-single.scss` (layout 2-col + sidebar + content + related + event-meta sidebar styles).

**Decisiones clave**:
- Eyebrow source = primer `post_tag`. Razón: la única taxonomía con valores tipo "Charla", "Cine" que matchea el spec visual. `tipo-contenido` (18 terms) tiene cobertura insuficiente para todos los eventos; `facultad` se usa como filtro primary en lugar.
- Order por meta `fecha` ASC (próximos primero). Eventos sin fecha caen al final natural por ordenación de meta vacío.
- `posts_per_page` en list = 12 (vs 6 en grid). Razón: list cards son más compactas, caben más por scroll natural.
- ICS endpoint inline en `init` hook en lugar de REST endpoint. Razón: zero overhead, no requiere registración de routes ni autenticación.
- "Entrada" hardcoded "Entrada liberada para todo público" — no hay campo ACF para esto. TODO añadir campo `entrada_info` en grupo agenda si el cliente quiere control editorial.

**Pendientes**:
- F4 extras: image gallery del single-post (campo ACF gallery + carousel JS).
- ACF nuevo `entrada_info` (textarea) en grupo agenda para sustituir el hardcoded.
- Color por término de taxonomía (eyebrows actualmente todos amarillos).
- `/eventos/` slug si el cliente prefiere a `/agenda-udp/`.
```

- [ ] **Step 8: Commit**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add \
  inc/udp-cards.php \
  inc/udp-ics.php \
  templates/page-eventos.php \
  template-parts/archive/eventos-filters.php \
  template-parts/blocks/parts/card-evento.php \
  single-agenda.php \
  template-parts/single/event-meta.php \
  template-parts/single/event-related.php \
  src/scss/blocks/_card-evento.scss \
  src/scss/templates/_eventos-archive.scss \
  src/scss/templates/_eventos-single.scss \
  src/scss/main.scss \
  functions.php \
  MEMORY.md
git commit -m "$(cat <<'EOF'
feat(agenda): F4c archive (grid + list) + single-evento

- templates/page-eventos.php asignado a página Agenda (ID 91). Theme dark,
  2 vistas toggleables (grid 6/page con cards image-left + CTA circular,
  list 12/page con rows table-like). Filtros: facultad + año + búsqueda.
- single-agenda.php enrutado para CPT agenda. Light theme, 2-col sidebar
  (event-meta) + content + share + related.
- inc/udp-cards.php: udp_query_agenda (orderby meta fecha ASC) + udp_card_data_from_agenda
  (fecha_display + hora_display + lugar). Eyebrow desde primer post_tag.
- card-evento.php con 2 modes (grid + list) en un partial.
- ICS endpoint /?udp_ics=ID en init hook (inc/udp-ics.php) para "Agregar
  al calendario". Genera VCALENDAR válido.
- Sidebar event-meta con día + hora + lugar + entrada + unidad + 2 CTAs
  (ICS + inscripciones).
- 3 SCSS nuevos: card-evento + eventos-archive + eventos-single.
EOF
)"
echo "---"
git status --short
git log --oneline -3
```

---

## Coverage check vs. spec

| Requisito spec | Tasks |
|---|---|
| `templates/page-eventos.php` asignable | Task 5 |
| 2 vistas (grid + list) toggleables | Task 5 (template + toggle) + Task 7 (SCSS) |
| Filtros facultad + año + búsqueda | Task 4 (partial) + Task 5 (template logic) |
| Paginación reutilizando F4b1 | Task 5 (get_template_part archive/pagination) |
| Card primitive grid + list | Task 3 + Task 7 (_card-evento.scss) |
| `single-agenda.php` light 2-col | Task 6 + Task 7 (_eventos-single.scss) |
| Sidebar event-meta | Task 6 (event-meta partial) + Task 7 (event-meta SCSS) |
| Related events | Task 6 (event-related) |
| Helper `udp_query_agenda` + `udp_card_data_from_agenda` | Task 1 |
| ICS endpoint | Task 2 |
| Share buttons reutilizando post-share | Task 6 (single-agenda usa el partial) |

**Cobertura completa.**

---

## Verification end-to-end (post-ejecución)

1. `/agenda-udp/` HTTP 200, header dark con título "Agenda" + toggle 2 botones, filtros dark, 6 cards `--grid` (image + body + CTA), pagination dark.
2. `/agenda-udp/?view=list` muestra 12 rows table-like (eyebrow + title + date), separadores horizontales. Toggle muestra "list" como activo.
3. Filtros: `?facultad=X` aplica tax_query, `?year=Y` aplica meta_query LIKE, `?udp_s=Z` aplica WP search. Combinaciones AND lógico.
4. Click card → single light layout 2-col. Sidebar con eyebrow + Día + Hora + Dirección + Entrada + Unidad Académica + 2 CTAs.
5. Click "Agregar al calendario" → descarga `evento-{slug}.ics` con VEVENT válido (importable a Google Calendar / Outlook / Apple Calendar).
6. Click "Inscríbete aquí" → abre URL externa en pestaña nueva (si el ACF inscripciones existe).
7. Sección "Te podría interesar" muestra 3 eventos próximos por facultad primaria.
8. Mobile: grid stack vertical, list igual, sidebar single-event apila encima.
