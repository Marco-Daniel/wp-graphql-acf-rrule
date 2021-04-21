<?php

/**
 * Plugin Name: ACF RRule Field WPGraphQL Extension
 * Plugin URI: https://mddd.nl
 * Description: Add ACF rrule field to WPGraphQL.
 * Author: M.D. Leguijt
 * Author URI: https://mddd.nl
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
  exit;
}

add_filter('wpgraphql_acf_supported_fields', function($supported_fields) {
	$supported_fields[] = 'rrule';

	return $supported_fields;
});

add_action( 'graphql_register_types', function() {

	register_graphql_scalar('DateTime', [
		'description' => __('The `DateTime` scalar type represents time data, represented as an ISO-8601 encoded UTC date string.'),
		'serialize' => function($value) {
			if (! $value instanceof DateTime) {
				throw new Error('Value is not a DateTime object.');
			}

			return $value->format(DateTime::ATOM);
		},
		'parseValue' => function($value) {
			return DateTime::createFromFormat(DateTime::ATOM, $value) ?: null;
		},
		'parseLiteral' => function($valueNode, array $variables = null) {
			if ( ! $valueNode instanceof GraphQLLanguageASTStringValueNode ) {
				throw new Error( 'Query error: Can only parse strings got: ' . $valueNode->kind, [$valueNode] );
			}

			return DateTime::createFromFormat(DateTime::ATOM, $valueNode->value) ?: null;
		}
	]);

	register_graphql_object_type('Rrule', [
		'description' => __('ACF Recurring rule field'),
		'fields' => [
			'rrule' => [
				'type' => 'String',
				'description' => __('Complete rule as a string'),
			],
			'start_date' => [
				'type' => 'String',
				'description' => __('Start date'),
			],
			'start_time' => [
				'type' => 'String',
				'description' => __('Start time'),
			],
			'frequency' => [
				'type' => 'String',
				'description' => __('Frequency'),
			],
			'interval' => [
				'type' => 'Number',
				'description' => __('Interval'),
			],
			'weekdays' => [
				'type' => ['list_of' => 'String'],
				'description' => __('Days of the week'),
			],
			'monthdays' => [
				'type' => ['list_of' => 'Number'],
				'description' => __('Days of the month'),
			],
			'months' => [
				'type' => ['list_of' => 'Number'],
				'description' => __('Month of the year'),
			],
			'monthly_by' => [
				'type' => 'String',
				'description' => __('How the rule is applied on monthly frequency'),
			],
			'setpos' => [
				'type' => 'Number',
				'description' => __('Position within month'),
			],
			'setpos_option' => [
				'type' => 'String',
				'description' => __('Day of the week for monthly frequency'),
			],
			'end_type' => [
				'type' => 'String',
				'description' => __('How to end'),
			],
			'end_date' => [
				'type' => 'String',
				'description' => __('Last occurrence'),
			],
			'occurence_count' => [
				'type' => 'Number',
				'description' => __('How many times does this repeat'),
			],
			'dates_collection' => [
				'type' => ['list_of' => 'DateTime'],
				'description' => __('List of all occurrences'),
			],
			'text' => [
				'type' => 'String',
				'description' => __('Human readable explanation of rule'),
			],
		],
	]);
});

add_filter( 'wpgraphql_acf_register_graphql_field', function($field_config, $type_name, $field_name, $config) {
	$acf_field = isset( $config['acf_field'] ) ? $config['acf_field'] : null;
	$acf_type  = isset( $acf_field['type'] ) ? $acf_field['type'] : null;

	if( !$acf_field ) {
		return $field_config;
	} 

	// ignore all other field types
	if( $acf_type !== 'rrule' ) {
			return $field_config;
	}

	// define data type
	$field_config['type'] = 'Rrule';

	// add resolver
	$field_config['resolve'] = function( $root ) use ( $acf_field ) {
		// when field is used in WP_Post and is top-level field (not nested in repeater, flexible content etc.)
		if( $root->ID ) {
			$value = get_field( $acf_field['key'], $root->ID, false );

		// when field is used in WP_Post and is nested in repeater, flexible content etc. ...
		} elseif( array_key_exists( $acf_field['key'], $root ) ) {
			$value = $root[$acf_field['key']];
		} 

		return !empty( $value ) ? $value : null;
	};

	return $field_config;
}, 10, 4 );