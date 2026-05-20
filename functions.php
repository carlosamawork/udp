<?php
/**
 * Starter Theme BS5 - functions.php
 *
 * Bootstrap 5 (via npm) + SCSS + Vanilla JS modular + Vite + ACF Pro
 *
 * @package Starter_BS5
 */

defined('ABSPATH') || exit;

define('STARTER_BS5_VERSION', '1.0.0');
define('STARTER_BS5_DIR', get_template_directory());
define('STARTER_BS5_URI', get_template_directory_uri());

// =============================================================================
// 0. VITE ASSET LOADER
// =============================================================================
require_once STARTER_BS5_DIR . '/inc/class-vite.php';


// =============================================================================
// 1. THEME SETUP
// =============================================================================
add_action('after_setup_theme', function () {
    load_theme_textdomain('starter-bs5', STARTER_BS5_DIR . '/languages');

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', [
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ]);
    add_theme_support('custom-logo', [
        'height'      => 80,
        'width'       => 250,
        'flex-height' => true,
        'flex-width'  => true,
    ]);
    add_theme_support('align-wide');
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');

    add_image_size('card-thumbnail', 400, 300, true);
    add_image_size('hero-banner', 1920, 800, true);

    register_nav_menus([
        'primary'   => __('Menú Principal', 'starter-bs5'),
        'footer'    => __('Menú Footer', 'starter-bs5'),
    ]);
});


// =============================================================================
// 2. ENQUEUE: VITE (Bootstrap SCSS + JS modular)
// =============================================================================
add_action('wp_enqueue_scripts', function () {

    // -----------------------------------------------------------------------
    // Entry principal: src/js/main.js
    // Incluye: Bootstrap SCSS + JS, todos los módulos del tema
    // -----------------------------------------------------------------------
    Starter_BS5_Vite::enqueue('starter-bs5-theme', 'src/js/main.js');

    // Pasar datos de WP a JS
    wp_localize_script('starter-bs5-theme', 'starterBS5', [
        'ajaxUrl'  => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('starter_bs5_nonce'),
        'themeUrl' => STARTER_BS5_URI,
        'homeUrl'  => home_url('/'),
    ]);

    // -----------------------------------------------------------------------
    // ENTRY POINTS ADICIONALES POR PÁGINA (opcional)
    //
    // if (is_front_page()) {
    //     Starter_BS5_Vite::enqueue('page-home', 'src/js/page-home.js');
    // }
    //
    // if (is_page_template('templates/page-contact.php')) {
    //     Starter_BS5_Vite::enqueue('page-contact', 'src/js/page-contact.js');
    // }
    // -----------------------------------------------------------------------
});


// =============================================================================
// 3. WALKER: MENÚ BOOTSTRAP 5
// =============================================================================
require_once STARTER_BS5_DIR . '/inc/class-bootstrap-navwalker.php';


// =============================================================================
// 4. WIDGETS / SIDEBARS
// =============================================================================
add_action('widgets_init', function () {
    register_sidebar([
        'name'          => __('Sidebar Principal', 'starter-bs5'),
        'id'            => 'sidebar-main',
        'description'   => __('Widgets de la barra lateral principal.', 'starter-bs5'),
        'before_widget' => '<div id="%1$s" class="widget mb-4 %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h5 class="widget-title mb-3">',
        'after_title'   => '</h5>',
    ]);

    register_sidebar([
        'name'          => __('Footer Columna 1', 'starter-bs5'),
        'id'            => 'footer-1',
        'before_widget' => '<div id="%1$s" class="widget mb-3 %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h6 class="widget-title text-uppercase mb-3">',
        'after_title'   => '</h6>',
    ]);

    register_sidebar([
        'name'          => __('Footer Columna 2', 'starter-bs5'),
        'id'            => 'footer-2',
        'before_widget' => '<div id="%1$s" class="widget mb-3 %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h6 class="widget-title text-uppercase mb-3">',
        'after_title'   => '</h6>',
    ]);

    register_sidebar([
        'name'          => __('Footer Columna 3', 'starter-bs5'),
        'id'            => 'footer-3',
        'before_widget' => '<div id="%1$s" class="widget mb-3 %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h6 class="widget-title text-uppercase mb-3">',
        'after_title'   => '</h6>',
    ]);
});


// =============================================================================
// 5. ACF PRO - CONFIGURACIÓN
// =============================================================================
require_once STARTER_BS5_DIR . '/inc/acf-setup.php';


// =============================================================================
// 6. HELPERS & UTILIDADES
// =============================================================================
require_once STARTER_BS5_DIR . '/inc/helpers.php';
require_once STARTER_BS5_DIR . '/inc/template-helpers.php';
require_once STARTER_BS5_DIR . '/inc/udp-cards.php';
require_once STARTER_BS5_DIR . '/inc/udp-ics.php';
require_once STARTER_BS5_DIR . '/inc/udp-institucional.php';


// =============================================================================
// 7. LIMPIEZA DEL <head>
// =============================================================================
add_action('init', function () {
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    remove_action('wp_head', 'rest_output_link_wp_head');
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
});


// =============================================================================
// 8. GUTENBERG: ESTILOS DEL EDITOR (compilados por Vite)
// =============================================================================
add_action('enqueue_block_editor_assets', function () {
    Starter_BS5_Vite::enqueueStyle('starter-bs5-editor', 'src/scss/editor.scss');
});


// =============================================================================
// 9. EXCERPT PERSONALIZADO
// =============================================================================
add_filter('excerpt_length', fn() => 25, 999);
add_filter('excerpt_more', fn() => '&hellip;');


// =============================================================================
// 10. MODULE TYPE para scripts de Vite (en modo dev)
// =============================================================================
add_filter('script_loader_tag', function ($tag, $handle) {
    if ($handle === 'vite-client') {
        $tag = str_replace(' src', ' type="module" src', $tag);
    }
    return $tag;
}, 10, 2);
