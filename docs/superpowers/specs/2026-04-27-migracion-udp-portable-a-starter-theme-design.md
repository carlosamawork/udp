# Migración UDP — de `udp_portable` a `starter-theme`

**Fecha**: 2026-04-27
**Estado**: Diseño aprobado por el usuario — pendiente revisión final del documento
**Plazo objetivo**: 8-12 semanas (plan original; el usuario buscará acortar con más trabajo donde sea posible)

---

## 1. Resumen ejecutivo

El sitio actual de la Universidad Diego Portales (`http://localhost:8888/udp/`) usa el tema `udp_portable`, con stack legacy (jQuery 1.12, MaterializeCSS, Slick, Masonry) y arquitectura acoplada: los CPTs y taxonomías están registrados directamente en el tema, y los grupos ACF viven solo en base de datos.

Esta migración traslada el frontend a `starter-theme` (Bootstrap 5 + Vite 6 + SCSS + ACF Pro) y refactoriza la capa de datos a un mu-plugin propio (`udp-core`) que sobrevive a cualquier cambio de tema futuro. Los datos existentes (~22.000 registros publicados entre posts, agenda, académicos, calendario, carreras, centros, concursos y páginas) se mantienen intactos: no hay migración masiva de contenido, solo refactor estructural.

El diseño visual proviene de un Figma cerrado (25 pantallas a 1440px). El admin podrá componer páginas con bloques flexibles ACF reutilizables.

El despliegue es progresivo: trabajo en local con un theme switcher por sesión que permite ver `starter-theme` sin romper el tema activo, hitos a entorno de pruebas conforme se cierran fases, y producción al final.

## 2. Contexto y alcance

### 2.1 Estado actual (inventariado el 2026-04-27)

**Configuración del sitio**:
- WordPress en MAMP, DB `udp` con prefijo `wp_fnku4y` (sin guion bajo)
- 33 GB de uploads, 13.267 adjuntos
- `show_on_front: posts` (la home actual es el índice de blog, no una página estática)

**Volumen de contenido publicado**:
- `post` (Noticias): 4.064
- `agenda`: 3.626
- `academico`: 691
- `calendario`: 505
- `carrera-udp`: 42
- `centro-udp`: 55
- `concurso-academico`: 3
- `page`: 123
- `contacto-udp` en draft: 8.055 (envíos del formulario, se mantienen como están — sin migración, sin purga)
- `acf-field-group`: 20 grupos · `acf-field`: 197 campos

**CPTs y taxonomías** (hoy registrados en `udp_portable/inc/`):
- CPTs: `agenda`, `academico`, `carrera-udp`, `centro-udp`, `contacto-udp`, `concurso-academico`, `calendario`
- Taxonomías: `facultad`, `carrera`, `area` (compartidas por agenda/academico/post/etc.); `area-udp`, `publico-udp`, `tipo-udp` (solo para `calendario`)

**Plugins activos (21)** — críticos para datos: ACF Pro, WP All Import Pro + WPAI ACF Add-on, CSV/XML Import for ACF, Classic Editor.

**Plugin propio `portable__plugin_ws`**: instalado pero NO activo. Su clase está prácticamente vacía. Se desinstala.

### 2.2 Lo que se mantiene

- Toda la base de datos (posts, CPTs, taxonomías, ACF values, medios, usuarios, comentarios, menús)
- Slugs de CPTs y taxonomías (sin renombrar, evita migración masiva)
- Plugins críticos de datos (ACF Pro, WP All Import, Classic Editor)
- Plugins de feeds sociales (Facebook, Twitter, Instagram, YouTube, Social Wall) — pendiente revisar si todos se mantienen tras lanzamiento
- Plugins infra (Wordfence, WP Fastest Cache, UpdraftPlus, WP Migrate DB, Easy WP SMTP, Pojo Accessibility)

### 2.3 Lo que cambia

