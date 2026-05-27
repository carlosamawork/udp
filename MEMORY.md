# MEMORY.md — Bitácora del proyecto

> Registro de sesiones del agente. No borrar entradas anteriores.

---

### 2026-03-18 — Inicio del archivo _(Cacho)_

- Se creó este archivo como parte de la configuración inicial del proyecto.
- Se actualizó `CLAUDE.md` con dos bloques nuevos:
  - **MEMORY.md — Bitácora de sesión**: instrucciones para leer/escribir este archivo al abrir y cerrar sesión.
  - **Reglas de trabajo**: obligación de buscar en internet antes de implementar cualquier cosa externa, y no proceder si hay dudas de compatibilidad.
- Versiones en uso confirmadas en CLAUDE.md: Vite 6, Bootstrap 5, ACF Pro, WordPress última estable.

### 2026-03-18 — Cierre de sesión _(Cacho)_

- Estado: proyecto en configuración inicial, sin cambios en código fuente.
- Próximos pasos sugeridos: iniciar desarrollo de features o ajustar la configuración de Vite/SCSS según necesidades del proyecto.

### 2026-04-27 — F0 Foundation completada _(Cacho)_

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

### 2026-04-27 — F1 udp-core + ACF refactor completada _(Cacho)_

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

### 2026-04-27 — F2 Sistema de diseño + chrome completada _(Cacho)_

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

### 2026-04-27 — Footer pixel-perfect F2 (Figma 4401:23290) _(Cacho)_

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

### 2026-04-27 — fix(acf): contact_blocks.enlace cambiado de url a text _(Cacho)_

- Sub-field `enlace` del repeater `contact_blocks` (group_options_footer, ID 55199) cambiado de `type: "url"` a `type: "text"` con placeholder `tel:+56... | mailto:... | https://...`.
- Razón: ACF `type:url` valida solo http/https y rechaza esquemas `tel:` y `mailto:` con "El valor debe ser una URL válida". El template `contact.php` ya escapa con `esc_url()` que sí acepta tel/mailto/http(s).
- Re-sync vía script `/tmp/acf-resync-footer-upsert.php` con upsert por ID (acf_get_field_group + inyección de ID) — UPDATE confirmado, sin duplicados (count=1).
- Verificación SQL: `post_content` ahora contiene `s:4:"type";s:4:"text"`.
- HTTP 200, JSON válido. Commit f39fa0c.

### 2026-04-27 — F3 Task 5: Swiper.js install + módulo JS + init _(Cacho)_

- Instalado `swiper@12.1.3` como `dependency` (runtime, no devDep).
- Creado `src/js/modules/section-landing-swiper.js`: usa `qsa('.udp-section-cards--swiper')` y hace **lazy import** de Swiper + `Navigation/Keyboard/FreeMode` + CSS solo si hay containers en la página. Config: `slidesPerView:'auto'`, `spaceBetween: 33` (16 en mobile), `freeMode + momentum`, `keyboard`, `grabCursor`.
- Wire en `src/js/main.js`: añadido `import { initSectionLandingSwiper } from '@modules/section-landing-swiper'` y llamada en `domReady()` antes del `console.log`.
- Build OK (`npm run build`, 652ms): Vite generó chunk separado `dist/js/chunks/swiper.0ByW75It.js` (62K) y `dist/css/swiper.vL0Q9Dr-.css` (4.69 kB) gracias al `await import()` dinámico. `main.js` quedó en 84K (sin crecer significativamente respecto al baseline ~83K — Swiper no entra en el bundle principal).
- No commit (Task 6 los agrupa).

### 2026-04-28 — F3 Section Landing Template completada _(Cacho)_

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

### 2026-04-28 — Fix: Vite base path para subdir install (MAMP /udp/cms/) _(Cacho)_

**Síntoma**: Swiper no inicializaba. Consola mostraba 404 en chunks dinámicos (`swiper.xxx.js`, `utils.xxx.js`, `index.xxx.js`) y todas las fonts woff2. `main.js` sí cargaba.

**Raíz**: `vite.config.js` tenía `base: '/wp-content/themes/starter-theme/dist/'` hardcodeado. Pero MAMP local sirve WP en `http://localhost:8888/udp/cms/`, así que Vite reescribía URLs sin el prefijo `/udp/cms/` en build-time. `main.js` cargaba porque PHP lo encola con `STARTER_BS5_URI` (URL completa de WP), pero dynamic imports y `url()` en CSS quedaban con el path equivocado.

**Fix**: `vite.config.js` ahora usa `loadEnv()` y lee `VITE_BASE_PATH` desde `.env.local`, con fallback al default de raíz para producción. Creado `.env.local` (ya gitignored) con `VITE_BASE_PATH=/udp/cms/wp-content/themes/starter-theme/dist/`.

**Side note**: `dist/` quedó owned by `root:staff` por algún `sudo npm` previo — `npm run build` falló con EACCES hasta resolver con `sudo chown -R 501:20 dist`. Mismo patrón que el bug de `~/.npm` documentado en F0.

**Verificación**: Curl 200 en chunks + fonts; `grep` confirma path `/udp/cms/...` baked en `main.DY8Fqxyc.js` y `main.BurZMkSV.css`.

### 2026-04-28 — F3 Section Landing — Refactor v2 (sin imagen + breadcrumb + back-card) _(Cacho)_

> Esta entrada **reemplaza conceptualmente** la entrada anterior "F3 Section Landing Template completada" (línea 138). Aquella describe la v1 y se mantiene como historial de la iteración inicial; la arquitectura efectiva en código es la v2 descrita aquí.

**Cambios respecto a v1**:

- ACF: `hero` group → `page_header` group. Eliminados `hero.imagen_fondo` y `cards.imagen` (las cards son siempre gris→lila sin imagen, el header no usa bg image en este template). `hero.bajada` (textarea) → `page_header.descripcion` (wysiwyg).
- Nuevo template-part reutilizable `template-parts/sections/breadcrumb.php` que recorre `wp_get_post_parent_id` desde la página actual hasta la raíz, prepende "Inicio" y marca el último ítem como `<span aria-current="page">`. Acepta args opcionales `page_id` y `home_label`.
- `section-landing-hero.php` → `section-landing-header.php` (incluye breadcrumb + título + descripción wysiwyg + separador `$gray-medium`).
- Container `section-landing-cards.php` ahora **prepende automáticamente** una card sintética con `variant=back` cuando `wp_get_post_parent_id() > 0`. El editor NO la edita.
- Card single soporta dos variantes: `default` (icono arrow-up-right, eyebrow + título + descripción) y `back` (icono undo-2, solo título "Volver a {padre}").
- SCSS: header sin `max-width: 1440` (usa anchos heredados de la layout). Swiper sin padding/gap CSS — el padding inicial/final lo gestiona Swiper.js vía `slidesOffsetBefore/slidesOffsetAfter` (40px desktop, 16px mobile) para que `freeMode` calcule bien el extremo derecho.
- Hover de card: bg `$dark-2` → `$brand-blue` (#4539F2). El `transform: translateY(-2px)` ahora respeta `prefers-reduced-motion`.
- Breadcrumb current con `font-weight: 600` (diferenciación visual vs links).

**Verificación E2E**:

- Página TOP-LEVEL (`/udp/test-section-landing-top/`): HTTP 200, `udp-section-header__title`, breadcrumb `Inicio › Test Section Landing Top` (current), sin back-card. Container de cards omitido por early return (repeater vacío). ✓
- Página HIJA (`/udp/test-section-landing-top/test-section-landing-child/`): HTTP 200, breadcrumb 3 niveles `Inicio › Test ... Top › Test ... Child` (current), `udp-section-card--back` con título "Volver a Test Section Landing Top". ✓
- Páginas de prueba creadas vía SQL e immediately removed tras la verificación (no quedan residuos en `wp_fnku4yposts` ni `wp_fnku4ypostmeta`).

**Pendientes**:

- El usuario debe asignar el template manualmente desde el dropdown del editor a las páginas iniciales: Pregrado, Conoce UDP, Gobernanza y Reglamentos, Premios y distinciones, Servicios, Webmail UDP.
- F4 en adelante: archivos/singles de CPT.

### 2026-04-28 — F4a `block_card_grid` + helpers _(Cacho)_

**Hechos**:

- ACF group `group_template_flexible_content` (location `page_template == page-flexible.php`) con campo `content_blocks` flex y único layout `block_card_grid`. Sub-fields: `titulo`, `eyebrow`, `fuente` (manual|post|concurso), `cards_manuales` (repeater con eyebrow + eyebrow_color + titulo + imagen + fecha + link), `filtros` (group con taxonomias + n_items + orden, condicional a fuente IN [post, concurso]), `columnas` (3col|4col|list), `theme` (dark|light).
- Helper plano `inc/udp-cards.php` con 4 funciones: `udp_query_cards($args)` (entry point, devuelve `{cards, total, max_pages, paged}`), `udp_card_data_from_post($post)` (mapea WP_Post → shape Card o null si no hay featured image), `udp_card_eyebrow_from_post($post)` (text + color desde primer término `category`, color hardcoded 'yellow' por ahora), `udp_card_format_date($iso)` ('YYYY-MM-DD' → 'DD / MM / YYYY' con `date_i18n`).
- Container `template-parts/blocks/block-block_card_grid.php` y card primitive `template-parts/blocks/parts/card-noticia.php`. La card es `<a>` envolvente, line-clamp 2 en título, image con scale 1.03 en hover (respeta `prefers-reduced-motion`), "Leer más" subraya con thickness 2px.
- SCSS único `_card-grid.scss` con modifiers BEM (`--3col`, `--4col`, `--list`, `--dark`, `--light`, `--yellow`, `--red`, `--blue`). Mobile (`<md`) cae a 1 col en grid; en `<xl` baja a 2 cols (3col y 4col). `$brand-yellow` añadido a `_variables.scss` con valor `#FCD303` (UDP gold-medium del Figma `main/AmarilloUDP/gold-medium`).
- Verificación E2E pasada con seed via `update_post_meta()` directo: bloque manual con 4 cards rendereó las 4 con sus colores de eyebrow, cambios runtime de columnas (3col → 4col → list) y theme (dark → light) reflejados sin tocar PHP, fuente=post devolvió 6 cards desde DB real.

**Decisiones clave**:

- Performance: `udp_card_data_from_post()` usa `wp_get_attachment_image_url` + `get_post_meta` en lugar de `wp_prepare_attachment_for_js` (este último corre ~15 filtros del media modal, innecesarios para una card). `udp_query_cards()` añade arg `need_pagination` (default false) que controla `no_found_rows` en WP_Query — block path evita SQL_CALC_FOUND_ROWS, archive path lo activa para poblar `total`/`max_pages`.
- Eyebrow color para `fuente=post` queda hardcoded en `yellow`. Implementar color por término requiere ACF de color picker en cada término — diferido para iteración futura.
- Fuente `concurso` ignora el filtro `taxonomias` (concurso no usa `category`). Documentado en `udp_query_cards()`.
- Posts sin featured image se omiten silenciosamente. El editor lo sabe (la imagen es featured de WordPress).
- Helper devuelve `total` y `max_pages` para que F4b/F4c puedan paginar archives sin reescribir la query.

**Cosas que descubrí**:

- ACF JSON local + `acf_import_field_group()` puede generar duplicados de DB row si ACF auto-importa el JSON antes que corra el script (auto-sync hook + manual sync). Solución: borrar el row más viejo a mano. Pattern UPSERT del script falla porque `acf_get_field_group()` con local JSON devuelve `ID: 0`.
- **CRÍTICO (naming)**: `page-flexible.php` llama `get_template_part('template-parts/blocks/block', $layout)` donde `$layout = 'block_card_grid'`. WordPress busca `block-block_card_grid.php` (slug=`block`, name=`block_card_grid`). El archivo se nombró originalmente `block-card_grid.php` (sin el prefijo `block_`), lo que hacía que la sección fuera silenciosamente vacía (have*rows devolvía datos correctamente en WP-CLI pero el template part no se cargaba vía HTTP). Renombrado a `block-block_card_grid.php`. Regla: el layout name en ACF debe matchear exactamente la porción `{name}` del call, o bien el layout name debe ser sin prefijo `block*`si el slug del template_part es`block`.
- Seeding de flex content vía `update_field()` con array anidado solo guarda el layout key, no los sub-fields. Para seed correcto: usar `update_post_meta()` directamente con la estructura de claves `content_blocks_0_titulo`, `content_blocks_0_cards_manuales`, `content_blocks_0_cards_manuales_0_eyebrow`, etc. + los correspondientes `_` reference keys.
- `grep -cE "udp-card-noticia "` (con espacio al final) falla cuando el HTML tiene la class en su propia línea indentada. Usar `grep -cE "udp-card-noticia"` (sin espacio).

**Pendientes**:

- F4b: archive-post + single-post (Noticias). Reusará `udp_query_cards()` con `paged` + `need_pagination=true` y `udp_card_data_from_post()` para markup directo (sin pasar por el bloque).
- F4c: archive-agenda (toggle grid/list) + single-agenda + nuevo card primitive `card-evento.php` (image-izquierda + CTA circular).
- Cleanup de scaffold legacy (`block-cards_grid.php`, `_flexible-blocks.scss`) cuando todos los bloques UDP estén migrados.
- El group PHP `group_flexible_blocks` en `inc/acf-setup.php` tiene mismo field name `content_blocks` que el nuevo JSON group — coexisten sin conflicto (ambos aplican al mismo template) pero el PHP group está vacío (no tiene `block_card_grid` layout). Limpiar en F11.

### 2026-04-28 — F4b Task 2: Card `--horizontal` modifier _(Cacho)_

**Hechos**:

- `card-noticia.php`: docblock actualizado (`@var array $args` documenta nuevo arg `variant`).
- `card-noticia.php`: variable `$variant` sanitizada (whitelist `['horizontal']`), clase BEM `udp-card-noticia--horizontal` condicionalmente añadida.
- `_card-grid.scss`: bloque `.udp-card-noticia--horizontal` añadido al final del archivo. Diseño: `flex-direction: row`, gap 30px (Figma spec), imagen fija 201×275px. Mobile (`<md`): cae a `flex-direction: column`, imagen 16/9 full-width.
- PHP lint: sin errores. Build: OK en 1.22s.

**Decisión**:

- El modifier es selector de nivel raíz (`.udp-card-noticia--horizontal { ... }`) en lugar de anidado dentro de `.udp-card-noticia { ... }` para evitar especificidad conflictiva con overrides de layout (`.udp-card-grid--list &` y similares).

**Pendiente**:

- Task 3 de F4b: archivo `archive-post.php` que pase `variant=>'horizontal'` al partial.

### 2026-04-28 — F4b1 Noticias archive (simple) + single-post _(Cacho)_

**Hechos**:

- `templates/page-noticias.php` asignado manualmente a la página "Noticias" (ID 97). Archive con filtros (categoría + año + búsqueda via $\_GET) + grid 2-col × 3 filas (6 cards/page) + paginación. Light theme.
- `inc/udp-cards.php` extendido con `udp_query_noticias($filters)` (wrapper WP_Query con date_query + s) y `udp_get_post_years()` (transient 1 día). El `udp_query_cards()` de F4a queda intocado.
- Card primitive `card-noticia.php` extendido con arg `variant='horizontal'` → SCSS modifier `--horizontal` (image-left 201×275, line-clamp 3). Reutilizable por F4c.
- Partials reutilizables en `template-parts/archive/`: `noticias-filters.php` (form GET con auto-submit JS) y `pagination.php` (wrapper paginate_links con BEM UDP). El de paginación lo usará F4c también.
- `single-post.php` reemplaza el scaffold genérico para post_type=post (single.php queda como fallback). Hero light + content + share floating + related.
- Partials del single en `template-parts/single/`: `post-hero.php`, `post-share.php` (5 botones — copy URL con clipboard fallback + Facebook + X + WhatsApp + LinkedIn), `post-related.php` (3 cards por categoría primaria con fallback a más recientes si <3 matches).
- SCSS templates `_noticias-archive.scss` (header light + filtros 3-col + grid + paginación) y `_noticias-single.scss` (hero + content max-width 480px + share sticky + related grid 3-col).
- Verificación E2E: `/noticias/` HTTP 200 con 6 cards, filtros funcionando con cat/year/udp_s, paginación correcta, single con todos los markup classes esperados.

**Decisiones clave**:

- `templates/page-noticias.php` (page template) en vez de `home.php` o `archive-post.php` porque WP routea `/noticias/` como page por defecto y porque `home.php` colisionaría con F9 (Home) cuando se setee `show_on_front=page`.
- `udp_query_cards()` de F4a queda intocado — soporta solo el shape del bloque flex. `udp_query_noticias()` es el wrapper específico para archive (con year + search). Patrón reutilizable: F4c tendrá `udp_query_agenda()` similar.
- Eyebrow en single y archive viene del primer término `category` del post (color hardcoded yellow). Igual que F4a — pendiente de implementar color por término.
- Share buttons usan `<button>` para copy URL (con clipboard API + fallback a `window.prompt`) y `<a target=_blank>` para los demás.
- Pagination preserva todos los filtros del URL (cat + year + udp_s) vía `add_args` de paginate_links.
- **CRÍTICO**: El param de búsqueda en el form usa `name="udp_s"` (NO `name="s"`). WP intercepta `?s=` como búsqueda global antes de que llegue al page template — redirige a la página de resultados de búsqueda nativa (404 si no hay resultados). El template lee `$_GET['udp_s']` y lo pasa a `WP_Query` como `'s'` internamente.

**Cosas que descubrí**:

- `paginate_links` con `type='array'` devuelve cada link como HTML string — para aplicar BEM modifiers UDP (`--current`, `--prev`, `--next`, `--dots`) detectamos las clases nativas WP (`current`, `prev`, `next`, `dots`) en cada string y mapeamos.
- `get_query_var('paged')` solo se popula automáticamente para queries de archive nativas. En page templates con WP_Query custom hay que leer `$_GET['paged']` también como fallback.
- WP intercepta el query param `?s=` globalmente en el request lifecycle (antes de template routing). Si un form GET en un page template usa `name="s"`, el request se procesa como búsqueda global y NO llega al template. Workaround: usar nombre custom (`udp_s`) y pasarlo internamente a WP_Query.

**Pendientes**:

- F4b2 (parcial): hero band del archive con featured card + 2 side compactas → Tasks 4+5 completados (ver entrada 2026-04-28 F4b2 Tasks 4+5). Quedan: bump posts_per_page 6→9, wiring PHP del hero en page-noticias.php, campo ACF featured_post.
- F4c: archive Agenda (toggle grid/list) + single-evento + nuevo card primitive `card-evento.php`. Reutilizará `udp_query_noticias` pattern → `udp_query_agenda()`, y los partials `archive/pagination.php`.
- Image gallery del single (Figma muestra 3 imágenes con arrows después del body) → F4b2 cuando se añada el campo ACF gallery.

### 2026-04-28 — F4b2 Tasks 4+5: Hero partial + SCSS featured + dark theme _(Cacho)_

**Hechos**:

- Task 4: Creado `template-parts/archive/noticias-hero.php` — partial de la hero band. Acepta `$args['featured']` (card array|null) + `$args['side']` (array de cards). Delega a `card-noticia.php` con `variant=featured` y `variant=horizontal`.
- Task 5.1: Appended `.udp-card-noticia--featured` modifier a `src/scss/blocks/_card-grid.scss` — image fullbleed absolutamente posicionada + overlay gradient + título centrado + zoom hover.
- Task 5.2: Reemplazado íntegro `src/scss/templates/_noticias-archive.scss` — dark theme ($dark-1 bg, $white text), hero band layout (.udp-noticias-hero), filtros dark (borders rgba $white 0.2, chevron SVG blanco), paginación invertida (current = $white bg + $dark-1 text).
- Build: ✓ 663ms, sin errores. PHP lint: no syntax errors.

**Decisiones**:

- `_noticias-archive.scss` se reemplazó por completo (no append) — la especificación exigía reemplazar light theme por dark. Mantener ambos habría generado conflictos de especificidad.
- `--featured` va como selector raíz (`.udp-card-noticia--featured`) no anidado dentro de `.udp-card-noticia` — es un modifier BEM que sobreescribe el bloque base por completo (layout absolutamente diferente). Igual que `--horizontal`.

**Pendientes F4b2**:

- Wiring PHP: integrar noticias-hero.php en page-noticias.php (get_template_part con featured/side de la query o ACF).
- Campo ACF `featured_post` (post_object) en options page UDP para curaduría editorial.
- Bump `posts_per_page` de 6 → 9 para compensar los 3 posts consumidos por el hero.

### 2026-04-28 — F4b2 Task 6: page-noticias.php logic update (page 1 vs 2+, theme=dark) _(Cacho)_

**Hechos**:

- `templates/page-noticias.php` reemplazado íntegramente con la lógica de hero/page-1.
- `$show_hero` flag: true solo cuando `$paged === 1` && sin filtros (cat/year/s vacíos).
- Página 1 sin filtros: featured (ACF `featured_post` o fallback a más reciente con thumbnail) + 2 side cards + 6 grid (9 totales en pantalla).
- Página 1 con filtros o página 2+: 9 cards en grid, sin hero.
- Hero se renderiza con `get_template_part('template-parts/archive/noticias-hero', ...)` cuando `$show_hero && ($featured_card || !empty($side_cards))`.
- Empty state condicional: solo muestra "No se encontraron noticias" cuando `!$featured_card && empty($side_cards)` — no se muestra en página 1 normal que tiene hero pero grid vacío.
- theme cambiado de `'light'` → `'dark'` en el `get_template_part` de `card-noticia`.
- PHP lint: `No syntax errors detected`. No hay commit (instrucción del plan).

**Decisiones**:

- `$featured_id` se resuelve primero por ACF (`get_field('featured_post', $page_id)`) y cae a fallback `get_posts` con `meta_query EXISTS _thumbnail_id`. Esto evita depender de que el campo ACF esté configurado en el CMS para que la página funcione en entornos de dev.
- Side query y grid query usan arrays `$exclude_for_side` / `$exclude_for_grid` para ir acumulando exclusiones — evita que el mismo post aparezca en hero + side + grid.
- `udp_query_noticias` recibe `'exclude'` en el hero path y `cat/year/s/paged` en el filtros path — la función ya existía con estos args desde F4b1.
- `$max_pages` sale del `$grid_result` en ambos paths — el hero path usa el grid de 6 posts (pág 1 solo), el filtros path usa la query de 9 posts con paginación.

**Pendiente**:

- Campo ACF `featured_post` (post_object) en el grupo de la página "Noticias" (ID 97) — mientras no exista, el fallback automático funciona bien.

---

### 2026-04-28 — F4b2 Noticias hero band + tema dark fix _(Cacho)_

**Hechos**:

- Archive Noticias corregido a tema **dark** (F4b1 lo había deployado light por error de spec). `templates/page-noticias.php` pasa `theme=dark` al card-noticia. SCSS `_noticias-archive.scss` invertido (bg `$dark-1`, color `$white`, breadcrumb override, filtros input/select con border `rgba($white,0.2)` y placeholder `rgba($white,0.5)`, paginación con current bg `$white` color `$dark-1`).
- Hero band en página 1 sin filtros: 1 featured grande (variant=`featured`) + 2 side compactas (variant=`horizontal`) en grid 2-col (`udp-noticias-hero__inner`). Mobile cae a 1-col.
- Featured card: image fullbleed + overlay gradient `rgba($dark-1, 0.25→0.65)` + eyebrow yellow top-left + date top-right + título centrado Arizona Flare 40px (28px mobile). Aspect-ratio 432/580. Hover image scale 1.04. Sin "Leer más" — toda la card es clickable.
- ACF group nuevo `group_template_noticias` con field único `featured_post` (post_object, nullable). Location `page_template == page-noticias.php`. Si vacío, fallback al post más reciente con featured image.
- Helper `udp_card_data_from_post` ahora incluye `post_id` en el shape Card (necesario para excluir IDs en queries siguientes).
- Helper `udp_query_noticias` acepta nuevo arg `exclude` (array de post IDs) → mapea a `post__not_in`.
- `posts_per_page` lógica: página 1 sin filtros = 9 (1 featured + 2 side + 6 grid); página 1 con filtros = 9 grid; página 2+ = 9 grid.

**Decisiones clave**:

- El featured se SUPRIME cuando hay filtros activos (cat/year/udp_s) o en página 2+. Razón: el ACF `featured_post` puede ser de otra categoría; mostrarlo confundiría.
- Card `--featured` es un markup ALTERNATIVO en card-noticia.php (rama `if/else` por variant). No comparte el `<a>...<body>` con default/horizontal — necesita estructura distinta (image fullbleed + overlays absolutos + título centrado).
- Helper `udp_query_noticias()` se llama hasta 3 veces en page 1 (featured fallback + side + grid). Cada query con su propio `exclude` correctamente acumulado.
- Pattern UPSERT del ACF JSON ahora consulta DB directamente por `post_name` (en lugar de `acf_get_field_group()` que devuelve ID:0 con local JSON). Aplicado a `group_template_noticias` para evitar duplicados.

**Pendientes**:

- F4c: Agenda (toggle grid/list, single-evento, card-evento.php). Reusará paginación + exclude pattern.
- F4 extras: image gallery del single (campo ACF gallery + carousel JS).

---

### 2026-04-28 — F4c Task 2: ICS calendar endpoint _(Cacho)_

**Hechos**:

- Creado `inc/udp-ics.php` con endpoint `?udp_ics={post_id}` que emite un VCALENDAR/VEVENT descargable para el CPT `agenda`.
- Dos helpers: `udp_ics_parse_date_raw($raw)` (Ymd → Unix timestamp, fallback strtotime) y `udp_ics_parse_time_raw($raw)` (H:i:s/H:i/g:i a/g:i A → segundos desde medianoche).
- Ambos helpers usan `get_post_meta()` directo (NO `get_field()`), mismo patrón que el bugfix de `udp_card_data_from_agenda()`.
- Si no hay hora_termino o es 0, end_ts = start_ts + 3600 (una hora por defecto). Si end_ts <= start_ts, mismo fallback.
- Headers: `Content-Type: text/calendar; charset=utf-8` + `Content-Disposition: attachment; filename="evento-{slug}.ics"` + `nocache_headers()`.
- Wire en `functions.php` después de `require udp-cards.php`.

**Verificación**:

- `php -l` sin errores en ambos archivos.
- Smoke test con EVENT_ID=54943: HTTP 200, `DTSTART:20260409T080454Z` (real timestamp, no epoch), `SUMMARY:Inauguración de año académico...`.

**Decisiones**:

- No commit (instrucción del plan).

---

### 2026-04-28 — Bugfix: udp_card_data_from_agenda fecha_display vacío _(Cacho)_

**Hechos**:

- `fecha_display` llegaba siempre vacío porque `get_field('fecha', $post->ID)` devuelve el valor de "Return Format" configurado en ACF (ej. `'3 Octubre 2017'`), no el valor raw de storage.
- `strtotime('3 Octubre 2017')` falla en PHP 8.4, y aunque funcionara, ese string tampoco es `Ymd`.
- Fix aplicado en `inc/udp-cards.php`, función `udp_card_data_from_agenda()`:
  - Se reemplaza `get_field('fecha')` por `get_post_meta($post->ID, 'fecha', true)` para obtener el valor raw `Ymd` (`20171003`).
  - Se usa `DateTime::createFromFormat('Ymd', ...)` como método primario de parseo (más robusto que `strtotime` para este formato).
  - `strtotime` queda como fallback por si algún valor está en `Y-m-d`.
- Diagnóstico adicional: muchos posts de agenda tienen `fecha` vacío en DB (datos legacy sin ese campo completado). El smoke test veía el primer post con imagen y fecha vacía — eso es un problema de datos, no de código.
- Verificado con post ID 8731 (raw `20171003`): `fecha_iso = 2017-10-03`, `fecha_disp = 3 de Octubre de 2017`.
- `php -l`: sin errores de sintaxis.

---

### 2026-04-28 — F4c Agenda archive (grid + list) + single-evento _(Cacho)_

**Hechos**:

- `templates/page-eventos.php` asignado a página "Agenda" (ID 91, slug `agenda-udp` → URL `/agenda-udp/`). Theme dark. 2 vistas: grid (6/page con cards image-left + CTA) y list (12/page con rows table-like). View toggle preserva filtros vía `add_query_arg`.
- `single-agenda.php` enrutado para CPT `agenda`. Light theme. Layout 2-col sidebar (event-meta) + content. Reusa `post-share.php` partial de F4b1.
- Helpers nuevos en `inc/udp-cards.php`:
  - `udp_query_agenda($filters)` — WP_Query sobre post_type=agenda con `meta_key=fecha`, `orderby=meta_value`, `order=ASC`. Filtros: facultad (tax_query), year (meta_query LIKE 'YYYY%'), s, exclude.
  - `udp_card_data_from_agenda($post)` — añade `fecha_display` ("3 de Octubre de 2017"), `hora_display` ("12:00 hrs") y `lugar` al shape Card. Eyebrow desde primer post_tag (no category).
- Card primitive `card-evento.php` con 2 modes: grid (image-left 228×275 + body con title/eyebrow/datetime/lugar + CTA circular bottom-right) y list (grid 3-col 140/1fr/200, eyebrow + title + date, separador 1px abajo).
- Filtros: facultad (taxonomía `facultad`, 84% coverage en eventos) + año + búsqueda (`udp_s`). Hidden input `view` preserva la vista al filtrar.
- ICS endpoint `/?udp_ics={post_id}` registrado en `init` hook (`inc/udp-ics.php`). Genera VCALENDAR/VEVENT con DTSTART, DTEND, SUMMARY, LOCATION, URL, DESCRIPTION. Headers `text/calendar; charset=utf-8` + `Content-Disposition: attachment`.
- Sidebar event-meta: eyebrow + Día + Hora (rango si hay hora_termino) + Dirección + Entrada (hardcoded) + Unidad Académica (primer término facultad) + 2 CTAs (outline ICS + primary inscripciones URL).
- "Te podría interesar" en single: 3 eventos próximos por facultad primaria (orderby meta fecha ASC), fallback a más próximos globales si <3.
- 3 SCSS nuevos: `_card-evento.scss`, `_eventos-archive.scss`, `_eventos-single.scss`. CSS sube ~17 kB (de 282 → 300 kB).

**Decisiones clave**:

- **Date parsing fix descubierto en Task 1**: `get_field('fecha')` devuelve el ACF Return Format (string locale español "3 Octubre 2017"), NO el raw `Ymd` storage. Switch a `get_post_meta($post_id, 'fecha', true)` + `DateTime::createFromFormat('Ymd', $val)`. Mismo pattern aplicado en `udp-ics.php` y `event-meta.php`. Documentar para futuros agentes — `get_field()` para fechas ACF NO sirve para parsing programático.
- Eyebrow source = primer `post_tag`. La única taxonomía con valores de tipo evento ("Charla", "Cine"). `tipo-contenido` (18 terms) tiene cobertura insuficiente.
- Order por meta `fecha` ASC. Eventos sin fecha caen al inicio por ordenación de meta vacío.
- `posts_per_page` en list = 12 vs 6 en grid. List cards más compactas, caben más por scroll.
- ICS endpoint inline en `init` hook en lugar de REST endpoint. Razón: zero overhead, no requiere registración de routes.
- "Entrada" hardcoded "Entrada liberada para todo público" — TODO añadir campo ACF `entrada_info` si el cliente quiere control editorial.

**Cosas que descubrí**:

- ACF `get_field()` para campos `date_picker` aplica el "Return Format" del field (en este proyecto `'F j, Y'` o similar locale-dependent). Para parsear debes usar `get_post_meta()` directo y obtener el storage format crudo (`Ymd`).
- `WP_Query` con `meta_key + orderby=meta_value` funciona con dates en `Ymd` porque ordena lexicográficamente y `Ymd` es lex-equivalente a cronológico.
- Solo 45 de 3626 eventos tienen featured image (`_thumbnail_id`). Tanto `udp_card_data_from_agenda` como el related partial retornan null sin imagen. Esto produce 1 card visible en el archive (datos insuficientes en BD local, no un bug de código). En producción con más imágenes se verán los 6/12 esperados.

**E2E verification (2026-04-28)**:

- Grid archive: HTTP 200, clases `udp-eventos-archive--grid`, `udp-eventos-archive__toggle`, `udp-card-evento--grid` presentes. Cards visibles: 1 (limitado por cobertura de thumbnail = 45/3626).
- List archive: HTTP 200, clases `udp-eventos-archive--list`, `udp-card-evento--list` presentes. Cards visibles: 1 (mismo motivo).
- Single (`conversacion-unidad-minima-practicas-editoriales-y-artisticas`): HTTP 200. Clases `udp-single-event*` y `udp-event-meta*` completas. "Volver a Eventos" y "Agregar al calendario" presentes con `udp_ics=49996`. "Te podría interesar" no renderiza (0 eventos con thumbnail en facultad del post actual).
- ICS (EVENT_ID=54943): HTTP 200, `BEGIN:VCALENDAR`, `DTSTART:20260409T084557Z` (real, no epoch).
- Filtro facultad (ID=16): option `selected='selected'` correcta. Cards: 1 (misma limitación thumbnail).

**Pendientes**:

- ACF nuevo `entrada_info` (textarea) en grupo agenda para sustituir el hardcoded "Entrada liberada".
- Color por término de taxonomía (eyebrows actualmente hardcoded yellow).
- `/eventos/` slug si el cliente prefiere a `/agenda-udp/`.
- Subir imágenes featured a más eventos en BD para que el archive muestre los 6/12 cards esperados.

### 2026-04-28 — F4 extras: image gallery en single-post _(Cacho)_

**Hechos**:

- Galería del campo ACF existente `galeria_de_imagenes` (group `cpt_post_meta`, ya creado en F1) se renderiza en `single-post.php` entre el body content y el related section.
- Partial nuevo `template-parts/single/post-gallery.php` itera el array de attachments y emite `<ul class="...gallery-list swiper-wrapper">` con `<li class="...swiper-slide">` por imagen.
- Nav buttons (prev/next) custom UDP — círculos 40×40 con borders dark, hover bg dark.
- JS module nuevo `single-post-gallery.js` lazy-importa Swiper + Navigation + Keyboard solo si `[data-udp-post-gallery]` existe. Mismo patrón que `section-landing-swiper.js` (F3) y `card-grid` no aplica aquí (block diferente).
- Posts sin galería: el partial early-returns silenciosamente (no markup).
- Reusa la dependencia Swiper de F3 (ya en package.json) — chunk separado lazy-loaded, no añade peso al main bundle.
- E2E validado: post ID 28413 devuelve 22 slides y las 7 clases BEM esperadas.

**Pendientes**:

- F5+: el cliente sigue con calendario, concursos, facultades, carreras, centros, home, etc.

### 2026-04-28 — Bugfix F4b1/F4c: prefijo udp\_ en params + agenda >= hoy _(Cacho)_

**Bug 1**: `year`, `cat`, `facultad` son WP built-in query vars. Al enviar el form con `?year=2024`, WP interceptaba el request y ruteaba a date archive → 404.

**Fix aplicado**:

- `$_GET['cat']` → `$_GET['udp_cat']` en `templates/page-noticias.php`
- `$_GET['year']` → `$_GET['udp_year']` en `templates/page-noticias.php` y `templates/page-eventos.php`
- `$_GET['facultad']` → `$_GET['udp_facultad']` en `templates/page-eventos.php`
- `name="cat"` → `name="udp_cat"`, `name="year"` → `name="udp_year"` en `template-parts/archive/noticias-filters.php`
- `name="facultad"` → `name="udp_facultad"`, `name="year"` → `name="udp_year"` en `template-parts/archive/eventos-filters.php`
- `add_args` en `template-parts/archive/pagination.php`: claves `cat/year/s` → `udp_cat/udp_year/udp_s` + añadido `udp_facultad`
- `$base_args` en `page-eventos.php` (para view toggle URLs): `facultad/year` → `udp_facultad/udp_year`
- Los args internos de `udp_query_noticias()` y `udp_query_agenda()` se mantienen iguales — el mapeo $GET→función ya estaba correcto.

**Bug 2**: `udp_query_agenda` usaba `orderby=meta_value, order=ASC` globalmente. Eventos de 2021 aparecían al inicio.

**Fix**: En `inc/udp-cards.php`, bloque `else` añadido tras el condicional `if ($year > 0)`:

```php
$today_ymd = date('Ymd');
$args['meta_query'] = [['key'=>'fecha','value'=>$today_ymd,'compare'=>'>=','type'=>'CHAR']];
```

Con año específico el filtro LIKE reemplaza al >=hoy (el usuario quiere ver todo ese año). Sin filtro de año, solo se muestran eventos próximos.

**Verificación**:

- `php -l`: 6/6 sin errores de sintaxis.
- `?udp_year=2024` → HTTP 200 (antes 404).
- `?udp_year=2026` en noticias → HTTP 200.
- `?year=2024` (sin prefijo) → HTTP 404 esperado (WP date archive, correcto — el form ya no emite este param).
- DB confirma: la única fecha >= 20260428 es el evento del 20260713 (sin thumbnail). El filtro >=hoy funciona.
- Commit: `89834d7`

**Regla descubierta**: Los query params de forms GET propios deben llevar prefijo para evitar colisión con WP built-in vars: `year`, `cat`, `tag`, `author`, `s`, `p`, `name`, `feed`, `tb`, `paged`, `comments_popup`, `preview`, `page`, `calendar`, `m`, `w`, `day`, `monthnum`, `order`, `orderby`, `meta_key`, `meta_value`, `meta_compare`, `meta_query`, `posts`, etc.

### 2026-04-28 — Fix: featured image opcional en card-evento grid + placeholder _(Cacho)_

**Hechos**:

- `udp_card_data_from_agenda()` ya NO devuelve `null` cuando falta featured image. Ahora devuelve siempre el array Card con `imagen.url = ''`.
- `card-evento.php` (grid mode): eliminado early-return para `empty imagen.url`. La figura recibe class `--placeholder` cuando no hay imagen; el `<img>` se emite condicionalmente.
- SCSS `_card-evento.scss`: modifier `&--placeholder` añadido dentro de `.udp-card-evento__media` con hatching diagonal sutil (`repeating-linear-gradient 45deg`).
- `udp_card_data_from_post()` (Noticias) NO cambia — featured image sigue siendo requerida allí.

**Verificación**:

- `php -l`: 2/2 sin errores de sintaxis.
- Build: ✓ en 660ms.
- E2E: `/agenda-udp/` muestra 1 card con `udp-card-evento__media--placeholder` (el único evento próximo en BD local no tiene featured image).
- Commit: `4f678f1`

**Decisiones**:

- Solo `udp_card_data_from_agenda` liberada del requirement de featured image — Agenda es CPT donde las imágenes no siempre están disponibles. Noticias mantiene el estándar editorial (imagen requerida).
- El placeholder visual usa hatching diagonal vs gris sólido para distinguir visualmente "sin imagen intencional" de un posible error de carga.

### 2026-04-29 — F5a Calendario Académico archive _(Cacho)_

**Hechos**:

- `templates/page-calendario.php` asignado a página "Calendario Académico" (ID 74). Theme dark, layout 2-col: sidebar sticky con dropdown año + lista de meses anchor + main con intro + secciones por mes.
- Helpers nuevos en `inc/udp-cards.php`: `udp_query_calendario` (no pagina, devuelve entries_by_month), `udp_calendario_data_from_post` (siempre devuelve array, no requiere featured image), `udp_get_calendario_years` (transient 1 día). Detectó 3452 entries en 2026 distribuidas en 12 meses.
- ICS endpoint extendido en `inc/udp-ics.php` para soportar `calendario` post_type además de `agenda`. Para calendario emite all-day events con `DTSTART;VALUE=DATE:YYYYMMDD` y `DTEND` día siguiente.
- 4 partials en `template-parts/archive/`: `calendario-sidebar.php`, `calendario-filters.php`, `calendario-month-section.php`, y `template-parts/blocks/parts/entry-calendario.php`.
- Filtros: `udp_publico` (publico-udp) + `udp_tipo` (tipo-udp) + `udp_s` + `udp_year` (en sidebar). Sidebar preserva los demás filtros via hidden inputs.
- Entry destacado: border-left amarillo + bg sutil `rgba($brand-yellow, 0.05)` + tag yellow "Destacado".
- Una sola página por año — no hay paginación. El usuario cambia el año vía dropdown del sidebar.
- SCSS nuevo `_calendario-archive.scss` con: dark theme, 2-col grid (280/1fr), sidebar sticky 100px top, month sections con título Arizona Flare 32px, entries con grid 140/1fr (date + body).

**Verificación E2E** (2026-04-29):

- HTTP 200 ✓
- 30 clases BEM presentes (archivo, sidebar, month, entry, filters) ✓
- 3452 entries en año 2026 (default) ✓
- 10+ secciones de meses (enero–noviembre) ✓
- ICS all-day: `DTSTART;VALUE=DATE:20251229` y `DTEND;VALUE=DATE:20251230` ✓
- Filtro `udp_publico` (PUB_ID=4052): 1393 entries ✓
- Filtro `udp_year=2025`: 7 entries ✓

**Decisiones clave**:

- No paginación — calendario académico es un documento "completo" del año. Todos los entries cargan en una página (~3500 entries está bien, performance OK con `posts_per_page=-1`).
- Año vía dropdown sidebar en lugar de top filter bar — coincide con el Figma y semánticamente "el año contextualiza toda la página".
- ICS all-day para calendario: `VALUE=DATE` format (YYYYMMDD sin T-time) compatible con Google Calendar / Outlook / Apple. Diferente del ICS de agenda que usa hora_inicio.
- Eyebrow en entries usa primer término de `tipo-udp` (mostrado como text + border, no yellow pill — el yellow está reservado para destacado).

**Pendientes**:

- `block_calendario_grid` flex content: diferido a iteración futura.
- JS active-month tracking en sidebar (IntersectionObserver): defer.

### 2026-04-29 — F5b Concursos académicos archive + single _(Cacho)_

**Hechos**:

- `templates/page-concursos.php` asignado a página "Concursos Académicos" (ID 76). Light theme con hero **purple/blue** (`$brand-blue` bg). 2-col grid de cards horizontales (reusa `card-noticia variant=horizontal theme=light`).
- `single-concurso-academico.php` enrutado para CPT `concurso-academico`. Layout 2-col sidebar (`concurso-meta` partial: fecha + eyebrow facultad) + content (featured image + caption desde `post_excerpt` + body + 2 buttons descarga). Reusa `post-share` partial.
- Helpers nuevos en `inc/udp-cards.php`: `udp_query_concursos` + `udp_card_data_from_concurso` (eyebrow desde primer término facultad, color yellow).
- ACF group `cpt_concurso_meta` extendido con field `archivo_formato_propuestas` (file, opcional). Field existente `archivo_concurso` se mapea al botón "Descargar bases".
- Filtros: facultad + udp_s.
- 2 SCSS nuevos: `_concursos-archive.scss` (light + purple hero) y `_concursos-single.scss` (layout 2-col + buttons pill outline/primary).

**Decisiones clave**:

- Caption single = `post_excerpt`. Si el cliente quiere control específico ("Periodo de postulación: hasta..."), añadir ACF dedicado en iteración futura.
- Buttons primary hover = `$brand-blue` (consistencia con el hero purple).
- 3 entries en DB → no necesita paginación pero el partial paginate_links se incluye igual (early-returns si max_pages <= 1).
- `archivo_concurso` usa `return_format: "url"` (string) — concurso-files.php maneja ambos string y array para compatibilidad.

**Pendientes**:

- F5 cerrado. Próxima fase opcional: F6 (Facultades + Carreras + Centros) o seguir según prioridad del cliente.

### 2026-04-29 — F5c Active-month tracking en Calendario sidebar _(Cacho)_

**Hechos**:

- JS module `src/js/modules/calendario-active-month.js`: IntersectionObserver que marca el link del mes correspondiente con clase `udp-calendario-sidebar__month-link--active` cuando esa sección está visible. Si hay múltiples secciones visibles, la topmost (más arriba en viewport) gana.
- `rootMargin: '-120px 0px -50% 0px'` ancla el "punto activo" justo bajo el sticky header.
- Wire en `src/js/main.js`. SCSS modifier `--active` con color `$brand-yellow` y font-weight 600.

**Decisiones clave**:

- IntersectionObserver vs scroll listener: el observer es más performante (rAF interno) y no requiere debounce.
- Topmost-wins logic: cuando dos meses son visibles a la vez (transition entre Enero y Febrero), el de arriba mantiene el active hasta que el de abajo cruza el rootMargin top.

### 2026-04-29 — F5d block_calendario_grid (flex content) _(Cacho)_

**Hechos**:

- Layout `block_calendario_grid` añadido al campo flex `content_blocks` del group `group_template_flexible_content` (creado en F4a). Sub-fields: titulo, eyebrow, year, mes (select 'Todo el año' o '01'..'12'), filtros group (tipo + publico), n_items (max 30), theme (dark|light).
- Helper nuevo `udp_query_calendario_flat` en `inc/udp-cards.php`: similar a `udp_query_calendario` pero devuelve flat list (no agrupado por mes), soporta filter de mes específico via meta_value LIKE 'YYYYMM', limit max 30.
- Container partial `template-parts/blocks/block-block_calendario_grid.php` (slug-name pattern WP). Reusa partial `entry-calendario.php` (F5a).
- SCSS nuevo `_block-calendario-grid.scss` con container + theme dark/light. Light theme override colors del entry para mantener contraste.
- ACF sync via DB UPSERT (UPDATE existing id=55251). jq verify: ambos layouts presentes en JSON.
- E2E: página de prueba ID 55282 renderizó 5 entries de Marzo 2026 con todas las clases BEM esperadas. Eliminada tras la prueba.

**Decisiones clave**:

- Helper separado `_flat` en lugar de extender `udp_query_calendario` con flag — mantiene cada función con responsabilidad única.
- Light theme override — solo el entry necesita ajustes (no el container del block que ya define bg + color base).

**Pendientes**:

- F5 cerrado (a + b + c + d). F6 en adelante.

### 2026-04-29 — F6a Facultades landing + mosaic primitive _(Cacho)_

**Hechos**:

- `templates/page-facultades.php` asignado a página "Facultades" (ID 14, hija de "Pregrado y Formación General"). Theme dark, mosaico 5-col responsive (5 → 3 en lg → 2 en mobile).
- Helpers nuevos: `udp_query_facultades` (itera términos de tax facultad) + `udp_card_data_from_facultad_term` (mapea término a card mosaic shape, image desde ACF imagen_taxonomia, link via `get_page_by_title($term_name)` con fallback a term archive).
- Card primitive `card-mosaic.php` reutilizable por Carreras (F6b con eyebrow) y Centros (F6c). Soporta theme dark/light + placeholder hatching cuando no hay imagen.
- 2 SCSS nuevos: `_card-mosaic.scss` (primitive con dark/light variants) y `_facultades-archive.scss` (page con grid 5-col responsive).

**Decisiones clave**:

- 14 términos en taxonomía facultad pero solo 2 con `imagen_taxonomia` poblada en ACF — el placeholder hatching cubre los 12 restantes hasta que se carguen las imágenes.
- ACF devuelve la URL como string plano (return format 'url') no como array — se añadió rama `elseif is_string()` al helper para manejar este caso correctamente.
- Match término → página dedicada por exact title (`get_page_by_title`). Funciona para las 9-10 facultades que tienen su landing existente. Resto cae a term archive `/facultad/{slug}/`.
- Página 14 vive bajo `/pregrado-y-formacion-general/facultades/` — WordPress redirige `/facultades/` ahí (301 esperado).
- Mosaic primitive separado del archive container para reuso en F6b/F6c.

**Pendientes**:

- F6b: COMPLETADO (ver entrada siguiente).
- F6c: Archive Centros + single simple.
- F6 extras: `block_facultades_mosaic` flex content (mismo mosaic insertable como widget) — diferido.
- Subir las 12 imágenes de facultad faltantes en admin para que el placeholder se reemplace.

### 2026-04-29 — F6b Carreras archive + single _(Cacho)_

**Hechos**:

- `templates/page-carreras.php` asignado a página "Carreras" (ID 12). Theme dark, mosaico 5-col reusando `card-mosaic` con eyebrow facultad. Filtros legacy: facultad dropdown + udp_s.
- Helpers en `inc/udp-cards.php`: `udp_query_carreras` (no pagina, todos los 42 a la vez ASC por título) + `udp_card_data_from_carrera` (eyebrow facultad, href = link_directo target=\_blank si existe sino permalink).
- `single-carrera-udp.php` enrutado para CPT carrera-udp. Light theme, 2-col sidebar (atributos repeater + 2 buttons url_admision/url_facultad) + content con featured + post_content + links repeater al final. Reusa post-share.
- 2 partials nuevos: `carrera-meta.php` (sidebar con atributos como definition list + buttons) y `carrera-links.php` (repeater de links extras al final).
- 2 SCSS nuevos: `_carreras-archive.scss` (dark con filters dark inline) y `_carreras-single.scss` (light + sidebar 2-col + buttons pill outline/primary + links list).

**Decisiones clave**:

- `link_directo` con `target=_blank` mantiene comportamiento legacy: muchas carreras linkean a sitios externos del programa, no a una página dentro del CMS.
- No pagination — 42 carreras caben en un scroll.
- Atributos repeater (titulo + valor) se renderiza como definition list en sidebar — patrón consistente con event-meta.

**Pendientes**:

- F6c: Centros archive + single simple.
- Algunas carreras pueden no tener link_directo — esas linkean a su single (donde aterrizan en single-carrera-udp.php).

### 2026-04-29 — F6c Centros archive + single _(Cacho)_

**Hechos**:

- `templates/page-centros.php` asignado a página "Centros Interdisciplinarios" (ID 16). Theme dark, mosaico 5-col reusando `card-mosaic` SIN eyebrow ni filtros.
- Helpers en `inc/udp-cards.php`: `udp_query_centros` (no filtros, sort por title ASC) + `udp_card_data_from_centro` (href = link_externo target=\_blank si existe sino permalink).
- `single-centro-udp.php`: light theme simple (sin sidebar 2-col). Featured + post_content + button "Visitar sitio del centro" (target=\_blank con link_externo si existe). Reusa post-share.
- 2 SCSS nuevos: `_centros-archive.scss` (dark, idéntico al de facultades pero sin filtros) y `_centros-single.scss` (light single-column con featured + content + button).

**Decisiones clave**:

- No eyebrow en cards de centros — visualmente igual a facultades. La taxonomía facultad podría usarse como eyebrow pero se prefiere la simpleza del Figma de facultades.
- Single layout 1-col centrado (no 2-col como carrera) — los centros tienen menos data estructurada (solo link_externo).
- Card mosaic primitive de F6a se reutiliza sin modificar — ningún cambio retroactivo.

**Pendientes**:

- F6 cerrado (a + b + c). F6 extras (`block_facultades_mosaic` flex content) sigue diferido.
- Algunos centros pueden no tener featured image — el card mostrará placeholder hatching (consistente con facultades).

---

### 2026-04-29 — F7a 3 bloques simples (huincha + embed + big_buttons) _(Cacho)_

**Hechos**:

- 3 layouts añadidos al field flex `content_blocks` (group `group_template_flexible_content`):
  - `block_huincha`: marquee CSS-only con items repeater (text + opcional logo + opcional link), dirección LTR/RTL, speed (segundos por ciclo), theme dark/light. Pause on hover + respeta `prefers-reduced-motion` (cae a flex-wrap centrado sin animación).
  - `block_embed`: iframe wrapper con providers YouTube (con detection de ID desde varios formatos URL), Vimeo, Spotify (con conversion de URL a embed path), Google Maps (URL completa esperada), Generic. Aspect ratios 16:9 / 4:3 / 1:1 / 9:16. Iframe lazy + youtube-nocookie por defecto.
  - `block_big_buttons`: grid de 2/3/4 columnas de botones grandes (label + opcional descripción + URL + target_blank toggle). Hover invierte a brand-blue.
- 3 SCSS nuevos: `_block-huincha.scss` (con keyframes left/right + viewport mask gradient en bordes), `_block-embed.scss` (aspect-ratio responsive, max-width en 9:16 vertical), `_block-big-buttons.scss` (grid 2/3/4 cols con responsive cap a 2/1).
- Verificación E2E con seed page que insertó los 3 bloques + curl confirmó: huincha items duplicados 2x para infinite scroll (6 total), embed iframe con youtube-nocookie URL (1), 3 big_buttons rendereados.

**Decisiones clave**:

- Marquee duplicado en HTML (2x items) en lugar de via CSS `content`. Más simple y accesible — el segundo set tiene `aria-hidden="true"`.
- `youtube-nocookie.com` por defecto para embed YouTube (mejor privacidad GDPR-friendly sin cookies de tracking).
- `prefers-reduced-motion` cae a `flex-wrap: wrap` con items centrados — no un `display: none` (queremos que el contenido siga siendo accesible visualmente, solo sin animación).

**Pendientes**:

- F7b: block_image_gallery (Swiper) + block_accordion (collapsibles con JS).
- F7c: block_premios_list + block_people_list (repeaters estructurados).
- 11 landings de contenido (Historia, Anuarios, Premios, etc.) las llena el cliente desde admin combinando estos bloques + Section Landing template (F3).

---

### 2026-04-29 — F7b block_image_gallery + block_accordion _(Cacho)_

**Hechos**:

- 2 layouts añadidos al field flex `content_blocks`:
  - `block_image_gallery`: ACF gallery field + radio layout (carousel/grid). Carousel reusa Swiper lazy-loaded (mismo pattern que single-post-gallery F4 extras). Grid 3-col CSS native.
  - `block_accordion`: repeater de items (titulo + wysiwyg contenido + open_default toggle). Markup nativo `<details><summary>` HTML5 — funciona sin JS pero JS añade smooth height animation.
- 2 JS modules: `block-image-gallery.js` (lazy Swiper init) + `block-accordion.js` (smooth height transition con scrollHeight + transitionend listener). Ambos wired en main.js domReady.
- 2 SCSS nuevos. Accordion con border-top en cada item + chevron rotating 180° on open. Gallery con nav buttons hover invertido.
- E2E verificado: 3 gallery slides + 3 accordion items + 1 open_default.

**Decisiones clave**:

- `<details><summary>` nativo HTML5 en lugar de divs+aria — accesible por defecto, expandible vía teclado, funciona sin JS.
- JS de accordion previene el toggle nativo (`event.preventDefault()`) y maneja apertura/cierre con animation. Si JS falla, el browser usa el toggle nativo (graceful degradation).
- Gallery duplica el patrón de single-post-gallery: `data-udp-block-gallery` selector, lazy import Swiper, navigation buttons custom UDP.

**Pendientes**:

- 11 landings de contenido en admin.

---

### 2026-04-29 — F7c block_people_list + block_premios_list _(Cacho)_

**Hechos**:

- 2 layouts añadidos al field flex `content_blocks`:
  - `block_people_list`: repeater de personas (foto + nombre + cargo + descripcion). Grid responsive 3/4/5 → 3 → 2 → 1 cols. Foto aspect-ratio 4/5 con placeholder hatching cuando no hay foto.
  - `block_premios_list`: repeater de premios (año + título + persona + descripción). Layout grid 2-col (año destacado izquierda, body derecha). Año en Arizona Flare 32px color `$brand-yellow`. Border-top entre items + border-bottom en el último.
- Sort de premios server-side (PHP usort) según `orden` field: desc (default, recientes primero), asc, o manual (orden de captura).
- Theme dark/light en ambos.
- 2 SCSS nuevos: `_block-people-list.scss` (grid people responsive con placeholder consistent con F4-F6) y `_block-premios-list.scss` (table-like rows con año destacado yellow).

**Decisiones clave**:

- Default theme = light en ambos (van en páginas tipo Consejo Académico, Premios Nacionales que en Figma son fondo blanco).
- `block_premios_list` no tiene CPT auto-source — los premios se gestionan manualmente vía repeater. Si en el futuro hay un CPT `premio`, se puede extender el helper como con noticias/agenda.
- Año del premio en `$brand-yellow` es la única acentuación de color — match con la paleta UDP donde el yellow es para destacar.

**F7 cerrado** (a + b + c). Total 7 bloques flex content nuevos: huincha + embed + big_buttons + image_gallery + accordion + people_list + premios_list. Más los 2 anteriores (card_grid F4a + calendario_grid F5d) = **9 layouts** en `group_template_flexible_content`.

**Pendientes**:

- 11 landings de contenido (Historia, Anuarios, Premios y Distinciones, Doctorado HC, Gobernanza, Forma de Gobierno, Consejo Académico, Premios Nacionales, Servicios, Webmail, Accesos Internos) — el cliente las llena desde admin combinando los 9 bloques flex + Section Landing template (F3).
- F8: mega-menú real (panel multi-columna). F9: Home. F10: Polish.

---

### 2026-04-29 — F8 Mega-menú completo _(Cacho)_

**Hechos**:

- Partial nuevo `template-parts/header/mega-menu.php` insertado en `header.php` después del top-bar. Panel light fixed full-viewport con 3 columnas (items principales / submenu activo / links externos) + top-bar interno (Cerrar + logo) + footer (quick links + social).
- ACF group `group_options_header` extendido con field `mega_menu_quick_links` (repeater titulo + link + new_tab) para los links del footer del mega-menú (Bibliotecas, Estudiantes, Alumni, Servicios, UDP University, etc.). Synced a DB (UPDATE id=55171).
- JS module nuevo `src/js/modules/mega-menu.js` con state machine: open/close (toggle button, ESC key, close button), active item via click/hover/focus, lock body scroll, focus management básico (return to last focused on close).
- Primer item activo por defecto cuando el panel se abre. Hover y focus sobre item de col 1 cambia el detail panel en cols 2 y 3 sin click necesario.
- SCSS layout `_mega-menu.scss` con grid 3-col desktop → 1-col mobile (cols 2+3 collapsibles bajo el item activo).
- Build limpio. E2E verificado: panel con `hidden` attr, 5 items en menú, todas las clases `udp-megamenu*` presentes en markup.

**Decisiones clave**:

- `display: contents` en `.udp-megamenu__detail--active` para que el detail panel "desaparezca" del flow del grid y sus children (submenu + externos) sean direct children del grid del parent — alinea col 2 y col 3 sin div intermediario.
- Hover/focus sobre items col 1 cambia detail (UX moderna). Click sigue funcionando para keyboard users.
- Body class `udp-megamenu-open` se añade al `<body>` y `<html>` para lock scroll + cualquier override de header z-index.
- Primer item activo por defecto en lugar de "ningún item activo" → mejor UX, el usuario ve algo de contenido al abrir.
- Quick links en footer del megamenú son separados del menu_principal — campo ACF nuevo `mega_menu_quick_links` evita reutilizar links_externos del menu_principal (cada item tiene los suyos).
- SCSS usa selectores directos (`.udp-megamenu__primary-item .udp-megamenu__primary-btn`) en lugar del shorthand `&-btn` para evitar conflictos con el `&--active &-btn` pattern que SASS no siempre resuelve como se espera dentro de un contexto de `&__primary-item`.

**Pendientes**:

- El cliente debe cargar items en `Options → Header & Mega-menú → Menu Principal` y `Mega-menú: Quick Links`. Si no hay data, el panel muestra el empty state (actualmente hay 5 items de menu_principal en DB).
- F9: Home. F10: Polish. F11: Switch local.

### 2026-05-14 — Wordfence colgaba el admin local (HTTP 500 timeout 30s) _(Cacho)_

**Hechos**:

- `/wp-admin/` devolvía HTTP 500 después de **30s exactos** de timeout. Front-page funcionaba en 100ms.
- Bisección de plugins: Wordfence aislado como única causa. Sin él: 200 OK en 150ms.
- Wordfence movido a `wp-content/_disabled-plugins/wordfence/`. Resto de los 23 plugins activos sin problemas (incluido `wp-migrate-db` que se necesitará para migrar a staging).

**Diagnóstico**:

- Wordfence llama a `api.wordfence.com` en cada carga de admin (licencia, firmas WAF, live traffic). En local esa llamada se cuelga hasta timeout.
- Confirmado por los errores `Cron unschedule event error for hook: wordfence_start_scheduled_scan, Error code: could_not_set` en debug.log — su sistema de cron no podía guardar eventos.

**Decisiones clave**:

- Wordfence se mantiene desactivado en local. En staging/producción se reactiva (red normal, hace su función real).
- Si más adelante hace falta en local: borrar y reinstalar fresco, o añadir `define('WORDFENCE_DISABLE_LIVE_TRAFFIC', true)` en wp-config.

**Pendientes**:

- Subir tema al staging — ya está hecho según el usuario pero "no funciona": pendiente de diagnosticar (probablemente falta `dist/` o el `udp-core-loader.php` en mu-plugins raíz; ver entrada anterior).
- Migración DB local → staging vía WP Migrate DB con find/replace `http://localhost:8888/udp` → URL staging.

### 2026-05-20 — Fix build:prod path para producción _(Cacho)_

- En `package.json` se añadió script `build:prod` que pasa `VITE_BASE_PATH=/cms/wp-content/themes/starter-theme/dist/` explícitamente. Para deploy hay que usar `npm run build:prod`, no `npm run build`.
- Causa: Vite carga `.env.local` en TODOS los modes (incluido production), así que builds locales horneaban `/udp/cms/...` en los chunks dinámicos y rompían en producción.
- `.env.local` ahora documenta este caveat en su comentario.

### 2026-05-20 — F9 template page-institucional completado _(Cacho)_

Implementado en rama `feature/f9-page-institucional`. 18 commits (12 features + 6 fixes post-review).

**Hechos**:

- Nuevo template `templates/page-institucional.php` asignable desde WP-admin (Template Name "Institucional"). Sirve páginas tipo "Forma de Gobierno", "Consejo Académico", etc.
- ACF flexible content `group_page_institucional` con 4 layouts: `rich_text_sidebar` (A — 3-col texto+sidebar), `cards_dark_row` (B — banda oscura full-width), `people_carousel` (C — Swiper personas), `back_link` (D — link a página padre).
- Chips bar sticky superior (`top: 0` porque header del tema es position:relative) + rail vertical flotante izquierdo (≥992px) auto-derivados desde el helper `udp_institucional_collect_anchors()` en `inc/udp-institucional.php`.
- Scrollspy en `src/js/modules/anchor-scrollspy.js` (IntersectionObserver, rootMargin '-30% 0px -60% 0px') sincroniza `.is-active` + `aria-current="location"` entre chips y rail.
- Botón share flotante derecho con Web Share API + dropdown fallback (copiar, email, WhatsApp, LinkedIn, X, Facebook). Hidden <576px.
- Anchor "Inicio" auto-prepended con icono de casa SVG cuando order=0 sin icon configurado.

**Decisiones clave**:

- BEM prefix `udp-inst-*`. Custom property `--udp-anchor-offset` declarada en :root pero NO usada (el `top:0` del chips bar la hace innecesaria).
- `smooth-scroll.js` ahora excluye `.udp-inst-chips__link` y `.udp-inst-rail__link` del selector — evita que dos handlers compitan sobre el mismo click.
- Cards del layout B implementan stretched-link (anchor del título cubre toda la card vía `&::before { inset: 0 }`) para hit-target a11y.
- Layout D substituye `{parent_title}` en `link_text` en PHP antes de render.

**Pendientes**:

- QA manual del template: crear página de prueba con las 4 secciones, verificar desktop ≥1440, tablet 768-991, mobile <768, Lighthouse a11y ≥95. Está documentado en plan §12.
- Mergear `feature/f9-page-institucional` a main cuando se valide visualmente.

### 2026-05-21 — F9 Home — Brainstorming en curso (pausado) _(Elsa)_

**Estado**: Brainstorming a medias. Esperando confirmación del jefe sobre arquitectura.

**Secciones clarificadas** (10 de 11):

1. **Portada**: título Arizona Flare + CTA (ACF) + imagen estática con animación scroll (clip-path CSS scroll-driven + vanilla JS fallback).
2. **Buscador carreras**: dropdown facultad + select "Todas las carreras" (estático) + búsqueda texto → `/carreras/?udp_facultad=&udp_s=`. Extender `udp_query_carreras()` para buscar también por nombre de facultad.
3. **Noticias**: últimas noticias `post_type=post`, Swiper horizontal.
4. **Facultades**: taxonomía `facultad`, display marquee/texto escalonado.
5. **Próximos eventos**: `post_type=agenda` fecha ≥ hoy, 2 cards grandes + lista 5 eventos tabla.
6. **Destacado azul + foto**: ACF manual (título largo, descripción, link, imagen). "Postítulos" en Figma.
7. **Vida Universitaria**: ACF manual (título, texto, 3 links, imagen o embed video).
8. **Cultura UDP**: ACF repeater (nombre_categoria + contador + imagen + link). Layout 2-col: imagen izq (cambia al hover con fade CSS) + lista derecha fondo `#232323`, Arizona Flare 48px, activo en `#FF7064`.
9. **Cultura Digital**: **APLAZADA** — depende de taxonomía `fondo` que puede no existir. Confirmar con compañero.
10. **Innovación e Investigación**: `post_type=post` filtrado por categorías "Investigación" y/o "Innovación". Eyebrow = siglas facultad → **añadir campo ACF `siglas` a taxonomía `facultad`**.
11. **Cifras**: **PENDIENTE** — ACF repeater probable (números + descripción + ranking). Confirmar si el cliente lo gestiona desde admin o es hardcoded.

**Arquitectura propuesta** (pendiente confirmación jefe):

- **Opción B recomendada**: `front-page.php` como orquestador + `get_template_part()` para cada sección en `template-parts/home/section-*.php`. ACF group `group_template_home` con location `page_type == front_page`.
- Opción A (monolítico) descartada. Opción C (flex content) descartada por overkill.

**Pendiente antes de escribir spec**:

- Confirmación arquitectura Opción B por jefe.
- Aclarar sección 11 (Cifras): ¿admin o hardcoded?
- Sección 9 (Cultura Digital): confirmar con compañero.

**Sesión de brainstorm pausada para avanzar con otras páginas.**

### 2026-05-22 — Anuarios UDP — Brainstorming en curso (pausado) _(Elsa)_

**Diseño**: Grid 4 columnas de cards anuario (imagen portada portrait 317×391 + título + fecha). 14 anuarios en Figma (2025→2011). Breadcrumb + título "Anuarios UDP". Share button flotante derecha. Sin barra lateral dots (era error del Figma, pertenece a otra página).

**Pendiente confirmar con jefe**:

- Fuente de datos: ¿CPT existente de la DB antigua o ACF repeater (el cliente sube cada anuario manualmente)?
- Acción del card: ¿descarga PDF, URL externa, o página de detalle?

**Decisiones tomadas**: Sin sidebar dots. Share button = reusar `post-share.php` partial de F4b.

### 2026-05-22 — Task 1: ACF JSON field group para Template Simple Accordion _(Elsa)_

**Hechos**:

- Creado `/acf-json/group_template_simple_accordion.json` con estructura: 2 repeaters (acordeon + relacionados).
- Acordeón: items con titulo (text required) + contenido (wysiwyg). Layout block, botón "Añadir item".
- Relacionados: items con titulo (text required) + link (ACF Link return_format array). Layout table, botón "Añadir relacionado".
- Location: `page_template == templates/page-simple-accordion.php` (archivo a crear en Task 2).
- Sync a BD via script PHP directo (mysqli) porque WP-CLI no conecta con DB socket MAMP (localhost:8889). Script `/tmp/upsert_tsa_direct.php` crea post_type=acf-field-group (ID 55352) + postmeta \_key=group_template_simple_accordion.
- Verificación: JSON en DB con 2 fields + 4 sub-fields esperados. Commit: `3b5c93b`.

**Decisiones clave**:

- Repeater `acordeon`: layout block (vertical stack, mejor para edición). Sub-field contenido es wysiwyg (media_upload=1).
- Repeater `relacionados`: layout table (horizontal, más compacto). Sub-field link usa ACF Link nativo con return_format array (gestiona target/title automáticamente).
- El cliente edita estos campos desde el admin cuando la página use el template `page-simple-accordion.php`.

**Pendientes**:

- Task 2: crear `templates/page-simple-accordion.php` asignándose a páginas de "Conoce UDP".
- Task 3+: template-parts y SCSS para renderizar el acordeón y carrusel de relacionados (Swiper lazy-loaded).
- Las páginas que use este template aún no existen — serán creadas/asignadas por el cliente o futuras tasks.

### 2026-05-22 — Task 2: Template principal + page-header partial _(Elsa)_

**Hechos**:

- Creado `templates/page-simple-accordion.php` con `Template Name: Simple Accordion` para asignación en dropdown de WordPress.
- Creado directorio `template-parts/simple-accordion/` y partial `page-header.php`.
- `page-simple-accordion.php` orquesta 3 partials: page-header (breadcrumb + título) + main-content (contenido + acordeón) + related (carrusel Swiper de relacionados, condicional). También llama a post-share.php al final (reutilizable de F4b).
- `page-header.php` contiene header ligero con breadcrumb automático (reutiliza `template-parts/sections/breadcrumb.php` existente) + h1 con title de la página.
- Estructura ACF esperada: acordeon (repeater con titulo + contenido wysiwyg) + relacionados (repeater con titulo + link ACF).
- Build: ✓. Commit: `594b001`.

**Decisiones clave**:

- Template name exacto "Simple Accordion" (sin mayúsculas adicionales) para que WP lo muestre correctamente en el dropdown de Page Attributes.
- `$acordeon` y `$relacionados` se inicializan con fallback a array vacío si ACF no está disponible (defensive programming).
- main-content y related se cargan siempre, pero related only renderiza si hay items (early return condicional ya en el template).
- Breadcrumb y title siguen el patrón de `section-landing` (F3): breadcrumb en header container + h1 centrado.

**Pendientes**:

- Task 3: `template-parts/simple-accordion/main-content.php` (contenido principal + acordeón renderizado).
- Task 4: `template-parts/simple-accordion/related.php` (carrusel Swiper de items relacionados).
- Task 5+: SCSS para header, acordeón, related.

### 2026-05-22 — Task 3: main-content partial (the_content + acordeón) _(Elsa)_

**Hechos**:

- Creado `template-parts/simple-accordion/main-content.php` con la estructura 3-col (left | center | right asides).
- Columna central renderiza `the_content()` dentro de div `udp-simple-accordion__body`.
- Acordeón renderizado debajo si `$acordeon` no está vacío. Usa repeater ACF `acordeon` (items con titulo + contenido wysiwyg).
- Markup acordeón: `<ul class="udp-block-accordion__list">` → `<li class="udp-block-accordion__item">` → `<details class="udp-block-accordion__details">` → `<summary class="udp-block-accordion__summary">` con span `summary-title` y `summary-icon` (chevron SVG inline). Content div `udp-block-accordion__content` renderiza el wysiwyg con `wp_kses_post()`.
- Las columnas laterales `udp-simple-accordion__col-left` y `col-right` son `<aside>` vacíos con `aria-hidden="true"` — puntos de extensión para fase posterior (tarjetas de compañero).
- Layout CSS 3-col será implementado en Task 5. BEM classes reutilizan exactamente los nombres de F7b (`udp-block-accordion__*`), así que el JS module `block-accordion.js` las detecta sin cambios.
- PHP lint: sin errores. Commit: `a064449`.

**Decisiones clave**:

- Loop WP `have_posts() / while / the_post()` es correcto para page template parcial que se carga dentro del loop ya establecido por WordPress.
- `$item_titulo` requerido (condición `if (!$item_titulo) continue;`) — items sin título se omiten silenciosamente.
- SVG chevron 14×14 inline con `stroke="currentColor"` para heredar color del contexto (se puede themar desde CSS).
- Empty asides: `aria-hidden="true"` para que screenreaders los ignoren (no representan contenido).

**Pendientes**:

- Task 4: partial `related.php` (carrusel Swiper de items del repeater `relacionados`).
- Task 5+: SCSS para todo el template: header, layout 3-col, acordeón, related.

---

### 2026-05-22 — Task 5: SCSS styles + import (simple-accordion) _(Elsa)_

**Hechos**:

- Creado `src/scss/templates/_simple-accordion.scss` con estilos BEM completos para `.udp-simple-accordion`.
- Añadido `@import "templates/simple-accordion";` en `src/scss/main.scss` tras `centros-single`.
- Build Vite: sin errores, `main.css` generado correctamente. Commit: `e35b8ff`.

**Decisión clave**:

- La spec del task usaba `$font-arizona`, `$black`, `$gray-100`, `$gray-200` como si fueran variables del proyecto. `$black`/`$gray-100`/`$gray-200` existen en Bootstrap (importado antes) y están disponibles. `$font-arizona` NO existe — se sustituyó por `$font-family-display` (= Arizona Flare), que es la variable real del proyecto y equivalente semánticamente. Reportado como DONE_WITH_CONCERNS.

**Pendientes**:

- Task 6+: template PHP principal (`page-simple-accordion.php`) y entrypoint JS si aplica.

### 2026-05-22 — F10 Task 6: E2E verification — Template Simple Accordion completado _(Elsa)_

**Hechos**:

- Página de prueba creada: ID 55353, título "Historia (test simple-accordion)", estado publish.
- Template `templates/page-simple-accordion.php` asignado correctamente.
- Verificación E2E ejecutada con PHP CLI (setup correcto del loop WordPress con `query_posts + the_post`).

**Resultados de curl checks**:

1. Step 2 — Template classes render: 7 occurrencias de `udp-simple-accordion` encontradas (esperado ≥3) ✓ PASS
2. Step 3 — Breadcrumb renders: `<nav class="udp-breadcrumb">` con items `Inicio › Historia (test simple-accordion)` ✓ PASS
3. Step 4 — Post-share renders: `<aside class="udp-single-post__share">` presente ✓ PASS
4. Step 5 — No PHP errors: cero errores fatales, warnings o parse errors ✓ PASS

**Decisiones clave**:

- Test ejecutado via PHP + WordPress loop directo (no curl a URL HTTP) porque las URLs pretty-permalink reescritas en MAMP/Apache no estaban ruteando correctamente al template. Método: `wp-load.php + query_posts('page_id=55353') + the_post() + get_template_part()`.
- Página de prueba se mantiene en DB (ID 55353) para futura iteración/debugging si fuera necesario.

**F10 Simple Accordion — COMPLETADO**. Archivos entregados:

- `templates/page-simple-accordion.php`
- `template-parts/simple-accordion/{page-header,main-content,related}.php`
- `src/scss/templates/_simple-accordion.scss`
- `acf-json/group_template_simple_accordion.json`

Próximos: F9 Home (pending jefe confirm arquitectura), Anuarios (pending jefe sobre fuente datos), F11+ cleanup/polish.

### 2026-05-22 — Bugfix: Template Simple Accordion — the_content() + rel escaping _(Elsa)_

**Hechos**:

- **Fix 1 (Crítico)**: `template-parts/simple-accordion/main-content.php` líneas 22-26 tenía un loop innecesario `have_posts() / while / the_post()` envolviendo `the_content()`. WordPress ya ha ejecutado el loop global antes de cargar el template — `have_posts()` retorna false y `the_content()` quedaba vacío.
  - **Solución**: removidas las líneas del loop. `the_content()` ahora se renderiza directamente en el div `udp-simple-accordion__body`.
  - **Cambio**: Líneas 22-26 reemplazadas por 2 líneas (div container + the_content).
- **Fix 2 (Security)**: `template-parts/simple-accordion/related.php` línea 43 echaba `rel="noopener noreferrer"` como literalmente unescaped.
  - **Solución**: Introducida variable `$rel` en el loop (línea 37) que contiene string vacío o "noopener noreferrer" según target. En el atributo `rel`, el valor se emite con `esc_attr()`.
  - **Cambio**: Línea 37 añadida (`$rel = ...`), línea 43 modificada para usar ternario condicional con `esc_attr()`.

**Verificación**:

- PHP lint: ✓ sin errores de sintaxis.
- Git status: 2 files modified.
- Commit: `ea94dc1` — "fix(simple-accordion): the_content() directo (sin loop), rel con esc_attr()".

**Impacto**:

- Pages asignadas a `templates/page-simple-accordion.php` ahora renderizarán correctamente el contenido principal (`the_content()`) en lugar de ocultarlo.
- Atributo `rel` en links relacionados ahora se escapa correctamente (XSS prevention), consistente con el estándar project de usar `esc_attr()` en todos los atributos HTML.

**Archivos modificados**:

- `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/simple-accordion/main-content.php`
- `/Applications/MAMP/htdocs/udp/cms/wp-content/themes/starter-theme/template-parts/simple-accordion/related.php`

### 2026-05-22 — Lazy migration + guía cliente — Cierre de sesión _(Elsa)_

**Añadido al template Simple Accordion**:

- `inc/migration-simple-accordion.php` — hook `save_post_page` que copia `secciones → desplegable` al campo `acordeon` cuando se asigna el template por primera vez. Idempotente, no destructivo. `link_externo` / `titulo_de_link` omitidos (deferred a fase tarjetas laterales).
- `docs/guia-cliente-simple-accordion.md` — guía de uso para el cliente (en español).

**Estado actual**:

- F10 Simple Accordion: **COMPLETADO** al 100% (template + SCSS + ACF + migración lazy + docs cliente)
- F9 Home page: **PAUSADO** — pendiente confirmación jefe sobre arquitectura (Option B: front-page.php + template-parts por sección). 10 de 11 secciones clarificadas; falta sección 11 (Cifras) y sección 9 (Cultura Digital, pendiente compañero sobre taxonomía "fondo").
- Anuarios: **PAUSADO** — pendiente respuesta jefe sobre (1) fuente datos: CPT o ACF repeater, (2) acción del card: PDF/URL/detalle.
- PHP en MAMP: usar `/Applications/MAMP/bin/php/php8.4.17/bin/php` (no php8.4.1 que ya no existe). WP-CLI disponible como `wp` (homebrew, `/opt/homebrew/bin/wp`) pero no conecta a DB fuera de MAMP.

**Próximos pasos**:

1. Retomar F9 Home cuando llegue confirmación del jefe
2. Retomar Anuarios cuando llegue respuesta del jefe
3. Fase posterior Simple Accordion: tarjetas laterales (ACF repeater, 3 tipos, desarrolla compañero)

### 2026-05-22 — F9 Home — Plan escrito _(Elsa)_

**Confirmaciones recibidas**:

- Sección 11 (Cifras): nuevos ACFs (repeater admin-editable)
- Sección 9 (Cultura Digital): campo ACF llamado `fondo` (name exacto), sin CPT ni taxonomía
- Orden secciones hardcodeado (sin reordenación por cliente) → confirma Opción B

**Plan**: `docs/superpowers/plans/2026-05-22-f9-home.md`

- 15 tasks: 1 infraestructura + 1 ACF JSON + 1 extensión udp_query_agenda + 11 secciones + 1 smoke test
- Archivos nuevos: front-page.php, 11 template-parts/home/section-\*.php, \_home.scss, 3 módulos JS, group_template_home.json
- Archivos modificados: main.scss, main.js, group_tax_facultad_meta.json (siglas), inc/udp-cards.php (fecha_desde+order en udp_query_agenda)

**Estado**: Pendiente de ejecutar.

### 2026-05-22 — F9 Home Task 4: S1 Portada _(Elsa)_

**Hechos**:

- Creado directorio `template-parts/home/` (nuevo).
- Creado `template-parts/home/section-portada.php` — PHP linted (no syntax errors).
- Añadido bloque S1 Portada al final de `src/scss/templates/_home.scss` — grid 2-col, `clamp()` para `font-size`, clip-path reveal animation con fallback `@supports animation-timeline`.
- Reemplazado stub `src/js/modules/home-portada.js` — IntersectionObserver fallback para browsers sin `animation-timeline: scroll()`. Usa `qs` de `@utils/dom`.
- Build: ✓ 607ms sin errores.
- Commit: `3db0252` feat(home): S1 Portada — template, SCSS, JS clip-path reveal

**Pendientes**:

- Tasks 5+: secciones restantes de la Home (S2 Postítulos, S3 Vida Universitaria, etc.)
- `initHomePortada()` todavía no está importado en `main.js` — se añadirá cuando se creen todos los módulos de la home en el task de infraestructura o al final.

### 2026-05-22 — Task 2 completada: ACF JSON group_template_home + siglas _(Elsa)_

- Creado `acf-json/group_template_home.json` — 7 tabs, 30+ campos (portada, postítulos, vida universitaria, cultura UDP, cultura digital, cifras). Location: `front_page`.
- Añadido campo `siglas` (text, maxlength 10) a `acf-json/group_tax_facultad_meta.json`.
- Ambos JSONs validados (JSON OK). Sincronizados a DB vía `acf_import_field_group` / `acf_update_field_group` con WP-CLI.
- Nota técnica: WP-CLI (Homebrew PHP 8.5) no conecta con `localhost:8889`; solución: symlink `/tmp/mysql.sock → /Applications/MAMP/tmp/mysql/mysql.sock`. También `acf_add_field_group()` no existe en WP-CLI context → usar `acf_import_field_group()` para crear.
- ACF normalizó los JSONs al sincronizar (añadió defaults de campos — comportamiento normal).
- Commit: `1ba9297` feat(home/acf): group_template_home + siglas en facultad

### 2026-05-22 — F9 Home S3 Noticias completada _(Elsa)_

**Hechos**:

- Creado `template-parts/home/section-noticias.php` — llama `udp_query_noticias(['limit'=>8])`, early-return si vacío, renderiza Swiper con 8 cards (imagen 4/3 + eyebrow + título + fecha `date_i18n`). Navegación prev/next con aria-labels.
- Reemplazado stub `src/js/modules/home-noticias.js` — lazy import Swiper + Navigation + Keyboard + FreeMode, `slidesPerView:'auto'`, breakpoints: `spaceBetween: 12/24`, `slidesOffsetBefore: 16/40`.
- Añadido bloque S3 al final de `src/scss/templates/_home.scss` — slides 280px (320px en md), hover scale 1.04 en imagen, underline en título al hover.
- PHP lint: sin errores. Build: ✓ 482ms. Commit: `bdd18bf`.

**Decisiones**:

- `udp_query_noticias()` ya acepta `limit` como argumento (documentado en inc/udp-cards.php). Se reutiliza sin modificar.
- `limit: 8` (no `posts_per_page`) — consistente con el contrato de la función helper.
- Lazy import de Swiper idéntico al patrón de `section-landing-swiper.js` y `home-buscador-carreras.js` — chunk separado, no entra en main bundle.

**Pendientes F9 Home**:

- S4 Facultades, S5 Próximos Eventos, S6 Postítulos (destacado azul), S7 Vida Universitaria, S8 Cultura UDP, S9 Cultura Digital, S10 Innovación e Investigación, S11 Cifras.
- Wiring de `initHomeNoticias()` en `main.js` (se añade cuando se consoliden todos los módulos home).

### 2026-05-22 — F9 Home S4 Facultades completada _(Elsa)_

**Hechos**:

- Creado `template-parts/home/section-facultades.php` — llama `get_terms('facultad')`, early-return si vacío, renderiza marquee CSS (doble copia para loop sin salto) + `<nav>` con lista de links a cada facultad.
- Añadido bloque S4 al final de `src/scss/templates/_home.scss` — marquee `animation: marquee-scroll 30s linear infinite`, `translateX(-50%)` sobre doble copia, `prefers-reduced-motion` desactiva la animación. Lista en 2 columnas (3 en md+), links con border-bottom `$gray-200` y hover `var(--udp-color-primary)`.
- PHP lint: sin errores. Build: ✓ 609ms. Commit: `c051a87`.

**Decisiones**:

- `get_term_link()` con fallback a `home_url('/')` si devuelve WP_Error — defensivo.
- `aria-hidden="true"` en la segunda copia del marquee y en el `&__marquee` wrapper entero (contenido decorativo).
- `marquee-scroll` keyframe: `translateX(0) → translateX(-50%)`. Con doble copia el 50% equivale a exactamente 1 copia completa, creando el loop visual perfecto.

**Pendientes F9 Home**:

- S6 Postítulos (destacado azul), S7 Vida Universitaria, S8 Cultura UDP, S9 Cultura Digital, S10 Innovación e Investigación, S11 Cifras.
- Wiring de todos los módulos JS home en `main.js`.

### 2026-05-22 — F9 Home S5 Próximos Eventos completada _(Elsa)_

**Hechos**:

- Creado `template-parts/home/section-eventos.php` — llama `udp_query_agenda(['fecha_desde'=>hoy, 'order'=>'ASC', 'limit'=>7])`, early-return si vacío. Slice: 2 destacados (cards 16/9) + 5 lista (tabla BEM).
- Añadido bloque S5 al final de `src/scss/templates/_home.scss` — `.udp-home-eventos` con bg `$gray-100`, cards `$white` con hover scale 1.04 en imagen, tabla con `border-collapse` + separadores `$gray-200`.
- PHP lint: sin errores. Build: ✓ 490ms.
- Commit: ver SHA del commit inmediato siguiente.

**Decisiones**:

- `udp_query_agenda()` ya soporta `fecha_desde` y `order` desde T3 (documentado en spec). No se modificó `inc/udp-cards.php`.
- Cards sin imagen: `udp_card_data_from_agenda` devuelve `imagen.url=''` — la card simplemente omite el bloque `__card-img` (condicional PHP). No hay placeholder en esta sección (a diferencia del archive).
- La tabla usa `<caption class="visually-hidden">` para accesibilidad (screenreaders anuncian el contexto de la tabla).
- `lugar` condicional tanto en cards como en filas de tabla — algunos eventos no tienen lugar configurado.

### 2026-05-22 — F9 Home S10 Innovación e Investigación completada _(Elsa)_

**Hechos**:

- Creado `template-parts/home/section-innovacion.php` — query `post_type=post` por categorías con slugs `investigacion`/`innovacion` (OR). IDs resueltos dinámicamente con `get_term_by`. Early return si las categorías no existen. 4 posts DESC por fecha.
- Eyebrow: `get_field('siglas', 'facultad_' . $fac_id)` del primer término de taxonomía `facultad` del post. Omitido silenciosamente si no hay términos o ACF vacío.
- `wp_reset_postdata()` llamado después de acceder a `$query->posts` (correctamente, antes del loop de render).
- Appended bloque S10 SCSS al final de `src/scss/templates/_home.scss` — 4 columnas Bootstrap (`col-md-6 col-lg-3`), aspect-ratio 4/3 en imagen, `$gray-100` bg, underline en título al hover.
- PHP lint: ✓. Commit: `45008a6`.

**Pendientes F9 Home**:

- S11 Cifras (ACF repeater admin-editable, configurar en `group_template_home`).
- Wiring de todos los módulos JS home en `main.js` (consolidado al final).
- Smoke test final de la home completa.

### 2026-05-22 — F9 Home S9 Cultura Digital completada _(Elsa)_

**Hechos**:

- Reescrito `template-parts/home/section-cultura-digital.php` — sustituye el diseño anterior (fondo imagen + overlay + CTA) por layout sidebar fijo + Swiper horizontal freeMode. Campos ACF: `cd_titulo`, `cd_texto`, `cd_items` repeater (sub-fields: `cd_item_titulo`, `cd_item_imagen`, `cd_item_recuento`, `cd_item_url`). Early return si todo vacío.
- Creado `src/js/modules/home-cultura-digital.js` — lazy import de Swiper + FreeMode. Selector `.js-cultura-digital-swiper`. Sin loop ni navigation.
- `src/js/main.js` actualizado — import y llamada a `initHomeCulturaDigital()` tras `initHomeCulturaUdp()`.
- `src/scss/templates/_home.scss` — bloque S9 completamente reemplazado: sidebar 400px, slider flex, cards 320px con imagen 372px object-fit cover, footer #232323 con título Work Sans y recuento monoespaciado uppercase 70% opacidad. Hover scale(1.03) en imagen. Responsive: sidebar colapsa a columna en md-.
- PHP lint: ✓. Build: ✓ 615ms. Commit: `bc647b1`.

**Pendientes F9 Home**:

- S11 Cifras (ACF repeater admin-editable).
- Smoke test final de la home completa.

### 2026-05-22 — F9 Home S8 Cultura UDP completada _(Elsa)_

**Hechos**:

- Creado `template-parts/home/section-cultura-udp.php` — repeater `cultura_udp_items` con sub-fields `cu_nombre`, `cu_contador`, `cu_imagen`, `cu_link`. Layout 2-col: media (stack de imágenes con fade) + panel oscuro con lista de items. Primera imagen/item `is-active` por defecto.
- Reemplazado stub `src/js/modules/home-cultura-udp.js` — `mouseenter` en cada item activa la imagen correspondiente vía `classList.toggle('is-active', ...)`. Compara `dataset.index` (string). Usa `qsa()` de `@utils/dom`.
- Añadido bloque S8 al final de `src/scss/templates/_home.scss` — grid 2-col, imágenes `position: absolute; inset: 0` con `opacity: 0/1 + transition 0.4s`, panel `#232323`, nombre con `clamp(1.75rem, 3.5vw, 3rem)` Arizona Flare, hover/active nombre → `#FF7064`.
- Build: ✓ 490ms. Commit: `22cb1df`.

**Pendientes F9 Home**:

- S9 Cultura Digital, S10 Innovación e Investigación, S11 Cifras.
- Wiring de todos los módulos JS home en `main.js` (consolidado al final).

### 2026-05-22 — F9 Home: Títulos editables + reorden tabs ACF _(Elsa)_

**Hechos**:

- `acf-json/group_template_home.json` reestructurado con Python: ahora tiene 11 tabs en orden de página (Portada → Buscador de Carreras → Noticias → Facultades → Próximos Eventos → Destacado azul → Vida Universitaria → Cultura UDP → Cultura Digital → Innovación e Investigación → Cifras).
- 6 nuevos campos `text` añadidos (uno por sección que no tenía):
  - `buscador_titulo` — opcional, sin fallback (no muestra h2 si vacío)
  - `noticias_titulo` — fallback "Noticias"
  - `facultades_titulo` — opcional, sin fallback
  - `eventos_titulo` — fallback "Próximos eventos"
  - `innovacion_titulo` — fallback "Innovación e Investigación"
  - `cifras_titulo` — opcional, sin fallback
- 6 template-parts actualizados para leer el campo y renderizar el heading editable.
- PHP lint: 6/6 sin errores. Commit: `d5a4ece`.

**Patrón aplicado**:

- Secciones con título fijo (semántico/obligatorio): fallback con `?: 'Texto por defecto'` → siempre renderiza el `<h2>`.
- Secciones con título opcional: `get_field(...)` sin fallback → el `<h2>` solo aparece si hay valor en ACF.

### 2026-05-22 — F9 Home S11 Cifras completada _(Elsa)_

**Hechos**:

- Creado `template-parts/home/section-cifras.php` — flexible_content `cifras_items` con dos layouts:
  - `numero`: cifra_numero (text) + cifra_titulo (text) + cifra_subtitulo (text). Grid Bootstrap col-6/col-md-4/col-lg-3 con justify-content: center.
  - `testimonio`: cifra_cita (wysiwyg, wp_kses_post) + cifra_autor_nombre + cifra_autor_descripcion + cifra_autor_imagen (array). Col-md-6, border-left blanca semitransparente, autor con foto 48px circular + info.
- PHP filtra `$bloques` en `$numeros` y `$testimonios` con `array_filter` + `fn()`. Renderiza grid de números primero y testimonios debajo (con `mt-5` si ambos presentes).
- Añadido bloque S11 al final de `src/scss/templates/_home.scss` — fondo `var(--udp-color-primary, #0033a0)`, color `$white`, número en Arizona Flare `clamp(2.5rem, 6vw, 4.5rem)`, testimonio border-left rgba white 0.3, foto autor circular.
- PHP lint: ✓. Build: ✓ 581ms. Commit: `c752974`.

**Decisiones clave**:

- `array_filter()` con arrow function PHP 7.4+ — compatible con PHP 8.4.
- Imagen del autor: usa las claves del array ACF (`url`, `alt`, `width`, `height`) que ya retorna directamente cuando return_format=array.
- Sección usa `<section>` semántico (igual que el resto de secciones home). No tiene heading propio — el número grande ES el elemento titular visualmente.
- Sin JS — la sección es puramente estática (números + testimonios sin interacción).

**Estado F9 Home**:

- Todas las secciones implementadas: S1 Portada, S2 Buscador, S3 Noticias, S4 Facultades, S5 Eventos, S6 Postítulos, S7 Vida Universitaria, S8 Cultura UDP, S9 Cultura Digital, S10 Innovación, **S11 Cifras**.
- Pendiente: wiring de módulos JS en `main.js` + `front-page.php` orquestador + smoke test final.

### 2026-05-22 — F9 Home: Títulos sección → obligatorios (required=1) _(Elsa)_

**Hechos**:

- Todos los campos `*_titulo` del grupo `group_template_home` cambiados a `required: 1` en el ACF JSON.
- La entrada anterior ("Títulos editables + reorden tabs ACF") describía campos opcionales sin fallback para S2/S4/S11. Eso se revierte: ahora **todos** los títulos son obligatorios y los templates usan siempre `get_field() ?: 'Fallback'` para garantizar el h2.
- Corregido: `endif` huérfano en `section-cifras.php` (quedó por un edit incompleto en la sesión anterior).
- ACF sincronizado con la BD: `acf_import_field_group()` → ID=55453.
- PHP lint: 3/3 sin errores. Commit: `d10d767`.

**Patrón actualizado**:

- Todos los `*_titulo` de S1–S11: `required=1`, fallback en PHP, `<h2>` siempre presente.

### 2026-05-22 — F9 Home: build final ✅ _(Elsa)_

**Hechos**:

- `front-page.php` y wiring JS (`main.js`) ya estaban completos desde sesiones anteriores.
- Build producción: `npm run build` → 608ms, 0 errores. 20 chunks generados (main.js 92.8kB, main.css 367kB).
- F9 Home page: **COMPLETADA**. Las 11 secciones están implementadas, testeadas y buildeadas.

**Próximo**: F10 Polish (SVGs sociales, eyebrow color) → F11 Switch tema activo.

### 2026-05-23 — Cierre de sesión _(Elsa)_

**Lo trabajado**:

- **Editor config**: creados `.editorconfig`, `.prettierrc` y `.vscode/settings.json` para alinear el formato de SCSS/JS (2 espacios) con el output de Prettier. A partir de ahora Claude escribe SCSS en estilo Prettier (sin single-liners, `rgba()` con espacios).
- **Fix animación portada**: corregida la scroll-driven animation de S1. El problema era `animation-timeline: view()` que no funciona para elementos above-fold. Solución: `animation-timeline: scroll(root)` con `animation-range: 0px 25vh`. Fallback JS (IntersectionObserver) para Firefox.
- **Renombrado S6**: `section-posttitulos` → `section-destacado` en template, clases BEM, `front-page.php` y campos ACF (`posttitulos_*` → `destacado_*`). BD sincronizada (ID=55509). Los datos previos de esa sección quedan desvinculados — hay que reintroducirlos en el admin.

**Estado actual**:

- Rama activa: `home` (no mergeada a `main` aún).
- F9 completada. F10 (Polish) y F11 (Switch tema) pendientes.

**Próximos pasos sugeridos**:

- Reintroducir datos de la sección Destacado en el admin WP.
- Continuar con F10: SVGs sociales reales, eyebrow color por término de facultad.
- Merge `home` → `main` cuando esté lista.

### 2026-05-24 — Abstracción y centralización de form controls _(Elsa)_

**Hechos**:

- Creado `src/scss/components/_form-controls.scss` con todas las clases reutilizables de formulario. Importado en `main.scss` tras `_button.scss`.
- Clases definidas:
  - `.udp-form-select` (dark, 48px) + `--slim` (40px) + `--dim` (gris) + `--light`
  - `.udp-form-input` (dark, 48px) + `--slim` + `--dim` + `--light` + `--search-icon` (lupa como bg-image)
  - `.udp-form-filterbar` — contenedor flex (`max-width: 1440px; padding: 0 40px; margin-inline: auto`) para barras de filtros de archivo. `__group` (320px) y `__group--search` (380px). Concursos lo sobreescribe con `padding: 20px 40px; border-bottom: 1px solid black`.
  - `.udp-form-search-group` — wrapper con botón lupa absoluto a la **izquierda** (`left: 0`, `padding-left: 48px` en el input). Modificador `--light` para tema claro.
- Eliminados bloques de grid layout `.udp-archive-filters {}` de `_noticias-archive.scss`, `_carreras-archive.scss`, `_concursos-archive.scss`.
- Renombrado `udp-archive-filters` → `udp-form-filterbar` (pasando por `udp-form-filtersgroup`) en los 5 PHP de filtros: noticias, eventos, carreras, concursos, calendario. JS selectors actualizados. Eliminado modificador muerto `--light` del form de concursos.
- Bootstrap `form-select`/`form-control` eliminados del home buscador PHP. Home usa `--dim` para color gris.
- Build: ✓ sin errores.

**URLs para verificar el resultado**:

- Home buscador: http://localhost:8888/udp/
- Noticias: http://localhost:8888/udp/noticias/
- Agenda/Eventos: http://localhost:8888/udp/agenda-udp/
- Calendario académico: http://localhost:8888/udp/calendario-academico/
- Carreras: http://localhost:8888/udp/pregrado-y-formacion-general/carreras/
- Concursos académicos: http://localhost:8888/udp/docentes/concursos-academicos/

### 2026-05-24 — Rediseño sección Home Eventos (S5) según Figma + taxonomía tipo-evento _(Elsa)_

**Hechos**:

- Reescrito `template-parts/home/section-eventos.php` según Figma 3706:20036: 2 cards destacadas (imagen 232px + cuerpo #232323, altura 250px) con footer (eyebrow Necto Mono + fecha+lugar + botón círculo flecha 48px). Lista de hasta 5 eventos: grid 3 cols (eyebrow 200px | título 1fr | fecha 200px), separadores `#3d3d3d`.
- Botón "Ver todos los eventos" pill outline `rgba(white, 0.7)`.
- Taxonomía `tipo-evento` creada (jerárquica = checkboxes, exclusiva CPT `agenda`). Términos: Charlas, Cátedras, Otras actividades, Exposiciones, Congresos (IDs 4309–4313).
- Los 3 puntos que renderizan el eyebrow de agenda leen ahora `tipo-evento`: `udp_card_data_from_agenda()` (cubre home S5 + archivo), `event-meta.php` (single).
- `post_tag` eliminado del array `taxonomies` del CPT `agenda` en mu-plugin (ya no aparece en el admin). Comentario corregido en `udp-cards.php`.
- Refactor SCSS: mixin `udp-media-placeholder($theme)` en `_mixins.scss` + clase standalone `.udp-media-placeholder` en `components/_media-placeholder.scss`. Elimina 5 bloques `repeating-linear-gradient` duplicados en `_card-evento`, `_card-mosaic` y `_block-people-list`.

### 2026-05-24 — Rediseño sección Home Noticias (S3) _(Elsa)_

**Hechos**:

- Rediseñada `template-parts/home/section-noticias.php` según Figma (node `3706:19949`):
  - 1 slide destacado: imagen 432×580px con overlay blanco 60%, badge "Destacado" (#FFE210, Necto Mono), fecha top-right, título serif 48px centrado + columna de texto Work Sans 30px + "Leer más ↗"
  - Slides regulares: pares de 2 cards (201×275px, imagen + título 18px + fecha + "Leer más ↗")
  - Botón "Ver todas las noticias" pill outline bottom-right (216×44px)
- Actualizado `_home.scss` bloque `.udp-home-noticias` con todas las dimensiones del Figma: swiper 580px, slide featured 694px, slide pair 463px, nav circular (#4539F2 / #2B2788)
- Actualizado `home-noticias.js`: nav apunta a `.js-noticias-prev/next` del header, spaceBetween: 30, sin freeMode
- Build: ✓ sin errores. Commit: `6131f7b`

### 2026-05-24 — Cierre de sesión _(Elsa)_

**Lo trabajado**:

- Centralización form controls en `_form-controls.scss`; renombrado `udp-archive-filters` → `udp-form-filterbar`
- Rediseño S3 Noticias según Figma
- Rediseño S5 Eventos según Figma 3706:20036 (cards imagen+cuerpo oscuro + lista 3 cols)
- Taxonomía `tipo-evento` (checkboxes, exclusiva agenda): 5 términos creados en BD
- Eyebrow de agenda unificado a `tipo-evento` en los 3 puntos: helper, single, archivo
- `post_tag` ocultado del admin de agenda (eliminado del CPT `taxonomies`)
- Refactor SCSS: mixin `udp-media-placeholder` + clase standalone, elimina gradientes duplicados

**Estado actual**:

- Rama activa: `home` (no mergeada a `main`).
- S3 y S5 home implementadas según Figma — pendiente revisar en navegador.
- Resto de secciones del home pendientes de revisar/ajustar contra Figma.

**Próximos pasos sugeridos**:

- Revisar en navegador S3 y S5 y ajustar si es necesario.
- Continuar revisión sección a sección del home contra Figma.
- Merge `home` → `main` cuando el home esté completo.

### 2026-05-24 (tarde) — Rediseño S6 Destacado azul + fix sistémico get_field() _(Elsa)_

**Hechos**:

- Rediseñada `template-parts/home/section-destacado.php` según Figma `3706:20079`:
  - Sin Bootstrap container — ancho completo.
  - Panel izquierdo `$brand-blue` (#4539F2), `flex: 1`, `min-height: 432px`, padding 40px.
  - Imagen derecha `flex: 0 0 432px`, `object-fit: cover`.
  - Título: serif, `font-weight: 300` (Light) por defecto; `strong/b` → 500 (Medium). Tamaño `clamp(2rem, 3.33vw, 3rem)`, `line-height: 1`.
  - CTA: texto + SVG flecha (arrow-up-right), Work Sans SemiBold 16px blanco.
  - Responsive `<lg`: flex-column, imagen `aspect-ratio: 16/9`.
- Bloque SCSS `.udp-home-destacado` completamente reescrito en `_home.scss`.

**Fix crítico — get_field() sin post_id en template parts**:

- Causa raíz: `get_template_part()` se ejecuta fuera del loop de WP. `get_the_ID()` devuelve vacío. `get_field()` sin segundo argumento no puede determinar qué post leer.
- El título S6 aparecía solo gracias al fallback hardcoded, no desde la BD. La descripción y todos los demás campos sin fallback no se renderizaban.
- Fix: `front-page.php` ahora pasa `['post_id' => get_option('page_on_front')]` como `$args` a los 11 templates via `get_template_part()`.
- Los 11 templates añaden `$post_id = $args['post_id'] ?? (int) get_option('page_on_front');` y pasan `$post_id` explícito a todos sus `get_field()`. Las llamadas con post_id propio (ej. `get_field('color', $fac)`) no fueron tocadas.

**Limpieza BD ACF**:

- Había 4 entradas duplicadas de `group_template_home` en `wp_posts`. ACF cargaba todos y `get_field()` entraba en conflicto.
- Eliminados IDs 55354, 55396, 55453 via `wp post delete --force`. Queda solo el 55509 (más reciente, 37 campos correctos).

**Commit**: `d314464`

### 2026-05-24 — Cierre de sesión _(Elsa)_

**Lo trabajado hoy (sesión completa)**:

- S3 Noticias: rediseño según Figma
- S5 Eventos: rediseño según Figma; taxonomía `tipo-evento`; eyebrow unificado
- S6 Destacado azul: rediseño según Figma; fix get_field() en todos los templates home

**Estado actual**:

- Rama activa: `home` (no mergeada a `main`).
- S6 implementada y funcionando con contenido real desde ACF.
- S3 y S5 implementadas — pendiente revisar en navegador.
- Resto de secciones del home pendientes de revisión visual contra Figma.

**Próximos pasos sugeridos**:

- Continuar revisión sección a sección del home contra Figma (S7–S11).
- Revisar S3 y S5 en navegador.
- Merge `home` → `main` cuando el home esté completo.

### 2026-05-24 — Rediseño sección Home Eventos (S5) según Figma 3706:20036 _(Elsa)_

**Hechos**:

- Reescrito `template-parts/home/section-eventos.php` — estructura nueva: 2 cards destacadas (imagen 232px fija + cuerpo oscuro #232323, altura 250px) + lista de hasta 5 eventos.
- Card: `display:flex`, imagen fija izquierda, cuerpo derecho con título arriba (Work Sans Medium 20px, height 150px overflow) + footer (eyebrow Necto Mono + fecha+lugar + botón círculo flecha 48px border #4f4f4f).
- Lista: filas `display:grid` 3 cols (eyebrow 200px | título 1fr | fecha 200px), separadas por `border-top: 1px solid #3d3d3d`.
- Botón "Ver todos los eventos" — pill outline `border: rgba(white, 0.7)`, 230×44px.
- Build: ✓ 591ms sin errores.

### 2026-05-24 — Fix S7 Vida Universitaria: colores y estilos _(Elsa)_

- `.udp-home-vida` añadido `background-color: $dark-1` y `padding-block: 80px` — la sección no tenía fondo oscuro.
- `__texto` corregido de `color: $gray-700` a `color: $white`.
- `__link` corregido de `color: $black` a `color: $white`; eliminado `border-bottom`; añadido `::after { content: '↗' }` para ícono flecha según Figma.
- Build: ✓ sin errores.

### 2026-05-24 — S7 Vida Universitaria: campo video con fallback a imagen _(Elsa)_

- Nuevo campo ACF `vida_video` (tipo File, mime mp4/webm) añadido al JSON `group_template_home.json` antes de `vida_imagen`.
- `vida_imagen` renombrado a "Imagen (fallback si no hay video)" con instrucción actualizada — para que el admin entienda la jerarquía.
- Template PHP: si hay `vida_video` → `<video autoplay muted loop playsinline>`; si no → `<img>` existente.
- SCSS: `__video` y `__imagen` comparten estilos (`object-fit: cover`, `border-radius: 2px`).
- Build: ✓ sin errores.

### 2026-05-24 — Cierre de sesión _(Elsa)_

- Resumen: fix completo de S7 Vida Universitaria — colores (fondo oscuro, texto blanco, links blancos con ↗) + nuevo campo video con fallback a imagen.
- Estado actual: sección S7 alineada con Figma, campo ACF listo para que el admin suba video.
- Próximos pasos: revisar el resto de secciones pendientes (S9–S11 según memory) contra Figma.

### 2026-05-24 — S8 Cultura UDP: refactor campos ACF + contenido _(Elsa)_

- `cu_contador` (number) → `cu_subtitulo` (text) en ACF JSON. Campo `cu_link` eliminado del repeater.
- Template PHP: `<a href>` reemplazado por `<div class="__row">` (sin links). Clases actualizadas: `__link` → `__row`, `__contador` → `__subtitulo`.
- SCSS: mismos renombres, `cursor: default` en `__row`.
- JS sin cambios (fade ya funcionaba sobre el `<li>`).
- Contenido cargado vía WP-CLI (post 55394): 6 ítems con subtítulos del Figma. Imagen asignada solo en Conferencias (ID 54829); las otras 5 categorías pendientes de imagen.

### 2026-05-24 — Cierre de sesión _(Elsa)_

- Resumen: S8 Cultura UDP alineada con Figma — campos ACF refactorizados, links eliminados, contenido de las 6 categorías cargado.
- Estado actual: sección funcional, hover fade operativo. Faltan imágenes para Publicaciones, Exposiciones, Documentales, Podcasts y Especiales.
- Próximos pasos: subir imágenes para los 5 ítems restantes desde el admin; revisar S3, S5, S6 contra Figma.

### 2026-05-24 — S8 Cultura UDP: fixes hover + subtítulo + placeholder _(Elsa)_

- **Bug hover CSS (BEM)**: `&.is-active &__nombre` dentro de `&__item` compilaba como doble BEM (`__item__nombre`). Movido un nivel arriba: `&__item.is-active &__nombre` funciona correctamente.
- **Subtítulo**: añadidos `font-family: $font-family-mono`, `text-transform: uppercase`, `letter-spacing: 0.05em`, `line-height: 1.25rem` — alineado con Figma (Necto Mono 14px).
- **Row**: `align-items: baseline` → `flex-end`; gap `12px` → `18px` (según Figma).
- **Media placeholder**: cuando no hay imagen el PHP renderiza `<div class="__placeholder js-cultura-img">` en lugar de `<img>`. SCSS: `__img` y `__placeholder` comparten fade styles; `__placeholder` usa `@include udp-media-placeholder()`.

### 2026-05-24 — Cierre de sesión (final) _(Elsa)_

- Resumen: S8 Cultura UDP completamente alineada con Figma. ACF refactorizado, hover naranja corregido, subtítulos con fuente mono correcta, placeholder con mixin para ítems sin imagen.
- Estado actual: build limpio, sección lista. Los 5 ítems sin imagen muestran tramado diagonal.
- Próximos pasos: subir imágenes para Publicaciones–Especiales desde el admin; revisar S3, S5, S6 contra Figma.

### 2026-05-25 — F9: migración de contenido viejo (`secciones`) al template Institucional _(Cacho)_

El usuario reportó que la página institucional "no aprovechaba las secciones que ya había". Diagnóstico: el template solo renderiza el campo ACF nuevo `sections` (no `the_content()` ni el campo viejo `secciones` del tema legacy). El contenido vivía en el campo viejo `secciones` (group "Secciones" ID 313, aún activo) + `post_content`.

**Hechos**:

- **Forma de Gobierno (ID 62)** ya estaba migrada (curada a mano) pero incompleta. Completado: la banda oscura `cards_dark_row` pasó de 1 a **3 cards** (se habían perdido _Consejos de Facultad y Escuela_ 564 y _Consejo Asesor Externo_ 43486 del viejo `links_cuadrados`), y se recuperó el link "Más información" → `/docentes/carrera-academica/` en la sección Carrera Académica. Cards sin imagen (solo título + URL), como pidió el usuario (estilo página Posgrado).
- **Migradas las 3 hijas de Forma de Gobierno** al template Institucional (decisión de alcance del usuario): Consejo Académico (558), Consejos de Facultad y Escuela (564), Consejo Asesor Externo Ampliado (43486). Script `/tmp/udp-migrate-institucional.php` (idempotente, --dry, lee old `secciones` vía `get_field`, borra meta `sections*` previa y reescribe con field keys + reference keys).
- **No se creó ningún layout nuevo** — los 3 Figma compartidos por el usuario confirmaron que los rosters de personas usan el `people_carousel` EXISTENTE (foto opcional + nombre + cargo), no un listado estático.

**Mapeo viejo → nuevo (layouts existentes)**:

- `desplegable` (acordeón: titulo/contenido/link_externo/titulo_de_link) → `rich_text_sidebar` (1 por item; si hay link → sidebar card con CTA).
- `contenido` (cabecera `<h3>…`) + `listado_de_informacion` siguiente → un `people_carousel` (title = h3, subtitle = resto del contenido; personas: nombre=titulo, cargo=subtitulo; **se descarta `descripcion`** y van sin foto).
- `contenido` suelto → `rich_text_sidebar`.
- `post_content` → `rich_text_sidebar` "Introducción" al inicio (con `wpautop`).
- `back_link` al final: `target` vacío (el partial resuelve al padre vía `wp_get_post_parent_id`), `link_text="Volver a {parent_title}"`, `display_in_anchors=0` (no aparece como chip).

**Verificación E2E** (curl `?theme=new`, HTTP 200):

- 558: 7 chips/rail, 3 rich_text, 3 people_carousel, **14 personas**, back_link "Volver a Forma de Gobierno", breadcrumb. Calza 1:1 con Figma `3722:43026`.
- 564: 3 chips, 2 rich_text, back_link. 43486: 12 chips, 11 rich_text (Introducción + 10 facultades), back_link.

**Cosas que descubrí**:

- En la BD hay **81 páginas** con el campo viejo `secciones` poblado. La mayoría (~30) son "especiales" (`page-template-expeciales.php`, carruseles/campañas) que NO mapean al template Institucional. El subconjunto institucional usa: `desplegable`, `contenido`, `links_cuadrados(_externos)`, `listado_de_informacion`, `atributos`.
- `get_field('secciones')` devuelve el wysiwyg ya formateado (wpautop aplicado); al reescribir en el campo nuevo, ACF re-aplica wpautop en output (idempotente para HTML bien formado).
- ACF `page_link` del `back_link`: dejar `target` vacío es lo más robusto — el partial cae al padre automáticamente.

**Pendientes**:

- **Premios Nacionales** (Figma `4394:22894`) y **Doctorado Honoris Causa** (Figma `4398:21365`) son archetipos DISTINTOS fuera de este alcance: Premios necesita un **layout nuevo** (bloque grande foto vertical + nombre + premio/año + 2 botones + bio larga, uno por laureado); Doctorado HC es rich_text con **acordeón dentro del body**. Construir cuando se aborden esas páginas.
- Subir **fotos** de los integrantes (Consejo Académico) desde el admin — el people_carousel renderiza sin foto por ahora.
- El group viejo "Secciones" (ID 313) sigue activo y visible en el editor de estas páginas; se conserva como backup. Desactivar/limpiar post-validación.
- Backups de meta de Forma de Gobierno en `/tmp/udp-fdg-backup.json`.

### 2026-05-25 — F9: 2 layouts nuevos (premio_block + text_accordion) + migración Premios/Distinciones _(Cacho)_

El usuario pidió construir los layouts faltantes y migrar las páginas que tuvieran datos. Referencias Figma: Consejo Académico `3722:43026` (rosters = people_carousel existente), Premios Nacionales `4394:22894` (bloque laureado), Doctorado Honoris Causa `4398:21365` (acordeón).

**Layouts nuevos añadidos al flex `sections` de group_page_institucional**:

- `premio_block` (Layout E): retrato izq + nombre (H2 Arizona Flare) + premio/año con border-bottom + 2 botones circulares (LinkedIn + globo/web) + biografía. Campos: `field_inst_pn_*` (anchor_label, anchor_icon, nombre, premio, imagen, linkedin, web, bio). Un bloque por laureado → cada uno es su propio anchor/chip (calza con el Figma de Premios).
- `text_accordion` (Layout F): 3-col (título / intro + acordeón / sidebar) con `<details><summary>` NATIVO (sin JS, accesible, chevron rota con `[open]`). Campos: `field_inst_acc_*` (anchor_label, anchor_icon, title, intro, items[titulo+contenido], sidebar_cards[title+body+cta]).
- Partials: `layout-premio-block.php`, `layout-text-accordion.php`. SCSS `.udp-inst-premio` + `.udp-inst-accordion` en `_institucional.scss`. Whitelist `$allowed` en `page-institucional.php` ampliado a 6 layouts.

**Migraciones** (script `/tmp/udp-migrate-premios-distinciones.php`):

- **Premios Nacionales (828)**: `listado_de_informacion` (5) → 5× `premio_block` (nombre=titulo, premio=subtitulo, bio=descripcion). Sin foto ni links (no hay data). + back_link al padre (46). E2E: 6 chips, 5 bloques, bios ~2500ch. ✓
- **Distinciones (831)**: 3× (`contenido` heading+intro + `desplegable`) → 3× `text_accordion` (Doctorado Honoris Causa 8 items, Profesor Emérito 15, Profesor Honorario 12 = 35 items). + back_link. E2E: 4 chips, 3 acordeones, 35 `<details>`. ✓ Calza con Figma Doctorado HC.

**⚠️ INCIDENTE Y LECCIÓN CRÍTICA (ACF + save_json/load_json)**:
Al intentar sincronizar el JSON con `acf_import_field_group()`, se generó un duplicado del field group (porque con local JSON `acf_get_field_group()` devuelve ID:0 y el import crea nuevo en vez de actualizar — ya documentado en F4a). Al limpiar el duplicado con `acf_delete_field_group()`, **ACF borró también el archivo `acf-json/group_page_institucional.json`** (hook de save_json on delete). Los re-imports siguientes hicieron `json_decode()` del archivo ya borrado → `null` → crearon grupos vacíos con keys aleatorias (`group_6a14...`). El grupo original se perdió de BD **y** de JSON.
**Recuperación**: reconstruí el field group COMPLETO en PHP (`/tmp/udp-acf-rebuild.php`, 3 layouts originales + 2 nuevos + back_link, basado en el contenido que ya había leído del JSON) e importé una sola vez → grupo limpio ID 55435, JSON regenerado por save_json. Datos de las páginas intactos (meta keys idénticos, solo se reconstruyeron las DEFINICIONES). Verificado: 62/558/564/43486 siguen renderizando.
**Reglas para el futuro**:

1. NUNCA usar `acf_delete_field_group()` sobre un grupo que tiene JSON local sin respaldar el archivo antes — borra el .json.
2. Para sincronizar JSON→BD evitando duplicados: borrar primero TODOS los grupos de BD con ese key (force delete + hijos recursivos), respaldar/recrear el .json, e importar UNA vez con un array válido (no leer un archivo que puede haber sido borrado en el mismo flujo).
3. El group_page_institucional ahora es ID 55435 (antes 55346).

**Pendientes**:

- Subir fotos: laureados de Premios (premio_block sin retrato → placeholder hatching) e integrantes de Consejo (people_carousel sin foto).
- Verificación visual en navegador (desktop/tablet/mobile) de premio_block y text_accordion — solo verificado por estructura HTML, no pixel-perfect.
- El group viejo "Secciones" (313) sigue activo como backup de los datos legacy.

### 2026-05-25 — F9 ajustes UI: quitar rail lateral + rediseñar share (Figma 3706:24477) _(Cacho)_

- **Rail vertical fijo izquierdo eliminado** (decisión del usuario): quitado el `get_template_part('nav-rail')` de `page-institucional.php` y el bloque SCSS `.udp-inst-rail`. La navegación queda solo en la **chips bar** superior (sticky). El partial `nav-rail.php` queda en disco pero sin uso. `anchor-scrollspy.js` sigue OK (`railLinks` resuelve a array vacío, solo sincroniza chips).
- **Botón compartir rediseñado** según Figma `3706:24477`: de trigger+dropdown+Web Share API → **píldora vertical blanca** (border #e7e7e7, rounded-full, padding 20/16, gap 16) con 5 iconos de acción directa: copiar enlace, email, Facebook, X, WhatsApp (sin LinkedIn). `share-floating.php` reescrito (5 `<a>/<button>` con SVG inline), `share-floating.js` simplificado (sin dropdown/native share; solo set de hrefs + clipboard con feedback `.is-copied`), SCSS `.udp-inst-share` reescrito. Sigue oculto <576px.
- E2E: 558 y 828 HTTP 200, 0 markup de rail, share con 5 botones, chips intactos. Build OK.

### 2026-05-25 — F9: render institucional por defecto en page.php (auto-transforma legacy) _(Cacho)_

Objetivo del usuario: que **todas las páginas que NO usan un template propio** rendericen con estilo institucional aprovechando sus secciones existentes, sin migración manual. Referencias Figma: Historia `3706:20493` y Accesos Internos `4418:26400` (hero morado + intro + acordeón + sidebar + back link).

**Clave de enrutado**: `page.php` es el fallback de WP para toda página sin template válido. Los "especiales" legacy tienen `_wp_page_template=page-template-expeciales.php` pero ese archivo NO existe en el tema → también caen a `page.php`. Así que **reescribir `page.php` cubre exactamente "todas las que no usan nuestros templates"**, sin `template_include` filter.

**Implementación (render-time, sin tocar datos)**:

- `inc/udp-institucional.php`:
  - `udp_institucional_sections_from_legacy($pid)`: transforma el campo viejo `secciones` + `post_content` al shape de `get_field('sections')`. Mapeo: `desplegable`→`text_accordion` (¡acordeón, como los Figma — distinto de la migración manual de las hijas de FdG que las separó en rich_text!); `contenido`+desplegable/listado/links → accordion/people_carousel/cards_dark_row; `contenido` suelto → rich_text; `listado`→people_carousel; `links_cuadrados(_externos)`→cards_dark_row; `post_content`→intro del primer acordeón (con título "Sobre {título}") o rich_text suelto; otros layouts legacy (carruseles, video, mozaico…) **se omiten**; + `back_link` al padre.
  - `udp_institucional_get_sections($pid)`: devuelve ACF `sections` si está poblado, si no el transformador legacy.
  - `collect_anchors($pid, $sections=null)`: ahora acepta el array de secciones (evita recomputar) y usa get_sections por defecto.
- `template-parts/institucional/article.php` (NUEVO): render compartido (hero+breadcrumb+chips+share+loop de layouts). Lo usan `page-institucional.php` (refactorizado a delegar) y `page.php`.
- `page.php` reescrito: si la página tiene `secciones` legacy → render institucional transformado; si es moderna (solo post_content) → hero institucional + `the_content()` nativo en `.udp-inst-plain`.
- Partials `rich_text_sidebar` y `text_accordion`: saltan el `<h2>` si el título está vacío.

**E2E** (`?theme=new`, HTTP 200):

- Historia (64): 1 acordeón (Autonomía/Plan de crecimiento/Fortalecimiento) + intro 491ch + "Sobre Historia" + share + volver. Calza con Figma `3706:20493`.
- Accesos Internos (107): 1 acordeón (4 items) + intro + volver. Calza con `4418:26400`.
- Consejo Directivo Superior (328, listado): → people_carousel. Consejo Académico (558, migrada con ACF): intacta (3 rts + 3 people). Revista Santiago (7051, moderna): hero + contenido nativo en `.udp-inst-plain`.

**Notas / pendientes**:

- Páginas "especiales" (campañas con `destacados_carrusel`, `video`, `mozaico`, `numeros_destacados`, etc.): esos layouts se omiten → muestran solo hero + lo mapeable + volver. Construir esos layouts si se quieren soportar.
- No portado de los Figma: widget "Noticias" lateral derecho (Design Historia) y banda "También te puede interesar" (relacionadas por hermanas). El back_link cubre el "volver a {padre}".
- Convención divergente intencional: la migración manual de las 3 hijas de FdG dejó `desplegable`→rich_text separados; el render por defecto usa `desplegable`→acordeón. Si se quiere unificar, re-migrar o borrar el campo `sections` de esas 3 (para que caigan al transformador).

### 2026-05-25 — F9: banda "También te puede interesar" (páginas hermanas) _(Cacho)_

Añadida la banda relacionada del Figma de Historia (`3706:20493`, nodo `3706:20540`): un carrusel de páginas hermanas al pie del render institucional.

- Layout `related` (auto-generado, no editable): partial `template-parts/institucional/layout-related.php` = encabezado "También te puede interesar" + **reutiliza** el sistema de cards + swiper de Section Landing (`section-landing-cards.php`, display=swiper). La card "Volver a {padre}" se antepone vía `parent_id` (variante back, ícono undo); las hermanas son cards default (ícono arrow-up-right). El swiper auto-inicializa (`initSectionLandingSwiper` ya cableado en main.js).
- El transformador legacy (`udp_institucional_sections_from_legacy`) ahora, en vez de un `back_link` fijo, añade: si la página tiene **hermanas** → sección `related` (incluye el "Volver" como primera card); si tiene padre pero **sin hermanas** → `back_link` simple. (Coincide con los dos Figma: Historia=banda, Accesos Internos con pocas hermanas=banda; páginas hoja=back link.)
- `related` añadido al whitelist de `article.php`. SCSS `.udp-inst-related` (banda oscura `$dark-1` + heading Arizona Flare blanco; el contenedor de cards se hace transparente para heredar el fondo).
- E2E: Historia (64) → banda con "Volver a Universidad" + 9 hermanas en swiper, sin back_link duplicado. Accesos Internos (107) → "Volver a Servicios" + 4 hermanas. Consejo Académico (558, migrada ACF) → intacta, conserva su back_link explícito, sin banda (el transformador no aplica a páginas con `sections` ACF).

**Pendiente**: para que las páginas migradas (page-institucional) también muestren la banda relacionada habría que añadir `related` como layout ACF o auto-inyectarlo en `article.php`. Hoy solo aparece en el render por defecto (page.php).

### 2026-05-25 — F9: widget "Noticias" en sidebar institucional (Figma 3706:20539) _(Cacho)_

Añadida la tarjeta de noticias del sidebar derecho del Figma de Historia.

- Helper `udp_institucional_latest_noticia()`: devuelve el post más reciente con featured image (title, url, image medium_large, date 'd / m / Y', category). Cacheado 1h en transient `udp_inst_latest_noticia` (array vacío = "no hay", para no re-consultar).
- Partial `template-parts/institucional/news-widget.php`: `<a>` envolvente (toda la tarjeta clickable) — eyebrow "Noticias" con ícono broadcast + imagen (aspect 318/196) + título (Arizona Flare 20px) + fecha + chip de categoría. SCSS `.udp-inst-news`: bg beige `#f8f7f4`, chip cerulean (`#dce5fd`/`#95b6fb`/`#2135d4`, Necto Mono uppercase) — colores literales del Figma (no están en la paleta). Hover: título azul + zoom imagen (respeta prefers-reduced-motion).
- Inyección: el transformador legacy marca `show_news=true` en la **primera** sección `rich_text_sidebar`/`text_accordion`; esos partials renderizan el widget al inicio de su `<aside>` (y ahora abren el aside aunque no haya sidebar_cards).
- E2E: Historia (64) y Accesos Internos (107) → widget con el post más reciente (imagen + título + fecha + categoría). Consejo Académico (558, migrada ACF) → sin widget (el transformador no aplica; `show_news` solo lo pone el render por defecto).

**Pendiente**: igual que la banda relacionada, el widget hoy solo aparece en el render por defecto (page.php). Para migradas habría que setear `show_news` o un sidebar_card especial en su ACF.

### 2026-05-25 — F9: layouts de "especiales" (carrusel, botones, números, video) _(Cacho)_

⚠️ **El Figma NO tiene mockups de los especiales** (campañas) — solo Home + las páginas institucionales. Estos layouts se hicieron **best-effort** con el lenguaje visual existente; pendiente confirmación visual del cliente.

Introspección del flex viejo `secciones` (field_60c09c7877e03): 18 layouts. Ya se manejaban 5 (desplegable, contenido, listado_de_informacion, links_cuadrados, links_cuadrados_externos). Añadidos 4 más al transformador (`udp_institucional_sections_from_legacy`), los más usados y self-contained (sin queries relationship):

- `destacados_carrusel` (3395 usos, el #1) → layout `featured_carousel`: carrusel scroll-snap CSS (sin JS) de cards imagen+título. Partial + SCSS nuevos (`.udp-inst-featured`).
- `botones_con_links_externos` (824) → layout `buttons`: **reusa** las clases del bloque F7 `.udp-block-big-buttons` (SCSS existente). url = link_externo o el archivo de descarga.
- `numeros_destacados` (225) → layout `stats`: banda oscura con cifras grandes (número en `$brand-yellow` Arizona Flare). Partial + SCSS nuevos (`.udp-inst-stats`).
- `video` (113, oembed) → layout `video`: **reusa** `.udp-block-embed` (SCSS existente); emite el HTML oembed.

Los 4 añadidos al `$allowed` de `article.php`. Como video/buttons emiten clases de bloques F7, no requirieron SCSS nuevo (esos `_block-*.scss` ya están importados en main.scss).

**E2E** (especiales publicados, page.php fallback):

- Ricardo Lagos (43079): 2 videos (iframe), carrusel 5 cards con imágenes+títulos reales, 2 botones, secciones de texto. ✓
- Aniversario (40919): 2 bandas de números (3°/1°/4400/10…), carrusel 8 cards. ✓
- Vargas Llosa (41303): video + carrusel + texto. ✓

**Layouts legacy DIFERIDOS** (no soportados aún — se omiten en el render):

- Nicho / nested repeaters: `links_en_tabs`, `directorio_de_redes_sociales`, `atributos`.
- `color_de_fondo` (color picker por sección) se ignora — los layouts usan los temas dark/light por defecto.

### 2026-05-25 — F9: especiales relationship-based (mozaico, destacados, páginas/eventos destacados) _(Cacho)_

Añadidos al transformador los layouts legacy basados en campo `relationship`, resolviéndolos a items del **`featured_carousel` existente** (sin partial/SCSS nuevos):

- Resolver `$featured_from_rel($value)`: mapea IDs/WP_Post (publicados) → `{titulo: post title, imagen: featured image large, link: permalink}`.
- `paginas_destacadas`, `destacados`, `mozaico` → leen el campo `destacados` (relationship) → `featured_carousel` (title = `titulo` de la sección si existe).
- `eventos_destacados` → lee el campo `eventos` (relationship) → `featured_carousel`.
- `link_ver_mas`/`texto_link_ver_mas` de mozaico se ignoran (sin CTA "ver más" por ahora).

**E2E**: Investigación en UDP (636) → 4 carruseles (3 destacados_carrusel + 1 mozaico), 57 cards (55 con imagen), títulos de sección reales (Entrevistas, Videos, Revistas Especializadas, Conoce más). Acreditación 2023 (33809) → 2 carruseles + banda de números. Facultad de Derecho (269) → paginas_destacadas + botones. Todos HTTP 200 con datos reales de los posts relacionados.

**Quedan sin soportar** (nicho): `links_en_tabs`, `directorio_de_redes_sociales`, `atributos`. Cubren poca superficie y/o necesitan diseño dedicado.

### 2026-05-25 — F9 fixes de revisión (especiales) _(Cacho)_

- **Título duplicado**: un `contenido` suelto se mapeaba a `rich_text_sidebar` con `title` = encabezado extraído Y `body` = HTML completo (que incluía el mismo encabezado) → el título salía 2 veces (columna izquierda + cuerpo). Fix: `body = $rest` (contenido sin el primer heading) en `udp_institucional_sections_from_legacy`.
- **Video con fondo oscuro**: `layout-video.php` usaba `.udp-block-embed--dark`; cambiado a `--light` (fondo blanco) por feedback del cliente.
- Nota: un `contenido` que solo es un encabezado (ej. "Galería de Imágenes" que precedía a un `galeria_de_imagenes` aún no soportado) queda como título suelto sin cuerpo — no es el bug de duplicación; se resolvería al soportar el layout `galeria_de_imagenes`.

### 2026-05-25 — F9: botones (botones_con_links_externos) al aside derecho _(Cacho)_

Por feedback del cliente, los botones dejan de ser una banda full-width y van al **aside derecho** de la primera sección de texto, como las tarjetas.

- Transformador: acumula los botones en `$pending_buttons` y los adjunta como `sidebar_buttons` a la primera sección `rich_text_sidebar`/`text_accordion`. Si la página no tiene sección de texto, fallback a la banda full-width `buttons` (layout-buttons.php sigue existiendo para ese caso).
- Partial nuevo `template-parts/institucional/sidebar-buttons.php`: pills verticales (label + ícono arrow-up-right, outline → azul en hover). Renderizado en el `<aside>` de rich_text/text_accordion (tras news widget y antes de las tarjetas).
- SCSS `.udp-inst-sidebar-btns` / `.udp-inst-sidebar-btn`.
- E2E: Ricardo Lagos (43079) → 2 botones en aside, 0 full-width. Facultad de Derecho (269) → 1 botón en aside + paginas_destacadas.

### 2026-05-25 — F9: soporte `galeria_de_imagenes` _(Cacho)_

El cliente notó que faltaban las imágenes (la galería estaba diferida). Añadido:

- Resolver `$gallery_from()` en el transformador: campo `gallery` (attachments/IDs) → `{url (size large), alt}`.
- `galeria_de_imagenes` → layout `gallery`. `contenido` + `galeria_de_imagenes` → gallery con el encabezado como título (consume el contenido, sin título huérfano ni duplicado).
- Partial `layout-gallery.php`: **reutiliza** las clases del bloque F7 `.udp-block-image-gallery--grid --light` (grid CSS, sin JS). `gallery` añadido al `$allowed`.
- E2E: Ricardo Lagos (43079) → grid de 4 imágenes con título "GALERÍA DE IMÁGENES" (1 vez).

**Layouts legacy que quedan sin soportar**: `links_en_tabs`, `directorio_de_redes_sociales`, `atributos`.

### 2026-05-25 — F9: `contenido` suelto a una sola columna (no 2-col) _(Cacho)_

Feedback del cliente: el `contenido` no debe ir con título en columna lateral (2-col), sino todo seguido en una columna.

- Transformador: el `contenido` suelto (rama else) deja de mapearse a `rich_text_sidebar` (3-col) y pasa a layout nuevo `rich_text` (una columna). El `body` lleva el contenido COMPLETO (con su encabezado inline, no se separa). `anchor_label` usa el encabezado solo si el contenido tenía un `<h1-6>` (para el chip), si no queda sin chip.
- Partial `layout-rich-text.php`: reutiliza `.udp-inst-plain` (columna legible centrada, sin SCSS nuevo). `rich_text` añadido al `$allowed`.
- Scope: solo afecta al render legacy (page.php). Las páginas migradas (page-institucional) siguen usando `rich_text_sidebar` 3-col con su sidebar a propósito (verificado: Consejo Académico mantiene 3 secciones 2-col).
- E2E: Ricardo Lagos (43079) → contenido en `.udp-inst-text` (1 col), 0 `.udp-inst-rts`.

### 2026-05-25 — F9: featured_carousel drag-to-scroll + scrollbar oculta _(Cacho)_

- SCSS `.udp-inst-featured__list`: `scrollbar-width:none` + `-ms-overflow-style:none` + `::-webkit-scrollbar{display:none}` (barra oculta). `cursor:grab`; `.is-dragging` → `grabbing` + `scroll-snap-type:none` + `user-select:none`.
- JS nuevo `src/js/modules/featured-drag.js` (cableado en main.js): drag-to-scroll con pointer events para mouse (touch usa scroll nativo). Si hubo arrastre (>4px), cancela el click para no disparar el enlace de la card. Selector `.udp-inst-featured__list`.
- **Permisos `dist/`**: el build falló porque `dist/js` quedó propiedad de `root` (sudo previo, ver F0/F3). Workaround sin sudo: `mv dist .dist-root-bak` (renombrar solo requiere permiso en el padre) y `npm run build` crea un `dist/` limpio del usuario. **Pendiente**: borrar `.dist-root-bak` con `sudo rm -rf .dist-root-bak` (root-owned, ambos gitignored).

### 2026-05-26 — PENDIENTE (próxima sesión): traer la Home de la otra dev a esta BD _(Cacho)_

**Contexto**: hay otra persona trabajando en el proyecto. Ella hizo **la Home** (front page); este usuario hizo **todo lo demás** (F9 institucional/especiales/migraciones). Ambas BDs son **locales**. Solo se quiere traer **la Home** de ella a esta base (sus otros cambios NO). No es un merge de BD completo — es traer una feature acotada. WordPress no fusiona BDs (los IDs autoincrementales chocan), así que el plan evita sobrescribir: solo **inserta** la home con remapeo de IDs.

**Estado de ESTA BD**: `show_on_front = posts`, `page_on_front = 0` → NO hay home configurada. Esta base solo **recibe** (no hay nada que se pise). `front-page.php` actual es el scaffold viejo (Bootstrap hero + flexible content leyendo ACF `hero_title`, etc.).

**Qué se le pidió a la otra dev (artefactos completos; nosotros extraemos solo la home)**:

- **A) Código**: commit+push de su rama (front-page.php + partials/SCSS/JS de la home + archivos `acf-json/` del/los field groups de la home). Si no hay remoto: zip del tema. `dist/` no (se regenera con `npm run build`). Los field groups ACF viajan por `acf-json/` (git) + _Custom Fields → Sync_, NO por BD.
- **B) Dump completo de su BD local**: `mysqldump ... udp | gzip > db-home-de-ella.sql.gz`. De aquí se extrae quirúrgicamente la home.
- **C) Sus `wp-content/uploads/`** (imágenes de la home), zip.
- **D) Datos**: cuál es la página Home (título/ID) y cómo la armó (página estática con flexible content ACF **vs** front-page.php + options page ACF) — define si la home está en `postmeta` de una página o en `wp_options`.

**Plan de ejecución (próxima sesión, cuando lleguen A+B+C+D)**:

1. Respaldar ESTA BD primero (`mysqldump` comprimido, patrón F0).
2. Merge del código por git + `npm run build` + sync ACF en admin.
3. Copiar sus `uploads/` sobre los nuestros (carpetas por fecha, rara vez chocan) ANTES de insertar contenido.
4. Del dump de ella, extraer e **insertar** en nuestra BD (con remapeo de IDs, sin sobrescribir): la página Home + su `postmeta` (valores ACF) + attachments + options de la home si usó options page.
5. Ajustes → Lectura → portada estática = Home importada (`show_on_front=page`, `page_on_front`).
6. Verificar `/` (home) + páginas institucionales/especiales.

**NO usar** WP Migrate DB (sobrescribe, no fusiona) ni editar IDs a mano en SQL (rompe relaciones).

### 2026-05-27 — Merge de la Home (de elsa_udp) COMPLETADO (parte BD)

La BD de la compañera está en el mismo servidor MySQL: **`elsa_udp`** (mismo prefijo `wp_fnku4y`, misma URL `localhost:8888/udp/cms`, mismo tema). Ambas son **forks del mismo WordPress**. La nuestra es **`udp`**.

**Diagnóstico**: en elsa la Home es página estática **ID 55394** ("Inicio", `_wp_page_template=default` → la renderiza `front-page.php`). Usa el field group nuevo **`group_template_home`** (~92 campos: portada/hero, destacado, postítulos, facultades, eventos, noticias, innovación, cultura UDP, cultura digital, cifras, vida universitaria, buscador). Referencia 13 adjuntos: **9 ya existían en udp** (mismo fork; solo difería el dominio del guid — no afecta) y **4 nuevos** (55565, 55577, 55578, 55579) cuyos **archivos ya estaban** en `uploads/2026/05`.

**Código del tema**: está en el branch **`origin/home`** (61 archivos: front-page.php nuevo, `template-parts/home/section-*.php`, `src/js/modules/home-*.js`, `_home.scss`, `acf-json/group_template_home.json`). El usuario lo **mergeó a main** (no tocar ramas).

**Hecho (solo BD `udp`, sin sobrescribir nada)**:
1. Backup: `~/Backups/udp/udp-pre-home-merge-20260527-124611.sql.gz` (25 MB). (mysqldump real está en `/Applications/MAMP/Library/bin/mysql80/bin/`, vía socket `/Applications/MAMP/tmp/mysql/mysql.sock` — la ruta `Library/bin/mysqldump` NO existe.)
2. Registrado `group_template_home` en BD udp vía `git show origin/home:acf-json/...json` + `acf_import_field_group` (ID 55495, sin duplicados). En este install los grupos ACF deben estar en BD (el JSON local solo no basta).
3. Copiados de elsa_udp→udp (IDs 55394 libre + 4 adjuntos libres; idempotente; guid de dominio capitanproject→localhost solo en wp_posts, NO en meta_value para no romper serializados): página 55394 +187 metas, 4 attachments +2 metas c/u.
4. `update_option(show_on_front=page, page_on_front=55394)` + cache flush.
5. Verificado: ACF resuelve (portada_titulo, cifras 3 filas, cultura_udp 6, portada_imagen→URL localhost válida).

**Pendiente**: el render solo se ve en **main** (front-page.php nuevo + partials + `_home.scss` + JS están en main, no en feature/f9-page-institucional cuyo front-page.php es el scaffold viejo). Estando en main: `npm run build` y ver `/`. Los datos ya están en la BD compartida (no dependen de la rama).

### 2026-05-26 — F9 fix: links_cuadrados con estilo de cards "te podría interesar" _(Cacho)_

Feedback: el módulo `cards_dark_row` de `links_cuadrados` se veía mal (cards solo-título, vacías, sobre fondo oscuro). El cliente quiere que se vean como las cards de "También te puede interesar" (Section Landing: gris → azul, ícono flecha, sin imagen).

- Transformador: `links_cuadrados`/`links_cuadrados_externos` ahora mapean a layout nuevo `link_cards` (antes `cards_dark_row`). Nuevo resolver `$linkcards_from()` → cards `{titulo, link}` (formato Section Landing). Maneja link_externo+titulo y link_relacionado (relationship → permalink/título).
- Partial `layout-link-cards.php`: reutiliza la banda `.udp-inst-related` + `section-landing-cards.php` (display swiper, `parent_id=0` → sin card "Volver"). Sin SCSS/JS nuevos (swiper ya cableado).
- `link_cards` añadido al `$allowed`. El layout `cards_dark_row` sigue existiendo para la página migrada Forma de Gobierno (ACF) — esa NO cambió (sus cards también son sin imagen; si el cliente quiere el mismo estilo ahí, habría que re-migrarla a link_cards o ajustar su layout).
- E2E: Autoridades (58) → 3 link cards (Consejo Directivo Superior / Dirección Superior / Decanatos) estilo Section Landing, 0 `udp-inst-dark__card`, sin back-card en ese módulo (el back-card solo en la banda "te podría interesar").

### 2026-05-26 — Botón de accesibilidad (plugin Pojo) — estilo UDP + posición _(Cacho)_

Rediseño del botón del plugin **Pojo / One Click Accessibility** (Figma 3706:21202): círculo amarillo `#fcd303` (gold-medium) + ícono oscuro + sombra `0 8px 5px rgba(0,0,0,.25)`, fijo bajo la línea del header, a la derecha (bajo el buscador).

- **Mecanismo del plugin** (`modules/legacy/assets/css/style.css`): `#pojo-a11y-toolbar` se desliza fuera de pantalla según su clase de lado: `.pojo-a11y-toolbar-right` va de `right:-180px` (colapsado) a `right:0` con `.pojo-a11y-toolbar-open` (la clase la togglea el JS). El toggle es `position:absolute` con offset 180px. NO hay que tocar `right/width/position/left` del contenedor o se rompe el colapso (lección: un primer intento con `width/right:auto !important` dejó el panel siempre abierto).
- **Posición lado**: estaba en `left` → cambiado a `right` vía el option `pojo_a11y_customizer_options.a11y_toolbar_position` (en BD). Es un ajuste del plugin (Customizer → Accesibilidad) — en **prod** hay que setearlo igual (o viajará con la BD).
- **Override del tema**: `src/scss/components/_a11y.scss` (import en main.scss). Solo `#pojo-a11y-toolbar { top:100px !important }` (bajo el header; mobile 88px) + reestilo del `.pojo-a11y-toolbar-toggle a` (círculo 50px amarillo, ícono dark, svg fill dark, sombra). El color rojo `#c23633` por defecto viene de un `<style>` inline de los ajustes del plugin (`a11y_toggle_button_bg_color`); el `!important` del tema lo gana.
- **Inset + top** (pedido del cliente): el toggle se separó 40px del borde (mismo margen que el buscador) subiendo su `right` de 180px → `calc(180px + $space-3xl)` = 220px (insetar el botón sin revelar el panel, que sigue colapsado en `right:-180px`). `top` subido a 124px desktop / 104px mobile (más abajo del header).
- **Permisos dist (recurrente)**: `dist/js`+`dist/css` se vuelven root tras cada build (causa no identificada; pasa repetido hoy). Workaround: `mv dist .dist-root-bak* && npm run build`. Acumuladas varias carpetas basura: `sudo rm -rf .dist-root-bak*`.

### 2026-05-26 — people_carousel: card full-bleed con foto (Figma 3904:54293) _(Cacho)_

El cliente pidió que los integrantes (people_carousel) puedan tener foto. El layout YA tenía el campo `foto` (field_inst_pc_persona_foto) y el partial lo renderiza — pero el diseño anterior era foto cuadrada arriba + texto debajo (dark sobre claro). El Figma real es **foto a sangre completa** (289×365) con **degradado oscuro** (negro 91% abajo → transparente arriba) y **nombre (Arizona Flare 20px) + cargo en blanco** sobre-impresos abajo-izquierda. Fondo fallback beige `#f8f7f4`.

- Reescrito SOLO el SCSS de `.udp-inst-people__card/__photo/__info` en `_institucional.scss` (el partial ya tenía la estructura correcta: `__photo` absolute fill + `__info` absolute bottom + `::after` degradado). Sin cambios de PHP.
- El degradado deja el texto legible aunque NO haya foto → sirve de placeholder (card beige con franja oscura abajo).
- **Datos**: ni el `listado_de_informacion` viejo ni el sitio live (capitanproject) tienen fotos — hay que subirlas en wp-admin: editar la página → grupo "Página Institucional" → Secciones → sección people_carousel → Personas → campo **Foto**. Hasta entonces las cards salen con placeholder + nombre/cargo.
- **Ajustes de revisión**: (1) placeholder sin foto → fondo **gris `#cfcfcf`** (en vez de beige) para que se sustituya al subir imagen. (2) Fix "muy grandes": con la estructura full-bleed, Swiper (`slidesPerView:'auto'`, lee ancho del CSS) dejaba ganar su `.swiper-slide{width:100%}` → cards al 100% + aspect-ratio = altura enorme. Fijado con `width:289px !important` + `flex-shrink:0` + `max-width:80vw` (responsive). Vuelve a 289×365.

### 2026-05-27 — Cierre de sesión _(Elsa)_

**Lo trabajado hoy**:

- Revertido el template `page-simple-accordion` (Historia) completo — el compañero lo implementa por su lado.
- Commit `dd7b828`: eliminados 6 archivos (template principal, 3 partials, SCSS, ACF JSON) + import en `main.scss`.
- Build limpio tras el borrado.

**Estado actual**:

- Rama `home` activa, sin el template de Historia. El resto del home (S1–S11) intacto.

**Pendiente**:

- Borrar manualmente la página de prueba ID 55353 "Historia (test simple-accordion)" desde WP Admin → Páginas (requiere MAMP corriendo).
- Continuar con los pendientes habituales: imágenes S8, contenido S9, revisar S3/S5/S6 contra Figma, F10 polish, merge home → main.
