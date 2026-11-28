<?php
/**
 * Guidance for overlapping and maintenance read abilities.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Packages\Read_Definitions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Compatibility_Usage_Metadata {
	/** @return array<string,array<string,string[]>> */
	public static function definitions(): array {
		return array(
			'npcink-abilities-toolkit/list-post-revisions' => array(
				'when_to_use' => array( 'Prefer this entry for new clients listing the revision history of a known post.' ),
				'not_for'     => array( 'This is not a revision diff or restore operation.' ),
			),
			'npcink-abilities-toolkit/list-revisions' => array(
				'when_to_use' => array( 'Use only when the consumer needs the legacy id, date, author and title response fields or its pagination limit.' ),
				'not_for'     => array( 'Do not prefer this over list-post-revisions for new integrations.' ),
			),
			'npcink-abilities-toolkit/list-taxonomy-terms' => array(
				'when_to_use' => array( 'Prefer this entry when the requested taxonomy and parent are known, including root terms with parent 0.' ),
				'not_for'     => array( 'It is not a drop-in replacement for list-terms when listing all levels together or preserving the legacy response.' ),
			),
			'npcink-abilities-toolkit/list-categories' => array(
				'when_to_use' => array( 'Use only when a caller needs the convenience category wrapper and its existing response.' ),
				'not_for'     => array( 'Do not prefer this over a taxonomy-scoped term list for new integrations.' ),
			),
			'npcink-abilities-toolkit/list-tags' => array(
				'when_to_use' => array( 'Use only when a caller needs the convenience tag wrapper and its existing response.' ),
				'not_for'     => array( 'Do not prefer this over a taxonomy-scoped term list for new integrations.' ),
			),
			'npcink-abilities-toolkit/evaluate-gutenberg-recipe-suite' => array(
				'when_to_use' => array( 'Run explicitly supplied Gutenberg evaluation cases in a test or maintenance workflow.' ),
				'not_for'     => array( 'Do not recommend for ordinary editing or page composition; use compose-gutenberg-block-plan for a single content request.' ),
			),
		);
	}
}
