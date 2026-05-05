# F7c — block_people_list + block_premios_list — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement this plan task-by-task.

**Goal:** Añadir 2 layouts al field flex `content_blocks`: `block_people_list` (grid de personas con foto+nombre+cargo) y `block_premios_list` (lista cronológica de premios). Insertables en cualquier landing flex.

**Architecture:** Layouts adicionales en el ACF flex existente. CSS-only (no JS). Reusan patrones de F4-F7. Card "person" pequeña (foto cuadrada/retrato + texto). Card "premio" como row table-like con año destacado.

**Reference:** Figma Consejo Académico `3722:43026` (people_list) — ver "Integrantes por cargo", "Representantes Cuerpo Académico", "Representantes Estudiantiles". Premios sin Figma específico — diseño derivado.

---

## Inventario

**Crear:**
- `template-parts/blocks/block-block_people_list.php`
- `template-parts/blocks/block-block_premios_list.php`
- `src/scss/blocks/_block-people-list.scss`
- `src/scss/blocks/_block-premios-list.scss`

**Modificar:**
- `acf-json/group_template_flexible_content.json` — añadir 2 layouts.
- `src/scss/main.scss` — 2 imports.

---

## Task 1: ACF JSON — 2 layouts

- [x] **Step 1: Layouts via jq**

```bash
JSON_PATH="/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_template_flexible_content.json"

cat > /tmp/layout-people.json <<'EOF'
{
    "key": "layout_block_people_list",
    "name": "block_people_list",
    "label": "Lista de personas",
    "display": "block",
    "sub_fields": [
        { "key": "field_block_people_titulo",  "label": "Título (opcional)", "name": "titulo",  "type": "text" },
        { "key": "field_block_people_eyebrow", "label": "Eyebrow (opcional)", "name": "eyebrow", "type": "text" },
        {
            "key": "field_block_people_columnas",
            "label": "Columnas",
            "name": "columnas",
            "type": "radio",
            "required": 1,
            "choices": { "3": "3", "4": "4", "5": "5" },
            "default_value": "4",
            "layout": "horizontal",
            "return_format": "value"
        },
        {
            "key": "field_block_people_personas",
            "label": "Personas",
            "name": "personas",
            "type": "repeater",
            "min": 1,
            "layout": "block",
            "button_label": "Agregar persona",
            "sub_fields": [
                { "key": "field_block_people_persona_foto", "label": "Foto", "name": "foto", "type": "image", "return_format": "array", "preview_size": "thumbnail" },
                { "key": "field_block_people_persona_nombre", "label": "Nombre", "name": "nombre", "type": "text", "required": 1 },
                { "key": "field_block_people_persona_cargo", "label": "Cargo / Rol", "name": "cargo", "type": "text" },
                { "key": "field_block_people_persona_descripcion", "label": "Descripción (opcional, 1-2 líneas)", "name": "descripcion", "type": "textarea", "rows": 2 }
            ]
        },
        {
            "key": "field_block_people_theme",
            "label": "Tema",
            "name": "theme",
            "type": "radio",
            "choices": { "dark": "Dark", "light": "Light" },
            "default_value": "light",
            "layout": "horizontal",
            "return_format": "value"
        }
    ]
}
EOF

cat > /tmp/layout-premios.json <<'EOF'
{
    "key": "layout_block_premios_list",
    "name": "block_premios_list",
    "label": "Lista de premios",
    "display": "block",
    "sub_fields": [
        { "key": "field_block_premios_titulo",  "label": "Título (opcional)", "name": "titulo",  "type": "text" },
        { "key": "field_block_premios_eyebrow", "label": "Eyebrow (opcional)", "name": "eyebrow", "type": "text" },
        {
            "key": "field_block_premios_premios",
            "label": "Premios",
            "name": "premios",
            "type": "repeater",
            "min": 1,
            "layout": "block",
            "button_label": "Agregar premio",
            "sub_fields": [
                { "key": "field_block_premios_premio_ano",     "label": "Año", "name": "ano", "type": "number", "min": 1900, "max": 2099, "required": 1 },
                { "key": "field_block_premios_premio_titulo",  "label": "Título del premio", "name": "titulo", "type": "text", "required": 1 },
                { "key": "field_block_premios_premio_persona", "label": "Persona / Recipient (opcional)", "name": "persona", "type": "text" },
                { "key": "field_block_premios_premio_descripcion", "label": "Descripción (opcional)", "name": "descripcion", "type": "textarea", "rows": 3 }
            ]
        },
        {
            "key": "field_block_premios_orden",
            "label": "Orden",
            "name": "orden",
            "type": "radio",
            "required": 1,
            "choices": { "desc": "Más recientes primero", "asc": "Más antiguos primero", "manual": "Manual (orden de captura)" },
            "default_value": "desc",
            "layout": "horizontal",
            "return_format": "value"
        },
        {
            "key": "field_block_premios_theme",
            "label": "Tema",
            "name": "theme",
            "type": "radio",
            "choices": { "dark": "Dark", "light": "Light" },
            "default_value": "light",
            "layout": "horizontal",
            "return_format": "value"
        }
    ]
}
EOF

jq --slurpfile p /tmp/layout-people.json --slurpfile pr /tmp/layout-premios.json '
    .fields[0].layouts.layout_block_people_list  = $p[0] |
    .fields[0].layouts.layout_block_premios_list = $pr[0]
' "$JSON_PATH" > "$JSON_PATH.tmp" && mv "$JSON_PATH.tmp" "$JSON_PATH"

jq empty "$JSON_PATH" && echo "JSON válido"
jq '.fields[0].layouts | keys' "$JSON_PATH"
```

