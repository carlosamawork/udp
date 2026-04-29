# F5a — Calendario Académico archive — Design Spec

**Status:** Draft.
**Phase:** F5a (sub-fase 1 de F5 — Calendario solo). F5b (Concursos) y `block_calendario_grid` quedan para después.

---

## 1. Objetivo

Construir el archive de Calendario Académico en `/calendario-academico/` (page existente ID 74). Una página por año (default año actual), con sidebar sticky de meses-anchor + main content con secciones agrupadas por mes + filtros publico/tipo/búsqueda. Reutiliza `?udp_ics=ID` para descarga `.ics` por entrada (single-day all-day event).

**Out of scope F5a:**
- Single-calendario template (per usuario: no hay single, solo botón ICS inline).
- `block_calendario_grid` flex content — diferido.

---

## 2. Routing

Page template `templates/page-calendario.php` asignable manualmente a la página "Calendario Académico" (ID 74, slug `calendario-academico`). Sin colisiones con WP query vars (todos los filter params usan prefijo `udp_*`).

---

## 3. Referencias

- Figma archive: `4QlgGMlzNR9Ye344bAFuye` nodeId `3706:20328`.
- ACF `cpt_calendario_meta` (F1): `fecha` (date_picker), `fecha_amistosa` (text), `destacado` (true_false).
- Taxonomías calendario: `tipo-udp` (603), `publico-udp` (544), `area-udp` (505).
- Helpers heredados de F4c: pattern `udp_query_X` + `udp_card_data_from_X`. ICS endpoint `inc/udp-ics.php` (extender whitelist para incluir `calendario`).

---

## 4. Inventario de archivos

**Crear:**
- `templates/page-calendario.php`
- `template-parts/archive/calendario-sidebar.php` — año + lista de meses anchor
- `template-parts/archive/calendario-filters.php` — filtros (publico + tipo + udp_s)
- `template-parts/archive/calendario-month-section.php` — heading mes + lista de entries
- `template-parts/blocks/parts/entry-calendario.php` — single entry render (date + title + DESTACADO + ICS button)
- `src/scss/templates/_calendario-archive.scss`

**Modificar:**
- `inc/udp-cards.php` — añadir `udp_query_calendario($filters)` + `udp_calendario_data_from_post($post)`.
- `inc/udp-ics.php` — extender whitelist para incluir `calendario` (post_type) además de `agenda`.
- `src/scss/main.scss` — `@import "templates/calendario-archive";`.

**Reutilizar sin modificar:**
- `template-parts/sections/breadcrumb.php` (F3).

---

## 5. Helpers

### `udp_query_calendario( array $filters ): array`

```
@param int    $publico  term_id de publico-udp. 0 = sin filtro.
@param int    $tipo     term_id de tipo-udp. 0 = sin filtro.
@param int    $year     YYYY. Default: año actual.
@param string $s        Búsqueda. '' = sin búsqueda.
@return array { entries_by_month: array<month_num, array<entry>>, total: int, year: int }
```

Diferencia clave vs `udp_query_noticias`/`udp_query_agenda`:
- **No pagina**. Devuelve TODAS las entries del año, agrupadas por mes.
- `entries_by_month`: `{ '01' => [entry, entry], '02' => [entry], ..., '12' => [...] }` con keys 01-12 (los meses sin entries no aparecen).
- Order interno por `meta_value (fecha)` ASC.
- `tax_query` con relation AND para `publico-udp` y `tipo-udp` cuando ambos filtros activos.
- `s` aplica a WP_Query.

### `udp_calendario_data_from_post( WP_Post $post ): array`

Shape Entry (no Card — no necesita featured image):

```
[
    'post_id'       => int,
    'titulo'        => string,
    'fecha'         => 'YYYY-MM-DD',
    'fecha_display' => string,  // ACF fecha_amistosa si existe, sino formatted "j de F de Y"
    'destacado'     => bool,    // ACF
    'descripcion'   => string,  // post_content stripped (~120 chars max)
    'tipo'          => string,  // primer tipo-udp term name (mostrado opcional)
    'href_ics'      => string,  // /?udp_ics=ID
]
```

NUNCA devuelve null — todas las entries siempre se muestran.

### Extensión `inc/udp-ics.php`

Whitelist actual: solo `agenda`. Cambiar a:

```php
if ( ! $post || ! in_array( $post->post_type, array( 'agenda', 'calendario' ), true ) || $post->post_status !== 'publish' ) {
```

Las entradas calendario son all-day (no hora_inicio). El parser de hora ya devuelve 0 cuando no hay valor → DTSTART = midnight, DTEND = midnight + 1h. Para mejor compatibility con calendar apps, hacemos all-day cuando el post_type es calendario:
- DTSTART;VALUE=DATE:YYYYMMDD
- DTEND;VALUE=DATE:YYYYMMDD (+1 day)

---

## 6. Page template `page-calendario.php`

