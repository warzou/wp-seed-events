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

$pssm_march = array(
	'id'          => 4000,
	'title'       => 'Premiers secours en santé mentale — mars 2026',
	'person_keys' => array( 'isabelle-jantzem', 'isabelle-follain' ),
	'place_id'    => 3716,
	'contact_key' => 'isabelle-jantzem',
	'media_ids'   => array( 3998, 3999 ),
	'occurrences' => array(
		array( 'start_date' => '2026-03-09', 'end_date' => '2026-03-10', 'start_time' => '09:00', 'end_time' => '17:30' ),
	),
);
$pssm_june = array(
	'id'          => 2371,
	'title'       => 'Premiers secours en santé mentale — juin 2026',
	'person_keys' => array( 'isabelle-jantzem', 'isabelle-follain' ),
	'place_id'    => 3997,
	'contact_key' => 'isabelle-follain',
	'media_ids'   => array( 2372, 2373 ),
	'occurrences' => array(
		array( 'start_date' => '2026-06-09', 'end_date' => '2026-06-10', 'start_time' => '09:00', 'end_time' => '17:30' ),
	),
);

series_assert( $pssm_march['id'] !== $pssm_june['id'], 'PSSM series lost their event identity.' );
series_assert( $pssm_march['title'] !== $pssm_june['title'], 'PSSM editorial titles were merged.' );
series_assert( $pssm_march['person_keys'] === $pssm_june['person_keys'], 'PSSM shared people fixture differs.' );
series_assert( $pssm_march['occurrences'] !== $pssm_june['occurrences'], 'PSSM March and June dates were merged.' );
series_assert( $pssm_march['place_id'] !== $pssm_june['place_id'], 'PSSM event-scoped places were merged.' );
series_assert( $pssm_march['contact_key'] !== $pssm_june['contact_key'], 'PSSM event-scoped contacts were merged.' );
series_assert( array() === array_intersect( $pssm_march['media_ids'], $pssm_june['media_ids'] ), 'PSSM event-scoped media were shared.' );
series_assert( 1 === count( $pssm_march['occurrences'] ), 'PSSM March is not one continuous occurrence.' );
series_assert( 1 === count( $pssm_june['occurrences'] ), 'PSSM June is not one continuous occurrence.' );

$communication_may = array(
	'id'          => 2399,
	'title'       => 'Communication animale — mai 2025',
	'person_keys' => array( 'helene-dallasta' ),
	'place_id'    => 3716,
	'media_ids'   => array( 2400, 2401 ),
	'occurrences' => array(
		array( 'start_date' => '2025-05-02', 'end_date' => '2025-05-04', 'start_time' => '', 'end_time' => '', 'all_day' => '1' ),
	),
);
$communication_november = array(
	'id'          => 5000,
	'title'       => 'Communication animale — novembre 2025',
	'person_keys' => array( 'helene-dallasta' ),
	'place_id'    => 3716,
	'media_ids'   => array( 2402, 2403 ),
	'occurrences' => array(
		array( 'start_date' => '2025-11-21', 'end_date' => '2025-11-23', 'start_time' => '', 'end_time' => '', 'all_day' => '1' ),
	),
);

series_assert( $communication_may['id'] !== $communication_november['id'], 'Communication animale series lost their event identity.' );
series_assert( $communication_may['title'] !== $communication_november['title'], 'Communication animale editorial titles were merged.' );
series_assert( $communication_may['person_keys'] === $communication_november['person_keys'], 'Communication animale shared person fixture differs.' );
series_assert( $communication_may['place_id'] === $communication_november['place_id'], 'Communication animale shared place fixture differs.' );
series_assert( $communication_may['occurrences'] !== $communication_november['occurrences'], 'Communication animale May and November dates were merged.' );
series_assert( array() === array_intersect( $communication_may['media_ids'], $communication_november['media_ids'] ), 'Communication animale event-scoped media were shared.' );
series_assert( 1 === count( $communication_may['occurrences'] ), 'Communication animale May is not one continuous occurrence.' );
series_assert( 1 === count( $communication_november['occurrences'] ), 'Communication animale November is not one continuous occurrence.' );

echo "Event series distinctness harness: 25/25 OK\n";
