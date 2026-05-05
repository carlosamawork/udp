# F7b — block_image_gallery + block_accordion — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement this plan task-by-task.

**Goal:** Añadir 2 layouts al field flex `content_blocks`: `block_image_gallery` (Swiper carousel similar a single-post-gallery F4 extras) + `block_accordion` (collapsibles vanilla JS con `<details>` semantic). Insertables en cualquier landing flex.

**Architecture:** Layouts adicionales en el ACF flex. Image gallery reusa el JS `single-post-gallery.js` pattern (lazy Swiper). Accordion usa `<details><summary>` nativo HTML5 (sin JS necesario; opcional smooth animation con JS).

---

## Inventario

**Crear:**
- `template-parts/blocks/block-block_image_gallery.php`
- `template-parts/blocks/block-block_accordion.php`
- `src/js/modules/block-image-gallery.js` — Swiper init lazy
- `src/js/modules/block-accordion.js` — opcional: smooth height animation
- `src/scss/blocks/_block-image-gallery.scss`
- `src/scss/blocks/_block-accordion.scss`

**Modificar:**
- `acf-json/group_template_flexible_content.json` — añadir 2 layouts.
- `src/js/main.js` — 2 imports + init en domReady.
- `src/scss/main.scss` — 2 imports.

---

## Task 1: ACF JSON — 2 layouts nuevos

- [ ] **Step 1: Layouts via jq**

```bash
JSON_PATH="/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_template_flexible_content.json"

cat > /tmp/layout-gallery.json <<'EOF'
{
    "key": "layout_block_image_gallery",
    "name": "block_image_gallery",
    "label": "Galería de imágenes",
    "display": "block",
    "sub_fields": [
        { "key": "field_block_gallery_titulo",  "label": "Título (opcional)", "name": "titulo",  "type": "text" },
        { "key": "field_block_gallery_eyebrow", "label": "Eyebrow (opcional)", "name": "eyebrow", "type": "text" },
        {
            "key": "field_block_gallery_images",
            "label": "Imágenes",
            "name": "images",
            "type": "gallery",
            "min": 1,
            "return_format": "array",
            "preview_size": "medium",
            "instructions": "Sube o selecciona imágenes desde la biblioteca. Mínimo 1."
        },
        {
            "key": "field_block_gallery_layout",
            "label": "Layout",
            "name": "layout",
            "type": "radio",
            "choices": { "carousel": "Carrusel (Swiper)", "grid": "Grid 3 columnas" },
            "default_value": "carousel",
            "layout": "horizontal",
            "return_format": "value"
        },
        {
            "key": "field_block_gallery_theme",
            "label": "Tema",
            "name": "theme",
            "type": "radio",
            "choices": { "dark": "Dark", "light": "Light" },
            "default_value": "dark",
            "layout": "horizontal",
            "return_format": "value"
        }
    ]
}
EOF

cat > /tmp/layout-accordion.json <<'EOF'
{
    "key": "layout_block_accordion",
    "name": "block_accordion",
    "label": "Acordeón",
    "display": "block",
    "sub_fields": [
        { "key": "field_block_accordion_titulo", "label": "Título (opcional)", "name": "titulo", "type": "text" },
        {
            "key": "field_block_accordion_items",
            "label": "Items",
            "name": "items",
            "type": "repeater",
            "min": 1,
            "layout": "block",
            "button_label": "Agregar item",
            "sub_fields": [
                { "key": "field_block_accordion_item_titulo",    "label": "Título del item", "name": "titulo", "type": "text", "required": 1 },
                { "key": "field_block_accordion_item_contenido", "label": "Contenido", "name": "contenido", "type": "wysiwyg", "tabs": "visual", "toolbar": "basic", "media_upload": 0, "delay": 0 },
                { "key": "field_block_accordion_item_open_default", "label": "Abierto por defecto", "name": "open_default", "type": "true_false", "ui": 1, "default_value": 0 }
            ]
        },
        {
            "key": "field_block_accordion_theme",
            "label": "Tema",
            "name": "theme",
            "type": "radio",
            "choices": { "dark": "Dark", "light": "Light" },
            "default_value": "dark",
            "layout": "horizontal",
            "return_format": "value"
        }
    ]
}
EOF

jq --slurpfile g /tmp/layout-gallery.json --slurpfile a /tmp/layout-accordion.json '
    .fields[0].layouts.layout_block_image_gallery = $g[0] |
    .fields[0].layouts.layout_block_accordion     = $a[0]
' "$JSON_PATH" > "$JSON_PATH.tmp" && mv "$JSON_PATH.tmp" "$JSON_PATH"

jq empty "$JSON_PATH" && echo "JSON válido"
jq '.fields[0].layouts | keys' "$JSON_PATH"
```

