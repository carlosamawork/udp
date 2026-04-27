<?php
/**
 * The template for displaying the footer.
 *
 * Estructura según Figma 4401:23290:
 *  - .udp-site-footer__contact: 6 mini-bloques (dirección + 4 teléfonos + 2 emails).
 *  - .udp-site-footer__bottom: logo UDP (left) + social icons (center) + sello acreditación (right),
 *    separado del contact strip por un border-top blanco.
 *
 * NOTA: el Figma actual NO incluye las columnas de links ni el bloque de
 * copyright/legal links que tenía el tema viejo. Mantenemos los helpers
 * (`udp_get_footer_columns()`, copyright/legal_links) disponibles en options
 * page Footer para uso futuro (ej. landing pages que quieran un footer
 * extendido), pero NO se renderizan aquí.
 *
 * @package Starter_Theme
 */
?>
</main>

<footer class="udp-site-footer" role="contentinfo">
	<div class="udp-site-footer__inner">

		<?php get_template_part( 'template-parts/footer/contact' ); ?>

		<div class="udp-site-footer__bottom">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="udp-site-footer__logo" aria-label="<?php bloginfo( 'name' ); ?>">
				<?php
				$logo = function_exists( 'udp_get_logo_url' ) ? udp_get_logo_url( 'blanco' ) : '';
				if ( ! empty( $logo ) ) :
					?>
					<img src="<?php echo esc_url( $logo ); ?>" alt="<?php bloginfo( 'name' ); ?>" />
				<?php else : ?>
					<span><?php bloginfo( 'name' ); ?></span>
				<?php endif; ?>
			</a>

			<?php get_template_part( 'template-parts/footer/social' ); ?>

			<?php get_template_part( 'template-parts/footer/acreditacion' ); ?>
		</div>

	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
