<?php
/**
 * Runtime contract REST surface.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Rest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exposes stable, non-secret Toolkit contract metadata.
 */
final class Contract_Controller {
	const NAMESPACE                 = 'npcink-abilities-toolkit/v1';
	const TOOLKIT_CONTRACT_VERSION  = '1';
	const ABILITY_REGISTRY_VERSION  = '1';
	const WORKFLOW_RECIPES_VERSION  = '1';

	/**
	 * Registers REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		if ( ! function_exists( 'register_rest_route' ) ) {
			return;
		}

		register_rest_route(
			self::NAMESPACE,
			'/contract',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'contract' ),
					'permission_callback' => array( $this, 'can_read_contract' ),
				),
			)
		);
	}

	/**
	 * Checks whether the current REST caller can read runtime contract metadata.
	 *
	 * @return bool
	 */
	public function can_read_contract() {
		return function_exists( 'current_user_can' ) ? current_user_can( 'manage_options' ) : false;
	}

	/**
	 * Returns the runtime contract projection.
	 *
	 * @return array<string,mixed>
	 */
	public function contract() {
		$abilities          = function_exists( 'npcink_abilities_toolkit_get_registered' ) ? npcink_abilities_toolkit_get_registered() : array();
		$abilities          = is_array( $abilities ) ? $abilities : array();
		$ability_ids        = array_values( array_map( 'strval', array_keys( $abilities ) ) );
		$ability_projection = $this->ability_contract_projection( $abilities );
		$risk_counts        = $this->ability_risk_counts( $abilities );
		sort( $ability_ids, SORT_STRING );

		$workflow_recipes = function_exists( 'npcink_abilities_toolkit_get_workflow_definitions' ) ? npcink_abilities_toolkit_get_workflow_definitions() : array();
		$workflow_recipes = is_array( $workflow_recipes ) ? $workflow_recipes : array();

		return array(
			'schema_version'              => 'npcink_abilities_toolkit_contract.v1',
			'toolkit_contract_version'    => self::TOOLKIT_CONTRACT_VERSION,
			'plugin_version'              => defined( 'NPCINK_ABILITIES_TOOLKIT_VERSION' ) ? (string) NPCINK_ABILITIES_TOOLKIT_VERSION : '',
			'ability_registry_version'    => self::ABILITY_REGISTRY_VERSION,
			'workflow_recipes_version'    => self::WORKFLOW_RECIPES_VERSION,
			'ability_count'               => count( $ability_ids ),
			'ability_risk_counts'         => $risk_counts,
			'ability_ids_hash'            => $this->sha256( $ability_ids ),
			'ability_contracts_hash'      => $this->sha256( $ability_projection ),
			'workflow_recipes_hash'       => $this->sha256( $workflow_recipes ),
			'write_controls'              => array(
				'dry_run_default'       => true,
				'commit_default'        => false,
				'idempotency_required'  => true,
				'host_governed_writes'  => true,
				'final_commit_owner'    => 'host_runtime_after_governance',
			),
			'boundary'                    => array(
				'ability_definitions_owner' => 'npcink-abilities-toolkit',
				'approval_truth_owner'      => 'host_governance_layer',
				'final_write_authority'     => 'host_governance_layer',
				'workflow_runtime_owner'    => 'host_or_external_runtime',
				'cloud_control_plane_owner' => 'not_npcink-abilities-toolkit',
			),
		);
	}

