/**
 * Módulo: Mobile Menu
 *
 * Cierra el navbar colapsado al hacer clic en un enlace.
 */

import { qs, qsa } from '@utils/dom';

export function initMobileMenu() {
    const navbarCollapse = qs('#mainNavbar');
    if (!navbarCollapse) return;

    qsa('.nav-link:not(.dropdown-toggle)', navbarCollapse).forEach(link => {
        link.addEventListener('click', () => {
            const bsCollapse = window.bootstrap?.Collapse?.getInstance(navbarCollapse);
            if (bsCollapse) {
                bsCollapse.hide();
            }
        });
    });
}
