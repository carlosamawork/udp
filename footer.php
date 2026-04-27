<?php
/**
 * The template for displaying the footer
 *
 * @package Starter_Theme
 */
$copyright   = function_exists( 'get_field' ) ? get_field( 'copyright', 'option' ) : '';
$legal_links = function_exists( 'get_field' ) ? get_field( 'legal_links', 'option' ) : array();
?>
</main>

<footer class="udp-site-footer" role="contentinfo">
	<div class="udp-site-footer__inner">

		<div class="udp-site-footer__top">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="udp-site-footer__logo">
				<?php
				$logo = udp_get_logo_url( 'blanco' );
				if ( ! empty( $logo ) ) :
					?>
					<img src="<?php echo esc_url( $logo ); ?>" alt="<?php bloginfo( 'name' ); ?>" />
				<?php else : ?>
					<span><?php bloginfo( 'name' ); ?></span>
				<?php endif; ?>
			</a>

			<?php get_template_part( 'template-parts/footer/social' ); ?>
		</div>

		<?php get_template_part( 'template-parts/footer/columns' ); ?>

		<div class="udp-site-footer__bottom">
			<?php if ( ! empty( $copyright ) ) : ?>
				<p class="udp-site-footer__copyright"><?php echo esc_html( $copyright ); ?></p>
			<?php else : ?>
				<p class="udp-site-footer__copyright">&copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $legal_links ) && is_array( $legal_links ) ) : ?>
				<ul class="udp-site-footer__legal">
					<?php foreach ( $legal_links as $link ) : ?>
						<?php
						$url   = isset( $link['url'] ) ? $link['url'] : '';
						$label = isset( $link['label'] ) ? $link['label'] : '';
						if ( empty( $url ) || empty( $label ) ) {
							continue;
						}
						?>
						<li><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
