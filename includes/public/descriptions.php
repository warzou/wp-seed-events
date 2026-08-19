<?php
/**
 * Event description storage, resolution, and editing helpers.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

const WP_SEED_EVENTS_SHORT_DESCRIPTION_META_KEY = '_wp_seed_event_short_description';

add_action( 'save_post_wp_seed_event', 'wp_seed_events_save_short_description' );
add_action( 'rest_api_init', 'wp_seed_events_register_description_rest_fields' );

function wp_seed_events_normalize_multiline_description( $value ) {
	$value = str_replace( array( "\r\n", "\r" ), "\n", (string) $value );
	$lines = array_map(
		static function ( $line ) {
			$line = preg_replace( '/[ \t\x{00a0}]+/u', ' ', (string) $line );
			return trim( (string) $line );
		},
		explode( "\n", $value )
	);
	$value = implode( "\n", $lines );
	$value = preg_replace( "/\n{3,}/", "\n\n", $value );

	return trim( (string) $value );
}
function wp_seed_events_description_content_to_text( $content ) {
	$content = str_replace( array( "\r\n", "\r" ), "\n", (string) $content );
	$content = preg_replace( '#<(script|style)\b[^>]*>.*?</\1\s*>#is', '', $content );
	$content = strip_shortcodes( (string) $content );
	$content = preg_replace( '#<br\s*/?>#i', "\n", (string) $content );
	$content = preg_replace( '#</(?:p|div|section|article|header|footer|blockquote|pre|h[1-6])\s*>#i', "\n\n", (string) $content );
	$content = preg_replace( '#</(?:li|dt|dd|tr)\s*>#i', "\n", (string) $content );
	$content = wp_strip_all_tags( (string) $content );
	$content = html_entity_decode( $content, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$content = str_replace( "\xC2\xA0", ' ', $content );

	return wp_seed_events_normalize_multiline_description( $content );
}

function wp_seed_events_trim_multiline_words( $text, $word_limit = 40 ) {
	$text       = (string) $text;
	$word_limit = max( 1, absint( $word_limit ) );
	$matched    = preg_match_all(
		"/[\p{L}\p{N}]+(?:['\x{2019}\-][\p{L}\p{N}]+)*/u",
		$text,
		$words,
		PREG_OFFSET_CAPTURE
	);

	if ( false === $matched || $matched <= $word_limit ) {
		return $text;
	}

	$next_word_offset = (int) $words[0][ $word_limit ][1];
	$trimmed          = rtrim( substr( $text, 0, $next_word_offset ) );

	return '' === $trimmed ? '' : $trimmed . '…';
}

function wp_seed_events_resolve_short_description( string $description, string $short_description = '', int $word_limit = 40 ): string {
	if ( '' !== trim( $short_description ) ) {
		return $short_description;
	}

	if ( preg_match( '/<!--more(.*?)?-->/is', $description ) ) {
		$extended = get_extended( $description );
		return wp_seed_events_description_content_to_text( $extended['main'] ?? '' );
	}

	return wp_seed_events_trim_multiline_words(
		wp_seed_events_description_content_to_text( $description ),
		$word_limit
	);
}

function wp_seed_events_public_event_excerpt( $event ) {
	$post = null;

	if ( $event instanceof WP_Post ) {
		$post = $event;
	} elseif ( is_int( $event ) || ( is_string( $event ) && ctype_digit( $event ) ) ) {
		$post = get_post( absint( $event ) );
	}

	if ( $post && 'wp_seed_event' === $post->post_type ) {
		return wp_seed_events_resolve_short_description(
			(string) $post->post_content,
			(string) get_post_meta( $post->ID, WP_SEED_EVENTS_SHORT_DESCRIPTION_META_KEY, true )
		);
	}

	return wp_seed_events_resolve_short_description( (string) $event );
}

function wp_seed_events_sanitize_short_description( $value ) {
	return wp_seed_events_normalize_multiline_description(
		sanitize_textarea_field( (string) $value )
	);
}

function wp_seed_events_render_short_description_field( $post ) {
	$value = (string) get_post_meta( $post->ID, WP_SEED_EVENTS_SHORT_DESCRIPTION_META_KEY, true );

	wp_nonce_field( 'wp_seed_events_save_short_description', 'wp_seed_events_short_description_nonce' );
	?>
	<p>
		<label for="wp-seed-event-short-description"><strong>Description courte (facultative)</strong></label>
	</p>
	<textarea id="wp-seed-event-short-description" name="wp_seed_event_short_description" rows="5" class="widefat"><?php echo esc_textarea( $value ); ?></textarea>
	<p class="description">Utilisée dans les listes et cartes. Si ce champ est vide, un extrait de la description sera généré automatiquement. Une coupure « Lire la suite » dans la description est utilisée en priorité. L’extrait automatique est limité à 40 mots.</p>
	<?php
}

