# F0 — Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Dejar el entorno listo para empezar a construir `starter-theme` sobre el WordPress de UDP, con un mu-plugin que permita previsualizarlo vía `?theme=new` sin tocar el tema activo.

**Architecture:** Backup defensivo de DB → repos git inicializados (theme + mu-plugins) → toolchain Vite validada → mu-plugin `udp-theme-switcher` operativo → fuentes WOFF2 cargadas en SCSS → carpeta `acf-json/` lista para F1.

**Tech Stack:** WordPress 6.x, MAMP (PHP/MySQL local), ACF Pro, Vite 6, Bootstrap 5, sass-embedded, Homebrew (woff2), npm, git.

**Note on worktrees:** Esta fase NO usa worktree git. La instalación de WordPress vive en una ruta concreta servida por MAMP (`/Applications/MAMP/htdocs/udp/cms`). Los cambios se aplican in-place sobre el tema y el mu-plugin.

---

## Inventario de archivos

**A crear:**
- `~/Backups/udp/udp-pre-migration-2026-04-27.sql` — dump de seguridad de la DB
- `/Applications/MAMP/htdocs/udp/cms/wp-content/mu-plugins/udp-theme-switcher.php` — mu-plugin de previsualización
- `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/utilities/_typography.scss` — `@font-face` declarations
- `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/.gitkeep` — placeholder de la carpeta versionable de ACF
- `/Applications/MAMP/htdocs/udp/cms/wp-content/mu-plugins/.gitignore` — excluir vendor/cache de mu-plugins

**A modificar:**
- `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/main.scss` — añadir `@import "utilities/typography";`
- `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/package.json` — añadir `@fontsource/work-sans`

**Archivos generados por herramientas (no editar a mano):**
- `src/scss/fonts/*.woff2` — generados por `woff2_compress` y copiados desde `node_modules/@fontsource/work-sans/`

---

## Task 1: Backup defensivo de la DB

**Files:**
- Create: `~/Backups/udp/udp-pre-migration-2026-04-27.sql`

- [x] **Step 1: Crear directorio de backups fuera del proyecto**

```bash
mkdir -p ~/Backups/udp
```

Expected: comando exitoso, sin output.

- [x] **Step 2: Generar mysqldump de la DB `udp` usando el binario de MAMP y el socket Unix**

```bash
/Applications/MAMP/Library/bin/mysql80/bin/mysqldump \
  --socket=/Applications/MAMP/tmp/mysql/mysql.sock \
  -uroot -proot \
  --single-transaction \
  --quick \
  --default-character-set=utf8mb4 \
  udp > ~/Backups/udp/udp-pre-migration-2026-04-27.sql
```

Expected: mensaje `mysqldump: [Warning] Using a password on the command line interface can be insecure.` (esperado, ignorar). El comando termina sin error.

- [x] **Step 3: Verificar que el dump tiene contenido razonable (>10 MB)**

```bash
ls -lh ~/Backups/udp/udp-pre-migration-2026-04-27.sql
```

Expected: archivo presente, tamaño >10 MB (probablemente 100-500 MB).

- [x] **Step 4: Verificar que el dump contiene las tablas críticas**

```bash
grep -c "CREATE TABLE" ~/Backups/udp/udp-pre-migration-2026-04-27.sql
grep "CREATE TABLE \`wp_fnku4yposts\`" ~/Backups/udp/udp-pre-migration-2026-04-27.sql
```

Expected: el primer comando devuelve un número >40 (hay >40 tablas). El segundo devuelve la línea `CREATE TABLE \`wp_fnku4yposts\` (`.

- [x] **Step 5: Comprimir el dump para ahorrar espacio**

```bash
gzip ~/Backups/udp/udp-pre-migration-2026-04-27.sql
ls -lh ~/Backups/udp/udp-pre-migration-2026-04-27.sql.gz
```

