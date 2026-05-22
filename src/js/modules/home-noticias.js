/**
 * Home — Noticias Swiper
 * Lazy import de Swiper solo si el componente está presente.
 */
import { qs } from '@utils/dom';

export async function initHomeNoticias() {
    const el = qs( '.js-home-noticias-swiper' );
    if ( ! el ) return;

    const { default: Swiper }                = await import( 'swiper' );
    const { Navigation, Keyboard, FreeMode } = await import( 'swiper/modules' );
    await import( 'swiper/css' );

    new Swiper( el, {
        modules: [ Navigation, Keyboard, FreeMode ],
        slidesPerView: 'auto',
        spaceBetween: 24,
        freeMode: { enabled: true, momentum: true },
        keyboard: { enabled: true },
        grabCursor: true,
        navigation: {
            nextEl: el.querySelector( '.swiper-button-next' ),
            prevEl: el.querySelector( '.swiper-button-prev' ),
        },
        breakpoints: {
            0:   { spaceBetween: 12, slidesOffsetBefore: 16, slidesOffsetAfter: 16 },
            768: { spaceBetween: 24, slidesOffsetBefore: 40, slidesOffsetAfter: 40 },
        },
    } );
}
