<?php
/**
 * Single Post > Share buttons (floating)
 *
 * Sticky vertical bar con: copy URL + Facebook + X + WhatsApp + LinkedIn.
 *
 * @package Starter_Theme
 *
 * @var array $args ['post_id' => int]
 */
$post_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();
if ( ! $post_id ) {
    return;
}

$url   = get_permalink( $post_id );
$title = get_the_title( $post_id );

$facebook  = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url );
$twitter   = 'https://twitter.com/intent/tweet?url=' . rawurlencode( $url ) . '&text=' . rawurlencode( $title );
$whatsapp  = 'https://api.whatsapp.com/send?text=' . rawurlencode( $title . ' ' . $url );
$linkedin  = 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( $url );
?>
<aside class="udp-single-post__share" aria-label="<?php esc_attr_e( 'Compartir', 'starter-theme' ); ?>">
    <ul class="udp-single-post__share-list">

        <li class="udp-single-post__share-item">
            <button
                type="button"
                class="udp-single-post__share-btn"
                data-udp-copy-url
                data-url="<?php echo esc_attr( $url ); ?>"
                aria-label="<?php esc_attr_e( 'Copiar enlace', 'starter-theme' ); ?>"
            >
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                    <path d="M7.5 10.5a3 3 0 0 0 4.24 0l3-3a3 3 0 0 0-4.24-4.24l-.75.75M10.5 7.5a3 3 0 0 0-4.24 0l-3 3a3 3 0 0 0 4.24 4.24l.75-.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
            </button>
            <span class="udp-single-post__share-toast" data-udp-copy-toast hidden><?php esc_html_e( 'Copiado', 'starter-theme' ); ?></span>
        </li>

        <li class="udp-single-post__share-item">
            <a class="udp-single-post__share-btn" href="<?php echo esc_url( $facebook ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="currentColor"><path d="M10.5 9.5h2l.5-2.5h-2.5V5.5c0-.7.3-1.4 1.4-1.4h1.2V2A12.5 12.5 0 0 0 11.4 2c-2 0-3.4 1.2-3.4 3.4V7H6v2.5h2V16h2.5V9.5z"/></svg>
            </a>
        </li>

        <li class="udp-single-post__share-item">
            <a class="udp-single-post__share-btn" href="<?php echo esc_url( $twitter ); ?>" target="_blank" rel="noopener noreferrer" aria-label="X (Twitter)">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="currentColor"><path d="M13.6 2h2.5l-5.5 6.3L17 16h-5l-3.9-5.1L3.6 16H1.1l5.9-6.7L1 2h5.1l3.5 4.6L13.6 2zm-.9 12.5h1.4L5.4 3.4H3.9l8.8 11.1z"/></svg>
            </a>
        </li>

        <li class="udp-single-post__share-item">
            <a class="udp-single-post__share-btn" href="<?php echo esc_url( $whatsapp ); ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="currentColor"><path d="M9 1.5C4.9 1.5 1.5 4.9 1.5 9c0 1.4.4 2.7 1 3.9L1.5 16.5l3.7-1c1.1.6 2.4.9 3.8.9 4.1 0 7.5-3.4 7.5-7.5S13.1 1.5 9 1.5zM9 15.1c-1.2 0-2.4-.3-3.4-1l-.2-.1-2.2.6.6-2.1-.1-.2A6 6 0 1 1 9 15.1zm3.5-4.5c-.2-.1-1.1-.5-1.3-.6-.2-.1-.3-.1-.4.1-.1.2-.5.6-.6.7-.1.1-.2.2-.4.1-.2-.1-.8-.3-1.5-.9-.6-.5-1-1.1-1.1-1.3-.1-.2 0-.3.1-.4l.3-.4c.1-.1.1-.2.2-.3.1-.1 0-.2 0-.3 0-.1-.4-.9-.5-1.3-.1-.3-.3-.3-.4-.3h-.4c-.1 0-.3 0-.5.2s-.7.7-.7 1.6.7 1.9.8 2c.1.2 1.4 2.2 3.4 3 .5.2.9.3 1.2.4.5.2 1 .1 1.3.1.4-.1 1.1-.5 1.3-.9.2-.4.2-.8.1-.9-.1-.1-.2-.1-.4-.2z"/></svg>
            </a>
        </li>

        <li class="udp-single-post__share-item">
            <a class="udp-single-post__share-btn" href="<?php echo esc_url( $linkedin ); ?>" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="currentColor"><path d="M3.5 6h2.7v9H3.5V6zM4.8 2A1.6 1.6 0 1 1 4.8 5.2 1.6 1.6 0 0 1 4.8 2zM7.5 6h2.6v1.2h0a2.9 2.9 0 0 1 2.6-1.4c2.7 0 3.3 1.8 3.3 4.1V15h-2.7v-4.6c0-1.1 0-2.5-1.5-2.5s-1.8 1.2-1.8 2.4V15H7.5V6z"/></svg>
            </a>
        </li>

    </ul>
</aside>
<script>
(function () {
    document.querySelectorAll('[data-udp-copy-url]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var url = btn.getAttribute('data-url');
            var toast = btn.parentElement.querySelector('[data-udp-copy-toast]');
            var done = function () {
                if (!toast) return;
                toast.hidden = false;
                setTimeout(function () { toast.hidden = true; }, 1800);
            };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url).then(done).catch(function () {
                    window.prompt('Copia el enlace:', url);
                });
            } else {
                window.prompt('Copia el enlace:', url);
                done();
            }
        });
    });
})();
</script>
