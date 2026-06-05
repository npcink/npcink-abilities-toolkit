<?php
/**
 * Example consumer that builds a Core proposal payload from ability discovery.
 *
 * This file does not call Npcink AI Core directly. It shows how another plugin
 * can discover a real ability id, inspect its schema/risk metadata, and prepare
 * the payload it would send to Core's proposal endpoint.
 *
 * @package NpcinkAbilitiesToolkitExample
 */

add_action(
	'plugins_loaded',
	static function () {
		if ( ! function_exists( 'npcink_abilities_toolkit_get_registered' ) ) {
			return;
		}

		$abilities  = npcink_abilities_toolkit_get_registered();
		$ability_id = 'npcink-abilities-toolkit/create-draft';
		if ( ! isset( $abilities[ $ability_id ] ) || ! is_array( $abilities[ $ability_id ] ) ) {
			return;
		}

		$ability = $abilities[ $ability_id ];
		if ( 'write' !== (string) ( $ability['risk_level'] ?? '' ) || true !== (bool) ( $ability['requires_approval'] ?? false ) ) {
			return;
		}

		$proposal_payload = array(
			'ability_id' => $ability_id,
			'title'      => 'Create a draft from discovered ability schema',
			'summary'    => 'Prepared by a consumer plugin after reading the ability contract.',
			'input'      => array(
				'title'   => 'Draft prepared through Core governance',
				'content' => '<p>Draft body prepared by the consumer plugin.</p>',
				'dry_run' => true,
			),
			'preview'    => array(
				'ability_risk_level'    => (string) ( $ability['risk_level'] ?? '' ),
				'requires_approval'     => (bool) ( $ability['requires_approval'] ?? false ),
				'input_required_fields' => (array) ( $ability['input_schema']['required'] ?? array() ),
			),
			'caller'     => array(
				'source'      => 'example-consumer-plugin',
				'caller_type' => 'product_plugin',
			),
		);

		/**
		 * A real consumer would send $proposal_payload to:
		 * POST /wp-json/npcink-ai-core/v1/proposals
		 */
		do_action( 'npcink_abilities_toolkit_example_core_proposal_payload', $proposal_payload, $ability );
	}
);
