# F7a — 3 bloques simples (huincha + embed + big_buttons) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement this plan task-by-task.

**Goal:** Añadir 3 layouts simples al campo flex `content_blocks` (group `group_template_flexible_content` de F4a/F5d): `block_huincha` (marquee horizontal), `block_embed` (iframe genérico para YouTube/Vimeo/Spotify/etc), `block_big_buttons` (grid de botones grandes). Insertables en cualquier landing flex.

**Architecture:** Layouts adicionales en el ACF flex existente (no nuevo grupo). Container partials siguen pattern `block-block_X.php` (slug-name resolution). Sin JS — todo CSS (marquee con `@keyframes` + `:hover{animation-play-state:paused}` + `prefers-reduced-motion`).

---

## Inventario

**Crear:**
- `template-parts/blocks/block-block_huincha.php`
- `template-parts/blocks/block-block_embed.php`
- `template-parts/blocks/block-block_big_buttons.php`
- `src/scss/blocks/_block-huincha.scss`
- `src/scss/blocks/_block-embed.scss`
- `src/scss/blocks/_block-big-buttons.scss`

**Modificar:**
- `acf-json/group_template_flexible_content.json` — añadir 3 layouts.
- `src/scss/main.scss` — 3 imports.

---

## Task 1: ACF JSON — 3 layouts nuevos

**File:** `acf-json/group_template_flexible_content.json`

- [ ] **Step 1: Read structure**

```bash
jq '.fields[0].layouts | keys' /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_template_flexible_content.json
```

Expected: `["layout_block_calendario_grid", "layout_block_card_grid"]` (de F4a/F5d).

- [ ] **Step 2: Crear los 3 layouts en /tmp y añadir vía jq**

