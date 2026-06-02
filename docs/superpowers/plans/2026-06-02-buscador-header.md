# Buscador del Header — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implementar el buscador del header — click en "Buscador" transforma el top-bar en un panel de búsqueda AJAX con debounce que muestra resultados agrupados por tipo (7 secciones).

**Architecture:** Endpoint WP AJAX custom (`udp_search_handler`) lanza hasta 7 WP_Query y devuelve JSON `{sections}`. JS vanilla con debounce 400ms usa el helper `ajax()` existente, transforma el top-bar y renderiza cards DOM. El panel de resultados es `position:fixed` bajo el header.

**Tech Stack:** PHP 8.4, WordPress WP_Query, vanilla JS (ES modules), SCSS/Vite 6, WP AJAX (`admin-ajax.php`).

---

## Mapa de archivos

| Archivo | Acción | Responsabilidad |
|---|---|---|
| `inc/udp-search.php` | Crear | Endpoint AJAX + 7 WP_Query |
| `functions.php` | Modificar (línea 154) | `require_once` del nuevo inc |
| `template-parts/header/top-bar.php` | Modificar | Añadir `__search-bar` (input + cerrar) dentro de `.udp-top-bar` |
| `header.php` | Modificar (tras `</header>`) | Añadir `<div id="udp-search-results">` |
| `src/scss/layouts/_search.scss` | Crear | Estilos del panel, transiciones y cards |
| `src/scss/main.scss` | Modificar (línea 49) | `@import "layouts/search"` |
| `src/js/modules/search.js` | Crear | Apertura/cierre, debounce, fetch, render DOM |
| `src/js/main.js` | Modificar (línea 84) | import + `initSearch()` en domReady |

---

## Task 1: PHP endpoint (`inc/udp-search.php`)

**Files:**
- Create: `inc/udp-search.php`
- Modify: `functions.php:154`

- [ ] **Step 1.1: Crear `inc/udp-search.php`**

```php
<?php
/**
 * Buscador — endpoint AJAX
 *
 * Acción: udp_search
 * POST params: q (string), nonce (string)
 * Respuesta: { success: true, data: { sections: [{id, label, items: [{title, url}]}] } }
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

define( 'UDP_SEARCH_MAX_PER_SECTION', 10 );

add_action( 'wp_ajax_nopriv_udp_search', 'udp_search_handler' );
add_action( 'wp_ajax_udp_search',        'udp_search_handler' );

function udp_search_handler(): void {
    if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'starter_bs5_nonce' ) ) {
        wp_send_json_error( [ 'message' => 'Invalid nonce' ], 403 );
    }

    $q = sanitize_text_field( wp_unslash( $_POST['q'] ?? '' ) );

    if ( $q === '' ) {
        wp_send_json_error( [ 'message' => 'Empty query' ], 400 );
    }

    $base = [
        's'                      => $q,
        'posts_per_page'         => UDP_SEARCH_MAX_PER_SECTION,
        'no_found_rows'          => true,
        'update_post_term_cache' => false,
        'update_post_meta_cache' => false,
    ];

    // Resolvemos el ID de la página Facultades por path (resiliente entre entornos)
    $fac_page = get_page_by_path( 'facultades' );
    $fac_pid  = $fac_page ? (int) $fac_page->ID : 14;

    $definitions = [
        [ 'pages',      'Páginas',             'page',        [ 'post_parent__not_in' => [ $fac_pid ] ] ],
        [ 'facultades', 'Facultades',           'page',        [ 'post_parent'         => $fac_pid ] ],
        [ 'carreras',   'Carreras',             'carrera-udp', [] ],
        [ 'centros',    'Centros',              'centro-udp',  [] ],
        [ 'noticias',   'Noticias',             'post',        [] ],
        [ 'eventos',    'Eventos',              'agenda',      [] ],
        [ 'calendario', 'Calendario Académico', 'calendario',  [] ],
    ];

    $sections = [];

    foreach ( $definitions as [ $id, $label, $post_type, $extra ] ) {
        $query = new WP_Query( array_merge( $base, [ 'post_type' => $post_type ], $extra ) );

        if ( ! $query->have_posts() ) {
            continue;
        }

        $items = [];
        foreach ( $query->posts as $post ) {
            $title = get_the_title( $post );
            if ( ! $title ) {
                continue;
            }
            $items[] = [
                'title' => $title,
                'url'   => get_permalink( $post ),
            ];
        }

        if ( ! empty( $items ) ) {
            $sections[] = compact( 'id', 'label', 'items' );
        }
    }

    wp_reset_postdata();
    wp_send_json_success( [ 'sections' => $sections ] );
}
```

