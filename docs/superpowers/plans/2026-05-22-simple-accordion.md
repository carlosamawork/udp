# Simple Accordion — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create a reusable WordPress page template (`templates/page-simple-accordion.php`) with breadcrumb + title, 3-column layout (lateral placeholders + main content with `the_content()` + ACF accordion), and a "También te puede interesar" Swiper section.

**Architecture:** A new page template orchestrates four partials (`page-header`, `main-content`, `related`) and reuses three existing partials (`breadcrumb.php`, `block-block_accordion` markup classes, `post-share.php`). The accordion reuses F7b's `initBlockAccordion()` via matching BEM class names. The related Swiper piggybacks on F3's `initSectionLandingSwiper()` via the existing `udp-section-cards--swiper` class. ACF fields live in a new JSON group, synced to DB via `wp eval-file`.

**Tech Stack:** PHP 8.4, WordPress, ACF Pro (JSON sync), Vite 6 + SCSS, Swiper.js (already installed), Vanilla JS (no new modules needed).

---

## File Map

| Action | Path | Responsibility |
|--------|------|----------------|
| Create | `acf-json/group_template_simple_accordion.json` | ACF field group definition |
| Create | `templates/page-simple-accordion.php` | Template orchestrator |
| Create | `template-parts/simple-accordion/page-header.php` | Breadcrumb + page title |
| Create | `template-parts/simple-accordion/main-content.php` | `the_content()` + ACF accordion repeater |
| Create | `template-parts/simple-accordion/related.php` | "También te puede interesar" Swiper |
| Create | `src/scss/templates/_simple-accordion.scss` | BEM styles for this template |
| Modify | `src/scss/main.scss` | Add `@import "templates/simple-accordion";` |

---

### Task 1: ACF JSON field group

**Files:**
- Create: `acf-json/group_template_simple_accordion.json`

- [ ] **Step 1: Create the ACF JSON file**

```json
{
    "key": "group_template_simple_accordion",
    "title": "Template — Simple Accordion",
    "fields": [
        {
            "key": "field_tsa_acordeon",
            "label": "Acordeón",
            "name": "acordeon",
            "type": "repeater",
            "instructions": "Items del acordeón que aparecen debajo del texto principal.",
            "required": 0,
            "min": 0,
            "max": 0,
            "layout": "block",
            "button_label": "Añadir item",
            "sub_fields": [
                {
                    "key": "field_tsa_acordeon_titulo",
                    "label": "Título",
                    "name": "titulo",
                    "type": "text",
                    "required": 1,
                    "instructions": "Texto visible en el header del item.",
                    "default_value": "",
                    "placeholder": "",
                    "prepend": "",
                    "append": "",
                    "maxlength": ""
                },
                {
                    "key": "field_tsa_acordeon_contenido",
                    "label": "Contenido",
                    "name": "contenido",
                    "type": "wysiwyg",
                    "required": 0,
                    "instructions": "Cuerpo del item. Admite formato rico.",
                    "tabs": "all",
                    "toolbar": "full",
                    "media_upload": 1,
                    "default_value": "",
                    "delay": 0
                }
            ]
        },
        {
            "key": "field_tsa_relacionados",
            "label": "También te puede interesar",
            "name": "relacionados",
            "type": "repeater",
            "instructions": "Links manuales que aparecen en el carrusel inferior.",
            "required": 0,
            "min": 0,
            "max": 0,
            "layout": "table",
            "button_label": "Añadir relacionado",
            "sub_fields": [
                {
                    "key": "field_tsa_relacionados_titulo",
                    "label": "Título",
                    "name": "titulo",
                    "type": "text",
                    "required": 1,
                    "default_value": "",
                    "placeholder": "",
                    "prepend": "",
                    "append": "",
                    "maxlength": ""
                },
                {
                    "key": "field_tsa_relacionados_link",
                    "label": "Link",
                    "name": "link",
                    "type": "link",
                    "required": 1,
                    "instructions": "Acepta URLs internas y externas. El campo ACF Link gestiona target automáticamente.",
                    "return_format": "array"
                }
            ]
        }
    ],
    "location": [
        [
            {
                "param": "page_template",
                "operator": "==",
                "value": "templates/page-simple-accordion.php"
            }
        ]
    ],
    "menu_order": 0,
    "position": "normal",
    "style": "default",
    "label_placement": "top",
    "instruction_placement": "label",
    "hide_on_screen": "",
    "active": true,
    "description": "Template Simple Accordion: texto editorial + acordeón ACF + links relacionados en Swiper. Usado en páginas de la sección Conoce UDP."
}
```

