<?php
/**
 * Public API functions.
 *
 * @package MagickAIAbilities
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'magick_ai_abilities_register_category' ) ) {
	/**
	 * Registers a WordPress Abilities API category.
	 *
	 * @param string $category_id Category id.
	 * @param array  $args Category args.
	 * @return bool
	 */
	function magick_ai_abilities_register_category( $category_id, array $args = array() ) {
		return Magick_AI_Abilities\Plugin::instance()->categories()->add( $category_id, $args );
	}
}

if ( ! function_exists( 'magick_ai_abilities_register_readonly' ) ) {
	/**
	 * Registers a read-only agent-callable ability.
	 *
	 * @param string $ability_id Namespaced ability id.
	 * @param array  $definition Ability definition.
	 * @return bool
	 */
	function magick_ai_abilities_register_readonly( $ability_id, array $definition ) {
		return Magick_AI_Abilities\Plugin::instance()->abilities()->add_readonly( $ability_id, $definition );
	}
}

if ( ! function_exists( 'magick_ai_abilities_register_write_proposal' ) ) {
	/**
	 * Registers a write-like ability that returns a proposal instead of committing directly.
	 *
	 * @param string $ability_id Namespaced ability id.
	 * @param array  $definition Ability definition.
	 * @return bool
	 */
	function magick_ai_abilities_register_write_proposal( $ability_id, array $definition ) {
		return Magick_AI_Abilities\Plugin::instance()->abilities()->add_write_proposal( $ability_id, $definition );
	}
}

if ( ! function_exists( 'magick_ai_abilities_normalize_schema' ) ) {
	/**
	 * Normalizes a schema fragment for WordPress Abilities API registration.
	 *
	 * @param mixed  $schema Raw schema.
	 * @param string $default_type Default type.
	 * @return array
	 */
	function magick_ai_abilities_normalize_schema( $schema, $default_type = 'object' ) {
		$normalizer = new Magick_AI_Abilities\Registry\Schema_Normalizer();

		return $normalizer->normalize( $schema, $default_type );
	}
}

if ( ! function_exists( 'magick_ai_abilities_normalize_annotations' ) ) {
	/**
	 * Normalizes Abilities API annotations for a risk level.
	 *
	 * @param mixed  $annotations Raw annotations.
	 * @param string $risk_level Risk level.
	 * @return array
	 */
	function magick_ai_abilities_normalize_annotations( $annotations, $risk_level = 'read' ) {
		$normalizer = new Magick_AI_Abilities\Registry\Annotation_Normalizer();

		return $normalizer->normalize( $annotations, $risk_level );
	}
}

if ( ! function_exists( 'magick_ai_abilities_get_registered' ) ) {
	/**
	 * Returns normalized abilities registered through this toolkit.
	 *
	 * @return array
	 */
	function magick_ai_abilities_get_registered() {
		return Magick_AI_Abilities\Plugin::instance()->abilities()->all();
	}
}
