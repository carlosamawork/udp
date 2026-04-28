# F3 — Section Landing Page Template — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Crear un page template asignable `Section Landing` que el admin pueda elegir desde el dropdown del editor, con cabecera de página (breadcrumb dinámico + título + descripción wysiwyg) y un grid de cards en dos modos (`swiper` carrusel horizontal o `grid` 5 columnas con scroll-snap mobile). Cards en gris (`$dark-2` #232323) con hover lila (`$brand-blue` #4539F2), sin imagen. Cuando la página tiene `post_parent`, se prepende automáticamente una card "Volver a {nombre del padre}" con icono back.

**Architecture:** 1 page template + 1 grupo ACF + 1 partial reutilizable de breadcrumb. El header NO tiene imagen de fondo (sólo dark surface y el separador inferior). Card single = componente único reutilizable que renderiza variante `default` (link interno/externo, icono arrow-up-right) o `back` (volver al padre, icono undo-2 generado en el container). Container condicional `if cards_display == swiper` (Swiper.js) `else` grid CSS. Mobile grid usa `scroll-snap` nativo (sin JS).

**Tech Stack:** WordPress page template, ACF Pro (link field, radio, repeater, wysiwyg), Bootstrap 5, Swiper.js 11+, SCSS, scroll-snap CSS.

