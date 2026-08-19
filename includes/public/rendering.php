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



function wp_seed_events_public_event_next_date_line( $event ) {
	$occurrence = wp_seed_events_public_event_next_occurrence( $event );

	if ( array() === $occurrence ) {
		return '';
	}

	return wp_seed_events_format_occurrence_date_line( $occurrence );
}

function wp_seed_events_public_event_next_time_line( $event ) {
	$occurrence = wp_seed_events_public_event_next_occurrence( $event );

	if ( array() === $occurrence ) {
		return '';
	}

	return wp_seed_events_format_occurrence_time_line( $occurrence );
}

function wp_seed_events_public_event_display_date_line( $event ) {
	$occurrence = wp_seed_events_public_event_display_occurrence( $event );

	if ( array() === $occurrence ) {
		return '';
	}

	return wp_seed_events_format_occurrence_date_line( $occurrence );
}

function wp_seed_events_public_event_display_time_line( $event ) {
	$occurrence = wp_seed_events_public_event_display_occurrence( $event );

	if ( array() === $occurrence ) {
		return '';
	}

	return wp_seed_events_format_occurrence_time_line( $occurrence );
}

function wp_seed_events_public_event_next_occurrence( $event ) {
	if ( ! empty( $event['next_occurrence'] ) && is_array( $event['next_occurrence'] ) ) {
		return $event['next_occurrence'];
	}

	return array();
}

function wp_seed_events_public_event_display_occurrence( $event ) {
	if ( ! empty( $event['display_occurrence'] ) && is_array( $event['display_occurrence'] ) ) {
		return $event['display_occurrence'];
	}

	if ( ! empty( $event['next_occurrence'] ) && is_array( $event['next_occurrence'] ) ) {
		return $event['next_occurrence'];
	}

	return array();
}

function wp_seed_events_public_yes_no_option( $value, $default = true ) {
	if ( ! is_scalar( $value ) ) {
		return (bool) $default;
	}

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
	if ( ! is_scalar( $value ) ) {
		return 'long';
	}

	return 'short' === strtolower( trim( (string) $value ) ) ? 'short' : 'long';
}

function wp_seed_events_public_date_scope_option( $value ) {
	if ( ! is_scalar( $value ) ) {
		return 'all';
	}

	$value = strtolower( trim( (string) $value ) );

	return in_array( $value, array( 'all', 'upcoming', 'past' ), true ) ? $value : 'all';
}

function wp_seed_events_public_date_mode_option( $value ) {
	if ( ! is_scalar( $value ) ) {
		return 'all';
	}

	$value = strtolower( trim( (string) $value ) );

	return in_array( $value, array( 'next', 'first', 'last', 'all' ), true ) ? $value : 'all';
}

function wp_seed_events_public_heading_level_option( $value ) {
	if ( ! is_scalar( $value ) ) {
		return 'h2';
	}

	$value = strtolower( trim( (string) $value ) );

	return in_array( $value, array( 'h2', 'h3', 'h4', 'h5', 'h6' ), true ) ? $value : 'h2';
}

function wp_seed_events_public_boolean_option( $value, $default = true ) {
	if ( is_bool( $value ) ) {
		return $value;
	}

	if ( ! is_scalar( $value ) ) {
		return (bool) $default;
	}

	$value = strtolower( trim( (string) $value ) );

	if ( in_array( $value, array( '1', 'yes', 'true', 'on' ), true ) ) {
		return true;
	}

	if ( in_array( $value, array( '0', 'no', 'false', 'off' ), true ) ) {
		return false;
	}

	return (bool) $default;
}

function wp_seed_events_public_visuals_layout_option( $value ) {
	if ( ! is_scalar( $value ) ) {
		return 'grid';
	}

	$value = strtolower( trim( (string) $value ) );

	return in_array( $value, array( 'grid', 'list' ), true ) ? $value : 'grid';
}

function wp_seed_events_public_visuals_image_size_option( $value ) {
	if ( ! is_scalar( $value ) ) {
		return 'large';
	}

	$value = sanitize_key( trim( (string) $value ) );
	$sizes = function_exists( 'get_intermediate_image_sizes' ) ? get_intermediate_image_sizes() : array();

	return in_array( $value, $sizes, true ) ? $value : 'large';
}

function wp_seed_events_public_people_role_option( $value ) {
	$value   = is_scalar( $value ) ? strtolower( trim( (string) $value ) ) : '';
	$aliases = array(
		'all'                 => 'all',
		'organisateur'        => 'organizer',
		'organizer'           => 'organizer',
		'intervenant'         => 'speaker',
		'speaker'             => 'speaker',
		'contact'             => 'contact',
		'contact_inscription' => 'contact',
		'registration_contact' => 'contact',
		'contact_information' => 'contact',
		'information_contact' => 'contact',
	);

	return $aliases[ $value ] ?? 'all';
}

function wp_seed_events_public_people_roles_option( $value ) {
	$raw_roles = is_array( $value ) ? $value : ( is_scalar( $value ) ? explode( ',', (string) $value ) : array() );
	$roles     = array();

	foreach ( $raw_roles as $raw_role ) {
		if ( ! is_scalar( $raw_role ) ) {
			continue;
		}

		$raw_role = strtolower( trim( (string) $raw_role ) );

		if ( 'all' === $raw_role ) {
			return array();
		}

		$role = wp_seed_events_public_people_role_option( $raw_role );

		if ( 'all' !== $role && ! in_array( $role, $roles, true ) ) {
			$roles[] = $role;
		}
	}

	return $roles;
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
			'order'  => '',
		),
		$atts,
		'wp_seed_events'
	);

	$events = wp_seed_events_get_event_collection( $atts );

	return wp_seed_events_render_public_event_collection( $events );
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
			return trim( $template_output . "\n" . wp_seed_events_render_event_share_menu( $event ) );
		}
	}

	return trim(
		wp_seed_events_render_public_template( 'event-single.php', $event, 'wp_seed_events_render_public_event_single_fallback' )
		. "\n"
		. wp_seed_events_render_event_share_menu( $event )
	);
}

