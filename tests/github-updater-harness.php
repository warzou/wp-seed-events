<?php
/** Standalone contract assertions for the controlled GitHub updater. */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'WP_SEED_EVENTS_VERSION', '0.2.0-beta.2' );

$GLOBALS['updater_hooks']       = array();
$GLOBALS['updater_site_options'] = array();
$GLOBALS['updater_transients']  = array();
$GLOBALS['updater_multisite']   = false;
$GLOBALS['updater_http_calls']  = 0;
$GLOBALS['updater_http']        = array( 'status' => 200, 'body' => '[]', 'headers' => array() );
$GLOBALS['updater_cases']       = 0;
$GLOBALS['updater_can_update']  = true;

class WP_Error {
	private $code;
	private $message;
	private $data;

	public function __construct( $code = '', $message = '', $data = null ) {
		$this->code = $code;
		$this->message = $message;
		$this->data = $data;
	}

	public function get_error_code() {
		return $this->code;
	}
}

function is_wp_error( $value ) {
	return $value instanceof WP_Error;
}

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['updater_hooks'][ $hook ][] = compact( 'callback', 'priority', 'accepted_args' );
}

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	add_filter( $hook, $callback, $priority, $accepted_args );
}
function remove_filter( $hook, $callback, $priority = 10 ) {
	if ( empty( $GLOBALS['updater_hooks'][ $hook ] ) ) {
		return false;
	}

	$before = count( $GLOBALS['updater_hooks'][ $hook ] );
	$GLOBALS['updater_hooks'][ $hook ] = array_values(
		array_filter(
			$GLOBALS['updater_hooks'][ $hook ],
			function ( $registered ) use ( $callback, $priority ) {
				return $registered['callback'] !== $callback || $registered['priority'] !== $priority;
			}
		)
	);
	return $before !== count( $GLOBALS['updater_hooks'][ $hook ] );
}

function plugin_basename( $file ) {
	return 'wp-seed-events/' . basename( $file );
}

function get_site_option( $name, $default = false ) {
	return $GLOBALS['updater_site_options'][ $name ] ?? $default;
}

function is_multisite() {
	return ! empty( $GLOBALS['updater_multisite'] );
}

function get_site_transient( $name ) {
	return $GLOBALS['updater_transients'][ $name ] ?? false;
}

function set_site_transient( $name, $value, $expiration = 0 ) {
	$GLOBALS['updater_transients'][ $name ] = $value;
	return true;
}

function delete_site_transient( $name ) {
	unset( $GLOBALS['updater_transients'][ $name ] );
	return true;
}

function home_url() {
	return 'https://example.test/';
}

function esc_url_raw( $value, $protocols = null ) {
	return preg_match( '#^https?://#', (string) $value ) ? (string) $value : '';
}

function wp_parse_url( $url ) {
	return parse_url( $url );
}

function sanitize_text_field( $value ) {
	return trim( strip_tags( (string) $value ) );
}

function absint( $value ) {
	return abs( (int) $value );
}

function wp_safe_remote_get( $url, $args = array() ) {
	$GLOBALS['updater_http_calls']++;
	return $GLOBALS['updater_http'];
}

function wp_remote_retrieve_response_code( $response ) {
	return $response['status'] ?? 0;
}

function wp_remote_retrieve_body( $response ) {
	return $response['body'] ?? '';
}

function wp_remote_retrieve_header( $response, $name ) {
	return $response['headers'][ strtolower( $name ) ] ?? '';
}

function esc_html( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}

function esc_attr( $value ) { return esc_html( $value ); }
function esc_url( $value ) { return (string) $value; }
function __( $value, $domain = null ) { return (string) $value; }
function esc_html__( $value, $domain = null ) { return esc_html( $value ); }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function wp_unslash( $value ) { return $value; }
function current_user_can( $capability ) { return 'update_plugins' === $capability && ! empty( $GLOBALS['updater_can_update'] ); }
function network_admin_url( $path = '' ) { return 'https://example.test/wp-admin/network/' . ltrim( $path, '/' ); }
function untrailingslashit( $value ) { return rtrim( (string) $value, '/\\' ); }
function trailingslashit( $value ) { return untrailingslashit( $value ) . '/'; }
function self_admin_url( $path = '' ) { return 'https://example.test/wp-admin/' . ltrim( $path, '/' ); }
function add_query_arg( $args, $url ) { return $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . http_build_query( $args ); }
function wp_nonce_url( $url, $action ) { return add_query_arg( array( '_wpnonce' => 'nonce-' . $action ), $url ); }

