/**
 * Block Accordion — smooth height animation para <details>.
 *
 * El <details> nativo abre/cierra instantáneo. Este módulo añade
 * height animation. Si JS no carga, sigue funcional vía nativo.
 */
import { qsa } from '@utils/dom';

export function initBlockAccordion() {
    const detailsList = qsa('.udp-block-accordion__details');
    if (!detailsList.length) return;

    detailsList.forEach((details) => {
        const summary = details.querySelector('.udp-block-accordion__summary');
        const content = details.querySelector('.udp-block-accordion__content');
        if (!summary || !content) return;

        summary.addEventListener('click', (event) => {
            event.preventDefault();

            if (details.open) {
                // closing — animate to 0
                content.style.height = content.offsetHeight + 'px';
                requestAnimationFrame(() => {
                    content.style.transition = 'height 0.25s ease';
                    content.style.height = '0px';
                });
                content.addEventListener('transitionend', function once() {
                    details.open = false;
                    content.style.height = '';
                    content.style.transition = '';
                    content.removeEventListener('transitionend', once);
                });
            } else {
                details.open = true;
                const target = content.scrollHeight;
                content.style.height = '0px';
                requestAnimationFrame(() => {
                    content.style.transition = 'height 0.25s ease';
                    content.style.height = target + 'px';
                });
                content.addEventListener('transitionend', function once() {
                    content.style.height = '';
                    content.style.transition = '';
                    content.removeEventListener('transitionend', once);
                });
            }
        });
    });
}
