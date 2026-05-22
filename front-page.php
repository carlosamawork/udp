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
        'posttitulos',
        'vida-universitaria',
        'cultura-udp',
        'cultura-digital',
        'innovacion',
        'cifras',
    ];

    foreach ( $sections as $sec ) {
        get_template_part( 'template-parts/home/section', $sec );
    }
    ?>
</main>
<?php get_footer(); ?>
