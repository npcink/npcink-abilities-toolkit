<?php
/**
 * Ordered regression suite part: 80-media-restore-alt-plans.
 *
 * Included by tests/run.php; parts must run in the original execution order.
 *
 * @package NpcinkAbilitiesToolkit
 */

use Npcink_Abilities_Toolkit\Integration\Npcink_Catalog_Bridge;
use Npcink_Abilities_Toolkit\Packages\Core_Comment_Pack_Classifier;
use Npcink_Abilities_Toolkit\Packages\Core_Comment_Package;
use Npcink_Abilities_Toolkit\Packages\Core_Destructive_Package;
use Npcink_Abilities_Toolkit\Packages\Core_Read_Pack_Classifier;
use Npcink_Abilities_Toolkit\Packages\Core_Read_Package;
use Npcink_Abilities_Toolkit\Packages\Core_Write_Package;
use Npcink_Abilities_Toolkit\Packages\Read_Definitions\Media_Governance_Read_Definitions;
use Npcink_Abilities_Toolkit\Plugin;
use Npcink_Abilities_Toolkit\Registry\Ability_Registrar;
use Npcink_Abilities_Toolkit\Registry\Annotation_Normalizer;
use Npcink_Abilities_Toolkit\Registry\Category_Registrar;
use Npcink_Abilities_Toolkit\Registry\Contract_Normalizer;
use Npcink_Abilities_Toolkit\Registry\Schema_Normalizer;
use Npcink_Abilities_Toolkit\Rest\Contract_Controller;
use Npcink_Abilities_Toolkit\Security\Permission_Callbacks;
use Npcink_Abilities_Toolkit\Support\Gutenberg_Block_Document;

$media_restore_original_pointer = (string) get_post_meta( 79, '_wp_attached_file', true );
$media_restore_original_mime = (string) get_post_mime_type( 79 );
$media_restore_original_metadata = wp_get_attachment_metadata( 79 );
$media_restore_original_history = get_post_meta( 79, '_npcink_ai_media_file_replacement_history', true );
$media_restore_original_latest = get_post_meta( 79, '_npcink_ai_media_latest_file_replacement', true );
$media_restore_target_path = $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/2026/06/workflow-diagram-image.jpg';
file_put_contents( $media_restore_target_path, 'preexisting-target-bytes' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][83] = (object) array(
	'ID'           => 83,
	'post_title'   => 'Media Restore Compensation Candidate',
	'post_status'  => 'publish',
	'post_type'    => 'post',
	'post_excerpt' => '',
	'post_content' => '<p><img src="https://example.test/wp-content/uploads/2026/06/workflow-diagram-image-optimized.webp" /></p>',
	'post_name'    => 'media-restore-compensation-candidate',
	'post_author'  => 7,
);
$media_restore_original_content = (string) $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][83]->post_content;
$restore_reference_failed_once = false;
$GLOBALS['npcink_abilities_toolkit_unit_wp_update_post_callback'] = static function ( array $postarr ) use ( &$restore_reference_failed_once ) {
	if ( 83 === (int) ( $postarr['ID'] ?? 0 ) && array_key_exists( 'post_content', $postarr ) && ! $restore_reference_failed_once ) {
		$restore_reference_failed_once = true;
		return new WP_Error( 'unit_media_restore_reference_failure', 'Injected restore reference failure.' );
	}
	return null;
};
$GLOBALS['npcink_ai_runtime_wp_ability_context']['context'] = array(
	'approval_commit_authorized' => true,
	'approval_id'                => 'approval-media-restore-compensation',
);
$media_restore_reference_failure = $core_write_package->restore_media_backup(
	array(
		'attachment_id'                  => 79,
		'backup_id'                      => 'media_replace_unit',
		'expected_current_relative_file' => $media_restore_original_pointer,
		'target_conflict_mode'           => 'overwrite',
		'commit'                         => true,
	)
);
unset( $GLOBALS['npcink_abilities_toolkit_unit_wp_update_post_callback'], $GLOBALS['npcink_ai_runtime_wp_ability_context'] );
npcink_abilities_toolkit_assert_true( is_wp_error( $media_restore_reference_failure ) && 'npcink_abilities_toolkit_cloud_adoption_mutation_conflict' === $media_restore_reference_failure->get_error_code(), 'restore-media-backup preserves the reference repair failure after complete compensation' );
npcink_abilities_toolkit_assert_same( $media_restore_original_pointer, get_post_meta( 79, '_wp_attached_file', true ), 'restore reference failure restores the attachment pointer' );
npcink_abilities_toolkit_assert_same( $media_restore_original_mime, get_post_mime_type( 79 ), 'restore reference failure restores the attachment MIME' );
npcink_abilities_toolkit_assert_same( $media_restore_original_metadata, wp_get_attachment_metadata( 79 ), 'restore reference failure restores attachment metadata' );
npcink_abilities_toolkit_assert_same( $media_restore_original_content, (string) $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][83]->post_content, 'restore reference failure restores affected post content' );
npcink_abilities_toolkit_assert_same( $media_restore_original_history, get_post_meta( 79, '_npcink_ai_media_file_replacement_history', true ), 'restore reference failure preserves replacement history' );
npcink_abilities_toolkit_assert_same( $media_restore_original_latest, get_post_meta( 79, '_npcink_ai_media_latest_file_replacement', true ), 'restore reference failure preserves latest replacement projection' );
npcink_abilities_toolkit_assert_same( 'preexisting-target-bytes', file_get_contents( $media_restore_target_path ), 'restore reference failure restores overwritten target bytes' );

