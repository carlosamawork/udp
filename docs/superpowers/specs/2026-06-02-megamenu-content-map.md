# Spec: Mapeo de contenido del mega-menú UDP

**Fecha de exportación:** 2026-06-02  
**Fuente:** BD `capitanproject_comudp`, tabla `wp_fnku4yoptions`, claves `options_menu_principal_*`  
**Cómo re-importar:** ver sección "Script de re-importación" al final.

---

## Estructura del mega-menú

3 niveles:

- **Col 1** — secciones principales (8 en total): `titulo_main_link`
- **Col 2** — apartados de cada sección: `submenu[]`
  - `tipo`: `interno` | `externo` | `sin_link`
  - `interno` → `pagina` (WP post ID) + `anchor` (opcional, sin `#`)
  - `externo` → `url` + `new_tab_check` (0/1)
  - `sin_link` → solo texto, sin href
- **Col 3** — sub-items del apartado en hover: `sub_items[]`
  - `tipo`: `interno` | `externo`
  - `interno` → `pagina` (ID) + `anchor` + `titulo_alt` (sobreescribe el título de la página si se completa)
  - `externo` → `titulo` + `url` + `nueva_pestana` (0/1)

---

## S0 — Pregrado

| Apartado (col 2) | Tipo | URL / Página |
|---|---|---|
| Carreras | interno | ID 12 (`/carreras/`) |
| Facultades | interno | ID 14 (`/facultades/`) |
| Estudios Generales | externo | https://estudiosgenerales.udp.cl/ |
| Educación en Línea | externo | https://educacionenlinea.udp.cl/ |
| Vida Universitaria | interno | ID 20 (`/vida-universitaria/`) |
| Vértice UDP | externo | https://vertice.udp.cl/ |

### S0 · Carreras → sub-items (col 3)

| Ítem | Tipo | URL / Página | Anchor |
|---|---|---|---|
| Buscador de carreras | interno | ID 55394 (Home) | `#buscador-carreras` |
| Vías de admisión | externo | https://admision.udp.cl/vias-de-admision/ | — |
| Becas y beneficios | externo | https://admision.udp.cl/becas-y-beneficios/ | — |
| Matrícula y aranceles | externo | https://admision.udp.cl/aranceles-y-matricula/ | — |

### S0 · Facultades → sub-items (col 3)

| Ítem | Tipo | Página ID | Nombre |
|---|---|---|---|
| Facultad de Administración y Economía | interno | 354 | — |
| Facultad de Arquitectura, Arte y Diseño | interno | 263 | — |
| Facultad de Ciencias Sociales y Humanidades | interno | 265 | — |
| Facultad de Comunicación y Letras | interno | 267 | — |
| Facultad de Derecho | interno | 269 | — |
| Facultad de Educación | interno | 271 | — |
| Facultad de Ingeniería y Ciencias | interno | 505 | — |
| Facultad de Medicina | interno | 273 | — |
| Facultad de Psicología | interno | 508 | — |
| Facultad de Salud y Odontología | interno | 511 | — |

### S0 · Estudios Generales → sub-items (col 3)

| Ítem | Tipo | URL |
|---|---|---|
| Programa de formación general | externo | https://estudiosgenerales.udp.cl/programas-y-oferta-semestral/programa-de-formacion-general/ |
| Programa de inglés general | externo | https://estudiosgenerales.udp.cl/programas-y-oferta-semestral/programa-de-ingles-general/ |
| Programa de diplomas de honor | externo | https://estudiosgenerales.udp.cl/programas-y-oferta-semestral/programa-de-diplomas-de-honor/ |

### S0 · Vida Universitaria → sub-items (col 3)

