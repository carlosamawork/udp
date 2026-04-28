# F4b1 — Noticias archive (simple) + single-post — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implementar el listado público de Noticias en `/noticias/` (page template asignable a la página existente ID 97) con filtros (categoría + año + búsqueda) + grid 2-col + paginación, y el template `single-post.php` light con hero + content + related + share floating.

**Architecture:** Page template para evitar colisión con F9 Home. Helper `udp_query_noticias()` extiende a F4a (`udp_query_cards()` queda intocado). Card primitive de F4a se reutiliza con un nuevo modifier `--horizontal` (image-left 201×275). Single-post usa light theme con sticky share buttons. Pagination y filters parciales son reutilizables por F4c.

**Tech Stack:** WordPress, ACF Pro, WP_Query, paginate_links, SCSS BEM. No JS framework — inline JS para auto-submit y copy URL.

**Reference:** Spec `docs/superpowers/specs/2026-04-28-f4b1-noticias-archive-single-design.md` (lectura previa requerida).

---

## Inventario de archivos

**Crear:**
- `wp-content/themes/starter-theme/templates/page-noticias.php`
- `wp-content/themes/starter-theme/template-parts/archive/noticias-filters.php`
- `wp-content/themes/starter-theme/template-parts/archive/pagination.php`
- `wp-content/themes/starter-theme/single-post.php`
- `wp-content/themes/starter-theme/template-parts/single/post-hero.php`
- `wp-content/themes/starter-theme/template-parts/single/post-share.php`
- `wp-content/themes/starter-theme/template-parts/single/post-related.php`
- `wp-content/themes/starter-theme/src/scss/templates/_noticias-archive.scss`
- `wp-content/themes/starter-theme/src/scss/templates/_noticias-single.scss`

**Modificar:**
- `wp-content/themes/starter-theme/inc/udp-cards.php` — añadir `udp_query_noticias()` y `udp_get_post_years()`.
- `wp-content/themes/starter-theme/template-parts/blocks/parts/card-noticia.php` — soportar arg `variant='horizontal'`.
- `wp-content/themes/starter-theme/src/scss/blocks/_card-grid.scss` — añadir modifier `.udp-card-noticia--horizontal`.
- `wp-content/themes/starter-theme/src/scss/main.scss` — `@import "templates/noticias-archive";` y `@import "templates/noticias-single";`.

**A NO tocar:** `archive.php`, `single.php` (fallback genérico), F4a entregables ya cerrados.

---

## Task 1: Helper extensions — `udp_query_noticias` + `udp_get_post_years`

**Files:**
- Modify: `wp-content/themes/starter-theme/inc/udp-cards.php`

- [ ] **Step 1: Añadir `udp_get_post_years()` al final del archivo**

Edit `inc/udp-cards.php`. Añadir AL FINAL del archivo (después de la última función existente, antes del cierre del archivo si lo hubiera):

```php

/**
 * Devuelve los años con posts publicados (DESC). Cacheado 1 día via transient.
 *
 * @return int[] Array de años (4 dígitos) ordenados DESC.
 */
function udp_get_post_years(): array {
    $cache = get_transient( 'udp_post_years' );
    if ( $cache !== false ) {
        return $cache;
    }
    global $wpdb;
    $years = $wpdb->get_col(
        "SELECT DISTINCT YEAR(post_date) FROM {$wpdb->posts}
         WHERE post_type='post' AND post_status='publish'
         ORDER BY YEAR(post_date) DESC"
    );
    $years = array_map( 'intval', (array) $years );
    set_transient( 'udp_post_years', $years, DAY_IN_SECONDS );
    return $years;
}

/**
 * Wrapper sobre WP_Query especializado en archive de Noticias.
 * Soporta filtros que `udp_query_cards()` no maneja: año + búsqueda.
 *
 * @param array $filters {
 *     @type int    $cat    term_id de category. 0 o ausente = sin filtro.
 *     @type int    $year   Año (YYYY). 0 o ausente = sin filtro.
 *     @type string $s      Texto de búsqueda. '' = sin búsqueda.
 *     @type int    $paged  Página 1-based. Default 1.
 *     @type int    $limit  Posts por página. Default 6.
 * }
 * @return array { cards, total, max_pages, paged } — mismo shape que udp_query_cards.
 */
function udp_query_noticias( array $filters ): array {
    $cat   = (int) ( $filters['cat']   ?? 0 );
    $year  = (int) ( $filters['year']  ?? 0 );
    $s     = trim( (string) ( $filters['s'] ?? '' ) );
    $paged = max( 1, (int) ( $filters['paged'] ?? 1 ) );
    $limit = max( 1, (int) ( $filters['limit'] ?? 6 ) );

    $args = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => $limit,
        'paged'          => $paged,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    if ( $cat > 0 ) {
        $args['tax_query'] = array(
            array( 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => array( $cat ) ),
        );
    }

    if ( $year > 0 ) {
        $args['date_query'] = array( array( 'year' => $year ) );
    }

    if ( $s !== '' ) {
        $args['s'] = $s;
    }

    $q = new WP_Query( $args );

    $cards = array();
    foreach ( $q->posts as $post ) {
        $card = udp_card_data_from_post( $post );
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

- [ ] **Step 3: Smoke test del helper**

Crear `/tmp/test-udp-query-noticias.php`:

```php
<?php
$result = udp_query_noticias( array( 'paged' => 1, 'limit' => 6 ) );
WP_CLI::log( 'cards: ' . count( $result['cards'] ) );
WP_CLI::log( 'total: ' . $result['total'] );
WP_CLI::log( 'max_pages: ' . $result['max_pages'] );

$years = udp_get_post_years();
WP_CLI::log( 'years count: ' . count( $years ) );
WP_CLI::log( 'first year: ' . ( $years[0] ?? 'none' ) );
WP_CLI::log( 'last year: ' . ( end( $years ) ?: 'none' ) );

WP_CLI::success( 'Helpers OK' );
```

Ejecutar:

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval-file /tmp/test-udp-query-noticias.php --path=/Applications/MAMP/htdocs/udp/cms 2>&1 | tail -7
```

Expected: cards >= 1 (los posts más recientes con featured image), total = 4064 (volumen actual), max_pages = 678, years tiene varios años, primer año >= 2025.