Expected: archivo `.sql.gz` con tamaño ~10-100 MB (ratio de compresión ~5-10×).

---

## Task 2: Inicializar git en `starter-theme`

**Files:**
- Modify: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/.gitignore` (ya existe, validar contenido)
- Create: directorio `.git/` mediante `git init`

- [ ] **Step 1: Verificar el `.gitignore` actual cubre lo necesario**

```bash
cat /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/.gitignore
```

Expected: contiene `node_modules/`, `dist/`, `.DS_Store`, `.env`, `CLAUDE.md`, `.claude`. **Si falta algo, añadirlo en este paso. Si está todo, continuar.**

- [ ] **Step 2: Inicializar repo git en starter-theme**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git init -b main
```

Expected: mensaje `Initialized empty Git repository in .../starter-theme/.git/`.

- [ ] **Step 3: Hacer primer commit con el estado actual del tema**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add .
git status
```

Expected: lista de archivos staged (PHP, SCSS, JS, package.json, fuentes OTF, etc.). NO debe aparecer `node_modules/`, `dist/`, `CLAUDE.md`, `.claude/`.

- [ ] **Step 4: Commit inicial**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git commit -m "chore: initial commit of starter-theme baseline"
```

Expected: commit exitoso con resumen de archivos añadidos (>30 archivos típicamente).

---

## Task 3: Inicializar git en `mu-plugins/`

**Files:**
- Create: `/Applications/MAMP/htdocs/udp/cms/wp-content/mu-plugins/.gitignore`
- Create: directorio `.git/` mediante `git init`

- [ ] **Step 1: Crear `.gitignore` para el repo de mu-plugins**

Create `/Applications/MAMP/htdocs/udp/cms/wp-content/mu-plugins/.gitignore`:

```
# OS
.DS_Store
Thumbs.db

# IDE
.vscode/
.idea/
*.swp

# Logs
*.log

# Temp
tmp/
```

- [ ] **Step 2: Inicializar repo**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/mu-plugins
git init -b main
git add .
git status
```

Expected: ver `.gitignore` y `wp-migrate-db-pro-compatibility.php` staged.

- [ ] **Step 3: Commit inicial**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/mu-plugins
git commit -m "chore: initial commit of mu-plugins folder"
```

Expected: commit exitoso.

---

## Task 4: Validar el toolchain Vite de `starter-theme`

**Files:**
- Generated: `node_modules/`, `dist/.vite/manifest.json`

- [ ] **Step 1: Instalar dependencias npm**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
npm install
```

Expected: `node_modules/` se crea, sin errores. Algunos warnings de deprecation son aceptables.

- [ ] **Step 2: Ejecutar build de producción**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
npm run build
```

Expected: salida tipo `vite v6.x building for production...` y al final `built in Xms`. Sin errores rojos.

- [ ] **Step 3: Verificar que el manifest se genera**

```bash
ls -la /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/dist/.vite/manifest.json
```

Expected: archivo presente, no vacío.

- [ ] **Step 4: Inspeccionar el manifest para confirmar entradas SCSS y JS**

```bash
cat /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/dist/.vite/manifest.json | head -40
```

Expected: JSON con entradas para `src/scss/main.scss` (o equivalente) y `src/js/main.js`, cada una con un hash en el campo `file`.

- [ ] **Step 5: Probar `npm run dev` (Vite dev server con HMR)**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
npm run dev
```

Expected: servidor Vite arranca en `http://localhost:5173/`. Dejar correr 5 segundos, luego cancelar con `Ctrl+C`. Si no arranca, revisar `vite.config.js`.

---

## Task 5: Crear el mu-plugin `udp-theme-switcher`

**Files:**
- Create: `/Applications/MAMP/htdocs/udp/cms/wp-content/mu-plugins/udp-theme-switcher.php`

- [ ] **Step 1: Crear el archivo del mu-plugin con la lógica completa**

