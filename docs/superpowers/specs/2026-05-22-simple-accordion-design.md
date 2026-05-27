# Simple Accordion — Design Spec

**Fecha**: 2026-05-22  
**Feature**: Template de página con contenido editorial + acordeón + relacionados  
**Scope**: Template reutilizable para páginas de contenido dentro de "Conoce UDP" (Historia, Gobernanza, Forma de Gobierno, etc.) y cualquier otra página que siga el mismo patrón.

---

## Objetivo

Crear un page template de WordPress que muestre:
1. Cabecera de página (breadcrumb + título)
2. Layout de 3 columnas con columnas laterales vacías (placeholder para fase posterior) y columna central con texto editorial + acordeón
3. Sección "También te puede interesar" con links manuales en Swiper horizontal
4. Share button flotante

---

## Layout

```
┌─────────────────────────────────────────────────────┐
│ Header (global)                                     │
├─────────────────────────────────────────────────────┤
│ Breadcrumb + Título de página                       │
├──────────┬──────────────────────────┬───────────────┤
│          │  the_content()           │               │
│  [col    │  ─────────────────────  │  [col         │
│   izq.   │  Acordeón ACF repeater  │   dcha.       │
│   vacía] │  ─────────────────────  │   vacía]      │
│          │  item 1, item 2, ...    │               │
├──────────┴──────────────────────────┴───────────────┤
│ También te puede interesar (Swiper de link cards)   │
├─────────────────────────────────────────────────────┤
│ Footer (global)                                     │
└─────────────────────────────────────────────────────┘
Share button flotante (derecha, mismo que F4b)
```

**Columnas**: izquierda y derecha son placeholders vacíos. La estructura CSS (grid 3-col) existe desde ahora para que la fase posterior inserte los repeaters de tarjetas sin tocar el layout.

---

## Archivos

### Nuevos
- `templates/page-simple-accordion.php` — template principal (orquestador)
- `template-parts/simple-accordion/page-header.php` — breadcrumb + título
- `template-parts/simple-accordion/main-content.php` — the_content() + acordeón
- `template-parts/simple-accordion/related.php` — sección "También te puede interesar"
- `src/scss/templates/_simple-accordion.scss`
- `acf-json/group_template_simple_accordion.json`

### Reutilizados
- `template-parts/sections/breadcrumb.php` (F3)
- `template-parts/single/post-share.php` (F4b)
- `src/js/modules/block-accordion.js` (F7b) — mismo selector `[data-udp-accordion]`
- `src/js/modules/section-landing-swiper.js` (F3) — Swiper lazy para relacionados

---

## ACF — `group_template_simple_accordion`

**Location**: `page_template == templates/page-simple-accordion.php`

| Campo | Tipo | Notas |
|---|---|---|
| `acordeon` | repeater | Items del acordeón |
| `acordeon[].titulo` | text | Título del item (required) |
| `acordeon[].contenido` | wysiwyg | Cuerpo rico del item |
| `relacionados` | repeater | "También te puede interesar" |
| `relacionados[].titulo` | text | Nombre de la página/sección |
| `relacionados[].link` | link | URL interna o externa (ACF Link field) |

---

## Componentes

### Cabecera de página
- Breadcrumb: reutiliza `template-parts/sections/breadcrumb.php` con `home_label = 'Inicio'`
- Título: `get_the_title()`, tipografía Arizona Flare (misma clase que otras páginas)
- Fondo claro (`$white`), padding vertical consistente con el resto del tema

### Layout 3 columnas
- Grid: `318px | 1fr | 318px` (anchos del Figma)
- Breakpoints:
  - `< lg`: columnas laterales desaparecen (`display: none`), columna central a ancho completo
  - `< md`: sin cambios adicionales (ya es 1 col)
- Las columnas laterales son `<aside>` vacíos con clase BEM `udp-simple-accordion__col-left` / `__col-right` — no tienen contenido ahora, la clase es el punto de extensión para la fase posterior

### Texto principal
- `the_content()` envuelto en clase `udp-simple-accordion__body`
- Estilos de prosa: font-size 16px, line-height 1.7, max-width sin restricción adicional (la columna central ya lo limita)

### Acordeón
- Markup idéntico al de F7b (`<details><summary>`) con atributo `data-udp-accordion`
- El JS de F7b (`block-accordion.js`) ya inicializa con `document.querySelectorAll('[data-udp-accordion]')` — no requiere cambios en el módulo
- Si no hay items en el repeater ACF: el acordeón no se renderiza (early return)

### Sección "También te puede interesar"
- Título fijo: "También te puede interesar" (no editable, hardcoded en el template)
- Cards: link cards sin imagen — título + flecha
- Layout: Swiper con `slidesPerView: 'auto'`, `freeMode`, `grabCursor` (mismo config que section-landing-swiper)
- Si el repeater está vacío: la sección no se renderiza
- El container del Swiper lleva la clase `udp-section-cards--swiper` para reutilizar el módulo JS existente de F3 sin crear uno nuevo

### Share button
- Reutiliza `template-parts/single/post-share.php` sin cambios
- Posición: flotante derecha (el partial ya gestiona su posicionamiento)

---

## SCSS — `_simple-accordion.scss`

Clases BEM nuevas:

```
.udp-simple-accordion          — wrapper principal
  __header                     — zona breadcrumb + título
  __title                      — h1 de la página
  __layout                     — grid 3-col
    __col-left                 — aside izquierdo (vacío)
    __col-center               — columna principal
    __col-right                — aside derecho (vacío)
  __body                       — the_content() wrapper
  __accordion                  — wrapper del acordeón ACF
  __related                    — sección "También te puede interesar"
    __related-title            — título de la sección
    __related-swiper           — container del Swiper
    __related-card             — card individual (link)
    __related-card-title       — título del link
```

---

## Fase posterior (fuera de scope)

- ACF repeater `tarjetas_izquierda` + `tarjetas_derecha` con los 3 tipos de tarjeta
- Renderizado de tarjetas en `__col-left` y `__col-right`
- Desarrollado por compañero de Elsa

---

## Verificación E2E

1. Asignar template a la página "Historia" desde el admin
2. `curl http://localhost:8888/udp/?p={id_historia} | grep udp-simple-accordion` → clases presentes
3. Breadcrumb renderiza correctamente según jerarquía WP
4. `the_content()` muestra el contenido del editor
5. Acordeón funciona: click en ítem → expande con animación (JS de F7b)
6. "También te puede interesar": Swiper con los links configurados
7. Share button: botones de compartir visibles y funcionales
8. Mobile (`< lg`): columnas laterales ocultas, columna central a 100%
