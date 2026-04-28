# F4b2 — Noticias hero band + tema dark — Design Spec

**Status:** Draft, pending implementation plan.
**Phase:** F4b2 (sub-fase 2 de F4b después de F4b1).
**Sucesores:** F4c (Agenda), F4 extras (image gallery del single).

---

## 1. Objetivo

Completar el archive de Noticias con:
1. **Tema dark** (corrección de F4b1, que se deployó light por error de spec).
2. **Hero band** en página 1: 1 card featured grande (image con overlay) + 2 side compactas apiladas a la derecha.
3. **Bump** posts_per_page 6 → 9 (3 hero + 6 main grid en página 1; 9 en grid en páginas 2+).
4. **ACF** field `featured_post` (post_object) en page-noticias para curaduría editorial. Si no se setea, fallback al post más reciente.

---

## 2. Cambios al theme — light → dark

`templates/page-noticias.php` y partials del archive switchean a `theme=dark`. SCSS `_noticias-archive.scss` invierte:
- Container `.udp-noticias-archive` → bg `$dark-1`, color `$white`
- Title → `$white`
- Breadcrumb override → `$white` y separador `$white-70`
- Separator → `rgba($white, 0.15)`
- Filters → input/select bg transparent, border `rgba($white, 0.2)`, color `$white`, placeholder `rgba($white, 0.5)`, chevron SVG con stroke `%23FFFFFF`
- Pagination → links color `$white`, current bg `$white` color `$dark-1` (invertido)

El `single-post.php` se mantiene LIGHT (es la página individual, no el archive).

---

## 3. Hero band — markup y data

Partial nuevo `template-parts/archive/noticias-hero.php` que recibe array `cards` (3 items) y renderiza:

```html
<section class="udp-noticias-hero">
  <div class="udp-noticias-hero__inner">
    <!-- Featured (grande, izquierda) -->
    <div class="udp-noticias-hero__featured">
      <!-- card-noticia variant=featured theme=dark -->
    </div>
    <!-- 2 side cards stacked (derecha) -->
    <div class="udp-noticias-hero__side">
      <!-- card-noticia variant=horizontal theme=dark -->
      <!-- card-noticia variant=horizontal theme=dark -->
    </div>
  </div>
</section>
```

**Layout grid CSS:**
- Desktop: 2 cols (1fr 1fr) gap 30px
- Featured ocupa col 1
- Side ocupa col 2 con flex-column gap 30px (2 cards stacked)
- Mobile (`<lg`): 1 col, featured arriba, side cards apiladas abajo

---

## 4. Featured card variant

Nuevo modifier `.udp-card-noticia--featured` en `_card-grid.scss`. Estructura visual diferente:

```
┌──────────────────────────────────┐
│ [DESTACADO]      26 / 08 / 2026  │  ← Eyebrow top-left + Date top-right
│                                  │
│                                  │
│       Título grande              │  ← Título centrado, Arizona Flare
│       en 3-4 líneas              │     32-40px, color $white
│       tipo display               │
│                                  │
│                                  │
└──────────────────────────────────┘
```

- Aspect-ratio 432/580 (~3:4)
- Image cover llena la card
- Overlay: `linear-gradient(rgba($dark-1, 0.4), rgba($dark-1, 0.5))` o `background-color: rgba($dark-1, 0.4)` sobre la image (oscurece para legibilidad del texto)
- Eyebrow yellow position absolute top-left padding $space-md
- Date position absolute top-right (Work Sans Medium 14px white)
- Título position absolute centrado vertical+horizontal (Arizona Flare 40px desktop / 28px mobile)
- Sin "Leer más" — la card entera es clickable
- Hover: image scale 1.02 + overlay un poco más oscuro

Cuando `variant=featured`, el partial `card-noticia.php` cambia el markup (no usa el body con eyebrow+title+date+leer-más; usa un overlay simplificado).

---

## 5. ACF — `featured_post` field

Nuevo grupo `group_template_noticias`:
- Location: `page_template == templates/page-noticias.php`
- Field único: `featured_post` (post_object)
  - Post type filter: `['post']`
  - return_format: `id`
  - allow_null: 1
  - Instructions: "Post destacado en la zona hero. Si dejas vacío, se muestra automáticamente la noticia más reciente."

