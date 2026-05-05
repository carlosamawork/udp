/**
 * Block Image Gallery — Swiper init (lazy)
 */
import { qsa } from '@utils/dom';

export async function initBlockImageGallery() {
    const containers = qsa('[data-udp-block-gallery]');
    if (!containers.length) return;

    const { default: Swiper } = await import('swiper');
    const { Navigation, Keyboard, FreeMode } = await import('swiper/modules');
    await import('swiper/css');

    containers.forEach((el) => {
        const swiperEl = el.querySelector('.swiper');
        if (!swiperEl) return;

        new Swiper(swiperEl, {
            modules: [Navigation, Keyboard, FreeMode],
            slidesPerView: 'auto',
            spaceBetween: 16,
            keyboard: { enabled: true },
            grabCursor: true,
            freeMode: { enabled: true, momentum: true },
            navigation: {
                nextEl: el.querySelector('.udp-block-image-gallery__next'),
                prevEl: el.querySelector('.udp-block-image-gallery__prev'),
            },
            breakpoints: {
                768: { slidesPerView: 3, spaceBetween: 24 },
                0:   { slidesPerView: 1.1, spaceBetween: 12 },
            },
        });
    });
}
