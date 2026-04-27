# F3 — Section Landing Page Template — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Crear un page template asignable `Section Landing` que el admin pueda elegir desde el dropdown del editor, con un hero (eyebrow + título + bajada + imagen fondo) y un grid de cards en dos modos visualizables (`swiper` carrusel horizontal o `grid` 5 columnas con scroll-snap mobile). Cada card es un link interno o externo.

**Architecture:** 1 page template con 1 grupo ACF. Card = componente único reutilizable. Container condicional `if cards_display == swiper` (Swiper.js) `else` grid CSS. Swiper.js como dependencia npm. Mobile grid usa `scroll-snap` nativo (sin JS).

**Tech Stack:** WordPress page template, ACF Pro (link field, radio, repeater), Bootstrap 5, Swiper.js 11+, SCSS, scroll-snap CSS.

**Reference**:
- Spec: `wp-content/themes/starter-theme/docs/superpowers/specs/2026-04-27-migracion-udp-portable-a-starter-theme-design.md`
- Figma swiper (Pregrado): nodeId `4383:18979` — card 285×365, gap 33, scroll horizontal con padding-right 150 (peek next card)
- Figma grid (Servicios): nodeId `4401:22378` — card 248×318, gap 30, 5 cols desktop
- Card común a ambos modos: bg `$dark-2`, padding 18px, eyebrow Necto Mono uppercase 12px white-70, título Work Sans Medium 20px, botón CTA circular 48×48 top-right con arrow-up-right

---

## Plan file location

⚠️ El sistema fuerza este archivo en `~/.claude/plans/`. Cuando F3 arranque (fuera de plan mode), copiar/mover a `wp-content/themes/starter-theme/docs/superpowers/plans/2026-04-28-f3-section-landing-template.md` para mantener convención del proyecto.

---

## Inventario de archivos

**A crear:**
- `wp-content/themes/starter-theme/templates/page-section-landing.php` — page template (Template Name header)
- `wp-content/themes/starter-theme/template-parts/sections/section-landing-hero.php`
- `wp-content/themes/starter-theme/template-parts/sections/section-landing-cards.php` — container condicional
- `wp-content/themes/starter-theme/template-parts/sections/section-landing-card.php` — card único
- `wp-content/themes/starter-theme/src/scss/blocks/_section-landing.scss` — hero + cards (un solo archivo, todo es del mismo template)
- `wp-content/themes/starter-theme/src/js/modules/section-landing-swiper.js`
- `wp-content/themes/starter-theme/acf-json/group_template_section_landing.json`

**A modificar:**
- `wp-content/themes/starter-theme/package.json` — añadir dependencia `swiper`
- `wp-content/themes/starter-theme/src/js/main.js` — importar el nuevo módulo
- `wp-content/themes/starter-theme/src/scss/main.scss` — `@import "blocks/section-landing";`

**A NO tocar:** mu-plugins, otros templates, _variables.scss, _mixins.scss, header.php, footer.php.

---

## ACF Schema — `group_template_section_landing`

Location: `page_template == templates/page-section-landing.php`

```
HERO (group)
├── eyebrow         (text, opcional)        ← "Universidad", "Pregrado", etc.
├── titulo          (text, requerido)        ← "Pregrado"
├── bajada          (textarea, opcional)     ← párrafo intro
└── imagen_fondo    (image, opcional)        ← bg con overlay #232323

CARDS_DISPLAY (radio, requerido, default 'grid')
├── grid     ← 5 columnas desktop, scroll-snap mobile
└── swiper   ← carrusel horizontal con peek

CARDS (repeater, min 1, max ilimitado, layout block)
├── eyebrow         (text, opcional)        ← "Charlas", "Postgrado"
├── titulo          (text, requerido)
├── descripcion     (textarea, opcional)
├── imagen          (image, opcional)
└── link            (link, requerido)        ← ACF link nativo (url + title + target)
```

Field keys: prefijo `field_template_section_landing_*` para todos.

---

## Task 1: Crear ACF JSON + sync

**Files:**
- Create: `wp-content/themes/starter-theme/acf-json/group_template_section_landing.json`

