# F4 Extras — Image gallery en single-post — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement this plan task-by-task.

**Goal:** Renderizar el campo ACF existente `galeria_de_imagenes` (gallery) del CPT post en `single-post.php` con un carousel Swiper. Aparece entre el body content y el related section.

**Architecture:** Partial nuevo `template-parts/single/post-gallery.php` que recibe array de attachments y renderiza un Swiper. JS module `single-post-gallery.js` lazy-inicializa Swiper si hay galería. SCSS nuevo en `_noticias-single.scss` (extension del existente).

**Reference:** Figma `3706:21278` muestra 3 imágenes con arrows nav después del body. ACF field `galeria_de_imagenes` ya existe en `acf-json/group_cpt_post_meta.json`.

---

## Inventario

**Crear:**
- `template-parts/single/post-gallery.php`
- `src/js/modules/single-post-gallery.js`

**Modificar:**
- `single-post.php` — incluir `get_template_part('template-parts/single/post-gallery', ...)` después del body, antes del related.
- `src/js/main.js` — importar e inicializar el módulo.
- `src/scss/templates/_noticias-single.scss` — añadir estilos `.udp-single-post__gallery*`.

---

## Task 1: Partial + integración en single-post.php

- [x] **Step 1: Crear `template-parts/single/post-gallery.php`**

```php
<?php
/**
 * Single Post > Image gallery (Swiper)
 *
 * Renderiza el campo ACF `galeria_de_imagenes` como carousel Swiper.
 * El JS lazy-inicializa Swiper solo si `.udp-single-post__gallery` existe.
 *
 * @package Starter_Theme
 *
 * @var array $args ['post_id' => int]
 */
$post_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();
if ( ! $post_id ) {
    return;
}

$gallery = function_exists( 'get_field' ) ? get_field( 'galeria_de_imagenes', $post_id ) : null;
if ( ! is_array( $gallery ) || empty( $gallery ) ) {
    return;
}
?>
<section class="udp-single-post__gallery" data-udp-post-gallery>
    <div class="udp-single-post__gallery-viewport swiper">
        <ul class="udp-single-post__gallery-list swiper-wrapper">
            <?php foreach ( $gallery as $image ) :
                $url = $image['sizes']['large'] ?? ( $image['url'] ?? '' );
                $alt = $image['alt'] ?? '';
                if ( empty( $url ) ) {
                    continue;
                }
            ?>
                <li class="udp-single-post__gallery-item swiper-slide">
                    <img src="<?php echo esc_url( $url ); ?>" alt="<?php echo esc_attr( $alt ); ?>" loading="lazy" decoding="async" />
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="udp-single-post__gallery-nav">
        <button type="button" class="udp-single-post__gallery-prev" aria-label="<?php esc_attr_e( 'Anterior', 'starter-theme' ); ?>">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M9 3L5 7l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <button type="button" class="udp-single-post__gallery-next" aria-label="<?php esc_attr_e( 'Siguiente', 'starter-theme' ); ?>">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M5 3l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
    </div>
</section>
```

- [x] **Step 2: Modificar `single-post.php` para incluir la galería**

Edit `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/single-post.php`. Localizar el bloque:

```php
<div class="udp-single-post__body">
    <div class="udp-single-post__content">
        <?php the_content(); ?>
    </div>
</div>

<?php
get_template_part( 'template-parts/single/post-related', null, array( 'post_id' => get_the_ID() ) );
?>
```

Y AÑADIR la galería ENTRE el body y el related:

```php
<div class="udp-single-post__body">
    <div class="udp-single-post__content">
        <?php the_content(); ?>
    </div>
</div>

<?php get_template_part( 'template-parts/single/post-gallery', null, array( 'post_id' => get_the_ID() ) ); ?>

<?php
get_template_part( 'template-parts/single/post-related', null, array( 'post_id' => get_the_ID() ) );
?>
```

- [x] **Step 3: Validar PHP**

```bash
PHP=/Applications/MAMP/bin/php/php8.4.1/bin/php
$PHP -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/single/post-gallery.php
$PHP -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/single-post.php
```

Expected: 2× `No syntax errors detected`.

---

## Task 2: JS module Swiper init

- [x] **Step 1: Crear `src/js/modules/single-post-gallery.js`**

```javascript
/**
 * Single Post — Gallery Swiper init
 *
 * Inicializa Swiper solo si `[data-udp-post-gallery]` existe en la página.
 * Lazy import — no añade peso al bundle si no hay galería.
 */
import { qsa } from '@utils/dom';

export async function initSinglePostGallery() {
    const containers = qsa('[data-udp-post-gallery]');
    if (!containers.length) {
        return;
    }

    const { default: Swiper } = await import('swiper');
    const { Navigation, Keyboard } = await import('swiper/modules');
    await import('swiper/css');

    containers.forEach((el) => {
        const swiperEl = el.querySelector('.swiper');
        if (!swiperEl) return;

        new Swiper(swiperEl, {
            modules: [Navigation, Keyboard],
            slidesPerView: 'auto',
            spaceBetween: 16,
            keyboard: { enabled: true },
            grabCursor: true,
            navigation: {
                nextEl: el.querySelector('.udp-single-post__gallery-next'),
                prevEl: el.querySelector('.udp-single-post__gallery-prev'),
            },
            breakpoints: {
                768: { slidesPerView: 3, spaceBetween: 30 },
                0:   { slidesPerView: 1.1, spaceBetween: 12 },
            },
        });
    });
}
```

- [x] **Step 2: Wire en `src/js/main.js`**

Edit `src/js/main.js`. Localizar la línea `import { initSectionLandingSwiper } from '@modules/section-landing-swiper';` y AÑADIR DESPUÉS:

```javascript
import { initSinglePostGallery } from '@modules/single-post-gallery';
```

Localizar el bloque `domReady(() => {` y AÑADIR la línea `initSinglePostGallery();` (antes del `console.log` final).

---

## Task 3: SCSS gallery

- [x] **Step 1: Añadir SCSS al final de `_noticias-single.scss`**

Append AL FINAL de `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/templates/_noticias-single.scss`:

```scss

// --------------------------------------------------------------------------
// SINGLE POST — Image gallery (Swiper)
// --------------------------------------------------------------------------
.udp-single-post__gallery {
    max-width: 1440px;
    margin: $space-3xl auto 0;
    padding: 0 $space-3xl;
    position: relative;

    @include media-down(md) {
        padding: 0 $space-sm;
    }

    &-viewport {
        overflow: hidden;
    }

    &-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
    }

    &-item {
        flex-shrink: 0;

        img {
            width: 100%;
            height: auto;
            aspect-ratio: 4 / 3;
            object-fit: cover;
            display: block;
        }
    }

    &-nav {
        display: flex;
        gap: 8px;
        margin-top: $space-md;
        justify-content: flex-end;
    }

    &-prev,
    &-next {
        width: 40px;
        height: 40px;
        border-radius: 9999px;
        border: 1px solid rgba($dark-1, 0.3);
        background: transparent;
        color: $dark-1;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition:
            background-color $transition-base,
            color $transition-base,
            border-color $transition-base;

        &:hover,
        &:focus-visible {
            background-color: $dark-1;
            color: $white;
            outline: none;
        }

        &.swiper-button-disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }
    }
}
```

- [x] **Step 2: Build**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -5
```

Expected: build OK, CSS sube ~1 kB. Si Swiper se reusa en otro chunk, no añade peso JS.

---

## Task 4: E2E + commit

- [x] **Step 1: Encontrar un post con gallery populada**

```bash
export MYSQL_PWD=root
GALLERY_POST=$(/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot udp -sN -e "
SELECT p.ID, p.post_name FROM wp_fnku4yposts p
JOIN wp_fnku4ypostmeta pm ON p.ID=pm.post_id
WHERE p.post_type='post' AND p.post_status='publish'
  AND pm.meta_key='galeria_de_imagenes' AND pm.meta_value != '' AND pm.meta_value != 'a:0:{}'
LIMIT 1;")
echo "$GALLERY_POST"
```

Si vacío: ningún post tiene gallery — el partial early-returns silencio. Documentar y saltar al commit.

- [x] **Step 2: Curl + verificar markup**

```bash
SLUG=$(echo "$GALLERY_POST" | awk '{print $2}')
TS=$(date +%s)
echo "=== Markup gallery ==="
curl -s "http://localhost:8888/udp/$SLUG/?nocache=$TS" | grep -oE 'udp-single-post__gallery[a-z_-]*' | sort -u
echo ""
echo "=== Image count ==="
curl -s "http://localhost:8888/udp/$SLUG/?nocache=$TS" | grep -cE 'udp-single-post__gallery-item swiper-slide'
```

Expected: Aparecen `udp-single-post__gallery`, `udp-single-post__gallery-viewport`, `udp-single-post__gallery-list`, `udp-single-post__gallery-item`, `udp-single-post__gallery-nav`. Image count >= 1.

- [x] **Step 3: Update MEMORY.md**

Append:

```markdown

### 2026-04-28 — F4 extras: image gallery en single-post

**Hechos**:
- Galería del campo ACF existente `galeria_de_imagenes` (group `cpt_post_meta`, ya creado en F1) se renderiza en `single-post.php` entre el body content y el related section.
- Partial nuevo `template-parts/single/post-gallery.php` itera el array de attachments y emite `<ul class="...gallery-list swiper-wrapper">` con `<li class="...swiper-slide">` por imagen.
- Nav buttons (prev/next) custom UDP — círculos 40×40 con borders dark, hover bg dark.
- JS module nuevo `single-post-gallery.js` lazy-importa Swiper + Navigation + Keyboard solo si `[data-udp-post-gallery]` existe en la página. Patrón idéntico a `section-landing-swiper.js` (F3).
- Posts sin galería: el partial early-returns silenciosamente (no markup).

**Decisiones clave**:
- Reusa la dependencia Swiper de F3 (ya en npm). No añade peso al bundle principal — chunk separado lazy-loaded.
- 3 cards/page desktop, 1.1 mobile (peek de la siguiente). Same UX que carruseles típicos.

**Pendientes**:
- Color de los nav buttons en el dark theme del archive — N/A, gallery solo en single (light theme).
- El campo ACF `galeria_de_imagenes` existe pero la mayoría de posts no la tienen populada — documentar editorial.
```

- [x] **Step 4: Commit**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add \
  template-parts/single/post-gallery.php \
  single-post.php \
  src/js/modules/single-post-gallery.js \
  src/js/main.js \
  src/scss/templates/_noticias-single.scss \
  MEMORY.md
git commit -m "feat(single-post): F4 extras — image gallery con Swiper

- Partial template-parts/single/post-gallery.php renderiza el campo ACF
  galeria_de_imagenes (ya existente en cpt_post_meta) entre el body y el
  related section.
- JS module single-post-gallery.js lazy-importa Swiper solo si la galería
  existe (chunk separado, no añade peso al main bundle).
- Nav buttons (prev/next) custom UDP, 40x40 círculos.
- SCSS append en _noticias-single.scss."
```

---

## Verification end-to-end

1. Si hay un post con `galeria_de_imagenes` populada: visitar el single, ver la galería entre body y related, click prev/next funciona, mobile swipe funciona.
2. Posts sin galería: no aparece la sección (silent early return).