Create `/Applications/MAMP/htdocs/udp/cms/wp-content/mu-plugins/udp-theme-switcher.php`:

```php
<?php
/**
 * Plugin Name: UDP Theme Switcher (Local Dev)
 * Description: Permite previsualizar starter-theme vía ?theme=new (cookie persistente). Solo activo en entornos no-producción. Para resetear: ?theme=reset.
 * Version:     1.0.0
 * Author:      UDP / starter-theme migration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Solo activo en entornos NO-producción.
if ( function_exists( 'wp_get_environment_type' ) && wp_get_environment_type() === 'production' ) {
	return;
}

/**
 * Tema preview a forzar (o null si no hay).
 *
 * @return string|null Nombre del directorio del tema, o null.
 */
function udp_theme_switcher_preview_theme() {
	$cookie_name = 'udp_preview_theme';

	// Query var manda sobre cookie y la persiste.
	if ( isset( $_GET['theme'] ) ) {
		$req = sanitize_key( wp_unslash( $_GET['theme'] ) );

		if ( $req === 'reset' ) {
			setcookie( $cookie_name, '', time() - 3600, '/' );
			unset( $_COOKIE[ $cookie_name ] );
			return null;
		}

		if ( $req === 'new' ) {
			setcookie( $cookie_name, 'new', time() + DAY_IN_SECONDS, '/' );
			$_COOKIE[ $cookie_name ] = 'new';
			return 'starter-theme';
		}
	}

	if ( isset( $_COOKIE[ $cookie_name ] ) && $_COOKIE[ $cookie_name ] === 'new' ) {
		return 'starter-theme';
	}

	return null;
}

add_filter( 'template', function ( $template ) {
	$preview = udp_theme_switcher_preview_theme();
	return $preview ?: $template;
} );

add_filter( 'stylesheet', function ( $stylesheet ) {
	$preview = udp_theme_switcher_preview_theme();
	return $preview ?: $stylesheet;
} );

// Indicador en la admin bar para que sepas en qué modo estás.
add_action( 'admin_bar_menu', function ( $admin_bar ) {
	if ( ! is_admin_bar_showing() ) {
		return;
	}

	$active = udp_theme_switcher_preview_theme();
	$label  = $active
		? '🔄 Preview: ' . $active . ' (click para volver al tema activo)'
		: '👁 Tema activo (click para previsualizar starter-theme)';
	$href   = $active
		? add_query_arg( 'theme', 'reset' )
		: add_query_arg( 'theme', 'new' );

	$admin_bar->add_node( array(
		'id'    => 'udp-theme-switcher',
		'title' => $label,
		'href'  => esc_url( $href ),
		'meta'  => array(
			'title' => 'UDP Theme Switcher (solo dev)',
		),
	) );
}, 100 );
```

- [ ] **Step 2: Verificar sintaxis PHP del archivo**

```bash
/Applications/MAMP/bin/php/php8.2.0/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/mu-plugins/udp-theme-switcher.php
```

(Si tu versión de PHP en MAMP es distinta a 8.2.0, ajusta la ruta. Para listar versiones disponibles: `ls /Applications/MAMP/bin/php/`.)

Expected: `No syntax errors detected in ...udp-theme-switcher.php`.

- [ ] **Step 3: Commit del mu-plugin**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/mu-plugins
git add udp-theme-switcher.php
git commit -m "feat: add udp-theme-switcher mu-plugin for local preview"
```

Expected: commit exitoso.

---

## Task 6: Verificar el theme switcher en navegador

**Files:** ninguno. Es validación manual.

- [ ] **Step 1: Visitar la home con el tema actual (sin parámetro)**

Abre en navegador: `http://localhost:8888/udp/`

Expected: la home actual con `udp_portable` (Materialize, jQuery, fuentes legacy). Identificable por el HTML/CSS antiguo.

- [ ] **Step 2: Activar preview con `?theme=new`**

