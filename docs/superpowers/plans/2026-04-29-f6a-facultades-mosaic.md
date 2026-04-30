# F6a — Facultades landing + mosaic primitive — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement this plan task-by-task.

**Goal:** Construir landing `/facultades/` (page existente ID 14, hija de "Pregrado y Formación General") con mosaico 5-col de cards facultad. Card primitive `card-mosaic.php` reutilizable por F6b (Carreras) y F6c (Centros).

**Architecture:** Page template asignable. Helper `udp_query_facultades()` itera términos de la taxonomía `facultad` y devuelve cards normalizadas (image desde ACF `imagen_taxonomia`, link a página dedicada via `get_page_by_title($term_name)` o fallback a term archive). Card primitive shared con placeholder hatching cuando no hay imagen. Theme dark matching Figma.

**Reference:** Figma `4QlgGMlzNR9Ye344bAFuye` nodeId `4383:18935`. ACF tax meta `group_tax_facultad_meta` ya existe (color + imagen_taxonomia).

---

## Inventario

**Crear:**
- `templates/page-facultades.php` — page template
- `template-parts/blocks/parts/card-mosaic.php` — primitive (image + title + link)
- `src/scss/blocks/_card-mosaic.scss` — card SCSS
- `src/scss/templates/_facultades-archive.scss` — page SCSS

**Modificar:**
- `inc/udp-cards.php` — `udp_query_facultades` + `udp_card_data_from_facultad_term`.
- `src/scss/main.scss` — 2 imports.

---

## Task 1: Helpers

**File:** `inc/udp-cards.php`

- [x] **Step 1: Append AT END del archivo**

```php

/**
 * Convierte un término de taxonomía 'facultad' a Card mosaic shape.
 * Image desde ACF imagen_taxonomia (puede ser null → placeholder).
 * Link prefiere página dedicada (match por exact title); fallback a term archive.
 *
 * @return array { titulo, imagen, color, href, has_image }
 */
function udp_card_data_from_facultad_term( WP_Term $term ): array {
    $imagen = function_exists( 'get_field' ) ? get_field( 'imagen_taxonomia', $term ) : null;
    $color  = function_exists( 'get_field' ) ? (string) get_field( 'color', $term ) : '';

    $imagen_url = '';
    $imagen_alt = '';
    if ( is_array( $imagen ) ) {
        $imagen_url = $imagen['sizes']['medium_large'] ?? ( $imagen['url'] ?? '' );
        $imagen_alt = $imagen['alt'] ?? '';
    } elseif ( is_numeric( $imagen ) && (int) $imagen > 0 ) {
        $imagen_url = wp_get_attachment_image_url( (int) $imagen, 'medium_large' ) ?: '';
        $imagen_alt = (string) get_post_meta( (int) $imagen, '_wp_attachment_image_alt', true );
    }

    // Link: página dedicada exacta por título → fallback a term archive
    $page = get_page_by_title( $term->name, OBJECT, 'page' );
    if ( $page && $page->post_status === 'publish' ) {
        $href = get_permalink( $page );
    } else {
        $term_link = get_term_link( $term );
        $href = is_wp_error( $term_link ) ? '#' : $term_link;
    }

    return array(
        'term_id'   => (int) $term->term_id,
        'titulo'    => $term->name,
        'imagen'    => array( 'url' => $imagen_url, 'alt' => $imagen_alt ),
        'color'     => $color,
        'href'      => $href,
        'has_image' => $imagen_url !== '',
    );
}

/**
 * Devuelve cards de todos los términos de la taxonomía 'facultad'.
 * Order alfabético, hide_empty FALSE para incluir todos.
 *
 * @return array<int,array>
 */
function udp_query_facultades(): array {
    $terms = get_terms( array(
        'taxonomy'   => 'facultad',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ) );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return array();
    }

    $cards = array();
    foreach ( $terms as $term ) {
        $cards[] = udp_card_data_from_facultad_term( $term );
    }
    return $cards;
}
```