```html
<article class="udp-calendario-archive">

  <header class="udp-calendario-archive__header">
    <!-- breadcrumb -->
    <h1>Calendario Académico</h1>
  </header>

  <hr/>

  <!-- filters partial: publico + tipo + udp_s -->

  <hr/>

  <div class="udp-calendario-archive__body">

    <aside class="udp-calendario-archive__sidebar">
      <!-- calendario-sidebar partial: año dropdown + lista meses anchor -->
    </aside>

    <main class="udp-calendario-archive__main">
      <!-- intro paragraph from page_content (the_content()) -->
      <div class="udp-calendario-archive__intro">
        <?php the_content(); ?>
      </div>

      <!-- foreach mes en entries_by_month: -->
      <section id="enero" class="udp-calendario-archive__month">
        <h2>Enero</h2>
        <ul>
          <li><entry-calendario partial></li>
          ...
        </ul>
      </section>
      <!-- ... más meses ... -->

      <a href="#top" class="udp-calendario-archive__back-to-top">Volver arriba</a>
    </main>

  </div>

</article>
```

Año active: `?udp_year=2026` (default current year). Año changes via dropdown en el sidebar — recarga la página con nuevo `udp_year`. NO hay scroll cross-year.

Months: si `entries_by_month['03']` está vacío (no entries en marzo del año), la `<section id="marzo">` NO se renderiza (sidebar link tampoco la enlaza, o queda como link "muerto" disabled).

---

## 7. Sidebar partial

```html
<form method="get" action="<page url>" class="udp-calendario-archive__year-form">
  <select name="udp_year" data-udp-autosubmit>
    <!-- años con entries (DISTINCT YEAR(fecha) cacheado) -->
  </select>
  <input type="hidden" name="udp_publico" value="..." />
  <input type="hidden" name="udp_tipo" value="..." />
  <input type="hidden" name="udp_s" value="..." />
</form>

<ul class="udp-calendario-archive__months-nav">
  <li><a href="#enero" data-udp-month-link="01">Enero</a></li>
  <li><a href="#febrero" data-udp-month-link="02">Febrero</a></li>
  ...
</ul>
```

Sidebar es `position: sticky; top: 100px;` para mantenerse visible al hacer scroll.

JS opcional: marcar el month link como `--active` cuando esa sección está visible (IntersectionObserver). Si es muy complejo, se puede dejar para iteración posterior — para F5a básico, los links son anchor scroll simples.

---

## 8. Entry calendario partial

```html
<li class="udp-entry-calendario<?php echo $destacado ? ' udp-entry-calendario--destacado' : ''; ?>">
  <div class="udp-entry-calendario__date">
    <?php echo esc_html( $fecha_display ); ?>
  </div>
  <div class="udp-entry-calendario__body">
    <?php if ( $destacado ) : ?>
      <span class="udp-entry-calendario__tag">DESTACADO</span>
    <?php endif; ?>
    <h3 class="udp-entry-calendario__title"><?php echo esc_html( $titulo ); ?></h3>
    <?php if ( $descripcion ) : ?>
      <p class="udp-entry-calendario__desc"><?php echo esc_html( $descripcion ); ?></p>
    <?php endif; ?>
    <a class="udp-entry-calendario__ics" href="<?php echo esc_url( $href_ics ); ?>">
      <svg /* calendar icon */ />
      <?php esc_html_e( 'Agregar al calendario', 'starter-theme' ); ?>
    </a>
  </div>
</li>
```

DESTACADO tag visual: yellow eyebrow pequeño (Necto Mono uppercase, igual que `udp-card-noticia__eyebrow--yellow`).

---

## 9. SCSS

`_calendario-archive.scss`:
- `.udp-calendario-archive` dark theme (bg `$dark-1`, color `$white`).
- Header con título 64px Arizona Flare.
- Filters dark (igual que noticias dark — se reusa el SCSS de `_noticias-archive.scss`).
- `.udp-calendario-archive__body`: grid 2-col `[280px 1fr]` desktop; stack vertical mobile.
- Sidebar sticky top 100px.
- Months sections: heading 24px Arizona Flare, separator entre meses.
- Entry: row con date column + body column (gap 24px). DESTACADO destacado con border-left amarillo o tag.

---

## 10. Verificación E2E

1. `/calendario-academico/`: HTTP 200, dark theme, sidebar visible con años + meses, main con intro + secciones meses, entries listadas.
2. Click en mes en sidebar → scroll a esa sección.
3. `?udp_year=2025` → cambia el año mostrado, recarga con entries de 2025.
4. `?udp_publico=X` → filtra entries por publico-udp.
5. `?udp_tipo=Y` → filtra por tipo-udp.
6. ICS button click en una entry → descarga .ics all-day para esa fecha.
7. Mobile: sidebar pasa a top horizontal scroll de meses, main full-width.

---

## 11. Open questions / Pendientes

- **JS active-month tracking**: defer to iteración futura (IntersectionObserver para destacar el mes activo en sidebar).
- **`block_calendario_grid`** flex content para Home u otros landings: F5 extras o cuando se necesite.
- **Single-calendario.php**: no se construye (per usuario, solo ICS button basta).