require dirname( __DIR__ ) . '/includes/admin/github-updater.php';

function updater_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function updater_case( $label, $callback ) {
	$GLOBALS['updater_cases']++;
	$callback();
	echo 'ok ' . $GLOBALS['updater_cases'] . ' - ' . $label . PHP_EOL;
}

function updater_asset( $name, $size = 100 ) {
	return array(
		'name'                 => $name,
		'size'                 => $size,
		'browser_download_url' => 'https://github.com/warzou/wp-seed-events/releases/download/v1/' . $name,
	);
}

function updater_release( $version, $prerelease = false, $overrides = array() ) {
	$zip = 'wp-seed-events-' . $version . '.zip';
	return array_merge(
		array(
			'tag_name'     => 'v' . $version,
			'name'         => 'Release ' . $version,
			'body'         => 'Changes',
			'html_url'     => 'https://github.com/warzou/wp-seed-events/releases/tag/v' . $version,
			'published_at' => '2026-07-26T10:00:00Z',
			'draft'        => false,
			'prerelease'   => $prerelease,
			'assets'       => array( updater_asset( $zip ), updater_asset( $zip . '.sha256', 64 ) ),
		),
		$overrides
	);
}

function updater_reset_cache( $releases = array() ) {
	$GLOBALS['updater_transients'] = array( wp_seed_events_github_updater_cache_key() => $releases );
}

updater_case( 'hooks are isolated and registered once', function () {
	foreach ( array( 'pre_set_site_transient_update_plugins', 'plugins_api', 'plugin_row_meta', 'upgrader_pre_download', 'upgrader_source_selection', 'upgrader_process_complete', 'admin_init', 'all_admin_notices' ) as $hook ) {
		updater_assert( 1 === count( $GLOBALS['updater_hooks'][ $hook ] ?? array() ), 'Hook differs: ' . $hook );
	}
	updater_assert( 999 === $GLOBALS['updater_hooks']['upgrader_process_complete'][0]['priority'], 'Process-complete cleanup priority differs.' );
} );

updater_case( 'explicit prerelease opt-in is disabled by default', function () {
	updater_assert( ! wp_seed_events_github_updater_prereleases_enabled(), 'Explicit prerelease opt-in must default to disabled.' );
} );

updater_case( 'an installed prerelease follows the prerelease channel', function () {
	updater_assert( wp_seed_events_github_updater_allows_prereleases(), 'Installed beta did not enable prerelease continuity.' );
	updater_assert( wp_seed_events_github_updater_is_prerelease_version( WP_SEED_EVENTS_VERSION ), 'Installed beta was not detected.' );
	updater_assert( ! wp_seed_events_github_updater_is_prerelease_version( '1.0.0' ), 'Stable version was treated as prerelease.' );
} );

updater_case( 'same version produces no update', function () {
	updater_assert( array() === wp_seed_events_github_updater_select_release( array( updater_release( '0.2.0-beta.2', true ) ), WP_SEED_EVENTS_VERSION, true ), 'Same version was selected.' );
} );

updater_case( 'higher stable is selected', function () {
	$selected = wp_seed_events_github_updater_select_release( array( updater_release( '0.2.0', false ) ), WP_SEED_EVENTS_VERSION, false );
	updater_assert( '0.2.0' === $selected['version'], 'Stable update was not selected.' );
} );

updater_case( 'prerelease is ignored while channel is disabled', function () {
	updater_assert( array() === wp_seed_events_github_updater_select_release( array( updater_release( '0.2.0-beta.3', true ) ), WP_SEED_EVENTS_VERSION, false ), 'Prerelease leaked into stable channel.' );
} );