---

## Task 2: Card `--horizontal` modifier (PHP + SCSS)

**Files:**
- Modify: `wp-content/themes/starter-theme/template-parts/blocks/parts/card-noticia.php`
- Modify: `wp-content/themes/starter-theme/src/scss/blocks/_card-grid.scss`

- [ ] **Step 1: Soportar arg `variant` en el partial**

Edit `template-parts/blocks/parts/card-noticia.php`. Localizar la línea:

```php
$class = 'udp-card-noticia udp-card-noticia--' . $theme;
```

Y reemplazarla por:

```php
$variant = isset( $args['variant'] ) && in_array( $args['variant'], array( 'horizontal' ), true ) ? $args['variant'] : '';
$class = 'udp-card-noticia udp-card-noticia--' . $theme . ( $variant ? ' udp-card-noticia--' . $variant : '' );
```

Justo después de la línea `$rel = $target === '_blank' ? 'noopener noreferrer' : '';` (que ya existe).

- [ ] **Step 2: Documentar el nuevo arg en el docblock del partial**

Edit el mismo archivo. Localizar el docblock superior (líneas 2-10) y añadir al @var:

```php
 * @var array $args ['card' => array, 'theme' => string, 'variant' => string]
 *                  variant: '' (default) | 'horizontal' (image-left 201×275 para archive)
```

- [ ] **Step 3: Añadir modifier SCSS**

Edit `src/scss/blocks/_card-grid.scss`. AL FINAL del archivo (después de la última `}` de `.udp-card-noticia`), AÑADIR:

```scss

// --------------------------------------------------------------------------
// VARIANTE HORIZONTAL — image-left 201×275 (archive Noticias / archive Agenda)
// --------------------------------------------------------------------------
.udp-card-noticia--horizontal {
    flex-direction: row;
    gap: 30px;  // Spec Figma: 30px entre image y texto
    align-items: flex-start;

    .udp-card-noticia__media {
        flex: 0 0 201px;
        width: 201px;
        height: 275px;
        aspect-ratio: 201 / 275;
    }

    .udp-card-noticia__body {
        flex: 1;
        padding-top: 0;
        gap: $space-sm;
    }

    .udp-card-noticia__title {
        font-size: 20px;
        line-height: 1.3;
        -webkit-line-clamp: 3;
    }

    @include media-down(md) {
        flex-direction: column;
        gap: $space-md;

        .udp-card-noticia__media {
            width: 100%;
            height: auto;
            aspect-ratio: 16 / 9;
            flex: 0 0 auto;
        }
    }
}
```

- [ ] **Step 4: Validar PHP + build**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/blocks/parts/card-noticia.php
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -5
```

Expected: PHP OK, build OK, CSS sube ~0.5 kB.

---

## Task 3: Page template `templates/page-noticias.php`

**Files:**
- Create: `wp-content/themes/starter-theme/templates/page-noticias.php`

- [ ] **Step 1: Crear el page template**

Create `wp-content/themes/starter-theme/templates/page-noticias.php`:

```php
<?php
/**
 * Template Name: Noticias (Archive)
 *
 * Page template asignable a la página "Noticias" (ID 97). Renderiza
 * filtros (categoría + año + búsqueda) + grid 2-col de cards horizontales
 * + paginación. Reutiliza F4a card-noticia con variant 'horizontal'.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$cat   = isset( $_GET['cat'] ) ? (int) $_GET['cat'] : 0;
$year  = isset( $_GET['year'] ) ? (int) $_GET['year'] : 0;
$s     = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$paged = max( 1, (int) ( get_query_var( 'paged' ) ?: ( $_GET['paged'] ?? 1 ) ) );

$result = function_exists( 'udp_query_noticias' )
    ? udp_query_noticias( array(
        'cat'   => $cat,
        'year'  => $year,
        's'     => $s,
        'paged' => $paged,
        'limit' => 6,
    ) )
    : array( 'cards' => array(), 'total' => 0, 'max_pages' => 0, 'paged' => 1 );

$cards     = $result['cards'];
$max_pages = $result['max_pages'];

get_header();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-noticias-archive' ); ?>>

    <header class="udp-noticias-archive__header">
        <?php
        get_template_part(
            'template-parts/sections/breadcrumb',
            null,
            array( 'page_id' => get_the_ID() )
        );
        ?>
        <h1 class="udp-noticias-archive__title"><?php the_title(); ?></h1>
    </header>

    <hr class="udp-noticias-archive__separator" aria-hidden="true" />

    <?php
    get_template_part(
        'template-parts/archive/noticias-filters',
        null,
        array( 'cat' => $cat, 'year' => $year, 's' => $s )
    );
    ?>

    <hr class="udp-noticias-archive__separator" aria-hidden="true" />

    <?php if ( ! empty( $cards ) ) : ?>
        <ul class="udp-noticias-archive__list">
            <?php foreach ( $cards as $card ) : ?>
                <li class="udp-noticias-archive__item">
                    <?php
                    get_template_part(
                        'template-parts/blocks/parts/card-noticia',
                        null,
                        array( 'card' => $card, 'theme' => 'light', 'variant' => 'horizontal' )
                    );
                    ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <p class="udp-noticias-archive__empty">
            <?php esc_html_e( 'No se encontraron noticias con esos filtros.', 'starter-theme' ); ?>
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

- [ ] **Step 2: Validar PHP**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/templates/page-noticias.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 3: Asignar el template a la página Noticias (ID 97) vía SQL**

```bash
export MYSQL_PWD=root
/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot udp -e "
UPDATE wp_fnku4ypostmeta SET meta_value='templates/page-noticias.php'
WHERE post_id=97 AND meta_key='_wp_page_template';"
```

Verificar:

```bash
/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot udp -sN -e "
SELECT meta_value FROM wp_fnku4ypostmeta WHERE post_id=97 AND meta_key='_wp_page_template';"
```

Expected: `templates/page-noticias.php`.

---

## Task 4: Filters partial

**Files:**
- Create: `wp-content/themes/starter-theme/template-parts/archive/noticias-filters.php`

- [ ] **Step 1: Crear directorio**

```bash
mkdir -p /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/archive
```

- [ ] **Step 2: Crear el partial**

Create `wp-content/themes/starter-theme/template-parts/archive/noticias-filters.php`:

```php
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
            name="s"
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

    <?php /* Si tras submit hay paged en URL, lo conservamos NO — siempre volvemos a página 1 al filtrar */ ?>
