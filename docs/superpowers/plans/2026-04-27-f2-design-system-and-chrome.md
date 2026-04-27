# F2 — Sistema de diseño + chrome (header/footer) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Reemplazar los tokens placeholder del starter-theme por el sistema de diseño UDP completo (paleta + tipografía + espaciado + overrides Bootstrap), implementar el chrome global (header con logo + top-bar + trigger mega-menú stub; footer con columnas + RRSS desde options pages de F1) y dejar 5 componentes base reutilizables (button, card, eyebrow, huincha, faculty-card) listos para los bloques flexible content de F3+.

**Architecture:** SCSS layered: `utilities/` (variables + mixins + typography scale + faculty-colors) cargado primero (vía `additionalData` de Vite + import explícito), luego Bootstrap, luego `layouts/` (header, footer), luego `components/` (button, card, eyebrow, huincha, faculty-card). PHP de chrome: `header.php` detecta contexto (front/archive vs single/page) y aplica clase `body.is-dark` o `body.is-light`. Faculty colors leídos en runtime desde ACF tax meta (`tax_facultad_meta` creado en F1) e inyectados como CSS custom property `--faculty-color`.

**Tech Stack:** SCSS, Bootstrap 5, Vite 6, ACF Pro (lectura de options + taxonomy meta), WordPress (header/footer hooks).

**Note on visibility:** Cada task deja el sitio en estado funcional. El switcher `?theme=new` debe seguir mostrando algo coherente después de cada commit (sin pixel-perfect — el ajuste fino viene cuando se construyan páginas reales en F3+).

**Reference files:**
- Spec: `docs/superpowers/specs/2026-04-27-migracion-udp-portable-a-starter-theme-design.md` (sección 4 completa: paleta, tipografía, espaciado, theming, mixins, estructura SCSS)
- Plan F0 (foundation, ya ejecutado): `docs/superpowers/plans/2026-04-27-f0-foundation.md`
- Plan F1 (data layer, ya ejecutado): `docs/superpowers/plans/2026-04-27-f1-udp-core-and-acf-refactor.md`

---

## Inventario de archivos

**A reescribir (existen, contenido actual no aplica):**
- `src/scss/utilities/_variables.scss` — actualmente tiene defaults Inter de starter-theme; reemplazar con tokens UDP completos
- `src/scss/utilities/_mixins.scss` — actualmente tiene helpers generales; añadir mixins UDP-específicos sin romper los existentes
- `src/scss/layouts/_header.scss` — actualmente está casi vacío; rellenar con estilos del header UDP
- `src/scss/layouts/_footer.scss` — actualmente está casi vacío; rellenar
- `src/scss/components/_cards.scss` — placeholder; reescribir como `_card.scss` con design UDP (flat, hover-invert)
- `header.php` — defaults de starter-theme; reemplazar por estructura UDP (logo + top-bar + body class theming + trigger mega-menú stub)
- `footer.php` — defaults; reemplazar por footer UDP (consume options page Footer + Redes Sociales de F1)
- `src/scss/main.scss` — añadir imports de los nuevos parciales

**A crear:**
- `src/scss/utilities/_faculty-colors.scss` — helpers para CSS custom properties de facultad
- `src/scss/components/_button.scss` — pill buttons + sizes + icon-circle
- `src/scss/components/_eyebrow.scss` — mono uppercase con tracking
- `src/scss/components/_huincha.scss` — texto continuo / marquee horizontal
- `src/scss/components/_faculty-card.scss` — card con `--faculty-color` aplicado
- `inc/template-helpers.php` — funciones PHP helper (ej. `udp_body_theme_class()`, `udp_get_logo_url()`, `udp_render_faculty_color_var()`)
- `template-parts/header/top-bar.php` — markup del top-bar (logo + search + CTA + accesibilidad)
- `template-parts/header/mega-menu-trigger.php` — botón que abrirá el mega-menú (stub, F8 lo conecta)
- `template-parts/footer/columns.php` — columnas de links del footer (lee options page)
- `template-parts/footer/social.php` — iconos sociales (lee options page Redes Sociales)

**A NO tocar:**
- `inc/acf-setup.php` — ya quedó cerrado en F1
- `inc/class-vite.php` — toolchain estable de F0
- Mu-plugins — son data layer, F2 es presentación
- `single*.php`, `archive*.php`, `templates/*.php` — son F4-F9

**Decisiones arquitectónicas que F2 cierra:**
- **Faculty color**: leído via `get_field('color', 'facultad_' . $term_id)` (recordar: el field `color` lo registramos en F1 con tax key `field_60c07f1c20f2f` reusado del grupo viejo "Color"). Helper PHP `udp_render_faculty_color_var($term_id)` devuelve `style="--faculty-color: #XXX;"` o cadena vacía.
- **Body theming**: clase `is-dark` para front-page/home/archives/post-type-archives; `is-light` para singles/pages. Header siempre dark (independiente del body). Cada bloque flexible (F3+) puede sobreescribir con su propio `theme: light|dark|inherit`.
- **Logos**: leer desde options page General (`get_field('logo_color', 'option')` y `logo_blanco`). Helper `udp_get_logo_url($variant)` con fallback a placeholder.
- **No JS modules en F2**: el toggle del mega-menú trigger emite un `data-attribute` que F8 cableará. F2 deja solo CSS hover/focus states.

---

## Task 1: Reescribir `_variables.scss` con tokens UDP

**Files:**
- Modify: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/utilities/_variables.scss`

Reemplazar el contenido placeholder (Inter colors, primary `#0d6efd`) por la paleta UDP completa, escala tipográfica, espaciado mixto y overrides de Bootstrap. Conservar comentarios explicativos.

- [ ] **Step 1: Backup mental del contenido actual y reescribir**

Reemplazar TODO el contenido de `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/utilities/_variables.scss` por:

```scss
// ==========================================================================
// VARIABLES UDP — Sistema de diseño
// Sobreescriben Bootstrap (importado después en main.scss).
// Sección 4 del spec docs/superpowers/specs/2026-04-27-migracion-udp-portable-a-starter-theme-design.md
// ==========================================================================

// --------------------------------------------------------------------------
// 1. Paleta UDP
// --------------------------------------------------------------------------
$brand-red:      #C81C0D;  // CTA principal, botón Accesibilidad
$brand-accent:   #FF7064;  // Item activo, texto destacado sobre dark
$brand-blue:     #4539F2;  // Hover de cards, acentos secundarios

// Superficies dark (modo dark-first)
$dark-1:         #1C1C1C;  // Background principal del header / dark surfaces
$dark-2:         #232323;  // Cards dark, paneles internos
$gray-high:      #454545;  // Bordes iconos circulares
$gray-medium:    #4F4F4F;  // Bordes botones sobre dark
$white:          #FFFFFF;
$white-70:       rgba(255, 255, 255, 0.7);  // Etiquetas/categorías sobre dark

// --------------------------------------------------------------------------
// 2. Mapeo a variables Bootstrap (ANTES del @import de Bootstrap)
// --------------------------------------------------------------------------
$primary:        $brand-red;
$secondary:      $brand-blue;
$success:        #198754;
$info:           #0dcaf0;
$warning:        #ffc107;
$danger:         #dc3545;
$light:          $white;
$dark:           $dark-1;

// --------------------------------------------------------------------------
// 3. Tipografía
// --------------------------------------------------------------------------
$font-family-display: 'Arizona Flare', Georgia, "Times New Roman", serif;
$font-family-body:    'Work Sans', system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
$font-family-mono:    'Necto Mono', "JetBrains Mono", "Courier New", monospace;

// Sobreescribir Bootstrap
$font-family-sans-serif: $font-family-body;
$font-family-monospace:  $font-family-mono;
$headings-font-family:   $font-family-display;
$headings-font-weight:   500;  // Arizona Flare Medium para titulares display
$font-size-base:         1rem;
$line-height-base:       1.5;

// Escala UDP (sección 4.2 del spec)
$font-size-display-1: 48px;  // Hero titles — Arizona Flare Medium
$font-size-display-2: 40px;  // H2 grandes — Arizona Flare Medium
$font-size-h3:        24px;  // Card titles — Work Sans Medium
$font-size-h4:        20px;  // Subtítulos — Work Sans Medium
$font-size-body-lg:   16px;  // Body grande — Work Sans Medium
$font-size-body-sm:   14px;  // Metadatos — Work Sans SemiBold
$font-size-label-md:  14px;  // Eyebrow uppercase — Necto Mono
$font-size-label-sm:  12px;  // Caption — Necto Mono

$line-height-tight:   1.0;   // Display
$line-height-snug:    1.25;  // H3, H4
$line-height-normal:  1.5;   // Body
$line-height-loose:   1.7;   // Long-form prose

$letter-spacing-eyebrow: 0.8px;  // Mono uppercase

// --------------------------------------------------------------------------
// 4. Espaciado (escala UDP — mixta, no estricta de 4 ni 8)
// --------------------------------------------------------------------------
$space-2xs: 8px;
$space-xs:  12px;
$space-sm:  16px;
$space-md:  18px;
$space-lg:  20px;
$space-xl:  24px;
$space-2xl: 32px;
$space-3xl: 40px;
$space-4xl: 64px;
$space-5xl: 120px;

// Inyectado al map de Bootstrap (extiende, no reemplaza).
$spacers: (
    0: 0,
    1: $space-2xs,
    2: $space-xs,
    3: $space-sm,
    4: $space-md,
    5: $space-lg,
    6: $space-xl,
    7: $space-2xl,
    8: $space-3xl,
    9: $space-4xl,
    10: $space-5xl,
);

// --------------------------------------------------------------------------
// 5. Overrides de Bootstrap (componentes — sección 4.4 del spec)
// --------------------------------------------------------------------------

// Container — frame Figma 1440 con padding lateral 40
$container-max-widths: (
    sm: 540px,
    md: 720px,
    lg: 960px,
    xl: 1140px,
    xxl: 1360px,
);
$grid-gutter-width: $space-2xl;  // 32px

// Botones — pill (radius-full) sobreescribe el default 0.375rem
$btn-border-radius:    9999px;
$btn-border-radius-sm: 9999px;
$btn-border-radius-lg: 9999px;
$btn-padding-y:        12px;
$btn-padding-x:        $space-sm;  // 16px
$btn-font-weight:      600;        // Work Sans SemiBold

// Cards — flat (sin esquinas, sin sombras por defecto)
$card-border-radius: 0;
$card-border-width:  0;
$card-spacer-y:      $space-md;  // 18px
$card-spacer-x:      $space-md;  // 18px
$card-bg:            transparent;

// Body — diseño dark-first; el switch is-dark/is-light viene de body class
$body-bg:    $white;        // Default light, ya que la mayor parte de páginas interiores son light
$body-color: $dark-1;

// Sombras — usar muy sparingly, diseño es flat
$box-shadow:    0 1px 2px rgba(0, 0, 0, 0.08);
$box-shadow-sm: 0 1px 1px rgba(0, 0, 0, 0.04);
$box-shadow-lg: 0 4px 12px rgba(0, 0, 0, 0.12);

// Bordes
$border-radius:    0;
$border-radius-sm: 0;
$border-radius-lg: 0;

// --------------------------------------------------------------------------
// 6. Layout
// --------------------------------------------------------------------------
$navbar-height: 80px;  // Top-bar UDP
$header-side-padding: $space-3xl;  // 40px

// --------------------------------------------------------------------------
// 7. Transiciones
// --------------------------------------------------------------------------
$transition-base: 0.3s ease;
$transition-fast: 0.15s ease;
```

- [ ] **Step 2: Validar build**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -10
```

Expected: build exitoso (warnings de Sass deprecation aceptables — son por @import legacy de F0). Sin errores rojos.

- [ ] **Step 3: Verificar que las variables están disponibles globalmente vía `additionalData`**

```bash
grep -A1 "additionalData" /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/vite.config.js
```

Expected: `@import "utilities/variables";` está siendo inyectado (configuración de F0).

- [ ] **Step 4: Commit**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add src/scss/utilities/_variables.scss
git commit -m "feat(scss): replace defaults with UDP design tokens

- Paleta UDP: brand-red #C81C0D, brand-accent #FF7064, brand-blue #4539F2.
- 4 superficies dark (#1C1C1C / #232323) + grays + white-70.
- Tipografía: Arizona Flare display, Work Sans body, Necto Mono labels.
- Escala mixta de espaciado (8/12/16/18/20/24/32/40/64/120).
- Overrides Bootstrap: pill buttons, flat cards, container 1360px, grid 32px gutter."
```

---

## Task 2: Añadir mixins UDP-específicos a `_mixins.scss`

**Files:**
- Modify: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/utilities/_mixins.scss`

Conservar los mixins existentes (media-up, media-down, hover-lift, overlay, section-padding, truncate-lines, aspect-ratio) y añadir los específicos UDP al final del archivo.

- [ ] **Step 1: Append mixins UDP al archivo**

Localizar el FINAL de `_mixins.scss` (después del último `@mixin aspect-ratio`) y AÑADIR (no reemplazar) el bloque siguiente al final:

```scss

// ==========================================================================
// MIXINS UDP — sección 4.7 del spec
// ==========================================================================

// --------------------------------------------------------------------------
// Eyebrow: etiqueta mono uppercase con tracking generoso.
// Patrón usado en cards, headers de sección, metadatos.
// --------------------------------------------------------------------------
@mixin eyebrow($color: $white-70) {
    font-family: $font-family-mono;
    font-size: $font-size-label-md;
    line-height: $line-height-tight;
    letter-spacing: $letter-spacing-eyebrow;
    text-transform: uppercase;
    color: $color;
    font-weight: 400;
}

