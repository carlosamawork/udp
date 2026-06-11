# Spec: Mobile Header UDP

**Fecha:** 2026-06-11  
**Figma ref:** `3890:36740` (bottom nav bar)  
**Breakpoint de entrada:** `< md` (768px)

---

## Resumen

En mobile el header se simplifica a logo + lupa. El trigger del mega-menú se mueve a una barra fija en la parte inferior de la pantalla. El mega-menú en sí se abordará en una fase posterior.

---

## Archivos

### Nuevos
| Archivo | Responsabilidad |
|---|---|
| `template-parts/header/mobile-nav.php` | Barra fixed bottom con trigger del menú |

### Modificados
| Archivo | Cambio |
|---|---|
| `src/scss/layouts/_header.scss` | Grid mobile 50px/1fr/50px, ocultar `__menu` en `< md` |
| `src/scss/layouts/_header.scss` | Nuevo bloque `.udp-mobile-nav` (fixed bottom) |
| `footer.php` | `get_template_part('template-parts/header/mobile-nav')` antes de `wp_footer()` |
| `src/js/modules/mega-menu.js` | `qs` → `qsa` con loop para múltiples triggers |

---

## Sección 1: Top bar mobile (`< md`)

### Grid
En `< md`, `__inner` cambia:
```scss
@include media-down(md) {
  grid-template-columns: 50px 1fr 50px;
  padding-inline: $space-sm;
}
```
La columna izquierda pasa de 150px a 50px — espaciador visual que equilibra la lupa (50px) de la derecha. El logo sigue centrado con `justify-self: center`.

### Ocultar trigger desktop
```scss
@include media-down(md) {
  .udp-top-bar__menu {
    display: none;
  }
}
```
El botón sigue en el DOM (accesibilidad, no hay cambio de markup).

---

## Sección 2: Bottom nav bar

### Markup — `template-parts/header/mobile-nav.php`
```php
<nav class="udp-mobile-nav" aria-label="Menú principal">
  <button
    type="button"
    class="udp-mobile-nav__trigger"
    data-udp-megamenu-toggle
    aria-expanded="false"
    aria-controls="udp-megamenu-panel"
  >
    <span class="udp-mobile-nav__circle" aria-hidden="true">
      <!-- mismo SVG hamburger 26×26 del top-bar -->
    </span>
    <span class="udp-mobile-nav__label">Menú</span>
  </button>
</nav>
```

### SCSS — `.udp-mobile-nav`
```scss
.udp-mobile-nav {
  display: none;         // oculto en desktop

  @include media-down(md) {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 100;        // mismo nivel que el header fixed
    background: $dark-1;
    border-top: 1px solid $white;
    padding: 15px 40px;
  }
}

.udp-mobile-nav__trigger {
  display: inline-flex;
  align-items: center;
  gap: $space-2xs;
  background: transparent;
  border: none;
  color: $white;
  cursor: pointer;
  padding: 0;
}

.udp-mobile-nav__circle {
  width: 50px;
  height: 50px;
  border-radius: 9999px;
  border: 1px solid $gray-high;  // #454545
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.udp-mobile-nav__label {
  font-family: $font-family-body;  // Work Sans
  font-weight: 500;
  font-size: 16px;
  line-height: 24px;
}
```

### Body padding en mobile
Para que el contenido no quede tapado por la barra (~80px):
```scss
@include media-down(md) {
  body {
    padding-bottom: 80px;
  }
}
```

### Inclusión en `footer.php`
```php
<?php get_template_part( 'template-parts/header/mobile-nav' ); ?>
<?php wp_footer(); ?>
```

---

## Sección 3: Fix JS — múltiples triggers

### Problema
`mega-menu.js` usa `qs('[data-udp-megamenu-toggle]')` (querySelector singular). Con dos triggers en el DOM solo escucha el primero (el del top-bar, que está oculto en mobile).

### Fix
Reemplazar las dos referencias a `qs('[data-udp-megamenu-toggle]')` por `qsa(...)` con un loop:

```js
// Antes (líneas 20 y 113):
const toggle = qs( '[data-udp-megamenu-toggle]' );
toggle.addEventListener( 'click', openMenu );

// Después:
const toggles = qsa( '[data-udp-megamenu-toggle]' );
toggles.forEach( t => t.addEventListener( 'click', openMenu ) );
```

Al cerrar el menú, el focus se devuelve al trigger activo (el que lo abrió), no a un trigger fijo. Requiere guardar `let lastToggle = null` y asignarlo en el listener de apertura.

---

## Notas de implementación

- `z-index: 100` en la barra inferior es el mismo que `__inner` del header — en mobile nunca se superponen (el header está arriba fijo, la barra abajo fija).
- El mega-menú en sí (`_mega-menu.scss`) no se toca en esta fase — se adapta en la siguiente.
- El atributo `aria-expanded` en ambos triggers debe mantenerse sincronizado. El JS lo actualiza en todos los triggers con el loop.
- El `data-udp-megamenu-toggle` del top-bar queda en el DOM en mobile (oculto via CSS) para no romper el JS en desktop.
