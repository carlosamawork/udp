<?php
/**
 * @var array $args ['month_name' => string, 'month_slug' => string, 'entries' => array]
 */
$month_name = isset( $args['month_name'] ) ? (string) $args['month_name'] : '';
$month_slug = isset( $args['month_slug'] ) ? (string) $args['month_slug'] : '';
$entries    = isset( $args['entries'] ) && is_array( $args['entries'] ) ? $args['entries'] : array();

if ( empty( $entries ) || ! $month_name ) {
    return;
}
?>
<section id="<?php echo esc_attr( $month_slug ); ?>" class="udp-calendario-month">
    <h2 class="udp-calendario-month__title"><?php echo esc_html( $month_name ); ?></h2>
    <ul class="udp-calendario-month__list">
        <?php foreach ( $entries as $entry ) : ?>
            <?php
            get_template_part(
                'template-parts/blocks/parts/entry-calendario',
                null,
                array( 'entry' => $entry )
            );
            ?>
        <?php endforeach; ?>
    </ul>
</section>
