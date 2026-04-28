# F4b1 — Noticias archive (simple) + single-post — Design Spec

**Status:** Draft, pending implementation plan.
**Phase:** F4b1 (subdivisión de F4 — sub-fase 2 después de F4a).
**Sucesores:** F4b2 (hero band con featured + side cards), F4c (Agenda).

---

## 1. Objetivo

Construir el listado público de Noticias en `/noticias/` y el template de noticia individual.

**Alcance F4b1** (de menor a mayor):
- Template `templates/page-noticias.php` asignable a la página existente "Noticias" (ID 97). Renderiza:
  - Cabecera (breadcrumb `Inicio › Noticias` + título "Noticias")
  - Filtros: dropdown categoría + dropdown año + search input
  - Grid 2 columnas × N filas de cards `card-noticia` en variante **horizontal grande** (image-left 201×275 + texto-right 433×275)
  - Paginación con `paginate_links()`
- Template `single-post.php` que reemplaza el scaffold genérico actual (`single.php` queda como fallback). Renderiza:
  - Cabecera light: link "← Volver a Noticias" + H1 título + meta (fecha + eyebrow categoría) + featured image
  - Body: `the_content()` en columna centrada (~480px), tipografía body 16px line-height 24px
  - Sección "Te podría interesar": 3 posts relacionados (misma categoría primaria, excluyendo el actual) usando `card-noticia` light theme 3col
  - Botones de share verticales floating a la derecha (links a copy URL + Facebook + Twitter/X + WhatsApp + LinkedIn — inline SVG)
- Nueva variante SCSS `card-noticia--horizontal` con image-left 201×275 (vs `--list` que usa 96×96).

**Out of scope F4b1:**
- Featured/destacada card grande en la zona hero del archive (con overlay encima de la imagen) → F4b2.
- 2 side compact cards al lado de la featured → F4b2.
- Image gallery dentro del single (campo ACF gallery) → F4b2 o más adelante. Solo se renderiza la featured image en F4b1.
- Navegación previous/next entre posts → no es parte del Figma.
- Comments — el single los excluye (la UDP no comenta noticias).

---

## 2. Routing — por qué un page template y no `archive-post.php`

WordPress no enruta `/noticias/` como archive de post type por defecto: lo trata como page (post_type=page). La opción canónica WP sería marcar la página en *Settings → Reading → Posts page*, lo que dispara `home.php` automáticamente. Se descarta esto porque colisiona con F9 (Home en `/`) cuando `show_on_front=posts`.

Solución: page template asignable. La página "Noticias" (ID 97) recibe `_wp_page_template = templates/page-noticias.php` desde el dropdown del editor. El template internamente ejecuta `WP_Query` con filtros + paginación. El URL queda `/noticias/?page=2&cat=12&year=2024&s=palabra`.

`single-post.php` sigue la jerarquía nativa: WP lo prefiere sobre `single.php` para post_type=post. Sin tocar settings.

---

## 3. Referencias

- Figma archive Noticias: `4QlgGMlzNR9Ye344bAFuye`, nodeId `3706:20836`. **Sub-zona en scope F4b1**: filtros (top, y=344), grid principal (y=660-1635, 2 cols × 3 rows). Out of scope: hero band (y=428-1085).
- Figma single Noticia: nodeId `3706:21278`.
- F4a entregables que F4b1 reutiliza: `udp_query_cards()`, `udp_card_data_from_post()`, `template-parts/blocks/parts/card-noticia.php`, SCSS `_card-grid.scss`.
- Spec F4a: `docs/superpowers/specs/2026-04-28-f4a-block-card-grid-design.md`.

---

## 4. Inventario de archivos

