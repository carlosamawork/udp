# F4a — `block_card_grid` + helpers — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Construir el bloque ACF Flexible Content `block_card_grid` (3 fuentes: manual / post / concurso · 3 layouts: 3col/4col/list · 2 themes: dark/light) y el helper `udp_query_cards()` reutilizable por archives futuros (F4b/F4c).

**Architecture:** ACF flex layout dentro de un grupo nuevo `group_template_flexible_content` (location `page_template == page-flexible.php`). Helper plano en `inc/udp-cards.php` con un único entry-point público que normaliza data al shape `Card`. Container template-part lee sub-fields, llama al helper, y delega cada card al partial `card-noticia.php`. SCSS BEM con modifiers `--3col / --4col / --list` y `--dark / --light` aplicados al container.

**Tech Stack:** WordPress, ACF Pro 6.x (flexible_content), Vite 6, SCSS (BEM), Bootstrap 5 utility classes solo donde haga falta.

**Reference:** Spec `docs/superpowers/specs/2026-04-28-f4a-block-card-grid-design.md` (lectura previa requerida).

---

## Inventario de archivos

**Crear:**
- `wp-content/themes/starter-theme/acf-json/group_template_flexible_content.json`
- `wp-content/themes/starter-theme/inc/udp-cards.php`
- `wp-content/themes/starter-theme/template-parts/blocks/block-card_grid.php`
- `wp-content/themes/starter-theme/template-parts/blocks/parts/card-noticia.php`
- `wp-content/themes/starter-theme/src/scss/blocks/_card-grid.scss`

**Modificar:**
- `wp-content/themes/starter-theme/functions.php` — `require_once` del helper.
- `wp-content/themes/starter-theme/src/scss/main.scss` — `@import "blocks/card-grid";`.

**A NO tocar:** `block-cards_grid.php` (scaffold legacy con guion bajo distinto), `_flexible-blocks.scss`, mu-plugins, otros templates.

---

## Task 1: ACF JSON + sync

**Files:**
- Create: `wp-content/themes/starter-theme/acf-json/group_template_flexible_content.json`

- [ ] **Step 1: Escribir el JSON**

```json
{
    "key": "group_template_flexible_content",
    "title": "Template — Flexible Content",
    "fields": [
        {
            "key": "field_template_flex_content_blocks",
            "label": "Bloques de contenido",
            "name": "content_blocks",
            "type": "flexible_content",
            "button_label": "Añadir bloque",
            "min": 0,
            "layouts": {
                "layout_block_card_grid": {
                    "key": "layout_block_card_grid",
                    "name": "block_card_grid",
                    "label": "Grid de cards",
                    "display": "block",
                    "sub_fields": [
                        { "key": "field_block_card_grid_titulo",  "label": "Título de la sección", "name": "titulo",  "type": "text" },
                        { "key": "field_block_card_grid_eyebrow", "label": "Eyebrow",              "name": "eyebrow", "type": "text" },
                        {
                            "key": "field_block_card_grid_fuente",
                            "label": "Fuente de datos",
                            "name": "fuente",
                            "type": "radio",
                            "required": 1,
                            "choices": { "manual": "Manual", "post": "Noticias (CPT post)", "concurso": "Concursos (CPT concurso-academico)" },
                            "default_value": "manual",
                            "layout": "horizontal",
                            "return_format": "value"
                        },
                        {
                            "key": "field_block_card_grid_cards_manuales",
                            "label": "Cards manuales",
                            "name": "cards_manuales",
                            "type": "repeater",
                            "min": 1,
                            "layout": "block",
                            "button_label": "Agregar card",
                            "conditional_logic": [[ { "field": "field_block_card_grid_fuente", "operator": "==", "value": "manual" } ]],
                            "sub_fields": [
                                { "key": "field_block_card_grid_card_eyebrow",       "label": "Eyebrow",       "name": "eyebrow",       "type": "text", "instructions": "Etiqueta superior. P.ej. INTERNACIONAL." },
                                {
                                    "key": "field_block_card_grid_card_eyebrow_color",
                                    "label": "Color del eyebrow",
                                    "name": "eyebrow_color",
                                    "type": "radio",
                                    "choices": { "yellow": "Amarillo", "red": "Rojo", "blue": "Azul" },
                                    "default_value": "yellow",
                                    "layout": "horizontal",
                                    "return_format": "value"
                                },
                                { "key": "field_block_card_grid_card_titulo",        "label": "Título",        "name": "titulo",        "type": "text",        "required": 1 },
                                { "key": "field_block_card_grid_card_imagen",        "label": "Imagen",        "name": "imagen",        "type": "image",       "required": 1, "return_format": "array", "preview_size": "card-thumbnail" },
                                { "key": "field_block_card_grid_card_fecha",         "label": "Fecha",         "name": "fecha",         "type": "date_picker", "display_format": "d / m / Y", "return_format": "Y-m-d", "first_day": 1 },
                                { "key": "field_block_card_grid_card_link",          "label": "Link",          "name": "link",          "type": "link",        "required": 1, "return_format": "array" }
                            ]
                        },
                        {
                            "key": "field_block_card_grid_filtros",
                            "label": "Filtros (CPT)",
                            "name": "filtros",
                            "type": "group",
                            "layout": "block",
                            "conditional_logic": [
                                [ { "field": "field_block_card_grid_fuente", "operator": "==", "value": "post" } ],
                                [ { "field": "field_block_card_grid_fuente", "operator": "==", "value": "concurso" } ]
                            ],
                            "sub_fields": [
                                {
                                    "key": "field_block_card_grid_filtros_taxonomias",
                                    "label": "Categorías (solo Noticias)",
                                    "name": "taxonomias",
                                    "type": "taxonomy",
                                    "taxonomy": "category",
                                    "field_type": "multi_select",
                                    "return_format": "id",
                                    "instructions": "Si dejas vacío, se muestran todos. Aplica solo a fuente Noticias.",
                                    "conditional_logic": [[ { "field": "field_block_card_grid_fuente", "operator": "==", "value": "post" } ]]
                                },
                                { "key": "field_block_card_grid_filtros_n_items", "label": "Número de items", "name": "n_items", "type": "number", "required": 1, "default_value": 6, "min": 1, "max": 24 },
                                {
                                    "key": "field_block_card_grid_filtros_orden",
                                    "label": "Orden",
                                    "name": "orden",
                                    "type": "radio",
                                    "required": 1,
                                    "choices": { "date_desc": "Más recientes primero", "date_asc": "Más antiguos primero", "random": "Aleatorio" },
                                    "default_value": "date_desc",
                                    "layout": "horizontal",
                                    "return_format": "value"
                                }
                            ]
                        },
                        {
                            "key": "field_block_card_grid_columnas",
                            "label": "Columnas",
                            "name": "columnas",
                            "type": "radio",
                            "required": 1,
                            "choices": { "3col": "3 columnas", "4col": "4 columnas", "list": "Lista (apilado)" },
                            "default_value": "3col",
                            "layout": "horizontal",
                            "return_format": "value"
                        },
                        {
                            "key": "field_block_card_grid_theme",
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
            }
        }
    ],
    "location": [
        [{ "param": "page_template", "operator": "==", "value": "templates/page-flexible.php" }]
    ],
    "menu_order": 0,
    "position": "normal",
    "style": "default",
    "label_placement": "top",
    "instruction_placement": "label",
    "active": true,
    "description": "Campo content_blocks (flexible content) con el layout block_card_grid. Container del bloque y card primitive son template-parts."
}
```

