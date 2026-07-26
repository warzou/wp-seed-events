<?php
/**
 * Explicit, bounded lifecycle index backfill.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_SEED_EVENTS_LIFECYCLE_INDEX_BATCH_SIZE' ) ) {
	define( 'WP_SEED_EVENTS_LIFECYCLE_INDEX_BATCH_SIZE', 25 );
}

if ( ! defined( 'WP_SEED_EVENTS_LIFECYCLE_INDEX_LOCK_TTL' ) ) {
	define( 'WP_SEED_EVENTS_LIFECYCLE_INDEX_LOCK_TTL', 300 );
}

add_action( 'admin_post_wp_seed_events_run_lifecycle_index_backfill', 'wp_seed_events_handle_lifecycle_index_backfill' );

function wp_seed_events_lifecycle_index_expected_version() {
	return 2;
}

function wp_seed_events_lifecycle_index_version_option_name() {
	return 'wp_seed_events_lifecycle_index_version';
}

function wp_seed_events_lifecycle_index_progress_option_name() {
	return 'wp_seed_events_lifecycle_index_progress';
}

function wp_seed_events_lifecycle_index_lock_option_name() {
	return 'wp_seed_events_lifecycle_index_lock';
}

function wp_seed_events_lifecycle_index_batch_size() {
	return max( 1, min( 100, (int) WP_SEED_EVENTS_LIFECYCLE_INDEX_BATCH_SIZE ) );
}

function wp_seed_events_lifecycle_index_lock_ttl() {
	return max( 60, min( 1800, (int) WP_SEED_EVENTS_LIFECYCLE_INDEX_LOCK_TTL ) );
}

function wp_seed_events_lifecycle_index_post_statuses() {
	return array( 'publish', 'draft', 'private', 'future', 'pending', 'trash' );
}

/**
 * Private progress structure containing technical counters only.
 *
 * Keys: version, status, cursor_id, total, processed, errors,
 * error_ids (bounded), last_error_id and updated_at.
 */
function wp_seed_events_lifecycle_index_default_progress() {
	return array(
		'version'       => wp_seed_events_lifecycle_index_expected_version(),
		'status'        => 'pending',
		'cursor_id'       => 0,
		'total'         => 0,
		'processed'     => 0,
		'errors'        => 0,
		'error_ids'     => array(),
		'last_error_id' => 0,
		'updated_at'    => 0,
	);
}

function wp_seed_events_get_lifecycle_index_progress() {
	$stored   = get_option( wp_seed_events_lifecycle_index_progress_option_name(), array() );
	$progress = wp_parse_args( is_array( $stored ) ? $stored : array(), wp_seed_events_lifecycle_index_default_progress() );
	$statuses = array( 'pending', 'running', 'complete', 'failed' );

	$progress['version']       = absint( $progress['version'] );
	$progress['status']        = in_array( $progress['status'], $statuses, true ) ? $progress['status'] : 'pending';
	$progress['cursor_id']       = absint( $progress['cursor_id'] );
	$progress['total']         = absint( $progress['total'] );
	$progress['processed']     = absint( $progress['processed'] );
	$progress['error_ids']     = array_slice( array_values( array_unique( array_filter( array_map( 'absint', (array) $progress['error_ids'] ) ) ) ), 0, wp_seed_events_lifecycle_index_batch_size() );
	$progress['errors']        = max( absint( $progress['errors'] ), count( $progress['error_ids'] ) );
	$progress['last_error_id'] = absint( $progress['last_error_id'] );
	$progress['updated_at']    = absint( $progress['updated_at'] );

	return $progress;
}

function wp_seed_events_is_lifecycle_index_ready() {
	$expected_version = wp_seed_events_lifecycle_index_expected_version();
	$stored_version   = absint( get_option( wp_seed_events_lifecycle_index_version_option_name(), 0 ) );
	$progress         = wp_seed_events_get_lifecycle_index_progress();

	return $expected_version === $stored_version
		&& $expected_version === $progress['version']
		&& 'complete' === $progress['status']
		&& 0 === $progress['errors'];
}

function wp_seed_events_lifecycle_index_estimated_total() {
	global $wpdb;

	$statuses     = wp_seed_events_lifecycle_index_post_statuses();
	$placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );
	$query        = "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = %s AND post_status IN ({$placeholders})";
	$params       = array_merge( array( 'wp_seed_event' ), $statuses );
	$prepared     = call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $query ), $params ) );

	return absint( $wpdb->get_var( $prepared ) );
}