// Variante pequeña (12px) para captions tight.
@mixin eyebrow-sm($color: $white-70) {
    @include eyebrow($color);
    font-size: $font-size-label-sm;
}

// --------------------------------------------------------------------------
// Card hover-invert: card que invierte de dark a light/blue al hover.
// Usado en grids de eventos/noticias en home y archives.
// --------------------------------------------------------------------------
@mixin card-hover-invert($hover-bg: $brand-blue, $hover-color: $white) {
    background: $dark-2;
    color: $white;
    transition: background $transition-base, color $transition-base;

    &:hover,
    &:focus-within {
        background: $hover-bg;
        color: $hover-color;
    }
}

// --------------------------------------------------------------------------
// Huincha-list: lista vertical donde cada item combina título serif grande
// + meta mono pequeño en línea (patrón header UDP).
// --------------------------------------------------------------------------
@mixin huincha-list-item {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    padding: $space-sm 0;
    border-bottom: 1px solid $gray-medium;

    .huincha-item__title {
        font-family: $font-family-display;
        font-size: $font-size-display-1;
        font-weight: 500;
        line-height: $line-height-tight;
    }

    .huincha-item__meta {
        @include eyebrow($white-70);
    }
}

// --------------------------------------------------------------------------
// Container con padding lateral UDP (40px en desktop).
// --------------------------------------------------------------------------
@mixin container-udp {
    width: 100%;
    max-width: 1440px;
    margin-inline: auto;
    padding-inline: $header-side-padding;

    @include media-down(md) {
        padding-inline: $space-sm;  // 16px en mobile
    }
}

// --------------------------------------------------------------------------
// Faculty color application: aplica --faculty-color a un elemento como
// border accent + eyebrow color. El CSS var se inyecta vía PHP en el wrapper
// (style="--faculty-color: #XXX") por udp_render_faculty_color_var().
// --------------------------------------------------------------------------
@mixin faculty-accent {
    --faculty-color: #{$brand-red};  // Fallback si no hay color en ACF
    border-top: 4px solid var(--faculty-color);

    .eyebrow,
    .faculty-card__eyebrow {
        color: var(--faculty-color);
    }
}
```

- [ ] **Step 2: Validar build**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -8
```

Expected: build exitoso.

- [ ] **Step 3: Commit**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add src/scss/utilities/_mixins.scss
git commit -m "feat(scss): add UDP-specific mixins (eyebrow, card-hover-invert, huincha, container, faculty-accent)"
```

---

## Task 3: Crear `_faculty-colors.scss` (sistema de CSS custom properties)

**Files:**
- Create: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/utilities/_faculty-colors.scss`
- Modify: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/main.scss` (añadir import)

Define la propiedad CSS `--faculty-color` con fallback al brand-red, más helpers para aplicarla. La asignación del valor concreto la hace PHP en runtime (Task 6 PHP helper).

- [ ] **Step 1: Crear el parcial**

Create `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/utilities/_faculty-colors.scss`:

```scss
// ==========================================================================
// FACULTY COLORS — sistema de CSS custom properties
// Cada facultad tiene un color en ACF (tax_facultad_meta.color, registrado
// en F1). PHP inyecta `style="--faculty-color: #XXX"` en el wrapper de
// elementos relacionados a una facultad (cards, badges, secciones).
// SCSS solo declara el fallback y los hooks de uso.
// ==========================================================================

:root {
    --faculty-color: #{$brand-red};  // Fallback global
}

// Utility class: aplica el color como acento (border + texto eyebrow).
.has-faculty-color {
    @include faculty-accent;
}

// Variante badge: chip pequeño con bg del color de facultad.
.faculty-badge {
    --faculty-color: #{$brand-red};
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    background: var(--faculty-color);
    color: $white;
    @include eyebrow-sm($white);
    text-decoration: none;
}
```

- [ ] **Step 2: Importar en main.scss**

Localizar en `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/main.scss` la línea `@import "utilities/typography";` y AÑADIR justo después:

```scss
@import "utilities/faculty-colors";
```

El bloque modificado debe quedar:

```scss
// --------------------------------------------------------------------------
// 3.5. Typography (@font-face)
// --------------------------------------------------------------------------
@import "utilities/typography";
@import "utilities/faculty-colors";
```

- [ ] **Step 3: Build + verificar que el CSS contiene `--faculty-color`**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -5
grep -o "\\-\\-faculty-color[^;]*" dist/css/theme.*.css | head -3
```

Expected: build OK + el `--faculty-color: #C81C0D` aparece en el CSS compilado.

- [ ] **Step 4: Commit**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add src/scss/utilities/_faculty-colors.scss src/scss/main.scss
git commit -m "feat(scss): faculty-colors utility — CSS custom property + badge

PHP injection del valor real en runtime (Task 6)."
```

---

## Task 4: Crear `_button.scss` (pill buttons + sizes + icon-circle)

**Files:**
- Create: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/components/_button.scss`
- Modify: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/main.scss` (añadir import)

Bootstrap ya genera `.btn` con border-radius 9999px gracias a los overrides en `_variables.scss`. Aquí añadimos sizes UDP (md = 60px alto), icon-circle (48×48 sobre dark) y CTAs específicos.

- [ ] **Step 1: Crear el parcial**

Create `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/components/_button.scss`:

```scss
// ==========================================================================
// BUTTONS — Sistema UDP (pill, sizes, icon-circle)
// Bootstrap .btn ya viene con border-radius 9999px via _variables.scss.
// Aquí extendemos con sizes específicos y variantes UDP.
// ==========================================================================

// Tamaño UDP "md" — alto 60px (CTA Accesibilidad típico)
.btn-udp-md {
    min-height: 60px;
    padding-block: 0;
    padding-inline: $space-xl;  // 24px
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: $space-2xs;            // 8px
    font-family: $font-family-body;
    font-weight: 600;
    font-size: $font-size-body-lg;
    line-height: 1;
}

// Tamaño UDP "sm" — alto 48px
.btn-udp-sm {
    min-height: 48px;
    padding-block: 0;
    padding-inline: $space-md;  // 18px
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: $space-2xs;
    font-family: $font-family-body;
    font-weight: 600;
    font-size: $font-size-body-sm;
    line-height: 1;
}

// CTA primario (rojo UDP)
.btn-udp-primary {
    background: $brand-red;
    color: $white;
    border: none;
    transition: background $transition-base;

    &:hover,
    &:focus {
        background: darken($brand-red, 8%);
        color: $white;
    }
}

// CTA secundario sobre superficie dark — outline blanco
.btn-udp-outline-light {
    background: transparent;
    color: $white;
    border: 1px solid $gray-medium;
    transition: background $transition-base, color $transition-base, border-color $transition-base;

    &:hover,
    &:focus {
        background: $white;
        color: $dark-1;
        border-color: $white;
    }
}

