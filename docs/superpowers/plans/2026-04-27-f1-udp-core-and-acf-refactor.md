# F1 — udp-core mu-plugin + ACF refactor — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Crear el mu-plugin `udp-core` que registra los CPTs y taxonomías del proyecto (independiente del tema), reorganizar 14 grupos ACF (8 CPT + 2 tax + 4 options) en `acf-json/` siguiendo el mapeo aprobado en el spec, migrar `politicas_publicas` de meta-field a taxonomía, y desactivar 7 grupos ACF obsoletos. Los 15 bloques flexible content (`block_*`) NO se crean en F1 — se autoran cuando se construya cada bloque (F3+).

**Architecture:** Mu-plugin `udp-core` con `inc/post-types.php` y `inc/taxonomies.php` que mantienen los slugs actuales (`agenda`, `academico`, etc.) para no migrar datos. ACF JSON files autoreados manualmente reusando las **field keys originales** del export (`acf-current-export.json`) para que `get_field()` siga encontrando los valores existentes en `wp_postmeta`. Los grupos viejos se desactivan (no se borran) para permitir rollback.

**Tech Stack:** WordPress 6.x, ACF Pro, MAMP MySQL, mu-plugins, WP-CLI (opcional).

**Note on data preservation:** Hay ~22.000 registros publicados (4.064 posts + 3.626 agenda + 691 académicos + 505 calendario + 55 centros + 42 carreras + 8.055 contacto-udp drafts). NO migramos estos datos — el plan reusa field keys existentes para que sigan accesibles.

**Note on field keys:** ACF identifica fields por `key` (ej. `field_63ce25719003c`), no por `name`. El nombre del field es el "label" interno. La data en `wp_postmeta` está vinculada al key vía `_<fieldname>` postmeta apuntando al field key. **Si cambiamos el key, perdemos la data**. Reusamos los keys del export `acf-current-export.json`.

**Reference files:**
- Spec: `docs/superpowers/specs/2026-04-27-migracion-udp-portable-a-starter-theme-design.md` (sección 5.4)
- ACF export con keys originales: `docs/superpowers/specs/acf-current-export.json`
- Tema actual con CPTs/tax: `wp-content/themes/udp_portable/inc/post_types.php` y `custom_taxonomies.php`

---

## Inventario de archivos

**A crear:**
- `wp-content/mu-plugins/udp-core/udp-core.php` — Loader del mu-plugin
- `wp-content/mu-plugins/udp-core/inc/post-types.php` — Registro de los 7 CPTs
- `wp-content/mu-plugins/udp-core/inc/taxonomies.php` — Registro de las 6 taxonomías existentes + 1 nueva (`tipo-contenido`)
- `wp-content/mu-plugins/udp-core/README.md` — Documentación del mu-plugin
- `wp-content/mu-plugins/udp-core-loader.php` — Loader en raíz que require el subdirectorio (WordPress no auto-carga mu-plugins en subcarpetas)
- 14 archivos JSON en `wp-content/themes/starter-theme/acf-json/`:
  - `group_cpt_post_meta.json`
  - `group_cpt_agenda_meta.json`
  - `group_cpt_calendario_meta.json`
  - `group_cpt_concurso_meta.json`
  - `group_cpt_carrera_meta.json`
  - `group_cpt_centro_meta.json`
  - `group_cpt_academico_meta.json`
  - `group_cpt_contacto_meta.json`
  - `group_tax_facultad_meta.json`
  - `group_tax_area_meta.json`
  - `group_options_general.json`
  - `group_options_header.json`
  - `group_options_footer.json`
  - `group_options_redes_sociales.json`

**A modificar:**
- `wp-content/themes/starter-theme/functions.php` — Registrar las 4 options pages nuevas (separadas) en lugar de la única "opciones-generales-del-sitio" actual

**A NO tocar (desactivar via UI ACF, no eliminar):**
- Grupos viejos en DB que se desactivan: `Color`, `Foto Taxonomía`, `Link Externo`, `Links Destacados`, `Slider Principal`, `Campos Para Filtros`, `Campos para Publicaciones` (los 7 explícitamente descartados)
- Grupos viejos que se reemplazan: los 13 restantes — desactivar después de validar que los nuevos funcionan (no en F1, en una fase posterior cuando todo esté validado)

**A NO tocar en absoluto:**
- `wp-content/themes/udp_portable/inc/post_types.php` y `custom_taxonomies.php` — los dejamos hasta F11 (switch local). Sí, hay registro duplicado de CPTs durante la transición, pero WordPress lo gestiona (el segundo `register_post_type` con el mismo slug sobrescribe args; ambos producen el mismo resultado funcional).

---

## Mapa de field keys reusados (referencia rápida)

| Old group key | Old group title | → New group |
|---|---|---|
| `group_609e938c231ec` | Campos para Entradas | `cpt_post_meta` (parte) |
| `group_60d0c173c9056` | campos para noticias | `cpt_post_meta` (parte) |
| `group_609e94a8c2128` | Campos para Agenda | `cpt_agenda_meta` |
| `group_63ce2547db95c` | Calendario Fields | `cpt_calendario_meta` |
| `group_60df304938af4` | Campos Concursos | `cpt_concurso_meta` |
| `group_60c9f1dd5c26e` | Campos Carreras | `cpt_carrera_meta` |
| `group_60d266f45c4a8` | Campos para Centros | `cpt_centro_meta` |
| `group_609e988a72f99` | Campos para Académicos | `cpt_academico_meta` |
| `group_60d4426c8954d` | Contact Fields | `cpt_contacto_meta` |
| `group_60c07f10246f3` | Color | `tax_facultad_meta` (campo `color`) |
| `group_60df345725011` | Foto Taxonomía | `tax_facultad_meta` + `tax_area_meta` |
| `group_60b9db2361b4c` | Campos Generales | `options_general` + `options_redes_sociales` (split) |
| `group_60bdca5f74353` | Menu Principal | `options_header` |

**Para extraer los field keys originales de cada grupo**:
```bash
jq '.[] | select(.key == "group_609e94a8c2128") | .fields[] | {name, key, type}' \
  /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/docs/superpowers/specs/acf-current-export.json
```

---

## Task 1: Crear scaffold del mu-plugin `udp-core`

**Files:**
- Create: `/Applications/MAMP/htdocs/udp/cms/wp-content/mu-plugins/udp-core-loader.php`
- Create: `/Applications/MAMP/htdocs/udp/cms/wp-content/mu-plugins/udp-core/udp-core.php`
- Create: `/Applications/MAMP/htdocs/udp/cms/wp-content/mu-plugins/udp-core/README.md`

WordPress NO auto-carga mu-plugins en subcarpetas — necesita un loader PHP en la raíz de `mu-plugins/` que haga `require` del archivo principal del subdirectorio.

- [ ] **Step 1: Crear el loader raíz**

Create `/Applications/MAMP/htdocs/udp/cms/wp-content/mu-plugins/udp-core-loader.php`:

```php
<?php
/**
 * UDP Core Loader
 *
 * WordPress no auto-carga mu-plugins en subcarpetas. Este loader hace require
 * del plugin principal en udp-core/.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/udp-core/udp-core.php';
```

- [ ] **Step 2: Crear el archivo principal del mu-plugin**

Create `/Applications/MAMP/htdocs/udp/cms/wp-content/mu-plugins/udp-core/udp-core.php`:

```php
<?php
/**
 * Plugin Name: UDP Core
 * Description: Capa de datos canónica del sitio UDP. Registra Custom Post Types, taxonomías y endpoints REST. Independiente del tema activo.
 * Version:     1.0.0
 * Author:      UDP / starter-theme migration
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'UDP_CORE_VERSION', '1.0.0' );
define( 'UDP_CORE_DIR', __DIR__ );
define( 'UDP_CORE_FILE', __FILE__ );

// Registro de tipos de contenido y taxonomías.
require_once UDP_CORE_DIR . '/inc/post-types.php';
require_once UDP_CORE_DIR . '/inc/taxonomies.php';
```

- [ ] **Step 3: Crear el README**

Create `/Applications/MAMP/htdocs/udp/cms/wp-content/mu-plugins/udp-core/README.md`:

