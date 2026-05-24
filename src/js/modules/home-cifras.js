/**
 * Home — Cifras: slider horizontal con arrastre libre.
 */
export async function initHomeCifras() {
    const el = document.querySelector('.js-cifras-swiper');
    if (!el) return;

    // 1. Importamos el BUNDLE completo (incluye todos los componentes ya registrados)
    // En versiones antiguas/modernas, '/bundle' o 'swiper/bundle' asegura que todo esté activo.
    const { default: Swiper } = await import('swiper/bundle');

    // 2. Importamos el CSS del bundle completo para que no falte ningún estilo
    await import('swiper/css/bundle');

    // 3. Inicializamos directamente. YA NO necesitas pasar 'modules: [Autoplay]' 
    // porque el bundle completo ya los tiene inyectados globalmente.
    const swiper = new Swiper(el, {
        grabCursor: true,
        slidesPerView: 'auto',
        spaceBetween: 30,
        loop: true,
        autoplay: {
            delay: 3000,
            disableOnInteraction: false,
        },
        speed: 1000,
    });

    // 4. Verificación de seguridad por si acaso
    setTimeout(() => {
        if (swiper && swiper.autoplay) {
            swiper.autoplay.start();
        }
    }, 100);
}