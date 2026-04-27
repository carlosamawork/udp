<?php
/**
 * ACF Pro - Configuración y campos
 *
 * - Options Page (Opciones del Tema)
 * - Sincronización JSON local
 * - Ejemplo de campos para páginas y opciones globales
 *
 * @package Starter_BS5
 */

defined('ABSPATH') || exit;

// =============================================================================
// 1. ACF JSON: GUARDAR / CARGAR desde /acf-json
// =============================================================================

/**
 * Directorio donde ACF guarda los JSON de los field groups
 */
add_filter('acf/settings/save_json', function ($path) {
    return STARTER_BS5_DIR . '/acf-json';
});

/**
 * Directorio donde ACF carga los JSON
 */
add_filter('acf/settings/load_json', function ($paths) {
    unset($paths[0]); // quitar default
    $paths[] = STARTER_BS5_DIR . '/acf-json';
    return $paths;
});


// =============================================================================
// 2. ACF OPTIONS PAGE (Opciones del Tema)
// =============================================================================

add_action('acf/init', function () {
    if (!function_exists('acf_add_options_page')) {
        return;
    }

    // Página principal de opciones
    acf_add_options_page([
        'page_title'  => __('Opciones del Tema', 'starter-bs5'),
        'menu_title'  => __('Opciones Tema', 'starter-bs5'),
        'menu_slug'   => 'theme-options',
        'capability'  => 'edit_posts',
        'redirect'    => true,
        'icon_url'    => 'dashicons-admin-customizer',
        'position'    => 59,
    ]);

    // Subpáginas
    acf_add_options_sub_page([
        'page_title'  => __('General', 'starter-bs5'),
        'menu_title'  => __('General', 'starter-bs5'),
        'parent_slug' => 'theme-options',
        'menu_slug'   => 'theme-options-general',
    ]);

    acf_add_options_sub_page([
        'page_title'  => __('Header & Footer', 'starter-bs5'),
        'menu_title'  => __('Header & Footer', 'starter-bs5'),
        'parent_slug' => 'theme-options',
        'menu_slug'   => 'theme-options-header-footer',
    ]);

    acf_add_options_sub_page([
        'page_title'  => __('Redes Sociales', 'starter-bs5'),
        'menu_title'  => __('Redes Sociales', 'starter-bs5'),
        'parent_slug' => 'theme-options',
        'menu_slug'   => 'theme-options-social',
    ]);
});


// =============================================================================
// 3. REGISTRAR FIELD GROUPS MEDIANTE PHP (opcional)
//    Puedes usar esto O crear los campos desde la UI de ACF.
//    Si usas la UI, los JSON se guardarán en /acf-json automáticamente.
// =============================================================================

