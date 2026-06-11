# Eventos Mobile + Barra de acción global — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Globalizar la barra de acción mobile (Compartir/Menú/volver-arriba) a todas las páginas vía footer, y dejar el archive de Eventos pixel-perfect en mobile (grid + list).

**Architecture:** Parte A extrae la barra del single (ya refinada al Figma) a un componente global `udp-mobile-bar` (partial en footer + SCSS layout + módulo JS), y retira la barra específica del single. Parte B ajusta `_eventos-archive.scss` y `_card-evento.scss` bajo `media-down(md)`. Desktop intacto.

**Tech Stack:** WordPress (PHP templates), SCSS (mixin `media-down(md)` = ≤767.98px), Vite 6, Swiper (ya presente).

**Spec:** `docs/superpowers/specs/2026-06-11-eventos-mobile-design.md`
**Rama:** `feature/mobile`.

**Verificación (sin runner):** `php -l`, `npm run build`, `curl`/`grep`, revisión visual @393px. Cache WPFC desactivado (dev).
**Build (problema recurrente):** si `npm run build` falla con EACCES en `dist/`, ejecutar `mv dist .dist-root-bak-$(date +%s) && npm run build` (renombrar no requiere sudo). NUNCA build con sudo.
**Lint PHP:** usar `/Applications/MAMP/bin/php/php8.4.1/bin/php -l <archivo>`.
**URL prueba single:** `http://localhost:8888/udp/feria-del-libro-udp-se-consolida-como-un-espacio-de-encuentro-e-intercambio-cultural/`
**URL prueba eventos:** `http://localhost:8888/udp/agenda-udp/` (grid) y `?view=list` (list).

---

## File Structure

**Crear:**
- `template-parts/global/mobile-action-bar.php` — barra global (clase `udp-mobile-bar`), comparte la página actual.
- `src/scss/layouts/_mobile-action-bar.scss` — estilos portados, clase `.udp-mobile-bar*`.
- `src/js/modules/mobile-action-bar.js` — `initMobileActionBar()`: share + menú + volver-arriba.

**Modificar:**
- `footer.php` — incluir el partial global antes de `wp_footer()`.
- `src/scss/main.scss` — `@import "layouts/mobile-action-bar"`.
- `src/js/main.js` — import + llamada `initMobileActionBar()`.
- `single-post.php` — quitar el include de `post-mobile-bar`.
- `src/scss/templates/_noticias-single.scss` — borrar bloque `.udp-single-post__mobile-*`.
- `src/js/modules/single-post-mobile.js` — quitar `initShare/initMenu/initTop`, dejar solo `initRelatedCarousel`.
- `src/scss/templates/_eventos-archive.scss` — título 48px + toggle inline-right en `<md`.
- `src/scss/blocks/_card-evento.scss` — grid y list `media-down(md)`.

**Borrar:**
- `template-parts/single/post-mobile-bar.php`.

---

## Task 1: Partial global `mobile-action-bar.php`

**Files:**
- Create: `template-parts/global/mobile-action-bar.php`

- [ ] **Step 1: Crear el partial**

