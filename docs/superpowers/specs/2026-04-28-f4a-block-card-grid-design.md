# F4a — `block_card_grid` + helpers — Design Spec

**Status:** Draft, pending implementation plan.
**Phase:** F4a (subdivisión de F4 según el spec maestro `2026-04-27-migracion-udp-portable-a-starter-theme-design.md` §7).
**Sucesores:** F4b (Noticias archive + single), F4c (Agenda archive + single).

---

## 1. Objetivo

Construir el bloque ACF Flexible Content `block_card_grid` que el admin podrá colocar en cualquier página con plantilla `page-flexible.php` (Home, landings de sección, etc.). El bloque renderiza un grid de cards con:

- **3 fuentes de datos**: `manual` (repeater editorial), `post` (CPT Noticias, 4.064 entries), `concurso` (CPT Concurso Académico, 3 entries).
- **3 layouts**: `3col` (default), `4col`, `list`.
- **2 themes**: `dark` (default), `light`.

Y un helper PHP `udp_query_cards()` reutilizable por F4b (`archive-post.php`) y F4c (`archive-agenda.php`) — el helper soporta tanto modo *block* (LIMIT) como modo *archive* (paginación).

**Out of scope para F4a:**
- La forma de card "Evento" (image-izquierda + CTA circular del Figma `3706:21143`) — vive en F4c con su propio partial `card-evento.php`.
- Layout 2col — se añade cuando algún Figma lo pida.
- Filtros visibles para el visitante — pertenecen a los archives (F4b/F4c).
- Paginación — pertenece a los archives.
- Color por término de la taxonomía — los términos no tienen ACF de color hoy; se difiere a una iteración posterior. F4a usa color fijo `$brand-yellow` para post-source y un radio de 3 colores para manual.

---

## 2. Referencias

- Figma archive Noticias (forma de card canónica): `4QlgGMlzNR9Ye344bAFuye`, nodeId `3706:20836`.
- Spec maestro: `docs/superpowers/specs/2026-04-27-migracion-udp-portable-a-starter-theme-design.md` (§5.4 ACF, §6 Templates, §7 Roadmap).
- Plan F3 (referencia de patrón template-part + ACF JSON + SCSS BEM): `docs/superpowers/plans/2026-04-28-f3-section-landing-template.md`.

---

## 3. Inventario de archivos

**Crear:**
- `acf-json/group_template_flexible_content.json` — campo `content_blocks` flex con layout `block_card_grid` y todas sus sub-fields inline.
- `inc/udp-cards.php` — helper `udp_query_cards($args)` + helpers internos (`udp_card_data_from_post`, `udp_card_format_date`, `udp_card_eyebrow_from_post`).
- `template-parts/blocks/block-card_grid.php` — container del bloque flex.
- `template-parts/blocks/parts/card-noticia.php` — card primitive.
- `src/scss/blocks/_card-grid.scss` — estilos del bloque y la card en un único archivo.

**Modificar:**
- `functions.php` — añadir `require_once STARTER_BS5_DIR . 'inc/udp-cards.php';`.
- `src/scss/main.scss` — `@import "blocks/card-grid";` en la sección de bloques ACF.

**No tocar:**
- `template-parts/blocks/block-cards_grid.php` (scaffold legacy con guion bajo, nombre distinto del nuevo). Queda en disco hasta cleanup posterior.
- `src/scss/blocks/_flexible-blocks.scss` (placeholder). Limpieza en F4 final o más adelante.
- mu-plugins, header/footer, F3 templates.

---

## 4. ACF schema

Grupo `group_template_flexible_content`, location `page_template == templates/page-flexible.php`.

Field keys: prefijo `field_block_card_grid_*` para todas las sub-fields del layout.

