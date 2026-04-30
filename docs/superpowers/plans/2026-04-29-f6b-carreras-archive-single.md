# F6b — Carreras archive + single — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement this plan task-by-task.

**Goal:** Archive `/carreras/` (page existente ID 12) reusando mosaic de F6a + filtros legacy (facultad + udp_s) + cards con eyebrow facultad. Single carrera nuevo con sidebar 2-col (atributos + 2 buttons URL admision/facultad) + content + links repeater. Theme dark archive, light single.

**Architecture:** Page template asignable. Helper `udp_query_carreras($filters)` filtra por facultad + s, devuelve cards con eyebrow desde término facultad primario y href = `link_directo` (target=_blank) si existe o permalink. Single nuevo `single-carrera-udp.php` con partials `carrera-meta` + `carrera-links`. Reusa `card-mosaic.php` con eyebrow.

**Reference:** Sin Figma directo — diseño derivado del lenguaje F4-F5 + ACF carrera (atributos + url_admision + url_facultad + links + link_directo). Filtros del tema legacy `udp_portable/sections-page/carreras.php`.

---

## Inventario

**Crear:**
- `templates/page-carreras.php` — page template
- `template-parts/archive/carreras-filters.php` — facultad + udp_s
- `single-carrera-udp.php`
- `template-parts/single/carrera-meta.php` — sidebar (atributos + 2 buttons)
- `template-parts/single/carrera-links.php` — repeater de links extras al final
- `src/scss/templates/_carreras-archive.scss`
- `src/scss/templates/_carreras-single.scss`

**Modificar:**
- `inc/udp-cards.php` — `udp_query_carreras` + `udp_card_data_from_carrera`.
- `src/scss/main.scss` — 2 imports.

---

## Task 1: Helpers carreras

**File:** `inc/udp-cards.php`

- [x] **Step 1: Append AT END**

```php

/**
 * Convierte WP_Post (carrera-udp) a Card mosaic shape con eyebrow facultad.
 * href = link_directo si existe, sino permalink. Image opcional (placeholder).
 */
function udp_card_data_from_carrera( WP_Post $post ): array {
    $thumb_id = (int) get_post_thumbnail_id( $post->ID );
    $imagen_url = '';
    $imagen_alt = '';
    if ( $thumb_id > 0 ) {
        $imagen_url = wp_get_attachment_image_url( $thumb_id, 'medium_large' ) ?: '';
        $imagen_alt = (string) get_post_meta( $thumb_id, '_wp_attachment_image_alt', true );
    }

    $eyebrow_text = '';
    $facultades = get_the_terms( $post->ID, 'facultad' );
    if ( ! is_wp_error( $facultades ) && ! empty( $facultades ) ) {
        $eyebrow_text = $facultades[0]->name;
    }

    $link_directo = (string) get_post_meta( $post->ID, 'link_directo', true );
    $href   = $link_directo ?: get_permalink( $post );
    $target = $link_directo ? '_blank' : '';

    return array(
        'post_id'   => (int) $post->ID,
        'titulo'    => get_the_title( $post ),
        'imagen'    => array( 'url' => $imagen_url, 'alt' => $imagen_alt ),
        'has_image' => $imagen_url !== '',
        'eyebrow'   => $eyebrow_text,
        'href'      => $href,
        'target'    => $target,
    );
}

/**
 * Wrapper sobre WP_Query para archive Carreras.
 */
function udp_query_carreras( array $filters ): array {
    $facultad = (int) ( $filters['facultad'] ?? 0 );
    $s        = trim( (string) ( $filters['s'] ?? '' ) );

    $args = array(
        'post_type'      => 'carrera-udp',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'no_found_rows'  => true,
    );

    if ( $facultad > 0 ) {
        $args['tax_query'] = array(
            array( 'taxonomy' => 'facultad', 'field' => 'term_id', 'terms' => array( $facultad ) ),
        );
    }
    if ( $s !== '' ) {
        $args['s'] = $s;
    }

    $q = new WP_Query( $args );

    $cards = array();
    foreach ( $q->posts as $post ) {
        $cards[] = udp_card_data_from_carrera( $post );
    }
    return $cards;
}
```