- [ ] **Step 2: Sync the ACF group to the database**

Create `/tmp/upsert_tsa.php`:

```php
<?php
if ( ! function_exists( 'acf_add_local_field_group' ) ) {
    die( "ACF Pro not active.\n" );
}

$json_path = get_template_directory() . '/acf-json/group_template_simple_accordion.json';
$data      = json_decode( file_get_contents( $json_path ), true );

if ( ! $data ) {
    die( "JSON parse error.\n" );
}

// Remove existing group if present
if ( function_exists( 'acf_get_field_group' ) ) {
    $existing = acf_get_field_group( $data['key'] );
    if ( $existing ) {
        acf_delete_field_group( $existing['ID'] );
        echo "Existing group deleted.\n";
    }
}

acf_import_field_group( $data );
echo "Group 'group_template_simple_accordion' imported OK.\n";
```

Run it:

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval-file /tmp/upsert_tsa.php --path=/Applications/MAMP/htdocs/udp/cms
```

Expected output: `Group 'group_template_simple_accordion' imported OK.`

- [ ] **Step 3: Verify the group appears in wp-admin**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval 'var_dump( acf_get_field_group("group_template_simple_accordion")["title"] ?? "NOT FOUND" );' --path=/Applications/MAMP/htdocs/udp/cms
```

Expected output: `string(26) "Template — Simple Accordion"`

---

### Task 2: Page template + page-header partial

**Files:**
- Create: `templates/page-simple-accordion.php`
- Create: `template-parts/simple-accordion/page-header.php`

- [ ] **Step 1: Create the main template file**

`templates/page-simple-accordion.php`:

```php
<?php
/**
 * Template Name: Simple Accordion
 *
 * Layout: breadcrumb + título / 3 columnas (laterales vacías + columna central con
 * the_content() + acordeón ACF) / sección "También te puede interesar" (Swiper).
 * Columnas laterales son placeholders para fase posterior (tarjetas de compañero).
 *
 * @package Starter_Theme
 */

get_header();

$acordeon    = function_exists( 'get_field' ) ? get_field( 'acordeon' )    : array();
$relacionados = function_exists( 'get_field' ) ? get_field( 'relacionados' ) : array();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-simple-accordion' ); ?>>

    <?php
    get_template_part( 'template-parts/simple-accordion/page-header' );

    get_template_part(
        'template-parts/simple-accordion/main-content',
        null,
        array( 'acordeon' => $acordeon ?: array() )
    );

    if ( ! empty( $relacionados ) ) {
        get_template_part(
            'template-parts/simple-accordion/related',
            null,
            array( 'relacionados' => $relacionados )
        );
    }
    ?>

</article>

<?php
get_template_part(
    'template-parts/single/post-share',
    null,
    array( 'post_id' => get_the_ID() )
);

get_footer();
```

- [ ] **Step 2: Create the page-header partial**

`template-parts/simple-accordion/page-header.php`:

```php
<?php
/**
 * Simple Accordion — cabecera de página
 *
 * Breadcrumb automático + título de la página.
 *
 * @package Starter_Theme
 */
?>
<header class="udp-simple-accordion__header">
    <div class="container">
        <?php
        get_template_part(
            'template-parts/sections/breadcrumb',
            null,
            array( 'home_label' => __( 'Inicio', 'starter-theme' ) )
        );
        ?>
        <h1 class="udp-simple-accordion__title"><?php the_title(); ?></h1>
    </div>
</header>
```

