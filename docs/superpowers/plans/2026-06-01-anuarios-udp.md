# Anuarios UDP — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Crear la página Anuarios UDP con grid de 14 cards portrait que abren PDFs en nueva pestaña, gestionada via ACF repeater, con imágenes extraídas del Figma y PDFs ya existentes en la media library de WP.

**Architecture:** Page template `templates/page-anuarios.php` orquesta: hero de Institucional reutilizado + share button + grid de cards. ACF repeater `anuarios_items` almacena los 14 anuarios. Un script de un solo uso extrae imágenes del Figma, las sube a WP y puebla el repeater con PDFs + imágenes + fechas.

**Tech Stack:** WordPress + ACF Pro + PHP 8.4 (`/Applications/MAMP/bin/php/php8.4.17/bin/php`) + SCSS + Vite 6. Figma MCP (`mcp__plugin_figma_figma__get_screenshot`). WP via `wp eval-file` con socket MAMP.

---

## Mapa de archivos

| Archivo | Acción | Responsabilidad |
|---------|--------|-----------------|
| `acf-json/group_page_anuarios.json` | Crear | Field group con repeater |
| `templates/page-anuarios.php` | Crear | Orquestador del template |
| `template-parts/anuarios/card-anuario.php` | Crear | Card portrait individual |
| `src/scss/templates/_anuarios.scss` | Crear | Grid + card styles |
| `src/scss/main.scss` | Modificar | Añadir @import (línea 100) |
| `/tmp/udp-figma-images.sh` | Crear (no commit) | Descarga imágenes del Figma |
| `/tmp/udp-upload-images.php` | Crear (no commit) | Sube imágenes descargadas a WP media |
| `/tmp/udp-populate-anuarios.php` | Crear (no commit) | Puebla el repeater ACF |

Reutilizados sin cambios:
- `template-parts/institucional/header.php`
- `template-parts/institucional/share-floating.php`

---

## Mapa de anuarios (referencia para scripts)

| Título | Figma node ID | PDF filename (contiene) |
|--------|---------------|-------------------------|
| Anuario UDP 2023 – 2024 | 3706:24390 | `anuario_udp_23-24` |
| Anuario UDP 2022 | 3706:24396 | `ANUARIO_2022` |
| Anuario UDP 2021 | 3706:24402 | `ANUARIO_2021-1` |
| Anuario UDP 2020 | 3706:24408 | `ANUARIO_2020` |
| Anuario UDP 2019 | 3706:24415 | `anuario2019` |
| Anuario UDP 2018 | 3706:24421 | `anuario2018` |
| Anuario UDP 2017 | 3706:24427 | `anuario2017` |
| Anuario UDP 2016 | 3706:24433 | `anuario_2016` |
| Anuario UDP 2015 | 3706:24440 | `anuario_udp_2016` ⚠️ confirmar |
| Anuario UDP 2014 | 3706:24446 | `ANUARIO2014` |
| Anuario UDP 2013 | 3706:24452 | `Anuario_udp_2013` |
| Anuario UDP 2012 | 3706:24458 | `anuario_udp_2012` |
| Anuario UDP 2011 | 3706:24465 | `anuario_udp_2011` |
| Anuario UDP 2010 | 3706:24471 | `anuario_udp_2010` |

---

## Task 1: ACF JSON — group_page_anuarios

**Files:**
- Create: `acf-json/group_page_anuarios.json`

- [ ] **Step 1.1: Crear el JSON**

Crear `acf-json/group_page_anuarios.json` con este contenido exacto:

```json
{
  "key": "group_page_anuarios",
  "title": "Anuarios UDP",
  "fields": [
    {
      "key": "field_anuario_items",
      "label": "Anuarios",
      "name": "anuarios_items",
      "type": "repeater",
      "required": 0,
      "min": 0,
      "max": 0,
      "layout": "block",
      "button_label": "Añadir anuario",
      "sub_fields": [
        {
          "key": "field_anuario_titulo",
          "label": "Título",
          "name": "anuario_titulo",
          "type": "text",
          "required": 1,
          "placeholder": "Anuario UDP 2023 – 2024"
        },
        {
          "key": "field_anuario_fecha",
          "label": "Fecha de publicación",
          "name": "anuario_fecha",
          "type": "date_picker",
          "required": 0,
          "display_format": "d\/m\/Y",
          "return_format": "Ymd",
          "first_day": 1
        },
        {
          "key": "field_anuario_pdf",
          "label": "PDF",
          "name": "anuario_pdf",
          "type": "file",
          "required": 1,
          "return_format": "url",
          "library": "all",
          "mime_types": "pdf"
        },
        {
          "key": "field_anuario_imagen",
          "label": "Imagen de portada",
          "name": "anuario_imagen",
          "type": "image",
          "required": 0,
          "return_format": "array",
          "preview_size": "medium",
          "library": "all"
        }
      ]
    }
  ],
  "location": [
    [
      {
        "param": "page_template",
        "operator": "==",
        "value": "templates\/page-anuarios.php"
      }
    ]
  ],
  "menu_order": 0,
  "position": "normal",
  "style": "default",
  "label_placement": "top",
  "instruction_placement": "label",
  "active": true,
  "description": "",
  "show_in_rest": false
}
```

- [ ] **Step 1.2: Sincronizar a BD (UPSERT)**

Crear `/tmp/udp-sync-anuarios-acf.php`:

```php
<?php
// Sync group_page_anuarios a BD (UPSERT por post_name)
require_once '/Applications/MAMP/htdocs/udp/cms/wp-load.php';

$json_path = get_template_directory() . '/acf-json/group_page_anuarios.json';
$group     = json_decode( file_get_contents( $json_path ), true );

// Borrar duplicados por post_name antes de importar
global $wpdb;
$existing_ids = $wpdb->get_col(
    $wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'acf-field-group'",
        'group_page_anuarios'
    )
);
foreach ( $existing_ids as $id ) {
    wp_delete_post( (int) $id, true );
    echo "Borrado grupo existente ID {$id}\n";
}

$result = acf_import_field_group( $group );
echo $result ? "Importado OK — ID {$result['ID']}\n" : "ERROR al importar\n";
```

Ejecutar:
```bash
/Applications/MAMP/bin/php/php8.4.17/bin/php /tmp/udp-sync-anuarios-acf.php
```

Salida esperada:
```
Importado OK — ID XXXXX
```

- [ ] **Step 1.3: Verificar en WP Admin**

Ir a WP Admin → Custom Fields → Grupos de campos. Debe aparecer "Anuarios UDP" con el campo `anuarios_items` (repeater).

- [ ] **Step 1.4: Commit**

```bash
git add acf-json/group_page_anuarios.json
git commit -m "feat(acf): group_page_anuarios — repeater anuarios con título, fecha, PDF e imagen"
```

---

## Task 2: Page template

**Files:**
- Create: `templates/page-anuarios.php`

- [ ] **Step 2.1: Crear el template**

```php
<?php
/**
 * Template Name: Anuarios
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

get_template_part( 'template-parts/institucional/header', null, [
    'page_title'      => get_the_title(),
    'show_breadcrumb' => true,
] );

get_template_part( 'template-parts/institucional/share-floating' );

$items = get_field( 'anuarios_items' ) ?: [];
?>

<main>
    <section class="udp-anuarios">
        <div class="container">
            <?php if ( empty( $items ) ) : ?>
                <p class="udp-anuarios__empty">No hay anuarios disponibles.</p>
            <?php else : ?>
                <div class="udp-anuarios__grid">
                    <?php foreach ( $items as $item ) :
                        get_template_part( 'template-parts/anuarios/card-anuario', null, [
                            'titulo'  => $item['anuario_titulo'] ?? '',
                            'fecha'   => $item['anuario_fecha'] ?? '',
                            'pdf_url' => $item['anuario_pdf'] ?? '',
                            'imagen'  => $item['anuario_imagen'] ?: [],
                        ] );
                    endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php get_footer(); ?>
```

- [ ] **Step 2.2: PHP lint**

