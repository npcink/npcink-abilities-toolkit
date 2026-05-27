<?php
/**
 * Optional Magick AI compatibility bridge.
 *
 * @package MagickAIAbilities
 */

namespace Magick_AI_Abilities\Integration;

use Magick_AI_Abilities\Registry\Ability_Registrar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Projects registered WordPress abilities into Magick AI's catalog when Magick AI is available.
 */
final class Magick_Catalog_Bridge {
	/**
	 * Ability registrar.
	 *
	 * @var Ability_Registrar
	 */
	private $abilities;

	/**
	 * Constructor.
	 *
	 * @param Ability_Registrar $abilities Ability registrar.
	 */
	public function __construct( Ability_Registrar $abilities ) {
		$this->abilities = $abilities;
	}

	/**
	 * Registers bridge hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_filter( 'magick_ai_open_platform_ability_catalog', array( $this, 'filter_catalog' ), 20, 2 );
	}

	/**
	 * Adds opted-in provider ability definitions to the Magick AI catalog.
	 *
	 * @param mixed $catalog Existing catalog.
	 * @param mixed $args Catalog build args.
	 * @return array<string,array<string,mixed>>
	 */
	public function filter_catalog( $catalog, $args = array() ) {
		unset( $args );

		$catalog = is_array( $catalog ) ? $catalog : array();

		foreach ( $this->abilities->all() as $ability_id => $definition ) {
			if ( empty( $definition['project_to_magick_catalog'] ) ) {
				continue;
			}

			if ( empty( $definition['meta']['show_in_rest'] ) ) {
				continue;
			}

			$catalog_key = $this->catalog_key( $ability_id );
			if ( '' === $catalog_key ) {
				continue;
			}

			$is_write_like = in_array( $definition['risk_level'], array( 'write', 'destructive' ), true );
			$is_destructive = 'destructive' === $definition['risk_level'];
			$write_mode    = $is_write_like ? 'allow_write' : 'read_only';
			$tool_policy   = array(
				'allow_write'          => $is_write_like,
				'require_confirmation' => ! empty( $definition['requires_confirm'] ),
				'max_calls'            => $is_write_like ? 1 : 0,
			);
			if ( $is_destructive ) {
				$tool_policy['allow_destructive'] = true;
			}
			$magick_meta   = is_array( $definition['meta']['magick'] ?? null ) ? $definition['meta']['magick'] : array();
			$magick_meta['show_in_rest'] = true;
			$magick_meta['write_mode'] = $write_mode;
			$magick_meta['tool_policy'] = $tool_policy;

			$catalog[ $catalog_key ] = array(
				'ability_id'        => $ability_id,
				'name'              => $definition['label'],
				'description'       => $definition['description'],
				'version'           => '1.0.0',
				'schema_version'    => 'v1',
				'source'            => $definition['source'],
				'review_status'     => 'draft',
				'stability'         => 'beta',
				'open_api_enabled'  => false,
				'required_scope'    => $definition['required_scope'],
				'required_scopes'   => $definition['required_scopes'],
				'input_schema'      => $definition['input_schema'],
				'output_schema'     => $definition['output_schema'],
				'executor_type'     => 'wp_ability',
				'backend_priority'  => array( 'wp_ability' ),
				'wp_ability_id'     => $ability_id,
				'risk_level'        => $definition['risk_level'],
				'requires_confirm'  => $definition['requires_confirm'],
				'show_in_rest'      => true,
				'write_mode'        => $write_mode,
				'tool_policy'       => $tool_policy,
				'contract_version'  => $definition['contract_version'],
				'deprecated'        => $definition['deprecated'],
				'successor'         => $definition['successor'],
				'execution_modes'   => array( 'sync' ),
				'allowed_channels'  => $magick_meta['channels'],
				'skip_catalog_manifest_fallback' => true,
				'meta'              => array(
					'show_in_rest' => true,
					'annotations'  => $definition['annotations'],
					'mcp'          => $definition['meta']['mcp'],
					'magick'       => $magick_meta,
				),
			);
		}

		return $catalog;
	}

	/**
	 * Builds a stable catalog array key.
	 *
	 * @param string $ability_id Ability id.
	 * @return string
	 */
	private function catalog_key( $ability_id ) {
		$key = strtolower( sanitize_text_field( (string) $ability_id ) );
		$key = str_replace( '/', '_', $key );
		$key = preg_replace( '/[^a-z0-9._:-]/', '', $key );

		return is_string( $key ) ? $key : '';
	}
}
