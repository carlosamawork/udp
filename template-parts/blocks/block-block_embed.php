<?php
/**
 * Block: Embed (iframe genérico)
 *
 * Soporta YouTube / Vimeo / Spotify / Google Maps / generic.
 * Detecta IDs desde URLs comunes.
 *
 * @package Starter_Theme
 */

defined( 'ABSPATH' ) || exit;

$titulo       = get_sub_field( 'titulo' );
$provider     = get_sub_field( 'provider' ) ?: 'youtube';
$url          = trim( (string) get_sub_field( 'url' ) );
$aspect_ratio = get_sub_field( 'aspect_ratio' ) ?: '16-9';
$caption      = get_sub_field( 'caption' );
$theme        = get_sub_field( 'theme' ) ?: 'dark';

if ( ! $url ) {
    return;
}

// Build iframe src per provider
$iframe_src = '';
$iframe_allow = 'fullscreen';
$iframe_title = $titulo ?: 'Embed';

switch ( $provider ) {
    case 'youtube':
        // Extract ID from various URL formats or use as-is if it's just an ID
        $id = $url;
        if ( preg_match( '#(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([\w-]{11})#', $url, $m ) ) {
            $id = $m[1];
        }
        $iframe_src   = 'https://www.youtube-nocookie.com/embed/' . rawurlencode( $id );
        $iframe_allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share; fullscreen';
        break;

    case 'vimeo':
        $id = $url;
        if ( preg_match( '#vimeo\.com/(\d+)#', $url, $m ) ) {
            $id = $m[1];
        }
        $iframe_src   = 'https://player.vimeo.com/video/' . rawurlencode( $id );
        $iframe_allow = 'autoplay; fullscreen; picture-in-picture';
        break;

    case 'spotify':
        // Accept full URL like https://open.spotify.com/episode/XYZ — convert to /embed/episode/XYZ
        $iframe_src = $url;
        if ( preg_match( '#open\.spotify\.com/(track|episode|playlist|album|show)/([\w-]+)#', $url, $m ) ) {
            $iframe_src = 'https://open.spotify.com/embed/' . $m[1] . '/' . $m[2];
        }
        $iframe_allow = 'autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture';
        break;

    case 'maps':
        // If user pastes "https://www.google.com/maps/embed?..." use as-is
        $iframe_src   = $url;
        $iframe_allow = 'fullscreen';
        break;

    case 'generic':
    default:
        $iframe_src   = $url;
        break;
}

if ( ! $iframe_src ) {
    return;
}

$container_class = 'udp-block-embed udp-block-embed--' . $theme . ' udp-block-embed--ratio-' . $aspect_ratio;
?>
<section class="<?php echo esc_attr( $container_class ); ?>">
    <div class="udp-block-embed__inner">
        <?php if ( $titulo ) : ?>
            <h2 class="udp-block-embed__title"><?php echo esc_html( $titulo ); ?></h2>
        <?php endif; ?>

        <div class="udp-block-embed__media">
            <iframe
                src="<?php echo esc_url( $iframe_src ); ?>"
                title="<?php echo esc_attr( $iframe_title ); ?>"
                loading="lazy"
                allow="<?php echo esc_attr( $iframe_allow ); ?>"
                allowfullscreen
                referrerpolicy="strict-origin-when-cross-origin"
            ></iframe>
        </div>

        <?php if ( $caption ) : ?>
            <p class="udp-block-embed__caption"><?php echo esc_html( $caption ); ?></p>
        <?php endif; ?>
    </div>
</section>