updater_case( 'prerelease is selected when channel is enabled', function () {
	$selected = wp_seed_events_github_updater_select_release( array( updater_release( '0.2.0-beta.3', true ) ), WP_SEED_EVENTS_VERSION, true );
	updater_assert( '0.2.0-beta.3' === $selected['version'], 'Prerelease channel did not select beta.3.' );
} );

updater_case( 'highest admissible release wins', function () {
	$selected = wp_seed_events_github_updater_select_release( array( updater_release( '0.2.0-beta.3', true ), updater_release( '0.2.0-rc.1', true ) ), WP_SEED_EVENTS_VERSION, true );
	updater_assert( '0.2.0-rc.1' === $selected['version'], 'Highest release was not selected.' );
} );

updater_case( 'older release never downgrades', function () {
	updater_assert( array() === wp_seed_events_github_updater_select_release( array( updater_release( '0.2.0-alpha.3', true ) ), WP_SEED_EVENTS_VERSION, true ), 'Downgrade was selected.' );
} );

updater_case( 'invalid tag is rejected', function () {
	$release = updater_release( '0.2.0-beta.3', true, array( 'tag_name' => 'latest' ) );
	updater_assert( array() === wp_seed_events_github_updater_release_candidate( $release, true ), 'Invalid tag was accepted.' );
} );

updater_case( 'missing checksum asset is rejected', function () {
	$release = updater_release( '0.2.0-beta.3', true );
	$release['assets'] = array( $release['assets'][0] );
	updater_assert( array() === wp_seed_events_github_updater_release_candidate( $release, true ), 'Checksum-less release was accepted.' );
} );

updater_case( 'duplicate package asset is rejected', function () {
	$release = updater_release( '0.2.0-beta.3', true );
	$release['assets'][] = $release['assets'][0];
	updater_assert( array() === wp_seed_events_github_updater_release_candidate( $release, true ), 'Ambiguous package was accepted.' );
} );

updater_case( 'non HTTPS asset is rejected', function () {
	$release = updater_release( '0.2.0-beta.3', true );
	$release['assets'][0]['browser_download_url'] = 'http://example.test/plugin.zip';
	updater_assert( array() === wp_seed_events_github_updater_release_candidate( $release, true ), 'Insecure package URL was accepted.' );
} );
updater_case( 'HTTPS asset outside the official repository is rejected', function () {
	$release = updater_release( '0.2.0-beta.3', true );
	$release['assets'][0]['browser_download_url'] = 'https://example.test/wp-seed-events-0.2.0-beta.3.zip';
	updater_assert( array() === wp_seed_events_github_updater_release_candidate( $release, true ), 'Foreign package URL was accepted.' );
} );


updater_case( 'ordinary plugin update transient is untouched', function () {
	$transient = (object) array( 'checked' => array( 'other/other.php' => '1.0.0' ), 'response' => array() );
	updater_assert( $transient === wp_seed_events_github_updater_update_transient( $transient ), 'Another plugin transient changed.' );
} );

updater_case( 'selected update is added only for WP Seed Events', function () {
	$GLOBALS['updater_site_options'][ wp_seed_events_github_updater_prerelease_option_name() ] = '1';
	updater_reset_cache( array( updater_release( '0.2.0-beta.3', true ) ) );
	$plugin = wp_seed_events_github_updater_plugin_basename();
	$result = wp_seed_events_github_updater_update_transient( (object) array( 'checked' => array( $plugin => WP_SEED_EVENTS_VERSION ), 'response' => array() ) );
	updater_assert( '0.2.0-beta.3' === $result->response[ $plugin ]->new_version, 'Update response differs.' );
	updater_assert( 1 === count( $result->response ), 'Another plugin response was introduced.' );
} );

updater_case( 'plugin information exposes selected release details', function () {
	$result = wp_seed_events_github_updater_plugin_information( false, 'plugin_information', (object) array( 'slug' => 'wp-seed-events' ) );
	updater_assert( is_object( $result ) && '0.2.0-beta.3' === $result->version, 'Plugin details differ.' );
	updater_assert( '7.0.2' === $result->tested && '8.4' === $result->requires_php, 'Compatibility metadata differs.' );
	updater_assert( false === strpos( $result->sections['changelog'], '<script' ), 'Remote changelog was not escaped.' );
} );