// --------------------------------------------------------------------------
// Icon-circle: botón circular para iconos sueltos (search, RRSS, etc.)
// Variantes: 48×48 (sm) y 60×60 (md). Sobre dark por defecto.
// --------------------------------------------------------------------------
.btn-icon-circle {
    width: 48px;
    height: 48px;
    border-radius: 9999px;
    background: transparent;
    border: 1px solid $gray-medium;
    color: $white;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background $transition-base, color $transition-base;
    padding: 0;

    &:hover,
    &:focus {
        background: $white;
        color: $dark-1;
    }

    &.btn-icon-circle--md {
        width: 60px;
        height: 60px;
    }
}
```

- [ ] **Step 2: Importar en main.scss**

Localizar en `main.scss` la sección `// 6. Componentes` y modificar para añadir el import nuevo. Reemplazar:

```scss
// --------------------------------------------------------------------------
// 6. Componentes
// --------------------------------------------------------------------------
@import "components/hero";
@import "components/cards";
@import "components/entry-content";
@import "components/widgets";
```

Por:

```scss
// --------------------------------------------------------------------------
// 6. Componentes
// --------------------------------------------------------------------------
@import "components/button";
@import "components/hero";
@import "components/cards";
@import "components/entry-content";
@import "components/widgets";
```

- [ ] **Step 3: Build**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -5
```

Expected: build OK.

- [ ] **Step 4: Commit**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add src/scss/components/_button.scss src/scss/main.scss
git commit -m "feat(scss): button components (pill sizes, icon-circle, primary, outline-light)"
```

---

## Task 5: Reescribir `_cards.scss` (flat + hover-invert + faculty support)

**Files:**
- Modify: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/components/_cards.scss`

Reemplazar el contenido placeholder por cards UDP: flat (sin esquinas), hover-invert (dark → blue), soporte para `--faculty-color` opcional.

- [ ] **Step 1: Reemplazar contenido completo**

Reemplazar TODO el contenido de `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/components/_cards.scss` por:

```scss
// ==========================================================================
// CARDS UDP
// Bootstrap .card ya tiene border-radius 0 y border-width 0 vía _variables.
// Aquí extendemos con variantes específicas: evento, tema, facultad.
// ==========================================================================

// --------------------------------------------------------------------------
// Card de evento / noticia (variante base usada en grids)
// Tamaños típicos: 433×433 (cuadrada) o 800×668 (horizontal).
// --------------------------------------------------------------------------
.card-evento {
    @include card-hover-invert($brand-blue, $white);
    padding: $space-md;            // 18px base
    height: 100%;
    display: flex;
    flex-direction: column;
    gap: $space-xs;                // 12px entre eyebrow/title/meta
    text-decoration: none;

    &:hover,
    &:focus-within {
        padding: $space-xl;        // 24px en hover (efecto "expand")
        text-decoration: none;
    }

    .card-evento__eyebrow {
        @include eyebrow($white-70);
    }

    .card-evento__title {
        font-family: $font-family-body;
        font-weight: 500;
        font-size: $font-size-h3;     // 24px en sm; 20px en mobile
        line-height: $line-height-snug;

        @include media-down(md) {
            font-size: $font-size-h4;
        }
    }

    .card-evento__meta {
        margin-top: auto;
        font-family: $font-family-body;
        font-size: $font-size-body-sm;
        font-weight: 600;
    }
}

// Variante hover en blanco (en lugar de azul)
.card-evento--hover-light {
    @include card-hover-invert($white, $dark-1);
}

// --------------------------------------------------------------------------
// Card de tema (más alta — 800×1300)
// --------------------------------------------------------------------------
.card-tema {
    @include card-hover-invert($brand-blue, $white);
    padding: $space-xl;
    min-height: 480px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;

    .card-tema__title {
        font-family: $font-family-display;
        font-size: $font-size-display-2;
        font-weight: 500;
        line-height: $line-height-tight;
    }

    .card-tema__cta {
        align-self: flex-start;
        @include eyebrow($white);
    }
}
```

- [ ] **Step 2: Build**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -5
```

Expected: build OK.

- [ ] **Step 3: Commit**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add src/scss/components/_cards.scss
git commit -m "feat(scss): cards UDP (flat, hover-invert dark→blue, evento + tema variants)"
```

---

## Task 6: Crear componentes `_eyebrow.scss`, `_huincha.scss`, `_faculty-card.scss`

**Files:**
- Create: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/components/_eyebrow.scss`
- Create: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/components/_huincha.scss`
- Create: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/components/_faculty-card.scss`
- Modify: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/main.scss` (añadir 3 imports)

3 componentes nuevos en un único task (cada uno corto, son aplicaciones del mixin que ya existe).

- [ ] **Step 1: Crear `_eyebrow.scss`**

Create `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/components/_eyebrow.scss`:

```scss
// ==========================================================================
// EYEBROW — etiqueta mono uppercase usada como label de sección/categoría
// ==========================================================================

.eyebrow {
    @include eyebrow($white-70);

    body.is-light & {
        color: rgba(0, 0, 0, 0.6);  // En body light, eyebrow oscurece
    }
}

.eyebrow-sm {
    @include eyebrow-sm($white-70);

    body.is-light & {
        color: rgba(0, 0, 0, 0.6);
    }
}

// Variante con dot decorativo a la izquierda
.eyebrow--dotted {
    display: inline-flex;
    align-items: center;
    gap: $space-2xs;

    &::before {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
    }
}
```

- [ ] **Step 2: Crear `_huincha.scss`**

Create `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/components/_huincha.scss`:

```scss
// ==========================================================================
// HUINCHA — banda horizontal de texto continuo (marquee) o lista vertical
// ==========================================================================

// Marquee horizontal: texto que se desplaza de derecha a izquierda en loop
.huincha-marquee {
    overflow: hidden;
    white-space: nowrap;
    background: $dark-1;
    color: $white;
    padding: $space-md 0;

    .huincha-marquee__track {
        display: inline-block;
        animation: huincha-scroll 30s linear infinite;
    }

    .huincha-marquee__item {
        display: inline-block;
        padding-inline: $space-xl;
        font-family: $font-family-display;
        font-size: 60px;
        font-weight: 500;
        line-height: 1;
    }

    &:hover .huincha-marquee__track {
        animation-play-state: paused;
    }
}

@keyframes huincha-scroll {
    from { transform: translateX(0); }
    to   { transform: translateX(-50%); }
}

// Lista vertical (huincha-list)
.huincha-list {
    list-style: none;
    margin: 0;
    padding: 0;

    > li {
        @include huincha-list-item;
    }
}
```

- [ ] **Step 3: Crear `_faculty-card.scss`**

Create `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/components/_faculty-card.scss`:

```scss
// ==========================================================================
// FACULTY CARD — card específica de facultad con --faculty-color aplicado
// PHP inyecta style="--faculty-color: #XXX" en el wrapper (Task 7 helpers).
// ==========================================================================

