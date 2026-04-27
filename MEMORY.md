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
