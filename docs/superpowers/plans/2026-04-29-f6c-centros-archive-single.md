# F6c — Centros archive + single — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement this plan task-by-task.

**Goal:** Archive `/centros-interdisciplinarios/` (page existente ID 16) reusando mosaic de F6a (estilo Facultades, sin filtros). Cada card linka a `link_externo` ACF (target=_blank) si existe, sino al single. Single centro simple: light theme + featured + post_content + button externo si existe. Reusa post-share.

**Architecture:** Mismo patrón que F6a/F6b. Helper `udp_query_centros` + `udp_card_data_from_centro`. Reusa `card-mosaic.php`. Single `single-centro-udp.php` minimal (sin sidebar 2-col — más simple que carrera). 1 SCSS archive + 1 SCSS single.

**Reference:** Sin Figma — derivado del lenguaje F6a (Facultades layout) + ACF centro (`link_externo`).

---

## Inventario

**Crear:**
- `templates/page-centros.php`
- `single-centro-udp.php`
- `src/scss/templates/_centros-archive.scss`
- `src/scss/templates/_centros-single.scss`

**Modificar:**
- `inc/udp-cards.php` — `udp_query_centros` + `udp_card_data_from_centro`.
- `src/scss/main.scss` — 2 imports.

---

## Task 1: Helpers

**File:** `inc/udp-cards.php`

- [x] **Step 1: Append AT END**

```php

/**
 * Convierte WP_Post (centro-udp) a Card mosaic shape.
 * href = link_externo (target=_blank) si existe, sino permalink.
 */
function udp_card_data_from_centro( WP_Post $post ): array {
    $thumb_id = (int) get_post_thumbnail_id( $post->ID );
    $imagen_url = '';
    $imagen_alt = '';
    if ( $thumb_id > 0 ) {
        $imagen_url = wp_get_attachment_image_url( $thumb_id, 'medium_large' ) ?: '';
        $imagen_alt = (string) get_post_meta( $thumb_id, '_wp_attachment_image_alt', true );
    }

    $link_externo = (string) get_post_meta( $post->ID, 'link_externo', true );
    $href   = $link_externo ?: get_permalink( $post );
    $target = $link_externo ? '_blank' : '';

    return array(
        'post_id'   => (int) $post->ID,
        'titulo'    => get_the_title( $post ),
        'imagen'    => array( 'url' => $imagen_url, 'alt' => $imagen_alt ),
        'has_image' => $imagen_url !== '',
        'eyebrow'   => '',
        'href'      => $href,
        'target'    => $target,
    );
}

/**
 * Wrapper sobre WP_Query para archive Centros. Sin filtros (como Facultades).
 */
function udp_query_centros(): array {
    $q = new WP_Query( array(
        'post_type'      => 'centro-udp',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'no_found_rows'  => true,
    ) );

    $cards = array();
    foreach ( $q->posts as $post ) {
        $cards[] = udp_card_data_from_centro( $post );
    }
    return $cards;
}
```

- [x] **Step 2: Validar PHP + smoke test**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/inc/udp-cards.php
```

`/tmp/test-centros.php`:

```php
<?php
$r = udp_query_centros();
WP_CLI::log( 'count: ' . count( $r ) );
if ( $r ) {
    WP_CLI::log( 'first: ' . $r[0]['titulo'] . ' — has_image: ' . ($r[0]['has_image'] ? 'yes':'NO') . ' — target: ' . $r[0]['target'] );
}
WP_CLI::success( 'OK' );
```

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval-file /tmp/test-centros.php --path=/Applications/MAMP/htdocs/udp/cms 2>&1 | tail -5
```

Expected: 55 centros, primero con title.

---

## Task 2: Page template + assign

**File:** `templates/page-centros.php`

- [x] **Step 1: Crear**