$restore_late_drift_injected = false;
add_filter(
	'npcink_abilities_toolkit_media_file_copy_blocked',
	static function ( $blocked, $source_path, $target_path, $context ) use ( &$restore_late_drift_injected ) {
		unset( $source_path, $target_path );
		if ( ! $restore_late_drift_injected && is_array( $context ) && 'restore_media_backup' === (string) ( $context['operation'] ?? '' ) && 'backup_current' === (string) ( $context['step'] ?? '' ) ) {
			$restore_late_drift_injected = true;
			$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][83]->post_content .= '<p>Concurrent edit.</p>';
		}
		return $blocked;
	},
	10,
	4
);
$GLOBALS['npcink_ai_runtime_wp_ability_context']['context'] = array(
	'approval_commit_authorized' => true,
	'approval_id'                => 'approval-media-restore-drift',
);
$media_restore_late_drift = $core_write_package->restore_media_backup(
	array(
		'attachment_id'                  => 79,
		'backup_id'                      => 'media_replace_unit',
		'expected_current_relative_file' => $media_restore_original_pointer,
		'target_conflict_mode'           => 'overwrite',
		'commit'                         => true,
	)
);
remove_all_filters( 'npcink_abilities_toolkit_media_file_copy_blocked' );
unset( $GLOBALS['npcink_ai_runtime_wp_ability_context'] );
npcink_abilities_toolkit_assert_true(
	is_wp_error( $media_restore_late_drift ) && 'npcink_abilities_toolkit_media_restore_precommit_drift' === $media_restore_late_drift->get_error_code(),
	'restore-media-backup blocks a concurrent post-content change at the late precommit gate; got ' . ( is_wp_error( $media_restore_late_drift ) ? $media_restore_late_drift->get_error_code() : gettype( $media_restore_late_drift ) )
);
npcink_abilities_toolkit_assert_same( $media_restore_original_pointer, get_post_meta( 79, '_wp_attached_file', true ), 'late restore drift leaves the attachment pointer unchanged' );
npcink_abilities_toolkit_assert_same( 'preexisting-target-bytes', file_get_contents( $media_restore_target_path ), 'late restore drift leaves target bytes unchanged' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][83]->post_content = $media_restore_original_content;

$restore_history_failed_once = false;
$GLOBALS['npcink_abilities_toolkit_unit_update_post_meta_callback'] = static function ( $post_id, $meta_key ) use ( &$restore_history_failed_once ) {
	if ( 79 === (int) $post_id && '_npcink_ai_media_file_replacement_history' === (string) $meta_key && ! $restore_history_failed_once ) {
		$restore_history_failed_once = true;
		return false;
	}
	return null;
};
$GLOBALS['npcink_ai_runtime_wp_ability_context']['context'] = array(
	'approval_commit_authorized' => true,
	'approval_id'                => 'approval-media-restore-history-failure',
);
$media_restore_history_failure = $core_write_package->restore_media_backup(
	array(
		'attachment_id'                  => 79,
		'backup_id'                      => 'media_replace_unit',
		'expected_current_relative_file' => $media_restore_original_pointer,
		'target_conflict_mode'           => 'overwrite',
		'commit'                         => true,
	)
);
unset( $GLOBALS['npcink_abilities_toolkit_unit_update_post_meta_callback'], $GLOBALS['npcink_ai_runtime_wp_ability_context'] );
npcink_abilities_toolkit_assert_true( is_wp_error( $media_restore_history_failure ) && 'npcink_abilities_toolkit_cloud_adoption_mutation_conflict' === $media_restore_history_failure->get_error_code(), 'restore-media-backup preserves a history CAS failure after complete compensation' );
npcink_abilities_toolkit_assert_same( $media_restore_original_pointer, get_post_meta( 79, '_wp_attached_file', true ), 'restore history failure restores the attachment pointer' );
npcink_abilities_toolkit_assert_same( $media_restore_original_mime, get_post_mime_type( 79 ), 'restore history failure restores the attachment MIME' );
npcink_abilities_toolkit_assert_same( $media_restore_original_metadata, wp_get_attachment_metadata( 79 ), 'restore history failure restores attachment metadata' );
npcink_abilities_toolkit_assert_same( $media_restore_original_content, (string) $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][83]->post_content, 'restore history failure restores affected post content' );
npcink_abilities_toolkit_assert_same( $media_restore_original_history, get_post_meta( 79, '_npcink_ai_media_file_replacement_history', true ), 'restore history failure preserves replacement history' );
npcink_abilities_toolkit_assert_same( $media_restore_original_latest, get_post_meta( 79, '_npcink_ai_media_latest_file_replacement', true ), 'restore history failure preserves latest replacement projection' );
npcink_abilities_toolkit_assert_same( 'preexisting-target-bytes', file_get_contents( $media_restore_target_path ), 'restore history failure restores overwritten target bytes' );

$media_restore_source_backup_relative = '';
foreach ( $media_restore_original_history as $media_restore_history_record ) {
	if ( 'media_replace_unit' === (string) ( $media_restore_history_record['replacement_id'] ?? '' ) ) {
		$media_restore_source_backup_relative = (string) ( $media_restore_history_record['backup']['relative_file'] ?? '' );
		break;
	}
}
$media_restore_source_backup_path = $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/' . $media_restore_source_backup_relative;
$media_restore_source_backup_bytes = file_get_contents( $media_restore_source_backup_path );
$restore_backup_drift_injected = false;
add_filter(
	'npcink_abilities_toolkit_media_file_copy_blocked',
	static function ( $blocked, $source_path, $target_path, $context ) use ( &$restore_backup_drift_injected, $media_restore_source_backup_path ) {
		unset( $source_path, $target_path );
		if ( ! $restore_backup_drift_injected && is_array( $context ) && 'restore_media_backup' === (string) ( $context['operation'] ?? '' ) && 'backup_current' === (string) ( $context['step'] ?? '' ) ) {
			$restore_backup_drift_injected = true;
			file_put_contents( $media_restore_source_backup_path, 'concurrent-backup-drift' );
		}
		return $blocked;
	},
	10,
	4
);
$GLOBALS['npcink_ai_runtime_wp_ability_context']['context'] = array(
	'approval_commit_authorized' => true,
	'approval_id'                => 'approval-media-restore-backup-drift',
);
$media_restore_backup_drift = $core_write_package->restore_media_backup(
	array(
		'attachment_id'                  => 79,
		'backup_id'                      => 'media_replace_unit',
		'expected_current_relative_file' => $media_restore_original_pointer,
		'target_conflict_mode'           => 'overwrite',
		'commit'                         => true,
	)
);
remove_all_filters( 'npcink_abilities_toolkit_media_file_copy_blocked' );
unset( $GLOBALS['npcink_ai_runtime_wp_ability_context'] );
npcink_abilities_toolkit_assert_true( is_wp_error( $media_restore_backup_drift ) && 'npcink_abilities_toolkit_media_restore_precommit_drift' === $media_restore_backup_drift->get_error_code(), 'restore-media-backup blocks source backup drift at the late precommit gate' );
npcink_abilities_toolkit_assert_same( $media_restore_original_pointer, get_post_meta( 79, '_wp_attached_file', true ), 'source backup drift leaves the attachment pointer unchanged' );
npcink_abilities_toolkit_assert_same( 'preexisting-target-bytes', file_get_contents( $media_restore_target_path ), 'source backup drift leaves target bytes unchanged' );
file_put_contents( $media_restore_source_backup_path, $media_restore_source_backup_bytes );

