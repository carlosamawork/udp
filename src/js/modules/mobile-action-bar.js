/**
 * Barra de acción mobile global: compartir + menú + volver-arriba.
 */
import { qs } from '@utils/dom';

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

function initTop(bar) {
    const btn = qs('[data-udp-mobile-top]', bar);
    if (!btn) return;
    btn.addEventListener('click', () => {
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
    });
}

export function initMobileActionBar() {
    const bar = qs('[data-udp-mobile-bar]');
    if (!bar) return;
    initShare(bar);
    initTop(bar);
}