```markdown
# UDP Core

Mu-plugin que provee la **capa de datos canónica** del sitio UDP:
- Custom Post Types: agenda, academico, carrera-udp, centro-udp, contacto-udp, concurso-academico, calendario
- Taxonomías: facultad, carrera, area, area-udp, publico-udp, tipo-udp, tipo-contenido

## Por qué mu-plugin y no theme

Los CPTs y taxonomías deben sobrevivir cualquier cambio de tema. Si viven en el tema, al cambiar de tema desaparecen y los registros quedan huérfanos en DB. Como mu-plugin, son siempre activos.

## Slugs intactos

Los slugs son **idénticos** a los que registraba `udp_portable/inc/post_types.php` y `custom_taxonomies.php` para no requerir migración de datos.

## Estructura

- `udp-core.php` — Loader principal
- `inc/post-types.php` — Registros de CPT
- `inc/taxonomies.php` — Registros de taxonomías

## Activación

Auto-cargado por WordPress vía `wp-content/mu-plugins/udp-core-loader.php` en la raíz de mu-plugins.
```

- [ ] **Step 4: Crear directorio `inc/`**

```bash
mkdir -p /Applications/MAMP/htdocs/udp/cms/wp-content/mu-plugins/udp-core/inc
```

Expected: directorio creado.

- [ ] **Step 5: Verificar que WP detecta el mu-plugin**

```bash
curl -s "http://localhost:8888/udp/" -o /dev/null -w "%{http_code}\n"
```

Expected: `200`. Si fuera `500` el mu-plugin tiene un error de PHP — revisar logs en `wp-content/debug.log` (WP_DEBUG ya está activo).

```bash
ls /Applications/MAMP/htdocs/udp/cms/wp-content/debug.log 2>/dev/null && tail -20 /Applications/MAMP/htdocs/udp/cms/wp-content/debug.log 2>/dev/null
```

Expected: si existe debug.log, no debe haber errores fatales recientes referenciando `udp-core`.

---

## Task 2: Mover registro de CPTs al mu-plugin

**Files:**
- Create: `/Applications/MAMP/htdocs/udp/cms/wp-content/mu-plugins/udp-core/inc/post-types.php`
- Reference: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/udp_portable/inc/post_types.php`

Traslado verbatim (con limpieza ligera) de las 7 funciones de registro de CPT del tema viejo al mu-plugin. **NO eliminamos las del tema viejo** — quedan duplicadas durante la transición; WordPress acepta `register_post_type` duplicado con mismo slug (el segundo sobreescribe args, ambos producen el mismo resultado funcional). Esto permite que el switcher de F0 siga funcionando con `udp_portable` activo.

- [ ] **Step 1: Crear `post-types.php` con los 7 CPTs**

Create `/Applications/MAMP/htdocs/udp/cms/wp-content/mu-plugins/udp-core/inc/post-types.php`:

```php
<?php
/**
 * Custom Post Types — UDP Core
 *
 * Mantiene los slugs del tema legacy (udp_portable) para no requerir
 * migración de datos.
 *
 * @package UDP_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra todos los CPTs en el hook init.
 */
add_action( 'init', 'udp_core_register_post_types', 0 );

function udp_core_register_post_types() {
	udp_core_register_agenda();
	udp_core_register_academico();
	udp_core_register_carrera();
	udp_core_register_centro();
	udp_core_register_contacto();
	udp_core_register_concurso();
	udp_core_register_calendario();
}

/**
 * CPT: Agenda (eventos)
 */
function udp_core_register_agenda() {
	register_post_type(
		'agenda',
		array(
			'label'              => __( 'Agenda', 'udp-core' ),
			'labels'             => array(
				'name'          => __( 'Agenda', 'udp-core' ),
				'singular_name' => __( 'Agenda', 'udp-core' ),
				'menu_name'     => __( 'Agenda', 'udp-core' ),
				'add_new_item'  => __( 'Nueva Agenda', 'udp-core' ),
				'edit_item'     => __( 'Editar Agenda', 'udp-core' ),
				'view_item'     => __( 'Ver Agenda', 'udp-core' ),
				'search_items'  => __( 'Buscar Agenda', 'udp-core' ),
				'not_found'     => __( 'No se encontró', 'udp-core' ),
			),
			'public'             => true,
			'show_in_rest'       => true,
			'show_in_menu'       => true,
			'show_in_admin_bar'  => true,
			'show_in_nav_menus'  => true,
			'menu_position'      => 5,
			'menu_icon'          => 'dashicons-calendar-alt',
			'supports'           => array( 'title', 'editor', 'thumbnail', 'revisions' ),
			'has_archive'        => true,
			'publicly_queryable' => true,
			'capability_type'    => 'page',
			'taxonomies'         => array( 'post_tag' ),
		)
	);
}

/**
 * CPT: Académico (profesorado)
 */
function udp_core_register_academico() {
	register_post_type(
		'academico',
		array(
			'label'              => __( 'Académico', 'udp-core' ),
			'labels'             => array(
				'name'          => __( 'Académico', 'udp-core' ),
				'singular_name' => __( 'Académico', 'udp-core' ),
				'menu_name'     => __( 'Académico', 'udp-core' ),
				'add_new_item'  => __( 'Nuevo Académico', 'udp-core' ),
				'edit_item'     => __( 'Editar Académico', 'udp-core' ),
				'view_item'     => __( 'Ver Académico', 'udp-core' ),
			),
			'public'             => true,
			'show_in_rest'       => true,
			'show_in_menu'       => true,
			'menu_position'      => 5,
			'menu_icon'          => 'dashicons-businessperson',
			'supports'           => array( 'title', 'editor', 'thumbnail', 'revisions' ),
			'has_archive'        => true,
			'publicly_queryable' => true,
			'capability_type'    => 'page',
		)
	);
}

/**
 * CPT: Carrera (carrera-udp)
 */
function udp_core_register_carrera() {
	register_post_type(
		'carrera-udp',
		array(
			'label'              => __( 'Carrera', 'udp-core' ),
			'labels'             => array(
				'name'          => __( 'Carreras', 'udp-core' ),
				'singular_name' => __( 'Carrera', 'udp-core' ),
				'menu_name'     => __( 'Carreras', 'udp-core' ),
				'add_new_item'  => __( 'Nueva Carrera', 'udp-core' ),
				'edit_item'     => __( 'Editar Carrera', 'udp-core' ),
			),
			'public'             => true,
			'show_in_rest'       => true,
			'show_in_menu'       => true,
			'menu_position'      => 5,
			'menu_icon'          => 'dashicons-welcome-learn-more',
			'supports'           => array( 'title', 'editor', 'thumbnail', 'revisions' ),
			'has_archive'        => true,
			'publicly_queryable' => true,
			'capability_type'    => 'page',
		)
	);
}

/**
 * CPT: Centro (centro-udp)
 */
function udp_core_register_centro() {
	register_post_type(
		'centro-udp',
		array(
			'label'              => __( 'Centro', 'udp-core' ),
			'labels'             => array(
				'name'          => __( 'Centros', 'udp-core' ),
				'singular_name' => __( 'Centro', 'udp-core' ),
				'menu_name'     => __( 'Centros', 'udp-core' ),
				'add_new_item'  => __( 'Nuevo Centro', 'udp-core' ),
				'edit_item'     => __( 'Editar Centro', 'udp-core' ),
			),
			'public'             => true,
			'show_in_rest'       => true,
			'show_in_menu'       => true,
			'menu_position'      => 5,
			'menu_icon'          => 'dashicons-building',
			'supports'           => array( 'title', 'editor', 'thumbnail', 'revisions' ),
			'has_archive'        => true,
			'publicly_queryable' => true,
			'capability_type'    => 'page',
		)
	);
}

/**
 * CPT: Contacto (contacto-udp) — envíos del formulario de contacto
 */
function udp_core_register_contacto() {
	register_post_type(
		'contacto-udp',
		array(
			'label'              => __( 'Contacto', 'udp-core' ),
			'labels'             => array(
				'name'          => __( 'Contactos', 'udp-core' ),
				'singular_name' => __( 'Contacto', 'udp-core' ),
				'menu_name'     => __( 'Contactos', 'udp-core' ),
			),
			'public'             => true,
			'show_in_rest'       => true,
			'show_in_menu'       => true,
			'menu_position'      => 5,
			'menu_icon'          => 'dashicons-email',
			'supports'           => array( 'title', 'editor', 'thumbnail' ),
			'has_archive'        => true,
			'publicly_queryable' => true,
			'capability_type'    => 'page',
		)
	);
}

/**
 * CPT: Concurso académico (concurso-academico)
 */