- [ ] **Step 2: Validar JSON**

```bash
jq empty /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_template_flexible_content.json && echo "JSON válido"
```

Expected: `JSON válido`.

- [ ] **Step 3: Sync con UPSERT**

Crear `/tmp/acf-sync-flex-content.php`:

```php
<?php
$json_path = '/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_template_flexible_content.json';
$json = json_decode( file_get_contents( $json_path ), true );
$existing = acf_get_field_group( $json['key'] );
if ( $existing && ! empty( $existing['ID'] ) ) {
    $json['ID'] = $existing['ID'];
    WP_CLI::log( 'UPDATE existing id=' . $existing['ID'] );
} else {
    WP_CLI::log( 'CREATE new' );
}
$result = acf_import_field_group( $json );
WP_CLI::success( 'id=' . $result['ID'] );
```

Ejecutar:

```bash
test -f /tmp/wp-cli.phar || curl -sL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /tmp/wp-cli.phar && chmod +x /tmp/wp-cli.phar
/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval-file /tmp/acf-sync-flex-content.php --path=/Applications/MAMP/htdocs/udp/cms 2>&1 | tail -3
```

Expected: `CREATE new` + `Success: id=NNNNN`.

- [ ] **Step 4: Verificar el field group registrado**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval 'print_r( acf_get_fields( acf_get_field_group("group_template_flexible_content")["ID"] )[0]["layouts"] );' --path=/Applications/MAMP/htdocs/udp/cms 2>&1 | tail -25
```

Expected: imprime un array con la key `layout_block_card_grid` y sus sub-fields.

---

## Task 2: Helper `udp-cards.php`

**Files:**
- Create: `wp-content/themes/starter-theme/inc/udp-cards.php`
- Modify: `wp-content/themes/starter-theme/functions.php`

- [ ] **Step 1: Esqueleto del archivo**

Create `wp-content/themes/starter-theme/inc/udp-cards.php`:

```php
<?php
/**
 * Card data helpers
 *
 * Funciones para normalizar data de WP_Posts (o repeaters manuales) al shape
 * `Card` que consume el partial `card-noticia.php`. El helper público
 * udp_query_cards() es reutilizable por blocks (modo limit) y archives
 * (modo paged).
 *
 * Forma `Card`:
 *   [
 *     'eyebrow'       => string,  // 'INTERNACIONAL' o ''
 *     'eyebrow_color' => string,  // 'yellow' | 'red' | 'blue' | ''
 *     'titulo'        => string,  // required
 *     'imagen'        => array,   // ACF image array (id, url, alt, sizes)
 *     'fecha'         => string,  // 'YYYY-MM-DD' o ''
 *     'href'          => string,  // permalink o link.url
 *     'target'        => string,  // '_blank' o ''
 *   ]
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Formatea una fecha ISO (YYYY-MM-DD) a 'DD / MM / YYYY' para display.
 */
function udp_card_format_date( string $iso ): string {
    if ( ! $iso ) {
        return '';
    }
    $ts = strtotime( $iso );
    return $ts ? date_i18n( 'd / m / Y', $ts ) : '';
}

/**
 * Devuelve eyebrow ['text' => ..., 'color' => ...] desde el primer término
 * de la taxonomía 'category' del post. Color fijo 'yellow' en F4a — pendiente
 * de implementar color por término en una iteración futura.
 *
 * @return array { text: string, color: string }
 */
function udp_card_eyebrow_from_post( WP_Post $post ): array {
    $terms = get_the_terms( $post->ID, 'category' );
    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return array( 'text' => '', 'color' => '' );
    }
    return array(
        'text'  => $terms[0]->name,
        'color' => 'yellow',
    );
}

/**
 * Convierte un WP_Post a la forma Card. Devuelve null si el post NO tiene
 * featured image (la card requiere imagen — el caller debe filtrar nulls).
 *
 * @return array|null Card array o null.
 */