.faculty-card {
    @include faculty-accent;
    background: $dark-2;
    color: $white;
    padding: $space-xl;
    text-decoration: none;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 320px;
    transition: transform $transition-base;

    &:hover,
    &:focus-within {
        transform: translateY(-4px);
        text-decoration: none;
    }

    .faculty-card__eyebrow {
        @include eyebrow();  // El color lo override @include faculty-accent
    }

    .faculty-card__title {
        font-family: $font-family-display;
        font-size: $font-size-display-2;
        font-weight: 500;
        line-height: $line-height-tight;
        color: $white;
        margin: $space-md 0 0;
    }

    .faculty-card__meta {
        margin-top: $space-xl;
        font-family: $font-family-body;
        font-size: $font-size-body-sm;
        color: $white-70;
    }
}
```

- [ ] **Step 4: Importar los 3 en main.scss**

Localizar en `main.scss` la sección `// 6. Componentes` (modificada en Task 4). Después de `@import "components/button";` y antes de los demás, añadir:

```scss
@import "components/button";
@import "components/eyebrow";
@import "components/huincha";
@import "components/faculty-card";
@import "components/hero";
@import "components/cards";
@import "components/entry-content";
@import "components/widgets";
```

- [ ] **Step 5: Build**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -5
```

Expected: build OK.

- [ ] **Step 6: Commit**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add src/scss/components/_eyebrow.scss src/scss/components/_huincha.scss src/scss/components/_faculty-card.scss src/scss/main.scss
git commit -m "feat(scss): eyebrow, huincha (marquee + list), faculty-card components"
```

---

## Task 7: Crear `inc/template-helpers.php` con funciones PHP

**Files:**
- Create: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/inc/template-helpers.php`
- Modify: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/functions.php` (require el nuevo archivo)

Helpers PHP que header.php, footer.php y los bloques (F3+) usarán:
- `udp_body_theme_class()` — devuelve "is-dark" o "is-light" según contexto
- `udp_get_logo_url($variant)` — devuelve URL del logo desde options page
- `udp_render_faculty_color_var($term_id)` — devuelve `style="--faculty-color: #XXX;"` o cadena vacía

- [ ] **Step 1: Crear el archivo**

Create `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/inc/template-helpers.php`:

```php
<?php
/**
 * Template helpers — UDP starter-theme
 *
 * Funciones reutilizables por header, footer y bloques flexible content.
 *
 * @package Starter_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Devuelve la clase de tema del body según contexto.
 *
 * Reglas (sección 4.5 del spec):
 * - is-dark: home, archives, post-type-archives, search results
 * - is-light: singles, pages, 404
 *
 * @return string 'is-dark' | 'is-light'
 */
function udp_body_theme_class() {
	if ( is_front_page() || is_home() || is_archive() || is_post_type_archive() || is_search() ) {
		return 'is-dark';
	}
	return 'is-light';
}

/**
 * Devuelve la URL del logo desde options page General.
 *
 * @param string $variant 'color' | 'blanco' | 'udp' | 'acreditacion'
 * @return string URL del logo o cadena vacía si no está configurado.
 */
function udp_get_logo_url( $variant = 'color' ) {
	$valid_variants = array( 'color', 'blanco', 'udp', 'acreditacion' );
	if ( ! in_array( $variant, $valid_variants, true ) ) {
		$variant = 'color';
	}
	$field = 'logo_' . $variant;
	$url   = function_exists( 'get_field' ) ? get_field( $field, 'option' ) : '';
	return is_string( $url ) ? $url : '';
}

/**
 * Devuelve el `style="--faculty-color: #XXX;"` para inyectar en un wrapper
 * relacionado a una facultad (card, badge, sección).
 *
 * @param int $term_id ID del término de la taxonomía 'facultad'.
 * @return string Atributo style listo para echo, o cadena vacía si no hay color.
 */
function udp_render_faculty_color_var( $term_id ) {
	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}
	$color = get_field( 'color', 'facultad_' . (int) $term_id );
	if ( empty( $color ) || ! is_string( $color ) ) {
		return '';
	}
	return ' style="--faculty-color: ' . esc_attr( $color ) . ';"';
}

/**
 * Devuelve un array con las URLs de redes sociales configuradas en options
 * page Redes Sociales. Solo incluye las que tienen valor (no vacías).
 *
 * @return array Asociativo: ['facebook' => 'https://...', 'twitter' => '...', ...]
 */
function udp_get_social_urls() {
	if ( ! function_exists( 'get_field' ) ) {
		return array();
	}
	$networks = array( 'facebook', 'twitter', 'instagram', 'youtube', 'linkedin', 'tiktok' );
	$out      = array();
	foreach ( $networks as $net ) {
		$url = get_field( $net, 'option' );
		if ( ! empty( $url ) && is_string( $url ) ) {
			$out[ $net ] = $url;
		}
	}
	return $out;
}

/**
 * Devuelve las columnas del footer desde options page Footer.
 *
 * @return array Array de columnas, cada una con 'titulo' y 'links' (array de label+url).
 */
function udp_get_footer_columns() {
	if ( ! function_exists( 'get_field' ) ) {
		return array();
	}
	$cols = get_field( 'columnas_footer', 'option' );
	return is_array( $cols ) ? $cols : array();
}
```

- [ ] **Step 2: Requerirlo desde functions.php**

Localizar `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/functions.php` y buscar la sección donde se hacen `require_once` (probablemente al inicio o donde se carga `inc/class-vite.php`). Añadir:

```php
require_once get_template_directory() . '/inc/template-helpers.php';
```

(Si ya hay un bloque de requires, añadir esta línea junto a los demás. Si no hay, añadir después del header docblock del archivo.)

- [ ] **Step 3: Validar PHP syntax**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/inc/template-helpers.php
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/functions.php
```

Expected: ambos `No syntax errors detected`.

- [ ] **Step 4: Verificar que el sitio carga (sin fatal en debug.log)**

```bash
curl -s -o /dev/null -w "%{http_code}\n" "http://localhost:8888/udp/?theme=new&nocache=$(date +%s)"
tail -10 /Applications/MAMP/htdocs/udp/cms/wp-content/debug.log 2>/dev/null
```

Expected: 200 + sin nuevos fatales mencionando `template-helpers` o `udp_body_theme_class`.

- [ ] **Step 5: Commit**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add inc/template-helpers.php functions.php
git commit -m "feat(php): template helpers (body theme class, logo URL, faculty color, social, footer cols)"
```

---

## Task 8: Reescribir `header.php` con chrome UDP

**Files:**
- Modify: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/header.php`
- Create: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/header/top-bar.php`
- Create: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/header/mega-menu-trigger.php`
- Modify: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/layouts/_header.scss`

