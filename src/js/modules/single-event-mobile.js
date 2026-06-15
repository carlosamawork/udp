/**
 * Single Event — Carrusel de "Te podría interesar" (init/destroy por matchMedia).
 * Mismo patrón que single-post-mobile.js, con las clases del single-event.
 */
import { qs, qsa } from '@utils/dom';

async function initEventRelatedCarousel() {
    const el = qs('[data-udp-event-related-carousel]');
    if (!el) return;

    const viewport = el.querySelector('.udp-single-event__related-viewport');
    const list = el.querySelector('.udp-single-event__related-list');
    const items = qsa('.udp-single-event__related-item', el);
    const pagination = el.querySelector('.udp-single-event__related-dots');
    if (!viewport || !list) return;

    const mq = window.matchMedia('(max-width: 767.98px)');
    let swiper = null;
    let enabling = false;

    async function enable() {
        if (swiper || enabling) return;
        enabling = true;
        viewport.classList.add('swiper');
        list.classList.add('swiper-wrapper');
        items.forEach((i) => i.classList.add('swiper-slide'));

        const { default: Swiper } = await import('swiper');
        const { Pagination } = await import('swiper/modules');
        await import('swiper/css');

        swiper = new Swiper(viewport, {
            modules: [Pagination],
            slidesPerView: 1,
            spaceBetween: 16,
            grabCursor: true,
            pagination: { el: pagination, clickable: true },
        });
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
}

export function initSingleEventMobile() {
    initEventRelatedCarousel();
}