**Reference**:
- Spec: `wp-content/themes/starter-theme/docs/superpowers/specs/2026-04-27-migracion-udp-portable-a-starter-theme-design.md`
- Figma swiper variant (Pregrado): nodeId `4383:18979` — header `Inicio › Pregrado` + título 64px + descripción + separador, cards 285×365 gap 33 con peek 150px
- Figma grid variant (Servicios): nodeId `4401:22378` — mismo header, grid 5 cols cards 248×318 gap 30
- Figma con back-card y breadcrumb multinivel (Conoce la UDP): nodeId `3706:21530` — breadcrumb `Inicio › Universidad › Conoce la UDP`, card "Volver a Universidad" con icono `undo-2`
- Header común: padding 40px horizontal (16px en mobile), breadcrumb 14px Work Sans Medium con chevron-right 12px, título Arizona Flare Medium 64px white (40px mobile), descripción 16px Work Sans Regular `$white-70` max-width 696px, separador horizontal 1px `$gray-medium` full-width al final
- Card común a ambos modos: bg `$dark-2`, padding 18px, eyebrow opcional Necto Mono uppercase 12px `$white-70`, título Work Sans Medium 20px, botón CTA circular 48×48 top-right (border `$gray-medium`, arrow-up-right o undo-2 según variante), hover invierte bg → `$brand-blue` (#4539F2 ≈ lila) y CTA bg → white

---

## Inventario de archivos

**A crear:**
- `wp-content/themes/starter-theme/templates/page-section-landing.php` — page template (Template Name header)
- `wp-content/themes/starter-theme/template-parts/sections/section-landing-header.php` — page header (breadcrumb + título + descripción)
- `wp-content/themes/starter-theme/template-parts/sections/breadcrumb.php` — breadcrumb partial reutilizable (jerarquía WP)
- `wp-content/themes/starter-theme/template-parts/sections/section-landing-cards.php` — container condicional + back-to-parent prepend
- `wp-content/themes/starter-theme/template-parts/sections/section-landing-card.php` — card único (variantes default | back)
- `wp-content/themes/starter-theme/src/scss/blocks/_section-landing.scss` — header + breadcrumb + cards (un solo archivo)
- `wp-content/themes/starter-theme/src/js/modules/section-landing-swiper.js`
- `wp-content/themes/starter-theme/acf-json/group_template_section_landing.json`

**A modificar:**
- `wp-content/themes/starter-theme/package.json` — añadir dependencia `swiper`
- `wp-content/themes/starter-theme/src/js/main.js` — importar el nuevo módulo
- `wp-content/themes/starter-theme/src/scss/main.scss` — `@import "blocks/section-landing";`

**A NO tocar:** mu-plugins, otros templates, `_variables.scss`, `_mixins.scss`, `header.php`, `footer.php`.

---

## ACF Schema — `group_template_section_landing`

Location: `page_template == templates/page-section-landing.php`

```
PAGE_HEADER (group)
├── eyebrow         (text, opcional)        ← rara vez se usa (el breadcrumb ya da contexto)
├── titulo          (text, requerido)        ← "Pregrado". Si vacío → fallback al título WP.
└── descripcion     (wysiwyg, opcional)     ← párrafo intro con formato simple (negrita, links)

CARDS_DISPLAY (radio, requerido, default 'grid')
├── grid     ← 5 columnas desktop, scroll-snap mobile
└── swiper   ← carrusel horizontal con peek

CARDS (repeater, min 1, max ilimitado, layout block)
├── eyebrow         (text, opcional)        ← "Charlas", "Postgrado"
├── titulo          (text, requerido)
├── descripcion     (textarea, opcional)
└── link            (link, requerido)        ← ACF link nativo (url + title + target)
```

Cambios respecto a iteración anterior (descartados):
- `hero` → `page_header` (no es un hero visual, es una cabecera con breadcrumb + título + descripción).
- `hero.imagen_fondo` ELIMINADO. El header NO usa imagen de fondo en este template.
- `hero.bajada` (textarea) → `page_header.descripcion` (wysiwyg).
- `cards.imagen` ELIMINADO. Las cards son siempre gris→lila sin imagen.
- Breadcrumb → NO es un campo ACF, se construye automáticamente desde la jerarquía WP (`wp_get_post_parent_id`).
- Back-to-parent card → NO es un campo ACF, se prepende automáticamente al array de cards si `wp_get_post_parent_id() > 0`.

Field keys: prefijo `field_template_section_landing_*` para todos.

---

## Task 1: Crear ACF JSON + sync

**Files:**
- Create: `wp-content/themes/starter-theme/acf-json/group_template_section_landing.json`

- [x] **Step 1: Escribir JSON con la estructura ACF completa**

```json
{
    "key": "group_template_section_landing",
    "title": "Template — Section Landing",
    "fields": [
        {
            "key": "field_template_section_landing_header",
            "label": "Cabecera de página",
            "name": "page_header",
            "type": "group",
            "layout": "block",
            "sub_fields": [
                { "key": "field_template_section_landing_header_eyebrow", "label": "Eyebrow (etiqueta superior)", "name": "eyebrow", "type": "text", "instructions": "Opcional. Suele dejarse vacío — el breadcrumb ya da contexto." },
                { "key": "field_template_section_landing_header_titulo", "label": "Título", "name": "titulo", "type": "text", "required": 1, "instructions": "Si está vacío, se usa el título de la página WP." },
                { "key": "field_template_section_landing_header_descripcion", "label": "Descripción", "name": "descripcion", "type": "wysiwyg", "tabs": "visual", "toolbar": "basic", "media_upload": 0, "delay": 0, "instructions": "Texto introductorio con formato simple (negrita, links). Ancho máx ~696px en desktop." }
            ]
        },
        {
            "key": "field_template_section_landing_cards_display",
            "label": "Modo de visualización de cards",
            "name": "cards_display",
            "type": "radio",
            "required": 1,
            "choices": { "grid": "Grid (5 columnas, scroll mobile)", "swiper": "Swiper (carrusel horizontal con peek)" },
            "default_value": "grid",
            "layout": "horizontal",
            "return_format": "value"
        },
        {
            "key": "field_template_section_landing_cards",
            "label": "Cards",
            "name": "cards",
            "type": "repeater",
            "min": 1,
            "layout": "block",
            "button_label": "Agregar card",
            "instructions": "Si la página tiene una página padre, se añade automáticamente una card 'Volver a [padre]' como primera card. NO la incluyas aquí.",
            "sub_fields": [
                { "key": "field_template_section_landing_card_eyebrow", "label": "Eyebrow (etiqueta)", "name": "eyebrow", "type": "text", "instructions": "Opcional. Ej. 'Charlas', 'Postgrado'." },
                { "key": "field_template_section_landing_card_titulo", "label": "Título", "name": "titulo", "type": "text", "required": 1 },
                { "key": "field_template_section_landing_card_descripcion", "label": "Descripción", "name": "descripcion", "type": "textarea", "rows": 2 },
                { "key": "field_template_section_landing_card_link", "label": "Link", "name": "link", "type": "link", "required": 1, "return_format": "array", "instructions": "Página interna o URL externa. Si es externa, marcar 'Open in a new window'." }
            ]
        }
    ],
    "location": [
        [{ "param": "page_template", "operator": "==", "value": "templates/page-section-landing.php" }]
    ],
    "menu_order": 0,
    "position": "normal",
    "style": "default",
    "label_placement": "top",
    "instruction_placement": "label",
    "hide_on_screen": "",
    "active": true,
    "description": "Cabecera (breadcrumb auto + título + descripción wysiwyg) + grid de cards en modo grid o swiper, con back-to-parent card auto en subpáginas."
}
```

- [x] **Step 2: Validar JSON**

```bash
jq empty /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_template_section_landing.json && echo "JSON válido"
```

- [x] **Step 3: Sync con UPSERT (no duplicar)**

Crear `/tmp/acf-sync-section-landing.php`:

```php
<?php
$json_path = '/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_template_section_landing.json';
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
/Applications/MAMP/bin/php/php8.4.1/bin/php /tmp/wp-cli.phar eval-file /tmp/acf-sync-section-landing.php --path=/Applications/MAMP/htdocs/udp/cms 2>&1 | tail -3
```

Expected: `CREATE new` + `Success: id=NNNNN`. NO commit todavía (commits agrupados al final).

---

## Task 2: Page template scaffold + page header

**Files:**
- Create: `wp-content/themes/starter-theme/templates/page-section-landing.php`
- Create: `wp-content/themes/starter-theme/template-parts/sections/section-landing-header.php`

- [x] **Step 1: Crear directorio si no existe**

```bash
mkdir -p /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/sections
```

- [x] **Step 2: Crear el page template con su Template Name**

Create `wp-content/themes/starter-theme/templates/page-section-landing.php`:

```php
<?php
/**
 * Template Name: Section Landing
 *
 * Cabecera (breadcrumb auto + título + descripción wysiwyg) + grid o swiper de cards.
 * Sin imagen de fondo en el header. Cards en gris (#232323), hover lila (#4539F2).
 * Si la página tiene un padre, se prepende una card "Volver a {padre}" automática.
 *
 * @package Starter_Theme
 */

get_header();

$page_header   = function_exists( 'get_field' ) ? get_field( 'page_header' ) : array();
$cards         = function_exists( 'get_field' ) ? get_field( 'cards' ) : array();
$cards_display = function_exists( 'get_field' ) ? get_field( 'cards_display' ) : 'grid';
$page_id       = get_the_ID();
$parent_id     = (int) wp_get_post_parent_id( $page_id );
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-section-landing' ); ?>>

	<?php
	get_template_part(
		'template-parts/sections/section-landing-header',
		null,
		array(
			'header'     => $page_header,
			'page_id'    => $page_id,
			'page_title' => get_the_title(),
		)
	);

	get_template_part(
		'template-parts/sections/section-landing-cards',
		null,
		array(
			'cards'     => $cards,
			'display'   => $cards_display ?: 'grid',
			'parent_id' => $parent_id,
		)
	);
	?>

</article>

<?php
get_footer();
```

- [x] **Step 3: Crear el page header template-part**

Create `wp-content/themes/starter-theme/template-parts/sections/section-landing-header.php`:

```php
<?php
/**
 * Section Landing > Page Header
 *
 * Breadcrumb dinámico + (opcional) eyebrow + título + descripción wysiwyg + separador inferior.
 * NO usa imagen de fondo en este template.
 *
 * @package Starter_Theme
 *
 * @var array $args ['header' => array, 'page_id' => int, 'page_title' => string]
 */
$header      = isset( $args['header'] ) && is_array( $args['header'] ) ? $args['header'] : array();
$eyebrow     = isset( $header['eyebrow'] ) ? $header['eyebrow'] : '';
$titulo      = isset( $header['titulo'] ) && ! empty( $header['titulo'] ) ? $header['titulo'] : ( $args['page_title'] ?? '' );
$descripcion = isset( $header['descripcion'] ) ? $header['descripcion'] : '';
$page_id     = isset( $args['page_id'] ) ? (int) $args['page_id'] : 0;
?>
<section class="udp-section-header">
	<div class="udp-section-header__inner">

		<?php
		get_template_part(
			'template-parts/sections/breadcrumb',
			null,
			array( 'page_id' => $page_id )
		);
		?>

		<div class="udp-section-header__content">
			<?php if ( ! empty( $eyebrow ) ) : ?>
				<p class="udp-section-header__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $titulo ) ) : ?>
				<h1 class="udp-section-header__title"><?php echo esc_html( $titulo ); ?></h1>
			<?php endif; ?>

			<?php if ( ! empty( $descripcion ) ) : ?>
				<div class="udp-section-header__desc">
					<?php echo wp_kses_post( $descripcion ); ?>
				</div>
			<?php endif; ?>
		</div>

	</div>
	<hr class="udp-section-header__separator" aria-hidden="true" />
</section>
```

- [x] **Step 4: Validar PHP syntax**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/templates/page-section-landing.php
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/sections/section-landing-header.php
```

Expected: 2× `No syntax errors detected`.

---

## Task 2b: Breadcrumb partial (reutilizable)

**Files:**
- Create: `wp-content/themes/starter-theme/template-parts/sections/breadcrumb.php`

Partial reutilizable. Recorre `wp_get_post_parent_id` desde la página actual hasta la raíz, construye el trail y prepende `Inicio`. El último item NO es link (current). Acepta argumentos opcionales para usarlo desde otros templates.

- [x] **Step 1: Crear el partial**

Create `wp-content/themes/starter-theme/template-parts/sections/breadcrumb.php`:

```php
<?php
/**
 * Breadcrumb dinámico
 *
 * Construye trail desde Inicio hasta la página actual recorriendo post_parent.
 * Markup: nav > ol > li (link o current). Separador chevron-right inline SVG.
 * El último item se renderiza como <span aria-current="page"> (no link).
 *
 * @package Starter_Theme
 *
 * @var array $args {
 *     @type int    $page_id    ID de la página. Default get_the_ID().
 *     @type string $home_label Etiqueta del item raíz. Default 'Inicio'.
 * }
 */
$page_id    = isset( $args['page_id'] ) && (int) $args['page_id'] > 0 ? (int) $args['page_id'] : (int) get_the_ID();
$home_label = isset( $args['home_label'] ) && $args['home_label'] ? $args['home_label'] : __( 'Inicio', 'starter-theme' );

if ( ! $page_id ) {
	return;
}

// Cadena de ancestros (current → root).
$trail   = array();
$current = $page_id;
$safety  = 10; // evita loop infinito si hay ciclo en post_parent
while ( $current && $safety-- > 0 ) {
	$trail[] = array(
		'id'    => $current,
		'title' => get_the_title( $current ),
		'url'   => get_permalink( $current ),
	);
	$current = (int) wp_get_post_parent_id( $current );
}
$trail = array_reverse( $trail ); // root primero

// Prepend Home.
array_unshift(
	$trail,
	array(
		'id'    => 0,
		'title' => $home_label,
		'url'   => home_url( '/' ),
	)
);

$last_index = count( $trail ) - 1;
?>
<nav class="udp-breadcrumb" aria-label="<?php esc_attr_e( 'Migas de pan', 'starter-theme' ); ?>">
	<ol class="udp-breadcrumb__list">
		<?php foreach ( $trail as $i => $item ) : ?>
			<li class="udp-breadcrumb__item">
				<?php if ( $i === $last_index ) : ?>
					<span class="udp-breadcrumb__current" aria-current="page"><?php echo esc_html( $item['title'] ); ?></span>
				<?php else : ?>
					<a class="udp-breadcrumb__link" href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['title'] ); ?></a>
					<span class="udp-breadcrumb__sep" aria-hidden="true">
						<svg width="12" height="12" viewBox="0 0 12 12" fill="none">
							<path d="M4.5 3l3 3-3 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
