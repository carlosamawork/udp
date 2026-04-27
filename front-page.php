<?php
/**
 * Front Page Template
 *
 * Se usa cuando tienes una "Página de inicio" estática en Ajustes > Lectura.
 * Combina Hero (si tiene ACF) + contenido del editor + Flexible Content.
 *
 * @package Starter_BS5
 */

get_header();

// Hero (si hay campos rellenados)
$hero_title = starter_get_field('hero_title');
$hero_image = starter_get_field('hero_image');

if ($hero_title || $hero_image) :
    $hero_subtitle = starter_get_field('hero_subtitle');
    $hero_cta_text = starter_get_field('hero_cta_text');
    $hero_cta_url  = starter_get_field('hero_cta_url');
    ?>

    <section class="hero-section position-relative overflow-hidden text-white"
             <?php if ($hero_image) : ?>
             style="background-image: url('<?php echo esc_url($hero_image['sizes']['hero-banner'] ?? $hero_image['url']); ?>');
                    background-size: cover; background-position: center; min-height: 80vh;"
             <?php else : ?>
             style="background: linear-gradient(135deg, #0d6efd, #6610f2); min-height: 80vh;"
             <?php endif; ?>>

        <div class="hero-overlay position-absolute top-0 start-0 w-100 h-100"
             style="background: rgba(0,0,0,0.45);"></div>

        <div class="container position-relative d-flex align-items-center" style="min-height: 80vh;">
            <div class="row">
                <div class="col-lg-8">
                    <h1 class="display-2 fw-bold mb-3"><?php echo esc_html($hero_title); ?></h1>
                    <?php if ($hero_subtitle) : ?>
                        <p class="lead text-white text-opacity-90 mb-4"><?php echo esc_html($hero_subtitle); ?></p>
                    <?php endif; ?>
                    <?php if ($hero_cta_text && $hero_cta_url) : ?>
                        <a href="<?php echo esc_url($hero_cta_url); ?>" class="btn btn-light btn-lg">
                            <?php echo esc_html($hero_cta_text); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

<?php endif; ?>

<!-- Contenido del editor -->
<div class="container py-5">
    <?php while (have_posts()) : the_post(); ?>
        <?php
        $content = get_the_content();
        if ($content) : ?>
            <div class="entry-content">
                <?php the_content(); ?>
            </div>
        <?php endif; ?>
    <?php endwhile; ?>

    <?php
    // Flexible Content (si está asignado a esta página)
    if (have_rows('content_blocks')) : ?>
        <div class="flexible-blocks mt-3">
            <?php
            while (have_rows('content_blocks')) : the_row();
                get_template_part('template-parts/blocks/block', get_row_layout());
            endwhile;
            ?>
        </div>
    <?php endif; ?>
</div>

<?php
get_footer();