function wp_seed_events_render_public_event_card_fallback( $event ) {
	$title        = $event['title'] ?? '';
	$url          = $event['url'] ?? '';
	$display_date = wp_seed_events_public_event_display_date_line( $event );

	if ( '' === $title ) {
		return '';
	}

	ob_start();
	?>
	<article class="wp-seed-event-card">
		<h3><?php echo esc_html( $title ); ?></h3>
		<?php if ( '' !== $display_date ) : ?>
			<p><?php echo esc_html( $display_date ); ?></p>
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
		case 'display_date':
			return esc_html( wp_seed_events_public_event_display_date_line( $event ) );
		case 'display_time':
			return esc_html( wp_seed_events_public_event_display_time_line( $event ) );
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
			return empty( $event['excerpt'] ) ? '' : nl2br( esc_html( $event['excerpt'] ) );
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

			return $url ? wp_seed_events_public_url_link( $url, 'Télécharger le document PDF' ) : '';
		default:
			return '';
	}
}

function wp_seed_events_public_event_for_shortcode( $raw_id ) {
	$post_id = wp_seed_events_public_shortcode_event_id( $raw_id );

	return wp_seed_events_public_event_data( $post_id );
}

function wp_seed_events_event_dates_shortcode_event_id( $raw_atts ) {
	if ( is_array( $raw_atts ) && array_key_exists( 'id', $raw_atts ) ) {
		if ( ! is_scalar( $raw_atts['id'] ) ) {
			return 0;
		}

		if ( '' !== trim( (string) $raw_atts['id'] ) ) {
			return absint( $raw_atts['id'] );
		}
	}

	return wp_seed_events_public_shortcode_event_id( 0 );
}

function wp_seed_events_event_dates_shortcode( $atts ) {
	$raw_atts = is_array( $atts ) ? $atts : array();
	$atts = shortcode_atts(
		array(
			'id'                  => 0,
			'title'               => 'Dates',
			'heading_level'       => 'h2',
			'mode'                => 'all',
			'scope'               => 'all',
			'show_cancelled'      => 'yes',
			'show_times'          => 'yes',
			'show_calendar_links' => 'yes',
			'format'              => 'long',
			'show_time'           => 'yes',
		),
		$raw_atts,
		'wp_seed_event_dates'
	);

	$post_id = wp_seed_events_event_dates_shortcode_event_id( $raw_atts );

	if ( 0 === $post_id ) {
		return '';
	}

	$event = wp_seed_events_public_event_data( $post_id );

	if ( array() === $event ) {
		return '';
	}

	$show_times_value = array_key_exists( 'show_times', $raw_atts ) ? $atts['show_times'] : $atts['show_time'];

	return wp_seed_events_render_public_event_dates_section(
		$event,
		array(
			'title'               => is_scalar( $atts['title'] ) ? (string) $atts['title'] : 'Dates',
			'mode'                => wp_seed_events_public_date_mode_option( $atts['mode'] ),
			'heading_level'       => wp_seed_events_public_heading_level_option( $atts['heading_level'] ),
			'scope'               => wp_seed_events_public_date_scope_option( $atts['scope'] ),
			'show_cancelled'      => wp_seed_events_public_yes_no_option( $atts['show_cancelled'], true ),
			'show_times'          => wp_seed_events_public_yes_no_option( $show_times_value, true ),
			'show_calendar_links' => wp_seed_events_public_yes_no_option( $atts['show_calendar_links'], true ),
			'format'              => wp_seed_events_public_date_format_option( $atts['format'] ),
		)
	);
}

function wp_seed_events_event_visuals_shortcode_event_id( $raw_atts ) {
	if ( is_array( $raw_atts ) && array_key_exists( 'id', $raw_atts ) ) {
		if ( ! is_scalar( $raw_atts['id'] ) ) {
			return 0;
		}

		$raw_id = trim( (string) $raw_atts['id'] );

		if ( '' === $raw_id || ! preg_match( '/^[0-9]+$/', $raw_id ) ) {
			return 0;
		}

		$post_id = absint( $raw_id );

		return $post_id > 0 ? $post_id : 0;
	}

	return wp_seed_events_public_shortcode_event_id( 0 );
}

function wp_seed_events_event_visuals_shortcode( $atts ) {
	$raw_atts = is_array( $atts ) ? $atts : array();
	$atts     = shortcode_atts(
		array(
			'id'             => 0,
			'title'          => 'Visuels de communication',
			'heading_level'  => 'h2',
			'show_flyer'     => true,
			'show_visuals'   => true,
			'show_document'  => true,
			'show_captions'  => false,
			'image_size'     => 'large',
			'link_original'  => true,
			'layout'         => 'grid',
		),
		$raw_atts,
		'wp_seed_event_visuals'
	);

	$post_id = wp_seed_events_event_visuals_shortcode_event_id( $raw_atts );

	if ( 0 === $post_id ) {
		return '';
	}

	$event = wp_seed_events_public_event_data( $post_id );

	if ( array() === $event ) {
		return '';
	}

	return wp_seed_events_render_public_event_visuals_section(
		$event,
		array(
			'title'          => $atts['title'],
			'heading_level'  => $atts['heading_level'],
			'show_flyer'     => $atts['show_flyer'],
			'show_visuals'   => $atts['show_visuals'],
			'show_document'  => $atts['show_document'],
			'show_captions'  => $atts['show_captions'],
			'image_size'     => $atts['image_size'],
			'link_original'  => $atts['link_original'],
			'layout'         => $atts['layout'],
		)
	);
}