</nav>
```

- [x] **Step 2: Validar PHP**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/sections/breadcrumb.php
```

Expected: `No syntax errors detected`.

---

## Task 3: Cards container + card single (sin imagen, con back-to-parent auto)

**Files:**
- Create: `wp-content/themes/starter-theme/template-parts/sections/section-landing-cards.php`
- Create: `wp-content/themes/starter-theme/template-parts/sections/section-landing-card.php`

- [x] **Step 1: Container con back-to-parent prepend**

Cuando `parent_id > 0`, se inyecta una card sintética al inicio del array con `variant = 'back'`. El card single la renderiza con icono `undo-2` y solo título.

Create `wp-content/themes/starter-theme/template-parts/sections/section-landing-cards.php`:

```php
<?php
/**
 * Section Landing > Cards container (grid o swiper, con back-to-parent auto)
 *
 * Si la página tiene un padre, prepende una card "Volver a {padre}" sintética
 * antes del array de cards manuales.
 *
 * @package Starter_Theme
 *
 * @var array $args ['cards' => array, 'display' => string ('grid'|'swiper'), 'parent_id' => int]
 */
$cards     = isset( $args['cards'] ) && is_array( $args['cards'] ) ? $args['cards'] : array();
$display   = isset( $args['display'] ) && in_array( $args['display'], array( 'grid', 'swiper' ), true ) ? $args['display'] : 'grid';
$parent_id = isset( $args['parent_id'] ) ? (int) $args['parent_id'] : 0;

// Back-to-parent card sintética (solo si la página tiene padre).
$back_card = null;
if ( $parent_id > 0 ) {
	$parent_title = get_the_title( $parent_id );
	$back_card    = array(
		'variant' => 'back',
		'titulo'  => sprintf( __( 'Volver a %s', 'starter-theme' ), $parent_title ),
		'link'    => array(
			'url'    => get_permalink( $parent_id ),
			'title'  => $parent_title,
			'target' => '',
		),
	);
}

$all_cards = $back_card ? array_merge( array( $back_card ), $cards ) : $cards;

if ( empty( $all_cards ) ) {
	return;
}

$container_class = 'udp-section-cards udp-section-cards--' . $display;
?>
<section class="<?php echo esc_attr( $container_class ); ?>"<?php echo $display === 'swiper' ? ' data-udp-swiper' : ''; ?>>
	<?php if ( $display === 'swiper' ) : ?>
		<div class="udp-section-cards__viewport swiper">
			<ul class="udp-section-cards__list swiper-wrapper">
				<?php foreach ( $all_cards as $index => $card ) : ?>
					<li class="udp-section-cards__item swiper-slide">
						<?php
						get_template_part(
							'template-parts/sections/section-landing-card',
							null,
							array( 'card' => $card, 'index' => $index )
						);
						?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php else : ?>
		<ul class="udp-section-cards__list">
			<?php foreach ( $all_cards as $index => $card ) : ?>
				<li class="udp-section-cards__item">
					<?php
					get_template_part(
						'template-parts/sections/section-landing-card',
						null,
						array( 'card' => $card, 'index' => $index )
					);
					?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>
```

