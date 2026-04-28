# F4b2 — Noticias hero band + tema dark — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Cerrar F4b añadiendo hero band con featured + 2 side al archive Noticias, corregir tema light → dark, y subir posts_per_page de 6 a 9. Reutiliza card-noticia con nuevo variant `--featured` (image-overlay). ACF nuevo `featured_post` para curaduría editorial.

**Architecture:** Modificaciones al template page-noticias.php (lógica page 1 vs 2+), card-noticia.php (variant featured con markup overlay), helper (`exclude` arg + post_id en card shape), SCSS (theme dark override + featured modifier + hero band layout). 1 ACF group nuevo + 1 partial nuevo.

**Reference:** Spec `docs/superpowers/specs/2026-04-28-f4b2-noticias-hero-band-design.md`.

---

## Inventario de archivos

**Crear:**
- `wp-content/themes/starter-theme/acf-json/group_template_noticias.json`
- `wp-content/themes/starter-theme/template-parts/archive/noticias-hero.php`

**Modificar:**
- `wp-content/themes/starter-theme/inc/udp-cards.php` — `udp_query_noticias()` add `exclude`; `udp_card_data_from_post()` add `post_id`.
- `wp-content/themes/starter-theme/templates/page-noticias.php` — lógica page 1 vs 2+, theme=dark.
- `wp-content/themes/starter-theme/template-parts/blocks/parts/card-noticia.php` — soporta `variant='featured'`.
- `wp-content/themes/starter-theme/src/scss/blocks/_card-grid.scss` — modifier `.udp-card-noticia--featured`.
- `wp-content/themes/starter-theme/src/scss/templates/_noticias-archive.scss` — theme dark + hero band layout.

---

## Task 1: Helper extensions — `exclude` arg + `post_id` en card shape

**Files:**
- Modify: `wp-content/themes/starter-theme/inc/udp-cards.php`

- [ ] **Step 1: Añadir `post_id` al return de `udp_card_data_from_post`**

Edit `inc/udp-cards.php`. Localizar la función `udp_card_data_from_post` y AÑADIR la key `post_id` al array de retorno (justo después de `'id' => (int) $thumb_id` o como key separada al inicio del array). El return debe incluir:

```php
return array(
    'post_id'       => (int) $post->ID,  // ← NUEVO
    'eyebrow'       => $eyebrow['text'],
    'eyebrow_color' => $eyebrow['color'],
    'titulo'        => get_the_title( $post ),
    'imagen'        => array(
        'id'    => (int) $thumb_id,
        'url'   => $imagen_url,
        'alt'   => (string) get_post_meta( $thumb_id, '_wp_attachment_image_alt', true ),
        'sizes' => is_array( $metadata ) && isset( $metadata['sizes'] ) ? $metadata['sizes'] : array(),
    ),
    'fecha'         => get_the_date( 'Y-m-d', $post ),
    'href'          => get_permalink( $post ),
    'target'        => '',
);
```

Buscar el `return array(` dentro de `udp_card_data_from_post` y AÑADIR `'post_id' => (int) $post->ID,` como primera entrada del array.

- [ ] **Step 2: Añadir arg `exclude` a `udp_query_noticias`**

En la misma función `udp_query_noticias`, añadir extracción del arg al inicio:

```php
$exclude = isset( $filters['exclude'] ) && is_array( $filters['exclude'] ) ? array_map( 'intval', $filters['exclude'] ) : array();
```

(Después del `$limit = max(...)` line.)

Y antes de `$q = new WP_Query( $args );`, añadir:

```php
if ( ! empty( $exclude ) ) {
    $args['post__not_in'] = $exclude;
}
```

- [ ] **Step 3: Validar PHP**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/inc/udp-cards.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 4: Smoke test exclude**

Crear `/tmp/test-exclude.php`:

```php
<?php
// Sin exclude
$r1 = udp_query_noticias( array( 'paged' => 1, 'limit' => 3 ) );
WP_CLI::log( 'sin exclude — first post_id: ' . ( $r1['cards'][0]['post_id'] ?? 'none' ) );

// Con exclude del primero
$first_id = $r1['cards'][0]['post_id'] ?? 0;
$r2 = udp_query_noticias( array( 'paged' => 1, 'limit' => 3, 'exclude' => array( $first_id ) ) );
WP_CLI::log( 'con exclude — first post_id: ' . ( $r2['cards'][0]['post_id'] ?? 'none' ) );

if ( $first_id && $first_id !== ( $r2['cards'][0]['post_id'] ?? 0 ) ) {
    WP_CLI::success( 'Exclude OK — primer post diferente' );
} else {
    WP_CLI::error( 'Exclude FAILED — el primer post no fue excluido' );
}
```

Ejecutar:

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval-file /tmp/test-exclude.php --path=/Applications/MAMP/htdocs/udp/cms 2>&1 | tail -5
```

Expected: el primer post_id de r1 != primer post_id de r2; "Exclude OK".

---

## Task 2: ACF JSON `group_template_noticias` + sync

**Files:**
- Create: `wp-content/themes/starter-theme/acf-json/group_template_noticias.json`

- [ ] **Step 1: Escribir el JSON**

Create `wp-content/themes/starter-theme/acf-json/group_template_noticias.json`:

```json
{
    "key": "group_template_noticias",
    "title": "Template — Noticias",
    "fields": [
        {
            "key": "field_template_noticias_featured_post",
            "label": "Post destacado",
            "name": "featured_post",
            "type": "post_object",
            "post_type": ["post"],
            "return_format": "id",
            "allow_null": 1,
            "multiple": 0,
            "ui": 1,
            "instructions": "Post que aparece en la zona hero de la página 1. Si dejas vacío, se muestra automáticamente la noticia más reciente con imagen destacada."
        }
    ],
    "location": [
        [{ "param": "page_template", "operator": "==", "value": "templates/page-noticias.php" }]
    ],
    "menu_order": 0,
    "position": "normal",
    "style": "default",
    "label_placement": "top",
    "instruction_placement": "label",
    "active": true,
    "description": "ACF para la página Noticias (page-noticias.php). Selecciona el post destacado del hero band."
}
```

- [ ] **Step 2: Validar y sync**

```bash
jq empty /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_template_noticias.json && echo "JSON válido"
```

Crear `/tmp/acf-sync-noticias-template.php`:

```php
<?php
$json_path = '/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_template_noticias.json';
$json = json_decode( file_get_contents( $json_path ), true );

// Buscar duplicado por post_name (acf_get_field_group con local JSON devuelve ID:0 — no usable)
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

Ejecutar:

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval-file /tmp/acf-sync-noticias-template.php --path=/Applications/MAMP/htdocs/udp/cms 2>&1 | tail -3
```

Expected: `CREATE new` + `Success: id=NNNNN`.

- [ ] **Step 3: Verificar 1 sola row en DB**

```bash
export MYSQL_PWD=root
/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot udp -sN -e "
SELECT COUNT(*) FROM wp_fnku4yposts
WHERE post_type='acf-field-group' AND post_name='group_template_noticias' AND post_status='publish';"
```

Expected: `1`. Si devuelve `2` (duplicado), borrar el más viejo:

```bash
/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot udp -sN -e "
SELECT ID, post_date FROM wp_fnku4yposts WHERE post_type='acf-field-group' AND post_name='group_template_noticias' ORDER BY ID ASC;"
# Si hay 2: borrar el de ID menor (más viejo)
# /Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=$SOCK -uroot udp -e "DELETE FROM wp_fnku4yposts WHERE ID=<ID_VIEJO>; DELETE FROM wp_fnku4ypostmeta WHERE post_id=<ID_VIEJO>;"
```

---

## Task 3: Card primitive `variant='featured'`

**Files:**
- Modify: `wp-content/themes/starter-theme/template-parts/blocks/parts/card-noticia.php`

- [ ] **Step 1: Soportar 'featured' en la whitelist de variant**

Edit `template-parts/blocks/parts/card-noticia.php`. Localizar la línea:

```php
$variant = isset( $args['variant'] ) && in_array( $args['variant'], array( 'horizontal' ), true ) ? $args['variant'] : '';
```

Reemplazar por:

```php
$variant = isset( $args['variant'] ) && in_array( $args['variant'], array( 'horizontal', 'featured' ), true ) ? $args['variant'] : '';
```

- [ ] **Step 2: Añadir bloque condicional para markup featured**

En el mismo archivo, ANTES del `<a href=...>` actual, AÑADIR:

```php
<?php if ( $variant === 'featured' ) : ?>
<a
    href="<?php echo esc_url( $href ); ?>"
    class="<?php echo esc_attr( $class ); ?>"
    <?php if ( $target ) : ?>target="<?php echo esc_attr( $target ); ?>"<?php endif; ?>
    <?php if ( $rel ) : ?>rel="<?php echo esc_attr( $rel ); ?>"<?php endif; ?>
