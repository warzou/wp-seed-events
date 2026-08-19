<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wp_seed_events_divi_optional_title( $values, $default_title ) {
	$values = is_array( $values ) ? $values : array();
	$title  = array_key_exists( 'title', $values ) && is_scalar( $values['title'] )
		? trim( (string) $values['title'] )
		: (string) $default_title;

	if ( array_key_exists( 'show_title', $values ) ) {
		return wp_seed_events_public_boolean_option( $values['show_title'], true ) ? $title : '';
	}

	return $title;
}

function wp_seed_events_divi_scalar_style_value( $value, $field ) {
	if ( is_scalar( $value ) ) {
		return (string) $value;
	}
	if ( ! is_array( $value ) ) {
		return null;
	}
	if ( '' !== $field && is_scalar( $value[ $field ] ?? null ) ) {
		return (string) $value[ $field ];
	}
	return array_key_exists( 'value', $value )
		? wp_seed_events_divi_scalar_style_value( $value['value'], $field )
		: null;
}

function wp_seed_events_divi_resolve_style_value( $attribute, $breakpoint, $field, $fallback ) {
	$inheritance = array(
		'desktop' => array( 'desktop' ),
		'tablet'  => array( 'tablet', 'desktop' ),
		'phone'   => array( 'phone', 'tablet', 'desktop' ),
	);

	foreach ( $inheritance[ $breakpoint ] ?? array( $breakpoint, 'desktop' ) as $candidate ) {
		$breakpoint_value = is_array( $attribute ) && isset( $attribute[ $candidate ] ) ? $attribute[ $candidate ] : null;
		$value            = is_array( $breakpoint_value ) && array_key_exists( 'value', $breakpoint_value )
			? wp_seed_events_divi_scalar_style_value( $breakpoint_value['value'], $field )
			: wp_seed_events_divi_scalar_style_value( $breakpoint_value, $field );
		if ( null !== $value ) {
			return $value;
		}
	}

	$value = wp_seed_events_divi_scalar_style_value( $attribute, $field );
	return null !== $value ? $value : $fallback;
}

function wp_seed_events_divi_list_style_values( $attrs, $attr_name = 'listStyle', $defaults = array() ) {
	$defaults = wp_parse_args(
		$defaults,
		array(
			'markerType'     => 'none',
			'markerPosition' => 'outside',
			'leftIndent'     => '0px',
			'occurrenceGap'  => '0px',
			'markerColor'    => '',
		)
	);
	$advanced = isset( $attrs[ $attr_name ]['advanced'] ) && is_array( $attrs[ $attr_name ]['advanced'] )
		? $attrs[ $attr_name ]['advanced']
		: array();
	$styles = array();

	foreach ( array( 'desktop', 'tablet', 'phone' ) as $breakpoint ) {
		$marker_type = wp_seed_events_divi_resolve_style_value( $advanced['markerType'] ?? null, $breakpoint, 'markerType', $defaults['markerType'] );
		$position    = wp_seed_events_divi_resolve_style_value( $advanced['markerPosition'] ?? null, $breakpoint, 'markerPosition', $defaults['markerPosition'] );
		$indent      = wp_seed_events_divi_resolve_style_value( $advanced['leftIndent'] ?? null, $breakpoint, 'leftIndent', $defaults['leftIndent'] );
		$gap         = wp_seed_events_divi_resolve_style_value( $advanced['occurrenceGap'] ?? null, $breakpoint, 'occurrenceGap', $defaults['occurrenceGap'] );
		$color       = wp_seed_events_divi_resolve_style_value( $advanced['markerColor'] ?? null, $breakpoint, 'markerColor', $defaults['markerColor'] );

		$marker_type = in_array( $marker_type, array( 'none', 'disc', 'circle', 'square' ), true ) ? $marker_type : $defaults['markerType'];
		$position    = in_array( $position, array( 'inside', 'outside' ), true ) ? $position : $defaults['markerPosition'];
		$indent      = wp_seed_events_public_date_list_dimension_option( $indent, $defaults['leftIndent'] );
		$gap         = wp_seed_events_public_date_list_dimension_option( $gap, $defaults['occurrenceGap'] );
		$color       = wp_seed_events_public_date_list_marker_color_option( $color );

		if ( 'none' === $marker_type && in_array( $indent, array( '0px', '2.5em' ), true ) ) {
			$indent = '0px';
		}

		$styles[ $breakpoint ] = array(
			'markerType'     => $marker_type,
			'markerPosition' => $position,
			'leftIndent'     => $indent,
			'occurrenceGap'  => $gap,
			'markerColor'    => $color,
		);
	}

	return $styles;
}

function wp_seed_events_divi_list_renderer_options( $styles ) {
	$desktop = $styles['desktop'];
	return array(
		'list_marker_type'     => $desktop['markerType'],
		'list_marker_position' => $desktop['markerPosition'],
		'list_indent'          => $desktop['leftIndent'],
		'occurrence_gap'       => $desktop['occurrenceGap'],
		'marker_color'         => $desktop['markerColor'],
	);
}

function wp_seed_events_divi_apply_list_styles( $html, $styles, $list_class ) {
	if ( '' === $html || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
		return $html;
	}

	$processor = new WP_HTML_Tag_Processor( $html );
	if ( ! $processor->next_tag( array( 'class_name' => $list_class ) ) ) {
		return $html;
	}

	$processor->add_class( 'wp-seed-event-list' );
	$processor->add_class( 'has-custom-list-style' );
	$declarations = array();
	$properties   = array(
		'markerType'     => 'marker-type',
		'markerPosition' => 'marker-position',
		'leftIndent'     => 'list-indent',
		'occurrenceGap'  => 'item-gap',
		'markerColor'    => 'marker-color',
	);

	foreach ( array( 'desktop', 'tablet', 'phone' ) as $breakpoint ) {
		foreach ( $properties as $field => $property ) {
			$value = 'markerColor' === $field && '' === $styles[ $breakpoint ][ $field ]
				? 'currentColor'
				: $styles[ $breakpoint ][ $field ];
			$declarations[] = '--wp-seed-event-list-' . $property . '-' . $breakpoint . ':' . $value;
		}
	}

	$existing_style = trim( (string) $processor->get_attribute( 'style' ), " \t\n\r\0\x0B;" );
	$processor->set_attribute( 'style', ( '' !== $existing_style ? $existing_style . ';' : '' ) . implode( ';', $declarations ) );
	return $processor->get_updated_html();
}
