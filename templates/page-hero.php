<?php
/**
 * Template Name: Página con Hero
 *
 * Template para páginas con hero banner gestionado por ACF.
 *
 * @package Starter_BS5
 */

get_header();

// Campos ACF del Hero
$hero_title    = starter_get_field('hero_title');
$hero_subtitle = starter_get_field('hero_subtitle');
$hero_image    = starter_get_field('hero_image');
$hero_cta_text = starter_get_field('hero_cta_text');
$hero_cta_url  = starter_get_field('hero_cta_url');
?>

<?php if ($hero_title || $hero_image) : ?>
<!-- Hero Section -->
<section class="hero-section position-relative overflow-hidden"
         <?php if ($hero_image) : ?>
         style="background-image: url('<?php echo esc_url($hero_image['sizes']['hero-banner'] ?? $hero_image['url']); ?>');
                background-size: cover;
                background-position: center;
                min-height: 70vh;"
         <?php endif; ?>>

    <div class="hero-overlay position-absolute top-0 start-0 w-100 h-100"
         style="background: rgba(0,0,0,0.5);"></div>

    <div class="container position-relative d-flex align-items-center" style="min-height: 70vh;">
        <div class="row">
            <div class="col-lg-8">
                <?php if ($hero_title) : ?>
                    <h1 class="display-3 fw-bold text-white mb-3">
                        <?php echo esc_html($hero_title); ?>
                    </h1>
                <?php endif; ?>

                <?php if ($hero_subtitle) : ?>
                    <p class="lead text-white text-opacity-90 mb-4">
                        <?php echo esc_html($hero_subtitle); ?>
                    </p>
                <?php endif; ?>

                <?php if ($hero_cta_text && $hero_cta_url) : ?>
                    <a href="<?php echo esc_url($hero_cta_url); ?>"
                       class="btn btn-primary btn-lg">
                        <?php echo esc_html($hero_cta_text); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Contenido de la página -->
<div class="container py-5">
    <?php while (have_posts()) : the_post(); ?>
        <div class="entry-content">
            <?php the_content(); ?>
        </div>
    <?php endwhile; ?>
</div>

<?php
get_footer();
