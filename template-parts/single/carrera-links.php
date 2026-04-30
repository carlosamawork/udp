<?php
/**
 * Single Carrera > Links repeater (extras al final del content).
 *
 * @var array $args ['post_id' => int]
 */
$post_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();
if ( ! $post_id ) return;

$links = function_exists( 'get_field' ) ? get_field( 'links', $post_id ) : array();
if ( ! is_array( $links ) || empty( $links ) ) return;
?>
<section class="udp-carrera-links">
    <h2 class="udp-carrera-links__title"><?php esc_html_e( 'Enlaces relacionados', 'starter-theme' ); ?></h2>
    <ul class="udp-carrera-links__list">
        <?php foreach ( $links as $row ) :
            $titulo = $row['titulo_link'] ?? '';
            $url    = $row['link']        ?? '';
            if ( ! $titulo || ! $url ) continue;
        ?>
            <li class="udp-carrera-links__item">
                <a class="udp-carrera-links__link" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">
                    <?php echo esc_html( $titulo ); ?>
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                        <path d="M3 3h6v6M9 3 3 9" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
