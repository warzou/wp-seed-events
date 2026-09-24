<?php
/**
 * Public event sharing for WP Seed Events.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

function wp_seed_events_event_share_data( $event ) {
	if ( ! is_array( $event ) ) {
		return array();
	}

	$title = trim( wp_strip_all_tags( (string) ( $event['title'] ?? '' ) ) );
	$url   = esc_url_raw( (string) ( $event['url'] ?? '' ) );

	if ( '' === $title || '' === $url ) {
		return array();
	}

	$text = sprintf(
		/* translators: %s: event title. */
		__( 'Découvrez cet événement : %s', 'wp-seed-events' ),
		$title
	);

	return array(
		'title'     => $title,
		'text'      => $text,
		'url'       => $url,
		'email_url' => 'mailto:?subject=' . rawurlencode( $title ) . '&body=' . rawurlencode(
			sprintf(
				/* translators: 1: event title, 2: canonical event URL. */
				__( "Je vous partage cet événement :\r\n\r\n%1\$s\r\n%2\$s", 'wp-seed-events' ),
				$title,
				$url
			)
		),
	);
}

function wp_seed_events_share_text_option( $value, $default ) {
	$value = is_scalar( $value ) ? trim( wp_strip_all_tags( (string) $value, true ) ) : '';

	return '' !== $value ? $value : $default;
}

function wp_seed_events_share_display_mode( $value ) {
	$value = sanitize_key( is_scalar( $value ) ? (string) $value : '' );

	return in_array( $value, array( 'text_icon', 'text', 'icon' ), true ) ? $value : 'text_icon';
}

function wp_seed_events_share_action_order_key( $value ) {
	$value = sanitize_key( is_scalar( $value ) ? (string) $value : '' );
	$valid = array(
		'share_copy_email',
		'share_email_copy',
		'copy_share_email',
		'copy_email_share',
		'email_share_copy',
		'email_copy_share',
	);

	return in_array( $value, $valid, true ) ? $value : 'share_copy_email';
}

function wp_seed_events_share_action_order( $value ) {
	return explode( '_', wp_seed_events_share_action_order_key( $value ) );
}

function wp_seed_events_share_icon( $value ) {
	$value = is_scalar( $value ) ? trim( (string) $value ) : '';
	$value = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$value = wp_strip_all_tags( $value );

	return $value;
}

function wp_seed_events_render_share_action_content( $label, $icon, $display_mode ) {
	$label        = wp_seed_events_share_text_option( $label, __( 'Action', 'wp-seed-events' ) );
	$display_mode = wp_seed_events_share_display_mode( $display_mode );
	$has_icon     = '' !== $icon;
	$show_label   = 'icon' !== $display_mode || ! $has_icon;
	$html         = '';

	if ( $show_label ) {
		$html .= '<span class="wp-seed-event-share__label" data-wp-seed-event-share-action-label>' . esc_html( $label ) . '</span>';
	}

	return $html;
}

function wp_seed_events_share_action_attributes( $action, $icon, $display_mode, $use_divi_button, $icon_placement = 'right', $icon_on_hover = 'on' ) {
	$has_rendered_icon = 'text' !== $display_mode && '' !== $icon;
	$icon_placement    = 'left' === $icon_placement ? 'left' : 'right';
	$icon_on_hover     = 'off' === $icon_on_hover ? 'off' : 'on';
	$classes = array(
		'wp-seed-event-share__action',
		'wp-seed-event-share__action--' . sanitize_html_class( $action ),
		$has_rendered_icon ? 'has-explicit-icon' : 'has-no-rendered-icon',
	);

	if ( $use_divi_button ) {
		$classes[] = 'et_pb_button';
		if ( $has_rendered_icon ) {
			$classes[] = 'et_pb_custom_button_icon';
			$classes[] = 'has-icon-' . $icon_placement;
			$classes[] = 'is-icon-hover-' . $icon_on_hover;
		}
	}

	return array(
		'class'     => implode( ' ', $classes ),
		'data-icon' => $has_rendered_icon ? $icon : '',
		'data-icon-placement' => $has_rendered_icon ? $icon_placement : '',
	);
}

/**
 * Render the shared, builder-agnostic event action bar.
 *
 * The historical function name is kept because public templates and saved Divi
 * instances already call it. Legacy dropdown-only options remain storage-only.
 */
