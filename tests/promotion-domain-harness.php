<?php
/**
 * Promotion domain contract harness.
 *
 * Run with: php tests/promotion-domain-harness.php
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
define( 'OBJECT', 'OBJECT' );
define( 'WP_SEED_EVENTS_SHORT_DESCRIPTION_META_KEY', '_wp_seed_event_short_description' );

class WP_Post {
	public $ID;
	public $post_type;
	public $post_status;
	public $post_title;
	public $post_name;
	public $post_content;

	public function __construct( array $data ) {
		foreach ( $data as $key => $value ) {
			$this->{$key} = $value;
		}
	}
}

class WP_Error {
	private $code;
	private $message;
	private $data;

	public function __construct( $code, $message, $data = array() ) {
		$this->code = $code;
		$this->message = $message;
		$this->data = $data;
	}

	public function get_error_code() {
		return $this->code;
	}
}

class WP_REST_Server {
	const READABLE = 'GET';
}

class Promotion_Request {
	private $params;

	public function __construct( array $params ) {
		$this->params = $params;
	}

	public function get_param( $key ) {
		return $this->params[ $key ] ?? null;
	}
}

$GLOBALS['promotion_posts'] = array(
	10 => new WP_Post(
		array(
			'ID'           => 10,
			'post_type'    => 'wp_seed_promotion',
			'post_status'  => 'publish',
			'post_title'   => 'Promotion Soleil',
			'post_name'    => 'promotion-soleil',
			'post_content' => '<p>Promotion active.</p>',
		)
	),
	11 => new WP_Post(
		array(
			'ID'           => 11,
			'post_type'    => 'wp_seed_promotion',
			'post_status'  => 'publish',
			'post_title'   => 'Promotion Lune',
			'post_name'    => 'promotion-lune',
			'post_content' => '<p>Promotion historique.</p>',
		)
	),
	12 => new WP_Post(
		array(
			'ID'           => 12,
			'post_type'    => 'wp_seed_promotion',
			'post_status'  => 'draft',
			'post_title'   => 'Promotion privée',
			'post_name'    => 'promotion-privee',
			'post_content' => '',
		)
	),
	13 => new WP_Post(
		array(
			'ID'           => 13,
			'post_type'    => 'wp_seed_promotion',
			'post_status'  => 'publish',
			'post_title'   => 'Promotion Vague',
			'post_name'    => 'promotion-vague',
			'post_content' => '<p>Promotion parallèle.</p>',
		)
	),
	100 => new WP_Post(
		array(
			'ID'           => 100,
			'post_type'    => 'wp_seed_event',
			'post_status'  => 'publish',
			'post_title'   => 'Séminaire du souffle',
			'post_name'    => 'seminaire-du-souffle',
			'post_content' => 'Description publique.',
		)
	),
);
$GLOBALS['promotion_meta'] = array(
	10  => array(
		'_wp_seed_promotion_start_year' => 2026,
		'_wp_seed_promotion_status'     => 'active',
		'_wp_seed_promotion_order'      => 20,
	),
	11  => array(
		'_wp_seed_promotion_start_year' => 2024,
		'_wp_seed_promotion_status'     => 'archived',
		'_wp_seed_promotion_order'      => 10,
	),
	13  => array(
		'_wp_seed_promotion_start_year' => 2026,
		'_wp_seed_promotion_status'     => 'active',
		'_wp_seed_promotion_order'      => 30,
	),
	100 => array(
		'_wp_seed_event_occurrences' => array(
			array(
				'uid'           => '12345678-1234-4123-a123-123456789abc',
				'start_date'    => '2026-09-10',
				'start_time'    => '09:00',
				'promotion_id'  => 10,
				'parcours_year' => 1,
			),
			array(
				'uid'           => '22345678-1234-4123-a123-123456789abc',
				'start_date'    => '2027-03-10',
				'promotion_id'  => 11,
				'parcours_year' => 3,
			),
		),
	),
);
$GLOBALS['promotion_routes'] = array();
$GLOBALS['promotion_assertions'] = 0;

function absint( $value ) {
	return abs( (int) $value );
}

function sanitize_key( $value ) {
	return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $value ) );
}

function sanitize_title( $value ) {
	$value = strtolower( trim( (string) $value ) );
	$value = preg_replace( '/[^a-z0-9]+/', '-', $value );
	return trim( (string) $value, '-' );
}

function wp_unslash( $value ) {
	return $value;
}

function wp_unique_post_slug( $slug, $post_id, $post_status, $post_type, $post_parent ) {
	if ( 'promotion-soleil' === $slug && 10 !== (int) $post_id ) {
		return 'promotion-soleil-2';
	}

	return $slug;
}

function sanitize_text_field( $value ) {
	return trim( strip_tags( (string) $value ) );
}
function sanitize_textarea_field( $value ) {
	return trim( strip_tags( (string) $value ) );
}

function sanitize_file_name( $value ) {
	return basename( (string) $value );
}


function wp_kses_post( $value ) {
	return (string) $value;
}

function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, is_array( $args ) ? $args : array() );
}

function get_post( $post_id ) {
	return $GLOBALS['promotion_posts'][ absint( $post_id ) ] ?? null;
}

function get_page_by_path( $slug, $output, $post_type ) {
	foreach ( $GLOBALS['promotion_posts'] as $post ) {
		if ( $post_type === $post->post_type && $slug === $post->post_name ) {
			return $post;
		}
	}

	return null;
}

function get_post_meta( $post_id, $key, $single = true ) {
	return $GLOBALS['promotion_meta'][ absint( $post_id ) ][ $key ] ?? '';
}

function get_posts( $args ) {
	if ( 'ids' === ( $args['fields'] ?? '' ) ) {
		return array( 100 );
	}

	return array_values(
		array_filter(
			$GLOBALS['promotion_posts'],
			function ( $post ) use ( $args ) {
				return ( $args['post_type'] ?? '' ) === $post->post_type
					&& 'publish' === $post->post_status;
			}
		)
	);
}

function current_time( $format ) {
	return '2026-07-27';
}

function wp_seed_events_format_occurrence_date_line( $occurrence ) {
	return $occurrence['start_date'];
}

function wp_seed_events_format_occurrence_time_line( $occurrence ) {
	return $occurrence['start_time'];
}

function wp_seed_events_get_event_media( $event_id ) {
	return array(
		'featured_image'          => null,
		'communication_visual'    => null,
		'communication_visuals'   => array(),
		'other_visuals'           => array(),
		'event_document'          => null,
	);
}

function wp_seed_events_public_event_place_data( $event_id ) {
	return array();
}

function wp_seed_events_event_type_labels_for_event( $event_id ) {
	return array( 'Séminaire' );
}

function wp_seed_events_public_event_people_data( $event_id ) {
	return array();
}

function wp_seed_events_resolve_short_description( string $description, string $short_description = '', int $word_limit = 40 ): string {
	unset( $word_limit );
	return '' !== trim( $short_description ) ? $short_description : $description;
}

function get_permalink( $event_id ) {
	return 'https://example.test/evenement/' . absint( $event_id ) . '/';
}

function get_the_title( $event_id ) {
	$post = get_post( $event_id );
	return $post ? $post->post_title : '';
}

function esc_url_raw( $url, $protocols = null ) {
	return (string) $url;
}

function wp_parse_url( $url ) {
	return parse_url( $url );
}

function is_wp_error( $value ) {
	return $value instanceof WP_Error;
}

function current_user_can( $capability, $post_id = 0 ) {
	return true;
}

function register_rest_route( $namespace, $route, $args ) {
	$GLOBALS['promotion_routes'][ $namespace . $route ] = $args;
}

function rest_ensure_response( $value ) {
	return $value;
}

function rest_sanitize_boolean( $value ) {
	return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
}

require dirname( __DIR__ ) . '/includes/public/promotions.php';
require dirname( __DIR__ ) . '/includes/public/occurrences.php';
require dirname( __DIR__ ) . '/includes/public/event-data.php';
require dirname( __DIR__ ) . '/includes/admin/promotions.php';

function promotion_assert( $condition, $message ) {
	$GLOBALS['promotion_assertions']++;

	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function promotion_same( $expected, $actual, $message ) {
	promotion_assert( $expected === $actual, $message . ' Expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) . '.' );
}

$_POST['wp_seed_promotion_slug'] = 'Promotion Soleil';
$prepared_promotion              = wp_seed_events_prepare_promotion_post_data(
	array(
		'post_type'  => 'wp_seed_promotion',
		'post_name'  => '',
		'post_title' => 'Promotion Soleil',
	),
	array( 'ID' => 99 )
);
promotion_same( 'promotion-soleil-2', $prepared_promotion['post_name'], 'Duplicate Promotion slug was not uniquified.' );
unset( $_POST['wp_seed_promotion_slug'] );

$unrelated_post = wp_seed_events_prepare_promotion_post_data(
	array(
		'post_type' => 'post',
		'post_name' => 'article',
	),
	array( 'ID' => 200 )
);
promotion_same( 'article', $unrelated_post['post_name'], 'Promotion slug filter altered another post type.' );

$active = wp_seed_events_get_promotion( 10 );
promotion_same( 10, $active['id'], 'Promotion ID differs.' );
promotion_same( 'Promotion Soleil', $active['name'], 'Promotion name differs.' );
promotion_same( 'promotion-soleil', $active['slug'], 'Promotion slug differs.' );
promotion_same( 2026, $active['start_year'], 'Promotion start year differs.' );
promotion_same( 'active', $active['status'], 'Promotion status differs.' );
promotion_same( 20, $active['order'], 'Promotion order differs.' );
promotion_same( '<p>Promotion active.</p>', $active['description'], 'Promotion description differs.' );
promotion_same( $active, wp_seed_events_get_promotion( 'promotion-soleil' ), 'Slug lookup differs.' );
promotion_same( array(), wp_seed_events_get_promotion( 12 ), 'Draft Promotion leaked.' );
promotion_same( array(), wp_seed_events_get_promotion( 'missing' ), 'Unknown Promotion leaked.' );

$active_list = wp_seed_events_get_promotions();
promotion_same( array( 10, 13 ), array_column( $active_list, 'id' ), 'Default list is not active-only.' );
$all_list = wp_seed_events_get_promotions( array( 'status' => 'all' ) );
promotion_same( array( 11, 10, 13 ), array_column( $all_list, 'id' ), 'Promotion manual order differs.' );
$name_list = wp_seed_events_get_promotions( array( 'status' => 'all', 'orderby' => 'name' ) );
promotion_same( array( 11, 10, 13 ), array_column( $name_list, 'id' ), 'Promotion name order differs.' );
$archived_list = wp_seed_events_get_promotions( array( 'status' => 'archived' ) );
promotion_same( array( 11 ), array_column( $archived_list, 'id' ), 'Archived Promotion filter differs.' );
$descending_list = wp_seed_events_get_promotions( array( 'status' => 'all', 'order' => 'DESC' ) );
promotion_same( array( 13, 10, 11 ), array_column( $descending_list, 'id' ), 'Descending Promotion order differs.' );
$start_year_list = wp_seed_events_get_promotions( array( 'status' => 'all', 'orderby' => 'start_year' ) );
promotion_same( array( 11, 10, 13 ), array_column( $start_year_list, 'id' ), 'Promotion start-year order differs.' );
promotion_same( 2026, wp_seed_events_get_promotion( 13 )['start_year'], 'A duplicate Promotion start year was not preserved.' );

promotion_same( 1, wp_seed_events_normalize_parcours_year( 1 ), 'Year 1 rejected.' );
promotion_same( 4, wp_seed_events_normalize_parcours_year( '4' ), 'Year 4 rejected.' );
promotion_same( 0, wp_seed_events_normalize_parcours_year( 'texte' ), 'Text parcours year accepted.' );
promotion_same( 0, wp_seed_events_normalize_parcours_year( 5 ), 'Out-of-range year accepted.' );
promotion_same( '1re année', wp_seed_events_parcours_year_label( 1 ), 'First-year label differs.' );
promotion_same( '3e année', wp_seed_events_parcours_year_label( 3 ), 'Later-year label differs.' );
promotion_same( '', wp_seed_events_parcours_year_label( 0 ), 'Empty-year label differs.' );

promotion_same( true, wp_seed_events_validate_occurrence_parcours( 0, 0 ), 'Empty parcours pair rejected.' );
promotion_same( 'wp_seed_events_parcours_year_without_promotion', wp_seed_events_validate_occurrence_parcours( 0, 2 )->get_error_code(), 'Year without Promotion accepted.' );
promotion_same( 'wp_seed_events_promotion_without_parcours_year', wp_seed_events_validate_occurrence_parcours( 10, 0 )->get_error_code(), 'Promotion without year accepted.' );
promotion_same( 'wp_seed_events_invalid_promotion', wp_seed_events_validate_occurrence_parcours( 99, 2 )->get_error_code(), 'Unknown Promotion accepted.' );
promotion_same( 'wp_seed_events_archived_promotion', wp_seed_events_validate_occurrence_parcours( 11, 3 )->get_error_code(), 'New archived Promotion assignment accepted.' );
promotion_same( true, wp_seed_events_validate_occurrence_parcours( 11, 3, true ), 'Historical archived Promotion association rejected.' );

$occurrences = wp_seed_events_get_event_occurrences( 100 );
promotion_same( 2, count( $occurrences ), 'Occurrence count differs.' );
promotion_same( 10, $occurrences[0]['promotion_id'], 'Occurrence Promotion ID differs.' );
promotion_same( 'Promotion Soleil', $occurrences[0]['promotion']['name'], 'Occurrence Promotion object differs.' );
promotion_same( 1, $occurrences[0]['parcours_year'], 'Occurrence parcours year differs.' );
promotion_same( '1re année', $occurrences[0]['parcours_year_label'], 'Occurrence parcours label differs.' );
promotion_same( 'archived', $occurrences[1]['promotion']['status'], 'Archived historical Promotion is unreadable.' );

$legacy = wp_seed_events_normalize_occurrence( array( 'start_date' => '2028-01-10' ), 100, 2 );
promotion_same( 0, $legacy['promotion_id'], 'Legacy occurrence received a Promotion.' );
promotion_same( array(), $legacy['promotion'], 'Legacy occurrence Promotion is not empty.' );
promotion_same( 0, $legacy['parcours_year'], 'Legacy occurrence received a parcours year.' );
promotion_same( '', $legacy['parcours_year_label'], 'Legacy occurrence received a parcours label.' );

$invalid_pair = wp_seed_events_normalize_occurrence(
	array(
		'start_date'    => '2028-02-10',
		'promotion_id'  => 10,
		'parcours_year' => 9,
	),
	100,
	3
);
promotion_same( 0, $invalid_pair['promotion_id'], 'Invalid public pair leaked Promotion ID.' );
promotion_same( 0, $invalid_pair['parcours_year'], 'Invalid public pair leaked parcours year.' );

$event = wp_seed_events_get_event_data( 100 );
promotion_same( array( 10, 11 ), array_column( $event['promotions'], 'id' ), 'Event Data Promotions differ.' );
promotion_same( array( 1, 3 ), $event['parcours_years'], 'Event Data parcours years differ.' );
promotion_same( 'Séminaire du souffle', $event['title'], 'Event title/theme changed.' );
promotion_same( 'seminaire-du-souffle', $event['slug'], 'Event theme slug changed.' );
promotion_assert( array_key_exists( 'featured_image_id', $event ), 'Historical Event Data aliases disappeared.' );

promotion_assert( wp_seed_events_promotion_is_referenced( 10 ), 'Referenced Promotion was not detected.' );
promotion_assert( ! wp_seed_events_promotion_is_referenced( 99 ), 'Unknown Promotion was reported as referenced.' );
$blocked = wp_seed_events_prevent_referenced_promotion_deletion( null, $GLOBALS['promotion_posts'][10] );
promotion_same( false, $blocked, 'Referenced Promotion deletion was not blocked.' );
promotion_same( null, wp_seed_events_prevent_referenced_promotion_deletion( null, $GLOBALS['promotion_posts'][13] ), 'Unreferenced Promotion deletion was blocked.' );

wp_seed_events_register_promotion_rest_routes();
promotion_same( 3, count( $GLOBALS['promotion_routes'] ), 'REST route count differs.' );
promotion_assert( isset( $GLOBALS['promotion_routes']['wp-seed-events/v1/promotions'] ), 'Promotion collection route missing.' );
promotion_assert( isset( $GLOBALS['promotion_routes']['wp-seed-events/v1/promotions/(?P<identifier>[a-zA-Z0-9_-]+)'] ), 'Promotion item route missing.' );
promotion_assert( isset( $GLOBALS['promotion_routes']['wp-seed-events/v1/events/(?P<event_id>\d+)/occurrences'] ), 'Occurrence route missing.' );
promotion_assert( wp_seed_events_rest_can_read_event_occurrences( new Promotion_Request( array( 'event_id' => 100 ) ) ), 'Published occurrence REST permission was rejected.' );
promotion_assert( ! wp_seed_events_rest_can_read_event_occurrences( new Promotion_Request( array( 'event_id' => 999 ) ) ), 'Unknown occurrence REST permission was accepted.' );

$rest_promotions = wp_seed_events_rest_get_promotions( new Promotion_Request( array( 'status' => 'all', 'orderby' => 'order', 'order' => 'ASC' ) ) );
promotion_same( array( 11, 10, 13 ), array_column( $rest_promotions, 'id' ), 'REST Promotion collection differs.' );
promotion_same( 10, wp_seed_events_rest_get_promotion( new Promotion_Request( array( 'identifier' => 'promotion-soleil' ) ) )['id'], 'REST Promotion slug lookup differs.' );
promotion_assert( is_wp_error( wp_seed_events_rest_get_promotion( new Promotion_Request( array( 'identifier' => 'missing' ) ) ) ), 'Missing REST Promotion did not return an error.' );
$rest_occurrences = wp_seed_events_rest_get_event_occurrences( new Promotion_Request( array( 'event_id' => 100, 'include_cancelled' => true, 'status' => 'all' ) ) );
promotion_same( 10, $rest_occurrences[0]['promotion_id'], 'REST occurrence is not enriched.' );

$main  = file_get_contents( dirname( __DIR__ ) . '/wp-seed-events.php' );
$admin = file_get_contents( dirname( __DIR__ ) . '/includes/admin/promotions.php' );
promotion_assert( false !== strpos( $main, "register_post_type(\n\t\t'wp_seed_event'" ), 'Event CPT registration changed unexpectedly.' );
promotion_assert( false !== strpos( $main, 'wp_seed_events_register_promotion_post_type();' ), 'Promotion CPT registration missing.' );
promotion_assert( false !== strpos( $main, "manage_edit-wp_seed_promotion_sortable_columns" ), 'Promotion sortable columns hook missing.' );
promotion_assert( false !== strpos( $main, 'data-wp-seed-date-panel-field="promotion_id"' ), 'Promotion occurrence selector missing.' );
promotion_assert( false !== strpos( $main, 'data-wp-seed-date-panel-field="parcours_year"' ), 'Parcours year selector missing.' );
promotion_assert( false !== strpos( $main, '$GLOBALS[\'wp_seed_events_occurrences_validation_error\'] = true;' ), 'Atomic occurrence rejection missing.' );
promotion_assert( false === strpos( $main, 'wp_seed_events_lifecycle_v3' ), 'Lifecycle v3 started in the foundation lot.' );
promotion_assert( false !== strpos( $admin, "'publicly_queryable'  => false" ), 'Promotion post type became publicly queryable.' );
promotion_assert( false !== strpos( $admin, 'name="wp_seed_promotion_slug"' ), 'Editable Promotion slug field missing.' );
promotion_assert( false !== strpos( $admin, 'wp_unique_post_slug(' ), 'Promotion slug uniqueness enforcement missing.' );

echo 'Promotion domain harness: ' . $GLOBALS['promotion_assertions'] . '/' . $GLOBALS['promotion_assertions'] . ' OK' . PHP_EOL;
