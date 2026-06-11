# Mega-menú mobile — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implementar la navegación mobile del mega-menú: panel fondo claro con tres niveles (secciones → apartados → sub-items) que se deslizan horizontalmente.

**Architecture:** Nuevo bloque `.udp-megamenu__mobile` añadido al final de `mega-menu.php` con los tres paneles pre-renderizados en PHP. El SCSS oculta el layout desktop (`__top`, `__body`, `__footer`) en `< md` y muestra el bloque mobile con un slider CSS de 300% de ancho. El JS añade `initMegaMenuMobile()` que gestiona el slide, la top bar dinámica (logo en L1, ← título × en L2/L3) y el reset al cerrar. El JS desktop no se modifica.

**Tech Stack:** PHP 8.4, WordPress, SCSS/Vite 6, vanilla JS ES modules.

---

## Mapa de archivos

| Archivo | Acción | Responsabilidad |
|---|---|---|
| `template-parts/header/mega-menu.php` | Modificar | Añadir bloque `.udp-megamenu__mobile` con L1/L2/L3 pre-renderizados |
| `src/scss/layouts/_mega-menu.scss` | Modificar | Ocultar desktop en `< md`; estilos slider, top bar y items mobile |
| `src/js/modules/mega-menu.js` | Modificar | Añadir funciones mobile y `initMegaMenuMobile()`; reset en `setOpen` |

---

## Task 1: PHP — bloque `.udp-megamenu__mobile`

**Files:**
- Modify: `template-parts/header/mega-menu.php`

- [ ] **Step 1.1: Añadir el bloque mobile al final del panel**

En `mega-menu.php`, localizar la última línea del archivo:
```php
</div>
```
(el `</div>` de cierre de `#udp-megamenu-panel`, línea 273)

Insertar justo **antes** de ese `</div>` de cierre, pero **después** del `</footer>` del footer:

```php
<?php if ( ! empty( $menu_items ) ) :

	// SVG inline para botones mobile (sin dependencia de font)
	$svg_mob_back  = '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 14L4 9l5-5"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/></svg>';
	$svg_mob_close = '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" viewBox="0 0 14 14"><line x1="2" y1="2" x2="12" y2="12"/><line x1="12" y1="2" x2="2" y2="12"/></svg>';
	$svg_mob_chev  = '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 16 16"><path d="M6 3l5 5-5 5"/></svg>';
	$svg_mob_arr   = '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 16 16"><path d="M3 8h10M8 3l5 5-5 5"/></svg>';
	$svg_mob_ext   = '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 16 16"><path d="M11 11V5H5M11 5L5 11"/></svg>';
?>
<div class="udp-megamenu__mobile">

	<!-- TOP BAR DINÁMICA -->
	<div class="udp-megamenu__mtop">

		<div class="udp-megamenu__mtop-l1" id="udp-mob-topbar-l1">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="udp-megamenu__mlogo" aria-label="<?php bloginfo( 'name' ); ?>">
				<?php
				$logo = function_exists( 'udp_get_logo_url' ) ? udp_get_logo_url( 'udp' ) : '';
				if ( ! empty( $logo ) ) : ?>
					<img src="<?php echo esc_url( $logo ); ?>" alt="<?php bloginfo( 'name' ); ?>" />
				<?php else : ?>
					<span class="udp-megamenu__mlogo-text"><?php bloginfo( 'name' ); ?></span>
				<?php endif; ?>
			</a>
		</div>

		<div class="udp-megamenu__mtop-nav" id="udp-mob-topbar-nav" hidden>
			<button class="udp-megamenu__mback" type="button" data-udp-mob-back aria-label="<?php esc_attr_e( 'Volver', 'starter-theme' ); ?>">
				<?php echo $svg_mob_back; // phpcs:ignore ?>
			</button>
			<span class="udp-megamenu__mtitle" id="udp-mob-title"></span>
			<button class="udp-megamenu__mclose-all" type="button" data-udp-megamenu-close aria-label="<?php esc_attr_e( 'Cerrar menú', 'starter-theme' ); ?>">
				<?php echo $svg_mob_close; // phpcs:ignore ?>
			</button>
		</div>

	</div><!-- /.udp-megamenu__mtop -->

	<!-- VIEWPORT SLIDER -->
	<div class="udp-megamenu__mviewport">
		<div class="udp-megamenu__mslider" id="udp-megamenu-mslider">

			<!-- L1: lista de secciones -->
			<div class="udp-megamenu__ml1">
				<ul class="udp-megamenu__mlist">
					<?php foreach ( $menu_items as $idx => $item ) :
						$titulo = $item['titulo_main_link'] ?? '';
						if ( ! $titulo ) continue;
					?>
						<li class="udp-megamenu__mitem">
							<button
								type="button"
								class="udp-megamenu__mbtn"
								data-udp-mob-section="<?php echo esc_attr( $idx ); ?>"
								data-udp-mob-title="<?php echo esc_attr( wp_strip_all_tags( $titulo ) ); ?>"
							>
								<span class="udp-megamenu__mbtn-label"><?php echo esc_html( wp_strip_all_tags( $titulo ) ); ?></span>
								<?php echo $svg_mob_chev; // phpcs:ignore ?>
							</button>
						</li>
					<?php endforeach; ?>
				</ul>
			</div><!-- /.udp-megamenu__ml1 -->

			<!-- L2-wrap: un panel por sección, solo uno visible a la vez -->
			<div class="udp-megamenu__ml2-wrap">
				<?php foreach ( $menu_items as $idx => $item ) :
					$titulo  = $item['titulo_main_link'] ?? '';
					$submenu = is_array( $item['submenu'] ?? null ) ? $item['submenu'] : [];
					if ( ! $titulo ) continue;
				?>
					<div
						class="udp-megamenu__ml2"
						data-udp-mob-l2="<?php echo esc_attr( $idx ); ?>"
						hidden
					>
						<ul class="udp-megamenu__mlist">
							<?php foreach ( $submenu as $sub_idx => $sub ) :
								$sub_titulo = $sub['titulo'] ?? '';
								$sub_tipo   = $sub['tipo']   ?? 'externo';
								$sub_items  = is_array( $sub['sub_items'] ?? null ) ? $sub['sub_items'] : [];
								$has_sub    = ! empty( $sub_items );
								if ( ! $sub_titulo ) continue;

								if ( $sub_tipo === 'interno' && ! empty( $sub['pagina'] ) ) {
									$sub_anchor = ltrim( $sub['anchor'] ?? '', '#' );
									$sub_link   = get_permalink( $sub['pagina'] ) . ( $sub_anchor ? '#' . $sub_anchor : '' );
									$is_ext     = false;
								} elseif ( $sub_tipo === 'sin_link' ) {
									$sub_link = '';
									$is_ext   = false;
								} else {
									$sub_link = $sub['url'] ?? $sub['link'] ?? '';
									$is_ext   = $sub_link ? udp_megamenu_is_external( $sub_link ) : false;
								}
							?>
								<li class="udp-megamenu__mitem">
									<?php if ( $has_sub ) : ?>
										<button
											type="button"
											class="udp-megamenu__mbtn udp-megamenu__mbtn--l2"
											data-udp-mob-sub="<?php echo esc_attr( $sub_idx ); ?>"
											data-udp-mob-title="<?php echo esc_attr( $sub_titulo ); ?>"
										>
											<span class="udp-megamenu__mbtn-label udp-megamenu__mbtn-label--l2"><?php echo esc_html( $sub_titulo ); ?></span>
											<?php echo $svg_mob_arr; // phpcs:ignore ?>
										</button>
									<?php elseif ( $sub_link ) : ?>
										<a
											class="udp-megamenu__mlink udp-megamenu__mlink--l2"
											href="<?php echo esc_url( $sub_link ); ?>"
											<?php if ( $is_ext ) echo 'target="_blank" rel="noopener noreferrer"'; ?>
										>
											<span><?php echo esc_html( $sub_titulo ); ?></span>
											<?php if ( $is_ext ) echo $svg_mob_ext; // phpcs:ignore ?>
										</a>
									<?php else : ?>
										<span class="udp-megamenu__mlink udp-megamenu__mlink--l2 udp-megamenu__mlink--no-url">
											<?php echo esc_html( $sub_titulo ); ?>
										</span>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					</div><!-- /.udp-megamenu__ml2 -->
				<?php endforeach; ?>
			</div><!-- /.udp-megamenu__ml2-wrap -->

			<!-- L3-wrap: un panel por sección-apartado, solo uno visible a la vez -->
			<div class="udp-megamenu__ml3-wrap">
				<?php foreach ( $menu_items as $idx => $item ) :
					$titulo  = $item['titulo_main_link'] ?? '';
					$submenu = is_array( $item['submenu'] ?? null ) ? $item['submenu'] : [];
					if ( ! $titulo ) continue;
					foreach ( $submenu as $sub_idx => $sub ) :
						$sub_items = is_array( $sub['sub_items'] ?? null ) ? $sub['sub_items'] : [];
						if ( empty( $sub_items ) ) continue;
				?>
						<div
							class="udp-megamenu__ml3"
							data-udp-mob-l3="<?php echo esc_attr( $idx ); ?>-<?php echo esc_attr( $sub_idx ); ?>"
							hidden
						>
							<ul class="udp-megamenu__mlist">
								<?php foreach ( $sub_items as $si ) :
									$si_tipo = $si['tipo'] ?? 'externo';
									if ( $si_tipo === 'interno' && ! empty( $si['pagina'] ) ) {
										$si_titulo = ! empty( $si['titulo_alt'] ) ? $si['titulo_alt'] : get_the_title( $si['pagina'] );
										$si_anchor = ltrim( $si['anchor'] ?? '', '#' );
										$si_link   = get_permalink( $si['pagina'] ) . ( $si_anchor ? '#' . $si_anchor : '' );
										$si_ext    = false;
									} else {
										$si_titulo = $si['titulo']             ?? '';
										$si_link   = $si['url'] ?? $si['link'] ?? '';
										$si_ext    = ! empty( $si['nueva_pestana'] );
									}
									if ( ! $si_titulo || ! $si_link ) continue;
								?>
									<li class="udp-megamenu__mitem">
										<a
											class="udp-megamenu__mlink udp-megamenu__mlink--l3"
											href="<?php echo esc_url( $si_link ); ?>"
											<?php if ( $si_ext ) echo 'target="_blank" rel="noopener noreferrer"'; ?>
										>
											<span><?php echo esc_html( $si_titulo ); ?></span>
											<?php if ( $si_ext || $si_tipo === 'externo' ) echo $svg_mob_ext; // phpcs:ignore ?>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						</div><!-- /.udp-megamenu__ml3 -->
				<?php endforeach; ?>
				<?php endforeach; ?>
			</div><!-- /.udp-megamenu__ml3-wrap -->

		</div><!-- /.udp-megamenu__mslider -->
	</div><!-- /.udp-megamenu__mviewport -->

</div><!-- /.udp-megamenu__mobile -->
<?php endif; ?>
```

