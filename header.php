<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header id="site-header" class="sticky-top">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">

            <!-- Logo / Nombre del sitio -->
            <a class="navbar-brand" href="<?php echo esc_url(home_url('/')); ?>">
                <?php if (has_custom_logo()) : ?>
                    <?php
                    $logo_id  = get_theme_mod('custom_logo');
                    $logo_url = wp_get_attachment_image_url($logo_id, 'medium');
                    ?>
                    <img src="<?php echo esc_url($logo_url); ?>"
                         alt="<?php bloginfo('name'); ?>"
                         height="40"
                         class="d-inline-block align-text-top">
                <?php else : ?>
                    <?php bloginfo('name'); ?>
                <?php endif; ?>
            </a>

            <!-- Toggler mobile -->
            <button class="navbar-toggler"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#mainNavbar"
                    aria-controls="mainNavbar"
                    aria-expanded="false"
                    aria-label="<?php esc_attr_e('Toggle navigation', 'starter-bs5'); ?>">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Menú -->
            <div class="collapse navbar-collapse" id="mainNavbar">
                <?php
                wp_nav_menu([
                    'theme_location' => 'primary',
                    'container'      => false,
                    'menu_class'     => 'navbar-nav ms-auto mb-2 mb-lg-0',
                    'depth'          => 2,
                    'walker'         => new Starter_BS5_Navwalker(),
                    'fallback_cb'    => 'Starter_BS5_Navwalker::fallback',
                ]);
                ?>

                <?php
                // Botón CTA en el header (desde ACF Options)
                $cta_text = starter_get_option('header_cta_text');
                $cta_url  = starter_get_option('header_cta_url');
                if ($cta_text && $cta_url) : ?>
                    <a href="<?php echo esc_url($cta_url); ?>"
                       class="btn btn-primary ms-lg-3">
                        <?php echo esc_html($cta_text); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</header>

<main id="site-content">