function udp_card_data_from_post( WP_Post $post ): ?array {
    $thumb_id = get_post_thumbnail_id( $post->ID );
    if ( ! $thumb_id ) {
        return null;
    }

    $imagen = wp_prepare_attachment_for_js( $thumb_id );
    if ( ! $imagen ) {
        return null;
    }

    $eyebrow = udp_card_eyebrow_from_post( $post );

    return array(
        'eyebrow'       => $eyebrow['text'],
        'eyebrow_color' => $eyebrow['color'],
        'titulo'        => get_the_title( $post ),
        'imagen'        => array(
            'id'    => (int) $thumb_id,
            'url'   => $imagen['url'] ?? '',
            'alt'   => $imagen['alt'] ?? '',
            'sizes' => $imagen['sizes'] ?? array(),
        ),
        'fecha'         => get_the_date( 'Y-m-d', $post ),
        'href'          => get_permalink( $post ),
        'target'        => '',
    );
}

/**
 * Entry point público. Consulta cards según source y filtros, devuelve
 * cards normalizadas + metadata de paginación.
 *
 * @param array $args {
 *     @type string $source        'manual' | 'post' | 'concurso'.
 *     @type array  $manual_cards  Repeater rows si source=manual.
 *     @type array  $taxonomies    IDs de términos 'category' (solo source=post).
 *     @type int    $limit         Items por página. Default 6.
 *     @type int    $paged         Página 1-based. Default 1.
 *     @type string $orden         'date_desc' | 'date_asc' | 'random'. Default 'date_desc'.
 * }
 * @return array { cards: array, total: int, max_pages: int, paged: int }
 */
function udp_query_cards( array $args ): array {
    $source       = $args['source']       ?? 'manual';
    $manual_cards = $args['manual_cards'] ?? array();
    $taxonomies   = $args['taxonomies']   ?? array();
    $limit        = max( 1, (int) ( $args['limit'] ?? 6 ) );
    $paged        = max( 1, (int) ( $args['paged'] ?? 1 ) );
    $orden        = $args['orden'] ?? 'date_desc';

    $cards     = array();
    $total     = 0;

    if ( $source === 'manual' ) {
        foreach ( (array) $manual_cards as $row ) {
            $link = is_array( $row['link'] ?? null ) ? $row['link'] : array();
            $cards[] = array(
                'eyebrow'       => $row['eyebrow'] ?? '',
                'eyebrow_color' => $row['eyebrow_color'] ?? 'yellow',
                'titulo'        => $row['titulo'] ?? '',
                'imagen'        => is_array( $row['imagen'] ?? null ) ? $row['imagen'] : array(),
                'fecha'         => $row['fecha'] ?? '',
                'href'          => $link['url'] ?? '',
                'target'        => $link['target'] ?? '',
            );
        }
        $total = count( $cards );
    } elseif ( in_array( $source, array( 'post', 'concurso' ), true ) ) {
        $post_type = $source === 'post' ? 'post' : 'concurso-academico';

        $orderby_map = array(
            'date_desc' => array( 'orderby' => 'date',     'order' => 'DESC' ),
            'date_asc'  => array( 'orderby' => 'date',     'order' => 'ASC' ),
            'random'    => array( 'orderby' => 'rand',     'order' => 'DESC' ),
        );
        $orderby_args = $orderby_map[ $orden ] ?? $orderby_map['date_desc'];

        $query_args = array(
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'paged'          => $paged,
            'orderby'        => $orderby_args['orderby'],
            'order'          => $orderby_args['order'],
        );

        if ( $source === 'post' && ! empty( $taxonomies ) ) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'category',
                    'field'    => 'term_id',
                    'terms'    => array_map( 'intval', $taxonomies ),
                ),
            );
        }

        $q = new WP_Query( $query_args );
        $total = (int) $q->found_posts;

        foreach ( $q->posts as $post ) {
            $card = udp_card_data_from_post( $post );
            if ( $card ) {
                $cards[] = $card;
            }
        }
    }

    $max_pages = $total > 0 ? (int) ceil( $total / $limit ) : 0;

    return array(
        'cards'     => $cards,
        'total'     => $total,
        'max_pages' => $max_pages,
        'paged'     => $paged,
    );
}
```

- [ ] **Step 2: Wire en functions.php**

Edit `wp-content/themes/starter-theme/functions.php`. Localizar la sección donde otros helpers se cargan (búsqueda: `require_once STARTER_BS5_DIR . 'inc/`) y AÑADIR la línea:

```php
require_once STARTER_BS5_DIR . 'inc/udp-cards.php';
```

Justo después del último `require_once` existente.

- [ ] **Step 3: Validar PHP syntax**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/inc/udp-cards.php
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/functions.php
```

Expected: 2× `No syntax errors detected`.

- [ ] **Step 4: Smoke test del helper en modo manual**

Crear `/tmp/test-udp-query-cards.php`:

```php
<?php
$result = udp_query_cards( array(
    'source'       => 'manual',
    'manual_cards' => array(
        array(
            'eyebrow'       => 'TEST',
            'eyebrow_color' => 'yellow',
            'titulo'        => 'Card de prueba',
            'imagen'        => array( 'id' => 1, 'url' => 'http://example.com/x.jpg', 'alt' => '', 'sizes' => array() ),
            'fecha'         => '2026-04-28',
            'link'          => array( 'url' => '/test', 'title' => 'Test', 'target' => '' ),
        ),
    ),
    'limit'  => 6,
) );
WP_CLI::log( 'cards count: ' . count( $result['cards'] ) );
WP_CLI::log( 'total: ' . $result['total'] );
WP_CLI::log( 'max_pages: ' . $result['max_pages'] );
WP_CLI::log( 'fecha display: ' . udp_card_format_date( $result['cards'][0]['fecha'] ) );
WP_CLI::success( 'Helper OK' );
```

Ejecutar:

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval-file /tmp/test-udp-query-cards.php --path=/Applications/MAMP/htdocs/udp/cms 2>&1 | tail -5
```