Expected: 7 layouts (los 5 + image_gallery + accordion).

- [ ] **Step 2: Sync DB-direct UPSERT**

`/tmp/acf-sync-flex-f7b.php`:

```php
<?php
$json = json_decode( file_get_contents( '/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_template_flexible_content.json' ), true );
global $wpdb;
$existing_id = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT ID FROM {$wpdb->posts} WHERE post_type='acf-field-group' AND post_name=%s AND post_status='publish' LIMIT 1",
    $json['key']
) );
if ( $existing_id > 0 ) {
    $json['ID'] = $existing_id;
    WP_CLI::log( 'UPDATE id=' . $existing_id );
} else {
    WP_CLI::log( 'CREATE' );
}
$result = acf_import_field_group( $json );
WP_CLI::success( 'id=' . $result['ID'] );
```

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval-file /tmp/acf-sync-flex-f7b.php --path=/Applications/MAMP/htdocs/udp/cms 2>&1 | tail -3
```

---

## Task 2: Container partials

- [ ] **Step 1: `template-parts/blocks/block-block_image_gallery.php`**

```php
<?php
/**
 * Block: Image Gallery (Swiper carrusel o grid).
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$titulo  = get_sub_field( 'titulo' );
$eyebrow = get_sub_field( 'eyebrow' );
$images  = get_sub_field( 'images' ) ?: array();
$layout  = get_sub_field( 'layout' ) ?: 'carousel';
$theme   = get_sub_field( 'theme' ) ?: 'dark';

if ( empty( $images ) ) {
    return;
}

$container_class = 'udp-block-image-gallery udp-block-image-gallery--' . $layout . ' udp-block-image-gallery--' . $theme;
?>
<section class="<?php echo esc_attr( $container_class ); ?>" <?php if ( $layout === 'carousel' ) : ?>data-udp-block-gallery<?php endif; ?>>
    <div class="udp-block-image-gallery__inner">
        <?php if ( $eyebrow || $titulo ) : ?>
            <header class="udp-block-image-gallery__header">
                <?php if ( $eyebrow ) : ?>
                    <p class="udp-block-image-gallery__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
                <?php endif; ?>
                <?php if ( $titulo ) : ?>
                    <h2 class="udp-block-image-gallery__title"><?php echo esc_html( $titulo ); ?></h2>
                <?php endif; ?>
            </header>
        <?php endif; ?>

        <?php if ( $layout === 'carousel' ) : ?>
            <div class="udp-block-image-gallery__viewport swiper">
                <ul class="udp-block-image-gallery__list swiper-wrapper">
                    <?php foreach ( $images as $image ) :
                        $url = $image['sizes']['large'] ?? ( $image['url'] ?? '' );
                        $alt = $image['alt'] ?? '';
                        if ( empty( $url ) ) continue;
                    ?>
                        <li class="udp-block-image-gallery__item swiper-slide">
                            <img src="<?php echo esc_url( $url ); ?>" alt="<?php echo esc_attr( $alt ); ?>" loading="lazy" decoding="async" />
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="udp-block-image-gallery__nav">
                <button type="button" class="udp-block-image-gallery__prev" aria-label="<?php esc_attr_e( 'Anterior', 'starter-theme' ); ?>">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M9 3L5 7l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <button type="button" class="udp-block-image-gallery__next" aria-label="<?php esc_attr_e( 'Siguiente', 'starter-theme' ); ?>">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M5 3l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
        <?php else : ?>
            <ul class="udp-block-image-gallery__list">
                <?php foreach ( $images as $image ) :
                    $url = $image['sizes']['large'] ?? ( $image['url'] ?? '' );
                    $alt = $image['alt'] ?? '';
                    if ( empty( $url ) ) continue;
                ?>
                    <li class="udp-block-image-gallery__item">
                        <img src="<?php echo esc_url( $url ); ?>" alt="<?php echo esc_attr( $alt ); ?>" loading="lazy" decoding="async" />
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>
```

- [ ] **Step 2: `template-parts/blocks/block-block_accordion.php`**

```php
<?php
/**
 * Block: Accordion (collapsible list usando <details><summary> HTML5).
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$titulo = get_sub_field( 'titulo' );
$items  = get_sub_field( 'items' ) ?: array();
$theme  = get_sub_field( 'theme' ) ?: 'dark';

if ( empty( $items ) ) {
    return;
}

$container_class = 'udp-block-accordion udp-block-accordion--' . $theme;
?>
<section class="<?php echo esc_attr( $container_class ); ?>">
    <div class="udp-block-accordion__inner">
        <?php if ( $titulo ) : ?>
            <h2 class="udp-block-accordion__title"><?php echo esc_html( $titulo ); ?></h2>
        <?php endif; ?>

        <ul class="udp-block-accordion__list">
            <?php foreach ( $items as $item ) :
                $item_titulo    = $item['titulo']       ?? '';
                $item_contenido = $item['contenido']    ?? '';
                $open_default   = ! empty( $item['open_default'] );
                if ( ! $item_titulo ) continue;
            ?>
                <li class="udp-block-accordion__item">
                    <details class="udp-block-accordion__details" <?php if ( $open_default ) echo 'open'; ?>>
                        <summary class="udp-block-accordion__summary">
                            <span class="udp-block-accordion__summary-title"><?php echo esc_html( $item_titulo ); ?></span>
                            <span class="udp-block-accordion__summary-icon" aria-hidden="true">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                                    <path d="M3 5l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        </summary>
                        <?php if ( $item_contenido ) : ?>
                            <div class="udp-block-accordion__content">
                                <?php echo wp_kses_post( $item_contenido ); ?>
                            </div>
                        <?php endif; ?>
                    </details>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
```

- [ ] **Step 3: Validar PHP**

```bash
PHP=/Applications/MAMP/bin/php/php8.4.1/bin/php
$PHP -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/blocks/block-block_image_gallery.php
$PHP -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/blocks/block-block_accordion.php
```

---

## Task 3: JS modules

- [ ] **Step 1: `src/js/modules/block-image-gallery.js`**

```javascript
/**
 * Block Image Gallery — Swiper init (lazy)
 */