function wp_seed_events_save_short_description( $post_id ) {
	if ( ! isset( $_POST['wp_seed_events_short_description_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['wp_seed_events_short_description_nonce'] ) );

	if (
		! wp_verify_nonce( $nonce, 'wp_seed_events_save_short_description' )
		|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		|| wp_is_post_revision( $post_id )
		|| 'wp_seed_event' !== get_post_type( $post_id )
		|| ! current_user_can( 'edit_post', $post_id )
		|| ! array_key_exists( 'wp_seed_event_short_description', $_POST )
	) {
		return;
	}

	$value = wp_seed_events_sanitize_short_description( wp_unslash( $_POST['wp_seed_event_short_description'] ) );

	if ( '' === trim( $value ) ) {
		delete_post_meta( $post_id, WP_SEED_EVENTS_SHORT_DESCRIPTION_META_KEY );
	} else {
		update_post_meta( $post_id, WP_SEED_EVENTS_SHORT_DESCRIPTION_META_KEY, $value );
	}

	if ( function_exists( 'wp_seed_events_dynamic_data_invalidate_event_cache' ) ) {
		wp_seed_events_dynamic_data_invalidate_event_cache( $post_id );
	}
}

function wp_seed_events_description_rest_event_id( $object ) {
	if ( is_array( $object ) ) {
		return absint( $object['id'] ?? $object['ID'] ?? 0 );
	}

	return is_object( $object ) ? absint( $object->ID ?? $object->id ?? 0 ) : 0;
}

function wp_seed_events_description_rest_values( $event_id ) {
	$post = get_post( absint( $event_id ) );

	if ( ! $post || 'wp_seed_event' !== $post->post_type ) {
		return array();
	}

	$manual    = (string) get_post_meta( $post->ID, WP_SEED_EVENTS_SHORT_DESCRIPTION_META_KEY, true );
	$effective = wp_seed_events_resolve_short_description( (string) $post->post_content, $manual );

	return array(
		'short_description'           => $manual,
		'short_description_effective' => $effective,
		'excerpt'                     => $effective,
	);
}

function wp_seed_events_description_rest_get( $object, $field_name, $request ) {
	$event_id = wp_seed_events_description_rest_event_id( $object );
	$context  = is_object( $request ) && method_exists( $request, 'get_param' )
		? sanitize_key( (string) $request->get_param( 'context' ) )
		: '';

	if ( 0 === $event_id || 'edit' !== $context || ! current_user_can( 'edit_post', $event_id ) ) {
		return null;
	}

	$values = wp_seed_events_description_rest_values( $event_id );

	return isset( $values[ $field_name ] ) ? (string) $values[ $field_name ] : null;
}

function wp_seed_events_description_rest_update( $value, $object ) {
	$event_id = wp_seed_events_description_rest_event_id( $object );

	if ( 0 === $event_id || ! current_user_can( 'edit_post', $event_id ) ) {
		return new WP_Error( 'rest_cannot_update', __( 'Vous ne pouvez pas modifier cette description courte.', 'wp-seed-events' ), array( 'status' => 403 ) );
	}

	$value = wp_seed_events_sanitize_short_description( $value );

	if ( '' === trim( $value ) ) {
		delete_post_meta( $event_id, WP_SEED_EVENTS_SHORT_DESCRIPTION_META_KEY );
	} else {
		update_post_meta( $event_id, WP_SEED_EVENTS_SHORT_DESCRIPTION_META_KEY, $value );
	}

	if ( function_exists( 'wp_seed_events_dynamic_data_invalidate_event_cache' ) ) {
		wp_seed_events_dynamic_data_invalidate_event_cache( $event_id );
	}

	return true;
}

function wp_seed_events_register_description_rest_fields() {
	if ( ! function_exists( 'register_rest_field' ) ) {
		return;
	}

	$readonly_schema = array(
		'type'     => 'string',
		'context'  => array( 'edit' ),
		'readonly' => true,
	);

	register_rest_field(
		'wp_seed_event',
		'short_description',
		array(
			'get_callback'    => 'wp_seed_events_description_rest_get',
			'update_callback' => 'wp_seed_events_description_rest_update',
			'schema'          => array(
				'type'        => 'string',
				'context'     => array( 'edit' ),
				'description' => 'Optional manual multiline short description.',
			),
		)
	);
	register_rest_field(
		'wp_seed_event',
		'short_description_effective',
		array(
			'get_callback' => 'wp_seed_events_description_rest_get',
			'schema'       => $readonly_schema,
		)
	);
	register_rest_field(
		'wp_seed_event',
		'excerpt',
		array(
			'get_callback' => 'wp_seed_events_description_rest_get',
			'schema'       => $readonly_schema,
		)
	);
}
