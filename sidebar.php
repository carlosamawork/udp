<?php
/**
 * Sidebar
 *
 * @package Starter_BS5
 */

if (!is_active_sidebar('sidebar-main')) {
    return;
}
?>

<aside id="sidebar" class="sidebar" role="complementary">
    <?php dynamic_sidebar('sidebar-main'); ?>
</aside>
