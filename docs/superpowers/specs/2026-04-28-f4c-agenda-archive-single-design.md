# F4c — Agenda archive (grid + list) + single-evento — Design Spec

**Status:** Draft, pending implementation plan.
**Phase:** F4c (sub-fase final de F4 — agenda end-to-end).
**Sucesor:** F4 extras (image gallery del single-post).

---

## 1. Objetivo

Construir el listado público de Eventos en la página existente "Agenda" (ID 91, slug `agenda-udp`) con dos vistas (grid / list) toggleable, filtros, paginación, y el template de evento individual.

**Alcance F4c:**
- Page template `templates/page-eventos.php` asignable a la página "Agenda" (ID 91).
- 2 vistas toggleables (`?view=grid` default, `?view=list`).
- Card primitive nuevo `card-evento.php`:
  - **Grid mode**: image-left ~228×275 + texto-right (titulo + eyebrow + datetime + lugar + CTA circular bottom-right)
  - **List mode**: row sin imagen (eyebrow left + título centro + fecha right) — row separators horizontales
- Filtros: facultad (taxonomía `facultad`) + año + búsqueda (param `udp_s` igual que F4b1).
- Paginación reutilizando partial F4b1 `template-parts/archive/pagination.php`.
- `single-agenda.php` light theme con sidebar meta (left) + content (right) + share floating + related events.
- Helper nuevo `udp_query_agenda()` siguiendo el patrón de `udp_query_noticias()`.

**Out of scope F4c:**
- Hero band con featured event — no aparece en Figma.
- Image gallery dentro del single-event — diferido.
- Filtro por área u otra taxonomía secundaria — solo facultad.

---

## 2. Routing

Misma estrategia que F4b1: page template asignable a la página existente. La página "Agenda" (ID 91, slug `agenda-udp`) recibe `_wp_page_template = templates/page-eventos.php`. Manteniendo el slug `agenda-udp`, el URL queda `http://localhost:8888/udp/agenda-udp/`. Si el usuario quiere `/eventos/`, debe cambiar manualmente el slug en el editor (opcional, no parte del plan).

`single-agenda.php` enruta automáticamente para post_type=agenda (template hierarchy WP).

---

## 3. Referencias

- Figma archive Agenda grid: `4QlgGMlzNR9Ye344bAFuye` nodeId `3706:21143`.
- Figma archive Agenda list: nodeId `3706:21203`.
- Figma single Evento: nodeId `3706:21402`.
- F4a: `card-noticia.php`, `udp_query_cards`, `udp_card_data_from_post` (parcialmente reutilizable — el evento tiene shape distinto, requiere `udp_card_data_from_evento`).
- F4b1: pattern del page template + filtros + paginación; partial `archive/pagination.php` reutilizado as-is.

**ACF agenda existente (de F1)**: `subtitulo`, `invitados`, `link`, `fecha` (date_picker), `hora_inicio` (time_picker), `hora_termino` (time_picker), `lugar`, `inscripciones` (url), `dias_repetitivos`.

**Taxonomías agenda** (en orden de uso): `post_tag` (3824), `facultad` (3041), `area` (1177), `carrera` (392), `tipo-contenido` (18).

---

## 4. Inventario de archivos

**Crear:**
- `wp-content/themes/starter-theme/templates/page-eventos.php` — page template.
- `wp-content/themes/starter-theme/template-parts/archive/eventos-filters.php` — filtros (facultad + año + búsqueda).
- `wp-content/themes/starter-theme/template-parts/blocks/parts/card-evento.php` — card primitive grid + list (un solo partial con dos modos).
- `wp-content/themes/starter-theme/single-agenda.php` — single de evento.
- `wp-content/themes/starter-theme/template-parts/single/event-meta.php` — sidebar de meta (fecha + hora + lugar + entrada + unidad académica + CTAs).
- `wp-content/themes/starter-theme/template-parts/single/event-related.php` — "Te podría interesar" 2-3 cards de eventos.
- `wp-content/themes/starter-theme/src/scss/blocks/_card-evento.scss` — SCSS card grid + list mode.
- `wp-content/themes/starter-theme/src/scss/templates/_eventos-archive.scss` — page template SCSS.
- `wp-content/themes/starter-theme/src/scss/templates/_eventos-single.scss` — single SCSS.