</form>
<script>
(function () {
    document.querySelectorAll('.udp-archive-filters [data-udp-autosubmit]').forEach(function (el) {
        el.addEventListener('change', function () { el.form.submit(); });
    });
})();
</script>
```

- [ ] **Step 3: Validar PHP**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/archive/noticias-filters.php
```

Expected: `No syntax errors detected`.

---

## Task 5: Pagination partial

**Files:**
- Create: `wp-content/themes/starter-theme/template-parts/archive/pagination.php`

- [ ] **Step 1: Crear el partial**

Create `wp-content/themes/starter-theme/template-parts/archive/pagination.php`:

```php
<?php
/**
 * Archive > Paginación
 *
 * Wrapper sobre paginate_links() con markup BEM UDP. Reutilizable por
 * cualquier archive (Noticias, Agenda, Concursos).
 *
 * @package Starter_Theme
 *
 * @var array $args ['paged' => int, 'max_pages' => int]
 */
$paged     = isset( $args['paged'] )     ? (int) $args['paged']     : 1;
$max_pages = isset( $args['max_pages'] ) ? (int) $args['max_pages'] : 0;

if ( $max_pages <= 1 ) {
    return;
}

$pages = paginate_links( array(
    'total'     => $max_pages,
    'current'   => max( 1, $paged ),
    'mid_size'  => 1,
    'end_size'  => 1,
    'prev_text' => '<svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M9 3L5 7l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    'next_text' => '<svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M5 3l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    'type'      => 'array',
    'add_args'  => array_filter( array(
        'cat'  => isset( $_GET['cat'] )  ? (int) $_GET['cat']  : null,
        'year' => isset( $_GET['year'] ) ? (int) $_GET['year'] : null,
        's'    => isset( $_GET['s'] )    ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : null,
    ) ),
) );

if ( empty( $pages ) ) {
    return;
}
?>
<nav class="udp-pagination" aria-label="<?php esc_attr_e( 'Paginación', 'starter-theme' ); ?>">
    <ul class="udp-pagination__list">
        <?php foreach ( $pages as $page_html ) : ?>
            <?php
            $is_current  = strpos( $page_html, 'current' ) !== false;
            $is_prev     = strpos( $page_html, 'prev' ) !== false;
            $is_next     = strpos( $page_html, 'next' ) !== false;
            $is_dots     = strpos( $page_html, 'dots' ) !== false;
            $modifier    = '';
            if ( $is_current ) $modifier = ' udp-pagination__item--current';
            elseif ( $is_prev ) $modifier = ' udp-pagination__item--prev';
            elseif ( $is_next ) $modifier = ' udp-pagination__item--next';
            elseif ( $is_dots ) $modifier = ' udp-pagination__item--dots';
            ?>
            <li class="udp-pagination__item<?php echo esc_attr( $modifier ); ?>">
                <?php echo $page_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — paginate_links() returns sanitized HTML ?>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
```

- [ ] **Step 2: Validar PHP**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/archive/pagination.php
```

Expected: `No syntax errors detected`.

---

## Task 6: SCSS archive `_noticias-archive.scss`

**Files:**
- Create: `wp-content/themes/starter-theme/src/scss/templates/_noticias-archive.scss`
- Modify: `wp-content/themes/starter-theme/src/scss/main.scss`

- [ ] **Step 1: Crear directorio si no existe**

```bash
mkdir -p /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/templates
```

- [ ] **Step 2: Crear el SCSS**

Create `wp-content/themes/starter-theme/src/scss/templates/_noticias-archive.scss`:

```scss
// ==========================================================================
// NOTICIAS ARCHIVE — page template `page-noticias.php`
// Header light + filtros + grid 2-col + paginación.
// Card --horizontal en `_card-grid.scss`.
// ==========================================================================

