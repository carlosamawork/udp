# F9 — `page-institucional` template — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implementar el template `page-institucional` que sirve páginas tipo "Forma de Gobierno" y "Consejo Académico" — hero morado + breadcrumb + 4 layouts de flexible content (rich text+sidebar, cards dark, people carousel, back link) + chips bar sticky + rail vertical flotante con scrollspy + botón share flotante.

**Architecture:** Template raíz `templates/page-institucional.php` que orquesta partials aislados en `template-parts/institucional/`. ACF Flexible Content con 4 layouts. Anchors derivados en runtime por helper PHP (`udp_institucional_collect_anchors`). Scrollspy con IntersectionObserver en módulo JS dedicado.

**Tech Stack:** WordPress + ACF Pro (Flexible Content + JSON sync), SCSS BEM (`udp-inst-*`), Swiper (reusado de F3 para layout C), IntersectionObserver vanilla.

**Spec:** `docs/superpowers/specs/2026-05-20-f9-page-institucional-template-design.md`
**Figmas:** `4QlgGMlzNR9Ye344bAFuye` → nodos `3706:20200` (Forma de Gobierno) y `3722:43026` (Consejo Académico)

---

## Inventario

**Crear:**
- `inc/udp-institucional.php` — helpers PHP (anchor collection)
- `acf-json/group_page_institucional.json` — schema ACF
- `templates/page-institucional.php` — template raíz
- `template-parts/institucional/header.php` — hero + breadcrumb
- `template-parts/institucional/nav-chips.php` — chips bar sticky
- `template-parts/institucional/nav-rail.php` — rail vertical desktop
- `template-parts/institucional/layout-rich-text-sidebar.php` — layout A
- `template-parts/institucional/layout-cards-dark-row.php` — layout B
- `template-parts/institucional/layout-people-carousel.php` — layout C
- `template-parts/institucional/layout-back-link.php` — layout D
- `template-parts/sections/share-floating.php` — botón share flotante
- `src/scss/templates/_institucional.scss`
- `src/js/modules/anchor-scrollspy.js`
- `src/js/modules/share-floating.js`

**Modificar:**
- `functions.php` — require helper
- `src/js/main.js` — import + init de scrollspy y share
- `src/scss/main.scss` — import del template

---

## Task 1: Helper PHP de recolección de anchors

**Files:**
- Create: `inc/udp-institucional.php`
- Modify: `functions.php`

- [ ] **Step 1: Crear el helper**

```php
<?php
/**
 * Helpers para el template page-institucional.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Recolecta los anchors a partir del flexible content `sections` del post actual.
 *
 * Cada layout que tenga `anchor_label` no vacío genera una entrada.
 * El layout `back_link` solo aparece si `display_in_anchors=true`.
 * Se prepende un anchor "Inicio" que apunta al hero.
 *
 * @param int|null $post_id Post a consultar; null usa el actual.
 * @return array<int,array{id:string,label:string,icon:?array,order:int,layout_key:string}>
 */
function udp_institucional_collect_anchors( $post_id = null ) {
    if ( ! function_exists( 'get_field' ) ) {
        return array();
    }

    $sections = get_field( 'sections', $post_id );
    if ( ! is_array( $sections ) ) {
        $sections = array();
    }

    $anchors = array();
    $used_ids = array();

    // Anchor "Inicio" auto al inicio
    $anchors[] = array(
        'id'         => 'section-inicio',
        'label'      => __( 'Inicio', 'starter-theme' ),
        'icon'       => null,
        'order'      => 0,
        'layout_key' => '__hero__',
    );
    $used_ids['section-inicio'] = 1;

    $order = 1;
    foreach ( $sections as $section ) {
        $layout = $section['acf_fc_layout'] ?? '';
        $label  = trim( (string) ( $section['anchor_label'] ?? '' ) );

        if ( $label === '' ) {
            continue;
        }

        if ( $layout === 'back_link' && empty( $section['display_in_anchors'] ) ) {
            continue;
        }

        $base_id = 'section-' . sanitize_title( $label );
        $id      = $base_id;
        $suffix  = 2;
        while ( isset( $used_ids[ $id ] ) ) {
            $id = $base_id . '-' . $suffix;
            $suffix++;
        }
        $used_ids[ $id ] = 1;

        $icon = $section['anchor_icon'] ?? null;
        if ( ! is_array( $icon ) ) {
            $icon = null;
        }

        $anchors[] = array(
            'id'         => $id,
            'label'      => $label,
            'icon'       => $icon,
            'order'      => $order,
            'layout_key' => $layout,
        );

        $order++;
    }

    return $anchors;
}

/**
 * Devuelve el id de anchor para una sección dada del flexible content,
 * tomando como referencia el array completo de anchors.
 *
 * Útil dentro de cada partial layout-*.php para obtener su id sin re-derivar.
 */
function udp_institucional_anchor_for_index( array $anchors, $section_index ) {
    foreach ( $anchors as $a ) {
        if ( $a['order'] === ( $section_index + 1 ) ) {
            return $a;
        }
    }
    return null;
}
```

- [ ] **Step 2: Wire helper en `functions.php`**