$restore_target_drift_injected = false;
add_filter(
	'npcink_abilities_toolkit_media_file_copy_blocked',
	static function ( $blocked, $source_path, $target_path, $context ) use ( &$restore_target_drift_injected, $media_restore_target_path ) {
		unset( $source_path, $target_path );
		if ( ! $restore_target_drift_injected && is_array( $context ) && 'restore_media_backup' === (string) ( $context['operation'] ?? '' ) && 'backup_restore_target' === (string) ( $context['step'] ?? '' ) ) {
			$restore_target_drift_injected = true;
			file_put_contents( $media_restore_target_path, 'concurrent-target-winner' );
		}
		return $blocked;
	},
	10,
	4
);
$GLOBALS['npcink_ai_runtime_wp_ability_context']['context'] = array(
	'approval_commit_authorized' => true,
	'approval_id'                => 'approval-media-restore-target-drift',
);
$media_restore_target_drift = $core_write_package->restore_media_backup(
	array(
		'attachment_id'                  => 79,
		'backup_id'                      => 'media_replace_unit',
		'expected_current_relative_file' => $media_restore_original_pointer,
		'target_conflict_mode'           => 'overwrite',
		'commit'                         => true,
	)
);
remove_all_filters( 'npcink_abilities_toolkit_media_file_copy_blocked' );
unset( $GLOBALS['npcink_ai_runtime_wp_ability_context'] );
npcink_abilities_toolkit_assert_true( is_wp_error( $media_restore_target_drift ) && 'npcink_abilities_toolkit_media_restore_precommit_drift' === $media_restore_target_drift->get_error_code(), 'restore-media-backup blocks target drift during compensation copy' );
npcink_abilities_toolkit_assert_same( $media_restore_original_pointer, get_post_meta( 79, '_wp_attached_file', true ), 'target drift during compensation copy leaves the attachment pointer unchanged' );
npcink_abilities_toolkit_assert_same( 'concurrent-target-winner', file_get_contents( $media_restore_target_path ), 'target drift during compensation copy preserves the concurrent winner bytes' );
file_put_contents( $media_restore_target_path, 'preexisting-target-bytes' );

