# Spec: Mega-menú mobile — navegación por niveles con slide horizontal

**Fecha:** 2026-06-11
**Figma refs:** `3928:56367` (L1), `3928:58351` (L2 — "Pregrado"), `3987:11363` (L3 — "Carreras")
**Breakpoint:** `< md` (768px)

---

## Resumen

En mobile el mega-menú desktop (3 columnas, fondo oscuro) se sustituye por un panel de fondo claro (`#f8f7f4`) con navegación por niveles tipo "drill-down". Los tres niveles (secciones → apartados → sub-items) se presentan como paneles que se deslizan horizontalmente. La top bar del panel cambia según el nivel activo.

---

## Estructura del panel en mobile

```
#udp-megamenu-panel (en mobile: fondo #f8f7f4, full-screen menos bottom bar)
│
├── .udp-megamenu__mtop  ←  top bar dinámica
│     - L1: solo logo UDP (negro, mismo que top-bar del sitio)
│     - L2+: [← undo] [Nombre sección/apartado] [× cerrar todo]
│
└── .udp-megamenu__mviewport  ←  overflow: hidden
      └── .udp-megamenu__mslider  ←  flex-row, width: 300%
            ├── .udp-megamenu__ml1  (33.3%) — lista de secciones
            ├── .udp-megamenu__ml2-wrap  (33.3%) — apartados de la sección activa
            └── .udp-megamenu__ml3-wrap  (33.3%) — sub-items del apartado activo
```

El slider usa `transform: translateX(0 | -33.33% | -66.66%)` + `transition: 280ms cubic-bezier(0.4,0,0.2,1)` para el slide.

---

## Nivel 1 — secciones

**Top bar:** logo UDP (negro, `#1c1c1c`), mismo markup que el logo del `__top` desktop pero a la izquierda sin el botón cerrar.

**Contenido:** lista de secciones del `menu_principal` ACF.

```
Pregrado            ›
Posgrado            ›
Admisión            ›
Investigación...    ›
Universidad         ›
Cultura             ›
Internacional       ›
```

- Font: serif (`$font-family-serif`), 25px, tracking `-0.5px`
- Flecha: `›` SVG chevron-right, `stroke: #888`
- Click: slide a L2, topbar pasa a modo navegación
- Sin quick links ni redes sociales (solo desktop)

---

## Nivel 2 — apartados

**Top bar:**
- Izquierda: botón `←` (undo-2 SVG, 40×40, border `1px solid #b0b0b0`, sin fondo)
- Centro: nombre de la sección activa, semibold 17px, `#1c1c1c`
- Derecha: botón `×` (x SVG, 40×40, mismo estilo que `←`) — cierra el menú completo

**Contenido:** apartados del `submenu` ACF de la sección activa.

Iconos según tipo:
- `has_sub = true` → flecha `→` (`stroke: #888`) — click hace slide a L3
- `tipo = externo` sin sub → flecha `↗` (`stroke: #888`)
- `tipo = interno/sin_link` sin sub → sin icono (link directo o span)

Font: Work Sans medium, 17px.

---

## Nivel 3 — sub-items

**Top bar:**
- Izquierda: `←` — vuelve a L2
- Centro: nombre del apartado activo, semibold 17px
- Derecha: `×` — cierra menú completo

**Contenido:** sub-items del `sub_items` ACF del apartado activo.

Font: Work Sans medium, 16px. Todos llevan icono `↗` si externos, sin icono si internos.

---

## Bottom bar (barra inferior fija)

La `udp-mobile-nav` ya existente permanece visible en todos los niveles. El botón hamburger/cerrar de esa barra sigue funcionando para abrir/cerrar el panel completo. No muestra breadcrumb ni nivel.

---

## Archivos afectados

| Archivo | Acción |
|---|---|
| `template-parts/header/mega-menu.php` | Añadir bloque `__mtop` + `__mviewport` con los 3 niveles pre-renderizados al final del panel (antes del `</div>` de cierre) |
| `src/scss/layouts/_mega-menu.scss` | En `< md`: ocultar `__top`, `__body`, `__footer` desktop; mostrar `__mtop` y `__mviewport`; estilos del slider y paneles mobile |
| `src/js/modules/mega-menu.js` | Añadir `initMegaMenuMobile()` — gestiona slide, topbar dinámica, back/close; guard `matchMedia` para no activar en desktop |

---

## PHP — estructura del bloque mobile

Se añade dentro de `#udp-megamenu-panel`, después del `__footer` desktop:

```php
<div class="udp-megamenu__mobile" aria-hidden="true">

  <!-- Top bar dinámica (JS la gestiona) -->
  <div class="udp-megamenu__mtop" id="udp-megamenu-mtop">
    <!-- L1: logo -->
    <div class="udp-megamenu__mtop-l1">
      <a href="<?= esc_url(home_url('/')) ?>" class="udp-megamenu__mlogo">
        <!-- img logo negro -->
      </a>
    </div>
    <!-- L2+: nav (oculto inicialmente) -->
    <div class="udp-megamenu__mtop-nav" hidden>
      <button class="udp-megamenu__mback" data-udp-mob-back aria-label="Volver"><!-- SVG undo --></button>
      <span class="udp-megamenu__mtitle" id="udp-megamenu-mtitle"></span>
      <button class="udp-megamenu__mclose-all" data-udp-megamenu-close aria-label="Cerrar menú"><!-- SVG × --></button>
    </div>
  </div>

  <!-- Viewport del slider -->
  <div class="udp-megamenu__mviewport">
    <div class="udp-megamenu__mslider" id="udp-megamenu-mslider">

      <!-- L1: lista de secciones -->
      <div class="udp-megamenu__ml1">
        <ul>
          <?php foreach ($menu_items as $idx => $item): ?>
            <li>
              <button data-udp-mob-section="<?= $idx ?>" data-udp-mob-title="<?= esc_attr($item['titulo_main_link']) ?>">
                <span><?= esc_html($item['titulo_main_link']) ?></span>
                <!-- SVG chevron-right -->
              </button>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <!-- L2: apartados (uno por sección, solo uno visible a la vez) -->
      <div class="udp-megamenu__ml2-wrap">
        <?php foreach ($menu_items as $idx => $item): ?>
          <div class="udp-megamenu__ml2" data-udp-mob-l2="<?= $idx ?>" hidden>
            <ul>
              <?php foreach ($item['submenu'] as $sub_idx => $sub): ?>
                <li>
                  <?php if ($has_sub): ?>
                    <button data-udp-mob-sub="<?= $sub_idx ?>" data-udp-mob-title="<?= esc_attr($sub['titulo']) ?>">
                      <span><?= esc_html($sub['titulo']) ?></span>
                      <!-- SVG → -->
                    </button>
                  <?php elseif ($sub_link): ?>
                    <a href="<?= esc_url($sub_link) ?>">
                      <span><?= esc_html($sub['titulo']) ?></span>
                      <!-- SVG ↗ si externo -->
                    </a>
                  <?php else: ?>
                    <span><?= esc_html($sub['titulo']) ?></span>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- L3: sub-items (uno por apartado, solo uno visible a la vez) -->
      <div class="udp-megamenu__ml3-wrap">
        <?php foreach ($menu_items as $idx => $item): ?>
          <?php foreach ($item['submenu'] as $sub_idx => $sub): ?>
            <?php if (!empty($sub['sub_items'])): ?>
              <div class="udp-megamenu__ml3" data-udp-mob-l3="<?= $idx ?>-<?= $sub_idx ?>" hidden>
                <ul>
                  <?php foreach ($sub['sub_items'] as $si): ?>
                    <li><a href="<?= esc_url($si_link) ?>">
                      <span><?= esc_html($si_titulo) ?></span>
                      <!-- SVG ↗ si externo -->
                    </a></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </div>

    </div><!-- /.mslider -->
  </div><!-- /.mviewport -->

</div><!-- /.udp-megamenu__mobile -->
```

---

## SCSS — resumen de reglas mobile

```scss
@include media-down(md) {
  // Ocultar desktop
  .udp-megamenu__top,
  .udp-megamenu__body,
  .udp-megamenu__footer { display: none; }

  // Mostrar mobile
  .udp-megamenu__mobile {
    display: flex;
    flex-direction: column;
    height: 100%;
  }

  // Top bar
  .udp-megamenu__mtop {
    height: $header-height;
    border-bottom: 1px solid $dark-1;
    padding: 0 $space-md;
    // L1: logo izquierda; L2: flex space-between
  }

  // Botones back/close
  .udp-megamenu__mback,
  .udp-megamenu__mclose-all {
    width: 40px; height: 40px;
    border-radius: 9999px;
    border: 1px solid $gray-high; // #b0b0b0
    background: transparent;
  }

  // Slider
  .udp-megamenu__mviewport { overflow: hidden; flex: 1; }
  .udp-megamenu__mslider {
    display: flex;
    width: 300%;
    height: 100%;
    transition: transform 280ms cubic-bezier(0.4, 0, 0.2, 1);
  }
  .udp-megamenu__ml1,
  .udp-megamenu__ml2-wrap,
  .udp-megamenu__ml3-wrap {
    width: 33.333%;
    flex-shrink: 0;
    overflow-y: auto;
    padding: $space-sm $space-md;
  }

  // L1 items — serif
  .udp-megamenu__ml1 button {
    font-family: $font-family-serif;
    font-size: 25px;
    letter-spacing: -0.5px;
  }

  // L2 items — sans 17px
  // L3 items — sans 16px
}
```

---

## JS — flujo de `initMegaMenuMobile()`

```js
// Solo activo en mobile
if (!window.matchMedia('(max-width: 767px)').matches) return;

// Estado: { level: 0|1|2, sectionIdx: null, subKey: null }
// Acciones:
//   goSection(idx) → level=1, muestra ml2[idx], slide(-33.33%), topbar nav
//   goSub(idx, subKey) → level=2, muestra ml3[subKey], slide(-66.66%), topbar sub
//   back() → level--, slide, topbar
//   closeAll() → delegar a setOpen(panel, false) del JS desktop
```

El `setOpen` desktop ya gestiona el reset al cerrar — `initMegaMenuMobile` solo necesita resetear el nivel a 0 cuando el panel se cierra.

---

## Notas de implementación

- El bloque `.udp-megamenu__mobile` tiene `display: none` en desktop (via CSS) y `aria-hidden="true"` por defecto; JS lo gestiona en mobile.
- El JS desktop (`setOpen`, `setActiveItem`, etc.) no se toca — funciona en `>= md` sin cambios.
- El reset del nivel mobile (volver a L1, resetear slider) ocurre dentro del `setOpen(panel, false)` existente, detectando si estamos en mobile.
- La animación fade-in del panel al abrir sigue funcionando (está en `.udp-megamenu:not([hidden])`).
- Los SVG de los iconos se inline en PHP para evitar dependencias de font.