| Ítem | Tipo | URL / Página |
|---|---|---|
| Conoce el campus | interno | ID 743 |
| Deportes y Actividad Física | externo | https://estudiantes.udp.cl/vida-universitaria/deportes-y-actividad-fisica/ |
| Participación y organización estudiantil | externo | https://estudiantes.udp.cl/vida-universitaria/participacion-y-organizacion-estudiantil/ |
| Arte y cultura estudiantil | externo | https://estudiantes.udp.cl/vida-universitaria/arte-y-cultura-estudiantil/ |
| Vida Saludable | externo | https://estudiantes.udp.cl/vida-universitaria/vida-saludable/ |

---

## S1 — Posgrado

| Apartado (col 2) | Tipo | URL |
|---|---|---|
| Doctorados | externo | https://doctorados.udp.cl/ |
| Magísteres y Educación Continua | externo | https://posgrados.udp.cl/grupo/educacion-continua/ |
| Buscar por área de estudio | externo | https://posgrados.udp.cl/ |
| Buscar por tipo de programa | sin_link (solo trigger col 3) | — |
| Buscar por Facultad | sin_link (solo trigger col 3) | — |
| OTEC | externo | https://posgrados.udp.cl/tipo/otec/ |
| Convenios | externo | https://posgrados.udp.cl/convenios/ |

### S1 · Buscar por tipo de programa → sub-items (col 3)

| Ítem | Tipo | URL |
|---|---|---|
| Doctorados | externo | https://doctorados.udp.cl/ |
| Magíster | externo | https://posgrados.udp.cl/tipo/magister/ |
| Especialidades médicas | externo | https://posgrados.udp.cl/tipo/especialidades/ |
| Especialidades odontológicas | externo | https://posgrados.udp.cl/tipo/especialidades-odontologicas/ |
| Postítulos | externo | https://posgrados.udp.cl/tipo/postitulos/?grupo=educacion-continua |
| Estadías medicina | externo | https://posgrados.udp.cl/tipo/estadias-medicina/ |
| Estadías psicología | externo | https://posgrados.udp.cl/tipo/estadias-psicologia/ |
| Diplomados | externo | https://posgrados.udp.cl/tipo/diplomados/?grupo=educacion-continua |
| Cursos | externo | https://posgrados.udp.cl/tipo/cursos/?grupo=educacion-continua |
| Escuelas de Verano | externo | https://posgrados.udp.cl/tipo/escuela-de-verano/ |

### S1 · Buscar por Facultad → sub-items (col 3)

| Ítem | Tipo | URL |
|---|---|---|
| Facultad de Administración y Economía | externo | https://posgrados.udp.cl/facultad/administracion-economia/ |
| Facultad de Arquitectura, Arte y Diseño | externo | https://posgrados.udp.cl/facultad/facultad-de-arquitectura-arte-diseno/ |
| Facultad de Ciencias Sociales y Humanidades | externo | https://postgradosfcsh.udp.cl/ |
| Facultad de Comunicación y Letras | externo | https://posgrados.udp.cl/facultad/comunicacion-y-letras/ |
| Facultad de Derecho | externo | https://posgrados.udp.cl/facultad/derecho/ |
| Facultad de Educación | externo | https://posgrados.udp.cl/facultad/educacion/ |
| Facultad de Ingeniería y Ciencias | externo | https://posgrados.udp.cl/facultad/ingenieria-ciencias/ |
| Facultad de Medicina | externo | https://posgrados.udp.cl/facultad/medicina/ |
| Facultad de Psicología | externo | https://posgrados.udp.cl/facultad/psicologia/ |
| Facultad de Salud y Odontología | externo | https://posgrados.udp.cl/facultad/salud-odontologia/ |

---

## S2 — Admisión

| Apartado (col 2) | Tipo | URL |
|---|---|---|
| Universidad | externo | https://admision.udp.cl/universidad/ |
| Carreras | externo | https://admision.udp.cl/carreras/ |
| Experiencia UDP | externo | https://admision.udp.cl/experiencia-udp/ |
| Vías de Admisión | externo | https://admision.udp.cl/vias-de-admision/ |
| Aranceles y Matrícula | externo | https://admision.udp.cl/aranceles-y-matricula/ |
| Becas y Beneficios | externo | https://admision.udp.cl/becas-y-beneficios/ |

