# F9 — `page-institucional` template — Design Spec

**Status:** Draft, pending implementation plan.
**Phase:** F9 (siguiente fase tras F8 mega-menú).
**Predecesores:** F3 (`page-section-landing`), F7c (`block_people_list`), F4a (`block_card_grid`), F8 (mega-menú — comparte el rail/sticky pattern del header).

---

## 1. Objetivo

Construir un template `page-institucional` asignable desde WP-admin que sirva páginas de contenido institucional como "Forma de Gobierno" y "Consejo Académico" (Universidad → Gobernanza y reglamentos). El template:

- Combina un hero morado con breadcrumb + H1 (sin descripción WYSIWYG, a diferencia de `page-section-landing`)
- Permite componer N secciones de contenido vía ACF flexible content con 4 tipos de layout
- Renderiza dos navegaciones por anchors auto-derivadas de las secciones (chips bar sticky arriba + rail vertical flotante a la izquierda)
- Añade un botón flotante de "compartir" a la derecha

**In scope:**
- 4 layouts del flexible content (A/B/C/D — ver §5)
- Chips bar sticky + rail vertical flotante (desktop) con scrollspy
- Botón share flotante (Web Share API + fallback copiar enlace)
- Hero morado con breadcrumb auto

**Out of scope:**
- CPT nuevo para "persona" (el layout C reusa el repeater inline de `block_people_list`)
- Buscador de secciones / filtros
- Soporte de bloques mixtos (los layouts B y C no se pueden usar fuera de este template — son reuso *de markup* via partials compartidos, no bloques globales)
- Migración de contenido existente desde el sitio actual

---

## 2. Referencias

- Figma "Forma de Gobierno": `4QlgGMlzNR9Ye344bAFuye`, nodeId `3706:20200`
- Figma "Consejo Académico": `4QlgGMlzNR9Ye344bAFuye`, nodeId `3722:43026`
- Spec maestro: `docs/superpowers/specs/2026-04-27-migracion-udp-portable-a-starter-theme-design.md`
- Plan F3 (referencia para template + ACF JSON + SCSS): `docs/superpowers/plans/2026-04-28-f3-section-landing-template.md`
- Block reutilizado (people_list): `template-parts/blocks/block-block_people_list.php`
- Block reutilizado (card_grid): `template-parts/blocks/parts/card-noticia.php`

---

## 3. Decisiones tomadas en brainstorming (2026-05-20)

| Decisión | Elegido |
|---|---|
| Arquitectura | Flexible content con layouts dedicados |
| Navegación anchors | Chips sticky (todos los breakpoints) + rail vertical (≥992px) |
| Reuso de cards/carruseles | Reusar bloques existentes vía partials compartidos |
| Naming | `page-institucional` (Template Name: "Institucional") |

---

## 4. Inventario de archivos

**Crear:**

- `templates/page-institucional.php` — template raíz con Template Name
- `template-parts/institucional/header.php` — hero morado + breadcrumb + H1
- `template-parts/institucional/nav-chips.php` — chips bar sticky (todos los breakpoints)
- `template-parts/institucional/nav-rail.php` — rail vertical fijo (desktop ≥992px)
- `template-parts/institucional/layout-rich-text-sidebar.php` — layout A
- `template-parts/institucional/layout-cards-dark-row.php` — layout B
- `template-parts/institucional/layout-people-carousel.php` — layout C
- `template-parts/institucional/layout-back-link.php` — layout D
- `template-parts/sections/share-floating.php` — botón share flotante (reutilizable fuera del template también)
- `inc/udp-institucional.php` — helpers: `udp_institucional_collect_anchors()`, `udp_institucional_anchor_id()`
- `acf-json/group_page_institucional.json` — definición ACF del grupo
- `src/scss/templates/_institucional.scss` — todos los estilos del template + partials
- `src/js/modules/anchor-scrollspy.js` — scrollspy IntersectionObserver

**Modificar:**

