# Mobile Header — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Simplificar el top-bar en mobile (logo + lupa) y añadir una barra fija inferior con el trigger del mega-menú.

**Architecture:** Nuevo partial `mobile-nav.php` incluido desde `footer.php`. El SCSS oculta `__menu` en el top-bar en `< md` y muestra la barra inferior. El JS del mega-menú se actualiza de `qs` a `qsa` para soportar múltiples triggers simultáneos.

**Tech Stack:** PHP 8.4, WordPress, SCSS/Vite 6, vanilla JS (ES modules).

---

## Mapa de archivos

| Archivo | Acción | Responsabilidad |
|---|---|---|
| `template-parts/header/mobile-nav.php` | Crear | Barra fija inferior: círculo hamburger + label "Menú" |
| `src/scss/layouts/_header.scss` | Modificar | Grid mobile 50/1fr/50, ocultar `__menu`, bloque `.udp-mobile-nav` |
| `footer.php` | Modificar | Include del partial antes de `wp_footer()` |
| `src/js/modules/mega-menu.js` | Modificar | `qs` → `qsa` en `setOpen` e `initMegaMenu`; sync `aria-expanded` en todos los triggers |

---

## Task 1: PHP partial `mobile-nav.php`

**Files:**
- Create: `template-parts/header/mobile-nav.php`

- [ ] **Step 1.1: Crear el partial**

```php
<?php
/**
 * Mobile nav — barra fija inferior (solo mobile < md)
 * Trigger del mega-menú en mobile.
 *
 * @package Starter_Theme
 */
?>
<nav class="udp-mobile-nav" aria-label="<?php esc_attr_e( 'Menú principal', 'starter-theme' ); ?>">
	<button
		type="button"
		class="udp-mobile-nav__trigger"
		data-udp-megamenu-toggle
		aria-expanded="false"
		aria-controls="udp-megamenu-panel"
		aria-label="<?php esc_attr_e( 'Abrir menú principal', 'starter-theme' ); ?>"
	>
		<span class="udp-mobile-nav__circle" aria-hidden="true">
			<svg width="26" height="26" viewBox="0 0 26 26" fill="none">
				<line x1="5" y1="9" x2="21" y2="9" stroke="currentColor" stroke-width="1.5"/>
				<line x1="5" y1="13" x2="21" y2="13" stroke="currentColor" stroke-width="1.5"/>
				<line x1="5" y1="17" x2="21" y2="17" stroke="currentColor" stroke-width="1.5"/>
			</svg>
		</span>
		<span class="udp-mobile-nav__label"><?php esc_html_e( 'Menú', 'starter-theme' ); ?></span>
	</button>
</nav>
```

- [ ] **Step 1.2: PHP lint**

```bash
/Applications/MAMP/bin/php/php8.4.17/bin/php -l template-parts/header/mobile-nav.php
```

Salida esperada: `No syntax errors detected in template-parts/header/mobile-nav.php`

- [ ] **Step 1.3: Commit**

```bash
git add template-parts/header/mobile-nav.php
git commit -m "feat(mobile-header): partial mobile-nav — barra fija inferior con trigger menú"
```

---

## Task 2: SCSS — top bar mobile + bloque `.udp-mobile-nav`

**Files:**
- Modify: `src/scss/layouts/_header.scss`

- [ ] **Step 2.1: Reducir grid y ocultar `__menu` en `< md`**

Dentro de `.udp-top-bar`, el bloque `&__inner` ya tiene un `@include media-down(md)` (línea 32). Reemplazarlo por:

```scss
// Bloque existente a reemplazar (líneas 32-34):
@include media-down(md) {
    padding-inline: $space-sm;
}

// Reemplazar por:
@include media-down(md) {
    grid-template-columns: 50px 1fr 50px;
    padding-inline: $space-sm;
}
```

Justo después del cierre de `&__inner { ... }` (antes de `&__menu { justify-self: start; }`), añadir:

```scss
// Ocultar trigger desktop en mobile — el bottom bar lo reemplaza
@include media-down(md) {
    .udp-top-bar__menu {
        display: none;
    }
}
```

- [ ] **Step 2.2: Añadir bloque `.udp-mobile-nav` al final de `_header.scss`**

Añadir al final del archivo, después del bloque `body.is-light` y antes (o después) de `.visually-hidden`:

```scss
// ==========================================================================
// BOTTOM NAV — mobile only (< md)
// Barra fija inferior con trigger del mega-menú
// ==========================================================================

.udp-mobile-nav {
    display: none;

    @include media-down(md) {
        display: flex;
        justify-content: center;
        align-items: flex-start;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 100;
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
    transition: opacity $transition-base;

    &:hover,
    &:focus {
        opacity: 0.7;
    }
}

.udp-mobile-nav__circle {
    width: 50px;
    height: 50px;
    border-radius: 9999px;
    border: 1px solid $gray-high;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.udp-mobile-nav__label {
    font-family: $font-family-body;
    font-weight: 500;
    font-size: 16px;
    line-height: 24px;
}

// Padding-bottom en body para que el contenido no quede tapado por la barra fija
@include media-down(md) {
    body {
        padding-bottom: 80px;
    }
}
```

- [ ] **Step 2.3: Build**

```bash
npm run build 2>&1 | tail -5
```

Salida esperada: build exitoso sin errores.

```bash
grep -c "udp-mobile-nav" dist/css/main.*.css
```

Salida esperada: `1` o más.

- [ ] **Step 2.4: Commit**

```bash
git add src/scss/layouts/_header.scss
git commit -m "feat(mobile-header): SCSS — grid 50/1fr/50, ocultar menu trigger, bottom nav bar"
```

---

## Task 3: `footer.php` — include del partial

**Files:**
- Modify: `footer.php`

- [ ] **Step 3.1: Añadir include antes de `wp_footer()`**

En `footer.php`, la última sección del archivo es:

```php
<?php wp_footer(); ?>
</body>
</html>
```

Añadir el include justo antes de `wp_footer()`:

```php
<?php get_template_part( 'template-parts/header/mobile-nav' ); ?>
<?php wp_footer(); ?>
</body>
</html>
```

- [ ] **Step 3.2: PHP lint**

```bash
/Applications/MAMP/bin/php/php8.4.17/bin/php -l footer.php
```

Salida esperada: `No syntax errors detected in footer.php`

- [ ] **Step 3.3: Verificar markup en HTTP**

```bash
curl -s "http://localhost:8888/udp/?theme=new" | grep -c "udp-mobile-nav"
```

Salida esperada: `1` o más (al menos el `<nav class="udp-mobile-nav">`).

- [ ] **Step 3.4: Commit**

```bash
git add footer.php
git commit -m "feat(mobile-header): incluir mobile-nav partial desde footer.php"
```

---

## Task 4: JS — soportar múltiples triggers en mega-menu.js

**Files:**
- Modify: `src/js/modules/mega-menu.js`

El archivo tiene dos lugares donde se usa `qs('[data-udp-megamenu-toggle]')`:
1. **Función `setOpen`** (líneas 20, 27, 54): lee el toggle para actualizar `aria-expanded`.
2. **Función `initMegaMenu`** (líneas 113–116): busca el toggle para añadir el listener de click.

- [ ] **Step 4.1: Actualizar `setOpen` — sincronizar `aria-expanded` en todos los triggers**

Reemplazar el bloque de líneas 20-27 y 54 en la función `setOpen`:

```js
// ANTES (líneas 20, 27 y 54):
const toggle = qs( '[data-udp-megamenu-toggle]' );
// ...
if ( toggle ) toggle.setAttribute( 'aria-expanded', 'true' );
// ...
if ( toggle ) toggle.setAttribute( 'aria-expanded', 'false' );

// DESPUÉS — reemplazar las 3 líneas individualmente:

// Línea 20, cambiar a:
const toggles = qsa( '[data-udp-megamenu-toggle]' );

// Línea 27, cambiar a:
toggles.forEach( t => t.setAttribute( 'aria-expanded', 'true' ) );

// Línea 54, cambiar a:
toggles.forEach( t => t.setAttribute( 'aria-expanded', 'false' ) );
```

El bloque `setOpen` completo queda así:

```js
function setOpen( panel, open ) {
	if ( !panel ) return;

	const toggles = qsa( '[data-udp-megamenu-toggle]' );

	if ( open ) {
		STATE.isOpen = true;
		panel.hidden = false;
		document.documentElement.classList.add( 'udp-megamenu-open' );
		document.body.classList.add( 'udp-megamenu-open' );
		toggles.forEach( t => t.setAttribute( 'aria-expanded', 'true' ) );
		STATE.lastFocused = document.activeElement;
		const closeBtn = panel.querySelector( '[data-udp-megamenu-close]' );
		if ( closeBtn ) closeBtn.focus();
	} else {
		// Fade-out antes de ocultar
		panel.classList.add( 'udp-megamenu--closing' );
		setTimeout( () => {
			panel.classList.remove( 'udp-megamenu--closing' );
			panel.hidden = true;
			STATE.isOpen = false;
			STATE.activeIdx = -1;

			// Reset DOM: quitar --active de col-1, ocultar todos los detail panels
			qsa( '.udp-megamenu__primary-item--active', panel ).forEach( el =>
				el.classList.remove( 'udp-megamenu__primary-item--active' )
			);
			qsa( '.udp-megamenu__primary-btn', panel ).forEach( btn =>
				btn.setAttribute( 'aria-expanded', 'false' )
			);
			qsa( '[data-udp-megamenu-detail]', panel ).forEach( el => {
				el.classList.remove( 'udp-megamenu__detail--active' );
				el.hidden = true;
				clearSubPanels( el );
			} );
			document.documentElement.classList.remove( 'udp-megamenu-open' );
			document.body.classList.remove( 'udp-megamenu-open' );
			toggles.forEach( t => t.setAttribute( 'aria-expanded', 'false' ) );
			if ( STATE.lastFocused && typeof STATE.lastFocused.focus === 'function' ) {
				STATE.lastFocused.focus();
			}
		}, ANIM_DURATION );
	}
}
```

- [ ] **Step 4.2: Actualizar `initMegaMenu` — añadir listeners a todos los triggers**

Reemplazar las líneas 113–116:

```js
// ANTES:
const toggle = qs( '[data-udp-megamenu-toggle]' );
if ( !panel || !toggle ) return;

toggle.addEventListener( 'click', () => setOpen( panel, true ) );

// DESPUÉS:
const toggles = qsa( '[data-udp-megamenu-toggle]' );
if ( !panel || !toggles.length ) return;

toggles.forEach( t => t.addEventListener( 'click', () => setOpen( panel, true ) ) );
```

- [ ] **Step 4.3: Build**

```bash
npm run build 2>&1 | tail -5
```

Salida esperada: build exitoso sin errores.

- [ ] **Step 4.4: Commit**

```bash
git add src/js/modules/mega-menu.js
git commit -m "fix(mega-menu): qs → qsa para múltiples triggers — soporta bottom nav mobile"
```

---

## Task 5: Verificación E2E

- [ ] **Step 5.1: Verificar markup completo**

```bash
curl -s "http://localhost:8888/udp/?theme=new" | grep -E "udp-mobile-nav|data-udp-megamenu-toggle" | head -10
```

Salida esperada: al menos 2 líneas — el trigger del top-bar (oculto en mobile vía CSS) y el trigger de la barra inferior.

- [ ] **Step 5.2: Verificar en navegador a 375px**

Abrir `http://localhost:8888/udp/?theme=new` y redimensionar DevTools a 375px de ancho.

Comprobar:
1. El botón "Menú" del top-bar desaparece — solo se ven logo y lupa.
2. La barra inferior aparece fija al pie de pantalla: fondo oscuro, borde superior blanco, círculo hamburger + label "Menú" centrados.
3. El contenido no queda tapado por la barra (padding-bottom: 80px en body).

- [ ] **Step 5.3: Verificar que el trigger mobile abre el mega-menú**

Con DevTools en 375px:
1. Hacer click en el círculo hamburger de la barra inferior.
2. El mega-menú debe abrirse (panel `#udp-megamenu-panel` visible).
3. `aria-expanded="true"` debe estar en ambos triggers (top-bar oculto + mobile).
4. ESC cierra el panel.
5. Al cerrar, `aria-expanded="false"` en ambos triggers.

- [ ] **Step 5.4: Verificar que el trigger desktop sigue funcionando**

Ampliar DevTools a ≥ 768px:
1. La barra inferior desaparece.
2. El botón "Menú" del top-bar vuelve a ser visible.
3. Click en "Menú" abre el mega-menú normalmente.

- [ ] **Step 5.5: Actualizar MEMORY.md**

Añadir al final de MEMORY.md (en el repo):

```markdown
### 2026-06-11 — Mobile header: top bar simplificado + bottom nav bar

- Top bar en `< md`: `__menu` oculto, grid `50px 1fr 50px` (logo centrado + lupa).
- Nuevo `template-parts/header/mobile-nav.php` (barra fija inferior): círculo hamburger + "Menú", incluido desde `footer.php`.
- `mega-menu.js`: `qs → qsa` en `setOpen` e `initMegaMenu` — sincroniza `aria-expanded` en todos los triggers.
- Pendiente: adaptar el panel del mega-menú al diseño mobile (fase siguiente).
```

```bash
git add MEMORY.md
git commit -m "docs(memory): mobile header — bottom nav bar completado"
```
