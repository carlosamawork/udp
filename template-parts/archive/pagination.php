<?php
/**
 * Archive > Paginación
 *
 * Wrapper sobre paginate_links() con markup BEM UDP. Reutilizable por
 * cualquier archive (Noticias, Agenda, Concursos).
 *
 * @package Starter_Theme
 *
 * @var array $args ['paged' => int, 'max_pages' => int]
 */
$paged     = isset( $args['paged'] )     ? (int) $args['paged']     : 1;
$max_pages = isset( $args['max_pages'] ) ? (int) $args['max_pages'] : 0;

if ( $max_pages <= 1 ) {
    return;
}

$pages = paginate_links( array(
    'total'     => $max_pages,
    'current'   => max( 1, $paged ),
    'mid_size'  => 1,
    'end_size'  => 1,
    'prev_text' => '<svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M9 3L5 7l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    'next_text' => '<svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M5 3l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    'type'      => 'array',
    'add_args'  => array_filter( array(
        'cat'  => isset( $_GET['cat'] )  ? (int) $_GET['cat']  : null,
        'year' => isset( $_GET['year'] ) ? (int) $_GET['year'] : null,
        's'    => isset( $_GET['s'] )    ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : null,
    ) ),
) );

if ( empty( $pages ) ) {
    return;
}
?>
<nav class="udp-pagination" aria-label="<?php esc_attr_e( 'Paginación', 'starter-theme' ); ?>">
    <ul class="udp-pagination__list">
        <?php foreach ( $pages as $page_html ) : ?>
            <?php
            $is_current  = strpos( $page_html, 'current' ) !== false;
            $is_prev     = strpos( $page_html, 'prev' ) !== false;
            $is_next     = strpos( $page_html, 'next' ) !== false;
            $is_dots     = strpos( $page_html, 'dots' ) !== false;
            $modifier    = '';
            if ( $is_current ) $modifier = ' udp-pagination__item--current';
            elseif ( $is_prev ) $modifier = ' udp-pagination__item--prev';
            elseif ( $is_next ) $modifier = ' udp-pagination__item--next';
            elseif ( $is_dots ) $modifier = ' udp-pagination__item--dots';
            ?>
            <li class="udp-pagination__item<?php echo esc_attr( $modifier ); ?>">
                <?php echo $page_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — paginate_links() returns sanitized HTML ?>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
