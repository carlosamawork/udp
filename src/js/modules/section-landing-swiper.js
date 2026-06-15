/**
 * Section Landing — Swiper init
 *
 * - Modo SWIPER (`.udp-section-cards--swiper`): carrusel en TODOS los anchos.
 * - Modo GRID   (`.udp-section-cards--grid`):  grid en desktop; en mobile (<md)
 *   se convierte en el MISMO slider (init/destroy por matchMedia). Así, en mobile
 *   ambos modos son slider (Figma 4041-44920).
 *
 * Lazy: importa Swiper.js solo si hay algún carrusel candidato en la página.
 */
import { qsa } from '@utils/dom';

const SWIPER_CONFIG = {
    slidesPerView: 'auto',
    grabCursor: true,
    freeMode: { enabled: true, momentum: true },
    keyboard: { enabled: true },
    spaceBetween: 33,
    slidesOffsetBefore: 40,
    slidesOffsetAfter: 40,
    breakpoints: {
        768: { spaceBetween: 33, slidesOffsetBefore: 40, slidesOffsetAfter: 40 },
        0:   { spaceBetween: 16, slidesOffsetBefore: 16, slidesOffsetAfter: 16 },
    },
};

export async function initSectionLandingSwiper() {
    const swiperContainers = qsa('.udp-section-cards--swiper');
    const gridContainers = qsa('.udp-section-cards--grid');
    if (!swiperContainers.length && !gridContainers.length) {
        return;
    }

    // Lazy load Swiper solo si hace falta
    const { default: Swiper } = await import('swiper');
    const { Navigation, Keyboard, FreeMode } = await import('swiper/modules');
    await import('swiper/css');

    const config = { ...SWIPER_CONFIG, modules: [Navigation, Keyboard, FreeMode] };

    // --- Modo swiper: carrusel siempre activo ---
    swiperContainers.forEach((el) => {
        const swiperEl = el.querySelector('.swiper');
        if (swiperEl) {
            new Swiper(swiperEl, config);
        }
    });

    // --- Modo grid: slider SOLO en mobile (<md); grid en desktop ---
    const mq = window.matchMedia('(max-width: 767.98px)');

    gridContainers.forEach((el) => {
        const viewport = el.querySelector('.udp-section-cards__viewport');
        const list = el.querySelector('.udp-section-cards__list');
        const items = qsa('.udp-section-cards__item', el);
        if (!viewport || !list) return;

        let swiper = null;
        let enabling = false;

        function enable() {
            if (swiper || enabling) return;
            enabling = true;
            viewport.classList.add('swiper');
            list.classList.add('swiper-wrapper');
            items.forEach((i) => i.classList.add('swiper-slide'));
            swiper = new Swiper(viewport, config);
            enabling = false;
        }

        function disable() {
            if (!swiper) return;
            swiper.destroy(true, true);
            swiper = null;
            viewport.classList.remove('swiper');
            list.classList.remove('swiper-wrapper');
            items.forEach((i) => i.classList.remove('swiper-slide'));
        }

        function sync() {
            if (mq.matches) enable();
            else disable();
        }

        sync();
        mq.addEventListener('change', sync);
    });
}