- **Tema**: `udp_portable` → `starter-theme` (Bootstrap 5 + Vite + SCSS)
- **Capa de datos**: registros de CPTs/taxonomías se mueven del tema a un mu-plugin nuevo `udp-core`
- **ACF**: 20 grupos en DB → reorganizados a ~14 grupos en `acf-json/` versionado dentro de `starter-theme`
- **Home**: pasa de índice de blog a página estática nueva (`page_on_front`)
- **Stack frontend**: jQuery+Materialize+Slick+Masonry → Bootstrap 5 + Vite 6 + Work Sans/Arizona Flare/Necto Mono
- **Mega-menú**: nuevo componente UI multi-columna con links internos+externos
- **Plugin `portable__plugin_ws`**: se desinstala (no activo, no aporta)

### 2.4 Lo que queda fuera de scope

- **CPT `academico`** (691 entradas): no aparece en Figma, datos se mantienen intactos en DB para uso futuro, sin plantillas nuevas.
- **Plantillas legacy obsoletas** en `udp_portable/` — ver lista completa en apéndice (sección 9).
- **REST endpoints** del tema actual (`udp_portable/rest/get_academicos.php`, `get_agenda.php`, `get_posts.php`, `contacto.php`, `import_calendar.php`): revisar uno a uno. Los que tengan consumidores externos van a `udp-core`. Los que no se usen, se descartan.
- **Limpieza de revisiones acumuladas** (12.738): no urgente, queda como tarea de mantenimiento posterior.

## 3. Arquitectura — tres componentes, tres responsabilidades

```
┌────────────────────────────────────────────────────────────┐
│  starter-theme/  (presentación pura)                       │
│  · templates, template-parts, single/archive               │
│  · src/scss + src/js (Vite 6)                              │
│  · acf-json/  ← grupos de campos versionados               │
│  · template-parts/blocks/ ← bloques ACF flexible content   │
└────────────────────────────────────────────────────────────┘
                           ↓ consume datos
┌────────────────────────────────────────────────────────────┐
│  mu-plugins/udp-core/  (datos)                             │
│  · CPTs: agenda, academico, carrera-udp, centro-udp,       │
│    concurso-academico, calendario, contacto-udp            │
│  · Taxonomías: facultad, carrera, area, area-udp,          │
│    publico-udp, tipo-udp                                   │
│  · REST endpoints custom (los que sigan vigentes)          │
│  · Helpers compartidos (queries reusables)                 │
└────────────────────────────────────────────────────────────┘
                           y en paralelo (solo dev/local)
┌────────────────────────────────────────────────────────────┐
│  mu-plugins/udp-theme-switcher/  (solo dev/local)          │
│  · Filtros 'template' y 'stylesheet'                       │
│  · Activa starter-theme cuando ?theme=new o cookie         │
│  · No se carga en producción (gate por WP_ENV o constante) │
└────────────────────────────────────────────────────────────┘
```

### 3.1 `udp-core` (mu-plugin nuevo)

Vive en `wp-content/mu-plugins/udp-core/`. Es la capa de datos canónica del sitio.

- Mueve aquí lo que hoy hace `udp_portable/inc/post_types.php` y `udp_portable/inc/custom_taxonomies.php`.
- **Mantiene los slugs actuales** (`agenda`, `academico`, `carrera-udp`, etc.) → cero migración de datos.
- Sobrevive a cualquier cambio de tema futuro.
- Estructura interna: `udp-core.php` (loader), `inc/post-types.php`, `inc/taxonomies.php`, `inc/rest-endpoints.php`, `inc/helpers.php`.

### 3.2 `starter-theme` (existente, lo enriquecemos)

Vive en `wp-content/themes/starter-theme/`. Solo presentación.

- Templates, template-parts (incluyendo `template-parts/blocks/`), SCSS, JS, bloques ACF.
- Los grupos ACF se exportan de DB → reorganizan → guardan en `acf-json/` versionado.
- Build con Vite 6: `npm run dev` (HMR) y `npm run build` (producción).