```
content_blocks (flexible_content, button_label = "Añadir bloque", required: false)
└── LAYOUT "block_card_grid" — display: block, label "Grid de cards"
    ├── titulo            (text, optional)         ← H2 de la sección
    ├── eyebrow           (text, optional)         ← Eyebrow sobre el título
    ├── fuente            (radio, required, default 'manual')
    │   choices: manual | post | concurso
    │   layout: horizontal · return_format: value
    ├── cards_manuales    (repeater, min 1, layout 'block', button_label "Agregar card")
    │   conditional_logic: fuente == manual
    │   ├── eyebrow       (text, optional)         ← p.ej. "INTERNACIONAL"
    │   ├── eyebrow_color (radio, optional, default 'yellow')
    │   │   choices: yellow | red | blue
    │   │   instructions: "Color de la etiqueta. Default amarillo."
    │   ├── titulo        (text, required)
    │   ├── imagen        (image, required, return_format 'array', preview_size 'card-thumbnail')
    │   ├── fecha         (date_picker, optional, display_format 'd / m / Y', return_format 'Y-m-d')
    │   └── link          (link, required, return_format 'array')
    ├── filtros           (group, display 'block')
    │   conditional_logic: fuente IN (post, concurso)
    │   ├── taxonomias    (taxonomy, multiple, return_format 'id', optional)
    │   │   taxonomy: 'category' (única taxonomía soportada en F4a — ver decisión §4 final)
    │   │   instructions: "Si dejas vacío, se muestran todos."
    │   ├── n_items       (number, min 1, max 24, default 6, required)
    │   └── orden         (radio, required, default 'date_desc')
    │       choices: date_desc | date_asc | random
    ├── columnas          (radio, required, default '3col')
    │   choices: 3col | 4col | list
    │   layout: horizontal
    └── theme             (radio, required, default 'dark')
        choices: dark | light
        layout: horizontal
```

**Decisión:** la taxonomía del campo `filtros.taxonomias` se establece a `category`. Para la fuente `concurso` el filtro queda inactivo (concurso no usa `category`); en una iteración futura se puede ampliar a un radio que cambie la taxonomía según la fuente, pero con 3 entradas en concurso no merece la pena ahora.

---

## 5. API del helper

`inc/udp-cards.php` expone una función pública y dos privadas reutilizables.

```php
/**
 * Devuelve cards normalizadas para el partial `card-noticia`, más metadata
 * útil para paginación de archives.
 *
 * @param array $args {
 *     @type string $source        'manual' | 'post' | 'concurso'.
 *     @type array  $manual_cards  Repeater rows si source=manual.
 *     @type array  $taxonomies    IDs de términos para filtrar (post|concurso).
 *     @type int    $limit         Items por página. Default 6, max 24.
 *     @type int    $paged         Página actual (1-based). Default 1.
 *     @type string $orden         'date_desc' | 'date_asc' | 'random'. Default 'date_desc'.
 * }
 * @return array {
 *     @type array $cards     Array<Card> normalizadas.
 *     @type int   $total     Total matching items.
 *     @type int   $max_pages ceil($total / $limit).
 *     @type int   $paged     Página actual eco.
 * }
 */
function udp_query_cards( array $args ): array;

/**
 * Convierte un WP_Post a la forma Card. Pública porque archives la usarán
 * para listas custom sin ir por udp_query_cards().
 */
function udp_card_data_from_post( WP_Post $post ): array;
```

**Forma `Card`** (estable, lo que recibe el partial):

```php
[
    'eyebrow'       => string,  // 'INTERNACIONAL' o ''
    'eyebrow_color' => string,  // 'yellow' | 'red' | 'blue' | ''
    'titulo'        => string,  // required
    'imagen'        => [        // ACF image array — id, url, alt, sizes
        'id'    => int,
        'url'   => string,
        'alt'   => string,
        'sizes' => array,
    ],
    'fecha'         => string,  // 'YYYY-MM-DD' o ''
    'href'          => string,  // permalink o link.url
    'target'        => string,  // '_blank' o ''
]
```

Los posts sin featured image se omiten silenciosamente del array `cards` (la imagen es requirement de la card y el editor lo sabe). Esto se documenta en el código.

**Manual mode:** itera el repeater, mapea cada row al shape Card. NO ejecuta WP_Query.

