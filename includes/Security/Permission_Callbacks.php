<?php
/**
 * Permission callback factories.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds predictable permission callbacks for abilities.
 */
final class Permission_Callbacks {
	/**
	 * Builds a capability-based permission callback.
	 *
	 * @param string $capability Required WordPress capability.
	 * @return callable
	 */
	public static function for_capability( $capability ) {
		$capability = sanitize_key( (string) $capability );
		if ( '' === $capability ) {
			$capability = 'manage_options';
		}

		return static function () use ( $capability ) {
			return current_user_can( $capability );
		};
	}
}