```php
<?php
/**
 * Barra de acción mobile GLOBAL (fija abajo, solo <md)
 *
 * Compartir (nativo + fallback popover) + Menú (dispara el mega-menú del header)
 * + volver-arriba. Comparte la página ACTUAL. Se incluye desde footer.php en
 * todas las páginas; visible solo en <md vía CSS.
 *
 * @package Starter_Theme
 */
defined( 'ABSPATH' ) || exit;

if ( is_singular() ) {
    $url   = get_permalink();
    $title = get_the_title();
} else {
    // URL desde origen de confianza: home_url() fuerza scheme+host del sitio
    // (no manipulables vía cabecera Host). Evita Host Header Injection.
    global $wp;
    $path  = isset( $wp->request ) ? $wp->request : '';
    $url   = home_url( add_query_arg( array(), $path ) );
    $title = wp_get_document_title();
}

$facebook = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url );
$twitter  = 'https://twitter.com/intent/tweet?url=' . rawurlencode( $url ) . '&text=' . rawurlencode( $title );
$whatsapp = 'https://api.whatsapp.com/send?text=' . rawurlencode( $title . ' ' . $url );
$linkedin = 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( $url );
?>
<div
    class="udp-mobile-bar"
    data-udp-mobile-bar
    data-share-url="<?php echo esc_attr( $url ); ?>"
    data-share-title="<?php echo esc_attr( $title ); ?>"
>
    <button type="button" class="udp-mobile-bar__share" data-udp-mobile-share
        aria-label="<?php esc_attr_e( 'Compartir', 'starter-theme' ); ?>" aria-expanded="false">
        <svg width="20" height="20" viewBox="0 0 18 18" fill="none" aria-hidden="true">
            <circle cx="4" cy="9" r="2" stroke="currentColor" stroke-width="1.3"/>
            <circle cx="13" cy="4" r="2" stroke="currentColor" stroke-width="1.3"/>
            <circle cx="13" cy="14" r="2" stroke="currentColor" stroke-width="1.3"/>
            <path d="M5.7 8l5.6-3M5.7 10l5.6 3" stroke="currentColor" stroke-width="1.3"/>
        </svg>
    </button>

    <button type="button" class="udp-mobile-bar__menu" data-udp-mobile-menu
        aria-label="<?php esc_attr_e( 'Abrir menú', 'starter-theme' ); ?>">
        <span class="udp-mobile-bar__menu-icon" aria-hidden="true">
            <svg width="24" height="24" viewBox="0 0 26 26" fill="none">
                <line x1="5" y1="9"  x2="21" y2="9"  stroke="currentColor" stroke-width="1.5"/>
                <line x1="5" y1="13" x2="21" y2="13" stroke="currentColor" stroke-width="1.5"/>
                <line x1="5" y1="17" x2="21" y2="17" stroke="currentColor" stroke-width="1.5"/>
            </svg>
        </span>
        <span class="udp-mobile-bar__menu-label"><?php esc_html_e( 'Menú', 'starter-theme' ); ?></span>
    </button>

    <button type="button" class="udp-mobile-bar__top" data-udp-mobile-top
        aria-label="<?php esc_attr_e( 'Volver arriba', 'starter-theme' ); ?>">
        <svg width="24" height="24" viewBox="0 0 18 18" fill="none" aria-hidden="true">
            <path d="M9 14V4M5 8l4-4 4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </button>

    <div class="udp-mobile-bar__sheet" data-udp-mobile-sheet hidden>
        <button type="button" class="udp-mobile-bar__sheet-action" data-udp-copy-url data-url="<?php echo esc_attr( $url ); ?>">
            <?php esc_html_e( 'Copiar enlace', 'starter-theme' ); ?>
            <span class="udp-mobile-bar__sheet-toast" data-udp-copy-toast hidden><?php esc_html_e( 'Copiado', 'starter-theme' ); ?></span>
        </button>
        <a class="udp-mobile-bar__sheet-action" href="<?php echo esc_url( $facebook ); ?>" target="_blank" rel="noopener noreferrer">Facebook</a>
        <a class="udp-mobile-bar__sheet-action" href="<?php echo esc_url( $twitter ); ?>" target="_blank" rel="noopener noreferrer">X</a>
        <a class="udp-mobile-bar__sheet-action" href="<?php echo esc_url( $whatsapp ); ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a>
        <a class="udp-mobile-bar__sheet-action" href="<?php echo esc_url( $linkedin ); ?>" target="_blank" rel="noopener noreferrer">LinkedIn</a>
    </div>
</div>
```

- [ ] **Step 2: Lint**

