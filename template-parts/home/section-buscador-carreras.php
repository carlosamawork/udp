<?php
/**
 * Home — Sección 2: Buscador de Carreras
 *
 * Formulario GET → /carreras/?udp_facultad=&udp_s=
 * Sin AJAX: la búsqueda la procesa el archive page-carreras.php.
 *
 * @package starter-bs5
 */

$facultades = get_terms( [
    'taxonomy'   => 'facultad',
    'hide_empty' => false,
    'orderby'    => 'name',
    'order'      => 'ASC',
] );

if ( is_wp_error( $facultades ) ) {
    $facultades = [];
}

$carreras = get_posts([
    'post_type'      => 'carrera-udp',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
]);

if ( is_wp_error( $carreras ) ) {
    $carreras = [];
}

$carreras_page = get_page_by_path( 'carreras' );
$carreras_url  = $carreras_page ? get_permalink( $carreras_page ) : home_url( '/carreras/' );

$titulo_seccion = get_field( 'buscador_titulo' ) ?: 'Buscador de Carreras';
?>
<section class="udp-home-buscador">
    <div class="container">
        <h2 class="udp-home__titulo"><?php echo esc_html( $titulo_seccion ); ?></h2>
        <form
            class="udp-home-buscador__form"
            method="get"
            action="<?php echo esc_url( $carreras_url ); ?>"
            role="search"
            aria-label="Buscador de carreras"
        >
            <div class="udp-home-buscador__fields">
                <div class="udp-home-buscador__field">
                    <label for="udp_facultad">Buscar por Facultad</label>
                    <select
                        id="udp_facultad"
                        name="udp_facultad"
                        class="udp-home-buscador__select form-select"
                        aria-label="Filtrar por facultad"
                    >
                        <option value="">Selecciona una facultad</option>
                        <?php foreach ( $facultades as $fac ) : ?>
                            <option value="<?php echo esc_attr( $fac->term_id ); ?>">
                                <?php echo esc_html( $fac->name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="udp-home-buscador__field">
                    <label for="udp_carrera">Buscar por Carrera</label>
                    <select
                        id="udp_carrera"
                        name="udp_carrera"
                        class="udp-home-buscador__select form-select"
                        aria-label="Filtrar por carrera"
                    >
                        <option value="">Selecciona una carrera</option>
                        <?php foreach ( $carreras as $post ) : ?>
                            <option value="<?php echo esc_attr( $post->ID ); ?>">
                                <?php echo esc_html( $post->post_title ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="udp-home-buscador__field">
                    <label for="udp_s">Buscar por palabra clave</label>
                    <input
                        type="search"
                        id="udp_s"
                        name="udp_s"
                        class="udp-home-buscador__input form-control"
                        placeholder="Escribe aquí lo que quieras buscar"
                        aria-label="Buscar carrera por nombre"
                        value=""
                    >
                </div>
            </div>
            <button type="submit" class="udp-home-buscador__btn btn btn-secondary">
                Buscar
            </button>
        </form>
    </div>
</section>
