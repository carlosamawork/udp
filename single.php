<?php
/**
 * Template para entradas individuales (single post)
 *
 * @package Starter_BS5
 */

get_header();
?>

<div class="container py-5">
    <?php starter_breadcrumbs(); ?>

    <div class="row">
        <div class="col-lg-8">
            <?php while (have_posts()) : the_post(); ?>

                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

                    <!-- Cabecera -->
                    <header class="mb-4">
                        <h1 class="display-5 fw-bold"><?php the_title(); ?></h1>
                        <div class="text-muted small">
                            <time datetime="<?php echo get_the_date('c'); ?>">
                                <?php echo get_the_date(); ?>
                            </time>
                            &middot;
                            <?php the_author(); ?>
                            <?php
                            $categories = get_the_category();
                            if ($categories) : ?>
                                &middot;
                                <?php foreach ($categories as $i => $cat) : ?>
                                    <a href="<?php echo esc_url(get_category_link($cat)); ?>"
                                       class="text-decoration-none">
                                        <?php echo esc_html($cat->name); ?>
                                    </a><?php echo $i < count($categories) - 1 ? ', ' : ''; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </header>

                    <!-- Imagen destacada -->
                    <?php if (has_post_thumbnail()) : ?>
                        <figure class="mb-4">
                            <?php the_post_thumbnail('large', ['class' => 'img-fluid rounded']); ?>
                        </figure>
                    <?php endif; ?>

                    <!-- Contenido -->
                    <div class="entry-content">
                        <?php the_content(); ?>
                    </div>

                    <!-- Tags -->
                    <?php
                    $tags = get_the_tags();
                    if ($tags) : ?>
                        <div class="mt-4 pt-3 border-top">
                            <?php foreach ($tags as $tag) : ?>
                                <a href="<?php echo esc_url(get_tag_link($tag)); ?>"
                                   class="badge bg-secondary text-decoration-none me-1">
                                    <?php echo esc_html($tag->name); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Navegación entre posts -->
                    <nav class="mt-5 pt-4 border-top" aria-label="<?php esc_attr_e('Navegación entre entradas', 'starter-bs5'); ?>">
                        <div class="row">
                            <div class="col-6">
                                <?php previous_post_link('<div class="small text-muted">&laquo; Anterior</div><div>%link</div>'); ?>
                            </div>
                            <div class="col-6 text-end">
                                <?php next_post_link('<div class="small text-muted">Siguiente &raquo;</div><div>%link</div>'); ?>
                            </div>
                        </div>
                    </nav>

                </article>

                <!-- Comentarios -->
                <?php if (comments_open() || get_comments_number()) : ?>
                    <div class="mt-5">
                        <?php comments_template(); ?>
                    </div>
                <?php endif; ?>

            <?php endwhile; ?>
        </div>

        <div class="col-lg-4">
            <?php get_sidebar(); ?>
        </div>
    </div>
</div>

<?php
get_footer();
