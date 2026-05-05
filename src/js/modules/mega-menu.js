/**
 * Mega-menu — open/close + active item state machine.
 *
 * Eventos:
 *  - Click en [data-udp-megamenu-toggle] → open
 *  - Click en [data-udp-megamenu-close] → close
 *  - ESC key → close
 *  - Click en [data-udp-megamenu-item="N"] → activa el detail panel N
 *  - Click backdrop fuera del panel body → close (opcional, si UX lo pide)
 *
 * Lock body scroll cuando está abierto. Trap focus básico (Tab dentro del panel).
 */
import { qs, qsa } from '@utils/dom';

const STATE = {
    isOpen: false,
    activeIdx: 0,
    lastFocused: null,
};

function setOpen(panel, open) {
    if (!panel) return;
    STATE.isOpen = open;
    panel.hidden = !open;
    document.documentElement.classList.toggle('udp-megamenu-open', open);
    document.body.classList.toggle('udp-megamenu-open', open);

    const toggle = qs('[data-udp-megamenu-toggle]');
    if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');

    if (open) {
        STATE.lastFocused = document.activeElement;
        const closeBtn = panel.querySelector('[data-udp-megamenu-close]');
        if (closeBtn) closeBtn.focus();
    } else {
        if (STATE.lastFocused && typeof STATE.lastFocused.focus === 'function') {
            STATE.lastFocused.focus();
        }
    }
}

function setActiveItem(panel, idx) {
    if (!panel) return;
    STATE.activeIdx = idx;

    qsa('.udp-megamenu__primary-item', panel).forEach((el, i) => {
        el.classList.toggle('udp-megamenu__primary-item--active', i === idx);
    });
    qsa('.udp-megamenu__primary-btn', panel).forEach((btn, i) => {
        btn.setAttribute('aria-expanded', i === idx ? 'true' : 'false');
    });
    qsa('[data-udp-megamenu-detail]', panel).forEach((el) => {
        const detailIdx = parseInt(el.getAttribute('data-udp-megamenu-detail'), 10);
        const isActive = detailIdx === idx;
        el.classList.toggle('udp-megamenu__detail--active', isActive);
        el.hidden = !isActive;
    });
}

export function initMegaMenu() {
    const panel = qs('#udp-megamenu-panel');
    const toggle = qs('[data-udp-megamenu-toggle]');
    if (!panel || !toggle) return;

    toggle.addEventListener('click', () => setOpen(panel, true));

    const closeBtn = panel.querySelector('[data-udp-megamenu-close]');
    if (closeBtn) closeBtn.addEventListener('click', () => setOpen(panel, false));

    qsa('[data-udp-megamenu-item]', panel).forEach((btn) => {
        const idx = parseInt(btn.getAttribute('data-udp-megamenu-item'), 10);
        btn.addEventListener('click', () => setActiveItem(panel, idx));
        btn.addEventListener('mouseenter', () => setActiveItem(panel, idx));
        btn.addEventListener('focus', () => setActiveItem(panel, idx));
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && STATE.isOpen) {
            setOpen(panel, false);
        }
    });
}