Localizar la sección de `require_once` de inc/* y añadir:

```php
require_once STARTER_BS5_DIR . 'inc/udp-institucional.php';
```

Verificar con grep que se añadió:

```bash
grep -n "udp-institucional" /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/functions.php
```

Expected: una línea con el require.

- [ ] **Step 3: Verificación manual**

Abrir cualquier página de WP en backend (no rompe nada porque no hay caller todavía):

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8888/udp/cms/wp-admin/
```

Expected: `302` (redirect a login) o `200`. No `500`.

- [ ] **Step 4: Commit**

```bash
git add inc/udp-institucional.php functions.php
git commit -m "feat(f9): helper udp_institucional_collect_anchors"
```

---

## Task 2: ACF schema — `group_page_institucional.json`

**Files:**
- Create: `acf-json/group_page_institucional.json`

- [ ] **Step 1: Crear el JSON inicial con flexible content y un primer layout**

```bash
cat > /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_page_institucional.json <<'EOF'
{
    "key": "group_page_institucional",
    "title": "Página Institucional",
    "fields": [
        {
            "key": "field_inst_header",
            "label": "Cabecera",
            "name": "page_header",
            "type": "group",
            "layout": "block",
            "sub_fields": [
                {
                    "key": "field_inst_header_show_breadcrumb",
                    "label": "Mostrar breadcrumb",
                    "name": "show_breadcrumb",
                    "type": "true_false",
                    "ui": 1,
                    "default_value": 1
                }
            ]
        },
        {
            "key": "field_inst_sections",
            "label": "Secciones",
            "name": "sections",
            "type": "flexible_content",
            "instructions": "Cada sección añade un anchor a la chips bar superior y al rail vertical (excepto Back link, configurable).",
            "required": 0,
            "button_label": "Añadir sección",
            "min": 0,
            "layouts": {
                "layout_inst_rich_text_sidebar": {
                    "key": "layout_inst_rich_text_sidebar",
                    "name": "rich_text_sidebar",
                    "label": "Texto + sidebar",
                    "display": "block",
                    "sub_fields": [
                        { "key": "field_inst_rts_anchor_label", "label": "Anchor label", "name": "anchor_label", "type": "text", "required": 1, "instructions": "Aparece en chips bar y como tooltip del rail" },
                        { "key": "field_inst_rts_anchor_icon", "label": "Anchor icon", "name": "anchor_icon", "type": "image", "return_format": "array", "preview_size": "thumbnail", "instructions": "SVG ideal 32×32. Si vacío usa número de orden" },
                        { "key": "field_inst_rts_title", "label": "Título (H2)", "name": "title", "type": "text", "required": 1 },
                        { "key": "field_inst_rts_body", "label": "Cuerpo", "name": "body", "type": "wysiwyg", "toolbar": "full", "media_upload": 0, "required": 1 },
                        { "key": "field_inst_rts_sidebar_cards", "label": "Tarjetas laterales", "name": "sidebar_cards", "type": "repeater", "min": 0, "max": 6, "layout": "block", "button_label": "Añadir tarjeta", "sub_fields": [
                            { "key": "field_inst_rts_card_title", "label": "Título", "name": "title", "type": "text", "required": 1 },
                            { "key": "field_inst_rts_card_body", "label": "Texto", "name": "body", "type": "textarea", "rows": 3 },
                            { "key": "field_inst_rts_card_cta", "label": "CTA", "name": "cta", "type": "link", "return_format": "array", "required": 1 }
                        ]}
                    ]
                },
                "layout_inst_cards_dark_row": {
                    "key": "layout_inst_cards_dark_row",
                    "name": "cards_dark_row",
                    "label": "Fila cards oscura",
                    "display": "block",
                    "sub_fields": [
                        { "key": "field_inst_cdr_anchor_label", "label": "Anchor label", "name": "anchor_label", "type": "text", "required": 1 },
                        { "key": "field_inst_cdr_anchor_icon", "label": "Anchor icon", "name": "anchor_icon", "type": "image", "return_format": "array", "preview_size": "thumbnail" },
                        { "key": "field_inst_cdr_title", "label": "Título", "name": "title", "type": "text", "required": 1 },
                        { "key": "field_inst_cdr_cards", "label": "Cards", "name": "cards", "type": "repeater", "min": 1, "max": 4, "layout": "block", "button_label": "Añadir card", "sub_fields": [
                            { "key": "field_inst_cdr_card_image", "label": "Imagen", "name": "image", "type": "image", "return_format": "array", "preview_size": "medium", "required": 1 },
                            { "key": "field_inst_cdr_card_title", "label": "Título", "name": "title", "type": "text", "required": 1 },
                            { "key": "field_inst_cdr_card_excerpt", "label": "Texto", "name": "excerpt", "type": "textarea", "rows": 3 },
                            { "key": "field_inst_cdr_card_link", "label": "Link", "name": "link", "type": "link", "return_format": "array" }
                        ]}
                    ]
                },
                "layout_inst_people_carousel": {
                    "key": "layout_inst_people_carousel",
                    "name": "people_carousel",
                    "label": "Carrusel de personas",
                    "display": "block",
                    "sub_fields": [
                        { "key": "field_inst_pc_anchor_label", "label": "Anchor label", "name": "anchor_label", "type": "text", "required": 1 },
                        { "key": "field_inst_pc_anchor_icon", "label": "Anchor icon", "name": "anchor_icon", "type": "image", "return_format": "array", "preview_size": "thumbnail" },
                        { "key": "field_inst_pc_title", "label": "Título", "name": "title", "type": "text", "required": 1 },
                        { "key": "field_inst_pc_subtitle", "label": "Subtítulo", "name": "subtitle", "type": "text" },
                        { "key": "field_inst_pc_personas", "label": "Personas", "name": "personas", "type": "repeater", "min": 1, "layout": "block", "button_label": "Añadir persona", "sub_fields": [
                            { "key": "field_inst_pc_persona_foto", "label": "Foto", "name": "foto", "type": "image", "return_format": "array", "preview_size": "thumbnail", "required": 1 },
                            { "key": "field_inst_pc_persona_nombre", "label": "Nombre", "name": "nombre", "type": "text", "required": 1 },
                            { "key": "field_inst_pc_persona_cargo", "label": "Cargo", "name": "cargo", "type": "text" },
                            { "key": "field_inst_pc_persona_email", "label": "Email (oculto)", "name": "email", "type": "email" }
                        ]}
                    ]
                },
                "layout_inst_back_link": {
                    "key": "layout_inst_back_link",
                    "name": "back_link",
                    "label": "Link de volver",
                    "display": "block",
                    "sub_fields": [
                        { "key": "field_inst_bl_anchor_label", "label": "Anchor label", "name": "anchor_label", "type": "text", "default_value": "Volver", "instructions": "Solo se usa si display_in_anchors está activo" },
                        { "key": "field_inst_bl_display_in_anchors", "label": "Mostrar en chips/rail", "name": "display_in_anchors", "type": "true_false", "ui": 1, "default_value": 0 },
                        { "key": "field_inst_bl_link_text", "label": "Texto del link", "name": "link_text", "type": "text", "instructions": "Usa {parent_title} para insertar el título de la página padre", "default_value": "Volver a {parent_title}" },
                        { "key": "field_inst_bl_target", "label": "Destino", "name": "target", "type": "page_link", "post_type": ["page"], "instructions": "Si vacío, usa la página padre automáticamente" }
                    ]
                }
            }
        }
    ],
    "location": [
        [
            { "param": "page_template", "operator": "==", "value": "templates/page-institucional.php" }
        ]
    ],
    "menu_order": 10,
    "position": "normal",
    "style": "default",
    "label_placement": "top",
    "instruction_placement": "label",
    "active": true,
    "modified": 1747756800
}
EOF

jq empty /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_page_institucional.json && echo "JSON válido"
```

Expected: `JSON válido`.

- [ ] **Step 2: Sync DB-direct UPSERT (igual patrón que F8)**

```bash
cat > /tmp/acf-sync-f9.php <<'EOF'
<?php
$json = json_decode( file_get_contents( '/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_page_institucional.json' ), true );
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
EOF

/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval-file /tmp/acf-sync-f9.php --path=/Applications/MAMP/htdocs/udp/cms 2>&1 | tail -3
```

Expected: `Success: id=NNN`.

- [ ] **Step 3: Verificación manual en WP-admin**

Ir a WP-admin → ACF → Field Groups. Debe aparecer "Página Institucional" con los 4 layouts (Texto+sidebar, Fila cards oscura, Carrusel de personas, Link de volver).

- [ ] **Step 4: Commit**

```bash
git add acf-json/group_page_institucional.json
git commit -m "feat(f9): ACF group page-institucional con 4 layouts"
```

---

## Task 3: Template raíz + skeleton del partial header

**Files:**
- Create: `templates/page-institucional.php`
- Create: `template-parts/institucional/header.php`
- Create: `src/scss/templates/_institucional.scss`
- Modify: `src/scss/main.scss`

- [ ] **Step 1: Crear `templates/page-institucional.php`**

```php
<?php
/**
 * Template Name: Institucional
 *
 * Página institucional con hero morado + breadcrumb + secciones flexibles
 * con navegación por anchors (chips bar sticky + rail vertical flotante)
 * y botón share flotante.
 *
 * @package Starter_Theme
 */

get_header();

