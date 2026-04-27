<?php
/**
 * Helpers y funciones de utilidad del tema
 *
 * @package Starter_BS5
 */

defined('ABSPATH') || exit;

// =============================================================================
// ACF HELPERS
// =============================================================================

/**
 * Obtener campo ACF con fallback
 */
function starter_get_field(string $field, $post_id = false, $default = '')
{
    if (!function_exists('get_field')) {
        return $default;
    }
    $value = get_field($field, $post_id);
    return $value ?: $default;
}

/**
 * Obtener campo de Options Page
 */
function starter_get_option(string $field, $default = '')
{
    return starter_get_field($field, 'option', $default);
}

/**
 * Renderizar imagen ACF con lazy loading
 */
function starter_acf_image(array $image, string $size = 'large', string $class = '', string $alt_fallback = ''): string
{
    if (empty($image['ID'])) {
        return '';
    }

    $src    = $image['sizes'][$size] ?? $image['url'];
    $alt    = $image['alt'] ?: $alt_fallback;
    $width  = $image['sizes']["{$size}-width"] ?? $image['width'];
    $height = $image['sizes']["{$size}-height"] ?? $image['height'];

    return sprintf(
        '<img src="%s" alt="%s" width="%s" height="%s" class="%s" loading="lazy">',
        esc_url($src),
        esc_attr($alt),
        esc_attr($width),
        esc_attr($height),
        esc_attr($class)
    );
}

/**
 * Obtener redes sociales desde Options
 */
function starter_get_social_links(): array
{
    $links = [];
    if (!function_exists('get_field')) {
        return $links;
    }

    $networks = get_field('social_networks', 'option');
    if ($networks) {
        foreach ($networks as $network) {
            $links[] = [
                'name' => $network['network_name'] ?? '',
                'url'  => $network['url'] ?? '',
            ];
        }
    }
    return $links;
}


// =============================================================================
// TEMPLATE HELPERS
// =============================================================================

/**
 * Mostrar breadcrumbs con Bootstrap
 */
function starter_breadcrumbs(): void
{
    if (is_front_page()) {
        return;
    }

    echo '<nav aria-label="breadcrumb" class="my-3">';
    echo '<ol class="breadcrumb">';
    echo '<li class="breadcrumb-item"><a href="' . esc_url(home_url('/')) . '">' . esc_html__('Inicio', 'starter-bs5') . '</a></li>';

    if (is_category() || is_single()) {
        $categories = get_the_category();
        if ($categories) {
            $cat = $categories[0];
            echo '<li class="breadcrumb-item"><a href="' . esc_url(get_category_link($cat->term_id)) . '">' . esc_html($cat->name) . '</a></li>';
        }
        if (is_single()) {
            echo '<li class="breadcrumb-item active" aria-current="page">' . esc_html(get_the_title()) . '</li>';
        }
    } elseif (is_page()) {
        global $post;
        if ($post->post_parent) {
            $ancestors = array_reverse(get_post_ancestors($post->ID));
            foreach ($ancestors as $ancestor) {
                echo '<li class="breadcrumb-item"><a href="' . esc_url(get_permalink($ancestor)) . '">' . esc_html(get_the_title($ancestor)) . '</a></li>';
            }
        }
        echo '<li class="breadcrumb-item active" aria-current="page">' . esc_html(get_the_title()) . '</li>';
    } elseif (is_search()) {
        echo '<li class="breadcrumb-item active" aria-current="page">' . esc_html__('Resultados de búsqueda', 'starter-bs5') . '</li>';
    } elseif (is_archive()) {
        echo '<li class="breadcrumb-item active" aria-current="page">' . esc_html(get_the_archive_title()) . '</li>';
    }

    echo '</ol></nav>';
}

/**
 * Paginación con Bootstrap
 */
function starter_pagination(): void
{
    global $wp_query;

    if ($wp_query->max_num_pages <= 1) {
        return;
    }

    $paged   = max(1, get_query_var('paged'));
    $pages   = $wp_query->max_num_pages;

    echo '<nav aria-label="' . esc_attr__('Paginación', 'starter-bs5') . '" class="mt-5">';
    echo '<ul class="pagination justify-content-center">';

    // Anterior
    if ($paged > 1) {
        echo '<li class="page-item"><a class="page-link" href="' . esc_url(get_pagenum_link($paged - 1)) . '">&laquo;</a></li>';
    }

    for ($i = 1; $i <= $pages; $i++) {
        $active = ($i === $paged) ? ' active' : '';
        echo '<li class="page-item' . $active . '"><a class="page-link" href="' . esc_url(get_pagenum_link($i)) . '">' . $i . '</a></li>';
    }

    // Siguiente
    if ($paged < $pages) {
        echo '<li class="page-item"><a class="page-link" href="' . esc_url(get_pagenum_link($paged + 1)) . '">&raquo;</a></li>';
    }

    echo '</ul></nav>';
}
