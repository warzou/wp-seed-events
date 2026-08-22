<?php
/**
 * Builder-agnostic lightbox adapters for public event visuals.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

/**
 * Apply the native WordPress core/image lightbox contract to a rendered figure.
 *
 * WordPress owns the interaction script, overlay, keyboard handling and focus
 * management. Returning the original figure is the compatibility fallback for
 * WordPress versions where the native lightbox API is unavailable.
 */
function wp_seed_events_render_wordpress_lightbox_figure( $figure_html, $attachment_id, $gallery_id = '' ) {
	$attachment_id = absint( $attachment_id );

	if (
		0 === $attachment_id
		|| ! is_string( $figure_html )
		|| '' === trim( $figure_html )
		|| ! function_exists( 'block_core_image_render_lightbox' )
	) {
		return $figure_html;
	}

	$block = array(
		'blockName' => 'core/image',
		'attrs'     => array(
			'id'              => $attachment_id,
			'linkDestination' => 'none',
			'lightbox'        => array( 'enabled' => true ),
		),
	);
	$instance = (object) array(
		'context' => array(
			'galleryId' => is_scalar( $gallery_id ) ? sanitize_key( (string) $gallery_id ) : '',
		),
	);

	return (string) block_core_image_render_lightbox( $figure_html, $block, $instance );
}