### 3.3 `udp-theme-switcher` (mu-plugin solo dev/local)

Vive en `wp-content/mu-plugins/udp-theme-switcher/`.

- Cuando el usuario solicita una página con `?theme=new` o tiene la cookie correspondiente, los filtros `template` y `stylesheet` devuelven `starter-theme` aunque el tema activo siga siendo `udp_portable`.
- Permite ver progreso en local sin romper el sitio para el resto de visualizaciones.
- En producción se desinstala completamente (o se carga condicionalmente solo si `wp_get_environment_type() !== 'production'`, que es la API nativa desde WordPress 5.5).

## 4. Sistema de diseño

### 4.1 Paleta (extraída del Figma)

| Token SCSS | Hex | Uso |
|---|---|---|
| `$brand-red` | `#C81C0D` | CTA principal, botón "Accesibilidad" |
| `$brand-accent` | `#FF7064` | Item activo en menús, texto destacado sobre dark |
| `$brand-blue` | `#4539F2` | Hover de cards, acentos secundarios |
| `$dark-1` | `#1C1C1C` | Fondo principal del header/dark surfaces |
| `$dark-2` | `#232323` | Cards dark, paneles |
| `$gray-high` | `#454545` | Bordes iconos circulares |
| `$gray-medium` | `#4F4F4F` | Bordes botones sobre dark |
| `$white-70` | `rgba(255,255,255,0.7)` | Etiquetas/categorías sobre dark |

**Colores de facultad**: NO se hardcodean. Cada término de la taxonomía `facultad` tiene un campo ACF `color` (hoy en grupo `Foto Taxonomía` 7046, lo migramos al grupo `tax_facultad_fields`). En render se inyecta como CSS custom property:

```php
$color = get_field('color', 'facultad_' . $term_id);
echo '<article class="faculty-card" style="--faculty-color: ' . esc_attr($color) . ';">';
```

```scss
.faculty-card {
  --faculty-color: #{$brand-red}; // fallback
  border-top: 4px solid var(--faculty-color);
  .eyebrow { color: var(--faculty-color); }
}
```

### 4.2 Tipografía

| Familia | Uso | Estado |
|---|---|---|
| **Work Sans** | UI, body, navegación | Pendiente añadir desde Google Fonts a `src/scss/fonts/` |
| **ABC Arizona Flare** | Display, H1/H2, hero titles | OTF en `src/scss/fonts/` (7 pesos) — convertir a WOFF2 |
| **Necto Mono** | Eyebrows, labels uppercase con tracking | OTF en `src/scss/fonts/` (Regular) — convertir a WOFF2 |

**Conversión OTF → WOFF2**: paso de build con `fonttools` (`pyftsubset`). Reduce ~1 MB total a ~300-400 KB. Subset a latín-básico + español. `font-display: swap`.

**Escala** (en `_variables.scss`):

```scss
$font-sizes: (
  display-1:  48px,  // h1, hero — Arizona Flare Medium
  display-2:  40px,  // h2 — Arizona Flare Medium
  h3:         24px,  // títulos card — Work Sans Medium
  h4:         20px,  // subtítulos — Work Sans Medium
  body-lg:    16px,  // cuerpo — Work Sans Medium
  body-sm:    14px,  // metadatos — Work Sans SemiBold
  label-md:   14px,  // eyebrow uppercase — Necto Mono, tracking 0.8px
  label-sm:   12px,  // caption — Necto Mono, tracking 0.8px
);
```

### 4.3 Espaciado

Escala mixta detectada en Figma: `8 / 12 / 16 / 18 / 20 / 24 / 32 / 40 / 64 / 120 px`. No es escala estricta de 4 ni de 8 — se respeta tal cual.

### 4.4 Overrides de Bootstrap (en `_variables.scss`, antes del import de Bootstrap)