**Crear:**
- `wp-content/themes/starter-theme/templates/page-noticias.php` — page template (asignable al editor).
- `wp-content/themes/starter-theme/template-parts/archive/noticias-filters.php` — partial filtros (form GET con dropdowns + search).
- `wp-content/themes/starter-theme/template-parts/archive/pagination.php` — partial paginación reutilizable (sirve también a F4c).
- `wp-content/themes/starter-theme/single-post.php` — single de post.
- `wp-content/themes/starter-theme/template-parts/single/post-hero.php` — hero del single (back link + title + meta + featured image).
- `wp-content/themes/starter-theme/template-parts/single/post-share.php` — botones share floating.
- `wp-content/themes/starter-theme/template-parts/single/post-related.php` — sección "Te podría interesar" (usa F4a card-noticia 3col).
- `wp-content/themes/starter-theme/src/scss/templates/_noticias-archive.scss` — estilos del archive (filtros + layout 2col).
- `wp-content/themes/starter-theme/src/scss/templates/_noticias-single.scss` — estilos del single (hero light + content + share + related).

**Modificar:**
- `wp-content/themes/starter-theme/src/scss/blocks/_card-grid.scss` — añadir modifier `.udp-card-noticia--horizontal` para la card de archive.
- `wp-content/themes/starter-theme/src/scss/main.scss` — `@import "templates/noticias-archive";` y `@import "templates/noticias-single";`.

**A NO tocar:** `single.php` queda como fallback genérico para CPTs futuros sin template propio (academico, contacto-udp, etc.). `archive.php` no se toca.

---

## 5. ACF — campos nuevos requeridos

Ninguno en F4b1. La data del archive sale de:
- `category` taxonomy (filtros + eyebrow color hardcoded yellow)
- `post_date` (filtro año + fecha display)
- `post_title`, `post_content`, featured image (single hero + body)
- `WP_Query` con `s=` para búsqueda

Diferido para F4b2: campo ACF `featured_post` (post_object) en options page para curaduría editorial del destacado.

---

## 6. `templates/page-noticias.php` — Archive

Lee filtros desde `$_GET`, ejecuta `udp_query_cards()` con `need_pagination=true`, renderiza header + filtros + grid + paginación.

```html
<article class="udp-noticias-archive">
  <header class="udp-noticias-archive__header">
    <!-- breadcrumb partial F3 -->
    <h1 class="udp-noticias-archive__title">Noticias</h1>
  </header>

  <hr class="udp-noticias-archive__separator" />

  <!-- filters partial -->
  <div class="udp-noticias-archive__filters">…</div>

  <hr class="udp-noticias-archive__separator" />

  <ul class="udp-noticias-archive__list">
    <li class="udp-noticias-archive__item">
      <!-- card-noticia variant --horizontal --light -->
    </li>
    ...
  </ul>

  <!-- pagination partial -->
  <nav class="udp-noticias-archive__pagination">…</nav>
</article>
```

**Lógica PHP:**
- Lee `$_GET['cat']` (term_id), `$_GET['year']` (4 dígitos), `$_GET['s']` (search), `$_GET['paged']` (default `get_query_var('paged') ?: 1`).
- Construye `$args` para `udp_query_cards()`:
  - `source = 'post'`
  - `taxonomies = [(int) cat]` si está
  - `limit = 6` (3 filas × 2 cols, matchea el main grid del Figma). En F4b2 subiremos a 9 absorbiendo 3 cards en el hero band (1 featured + 2 side).
  - `paged`
  - `need_pagination = true`
- Para los filtros de **año** y **búsqueda** — `udp_query_cards()` no los soporta. Solución: usar `WP_Query` directamente con `meta_query` (year via `date_query`) y `s` (search), después mapear con `udp_card_data_from_post()`. F4b1 introduce un helper auxiliar `udp_query_noticias($filters): array` (en `inc/udp-cards.php`) que orquesta:
  - WP_Query con `post_type=post`, `posts_per_page=6`, `paged`, `tax_query` (cat), `date_query` (year), `s` (search), `orderby=date DESC`.
  - Mapea posts → cards con `udp_card_data_from_post()`, filtra nulls.
  - Devuelve `{cards, total, max_pages, paged}` mismo shape que `udp_query_cards`.

