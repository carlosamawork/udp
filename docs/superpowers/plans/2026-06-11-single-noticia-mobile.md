# Single Noticia Mobile — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Adaptar el single de noticias (`single-post.php`) a mobile según la maqueta Figma `4041-42144`, sin alterar el desktop.

**Architecture:** Pase responsive sobre el single existente. Tres patrones nuevos solo en `<md` (≤767.98px): (1) meta apilado con chip de categoría en su línea; (2) barra inferior fija con Compartir + Menú + volver-arriba que reemplaza el share sidebar; (3) sección "Te podría interesar" en oscuro + carrusel Swiper con dots. Desktop (≥992px) queda sin cambios visuales. Se reutiliza Swiper (ya es dependencia), el toggle del mega-menú del header, y la card `card-noticia` (la variante horizontal es solo CSS, mismo DOM).

**Tech Stack:** WordPress (PHP templates), SCSS (mixins `media-down(md/lg)`), Vite 6, Swiper 12, JS módulos del tema.

**Spec:** `docs/superpowers/specs/2026-06-11-single-noticia-mobile-design.md`

**Verificación (sin runner de tests):** patrón del proyecto = `php -l` (lint PHP), `npm run build` (compila SCSS+JS), `curl`/`grep` sobre el HTML renderizado, y revisión visual en viewport 393px. La URL de un post real para pruebas: cualquier permalink de `post_type=post` en `http://localhost:8888/udp/`. Para obtener uno: `wp post list --post_type=post --posts_per_page=1 --field=url --path=/Applications/MAMP/htdocs/udp/cms` (o usar uno conocido del navegador).

> **Nota sobre permisos de build (recurrente):** si `npm run build` falla con EACCES en `dist/`, ejecutar `sudo chown -R 501:20 dist` o `mv dist .dist-root-bak && npm run build`. Ver MEMORY.md.

---

## File Structure

**Crear:**
- `template-parts/single/post-mobile-bar.php` — barra inferior fija mobile (Compartir + Menú + volver-arriba + popover fallback de compartir). Responsabilidad: markup + datos de share.
- `src/js/modules/single-post-mobile.js` — comportamiento mobile: share nativo/popover, volver-arriba, menú→mega-menú, init/destroy del carrusel de relacionados por `matchMedia`.

**Modificar:**
- `single-post.php` — incluir el partial `post-mobile-bar`.
- `template-parts/single/post-related.php` — envolver lista en viewport + añadir contenedor de dots + `data-udp-related-carousel`.
- `src/scss/templates/_noticias-single.scss` — reglas mobile: meta apilado, ocultar sidebar share, barra inferior, related dark+horizontal+carrusel.
- `src/js/main.js` — importar y llamar `initSinglePostMobile()`.
- `src/js/modules/single-post-gallery.js` — (Task 8, opcional) dots en `<md`.

---

## Task 1: Meta apilado en mobile (chip en su línea)

**Files:**
- Modify: `src/scss/templates/_noticias-single.scss` (bloque `&__meta`, ~líneas 61-69)

- [ ] **Step 1: Añadir regla mobile al bloque `&__meta`**

En `_noticias-single.scss`, el bloque actual es:

```scss
    &__meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: $space-sm;
        margin-top: $space-lg;
        padding-bottom: $space-md;
        border-bottom: 1px solid rgba($dark-1, 0.15);
    }
```

Reemplazarlo por (añade la media query interna; mantiene el desktop idéntico):

```scss
    &__meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: $space-sm;
        margin-top: $space-lg;
        padding-bottom: $space-md;
        border-bottom: 1px solid rgba($dark-1, 0.15);

        @include media-down(md) {
            flex-direction: column;
            align-items: flex-start;
            gap: 6px;
        }
    }
```

- [ ] **Step 2: Compilar**

Run: `npm run build`
Expected: build OK sin errores; CSS regenerado.