```bash
/Applications/MAMP/bin/php/php8.4.17/bin/php -l templates/page-anuarios.php
```

Salida esperada: `No syntax errors detected`

---

## Task 3: Card partial

**Files:**
- Create: `template-parts/anuarios/card-anuario.php`

- [ ] **Step 3.1: Crear directorio y partial**

```php
<?php
/**
 * Card: Anuario UDP
 *
 * @package Starter_Theme
 * @var array $args { titulo, fecha, pdf_url, imagen }
 */

defined( 'ABSPATH' ) || exit;

$titulo  = $args['titulo'] ?? '';
$fecha   = $args['fecha'] ?? '';
$pdf_url = $args['pdf_url'] ?? '';
$imagen  = $args['imagen'] ?? [];

if ( empty( $titulo ) || empty( $pdf_url ) ) {
    return;
}

$fecha_display = '';
if ( $fecha ) {
    $dt = DateTime::createFromFormat( 'Ymd', $fecha );
    if ( $dt ) {
        $fecha_display = date_i18n( 'F Y', $dt->getTimestamp() );
    }
}

$has_image       = ! empty( $imagen['url'] );
$media_class     = 'udp-card-anuario__media' . ( $has_image ? '' : ' udp-card-anuario__media--placeholder' );
?>
<a class="udp-card-anuario"
   href="<?php echo esc_url( $pdf_url ); ?>"
   target="_blank"
   rel="noopener noreferrer"
   aria-label="<?php echo esc_attr( $titulo ); ?> (PDF)">

    <figure class="<?php echo esc_attr( $media_class ); ?>">
        <?php if ( $has_image ) : ?>
            <img
                src="<?php echo esc_url( $imagen['url'] ); ?>"
                alt="<?php echo esc_attr( $imagen['alt'] ?: $titulo ); ?>"
                width="<?php echo (int) ( $imagen['width'] ?? 317 ); ?>"
                height="<?php echo (int) ( $imagen['height'] ?? 391 ); ?>"
                loading="lazy"
            />
        <?php else : ?>
            <div class="udp-media-placeholder"></div>
        <?php endif; ?>
    </figure>

    <div class="udp-card-anuario__body">
        <p class="udp-card-anuario__title"><?php echo esc_html( $titulo ); ?></p>
        <?php if ( $fecha_display ) : ?>
            <time class="udp-card-anuario__date"><?php echo esc_html( $fecha_display ); ?></time>
        <?php endif; ?>
    </div>

</a>
```

- [ ] **Step 3.2: PHP lint**

```bash
/Applications/MAMP/bin/php/php8.4.17/bin/php -l template-parts/anuarios/card-anuario.php
```

Salida esperada: `No syntax errors detected`

---

## Task 4: SCSS + import

**Files:**
- Create: `src/scss/templates/_anuarios.scss`
- Modify: `src/scss/main.scss:99`

- [ ] **Step 4.1: Crear SCSS**

Crear `src/scss/templates/_anuarios.scss`:

```scss
// =============================================================================
// Template: page-anuarios
// =============================================================================

.udp-anuarios {
    background: #fff;
    padding-block: 80px;

    &__empty {
        color: $gray-medium;
        font-style: italic;
        text-align: center;
        padding-block: $space-4xl;
    }

    &__grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 50px 30px;

        @include media-breakpoint-down(lg) {
            grid-template-columns: repeat(3, 1fr);
        }

        @include media-breakpoint-down(md) {
            grid-template-columns: repeat(2, 1fr);
        }

        @include media-breakpoint-down(sm) {
            grid-template-columns: 1fr;
        }
    }
}

.udp-card-anuario {
    display: block;
    text-decoration: none;
    color: inherit;

    &__media {
        aspect-ratio: 317 / 391;
        overflow: hidden;
        border-radius: 4px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        margin-bottom: $space-sm;
        background: $gray-high;

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
            display: block;
        }

        &--placeholder {
            @include udp-media-placeholder(light);
        }
    }

    &__title {
        font-family: $font-family-display;
        font-size: 1.125rem;
        font-weight: 700;
        color: $dark-1;
        margin: 0 0 $space-2xs;
        line-height: 1.2;
    }

    &__date {
        font-size: 0.875rem;
        color: $gray-medium;
    }

    &:hover &__media img {
        transform: scale(1.04);
    }

    @media (prefers-reduced-motion: reduce) {
        &:hover &__media img {
            transform: none;
        }
    }
}
```