	/**
	 * Builds a callback-free projection of ability contracts.
	 *
	 * @param array<string,mixed> $abilities Registered abilities.
	 * @return array<string,array<string,mixed>>
	 */
	private function ability_contract_projection( array $abilities ) {
		$projection = array();

		foreach ( $abilities as $ability_id => $ability ) {
			if ( ! is_array( $ability ) ) {
				continue;
			}

			$projection[ (string) $ability_id ] = array(
				'ability_id'         => (string) ( $ability['ability_id'] ?? $ability_id ),
				'contract_version'   => (string) ( $ability['contract_version'] ?? '' ),
				'category'           => (string) ( $ability['category'] ?? '' ),
				'risk_level'         => (string) ( $ability['risk_level'] ?? '' ),
				'requires_confirm'   => (bool) ( $ability['requires_confirm'] ?? false ),
				'requires_approval'  => (bool) ( $ability['requires_approval'] ?? false ),
				'required_scope'     => (string) ( $ability['required_scope'] ?? '' ),
				'required_scopes'    => array_values( array_map( 'strval', (array) ( $ability['required_scopes'] ?? array() ) ) ),
				'input_schema'       => is_array( $ability['input_schema'] ?? null ) ? $ability['input_schema'] : array(),
				'output_schema'      => is_array( $ability['output_schema'] ?? null ) ? $ability['output_schema'] : array(),
				'annotations'        => is_array( $ability['annotations'] ?? null ) ? $ability['annotations'] : array(),
				'channels'           => array_values( array_map( 'strval', (array) ( $ability['channels'] ?? array() ) ) ),
				'meta'               => $this->meta_contract_projection( is_array( $ability['meta'] ?? null ) ? $ability['meta'] : array() ),
			);
		}

		ksort( $projection, SORT_STRING );
		return $projection;
	}

	/**
	 * Builds a stable metadata projection without callback internals.
	 *
	 * @param array<string,mixed> $meta Ability meta.
	 * @return array<string,mixed>
	 */
	private function meta_contract_projection( array $meta ) {
		$npcink = is_array( $meta['npcink'] ?? null ) ? $meta['npcink'] : array();
		$mcp    = is_array( $meta['mcp'] ?? null ) ? $meta['mcp'] : array();

		return array(
			'show_in_rest' => (bool) ( $meta['show_in_rest'] ?? false ),
			'npcink'       => array(
				'canonical_ability_id' => (string) ( $npcink['canonical_ability_id'] ?? '' ),
				'risk_level'           => (string) ( $npcink['risk_level'] ?? '' ),
				'requires_approval'    => (bool) ( $npcink['requires_approval'] ?? false ),
			),
			'mcp'          => array(
				'public' => (bool) ( $mcp['public'] ?? false ),
				'server' => (string) ( $mcp['server'] ?? '' ),
				'risk'   => (string) ( $mcp['risk'] ?? '' ),
			),
		);
	}

	/**
	 * Counts abilities by risk level.
	 *
	 * @param array<string,mixed> $abilities Registered abilities.
	 * @return array<string,int>
	 */
	private function ability_risk_counts( array $abilities ) {
		$counts = array(
			'read'        => 0,
			'write'       => 0,
			'destructive' => 0,
			'other'       => 0,
		);

		foreach ( $abilities as $ability ) {
			$risk = is_array( $ability ) ? (string) ( $ability['risk_level'] ?? '' ) : '';
			if ( isset( $counts[ $risk ] ) ) {
				++$counts[ $risk ];
			} else {
				++$counts['other'];
			}
		}

		return $counts;
	}

	/**
	 * Returns a stable sha256 hash for contract values.
	 *
	 * @param mixed $value Contract value.
	 * @return string
	 */
	private function sha256( $value ) {
		$normalized = $this->normalize_for_hash( $value );
		$json       = function_exists( 'wp_json_encode' )
			? wp_json_encode( $normalized )
			: json_encode( $normalized );

		return 'sha256:' . hash( 'sha256', (string) $json );
	}

	/**
	 * Recursively sorts associative arrays for stable hashing.
	 *
	 * @param mixed $value Value.
	 * @return mixed
	 */
	private function normalize_for_hash( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		$normalized = array();
		foreach ( $value as $key => $child ) {
			$normalized[ $key ] = $this->normalize_for_hash( $child );
		}

		if ( $this->is_assoc( $normalized ) ) {
			ksort( $normalized, SORT_STRING );
		}

		return $normalized;
	}

	/**
	 * Checks whether an array has string/non-sequential keys.
	 *
	 * @param array<mixed> $value Value.
	 * @return bool
	 */
	private function is_assoc( array $value ) {
		if ( array() === $value ) {
			return false;
		}

		return array_keys( $value ) !== range( 0, count( $value ) - 1 );
	}
}