| Override | Valor | Razón |
|---|---|---|
| `$container-max-widths.xxl` | `1360px` | Frame Figma 1440 con padding lateral 40 |
| `$grid-gutter-width` | `32px` | Gap detectado en grids |
| `$btn-border-radius` | `9999px` | Botones tipo pill |
| `$btn-padding-x` | `16px` | |
| `$card-border-radius` | `0` | Cards flat sin esquinas |
| `$card-spacer-y/x` | `18px / 18px` | Padding interno detectado |
| `$body-bg` | `#1C1C1C` | Diseño dark-first (hover invierte a light) |
| `$body-color` | `#FFFFFF` | |

### 4.5 Theming dark/light mixto

El sitio mezcla páginas dark y light. Modelo basado en contexto de plantilla:

```scss
body.is-dark { background: $dark-1; color: $white; }
body.is-light { background: $white; color: $dark-1; }
```

```php
// header.php
$theme_class = (is_front_page() || is_home() || is_archive() || is_post_type_archive())
  ? 'is-dark'
  : 'is-light';
echo '<body class="' . join(' ', get_body_class($theme_class)) . '">';
```

Adicionalmente, cada bloque flexible tendrá un selector ACF `theme: light|dark|inherit` para forzar el modo de un bloque concreto.

| Contexto | Tema por defecto |
|---|---|
| Home, archivos (Noticias, Agenda, Calendario, Concursos, Facultades) | dark |
| Singles (`post`, `agenda`, `calendario`, `concurso-academico`, `carrera-udp`, `centro-udp`) | light |
| Páginas (`page`) — Universidad, Pregrado, Servicios, Webmail, Accesos | light |
| Header / Footer | siempre dark (independiente del body) |

### 4.6 Estructura SCSS

```
src/scss/
├── main.scss                    ← entry, imports en orden
├── editor.scss                  ← Gutenberg editor styles
├── utilities/
│   ├── _variables.scss          ← tokens (overrides BS5 ANTES de imports)
│   ├── _faculty-colors.scss     ← helpers para CSS vars de facultad
│   ├── _typography.scss         ← @font-face, mixins de tipo
│   ├── _mixins.scss             ← eyebrow, card-hover-invert, huincha-list
│   └── _spacing.scss            ← escala custom
├── layouts/
│   ├── _header.scss
│   ├── _footer.scss
│   ├── _container.scss
│   └── _mega-menu.scss
├── components/
│   ├── _button.scss             ← pill, sizes, icon-circle
│   ├── _card.scss               ← evento, tema, facultad
│   ├── _eyebrow.scss
│   ├── _huincha.scss
│   └── _faculty-card.scss
└── blocks/                      ← uno por bloque ACF
    ├── _hero.scss
    ├── _card-grid.scss
    ├── _facultades-mosaic.scss
    └── …
```

### 4.7 Mixins clave

```scss
@mixin eyebrow {
  font-family: 'Necto Mono', monospace;
  font-size: 14px;
  letter-spacing: 0.8px;
  text-transform: uppercase;
  color: rgba(255,255,255,0.7);
}

@mixin card-hover-invert {
  // base: bg dark, texto blanco
  // hover: bg blanco o azul, texto dark
}

@mixin huincha-list {
  // lista vertical: serif 48px + mono 14px en línea con número
}
```

## 5. Catálogo de bloques ACF

### 5.1 Estructura general de plantillas de página

Casi todas las páginas usan `templates/page-flexible.php`:

```
header (siempre)
├── hero específico de la página
├── flexible_content "secciones" (los bloques de la lista 5.2)
└── footer (siempre)
```

Las plantillas con layout fijo (singles de noticia, agenda, calendario; mega-menú) tienen su propio template no-flexible.

### 5.2 Bloques flexible content (compartidos)

Cada bloque vive en `template-parts/blocks/block-<slug>.php` + SCSS en `src/scss/blocks/_<slug>.scss` + definición ACF en `acf-json/group_block_<slug>.json`.

