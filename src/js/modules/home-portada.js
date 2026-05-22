/**
 * Home — Portada: clip-path reveal via IntersectionObserver
 * Fallback para browsers sin soporte de animation-timeline: scroll().
 */
import { qs } from '@utils/dom';

export function initHomePortada() {
    if ( CSS.supports( 'animation-timeline', 'scroll()' ) ) return;

    const media = qs( '.js-portada-media' );
    if ( ! media ) return;

    const observer = new IntersectionObserver(
        ( entries ) => {
            entries.forEach( ( entry ) => {
                if ( entry.isIntersecting ) {
                    entry.target.classList.add( 'is-visible' );
                    observer.unobserve( entry.target );
                }
            } );
        },
        { threshold: 0.1 }
    );

    observer.observe( media );
}
