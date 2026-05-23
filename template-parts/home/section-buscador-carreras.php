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
                <select
                    name="udp_facultad"
                    class="udp-home-buscador__select form-select"
                    aria-label="Filtrar por facultad"
                >
                    <option value="">Todas las carreras</option>
                    <?php foreach ( $facultades as $fac ) : ?>
                        <option value="<?php echo esc_attr( $fac->term_id ); ?>">
                            <?php echo esc_html( $fac->name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input
                    type="search"
                    name="udp_s"
                    class="udp-home-buscador__input form-control"
                    placeholder="Buscar una carrera"
                    aria-label="Buscar carrera por nombre"
                    value=""
                >

                <button type="submit" class="udp-home-buscador__btn btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.099zm-5.242 1.156a5.5 5.5 0 1 1 0-11 5.5 5.5 0 0 1 0 11"/>
                    </svg>
                    <span class="visually-hidden">Buscar</span>
                </button>
            </div>
        </form>
    </div>
</section>