- [x] **Step 2: Validar + smoke**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/inc/udp-cards.php
```

`/tmp/test-carreras.php`:

```php
<?php
$r = udp_query_carreras( array() );
WP_CLI::log( 'count: ' . count( $r ) );
if ( $r ) {
    WP_CLI::log( 'first: ' . $r[0]['titulo'] . ' — eyebrow: ' . $r[0]['eyebrow'] . ' — has_image: ' . ($r[0]['has_image'] ? 'yes':'NO') . ' — target: ' . $r[0]['target'] );
}
WP_CLI::success( 'OK' );
```

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval-file /tmp/test-carreras.php --path=/Applications/MAMP/htdocs/udp/cms 2>&1 | tail -5
```

Expected: 42 carreras, primera tiene eyebrow facultad y href.

---

## Task 2: Filters partial

**File:** `template-parts/archive/carreras-filters.php`

- [x] **Step 1: Crear**

```php
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
<form class="udp-archive-filters" method="get" action="<?php echo esc_url( $action_url ); ?>" role="search">

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

    <div class="udp-archive-filters__group udp-archive-filters__group--search">
        <label for="udp-filter-s" class="visually-hidden"><?php esc_html_e( 'Buscar', 'starter-theme' ); ?></label>
        <input id="udp-filter-s" type="search" name="udp_s" class="udp-archive-filters__input"
            placeholder="<?php esc_attr_e( 'Palabra clave', 'starter-theme' ); ?>"
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
    document.querySelectorAll('.udp-carreras-archive .udp-archive-filters [data-udp-autosubmit]').forEach(function (el) {
        el.addEventListener('change', function () { el.form.submit(); });
    });
})();
</script>
```

