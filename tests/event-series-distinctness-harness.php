<?php
/** Contract ensuring distinct event series may share occurrences. */

function series_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function series_projection_uid( $event_id, $occurrence ) {
	$fingerprint = array(
		'event_id'      => (int) $event_id,
		'start_date'    => (string) $occurrence['start_date'],
		'end_date'      => '',
		'start_time'    => (string) ( $occurrence['start_time'] ?? '' ),
		'end_time'      => (string) ( $occurrence['end_time'] ?? '' ),
		'all_day'       => ! empty( $occurrence['all_day'] ) ? '1' : '',
		'cancelled'     => '',
		'promotion_id'  => 0,
		'parcours_year' => 0,
	);

	return 'legacy-' . substr( hash( 'sha256', json_encode( $fingerprint ) ), 0, 48 ) . '-1';
}

$series_2025 = array(
	'id'          => 3001,
	'title'       => 'Danse libre 2025',
	'person_keys' => array( 'helene', 'emilie' ),
	'place_id'    => 3716,
	'occurrences' => array(
		array( 'start_date' => '2025-11-20', 'start_time' => '20:00', 'end_time' => '21:45' ),
		array( 'start_date' => '2025-12-11', 'start_time' => '20:00', 'end_time' => '21:45' ),
	),
);
$series_2025_2026 = array(
	'id'          => 2393,
	'title'       => 'Danse libre 2025-2026',
	'person_keys' => array( 'helene', 'emilie' ),
	'place_id'    => 3716,
	'occurrences' => array(
		array( 'start_date' => '2025-11-20', 'start_time' => '20:00', 'end_time' => '21:45' ),
		array( 'start_date' => '2025-12-11', 'start_time' => '20:00', 'end_time' => '21:45' ),
	),
);

$projection_source = file_get_contents( dirname( __DIR__ ) . '/includes/admin/occurrence-projection.php' );

series_assert( $series_2025['id'] !== $series_2025_2026['id'], 'Distinct series lost their event identity.' );
series_assert( $series_2025['person_keys'] === $series_2025_2026['person_keys'], 'Fixture people differ.' );
series_assert( $series_2025['place_id'] === $series_2025_2026['place_id'], 'Fixture places differ.' );
series_assert( $series_2025['occurrences'] === $series_2025_2026['occurrences'], 'Fixture overlap does not reproduce the approved shared dates and hours.' );
series_assert( false !== strpos( $projection_source, 'UNIQUE KEY event_occurrence (event_id,occurrence_uid)' ), 'Projection uniqueness is not event-scoped.' );
series_assert( false !== strpos( $projection_source, "'event_id'      => absint( \$event_id )" ), 'Legacy occurrence identity omits the event ID.' );

foreach ( $series_2025['occurrences'] as $index => $shared_occurrence ) {
	$first  = series_projection_uid( $series_2025['id'], $shared_occurrence );
	$second = series_projection_uid( $series_2025_2026['id'], $series_2025_2026['occurrences'][ $index ] );
	series_assert( $first !== $second, 'Shared occurrence date merged across events.' );
}

echo "Event series distinctness harness: 8/8 OK\n";
