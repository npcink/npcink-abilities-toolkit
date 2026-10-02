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
	const RUNTIME_CONTRACT_ENDPOINT_VERSION = '1';
	const ABILITY_CONTRACT_SOURCE = 'npcink_abilities_toolkit';

	/**
	 * Whether the not-modified serve filter was registered for this process.
	 *
	 * @var bool
	 */
	private static $serve_filter_registered = false;

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
					'callback'            => array( $this, 'serve_contract' ),
					'permission_callback' => array( $this, 'can_read_contract' ),
				),
			)
		);
		if ( function_exists( 'add_filter' ) && ! self::$serve_filter_registered ) {
			self::$serve_filter_registered = true;
			add_filter( 'rest_pre_serve_request', array( $this, 'maybe_send_not_modified' ), 10, 4 );
		}
	}

	/**
	 * Returns the cache policy for the contract response.
	 *
	 * The default forces revalidation on every poll; the paired ETag makes
	 * that revalidation an empty 304, so clients never hold a stale contract
	 * just because a max-age window has not elapsed.
	 *
	 * @return string
	 */
	private function cache_control() {
		$default = 'private, no-cache';

		if ( ! function_exists( 'apply_filters' ) ) {
			return $default;
		}

		/**
		 * Filters the Cache-Control header for the runtime contract response.
		 *
		 * Hosts that accept a short staleness window can return for example
		 * 'private, max-age=300'.
		 *
		 * @param string $default Default Cache-Control value.
		 */
		$value = apply_filters( 'npcink_abilities_toolkit_contract_cache_control', $default );

		return is_string( $value ) && '' !== $value ? $value : $default;
	}

	/**
	 * Serves the contract with cache validation headers.
	 *
	 * @return array<string,mixed>|\WP_REST_Response
	 */
	public function serve_contract() {
		$data = $this->contract();
		if ( ! class_exists( '\WP_REST_Response' ) ) {
			return $data;
		}

		$response = new \WP_REST_Response( $data );
		$response->set_headers(
			array(
				'ETag'          => $this->contract_etag( $data ),
				'Cache-Control' => $this->cache_control(),
			)
		);

		return $response;
	}

	/**
	 * Answers If-None-Match with an empty 304 for the contract route.
	 *
	 * Clients that already hold the contract identified by its ETag avoid
	 * re-downloading the full payload on every poll.
	 *
	 * @param mixed         $served Whether the request was already served.
	 * @param mixed         $result Response result.
	 * @param mixed         $request Request.
	 * @param mixed         $server REST server.
	 * @return bool
	 */
	public function maybe_send_not_modified( $served, $result, $request, $server ) {
		unset( $server );

		if ( $served ) {
			return (bool) $served;
		}
		if ( ! is_object( $result ) || ! method_exists( $result, 'get_headers' ) ) {
			return (bool) $served;
		}
		if ( ! is_object( $request ) || ! method_exists( $request, 'get_route' ) ) {
			return (bool) $served;
		}
		if ( '/' . self::NAMESPACE . '/contract' !== (string) $request->get_route() ) {
			return (bool) $served;
		}

		$headers = $result->get_headers();
		$etag    = isset( $headers['ETag'] ) ? (string) $headers['ETag'] : '';
		if ( '' === $etag ) {
			return (bool) $served;
		}

		$if_none_match = isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? trim( (string) ( function_exists( 'wp_unslash' ) ? wp_unslash( $_SERVER['HTTP_IF_NONE_MATCH'] ) : $_SERVER['HTTP_IF_NONE_MATCH'] ) )
			: '';
		if ( '' === $if_none_match || ! $this->etag_matches( $etag, $if_none_match ) ) {
			return (bool) $served;
		}

		if ( function_exists( 'status_header' ) ) {
			status_header( 304 );
		}
		if ( function_exists( 'header' ) ) {
			header( 'ETag: ' . $etag );
			if ( isset( $headers['Cache-Control'] ) ) {
				header( 'Cache-Control: ' . (string) $headers['Cache-Control'] );
			}
		}

		return true;
	}

	/**
	 * Returns whether an If-None-Match header matches the contract ETag.
	 *
	 * @param string $etag Response ETag.
	 * @param string $if_none_match Request If-None-Match value.
	 * @return bool
	 */
	private function etag_matches( $etag, $if_none_match ) {
		if ( '*' === $if_none_match ) {
			return true;
		}

		foreach ( array_map( 'trim', explode( ',', $if_none_match ) ) as $candidate ) {
			if ( 0 === strpos( $candidate, 'W/' ) ) {
				$candidate = substr( $candidate, 2 );
			}
			if ( trim( $candidate, '"' ) === trim( $etag, '"' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns the quoted ETag for one contract payload.
	 *
	 * @param array<string,mixed> $data Contract payload.
	 * @return string
	 */
	private function contract_etag( array $data ) {
		return '"' . $this->sha256( $data ) . '"';
	}

	/**
	 * Returns whether the WordPress Abilities API catalog route is live.
	 *
	 * @return bool
	 */
	private function ability_catalog_route_available() {
		$routes = array();
		if ( function_exists( 'rest_get_server' ) ) {
			$server = rest_get_server();
			$routes = is_object( $server ) && method_exists( $server, 'get_routes' ) ? $server->get_routes() : array();
		}

		return isset( $routes['/wp-abilities/v1/abilities'] );
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
			'runtime_contract_endpoint_version' => self::RUNTIME_CONTRACT_ENDPOINT_VERSION,
			'compatibility'               => array(
				'contract_family'                 => 'npcink_abilities_toolkit',
				'minimum_adapter_contract_version' => '1',
				'metadata_only'                   => true,
				'admin_authenticated'             => true,
				'wordpress_abilities_api_required' => true,
				'ability_catalog_available'       => $this->ability_catalog_route_available(),
				'ability_schema_hashes_available' => true,
				'workflow_recipe_hash_available'  => true,
			),
			'ability_count'               => count( $ability_ids ),
			'ability_risk_counts'         => $risk_counts,
			'abilities'                   => $ability_projection,
			'catalog'                     => array(
				'ability_definitions_owner' => 'npcink-abilities-toolkit',
				'ability_catalog_source'    => 'wordpress_abilities_api',
				'ability_catalog_route'     => '/wp-json/wp-abilities/v1/abilities',
				'ability_detail_route_template' => '/wp-json/wp-abilities/v1/abilities/{namespace}/{name}',
				'ability_run_route_template' => '/wp-json/wp-abilities/v1/abilities/{namespace}/{name}/run',
				'ability_id_format'         => 'namespace/name',
			),
			'ability_ids_hash'            => $this->sha256( $ability_ids ),
			'ability_contracts_hash'      => $this->sha256( $ability_projection ),
			'workflow_recipes_hash'       => $this->sha256( $workflow_recipes ),
			'schema_controls'             => array(
				'input_schema_source'       => 'wordpress_abilities_api',
				'output_schema_source'      => 'wordpress_abilities_api',
				'normalization_owner'       => 'npcink-abilities-toolkit',
				'callback_free_hashes'      => true,
				'stable_contract_hashes'    => true,
			),
			'write_controls'              => array(
				'dry_run_default'       => true,
				'commit_default'        => false,
				'idempotency_required'  => true,
				'host_governed_writes'  => true,
				'final_commit_owner'    => 'host_runtime_after_governance',
			),
			'execution_controls'          => array(
				'read_execution_surface'       => 'wordpress_abilities_api',
				'write_execution_surface'      => 'host_runtime_after_governance',
				'approval_context_required'    => true,
				'approval_storage'             => false,
				'audit_truth'                  => false,
				'final_write_authorization'    => false,
			),
			'boundary'                    => array(
				'ability_definitions_owner' => 'npcink-abilities-toolkit',
				'approval_truth_owner'      => 'host_governance_layer',
				'audit_truth_owner'         => 'host_governance_layer',
				'final_write_authority'     => 'host_governance_layer',
				'workflow_runtime_owner'    => 'host_or_external_runtime',
				'cloud_control_plane_owner' => 'not_npcink-abilities-toolkit',
			),
			'forbidden_payloads'          => array(
				'callback_internals'       => false,
				'permission_callable_refs' => false,
				'approval_records'         => false,
				'audit_records'            => false,
				'app_secret_material'      => false,
				'provider_secret_material' => false,
				'runtime_state'            => false,
				'model_routing'            => false,
				'prompt_material'          => false,
				'cloud_execution_truth'    => false,
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
				'contract_source'    => self::ABILITY_CONTRACT_SOURCE,
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
					'implementation_posture' => is_array( $ability['implementation_posture'] ?? null ) ? $ability['implementation_posture'] : array(),
					'channels'           => array_values( array_map( 'strval', (array) ( $ability['channels'] ?? array() ) ) ),
				'meta'               => $this->meta_contract_projection( is_array( $ability['meta'] ?? null ) ? $ability['meta'] : array() ),
			);
			$projection[ (string) $ability_id ]['schema_hash'] = $this->sha256(
				array(
					'input_schema'  => $projection[ (string) $ability_id ]['input_schema'],
					'output_schema' => $projection[ (string) $ability_id ]['output_schema'],
				)
			);
			$implementation_posture = $projection[ (string) $ability_id ]['implementation_posture'];
			$projection[ (string) $ability_id ]['write_posture'] = (string) ( $implementation_posture['write_posture'] ?? ( 'read' === $projection[ (string) $ability_id ]['risk_level'] ? 'read_only' : 'host_governed_dry_run_first' ) );
			$projection[ (string) $ability_id ]['verification_state'] = 'registered';
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
				'implementation_posture' => is_array( $npcink['implementation_posture'] ?? null ) ? $npcink['implementation_posture'] : array(),
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
