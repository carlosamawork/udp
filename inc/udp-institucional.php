<?php
/**
 * Helpers para el template page-institucional.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Recolecta los anchors a partir del flexible content `sections` del post actual.
 *
 * Cada layout que tenga `anchor_label` no vacío genera una entrada.
 * El layout `back_link` solo aparece si `display_in_anchors=true`.
 * Se prepende un anchor "Inicio" que apunta al hero.
 *
 * @param int|null $post_id Post a consultar; null usa el actual.
 * @return array<int,array{id:string,label:string,icon:?array,order:int,layout_key:string,section_index:?int}>
 */
function udp_institucional_collect_anchors( $post_id = null, $sections = null ) {
    if ( $sections === null ) {
        $sections = udp_institucional_get_sections( $post_id );
    }
    if ( ! is_array( $sections ) ) {
        $sections = array();
    }

    $anchors = array();
    $used_ids = array();

    // Anchor "Inicio" auto al inicio
    $anchors[] = array(
        'id'            => 'section-inicio',
        'label'         => __( 'Inicio', 'starter-theme' ),
        'icon'          => null,
        'order'         => 0,
        'layout_key'    => '__hero__',
        'section_index' => null,
    );
    $used_ids['section-inicio'] = 1;

    $order = 1;
    foreach ( $sections as $i => $section ) {
        $layout = $section['acf_fc_layout'] ?? '';
        $label  = trim( (string) ( $section['anchor_label'] ?? '' ) );

        if ( $label === '' ) {
            continue;
        }

        if ( $layout === 'back_link' && empty( $section['display_in_anchors'] ) ) {
            continue;
        }

        $base_id = 'section-' . sanitize_title( $label );
        $id      = $base_id;
        $suffix  = 2;
        while ( isset( $used_ids[ $id ] ) ) {
            $id = $base_id . '-' . $suffix;
            $suffix++;
        }
        $used_ids[ $id ] = 1;

        $icon = $section['anchor_icon'] ?? null;
        if ( ! is_array( $icon ) ) {
            $icon = null;
        }

        $anchors[] = array(
            'id'            => $id,
            'label'         => $label,
            'icon'          => $icon,
            'order'         => $order,
            'layout_key'    => $layout,
            'section_index' => $i,
        );

        $order++;
    }

    return $anchors;
}

/**
 * Devuelve el id de anchor para una sección dada del flexible content,
 * tomando como referencia el array completo de anchors.
 *
 * Útil dentro de cada partial layout-*.php para obtener su id sin re-derivar.
 *
 * Hace match por `section_index` (índice real del flexible content), no por
 * `order` — porque `order` solo se incrementa para secciones que pasaron los
 * filtros (anchor_label no vacío; back_link visible). Si la sección fue
 * filtrada, no tiene anchor y se devuelve null: el template debe renderizar
 * la sección igual, pero sin id ni entrada en la rail/scrollspy.
 *
 * @param array $anchors       Array completo devuelto por udp_institucional_collect_anchors().
 * @param int   $section_index Índice (0-based) de la sección dentro del flexible content.
 * @return array|null
 */
function udp_institucional_anchor_for_index( array $anchors, $section_index ) {
    foreach ( $anchors as $a ) {
        if ( ( $a['section_index'] ?? null ) === $section_index ) {
            return $a;
        }
    }
    return null;
}

/**
 * Devuelve los datos de la noticia (post) más reciente con imagen destacada,
 * para el widget "Noticias" del sidebar institucional. Cacheado 1h.
 *
 * @return array|null { title, url, image, date, category }
 */