updater_case( 'plugin information falls back to local metadata when GitHub fails', function () {
	$GLOBALS['updater_transients'] = array();
	$GLOBALS['updater_http'] = new WP_Error( 'http_request_failed', 'Offline.' );
	$result = wp_seed_events_github_updater_plugin_information( false, 'plugin_information', (object) array( 'slug' => 'wp-seed-events' ) );
	updater_assert( is_object( $result ) && WP_SEED_EVENTS_VERSION === $result->version, 'Local details fallback differs.' );
	updater_assert( '' === $result->download_link, 'Offline fallback exposed a package.' );
	$GLOBALS['updater_http'] = array( 'status' => 200, 'body' => '[]', 'headers' => array() );
} );

updater_case( 'details metadata never presents an older release', function () {
	updater_reset_cache( array( updater_release( '0.2.0-alpha.3', true ) ) );
	$result = wp_seed_events_github_updater_plugin_information( false, 'plugin_information', (object) array( 'slug' => 'wp-seed-events' ) );
	updater_assert( WP_SEED_EVENTS_VERSION === $result->version && '' === $result->download_link, 'Older release leaked into details.' );
	updater_reset_cache( array( updater_release( '0.2.0-beta.3', true ) ) );
} );

updater_case( 'plugin row exposes native details and manual check links once', function () {
	$GLOBALS['updater_can_update'] = true;
	$links = wp_seed_events_github_updater_plugin_row_meta( array(), wp_seed_events_github_updater_plugin_basename(), array( 'Name' => 'WP Seed Events' ), 'active' );
	updater_assert( 2 === count( $links ), 'Plugin row link count differs.' );
	updater_assert( 1 === substr_count( implode( '', $links ), '>Afficher les détails</a>' ), 'Visible details link is absent or duplicated.' );
	updater_assert( 1 === substr_count( implode( '', $links ), '>Vérifier les mises à jour</a>' ), 'Visible manual check link is absent or duplicated.' );
	updater_assert( false !== strpos( $links['wp_seed_events_check_updates'], '_wpnonce=nonce-wp_seed_events_check_updates' ), 'Manual link nonce is absent.' );
} );

updater_case( 'manual check link requires update_plugins', function () {
	$GLOBALS['updater_can_update'] = false;
	$links = wp_seed_events_github_updater_plugin_row_meta( array(), wp_seed_events_github_updater_plugin_basename(), array(), 'active' );
	updater_assert( isset( $links['wp_seed_events_details'] ) && ! isset( $links['wp_seed_events_check_updates'] ), 'Capability boundary differs.' );
	$GLOBALS['updater_can_update'] = true;
} );

updater_case( 'another plugin row is untouched', function () {
	$links = array( 'existing' => 'value' );
	updater_assert( $links === wp_seed_events_github_updater_plugin_row_meta( $links, 'other/other.php', array(), 'active' ), 'Another plugin row changed.' );
} );

updater_case( 'release cache prevents repeated HTTP calls', function () {
	$GLOBALS['updater_http_calls'] = 0;
	updater_reset_cache( array( updater_release( '0.2.0-beta.3', true ) ) );
	wp_seed_events_github_updater_get_releases();
	wp_seed_events_github_updater_get_releases();
	updater_assert( 0 === $GLOBALS['updater_http_calls'], 'Cached metadata triggered HTTP.' );
} );

updater_case( 'HTTP 404 fails cleanly', function () {
	$GLOBALS['updater_http'] = array( 'status' => 404, 'body' => '', 'headers' => array() );
	$result = wp_seed_events_github_updater_get_releases( true );
	updater_assert( is_wp_error( $result ) && 'github_http_error' === $result->get_error_code(), 'HTTP 404 error differs.' );
} );

updater_case( 'HTTP 403 is treated as rate limiting', function () {
	$GLOBALS['updater_http'] = array( 'status' => 403, 'body' => '', 'headers' => array() );
	$result = wp_seed_events_github_updater_get_releases( true );
	updater_assert( is_wp_error( $result ) && 'github_rate_limited' === $result->get_error_code(), 'HTTP 403 error differs.' );
} );

