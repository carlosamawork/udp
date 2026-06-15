/**
 * Módulo: Institucional > People Carousel
 *
 * Swiper lazy-loaded. Solo init si hay .udp-inst-people en el DOM.
 */

import { qsa } from '@utils/dom';

export async function initInstitucionalPeople() {
    const sections = qsa('.udp-inst-people .swiper');
    if (!sections.length) return;

    const { default: Swiper } = await import('swiper');
    const { FreeMode, A11y, Scrollbar } = await import('swiper/modules');
    await import('swiper/css');
    await import('swiper/css/scrollbar');

    sections.forEach((swiperEl) => {
        // Deslizador (Figma): scrollbar arrastrable dentro de este carrusel.
        const scrollbarEl = swiperEl.querySelector('.swiper-scrollbar');

        new Swiper(swiperEl, {
            modules: [FreeMode, A11y, Scrollbar],
            slidesPerView: 'auto',
            // Base = mobile (offset 16). El breakpoint 768 sube a desktop (40).
            spaceBetween: 16,
            slidesOffsetBefore: 16,
            slidesOffsetAfter: 16,
            freeMode: { enabled: true, momentum: true },
            grabCursor: true,
            a11y: { enabled: true },
            scrollbar: scrollbarEl ? { el: scrollbarEl, draggable: true } : false,
            breakpoints: {
                768: { spaceBetween: 24, slidesOffsetBefore: 40, slidesOffsetAfter: 40 },
            },
        });
    });
}