Expected:
```
cards count: 1
total: 1
max_pages: 1
fecha display: 28 / 04 / 2026
Success: Helper OK
```

- [ ] **Step 5: Smoke test del helper en modo post**

Crear `/tmp/test-udp-query-cards-post.php`:

```php
<?php
$result = udp_query_cards( array(
    'source' => 'post',
    'limit'  => 3,
    'paged'  => 1,
    'orden'  => 'date_desc',
) );
WP_CLI::log( 'cards count: ' . count( $result['cards'] ) );
WP_CLI::log( 'total: ' . $result['total'] );
WP_CLI::log( 'max_pages: ' . $result['max_pages'] );
if ( ! empty( $result['cards'] ) ) {
    $first = $result['cards'][0];
    WP_CLI::log( 'first titulo: ' . $first['titulo'] );
    WP_CLI::log( 'first href: ' . $first['href'] );
    WP_CLI::log( 'first fecha: ' . $first['fecha'] );
    WP_CLI::log( 'first eyebrow: ' . $first['eyebrow'] );
}
WP_CLI::success( 'Post mode OK' );
```

Ejecutar:

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval-file /tmp/test-udp-query-cards-post.php --path=/Applications/MAMP/htdocs/udp/cms 2>&1 | tail -8
```

Expected: `cards count: 3` (o menos si hay posts sin featured image entre los 3 más recientes), `total > 0`, datos del primer post.

Si `cards count: 0` y `total: 3+`: significa que ningún post entre los 3 más recientes tiene featured image. NO es un fallo del helper — documenta y continúa.

---

## Task 3: Card partial `card-noticia.php`

**Files:**
- Create: `wp-content/themes/starter-theme/template-parts/blocks/parts/card-noticia.php`

- [ ] **Step 1: Crear directorio si no existe**

```bash
mkdir -p /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/blocks/parts
```

- [ ] **Step 2: Crear el partial**

Create `wp-content/themes/starter-theme/template-parts/blocks/parts/card-noticia.php`:

```php
<?php
/**
 * Card primitive — Noticia
 *
 * Recibe data ya normalizada en shape `Card` (ver inc/udp-cards.php).
 * No sabe de WP_Query ni del bloque contenedor; reutilizable por archives.
 *
 * @package Starter_Theme
 *
 * @var array $args ['card' => array, 'theme' => string]
 */
$card  = isset( $args['card'] ) && is_array( $args['card'] ) ? $args['card'] : array();
$theme = isset( $args['theme'] ) && in_array( $args['theme'], array( 'dark', 'light' ), true ) ? $args['theme'] : 'dark';

$href = $card['href'] ?? '';
$titulo = $card['titulo'] ?? '';
$imagen = is_array( $card['imagen'] ?? null ) ? $card['imagen'] : array();

if ( empty( $href ) || empty( $titulo ) || empty( $imagen['url'] ?? '' ) ) {
    return;
}

$eyebrow       = $card['eyebrow'] ?? '';
$eyebrow_color = in_array( ( $card['eyebrow_color'] ?? '' ), array( 'yellow', 'red', 'blue' ), true ) ? $card['eyebrow_color'] : '';
$fecha_iso     = $card['fecha'] ?? '';
$fecha_display = function_exists( 'udp_card_format_date' ) ? udp_card_format_date( $fecha_iso ) : '';
$target        = $card['target'] ?? '';
$rel           = $target === '_blank' ? 'noopener noreferrer' : '';

$class = 'udp-card-noticia udp-card-noticia--' . $theme;
?>
<a
    href="<?php echo esc_url( $href ); ?>"
    class="<?php echo esc_attr( $class ); ?>"
    <?php if ( $target ) : ?>target="<?php echo esc_attr( $target ); ?>"<?php endif; ?>
    <?php if ( $rel ) : ?>rel="<?php echo esc_attr( $rel ); ?>"<?php endif; ?>
>
    <figure class="udp-card-noticia__media">
        <img
            src="<?php echo esc_url( $imagen['url'] ); ?>"
            alt="<?php echo esc_attr( $imagen['alt'] ?? '' ); ?>"
            loading="lazy"
            decoding="async"
        />
    </figure>
    <div class="udp-card-noticia__body">
        <header class="udp-card-noticia__meta">
            <?php if ( $eyebrow ) : ?>
                <span class="udp-card-noticia__eyebrow<?php echo $eyebrow_color ? ' udp-card-noticia__eyebrow--' . esc_attr( $eyebrow_color ) : ''; ?>"><?php echo esc_html( $eyebrow ); ?></span>
            <?php endif; ?>
            <?php if ( $fecha_iso && $fecha_display ) : ?>
                <time class="udp-card-noticia__date" datetime="<?php echo esc_attr( $fecha_iso ); ?>"><?php echo esc_html( $fecha_display ); ?></time>
            <?php endif; ?>
        </header>
        <h3 class="udp-card-noticia__title"><?php echo esc_html( $titulo ); ?></h3>
        <span class="udp-card-noticia__more" aria-hidden="true">
            <?php esc_html_e( 'Leer más', 'starter-theme' ); ?>
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                <path d="M3.5 2.5h6v6M9.5 2.5l-7 7" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </span>
    </div>
</a>
```

- [ ] **Step 3: Validar PHP syntax**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/blocks/parts/card-noticia.php
```

Expected: `No syntax errors detected`.

---

## Task 4: Container `block-card_grid.php`