function udp_institucional_latest_noticia() {
    $cache = get_transient( 'udp_inst_latest_noticia' );
    if ( is_array( $cache ) ) {
        // Array vacío = "no hay noticia" cacheado.
        return empty( $cache ) ? null : $cache;
    }

    $posts = get_posts(
        array(
            'post_type'        => 'post',
            'posts_per_page'   => 1,
            'post_status'      => 'publish',
            'meta_query'       => array( array( 'key' => '_thumbnail_id', 'compare' => 'EXISTS' ) ),
            'suppress_filters' => false,
        )
    );

    $data = array();
    if ( $posts ) {
        $p   = $posts[0];
        $cat = get_the_category( $p->ID );
        $data = array(
            'title'    => get_the_title( $p ),
            'url'      => get_permalink( $p ),
            'image'    => get_the_post_thumbnail_url( $p, 'medium_large' ) ?: get_the_post_thumbnail_url( $p, 'large' ),
            'date'     => get_the_date( 'd / m / Y', $p ),
            'category' => ! empty( $cat ) ? $cat[0]->name : '',
        );
    }

    set_transient( 'udp_inst_latest_noticia', $data, HOUR_IN_SECONDS );
    return empty( $data ) ? null : $data;
}

/**
 * Devuelve las secciones efectivas de una página: usa el campo ACF `sections`
 * si está poblado; si no, transforma el contenido legacy (`secciones` + post_content)
 * al shape de los layouts institucionales.
 *
 * @param int|null $post_id
 * @return array
 */
function udp_institucional_get_sections( $post_id = null ) {
    if ( function_exists( 'get_field' ) ) {
        $secs = get_field( 'sections', $post_id );
        if ( is_array( $secs ) && ! empty( $secs ) ) {
            return $secs;
        }
    }
    return udp_institucional_sections_from_legacy( $post_id );
}

/**
 * Parte un bloque `contenido` (HTML) en título (primer heading) + resto HTML.
 *
 * @return array{0:string,1:string} [title, rest_html]
 */
function udp_institucional_parse_heading( $html ) {
    $title = '';
    $rest  = (string) $html;
    if ( preg_match( '/<h[1-6][^>]*>(.*?)<\/h[1-6]>/is', $html, $m ) ) {
        $title = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $m[1] ) ) );
        $rest  = trim( preg_replace( '/<h[1-6][^>]*>.*?<\/h[1-6]>/is', '', $html, 1 ) );
    }
    return array( $title, $rest );
}

/**
 * Transforma el contenido legacy de una página (campo ACF `secciones` del tema
 * antiguo + post_content) al array de secciones que consumen los layouts
 * institucionales (mismo shape que get_field('sections')).
 *
 * Mapeo:
 *   post_content               → intro del primer acordeón (o rich_text suelto)
 *   desplegable                → text_accordion (items colapsables)
 *   contenido + desplegable    → text_accordion (title = encabezado)
 *   contenido + listado_inf    → people_carousel
 *   contenido + links_cuadr*   → cards_dark_row
 *   contenido (suelto)         → rich_text_sidebar
 *   listado_de_informacion     → people_carousel
 *   links_cuadrados(_externos) → cards_dark_row
 *   (otros layouts legacy)     → omitidos
 *   + back_link al padre (si la página tiene padre)
 *
 * @param int|null $post_id
 * @return array
 */