- [x] **Step 2: Validar PHP**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/archive/carreras-filters.php
```

---

## Task 3: Page template + assign

**File:** `templates/page-carreras.php`

- [x] **Step 1: Crear**

```php
<?php
/**
 * Template Name: Carreras (Archive)
 *
 * Page template asignable a "Carreras" (ID 12). Theme dark.
 * Mosaico 5-col de cards carrera con eyebrow facultad. Filtros
 * legacy: facultad dropdown + udp_s.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$facultad = isset( $_GET['udp_facultad'] ) ? (int) $_GET['udp_facultad'] : 0;
$s        = isset( $_GET['udp_s'] )        ? sanitize_text_field( wp_unslash( $_GET['udp_s'] ) ) : '';

$cards = function_exists( 'udp_query_carreras' )
    ? udp_query_carreras( array( 'facultad' => $facultad, 's' => $s ) )
    : array();

get_header();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-carreras-archive' ); ?>>

    <header class="udp-carreras-archive__header">
        <?php
        get_template_part( 'template-parts/sections/breadcrumb', null, array( 'page_id' => get_the_ID() ) );
        ?>
        <h1 class="udp-carreras-archive__title"><?php the_title(); ?></h1>

        <?php $intro = get_the_content(); ?>
        <?php if ( $intro ) : ?>
            <div class="udp-carreras-archive__intro">
                <?php echo apply_filters( 'the_content', $intro ); ?>
            </div>
        <?php endif; ?>
    </header>

    <hr class="udp-carreras-archive__separator" aria-hidden="true" />

    <?php
    get_template_part(
        'template-parts/archive/carreras-filters',
        null,
        array( 'facultad' => $facultad, 's' => $s )
    );
    ?>

    <hr class="udp-carreras-archive__separator" aria-hidden="true" />

    <?php if ( ! empty( $cards ) ) : ?>
        <ul class="udp-carreras-archive__list">
            <?php foreach ( $cards as $card ) : ?>
                <li class="udp-carreras-archive__item">
                    <?php
                    get_template_part(
                        'template-parts/blocks/parts/card-mosaic',
                        null,
                        array( 'card' => $card, 'theme' => 'dark' )
                    );
                    ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <p class="udp-carreras-archive__empty">
            <?php esc_html_e( 'No se encontraron carreras con esos filtros.', 'starter-theme' ); ?>
        </p>
    <?php endif; ?>

</article>

<?php
get_footer();
```

- [x] **Step 2: Asignar a página 12**

```bash
export MYSQL_PWD=root
SOCK=/Applications/MAMP/tmp/mysql/mysql.sock
MYSQL=/Applications/MAMP/Library/bin/mysql80/bin/mysql

EXISTING=$($MYSQL --socket=$SOCK -uroot udp -sN -e "SELECT meta_value FROM wp_fnku4ypostmeta WHERE post_id=12 AND meta_key='_wp_page_template' LIMIT 1;")
if [ -n "$EXISTING" ]; then
    $MYSQL --socket=$SOCK -uroot udp -e "UPDATE wp_fnku4ypostmeta SET meta_value='templates/page-carreras.php' WHERE post_id=12 AND meta_key='_wp_page_template';"
else
    $MYSQL --socket=$SOCK -uroot udp -e "INSERT INTO wp_fnku4ypostmeta (post_id, meta_key, meta_value) VALUES (12, '_wp_page_template', 'templates/page-carreras.php');"
fi
$MYSQL --socket=$SOCK -uroot udp -sN -e "SELECT meta_value FROM wp_fnku4ypostmeta WHERE post_id=12 AND meta_key='_wp_page_template';"
```

- [x] **Step 3: Validar PHP**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/templates/page-carreras.php
```

---

## Task 4: Single carrera + 2 partials

**Files:**
- Create: `single-carrera-udp.php`
- Create: `template-parts/single/carrera-meta.php`
- Create: `template-parts/single/carrera-links.php`

- [x] **Step 1: `single-carrera-udp.php`**

```php
<?php
/**
 * Single Carrera (CPT carrera-udp)
 *
 * Light theme. Layout 2-col: sidebar meta + content.
 * Sidebar: facultad eyebrow + atributos repeater + 2 buttons (admisión + facultad).
 * Content: featured + post_content + links repeater al final.
 * Reusa post-share.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
    the_post();
    ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-single-carrera' ); ?>>

        <header class="udp-single-carrera__header">
            <?php
            $archive_url = get_permalink( 12 );
            if ( ! $archive_url ) {
                $archive_url = home_url( '/carreras/' );
            }
            ?>
            <a class="udp-single-carrera__back" href="<?php echo esc_url( $archive_url ); ?>">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                    <path d="M9 3L5 7l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <?php esc_html_e( 'Volver a Carreras', 'starter-theme' ); ?>
            </a>
            <h1 class="udp-single-carrera__title"><?php the_title(); ?></h1>
        </header>

        <hr class="udp-single-carrera__separator" aria-hidden="true" />

        <div class="udp-single-carrera__body">

            <aside class="udp-single-carrera__sidebar">
                <?php get_template_part( 'template-parts/single/carrera-meta', null, array( 'post_id' => get_the_ID() ) ); ?>
            </aside>

            <div class="udp-single-carrera__content">
                <?php if ( has_post_thumbnail() ) : ?>
                    <figure class="udp-single-carrera__featured">
                        <?php the_post_thumbnail( 'large' ); ?>
                    </figure>
                <?php endif; ?>

                <div class="udp-single-carrera__entry-content">
                    <?php the_content(); ?>
                </div>

                <?php get_template_part( 'template-parts/single/carrera-links', null, array( 'post_id' => get_the_ID() ) ); ?>
            </div>

        </div>

        <?php get_template_part( 'template-parts/single/post-share', null, array( 'post_id' => get_the_ID() ) ); ?>

    </article>

    <?php
endwhile;

get_footer();
```

- [x] **Step 2: `template-parts/single/carrera-meta.php`**

```php
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
```

- [x] **Step 3: `template-parts/single/carrera-links.php`**

```php
<?php
/**
 * Single Carrera > Links repeater (extras al final del content).
 *
 * @var array $args ['post_id' => int]
 */
$post_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();
if ( ! $post_id ) return;