- [ ] **Step 1.2: PHP lint**

```bash
/Applications/MAMP/bin/php/php8.4.17/bin/php -l template-parts/header/mega-menu.php
```

Salida esperada: `No syntax errors detected in template-parts/header/mega-menu.php`

- [ ] **Step 1.3: Verificar que el bloque aparece en el HTML**

```bash
curl -s "http://localhost:8888/udp/?theme=new" | grep -c "udp-megamenu__mobile"
```

Salida esperada: `1` o más.

- [ ] **Step 1.4: Commit**

```bash
git add template-parts/header/mega-menu.php
git commit -m "feat(megamenu-mobile): PHP — bloque mobile con L1/L2/L3 pre-renderizados"
```

---

## Task 2: SCSS — estilos mobile

**Files:**
- Modify: `src/scss/layouts/_mega-menu.scss`

- [ ] **Step 2.1: Añadir bloque de estilos mobile al final del archivo**

Localizar el final del archivo, después del último keyframe (`udp-subpanel-fadein`). Añadir:

```scss
// ==========================================================================
// MEGA-MENU MOBILE (< md)
// Layout: top bar dinámica + slider horizontal 3 paneles.
// El bloque .udp-megamenu__mobile está oculto en desktop.
// ==========================================================================

.udp-megamenu__mobile {
    display: none; // oculto en desktop — JS/CSS mobile lo muestran
}

@include media-down(md) {

    // Cambiar fondo del panel de blanco a crema
    .udp-megamenu {
        background-color: #f8f7f4;
        overflow: hidden; // el scroll queda dentro del viewport del slider
    }

    // Ocultar layout desktop
    .udp-megamenu__top,
    .udp-megamenu__body,
    .udp-megamenu__footer {
        display: none !important;
    }

    // Mostrar bloque mobile
    .udp-megamenu__mobile {
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    // ---- TOP BAR DINÁMICA ----

    .udp-megamenu__mtop {
        flex-shrink: 0;
        height: $header-height;
        border-bottom: 1px solid $dark-1;
        padding: 0 $space-md;
        display: flex;
        align-items: center;
    }

    .udp-megamenu__mtop-l1 {
        display: flex;
        align-items: center;
        width: 100%;

        &[hidden] { display: none; }
    }

    .udp-megamenu__mlogo {
        display: inline-flex;
        align-items: center;
        text-decoration: none;

        img {
            height: 32px;
            width: auto;
            display: block;
        }
    }

    .udp-megamenu__mlogo-text {
        font-family: $font-family-display;
        font-size: 16px;
        font-weight: 500;
        color: $dark-1;
    }

    .udp-megamenu__mtop-nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;

        &[hidden] { display: none; }
    }

    .udp-megamenu__mback,
    .udp-megamenu__mclose-all {
        width: 40px;
        height: 40px;
        min-width: 40px;
        border-radius: 9999px;
        border: 1px solid $gray-high;
        background: transparent;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: $dark-1;
        padding: 0;

        svg {
            display: block;
            color: $dark-1;
            stroke: $dark-1;
        }

        &:hover,
        &:focus-visible {
            border-color: $dark-1;
            outline: none;
        }
    }

    .udp-megamenu__mtitle {
        font-family: $font-family-body;
        font-size: 17px;
        font-weight: 600;
        color: $dark-1;
        flex: 1;
        text-align: center;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        padding: 0 $space-sm;
    }

    // ---- SLIDER ----

    .udp-megamenu__mviewport {
        flex: 1;
        overflow: hidden;
        min-height: 0; // necesario para que flex overflow funcione
    }

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
        padding: $space-md;
        box-sizing: border-box;
    }

    .udp-megamenu__ml2,
    .udp-megamenu__ml3 {
        &[hidden] { display: none; }
    }

    // ---- LISTAS E ITEMS ----

    .udp-megamenu__mlist {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .udp-megamenu__mitem {
        border-bottom: 0.5px solid rgba($dark-1, 0.12);

        &:last-child {
            border-bottom: none;
        }
    }

    // ---- BOTONES (L1 y L2 con sub-items) ----

    .udp-megamenu__mbtn {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        padding: 14px 0;
        background: transparent;
        border: none;
        text-align: left;
        cursor: pointer;
        color: $dark-1;
        gap: $space-sm;

        svg {
            flex-shrink: 0;
            color: rgba($dark-1, 0.4);
            stroke: rgba($dark-1, 0.4);
        }

        &:focus-visible {
            outline: 2px solid $dark-1;
            border-radius: 2px;
        }
    }

    // Etiqueta texto del botón
    .udp-megamenu__mbtn-label {
        font-family: $font-family-display; // serif — L1
        font-size: 25px;
        font-weight: 500;
        letter-spacing: -0.5px;
        color: $dark-1;
        line-height: 1.2;

        &--l2 {
            font-family: $font-family-body; // sans — L2
            font-size: 17px;
            font-weight: 500;
            letter-spacing: 0;
        }
    }

    // ---- LINKS (L2 sin sub-items y L3) ----

    .udp-megamenu__mlink {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: $space-sm;
        padding: 14px 0;
        text-decoration: none;
        color: $dark-1;

        span {
            flex: 1;
        }

        svg {
            flex-shrink: 0;
            color: rgba($dark-1, 0.4);
            stroke: rgba($dark-1, 0.4);
        }

        &--l2 {
            font-family: $font-family-body;
            font-size: 17px;
            font-weight: 500;
        }

        &--l3 {
            font-family: $font-family-body;
            font-size: 16px;
            font-weight: 500;
        }

        &--no-url {
            cursor: default;
        }

        &:not(&--no-url):hover,
        &:not(&--no-url):focus-visible {
            text-decoration: underline;
            text-underline-offset: 3px;
            outline: none;
        }
    }
}
```

