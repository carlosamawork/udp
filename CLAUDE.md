# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## MEMORY.md — Bitácora de sesión

### Archivo MEMORY.md
- Si no existe un archivo `MEMORY.md` en la raíz del proyecto, **créalo antes de cualquier otra acción**.
- Este archivo es la bitácora de trabajo del agente en este proyecto. No es documentación del código — es un registro de decisiones, cambios y estado de las sesiones.

### Al iniciar sesión
Lee `MEMORY.md` y carga el contexto de la última sesión: qué se estaba haciendo, qué quedó pendiente y cualquier advertencia relevante.

### Durante la sesión
Cada vez que completes una acción significativa (nuevo archivo, cambio de arquitectura, instalación de dependencia, decisión de diseño, bug resuelto), añade una entrada al final de `MEMORY.md` con el formato:

```
### [FECHA] — [TÍTULO BREVE]
- Qué se hizo
- Por qué se tomó esa decisión
- Qué queda pendiente (si aplica)
```

### Al cerrar sesión (o al final de una respuesta larga)
Añade un bloque de cierre en `MEMORY.md`:

```
### [FECHA] — Cierre de sesión
- Resumen de lo trabajado
- Estado actual del proyecto
- Próximos pasos sugeridos
```

### Reglas del archivo
- Formato Markdown, entradas en orden cronológico (más reciente al final).
- No borres entradas anteriores — son historial.
- No repitas información que ya está en el código; anota el *porqué*, no el *qué*.

## Reglas de trabajo

### Antes de cualquier implementación
1. **Busca en internet primero** — ante cualquier integración con librería externa, paquete npm, API, plugin de WordPress o patrón que no sea código propio del proyecto, usa WebSearch/WebFetch para verificar la documentación oficial y ejemplos actuales antes de escribir código.
2. **No implementes nada externo sin certeza** — si tras la búsqueda existe alguna duda razonable sobre compatibilidad, versión, sintaxis o comportamiento, detente y comunícalo al usuario antes de proceder. No asumas, no adivines.
3. **Verifica versiones** — comprueba que la solución encontrada es compatible con las versiones exactas en uso: Vite 6, Bootstrap 5, ACF Pro, WordPress (última estable), Node LTS.
4. **Prefiere fuentes oficiales** — documentación oficial > artículos técnicos reconocidos > Stack Overflow. Descarta resultados desactualizados (>2 años salvo que sean los únicos disponibles).

## Overview

WordPress starter theme built with Bootstrap 5, ACF Pro, Vite, and SCSS. Uses a modular JavaScript architecture and a Vite-based build system with manifest-driven asset loading.

## Commands

```bash
npm run dev      # Start Vite dev server with HMR (localhost:5173)
npm run build    # Compile SCSS + JS for production to /dist
npm run watch    # Watch mode with auto-recompilation
npm run preview  # Preview production build locally
```

To enable dev mode in WordPress, add to `wp-config.php`:
```php
define('VITE_DEV_SERVER', true);
```

## Architecture

### Build System
- **Vite 6** handles all JS and SCSS compilation
- Entry points: all `src/js/*.js` files (auto-detected via glob)
- Output goes to `/dist` (gitignored — regenerate with `npm run build`)
- SCSS utilities (`_variables.scss`, `_mixins.scss`) are auto-injected into every SCSS file via `vite.config.js`
- Production builds generate a `dist/.vite/manifest.json` that WordPress reads to enqueue hashed assets
- Path aliases: `@js`, `@scss`, `@modules`, `@utils`

### Asset Loading (inc/class-vite.php)
- `Starter_BS5_Vite::enqueue($entry)` — loads JS + linked CSS from manifest
- `Starter_BS5_Vite::enqueueStyle($entry)` — CSS-only loading
- Dev mode serves from `localhost:5173` with HMR; production reads from manifest

### JavaScript
- `src/js/main.js` — single entry point; imports Bootstrap, SCSS, and all theme modules
- `src/js/modules/` — one file per feature (navbar, smooth-scroll, scroll-animations, mobile-menu)
- `src/js/utils/dom.js` — `qs()`, `qsa()`, `createElement()`, `domReady()`
- `src/js/utils/ajax.js` — `ajax(action, data)` for WP AJAX and `restGet(endpoint)` for REST API
- Globals exposed to `window`: `bootstrap`, `starterAjax`, `starterBS5` (ajaxUrl, nonce, themeUrl, homeUrl)

### SCSS
- `src/scss/main.scss` — entry point
- `src/scss/editor.scss` — Gutenberg editor styles (separate entry)
- Structure: `utilities/` → `layouts/` → `components/` → `blocks/`
- Bootstrap variables must be overridden in `_variables.scss` **before** Bootstrap imports

### ACF Pro Integration
- Field group JSON is stored in `/acf-json/` for version control (auto-save/load configured)
- Options pages: Opciones del Sitio (parent) → General, Header & Mega-menú, Footer, Redes Sociales (4 sub-pages temáticas con slugs udp-options-*).
- Helper functions in `inc/helpers.php`:
  - `starter_get_field($field, $post_id, $default)` — field with fallback
  - `starter_get_option($field, $default)` — options page field
  - `starter_acf_image($image, $size, $class)` — renders image with lazy loading

### Template System
- `templates/page-hero.php` — page template with hero section (ACF fields: title, subtitle, bg image, CTA)
- `templates/page-flexible.php` — flexible content builder via ACF
- `template-parts/blocks/` — block templates for flexible content: `block-text_image.php`, `block-cta_banner.php`, `block-cards_grid.php`

### WordPress Setup
- Theme constants: `STARTER_BS5_VERSION`, `STARTER_BS5_DIR`, `STARTER_BS5_URI`
- Custom image sizes: `card-thumbnail` (400×300), `hero-banner` (1920×800)
- Registered menus: `primary`, `footer`
- Bootstrap 5 nav walker in `inc/class-bootstrap-navwalker.php` — handles dropdowns natively
- WordPress meta tag cleanup (generator, shortlinks, emoji) done in `functions.php`