_Sin sub-items._

---

## S3 — Investigación e Innovación

| Apartado (col 2) | Tipo | URL |
|---|---|---|
| Vicerrectoría de Investigación e Innovación | externo | https://investigacioneinnovacion.udp.cl/vicerrectoria/vicerrectoria-de-investigacion-e-innovacion/ |
| Dirección General de Investigación y Doctorados | externo | https://investigacioneinnovacion.udp.cl/investigacion-udp/direccion-general-de-investigacion-y-doctorados/ |
| Dirección de Innovación | externo | https://investigacioneinnovacion.udp.cl/innovacion/direccion-de-innovacion/ |
| Doctorados | externo | https://investigacioneinnovacion.udp.cl/investigacion-udp/programas-de-doctorado/ |
| Convocatorias | externo | https://investigacioneinnovacion.udp.cl/convocatorias-udp/calendario-de-convocatorias/?estado_de_convocatorias=convocatoria-abierta |
| Proyectos de investigación | externo | https://investigacioneinnovacion.udp.cl/investigacion-udp/proyectos-destacados/ |
| Revistas especializadas | externo | https://investigacioneinnovacion.udp.cl/investigacion-udp/revistas-especializadas/ |
| Comité de Ética | externo | https://investigacioneinnovacion.udp.cl/vicerrectoria/comites/comite-de-etica/ |

_Sin sub-items._

---

## S4 — Vinculación con el Medio

| Apartado (col 2) | Tipo | URL |
|---|---|---|
| ¿Qué es vinculación con el medio? | externo | https://vinculacionconelmedio.udp.cl/ |
| Red Alumni | externo | https://redalumni.udp.cl/ |
| Políticas Públicas | externo | https://politicaspublicas.udp.cl/ |
| Democracia UDP | externo | https://democracia.udp.cl/ |
| UDP Verde | externo | https://udpverde.udp.cl/ |
| Debate UDP | externo | https://debate.udp.cl/ |

### S4 · Red Alumni → sub-items (col 3)

| Ítem | Tipo | URL |
|---|---|---|
| Bolsa de empleo | externo | https://redalumni.udp.cl/trabajo/trabajos-en-chile |
| Credencial Alumni UDP | externo | https://redalumni.udp.cl/page/credencial |
| Post UDP | externo | https://redalumni.udp.cl/misc/postudp |
| Emprendimientos | externo | https://redalumni.udp.cl/page/innova |
| Premio Alumni Destacado | externo | https://redalumni.udp.cl/misc/premio-alumni |

---

## S5 — Cultura

| Apartado (col 2) | Tipo | URL |
|---|---|---|
| Cultura UDP | externo (nueva pestaña) | https://cultura.udp.cl/ |
| Cultura Digital | externo | https://culturadigital.udp.cl/ |
| Cenfoto | externo | https://cenfoto.udp.cl/ |
| Programa Archivos | externo | https://archivos.udp.cl/ |
| Centro para las Humanidades | externo | https://centroparalashumanidades.udp.cl/ |
| Revista Santiago | externo | https://revistasantiago.cl/ |
| Ediciones UDP | externo | https://ediciones.udp.cl/ |

_Sin sub-items._

---

## S6 — Universidad

| Apartado (col 2) | Tipo | URL / Página |
|---|---|---|
| Conoce la UDP | externo (trigger col 3) | https://estudiantes.udp.cl/vida-universitaria/conoce-el-campus/ |
| Gobernanza y reglamentos | externo (trigger col 3) | https://estudiantes.udp.cl/formacion-academica/reglamentos-y-politicas/ |
| Premios y distinciones | interno (trigger col 3) | ID 46 (`/premios-y-distinciones/`) |
| Estructura organizacional | externo (trigger col 3) | https://udptransparente.udp.cl/organigrama/ |
| Calendario Académico | interno | ID 74 (`/calendario-academico/`) |
| Vértice UDP | externo | https://vertice.udp.cl/ |