- `functions.php` — `require_once STARTER_BS5_DIR . 'inc/udp-institucional.php';`
- `src/js/main.js` — `import './modules/anchor-scrollspy.js';`
- `src/scss/main.scss` — `@import "templates/institucional";`

**No tocar:**

- `templates/page-section-landing.php` — comparte sólo el color/typography del hero, no la estructura
- `template-parts/blocks/block-block_people_list.php` — se reusa su markup de card via partial compartido (sin modificar el bloque)
- Mega-menú, header, footer

---

## 5. ACF schema — `group_page_institucional`

**Location rule:** `page_template == templates/page-institucional.php`

**Field keys:** prefijo `field_inst_*` para todas las sub-fields.

### 5.1 Page header

```
page_header (group, field_inst_header)
  └─ show_breadcrumb (bool, default true)
```

> El H1 viene de `get_the_title()`, no es un campo.

### 5.2 Sections (flexible content)

```
sections (flexible_content, field_inst_sections, opcional, sin min)
```

**Layout A — `rich_text_sidebar`** (el más común, ~80% de las secciones)

```
├─ anchor_label (text, required, instructions: "Label corto que aparece en chips y como tooltip del rail")
├─ anchor_icon (image, opcional, return: array, preview: thumbnail, instructions: "SVG ideal 32×32. Si vacío usa número de orden")
├─ title (text, required)              — H2 columna izquierda
├─ body (wysiwyg, required, toolbar: full, media_upload: 0)
└─ sidebar_cards (repeater, opcional, min 0, max 6)
    ├─ title (text, required)
    ├─ body (textarea, opcional, rows 3)
    └─ cta (link, required, return: array)
```

**Layout B — `cards_dark_row`** (banda oscura full-width con 3 cards)

```
├─ anchor_label (text, required)
├─ anchor_icon (image, opcional)
├─ title (text, required)
└─ cards (repeater, min 1, max 4)
    ├─ image (image, required, return: array, preview: medium)
    ├─ title (text, required)
    ├─ excerpt (textarea, opcional, rows 3)
    └─ link (link, opcional, return: array)
```

**Layout C — `people_carousel`** (carrusel horizontal de personas con Swiper)

```
├─ anchor_label (text, required)
├─ anchor_icon (image, opcional)
├─ title (text, required)
├─ subtitle (text, opcional)
└─ personas (repeater, min 1)
    ├─ foto (image, required, return: array, preview: thumbnail)
    ├─ nombre (text, required)
    ├─ cargo (text, opcional)
    └─ email (email, opcional, hidden by default — no se renderiza en este layout)
```

**Layout D — `back_link`** (CTA de retorno a página padre o página configurada)

```
├─ anchor_label (text, default "Volver", instructions: "No aparece en chips bar si display_in_anchors=false")
├─ display_in_anchors (bool, default false) — controla si esta sección aparece en chips/rail
├─ link_text (text, default "Volver a {parent_title}", instructions: "{parent_title} se reemplaza por el título del padre")
└─ target (page_link, opcional, default = parent — si vacío usa wp_get_post_parent_id)
```

---

## 6. Anchor navigation — derivación y comportamiento

### 6.1 Recolección

`udp_institucional_collect_anchors()` recorre `get_field('sections')` y devuelve array ordenado:

```php
[
  [
    'id'         => 'section-introduccion',   // slug(anchor_label) con prefijo 'section-'
    'label'      => 'Introducción',
    'icon'       => [...] | null,             // ACF image array
    'order'      => 1,                        // 1-indexed
    'layout_key' => 'rich_text_sidebar',
  ],
  ...
]
```

**Reglas:**

- Cada layout que tenga `anchor_label` no vacío produce una entrada
- Layout D solo aparece si `display_in_anchors=true`
- `id` = `'section-' . sanitize_title($anchor_label)`; colisiones se resuelven con `-2`, `-3`, etc. (helper interno)
- Se añade automáticamente al inicio un anchor "Inicio" con `id=section-inicio` y `icon=null` que ancla al top del hero

### 6.2 Render del chrome

