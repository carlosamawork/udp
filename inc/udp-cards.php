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

/**
 * Convierte un WP_Post (CPT agenda) a la forma Card adaptada para evento.
 * Devuelve null si no hay featured image (igual que noticias).
 *
 * Card shape evento adicional al de noticia:
 *   - fecha_display: human readable "10 de Marzo de 2026"
 *   - hora_display:  del ACF hora_inicio "12:00 hrs"
 *   - lugar:         ACF lugar
 * Eyebrow viene del primer post_tag (case original) — uppercase via CSS.
 */
function udp_card_data_from_agenda( WP_Post $post ): ?array {
    $thumb_id = (int) get_post_thumbnail_id( $post->ID );
    $imagen_url = '';
    $imagen_alt = '';
    $sizes      = array();

    if ( $thumb_id > 0 ) {
        $imagen_url = wp_get_attachment_image_url( $thumb_id, 'medium_large' ) ?: '';
        $imagen_alt = (string) get_post_meta( $thumb_id, '_wp_attachment_image_alt', true );
        $metadata   = wp_get_attachment_metadata( $thumb_id );
        $sizes      = is_array( $metadata ) && isset( $metadata['sizes'] ) ? $metadata['sizes'] : array();
    }

    $eyebrow_text = '';
    $tags = get_the_terms( $post->ID, 'post_tag' );
    if ( ! is_wp_error( $tags ) && ! empty( $tags ) ) {
        $eyebrow_text = $tags[0]->name;
    }

    // get_field('fecha') returns the ACF "Return Format" (human-readable string like "3 Octubre 2017").
    // For reliable ISO parsing use the raw postmeta which always stores Ymd ('20171003').
    $fecha_raw = get_post_meta( $post->ID, 'fecha', true );
    $hora_acf  = function_exists( 'get_field' ) ? get_field( 'hora_inicio', $post->ID ) : '';
    $lugar     = function_exists( 'get_field' ) ? (string) get_field( 'lugar', $post->ID ) : '';

    $fecha_iso = '';
    $fecha_disp = '';
    if ( $fecha_raw ) {
        // ACF date_picker stores dates as Ymd ('20210101') in postmeta.
        // strtotime() does not reliably parse Ymd on all PHP builds, so use DateTime as primary.
        $dt = DateTime::createFromFormat( 'Ymd', $fecha_raw );
        if ( $dt ) {
            $ts = $dt->getTimestamp();
        } else {
            $ts = strtotime( $fecha_raw );
        }
        if ( $ts ) {
            $fecha_iso  = date( 'Y-m-d', $ts );
            $fecha_disp = date_i18n( 'j \d\e F \d\e Y', $ts );
        }
    }

    $hora_disp = '';
    if ( $hora_acf ) {
        $ts_h = strtotime( $hora_acf );
        if ( $ts_h ) {
            $hora_disp = date_i18n( 'H:i', $ts_h ) . ' hrs';
        }
    }

    return array(
        'post_id'       => (int) $post->ID,
        'eyebrow'       => $eyebrow_text,
        'eyebrow_color' => 'yellow',
        'titulo'        => get_the_title( $post ),
        'imagen'        => array(
            'id'    => $thumb_id,
            'url'   => $imagen_url,
            'alt'   => $imagen_alt,
            'sizes' => $sizes,
        ),
        'fecha'         => $fecha_iso,
        'fecha_display' => $fecha_disp,
        'hora_display'  => $hora_disp,
        'lugar'         => $lugar,
        'href'          => get_permalink( $post ),
        'target'        => '',
    );
}

/**
 * Wrapper sobre WP_Query para archive Agenda.
 * Order por meta `fecha` ASC (próximos primero).
 */