**Modificar:**
- `wp-content/themes/starter-theme/inc/udp-cards.php` — añadir `udp_query_agenda($filters)` y `udp_card_data_from_agenda(WP_Post $post)`.
- `wp-content/themes/starter-theme/src/scss/main.scss` — `@import "blocks/card-evento"; @import "templates/eventos-archive"; @import "templates/eventos-single";`.

**Reutilizar sin modificar:**
- `template-parts/archive/pagination.php` (F4b1).
- `template-parts/single/post-share.php` (F4b1) — funciona idéntico para eventos (share buttons no dependen del CPT).

---

## 5. Helper `udp_query_agenda()` y `udp_card_data_from_agenda()`

```php
/**
 * Wrapper sobre WP_Query especializado en archive de Agenda.
 *
 * @param array $filters {
 *     @type int    $facultad   term_id de facultad. 0 = sin filtro.
 *     @type int    $year       Año (YYYY). 0 = sin filtro. NB: filtra por meta `fecha` ACF, NO por post_date.
 *     @type string $s          Texto búsqueda. '' = sin búsqueda.
 *     @type int    $paged      Página 1-based. Default 1.
 *     @type int    $limit      Items por página. Default 6.
 *     @type array  $exclude    Post IDs a excluir.
 * }
 * @return array { cards, total, max_pages, paged }
 */
function udp_query_agenda( array $filters ): array;
```

**Diferencias clave vs `udp_query_noticias`:**
- Filtro `year` opera sobre `meta_query` con `fecha` (campo ACF) — los eventos se ordenan por `meta_value` (fecha), no por `post_date`.
- `orderby = meta_value`, `meta_key = fecha`, `order = ASC` (eventos próximos primero) o `DESC` (más recientes primero — decidir; mi propuesta: ASC para mostrar próximos, con flag para invertir si el cliente prefiere).
- Mapea posts vía `udp_card_data_from_agenda` (no `udp_card_data_from_post`).

**Card shape evento:**

```php
[
    'post_id'       => int,
    'eyebrow'       => string,    // primer term de post_tag (case original) — uppercase via CSS
    'eyebrow_color' => 'yellow',  // hardcoded como noticias
    'titulo'        => string,
    'imagen'        => array,     // featured image
    'fecha'         => 'YYYY-MM-DD',  // del campo ACF `fecha`
    'fecha_display' => 'DD MMMM YYYY',  // human-readable "10 Marzo 2026"
    'hora_display'  => '12:00 hrs',     // del campo ACF `hora_inicio`
    'lugar'         => string,    // ACF lugar
    'href'          => permalink,
    'target'        => '',
]
```

Eventos sin featured image se omiten silenciosamente (igual que noticias). Eventos sin fecha ACF caen al final del orden.

---

## 6. Page template `templates/page-eventos.php`

Lee `view`, `facultad`, `year`, `udp_s`, `paged` desde `$_GET`. Llama `udp_query_agenda()`. Renderiza header (breadcrumb + título + toggle) + filtros + body condicional (grid o list).

```html
<article class="udp-eventos-archive udp-eventos-archive--{view}">
  <header class="udp-eventos-archive__header">
    <!-- breadcrumb -->
    <h1>Eventos</h1>
    <!-- view-toggle: 2 botones -->
    <div class="udp-eventos-archive__toggle">
      <a href="?view=grid" class="...--active or not">grid icon</a>
      <a href="?view=list" class="..">list icon</a>
    </div>
  </header>
  <hr/>
  <!-- filters -->
  <hr/>
  <?php if ( $view === 'list' ): ?>
    <ul class="udp-eventos-archive__list udp-eventos-archive__list--list">
      <li><card-evento mode=list></li>
      ...
    </ul>
  <?php else: ?>
    <ul class="udp-eventos-archive__list udp-eventos-archive__list--grid">
      <li><card-evento mode=grid></li>
      ...
    </ul>
  <?php endif; ?>
  <!-- pagination -->
</article>
```

