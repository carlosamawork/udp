# Single Noticia — Mobile (responsive)

**Fecha:** 2026-06-11
**Figma:** node `4041-42144` ("noticia interior", 393×4145) — file `4QlgGMlzNR9Ye344bAFuye`
**Alcance:** primer pase de la fase mobile. Solo el **contenido del single de noticias** (`single-post.php` y sus partials/SCSS). El header y el footer globales ya tienen su versión mobile y quedan **fuera de alcance** (se tocan solo si algo del single los rompe).

---

## Contexto

El single de noticias (`single-post.php`) ya existe (F4b) y funciona en desktop:

- `template-parts/single/post-hero.php` — back link + título + meta (Fecha + chip categoría) + imagen destacada.
- `template-parts/single/post-share.php` — barra vertical fija (sidebar derecha) con 5 acciones: copiar URL, Facebook, X, WhatsApp, LinkedIn. En `<lg` hoy cae a fila estática centrada.
- `template-parts/single/post-gallery.php` — galería Swiper con flechas prev/next (solo si el post tiene `galeria_de_imagenes`).
- `template-parts/single/post-related.php` — "Te podría interesar": 3 cards (`card-noticia`, `theme=light`) en grid 3-col; en `<lg` hoy cae a 1 columna.
- SCSS: `src/scss/templates/_noticias-single.scss`.

La maqueta mobile resuelve **tres** cosas distinto al desktop actual. El resto es afinado responsive.

**Breakpoints** (mixins existentes): `md` = `max-width: 767.98px` (teléfono), `lg` = `max-width: 991.98px`. Los patrones mobile nuevos se aplican en **`media-down(md)`**. Desktop (`≥992px`) **no se modifica**. Tablet (768–991) queda como capa intermedia razonable (ver cada sección).

---

## Decisiones tomadas (brainstorming)

1. **Alcance:** solo contenido del single. Header/footer globales fuera.
2. **Relacionados:** oscuro + carrusel **solo en mobile** (`<md`). Desktop mantiene el grid claro de 3 columnas tal cual.
3. **Share:** en mobile se reemplaza el sidebar vertical por una **barra inferior fija** con: botón Compartir, volver-arriba (↑) y pill **"Menú"**. El Compartir usa la API nativa con fallback a popover. El "Menú" reutiliza el mega-menú del header (no es un nav propio).

---

## Diseño por bloque

### 1. Hero / título / meta

**Markup:** sin cambios estructurales salvo el orden del meta (ver abajo).

- Back link "↩ Volver a Noticias" — ya existe.
- Título Arizona Flare, `32px` en `<md` — ya existe.
- **Meta (cambio):** la maqueta apila verticalmente:
  1. Label "Fecha" (Necto Mono, gris, uppercase pequeña).
  2. Fecha `26 / 01 / 2026`.
  3. Chip **amarillo** `INTERNACIONAL` (Necto Mono uppercase, fondo `$brand-yellow`) en su **propia línea** debajo.

  Hoy `__meta` es `display:flex` en fila con `flex-wrap`. En `<md` se pasa a `flex-direction: column; align-items: flex-start; gap`. El chip queda en su propia línea automáticamente. El borde inferior del meta se conserva.

- Imagen destacada (`__featured`) full-width con padding lateral `$space-sm` (ya está vía `__hero-inner`).

**Sin cambios de PHP** (el partial ya emite label + fecha + chip). Solo SCSS.

### 2. Cuerpo (`post_content`)

- Una columna, padding lateral `$space-sm` (ya está).
- Sin cambios estructurales. Afinado de tipografía/espaciados contra la maqueta se hace en el pase pixel-perfect posterior (no en este plan).

### 3. Galería (Swiper)

- Se mantiene el módulo y markup actuales.
- En `<md`: peek lateral (que ya da Swiper con `slidesPerView:auto`) y se sustituyen las flechas prev/next por **dots** de paginación (pagination `bullets`), coherente con la maqueta.
- Polish menor: solo aplica si el post tiene galería. No bloquea el resto.

### 4. Share → barra inferior fija (NUEVO, solo `<md`)

**Partial nuevo:** `template-parts/single/post-mobile-bar.php`. Recibe `['post_id' => int]`. Por ahora se incluye solo desde `single-post.php`; se diseña reutilizable para promoverlo a global más adelante (fuera de este alcance).

**Estructura visual (maqueta):**
- Contenedor `position: fixed; left:0; right:0; bottom:0; z-index`, oculto en `≥md` (`display:none`), visible en `<md`.
- Barra inferior oscura (`$dark-1`) con **línea superior** (border-top sutil) y el pill **"≡ Menú"** centrado (círculo hamburguesa + label "Menú").
- **Botón Compartir** (círculo ~48px) flotando en la esquina **inferior-izquierda**, apoyado sobre el borde superior de la barra.
- **Botón volver-arriba ↑** (círculo ~48px) flotando en la esquina **inferior-derecha**.

> Colores exactos (fondo de la barra, borde y relleno de los círculos) se verifican contra Figma en el pase pixel-perfect. Base: barra `$dark-1`, círculos con borde claro e icono claro.

