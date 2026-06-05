<?php
/**
 * Core comment sub-pack classifier.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Packages;

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
	 * Returns the explicit built-in comment helper to sub-pack map.
	 *
	 * @return array<string,string>
	 */
	public static function known_pack_map() {
		return array(
			'npcink-abilities-toolkit/build-comment-moderation-suggest'       => 'comment_queue_context',
			'npcink-abilities-toolkit/compose-comment-moderation-result'     => 'comment_queue_context',
			'npcink-abilities-toolkit/build-comment-mention-reply-suggest'   => 'comment_queue_context',
			'npcink-abilities-toolkit/read-comment-trigger-queue'            => 'comment_queue_context',
			'npcink-abilities-toolkit/get-comment-queue-health'              => 'comment_queue_context',
			'npcink-abilities-toolkit/get-comment-action-priority-queue'     => 'comment_queue_context',
			'npcink-abilities-toolkit/get-comment-compliance-handoff'        => 'comment_handoff_context',
			'npcink-abilities-toolkit/compose-comment-mention-reply-result'  => 'comment_queue_context',
			'npcink-abilities-toolkit/build-comment-moderation-batch-suggest' => 'comment_queue_context',
			'npcink-abilities-toolkit/compose-comment-moderation-batch-result' => 'comment_queue_context',
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
		$known      = self::known_pack_map();

		if ( isset( $known[ $ability_id ] ) ) {
			return $known[ $ability_id ];
		}

		if ( false !== strpos( $ability_id, 'handoff' ) || false !== strpos( $ability_id, 'compliance' ) ) {
			return 'comment_handoff_context';
		}

		return 'comment_queue_context';
	}
}