function wp_seed_events_lifecycle_index_select_ids( $after_id, $limit ) {
	global $wpdb;

	$statuses     = wp_seed_events_lifecycle_index_post_statuses();
	$placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );
	$query        = "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status IN ({$placeholders}) AND ID > %d ORDER BY ID ASC LIMIT %d";
	$params       = array_merge( array( 'wp_seed_event' ), $statuses, array( absint( $after_id ), absint( $limit ) ) );
	$prepared          = call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $query ), $params ) );
	$wpdb->last_error = '';
	$ids               = $wpdb->get_col( $prepared );

	if ( '' !== $wpdb->last_error ) {
		return new WP_Error( 'lifecycle_index_query_failed', 'Lifecycle index selection failed.' );
	}

	return is_array( $ids ) ? array_values( array_filter( array_map( 'absint', $ids ) ) ) : array();
}

function wp_seed_events_lifecycle_index_lock_is_active() {
	$lock = get_option( wp_seed_events_lifecycle_index_lock_option_name(), array() );

	return is_array( $lock ) && ! empty( $lock['expires_at'] ) && absint( $lock['expires_at'] ) > time();
}

function wp_seed_events_acquire_lifecycle_index_lock() {
	$option_name = wp_seed_events_lifecycle_index_lock_option_name();
	$lock        = get_option( $option_name, array() );
	$now         = time();

	if ( is_array( $lock ) && ! empty( $lock['expires_at'] ) && absint( $lock['expires_at'] ) > $now ) {
		return new WP_Error( 'lifecycle_index_locked', 'Lifecycle index backfill is already running.' );
	}

	// An expired lock is reclaimed only when a protected action starts.
	if ( false !== get_option( $option_name, false ) ) {
		delete_option( $option_name );
	}

	$token = wp_generate_uuid4();
	$value = array(
		'token'       => $token,
		'acquired_at' => $now,
		'expires_at'  => $now + wp_seed_events_lifecycle_index_lock_ttl(),
	);

	if ( ! add_option( $option_name, $value, '', false ) ) {
		return new WP_Error( 'lifecycle_index_locked', 'Lifecycle index backfill lock could not be acquired.' );
	}

	return $token;
}

function wp_seed_events_release_lifecycle_index_lock( $token ) {
	$option_name = wp_seed_events_lifecycle_index_lock_option_name();
	$lock        = get_option( $option_name, array() );

	if ( is_array( $lock ) && isset( $lock['token'] ) && hash_equals( (string) $lock['token'], (string) $token ) ) {
		delete_option( $option_name );
	}
}

function wp_seed_events_reset_lifecycle_index_backfill() {
	$progress               = wp_seed_events_lifecycle_index_default_progress();
	$progress['total']      = wp_seed_events_lifecycle_index_estimated_total();
	$progress['updated_at'] = time();

	delete_option( wp_seed_events_lifecycle_index_version_option_name() );
	update_option( wp_seed_events_lifecycle_index_progress_option_name(), $progress, false );

	return $progress;
}

function wp_seed_events_lifecycle_index_process_event( $event_id ) {
	$event = get_post( $event_id );

	if ( ! $event instanceof WP_Post || 'wp_seed_event' !== $event->post_type || ! in_array( $event->post_status, wp_seed_events_lifecycle_index_post_statuses(), true ) ) {
		return 'skipped';
	}

	return false === wp_seed_events_update_lifecycle_index( $event_id ) ? 'failed' : 'processed';
}