Abre: `http://localhost:8888/udp/?theme=new`

Expected: la home **renderiza con starter-theme** (probablemente rota visualmente porque starter-theme aún no está poblado, pero el HTML será distinto: clases Bootstrap, fuente system-ui, sin Materialize). Si starter-theme no tiene `front-page.php` específica para el contexto `home/index` que se está viendo, usará `index.php`.

Para confirmar inequívocamente:

```bash
curl -s -b "udp_preview_theme=new" "http://localhost:8888/udp/" | grep -E "(starter-theme|udp_portable)" | head
```

Expected: aparece `starter-theme` en las URLs de assets (CSS/JS), no `udp_portable`.

- [ ] **Step 3: Verificar persistencia por cookie**

Abre: `http://localhost:8888/udp/sample-page/` (sin `?theme=new`)

Expected: sigue renderizando con starter-theme (la cookie `udp_preview_theme=new` persiste durante 24h).

- [ ] **Step 4: Resetear con `?theme=reset`**

Abre: `http://localhost:8888/udp/?theme=reset`

Expected: vuelve a `udp_portable`. Si recargas la home sin parámetros, sigue en `udp_portable`.

- [ ] **Step 5 (opcional): Verificar admin bar**

Loguearse en `http://localhost:8888/udp/cms/wp-admin/` y abrir el sitio en una pestaña. La admin bar superior debe mostrar el toggle "👁 Tema activo" / "🔄 Preview: starter-theme".

Expected: el botón aparece y al clicarlo cambia de modo.

---

## Task 7: Convertir las fuentes OTF a WOFF2

**Files:**
- Generated: `src/scss/fonts/ABCArizonaFlare-*.woff2` (7 archivos), `src/scss/fonts/NectoMono-Regular.woff2`

- [ ] **Step 1: Instalar `woff2` vía Homebrew (si no está)**

```bash
which woff2_compress || brew install woff2
```

Expected: si no estaba, brew lo instala. Al final, `which woff2_compress` debe devolver una ruta tipo `/opt/homebrew/bin/woff2_compress`.

- [ ] **Step 2: Convertir todas las OTF en `src/scss/fonts/`**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/fonts
for otf in *.otf; do
  echo "→ Convirtiendo $otf"
  woff2_compress "$otf"