.udp-noticias-archive {
    background-color: $white;
    color: $dark-1;
    padding-bottom: $space-3xl;

    &__header {
        max-width: 1440px;
        margin-inline: auto;
        padding: $space-2xl $space-3xl 0;

        @include media-down(md) {
            padding: $space-xl $space-sm 0;
        }

        // Override breadcrumb colors para light theme
        .udp-breadcrumb__item,
        .udp-breadcrumb__link,
        .udp-breadcrumb__current {
            color: $dark-1;
        }
        .udp-breadcrumb__sep {
            color: $dark-2;
        }
    }

    &__title {
        margin: $space-md 0 0;
        font-family: $font-family-display;
        font-weight: 500;
        font-size: 64px;
        line-height: 1.05;
        color: $dark-1;

        @include media-down(md) {
            font-size: 40px;
        }
    }

    &__separator {
        max-width: 1360px;
        margin: $space-2xl auto 0;
        border: 0;
        height: 1px;
        background-color: rgba($dark-1, 0.15);
    }

    &__list {
        list-style: none;
        margin: $space-2xl auto 0;
        padding: 0 $space-3xl;
        max-width: 1440px;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: $space-2xl 50px;  // gap-y 32 / gap-x 50

        @include media-down(lg) {
            grid-template-columns: 1fr;
            padding: 0 $space-sm;
            gap: $space-2xl;
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
        color: $dark-2;
    }
}

// --------------------------------------------------------------------------
// FILTERS — 3 columnas: 2 dropdowns + search
// --------------------------------------------------------------------------
.udp-archive-filters {
    max-width: 1440px;
    margin: $space-md auto 0;
    padding: 0 $space-3xl;
    display: grid;
    grid-template-columns: 320px 320px 1fr;
    gap: 12px;
    align-items: center;

    @include media-down(lg) {
        grid-template-columns: 1fr;
        padding: 0 $space-sm;
        gap: $space-2xs;
    }

    &__group {
        position: relative;
        display: flex;
        align-items: center;
    }

    &__group--search {
        max-width: 380px;
        justify-self: end;

        @include media-down(lg) {
            max-width: none;
            justify-self: stretch;
        }
    }

    &__select {
        appearance: none;
        -webkit-appearance: none;
        width: 100%;
        height: 40px;
        padding: 0 36px 0 16px;
        font-family: $font-family-body;
        font-size: 14px;
        color: $dark-1;
        background-color: transparent;
        border: 1px solid rgba($dark-1, 0.2);
        border-radius: 0;
        cursor: pointer;

        // Custom chevron via SVG bg
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12' fill='none'%3E%3Cpath d='M3 4.5l3 3 3-3' stroke='%231C1C1C' stroke-width='1.2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 14px center;

        &:focus-visible {
            outline: 2px solid $brand-blue;
            outline-offset: 2px;
        }
    }

    &__input {
        flex: 1;
        height: 40px;
        padding: 0 48px 0 16px;
        font-family: $font-family-body;
        font-size: 14px;
        color: $dark-1;
        background-color: transparent;
        border: 1px solid rgba($dark-1, 0.2);
        border-radius: 0;

        &:focus-visible {
            outline: 2px solid $brand-blue;
            outline-offset: 2px;
        }

        &::placeholder {
            color: rgba($dark-1, 0.5);
        }
    }

    &__submit {
        position: absolute;
        right: 0;
        top: 0;
        height: 40px;
        width: 40px;
        background: transparent;
        border: 0;
        color: $dark-1;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;

        &:focus-visible {
            outline: 2px solid $brand-blue;
            outline-offset: -2px;
        }
    }
}

// --------------------------------------------------------------------------
// PAGINATION
// --------------------------------------------------------------------------
.udp-pagination {
    max-width: 1440px;
    margin: $space-3xl auto 0;
    padding: 0 $space-3xl;
    display: flex;
    justify-content: flex-end;

    @include media-down(md) {
        padding: 0 $space-sm;
        justify-content: center;
    }

    &__list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: inline-flex;
        gap: 4px;
        align-items: center;
    }

    &__item {
        a, span.current {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            padding: 0 8px;
            font-family: $font-family-body;
            font-size: 14px;
            font-weight: 500;
            color: $dark-1;
            text-decoration: none;
            border: 1px solid transparent;
        }

        &--current span.current {
            background-color: $dark-1;
            color: $white;
        }

        a:hover,
        a:focus-visible {
            border-color: rgba($dark-1, 0.3);
            outline: none;
        }

        &--prev a,
        &--next a {
            color: $dark-1;
        }

        &--dots {
            color: rgba($dark-1, 0.5);
            padding: 0 4px;
        }
    }
}
```

- [ ] **Step 3: Importar en main.scss**

Edit `src/scss/main.scss`. Localizar la sección de imports (después de `@import "blocks/...";` lines) y AÑADIR ANTES de los component imports una sección nueva o usar la existente:

```scss
// --------------------------------------------------------------------------
// 8. Templates (page templates con estilos propios)
// --------------------------------------------------------------------------
@import "templates/noticias-archive";
```

Si ya existe un comment de "Templates" sección, añadir el import allí. Si no, crear la sección al final, antes de cualquier override.

- [ ] **Step 4: Build**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -5
```

Expected: build OK, CSS sube ~3-5 kB.

---

## Task 7: Single-post template + partials

**Files:**
- Create: `wp-content/themes/starter-theme/single-post.php`
- Create: `wp-content/themes/starter-theme/template-parts/single/post-hero.php`
- Create: `wp-content/themes/starter-theme/template-parts/single/post-share.php`
- Create: `wp-content/themes/starter-theme/template-parts/single/post-related.php`

- [ ] **Step 1: Crear directorio**

```bash
mkdir -p /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/single
```

- [ ] **Step 2: Crear `single-post.php`**

Create `wp-content/themes/starter-theme/single-post.php`:

```php
<?php
/**
 * Single Post (Noticia)
 *
 * Hero light con back link + título + meta + featured image.
 * Body con post_content. Share floating sticky derecha. Related
 * posts (3 cards) al final.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
    the_post();
    ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-single-post' ); ?>>

        <?php
        get_template_part( 'template-parts/single/post-hero', null, array( 'post_id' => get_the_ID() ) );
        get_template_part( 'template-parts/single/post-share', null, array( 'post_id' => get_the_ID() ) );
        ?>

        <div class="udp-single-post__body">
            <div class="udp-single-post__content">
                <?php the_content(); ?>
            </div>
        </div>

        <?php
        get_template_part( 'template-parts/single/post-related', null, array( 'post_id' => get_the_ID() ) );
        ?>

    </article>

    <?php
endwhile;

get_footer();
```

- [ ] **Step 3: Crear `template-parts/single/post-hero.php`**

```php
<?php
/**
 * Single Post > Hero
 *
 * Back link + título + meta (fecha + eyebrow categoría) + featured image.
 *
 * @package Starter_Theme
 *
 * @var array $args ['post_id' => int]
 */
$post_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();
if ( ! $post_id ) {
    return;
}

$archive_url = get_permalink( get_page_by_path( 'noticias' ) );
if ( ! $archive_url ) {
    $archive_url = home_url( '/noticias/' );
}

$fecha_iso     = get_the_date( 'Y-m-d', $post_id );
$fecha_display = function_exists( 'udp_card_format_date' ) ? udp_card_format_date( $fecha_iso ) : $fecha_iso;

$terms = get_the_terms( $post_id, 'category' );
$primary_term_name = '';
if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
    $primary_term_name = $terms[0]->name;
}

$thumb_id = get_post_thumbnail_id( $post_id );
$thumb_url = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'large' ) : '';
$thumb_alt = $thumb_id ? (string) get_post_meta( $thumb_id, '_wp_attachment_image_alt', true ) : '';
?>
<header class="udp-single-post__hero">
    <div class="udp-single-post__hero-inner">

        <a class="udp-single-post__back" href="<?php echo esc_url( $archive_url ); ?>">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                <path d="M9 3L5 7l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <?php esc_html_e( 'Volver a Noticias', 'starter-theme' ); ?>
        </a>

        <h1 class="udp-single-post__title"><?php the_title(); ?></h1>

        <div class="udp-single-post__meta">
            <span class="udp-single-post__meta-label"><?php esc_html_e( 'Fecha', 'starter-theme' ); ?></span>
            <time class="udp-single-post__date" datetime="<?php echo esc_attr( $fecha_iso ); ?>"><?php echo esc_html( $fecha_display ); ?></time>
            <?php if ( $primary_term_name ) : ?>
                <span class="udp-card-noticia__eyebrow udp-card-noticia__eyebrow--yellow"><?php echo esc_html( $primary_term_name ); ?></span>
            <?php endif; ?>
        </div>

        <?php if ( $thumb_url ) : ?>
            <figure class="udp-single-post__featured">
                <img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $thumb_alt ); ?>" />
            </figure>
        <?php endif; ?>

    </div>
</header>
```