- [ ] **Step 3: Verificar que el desktop no cambió y mobile apila**

Run: `grep -n "flex-direction: column" dist/css/main.*.css | head`
Expected: aparece la regla. Revisión visual en DevTools @393px: "Fecha" / fecha / chip amarillo en tres líneas; @1200px sin cambios (en fila).

- [ ] **Step 4: Commit**

```bash
git add src/scss/templates/_noticias-single.scss
git commit -m "feat(single-noticia): meta apilado en mobile (chip categoría en su línea)"
```

---

## Task 2: Partial barra inferior + include

**Files:**
- Create: `template-parts/single/post-mobile-bar.php`
- Modify: `single-post.php` (tras la línea del `post-share`)

- [ ] **Step 1: Crear `template-parts/single/post-mobile-bar.php`**

```php
<?php
/**
 * Single Post > Barra inferior mobile (fija, solo <md)
 *
 * Compartir (nativo + fallback popover) + Menú (dispara el mega-menú del header)
 * + volver-arriba. Oculta en >=md vía CSS.
 *
 * @package Starter_Theme
 *
 * @var array $args ['post_id' => int]
 */
$post_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();
if ( ! $post_id ) {
    return;
}

$url   = get_permalink( $post_id );
$title = get_the_title( $post_id );

$facebook = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url );
$twitter  = 'https://twitter.com/intent/tweet?url=' . rawurlencode( $url ) . '&text=' . rawurlencode( $title );
$whatsapp = 'https://api.whatsapp.com/send?text=' . rawurlencode( $title . ' ' . $url );
$linkedin = 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( $url );
?>
<div
    class="udp-single-post__mobile-bar"
    data-udp-mobile-bar
    data-share-url="<?php echo esc_attr( $url ); ?>"
    data-share-title="<?php echo esc_attr( $title ); ?>"
>
    <button type="button" class="udp-single-post__mobile-share" data-udp-mobile-share
        aria-label="<?php esc_attr_e( 'Compartir', 'starter-theme' ); ?>" aria-expanded="false">
        <svg width="20" height="20" viewBox="0 0 18 18" fill="none" aria-hidden="true">
            <circle cx="4" cy="9" r="2" stroke="currentColor" stroke-width="1.3"/>
            <circle cx="13" cy="4" r="2" stroke="currentColor" stroke-width="1.3"/>
            <circle cx="13" cy="14" r="2" stroke="currentColor" stroke-width="1.3"/>
            <path d="M5.7 8l5.6-3M5.7 10l5.6 3" stroke="currentColor" stroke-width="1.3"/>
        </svg>
    </button>

    <button type="button" class="udp-single-post__mobile-menu" data-udp-mobile-menu
        aria-label="<?php esc_attr_e( 'Abrir menú', 'starter-theme' ); ?>">
        <span class="udp-single-post__mobile-menu-icon" aria-hidden="true">
            <svg width="22" height="22" viewBox="0 0 26 26" fill="none">
                <line x1="5" y1="9"  x2="21" y2="9"  stroke="currentColor" stroke-width="1.5"/>
                <line x1="5" y1="13" x2="21" y2="13" stroke="currentColor" stroke-width="1.5"/>
                <line x1="5" y1="17" x2="21" y2="17" stroke="currentColor" stroke-width="1.5"/>
            </svg>
        </span>
        <span class="udp-single-post__mobile-menu-label"><?php esc_html_e( 'Menú', 'starter-theme' ); ?></span>
    </button>

    <button type="button" class="udp-single-post__mobile-top" data-udp-mobile-top
        aria-label="<?php esc_attr_e( 'Volver arriba', 'starter-theme' ); ?>">
        <svg width="20" height="20" viewBox="0 0 18 18" fill="none" aria-hidden="true">
            <path d="M9 14V4M5 8l4-4 4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </button>

    <div class="udp-single-post__mobile-sheet" data-udp-mobile-sheet hidden>
        <button type="button" class="udp-single-post__mobile-sheet-action" data-udp-copy-url data-url="<?php echo esc_attr( $url ); ?>">
            <?php esc_html_e( 'Copiar enlace', 'starter-theme' ); ?>
            <span class="udp-single-post__mobile-sheet-toast" data-udp-copy-toast hidden><?php esc_html_e( 'Copiado', 'starter-theme' ); ?></span>
        </button>
        <a class="udp-single-post__mobile-sheet-action" href="<?php echo esc_url( $facebook ); ?>" target="_blank" rel="noopener noreferrer">Facebook</a>
        <a class="udp-single-post__mobile-sheet-action" href="<?php echo esc_url( $twitter ); ?>" target="_blank" rel="noopener noreferrer">X</a>
        <a class="udp-single-post__mobile-sheet-action" href="<?php echo esc_url( $whatsapp ); ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a>
        <a class="udp-single-post__mobile-sheet-action" href="<?php echo esc_url( $linkedin ); ?>" target="_blank" rel="noopener noreferrer">LinkedIn</a>
    </div>
</div>
```

