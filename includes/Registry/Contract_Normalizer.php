<?php
/**
 * Ability contract normalization.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Registry;

use Npcink_Abilities_Toolkit\Security\Permission_Callbacks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Expands user-provided ability definitions into a stable contract.
 */
final class Contract_Normalizer {
	/**
	 * Schema normalizer.
	 *
	 * @var Schema_Normalizer
	 */
	private $schema_normalizer;

	/**
	 * Annotation normalizer.
	 *
	 * @var Annotation_Normalizer
	 */
	private $annotation_normalizer;

	/**
	 * Constructor.
	 *
	 * @param Schema_Normalizer $schema_normalizer Schema normalizer.
	 */
	public function __construct( Schema_Normalizer $schema_normalizer, ?Annotation_Normalizer $annotation_normalizer = null ) {
		$this->schema_normalizer     = $schema_normalizer;
		$this->annotation_normalizer = $annotation_normalizer ? $annotation_normalizer : new Annotation_Normalizer();
	}

	/**
	 * Normalizes an ability definition.
	 *
	 * @param string              $ability_id Ability id.
	 * @param array<string,mixed> $definition Raw definition.
	 * @param string              $mode Registration mode.
	 * @return array<string,mixed>
	 */
	public function normalize( $ability_id, array $definition, $mode ) {
		$ability_id = $this->sanitize_ability_id( $ability_id );
		$mode       = sanitize_key( (string) $mode );
		$is_write   = in_array( $mode, array( 'write_proposal', 'write_host', 'destructive_host' ), true );
		$is_destructive = 'destructive_host' === $mode;
		$risk_level = $is_destructive ? 'destructive' : ( $is_write ? 'write' : 'read' );

		$annotations = $this->annotation_normalizer->normalize(
			isset( $definition['annotations'] ) && is_array( $definition['annotations'] ) ? $definition['annotations'] : array(),
			$risk_level
		);

		$meta = isset( $definition['meta'] ) && is_array( $definition['meta'] )
			? $definition['meta']
			: array();
		$agent_usage = $this->normalize_agent_usage(
			isset( $definition['agent_usage'] ) ? $definition['agent_usage'] : ( $meta['agent_usage'] ?? array() )
		);
		$implementation_posture = $this->normalize_implementation_posture(
			isset( $definition['implementation_posture'] ) ? $definition['implementation_posture'] : ( $meta['implementation_posture'] ?? array() ),
			$risk_level
		);

		$mcp_meta = isset( $meta['mcp'] ) && is_array( $meta['mcp'] )
			? $meta['mcp']
			: array();
		$mcp_meta['public']      = ! empty( $mcp_meta['public'] );
		$mcp_meta['server']      = isset( $mcp_meta['server'] ) ? sanitize_key( (string) $mcp_meta['server'] ) : ( $is_write ? 'npcink-abilities-toolkit-write' : 'npcink-abilities-toolkit-read' );
		$mcp_meta['risk']        = $risk_level;
		$mcp_meta['annotations'] = $annotations;

		$npcink_meta = isset( $meta['npcink'] ) && is_array( $meta['npcink'] )
			? $meta['npcink']
			: array();
		$npcink_meta['canonical_ability_id'] = $ability_id;
		$npcink_meta['wp_ability_id']        = $ability_id;
		$npcink_meta['risk_level']           = $risk_level;
		$npcink_meta['requires_confirm']     = $is_write;
		$npcink_meta['requires_approval']    = $is_write;
		$npcink_meta['annotations']          = $annotations;
		$npcink_meta['channels']             = isset( $definition['channels'] ) && is_array( $definition['channels'] )
			? array_values( array_map( 'sanitize_key', $definition['channels'] ) )
			: array( 'abilities_rest' );
		if ( ! empty( $implementation_posture ) ) {
			$npcink_meta['implementation_posture'] = $implementation_posture;
		}

		$meta['show_in_rest'] = array_key_exists( 'show_in_rest', $meta )
			? ! empty( $meta['show_in_rest'] )
			: true;
		$meta['annotations']  = $annotations;
		$meta['mcp']         = $mcp_meta;
		$meta['npcink']      = $npcink_meta;
		if ( $this->has_agent_usage( $agent_usage ) ) {
			$meta['agent_usage'] = $agent_usage;
		}
		if ( ! empty( $implementation_posture ) ) {
			$meta['implementation_posture'] = $implementation_posture;
		}

		$capability = isset( $definition['capability'] ) ? sanitize_key( (string) $definition['capability'] ) : 'manage_options';
		if ( '' === $capability ) {
			$capability = 'manage_options';
		}

		$permission_callback = isset( $definition['permission_callback'] ) && is_callable( $definition['permission_callback'] )
			? $definition['permission_callback']
			: Permission_Callbacks::for_capability( $capability );
		$source = isset( $definition['source'] ) ? sanitize_key( (string) $definition['source'] ) : 'third_party';
		if ( ! in_array( $source, array( 'official', 'third_party' ), true ) ) {
			$source = 'third_party';
		}
		$input_schema = $this->schema_normalizer->normalize( isset( $definition['input_schema'] ) ? $definition['input_schema'] : array(), 'object' );
		if ( $is_write ) {
			$input_schema = $this->add_write_control_schema( $input_schema );
		}
		$output_schema = $this->schema_normalizer->normalize( isset( $definition['output_schema'] ) ? $definition['output_schema'] : array(), 'object' );
		if ( $is_write ) {
			$output_schema = $this->add_write_output_schema( $output_schema );
		}

		$contract = array(
			'ability_id'          => $ability_id,
			'mode'                => $mode,
			'source'              => $source,
			'project_to_npcink_catalog' => array_key_exists( 'project_to_npcink_catalog', $definition )
				? ! empty( $definition['project_to_npcink_catalog'] )
				: false,
			'label'               => isset( $definition['label'] ) ? sanitize_text_field( (string) $definition['label'] ) : $ability_id,
			'description'         => isset( $definition['description'] ) ? sanitize_text_field( (string) $definition['description'] ) : '',
			'category'            => isset( $definition['category'] ) ? sanitize_key( (string) $definition['category'] ) : ( $is_write ? 'npcink-abilities-toolkit-write' : 'npcink-abilities-toolkit-read' ),
			'input_schema'        => $input_schema,
			'output_schema'       => $output_schema,
			'execute_callback'    => isset( $definition['execute_callback'] ) && is_callable( $definition['execute_callback'] )
				? $definition['execute_callback']
				: array( $this, 'missing_execute_callback' ),
			'permission_callback' => $permission_callback,
			'capability'          => $capability,
			'annotations'         => $annotations,
			'risk_level'          => $risk_level,
			'requires_confirm'    => $is_write,
			'requires_approval'   => $is_write,
			'required_scope'      => isset( $definition['required_scope'] ) ? sanitize_text_field( (string) $definition['required_scope'] ) : '',
			'required_scopes'     => isset( $definition['required_scopes'] ) && is_array( $definition['required_scopes'] )
				? array_values(
					array_map(
						static function ( $scope ) {
							return sanitize_text_field( (string) $scope );
						},
						$definition['required_scopes']
					)
				)
				: array(),
			'contract_version'    => isset( $definition['contract_version'] ) ? sanitize_text_field( (string) $definition['contract_version'] ) : 'v1',
			'deprecated'          => ! empty( $definition['deprecated'] ),
			'successor'           => isset( $definition['successor'] ) ? $this->sanitize_ability_id( $definition['successor'] ) : '',
			'meta'                => $meta,
		);

		if ( $this->has_agent_usage( $agent_usage ) ) {
			$contract['agent_usage'] = $agent_usage;
		}
		if ( ! empty( $implementation_posture ) ) {
			$contract['implementation_posture'] = $implementation_posture;
		}

		return $contract;
	}

