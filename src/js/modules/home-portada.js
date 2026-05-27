import { qs } from '@utils/dom';

export function initHomePortada() {
    // Scroll-driven animations handle this natively — only run fallback for Firefox.
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