- [x] **Step 2: Card single (sin imagen, variantes default | back)**

Card único reutilizable. Variantes:
- `default` → eyebrow + título + descripción + icono `arrow-up-right` (idéntico para link interno o externo, igual al Figma).
- `back` → solo título "Volver a {padre}" + icono `undo-2`.

Create `wp-content/themes/starter-theme/template-parts/sections/section-landing-card.php`:

```php
<?php
/**
 * Section Landing > Card individual (variantes default | back)
 *
 * Sin imagen — fondo gris ($dark-2) que pasa a lila ($brand-blue) en hover.
 *
 * @package Starter_Theme
 *
 * @var array $args ['card' => array, 'index' => int]
 */
$card        = isset( $args['card'] ) && is_array( $args['card'] ) ? $args['card'] : array();
$variant     = isset( $card['variant'] ) && $card['variant'] === 'back' ? 'back' : 'default';
$eyebrow     = isset( $card['eyebrow'] ) ? $card['eyebrow'] : '';
$titulo      = isset( $card['titulo'] ) ? $card['titulo'] : '';
$descripcion = isset( $card['descripcion'] ) ? $card['descripcion'] : '';
$link        = isset( $card['link'] ) && is_array( $card['link'] ) ? $card['link'] : array();

$href   = isset( $link['url'] ) ? $link['url'] : '';
$target = isset( $link['target'] ) ? $link['target'] : '';
$rel    = $target === '_blank' ? 'noopener noreferrer' : '';

if ( empty( $href ) || empty( $titulo ) ) {
	return;
}

$class = 'udp-section-card udp-section-card--' . $variant;
?>
<a
	href="<?php echo esc_url( $href ); ?>"
	class="<?php echo esc_attr( $class ); ?>"
	<?php if ( $target ) : ?>target="<?php echo esc_attr( $target ); ?>"<?php endif; ?>
	<?php if ( $rel ) : ?>rel="<?php echo esc_attr( $rel ); ?>"<?php endif; ?>
>
	<span class="udp-section-card__cta" aria-hidden="true">
		<?php if ( $variant === 'back' ) : ?>
			<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
				<path d="M9 4 5 8l4 4M5 8h6.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
			</svg>
		<?php else : ?>
			<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
				<path d="M5 3h8v8M13 3 3 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
			</svg>
		<?php endif; ?>
	</span>

	<div class="udp-section-card__content">
		<?php if ( $variant !== 'back' && ! empty( $eyebrow ) ) : ?>
			<p class="udp-section-card__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
		<?php endif; ?>

		<h3 class="udp-section-card__title"><?php echo esc_html( $titulo ); ?></h3>

		<?php if ( $variant !== 'back' && ! empty( $descripcion ) ) : ?>
			<p class="udp-section-card__desc"><?php echo esc_html( $descripcion ); ?></p>
		<?php endif; ?>
	</div>
</a>
```