| # | Bloque | Slug | Campos clave | Usado en |
|---|---|---|---|---|
| 1 | Hero de página | `block_hero` | título, subtítulo, eyebrow, imagen fondo, CTA opcional, theme | Casi todas |
| 2 | Texto + imagen | `block_text_image` | layout (img-izq/img-der), título, contenido WYSIWYG, imagen, theme | Conoce UDP, Historia, Pregrado |
| 3 | Grid de cards | `block_card_grid` | título, fuente (manual/CPT post/agenda/concurso), filtros taxonomía, nº items, layout (3col/4col/lista), theme | Home, Noticias, Agenda, Concursos |
| 4 | Mosaico facultades | `block_facultades_mosaic` | título, descripción opcional. 7 enlaces auto desde taxonomy `facultad` con su color ACF | Home, Facultades |
| 5 | Huincha texto continuo | `block_huincha` | texto, velocidad, theme | Home, secciones decorativas |
| 6 | CTA banner | `block_cta_banner` | título, texto, botón (label, link, estilo), theme | Múltiples |
| 7 | Galería de imágenes | `block_image_gallery` | layout (mosaico/carrusel/grid), imágenes (repeater), pies opcionales | Anuarios, Aniversario, especiales |
| 8 | Acordeón | `block_accordion` | título, items (repeater: heading + WYSIWYG) | Gobernanza, Reglamentos, Forma de Gobierno |
| 9 | Lista de personas | `block_people_list` | título, fuente, layout (grid/lista), tarjeta (foto, nombre, cargo, descripción) | Consejo Académico |
| 10 | Tabla calendario | `block_calendario_grid` | mes, año, eventos auto del CPT `calendario`, filtros taxonomía | Calendario Académico |
| 11 | Tarjeta facultad detalle | `block_facultad_detail` | datos auto del término `facultad` + repeater de carreras | Página Facultades |
| 12 | Premios/distinciones | `block_premios_list` | título, items repeater (año, premio, persona, descripción) o auto desde CPT | Premios, Doctorado HC, Premios Nacionales |
| 13 | Botones/accesos grandes | `block_big_buttons` | items repeater (label, link, icono, estilo) | Accesos Internos, Servicios |
| 14 | Embed externo | `block_embed` | URL (YouTube/Vimeo) o código, ratio | Especiales |
| 15 | WYSIWYG suelto | `block_wysiwyg` | contenido (rich text) — fallback flexible | Cualquiera |

### 5.3 Templates fijos (no flexible content)

| Template | Notas |
|---|---|
| `template-parts/header.php` | Logo, search trigger, top-bar, trigger mega-menú |
| `template-parts/footer.php` | Logo, columnas de links, RRSS, copyright |
| `template-parts/mega-menu.php` | Multi-columna, links internos + externos, panel dark |
| `single-post.php` | Hero noticia + contenido + meta + relacionadas |
| `single-agenda.php` | Hero evento + datos (fecha/hora/lugar) + descripción + CTA inscripción |
| `single-calendario.php` | Detalle de fecha (compacto) |
| `single-concurso-academico.php` | Datos + bases + descarga PDF |
| `single-carrera-udp.php` | Hero + plan estudios + admisión + facultad madre |
| `single-centro-udp.php` | Hero + sobre el centro + investigadores + actividades |
| `archive-post.php` (Noticias) | Hero + filtros + grid + paginación |
| `archive-agenda.php` | 2 vistas: cuadrícula + lista (toggle) + filtros + paginación |
| `archive-calendario.php` | Vista calendario por mes + filtros |
| `archive-concurso-academico.php` | Grid con filtros |
| `archive-carrera-udp.php` | Agrupado por facultad |
| `archive-centro-udp.php` | Grid con filtros |

### 5.4 Grupos ACF en `acf-json/`

