/**
 * Home — Cultura UDP: hover fade de imagen.
 * Al hacer hover sobre un item de la lista, activa la imagen correspondiente
 * con una transición de opacidad CSS.
 */
import { qsa } from '@utils/dom';

export function initHomeCulturaUdp() {
    const section = document.querySelector( '.js-cultura-udp' );
    if ( ! section ) return;

    const items  = qsa( '.js-cultura-item', section );
    const images = qsa( '.js-cultura-img',  section );

    if ( ! items.length || ! images.length ) return;

    const activate = ( item ) => {
        const idx = item.dataset.index;
        items.forEach(  ( el ) => el.classList.toggle( 'is-active', el.dataset.index === idx ) );
        images.forEach( ( el ) => el.classList.toggle( 'is-active', el.dataset.index === idx ) );
    };

    items.forEach( ( item ) => {
        item.addEventListener( 'mouseenter', () => activate( item ) );
        item.addEventListener( 'click',      () => activate( item ) );
    } );
}
