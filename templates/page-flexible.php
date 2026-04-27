<?php
/**
 * Template Name: Página Flexible (Bloques ACF)
 *
 * Template que renderiza bloques de Flexible Content de ACF.
 *
 * @package Starter_BS5
 */

get_header();
?>

<div class="container py-5">
    <?php starter_breadcrumbs(); ?>

    <?php while (have_posts()) : the_post(); ?>

        <h1 class="mb-5"><?php the_title(); ?></h1>

        <?php
        // Renderizar Flexible Content
        if (have_rows('content_blocks')) :
            while (have_rows('content_blocks')) : the_row();
                $layout = get_row_layout();
                get_template_part('template-parts/blocks/block', $layout);
            endwhile;
        endif;
        ?>

        <!-- Contenido del editor (si hay) -->
        <?php
        $content = get_the_content();
        if ($content) : ?>
            <div class="entry-content mt-5">
                <?php the_content(); ?>
            </div>
        <?php endif; ?>

    <?php endwhile; ?>
</div>

<?php
get_footer();
