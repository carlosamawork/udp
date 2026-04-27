# Starter Theme BS5

Theme starter para WordPress con **Bootstrap 5.3** (npm), **SCSS**, **Vanilla JS modular**, **Vite** y soporte completo para **ACF Pro**.

## Requisitos

- WordPress 6.0+
- PHP 8.0+
- Node.js 18+ y npm
- Plugin **Advanced Custom Fields PRO**

## Instalación

```bash
# 1. Copia el tema a wp-content/themes/ y renómbralo
cp -r starter-theme wp-content/themes/mi-proyecto

# 2. Instala dependencias
cd wp-content/themes/mi-proyecto
npm install

# 3. Compila assets para producción
npm run build

# 4. Activa el tema en WordPress
```

## Comandos

| Comando         | Descripción                                         |
|-----------------|-----------------------------------------------------|
| `npm run dev`   | Arranca Vite dev server con HMR (localhost:5173)     |
| `npm run build` | Compila SCSS + JS para producción en `/dist`         |
| `npm run watch` | Build en modo watch (recompila al guardar cambios)   |

## Modo desarrollo (HMR)

Para tener Hot Module Replacement (los cambios CSS/JS se aplican sin recargar):

1. Añade a `wp-config.php`:
   ```php
   define('VITE_DEV_SERVER', true);
   ```

2. Arranca Vite:
   ```bash
   npm run dev
   ```

3. Los assets se cargarán desde `localhost:5173` con HMR.

4. **Para producción**, quita `VITE_DEV_SERVER` y ejecuta `npm run build`.

## Estructura

```
starter-theme/
├── src/                          # ← CÓDIGO FUENTE (aquí trabajas)
│   ├── scss/
│   │   ├── main.scss             # Entry SCSS principal
│   │   ├── editor.scss           # Estilos Gutenberg
│   │   ├── _base.scss            # Reset / estilos globales
│   │   ├── utilities/
│   │   │   ├── _variables.scss   # Variables (sobreescribe Bootstrap)
│   │   │   ├── _mixins.scss      # Mixins personalizados
│   │   │   └── _animations.scss  # Animaciones CSS
│   │   ├── layouts/
│   │   │   ├── _header.scss
│   │   │   └── _footer.scss
│   │   ├── components/
│   │   │   ├── _hero.scss
│   │   │   ├── _cards.scss
│   │   │   ├── _entry-content.scss
│   │   │   └── _widgets.scss
│   │   └── blocks/
│   │       └── _flexible-blocks.scss
│   └── js/
│       ├── main.js               # Entry JS principal
│       ├── modules/              # Módulos funcionales
│       │   ├── navbar.js
│       │   ├── smooth-scroll.js
│       │   ├── scroll-animations.js
│       │   └── mobile-menu.js
│       └── utils/                # Utilidades reutilizables
│           ├── dom.js
│           └── ajax.js
│
├── dist/                         # ← BUILD OUTPUT (generado por Vite)
│   ├── js/
│   ├── css/
│   └── .vite/manifest.json
│
├── inc/                          # PHP del tema
│   ├── class-vite.php            # Vite asset loader
│   ├── class-bootstrap-navwalker.php
│   ├── acf-setup.php
│   └── helpers.php
│
├── template-parts/
│   ├── card-post.php
│   └── blocks/
│       ├── block-text_image.php
│       ├── block-cta_banner.php
│       └── block-cards_grid.php
│
├── templates/
│   ├── page-hero.php
│   └── page-flexible.php
│
├── acf-json/                     # ACF JSON sync
│
├── vite.config.js                # Configuración Vite
├── postcss.config.js
├── package.json
├── .gitignore
│
├── functions.php
├── header.php / footer.php
├── index.php / single.php / page.php
├── archive.php / search.php / 404.php
├── front-page.php / sidebar.php
└── style.css
```

## Cómo funciona el Build System

### SCSS
- Las **variables** y **mixins** se inyectan automáticamente en todos los archivos SCSS (via `additionalData` en Vite).
- Bootstrap se importa DESPUÉS de tus variables, así las sobreescribes limpiamente.
- Puedes importar solo los módulos de Bootstrap que necesites (ver comentarios en `main.scss`).

### JavaScript
- Cada archivo en `src/js/modules/` es un módulo ES independiente.
- Se importan en `main.js` y Vite los bundlea con tree-shaking.
- **Aliases** disponibles: `@js`, `@scss`, `@modules`, `@utils`.

### Añadir una librería npm

```bash
# Ejemplo: instalar Swiper
npm install swiper

# Crear un módulo para él
# src/js/modules/slider.js
```

```js
// src/js/modules/slider.js
import Swiper from 'swiper';
import 'swiper/css';

export function initSlider() {
    new Swiper('.swiper', {
        slidesPerView: 3,
        spaceBetween: 30,
    });
}
```

```js
// src/js/main.js - añadir el import
import { initSlider } from '@modules/slider';

domReady(() => {
    // ...
    initSlider();
});
```

### Entry points por página

Puedes crear JS específico para cada página:

```js
// src/js/page-contact.js
import '@scss/pages/_contact.scss';  // SCSS específico
import { initMap } from '@modules/map';

document.addEventListener('DOMContentLoaded', () => {
    initMap();
});
```

```php
// functions.php
if (is_page_template('templates/page-contact.php')) {
    Starter_BS5_Vite::enqueue('page-contact', 'src/js/page-contact.js');
}
```

Vite detecta automáticamente todos los `src/js/*.js` como entry points.

## Campos ACF incluidos

### Options Pages
- **General**: nombre empresa, teléfono, email, dirección, Google Maps
- **Redes Sociales**: repeater con nombre de red + URL

### Page Templates
- **Página con Hero**: título, subtítulo, imagen de fondo, CTA
- **Página Flexible**: Flexible Content con 3 layouts:
  - Texto + Imagen, CTA Banner, Grid de Cards

## Helpers PHP

```php
starter_get_field($field, $post_id, $default)  // get_field con fallback
starter_get_option($field, $default)           // Campo de Options Page
starter_acf_image($image, $size, $class)       // Renderizar imagen ACF
starter_get_social_links()                     // Array de redes sociales
starter_breadcrumbs()                          // Breadcrumbs Bootstrap
starter_pagination()                           // Paginación Bootstrap
```

## Datos disponibles en JS

```js
window.starterBS5.ajaxUrl   // admin-ajax.php
window.starterBS5.nonce     // Nonce de seguridad
window.starterBS5.themeUrl  // URL del tema
window.starterBS5.homeUrl   // URL home
```

## Notas

- Reemplaza `screenshot.txt` por un `screenshot.png` de 1200×900px
- El directorio `dist/` se regenera con `npm run build` — no lo edites a mano
- En `.gitignore` están excluidos `node_modules/` y `dist/`