**Toggle UI**: 2 botones circulares 40×40 border-rounded, top-right del header. El que está activo recibe modifier `--active` (bg invertido).

**View persistence**: el toggle es un link simple (`<a href="?view=X">`); preserva los demás GET params actuales (cat/year/udp_s/paged) usando `add_query_arg`.

---

## 7. `card-evento.php` (grid + list modes)

Un solo partial con `$args['mode']` (`'grid'` default | `'list'`). Los modos divergen significativamente en markup, igual que `card-noticia` con `featured`.

### Grid mode (default)

```html
<a class="udp-card-evento udp-card-evento--grid">
  <figure class="udp-card-evento__media">
    <img />
  </figure>
  <div class="udp-card-evento__body">
    <h3 class="udp-card-evento__title">Titulo</h3>
    <span class="udp-card-evento__eyebrow">CHARLAS</span>
    <p class="udp-card-evento__datetime">10 Marzo 2026, 12:00 hrs</p>
    <p class="udp-card-evento__lugar">Auditorio Biblioteca Nicanor Parra, ...</p>
    <span class="udp-card-evento__cta" aria-hidden>↗</span>
  </div>
</a>
```

CTA circular (similar a F3 section-landing): 48×48 border `$gray-medium`, icon arrow-up-right. Hover: bg blanco icon `$dark-1`.

### List mode

```html
<a class="udp-card-evento udp-card-evento--list">
  <span class="udp-card-evento__eyebrow">CHARLAS</span>
  <h3 class="udp-card-evento__title">Las humanidades y el derecho a la cultura</h3>
  <span class="udp-card-evento__date-short">10 Marzo 2026</span>
</a>
```

Layout grid 3-col en CSS: `[140px 1fr 200px]` (eyebrow / title / date). Row con border-bottom 1px `rgba($white, 0.15)`. Hover row: bg `rgba($white, 0.05)`.

Mobile (`<md`):
- Grid mode → image arriba full-width, texto debajo, CTA al lado del título.
- List mode → eyebrow + title + date stack vertical, separator entre rows.

---

## 8. `single-agenda.php` (single de Evento)

Light theme. Layout 2-col con sidebar meta (left) + main content (right) similar al Figma.

```html
<article class="udp-single-event">
  <header class="udp-single-event__header">
    <a class="udp-single-event__back" href="/agenda-udp/">← Volver a Eventos</a>
    <h1 class="udp-single-event__title">Las humanidades y el derecho a la cultura</h1>
  </header>
  <hr />
  <div class="udp-single-event__body">

    <aside class="udp-single-event__meta">
      <!-- event-meta partial -->
    </aside>

    <div class="udp-single-event__content">
      <figure class="udp-single-event__featured"><img /></figure>
      <p class="udp-single-event__caption">Presenta: …</p>
      <?php the_content(); ?>
    </div>

  </div>
  <!-- post-share partial reused -->
  <!-- event-related partial -->
</article>
```

### `event-meta.php` partial — sidebar

Renderiza:
- Eyebrow yellow (primer post_tag)
- "Día" + fecha display
- "Hora" + hora display (from `hora_inicio` ACF, "12:00 hrs")
- "Dirección" + lugar (ACF)
- "Entrada" + "Entrada liberada para todo público" (HARDCODED por ahora — no hay campo ACF; añadir nota TODO para campo futuro)
- "Unidad Académica relacionada" + nombre del primer término `facultad`
- 2 CTAs:
  - "Agregar al calendario" — outline button. Genera link `.ics` dinámico via PHP (AJAX endpoint o link directo a un endpoint que sirve `text/calendar`).
  - "Inscríbete aquí" — primary dark button. Link al campo ACF `inscripciones` si existe; si no, oculto.

