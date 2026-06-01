# Spec: Página Anuarios UDP

**Fecha**: 2026-06-01
**Figma**: `4QlgGMlzNR9Ye344bAFuye`, nodo `3706:24351`

---

## Objetivo

Página que lista todos los anuarios de la UDP (actualmente 14, de 2010 a 2023-2024) en un grid de cards portrait. Cada card abre el PDF correspondiente en nueva pestaña. Las imágenes de portada se extraen del Figma y se suben a la media library de WP. Los datos (título + fecha + PDF + imagen) se gestionan via ACF repeater desde el admin.

---

## Arquitectura

```
templates/page-anuarios.php               ← orquestador (Template Name: Anuarios)
acf-json/group_page_anuarios.json         ← field group con repeater
template-parts/anuarios/
  └── card-anuario.php                    ← card portrait individual
src/scss/templates/_anuarios.scss         ← grid + card

// Reutilizados sin modificar:
template-parts/institucional/header.php       ← hero azul + breadcrumb + h1
template-parts/institucional/share-floating.php
```

---

## ACF — `group_page_anuarios`

- **Location**: `page_template == templates/page-anuarios.php`
- **Repeater**: `anuarios_items` (orden: más reciente primero)

| Sub-field       | Tipo            | Notas                                 |
|-----------------|-----------------|---------------------------------------|
| `anuario_titulo`| text, required  | "Anuario UDP 2023 – 2024"             |
| `anuario_fecha` | date_picker (Ymd) | Fecha de publicación. Se muestra como "F Y" (ej. "Diciembre 2025"). Script rellena desde `post_date` del attachment. |
| `anuario_pdf`   | file, url       | PDF existente en WP media             |
| `anuario_imagen`| image, array    | Portada extraída del Figma            |

---

## Template `page-anuarios.php`

```
get_header()
get_template_part('institucional/header', args: page_title='Anuarios UDP')
share-floating.php
<main>
  <section class="udp-anuarios">
    <div class="container">
      <div class="udp-anuarios__grid">
        foreach anuarios_items → card-anuario.php
      </div>
    </div>
  </section>
</main>
get_footer()
```

Si el repeater está vacío → early return con mensaje "No hay anuarios disponibles".

---

## Card — `card-anuario.php`

Acepta `$args`:
- `titulo` — string
- `fecha` — string
- `pdf_url` — string (URL del PDF)
- `imagen` — array ACF (url, alt, width, height) o vacío

Markup:

```html
<a class="udp-card-anuario" href="{pdf_url}" target="_blank" rel="noopener noreferrer">
  <figure class="udp-card-anuario__media [--placeholder]">
    <img src="{imagen.url}" alt="{imagen.alt}" loading="lazy"> <!-- si hay imagen -->
    <!-- si no hay imagen: div.udp-media-placeholder -->
  </figure>
  <div class="udp-card-anuario__body">
    <p class="udp-card-anuario__title">{titulo}</p>
    <time class="udp-card-anuario__date">{fecha}</time>
  </div>
</a>
```

---

## SCSS — `_anuarios.scss`

```scss
.udp-anuarios {
  background: #fff;
  padding-block: $space-4xl;

  &__grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 50px 30px;

    @media (max-width: 991px) { grid-template-columns: repeat(3, 1fr); }
    @media (max-width: 767px) { grid-template-columns: repeat(2, 1fr); }
    @media (max-width: 479px) { grid-template-columns: 1fr; }
  }
}

.udp-card-anuario {
  // card completa clickable
  &__media {
    aspect-ratio: 317 / 391;
    overflow: hidden;
    border-radius: 4px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);  // sombra sutil libro
    margin-bottom: 16px;

    img { width: 100%; height: 100%; object-fit: cover; }
    &--placeholder { @include udp-media-placeholder(light); }
  }

  &__title {
    font-family: $font-family-display;
    font-size: 1.125rem;  // ~18px
    font-weight: 700;
    color: $dark-1;
    margin: 0 0 4px;
  }

  &__date {
    font-size: 0.875rem;
    color: $gray-medium;
  }

  // hover: leve zoom en imagen
  &:hover &__media img { transform: scale(1.04); }
  &__media img { transition: transform 0.3s ease; }
  @media (prefers-reduced-motion: reduce) { &:hover &__media img { transform: none; } }
}
```

---

## Script de autopoblado

Script PHP ejecutable una vez (`/tmp/udp-populate-anuarios.php`), idempotente.

**Paso 1 — Imágenes de Figma**: las 14 imágenes de portada se descargan de los nodos Figma (roundedrectangle con image fill, IDs conocidos) y se suben a WP media via `media_handle_sideload()`. Se almacenan en `uploads/anuarios/`.

**Mapa de anuarios** (14 ítems, orden DESC):

| Título                   | Fecha pub.    | PDF filename pattern     | Figma node ID |
|--------------------------|---------------|--------------------------|---------------|
| Anuario UDP 2023 – 2024  | Diciembre 2025| anuario_udp_23-24        | 3706:24390    |
| Anuario UDP 2022         | Octubre 2023  | ANUARIO_2022             | 3706:24396    |
| Anuario UDP 2021         | Octubre 2022  | ANUARIO_2021-1           | 3706:24402    |
| Anuario UDP 2020         | Octubre 2021  | ANUARIO_2020             | 3706:24408    |
| Anuario UDP 2019         | Octubre 2020  | anuario2019              | 3706:24415    |
| Anuario UDP 2018         | Octubre 2019  | anuario2018              | 3706:24421    |
| Anuario UDP 2017         | Octubre 2018  | anuario2017              | 3706:24427    |
| Anuario UDP 2016         | Octubre 2017  | anuario_2016 / anuario_udp_2016 | 3706:24433 |
| Anuario UDP 2015         | Octubre 2016  | anuario_udp_2016 (pendiente confirmar) | 3706:24440 |
| Anuario UDP 2014         | Octubre 2015  | ANUARIO2014_UDP_WEB      | 3706:24446    |
| Anuario UDP 2013         | Octubre 2014  | Anuario_udp_2013         | 3706:24452    |
| Anuario UDP 2012         | Octubre 2013  | anuario_udp_2012         | 3706:24458    |
| Anuario UDP 2011         | Octubre 2012  | anuario_udp_2011         | 3706:24465    |
| Anuario UDP 2010         | Octubre 2011  | anuario_udp_2010         | 3706:24471    |

**Paso 2 — PDFs**: busca en WP media por `post_title LIKE '%anuario%'` y `mime_type = application/pdf`, matchea por filename pattern de la tabla anterior.

**Paso 3 — Repeater**: escribe los 14 ítems en `update_field('anuarios_items', $items, $page_id)` usando field keys del JSON. Idempotente: borra el campo antes de escribir (no acumula duplicados).

> ⚠️ Hay duplicados en la media library (dos PDFs de 2021, dos de 2016). El script toma el attachment más reciente en caso de ambigüedad y lo documenta en el output.

---

## Notas / decisiones

- **Sin paginación** — 14 anuarios caben en un scroll sin problema.
- **Sin filtros** — el grid es el único modo de navegación.
- **Imágenes opcionales** — si un anuario no tiene imagen (por fallo de Figma), muestra placeholder. El admin puede subir la imagen después.
- **Breadcrumb automático** — el partial `breadcrumb.php` lo genera a partir de la jerarquía de la página en WP (sin hardcodear la ruta).
- **Rail lateral** — no se incluye (pertenece a otras páginas, confirmado).
