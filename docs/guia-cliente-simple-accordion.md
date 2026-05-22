# Guía de uso — Template "Simple Accordion"

Esta guía explica cómo usar el template **Simple Accordion** para las páginas de la sección *Conoce UDP* (Historia, Gobernanza, Forma de Gobierno, etc.).

---

## ¿Qué hace este template?

Muestra una página con este layout:

```
┌──────────────────────────────────────────┐
│  Inicio › Conoce UDP › Nombre de página  │  ← Breadcrumb automático
│  Nombre de página                        │  ← Título
├──────────────────────────────────────────┤
│  Texto principal de la página            │
│  ─────────────────────────────           │
│  ▸ Item acordeón 1                       │
│  ▸ Item acordeón 2                       │
│  ▸ Item acordeón 3                       │
├──────────────────────────────────────────┤
│  También te puede interesar              │
│  [Gobernanza] [Forma de Gobierno] [...]  │
└──────────────────────────────────────────┘
```

- El **texto principal** se edita con el editor estándar de WordPress.
- El **acordeón** se gestiona desde los campos ACF de la página.
- La sección **"También te puede interesar"** muestra links manuales en un carrusel.

---

## Paso 1 — Asignar el template a una página

1. Ir a **Páginas** en el admin de WordPress.
2. Abrir la página que quieres editar (ej. *Historia*).
3. En el panel derecho, buscar **Atributos de página → Template**.
4. Seleccionar **Simple Accordion** en el desplegable.
5. Pulsar **Actualizar**.

> **Nota:** Si la página tenía contenido en el antiguo sistema (campo *Secciones*), al pulsar Actualizar ese contenido se copiará automáticamente al nuevo acordeón. No se borra nada del sistema anterior.

---

## Paso 2 — Editar el texto principal

El texto de introducción que aparece encima del acordeón se edita con el **editor de WordPress** (el bloque de texto grande en el centro de la pantalla de edición). Admite formato: negrita, cursiva, listas, links, etc.

---

## Paso 3 — Gestionar el acordeón

Debajo del editor encontrarás el campo **Acordeón**.

Cada item tiene dos campos:

| Campo | Descripción |
|---|---|
| **Título** | Texto visible en la cabecera del item (obligatorio) |
| **Contenido** | Texto enriquecido que aparece al desplegar el item |

### Añadir un item
Pulsar el botón **Añadir item** al final de la lista.

### Reordenar items
Arrastrar cada item por el icono de las seis puntos (⠿) que aparece a la izquierda.

### Eliminar un item
Pulsar el icono de la papelera en la esquina superior derecha del item.

---

## Paso 4 — Gestionar "También te puede interesar"

Debajo del acordeón encontrarás el campo **También te puede interesar**.

Cada link tiene dos campos:

| Campo | Descripción |
|---|---|
| **Título** | Nombre que aparece en la tarjeta |
| **Link** | URL de destino (acepta páginas internas y URLs externas) |

Si este campo está vacío, la sección no aparece en la página.

---

## Preguntas frecuentes

**¿Puedo usar este template en cualquier página?**
Sí. Aunque está pensado para páginas de *Conoce UDP*, puede usarse en cualquier página del sitio.

**¿Qué pasa con el contenido antiguo (campo "Secciones")?**
Al asignar el template por primera vez, el contenido del acordeón antiguo se copia automáticamente al nuevo campo. El campo antiguo no se elimina — está guardado en la base de datos por si fuera necesario consultarlo.

**¿Puedo dejar el acordeón vacío?**
Sí. Si no hay items en el acordeón, esa sección simplemente no aparece en la página.

**¿Puedo dejar "También te puede interesar" vacío?**
Sí. Si no hay links configurados, la sección no aparece.