- [ ] **Step 1.2: Añadir require en `functions.php` (tras línea 154)**

Añadir justo después de la línea `require_once STARTER_BS5_DIR . '/inc/udp-institucional.php';`:

```php
require_once STARTER_BS5_DIR . '/inc/udp-search.php';
```

- [ ] **Step 1.3: PHP lint**

```bash
/Applications/MAMP/bin/php/php8.4.17/bin/php -l inc/udp-search.php
```

Salida esperada: `No syntax errors detected`

- [ ] **Step 1.4: Smoke test del endpoint**

```bash
NONCE=$(/Applications/MAMP/bin/php/php8.4.17/bin/php -r "
require '/Applications/MAMP/htdocs/udp/cms/wp-load.php';
echo wp_create_nonce('starter_bs5_nonce');
")

curl -s -X POST "http://localhost:8888/udp/cms/wp-admin/admin-ajax.php" \
  -d "action=udp_search&q=udp&nonce=$NONCE" | python3 -m json.tool | head -30
```

Salida esperada: JSON con `"success": true` y al menos una sección con items.

```bash
# Test query vacía → debe devolver error
curl -s -X POST "http://localhost:8888/udp/cms/wp-admin/admin-ajax.php" \
  -d "action=udp_search&q=&nonce=$NONCE" | python3 -m json.tool
```

Salida esperada: `"success": false`

- [ ] **Step 1.5: Commit**

```bash
git add inc/udp-search.php functions.php
git commit -m "feat(search): endpoint AJAX udp_search — 7 WP_Query, respuesta JSON secciones"
```

---

## Task 2: Markup HTML

**Files:**
- Modify: `template-parts/header/top-bar.php`
- Modify: `header.php`

- [ ] **Step 2.1: Añadir `__search-bar` en `top-bar.php`**

Al final de `top-bar.php`, justo antes del `</div>` de cierre de `.udp-top-bar`, añadir:

```php
	<div class="udp-top-bar__search-bar" hidden>
		<span class="udp-top-bar__search-cursor" aria-hidden="true">|</span>
		<input
			type="search"
			class="udp-top-bar__search-input"
			placeholder="<?php esc_attr_e( 'Escribe aquí lo que quieras buscar', 'starter-theme' ); ?>"
			autocomplete="off"
			aria-label="<?php esc_attr_e( 'Buscador', 'starter-theme' ); ?>"
			aria-controls="udp-search-results"
			aria-expanded="false"
		>
		<button
			type="button"
			class="udp-top-bar__search-close"
			aria-label="<?php esc_attr_e( 'Cerrar buscador', 'starter-theme' ); ?>"
		>
			<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
				<line x1="3" y1="3" x2="13" y2="13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
				<line x1="13" y1="3" x2="3" y2="13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
			</svg>
		</button>
	</div>
```

El archivo completo resultante debe tener la estructura:
```
<div class="udp-top-bar">
    <div class="udp-top-bar__inner">
        ... (menú + logo + trigger existentes, sin cambios)
    </div>
    <div class="udp-top-bar__search-bar" hidden>
        ... (nuevo)
    </div>
</div>
```

- [ ] **Step 2.2: Añadir panel de resultados en `header.php`**

En `header.php`, entre `</header>` (línea 28) y el `get_template_part` del mega-menu (línea 30), insertar:

```php
<div
	id="udp-search-results"
	class="udp-search-results"
	hidden
	aria-live="polite"
	aria-label="<?php esc_attr_e( 'Resultados de búsqueda', 'starter-theme' ); ?>"
></div>
```

- [ ] **Step 2.3: PHP lint de ambos archivos**

```bash
/Applications/MAMP/bin/php/php8.4.17/bin/php -l template-parts/header/top-bar.php
/Applications/MAMP/bin/php/php8.4.17/bin/php -l header.php
```

Salida esperada (ambos): `No syntax errors detected`

- [ ] **Step 2.4: Verificar markup en HTTP**

```bash
curl -s "http://localhost:8888/udp/?theme=new" | grep -c "udp-top-bar__search-bar"
```

Salida esperada: `1`

```bash
curl -s "http://localhost:8888/udp/?theme=new" | grep -c "udp-search-results"
```

Salida esperada: `1`

- [ ] **Step 2.5: Commit**

```bash
git add template-parts/header/top-bar.php header.php
git commit -m "feat(search): markup — search-bar en top-bar + panel de resultados en header"
```

---

