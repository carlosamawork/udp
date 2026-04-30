/**
 * Calendario Archive — Active month tracking via IntersectionObserver
 *
 * Cuando el usuario scrolea por las secciones de meses, el link
 * correspondiente en el sidebar recibe la clase `--active`. Soporta
 * múltiples secciones visibles a la vez — la "más arriba" en viewport
 * gana el active state.
 */
import { qs, qsa } from '@utils/dom';

export function initCalendarioActiveMonth() {
    const sections = qsa('.udp-calendario-month');
    if (!sections.length) {
        return;
    }
    const links = qsa('.udp-calendario-sidebar__months-nav a[data-udp-month-link]');
    if (!links.length) {
        return;
    }

    const linkBySlug = {};
    links.forEach((link) => {
        const href = link.getAttribute('href') || '';
        const slug = href.replace(/^#/, '');
        if (slug) {
            linkBySlug[slug] = link;
        }
    });

    const setActive = (slug) => {
        links.forEach((link) => {
            link.classList.remove('udp-calendario-sidebar__month-link--active');
        });
        const target = linkBySlug[slug];
        if (target) {
            target.classList.add('udp-calendario-sidebar__month-link--active');
        }
    };

    // Track which sections are intersecting; pick the topmost one
    const visibleMap = new Map();

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    visibleMap.set(entry.target.id, entry.target.getBoundingClientRect().top);
                } else {
                    visibleMap.delete(entry.target.id);
                }
            });

            if (visibleMap.size === 0) {
                return;
            }

            // Topmost visible (smallest top, but >= some threshold)
            let topmost = null;
            let topmostY = Infinity;
            visibleMap.forEach((top, id) => {
                if (top < topmostY) {
                    topmostY = top;
                    topmost = id;
                }
            });

            if (topmost) {
                setActive(topmost);
            }
        },
        {
            // Anchor at ~120px from top (under sticky header)
            rootMargin: '-120px 0px -50% 0px',
            threshold: 0,
        }
    );

    sections.forEach((section) => {
        if (section.id) {
            observer.observe(section);
        }
    });
}
