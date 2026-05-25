/**
 * Módulo: drag-to-scroll para el carrusel `.udp-inst-featured__list`.
 *
 * Permite arrastrar con el mouse para desplazar horizontalmente. En touch se
 * deja el scroll/swipe nativo. Si hubo arrastre, se cancela el click para que
 * no se dispare el enlace de la card.
 */

import { qsa } from '@utils/dom';

export function initFeaturedDrag() {
    qsa('.udp-inst-featured__list').forEach((list) => {
        let isDown = false;
        let startX = 0;
        let startScroll = 0;
        let moved = false;

        list.addEventListener('pointerdown', (e) => {
            if (e.pointerType === 'touch') return; // touch: scroll nativo
            isDown = true;
            moved = false;
            startX = e.clientX;
            startScroll = list.scrollLeft;
            list.classList.add('is-dragging');
            try { list.setPointerCapture(e.pointerId); } catch (_) { /* noop */ }
        });

        list.addEventListener('pointermove', (e) => {
            if (!isDown) return;
            const dx = e.clientX - startX;
            if (Math.abs(dx) > 4) moved = true;
            list.scrollLeft = startScroll - dx;
        });

        const end = (e) => {
            if (!isDown) return;
            isDown = false;
            list.classList.remove('is-dragging');
            try { list.releasePointerCapture(e.pointerId); } catch (_) { /* noop */ }
        };
        list.addEventListener('pointerup', end);
        list.addEventListener('pointercancel', end);

        // Si hubo arrastre, evita que el click abra el enlace de la card.
        list.addEventListener('click', (e) => {
            if (moved) {
                e.preventDefault();
                e.stopPropagation();
                moved = false;
            }
        }, true);
    });
}