$page_header = function_exists( 'get_field' ) ? get_field( 'page_header' ) : array();
$sections    = function_exists( 'get_field' ) ? get_field( 'sections' ) : array();
$anchors     = function_exists( 'udp_institucional_collect_anchors' ) ? udp_institucional_collect_anchors() : array();
$show_nav    = count( $anchors ) >= 2; // mínimo "Inicio" + 1 sección real
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-inst' ); ?>>

    <?php
    get_template_part(
        'template-parts/institucional/header',
        null,
        array(
            'show_breadcrumb' => ! empty( $page_header['show_breadcrumb'] ),
            'page_title'      => get_the_title(),
        )
    );

    if ( $show_nav ) {
        get_template_part( 'template-parts/institucional/nav-chips', null, array( 'anchors' => $anchors ) );
        get_template_part( 'template-parts/institucional/nav-rail',  null, array( 'anchors' => $anchors ) );
    }

    get_template_part( 'template-parts/sections/share-floating' );

    if ( is_array( $sections ) && ! empty( $sections ) ) :
        foreach ( $sections as $i => $section ) :
            $layout = $section['acf_fc_layout'] ?? '';
            $anchor = function_exists( 'udp_institucional_anchor_for_index' )
                ? udp_institucional_anchor_for_index( $anchors, $i )
                : null;

            $allowed = array( 'rich_text_sidebar', 'cards_dark_row', 'people_carousel', 'back_link' );
            if ( ! in_array( $layout, $allowed, true ) ) {
                continue;
            }

            $slug = 'layout-' . str_replace( '_', '-', $layout );
            get_template_part(
                'template-parts/institucional/' . $slug,
                null,
                array(
                    'data'   => $section,
                    'anchor' => $anchor,
                )
            );
        endforeach;
    endif;
    ?>

</article>

<?php
get_footer();
```

- [ ] **Step 2: Crear `template-parts/institucional/header.php`**

```php
<?php
/**
 * Institucional > Header (hero morado + breadcrumb + H1)
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$show_breadcrumb = isset( $args['show_breadcrumb'] ) ? (bool) $args['show_breadcrumb'] : true;
$page_title      = $args['page_title'] ?? get_the_title();
?>
<section id="section-inicio" class="udp-inst-hero" style="scroll-margin-top: var(--udp-anchor-offset, 168px);">
    <div class="udp-inst-hero__inner">
        <?php if ( $show_breadcrumb ) : ?>
            <div class="udp-inst-hero__breadcrumb">
                <?php get_template_part( 'template-parts/sections/breadcrumb' ); ?>
            </div>
        <?php endif; ?>

        <h1 class="udp-inst-hero__title"><?php echo esc_html( $page_title ); ?></h1>
    </div>
</section>
```

- [ ] **Step 3: Crear stub SCSS del template**

```bash
mkdir -p /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/templates 2>/dev/null

cat > /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/templates/_institucional.scss <<'EOF'
// =============================================================================
// Template: page-institucional
// =============================================================================
//
// Hero morado + breadcrumb + 4 layouts + chips + rail + share.
// BEM prefix: udp-inst-*

:root {
    --udp-anchor-offset: calc(var(--header-h, 84px) + 84px); // header + chips bar
}

.udp-inst {
    // Container general del template

    &-hero {
        background: $brand-blue;
        color: #fff;
        padding: $space-2xl $space-3xl;

        &__inner {
            max-width: 1440px;
            margin: 0 auto;
        }

        &__breadcrumb {
            margin-bottom: $space-md;
            color: rgba(255, 255, 255, 0.85);

            a { color: inherit; }
        }

        &__title {
            font-size: clamp(2.5rem, 5vw, 4.25rem);
            font-weight: 700;
            line-height: 1.1;
            margin: 0;
        }
    }
}
EOF
```

- [ ] **Step 4: Wire SCSS en main.scss**

Localizar la sección de `@import "templates/...";` en `src/scss/main.scss` y añadir:

```scss
@import "templates/institucional";
```

Verificar:

```bash
grep -n "templates/institucional" /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/main.scss
```

- [ ] **Step 5: Build + verificación manual**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -5
```

Expected: `✓ built in ...`.

En WP-admin crear página "Test Institucional", asignar template "Institucional", guardar y abrir en frontend. Debe verse:
- Hero morado full-width con breadcrumb y H1 grande "Test Institucional"
- Sin secciones aún (no hay chips/rail)

- [ ] **Step 6: Commit**

```bash
git add templates/page-institucional.php template-parts/institucional/header.php src/scss/templates/_institucional.scss src/scss/main.scss
git commit -m "feat(f9): template page-institucional + hero header"
```

---

## Task 4: Share floating button (PHP + SCSS + JS)

**Files:**
- Create: `template-parts/sections/share-floating.php`
- Create: `src/js/modules/share-floating.js`
- Modify: `src/js/main.js`
- Modify: `src/scss/templates/_institucional.scss`

- [ ] **Step 1: Crear el partial PHP**

```php
<?php
/**
 * Botón share flotante a la derecha.
 *
 * Usa Web Share API si está disponible; fallback dropdown con
 * copiar enlace, email, WhatsApp, LinkedIn, X y Facebook.
 *
 * Se oculta bajo 576px de ancho.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$share_url   = esc_url( get_permalink() );
$share_title = esc_attr( wp_strip_all_tags( get_the_title() ) );
?>
<aside class="udp-inst-share" data-udp-share data-share-url="<?php echo $share_url; ?>" data-share-title="<?php echo $share_title; ?>" aria-label="<?php esc_attr_e( 'Compartir esta página', 'starter-theme' ); ?>">
    <button type="button" class="udp-inst-share__trigger" aria-haspopup="menu" aria-expanded="false">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
            <path d="M15 7a2.5 2.5 0 100-5 2.5 2.5 0 000 5zM5 12.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5zM15 18a2.5 2.5 0 100-5 2.5 2.5 0 000 5zM7.15 11.25l5.7 3.5M12.85 5.25l-5.7 3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span class="udp-inst-share__label"><?php esc_html_e( 'Compartir', 'starter-theme' ); ?></span>
    </button>

    <ul class="udp-inst-share__menu" role="menu" hidden>
        <li role="none"><button type="button" role="menuitem" data-share-action="copy"><?php esc_html_e( 'Copiar enlace', 'starter-theme' ); ?></button></li>
        <li role="none"><a role="menuitem" data-share-action="email" href="#" target="_blank" rel="noopener"><?php esc_html_e( 'Email', 'starter-theme' ); ?></a></li>
        <li role="none"><a role="menuitem" data-share-action="whatsapp" href="#" target="_blank" rel="noopener"><?php esc_html_e( 'WhatsApp', 'starter-theme' ); ?></a></li>
        <li role="none"><a role="menuitem" data-share-action="linkedin" href="#" target="_blank" rel="noopener"><?php esc_html_e( 'LinkedIn', 'starter-theme' ); ?></a></li>
        <li role="none"><a role="menuitem" data-share-action="x" href="#" target="_blank" rel="noopener"><?php esc_html_e( 'X', 'starter-theme' ); ?></a></li>
        <li role="none"><a role="menuitem" data-share-action="facebook" href="#" target="_blank" rel="noopener"><?php esc_html_e( 'Facebook', 'starter-theme' ); ?></a></li>
    </ul>
</aside>
```

- [ ] **Step 2: Crear el módulo JS**

```js
/**
 * Módulo: Share floating button
 *
 * Si Web Share API está disponible, el click en el trigger la usa directamente.
 * Si no, abre un dropdown con 6 acciones (copiar, email, whatsapp, linkedin, x, facebook).
 */

