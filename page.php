<?php
/**
 * Template para páginas estáticas
 *
 * @package Starter_BS5
 */

get_header();
?>

<div class="container py-5">
    <?php starter_breadcrumbs(); ?>

    <?php while (have_posts()) : the_post(); ?>

        <article id="page-<?php the_ID(); ?>" <?php post_class(); ?>>
            <h1 class="mb-4"><?php the_title(); ?></h1>

            <?php if (has_post_thumbnail()) : ?>
                <figure class="mb-4">
                    <?php the_post_thumbnail('large', ['class' => 'img-fluid rounded']); ?>
                </figure>
            <?php endif; ?>

            <div class="entry-content">
                <?php the_content(); ?>
            </div>
        </article>

    <?php endwhile; ?>
</div>

<?php
get_footer();
