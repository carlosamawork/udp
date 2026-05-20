/**
 * Módulo: Share floating button
 *
 * Si Web Share API está disponible, el click en el trigger la usa directamente.
 * Si no, abre un dropdown con 6 acciones (copiar, email, whatsapp, linkedin, x, facebook).
 */

import { qsa } from '@utils/dom';

function buildHref(action, url, title) {
    const u = encodeURIComponent(url);
    const t = encodeURIComponent(title);
    switch (action) {
        case 'email':    return `mailto:?subject=${t}&body=${u}`;
        case 'whatsapp': return `https://wa.me/?text=${t}%20${u}`;
        case 'linkedin': return `https://www.linkedin.com/sharing/share-offsite/?url=${u}`;
        case 'x':        return `https://x.com/intent/post?text=${t}&url=${u}`;
        case 'facebook': return `https://www.facebook.com/sharer/sharer.php?u=${u}`;
        default:         return '#';
    }
}

async function copyToClipboard(text, button) {
    try {
        await navigator.clipboard.writeText(text);
        const original = button.textContent;
        button.textContent = '¡Copiado!';
        setTimeout(() => { button.textContent = original; }, 1500);
    } catch (e) {
        window.prompt('Copia el enlace:', text);
    }
}

export function initShareFloating() {
    const containers = qsa('[data-udp-share]');
    if (!containers.length) return;

    containers.forEach((container) => {
        const trigger = container.querySelector('.udp-inst-share__trigger');
        const menu    = container.querySelector('.udp-inst-share__menu');
        const url     = container.dataset.shareUrl;
        const title   = container.dataset.shareTitle || document.title;

        if (!trigger || !menu || !url) return;

        menu.querySelectorAll('[data-share-action]').forEach((el) => {
            const action = el.dataset.shareAction;
            if (action === 'copy') return;
            const href = buildHref(action, url, title);
            if (el.tagName === 'A') el.href = href;
        });

        const canNative = typeof navigator.share === 'function';

        trigger.addEventListener('click', async (e) => {
            e.stopPropagation();
            if (canNative) {
                try {
                    await navigator.share({ title, url });
                    return;
                } catch (err) {
                    if (err && err.name === 'AbortError') return;
                }
            }
            const open = !menu.hidden;
            menu.hidden = open;
            trigger.setAttribute('aria-expanded', open ? 'false' : 'true');
        });

        const copyBtn = menu.querySelector('[data-share-action="copy"]');
        if (copyBtn) {
            copyBtn.addEventListener('click', () => copyToClipboard(url, copyBtn));
        }

        document.addEventListener('click', (e) => {
            if (!menu.hidden && !container.contains(e.target)) {
                menu.hidden = true;
                trigger.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !menu.hidden) {
                menu.hidden = true;
                trigger.setAttribute('aria-expanded', 'false');
                trigger.focus();
            }
        });
    });
}
