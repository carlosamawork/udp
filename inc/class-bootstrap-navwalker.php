<?php
/**
 * Bootstrap 5 Nav Walker
 *
 * Convierte wp_nav_menu en una navbar compatible con Bootstrap 5.
 *
 * @package Starter_BS5
 */

defined('ABSPATH') || exit;

class Starter_BS5_Navwalker extends Walker_Nav_Menu
{
    /**
     * Start Level - Abre el <ul> para submenús (dropdowns)
     */
    public function start_lvl(&$output, $depth = 0, $args = null)
    {
        $indent = str_repeat("\t", $depth);
        $output .= "\n{$indent}<ul class=\"dropdown-menu\">\n";
    }

    /**
     * Start Element - Cada <li> del menú
     */
    public function start_el(&$output, $data_object, $depth = 0, $args = null, $current_object_id = 0)
    {
        $item = $data_object;
        $indent = ($depth) ? str_repeat("\t", $depth) : '';

        $classes   = empty($item->classes) ? [] : (array) $item->classes;
        $classes[] = 'nav-item';

        // ¿Tiene submenú?
        $has_children = in_array('menu-item-has-children', $classes, true);
        if ($has_children) {
            $classes[] = 'dropdown';
        }

        // Clase activa
        if (in_array('current-menu-item', $classes, true)) {
            $classes[] = 'active';
        }

        $class_names = implode(' ', array_filter($classes));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

        $output .= $indent . '<li' . $class_names . '>';

        // Atributos del enlace
        $atts = [];
        $atts['title']  = !empty($item->attr_title) ? $item->attr_title : '';
        $atts['target'] = !empty($item->target) ? $item->target : '';
        $atts['rel']    = !empty($item->xfn) ? $item->xfn : '';
        $atts['href']   = !empty($item->url) ? $item->url : '';

        if ($depth === 0 && $has_children) {
            $atts['class']          = 'nav-link dropdown-toggle';
            $atts['role']           = 'button';
            $atts['data-bs-toggle'] = 'dropdown';
            $atts['aria-expanded']  = 'false';
        } elseif ($depth > 0) {
            $atts['class'] = 'dropdown-item';
        } else {
            $atts['class'] = 'nav-link';
        }

        // Activo
        if (in_array('current-menu-item', (array) $item->classes, true)) {
            $atts['class']       .= ' active';
            $atts['aria-current'] = 'page';
        }

        $attributes = '';
        foreach ($atts as $attr => $value) {
            if (!empty($value)) {
                $attributes .= ' ' . $attr . '="' . esc_attr($value) . '"';
            }
        }

        $title = apply_filters('the_title', $item->title, $item->ID);
        $title = apply_filters('nav_menu_item_title', $title, $item, $args, $depth);

        $item_output  = $args->before ?? '';
        $item_output .= '<a' . $attributes . '>';
        $item_output .= ($args->link_before ?? '') . $title . ($args->link_after ?? '');
        $item_output .= '</a>';
        $item_output .= $args->after ?? '';

        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }

    /**
     * Fallback si no hay menú asignado
     */
    public static function fallback($args)
    {
        if (!current_user_can('edit_theme_options')) {
            return;
        }

        echo '<ul class="navbar-nav ms-auto">';
        echo '<li class="nav-item">';
        echo '<a class="nav-link" href="' . esc_url(admin_url('nav-menus.php')) . '">';
        echo esc_html__('Crear Menú', 'starter-bs5');
        echo '</a></li></ul>';
    }
}