import { qsa } from '@utils/dom';

function buildHref(action, url, title) {
    const u = encodeURIComponent(url);
    const t = encodeURIComponent(title);
    switch (action) {
        case 'email':    return `mailto:?subject=${t}&body=${u}`;
        case 'whatsapp': return `https://wa.me/?text=${t}%20${u}`;
        case 'linkedin': return `https://www.linkedin.com/sharing/share-offsite/?url=${u}`;
        case 'x':        return `https://x.com/intent/post?text=${t}&url=${u}`;
        case 'facebook': return `https://www.facebook.com/sharer/sharer.php?u=${u}`;
        default:         return '#';
    }
}

async function copyToClipboard(text, button) {
    try {
        await navigator.clipboard.writeText(text);
        const original = button.textContent;
        button.textContent = '¡Copiado!';
        setTimeout(() => { button.textContent = original; }, 1500);
    } catch (e) {
        // Fallback silencioso
        window.prompt('Copia el enlace:', text);
    }
}

export function initShareFloating() {
    const containers = qsa('[data-udp-share]');
    if (!containers.length) return;

    containers.forEach((container) => {
        const trigger = container.querySelector('.udp-inst-share__trigger');
        const menu    = container.querySelector('.udp-inst-share__menu');
        const url     = container.dataset.shareUrl;
        const title   = container.dataset.shareTitle || document.title;

        if (!trigger || !menu || !url) return;

        // Precalcular hrefs de los enlaces
        menu.querySelectorAll('[data-share-action]').forEach((el) => {
            const action = el.dataset.shareAction;
            if (action === 'copy') return;
            const href = buildHref(action, url, title);
            if (el.tagName === 'A') el.href = href;
        });

        // Web Share API path (nativo)
        const canNative = typeof navigator.share === 'function';

        trigger.addEventListener('click', async (e) => {
            e.stopPropagation();
            if (canNative) {
                try {
                    await navigator.share({ title, url });
                    return;
                } catch (err) {
                    if (err && err.name === 'AbortError') return;
                    // si falla por permisos, caemos al menú
                }
            }
            const open = !menu.hidden;
            menu.hidden = open;
            trigger.setAttribute('aria-expanded', open ? 'false' : 'true');
        });

        // Acción copiar
        const copyBtn = menu.querySelector('[data-share-action="copy"]');
        if (copyBtn) {
            copyBtn.addEventListener('click', () => copyToClipboard(url, copyBtn));
        }

        // Cerrar al click fuera
        document.addEventListener('click', (e) => {
            if (!menu.hidden && !container.contains(e.target)) {
                menu.hidden = true;
                trigger.setAttribute('aria-expanded', 'false');
            }
        });

        // Cerrar con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !menu.hidden) {
                menu.hidden = true;
                trigger.setAttribute('aria-expanded', 'false');
                trigger.focus();
            }
        });
    });
}
```

- [ ] **Step 3: Wire en main.js**

Localizar bloque de imports en `src/js/main.js` y añadir:

```js
import { initShareFloating } from '@modules/share-floating';
```

Y en el bloque de inits, añadir:

```js
initShareFloating();
```

- [ ] **Step 4: Añadir SCSS del share button**

Añadir al final de `src/scss/templates/_institucional.scss`:

```scss
.udp-inst-share {
    position: fixed;
    right: 0;
    top: 50vh;
    transform: translateY(-50%);
    z-index: 950;
    display: none;

    @include media-up(sm) { display: block; }

    &__trigger {
        background: $brand-blue;
        color: #fff;
        border: 0;
        border-radius: 28px 0 0 28px;
        padding: $space-md $space-sm;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: $space-xs;
        cursor: pointer;
        writing-mode: vertical-rl;
        font-size: 0.875rem;
        font-weight: 600;

        svg { writing-mode: horizontal-tb; }

        &:hover { background: darken($brand-blue, 10%); }
    }

    &__menu {
        position: absolute;
        right: 100%;
        top: 50%;
        transform: translateY(-50%);
        margin-right: $space-xs;
        background: #fff;
        border-radius: $space-xs;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        padding: $space-xs 0;
        list-style: none;
        min-width: 180px;

        a, button {
            display: block;
            width: 100%;
            padding: $space-xs $space-md;
            background: none;
            border: 0;
            text-align: left;
            color: $dark-1;
            text-decoration: none;
            font-size: 0.9375rem;
            cursor: pointer;

            &:hover { background: rgba($dark-1, 0.05); }
        }
    }
}
```

- [ ] **Step 5: Build + verificación manual**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -5
```

Abrir la página de test. En desktop debe aparecer la pildora vertical "Compartir" fija a la derecha. Click en Chrome desktop abre el dropdown con las 6 opciones; click en "Copiar enlace" copia y muestra "¡Copiado!" temporalmente. En Safari iOS debería abrir el share sheet nativo.

- [ ] **Step 6: Commit**

```bash
git add template-parts/sections/share-floating.php src/js/modules/share-floating.js src/js/main.js src/scss/templates/_institucional.scss
git commit -m "feat(f9): share floating button con Web Share API + fallback"
```

---

## Task 5: Layout A — rich_text_sidebar

**Files:**
- Create: `template-parts/institucional/layout-rich-text-sidebar.php`
- Modify: `src/scss/templates/_institucional.scss`

- [ ] **Step 1: Crear el partial**

```php
<?php
/**
 * Layout A — Texto + sidebar
 *
 * 3 columnas en ≥992px: título / WYSIWYG / sidebar cards
 * Stack vertical en <992px.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$data   = $args['data']   ?? array();
$anchor = $args['anchor'] ?? null;

$title         = $data['title']         ?? '';
$body          = $data['body']          ?? '';
$sidebar_cards = is_array( $data['sidebar_cards'] ?? null ) ? $data['sidebar_cards'] : array();

$id = $anchor['id'] ?? '';
?>
<section
    <?php if ( $id ) : ?>id="<?php echo esc_attr( $id ); ?>"<?php endif; ?>
    class="udp-inst-section udp-inst-rts"
    style="scroll-margin-top: var(--udp-anchor-offset, 168px);"
>
    <div class="udp-inst-rts__inner">
        <header class="udp-inst-rts__title-col">
            <h2 class="udp-inst-rts__title"><?php echo esc_html( $title ); ?></h2>
        </header>

        <div class="udp-inst-rts__body-col">
            <div class="udp-inst-rts__body"><?php echo wp_kses_post( $body ); ?></div>
        </div>

        <?php if ( ! empty( $sidebar_cards ) ) : ?>
            <aside class="udp-inst-rts__sidebar">
                <?php foreach ( $sidebar_cards as $card ) :
                    $c_title = $card['title'] ?? '';
                    $c_body  = $card['body']  ?? '';
                    $c_cta   = is_array( $card['cta'] ?? null ) ? $card['cta'] : array();
                    $c_url   = $c_cta['url']    ?? '';
                    $c_label = $c_cta['title']  ?? __( 'Conoce más', 'starter-theme' );
                    $c_tgt   = $c_cta['target'] ?? '';

                    if ( ! $c_url ) continue;
                ?>
                    <article class="udp-inst-rts__card">
                        <?php if ( $c_title ) : ?>
                            <h3 class="udp-inst-rts__card-title"><?php echo esc_html( $c_title ); ?></h3>
                        <?php endif; ?>
                        <?php if ( $c_body ) : ?>
                            <p class="udp-inst-rts__card-body"><?php echo esc_html( $c_body ); ?></p>
                        <?php endif; ?>
                        <a class="udp-inst-rts__card-cta" href="<?php echo esc_url( $c_url ); ?>"<?php echo $c_tgt ? ' target="' . esc_attr( $c_tgt ) . '" rel="noopener"' : ''; ?>>
                            <span><?php echo esc_html( $c_label ); ?></span>
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    </article>
                <?php endforeach; ?>
            </aside>
        <?php endif; ?>
    </div>
</section>
```

