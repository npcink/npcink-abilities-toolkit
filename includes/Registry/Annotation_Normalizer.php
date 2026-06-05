<?php
/**
 * Ability annotation normalization helpers.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Registry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalizes Abilities API annotations from one risk level.
 */
final class Annotation_Normalizer {
	/**
	 * Normalizes readonly/destructive/idempotent annotations.
	 *
	 * @param mixed  $annotations Raw annotations.
	 * @param string $risk_level Risk level.
	 * @return array<string,mixed>
	 */
	public function normalize( $annotations, $risk_level = 'read' ) {
		$annotations = is_array( $annotations ) ? $annotations : array();
		$risk_level  = sanitize_key( (string) $risk_level );

		if ( ! in_array( $risk_level, array( 'read', 'write', 'destructive' ), true ) ) {
			$risk_level = 'read';
		}

		$is_write_like = in_array( $risk_level, array( 'write', 'destructive' ), true );

		$annotations['readonly']    = ! $is_write_like;
		$annotations['destructive'] = 'destructive' === $risk_level;
		$annotations['idempotent']  = ! $is_write_like;

		if ( array_key_exists( 'instructions', $annotations ) ) {
			$annotations['instructions'] = sanitize_text_field( (string) $annotations['instructions'] );
		}

		return $annotations;
	}
}