**Comportamiento:**
- **Compartir:** un único botón. `navigator.share({ title, url })` si está disponible (`navigator.share` existe). Fallback: abre un popover/hoja con las 5 acciones actuales (copiar URL con clipboard + toast, Facebook, X, WhatsApp, LinkedIn) reutilizando los mismos `href`/lógica de `post-share.php`. El "copiar" mantiene el toast "Copiado".
- **Menú:** botón que dispara el mismo mega-menú del header. Reutiliza el atributo/handler existente `data-udp-megamenu-toggle` (mismo que `top-bar.php`), para no duplicar lógica de nav.
- **Volver-arriba:** `window.scrollTo({ top:0, behavior })`, con `behavior: 'auto'` si `prefers-reduced-motion`. Reusar el patrón de `_calendario-archive.scss __back-to-top` para estilo si encaja.

**Desktop:** el sidebar vertical fijo de `post-share.php` se mantiene intacto en `≥md`. En `<md` se oculta (`display:none`) y la barra inferior toma su lugar.

**Tablet (768–991):** el sidebar de desktop sigue vigente (no la barra inferior). La barra inferior es estrictamente `<md`.

### 5. "Te podría interesar" → oscuro + carrusel (solo `<md`)

**Desktop (`≥md`):** sin cambios. Grid claro de 3 columnas, cards `card-noticia theme=light`.

**Mobile (`<md`):**
- Sección con **fondo oscuro** (`$dark-1`, texto blanco) — solo vía media query, sin tocar el desktop.
- Las cards se vuelven **carrusel** Swiper: `slidesPerView: 'auto'`, una visible con **peek** lateral, `freeMode`/swipe táctil, **dots** de paginación abajo (coherente con la maqueta).
- Cada card usa `card-noticia` variant **`horizontal`** con **`theme=dark`** (imagen izquierda ~140px + título + fecha + "Leer más ↗").

**Markup (`post-related.php`):** se envuelve la `<ul>` en la estructura Swiper (`swiper` > `swiper-wrapper` > `swiper-slide`) con `data-*` para inicializar solo en mobile. Las cards y la query no cambian. El SCSS controla que en `≥md` el contenedor se comporte como grid (Swiper sin inicializar en desktop) y en `<md` como carrusel.

> Decisión de inicialización: el módulo JS inicializa el Swiper de relacionados **solo cuando** `window.matchMedia('(max-width: 767.98px)')` matchea (y lo destruye si se cruza a desktop), para que el desktop conserve el grid CSS nativo sin Swiper. Alternativa más simple si da problemas de resize: inicializar siempre y dejar que el CSS de `≥md` muestre grid (Swiper en modo "desactivado"). Se elige la primera; la segunda es fallback documentado.

---

## Archivos afectados

**PHP**
- `single-post.php` — incluir `post-mobile-bar` (después de `post-share`).
- `template-parts/single/post-mobile-bar.php` — **nuevo**. Barra inferior fija mobile (Compartir + Menú + volver-arriba) + popover fallback de compartir.
- `template-parts/single/post-related.php` — envolver lista en estructura Swiper con `data-*`; pasar `variant=horizontal` + `theme` según contexto (la card ya soporta ambos). El `theme=light` se mantiene; el oscuro mobile es por CSS.

**SCSS** (`src/scss/templates/_noticias-single.scss`)
- `__meta` → columna en `<md` (chip en su línea).
- Galería → dots en `<md` (si se aborda en este plan).
- Bloque nuevo barra inferior `.udp-single-post__mobile-bar` (+ botones + popover).
- `__share` (sidebar) → `display:none` en `<md`.
- `__related` → variante oscura + carrusel en `<md` (bloque dentro de la media query, sin tocar el desktop).

**JS**
- `src/js/modules/single-post-mobile.js` — **nuevo**. (a) Compartir nativo con fallback a popover; (b) volver-arriba; (c) init/destroy del Swiper de relacionados según `matchMedia(<md)`. Lazy import de Swiper (ya es dependencia del proyecto) solo si hay relacionados.
- `src/js/main.js` — wire del nuevo módulo.
- Galería: su módulo `single-post-gallery.js` ya existe; si se añaden dots, ajustar ahí (config pagination).

---

## Fuera de alcance (este plan)

- Header y footer mobile globales (ya existen; se ajustan aparte solo si se rompen).
- Promover la barra inferior "Menú" a todas las páginas mobile (site-wide). Se construye reutilizable pero solo se monta en el single por ahora.
- Pase pixel-perfect fino (tipografías/espaciados exactos px a px) — iteración posterior con el skill `pixel-perfect`.
- Otras páginas single/archive mobile (vendrán después en la misma fase).

---

## Verificación

- `npm run build` sin errores; `php -l` en PHP nuevos/modificados.
- En viewport 393px (DevTools): meta apilado con chip en su línea; barra inferior fija visible con Compartir/Menú/↑; "Te podría interesar" oscuro en carrusel con dots; galería con dots (si aplica).
- En `≥992px`: cero cambios visuales respecto a hoy (sidebar share fijo, related grid claro 3-col, sin barra inferior).
- Compartir: nativo donde exista `navigator.share`; popover con las 5 acciones donde no; "copiar" muestra toast.
- Menú de la barra abre el mismo mega-menú del header.
- `prefers-reduced-motion`: volver-arriba sin animación; carrusel sin transiciones bruscas.
