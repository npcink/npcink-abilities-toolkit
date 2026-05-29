<?php
/**
 * Core comment sub-pack classifier.
 *
 * @package MagickAIAbilities
 */

namespace Magick_AI_Abilities\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classifies built-in comment helper abilities into host-selectable packs.
 */
final class Core_Comment_Pack_Classifier {
	/**
	 * Returns the default enabled comment-helper sub-packs.
	 *
	 * @return string[]
	 */
	public static function default_packs() {
		return array(
			'comment_queue_context',
			'comment_handoff_context',
		);
	}

	/**
	 * Classifies a built-in comment helper ability into a coarse sub-pack.
	 *
	 * @param string $ability_id Ability id.
	 * @return string
	 */
	public static function classify( $ability_id ) {
		$ability_id = (string) $ability_id;

		if ( false !== strpos( $ability_id, 'handoff' ) || false !== strpos( $ability_id, 'compliance' ) ) {
			return 'comment_handoff_context';
		}

		return 'comment_queue_context';
	}
}