function udp_core_register_concurso() {
	register_post_type(
		'concurso-academico',
		array(
			'label'              => __( 'Concurso', 'udp-core' ),
			'labels'             => array(
				'name'          => __( 'Concursos', 'udp-core' ),
				'singular_name' => __( 'Concurso', 'udp-core' ),
				'menu_name'     => __( 'Concursos', 'udp-core' ),
			),
			'public'             => true,
			'show_in_rest'       => true,
			'show_in_menu'       => true,
			'menu_position'      => 5,
			'menu_icon'          => 'dashicons-awards',
			'supports'           => array( 'title', 'editor', 'thumbnail', 'revisions' ),
			'has_archive'        => true,
			'publicly_queryable' => true,
			'capability_type'    => 'page',
		)
	);
}

/**
 * CPT: Calendario académico (calendario)
 */
function udp_core_register_calendario() {
	register_post_type(
		'calendario',
		array(
			'label'              => __( 'Calendario', 'udp-core' ),
			'labels'             => array(
				'name'          => __( 'Calendario', 'udp-core' ),
				'singular_name' => __( 'Calendario', 'udp-core' ),
				'menu_name'     => __( 'Calendario', 'udp-core' ),
			),
			'public'             => true,
			'show_in_rest'       => true,
			'show_in_menu'       => true,
			'menu_position'      => 7,
			'menu_icon'          => 'dashicons-calendar',
			'supports'           => array( 'title', 'editor', 'thumbnail', 'revisions' ),
			'has_archive'        => true,
			'publicly_queryable' => true,
			'capability_type'    => 'page',
		)
	);
}
```

- [ ] **Step 2: Verificar sintaxis PHP**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/mu-plugins/udp-core/inc/post-types.php
```

Expected: `No syntax errors detected`.

---

## Task 3: Mover registro de taxonomías al mu-plugin

**Files:**
- Create: `/Applications/MAMP/htdocs/udp/cms/wp-content/mu-plugins/udp-core/inc/taxonomies.php`
- Reference: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/udp_portable/inc/custom_taxonomies.php`

Incluye las 6 taxonomías existentes (`facultad`, `carrera`, `area`, `area-udp`, `publico-udp`, `tipo-udp`) **+** la nueva `tipo-contenido` (reemplaza el field `politicas_publicas` que estaba duplicado en 3 CPTs).

- [ ] **Step 1: Crear `taxonomies.php`**

Create `/Applications/MAMP/htdocs/udp/cms/wp-content/mu-plugins/udp-core/inc/taxonomies.php`:

```php
<?php
/**
 * Taxonomías — UDP Core
 *
 * Mantiene los slugs del tema legacy + nueva taxonomía `tipo-contenido`
 * para reemplazar el field `politicas_publicas` (true_false) duplicado
 * en agenda/post/centro.
 *
 * @package UDP_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'udp_core_register_taxonomies', 0 );

function udp_core_register_taxonomies() {
	udp_core_register_facultad();
	udp_core_register_carrera_tax();
	udp_core_register_area();
	udp_core_register_area_udp();
	udp_core_register_publico_udp();
	udp_core_register_tipo_udp();
	udp_core_register_tipo_contenido();
}

/**
 * Taxonomía: Facultad (compartida)
 */