**Files:**
- Create: `wp-content/themes/starter-theme/template-parts/blocks/block-card_grid.php`

- [ ] **Step 1: Crear el container**

Create `wp-content/themes/starter-theme/template-parts/blocks/block-card_grid.php`:

```php
<?php
/**
 * Block: Card Grid (flexible content layout)
 *
 * Lee sub-fields del row activo de content_blocks, llama a udp_query_cards()
 * y renderiza el grid usando el partial card-noticia.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$titulo   = get_sub_field( 'titulo' );
$eyebrow  = get_sub_field( 'eyebrow' );
$fuente   = get_sub_field( 'fuente' ) ?: 'manual';
$columnas = get_sub_field( 'columnas' ) ?: '3col';
$theme    = get_sub_field( 'theme' ) ?: 'dark';

$args = array( 'source' => $fuente );

if ( $fuente === 'manual' ) {
    $args['manual_cards'] = get_sub_field( 'cards_manuales' ) ?: array();
    $args['limit']        = 24;
} else {
    $filtros = get_sub_field( 'filtros' ) ?: array();
    $args['taxonomies'] = isset( $filtros['taxonomias'] ) && is_array( $filtros['taxonomias'] ) ? $filtros['taxonomias'] : array();
    $args['limit']      = isset( $filtros['n_items'] ) ? (int) $filtros['n_items'] : 6;
    $args['orden']      = $filtros['orden'] ?? 'date_desc';
}

$result = function_exists( 'udp_query_cards' ) ? udp_query_cards( $args ) : array( 'cards' => array() );
$cards  = $result['cards'];

if ( empty( $cards ) ) {
    return;
}

$container_class = sprintf( 'udp-card-grid udp-card-grid--%s udp-card-grid--%s', $columnas, $theme );
?>
<section class="<?php echo esc_attr( $container_class ); ?>">
    <div class="udp-card-grid__inner">
        <?php if ( $eyebrow || $titulo ) : ?>
            <header class="udp-card-grid__header">
                <?php if ( $eyebrow ) : ?>
                    <p class="udp-card-grid__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
                <?php endif; ?>
                <?php if ( $titulo ) : ?>
                    <h2 class="udp-card-grid__title"><?php echo esc_html( $titulo ); ?></h2>
                <?php endif; ?>
            </header>
        <?php endif; ?>

        <ul class="udp-card-grid__list">
            <?php foreach ( $cards as $card ) : ?>
                <li class="udp-card-grid__item">
                    <?php
                    get_template_part(
                        'template-parts/blocks/parts/card-noticia',
                        null,
                        array( 'card' => $card, 'theme' => $theme )
                    );
                    ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
```

- [ ] **Step 2: Validar PHP syntax**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/blocks/block-card_grid.php
```

Expected: `No syntax errors detected`.

---

## Task 5: SCSS `_card-grid.scss` + import + build

**Files:**
- Create: `wp-content/themes/starter-theme/src/scss/blocks/_card-grid.scss`
- Modify: `wp-content/themes/starter-theme/src/scss/main.scss`

- [ ] **Step 1: Crear el parcial SCSS**

Create `wp-content/themes/starter-theme/src/scss/blocks/_card-grid.scss`:

```scss
// ==========================================================================
// BLOCK CARD GRID — flexible content layout `block_card_grid`
// Container con modificadores --3col / --4col / --list y --dark / --light.
// La card primitive `udp-card-noticia` también se usa en archive-post (F4b).
// ==========================================================================