## Task 3: SCSS

**Files:**
- Create: `src/scss/layouts/_search.scss`
- Modify: `src/scss/main.scss:49`

- [ ] **Step 3.1: Crear `src/scss/layouts/_search.scss`**

```scss
// =============================================================================
// Buscador del header
// Estados: .udp-search-open (panel abierto) + .udp-search-has-text (fondo beige)
// =============================================================================

// ── Overlay de página ────────────────────────────────────────────────────────

body.udp-search-overlay::after {
  content: '';
  position: fixed;
  inset: 84px 0 0 0;
  background: rgba($dark-1, 0.4);
  z-index: 998;
  pointer-events: none;
}

// ── Estados del top-bar ──────────────────────────────────────────────────────

.udp-top-bar {
  transition: background-color 0.2s ease;

  &.udp-search-open {
    .udp-top-bar__inner {
      display: none;
    }

    .udp-top-bar__search-bar {
      display: flex;
    }
  }

  &.udp-search-has-text {
    background-color: #f8f7f4;
    border-bottom-color: $dark-1;

    .udp-top-bar__search-input {
      color: $dark-1;

      &::placeholder {
        color: rgba($dark-1, 0.3);
      }
    }

    .udp-top-bar__search-cursor {
      color: $dark-1;
      opacity: 0.4;
    }

    .udp-top-bar__search-close {
      border-color: #b0b0b0;

      svg {
        stroke: $dark-1;
      }
    }
  }
}

// ── Search bar ───────────────────────────────────────────────────────────────

.udp-top-bar__search-bar {
  display: none;
  align-items: center;
  gap: 8px;
  width: 100%;
  height: 100%;
  padding: 0 40px;

  @include media-breakpoint-down(md) {
    padding: 0 16px;
  }
}

.udp-top-bar__search-cursor {
  color: $white;
  opacity: 0.5;
  font-size: 20px;
  line-height: 1;
  user-select: none;
  flex-shrink: 0;
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
  min-width: 0;

  &::placeholder {
    color: rgba($white, 0.3);
  }

  &::-webkit-search-cancel-button {
    display: none;
  }
}

.udp-top-bar__search-close {
  width: 50px;
  height: 50px;
  border-radius: 9999px;
  border: 1px solid #454545;
  background: transparent;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: border-color 0.2s, background-color 0.2s;

  svg {
    stroke: $white;
    transition: stroke 0.2s;
  }

  &:hover {
    background-color: rgba($white, 0.1);
  }
}

// ── Panel de resultados ──────────────────────────────────────────────────────

.udp-search-results {
  position: fixed;
  top: 84px;
  left: 0;
  right: 0;
  background: $white;
  z-index: 999;
  max-height: calc(100vh - 84px);
  overflow-y: auto;
  padding: 32px 40px 48px;
  border-bottom: 1px solid $gray-high;

  &[hidden] {
    display: none;
  }

  @include media-breakpoint-down(md) {
    padding: 24px 16px 40px;
  }
}

// ── Estados loading / empty ───────────────────────────────────────────────────

.udp-search-results__loading,
.udp-search-results__empty {
  font-family: $font-family-body;
  font-size: 16px;
  color: $gray-medium;
  padding: 24px 0;
  text-align: center;

  strong {
    color: $dark-1;
    font-weight: 600;
  }
}

// ── Secciones ────────────────────────────────────────────────────────────────

.udp-search-results__section {
  margin-bottom: 32px;

  &:last-child {
    margin-bottom: 0;
  }
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

  @include media-breakpoint-down(lg) {
    grid-template-columns: repeat(2, 1fr);
  }

  @include media-breakpoint-down(sm) {
    grid-template-columns: 1fr;
  }
}

// ── Card de resultado ────────────────────────────────────────────────────────

.udp-search-card {
  background: #f8f7f4;
  padding: 20px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  min-height: 120px;
  text-decoration: none;
  transition: background-color 0.15s;

  &:hover {
    background-color: #eceae6;
  }

  @media (prefers-reduced-motion: reduce) {
    transition: none;
  }

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

- [ ] **Step 3.2: Añadir import en `main.scss` (tras `@import "layouts/mega-menu"`)**

```scss
@import "layouts/search";
```

- [ ] **Step 3.3: Build**

```bash
npm run build 2>&1 | tail -5
```

Salida esperada: build exitoso sin errores. Verificar:

```bash
grep -c "udp-search-card" dist/css/main.css
```

Salida esperada: `1`

- [ ] **Step 3.4: Commit**

```bash
git add src/scss/layouts/_search.scss src/scss/main.scss
git commit -m "feat(search): SCSS — panel, transiciones dark/beige, cards resultados"
```

---

## Task 4: JS module

**Files:**
- Create: `src/js/modules/search.js`
- Modify: `src/js/main.js`

- [ ] **Step 4.1: Crear `src/js/modules/search.js`**

```js
/**
 * Buscador del header — panel AJAX con debounce
 *
 * Apertura:  click en .udp-top-bar__search
 * Cierre:    click en __search-close | ESC | click fuera del panel
 * Búsqueda:  debounce 400ms desde primer carácter, cancela request anterior
 *
 * @package Starter_Theme
 */
