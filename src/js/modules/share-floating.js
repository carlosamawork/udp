/**
 * Módulo: Share floating (píldora vertical de iconos).
 *
 * 5 acciones directas: copiar enlace, email, Facebook, X, WhatsApp.
 * Los enlaces sociales reciben su href construido; el de copiar usa clipboard.
 */

import { qsa } from '@utils/dom';

function buildHref(action, url, title) {
    const u = encodeURIComponent(url);
    const t = encodeURIComponent(title);
    switch (action) {
        case 'email':    return `mailto:?subject=${t}&body=${u}`;
        case 'whatsapp': return `https://wa.me/?text=${t}%20${u}`;
        case 'x':        return `https://x.com/intent/post?text=${t}&url=${u}`;
        case 'facebook': return `https://www.facebook.com/sharer/sharer.php?u=${u}`;
        default:         return '#';
    }
}

async function copyToClipboard(text, button) {
    try {
        await navigator.clipboard.writeText(text);
        button.classList.add('is-copied');
        const original = button.getAttribute('aria-label');
        button.setAttribute('aria-label', '¡Enlace copiado!');
        setTimeout(() => {
            button.classList.remove('is-copied');
            button.setAttribute('aria-label', original);
        }, 1500);
    } catch (e) {
        window.prompt('Copia el enlace:', text);
    }
}

export function initShareFloating() {
    const containers = qsa('[data-udp-share]');
    if (!containers.length) return;

    containers.forEach((container) => {
        const url   = container.dataset.shareUrl;
        const title = container.dataset.shareTitle || document.title;
        if (!url) return;

        container.querySelectorAll('a[data-share-action]').forEach((el) => {
            el.href = buildHref(el.dataset.shareAction, url, title);
        });

        const copyBtn = container.querySelector('button[data-share-action="copy"]');
        if (copyBtn) {
            copyBtn.addEventListener('click', () => copyToClipboard(url, copyBtn));
        }
    });
}