>
    <figure class="udp-card-noticia__media udp-card-noticia__media--featured">
        <img
            src="<?php echo esc_url( $imagen['url'] ); ?>"
            alt="<?php echo esc_attr( $imagen['alt'] ?? '' ); ?>"
            loading="lazy"
            decoding="async"
        />
        <span class="udp-card-noticia__overlay" aria-hidden="true"></span>
    </figure>
    <div class="udp-card-noticia__featured-meta">
        <?php if ( $eyebrow ) : ?>
            <span class="udp-card-noticia__eyebrow<?php echo $eyebrow_color ? ' udp-card-noticia__eyebrow--' . esc_attr( $eyebrow_color ) : ''; ?>"><?php echo esc_html( $eyebrow ); ?></span>
        <?php else : ?>
            <span class="udp-card-noticia__eyebrow udp-card-noticia__eyebrow--yellow"><?php esc_html_e( 'Destacado', 'starter-theme' ); ?></span>
        <?php endif; ?>
        <?php if ( $fecha_iso && $fecha_display ) : ?>
            <time class="udp-card-noticia__date udp-card-noticia__date--featured" datetime="<?php echo esc_attr( $fecha_iso ); ?>"><?php echo esc_html( $fecha_display ); ?></time>
        <?php endif; ?>
    </div>
    <h3 class="udp-card-noticia__title udp-card-noticia__title--featured"><?php echo esc_html( $titulo ); ?></h3>
</a>
<?php else : ?>
```

Y al FINAL del `<a>...</a>` actual existente, AÑADIR:

```php
<?php endif; ?>
```

Esto hace que cuando `variant=featured`, se renderice el markup nuevo (image fullbleed + overlay + eyebrow top-left + date top-right + título centrado), y para los demás casos (default y horizontal) se renderice el markup existente.

- [ ] **Step 3: Validar PHP**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/blocks/parts/card-noticia.php
```

Expected: `No syntax errors detected`.

---

## Task 4: Hero band partial

**Files:**
- Create: `wp-content/themes/starter-theme/template-parts/archive/noticias-hero.php`

- [ ] **Step 1: Crear el partial**

Create `template-parts/archive/noticias-hero.php`:

```php
<?php
/**
 * Archive Noticias > Hero band (página 1)
 *
 * Featured card grande (variant=featured) + 2 side compactas
 * (variant=horizontal) apiladas a la derecha. Solo se muestra
 * en página 1 sin filtros activos.
 *
 * @package Starter_Theme
 *
 * @var array $args ['featured' => array|null, 'side' => array]
 */
$featured = isset( $args['featured'] ) && is_array( $args['featured'] ) ? $args['featured'] : null;
$side     = isset( $args['side'] )     && is_array( $args['side'] )     ? $args['side']     : array();

if ( ! $featured && empty( $side ) ) {
    return;
}
?>
<section class="udp-noticias-hero">
    <div class="udp-noticias-hero__inner">

        <?php if ( $featured ) : ?>
            <div class="udp-noticias-hero__featured">
                <?php
                get_template_part(
                    'template-parts/blocks/parts/card-noticia',
                    null,
                    array( 'card' => $featured, 'theme' => 'dark', 'variant' => 'featured' )
                );
                ?>
            </div>
        <?php endif; ?>

        <?php if ( ! empty( $side ) ) : ?>
            <div class="udp-noticias-hero__side">
                <?php foreach ( $side as $card ) : ?>
                    <div class="udp-noticias-hero__side-item">
                        <?php
                        get_template_part(
                            'template-parts/blocks/parts/card-noticia',
                            null,
                            array( 'card' => $card, 'theme' => 'dark', 'variant' => 'horizontal' )
                        );
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</section>
```

- [ ] **Step 2: Validar PHP**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/archive/noticias-hero.php
```

Expected: `No syntax errors detected`.

---

## Task 5: SCSS — featured modifier + dark theme + hero layout

**Files:**
- Modify: `wp-content/themes/starter-theme/src/scss/blocks/_card-grid.scss`
- Modify: `wp-content/themes/starter-theme/src/scss/templates/_noticias-archive.scss`

- [ ] **Step 1: Añadir `.udp-card-noticia--featured` modifier**

Edit `src/scss/blocks/_card-grid.scss`. AL FINAL del archivo (después del modifier `--horizontal`), AÑADIR:

```scss

// --------------------------------------------------------------------------
// VARIANTE FEATURED — image fullbleed + overlay + eyebrow + date + título centrado
// --------------------------------------------------------------------------
.udp-card-noticia--featured {
    position: relative;
    display: block;
    aspect-ratio: 432 / 580;
    overflow: hidden;
    color: $white;
    text-decoration: none;

    .udp-card-noticia__media--featured {
        position: absolute;
        inset: 0;
        margin: 0;
        background: $dark-2;

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.4s ease;
        }
    }

    .udp-card-noticia__overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(
            180deg,
            rgba($dark-1, 0.25) 0%,
            rgba($dark-1, 0.45) 50%,
            rgba($dark-1, 0.65) 100%
        );
    }

    .udp-card-noticia__featured-meta {
        position: absolute;
        top: $space-md;
        left: $space-md;
        right: $space-md;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        z-index: 2;
    }

    .udp-card-noticia__date--featured {
        font-family: $font-family-body;
        font-size: 14px;
        font-weight: 500;
        color: $white;
    }

    .udp-card-noticia__title--featured {
        position: absolute;
        inset: 0;
        z-index: 2;
        margin: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: $space-3xl;
        font-family: $font-family-display;
        font-weight: 500;
        font-size: 40px;
        line-height: 1.1;
        text-align: center;
        color: $white;
        text-transform: none;
        -webkit-line-clamp: unset;
        overflow: visible;
        display: flex;  // override del .udp-card-noticia__title default

        @include media-down(md) {
            font-size: 28px;
            padding: $space-lg;
        }
    }

    @media (prefers-reduced-motion: no-preference) {
        &:hover .udp-card-noticia__media--featured img,
        &:focus-visible .udp-card-noticia__media--featured img {
            transform: scale(1.04);
        }
    }
}
```

- [ ] **Step 2: Cambiar tema archive a dark + añadir hero band layout**

Edit `src/scss/templates/_noticias-archive.scss`. REEMPLAZAR todo el contenido del archivo por:

```scss
// ==========================================================================
// NOTICIAS ARCHIVE — page template `page-noticias.php`
// Header DARK + filtros dark + hero band + grid 2-col + paginación.
// Card --horizontal y --featured en `_card-grid.scss`.
// ==========================================================================

