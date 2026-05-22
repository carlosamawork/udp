/**
 * Home — Cultura Digital: slider horizontal con arrastre libre.
 */
export async function initHomeCulturaDigital() {
    const el = document.querySelector( '.js-cultura-digital-swiper' );
    if ( ! el ) return;

    const { default: Swiper, FreeMode } = await import( 'swiper' );
    await import( 'swiper/css' );
    await import( 'swiper/css/free-mode' );

    new Swiper( el, {
        modules: [ FreeMode ],
        freeMode: true,
        grabCursor: true,
        slidesPerView: 'auto',
        spaceBetween: 30,
    } );
}
