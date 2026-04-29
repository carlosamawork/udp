# F5b — Concursos archive + single — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement this plan task-by-task.

**Goal:** Archive `/concursos-academicos/` (page ID 76) light + purple hero + 3 cards. Single `single-concurso-academico.php` light 2-col con sidebar meta + content + 2 download buttons (Formato + Descargar bases) + share.

**Architecture:** Reusa `card-noticia.php variant=horizontal theme=light` para el archive (mismo card que F4b1). ACF `cpt_concurso_meta` extendido con `archivo_formato_propuestas` (file). Single nuevo con sidebar 2-col similar a single-event.

**Reference:** Spec `docs/superpowers/specs/2026-04-29-f5b-concursos-archive-single-design.md`.

---

## Task 1: ACF — `archivo_formato_propuestas` + sync

**Files:**
- Modify: `acf-json/group_cpt_concurso_meta.json`

- [ ] **Step 1: Añadir field al JSON**

Edit `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_cpt_concurso_meta.json`. Read it first to understand structure. Then add a new field object to the `fields` array:

```json
{
    "key": "field_concurso_archivo_formato",
    "label": "Archivo Formato Propuestas",
    "name": "archivo_formato_propuestas",
    "type": "file",
    "return_format": "array",
    "library": "all",
    "min_size": "",
    "max_size": "",
    "mime_types": "",
    "instructions": "PDF/DOCX con el formato de propuesta. Opcional — si no se sube, el botón 'Formato de propuestas' no aparece en el single."
}
```

Insert it AFTER the existing `archivo_concurso` field. Validate JSON.

- [ ] **Step 2: Validar + sync**

```bash
jq empty /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_cpt_concurso_meta.json && echo "JSON válido"
```

Crear `/tmp/acf-sync-concurso-meta.php` (DB-direct UPSERT por post_name):

```php
<?php
$json_path = '/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_cpt_concurso_meta.json';
$json = json_decode( file_get_contents( $json_path ), true );

global $wpdb;
$existing_id = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT ID FROM {$wpdb->posts} WHERE post_type='acf-field-group' AND post_name=%s AND post_status='publish' LIMIT 1",
    $json['key']
) );

if ( $existing_id > 0 ) {
    $json['ID'] = $existing_id;
    WP_CLI::log( 'UPDATE existing id=' . $existing_id );
} else {
    WP_CLI::log( 'CREATE new' );
}
$result = acf_import_field_group( $json );
WP_CLI::success( 'id=' . $result['ID'] );
```

Run:

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval-file /tmp/acf-sync-concurso-meta.php --path=/Applications/MAMP/htdocs/udp/cms 2>&1 | tail -3
```

Expected: `UPDATE existing id=NNN` + `Success: id=NNN`.

---

## Task 2: Helpers `udp_query_concursos` + `udp_card_data_from_concurso`

**File:** `inc/udp-cards.php`

- [ ] **Step 1: Append AT END del archivo**

```php

/**
 * Convierte WP_Post (concurso-academico) a Card shape.
 * Eyebrow desde primer término de `facultad`. Color hardcoded yellow.
 * Devuelve null si no hay featured image (igual que noticias).
 */
function udp_card_data_from_concurso( WP_Post $post ): ?array {
    $thumb_id = (int) get_post_thumbnail_id( $post->ID );
    $imagen_url = '';
    $imagen_alt = '';
    $sizes      = array();

    if ( $thumb_id > 0 ) {
        $imagen_url = wp_get_attachment_image_url( $thumb_id, 'medium_large' ) ?: '';
        $imagen_alt = (string) get_post_meta( $thumb_id, '_wp_attachment_image_alt', true );
        $metadata   = wp_get_attachment_metadata( $thumb_id );
        $sizes      = is_array( $metadata ) && isset( $metadata['sizes'] ) ? $metadata['sizes'] : array();
    }
    if ( ! $imagen_url ) {
        return null;
    }

    $eyebrow_text = '';
    $facultades = get_the_terms( $post->ID, 'facultad' );
    if ( ! is_wp_error( $facultades ) && ! empty( $facultades ) ) {
        $eyebrow_text = $facultades[0]->name;
    }

    return array(
        'post_id'       => (int) $post->ID,
        'eyebrow'       => $eyebrow_text,
        'eyebrow_color' => 'yellow',
        'titulo'        => get_the_title( $post ),
        'imagen'        => array(
            'id'    => $thumb_id,
            'url'   => $imagen_url,
            'alt'   => $imagen_alt,
            'sizes' => $sizes,
        ),
        'fecha'         => get_the_date( 'Y-m-d', $post ),
        'href'          => get_permalink( $post ),
        'target'        => '',
    );
}