function udp_core_register_facultad() {
	register_taxonomy(
		'facultad',
		array( 'agenda', 'academico', 'post', 'carrera-udp', 'centro-udp', 'concurso-academico' ),
		array(
			'label'             => __( 'Facultad', 'udp-core' ),
			'labels'            => array(
				'name'          => __( 'Facultades', 'udp-core' ),
				'singular_name' => __( 'Facultad', 'udp-core' ),
				'menu_name'     => __( 'Facultades', 'udp-core' ),
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
		)
	);
}

/**
 * Taxonomía: Carrera (compartida — agenda/academico/post)
 */
function udp_core_register_carrera_tax() {
	register_taxonomy(
		'carrera',
		array( 'agenda', 'academico', 'post' ),
		array(
			'label'             => __( 'Carrera', 'udp-core' ),
			'labels'            => array(
				'name'          => __( 'Carreras', 'udp-core' ),
				'singular_name' => __( 'Carrera', 'udp-core' ),
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
		)
	);
}

/**
 * Taxonomía: Área (compartida)
 */
function udp_core_register_area() {
	register_taxonomy(
		'area',
		array( 'agenda', 'academico', 'post' ),
		array(
			'label'             => __( 'Área', 'udp-core' ),
			'labels'            => array(
				'name'          => __( 'Áreas', 'udp-core' ),
				'singular_name' => __( 'Área', 'udp-core' ),
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
		)
	);
}

/**
 * Taxonomía: Área UDP (solo calendario)
 */
function udp_core_register_area_udp() {
	register_taxonomy(
		'area-udp',
		array( 'calendario' ),
		array(
			'label'             => __( 'Área (UDP)', 'udp-core' ),
			'labels'            => array(
				'name'          => __( 'Áreas (UDP)', 'udp-core' ),
				'singular_name' => __( 'Área (UDP)', 'udp-core' ),
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
		)
	);
}

/**
 * Taxonomía: Público UDP (solo calendario)
 */
function udp_core_register_publico_udp() {
	register_taxonomy(
		'publico-udp',
		array( 'calendario' ),
		array(
			'label'             => __( 'Público', 'udp-core' ),
			'labels'            => array(
				'name'          => __( 'Públicos', 'udp-core' ),
				'singular_name' => __( 'Público', 'udp-core' ),
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
		)
	);
}

/**
 * Taxonomía: Tipo UDP (solo calendario)
 */
function udp_core_register_tipo_udp() {
	register_taxonomy(
		'tipo-udp',
		array( 'calendario' ),
		array(
			'label'             => __( 'Tipo (Calendario)', 'udp-core' ),
			'labels'            => array(
				'name'          => __( 'Tipos (Calendario)', 'udp-core' ),
				'singular_name' => __( 'Tipo', 'udp-core' ),
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
		)
	);
}

/**
 * Taxonomía NUEVA: Tipo de contenido (reemplaza politicas_publicas).
 * Aplica a agenda, post, centro-udp donde antes había el true_false.
 * Permite filtrar contenido por tipos en listings.
 */
function udp_core_register_tipo_contenido() {
	register_taxonomy(
		'tipo-contenido',
		array( 'agenda', 'post', 'centro-udp' ),
		array(
			'label'             => __( 'Tipo de contenido', 'udp-core' ),
			'labels'            => array(
				'name'          => __( 'Tipos de contenido', 'udp-core' ),
				'singular_name' => __( 'Tipo de contenido', 'udp-core' ),
				'menu_name'     => __( 'Tipos de contenido', 'udp-core' ),
				'add_new_item'  => __( 'Agregar tipo', 'udp-core' ),
			),
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
		)
	);
}
```

- [ ] **Step 2: Verificar sintaxis**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/mu-plugins/udp-core/inc/taxonomies.php
```

Expected: `No syntax errors detected`.

---

## Task 4: Verificar mu-plugin operativo y commit

**Files:** ninguno. Validación.

- [ ] **Step 1: Verificar que la home sigue cargando**

```bash
curl -s "http://localhost:8888/udp/" -o /dev/null -w "%{http_code}\n"
```

Expected: `200`.

- [ ] **Step 2: Verificar que los CPTs aparecen via REST API (los registra el mu-plugin con `show_in_rest: true`)**

```bash
curl -s "http://localhost:8888/udp/cms/wp-json/wp/v2/types" | python3 -c "import sys,json; d=json.load(sys.stdin); print('\n'.join(sorted(d.keys())))"
```

Expected: la lista incluye `agenda`, `academico`, `carrera-udp`, `centro-udp`, `contacto-udp`, `concurso-academico`, `calendario` (todos los CPT registrados por udp-core).

- [ ] **Step 3: Verificar que la nueva taxonomía `tipo-contenido` está registrada**

```bash
curl -s "http://localhost:8888/udp/cms/wp-json/wp/v2/taxonomies" | python3 -c "import sys,json; d=json.load(sys.stdin); print('\n'.join(sorted(d.keys())))"
```

Expected: incluye `tipo-contenido` además de las 6 existentes (`facultad`, `carrera`, `area`, `area-udp`, `publico-udp`, `tipo-udp`).

- [ ] **Step 4: Verificar que existen entradas en el CPT `agenda` (datos preservados)**

```bash
curl -s "http://localhost:8888/udp/cms/wp-json/wp/v2/agenda?per_page=3&_fields=id,title" | python3 -m json.tool
```

Expected: 3 entradas con id y title — confirma que los 3.626 registros siguen accesibles.

- [ ] **Step 5: Commit del mu-plugin**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/mu-plugins
git add udp-core-loader.php udp-core/
git commit -m "feat(udp-core): mu-plugin para CPTs y taxonomías canónicas

- Registra 7 CPTs (agenda, academico, carrera-udp, centro-udp,
  contacto-udp, concurso-academico, calendario) con slugs idénticos
  a los del tema viejo (sin migración de datos).
- Registra 6 taxonomías existentes (facultad, carrera, area, area-udp,
  publico-udp, tipo-udp).
- Añade nueva taxonomía tipo-contenido para reemplazar el field
  politicas_publicas duplicado en 3 CPTs.
- Sobrevive a cambio de tema futuro." 2>&1 | tail -3
```

Expected: commit exitoso.

---

## Task 5: Refactorizar Options pages en starter-theme

**Files:**
- Modify: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/inc/acf-setup.php` (probable)
- Si las options pages se registran en `functions.php`, modificar ahí.

El admin actual tiene UNA options page monolítica (`opciones-generales-del-sitio`) con todos los campos mezclados (logos, redes sociales, menú principal, etc.). Reorganizamos en 4 páginas separadas: General, Header & Mega-menú, Footer, Redes Sociales.

- [ ] **Step 1: Inspeccionar dónde se registran las options pages actuales en starter-theme**

```bash
grep -rn "acf_add_options_page\|acf_add_options_sub_page" /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/ 2>/dev/null | grep -v node_modules
```

Expected: localiza el archivo. Probablemente `inc/acf-setup.php` o `functions.php`.

- [ ] **Step 2: Reemplazar la options page única por las 4 nuevas**

Localizar el bloque actual (típicamente algo como `acf_add_options_page(['page_title' => 'Theme Options'])` o similar) y reemplazarlo por:

```php
add_action( 'acf/init', 'udp_register_options_pages' );

function udp_register_options_pages() {
	if ( ! function_exists( 'acf_add_options_page' ) ) {
		return;
	}

	// Página padre — solo navegación, sin campos propios.
	acf_add_options_page( array(
		'page_title' => __( 'Opciones del Sitio', 'starter-theme' ),
		'menu_title' => __( 'Opciones del Sitio', 'starter-theme' ),
		'menu_slug'  => 'udp-options',
		'capability' => 'edit_posts',
		'redirect'   => true,
		'icon_url'   => 'dashicons-admin-generic',
		'position'   => 2,
	) );

	// Sub-páginas temáticas.
	acf_add_options_sub_page( array(
		'page_title'  => __( 'General', 'starter-theme' ),
		'menu_title'  => __( 'General', 'starter-theme' ),
		'menu_slug'   => 'udp-options-general',
		'parent_slug' => 'udp-options',
		'capability'  => 'edit_posts',
	) );

	acf_add_options_sub_page( array(
		'page_title'  => __( 'Header & Mega-menú', 'starter-theme' ),
		'menu_title'  => __( 'Header', 'starter-theme' ),
		'menu_slug'   => 'udp-options-header',
		'parent_slug' => 'udp-options',
		'capability'  => 'edit_posts',
	) );

	acf_add_options_sub_page( array(
		'page_title'  => __( 'Footer', 'starter-theme' ),
		'menu_title'  => __( 'Footer', 'starter-theme' ),
		'menu_slug'   => 'udp-options-footer',
		'parent_slug' => 'udp-options',
		'capability'  => 'edit_posts',
	) );

	acf_add_options_sub_page( array(
		'page_title'  => __( 'Redes Sociales', 'starter-theme' ),
		'menu_title'  => __( 'Redes Sociales', 'starter-theme' ),
		'menu_slug'   => 'udp-options-redes-sociales',
		'parent_slug' => 'udp-options',
		'capability'  => 'edit_posts',
	) );
}
```

- [ ] **Step 3: Verificar via wp-admin que aparecen los 4 menús lateralmente**

Visitar `http://localhost:8888/udp/cms/wp-admin/` (logueado). En el sidebar izquierdo debe aparecer **"Opciones del Sitio"** con 4 sub-páginas: General, Header, Footer, Redes Sociales.

Expected: 4 sub-menús visibles. Cada uno abre una página vacía (sin field groups asignados todavía — eso lo cubren los siguientes tasks).

- [ ] **Step 4: Commit**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add inc/acf-setup.php functions.php  # ajustar según donde se hayan hecho los cambios
git commit -m "feat(acf): split monolithic options page into 4 thematic sub-pages

- Antes: 1 options page 'Opciones Generales del Sitio' con todos los
  campos mezclados (logos, RRSS, menu, slider, etc.).
- Ahora: General, Header, Footer, Redes Sociales (separadas).
- Editar un campo concreto requiere 1 click en lugar de scrollear 50." 2>&1 | tail -3
```

Expected: commit exitoso.

---

## Task 6: Crear ACF JSON para `cpt_post_meta` (fusión de 2 grupos viejos)

**Files:**
- Create: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_cpt_post_meta.json`
- Reference: `acf-current-export.json` grupos `group_609e938c231ec` (Campos para Entradas) y `group_60d0c173c9056` (campos para noticias)

Este es el grupo más complejo porque fusiona dos grupos viejos sobre `post`. Reusamos field keys originales para preservar data.

- [ ] **Step 1: Extraer field keys originales**

```bash
echo "=== Campos para Entradas ===" && jq '.[] | select(.key == "group_609e938c231ec") | .fields[] | {key, name, type}' /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/docs/superpowers/specs/acf-current-export.json
echo "=== campos para noticias ===" && jq '.[] | select(.key == "group_60d0c173c9056") | .fields[] | {key, name, type}' /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/docs/superpowers/specs/acf-current-export.json
```

Expected: lista los fields con sus keys. Anotar para usar en el JSON nuevo.

- [ ] **Step 2: Crear el JSON del grupo fusionado**

Create `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_cpt_post_meta.json`:

```json
{
    "key": "group_cpt_post_meta",
    "title": "Post — Metadatos",
    "fields": [
        {
            "key": "field_60d0c173c9XXX_tipo",
            "label": "Tipo de noticia",
            "name": "tipo",
            "type": "radio",
            "instructions": "Categoría editorial de la noticia (afecta visualización en listings).",
            "required": 0,
            "choices": {
                "general": "General",
                "destacado": "Destacado",
                "pregrado": "Pregrado",
                "postgrado": "Postgrado",
                "investigacion": "Investigación",
                "internacional": "Internacional",
                "egresados": "Egresados"
            },
            "allow_null": 1,
            "default_value": "general",
            "layout": "vertical",
            "return_format": "value"
        },
        {
            "key": "field_60d0c173c9XXX_epigrafe",
            "label": "Epígrafe",
            "name": "epigrafe",
            "type": "textarea",
            "instructions": "Texto corto sobre el título.",
            "rows": 2
        },
        {
            "key": "field_60d0c173c9XXX_extracto",
            "label": "Extracto",
            "name": "extracto",
            "type": "textarea",
            "instructions": "Resumen para listings y meta description.",
            "rows": 3
        },
        {
            "key": "field_609e938c231XX_bajada",
            "label": "Bajada",
            "name": "bajada",
            "type": "textarea",
            "rows": 3
        },
        {
            "key": "field_609e938c231XX_link_externo",
            "label": "Link Externo",
            "name": "link_externo",
            "type": "url",
            "instructions": "Si la noticia enlaza a un artículo externo, pega la URL aquí. Antes era tipo text — ahora url para validación."
        },
        {
            "key": "field_609e938c231XX_galeria_de_imagenes",
            "label": "Galería de Imágenes",
            "name": "galeria_de_imagenes",
            "type": "gallery",
            "return_format": "array",
            "library": "all"
        }
    ],
    "location": [
        [
            {
                "param": "post_type",
                "operator": "==",
                "value": "post"
            }
        ]
    ],
    "menu_order": 0,
    "position": "normal",
    "style": "default",
    "label_placement": "top",
    "instruction_placement": "label",
    "hide_on_screen": "",
    "active": true,
    "description": "Metadatos editoriales del post. Fusiona los dos grupos viejos (Campos para Entradas + campos para noticias).",
    "show_in_rest": 0
}
```

**⚠️ Sustituir los `XXX`** con los keys reales obtenidos del Step 1. Por ejemplo si `tipo` tenía key `field_60d0c173c9056_tipo_real`, usa ese key (los keys los ves en la salida del jq del Step 1).

**⚠️ Field `politicas_publicas` NO se incluye** — su data se migrará a la taxonomía `tipo-contenido` en Task 13.

- [ ] **Step 3: Validar JSON**

```bash
jq empty /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_cpt_post_meta.json && echo "JSON válido"
```

Expected: `JSON válido`.

---

## Task 7: Crear ACF JSON para `cpt_agenda_meta`

**Files:**
- Create: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_cpt_agenda_meta.json`
- Reference: `group_609e94a8c2128` (Campos para Agenda) en el export

- [ ] **Step 1: Extraer field keys originales**

```bash
jq '.[] | select(.key == "group_609e94a8c2128") | .fields[] | {key, name, type}' /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/docs/superpowers/specs/acf-current-export.json
```

Expected: 10 fields listados.

- [ ] **Step 2: Crear JSON copiando field keys del export**

Plantilla — sustituir `<KEY_X>` con los keys reales del Step 1:

```json
{
    "key": "group_cpt_agenda_meta",
    "title": "Agenda — Metadatos",
    "fields": [
        { "key": "<KEY_subtitulo>", "label": "Subtítulo", "name": "subtitulo", "type": "text" },
        { "key": "<KEY_invitados>", "label": "Invitados", "name": "invitados", "type": "text" },
        { "key": "<KEY_link>", "label": "Link", "name": "link", "type": "url" },
        { "key": "<KEY_fecha>", "label": "Fecha", "name": "fecha", "type": "date_picker", "display_format": "j F Y", "return_format": "j F Y", "first_day": 1 },
        { "key": "<KEY_hora_inicio>", "label": "Hora Inicio", "name": "hora_inicio", "type": "time_picker", "display_format": "H:i", "return_format": "H:i" },
        { "key": "<KEY_hora_termino>", "label": "Hora Término", "name": "hora_termino", "type": "time_picker", "display_format": "H:i", "return_format": "H:i" },
        { "key": "<KEY_lugar>", "label": "Lugar", "name": "lugar", "type": "text" },
        { "key": "<KEY_inscripciones>", "label": "Inscripciones", "name": "inscripciones", "type": "url" },
        { "key": "<KEY_dias_repetitivos>", "label": "Días repetitivos", "name": "dias_repetitivos", "type": "text", "instructions": "Para eventos recurrentes." }
    ],
    "location": [[{ "param": "post_type", "operator": "==", "value": "agenda" }]],
    "menu_order": 0,
    "position": "normal",
    "style": "default",
    "label_placement": "top",
    "instruction_placement": "label",
    "hide_on_screen": "",
    "active": true,
    "description": "Metadatos de evento de agenda. politicas_publicas se migra a tax tipo-contenido (Task 13)."
}
```

**⚠️ Field `politicas_publicas` excluido — migra a taxonomía.**

- [ ] **Step 3: Validar JSON**

```bash
jq empty /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_cpt_agenda_meta.json && echo "JSON válido"
```

---

## Task 8: Crear ACF JSON para `cpt_calendario_meta`

**Files:**
- Create: `acf-json/group_cpt_calendario_meta.json`
- Reference: `group_63ce2547db95c` (3 fields: fecha, fecha_amistosa, destacado)

- [ ] **Step 1: Extraer keys**

```bash
jq '.[] | select(.key == "group_63ce2547db95c") | .fields[] | {key, name, type}' /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/docs/superpowers/specs/acf-current-export.json
```

- [ ] **Step 2: Crear JSON. Renombrar `fecha_amistosa` → `fecha_legible` (label más claro)**

```json
{
    "key": "group_cpt_calendario_meta",
    "title": "Calendario — Metadatos",
    "fields": [
        { "key": "<KEY_fecha>", "label": "Fecha", "name": "fecha", "type": "date_picker", "display_format": "d/m/Y", "return_format": "d/m/Y", "first_day": 1 },
        { "key": "<KEY_fecha_amistosa>", "label": "Fecha legible", "name": "fecha_legible", "type": "text", "instructions": "Versión humana (ej. 'Lunes 27 de abril')." },
        { "key": "<KEY_destacado>", "label": "Destacado", "name": "destacado", "type": "true_false", "ui": 1 }
    ],
    "location": [[{ "param": "post_type", "operator": "==", "value": "calendario" }]],
    "menu_order": 0,
    "position": "normal",
    "style": "default",
    "label_placement": "top",
    "active": true
}
```

**⚠️ Renombrar `fecha_amistosa` → `fecha_legible` requiere migración manual de meta_keys** (ver Task 14). El field NAME (slug) cambia, no el key.

Reconsideración: para evitar la migración, mantener `fecha_amistosa` como `name` y solo cambiar el `label`. Eso preserva 100% la data sin nada que migrar.

**Decisión final**: mantener `name: "fecha_amistosa"` y solo cambiar `label: "Fecha legible"` en la UI. Más simple, sin riesgo.

Re-create:

```json
{
    "key": "group_cpt_calendario_meta",
    "title": "Calendario — Metadatos",
    "fields": [
        { "key": "<KEY_fecha>", "label": "Fecha", "name": "fecha", "type": "date_picker", "display_format": "d/m/Y", "return_format": "d/m/Y", "first_day": 1 },
        { "key": "<KEY_fecha_amistosa>", "label": "Fecha legible", "name": "fecha_amistosa", "type": "text", "instructions": "Versión humana (ej. 'Lunes 27 de abril')." },
        { "key": "<KEY_destacado>", "label": "Destacado", "name": "destacado", "type": "true_false", "ui": 1 }
    ],
    "location": [[{ "param": "post_type", "operator": "==", "value": "calendario" }]],
    "menu_order": 0,
    "position": "normal",
    "style": "default",
    "label_placement": "top",
    "active": true
}
```

- [ ] **Step 3: Validar**

```bash
jq empty /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_cpt_calendario_meta.json && echo "JSON válido"
```

---

## Task 9: Crear ACF JSON para los 5 grupos CPT restantes

**Files (5):**
- `acf-json/group_cpt_concurso_meta.json` (← group_60df304938af4 — 1 field)
- `acf-json/group_cpt_carrera_meta.json` (← group_60c9f1dd5c26e — 5 fields)
- `acf-json/group_cpt_centro_meta.json` (← group_60d266f45c4a8 — 2 fields, sin politicas_publicas)
- `acf-json/group_cpt_academico_meta.json` (← group_609e988a72f99 — 9 fields, fixing email type)
- `acf-json/group_cpt_contacto_meta.json` (← group_60d4426c8954d — 4 fields, fixing email + mensaje types)

Patrón repetido: extraer keys del export, copiar fields al JSON nuevo manteniendo names/keys, aplicar las correcciones de tipo aprobadas en el spec.

- [ ] **Step 1: Crear `group_cpt_concurso_meta.json`**

```bash
jq '.[] | select(.key == "group_60df304938af4") | .fields[] | {key, name, type}' /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/docs/superpowers/specs/acf-current-export.json
```

Crear el archivo con 1 field (`archivo_concurso` tipo file). Estructura igual a Tasks 7-8.

- [ ] **Step 2: Crear `group_cpt_carrera_meta.json`**

```bash
jq '.[] | select(.key == "group_60c9f1dd5c26e") | .fields[] | {key, name, type}' /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/docs/superpowers/specs/acf-current-export.json
```

5 fields: `atributos` (repeater), `url_admision` (url), `url_facultad` (url), `links` (repeater — corregir label "LInks" → "Links"), `link_directo` (url).

- [ ] **Step 3: Crear `group_cpt_centro_meta.json`**

```bash
jq '.[] | select(.key == "group_60d266f45c4a8") | .fields[] | {key, name, type}' /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/docs/superpowers/specs/acf-current-export.json
```

1 field: `link_externo` (url, ya estaba bien). **Excluir `politicas_publicas`** (migra a taxonomía).

- [ ] **Step 4: Crear `group_cpt_academico_meta.json`**

```bash
jq '.[] | select(.key == "group_609e988a72f99") | .fields[] | {key, name, type}' /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/docs/superpowers/specs/acf-current-export.json
```

9 fields. **Cambio de tipo**: `email` debe ser `type: "email"` (estaba como `text`). Resto idéntico.

- [ ] **Step 5: Crear `group_cpt_contacto_meta.json`**

```bash
jq '.[] | select(.key == "group_60d4426c8954d") | .fields[] | {key, name, type}' /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/docs/superpowers/specs/acf-current-export.json
```

4 fields. **Cambios de tipo**: `email` → `type: "email"`, `mensaje` → `type: "textarea"` (estaba como text).

- [ ] **Step 6: Validar todos**

```bash
for f in /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_cpt_*.json; do
  jq empty "$f" 2>&1 | grep -v "^$" || echo "$(basename $f) OK"
done
```

Expected: cada archivo reporta "OK".

---

## Task 10: Crear ACF JSON para `tax_facultad_meta` y `tax_area_meta`

**Files:**
- Create: `acf-json/group_tax_facultad_meta.json`
- Create: `acf-json/group_tax_area_meta.json`
- Reference: `group_60c07f10246f3` (Color) + `group_60df345725011` (Foto Taxonomía)

El campo `color` que actualmente está en el grupo `Color` (asignado a post/page/all-taxonomy) se restringe SOLO a la taxonomía `facultad` (que es el único uso real). Más el `imagen_taxonomia` que estaba en `Foto Taxonomía` (taxonomy == all) se split en facultad + area.

- [ ] **Step 1: Extraer keys originales**

```bash
echo "=== Color ===" && jq '.[] | select(.key == "group_60c07f10246f3") | .fields[] | {key, name, type}' /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/docs/superpowers/specs/acf-current-export.json
echo "=== Foto Taxonomía ===" && jq '.[] | select(.key == "group_60df345725011") | .fields[] | {key, name, type}' /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/docs/superpowers/specs/acf-current-export.json
```

- [ ] **Step 2: Crear `group_tax_facultad_meta.json`**

```json
{
    "key": "group_tax_facultad_meta",
    "title": "Facultad — Metadatos",
    "fields": [
        {
            "key": "<KEY_color_original_del_grupo_Color>",
            "label": "Color de facultad",
            "name": "color",
            "type": "color_picker",
            "instructions": "Color identificativo de esta facultad. Se inyecta como CSS custom property --faculty-color en cards y plantillas relacionadas.",
            "return_format": "string"
        },
        {
            "key": "<KEY_imagen_taxonomia_del_grupo_FotoTax>",
            "label": "Imagen de la facultad",
            "name": "imagen_taxonomia",
            "type": "image",
            "return_format": "url",
            "library": "all"
        }
    ],
    "location": [[{ "param": "taxonomy", "operator": "==", "value": "facultad" }]],
    "menu_order": 0,
    "position": "normal",
    "style": "default",
    "label_placement": "top",
    "active": true,
    "description": "Color y foto representativa de cada facultad. El color alimenta el sistema cromático del front."
}
```

- [ ] **Step 3: Crear `group_tax_area_meta.json`**

```json
{
    "key": "group_tax_area_meta",
    "title": "Área — Metadatos",
    "fields": [
        {
            "key": "field_tax_area_imagen",
            "label": "Imagen del área",
            "name": "imagen_taxonomia",
            "type": "image",
            "return_format": "url",
            "library": "all"
        }
    ],
    "location": [
        [{ "param": "taxonomy", "operator": "==", "value": "area" }],
        [{ "param": "taxonomy", "operator": "==", "value": "area-udp" }],
        [{ "param": "taxonomy", "operator": "==", "value": "carrera" }]
    ],
    "menu_order": 0,
    "position": "normal",
    "style": "default",
    "label_placement": "top",
    "active": true,
    "description": "Imagen para términos de las taxonomías 'area' y 'area-udp'. Field key NUEVO (no había antes uso específico)."
}
```

**Nota**: para `tax_area_meta` usamos field key NUEVO (`field_tax_area_imagen`) porque el `imagen_taxonomia` original estaba asignado a `taxonomy == all` (todas), incluyendo facultades. Si copiásemos el mismo key a las dos taxonomías, ACF se confundiría. Los términos de area/carrera que actualmente tienen una imagen guardada bajo el key viejo seguirán mostrándola via fallback de `get_field()`, pero idealmente se reasignan después (poco volumen).

- [ ] **Step 4: Validar**

```bash
for f in /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_tax_*.json; do
  jq empty "$f" && echo "$(basename $f) OK"
done
```

---

## Task 11: Crear ACF JSON para `options_general` y `options_redes_sociales` (split de "Campos Generales")

**Files:**
- Create: `acf-json/group_options_general.json`
- Create: `acf-json/group_options_redes_sociales.json`
- Reference: `group_60b9db2361b4c` (Campos Generales — 17 fields)

El grupo "Campos Generales" actual mezcla logos + redes sociales + popup. Lo split: redes sociales a su propia options page, resto a "general".

- [ ] **Step 1: Extraer keys**

```bash
jq '.[] | select(.key == "group_60b9db2361b4c") | .fields[] | {key, name, type}' /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/docs/superpowers/specs/acf-current-export.json
```

- [ ] **Step 2: Crear `group_options_general.json`** con los fields no-sociales: logos (logo_blanco, logo_color, logo_udp, logo_acreditacion), link_acreditacion, correo, popup (mostrar_popup, popup_link, popup_imagen_desktop, popup_imagen_mobile)

```json
{
    "key": "group_options_general",
    "title": "Opciones — General",
    "fields": [
        { "key": "<KEY_logo_color>", "label": "Logo Color", "name": "logo_color", "type": "image", "return_format": "url" },
        { "key": "<KEY_logo_blanco>", "label": "Logo Blanco", "name": "logo_blanco", "type": "image", "return_format": "url" },
        { "key": "<KEY_logo_udp>", "label": "Logo UDP", "name": "logo_udp", "type": "image", "return_format": "url" },
        { "key": "<KEY_logo_acreditacion>", "label": "Logo Acreditación", "name": "logo_acreditacion", "type": "image", "return_format": "url" },
        { "key": "<KEY_link_acreditacion>", "label": "Link Acreditación", "name": "link_acreditacion", "type": "url" },
        { "key": "<KEY_correo>", "label": "Correo de contacto", "name": "correo", "type": "email" },
        { "key": "<KEY_mostrar_popup>", "label": "Mostrar popup", "name": "mostrar_popup", "type": "true_false", "ui": 1 },
        { "key": "<KEY_popup_link>", "label": "Popup link", "name": "popup_link", "type": "url" },
        { "key": "<KEY_popup_imagen_desktop>", "label": "Popup imagen desktop", "name": "popup_imagen_desktop", "type": "image", "return_format": "url" },
        { "key": "<KEY_popup_imagen_mobile>", "label": "Popup imagen mobile", "name": "popup_imagen_mobile", "type": "image", "return_format": "url" }
    ],
    "location": [[{ "param": "options_page", "operator": "==", "value": "udp-options-general" }]],
    "menu_order": 0,
    "position": "normal",
    "style": "default",
    "label_placement": "top",
    "active": true
}
```

- [ ] **Step 3: Crear `group_options_redes_sociales.json`** con: facebook, twitter, instagram, youtube, linkedin, tiktok, tiktok_logo

```json
{
    "key": "group_options_redes_sociales",
    "title": "Opciones — Redes Sociales",
    "fields": [
        { "key": "<KEY_facebook>", "label": "Facebook", "name": "facebook", "type": "url" },
        { "key": "<KEY_twitter>", "label": "Twitter / X", "name": "twitter", "type": "url" },
        { "key": "<KEY_instagram>", "label": "Instagram", "name": "instagram", "type": "url" },
        { "key": "<KEY_youtube>", "label": "YouTube", "name": "youtube", "type": "url" },
        { "key": "<KEY_linkedin>", "label": "LinkedIn", "name": "linkedin", "type": "url" },
        { "key": "<KEY_tiktok>", "label": "TikTok", "name": "tiktok", "type": "url" },
        { "key": "<KEY_tiktok_logo>", "label": "TikTok logo", "name": "tiktok_logo", "type": "image", "return_format": "url" }
    ],
    "location": [[{ "param": "options_page", "operator": "==", "value": "udp-options-redes-sociales" }]],
    "menu_order": 0,
    "position": "normal",
    "style": "default",
    "label_placement": "top",
    "active": true
}
```

- [ ] **Step 4: Validar**

```bash
for f in /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_options_general.json /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_options_redes_sociales.json; do
  jq empty "$f" && echo "$(basename $f) OK"
done
```

---

## Task 12: Crear ACF JSON para `options_header` y `options_footer`

**Files:**
- Create: `acf-json/group_options_header.json`
- Create: `acf-json/group_options_footer.json`
- Reference: `group_60bdca5f74353` (Menu Principal — 2 fields incluyendo el repeater enorme del menu)

`options_header` reusa el repeater `menu_principal` del grupo viejo "Menu Principal" pero **descarta** el field `mensaje_sobre_medidas_por_covid` (obsoleto).

`options_footer` se crea desde cero con campos sensatos (columnas de links + copyright).

- [ ] **Step 1: Extraer keys del menu**

```bash
jq '.[] | select(.key == "group_60bdca5f74353") | .fields[] | {key, name, type}' /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/docs/superpowers/specs/acf-current-export.json
```

Anotar el key de `menu_principal` (el repeater).

- [ ] **Step 2: Crear `group_options_header.json`** reusando el repeater `menu_principal` con su sub-structure tal cual

Plantilla (sustituir `<KEY_menu_principal>` con el real, y los keys de los sub-fields del repeater anidado del export):

```json
{
    "key": "group_options_header",
    "title": "Opciones — Header & Mega-menú",
    "fields": [
        {
            "key": "<KEY_menu_principal_repeater>",
            "label": "Mega-menú principal",
            "name": "menu_principal",
            "type": "repeater",
            "instructions": "Items del mega-menú con submenu, imagen y links externos opcionales.",
            "min": 0,
            "max": 0,
            "layout": "block",
            "button_label": "Agregar item",
            "sub_fields": [
                "<COPIA-VERBATIM los sub_fields del repeater original — ver export para keys + structure>"
            ]
        }
    ],
    "location": [[{ "param": "options_page", "operator": "==", "value": "udp-options-header" }]],
    "menu_order": 0,
    "position": "normal",
    "style": "default",
    "label_placement": "top",
    "active": true,
    "description": "Configuración del mega-menú. NO incluye el field mensaje_sobre_medidas_por_covid (obsoleto)."
}
```

**Para los `sub_fields` del repeater menu_principal**: extraerlos directamente del export con jq y pegarlos en su totalidad. Ejemplo:

```bash
jq '.[] | select(.key == "group_60bdca5f74353") | .fields[] | select(.name == "menu_principal") | .sub_fields' \
  /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/docs/superpowers/specs/acf-current-export.json > /tmp/menu_subfields.json
```

Luego copiar el contenido de `/tmp/menu_subfields.json` dentro del array `sub_fields` del JSON nuevo.

- [ ] **Step 3: Crear `group_options_footer.json`** desde cero

```json
{
    "key": "group_options_footer",
    "title": "Opciones — Footer",
    "fields": [
        {
            "key": "field_options_footer_columnas",
            "label": "Columnas del footer",
            "name": "columnas_footer",
            "type": "repeater",
            "min": 0,
            "max": 5,
            "layout": "block",
            "button_label": "Agregar columna",
            "sub_fields": [
                { "key": "field_options_footer_col_titulo", "label": "Título", "name": "titulo", "type": "text" },
                {
                    "key": "field_options_footer_col_links",
                    "label": "Links",
                    "name": "links",
                    "type": "repeater",
                    "layout": "table",
                    "sub_fields": [
                        { "key": "field_options_footer_col_link_label", "label": "Label", "name": "label", "type": "text" },
                        { "key": "field_options_footer_col_link_url", "label": "URL", "name": "url", "type": "url" }
                    ]
                }
            ]
        },
        { "key": "field_options_footer_copyright", "label": "Copyright", "name": "copyright", "type": "text", "default_value": "© Universidad Diego Portales" },
        {
            "key": "field_options_footer_legal_links",
            "label": "Links legales",
            "name": "legal_links",
            "type": "repeater",
            "layout": "table",
            "sub_fields": [
                { "key": "field_options_footer_legal_label", "label": "Label", "name": "label", "type": "text" },
                { "key": "field_options_footer_legal_url", "label": "URL", "name": "url", "type": "url" }
            ]
        }
    ],
    "location": [[{ "param": "options_page", "operator": "==", "value": "udp-options-footer" }]],
    "menu_order": 0,
    "position": "normal",
    "style": "default",
    "label_placement": "top",
    "active": true,
    "description": "Estructura del footer. Field keys NUEVOS (no existían antes — el footer del tema viejo estaba hardcoded)."
}
```

- [ ] **Step 4: Validar**

```bash
for f in /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_options_header.json /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/group_options_footer.json; do
  jq empty "$f" && echo "$(basename $f) OK"
done
```

---

## Task 13: Sincronizar todos los grupos nuevos via ACF admin

**Files:** ninguno. Acción manual via UI.

ACF Pro detecta archivos nuevos en `acf-json/` y muestra un link "Sync available". Al hacer click, importa el grupo a la DB.

- [ ] **Step 1: Listar archivos creados**

```bash
ls -la /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/*.json | wc -l
```

Expected: 14 archivos (8 cpt + 2 tax + 4 options).

- [ ] **Step 2: Visitar wp-admin como admin**

Ir a: `http://localhost:8888/udp/cms/wp-admin/edit.php?post_type=acf-field-group`

Expected: en la parte superior aparece el banner **"Sync available — 14 field group(s) available to import"** con un link "Sync changes".

- [ ] **Step 3: Sincronizar todos**

Click en "Bulk Actions" → "Select all" → "Sync changes".

Expected: redirige y muestra los 14 grupos nuevos en el listado, con el badge "JSON".

- [ ] **Step 4: Verificar via SQL que los grupos están activos**

```bash
/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot -proot udp -e "SELECT post_title, post_name FROM wp_fnku4yposts WHERE post_type='acf-field-group' AND post_status='publish' AND post_name LIKE 'group_cpt_%' OR post_name LIKE 'group_tax_%' OR post_name LIKE 'group_options_%' ORDER BY post_title;"
```

Expected: lista los 14 grupos nuevos.

---

## Task 14: Migrar `politicas_publicas` (true_false) a taxonomía `tipo-contenido`

**Files:** Acción de DB vía SQL.

Los registros que tienen `politicas_publicas = 1` deben quedar asignados al término `politicas-publicas` de la taxonomía nueva `tipo-contenido`.

- [ ] **Step 1: Crear el término `politicas-publicas` en la taxonomía nueva**

Vía wp-admin: `Tipos de contenido` → "Add New" → Name: "Políticas Públicas" → Slug: `politicas-publicas` → Add.

O vía wp-cli/SQL:

```bash
/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot -proot udp <<'SQL'
INSERT INTO wp_fnku4yterms (name, slug, term_group) VALUES ('Políticas Públicas', 'politicas-publicas', 0);
SET @new_term_id = LAST_INSERT_ID();
INSERT INTO wp_fnku4yterm_taxonomy (term_id, taxonomy, description, parent, count) VALUES (@new_term_id, 'tipo-contenido', '', 0, 0);
SELECT @new_term_id AS term_id, LAST_INSERT_ID() AS term_taxonomy_id;
SQL
```

Expected: devuelve `term_id` y `term_taxonomy_id` del nuevo término. Anotar `term_taxonomy_id` para los pasos siguientes.

- [ ] **Step 2: Buscar todos los posts con `politicas_publicas = 1` (en post, agenda, centro-udp)**

```bash
/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot -proot udp -e "SELECT pm.post_id, p.post_type, p.post_title FROM wp_fnku4ypostmeta pm JOIN wp_fnku4yposts p ON p.ID = pm.post_id WHERE pm.meta_key = 'politicas_publicas' AND pm.meta_value = '1' AND p.post_type IN ('post','agenda','centro-udp') AND p.post_status = 'publish' LIMIT 20;"
```

Expected: lista de post_ids con políticas públicas activadas. Cuenta total para tener referencia:

```bash
/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot -proot udp -e "SELECT p.post_type, COUNT(*) FROM wp_fnku4ypostmeta pm JOIN wp_fnku4yposts p ON p.ID = pm.post_id WHERE pm.meta_key = 'politicas_publicas' AND pm.meta_value = '1' AND p.post_type IN ('post','agenda','centro-udp') AND p.post_status = 'publish' GROUP BY p.post_type;"
```

- [ ] **Step 3: Asignar el término a esos posts**

⚠️ **Sustituir `<TERM_TAXONOMY_ID>`** con el valor obtenido en Step 1.

```bash
/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot -proot udp <<'SQL'
INSERT INTO wp_fnku4yterm_relationships (object_id, term_taxonomy_id, term_order)
SELECT pm.post_id, <TERM_TAXONOMY_ID>, 0
FROM wp_fnku4ypostmeta pm
JOIN wp_fnku4yposts p ON p.ID = pm.post_id
WHERE pm.meta_key = 'politicas_publicas'
  AND pm.meta_value = '1'
  AND p.post_type IN ('post','agenda','centro-udp')
  AND p.post_status IN ('publish','private','draft');

-- Actualizar el count del término.
UPDATE wp_fnku4yterm_taxonomy SET count = (
  SELECT COUNT(*) FROM wp_fnku4yterm_relationships WHERE term_taxonomy_id = <TERM_TAXONOMY_ID>
) WHERE term_taxonomy_id = <TERM_TAXONOMY_ID>;
SQL
```

Expected: insertados N registros (matching count del Step 2).

- [ ] **Step 4: Verificar via REST que la taxonomía está asignada**

```bash
curl -s "http://localhost:8888/udp/cms/wp-json/wp/v2/posts?tipo-contenido=<TERM_ID>&per_page=3&_fields=id,title,tipo-contenido" | python3 -m json.tool
```

Expected: posts retornados con el term_id en el array de la taxonomía.

- [ ] **Step 5: NO eliminar los meta_keys `politicas_publicas`** todavía

Mantener la data de meta `politicas_publicas` durante la transición. Eliminar definitivamente queda para una fase posterior cuando se haya validado todo el sistema (post-F11).

---

## Task 15: Desactivar los 7 grupos ACF obsoletos

**Files:** Acción manual via wp-admin.

Los 7 grupos descartados explícitamente en el spec se desactivan (no se borran). Esto los oculta de la UI pero preserva la posibilidad de rollback.

- [ ] **Step 1: Acceder al listado de field groups**

Visitar: `http://localhost:8888/udp/cms/wp-admin/edit.php?post_type=acf-field-group`

- [ ] **Step 2: Desactivar uno por uno los siguientes 7 grupos**

Para cada grupo: hover sobre el título → "Edit" → en el sidebar derecho cambiar "Active" a "No" → "Update".

Lista de grupos a desactivar (con su key para identificarlos sin ambigüedad):

| Título | Key |
|---|---|
| Color | `group_60c07f10246f3` |
| Foto Taxonomía | `group_60df345725011` |
| Link Externo | `group_61536a8e8b2b0` |
| Links Destacados | `group_60c1bc47cd7dc` |
| Slider Principal | `group_60cc69865fdba` |
| Campos Para Filtros | `group_60c9f88ec842d` |
| Campos para Publicaciones | `group_60df31c1b3671` |

- [ ] **Step 3: Verificar via SQL que están como inactivos**

```bash
/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot -proot udp -e "SELECT post_title, post_status FROM wp_fnku4yposts WHERE post_type='acf-field-group' AND post_name IN ('group_60c07f10246f3','group_60df345725011','group_61536a8e8b2b0','group_60c1bc47cd7dc','group_60cc69865fdba','group_60c9f88ec842d','group_60df31c1b3671');"
```

Expected: los 7 con `post_status='acf-disabled'` (o similar — ACF guarda inactivos con un status distinto a 'publish').

---

## Task 16: Verificación end-to-end + cierre de F1

**Files:**
- Modify: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/MEMORY.md`

- [ ] **Step 1: Verificar que el sitio sigue cargando con el tema viejo activo**

```bash
curl -s -o /dev/null -w "%{http_code}\n" "http://localhost:8888/udp/"
```

Expected: `200`.

- [ ] **Step 2: Verificar que `?theme=new` también funciona**

```bash
curl -s "http://localhost:8888/udp/?theme=new&nocache=$(date +%s)" | grep -oE "wp-content/themes/[a-z_-]+" | sort -u
```

Expected: `wp-content/themes/starter-theme`.

- [ ] **Step 3: Verificar conteo final de grupos ACF activos**

```bash
/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot -proot udp -e "SELECT COUNT(*) AS total_active FROM wp_fnku4yposts WHERE post_type='acf-field-group' AND post_status='publish';"
```

Expected: ~14 grupos nuevos + grupos viejos NO descartados ni desactivados (los que se reemplazan se mantienen activos hasta una fase posterior, lo que da ~14 + 13 = 27 activos).

- [ ] **Step 4: Test funcional via REST con un post real**

```bash
POST_ID=$(/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot -proot udp -N -e "SELECT pm.post_id FROM wp_fnku4ypostmeta pm WHERE pm.meta_key='bajada' AND pm.meta_value != '' LIMIT 1;")
echo "Test con post_id=$POST_ID"
curl -s "http://localhost:8888/udp/cms/wp-json/wp/v2/posts/$POST_ID?_fields=id,title,acf" | python3 -m json.tool | head -30
```

Expected: el post devuelve sus ACF fields (incluyendo `bajada` con su valor original).

- [ ] **Step 5: Actualizar MEMORY.md**

Add to `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/MEMORY.md` (al final):

```markdown
### 2026-XX-XX — F1 udp-core + ACF refactor completada

**Hechos**:
- Mu-plugin `udp-core` operativo en `wp-content/mu-plugins/udp-core/` con loader `udp-core-loader.php` en raíz.
- 7 CPTs registrados (slugs intactos): agenda, academico, carrera-udp, centro-udp, contacto-udp, concurso-academico, calendario.
- 6 taxonomías existentes registradas (facultad, carrera, area, area-udp, publico-udp, tipo-udp) + 1 nueva: `tipo-contenido` (reemplaza el field politicas_publicas duplicado en 3 CPTs).
- 14 grupos ACF nuevos en `acf-json/`: 8 CPT meta + 2 tax meta + 4 options. Reusan field keys originales para preservar 22.000+ registros.
- Options pages reorganizadas: 4 sub-páginas temáticas (General, Header, Footer, Redes Sociales) reemplazan la única "opciones-generales-del-sitio".
- 7 grupos ACF descartados desactivados (Color, Foto Taxonomía, Link Externo, Links Destacados, Slider Principal, Campos Para Filtros, Campos para Publicaciones).
- Migración de datos `politicas_publicas` (true_false) → término "Políticas Públicas" de la taxonomía nueva `tipo-contenido`.

**Pendientes**:
- Los 13 grupos viejos NO descartados (CPT meta + Menu Principal + Campos Generales + 2 flexible_content) siguen activos. Desactivar después de F11 cuando se valide que los nuevos cubren todo.
- Los 15 grupos `block_*` del catálogo se autoran cuando se construya cada bloque (F3+).
- Plugin `portable__plugin_ws` aún instalado y NO activo. Desinstalar en F11.
- Eliminar meta_keys `politicas_publicas` cuando se valide la taxonomía en producción (post-F11).

**Cosas que descubrí en el proceso**:
- ACF Pro requiere "Sync" manual desde admin para importar grupos de `acf-json/` (no auto-importa).
- Los CPTs registrados duplicados (en theme + mu-plugin) no causan errores: el segundo `register_post_type` con mismo slug sobrescribe args silenciosamente.
- Field keys son la única forma de preservar data — los names/labels se pueden cambiar sin pérdida.
```

- [ ] **Step 6: Commit final**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add acf-json/ MEMORY.md docs/superpowers/plans/2026-04-27-f1-udp-core-and-acf-refactor.md inc/acf-setup.php functions.php
git status
git commit -m "feat(acf): F1 — 14 grupos ACF reorganizados en acf-json/

- 8 CPT meta groups (preservando field keys del export para no perder data).
- 2 tax meta groups (facultad con color, area con imagen).
- 4 options groups (General, Header, Footer, Redes Sociales — split del monolito).
- politicas_publicas migrado a taxonomía tipo-contenido.
- 7 grupos viejos descartados desactivados (no eliminados, permite rollback).
- 13 grupos viejos restantes siguen activos hasta validación post-F11.
- Bloques flexible content (15) quedan deferred a sus fases de creación (F3+)." 2>&1 | tail -3
```

Expected: commit exitoso.

---

## Coverage check vs. spec

Verificación de que cada elemento de F1 en `2026-04-27-migracion-udp-portable-a-starter-theme-design.md` (sección 6.2) tiene un task que lo cubre:

| Spec F1 deliverable | Task(s) |
|---|---|
| Crear mu-plugin `udp-core` | Task 1 |
| Mover registro de CPTs desde `udp_portable/inc/` (slugs intactos) | Task 2 |
| Mover taxonomías desde `udp_portable/inc/` | Task 3 |
| Test: con tema viejo activo, datos siguen funcionando | Task 4 (REST API checks) + Task 16 |
| Exportar 20 grupos ACF actuales | YA HECHO antes de F1 (`acf-current-export.json`) |
| Reorganizar a la lista de 5.4 → guardar en `acf-json/` | Tasks 6-12 (14 grupos no-block) |
| Bloques `block_*` (15) | **DEFERRED a F3+** — documentado en plan header |
| Nueva options pages (4 separadas) | Task 5 |
| Nueva taxonomía `tipo-contenido` | Task 3 + Task 14 |
| Migración data `politicas_publicas` | Task 14 |
| Desactivar 7 grupos descartados | Task 15 |

**Cobertura completa de los deliverables de F1.** Los 15 grupos `block_*` quedan formalmente fuera de F1 (justificado en el header — premature optimization crearlos antes de los bloques).
