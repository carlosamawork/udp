/**
 * Single Post — Gallery Swiper init
 */
import { qsa } from '@utils/dom';

export async function initSinglePostGallery() {
    const containers = qsa('[data-udp-post-gallery]');
    if (!containers.length) {
        return;
    }

    const { default: Swiper } = await import('swiper');
    const { Navigation, Keyboard } = await import('swiper/modules');
    await import('swiper/css');

    containers.forEach((el) => {
        const swiperEl = el.querySelector('.swiper');
        if (!swiperEl) return;

        new Swiper(swiperEl, {
            modules: [Navigation, Keyboard],
            slidesPerView: 'auto',
            spaceBetween: 16,
            keyboard: { enabled: true },
            grabCursor: true,
            navigation: {
                nextEl: el.querySelector('.udp-single-post__gallery-next'),
                prevEl: el.querySelector('.udp-single-post__gallery-prev'),
            },
            breakpoints: {
                768: { slidesPerView: 3, spaceBetween: 30 },
                0:   { slidesPerView: 1.1, spaceBetween: 12 },
            },
        });
    });
}