- [ ] **Step 2.2: Build y verificar que compila**

```bash
npm run build 2>&1 | tail -5
```

Salida esperada: build exitoso sin errores.

```bash
grep -c "udp-megamenu__mobile" dist/css/main.*.css
```

Salida esperada: `1` o más.

- [ ] **Step 2.3: Commit**

```bash
git add src/scss/layouts/_mega-menu.scss
git commit -m "feat(megamenu-mobile): SCSS — slider 3 niveles, top bar dinámica, fondo crema"
```

---

## Task 3: JS — `initMegaMenuMobile()`

**Files:**
- Modify: `src/js/modules/mega-menu.js`

- [ ] **Step 3.1: Añadir estado y funciones mobile tras la declaración de `STATE`**

Localizar la línea tras el cierre de `STATE = { ... }` (línea ~14). Insertar:

```js
const MOBILE_MQ = window.matchMedia( '(max-width: 767px)' );

const MOB = {
	level: 0,
	sectionIdx: -1,
	sectionTitle: '',
};

function mobSlide( level ) {
	const slider = qs( '#udp-megamenu-mslider' );
	if ( slider ) slider.style.transform = `translateX(-${level * 33.333}%)`;
}

function mobShowTopbar( level, title ) {
	const l1  = qs( '#udp-mob-topbar-l1' );
	const nav = qs( '#udp-mob-topbar-nav' );
	const lbl = qs( '#udp-mob-title' );
	if ( ! l1 || ! nav ) return;
	if ( level === 0 ) {
		l1.hidden  = false;
		nav.hidden = true;
	} else {
		l1.hidden  = true;
		nav.hidden = false;
		if ( lbl ) lbl.textContent = title;
	}
}

function mobReset() {
	MOB.level       = 0;
	MOB.sectionIdx  = -1;
	MOB.sectionTitle = '';
	mobSlide( 0 );
	mobShowTopbar( 0, '' );
	qsa( '[data-udp-mob-l2]' ).forEach( el => { el.hidden = true; } );
	qsa( '[data-udp-mob-l3]' ).forEach( el => { el.hidden = true; } );
}

function mobGoSection( idx, title ) {
	qsa( '[data-udp-mob-l2]' ).forEach( el => {
		el.hidden = parseInt( el.getAttribute( 'data-udp-mob-l2' ), 10 ) !== idx;
	} );
	MOB.level        = 1;
	MOB.sectionIdx   = idx;
	MOB.sectionTitle = title;
	mobSlide( 1 );
	mobShowTopbar( 1, title );
}

function mobGoSub( subIdx, title ) {
	const key = `${MOB.sectionIdx}-${subIdx}`;
	qsa( '[data-udp-mob-l3]' ).forEach( el => {
		el.hidden = el.getAttribute( 'data-udp-mob-l3' ) !== key;
	} );
	MOB.level = 2;
	mobSlide( 2 );
	mobShowTopbar( 2, title );
}

function mobBack() {
	if ( MOB.level <= 0 ) return;
	MOB.level--;
	mobSlide( MOB.level );
	if ( MOB.level === 0 ) {
		mobShowTopbar( 0, '' );
	} else {
		mobShowTopbar( 1, MOB.sectionTitle );
	}
}
```