---

## 6. Page template — lógica nueva

```php
$is_first_page = ($paged === 1);
$featured_id   = (int) get_field('featured_post', $page_id);

if ($is_first_page) {
    // Resolver featured: ACF o fallback a más reciente
    if ($featured_id <= 0) {
        $latest = get_posts(['posts_per_page' => 1, 'post_type' => 'post', 'fields' => 'ids']);
        $featured_id = $latest ? (int) $latest[0] : 0;
    }

    // Side cards: 2 más recientes excluyendo featured
    $side_result = udp_query_noticias([
        'paged'   => 1,
        'limit'   => 2,
        'exclude' => [$featured_id],
    ]);
    $side_cards = $side_result['cards'];

    // Side card IDs to also exclude from grid
    $side_ids = array_map(fn($c) => $c['post_id'] ?? 0, $side_cards);
    $exclude_grid = array_filter(array_merge([$featured_id], $side_ids));

    // Main grid: 6 cards excluyendo featured + side
    $grid_result = udp_query_noticias([
        'cat'     => $cat,
        'year'    => $year,
        's'       => $s,
        'paged'   => 1,
        'limit'   => 6,
        'exclude' => $exclude_grid,
    ]);
} else {
    // Page 2+: solo grid 9 sin featured
    $featured_id = 0;
    $side_cards  = [];
    $grid_result = udp_query_noticias([
        'cat'   => $cat, 'year' => $year, 's' => $s,
        'paged' => $paged, 'limit' => 9,
    ]);
}
```

**Card data** del featured se obtiene con `udp_card_data_from_post(get_post($featured_id))`.

**Cambios en `udp_query_noticias()`:**
- Nuevo arg `exclude` (array de post IDs) → mapea a `'post__not_in'` en WP_Query.
- Card shape de `udp_card_data_from_post` añade `post_id => int` para que el caller pueda excluir IDs en queries siguientes.

---

## 7. Inventario de archivos

**Crear:**
- `acf-json/group_template_noticias.json`
- `template-parts/archive/noticias-hero.php`

**Modificar:**
- `inc/udp-cards.php` — `udp_query_noticias()` arg `exclude`; `udp_card_data_from_post()` add `post_id` field
- `templates/page-noticias.php` — lógica nueva (page 1 vs page 2+); pasa theme=dark
- `template-parts/blocks/parts/card-noticia.php` — soporta `variant='featured'` con markup distinto
- `src/scss/blocks/_card-grid.scss` — modifier `.udp-card-noticia--featured`
- `src/scss/templates/_noticias-archive.scss` — theme dark + hero band layout
- `MEMORY.md`

---

## 8. Verificación E2E

**Caso 1 — Page 1 sin featured ACF set:**
- `/noticias/`: hero band visible. Featured = post más reciente con featured image. Side = posts #2 y #3. Grid = posts #4-9. Total 9 cards.
- Theme dark: bg negro, texto blanco, cards dark.

**Caso 2 — Page 1 con featured ACF set:**
- Admin asigna `featured_post` a un post específico desde el editor. Esa noticia aparece en el slot featured. Side y grid llenan con los más recientes excluyendo el featured.

**Caso 3 — Page 2:**
- `/noticias/?paged=2`: solo grid 9 (sin hero band). Theme dark.

**Caso 4 — Filtros:**
- `/noticias/?cat=X` (page 1): hero band con featured + side de la categoría X. Grid con los demás.
- `/noticias/?cat=X&paged=2`: solo grid 9 de la categoría X.

**Caso 5 — Featured card hover:**
- Image scale 1.02. No se invierte el bg (a diferencia de F3 section-landing).

---

## 9. Open questions / Pendientes

- **Featured per filtro**: si el admin filtra por categoría X, ¿el featured se filtra también o sigue siendo el ACF set? Propuesta: si hay filtro activo (cat/year/s), el featured se IGNORA y se muestran 9 grid (sin hero). Esto evita confusión cuando el `featured_post` no pertenece a la categoría filtrada.
- **Image gallery del single** (Figma del single muestra 3 imágenes con arrows): se difiere a F4 extras o se resuelve en F4c con campo ACF gallery.
