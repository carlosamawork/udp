# Spec: Buscador del header UDP

**Fecha:** 2026-06-02
**Figma refs:** `3706:24003` (estado vacío), `3706:24011` (estado con texto), `4398:22202` (card resultado)

---

## Resumen

El botón "Buscador" del top-bar abre un panel de búsqueda que reemplaza visualmente el header. El usuario escribe y los resultados aparecen debajo via AJAX (debounce 400ms, sin mínimo de caracteres — busca desde el primer carácter). Los resultados se agrupan en hasta 7 secciones (solo se muestran las que tienen resultados), con un máximo de 10 items por sección.

---

## Archivos

### Nuevos
| Archivo | Responsabilidad |
|---|---|
| `inc/udp-search.php` | Endpoint AJAX + 7 WP_Query |
| `src/js/modules/search.js` | Debounce, fetch, render DOM |
| `src/scss/layouts/_search.scss` | Estilos del panel y cards |

### Modificados
| Archivo | Cambio |
|---|---|
| `template-parts/header/top-bar.php` | Añadir `__search-bar` y mover `udp-search-results` |
| `header.php` | Añadir `<div class="udp-search-results">` justo después de `<header>` |
| `src/js/main.js` | Import + `initSearch()` en domReady |
| `src/scss/main.scss` | `@import "layouts/search"` |
| `functions.php` | `require_once 'inc/udp-search.php'` |

---

## Arquitectura general

**Approach:** WP AJAX custom (`wp_ajax_nopriv_udp_search` + `wp_ajax_udp_search`). Una sola request HTTP por búsqueda, reutiliza el helper `ajax('udp_search', {q})` de `src/js/utils/ajax.js`.

El panel de búsqueda no es un overlay independiente sino una **transformación del top-bar**: al abrir, el `__inner` (menú + logo + trigger) se oculta y aparece el `__search-bar` (input + cerrar) en su lugar, manteniendo la misma altura (84px). Los resultados viven en un `<div>` fijo posicionado justo debajo del header.

---

## PHP — `inc/udp-search.php`

### Registro
```php
add_action('wp_ajax_nopriv_udp_search', 'udp_search_handler');
add_action('wp_ajax_udp_search', 'udp_search_handler');
```

### Handler
1. Verifica nonce (`wp_verify_nonce`, mismo nonce de `starterBS5`)
2. Sanitiza query con `sanitize_text_field($_POST['q'])`
3. Rechaza si `$q === ''` → `wp_send_json_error`
4. Lanza las queries (ver tabla abajo)
5. Filtra secciones vacías
6. `wp_send_json_success(['sections' => $sections])`

### Queries (orden de aparición en resultados)

| id | label | post_type | Filtro extra | max |
|---|---|---|---|---|
| `pages` | Páginas | `page` | `post_parent != 14`, excluye páginas sin título | 10 |
| `facultades` | Facultades | `page` | `post_parent = 14` | 10 |
| `carreras` | Carreras | `carrera-udp` | — | 10 |
| `centros` | Centros | `centro-udp` | — | 10 |
| `noticias` | Noticias | `post` | — | 10 |
| `eventos` | Eventos | `agenda` | — | 10 |
| `calendario` | Calendario Académico | `calendario` | — | 10 |

### WP_Query args (base para todas)
```php
[
  's'                        => $q,
  'posts_per_page'           => 10,
  'no_found_rows'            => true,
  'update_post_term_cache'   => false,
  'update_post_meta_cache'   => false,
]
```

WP `s` busca en `post_title` + `post_content` + `post_excerpt`. No busca en ACF meta.

### Estructura JSON de respuesta
```json
{
  "success": true,
  "data": {
    "sections": [
      {
        "id": "noticias",
        "label": "Noticias",
        "items": [
          { "title": "Título del post", "url": "https://..." }
        ]
      }
    ]
  }
}
```

---

## HTML — `template-parts/header/top-bar.php`

Dentro del `.udp-top-bar`, como hermano de `.udp-top-bar__inner`:

```html
<div class="udp-top-bar__search-bar" hidden>
  <span class="udp-top-bar__search-cursor" aria-hidden="true">|</span>
  <input
    type="search"
    class="udp-top-bar__search-input"
    placeholder="Escribe aquí lo que quieras buscar"
    autocomplete="off"
    aria-label="Buscador"
    aria-controls="udp-search-results"
    aria-expanded="false"
  >
  <button
    class="udp-top-bar__search-close"
    type="button"
    aria-label="Cerrar buscador"
  >
    <!-- SVG × 16×16 -->
  </button>
</div>
```

En `header.php`, justo después del `</header>`:

```html
<div
  id="udp-search-results"
  class="udp-search-results"
  hidden
  aria-live="polite"
  aria-label="Resultados de búsqueda"
></div>
```

---

## JS — `src/js/modules/search.js`

### Estado y selectores
```js
const topBar     = qs('.udp-top-bar');
const trigger    = qs('.udp-top-bar__search');
const searchBar  = qs('.udp-top-bar__search-bar');
const input      = qs('.udp-top-bar__search-input');
const closeBtn   = qs('.udp-top-bar__search-close');
const resultsEl  = qs('#udp-search-results');
```

### Apertura
```
click trigger → topBar.classList.add('udp-search-open')
             → searchBar.hidden = false
             → input.focus()
             → document.body.classList.add('udp-search-overlay')
```

### Cierre
```
click closeBtn | ESC | click fuera del panel →
  topBar.classList.remove('udp-search-open', 'udp-search-has-text')
  searchBar.hidden = true
  resultsEl.hidden = true
  input.value = ''
  document.body.classList.remove('udp-search-overlay')
  trigger.focus()
```