- [ ] **Step 2: Incluir el partial en `single-post.php`**

En `single-post.php`, el bloque actual es:

```php
        get_template_part( 'template-parts/single/post-hero', null, array( 'post_id' => get_the_ID() ) );
        get_template_part( 'template-parts/single/post-share', null, array( 'post_id' => get_the_ID() ) );
        ?>
```

Añadir la barra mobile justo después del share:

```php
        get_template_part( 'template-parts/single/post-hero', null, array( 'post_id' => get_the_ID() ) );
        get_template_part( 'template-parts/single/post-share', null, array( 'post_id' => get_the_ID() ) );
        get_template_part( 'template-parts/single/post-mobile-bar', null, array( 'post_id' => get_the_ID() ) );
        ?>
```

- [ ] **Step 3: Lint PHP**

Run: `php -l template-parts/single/post-mobile-bar.php && php -l single-post.php`
Expected: `No syntax errors detected` en ambos.

- [ ] **Step 4: Verificar markup en HTML renderizado**

Run: `curl -s "<URL_DE_UN_POST>" | grep -c "udp-single-post__mobile-bar"`
Expected: `1`. (Aún sin estilos: aparecerá inline al final del article; se estiliza en Task 3.)

- [ ] **Step 5: Commit**

```bash
git add template-parts/single/post-mobile-bar.php single-post.php
git commit -m "feat(single-noticia): partial barra inferior mobile (markup)"
```

---

## Task 3: SCSS barra inferior + ocultar sidebar share en mobile

**Files:**
- Modify: `src/scss/templates/_noticias-single.scss`

- [ ] **Step 1: Ocultar el sidebar share en `<md`**

En el bloque `&__share` (actualmente ~líneas 152-165), añadir una regla `media-down(md)` que lo oculte. El bloque actual:

```scss
    &__share {
        position: fixed;
        right: 24px;
        top: 50%;
        transform: translateY(-50%);
        z-index: 50;

        @include media-down(lg) {
            position: static;
            transform: none;
            margin: $space-2xl auto 0;
            text-align: center;
        }
    }
```

Reemplazar por (la barra inferior sustituye al share en teléfono; tablet conserva la fila estática actual):

```scss
    &__share {
        position: fixed;
        right: 24px;
        top: 50%;
        transform: translateY(-50%);
        z-index: 50;

        @include media-down(lg) {
            position: static;
            transform: none;
            margin: $space-2xl auto 0;
            text-align: center;
        }

        @include media-down(md) {
            display: none;
        }
    }
```

- [ ] **Step 2: Añadir el bloque de la barra inferior al final de `_noticias-single.scss`**

Añadir al final del archivo (antes o después del bloque `.udp-single-post__gallery`, da igual; usar al final):