/**
 * Wrapper sobre WP_Query para archive Concursos académicos.
 */
function udp_query_concursos( array $filters ): array {
    $facultad = (int) ( $filters['facultad'] ?? 0 );
    $s        = trim( (string) ( $filters['s'] ?? '' ) );
    $paged    = max( 1, (int) ( $filters['paged'] ?? 1 ) );
    $limit    = max( 1, (int) ( $filters['limit'] ?? 6 ) );

    $args = array(
        'post_type'      => 'concurso-academico',
        'post_status'    => 'publish',
        'posts_per_page' => $limit,
        'paged'          => $paged,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    if ( $facultad > 0 ) {
        $args['tax_query'] = array(
            array( 'taxonomy' => 'facultad', 'field' => 'term_id', 'terms' => array( $facultad ) ),
        );
    }
    if ( $s !== '' ) {
        $args['s'] = $s;
    }
    if ( empty( $args['need_pagination'] ) ) {
        $args['no_found_rows'] = ! ( $filters['need_pagination'] ?? false );
    }

    $q = new WP_Query( $args );

    $cards = array();
    foreach ( $q->posts as $post ) {
        $card = udp_card_data_from_concurso( $post );
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

- [ ] **Step 2: Validar PHP + smoke test**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/inc/udp-cards.php
```

Crear `/tmp/test-udp-query-concursos.php`:

```php
<?php
$result = udp_query_concursos( array( 'paged' => 1, 'limit' => 6 ) );
WP_CLI::log( 'cards: ' . count( $result['cards'] ) );
WP_CLI::log( 'total: ' . $result['total'] );
if ( ! empty( $result['cards'] ) ) {
    $c = $result['cards'][0];
    WP_CLI::log( 'first titulo: ' . $c['titulo'] );
    WP_CLI::log( 'first eyebrow: ' . $c['eyebrow'] );
    WP_CLI::log( 'first href: ' . $c['href'] );
}
WP_CLI::success( 'Concursos OK' );
```

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval-file /tmp/test-udp-query-concursos.php --path=/Applications/MAMP/htdocs/udp/cms 2>&1 | tail -7
```

Expected: cards 0-3 (depende de cuántos tengan featured image), total = 3, datos del primero si existe.

---

## Task 3: Page template + filters

**Files:**
- Create: `templates/page-concursos.php`
- Create: `template-parts/archive/concursos-filters.php`

- [ ] **Step 1: Page template**

Create `templates/page-concursos.php`:

```php
<?php
/**
 * Template Name: Concursos académicos (Archive)
 *
 * Page template asignable a la página "Concursos Académicos" (ID 76).
 * Hero light + purple/blue header. Filters facultad + udp_s.
 * Grid 2-col cards horizontales (card-noticia variant=horizontal theme=light).
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$facultad = isset( $_GET['udp_facultad'] ) ? (int) $_GET['udp_facultad'] : 0;
$s        = isset( $_GET['udp_s'] )        ? sanitize_text_field( wp_unslash( $_GET['udp_s'] ) ) : '';
$paged    = max( 1, (int) ( get_query_var( 'paged' ) ?: ( $_GET['paged'] ?? 1 ) ) );

$result = function_exists( 'udp_query_concursos' )
    ? udp_query_concursos( array(
        'facultad'        => $facultad,
        's'               => $s,
        'paged'           => $paged,
        'limit'           => 6,
        'need_pagination' => true,
    ) )
    : array( 'cards' => array(), 'total' => 0, 'max_pages' => 0, 'paged' => 1 );

$cards     = $result['cards'];
$max_pages = $result['max_pages'];

get_header();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-concursos-archive' ); ?>>

    <header class="udp-concursos-archive__header">
        <div class="udp-concursos-archive__header-inner">
            <?php
            get_template_part(
                'template-parts/sections/breadcrumb',
                null,
                array( 'page_id' => get_the_ID() )
            );
            ?>
            <h1 class="udp-concursos-archive__title"><?php the_title(); ?></h1>
        </div>
    </header>

    <?php
    get_template_part(
        'template-parts/archive/concursos-filters',
        null,
        array( 'facultad' => $facultad, 's' => $s )
    );
    ?>

    <hr class="udp-concursos-archive__separator" aria-hidden="true" />

    <?php if ( ! empty( $cards ) ) : ?>
        <ul class="udp-concursos-archive__list">
            <?php foreach ( $cards as $card ) : ?>
                <li class="udp-concursos-archive__item">
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
        <p class="udp-concursos-archive__empty">
            <?php esc_html_e( 'No se encontraron concursos con esos filtros.', 'starter-theme' ); ?>
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

- [ ] **Step 2: Filters partial**

Create `template-parts/archive/concursos-filters.php`:

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
<form class="udp-archive-filters udp-archive-filters--light" method="get" action="<?php echo esc_url( $action_url ); ?>" role="search">

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
        <label for="udp-filter-s" class="visually-hidden"><?php esc_html_e( 'Buscar por palabra clave', 'starter-theme' ); ?></label>
        <input id="udp-filter-s" type="search" name="udp_s" class="udp-archive-filters__input"
            placeholder="<?php esc_attr_e( 'Buscar por palabra clave', 'starter-theme' ); ?>"
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
    document.querySelectorAll('.udp-concursos-archive .udp-archive-filters [data-udp-autosubmit]').forEach(function (el) {
        el.addEventListener('change', function () { el.form.submit(); });
    });
})();
</script>
```

- [ ] **Step 3: Asignar template a página 76**

```bash
export MYSQL_PWD=root
SOCK=/Applications/MAMP/tmp/mysql/mysql.sock
MYSQL=/Applications/MAMP/Library/bin/mysql80/bin/mysql

EXISTING=$($MYSQL --socket=$SOCK -uroot udp -sN -e "SELECT meta_value FROM wp_fnku4ypostmeta WHERE post_id=76 AND meta_key='_wp_page_template' LIMIT 1;")
if [ -n "$EXISTING" ]; then
    $MYSQL --socket=$SOCK -uroot udp -e "UPDATE wp_fnku4ypostmeta SET meta_value='templates/page-concursos.php' WHERE post_id=76 AND meta_key='_wp_page_template';"
else
    $MYSQL --socket=$SOCK -uroot udp -e "INSERT INTO wp_fnku4ypostmeta (post_id, meta_key, meta_value) VALUES (76, '_wp_page_template', 'templates/page-concursos.php');"
fi
$MYSQL --socket=$SOCK -uroot udp -sN -e "SELECT meta_value FROM wp_fnku4ypostmeta WHERE post_id=76 AND meta_key='_wp_page_template';"
```

- [ ] **Step 4: Validar PHP**

```bash
PHP=/Applications/MAMP/bin/php/php8.4.1/bin/php
$PHP -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/templates/page-concursos.php
$PHP -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/archive/concursos-filters.php
```

---

## Task 4: Single + 2 partials

**Files:**
- Create: `single-concurso-academico.php`
- Create: `template-parts/single/concurso-meta.php`
- Create: `template-parts/single/concurso-files.php`

- [ ] **Step 1: `single-concurso-academico.php`**

```php
<?php
/**
 * Single Concurso Académico
 *
 * Light theme. Layout 2-col sidebar meta + content con featured image,
 * caption (post_excerpt), body, y 2 buttons de descarga.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
    the_post();
    ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-single-concurso' ); ?>>

        <header class="udp-single-concurso__header">
            <?php
            $archive_url = get_permalink( 76 );
            if ( ! $archive_url ) {
                $archive_url = home_url( '/concursos-academicos/' );
            }
            ?>
            <a class="udp-single-concurso__back" href="<?php echo esc_url( $archive_url ); ?>">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                    <path d="M9 3L5 7l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <?php esc_html_e( 'Volver a Concursos académicos', 'starter-theme' ); ?>
            </a>
            <h1 class="udp-single-concurso__title"><?php the_title(); ?></h1>
        </header>

        <hr class="udp-single-concurso__separator" aria-hidden="true" />

        <div class="udp-single-concurso__body">

            <aside class="udp-single-concurso__sidebar">
                <?php get_template_part( 'template-parts/single/concurso-meta', null, array( 'post_id' => get_the_ID() ) ); ?>
            </aside>

            <div class="udp-single-concurso__content">
                <?php if ( has_post_thumbnail() ) : ?>
                    <figure class="udp-single-concurso__featured">
                        <?php the_post_thumbnail( 'large' ); ?>
                    </figure>
                <?php endif; ?>

                <?php $caption = get_the_excerpt(); ?>
                <?php if ( $caption ) : ?>
                    <p class="udp-single-concurso__caption"><?php echo esc_html( $caption ); ?></p>
                <?php endif; ?>

                <div class="udp-single-concurso__entry-content">
                    <?php the_content(); ?>
                </div>

                <?php get_template_part( 'template-parts/single/concurso-files', null, array( 'post_id' => get_the_ID() ) ); ?>
            </div>

        </div>

        <?php get_template_part( 'template-parts/single/post-share', null, array( 'post_id' => get_the_ID() ) ); ?>

    </article>

    <?php
endwhile;

get_footer();
```

- [ ] **Step 2: `template-parts/single/concurso-meta.php`**

```php
<?php
/**
 * Single Concurso > Sidebar meta (Fecha + facultad eyebrow).
 * @var array $args ['post_id' => int]
 */
$post_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();
if ( ! $post_id ) return;

$fecha_iso     = get_the_date( 'Y-m-d', $post_id );
$fecha_display = function_exists( 'udp_card_format_date' ) ? udp_card_format_date( $fecha_iso ) : $fecha_iso;

$eyebrow_text = '';
$facultades = get_the_terms( $post_id, 'facultad' );
if ( ! is_wp_error( $facultades ) && ! empty( $facultades ) ) {
    $eyebrow_text = $facultades[0]->name;
}
?>
<div class="udp-concurso-meta">
    <?php if ( $fecha_display ) : ?>
        <div class="udp-concurso-meta__row">
            <span class="udp-concurso-meta__label"><?php esc_html_e( 'Fecha', 'starter-theme' ); ?></span>
            <time class="udp-concurso-meta__value" datetime="<?php echo esc_attr( $fecha_iso ); ?>">
                <?php echo esc_html( $fecha_display ); ?>
            </time>
        </div>
    <?php endif; ?>
    <?php if ( $eyebrow_text ) : ?>
        <span class="udp-card-noticia__eyebrow udp-card-noticia__eyebrow--yellow"><?php echo esc_html( $eyebrow_text ); ?></span>
    <?php endif; ?>
</div>
```

- [ ] **Step 3: `template-parts/single/concurso-files.php`**

```php
<?php
/**
 * Single Concurso > Buttons descarga (Formato + Bases).
 * @var array $args ['post_id' => int]
 */
$post_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();
if ( ! $post_id ) return;

$archivo_concurso  = function_exists( 'get_field' ) ? get_field( 'archivo_concurso', $post_id ) : null;
$archivo_formato   = function_exists( 'get_field' ) ? get_field( 'archivo_formato_propuestas', $post_id ) : null;

$bases_url   = is_array( $archivo_concurso ) ? ( $archivo_concurso['url'] ?? '' ) : '';
$formato_url = is_array( $archivo_formato )  ? ( $archivo_formato['url']  ?? '' ) : '';

if ( empty( $bases_url ) && empty( $formato_url ) ) {
    return;
}
?>
<div class="udp-concurso-files">
    <?php if ( $formato_url ) : ?>
        <a class="udp-concurso-files__btn udp-concurso-files__btn--outline" href="<?php echo esc_url( $formato_url ); ?>" target="_blank" rel="noopener noreferrer">
            <?php esc_html_e( 'Formato de propuestas', 'starter-theme' ); ?>
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                <path d="M3 1.5h5l3 3v8H3v-11z" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/>
                <path d="M8 1.5v3h3" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/>
            </svg>
        </a>
    <?php endif; ?>
    <?php if ( $bases_url ) : ?>
        <a class="udp-concurso-files__btn udp-concurso-files__btn--primary" href="<?php echo esc_url( $bases_url ); ?>" target="_blank" rel="noopener noreferrer">
            <?php esc_html_e( 'Descargar bases', 'starter-theme' ); ?>
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                <path d="M7 2v8M3.5 6.5L7 10l3.5-3.5M2.5 12h9" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </a>
    <?php endif; ?>
</div>
```

- [ ] **Step 4: Validar PHP**

```bash
PHP=/Applications/MAMP/bin/php/php8.4.1/bin/php
$PHP -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/single-concurso-academico.php
$PHP -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/single/concurso-meta.php
$PHP -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/single/concurso-files.php
```

---

## Task 5: SCSS

**Files:**
- Create: `src/scss/templates/_concursos-archive.scss`
- Create: `src/scss/templates/_concursos-single.scss`
- Modify: `src/scss/main.scss`

- [ ] **Step 1: `_concursos-archive.scss`**

```scss
// ==========================================================================
// CONCURSOS ARCHIVE — page template `page-concursos.php`
// Light body con purple hero header.
// ==========================================================================

.udp-concursos-archive {
    background-color: $white;
    color: $dark-1;
    padding-bottom: $space-3xl;

    &__header {
        background-color: $brand-blue;
        color: $white;
        padding: $space-2xl 0 $space-3xl;
    }

    &__header-inner {
        max-width: 1440px;
        margin-inline: auto;
        padding-inline: $space-3xl;

        @include media-down(md) {
            padding-inline: $space-sm;
        }

        .udp-breadcrumb__item,
        .udp-breadcrumb__link,
        .udp-breadcrumb__current { color: $white; }
        .udp-breadcrumb__sep { color: rgba($white, 0.7); }
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

    &__separator {
        max-width: 1360px;
        margin: $space-2xl auto 0;
        border: 0;
        height: 1px;
        background-color: rgba($dark-1, 0.15);
    }

    // Filters override en light theme: borders dark, no white
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

        &__select,
        &__input {
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

            &:focus-visible {
                outline: 2px solid $brand-blue;
                outline-offset: 2px;
            }
        }

        &__select {
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12' fill='none'%3E%3Cpath d='M3 4.5l3 3 3-3' stroke='%231C1C1C' stroke-width='1.2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
        }

        &__input {
            padding-right: 48px;

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
        }
    }

    &__list {
        list-style: none;
        margin: $space-2xl auto 0;
        padding: 0 $space-3xl;
        max-width: 1440px;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: $space-2xl 50px;

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
```

- [ ] **Step 2: `_concursos-single.scss`**

```scss
// ==========================================================================
// SINGLE CONCURSO — `single-concurso-academico.php`
// Light theme. Layout 2-col sidebar + content + share + 2 download buttons.
// ==========================================================================

.udp-single-concurso {
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
        font-size: 40px;
        line-height: 1.1;
        color: $dark-1;

        @include media-down(md) { font-size: 28px; }
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
        grid-template-columns: 280px 1fr;
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

    &__caption {
        margin: 0 0 $space-md;
        font-family: $font-family-body;
        font-size: 14px;
        font-style: italic;
        color: $dark-2;
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
    }
}

// --------------------------------------------------------------------------
// CONCURSO META (sidebar)
// --------------------------------------------------------------------------
.udp-concurso-meta {
    display: flex;
    flex-direction: column;
    gap: $space-sm;

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
        color: $dark-1;
    }
}

// --------------------------------------------------------------------------
// CONCURSO FILES (2 download buttons)
// --------------------------------------------------------------------------
.udp-concurso-files {
    margin-top: $space-2xl;
    display: flex;
    flex-direction: column;
    gap: $space-2xs;
    align-items: flex-start;

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
        transition: background-color $transition-base, color $transition-base, border-color $transition-base;
        min-width: 220px;

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

- [ ] **Step 3: Imports en main.scss + build**

Edit `src/scss/main.scss`. Después del último `@import "templates/...";` (probablemente `templates/calendario-archive`), añadir:

```scss
@import "templates/concursos-archive";
@import "templates/concursos-single";
```

Build:

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -3
```

---

## Task 6: E2E + MEMORY + commit

- [ ] **Step 1: Verify archive**

```bash
TS=$(date +%s)
echo "=== HTTP /concursos-academicos/ ==="
curl -sI "http://localhost:8888/udp/concursos-academicos/?nocache=$TS" 2>&1 | head -3
echo ""
echo "=== Markup classes ==="
curl -s "http://localhost:8888/udp/concursos-academicos/?nocache=$TS" | grep -oE "udp-(concursos-archive|concurso-meta|archive-filters|card-noticia)[a-z_-]*" | sort -u
echo ""
echo "=== Cards count ==="
curl -s "http://localhost:8888/udp/concursos-academicos/?nocache=$TS" | grep -cE 'class="udp-concursos-archive__item"'
```

Expected: HTTP 200, classes presentes, cards 0-3 (depends on featured images).

- [ ] **Step 2: Verify single**

```bash
export MYSQL_PWD=root
SLUG=$(/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot udp -sN -e "
SELECT post_name FROM wp_fnku4yposts WHERE post_type='concurso-academico' AND post_status='publish' LIMIT 1;")
echo "SLUG=$SLUG"
TS=$(date +%s)
echo "=== HTTP ==="
curl -sI "http://localhost:8888/udp/concurso-academico/$SLUG/?nocache=$TS" 2>&1 | head -3
echo "=== Markup ==="
curl -s "http://localhost:8888/udp/concurso-academico/$SLUG/?nocache=$TS" | grep -oE "udp-single-concurso[a-z_-]*|udp-concurso-(meta|files)[a-z_-]*" | sort -u
echo "=== Buttons + back link ==="
curl -s "http://localhost:8888/udp/concurso-academico/$SLUG/?nocache=$TS" | grep -E "Volver a Concursos|Descargar bases|Formato de propuestas" | head -5
```

- [ ] **Step 3: Cleanup**

```bash
rm -f /tmp/test-udp-query-concursos.php /tmp/acf-sync-concurso-meta.php
```

- [ ] **Step 4: MEMORY.md**

Append:

```markdown

### 2026-04-29 — F5b Concursos académicos archive + single

**Hechos**:
- `templates/page-concursos.php` asignado a página "Concursos Académicos" (ID 76). Light theme con hero **purple/blue** (`$brand-blue` bg). 2-col grid de cards horizontales (reusa `card-noticia variant=horizontal theme=light`).
- `single-concurso-academico.php` enrutado para CPT `concurso-academico`. Layout 2-col sidebar (`concurso-meta` partial: fecha + eyebrow facultad) + content (featured image + caption desde `post_excerpt` + body + 2 buttons descarga). Reusa `post-share` partial.
- Helpers nuevos en `inc/udp-cards.php`: `udp_query_concursos` + `udp_card_data_from_concurso` (eyebrow desde primer término facultad, color yellow).
- ACF group `cpt_concurso_meta` extendido con field `archivo_formato_propuestas` (file, opcional). Field existente `archivo_concurso` se mapea al botón "Descargar bases".
- Filtros: facultad + udp_s.
- 2 SCSS nuevos: `_concursos-archive.scss` (light + purple hero) y `_concursos-single.scss` (layout 2-col + buttons pill outline/primary).

**Decisiones clave**:
- Caption single = `post_excerpt`. Si el cliente quiere control específico ("Periodo de postulación: hasta..."), añadir ACF dedicado en iteración futura.
- Buttons primary hover = `$brand-blue` (consistencia con el hero purple).
- 3 entries en DB → no necesita paginación pero el partial paginate_links se incluye igual (early-returns si max_pages <= 1).

**Pendientes**:
- F5 cerrado. Próxima fase opcional: F6 (Facultades + Carreras + Centros) o seguir según prioridad del cliente.
```

- [ ] **Step 5: Commit**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add \
  acf-json/group_cpt_concurso_meta.json \
  inc/udp-cards.php \
  templates/page-concursos.php \
  template-parts/archive/concursos-filters.php \
  single-concurso-academico.php \
  template-parts/single/concurso-meta.php \
  template-parts/single/concurso-files.php \
  src/scss/templates/_concursos-archive.scss \
  src/scss/templates/_concursos-single.scss \
  src/scss/main.scss \
  MEMORY.md
git commit -m "$(cat <<'EOF'
feat(concursos): F5b archive + single Concursos académicos

- templates/page-concursos.php asignado a página Concursos Académicos
  (ID 76). Light body + purple/blue hero header. Filters facultad +
  udp_s. 2-col grid de cards horizontales (reusa card-noticia variant=
  horizontal theme=light).
- single-concurso-academico.php: light 2-col sidebar (concurso-meta
  partial: fecha + facultad eyebrow) + content (featured + excerpt
  caption + body + 2 download buttons via concurso-files partial).
  Reusa post-share.
- inc/udp-cards.php: udp_query_concursos + udp_card_data_from_concurso
  (eyebrow desde primer término facultad).
- ACF cpt_concurso_meta extendido con archivo_formato_propuestas (file).
- 2 SCSS: light + purple hero archive, single light 2-col con buttons
  pill outline/primary.
EOF
)"
```

---

## Verification end-to-end

1. `/concursos-academicos/`: HTTP 200, hero purple, filters, hasta 3 cards horizontales light.
2. `?udp_facultad=X` filtra por facultad.
3. Single: layout 2-col, sidebar con fecha + eyebrow yellow, content con caption del excerpt + body + 2 buttons (Formato + Descargar bases).
4. Click "Descargar bases" → descarga archivo del ACF `archivo_concurso`.
5. Click "Formato de propuestas" → descarga `archivo_formato_propuestas` (si existe; oculto si vacío).