import { qsa } from '@utils/dom';

export async function initBlockImageGallery() {
    const containers = qsa('[data-udp-block-gallery]');
    if (!containers.length) return;

    const { default: Swiper } = await import('swiper');
    const { Navigation, Keyboard, FreeMode } = await import('swiper/modules');
    await import('swiper/css');

    containers.forEach((el) => {
        const swiperEl = el.querySelector('.swiper');
        if (!swiperEl) return;

        new Swiper(swiperEl, {
            modules: [Navigation, Keyboard, FreeMode],
            slidesPerView: 'auto',
            spaceBetween: 16,
            keyboard: { enabled: true },
            grabCursor: true,
            freeMode: { enabled: true, momentum: true },
            navigation: {
                nextEl: el.querySelector('.udp-block-image-gallery__next'),
                prevEl: el.querySelector('.udp-block-image-gallery__prev'),
            },
            breakpoints: {
                768: { slidesPerView: 3, spaceBetween: 24 },
                0:   { slidesPerView: 1.1, spaceBetween: 12 },
            },
        });
    });
}
```

- [ ] **Step 2: `src/js/modules/block-accordion.js`**

```javascript
/**
 * Block Accordion — smooth height animation para <details>.
 *
 * El <details> nativo abre/cierra instantáneo. Este módulo añade
 * height animation. Si JS no carga, sigue funcional vía nativo.
 */
import { qsa } from '@utils/dom';

export function initBlockAccordion() {
    const detailsList = qsa('.udp-block-accordion__details');
    if (!detailsList.length) return;

    detailsList.forEach((details) => {
        const summary = details.querySelector('.udp-block-accordion__summary');
        const content = details.querySelector('.udp-block-accordion__content');
        if (!summary || !content) return;

        summary.addEventListener('click', (event) => {
            event.preventDefault();

            if (details.open) {
                // closing — animate to 0
                content.style.height = content.offsetHeight + 'px';
                requestAnimationFrame(() => {
                    content.style.transition = 'height 0.25s ease';
                    content.style.height = '0px';
                });
                content.addEventListener('transitionend', function once() {
                    details.open = false;
                    content.style.height = '';
                    content.style.transition = '';
                    content.removeEventListener('transitionend', once);
                });
            } else {
                details.open = true;
                const target = content.scrollHeight;
                content.style.height = '0px';
                requestAnimationFrame(() => {
                    content.style.transition = 'height 0.25s ease';
                    content.style.height = target + 'px';
                });
                content.addEventListener('transitionend', function once() {
                    content.style.height = '';
                    content.style.transition = '';
                    content.removeEventListener('transitionend', once);
                });
            }
        });
    });
}
```

- [ ] **Step 3: Wire en main.js**

Edit `src/js/main.js`. Localizar imports y AÑADIR:

```javascript
import { initBlockImageGallery } from '@modules/block-image-gallery';
import { initBlockAccordion } from '@modules/block-accordion';
```

En el `domReady(() => { ... })` AÑADIR antes del console.log:

```javascript
initBlockImageGallery();
initBlockAccordion();
```

---

## Task 4: SCSS

- [ ] **Step 1: `_block-image-gallery.scss`**

```scss
// ==========================================================================
// BLOCK IMAGE GALLERY — carrusel Swiper o grid 3-col.
// ==========================================================================