function wp_seed_events_event_people_shortcode_event_id( $raw_atts ) {
	if ( is_array( $raw_atts ) && array_key_exists( 'id', $raw_atts ) ) {
		if ( ! is_scalar( $raw_atts['id'] ) ) {
			return 0;
		}

		$raw_id = trim( (string) $raw_atts['id'] );

		if ( '' === $raw_id || ! preg_match( '/^[0-9]+$/', $raw_id ) ) {
			return 0;
		}

		$post_id = absint( $raw_id );

		return $post_id > 0 ? $post_id : 0;
	}

	return wp_seed_events_public_shortcode_event_id( 0 );
}

function wp_seed_events_event_people_shortcode( $atts ) {
	$raw_atts = is_array( $atts ) ? $atts : array();
	$atts     = shortcode_atts(
		array(
			'id'            => 0,
			'roles'         => '',
			'role'          => 'all',
			'details'       => 'yes',
			'title'         => 'Contacts et intervenants',
			'heading_level' => 'h2',
			'show_name'     => 'yes',
			'show_roles'    => 'yes',
			'show_email'    => 'yes',
			'show_phone'    => 'yes',
			'show_link'     => 'yes',
			'link_phone'    => 'yes',
			'link_email'    => 'yes',
			'link_url'      => 'yes',
			'layout'        => 'list',
		),
		$raw_atts,
		'wp_seed_event_people'
	);

	$post_id = wp_seed_events_event_people_shortcode_event_id( $raw_atts );

	if ( 0 === $post_id ) {
		return '';
	}

	$event = wp_seed_events_public_event_data( $post_id );

	if ( array() === $event ) {
		return '';
	}

	$options = array(
		'title'         => is_scalar( $atts['title'] ) ? (string) $atts['title'] : 'Contacts et intervenants',
		'heading_level' => wp_seed_events_public_heading_level_option( $atts['heading_level'] ),
		'roles'         => array_key_exists( 'roles', $raw_atts )
			? wp_seed_events_public_people_roles_option( $atts['roles'] )
			: wp_seed_events_public_people_roles_option( $atts['role'] ),
		'details'       => wp_seed_events_public_yes_no_option( $atts['details'], true ),
		'layout'        => wp_seed_events_public_event_people_layout_option( $atts['layout'] ),
	);

	foreach ( array( 'show_name', 'show_roles', 'show_email', 'show_phone', 'show_link', 'link_phone', 'link_email', 'link_url' ) as $option_key ) {
		if ( array_key_exists( $option_key, $raw_atts ) ) {
			$options[ $option_key ] = wp_seed_events_public_yes_no_option( $atts[ $option_key ], true );
		}
	}

	return wp_seed_events_render_public_event_people_section( $event, $options );
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

function wp_seed_events_public_date_list_marker_type_option( $value ) {
	$value = is_scalar( $value ) ? strtolower( trim( (string) $value ) ) : '';

	return in_array( $value, array( 'none', 'disc', 'circle', 'square' ), true ) ? $value : 'none';
}

function wp_seed_events_public_date_list_marker_position_option( $value ) {
	$value = is_scalar( $value ) ? strtolower( trim( (string) $value ) ) : '';

	return in_array( $value, array( 'inside', 'outside' ), true ) ? $value : 'outside';
}

function wp_seed_events_public_date_list_dimension_option( $value, $default ) {
	$value = is_scalar( $value ) ? strtolower( trim( (string) $value ) ) : '';

	return preg_match( '/^(?:0|(?:\d+(?:\.\d+)?|\.\d+)(?:px|em|rem|%|ch))$/', $value ) ? $value : $default;
}

function wp_seed_events_public_date_list_marker_color_option( $value ) {
	$value = is_scalar( $value ) ? trim( (string) $value ) : '';

	return preg_match( '/^(?:#[0-9a-f]{3,8}|(?:rgb|hsl)a?\([^;{}]+\)|var\(--[a-z0-9_-]+\))$/i', $value ) ? $value : '';
}

function wp_seed_events_render_public_event_dates_section( $event, $options = array() ) {
	if ( ! is_array( $event ) ) {
		$event = wp_seed_events_public_event_data( absint( $event ) );
	}

	if ( empty( $event['occurrences'] ) || ! is_array( $event['occurrences'] ) ) {
		return '';
	}

	if ( is_array( $options ) && array_key_exists( 'show_time', $options ) && ! array_key_exists( 'show_times', $options ) ) {
		$options['show_times'] = $options['show_time'];
	}

	$list_style_keys      = array( 'list_marker_type', 'list_marker_position', 'list_indent', 'occurrence_gap', 'marker_color' );
	$list_style_requested = is_array( $options ) && array() !== array_intersect( $list_style_keys, array_keys( $options ) );

	$options = wp_parse_args(
		$options,
		array(
			'title'               => 'Dates',
			'heading_level'       => 'h2',
			'mode'                => 'all',
			'scope'               => 'all',
			'show_cancelled'      => true,
			'show_times'          => true,
			'show_calendar_links' => true,
			'format'               => 'long',
			'list_marker_type'     => 'none',
			'list_marker_position' => 'outside',
			'list_indent'          => '0px',
			'occurrence_gap'       => '0px',
			'marker_color'         => '',
		)
	);

	$title                     = is_scalar( $options['title'] ) ? trim( (string) $options['title'] ) : '';
	$heading_level             = wp_seed_events_public_heading_level_option( $options['heading_level'] );
	$mode                      = wp_seed_events_public_date_mode_option( $options['mode'] );
	$scope                     = wp_seed_events_public_date_scope_option( $options['scope'] );
	$options['show_cancelled'] = wp_seed_events_public_boolean_option( $options['show_cancelled'], true );
	$options['format']               = wp_seed_events_public_date_format_option( $options['format'] );
	$options['list_marker_type']     = wp_seed_events_public_date_list_marker_type_option( $options['list_marker_type'] );
	$options['list_marker_position'] = wp_seed_events_public_date_list_marker_position_option( $options['list_marker_position'] );
	$options['list_indent']          = wp_seed_events_public_date_list_dimension_option( $options['list_indent'], '0px' );
	$options['occurrence_gap']       = wp_seed_events_public_date_list_dimension_option( $options['occurrence_gap'], '0px' );
	$options['marker_color']         = wp_seed_events_public_date_list_marker_color_option( $options['marker_color'] );

	if ( 'next' === $mode ) {
		$scope                     = 'upcoming';
		$options['show_cancelled'] = false;
	}

	$options['mode']  = $mode;
	$options['scope'] = $scope;

	$occurrences = array_values(
		array_filter(
			$event['occurrences'],
			function ( $occurrence ) use ( $options ) {
				if ( ! is_array( $occurrence ) || ! wp_seed_events_public_event_occurrence_has_valid_date( $occurrence ) ) {
					return false;
				}

				if ( 'next' === $options['mode'] && empty( $occurrence['is_active'] ) ) {
					return false;
				}

				if ( ! empty( $occurrence['is_cancelled'] ) && ! $options['show_cancelled'] ) {
					return false;
				}

				if ( 'upcoming' === $options['scope'] ) {
					return ! empty( $occurrence['is_date_future'] );
				}

				if ( 'past' === $options['scope'] ) {
					return ! empty( $occurrence['is_date_past'] );
				}

				return true;
			}
		)
	);

	if ( array() === $occurrences ) {
		return '';
	}

	if ( 'next' === $mode || 'first' === $mode ) {
		$occurrences = array( reset( $occurrences ) );
	} elseif ( 'last' === $mode ) {
		$occurrences = array( end( $occurrences ) );
	}

	$all_dates_link = (
		'all' === $mode
		&& count( $occurrences ) > 1
		&& $options['show_calendar_links']
	)
		? wp_seed_events_render_event_calendar_link( $event, $occurrences )
		: '';
	$list_classes = array( 'wp-seed-event-dates' );
	$list_style   = '';
	$item_style   = '';

	if ( $list_style_requested ) {
		$list_indent    = $options['list_indent'];
		$is_marker_none = 'none' === $options['list_marker_type'];

		if ( $is_marker_none && '2.5em' === $list_indent ) {
			$list_indent = '0px';
		}

		$list_classes[] = 'has-custom-list-style';
		$list_classes[] = 'is-marker-' . $options['list_marker_type'];
		$list_classes[] = 'is-marker-position-' . $options['list_marker_position'];
		$list_properties = array(
			'--wp-seed-event-dates-marker-type:' . $options['list_marker_type'],
			'--wp-seed-event-dates-marker-position:' . $options['list_marker_position'],
			'--wp-seed-event-dates-list-indent:' . $list_indent,
			'--wp-seed-event-dates-occurrence-gap:' . $options['occurrence_gap'],
		);

		if ( '' !== $options['marker_color'] ) {
			$list_properties[] = '--wp-seed-event-dates-marker-color:' . $options['marker_color'];
		}

		if ( $is_marker_none ) {
			$list_properties[] = 'list-style:none';
			$list_properties[] = 'list-style-type:none';
			$list_properties[] = 'padding-inline-start:' . $list_indent;
			$list_properties[] = 'margin-inline-start:0';
			$item_style        = 'list-style:none;list-style-type:none';
		}

		$list_style = implode( ';', $list_properties );
	}

	$section_classes = array(
		'wp-seed-event-section',
		'wp-seed-event-section--dates',
		'wp-seed-event-single__section',
		'wp-seed-event-single__dates',
	);

	ob_start();
	?>
	<section class="<?php echo esc_attr( implode( ' ', $section_classes ) ); ?>"<?php echo '' === $title ? ' aria-label="' . esc_attr( "Dates de l'\u{00E9}v\u{00E9}nement" ) . '"' : ''; ?>>
		<?php if ( '' !== $title ) : ?>
			<<?php echo esc_attr( $heading_level ); ?> class="wp-seed-event-dates__title"><?php echo esc_html( $title ); ?></<?php echo esc_attr( $heading_level ); ?>>
		<?php endif; ?>
		<?php if ( '' !== $all_dates_link ) : ?>
			<p class="wp-seed-event-calendar-all"><?php echo wp_kses_post( $all_dates_link ); ?></p>
		<?php endif; ?>
		<ul class="<?php echo esc_attr( implode( ' ', $list_classes ) ); ?>"<?php echo '' !== $list_style ? ' style="' . esc_attr( $list_style ) . '"' : ''; ?>>
			<?php foreach ( $occurrences as $occurrence ) : ?>
				<?php
				$date_line          = wp_seed_events_public_event_occurrence_date_line( $occurrence, $options['format'] );
				$time_line          = $options['show_times'] ? wp_seed_events_public_event_occurrence_time_line( $occurrence ) : '';
				$calendar_link      = $options['show_calendar_links'] ? wp_seed_events_render_occurrence_calendar_link( $event, $occurrence ) : '';
				$is_cancelled       = ! empty( $occurrence['is_cancelled'] );
				$occurrence_classes = array( 'wp-seed-event-date' );

				if ( ! empty( $occurrence['is_date_future'] ) ) {
					$occurrence_classes[] = 'is-future';
				}

				if ( ! empty( $occurrence['is_date_past'] ) ) {
					$occurrence_classes[] = 'is-past';
				}

				if ( $is_cancelled ) {
					$occurrence_classes[] = 'is-cancelled';
				}

				if ( ! empty( $occurrence['all_day'] ) ) {
					$occurrence_classes[] = 'is-all-day';
				}
				?>
				<li class="<?php echo esc_attr( implode( ' ', $occurrence_classes ) ); ?>"<?php echo '' !== $item_style ? ' style="' . esc_attr( $item_style ) . '"' : ''; ?>>
					<time class="wp-seed-event-date__date" datetime="<?php echo esc_attr( $occurrence['start_date'] ); ?>"><?php echo esc_html( $date_line ); ?></time>
					<?php if ( $is_cancelled ) : ?>
						<span class="wp-seed-event-date-status wp-seed-event-date__status wp-seed-event-single__cancelled">Annulée</span>
					<?php endif; ?>
					<?php if ( '' !== $time_line ) : ?>
						<span class="wp-seed-event-date__time"><?php echo esc_html( $time_line ); ?></span>
					<?php endif; ?>
					<?php if ( '' !== $calendar_link ) : ?>
						<?php echo wp_kses_post( $calendar_link ); ?>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
	<?php

	return trim( ob_get_clean() );
}

function wp_seed_events_public_event_visual_media_is_valid( $media ) {
	if ( ! is_array( $media ) ) {
		return false;
	}

	$attachment_id = isset( $media['id'] ) ? absint( $media['id'] ) : 0;
	$url           = isset( $media['url'] ) && is_scalar( $media['url'] ) ? trim( (string) $media['url'] ) : '';
	$mime_type     = isset( $media['mime_type'] ) && is_scalar( $media['mime_type'] ) ? strtolower( trim( (string) $media['mime_type'] ) ) : '';

	return $attachment_id > 0 && '' !== $url && 0 === strpos( $mime_type, 'image/' );
}

function wp_seed_events_public_event_document_media_is_valid( $media ) {
	if ( ! is_array( $media ) ) {
		return false;
	}

	$attachment_id = isset( $media['id'] ) ? absint( $media['id'] ) : 0;
	$url           = isset( $media['url'] ) && is_scalar( $media['url'] ) ? trim( (string) $media['url'] ) : '';
	$mime_type     = isset( $media['mime_type'] ) && is_scalar( $media['mime_type'] ) ? strtolower( trim( (string) $media['mime_type'] ) ) : '';

	return $attachment_id > 0 && '' !== $url && 'application/pdf' === $mime_type;
}

function wp_seed_events_public_event_visuals_projection( $event ) {
	$visuals = array();
	$seen    = array();

	if ( ! empty( $event['communication_visuals'] ) && is_array( $event['communication_visuals'] ) ) {
		foreach ( $event['communication_visuals'] as $media ) {
			if ( ! wp_seed_events_public_event_visual_media_is_valid( $media ) ) {
				continue;
			}

			$attachment_id = absint( $media['id'] );

			if ( isset( $seen[ $attachment_id ] ) ) {
				continue;
			}

			$seen[ $attachment_id ] = true;
			$visuals[]               = $media;
		}
	}

	if ( array() !== $visuals ) {
		return array(
			'flyer'  => $visuals[0],
			'visuals' => array_slice( $visuals, 1 ),
		);
	}

	$flyer         = null;
	$other_visuals = array();

	if ( ! empty( $event['communication_visual'] ) && wp_seed_events_public_event_visual_media_is_valid( $event['communication_visual'] ) ) {
		$flyer                         = $event['communication_visual'];
		$seen[ absint( $flyer['id'] ) ] = true;
	}

	if ( ! empty( $event['other_visuals'] ) && is_array( $event['other_visuals'] ) ) {
		foreach ( $event['other_visuals'] as $media ) {
			if ( ! wp_seed_events_public_event_visual_media_is_valid( $media ) ) {
				continue;
			}

			$attachment_id = absint( $media['id'] );

			if ( isset( $seen[ $attachment_id ] ) ) {
				continue;
			}

			$seen[ $attachment_id ] = true;
			$other_visuals[]         = $media;
		}
	}

	return array(
		'flyer'  => $flyer,
		'visuals' => $other_visuals,
	);
}

function wp_seed_events_render_public_event_visuals_section( $event, $options = array() ) {
	if ( ! is_array( $event ) ) {
		return '';
	}

	$options = wp_parse_args(
		is_array( $options ) ? $options : array(),
		array(
			'title'          => 'Visuels de communication',
			'heading_level'  => 'h2',
			'show_flyer'     => true,
			'show_visuals'   => true,
			'show_document'  => true,
			'show_captions'  => false,
			'image_size'     => 'large',
			'link_original'  => true,
			'layout'         => 'grid',
		)
	);

	$title         = is_scalar( $options['title'] ) ? trim( wp_strip_all_tags( (string) $options['title'], true ) ) : '';
	$heading_level = wp_seed_events_public_heading_level_option( $options['heading_level'] );
	$image_size    = wp_seed_events_public_visuals_image_size_option( $options['image_size'] );
	$layout        = wp_seed_events_public_visuals_layout_option( $options['layout'] );
	$show_flyer    = wp_seed_events_public_boolean_option( $options['show_flyer'], true );
	$show_visuals  = wp_seed_events_public_boolean_option( $options['show_visuals'], true );
	$show_document = wp_seed_events_public_boolean_option( $options['show_document'], true );
	$show_captions = wp_seed_events_public_boolean_option( $options['show_captions'], false );
	$link_original = wp_seed_events_public_boolean_option( $options['link_original'], true );
	$projection    = wp_seed_events_public_event_visuals_projection( $event );
	$items         = array();
	$quote         = chr( 34 );

	if ( $show_flyer && is_array( $projection['flyer'] ) ) {
		$items[] = array(
			'type'  => 'flyer',
			'media' => $projection['flyer'],
		);
	}

	if ( $show_visuals ) {
		foreach ( $projection['visuals'] as $media ) {
			$items[] = array(
				'type'  => 'visual',
				'media' => $media,
			);
		}
	}

	$rendered_items = array();

	foreach ( $items as $item ) {
		$media         = $item['media'];
		$attachment_id = absint( $media['id'] );
		$alt            = isset( $media['alt'] ) && is_scalar( $media['alt'] ) ? (string) $media['alt'] : '';
		$image          = wp_get_attachment_image(
			$attachment_id,
			$image_size,
			false,
			array(
				'alt'     => $alt,
				'class'   => 'wp-seed-event-visuals__image',
				'loading' => 'lazy',
			)
		);

		if ( ! is_string( $image ) || '' === trim( $image ) ) {
			continue;
		}

		$image_html = $image;
		$media_url  = isset( $media['url'] ) && is_scalar( $media['url'] ) ? esc_url( (string) $media['url'] ) : '';

		if ( $link_original && '' !== $media_url ) {
			$link_label = 'flyer' === $item['type']
				? 'Voir le flyer recto en taille originale'
				: 'Voir le visuel de communication en taille originale';
			$image_html = '<a class=' . $quote . esc_attr( 'wp-seed-event-visuals__image-link' ) . $quote . ' href=' . $quote . $media_url . $quote . ' aria-label=' . $quote . esc_attr( $link_label ) . $quote . '>' . $image . '</a>';
		}

		$caption = isset( $media['caption'] ) && is_scalar( $media['caption'] )
			? trim( wp_strip_all_tags( (string) $media['caption'], true ) )
			: '';
		$figure  = '<figure class=' . $quote . esc_attr( 'wp-seed-event-visuals__figure' ) . $quote . '>' . $image_html;

		if ( $show_captions && '' !== $caption ) {
			$figure .= '<figcaption class=' . $quote . esc_attr( 'wp-seed-event-visuals__caption' ) . $quote . '>' . esc_html( $caption ) . '</figcaption>';
		}

		$figure .= '</figure>';

		$rendered_items[] = '<li class=' . $quote . esc_attr( 'wp-seed-event-visuals__item wp-seed-event-visuals__item--' . $item['type'] ) . $quote . '>' . $figure . '</li>';
	}

	$document = $event['event_document'] ?? null;

	if ( $show_document && wp_seed_events_public_event_document_media_is_valid( $document ) ) {
		$document_url = esc_url( (string) $document['url'] );

		if ( '' !== $document_url ) {
			$filename = isset( $document['filename'] ) && is_scalar( $document['filename'] )
				? sanitize_file_name( (string) $document['filename'] )
				: '';
			$link_text = 'Télécharger le document PDF';

			if ( '' !== $filename ) {
				$link_text .= ' (' . $filename . ')';
			}

			$document_link   = '<a class=' . $quote . esc_attr( 'wp-seed-event-visuals__document-link' ) . $quote . ' href=' . $quote . $document_url . $quote . '>' . esc_html( $link_text ) . '</a>';
			$rendered_items[] = '<li class=' . $quote . esc_attr( 'wp-seed-event-visuals__item wp-seed-event-visuals__item--document wp-seed-event-visuals__document' ) . $quote . '>' . $document_link . '</li>';
		}
	}

	if ( array() === $rendered_items ) {
		return '';
	}

	$section_classes = array(
		'wp-seed-event-section',
		'wp-seed-event-section--visuals',
		'wp-seed-event-visuals',
		'is-layout-' . $layout,
	);
	$html            = '<section class=' . $quote . esc_attr( implode( ' ', $section_classes ) ) . $quote;

	if ( '' === $title ) {
		$html .= ' aria-label=' . $quote . esc_attr( 'Visuels de communication' ) . $quote;
	}

	$html .= '>';

	if ( '' !== $title ) {
		$html .= '<' . $heading_level . ' class=' . $quote . esc_attr( 'wp-seed-event-visuals__title' ) . $quote . '>' . esc_html( $title ) . '</' . $heading_level . '>';
	}

	$html .= '<ul class=' . $quote . esc_attr( 'wp-seed-event-visuals__list' ) . $quote . '>' . implode( '', $rendered_items ) . '</ul>';
	$html .= '</section>';

	return $html;
}

function wp_seed_events_public_event_occurrence_has_valid_date( $occurrence ) {
	$start_date = isset( $occurrence['start_date'] ) ? (string) $occurrence['start_date'] : '';
	$end_date   = isset( $occurrence['end_date'] ) ? (string) $occurrence['end_date'] : '';

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date ) ) {
		return false;
	}

	$start = DateTimeImmutable::createFromFormat( '!Y-m-d', $start_date );

	if ( false === $start || $start->format( 'Y-m-d' ) !== $start_date ) {
		return false;
	}

	if ( '' === $end_date ) {
		return true;
	}

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date ) ) {
		return false;
	}

	$end = DateTimeImmutable::createFromFormat( '!Y-m-d', $end_date );

	return false !== $end && $end->format( 'Y-m-d' ) === $end_date;
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