### S6 · Conoce la UDP → sub-items (col 3)

| Ítem | Tipo | Página ID | Anchor | Título alt |
|---|---|---|---|---|
| Historia | interno | 64 | — | — |
| Modelo Educativo | interno | 50 | — | — |
| Acreditación | interno | 52 | — | — |
| Anuarios UDP | interno | 7081 | — | — |
| Planificación Estratégica | externo | — | — | https://planificacionestrategica.udp.cl/ |
| Infraestructura | interno | 54 | — | — |

### S6 · Gobernanza y reglamentos → sub-items (col 3)

| Ítem | Tipo | URL / Página |
|---|---|---|
| Forma de Gobierno | interno | ID 62 |
| Autoridades | interno | ID 58 |
| Reglamentos y Políticas | interno | ID 66 |
| UDP Transparente | externo | https://udptransparente.udp.cl/ |
| Modelo de prevención de delitos | externo | https://www.udp.cl/servicios/modelo-de-prevencion-de-delitos/ |
| Canal de Denuncias | externo | https://denuncias.udp.cl/ |

### S6 · Premios y distinciones → sub-items (col 3)

| Ítem | Tipo | Página ID | Anchor |
|---|---|---|---|
| Premios nacionales en la UDP | interno | 828 | — |
| Doctorado Honoris Causa | interno | 831 | `#section-doctorado-honoris-causa` |
| Profesora o Profesor Emérito | interno | 831 | `#section-profesora-o-profesor-emerito` |
| Profesora o Profesor Honorario | interno | 831 | `#section-profesora-o-profesor-honorario` |

### S6 · Estructura organizacional → sub-items (col 3)

| Ítem | Tipo | URL |
|---|---|---|
| Vicerrectoría Académica | externo | https://vra.udp.cl/ |
| Dirección Aseguramiento de la Calidad | externo | https://calidad.udp.cl/ |
| Dirección de Asuntos Estudiantiles | externo | https://dae.udp.cl/ |
| Dirección de Género | externo | https://genero.udp.cl/ |
| Contraloría | externo | https://contraloria.udp.cl/ |
| Sistema de Bibliotecas | externo | https://bibliotecas.udp.cl/ |
| UDP Inclusiva | externo | https://inclusiva.udp.cl/ |
| Dirección de Finanzas y Presupuesto | externo | https://www.udp.cl/area-udp/direccion-gral-de-finanzas-y-presupuestos/ ⚠️ URL provisional |
| Inteligencia Artificial en la UDP | externo | https://ia.udp.cl/ |

---

## S7 — Internacional

| Apartado (col 2) | Tipo | URL |
|---|---|---|
| Política de internacionalización | externo | https://internacional.udp.cl/nosotros/politica-de-internacionalizacion/ |
| Movilidad internacional | externo | https://internacional.udp.cl/movilidad-internacional/ |
| Formación Global | externo | https://internacional.udp.cl/formacion-global/ |
| Convenios internacionales | externo | https://internacional.udp.cl/cooperacion-internacional/convenios-internacionales/ |
| American Corner | externo | https://americancorner.udp.cl/ |
| Writing Center | externo | https://writingcenter.udp.cl/ |

_Sin sub-items._

---

## Quick Links (footer del mega-menú)

> **Pendiente:** aún no están configurados desde el admin (Opciones → Header & Mega-menú → Quick Links).  
> Los items esperados son: Bibliotecas, Estudiantes, Alumni, Servicios, UDP University.

---

## Pendientes / items con URL no confirmada

| Ítem | Ubicación | Estado |
|---|---|---|
| Quick Links footer | Admin → Header & Mega-menú | ⏳ Poblar desde admin |
| Dirección de Finanzas y Presupuesto | S6 > Estructura organizacional | ⚠️ URL provisional |
| Rankings | Eliminado de S6 > Conoce la UDP | ❌ No hay página dedicada |
| Proyectos institucionales | Eliminado de S3 | ❌ Sin URL |
| Centros y unidades de investigación | Eliminado de S3 | ❌ Sin URL |

