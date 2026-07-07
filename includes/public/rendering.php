<?php
/**
 * Public rendering helpers for WP Seed Events.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

function wp_seed_events_public_event_data( $post_id ) {
	return wp_seed_events_get_event_data( $post_id );
}

function wp_seed_events_public_event_place_data( $post_id ) {
	$place_id = (int) get_post_meta( $post_id, '_wp_seed_event_place_id', true );

	if ( 0 === $place_id ) {
		return array();
	}

	$place = get_post( $place_id );

	if ( ! $place || 'wp_seed_place' !== $place->post_type ) {
		return array();
	}

	return array(
		'id'      => $place_id,
		'name'    => get_the_title( $place_id ),
		'address' => (string) get_post_meta( $place_id, '_wp_seed_place_address', true ),
		'link'    => (string) get_post_meta( $place_id, '_wp_seed_place_link', true ),
		'details' => (string) get_post_meta( $post_id, '_wp_seed_event_place_details', true ),
	);
}

function wp_seed_events_public_event_people_data( $post_id ) {
	$contacts = get_post_meta( $post_id, '_wp_seed_event_contacts', true );

	if ( ! is_array( $contacts ) ) {
		return array();
	}

	$roles  = wp_seed_events_contact_roles();
	$people = array();

	foreach ( $contacts as $contact ) {
		if ( ! is_array( $contact ) || empty( $contact['name'] ) ) {
			continue;
		}

		$people[] = array(
			'person_key' => wp_seed_events_contact_person_key( $contact ),
			'name'       => wp_seed_events_contact_name( $contact ),
			'role_keys'  => wp_seed_events_contact_role_keys( $contact, $roles ),
			'roles'      => wp_seed_events_contact_role_labels( $contact, $roles ),
			'phone'      => isset( $contact['phone'] ) ? (string) $contact['phone'] : '',
			'email'      => isset( $contact['email'] ) ? (string) $contact['email'] : '',
			'link'       => isset( $contact['link'] ) ? (string) $contact['link'] : '',
		);
	}

	return $people;
}

function wp_seed_events_public_event_excerpt( $content ) {
	$content = wp_strip_all_tags( strip_shortcodes( (string) $content ) );
	$content = trim( preg_replace( '/\s+/', ' ', $content ) );

	if ( '' === $content ) {
		return '';
	}

	return wp_trim_words( $content, 28, '?' );
}

function wp_seed_events_public_event_next_date_line( $event ) {
	if ( empty( $event['next_occurrence'] ) || ! is_array( $event['next_occurrence'] ) ) {
		return '';
	}

	return wp_seed_events_format_occurrence_date_line( $event['next_occurrence'] );
}

function wp_seed_events_public_event_next_time_line( $event ) {
	if ( empty( $event['next_occurrence'] ) || ! is_array( $event['next_occurrence'] ) ) {
		return '';
	}

	return wp_seed_events_format_occurrence_time_line( $event['next_occurrence'] );
}

function wp_seed_events_public_yes_no_option( $value, $default = true ) {
	$value = strtolower( trim( (string) $value ) );

	if ( 'yes' === $value ) {
		return true;
	}

	if ( 'no' === $value ) {
		return false;
	}

	return (bool) $default;
}

function wp_seed_events_public_date_format_option( $value ) {
	return 'short' === strtolower( trim( (string) $value ) ) ? 'short' : 'long';
}

function wp_seed_events_public_people_role_option( $value ) {
	$value   = strtolower( trim( (string) $value ) );
	$aliases = array(
		'all'                 => 'all',
		'organisateur'        => 'organizer',
		'organizer'           => 'organizer',
		'intervenant'         => 'speaker',
		'speaker'             => 'speaker',
		'contact_inscription' => 'registration_contact',
		'registration_contact' => 'registration_contact',
		'contact_information' => 'information_contact',
		'information_contact' => 'information_contact',
	);

	return $aliases[ $value ] ?? 'all';
}

function wp_seed_events_public_format_occurrence_date( $date, $format = 'long' ) {
	$date = (string) $date;

	if ( 'short' !== $format ) {
		return wp_seed_events_format_occurrence_date( $date );
	}

	if ( '' === $date ) {
		return 'Date sans jour défini';
	}

	$timestamp = strtotime( $date . ' 12:00:00' );

	if ( false === $timestamp ) {
		return $date;
	}

	return date_i18n( 'd/m/Y', $timestamp );
}

function wp_seed_events_public_event_occurrence_date_line( $occurrence, $format = 'long' ) {
	$start_date = $occurrence['start_date'] ?? '';
	$end_date   = $occurrence['end_date'] ?? '';

	if ( '' !== $end_date && $end_date !== $start_date ) {
		return wp_seed_events_public_format_occurrence_date( $start_date, $format ) . ' → ' . wp_seed_events_public_format_occurrence_date( $end_date, $format );
	}

	return wp_seed_events_public_format_occurrence_date( $start_date, $format );
}

function wp_seed_events_public_event_occurrence_time_line( $occurrence ) {
	return wp_seed_events_format_occurrence_time_line( $occurrence );
}

function wp_seed_events_public_phone_href( $phone ) {
	$phone = trim( (string) $phone );

	if ( '' === $phone ) {
		return '';
	}

	$href = preg_replace( '/[^0-9+]/', '', $phone );
	$href = preg_replace( '/(?!^)\+/', '', $href );

	return '' === $href ? '' : 'tel:' . $href;
}

function wp_seed_events_public_phone_link( $phone ) {
	$phone = trim( (string) $phone );
	$href  = wp_seed_events_public_phone_href( $phone );

	if ( '' === $phone ) {
		return '';
	}

	if ( '' === $href ) {
		return esc_html( $phone );
	}

	return '<a href="' . esc_attr( $href ) . '">' . esc_html( $phone ) . '</a>';
}

function wp_seed_events_public_email_link( $email ) {
	$email = trim( (string) $email );

	if ( '' === $email ) {
		return '';
	}

	return '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
}

function wp_seed_events_public_url_link( $url, $label = '' ) {
	$url = trim( (string) $url );

	if ( '' === $url ) {
		return '';
	}

	$label = '' === $label ? $url : $label;

	return '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
}

function wp_seed_events_public_template_path( $template_name ) {
	$template_name = ltrim( sanitize_text_field( $template_name ), '/\\' );
	$template_name = str_replace( array( '..', '\\' ), array( '', '/' ), $template_name );

	$candidates = array(
		trailingslashit( get_stylesheet_directory() ) . 'wp-seed-events/' . $template_name,
		trailingslashit( get_template_directory() ) . 'wp-seed-events/' . $template_name,
		trailingslashit( dirname( __DIR__, 2 ) ) . 'templates/' . $template_name,
	);

	foreach ( $candidates as $candidate ) {
		if ( file_exists( $candidate ) ) {
			return $candidate;
		}
	}

	return '';
}

function wp_seed_events_render_public_template( $template_name, $event, $fallback_callback = null ) {
	if ( array() === $event ) {
		return '';
	}

	$template_path = wp_seed_events_public_template_path( $template_name );

	if ( '' === $template_path ) {
		return is_callable( $fallback_callback ) ? (string) call_user_func( $fallback_callback, $event ) : '';
	}

	ob_start();
	include $template_path;
	return trim( ob_get_clean() );
}

function wp_seed_events_event_template_page_id() {
	$page_id = absint( get_option( 'wp_seed_events_event_template_page_id', 0 ) );

	return 'page' === get_post_type( $page_id ) ? $page_id : 0;
}

function wp_seed_events_render_public_event_template_page( $event ) {
	if ( empty( $event['id'] ) ) {
		return '';
	}

	$page_id = wp_seed_events_event_template_page_id();

	if ( 0 === $page_id ) {
		return '';
	}

	$page = get_post( $page_id );

	if ( ! $page || 'page' !== $page->post_type ) {
		return '';
	}

	return wp_seed_events_with_public_event_context(
		(int) $event['id'],
		function () use ( $page ) {
			return apply_filters( 'the_content', $page->post_content );
		}
	);
}

function wp_seed_events_with_public_event_context( $post_id, $callback ) {
	if ( ! is_callable( $callback ) ) {
		return '';
	}

	global $wp_seed_events_public_event_id;

	$previous_event_id              = $wp_seed_events_public_event_id ?? 0;
	$wp_seed_events_public_event_id = absint( $post_id );

	$output = (string) call_user_func( $callback );

	$wp_seed_events_public_event_id = $previous_event_id;

	return $output;
}

function wp_seed_events_render_public_event_card( $post_id ) {
	$event = wp_seed_events_public_event_data( $post_id );

	return wp_seed_events_render_public_template( 'event-card.php', $event, 'wp_seed_events_render_public_event_card_fallback' );
}

function wp_seed_events_event_collection_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'limit'  => 6,
			'status' => 'upcoming',
			'type'   => '',
			'pinned' => 'all',
		),
		$atts,
		'wp_seed_events'
	);

	$events = wp_seed_events_get_event_collection( $atts );

	return wp_seed_events_render_public_event_collection( $events );
}

function wp_seed_events_get_event_collection( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'limit'  => 6,
			'status' => 'upcoming',
			'type'   => '',
			'pinned' => 'all',
		)
	);

	$limit  = max( 1, absint( $args['limit'] ) );
	$status = wp_seed_events_public_collection_status( $args['status'] );
	$pinned = wp_seed_events_public_collection_pinned( $args['pinned'] );
	$type   = sanitize_title( (string) $args['type'] );

	$event_ids = get_posts(
		array(
			'post_type'      => 'wp_seed_event',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	$items = array();
	$now   = current_time( 'Y-m-d H:i' );

	foreach ( $event_ids as $event_id ) {
		$event_id = absint( $event_id );

		if ( ! wp_seed_events_public_collection_event_matches_type( $event_id, $type ) ) {
			continue;
		}

		$is_pinned = '1' === get_post_meta( $event_id, '_wp_seed_event_pinned', true );

		if ( 'only' === $pinned && ! $is_pinned ) {
			continue;
		}

		$event = wp_seed_events_get_event_data( $event_id );

		if ( array() === $event ) {
			continue;
		}

		$timing = wp_seed_events_public_collection_event_timing( $event, $now );

		if ( 'upcoming' === $status && ! $timing['is_upcoming'] ) {
			continue;
		}

		if ( 'past' === $status && ! $timing['is_past'] ) {
			continue;
		}

		$items[] = array(
			'event'     => $event,
			'is_pinned' => $is_pinned,
			'has_date'  => $timing['has_date'],
			'sort'      => $timing['sort'],
		);
	}

	usort(
		$items,
		function ( $first, $second ) use ( $status ) {
			if ( $first['is_pinned'] !== $second['is_pinned'] ) {
				return $first['is_pinned'] ? -1 : 1;
			}

			if ( $first['has_date'] !== $second['has_date'] ) {
				return $first['has_date'] ? -1 : 1;
			}

			$comparison = strcmp( (string) $first['sort'], (string) $second['sort'] );

			return 'past' === $status ? -$comparison : $comparison;
		}
	);

	$items = array_slice( $items, 0, $limit );

	return array_map(
		function ( $item ) {
			return $item['event'];
		},
		$items
	);
}

function wp_seed_events_public_collection_status( $value ) {
	$value = strtolower( trim( (string) $value ) );

	return in_array( $value, array( 'upcoming', 'past', 'all' ), true ) ? $value : 'upcoming';
}

function wp_seed_events_public_collection_pinned( $value ) {
	$value = strtolower( trim( (string) $value ) );

	return 'only' === $value ? 'only' : 'all';
}

function wp_seed_events_public_collection_event_matches_type( $event_id, $type ) {
	if ( '' === $type ) {
		return true;
	}

	foreach ( wp_seed_events_event_type_keys_for_event( $event_id ) as $type_key ) {
		if ( $type === wp_seed_events_event_type_public_slug( $type_key ) || $type === sanitize_title( $type_key ) ) {
			return true;
		}
	}

	return false;
}

function wp_seed_events_public_collection_event_timing( $event, $now ) {
	$occurrences = isset( $event['occurrences'] ) && is_array( $event['occurrences'] ) ? $event['occurrences'] : array();
	$dated       = array();

	foreach ( $occurrences as $occurrence ) {
		if ( ! is_array( $occurrence ) || empty( $occurrence['start_date'] ) || ! empty( $occurrence['cancelled'] ) ) {
			continue;
		}

		$sort = wp_seed_events_occurrence_sort_value( $occurrence );

		if ( '' === $sort ) {
			continue;
		}

		$dated[] = $sort;
	}

	if ( array() === $dated ) {
		return array(
			'has_date'    => false,
			'is_upcoming' => false,
			'is_past'     => false,
			'sort'        => '',
		);
	}

	sort( $dated, SORT_STRING );

	foreach ( $dated as $sort ) {
		if ( $sort >= $now ) {
			return array(
				'has_date'    => true,
				'is_upcoming' => true,
				'is_past'     => false,
				'sort'        => $sort,
			);
		}
	}

	return array(
		'has_date'    => true,
		'is_upcoming' => false,
		'is_past'     => true,
		'sort'        => end( $dated ),
	);
}

function wp_seed_events_render_public_event_collection( $events ) {
	if ( empty( $events ) || ! is_array( $events ) ) {
		return '<p class="wp-seed-events-list-empty">Aucun événement à afficher.</p>';
	}

	ob_start();
	?>
	<div class="wp-seed-events-list">
		<?php foreach ( $events as $event ) : ?>
			<?php if ( ! empty( $event['id'] ) ) : ?>
				<?php echo wp_seed_events_render_public_event_card( (int) $event['id'] ); ?>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
	<?php

	return trim( ob_get_clean() );
}

function wp_seed_events_render_public_event_single( $post_id, $use_template_page = false ) {
	$event = wp_seed_events_public_event_data( $post_id );

	if ( array() === $event ) {
		return '';
	}

	if ( $use_template_page ) {
		$template_output = wp_seed_events_render_public_event_template_page( $event );

		if ( '' !== trim( $template_output ) ) {
			return $template_output;
		}
	}

	return wp_seed_events_render_public_template( 'event-single.php', $event, 'wp_seed_events_render_public_event_single_fallback' );
}

function wp_seed_events_render_public_event_card_fallback( $event ) {
	$title = $event['title'] ?? '';
	$url   = $event['url'] ?? '';

	if ( '' === $title ) {
		return '';
	}

	ob_start();
	?>
	<article class="wp-seed-event-card">
		<h3><?php echo esc_html( $title ); ?></h3>
		<?php if ( '' !== wp_seed_events_public_event_next_date_line( $event ) ) : ?>
			<p><?php echo esc_html( wp_seed_events_public_event_next_date_line( $event ) ); ?></p>
		<?php endif; ?>
		<?php if ( $url ) : ?>
			<p><a href="<?php echo esc_url( $url ); ?>">En savoir plus</a></p>
		<?php endif; ?>
	</article>
	<?php

	return trim( ob_get_clean() );
}

function wp_seed_events_render_public_event_single_fallback( $event ) {
	return wp_seed_events_render_public_event_card_fallback( $event );
}

function wp_seed_events_event_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'id' => 0,
		),
		$atts,
		'wp_seed_event'
	);

	$post_id = wp_seed_events_public_shortcode_event_id( $atts['id'] );

	return wp_seed_events_render_public_event_single( $post_id );
}

function wp_seed_events_event_field_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'id'    => 0,
			'field' => 'title',
		),
		$atts,
		'wp_seed_event_field'
	);

	$post_id = wp_seed_events_public_shortcode_event_id( $atts['id'] );
	$event   = wp_seed_events_public_event_data( $post_id );

	if ( array() === $event ) {
		return '';
	}

	return wp_seed_events_public_event_field_value( $event, sanitize_key( $atts['field'] ) );
}

function wp_seed_events_public_shortcode_event_id( $raw_id ) {
	$post_id = absint( $raw_id );

	if ( 0 === $post_id ) {
		global $wp_seed_events_public_event_id;

		$post_id = absint( $wp_seed_events_public_event_id ?? 0 );
	}

	if ( 0 === $post_id && 'wp_seed_event' === get_post_type( get_the_ID() ) ) {
		$post_id = get_the_ID();
	}

	return $post_id;
}

function wp_seed_events_public_event_field_value( $event, $field ) {
	switch ( $field ) {
		case 'title':
			return esc_html( $event['title'] ?? '' );
		case 'url':
			return isset( $event['url'] ) ? esc_url( $event['url'] ) : '';
		case 'types':
			return empty( $event['types'] ) ? '' : esc_html( implode( ' • ', $event['types'] ) );
		case 'next_date':
			return esc_html( wp_seed_events_public_event_next_date_line( $event ) );
		case 'next_time':
			return esc_html( wp_seed_events_public_event_next_time_line( $event ) );
		case 'place':
			return empty( $event['place']['name'] ) ? '' : esc_html( $event['place']['name'] );
		case 'place_address':
			return empty( $event['place']['address'] ) ? '' : esc_html( $event['place']['address'] );
		case 'place_link':
			return empty( $event['place']['link'] ) ? '' : wp_seed_events_public_url_link( $event['place']['link'] );
		case 'phone':
			if ( empty( $event['people'] ) ) {
				return '';
			}

			foreach ( $event['people'] as $person ) {
				if ( ! empty( $person['phone'] ) ) {
					return wp_seed_events_public_phone_link( $person['phone'] );
				}
			}

			return '';
		case 'email':
			if ( empty( $event['people'] ) ) {
				return '';
			}

			foreach ( $event['people'] as $person ) {
				if ( ! empty( $person['email'] ) ) {
					return wp_seed_events_public_email_link( $person['email'] );
				}
			}

			return '';
		case 'person_link':
			if ( empty( $event['people'] ) ) {
				return '';
			}

			foreach ( $event['people'] as $person ) {
				if ( ! empty( $person['link'] ) ) {
					return wp_seed_events_public_url_link( $person['link'] );
				}
			}

			return '';
		case 'description':
			return empty( $event['description'] ) ? '' : apply_filters( 'the_content', $event['description'] );
		case 'excerpt':
			return empty( $event['excerpt'] ) ? '' : esc_html( $event['excerpt'] );
		case 'image':
			if ( empty( $event['primary_image_id'] ) ) {
				return '';
			}

			return wp_get_attachment_image(
				(int) $event['primary_image_id'],
				'large',
				false,
				array(
					'class'   => 'wp-seed-event-image',
					'loading' => 'lazy',
					'style'   => 'max-width:100%;height:auto;',
				)
			);
		case 'flyer':
			if ( empty( $event['flyer_pdf_id'] ) ) {
				return '';
			}

			$url = wp_get_attachment_url( (int) $event['flyer_pdf_id'] );

			return $url ? wp_seed_events_public_url_link( $url, 'Télécharger le flyer' ) : '';
		default:
			return '';
	}
}

function wp_seed_events_public_event_for_shortcode( $raw_id ) {
	$post_id = wp_seed_events_public_shortcode_event_id( $raw_id );

	return wp_seed_events_public_event_data( $post_id );
}

function wp_seed_events_event_dates_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'id'             => 0,
			'format'         => 'long',
			'show_time'      => 'yes',
			'show_cancelled' => 'yes',
		),
		$atts,
		'wp_seed_event_dates'
	);

	$event = wp_seed_events_public_event_for_shortcode( $atts['id'] );

	return wp_seed_events_render_public_event_dates_section(
		$event,
		array(
			'format'         => wp_seed_events_public_date_format_option( $atts['format'] ),
			'show_time'      => wp_seed_events_public_yes_no_option( $atts['show_time'], true ),
			'show_cancelled' => wp_seed_events_public_yes_no_option( $atts['show_cancelled'], true ),
		)
	);
}

function wp_seed_events_event_people_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'id'      => 0,
			'role'    => 'all',
			'details' => 'yes',
		),
		$atts,
		'wp_seed_event_people'
	);

	$event = wp_seed_events_public_event_for_shortcode( $atts['id'] );

	return wp_seed_events_render_public_event_people_section(
		$event,
		array(
			'role'    => wp_seed_events_public_people_role_option( $atts['role'] ),
			'details' => wp_seed_events_public_yes_no_option( $atts['details'], true ),
		)
	);
}

function wp_seed_events_event_place_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'id' => 0,
		),
		$atts,
		'wp_seed_event_place'
	);

	$event = wp_seed_events_public_event_for_shortcode( $atts['id'] );

	return wp_seed_events_render_public_event_place_section( $event );
}

function wp_seed_events_event_practical_info_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'id' => 0,
		),
		$atts,
		'wp_seed_event_practical_info'
	);

	$event = wp_seed_events_public_event_for_shortcode( $atts['id'] );

	return wp_seed_events_render_public_event_practical_info_section( $event );
}

function wp_seed_events_render_public_event_dates_section( $event, $options = array() ) {
	if ( empty( $event['occurrences'] ) || ! is_array( $event['occurrences'] ) ) {
		return '';
	}

	$options = wp_parse_args(
		$options,
		array(
			'format'         => 'long',
			'show_time'      => true,
			'show_cancelled' => true,
		)
	);

	$occurrences = array_filter(
		$event['occurrences'],
		function ( $occurrence ) use ( $options ) {
			if ( ! empty( $occurrence['cancelled'] ) && ! $options['show_cancelled'] ) {
				return false;
			}

			return '' !== wp_seed_events_public_event_occurrence_date_line( $occurrence, $options['format'] );
		}
	);

	if ( array() === $occurrences ) {
		return '';
	}

	ob_start();
	?>
	<section class="wp-seed-event-section wp-seed-event-section--dates">
		<h2>Dates</h2>
		<ul class="wp-seed-event-dates">
			<?php foreach ( $occurrences as $occurrence ) : ?>
				<?php
				$date_line = wp_seed_events_public_event_occurrence_date_line( $occurrence, $options['format'] );
				$time_line = $options['show_time'] ? wp_seed_events_public_event_occurrence_time_line( $occurrence ) : '';
				?>
				<li class="wp-seed-event-date<?php echo ! empty( $occurrence['cancelled'] ) ? ' is-cancelled' : ''; ?>">
					<strong><?php echo esc_html( $date_line ); ?></strong>
					<?php if ( ! empty( $occurrence['cancelled'] ) ) : ?>
						<span class="wp-seed-event-date-status">Annulée</span>
					<?php endif; ?>
					<?php if ( '' !== $time_line ) : ?>
						<br /><span><?php echo esc_html( $time_line ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
	<?php

	return trim( ob_get_clean() );
}

function wp_seed_events_render_public_event_place_section( $event ) {
	$place = $event['place'] ?? array();

	if ( empty( $place['name'] ) && empty( $place['address'] ) && empty( $place['link'] ) ) {
		return '';
	}

	ob_start();
	?>
	<section class="wp-seed-event-section wp-seed-event-section--place">
		<h2>Lieu</h2>
		<?php if ( ! empty( $place['name'] ) ) : ?>
			<p><strong><?php echo esc_html( $place['name'] ); ?></strong></p>
		<?php endif; ?>
		<?php if ( ! empty( $place['address'] ) ) : ?>
			<p><?php echo esc_html( $place['address'] ); ?></p>
		<?php endif; ?>
		<?php if ( ! empty( $place['link'] ) ) : ?>
			<p><?php echo wp_seed_events_public_url_link( $place['link'] ); ?></p>
		<?php endif; ?>
	</section>
	<?php

	return trim( ob_get_clean() );
}

function wp_seed_events_render_public_event_practical_info_section( $event ) {
	$details = $event['place']['details'] ?? '';

	if ( '' === trim( (string) $details ) ) {
		return '';
	}

	ob_start();
	?>
	<section class="wp-seed-event-section wp-seed-event-section--practical-info">
		<h2>Informations pratiques</h2>
		<p><?php echo nl2br( esc_html( $details ) ); ?></p>
	</section>
	<?php

	return trim( ob_get_clean() );
}

function wp_seed_events_render_public_event_people_section( $event, $options = array() ) {
	if ( empty( $event['people'] ) || ! is_array( $event['people'] ) ) {
		return '';
	}

	$options = wp_parse_args(
		$options,
		array(
			'role'    => 'all',
			'details' => true,
		)
	);

	$people = array_filter(
		$event['people'],
		function ( $person ) use ( $options ) {
			if ( empty( $person['name'] ) ) {
				return false;
			}

			if ( 'all' === $options['role'] ) {
				return true;
			}

			$role_keys = isset( $person['role_keys'] ) && is_array( $person['role_keys'] ) ? $person['role_keys'] : array();

			return in_array( $options['role'], $role_keys, true );
		}
	);

	if ( array() === $people ) {
		return '';
	}

	ob_start();
	?>
	<section class="wp-seed-event-section wp-seed-event-section--people">
		<h2>Contacts et intervenants</h2>
		<ul class="wp-seed-event-people">
			<?php foreach ( $people as $person ) : ?>
				<li class="wp-seed-event-person">
					<strong><?php echo esc_html( $person['name'] ); ?></strong>
					<?php if ( $options['details'] && ! empty( $person['roles'] ) && is_array( $person['roles'] ) ) : ?>
						<br /><span><?php echo esc_html( implode( ' • ', $person['roles'] ) ); ?></span>
					<?php endif; ?>
					<?php if ( $options['details'] && ! empty( $person['phone'] ) ) : ?>
						<br /><?php echo wp_seed_events_public_phone_link( $person['phone'] ); ?>
					<?php endif; ?>
					<?php if ( $options['details'] && ! empty( $person['email'] ) ) : ?>
						<br /><?php echo wp_seed_events_public_email_link( $person['email'] ); ?>
					<?php endif; ?>
					<?php if ( $options['details'] && ! empty( $person['link'] ) ) : ?>
						<br /><?php echo wp_seed_events_public_url_link( $person['link'] ); ?>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
	<?php

	return trim( ob_get_clean() );
}