Run: `/Applications/MAMP/bin/php/php8.4.1/bin/php -l template-parts/global/mobile-action-bar.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add template-parts/global/mobile-action-bar.php
git commit -m "feat(mobile-bar): partial global de barra de acción (markup)

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 2: SCSS global `_mobile-action-bar.scss`

**Files:**
- Create: `src/scss/layouts/_mobile-action-bar.scss`
- Modify: `src/scss/main.scss`

- [ ] **Step 1: Crear `src/scss/layouts/_mobile-action-bar.scss`**

```scss
// ==========================================================================
// BARRA DE ACCIÓN MOBILE GLOBAL — udp-mobile-bar (solo <md)
// Compartir + Menú + volver-arriba. Figma 4054-51922.
// ==========================================================================
.udp-mobile-bar {
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
        min-height: 80px;
        padding: 15px $space-md;
        background-color: $dark-1;
        border-top: 1px solid $white;
    }

    // Compartir (izq) y volver-arriba (der): círculos flotando sobre el borde
    &__share,
    &__top {
        position: absolute;
        bottom: calc(100% + 16px);
        width: 48px;
        height: 48px;
        border-radius: 9999px;
        border: 1px solid $gray-high;
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

    &__share { left: $space-md; }
    &__top   { right: $space-md; }

    // Menú: círculo hamburguesa + etiqueta "Menú" al lado (no pill)
    &__menu {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 0;
        border: 0;
        background-color: transparent;
        color: $white;
        font-family: $font-family-body;
        font-size: 16px;
        font-weight: 500;
        line-height: 24px;
        cursor: pointer;

        &-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            border-radius: 9999px;
            border: 1px solid $gray-high;
            transition: background-color $transition-base, color $transition-base;
        }

        &:hover &-icon,
        &:focus-visible &-icon {
            background-color: $white;
            color: $dark-1;
            outline: none;
        }
    }

    &__sheet {
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
}
```

- [ ] **Step 2: Importar en `src/scss/main.scss`**

Tras la línea `@import "layouts/search";` añadir:

```scss
@import "layouts/mobile-action-bar";
```

- [ ] **Step 3: Compilar**

Run: `npm run build` (si EACCES: `mv dist .dist-root-bak-$(date +%s) && npm run build`)
Expected: build OK.

- [ ] **Step 4: Verificar**

Run: `grep -c "udp-mobile-bar" dist/css/main.*.css`
Expected: ≥1.

- [ ] **Step 5: Commit**

```bash
git add src/scss/layouts/_mobile-action-bar.scss src/scss/main.scss
git commit -m "feat(mobile-bar): SCSS global de la barra de acción

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 3: Módulo JS global `mobile-action-bar.js`

**Files:**
- Create: `src/js/modules/mobile-action-bar.js`
- Modify: `src/js/main.js`

- [ ] **Step 1: Crear `src/js/modules/mobile-action-bar.js`**

```js
/**
 * Barra de acción mobile global: compartir + menú + volver-arriba.
 */
import { qs } from '@utils/dom';

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

export function initMobileActionBar() {
    const bar = qs('[data-udp-mobile-bar]');
    if (!bar) return;
    initShare(bar);
    initMenu(bar);
    initTop(bar);
}
```

- [ ] **Step 2: Wire en `src/js/main.js`**

(a) Tras `import { initSinglePostMobile } from '@modules/single-post-mobile';` añadir:

```js
import { initMobileActionBar } from '@modules/mobile-action-bar';
```

(b) Dentro de `domReady(() => { ... })`, tras `initSinglePostMobile();` añadir:

```js
    initMobileActionBar();
```

- [ ] **Step 3: Compilar**

Run: `npm run build` (si EACCES, mover dist y reintentar)
Expected: build OK.

- [ ] **Step 4: Verificar**

Run: `grep -c "initMobileActionBar\|data-udp-mobile-share" dist/js/main.*.js`
Expected: ≥1.

- [ ] **Step 5: Commit**

