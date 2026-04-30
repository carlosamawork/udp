# F5d — `block_calendario_grid` flex content block — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement this plan task-by-task.

**Goal:** Añadir un segundo layout `block_calendario_grid` al campo flex `content_blocks` (group `group_template_flexible_content` de F4a). El admin lo inserta en cualquier landing flex y muestra N entries del CPT calendario filtradas por año + mes opcional + taxonomías. Reusa el partial `entry-calendario.php` de F5a.

**Architecture:** Layout adicional en el ACF flex existente (no nuevo grupo). Helper nuevo `udp_query_calendario_flat` (flat list con mes opcional + limit). Container partial sigue el pattern de `block-block_card_grid.php` (slug-name resolution: `get_template_part('block', 'block_calendario_grid')` → `block-block_calendario_grid.php`).

**Reference:** Spec `docs/superpowers/specs/2026-04-29-f5d-block-calendario-grid-design.md`.

---

## Inventario

**Crear:**
- `template-parts/blocks/block-block_calendario_grid.php`
- `src/scss/blocks/_block-calendario-grid.scss`

**Modificar:**
- `acf-json/group_template_flexible_content.json` — añadir layout.
- `inc/udp-cards.php` — `udp_query_calendario_flat`.
- `src/scss/main.scss` — import.

---

## Task 1: Helper `udp_query_calendario_flat`

**File:** `inc/udp-cards.php`

- [ ] **Step 1: Append AT END del archivo**

```php

/**
 * Flat list de entries de calendario para uso en bloques (no agrupa por mes).
 *
 * @param array $filters {
 *     @type int    $year     YYYY. Default año actual.
 *     @type string $mes      '01'..'12' o '' para todos.
 *     @type int    $tipo     term_id tipo-udp.
 *     @type int    $publico  term_id publico-udp.
 *     @type int    $limit    Default 10, max 30.
 * }
 * @return array<int,array> Lista plana de entries (shape igual a udp_calendario_data_from_post).
 */
function udp_query_calendario_flat( array $filters ): array {
    $year    = (int) ( $filters['year']    ?? (int) date( 'Y' ) );
    $mes     = (string) ( $filters['mes']  ?? '' );
    $tipo    = (int) ( $filters['tipo']    ?? 0 );
    $publico = (int) ( $filters['publico'] ?? 0 );
    $limit   = max( 1, min( 30, (int) ( $filters['limit'] ?? 10 ) ) );

    // Build meta_query value: 'YYYY' or 'YYYYMM'
    $meta_value = sprintf( '%04d', $year );
    if ( $mes !== '' && preg_match( '/^(0[1-9]|1[0-2])$/', $mes ) ) {
        $meta_value .= $mes;
    }

    $args = array(
        'post_type'      => 'calendario',
        'post_status'    => 'publish',
        'posts_per_page' => $limit,
        'meta_key'       => 'fecha',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'no_found_rows'  => true,
        'meta_query'     => array(
            array(
                'key'     => 'fecha',
                'value'   => $meta_value,
                'compare' => 'LIKE',
            ),
        ),
    );

    $tax_query = array();
    if ( $tipo > 0 ) {
        $tax_query[] = array( 'taxonomy' => 'tipo-udp', 'field' => 'term_id', 'terms' => array( $tipo ) );
    }
    if ( $publico > 0 ) {
        $tax_query[] = array( 'taxonomy' => 'publico-udp', 'field' => 'term_id', 'terms' => array( $publico ) );
    }
    if ( count( $tax_query ) > 1 ) {
        $tax_query['relation'] = 'AND';
    }
    if ( ! empty( $tax_query ) ) {
        $args['tax_query'] = $tax_query;
    }

    $q = new WP_Query( $args );

    $entries = array();
    foreach ( $q->posts as $post ) {
        $entry = udp_calendario_data_from_post( $post );
        if ( ! empty( $entry['fecha'] ) ) {
            $entries[] = $entry;
        }
    }

    return $entries;
}
```

- [ ] **Step 2: Validar PHP + smoke test**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/inc/udp-cards.php
```

Crear `/tmp/test-flat.php`:

```php
<?php
$r = udp_query_calendario_flat( array( 'year' => 2026, 'mes' => '03', 'limit' => 5 ) );
WP_CLI::log( 'count: ' . count( $r ) );
if ( $r ) {
    WP_CLI::log( 'first: ' . $r[0]['titulo'] . ' — ' . $r[0]['fecha'] );
}
$r2 = udp_query_calendario_flat( array( 'year' => 2026, 'limit' => 3 ) );
WP_CLI::log( 'todos count: ' . count( $r2 ) );
WP_CLI::success( 'Flat OK' );
```

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval-file /tmp/test-flat.php --path=/Applications/MAMP/htdocs/udp/cms 2>&1 | tail -5
```