- [ ] **Step 4.2: Añadir import en main.scss**

En `src/scss/main.scss`, después de la línea `@import "templates/institucional";` (línea 99), añadir:

```scss
@import "templates/anuarios";
```

- [ ] **Step 4.3: Build**

```bash
npm run build
```

Salida esperada: build exitoso sin errores. `dist/css/main.css` debe contener `.udp-anuarios__grid`.

```bash
grep -c "udp-anuarios__grid" dist/css/main.css
```

Salida esperada: `1`

- [ ] **Step 4.4: Commit**

```bash
git add templates/page-anuarios.php template-parts/anuarios/card-anuario.php src/scss/templates/_anuarios.scss src/scss/main.scss
git commit -m "feat(anuarios): template + card partial + SCSS"
```

---

## Task 5: Descargar imágenes del Figma

**Files:**
- Create (no commit): `/tmp/anuarios-imgs/` (directorio local)

Los nodos son `rounded-rectangle` con image fill en el archivo Figma `4QlgGMlzNR9Ye344bAFuye`.

- [ ] **Step 5.1: Crear directorio de trabajo**

```bash
mkdir -p /tmp/anuarios-imgs
```

- [ ] **Step 5.2: Descargar las 14 imágenes**

Para cada nodo de la tabla de abajo, usar la herramienta `mcp__plugin_figma_figma__get_screenshot` con `fileKey=4QlgGMlzNR9Ye344bAFuye` y el `nodeId` indicado. La herramienta devuelve una URL de descarga. Inmediatamente ejecutar curl para descargar el PNG al path indicado:

| Nodo Figma | Archivo destino |
|------------|----------------|
| `3706:24390` | `/tmp/anuarios-imgs/2023-2024.png` |
| `3706:24396` | `/tmp/anuarios-imgs/2022.png` |
| `3706:24402` | `/tmp/anuarios-imgs/2021.png` |
| `3706:24408` | `/tmp/anuarios-imgs/2020.png` |
| `3706:24415` | `/tmp/anuarios-imgs/2019.png` |
| `3706:24421` | `/tmp/anuarios-imgs/2018.png` |
| `3706:24427` | `/tmp/anuarios-imgs/2017.png` |
| `3706:24433` | `/tmp/anuarios-imgs/2016.png` |
| `3706:24440` | `/tmp/anuarios-imgs/2015.png` |
| `3706:24446` | `/tmp/anuarios-imgs/2014.png` |
| `3706:24452` | `/tmp/anuarios-imgs/2013.png` |
| `3706:24458` | `/tmp/anuarios-imgs/2012.png` |
| `3706:24465` | `/tmp/anuarios-imgs/2011.png` |
| `3706:24471` | `/tmp/anuarios-imgs/2010.png` |

Para cada imagen:
```bash
# Ejemplo para 2023-2024 (repetir para cada URL obtenida del tool):
curl -o /tmp/anuarios-imgs/2023-2024.png "{URL_devuelta_por_figma_tool}"
```

- [ ] **Step 5.3: Verificar descargas**

```bash
ls -lh /tmp/anuarios-imgs/
```

Salida esperada: 14 archivos `.png`, todos > 10 KB.

- [ ] **Step 5.4: Subir imágenes a WP media**

Crear `/tmp/udp-upload-images.php`:

```php
<?php
/**
 * Sube las 14 imágenes de anuarios a WP media library.
 * Idempotente: si ya existe un attachment con el mismo título, lo salta.
 */
require_once '/Applications/MAMP/htdocs/udp/cms/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$images = [
    '2023-2024' => 'Portada Anuario UDP 2023-2024',
    '2022'      => 'Portada Anuario UDP 2022',
    '2021'      => 'Portada Anuario UDP 2021',
    '2020'      => 'Portada Anuario UDP 2020',
    '2019'      => 'Portada Anuario UDP 2019',
    '2018'      => 'Portada Anuario UDP 2018',
    '2017'      => 'Portada Anuario UDP 2017',
    '2016'      => 'Portada Anuario UDP 2016',
    '2015'      => 'Portada Anuario UDP 2015',
    '2014'      => 'Portada Anuario UDP 2014',
    '2013'      => 'Portada Anuario UDP 2013',
    '2012'      => 'Portada Anuario UDP 2012',
    '2011'      => 'Portada Anuario UDP 2011',
    '2010'      => 'Portada Anuario UDP 2010',
];

foreach ( $images as $key => $title ) {
    $source_path = "/tmp/anuarios-imgs/{$key}.png";

    if ( ! file_exists( $source_path ) ) {
        echo "SKIP {$key}: archivo no encontrado en {$source_path}\n";
        continue;
    }

    // Comprobar si ya existe un attachment con ese título
    $existing = get_posts( [
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'title'          => $title,
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ] );
    if ( $existing ) {
        echo "OK (ya existía) {$key} → ID {$existing[0]}\n";
        continue;
    }

    // Copiar a uploads tmp para que WP lo procese
    $tmp_path = sys_get_temp_dir() . "/anuario_{$key}.png";
    copy( $source_path, $tmp_path );

    $file_array = [
        'name'     => "portada-anuario-udp-{$key}.png",
        'tmp_name' => $tmp_path,
        'type'     => 'image/png',
        'error'    => 0,
        'size'     => filesize( $tmp_path ),
    ];

    $id = media_handle_sideload( $file_array, 0, $title );

    if ( is_wp_error( $id ) ) {
        echo "ERROR {$key}: " . $id->get_error_message() . "\n";
    } else {
        echo "SUBIDO {$key} → attachment ID {$id}\n";
    }
}
```

Ejecutar:
```bash
/Applications/MAMP/bin/php/php8.4.17/bin/php /tmp/udp-upload-images.php
```

Salida esperada: 14 líneas `SUBIDO XXXX → attachment ID YYYY` (o `OK (ya existía)` si se re-ejecuta).

---

## Task 6: Script autopoblado — PDFs + repeater

**Files:**
- Create (no commit): `/tmp/udp-populate-anuarios.php`

- [ ] **Step 6.1: Identificar page_id de Anuarios UDP**

```bash
/Applications/MAMP/bin/php/php8.4.17/bin/php -r "
require '/Applications/MAMP/htdocs/udp/cms/wp-load.php';
\$page = get_page_by_title('Anuarios UDP');
if (\$page) echo 'ID: ' . \$page->ID . PHP_EOL;
else echo 'Página no encontrada' . PHP_EOL;
"
```

Si devuelve "Página no encontrada": ir a WP Admin → Páginas → Añadir nueva → título "Anuarios UDP", asignar template "Anuarios" desde Page Attributes, publicar. Anotar el ID.

- [ ] **Step 6.2: Crear y ejecutar el script**

Crear `/tmp/udp-populate-anuarios.php` reemplazando `PAGE_ID` con el ID obtenido en el paso anterior:

```php
<?php
/**
 * Puebla el repeater anuarios_items en la página Anuarios UDP.
 * Idempotente: borra el campo antes de escribir.
 *
 * Uso: php /tmp/udp-populate-anuarios.php [--dry]
 */
require_once '/Applications/MAMP/htdocs/udp/cms/wp-load.php';

$dry_run = in_array( '--dry', $argv ?? [], true );
$page_id = PAGE_ID; // ← reemplazar con el ID real

// Mapa: slug de año → [título, PDF filename substring, imagen title]
$anuarios_map = [
    '2023-2024' => [
        'titulo'      => 'Anuario UDP 2023 – 2024',
        'pdf_pattern' => 'anuario_udp_23-24',
        'img_title'   => 'Portada Anuario UDP 2023-2024',
    ],
    '2022' => [
        'titulo'      => 'Anuario UDP 2022',
        'pdf_pattern' => 'ANUARIO_2022',
        'img_title'   => 'Portada Anuario UDP 2022',
    ],
    '2021' => [
        'titulo'      => 'Anuario UDP 2021',
        'pdf_pattern' => 'ANUARIO_2021-1',
        'img_title'   => 'Portada Anuario UDP 2021',
    ],
    '2020' => [
        'titulo'      => 'Anuario UDP 2020',
        'pdf_pattern' => 'ANUARIO_2020',
        'img_title'   => 'Portada Anuario UDP 2020',
    ],
    '2019' => [
        'titulo'      => 'Anuario UDP 2019',
        'pdf_pattern' => 'anuario2019',
        'img_title'   => 'Portada Anuario UDP 2019',
    ],
    '2018' => [
        'titulo'      => 'Anuario UDP 2018',
        'pdf_pattern' => 'anuario2018',
        'img_title'   => 'Portada Anuario UDP 2018',
    ],
    '2017' => [
        'titulo'      => 'Anuario UDP 2017',
        'pdf_pattern' => 'anuario2017',
        'img_title'   => 'Portada Anuario UDP 2017',
    ],
    '2016' => [
        'titulo'      => 'Anuario UDP 2016',
        'pdf_pattern' => 'anuario_2016',
        'img_title'   => 'Portada Anuario UDP 2016',
    ],
    '2015' => [
        'titulo'      => 'Anuario UDP 2015',
        'pdf_pattern' => 'anuario_udp_2016', // ⚠️ confirmar filename real
        'img_title'   => 'Portada Anuario UDP 2015',
    ],
    '2014' => [
        'titulo'      => 'Anuario UDP 2014',
        'pdf_pattern' => 'ANUARIO2014',
        'img_title'   => 'Portada Anuario UDP 2014',
    ],
    '2013' => [
        'titulo'      => 'Anuario UDP 2013',
        'pdf_pattern' => 'Anuario_udp_2013',
        'img_title'   => 'Portada Anuario UDP 2013',
    ],
    '2012' => [
        'titulo'      => 'Anuario UDP 2012',
        'pdf_pattern' => 'anuario_udp_2012',
        'img_title'   => 'Portada Anuario UDP 2012',
    ],
    '2011' => [
        'titulo'      => 'Anuario UDP 2011',
        'pdf_pattern' => 'anuario_udp_2011',
        'img_title'   => 'Portada Anuario UDP 2011',
    ],
    '2010' => [
        'titulo'      => 'Anuario UDP 2010',
        'pdf_pattern' => 'anuario_udp_2010',
        'img_title'   => 'Portada Anuario UDP 2010',
    ],
];

// Helper: busca un attachment PDF por substring del filename en guid.
// Se usa guid (URL del archivo) porque preserva el nombre original con mayúsculas/underscores.
// post_name sanitiza a minúsculas+guiones, lo que rompería patrones como "ANUARIO_2022".
function find_pdf( string $pattern ): ?array {
    global $wpdb;
    $row = $wpdb->get_row( $wpdb->prepare(
        "SELECT ID, post_title, post_date, guid
         FROM {$wpdb->posts}
         WHERE post_type = 'attachment'
           AND post_mime_type = 'application/pdf'
           AND guid LIKE %s
         ORDER BY ID DESC LIMIT 1",
        '%' . $wpdb->esc_like( $pattern ) . '%'
    ) );
    return $row ? (array) $row : null;
}

// Helper: busca imagen de portada por título exacto
function find_image( string $title ): ?array {
    $posts = get_posts( [
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'title'          => $title,
        'posts_per_page' => 1,
    ] );
    if ( ! $posts ) {
        return null;
    }
    $p  = $posts[0];
    $id = $p->ID;
    return [
        'ID'     => $id,
        'id'     => $id,
        'url'    => wp_get_attachment_url( $id ),
        'alt'    => get_post_meta( $id, '_wp_attachment_image_alt', true ) ?: $title,
        'width'  => 317,
        'height' => 391,
    ];
}

$items = [];

foreach ( $anuarios_map as $year_key => $data ) {
    $pdf = find_pdf( $data['pdf_pattern'] );
    $img = find_image( $data['img_title'] );

    if ( ! $pdf ) {
        echo "⚠️  PDF no encontrado para {$year_key} (pattern: {$data['pdf_pattern']})\n";
        $pdf_url = '';
        $fecha   = '';
    } else {
        $pdf_url = wp_get_attachment_url( (int) $pdf['ID'] );
        $fecha   = gmdate( 'Ymd', strtotime( $pdf['post_date'] ) );
        echo "✓  PDF {$year_key} → ID {$pdf['ID']} ({$pdf['post_title']})\n";
    }

    if ( ! $img ) {
        echo "⚠️  Imagen no encontrada para {$year_key}\n";
    } else {
        echo "✓  Imagen {$year_key} → ID {$img['ID']}\n";
    }

    $items[] = [
        'anuario_titulo' => $data['titulo'],
        'anuario_fecha'  => $fecha,
        'anuario_pdf'    => $pdf_url,
        'anuario_imagen' => $img ?: false,
    ];
}

echo "\n--- Resumen: " . count( $items ) . " ítems construidos ---\n";

if ( $dry_run ) {
    echo "[DRY RUN] No se escriben datos.\n";
    exit(0);
}

// Borrar el campo antes de escribir (idempotente)
delete_field( 'anuarios_items', $page_id );

$ok = update_field( 'anuarios_items', $items, $page_id );
echo $ok ? "✅ Repeater escrito en página {$page_id}\n" : "❌ Error al escribir repeater\n";
```

