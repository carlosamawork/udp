<?php
/**
 * Template de resultados de búsqueda
 *
 * @package Starter_BS5
 */

get_header();
?>

<div class="container py-5">
    <?php starter_breadcrumbs(); ?>

    <h1 class="mb-4">
        <?php
        printf(
            esc_html__('Resultados para: %s', 'starter-bs5'),
            '<span class="text-primary">' . esc_html(get_search_query()) . '</span>'
        );
        ?>
    </h1>

    <?php if (have_posts()) : ?>
        <div class="row g-4">
            <?php while (have_posts()) : the_post(); ?>
                <div class="col-md-6">
                    <?php get_template_part('template-parts/card', 'post'); ?>
                </div>
            <?php endwhile; ?>
        </div>

        <?php starter_pagination(); ?>
    <?php else : ?>
        <div class="alert alert-warning">
            <p class="mb-3"><?php esc_html_e('No se encontraron resultados. Intenta otra búsqueda.', 'starter-bs5'); ?></p>
            <?php get_search_form(); ?>
        </div>
    <?php endif; ?>
</div>

<?php
get_footer();