- [x] **Step 3: Validar PHP syntax**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/sections/section-landing-cards.php
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/sections/section-landing-card.php
```

---

## Task 4: SCSS — page header + breadcrumb + cards (gris→lila, sin imagen)

**Files:**
- Create: `wp-content/themes/starter-theme/src/scss/blocks/_section-landing.scss`
- Modify: `wp-content/themes/starter-theme/src/scss/main.scss`

- [x] **Step 1: Crear el parcial SCSS**

Create `wp-content/themes/starter-theme/src/scss/blocks/_section-landing.scss`:

```scss
// ==========================================================================
// SECTION LANDING — page template
// Page header (breadcrumb + título + descripción) + cards grid o swiper.
// Spec Figma:
//   - 4383:18979 (swiper Pregrado)
//   - 4401:22378 (grid Servicios)
//   - 3706:21530 (Conoce la UDP — breadcrumb multinivel + back-card)
// Card 248×318 (grid) o 285×365 (swiper); bg $dark-2; padding 18px;
// hover: bg $brand-blue (#4539F2 ≈ lila).
// ==========================================================================

// --------------------------------------------------------------------------
// PAGE HEADER (sin bg image, separador inferior, breadcrumb arriba)
// --------------------------------------------------------------------------
.udp-section-header {
    background-color: $dark-1;
    color: $white;
    padding-top: $space-2xl;  // 32px

    &__inner {
        max-width: 1440px;
        margin-inline: auto;
        padding-inline: $space-3xl;  // 40px
        padding-bottom: $space-3xl;

        @include media-down(md) {
            padding-inline: $space-sm;
            padding-bottom: $space-2xl;
        }
    }

    &__content {
        display: flex;
        flex-direction: column;
        gap: $space-sm;  // 16px (Figma usa 16px entre título y bajada)
        margin-top: $space-md;
    }

    &__eyebrow {
        margin: 0;
        @include eyebrow($white-70);
    }

    &__title {
        margin: 0;
        font-family: $font-family-display;  // Arizona Flare
        font-weight: 500;
        font-size: 64px;
        line-height: 86px;
        color: $white;

        @include media-down(md) {
            font-size: 40px;
            line-height: 1.1;
        }
    }

    &__desc {
        max-width: 696px;
        font-family: $font-family-body;
        font-weight: 400;
        font-size: $font-size-body-lg;  // 16px
        line-height: 24px;
        color: $white-70;

        p { margin: 0; }
        p + p { margin-top: $space-sm; }
        a { color: $brand-accent; text-decoration: underline; }
        strong { font-weight: 600; }
    }

    &__separator {
        margin: 0 auto;
        border: 0;
        height: 1px;
        background-color: $gray-medium;
        max-width: 1360px;
    }
}

// --------------------------------------------------------------------------
// BREADCRUMB (reutilizable, anida dentro de cualquier header dark)
// --------------------------------------------------------------------------
.udp-breadcrumb {
    &__list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-wrap: wrap;
        gap: 6px;  // Spec Figma: 6px entre item y chevron
        align-items: center;
    }

    &__item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-family: $font-family-body;
        font-weight: 500;
        font-size: 14px;
        line-height: 20px;
        color: $white;
    }

    &__link,
    &__current {
        color: inherit;
        text-decoration: underline;
        text-underline-offset: 2px;
    }

    &__link {
        &:hover,
        &:focus-visible {
            color: $brand-accent;
        }
    }

    &__current {
        cursor: default;
    }

    &__sep {
        display: inline-flex;
        color: $white-70;
    }
}

// --------------------------------------------------------------------------
// CARDS — container
// --------------------------------------------------------------------------
.udp-section-cards {
    background-color: $dark-1;
    color: $white;
    padding: $space-3xl 0;

    &__list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    &__item {
        display: block;
    }
}

