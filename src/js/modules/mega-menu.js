/**
 * Mega-menu — open/close + active item state machine.
 *
 * Col-1 hover/click → switches which section detail panel is visible.
 * Col-2 hover       → shows/hides sub-panels in col-3.
 */
import { qs, qsa } from '@utils/dom';

const STATE = {
	isOpen: false,
	activeIdx: -1, // -1 = ninguna sección activa al abrir
	lastFocused: null,
};

const ANIM_DURATION = 220; // ms — debe coincidir con udp-megamenu-fadeout

function setOpen(panel, open) {
	if (!panel) return;

	const toggles = qsa('[data-udp-megamenu-toggle]');

	if (open) {
		STATE.isOpen = true;
		panel.hidden = false;
		document.documentElement.classList.add('udp-megamenu-open');
		document.body.classList.add('udp-megamenu-open');
		toggles.forEach(t => t.setAttribute('aria-expanded', 'true'));
		STATE.lastFocused = document.activeElement;
		const closeBtn = panel.querySelector('[data-udp-megamenu-close]');
		if (closeBtn) closeBtn.focus();
		// Re-dispara la animación del círculo del close mobile en cada apertura
		const mobileCircle = document.querySelector('.udp-mobile-nav__close-circle');
		if (mobileCircle) {
			mobileCircle.classList.remove('udp-is-entering');
			void mobileCircle.offsetWidth; // fuerza reflow
			mobileCircle.classList.add('udp-is-entering');
		}
	} else {
		// Fade-out antes de ocultar
		panel.classList.add('udp-megamenu--closing');
		setTimeout(() => {
			panel.classList.remove('udp-megamenu--closing');
			panel.hidden = true;
			STATE.isOpen = false;
			STATE.activeIdx = -1;

			// Reset DOM: quitar --active de col-1, ocultar todos los detail panels
			qsa('.udp-megamenu__primary-item--active', panel).forEach(el =>
				el.classList.remove('udp-megamenu__primary-item--active')
			);
			qsa('.udp-megamenu__primary-btn', panel).forEach(btn =>
				btn.setAttribute('aria-expanded', 'false')
			);
			qsa('[data-udp-megamenu-detail]', panel).forEach(el => {
				el.classList.remove('udp-megamenu__detail--active');
				el.hidden = true;
				clearSubPanels(el);
			});
			document.documentElement.classList.remove('udp-megamenu-open');
			document.body.classList.remove('udp-megamenu-open');
			toggles.forEach(t => t.setAttribute('aria-expanded', 'false'));
			if (STATE.lastFocused && typeof STATE.lastFocused.focus === 'function') {
				STATE.lastFocused.focus();
			}
		}, ANIM_DURATION);
	}
}

function clearSubPanels(detail) {
	if (!detail) return;
	qsa('[data-udp-sub-panel]', detail).forEach(p => { p.hidden = true; });
	qsa('.udp-megamenu__submenu-item--active', detail).forEach(li => {
		li.classList.remove('udp-megamenu__submenu-item--active');
	});
}

function setSubPanel(detail, subIdx) {
	if (!detail) return;
	// Clear previous active item
	qsa('.udp-megamenu__submenu-item--active', detail).forEach(li => {
		li.classList.remove('udp-megamenu__submenu-item--active');
	});
	// Show matching sub-panel, hide others
	let found = false;
	qsa('[data-udp-sub-panel]', detail).forEach(p => {
		const match = parseInt(p.getAttribute('data-udp-sub-panel'), 10) === subIdx;
		p.hidden = !match;
		if (match) found = true;
	});
	// Mark active submenu item
	if (found) {
		const li = detail.querySelector(`[data-udp-sub-idx="${subIdx}"]`);
		if (li) li.classList.add('udp-megamenu__submenu-item--active');
	}
}

function setActiveItem(panel, idx) {
	if (!panel) return;
	STATE.activeIdx = idx;

	// Clear all sub-panels before switching section
	qsa('[data-udp-megamenu-detail]', panel).forEach(d => clearSubPanels(d));

	qsa('.udp-megamenu__primary-item', panel).forEach((el, i) => {
		el.classList.toggle('udp-megamenu__primary-item--active', i === idx);
	});
	qsa('.udp-megamenu__primary-btn', panel).forEach((btn, i) => {
		btn.setAttribute('aria-expanded', i === idx ? 'true' : 'false');
	});
	qsa('[data-udp-megamenu-detail]', panel).forEach(el => {
		const detailIdx = parseInt(el.getAttribute('data-udp-megamenu-detail'), 10);
		const isActive = detailIdx === idx;
		el.classList.toggle('udp-megamenu__detail--active', isActive);
		el.hidden = !isActive;
	});
}

export function initMegaMenu() {
	const panel = qs('#udp-megamenu-panel');
	const toggles = qsa('[data-udp-megamenu-toggle]');
	if (!panel || !toggles.length) return;

	toggles.forEach(t => t.addEventListener('click', () => setOpen(panel, true)));

	qsa('[data-udp-megamenu-close]').forEach(btn => {
		btn.addEventListener('click', () => setOpen(panel, false));
	});

	// Col-1: section switching
	qsa('[data-udp-megamenu-item]', panel).forEach(btn => {
		const idx = parseInt(btn.getAttribute('data-udp-megamenu-item'), 10);
		btn.addEventListener('click', () => setActiveItem(panel, idx));
		btn.addEventListener('mouseenter', () => setActiveItem(panel, idx));
		btn.addEventListener('focus', () => setActiveItem(panel, idx));
	});

	// Col-2: hover abre sub-panel col-3
	panel.addEventListener('mouseenter', e => {
		const li = e.target.closest('[data-udp-sub-idx]');
		if (!li) return;
		const detail = li.closest('[data-udp-megamenu-detail]');
		const subIdx = parseInt(li.getAttribute('data-udp-sub-idx'), 10);
		setSubPanel(detail, subIdx);
	}, true);

	// Cerrar menú al hacer click en un link de la misma página (anchors o mismo pathname)
	panel.addEventListener('click', e => {
		const a = e.target.closest('a[href]');
		if (!a || !STATE.isOpen) return;
		const href = a.getAttribute('href');
		const isSamePage =
			href.startsWith('#') ||
			(a.hostname === window.location.hostname && a.pathname === window.location.pathname);
		if (isSamePage) setOpen(panel, false);
	});

	document.addEventListener('keydown', e => {
		if (e.key === 'Escape' && STATE.isOpen) {
			setOpen(panel, false);
		}
	});
}