- [ ] **Step 4: Crear `template-parts/single/post-share.php`**

```php
<?php
/**
 * Single Post > Share buttons (floating)
 *
 * Sticky vertical bar con: copy URL + Facebook + X + WhatsApp + LinkedIn.
 *
 * @package Starter_Theme
 *
 * @var array $args ['post_id' => int]
 */
$post_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();
if ( ! $post_id ) {
    return;
}

$url   = get_permalink( $post_id );
$title = get_the_title( $post_id );

$facebook  = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url );
$twitter   = 'https://twitter.com/intent/tweet?url=' . rawurlencode( $url ) . '&text=' . rawurlencode( $title );
$whatsapp  = 'https://api.whatsapp.com/send?text=' . rawurlencode( $title . ' ' . $url );
$linkedin  = 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( $url );
?>
<aside class="udp-single-post__share" aria-label="<?php esc_attr_e( 'Compartir', 'starter-theme' ); ?>">
    <ul class="udp-single-post__share-list">

        <li class="udp-single-post__share-item">
            <button
                type="button"
                class="udp-single-post__share-btn"
                data-udp-copy-url
                data-url="<?php echo esc_attr( $url ); ?>"
                aria-label="<?php esc_attr_e( 'Copiar enlace', 'starter-theme' ); ?>"
            >
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                    <path d="M7.5 10.5a3 3 0 0 0 4.24 0l3-3a3 3 0 0 0-4.24-4.24l-.75.75M10.5 7.5a3 3 0 0 0-4.24 0l-3 3a3 3 0 0 0 4.24 4.24l.75-.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
            </button>
            <span class="udp-single-post__share-toast" data-udp-copy-toast hidden><?php esc_html_e( 'Copiado', 'starter-theme' ); ?></span>
        </li>

        <li class="udp-single-post__share-item">
            <a class="udp-single-post__share-btn" href="<?php echo esc_url( $facebook ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="currentColor"><path d="M10.5 9.5h2l.5-2.5h-2.5V5.5c0-.7.3-1.4 1.4-1.4h1.2V2A12.5 12.5 0 0 0 11.4 2c-2 0-3.4 1.2-3.4 3.4V7H6v2.5h2V16h2.5V9.5z"/></svg>
            </a>
        </li>

        <li class="udp-single-post__share-item">
            <a class="udp-single-post__share-btn" href="<?php echo esc_url( $twitter ); ?>" target="_blank" rel="noopener noreferrer" aria-label="X (Twitter)">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="currentColor"><path d="M13.6 2h2.5l-5.5 6.3L17 16h-5l-3.9-5.1L3.6 16H1.1l5.9-6.7L1 2h5.1l3.5 4.6L13.6 2zm-.9 12.5h1.4L5.4 3.4H3.9l8.8 11.1z"/></svg>
            </a>
        </li>

        <li class="udp-single-post__share-item">
            <a class="udp-single-post__share-btn" href="<?php echo esc_url( $whatsapp ); ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="currentColor"><path d="M9 1.5C4.9 1.5 1.5 4.9 1.5 9c0 1.4.4 2.7 1 3.9L1.5 16.5l3.7-1c1.1.6 2.4.9 3.8.9 4.1 0 7.5-3.4 7.5-7.5S13.1 1.5 9 1.5zM9 15.1c-1.2 0-2.4-.3-3.4-1l-.2-.1-2.2.6.6-2.1-.1-.2A6 6 0 1 1 9 15.1zm3.5-4.5c-.2-.1-1.1-.5-1.3-.6-.2-.1-.3-.1-.4.1-.1.2-.5.6-.6.7-.1.1-.2.2-.4.1-.2-.1-.8-.3-1.5-.9-.6-.5-1-1.1-1.1-1.3-.1-.2 0-.3.1-.4l.3-.4c.1-.1.1-.2.2-.3.1-.1 0-.2 0-.3 0-.1-.4-.9-.5-1.3-.1-.3-.3-.3-.4-.3h-.4c-.1 0-.3 0-.5.2s-.7.7-.7 1.6.7 1.9.8 2c.1.2 1.4 2.2 3.4 3 .5.2.9.3 1.2.4.5.2 1 .1 1.3.1.4-.1 1.1-.5 1.3-.9.2-.4.2-.8.1-.9-.1-.1-.2-.1-.4-.2z"/></svg>
            </a>
        </li>

        <li class="udp-single-post__share-item">
            <a class="udp-single-post__share-btn" href="<?php echo esc_url( $linkedin ); ?>" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="currentColor"><path d="M3.5 6h2.7v9H3.5V6zM4.8 2A1.6 1.6 0 1 1 4.8 5.2 1.6 1.6 0 0 1 4.8 2zM7.5 6h2.6v1.2h0a2.9 2.9 0 0 1 2.6-1.4c2.7 0 3.3 1.8 3.3 4.1V15h-2.7v-4.6c0-1.1 0-2.5-1.5-2.5s-1.8 1.2-1.8 2.4V15H7.5V6z"/></svg>
            </a>
        </li>

    </ul>
</aside>
<script>
(function () {
    document.querySelectorAll('[data-udp-copy-url]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var url = btn.getAttribute('data-url');
            var toast = btn.parentElement.querySelector('[data-udp-copy-toast]');
            var done = function () {
                if (!toast) return;
                toast.hidden = false;
                setTimeout(function () { toast.hidden = true; }, 1800);
            };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url).then(done).catch(function () {
                    window.prompt('Copia el enlace:', url);
                });
            } else {
                window.prompt('Copia el enlace:', url);
                done();
            }
        });
    });
})();
</script>
```