function wp_seed_events_render_event_share_menu( $event, $options = array() ) {
	$share = wp_seed_events_event_share_data( $event );

	if ( array() === $share ) {
		return '';
	}

	$options = wp_parse_args(
		is_array( $options ) ? $options : array(),
		array(
			'display_mode'     => 'text_icon',
			'action_order'     => 'share_copy_email',
			'label'            => __( 'Partager', 'wp-seed-events' ),
			'show_share'       => true,
			'share_icon'       => '',
			'share_icon_placement' => 'right',
			'share_icon_on_hover'  => 'on',
			'show_copy'        => true,
			'copy_label'       => __( 'Copier le lien', 'wp-seed-events' ),
			'copy_icon'        => '',
			'copy_icon_placement' => 'right',
			'copy_icon_on_hover'  => 'on',
			'show_email'       => true,
			'email_label'      => __( 'Par email', 'wp-seed-events' ),
			'email_icon'       => '',
			'email_icon_placement' => 'right',
			'email_icon_on_hover'  => 'on',
			'use_divi_button'  => false,
		)
	);
	$display_mode     = wp_seed_events_share_display_mode( $options['display_mode'] );
	$action_order     = wp_seed_events_share_action_order( $options['action_order'] );
	$label            = wp_seed_events_share_text_option( $options['label'], __( 'Partager', 'wp-seed-events' ) );
	$show_share       = wp_seed_events_public_boolean_option( $options['show_share'], true );
	$share_icon       = wp_seed_events_share_icon( $options['share_icon'] );
	$share_icon_placement = 'left' === sanitize_key( (string) $options['share_icon_placement'] ) ? 'left' : 'right';
	$share_icon_on_hover  = 'off' === sanitize_key( (string) $options['share_icon_on_hover'] ) ? 'off' : 'on';
	$show_copy        = wp_seed_events_public_boolean_option( $options['show_copy'], true );
	$copy_label       = wp_seed_events_share_text_option( $options['copy_label'], __( 'Copier le lien', 'wp-seed-events' ) );
	$copy_icon        = wp_seed_events_share_icon( $options['copy_icon'] );
	$copy_icon_placement = 'left' === sanitize_key( (string) $options['copy_icon_placement'] ) ? 'left' : 'right';
	$copy_icon_on_hover  = 'off' === sanitize_key( (string) $options['copy_icon_on_hover'] ) ? 'off' : 'on';
	$show_email       = wp_seed_events_public_boolean_option( $options['show_email'], true );
	$email_label      = wp_seed_events_share_text_option( $options['email_label'], __( 'Par email', 'wp-seed-events' ) );
	$email_icon       = wp_seed_events_share_icon( $options['email_icon'] );
	$email_icon_placement = 'left' === sanitize_key( (string) $options['email_icon_placement'] ) ? 'left' : 'right';
	$email_icon_on_hover  = 'off' === sanitize_key( (string) $options['email_icon_on_hover'] ) ? 'off' : 'on';
	$use_divi_button  = wp_seed_events_public_boolean_option( $options['use_divi_button'], false );
	$share_attrs      = wp_seed_events_share_action_attributes( 'share', $share_icon, $display_mode, $use_divi_button, $share_icon_placement, $share_icon_on_hover );
	$copy_attrs       = wp_seed_events_share_action_attributes( 'copy', $copy_icon, $display_mode, $use_divi_button, $copy_icon_placement, $copy_icon_on_hover );
	$email_attrs      = wp_seed_events_share_action_attributes( 'email', $email_icon, $display_mode, $use_divi_button, $email_icon_placement, $email_icon_on_hover );

	if ( ! $show_share && ! $show_copy && ! $show_email ) {
		return '';
	}

	ob_start();
	?>
	<div class="wp-seed-event-share is-display-<?php echo esc_attr( $display_mode ); ?>" data-wp-seed-event-share data-display-mode="<?php echo esc_attr( $display_mode ); ?>">
		<div class="wp-seed-event-share__actions" role="group" aria-label="<?php echo esc_attr__( 'Partager cet événement', 'wp-seed-events' ); ?>">
			<?php foreach ( $action_order as $action ) : ?>
				<?php if ( 'share' === $action && $show_share ) : ?>
					<button type="button" class="<?php echo esc_attr( $share_attrs['class'] ); ?>"<?php echo '' !== $share_attrs['data-icon'] ? ' data-icon="' . esc_attr( $share_attrs['data-icon'] ) . '" data-icon-placement="' . esc_attr( $share_attrs['data-icon-placement'] ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo esc_attr( $label ); ?>" data-wp-seed-event-share-native data-share-title="<?php echo esc_attr( $share['title'] ); ?>" data-share-text="<?php echo esc_attr( $share['text'] ); ?>" data-share-url="<?php echo esc_url( $share['url'] ); ?>"><?php echo wp_seed_events_render_share_action_content( $label, $share_icon, $display_mode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
				<?php elseif ( 'copy' === $action && $show_copy ) : ?>
					<button type="button" class="<?php echo esc_attr( $copy_attrs['class'] ); ?>"<?php echo '' !== $copy_attrs['data-icon'] ? ' data-icon="' . esc_attr( $copy_attrs['data-icon'] ) . '" data-icon-placement="' . esc_attr( $copy_attrs['data-icon-placement'] ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo esc_attr( $copy_label ); ?>" data-wp-seed-event-share-copy data-share-url="<?php echo esc_url( $share['url'] ); ?>"><?php echo wp_seed_events_render_share_action_content( $copy_label, $copy_icon, $display_mode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
				<?php elseif ( 'email' === $action && $show_email ) : ?>
					<a class="<?php echo esc_attr( $email_attrs['class'] ); ?>"<?php echo '' !== $email_attrs['data-icon'] ? ' data-icon="' . esc_attr( $email_attrs['data-icon'] ) . '" data-icon-placement="' . esc_attr( $email_attrs['data-icon-placement'] ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo esc_attr( $email_label ); ?>" href="<?php echo esc_url( $share['email_url'] ); ?>"><?php echo wp_seed_events_render_share_action_content( $email_label, $email_icon, $display_mode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
		<p class="wp-seed-event-share__feedback screen-reader-text" role="status" aria-live="polite" aria-atomic="true" data-wp-seed-event-share-feedback></p>
	</div>
	<?php

	return trim( ob_get_clean() );
}
