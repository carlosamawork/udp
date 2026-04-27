<?php
/**
 * Breadcrumb dinámico
 *
 * Construye trail desde Inicio hasta la página actual recorriendo post_parent.
 * Markup: nav > ol > li (link o current). Separador chevron-right inline SVG.
 * El último item se renderiza como <span aria-current="page"> (no link).
 *
 * @package Starter_Theme
 *
 * @var array $args {
 *     @type int    $page_id    ID de la página. Default get_the_ID().
 *     @type string $home_label Etiqueta del item raíz. Default 'Inicio'.
 * }
 */
$page_id    = isset( $args['page_id'] ) && (int) $args['page_id'] > 0 ? (int) $args['page_id'] : (int) get_the_ID();
$home_label = isset( $args['home_label'] ) && $args['home_label'] ? $args['home_label'] : __( 'Inicio', 'starter-theme' );

if ( ! $page_id ) {
	return;
}

// Cadena de ancestros (current → root).
$trail   = array();
$current = $page_id;
$safety  = 10; // evita loop infinito si hay ciclo en post_parent
while ( $current && $safety-- > 0 ) {
	$trail[] = array(
		'id'    => $current,
		'title' => get_the_title( $current ),
		'url'   => get_permalink( $current ),
	);
	$current = (int) wp_get_post_parent_id( $current );
}
$trail = array_reverse( $trail ); // root primero

// Prepend Home.
array_unshift(
	$trail,
	array(
		'id'    => 0,
		'title' => $home_label,
		'url'   => home_url( '/' ),
	)
);

$last_index = count( $trail ) - 1;
?>
<nav class="udp-breadcrumb" aria-label="<?php esc_attr_e( 'Migas de pan', 'starter-theme' ); ?>">
	<ol class="udp-breadcrumb__list">
		<?php foreach ( $trail as $i => $item ) : ?>
			<li class="udp-breadcrumb__item">
				<?php if ( $i === $last_index ) : ?>
					<span class="udp-breadcrumb__current" aria-current="page"><?php echo esc_html( $item['title'] ); ?></span>
				<?php else : ?>
					<a class="udp-breadcrumb__link" href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['title'] ); ?></a>
					<span class="udp-breadcrumb__sep" aria-hidden="true">
						<svg width="12" height="12" viewBox="0 0 12 12" fill="none">
							<path d="M4.5 3l3 3-3 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
</nav>
