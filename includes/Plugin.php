<?php
/**
 * Plugin composition root.
 *
 * @package MagickAIAbilities
 */

namespace Magick_AI_Abilities;

use Magick_AI_Abilities\Integration\Magick_Catalog_Bridge;
use Magick_AI_Abilities\Registry\Ability_Registrar;
use Magick_AI_Abilities\Registry\Category_Registrar;
use Magick_AI_Abilities\Registry\Contract_Normalizer;
use Magick_AI_Abilities\Registry\Schema_Normalizer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wires the public API to WordPress hooks.
 */
final class Plugin {
	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Category registrar.
	 *
	 * @var Category_Registrar
	 */
	private $categories;

	/**
	 * Ability registrar.
	 *
	 * @var Ability_Registrar
	 */
	private $abilities;

	/**
	 * Magick catalog bridge.
	 *
	 * @var Magick_Catalog_Bridge
	 */
	private $catalog_bridge;

	/**
	 * Whether hooks have been registered.
	 *
	 * @var bool
	 */
	private $booted = false;

	/**
	 * Returns the shared plugin instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$schema_normalizer   = new Schema_Normalizer();
		$contract_normalizer = new Contract_Normalizer( $schema_normalizer );

		$this->categories     = new Category_Registrar();
		$this->abilities      = new Ability_Registrar( $this->categories, $contract_normalizer );
		$this->catalog_bridge = new Magick_Catalog_Bridge( $this->abilities );
	}

	/**
	 * Registers WordPress hooks.
	 *
	 * @return void
	 */
	public function boot() {
		if ( $this->booted ) {
			return;
		}

		$this->booted = true;
		$this->categories->boot();
		$this->abilities->boot();
		$this->catalog_bridge->boot();
	}

	/**
	 * Returns the category registrar.
	 *
	 * @return Category_Registrar
	 */
	public function categories() {
		return $this->categories;
	}

	/**
	 * Returns the ability registrar.
	 *
	 * @return Ability_Registrar
	 */
	public function abilities() {
		return $this->abilities;
	}
}
