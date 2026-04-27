<?php
/**
 * Bloque ACF Flexible Content: Grid de Cards
 *
 * @package Starter_BS5
 */

$section_title = get_sub_field('section_title');
$columns       = get_sub_field('columns') ?: '3';
$cards         = get_sub_field('cards');

$col_class_map = [
    '2' => 'col-md-6',
    '3' => 'col-md-6 col-lg-4',
    '4' => 'col-md-6 col-lg-3',
];
$col_class = $col_class_map[$columns] ?? 'col-md-6 col-lg-4';
?>

<section class="block-cards-grid py-5">
    <?php if ($section_title) : ?>
        <h2 class="text-center mb-5"><?php echo esc_html($section_title); ?></h2>
    <?php endif; ?>

    <?php if ($cards) : ?>
        <div class="row g-4">
            <?php foreach ($cards as $card) : ?>
                <div class="<?php echo esc_attr($col_class); ?>">
                    <div class="card h-100 shadow-sm border-0">
                        <?php if (!empty($card['image'])) : ?>
                            <img src="<?php echo esc_url($card['image']['sizes']['card-thumbnail'] ?? $card['image']['url']); ?>"
                                 alt="<?php echo esc_attr($card['image']['alt'] ?? $card['title']); ?>"
                                 class="card-img-top"
                                 loading="lazy">
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <?php if (!empty($card['title'])) : ?>
                                <h5 class="card-title"><?php echo esc_html($card['title']); ?></h5>
                            <?php endif; ?>
                            <?php if (!empty($card['text'])) : ?>
                                <p class="card-text text-muted"><?php echo esc_html($card['text']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($card['link'])) : ?>
                                <a href="<?php echo esc_url($card['link']['url']); ?>"
                                   class="btn btn-outline-primary mt-auto"
                                   <?php echo !empty($card['link']['target']) ? 'target="' . esc_attr($card['link']['target']) . '"' : ''; ?>>
                                    <?php echo esc_html($card['link']['title'] ?: __('Ver más', 'starter-bs5')); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