- [ ] **Step 3: Create the template-parts/simple-accordion directory**

```bash
mkdir -p /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/simple-accordion
```

---

### Task 3: main-content partial (the_content + accordion)

**Files:**
- Create: `template-parts/simple-accordion/main-content.php`

- [ ] **Step 1: Create the partial**

The accordion markup reuses the exact BEM classes that `initBlockAccordion()` watches (`.udp-block-accordion__details`, `.udp-block-accordion__summary`, `.udp-block-accordion__content`). No JS changes needed.

`template-parts/simple-accordion/main-content.php`:

```php
<?php
/**
 * Simple Accordion — columna central: the_content() + acordeón ACF.
 *
 * Renderiza el layout 3-col. Las columnas laterales son <aside> vacíos —
 * puntos de extensión para la fase posterior (tarjetas de compañero).
 *
 * @package Starter_Theme
 *
 * @var array $args {
 *     @type array $acordeon  Rows del repeater ACF 'acordeon'.
 * }
 */
$acordeon = isset( $args['acordeon'] ) ? $args['acordeon'] : array();
?>
<div class="udp-simple-accordion__layout">

    <aside class="udp-simple-accordion__col-left" aria-hidden="true"></aside>

    <div class="udp-simple-accordion__col-center">

        <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
            <div class="udp-simple-accordion__body">
                <?php the_content(); ?>
            </div>
        <?php endwhile; endif; ?>

        <?php if ( ! empty( $acordeon ) ) : ?>
            <div class="udp-simple-accordion__accordion">
                <ul class="udp-block-accordion__list">
                    <?php foreach ( $acordeon as $item ) :
                        $item_titulo    = isset( $item['titulo'] )    ? $item['titulo']    : '';
                        $item_contenido = isset( $item['contenido'] ) ? $item['contenido'] : '';
                        if ( ! $item_titulo ) continue;
                    ?>
                        <li class="udp-block-accordion__item">
                            <details class="udp-block-accordion__details">
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
        <?php endif; ?>

    </div>

    <aside class="udp-simple-accordion__col-right" aria-hidden="true"></aside>

</div>
```

---

### Task 4: related partial (Swiper)

**Files:**
- Create: `template-parts/simple-accordion/related.php`

- [ ] **Step 1: Create the partial**

The container carries `udp-section-cards--swiper` which triggers `initSectionLandingSwiper()` from F3. The Swiper expects `.swiper > .swiper-wrapper > .swiper-slide` inside the container.

`template-parts/simple-accordion/related.php`:

```php
<?php
/**
 * Simple Accordion — sección "También te puede interesar".
 *
 * Swiper horizontal de link cards (sin imagen). Reutiliza initSectionLandingSwiper()
 * de F3 mediante la clase udp-section-cards--swiper.
 *
 * @package Starter_Theme
 *
 * @var array $args {
 *     @type array $relacionados  Rows del repeater ACF 'relacionados'.
 * }
 */
$relacionados = isset( $args['relacionados'] ) ? $args['relacionados'] : array();

if ( empty( $relacionados ) ) {
    return;
}
?>
<section class="udp-simple-accordion__related">
    <div class="container">
        <h2 class="udp-simple-accordion__related-title">
            <?php esc_html_e( 'También te puede interesar', 'starter-theme' ); ?>
        </h2>
    </div>

    <div class="udp-section-cards--swiper">
        <div class="swiper">
            <div class="swiper-wrapper">
                <?php foreach ( $relacionados as $item ) :
                    $titulo = isset( $item['titulo'] ) ? $item['titulo'] : '';
                    $link   = isset( $item['link'] )   ? $item['link']   : array();
                    if ( ! $titulo || empty( $link['url'] ) ) continue;
                    $target = ! empty( $link['target'] ) ? $link['target'] : '_self';
                ?>
                    <div class="swiper-slide">
                        <a
                            class="udp-simple-accordion__related-card"
                            href="<?php echo esc_url( $link['url'] ); ?>"
                            target="<?php echo esc_attr( $target ); ?>"
                            <?php if ( '_blank' === $target ) echo 'rel="noopener noreferrer"'; ?>
                        >
                            <span class="udp-simple-accordion__related-card-title">
                                <?php echo esc_html( $titulo ); ?>
                            </span>
                            <span class="udp-simple-accordion__related-card-arrow" aria-hidden="true">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                    <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
```