updater_case( 'HTTP 500 fails cleanly', function () {
	$GLOBALS['updater_http'] = array( 'status' => 500, 'body' => '', 'headers' => array() );
	$result = wp_seed_events_github_updater_get_releases( true );
	updater_assert( is_wp_error( $result ) && 'github_http_error' === $result->get_error_code(), 'HTTP 500 error differs.' );
} );

updater_case( 'network timeout fails cleanly', function () {
	$GLOBALS['updater_http'] = new WP_Error( 'http_request_failed', 'Operation timed out.' );
	$result = wp_seed_events_github_updater_get_releases( true );
	updater_assert( is_wp_error( $result ) && 'http_request_failed' === $result->get_error_code(), 'Timeout error differs.' );
} );

updater_case( 'metadata fetch recovers after a network failure', function () {
	$GLOBALS['updater_http'] = array( 'status' => 200, 'body' => json_encode( array( updater_release( '0.2.0-beta.3', true ) ) ), 'headers' => array() );
	$result = wp_seed_events_github_updater_get_releases( true );
	updater_assert( is_array( $result ) && 1 === count( $result ), 'Metadata fetch did not recover.' );
} );

updater_case( 'manual cache purge only clears Events state', function () {
	$plugin = wp_seed_events_github_updater_plugin_basename();
	$GLOBALS['updater_transients'][ wp_seed_events_github_updater_cache_key() ] = array( updater_release( '0.2.0-beta.3', true ) );
	$GLOBALS['updater_transients']['update_plugins'] = (object) array(
		'response' => array( $plugin => (object) array(), 'other/other.php' => (object) array( 'new_version' => '2.0' ) ),
		'no_update' => array( $plugin => (object) array(), 'third/third.php' => (object) array( 'new_version' => '1.0' ) ),
	);
	wp_seed_events_github_updater_clear_cache();
	$state = $GLOBALS['updater_transients']['update_plugins'];
	updater_assert( ! isset( $GLOBALS['updater_transients'][ wp_seed_events_github_updater_cache_key() ] ), 'Release cache survived purge.' );
	updater_assert( ! isset( $state->response[ $plugin ], $state->no_update[ $plugin ] ), 'Events update state survived purge.' );
	updater_assert( isset( $state->response['other/other.php'], $state->no_update['third/third.php'] ), 'Another plugin update state was purged.' );
} );

updater_case( 'same release populates only the Events no_update entry', function () {
	$GLOBALS['updater_site_options'][ wp_seed_events_github_updater_prerelease_option_name() ] = '0';
	updater_reset_cache( array( updater_release( WP_SEED_EVENTS_VERSION, true ) ) );
	$plugin = wp_seed_events_github_updater_plugin_basename();
	$state = wp_seed_events_github_updater_update_transient( (object) array( 'checked' => array( $plugin => WP_SEED_EVENTS_VERSION ), 'response' => array( 'other/other.php' => (object) array() ) ) );
	updater_assert( isset( $state->no_update[ $plugin ] ) && WP_SEED_EVENTS_VERSION === $state->no_update[ $plugin ]->new_version, 'No-update metadata is absent.' );
	updater_assert( isset( $state->response['other/other.php'] ), 'Another plugin response changed.' );
} );

updater_case( 'incomplete newer release is reported separately', function () {
	$release = updater_release( '0.2.0-beta.3', true );
	$release['assets'] = array( $release['assets'][0] );
	updater_assert( wp_seed_events_github_updater_has_incomplete_newer_release( array( $release ) ), 'Incomplete newer release was not detected.' );
} );

updater_case( 'User-Agent identifies the plugin without exposing the site URL', function () {
	$headers = wp_seed_events_github_updater_headers();
	updater_assert( 'WP-Seed-Events/' . WP_SEED_EVENTS_VERSION === $headers['User-Agent'], 'User-Agent differs or exposes site context.' );
} );

