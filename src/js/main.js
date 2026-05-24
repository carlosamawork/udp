/**
 * Starter BS5 - JavaScript Entry Point
 *
 * Este archivo es el punto de entrada principal de Vite.
 * Importa el SCSS y todos los módulos JS necesarios.
 *
 * Para añadir una librería npm:
 *   npm install nombre-librería
 *   import NombreLibreria from 'nombre-libreria';
 */

// --------------------------------------------------------------------------
// 1. SCSS (Vite lo procesa y genera el CSS)
// --------------------------------------------------------------------------
import '@scss/main.scss';

// --------------------------------------------------------------------------
// 2. Bootstrap JS (importa solo los componentes que necesites)
// --------------------------------------------------------------------------
// Opción A: Importar todo Bootstrap (más sencillo)
import * as bootstrap from 'bootstrap';

// Opción B: Importar solo lo necesario (mejor rendimiento)
// import { Collapse, Dropdown, Modal, Offcanvas, Tooltip } from 'bootstrap';

// Exportar bootstrap globalmente para uso en templates PHP si hace falta
window.bootstrap = bootstrap;

// --------------------------------------------------------------------------
// 3. MÓDULOS JS DEL TEMA
// --------------------------------------------------------------------------
import { initNavbar } from '@modules/navbar';
import { initSmoothScroll } from '@modules/smooth-scroll';
import { initScrollAnimations } from '@modules/scroll-animations';
import { initMobileMenu } from '@modules/mobile-menu';
import { initSectionLandingSwiper } from '@modules/section-landing-swiper';
import { initSinglePostGallery } from '@modules/single-post-gallery';
import { initCalendarioActiveMonth } from '@modules/calendario-active-month';
import { initBlockImageGallery } from '@modules/block-image-gallery';
import { initBlockAccordion } from '@modules/block-accordion';
import { initMegaMenu } from '@modules/mega-menu';
import { initHomePortada }    from '@modules/home-portada';
import { initHomeNoticias }   from '@modules/home-noticias';
import { initHomeCulturaUdp } from '@modules/home-cultura-udp';
import { initHomeCulturaDigital } from '@modules/home-cultura-digital';
import { initHomeInnovacion }     from '@modules/home-innovacion';
import { initHomeCifras }        from '@modules/home-cifras';

// --------------------------------------------------------------------------
// 4. UTILS
// --------------------------------------------------------------------------
import { ajax } from '@utils/ajax';
import { domReady } from '@utils/dom';

// Exponer el helper AJAX globalmente
window.starterAjax = ajax;

// --------------------------------------------------------------------------
// 5. INICIALIZACIÓN
// --------------------------------------------------------------------------
domReady(() => {
    initNavbar();
    initSmoothScroll();
    initScrollAnimations();
    initMobileMenu();
    initSectionLandingSwiper();
    initSinglePostGallery();
    initCalendarioActiveMonth();
    initBlockImageGallery();
    initBlockAccordion();
    initMegaMenu();
    initHomePortada();
    initHomeNoticias();
    initHomeCulturaUdp();
    initHomeCulturaDigital();
    initHomeInnovacion();
    initHomeCifras();

    console.log('[StarterBS5] Theme initialized');
});
