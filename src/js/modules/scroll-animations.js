/**
 * Módulo: Scroll Animations
 *
 * Activa la clase .is-visible en elementos .fade-in
 * cuando entran en el viewport (IntersectionObserver).
 */

import { qsa } from '@utils/dom';

export function initScrollAnimations() {
    const elements = qsa('.fade-in');

    if (elements.length === 0 || !('IntersectionObserver' in window)) return;

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px',
        }
    );

    elements.forEach(el => observer.observe(el));
}