- [ ] **Step 5: Crear `template-parts/single/post-related.php`**

```php
<?php
/**
 * Single Post > Te podría interesar
 *
 * 3 posts relacionados por categoría primaria. Si <3, fallback a más recientes.
 *
 * @package Starter_Theme
 *
 * @var array $args ['post_id' => int]
 */
$current_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();
if ( ! $current_id ) {
    return;
}

$primary_term_id = 0;
$terms = get_the_terms( $current_id, 'category' );
if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
    $primary_term_id = (int) $terms[0]->term_id;
}

$base_args = array(
    'post_type'      => 'post',
    'posts_per_page' => 3,
    'post__not_in'   => array( $current_id ),
    'orderby'        => 'date',
    'order'          => 'DESC',
    'no_found_rows'  => true,
);

if ( $primary_term_id ) {
    $args1 = $base_args;
    $args1['tax_query'] = array(
        array( 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => array( $primary_term_id ) ),
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
    $card = function_exists( 'udp_card_data_from_post' ) ? udp_card_data_from_post( $post ) : null;
    if ( $card ) {
        $cards[] = $card;
    }
}

if ( empty( $cards ) ) {
    return;
}
?>
<section class="udp-single-post__related">
    <div class="udp-single-post__related-inner">
        <h2 class="udp-single-post__related-title"><?php esc_html_e( 'Te podría interesar', 'starter-theme' ); ?></h2>
        <ul class="udp-single-post__related-list">
            <?php foreach ( $cards as $card ) : ?>
                <li class="udp-single-post__related-item">
                    <?php
                    get_template_part(
                        'template-parts/blocks/parts/card-noticia',
                        null,
                        array( 'card' => $card, 'theme' => 'light' )
                    );
                    ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
```

- [ ] **Step 6: Validar PHP de los 4 archivos**

```bash
PHP=/Applications/MAMP/bin/php/php8.4.1/bin/php
$PHP -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/single-post.php
$PHP -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/single/post-hero.php
$PHP -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/single/post-share.php
$PHP -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/single/post-related.php
```

Expected: 4× `No syntax errors detected`.

---

## Task 8: SCSS single `_noticias-single.scss`

**Files:**
- Create: `wp-content/themes/starter-theme/src/scss/templates/_noticias-single.scss`
- Modify: `wp-content/themes/starter-theme/src/scss/main.scss`

- [ ] **Step 1: Crear el SCSS**

Create `wp-content/themes/starter-theme/src/scss/templates/_noticias-single.scss`:

```scss
// ==========================================================================
// SINGLE POST (Noticia) — single-post.php
// Light theme. Hero + body + share floating + related.
// ==========================================================================

.udp-single-post {
    background-color: $white;
    color: $dark-1;
    position: relative;
    padding-bottom: $space-3xl;

    // ----------------------------------------------------------------
    // HERO
    // ----------------------------------------------------------------
    &__hero {
        background-color: $white;
        padding-top: $space-2xl;
    }

    &__hero-inner {
        max-width: 1080px;
        margin-inline: auto;
        padding-inline: $space-3xl;

        @include media-down(md) {
            padding-inline: $space-sm;
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

        &:hover,
        &:focus-visible {
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

    &__meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: $space-sm;
        margin-top: $space-lg;
        padding-bottom: $space-md;
        border-bottom: 1px solid rgba($dark-1, 0.15);
    }

    &__meta-label {
        font-family: $font-family-body;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: rgba($dark-1, 0.5);
    }

    &__date {
        font-family: $font-family-body;
        font-size: 14px;
        color: $dark-1;
    }

    &__featured {
        margin: $space-2xl 0 0;

        img {
            width: 100%;
            height: auto;
            display: block;
        }
    }

    // ----------------------------------------------------------------
    // BODY (post_content)
    // ----------------------------------------------------------------
    &__body {
        max-width: 1080px;
        margin: $space-2xl auto 0;
        padding-inline: $space-3xl;

        @include media-down(md) {
            padding-inline: $space-sm;
        }
    }

    &__content {
        max-width: 480px;
        margin-inline: auto;
        font-family: $font-family-body;
        font-size: 16px;
        line-height: 24px;
        color: $dark-1;

        p { margin: 0 0 $space-md; }
        p:last-child { margin-bottom: 0; }

        h2, h3, h4 {
            margin: $space-2xl 0 $space-md;
            font-family: $font-family-body;
            font-weight: 600;
            line-height: 1.3;
            color: $dark-1;
        }
        h2 { font-size: 24px; }
        h3 { font-size: 20px; }
        h4 { font-size: 18px; }

        a {
            color: $brand-blue;
            text-decoration: underline;
        }

        strong { font-weight: 600; }

        ul, ol {
            margin: 0 0 $space-md;
            padding-inline-start: $space-lg;
        }

        img {
            max-width: 100%;
            height: auto;
            margin: $space-md 0;
        }
    }

    // ----------------------------------------------------------------
    // SHARE (floating sticky)
    // ----------------------------------------------------------------
    &__share {
        position: fixed;
        right: 24px;
        top: 50%;
        transform: translateY(-50%);
        z-index: 50;

        @include media-down(lg) {
            position: static;
            transform: none;
            margin: $space-2xl auto 0;
            text-align: center;
        }
    }

    &__share-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 8px;
        background-color: $white;
        border: 1px solid rgba($dark-1, 0.1);
        padding: $space-2xs;

        @include media-down(lg) {
            flex-direction: row;
            justify-content: center;
            display: inline-flex;
        }
    }

    &__share-item {
        position: relative;
    }

    &__share-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        background: transparent;
        border: 0;
        color: $dark-1;
        cursor: pointer;
        transition: color $transition-base, background-color $transition-base;

        &:hover,
        &:focus-visible {
            background-color: $dark-1;
            color: $white;
            outline: none;
        }
    }

    &__share-toast {
        position: absolute;
        right: 100%;
        top: 50%;
        transform: translateY(-50%);
        margin-right: 8px;
        padding: 4px 8px;
        font-family: $font-family-body;
        font-size: 12px;
        background-color: $dark-1;
        color: $white;
        white-space: nowrap;

        &[hidden] { display: none; }
    }

    // ----------------------------------------------------------------
    // RELATED
    // ----------------------------------------------------------------
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
    }

    &__related-item {
        display: block;
    }
}
```

