/**
 * AJAX Helper
 *
 * Usa fetch para comunicarse con admin-ajax.php de WordPress.
 * Los datos de starterBS5 se inyectan vía wp_localize_script.
 */

/**
 * Enviar petición AJAX a WordPress
 *
 * @param {string} action - Nombre del action de WordPress
 * @param {Object} data - Datos adicionales
 * @param {Object} options - Opciones extra para fetch
 * @returns {Promise<Object|null>}
 *
 * @example
 * import { ajax } from '@utils/ajax';
 *
 * const result = await ajax('load_more_posts', { page: 2, category: 'news' });
 * if (result?.success) {
 *     console.log(result.data);
 * }
 */
export async function ajax(action, data = {}, options = {}) {
    const { ajaxUrl, nonce } = window.starterBS5 || {};

    if (!ajaxUrl) {
        console.error('[ajax] starterBS5.ajaxUrl no disponible');
        return null;
    }

    const formData = new FormData();
    formData.append('action', action);
    formData.append('nonce', nonce);

    Object.entries(data).forEach(([key, value]) => {
        formData.append(key, value);
    });

    try {
        const response = await fetch(ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            ...options,
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        return await response.json();
    } catch (error) {
        console.error(`[ajax] Error en action "${action}":`, error);
        return null;
    }
}

/**
 * Petición GET a la REST API de WordPress
 *
 * @param {string} endpoint - Ruta relativa (ej: 'wp/v2/posts')
 * @param {Object} params - Query params
 * @returns {Promise<Object|null>}
 *
 * @example
 * const posts = await restGet('wp/v2/posts', { per_page: 6, categories: 5 });
 */
export async function restGet(endpoint, params = {}) {
    const { homeUrl } = window.starterBS5 || {};
    const url = new URL(`${homeUrl}wp-json/${endpoint}`);

    Object.entries(params).forEach(([key, val]) => {
        url.searchParams.append(key, val);
    });

    try {
        const response = await fetch(url.toString(), {
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        return await response.json();
    } catch (error) {
        console.error(`[restGet] Error en ${endpoint}:`, error);
        return null;
    }
}
