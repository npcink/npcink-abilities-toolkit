<?php
/**
 * Context-aware, one-time built-in package registration for Plugin.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defers frontend boot until WordPress establishes a REST runtime context.
 */
trait Runtime_Packages {
	/** @var bool */
	private $runtime_packages_booted = false;

	/**
	 * Registers enabled packages at init or before REST registry consumption.
	 *
	 * @return void
	 */
	public function boot_runtime_packages() {
		if ( $this->runtime_packages_booted ) {
			return;
		}
		$register_packages = $this->abilities->runtime_context_active();
		if ( function_exists( 'apply_filters' ) ) {
			/**
			 * Filters whether built-in ability packages register on anonymous
			 * frontend contexts, where abilities never run; hosts that run
			 * abilities programmatically on frontend requests return true to
			 * restore registration everywhere.
			 *
			 * @param bool $register_packages Whether frontend contexts register built-in abilities.
			 */
			$register_packages = (bool) apply_filters( 'npcink_abilities_toolkit_register_frontend_contexts', $register_packages );
		}
		if ( ! $register_packages ) {
			if ( ! $this->is_package_enabled( 'core_write' ) ) {
				$this->clear_media_backup_cleanup_schedule();
			}
			return;
		}
		$this->runtime_packages_booted = true;
		if ( $this->is_package_enabled( 'core_read' ) ) {
			$this->core_read_package()->boot();
		}
		if ( $this->is_package_enabled( 'core_write' ) ) {
			$this->core_write_package()->boot();
			$this->schedule_media_backup_cleanup();
		} elseif ( ! $this->is_package_enabled( 'core_write' ) ) {
			$this->clear_media_backup_cleanup_schedule();
		}
		if ( $this->is_package_enabled( 'core_destructive' ) ) {
			$this->core_destructive_package()->boot();
		}
		if ( $this->is_package_enabled( 'core_comment' ) ) {
			$this->core_comment_package()->boot();
		}
	}

	private function schedule_media_backup_cleanup() {
		if ( ! function_exists( 'wp_next_scheduled' ) || ! function_exists( 'wp_schedule_event' ) ) {
			return;
		}
		$hook = 'npcink_abilities_toolkit_cleanup_media_backups';
		if ( ! wp_next_scheduled( $hook ) ) {
			wp_schedule_event( time() + ( defined( 'HOUR_IN_SECONDS' ) ? HOUR_IN_SECONDS : 3600 ), 'daily', $hook );
		}
	}

	private function clear_media_backup_cleanup_schedule() {
		if ( ! function_exists( 'wp_next_scheduled' ) || ! function_exists( 'wp_clear_scheduled_hook' ) ) {
			return;
		}
		$hook = 'npcink_abilities_toolkit_cleanup_media_backups';
		if ( wp_next_scheduled( $hook ) ) {
			wp_clear_scheduled_hook( $hook );
		}
	}

}