$restore_compensation_pattern = $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/2026/06/*npcink-abilities-toolkit-restore-compensation*';
$restore_compensation_before = glob( $restore_compensation_pattern );
$GLOBALS['npcink_ai_runtime_wp_ability_context']['context'] = array(
	'approval_commit_authorized' => true,
	'approval_id'                => 'approval-media-restore-success',
);
$media_restore_success = $core_write_package->restore_media_backup(
	array(
		'attachment_id'                  => 79,
		'backup_id'                      => 'media_replace_unit',
		'expected_current_relative_file' => $media_restore_original_pointer,
		'target_conflict_mode'           => 'overwrite',
		'commit'                         => true,
	)
);
unset( $GLOBALS['npcink_ai_runtime_wp_ability_context'] );
$media_restore_success_history = get_post_meta( 79, '_npcink_ai_media_file_replacement_history', true );
$media_restore_success_original = array_values( array_filter( $media_restore_success_history, static function ( $record ) { return 'media_replace_unit' === (string) ( $record['replacement_id'] ?? '' ); } ) );
$media_restore_success_latest = get_post_meta( 79, '_npcink_ai_media_latest_file_replacement', true );
$media_restore_current_backup_path = $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/' . (string) ( $media_restore_success['current_backup']['relative_file'] ?? '' );
npcink_abilities_toolkit_assert_same( true, $media_restore_success['restored'] ?? null, 'restore-media-backup success reports restored=true' );
npcink_abilities_toolkit_assert_same( true, $media_restore_success['rolled_back'] ?? null, 'restore-media-backup success reports rolled_back=true' );
npcink_abilities_toolkit_assert_same( '2026/06/workflow-diagram-image.jpg', get_post_meta( 79, '_wp_attached_file', true ), 'restore-media-backup success switches to the original attachment pointer' );
npcink_abilities_toolkit_assert_same( $media_restore_source_backup_bytes, file_get_contents( $media_restore_target_path ), 'restore-media-backup success restores the selected backup bytes' );
npcink_abilities_toolkit_assert_true( is_file( $media_restore_current_backup_path ), 'restore-media-backup success retains the new rollback backup' );
npcink_abilities_toolkit_assert_same( 'rolled_back', $media_restore_success_original[0]['status'] ?? '', 'restore-media-backup success marks the selected replacement rolled back' );
npcink_abilities_toolkit_assert_same( 'active', $media_restore_success_latest['status'] ?? '', 'restore-media-backup success records an active restore history projection' );
npcink_abilities_toolkit_assert_same( 'media_replace_unit', $media_restore_success_latest['restored_from'] ?? '', 'restore-media-backup success links the restore record to its selected backup' );
npcink_abilities_toolkit_assert_true( 1 === preg_match( '/^sha256:[a-f0-9]{64}$/', (string) ( $media_restore_success_latest['new_media_fingerprint'] ?? '' ) ) && 1 === preg_match( '/^sha256:[a-f0-9]{64}$/', (string) ( $media_restore_success_latest['derived_from_media_fingerprint'] ?? '' ) ), 'restore-media-backup success records canonical source and target fingerprints in restore lineage' );
npcink_abilities_toolkit_assert_same( 'requires_reidentification', $media_restore_success_latest['visual_reuse_policy'] ?? '', 'restore-media-backup marks restored media as requiring fresh visual identification' );
npcink_abilities_toolkit_assert_same( 'manual_confirmation_required', $media_restore_success_latest['backup_cleanup_policy'] ?? '', 'restore history inherits the exact-manifest manual cleanup policy' );
npcink_abilities_toolkit_assert_same( $restore_compensation_before, glob( $restore_compensation_pattern ), 'restore-media-backup success removes its transient overwrite compensation file' );

$manual_retention_relative = 'npcink-abilities-toolkit-backups/2026/06/manual-retention.jpg';
$automatic_retention_relative = 'npcink-abilities-toolkit-backups/2026/06/automatic-retention.jpg';
$manual_retention_path = $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/' . $manual_retention_relative;
$automatic_retention_path = $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/' . $automatic_retention_relative;
file_put_contents( $manual_retention_path, 'manual-retention-bytes' );
file_put_contents( $automatic_retention_path, 'automatic-retention-bytes' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][790] = (object) array(
	'ID'             => 790,
	'post_type'      => 'attachment',
	'post_status'    => 'inherit',
	'post_mime_type' => 'image/jpeg',
);
update_post_meta(
	790,
	'_npcink_ai_media_file_replacement_history',
	array(
		array(
			'replacement_id'       => 'manual-retention',
			'status'               => 'active',
			'replaced_at_gmt'      => gmdate( 'c', time() - ( 31 * 86400 ) ),
			'backup_cleanup_policy' => 'manual_confirmation_required',
			'backup'               => array( 'relative_file' => $manual_retention_relative, 'file_exists' => true ),
		),
		array(
			'replacement_id'  => 'automatic-retention',
			'status'          => 'active',
			'replaced_at_gmt' => gmdate( 'c', time() - ( 31 * 86400 ) ),
			'backup_cleanup_policy' => 'automatic_after_retention',
			'backup'          => array( 'relative_file' => $automatic_retention_relative, 'file_exists' => true ),
		),
	)
);
$media_backup_cleanup = $core_write_package->cleanup_expired_media_backups();
$media_backup_cleanup_history = get_post_meta( 790, '_npcink_ai_media_file_replacement_history', true );
npcink_abilities_toolkit_assert_true( is_file( $manual_retention_path ), 'expired exact-manifest backup remains available without explicit cleanup confirmation' );
npcink_abilities_toolkit_assert_same( 'active', $media_backup_cleanup_history[0]['status'] ?? '', 'expired exact-manifest history remains restorable' );
npcink_abilities_toolkit_assert_true( ! is_file( $automatic_retention_path ), 'explicit automatic policy removes the backup after retention' );
npcink_abilities_toolkit_assert_same( 'backup_expired', $media_backup_cleanup_history[1]['status'] ?? '', 'automatic backup history records expiry' );
npcink_abilities_toolkit_assert_true( (int) ( $media_backup_cleanup['removed'] ?? 0 ) >= 1, 'backup cleanup reports automatically removed backups' );
$confirmed_media_backup_cleanup = $core_write_package->cleanup_expired_media_backups( true, true );
$confirmed_media_backup_cleanup_history = get_post_meta( 790, '_npcink_ai_media_file_replacement_history', true );
npcink_abilities_toolkit_assert_true( ! is_file( $manual_retention_path ), 'confirmed maintenance cleanup removes an expired exact-manifest backup' );
npcink_abilities_toolkit_assert_same( 'backup_expired', $confirmed_media_backup_cleanup_history[0]['status'] ?? '', 'confirmed maintenance cleanup records manual backup expiry' );
npcink_abilities_toolkit_assert_true( (int) ( $confirmed_media_backup_cleanup['removed'] ?? 0 ) >= 1, 'confirmed maintenance cleanup reports manually approved removals' );

update_post_meta( 79, '_wp_attached_file', '2026/06/workflow-diagram-image.jpg' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][79]->post_mime_type = 'image/jpeg';
update_post_meta(
	79,
	'_wp_attachment_metadata',
	array(
		'width'    => 2600,
		'height'   => 1400,
		'file'     => '2026/06/workflow-diagram-image.jpg',
		'filesize' => 900000,
		'sizes'    => array(
			'medium' => array(
				'file'   => 'workflow-diagram-image-300x162.jpg',
				'width'  => 300,
				'height' => 162,
			),
		),
	)
);
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][83] = (object) array(
	'ID'           => 83,
	'post_title'   => 'Media Reference Repair Candidate',
	'post_status'  => 'publish',
	'post_type'    => 'post',
	'post_excerpt' => '',
	'post_content' => '<p><img src="https://example.test/wp-content/uploads/2026/06/workflow-diagram-image.jpg" /></p><p><a href="/wp-content/uploads/2026/06/workflow-diagram-image.jpg">download</a></p><p><img src="/wp-content/uploads/2026/06/workflow-diagram-image-300x162.jpg" /></p>',
	'post_name'    => 'media-reference-repair-candidate',
	'post_author'  => 7,
);
$media_reference_repair_plan = $core_read_package->build_media_reference_repair_plan(
	array(
		'attachment_id'  => 79,
		'replacement_id' => 'media_replace_unit',
		'max_posts'      => 10,
	)
);
npcink_abilities_toolkit_assert_same( true, $media_reference_repair_plan['success'] ?? null, 'build-media-reference-repair-plan returns a success envelope' );
npcink_abilities_toolkit_assert_same( false, $media_reference_repair_plan['data']['commit_execution'] ?? null, 'media reference repair plan does not execute commits' );
npcink_abilities_toolkit_assert_same( 1, $media_reference_repair_plan['data']['action_count'] ?? 0, 'media reference repair plan builds one post patch action' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/patch-post-content', $media_reference_repair_plan['data']['write_actions'][0]['target_ability_id'] ?? '', 'media reference repair plan reuses patch-post-content' );
npcink_abilities_toolkit_assert_same( 83, $media_reference_repair_plan['data']['write_actions'][0]['input']['post_id'] ?? 0, 'media reference repair action targets the referencing post' );
npcink_abilities_toolkit_assert_same( 'replace', $media_reference_repair_plan['data']['write_actions'][0]['input']['operations'][0]['op'] ?? '', 'media reference repair action uses replace operations' );
npcink_abilities_toolkit_assert_true( false !== strpos( (string) ( $media_reference_repair_plan['data']['write_actions'][0]['input']['operations'][0]['find'] ?? '' ), 'workflow-diagram-image.jpg' ), 'media reference repair action finds old media URL' );
npcink_abilities_toolkit_assert_true( false !== strpos( (string) ( $media_reference_repair_plan['data']['write_actions'][0]['input']['operations'][0]['replace'] ?? '' ), 'workflow-diagram-image-optimized.webp' ), 'media reference repair action replaces with new media URL' );
npcink_abilities_toolkit_assert_same( 'old_sized_variant_reference_detected', $media_reference_repair_plan['data']['manual_review'][0]['reason'] ?? '', 'media reference repair plan sends old size variants to manual review' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][82] = (object) array(
	'ID'           => 82,
	'post_title'   => 'Media Adoption Enhancement Candidate',
	'post_status'  => 'draft',
	'post_type'    => 'page',
	'post_excerpt' => '',
	'post_content' => '<!-- wp:media-text {"mediaUrl":"https://example.test/wp-content/uploads/2026/06/raw-dashboard.png"} --><div><img src="https://example.test/wp-content/uploads/2026/06/raw-dashboard.png" /></div><!-- /wp:media-text -->',
	'post_name'    => 'media-adoption-enhancement-candidate',
	'post_author'  => 7,
);
$media_adoption_enhancement_plan = $core_read_package->build_media_adoption_enhancement_plan(
	array(
		'url'               => 'https://cdn.example.test/generated/raw-dashboard.png',
		'post_id'           => 82,
		'old_url'           => 'https://example.test/wp-content/uploads/2026/06/raw-dashboard.png',
		'file_name'         => 'raw-dashboard.png',
		'title'             => 'Dashboard visual',
		'alt'               => 'Dashboard visual showing proposal approval.',
		'description'       => 'Reviewed page visual.',
		'source_type'       => 'ai_generated',
		'preferred_format'  => 'webp',
		'target_max_width'  => 1024,
		'quality'           => 82,
		'derivative_suffix' => 'optimized',
	)
);
npcink_abilities_toolkit_assert_same( true, $media_adoption_enhancement_plan['success'] ?? null, 'build-media-adoption-enhancement-plan returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'media_adoption_enhancement_plan', $media_adoption_enhancement_plan['data']['artifact_type'] ?? '', 'media adoption enhancement plan declares artifact type' );
npcink_abilities_toolkit_assert_same( 'batch', $media_adoption_enhancement_plan['data']['proposal_mode'] ?? '', 'media adoption enhancement plan requests batch proposal mode' );
npcink_abilities_toolkit_assert_same( false, $media_adoption_enhancement_plan['data']['direct_wordpress_write'] ?? null, 'media adoption enhancement plan does not directly write WordPress' );
npcink_abilities_toolkit_assert_same( false, $media_adoption_enhancement_plan['data']['commit_execution'] ?? null, 'media adoption enhancement plan keeps commit execution disabled' );
npcink_abilities_toolkit_assert_same( true, $media_adoption_enhancement_plan['meta']['readonly'] ?? null, 'media adoption enhancement plan remains read-only' );
$media_adoption_actions = (array) ( $media_adoption_enhancement_plan['data']['write_actions'] ?? array() );
npcink_abilities_toolkit_assert_same( 3, count( $media_adoption_actions ), 'media adoption enhancement plan emits upload, optimize, and patch actions when old URL is present' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/upload-media-from-url', $media_adoption_actions[0]['target_ability_id'] ?? '', 'media adoption enhancement plan starts with media upload' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/optimize-media-asset', $media_adoption_actions[1]['target_ability_id'] ?? '', 'media adoption enhancement plan optimizes the uploaded media' );
npcink_abilities_toolkit_assert_same( '$outputs.upload-media-asset.attachment_id', $media_adoption_actions[1]['input']['attachment_id'] ?? '', 'media adoption enhancement plan optimizes the uploaded attachment output' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/patch-post-content', $media_adoption_actions[2]['target_ability_id'] ?? '', 'media adoption enhancement plan patches reviewed page references' );
npcink_abilities_toolkit_assert_same( array( 'optimize-media-asset' ), $media_adoption_actions[2]['depends_on'] ?? array(), 'media adoption enhancement patch waits for optimized derivative output' );
npcink_abilities_toolkit_assert_same( '$outputs.optimize-media-asset.derivative_url', $media_adoption_actions[2]['input']['operations'][0]['replace'] ?? '', 'media adoption enhancement patch uses a whole-field derivative URL output reference' );
npcink_abilities_toolkit_assert_same( 2, $media_adoption_actions[2]['input']['operations'][0]['limit'] ?? 0, 'media adoption enhancement patch limits replacements to reviewed matches' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/build-media-adoption-enhancement-plan', $media_adoption_enhancement_plan['data']['handoff']['plan_ability_id'] ?? '', 'media adoption enhancement plan identifies itself for Core from-plan intake' );
unset( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][82] );

$image_candidate_review_artifact = $core_read_package->build_image_candidate_review_artifact(
	array(
		'target_field'     => 'featured_image',
		'candidate_limit'  => 4,
		'image_candidates' => array(
			array(
				'id'                    => 'reviewed-featured',
				'contract_version'      => 'image_candidate.v1',
				'download_url'          => 'https://cdn.example.test/images/reviewed-featured.png',
				'thumbnail_url'         => 'https://cdn.example.test/images/reviewed-featured-thumb.png',
				'source_url'            => 'https://source.example.test/reviewed-featured',
				'source_type'           => 'stock',
				'provider'              => 'unsplash',
				'provider_origin'       => 'toolbox',
				'title'                 => 'Reviewed featured image',
				'description'           => 'Reviewed source image for the article.',
				'alt_description'       => 'Dashboard operator reviewing Core proposal.',
				'attribution'           => 'Photo by Example',
				'photographer'          => 'Example Photographer',
				'download_location'     => 'https://api.unsplash.example.test/download-location',
				'suggested_filename'    => 'reviewed-featured-image.png',
				'license_review_status' => 'reviewed',
				'match_score'           => 0.84,
			),
			array(
				'id'                    => 'weak-no-url',
				'title'                 => 'Weak image candidate',
				'provider'              => 'external',
				'license_review_status' => 'required',
			),
		),
	)
);
npcink_abilities_toolkit_assert_same( true, $image_candidate_review_artifact['success'] ?? null, 'build-image-candidate-review-artifact returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'image_candidate_review.v1', $image_candidate_review_artifact['data']['artifact_type'] ?? '', 'image candidate review artifact declares artifact type' );
npcink_abilities_toolkit_assert_same( 'image_candidate.v1', $image_candidate_review_artifact['data']['candidate_contract'] ?? '', 'image candidate review artifact preserves authoritative candidate contract' );
npcink_abilities_toolkit_assert_same( 'recommendation_candidate.v1', $image_candidate_review_artifact['data']['projection_contract'] ?? '', 'image candidate review artifact exposes recommendation projection contract' );
npcink_abilities_toolkit_assert_same( false, $image_candidate_review_artifact['data']['direct_wordpress_write'] ?? null, 'image candidate review artifact does not directly write WordPress' );
npcink_abilities_toolkit_assert_true( ! isset( $image_candidate_review_artifact['data']['write_actions'] ), 'image candidate review artifact does not create adoption write actions' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/build-image-candidate-adoption-plan', $image_candidate_review_artifact['data']['handoff']['plan_ability_id'] ?? '', 'image candidate review artifact points selected candidates to the adoption planner' );
npcink_abilities_toolkit_assert_same( 'image_candidate.v1', $image_candidate_review_artifact['data']['items'][0]['contract_version'] ?? '', 'image candidate review artifact normalizes candidates to image_candidate.v1' );
npcink_abilities_toolkit_assert_same( 'https://api.unsplash.example.test/download-location', $image_candidate_review_artifact['data']['items'][0]['download_location'] ?? '', 'image candidate review artifact preserves source download tracking metadata' );
npcink_abilities_toolkit_assert_same( 'review', $image_candidate_review_artifact['data']['recommendation_candidates'][0]['quality_status'] ?? '', 'image candidate review artifact projects strong candidates for review' );
npcink_abilities_toolkit_assert_same( 'weak', $image_candidate_review_artifact['data']['recommendation_candidates'][1]['quality_status'] ?? '', 'image candidate review artifact downgrades candidates missing usable URLs' );

$media_alt_caption_review_set = $core_read_package->build_media_alt_caption_review_set(
	array(
		'review_set_limit'       => 2,
		'media_snapshot'         => array(
			'snapshot_policy' => 'current_article_media_metadata_only',
			'media_scope'     => 'current_article_used_images',
			'post_context'    => array(
				'post_id' => 82,
				'title'   => 'AI workflow operations',
			),
			'items'           => array(
				array(
					'attachment_id' => 711,
					'title'         => 'Operator reviewing workflow dashboard',
					'filename'      => 'workflow-dashboard-hero.jpg',
					'alt'           => '',
					'caption'       => '',
					'description'   => 'Generated with model: keep this provenance out of ALT.',
					'thumbnail_url' => 'https://cdn.example.test/workflow-dashboard-thumb.jpg',
					'url'           => 'https://cdn.example.test/workflow-dashboard.jpg',
					'mime_type'     => 'image/jpeg',
				),
				array(
					'attachment_id' => 712,
					'title'         => 'IMG_1234',
					'filename'      => 'IMG_1234.jpg',
					'alt'           => 'IMG_1234',
					'caption'       => '',
					'description'   => '',
					'thumbnail_url' => 'https://cdn.example.test/img-1234-thumb.jpg',
					'url'           => 'https://cdn.example.test/img-1234.jpg',
					'mime_type'     => 'image/jpeg',
				),
			),
		),
		'image_context_evidence' => array(
			'contract_version'       => 'image_context_evidence.v1',
			'write_posture'          => 'suggestion_only',
			'direct_wordpress_write' => false,
			'items'                  => array(
				array(
					'attachment_id'  => 711,
					'visual_summary' => 'WordPress operator reviewing an AI workflow dashboard',
					'objects'        => array( 'dashboard', 'workflow cards' ),
					'confidence'     => 'high',
				),
			),
		),
	)
);
npcink_abilities_toolkit_assert_same( true, $media_alt_caption_review_set['success'] ?? null, 'build-media-alt-caption-review-set returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'media_alt_caption_review_set.v1', $media_alt_caption_review_set['data']['contract_version'] ?? '', 'media ALT/caption review set declares the reusable contract' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/build-media-alt-caption-review-set', $media_alt_caption_review_set['data']['source_ability_id'] ?? '', 'media ALT/caption review set records Toolkit source ability' );
npcink_abilities_toolkit_assert_same( 'current_article_media_metadata_only_no_pixel_vision', $media_alt_caption_review_set['data']['source_policy'] ?? '', 'media ALT/caption review set keeps no-pixel-vision metadata policy' );
npcink_abilities_toolkit_assert_same( false, $media_alt_caption_review_set['data']['direct_wordpress_write'] ?? null, 'media ALT/caption review set does not directly write WordPress' );
npcink_abilities_toolkit_assert_same( false, $media_alt_caption_review_set['data']['proposal_created'] ?? null, 'media ALT/caption review set does not create proposals' );
npcink_abilities_toolkit_assert_same( false, $media_alt_caption_review_set['data']['safety']['media_derivative_run_created'] ?? null, 'media ALT/caption review set does not create derivative runs' );
npcink_abilities_toolkit_assert_same( true, $media_alt_caption_review_set['data']['selected_items'][0]['needs_human_visual_check'] ?? null, 'media ALT/caption review set requires human visual review' );
npcink_abilities_toolkit_assert_same( 'visual_fact', $media_alt_caption_review_set['data']['selected_items'][0]['candidate_fact_types'][0] ?? '', 'media ALT/caption review set can use supplied reviewed image context as visual fact evidence' );
npcink_abilities_toolkit_assert_same( 'ready', $media_alt_caption_review_set['data']['selected_items'][0]['candidate_quality']['tier'] ?? '', 'media ALT/caption review set marks visual-evidence rows as ready only after human visual review' );
npcink_abilities_toolkit_assert_same( 90, $media_alt_caption_review_set['data']['selected_items'][0]['candidate_quality_score'] ?? null, 'media ALT/caption review set assigns a deterministic quality score for visual-evidence candidates' );
npcink_abilities_toolkit_assert_same( 'eligible_for_local_preview_after_visual_check', $media_alt_caption_review_set['data']['selected_items'][0]['automation_recommendation'] ?? '', 'media ALT/caption review set exposes machine-readable local preview guidance without write authority' );
npcink_abilities_toolkit_assert_same( 1, $media_alt_caption_review_set['data']['eligibility_summary']['local_preview_candidate_count'] ?? null, 'media ALT/caption review set summarizes local preview rows for UI triage' );
npcink_abilities_toolkit_assert_true( false === array_key_exists( 'ready_for_handoff_count', $media_alt_caption_review_set['data']['eligibility_summary'] ?? array() ), 'media ALT/caption review set does not emit deprecated ready-for-handoff counts in P0' );
npcink_abilities_toolkit_assert_same( 1, $media_alt_caption_review_set['data']['eligibility_summary']['visual_evidence_request_count'] ?? null, 'media ALT/caption review set summarizes rows that need visual evidence' );
npcink_abilities_toolkit_assert_same( 'insufficient', $media_alt_caption_review_set['data']['blocked_items'][0]['candidate_quality']['tier'] ?? '', 'media ALT/caption review set marks metadata-only failures as insufficient quality' );
npcink_abilities_toolkit_assert_true( false === strpos( implode( ' ', $media_alt_caption_review_set['data']['selected_items'][0]['alt_candidates'] ?? array() ), 'Generated with model' ), 'media ALT/caption review set filters runtime provenance from candidates' );
npcink_abilities_toolkit_assert_same( 'image_context_evidence_request.v1', $media_alt_caption_review_set['data']['image_context_evidence_request']['contract_version'] ?? '', 'media ALT/caption review set can request bounded external visual evidence for weak metadata rows' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/build-media-alt-apply-plan', $media_alt_caption_review_set['data']['handoff']['accepted_selection_target'] ?? '', 'media ALT/caption review set points accepted rows to the ALT-only apply planner' );

$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][713] = (object) array(
	'ID'             => 713,
	'post_type'      => 'attachment',
	'post_status'    => 'inherit',
	'post_title'     => 'Reviewed workflow dashboard',
	'post_content'   => '',
	'post_excerpt'   => '',
	'post_mime_type' => 'image/jpeg',
	'guid'           => 'https://example.test/wp-content/uploads/workflow-dashboard.jpg',
);
$GLOBALS['npcink_abilities_toolkit_unit_post_meta'][713]['_wp_attachment_image_alt'] = '';
$media_alt_apply_plan_input = array(
	'attachment_id'                     => 713,
	'alt'                               => 'Operator reviewing the workflow dashboard',
	'expected_current_alt'              => '',
	'operator_visual_review_confirmed' => true,
	'review_set_contract'               => 'media_alt_caption_review_set.v1',
	'source_item_id'                    => 'media-alt-caption:713',
	'evidence_refs'                     => array( 'image-context:713', 'article-context:82' ),
);
$media_alt_apply_plan = $core_read_package->build_media_alt_apply_plan( $media_alt_apply_plan_input );
npcink_abilities_toolkit_assert_same( true, $media_alt_apply_plan['success'] ?? null, 'build-media-alt-apply-plan returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'media_alt_apply_plan.v1', $media_alt_apply_plan['data']['contract_version'] ?? '', 'media ALT apply plan declares the shared contract' );
npcink_abilities_toolkit_assert_same( 'core_proposal_required', $media_alt_apply_plan['data']['authorization']['classification'] ?? '', 'media ALT apply plan requires Core governance' );
npcink_abilities_toolkit_assert_same( 1, count( $media_alt_apply_plan['data']['write_actions'] ?? array() ), 'media ALT apply plan emits exactly one write action' );
$media_alt_apply_action = $media_alt_apply_plan['data']['write_actions'][0] ?? array();
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/update-media-details', $media_alt_apply_action['target_ability_id'] ?? '', 'media ALT apply plan reuses update-media-details' );
npcink_abilities_toolkit_assert_same( '', $media_alt_apply_action['input']['expected_current_alt'] ?? null, 'media ALT apply plan preserves the reviewed empty old value' );
npcink_abilities_toolkit_assert_same( true, $media_alt_apply_action['input']['operator_visual_review_confirmed'] ?? null, 'media ALT apply plan preserves visual confirmation' );
npcink_abilities_toolkit_assert_true( 0 === strpos( (string) ( $media_alt_apply_action['input']['idempotency_key'] ?? '' ), 'media-alt-missing-713-' ), 'media ALT apply plan emits a stable bounded idempotency key' );
npcink_abilities_toolkit_assert_same( 'media_alt_apply_plan_item', $media_alt_apply_action['preview']['artifact_type'] ?? '', 'media ALT apply action carries Core-readable review evidence' );

$media_alt_dry_run = $core_write_package->update_media_details( $media_alt_apply_action['input'] ?? array() );
npcink_abilities_toolkit_assert_same( true, $media_alt_dry_run['dry_run'] ?? null, 'guarded update-media-details returns a dry-run preview' );
npcink_abilities_toolkit_assert_same( 'media_alt_only_write.v1', $media_alt_dry_run['preview']['contract_version'] ?? '', 'guarded media ALT dry run declares its write contract' );
npcink_abilities_toolkit_assert_same( '', get_post_meta( 713, '_wp_attachment_image_alt', true ), 'guarded media ALT dry run does not mutate attachment metadata' );

$media_alt_unconfirmed_input = $media_alt_apply_plan_input;
$media_alt_unconfirmed_input['operator_visual_review_confirmed'] = false;
$media_alt_unconfirmed = $core_read_package->build_media_alt_apply_plan( $media_alt_unconfirmed_input );
npcink_abilities_toolkit_assert_true( is_wp_error( $media_alt_unconfirmed ), 'media ALT apply plan rejects missing visual confirmation' );
npcink_abilities_toolkit_assert_same( 'npcink_abilities_toolkit_media_alt_visual_confirmation_required', $media_alt_unconfirmed->get_error_code(), 'media ALT visual confirmation failure is machine-readable' );

update_post_meta( 713, '_wp_attachment_image_alt', 'Changed after review' );
$media_alt_stale = $core_read_package->build_media_alt_apply_plan( $media_alt_apply_plan_input );
npcink_abilities_toolkit_assert_true( is_wp_error( $media_alt_stale ), 'media ALT apply plan rejects a stale old value' );
npcink_abilities_toolkit_assert_same( 'npcink_abilities_toolkit_media_alt_stale', $media_alt_stale->get_error_code(), 'media ALT stale-value failure is machine-readable' );
update_post_meta( 713, '_wp_attachment_image_alt', '' );

$image_candidate_adoption_plan = $core_read_package->build_image_candidate_adoption_plan(
	array(
		'post_id'            => 82,
		'set_featured_image' => true,
		'image_candidate'    => array(
			'contract_version'      => 'image_candidate.v1',
			'download_url'          => 'https://cdn.example.test/images/reviewed-featured.png',
			'thumbnail_url'         => 'https://cdn.example.test/images/reviewed-featured-thumb.png',
			'source_url'            => 'https://source.example.test/reviewed-featured',
			'source_type'           => 'stock',
			'provider'              => 'unsplash',
			'provider_origin'       => 'toolbox',
			'title'                 => 'Reviewed featured image',
			'description'           => 'Reviewed source image for the article.',
			'alt_description'       => 'Dashboard operator reviewing Core proposal.',
			'attribution'           => 'Photo by Example',
			'photographer'          => 'Example Photographer',
			'download_location'     => 'https://api.unsplash.example.test/download-location',
			'suggested_filename'    => 'reviewed-featured-image.png',
			'license_review_status' => 'reviewed',
		),
	)
);
npcink_abilities_toolkit_assert_same( true, $image_candidate_adoption_plan['success'] ?? null, 'build-image-candidate-adoption-plan returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'image_candidate_adoption_plan', $image_candidate_adoption_plan['data']['artifact_type'] ?? '', 'image candidate adoption plan declares artifact type' );
npcink_abilities_toolkit_assert_same( false, $image_candidate_adoption_plan['data']['direct_wordpress_write'] ?? null, 'image candidate adoption plan does not directly write WordPress' );
npcink_abilities_toolkit_assert_same( false, $image_candidate_adoption_plan['data']['commit_execution'] ?? null, 'image candidate adoption plan keeps commit execution disabled' );
npcink_abilities_toolkit_assert_same( true, $image_candidate_adoption_plan['meta']['readonly'] ?? null, 'image candidate adoption plan remains read-only' );
$image_candidate_actions = (array) ( $image_candidate_adoption_plan['data']['write_actions'] ?? array() );
npcink_abilities_toolkit_assert_same( 3, count( $image_candidate_actions ), 'image candidate adoption plan emits upload, metadata, and featured-image actions when requested' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/upload-media-from-url', $image_candidate_actions[0]['target_ability_id'] ?? '', 'image candidate adoption plan starts with media upload' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/update-media-details', $image_candidate_actions[1]['target_ability_id'] ?? '', 'image candidate adoption plan updates media details after upload' );
npcink_abilities_toolkit_assert_same( '$outputs.upload_image_candidate.attachment_id', $image_candidate_actions[1]['input']['attachment_id'] ?? '', 'image candidate metadata action uses the upload output reference' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/set-post-featured-image', $image_candidate_actions[2]['target_ability_id'] ?? '', 'image candidate adoption plan can include featured image assignment' );
npcink_abilities_toolkit_assert_same( 'image_candidate.v1', $image_candidate_adoption_plan['data']['selected_image_candidate']['contract_version'] ?? '', 'image candidate adoption plan preserves image_candidate.v1 evidence' );
npcink_abilities_toolkit_assert_same( 'https://api.unsplash.example.test/download-location', $image_candidate_adoption_plan['data']['selected_image_candidate']['download_location'] ?? '', 'image candidate adoption plan preserves source download tracking metadata' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/build-image-candidate-adoption-plan', $image_candidate_adoption_plan['data']['handoff']['plan_ability_id'] ?? '', 'image candidate adoption plan identifies the Toolkit Core handoff ability' );

