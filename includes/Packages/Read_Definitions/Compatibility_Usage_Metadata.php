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
				'when_to_use' => array( 'List the revision history of a known post.' ),
				'not_for'     => array( 'This is not a revision diff or restore operation.' ),
			),
			'npcink-abilities-toolkit/list-taxonomy-terms' => array(
				'when_to_use' => array( 'List one taxonomy: omit parent for all levels, pass 0 for roots, or pass a term ID for its direct children.' ),
				'not_for'     => array( 'This is not a cross-taxonomy or recursive subtree query.' ),
			),
			'npcink-abilities-toolkit/evaluate-gutenberg-recipe-suite' => array(
				'when_to_use' => array( 'Run explicitly supplied Gutenberg evaluation cases in a test or maintenance workflow.' ),
				'not_for'     => array( 'Do not recommend for ordinary editing or page composition; use compose-gutenberg-block-plan for a single content request.' ),
			),
		);
	}
}