.udp-block-image-gallery {
    padding: $space-3xl 0;

    &--dark  { background-color: $dark-1; color: $white; }
    &--light { background-color: $white;  color: $dark-1; }

    &__inner {
        max-width: 1440px;
        margin-inline: auto;
        padding-inline: $space-3xl;

        @include media-down(md) { padding-inline: $space-sm; }
    }

    &__header {
        margin-bottom: $space-xl;
        display: flex;
        flex-direction: column;
        gap: $space-2xs;
    }

    &__eyebrow {
        margin: 0;
        font-family: $font-family-mono;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: $white-70;

        .udp-block-image-gallery--light & { color: $dark-2; }
    }

    &__title {
        margin: 0;
        font-family: $font-family-display;
        font-weight: 500;
        font-size: 32px;
        line-height: 1.1;
    }

    &__viewport {
        overflow: hidden;
    }

    &__list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    &--carousel &__list {
        display: flex;
    }

    &--grid &__list {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: $space-md;

        @include media-down(md) {
            grid-template-columns: 1fr;
        }
    }

    &__item {
        flex-shrink: 0;

        img {
            width: 100%;
            height: auto;
            aspect-ratio: 4 / 3;
            object-fit: cover;
            display: block;
        }
    }

    &__nav {
        display: flex;
        gap: 8px;
        margin-top: $space-md;
        justify-content: flex-end;
    }

    &__prev,
    &__next {
        width: 40px;
        height: 40px;
        border-radius: 9999px;
        border: 1px solid currentColor;
        background: transparent;
        color: inherit;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background-color $transition-base, color $transition-base;

        &:hover, &:focus-visible {
            background-color: $white;
            color: $dark-1;
            outline: none;
        }

        .udp-block-image-gallery--light &:hover,
        .udp-block-image-gallery--light &:focus-visible {
            background-color: $dark-1;
            color: $white;
        }

        &.swiper-button-disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }
    }
}
```

- [ ] **Step 2: `_block-accordion.scss`**

```scss
// ==========================================================================
// BLOCK ACCORDION — collapsible list con <details><summary>.
// JS añade height animation; sin JS funciona instantáneo via native.
// ==========================================================================

.udp-block-accordion {
    padding: $space-3xl 0;

    &--dark  { background-color: $dark-1; color: $white; }
    &--light { background-color: $white;  color: $dark-1; }

    &__inner {
        max-width: 880px;
        margin-inline: auto;
        padding-inline: $space-3xl;

        @include media-down(md) { padding-inline: $space-sm; }
    }

    &__title {
        margin: 0 0 $space-xl;
        font-family: $font-family-display;
        font-weight: 500;
        font-size: 32px;
        line-height: 1.1;
    }

    &__list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    &__item {
        border-top: 1px solid currentColor;

        &:last-child { border-bottom: 1px solid currentColor; }
    }

    &__details {
        // Override default browser styles
        summary { list-style: none; }
        summary::-webkit-details-marker { display: none; }
    }

    &__summary {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: $space-md;
        padding: $space-md 0;
        cursor: pointer;
        font-family: $font-family-body;
        font-weight: 500;
        font-size: 18px;
        line-height: 1.4;
        color: inherit;
        transition: color $transition-base;

        &:hover, &:focus-visible {
            color: $brand-blue;
            outline: none;
        }

        .udp-block-accordion--light &:hover,
        .udp-block-accordion--light &:focus-visible {
            color: $brand-blue;
        }
    }

    &__summary-icon {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.3s ease;
    }

    &__details[open] &__summary-icon {
        transform: rotate(180deg);
    }

    &__content {
        padding: 0 0 $space-md;
        font-family: $font-family-body;
        font-size: 16px;
        line-height: 24px;
        color: inherit;
        opacity: 0.9;
        overflow: hidden;

        p { margin: 0 0 $space-2xs; }
        p:last-child { margin-bottom: 0; }
        a { color: $brand-blue; text-decoration: underline; }
        ul, ol { margin: 0 0 $space-2xs; padding-inline-start: $space-lg; }
    }
}
```

- [ ] **Step 3: Imports + build**

Edit `src/scss/main.scss`:

```scss
@import "blocks/block-image-gallery";
@import "blocks/block-accordion";
```

Build:

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -5
```

