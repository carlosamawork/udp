# F5d — `block_calendario_grid` flex content block — Design Spec

**Status:** Draft.
**Phase:** F5d (extras de F5).

---

## 1. Objetivo

Bloque flex content `block_calendario_grid` insertable en cualquier landing con plantilla `page-flexible.php`. Renderiza N entries del CPT calendario filtradas por año + mes (opcional) + taxonomías. Reusa `entry-calendario.php` partial.

A diferencia del archive completo (F5a) que es un documento anual con sidebar, este bloque es un **widget compacto** para una landing.

---

## 2. ACF — extender el layout flex existente

Añadir un segundo layout `block_calendario_grid` al field `content_blocks` del group `group_template_flexible_content` (creado en F4a). Sub-fields:

```
LAYOUT block_calendario_grid
├── titulo            (text, opt)        ← H2 de la sección
├── eyebrow           (text, opt)        ← Eyebrow sobre el título
├── year              (number, default current year)
├── mes               (radio, opt)
│   choices: '' | 01 | 02 | ... | 12
│   labels: 'Todo el año' | 'Enero' | 'Febrero' | ... | 'Diciembre'
│   default ''
├── filtros           (group)
│   ├── tipo          (taxonomy tipo-udp, opt)
│   └── publico       (taxonomy publico-udp, opt)
├── n_items           (number, default 10, min 1, max 30)
└── theme             (radio: dark | light, default dark)
```

Field keys: `field_block_calendario_grid_*`.

---

## 3. Container partial

`template-parts/blocks/block-block_calendario_grid.php` (slug-name pattern: `get_template_part('template-parts/blocks/block', 'block_calendario_grid')`).

Lee sub-fields, llama `udp_query_calendario()` con los filtros + truncation a `n_items`, renderiza:

```html
<section class="udp-block-calendario-grid udp-block-calendario-grid--{theme}">
    <div class="udp-block-calendario-grid__inner">
        <header class="udp-block-calendario-grid__header"> <!-- opcional si hay titulo/eyebrow -->
            <p class="udp-block-calendario-grid__eyebrow">EYEBROW</p>
            <h2 class="udp-block-calendario-grid__title">Título</h2>
        </header>
        <ul class="udp-block-calendario-grid__list">
            <!-- foreach entries → entry-calendario partial -->
        </ul>
    </div>
</section>
```

`udp_query_calendario()` ya existe pero devuelve `entries_by_month` (todos los meses). Para este bloque necesitamos slicing:
- Si `mes` está set → solo ese mes
- Si `mes` vacío → todos los meses, primero los más cercanos a hoy
- Truncar a `n_items` total

**Decisión:** crear una variante simple `udp_query_calendario_flat( array $filters )` que devuelve un array plano de entries (no agrupado) con limit + offset, ordenado por fecha ASC. Reusa internamente WP_Query similar.

O extender `udp_query_calendario()` con un flag. Mejor crear función separada para mantener limpio el código.

---

## 4. Inventario de archivos

**Crear:**
- `template-parts/blocks/block-block_calendario_grid.php`
- `src/scss/blocks/_block-calendario-grid.scss`

**Modificar:**
- `acf-json/group_template_flexible_content.json` — añadir layout `block_calendario_grid` al campo flex existente.
- `inc/udp-cards.php` — añadir `udp_query_calendario_flat` (flat list con mes optional + n_items).
- `src/scss/main.scss` — `@import "blocks/block-calendario-grid";`.

---

## 5. Helper `udp_query_calendario_flat`

```php
/**
 * @param array $filters {
 *     @type int    $year     YYYY. Default año actual.
 *     @type string $mes      '01'..'12' o '' para todos.
 *     @type int    $tipo     term_id tipo-udp.
 *     @type int    $publico  term_id publico-udp.
 *     @type int    $limit    Default 10, max 30.
 * }
 * @return array<entry>  // shape igual a udp_calendario_data_from_post
 */
function udp_query_calendario_flat( array $filters ): array;
```

Implementación: WP_Query con `post_type=calendario`, `meta_key=fecha`, `orderby=meta_value ASC`. Filtros:
- `meta_query` con `value = sprintf('%04d%02d', year, mes)` LIKE si mes set, else `value = sprintf('%04d', year)` LIKE.
- `tax_query` AND para tipo + publico.
- `posts_per_page = limit` (max 30).

Mapea posts via `udp_calendario_data_from_post()` (ya existe).

---

## 6. SCSS

`_block-calendario-grid.scss`:

```scss
.udp-block-calendario-grid {
    padding: $space-3xl 0;

    &--dark { background: $dark-1; color: $white; }
    &--light { background: $white; color: $dark-1; }

    &__inner {
        max-width: 1440px;
        margin-inline: auto;
        padding-inline: $space-3xl;
        @include media-down(md) { padding-inline: $space-sm; }
    }

    &__header { /* título + eyebrow */ }
    &__list { /* reusa estilos de entry-calendario, en theme dark/light según el block */ }
}
```

Para entries en theme light: override colors del `entry-calendario` (text dark, border-bottom dark, tag yellow se mantiene).

---

## 7. Verificación E2E

1. En page-flexible, admin añade un bloque `block_calendario_grid` con title, year=2026, mes='', n_items=8 → renderiza 8 entries del 2026.
2. Cambiar mes='03' → solo 8 entries de marzo (o las que existan).
3. Cambiar theme=light → bg blanco, texto oscuro.
4. Filtros taxonomía aplicados correctamente.

---

## 8. Pendientes

- ICS button por entry (igual que en archive F5a) — heredado del partial `entry-calendario.php`.
- Active-month tracking N/A (no hay sidebar en bloque).