- [ ] **Step 3.2: Añadir `mobReset()` al cierre del menú en `setOpen`**

Localizar en `setOpen` el bloque `else` (cierre del panel). Añadir `mobReset()` justo antes del `if ( STATE.lastFocused ... )`:

```js
	} else {
		panel.hidden = true;
		STATE.isOpen  = false;
		STATE.activeIdx = -1;

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
		if ( MOBILE_MQ.matches ) mobReset();   // ← añadir esta línea
		if ( STATE.lastFocused && typeof STATE.lastFocused.focus === 'function' ) {
			STATE.lastFocused.focus();
		}
	}
```

- [ ] **Step 3.3: Añadir `initMegaMenuMobile()` al final del archivo (antes del cierre de módulo)**

Justo antes del `export function initMegaMenu()`, añadir:

```js
export function initMegaMenuMobile() {
	if ( ! MOBILE_MQ.matches ) return;

	qsa( '[data-udp-mob-section]' ).forEach( btn => {
		btn.addEventListener( 'click', () => {
			const idx   = parseInt( btn.getAttribute( 'data-udp-mob-section' ), 10 );
			const title = btn.getAttribute( 'data-udp-mob-title' ) || '';
			mobGoSection( idx, title );
		} );
	} );

	qsa( '[data-udp-mob-sub]' ).forEach( btn => {
		btn.addEventListener( 'click', () => {
			const subIdx = parseInt( btn.getAttribute( 'data-udp-mob-sub' ), 10 );
			const title  = btn.getAttribute( 'data-udp-mob-title' ) || '';
			mobGoSub( subIdx, title );
		} );
	} );

	qsa( '[data-udp-mob-back]' ).forEach( btn => {
		btn.addEventListener( 'click', mobBack );
	} );
}
```