Header UDP siempre es dark. Estructura:
1. Top bar dark con logo + buscador + accesibilidad CTA + login (placeholder)
2. Mega-menu trigger (stub clickable, F8 lo cablea)
3. body con clase is-dark/is-light según contexto

- [ ] **Step 1: Crear template-part del top-bar**

Create `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/header/top-bar.php`:

```php
<?php
/**
 * Header > Top bar
 * Logo + buscador + CTA accesibilidad + acceso usuario.
 *
 * @package Starter_Theme
 */
?>
<div class="udp-top-bar">
	<div class="udp-top-bar__inner">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="udp-top-bar__logo" aria-label="<?php bloginfo( 'name' ); ?>">
			<?php
			$logo = udp_get_logo_url( 'blanco' );
			if ( ! empty( $logo ) ) :
				?>
				<img src="<?php echo esc_url( $logo ); ?>" alt="<?php bloginfo( 'name' ); ?>" />
			<?php else : ?>
				<span class="udp-top-bar__logo-text"><?php bloginfo( 'name' ); ?></span>
			<?php endif; ?>
		</a>

		<div class="udp-top-bar__actions">
			<button type="button" class="btn-icon-circle" aria-label="<?php esc_attr_e( 'Buscar', 'starter-theme' ); ?>">
				<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
					<circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="1.5"/>
					<line x1="13.5" y1="13.5" x2="17" y2="17" stroke="currentColor" stroke-width="1.5"/>
				</svg>
			</button>

			<a href="#" class="btn btn-udp-primary btn-udp-md">
				<?php esc_html_e( 'Accesibilidad UDP', 'starter-theme' ); ?>
			</a>

			<a href="#" class="btn-icon-circle" aria-label="<?php esc_attr_e( 'Acceso usuario', 'starter-theme' ); ?>">
				<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
					<circle cx="10" cy="7" r="3" stroke="currentColor" stroke-width="1.5"/>
					<path d="M4 17c0-3 3-5 6-5s6 2 6 5" stroke="currentColor" stroke-width="1.5"/>
				</svg>
			</a>
		</div>
	</div>
</div>
```

- [ ] **Step 2: Crear template-part del trigger del mega-menú**

Create `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/header/mega-menu-trigger.php`:

```php
<?php
/**
 * Header > Mega-menu trigger (stub)
 * F8 cablea la apertura/cierre del panel de mega-menú completo.
 *
 * @package Starter_Theme
 */
?>
<button
	type="button"
	class="udp-megamenu-trigger"
	data-udp-megamenu-toggle
	aria-expanded="false"
	aria-controls="udp-megamenu-panel"
>
	<span class="udp-megamenu-trigger__icon" aria-hidden="true">
		<span></span>
		<span></span>
		<span></span>
	</span>
	<span class="udp-megamenu-trigger__label"><?php esc_html_e( 'Menú', 'starter-theme' ); ?></span>
</button>
```

- [ ] **Step 3: Reescribir `header.php`**

Reemplazar TODO el contenido de `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/header.php` por:

```php
<?php
/**
 * The template for displaying the header
 *
 * @package Starter_Theme
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class( udp_body_theme_class() ); ?>>
<?php wp_body_open(); ?>

<a class="visually-hidden visually-hidden-focusable" href="#main">
	<?php esc_html_e( 'Saltar al contenido principal', 'starter-theme' ); ?>
</a>

<header class="udp-site-header" role="banner">
	<?php get_template_part( 'template-parts/header/top-bar' ); ?>
	<?php get_template_part( 'template-parts/header/mega-menu-trigger' ); ?>
</header>

<main id="main" class="udp-site-main" role="main">
```

(Importante: el `<main>` se abre aquí y se cierra en `footer.php` — patrón estándar WP.)

- [ ] **Step 4: Reescribir `_header.scss`**

Reemplazar TODO el contenido de `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/layouts/_header.scss` por:

```scss
// ==========================================================================
// HEADER UDP — siempre dark, independiente del body theme
// ==========================================================================

.udp-site-header {
    background: $dark-1;
    color: $white;
    position: relative;
    z-index: 100;
}

.udp-top-bar {
    border-bottom: 1px solid $gray-medium;

    &__inner {
        @include container-udp;
        min-height: $navbar-height;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: $space-xl;
    }

    &__logo {
        display: inline-flex;
        align-items: center;
        text-decoration: none;
        color: $white;

        img {
            height: 36px;
            width: auto;
            display: block;
        }
    }

    &__logo-text {
        font-family: $font-family-display;
        font-size: $font-size-h3;
        font-weight: 500;
    }

    &__actions {
        display: flex;
        align-items: center;
        gap: $space-sm;  // 16px

        @include media-down(md) {
            gap: $space-2xs;
        }
    }
}

// --------------------------------------------------------------------------
// Mega-menu trigger (stub — F8 lo cablea)
// --------------------------------------------------------------------------
.udp-megamenu-trigger {
    @include container-udp;
    display: flex;
    align-items: center;
    gap: $space-sm;
    padding-block: $space-md;
    background: transparent;
    border: none;
    color: $white;
    cursor: pointer;
    @include eyebrow($white);
    transition: opacity $transition-base;

    &:hover,
    &:focus {
        opacity: 0.7;
    }

    &__icon {
        display: inline-flex;
        flex-direction: column;
        gap: 4px;

        > span {
            display: block;
            width: 24px;
            height: 2px;
            background: currentColor;
        }
    }

    &__label {
        font-size: $font-size-label-md;
    }
}

// --------------------------------------------------------------------------
// Body theming hooks (sección 4.5 del spec)
// --------------------------------------------------------------------------
body.is-dark {
    background: $dark-1;
    color: $white;
}

body.is-light {
    background: $white;
    color: $dark-1;
}

// Skip link
.visually-hidden {
    position: absolute !important;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

.visually-hidden-focusable:focus {
    position: static !important;
    width: auto;
    height: auto;
    margin: 0;
    overflow: visible;
    clip: auto;
    white-space: normal;
    background: $brand-red;
    color: $white;
    padding: $space-2xs $space-sm;
    z-index: 9999;
}
```

- [ ] **Step 5: Validar y verificar visualmente**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/header.php
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/header/top-bar.php
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/header/mega-menu-trigger.php
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -5
```

Expected: 3× `No syntax errors detected` + build OK.

```bash
curl -s "http://localhost:8888/udp/?theme=new&nocache=$(date +%s)" | grep -E "udp-site-header|udp-top-bar|udp-megamenu-trigger" | head -5
```

Expected: el HTML contiene las clases del nuevo header.

- [ ] **Step 6: Commit**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add header.php template-parts/header/top-bar.php template-parts/header/mega-menu-trigger.php src/scss/layouts/_header.scss
git commit -m "feat(chrome): UDP header with top-bar (logo + search + accesibilidad CTA + user) and mega-menu trigger stub

Body class is-dark/is-light segun contexto (front/archive vs single/page)."
```

