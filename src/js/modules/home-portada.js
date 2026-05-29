import { qs } from '@utils/dom';

export async function initHomePortada() {
    const section = qs('.js-home-portada');
    const media = qs('.js-portada-media');
    const content = qs('.js-portada-content');
    const header = qs('.udp-site-header');

    if (!section || !media || !content) return;

    const { gsap } = await import('gsap');
    const { ScrollTrigger } = await import('gsap/ScrollTrigger');
    gsap.registerPlugin(ScrollTrigger);

    await document.fonts.ready;

    const headerH = header ? header.offsetHeight : 0;
    const contentH = content.offsetHeight;
    const initTop = headerH + contentH;
    const initLeft = content.getBoundingClientRect().left;

    gsap.set(media, {
        top: initTop,
        left: initLeft,
        opacity: 1
    });

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    gsap.to(media, {
        top: 0,
        left: 0,
        width: '100vw',
        height: '100vh',
        ease: 'none',
        scrollTrigger: {
            trigger: section,
            start: 'top top',
            end: '+=50vh',
            scrub: 2,
        },
    });
}