- [ ] **Step 2: Añadir SCSS del layout A**

Añadir al final de `src/scss/templates/_institucional.scss`:

```scss
.udp-inst-section {
    padding: $space-2xl 0;

    & + & { border-top: 1px solid rgba($dark-1, 0.1); }
}

.udp-inst-rts {
    &__inner {
        max-width: 1440px;
        margin: 0 auto;
        padding: 0 $space-3xl;
        display: grid;
        grid-template-columns: 1fr;
        gap: $space-xl;

        @include media-up(lg) {
            grid-template-columns: 318px 664px 318px;
            gap: $space-3xl;
            justify-content: space-between;
        }
    }

    &__title {
        font-size: clamp(1.75rem, 3vw, 2.5rem);
        font-weight: 700;
        line-height: 1.2;
        margin: 0;
    }

    &__body {
        color: $dark-1;
        line-height: 1.6;

        p { margin: 0 0 $space-md; }
        ul, ol { margin: 0 0 $space-md $space-md; }
    }

    &__sidebar {
        display: flex;
        flex-direction: column;
        gap: $space-md;
    }

    &__card {
        background: rgba($dark-1, 0.05);
        padding: $space-md;
        border-radius: $space-xs;

        &-title {
            font-size: 1.125rem;
            font-weight: 700;
            margin: 0 0 $space-xs;
        }

        &-body {
            font-size: 0.9375rem;
            color: $gray-medium;
            margin: 0 0 $space-md;
        }

        &-cta {
            display: inline-flex;
            align-items: center;
            gap: $space-xs;
            color: $brand-blue;
            font-weight: 600;
            text-decoration: none;

            &:hover {
                color: $brand-accent;
                svg { transform: translateX(4px); }
            }

            svg { transition: transform 0.2s ease; }
        }
    }
}
```

- [ ] **Step 3: Build + verificación**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -3
```

En WP-admin, en "Test Institucional", añadir una sección "Texto + sidebar":
- anchor_label: "Introducción"
- title: "Introducción"
- body: 3-4 párrafos de lorem
- añadir 2 sidebar_cards con title/body/cta

Frontend desktop: 3 columnas (título 318 / body 664 / sidebar 318). Mobile: una columna apilada en orden título → body → sidebar.

- [ ] **Step 4: Commit**

```bash
git add template-parts/institucional/layout-rich-text-sidebar.php src/scss/templates/_institucional.scss
git commit -m "feat(f9): layout rich_text_sidebar (A)"
```

---

## Task 6: Layout B — cards_dark_row

**Files:**
- Create: `template-parts/institucional/layout-cards-dark-row.php`
- Modify: `src/scss/templates/_institucional.scss`

- [ ] **Step 1: Crear el partial**

```php
<?php
/**
 * Layout B — Fila de cards sobre banda oscura
 *
 * Full-width con fondo oscuro. Título a la izquierda, cards en grid.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$data   = $args['data']   ?? array();
$anchor = $args['anchor'] ?? null;

$title = $data['title'] ?? '';
$cards = is_array( $data['cards'] ?? null ) ? $data['cards'] : array();

$id = $anchor['id'] ?? '';

if ( empty( $cards ) ) return;
?>
<section
    <?php if ( $id ) : ?>id="<?php echo esc_attr( $id ); ?>"<?php endif; ?>
    class="udp-inst-section udp-inst-dark"
    style="scroll-margin-top: var(--udp-anchor-offset, 168px);"
>
    <div class="udp-inst-dark__inner">
        <?php if ( $title ) : ?>
            <header class="udp-inst-dark__header">
                <h2 class="udp-inst-dark__title"><?php echo esc_html( $title ); ?></h2>
            </header>
        <?php endif; ?>

        <ul class="udp-inst-dark__cards">
            <?php foreach ( $cards as $card ) :
                $img     = is_array( $card['image'] ?? null ) ? $card['image'] : array();
                $c_title = $card['title']   ?? '';
                $c_exc   = $card['excerpt'] ?? '';
                $c_link  = is_array( $card['link'] ?? null ) ? $card['link'] : array();
                $c_url   = $c_link['url']    ?? '';
                $c_tgt   = $c_link['target'] ?? '';
            ?>
                <li class="udp-inst-dark__card">
                    <?php if ( ! empty( $img['url'] ) ) : ?>
                        <div class="udp-inst-dark__card-image">
                            <img src="<?php echo esc_url( $img['url'] ); ?>" alt="<?php echo esc_attr( $img['alt'] ?? '' ); ?>" loading="lazy">
                        </div>
                    <?php endif; ?>
                    <div class="udp-inst-dark__card-body">
                        <?php if ( $c_title ) : ?>
                            <?php if ( $c_url ) : ?>
                                <h3 class="udp-inst-dark__card-title">
                                    <a href="<?php echo esc_url( $c_url ); ?>"<?php echo $c_tgt ? ' target="' . esc_attr( $c_tgt ) . '" rel="noopener"' : ''; ?>>
                                        <?php echo esc_html( $c_title ); ?>
                                    </a>
                                </h3>
                            <?php else : ?>
                                <h3 class="udp-inst-dark__card-title"><?php echo esc_html( $c_title ); ?></h3>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if ( $c_exc ) : ?>
                            <p class="udp-inst-dark__card-excerpt"><?php echo esc_html( $c_exc ); ?></p>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
```

- [ ] **Step 2: Añadir SCSS del layout B**

Añadir al final de `src/scss/templates/_institucional.scss`:

```scss
.udp-inst-dark {
    background: $dark-1;
    color: #fff;

    &__inner {
        max-width: 1440px;
        margin: 0 auto;
        padding: $space-2xl $space-3xl;
        display: grid;
        grid-template-columns: 1fr;
        gap: $space-xl;

        @include media-up(lg) {
            grid-template-columns: 318px 1fr;
            gap: $space-3xl;
        }
    }

    &__title {
        font-size: clamp(2rem, 4vw, 3rem);
        font-weight: 700;
        line-height: 1.1;
        margin: 0;
    }

    &__cards {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        grid-template-columns: 1fr;
        gap: $space-md;

        @include media-up(md) { grid-template-columns: repeat(2, 1fr); }
        @include media-up(lg) { grid-template-columns: repeat(3, 1fr); }
    }

    &__card {
        background: rgba(255, 255, 255, 0.05);
        border-radius: $space-xs;
        overflow: hidden;
        display: flex;
        flex-direction: column;

        &-image {
            aspect-ratio: 16 / 10;
            background: $gray-medium;

            img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }
        }

        &-body { padding: $space-md; }

        &-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0 0 $space-xs;

            a { color: inherit; text-decoration: none; &:hover { color: $brand-accent; } }
        }

        &-excerpt {
            color: rgba(255, 255, 255, 0.75);
            font-size: 0.9375rem;
            margin: 0;
        }
    }
}
```

- [ ] **Step 3: Build + verificación**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -3
```

En la página de test, añadir una sección "Fila cards oscura" con anchor_label "Sistema de Gobierno", title "Sistema de Gobierno Universitario" y 3 cards (image+title+excerpt+link). Frontend: banda full-width con fondo oscuro, título a la izquierda en desktop, 3 cards en grid.

- [ ] **Step 4: Commit**

```bash
git add template-parts/institucional/layout-cards-dark-row.php src/scss/templates/_institucional.scss
git commit -m "feat(f9): layout cards_dark_row (B)"
```

---

## Task 7: Layout C — people_carousel (Swiper)

**Files:**
- Create: `template-parts/institucional/layout-people-carousel.php`
- Create: `src/js/modules/institucional-people.js`
- Modify: `src/js/main.js`
- Modify: `src/scss/templates/_institucional.scss`

- [ ] **Step 1: Crear el partial PHP**

```php
<?php
/**
 * Layout C — Carrusel de personas (Swiper)
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$data   = $args['data']   ?? array();
$anchor = $args['anchor'] ?? null;

$title    = $data['title']    ?? '';
$subtitle = $data['subtitle'] ?? '';
$personas = is_array( $data['personas'] ?? null ) ? $data['personas'] : array();

$id = $anchor['id'] ?? '';

if ( empty( $personas ) ) return;
?>
<section
    <?php if ( $id ) : ?>id="<?php echo esc_attr( $id ); ?>"<?php endif; ?>
    class="udp-inst-section udp-inst-people"
    style="scroll-margin-top: var(--udp-anchor-offset, 168px);"
>
    <div class="udp-inst-people__inner">
        <?php if ( $title ) : ?>
            <header class="udp-inst-people__header">
                <h2 class="udp-inst-people__title"><?php echo esc_html( $title ); ?></h2>
                <?php if ( $subtitle ) : ?>
                    <p class="udp-inst-people__subtitle"><?php echo esc_html( $subtitle ); ?></p>
                <?php endif; ?>
            </header>
        <?php endif; ?>

        <div class="udp-inst-people__carousel">
            <div class="swiper">
                <ul class="swiper-wrapper">
                    <?php foreach ( $personas as $persona ) :
                        $foto   = is_array( $persona['foto'] ?? null ) ? $persona['foto'] : array();
                        $nombre = $persona['nombre'] ?? '';
                        $cargo  = $persona['cargo']  ?? '';
                        if ( ! $nombre ) continue;
                    ?>
                        <li class="swiper-slide udp-inst-people__card">
                            <?php if ( ! empty( $foto['url'] ) ) : ?>
                                <div class="udp-inst-people__photo">
                                    <img src="<?php echo esc_url( $foto['url'] ); ?>" alt="<?php echo esc_attr( $foto['alt'] ?? $nombre ); ?>" loading="lazy">
                                </div>
                            <?php endif; ?>
                            <div class="udp-inst-people__info">
                                <p class="udp-inst-people__name"><?php echo esc_html( $nombre ); ?></p>
                                <?php if ( $cargo ) : ?>
                                    <p class="udp-inst-people__role"><?php echo esc_html( $cargo ); ?></p>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</section>
```

- [ ] **Step 2: Crear el módulo JS (Swiper lazy)**

```js
/**
 * Módulo: Institucional > People Carousel
 *
 * Swiper lazy-loaded. Solo init si hay .udp-inst-people en el DOM.
 */