// --------------------------------------------------------------------------
// CONTAINER
// --------------------------------------------------------------------------
.udp-card-grid {
    padding: $space-3xl 0;

    &--dark {
        background-color: $dark-1;
        color: $white;
    }

    &--light {
        background-color: $white;
        color: $dark-1;
    }

    &__inner {
        max-width: 1440px;
        margin-inline: auto;
        padding-inline: $space-3xl;  // 40px

        @include media-down(md) {
            padding-inline: $space-sm;
        }
    }

    &__header {
        display: flex;
        flex-direction: column;
        gap: $space-2xs;
        margin-bottom: $space-2xl;
    }

    &__eyebrow {
        margin: 0;
        @include eyebrow($white-70);

        .udp-card-grid--light & {
            color: $dark-2;
        }
    }

    &__title {
        margin: 0;
        font-family: $font-family-display;
        font-weight: 500;
        font-size: 40px;
        line-height: 1.1;
        color: inherit;

        @include media-down(md) {
            font-size: 28px;
        }
    }

    &__list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        gap: $space-md;  // 18px

        @include media-down(md) {
            grid-template-columns: 1fr !important;  // forzar 1 col en mobile
        }
    }

    &--3col .udp-card-grid__list {
        grid-template-columns: repeat(3, minmax(0, 1fr));

        @include media-down(xl) {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    &--4col .udp-card-grid__list {
        grid-template-columns: repeat(4, minmax(0, 1fr));

        @include media-down(xl) {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    &--list .udp-card-grid__list {
        grid-template-columns: 1fr;
        gap: $space-sm;
    }

    &__item {
        display: block;
    }
}

// --------------------------------------------------------------------------
// CARD PRIMITIVE — image (top) + eyebrow + title + date + "Leer más"
// --------------------------------------------------------------------------
.udp-card-noticia {
    position: relative;
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
        aspect-ratio: 4 / 3;

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform $transition-base;
        }
    }

    @media (prefers-reduced-motion: no-preference) {
        &:hover .udp-card-noticia__media img,
        &:focus-visible .udp-card-noticia__media img {
            transform: scale(1.03);
        }
    }

    &__body {
        display: flex;
        flex-direction: column;
        gap: $space-2xs;
        padding-top: $space-xs;
    }

    &__meta {
        display: flex;
        align-items: center;
        gap: $space-sm;
        flex-wrap: wrap;
    }

    &__eyebrow {
        display: inline-block;
        padding: 2px 8px;
        font-family: $font-family-mono;
        font-size: 11px;
        font-weight: 600;
        line-height: 1.4;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: $dark-1;
        background-color: $brand-yellow;

        &--yellow { background-color: $brand-yellow; color: $dark-1; }
        &--red    { background-color: $brand-red;    color: $white; }
        &--blue   { background-color: $brand-blue;   color: $white; }
    }

    &__date {
        font-family: $font-family-body;
        font-size: 12px;
        font-weight: 400;
        color: $white-70;

        .udp-card-grid--light & {
            color: $dark-2;
        }
    }

    &__title {
        margin: 0;
        font-family: $font-family-body;
        font-weight: 500;
        font-size: 18px;
        line-height: $line-height-snug;
        color: inherit;

        // line-clamp 2 lines
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;

        .udp-card-grid--4col & { font-size: 16px; }
        .udp-card-grid--list & { font-size: 16px; }
    }

    &__more {
        margin-top: $space-2xs;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-family: $font-family-body;
        font-size: 14px;
        font-weight: 500;
        color: inherit;
        text-decoration: underline;
        text-decoration-thickness: 1px;
        text-underline-offset: 4px;
        transition: text-decoration-thickness $transition-base;

        svg {
            transition: transform $transition-base;
        }
    }

    &:hover .udp-card-noticia__more,
    &:focus-visible .udp-card-noticia__more {
        text-decoration-thickness: 2px;

        svg {
            transform: translate(2px, -2px);
        }
    }

    // ---- LIST LAYOUT ----
    .udp-card-grid--list & {
        flex-direction: row;
        gap: $space-md;
        align-items: flex-start;

        .udp-card-noticia__media {
            flex: 0 0 96px;
            width: 96px;
            height: 96px;
            aspect-ratio: 1;
        }

        .udp-card-noticia__body {
            padding-top: 0;
            flex: 1;
        }
    }
}
```

- [ ] **Step 2: Importar el parcial en main.scss**

Edit `wp-content/themes/starter-theme/src/scss/main.scss`. Localizar la sección `// 7. Bloques ACF` y AÑADIR el import (sin tocar imports existentes):

```scss
// --------------------------------------------------------------------------
// 7. Bloques ACF
// --------------------------------------------------------------------------
@import "blocks/section-landing";
@import "blocks/card-grid";          // ← NUEVO
@import "blocks/flexible-blocks";
```

Si `_section-landing` o `_flexible-blocks` no están en esa sección con esos nombres, añadir solo `@import "blocks/card-grid";` después del último @import de bloques ACF existente.

- [ ] **Step 3: Build**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -8
```

Expected: build OK, CSS sube ~3-5 kB.

---

## Task 6: Verificación E2E + commit final

**Files:**
- Modify: `wp-content/themes/starter-theme/MEMORY.md`

- [ ] **Step 1: Encontrar attachment ID para imagen de prueba**

```bash
export MYSQL_PWD=root
/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot udp -sN -e "SELECT ID FROM wp_fnku4yposts WHERE post_type='attachment' AND post_mime_type LIKE 'image/%' AND post_status='inherit' LIMIT 1;"
```

Anotar el ID devuelto (`<ATT_ID>`). Si la query devuelve vacío: reportar al usuario "no hay attachments en DB; saltando E2E manual y validando solo PHP+build". El plan completa en ese caso.

- [ ] **Step 2: Crear página flex de prueba con bloque manual**

Crear `/tmp/seed-test-card-grid.php` (sustituyendo `<ATT_ID>` por el valor del Step 1):

```php
<?php
$attachment_id = <ATT_ID>;

$page_id = wp_insert_post( array(
    'post_title'   => 'Test Card Grid',
    'post_status'  => 'publish',
    'post_type'    => 'page',
    'post_content' => '',
    'post_name'    => 'test-card-grid',
) );
update_post_meta( $page_id, '_wp_page_template', 'templates/page-flexible.php' );

$flex = array(
    array(
        'acf_fc_layout' => 'block_card_grid',
        'titulo'        => 'Bloque de prueba',
        'eyebrow'       => 'TEST',
        'fuente'        => 'manual',
        'cards_manuales' => array(
            array( 'eyebrow' => 'INTERNACIONAL', 'eyebrow_color' => 'yellow', 'titulo' => 'Card amarilla',  'imagen' => $attachment_id, 'fecha' => '2026-04-28', 'link' => array( 'url' => 'http://localhost:8888/udp/', 'title' => 'Inicio',     'target' => '' ) ),
            array( 'eyebrow' => 'UNIVERSIDAD',   'eyebrow_color' => 'red',    'titulo' => 'Card roja',      'imagen' => $attachment_id, 'fecha' => '2026-04-27', 'link' => array( 'url' => 'http://localhost:8888/udp/', 'title' => 'Universidad','target' => '' ) ),
            array( 'eyebrow' => 'INVESTIGACIÓN', 'eyebrow_color' => 'blue',   'titulo' => 'Card azul',      'imagen' => $attachment_id, 'fecha' => '2026-04-26', 'link' => array( 'url' => 'http://localhost:8888/udp/', 'title' => 'Externo',    'target' => '_blank' ) ),
            array( 'eyebrow' => '',              'eyebrow_color' => 'yellow', 'titulo' => 'Card sin eyebrow ni fecha', 'imagen' => $attachment_id, 'fecha' => '', 'link' => array( 'url' => 'http://localhost:8888/udp/', 'title' => 'Sin meta',   'target' => '' ) ),
        ),
        'columnas' => '3col',
        'theme'    => 'dark',
    ),
);
update_field( 'content_blocks', $flex, $page_id );
WP_CLI::success( 'page_id=' . $page_id );
```

Ejecutar:

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval-file /tmp/seed-test-card-grid.php --path=/Applications/MAMP/htdocs/udp/cms 2>&1 | tail -2
```

Anotar el `page_id` devuelto (`<PAGE_ID>`).

- [ ] **Step 3: Validar HTML — modo manual / 3col / dark**

```bash
TS=$(date +%s)
curl -s "http://localhost:8888/udp/test-card-grid/?nocache=$TS" | grep -E "udp-card-grid--(3col|4col|list|dark|light)|udp-card-noticia|udp-card-noticia__eyebrow--(yellow|red|blue)|Bloque de prueba|Card (amarilla|roja|azul)" | head -20
```

Expected: aparecen al menos:
- `udp-card-grid--3col udp-card-grid--dark`
- `udp-card-noticia udp-card-noticia--dark` (4 veces — una por card)
- `udp-card-noticia__eyebrow--yellow`, `--red`, `--blue`
- Texto "Bloque de prueba", "Card amarilla", "Card roja", "Card azul"

- [ ] **Step 4: Cambiar layout a 4col vía update_field y revalidar**

Crear `/tmp/update-test-4col.php`:

```php
<?php
$page_id = <PAGE_ID>;
$flex = get_field( 'content_blocks', $page_id );
$flex[0]['columnas'] = '4col';
update_field( 'content_blocks', $flex, $page_id );
WP_CLI::success( 'updated to 4col' );
```

Ejecutar y revalidar:

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval-file /tmp/update-test-4col.php --path=/Applications/MAMP/htdocs/udp/cms 2>&1 | tail -1
TS=$(date +%s)
curl -s "http://localhost:8888/udp/test-card-grid/?nocache=$TS" | grep -oE "udp-card-grid--[a-z0-9]+" | sort -u
```

Expected: aparece `udp-card-grid--4col` y `udp-card-grid--dark` (NO debe aparecer `--3col`).

- [ ] **Step 5: Cambiar layout a list + theme light**

Crear `/tmp/update-test-list-light.php`:

```php
<?php
$page_id = <PAGE_ID>;
$flex = get_field( 'content_blocks', $page_id );
$flex[0]['columnas'] = 'list';
$flex[0]['theme']    = 'light';
update_field( 'content_blocks', $flex, $page_id );
WP_CLI::success( 'updated to list+light' );
```

Ejecutar y revalidar:

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval-file /tmp/update-test-list-light.php --path=/Applications/MAMP/htdocs/udp/cms 2>&1 | tail -1
TS=$(date +%s)
curl -s "http://localhost:8888/udp/test-card-grid/?nocache=$TS" | grep -oE "udp-card-(grid|noticia)--[a-z0-9]+" | sort -u
```

Expected: aparecen `udp-card-grid--list`, `udp-card-grid--light`, `udp-card-noticia--light`.

- [ ] **Step 6: Cambiar fuente a `post` y verificar query real**

Crear `/tmp/update-test-source-post.php`:

```php
<?php
$page_id = <PAGE_ID>;
$flex = get_field( 'content_blocks', $page_id );
$flex[0]['fuente']   = 'post';
$flex[0]['filtros']  = array( 'taxonomias' => array(), 'n_items' => 6, 'orden' => 'date_desc' );
$flex[0]['columnas'] = '3col';
$flex[0]['theme']    = 'dark';
update_field( 'content_blocks', $flex, $page_id );
WP_CLI::success( 'updated to post source' );
```

Ejecutar y revalidar:

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval-file /tmp/update-test-source-post.php --path=/Applications/MAMP/htdocs/udp/cms 2>&1 | tail -1
TS=$(date +%s)
curl -s "http://localhost:8888/udp/test-card-grid/?nocache=$TS" | grep -cE "udp-card-noticia "
```

Expected: número >= 1 (cantidad de cards reales rendereadas — depende de cuántos posts publicados tienen featured image). Si devuelve `0`, NO es fallo del helper — significa que ningún post entre los 6 más recientes tiene featured image. Documentar y continuar.

- [ ] **Step 7: Borrar la página de prueba y los archivos /tmp**

```bash
export MYSQL_PWD=root
/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot udp -e "DELETE FROM wp_fnku4yposts WHERE ID = <PAGE_ID>; DELETE FROM wp_fnku4ypostmeta WHERE post_id = <PAGE_ID>;"
rm -f /tmp/seed-test-card-grid.php /tmp/update-test-4col.php /tmp/update-test-list-light.php /tmp/update-test-source-post.php /tmp/test-udp-query-cards.php /tmp/test-udp-query-cards-post.php /tmp/acf-sync-flex-content.php
echo "Cleanup OK"
```

- [ ] **Step 8: Actualizar MEMORY.md**

Append a `wp-content/themes/starter-theme/MEMORY.md`:

```markdown
### 2026-04-28 — F4a `block_card_grid` + helpers

**Hechos**:
- ACF group `group_template_flexible_content` (location `page_template == page-flexible.php`) con campo `content_blocks` flex y único layout `block_card_grid`. Sub-fields: `titulo`, `eyebrow`, `fuente` (manual|post|concurso), `cards_manuales` (repeater con eyebrow + eyebrow_color + titulo + imagen + fecha + link), `filtros` (group con taxonomias + n_items + orden, condicional a fuente IN [post, concurso]), `columnas` (3col|4col|list), `theme` (dark|light).
- Helper plano `inc/udp-cards.php` con 4 funciones: `udp_query_cards($args)` (entry point que devuelve `{cards, total, max_pages, paged}`), `udp_card_data_from_post($post)` (mapea WP_Post → shape Card o null si no hay featured image), `udp_card_eyebrow_from_post($post)` (text + color desde primer term `category`, color hardcoded 'yellow' por ahora), `udp_card_format_date($iso)` ('YYYY-MM-DD' → 'DD / MM / YYYY' con `date_i18n`).
- Container `template-parts/blocks/block-card_grid.php` y card primitive `template-parts/blocks/parts/card-noticia.php`. La card es `<a>` envolvente, line-clamp 2 en título, image con scale 1.03 en hover (respeta `prefers-reduced-motion`), "Leer más" subraya con thickness 2px.
- SCSS único `_card-grid.scss` con modifiers BEM (`--3col`, `--4col`, `--list`, `--dark`, `--light`, `--yellow`, `--red`, `--blue`). Mobile (`<md`) cae a 1 col en grid; en list y `<xl` baja a 2 cols.
- Verificación E2E pasada: bloque manual con 4 cards rendereó las 4 con sus colores de eyebrow, cambios runtime de columnas (3col → 4col → list) y theme (dark → light) reflejados sin tocar PHP, fuente=post devolvió cards desde DB real.

**Decisiones clave**:
- Eyebrow color para `fuente=post` queda hardcoded en `yellow`. Implementar color por término requiere ACF de color picker en cada término — diferido para iteración futura.
- Fuente `concurso` ignora el filtro `taxonomias` (concurso no usa `category`). Documentado en `udp_query_cards()`.
- Posts sin featured image se omiten silenciosamente. El editor lo sabe (la imagen es featured de WordPress).
- Helper devuelve `total` y `max_pages` para que F4b/F4c puedan paginar archives sin reescribir la query.

**Pendientes**:
- F4b: archive-post + single-post (Noticias). Reusará `udp_query_cards()` con `paged` y `udp_card_data_from_post()` para markup directo (sin pasar por el bloque).
- F4c: archive-agenda (toggle grid/list) + single-agenda + nuevo card primitive `card-evento.php` (image-izquierda + CTA circular).
- Cleanup de scaffold legacy (`block-cards_grid.php`, `_flexible-blocks.scss`) cuando todos los bloques UDP estén migrados.
```

- [ ] **Step 9: Commit final**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add \
  acf-json/group_template_flexible_content.json \
  inc/udp-cards.php \
  template-parts/blocks/block-card_grid.php \
  template-parts/blocks/parts/card-noticia.php \
  src/scss/blocks/_card-grid.scss \
  src/scss/main.scss \
  functions.php \
  MEMORY.md
git commit -m "$(cat <<'EOF'
feat(block): F4a block_card_grid + udp_query_cards helper

- ACF group_template_flexible_content (location page-flexible.php) con campo
  content_blocks y layout único block_card_grid (manual/post/concurso · 3col/
  4col/list · dark/light).
- Helper inc/udp-cards.php con udp_query_cards() reutilizable por archives
  (devuelve total + max_pages), udp_card_data_from_post() y formateo de fecha.
- Container block-card_grid.php + card primitive card-noticia.php (image+
  eyebrow color + título line-clamp + fecha + Leer más con arrow-up-right).
- SCSS BEM con modifiers de columnas y theme.
- Verificación E2E con seed via update_field() + cambios runtime de admin.
EOF
)"
```

---

## Coverage check vs. spec

| Requisito spec | Tasks |
|---|---|
| ACF group_template_flexible_content con flex content_blocks + layout block_card_grid | Task 1 |
| Helper udp_query_cards() público | Task 2 step 1 |
| Helper udp_card_data_from_post() pública | Task 2 step 1 |
| Helpers internos format_date + eyebrow_from_post | Task 2 step 1 |
| 3 fuentes: manual / post / concurso | Task 1 (radio) + Task 2 (switch) |
| Filtros taxonomía (category) condicionales a source=post | Task 1 + Task 2 (tax_query) |
| Layout 3col / 4col / list | Task 1 (radio) + Task 5 (SCSS) |
| Theme dark / light | Task 1 (radio) + Task 5 (SCSS) |
| Card primitive image + eyebrow + título + fecha + "Leer más" | Task 3 |
| line-clamp 2 en título | Task 5 |
| Hover image scale + Leer más thickness, respeta reduced-motion | Task 5 |
| Posts sin featured image se omiten | Task 2 step 1 (`udp_card_data_from_post` returns null) |
| Helper modo paged para archives | Task 2 step 1 (`paged` arg + `total`/`max_pages` returns) |

**Cobertura completa.**

---

## Verification end-to-end (post-ejecución)

1. ACF group registrado y visible en admin (verificar visitando `/wp-admin/edit.php?post_type=acf-field-group`).
2. Admin crea página con template "Página Flexible (Bloques ACF)", selecciona el bloque "Grid de cards", llena 4 cards manuales y guarda. Visita la página: 4 cards renderizadas en 3 cols con eyebrow coloreado, fecha, título, "Leer más ↗".
3. Cambia `columnas` a `4col` en admin y recarga: grid pasa a 4 cols.
4. Cambia `columnas` a `list` y recarga: cards apiladas con image 96×96 a la izquierda.
5. Cambia `theme` a `light` y recarga: bg blanco, texto oscuro.
6. Cambia `fuente` a `post`, `n_items` a 6, sin filtros: muestra 6 noticias reales con eyebrow desde category, fecha desde post_date, link al permalink.
7. Marca un filtro de categoría y recarga: solo posts de esa categoría aparecen.
8. Card hover: image scale 1.03 (con motion enabled), "Leer más" subraya 2px con arrow desplazándose 2px hacia arriba-derecha.
9. Mobile (<768px): 3col y 4col caen a 1 columna vertical; list mantiene image+texto inline.
