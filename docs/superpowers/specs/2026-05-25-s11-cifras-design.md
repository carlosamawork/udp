# Spec: S11 Cifras — Refactor layout y estilos

**Fecha:** 2026-05-25
**Figma:** https://www.figma.com/design/4QlgGMlzNR9Ye344bAFuye/...?node-id=3706-20153
**Estado:** Aprobado

---

## Contexto

La sección S11 Cifras ya tiene estructura ACF funcional (`flexible_content` con layouts `numero` y `testimonio`). Los datos se renderizan, pero el layout y los estilos no coinciden con el diseño Figma. Hay que refactorizar sin tocar el ACF.

---

## Diseño aprobado

### Arquitectura de layout

Carrusel horizontal Swiper freeMode, idéntico al patrón de S10 (Innovación):

```
<section.udp-home-cifras>
  <div.container.udp-home-cifras__wrap>   ← overflow: hidden
    <h2.udp-home__titulo>
    <div.js-cifras-swiper.swiper.udp-home-cifras__swiper>
      <div.swiper-wrapper>
        [slides: cards mezcladas en orden ACF]
      </div>
    </div>
  </div>
</section>
```

Todos los items del `flexible_content` se renderizan en orden (sin separación por tipo). Cada item es un `swiper-slide`.

---

### Card tipo `numero`

Layout flex-row horizontal: número grande a la izquierda, columna de texto a la derecha.

```
<div.udp-home-cifras__card.udp-home-cifras__card--numero>
  <span.udp-home-cifras__numero>  ← cifra_numero
  <div.udp-home-cifras__info>
    <p.udp-home-cifras__titulo-numero>  ← cifra_titulo
    <p.udp-home-cifras__subtitulo-numero>  ← cifra_subtitulo (si existe)
```

Estilos clave:
- Card: `background: #4539f2; border-radius: 20px; height: 280px; padding: 60px 30px; overflow: hidden; display: flex; align-items: center; gap: 30px`
- Número: `font-size: 10rem; font-weight: 700; line-height: 1; white-space: nowrap; flex-shrink: 0`
- Columna info: `width: 199px; flex-shrink: 0; display: flex; flex-direction: column; gap: 14px`
- Título: `font-size: 1.125rem; font-weight: 600; line-height: 22px`
- Subtítulo: `font-size: 0.875rem; font-weight: 400; line-height: 20px`

---

### Card tipo `testimonio`

Layout flex-col: icono comillas → texto cita → fila autor.

```
<div.udp-home-cifras__card.udp-home-cifras__card--cita>
  <div.udp-home-cifras__quote-icon>  ← SVG inline comillas
  <div.udp-home-cifras__cita-body>
    <div.udp-home-cifras__cita-texto>  ← cifra_cita (wysiwyg via wp_kses_post)
    <div.udp-home-cifras__autor>
      <img.udp-home-cifras__autor-img>  ← 40×40 circular
      <div.udp-home-cifras__autor-info>
        <p.udp-home-cifras__autor-nombre>
        <p.udp-home-cifras__autor-desc>
```

Estilos clave:
- Card: `background: #4539f2; border-radius: 20px; height: 280px; padding: 30px; overflow: hidden; display: flex; flex-direction: column; gap: 24px; width: 631px`
- Icono: `width: 32px; flex-shrink: 0`
- Cita body: `display: flex; flex-direction: column; gap: 24px; flex: 1; min-height: 0`
- Texto cita: `font-size: 1.125rem; line-height: 22px; font-weight: 400; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical`
- Autor row: `display: flex; align-items: center; gap: 12px`
- Avatar: `width: 40px; height: 40px; border-radius: 50%; object-fit: cover; flex-shrink: 0`
- Nombre: `font-size: 0.875rem; font-weight: 500; line-height: 20px`
- Descripción: `font-size: 0.75rem; line-height: 16px; color: rgba(255,255,255,0.7)`

---

### Sección

- `background-color: $dark-1`
- `padding-block: $space-5xl`
- Mobile (`<md`): `padding-block: $space-3xl`, número `font-size: 5rem`, cita `width: min(631px, calc(100vw - 60px))`

---

### Swiper JS (`home-cifras.js`)

Mismo patrón que `home-innovacion.js`:
- `freeMode: true`
- `grabCursor: true`
- `slidesPerView: 'auto'`
- `spaceBetween: 30`
- Selector: `.js-cifras-swiper`

---

## Archivos a modificar

| Archivo | Tipo de cambio |
|---|---|
| `template-parts/home/section-cifras.php` | Reescribir — Swiper, cards separadas por tipo |
| `src/scss/templates/_home.scss` | Reemplazar bloque `.udp-home-cifras` |
| `src/js/modules/home-cifras.js` | Crear nuevo |
| `src/js/main.js` | Registrar nuevo módulo |

---

## Restricciones

- No tocar el ACF (campos y layouts permanecen igual).
- Mantener BEM estricto y reglas SCSS multi-línea del proyecto.
- El SVG de comillas va inline en PHP (no asset externo).