	/**
	 * Returns a WP_Error for abilities missing an execute callback.
	 *
	 * @return \WP_Error
	 */
	public function missing_execute_callback() {
		return new \WP_Error(
			'npcink_abilities_toolkit_missing_execute_callback',
			__( 'Ability execute callback is not configured.', 'npcink-abilities-toolkit' ),
			array( 'status' => 500 )
		);
	}

	/**
	 * Sanitizes a WordPress ability id.
	 *
	 * @param mixed $ability_id Raw ability id.
	 * @return string
	 */
	private function sanitize_ability_id( $ability_id ) {
		$ability_id = strtolower( sanitize_text_field( (string) $ability_id ) );
		$ability_id = preg_replace( '/[^a-z0-9._:\/-]/', '', $ability_id );

		return is_string( $ability_id ) ? $ability_id : '';
	}

	/**
	 * Adds common host-governed write controls to an input schema.
	 *
	 * @param array<string,mixed> $schema Input schema.
	 * @return array<string,mixed>
	 */
	private function add_write_control_schema( array $schema ) {
		if ( ! isset( $schema['properties'] ) || ! is_array( $schema['properties'] ) ) {
			$schema['properties'] = array();
		}
		$schema['properties']['dry_run'] = array(
			'type'        => 'boolean',
			'default'     => true,
			'description' => __( 'When true, return a host-governed preview without mutating WordPress.', 'npcink-abilities-toolkit' ),
		);
		$schema['properties']['commit'] = array(
			'type'        => 'boolean',
			'default'     => false,
			'description' => __( 'When true, attempt the final commit. Host approval context is still required.', 'npcink-abilities-toolkit' ),
		);
		$schema['properties']['idempotency_key'] = array(
			'type'        => 'string',
			'minLength'   => 1,
			'maxLength'   => 190,
			'description' => __( 'Optional host-provided idempotency key for audit and replay correlation.', 'npcink-abilities-toolkit' ),
		);
		return $schema;
	}