- [ ] **Step 3.4: Llamar a `initMegaMenuMobile()` desde `main.js`**

Abrir `src/js/main.js`. Localizar la línea que importa e inicializa `initMegaMenu`:

```js
import { initMegaMenu } from '@modules/mega-menu';
// ...
initMegaMenu();
```

Cambiar a:

```js
import { initMegaMenu, initMegaMenuMobile } from '@modules/mega-menu';
// ...
initMegaMenu();
initMegaMenuMobile();
```

- [ ] **Step 3.5: Build y verificar que compila**

```bash
npm run build 2>&1 | tail -5
```

Salida esperada: build exitoso sin errores.

- [ ] **Step 3.6: Commit**

```bash
git add src/js/modules/mega-menu.js src/js/main.js
git commit -m "feat(megamenu-mobile): JS — slide 3 niveles, topbar dinámica, reset al cerrar"
```

---

## Task 4: Verificación E2E

- [ ] **Step 4.1: Verificar markup completo en HTTP**

```bash
curl -s "http://localhost:8888/udp/?theme=new" | grep -E "udp-megamenu__mobile|udp-mob-section|udp-mob-l2|udp-mob-l3" | wc -l
```

Salida esperada: `10` o más (panel + items de las 8 secciones + sub-paneles).

- [ ] **Step 4.2: Verificar en navegador a 375px — Nivel 1**

Abrir `http://localhost:8888/udp/?theme=new` con DevTools en 375px.
Abrir el mega-menú desde la barra inferior.

Comprobar:
1. Panel de fondo crema (`#f8f7f4`), no oscuro.
2. Top bar: solo logo UDP (negro).
3. Lista de secciones en serif con flecha `›` a la derecha.
4. Scroll vertical si hay más items de los que caben.
5. Los bloques desktop (`__top`, `__body`, `__footer`) no son visibles.

- [ ] **Step 4.3: Verificar Nivel 2**

Hacer click en cualquier sección (ej. "Pregrado").

Comprobar:
1. Slide horizontal al panel L2.
2. Top bar: `←` visible (borde círculo) + "Pregrado" centrado + `×` visible.
3. Lista de apartados en sans 17px.
4. Apartados con sub-items muestran flecha `→`; externos muestran `↗`.
5. `←` vuelve a L1 y la top bar recupera el logo.
6. `×` cierra el panel completamente.

- [ ] **Step 4.4: Verificar Nivel 3**

Desde L2, hacer click en un apartado con sub-items (ej. "Carreras" dentro de "Pregrado").

Comprobar:
1. Slide a L3.
2. Top bar: `←` + "Carreras" + `×`.
3. Lista de sub-items en sans 16px con iconos `↗`.
4. `←` vuelve a L2 con top bar "Pregrado".

- [ ] **Step 4.5: Verificar que desktop no se rompe**

Ampliar DevTools a ≥ 768px.

Comprobar:
1. El bloque `.udp-megamenu__mobile` no es visible.
2. El mega-menú desktop funciona con normalidad (col-1 hover → col-2, col-3).
3. No hay errores en consola.

- [ ] **Step 4.6: Actualizar MEMORY.md del repo**

Añadir al final de `MEMORY.md`:

```markdown
### 2026-06-11 — Mega-menú mobile: slide horizontal 3 niveles

- Nuevo bloque `.udp-megamenu__mobile` en `mega-menu.php`: L1 (secciones serif), L2 (apartados sans 17px), L3 (sub-items sans 16px), todos pre-renderizados en PHP.
- SCSS: fondo `#f8f7f4` en mobile, oculta desktop (`__top`, `__body`, `__footer`), slider `width:300%` con `transform` animado 280ms.
- Top bar dinámica: L1 = logo, L2+ = `←` título `×`.
- JS: `initMegaMenuMobile()` gestiona slide, topbar, back, reset al cerrar.
- Pendiente: revisar comportamiento del mega-menú al rotar dispositivo (desktop → mobile sin reload).
```

```bash
git add MEMORY.md
git commit -m "docs(memory): mega-menú mobile completado — slide 3 niveles"
```
