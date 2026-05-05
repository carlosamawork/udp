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

### 2026-04-28 — Fix: Vite base path para subdir install (MAMP /udp/cms/)

**Síntoma**: Swiper no inicializaba. Consola mostraba 404 en chunks dinámicos (`swiper.xxx.js`, `utils.xxx.js`, `index.xxx.js`) y todas las fonts woff2. `main.js` sí cargaba.

**Raíz**: `vite.config.js` tenía `base: '/wp-content/themes/starter-theme/dist/'` hardcodeado. Pero MAMP local sirve WP en `http://localhost:8888/udp/cms/`, así que Vite reescribía URLs sin el prefijo `/udp/cms/` en build-time. `main.js` cargaba porque PHP lo encola con `STARTER_BS5_URI` (URL completa de WP), pero dynamic imports y `url()` en CSS quedaban con el path equivocado.

**Fix**: `vite.config.js` ahora usa `loadEnv()` y lee `VITE_BASE_PATH` desde `.env.local`, con fallback al default de raíz para producción. Creado `.env.local` (ya gitignored) con `VITE_BASE_PATH=/udp/cms/wp-content/themes/starter-theme/dist/`.

**Side note**: `dist/` quedó owned by `root:staff` por algún `sudo npm` previo — `npm run build` falló con EACCES hasta resolver con `sudo chown -R 501:20 dist`. Mismo patrón que el bug de `~/.npm` documentado en F0.

**Verificación**: Curl 200 en chunks + fonts; `grep` confirma path `/udp/cms/...` baked en `main.DY8Fqxyc.js` y `main.BurZMkSV.css`.

### 2026-04-28 — F3 Section Landing — Refactor v2 (sin imagen + breadcrumb + back-card)

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

### 2026-04-28 — F4a `block_card_grid` + helpers

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
- **CRÍTICO (naming)**: `page-flexible.php` llama `get_template_part('template-parts/blocks/block', $layout)` donde `$layout = 'block_card_grid'`. WordPress busca `block-block_card_grid.php` (slug=`block`, name=`block_card_grid`). El archivo se nombró originalmente `block-card_grid.php` (sin el prefijo `block_`), lo que hacía que la sección fuera silenciosamente vacía (have_rows devolvía datos correctamente en WP-CLI pero el template part no se cargaba vía HTTP). Renombrado a `block-block_card_grid.php`. Regla: el layout name en ACF debe matchear exactamente la porción `{name}` del call, o bien el layout name debe ser sin prefijo `block_` si el slug del template_part es `block`.
- Seeding de flex content vía `update_field()` con array anidado solo guarda el layout key, no los sub-fields. Para seed correcto: usar `update_post_meta()` directamente con la estructura de claves `content_blocks_0_titulo`, `content_blocks_0_cards_manuales`, `content_blocks_0_cards_manuales_0_eyebrow`, etc. + los correspondientes `_` reference keys.
- `grep -cE "udp-card-noticia "` (con espacio al final) falla cuando el HTML tiene la class en su propia línea indentada. Usar `grep -cE "udp-card-noticia"` (sin espacio).

**Pendientes**:
- F4b: archive-post + single-post (Noticias). Reusará `udp_query_cards()` con `paged` + `need_pagination=true` y `udp_card_data_from_post()` para markup directo (sin pasar por el bloque).
- F4c: archive-agenda (toggle grid/list) + single-agenda + nuevo card primitive `card-evento.php` (image-izquierda + CTA circular).
- Cleanup de scaffold legacy (`block-cards_grid.php`, `_flexible-blocks.scss`) cuando todos los bloques UDP estén migrados.
- El group PHP `group_flexible_blocks` en `inc/acf-setup.php` tiene mismo field name `content_blocks` que el nuevo JSON group — coexisten sin conflicto (ambos aplican al mismo template) pero el PHP group está vacío (no tiene `block_card_grid` layout). Limpiar en F11.

### 2026-04-28 — F4b Task 2: Card `--horizontal` modifier

**Hechos**:
- `card-noticia.php`: docblock actualizado (`@var array $args` documenta nuevo arg `variant`).
- `card-noticia.php`: variable `$variant` sanitizada (whitelist `['horizontal']`), clase BEM `udp-card-noticia--horizontal` condicionalmente añadida.
- `_card-grid.scss`: bloque `.udp-card-noticia--horizontal` añadido al final del archivo. Diseño: `flex-direction: row`, gap 30px (Figma spec), imagen fija 201×275px. Mobile (`<md`): cae a `flex-direction: column`, imagen 16/9 full-width.
- PHP lint: sin errores. Build: OK en 1.22s.