- [x] **Step 2: Validar PHP + smoke test**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/inc/udp-cards.php
```

`/tmp/test-facultades.php`:

```php
<?php
$cards = udp_query_facultades();
WP_CLI::log( 'count: ' . count( $cards ) );
foreach ( $cards as $c ) {
    WP_CLI::log( sprintf( '  %s — img:%s — href:%s', $c['titulo'], $c['has_image'] ? 'yes' : 'NO', $c['href'] ) );
}
WP_CLI::success( 'Facultades OK' );
```

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval-file /tmp/test-facultades.php --path=/Applications/MAMP/htdocs/udp/cms 2>&1 | tail -20
```

Expected: ~14 cards (10 facultades + Carreras Vespertinas + Centro para las Humanidades + Estudios Generales + Instituto de Filosofía). 2 con `img:yes`, resto `img:NO`. Hrefs apuntando a la página dedicada o term archive.

---

## Task 2: Card primitive `card-mosaic.php`

**File:** `template-parts/blocks/parts/card-mosaic.php`

- [x] **Step 1: Crear**

```php
<?php
/**
 * Card primitive — Mosaic (image + title)
 *
 * Card simple para mosaicos: facultades, centros, carreras (con eyebrow opcional).
 * Imagen opcional — placeholder hatching si no hay.
 *
 * @package Starter_Theme
 *
 * @var array $args ['card' => array, 'theme' => 'dark'|'light']
 */
$card  = isset( $args['card'] ) && is_array( $args['card'] ) ? $args['card'] : array();
$theme = isset( $args['theme'] ) && in_array( $args['theme'], array( 'dark', 'light' ), true ) ? $args['theme'] : 'dark';

$titulo  = $card['titulo'] ?? '';
$href    = $card['href'] ?? '';
$imagen  = is_array( $card['imagen'] ?? null ) ? $card['imagen'] : array();
$has_img = ! empty( $card['has_image'] );
$eyebrow = $card['eyebrow'] ?? '';
$target  = $card['target'] ?? '';
$rel     = $target === '_blank' ? 'noopener noreferrer' : '';

if ( ! $titulo || ! $href ) {
    return;
}

$class = 'udp-card-mosaic udp-card-mosaic--' . $theme;
$media_class = 'udp-card-mosaic__media' . ( $has_img ? '' : ' udp-card-mosaic__media--placeholder' );
?>
<a
    href="<?php echo esc_url( $href ); ?>"
    class="<?php echo esc_attr( $class ); ?>"
    <?php if ( $target ) : ?>target="<?php echo esc_attr( $target ); ?>"<?php endif; ?>
    <?php if ( $rel ) : ?>rel="<?php echo esc_attr( $rel ); ?>"<?php endif; ?>
>
    <figure class="<?php echo esc_attr( $media_class ); ?>">
        <?php if ( $has_img ) : ?>
            <img
                src="<?php echo esc_url( $imagen['url'] ?? '' ); ?>"
                alt="<?php echo esc_attr( $imagen['alt'] ?? '' ); ?>"
                loading="lazy"
                decoding="async"
            />
        <?php endif; ?>
    </figure>
    <div class="udp-card-mosaic__body">
        <?php if ( $eyebrow ) : ?>
            <span class="udp-card-mosaic__eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
        <?php endif; ?>
        <h3 class="udp-card-mosaic__title"><?php echo esc_html( $titulo ); ?></h3>
    </div>
</a>
```