```scss
// --------------------------------------------------------------------------
// SINGLE POST — Barra inferior mobile (solo <md)
// --------------------------------------------------------------------------
.udp-single-post__mobile-bar {
    display: none;

    @include media-down(md) {
        display: flex;
        align-items: center;
        justify-content: center;
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 60;
        height: 88px;
        padding: 0 $space-md;
        background-color: $dark-1;
        border-top: 1px solid rgba($white, 0.15);
    }
}

.udp-single-post__mobile-share,
.udp-single-post__mobile-top {
    position: absolute;
    bottom: calc(88px - 24px); // flotan apoyados sobre el borde superior de la barra
    width: 48px;
    height: 48px;
    border-radius: 9999px;
    border: 1px solid rgba($white, 0.4);
    background-color: $dark-1;
    color: $white;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color $transition-base, color $transition-base;

    &:hover,
    &:focus-visible {
        background-color: $white;
        color: $dark-1;
        outline: none;
    }
}

.udp-single-post__mobile-share { left: $space-md; }
.udp-single-post__mobile-top   { right: $space-md; }

.udp-single-post__mobile-menu {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: 1px solid rgba($white, 0.4);
    border-radius: 9999px;
    background-color: transparent;
    color: $white;
    font-family: $font-family-body;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color $transition-base, color $transition-base;

    &:hover,
    &:focus-visible {
        background-color: $white;
        color: $dark-1;
        outline: none;
    }

    &-icon {
        display: inline-flex;
    }
}

.udp-single-post__mobile-sheet {
    position: fixed;
    left: $space-md;
    right: $space-md;
    bottom: 96px;
    z-index: 61;
    display: flex;
    flex-direction: column;
    background-color: $white;
    border: 1px solid rgba($dark-1, 0.15);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);

    &[hidden] { display: none; }

    &-action {
        position: relative;
        display: block;
        padding: 14px 18px;
        font-family: $font-family-body;
        font-size: 15px;
        color: $dark-1;
        text-decoration: none;
        background: transparent;
        border: 0;
        border-bottom: 1px solid rgba($dark-1, 0.1);
        text-align: left;
        cursor: pointer;

        &:last-child { border-bottom: 0; }

        &:hover,
        &:focus-visible {
            background-color: rgba($dark-1, 0.05);
            outline: none;
        }
    }

    &-toast {
        margin-left: 8px;
        font-size: 12px;
        color: $brand-blue;

        &[hidden] { display: none; }
    }
}
```

- [ ] **Step 3: Compilar**

Run: `npm run build`
Expected: build OK.

- [ ] **Step 4: Verificar**

Run: `grep -c "udp-single-post__mobile-bar" dist/css/main.*.css`
Expected: ≥1. Revisión visual @393px: barra oscura fija abajo con "Menú" centrado, círculo Compartir (izq) y volver-arriba (der) flotando sobre el borde; @1200px: barra oculta, sidebar share visible como hoy.

- [ ] **Step 5: Commit**

```bash
git add src/scss/templates/_noticias-single.scss
git commit -m "feat(single-noticia): SCSS barra inferior mobile + ocultar sidebar share en <md"
```

---

## Task 4: JS módulo mobile (share + menú + volver-arriba)

**Files:**
- Create: `src/js/modules/single-post-mobile.js`
- Modify: `src/js/main.js`

> El carrusel de relacionados se añade a este mismo módulo en Task 7. Esta tarea deja la función `initRelatedCarousel` como no-op (return temprano si no existe el contenedor), para no romper el build.

- [ ] **Step 1: Crear `src/js/modules/single-post-mobile.js`**

