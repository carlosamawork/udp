<?php
/**
 * The template for displaying the header
 *
 * @package Starter_Theme
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class( udp_body_theme_class() ); ?>>
<?php wp_body_open(); ?>

<a class="visually-hidden visually-hidden-focusable" href="#main">
	<?php esc_html_e( 'Saltar al contenido principal', 'starter-theme' ); ?>
</a>

<header class="udp-site-header" role="banner">
	<?php get_template_part( 'template-parts/header/top-bar' ); ?>
</header>

<?php get_template_part( 'template-parts/header/mega-menu' ); ?>

<main id="main" class="udp-site-main" role="main">
