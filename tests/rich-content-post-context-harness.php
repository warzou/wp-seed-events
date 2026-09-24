<?php
/**
 * Rich Content media output and global post restoration regression harness.
 *
 * Run with: php tests/rich-content-post-context-harness.php
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

function add_action() {}
function wp_kses_post( $value ) {
	return preg_replace( '~<script\b[^>]*>.*?</script>~is', '', (string) $value );
}
function apply_filters( $hook, $value ) {
	if ( 'the_content' !== $hook ) {
		return $value;
	}

	$GLOBALS['qa_filter_calls']++;
	if ( ! empty( $GLOBALS['qa_throw'] ) ) {
		$GLOBALS['post'] = (object) array( 'ID' => 2339 );
		throw new RuntimeException( 'content callback failed' );
	}

	$value = str_replace( 'https://youtu.be/qa-video', '<iframe src="https://www.youtube.com/embed/qa-video"></iframe>', $value );
	if ( false !== strpos( $value, 'https://media.example.test/interview.mp3' ) ) {
		// Mimic Divi's audio shortcode: its final wp_reset_postdata() leaves
		// the main query event in the global post during Theme Builder rendering.
		$GLOBALS['post'] = (object) array( 'ID' => 2339 );
		$value = str_replace(
			'https://media.example.test/interview.mp3',
			'<audio controls><source src="https://media.example.test/interview.mp3" type="audio/mpeg"></audio>',
			$value
		);
	}

	return $value;
}

require dirname( __DIR__ ) . '/includes/public/descriptions.php';

function qa_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

$layout_post = (object) array( 'ID' => 2773 );
$cases = array(
	'text'    => array( '<p>Texte seul</p>', array( '<p>Texte seul</p>' ) ),
	'youtube' => array( 'https://youtu.be/qa-video', array( '<iframe', 'youtube.com/embed/qa-video' ) ),
	'audio'   => array( 'https://media.example.test/interview.mp3', array( '<audio', '<source', 'interview.mp3' ) ),
	'both'    => array( "https://youtu.be/qa-video\n\nhttps://media.example.test/interview.mp3", array( '<iframe', '<audio', '<source' ) ),
);

foreach ( $cases as $name => $case ) {
	$GLOBALS['post']            = $layout_post;
	$GLOBALS['qa_filter_calls'] = 0;
	$result = wp_seed_events_render_rich_content( $case[0] );
	foreach ( $case[1] as $expected ) {
		qa_assert( false !== strpos( $result, $expected ), $name . ': missing ' . $expected );
	}
	qa_assert( $GLOBALS['post'] === $layout_post, $name . ': original WP_Post object was not restored' );
	qa_assert( 1 === $GLOBALS['qa_filter_calls'], $name . ': the_content was not applied exactly once' );
}

$GLOBALS['post'] = null;
wp_seed_events_render_rich_content( 'https://media.example.test/interview.mp3' );
qa_assert( array_key_exists( 'post', $GLOBALS ) && null === $GLOBALS['post'], 'Null post was not restored' );

unset( $GLOBALS['post'] );
wp_seed_events_render_rich_content( 'https://media.example.test/interview.mp3' );
qa_assert( ! array_key_exists( 'post', $GLOBALS ), 'Undefined post was not restored' );

$GLOBALS['post']     = $layout_post;
$GLOBALS['qa_throw'] = true;
try {
	wp_seed_events_render_rich_content( 'trigger exception' );
	throw new RuntimeException( 'Expected callback exception was not thrown' );
} catch ( RuntimeException $error ) {
	qa_assert( 'content callback failed' === $error->getMessage(), 'Unexpected callback exception' );
}
qa_assert( $GLOBALS['post'] === $layout_post, 'Post was not restored after exception' );

echo "Rich Content post context: 4 media cases, null, undefined, exception PASS\n";