| Grupo nuevo | Ubicación | Reemplaza al actual |
|---|---|---|
| `block_<slug>` × 15 | Page > template = Flexible | nuevos |
| `cpt_post_fields` | Post type = post | `Campos para Entradas` (118) + `campos para noticias` (922) — fusionar |
| `cpt_agenda_fields` | Post type = agenda | `Campos para Agenda` (122) |
| `cpt_calendario_fields` | Post type = calendario | `Calendario Fields` (24606) |
| `cpt_concurso_fields` | Post type = concurso-academico | `Campos Concursos` (7036) |
| `cpt_carrera_fields` | Post type = carrera-udp | `Campos Carreras` (443) |
| `cpt_centro_fields` | Post type = centro-udp | `Campos para Centros` (6750) |
| `tax_facultad_fields` | Taxonomy = facultad | mover `color` desde `Foto Taxonomía` (7046) |
| `tax_area_fields` | Taxonomies area, area-udp | mover desde `Foto Taxonomía` |
| `options_general` | Options page | `Campos Generales` (149) |
| `options_header` | Options page | `Menu Principal` (234) + parte de `Secciones` |
| `options_footer` | Options page | parte de `Secciones` (313) |
| `options_redes_sociales` | Options page | (verificar si ya existe en starter-theme inicial; si no, crear) |

**Grupos actuales que se descartan (obsoletos)**: `Color` (280), `Slider Principal` (661), `Links Destacados` (319), `Secciones Home` (247), `Campos para Filtros` (488), `Link Externo` (10803), `Campos para Publicaciones` (7039).

### 5.5 Decisiones ACF clave

1. **Flexible content por defecto**: el admin compone páginas con bloques en orden libre. Singles de CPT mantienen layout cerrado (no flexible).
2. **JSON local como fuente de verdad**: ACF Pro auto-sincroniza `acf-json/` ↔ DB. Versionable en git, despliegues sin tocar UI.
3. **Importación inicial**: WP-CLI `wp acf export` los 20 grupos actuales como base → reorganizar a la lista de 5.4 → guardar en `acf-json/`.

## 6. Plan de fases

### 6.1 Diagrama de dependencias

```
F0 Foundation ──► F1 udp-core ──► F2 Design system + header/footer
                                       │
                                       ▼
                              F3 Páginas estáticas simples (Pregrado, Conoce UDP)
                                       │
            ┌──────────────────────────┼──────────────────────────┐
            ▼                          ▼                          ▼
   F4 Listings volumen         F5 Calendario+Concursos    F6 Facultades+Carreras+Centros
   (Noticias, Agenda)
            │                          │                          │
            └──────────────────────────┼──────────────────────────┘
                                       ▼
                          F7 Resto Universidad + Servicios + Accesos
                                       │
                          ┌────────────┴────────────┐
                          ▼                          ▼
                F8 Mega-menú completo          (paralelizable)
                          │                          │
                          └────────────┬─────────────┘
                                       ▼
                                   F9 Home
                                       │
                                       ▼
                  F10 Polish (a11y, perf, fuentes WOFF2)
                                       │
                                       ▼
                  F11 Switch local + despliegue a pre
                                       │
                                       ▼
                       F12 QA pre + aprobación + producción
```

### 6.2 Detalle por fase

