import { qs } from '@utils/dom';

export function initHomePortada() {
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