function udp_institucional_sections_from_legacy( $post_id = null ) {
    if ( ! function_exists( 'get_field' ) ) {
        return array();
    }
    $post_id = $post_id ?: get_the_ID();
    if ( ! $post_id ) {
        return array();
    }

    $out  = array();
    $secs = get_field( 'secciones', $post_id );
    $secs = is_array( $secs ) ? $secs : array();
    $pc   = get_post_field( 'post_content', $post_id );
    $pc   = is_string( $pc ) ? trim( $pc ) : '';
    $intro_used      = false;
    $pending_buttons = array(); // botones_con_links_externos → aside de la 1ª sección de texto
    $page_title      = get_the_title( $post_id );

    $personas_from = static function ( $items ) {
        $out = array();
        foreach ( (array) $items as $p ) {
            $nombre = trim( (string) ( $p['titulo'] ?? '' ) );
            if ( $nombre === '' ) {
                continue;
            }
            $out[] = array(
                'foto'   => '',
                'nombre' => $nombre,
                'cargo'  => trim( (string) ( $p['subtitulo'] ?? '' ) ),
                'email'  => '',
            );
        }
        return $out;
    };

    $cards_from = static function ( $section ) {
        $cards = array();
        // links_cuadrados (post relacionado) o links_cuadrados_externos (url + texto).
        $rows = $section['links_cuadrados'] ?? ( $section['links_cuadrados_externos'] ?? array() );
        foreach ( (array) $rows as $row ) {
            $url = '';
            $title = '';
            if ( ! empty( $row['link_relacionado'] ) ) {
                $rel = $row['link_relacionado'];
                $rel_id = is_array( $rel ) ? (int) reset( $rel ) : (int) $rel;
                if ( $rel_id ) {
                    $url   = get_permalink( $rel_id );
                    $title = get_the_title( $rel_id );
                }
            } else {
                $url   = $row['link'] ?? ( $row['url'] ?? ( $row['link_externo'] ?? '' ) );
                $title = $row['texto'] ?? ( $row['titulo'] ?? '' );
                if ( ! $title && $url ) {
                    $title = $url;
                }
            }
            if ( ! $url && ! $title ) {
                continue;
            }
            $cards[] = array(
                'image'   => array(),
                'title'   => $title,
                'excerpt' => '',
                'link'    => array( 'title' => $title, 'url' => $url, 'target' => '' ),
            );
        }
        return $cards;
    };

    $accordion_items_from = static function ( $items ) {
        $rows = array();
        foreach ( (array) $items as $it ) {
            $t = trim( (string) ( $it['titulo'] ?? '' ) );
            if ( $t === '' ) {
                continue;
            }
            $rows[] = array( 'titulo' => $t, 'contenido' => $it['contenido'] ?? '' );
        }
        return $rows;
    };

    // Resuelve un campo gallery (attachments / IDs) a {url, alt}.
    $gallery_from = static function ( $value ) {
        $imgs = array();
        foreach ( (array) $value as $img ) {
            if ( is_array( $img ) ) {
                $url = $img['sizes']['large'] ?? ( $img['url'] ?? '' );
                $alt = $img['alt'] ?? '';
            } elseif ( is_numeric( $img ) ) {
                $url = wp_get_attachment_image_url( (int) $img, 'large' );
                $alt = get_post_meta( (int) $img, '_wp_attachment_image_alt', true );
            } else {
                $url = (string) $img;
                $alt = '';
            }
            if ( $url ) {
                $imgs[] = array( 'url' => $url, 'alt' => $alt );
            }
        }
        return $imgs;
    };

    // Resuelve un campo relationship (IDs / WP_Post) a items del featured_carousel.
    $featured_from_rel = static function ( $value ) {
        $items = array();
        foreach ( (array) $value as $rel ) {
            $rid = is_object( $rel ) ? (int) $rel->ID : ( is_array( $rel ) ? (int) ( $rel['ID'] ?? 0 ) : (int) $rel );
            if ( ! $rid || 'publish' !== get_post_status( $rid ) ) {
                continue;
            }
            $items[] = array(
                'titulo' => get_the_title( $rid ),
                'imagen' => get_the_post_thumbnail_url( $rid, 'large' ) ?: '',
                'link'   => get_permalink( $rid ),
            );
        }
        return $items;
    };

    $n = count( $secs );
    for ( $i = 0; $i < $n; $i++ ) {
        $s   = $secs[ $i ];
        $lay = $s['acf_fc_layout'] ?? '';

        if ( 'desplegable' === $lay ) {
            $intro = '';
            $title = '';
            if ( ! $intro_used && '' !== $pc ) {
                $intro      = wpautop( $pc );
                $intro_used = true;
                $title      = sprintf( __( 'Sobre %s', 'starter-theme' ), $page_title );
            }
            $out[] = array(
                'acf_fc_layout' => 'text_accordion',
                'anchor_label'  => $title ?: $page_title,
                'anchor_icon'   => null,
                'title'         => $title,
                'intro'         => $intro,
                'items'         => $accordion_items_from( $s['desplegable'] ?? array() ),
                'sidebar_cards' => array(),
            );
        } elseif ( 'contenido' === $lay ) {
            list( $t, $rest ) = udp_institucional_parse_heading( $s['contenido'] ?? '' );
            $next = $secs[ $i + 1 ]['acf_fc_layout'] ?? '';
            if ( 'desplegable' === $next ) {
                $out[] = array(
                    'acf_fc_layout' => 'text_accordion',
                    'anchor_label'  => $t,
                    'anchor_icon'   => null,
                    'title'         => $t,
                    'intro'         => $rest,
                    'items'         => $accordion_items_from( $secs[ $i + 1 ]['desplegable'] ?? array() ),
                    'sidebar_cards' => array(),
                );
                $i++;
            } elseif ( 'listado_de_informacion' === $next ) {
                $out[] = array(
                    'acf_fc_layout' => 'people_carousel',
                    'anchor_label'  => $t,
                    'anchor_icon'   => null,
                    'title'         => $t,
                    'subtitle'      => trim( wp_strip_all_tags( $rest ) ),
                    'personas'      => $personas_from( $secs[ $i + 1 ]['listado_de_informacion'] ?? array() ),
                );
                $i++;
            } elseif ( 'links_cuadrados' === $next || 'links_cuadrados_externos' === $next ) {
                $out[] = array(
                    'acf_fc_layout' => 'cards_dark_row',
                    'anchor_label'  => $t,
                    'anchor_icon'   => null,
                    'title'         => $t,
                    'cards'         => $cards_from( $secs[ $i + 1 ] ),
                );
                $i++;
            } elseif ( 'galeria_de_imagenes' === $next ) {
                $imgs = $gallery_from( $secs[ $i + 1 ]['galeria_de_imagenes'] ?? array() );
                if ( $imgs ) {
                    $out[] = array(
                        'acf_fc_layout' => 'gallery',
                        'anchor_label'  => $t,
                        'anchor_icon'   => null,
                        'title'         => $t,
                        'images'        => $imgs,
                    );
                } else {
                    $out[] = array(
                        'acf_fc_layout' => 'rich_text_sidebar',
                        'anchor_label'  => $t,
                        'anchor_icon'   => null,
                        'title'         => $t,
                        'body'          => $rest,
                        'sidebar_cards' => array(),
                    );
                }
                $i++;
            } else {
                // contenido suelto → texto a UNA sola columna (título dentro del
                // flujo del texto, no en columna lateral). El anchor usa el
                // encabezado solo si el contenido tenía un <h1-6>.
                $has_heading = (bool) preg_match( '/<h[1-6]/i', (string) ( $s['contenido'] ?? '' ) );
                $out[] = array(
                    'acf_fc_layout' => 'rich_text',
                    'anchor_label'  => $has_heading ? $t : '',
                    'anchor_icon'   => null,
                    'body'          => $s['contenido'] ?? '',
                );
            }
        } elseif ( 'listado_de_informacion' === $lay ) {
            $out[] = array(
                'acf_fc_layout' => 'people_carousel',
                'anchor_label'  => __( 'Integrantes', 'starter-theme' ),
                'anchor_icon'   => null,
                'title'         => __( 'Integrantes', 'starter-theme' ),
                'subtitle'      => '',
                'personas'      => $personas_from( $s['listado_de_informacion'] ?? array() ),
            );
        } elseif ( 'links_cuadrados' === $lay || 'links_cuadrados_externos' === $lay ) {
            $out[] = array(
                'acf_fc_layout' => 'cards_dark_row',
                'anchor_label'  => __( 'Enlaces', 'starter-theme' ),
                'anchor_icon'   => null,
                'title'         => '',
                'cards'         => $cards_from( $s ),
            );
        } elseif ( 'destacados_carrusel' === $lay ) {
            $items = array();
            foreach ( (array) ( $s['destacado'] ?? array() ) as $d ) {
                $imgv = $d['imagen'] ?? '';
                $img  = is_array( $imgv )
                    ? ( $imgv['url'] ?? '' )
                    : ( is_numeric( $imgv ) ? wp_get_attachment_image_url( (int) $imgv, 'large' ) : (string) $imgv );
                $items[] = array(
                    'titulo' => trim( (string) ( $d['titulo'] ?? '' ) ),
                    'imagen' => $img,
                    'link'   => (string) ( $d['link'] ?? '' ),
                );
            }
            if ( $items ) {
                $t = trim( (string) ( $s['titulo'] ?? '' ) );
                $out[] = array(
                    'acf_fc_layout' => 'featured_carousel',
                    'anchor_label'  => $t,
                    'anchor_icon'   => null,
                    'title'         => $t,
                    'items'         => $items,
                );
            }
        } elseif ( 'botones_con_links_externos' === $lay ) {
            // Se acumulan y se adjuntan al aside de la 1ª sección de texto (abajo).
            foreach ( (array) ( $s['botones_con_links_externos'] ?? array() ) as $b ) {
                $label = trim( (string) ( $b['titulo'] ?? '' ) );
                $url   = (string) ( $b['link_externo'] ?? '' );
                if ( '' === $url && ! empty( $b['archivo_para_descargar'] ) ) {
                    $file = $b['archivo_para_descargar'];
                    $url  = is_array( $file ) ? ( $file['url'] ?? '' ) : ( is_numeric( $file ) ? wp_get_attachment_url( (int) $file ) : (string) $file );
                }
                if ( '' === $label || '' === $url ) {
                    continue;
                }
                $pending_buttons[] = array( 'label' => $label, 'url' => $url );
            }
        } elseif ( 'numeros_destacados' === $lay ) {
            $nums = array();
            foreach ( (array) ( $s['numeros_destacados'] ?? array() ) as $nrow ) {
                $num = trim( (string) ( $nrow['numero'] ?? '' ) );
                if ( '' === $num ) {
                    continue;
                }
                $nums[] = array(
                    'numero'    => $num,
                    'titulo'    => trim( (string) ( $nrow['titulo'] ?? '' ) ),
                    'subtitulo' => trim( (string) ( $nrow['subtitulo'] ?? '' ) ),
                );
            }
            if ( $nums ) {
                $out[] = array(
                    'acf_fc_layout' => 'stats',
                    'anchor_label'  => '',
                    'numeros'       => $nums,
                );
            }
        } elseif ( 'video' === $lay ) {
            $embed = $s['video'] ?? '';
            if ( $embed ) {
                $out[] = array(
                    'acf_fc_layout' => 'video',
                    'anchor_label'  => '',
                    'embed'         => $embed,
                );
            }
        } elseif ( 'galeria_de_imagenes' === $lay ) {
            $imgs = $gallery_from( $s['galeria_de_imagenes'] ?? array() );
            if ( $imgs ) {
                $out[] = array(
                    'acf_fc_layout' => 'gallery',
                    'anchor_label'  => '',
                    'anchor_icon'   => null,
                    'title'         => '',
                    'images'        => $imgs,
                );
            }
        } elseif ( in_array( $lay, array( 'paginas_destacadas', 'destacados', 'mozaico' ), true ) ) {
            $items = $featured_from_rel( $s['destacados'] ?? array() );
            if ( $items ) {
                $t = trim( (string) ( $s['titulo'] ?? '' ) );
                $out[] = array(
                    'acf_fc_layout' => 'featured_carousel',
                    'anchor_label'  => $t,
                    'anchor_icon'   => null,
                    'title'         => $t,
                    'items'         => $items,
                );
            }
        } elseif ( 'eventos_destacados' === $lay ) {
            $items = $featured_from_rel( $s['eventos'] ?? array() );
            if ( $items ) {
                $out[] = array(
                    'acf_fc_layout' => 'featured_carousel',
                    'anchor_label'  => '',
                    'anchor_icon'   => null,
                    'title'         => '',
                    'items'         => $items,
                );
            }
        }
        // Layouts legacy aún sin soportar (se omiten): links_en_tabs,
        // directorio_de_redes_sociales, atributos.
    }

    // post_content no consumido como intro de un acordeón → rich_text inicial.
    if ( ! $intro_used && '' !== $pc ) {
        array_unshift(
            $out,
            array(
                'acf_fc_layout' => 'rich_text_sidebar',
                'anchor_label'  => sprintf( __( 'Sobre %s', 'starter-theme' ), $page_title ),
                'anchor_icon'   => null,
                'title'         => sprintf( __( 'Sobre %s', 'starter-theme' ), $page_title ),
                'body'          => wpautop( $pc ),
                'sidebar_cards' => array(),
            )
        );
    }

    // Adjunta el widget "Noticias" al sidebar de la primera sección de texto.
    foreach ( $out as $idx => $sec ) {
        if ( in_array( $sec['acf_fc_layout'] ?? '', array( 'rich_text_sidebar', 'text_accordion' ), true ) ) {
            $out[ $idx ]['show_news'] = true;
            break;
        }
    }

    // Botones (botones_con_links_externos) → aside derecho de la primera sección
    // de texto, igual que las tarjetas. Si no hay sección de texto, banda full-width.
    if ( ! empty( $pending_buttons ) ) {
        $attached = false;
        foreach ( $out as $idx => $sec ) {
            if ( in_array( $sec['acf_fc_layout'] ?? '', array( 'rich_text_sidebar', 'text_accordion' ), true ) ) {
                $out[ $idx ]['sidebar_buttons'] = $pending_buttons;
                $attached = true;
                break;
            }
        }
        if ( ! $attached ) {
            $out[] = array(
                'acf_fc_layout' => 'buttons',
                'anchor_label'  => '',
                'buttons'       => array_map( static fn( $b ) => $b + array( 'target' => true ), $pending_buttons ),
            );
        }
    }

    // Navegación inferior: banda "También te puede interesar" con páginas
    // hermanas (incluye "Volver a {padre}" como primera card); si no hay
    // hermanas, un back_link simple. Solo si la página tiene padre.
    $parent_id = wp_get_post_parent_id( $post_id );
    if ( $parent_id ) {
        $siblings = get_posts(
            array(
                'post_type'        => 'page',
                'post_parent'      => $parent_id,
                'post__not_in'     => array( $post_id ),
                'posts_per_page'   => 12,
                'orderby'          => 'menu_order title',
                'order'            => 'ASC',
                'post_status'      => 'publish',
                'suppress_filters' => false,
            )
        );

        if ( $siblings ) {
            $cards = array();
            foreach ( $siblings as $sib ) {
                $t = get_the_title( $sib );
                $cards[] = array(
                    'titulo' => $t,
                    'link'   => array( 'url' => get_permalink( $sib ), 'title' => $t, 'target' => '' ),
                );
            }
            $out[] = array(
                'acf_fc_layout' => 'related',
                'anchor_label'  => '',
                'parent_id'     => $parent_id,
                'cards'         => $cards,
            );
        } else {
            $out[] = array(
                'acf_fc_layout'      => 'back_link',
                'anchor_label'       => '',
                'display_in_anchors' => 0,
                'link_text'          => __( 'Volver a {parent_title}', 'starter-theme' ),
                'target'             => '',
            );
        }
    }

    return $out;
}
