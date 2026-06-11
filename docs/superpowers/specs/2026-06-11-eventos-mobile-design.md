# Eventos archive Mobile + Barra de acción global — diseño

**Fecha:** 2026-06-11
**Figma:** Eventos grid `4041-43414`, Eventos list `4041-43845`, barra `4054-51922`, flechas slider `4041-42206`.
**Rama:** `feature/mobile` (engloba todo el mobile: single ya hecho + esto).

## Contexto

El archive de Eventos (`templates/page-eventos.php`) ya es responsive (grid→1col, list→1col, título 40px md, toggle md). La maqueta mobile difiere en: (1) una **barra de acción flotante** idéntica a la del single — es un patrón **global**; (2) detalles pixel-perfect de header y cards.

Decisiones (brainstorming): **globalizar la barra ahora** + **pixel-perfect en este pase**.

---

## Parte A — Barra de acción mobile GLOBAL

La barra (Compartir + Menú + volver-arriba) aparece en single, eventos y, por diseño, en todas las páginas mobile. Hoy existe como componente específico del single (`post-mobile-bar.php` + estilos `.udp-single-post__mobile-*` + funciones en `single-post-mobile.js`), ya **refinada al Figma `4054-51922`** (barra `#1c1c1c`, borde-top blanco, círculos borde `#454545`, Menú = círculo hamburguesa 50px + label "Menú").

**Objetivo:** extraerla a un componente global reutilizable y montarla una sola vez en el footer.

### Archivos
- **Nuevo** `template-parts/global/mobile-action-bar.php` — markup genérico. Comparte la **página actual**: calcula `$url = get_permalink()` (o `home_url(add_query_arg([], $wp->request))` como fallback para no-singulares) y `$title = wp_get_document_title()`. Estructura idéntica a la del single pero con clase base genérica **`udp-mobile-bar`** (`__share`, `__menu`, `__menu-icon`, `__menu-label`, `__top`, `__sheet`, `__sheet-action`).
- **Nuevo** `src/scss/layouts/_mobile-action-bar.scss` — estilos portados de `.udp-single-post__mobile-*` renombrados a `.udp-mobile-bar*`. Importar en `main.scss`.
- **Nuevo** `src/js/modules/mobile-action-bar.js` — `initMobileActionBar()` con las funciones share (navigator.share + fallback popover), menú (dispara `data-udp-megamenu-toggle`) y volver-arriba (respeta `prefers-reduced-motion`). Portadas de `single-post-mobile.js`. Wire en `main.js`.
- **Modificado** `footer.php` — incluir `get_template_part('template-parts/global/mobile-action-bar')` (al final, antes de `wp_footer`). Render en todas las páginas; visible solo en `<md` por CSS.

### Retirada de la barra específica del single (reconciliación)
- `single-post.php`: quitar el `get_template_part('template-parts/single/post-mobile-bar', ...)`.
- Borrar `template-parts/single/post-mobile-bar.php`.
- `_noticias-single.scss`: borrar el bloque `.udp-single-post__mobile-bar / __share / __top / __menu / __sheet` (lo cubre el global).
- `single-post-mobile.js`: quitar `initShare`/`initMenu`/`initTop` y sus llamadas; **conservar `initRelatedCarousel`** (sigue siendo del single). Renombrar opcional `initSinglePostMobile` → mantiene solo el carrusel.
- Verificar que el single sigue mostrando la barra (ahora vía el footer global) y el carrusel de relacionados.

### Datos del share global
- `navigator.share({ title, url })` con la URL/título de la página actual; fallback popover con copiar/FB/X/WhatsApp/LinkedIn (mismos `href` que hoy).

### Estilo (ya definido, se conserva)
- Barra fija abajo, `#1c1c1c`, `border-top: 1px solid #fff`, `min-height: 80px`, `padding: 15px`.
- Círculos share/top 48px, `border: 1px solid #454545` (`$gray-high`), bg `#1c1c1c`, icono blanco; flotan 16px sobre el borde superior.
- Menú: círculo hamburguesa 50px (`border #454545`) + label "Menú" (Work Sans 500 16px) al lado.
- Solo visible en `<md`.

---

## Parte B — Eventos archive mobile (pixel-perfect)

Breakpoint `media-down(md)` (≤767px). Desktop intacto.

### Tokens Figma
- Título "Eventos": **H1 Mobile** = Arizona Flare 500, **48px** / lh 48 (hoy 40px md → subir a 48).
- Eyebrow: **Label/Sm** = Necto Mono 12px, ls 0.05em, uppercase.
- Fecha/meta: **Body Sm** = Work Sans 14px / lh 20.
- Padding lateral mobile ~24px (Figma 25; alinear con el valor de spacing más cercano).

### Header
- Título 48px en `<md`.
- **Toggle grid/list inline a la derecha del título** (hoy `position:static` lo baja). Mantener los 2 círculos 40px a la derecha del título en la misma fila.
- Breadcrumb "↩ Inicio" (ya existe).

### Card grid (mobile) — `_card-evento.scss` bloque `&--grid` `media-down(md)`
Hoy: imagen 16/9 arriba + body + CTA estático. Maqueta:
- Imagen arriba full-width, ratio ~**343:250** (≈11:8).
- Contenido debajo (padding ~24px): **título arriba** (Work Sans 500 ~18px, hasta 4 líneas) → **eyebrow** (12px mono) → **fecha/hora** (14px) → **lugar** (12px), y **CTA circular 48px absoluto abajo-derecha** (mantener absoluto, no estático).

### Card list (mobile) — `_card-evento.scss` bloque `&--list` `media-down(md)`
Hoy: 1 col, título 16px. Maqueta:
- 1 col apilada: **eyebrow** (Necto Mono 12px ls 0.05em uppercase) → **título** (Work Sans 500 ~**20px**, lh ~1.2; hoy 16px) → **fecha** (Work Sans 14px).
- Separador `1px rgba(white,.15)` entre filas; gap vertical ~20px.

### Archivos Parte B
- `src/scss/templates/_eventos-archive.scss` — título 48px + toggle inline-right en `<md`; padding lateral.
- `src/scss/blocks/_card-evento.scss` — ajustes `media-down(md)` de grid y list.

---

## Fuera de alcance
- Otras páginas mobile (la barra global aparecerá en ellas, pero su pixel-perfect propio es aparte).
- Desktop de eventos (sin cambios).

## Verificación
- `npm run build` + `php -l`. Cache WPFC sigue desactivado (dev).
- @393px: barra global visible en single y eventos (y demás páginas); título Eventos 48px; toggle a la derecha del título; grid card imagen-arriba + CTA abajo-derecha; list card eyebrow/título/fecha.
- Single sigue OK: barra ahora del footer global, carrusel relacionados intacto, sin doble barra.
- ≥992px: eventos y single sin regresiones.
