<?php
/**
 * Template principal (fallback)
 *
 * @package Starter_BS5
 */

get_header();
?>

<div class="container py-5">
    <div class="row">

        <!-- Contenido principal -->
        <div class="col-lg-8">
            <?php starter_breadcrumbs(); ?>

            <?php if (is_home() && !is_front_page()) : ?>
                <h1 class="mb-4"><?php single_post_title(); ?></h1>
            <?php endif; ?>

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
                <div class="alert alert-info">
                    <?php esc_html_e('No se han encontrado entradas.', 'starter-bs5'); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <?php get_sidebar(); ?>
        </div>

    </div>
</div>

<?php
get_footer();
