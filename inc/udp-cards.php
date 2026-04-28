<?php
/**
 * Card data helpers
 *
 * Funciones para normalizar data de WP_Posts (o repeaters manuales) al shape
 * `Card` que consume el partial `card-noticia.php`. El helper público
 * udp_query_cards() es reutilizable por blocks (modo limit) y archives
 * (modo paged).
 *
 * Forma `Card`:
 *   [
 *     'eyebrow'       => string,  // 'INTERNACIONAL' o ''
 *     'eyebrow_color' => string,  // 'yellow' | 'red' | 'blue' | ''
 *     'titulo'        => string,  // required
 *     'imagen'        => array,   // ACF image array (id, url, alt, sizes)
 *     'fecha'         => string,  // 'YYYY-MM-DD' o ''
 *     'href'          => string,  // permalink o link.url
 *     'target'        => string,  // '_blank' o ''
 *   ]
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Formatea una fecha ISO (YYYY-MM-DD) a 'DD / MM / YYYY' para display.
 */
function udp_card_format_date( string $iso ): string {
    if ( ! $iso ) {
        return '';
    }
    $ts = strtotime( $iso );
    return $ts ? date_i18n( 'd / m / Y', $ts ) : '';
}

/**
 * Devuelve eyebrow ['text' => ..., 'color' => ...] desde el primer término
 * de la taxonomía 'category' del post. Color fijo 'yellow' en F4a — pendiente
 * de implementar color por término en una iteración futura.
 *
 * @return array { text: string, color: string }
 */
function udp_card_eyebrow_from_post( WP_Post $post ): array {
    $terms = get_the_terms( $post->ID, 'category' );
    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return array( 'text' => '', 'color' => '' );
    }
    return array(
        'text'  => $terms[0]->name,
        'color' => 'yellow',
    );
}

/**
 * Convierte un WP_Post a la forma Card. Devuelve null si el post NO tiene
 * featured image (la card requiere imagen — el caller debe filtrar nulls).
 *
 * @return array|null Card array o null.
 */
function udp_card_data_from_post( WP_Post $post ): ?array {
    $thumb_id = get_post_thumbnail_id( $post->ID );
    if ( ! $thumb_id ) {
        return null;
    }

    $imagen_url = wp_get_attachment_image_url( $thumb_id, 'full' );
    if ( ! $imagen_url ) {
        return null;
    }

    $metadata = wp_get_attachment_metadata( $thumb_id );

    $eyebrow = udp_card_eyebrow_from_post( $post );

    return array(
        'post_id'       => (int) $post->ID,
        'eyebrow'       => $eyebrow['text'],
        'eyebrow_color' => $eyebrow['color'],
        'titulo'        => get_the_title( $post ),
        'imagen'        => array(
            'id'    => (int) $thumb_id,
            'url'   => $imagen_url,
            'alt'   => (string) get_post_meta( $thumb_id, '_wp_attachment_image_alt', true ),
            'sizes' => is_array( $metadata ) && isset( $metadata['sizes'] ) ? $metadata['sizes'] : array(),
        ),
        'fecha'         => get_the_date( 'Y-m-d', $post ),
        'href'          => get_permalink( $post ),
        'target'        => '',
    );
}

/**
 * Entry point público. Consulta cards según source y filtros, devuelve
 * cards normalizadas + metadata de paginación.
 *
 * @param array $args {
 *     @type string $source        'manual' | 'post' | 'concurso'.
 *     @type array  $manual_cards  Repeater rows si source=manual.
 *     @type array  $taxonomies    IDs de términos 'category' (solo source=post).
 *     @type int    $limit         Items por página. Default 6.
 *     @type int    $paged         Página 1-based. Default 1.
 *     @type string $orden           'date_desc' | 'date_asc' | 'random'. Default 'date_desc'.
 *     @type bool   $need_pagination Si true, ejecuta SQL_CALC_FOUND_ROWS para
 *                                   poblar `total` y `max_pages`. Default false
 *                                   (block mode no pagina).
 * }
 * @return array { cards: array, total: int, max_pages: int, paged: int }
 */