updater_case( 'invalid checksum body is rejected', function () {
	$release = wp_seed_events_github_updater_release_candidate( updater_release( '0.2.0-beta.3', true ), true );
	$GLOBALS['updater_http'] = array( 'status' => 200, 'body' => 'not-a-checksum', 'headers' => array() );
	$result = wp_seed_events_github_updater_request_checksum( $release );
	updater_assert( is_wp_error( $result ) && 'github_checksum_invalid' === $result->get_error_code(), 'Invalid checksum was accepted.' );
} );

updater_case( 'invalid JSON fails cleanly', function () {
	$GLOBALS['updater_transients'] = array();
	$GLOBALS['updater_http'] = array( 'status' => 200, 'body' => '{invalid', 'headers' => array() );
	$result = wp_seed_events_github_updater_get_releases( true );
	updater_assert( is_wp_error( $result ) && 'github_invalid_json' === $result->get_error_code(), 'Invalid JSON error differs.' );
} );

updater_case( 'rate limit fails cleanly', function () {
	$GLOBALS['updater_http'] = array( 'status' => 429, 'body' => '', 'headers' => array() );
	$result = wp_seed_events_github_updater_get_releases( true );
	updater_assert( is_wp_error( $result ) && 'github_rate_limited' === $result->get_error_code(), 'Rate limit error differs.' );
} );

updater_case( 'checksum with exact filename is accepted', function () {
	$release = wp_seed_events_github_updater_release_candidate( updater_release( '0.2.0-beta.3', true ), true );
	$GLOBALS['updater_http'] = array( 'status' => 200, 'body' => str_repeat( 'a', 64 ) . '  ' . $release['package_name'], 'headers' => array() );
	updater_assert( str_repeat( 'a', 64 ) === wp_seed_events_github_updater_request_checksum( $release ), 'Checksum parsing differs.' );
} );

updater_case( 'checksum for another filename is rejected', function () {
	$release = wp_seed_events_github_updater_release_candidate( updater_release( '0.2.0-beta.3', true ), true );
	$GLOBALS['updater_http'] = array( 'status' => 200, 'body' => str_repeat( 'b', 64 ) . '  other.zip', 'headers' => array() );
	$result = wp_seed_events_github_updater_request_checksum( $release );
	updater_assert( is_wp_error( $result ) && 'github_checksum_mismatch' === $result->get_error_code(), 'Checksum filename mismatch was accepted.' );
} );

updater_case( 'valid archive manifest and version are accepted', function () {
	$result = wp_seed_events_github_updater_validate_archive_manifest(
		array( 'wp-seed-events/', 'wp-seed-events/wp-seed-events.php', 'wp-seed-events/includes/public/event-data.php' ),
		"<?php\n * Version: 0.2.0-beta.2\n",
		'0.2.0-beta.2'
	);
	updater_assert( true === $result, 'Valid manifest was rejected.' );
} );

updater_case( 'archive traversal is rejected', function () {
	$result = wp_seed_events_github_updater_validate_archive_manifest(
		array( 'wp-seed-events/wp-seed-events.php', 'wp-seed-events/../secret.txt' ),
		"<?php\n * Version: 0.2.0-beta.2\n",
		'0.2.0-beta.2'
	);
	updater_assert( is_wp_error( $result ) && 'github_zip_unsafe' === $result->get_error_code(), 'Traversal was accepted.' );
} );

updater_case( 'wrong archive root is rejected', function () {
	$result = wp_seed_events_github_updater_validate_archive_manifest( array( 'other/wp-seed-events.php' ), "<?php\n * Version: 0.2.0-beta.2\n", '0.2.0-beta.2' );
	updater_assert( is_wp_error( $result ) && 'github_zip_root_invalid' === $result->get_error_code(), 'Wrong root was accepted.' );
} );

updater_case( 'archive version mismatch is rejected', function () {
	$result = wp_seed_events_github_updater_validate_archive_manifest( array( 'wp-seed-events/wp-seed-events.php' ), "<?php\n * Version: 0.2.0-beta.1\n", '0.2.0-beta.2' );
	updater_assert( is_wp_error( $result ) && 'github_zip_version_mismatch' === $result->get_error_code(), 'Wrong internal version was accepted.' );
} );