**Post / concurso mode:** ejecuta `WP_Query` con `post_type` correspondiente, `tax_query` (si `taxonomies` no está vacío), `posts_per_page` = `limit`, `paged`. Mapea cada `WP_Post` con `udp_card_data_from_post()`.

---

## 6. Container template-part

`template-parts/blocks/block-card_grid.php`:

```html
<section class="udp-card-grid udp-card-grid--{columnas} udp-card-grid--{theme}">
  <div class="udp-card-grid__inner">
    <header class="udp-card-grid__header">     <!-- opcional si hay eyebrow o titulo -->
      <p class="udp-card-grid__eyebrow">…</p>
      <h2 class="udp-card-grid__title">…</h2>
    </header>
    <ul class="udp-card-grid__list">
      <li class="udp-card-grid__item">
        <!-- get_template_part 'parts/card-noticia' con [card, theme] -->
      </li>
      …
    </ul>
  </div>
</section>
```

Lógica:
1. Lee `get_sub_field()` para `titulo`, `eyebrow`, `fuente`, `columnas`, `theme`.
2. Construye `$args` para `udp_query_cards()` según fuente (manual_cards desde repeater, o filtros + n_items + orden).
3. Si `cards` vacío → `return` early (no markup).
4. Renderiza header (si hay título o eyebrow).
5. Itera cards, llamando `get_template_part('template-parts/blocks/parts/card-noticia', null, ['card' => $card, 'theme' => $theme])`.

---

## 7. Card partial

`template-parts/blocks/parts/card-noticia.php`:

```html
<a class="udp-card-noticia udp-card-noticia--{theme}" href="{href}" {target} {rel}>
  <figure class="udp-card-noticia__media">
    <img src="…" alt="…" loading="lazy" />
  </figure>
  <div class="udp-card-noticia__body">
    <header class="udp-card-noticia__meta">
      <span class="udp-card-noticia__eyebrow udp-card-noticia__eyebrow--{color}">EYEBROW</span>
      <time class="udp-card-noticia__date" datetime="2026-08-26">26 / 08 / 2026</time>
    </header>
    <h3 class="udp-card-noticia__title">Título de la noticia hasta dos líneas</h3>
    <span class="udp-card-noticia__more">Leer más
      <svg width="12" height="12">…arrow-up-right…</svg>
    </span>
  </div>
</a>
```

- El `<a>` envuelve toda la card (área clickable completa).
- `eyebrow` y `eyebrow--{color}` aplican el color del pill (yellow/red/blue/none).
- Fecha en `<time datetime="ISO">` para semántica. El partial llama internamente a `udp_card_format_date($card['fecha'])` para el display human-readable `DD / MM / YYYY`.
- Title con `line-clamp: 2` (CSS).
- "Leer más" es decorativo, no es un link separado.

---

## 8. SCSS structure

`src/scss/blocks/_card-grid.scss` — un único archivo con:

1. Block container `.udp-card-grid` + modificadores `--3col / --4col / --list` para `.udp-card-grid__list`.
2. Theme modifiers `--dark / --light` (afectan bg, color de texto y eyebrow contrast).
3. Header (`__header / __eyebrow / __title`).
4. Card primitive `.udp-card-noticia` + `__media / __body / __meta / __eyebrow / __date / __title / __more`.
5. Eyebrow color modifiers (`--yellow / --red / --blue`).
6. Hover: image `transform: scale(1.03)`, "Leer más" subraya con thickness 2px. Respeta `prefers-reduced-motion`.

**Tipografía** (alineada a `_variables.scss` de F2):
- Eyebrow: Necto Mono uppercase 11px, padding 2px 8px, fondo color del modifier, texto `$dark-1` (sobre yellow) o `$white` (sobre red/blue).
- Date: Work Sans Regular 12px `$white-70` (dark) / `$dark-2` (light).
- Title: Work Sans Medium 18px (3col) / 16px (4col, list), line-height `$line-height-snug`.
- "Leer más": Work Sans Medium 14px, underline 1px → 2px en hover.