- [ ] **Step 1: Escribir JSON con la estructura ACF completa**

```json
{
    "key": "group_template_section_landing",
    "title": "Template — Section Landing",
    "fields": [
        {
            "key": "field_template_section_landing_hero",
            "label": "Hero",
            "name": "hero",
            "type": "group",
            "layout": "block",
            "sub_fields": [
                { "key": "field_template_section_landing_hero_eyebrow", "label": "Eyebrow (etiqueta superior)", "name": "eyebrow", "type": "text" },
                { "key": "field_template_section_landing_hero_titulo", "label": "Título", "name": "titulo", "type": "text", "required": 1 },
                { "key": "field_template_section_landing_hero_bajada", "label": "Bajada", "name": "bajada", "type": "textarea", "rows": 3 },
                { "key": "field_template_section_landing_hero_imagen", "label": "Imagen de fondo", "name": "imagen_fondo", "type": "image", "return_format": "url", "library": "all", "instructions": "Opcional. Si está vacío el hero se muestra solo con fondo dark." }
            ]
        },
        {
            "key": "field_template_section_landing_cards_display",
            "label": "Modo de visualización de cards",
            "name": "cards_display",
            "type": "radio",
            "required": 1,
            "choices": { "grid": "Grid (5 columnas, scroll mobile)", "swiper": "Swiper (carrusel horizontal)" },
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
            "sub_fields": [
                { "key": "field_template_section_landing_card_eyebrow", "label": "Eyebrow (etiqueta)", "name": "eyebrow", "type": "text", "instructions": "Opcional. Ej. 'Charlas', 'Postgrado'." },
                { "key": "field_template_section_landing_card_titulo", "label": "Título", "name": "titulo", "type": "text", "required": 1 },
                { "key": "field_template_section_landing_card_descripcion", "label": "Descripción", "name": "descripcion", "type": "textarea", "rows": 2 },
                { "key": "field_template_section_landing_card_imagen", "label": "Imagen", "name": "imagen", "type": "image", "return_format": "url" },
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
    "description": "Hero + grid de cards (link interno/externo) en modo grid o swiper."
}
```

- [ ] **Step 2: Validar JSON**

```bash
jq empty /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_template_section_landing.json && echo "JSON válido"
```

- [ ] **Step 3: Sync con UPSERT (no duplicar)**

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

## Task 2: Page template scaffold + hero

**Files:**
- Create: `wp-content/themes/starter-theme/templates/page-section-landing.php`
- Create: `wp-content/themes/starter-theme/template-parts/sections/section-landing-hero.php`

- [ ] **Step 1: Crear directorio si no existe**

```bash
mkdir -p /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/sections
```

- [ ] **Step 2: Crear el page template con su Template Name**

Create `wp-content/themes/starter-theme/templates/page-section-landing.php`:

```php
<?php
/**
 * Template Name: Section Landing
 *
 * Hero + grid o swiper de cards (link interno/externo).
 *
 * @package Starter_Theme
 */

get_header();

$hero          = function_exists( 'get_field' ) ? get_field( 'hero' ) : array();
$cards         = function_exists( 'get_field' ) ? get_field( 'cards' ) : array();
$cards_display = function_exists( 'get_field' ) ? get_field( 'cards_display' ) : 'grid';
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'udp-section-landing' ); ?>>

	<?php
	get_template_part(
		'template-parts/sections/section-landing-hero',
		null,
		array(
			'hero'      => $hero,
			'page_id'   => get_the_ID(),
			'page_title' => get_the_title(),
		)
	);

	get_template_part(
		'template-parts/sections/section-landing-cards',
		null,
		array(
			'cards'   => $cards,
			'display' => $cards_display ?: 'grid',
		)
	);
	?>

</article>

<?php
get_footer();
```

- [ ] **Step 3: Crear el hero template-part**

Create `wp-content/themes/starter-theme/template-parts/sections/section-landing-hero.php`:

```php
<?php
/**
 * Section Landing > Hero
 *
 * @package Starter_Theme
 *
 * @var array $args ['hero' => array, 'page_id' => int, 'page_title' => string]
 */
$hero        = isset( $args['hero'] ) && is_array( $args['hero'] ) ? $args['hero'] : array();
$eyebrow     = isset( $hero['eyebrow'] ) ? $hero['eyebrow'] : '';
$titulo      = isset( $hero['titulo'] ) && ! empty( $hero['titulo'] ) ? $hero['titulo'] : ( $args['page_title'] ?? '' );
$bajada      = isset( $hero['bajada'] ) ? $hero['bajada'] : '';
$imagen      = isset( $hero['imagen_fondo'] ) ? $hero['imagen_fondo'] : '';
$has_image   = ! empty( $imagen );
$style_attr  = $has_image ? ' style="background-image: url(' . esc_url( $imagen ) . ');"' : '';
$class_extra = $has_image ? ' udp-section-hero--has-image' : '';
?>
<section class="udp-section-hero<?php echo esc_attr( $class_extra ); ?>"<?php echo $style_attr; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="udp-section-hero__inner">
		<?php if ( ! empty( $eyebrow ) ) : ?>
			<p class="udp-section-hero__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
		<?php endif; ?>

		<?php if ( ! empty( $titulo ) ) : ?>
			<h1 class="udp-section-hero__title"><?php echo esc_html( $titulo ); ?></h1>
		<?php endif; ?>

		<?php if ( ! empty( $bajada ) ) : ?>
			<p class="udp-section-hero__bajada"><?php echo esc_html( $bajada ); ?></p>
		<?php endif; ?>
	</div>
</section>
```

- [ ] **Step 4: Validar PHP syntax**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/templates/page-section-landing.php
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/sections/section-landing-hero.php
```

Expected: 2× `No syntax errors detected`.

---

## Task 3: Cards container + card single template-parts

**Files:**
- Create: `wp-content/themes/starter-theme/template-parts/sections/section-landing-cards.php`
- Create: `wp-content/themes/starter-theme/template-parts/sections/section-landing-card.php`

- [ ] **Step 1: Container condicional swiper/grid**

Create `wp-content/themes/starter-theme/template-parts/sections/section-landing-cards.php`:

```php
<?php
/**
 * Section Landing > Cards container (grid o swiper)
 *
 * @package Starter_Theme
 *
 * @var array $args ['cards' => array, 'display' => string ('grid'|'swiper')]
 */
$cards   = isset( $args['cards'] ) && is_array( $args['cards'] ) ? $args['cards'] : array();
$display = isset( $args['display'] ) && in_array( $args['display'], array( 'grid', 'swiper' ), true ) ? $args['display'] : 'grid';

if ( empty( $cards ) ) {
	return;
}

$container_class = 'udp-section-cards udp-section-cards--' . $display;
?>
<section class="<?php echo esc_attr( $container_class ); ?>"<?php echo $display === 'swiper' ? ' data-udp-swiper' : ''; ?>>
	<?php if ( $display === 'swiper' ) : ?>
		<div class="udp-section-cards__viewport swiper">
			<ul class="udp-section-cards__list swiper-wrapper">
				<?php foreach ( $cards as $index => $card ) : ?>
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
			<?php foreach ( $cards as $index => $card ) : ?>
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

- [ ] **Step 2: Card single reutilizable**

Create `wp-content/themes/starter-theme/template-parts/sections/section-landing-card.php`:

```php
<?php
/**
 * Section Landing > Card individual
 *
 * @package Starter_Theme
 *
 * @var array $args ['card' => array, 'index' => int]
 */
$card        = isset( $args['card'] ) && is_array( $args['card'] ) ? $args['card'] : array();
$eyebrow     = isset( $card['eyebrow'] ) ? $card['eyebrow'] : '';
$titulo      = isset( $card['titulo'] ) ? $card['titulo'] : '';
$descripcion = isset( $card['descripcion'] ) ? $card['descripcion'] : '';
$imagen      = isset( $card['imagen'] ) ? $card['imagen'] : '';
$link        = isset( $card['link'] ) && is_array( $card['link'] ) ? $card['link'] : array();

$href   = isset( $link['url'] ) ? $link['url'] : '';
$target = isset( $link['target'] ) ? $link['target'] : '';
$rel    = $target === '_blank' ? 'noopener noreferrer' : '';

if ( empty( $href ) || empty( $titulo ) ) {
	return;
}

$is_external = $target === '_blank';
?>
<a
	href="<?php echo esc_url( $href ); ?>"
	class="udp-section-card<?php echo $imagen ? ' udp-section-card--has-image' : ''; ?>"
	<?php if ( $target ) : ?>target="<?php echo esc_attr( $target ); ?>"<?php endif; ?>
	<?php if ( $rel ) : ?>rel="<?php echo esc_attr( $rel ); ?>"<?php endif; ?>
>
	<?php if ( $imagen ) : ?>
		<div class="udp-section-card__image" style="background-image: url(<?php echo esc_url( $imagen ); ?>);" aria-hidden="true"></div>
	<?php endif; ?>

	<div class="udp-section-card__content">
		<?php if ( ! empty( $eyebrow ) ) : ?>
			<p class="udp-section-card__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
		<?php endif; ?>

		<h3 class="udp-section-card__title"><?php echo esc_html( $titulo ); ?></h3>

		<?php if ( ! empty( $descripcion ) ) : ?>
			<p class="udp-section-card__desc"><?php echo esc_html( $descripcion ); ?></p>
		<?php endif; ?>
	</div>

	<span class="udp-section-card__cta" aria-hidden="true">
		<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
			<?php if ( $is_external ) : ?>
				<path d="M5 3h8v8M13 3L3 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
			<?php else : ?>
				<path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
			<?php endif; ?>
		</svg>
	</span>
</a>
```

- [ ] **Step 3: Validar PHP syntax**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/sections/section-landing-cards.php
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/sections/section-landing-card.php
```

---

## Task 4: SCSS — hero + card + grid + swiper layouts

**Files:**
- Create: `wp-content/themes/starter-theme/src/scss/blocks/_section-landing.scss`
- Modify: `wp-content/themes/starter-theme/src/scss/main.scss`

- [ ] **Step 1: Crear el parcial SCSS**

Create `wp-content/themes/starter-theme/src/scss/blocks/_section-landing.scss`:

```scss
// ==========================================================================
// SECTION LANDING — page template (hero + cards grid o swiper)
// Spec Figma: 4383:18979 (swiper Pregrado), 4401:22378 (grid Servicios)
// Card común: 248×318 (grid), 285×365 (swiper); bg $dark-2; padding 18px
// ==========================================================================

// --------------------------------------------------------------------------
// HERO
// --------------------------------------------------------------------------
.udp-section-hero {
    background-color: $dark-2;
    color: $white;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    position: relative;
    isolation: isolate;
    padding: $space-4xl $space-3xl;  // 64px vertical, 40px horizontal
    min-height: 348px;
    display: flex;
    align-items: flex-end;

    @include media-down(md) {
        padding: $space-2xl $space-sm;
        min-height: 240px;
    }

    // Overlay sobre la imagen (si existe)
    &--has-image::before {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(35, 35, 35, 0.78);
        z-index: -1;
    }

    &__inner {
        max-width: 1360px;
        margin-inline: auto;
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: $space-md;
    }

    &__eyebrow {
        margin: 0;
        @include eyebrow($white-70);
    }

    &__title {
        margin: 0;
        font-family: $font-family-body;
        font-weight: 500;
        font-size: 64px;
        line-height: 1.05;
        color: $white;

        @include media-down(md) {
            font-size: 40px;
        }
    }

    &__bajada {
        margin: 0;
        max-width: 696px;
        font-family: $font-family-body;
        font-weight: 500;
        font-size: $font-size-body-lg;
        line-height: 1.5;
        color: $white-70;
    }
}

// --------------------------------------------------------------------------
// CARDS — container común
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

            // Hide scrollbar (visual cleaner)
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
        overflow: visible;  // Necesario para que las cards "peek" se vean
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
        flex: 0 0 285px;  // Card swiper width del Figma

        @include media-down(md) {
            flex: 0 0 80%;
        }
    }
}