$links = function_exists( 'get_field' ) ? get_field( 'links', $post_id ) : array();
if ( ! is_array( $links ) || empty( $links ) ) return;
?>
<section class="udp-carrera-links">
    <h2 class="udp-carrera-links__title"><?php esc_html_e( 'Enlaces relacionados', 'starter-theme' ); ?></h2>
    <ul class="udp-carrera-links__list">
        <?php foreach ( $links as $row ) :
            $titulo = $row['titulo_link'] ?? '';
            $url    = $row['link']        ?? '';
            if ( ! $titulo || ! $url ) continue;
        ?>
            <li class="udp-carrera-links__item">
                <a class="udp-carrera-links__link" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">
                    <?php echo esc_html( $titulo ); ?>
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                        <path d="M3 3h6v6M9 3 3 9" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
```

- [x] **Step 4: Validar PHP**

```bash
PHP=/Applications/MAMP/bin/php/php8.4.1/bin/php
$PHP -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/single-carrera-udp.php
$PHP -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/single/carrera-meta.php
$PHP -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/single/carrera-links.php
```

---

## Task 5: SCSS

**Files:**
- Create: `src/scss/templates/_carreras-archive.scss`
- Create: `src/scss/templates/_carreras-single.scss`
- Modify: `src/scss/main.scss`

- [x] **Step 1: `_carreras-archive.scss`** (similar a facultades-archive con filters dark)

```scss
// ==========================================================================
// CARRERAS ARCHIVE — page template `page-carreras.php`
// Theme dark. Mosaico 5-col reusando card-mosaic con eyebrow.
// ==========================================================================