- [x] **Step 2: Validar PHP**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/blocks/parts/card-mosaic.php
```

---

## Task 3: Page template `page-facultades.php`

**File:** `templates/page-facultades.php`

- [x] **Step 1: Crear page template**

```php
<?php
/**
 * Template Name: Facultades (Mosaic)
 *
 * Page template asignable a "Facultades" (ID 14). Mosaico 5-col de
 * cards facultad. Tema dark.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$cards = function_exists( 'udp_query_facultades' ) ? udp_query_facultades() : array();

get_header();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-facultades-archive' ); ?>>

    <header class="udp-facultades-archive__header">
        <?php
        get_template_part(
            'template-parts/sections/breadcrumb',
            null,
            array( 'page_id' => get_the_ID() )
        );
        ?>
        <h1 class="udp-facultades-archive__title"><?php the_title(); ?></h1>

        <?php $intro = get_the_content(); ?>
        <?php if ( $intro ) : ?>
            <div class="udp-facultades-archive__intro">
                <?php echo apply_filters( 'the_content', $intro ); ?>
            </div>
        <?php endif; ?>
    </header>

    <hr class="udp-facultades-archive__separator" aria-hidden="true" />

    <?php if ( ! empty( $cards ) ) : ?>
        <ul class="udp-facultades-archive__list">
            <?php foreach ( $cards as $card ) : ?>
                <li class="udp-facultades-archive__item">
                    <?php
                    get_template_part(
                        'template-parts/blocks/parts/card-mosaic',
                        null,
                        array( 'card' => $card, 'theme' => 'dark' )
                    );
                    ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <p class="udp-facultades-archive__empty">
            <?php esc_html_e( 'No hay facultades para mostrar.', 'starter-theme' ); ?>
        </p>
    <?php endif; ?>

</article>

<?php
get_footer();
```

- [x] **Step 2: Asignar a página 14**

```bash
export MYSQL_PWD=root
SOCK=/Applications/MAMP/tmp/mysql/mysql.sock
MYSQL=/Applications/MAMP/Library/bin/mysql80/bin/mysql

EXISTING=$($MYSQL --socket=$SOCK -uroot udp -sN -e "SELECT meta_value FROM wp_fnku4ypostmeta WHERE post_id=14 AND meta_key='_wp_page_template' LIMIT 1;")
if [ -n "$EXISTING" ]; then
    $MYSQL --socket=$SOCK -uroot udp -e "UPDATE wp_fnku4ypostmeta SET meta_value='templates/page-facultades.php' WHERE post_id=14 AND meta_key='_wp_page_template';"
else
    $MYSQL --socket=$SOCK -uroot udp -e "INSERT INTO wp_fnku4ypostmeta (post_id, meta_key, meta_value) VALUES (14, '_wp_page_template', 'templates/page-facultades.php');"
fi
$MYSQL --socket=$SOCK -uroot udp -sN -e "SELECT meta_value FROM wp_fnku4ypostmeta WHERE post_id=14 AND meta_key='_wp_page_template';"
```

- [x] **Step 3: Validar PHP**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/templates/page-facultades.php
```

---

## Task 4: SCSS card-mosaic + facultades-archive

**Files:**
- Create: `src/scss/blocks/_card-mosaic.scss`
- Create: `src/scss/templates/_facultades-archive.scss`
- Modify: `src/scss/main.scss`

- [x] **Step 1: `_card-mosaic.scss`**

```scss
// ==========================================================================
// CARD MOSAIC — primitive simple (image + title) usado en mosaicos:
// Facultades (F6a), Carreras (F6b con eyebrow), Centros (F6c).
// ==========================================================================

.udp-card-mosaic {
    display: flex;
    flex-direction: column;
    gap: $space-2xs;
    color: inherit;
    text-decoration: none;
    transition: color $transition-base;

    &__media {
        margin: 0;
        overflow: hidden;
        background: $dark-2;
        aspect-ratio: 4 / 5;

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.4s ease;
        }

        &--placeholder {
            background:
                repeating-linear-gradient(
                    45deg,
                    $dark-2,
                    $dark-2 8px,
                    rgba($white, 0.04) 8px,
                    rgba($white, 0.04) 16px
                );
        }
    }

    @media (prefers-reduced-motion: no-preference) {
        &:hover .udp-card-mosaic__media img,
        &:focus-visible .udp-card-mosaic__media img {
            transform: scale(1.04);
        }
    }

    &__body {
        display: flex;
        flex-direction: column;
        gap: $space-2xs;
        padding-top: $space-xs;
    }

    &__eyebrow {
        font-family: $font-family-mono;
        font-size: 11px;
        font-weight: 400;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: $white-70;
    }

    &__title {
        margin: 0;
        font-family: $font-family-body;
        font-weight: 500;
        font-size: 16px;
        line-height: 1.3;
        color: $white;
    }

    // Light theme override (para Carreras/Centros embedded en light landings)
    &--light {
        .udp-card-mosaic__title { color: $dark-1; }
        .udp-card-mosaic__eyebrow { color: $dark-2; }
        .udp-card-mosaic__media--placeholder {
            background:
                repeating-linear-gradient(
                    45deg,
                    rgba($dark-1, 0.05),
                    rgba($dark-1, 0.05) 8px,
                    rgba($dark-1, 0.1) 8px,
                    rgba($dark-1, 0.1) 16px
                );
        }
    }
}
```

- [x] **Step 2: `_facultades-archive.scss`**

```scss
// ==========================================================================
// FACULTADES ARCHIVE — page template `page-facultades.php`
// Theme dark. Header + mosaico 5-col de cards-mosaic.
// Reusable por Carreras (F6b) y Centros (F6c) con override mínimo.
// ==========================================================================

.udp-facultades-archive {
    background-color: $dark-1;
    color: $white;
    padding-bottom: $space-3xl;

    &__header {
        max-width: 1440px;
        margin-inline: auto;
        padding: $space-2xl $space-3xl 0;

        @include media-down(md) {
            padding: $space-xl $space-sm 0;
        }

        .udp-breadcrumb__item,
        .udp-breadcrumb__link,
        .udp-breadcrumb__current { color: $white; }
        .udp-breadcrumb__sep { color: $white-70; }
    }

    &__title {
        margin: $space-md 0 0;
        font-family: $font-family-display;
        font-weight: 500;
        font-size: 64px;
        line-height: 1.05;
        color: $white;

        @include media-down(md) { font-size: 40px; }
    }

    &__intro {
        margin-top: $space-md;
        font-family: $font-family-body;
        font-size: 14px;
        line-height: 20px;
        color: $white-70;
        max-width: 720px;

        p { margin: 0 0 $space-2xs; }
        p:last-child { margin-bottom: 0; }
    }

    &__separator {
        max-width: 1360px;
        margin: $space-2xl auto 0;
        border: 0;
        height: 1px;
        background-color: rgba($white, 0.15);
    }

    &__list {
        list-style: none;
        margin: $space-2xl auto 0;
        padding: 0 $space-3xl;
        max-width: 1440px;
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: $space-2xl 30px;

        @include media-down(xl) {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
        @include media-down(md) {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            padding: 0 $space-sm;
            gap: $space-md;
        }
    }

    &__item { display: block; }

    &__empty {
        max-width: 1440px;
        margin: $space-2xl auto 0;
        padding: 0 $space-3xl;
        font-family: $font-family-body;
        font-size: 16px;
        color: $white-70;
    }
}
```

- [x] **Step 3: Imports en main.scss + build**

Edit `src/scss/main.scss`. Añadir después del último `@import "blocks/...";`:

```scss
@import "blocks/card-mosaic";
```

Y después del último `@import "templates/...";`:

```scss
@import "templates/facultades-archive";
```

Build:

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -3
```

---

## Task 5: E2E + MEMORY + commit

- [x] **Step 1: Verify**

```bash
TS=$(date +%s)
echo "=== HTTP /facultades/ ==="
curl -sI "http://localhost:8888/udp/facultades/?nocache=$TS" 2>&1 | head -3
echo ""
echo "=== Markup classes ==="
curl -s "http://localhost:8888/udp/facultades/?nocache=$TS" | grep -oE "udp-(facultades-archive|card-mosaic)[a-z_-]*" | sort -u
echo ""
echo "=== Cards count (esperado ~14) ==="
curl -s "http://localhost:8888/udp/facultades/?nocache=$TS" | grep -cE 'class="udp-facultades-archive__item"'
echo ""
echo "=== Placeholder count (sin imagen) ==="
curl -s "http://localhost:8888/udp/facultades/?nocache=$TS" | grep -cE 'udp-card-mosaic__media--placeholder'
```

Expected: HTTP 200, classes presentes, ~14 cards (10-14 según data), ~12 placeholders.

- [x] **Step 2: Cleanup**

```bash
rm -f /tmp/test-facultades.php
```

- [x] **Step 3: MEMORY + commit**

Append a MEMORY.md:

```markdown

### 2026-04-29 — F6a Facultades landing + mosaic primitive

**Hechos**:
- `templates/page-facultades.php` asignado a página "Facultades" (ID 14, hija de "Pregrado y Formación General"). Theme dark, mosaico 5-col responsive (5 → 3 en lg → 2 en mobile).
- Helpers nuevos: `udp_query_facultades` (itera términos de tax facultad) + `udp_card_data_from_facultad_term` (mapea término a card mosaic shape, image desde ACF imagen_taxonomia, link via `get_page_by_title($term_name)` con fallback a term archive).
- Card primitive `card-mosaic.php` reutilizable por Carreras (F6b con eyebrow) y Centros (F6c). Soporta theme dark/light + placeholder hatching cuando no hay imagen.
- 2 SCSS nuevos: `_card-mosaic.scss` (primitive con dark/light variants) y `_facultades-archive.scss` (page con grid 5-col responsive).

**Decisiones clave**:
- 14 términos en taxonomía facultad pero solo 2 con `imagen_taxonomia` poblada en ACF — el placeholder hatching cubre los 12 restantes hasta que se carguen las imágenes.
- Match término → página dedicada por exact title (`get_page_by_title`). Funciona para las 9-10 facultades que tienen su landing existente. Resto cae a term archive `/facultad/{slug}/`.
- Mosaic primitive separado del archive container para reuso en F6b/F6c.

**Pendientes**:
- F6b: Archive Carreras (reusa mosaic + filtros legacy facultad+search) + single-carrera-udp.
- F6c: Archive Centros + single simple.
- F6 extras: `block_facultades_mosaic` flex content (mismo mosaic insertable como widget) — diferido.
- Subir las 12 imágenes de facultad faltantes en admin para que el placeholder se reemplace.
```

Commit:

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add \
  inc/udp-cards.php \
  templates/page-facultades.php \
  template-parts/blocks/parts/card-mosaic.php \
  src/scss/blocks/_card-mosaic.scss \
  src/scss/templates/_facultades-archive.scss \
  src/scss/main.scss \
  MEMORY.md
git commit -m "$(cat <<'EOF'
feat(facultades): F6a landing + card-mosaic primitive

- templates/page-facultades.php asignado a página Facultades (ID 14).
  Theme dark, mosaico 5-col responsive de cards facultad.
- inc/udp-cards.php: udp_query_facultades + udp_card_data_from_facultad_term.
  Match término → página dedicada via get_page_by_title con fallback a
  term archive.
- card-mosaic.php primitive reutilizable (image + title + opcional eyebrow).
  Soporta theme dark/light y placeholder hatching cuando no hay imagen.
- 2 SCSS: _card-mosaic + _facultades-archive con grid 5/3/2 cols
  responsive.
EOF
)"
```

---

## Verification

1. `/facultades/` HTTP 200, dark theme, header con título "Facultades" + breadcrumb + intro (si hay), 14 cards en grid 5-col.
2. Cards con imagen muestran imagen, sin imagen muestran placeholder hatching.
3. Click en card de "Facultad de Derecho" → página dedicada (ID 269). Click en "Estudios Generales" (sin página dedicada) → term archive.
4. Mobile: grid pasa a 2 cols.
