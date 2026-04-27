</main><!-- #site-content -->

<footer id="site-footer" class="bg-dark text-light pt-5 pb-3 mt-5">
    <div class="container">
        <div class="row g-4">

            <!-- Columna Info -->
            <div class="col-lg-4">
                <h5 class="text-uppercase mb-3">
                    <?php echo esc_html(starter_get_option('company_name', get_bloginfo('name'))); ?>
                </h5>
                <?php
                $address = starter_get_option('company_address');
                if ($address) : ?>
                    <p class="text-light text-opacity-75 small"><?php echo nl2br(esc_html($address)); ?></p>
                <?php endif; ?>

                <?php
                $phone = starter_get_option('company_phone');
                if ($phone) : ?>
                    <p class="mb-1">
                        <a href="tel:<?php echo esc_attr($phone); ?>" class="text-light text-decoration-none">
                            <?php echo esc_html($phone); ?>
                        </a>
                    </p>
                <?php endif; ?>

                <?php
                $email = starter_get_option('company_email');
                if ($email) : ?>
                    <p>
                        <a href="mailto:<?php echo esc_attr($email); ?>" class="text-light text-decoration-none">
                            <?php echo esc_html($email); ?>
                        </a>
                    </p>
                <?php endif; ?>

                <!-- Redes Sociales -->
                <?php
                $socials = starter_get_social_links();
                if ($socials) : ?>
                    <div class="d-flex gap-3 mt-3">
                        <?php foreach ($socials as $social) : ?>
                            <a href="<?php echo esc_url($social['url']); ?>"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="text-light text-opacity-75"
                               aria-label="<?php echo esc_attr(ucfirst($social['name'])); ?>">
                                <i class="bi bi-<?php echo esc_attr($social['name']); ?>" aria-hidden="true"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Widget Footer 1 -->
            <div class="col-lg-2 col-md-4">
                <?php if (is_active_sidebar('footer-1')) : ?>
                    <?php dynamic_sidebar('footer-1'); ?>
                <?php endif; ?>
            </div>

            <!-- Widget Footer 2 -->
            <div class="col-lg-3 col-md-4">
                <?php if (is_active_sidebar('footer-2')) : ?>
                    <?php dynamic_sidebar('footer-2'); ?>
                <?php endif; ?>
            </div>

            <!-- Widget Footer 3 -->
            <div class="col-lg-3 col-md-4">
                <?php if (is_active_sidebar('footer-3')) : ?>
                    <?php dynamic_sidebar('footer-3'); ?>
                <?php endif; ?>
            </div>

        </div>

        <hr class="my-4 border-light border-opacity-25">

        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="small text-light text-opacity-50 mb-0">
                    &copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>.
                    <?php esc_html_e('Todos los derechos reservados.', 'starter-bs5'); ?>
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <?php
                wp_nav_menu([
                    'theme_location' => 'footer',
                    'container'      => false,
                    'menu_class'     => 'list-inline mb-0',
                    'depth'          => 1,
                    'fallback_cb'    => false,
                    'items_wrap'     => '<ul class="%2$s">%3$s</ul>',
                    'link_before'    => '<span class="small text-light text-opacity-50">',
                    'link_after'     => '</span>',
                ]);
                ?>
            </div>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