function wp_seed_events_run_lifecycle_index_backfill_batch( $restart = false ) {
	$token = wp_seed_events_acquire_lifecycle_index_lock();

	if ( is_wp_error( $token ) ) {
		return $token;
	}

	try {
		$progress = $restart ? wp_seed_events_reset_lifecycle_index_backfill() : wp_seed_events_get_lifecycle_index_progress();

		if ( ! $restart && wp_seed_events_is_lifecycle_index_ready() ) {
			return $progress;
		}

		if ( wp_seed_events_lifecycle_index_expected_version() !== $progress['version'] ) {
			$progress = wp_seed_events_reset_lifecycle_index_backfill();
		}

		delete_option( wp_seed_events_lifecycle_index_version_option_name() );

		$progress['status']     = 'running';
		$progress['total']      = max( $progress['processed'], wp_seed_events_lifecycle_index_estimated_total() );
		$progress['updated_at'] = time();
		update_option( wp_seed_events_lifecycle_index_progress_option_name(), $progress, false );

		if ( array() !== $progress['error_ids'] ) {
			$remaining_errors = array();

			foreach ( array_slice( $progress['error_ids'], 0, wp_seed_events_lifecycle_index_batch_size() ) as $event_id ) {
				try {
					$result = wp_seed_events_lifecycle_index_process_event( $event_id );

					if ( 'failed' === $result ) {
						$remaining_errors[] = $event_id;
					} elseif ( 'processed' === $result ) {
						++$progress['processed'];
					}
				} catch ( Throwable $error ) {
					$remaining_errors[] = $event_id;
				}
			}

			$progress['error_ids']     = $remaining_errors;
			$progress['errors']        = count( $remaining_errors );
			$progress['last_error_id'] = array() === $remaining_errors ? 0 : (int) end( $remaining_errors );
			$progress['status']        = array() === $remaining_errors ? 'running' : 'failed';
			$progress['updated_at']    = time();
			update_option( wp_seed_events_lifecycle_index_progress_option_name(), $progress, false );

			return array() === $remaining_errors
				? $progress
				: new WP_Error( 'lifecycle_index_failed', 'One or more lifecycle index entries could not be rebuilt.' );
		}

		$batch_size = wp_seed_events_lifecycle_index_batch_size();
		$selected   = wp_seed_events_lifecycle_index_select_ids( $progress['cursor_id'], $batch_size + 1 );

		if ( is_wp_error( $selected ) ) {
			throw new RuntimeException( 'Lifecycle index selection failed.' );
		}

		$has_more   = count( $selected ) > $batch_size;
		$event_ids  = array_slice( $selected, 0, $batch_size );
		$error_ids  = array();

		foreach ( $event_ids as $event_id ) {
			try {
				$result = wp_seed_events_lifecycle_index_process_event( $event_id );

				if ( 'failed' === $result ) {
					$error_ids[] = $event_id;
				} elseif ( 'processed' === $result ) {
					++$progress['processed'];
				}
			} catch ( Throwable $error ) {
				$error_ids[] = $event_id;
			}

			$progress['cursor_id'] = max( $progress['cursor_id'], $event_id );
		}

		if ( ! $has_more && array() === $error_ids ) {
			$remaining = wp_seed_events_lifecycle_index_select_ids( $progress['cursor_id'], 1 );

			if ( is_wp_error( $remaining ) ) {
				throw new RuntimeException( 'Lifecycle index completion check failed.' );
			}

			$has_more = array() !== $remaining;
		}

		$progress['error_ids']     = array_slice( array_values( array_unique( $error_ids ) ), 0, $batch_size );
		$progress['errors']        = count( $progress['error_ids'] );
		$progress['last_error_id'] = array() === $progress['error_ids'] ? 0 : (int) end( $progress['error_ids'] );
		$progress['updated_at']    = time();

		if ( 0 < $progress['errors'] ) {
			$progress['status'] = 'failed';
		} elseif ( $has_more ) {
			$progress['status'] = 'running';
		} else {
			$progress['status'] = 'complete';
		}

		update_option( wp_seed_events_lifecycle_index_progress_option_name(), $progress, false );

		if ( 'complete' === $progress['status'] ) {
			update_option( wp_seed_events_lifecycle_index_version_option_name(), wp_seed_events_lifecycle_index_expected_version(), false );
		}

		return 0 < $progress['errors']
			? new WP_Error( 'lifecycle_index_failed', 'One or more lifecycle index entries could not be rebuilt.' )
			: $progress;
	} catch ( Throwable $error ) {
		$progress                  = isset( $progress ) && is_array( $progress ) ? $progress : wp_seed_events_lifecycle_index_default_progress();
		$progress['status']        = 'failed';
		$progress['errors']        = max( 1, absint( $progress['errors'] ) );
		$progress['last_error_id'] = absint( $progress['last_error_id'] );
		$progress['updated_at']    = time();
		update_option( wp_seed_events_lifecycle_index_progress_option_name(), $progress, false );

		return new WP_Error( 'lifecycle_index_failed', 'Lifecycle index backfill failed.' );
	} finally {
		wp_seed_events_release_lifecycle_index_lock( $token );
	}
}

function wp_seed_events_handle_lifecycle_index_backfill() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Vous n’avez pas les droits suffisants pour lancer cette opération.', 'wp-seed-events' ) );
	}

	check_admin_referer( 'wp_seed_events_run_lifecycle_index_backfill', 'wp_seed_events_lifecycle_index_nonce' );

	$operation = isset( $_POST['wp_seed_events_lifecycle_index_operation'] ) && is_scalar( $_POST['wp_seed_events_lifecycle_index_operation'] )
		? sanitize_key( wp_unslash( $_POST['wp_seed_events_lifecycle_index_operation'] ) )
		: '';

	if ( ! in_array( $operation, array( 'start', 'continue', 'resume', 'restart' ), true ) ) {
		wp_safe_redirect( wp_seed_events_lifecycle_index_settings_url( 'invalid' ) );
		exit;
	}

	$result  = wp_seed_events_run_lifecycle_index_backfill_batch( in_array( $operation, array( 'start', 'restart' ), true ) );
	$message = 'processed';

	if ( is_wp_error( $result ) ) {
		$message = 'lifecycle_index_locked' === $result->get_error_code() ? 'locked' : 'failed';
	} elseif ( 'complete' === $result['status'] && wp_seed_events_is_lifecycle_index_ready() ) {
		$message = 'complete';
	}

	wp_safe_redirect( wp_seed_events_lifecycle_index_settings_url( $message ) );
	exit;
}