- [ ] **Step 2: Importar en main.scss**

Edit `src/scss/main.scss`. AÑADIR después del `@import "templates/noticias-archive";` la línea:

```scss
@import "templates/noticias-single";
```

- [ ] **Step 3: Build**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -5
```

Expected: build OK, CSS sube otros ~3-4 kB.

---

## Task 9: Verificación E2E + MEMORY + commit

**Files:**
- Modify: `wp-content/themes/starter-theme/MEMORY.md`

- [ ] **Step 1: Validar archive sin filtros**

```bash
TS=$(date +%s)
echo "=== HTTP status ==="
curl -sI "http://localhost:8888/udp/noticias/?nocache=$TS" 2>&1 | head -3
echo ""
echo "=== Markup classes ==="
curl -s "http://localhost:8888/udp/noticias/?nocache=$TS" | grep -oE "udp-(noticias-archive|archive-filters|pagination|card-noticia)[a-z_-]*" | sort -u | head -25
echo ""
echo "=== Card count ==="
curl -s "http://localhost:8888/udp/noticias/?nocache=$TS" | grep -cE 'class="udp-noticias-archive__item"'
```

Expected:
- HTTP 200
- Aparecen `udp-noticias-archive`, `udp-noticias-archive__title`, `udp-archive-filters`, `udp-card-noticia`, `udp-card-noticia--horizontal`, `udp-card-noticia--light`, `udp-pagination`
- Card count = 6

- [ ] **Step 2: Validar archive con filtro de categoría**

Encontrar un term_id válido:

```bash
export MYSQL_PWD=root
CAT_ID=$(/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot udp -sN -e "
SELECT t.term_id FROM wp_fnku4yterms t
JOIN wp_fnku4yterm_taxonomy tt ON t.term_id=tt.term_id
WHERE tt.taxonomy='category' AND tt.count > 5
ORDER BY tt.count DESC LIMIT 1;")
echo "CAT_ID=$CAT_ID"
```

Validar:

```bash
TS=$(date +%s)
curl -s "http://localhost:8888/udp/noticias/?cat=$CAT_ID&nocache=$TS" | grep -E "selected.*$CAT_ID|udp-noticias-archive__item" | head -8
```

Expected: el `<option>` con `value="$CAT_ID"` tiene `selected="selected"`. Hay items en el grid (cantidad varía).

- [ ] **Step 3: Validar archive con búsqueda**

```bash
TS=$(date +%s)
curl -s "http://localhost:8888/udp/noticias/?s=universidad&nocache=$TS" | grep -cE 'class="udp-noticias-archive__item"'
```

Expected: número >= 1 (posts que matcheen "universidad").

- [ ] **Step 4: Validar paginación con paged=2**

```bash
TS=$(date +%s)
curl -s "http://localhost:8888/udp/noticias/?paged=2&nocache=$TS" | grep -oE "udp-pagination__item--current[^>]*>[^<]*<[^>]*>([^<]+)" | head -3
```

Expected: el current page debe ser "2".

- [ ] **Step 5: Encontrar un post con featured image y validar single**

```bash
export MYSQL_PWD=root
POST_SLUG=$(/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot udp -sN -e "
SELECT p.post_name FROM wp_fnku4yposts p
JOIN wp_fnku4ypostmeta pm ON p.ID=pm.post_id AND pm.meta_key='_thumbnail_id'
WHERE p.post_type='post' AND p.post_status='publish'
ORDER BY p.post_date DESC LIMIT 1;")
echo "POST_SLUG=$POST_SLUG"

TS=$(date +%s)
echo "=== HTTP status ==="
curl -sI "http://localhost:8888/udp/$POST_SLUG/?nocache=$TS" 2>&1 | head -3
echo ""
echo "=== Markup classes ==="
curl -s "http://localhost:8888/udp/$POST_SLUG/?nocache=$TS" | grep -oE "udp-single-post[a-z_-]*|udp-card-noticia--light" | sort -u
echo ""
echo "=== Back link + share + related ==="
curl -s "http://localhost:8888/udp/$POST_SLUG/?nocache=$TS" | grep -E "Volver a Noticias|udp-single-post__share-btn|Te podría interesar" | head -5
```

Expected:
- HTTP 200
- `udp-single-post`, `udp-single-post__hero`, `udp-single-post__title`, `udp-single-post__meta`, `udp-single-post__featured`, `udp-single-post__body`, `udp-single-post__content`, `udp-single-post__share`, `udp-single-post__related`
- Texto "Volver a Noticias", al menos 4 share-btn instances, "Te podría interesar"

- [ ] **Step 6: Limpiar archivos /tmp**

```bash
rm -f /tmp/test-udp-query-noticias.php
echo "Cleanup OK"
```

- [ ] **Step 7: Actualizar MEMORY.md**

Append a `wp-content/themes/starter-theme/MEMORY.md`:

```markdown
### 2026-04-28 — F4b1 Noticias archive (simple) + single-post

**Hechos**:
- `templates/page-noticias.php` asignado manualmente a la página "Noticias" (ID 97). Archive con filtros (categoría + año + búsqueda via $_GET) + grid 2-col × 3 filas (6 cards/page) + paginación. Light theme.
- `inc/udp-cards.php` extendido con `udp_query_noticias($filters)` (wrapper WP_Query con date_query + s) y `udp_get_post_years()` (transient 1 día). El `udp_query_cards()` de F4a queda intocado.
- Card primitive `card-noticia.php` extendido con arg `variant='horizontal'` → SCSS modifier `--horizontal` (image-left 201×275, line-clamp 3). Reutilizable por F4c.
- Partials reutilizables en `template-parts/archive/`: `noticias-filters.php` (form GET con auto-submit JS) y `pagination.php` (wrapper paginate_links con BEM UDP). El de paginación lo usará F4c también.
- `single-post.php` reemplaza el scaffold genérico para post_type=post (single.php queda como fallback). Hero light + content + share floating + related.
- Partials del single en `template-parts/single/`: `post-hero.php`, `post-share.php` (5 botones — copy URL con clipboard fallback + Facebook + X + WhatsApp + LinkedIn), `post-related.php` (3 cards por categoría primaria con fallback a más recientes si <3 matches).
- SCSS templates `_noticias-archive.scss` (header light + filtros 3-col + grid + paginación) y `_noticias-single.scss` (hero + content max-width 480px + share sticky + related grid 3-col).
- Verificación E2E: `/noticias/` HTTP 200 con 6 cards, filtros funcionando con cat/year/s, paginación correcta, single con todos los markup classes esperados.

**Decisiones clave**:
- `templates/page-noticias.php` (page template) en vez de `home.php` o `archive-post.php` porque WP routea `/noticias/` como page por defecto y porque `home.php` colisionaría con F9 (Home) cuando se setee `show_on_front=page`.
- `udp_query_cards()` de F4a queda intocado — soporta solo el shape del bloque flex. `udp_query_noticias()` es el wrapper específico para archive (con year + search). Patrón reutilizable: F4c tendrá `udp_query_agenda()` similar.
- Eyebrow en single y archive viene del primer término `category` del post (color hardcoded yellow). Igual que F4a — pendiente de implementar color por término.
- Share buttons usan `<button>` para copy URL (con clipboard API + fallback a `window.prompt`) y `<a target=_blank>` para los demás.
- Pagination preserva todos los filtros del URL (cat + year + s) vía `add_args` de paginate_links.

**Cosas que descubrí**:
- `paginate_links` con `type='array'` devuelve cada link como HTML string — para aplicar BEM modifiers UDP (`--current`, `--prev`, `--next`, `--dots`) detectamos las clases nativas WP (`current`, `prev`, `next`, `dots`) en cada string y mapeamos.
- `get_query_var('paged')` solo se popula automáticamente para queries de archive nativas. En page templates con WP_Query custom hay que leer `$_GET['paged']` también como fallback.

**Pendientes**:
- F4b2: hero band del archive con featured card (image overlay) + 2 side compactas. Bumpa `posts_per_page` de 6 → 9. Probable: campo ACF `featured_post` (post_object) en options page para curaduría editorial.
- F4c: archive Agenda (toggle grid/list) + single-evento + nuevo card primitive `card-evento.php`. Reutilizará `udp_query_noticias` pattern → `udp_query_agenda()`, y los partials `archive/pagination.php`.
- Image gallery del single (Figma muestra 3 imágenes con arrows después del body) → F4b2 cuando se añada el campo ACF gallery.
- Share toast "Copiado" usa hidden+timeout. Si se quiere transición fade, refactor minor en SCSS.
```

- [ ] **Step 8: Commit final**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add \
  inc/udp-cards.php \
  templates/page-noticias.php \
  template-parts/archive/noticias-filters.php \
  template-parts/archive/pagination.php \
  template-parts/blocks/parts/card-noticia.php \
  single-post.php \
  template-parts/single/post-hero.php \
  template-parts/single/post-share.php \
  template-parts/single/post-related.php \
  src/scss/blocks/_card-grid.scss \
  src/scss/templates/_noticias-archive.scss \
  src/scss/templates/_noticias-single.scss \
  src/scss/main.scss \
  MEMORY.md
git commit -m "$(cat <<'EOF'
feat(noticias): F4b1 archive (simple) + single-post

- templates/page-noticias.php asignado a la página Noticias (ID 97). Filtros
  (categoría + año + búsqueda) + grid 2-col × 3 filas + paginación.
- inc/udp-cards.php extendido con udp_query_noticias (wrapper WP_Query con
  date_query + s) y udp_get_post_years (transient 1 día). udp_query_cards
  de F4a intocado.
- Card primitive con nuevo arg variant='horizontal' → modifier SCSS
  --horizontal (image-left 201×275, line-clamp 3). Reutilizable por F4c.
- single-post.php reemplaza scaffold genérico. Hero light + content +
  share floating sticky (copy + FB + X + WA + LI) + related (3 cards por
  category primaria con fallback).
- Partials reutilizables: archive/noticias-filters, archive/pagination,
  single/post-hero, single/post-share, single/post-related.
- SCSS templates noticias-archive y noticias-single.
EOF
)"
```

Anotar el SHA del commit final.

---

## Coverage check vs. spec F4b1

| Requisito spec | Tasks |
|---|---|
| Page template `templates/page-noticias.php` asignable | Task 3 |
| Cabecera (breadcrumb + título "Noticias") | Task 3 (template) — reutiliza F3 breadcrumb |
| Filtros (categoría + año + búsqueda) | Task 4 (partial) + Task 1 (helper years) |
| Grid 2-col × 3 filas, 6 cards `--horizontal` light | Task 2 (modifier) + Task 3 (template) + Task 6 (SCSS) |
| Paginación con `paginate_links()` BEM UDP | Task 5 (partial) |
| `single-post.php` (replace scaffold) | Task 7 |
| Hero single (back link + título + meta + featured) | Task 7 (post-hero) |
| Body content max-width 480px | Task 7 + Task 8 |
| Related 3 cards (category primaria + fallback) | Task 7 (post-related) |
| Share floating sticky (copy + 4 social) | Task 7 (post-share) |
| Helper `udp_query_noticias($filters)` con year + s | Task 1 |
| Helper `udp_get_post_years()` cacheado | Task 1 |
| `card-noticia.php` con `variant='horizontal'` | Task 2 |

**Cobertura completa.**

---

## Verification end-to-end (post-ejecución)

1. `/noticias/` HTTP 200, 6 cards en grid 2-col, paginación abajo a la derecha.
2. Filtros: dropdown categoría auto-submit, dropdown año auto-submit, search submit con botón.
3. Filtros combinados: `?cat=12&year=2024&s=feria` aplica AND lógico.
4. Paginación a `?paged=2`: nuevos 6 posts.
5. Click en una card → single light con hero, body, share sticky, related (3 cards categoría primaria).
6. Mobile: filtros stack vertical, cards apiladas (image arriba, texto abajo), share posicionado al final del article.
7. Share copy URL: click en el primer botón → tooltip "Copiado" 1.8s. Fallback a `window.prompt` si clipboard API no disponible.
8. Single sin featured image → hero sin `<img>` (degradación graciosa). Posts sin category → eyebrow omitido en hero.
