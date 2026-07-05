<?php
/**
 * Full model public event template.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

get_header();

$event_id = get_queried_object_id();
?>
<main id="primary" class="wp-seed-events-full-model" role="main">
	<?php do_action( 'wp_seed_events_before_model' ); ?>
	<?php echo wp_seed_events_render_public_event_single( $event_id, true ); ?>
	<?php do_action( 'wp_seed_events_after_model' ); ?>
</main>
<?php
get_footer();
