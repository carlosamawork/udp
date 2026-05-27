<?php
/**
 * Front Page — Home UDP
 *
 * Orquestador de 11 secciones en orden fijo.
 * El contenido de cada sección vive en template-parts/home/section-*.php.
 *
 * @package starter-bs5
 */

get_header();
?>
<main id="main" class="udp-home">
    <?php
    $sections = [
        'portada',
        'buscador-carreras',
        'noticias',
        'facultades',
        'eventos',
        'destacado',
        'vida-universitaria',
        'cultura-udp',
        'cultura-digital',
        'innovacion',
        'cifras',
    ];

    $home_args = [ 'post_id' => (int) get_option( 'page_on_front' ) ];

    foreach ( $sections as $sec ) {
        get_template_part( 'template-parts/home/section', $sec, $home_args );
    }
    ?>
</main>
<?php get_footer(); ?>
