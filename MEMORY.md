# MEMORY.md — Bitácora del proyecto

> Registro de sesiones del agente. No borrar entradas anteriores.

---

### 2026-03-18 — Inicio del archivo

- Se creó este archivo como parte de la configuración inicial del proyecto.
- Se actualizó `CLAUDE.md` con dos bloques nuevos:
  - **MEMORY.md — Bitácora de sesión**: instrucciones para leer/escribir este archivo al abrir y cerrar sesión.
  - **Reglas de trabajo**: obligación de buscar en internet antes de implementar cualquier cosa externa, y no proceder si hay dudas de compatibilidad.
- Versiones en uso confirmadas en CLAUDE.md: Vite 6, Bootstrap 5, ACF Pro, WordPress última estable.

### 2026-03-18 — Cierre de sesión

- Estado: proyecto en configuración inicial, sin cambios en código fuente.
- Próximos pasos sugeridos: iniciar desarrollo de features o ajustar la configuración de Vite/SCSS según necesidades del proyecto.

### 2026-04-27 — F0 Foundation completada

Migración WordPress udp_portable → starter-theme. F0 cubre infraestructura.

**Hechos**:
- DB respaldada con `mysqldump` comprimido en `~/Backups/udp/udp-pre-migration-2026-04-27.sql.gz` (25 MB).
- Git init en `starter-theme/` y `wp-content/mu-plugins/` (repos separados).
- Vite 6 toolchain validada — `npm install` + `npm run build` ok (522ms).
- Mu-plugin `udp-theme-switcher` operativo: `?theme=new` previsualiza `starter-theme` sin tocar el tema activo. Solo carga si `wp_get_environment_type() !== 'production'`.
- 8 fuentes Arizona Flare + Necto Mono convertidas OTF→WOFF2 (woff2_compress vía Homebrew). OTFs archivadas en `_otf-source/` (gitignored).
- Work Sans 400/500/600 self-hosted vía `@fontsource/work-sans` y copiada a `src/scss/fonts/`.
- `@font-face` declaradas en `src/scss/utilities/_typography.scss`, importado en `main.scss`. Vite copia fuentes a `dist/fonts/` con hash y reescribe URLs correctamente.
- Carpeta `acf-json/` creada (vacía, lista para F1). Auto-sync ya cableado en `inc/acf-setup.php`.

**Desviaciones del plan original**:
1. **`vite.config.js`**: cambiado `additionalData` de `@use` a `@import` legacy (deprecated pero funcional). Razón: con `@use ... as *`, las variables no se propagan al scope de mixins definidos en otros archivos, causando "Undefined variable" al usar mixins desde components/. Refactor a `@use` puro requiere modificar todos los SCSS — fuera de scope F0.
2. **`vite.config.js`**: añadido `loadPaths: [path.resolve(__dirname, 'src/scss')]` para resolución de paths.
3. **`vite.config.js`**: corregido `base` path de `/wp-content/themes/starter-theme-bs5/dist/` (nombre antiguo del package) a `/wp-content/themes/starter-theme/dist/`.
4. **`wp-config.php`**: añadido `define('WP_ENVIRONMENT_TYPE', 'local')`. Sin esto, `wp_get_environment_type()` devuelve 'production' por defecto y el mu-plugin theme switcher hace early return.
5. **Permisos npm cache**: `~/.npm` tenía permisos rotos por uso previo de `sudo npm`. Resuelto con `sudo chown -R 501:20 ~/.npm`.

**Pendiente para F1**:
- Crear mu-plugin `udp-core` con CPTs/taxonomías (mover desde `udp_portable/inc/`).
- Mapeo ACF aprobado: 20 grupos → 8 CPT meta + 2 tax meta + 4 options + 15 block (consolidando 23 layouts duplicados).
- Plugin `portable__plugin_ws` aún instalado pero NO activo — desinstalación queda para F1.

**Cosas que descubrí en el proceso (deuda / mejoras futuras)**:
- WP Fastest Cache cachea respuestas — durante desarrollo conviene desactivarlo o usar bypass.
- `_mixins.scss` referencia `$transition-base` y otras variables — sí existen, el problema era el scope de @use.

### 2026-04-27 — F1 udp-core + ACF refactor completada

**Hechos**:
- Mu-plugin `udp-core` operativo en `wp-content/mu-plugins/udp-core/` con loader `udp-core-loader.php` en raíz.
- 7 CPTs registrados (slugs intactos): agenda, academico, carrera-udp, centro-udp, contacto-udp, concurso-academico, calendario.
- 6 taxonomías existentes registradas (facultad, carrera, area, area-udp, publico-udp, tipo-udp) + 1 nueva: `tipo-contenido` (reemplaza el field politicas_publicas duplicado en 3 CPTs).
- Hook `init` priority 99 garantiza que mu-plugin gana sobre el tema legacy (que registra a priority 0).
- 14 grupos ACF nuevos en `acf-json/`: 8 CPT meta + 2 tax meta + 4 options. Reusan field keys originales para preservar 22.000+ registros.
- Options pages reorganizadas: 4 sub-páginas temáticas (General, Header, Footer, Redes Sociales) reemplazan la única "opciones-generales-del-sitio".
- 7 grupos ACF descartados desactivados (Color, Foto Taxonomía, Link Externo, Links Destacados, Slider Principal, Campos Para Filtros, Campos para Publicaciones).
- Migración de datos `politicas_publicas` (true_false) → término "Políticas Públicas" de la taxonomía nueva `tipo-contenido`. 117 registros migrados (68 post + 18 agenda + 31 centro-udp).