	/**
	 * Adds common host-governed write dry-run output fields.
	 *
	 * @param array<string,mixed> $schema Output schema.
	 * @return array<string,mixed>
	 */
	private function add_write_output_schema( array $schema ) {
		if ( ! isset( $schema['properties'] ) || ! is_array( $schema['properties'] ) ) {
			$schema['properties'] = array();
		}
		$schema['properties']['dry_run'] = array( 'type' => 'boolean' );
		$schema['properties']['host_governed'] = array( 'type' => 'boolean' );
		$schema['properties']['commit_required'] = array( 'type' => 'boolean' );
		$schema['properties']['preview'] = array(
			'type'                 => 'object',
			'additionalProperties' => true,
		);
		return $schema;
	}

	/**
	 * Normalizes static guidance for agents and MCP tool descriptions.
	 *
	 * @param mixed $agent_usage Raw agent usage guidance.
	 * @return array<string,array<int,string>>
	 */
	private function normalize_agent_usage( $agent_usage ) {
		$agent_usage = is_array( $agent_usage ) ? $agent_usage : array();
		$normalized  = array();

		foreach ( array( 'when_to_use', 'not_for', 'best_for', 'stopping_points' ) as $field ) {
			$items = isset( $agent_usage[ $field ] ) ? $agent_usage[ $field ] : array();
			if ( ! is_array( $items ) ) {
				$items = array( $items );
			}

			$normalized[ $field ] = array_values(
				array_filter(
					array_map(
						static function ( $item ) {
							return sanitize_text_field( (string) $item );
						},
						$items
					),
					static function ( $item ) {
						return '' !== $item;
					}
				)
			);
		}

		return $normalized;
	}

	/**
	 * Checks whether agent usage guidance contains any guidance items.
	 *
	 * @param array<string,array<int,string>> $agent_usage Normalized usage guidance.
	 * @return bool
	 */
	private function has_agent_usage( array $agent_usage ) {
		foreach ( $agent_usage as $items ) {
			if ( ! empty( $items ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Normalizes implementation posture metadata for host-governed abilities.
	 *
	 * @param mixed  $posture Raw implementation posture metadata.
	 * @param string $risk_level Normalized ability risk level.
	 * @return array<string,mixed>
	 */
	private function normalize_implementation_posture( $posture, $risk_level ) {
		if ( ! is_array( $posture ) || empty( $posture ) ) {
			return array();
		}

		$normalized = array(
			'schema_version'                 => 'npcink_abilities_toolkit_implementation_posture.v1',
			'implementation_owner'           => 'npcink-abilities-toolkit',
			'execution_surface'              => 'wordpress_abilities_api_host_governed',
			'write_posture'                  => in_array( $risk_level, array( 'write', 'destructive' ), true ) ? 'host_governed_dry_run_first' : 'read_only',
			'commit_authority'               => 'host_runtime_approval_context_required',
			'final_authorization_owner'      => 'host_governance_layer',
			'approval_truth_owner'           => 'host_governance_layer',
			'audit_truth_owner'              => 'host_governance_layer',
			'direct_wordpress_write_default' => false,
			'dry_run_default'                => true,
			'commit_default'                 => false,
			'reference_patterns'             => $this->sanitize_string_list( $posture['reference_patterns'] ?? array() ),
			'verification_contract'          => $this->sanitize_string_list( $posture['verification_contract'] ?? array() ),
			'required_host_evidence'         => $this->sanitize_string_list( $posture['required_host_evidence'] ?? array() ),
			'non_goals'                      => $this->sanitize_string_list( $posture['non_goals'] ?? array() ),
			'workflow_runtime'               => false,
			'queue_or_scheduler'             => false,
			'model_routing'                  => false,
			'provider_credentials'           => false,
			'approval_storage'               => false,
			'audit_storage'                  => false,
		);

		foreach ( array( 'implementation_owner', 'execution_surface', 'write_posture', 'commit_authority', 'final_authorization_owner', 'approval_truth_owner', 'audit_truth_owner' ) as $field ) {
			if ( isset( $posture[ $field ] ) && '' !== trim( (string) $posture[ $field ] ) ) {
				$normalized[ $field ] = sanitize_key( (string) $posture[ $field ] );
			}
		}
		if ( isset( $posture['schema_version'] ) && '' !== trim( (string) $posture['schema_version'] ) ) {
			$normalized['schema_version'] = sanitize_text_field( (string) $posture['schema_version'] );
		}

		return $normalized;
	}

	/**
	 * Sanitizes a list of short contract strings.
	 *
	 * @param mixed $items Raw list.
	 * @return array<int,string>
	 */
	private function sanitize_string_list( $items ) {
		if ( ! is_array( $items ) ) {
			$items = array( $items );
		}

		return array_values(
			array_filter(
				array_map(
					static function ( $item ) {
						return sanitize_text_field( (string) $item );
					},
					$items
				),
				static function ( $item ) {
					return '' !== $item;
				}
			)
		);
	}
}