**Mobile (`<md`):**
- 3col, 4col → 1 columna (display: grid; grid-template-columns: 1fr).
- list → ya es 1 columna.
- Image en list mantiene 96×96 (no escala a 100%).

---

## 9. Verificación E2E (criterios de aceptación)

**Caso 1 — Manual mode:** Página flex con bloque `block_card_grid`, 4 cards manuales (variando eyebrow_color y con/sin fecha), layout `3col`, theme `dark`.
- Curl emite `udp-card-grid--3col udp-card-grid--dark` y 4 `<li>` con eyebrow coloreado, fecha `DD / MM / YYYY`, "Leer más ↗".
- Cambio admin → 4col: misma página renderiza 4 cols sin tocar PHP.
- Cambio admin → list: cards en flex-row, image 96×96 izquierda.
- Cambio admin → theme light: bg blanco, texto oscuro.

**Caso 2 — Post source:** Bloque con `fuente=post`, `n_items=6`, sin filtros taxonomía.
- Helper devuelve los 6 posts publicados más recientes con featured image.
- Eyebrow toma el primer término `category` (si existe).
- Fecha = `post_date` formateada.
- Link = permalink. Image = featured (`card-thumbnail`).

**Caso 3 — Post source con filtros:** mismo bloque + taxonomías = `[ID category seleccionada]`.
- Solo posts de esa categoría aparecen (vía `tax_query`).
- Sin matches → block early-return, sin markup.

**Caso 4 — Concurso source:** 3 entradas existentes en DB, `n_items=6`. Renderiza máx 3 cards.

**Edge cases que el plan implementa y testea:**
- Post sin featured image → omitido silenciosamente (documentado).
- Repeater vacío en runtime → early return.
- Limit > total real → solo renderiza los disponibles.

**Cleanup:** página de prueba "Test Card Grid" se borra vía SQL al final (mismo patrón F3 Task 6).

**No se incluye:**
- Tests unitarios PHP — el tema no tiene infraestructura phpunit, consistente con F1-F3.
- Tests E2E con browser — verificación manual del usuario en dev (mismo patrón).

---

## 10. Cobertura del spec maestro vs F4a

| Requisito spec §5.4 / §6 / §7 | Cubierto en F4a |
|---|---|
| Bloque flexible `block_card_grid` con título | ✓ Sec. 4 (campo `titulo`) |
| Fuente: manual / CPT post / agenda / concurso | Parcial — F4a cubre manual + post + concurso. Agenda en F4c. |
| Filtros taxonomía | ✓ Sec. 4 (`filtros.taxonomias`) — preset admin, no UI visitor |
| Nº items | ✓ (`filtros.n_items`, máx 24) |
| Layout 3col / 4col / lista | ✓ |
| Theme dark / light | ✓ |
| Helper reutilizable por archives | ✓ Sec. 5 (`udp_query_cards` + `udp_card_data_from_post`) |
| Card forma Noticia (image+eyebrow+título+fecha+Leer más) | ✓ Sec. 7 |
| Card forma Evento | ✗ — F4c |
| Paginación en bloque | ✗ por diseño — paginación pertenece a archives |

---

## 11. Open questions / TODOs futuros

- **Color por término de taxonomía**: hoy hardcoded `$brand-yellow` para post-source. Para implementar correctamente se necesita extender `group_tax_*_meta.json` con un ACF de color picker en cada término. Se difiere para iteración futura (no bloquea F4a).
- **Layout 2col**: si Home u otro Figma lo pide, se añade añadiendo un modifier SCSS `--2col` y una opción al radio `columnas`. Coste estimado: <30 min.
- **Forma Evento (card-evento.php)**: F4c. Reutilizará `udp_card_data_from_post()` con un mapeo distinto y tendrá su propio partial.
- **Más fuentes (calendario, carrera-udp, centro-udp)**: F5/F6. El helper crece con un `case` adicional en `switch($source)`.