```bash
JSON_PATH="/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_template_flexible_content.json"

# Huincha layout
cat > /tmp/layout-huincha.json <<'EOF'
{
    "key": "layout_block_huincha",
    "name": "block_huincha",
    "label": "Huincha (marquee)",
    "display": "block",
    "sub_fields": [
        { "key": "field_block_huincha_titulo", "label": "Título (opcional)", "name": "titulo", "type": "text" },
        {
            "key": "field_block_huincha_items",
            "label": "Items",
            "name": "items",
            "type": "repeater",
            "min": 1,
            "layout": "table",
            "button_label": "Agregar item",
            "sub_fields": [
                { "key": "field_block_huincha_item_text",  "label": "Texto", "name": "text", "type": "text" },
                { "key": "field_block_huincha_item_image", "label": "Logo (opcional)", "name": "image", "type": "image", "return_format": "array", "preview_size": "thumbnail" },
                { "key": "field_block_huincha_item_link",  "label": "Link (opcional)", "name": "link", "type": "url" }
            ]
        },
        {
            "key": "field_block_huincha_direccion",
            "label": "Dirección",
            "name": "direccion",
            "type": "radio",
            "choices": { "left": "Izquierda", "right": "Derecha" },
            "default_value": "left",
            "layout": "horizontal",
            "return_format": "value"
        },
        {
            "key": "field_block_huincha_speed",
            "label": "Velocidad (segundos por ciclo)",
            "name": "speed",
            "type": "number",
            "default_value": 30,
            "min": 10,
            "max": 120,
            "instructions": "Tiempo en segundos para recorrer el ancho completo. Más alto = más lento."
        },
        {
            "key": "field_block_huincha_theme",
            "label": "Tema",
            "name": "theme",
            "type": "radio",
            "choices": { "dark": "Dark", "light": "Light" },
            "default_value": "dark",
            "layout": "horizontal",
            "return_format": "value"
        }
    ]
}
EOF

# Embed layout
cat > /tmp/layout-embed.json <<'EOF'
{
    "key": "layout_block_embed",
    "name": "block_embed",
    "label": "Embed (iframe)",
    "display": "block",
    "sub_fields": [
        { "key": "field_block_embed_titulo", "label": "Título (opcional)", "name": "titulo", "type": "text" },
        {
            "key": "field_block_embed_provider",
            "label": "Proveedor",
            "name": "provider",
            "type": "radio",
            "required": 1,
            "choices": { "youtube": "YouTube", "vimeo": "Vimeo", "spotify": "Spotify", "maps": "Google Maps", "generic": "Genérico (URL completa)" },
            "default_value": "youtube",
            "layout": "horizontal",
            "return_format": "value"
        },
        {
            "key": "field_block_embed_url",
            "label": "URL o ID",
            "name": "url",
            "type": "text",
            "required": 1,
            "instructions": "URL completa del recurso o ID. Soporta: youtube.com/watch?v=ID, youtu.be/ID, vimeo.com/ID, open.spotify.com/episode/ID, google.com/maps/embed?..."
        },
        {
            "key": "field_block_embed_aspect_ratio",
            "label": "Aspect ratio",
            "name": "aspect_ratio",
            "type": "radio",
            "choices": { "16-9": "16:9 (video horizontal)", "4-3": "4:3", "1-1": "1:1 (cuadrado)", "9-16": "9:16 (vertical)" },
            "default_value": "16-9",
            "layout": "horizontal",
            "return_format": "value"
        },
        { "key": "field_block_embed_caption", "label": "Caption (opcional)", "name": "caption", "type": "text" },
        {
            "key": "field_block_embed_theme",
            "label": "Tema",
            "name": "theme",
            "type": "radio",
            "choices": { "dark": "Dark", "light": "Light" },
            "default_value": "dark",
            "layout": "horizontal",
            "return_format": "value"
        }
    ]
}
EOF

# Big buttons layout
cat > /tmp/layout-big-buttons.json <<'EOF'
{
    "key": "layout_block_big_buttons",
    "name": "block_big_buttons",
    "label": "Botones grandes",
    "display": "block",
    "sub_fields": [
        { "key": "field_block_big_buttons_titulo", "label": "Título (opcional)", "name": "titulo", "type": "text" },
        { "key": "field_block_big_buttons_eyebrow", "label": "Eyebrow (opcional)", "name": "eyebrow", "type": "text" },
        {
            "key": "field_block_big_buttons_columnas",
            "label": "Columnas",
            "name": "columnas",
            "type": "radio",
            "required": 1,
            "choices": { "2": "2", "3": "3", "4": "4" },
            "default_value": "3",
            "layout": "horizontal",
            "return_format": "value"
        },
        {
            "key": "field_block_big_buttons_buttons",
            "label": "Botones",
            "name": "buttons",
            "type": "repeater",
            "min": 1,
            "layout": "block",
            "button_label": "Agregar botón",
            "sub_fields": [
                { "key": "field_block_big_buttons_btn_label", "label": "Label", "name": "label", "type": "text", "required": 1 },
                { "key": "field_block_big_buttons_btn_descripcion", "label": "Descripción (opcional, 1 línea)", "name": "descripcion", "type": "text" },
                { "key": "field_block_big_buttons_btn_url", "label": "URL", "name": "url", "type": "url", "required": 1 },
                { "key": "field_block_big_buttons_btn_target", "label": "Abrir en pestaña nueva", "name": "target_blank", "type": "true_false", "ui": 1, "default_value": 0 }
            ]
        },
        {
            "key": "field_block_big_buttons_theme",
            "label": "Tema",
            "name": "theme",
            "type": "radio",
            "choices": { "dark": "Dark", "light": "Light" },
            "default_value": "dark",
            "layout": "horizontal",
            "return_format": "value"
        }
    ]
}
EOF

# Add the 3 layouts via jq
jq --slurpfile h /tmp/layout-huincha.json --slurpfile e /tmp/layout-embed.json --slurpfile b /tmp/layout-big-buttons.json '
    .fields[0].layouts.layout_block_huincha     = $h[0] |
    .fields[0].layouts.layout_block_embed       = $e[0] |
    .fields[0].layouts.layout_block_big_buttons = $b[0]
' "$JSON_PATH" > "$JSON_PATH.tmp" && mv "$JSON_PATH.tmp" "$JSON_PATH"

jq empty "$JSON_PATH" && echo "JSON válido"
jq '.fields[0].layouts | keys' "$JSON_PATH"
```

Expected: 5 layouts now (`block_big_buttons`, `block_calendario_grid`, `block_card_grid`, `block_embed`, `block_huincha`).

- [ ] **Step 3: Sync DB-direct UPSERT**

`/tmp/acf-sync-flex-f7a.php`:

```php
<?php
$json = json_decode( file_get_contents( '/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_template_flexible_content.json' ), true );
global $wpdb;
$existing_id = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT ID FROM {$wpdb->posts} WHERE post_type='acf-field-group' AND post_name=%s AND post_status='publish' LIMIT 1",
    $json['key']
) );
if ( $existing_id > 0 ) {
    $json['ID'] = $existing_id;
    WP_CLI::log( 'UPDATE id=' . $existing_id );
} else {
    WP_CLI::log( 'CREATE' );
}
$result = acf_import_field_group( $json );
WP_CLI::success( 'id=' . $result['ID'] );
```

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval-file /tmp/acf-sync-flex-f7a.php --path=/Applications/MAMP/htdocs/udp/cms 2>&1 | tail -3
```

Expected: UPDATE + Success.

---

## Task 2: 3 container partials

- [ ] **Step 1: `template-parts/blocks/block-block_huincha.php`**

```php
<?php
/**
 * Block: Huincha (marquee horizontal)
 *
 * CSS-only marquee con pause on hover + prefers-reduced-motion.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$titulo    = get_sub_field( 'titulo' );
$items     = get_sub_field( 'items' ) ?: array();
$direccion = get_sub_field( 'direccion' ) ?: 'left';
$speed     = (int) get_sub_field( 'speed' );
$theme     = get_sub_field( 'theme' ) ?: 'dark';

if ( empty( $items ) ) {
    return;
}
if ( $speed <= 0 ) {
    $speed = 30;
}

$container_class = 'udp-block-huincha udp-block-huincha--' . $theme . ' udp-block-huincha--' . $direccion;
?>
<section class="<?php echo esc_attr( $container_class ); ?>" style="--udp-huincha-speed: <?php echo (int) $speed; ?>s;">
    <?php if ( $titulo ) : ?>
        <h2 class="udp-block-huincha__title"><?php echo esc_html( $titulo ); ?></h2>
    <?php endif; ?>

    <div class="udp-block-huincha__viewport" aria-hidden="false">
        <ul class="udp-block-huincha__track">
            <?php
            // Render items twice for seamless infinite scroll
            for ( $rep = 0; $rep < 2; $rep++ ) :
                foreach ( $items as $item ) :
                    $text  = $item['text']  ?? '';
                    $image = is_array( $item['image'] ?? null ) ? $item['image'] : array();
                    $link  = $item['link']  ?? '';
                    $img_url = $image['sizes']['thumbnail'] ?? ( $image['url'] ?? '' );
                    $img_alt = $image['alt'] ?? $text;
                    $tag = $link ? 'a' : 'span';
            ?>
                    <li class="udp-block-huincha__item">
                        <<?php echo $tag; ?> class="udp-block-huincha__item-inner"
                            <?php if ( $link ) : ?> href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener noreferrer"<?php endif; ?>
                            <?php if ( $rep > 0 ) : ?> aria-hidden="true"<?php endif; ?>
                        >
                            <?php if ( $img_url ) : ?>
                                <img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $img_alt ); ?>" loading="lazy" />
                            <?php endif; ?>
                            <?php if ( $text ) : ?>
                                <span class="udp-block-huincha__item-text"><?php echo esc_html( $text ); ?></span>
                            <?php endif; ?>
                        </<?php echo $tag; ?>>
                    </li>
                <?php endforeach; ?>
            <?php endfor; ?>
        </ul>
    </div>
</section>
```

- [ ] **Step 2: `template-parts/blocks/block-block_embed.php`**

```php
<?php
/**
 * Block: Embed (iframe genérico)
 *
 * Soporta YouTube / Vimeo / Spotify / Google Maps / generic.
 * Detecta IDs desde URLs comunes.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$titulo       = get_sub_field( 'titulo' );
$provider     = get_sub_field( 'provider' ) ?: 'youtube';
$url          = trim( (string) get_sub_field( 'url' ) );
$aspect_ratio = get_sub_field( 'aspect_ratio' ) ?: '16-9';
$caption      = get_sub_field( 'caption' );
$theme        = get_sub_field( 'theme' ) ?: 'dark';

if ( ! $url ) {
    return;
}

// Build iframe src per provider
$iframe_src = '';
$iframe_allow = 'fullscreen';
$iframe_title = $titulo ?: 'Embed';

switch ( $provider ) {
    case 'youtube':
        // Extract ID from various URL formats or use as-is if it's just an ID
        $id = $url;
        if ( preg_match( '#(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([\w-]{11})#', $url, $m ) ) {
            $id = $m[1];
        }
        $iframe_src   = 'https://www.youtube-nocookie.com/embed/' . rawurlencode( $id );
        $iframe_allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share; fullscreen';
        break;

    case 'vimeo':
        $id = $url;
        if ( preg_match( '#vimeo\.com/(\d+)#', $url, $m ) ) {
            $id = $m[1];
        }
        $iframe_src   = 'https://player.vimeo.com/video/' . rawurlencode( $id );
        $iframe_allow = 'autoplay; fullscreen; picture-in-picture';
        break;

    case 'spotify':
        // Accept full URL like https://open.spotify.com/episode/XYZ — convert to /embed/episode/XYZ
        $iframe_src = $url;
        if ( preg_match( '#open\.spotify\.com/(track|episode|playlist|album|show)/([\w-]+)#', $url, $m ) ) {
            $iframe_src = 'https://open.spotify.com/embed/' . $m[1] . '/' . $m[2];
        }
        $iframe_allow = 'autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture';
        break;

    case 'maps':
        // If user pastes "https://www.google.com/maps/embed?..." use as-is
        $iframe_src   = $url;
        $iframe_allow = 'fullscreen';
        break;

    case 'generic':
    default:
        $iframe_src   = $url;
        break;
}

if ( ! $iframe_src ) {
    return;
}

$container_class = 'udp-block-embed udp-block-embed--' . $theme . ' udp-block-embed--ratio-' . $aspect_ratio;
?>
<section class="<?php echo esc_attr( $container_class ); ?>">
    <div class="udp-block-embed__inner">
        <?php if ( $titulo ) : ?>
            <h2 class="udp-block-embed__title"><?php echo esc_html( $titulo ); ?></h2>
        <?php endif; ?>

        <div class="udp-block-embed__media">
            <iframe
                src="<?php echo esc_url( $iframe_src ); ?>"
                title="<?php echo esc_attr( $iframe_title ); ?>"
                loading="lazy"
                allow="<?php echo esc_attr( $iframe_allow ); ?>"
                allowfullscreen
                referrerpolicy="strict-origin-when-cross-origin"
            ></iframe>
        </div>

        <?php if ( $caption ) : ?>
            <p class="udp-block-embed__caption"><?php echo esc_html( $caption ); ?></p>
        <?php endif; ?>
    </div>
</section>
```

- [ ] **Step 3: `template-parts/blocks/block-block_big_buttons.php`**

```php
<?php
/**
 * Block: Botones grandes (grid de big CTAs)
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$titulo   = get_sub_field( 'titulo' );
$eyebrow  = get_sub_field( 'eyebrow' );
$columnas = get_sub_field( 'columnas' ) ?: '3';
$buttons  = get_sub_field( 'buttons' ) ?: array();
$theme    = get_sub_field( 'theme' ) ?: 'dark';

if ( empty( $buttons ) ) {
    return;
}

$container_class = sprintf( 'udp-block-big-buttons udp-block-big-buttons--cols-%s udp-block-big-buttons--%s', esc_attr( $columnas ), esc_attr( $theme ) );
?>
<section class="<?php echo esc_attr( $container_class ); ?>">
    <div class="udp-block-big-buttons__inner">
        <?php if ( $eyebrow || $titulo ) : ?>
            <header class="udp-block-big-buttons__header">
                <?php if ( $eyebrow ) : ?>
                    <p class="udp-block-big-buttons__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
                <?php endif; ?>
                <?php if ( $titulo ) : ?>
                    <h2 class="udp-block-big-buttons__title"><?php echo esc_html( $titulo ); ?></h2>
                <?php endif; ?>
            </header>
        <?php endif; ?>

        <ul class="udp-block-big-buttons__list">
            <?php foreach ( $buttons as $btn ) :
                $label       = $btn['label']       ?? '';
                $descripcion = $btn['descripcion'] ?? '';
                $url         = $btn['url']         ?? '';
                $target      = ! empty( $btn['target_blank'] );
                if ( ! $label || ! $url ) continue;
                $rel = $target ? 'noopener noreferrer' : '';
            ?>
                <li class="udp-block-big-buttons__item">
                    <a
                        class="udp-block-big-buttons__btn"
                        href="<?php echo esc_url( $url ); ?>"
                        <?php if ( $target ) : ?>target="_blank" rel="<?php echo esc_attr( $rel ); ?>"<?php endif; ?>
                    >
                        <span class="udp-block-big-buttons__btn-content">
                            <span class="udp-block-big-buttons__btn-label"><?php echo esc_html( $label ); ?></span>
                            <?php if ( $descripcion ) : ?>
                                <span class="udp-block-big-buttons__btn-desc"><?php echo esc_html( $descripcion ); ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="udp-block-big-buttons__btn-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                                <path d="M6 4h8v8M14 4 4 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
```

- [ ] **Step 4: Validate PHP**

```bash
PHP=/Applications/MAMP/bin/php/php8.4.1/bin/php
$PHP -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/blocks/block-block_huincha.php
$PHP -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/blocks/block-block_embed.php
$PHP -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/blocks/block-block_big_buttons.php
```

---

## Task 3: SCSS

- [ ] **Step 1: `_block-huincha.scss`**

```scss
// ==========================================================================
// BLOCK HUINCHA — marquee horizontal CSS-only.
// Items duplicados en el HTML (2x) para scroll infinito sin saltos.
// Pause on hover + respeta prefers-reduced-motion.
// ==========================================================================

@keyframes udp-huincha-left {
    from { transform: translateX(0); }
    to   { transform: translateX(-50%); }
}

@keyframes udp-huincha-right {
    from { transform: translateX(-50%); }
    to   { transform: translateX(0); }
}

.udp-block-huincha {
    padding: $space-2xl 0;
    overflow: hidden;

    &--dark  { background-color: $dark-1; color: $white; }
    &--light { background-color: $white;  color: $dark-1; }

    &__title {
        max-width: 1440px;
        margin: 0 auto $space-md;
        padding: 0 $space-3xl;
        font-family: $font-family-mono;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: $white-70;

        .udp-block-huincha--light & { color: $dark-2; }

        @include media-down(md) { padding: 0 $space-sm; }
    }

    &__viewport {
        overflow: hidden;
        mask-image: linear-gradient(90deg, transparent 0, $dark-1 60px, $dark-1 calc(100% - 60px), transparent 100%);
        -webkit-mask-image: linear-gradient(90deg, transparent 0, $dark-1 60px, $dark-1 calc(100% - 60px), transparent 100%);
    }

    &__track {
        list-style: none;
        margin: 0;
        padding: 0;
        display: inline-flex;
        gap: $space-2xl;
        align-items: center;
        animation: udp-huincha-left var(--udp-huincha-speed, 30s) linear infinite;

        .udp-block-huincha--right & {
            animation-name: udp-huincha-right;
        }
    }

    &:hover .udp-block-huincha__track,
    &:focus-within .udp-block-huincha__track {
        animation-play-state: paused;
    }

    @media (prefers-reduced-motion: reduce) {
        .udp-block-huincha__track {
            animation: none;
            transform: translateX(0);
            flex-wrap: wrap;
            justify-content: center;
        }
    }

    &__item {
        flex-shrink: 0;
        display: inline-flex;
    }

    &__item-inner {
        display: inline-flex;
        align-items: center;
        gap: $space-2xs;
        color: inherit;
        text-decoration: none;
        font-family: $font-family-body;
        font-size: 16px;
        font-weight: 500;
        white-space: nowrap;

        img {
            max-height: 32px;
            width: auto;
            display: block;
        }
    }

    a.udp-block-huincha__item-inner:hover,
    a.udp-block-huincha__item-inner:focus-visible {
        opacity: 0.7;
        outline: none;
    }
}
```

- [ ] **Step 2: `_block-embed.scss`**

```scss
// ==========================================================================
// BLOCK EMBED — iframe wrapper con aspect ratio responsive.
// ==========================================================================

.udp-block-embed {
    padding: $space-3xl 0;

    &--dark  { background-color: $dark-1; color: $white; }
    &--light { background-color: $white;  color: $dark-1; }

    &__inner {
        max-width: 1080px;
        margin-inline: auto;
        padding-inline: $space-3xl;

        @include media-down(md) { padding-inline: $space-sm; }
    }

    &__title {
        margin: 0 0 $space-md;
        font-family: $font-family-display;
        font-weight: 500;
        font-size: 32px;
        line-height: 1.1;
    }

    &__media {
        position: relative;
        width: 100%;
        background: $dark-2;

        iframe {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            border: 0;
            display: block;
        }
    }

    &--ratio-16-9 .udp-block-embed__media { aspect-ratio: 16 / 9; }
    &--ratio-4-3  .udp-block-embed__media { aspect-ratio: 4 / 3; }
    &--ratio-1-1  .udp-block-embed__media { aspect-ratio: 1 / 1; }
    &--ratio-9-16 .udp-block-embed__media { aspect-ratio: 9 / 16; max-width: 480px; margin-inline: auto; }

    &__caption {
        margin: $space-md 0 0;
        font-family: $font-family-body;
        font-size: 14px;
        color: $white-70;

        .udp-block-embed--light & { color: $dark-2; }
    }
}
```

- [ ] **Step 3: `_block-big-buttons.scss`**

```scss
// ==========================================================================
// BLOCK BIG BUTTONS — grid de CTAs grandes con icono arrow-up-right.
// ==========================================================================

.udp-block-big-buttons {
    padding: $space-3xl 0;

    &--dark  { background-color: $dark-1; color: $white; }
    &--light { background-color: $white;  color: $dark-1; }

    &__inner {
        max-width: 1440px;
        margin-inline: auto;
        padding-inline: $space-3xl;

        @include media-down(md) { padding-inline: $space-sm; }
    }

    &__header {
        margin-bottom: $space-xl;
        display: flex;
        flex-direction: column;
        gap: $space-2xs;
    }

    &__eyebrow {
        margin: 0;
        font-family: $font-family-mono;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: $white-70;

        .udp-block-big-buttons--light & { color: $dark-2; }
    }

    &__title {
        margin: 0;
        font-family: $font-family-display;
        font-weight: 500;
        font-size: 32px;
        line-height: 1.1;
    }

    &__list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        gap: $space-md;
    }

    &--cols-2 .udp-block-big-buttons__list { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    &--cols-3 .udp-block-big-buttons__list { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    &--cols-4 .udp-block-big-buttons__list { grid-template-columns: repeat(4, minmax(0, 1fr)); }

    @include media-down(lg) {
        &__list { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
    }
    @include media-down(sm) {
        &__list { grid-template-columns: 1fr !important; }
    }

    &__item { display: block; }

    &__btn {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: $space-md;
        padding: $space-lg;
        min-height: 140px;
        background-color: $dark-2;
        color: $white;
        text-decoration: none;
        border: 1px solid rgba($white, 0.1);
        transition: background-color $transition-base, color $transition-base, border-color $transition-base;

        .udp-block-big-buttons--light & {
            background-color: rgba($dark-1, 0.04);
            color: $dark-1;
            border-color: rgba($dark-1, 0.1);
        }

        &:hover, &:focus-visible {
            background-color: $brand-blue;
            color: $white;
            border-color: $brand-blue;
            outline: none;
        }
    }

    &__btn-content {
        display: flex;
        flex-direction: column;
        gap: $space-2xs;
        flex: 1;
    }

    &__btn-label {
        font-family: $font-family-body;
        font-weight: 500;
        font-size: 18px;
        line-height: 1.3;
    }

    &__btn-desc {
        font-family: $font-family-body;
        font-size: 14px;
        line-height: 1.4;
        opacity: 0.8;
    }

    &__btn-icon {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 9999px;
        border: 1px solid currentColor;
    }
}
```

- [ ] **Step 4: Imports + build**

Edit `src/scss/main.scss`. Localizar la sección de `@import "blocks/...";` y AÑADIR (después de los existentes):

```scss
@import "blocks/block-huincha";
@import "blocks/block-embed";
@import "blocks/block-big-buttons";
```

Build:

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -3
```

---

## Task 4: E2E + commit

- [ ] **Step 1: Crear página flex con los 3 bloques**

`/tmp/seed-f7a-test.php`:

```php
<?php
$page_id = wp_insert_post( array(
    'post_title'   => 'Test F7a Bloques Simples',
    'post_status'  => 'publish',
    'post_type'    => 'page',
    'post_name'    => 'test-f7a-bloques-simples',
) );
update_post_meta( $page_id, '_wp_page_template', 'templates/page-flexible.php' );

// 3 layouts: huincha, embed, big_buttons
update_post_meta( $page_id, 'content_blocks', array( 'block_huincha', 'block_embed', 'block_big_buttons' ) );
update_post_meta( $page_id, '_content_blocks', 'field_template_flex_content_blocks' );

// Block 0: huincha
update_post_meta( $page_id, 'content_blocks_0_titulo', 'Acreditaciones' );
update_post_meta( $page_id, '_content_blocks_0_titulo', 'field_block_huincha_titulo' );
update_post_meta( $page_id, 'content_blocks_0_items', 3 );
update_post_meta( $page_id, '_content_blocks_0_items', 'field_block_huincha_items' );
for ( $i = 0; $i < 3; $i++ ) {
    update_post_meta( $page_id, "content_blocks_0_items_{$i}_text", "Item " . ( $i + 1 ) );
    update_post_meta( $page_id, "_content_blocks_0_items_{$i}_text", 'field_block_huincha_item_text' );
}
update_post_meta( $page_id, 'content_blocks_0_direccion', 'left' );
update_post_meta( $page_id, '_content_blocks_0_direccion', 'field_block_huincha_direccion' );
update_post_meta( $page_id, 'content_blocks_0_speed', 30 );
update_post_meta( $page_id, '_content_blocks_0_speed', 'field_block_huincha_speed' );
update_post_meta( $page_id, 'content_blocks_0_theme', 'dark' );
update_post_meta( $page_id, '_content_blocks_0_theme', 'field_block_huincha_theme' );

// Block 1: embed
update_post_meta( $page_id, 'content_blocks_1_titulo', 'Video institucional' );
update_post_meta( $page_id, '_content_blocks_1_titulo', 'field_block_embed_titulo' );
update_post_meta( $page_id, 'content_blocks_1_provider', 'youtube' );
update_post_meta( $page_id, '_content_blocks_1_provider', 'field_block_embed_provider' );
update_post_meta( $page_id, 'content_blocks_1_url', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' );
update_post_meta( $page_id, '_content_blocks_1_url', 'field_block_embed_url' );
update_post_meta( $page_id, 'content_blocks_1_aspect_ratio', '16-9' );
update_post_meta( $page_id, '_content_blocks_1_aspect_ratio', 'field_block_embed_aspect_ratio' );
update_post_meta( $page_id, 'content_blocks_1_caption', 'Caption opcional del video' );
update_post_meta( $page_id, '_content_blocks_1_caption', 'field_block_embed_caption' );
update_post_meta( $page_id, 'content_blocks_1_theme', 'dark' );
update_post_meta( $page_id, '_content_blocks_1_theme', 'field_block_embed_theme' );

// Block 2: big_buttons
update_post_meta( $page_id, 'content_blocks_2_titulo', 'Accesos rápidos' );
update_post_meta( $page_id, '_content_blocks_2_titulo', 'field_block_big_buttons_titulo' );
update_post_meta( $page_id, 'content_blocks_2_columnas', '3' );
update_post_meta( $page_id, '_content_blocks_2_columnas', 'field_block_big_buttons_columnas' );
update_post_meta( $page_id, 'content_blocks_2_buttons', 3 );
update_post_meta( $page_id, '_content_blocks_2_buttons', 'field_block_big_buttons_buttons' );
$btns = array(
    array( 'Portal Estudiantes', 'Acceso al portal académico', 'https://example.com/estudiantes', 1 ),
    array( 'Webmail UDP', 'Correo institucional', 'https://example.com/webmail', 1 ),
    array( 'Biblioteca', 'Búsqueda de recursos', 'https://example.com/biblioteca', 1 ),
);
foreach ( $btns as $i => $btn ) {
    update_post_meta( $page_id, "content_blocks_2_buttons_{$i}_label", $btn[0] );
    update_post_meta( $page_id, "_content_blocks_2_buttons_{$i}_label", 'field_block_big_buttons_btn_label' );
    update_post_meta( $page_id, "content_blocks_2_buttons_{$i}_descripcion", $btn[1] );
    update_post_meta( $page_id, "_content_blocks_2_buttons_{$i}_descripcion", 'field_block_big_buttons_btn_descripcion' );
    update_post_meta( $page_id, "content_blocks_2_buttons_{$i}_url", $btn[2] );
    update_post_meta( $page_id, "_content_blocks_2_buttons_{$i}_url", 'field_block_big_buttons_btn_url' );
    update_post_meta( $page_id, "content_blocks_2_buttons_{$i}_target_blank", $btn[3] );
    update_post_meta( $page_id, "_content_blocks_2_buttons_{$i}_target_blank", 'field_block_big_buttons_btn_target' );
}
update_post_meta( $page_id, 'content_blocks_2_theme', 'dark' );
update_post_meta( $page_id, '_content_blocks_2_theme', 'field_block_big_buttons_theme' );

WP_CLI::success( 'page_id=' . $page_id );
```

```bash
PAGE_ID=$(/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval-file /tmp/seed-f7a-test.php --path=/Applications/MAMP/htdocs/udp/cms 2>&1 | grep -oE 'page_id=[0-9]+' | sed 's/page_id=//')
echo "PAGE_ID=$PAGE_ID"
```

- [ ] **Step 2: Verify markup**

```bash
TS=$(date +%s)
echo "=== Markup classes ==="
curl -s "http://localhost:8888/udp/test-f7a-bloques-simples/?nocache=$TS" | grep -oE "udp-block-(huincha|embed|big-buttons)[a-z_-]*" | sort -u
echo ""
echo "=== iframe count (esperado 1, youtube) ==="
curl -s "http://localhost:8888/udp/test-f7a-bloques-simples/?nocache=$TS" | grep -cE 'youtube-nocookie'
echo ""
echo "=== big_buttons count (esperado 3) ==="
curl -s "http://localhost:8888/udp/test-f7a-bloques-simples/?nocache=$TS" | grep -cE 'class="udp-block-big-buttons__btn"'
echo ""
echo "=== huincha items duplicados (3 items × 2 reps = 6) ==="
curl -s "http://localhost:8888/udp/test-f7a-bloques-simples/?nocache=$TS" | grep -cE 'class="udp-block-huincha__item"'
```

Expected: classes presentes, iframe youtube-nocookie, 3 big buttons, 6 huincha items.

- [ ] **Step 3: Cleanup + MEMORY + commit**

```bash
export MYSQL_PWD=root
SOCK=/Applications/MAMP/tmp/mysql/mysql.sock
/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=$SOCK -uroot udp -e "DELETE FROM wp_fnku4yposts WHERE ID = $PAGE_ID; DELETE FROM wp_fnku4ypostmeta WHERE post_id = $PAGE_ID;"
rm -f /tmp/seed-f7a-test.php /tmp/acf-sync-flex-f7a.php /tmp/layout-huincha.json /tmp/layout-embed.json /tmp/layout-big-buttons.json
echo "Cleanup OK"
```

Append a MEMORY.md:

```markdown

### 2026-04-29 — F7a 3 bloques simples (huincha + embed + big_buttons)

**Hechos**:
- 3 layouts añadidos al field flex `content_blocks` (group `group_template_flexible_content`):
  - `block_huincha`: marquee CSS-only con items repeater (text + opcional logo + opcional link), dirección LTR/RTL, speed (segundos por ciclo), theme dark/light. Pause on hover + respeta `prefers-reduced-motion` (cae a flex-wrap centrado sin animación).
  - `block_embed`: iframe wrapper con providers YouTube (con detection de ID desde varios formatos URL), Vimeo, Spotify (con conversion de URL a embed path), Google Maps (URL completa esperada), Generic. Aspect ratios 16:9 / 4:3 / 1:1 / 9:16. Iframe lazy + youtube-nocookie por defecto.
  - `block_big_buttons`: grid de 2/3/4 columnas de botones grandes (label + opcional descripción + URL + target_blank toggle). Hover invierte a brand-blue.
- 3 SCSS nuevos: `_block-huincha.scss` (con keyframes left/right + viewport mask gradient en bordes), `_block-embed.scss` (aspect-ratio responsive, max-width en 9:16 vertical), `_block-big-buttons.scss` (grid 2/3/4 cols con responsive cap a 2/1).
- Verificación E2E con seed page que insertó los 3 bloques + curl confirmó: huincha items duplicados 2x para infinite scroll, embed iframe con youtube-nocookie URL, 3 big_buttons rendereados.

**Decisiones clave**:
- Marquee duplicado en HTML (2x items) en lugar de via CSS `content`. Más simple y accesible — el segundo set tiene `aria-hidden="true"`.
- `youtube-nocookie.com` por defecto para embed YouTube (mejor privacidad GDPR-friendly sin cookies de tracking).
- `prefers-reduced-motion` cae a `flex-wrap: wrap` con items centrados — no un `display: none` (queremos que el contenido siga siendo accesible visualmente, solo sin animación).

**Pendientes**:
- F7b: block_image_gallery (Swiper) + block_accordion (collapsibles con JS).
- F7c: block_premios_list + block_people_list (repeaters estructurados).
- 11 landings de contenido (Historia, Anuarios, Premios, etc.) las llena el cliente desde admin combinando estos bloques + Section Landing template (F3).
```

Commit:

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add \
  acf-json/group_template_flexible_content.json \
  template-parts/blocks/block-block_huincha.php \
  template-parts/blocks/block-block_embed.php \
  template-parts/blocks/block-block_big_buttons.php \
  src/scss/blocks/_block-huincha.scss \
  src/scss/blocks/_block-embed.scss \
  src/scss/blocks/_block-big-buttons.scss \
  src/scss/main.scss \
  MEMORY.md
git commit -m "$(cat <<'EOF'
feat(blocks): F7a — huincha + embed + big_buttons

- 3 layouts nuevos en content_blocks flex (group_template_flexible_content):
  block_huincha (marquee CSS-only con prefers-reduced-motion), block_embed
  (iframe YouTube/Vimeo/Spotify/Maps/generic con detection de ID), y
  block_big_buttons (grid 2/3/4 cols con hover brand-blue).
- Container partials siguen pattern block-block_X.php (slug-name resolution).
- 3 SCSS nuevos con themes dark/light. Marquee duplica items 2x para
  infinite scroll seamless con aria-hidden en duplicados.
- youtube-nocookie por defecto.
EOF
)"
```

---

## Verification

1. Admin crea página flex e inserta los 3 bloques. Cada uno renderiza con su shape correspondiente.
2. Huincha: marquee infinito ASC/DESC, pausa en hover, sin animación con prefers-reduced-motion.
3. Embed: iframe youtube-nocookie / vimeo player / spotify embed / etc según provider.
4. Big buttons: grid responsive con hover brand-blue.