updater_case( 'source selection preserves the directory separator required by WordPress', function () {
	updater_reset_cache( array( updater_release( '0.2.0-beta.3', true ) ) );
	$base = sys_get_temp_dir() . '/wp-seed-events-updater-' . uniqid( '', true );
	$source = $base . '/wp-seed-events';
	mkdir( $source, 0777, true );
	file_put_contents( $source . '/wp-seed-events.php', "<?php\n * Version: 0.2.0-beta.3\n" );
	try {
		$result = wp_seed_events_github_updater_source_selection( $source . '/', '', null, array( 'plugin' => wp_seed_events_github_updater_plugin_basename() ) );
		updater_assert( trailingslashit( $source ) === $result, 'Source directory separator was not preserved.' );
	} finally {
		unlink( $source . '/wp-seed-events.php' );
		rmdir( $source );
		rmdir( $base );
	}
} );
updater_case( 'individual and bulk upgrader hooks identify only WP Seed Events', function () {
	$plugin = wp_seed_events_github_updater_plugin_basename();
	updater_assert( wp_seed_events_github_updater_is_our_upgrade( array( 'plugin' => $plugin ) ), 'Individual update hook was not recognized.' );
	updater_assert( wp_seed_events_github_updater_is_our_upgrade( array( 'plugins' => array( 'other/other.php', $plugin ) ) ), 'Bulk update hook was not recognized.' );
	updater_assert( ! wp_seed_events_github_updater_is_our_upgrade( array( 'plugins' => array( 'other/other.php' ) ) ), 'Foreign bulk update hook was accepted.' );
} );
updater_case( 'package interception refuses an unexpected URL', function () {
	updater_reset_cache( array( updater_release( '0.2.0-beta.3', true ) ) );
	$result = wp_seed_events_github_updater_pre_download( false, 'https://example.test/other.zip', null, array( 'plugin' => wp_seed_events_github_updater_plugin_basename() ) );
	updater_assert( is_wp_error( $result ) && 'github_package_unexpected' === $result->get_error_code(), 'Unexpected package was accepted.' );
} );

updater_case( 'package interception leaves another plugin untouched', function () {
	updater_assert( false === wp_seed_events_github_updater_pre_download( false, 'https://example.test/other.zip', null, array( 'plugin' => 'other/other.php' ) ), 'Another plugin download changed.' );
} );

updater_case( 'settings capability follows single-site and network administration', function () {
	$GLOBALS['updater_multisite'] = false;
	updater_assert( 'manage_options' === wp_seed_events_github_updater_settings_capability(), 'Single-site capability differs.' );
	$GLOBALS['updater_multisite'] = true;
	updater_assert( 'manage_network_plugins' === wp_seed_events_github_updater_settings_capability(), 'Multisite capability differs.' );
	$GLOBALS['updater_multisite'] = false;
} );

updater_case( 'manual and settings callbacks enforce capability and nonce boundaries', function () {
	$source = file_get_contents( dirname( __DIR__ ) . '/includes/admin/github-updater.php' );
	updater_assert( false !== strpos( $source, "current_user_can( 'update_plugins' )" ), 'Manual check capability guard is absent.' );
	updater_assert( false !== strpos( $source, "check_admin_referer( 'wp_seed_events_check_updates' )" ), 'Manual check nonce guard is absent.' );
	updater_assert( false !== strpos( $source, "current_user_can( wp_seed_events_github_updater_settings_capability() )" ), 'Settings capability guard is absent.' );
	updater_assert( false !== strpos( $source, "check_admin_referer( 'wp_seed_events_save_update_settings'" ), 'Settings nonce guard is absent.' );
	updater_assert( false !== strpos( $source, 'update_site_option(' ), 'Network-scoped channel storage is absent.' );
	updater_assert( false !== strpos( $source, "function wp_seed_events_github_updater_admin_menu() {\n\tif ( is_multisite() )" ), 'The local settings menu is not hidden in multisite.' );
} );

echo 'GitHub updater harness: ' . $GLOBALS['updater_cases'] . '/' . $GLOBALS['updater_cases'] . ' OK' . PHP_EOL;
