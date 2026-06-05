<?php
/**
 * Public API functions.
 *
 * @package NpcinkAbilitiesToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'npcink_abilities_toolkit_emit_observability_event' ) ) {
	/**
	 * Emits a local-only observability event for optional Cloud Addon collection.
	 *
	 * This function never sends data off-site. The Cloud Addon may subscribe to
	 * npcink_abilities_toolkit_observability_event when monitoring is explicitly enabled.
	 *
	 * @param array<string,mixed> $event Event payload.
	 * @return void
	 */
	function npcink_abilities_toolkit_emit_observability_event( array $event ) {
		if ( ! function_exists( 'do_action' ) ) {
			return;
		}

		$payload = array_merge(
			array(
				'schema_version' => '2026-06-01',
				'plugin_slug'    => 'npcink-abilities-toolkit',
				'plugin_version' => defined( 'NPCINK_ABILITIES_TOOLKIT_VERSION' ) ? NPCINK_ABILITIES_TOOLKIT_VERSION : '',
				'source'         => 'local',
				'emitted_at'     => gmdate( 'c' ),
			),
			$event
		);

		do_action( 'npcink_abilities_toolkit_observability_event', $payload );
	}
}

if ( ! function_exists( 'npcink_abilities_toolkit_register_category' ) ) {
	/**
	 * Registers a WordPress Abilities API category.
	 *
	 * @param string $category_id Category id.
	 * @param array  $args Category args.
	 * @return bool
	 */
	function npcink_abilities_toolkit_register_category( $category_id, array $args = array() ) {
		return Npcink_Abilities_Toolkit\Plugin::instance()->categories()->add( $category_id, $args );
	}
}

if ( ! function_exists( 'npcink_abilities_toolkit_register_readonly' ) ) {
	/**
	 * Registers a read-only agent-callable ability.
	 *
	 * @param string $ability_id Namespaced ability id.
	 * @param array  $definition Ability definition.
	 * @return bool
	 */
	function npcink_abilities_toolkit_register_readonly( $ability_id, array $definition ) {
		return Npcink_Abilities_Toolkit\Plugin::instance()->abilities()->add_readonly( $ability_id, $definition );
	}
}

if ( ! function_exists( 'npcink_abilities_toolkit_register_write_proposal' ) ) {
	/**
	 * Registers a write-like ability that returns a proposal instead of committing directly.
	 *
	 * @param string $ability_id Namespaced ability id.
	 * @param array  $definition Ability definition.
	 * @return bool
	 */
	function npcink_abilities_toolkit_register_write_proposal( $ability_id, array $definition ) {
		return Npcink_Abilities_Toolkit\Plugin::instance()->abilities()->add_write_proposal( $ability_id, $definition );
	}
}

if ( ! function_exists( 'npcink_abilities_toolkit_normalize_schema' ) ) {
	/**
	 * Normalizes a schema fragment for WordPress Abilities API registration.
	 *
	 * @param mixed  $schema Raw schema.
	 * @param string $default_type Default type.
	 * @return array
	 */
	function npcink_abilities_toolkit_normalize_schema( $schema, $default_type = 'object' ) {
		$normalizer = new Npcink_Abilities_Toolkit\Registry\Schema_Normalizer();

		return $normalizer->normalize( $schema, $default_type );
	}
}

if ( ! function_exists( 'npcink_abilities_toolkit_normalize_annotations' ) ) {
	/**
	 * Normalizes Abilities API annotations for a risk level.
	 *
	 * @param mixed  $annotations Raw annotations.
	 * @param string $risk_level Risk level.
	 * @return array
	 */
	function npcink_abilities_toolkit_normalize_annotations( $annotations, $risk_level = 'read' ) {
		$normalizer = new Npcink_Abilities_Toolkit\Registry\Annotation_Normalizer();

		return $normalizer->normalize( $annotations, $risk_level );
	}
}

if ( ! function_exists( 'npcink_abilities_toolkit_get_registered' ) ) {
	/**
	 * Returns normalized abilities registered through this toolkit.
	 *
	 * @return array
	 */
	function npcink_abilities_toolkit_get_registered() {
		return Npcink_Abilities_Toolkit\Plugin::instance()->abilities()->all();
	}
}

if ( ! function_exists( 'npcink_abilities_toolkit_refresh_catalog_observability' ) ) {
	/**
	 * Emits the local catalog changed event for a manual catalog refresh.
	 *
	 * @param string $reason Refresh reason.
	 * @return bool
	 */
	function npcink_abilities_toolkit_refresh_catalog_observability( $reason = 'manual_refresh' ) {
		return Npcink_Abilities_Toolkit\Plugin::instance()->abilities()->emit_manual_catalog_refresh( $reason );
	}
}

if ( ! function_exists( 'npcink_abilities_toolkit_get_workflow_definitions' ) ) {
	/**
	 * Returns read-only workflow recipe definitions for host discovery.
	 *
	 * These definitions are declarative recipe guidance. They do not execute,
	 * schedule, approve, retry, audit, or commit workflow steps.
	 *
	 * @return array<string,mixed>
	 */
	function npcink_abilities_toolkit_get_workflow_definitions() {
		return Npcink_Abilities_Toolkit\Workflow\Workflow_Definition_Provider::manifest();
	}
}

if ( ! function_exists( 'npcink_abilities_toolkit_get_workflow_definition' ) ) {
	/**
	 * Returns one workflow recipe definition by recipe id or case id.
	 *
	 * @param string $recipe_id Recipe id or case id.
	 * @return array<string,mixed>|null
	 */
	function npcink_abilities_toolkit_get_workflow_definition( $recipe_id ) {
		return Npcink_Abilities_Toolkit\Workflow\Workflow_Definition_Provider::get( $recipe_id );
	}
}