import { qsa } from '@utils/dom';

export async function initInstitucionalPeople() {
    const sections = qsa('.udp-inst-people .swiper');
    if (!sections.length) return;

    const { default: Swiper } = await import('swiper');
    const { Navigation, FreeMode, A11y } = await import('swiper/modules');
    await import('swiper/css');

    sections.forEach((swiperEl) => {
        new Swiper(swiperEl, {
            modules: [Navigation, FreeMode, A11y],
            slidesPerView: 'auto',
            spaceBetween: 24,
            slidesOffsetBefore: 40,
            slidesOffsetAfter: 40,
            freeMode: { enabled: true, momentum: true },
            grabCursor: true,
            a11y: { enabled: true },
            breakpoints: {
                0:   { spaceBetween: 16, slidesOffsetBefore: 16, slidesOffsetAfter: 16 },
                768: { spaceBetween: 24, slidesOffsetBefore: 40, slidesOffsetAfter: 40 },
            },
        });
    });
}
```

- [ ] **Step 3: Wire en main.js**

Añadir import y init:

```js
import { initInstitucionalPeople } from '@modules/institucional-people';
// ...
initInstitucionalPeople();
```

- [ ] **Step 4: Añadir SCSS del layout C**

Añadir al final de `src/scss/templates/_institucional.scss`:

```scss
.udp-inst-people {
    &__inner {
        max-width: 1440px;
        margin: 0 auto;
        padding: 0 0 0 0;
    }

    &__header {
        padding: 0 $space-3xl;
        margin-bottom: $space-xl;

        @include media-down(md) { padding: 0 $space-md; }
    }

    &__title {
        font-size: clamp(1.75rem, 3vw, 2.5rem);
        font-weight: 700;
        margin: 0 0 $space-xs;
    }

    &__subtitle {
        font-size: 1.125rem;
        color: $gray-medium;
        margin: 0;
    }

    &__carousel { overflow: hidden; }

    &__card {
        width: 289px;
        background: transparent;
        list-style: none;
    }

    &__photo {
        aspect-ratio: 1 / 1;
        background: rgba($dark-1, 0.1);
        margin-bottom: $space-md;
        overflow: hidden;
        border-radius: $space-xs;

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
    }

    &__name {
        font-size: 1.125rem;
        font-weight: 700;
        margin: 0 0 $space-2xs;
        color: $dark-1;
    }

    &__role {
        font-size: 0.9375rem;
        color: $gray-medium;
        margin: 0;
    }
}
```

- [ ] **Step 5: Build + verificación**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -3
```

En la página de test, añadir una sección "Carrusel de personas" con title "Integrantes por cargo" y 5–6 personas con foto cuadrada + nombre + cargo. Verificar swiper horizontal, drag funciona, no hay errores en consola.

- [ ] **Step 6: Commit**

```bash
git add template-parts/institucional/layout-people-carousel.php src/js/modules/institucional-people.js src/js/main.js src/scss/templates/_institucional.scss
git commit -m "feat(f9): layout people_carousel (C) con Swiper"
```

---

## Task 8: Layout D — back_link

**Files:**
- Create: `template-parts/institucional/layout-back-link.php`
- Modify: `src/scss/templates/_institucional.scss`

- [ ] **Step 1: Crear el partial**