import { qs } from '@utils/dom';
import { ajax } from '@utils/ajax';

let debounceTimer    = null;
let abortController  = null;

export function initSearch() {
    const topBar    = qs( '.udp-top-bar' );
    const trigger   = qs( '.udp-top-bar__search' );
    const searchBar = qs( '.udp-top-bar__search-bar' );
    const input     = qs( '.udp-top-bar__search-input' );
    const closeBtn  = qs( '.udp-top-bar__search-close' );
    const results   = qs( '#udp-search-results' );

    if ( ! topBar || ! trigger || ! searchBar || ! input || ! closeBtn || ! results ) return;

    // ── Apertura ──────────────────────────────────────────────────────────

    trigger.addEventListener( 'click', openSearch );

    function openSearch() {
        topBar.classList.add( 'udp-search-open' );
        searchBar.hidden = false;
        document.body.classList.add( 'udp-search-overlay' );
        input.focus();
    }

    // ── Cierre ────────────────────────────────────────────────────────────

    closeBtn.addEventListener( 'click', closeSearch );

    document.addEventListener( 'keydown', ( e ) => {
        if ( e.key === 'Escape' && topBar.classList.contains( 'udp-search-open' ) ) {
            closeSearch();
        }
    } );

    document.addEventListener( 'click', ( e ) => {
        if (
            topBar.classList.contains( 'udp-search-open' ) &&
            ! topBar.contains( e.target ) &&
            ! results.contains( e.target )
        ) {
            closeSearch();
        }
    } );

    function closeSearch() {
        topBar.classList.remove( 'udp-search-open', 'udp-search-has-text' );
        searchBar.hidden = true;
        results.hidden   = true;
        results.innerHTML = '';
        input.value = '';
        document.body.classList.remove( 'udp-search-overlay' );
        clearTimeout( debounceTimer );
        if ( abortController ) abortController.abort();
        trigger.focus();
    }

    // ── Input ─────────────────────────────────────────────────────────────

    input.addEventListener( 'input', () => {
        const q = input.value.trim();
        topBar.classList.toggle( 'udp-search-has-text', q.length > 0 );

        clearTimeout( debounceTimer );
        if ( abortController ) abortController.abort();

        if ( q === '' ) {
            results.hidden    = true;
            results.innerHTML = '';
            return;
        }

        debounceTimer = setTimeout( () => fetchResults( q ), 400 );
    } );

    // ── Fetch ─────────────────────────────────────────────────────────────

    async function fetchResults( q ) {
        results.innerHTML = '<p class="udp-search-results__loading">Buscando…</p>';
        results.hidden    = false;

        abortController = new AbortController();

        const response = await ajax(
            'udp_search',
            { q },
            { signal: abortController.signal }
        );

        if ( ! response ) return; // abortado o error de red

        if ( ! response.success || ! response.data.sections.length ) {
            results.innerHTML = `<p class="udp-search-results__empty">No se encontraron resultados para <strong>«${ escHtml( q ) }»</strong></p>`;
            return;
        }

        render( response.data.sections );
    }

    // ── Render ────────────────────────────────────────────────────────────

    function render( sections ) {
        results.innerHTML = '';

        sections.forEach( ( { label, items } ) => {
            const section = document.createElement( 'div' );
            section.className = 'udp-search-results__section';

            const heading = document.createElement( 'p' );
            heading.className   = 'udp-search-results__label';
            heading.textContent = label;
            section.appendChild( heading );

            const grid = document.createElement( 'div' );
            grid.className = 'udp-search-results__grid';

            items.forEach( ( { title, url } ) => {
                const card = document.createElement( 'a' );
                card.className = 'udp-search-card';
                card.href      = url;
                card.innerHTML = `<span class="udp-search-card__title">${ escHtml( title ) }</span><span class="udp-search-card__arrow" aria-hidden="true">→</span>`;
                grid.appendChild( card );
            } );

            section.appendChild( grid );
            results.appendChild( section );
        } );

        results.hidden = false;
    }

    // ── Utils ─────────────────────────────────────────────────────────────

    function escHtml( str ) {
        return str
            .replace( /&/g, '&amp;' )
            .replace( /</g, '&lt;'  )
            .replace( />/g, '&gt;'  )
            .replace( /"/g, '&quot;' );
    }
}
```

- [ ] **Step 4.2: Añadir import y llamada en `src/js/main.js`**

Al final del bloque de imports (junto a los demás módulos del header), añadir:

```js
import { initSearch } from '@modules/search';
```

Dentro del callback `domReady(() => { ... })`, añadir tras `initMegaMenu()`:

```js
initSearch();
```

- [ ] **Step 4.3: Build**

```bash
npm run build 2>&1 | tail -5
```

Salida esperada: build exitoso. Verificar que el módulo se compiló:

```bash
grep -c "initSearch\|udp_search" dist/js/main.*.js
```

Salida esperada: `1` o más

- [ ] **Step 4.4: Commit**

```bash
git add src/js/modules/search.js src/js/main.js
git commit -m "feat(search): JS module — apertura/cierre, debounce AJAX, render secciones"
```

---

## Task 5: Verificación E2E

- [ ] **Step 5.1: Verificar markup completo en HTTP**

```bash
curl -s "http://localhost:8888/udp/?theme=new&nocache=$(date +%s)" | grep -E "udp-top-bar__search-bar|udp-search-results|udp-top-bar__search-input" | head -5
```

Salida esperada: 3 líneas con las clases respectivas.

- [ ] **Step 5.2: Verificar endpoint desde browser**

Abrir `http://localhost:8888/udp/?theme=new`, abrir DevTools → Network.

1. Click en botón "Buscador" → top-bar se transforma (menú y logo desaparecen, aparece input)
2. Escribir una letra (ej. "u") → al cabo de 400ms aparece request POST a `admin-ajax.php` con `action=udp_search`
3. El fondo del top-bar cambia de oscuro a beige
4. Aparece panel blanco con resultados agrupados por sección
5. Borrar el texto → panel se oculta, top-bar vuelve a oscuro
6. Escribir algo sin resultados (ej. "zzzzzzz") → aparece "No se encontraron resultados"
7. ESC → panel se cierra, focus vuelve al botón "Buscador"
8. Click en "×" → idem

- [ ] **Step 5.3: Verificar loader**

Abrir DevTools → Network → Throttling → Slow 3G.
Escribir cualquier texto → verificar que aparece "Buscando…" antes de los resultados.
Restaurar Throttling → No throttling.

- [ ] **Step 5.4: Verificar mobile (< 768px)**

Con DevTools en modo responsive (375px):
- Grid de resultados cae a 1 columna
- Padding del panel es 16px (no 40px)
- El buscador es usable en touch

- [ ] **Step 5.5: Actualizar MEMORY.md y commit final**

Añadir entrada en `MEMORY.md`:

```markdown
### 2026-06-02 — Buscador del header completado

- Endpoint `inc/udp-search.php`: acción `udp_search`, 7 WP_Query (pages/facultades/carreras/centros/noticias/eventos/calendario), max 10 por sección.
- JS `search.js`: debounce 400ms desde 1er carácter, AbortController, render DOM.
- SCSS `_search.scss`: top-bar transforma a beige al escribir, panel fixed bajo header.
- Cards: beige `#f8f7f4`, Arizona Flare 18px, flecha `→` abajo.
- Facultades: `post_parent` de la página con path `facultades` (resiliente al ID).
- Nonce: `starter_bs5_nonce` (mismo que el resto del tema).
```

```bash
git add MEMORY.md
git commit -m "docs(memory): buscador header completado"
```

---

## Notas de implementación

- **Nonce**: `starter_bs5_nonce` — confirmado en `functions.php:73`
- **Facultades**: se resuelve con `get_page_by_path('facultades')` con fallback a ID 14
- **AbortController + ajax.js**: el `signal` se pasa como tercer argumento a `ajax()` y se distribuye al `fetch` via spread `...options`; si se aborta, `ajax()` captura el error y devuelve `null`
- **`post_parent__not_in`**: soportado en WP_Query desde WordPress 4.x — filtra solo hijos directos de la página Facultades
- **`posts_per_page = -1` nunca**: siempre `UDP_SEARCH_MAX_PER_SECTION` (10) + `no_found_rows: true` para evitar `SQL_CALC_FOUND_ROWS`
