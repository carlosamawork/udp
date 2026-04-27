/**
 * Módulo: Navbar
 *
 * Gestiona el comportamiento del navbar al hacer scroll.
 */

import { qs } from '@utils/dom';

export function initNavbar() {
    const header = qs('#site-header');

    if (!header) return;

    const onScroll = () => {
        header.classList.toggle('is-scrolled', window.scrollY > 50);
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll(); // Estado inicial
}
