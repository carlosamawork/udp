<?php
/**
 * Bloque ACF Flexible Content: Texto + Imagen
 *
 * @package Starter_BS5
 */

$title  = get_sub_field('title');
$text   = get_sub_field('text');
$image  = get_sub_field('image');
$layout = get_sub_field('layout') ?: 'text_left';

$text_order  = ($layout === 'text_right') ? 'order-lg-2' : '';
$image_order = ($layout === 'text_right') ? 'order-lg-1' : '';
?>

<section class="block-text-image py-5">
    <div class="row align-items-center g-5">
        <div class="col-lg-6 <?php echo esc_attr($text_order); ?>">
            <?php if ($title) : ?>
                <h2 class="mb-3"><?php echo esc_html($title); ?></h2>
            <?php endif; ?>
            <?php if ($text) : ?>
                <div class="block-text-content"><?php echo $text; ?></div>
            <?php endif; ?>
        </div>
        <div class="col-lg-6 <?php echo esc_attr($image_order); ?>">
            <?php if ($image) : ?>
                <?php echo starter_acf_image($image, 'large', 'img-fluid rounded shadow-sm'); ?>
            <?php endif; ?>
        </div>
    </div>
</section>
