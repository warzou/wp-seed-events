<?php

define( 'ABSPATH', __DIR__ );

function __( $text ) {
	return $text;
}

function esc_attr__( $text ) {
	return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
}

function esc_attr( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_url( $url ) {
	return htmlspecialchars( (string) $url, ENT_QUOTES, 'UTF-8' );
}

function esc_url_raw( $url ) {
	return str_starts_with( (string) $url, 'https://' ) ? (string) $url : '';
}

function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
}

function sanitize_html_class( $value ) {
	return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $value );
}

function wp_strip_all_tags( $text ) {
	return strip_tags( (string) $text );
}

function wp_parse_args( $args, $defaults ) {
	return array_merge( $defaults, $args );
}

function wp_seed_events_public_boolean_option( $value, $default ) {
	if ( is_bool( $value ) ) {
		return $value;
	}
	if ( in_array( $value, array( 'off', '0', 0 ), true ) ) {
		return false;
	}
	if ( in_array( $value, array( 'on', '1', 1 ), true ) ) {
		return true;
	}
	return $default;
}

require_once dirname( __DIR__ ) . '/includes/public/sharing.php';

function share_case( $name, $callback ) {
	static $count = 0;
	$callback();
	++$count;
	echo "ok {$count} - {$name}\n";
}

function share_true( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

$event = array(
	'title' => 'Journée découverte',
	'url'   => 'https://example.test/journee-decouverte/',
);

share_case( 'invalid event is empty', function () {
	share_true( array() === wp_seed_events_event_share_data( null ), 'Invalid event leaked.' );
} );
share_case( 'missing canonical URL is empty', function () {
	share_true( '' === wp_seed_events_render_event_share_menu( array( 'title' => 'Test' ) ), 'Missing URL rendered.' );
} );
share_case( 'default renderer has three immediate actions and no block label', function () use ( $event ) {
	$html = wp_seed_events_render_event_share_menu( $event );
	share_true( 2 === substr_count( $html, '<button ' ), 'Button count differs.' );
	share_true( 1 === substr_count( $html, '<a ' ), 'Link count differs.' );
	share_true( ! str_contains( $html, 'wp-seed-event-share__group-label' ), 'A group label was imposed.' );
	share_true( strpos( $html, 'data-wp-seed-event-share-native' ) < strpos( $html, 'data-wp-seed-event-share-copy' ), 'Historical Share/Copy order differs.' );
	share_true( strpos( $html, 'data-wp-seed-event-share-copy' ) < strpos( $html, 'href="mailto:' ), 'Historical Copy/Email order differs.' );
} );
share_case( 'all six unique action orders render exactly once', function () use ( $event ) {
	$orders = array(
		'share_copy_email' => array( 'data-wp-seed-event-share-native', 'data-wp-seed-event-share-copy', 'href="mailto:' ),
		'share_email_copy' => array( 'data-wp-seed-event-share-native', 'href="mailto:', 'data-wp-seed-event-share-copy' ),
		'copy_share_email' => array( 'data-wp-seed-event-share-copy', 'data-wp-seed-event-share-native', 'href="mailto:' ),
		'copy_email_share' => array( 'data-wp-seed-event-share-copy', 'href="mailto:', 'data-wp-seed-event-share-native' ),
		'email_share_copy' => array( 'href="mailto:', 'data-wp-seed-event-share-native', 'data-wp-seed-event-share-copy' ),
		'email_copy_share' => array( 'href="mailto:', 'data-wp-seed-event-share-copy', 'data-wp-seed-event-share-native' ),
	);

	foreach ( $orders as $order => $tokens ) {
		$html = wp_seed_events_render_event_share_menu( $event, array( 'action_order' => $order ) );
		share_true( 1 === substr_count( $html, 'data-wp-seed-event-share-native' ), 'Share action duplicated.' );
		share_true( 1 === substr_count( $html, 'data-wp-seed-event-share-copy' ), 'Copy action duplicated.' );
		share_true( 1 === substr_count( $html, 'href="mailto:' ), 'Email action duplicated.' );
		share_true( strpos( $html, $tokens[0] ) < strpos( $html, $tokens[1] ) && strpos( $html, $tokens[1] ) < strpos( $html, $tokens[2] ), 'Requested action order differs.' );
	}

	$fallback = wp_seed_events_render_event_share_menu( $event, array( 'action_order' => 'share_share_email' ) );
	share_true( strpos( $fallback, 'data-wp-seed-event-share-native' ) < strpos( $fallback, 'data-wp-seed-event-share-copy' ), 'Invalid order did not fall back safely.' );
} );
share_case( 'legacy group label attributes are inert', function () use ( $event ) {
	$html = wp_seed_events_render_event_share_menu( $event, array( 'show_group_label' => true, 'group_label' => 'À partager' ) );
	share_true( ! str_contains( $html, 'wp-seed-event-share__group-label' ), 'Legacy group label still renders.' );
	share_true( ! str_contains( $html, 'À partager' ), 'Legacy group label text leaked.' );
} );
share_case( 'legacy dropdown markup is absent', function () use ( $event ) {
	$html = wp_seed_events_render_event_share_menu( $event );
	share_true( ! str_contains( $html, '<details' ) && ! str_contains( $html, '<summary' ), 'Dropdown survived.' );
} );
share_case( 'canonical UTF-8 title text and URL feed native share', function () use ( $event ) {
	$html = wp_seed_events_render_event_share_menu( $event );
	share_true( str_contains( $html, 'data-share-title="Journée découverte"' ), 'Share title differs.' );
	share_true( str_contains( $html, 'data-share-text="Découvrez cet événement : Journée découverte"' ), 'Share text differs.' );
	share_true( 2 === substr_count( $html, 'data-share-url="https://example.test/journee-decouverte/"' ), 'Canonical URL differs.' );
	share_true( ! preg_match( '/(?:Ã.|â.|Â.)/u', $html ), 'Mojibake leaked into markup.' );
} );
share_case( 'email uses UTF-8 encoded title body and canonical URL', function () use ( $event ) {
	$data = wp_seed_events_event_share_data( $event );
	share_true( str_starts_with( $data['email_url'], 'mailto:?subject=Journ%C3%A9e%20d%C3%A9couverte&body=' ), 'Email subject differs.' );
	share_true( str_contains( rawurldecode( $data['email_url'] ), 'Je vous partage cet événement' ), 'Email body encoding differs.' );
	share_true( str_contains( $data['email_url'], rawurlencode( $event['url'] ) ), 'Email URL missing.' );
} );
share_case( 'actions form one labelled accessible group', function () use ( $event ) {
	$html = wp_seed_events_render_event_share_menu( $event );
	share_true( str_contains( $html, 'role="group"' ), 'Action group missing.' );
	share_true( 4 === substr_count( $html, 'aria-label=' ), 'Action aria labels differ.' );
	share_true( str_contains( $html, 'role="status" aria-live="polite" aria-atomic="true"' ), 'Live feedback missing.' );
} );
share_case( 'text and icon mode contains both concepts', function () use ( $event ) {
	$html = wp_seed_events_render_event_share_menu(
		$event,
		array(
			'display_mode' => 'text_icon',
			'share_icon'   => '&#xe001;',
			'copy_icon'    => '&#xe002;',
			'email_icon'   => '&#xe003;',
			'use_divi_button' => true,
		)
	);
	share_true( 3 === substr_count( $html, ' data-icon=' ), 'Native Divi icon attributes missing.' );
	share_true( 3 === substr_count( $html, 'et_pb_button' ), 'Native Divi button classes missing.' );
	share_true( 3 === substr_count( $html, 'et_pb_custom_button_icon' ), 'Native Divi icon classes missing.' );
	share_true( ! str_contains( $html, 'wp-seed-event-share__icon' ), 'Manual icon nodes survived.' );
	share_true( 3 === substr_count( $html, 'data-wp-seed-event-share-action-label' ), 'Labels missing.' );
} );
share_case( 'new instances never receive hidden fallback icons', function () use ( $event ) {
	$html = wp_seed_events_render_event_share_menu( $event, array( 'display_mode' => 'text_icon' ) );
	share_true( ! str_contains( $html, ' data-icon=' ), 'An implicit icon was rendered.' );
	share_true( ! str_contains( $html, 'is-fallback-icon' ), 'A fallback icon class survived.' );
	share_true( 3 === substr_count( $html, 'data-wp-seed-event-share-action-label' ), 'Labels disappeared without icons.' );
} );
share_case( 'text-only mode contains labels without icons', function () use ( $event ) {
	$html = wp_seed_events_render_event_share_menu( $event, array( 'display_mode' => 'text' ) );
	share_true( ! str_contains( $html, ' data-icon=' ), 'Text-only mode retained icons.' );
	share_true( 3 === substr_count( $html, 'data-wp-seed-event-share-action-label' ), 'Text-only labels missing.' );
} );
share_case( 'icon-only mode stays visible and accessible', function () use ( $event ) {
	$html = wp_seed_events_render_event_share_menu(
		$event,
		array(
			'display_mode' => 'icon',
			'share_icon'   => '&#xe001;',
			'copy_icon'    => '&#xe002;',
			'email_icon'   => '&#xe003;',
		)
	);
	share_true( 3 === substr_count( $html, ' data-icon=' ), 'Icon-only actions are empty.' );
	share_true( ! str_contains( $html, 'data-wp-seed-event-share-action-label' ), 'Icon-only labels remained visible.' );
	share_true( 4 === substr_count( $html, 'aria-label=' ), 'Icon-only accessible names differ.' );
} );
share_case( 'icon-only mode falls back to the user label when no icon is configured', function () use ( $event ) {
	$html = wp_seed_events_render_event_share_menu( $event, array( 'display_mode' => 'icon' ) );
	share_true( ! str_contains( $html, ' data-icon=' ), 'Icon-only mode invented icons.' );
	share_true( 3 === substr_count( $html, 'data-wp-seed-event-share-action-label' ), 'Icon-only actions became visually empty.' );
} );
share_case( 'each action accepts its own Divi icon', function () use ( $event ) {
	$html = wp_seed_events_render_event_share_menu(
		$event,
		array( 'share_icon' => '&#xe001;', 'copy_icon' => '&#xe002;', 'email_icon' => '&#xe003;', 'use_divi_button' => true )
	);
	share_true( 3 === substr_count( $html, ' data-icon=' ), 'Distinct Divi icon contract differs.' );
	share_true( str_contains( $html, "data-icon=\"\u{e001}\"" ), 'First native icon differs.' );
	share_true( str_contains( $html, "data-icon=\"\u{e002}\"" ), 'Second native icon differs.' );
	share_true( str_contains( $html, "data-icon=\"\u{e003}\"" ), 'Third native icon differs.' );
} );
share_case( 'Divi placement and hover state select one native pseudo element', function () use ( $event ) {
	$html = wp_seed_events_render_event_share_menu(
		$event,
		array(
			'share_icon'           => '&#xe001;',
			'share_icon_placement' => 'left',
			'share_icon_on_hover'  => 'off',
			'copy_icon'            => '&#xe002;',
			'copy_icon_placement'  => 'right',
			'copy_icon_on_hover'   => 'on',
			'use_divi_button'      => true,
		)
	);
	share_true( str_contains( $html, 'has-icon-left is-icon-hover-off' ), 'Left icon state missing.' );
	share_true( str_contains( $html, 'data-icon-placement="left"' ), 'Left icon placement attribute missing.' );
	share_true( str_contains( $html, 'has-icon-right is-icon-hover-on' ), 'Right icon state missing.' );
	share_true( str_contains( $html, 'data-icon-placement="right"' ), 'Right icon placement attribute missing.' );
} );
share_case( 'copy-only output stays valid', function () use ( $event ) {
	$html = wp_seed_events_render_event_share_menu( $event, array( 'show_share' => false, 'show_email' => false ) );
	share_true( 1 === substr_count( $html, '<button ' ) && ! str_contains( $html, '<a ' ), 'Copy-only output differs.' );
} );
share_case( 'all actions hidden renders nothing', function () use ( $event ) {
	$html = wp_seed_events_render_event_share_menu( $event, array( 'show_share' => false, 'show_copy' => false, 'show_email' => false ) );
	share_true( '' === $html, 'Empty action bar rendered.' );
} );
share_case( 'custom labels are escaped', function () use ( $event ) {
	$html = wp_seed_events_render_event_share_menu( $event, array( 'label' => '<b>Diffuser</b>', 'copy_label' => '<script>Copier</script>' ) );
	share_true( str_contains( $html, '>Diffuser</span>' ) && str_contains( $html, '>Copier</span>' ), 'Labels were not normalized.' );
	share_true( ! str_contains( $html, '<script>' ), 'Unsafe label leaked.' );
} );
share_case( 'legacy dropdown-only options are inert', function () use ( $event ) {
	$html = wp_seed_events_render_event_share_menu( $event, array( 'show_icon' => true, 'layout' => 'stacked' ) );
	share_true( ! str_contains( $html, 'is-layout-stacked' ) && ! str_contains( $html, '<details' ), 'Legacy presentation remained active.' );
} );
share_case( 'semantic buttons and mail link remain keyboard native', function () use ( $event ) {
	$html = wp_seed_events_render_event_share_menu( $event );
	share_true( 2 === substr_count( $html, 'type="button"' ), 'JS action semantics differ.' );
	share_true( str_contains( $html, 'href="mailto:' ), 'Email is not a native link.' );
} );

echo "Share renderer harness: 21/21 PASS\n";
