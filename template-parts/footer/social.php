<?php
/**
 * Footer > Iconos sociales
 *
 * @package Starter_Theme
 */
$socials = udp_get_social_urls();
if ( empty( $socials ) ) {
	return;
}

$labels = array(
	'facebook'  => 'Facebook',
	'twitter'   => 'Twitter / X',
	'instagram' => 'Instagram',
	'youtube'   => 'YouTube',
	'linkedin'  => 'LinkedIn',
	'tiktok'    => 'TikTok',
);
?>
<ul class="udp-footer-social" role="list">
	<?php foreach ( $socials as $key => $url ) : ?>
		<li>
			<a
				href="<?php echo esc_url( $url ); ?>"
				class="btn-icon-circle"
				target="_blank"
				rel="noopener noreferrer"
				aria-label="<?php echo esc_attr( $labels[ $key ] ?? ucfirst( $key ) ); ?>"
			>
				<span aria-hidden="true"><?php echo esc_html( strtoupper( substr( $key, 0, 2 ) ) ); ?></span>
			</a>
		</li>
	<?php endforeach; ?>
</ul>