```js
/**
 * Single Post — Comportamiento mobile (barra inferior + carrusel relacionados)
 */
import { qs, qsa } from '@utils/dom';

function initShare(bar) {
    const btn = qs('[data-udp-mobile-share]', bar);
    const sheet = qs('[data-udp-mobile-sheet]', bar);
    if (!btn) return;

    const url = bar.dataset.shareUrl || window.location.href;
    const title = bar.dataset.shareTitle || document.title;

    btn.addEventListener('click', async () => {
        if (navigator.share) {
            try {
                await navigator.share({ title, url });
            } catch (e) {
                /* usuario canceló: no-op */
            }
            return;
        }
        if (sheet) {
            const willOpen = sheet.hidden;
            sheet.hidden = !willOpen;
            btn.setAttribute('aria-expanded', String(willOpen));
        }
    });

    const copyBtn = sheet ? sheet.querySelector('[data-udp-copy-url]') : null;
    if (copyBtn) {
        copyBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            const toast = copyBtn.querySelector('[data-udp-copy-toast]');
            const value = copyBtn.dataset.url || url;
            const done = () => {
                if (!toast) return;
                toast.hidden = false;
                setTimeout(() => { toast.hidden = true; }, 1500);
            };
            try {
                await navigator.clipboard.writeText(value);
                done();
            } catch (err) {
                window.prompt('Copia el enlace:', value);
            }
        });
    }

    document.addEventListener('click', (e) => {
        if (sheet && !sheet.hidden && !bar.contains(e.target)) {
            sheet.hidden = true;
            btn.setAttribute('aria-expanded', 'false');
        }
    });
}

function initMenu(bar) {
    const btn = qs('[data-udp-mobile-menu]', bar);
    if (!btn) return;
    btn.addEventListener('click', () => {
        const toggle = qs('[data-udp-megamenu-toggle]');
        if (toggle) toggle.click();
    });
}

function initTop(bar) {
    const btn = qs('[data-udp-mobile-top]', bar);
    if (!btn) return;
    btn.addEventListener('click', () => {
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
    });
}

// Placeholder — se implementa en Task 7.
async function initRelatedCarousel() {
    const el = qs('[data-udp-related-carousel]');
    if (!el) return;
}

export function initSinglePostMobile() {
    const bar = qs('[data-udp-mobile-bar]');
    if (bar) {
        initShare(bar);
        initMenu(bar);
        initTop(bar);
    }
    initRelatedCarousel();
}
```

- [ ] **Step 2: Wire en `src/js/main.js`**

Añadir el import junto a los demás módulos (tras `import { initSinglePostGallery } ...`):

```js
import { initSinglePostMobile } from '@modules/single-post-mobile';
```

Y la llamada dentro de `domReady(() => { ... })`, tras `initSinglePostGallery();`:

```js
    initSinglePostMobile();
```

- [ ] **Step 3: Compilar**

Run: `npm run build`
Expected: build OK; `single-post-mobile` incluido en el bundle.

- [ ] **Step 4: Verificar**

Run: `grep -c "data-udp-mobile-share\|udp-megamenu-toggle" dist/js/main.*.js`
Expected: ≥1. Prueba manual @393px: el botón "Menú" abre el mega-menú; "↑" sube; "Compartir" abre la hoja nativa (o el popover en navegadores sin `navigator.share`).

- [ ] **Step 5: Commit**

```bash
git add src/js/modules/single-post-mobile.js src/js/main.js
git commit -m "feat(single-noticia): JS barra mobile (share nativo/popover, menú, volver-arriba)"
```

---

## Task 5: `post-related.php` — estructura para carrusel

**Files:**
- Modify: `template-parts/single/post-related.php` (bloque de markup, ~líneas 63-81)

- [ ] **Step 1: Reemplazar el `<section>` de markup**

El bloque actual:

```php
<section class="udp-single-post__related">
    <div class="udp-single-post__related-inner">
        <h2 class="udp-single-post__related-title"><?php esc_html_e( 'Te podría interesar', 'starter-theme' ); ?></h2>
        <ul class="udp-single-post__related-list">
            <?php foreach ( $cards as $card ) : ?>
                <li class="udp-single-post__related-item">
                    <?php
                    get_template_part(
                        'template-parts/blocks/parts/card-noticia',
                        null,
                        array( 'card' => $card, 'theme' => 'light' )
                    );
                    ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
```

