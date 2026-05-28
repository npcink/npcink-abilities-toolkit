<?php
/**
 * Plugin composition root.
 *
 * @package MagickAIAbilities
 */

namespace Magick_AI_Abilities;

use Magick_AI_Abilities\Admin\Test_Page;
use Magick_AI_Abilities\Integration\Magick_Catalog_Bridge;
use Magick_AI_Abilities\Packages\Core_Comment_Package;
use Magick_AI_Abilities\Packages\Core_Destructive_Package;
use Magick_AI_Abilities\Packages\Core_Read_Package;
use Magick_AI_Abilities\Packages\Core_Write_Package;
use Magick_AI_Abilities\Registry\Ability_Registrar;
use Magick_AI_Abilities\Registry\Annotation_Normalizer;
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
	 * Core WordPress read package.
	 *
	 * @var Core_Read_Package
	 */
	private $core_read_package;

	/**
	 * Core WordPress write package.
	 *
	 * @var Core_Write_Package
	 */
	private $core_write_package;

	/**
	 * Core WordPress destructive package.
	 *
	 * @var Core_Destructive_Package
	 */
	private $core_destructive_package;

	/**
	 * Core WordPress comment helper package.
	 *
	 * @var Core_Comment_Package
	 */
	private $core_comment_package;

	/**
	 * Admin test page.
	 *
	 * @var Test_Page
	 */
	private $test_page;

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
		$contract_normalizer = new Contract_Normalizer( $schema_normalizer, new Annotation_Normalizer() );

		$this->categories     = new Category_Registrar();
		$this->abilities      = new Ability_Registrar( $this->categories, $contract_normalizer );
		$this->catalog_bridge = new Magick_Catalog_Bridge( $this->abilities );
		$this->core_read_package = new Core_Read_Package( $this->categories, $this->abilities );
		$this->core_write_package = new Core_Write_Package( $this->categories, $this->abilities );
		$this->core_destructive_package = new Core_Destructive_Package( $this->categories, $this->abilities );
		$this->core_comment_package = new Core_Comment_Package( $this->categories, $this->abilities );
		$this->test_page      = new Test_Page( $this->abilities );
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
		$this->core_read_package->boot();
		$this->core_write_package->boot();
		$this->core_destructive_package->boot();
		$this->core_comment_package->boot();
		$this->catalog_bridge->boot();
		$this->test_page->boot();
		$this->register_cache_invalidation_hooks();
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

	/**
	 * Registers read-cache invalidation hooks when WordPress hooks are available.
	 *
	 * @return void
	 */
	private function register_cache_invalidation_hooks() {
		if ( ! function_exists( 'add_action' ) ) {
			return;
		}

		add_action( 'save_post', array( $this, 'bump_read_cache_version' ), 20 );
		add_action( 'deleted_post', array( $this, 'bump_read_cache_version' ), 20 );
		add_action( 'transition_post_status', array( $this, 'bump_read_cache_version' ), 20 );
		add_action( 'created_term', array( $this, 'bump_read_cache_version' ), 20 );
		add_action( 'edited_term', array( $this, 'bump_read_cache_version' ), 20 );
		add_action( 'delete_term', array( $this, 'bump_read_cache_version' ), 20 );
		add_action( 'add_attachment', array( $this, 'bump_read_cache_version' ), 20 );
		add_action( 'edit_attachment', array( $this, 'bump_read_cache_version' ), 20 );
		add_action( 'delete_attachment', array( $this, 'bump_read_cache_version' ), 20 );
	}

	/**
	 * Bumps the read-cache version for bounded read-only report transients.
	 *
	 * @return void
	 */
	public function bump_read_cache_version() {
		if ( ! function_exists( 'get_option' ) || ! function_exists( 'update_option' ) ) {
			return;
		}

		$current = max( 1, (int) get_option( 'magick_ai_abilities_read_cache_version', 1 ) );
		update_option( 'magick_ai_abilities_read_cache_version', $current + 1, false );
	}
}