function udp_query_cards( array $args ): array {
    $source       = $args['source']       ?? 'manual';
    $manual_cards = $args['manual_cards'] ?? array();
    $taxonomies   = $args['taxonomies']   ?? array();
    $limit        = max( 1, (int) ( $args['limit'] ?? 6 ) );
    $paged        = max( 1, (int) ( $args['paged'] ?? 1 ) );
    $orden        = $args['orden'] ?? 'date_desc';

    $cards     = array();
    $total     = 0;

    if ( $source === 'manual' ) {
        foreach ( (array) $manual_cards as $row ) {
            $link = is_array( $row['link'] ?? null ) ? $row['link'] : array();
            $cards[] = array(
                'eyebrow'       => $row['eyebrow'] ?? '',
                'eyebrow_color' => $row['eyebrow_color'] ?? 'yellow',
                'titulo'        => $row['titulo'] ?? '',
                'imagen'        => is_array( $row['imagen'] ?? null ) ? $row['imagen'] : array(),
                'fecha'         => $row['fecha'] ?? '',
                'href'          => $link['url'] ?? '',
                'target'        => $link['target'] ?? '',
            );
        }
        $total = count( $cards );
    } elseif ( in_array( $source, array( 'post', 'concurso' ), true ) ) {
        $post_type = $source === 'post' ? 'post' : 'concurso-academico';

        $orderby_map = array(
            'date_desc' => array( 'orderby' => 'date',     'order' => 'DESC' ),
            'date_asc'  => array( 'orderby' => 'date',     'order' => 'ASC' ),
            'random'    => array( 'orderby' => 'rand',     'order' => 'DESC' ),
        );
        $orderby_args = $orderby_map[ $orden ] ?? $orderby_map['date_desc'];

        $query_args = array(
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'paged'          => $paged,
            'orderby'        => $orderby_args['orderby'],
            'order'          => $orderby_args['order'],
        );

        if ( $source === 'post' && ! empty( $taxonomies ) ) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'category',
                    'field'    => 'term_id',
                    'terms'    => array_map( 'intval', $taxonomies ),
                ),
            );
        }

        if ( empty( $args['need_pagination'] ) ) {
            $query_args['no_found_rows'] = true;
        }

        $q = new WP_Query( $query_args );
        $total = (int) $q->found_posts;

        foreach ( $q->posts as $post ) {
            $card = udp_card_data_from_post( $post );
            if ( $card ) {
                $cards[] = $card;
            }
        }
    }

    $max_pages = $total > 0 ? (int) ceil( $total / $limit ) : 0;

    return array(
        'cards'     => $cards,
        'total'     => $total,
        'max_pages' => $max_pages,
        'paged'     => $paged,
    );
}

/**
 * Devuelve los años con posts publicados (DESC). Cacheado 1 día via transient.
 *
 * @return int[] Array de años (4 dígitos) ordenados DESC.
 */
function udp_get_post_years(): array {
    $cache = get_transient( 'udp_post_years' );
    if ( $cache !== false ) {
        return $cache;
    }
    global $wpdb;
    $years = $wpdb->get_col(
        "SELECT DISTINCT YEAR(post_date) FROM {$wpdb->posts}
         WHERE post_type='post' AND post_status='publish'
         ORDER BY YEAR(post_date) DESC"
    );
    $years = array_map( 'intval', (array) $years );
    set_transient( 'udp_post_years', $years, DAY_IN_SECONDS );
    return $years;
}

/**
 * Wrapper sobre WP_Query especializado en archive de Noticias.
 * Soporta filtros que `udp_query_cards()` no maneja: año + búsqueda.
 *
 * @param array $filters {
 *     @type int    $cat    term_id de category. 0 o ausente = sin filtro.
 *     @type int    $year   Año (YYYY). 0 o ausente = sin filtro.
 *     @type string $s      Texto de búsqueda. '' = sin búsqueda.
 *     @type int    $paged  Página 1-based. Default 1.
 *     @type int    $limit  Posts por página. Default 6.
 * }
 * @return array { cards, total, max_pages, paged } — mismo shape que udp_query_cards.
 */
function udp_query_noticias( array $filters ): array {
    $cat   = (int) ( $filters['cat']   ?? 0 );
    $year  = (int) ( $filters['year']  ?? 0 );
    $s     = trim( (string) ( $filters['s'] ?? '' ) );
    $paged = max( 1, (int) ( $filters['paged'] ?? 1 ) );
    $limit = max( 1, (int) ( $filters['limit'] ?? 6 ) );
    $exclude = isset( $filters['exclude'] ) && is_array( $filters['exclude'] ) ? array_map( 'intval', $filters['exclude'] ) : array();

    $args = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => $limit,
        'paged'          => $paged,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    if ( $cat > 0 ) {
        $args['tax_query'] = array(
            array( 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => array( $cat ) ),
        );
    }

    if ( $year > 0 ) {
        $args['date_query'] = array( array( 'year' => $year ) );
    }

    if ( $s !== '' ) {
        $args['s'] = $s;
    }

    if ( ! empty( $exclude ) ) {
        $args['post__not_in'] = $exclude;
    }

    $q = new WP_Query( $args );

    $cards = array();
    foreach ( $q->posts as $post ) {
        $card = udp_card_data_from_post( $post );
        if ( $card ) {
            $cards[] = $card;
        }
    }

    return array(
        'cards'     => $cards,
        'total'     => (int) $q->found_posts,
        'max_pages' => $q->found_posts > 0 ? (int) ceil( $q->found_posts / $limit ) : 0,
        'paged'     => $paged,
    );
}