### Búsqueda
```
input event → debounce(400ms)
  if query.length === 0 → ocultar resultados, return
  añadir clase --loading
  cancelar request anterior (AbortController)
  ajax('udp_search', { q: query }) → response
  quitar clase --loading
  if !response.success || sections vacío → mostrar empty state
  else → render(sections)
```

### Transición de color
```
input.addEventListener('input') →
  topBar.classList.toggle('udp-search-has-text', input.value.length > 0)
```

### Render
```js
function render(sections) {
  resultsEl.innerHTML = '';
  sections.forEach(({ label, items }) => {
    // crear <div class="udp-search-results__section">
    //   <p class="udp-search-results__label">label</p>
    //   <div class="udp-search-results__grid">
    //     items.forEach → <a class="udp-search-card" href="url">
    //       <span class="udp-search-card__title">título</span>
    //       <span class="udp-search-card__arrow">→</span>
    //     </a>
  });
  resultsEl.hidden = false;
}
```

---

## SCSS — `src/scss/layouts/_search.scss`

### 1. Estados del top-bar

```scss
.udp-top-bar {
  transition: background-color 0.2s ease;

  &.udp-search-open {
    .udp-top-bar__inner { display: none; }
    .udp-top-bar__search-bar { display: flex; }
  }

  &.udp-search-has-text {
    background-color: #f8f7f4;
    border-bottom-color: $dark-1;

    .udp-top-bar__search-input { color: $dark-1; }
    .udp-top-bar__search-cursor { color: $dark-1; opacity: 0.4; }
    .udp-top-bar__search-close svg { stroke: $dark-1; }
    .udp-top-bar__search-close { border-color: #b0b0b0; }
  }
}
```

### 2. Search bar

```scss
.udp-top-bar__search-bar {
  display: none;
  align-items: center;
  gap: 8px;
  flex: 1;
  height: 100%;
  padding: 0 40px;
}

.udp-top-bar__search-input {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  font-family: $font-family-body;
  font-size: 20px;
  font-weight: 500;
  color: $white;
  &::placeholder { color: rgba($white, 0.3); }
}

.udp-top-bar__search-cursor {
  color: $white;
  opacity: 0.5;
  font-size: 20px;
  user-select: none;
}

.udp-top-bar__search-close {
  width: 50px; height: 50px;
  border-radius: 9999px;
  border: 1px solid #454545;
  background: transparent;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  transition: border-color 0.2s;
  svg { stroke: $white; }
}
```

### 3. Panel de resultados

```scss
.udp-search-results {
  position: fixed;
  top: 84px;
  left: 0; right: 0;
  background: $white;
  z-index: 999;
  max-height: calc(100vh - 84px);
  overflow-y: auto;
  padding: 32px 40px 48px;
  border-bottom: 1px solid $gray-high;

  &[hidden] { display: none; }

  &--loading {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 120px;
    color: $gray-medium;
    font-size: 14px;
  }
}
```

### 4. Sección de resultados

```scss
.udp-search-results__section {
  margin-bottom: 32px;
  &:last-child { margin-bottom: 0; }
}

.udp-search-results__label {
  font-family: $font-family-mono;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: $dark-1;
  margin-bottom: 14px;
}

.udp-search-results__grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;

  @include media-breakpoint-down(lg) { grid-template-columns: repeat(2, 1fr); }
  @include media-breakpoint-down(sm) { grid-template-columns: 1fr; }
}
```

### 5. Card de resultado

```scss
.udp-search-card {
  background: #f8f7f4;
  padding: 20px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  min-height: 120px;
  text-decoration: none;
  transition: background 0.15s;

  &:hover { background: #eceae6; }

  &__title {
    font-family: $font-family-display;
    font-size: 18px;
    font-weight: 500;
    color: $dark-1;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  &__arrow {
    font-size: 18px;
    color: $dark-1;
    margin-top: 12px;
    line-height: 1;
  }
}
```

---

## Body overlay

Cuando el buscador está abierto (`udp-search-overlay` en body), un overlay semitransparente cubre el contenido de la página debajo del panel de resultados. Click en él cierra el buscador.

```scss
body.udp-search-overlay::after {
  content: '';
  position: fixed;
  inset: 84px 0 0 0;
  background: rgba($dark-1, 0.4);
  z-index: 998;
}
```

---

## Accesibilidad

- `aria-live="polite"` en `#udp-search-results` → lectores de pantalla anuncian nuevos resultados
- `aria-label` en input y botón cerrar
- ESC cierra el panel y devuelve focus al trigger
- Cards son `<a>` con href real (no buttons)

---

## Estados vacío / sin resultados

- `query vacío` → panel oculto
- `query ≥ 3 chars, 0 resultados` → texto "No se encontraron resultados para «{query}»" centrado en el panel
- `query ≥ 3 chars, cargando` → texto "Buscando…" o spinner

---

## Notas de implementación

- El CPT centros usa slug `centro-udp` (verificado en `mu-plugins/udp-core/inc/post-types.php`)
- La página Facultades (ID 14) es la referencia para filtrar `post_parent = 14`; si el ID cambia en staging/prod habrá que ajustarlo o usar `get_page_by_path('facultades')`
- `posts_per_page = 10` definido como constante `UDP_SEARCH_MAX_PER_SECTION = 10` en el PHP para facilitar ajustes futuros
- El nonce debe ser el mismo que ya se localiza en `functions.php` como `starterBS5.nonce`
