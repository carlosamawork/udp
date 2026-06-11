/**
 * Single Post — Comportamiento mobile (barra inferior + carrusel relacionados)
 */
import { qs, qsa } from '@utils/dom';

function initShare(bar) {
    const btn = qs('[data-udp-mobile-share]', bar);
    const sheet = qs('[data-udp-mobile-sheet]', bar);
    if (!btn) return;

    const url = bar.dataset.shareUrl || window.location.href;
    const title = bar.dataset.shareTitle || document.title;

    btn.addEventListener('click', async () => {
        if (navigator.share) {
            try {
                await navigator.share({ title, url });
            } catch (e) {
                /* usuario canceló: no-op */
            }
            return;
        }
        if (sheet) {
            const willOpen = sheet.hidden;
            sheet.hidden = !willOpen;
            btn.setAttribute('aria-expanded', String(willOpen));
        }
    });

    const copyBtn = sheet ? sheet.querySelector('[data-udp-copy-url]') : null;
    if (copyBtn) {
        copyBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            const toast = copyBtn.querySelector('[data-udp-copy-toast]');
            const value = copyBtn.dataset.url || url;
            const done = () => {
                if (!toast) return;
                toast.hidden = false;
                setTimeout(() => { toast.hidden = true; }, 1500);
            };
            try {
                await navigator.clipboard.writeText(value);
                done();
            } catch (err) {
                window.prompt('Copia el enlace:', value);
            }
        });
    }

    document.addEventListener('click', (e) => {
        if (sheet && !sheet.hidden && !bar.contains(e.target)) {
            sheet.hidden = true;
            btn.setAttribute('aria-expanded', 'false');
        }
    });
}

function initMenu(bar) {
    const btn = qs('[data-udp-mobile-menu]', bar);
    if (!btn) return;
    btn.addEventListener('click', () => {
        const toggle = qs('[data-udp-megamenu-toggle]');
        if (toggle) toggle.click();
    });
}

function initTop(bar) {
    const btn = qs('[data-udp-mobile-top]', bar);
    if (!btn) return;
    btn.addEventListener('click', () => {
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
    });
}

async function initRelatedCarousel() {
    const el = qs('[data-udp-related-carousel]');
    if (!el) return;

    const viewport = el.querySelector('.udp-single-post__related-viewport');
    const list = el.querySelector('.udp-single-post__related-list');
    const items = qsa('.udp-single-post__related-item', el);
    const pagination = el.querySelector('.udp-single-post__related-dots');
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

export function initSinglePostMobile() {
    const bar = qs('[data-udp-mobile-bar]');
    if (bar) {
        initShare(bar);
        initMenu(bar);
        initTop(bar);
    }
    initRelatedCarousel();
}
