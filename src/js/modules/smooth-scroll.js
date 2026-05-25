/**
 * Módulo: Smooth Scroll
 *
 * Smooth scroll para enlaces con anclas internas (#).
 */

import { qsa, qs } from '@utils/dom';

export function initSmoothScroll() {
    // Excluir anchors gestionados por anchor-scrollspy (chips/rail del template
    // institucional) para no encimar dos scrolls sobre el mismo click.
    qsa('a[href^="#"]:not(.udp-inst-chips__link):not(.udp-inst-rail__link)').forEach(anchor => {
        anchor.addEventListener('click', (e) => {
            const targetId = anchor.getAttribute('href');
            if (targetId === '#' || targetId === '#!') return;

            const target = qs(targetId);
            if (!target) return;

            e.preventDefault();

            const header = qs('#site-header');
            const offset = header ? header.offsetHeight + 20 : 20;
            const top = target.getBoundingClientRect().top + window.scrollY - offset;

            window.scrollTo({ top, behavior: 'smooth' });

            // Actualizar URL sin saltar
            history.pushState(null, '', targetId);
        });
    });
}