// --------------------------------------------------------------------------
// MODO GRID — 5 columnas desktop, 3 tablet, scroll-snap mobile
// --------------------------------------------------------------------------
.udp-section-cards--grid {
    .udp-section-cards__list {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 30px;
        padding-inline: $space-3xl;  // 40px
        max-width: 1440px;
        margin-inline: auto;

        @include media-down(xl) {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        @include media-down(md) {
            // Mobile: scroll-snap horizontal
            grid-template-columns: none;
            grid-auto-flow: column;
            grid-auto-columns: 80%;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            scroll-padding-inline: $space-sm;
            padding-inline: $space-sm;
            -webkit-overflow-scrolling: touch;

            scrollbar-width: none;
            &::-webkit-scrollbar { display: none; }

            .udp-section-cards__item {
                scroll-snap-align: start;
            }
        }
    }
}

// --------------------------------------------------------------------------
// MODO SWIPER — carrusel horizontal con peek
// --------------------------------------------------------------------------
.udp-section-cards--swiper {
    .udp-section-cards__viewport {
        overflow: visible;  // Necesario para que la card peek se vea
        padding-inline: $space-3xl;
        padding-right: 150px;  // Reveal de la siguiente card (Figma)

        @include media-down(md) {
            padding-inline: $space-sm;
            padding-right: 60px;
        }
    }

    .udp-section-cards__list {
        display: flex;
        gap: 33px;
    }

    .udp-section-cards__item.swiper-slide {
        flex: 0 0 285px;

        @include media-down(md) {
            flex: 0 0 80%;
        }
    }
}

// --------------------------------------------------------------------------
// CARD ÚNICO — sin imagen, gris ($dark-2) → lila ($brand-blue) en hover
// --------------------------------------------------------------------------
.udp-section-card {
    position: relative;
    display: flex;
    flex-direction: column;
    background-color: $dark-2;
    color: $white;
    padding: $space-md;  // 18px
    text-decoration: none;
    aspect-ratio: 248 / 318;  // Default grid ratio
    transition: background-color $transition-base, transform $transition-base;
    overflow: hidden;
    height: 100%;

    .udp-section-cards--swiper & {
        aspect-ratio: 285 / 365;
    }

    &:hover,
    &:focus-visible {
        background-color: $brand-blue;  // #4539F2 — el "lila"
        transform: translateY(-2px);
        text-decoration: none;
        color: $white;
    }

    &__content {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        gap: $space-2xs;  // 8px
        margin-top: auto;  // Empuja el contenido al bottom
    }

    &__eyebrow {
        margin: 0;
        @include eyebrow($white-70);
    }

    &__title {
        margin: 0;
        font-family: $font-family-body;
        font-weight: 500;
        font-size: $font-size-h4;  // 20px
        line-height: $line-height-snug;
        color: $white;
    }

    &__desc {
        margin: 0;
        font-family: $font-family-body;
        font-size: $font-size-body-sm;  // 14px
        line-height: 1.4;
        color: $white-70;
    }

    &__cta {
        position: absolute;
        top: $space-md;
        right: $space-md;
        z-index: 2;
        width: 48px;
        height: 48px;
        border-radius: 9999px;
        border: 1px solid $gray-medium;  // #4F4F4F
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: $white;
        transition:
            background-color $transition-base,
            color $transition-base,
            border-color $transition-base;
    }

    &:hover .udp-section-card__cta,
    &:focus-visible .udp-section-card__cta {
        background-color: $white;
        color: $brand-blue;  // arrow se oscurece a lila para contraste con bg blanco
        border-color: $white;
    }

    // ------ VARIANTE BACK ------
    // Mismo layout, solo título (sin eyebrow ni desc). El icono cambia a undo-2
    // (lo gestiona el partial PHP).
    &--back {
        .udp-section-card__title {
            font-size: $font-size-h4;
            line-height: $line-height-snug;
        }
    }
}
```

- [x] **Step 2: Importar el parcial en main.scss**

Edit `wp-content/themes/starter-theme/src/scss/main.scss`. Localizar la sección `// 7. Bloques ACF` y AÑADIR el import (no eliminar el `flexible-blocks` existente, solo añadir):

```scss
// --------------------------------------------------------------------------
// 7. Bloques ACF
// --------------------------------------------------------------------------
@import "blocks/section-landing";
@import "blocks/flexible-blocks";
```

- [x] **Step 3: Build**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -5
```

Expected: build OK, CSS size aumenta ~5-10 kB.

---

## Task 5: Swiper.js — install + JS module + init

**Files:**
- Modify: `wp-content/themes/starter-theme/package.json` (npm install)
- Create: `wp-content/themes/starter-theme/src/js/modules/section-landing-swiper.js`
- Modify: `wp-content/themes/starter-theme/src/js/main.js`

- [x] **Step 1: Instalar Swiper**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm install swiper 2>&1 | tail -5
```

Expected: `swiper` añadido a `dependencies`.

- [x] **Step 2: Crear el módulo JS**

Create `wp-content/themes/starter-theme/src/js/modules/section-landing-swiper.js`:

```javascript
/**
 * Section Landing — Swiper init
 *
 * Inicializa Swiper.js solo en `.udp-section-cards--swiper [data-udp-swiper]`.
 * Si no hay elementos, no se importa ningún módulo de Swiper (lazy).
 */
import { qsa } from '@utils/dom';

export async function initSectionLandingSwiper() {
    const containers = qsa('.udp-section-cards--swiper [data-udp-swiper], .udp-section-cards--swiper.swiper');
    if (!containers.length) {
        return;
    }

    // Lazy load Swiper solo si hace falta
    const { default: Swiper } = await import('swiper');
    const { Navigation, Keyboard, FreeMode } = await import('swiper/modules');
    await import('swiper/css');

    containers.forEach((el) => {
        // Si el viewport tiene la clase .swiper, úsalo. Si no, busca el primer hijo .swiper.
        const swiperEl = el.classList.contains('swiper') ? el : el.querySelector('.swiper');
        if (!swiperEl) return;

        new Swiper(swiperEl, {
            modules: [Navigation, Keyboard, FreeMode],
            slidesPerView: 'auto',
            spaceBetween: 33,
            freeMode: { enabled: true, momentum: true },
            keyboard: { enabled: true },
            grabCursor: true,
            breakpoints: {
                768: { spaceBetween: 33 },
                0:   { spaceBetween: 16 },
            },
        });
    });
}
```

- [x] **Step 3: Importar e inicializar en main.js**

Edit `wp-content/themes/starter-theme/src/js/main.js`. Localizar la sección de imports de módulos (después de los actuales `initNavbar` etc.) y AÑADIR:

```javascript
import { initSectionLandingSwiper } from '@modules/section-landing-swiper';
```

Y en el bloque `domReady(() => { ... })` AÑADIR la llamada:

```javascript
domReady(() => {
    initNavbar();
    initSmoothScroll();
    initScrollAnimations();
    initMobileMenu();
    initSectionLandingSwiper();  // ← NUEVO

    console.log('[StarterBS5] Theme initialized');
});
```

- [x] **Step 4: Build y verificar size**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -8
```

Expected: build OK. Swiper aparece como chunk separado (`dist/js/chunks/swiper-XXX.js`) gracias al `import()` dinámico — solo se carga cuando hay un swiper en la página.

---

## Task 6: Verificación E2E + commit final

**Files:**
- Modify: `wp-content/themes/starter-theme/MEMORY.md`

- [x] **Step 1: Crear página de prueba TOP-LEVEL (sin parent)**

```bash
/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot -proot udp -e "INSERT INTO wp_fnku4yposts (post_author, post_date, post_date_gmt, post_content, post_title, post_status, comment_status, ping_status, post_name, post_type, post_modified, post_modified_gmt) VALUES (1, NOW(), UTC_TIMESTAMP(), '', 'Test Section Landing Top', 'publish', 'closed', 'closed', 'test-section-landing-top', 'page', NOW(), UTC_TIMESTAMP()); SELECT LAST_INSERT_ID();"
```

Anotar el ID devuelto (`<ID_TOP>`). Asignar el page template:

```bash
/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot -proot udp -e "INSERT INTO wp_fnku4ypostmeta (post_id, meta_key, meta_value) VALUES (<ID_TOP>, '_wp_page_template', 'templates/page-section-landing.php');"
```

- [x] **Step 2: Crear página HIJA (parent = ID_TOP) para validar back-card**

```bash
/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot -proot udp -e "INSERT INTO wp_fnku4yposts (post_author, post_parent, post_date, post_date_gmt, post_content, post_title, post_status, comment_status, ping_status, post_name, post_type, post_modified, post_modified_gmt) VALUES (1, <ID_TOP>, NOW(), UTC_TIMESTAMP(), '', 'Test Section Landing Child', 'publish', 'closed', 'closed', 'test-section-landing-child', 'page', NOW(), UTC_TIMESTAMP()); SELECT LAST_INSERT_ID();"
```

Anotar `<ID_CHILD>`. Asignar el template a la hija también:

```bash
/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot -proot udp -e "INSERT INTO wp_fnku4ypostmeta (post_id, meta_key, meta_value) VALUES (<ID_CHILD>, '_wp_page_template', 'templates/page-section-landing.php');"
```

- [x] **Step 3: Validar HTML de la página top-level**

```bash
curl -s "http://localhost:8888/udp/test-section-landing-top/?nocache=$(date +%s)" | grep -E "udp-section-header|udp-breadcrumb|udp-section-cards|udp-section-card--back" | head -10
```

Expected: aparece `udp-section-header`, `udp-section-header__title`, `udp-breadcrumb`, `udp-breadcrumb__current` (con texto `Test Section Landing Top`). NO debe aparecer `udp-section-card--back` (no hay padre). El container de cards puede no aparecer si el repeater está vacío — eso es correcto (early return).

- [x] **Step 4: Validar HTML de la página hija (back-card)**

```bash
curl -s "http://localhost:8888/udp/test-section-landing-top/test-section-landing-child/?nocache=$(date +%s)" | grep -E "udp-section-card--back|udp-breadcrumb__link|Volver a Test" | head -10
```

Expected:
- Aparece `udp-section-card--back` (el container la genera porque hay parent_id).
- En el breadcrumb aparecen 3 items: `Inicio` (link), `Test Section Landing Top` (link), `Test Section Landing Child` (current).
- Aparece el texto `Volver a Test Section Landing Top` dentro de la back-card.

- [x] **Step 5: Borrar las páginas de prueba**

```bash
/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot -proot udp -e "DELETE FROM wp_fnku4yposts WHERE ID IN (<ID_TOP>, <ID_CHILD>); DELETE FROM wp_fnku4ypostmeta WHERE post_id IN (<ID_TOP>, <ID_CHILD>);"
```

- [x] **Step 6: Verificar que el dropdown del editor muestra el template**

Visitar `http://localhost:8888/udp/cms/wp-admin/edit.php?post_type=page` (logueado). Editar cualquier página, en el sidebar derecho buscar "Template" o "Plantilla" y verificar que aparece la opción **"Section Landing"**. (Validación manual del usuario.)

- [x] **Step 7: Actualizar MEMORY.md**

Append a `wp-content/themes/starter-theme/MEMORY.md`:

```markdown
### 2026-04-28 — F3 Section Landing Template completada

**Hechos**:
- Page template `templates/page-section-landing.php` con `Template Name: Section Landing`. Asignable desde el dropdown del editor a cualquier página.
- ACF group `group_template_section_landing` con location `page_template == templates/page-section-landing.php`. Estructura:
  - `page_header` group: `eyebrow` (text) + `titulo` (text, requerido, fallback título WP) + `descripcion` (wysiwyg).
  - `cards_display` radio (grid|swiper, default grid).
  - `cards` repeater: `eyebrow` (text) + `titulo` (text) + `descripcion` (textarea) + `link` (link). NO hay campo imagen en cards ni en el header — las cards son siempre gris→lila sin imagen, y el header no usa bg image en este template.
- Template-parts en `template-parts/sections/`:
  - `section-landing-header.php` — breadcrumb + título + descripción wysiwyg + separador.
  - `breadcrumb.php` — partial reutilizable que recorre `wp_get_post_parent_id`. Acepta `args.page_id` y `args.home_label` opcionales.
  - `section-landing-cards.php` — container condicional (grid/swiper). Si la página tiene `post_parent`, prepende una card sintética con `variant=back`.
  - `section-landing-card.php` — card único con variantes `default` (icono arrow-up-right) y `back` (icono undo-2).
- SCSS único `_section-landing.scss` con: page-header (sin bg image, max-width 1440, separador 1px `$gray-medium`), breadcrumb (Work Sans Medium 14px, chevron-right 12px, gap 6px), grid 5 cols desktop / 3 tablet / scroll-snap mobile, swiper con peek 150px y card 285×365 vs grid card 248×318, hover bg `$dark-2` → `$brand-blue` (#4539F2 = lila), CTA hover bg blanco con icono lila.
- JS module `section-landing-swiper.js` con lazy import de Swiper.js (chunk separado, solo se carga si hay `.udp-section-cards--swiper` en la página).
- Swiper.js como dependencia npm.

**Decisiones clave**:
- Breadcrumb se construye desde la jerarquía nativa de WordPress (`wp_get_post_parent_id`), no como campo ACF — así el contenido editorial no necesita gestionarlo manualmente.
- La back-to-parent card es auto-prepended por el container, no es un campo ACF — el editor no la ve ni la edita.
- "Lila" del usuario = `$brand-blue` #4539F2 ya definido en `_variables.scss`.

**Pendientes**:
- Páginas iniciales que el usuario puede asignar el template: Pregrado, Conoce UDP, Gobernanza y Reglamentos, Premios y distinciones, Servicios, Webmail UDP. El admin asigna manualmente desde el editor.
- F4 en adelante: archivos/singles de CPT.
```

- [x] **Step 8: Commit final con todos los archivos del template**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add \
  acf-json/group_template_section_landing.json \
  templates/page-section-landing.php \
  template-parts/sections/section-landing-header.php \
  template-parts/sections/breadcrumb.php \
  template-parts/sections/section-landing-cards.php \
  template-parts/sections/section-landing-card.php \
  src/scss/blocks/_section-landing.scss \
  src/scss/main.scss \
  src/js/modules/section-landing-swiper.js \
  src/js/main.js \
  package.json \
  package-lock.json \
  MEMORY.md
git commit -m "feat(template): F3 Section Landing page template

- ACF group activado por page_template (page_header wysiwyg + cards_display + cards repeater sin imagen).
- Cabecera con breadcrumb dinámico (jerarquía WP) + título + descripción wysiwyg + separador.
- Card único reutilizable con variantes default (arrow-up-right) y back (undo-2).
- Back-to-parent card auto-prepended cuando la página tiene post_parent.
- Cards gris (\$dark-2) → lila (\$brand-blue #4539F2) en hover, sin imagen.
- Modo grid: 5 cols desktop, 3 tablet, scroll-snap horizontal mobile.
- Modo swiper: Swiper.js lazy-loaded (chunk separado), peek 150px del Figma.
- Asignable desde dropdown del editor a cualquier página WP."
```

---

## Coverage check vs. spec

| Requisito | Tasks |
|---|---|
| Page template asignable manualmente | Task 2 (Template Name header) |
| 1 grupo ACF con location page_template | Task 1 |
| Page header con breadcrumb + título + descripción wysiwyg (sin imagen de fondo) | Task 1 (ACF) + Task 2 (markup) + Task 2b (breadcrumb partial) + Task 4 (SCSS) |
| Breadcrumb dinámico desde jerarquía WP | Task 2b (recorre `wp_get_post_parent_id`) |
| Cards manuales en repeater ACF (sin campo imagen) | Task 1 (repeater) + Task 3 (markup) |
| Cards link interno O externo (link field ACF) | Task 1 (link field) + Task 3 (target blank + icono arrow-up-right) |
| Modo swiper / modo grid (radio, único modo por página) | Task 1 (cards_display) + Task 3 (container condicional) + Task 4 (SCSS) + Task 5 (Swiper.js) |
| Swiper.js library lazy-loaded | Task 5 |
| CSS scroll-snap mobile en grid | Task 4 |
| Card único componente reutilizable (variantes default | back) | Task 3 (single template-part) |
| Cards gris (`$dark-2`) → lila (`$brand-blue` #4539F2) en hover | Task 4 (SCSS) |
| Back-to-parent card auto en subpáginas con icono undo-2 | Task 2 (passes parent_id) + Task 3 (container prepend + variante back) + Task 4 (SCSS) |

**Cobertura completa.**

---

## Verification end-to-end (post-ejecución)

1. Build OK: `npm run build` sin errores. CSS sube ~10-15 kB. JS Swiper queda como chunk separado (~30 kB gzip).
2. Page template visible en editor: dropdown del sidebar de WP-Admin muestra "Section Landing".
3. Crear página de prueba TOP-LEVEL con template asignado, llenar `page_header` (titulo + descripción wysiwyg con un párrafo y un link) + 3 cards (eyebrow + titulo + descripción + link interno o externo). Visitar la página:
   - Header: breadcrumb `Inicio › Test...`, título 64px Arizona Flare, descripción 16px max-width 696px, separador 1px abajo.
   - NO debe aparecer back-card (no hay parent).
   - Cards renderizan en grid 5 cols. Hover sobre una card: bg pasa a lila (#4539F2) y el icono CTA se invierte a fondo blanco con flecha lila.
4. Crear página HIJA (parent = la TOP-LEVEL anterior) y asignarle el template. Visitar la hija:
   - Breadcrumb 3 niveles: `Inicio › Test... › Hija`.
   - Primera card es la back-card "Volver a [Top]", icono undo-2.
   - El resto de cards manuales aparece después.
5. Cambiar `cards_display` a `swiper` en la TOP-LEVEL, recargar: cards en línea horizontal con scroll, Swiper.js inicializado (touch swipe funciona), peek 150px de la siguiente card visible.
6. Mobile (responsive devtools): grid pasa a scroll-snap horizontal, cada card al 80% width, swipe natural. Header pasa a título 40px y padding 16px.
7. Card link externo (`target=_blank`) abre en pestaña nueva con `rel="noopener noreferrer"`. El icono es siempre arrow-up-right en variante default (igual que Figma — no cambia entre interno/externo).