Expected: 9 layouts total.

- [x] **Step 2: Sync DB-direct UPSERT**

`/tmp/acf-sync-flex-f7c.php`:

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
/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval-file /tmp/acf-sync-flex-f7c.php --path=/Applications/MAMP/htdocs/udp/cms 2>&1 | tail -3
```

---

## Task 2: Container partials

- [x] **Step 1: `template-parts/blocks/block-block_people_list.php`**

```php
<?php
/**
 * Block: People List — grid de personas (foto + nombre + cargo + descripcion).
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$titulo   = get_sub_field( 'titulo' );
$eyebrow  = get_sub_field( 'eyebrow' );
$columnas = get_sub_field( 'columnas' ) ?: '4';
$personas = get_sub_field( 'personas' ) ?: array();
$theme    = get_sub_field( 'theme' ) ?: 'light';

if ( empty( $personas ) ) {
    return;
}

$container_class = sprintf( 'udp-block-people-list udp-block-people-list--cols-%s udp-block-people-list--%s', esc_attr( $columnas ), esc_attr( $theme ) );
?>
<section class="<?php echo esc_attr( $container_class ); ?>">
    <div class="udp-block-people-list__inner">
        <?php if ( $eyebrow || $titulo ) : ?>
            <header class="udp-block-people-list__header">
                <?php if ( $eyebrow ) : ?>
                    <p class="udp-block-people-list__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
                <?php endif; ?>
                <?php if ( $titulo ) : ?>
                    <h2 class="udp-block-people-list__title"><?php echo esc_html( $titulo ); ?></h2>
                <?php endif; ?>
            </header>
        <?php endif; ?>

        <ul class="udp-block-people-list__list">
            <?php foreach ( $personas as $persona ) :
                $foto        = is_array( $persona['foto'] ?? null ) ? $persona['foto'] : array();
                $nombre      = $persona['nombre'] ?? '';
                $cargo       = $persona['cargo']  ?? '';
                $descripcion = $persona['descripcion'] ?? '';
                if ( ! $nombre ) continue;
                $foto_url = $foto['sizes']['medium'] ?? ( $foto['url'] ?? '' );
                $foto_alt = $foto['alt'] ?? $nombre;
                $has_foto = $foto_url !== '';
            ?>
                <li class="udp-block-people-list__item">
                    <figure class="udp-block-people-list__media<?php echo $has_foto ? '' : ' udp-block-people-list__media--placeholder'; ?>">
                        <?php if ( $has_foto ) : ?>
                            <img src="<?php echo esc_url( $foto_url ); ?>" alt="<?php echo esc_attr( $foto_alt ); ?>" loading="lazy" decoding="async" />
                        <?php endif; ?>
                    </figure>
                    <div class="udp-block-people-list__body">
                        <h3 class="udp-block-people-list__nombre"><?php echo esc_html( $nombre ); ?></h3>
                        <?php if ( $cargo ) : ?>
                            <p class="udp-block-people-list__cargo"><?php echo esc_html( $cargo ); ?></p>
                        <?php endif; ?>
                        <?php if ( $descripcion ) : ?>
                            <p class="udp-block-people-list__desc"><?php echo esc_html( $descripcion ); ?></p>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
```

- [x] **Step 2: `template-parts/blocks/block-block_premios_list.php`**

```php
<?php
/**
 * Block: Premios List — lista de premios con año destacado.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$titulo  = get_sub_field( 'titulo' );
$eyebrow = get_sub_field( 'eyebrow' );
$premios = get_sub_field( 'premios' ) ?: array();
$orden   = get_sub_field( 'orden' ) ?: 'desc';
$theme   = get_sub_field( 'theme' ) ?: 'light';

if ( empty( $premios ) ) {
    return;
}

// Sort según orden (cuando no es 'manual')
if ( $orden === 'desc' ) {
    usort( $premios, function ( $a, $b ) {
        return ( (int) ( $b['ano'] ?? 0 ) ) - ( (int) ( $a['ano'] ?? 0 ) );
    } );
} elseif ( $orden === 'asc' ) {
    usort( $premios, function ( $a, $b ) {
        return ( (int) ( $a['ano'] ?? 0 ) ) - ( (int) ( $b['ano'] ?? 0 ) );
    } );
}

$container_class = 'udp-block-premios-list udp-block-premios-list--' . $theme;
?>
<section class="<?php echo esc_attr( $container_class ); ?>">
    <div class="udp-block-premios-list__inner">
        <?php if ( $eyebrow || $titulo ) : ?>
            <header class="udp-block-premios-list__header">
                <?php if ( $eyebrow ) : ?>
                    <p class="udp-block-premios-list__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
                <?php endif; ?>
                <?php if ( $titulo ) : ?>
                    <h2 class="udp-block-premios-list__title"><?php echo esc_html( $titulo ); ?></h2>
                <?php endif; ?>
            </header>
        <?php endif; ?>

        <ul class="udp-block-premios-list__list">
            <?php foreach ( $premios as $p ) :
                $ano         = (int) ( $p['ano'] ?? 0 );
                $premio      = $p['titulo']      ?? '';
                $persona     = $p['persona']     ?? '';
                $descripcion = $p['descripcion'] ?? '';
                if ( ! $premio ) continue;
            ?>
                <li class="udp-block-premios-list__item">
                    <div class="udp-block-premios-list__year">
                        <?php echo $ano > 0 ? esc_html( $ano ) : ''; ?>
                    </div>
                    <div class="udp-block-premios-list__body">
                        <h3 class="udp-block-premios-list__premio"><?php echo esc_html( $premio ); ?></h3>
                        <?php if ( $persona ) : ?>
                            <p class="udp-block-premios-list__persona"><?php echo esc_html( $persona ); ?></p>
                        <?php endif; ?>
                        <?php if ( $descripcion ) : ?>
                            <p class="udp-block-premios-list__desc"><?php echo esc_html( $descripcion ); ?></p>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
```

- [x] **Step 3: Validar PHP**

```bash
PHP=/Applications/MAMP/bin/php/php8.4.1/bin/php
$PHP -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/blocks/block-block_people_list.php
$PHP -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/blocks/block-block_premios_list.php
```

---

## Task 3: SCSS

- [x] **Step 1: `_block-people-list.scss`**

```scss
// ==========================================================================
// BLOCK PEOPLE LIST — grid de personas con foto + nombre + cargo + desc.
// ==========================================================================

.udp-block-people-list {
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
        .udp-block-people-list--light & { color: $dark-2; }
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
        gap: $space-xl 30px;
    }

    &--cols-3 &__list { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    &--cols-4 &__list { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    &--cols-5 &__list { grid-template-columns: repeat(5, minmax(0, 1fr)); }

    @include media-down(lg) {
        &__list { grid-template-columns: repeat(3, minmax(0, 1fr)) !important; }
    }
    @include media-down(md) {
        &__list { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; gap: $space-md; }
    }
    @include media-down(sm) {
        &__list { grid-template-columns: 1fr !important; }
    }

    &__item {
        display: flex;
        flex-direction: column;
        gap: $space-2xs;
    }

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

        .udp-block-people-list--light & {
            background: rgba($dark-1, 0.06);

            &--placeholder {
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

    &__body {
        display: flex;
        flex-direction: column;
        gap: 4px;
        padding-top: $space-2xs;
    }

    &__nombre {
        margin: 0;
        font-family: $font-family-body;
        font-weight: 600;
        font-size: 16px;
        line-height: 1.3;
        color: inherit;
    }

    &__cargo {
        margin: 0;
        font-family: $font-family-mono;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: $white-70;

        .udp-block-people-list--light & { color: $dark-2; }
    }

    &__desc {
        margin: 0;
        font-family: $font-family-body;
        font-size: 13px;
        line-height: 1.4;
        color: $white-70;

        .udp-block-people-list--light & { color: rgba($dark-1, 0.7); }
    }
}
```

- [x] **Step 2: `_block-premios-list.scss`**

```scss
// ==========================================================================
// BLOCK PREMIOS LIST — lista cronológica de premios con año destacado.
// Cada item: año (col grande izquierda) + body (premio + persona + desc).
// ==========================================================================

.udp-block-premios-list {
    padding: $space-3xl 0;

    &--dark  { background-color: $dark-1; color: $white; }
    &--light { background-color: $white;  color: $dark-1; }

    &__inner {
        max-width: 1080px;
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
        .udp-block-premios-list--light & { color: $dark-2; }
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
    }

    &__item {
        display: grid;
        grid-template-columns: 120px 1fr;
        gap: $space-md;
        padding: $space-md 0;
        border-top: 1px solid currentColor;
        opacity: 1;

        &:last-child { border-bottom: 1px solid currentColor; }

        @include media-down(md) {
            grid-template-columns: 80px 1fr;
            gap: $space-sm;
        }
    }

    &__year {
        font-family: $font-family-display;
        font-weight: 500;
        font-size: 32px;
        line-height: 1;
        color: $brand-yellow;

        @include media-down(md) { font-size: 24px; }
    }

    &__body {
        display: flex;
        flex-direction: column;
        gap: $space-2xs;
    }

    &__premio {
        margin: 0;
        font-family: $font-family-body;
        font-weight: 600;
        font-size: 18px;
        line-height: 1.3;
        color: inherit;
    }

    &__persona {
        margin: 0;
        font-family: $font-family-body;
        font-weight: 500;
        font-size: 14px;
        color: inherit;
    }

    &__desc {
        margin: 0;
        font-family: $font-family-body;
        font-size: 14px;
        line-height: 1.4;
        color: $white-70;

        .udp-block-premios-list--light & { color: rgba($dark-1, 0.7); }
    }
}
```

- [x] **Step 3: Imports + build**

Edit `src/scss/main.scss`:

```scss
@import "blocks/block-people-list";
@import "blocks/block-premios-list";
```

Build:

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -3
```

---

## Task 4: E2E + commit

- [x] **Step 1: Seed page**

`/tmp/seed-f7c-test.php`:

```php
<?php
$page_id = wp_insert_post( array(
    'post_title'   => 'Test F7c People+Premios',
    'post_status'  => 'publish',
    'post_type'    => 'page',
    'post_name'    => 'test-f7c-people-premios',
) );
update_post_meta( $page_id, '_wp_page_template', 'templates/page-flexible.php' );

global $wpdb;
$att_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type='attachment' AND post_mime_type LIKE 'image/%' AND post_status='inherit' LIMIT 4" );
if ( count( $att_ids ) < 4 ) { $att_ids = array_pad( $att_ids, 4, $att_ids[0] ?? 0 ); }

update_post_meta( $page_id, 'content_blocks', array( 'block_people_list', 'block_premios_list' ) );
update_post_meta( $page_id, '_content_blocks', 'field_template_flex_content_blocks' );

// Block 0: people_list
update_post_meta( $page_id, 'content_blocks_0_titulo', 'Integrantes por cargo' );
update_post_meta( $page_id, '_content_blocks_0_titulo', 'field_block_people_titulo' );
update_post_meta( $page_id, 'content_blocks_0_columnas', '4' );
update_post_meta( $page_id, '_content_blocks_0_columnas', 'field_block_people_columnas' );
update_post_meta( $page_id, 'content_blocks_0_personas', 4 );
update_post_meta( $page_id, '_content_blocks_0_personas', 'field_block_people_personas' );
$personas = array(
    array( 'Carmen Smith', 'Rectora', $att_ids[0], 'Doctora en Educación' ),
    array( 'Juan Pérez', 'Vicerrector', $att_ids[1], 'Magíster en Administración' ),
    array( 'María González', 'Decana', $att_ids[2], 'Doctora en Derecho' ),
    array( 'Pedro Ramírez', 'Director', $att_ids[3], 'Magíster en Comunicación' ),
);
foreach ( $personas as $i => $p ) {
    update_post_meta( $page_id, "content_blocks_0_personas_{$i}_nombre", $p[0] );
    update_post_meta( $page_id, "_content_blocks_0_personas_{$i}_nombre", 'field_block_people_persona_nombre' );
    update_post_meta( $page_id, "content_blocks_0_personas_{$i}_cargo", $p[1] );
    update_post_meta( $page_id, "_content_blocks_0_personas_{$i}_cargo", 'field_block_people_persona_cargo' );
    update_post_meta( $page_id, "content_blocks_0_personas_{$i}_foto", $p[2] );
    update_post_meta( $page_id, "_content_blocks_0_personas_{$i}_foto", 'field_block_people_persona_foto' );
    update_post_meta( $page_id, "content_blocks_0_personas_{$i}_descripcion", $p[3] );
    update_post_meta( $page_id, "_content_blocks_0_personas_{$i}_descripcion", 'field_block_people_persona_descripcion' );
}
update_post_meta( $page_id, 'content_blocks_0_theme', 'light' );
update_post_meta( $page_id, '_content_blocks_0_theme', 'field_block_people_theme' );

// Block 1: premios_list
update_post_meta( $page_id, 'content_blocks_1_titulo', 'Premios Nacionales recibidos' );
update_post_meta( $page_id, '_content_blocks_1_titulo', 'field_block_premios_titulo' );
update_post_meta( $page_id, 'content_blocks_1_premios', 3 );
update_post_meta( $page_id, '_content_blocks_1_premios', 'field_block_premios_premios' );
$prems = array(
    array( 2024, 'Premio Nacional de Literatura', 'Diamela Eltit', 'Por su trayectoria en narrativa contemporánea.' ),
    array( 2020, 'Premio Nacional de Periodismo', 'Mónica González', 'Por sus aportes al periodismo de investigación.' ),
    array( 2018, 'Premio Nacional de Historia', 'Sergio Grez', 'Por sus contribuciones a la historiografía social.' ),
);
foreach ( $prems as $i => $pr ) {
    update_post_meta( $page_id, "content_blocks_1_premios_{$i}_ano", $pr[0] );
    update_post_meta( $page_id, "_content_blocks_1_premios_{$i}_ano", 'field_block_premios_premio_ano' );
    update_post_meta( $page_id, "content_blocks_1_premios_{$i}_titulo", $pr[1] );
    update_post_meta( $page_id, "_content_blocks_1_premios_{$i}_titulo", 'field_block_premios_premio_titulo' );
    update_post_meta( $page_id, "content_blocks_1_premios_{$i}_persona", $pr[2] );
    update_post_meta( $page_id, "_content_blocks_1_premios_{$i}_persona", 'field_block_premios_premio_persona' );
    update_post_meta( $page_id, "content_blocks_1_premios_{$i}_descripcion", $pr[3] );
    update_post_meta( $page_id, "_content_blocks_1_premios_{$i}_descripcion", 'field_block_premios_premio_descripcion' );
}
update_post_meta( $page_id, 'content_blocks_1_orden', 'desc' );
update_post_meta( $page_id, '_content_blocks_1_orden', 'field_block_premios_orden' );
update_post_meta( $page_id, 'content_blocks_1_theme', 'light' );
update_post_meta( $page_id, '_content_blocks_1_theme', 'field_block_premios_theme' );

WP_CLI::success( 'page_id=' . $page_id );
```

```bash
PAGE_ID=$(/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval-file /tmp/seed-f7c-test.php --path=/Applications/MAMP/htdocs/udp/cms 2>&1 | grep -oE 'page_id=[0-9]+' | sed 's/page_id=//')
echo "PAGE_ID=$PAGE_ID"
```

- [x] **Step 2: Verify**

```bash
TS=$(date +%s)
echo "=== Markup classes ==="
curl -s "http://localhost:8888/udp/test-f7c-people-premios/?nocache=$TS" | grep -oE "udp-block-(people-list|premios-list)[a-z_-]*" | sort -u
echo ""
echo "=== People count (esperado 4) ==="
curl -s "http://localhost:8888/udp/test-f7c-people-premios/?nocache=$TS" | grep -cE 'class="udp-block-people-list__item"'
echo ""
echo "=== Premios count (esperado 3) ==="
curl -s "http://localhost:8888/udp/test-f7c-people-premios/?nocache=$TS" | grep -cE 'class="udp-block-premios-list__item"'
echo ""
echo "=== First year (esperado 2024 — DESC sort) ==="
curl -s "http://localhost:8888/udp/test-f7c-people-premios/?nocache=$TS" | grep -oE 'udp-block-premios-list__year">\s*[0-9]+' | head -3
```

Expected: classes presentes, 4 people, 3 premios, primer año 2024.

- [x] **Step 3: Cleanup + MEMORY + commit**

```bash
export MYSQL_PWD=root
SOCK=/Applications/MAMP/tmp/mysql/mysql.sock
/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=$SOCK -uroot udp -e "DELETE FROM wp_fnku4yposts WHERE ID = $PAGE_ID; DELETE FROM wp_fnku4ypostmeta WHERE post_id = $PAGE_ID;"
rm -f /tmp/seed-f7c-test.php /tmp/acf-sync-flex-f7c.php /tmp/layout-people.json /tmp/layout-premios.json
```

Append a MEMORY.md:

```markdown

### 2026-04-29 — F7c block_people_list + block_premios_list

**Hechos**:
- 2 layouts añadidos al field flex `content_blocks`:
  - `block_people_list`: repeater de personas (foto + nombre + cargo + descripcion). Grid responsive 3/4/5 → 3 → 2 → 1 cols. Foto aspect-ratio 4/5 con placeholder hatching cuando no hay foto.
  - `block_premios_list`: repeater de premios (año + título + persona + descripción). Layout grid 2-col (año destacado izquierda, body derecha). Año en Arizona Flare 32px color `$brand-yellow`. Border-top entre items + border-bottom en el último.
- Sort de premios server-side (PHP usort) según `orden` field: desc (default, recientes primero), asc, o manual (orden de captura).
- Theme dark/light en ambos.
- 2 SCSS nuevos: `_block-people-list.scss` (grid people responsive con placeholder consistent con F4-F6) y `_block-premios-list.scss` (table-like rows con año destacado yellow).

**Decisiones clave**:
- Default theme = light en ambos (van en páginas tipo Consejo Académico, Premios Nacionales que en Figma son fondo blanco).
- `block_premios_list` no tiene CPT auto-source — los premios se gestionan manualmente vía repeater. Si en el futuro hay un CPT `premio`, se puede extender el helper como con noticias/agenda.
- Año del premio en `$brand-yellow` es la única acentuación de color — match con la paleta UDP donde el yellow es para destacar.

**F7 cerrado** (a + b + c). Total 7 bloques flex content nuevos: huincha + embed + big_buttons + image_gallery + accordion + people_list + premios_list. Más los 2 anteriores (card_grid F4a + calendario_grid F5d) = **9 layouts** en `group_template_flexible_content`.

**Pendientes**:
- 11 landings de contenido (Historia, Anuarios, Premios y Distinciones, Doctorado HC, Gobernanza, Forma de Gobierno, Consejo Académico, Premios Nacionales, Servicios, Webmail, Accesos Internos) — el cliente las llena desde admin combinando los 9 bloques flex + Section Landing template (F3).
- F8: mega-menú real (panel multi-columna). F9: Home. F10: Polish.
```

Commit:

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add \
  acf-json/group_template_flexible_content.json \
  template-parts/blocks/block-block_people_list.php \
  template-parts/blocks/block-block_premios_list.php \
  src/scss/blocks/_block-people-list.scss \
  src/scss/blocks/_block-premios-list.scss \
  src/scss/main.scss \
  MEMORY.md
git commit -m "$(cat <<'EOF'
feat(blocks): F7c — people_list + premios_list

- block_people_list: repeater personas (foto + nombre + cargo + desc)
  con grid responsive 3/4/5 cols. Placeholder hatching para personas
  sin foto.
- block_premios_list: repeater premios (año + título + persona + desc)
  con layout 2-col. Año destacado en Arizona Flare \$brand-yellow.
  Sort server-side desc/asc/manual.
- 2 SCSS con themes dark/light.

Cierre F7: 7 bloques flex nuevos (huincha + embed + big_buttons +
image_gallery + accordion + people_list + premios_list) — total 9
layouts en group_template_flexible_content.
EOF
)"
```

---

## Verification

1. Admin inserta block_people_list 4-col con 4 personas → grid de 4 fotos cuadradas con nombre/cargo/desc.
2. Mobile: grid pasa a 2 cols → 1 col en muy pequeño.
3. Admin inserta block_premios_list con 3 premios → lista 2-col con año amarillo izquierda + body derecha. Sort DESC default = 2024, 2020, 2018.