```php
<?php
/**
 * Template Name: Centros (Archive)
 *
 * Page template asignable a "Centros Interdisciplinarios" (ID 16).
 * Theme dark, mosaico 5-col reusando card-mosaic. Sin filtros.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$cards = function_exists( 'udp_query_centros' ) ? udp_query_centros() : array();

get_header();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-centros-archive' ); ?>>

    <header class="udp-centros-archive__header">
        <?php
        get_template_part( 'template-parts/sections/breadcrumb', null, array( 'page_id' => get_the_ID() ) );
        ?>
        <h1 class="udp-centros-archive__title"><?php the_title(); ?></h1>

        <?php $intro = get_the_content(); ?>
        <?php if ( $intro ) : ?>
            <div class="udp-centros-archive__intro">
                <?php echo apply_filters( 'the_content', $intro ); ?>
            </div>
        <?php endif; ?>
    </header>

    <hr class="udp-centros-archive__separator" aria-hidden="true" />

    <?php if ( ! empty( $cards ) ) : ?>
        <ul class="udp-centros-archive__list">
            <?php foreach ( $cards as $card ) : ?>
                <li class="udp-centros-archive__item">
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
        <p class="udp-centros-archive__empty">
            <?php esc_html_e( 'No hay centros para mostrar.', 'starter-theme' ); ?>
        </p>
    <?php endif; ?>

</article>

<?php
get_footer();
```

- [x] **Step 2: Asignar a página 16**

```bash
export MYSQL_PWD=root
SOCK=/Applications/MAMP/tmp/mysql/mysql.sock
MYSQL=/Applications/MAMP/Library/bin/mysql80/bin/mysql

EXISTING=$($MYSQL --socket=$SOCK -uroot udp -sN -e "SELECT meta_value FROM wp_fnku4ypostmeta WHERE post_id=16 AND meta_key='_wp_page_template' LIMIT 1;")
if [ -n "$EXISTING" ]; then
    $MYSQL --socket=$SOCK -uroot udp -e "UPDATE wp_fnku4ypostmeta SET meta_value='templates/page-centros.php' WHERE post_id=16 AND meta_key='_wp_page_template';"
else
    $MYSQL --socket=$SOCK -uroot udp -e "INSERT INTO wp_fnku4ypostmeta (post_id, meta_key, meta_value) VALUES (16, '_wp_page_template', 'templates/page-centros.php');"
fi
$MYSQL --socket=$SOCK -uroot udp -sN -e "SELECT meta_value FROM wp_fnku4ypostmeta WHERE post_id=16 AND meta_key='_wp_page_template';"
```

