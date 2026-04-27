<?php
/**
 * Bloque ACF Flexible Content: CTA Banner
 *
 * @package Starter_BS5
 */

$heading     = get_sub_field('heading');
$description = get_sub_field('description');
$btn_text    = get_sub_field('button_text');
$btn_url     = get_sub_field('button_url');
$bg_color    = get_sub_field('bg_color') ?: '#0d6efd';
?>

<section class="block-cta-banner py-5 my-5 rounded-3 text-white text-center"
         style="background-color: <?php echo esc_attr($bg_color); ?>;">
    <div class="px-4 py-4">
        <?php if ($heading) : ?>
            <h2 class="display-6 fw-bold mb-3"><?php echo esc_html($heading); ?></h2>
        <?php endif; ?>

        <?php if ($description) : ?>
            <p class="lead mb-4 mx-auto" style="max-width: 600px;">
                <?php echo esc_html($description); ?>
            </p>
        <?php endif; ?>

        <?php if ($btn_text && $btn_url) : ?>
            <a href="<?php echo esc_url($btn_url); ?>"
               class="btn btn-light btn-lg">
                <?php echo esc_html($btn_text); ?>
            </a>
        <?php endif; ?>
    </div>
</section>
