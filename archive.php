<?php
/**
 * Template para archivos (categorías, tags, fechas, autores)
 *
 * @package Starter_BS5
 */

get_header();
?>

<div class="container py-5">
    <?php starter_breadcrumbs(); ?>

    <header class="mb-5">
        <?php the_archive_title('<h1 class="mb-2">', '</h1>'); ?>
        <?php the_archive_description('<div class="text-muted lead">', '</div>'); ?>
    </header>

    <?php if (have_posts()) : ?>
        <div class="row g-4">
            <?php while (have_posts()) : the_post(); ?>
                <div class="col-md-6 col-lg-4">
                    <?php get_template_part('template-parts/card', 'post'); ?>
                </div>
            <?php endwhile; ?>
        </div>

        <?php starter_pagination(); ?>
    <?php else : ?>
        <div class="alert alert-info">
            <?php esc_html_e('No se han encontrado entradas en esta categoría.', 'starter-bs5'); ?>
        </div>
    <?php endif; ?>
</div>

<?php
get_footer();