| Fase | Trabajo | Visible al final | Effort |
|---|---|---|---|
| **F0 — Foundation** | `mysqldump` de la DB local guardado fuera del proyecto como punto de restauración (uploads NO se respaldan: nuestro trabajo no toca `/uploads/`) · Crear mu-plugin `udp-theme-switcher` · Convertir OTF→WOFF2, descargar Work Sans · `npm install` en starter-theme, validar Vite · Carpeta `acf-json/` creada · `git init` del proyecto si procede | `?theme=new` carga starter-theme con SCSS base | 1-2 días |
| **F1 — udp-core** | Crear mu-plugin `udp-core` · Mover registro de CPTs y taxonomías desde `udp_portable/inc/` (slugs intactos) · Test: con tema viejo activo, los datos siguen funcionando · Exportar 20 grupos ACF actuales → reorganizar a la lista de 5.4 → guardar en `acf-json/` | DB intacta, datos accesibles desde ambos temas, ACF en JSON versionable | 2-3 días |
| **F2 — Design system + chrome** | `_variables.scss` con tokens · `@font-face` y mixins · `header.php` con logo + top-bar + trigger menú (mega-menú stub) · `footer.php` con columnas + RRSS · Bloques base: button, card, eyebrow · Lógica `body.is-dark/is-light` | `?theme=new` enseña header/footer del nuevo diseño en cualquier página antigua | 3-4 días |
| **F3 — Páginas estáticas simples** | Bloques `block_hero`, `block_text_image`, `block_wysiwyg`, `block_cta_banner` · `templates/page-flexible.php` · Construir Pregrado + Conoce UDP en admin | Esas 2 páginas se ven completas | 1 semana |
| **F4 — Listings volumen** | `archive-post.php` + `single-post.php` · `archive-agenda.php` (toggle cuadrícula/lista) + `single-agenda.php` · `block_card_grid` con variantes · Filtros por facultad/área · Paginación | Noticias (4.064) y Agenda (3.626) operativas | 1-2 semanas |
| **F5 — Calendario + Concursos** | `archive-calendario.php` con vista mes · `single-calendario.php` · `block_calendario_grid` · `archive-concurso-academico.php` + `single-` | Calendario (505) + Concursos (3) operativos | 1 semana |
| **F6 — Facultades + Carreras + Centros** | `block_facultades_mosaic` (lee taxonomy `facultad` con color ACF) · Página Facultades · `archive-carrera-udp.php` + `single-` · `archive-centro-udp.php` + `single-` (lenguaje Figma derivado) | Sección académica completa | 1-2 semanas |
| **F7 — Resto Universidad + Servicios** | Bloques `block_accordion`, `block_premios_list`, `block_people_list`, `block_image_gallery`, `block_big_buttons`, `block_huincha`, `block_embed` · Construir contenido en admin: Historia, Anuarios, Premios, Doctorado HC, Gobernanza, Forma de Gobierno, Consejo Académico, Premios Nacionales, Servicios, Webmail, Accesos Internos | ~11 páginas terminadas | 1-2 semanas |
| **F8 — Mega-menú completo** | Estructura datos · `mega-menu.php` multi-columna · Links internos + externos · Lógica responsive (mobile drawer) · Animaciones | Header completo y funcional | 3-5 días |
| **F9 — Home** | Construir Home en admin · Cambiar `show_on_front` → page · Ajustes específicos del Figma de Home | Home nueva lista | 3-5 días |
| **F10 — Polish** | Auditoría a11y · Performance: subset fuentes, lazy load imágenes, build Vite producción · Cache: WP Fastest Cache config · 404, search, formulario contacto | Producto pulido | 3-5 días |
| **F11 — Switch local** | Desactivar `udp-theme-switcher` · Activar `starter-theme` como `template`/`stylesheet` · Validar todo el sitio sin `?theme=new` | Sitio local 100% nuevo tema | 1-2 días |
| **F12 — Despliegue pre + prod** | Sync code via git/deploy · DB migration con WP Migrate DB · Sync uploads diferenciales (rsync) · QA pre · Aprobación · Replicar a prod | En producción | Variable |

**Total**: 8-12 semanas (1 dev). El usuario buscará comprimir donde sea viable sin generar deuda técnica.

### 6.3 Hitos de despliegue a entorno de pruebas

No subir tras cada fase — subir cuesta tiempo. Hitos lógicos:
1. **Tras F3**: chrome + 2 páginas para validar look & feel macro.
2. **Tras F4**: listings de alto volumen para que editores prueben con datos reales.
3. **Tras F7**: todo el contenido editorial cubierto, falta home + mega-menú.
4. **Tras F11**: hito final pre-producción.

### 6.4 Workflow día a día

1. Trabajo en local con `?theme=new` activo.
2. Cualquier nuevo bloque/template → commit a git del repo del tema y del mu-plugin.
3. Cambios ACF en local → ACF auto-guarda a `acf-json/` → commit.
4. Cambios de contenido (páginas, posts) → en DB local; no se versionan.
5. Subida a pre: code via git, ACF se sincroniza solo, contenido a mano o vía export/import puntual.

