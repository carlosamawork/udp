<?php
/**
 * Template 404
 *
 * @package Starter_BS5
 */

get_header();
?>

<div class="container py-5">
    <div class="text-center py-5">
        <h1 class="display-1 fw-bold text-primary">404</h1>
        <h2 class="mb-4"><?php esc_html_e('Página no encontrada', 'starter-bs5'); ?></h2>
        <p class="lead text-muted mb-4">
            <?php esc_html_e('La página que buscas no existe o ha sido movida.', 'starter-bs5'); ?>
        </p>
        <div class="d-flex gap-3 justify-content-center">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary">
                <?php esc_html_e('Volver al inicio', 'starter-bs5'); ?>
            </a>
        </div>
        <div class="mt-5 mx-auto" style="max-width: 400px;">
            <?php get_search_form(); ?>
        </div>
    </div>
</div>

<?php
get_footer();
