<?php
/**
 * JSON Schema normalization helpers.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Registry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalizes Abilities API schema fragments into arrays WordPress can validate.
 */
final class Schema_Normalizer {
	/**
	 * Normalizes a schema payload.
	 *
	 * @param mixed  $schema Raw schema.
	 * @param string $default_type Default schema type.
	 * @return array<string,mixed>
	 */
	public function normalize( $schema, $default_type = 'object' ) {
		if ( is_string( $schema ) ) {
			$schema = array( 'type' => sanitize_key( $schema ) );
		}

		$schema = is_array( $schema ) ? $schema : array();
		$type   = $this->normalize_type(
			isset( $schema['type'] ) ? $schema['type'] : '',
			$default_type
		);

		if ( '' === $type ) {
			$type = 'object';
		}

		$schema['type'] = $type;

		if ( $this->allows_type( $type, 'object' ) ) {
			$properties = isset( $schema['properties'] ) && is_array( $schema['properties'] )
				? $schema['properties']
				: array();

			foreach ( $properties as $property_key => $property_schema ) {
				$property_key = sanitize_key( (string) $property_key );
				if ( '' === $property_key ) {
					continue;
				}
				$properties[ $property_key ] = $this->normalize( $property_schema, '' );
			}

			if ( empty( $properties ) ) {
				unset( $schema['properties'] );
			} else {
				$schema['properties'] = $properties;
			}

			if ( isset( $schema['additionalProperties'] ) && is_array( $schema['additionalProperties'] ) ) {
				$schema['additionalProperties'] = $this->normalize( $schema['additionalProperties'], '' );
			}
		}

		if ( $this->allows_type( $type, 'array' ) && isset( $schema['items'] ) ) {
			$schema['items'] = $this->normalize( $schema['items'], '' );
		}

		return $schema;
	}

	/**
	 * Normalizes a JSON Schema type.
	 *
	 * @param mixed  $type Raw type.
	 * @param string $default_type Default type.
	 * @return string|array<int,string>
	 */
	private function normalize_type( $type, $default_type ) {
		if ( is_array( $type ) ) {
			$types = array_values(
				array_filter(
					array_map(
						static function ( $item ) {
							return sanitize_key( (string) $item );
						},
						$type
					)
				)
			);

			if ( 1 === count( $types ) ) {
				return $types[0];
			}

			return $types;
		}

		$type = sanitize_key( (string) $type );
		if ( '' === $type ) {
			$type = sanitize_key( (string) $default_type );
		}

		return $type;
	}

	/**
	 * Checks if a normalized schema type allows a target type.
	 *
	 * @param string|array<int,string> $type Normalized type.
	 * @param string                   $target Target type.
	 * @return bool
	 */
	private function allows_type( $type, $target ) {
		if ( is_array( $type ) ) {
			return in_array( $target, $type, true );
		}

		return $target === $type;
	}
}