Expected: build OK.

---

## Task 5: E2E + commit

- [ ] **Step 1: Seed page**

`/tmp/seed-f7b-test.php`:

```php
<?php
$page_id = wp_insert_post( array(
    'post_title'   => 'Test F7b Gallery+Accordion',
    'post_status'  => 'publish',
    'post_type'    => 'page',
    'post_name'    => 'test-f7b-gallery-accordion',
) );
update_post_meta( $page_id, '_wp_page_template', 'templates/page-flexible.php' );

// Encontrar 3 attachments
global $wpdb;
$att_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type='attachment' AND post_mime_type LIKE 'image/%' AND post_status='inherit' LIMIT 3" );
if ( count( $att_ids ) < 3 ) {
    $att_ids = array_pad( $att_ids, 3, $att_ids[0] ?? 0 );
}

update_post_meta( $page_id, 'content_blocks', array( 'block_image_gallery', 'block_accordion' ) );
update_post_meta( $page_id, '_content_blocks', 'field_template_flex_content_blocks' );

// Block 0: image_gallery
update_post_meta( $page_id, 'content_blocks_0_titulo', 'Galería de prueba' );
update_post_meta( $page_id, '_content_blocks_0_titulo', 'field_block_gallery_titulo' );
update_post_meta( $page_id, 'content_blocks_0_images', $att_ids );
update_post_meta( $page_id, '_content_blocks_0_images', 'field_block_gallery_images' );
update_post_meta( $page_id, 'content_blocks_0_layout', 'carousel' );
update_post_meta( $page_id, '_content_blocks_0_layout', 'field_block_gallery_layout' );
update_post_meta( $page_id, 'content_blocks_0_theme', 'dark' );
update_post_meta( $page_id, '_content_blocks_0_theme', 'field_block_gallery_theme' );

// Block 1: accordion
update_post_meta( $page_id, 'content_blocks_1_titulo', 'Preguntas frecuentes' );
update_post_meta( $page_id, '_content_blocks_1_titulo', 'field_block_accordion_titulo' );
update_post_meta( $page_id, 'content_blocks_1_items', 3 );
update_post_meta( $page_id, '_content_blocks_1_items', 'field_block_accordion_items' );
$items = array(
    array( '¿Cómo me inscribo?', '<p>Visita el portal de admisión.</p>', 1 ),
    array( '¿Cuándo empiezan las clases?', '<p>El segundo semestre comienza en marzo.</p>', 0 ),
    array( '¿Hay becas disponibles?', '<p>Sí, consulta nuestra página de becas.</p>', 0 ),
);
foreach ( $items as $i => $item ) {
    update_post_meta( $page_id, "content_blocks_1_items_{$i}_titulo", $item[0] );
    update_post_meta( $page_id, "_content_blocks_1_items_{$i}_titulo", 'field_block_accordion_item_titulo' );
    update_post_meta( $page_id, "content_blocks_1_items_{$i}_contenido", $item[1] );
    update_post_meta( $page_id, "_content_blocks_1_items_{$i}_contenido", 'field_block_accordion_item_contenido' );
    update_post_meta( $page_id, "content_blocks_1_items_{$i}_open_default", $item[2] );
    update_post_meta( $page_id, "_content_blocks_1_items_{$i}_open_default", 'field_block_accordion_item_open_default' );
}
update_post_meta( $page_id, 'content_blocks_1_theme', 'light' );
update_post_meta( $page_id, '_content_blocks_1_theme', 'field_block_accordion_theme' );

WP_CLI::success( 'page_id=' . $page_id );
```

```bash
PAGE_ID=$(/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval-file /tmp/seed-f7b-test.php --path=/Applications/MAMP/htdocs/udp/cms 2>&1 | grep -oE 'page_id=[0-9]+' | sed 's/page_id=//')
echo "PAGE_ID=$PAGE_ID"
```

- [ ] **Step 2: Verify**

