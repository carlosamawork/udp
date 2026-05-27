/**
 * Home — Innovación e Investigación: slider horizontal con arrastre libre.
 */
export async function initHomeInnovacion() {
    const el = document.querySelector( '.js-innovacion-swiper' );
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
