<?php
/**
 * Ability category registrar.
 *
 * @package MagickAIAbilities
 */

namespace Magick_AI_Abilities\Registry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores and registers Abilities API categories.
 */
final class Category_Registrar {
	/**
	 * Registered category definitions.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	private $categories = array();

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_with_wordpress' ), 5 );

		$this->register_defaults();
	}

	/**
	 * Adds a category definition.
	 *
	 * @param string              $category_id Category id.
	 * @param array<string,mixed> $args Category arguments.
	 * @return bool
	 */
	public function add( $category_id, array $args ) {
		$category_id = sanitize_key( (string) $category_id );
		if ( '' === $category_id ) {
			return false;
		}

		$this->categories[ $category_id ] = array(
			'label'       => isset( $args['label'] ) ? sanitize_text_field( (string) $args['label'] ) : $category_id,
			'description' => isset( $args['description'] ) ? sanitize_text_field( (string) $args['description'] ) : '',
			'meta'        => isset( $args['meta'] ) && is_array( $args['meta'] ) ? $args['meta'] : array(),
		);

		return true;
	}

	/**
	 * Returns all category definitions.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function all() {
		return $this->categories;
	}

	/**
	 * Registers queued categories with WordPress.
	 *
	 * @return void
	 */
	public function register_with_wordpress() {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		foreach ( $this->categories as $category_id => $args ) {
			if ( function_exists( 'wp_has_ability_category' ) && wp_has_ability_category( $category_id ) ) {
				continue;
			}

			wp_register_ability_category( $category_id, $args );
		}
	}

	/**
	 * Registers default Magick AI categories.
	 *
	 * @return void
	 */
	private function register_defaults() {
		$this->add(
			'magick-ai-abilities-read',
			array(
				'label'       => __( 'Magick AI Abilities: Read', 'magick-ai-abilities' ),
				'description' => __( 'Read-only abilities for discovery, diagnostics, and context retrieval.', 'magick-ai-abilities' ),
			)
		);
		$this->add(
			'magick-ai-abilities-write',
			array(
				'label'       => __( 'Magick AI Abilities: Write Proposals', 'magick-ai-abilities' ),
				'description' => __( 'Write-like abilities that produce proposals instead of committing changes directly.', 'magick-ai-abilities' ),
			)
		);
		$this->add(
			'magick-ai-abilities-tools',
			array(
				'label'       => __( 'Magick AI Abilities: Tools', 'magick-ai-abilities' ),
				'description' => __( 'Tool-backed abilities for agent workflows.', 'magick-ai-abilities' ),
			)
		);
	}
}
