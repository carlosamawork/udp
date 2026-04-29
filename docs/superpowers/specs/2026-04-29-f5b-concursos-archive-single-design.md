# F5b — Concursos académicos archive + single — Design Spec

**Status:** Draft.
**Phase:** F5b (sub-fase 2 final de F5).

---

## 1. Objetivo

Archive `/concursos-academicos/` (page existente ID 76) + `single-concurso-academico.php` con descarga de PDFs. CPT `concurso-academico` tiene 3 entries.

---

## 2. Características visuales

- **Archive (Figma `4412:24719`)**: hero header **light + purple/blue** (`$brand-blue` background con título white), filters light (facultad + udp_s), grid 2-col cards horizontales (image-left format igual que noticias), share sticky.
- **Single (Figma `4412:25673`)**: light theme, back link + título, layout 2-col (sidebar meta left + content right), 2 botones de descarga al final del content, share sticky. NO related section.

---

## 3. Inventario de archivos

**Crear:**
- `templates/page-concursos.php` — page template asignable a ID 76
- `template-parts/archive/concursos-filters.php` — facultad + udp_s
- `single-concurso-academico.php`
- `template-parts/single/concurso-meta.php` — sidebar meta (fecha + facultad eyebrow)
- `template-parts/single/concurso-files.php` — 2 download buttons
- `src/scss/templates/_concursos-archive.scss` — light + purple hero
- `src/scss/templates/_concursos-single.scss` — light layout 2-col + buttons

**Modificar:**
- `inc/udp-cards.php` — añadir `udp_query_concursos($filters)` + `udp_card_data_from_concurso($post)` (similar a noticia, requiere featured image — eyebrow desde primer facultad term).
- `acf-json/group_cpt_concurso_meta.json` — añadir field `archivo_formato_propuestas` (file, opcional). Sync.
- `src/scss/main.scss` — 2 imports.

**Reutilizar sin modificar:**
- `template-parts/blocks/parts/card-noticia.php` con `variant='horizontal'` `theme='light'` (mismo card que F4b1 noticias).
- `template-parts/single/post-share.php` (igual que single-post).
- `template-parts/archive/pagination.php`.

---

## 4. ACF — `archivo_formato_propuestas`

Añadir al group `cpt_concurso_meta`:

```json
{
    "key": "field_concurso_archivo_formato",
    "label": "Archivo Formato Propuestas",
    "name": "archivo_formato_propuestas",
    "type": "file",
    "return_format": "array",
    "instructions": "PDF/DOCX con el formato de propuesta. Opcional — si no se sube, el botón 'Formato de propuestas' no aparece."
}
```

Existing `archivo_concurso` se mapea al botón "Descargar bases".

Caption del single ("Periodo de postulación: ..."): usar `post_excerpt` (no nuevo campo).

---

## 5. Helpers

### `udp_card_data_from_concurso( WP_Post $post ): ?array`

Idéntico a `udp_card_data_from_post` PERO eyebrow desde primer término de `facultad` (no `category`):

```php
function udp_card_data_from_concurso( WP_Post $post ): ?array {
    $thumb_id = get_post_thumbnail_id( $post->ID );
    if ( ! $thumb_id ) return null;
    // ... (igual a udp_card_data_from_post)
    // Eyebrow:
    $facultades = get_the_terms( $post->ID, 'facultad' );
    $eyebrow_text = ( ! is_wp_error( $facultades ) && ! empty( $facultades ) ) ? $facultades[0]->name : '';
    // Return shape Card con eyebrow_color = 'yellow'
}
```

### `udp_query_concursos( array $filters ): array`

```php
@param int    $facultad  term_id facultad. 0 = sin filtro.
@param string $s         búsqueda.
@param int    $paged     default 1.
@param int    $limit     default 6.
@return { cards, total, max_pages, paged }
```

WP_Query `post_type=concurso-academico`, orderby `date DESC`. Cards via `udp_card_data_from_concurso`.

---

## 6. Page template `page-concursos.php`

Layout:

```html
<article class="udp-concursos-archive">
  <header class="udp-concursos-archive__header"> <!-- BG brand-blue -->
    <breadcrumb white />
    <h1>Concursos académicos</h1>
  </header>

  <!-- filters partial -->
  <hr />

  <ul class="udp-concursos-archive__list">
    <!-- cards (variant=horizontal theme=light) -->
  </ul>

  <!-- pagination partial -->
</article>
```

Reusa `card-noticia.php` (variant=horizontal, theme=light).

---

## 7. `single-concurso-academico.php`

```html
<article class="udp-single-concurso">
  <header>
    <a class="udp-single-concurso__back" href="/concursos-academicos/">← Volver a Concursos académicos</a>
    <h1>{título}</h1>
  </header>

  <hr/>

  <div class="udp-single-concurso__body">
    <aside class="udp-single-concurso__sidebar">
      <!-- concurso-meta partial: Fecha + eyebrow facultad -->
    </aside>

    <div class="udp-single-concurso__content">
      <figure class="udp-single-concurso__featured"><img /></figure>
      <p class="udp-single-concurso__caption"><?php echo esc_html(get_the_excerpt()); ?></p>
      <div class="udp-single-concurso__entry-content"><?php the_content(); ?></div>

      <!-- concurso-files partial: 2 buttons -->
    </div>
  </div>

  <!-- post-share partial reusado -->
</article>
```

---

## 8. SCSS

### `_concursos-archive.scss` (light + purple hero):
- Container bg `$white`
- Header `__header`: bg `$brand-blue`, color `$white`, padding $space-3xl
- Breadcrumb override colors a `$white`
- Title 64px Arizona Flare white
- Body con cards-list grid 2-col light theme

### `_concursos-single.scss`:
- Layout 2-col 280/1fr (similar a single-event)
- Buttons:
  - `--outline`: border $dark-1, hover bg $dark-1 color $white
  - `--primary`: bg $dark-1 color $white, hover bg $brand-blue
  - Pill (border-radius 9999px), height 44, gap 8 con icono

---

## 9. Verificación E2E

1. `/concursos-academicos/`: HTTP 200, hero purple, filters, 3 cards horizontales light.
2. `?udp_facultad=X` filtra por facultad.
3. Single: layout 2-col, sidebar con fecha + eyebrow facultad, content con featured image + caption (excerpt) + content + 2 buttons (Formato + Descargar).
4. Click "Descargar bases" → descarga `archivo_concurso` (PDF).
5. Click "Formato de propuestas" → descarga `archivo_formato_propuestas` si existe; oculto si vacío.

---

## 10. Pendientes

- Caption alternativa: si quieren control editorial específico del periodo (no usar excerpt), añadir campo ACF `periodo_postulacion` en iteración futura.