- `template-parts/institucional/header.php` añade `id="section-inicio"` al hero
- `template-parts/institucional/nav-chips.php` recibe el array y renderiza `<ul role="tablist">` con `<a href="#section-x">label</a>`
- `template-parts/institucional/nav-rail.php` renderiza `<nav class="udp-inst-rail">` con `<a href="#section-x" aria-label="...">{icon|order}</a>`
- Cada layout en su partial añade `id="<?php echo esc_attr($anchor['id']); ?>"` al `<section>` wrapper, y `style="scroll-margin-top: var(--udp-anchor-offset);"`

### 6.3 JS — scrollspy

`src/js/modules/anchor-scrollspy.js`:

- Inicializa solo si existe `.udp-inst-chips` o `.udp-inst-rail` en el DOM
- `IntersectionObserver` con `rootMargin: '-30% 0px -60% 0px'` para detectar la sección "activa" cuando su top cruza el tercio superior del viewport
- Añade `.is-active` al `<a>` correspondiente en chips Y rail simultáneamente
- Smooth scroll en clicks: `behavior: 'smooth'`, respeta `prefers-reduced-motion`
- No re-implementar history.pushState (browser default basta para el hash)

---

## 7. CSS — `_institucional.scss`

Prefijo BEM: `udp-inst-*`. Estructura:

```scss
.udp-inst {
  &-hero { /* morado, breadcrumb claro, H1 grande */ }
  &-chips { /* sticky top, scroll-snap-x en mobile */ }
  &-rail { /* fixed left, only ≥992px */ }
  &-share { /* fixed right, vertical pill */ }
  &-section { /* container general de cada layout */ }
  &-rts { /* layout A: grid 3-col en ≥992px */ }
  &-dark { /* layout B: full-width dark band */ }
  &-people { /* layout C: swiper */ }
  &-back { /* layout D: link estilizado */ }
}
```

**Variables del DS reusadas:** `$brand-purple`, `$brand-lila`, `$gray-100`, `$gray-900`, `$font-size-h1`, etc. (no se introducen nuevos tokens).

**Custom property nueva:** `--udp-anchor-offset` (definido en `:root` del template wrapper) = `var(--header-h, 84px) + 84px` (header + chips bar height). Usado por `scroll-margin-top` y por el sticky `top` de la chips bar.

**Breakpoints:**
- Mobile (<768): rail oculto, chips en carrusel scroll-x, layout A en una columna (título → body → cards apiladas), layout B en una columna, layout C swiper a 1.2 slides visibles
- Tablet (≥768): mismo que mobile pero layout C a 2.2 slides
- Desktop (≥992): rail visible, layout A en 3 columnas como el Figma, layout B en 3 columnas, layout C a 3-4 slides

---

## 8. Botón share flotante

`template-parts/sections/share-floating.php` — partial reutilizable, no exclusivo del template:

- Pildora vertical fija a la derecha (`position: fixed; right: 0; top: 50vh`)
- Botón "Compartir" que abre Web Share API si está disponible (`navigator.share`)
- Fallback: dropdown con 6 acciones — copiar enlace, email (`mailto:`), WhatsApp (`wa.me`), LinkedIn (`linkedin.com/sharing/share-offsite`), X (`x.com/intent/post`), Facebook (`facebook.com/sharer/sharer.php`)
- Texto = título de la página + URL canónica
- Solo se renderiza en `>=576px` (en mobile ocupa demasiado espacio)

JS necesario: handler en `anchor-scrollspy.js` (o módulo aparte si crece) que pega listeners al botón.

---

## 9. Data flow

```
page-institucional.php
  ├─ get_header()
  ├─ $anchors = udp_institucional_collect_anchors()
  ├─ include header.php          → recibe ['breadcrumb', 'subtitulo']
  ├─ include nav-chips.php       → recibe ['anchors']
  ├─ include nav-rail.php        → recibe ['anchors']
  ├─ include share-floating.php
  ├─ foreach (get_field('sections') as $i => $section):
  │     $layout = $section['acf_fc_layout']
  │     $anchor = $anchors[matching by order]
  │     include "layout-{$layout}.php"  → recibe ['data' => $section, 'anchor' => $anchor]
  └─ get_footer()
```