// --------------------------------------------------------------------------
// CARD ÚNICO — mismo en grid y swiper, dimensiones según wrapper
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
        background-color: $brand-blue;
        transform: translateY(-2px);
        text-decoration: none;
        color: $white;
    }

    &__image {
        position: absolute;
        inset: 0;
        background-size: cover;
        background-position: center;
        z-index: 0;

        // Si hay imagen, oscurecer un poco el bg para que el texto se lea
        &::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(0,0,0,0.7) 100%);
        }
    }

    &__content {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        gap: $space-2xs;
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
        font-size: $font-size-body-sm;
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
        transition: background-color $transition-base, color $transition-base;
    }

    &:hover .udp-section-card__cta,
    &:focus-visible .udp-section-card__cta {
        background-color: $white;
        color: $dark-1;
        border-color: $white;
    }
}
```

- [ ] **Step 2: Importar el parcial en main.scss**

Edit `wp-content/themes/starter-theme/src/scss/main.scss`. Localizar la sección `// 7. Bloques ACF` y AÑADIR el import (no eliminar el `flexible-blocks` existente, solo añadir):

```scss
// --------------------------------------------------------------------------
// 7. Bloques ACF
// --------------------------------------------------------------------------
@import "blocks/section-landing";
@import "blocks/flexible-blocks";
```

- [ ] **Step 3: Build**

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

- [ ] **Step 1: Instalar Swiper**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm install swiper 2>&1 | tail -5
```

Expected: `swiper` añadido a `dependencies`.

- [ ] **Step 2: Crear el módulo JS**

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

- [ ] **Step 3: Importar e inicializar en main.js**

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

- [ ] **Step 4: Build y verificar size**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -8
```

Expected: build OK. Swiper aparece como chunk separado (`dist/js/chunks/swiper-XXX.js`) gracias al `import()` dinámico — solo se carga cuando hay un swiper en la página.

---

## Task 6: Verificación E2E + commit final

**Files:**
- Modify: `wp-content/themes/starter-theme/MEMORY.md`

- [ ] **Step 1: Crear página de prueba via SQL para validar template**

```bash
/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot -proot udp -e "INSERT INTO wp_fnku4yposts (post_author, post_date, post_date_gmt, post_content, post_title, post_status, comment_status, ping_status, post_name, post_type, post_modified, post_modified_gmt) VALUES (1, NOW(), UTC_TIMESTAMP(), '', 'Test Section Landing', 'publish', 'closed', 'closed', 'test-section-landing', 'page', NOW(), UTC_TIMESTAMP()); SELECT LAST_INSERT_ID();"
```

Anotar el ID devuelto. Asignar el page template:

```bash
/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot -proot udp -e "INSERT INTO wp_fnku4ypostmeta (post_id, meta_key, meta_value) VALUES (<ID>, '_wp_page_template', 'templates/page-section-landing.php');"
```

Sustituir `<ID>` con el ID real.

- [ ] **Step 2: Visitar la página y verificar HTML**

```bash
curl -s "http://localhost:8888/udp/test-section-landing/?theme=new&nocache=$(date +%s)" | grep -E "udp-section-hero|udp-section-cards|udp-section-card" | head -10
```

Expected: aparecen las clases del template aunque sin contenido (cards array vacío → early return en cards container; hero solo muestra título de la página). Mínimamente debe aparecer `udp-section-hero` y `udp-section-hero__title`.

- [ ] **Step 3: Borrar la página de prueba**

```bash
/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot -proot udp -e "DELETE FROM wp_fnku4yposts WHERE ID = <ID>; DELETE FROM wp_fnku4ypostmeta WHERE post_id = <ID>;"
```

- [ ] **Step 4: Verificar que el dropdown del editor muestra el template**

Visitar `http://localhost:8888/udp/cms/wp-admin/edit.php?post_type=page` (logueado). Editar cualquier página, en el sidebar derecho buscar "Template" o "Plantilla" y verificar que aparece la opción **"Section Landing"**. (Validación manual del usuario.)

