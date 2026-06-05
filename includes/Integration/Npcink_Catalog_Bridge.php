<?php
/**
 * Optional Npcink AI catalog bridge.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Integration;

use Npcink_Abilities_Toolkit\Registry\Ability_Registrar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Projects registered WordPress abilities into Npcink AI's catalog when Npcink AI is available.
 */
final class Npcink_Catalog_Bridge {
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
		add_filter( 'npcink_ai_open_platform_ability_catalog', array( $this, 'filter_catalog' ), 20, 2 );
	}

	/**
	 * Adds opted-in provider ability definitions to the Npcink AI catalog.
	 *
	 * @param mixed $catalog Existing catalog.
	 * @param mixed $args Catalog build args.
	 * @return array<string,array<string,mixed>>
	 */
	public function filter_catalog( $catalog, $args = array() ) {
		unset( $args );

		$catalog = is_array( $catalog ) ? $catalog : array();

		foreach ( $this->abilities->all() as $ability_id => $definition ) {
			if ( empty( $definition['project_to_npcink_catalog'] ) ) {
				continue;
			}

			if ( empty( $definition['meta']['show_in_rest'] ) ) {
				continue;
			}

			$catalog_key = $this->catalog_key( $ability_id );
			if ( '' === $catalog_key ) {
				continue;
			}

			$npcink_meta   = is_array( $definition['meta']['npcink'] ?? null ) ? $definition['meta']['npcink'] : array();
			$npcink_meta['show_in_rest'] = true;
			$npcink_meta['wp_ability_id'] = $ability_id;
			$npcink_meta['risk_level'] = $definition['risk_level'];
			$npcink_meta['requires_confirm'] = $definition['requires_confirm'];

			$row = array(
				'ability_id'        => $ability_id,
				'name'              => $definition['label'],
				'description'       => $definition['description'],
				'source'            => $definition['source'],
				'required_scope'    => $definition['required_scope'],
				'required_scopes'   => $definition['required_scopes'],
				'input_schema'      => $definition['input_schema'],
				'output_schema'     => $definition['output_schema'],
				'executor_type'     => 'wp_ability',
				'wp_ability_id'     => $ability_id,
				'risk_level'        => $definition['risk_level'],
				'requires_confirm'  => $definition['requires_confirm'],
				'show_in_rest'      => true,
				'contract_version'  => $definition['contract_version'],
				'deprecated'        => $definition['deprecated'],
				'successor'         => $definition['successor'],
				'meta'              => array(
					'show_in_rest' => true,
					'annotations'  => $definition['annotations'],
					'npcink'       => $npcink_meta,
				),
			);

			/**
			 * Filters a projected Npcink AI catalog row.
			 *
			 * The default row is intentionally thin: it identifies the WordPress
			 * ability and carries schemas/annotations without owning Npcink AI
			 * routing, OpenAPI, backend priority, or tool policy decisions.
			 *
			 * @param array<string,mixed> $row Ability catalog row.
			 * @param string              $ability_id WordPress ability id.
			 * @param array<string,mixed> $definition Normalized ability definition.
			 */
			$catalog[ $catalog_key ] = apply_filters( 'npcink_abilities_toolkit_projected_catalog_row', $row, $ability_id, $definition );
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
