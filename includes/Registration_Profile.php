<?php
/**
 * Optional, narrowing-only built-in registration profiles.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Groups existing package controls; stores no ability or runtime state.
 * Internal implementation: hosts select via the documented filter only.
 */
final class Registration_Profile {
	/** @return array<string,bool> Compatible full-profile defaults. */
	public static function defaults() {
		return array(
			'core_read'             => true,
			'core_write'            => true,
			'core_destructive'      => true,
			'core_comment'          => true,
			'npcink_catalog_bridge' => true,
			'admin_test_page'       => true,
			'read_cache_hooks'      => true,
		);
	}

	/** @return string Selected profile; invalid selections narrow to core reads. */
	private static function selected() {
		/**
		 * Selects built-in registration breadth before plugins_loaded.
		 *
		 * @param string $profile full (compatible default), read_only, or core_read.
		 */
		$profile = apply_filters( 'npcink_abilities_toolkit_registration_profile', 'full' );
		return in_array( $profile, array( 'full', 'read_only', 'core_read' ), true ) ? $profile : 'core_read';
	}

	/**
	 * Intersects the host's existing package selection with the profile.
	 *
	 * @param array<string,bool> $enabled Host-filtered packages.
	 * @return array<string,bool>
	 */
	public static function narrow_packages( array $enabled ) {
		$profile = self::selected();
		$off     = array();
		if ( 'full' !== $profile ) {
			$off = array( 'core_write', 'core_destructive' );
		}
		if ( 'core_read' === $profile ) {
			$off = array_merge( $off, array( 'core_comment', 'npcink_catalog_bridge' ) );
		}
		foreach ( $off as $package ) {
			$enabled[ $package ] = false;
		}
		return $enabled;
	}

	/**
	 * Bounds the read sub-pack after per-ability host filters.
	 *
	 * @param string $pack Read sub-pack.
	 * @return bool
	 */
	public static function allows_read_pack( $pack ) {
		return 'core_read' !== self::selected() || 'core_wordpress_read' === $pack;
	}
}