function wp_seed_events_lifecycle_index_settings_url( $message = '' ) {
	$args = array( 'page' => 'wp-seed-events-admin' );

	if ( '' !== $message ) {
		$args['lifecycle_index'] = sanitize_key( $message );
	}

	return add_query_arg( $args, admin_url( 'admin.php' ) );
}

function wp_seed_events_render_lifecycle_index_backfill_panel() {
	$stored_progress = get_option( wp_seed_events_lifecycle_index_progress_option_name(), false );
	$has_progress    = is_array( $stored_progress );
	$progress        = wp_seed_events_get_lifecycle_index_progress();
	$ready           = wp_seed_events_is_lifecycle_index_ready();
	$locked          = wp_seed_events_lifecycle_index_lock_is_active();
	$message         = isset( $_GET['lifecycle_index'] ) && is_scalar( $_GET['lifecycle_index'] )
		? sanitize_key( wp_unslash( $_GET['lifecycle_index'] ) )
		: '';
	$status_labels   = array(
		'pending'  => 'Non démarré',
		'running'  => 'En cours',
		'complete' => 'Complet',
		'failed'   => 'À reprendre',
	);
	$status_label = $has_progress ? $status_labels[ $progress['status'] ] : $status_labels['pending'];

	if ( 'complete' === $progress['status'] && ! $ready ) {
		$status_label = 'À finaliser';
	}

	$operation    = 'start';
	$button_label = 'Démarrer';

	if ( $ready ) {
		$operation    = 'restart';
		$button_label = 'Recalculer';
	} elseif ( 'failed' === $progress['status'] ) {
		$operation    = 'resume';
		$button_label = 'Reprendre';
	} elseif ( $has_progress ) {
		$operation    = 'continue';
		$button_label = 'Continuer';
	}
	?>
	<hr />
	<h2>Index des dates</h2>
	<p>Cette opération reconstruit progressivement l’index des dates, du lifecycle, des types et du tri des collections. Elle traite au maximum <?php echo esc_html( (string) wp_seed_events_lifecycle_index_batch_size() ); ?> événements par action.</p>

	<?php if ( 'complete' === $message ) : ?>
		<div class="notice notice-success inline"><p>Index historique terminé.</p></div>
	<?php elseif ( 'processed' === $message ) : ?>
		<div class="notice notice-success inline"><p>Un lot a été traité.</p></div>
	<?php elseif ( 'locked' === $message ) : ?>
		<div class="notice notice-warning inline"><p>Un traitement est déjà en cours. Réessayez après quelques minutes.</p></div>
	<?php elseif ( in_array( $message, array( 'failed', 'invalid' ), true ) ) : ?>
		<div class="notice notice-error inline"><p>Le traitement n’a pas pu se terminer. Vous pouvez le reprendre.</p></div>
	<?php endif; ?>

	<ul>
		<li><strong>État :</strong> <?php echo esc_html( $status_label ); ?></li>
		<li><strong>Version attendue :</strong> <?php echo esc_html( (string) wp_seed_events_lifecycle_index_expected_version() ); ?></li>
		<li><strong>Progression :</strong> <?php echo esc_html( (string) $progress['processed'] ); ?> / <?php echo esc_html( (string) $progress['total'] ); ?></li>
		<li><strong>Erreurs à reprendre :</strong> <?php echo esc_html( (string) $progress['errors'] ); ?></li>
		<?php if ( 0 < $progress['updated_at'] ) : ?>
			<li><strong>Dernière exécution :</strong> <?php echo esc_html( wp_date( 'd/m/Y H:i', $progress['updated_at'] ) ); ?></li>
		<?php endif; ?>
	</ul>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="wp_seed_events_run_lifecycle_index_backfill" />
		<input type="hidden" name="wp_seed_events_lifecycle_index_operation" value="<?php echo esc_attr( $operation ); ?>" />
		<?php wp_nonce_field( 'wp_seed_events_run_lifecycle_index_backfill', 'wp_seed_events_lifecycle_index_nonce' ); ?>
		<?php submit_button( $button_label, 'secondary', 'submit', false, $locked ? array( 'disabled' => 'disabled' ) : array() ); ?>
	</form>
	<?php
}