Cada partial está aislado: recibe sus datos por `get_template_part($slug, null, $args)` y no llama a `get_sub_field` directamente. Esto permite testear partials en isolation y reusarlos si en el futuro hay un template variante.

---

## 10. Edge cases y manejo

| Caso | Comportamiento |
|---|---|
| Página sin secciones | El template muestra hero + breadcrumb + share, sin chips/rail. No es error. |
| Una sola sección | Chips bar y rail se ocultan (mínimo 2 para que tengan sentido) |
| Anchor labels duplicados | Helper genera ids únicos (`-2`, `-3`); el label visible se mantiene |
| Falta `anchor_label` en un layout | Esa sección se renderiza pero no entra en la navegación (warning silencioso en console.warn solo en `WP_DEBUG`) |
| Sidebar cards vacío en layout A | Layout A renderiza en 2 columnas (título + body), el grid se ajusta |
| Sin `target` en layout D | Usa `wp_get_post_parent_id()`; si no hay parent, renderiza link a home |
| Icono SVG no provisto | Rail muestra número de orden centrado |
| Imagen falta en card del layout B | La card se renderiza sin imagen (placeholder gris del DS) |

---

## 11. Accesibilidad

- Hero H1 único en la página (Yoast / a11y check)
- Chips bar: `<nav aria-label="Navegación de sección">` + `<ul role="list">`
- Rail: `<nav aria-label="Navegación rápida">`, cada botón con `aria-label` explícito
- Sección activa: añadir `aria-current="location"` al chip/rail-button activo
- Share button: `aria-haspopup="menu"` cuando el fallback dropdown está disponible
- Respetar `prefers-reduced-motion` para smooth scroll y para el carrusel C (Swiper `speed: 0` cuando reduce-motion)
- Contraste AA en chips bar sobre fondo blanco y en el rail sobre fondos potencialmente variados (rail tiene fondo propio semitransparente para garantizarlo)

---

## 12. Testing manual (pre-merge checklist)

- [ ] Crear página "Test Institucional" con 5 secciones (una de cada layout + dos rich_text_sidebar), publicar
- [ ] Asignar template "Institucional" desde Page Attributes
- [ ] Verificar en desktop ≥1440: chips visibles, rail visible, share visible, scroll a cada anchor funciona, scrollspy activa el item correcto
- [ ] Verificar en tablet 768–991: rail oculto, chips visibles
- [ ] Verificar en mobile <768: chips en carrusel scroll-x, share oculto si <576
- [ ] Lighthouse ≥90 performance, ≥95 a11y
- [ ] Probar share en Chrome desktop (fallback dropdown) y Safari mobile (Web Share API nativo)
- [ ] Cambiar `display_in_anchors=false` en una sección y verificar que desaparece de chips/rail pero sigue visible
- [ ] Probar con 0 secciones (no debe romper el template)
- [ ] Verificar que ACF JSON se guarda en `acf-json/` al editar campos

---

## 13. Riesgos y mitigaciones

| Riesgo | Mitigación |
|---|---|
| Rail flotante tapa contenido en viewports angostos | Solo se muestra ≥992px, donde hay espacio. Bajo eso, la chips bar cubre la función. |
| Sticky chips bar + sticky header se solapan | `--udp-anchor-offset` calcula la suma; el chips bar usa `top: var(--header-h)` |
| Scrollspy "salta" al hacer click manual | Pausar IntersectionObserver durante 500ms tras un click en chip/rail (debounce) |
| Carrusel C duplica markup de people_list | Aceptable a corto plazo; si en F10+ aparecen más usos del carrusel de personas, extraer el card a `template-parts/cards/card-persona.php` |
| Editor olvida `anchor_label` | Required en ACF + fallback silencioso si llega vacío en runtime |
| Botón share rompe en navegadores viejos | Web Share API con feature detection; fallback siempre disponible |