function wp_seed_events_public_event_people_layout_option( $value ) {
	if ( ! is_scalar( $value ) ) {
		return 'list';
	}

	$value = strtolower( trim( (string) $value ) );

	return in_array( $value, array( 'list', 'grid' ), true ) ? $value : 'list';
}

function wp_seed_events_public_event_person_text( $value ) {
	if ( ! is_scalar( $value ) ) {
		return '';
	}

	$value = trim( wp_strip_all_tags( (string) $value, true ) );

	return trim( (string) preg_replace( '/\s+/u', ' ', $value ) );
}

function wp_seed_events_public_event_person_coordinate( $person, $canonical_key, $alias_key ) {
	if ( ! is_array( $person ) ) {
		return '';
	}

	if ( array_key_exists( $canonical_key, $person ) ) {
		$value = $person[ $canonical_key ];
	} elseif ( array_key_exists( $alias_key, $person ) ) {
		$value = $person[ $alias_key ];
	} else {
		return '';
	}

	if ( ! is_scalar( $value ) ) {
		return '';
	}

	$value = trim( (string) $value );

	return $value === trim( wp_strip_all_tags( $value, true ) ) ? $value : '';
}

function wp_seed_events_render_public_event_people_section( $event, $options = array() ) {
	if ( ! is_array( $event ) || empty( $event['people'] ) || ! is_array( $event['people'] ) ) {
		return '';
	}

	$options        = is_array( $options ) ? $options : array();
	$legacy_details = array_key_exists( 'details', $options )
		? wp_seed_events_public_boolean_option( $options['details'], true )
		: null;

	if ( null !== $legacy_details ) {
		foreach ( array( 'show_roles', 'show_email', 'show_phone', 'show_link' ) as $option_key ) {
			if ( ! array_key_exists( $option_key, $options ) ) {
				$options[ $option_key ] = $legacy_details;
			}
		}

		if ( ! array_key_exists( 'title', $options ) ) {
			$options['title'] = 'Contacts et intervenants';
		}
	}

	$options = wp_parse_args(
		$options,
		array(
			'title'         => 'Personnes',
			'heading_level' => 'h2',
			'roles'         => array(),
			'role'          => '',
			'show_name'     => true,
			'show_roles'    => true,
			'show_email'    => true,
			'show_phone'    => true,
			'show_link'     => true,
			'link_phone'    => true,
			'link_email'    => true,
			'link_url'      => true,
			'layout'        => 'list',
		)
	);

	$known_roles = array( 'organizer', 'speaker', 'contact' );
	$title       = wp_seed_events_public_event_person_text( $options['title'] );
	$role_source = array_key_exists( 'roles', $options ) && array() !== $options['roles'] ? $options['roles'] : $options['role'];
	$role_filters = wp_seed_events_public_people_roles_option( $role_source );

	$heading_level = wp_seed_events_public_heading_level_option( $options['heading_level'] );
	$show_name     = wp_seed_events_public_boolean_option( $options['show_name'], true );
	$show_roles    = wp_seed_events_public_boolean_option( $options['show_roles'], true );
	$show_email    = wp_seed_events_public_boolean_option( $options['show_email'], true );
	$show_phone    = wp_seed_events_public_boolean_option( $options['show_phone'], true );
	$show_link     = wp_seed_events_public_boolean_option( $options['show_link'], true );
	$link_phone    = wp_seed_events_public_boolean_option( $options['link_phone'], true );
	$link_email    = wp_seed_events_public_boolean_option( $options['link_email'], true );
	$link_url      = wp_seed_events_public_boolean_option( $options['link_url'], true );
	$layout        = wp_seed_events_public_event_people_layout_option( $options['layout'] );
	$rendered      = array();
	$quote         = chr( 34 );

	foreach ( $event['people'] as $person ) {
		if ( ! is_array( $person ) ) {
			continue;
		}

		$name = wp_seed_events_public_event_person_text( $person['name'] ?? '' );

		if ( '' === $name ) {
			continue;
		}

		$role_keys = array();

		foreach ( isset( $person['role_keys'] ) && is_array( $person['role_keys'] ) ? $person['role_keys'] : array() as $role_key ) {
			$role_key = is_scalar( $role_key ) ? wp_seed_events_public_people_role_option( $role_key ) : '';
			$role_key = 'all' === $role_key ? '' : $role_key;

			if ( in_array( $role_key, $known_roles, true ) && ! in_array( $role_key, $role_keys, true ) ) {
				$role_keys[] = $role_key;
			}
		}

		if ( array() !== $role_filters && array() === array_intersect( $role_filters, $role_keys ) ) {
			continue;
		}

		$roles = array();

		if ( $show_roles ) {
			foreach ( isset( $person['roles'] ) && is_array( $person['roles'] ) ? $person['roles'] : array() as $role_label ) {
				$role_label = wp_seed_events_public_event_person_text( $role_label );

				if ( '' !== $role_label && ! in_array( $role_label, $roles, true ) ) {
					$roles[] = $role_label;
				}
			}
		}

		$contacts = array();

		if ( $show_email ) {
			$email = wp_seed_events_normalize_person_email(
				wp_seed_events_public_event_person_coordinate( $person, 'public_email', 'email' )
			);

			if ( '' !== $email ) {
				$email_label = 'Envoyer un email à ' . $name;
				if ( $link_email ) {
					$email_link = '<a class=' . $quote . esc_attr( 'wp-seed-event-people__email-link' ) . $quote
						. ' href=' . $quote . esc_attr( 'mailto:' . $email ) . $quote
						. ' aria-label=' . $quote . esc_attr( $email_label ) . $quote . '>' . esc_html( $email ) . '</a>';
				} else {
					$email_link = '<span class=' . $quote . esc_attr( 'wp-seed-event-people__email-text' ) . $quote . '>' . esc_html( $email ) . '</span>';
				}
				$contacts[]  = '<li class=' . $quote . esc_attr( 'wp-seed-event-people__contact wp-seed-event-people__email' ) . $quote . '>' . $email_link . '</li>';
			}
		}

		if ( $show_phone ) {
			$phone = wp_seed_events_normalize_person_phone(
				wp_seed_events_public_event_person_coordinate( $person, 'public_phone', 'phone' )
			);
			$phone_href = wp_seed_events_public_phone_href( $phone );

			if ( '' !== $phone && '' !== $phone_href ) {
				$phone_label = 'Appeler ' . $name;
				if ( $link_phone ) {
					$phone_link = '<a class=' . $quote . esc_attr( 'wp-seed-event-people__phone-link' ) . $quote
						. ' href=' . $quote . esc_attr( $phone_href ) . $quote
						. ' aria-label=' . $quote . esc_attr( $phone_label ) . $quote . '>' . esc_html( $phone ) . '</a>';
				} else {
					$phone_link = '<span class=' . $quote . esc_attr( 'wp-seed-event-people__phone-text' ) . $quote . '>' . esc_html( $phone ) . '</span>';
				}
				$contacts[]  = '<li class=' . $quote . esc_attr( 'wp-seed-event-people__contact wp-seed-event-people__phone' ) . $quote . '>' . $phone_link . '</li>';
			}
		}

		if ( $show_link ) {
			$url = wp_seed_events_normalize_person_link(
				wp_seed_events_public_event_person_coordinate( $person, 'public_url', 'link' )
			);

			if ( '' !== $url ) {
				$url_label  = 'Consulter le lien associé à ' . $name;
				if ( $link_url ) {
					$url_link = '<a class=' . $quote . esc_attr( 'wp-seed-event-people__link-anchor' ) . $quote
						. ' href=' . $quote . esc_url( $url ) . $quote . '>' . esc_html( $url_label ) . '</a>';
				} else {
					$url_link = '<span class=' . $quote . esc_attr( 'wp-seed-event-people__link-text' ) . $quote . '>' . esc_html( $url ) . '</span>';
				}
				$contacts[] = '<li class=' . $quote . esc_attr( 'wp-seed-event-people__contact wp-seed-event-people__link' ) . $quote . '>' . $url_link . '</li>';
			}
		}

		if ( ! $show_name && array() === $roles && array() === $contacts ) {
			continue;
		}

		$name_classes = array( 'wp-seed-event-people__name' );
		if ( ! $show_name ) {
			$name_classes[] = 'screen-reader-text';
		}

		$item = '<li class=' . $quote . esc_attr( 'wp-seed-event-person wp-seed-event-people__item' ) . $quote . '>';
		$item .= '<span class=' . $quote . esc_attr( implode( ' ', $name_classes ) ) . $quote . '>' . esc_html( $name ) . '</span>';

		if ( array() !== $roles ) {
			$rendered_roles = array();

			foreach ( $roles as $role_label ) {
				$rendered_roles[] = '<li class=' . $quote . esc_attr( 'wp-seed-event-people__role' ) . $quote . '>' . esc_html( $role_label ) . '</li>';
			}

			$item .= '<ul class=' . $quote . esc_attr( 'wp-seed-event-people__roles' ) . $quote
				. ' aria-label=' . $quote . esc_attr( 'Rôles de ' . $name ) . $quote . '>' . implode( '', $rendered_roles ) . '</ul>';
		}

		if ( array() !== $contacts ) {
			$item .= '<ul class=' . $quote . esc_attr( 'wp-seed-event-people__contacts' ) . $quote
				. ' aria-label=' . $quote . esc_attr( 'Coordonnées publiques de ' . $name ) . $quote . '>' . implode( '', $contacts ) . '</ul>';
		}

		$item      .= '</li>';
		$rendered[] = $item;
	}

	if ( array() === $rendered ) {
		return '';
	}

	$section_classes = array(
		'wp-seed-event-section',
		'wp-seed-event-section--people',
		'wp-seed-event-people-section',
		'is-layout-' . $layout,
	);
	$html            = '<section class=' . $quote . esc_attr( implode( ' ', $section_classes ) ) . $quote;

	if ( '' === $title ) {
		$html .= ' aria-label=' . $quote . esc_attr( "Personnes de l'événement" ) . $quote;
	}

	$html .= '>';

	if ( '' !== $title ) {
		$html .= '<' . $heading_level . ' class=' . $quote . esc_attr( 'wp-seed-event-people__title' ) . $quote . '>' . esc_html( $title ) . '</' . $heading_level . '>';
	}

	$html .= '<ul class=' . $quote . esc_attr( 'wp-seed-event-people wp-seed-event-people__list' ) . $quote . '>' . implode( '', $rendered ) . '</ul>';
	$html .= '</section>';

	return $html;
}
