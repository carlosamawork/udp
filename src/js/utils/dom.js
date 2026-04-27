/**
 * DOM Utilities
 */

/**
 * Ejecuta callback cuando el DOM está listo
 * @param {Function} fn
 */
export function domReady(fn) {
    if (document.readyState !== 'loading') {
        fn();
    } else {
        document.addEventListener('DOMContentLoaded', fn);
    }
}

/**
 * Shortcut para querySelector
 * @param {string} selector
 * @param {Element} context
 * @returns {Element|null}
 */
export function qs(selector, context = document) {
    return context.querySelector(selector);
}

/**
 * Shortcut para querySelectorAll como array
 * @param {string} selector
 * @param {Element} context
 * @returns {Element[]}
 */
export function qsa(selector, context = document) {
    return [...context.querySelectorAll(selector)];
}

/**
 * Crear elemento con atributos y children
 * @param {string} tag
 * @param {Object} attrs
 * @param  {...(string|Element)} children
 * @returns {Element}
 */
export function createElement(tag, attrs = {}, ...children) {
    const el = document.createElement(tag);
    Object.entries(attrs).forEach(([key, val]) => {
        if (key === 'class') {
            el.className = val;
        } else if (key.startsWith('data-')) {
            el.setAttribute(key, val);
        } else {
            el[key] = val;
        }
    });
    children.forEach(child => {
        if (typeof child === 'string') {
            el.appendChild(document.createTextNode(child));
        } else if (child instanceof Element) {
            el.appendChild(child);
        }
    });
    return el;
}
