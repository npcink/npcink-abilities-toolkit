<?php
/**
 * Bounded flagged-media review evidence.
 * @package NpcinkAbilitiesToolkit
 */
namespace Npcink_Abilities_Toolkit\Packages;
if ( ! defined( 'ABSPATH' ) ) { exit; }
trait Flagged_Media_Read_Methods {
	/**
	 * Descriptive evidence only. These bounded scans are explicitly not exhaustive.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function flagged_media_reference_summary( int $attachment_id ) {
		if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $attachment_id ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to review flagged media references.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}
		$usage = $this->build_media_usage_context( $attachment_id );
		$url = (string) wp_get_attachment_url( $attachment_id );
		$settings = array();
		if ( '' !== $url ) {
			foreach ( $this->media_settings_reference_repair_candidates( array(), 50 ) as $candidate ) {
				if ( false !== strpos( $this->media_settings_reference_value_text( $candidate['value'] ?? null ), $url ) ) {
					$settings[] = array( 'target_type' => $candidate['target_type'], 'target_name' => $candidate['target_name'] );
				}
			}
		}
		return array( 'posts' => (array) ( $usage['content_reference_posts'] ?? array() ), 'featured_images' => (array) ( $usage['featured_image_posts'] ?? array() ), 'settings' => $settings, 'scan_complete' => false, 'scan_limits' => array( 'content_candidates' => 50, 'content_matches' => 10, 'featured_matches' => 5, 'settings_candidates' => 50 ), 'operator_review_required' => true );
	}
	/** @param mixed $input @return array<string,mixed>|\WP_Error */
	public function build_flagged_media_removal_plan( $input ) {
		$input = is_array( $input ) ? $input : array();
		$delete_input = array();
		foreach ( array( 'attachment_id', 'deletion_policy_branch', 'reason', 'idempotency_key' ) as $field ) { $delete_input[ $field ] = $input[ 'idempotency_key' === $field ? 'deletion_request_key' : $field ] ?? null; }
		$delete_input['dry_run'] = true;
		$delete_input['commit'] = false;
		if ( 'illegal_content_no_backup' === $delete_input['deletion_policy_branch'] && true !== ( $input['no_backup_confirmed'] ?? false ) ) { return \Npcink_Abilities_Toolkit\Support\Flagged_Media_Backup::error( 'operator_no_backup_confirmation_required', 400 ); }
		$destructive = new Core_Destructive_Package( $this->categories, $this->abilities );
		$preview = $destructive->delete_attachment( $delete_input );
		if ( is_wp_error( $preview ) ) { return $preview; }
		$delete_input['reason'] = trim( (string) $delete_input['reason'] );
		$delete_input['idempotency_key'] = trim( (string) $delete_input['idempotency_key'] );
		$patches = $input['reference_patches'] ?? array();
		if ( ! is_array( $patches ) || count( $patches ) > 10 ) { return \Npcink_Abilities_Toolkit\Support\Flagged_Media_Backup::error( 'reference_patch_limit', 400 ); }
		$files = \Npcink_Abilities_Toolkit\Support\Flagged_Media_Backup::attachment_files( (int) $delete_input['attachment_id'], false );
		if ( is_wp_error( $files ) ) { return $files; }
		$upload = wp_upload_dir();
		$urls = array( (string) wp_get_attachment_url( (int) $delete_input['attachment_id'] ) );
		foreach ( $files as $file ) { $urls[] = rtrim( (string) $upload['baseurl'], '/' ) . '/' . $file; }
		$write = new Core_Write_Package( $this->categories, $this->abilities );
		$actions = array();
		foreach ( $patches as $index => $patch ) {
			if ( ! is_array( $patch ) ) { return \Npcink_Abilities_Toolkit\Support\Flagged_Media_Backup::error( 'reference_patch_invalid', 400 ); }
			$ability = (string) ( $patch['target_ability_id'] ?? '' );
			$patch_input = array( 'dry_run' => true, 'commit' => false, 'operations' => $patch['operations'] ?? array() );
			if ( ! is_array( $patch_input['operations'] ) || empty( $patch_input['operations'] ) || count( $patch_input['operations'] ) > 20 ) { return \Npcink_Abilities_Toolkit\Support\Flagged_Media_Backup::error( 'reference_operations_invalid', 400 ); }
			foreach ( $patch_input['operations'] as &$operation ) {
				if ( ! is_array( $operation ) || 'replace' !== ( $operation['op'] ?? '' ) || '' !== ( $operation['replace'] ?? null ) || ! in_array( $operation['find'] ?? null, $urls, true ) ) { return \Npcink_Abilities_Toolkit\Support\Flagged_Media_Backup::error( 'reference_url_not_attachment', 400 ); }
				$operation = array( 'op' => 'replace', 'find' => $operation['find'], 'replace' => '', 'case_sensitive' => true, 'limit' => max( 1, min( 20, (int) ( $operation['limit'] ?? 1 ) ) ) );
			}
			unset( $operation );
			if ( 'npcink-abilities-toolkit/patch-post-content' === $ability ) {
				$patch_input['post_id'] = absint( $patch['post_id'] ?? 0 );
				$result = $write->patch_post_content( $patch_input );
			} elseif ( 'npcink-abilities-toolkit/patch-setting-value' === $ability ) {
				$patch_input['target_type'] = (string) ( $patch['target_type'] ?? '' );
				$patch_input['target_name'] = (string) ( $patch['target_name'] ?? '' );
				$result = $write->patch_setting_value( $patch_input );
			} else { return \Npcink_Abilities_Toolkit\Support\Flagged_Media_Backup::error( 'reference_target_not_allowed', 400 ); }
			if ( is_wp_error( $result ) ) { return $result; }
			$actions[] = array( 'action_id' => 'remove_reference_' . $index, 'target_ability_id' => $ability, 'input' => $patch_input );
		}
		$actions[] = array( 'action_id' => 'delete_attachment', 'target_ability_id' => 'npcink-abilities-toolkit/delete-attachment', 'input' => $delete_input );
		$context = $delete_input;
		unset( $context['dry_run'], $context['commit'] );
		foreach ( array( 'backup_evidence', 'no_backup_attestation' ) as $field ) { if ( isset( $preview['preview'][ $field ] ) ) { $context[ $field ] = $preview['preview'][ $field ]; } }
		$plan = array( 'artifact_type' => 'flagged_media_removal_plan', 'contract_version' => 'flagged_media_removal_plan.v1', 'plan_contract_version' => 'flagged_media_removal_plan.v1', 'readonly' => true, 'requires_approval' => true, 'commit_execution' => false, 'dry_run' => true, 'batch_approval' => true, 'proposal_mode' => 'batch', 'flagged_media_removal' => $context, 'write_actions' => $actions, 'preview' => $preview['preview'], 'manual_review_required' => true );
		return $this->build_analysis_success_response( $plan, array( 'source' => 'local_flagged_media_removal_plan', 'execution_mode' => 'deterministic', 'plan_only' => true ), 'Flagged media removal plan built.' );
	}

}
