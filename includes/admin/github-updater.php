<?php
/**
 * Controlled GitHub release updater for manual WordPress updates.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'pre_set_site_transient_update_plugins', 'wp_seed_events_github_updater_update_transient' );
add_filter( 'plugins_api', 'wp_seed_events_github_updater_plugin_information', 20, 3 );
add_filter( 'plugin_row_meta', 'wp_seed_events_github_updater_plugin_row_meta', 10, 4 );
add_filter( 'upgrader_pre_download', 'wp_seed_events_github_updater_pre_download', 10, 4 );
add_filter( 'upgrader_source_selection', 'wp_seed_events_github_updater_source_selection', 10, 4 );
add_action( 'upgrader_process_complete', 'wp_seed_events_github_updater_process_complete', 999, 2 );
add_action( 'admin_init', 'wp_seed_events_github_updater_handle_manual_check' );
add_action( 'all_admin_notices', 'wp_seed_events_github_updater_render_manual_check_notice' );
add_action( 'admin_menu', 'wp_seed_events_github_updater_admin_menu', 100 );
add_action( 'network_admin_menu', 'wp_seed_events_github_updater_network_admin_menu', 100 );
add_action( 'admin_post_wp_seed_events_save_update_settings', 'wp_seed_events_github_updater_save_settings' );

function wp_seed_events_github_updater_repository() {
	return 'warzou/wp-seed-events';
}

function wp_seed_events_github_updater_plugin_slug() {
	return 'wp-seed-events';
}

function wp_seed_events_github_updater_plugin_file() {
	return dirname( dirname( __DIR__ ) ) . '/wp-seed-events.php';
}

function wp_seed_events_github_updater_plugin_basename() {
	return plugin_basename( wp_seed_events_github_updater_plugin_file() );
}

function wp_seed_events_github_updater_prerelease_option_name() {
	return 'wp_seed_events_allow_prerelease_updates';
}

function wp_seed_events_github_updater_cache_key() {
	return 'wp_seed_events_github_releases';
}

function wp_seed_events_github_updater_manual_notice_key() {
	return 'wp_seed_events_github_manual_check';
}

function wp_seed_events_github_updater_prereleases_enabled() {
	return '1' === (string) get_site_option( wp_seed_events_github_updater_prerelease_option_name(), '0' );
}

function wp_seed_events_github_updater_is_prerelease_version( $version ) {
	return false !== strpos( (string) $version, '-' );
}

function wp_seed_events_github_updater_allows_prereleases() {
	return wp_seed_events_github_updater_prereleases_enabled()
		|| wp_seed_events_github_updater_is_prerelease_version( WP_SEED_EVENTS_VERSION );
}

function wp_seed_events_github_updater_requires_wp() {
	return '7.0';
}

function wp_seed_events_github_updater_tested_wp() {
	return '7.0.2';
}

function wp_seed_events_github_updater_requires_php() {
	return '8.4';
}

function wp_seed_events_github_updater_api_url() {
	return 'https://api.github.com/repos/' . wp_seed_events_github_updater_repository() . '/releases?per_page=100';
}

function wp_seed_events_github_updater_headers( $accept = 'application/vnd.github+json' ) {
	return array(
		'Accept'     => $accept,
		'User-Agent' => 'WP-Seed-Events/' . WP_SEED_EVENTS_VERSION,
	);
}

function wp_seed_events_github_updater_clear_cache() {
	delete_site_transient( wp_seed_events_github_updater_cache_key() );
	wp_seed_events_github_updater_forget_wordpress_state();
}

function wp_seed_events_github_updater_forget_wordpress_state() {
	$transient = get_site_transient( 'update_plugins' );

	if ( ! is_object( $transient ) ) {
		return;
	}

	$plugin = wp_seed_events_github_updater_plugin_basename();

	if ( isset( $transient->response ) && is_array( $transient->response ) ) {
		unset( $transient->response[ $plugin ] );
	}

	if ( isset( $transient->no_update ) && is_array( $transient->no_update ) ) {
		unset( $transient->no_update[ $plugin ] );
	}

	remove_filter( 'pre_set_site_transient_update_plugins', 'wp_seed_events_github_updater_update_transient' );
	set_site_transient( 'update_plugins', $transient );
	add_filter( 'pre_set_site_transient_update_plugins', 'wp_seed_events_github_updater_update_transient' );
}

function wp_seed_events_github_updater_get_releases( $force = false ) {
	if ( ! $force ) {
		$cached = get_site_transient( wp_seed_events_github_updater_cache_key() );

		if ( is_array( $cached ) ) {
			return $cached;
		}
	}

	$response = wp_safe_remote_get(
		wp_seed_events_github_updater_api_url(),
		array(
			'timeout'     => 5,
			'redirection' => 3,
			'headers'     => wp_seed_events_github_updater_headers(),
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$status = (int) wp_remote_retrieve_response_code( $response );

	if ( 200 !== $status ) {
		$code = in_array( $status, array( 403, 429 ), true ) ? 'github_rate_limited' : 'github_http_error';
		return new WP_Error( $code, 'GitHub release metadata is unavailable.', array( 'status' => $status ) );
	}

	$decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );

	if ( ! is_array( $decoded ) ) {
		return new WP_Error( 'github_invalid_json', 'GitHub returned invalid release metadata.' );
	}

	set_site_transient( wp_seed_events_github_updater_cache_key(), $decoded, 6 * HOUR_IN_SECONDS );

	return $decoded;
}

function wp_seed_events_github_updater_normalize_version( $tag ) {
	$version = ltrim( trim( (string) $tag ), 'vV' );

	return preg_match( '/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?$/D', $version ) ? $version : '';
}

function wp_seed_events_github_updater_release_asset_url( $value ) {
	$url   = wp_seed_events_github_updater_https_url( $value );
	$parts = '' !== $url ? wp_parse_url( $url ) : false;

	if ( ! is_array( $parts ) || 'github.com' !== strtolower( (string) ( $parts['host'] ?? '' ) ) ) {
		return '';
	}

	$prefix = '/' . wp_seed_events_github_updater_repository() . '/releases/download/';

	return 0 === strpos( (string) ( $parts['path'] ?? '' ), $prefix ) ? $url : '';
}

function wp_seed_events_github_updater_release_details_url( $value ) {
	$url   = wp_seed_events_github_updater_https_url( $value );
	$parts = '' !== $url ? wp_parse_url( $url ) : false;

	if ( ! is_array( $parts ) || 'github.com' !== strtolower( (string) ( $parts['host'] ?? '' ) ) ) {
		return '';
	}

	$prefix = '/' . wp_seed_events_github_updater_repository() . '/releases/tag/';

	return 0 === strpos( (string) ( $parts['path'] ?? '' ), $prefix ) ? $url : '';
}

function wp_seed_events_github_updater_https_url( $value ) {
	$value = esc_url_raw( (string) $value, array( 'https' ) );

	return 0 === strpos( $value, 'https://' ) ? $value : '';
}

function wp_seed_events_github_updater_release_candidate( $release, $allow_prereleases ) {
	if ( ! is_array( $release ) || ! empty( $release['draft'] ) ) {
		return array();
	}

	$is_prerelease = ! empty( $release['prerelease'] );

	if ( $is_prerelease && ! $allow_prereleases ) {
		return array();
	}

	$tag     = isset( $release['tag_name'] ) ? (string) $release['tag_name'] : '';
	$version = wp_seed_events_github_updater_normalize_version( $tag );

	if ( '' === $version ) {
		return array();
	}

	$zip_name      = 'wp-seed-events-' . $version . '.zip';
	$checksum_name = $zip_name . '.sha256';
	$zip_assets    = array();
	$sum_assets    = array();

	foreach ( (array) ( $release['assets'] ?? array() ) as $asset ) {
		if ( ! is_array( $asset ) || empty( $asset['name'] ) ) {
			continue;
		}

		if ( $zip_name === (string) $asset['name'] ) {
			$zip_assets[] = $asset;
		} elseif ( $checksum_name === (string) $asset['name'] ) {
			$sum_assets[] = $asset;
		}
	}

	if ( 1 !== count( $zip_assets ) || 1 !== count( $sum_assets ) ) {
		return array();
	}

	$package_url  = wp_seed_events_github_updater_release_asset_url( $zip_assets[0]['browser_download_url'] ?? '' );
	$checksum_url = wp_seed_events_github_updater_release_asset_url( $sum_assets[0]['browser_download_url'] ?? '' );
	$details_url  = wp_seed_events_github_updater_release_details_url( $release['html_url'] ?? '' );

	if ( '' === $package_url || '' === $checksum_url || '' === $details_url ) {
		return array();
	}

	return array(
		'version'       => $version,
		'tag'           => $tag,
		'prerelease'    => $is_prerelease,
		'name'          => sanitize_text_field( (string) ( $release['name'] ?? $tag ) ),
		'body'          => (string) ( $release['body'] ?? '' ),
		'details_url'   => $details_url,
		'package_url'   => $package_url,
		'package_name'  => $zip_name,
		'package_size'  => absint( $zip_assets[0]['size'] ?? 0 ),
		'checksum_url'  => $checksum_url,
		'checksum_name' => $checksum_name,
		'published_at'  => sanitize_text_field( (string) ( $release['published_at'] ?? '' ) ),
	);
}

function wp_seed_events_github_updater_select_release( $releases, $installed_version, $allow_prereleases = false ) {
	$selected = array();

	foreach ( (array) $releases as $release ) {
		$candidate = wp_seed_events_github_updater_release_candidate( $release, $allow_prereleases );

		if ( array() === $candidate || version_compare( $candidate['version'], $installed_version, '<=' ) ) {
			continue;
		}

		if ( array() === $selected || version_compare( $candidate['version'], $selected['version'], '>' ) ) {
			$selected = $candidate;
		}
	}

	return $selected;
}

function wp_seed_events_github_updater_available_release() {
	$releases = wp_seed_events_github_updater_get_releases();

	if ( is_wp_error( $releases ) ) {
		return $releases;
	}

	return wp_seed_events_github_updater_select_release(
		$releases,
		WP_SEED_EVENTS_VERSION,
		wp_seed_events_github_updater_allows_prereleases()
	);
}

function wp_seed_events_github_updater_release_for_details() {
	$releases = wp_seed_events_github_updater_get_releases();

	if ( is_wp_error( $releases ) ) {
		return $releases;
	}

	$selected = array();

	foreach ( $releases as $release ) {
		$candidate = wp_seed_events_github_updater_release_candidate( $release, wp_seed_events_github_updater_allows_prereleases() );

		if ( array() !== $candidate && version_compare( $candidate['version'], WP_SEED_EVENTS_VERSION, '>=' ) && ( array() === $selected || version_compare( $candidate['version'], $selected['version'], '>' ) ) ) {
			$selected = $candidate;
		}
	}

	return $selected;
}

function wp_seed_events_github_updater_update_data( $release, $plugin ) {
	return (object) array(
		'id'           => 'https://github.com/' . wp_seed_events_github_updater_repository(),
		'slug'         => wp_seed_events_github_updater_plugin_slug(),
		'plugin'       => $plugin,
		'new_version'  => $release['version'],
		'url'          => $release['details_url'],
		'package'      => $release['package_url'],
		'requires'     => wp_seed_events_github_updater_requires_wp(),
		'tested'       => wp_seed_events_github_updater_tested_wp(),
		'requires_php' => wp_seed_events_github_updater_requires_php(),
	);
}

function wp_seed_events_github_updater_no_update_data( $plugin ) {
	return (object) array(
		'id'           => 'https://github.com/' . wp_seed_events_github_updater_repository(),
		'slug'         => wp_seed_events_github_updater_plugin_slug(),
		'plugin'       => $plugin,
		'new_version'  => WP_SEED_EVENTS_VERSION,
		'url'          => 'https://github.com/' . wp_seed_events_github_updater_repository(),
		'package'      => '',
		'requires'     => wp_seed_events_github_updater_requires_wp(),
		'tested'       => wp_seed_events_github_updater_tested_wp(),
		'requires_php' => wp_seed_events_github_updater_requires_php(),
	);
}

function wp_seed_events_github_updater_update_transient( $transient ) {
	if ( ! is_object( $transient ) || empty( $transient->checked ) ) {
		return $transient;
	}

	$plugin = wp_seed_events_github_updater_plugin_basename();

	if ( empty( $transient->checked[ $plugin ] ) ) {
		return $transient;
	}

	$transient->response  = isset( $transient->response ) && is_array( $transient->response ) ? $transient->response : array();
	$transient->no_update = isset( $transient->no_update ) && is_array( $transient->no_update ) ? $transient->no_update : array();
	$release              = wp_seed_events_github_updater_available_release();

	if ( is_wp_error( $release ) ) {
		return $transient;
	}

	if ( array() === $release ) {
		unset( $transient->response[ $plugin ] );
		$transient->no_update[ $plugin ] = wp_seed_events_github_updater_no_update_data( $plugin );
		return $transient;
	}

	unset( $transient->no_update[ $plugin ] );
	$transient->response[ $plugin ] = wp_seed_events_github_updater_update_data( $release, $plugin );
	return $transient;
}

function wp_seed_events_github_updater_plugin_information( $result, $action, $args ) {
	if ( 'plugin_information' !== $action || ! is_object( $args ) || wp_seed_events_github_updater_plugin_slug() !== ( $args->slug ?? '' ) ) {
		return $result;
	}

	$release     = wp_seed_events_github_updater_release_for_details();
	$has_release = ! is_wp_error( $release ) && array() !== $release;
	$version     = $has_release ? $release['version'] : WP_SEED_EVENTS_VERSION;
	$homepage    = $has_release ? $release['details_url'] : 'https://github.com/' . wp_seed_events_github_updater_repository();
	$changelog   = $has_release
		? nl2br( esc_html( $release['body'] ) )
		: esc_html__( 'Les informations distantes sont temporairement indisponibles. Consultez le changelog du depot officiel.', 'wp-seed-events' );

	return (object) array(
		'name'          => 'WP Seed Events',
		'slug'          => wp_seed_events_github_updater_plugin_slug(),
		'version'       => $version,
		'author'        => '<a href="https://github.com/warzou">WP Seed</a>',
		'homepage'      => $homepage,
		'download_link' => $has_release ? $release['package_url'] : '',
		'last_updated'  => $has_release ? $release['published_at'] : '',
		'requires'      => wp_seed_events_github_updater_requires_wp(),
		'tested'        => wp_seed_events_github_updater_tested_wp(),
		'requires_php'  => wp_seed_events_github_updater_requires_php(),
		'external'      => true,
		'sections'      => array(
			'description' => esc_html__( 'Gestion et publication d’événements à occurrences multiples.', 'wp-seed-events' ),
			'changelog'   => $changelog,
		),
	);
}

function wp_seed_events_github_updater_details_url() {
	return add_query_arg(
		array(
			'tab'       => 'plugin-information',
			'plugin'    => wp_seed_events_github_updater_plugin_slug(),
			'TB_iframe' => 'true',
			'width'     => 600,
			'height'    => 550,
		),
		network_admin_url( 'plugin-install.php' )
	);
}

function wp_seed_events_github_updater_manual_check_url() {
	$url = add_query_arg(
		array(
			'wp_seed_events_check_updates' => '1',
			'plugin'                      => wp_seed_events_github_updater_plugin_slug(),
		),
		self_admin_url( 'plugins.php' )
	);

	return wp_nonce_url( $url, 'wp_seed_events_check_updates' );
}

function wp_seed_events_github_updater_plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data = array(), $status = '' ) {
	unset( $status );

	if ( wp_seed_events_github_updater_plugin_basename() !== $plugin_file ) {
		return $plugin_meta;
	}

	$name = isset( $plugin_data['Name'] ) ? (string) $plugin_data['Name'] : 'WP Seed Events';
	$plugin_meta['wp_seed_events_details'] = sprintf(
		'<a href="%1$s" class="thickbox open-plugin-details-modal" aria-label="%2$s" data-title="%3$s">%4$s</a>',
		esc_url( wp_seed_events_github_updater_details_url() ),
		esc_attr( sprintf( __( 'Afficher les détails de %s', 'wp-seed-events' ), $name ) ),
		esc_attr( $name ),
		esc_html__( 'Afficher les détails', 'wp-seed-events' )
	);

	if ( current_user_can( 'update_plugins' ) ) {
		$plugin_meta['wp_seed_events_check_updates'] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( wp_seed_events_github_updater_manual_check_url() ),
			esc_html__( 'Vérifier les mises à jour', 'wp-seed-events' )
		);
	}

	return $plugin_meta;
}

function wp_seed_events_github_updater_has_incomplete_newer_release( $releases ) {
	foreach ( (array) $releases as $release ) {
		if ( ! is_array( $release ) || ! empty( $release['draft'] ) ) {
			continue;
		}

		if ( ! empty( $release['prerelease'] ) && ! wp_seed_events_github_updater_allows_prereleases() ) {
			continue;
		}

		$version = wp_seed_events_github_updater_normalize_version( $release['tag_name'] ?? '' );

		if ( '' !== $version && version_compare( $version, WP_SEED_EVENTS_VERSION, '>' ) && array() === wp_seed_events_github_updater_release_candidate( $release, true ) ) {
			return true;
		}
	}

	return false;
}

function wp_seed_events_github_updater_refresh_wordpress_state() {
	$transient = get_site_transient( 'update_plugins' );
	$transient = is_object( $transient ) ? $transient : (object) array();
	$transient->checked = isset( $transient->checked ) && is_array( $transient->checked ) ? $transient->checked : array();
	$transient->checked[ wp_seed_events_github_updater_plugin_basename() ] = WP_SEED_EVENTS_VERSION;
	set_site_transient( 'update_plugins', wp_seed_events_github_updater_update_transient( $transient ) );
}

function wp_seed_events_github_updater_handle_manual_check() {
	if ( empty( $_GET['wp_seed_events_check_updates'] ) || wp_seed_events_github_updater_plugin_slug() !== sanitize_key( wp_unslash( $_GET['plugin'] ?? '' ) ) ) {
		return;
	}

	if ( ! current_user_can( 'update_plugins' ) ) {
		wp_die( esc_html__( 'Vous n’avez pas les droits suffisants pour vérifier les mises à jour.', 'wp-seed-events' ) );
	}

	check_admin_referer( 'wp_seed_events_check_updates' );
	delete_site_transient( wp_seed_events_github_updater_cache_key() );
	wp_seed_events_github_updater_forget_wordpress_state();
	$releases = wp_seed_events_github_updater_get_releases( true );
	$status   = 'up_to_date';
	$error    = '';

	if ( is_wp_error( $releases ) ) {
		$status = 'error';
		$error  = sanitize_key( $releases->get_error_code() );
	} else {
		$release = wp_seed_events_github_updater_select_release( $releases, WP_SEED_EVENTS_VERSION, wp_seed_events_github_updater_allows_prereleases() );

		if ( array() !== $release ) {
			$status = 'update_available';
		} elseif ( wp_seed_events_github_updater_has_incomplete_newer_release( $releases ) ) {
			$status = 'incomplete';
		}

		wp_seed_events_github_updater_refresh_wordpress_state();
	}

	set_site_transient( wp_seed_events_github_updater_manual_notice_key(), array( 'status' => $status, 'error' => $error ), 60 );
	wp_safe_redirect( add_query_arg( array( 'wp_seed_events_update_check_result' => $status, 'plugin' => wp_seed_events_github_updater_plugin_slug() ), self_admin_url( 'plugins.php' ) ) );
	exit;
}

function wp_seed_events_github_updater_render_manual_check_notice() {
	if ( wp_seed_events_github_updater_plugin_slug() !== sanitize_key( wp_unslash( $_GET['plugin'] ?? '' ) ) || empty( $_GET['wp_seed_events_update_check_result'] ) ) {
		return;
	}

	$notice = get_site_transient( wp_seed_events_github_updater_manual_notice_key() );
	delete_site_transient( wp_seed_events_github_updater_manual_notice_key() );
	$status = sanitize_key( wp_unslash( $_GET['wp_seed_events_update_check_result'] ) );
	$status = is_array( $notice ) && isset( $notice['status'] ) ? sanitize_key( $notice['status'] ) : $status;
	$class  = 'notice notice-success is-dismissible';
	$text   = __( 'WP Seed Events est à jour.', 'wp-seed-events' );

	if ( 'update_available' === $status ) {
		$text = __( 'Une mise à jour officielle de WP Seed Events est disponible.', 'wp-seed-events' );
	} elseif ( 'incomplete' === $status ) {
		$class = 'notice notice-warning is-dismissible';
		$text  = __( 'Une release plus récente existe, mais ses assets officiels sont incomplets.', 'wp-seed-events' );
	} elseif ( 'error' === $status ) {
		$class = 'notice notice-error is-dismissible';
		$text  = __( 'La vérification des mises à jour WP Seed Events est temporairement impossible.', 'wp-seed-events' );
	}

	printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $text ) );
}

function wp_seed_events_github_updater_request_checksum( $release ) {
	$response = wp_safe_remote_get(
		$release['checksum_url'],
		array(
			'timeout'     => 10,
			'redirection' => 3,
			'headers'     => wp_seed_events_github_updater_headers( 'text/plain' ),
		)
	);

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return new WP_Error( 'github_checksum_unavailable', 'The release checksum is unavailable.' );
	}

	$body = trim( (string) wp_remote_retrieve_body( $response ) );

	if ( ! preg_match( '/^([a-fA-F0-9]{64})(?:\s+\*?([^\s]+))?$/D', $body, $matches ) ) {
		return new WP_Error( 'github_checksum_invalid', 'The release checksum format is invalid.' );
	}

	if ( ! empty( $matches[2] ) && $release['package_name'] !== basename( (string) $matches[2] ) ) {
		return new WP_Error( 'github_checksum_mismatch', 'The release checksum targets another asset.' );
	}

	return strtolower( $matches[1] );
}

function wp_seed_events_github_updater_validate_archive_manifest( $entries, $main_source, $expected_version ) {
	if ( ! is_array( $entries ) || array() === $entries ) {
		return new WP_Error( 'github_zip_empty', 'The release archive is empty.' );
	}

	$has_main = false;

	foreach ( $entries as $entry ) {
		$entry = (string) $entry;

		if (
			'' === $entry
			|| false !== strpos( $entry, '\\' )
			|| preg_match( '#^(?:/|[A-Za-z]:|\./)#', $entry )
			|| preg_match( '#(?:^|/)\.\.(?:/|$)#', $entry )
		) {
			return new WP_Error( 'github_zip_unsafe', 'The release archive contains an unsafe path.' );
		}

		if ( 'wp-seed-events' !== strtok( $entry, '/' ) ) {
			return new WP_Error( 'github_zip_root_invalid', 'The release archive root is invalid.' );
		}

		if ( 'wp-seed-events/wp-seed-events.php' === $entry ) {
			$has_main = true;
		}
	}

	if ( ! $has_main || ! is_string( $main_source ) || ! preg_match( '/^[ \t\/*#@]*Version:\s*(.+)$/mi', $main_source, $matches ) ) {
		return new WP_Error( 'github_zip_plugin_invalid', 'The release archive has no valid plugin file.' );
	}

	return trim( $matches[1] ) === $expected_version
		? true
		: new WP_Error( 'github_zip_version_mismatch', 'The release archive version does not match its tag.' );
}

function wp_seed_events_github_updater_validate_archive( $filename, $expected_version ) {
	if ( ! class_exists( 'ZipArchive' ) ) {
		return new WP_Error( 'github_zip_support_missing', 'ZIP validation is unavailable on this server.' );
	}

	$zip = new ZipArchive();

	if ( true !== $zip->open( $filename, ZipArchive::RDONLY ) ) {
		return new WP_Error( 'github_zip_invalid', 'The release archive is not a valid ZIP file.' );
	}

	$entries = array();

	for ( $index = 0; $index < $zip->numFiles; $index++ ) {
		$name = $zip->getNameIndex( $index );

		if ( false !== $name ) {
			$entries[] = $name;
		}
	}

	$main_source = $zip->getFromName( 'wp-seed-events/wp-seed-events.php' );
	$zip->close();

	return wp_seed_events_github_updater_validate_archive_manifest( $entries, $main_source, $expected_version );
}

function wp_seed_events_github_updater_download_release( $release ) {
	$checksum = wp_seed_events_github_updater_request_checksum( $release );

	if ( is_wp_error( $checksum ) ) {
		return $checksum;
	}

	$filename = wp_tempnam( $release['package_name'] );

	if ( ! $filename ) {
		return new WP_Error( 'github_download_temp_failed', 'The update temporary file could not be created.' );
	}

	$response = wp_safe_remote_get(
		$release['package_url'],
		array(
			'timeout'     => 30,
			'redirection' => 3,
			'stream'      => true,
			'filename'    => $filename,
			'headers'     => wp_seed_events_github_updater_headers( 'application/octet-stream' ),
		)
	);

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		wp_delete_file( $filename );
		return new WP_Error( 'github_download_failed', 'The release asset could not be downloaded.' );
	}

	$content_type = strtolower( trim( strtok( (string) wp_remote_retrieve_header( $response, 'content-type' ), ';' ) ) );
	$allowed      = array( '', 'application/zip', 'application/x-zip-compressed', 'application/octet-stream' );

	if ( ! in_array( $content_type, $allowed, true ) || ! is_file( $filename ) || 0 === filesize( $filename ) ) {
		wp_delete_file( $filename );
		return new WP_Error( 'github_download_invalid', 'The release asset response is invalid.' );
	}

	if ( 0 < $release['package_size'] && (int) filesize( $filename ) !== (int) $release['package_size'] ) {
		wp_delete_file( $filename );
		return new WP_Error( 'github_download_size_mismatch', 'The release asset size does not match GitHub metadata.' );
	}

	if ( ! hash_equals( $checksum, strtolower( hash_file( 'sha256', $filename ) ) ) ) {
		wp_delete_file( $filename );
		return new WP_Error( 'github_download_checksum_mismatch', 'The release asset checksum is invalid.' );
	}

	$validated = wp_seed_events_github_updater_validate_archive( $filename, $release['version'] );

	if ( is_wp_error( $validated ) ) {
		wp_delete_file( $filename );
		return $validated;
	}

	return $filename;
}

function wp_seed_events_github_updater_is_our_upgrade( $hook_extra ) {
	if ( ! is_array( $hook_extra ) ) {
		return false;
	}

	$plugin = wp_seed_events_github_updater_plugin_basename();

	if ( isset( $hook_extra['plugin'] ) && $plugin === $hook_extra['plugin'] ) {
		return true;
	}

	return isset( $hook_extra['plugins'] )
		&& in_array( $plugin, (array) $hook_extra['plugins'], true );
}

function wp_seed_events_github_updater_pre_download( $reply, $package, $upgrader, $hook_extra ) {
	if ( false !== $reply || ! wp_seed_events_github_updater_is_our_upgrade( $hook_extra ) ) {
		return $reply;
	}

	$release = wp_seed_events_github_updater_available_release();

	if ( is_wp_error( $release ) ) {
		return $release;
	}

	if ( array() === $release || ! hash_equals( $release['package_url'], (string) $package ) ) {
		return new WP_Error( 'github_package_unexpected', 'The requested package is not the selected WP Seed Events release.' );
	}

	return wp_seed_events_github_updater_download_release( $release );
}

function wp_seed_events_github_updater_source_selection( $source, $remote_source, $upgrader, $hook_extra ) {
	if ( ! wp_seed_events_github_updater_is_our_upgrade( $hook_extra ) ) {
		return $source;
	}

	$source = untrailingslashit( (string) $source );

	if ( 'wp-seed-events' !== basename( $source ) ) {
		return new WP_Error( 'github_source_root_invalid', 'The extracted plugin root is invalid.' );
	}

	$plugin_file = $source . '/wp-seed-events.php';
	$release     = wp_seed_events_github_updater_available_release();

	if ( ! is_file( $plugin_file ) || is_wp_error( $release ) || array() === $release ) {
		return new WP_Error( 'github_source_invalid', 'The extracted plugin is incomplete.' );
	}

	$source_code = file_get_contents( $plugin_file );

	if ( false === $source_code || ! preg_match( '/^[ \t\/*#@]*Version:\s*(.+)$/mi', $source_code, $matches ) || trim( $matches[1] ) !== $release['version'] ) {
		return new WP_Error( 'github_source_version_mismatch', 'The extracted plugin version is invalid.' );
	}

	return trailingslashit( $source );
}

function wp_seed_events_github_updater_process_complete( $upgrader, $hook_extra ) {
	if ( wp_seed_events_github_updater_is_our_upgrade( $hook_extra ) ) {
		wp_seed_events_github_updater_clear_cache();
	}
}

function wp_seed_events_github_updater_admin_menu() {
	if ( is_multisite() ) {
		return;
	}

	add_submenu_page(
		'wp-seed-events-admin',
		'Mises à jour',
		'Mises à jour',
		'manage_options',
		'wp-seed-events-updates',
		'wp_seed_events_github_updater_render_settings'
	);
}

function wp_seed_events_github_updater_network_admin_menu() {
	add_submenu_page(
		'settings.php',
		'WP Seed Events — Mises à jour',
		'WP Seed Events',
		'manage_network_plugins',
		'wp-seed-events-updates',
		'wp_seed_events_github_updater_render_settings'
	);
}

function wp_seed_events_github_updater_settings_capability() {
	return is_multisite() ? 'manage_network_plugins' : 'manage_options';
}

function wp_seed_events_github_updater_render_settings() {
	if ( ! current_user_can( wp_seed_events_github_updater_settings_capability() ) ) {
		wp_die( esc_html__( 'Vous n’avez pas les droits suffisants pour gérer ces mises à jour.', 'wp-seed-events' ) );
	}
	?>
	<div class="wrap">
		<h1>WP Seed Events — Mises à jour</h1>
		<p>Les versions stables officielles sont recherchées sur GitHub. Une installation en préversion suit automatiquement les préversions plus récentes ; une installation stable exige un accord explicite.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="wp_seed_events_save_update_settings" />
			<?php wp_nonce_field( 'wp_seed_events_save_update_settings', 'wp_seed_events_update_settings_nonce' ); ?>
			<label>
				<input type="checkbox" name="wp_seed_events_allow_prerelease_updates" value="1" <?php checked( wp_seed_events_github_updater_prereleases_enabled() ); ?> />
				Autoriser les préversions pour WP Seed Events
			</label>
			<p class="description">Sur une version stable, activez ce canal uniquement pour tester volontairement les versions alpha, bêta ou RC. Les mises à jour restent manuelles.</p>
			<?php submit_button( 'Enregistrer et actualiser les mises à jour' ); ?>
		</form>
	</div>
	<?php
}

function wp_seed_events_github_updater_save_settings() {
	if ( ! current_user_can( wp_seed_events_github_updater_settings_capability() ) ) {
		wp_die( esc_html__( 'Vous n’avez pas les droits suffisants pour gérer ces mises à jour.', 'wp-seed-events' ) );
	}

	check_admin_referer( 'wp_seed_events_save_update_settings', 'wp_seed_events_update_settings_nonce' );
	$enabled = ! empty( $_POST['wp_seed_events_allow_prerelease_updates'] ) ? '1' : '0';
	update_site_option( wp_seed_events_github_updater_prerelease_option_name(), $enabled );
	wp_seed_events_github_updater_clear_cache();

	$url = is_multisite()
		? network_admin_url( 'settings.php?page=wp-seed-events-updates&updated=1' )
		: admin_url( 'admin.php?page=wp-seed-events-updates&updated=1' );
	wp_safe_redirect( $url );
	exit;
}
