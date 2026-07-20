<?php
/** Static contract checks for the event dates save guidance. */
declare(strict_types=1);

$source  = file_get_contents( dirname( __DIR__ ) . '/wp-seed-events.php' );
$message = 'Après avoir enregistré la date, pensez à mettre à jour l’événement pour conserver vos modifications.';
$marker  = 'data-wp-seed-date-save-guidance';
$cases   = 0;

function dates_guidance_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}
function dates_guidance_case( $name, $callback ) {
	global $cases;
	try {
		$callback();
		$cases++;
		echo '[OK] ' . $name . PHP_EOL;
	} catch ( Throwable $error ) {
		fwrite( STDERR, '[KO] ' . $name . ': ' . $error->getMessage() . PHP_EOL );
		exit( 1 );
	}
}

dates_guidance_assert( false !== $source, 'Unable to read wp-seed-events.php.' );
$box_start    = strpos( $source, 'function wp_seed_events_render_occurrences_meta_box' );
$box_end      = strpos( $source, 'function wp_seed_events_save_occurrences', $box_start );
$box          = substr( $source, $box_start, $box_end - $box_start );
$guide_start  = strpos( $box, '<p class="description" data-wp-seed-date-save-guidance>' );
$guide_end    = strpos( $box, '</p>', $guide_start );
$guide        = substr( $box, $guide_start, $guide_end - $guide_start + 4 );
$save_button  = strpos( $box, 'data-wp-seed-date-save>Enregistrer la date</button>' );
$panel_end    = strpos( $box, "\n\t\t</div>", $save_button );
$foreach_end  = strrpos( substr( $box, 0, $guide_start ), '<?php endforeach; ?>' );
$script_start = strpos( $source, "wp_add_inline_script(\n\t\t'wp-seed-events-admin'" );
$admin_script = false === $script_start ? '' : substr( $source, $script_start );

dates_guidance_case( '1 message present', function () use ( $box, $message ) {
	dates_guidance_assert( false !== strpos( $box, $message ), 'Message missing.' );
} );
dates_guidance_case( '2 exact clear French wording', function () use ( $guide, $message ) {
	dates_guidance_assert( false !== strpos( $guide, $message ), 'Unexpected wording.' );
} );
dates_guidance_case( '3 close to save button', function () use ( $save_button, $guide_start ) {
	dates_guidance_assert( false !== $save_button && $guide_start > $save_button && $guide_start - $save_button < 300, 'Message is not adjacent to save controls.' );
} );
dates_guidance_case( '4 no submitted data', function () use ( $guide ) {
	foreach ( array( '<input', '<select', '<textarea', ' name=', ' value=' ) as $token ) {
		dates_guidance_assert( false === strpos( $guide, $token ), 'Submitted data found.' );
	}
} );
dates_guidance_case( '5 no autosave', function () use ( $guide ) {
	dates_guidance_assert( false === stripos( $guide, 'autosave' ), 'Autosave found.' );
} );
dates_guidance_case( '6 no REST call', function () use ( $guide ) {
	foreach ( array( 'wp-json', 'apiFetch', 'fetch(', 'XMLHttpRequest' ) as $token ) {
		dates_guidance_assert( false === strpos( $guide, $token ), 'REST behavior found.' );
	}
} );
dates_guidance_case( '7 no meta operation', function () use ( $guide ) {
	foreach ( array( 'get_post_meta', 'update_post_meta', 'delete_post_meta', '_wp_seed_' ) as $token ) {
		dates_guidance_assert( false === strpos( $guide, $token ), 'Meta operation found.' );
	}
} );
dates_guidance_case( '8 nonce unchanged', function () use ( $box ) {
	dates_guidance_assert( 1 === substr_count( $box, "wp_nonce_field( 'wp_seed_events_save_occurrences', 'wp_seed_events_occurrences_nonce' )" ), 'Nonce contract changed.' );
} );
dates_guidance_case( '9 persistence hook retained', function () use ( $source ) {
	dates_guidance_assert( 1 === substr_count( $source, "add_action( 'save_post_wp_seed_event', 'wp_seed_events_save_occurrences' )" ), 'Global save hook changed.' );
	dates_guidance_assert( false !== strpos( $source, 'function wp_seed_events_save_occurrences( $post_id )' ), 'Save callback missing.' );
} );
dates_guidance_case( '10 visible in empty state', function () use ( $guide_start, $panel_end, $guide ) {
	dates_guidance_assert( $guide_start > $panel_end && false === strpos( $guide, 'hidden' ), 'Message depends on hidden panel.' );
} );
dates_guidance_case( '11 visible with one date', function () use ( $guide_start, $foreach_end ) {
	dates_guidance_assert( $guide_start > $foreach_end, 'Message is inside occurrences loop.' );
} );
dates_guidance_case( '12 visible with several dates', function () use ( $box, $marker ) {
	dates_guidance_assert( 1 === substr_count( $box, $marker ), 'Message repeats per occurrence.' );
} );
dates_guidance_case( '13 retained after edit', function () use ( $admin_script, $marker ) {
	dates_guidance_assert( false === strpos( $admin_script, $marker ), 'JavaScript mutates message after edit.' );
} );
dates_guidance_case( '14 retained after removal', function () use ( $admin_script, $marker ) {
	dates_guidance_assert( false === strpos( $admin_script, $marker ), 'JavaScript mutates message after removal.' );
} );
dates_guidance_case( '15 no duplicate', function () use ( $source, $message, $marker ) {
	dates_guidance_assert( 1 === substr_count( $source, $message ) && 1 === substr_count( $source, $marker ), 'Message duplicated.' );
} );
dates_guidance_case( '16 admin only', function () use ( $box, $marker ) {
	dates_guidance_assert( false !== strpos( $box, $marker ), 'Message is outside admin meta box.' );
} );
dates_guidance_case( '17 no builder dependency', function () use ( $guide ) {
	foreach ( array( 'Divi', 'Gutenberg', 'Spectra', 'builder' ) as $token ) {
		dates_guidance_assert( false === stripos( $guide, $token ), 'Builder dependency found.' );
	}
} );
dates_guidance_case( '18 accessible native admin help', function () use ( $guide ) {
	dates_guidance_assert( false !== strpos( $guide, 'class="description"' ), 'Native description class missing.' );
	dates_guidance_assert( false === strpos( $guide, 'aria-live' ) && false === strpos( $guide, '<svg' ), 'Unnecessary live region or icon found.' );
} );
dates_guidance_case( '19 UTF-8 without BOM', function () use ( $source ) {
	dates_guidance_assert( "\xEF\xBB\xBF" !== substr( $source, 0, 3 ), 'BOM found.' );
	dates_guidance_assert( 1 === preg_match( '//u', $source ), 'Invalid UTF-8.' );
} );
dates_guidance_case( '20 no mojibake', function () use ( $source ) {
	foreach ( array( 'c383', 'c382', 'c3a2e282ac' ) as $signature ) {
		$token = hex2bin( $signature );
		dates_guidance_assert( false === strpos( $source, $token ), 'Mojibake signature found.' );
	}
} );

echo '[OK] ' . $cases . '/20 admin dates save guidance cases passed.' . PHP_EOL;
