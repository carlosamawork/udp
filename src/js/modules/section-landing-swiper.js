/**
 * Section Landing — Swiper init
 *
 * Inicializa Swiper.js solo en `.udp-section-cards--swiper`.
 * Lazy: importa Swiper.js solo si hay un swiper en la página.
 */
import { qsa } from '@utils/dom';

export async function initSectionLandingSwiper() {
    const containers = qsa('.udp-section-cards--swiper');
    if (!containers.length) {
        return;
    }

    // Lazy load Swiper solo si hace falta
    const { default: Swiper } = await import('swiper');
    const { Navigation, Keyboard, FreeMode } = await import('swiper/modules');
    await import('swiper/css');

    containers.forEach((el) => {
        // El elemento .swiper está dentro del container __viewport
        const swiperEl = el.querySelector('.swiper');
        if (!swiperEl) return;

        new Swiper(swiperEl, {
            modules: [Navigation, Keyboard, FreeMode],
            slidesPerView: 'auto',
            spaceBetween: 33,
            freeMode: { enabled: true, momentum: true },
            keyboard: { enabled: true },
            grabCursor: true,
            breakpoints: {
                768: { spaceBetween: 33 },
                0:   { spaceBetween: 16 },
            },
        });
    });
}