---

### Task 5: SCSS styles + import

**Files:**
- Create: `src/scss/templates/_simple-accordion.scss`
- Modify: `src/scss/main.scss`

- [ ] **Step 1: Create the SCSS file**

`src/scss/templates/_simple-accordion.scss`:

```scss
// ─── Simple Accordion Template ─────────────────────────────────────────────

.udp-simple-accordion {

    // Header — breadcrumb + título
    &__header {
        background-color: $white;
        padding-top: $space-xl;
        padding-bottom: $space-xl;
    }

    &__title {
        font-family: $font-arizona;
        font-size: clamp(2rem, 4vw, 3rem);
        font-weight: 700;
        color: $black;
        margin-top: $space-sm;
        margin-bottom: 0;
    }

    // Layout 3-col: 318px | 1fr | 318px
    &__layout {
        display: grid;
        grid-template-columns: 318px 1fr 318px;
        gap: 30px;
        padding-top: $space-2xl;
        padding-bottom: $space-2xl;
        max-width: var(--bs-container-width, 1320px);
        margin-inline: auto;
        padding-inline: $space-xl;

        @include media-breakpoint-down(lg) {
            grid-template-columns: 1fr;
        }
    }

    &__col-left,
    &__col-right {
        @include media-breakpoint-down(lg) {
            display: none;
        }
    }

    &__col-center {
        min-width: 0; // evita desbordamiento en grid
    }

    // Prose content (the_content)
    &__body {
        font-size: 1rem;
        line-height: 1.7;
        color: $black;
        margin-bottom: $space-xl;

        p:last-child { margin-bottom: 0; }

        a { color: $primary; }
    }

    // Accordion wrapper (usa clases BEM de udp-block-accordion — F7b)
    &__accordion {
        margin-top: $space-xl;
    }

    // Sección relacionados
    &__related {
        background-color: $gray-100;
        padding-top: $space-2xl;
        padding-bottom: $space-2xl;
    }

    &__related-title {
        font-family: $font-arizona;
        font-size: clamp(1.25rem, 2.5vw, 1.75rem);
        font-weight: 700;
        color: $black;
        margin-bottom: $space-lg;
    }

    // Card individual dentro del Swiper
    &__related-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: $space-sm;
        padding: $space-md $space-lg;
        background-color: $white;
        border: 1px solid $gray-200;
        border-radius: 4px;
        text-decoration: none;
        color: $black;
        width: 240px; // Swiper slidesPerView: auto — el ancho fijo define el slide
        transition: background-color 0.15s ease, color 0.15s ease;

        &:hover,
        &:focus-visible {
            background-color: $primary;
            color: $white;
        }
    }

    &__related-card-title {
        font-size: 0.9375rem;
        font-weight: 600;
        line-height: 1.3;
    }

    &__related-card-arrow {
        flex-shrink: 0;
        display: flex;
    }
}
```

- [ ] **Step 2: Add the import to main.scss**

In `src/scss/main.scss`, after line `@import "templates/centros-single";` add:

```scss
@import "templates/simple-accordion";
```

The templates section (section 8) will look like:

```scss
// 8. Templates (page templates con estilos propios)

@import "templates/noticias-archive";
@import "templates/noticias-single";

@import "templates/eventos-archive";
@import "templates/eventos-single";
@import "templates/calendario-archive";
@import "templates/concursos-archive";
@import "templates/concursos-single";
@import "templates/facultades-archive";
@import "templates/carreras-archive";
@import "templates/carreras-single";
@import "templates/centros-archive";
@import "templates/centros-single";
@import "templates/simple-accordion";
```