```bash
TS=$(date +%s)
echo "=== Markup classes ==="
curl -s "http://localhost:8888/udp/test-f7b-gallery-accordion/?nocache=$TS" | grep -oE "udp-block-(image-gallery|accordion)[a-z_-]*" | sort -u
echo ""
echo "=== Gallery slides count (esperado 3) ==="
curl -s "http://localhost:8888/udp/test-f7b-gallery-accordion/?nocache=$TS" | grep -cE 'udp-block-image-gallery__item swiper-slide'
echo ""
echo "=== Accordion items count (esperado 3) ==="
curl -s "http://localhost:8888/udp/test-f7b-gallery-accordion/?nocache=$TS" | grep -cE 'class="udp-block-accordion__details"'
echo ""
echo "=== First accordion item open (esperado 1) ==="
curl -s "http://localhost:8888/udp/test-f7b-gallery-accordion/?nocache=$TS" | grep -cE 'udp-block-accordion__details" open'
```

- [ ] **Step 3: Cleanup + MEMORY + commit**

```bash
export MYSQL_PWD=root
SOCK=/Applications/MAMP/tmp/mysql/mysql.sock
/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=$SOCK -uroot udp -e "DELETE FROM wp_fnku4yposts WHERE ID = $PAGE_ID; DELETE FROM wp_fnku4ypostmeta WHERE post_id = $PAGE_ID;"
rm -f /tmp/seed-f7b-test.php /tmp/acf-sync-flex-f7b.php /tmp/layout-gallery.json /tmp/layout-accordion.json
```

Append a MEMORY.md:

```markdown

### 2026-04-29 — F7b block_image_gallery + block_accordion

**Hechos**:
- 2 layouts añadidos al field flex `content_blocks`:
  - `block_image_gallery`: ACF gallery field + radio layout (carousel/grid). Carousel reusa Swiper lazy-loaded (mismo pattern que single-post-gallery F4 extras). Grid 3-col CSS native.
  - `block_accordion`: repeater de items (titulo + wysiwyg contenido + open_default toggle). Markup nativo `<details><summary>` HTML5 — funciona sin JS pero JS añade smooth height animation.
- 2 JS modules: `block-image-gallery.js` (lazy Swiper init) + `block-accordion.js` (smooth height transition con scrollHeight + transitionend listener). Ambos wired en main.js domReady.
- 2 SCSS nuevos. Accordion con border-top en cada item + chevron rotating 180° on open. Gallery con nav buttons hover invertido.

**Decisiones clave**:
- `<details><summary>` nativo HTML5 en lugar de divs+aria — accesible por defecto, expandible vía teclado, funciona sin JS.
- JS de accordion previene el toggle nativo (`event.preventDefault()`) y maneja apertura/cierre con animation. Si JS falla, el browser usa el toggle nativo (graceful degradation).
- Gallery duplica el patrón de single-post-gallery: `data-udp-block-gallery` selector, lazy import Swiper, navigation buttons custom UDP.

**Pendientes**:
- F7c: block_premios_list + block_people_list (repeaters estructurados con foto/cargo).
- 11 landings de contenido en admin.
```

Commit:

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add \
  acf-json/group_template_flexible_content.json \
  template-parts/blocks/block-block_image_gallery.php \
  template-parts/blocks/block-block_accordion.php \
  src/js/modules/block-image-gallery.js \
  src/js/modules/block-accordion.js \
  src/js/main.js \
  src/scss/blocks/_block-image-gallery.scss \
  src/scss/blocks/_block-accordion.scss \
  src/scss/main.scss \
  MEMORY.md
git commit -m "$(cat <<'EOF'
feat(blocks): F7b — image_gallery + accordion

- block_image_gallery: ACF gallery field + carousel (Swiper lazy) o
  grid 3-col. Reusa pattern de single-post-gallery F4 extras.
- block_accordion: repeater items con <details><summary> HTML5 nativo.
  JS añade smooth height animation; sin JS funciona via native toggle
  (graceful degradation).
- 2 JS modules wired en main.js domReady.
- 2 SCSS con themes dark/light.
EOF
)"
```

---

## Verification

1. Admin inserta `block_image_gallery` con 3 imágenes layout=carousel → Swiper con prev/next funciona.
2. Cambiar layout=grid → 3-col native sin Swiper.
3. Admin inserta `block_accordion` con 3 items → primer item open_default. Click expande/colapsa con smooth animation.
4. JS desactivado: accordion sigue funcional (toggle nativo de details), gallery cae a static (no carousel).
