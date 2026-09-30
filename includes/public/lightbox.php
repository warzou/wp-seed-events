<?php
/** Native WordPress image lightbox integration. @package WPSeedEvents */
defined( 'ABSPATH' ) || exit;

/**
 * Render a real core/image block: Core owns assets, directives, overlay and focus.
 * The plain source link remains available without JS or on older WordPress.
 */
function wp_seed_events_render_wordpress_lightbox_figure( $figure_html, $attachment_id, $gallery_id = '', $source_url = '' ) {
	$attachment_id = absint( $attachment_id );
	if ( ! $attachment_id || ! is_string( $figure_html ) || '' === trim( $figure_html ) ) {
		return $figure_html;
	}
	$source_url = esc_url( $source_url );
	$fallback = '' !== $source_url ? '<a class="wp-seed-event-visuals__source-link" href="' . $source_url . '">Voir l’image en taille originale</a>' : '';
	if ( ! function_exists( 'block_core_image_render_lightbox' ) || ! class_exists( 'WP_Block' ) ) {
		return $figure_html . $fallback;
	}
	$figure_html = str_replace( 'class="wp-seed-event-visuals__figure"', 'class="wp-block-image wp-seed-event-visuals__figure"', $figure_html );
	$block = array(
		'blockName' => 'core/image',
		'attrs' => array( 'id' => $attachment_id, 'linkDestination' => 'none', 'scale' => 'contain', 'lightbox' => array( 'enabled' => true ) ),
		'innerBlocks' => array(),
		'innerHTML' => $figure_html,
		'innerContent' => array( $figure_html ),
	);
	$instance = new WP_Block( $block, array( 'galleryId' => sanitize_key( (string) $gallery_id ) ) );
	$html = $instance->render();
	// Classic themes can render the model after wp_head; print late image styles.
	wp_enqueue_style( 'wp-block-image' );
	wp_enqueue_script( 'wp-seed-events-native-image-focus', plugins_url( 'event-visuals-lightbox.js', __FILE__ ), array(), substr( hash_file( 'sha256', __DIR__ . '/event-visuals-lightbox.js' ), 0, 12 ), true );
	add_action( 'wp_footer', 'wp_seed_events_print_native_image_style', 1 );
	return $html . ( '' !== $fallback ? '<noscript>' . $fallback . '</noscript>' : '' );
}

function wp_seed_events_print_native_image_style() {
	wp_print_styles( 'wp-block-image' );
}