```bash
git add src/js/modules/mobile-action-bar.js src/js/main.js
git commit -m "feat(mobile-bar): módulo JS global (share/menú/volver-arriba)

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 4: Montar en footer + retirar la barra específica del single

**Files:**
- Modify: `footer.php`
- Modify: `single-post.php`
- Delete: `template-parts/single/post-mobile-bar.php`
- Modify: `src/scss/templates/_noticias-single.scss` (borrar bloque de la barra)
- Modify: `src/js/modules/single-post-mobile.js` (dejar solo el carrusel)

- [ ] **Step 1: Incluir el partial global en `footer.php`**

En `footer.php`, localizar `<?php wp_footer(); ?>` (cerca del final) y añadir JUSTO ANTES:

```php
<?php get_template_part( 'template-parts/global/mobile-action-bar' ); ?>

<?php wp_footer(); ?>
```

- [ ] **Step 2: Quitar el include de la barra en `single-post.php`**

Localizar y BORRAR la línea:

```php
        get_template_part( 'template-parts/single/post-mobile-bar', null, array( 'post_id' => get_the_ID() ) );
```

(Quedan los `get_template_part` de `post-hero` y `post-share`.)

- [ ] **Step 3: Borrar el partial específico**

```bash
git rm template-parts/single/post-mobile-bar.php
```

- [ ] **Step 4: Borrar el bloque SCSS de la barra del single**

En `src/scss/templates/_noticias-single.scss`, BORRAR íntegro el bloque que va desde el comentario:

```scss
// --------------------------------------------------------------------------
// SINGLE POST — Barra inferior mobile (solo <md)
// --------------------------------------------------------------------------
.udp-single-post__mobile-bar {
```

hasta el cierre `}` de `.udp-single-post__mobile-sheet` (incluye `.udp-single-post__mobile-bar`, `__mobile-share`, `__mobile-top`, `__mobile-menu`, `__mobile-sheet` y todos sus sub-bloques). Es decir, eliminar las reglas `.udp-single-post__mobile-*` por completo (las cubre `.udp-mobile-bar`). NO tocar los bloques de galería ni relacionados que vienen después.

- [ ] **Step 5: Reducir `single-post-mobile.js` a solo el carrusel**

Reemplazar el contenido COMPLETO de `src/js/modules/single-post-mobile.js` por:

```js
/**
 * Single Post — Carrusel de "Te podría interesar" (init/destroy por matchMedia).
 * (La barra de acción mobile ahora es global: ver mobile-action-bar.js)
 */
