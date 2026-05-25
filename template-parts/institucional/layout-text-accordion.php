<?php
/**
 * Layout — Texto + acordeón
 *
 * 3 columnas en ≥992px: título / (intro + acordeón) / sidebar cards.
 * El acordeón usa <details>/<summary> nativos (accesible, sin JS).
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$data   = $args['data']   ?? array();
$anchor = $args['anchor'] ?? null;

$title         = $data['title']         ?? '';
$intro         = $data['intro']         ?? '';
$items         = is_array( $data['items'] ?? null ) ? $data['items'] : array();
$sidebar_cards   = is_array( $data['sidebar_cards'] ?? null ) ? $data['sidebar_cards'] : array();
$sidebar_buttons = is_array( $data['sidebar_buttons'] ?? null ) ? $data['sidebar_buttons'] : array();
$show_news       = ! empty( $data['show_news'] );

$id = $anchor['id'] ?? '';
?>
<section
	<?php if ( $id ) : ?>id="<?php echo esc_attr( $id ); ?>"<?php endif; ?>
	class="udp-inst-section udp-inst-accordion"
	style="scroll-margin-top: var(--udp-anchor-offset, 168px);"
>
	<div class="udp-inst-accordion__inner">
		<header class="udp-inst-accordion__title-col">
			<?php if ( $title ) : ?><h2 class="udp-inst-accordion__title"><?php echo esc_html( $title ); ?></h2><?php endif; ?>
		</header>

		<div class="udp-inst-accordion__body-col">
			<?php if ( $intro ) : ?>
				<div class="udp-inst-accordion__intro"><?php echo wp_kses_post( $intro ); ?></div>
			<?php endif; ?>

			<?php if ( ! empty( $items ) ) : ?>
				<div class="udp-inst-accordion__list">
					<?php foreach ( $items as $item ) :
						$i_title = $item['titulo']    ?? '';
						$i_body  = $item['contenido'] ?? '';
						if ( ! $i_title ) {
							continue;
						}
					?>
						<details class="udp-inst-accordion__item">
							<summary class="udp-inst-accordion__summary">
								<span class="udp-inst-accordion__summary-text"><?php echo esc_html( $i_title ); ?></span>
								<svg class="udp-inst-accordion__chevron" width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
									<path d="M5 8l5 5 5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
								</svg>
							</summary>
							<div class="udp-inst-accordion__content"><?php echo wp_kses_post( $i_body ); ?></div>
						</details>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $sidebar_cards ) || $show_news || ! empty( $sidebar_buttons ) ) : ?>
			<aside class="udp-inst-accordion__sidebar">
				<?php if ( $show_news ) { get_template_part( 'template-parts/institucional/news-widget' ); } ?>
				<?php if ( ! empty( $sidebar_buttons ) ) { get_template_part( 'template-parts/institucional/sidebar-buttons', null, array( 'buttons' => $sidebar_buttons ) ); } ?>
				<?php foreach ( $sidebar_cards as $card ) :
					$c_title = $card['title'] ?? '';
					$c_body  = $card['body']  ?? '';
					$c_cta   = is_array( $card['cta'] ?? null ) ? $card['cta'] : array();
					$c_url   = $c_cta['url']    ?? '';
					$c_label = ! empty( $c_cta['title'] ) ? $c_cta['title'] : __( 'Conoce más', 'starter-theme' );
					$c_tgt   = $c_cta['target'] ?? '';

					if ( ! $c_url && ! $c_title && ! $c_body ) {
						continue;
					}
				?>
					<article class="udp-inst-accordion__card">
						<?php if ( $c_title ) : ?>
							<h3 class="udp-inst-accordion__card-title"><?php echo esc_html( $c_title ); ?></h3>
						<?php endif; ?>
						<?php if ( $c_body ) : ?>
							<p class="udp-inst-accordion__card-body"><?php echo esc_html( $c_body ); ?></p>
						<?php endif; ?>
						<?php if ( $c_url ) : ?>
							<a class="udp-inst-accordion__card-cta" href="<?php echo esc_url( $c_url ); ?>"<?php echo $c_tgt ? ' target="' . esc_attr( $c_tgt ) . '" rel="noopener noreferrer"' : ''; ?>>
								<span><?php echo esc_html( $c_label ); ?></span>
								<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
									<path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
								</svg>
							</a>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</aside>
		<?php endif; ?>
	</div>
</section>