Reemplazar por (añade `data-udp-related-carousel`, el wrapper `viewport` y el contenedor de dots; mantiene `theme => 'light'` y la card por defecto para no tocar el desktop):

```php
<section class="udp-single-post__related" data-udp-related-carousel>
    <div class="udp-single-post__related-inner">
        <h2 class="udp-single-post__related-title"><?php esc_html_e( 'Te podría interesar', 'starter-theme' ); ?></h2>
        <div class="udp-single-post__related-viewport">
            <ul class="udp-single-post__related-list">
                <?php foreach ( $cards as $card ) : ?>
                    <li class="udp-single-post__related-item">
                        <?php
                        get_template_part(
                            'template-parts/blocks/parts/card-noticia',
                            null,
                            array( 'card' => $card, 'theme' => 'light' )
                        );
                        ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="udp-single-post__related-dots" aria-hidden="true"></div>
    </div>
</section>
```

- [ ] **Step 2: Lint PHP**

Run: `php -l template-parts/single/post-related.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Verificar markup**

Run: `curl -s "<URL_DE_UN_POST>" | grep -c "udp-single-post__related-viewport"`
Expected: `1` (si el post tiene relacionados con imagen). El grid desktop sigue intacto (el `viewport` es un div neutro que envuelve el `ul`).

- [ ] **Step 4: Commit**

```bash
git add template-parts/single/post-related.php
git commit -m "feat(single-noticia): estructura viewport+dots en relacionados (carrusel mobile)"
```

---

## Task 6: SCSS relacionados oscuro + horizontal + carrusel (`<md`)

**Files:**
- Modify: `src/scss/templates/_noticias-single.scss`

- [ ] **Step 1: Añadir bloque mobile de relacionados al final del archivo**

Añadir al final de `_noticias-single.scss`. Sobrescribe SOLO en `<md` (desktop intacto). Convierte el grid en flex/carrusel, fondo oscuro, cards horizontales con texto claro, y estiliza los dots:

```scss
// --------------------------------------------------------------------------
// SINGLE POST — Relacionados: oscuro + carrusel horizontal (solo <md)
// --------------------------------------------------------------------------
@include media-down(md) {
    .udp-single-post__related {
        background-color: $dark-1;
    }

    .udp-single-post__related-title {
        color: $white;
    }

    // El grid pasa a fila (Swiper toma el control al inicializar)
    .udp-single-post__related-list {
        display: flex;
        grid-template-columns: none;
        gap: $space-md;
    }

    .udp-single-post__related-item {
        width: 88vw;
        max-width: 320px;
        flex-shrink: 0;
    }

    // Card por defecto (vertical) → layout horizontal + texto claro, scoped a relacionados
    .udp-single-post__related .udp-card-noticia {
        flex-direction: row;
        align-items: stretch;
        gap: $space-md;
        background-color: transparent;
    }

    .udp-single-post__related .udp-card-noticia__media {
        flex: 0 0 140px;
        width: 140px;
        aspect-ratio: 140 / 200;
        overflow: hidden;
    }

    .udp-single-post__related .udp-card-noticia__media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .udp-single-post__related .udp-card-noticia__body {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .udp-single-post__related .udp-card-noticia__title {
        color: $white;
    }

    .udp-single-post__related .udp-card-noticia__date {
        color: rgba($white, 0.7);
    }

    .udp-single-post__related .udp-card-noticia__more {
        color: $white;
        margin-top: auto;
    }

    // Dots de paginación (Swiper bullets)
    .udp-single-post__related-dots {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: $space-lg;

        .swiper-pagination-bullet {
            width: 8px;
            height: 8px;
            border-radius: 9999px;
            background-color: rgba($white, 0.35);
            opacity: 1;
            transition: background-color $transition-base, width $transition-base;
        }

        .swiper-pagination-bullet-active {
            width: 22px;
            background-color: $brand-blue;
        }
    }
}
```

> Nota: la card `udp-card-noticia` por defecto usa flex columna; aquí sobre `flex-direction: row` asumiendo que `.udp-card-noticia` es `display:flex`. Si en `_card-grid.scss` la card base es `display:block`, añadir `display: flex;` en la regla `.udp-single-post__related .udp-card-noticia` de arriba. Verificar en Step 2.

- [ ] **Step 2: Confirmar display base de la card**

Run: `grep -n -A3 "^\.udp-card-noticia {" src/scss/blocks/_card-grid.scss`
Expected: ver si la card base es `display: flex` o `block`. Si NO es flex, añadir `display: flex;` a la regla `.udp-single-post__related .udp-card-noticia` del Step 1 antes de compilar.

- [ ] **Step 3: Compilar**

Run: `npm run build`
Expected: build OK.

- [ ] **Step 4: Verificar**

Run: `grep -c "swiper-pagination-bullet" dist/css/main.*.css`
Expected: ≥1. Revisión visual @393px: sección "Te podría interesar" en oscuro, cards horizontales (imagen izq), texto blanco. @1200px: grid claro de 3 columnas sin cambios. (El swipe/dots se activan en Task 7.)

- [ ] **Step 5: Commit**

```bash
git add src/scss/templates/_noticias-single.scss
git commit -m "feat(single-noticia): relacionados oscuro + horizontal + dots en mobile"
```

---

## Task 7: JS carrusel de relacionados (init/destroy por matchMedia)

**Files:**
- Modify: `src/js/modules/single-post-mobile.js` (función `initRelatedCarousel`)

- [ ] **Step 1: Reemplazar el placeholder `initRelatedCarousel`**

Sustituir la función placeholder por la implementación completa. Inicializa Swiper solo en `<md` y lo destruye (limpiando las clases swiper) al volver a desktop, para que el grid CSS de desktop quede intacto:

```js
async function initRelatedCarousel() {
    const el = qs('[data-udp-related-carousel]');
    if (!el) return;

    const viewport = el.querySelector('.udp-single-post__related-viewport');
    const list = el.querySelector('.udp-single-post__related-list');
    const items = qsa('.udp-single-post__related-item', el);
    const pagination = el.querySelector('.udp-single-post__related-dots');
    if (!viewport || !list) return;

    const mq = window.matchMedia('(max-width: 767.98px)');
    let swiper = null;

    async function enable() {
        if (swiper) return;
        viewport.classList.add('swiper');
        list.classList.add('swiper-wrapper');
        items.forEach((i) => i.classList.add('swiper-slide'));

        const { default: Swiper } = await import('swiper');
        const { Pagination } = await import('swiper/modules');
        await import('swiper/css');

        swiper = new Swiper(viewport, {
            modules: [Pagination],
            slidesPerView: 'auto',
            spaceBetween: 16,
            grabCursor: true,
            pagination: { el: pagination, clickable: true },
        });
    }

    function disable() {
        if (!swiper) return;
        swiper.destroy(true, true);
        swiper = null;
        viewport.classList.remove('swiper');
        list.classList.remove('swiper-wrapper');
        items.forEach((i) => i.classList.remove('swiper-slide'));
    }

    function sync() {
        if (mq.matches) enable();
        else disable();
    }

    sync();
    mq.addEventListener('change', sync);
}
```

- [ ] **Step 2: Compilar**

Run: `npm run build`
Expected: build OK; el chunk de Swiper se reutiliza (lazy import).

- [ ] **Step 3: Verificar**

Run: `grep -c "related-carousel\|swiper/modules" dist/js/*.js dist/js/chunks/*.js 2>/dev/null | grep -v ":0" | head`
Expected: aparece referencia. Prueba manual @393px: "Te podría interesar" swipeable, dots funcionando (uno activo azul alargado). Redimensionar a ≥768px: vuelve a grid de 3 columnas sin restos de swiper (sin scroll horizontal). @1200px: idéntico a hoy.

- [ ] **Step 4: Commit**

```bash
git add src/js/modules/single-post-mobile.js
git commit -m "feat(single-noticia): carrusel relacionados con Swiper (init/destroy por matchMedia)"
```

---

## Task 8 (opcional): Galería con dots en mobile

> Polish menor. Solo si el post de prueba tiene `galeria_de_imagenes`. Si se decide diferir, saltar esta tarea.

**Files:**
- Modify: `src/js/modules/single-post-gallery.js`
- Modify: `template-parts/single/post-gallery.php`
- Modify: `src/scss/templates/_noticias-single.scss`

- [ ] **Step 1: Añadir contenedor de dots al partial**

En `post-gallery.php`, dentro de `<section class="udp-single-post__gallery" ...>`, tras el bloque `udp-single-post__gallery-nav`, añadir:

```php
    <div class="udp-single-post__gallery-dots" aria-hidden="true"></div>
```

- [ ] **Step 2: Activar Pagination en el módulo de galería**

En `single-post-gallery.js`, reemplazar la importación de módulos y la config Swiper:

```js
    const { default: Swiper } = await import('swiper');
    const { Navigation, Keyboard, Pagination } = await import('swiper/modules');
    await import('swiper/css');

    containers.forEach((el) => {
        const swiperEl = el.querySelector('.swiper');
        if (!swiperEl) return;

        new Swiper(swiperEl, {
            modules: [Navigation, Keyboard, Pagination],
            slidesPerView: 'auto',
            spaceBetween: 16,
            keyboard: { enabled: true },
            grabCursor: true,
            navigation: {
                nextEl: el.querySelector('.udp-single-post__gallery-next'),
                prevEl: el.querySelector('.udp-single-post__gallery-prev'),
            },
            pagination: {
                el: el.querySelector('.udp-single-post__gallery-dots'),
                clickable: true,
            },
            breakpoints: {
                768: { slidesPerView: 3, spaceBetween: 30 },
                0:   { slidesPerView: 1.1, spaceBetween: 12 },
            },
        });
    });
```

- [ ] **Step 3: SCSS — mostrar dots solo en `<md`, ocultar flechas**

Añadir al bloque `.udp-single-post__gallery` en `_noticias-single.scss`:

```scss
    &-dots {
        display: none;

        @include media-down(md) {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: $space-md;

            .swiper-pagination-bullet {
                width: 8px;
                height: 8px;
                border-radius: 9999px;
                background-color: rgba($dark-1, 0.25);
                opacity: 1;
            }

            .swiper-pagination-bullet-active {
                width: 22px;
                background-color: $dark-1;
            }
        }
    }

    @include media-down(md) {
        .udp-single-post__gallery-nav { display: none; }
    }
```

- [ ] **Step 4: Lint + compilar**

Run: `php -l template-parts/single/post-gallery.php && npm run build`
Expected: sin errores; build OK.

- [ ] **Step 5: Verificar**

Run: `grep -c "udp-single-post__gallery-dots" dist/css/main.*.css`
Expected: ≥1. Revisión visual @393px en un post con galería: dots bajo la galería, flechas ocultas. @1200px: flechas prev/next como hoy, sin dots.

- [ ] **Step 6: Commit**

```bash
git add src/js/modules/single-post-gallery.js template-parts/single/post-gallery.php src/scss/templates/_noticias-single.scss
git commit -m "feat(single-noticia): galería con dots en mobile"
```

---

## Cierre

- [ ] **Actualizar MEMORY.md** con una entrada de la sesión (qué se hizo, decisiones, pendientes: pase pixel-perfect fino, otras páginas mobile).
- [ ] Revisión visual final @393px vs Figma `4041-42144` y @1200px (sin regresiones desktop).