- [ ] **Step 5: Actualizar MEMORY.md**

Append a `wp-content/themes/starter-theme/MEMORY.md`:

```markdown
### 2026-04-28 — F3 Section Landing Template completada

**Hechos**:
- Page template `templates/page-section-landing.php` con header `Template Name: Section Landing`. Asignable desde el dropdown del editor a cualquier página.
- ACF group `group_template_section_landing` con location `page_template == templates/page-section-landing.php`. Estructura: hero (eyebrow + titulo + bajada + imagen_fondo) + cards_display radio (grid|swiper, default grid) + cards repeater (eyebrow + titulo + descripcion + imagen + link).
- Template-parts en `template-parts/sections/`: hero, cards (container condicional), card (single reutilizable).
- SCSS único `_section-landing.scss` con: hero responsivo, grid 5 cols desktop / 3 tablet / scroll-snap mobile, swiper con peek 150px y card 285×365 vs grid card 248×318, hover-invert dark→blue.
- JS module `section-landing-swiper.js` con lazy import de Swiper.js (chunk separado, solo se carga si hay `.udp-section-cards--swiper` en la página).
- Swiper.js como dependencia npm.
- Card maneja link interno (icono arrow) y externo (icono arrow-up-right + target_blank automático).

**Pendientes**:
- Páginas iniciales que el usuario puede asignar el template: Pregrado, Conoce UDP, Gobernanza y Reglamentos, Premios y distinciones, Servicios, Webmail UDP. El admin asigna manualmente desde el editor.
- F4 en adelante: archivos/singles de CPT.
```

- [ ] **Step 6: Commit final con todos los archivos del template**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add \
  acf-json/group_template_section_landing.json \
  templates/page-section-landing.php \
  template-parts/sections/section-landing-hero.php \
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

- ACF group activado por page_template (hero + cards_display + cards repeater).
- Card único reutilizable con icono CTA externo/interno automático.
- Modo grid: 5 cols desktop, 3 tablet, scroll-snap horizontal mobile.
- Modo swiper: Swiper.js lazy-loaded (chunk separado), peek 150px del Figma.
- Card hover invierte dark→blue (mixin card-hover-invert F2).
- Asignable desde dropdown del editor a cualquier página WP."
```

---

## Coverage check vs. spec

| Requisito brainstorming | Tasks |
|---|---|
| Page template asignable manualmente | Task 2 (Template Name header) |
| 1 grupo ACF con location page_template | Task 1 |
| Hero (eyebrow + titulo + bajada + imagen) | Task 1 (ACF) + Task 2 (markup) + Task 4 (SCSS) |
| Cards manuales (b) en repeater ACF | Task 1 (repeater) + Task 3 (markup) |
| Cards link interno O externo (link field ACF) | Task 1 (link field) + Task 3 (target blank + icono auto) |
| Modo swiper / modo grid (a — único modo por página) | Task 1 (cards_display radio) + Task 3 (container condicional) + Task 4 (SCSS ambos modos) + Task 5 (Swiper.js) |
| Swiper.js library | Task 5 |
| CSS scroll-snap mobile en grid | Task 4 |
| Card único componente reutilizable | Task 3 (single template-part usado por ambos modos) |

**Cobertura completa.**

---

## Verification end-to-end (post-ejecución)

1. Build OK: `npm run build` sin errores. CSS sube ~10-15 kB. JS Swiper queda como chunk separado (~30 kB gzip).
2. Page template visible en editor: dropdown del sidebar de WP-Admin muestra "Section Landing".
3. Crear página de prueba con template asignado, llenar hero + 3 cards con diferentes combinaciones (con imagen, sin imagen, link externo). Visitar la página: hero renderiza, cards renderizan en grid 5 cols.
4. Cambiar `cards_display` a `swiper`, recargar: cards en línea horizontal con scroll, Swiper.js inicializado (touch swipe funciona), peek de la siguiente card visible.
5. Mobile (responsive devtools): grid pasa a scroll-snap horizontal, cada card al 80% width, swipe natural.
6. Card link externo abre en pestaña nueva; icono arrow-up-right en lugar de arrow→.