**Pendientes**:
- Los 13 grupos viejos NO descartados (CPT meta + Menu Principal + Campos Generales + 2 flexible_content) siguen activos. Desactivar después de F11 cuando se valide que los nuevos cubren todo.
- Los 15 grupos `block_*` del catálogo se autoran cuando se construya cada bloque (F3+).
- Plugin `portable__plugin_ws` aún instalado y NO activo. Desinstalar en F11.
- Eliminar meta_keys `politicas_publicas` cuando se valide la taxonomía en producción (post-F11).
- 2 field groups PHP huérfanos en `inc/acf-setup.php` (group_theme_general, group_theme_social) marcados con TODO breadcrumb — se eliminan cuando Tasks 11-12 estén integrados (de hecho, ya están — los slugs nuevos `udp-options-*` ya tienen sus grupos en JSON).

**Cosas que descubrí en el proceso**:
- ACF Pro NO tiene WP-CLI command nativo (`wp acf` no existe sin plugin auxiliar). Solucionado con script PHP que usa `acf_import_field_group()` directamente, ejecutado vía `wp eval-file`.
- Los CPTs registrados duplicados (en theme + mu-plugin) NO causan errores: el segundo `register_post_type` con mismo slug sobrescribe args silenciosamente. PERO el ÚLTIMO en registrar gana (no el primero), por lo que mu-plugin debe ir a priority HIGHER que legacy theme.
- Field keys son la única forma de preservar data — los names/labels se pueden cambiar sin pérdida.
- Vite config tenía `base: starter-theme-bs5` (nombre antiguo) que rompía URLs de fonts en build — corregido en F0.

### 2026-04-27 — F2 Sistema de diseño + chrome completada