add_action('acf/include_fields', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    // -------------------------------------------------------------------------
    // OPCIONES GENERALES DEL TEMA
    // -------------------------------------------------------------------------
    acf_add_local_field_group([
        'key'      => 'group_theme_general',
        'title'    => 'Opciones Generales',
        'fields'   => [
            [
                'key'   => 'field_company_name',
                'label' => 'Nombre de la Empresa',
                'name'  => 'company_name',
                'type'  => 'text',
            ],
            [
                'key'   => 'field_company_phone',
                'label' => 'Teléfono',
                'name'  => 'company_phone',
                'type'  => 'text',
            ],
            [
                'key'   => 'field_company_email',
                'label' => 'Email de contacto',
                'name'  => 'company_email',
                'type'  => 'email',
            ],
            [
                'key'   => 'field_company_address',
                'label' => 'Dirección',
                'name'  => 'company_address',
                'type'  => 'textarea',
                'rows'  => 3,
            ],
            [
                'key'           => 'field_google_maps_embed',
                'label'         => 'Google Maps Embed URL',
                'name'          => 'google_maps_embed',
                'type'          => 'url',
                'instructions'  => 'URL del iframe de Google Maps',
            ],
        ],
        'location' => [
            [
                [
                    'param'    => 'options_page',
                    'operator' => '==',
                    'value'    => 'theme-options-general',
                ],
            ],
        ],
    ]);

    // -------------------------------------------------------------------------
    // REDES SOCIALES
    // -------------------------------------------------------------------------
    acf_add_local_field_group([
        'key'      => 'group_theme_social',
        'title'    => 'Redes Sociales',
        'fields'   => [
            [
                'key'        => 'field_social_networks',
                'label'      => 'Redes',
                'name'       => 'social_networks',
                'type'       => 'repeater',
                'layout'     => 'table',
                'min'        => 0,
                'max'        => 10,
                'sub_fields' => [
                    [
                        'key'     => 'field_social_network_name',
                        'label'   => 'Red Social',
                        'name'    => 'network_name',
                        'type'    => 'select',
                        'choices' => [
                            'facebook'  => 'Facebook',
                            'instagram' => 'Instagram',
                            'twitter'   => 'X / Twitter',
                            'linkedin'  => 'LinkedIn',
                            'youtube'   => 'YouTube',
                            'tiktok'    => 'TikTok',
                            'whatsapp'  => 'WhatsApp',
                        ],
                    ],
                    [
                        'key'   => 'field_social_url',
                        'label' => 'URL',
                        'name'  => 'url',
                        'type'  => 'url',
                    ],
                ],
            ],
        ],
        'location' => [
            [
                [
                    'param'    => 'options_page',
                    'operator' => '==',
                    'value'    => 'theme-options-social',
                ],
            ],
        ],
    ]);

    // -------------------------------------------------------------------------
    // HERO SECTION (para páginas con template "Página con Hero")
    // -------------------------------------------------------------------------
    acf_add_local_field_group([
        'key'      => 'group_hero_section',
        'title'    => 'Hero Section',
        'fields'   => [
            [
                'key'   => 'field_hero_title',
                'label' => 'Título Hero',
                'name'  => 'hero_title',
                'type'  => 'text',
            ],
            [
                'key'   => 'field_hero_subtitle',
                'label' => 'Subtítulo',
                'name'  => 'hero_subtitle',
                'type'  => 'textarea',
                'rows'  => 2,
            ],
            [
                'key'          => 'field_hero_image',
                'label'        => 'Imagen de Fondo',
                'name'         => 'hero_image',
                'type'         => 'image',
                'return_format' => 'array',
                'preview_size' => 'medium',
            ],
            [
                'key'   => 'field_hero_cta_text',
                'label' => 'Texto del Botón (CTA)',
                'name'  => 'hero_cta_text',
                'type'  => 'text',
            ],
            [
                'key'   => 'field_hero_cta_url',
                'label' => 'URL del Botón (CTA)',
                'name'  => 'hero_cta_url',
                'type'  => 'url',
            ],
        ],
        'location' => [
            [
                [
                    'param'    => 'page_template',
                    'operator' => '==',
                    'value'    => 'templates/page-hero.php',
                ],
            ],
        ],
    ]);

    // -------------------------------------------------------------------------
    // FLEXIBLE CONTENT: BLOQUES DE CONTENIDO
    // -------------------------------------------------------------------------
    acf_add_local_field_group([
        'key'      => 'group_flexible_blocks',
        'title'    => 'Bloques de Contenido',
        'fields'   => [
            [
                'key'     => 'field_content_blocks',
                'label'   => 'Bloques',
                'name'    => 'content_blocks',
                'type'    => 'flexible_content',
                'layouts' => [
                    // -- Bloque: Texto + Imagen --
                    [
                        'key'        => 'layout_text_image',
                        'name'       => 'text_image',
                        'label'      => 'Texto + Imagen',
                        'display'    => 'block',
                        'sub_fields' => [
                            [
                                'key'   => 'field_ti_title',
                                'label' => 'Título',
                                'name'  => 'title',
                                'type'  => 'text',
                            ],
                            [
                                'key'   => 'field_ti_text',
                                'label' => 'Texto',
                                'name'  => 'text',
                                'type'  => 'wysiwyg',
                            ],
                            [
                                'key'          => 'field_ti_image',
                                'label'        => 'Imagen',
                                'name'         => 'image',
                                'type'         => 'image',
                                'return_format' => 'array',
                            ],
                            [
                                'key'     => 'field_ti_layout',
                                'label'   => 'Disposición',
                                'name'    => 'layout',
                                'type'    => 'select',
                                'choices' => [
                                    'text_left'  => 'Texto izquierda / Imagen derecha',
                                    'text_right' => 'Texto derecha / Imagen izquierda',
                                ],
                            ],
                        ],
                    ],
                    // -- Bloque: CTA Banner --
                    [
                        'key'        => 'layout_cta_banner',
                        'name'       => 'cta_banner',
                        'label'      => 'CTA Banner',
                        'display'    => 'block',
                        'sub_fields' => [
                            [
                                'key'   => 'field_cta_heading',
                                'label' => 'Título',
                                'name'  => 'heading',
                                'type'  => 'text',
                            ],
                            [
                                'key'   => 'field_cta_description',
                                'label' => 'Descripción',
                                'name'  => 'description',
                                'type'  => 'textarea',
                                'rows'  => 2,
                            ],
                            [
                                'key'   => 'field_cta_button_text',
                                'label' => 'Texto Botón',
                                'name'  => 'button_text',
                                'type'  => 'text',
                            ],
                            [
                                'key'   => 'field_cta_button_url',
                                'label' => 'URL Botón',
                                'name'  => 'button_url',
                                'type'  => 'url',
                            ],
                            [
                                'key'   => 'field_cta_bg_color',
                                'label' => 'Color de Fondo',
                                'name'  => 'bg_color',
                                'type'  => 'color_picker',
                                'default_value' => '#0d6efd',
                            ],
                        ],
                    ],
                    // -- Bloque: Cards Grid --
                    [
                        'key'        => 'layout_cards_grid',
                        'name'       => 'cards_grid',
                        'label'      => 'Grid de Cards',
                        'display'    => 'block',
                        'sub_fields' => [
                            [
                                'key'   => 'field_cg_section_title',
                                'label' => 'Título de Sección',
                                'name'  => 'section_title',
                                'type'  => 'text',
                            ],
                            [
                                'key'     => 'field_cg_columns',
                                'label'   => 'Columnas',
                                'name'    => 'columns',
                                'type'    => 'select',
                                'choices' => [
                                    '2' => '2 columnas',
                                    '3' => '3 columnas',
                                    '4' => '4 columnas',
                                ],
                                'default_value' => '3',
                            ],
                            [
                                'key'        => 'field_cg_cards',
                                'label'      => 'Cards',
                                'name'       => 'cards',
                                'type'       => 'repeater',
                                'layout'     => 'block',
                                'sub_fields' => [
                                    [
                                        'key'          => 'field_card_image',
                                        'label'        => 'Imagen',
                                        'name'         => 'image',
                                        'type'         => 'image',
                                        'return_format' => 'array',
                                    ],
                                    [
                                        'key'   => 'field_card_title',
                                        'label' => 'Título',
                                        'name'  => 'title',
                                        'type'  => 'text',
                                    ],
                                    [
                                        'key'   => 'field_card_text',
                                        'label' => 'Texto',
                                        'name'  => 'text',
                                        'type'  => 'textarea',
                                        'rows'  => 3,
                                    ],
                                    [
                                        'key'   => 'field_card_link',
                                        'label' => 'Enlace',
                                        'name'  => 'link',
                                        'type'  => 'link',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'location' => [
            [
                [
                    'param'    => 'page_template',
                    'operator' => '==',
                    'value'    => 'templates/page-flexible.php',
                ],
            ],
        ],
    ]);
});
