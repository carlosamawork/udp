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
    const { FreeMode, A11y } = await import('swiper/modules');
    await import('swiper/css');

    sections.forEach((swiperEl) => {
        new Swiper(swiperEl, {
            modules: [FreeMode, A11y],
            slidesPerView: 'auto',
            spaceBetween: 24,
            slidesOffsetBefore: 40,
            slidesOffsetAfter: 40,
            freeMode: { enabled: true, momentum: true },
            grabCursor: true,
            a11y: { enabled: true },
            breakpoints: {
                0:   { spaceBetween: 16, slidesOffsetBefore: 16, slidesOffsetAfter: 16 },
                768: { spaceBetween: 24, slidesOffsetBefore: 40, slidesOffsetAfter: 40 },
            },
        });
    });
}