**Hechos**:
- `_variables.scss` reescrito con paleta UDP (brand-red #C81C0D, brand-accent #FF7064, brand-blue #4539F2, dark-1/2, gray-high/medium, white-70).
- Tipografía: Arizona Flare (display) + Work Sans (body) + Necto Mono (labels). Escala 12-48px.
- Espaciado mixto UDP (8/12/16/18/20/24/32/40/64/120) inyectado al `$spacers` map de Bootstrap (extendido a indices 0-10 — warning en comentarios sobre que utility classes BS no mapean a defaults).
- Overrides BS5: pill buttons (radius 9999px), flat cards (radius 0), container 1360px, grid 32px gutter.
- Mixins UDP añadidos: eyebrow, eyebrow-sm, card-hover-invert, huincha-list-item, container-udp, faculty-accent.
- 5 componentes nuevos: `_button.scss` (pill md/sm + icon-circle + primary + outline-light), `_cards.scss` (evento + tema, hover-invert), `_eyebrow.scss`, `_huincha.scss` (marquee + lista), `_faculty-card.scss`.
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
- Bootstrap `$spacers` map se EXTIENDE inyectándolo antes de su `@import`. Los valores custom (8/12/16/18/...) están disponibles como `.p-1` ... `.p-10`, PERO no mapean a los valores canónicos de BS — añadido warning en comentario del archivo.
- `udp_body_theme_class()` se pasa a `body_class()` como string adicional — WP lo añade al array de clases sin pisar las defaults.
- `additionalData` en Vite inyecta variables ANTES de que se procese cualquier @use/@import en SCSS, por eso los componentes pueden usar `$brand-red` sin importar nada.
- Las options pages General y Redes Sociales YA tenían data (logo + 6 redes) — el footer/header renderizan con valores reales.
- Renombrar variables ($font-family-heading → $font-family-display, $section-spacing → $space-5xl) requirió ajustes colaterales en editor.scss y el mixin section-padding.

### 2026-04-27 — Footer pixel-perfect F2 (Figma 4401:23290)

**Hechos**:
- Reescritos `footer.php`, `template-parts/footer/social.php` y `src/scss/layouts/_footer.scss` para matchear el frame Figma 4401:23290.
- Nuevos template-parts: `template-parts/footer/contact.php` (6 mini-bloques de contacto) y `template-parts/footer/acreditacion.php` (sello CNA).
- El footer Figma NO tiene columnas de links ni copyright/legal — esos quedan deprecated en render pero el template-part `columns.php`, los helpers `udp_get_footer_columns()` y los fields ACF (copyright, legal_links, columnas_footer) se conservan para uso futuro.
- BG cambió de `$dark-1` a `#000` puro. Padding fijo `70px 40px` (Figma).
- Iconos sociales: SVGs inline monocromos (LinkedIn, Instagram, YouTube priorizados según Figma; FB, Twitter/X, TikTok también con SVG si están configurados). Círculo 44px, border `#454545`, hover invierte a fondo blanco / icono negro.
- Contact strip usa CSS variable `--udp-footer-contact-w` por bloque para anchos fijos (317px / 175px) — cae a 100% en `<lg`.
- Acreditación se renderiza solo si está configurado `logo_acreditacion` en options General.

**Pendientes / TODO**:
- Crear field group ACF en `udp-options-footer` para los 6 bloques de contacto (`direccion_value`, `telefono_*`, `email_*`) — ahora hardcoded vía filter `udp_footer_contact_blocks`.
- Subir imagen `logo_acreditacion` real en options page General si todavía no está cargada.
- Validar SVGs sociales contra los del Figma (paths actuales son monocromos genéricos — F10 polish).


### 2026-04-27 — fix(acf): contact_blocks.enlace cambiado de url a text

- Sub-field `enlace` del repeater `contact_blocks` (group_options_footer, ID 55199) cambiado de `type: "url"` a `type: "text"` con placeholder `tel:+56... | mailto:... | https://...`.
- Razón: ACF `type:url` valida solo http/https y rechaza esquemas `tel:` y `mailto:` con "El valor debe ser una URL válida". El template `contact.php` ya escapa con `esc_url()` que sí acepta tel/mailto/http(s).
- Re-sync vía script `/tmp/acf-resync-footer-upsert.php` con upsert por ID (acf_get_field_group + inyección de ID) — UPDATE confirmado, sin duplicados (count=1).
- Verificación SQL: `post_content` ahora contiene `s:4:"type";s:4:"text"`.
- HTTP 200, JSON válido. Commit f39fa0c.


### 2026-04-27 — F3 Task 5: Swiper.js install + módulo JS + init

- Instalado `swiper@12.1.3` como `dependency` (runtime, no devDep).
- Creado `src/js/modules/section-landing-swiper.js`: usa `qsa('.udp-section-cards--swiper')` y hace **lazy import** de Swiper + `Navigation/Keyboard/FreeMode` + CSS solo si hay containers en la página. Config: `slidesPerView:'auto'`, `spaceBetween: 33` (16 en mobile), `freeMode + momentum`, `keyboard`, `grabCursor`.
- Wire en `src/js/main.js`: añadido `import { initSectionLandingSwiper } from '@modules/section-landing-swiper'` y llamada en `domReady()` antes del `console.log`.
- Build OK (`npm run build`, 652ms): Vite generó chunk separado `dist/js/chunks/swiper.0ByW75It.js` (62K) y `dist/css/swiper.vL0Q9Dr-.css` (4.69 kB) gracias al `await import()` dinámico. `main.js` quedó en 84K (sin crecer significativamente respecto al baseline ~83K — Swiper no entra en el bundle principal).
- No commit (Task 6 los agrupa).

### 2026-04-28 — F3 Section Landing Template completada

**Hechos**:
- Page template `templates/page-section-landing.php` con header `Template Name: Section Landing`. Asignable desde el dropdown del editor a cualquier página.
- ACF group `group_template_section_landing` (ID 55208) con location `page_template == templates/page-section-landing.php`. Estructura: hero (eyebrow + titulo + bajada + imagen_fondo) + cards_display radio (grid|swiper, default grid) + cards repeater (eyebrow + titulo + descripcion + imagen + link).
- Template-parts en `template-parts/sections/`: `section-landing-hero.php`, `section-landing-cards.php` (container condicional), `section-landing-card.php` (single reutilizable).
- SCSS único `_section-landing.scss` con: hero responsivo, grid 5 cols desktop / 3 tablet / scroll-snap mobile, swiper con peek 150px y card 285×365 vs grid card 248×318, hover-invert dark→blue.
- JS module `section-landing-swiper.js` con lazy import de Swiper.js (chunk separado 62K JS + 4.7K CSS, solo se carga si hay `.udp-section-cards--swiper` en la página).
- Swiper.js 12.1.3 como dependencia npm.
- Card maneja link interno (icono arrow→) y externo (icono arrow-up-right + target_blank automático).
- Verificación end-to-end: page de prueba con template asignado renderizó HTTP 200 y emitió `udp-section-hero`, `udp-section-hero__inner`, `udp-section-hero__title`. Sección de cards correctamente omitida con repeater vacío (early return).

**Pendientes**:
- Páginas iniciales que el usuario puede asignar el template: Pregrado, Conoce UDP, Gobernanza y Reglamentos, Premios y distinciones, Servicios, Webmail UDP. El admin asigna manualmente desde el editor.
- F4 en adelante: archivos/singles de CPT.