done
```

Expected: por cada `.otf` se genera el `.woff2` correspondiente. 7 de Arizona Flare + 1 de Necto Mono = 8 archivos `.woff2` nuevos.

- [ ] **Step 3: Verificar generación**

```bash
ls -lh /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/fonts/*.woff2
```

Expected: 8 archivos `.woff2`. Cada uno significativamente más pequeño que su `.otf` equivalente (típicamente 30-60% del tamaño original).

- [ ] **Step 4: Eliminar los `.otf` ya convertidos (opcional pero recomendado para no servirlos)**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/fonts
mkdir -p _otf-source
mv *.otf _otf-source/
ls -lh
```

Expected: los `.otf` quedan archivados en `_otf-source/` (por si necesitamos reconvertir con ajustes), y el directorio raíz solo tiene `.woff2`.

- [ ] **Step 5: Excluir `_otf-source/` del repo si se quiere evitar versionar las OTF**

Edit `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/.gitignore` — añadir al final:

```
# Source OTF fonts (convertidas a WOFF2 antes de servir)
src/scss/fonts/_otf-source/
```

(Decisión del equipo si se versionan o no. Si tu licencia permite distribución web pero no print, podrías querer NO commitear las OTF. Si son solo para tu equipo, sí commitearlas.)

- [ ] **Step 6: Commit de las fuentes WOFF2**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add src/scss/fonts/ .gitignore
git status
git commit -m "feat(fonts): add WOFF2 versions of Arizona Flare and Necto Mono"
```

Expected: 8 archivos `.woff2` añadidos al commit (más eventualmente la actualización del `.gitignore`).

---

## Task 8: Añadir Work Sans (la sans del body)

**Files:**
- Modify: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/package.json`
- Generated: `src/scss/fonts/work-sans-latin-{400,500,600}-normal.woff2`

- [ ] **Step 1: Instalar el paquete `@fontsource/work-sans`**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
npm install --save-dev @fontsource/work-sans
```

Expected: paquete instalado. Verás aparecer `@fontsource/work-sans` en `devDependencies` de `package.json`.

- [ ] **Step 2: Localizar los archivos WOFF2 dentro del paquete**

```bash
ls /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/node_modules/@fontsource/work-sans/files/ | grep "latin-.*-normal.woff2" | head -10
```

Expected: lista incluye `work-sans-latin-400-normal.woff2`, `work-sans-latin-500-normal.woff2`, `work-sans-latin-600-normal.woff2`, etc.

- [ ] **Step 3: Copiar los pesos 400, 500, 600 al directorio de fuentes del tema**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
cp node_modules/@fontsource/work-sans/files/work-sans-latin-400-normal.woff2 src/scss/fonts/
cp node_modules/@fontsource/work-sans/files/work-sans-latin-500-normal.woff2 src/scss/fonts/
cp node_modules/@fontsource/work-sans/files/work-sans-latin-600-normal.woff2 src/scss/fonts/
ls -lh src/scss/fonts/work-sans-*.woff2
```

Expected: 3 archivos copiados, cada uno ~25-30 KB.

- [ ] **Step 4: Commit del package.json y las fuentes Work Sans**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add package.json package-lock.json src/scss/fonts/work-sans-*.woff2
git commit -m "feat(fonts): add Work Sans (400/500/600) self-hosted via @fontsource"
```

Expected: commit exitoso.

---

## Task 9: Cablear `@font-face` en SCSS y verificar en navegador

**Files:**
- Create: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/utilities/_typography.scss`
- Modify: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/main.scss`

- [ ] **Step 1: Crear `_typography.scss` con todas las declaraciones `@font-face`**

Create `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/utilities/_typography.scss`:

```scss
// ==========================================================================
// TYPOGRAPHY — @font-face declarations
// Self-hosted WOFF2 desde src/scss/fonts/
// ==========================================================================

// --------------------------------------------------------------------------
// ABC Arizona Flare (display serif)
// --------------------------------------------------------------------------
@font-face {
	font-family: 'Arizona Flare';
	src: url('../fonts/ABCArizonaFlare-Light.woff2') format('woff2');
	font-weight: 300;
	font-style: normal;
	font-display: swap;
}

@font-face {
	font-family: 'Arizona Flare';
	src: url('../fonts/ABCArizonaFlare-Regular.woff2') format('woff2');
	font-weight: 400;
	font-style: normal;
	font-display: swap;
}

@font-face {
	font-family: 'Arizona Flare';
	src: url('../fonts/ABCArizonaFlare-RegularItalic.woff2') format('woff2');
	font-weight: 400;
	font-style: italic;
	font-display: swap;
}

@font-face {
	font-family: 'Arizona Flare';
	src: url('../fonts/ABCArizonaFlare-Medium.woff2') format('woff2');
	font-weight: 500;
	font-style: normal;
	font-display: swap;
}

@font-face {
	font-family: 'Arizona Flare';
	src: url('../fonts/ABCArizonaFlare-MediumItalic.woff2') format('woff2');
	font-weight: 500;
	font-style: italic;
	font-display: swap;
}

@font-face {
	font-family: 'Arizona Flare';
	src: url('../fonts/ABCArizonaFlare-Bold.woff2') format('woff2');
	font-weight: 700;
	font-style: normal;
	font-display: swap;
}

@font-face {
	font-family: 'Arizona Flare';
	src: url('../fonts/ABCArizonaFlare-BoldItalic.woff2') format('woff2');
	font-weight: 700;
	font-style: italic;
	font-display: swap;
}

// --------------------------------------------------------------------------
// Necto Mono (eyebrows / labels uppercase)
// --------------------------------------------------------------------------
@font-face {
	font-family: 'Necto Mono';
	src: url('../fonts/NectoMono-Regular.woff2') format('woff2');
	font-weight: 400;
	font-style: normal;
	font-display: swap;
}

// --------------------------------------------------------------------------
// Work Sans (UI / body)
// --------------------------------------------------------------------------
@font-face {
	font-family: 'Work Sans';
	src: url('../fonts/work-sans-latin-400-normal.woff2') format('woff2');
	font-weight: 400;
	font-style: normal;
	font-display: swap;
}

@font-face {
	font-family: 'Work Sans';
	src: url('../fonts/work-sans-latin-500-normal.woff2') format('woff2');
	font-weight: 500;
	font-style: normal;
	font-display: swap;
}

@font-face {
	font-family: 'Work Sans';
	src: url('../fonts/work-sans-latin-600-normal.woff2') format('woff2');
	font-weight: 600;
	font-style: normal;
	font-display: swap;
}
```

- [ ] **Step 2: Importar `_typography.scss` en `main.scss` (justo después de Bootstrap)**

Edit `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/main.scss` — localizar la línea `@import "bootstrap/scss/bootstrap";` y añadir justo después un nuevo bloque:

Reemplaza:
```scss
@import "bootstrap/scss/bootstrap";

// --------------------------------------------------------------------------
// 4. Base
```

Por:
```scss
@import "bootstrap/scss/bootstrap";

// --------------------------------------------------------------------------
// 3.5. Typography (@font-face)
// --------------------------------------------------------------------------
@import "utilities/typography";

// --------------------------------------------------------------------------
// 4. Base
```

- [ ] **Step 3: Rebuild y verificar que el SCSS compila sin error**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
npm run build
```

Expected: build sin errores. La salida debe mencionar el bundle CSS y los assets de fuentes (Vite copia los `.woff2` referenciados a `dist/`).

- [ ] **Step 4: Verificar que las fuentes están en `dist/`**

```bash
find /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/dist -name "*.woff2" 2>/dev/null
```

Expected: lista con los `.woff2` de Arizona Flare, Necto Mono, Work Sans (con hash en el nombre, tipo `ABCArizonaFlare-Medium-DEADBEEF.woff2`).

- [ ] **Step 5: Verificar carga de fuentes en navegador**

Abre `http://localhost:8888/udp/?theme=new`. Abre DevTools → pestaña **Network** → filtra por **Font**.

Expected: al recargar, las fuentes WOFF2 se descargan (200 OK). Al menos `Work Sans 400` debería aparecer (si el body tiene texto). Las otras se cargarán cuando un elemento las use.

- [ ] **Step 6: Commit**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add src/scss/utilities/_typography.scss src/scss/main.scss
git commit -m "feat(fonts): wire @font-face declarations and import typography utility"
```

Expected: commit exitoso.

---

## Task 10: Crear la carpeta `acf-json/` para F1

**Files:**
- Create: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/.gitkeep`

- [ ] **Step 1: Crear el directorio**

```bash
mkdir -p /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json
```

- [ ] **Step 2: Añadir un `.gitkeep` para que git versione el directorio vacío**

```bash
touch /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/acf-json/.gitkeep
```

- [ ] **Step 3: Confirmar que el theme ya tiene la lógica de auto-sync de ACF local-json**

```bash
grep -r "acf/settings/save_json\|acf/settings/load_json" /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/inc/ 2>/dev/null
```

Expected: si ya existe, devuelve líneas con esos hooks. Si NO devuelve nada, hay que crear `inc/acf-setup.php` con esta lógica — pero **eso es trabajo de F1**, no de F0. Para F0 basta con que la carpeta exista.

- [ ] **Step 4: Commit**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add acf-json/.gitkeep
git commit -m "chore: scaffold acf-json directory for F1"
```

Expected: commit exitoso.

---

## Task 11: Verificación end-to-end de F0

**Files:** ninguno. Es validación final.

- [ ] **Step 1: Confirmar backup**

```bash
ls -lh ~/Backups/udp/udp-pre-migration-2026-04-27.sql.gz
```

Expected: archivo existe, tamaño >5 MB.

- [ ] **Step 2: Confirmar repos git**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && git log --oneline | head
echo "---"
cd /Applications/MAMP/htdocs/udp/cms/wp-content/mu-plugins && git log --oneline | head
```

Expected: ambos repos muestran al menos los commits hechos en F0.

- [ ] **Step 3: Confirmar build limpio**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
npm run build 2>&1 | tail -10
```

Expected: build sin errores.

- [ ] **Step 4: Confirmar switcher operativo (manual en navegador)**

Visita en orden:
1. `http://localhost:8888/udp/` → tema viejo
2. `http://localhost:8888/udp/?theme=new` → starter-theme renderiza
3. `http://localhost:8888/udp/?theme=reset` → vuelve a tema viejo

Expected: las tres rutas se comportan según lo descrito.

- [ ] **Step 5: Confirmar fuentes cargadas en preview**

`http://localhost:8888/udp/?theme=new` → DevTools Network → filtro Font → recargar.

Expected: al menos un `.woff2` de Work Sans se descarga con 200.

- [ ] **Step 6: Cerrar F0 actualizando MEMORY.md del tema**

Edit `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/MEMORY.md` — añadir al final:

```markdown
### 2026-04-27 — F0 Foundation completada

- DB local respaldada (mysqldump comprimido en `~/Backups/udp/`)
- Git inicializado en `starter-theme/` y `wp-content/mu-plugins/` (repos separados)
- Vite 6 toolchain validada (`npm install` + `npm run build` ok)
- Mu-plugin `udp-theme-switcher` operativo: `?theme=new` previsualiza `starter-theme` sin tocar el tema activo
- Fuentes convertidas a WOFF2 (Arizona Flare 7 pesos, Necto Mono Regular)
- Work Sans añadida vía `@fontsource/work-sans` (400/500/600) y copiada a `src/scss/fonts/`
- `@font-face` declaradas en `src/scss/utilities/_typography.scss` e importadas en `main.scss`
- Carpeta `acf-json/` creada (vacía, lista para F1)
- Plugin `portable__plugin_ws` aún instalado pero NO activo — desinstalación queda para F1
- Próximo paso: F1 — crear mu-plugin `udp-core` con CPTs/taxonomías y reorganizar grupos ACF
```

- [ ] **Step 7: Commit final del MEMORY**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add MEMORY.md
git commit -m "docs: log F0 foundation completion in MEMORY.md"
```

---

## Coverage check vs. spec

Verificación de que cada elemento de F0 en `2026-04-27-migracion-udp-portable-a-starter-theme-design.md` (sección 6.2) tiene un task que lo cubre:

| Spec F0 deliverable | Task(s) que lo cubren |
|---|---|
| `mysqldump` de la DB local fuera del proyecto | Task 1 |
| Crear mu-plugin `udp-theme-switcher` | Task 5, validado en Task 6 |
| Convertir OTF→WOFF2 | Task 7 |
| Descargar Work Sans | Task 8 |
| `npm install` en starter-theme, validar Vite | Task 4 |
| Carpeta `acf-json/` creada | Task 10 |
| `git init` del proyecto si procede | Tasks 2 y 3 (repos separados para tema y mu-plugins) |
| Visible al final: `?theme=new` carga starter-theme con SCSS base | Task 6 + Task 9 (fuentes en navegador) + Task 11 (e2e) |

**Cobertura completa.** No hay deliverables del spec sin task asignado.