- [ ] **Step 6.3: Dry run**

```bash
/Applications/MAMP/bin/php/php8.4.17/bin/php /tmp/udp-populate-anuarios.php --dry
```

Revisar output: verificar que los 14 PDFs se encuentran y que las imágenes también. Si algún PDF aparece con `⚠️`, ajustar el `pdf_pattern` correspondiente en el mapa antes de continuar.

- [ ] **Step 6.4: Ejecutar para poblar**

```bash
/Applications/MAMP/bin/php/php8.4.17/bin/php /tmp/udp-populate-anuarios.php
```

Salida esperada al final: `✅ Repeater escrito en página XXXXX`

---

## Task 7: Asignar template + verificación E2E

- [ ] **Step 7.1: Asignar template a la página**

Ir a WP Admin → Páginas → "Anuarios UDP" → Page Attributes → Template → seleccionar **"Anuarios"** → Actualizar.

- [ ] **Step 7.2: Verificar markup**

```bash
PAGE_URL="http://localhost:8888/udp/conoce-la-udp/anuarios-udp/"  # ajustar slug real
curl -s "$PAGE_URL?theme=new" | grep -c "udp-card-anuario"
```

Salida esperada: `14` (una por anuario)

```bash
curl -s "$PAGE_URL?theme=new" | grep -c "udp-anuarios__grid"
```

Salida esperada: `1`

```bash
curl -s "$PAGE_URL?theme=new" | grep "udp-inst-hero"
```

Salida esperada: línea con `udp-inst-hero` (hero de institucional reutilizado)

```bash
curl -s "$PAGE_URL?theme=new" | grep "target=\"_blank\""  | wc -l
```

Salida esperada: ≥14 (los 14 links a PDFs en nueva pestaña)

- [ ] **Step 7.3: Verificar placeholder**

Si algún anuario no tiene imagen, la card debe mostrar el placeholder en lugar de un `<img>` roto:

```bash
curl -s "$PAGE_URL?theme=new" | grep "media--placeholder" | wc -l
```

Salida esperada: número de anuarios sin imagen (0 si todas las imágenes se subieron correctamente).

- [ ] **Step 7.4: Actualizar MEMORY.md**

Añadir entrada en `MEMORY.md` con fecha, qué se hizo y estado resultante.

- [ ] **Step 7.5: Commit final**

```bash
git add MEMORY.md
git commit -m "feat(anuarios): página completada — 14 cards, PDFs, imágenes Figma"
```