- [ ] **Step 3: Build and verify no SCSS errors**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -20
```

Expected: build completes without errors, `dist/` updated.

---

### Task 6: E2E verification + commit

- [ ] **Step 1: Assign the template to a test page**

In wp-admin: Pages → (create a test page or use "Historia") → Page Attributes → Template → **Simple Accordion**. Save.

- [ ] **Step 2: Verify template renders**

```bash
curl -s "http://localhost:8888/udp/?p={PAGE_ID}" | grep -c "udp-simple-accordion"
```

Expected: at least 3 matches (article class, layout div, header).

- [ ] **Step 3: Verify breadcrumb**

```bash
curl -s "http://localhost:8888/udp/?p={PAGE_ID}" | grep "udp-breadcrumb"
```

Expected: `<nav class="udp-breadcrumb"` present.

- [ ] **Step 4: Add accordion items in wp-admin and verify markup**

Add 2–3 items to the Acordeón repeater. Save. Then:

```bash
curl -s "http://localhost:8888/udp/?p={PAGE_ID}" | grep "udp-block-accordion__details"
```

Expected: one `<details class="udp-block-accordion__details">` per item.

- [ ] **Step 5: Add related items and verify Swiper container**

Add 2–3 items to the Relacionados repeater. Save. Then:

```bash
curl -s "http://localhost:8888/udp/?p={PAGE_ID}" | grep "udp-section-cards--swiper"
```

Expected: `<div class="udp-section-cards--swiper">` present.

- [ ] **Step 6: Smoke-test in browser**

Open `http://localhost:8888/udp/?p={PAGE_ID}` in a browser:
- Breadcrumb shows correct hierarchy (Inicio › Conoce UDP › Página)
- `the_content()` displays the editor content
- Accordion items expand/collapse with animation (JS F7b active)
- Related section shows Swiper with cards; draggable horizontally
- Share button (floating vertical bar) visible on right
- At `< lg` viewport: lateral columns hidden, center column at 100%

- [ ] **Step 7: Commit**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add \
  acf-json/group_template_simple_accordion.json \
  templates/page-simple-accordion.php \
  template-parts/simple-accordion/page-header.php \
  template-parts/simple-accordion/main-content.php \
  template-parts/simple-accordion/related.php \
  src/scss/templates/_simple-accordion.scss \
  src/scss/main.scss \
  MEMORY.md
git commit -m "feat(simple-accordion): template reutilizable para páginas Conoce UDP"
```

---

## Spec coverage check

| Spec requirement | Covered by |
|---|---|
| Breadcrumb + título de página | Task 2 — `page-header.php` |
| Grid 318px \| 1fr \| 318px | Task 5 — SCSS `__layout` |
| Columnas laterales vacías (placeholders) | Task 3 — `<aside>` con clase BEM |
| `< lg`: laterales `display:none` | Task 5 — `@include media-breakpoint-down(lg)` |
| `the_content()` en columna central | Task 3 — `__body` |
| Acordeón ACF repeater (`titulo` + `contenido`) | Task 1 + Task 3 |
| Early return si acordeón vacío | Task 3 — `if ( ! empty( $acordeon ) )` |
| JS F7b reused (`.udp-block-accordion__details`) | Task 3 — BEM classes idénticas, sin cambios en JS |
| Sección "También te puede interesar" | Task 4 — `related.php` |
| Swiper con `udp-section-cards--swiper` (F3 reuse) | Task 4 |
| Early return si relacionados vacío | Task 4 — `if ( empty( $relacionados ) )` |
| ACF Link field (interno + externo) | Task 1 — `field_tsa_relacionados_link` type=link |
| Share button flotante (F4b reuse) | Task 2 — `get_template_part('post-share')` |
| ACF JSON + UPSERT script (nunca admin) | Task 1 |
