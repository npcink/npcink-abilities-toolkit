<?php
/**
 * Ability registrar.
 *
 * @package MagickAIAbilities
 */

namespace Magick_AI_Abilities\Registry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores and registers Abilities API abilities.
 */
final class Ability_Registrar {
	/**
	 * Category registrar.
	 *
	 * @var Category_Registrar
	 */
	private $categories;

	/**
	 * Contract normalizer.
	 *
	 * @var Contract_Normalizer
	 */
	private $contract_normalizer;

	/**
	 * Ability definitions.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	private $abilities = array();

	/**
	 * Constructor.
	 *
	 * @param Category_Registrar  $categories Category registrar.
	 * @param Contract_Normalizer $contract_normalizer Contract normalizer.
	 */
	public function __construct( Category_Registrar $categories, Contract_Normalizer $contract_normalizer ) {
		$this->categories           = $categories;
		$this->contract_normalizer = $contract_normalizer;
	}

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'wp_abilities_api_init', array( $this, 'register_with_wordpress' ), 10 );
	}

	/**
	 * Adds a read-only ability.
	 *
	 * @param string              $ability_id Ability id.
	 * @param array<string,mixed> $definition Ability definition.
	 * @return bool
	 */
	public function add_readonly( $ability_id, array $definition ) {
		return $this->add( $ability_id, $definition, 'readonly' );
	}

	/**
	 * Adds a write proposal ability.
	 *
	 * @param string              $ability_id Ability id.
	 * @param array<string,mixed> $definition Ability definition.
	 * @return bool
	 */
	public function add_write_proposal( $ability_id, array $definition ) {
		return $this->add( $ability_id, $definition, 'write_proposal' );
	}

	/**
	 * Returns all normalized ability definitions.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function all() {
		return $this->abilities;
	}

	/**
	 * Registers queued abilities with WordPress.
	 *
	 * @return void
	 */
	public function register_with_wordpress() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		foreach ( $this->abilities as $ability_id => $definition ) {
			if ( function_exists( 'wp_has_ability' ) && wp_has_ability( $ability_id ) ) {
				continue;
			}

			$category = isset( $definition['category'] ) ? sanitize_key( (string) $definition['category'] ) : '';
			if ( '' !== $category && ! isset( $this->categories->all()[ $category ] ) ) {
				$this->categories->add(
					$category,
					array(
						'label'       => $category,
						'description' => '',
					)
				);
			}

			wp_register_ability(
				$ability_id,
				array(
					'label'               => $definition['label'],
					'description'         => $definition['description'],
					'category'            => $definition['category'],
					'input_schema'        => $definition['input_schema'],
					'output_schema'       => $definition['output_schema'],
					'execute_callback'    => $definition['execute_callback'],
					'permission_callback' => $definition['permission_callback'],
					'meta'                => $definition['meta'],
				)
			);
		}
	}

	/**
	 * Adds an ability definition.
	 *
	 * @param string              $ability_id Ability id.
	 * @param array<string,mixed> $definition Ability definition.
	 * @param string              $mode Registration mode.
	 * @return bool
	 */
	private function add( $ability_id, array $definition, $mode ) {
		$normalized = $this->contract_normalizer->normalize( $ability_id, $definition, $mode );
		$ability_id = $normalized['ability_id'];

		if ( '' === $ability_id || false === strpos( $ability_id, '/' ) ) {
			return false;
		}

		$this->abilities[ $ability_id ] = $normalized;

		return true;
	}
}
