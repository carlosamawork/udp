<?php
/**
 * @var array $args ['year' => int, 'publico' => int, 'tipo' => int, 's' => string,
 *                   'entries_by_month' => array, 'months_es' => array]
 */
$year      = isset( $args['year'] ) ? (int) $args['year'] : (int) date( 'Y' );
$publico   = isset( $args['publico'] ) ? (int) $args['publico'] : 0;
$tipo      = isset( $args['tipo'] ) ? (int) $args['tipo'] : 0;
$s         = isset( $args['s'] ) ? (string) $args['s'] : '';
$by_month  = isset( $args['entries_by_month'] ) && is_array( $args['entries_by_month'] ) ? $args['entries_by_month'] : array();
$months_es = isset( $args['months_es'] ) && is_array( $args['months_es'] ) ? $args['months_es'] : array();

$years = function_exists( 'udp_get_calendario_years' ) ? udp_get_calendario_years() : array();
$action_url = get_permalink( get_the_ID() );
?>
<form class="udp-calendario-sidebar__year-form" method="get" action="<?php echo esc_url( $action_url ); ?>">
    <?php if ( $publico ) : ?><input type="hidden" name="udp_publico" value="<?php echo esc_attr( $publico ); ?>"><?php endif; ?>
    <?php if ( $tipo )    : ?><input type="hidden" name="udp_tipo" value="<?php echo esc_attr( $tipo ); ?>"><?php endif; ?>
    <?php if ( $s !== '' ): ?><input type="hidden" name="udp_s" value="<?php echo esc_attr( $s ); ?>"><?php endif; ?>

    <label for="udp-calendario-year" class="visually-hidden"><?php esc_html_e( 'Selecciona año', 'starter-theme' ); ?></label>
    <select id="udp-calendario-year" name="udp_year" class="udp-calendario-sidebar__year-select udp-form-select udp-form-select--slim" data-udp-autosubmit>
        <?php foreach ( $years as $y ) : ?>
            <option value="<?php echo esc_attr( $y ); ?>" <?php selected( $year, $y ); ?>>
                <?php echo esc_html( $y ); ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<ul class="udp-calendario-sidebar__months-nav">
    <?php foreach ( $months_es as $num => $name ) :
        $has_entries = isset( $by_month[ $num ] ) && ! empty( $by_month[ $num ] );
        $slug = sanitize_title( $name );
    ?>
        <li class="udp-calendario-sidebar__month<?php echo $has_entries ? '' : ' udp-calendario-sidebar__month--empty'; ?>">
            <?php if ( $has_entries ) : ?>
                <a href="#<?php echo esc_attr( $slug ); ?>" data-udp-month-link="<?php echo esc_attr( $num ); ?>"><?php echo esc_html( $name ); ?></a>
            <?php else : ?>
                <span><?php echo esc_html( $name ); ?></span>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>

<script>
(function () {
    document.querySelectorAll('.udp-calendario-sidebar__year-form [data-udp-autosubmit]').forEach(function (el) {
        el.addEventListener('change', function () { el.form.submit(); });
    });
})();
</script>