```php
<?php
/**
 * Layout D — Link de volver
 *
 * Si target está vacío, usa wp_get_post_parent_id().
 * Si no hay padre, link a home.
 * {parent_title} en link_text se reemplaza por el título efectivo.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$data   = $args['data']   ?? array();
$anchor = $args['anchor'] ?? null;

$target_url = trim( (string) ( $data['target'] ?? '' ) );
$link_text  = trim( (string) ( $data['link_text'] ?? 'Volver' ) );

$parent_id    = (int) wp_get_post_parent_id( get_the_ID() );
$parent_title = $parent_id ? get_the_title( $parent_id ) : __( 'Inicio', 'starter-theme' );

if ( $target_url === '' ) {
    $target_url = $parent_id ? get_permalink( $parent_id ) : home_url( '/' );
}

$link_text = str_replace( '{parent_title}', $parent_title, $link_text );
if ( $link_text === '' ) $link_text = __( 'Volver', 'starter-theme' );

$id = $anchor['id'] ?? '';
?>
<section
    <?php if ( $id ) : ?>id="<?php echo esc_attr( $id ); ?>"<?php endif; ?>
    class="udp-inst-section udp-inst-back"
    style="scroll-margin-top: var(--udp-anchor-offset, 168px);"
>
    <div class="udp-inst-back__inner">
        <a class="udp-inst-back__link" href="<?php echo esc_url( $target_url ); ?>">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <path d="M13 8H3M7 4L3 8l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span><?php echo esc_html( $link_text ); ?></span>
        </a>
    </div>
</section>
```

- [ ] **Step 2: Añadir SCSS**

```scss
.udp-inst-back {
    &__inner {
        max-width: 1440px;
        margin: 0 auto;
        padding: $space-xl $space-3xl;

        @include media-down(md) { padding: $space-xl $space-md; }
    }

    &__link {
        display: inline-flex;
        align-items: center;
        gap: $space-xs;
        color: $brand-blue;
        font-weight: 600;
        text-decoration: none;
        font-size: 1.125rem;

        &:hover {
            color: $brand-accent;
            svg { transform: translateX(-4px); }
        }

        svg { transition: transform 0.2s ease; }
    }
}
```

- [ ] **Step 3: Build + verificación**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -3
```

Convertir la página de test en hija de otra página (Page Attributes → Parent). Añadir una sección "Link de volver" sin tocar `target` ni `link_text`. Frontend: aparece "Volver a {nombre del padre}" con flecha izquierda. Click navega al padre.

Probar también con `display_in_anchors=true`: la sección aparece como chip "Volver".

- [ ] **Step 4: Commit**

```bash
git add template-parts/institucional/layout-back-link.php src/scss/templates/_institucional.scss
git commit -m "feat(f9): layout back_link (D)"
```

---

## Task 9: Chips bar sticky

**Files:**
- Create: `template-parts/institucional/nav-chips.php`
- Modify: `src/scss/templates/_institucional.scss`

- [ ] **Step 1: Crear el partial**

```php
<?php
/**
 * Institucional > Chips bar sticky (todos los breakpoints).
 *
 * Recibe $args['anchors'] (output de udp_institucional_collect_anchors).
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$anchors = is_array( $args['anchors'] ?? null ) ? $args['anchors'] : array();
if ( count( $anchors ) < 2 ) return;
?>
<nav class="udp-inst-chips" aria-label="<?php esc_attr_e( 'Navegación de sección', 'starter-theme' ); ?>">
    <div class="udp-inst-chips__inner">
        <ul class="udp-inst-chips__list" role="list">
            <?php foreach ( $anchors as $a ) : ?>
                <li class="udp-inst-chips__item">
                    <a class="udp-inst-chips__link" href="#<?php echo esc_attr( $a['id'] ); ?>" data-udp-anchor="<?php echo esc_attr( $a['id'] ); ?>">
                        <?php echo esc_html( $a['label'] ); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>
```

- [ ] **Step 2: Añadir SCSS del chips bar**

```scss
.udp-inst-chips {
    position: sticky;
    top: var(--header-h, 84px);
    z-index: 900;
    background: #fff;
    border-bottom: 1px solid rgba($dark-1, 0.1);

    &__inner {
        max-width: 1440px;
        margin: 0 auto;
        padding: 0 $space-3xl;

        @include media-down(md) { padding: 0 $space-md; }
    }

    &__list {
        list-style: none;
        margin: 0;
        padding: $space-md 0;
        display: flex;
        flex-wrap: nowrap;
        gap: $space-xs;
        overflow-x: auto;
        scroll-snap-type: x mandatory;

        // Hide scrollbar visualmente
        scrollbar-width: none;
        &::-webkit-scrollbar { display: none; }
    }

    &__item { flex: 0 0 auto; scroll-snap-align: start; }

    &__link {
        display: inline-block;
        padding: $space-xs $space-md;
        background: rgba($dark-1, 0.05);
        color: $dark-1;
        border-radius: 999px;
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 500;
        white-space: nowrap;
        transition: background 0.15s ease, color 0.15s ease;

        &:hover { background: rgba($dark-1, 0.1); }

        &.is-active {
            background: $brand-blue;
            color: #fff;
        }
    }
}
```

- [ ] **Step 3: Build + verificación**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -3
```

Recargar la página de test (debe tener varias secciones). Justo bajo el hero aparece la chips bar con "Inicio" + un chip por cada anchor_label. Click en cada chip lleva a la sección. Al hacer scroll, la chips bar queda sticky bajo el header.

- [ ] **Step 4: Commit**

```bash
git add template-parts/institucional/nav-chips.php src/scss/templates/_institucional.scss
git commit -m "feat(f9): chips bar sticky con anchors"
```

---

## Task 10: Rail vertical flotante

**Files:**
- Create: `template-parts/institucional/nav-rail.php`
- Modify: `src/scss/templates/_institucional.scss`

- [ ] **Step 1: Crear el partial**

```php
<?php
/**
 * Institucional > Rail vertical flotante (desktop ≥992px).
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$anchors = is_array( $args['anchors'] ?? null ) ? $args['anchors'] : array();
if ( count( $anchors ) < 2 ) return;
?>
<nav class="udp-inst-rail" aria-label="<?php esc_attr_e( 'Navegación rápida por sección', 'starter-theme' ); ?>">
    <ul class="udp-inst-rail__list" role="list">
        <?php foreach ( $anchors as $a ) :
            $icon = is_array( $a['icon'] ) ? $a['icon'] : null;
            $aria = sprintf( __( 'Ir a %s', 'starter-theme' ), $a['label'] );
        ?>
            <li class="udp-inst-rail__item">
                <a
                    class="udp-inst-rail__link"
                    href="#<?php echo esc_attr( $a['id'] ); ?>"
                    data-udp-anchor="<?php echo esc_attr( $a['id'] ); ?>"
                    aria-label="<?php echo esc_attr( $aria ); ?>"
                    title="<?php echo esc_attr( $a['label'] ); ?>"
                >
                    <?php if ( $icon && ! empty( $icon['url'] ) ) : ?>
                        <img src="<?php echo esc_url( $icon['url'] ); ?>" alt="" width="32" height="32" loading="lazy">
                    <?php else : ?>
                        <span class="udp-inst-rail__num"><?php echo (int) $a['order']; ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
```

- [ ] **Step 2: Añadir SCSS del rail**

```scss
.udp-inst-rail {
    display: none;

    @include media-up(lg) {
        display: block;
        position: fixed;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        z-index: 940;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(6px);
        border-radius: 0 $space-xs $space-xs 0;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        padding: $space-xs;
    }

    &__list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: $space-2xs;
    }

    &__link {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 60px;
        height: 60px;
        border-radius: $space-xs;
        background: rgba($dark-1, 0.05);
        color: $dark-1;
        text-decoration: none;
        transition: background 0.15s ease, color 0.15s ease;

        img { display: block; }

        &:hover { background: rgba($dark-1, 0.1); }

        &.is-active {
            background: $brand-blue;
            color: #fff;
        }
    }

    &__num {
        font-size: 1.125rem;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
    }
}
```

- [ ] **Step 3: Build + verificación**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -3
```

Frontend desktop ≥992px: aparece el rail vertical pegado a la izquierda centrado verticalmente. Tablet/mobile: no se ve. Click en cada botón salta al anchor.

Probar también: subir un anchor_icon (PNG/SVG) en una sección desde WP-admin; en el rail aparece la imagen; sin icono aparece el número.

- [ ] **Step 4: Commit**

```bash
git add template-parts/institucional/nav-rail.php src/scss/templates/_institucional.scss
git commit -m "feat(f9): rail vertical flotante desktop"
```

---

## Task 11: Scrollspy JS

**Files:**
- Create: `src/js/modules/anchor-scrollspy.js`
- Modify: `src/js/main.js`

- [ ] **Step 1: Crear el módulo**

```js
/**
 * Módulo: Anchor Scrollspy (institucional)
 *
 * Detecta la sección activa con IntersectionObserver y aplica `.is-active`
 * al chip + rail-button correspondientes. Smooth scroll en clicks
 * (respeta prefers-reduced-motion). Pausa el spy 600ms tras un click
 * manual para evitar saltos.
 */