## 7. Open items / bloqueantes pendientes antes de F0

1. **Confirmación de licencia web** de ABC Arizona Flare y Necto Mono (Dinamo y similar suelen vender print/web por separado). Las OTF están en `src/scss/fonts/` pero no tenemos confirmación explícita de licencia para web embedding.
2. **Acceso al entorno de pruebas** — URL, credenciales SSH/SFTP, método de sync (rsync, git deploy, FTP).
3. **Inicialización git** del proyecto local — el directorio actual NO es un repositorio git. Hay que decidir si se versiona el WordPress completo, solo `wp-content/themes/starter-theme` + `wp-content/mu-plugins/udp-core` (recomendado), o ambos como repos separados.
4. **Hex de las 7 facultades** — el sistema lee `get_field('color', 'facultad_<term_id>')` automáticamente. Validar que todos los términos de la taxonomía `facultad` tienen su color ACF asignado en DB (no asumir).
5. **Inventario de consumidores externos** de los REST endpoints actuales (`udp_portable/rest/`). Si hay apps móviles, scripts de terceros o widgets que llamen a esos endpoints, su URL debe preservarse en `udp-core` aunque internamente se reescriba.
6. **Plugins de feeds sociales** (Facebook/Twitter/Instagram/YouTube + Social Wall) — ¿se mantienen o se sustituyen por algo más ligero? No bloquea F0 pero conviene decidir antes de F9 (Home), donde se renderiza esa data.

## 8. Apéndice — Mapeo Figma ↔ datos existentes

| Figma | CMS |
|---|---|
| Home UDP | Home estática nueva (cambia `show_on_front` a `page`) |
| Pregrado | Página existente `Pregrado y Formación General` (reskin) |
| Facultades | Nueva página, mosaico = taxonomía `facultad` |
| Conoce UDP / Historia / Anuarios / Premios / Doctorado HC / Gobernanza / Forma de Gobierno / Consejo Académico / Premios Nacionales | Hijas de Universidad (algunas existen, otras nuevas) |
| Calendario Académico | Página existente, lista CPT `calendario` (505) |
| Noticias listado + interior | Página + single CPT `post` (4.064) |
| Eventos cuadrícula + lista | Página `Agenda`, CPT `agenda` (3.626) |
| Concursos académicos listado + interior | Listado + single CPT `concurso-academico` (3) |
| Servicios / Webmail / Accesos Internos | Páginas existentes (reskin) |
| `carrera-udp` (42) | Nueva página + single derivados del lenguaje Figma |
| `centro-udp` (55) | Nueva página + single derivados del lenguaje Figma |
| `academico` (691) | Fuera de scope, datos intactos en DB |
| Mega-menú | Componente UI real, multi-columna, links internos + externos |

## 9. Apéndice — Plantillas legacy a descartar

De `udp_portable/`:
- `page-backup-12-07-2021.php`
- `page-38.php`
- `page-7081.php`
- `page-template-expeciales.php` (typo en slug — su funcionalidad pasa al sistema flexible si aplica)

## 10. Apéndice — Plugins activos (21)

- **Críticos para datos**: ACF Pro, WP All Import Pro, WPAI ACF Add-on, CSV/XML Import for ACF, Classic Editor
- **Feeds sociales**: Custom Facebook Feed Pro, Custom Twitter Feeds Pro, Instagram Feed Pro, YouTube Feed Pro, Social Wall
- **Infra/seguridad/perf**: Wordfence, WP Security Audit Log Premium, WP Fastest Cache (+ Premium), UpdraftPlus, Easy WP SMTP, WP Mail Logging, WP Migrate DB, Pojo Accessibility, Admin Columns Pro

`portable__plugin_ws` está instalado pero NO activo — se desinstala en F0.