.udp-carreras-archive {
    background-color: $dark-1;
    color: $white;
    padding-bottom: $space-3xl;

    &__header {
        max-width: 1440px;
        margin-inline: auto;
        padding: $space-2xl $space-3xl 0;

        @include media-down(md) { padding: $space-xl $space-sm 0; }

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

    &__intro {
        margin-top: $space-md;
        font-family: $font-family-body;
        font-size: 14px;
        line-height: 20px;
        color: $white-70;
        max-width: 720px;

        p { margin: 0 0 $space-2xs; }
        p:last-child { margin-bottom: 0; }
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
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: $space-2xl 30px;

        @include media-down(xl) { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        @include media-down(md) {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            padding: 0 $space-sm;
            gap: $space-md;
        }
    }

    &__item { display: block; }

    &__empty {
        max-width: 1440px;
        margin: $space-2xl auto 0;
        padding: 0 $space-3xl;
        font-family: $font-family-body;
        font-size: 16px;
        color: $white-70;
    }

    // Filters dark theme
    .udp-archive-filters {
        max-width: 1440px;
        margin: $space-md auto 0;
        padding: 0 $space-3xl;
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 12px;
        align-items: center;

        @include media-down(lg) {
            grid-template-columns: 1fr;
            padding: 0 $space-sm;
        }

        &__group { position: relative; display: flex; align-items: center; }
        &__group--search { max-width: 380px; justify-self: end; @include media-down(lg) { max-width: none; justify-self: stretch; } }

        &__select, &__input {
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
            &:focus-visible { outline: 2px solid $brand-blue; outline-offset: 2px; }
        }

        &__select {
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12' fill='none'%3E%3Cpath d='M3 4.5l3 3 3-3' stroke='%23FFFFFF' stroke-width='1.2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            option { color: $dark-1; background-color: $white; }
        }

        &__input {
            padding-right: 48px;
            &::placeholder { color: rgba($white, 0.5); }
        }

        &__submit {
            position: absolute;
            right: 0;
            top: 0;
            height: 40px;
            width: 40px;
            background: transparent;
            border: 0;
            color: $white;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
    }
}
```

- [x] **Step 2: `_carreras-single.scss`**

```scss
// ==========================================================================
// SINGLE CARRERA — `single-carrera-udp.php`
// Light theme. Layout 2-col sidebar (atributos + buttons) + content.
// ==========================================================================

.udp-single-carrera {
    background-color: $white;
    color: $dark-1;
    padding-bottom: $space-3xl;
    position: relative;

    &__header {
        max-width: 1440px;
        margin-inline: auto;
        padding: $space-2xl $space-3xl 0;
        @include media-down(md) { padding: $space-xl $space-sm 0; }
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
        &:hover, &:focus-visible { text-decoration: underline; }
    }

    &__title {
        margin: 0;
        font-family: $font-family-display;
        font-weight: 500;
        font-size: 48px;
        line-height: 1.1;
        color: $dark-1;
        @include media-down(md) { font-size: 32px; }
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

    &__content {
        max-width: 720px;
        @include media-down(lg) { max-width: none; }
    }

    &__featured {
        margin: 0 0 $space-md;
        img { width: 100%; height: auto; display: block; }
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
        h2, h3, h4 { margin: $space-2xl 0 $space-md; font-family: $font-family-body; font-weight: 600; line-height: 1.3; }
        h2 { font-size: 24px; }
        h3 { font-size: 20px; }
        h4 { font-size: 18px; }
    }
}

// --------------------------------------------------------------------------
// CARRERA META (sidebar)
// --------------------------------------------------------------------------
.udp-carrera-meta {
    display: flex;
    flex-direction: column;
    gap: $space-md;

    &__row {
        display: flex;
        flex-direction: column;
        gap: 4px;
        padding-bottom: $space-2xs;
        border-bottom: 1px solid rgba($dark-1, 0.1);
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
        text-align: center;
        transition: background-color $transition-base, color $transition-base, border-color $transition-base;

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
    }
}

// --------------------------------------------------------------------------
// CARRERA LINKS (al final del content)
// --------------------------------------------------------------------------
.udp-carrera-links {
    margin-top: $space-2xl;

    &__title {
        margin: 0 0 $space-md;
        font-family: $font-family-body;
        font-weight: 600;
        font-size: 18px;
        color: $dark-1;
    }

    &__list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: $space-2xs;
    }

    &__item { display: block; }

    &__link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 0;
        font-family: $font-family-body;
        font-size: 14px;
        color: $dark-1;
        text-decoration: none;
        border-bottom: 1px solid rgba($dark-1, 0.1);
        transition: color $transition-base;

        &:hover, &:focus-visible {
            color: $brand-blue;
            outline: none;
        }
    }
}
```

- [x] **Step 3: Imports + build**

Edit `src/scss/main.scss`. Añadir 2 imports después de los templates existentes:

```scss
@import "templates/carreras-archive";
@import "templates/carreras-single";
```

Build:

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -3
```

---

## Task 6: E2E + MEMORY + commit

- [x] **Step 1: Verify archive**

```bash
TS=$(date +%s)
echo "=== HTTP /carreras/ ==="
curl -sIL "http://localhost:8888/udp/carreras/?nocache=$TS" 2>&1 | grep -E "^HTTP|^Location" | head -4
echo ""
echo "=== Markup classes ==="
curl -sL "http://localhost:8888/udp/carreras/?nocache=$TS" | grep -oE "udp-(carreras-archive|card-mosaic|archive-filters)[a-z_-]*" | sort -u
echo ""
echo "=== Cards count (esperado 42) ==="
curl -sL "http://localhost:8888/udp/carreras/?nocache=$TS" | grep -cE 'class="udp-carreras-archive__item"'
```

Expected: HTTP 200 (after redirect), classes presentes, 42 cards.

- [x] **Step 2: Verify filtro facultad**

```bash
export MYSQL_PWD=root
FAC_ID=$(/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot udp -sN -e "
SELECT t.term_id FROM wp_fnku4yterms t
JOIN wp_fnku4yterm_taxonomy tt ON t.term_id=tt.term_id
WHERE tt.taxonomy='facultad' AND tt.count > 5
ORDER BY tt.count DESC LIMIT 1;")
echo "FAC_ID=$FAC_ID"
TS=$(date +%s)
curl -sL "http://localhost:8888/udp/carreras/?udp_facultad=$FAC_ID&nocache=$TS" | grep -cE 'class="udp-carreras-archive__item"'
```

Expected: número < 42.

- [x] **Step 3: Verify single**

```bash
SLUG=$(/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot udp -sN -e "
SELECT post_name FROM wp_fnku4yposts WHERE post_type='carrera-udp' AND post_status='publish' LIMIT 1;")
echo "SLUG=$SLUG"
TS=$(date +%s)
echo "=== HTTP ==="
curl -sIL "http://localhost:8888/udp/carrera-udp/$SLUG/?nocache=$TS" 2>&1 | grep -E "^HTTP" | head -2
echo ""
echo "=== Markup ==="
curl -sL "http://localhost:8888/udp/carrera-udp/$SLUG/?nocache=$TS" | grep -oE "udp-single-carrera[a-z_-]*|udp-carrera-(meta|links)[a-z_-]*" | sort -u
echo ""
echo "=== Back + Atributos + Buttons ==="
curl -sL "http://localhost:8888/udp/carrera-udp/$SLUG/?nocache=$TS" | grep -E "Volver a Carreras|Información de admisión|Sitio de la facultad|Enlaces relacionados" | head -5
```

- [x] **Step 4: Cleanup + MEMORY + commit**

```bash
rm -f /tmp/test-carreras.php
```

Append a MEMORY.md:

```markdown

### 2026-04-29 — F6b Carreras archive + single

**Hechos**:
- `templates/page-carreras.php` asignado a página "Carreras" (ID 12). Theme dark, mosaico 5-col reusando `card-mosaic` con eyebrow facultad. Filtros legacy: facultad dropdown + udp_s.
- Helpers en `inc/udp-cards.php`: `udp_query_carreras` (no pagina, todos los 42 a la vez ASC por título) + `udp_card_data_from_carrera` (eyebrow facultad, href = link_directo target=_blank si existe sino permalink).
- `single-carrera-udp.php` enrutado para CPT carrera-udp. Light theme, 2-col sidebar (atributos repeater + 2 buttons url_admision/url_facultad) + content con featured + post_content + links repeater al final. Reusa post-share.
- 2 partials nuevos: `carrera-meta.php` (sidebar con atributos como definition list + buttons) y `carrera-links.php` (repeater de links extras al final).
- 2 SCSS nuevos: `_carreras-archive.scss` (dark con filters dark inline) y `_carreras-single.scss` (light + sidebar 2-col + buttons pill outline/primary + links list).

**Decisiones clave**:
- `link_directo` con `target=_blank` mantiene comportamiento legacy: muchas carreras linkean a sitios externos del programa, no a una página dentro del CMS.
- No pagination — 42 carreras caben en un scroll.
- Atributos repeater (titulo + valor) se renderiza como definition list en sidebar — patrón consistente con event-meta.

**Pendientes**:
- F6c: Centros archive + single simple.
- Algunas carreras pueden no tener link_directo — esas linkean a su single (donde aterrizan en single-carrera-udp.php).
```

Commit:

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add \
  inc/udp-cards.php \
  templates/page-carreras.php \
  template-parts/archive/carreras-filters.php \
  single-carrera-udp.php \
  template-parts/single/carrera-meta.php \
  template-parts/single/carrera-links.php \
  src/scss/templates/_carreras-archive.scss \
  src/scss/templates/_carreras-single.scss \
  src/scss/main.scss \
  MEMORY.md
git commit -m "$(cat <<'EOF'
feat(carreras): F6b archive + single

- templates/page-carreras.php asignado a página Carreras (ID 12).
  Theme dark, mosaico 5-col reusando card-mosaic con eyebrow facultad.
  Filtros legacy: facultad + udp_s.
- single-carrera-udp.php: light 2-col sidebar (atributos repeater +
  2 buttons url_admision/url_facultad) + content (featured + body +
  links repeater al final). Reusa post-share.
- inc/udp-cards.php: udp_query_carreras + udp_card_data_from_carrera
  (href = link_directo target=_blank si existe sino permalink).
- Partials nuevos: carrera-meta + carrera-links + carreras-filters.
- 2 SCSS: _carreras-archive (dark) + _carreras-single (light + buttons
  + links).
EOF
)"
```

---

## Verification

1. `/carreras/` HTTP 200, 42 cards en mosaico 5-col, filtros visibles.
2. `?udp_facultad=X` filtra a las carreras de esa facultad.
3. Click en card → si tiene `link_directo`, abre URL externa en pestaña nueva. Sino → single carrera.
4. Single carrera light, sidebar con atributos + 2 buttons, content con featured + body + links section.
