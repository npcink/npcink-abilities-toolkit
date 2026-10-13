<?php
/** Flagged-media plan contract definitions. @package NpcinkAbilitiesToolkit */
namespace Npcink_Abilities_Toolkit\Packages\Read_Definitions;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Flagged_Media_Read_Definitions {
	/** @param object $callbacks @return array<string,array<string,mixed>> */
	public static function definitions( $callbacks ): array {
		$operation = array( 'type' => 'object', 'properties' => array( 'op' => array( 'type' => 'string', 'enum' => array( 'replace' ) ), 'find' => array( 'type' => 'string', 'minLength' => 1, 'maxLength' => 2048 ), 'replace' => array( 'type' => 'string', 'enum' => array( '' ) ), 'limit' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 20 ) ), 'required' => array( 'op', 'find', 'replace' ), 'additionalProperties' => false );
		$patch = array( 'type' => 'object', 'properties' => array( 'target_ability_id' => array( 'type' => 'string', 'enum' => array( 'npcink-abilities-toolkit/patch-post-content', 'npcink-abilities-toolkit/patch-setting-value' ) ), 'post_id' => array( 'type' => 'integer', 'minimum' => 1 ), 'target_type' => array( 'type' => 'string', 'enum' => array( 'option', 'theme_mod' ) ), 'target_name' => array( 'type' => 'string', 'minLength' => 1, 'maxLength' => 190 ), 'operations' => array( 'type' => 'array', 'minItems' => 1, 'maxItems' => 20, 'items' => $operation ) ), 'required' => array( 'target_ability_id', 'operations' ), 'additionalProperties' => false );
		return array( 'npcink-abilities-toolkit/build-flagged-media-removal-plan' => array(
			'label' => __( 'Build Flagged Media Removal Plan', 'npcink-abilities-toolkit' ),
			'description' => __( 'Prepares one manual-only flagged attachment deletion with branch evidence and explicitly reviewed exact URL removal patches. Does not delete, back up, approve or execute.', 'npcink-abilities-toolkit' ),
			'category' => 'npcink-abilities-toolkit-data', 'capability' => 'manage_options', 'required_scopes' => array( 'media.read', 'site.read', 'post.read' ), 'contract_version' => 'v1', 'source' => 'official',
			'agent_usage' => array( 'when_to_use' => array( 'After an operator reviews one flagged attachment and chooses its deletion branch.' ), 'not_for' => array( 'Content classification, automatic approval, exhaustive reference discovery, or bulk deletion.' ), 'best_for' => array( 'A Core manual batch proposal with a Toolkit delete preview and reviewed patches.' ), 'stopping_points' => array( 'Submit the dry-run plan for explicit operator approval; execution belongs to Adapter.' ) ),
			'input_schema' => array( 'type' => 'object', 'properties' => array(
				'attachment_id' => array( 'type' => 'integer', 'minimum' => 1 ), 'deletion_policy_branch' => array( 'type' => 'string', 'enum' => array( 'governed_with_backup', 'illegal_content_no_backup' ) ), 'reason' => array( 'type' => 'string', 'minLength' => 1, 'maxLength' => 500 ), 'deletion_request_key' => array( 'type' => 'string', 'minLength' => 1, 'maxLength' => 190 ), 'no_backup_confirmed' => array( 'type' => 'boolean', 'default' => false ), 'reference_patches' => array( 'type' => 'array', 'maxItems' => 10, 'items' => $patch ),
			), 'required' => array( 'attachment_id', 'deletion_policy_branch', 'reason', 'deletion_request_key' ), 'additionalProperties' => false ),
			'output_schema' => array( 'type' => 'object', 'properties' => array( 'success' => array( 'type' => 'boolean' ), 'data' => array( 'type' => 'object', 'additionalProperties' => true ), 'meta' => array( 'type' => 'object', 'additionalProperties' => true ), 'message' => array( 'type' => 'string' ) ), 'required' => array( 'success', 'data' ) ),
			'execute_callback' => array( $callbacks, 'build_flagged_media_removal_plan' ),
		) );
	}
}