---

## Notas técnicas

### Cómo funciona el almacenamiento en BD

Los datos viven en `wp_options` con prefijo `options_` (es una options page de ACF):

```
options_menu_principal          = 8  (total de secciones)
options_menu_principal_0_titulo_main_link = "Pregrado"
options_menu_principal_0_submenu         = 6  (total de apartados)
options_menu_principal_0_submenu_0_titulo    = "Carreras"
options_menu_principal_0_submenu_0_tipo      = "interno"
options_menu_principal_0_submenu_0_pagina    = 12
options_menu_principal_0_submenu_0_sub_items = 4
options_menu_principal_0_submenu_0_sub_items_0_tipo  = "interno"
options_menu_principal_0_submenu_0_sub_items_0_pagina = 55394
options_menu_principal_0_submenu_0_sub_items_0_anchor = "#buscador-carreras"
...
```

**CRÍTICO:** Las opciones de options pages de ACF llevan siempre el prefijo `options_`. Escribir sin ese prefijo no afecta lo que el admin lee.

### IDs de páginas internas referenciadas

| ID | Título | URL local |
|---|---|---|
| 12 | Carreras | `/carreras/` |
| 14 | Facultades | `/facultades/` |
| 20 | Vida Universitaria | `/vida-universitaria/` |
| 46 | Premios y distinciones | `/premios-y-distinciones/` |
| 50 | Modelo Educativo | `/universidad/modelo-educativo/` |
| 52 | Acreditación | `/universidad/acreditacion/` |
| 54 | Infraestructura | `/universidad/infraestructura/` |
| 58 | Autoridades | `/universidad/autoridades/` |
| 62 | Forma de Gobierno | `/universidad/forma-de-gobierno/` |
| 64 | Historia | `/universidad/historia/` |
| 66 | Reglamentos y Políticas | `/universidad/reglamentos-y-politicas/` |
| 74 | Calendario Académico | `/calendario-academico/` |
| 263 | Facultad de Arquitectura, Arte y Diseño | `/facultades/arquitectura/` |
| 265 | Facultad de Ciencias Sociales y Humanidades | — |
| 267 | Facultad de Comunicación y Letras | — |
| 269 | Facultad de Derecho | — |
| 271 | Facultad de Educación | — |
| 273 | Facultad de Medicina | — |
| 354 | Facultad de Administración y Economía | — |
| 505 | Facultad de Ingeniería y Ciencias | — |
| 508 | Facultad de Psicología | — |
| 511 | Facultad de Salud y Odontología | — |
| 743 | Conoce el campus | — |
| 828 | Premios nacionales en la UDP | — |
| 831 | Distinciones | — |
| 7081 | Anuarios UDP | — |
| 55394 | Home (front-page) | `/` |

> Nota: los IDs son de la BD LOCAL. En producción (staging/prod) los IDs de las páginas serán distintos — los sub-items de tipo `interno` deben re-importarse con los IDs correspondientes de cada entorno.

### Script de re-importación

Si se pierden los datos del mega-menú, existe el script base en `/tmp/udp-populate-megamenu.php` (2026-06-02). Para re-importar desde este spec:

1. Crear nuevo script PHP usando `update_option()` con prefijo `options_` para cada clave.
2. Ejecutar con `wp eval-file script.php --allow-root` (con el socket MAMP activo).
3. Vaciar caché del objeto (WP Fastest Cache o `wp cache flush`).
4. Ir a WP-Admin → Opciones → Header & Mega-menú para verificar que ACF muestra los datos.

O bien, copiar la exportación de BD directamente:

```bash
mysqldump -uroot -proot -S /Applications/MAMP/tmp/mysql/mysql.sock \
  capitanproject_comudp \
  --tables wp_fnku4yoptions \
  --where="option_name LIKE 'options_menu_principal%'" \
  > megamenu-backup.sql
```
