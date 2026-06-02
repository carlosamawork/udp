/**
 * Buscador del header — panel AJAX con debounce
 *
 * Apertura:  click en .udp-top-bar__search
 * Cierre:    click en __search-close | ESC | click fuera del panel
 * Búsqueda:  debounce 400ms desde primer carácter, cancela request anterior
 *
 * @package Starter_Theme
 */
import { qs } from '@utils/dom';
import { ajax } from '@utils/ajax';

export function initSearch() {
    let debounceTimer    = null;
    let abortController  = null;

    const topBar    = qs( '.udp-top-bar' );
    const trigger   = qs( '.udp-top-bar__search' );
    const searchBar = qs( '.udp-top-bar__search-bar' );
    const input     = qs( '.udp-top-bar__search-input' );
    const closeBtn  = qs( '.udp-top-bar__search-close' );
    const results   = qs( '#udp-search-results' );

    if ( ! topBar || ! trigger || ! searchBar || ! input || ! closeBtn || ! results ) return;

    // ── Apertura ──────────────────────────────────────────────────────────

    trigger.addEventListener( 'click', openSearch );

    function openSearch() {
        topBar.classList.add( 'udp-search-open' );
        searchBar.hidden = false;
        document.body.classList.add( 'udp-search-overlay' );
        input.focus();
    }

    // ── Cierre ────────────────────────────────────────────────────────────

    closeBtn.addEventListener( 'click', closeSearch );

    document.addEventListener( 'keydown', ( e ) => {
        if ( e.key === 'Escape' && topBar.classList.contains( 'udp-search-open' ) ) {
            closeSearch();
        }
    } );

    document.addEventListener( 'click', ( e ) => {
        if (
            topBar.classList.contains( 'udp-search-open' ) &&
            ! topBar.contains( e.target ) &&
            ! results.contains( e.target )
        ) {
            closeSearch();
        }
    } );

    function closeSearch() {
        topBar.classList.remove( 'udp-search-open', 'udp-search-has-text' );
        searchBar.hidden = true;
        results.hidden   = true;
        results.innerHTML = '';
        input.value = '';
        document.body.classList.remove( 'udp-search-overlay' );
        clearTimeout( debounceTimer );
        debounceTimer = null;
        if ( abortController ) abortController.abort();
        abortController = null;
        trigger.focus();
    }

    // ── Input ─────────────────────────────────────────────────────────────

    input.addEventListener( 'input', () => {
        const q = input.value.trim();
        topBar.classList.toggle( 'udp-search-has-text', q.length > 0 );

        clearTimeout( debounceTimer );
        if ( abortController ) abortController.abort();

        if ( q === '' ) {
            results.hidden    = true;
            results.innerHTML = '';
            return;
        }

        debounceTimer = setTimeout( () => fetchResults( q ), 400 );
    } );

    // ── Fetch ─────────────────────────────────────────────────────────────

    async function fetchResults( q ) {
        results.innerHTML = '<p class="udp-search-results__loading">Buscando…</p>';
        results.hidden    = false;

        abortController = new AbortController();

        const response = await ajax(
            'udp_search',
            { q },
            { signal: abortController.signal }
        );

        if ( ! response ) return; // abortado o error de red

        if ( ! response.success || ! response.data.sections.length ) {
            results.innerHTML = `<p class="udp-search-results__empty">No se encontraron resultados para <strong>«${ escHtml( q ) }»</strong></p>`;
            return;
        }

        render( response.data.sections );
    }

    // ── Render ────────────────────────────────────────────────────────────

    function render( sections ) {
        results.innerHTML = '';

        sections.forEach( ( { label, items } ) => {
            const section = document.createElement( 'div' );
            section.className = 'udp-search-results__section';

            const heading = document.createElement( 'p' );
            heading.className   = 'udp-search-results__label';
            heading.textContent = label;
            section.appendChild( heading );

            const grid = document.createElement( 'div' );
            grid.className = 'udp-search-results__grid';

            items.forEach( ( { title, url } ) => {
                const card = document.createElement( 'a' );
                card.className = 'udp-search-card';
                card.href      = url;
                card.innerHTML = `<span class="udp-search-card__title">${ escHtml( title ) }</span><span class="udp-search-card__arrow" aria-hidden="true">→</span>`;
                grid.appendChild( card );
            } );

            section.appendChild( grid );
            results.appendChild( section );
        } );

        results.hidden = false;
    }

    // ── Utils ─────────────────────────────────────────────────────────────

    function escHtml( str ) {
        return str
            .replace( /&/g, '&amp;' )
            .replace( /</g, '&lt;'  )
            .replace( />/g, '&gt;'  )
            .replace( /"/g, '&quot;' );
    }
}