Decision: `udp_query_cards()` queda intocado (sirve al bloque flexible). `udp_query_noticias()` es el wrapper que soporta archive-only filtros (year, search). Reutilizable por F4b2 y F4c (la versión Agenda tendrá su propio wrapper similar).

---

## 7. `template-parts/archive/noticias-filters.php`

Form GET sin JS — dropdowns nativos `<select>` con auto-submit en change vía pequeño handler inline (o `<form>` con submit button visible). Mi propuesta: `<form>` con submit oculto + JS inline que dispara submit en `change`.

```html
<form class="udp-archive-filters" method="get" action="<permalink Noticias page>">
  <div class="udp-archive-filters__group">
    <label for="cat" class="visually-hidden">Selecciona categoría</label>
    <select id="cat" name="cat" class="udp-archive-filters__select">
      <option value="">Selecciona categoría</option>
      <!-- foreach get_categories() -->
    </select>
  </div>
  <div class="udp-archive-filters__group">
    <label for="year" class="visually-hidden">Selecciona año</label>
    <select id="year" name="year" class="udp-archive-filters__select">
      <option value="">Selecciona año</option>
      <!-- foreach distinct YEAR(post_date) -->
    </select>
  </div>
  <div class="udp-archive-filters__group udp-archive-filters__group--search">
    <label for="s" class="visually-hidden">Buscar</label>
    <input id="s" type="search" name="s" placeholder="Buscar" value="<?= esc_attr($_GET['s'] ?? '') ?>" />
    <button type="submit" class="udp-archive-filters__submit"><svg search icon /></button>
  </div>
</form>
<script>
  // Auto-submit on dropdown change
  document.querySelectorAll('.udp-archive-filters__select').forEach(s => {
    s.addEventListener('change', () => s.form.submit());
  });
</script>
```

Estado activo (selected) se preserva mediante `selected="selected"` en el option matching `$_GET['cat']` o `$_GET['year']`.

**Años disponibles:** query SQL directa al theme load (cacheada via `wp_cache_*` o `transient`):

```php
function udp_get_post_years(): array {
    $cache = get_transient( 'udp_post_years' );
    if ( $cache !== false ) return $cache;
    global $wpdb;
    $years = $wpdb->get_col( "SELECT DISTINCT YEAR(post_date) FROM {$wpdb->posts} WHERE post_type='post' AND post_status='publish' ORDER BY YEAR(post_date) DESC" );
    set_transient( 'udp_post_years', $years, DAY_IN_SECONDS );
    return $years;
}
```

---

## 8. `template-parts/archive/pagination.php`

Reutilizable por F4b1, F4b2, F4c. Wrapper de `paginate_links()` con clases UDP.

```html
<nav class="udp-pagination" aria-label="Paginación">
  <ul class="udp-pagination__list">
    <!-- paginate_links output: prev / 1 / 2 / … / N / next, themed -->
  </ul>
</nav>
```

```php
$args = array(
    'total'     => $max_pages,
    'current'   => max( 1, $paged ),
    'mid_size'  => 1,
    'prev_text' => '<svg chevron-left />',
    'next_text' => '<svg chevron-right />',
    'type'      => 'list',
);
echo paginate_links( $args );
```

`paginate_links` con `type='list'` emite `<ul class="page-numbers">`. Re-clasificamos vía `add_filter('paginate_links_output', ...)` o hacemos manual loop. Mi propuesta: usar `'type' => 'array'` y bucle propio para emitir la estructura UDP exacta — `<li class="udp-pagination__item">` con `--current`, `--prev`, `--next` modifiers. Más control.

---

## 9. `single-post.php`

Light theme. Estructura:

```html
<article id="post-N" class="udp-single-post">
  <!-- post-hero partial -->
  <header class="udp-single-post__hero">
    <a class="udp-single-post__back" href="/noticias/">← Volver a Noticias</a>
    <h1 class="udp-single-post__title">…</h1>
    <div class="udp-single-post__meta">
      <span class="udp-single-post__date-label">Fecha</span>
      <time class="udp-single-post__date" datetime="ISO">DD / MM / YYYY</time>
      <span class="udp-card-noticia__eyebrow udp-card-noticia__eyebrow--yellow">CATEGORÍA</span>
    </div>
    <figure class="udp-single-post__featured">
      <img src=… alt=… />
    </figure>
  </header>

  <!-- post-share partial: floating right side, vertical -->
  <aside class="udp-single-post__share">…</aside>

  <!-- main body -->
  <div class="udp-single-post__body">
    <?php the_content(); ?>
  </div>

  <!-- post-related partial -->
  <section class="udp-single-post__related">
    <h2 class="udp-single-post__related-title">Te podría interesar</h2>
    <ul class="udp-card-grid__list udp-card-grid--3col">
      <!-- 3 × card-noticia --light --horizontal=false -->
    </ul>
  </section>
</article>
```

**Related posts logic** (en `template-parts/single/post-related.php`):

```php
$current_id = get_the_ID();
$primary_term = get_the_terms( $current_id, 'category' );
$primary_term_id = $primary_term ? $primary_term[0]->term_id : 0;

$args = array(
    'post_type'      => 'post',
    'posts_per_page' => 3,
    'post__not_in'   => array( $current_id ),
    'orderby'        => 'date',
    'order'          => 'DESC',
    'no_found_rows'  => true,
);
if ( $primary_term_id ) {
    $args['tax_query'] = array(
        array( 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => array( $primary_term_id ) ),
    );
}
$q = new WP_Query( $args );
// Si menos de 3 con la misma categoría, fallback a posts más recientes (rellenar hasta 3).
```

**Share buttons** (en `template-parts/single/post-share.php`):

5 iconos verticales, cada uno un link:
- **Copy URL** — JS inline que copia `window.location.href` al clipboard, muestra "Copiado" tooltip
- **Facebook** — `https://www.facebook.com/sharer/sharer.php?u={url}`
- **X (Twitter)** — `https://twitter.com/intent/tweet?url={url}&text={title}`
- **WhatsApp** — `https://api.whatsapp.com/send?text={title}%20{url}`
- **LinkedIn** — `https://www.linkedin.com/sharing/share-offsite/?url={url}`

Posicionamiento: `position: sticky; top: 100px; right: 40px;` (alineado a la derecha del viewport, debajo del header).

---

## 10. SCSS — nueva variante `card-noticia--horizontal`

Modifier en `_card-grid.scss`:

```scss
.udp-card-noticia--horizontal {
    flex-direction: row;
    gap: $space-2xl;  // 30px Figma
    align-items: flex-start;

    .udp-card-noticia__media {
        flex: 0 0 201px;
        width: 201px;
        height: 275px;
        aspect-ratio: 201 / 275;  // override del 4/3 default
    }

    .udp-card-noticia__body {
        flex: 1;
        padding-top: 0;
        gap: $space-sm;  // 16px entre eyebrow / title / desc / fecha
    }

    .udp-card-noticia__title {
        font-size: 20px;  // más grande que default 18px
        line-height: 1.3;
        -webkit-line-clamp: 3;  // 3 líneas en archive (vs 2 en grid)
    }

    @include media-down(md) {
        flex-direction: column;
        gap: $space-md;
        .udp-card-noticia__media {
            width: 100%;
            height: auto;
            aspect-ratio: 16 / 9;
        }
    }
}
```

El partial `card-noticia.php` recibe `theme` y `card`. Para activar el modifier `horizontal`, el caller añade un attr `$args['variant'] = 'horizontal'`. El partial:

```php
$variant = isset( $args['variant'] ) && in_array( $args['variant'], array( 'horizontal' ), true ) ? $args['variant'] : '';
$class = 'udp-card-noticia udp-card-noticia--' . $theme . ( $variant ? ' udp-card-noticia--' . $variant : '' );
```

---

## 11. Verificación E2E (criterios de aceptación)

**Caso 1 — Archive básico:**
- Asignar template `templates/page-noticias.php` a la página "Noticias" (ID 97).
- Visitar `/noticias/`: HTTP 200, breadcrumb `Inicio › Noticias`, título "Noticias", filtros (3 dropdowns + search), 6 cards horizontales en grid 2-col light theme, paginación abajo derecha.
- Click "página 2": URL pasa a `/noticias/page/2/` (o `?paged=2`), nuevos 6 posts cargan.
- Mobile: cards apiladas vertical (image arriba, texto abajo), filtros stack vertical.

**Caso 2 — Filtro categoría:**
- Seleccionar categoría "Universidad" del dropdown → submit auto.
- URL `/noticias/?cat=12`: solo posts de esa categoría aparecen, count en paginación reflectado.
- El dropdown muestra "Universidad" como selected.

**Caso 3 — Filtro año:**
- Seleccionar 2024 → URL `/noticias/?year=2024`: solo posts publicados en 2024.

**Caso 4 — Búsqueda:**
- Escribir "feria" en search → URL `/noticias/?s=feria`: posts cuyos títulos o contenido matchean.

**Caso 5 — Filtros combinados:**
- Categoría + año + search simultáneos → URL con los 3 params, query con AND lógico.

**Caso 6 — Single post:**
- Visitar un post individual: header light con back link, title, fecha, eyebrow categoría, featured image. Body con párrafos formateados. 3 related cards al final. Share buttons floating a la derecha.
- Categoría primary del post se refleja en el eyebrow (texto + color yellow hardcoded).

**Caso 7 — Related fallback:**
- Si la categoría primaria tiene < 3 posts (excluyendo current), rellenar con los 3 más recientes globales.

**No se incluye:** tests unitarios PHP (consistente con F1-F4a).

---

## 12. Cobertura

| Spec maestro requirement | Cubierto en F4b1 |
|---|---|
| `archive-post.php` (nombrado en spec, en realidad `templates/page-noticias.php` por routing) | ✓ Sec. 6 |
| `single-post.php` | ✓ Sec. 9 |
| Hero + filtros + grid + paginación | Filtros + grid + paginación ✓. **Hero con featured/destacada** → F4b2 |
| Hero noticia + contenido + meta + relacionadas | ✓ Sec. 9 |
| `block_card_grid` con variantes | F4a ya hecho |
| Filtros por facultad/área | Aproximación: `category` taxonomy. Si la UDP usa `area` para filtrado real, ajustar en implementación trivialmente |
| Paginación | ✓ Sec. 8 |

---

## 13. Open questions / TODOs futuros (para el plan)

- **`paged` URL rewrite**: usar `/noticias/page/2/` (pretty) o `?paged=2` (query). Mi propuesta: pretty (WP por defecto). Requiere `pre_get_posts` filter para que WP entienda `paged` en page templates.
- **Taxonomía de filtrado**: F4b1 usa `category` (default WP). Si el cliente usa `area` o `facultad` para clasificar noticias, cambiar la taxonomía del dropdown — coste 1 línea.
- **Share button copy URL**: si el navegador no soporta `navigator.clipboard` (Safari < 13.1), fallback a textarea + execCommand. Mi propuesta: detectar y mostrar tooltip "Copiado" o "No soportado".
- **Image gallery del single** (Figma muestra 3 imágenes con arrows después del body): F4b2 cuando se añada el campo ACF gallery al CPT post.
- **Featured/destacada del archive** (zona hero del Figma): F4b2.