import { qs, qsa } from '@utils/dom';

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
    let enabling = false;

    async function enable() {
        if (swiper || enabling) return;
        enabling = true;
        viewport.classList.add('swiper');
        list.classList.add('swiper-wrapper');
        items.forEach((i) => i.classList.add('swiper-slide'));

        const { default: Swiper } = await import('swiper');
        const { Pagination } = await import('swiper/modules');
        await import('swiper/css');

        swiper = new Swiper(viewport, {
            modules: [Pagination],
            slidesPerView: 1,
            spaceBetween: 16,
            grabCursor: true,
            pagination: { el: pagination, clickable: true },
        });
        enabling = false;
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

export function initSinglePostMobile() {
    initRelatedCarousel();
}
```

(El export `initSinglePostMobile` se mantiene para no tocar `main.js`; ahora solo inicializa el carrusel.)

- [ ] **Step 6: Lint + build**

Run: `/Applications/MAMP/bin/php/php8.4.1/bin/php -l footer.php && /Applications/MAMP/bin/php/php8.4.1/bin/php -l single-post.php && npm run build`
Expected: sin errores PHP; build OK (si EACCES, mover dist y reintentar).

- [ ] **Step 7: Verificar — single con UNA sola barra (global) + carrusel intacto**

```bash
U="http://localhost:8888/udp/feria-del-libro-udp-se-consolida-como-un-espacio-de-encuentro-e-intercambio-cultural/?nc=$(date +%s)"
curl -s "$U" -o /tmp/sx.html
echo "barras (debe 1, la global): $(grep -c 'data-udp-mobile-bar' /tmp/sx.html)"
echo "clase global udp-mobile-bar (debe >=1): $(grep -oE 'class=\"udp-mobile-bar\"' /tmp/sx.html | wc -l)"
echo "clase vieja del single (debe 0): $(grep -c 'udp-single-post__mobile-bar' /tmp/sx.html)"
echo "carrusel relacionados (debe 1): $(grep -c 'data-udp-related-carousel' /tmp/sx.html)"
```
Expected: `data-udp-mobile-bar` = 1, clase global ≥1, clase vieja = 0, carrusel = 1.

- [ ] **Step 8: Verificar — eventos también tiene la barra global**

```bash
curl -s "http://localhost:8888/udp/agenda-udp/?nc=$(date +%s)" | grep -c 'data-udp-mobile-bar'
```
Expected: `1`.

- [ ] **Step 9: Commit**

```bash
git add footer.php single-post.php src/scss/templates/_noticias-single.scss src/js/modules/single-post-mobile.js
git commit -m "refactor(mobile-bar): barra global en footer; retirar la específica del single

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 5: Eventos header — título 48px + toggle a la derecha del título (`<md`)

**Files:**
- Modify: `src/scss/templates/_eventos-archive.scss`

- [ ] **Step 1: Subir el título a 48px en `<md`**

Localizar el bloque `&__title`:

```scss
    &__title {
        margin: $space-md 0 0;
        font-family: $font-family-display;
        font-weight: 500;
        font-size: 64px;
        line-height: 1.05;
        color: $white;

        @include media-down(md) {
            font-size: 40px;
        }
    }
```

Cambiar `font-size: 40px;` por `font-size: 48px;` dentro del `media-down(md)`.

- [ ] **Step 2: Toggle inline a la derecha del título en `<md`**

Localizar el bloque `&__toggle`:

```scss
    &__toggle {
        position: absolute;
        right: $space-3xl;
        bottom: 0;
        display: inline-flex;
        gap: 8px;

        @include media-down(md) {
            position: static;
            margin-top: $space-sm;
            right: auto;
        }
    }
```

Reemplazar el `media-down(md)` interno (que lo baja) por uno que lo mantenga absoluto a la derecha del título:

```scss
    &__toggle {
        position: absolute;
        right: $space-3xl;
        bottom: 0;
        display: inline-flex;
        gap: 8px;

        @include media-down(md) {
            right: $space-sm;
        }
    }
```

- [ ] **Step 3: Compilar**

Run: `npm run build` (si EACCES, mover dist y reintentar)
Expected: build OK.

- [ ] **Step 4: Verificar**

Run: `grep -oE 'eventos-archive__title[^}]*48px|eventos-archive__toggle[^}]*right:18px' dist/css/main.*.css | head`
Expected: aparece el título 48px y/o el toggle con `right` mobile. Revisión visual @393px: "Eventos" 48px, toggle (cuadrícula/lista) a la derecha del título en la misma fila.

- [ ] **Step 5: Commit**

```bash
git add src/scss/templates/_eventos-archive.scss
git commit -m "feat(eventos mobile): título 48px + toggle a la derecha del título

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 6: Card evento GRID mobile (imagen arriba + CTA absoluto abajo-derecha)

**Files:**
- Modify: `src/scss/blocks/_card-evento.scss`

- [ ] **Step 1: Ajustar el bloque `media-down(md)` dentro de `&--grid`**

Localizar el `media-down(md)` actual dentro de `.udp-card-evento &--grid`:

```scss
        @include media-down(md) {
            flex-direction: column;
            gap: $space-sm;

            .udp-card-evento__media {
                width: 100%;
                height: auto;
                aspect-ratio: 16 / 9;
                flex: 0 0 auto;
            }

            .udp-card-evento__body {
                padding-right: 0;
                min-height: 0;
            }

            .udp-card-evento__cta {
                position: static;
                margin-top: $space-sm;
            }
        }
```

Reemplazarlo por (imagen ratio 343:250, CTA se queda absoluto abajo-derecha, body con espacio inferior para el CTA):

```scss
        @include media-down(md) {
            flex-direction: column;
            gap: $space-sm;

            .udp-card-evento__media {
                width: 100%;
                height: auto;
                aspect-ratio: 343 / 250;
                flex: 0 0 auto;
            }

            .udp-card-evento__body {
                min-height: 0;
                padding-right: 0;
                padding-bottom: 56px; // espacio para el CTA absoluto
            }

            // CTA se mantiene absoluto abajo-derecha (no se sobreescribe a static)
        }
```

> Nota: el `.udp-card-evento__cta` base de `&--grid` ya es `position: absolute; bottom: 0; right: 0;`. Al no sobreescribirlo, queda abajo-derecha del body también en mobile.

- [ ] **Step 2: Compilar**

Run: `npm run build` (si EACCES, mover dist y reintentar)
Expected: build OK.

- [ ] **Step 3: Verificar**

Run: `grep -oE 'card-evento--grid[^@]*aspect-ratio:343/250' dist/css/main.*.css | head -1` (o revisar `343 / 250`)
Expected: aparece el ratio. Revisión visual @393px en `/agenda-udp/`: card con imagen arriba (más alta que 16/9) + título/eyebrow/fecha/lugar debajo + CTA circular abajo-derecha.

- [ ] **Step 4: Commit**

```bash
git add src/scss/blocks/_card-evento.scss
git commit -m "feat(eventos mobile): card grid imagen-arriba ratio 343:250 + CTA abajo-derecha

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 7: Card evento LIST mobile (título 20px)

**Files:**
- Modify: `src/scss/blocks/_card-evento.scss`

- [ ] **Step 1: Subir el título de la list a 20px en `<md`**

Localizar el `media-down(md)` dentro de `.udp-card-evento &--list`:

```scss
        @include media-down(md) {
            grid-template-columns: 1fr;
            gap: $space-2xs;
            padding: $space-sm 0;

            .udp-card-evento__date {
                text-align: left;
            }
        }
```

Reemplazarlo por (añade el título 20px; el eyebrow 12px y la fecha 14px ya vienen del base):

```scss
        @include media-down(md) {
            grid-template-columns: 1fr;
            gap: $space-2xs;
            padding: $space-sm 0;

            .udp-card-evento__title {
                font-size: 20px;
                line-height: 1.2;
            }

            .udp-card-evento__date {
                text-align: left;
            }
        }
```

- [ ] **Step 2: Compilar**

Run: `npm run build` (si EACCES, mover dist y reintentar)
Expected: build OK.

- [ ] **Step 3: Verificar**

Run: `curl -s "http://localhost:8888/udp/agenda-udp/?view=list&nc=$(date +%s)" | grep -c 'udp-card-evento--list'`
Expected: ≥1 (cards en modo lista). Revisión visual @393px: filas con eyebrow (mono 12px) / título (~20px) / fecha (14px) apiladas, separador entre filas.

- [ ] **Step 4: Commit**

```bash
git add src/scss/blocks/_card-evento.scss
git commit -m "feat(eventos mobile): card list título 20px

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Cierre

- [ ] Revisión visual @393px: barra global en single, eventos y otras páginas; single sin doble barra y con su carrusel; eventos grid/list según maqueta. @≥992px sin regresiones.
- [ ] Actualizar `MEMORY.md`: barra globalizada (footer) + retirada del single; eventos mobile pixel-perfect; pendientes (pixel-perfect fino de cards si hace falta, otras páginas).
