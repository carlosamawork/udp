/**
 * Módulo: Anchor Scrollspy (institucional)
 *
 * Detecta la sección activa con IntersectionObserver y aplica `.is-active`
 * al chip + rail-button correspondientes. Smooth scroll en clicks
 * (respeta prefers-reduced-motion). Pausa el spy 600ms tras un click
 * manual para evitar saltos.
 */

import { qsa } from '@utils/dom';

export function initAnchorScrollspy() {
    const sections = qsa('.udp-inst [id^="section-"]');
    const chipsLinks = qsa('.udp-inst-chips__link');
    const railLinks  = qsa('.udp-inst-rail__link');
    const allLinks   = [...chipsLinks, ...railLinks];

    if (!sections.length || !allLinks.length) return;

    let spyPaused = false;
    let pauseTimer = null;

    const setActive = (anchorId) => {
        allLinks.forEach((link) => {
            const isMatch = link.dataset.udpAnchor === anchorId;
            link.classList.toggle('is-active', isMatch);
            if (isMatch) {
                link.setAttribute('aria-current', 'location');
            } else {
                link.removeAttribute('aria-current');
            }
        });
    };

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(
            (entries) => {
                if (spyPaused) return;
                const visible = entries.filter((e) => e.isIntersecting);
                if (!visible.length) return;
                visible.sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);
                setActive(visible[0].target.id);
            },
            {
                rootMargin: '-30% 0px -60% 0px',
                threshold: 0,
            }
        );

        sections.forEach((s) => observer.observe(s));
    }

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    allLinks.forEach((link) => {
        link.addEventListener('click', (e) => {
            const id = link.dataset.udpAnchor;
            const target = id ? document.getElementById(id) : null;
            if (!target) return;

            e.preventDefault();

            spyPaused = true;
            if (pauseTimer) clearTimeout(pauseTimer);
            pauseTimer = setTimeout(() => { spyPaused = false; }, 600);

            setActive(id);

            target.scrollIntoView({
                behavior: reduceMotion ? 'auto' : 'smooth',
                block: 'start',
            });

            if (history.pushState) {
                history.pushState(null, '', `#${id}`);
            }
        });
    });
}