---

## Task 9: Reescribir `footer.php` con columnas + RRSS desde options pages

**Files:**
- Modify: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/footer.php`
- Create: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/footer/columns.php`
- Create: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/footer/social.php`
- Modify: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/layouts/_footer.scss`

Footer UDP siempre dark. Consume options page Footer (columnas + copyright + legales) y options page Redes Sociales (URLs sociales) creadas en F1.

- [ ] **Step 1: Crear template-part de columnas**

Create `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/footer/columns.php`:

```php
<?php
/**
 * Footer > Columnas
 *
 * @package Starter_Theme
 */
$columns = udp_get_footer_columns();
if ( empty( $columns ) ) {
	return;
}
?>
<div class="udp-footer-columns">
	<?php foreach ( $columns as $col ) : ?>
		<div class="udp-footer-columns__col">
			<?php if ( ! empty( $col['titulo'] ) ) : ?>
				<h3 class="udp-footer-columns__title"><?php echo esc_html( $col['titulo'] ); ?></h3>
			<?php endif; ?>
			<?php if ( ! empty( $col['links'] ) && is_array( $col['links'] ) ) : ?>
				<ul class="udp-footer-columns__links">
					<?php foreach ( $col['links'] as $link ) : ?>
						<?php
						$url   = isset( $link['url'] ) ? $link['url'] : '';
						$label = isset( $link['label'] ) ? $link['label'] : '';
						if ( empty( $url ) || empty( $label ) ) {
							continue;
						}
						?>
						<li>
							<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>
```

- [ ] **Step 2: Crear template-part de social**

Create `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/footer/social.php`:

```php
<?php
/**
 * Footer > Iconos sociales
 *
 * @package Starter_Theme
 */
$socials = udp_get_social_urls();
if ( empty( $socials ) ) {
	return;
}

$labels = array(
	'facebook'  => 'Facebook',
	'twitter'   => 'Twitter / X',
	'instagram' => 'Instagram',
	'youtube'   => 'YouTube',
	'linkedin'  => 'LinkedIn',
	'tiktok'    => 'TikTok',
);
?>
<ul class="udp-footer-social" role="list">
	<?php foreach ( $socials as $key => $url ) : ?>
		<li>
			<a
				href="<?php echo esc_url( $url ); ?>"
				class="btn-icon-circle"
				target="_blank"
				rel="noopener noreferrer"
				aria-label="<?php echo esc_attr( $labels[ $key ] ?? ucfirst( $key ) ); ?>"
			>
				<span aria-hidden="true"><?php echo esc_html( strtoupper( substr( $key, 0, 2 ) ) ); ?></span>
			</a>
		</li>
	<?php endforeach; ?>
</ul>
```

(Nota: por simplicidad usamos texto de 2 letras como placeholder en lugar de SVGs sociales. F10 polish puede sustituir por SVGs reales.)

- [ ] **Step 3: Reescribir `footer.php`**

Reemplazar TODO el contenido de `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/footer.php` por:

```php
<?php
/**
 * The template for displaying the footer
 *
 * @package Starter_Theme
 */
$copyright   = function_exists( 'get_field' ) ? get_field( 'copyright', 'option' ) : '';
$legal_links = function_exists( 'get_field' ) ? get_field( 'legal_links', 'option' ) : array();
?>
</main>

<footer class="udp-site-footer" role="contentinfo">
	<div class="udp-site-footer__inner">

		<div class="udp-site-footer__top">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="udp-site-footer__logo">
				<?php
				$logo = udp_get_logo_url( 'blanco' );
				if ( ! empty( $logo ) ) :
					?>
					<img src="<?php echo esc_url( $logo ); ?>" alt="<?php bloginfo( 'name' ); ?>" />
				<?php else : ?>
					<span><?php bloginfo( 'name' ); ?></span>
				<?php endif; ?>
			</a>

			<?php get_template_part( 'template-parts/footer/social' ); ?>
		</div>

		<?php get_template_part( 'template-parts/footer/columns' ); ?>

		<div class="udp-site-footer__bottom">
			<?php if ( ! empty( $copyright ) ) : ?>
				<p class="udp-site-footer__copyright"><?php echo esc_html( $copyright ); ?></p>
			<?php else : ?>
				<p class="udp-site-footer__copyright">&copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $legal_links ) && is_array( $legal_links ) ) : ?>
				<ul class="udp-site-footer__legal">
					<?php foreach ( $legal_links as $link ) : ?>
						<?php
						$url   = isset( $link['url'] ) ? $link['url'] : '';
						$label = isset( $link['label'] ) ? $link['label'] : '';
						if ( empty( $url ) || empty( $label ) ) {
							continue;
						}
						?>
						<li><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
```

- [ ] **Step 4: Reescribir `_footer.scss`**

Reemplazar TODO el contenido de `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/src/scss/layouts/_footer.scss` por:

```scss
// ==========================================================================
// FOOTER UDP — siempre dark
// ==========================================================================

.udp-site-footer {
    background: $dark-1;
    color: $white;
    padding-block: $space-4xl $space-xl;
    margin-top: $space-5xl;

    &__inner {
        @include container-udp;
    }

    &__top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: $space-xl;
        padding-bottom: $space-2xl;
        border-bottom: 1px solid $gray-medium;
        flex-wrap: wrap;
    }

    &__logo {
        display: inline-flex;
        align-items: center;
        text-decoration: none;
        color: $white;

        img {
            height: 32px;
            width: auto;
        }
    }

    &__bottom {
        margin-top: $space-2xl;
        padding-top: $space-md;
        border-top: 1px solid $gray-medium;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: $space-md;
        flex-wrap: wrap;
    }

    &__copyright {
        margin: 0;
        @include eyebrow-sm($white-70);
    }

    &__legal {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        gap: $space-md;

        a {
            @include eyebrow-sm($white-70);
            text-decoration: none;

            &:hover {
                color: $white;
            }
        }
    }
}

// --------------------------------------------------------------------------
// Footer columns
// --------------------------------------------------------------------------
.udp-footer-columns {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: $space-xl;
    padding-block: $space-2xl;

    @include media-down(lg) {
        grid-template-columns: repeat(2, 1fr);
    }

    @include media-down(md) {
        grid-template-columns: 1fr;
    }

    &__title {
        @include eyebrow($white-70);
        margin: 0 0 $space-md;
    }

    &__links {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: $space-2xs;

        a {
            color: $white;
            text-decoration: none;
            font-size: $font-size-body-sm;
            transition: opacity $transition-base;

            &:hover {
                opacity: 0.7;
            }
        }
    }
}

// --------------------------------------------------------------------------
// Footer social
// --------------------------------------------------------------------------
.udp-footer-social {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    gap: $space-2xs;
}
```