function udp_query_agenda( array $filters ): array {
    $facultad = (int) ( $filters['facultad'] ?? 0 );
    $year     = (int) ( $filters['year']     ?? 0 );
    $s        = trim( (string) ( $filters['s'] ?? '' ) );
    $paged    = max( 1, (int) ( $filters['paged'] ?? 1 ) );
    $limit    = max( 1, (int) ( $filters['limit'] ?? 6 ) );
    $exclude  = isset( $filters['exclude'] ) && is_array( $filters['exclude'] ) ? array_map( 'intval', $filters['exclude'] ) : array();
    $order       = strtoupper( (string) ( $filters['order']       ?? 'DESC' ) );
    $fecha_desde = trim( (string)       ( $filters['fecha_desde'] ?? '' ) );

    $args = array(
        'post_type'      => 'agenda',
        'post_status'    => 'publish',
        'posts_per_page' => $limit,
        'paged'          => $paged,
        'meta_key'       => 'fecha',
        'orderby'        => 'meta_value',
        'order'          => in_array( $order, [ 'ASC', 'DESC' ], true ) ? $order : 'DESC',
    );

    $tax_query = array();
    if ( $facultad > 0 ) {
        $tax_query[] = array( 'taxonomy' => 'facultad', 'field' => 'term_id', 'terms' => array( $facultad ) );
    }
    if ( ! empty( $tax_query ) ) {
        $args['tax_query'] = $tax_query;
    }

    $meta_q = [];
    if ( $year > 0 ) {
        $meta_q[] = [
            'key'     => 'fecha',
            'value'   => sprintf( '%04d', $year ),
            'compare' => 'LIKE',
        ];
    }
    if ( $fecha_desde !== '' ) {
        $meta_q[] = [
            'key'     => 'fecha',
            'value'   => $fecha_desde,
            'compare' => '>=',
            'type'    => 'CHAR',
        ];
    }
    if ( ! empty( $meta_q ) ) {
        $args['meta_query'] = count( $meta_q ) > 1
            ? array_merge( [ 'relation' => 'AND' ], $meta_q )
            : $meta_q;
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
        $card = udp_card_data_from_agenda( $post );
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

/**
 * Convierte WP_Post (concurso-academico) a Card shape.
 * Eyebrow desde primer término de `facultad`. Color hardcoded yellow.
 * Devuelve null si no hay featured image (igual que noticias).
 */
function udp_card_data_from_concurso( WP_Post $post ): ?array {
    $thumb_id = (int) get_post_thumbnail_id( $post->ID );
    $imagen_url = '';
    $imagen_alt = '';
    $sizes      = array();

    if ( $thumb_id > 0 ) {
        $imagen_url = wp_get_attachment_image_url( $thumb_id, 'medium_large' ) ?: '';
        $imagen_alt = (string) get_post_meta( $thumb_id, '_wp_attachment_image_alt', true );
        $metadata   = wp_get_attachment_metadata( $thumb_id );
        $sizes      = is_array( $metadata ) && isset( $metadata['sizes'] ) ? $metadata['sizes'] : array();
    }
    if ( ! $imagen_url ) {
        return null;
    }

    $eyebrow_text = '';
    $facultades = get_the_terms( $post->ID, 'facultad' );
    if ( ! is_wp_error( $facultades ) && ! empty( $facultades ) ) {
        $eyebrow_text = $facultades[0]->name;
    }

    return array(
        'post_id'       => (int) $post->ID,
        'eyebrow'       => $eyebrow_text,
        'eyebrow_color' => 'yellow',
        'titulo'        => get_the_title( $post ),
        'imagen'        => array(
            'id'    => $thumb_id,
            'url'   => $imagen_url,
            'alt'   => $imagen_alt,
            'sizes' => $sizes,
        ),
        'fecha'         => get_the_date( 'Y-m-d', $post ),
        'href'          => get_permalink( $post ),
        'target'        => '',
    );
}

/**
 * Wrapper sobre WP_Query para archive Concursos académicos.
 */
function udp_query_concursos( array $filters ): array {
    $facultad = (int) ( $filters['facultad'] ?? 0 );
    $s        = trim( (string) ( $filters['s'] ?? '' ) );
    $paged    = max( 1, (int) ( $filters['paged'] ?? 1 ) );
    $limit    = max( 1, (int) ( $filters['limit'] ?? 6 ) );

    $args = array(
        'post_type'      => 'concurso-academico',
        'post_status'    => 'publish',
        'posts_per_page' => $limit,
        'paged'          => $paged,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    if ( $facultad > 0 ) {
        $args['tax_query'] = array(
            array( 'taxonomy' => 'facultad', 'field' => 'term_id', 'terms' => array( $facultad ) ),
        );
    }
    if ( $s !== '' ) {
        $args['s'] = $s;
    }
    if ( empty( $args['need_pagination'] ) ) {
        $args['no_found_rows'] = ! ( $filters['need_pagination'] ?? false );
    }

    $q = new WP_Query( $args );

    $cards = array();
    foreach ( $q->posts as $post ) {
        $card = udp_card_data_from_concurso( $post );
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

/**
 * Devuelve los años con entradas de calendario (DESC). Cache 1 día.
 *
 * @return int[]
 */
function udp_get_calendario_years(): array {
    $cache = get_transient( 'udp_calendario_years' );
    if ( $cache !== false ) {
        return $cache;
    }
    global $wpdb;
    $years = $wpdb->get_col( "
        SELECT DISTINCT LEFT(pm.meta_value, 4) AS y
        FROM {$wpdb->postmeta} pm
        JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = 'fecha'
          AND pm.meta_value REGEXP '^[0-9]{4}'
          AND p.post_type = 'calendario'
          AND p.post_status = 'publish'
        ORDER BY y DESC
    " );
    $years = array_map( 'intval', (array) $years );
    set_transient( 'udp_calendario_years', $years, DAY_IN_SECONDS );
    return $years;
}

/**
 * Convierte WP_Post (calendario) a Entry shape.
 * SIEMPRE devuelve array (no null) — no requiere featured image.
 *
 * @return array
 */
function udp_calendario_data_from_post( WP_Post $post ): array {
    $fecha_raw       = (string) get_post_meta( $post->ID, 'fecha', true );
    $fecha_amistosa  = (string) get_post_meta( $post->ID, 'fecha_amistosa', true );
    $destacado_raw   = get_post_meta( $post->ID, 'destacado', true );

    $fecha_iso = '';
    $fecha_disp_default = '';
    if ( $fecha_raw ) {
        $dt = DateTime::createFromFormat( 'Ymd', $fecha_raw );
        if ( ! $dt ) {
            $ts = strtotime( $fecha_raw );
            if ( $ts ) {
                $dt = ( new DateTime() )->setTimestamp( $ts );
            }
        }
        if ( $dt ) {
            $fecha_iso          = $dt->format( 'Y-m-d' );
            $fecha_disp_default = date_i18n( 'j \d\e F', $dt->getTimestamp() );
        }
    }

    $fecha_display = $fecha_amistosa !== '' ? $fecha_amistosa : $fecha_disp_default;

    $tipo_name = '';
    $tipos = get_the_terms( $post->ID, 'tipo-udp' );
    if ( ! is_wp_error( $tipos ) && ! empty( $tipos ) ) {
        $tipo_name = $tipos[0]->name;
    }

    $excerpt = wp_strip_all_tags( get_the_excerpt( $post ) );
    if ( strlen( $excerpt ) > 160 ) {
        $excerpt = mb_substr( $excerpt, 0, 157 ) . '…';
    }

    return array(
        'post_id'       => (int) $post->ID,
        'titulo'        => get_the_title( $post ),
        'fecha'         => $fecha_iso,
        'fecha_display' => $fecha_display,
        'destacado'     => (bool) $destacado_raw,
        'descripcion'   => $excerpt,
        'tipo'          => $tipo_name,
        'href_ics'      => add_query_arg( 'udp_ics', $post->ID, home_url( '/' ) ),
    );
}

/**
 * Wrapper sobre WP_Query para archive Calendario.
 * NO PAGINATES — devuelve TODAS las entries del año, agrupadas por mes.
 *
 * @return array { entries_by_month: array<string,array>, total: int, year: int }
 */
function udp_query_calendario( array $filters ): array {
    $publico = (int) ( $filters['publico'] ?? 0 );
    $tipo    = (int) ( $filters['tipo']    ?? 0 );
    $year    = (int) ( $filters['year']    ?? (int) date( 'Y' ) );
    $s       = trim( (string) ( $filters['s'] ?? '' ) );

    $args = array(
        'post_type'      => 'calendario',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_key'       => 'fecha',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'meta_query'     => array(
            array(
                'key'     => 'fecha',
                'value'   => sprintf( '%04d', $year ),
                'compare' => 'LIKE',
            ),
        ),
        'no_found_rows'  => true,
    );

    $tax_query = array();
    if ( $publico > 0 ) {
        $tax_query[] = array( 'taxonomy' => 'publico-udp', 'field' => 'term_id', 'terms' => array( $publico ) );
    }
    if ( $tipo > 0 ) {
        $tax_query[] = array( 'taxonomy' => 'tipo-udp', 'field' => 'term_id', 'terms' => array( $tipo ) );
    }
    if ( count( $tax_query ) > 1 ) {
        $tax_query['relation'] = 'AND';
    }
    if ( ! empty( $tax_query ) ) {
        $args['tax_query'] = $tax_query;
    }

    if ( $s !== '' ) {
        $args['s'] = $s;
    }

    $q = new WP_Query( $args );

    $entries_by_month = array();
    foreach ( $q->posts as $post ) {
        $entry = udp_calendario_data_from_post( $post );
        if ( ! $entry['fecha'] ) {
            continue;
        }
        $month_key = substr( $entry['fecha'], 5, 2 );
        if ( ! isset( $entries_by_month[ $month_key ] ) ) {
            $entries_by_month[ $month_key ] = array();
        }
        $entries_by_month[ $month_key ][] = $entry;
    }
    ksort( $entries_by_month );

    return array(
        'entries_by_month' => $entries_by_month,
        'total'            => count( $q->posts ),
        'year'             => $year,
    );
}

/**
 * Convierte un término de taxonomía 'facultad' a Card mosaic shape.
 * Image desde ACF imagen_taxonomia (puede ser null → placeholder).
 * Link prefiere página dedicada (match por exact title); fallback a term archive.
 *
 * @return array { titulo, imagen, color, href, has_image }
 */
function udp_card_data_from_facultad_term( WP_Term $term ): array {
    $imagen = function_exists( 'get_field' ) ? get_field( 'imagen_taxonomia', $term ) : null;
    $color  = function_exists( 'get_field' ) ? (string) get_field( 'color', $term ) : '';

    $imagen_url = '';
    $imagen_alt = '';
    if ( is_array( $imagen ) ) {
        $imagen_url = $imagen['sizes']['medium_large'] ?? ( $imagen['url'] ?? '' );
        $imagen_alt = $imagen['alt'] ?? '';
    } elseif ( is_numeric( $imagen ) && (int) $imagen > 0 ) {
        $imagen_url = wp_get_attachment_image_url( (int) $imagen, 'medium_large' ) ?: '';
        $imagen_alt = (string) get_post_meta( (int) $imagen, '_wp_attachment_image_alt', true );
    } elseif ( is_string( $imagen ) && filter_var( $imagen, FILTER_VALIDATE_URL ) ) {
        // ACF may return a plain URL string when the field return format is 'url'.
        $imagen_url = $imagen;
    }

    // Link: página dedicada exacta por título → fallback a term archive
    $page = get_page_by_title( $term->name, OBJECT, 'page' );
    if ( $page && $page->post_status === 'publish' ) {
        $href = get_permalink( $page );
    } else {
        $term_link = get_term_link( $term );
        $href = is_wp_error( $term_link ) ? '#' : $term_link;
    }

    return array(
        'term_id'   => (int) $term->term_id,
        'titulo'    => $term->name,
        'imagen'    => array( 'url' => $imagen_url, 'alt' => $imagen_alt ),
        'color'     => $color,
        'href'      => $href,
        'has_image' => $imagen_url !== '',
    );
}

/**
 * Convierte WP_Post (carrera-udp) a Card mosaic shape con eyebrow facultad.
 * href = link_directo si existe, sino permalink. Image opcional (placeholder).
 */
function udp_card_data_from_carrera( WP_Post $post ): array {
    $thumb_id = (int) get_post_thumbnail_id( $post->ID );
    $imagen_url = '';
    $imagen_alt = '';
    if ( $thumb_id > 0 ) {
        $imagen_url = wp_get_attachment_image_url( $thumb_id, 'medium_large' ) ?: '';
        $imagen_alt = (string) get_post_meta( $thumb_id, '_wp_attachment_image_alt', true );
    }

    $eyebrow_text = '';
    $facultades = get_the_terms( $post->ID, 'facultad' );
    if ( ! is_wp_error( $facultades ) && ! empty( $facultades ) ) {
        $eyebrow_text = $facultades[0]->name;
    }

    $link_directo = (string) get_post_meta( $post->ID, 'link_directo', true );
    $href   = $link_directo ?: get_permalink( $post );
    $target = $link_directo ? '_blank' : '';

    return array(
        'post_id'   => (int) $post->ID,
        'titulo'    => get_the_title( $post ),
        'imagen'    => array( 'url' => $imagen_url, 'alt' => $imagen_alt ),
        'has_image' => $imagen_url !== '',
        'eyebrow'   => $eyebrow_text,
        'href'      => $href,
        'target'    => $target,
    );
}

/**
 * Wrapper sobre WP_Query para archive Carreras.
 */
function udp_query_carreras( array $filters ): array {
    $facultad = (int) ( $filters['facultad'] ?? 0 );
    $s        = trim( (string) ( $filters['s'] ?? '' ) );

    $args = array(
        'post_type'      => 'carrera-udp',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'no_found_rows'  => true,
    );

    if ( $facultad > 0 ) {
        $args['tax_query'] = array(
            array( 'taxonomy' => 'facultad', 'field' => 'term_id', 'terms' => array( $facultad ) ),
        );
    }
    if ( $s !== '' ) {
        $args['s'] = $s;
    }

    $q = new WP_Query( $args );

    $cards = array();
    foreach ( $q->posts as $post ) {
        $cards[] = udp_card_data_from_carrera( $post );
    }
    return $cards;
}

/**
 * Devuelve cards de todos los términos de la taxonomía 'facultad'.
 * Order alfabético, hide_empty FALSE para incluir todos.
 *
 * @return array<int,array>
 */
function udp_query_facultades(): array {
    $terms = get_terms( array(
        'taxonomy'   => 'facultad',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ) );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return array();
    }

    $cards = array();
    foreach ( $terms as $term ) {
        $cards[] = udp_card_data_from_facultad_term( $term );
    }
    return $cards;
}

/**
 * Convierte WP_Post (centro-udp) a Card mosaic shape.
 * href = link_externo (target=_blank) si existe, sino permalink.
 */
function udp_card_data_from_centro( WP_Post $post ): array {
    $thumb_id = (int) get_post_thumbnail_id( $post->ID );
    $imagen_url = '';
    $imagen_alt = '';
    if ( $thumb_id > 0 ) {
        $imagen_url = wp_get_attachment_image_url( $thumb_id, 'medium_large' ) ?: '';
        $imagen_alt = (string) get_post_meta( $thumb_id, '_wp_attachment_image_alt', true );
    }

    $link_externo = (string) get_post_meta( $post->ID, 'link_externo', true );
    $href   = $link_externo ?: get_permalink( $post );
    $target = $link_externo ? '_blank' : '';

    return array(
        'post_id'   => (int) $post->ID,
        'titulo'    => get_the_title( $post ),
        'imagen'    => array( 'url' => $imagen_url, 'alt' => $imagen_alt ),
        'has_image' => $imagen_url !== '',
        'eyebrow'   => '',
        'href'      => $href,
        'target'    => $target,
    );
}

/**
 * Wrapper sobre WP_Query para archive Centros. Sin filtros (como Facultades).
 */
function udp_query_centros(): array {
    $q = new WP_Query( array(
        'post_type'      => 'centro-udp',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'no_found_rows'  => true,
    ) );

    $cards = array();
    foreach ( $q->posts as $post ) {
        $cards[] = udp_card_data_from_centro( $post );
    }
    return $cards;
}

/**
 * Flat list de entries de calendario para uso en bloques (no agrupa por mes).
 *
 * @param array $filters {
 *     @type int    $year     YYYY. Default año actual.
 *     @type string $mes      '01'..'12' o '' para todos.
 *     @type int    $tipo     term_id tipo-udp.
 *     @type int    $publico  term_id publico-udp.
 *     @type int    $limit    Default 10, max 30.
 * }
 * @return array<int,array> Lista plana de entries (shape igual a udp_calendario_data_from_post).
 */
function udp_query_calendario_flat( array $filters ): array {
    $year    = (int) ( $filters['year']    ?? (int) date( 'Y' ) );
    $mes     = (string) ( $filters['mes']  ?? '' );
    $tipo    = (int) ( $filters['tipo']    ?? 0 );
    $publico = (int) ( $filters['publico'] ?? 0 );
    $limit   = max( 1, min( 30, (int) ( $filters['limit'] ?? 10 ) ) );

    // Build meta_query value: 'YYYY' or 'YYYYMM'
    $meta_value = sprintf( '%04d', $year );
    if ( $mes !== '' && preg_match( '/^(0[1-9]|1[0-2])$/', $mes ) ) {
        $meta_value .= $mes;
    }

    $args = array(
        'post_type'      => 'calendario',
        'post_status'    => 'publish',
        'posts_per_page' => $limit,
        'meta_key'       => 'fecha',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'no_found_rows'  => true,
        'meta_query'     => array(
            array(
                'key'     => 'fecha',
                'value'   => $meta_value,
                'compare' => 'LIKE',
            ),
        ),
    );

    $tax_query = array();
    if ( $tipo > 0 ) {
        $tax_query[] = array( 'taxonomy' => 'tipo-udp', 'field' => 'term_id', 'terms' => array( $tipo ) );
    }
    if ( $publico > 0 ) {
        $tax_query[] = array( 'taxonomy' => 'publico-udp', 'field' => 'term_id', 'terms' => array( $publico ) );
    }
    if ( count( $tax_query ) > 1 ) {
        $tax_query['relation'] = 'AND';
    }
    if ( ! empty( $tax_query ) ) {
        $args['tax_query'] = $tax_query;
    }

    $q = new WP_Query( $args );

    $entries = array();
    foreach ( $q->posts as $post ) {
        $entry = udp_calendario_data_from_post( $post );
        if ( ! empty( $entry['fecha'] ) ) {
            $entries[] = $entry;
        }
    }

    return $entries;
}
