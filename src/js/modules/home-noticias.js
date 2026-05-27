/**
 * Home — Noticias Swiper
 * Lazy import de Swiper solo si el componente está presente.
 */
import { qs } from '@utils/dom';

export async function initHomeNoticias() {
    const el = qs('.js-home-noticias-swiper');
    if (!el) return;

    const section = el.closest('.udp-home-noticias');

    const { default: Swiper } = await import('swiper');
    const { Navigation, Keyboard } = await import('swiper/modules');
    await import('swiper/css');

    new Swiper(el, {
        modules: [Navigation, Keyboard],
        slidesPerView: 'auto',
        spaceBetween: 30,
        keyboard: { enabled: true },
        grabCursor: true,
        navigation: {
            nextEl: section ? section.querySelector('.js-noticias-next') : null,
            prevEl: section ? section.querySelector('.js-noticias-prev') : null,
        },
    });
}