- [x] **Step 3: Validar PHP**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/templates/page-centros.php
```

---

## Task 3: Single centro

**File:** `single-centro-udp.php`

- [x] **Step 1: Crear**

```php
<?php
/**
 * Single Centro (CPT centro-udp)
 *
 * Light theme, layout simple: featured + content + button externo si existe.
 * Reusa post-share (igual que single-post).
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
    the_post();
    $link_externo = (string) get_post_meta( get_the_ID(), 'link_externo', true );
    $archive_url  = get_permalink( 16 );
    if ( ! $archive_url ) {
        $archive_url = home_url( '/centros-interdisciplinarios/' );
    }
    ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-single-centro' ); ?>>

        <header class="udp-single-centro__header">
            <a class="udp-single-centro__back" href="<?php echo esc_url( $archive_url ); ?>">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                    <path d="M9 3L5 7l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <?php esc_html_e( 'Volver a Centros', 'starter-theme' ); ?>
            </a>
            <h1 class="udp-single-centro__title"><?php the_title(); ?></h1>
        </header>

        <hr class="udp-single-centro__separator" aria-hidden="true" />

        <div class="udp-single-centro__body">
            <?php if ( has_post_thumbnail() ) : ?>
                <figure class="udp-single-centro__featured">
                    <?php the_post_thumbnail( 'large' ); ?>
                </figure>
            <?php endif; ?>

            <div class="udp-single-centro__content">
                <?php the_content(); ?>
            </div>

            <?php if ( $link_externo ) : ?>
                <div class="udp-single-centro__actions">
                    <a class="udp-single-centro__btn" href="<?php echo esc_url( $link_externo ); ?>" target="_blank" rel="noopener noreferrer">
                        <?php esc_html_e( 'Visitar sitio del centro', 'starter-theme' ); ?>
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                            <path d="M3 3h6v6M9 3 3 9" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php get_template_part( 'template-parts/single/post-share', null, array( 'post_id' => get_the_ID() ) ); ?>

    </article>

    <?php
endwhile;

get_footer();
```

- [x] **Step 2: Validar PHP**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/single-centro-udp.php
```

---

## Task 4: SCSS

**Files:**
- Create: `src/scss/templates/_centros-archive.scss`
- Create: `src/scss/templates/_centros-single.scss`
- Modify: `src/scss/main.scss`

- [x] **Step 1: `_centros-archive.scss`**

```scss
// ==========================================================================
// CENTROS ARCHIVE — page template `page-centros.php`
// Theme dark. Mosaico 5-col reusando card-mosaic. Sin filtros.
// ==========================================================================

.udp-centros-archive {
    background-color: $dark-1;
    color: $white;
    padding-bottom: $space-3xl;

    &__header {
        max-width: 1440px;
        margin-inline: auto;
        padding: $space-2xl $space-3xl 0;
        @include media-down(md) { padding: $space-xl $space-sm 0; }

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

        @include media-down(xl) { grid-template-columns: repeat(3, minmax(0, 1fr)); }
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

- [x] **Step 2: `_centros-single.scss`**

```scss
// ==========================================================================
// SINGLE CENTRO — `single-centro-udp.php`
// Light theme simple: header + featured + content + button externo opcional.
// ==========================================================================

.udp-single-centro {
    background-color: $white;
    color: $dark-1;
    padding-bottom: $space-3xl;
    position: relative;

    &__header {
        max-width: 1080px;
        margin-inline: auto;
        padding: $space-2xl $space-3xl 0;
        @include media-down(md) { padding: $space-xl $space-sm 0; }
    }

    &__back {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-family: $font-family-body;
        font-size: 14px;
        font-weight: 500;
        color: $dark-1;
        text-decoration: none;
        margin-bottom: $space-lg;
        &:hover, &:focus-visible { text-decoration: underline; }
    }

    &__title {
        margin: 0;
        font-family: $font-family-display;
        font-weight: 500;
        font-size: 48px;
        line-height: 1.1;
        color: $dark-1;
        @include media-down(md) { font-size: 32px; }
    }

    &__separator {
        max-width: 1360px;
        margin: $space-2xl auto 0;
        border: 0;
        height: 1px;
        background-color: rgba($dark-1, 0.15);
    }

    &__body {
        max-width: 1080px;
        margin: $space-2xl auto 0;
        padding: 0 $space-3xl;
        @include media-down(md) { padding: 0 $space-sm; }
    }

    &__featured {
        margin: 0 0 $space-md;
        img { width: 100%; height: auto; display: block; }
    }

    &__content {
        max-width: 720px;
        margin-inline: auto;
        font-family: $font-family-body;
        font-size: 16px;
        line-height: 24px;
        color: $dark-1;
        p { margin: 0 0 $space-md; }
        p:last-child { margin-bottom: 0; }
        a { color: $brand-blue; text-decoration: underline; }
        strong { font-weight: 600; }
        h2, h3, h4 { margin: $space-2xl 0 $space-md; font-family: $font-family-body; font-weight: 600; line-height: 1.3; }
        h2 { font-size: 24px; }
        h3 { font-size: 20px; }
        h4 { font-size: 18px; }
    }

    &__actions {
        max-width: 720px;
        margin: $space-xl auto 0;
        display: flex;
        justify-content: flex-start;
    }

    &__btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        height: 44px;
        padding: 0 $space-md;
        font-family: $font-family-body;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        border-radius: 9999px;
        border: 1px solid $dark-1;
        background-color: $dark-1;
        color: $white;
        transition: background-color $transition-base, color $transition-base, border-color $transition-base;

        &:hover, &:focus-visible {
            background-color: $brand-blue;
            border-color: $brand-blue;
            color: $white;
            outline: none;
        }
    }
}
```

- [x] **Step 3: Imports + build**

Edit `src/scss/main.scss`. Añadir 2 imports después de `templates/carreras-single`:

```scss
@import "templates/centros-archive";
@import "templates/centros-single";
```

Build:

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -3
```

---

## Task 5: E2E + MEMORY + commit

- [x] **Step 1: Verify archive**

```bash
TS=$(date +%s)
echo "=== HTTP /centros-interdisciplinarios/ (con redirect follow) ==="
curl -sIL "http://localhost:8888/udp/centros-interdisciplinarios/?nocache=$TS" 2>&1 | grep -E "^HTTP" | head -3
echo ""
echo "=== Markup classes ==="
curl -sL "http://localhost:8888/udp/centros-interdisciplinarios/?nocache=$TS" | grep -oE "udp-(centros-archive|card-mosaic)[a-z_-]*" | sort -u
echo ""
echo "=== Cards count (esperado 55) ==="
curl -sL "http://localhost:8888/udp/centros-interdisciplinarios/?nocache=$TS" | grep -cE 'class="udp-centros-archive__item"'
```

Expected: HTTP 200 final, 55 cards.

- [x] **Step 2: Verify single**

```bash
export MYSQL_PWD=root
SLUG=$(/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot udp -sN -e "
SELECT post_name FROM wp_fnku4yposts WHERE post_type='centro-udp' AND post_status='publish' LIMIT 1;")
echo "SLUG=$SLUG"
TS=$(date +%s)
curl -sIL "http://localhost:8888/udp/centro-udp/$SLUG/?nocache=$TS" 2>&1 | grep -E "^HTTP" | head -2
curl -sL "http://localhost:8888/udp/centro-udp/$SLUG/?nocache=$TS" | grep -oE "udp-single-centro[a-z_-]*" | sort -u
curl -sL "http://localhost:8888/udp/centro-udp/$SLUG/?nocache=$TS" | grep -E "Volver a Centros|Visitar sitio del centro" | head -3
```

- [x] **Step 3: Cleanup**

```bash
rm -f /tmp/test-centros.php
```

- [x] **Step 4: MEMORY + commit**

Append a MEMORY.md:

```markdown

### 2026-04-29 — F6c Centros archive + single

**Hechos**:
- `templates/page-centros.php` asignado a página "Centros Interdisciplinarios" (ID 16). Theme dark, mosaico 5-col reusando `card-mosaic` SIN eyebrow ni filtros.
- Helpers en `inc/udp-cards.php`: `udp_query_centros` (no filtros, sort por title ASC) + `udp_card_data_from_centro` (href = link_externo target=_blank si existe sino permalink).
- `single-centro-udp.php`: light theme simple (sin sidebar 2-col). Featured + post_content + button "Visitar sitio del centro" (target=_blank con link_externo si existe). Reusa post-share.
- 2 SCSS nuevos: `_centros-archive.scss` (dark, idéntico al de facultades pero sin filtros) y `_centros-single.scss` (light single-column con featured + content + button).

**Decisiones clave**:
- No eyebrow en cards de centros — visualmente igual a facultades. La taxonomía facultad podría usarse como eyebrow pero se prefiere la simpleza del Figma de facultades.
- Single layout 1-col centrado (no 2-col como carrera) — los centros tienen menos data estructurada (solo link_externo).
- Card mosaic primitive de F6a se reutiliza sin modificar — ningún cambio retroactivo.

**Pendientes**:
- F6 cerrado (a + b + c). F6 extras (`block_facultades_mosaic` flex content) sigue diferido.
- Algunos centros pueden no tener featured image — el card mostrará placeholder hatching (consistente con facultades).
```

Commit:

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add \
  inc/udp-cards.php \
  templates/page-centros.php \
  single-centro-udp.php \
  src/scss/templates/_centros-archive.scss \
  src/scss/templates/_centros-single.scss \
  src/scss/main.scss \
  MEMORY.md
git commit -m "$(cat <<'EOF'
feat(centros): F6c archive + single

- templates/page-centros.php asignado a página Centros Interdisciplinarios
  (ID 16). Theme dark, mosaico 5-col reusando card-mosaic. Sin filtros.
- single-centro-udp.php: light single-column (featured + content + button
  externo opcional). Reusa post-share.
- inc/udp-cards.php: udp_query_centros + udp_card_data_from_centro
  (href = link_externo target=_blank si existe sino permalink).
- 2 SCSS: _centros-archive (dark) + _centros-single (light simple).
EOF
)"
```

---

## Verification

1. `/centros-interdisciplinarios/` HTTP 200 (post redirect), 55 cards en mosaico 5-col, sin filtros.
2. Click en card con link_externo → URL externa target=_blank. Click sin link_externo → single.
3. Single light, featured + content + button "Visitar sitio del centro" si tiene link_externo.
