# Spec: Animación de expansión — Portada Home (S1)

**Fecha:** 2026-05-29  
**Feature:** Animación scroll-driven de la imagen en la sección portada del home  
**Estado:** Aprobado por usuario

---

## Comportamiento esperado

1. **Estado inicial** — la sección ocupa `100vh`. El bloque de texto está arriba; la imagen (40vw × 23vw) aparece debajo del texto con 41px de separación, alineada a la izquierda del container. La imagen es visible en su totalidad (sin crop).

2. **Al hacer scroll** — la sección queda fija (`pin: true`) mientras el usuario scrollea 100vh adicionales. Durante ese recorrido, la imagen se expande progresivamente (ligada al scroll, `scrub: 1`) hasta cubrir toda la sección (100% × 100%). El texto permanece visible encima en todo momento.

3. **Tras la animación** — la sección se libera sola y la página scrollea normalmente hacia abajo.

---

## Cambios en CSS (`_home.scss`)

### `.udp-home-portada`
- `min-height: 80vh` → `height: 100vh`
- Mantiene `position: relative; overflow: hidden`
- Eliminar el bloque `@supports (animation-timeline: scroll())` y el `@keyframes portada-reveal` — reemplazados por GSAP

### `.udp-home-portada__inner`
- Sin cambios estructurales. La imagen ya no estará en el flujo del grid (se extrae con JS al init), así que el grid solo renderiza el texto.

### `.udp-home-portada__content`
- Añadir `position: relative; z-index: 2` para quedar encima de la imagen durante la expansión.

### `.udp-home-portada__media`
- Estado inicial (CSS): tamaño y posición los gestiona JS en el init. El CSS solo necesita:
  ```scss
  position: absolute;
  z-index: 0;
  ```
- Eliminar `clip-path`, `animation`, `animation-play-state`, `animation-timeline`, `animation-range`.

### `.udp-home-portada__imagen`
- `width: 100%; height: 100%; object-fit: cover; display: block`
- Nota: proporción 40vw × 23vw (≈ 1.74:1) es casi idéntica a 16:9 (1.78:1). Con `object-fit: cover` el recorte a ese tamaño es mínimo para imágenes horizontales. Si tras probar con el contenido real el crop es perceptible, se aplica `object-fit: contain` solo en el estado pequeño con una transición en el 50% del progreso (vía `onUpdate`).

---

## Cambios en JS (`home-portada.js`)

Reescritura completa del módulo. El archivo actual tiene ~23 líneas (IntersectionObserver fallback + CSS scroll-driven); se reemplaza por la lógica GSAP.

### Dependencia nueva
```bash
npm install gsap
```
Import dinámico (lazy), mismo patrón que `section-landing-swiper.js`:
```js
const { gsap } = await import('gsap');
const { ScrollTrigger } = await import('gsap/ScrollTrigger');
gsap.registerPlugin(ScrollTrigger);
```

### Lógica de init

```js
export async function initHomePortada() {
  const section = qs('.js-home-portada');
  const media   = qs('.js-portada-media', section);
  if (!section || !media) return;

  // Reducción de movimiento — mostrar estado final sin animación
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    media.style.cssText = 'position:absolute;inset:0;width:100%;height:100%';
    return;
  }

  const { gsap } = await import('gsap');
  const { ScrollTrigger } = await import('gsap/ScrollTrigger');
  gsap.registerPlugin(ScrollTrigger);

  // Esperar fuentes antes de medir (Arizona Flare afecta la altura del texto)
  await document.fonts.ready;

  function setup() {
    // Matar instancias previas para evitar duplicados en resize
    ScrollTrigger.getAll().forEach(st => st.kill());

    const inner   = section.querySelector('.udp-home-portada__inner');
    const content = section.querySelector('.udp-home-portada__content');

    // Calcular posición inicial de la imagen
    const sectionRect  = section.getBoundingClientRect();
    const innerLeft    = inner.getBoundingClientRect().left;
    const contentBottom = content.getBoundingClientRect().bottom - sectionRect.top;
    const imgTop  = contentBottom + 41;  // gap 41px (equivale al gap del grid original)
    const imgLeft = innerLeft;           // alineada al container

    // Setear posición inicial
    gsap.set(media, {
      position: 'absolute',
      top:    imgTop,
      left:   imgLeft,
      width:  '40vw',
      height: '23vw',
    });

    // Animación ligada al scroll
    gsap.to(media, {
      top:    0,
      left:   0,
      width:  '100%',
      height: '100%',
      ease:   'none',
      scrollTrigger: {
        trigger: section,
        start:   'top top',
        end:     '+=100vh',
        pin:     true,
        scrub:   1,
      },
    });
  }

  setup();

  // Recalcular en resize (kill + recrear para evitar duplicados)
  ScrollTrigger.addEventListener('refreshInit', setup);
}
```

### Wire en `main.js`
La función ya está importada; no cambia el import. El comportamiento interno cambia completamente.

---

## Dependencias y build

- **`gsap`** se instala como `dependency` (runtime). Vite generará un chunk lazy separado al hacer el dynamic import.
- No añadir a `additionalData` de Vite ni a ningún entry point directo — debe ser lazy igual que Swiper.

---

## Accessibility

- `prefers-reduced-motion: reduce` → la sección NO se pinea y la imagen aparece directamente en estado full-bleed (`inset: 0`). Sin animación, sin ScrollTrigger.

---

## Scope fuera de spec

- Cambios en el markup de `section-portada.php`: ninguno.
- Cambios en campos ACF de la portada: ninguno.
- Animación del texto (título / CTA) durante la expansión: el texto permanece estático en su posición durante toda la animación. Ajustes futuros de opacidad o movimiento del texto quedan fuera de este spec.
- Header durante el pin: el comportamiento del mega-menú y el header no cambia.