- [ ] **Step 5: Validar y verificar visualmente**

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/footer.php
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/footer/columns.php
/Applications/MAMP/bin/php/php8.4.1/bin/php -l /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/footer/social.php
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -5
```

Expected: 3× sintaxis OK + build OK.

```bash
curl -s "http://localhost:8888/udp/?theme=new&nocache=$(date +%s)" | grep -E "udp-site-footer|udp-footer-columns|udp-footer-social" | head -5
```

Expected: HTML contiene las clases del nuevo footer.

- [ ] **Step 6: Commit**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add footer.php template-parts/footer/columns.php template-parts/footer/social.php src/scss/layouts/_footer.scss
git commit -m "feat(chrome): UDP footer dark with columns + social + legal

Consumes options pages (Footer + Redes Sociales) creadas en F1."
```

---

## Task 10: Verificación end-to-end + actualizar MEMORY.md + cierre F2

**Files:**
- Modify: `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/MEMORY.md`

- [ ] **Step 1: Verificar que el sitio carga con header/footer nuevos**

```bash
curl -s -o /dev/null -w "%{http_code}\n" "http://localhost:8888/udp/?theme=new&nocache=$(date +%s)"
```

Expected: 200.

```bash
curl -s "http://localhost:8888/udp/?theme=new&nocache=$(date +%s)" | grep -cE "udp-site-header|udp-site-footer"
```

Expected: número >= 2 (al menos uno de cada).

- [ ] **Step 2: Verificar que body tiene la clase de tema correcta**

```bash
curl -s "http://localhost:8888/udp/?theme=new&nocache=$(date +%s)" | grep -oE 'body class="[^"]*"' | head -1
```

Expected: incluye `is-dark` (porque la home es archive de posts).

```bash
# Visitar una página single (post viejo) — debería ser is-light
SAMPLE_POST_URL=$(/Applications/MAMP/Library/bin/mysql80/bin/mysql --socket=/Applications/MAMP/tmp/mysql/mysql.sock -uroot -proot udp -N -e "SELECT post_name FROM wp_fnku4yposts WHERE post_status='publish' AND post_type='post' ORDER BY ID DESC LIMIT 1;")
curl -s "http://localhost:8888/udp/${SAMPLE_POST_URL}/?theme=new&nocache=$(date +%s)" | grep -oE 'body class="[^"]*"' | head -1
```

Expected: incluye `is-light`.

- [ ] **Step 3: Verificar build de producción**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme && npm run build 2>&1 | tail -8
```

Expected: build limpio. Verificar size del CSS:

```bash
ls -lh dist/css/theme.*.css
```

Expected: alrededor de 230-260 KB (creció con los nuevos componentes pero sigue razonable).

- [ ] **Step 4: Actualizar MEMORY.md**

Añadir al final de `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/MEMORY.md`:

```markdown
### 2026-04-27 — F2 Sistema de diseño + chrome completada

**Hechos**:
- `_variables.scss` reescrito con paleta UDP (brand-red #C81C0D, brand-accent #FF7064, brand-blue #4539F2, dark-1/2, gray-high/medium, white-70).
- Tipografía: Arizona Flare (display) + Work Sans (body) + Necto Mono (labels). Escala 12-48px.
- Espaciado mixto UDP (8/12/16/18/20/24/32/40/64/120) inyectado al `$spacers` map de Bootstrap.
- Overrides BS5: pill buttons (radius 9999px), flat cards (radius 0), container 1360px, grid 32px gutter.
- Mixins UDP: eyebrow, eyebrow-sm, card-hover-invert, huincha-list-item, container-udp, faculty-accent.
- 5 componentes nuevos: `_button.scss` (pill md/sm + icon-circle + primary + outline-light), `_card.scss` (evento + tema, hover-invert), `_eyebrow.scss`, `_huincha.scss` (marquee + lista), `_faculty-card.scss`.
- `inc/template-helpers.php` con 5 funciones: `udp_body_theme_class()`, `udp_get_logo_url()`, `udp_render_faculty_color_var()`, `udp_get_social_urls()`, `udp_get_footer_columns()`.
- `header.php` reescrito: dark, top-bar (logo + search + Accesibilidad CTA + user) + mega-menu trigger stub.
- `footer.php` reescrito: dark, top (logo + social) + 4 columnas (lee options Footer) + bottom (copyright + legal links).
- Body class `is-dark`/`is-light` aplicado dinámicamente según contexto.
- `?theme=new` muestra header/footer del nuevo diseño en cualquier página antigua.

**Pendientes**:
- Mega-menú real (panel multi-columna con links internos+externos): F8.
- SVGs sociales reales en lugar de placeholder de 2 letras: F10 polish.
- Bloques flexible content (`block_*`): F3 en adelante.
- Templates de single/archive UDP-styled: F4-F7.

**Cosas que descubrí**:
- Bootstrap `$spacers` map se EXTIENDE inyectándolo antes de su `@import`. Los valores custom (8/12/16/18/...) están disponibles como `.p-1` ... `.p-10`.
- `udp_body_theme_class()` se pasa a `body_class()` como string adicional — WP lo añade al array de clases sin pisar las defaults.
- `additionalData` en Vite inyecta variables ANTES de que se procese cualquier @use/@import en SCSS, por eso los componentes pueden usar `$brand-red` sin importar nada.
```

- [ ] **Step 5: Commit final F2**

```bash
cd /Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme
git add MEMORY.md
git commit -m "docs: log F2 design system and chrome completion in MEMORY.md"
```

- [ ] **Step 6: Verificación visual manual (a hacer por el usuario)**

Pídele al usuario:
1. Abrir `http://localhost:8888/udp/?theme=new` en navegador.
2. Confirmar visualmente: header dark con logo, top-bar con CTA Accesibilidad rojo, trigger Menú abajo. Footer dark con columnas (vacías hasta que se rellenen las options pages) y copyright.
3. Visitar un post: `http://localhost:8888/udp/<slug-de-noticia>/?theme=new`. Body debe ser light, header sigue dark.

Esto NO se puede automatizar via curl — es validación de aspecto.

---

## Coverage check vs. spec

Verificación de que cada elemento de F2 en la sección 6.2 del spec tiene un task que lo cubre:

| Spec F2 deliverable | Tasks |
|---|---|
| `_variables.scss` con tokens | Task 1 |
| `@font-face` y mixins | F0 hizo `@font-face`. Task 2 añade mixins UDP. |
| `header.php` con logo + top-bar + trigger menú (mega-menú stub) | Task 8 |
| `footer.php` con columnas + RRSS | Task 9 |
| Bloques base: button, card, eyebrow | Task 4 (button), Task 5 (card), Task 6 (eyebrow + huincha + faculty-card) |
| Lógica `body.is-dark/is-light` | Task 7 (helper PHP) + Task 8 (aplicación en header.php) + Task 8 (CSS rules en _header.scss) |
| Faculty color system | Task 3 (SCSS) + Task 7 (PHP helper `udp_render_faculty_color_var`) |

**Cobertura completa.**