.udp-noticias-archive {
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

        // Override breadcrumb colors para dark theme
        .udp-breadcrumb__item,
        .udp-breadcrumb__link,
        .udp-breadcrumb__current {
            color: $white;
        }
        .udp-breadcrumb__sep {
            color: $white-70;
        }
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
        background-color: rgba($white, 0.15);
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
        color: $white-70;
    }
}

// --------------------------------------------------------------------------
// HERO BAND (page 1, sin filtros activos)
// --------------------------------------------------------------------------
.udp-noticias-hero {
    margin-top: $space-2xl;

    &__inner {
        max-width: 1440px;
        margin-inline: auto;
        padding: 0 $space-3xl;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;

        @include media-down(lg) {
            grid-template-columns: 1fr;
            padding: 0 $space-sm;
            gap: $space-2xl;
        }
    }

    &__featured {
        display: block;
    }

    &__side {
        display: flex;
        flex-direction: column;
        gap: 30px;
    }

    &__side-item {
        display: block;
    }
}

// --------------------------------------------------------------------------
// FILTERS — dark theme
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
        color: $white;
        background-color: transparent;
        border: 1px solid rgba($white, 0.2);
        border-radius: 0;
        cursor: pointer;

        // White chevron SVG
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12' fill='none'%3E%3Cpath d='M3 4.5l3 3 3-3' stroke='%23FFFFFF' stroke-width='1.2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 14px center;

        option {
            color: $dark-1;  // dropdown native list usa text dark
            background-color: $white;
        }

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
        color: $white;
        background-color: transparent;
        border: 1px solid rgba($white, 0.2);
        border-radius: 0;

        &:focus-visible {
            outline: 2px solid $brand-blue;
            outline-offset: 2px;
        }

        &::placeholder {
            color: rgba($white, 0.5);
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
        color: $white;
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
// PAGINATION — dark theme (invertido)
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
            color: $white;
            text-decoration: none;
            border: 1px solid transparent;
        }

        &--current span.current {
            background-color: $white;
            color: $dark-1;
        }

        a:hover,
        a:focus-visible {
            border-color: rgba($white, 0.3);
            outline: none;
        }

        &--prev a,
        &--next a {
            color: $white;
        }

        &--dots {
            color: rgba($white, 0.5);
            padding: 0 4px;
        }
    }
}
```

- [ ] **Step 3: Build**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -5
```

Expected: build OK, CSS sube ~3-4 kB (más reglas de hero + featured + dark overrides).

---

## Task 6: Page template — lógica page 1 vs 2+

**Files:**
- Modify: `wp-content/themes/starter-theme/templates/page-noticias.php`

- [ ] **Step 1: Reescribir el page template**

Edit `templates/page-noticias.php`. REEMPLAZAR el contenido completo por:

```php
<?php
/**
 * Template Name: Noticias (Archive)
 *
 * Page template asignable a la página "Noticias" (ID 97). Renderiza
 * filtros (categoría + año + búsqueda) + hero band (página 1) + grid
 * 2-col de cards horizontales + paginación. Theme dark.
 *
 * Lógica:
 *   - Página 1 sin filtros: featured + 2 side cards + 6 grid (9 total).
 *   - Página 1 con filtros: 9 cards en grid (sin hero).
 *   - Página 2+: 9 cards en grid (sin hero).
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$cat   = isset( $_GET['cat'] ) ? (int) $_GET['cat'] : 0;
$year  = isset( $_GET['year'] ) ? (int) $_GET['year'] : 0;
$s     = isset( $_GET['udp_s'] ) ? sanitize_text_field( wp_unslash( $_GET['udp_s'] ) ) : '';
$paged = max( 1, (int) ( get_query_var( 'paged' ) ?: ( $_GET['paged'] ?? 1 ) ) );

$page_id      = get_the_ID();
$is_first_pg  = ( $paged === 1 );
$has_filters  = ( $cat > 0 || $year > 0 || $s !== '' );
$show_hero    = $is_first_pg && ! $has_filters;

$featured_card = null;
$side_cards    = array();
$grid_cards    = array();
$max_pages     = 0;

if ( $show_hero && function_exists( 'udp_query_noticias' ) && function_exists( 'udp_card_data_from_post' ) ) {
    // Resolver featured: ACF o fallback al más reciente con featured image
    $featured_id = (int) get_field( 'featured_post', $page_id );
    if ( $featured_id <= 0 ) {
        $latest = get_posts( array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'meta_query'     => array(
                array( 'key' => '_thumbnail_id', 'compare' => 'EXISTS' ),
            ),
            'fields'         => 'ids',
        ) );
        $featured_id = ! empty( $latest ) ? (int) $latest[0] : 0;
    }

    if ( $featured_id > 0 ) {
        $featured_post = get_post( $featured_id );
        if ( $featured_post ) {
            $featured_card = udp_card_data_from_post( $featured_post );
        }
    }

    // Side: 2 más recientes excluyendo featured
    $exclude_for_side = $featured_id > 0 ? array( $featured_id ) : array();
    $side_result = udp_query_noticias( array(
        'paged'   => 1,
        'limit'   => 2,
        'exclude' => $exclude_for_side,
    ) );
    $side_cards = $side_result['cards'];

    // Grid: 6 más recientes excluyendo featured + side
    $exclude_for_grid = $exclude_for_side;
    foreach ( $side_cards as $card ) {
        if ( ! empty( $card['post_id'] ) ) {
            $exclude_for_grid[] = (int) $card['post_id'];
        }
    }
    $grid_result = udp_query_noticias( array(
        'paged'   => 1,
        'limit'   => 6,
        'exclude' => $exclude_for_grid,
    ) );
    $grid_cards = $grid_result['cards'];
    $max_pages  = $grid_result['max_pages'];
} else {
    // Page 2+ o con filtros: solo grid 9 sin featured/side
    $grid_result = function_exists( 'udp_query_noticias' )
        ? udp_query_noticias( array(
            'cat'   => $cat,
            'year'  => $year,
            's'     => $s,
            'paged' => $paged,
            'limit' => 9,
        ) )
        : array( 'cards' => array(), 'total' => 0, 'max_pages' => 0, 'paged' => $paged );

    $grid_cards = $grid_result['cards'];
    $max_pages  = $grid_result['max_pages'];
}

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

    <?php if ( $show_hero && ( $featured_card || ! empty( $side_cards ) ) ) : ?>
        <?php
        get_template_part(
            'template-parts/archive/noticias-hero',
            null,
            array( 'featured' => $featured_card, 'side' => $side_cards )
        );
        ?>
    <?php endif; ?>

    <?php if ( ! empty( $grid_cards ) ) : ?>
        <ul class="udp-noticias-archive__list">
            <?php foreach ( $grid_cards as $card ) : ?>
                <li class="udp-noticias-archive__item">
                    <?php
                    get_template_part(
                        'template-parts/blocks/parts/card-noticia',
                        null,
                        array( 'card' => $card, 'theme' => 'dark', 'variant' => 'horizontal' )
                    );
                    ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php elseif ( ! $featured_card && empty( $side_cards ) ) : ?>
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

---

## Task 7: E2E + MEMORY + commit

**Files:**
- Modify: `wp-content/themes/starter-theme/MEMORY.md`

- [ ] **Step 1: Validar archive page 1 sin filtros — hero band visible**

```bash
TS=$(date +%s)
echo "=== HTTP status ==="
curl -sI "http://localhost:8888/udp/noticias/?nocache=$TS" 2>&1 | head -3
echo ""
echo "=== Markup classes ==="
curl -s "http://localhost:8888/udp/noticias/?nocache=$TS" | grep -oE "udp-(noticias-archive|noticias-hero|archive-filters|pagination|card-noticia)[a-z_-]*" | sort -u
echo ""
echo "=== Featured + side + grid counts ==="
curl -s "http://localhost:8888/udp/noticias/?nocache=$TS" | grep -cE 'udp-card-noticia--featured'
echo "(featured: 1 expected)"
curl -s "http://localhost:8888/udp/noticias/?nocache=$TS" | grep -cE 'udp-noticias-hero__side-item'
echo "(side: 2 expected)"
curl -s "http://localhost:8888/udp/noticias/?nocache=$TS" | grep -cE 'class="udp-noticias-archive__item"'
echo "(grid: 6 expected)"
```

Expected:
- HTTP 200
- Aparecen `udp-noticias-hero`, `udp-noticias-hero__featured`, `udp-noticias-hero__side`, `udp-card-noticia--featured`, `udp-card-noticia--horizontal`, `udp-card-noticia--dark`
- Featured: 1, side: 2, grid: 6 (total 9 cards en página 1)

- [ ] **Step 2: Validar page 2 — sin hero band**

```bash
TS=$(date +%s)
curl -s "http://localhost:8888/udp/noticias/?paged=2&nocache=$TS" | grep -cE 'udp-noticias-hero'
echo "(0 expected — sin hero en page 2)"
curl -s "http://localhost:8888/udp/noticias/?paged=2&nocache=$TS" | grep -cE 'class="udp-noticias-archive__item"'
echo "(9 expected — solo grid)"
```

Expected: 0 hero, 9 grid items.

- [ ] **Step 3: Validar con filtro — sin hero**

```bash
export MYSQL_PWD=root
CAT_ID=$(/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot udp -sN -e "
SELECT t.term_id FROM wp_fnku4yterms t
JOIN wp_fnku4yterm_taxonomy tt ON t.term_id=tt.term_id
WHERE tt.taxonomy='category' AND tt.count > 5
ORDER BY tt.count DESC LIMIT 1;")
TS=$(date +%s)
curl -s "http://localhost:8888/udp/noticias/?cat=$CAT_ID&nocache=$TS" | grep -cE 'udp-noticias-hero'
echo "(0 expected — sin hero con filtro)"
curl -s "http://localhost:8888/udp/noticias/?cat=$CAT_ID&nocache=$TS" | grep -cE 'class="udp-noticias-archive__item"'
echo "(<= 9 expected — depende del count de la categoría)"
```

Expected: 0 hero, items <= 9.

- [ ] **Step 4: Cleanup**

```bash
rm -f /tmp/test-exclude.php /tmp/acf-sync-noticias-template.php
echo "Cleanup OK"
```

- [ ] **Step 5: Update MEMORY.md**

Append a `MEMORY.md`:

```markdown

### 2026-04-28 — F4b2 Noticias hero band + tema dark fix

**Hechos**:
- Archive Noticias corregido a tema **dark** (F4b1 lo había deployado light por error de spec). `templates/page-noticias.php` pasa `theme=dark` al card-noticia. SCSS `_noticias-archive.scss` invertido (bg `$dark-1`, color `$white`, breadcrumb override, filtros input/select con border `rgba($white,0.2)` y placeholder `rgba($white,0.5)`, paginación con current bg `$white` color `$dark-1`).
- Hero band en página 1 sin filtros: 1 featured grande (variant=`featured`) + 2 side compactas (variant=`horizontal`) en grid 2-col (`udp-noticias-hero__inner`). Mobile cae a 1-col.
- Featured card: image fullbleed + overlay gradient `rgba($dark-1, 0.25→0.65)` + eyebrow yellow top-left + date top-right + título centrado Arizona Flare 40px (28px mobile). Aspect-ratio 432/580. Hover image scale 1.04. Sin "Leer más" — toda la card es clickable.
- ACF group nuevo `group_template_noticias` con field único `featured_post` (post_object, nullable). Location `page_template == page-noticias.php`. Si vacío, fallback al post más reciente con featured image.
- Helper `udp_card_data_from_post` ahora incluye `post_id` en el shape Card (necesario para excluir IDs en queries siguientes).
- Helper `udp_query_noticias` acepta nuevo arg `exclude` (array de post IDs) → mapea a `post__not_in`.
- `posts_per_page` lógica: página 1 sin filtros = 9 (1 featured + 2 side + 6 grid); página 1 con filtros = 9 grid; página 2+ = 9 grid.

**Decisiones clave**:
- El featured se SUPRIME cuando hay filtros activos (cat/year/udp_s). Razón: el ACF `featured_post` puede ser de otra categoría; mostrarlo confundiría. Igual con páginas > 1.
- Card `--featured` es un markup ALTERNATIVO en card-noticia.php (rama `if/else` por variant). No comparte el `<a>...<body>` con default/horizontal — necesita estructura distinta (image fullbleed + overlays absolutos + título centrado).
- Helper `udp_query_noticias()` ahora se llama 3 veces en page 1 (featured fallback + side + grid). Cada query con su propio `exclude` correctamente acumulado. Performance OK porque cada query es WP_Query con `posts_per_page` pequeño y sin `found_rows` (no_found_rows opcional vía `need_pagination`).

**Pendientes**:
- F4c: Agenda (toggle grid/list, single-evento, card-evento.php). Reusará paginación + exclude pattern.
- F4 extras: image gallery del single (campo ACF gallery + carousel JS).
```

- [ ] **Step 6: Commit final**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add \
  acf-json/group_template_noticias.json \
  inc/udp-cards.php \
  templates/page-noticias.php \
  template-parts/blocks/parts/card-noticia.php \
  template-parts/archive/noticias-hero.php \
  src/scss/blocks/_card-grid.scss \
  src/scss/templates/_noticias-archive.scss \
  MEMORY.md
git commit -m "$(cat <<'EOF'
feat(noticias): F4b2 hero band + theme dark fix

- Archive theme corregido light → dark (F4b1 había sido light por error
  de spec). bg \$dark-1, breadcrumb/filtros/pagination invertidos.
- Hero band en página 1 sin filtros: 1 featured grande con image overlay
  + 2 side compactas. ACF featured_post para curaduría editorial; si vacío
  fallback al post más reciente con featured image.
- Card primitive con nuevo variant 'featured' (markup alternativo: image
  fullbleed + overlay gradient + eyebrow top-left + date top-right + título
  centrado Arizona Flare).
- Helper extendido: udp_query_noticias acepta arg 'exclude'; udp_card_data_from_post
  añade post_id al shape Card.
- posts_per_page logic: page 1 sin filtros = 1+2+6 = 9; con filtros o
  page > 1 = 9 grid.
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
| Theme archive light → dark | Task 5 (SCSS) + Task 6 (template) |
| Hero band: featured + 2 side stacked | Task 4 (partial) + Task 5 (SCSS hero layout) |
| Featured card variant (image overlay) | Task 3 (markup) + Task 5 (SCSS modifier) |
| ACF featured_post field | Task 2 |
| Bump posts_per_page 6 → 9 | Task 6 |
| Lógica page 1 vs 2+ | Task 6 |
| Helper exclude arg + post_id | Task 1 |
| Featured suprimido con filtros | Task 6 (`$show_hero` flag) |

**Cobertura completa.**

---

## Verification end-to-end (post-ejecución)

1. `/noticias/` (page 1 sin filtros): hero band visible, 9 cards (1 featured + 2 side + 6 grid). Theme dark.
2. `/noticias/?paged=2`: sin hero, 9 grid.
3. `/noticias/?cat=X` (page 1): sin hero, hasta 9 grid.
4. Admin asigna `featured_post` a un post específico desde el editor de la página Noticias: ese post aparece en el slot featured al recargar `/noticias/`.
5. Mobile: hero cae a 1-col (featured arriba, side cards apiladas debajo, luego grid 1-col).
6. Featured hover: image scale 1.04 (con motion enabled).
7. Single light no afectado — sigue renderizando hero light + content + share + related.