**Decisión**:
- El modifier es selector de nivel raíz (`.udp-card-noticia--horizontal { ... }`) en lugar de anidado dentro de `.udp-card-noticia { ... }` para evitar especificidad conflictiva con overrides de layout (`.udp-card-grid--list &` y similares).

**Pendiente**:
- Task 3 de F4b: archivo `archive-post.php` que pase `variant=>'horizontal'` al partial.

### 2026-04-28 — F4b1 Noticias archive (simple) + single-post

**Hechos**:
- `templates/page-noticias.php` asignado manualmente a la página "Noticias" (ID 97). Archive con filtros (categoría + año + búsqueda via $_GET) + grid 2-col × 3 filas (6 cards/page) + paginación. Light theme.
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

### 2026-04-28 — F4b2 Tasks 4+5: Hero partial + SCSS featured + dark theme

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

### 2026-04-28 — F4b2 Task 6: page-noticias.php logic update (page 1 vs 2+, theme=dark)

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

### 2026-04-28 — F4b2 Noticias hero band + tema dark fix

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

### 2026-04-28 — F4c Task 2: ICS calendar endpoint

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

### 2026-04-28 — Bugfix: udp_card_data_from_agenda fecha_display vacío

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

### 2026-04-28 — F4c Agenda archive (grid + list) + single-evento

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

### 2026-04-28 — F4 extras: image gallery en single-post

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

### 2026-04-28 — Bugfix F4b1/F4c: prefijo udp_ en params + agenda >= hoy

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

### 2026-04-28 — Fix: featured image opcional en card-evento grid + placeholder

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

### 2026-04-29 — F5a Calendario Académico archive

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

### 2026-04-29 — F5b Concursos académicos archive + single

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

### 2026-04-29 — F5c Active-month tracking en Calendario sidebar

**Hechos**:
- JS module `src/js/modules/calendario-active-month.js`: IntersectionObserver que marca el link del mes correspondiente con clase `udp-calendario-sidebar__month-link--active` cuando esa sección está visible. Si hay múltiples secciones visibles, la topmost (más arriba en viewport) gana.
- `rootMargin: '-120px 0px -50% 0px'` ancla el "punto activo" justo bajo el sticky header.
- Wire en `src/js/main.js`. SCSS modifier `--active` con color `$brand-yellow` y font-weight 600.

**Decisiones clave**:
- IntersectionObserver vs scroll listener: el observer es más performante (rAF interno) y no requiere debounce.
- Topmost-wins logic: cuando dos meses son visibles a la vez (transition entre Enero y Febrero), el de arriba mantiene el active hasta que el de abajo cruza el rootMargin top.

### 2026-04-29 — F5d block_calendario_grid (flex content)

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

### 2026-04-29 — F6a Facultades landing + mosaic primitive

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

### 2026-04-29 — F6b Carreras archive + single

**Hechos**:
- `templates/page-carreras.php` asignado a página "Carreras" (ID 12). Theme dark, mosaico 5-col reusando `card-mosaic` con eyebrow facultad. Filtros legacy: facultad dropdown + udp_s.
- Helpers en `inc/udp-cards.php`: `udp_query_carreras` (no pagina, todos los 42 a la vez ASC por título) + `udp_card_data_from_carrera` (eyebrow facultad, href = link_directo target=_blank si existe sino permalink).
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

### 2026-04-29 — F6c Centros archive + single

**Hechos**:
- `templates/page-centros.php` asignado a página "Centros Interdisciplinarios" (ID 16). Theme dark, mosaico 5-col reusando `card-mosaic` SIN eyebrow ni filtros.
- Helpers en `inc/udp-cards.php`: `udp_query_centros` (no filtros, sort por title ASC) + `udp_card_data_from_centro` (href = link_externo target=_blank si existe sino permalink).
- `single-centro-udp.php`: light theme simple (sin sidebar 2-col). Featured + post_content + button "Visitar sitio del centro" (target=_blank con link_externo si existe). Reusa post-share.
- 2 SCSS nuevos: `_centros-archive.scss` (dark, idéntico al de facultades pero sin filtros) y `_centros-single.scss` (light single-column con featured + content + button).

**Decisiones clave**:
- No eyebrow en cards de centros — visualmente igual a facultades. La taxonomía facultad podría usarse como eyebrow pero se prefiere la simpleza del Figma de facultades.
- Single layout 1-col centrado (no 2-col como carrera) — los centros tienen menos data estructurada (solo link_externo).
- Card mosaic primitive de F6a se reutiliza sin modificar — ningún cambio retroactivo.

**Pendientes**:
- F6 cerrado (a + b + c). F6 extras (`block_facultades_mosaic` flex content) sigue diferido.
- Algunos centros pueden no tener featured image — el card mostrará placeholder hatching (consistente con facultades).

---

### 2026-04-29 — F7a 3 bloques simples (huincha + embed + big_buttons)

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
