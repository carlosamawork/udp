<?php
/**
 * Template Part: Card de Post
 *
 * Usado en index.php, archive.php, search.php
 *
 * @package Starter_BS5
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('card h-100 shadow-sm border-0'); ?>>
    <?php if (has_post_thumbnail()) : ?>
        <a href="<?php the_permalink(); ?>">
            <?php the_post_thumbnail('card-thumbnail', ['class' => 'card-img-top']); ?>
        </a>
    <?php endif; ?>

    <div class="card-body d-flex flex-column">
        <div class="small text-muted mb-2">
            <time datetime="<?php echo get_the_date('c'); ?>">
                <?php echo get_the_date(); ?>
            </time>
            <?php
            $cat = get_the_category();
            if ($cat) : ?>
                &middot;
                <a href="<?php echo esc_url(get_category_link($cat[0])); ?>"
                   class="text-decoration-none">
                    <?php echo esc_html($cat[0]->name); ?>
                </a>
            <?php endif; ?>
        </div>

        <h5 class="card-title">
            <a href="<?php the_permalink(); ?>" class="text-decoration-none text-dark">
                <?php the_title(); ?>
            </a>
        </h5>

        <p class="card-text text-muted flex-grow-1">
            <?php echo get_the_excerpt(); ?>
        </p>

        <a href="<?php the_permalink(); ?>" class="btn btn-sm btn-outline-primary mt-auto align-self-start">
            <?php esc_html_e('Leer más', 'starter-bs5'); ?>
        </a>
    </div>
</article>