Expected: count > 0 (entries de marzo 2026), todos count = 3.

---

## Task 2: ACF — añadir layout al flex

**File:** `acf-json/group_template_flexible_content.json`

- [ ] **Step 1: Read current JSON to understand structure**

```bash
jq '.fields[0].layouts | keys' /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_template_flexible_content.json
```

Expected: `["layout_block_card_grid"]` (solo el de F4a). Añadiremos `layout_block_calendario_grid`.

- [ ] **Step 2: Editar el JSON añadiendo el nuevo layout**

Edit el archivo. Localizar el objeto `"layouts": { "layout_block_card_grid": { ... } }` y añadir un segundo key `"layout_block_calendario_grid"` con esta definición:

```json
"layout_block_calendario_grid": {
    "key": "layout_block_calendario_grid",
    "name": "block_calendario_grid",
    "label": "Grid de calendario",
    "display": "block",
    "sub_fields": [
        { "key": "field_block_calendario_grid_titulo",  "label": "Título de la sección", "name": "titulo",  "type": "text" },
        { "key": "field_block_calendario_grid_eyebrow", "label": "Eyebrow",              "name": "eyebrow", "type": "text" },
        {
            "key": "field_block_calendario_grid_year",
            "label": "Año",
            "name": "year",
            "type": "number",
            "default_value": 2026,
            "min": 2020,
            "max": 2099,
            "instructions": "Año de las entries a mostrar."
        },
        {
            "key": "field_block_calendario_grid_mes",
            "label": "Mes (opcional)",
            "name": "mes",
            "type": "select",
            "choices": {
                "":   "Todo el año",
                "01": "Enero",
                "02": "Febrero",
                "03": "Marzo",
                "04": "Abril",
                "05": "Mayo",
                "06": "Junio",
                "07": "Julio",
                "08": "Agosto",
                "09": "Septiembre",
                "10": "Octubre",
                "11": "Noviembre",
                "12": "Diciembre"
            },
            "default_value": "",
            "allow_null": 0,
            "ui": 1,
            "return_format": "value"
        },
        {
            "key": "field_block_calendario_grid_filtros",
            "label": "Filtros (opcional)",
            "name": "filtros",
            "type": "group",
            "layout": "block",
            "sub_fields": [
                { "key": "field_block_calendario_grid_filtros_tipo", "label": "Tipo (tipo-udp)", "name": "tipo", "type": "taxonomy", "taxonomy": "tipo-udp", "field_type": "select", "return_format": "id", "allow_null": 1 },
                { "key": "field_block_calendario_grid_filtros_publico", "label": "Público (publico-udp)", "name": "publico", "type": "taxonomy", "taxonomy": "publico-udp", "field_type": "select", "return_format": "id", "allow_null": 1 }
            ]
        },
        {
            "key": "field_block_calendario_grid_n_items",
            "label": "Número de items",
            "name": "n_items",
            "type": "number",
            "required": 1,
            "default_value": 10,
            "min": 1,
            "max": 30
        },
        {
            "key": "field_block_calendario_grid_theme",
            "label": "Tema",
            "name": "theme",
            "type": "radio",
            "required": 1,
            "choices": { "dark": "Dark", "light": "Light" },
            "default_value": "dark",
            "layout": "horizontal",
            "return_format": "value"
        }
    ]
}
```

Use a JSON-aware editor (or jq) to insert this as a sibling of `layout_block_card_grid` inside the `layouts` object.

Pragmatic approach with jq:

```bash
JSON_PATH="/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_template_flexible_content.json"
NEW_LAYOUT_PATH="/tmp/new-cal-layout.json"

cat > "$NEW_LAYOUT_PATH" <<'EOF'
{
    "key": "layout_block_calendario_grid",
    "name": "block_calendario_grid",
    "label": "Grid de calendario",
    "display": "block",
    "sub_fields": [
        { "key": "field_block_calendario_grid_titulo",  "label": "Título de la sección", "name": "titulo",  "type": "text" },
        { "key": "field_block_calendario_grid_eyebrow", "label": "Eyebrow",              "name": "eyebrow", "type": "text" },
        { "key": "field_block_calendario_grid_year", "label": "Año", "name": "year", "type": "number", "default_value": 2026, "min": 2020, "max": 2099, "instructions": "Año de las entries a mostrar." },
        { "key": "field_block_calendario_grid_mes", "label": "Mes (opcional)", "name": "mes", "type": "select", "choices": { "": "Todo el año", "01": "Enero", "02": "Febrero", "03": "Marzo", "04": "Abril", "05": "Mayo", "06": "Junio", "07": "Julio", "08": "Agosto", "09": "Septiembre", "10": "Octubre", "11": "Noviembre", "12": "Diciembre" }, "default_value": "", "allow_null": 0, "ui": 1, "return_format": "value" },
        { "key": "field_block_calendario_grid_filtros", "label": "Filtros (opcional)", "name": "filtros", "type": "group", "layout": "block", "sub_fields": [
            { "key": "field_block_calendario_grid_filtros_tipo", "label": "Tipo (tipo-udp)", "name": "tipo", "type": "taxonomy", "taxonomy": "tipo-udp", "field_type": "select", "return_format": "id", "allow_null": 1 },
            { "key": "field_block_calendario_grid_filtros_publico", "label": "Público (publico-udp)", "name": "publico", "type": "taxonomy", "taxonomy": "publico-udp", "field_type": "select", "return_format": "id", "allow_null": 1 }
        ] },
        { "key": "field_block_calendario_grid_n_items", "label": "Número de items", "name": "n_items", "type": "number", "required": 1, "default_value": 10, "min": 1, "max": 30 },
        { "key": "field_block_calendario_grid_theme", "label": "Tema", "name": "theme", "type": "radio", "required": 1, "choices": { "dark": "Dark", "light": "Light" }, "default_value": "dark", "layout": "horizontal", "return_format": "value" }
    ]
}
EOF

jq --slurpfile new "$NEW_LAYOUT_PATH" '.fields[0].layouts.layout_block_calendario_grid = $new[0]' "$JSON_PATH" > "$JSON_PATH.tmp" && mv "$JSON_PATH.tmp" "$JSON_PATH"

jq empty "$JSON_PATH" && echo "JSON válido"
jq '.fields[0].layouts | keys' "$JSON_PATH"
```

Expected: `["layout_block_calendario_grid", "layout_block_card_grid"]` (orden alfabético).

- [ ] **Step 3: Sync via DB-direct UPSERT**

Crear `/tmp/acf-sync-flex.php`:

```php
<?php
$json_path = '/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_template_flexible_content.json';
$json = json_decode( file_get_contents( $json_path ), true );

global $wpdb;
$existing_id = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT ID FROM {$wpdb->posts} WHERE post_type='acf-field-group' AND post_name=%s AND post_status='publish' LIMIT 1",
    $json['key']
) );

if ( $existing_id > 0 ) {
    $json['ID'] = $existing_id;
    WP_CLI::log( 'UPDATE existing id=' . $existing_id );
} else {
    WP_CLI::log( 'CREATE new' );
}
$result = acf_import_field_group( $json );
WP_CLI::success( 'id=' . $result['ID'] );
```

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval-file /tmp/acf-sync-flex.php --path=/Applications/MAMP/htdocs/udp/cms 2>&1 | tail -3
```

Expected: UPDATE existing.

---

## Task 3: Container partial

**File:** `template-parts/blocks/block-block_calendario_grid.php`

- [ ] **Step 1: Crear el container**

```php
<?php
/**
 * Block: Calendario Grid (flexible content layout)
 *
 * Renderiza N entries del CPT calendario filtradas por año + mes + taxonomías.
 * Reusa entry-calendario.php partial.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$titulo  = get_sub_field( 'titulo' );
$eyebrow = get_sub_field( 'eyebrow' );
$year    = (int) get_sub_field( 'year' );
$mes     = (string) get_sub_field( 'mes' );
$n_items = (int) get_sub_field( 'n_items' );
$theme   = get_sub_field( 'theme' ) ?: 'dark';

$filtros = get_sub_field( 'filtros' ) ?: array();
$tipo    = isset( $filtros['tipo'] ) ? (int) $filtros['tipo'] : 0;
$publico = isset( $filtros['publico'] ) ? (int) $filtros['publico'] : 0;

if ( $year <= 0 ) {
    $year = (int) date( 'Y' );
}
if ( $n_items <= 0 ) {
    $n_items = 10;
}

$entries = function_exists( 'udp_query_calendario_flat' )
    ? udp_query_calendario_flat( array(
        'year'    => $year,
        'mes'     => $mes,
        'tipo'    => $tipo,
        'publico' => $publico,
        'limit'   => $n_items,
    ) )
    : array();

if ( empty( $entries ) ) {
    return;
}

$container_class = 'udp-block-calendario-grid udp-block-calendario-grid--' . $theme;
?>
<section class="<?php echo esc_attr( $container_class ); ?>">
    <div class="udp-block-calendario-grid__inner">
        <?php if ( $titulo || $eyebrow ) : ?>
            <header class="udp-block-calendario-grid__header">
                <?php if ( $eyebrow ) : ?>
                    <p class="udp-block-calendario-grid__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
                <?php endif; ?>
                <?php if ( $titulo ) : ?>
                    <h2 class="udp-block-calendario-grid__title"><?php echo esc_html( $titulo ); ?></h2>
                <?php endif; ?>
            </header>
        <?php endif; ?>

        <ul class="udp-block-calendario-grid__list">
            <?php foreach ( $entries as $entry ) : ?>
                <?php
                get_template_part(
                    'template-parts/blocks/parts/entry-calendario',
                    null,
                    array( 'entry' => $entry )
                );
                ?>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
```

- [ ] **Step 2: Validar PHP**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/blocks/block-block_calendario_grid.php
```

---

## Task 4: SCSS

**File:** `src/scss/blocks/_block-calendario-grid.scss`

- [ ] **Step 1: Crear**

```scss
// ==========================================================================
// BLOCK CALENDARIO GRID — flex content layout `block_calendario_grid`
// Compact widget de entries del CPT calendario.
// Reusa estilos de entry-calendario; aquí solo container + theme override.
// ==========================================================================

.udp-block-calendario-grid {
    padding: $space-3xl 0;

    &--dark {
        background-color: $dark-1;
        color: $white;
    }

    &--light {
        background-color: $white;
        color: $dark-1;

        // Override entry-calendario colors para light theme
        .udp-entry-calendario {
            border-bottom: 1px solid rgba($dark-1, 0.1);

            &__date,
            &__desc { color: $dark-2; }
            &__title { color: $dark-1; }
            &__tipo { color: $dark-2; border-color: rgba($dark-1, 0.2); }

            &--destacado {
                border-left-color: $brand-yellow;
                background-color: rgba($brand-yellow, 0.08);
            }

            &__ics {
                color: $dark-1;
                border-color: rgba($dark-1, 0.2);

                &:hover, &:focus-visible {
                    background-color: $dark-1;
                    color: $white;
                    border-color: $dark-1;
                }
            }
        }
    }

    &__inner {
        max-width: 1440px;
        margin-inline: auto;
        padding-inline: $space-3xl;

        @include media-down(md) {
            padding-inline: $space-sm;
        }
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

        .udp-block-calendario-grid--light & { color: $dark-2; }
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
}
```

- [ ] **Step 2: Importar en main.scss**

Edit `src/scss/main.scss`. Localizar la sección de `@import "blocks/...";` y añadir:

```scss
@import "blocks/block-calendario-grid";
```

- [ ] **Step 3: Build**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -3
```

---

## Task 5: E2E + MEMORY + commit

- [ ] **Step 1: Crear página flex de prueba con bloque calendario**

```bash
export MYSQL_PWD=root
SOCK=/Applications/MAMP/tmp/mysql/mysql.sock

# Insert test page
PAGE_ID=$(/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval '
$id = wp_insert_post( array(
    "post_title"   => "Test Block Calendario",
    "post_status"  => "publish",
    "post_type"    => "page",
    "post_name"    => "test-block-calendario",
) );
update_post_meta( $id, "_wp_page_template", "templates/page-flexible.php" );

// Set flex content
update_post_meta( $id, "content_blocks", array( "block_calendario_grid" ) );
update_post_meta( $id, "_content_blocks", "field_template_flex_content_blocks" );

update_post_meta( $id, "content_blocks_0_titulo", "Próximas fechas — Marzo 2026" );
update_post_meta( $id, "_content_blocks_0_titulo", "field_block_calendario_grid_titulo" );

update_post_meta( $id, "content_blocks_0_year", 2026 );
update_post_meta( $id, "_content_blocks_0_year", "field_block_calendario_grid_year" );

update_post_meta( $id, "content_blocks_0_mes", "03" );
update_post_meta( $id, "_content_blocks_0_mes", "field_block_calendario_grid_mes" );

update_post_meta( $id, "content_blocks_0_n_items", 5 );
update_post_meta( $id, "_content_blocks_0_n_items", "field_block_calendario_grid_n_items" );

update_post_meta( $id, "content_blocks_0_theme", "dark" );
update_post_meta( $id, "_content_blocks_0_theme", "field_block_calendario_grid_theme" );

echo $id;
' --path=/Applications/MAMP/htdocs/udp/cms 2>&1 | tail -1)
echo "PAGE_ID=$PAGE_ID"
```

- [ ] **Step 2: Curl + verificar**

```bash
TS=$(date +%s)
curl -s "http://localhost:8888/udp/test-block-calendario/?nocache=$TS" | grep -oE 'udp-block-calendario-grid[a-z_-]*|udp-entry-calendario[a-z_-]*' | sort -u
echo ""
echo "Entries count:"
curl -s "http://localhost:8888/udp/test-block-calendario/?nocache=$TS" | grep -cE 'class="udp-entry-calendario'
```

Expected: classes presentes, entries > 0.

- [ ] **Step 3: Borrar página de prueba + cleanup**

```bash
/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=$SOCK -uroot udp -e "
DELETE FROM wp_fnku4yposts WHERE ID = $PAGE_ID;
DELETE FROM wp_fnku4ypostmeta WHERE post_id = $PAGE_ID;"
rm -f /tmp/test-flat.php /tmp/acf-sync-flex.php /tmp/new-cal-layout.json
echo "Cleanup OK"
```

- [ ] **Step 4: Update MEMORY.md**

Append:

```markdown

### 2026-04-29 — F5d block_calendario_grid (flex content)

**Hechos**:
- Layout `block_calendario_grid` añadido al campo flex `content_blocks` del group `group_template_flexible_content` (creado en F4a). Sub-fields: titulo, eyebrow, year, mes (select 'Todo el año' o '01'..'12'), filtros group (tipo + publico), n_items (max 30), theme (dark|light).
- Helper nuevo `udp_query_calendario_flat` en `inc/udp-cards.php`: similar a `udp_query_calendario` pero devuelve flat list (no agrupado por mes), soporta filter de mes específico via meta_value LIKE 'YYYYMM', limit max 30.
- Container partial `template-parts/blocks/block-block_calendario_grid.php` (slug-name pattern WP). Reusa partial `entry-calendario.php` (F5a).
- SCSS nuevo `_block-calendario-grid.scss` con container + theme dark/light. Light theme override colors del entry para mantener contraste.

**Decisiones clave**:
- Helper separado `_flat` en lugar de extender `udp_query_calendario` con flag — mantiene cada función con responsabilidad única.
- Light theme override — solo el entry necesita ajustes (no el container del block que ya define bg + color base).

**Pendientes**:
- F5 cerrado (a + b + c + d). F6 en adelante.
```

- [ ] **Step 5: Commit**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add \
  acf-json/group_template_flexible_content.json \
  inc/udp-cards.php \
  template-parts/blocks/block-block_calendario_grid.php \
  src/scss/blocks/_block-calendario-grid.scss \
  src/scss/main.scss \
  MEMORY.md
git commit -m "$(cat <<'EOF'
feat(calendario): F5d block_calendario_grid flex content block

- Nuevo layout block_calendario_grid en el field flex content_blocks
  (group_template_flexible_content de F4a). Sub-fields: titulo + eyebrow
  + year + mes (select 'Todo el año' o 01-12) + filtros group (tipo +
  publico) + n_items (max 30) + theme (dark|light).
- Helper udp_query_calendario_flat en inc/udp-cards.php: flat list de
  entries para uso en bloques (no agrupa por mes).
- Container template-parts/blocks/block-block_calendario_grid.php reusa
  entry-calendario partial de F5a.
- SCSS _block-calendario-grid.scss con theme dark/light. Light theme
  override colors del entry.
EOF
)"
```

---

## Verification

1. Admin crea página flex, añade block "Grid de calendario", configura year=2026, mes='03', n_items=5, theme=dark → 5 entries de marzo 2026 en theme dark con los entries+ICS.
2. Cambiar mes='' → 5 entries del año 2026 (cualquier mes).
3. Cambiar theme=light → bg blanco, texto oscuro.
4. Filtrar por tipo o publico → solo entries que matchean ambos.