import { qs, qsa } from '@utils/dom';

export function initAnchorScrollspy() {
    const sections = qsa('.udp-inst [id^="section-"]');
    const chipsLinks = qsa('.udp-inst-chips__link');
    const railLinks  = qsa('.udp-inst-rail__link');
    const allLinks   = [...chipsLinks, ...railLinks];

    if (!sections.length || !allLinks.length) return;

    let spyPaused = false;
    let pauseTimer = null;

    const setActive = (anchorId) => {
        allLinks.forEach((link) => {
            const isMatch = link.dataset.udpAnchor === anchorId;
            link.classList.toggle('is-active', isMatch);
            if (isMatch) {
                link.setAttribute('aria-current', 'location');
            } else {
                link.removeAttribute('aria-current');
            }
        });
    };

    // IntersectionObserver: la sección activa es la que cruza el tercio superior
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(
            (entries) => {
                if (spyPaused) return;
                // Filtrar las intersecciones que están "tocando" la línea activa
                const visible = entries.filter((e) => e.isIntersecting);
                if (!visible.length) return;
                // La más cercana al top
                visible.sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);
                setActive(visible[0].target.id);
            },
            {
                rootMargin: '-30% 0px -60% 0px',
                threshold: 0,
            }
        );

        sections.forEach((s) => observer.observe(s));
    }

    // Smooth scroll en clicks + pausa del spy
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    allLinks.forEach((link) => {
        link.addEventListener('click', (e) => {
            const id = link.dataset.udpAnchor;
            const target = id ? document.getElementById(id) : null;
            if (!target) return;

            e.preventDefault();

            spyPaused = true;
            if (pauseTimer) clearTimeout(pauseTimer);
            pauseTimer = setTimeout(() => { spyPaused = false; }, 600);

            setActive(id);

            target.scrollIntoView({
                behavior: reduceMotion ? 'auto' : 'smooth',
                block: 'start',
            });

            // Actualizar el hash sin saltar (history.pushState)
            if (history.pushState) {
                history.pushState(null, '', `#${id}`);
            }
        });
    });
}
```

- [ ] **Step 2: Wire en main.js**

Añadir import y init:

```js
import { initAnchorScrollspy } from '@modules/anchor-scrollspy';
// ...
initAnchorScrollspy();
```

- [ ] **Step 3: Build + verificación**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -3
```

Recargar página de test con varias secciones. Al hacer scroll, el chip + rail-button de la sección visible se ponen morados (`is-active`). Click en un chip lleva a la sección sin que "salte" a otra mientras hace el smooth scroll.

- [ ] **Step 4: Commit**

```bash
git add src/js/modules/anchor-scrollspy.js src/js/main.js
git commit -m "feat(f9): scrollspy IntersectionObserver chips + rail"
```

---

## Task 12: QA pre-merge + MEMORY.md

**Files:**
- Modify: `MEMORY.md`

- [ ] **Step 1: Checklist manual de QA (spec §12)**

Marcar cada uno en el contexto de la página de test:

- [ ] Crear página con 5 secciones (una de cada layout + dos rich_text_sidebar), publicar
- [ ] Asignar template "Institucional"
- [ ] Desktop ≥1440: chips visibles, rail visible, share visible, click en anchor scroll funciona, scrollspy activa el item correcto
- [ ] Tablet 768–991: rail oculto, chips visibles
- [ ] Mobile <768: chips en carrusel scroll-x, share oculto si <576
- [ ] Lighthouse ≥90 performance, ≥95 a11y (DevTools Lighthouse panel)
- [ ] Share en Chrome desktop (fallback dropdown) y Safari mobile (Web Share API nativo)
- [ ] `display_in_anchors=false` en back_link → desaparece de chips/rail pero sigue visible
- [ ] Página con 0 secciones no rompe
- [ ] ACF JSON se regenera en `acf-json/` al editar campos

Si algún punto falla, abrir una iteración correctiva antes de añadir entrada a MEMORY.md.

- [ ] **Step 2: Añadir entrada a MEMORY.md**

Añadir al final del archivo:

```markdown
### 2026-05-20 — F9 template page-institucional
- Nuevo template `templates/page-institucional.php` asignable desde WP-admin
- ACF flexible content con 4 layouts: `rich_text_sidebar`, `cards_dark_row`, `people_carousel`, `back_link`
- Chips bar sticky + rail vertical flotante (≥992px) auto-derivados de las secciones; scrollspy con IntersectionObserver
- Botón share flotante con Web Share API + fallback a 6 destinos (copiar, email, whatsapp, linkedin, x, facebook)
- Helper PHP `udp_institucional_collect_anchors()` en `inc/udp-institucional.php`
- BEM prefix `udp-inst-*`; custom property `--udp-anchor-offset`
```

- [ ] **Step 3: Commit final**

```bash
git add MEMORY.md
git commit -m "chore(f9): MEMORY.md — template page-institucional"
```

---

## Self-review checklist (cubierto)

- ✅ Spec §1 objetivo → Tasks 3–11 implementan los 4 layouts + chrome + chips/rail/share
- ✅ Spec §4 inventario → tareas crean todos los archivos listados
- ✅ Spec §5 ACF schema → Task 2 crea el JSON completo con los 4 layouts
- ✅ Spec §6 anchor navigation → Tasks 1 (helper), 9 (chips), 10 (rail), 11 (scrollspy)
- ✅ Spec §7 CSS BEM `udp-inst-*` + `--udp-anchor-offset` → Tasks 3, 5, 6, 7, 8, 9, 10
- ✅ Spec §8 share button con 6 destinos → Task 4
- ✅ Spec §9 data flow con get_template_part + args → Task 3
- ✅ Spec §10 edge cases (sin secciones, 1 sola sección, etc.) → Tasks 3 (skip render), 9/10 (early return si <2 anchors)
- ✅ Spec §11 a11y (aria-label, aria-current, role="list") → presentes en Tasks 9, 10, 11
- ✅ Spec §12 QA pre-merge → Task 12