**`.ics` calendar link**: para F4c1 hago endpoint GET simple `/?udp_ics={post_id}` que genera VCALENDAR inline. Detallado en plan.

### `event-related.php` partial — "Te podría interesar"

3 eventos en `--horizontal` o `--grid` mode (decidir). Mi propuesta: grid mode (consistente con archive grid). Filtra por misma facultad excluyendo el current. Fallback a más recientes si <3.

---

## 9. SCSS

### `_card-evento.scss`
- Grid mode: flex row image-left 228×275 + body. body con title, eyebrow, datetime, lugar, CTA circular absoluto bottom-right.
- List mode: grid 3-col row separators.
- Theme dark (archive) con override para light (related en single).

### `_eventos-archive.scss`
- Header dark + toggle UI (2 botones circulares 40×40).
- Filtros similar a noticias (3 grid cols 320 320 1fr) — pero taxonomía facultad en vez de category.
- List variant: container-list sin grid 2col, solo flex column de rows.

### `_eventos-single.scss`
- Layout 2-col (sidebar 280px + content 1fr) en desktop, stack en mobile.
- Sidebar bordes finos, gap 16px entre secciones.
- CTAs btn outline + btn primary.
- Featured image full-width del content column.
- Light theme como single-post.

---

## 10. Verificación E2E

**Caso 1** — `/agenda-udp/` (grid default): HTTP 200, header dark, toggle visible, 6 cards `--grid` con image-left + datetime + lugar + CTA. Pagination dark.

**Caso 2** — `/agenda-udp/?view=list`: misma página pero con cards en formato row table (no image, eyebrow + title + date columns). Toggle muestra "list" como activo.

**Caso 3** — `/agenda-udp/?facultad=X&year=2026`: filtros aplicados via tax_query + meta_query (fecha año). Grid muestra solo eventos matching. Toggle preserva los filtros.

**Caso 4** — Single `/agenda/{slug}/`: light layout 2-col. Sidebar con eyebrow + día + hora + lugar + entrada + unidad + 2 CTAs. Content con featured image + caption + body. Share sticky derecha. Related 3 eventos abajo.

**Caso 5** — "Agregar al calendario" link: GET request a `/?udp_ics={post_id}` devuelve `Content-Type: text/calendar` con VEVENT válido (fecha + hora + lugar + título + descripción).

**Caso 6** — Mobile: grid cards stack vertical, list igual. Sidebar single-event apila encima del content.

---

## 11. Open questions / Pendientes

- **Eyebrow source**: primer `post_tag` por defecto. Si el cliente prefiere `tipo-contenido` (más limpio, solo 18 terms), trivial cambiar.
- **Filtro taxonomía**: facultad es el más usado (84% de eventos lo tienen). Si el Figma dice otra cosa, ajustar. Probable variante: añadir filtro área también.
- **Order**: eventos próximos primero (ASC por `fecha`) vs más recientes primero (DESC). Mi default: ASC desde la fecha de hoy hacia adelante (eventos futuros primero, seguidos por pasados). Implementar con `meta_query` `>= TODAY` para filtrar pasados, o simple ASC global.
- **Entrada** en sidebar: hardcoded "Entrada liberada para todo público". Si el cliente quiere control editorial, añadir campo ACF `entrada_info` (textarea) en el grupo agenda.
- **`.ics` endpoint**: implementación inline en `single-agenda.php` con `init` hook que detecta `$_GET['udp_ics']` y emite headers. Alternativa: REST endpoint más limpio, pero overkill.
- **`/eventos/` slug**: la página actual se llama "Agenda" con slug `agenda-udp`. URL queda `/agenda-udp/`. Si el cliente quiere `/eventos/`, renombrar manual desde admin (no parte del plan).
