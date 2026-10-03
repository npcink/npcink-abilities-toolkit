<?php
/**
 * Ordered regression suite part: 70-cloud-media-backups.
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

$exclusive_race_contents = 'external-exclusive-create-winner';
$exclusive_race_created = false;
add_filter(
	'npcink_abilities_toolkit_media_file_write_blocked',
	static function ( $blocked, $target_path, $bytes, $context ) use ( &$exclusive_race_created, $exclusive_race_contents ) {
		unset( $bytes );
		if (
			! $exclusive_race_created
			&& is_array( $context )
			&& 'adopt_cloud_media_derivative' === (string) ( $context['operation'] ?? '' )
			&& 'write_derivative' === (string) ( $context['step'] ?? '' )
			&& 'exclusive-race.webp' === basename( (string) $target_path )
		) {
			$exclusive_race_created = true;
			file_put_contents( (string) $target_path, $exclusive_race_contents );
		}
		return $blocked;
	},
	10,
	4
);
$cloud_adoption_exclusive_race = $core_write_package->adopt_cloud_media_derivative(
	array(
		'attachment_id'                 => 79,
		'derivative_artifact'           => npcink_abilities_toolkit_cloud_artifact_fixture(
			array(
				'artifact_id'    => 'art_00000000000000000000000000000018',
				'width'          => 1,
				'height'         => 1,
				'filesize_bytes' => strlen( $cloud_artifact_contents ),
				'sha256'         => $cloud_artifact_sha256,
			)
		),
		'expected_current_relative_file' => '2026/06/workflow-diagram-image.jpg',
		'expected_current_mime_type'    => 'image/jpeg',
		'expected_derivative_mime_type' => 'image/webp',
		'file_name'                    => 'exclusive-race.webp',
		'expected_content_reference_post_ids' => $cloud_adoption_expected_post_ids,
		'expected_content_reference_post_count' => $cloud_adoption_expected_post_count,
		'expected_content_reference_replacement_count' => $cloud_adoption_expected_replacement_count,
		'commit'                       => true,
	)
);
remove_all_filters( 'npcink_abilities_toolkit_media_file_write_blocked' );
$exclusive_race_error_data = is_wp_error( $cloud_adoption_exclusive_race ) ? $cloud_adoption_exclusive_race->get_error_data() : array();
$exclusive_race_path = $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/2026/06/exclusive-race.webp';
npcink_abilities_toolkit_assert_true( is_wp_error( $cloud_adoption_exclusive_race ) && 'npcink_abilities_toolkit_cloud_derivative_target_conflict' === $cloud_adoption_exclusive_race->get_error_code(), 'Cloud derivative exclusive create fails safely when another request wins after filename selection' );
npcink_abilities_toolkit_assert_same( 409, $exclusive_race_error_data['status'] ?? 0, 'exclusive derivative target races return a conflict' );
npcink_abilities_toolkit_assert_same( $exclusive_race_contents, is_readable( $exclusive_race_path ) ? file_get_contents( $exclusive_race_path ) : '', 'exclusive derivative target races never overwrite or delete the competing file' );
npcink_abilities_toolkit_assert_same( $precommit_history_before, $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][79]['_npcink_ai_media_file_replacement_history'] ?? null, 'exclusive derivative target races create no replacement history' );
wp_delete_file( $exclusive_race_path );
add_filter(
	'npcink_abilities_toolkit_media_file_write_blocked',
	static function ( $blocked, $target_path, $bytes, $context ) {
		unset( $target_path, $bytes );
		return true === $blocked || (
			is_array( $context ) &&
			'adopt_cloud_media_derivative' === (string) ( $context['operation'] ?? '' ) &&
			'write_derivative' === (string) ( $context['step'] ?? '' )
		);
	},
	10,
	4
);
$cloud_adoption_write_failure = $core_write_package->adopt_cloud_media_derivative(
	array(
		'attachment_id'                 => 79,
		'derivative_artifact'           => npcink_abilities_toolkit_cloud_artifact_fixture( array(
			'artifact_id'    => 'art_00000000000000000000000000000012',
			'expires_at'     => gmdate( 'c', time() + 600 ),
			'mime_type'      => 'image/webp',
			'format'         => 'webp',
			'width'          => 1,
			'height'         => 1,
			'filesize_bytes' => strlen( $cloud_artifact_contents ),
			'sha256'         => $cloud_artifact_sha256,
		) ),
		'expected_current_relative_file' => '2026/06/workflow-diagram-image.jpg',
		'expected_current_mime_type'    => 'image/jpeg',
		'expected_derivative_mime_type' => 'image/webp',
		'file_name'                    => 'write-failure.webp',
		'expected_content_reference_post_ids' => $cloud_adoption_expected_post_ids,
		'expected_content_reference_post_count' => $cloud_adoption_expected_post_count,
		'expected_content_reference_replacement_count' => $cloud_adoption_expected_replacement_count,
		'commit'                       => true,
	)
);
remove_all_filters( 'npcink_abilities_toolkit_media_file_write_blocked' );
npcink_abilities_toolkit_assert_true( is_wp_error( $cloud_adoption_write_failure ) && 'npcink_abilities_toolkit_cloud_derivative_write_failed' === $cloud_adoption_write_failure->get_error_code(), 'adopt-cloud-media-derivative commit reports local derivative write failures' );
npcink_abilities_toolkit_assert_same( '2026/06/workflow-diagram-image.jpg', get_post_meta( 79, '_wp_attached_file', true ), 'adopt-cloud-media-derivative write failure leaves the attachment pointer unchanged' );
add_filter(
	'npcink_abilities_toolkit_media_file_copy_blocked',
	static function ( $blocked, $source_path, $target_path, $context ) {
		unset( $source_path, $target_path );
		return true === $blocked || (
			is_array( $context ) &&
			'replace_media_file' === (string) ( $context['operation'] ?? '' ) &&
			'backup_current' === (string) ( $context['step'] ?? '' )
		);
	},
	10,
	4
);
$cloud_adoption_backup_failure = $core_write_package->adopt_cloud_media_derivative(
	array(
		'attachment_id'                 => 79,
		'derivative_artifact'           => npcink_abilities_toolkit_cloud_artifact_fixture( array(
			'artifact_id'    => 'art_00000000000000000000000000000013',
			'expires_at'     => gmdate( 'c', time() + 600 ),
			'mime_type'      => 'image/webp',
			'format'         => 'webp',
			'width'          => 1,
			'height'         => 1,
			'filesize_bytes' => strlen( $cloud_artifact_contents ),
			'sha256'         => $cloud_artifact_sha256,
		) ),
		'expected_current_relative_file' => '2026/06/workflow-diagram-image.jpg',
		'expected_current_mime_type'    => 'image/jpeg',
		'expected_derivative_mime_type' => 'image/webp',
		'file_name'                    => 'backup-failure.webp',
		'expected_content_reference_post_ids' => $cloud_adoption_expected_post_ids,
		'expected_content_reference_post_count' => $cloud_adoption_expected_post_count,
		'expected_content_reference_replacement_count' => $cloud_adoption_expected_replacement_count,
		'commit'                       => true,
	)
);
remove_all_filters( 'npcink_abilities_toolkit_media_file_copy_blocked' );
npcink_abilities_toolkit_assert_true( is_wp_error( $cloud_adoption_backup_failure ) && 'npcink_abilities_toolkit_media_backup_failed' === $cloud_adoption_backup_failure->get_error_code(), 'adopt-cloud-media-derivative commit reports current media backup failures' );
npcink_abilities_toolkit_assert_same( '2026/06/workflow-diagram-image.jpg', get_post_meta( 79, '_wp_attached_file', true ), 'adopt-cloud-media-derivative backup failure leaves the attachment pointer unchanged' );

$late_drift_relative_file = '2026/06/late-concurrent-external.jpg';
$late_drift_path = $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/' . $late_drift_relative_file;
$late_drift_contents = 'late-concurrent-external-bytes';
$late_drift_post_content = '<p>Late concurrent editor state must survive backup-time drift.</p>';
$late_drift_injected = false;
$late_drift_backups_before = glob( $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/npcink-abilities-toolkit-backups/2026/06/*' );
$late_drift_backups_before = is_array( $late_drift_backups_before ) ? $late_drift_backups_before : array();
add_filter(
	'npcink_abilities_toolkit_media_file_copy_blocked',
	static function ( $blocked, $source_path, $target_path, $context ) use ( &$late_drift_injected, $late_drift_relative_file, $late_drift_path, $late_drift_contents, $late_drift_post_content ) {
		unset( $source_path, $target_path );
		if (
			! $late_drift_injected
			&& is_array( $context )
			&& 'replace_media_file' === (string) ( $context['operation'] ?? '' )
			&& 'backup_current' === (string) ( $context['step'] ?? '' )
		) {
			$late_drift_injected = true;
			file_put_contents( $late_drift_path, $late_drift_contents );
			update_post_meta( 79, '_wp_attached_file', $late_drift_relative_file );
			update_post_meta(
				79,
				'_wp_attachment_metadata',
				array(
					'file'     => $late_drift_relative_file,
					'width'    => 640,
					'height'   => 480,
					'filesize' => strlen( $late_drift_contents ),
					'sizes'    => array(),
				)
			);
			wp_update_post( array( 'ID' => 79, 'post_mime_type' => 'image/png' ), true );
			wp_update_post( array( 'ID' => 89, 'post_content' => $late_drift_post_content ), true );
		}
		return $blocked;
	},
	10,
	4
);
$cloud_adoption_late_drift = $core_write_package->adopt_cloud_media_derivative(
	array(
		'attachment_id'                 => 79,
		'derivative_artifact'           => npcink_abilities_toolkit_cloud_artifact_fixture(
			array(
				'artifact_id'    => 'art_0000000000000000000000000000001c',
				'width'          => 1,
				'height'         => 1,
				'filesize_bytes' => strlen( $cloud_artifact_contents ),
				'sha256'         => $cloud_artifact_sha256,
			)
		),
		'expected_current_relative_file' => '2026/06/workflow-diagram-image.jpg',
		'expected_current_mime_type'    => 'image/jpeg',
		'expected_derivative_mime_type' => 'image/webp',
		'file_name'                    => 'late-precommit-drift.webp',
		'expected_content_reference_post_ids' => $cloud_adoption_expected_post_ids,
		'expected_content_reference_post_count' => $cloud_adoption_expected_post_count,
		'expected_content_reference_replacement_count' => $cloud_adoption_expected_replacement_count,
		'commit'                       => true,
	)
);
remove_all_filters( 'npcink_abilities_toolkit_media_file_copy_blocked' );
$late_drift_error_data = is_wp_error( $cloud_adoption_late_drift ) ? $cloud_adoption_late_drift->get_error_data() : array();
npcink_abilities_toolkit_assert_true( is_wp_error( $cloud_adoption_late_drift ) && 'npcink_abilities_toolkit_cloud_adoption_precommit_drift' === $cloud_adoption_late_drift->get_error_code(), 'adopt-cloud-media-derivative reruns its state CAS after backup and before the first pointer write' );
npcink_abilities_toolkit_assert_same( 409, $late_drift_error_data['status'] ?? 0, 'backup-window drift returns a conflict' );
npcink_abilities_toolkit_assert_same( $late_drift_relative_file, get_post_meta( 79, '_wp_attached_file', true ), 'late drift cleanup preserves the concurrent attachment pointer' );
npcink_abilities_toolkit_assert_same( 'image/png', get_post_mime_type( 79 ), 'late drift cleanup preserves the concurrent attachment MIME type' );
npcink_abilities_toolkit_assert_same( $late_drift_post_content, (string) ( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][89]->post_content ?? '' ), 'late drift cleanup preserves concurrent post content' );
npcink_abilities_toolkit_assert_same( $late_drift_contents, is_readable( $late_drift_path ) ? file_get_contents( $late_drift_path ) : '', 'late drift cleanup preserves concurrent attachment bytes' );
npcink_abilities_toolkit_assert_true( ! is_file( $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/2026/06/late-precommit-drift.webp' ), 'late drift cleanup removes the losing batch derivative' );
$late_drift_backups_after = glob( $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/npcink-abilities-toolkit-backups/2026/06/*' );
$late_drift_backups_after = is_array( $late_drift_backups_after ) ? $late_drift_backups_after : array();
npcink_abilities_toolkit_assert_same( $late_drift_backups_before, $late_drift_backups_after, 'late drift cleanup removes the losing batch backup without touching existing backups' );
npcink_abilities_toolkit_assert_same( $precommit_history_before, $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][79]['_npcink_ai_media_file_replacement_history'] ?? null, 'late drift creates no replacement history' );
update_post_meta( 79, '_wp_attached_file', $precommit_original_relative_file );
update_post_meta( 79, '_wp_attachment_metadata', $precommit_original_metadata );
wp_update_post( array( 'ID' => 79, 'post_mime_type' => $precommit_original_mime ), true );
wp_update_post( array( 'ID' => 89, 'post_content' => $precommit_original_post_content ), true );
wp_delete_file( $late_drift_path );

$source_open_race_hold_path = $current_media_path . '.source-open-race-hold';
$source_open_race_injected = false;
$source_open_race_backups_before = glob( $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/npcink-abilities-toolkit-backups/2026/06/*' );
$source_open_race_backups_before = is_array( $source_open_race_backups_before ) ? $source_open_race_backups_before : array();
add_filter(
	'npcink_abilities_toolkit_media_file_copy_blocked',
	static function ( $blocked, $source_path, $target_path, $context ) use ( &$source_open_race_injected, $source_open_race_hold_path ) {
		unset( $target_path );
		if (
			! $source_open_race_injected
			&& is_array( $context )
			&& 'replace_media_file' === (string) ( $context['operation'] ?? '' )
			&& 'backup_current' === (string) ( $context['step'] ?? '' )
		) {
			$source_open_race_injected = true;
			rename( (string) $source_path, $source_open_race_hold_path );
		}
		return $blocked;
	},
	10,
	4
);
$cloud_adoption_source_open_race = $core_write_package->adopt_cloud_media_derivative(
	array(
		'attachment_id'                 => 79,
		'derivative_artifact'           => npcink_abilities_toolkit_cloud_artifact_fixture(
			array(
				'artifact_id'    => 'art_0000000000000000000000000000001d',
				'width'          => 1,
				'height'         => 1,
				'filesize_bytes' => strlen( $cloud_artifact_contents ),
				'sha256'         => $cloud_artifact_sha256,
			)
		),
		'expected_current_relative_file' => '2026/06/workflow-diagram-image.jpg',
		'expected_current_mime_type'    => 'image/jpeg',
		'expected_derivative_mime_type' => 'image/webp',
		'file_name'                    => 'source-open-race.webp',
		'expected_content_reference_post_ids' => $cloud_adoption_expected_post_ids,
		'expected_content_reference_post_count' => $cloud_adoption_expected_post_count,
		'expected_content_reference_replacement_count' => $cloud_adoption_expected_replacement_count,
		'commit'                       => true,
	)
);
remove_all_filters( 'npcink_abilities_toolkit_media_file_copy_blocked' );
$source_open_race_backups_after = glob( $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/npcink-abilities-toolkit-backups/2026/06/*' );
$source_open_race_backups_after = is_array( $source_open_race_backups_after ) ? $source_open_race_backups_after : array();
npcink_abilities_toolkit_assert_true( is_wp_error( $cloud_adoption_source_open_race ) && 'npcink_abilities_toolkit_media_backup_failed' === $cloud_adoption_source_open_race->get_error_code(), 'exclusive backup fails safely when the source disappears before fopen' );
npcink_abilities_toolkit_assert_true( is_file( $source_open_race_hold_path ), 'source-open race test preserves the externally moved source bytes' );
npcink_abilities_toolkit_assert_same( $source_open_race_backups_before, $source_open_race_backups_after, 'source-open failure creates no empty or untracked backup target' );
npcink_abilities_toolkit_assert_true( ! is_file( $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/2026/06/source-open-race.webp' ), 'source-open failure removes the materialized derivative through the batch manifest' );
rename( $source_open_race_hold_path, $current_media_path );

$atomic_conflict_relative_file = '2026/06/atomic-conflict.webp';
$atomic_conflict_path = $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/' . $atomic_conflict_relative_file;
$atomic_conflict_mime = 'IMAGE/JPEG';
$atomic_conflict_post_content = '<p>Concurrent post content inserted before the row lock.</p>';
$atomic_conflict_metadata = array(
	'file'     => $atomic_conflict_relative_file,
	'width'    => 1,
	'height'   => 1,
	'filesize' => strlen( $cloud_artifact_contents ),
	'sizes'    => array(),
);
$atomic_conflict_injected = false;
$atomic_conflict_backups_before = glob( $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/npcink-abilities-toolkit-backups/2026/06/*' );
$atomic_conflict_backups_before = is_array( $atomic_conflict_backups_before ) ? $atomic_conflict_backups_before : array();
$GLOBALS['npcink_abilities_toolkit_unit_wpdb_select_for_update_callback'] = static function ( $query ) use ( &$atomic_conflict_injected, $atomic_conflict_mime, $atomic_conflict_post_content, $atomic_conflict_metadata ) {
	if ( ! $atomic_conflict_injected && false !== strpos( (string) $query, 'post_mime_type' ) && false !== strpos( (string) $query, 'ID = 79' ) ) {
		$atomic_conflict_injected = true;
		$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][79]->post_mime_type = $atomic_conflict_mime;
		$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][89]->post_content = $atomic_conflict_post_content;
		$GLOBALS['npcink_abilities_toolkit_unit_post_meta'][79]['_wp_attachment_metadata'] = $atomic_conflict_metadata;
	}
	return null;
};
$cloud_adoption_atomic_conflict = $core_write_package->adopt_cloud_media_derivative(
	array(
		'attachment_id'                 => 79,
		'derivative_artifact'           => npcink_abilities_toolkit_cloud_artifact_fixture(
			array(
				'artifact_id'    => 'art_0000000000000000000000000000001e',
				'width'          => 1,
				'height'         => 1,
				'filesize_bytes' => strlen( $cloud_artifact_contents ),
				'sha256'         => $cloud_artifact_sha256,
			)
		),
		'expected_current_relative_file' => '2026/06/workflow-diagram-image.jpg',
		'expected_current_mime_type'    => 'image/jpeg',
		'expected_derivative_mime_type' => 'image/webp',
		'file_name'                    => 'atomic-conflict.webp',
		'expected_content_reference_post_ids' => $cloud_adoption_expected_post_ids,
		'expected_content_reference_post_count' => $cloud_adoption_expected_post_count,
		'expected_content_reference_replacement_count' => $cloud_adoption_expected_replacement_count,
		'commit'                       => true,
	)
);
unset( $GLOBALS['npcink_abilities_toolkit_unit_wpdb_select_for_update_callback'] );
$atomic_conflict_error_data = is_wp_error( $cloud_adoption_atomic_conflict ) ? $cloud_adoption_atomic_conflict->get_error_data() : array();
$atomic_conflict_backups_after = glob( $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/npcink-abilities-toolkit-backups/2026/06/*' );
$atomic_conflict_backups_after = is_array( $atomic_conflict_backups_after ) ? $atomic_conflict_backups_after : array();
npcink_abilities_toolkit_assert_true( is_wp_error( $cloud_adoption_atomic_conflict ) && 'npcink_abilities_toolkit_cloud_adoption_cleanup_conflict' === $cloud_adoption_atomic_conflict->get_error_code(), 'Cloud adoption reports bounded cleanup conflict when a concurrent write wins before the wp_posts row lock' );
npcink_abilities_toolkit_assert_same( 409, $atomic_conflict_error_data['status'] ?? 0, 'atomic mutation cleanup conflicts return HTTP 409' );
npcink_abilities_toolkit_assert_same( $atomic_conflict_mime, get_post_mime_type( 79 ), 'strict row-lock compare preserves a case-only concurrent MIME change that a case-insensitive SQL WHERE could miss' );
npcink_abilities_toolkit_assert_same( $atomic_conflict_metadata, wp_get_attachment_metadata( 79 ), 'conditional compensation never overwrites concurrent attachment metadata' );
npcink_abilities_toolkit_assert_same( $atomic_conflict_post_content, (string) ( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][89]->post_content ?? '' ), 'conditional compensation never overwrites concurrent post content' );
npcink_abilities_toolkit_assert_same( $precommit_original_relative_file, get_post_meta( 79, '_wp_attached_file', true ), 'conditional compensation restores a batch-owned pointer through meta CAS' );
npcink_abilities_toolkit_assert_same( $cloud_artifact_contents, is_readable( $atomic_conflict_path ) ? file_get_contents( $atomic_conflict_path ) : '', 'any logical compensation conflict preserves the complete manifest instead of deleting potentially referenced derivative bytes' );
npcink_abilities_toolkit_assert_true( count( $atomic_conflict_backups_after ) > count( $atomic_conflict_backups_before ), 'logical compensation conflict preserves the bounded batch backup manifest for diagnosis' );
update_post_meta( 79, '_wp_attachment_metadata', $precommit_original_metadata );
wp_update_post( array( 'ID' => 79, 'post_mime_type' => $precommit_original_mime ), true );
wp_update_post( array( 'ID' => 89, 'post_content' => $precommit_original_post_content ), true );
wp_delete_file( $atomic_conflict_path );
foreach ( array_diff( $atomic_conflict_backups_after, $atomic_conflict_backups_before ) as $atomic_conflict_backup_path ) {
	wp_delete_file( $atomic_conflict_backup_path );
}

$cloud_adoption_original_content = (string) ( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][89]->post_content ?? '' );
$cloud_adoption_history_before_failure = $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][79]['_npcink_ai_media_file_replacement_history'] ?? null;
$cloud_adoption_derivatives_before_failure = $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][79]['_npcink_ai_media_optimized_derivatives'] ?? null;
$cloud_adoption_backup_files_before_failure = glob( $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/npcink-abilities-toolkit-backups/2026/06/*' );
$cloud_adoption_backup_files_before_failure = is_array( $cloud_adoption_backup_files_before_failure ) ? $cloud_adoption_backup_files_before_failure : array();
$reference_repair_failed_once = false;
$GLOBALS['npcink_abilities_toolkit_unit_wp_update_post_callback'] = static function ( array $postarr ) use ( &$reference_repair_failed_once ) {
	if ( 89 === (int) ( $postarr['ID'] ?? 0 ) && array_key_exists( 'post_content', $postarr ) && ! $reference_repair_failed_once ) {
		$reference_repair_failed_once = true;
		return new WP_Error( 'unit_wordpress_lifecycle_write_failed', 'Injected WordPress lifecycle write failure.' );
	}
	return null;
};
$cloud_adoption_reference_failure = $core_write_package->adopt_cloud_media_derivative(
	array(
		'attachment_id'                 => 79,
		'derivative_artifact'           => npcink_abilities_toolkit_cloud_artifact_fixture(
			array(
				'artifact_id'    => 'art_00000000000000000000000000000015',
				'width'          => 1,
				'height'         => 1,
				'filesize_bytes' => strlen( $cloud_artifact_contents ),
				'sha256'         => $cloud_artifact_sha256,
			)
		),
		'expected_current_relative_file' => '2026/06/workflow-diagram-image.jpg',
		'expected_current_mime_type'    => 'image/jpeg',
		'expected_derivative_mime_type' => 'image/webp',
		'file_name'                    => 'reference-repair-failure.webp',
		'expected_content_reference_post_ids' => $cloud_adoption_expected_post_ids,
		'expected_content_reference_post_count' => $cloud_adoption_expected_post_count,
		'expected_content_reference_replacement_count' => $cloud_adoption_expected_replacement_count,
		'commit'                       => true,
	)
);
unset( $GLOBALS['npcink_abilities_toolkit_unit_wp_update_post_callback'] );
npcink_abilities_toolkit_assert_true( is_wp_error( $cloud_adoption_reference_failure ) && 'npcink_abilities_toolkit_cloud_adoption_mutation_conflict' === $cloud_adoption_reference_failure->get_error_code(), 'adopt-cloud-media-derivative preserves the atomic reference repair conflict after successful cleanup' );
npcink_abilities_toolkit_assert_same( '2026/06/workflow-diagram-image.jpg', get_post_meta( 79, '_wp_attached_file', true ), 'reference repair failure restores the original attachment pointer' );
npcink_abilities_toolkit_assert_same( 'image/jpeg', get_post_mime_type( 79 ), 'reference repair failure restores the original attachment MIME type' );
npcink_abilities_toolkit_assert_same( $cloud_adoption_original_content, (string) ( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][89]->post_content ?? '' ), 'reference repair failure restores all touched post content' );
npcink_abilities_toolkit_assert_same( $cloud_adoption_history_before_failure, $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][79]['_npcink_ai_media_file_replacement_history'] ?? null, 'reference repair failure preserves existing replacement history' );
npcink_abilities_toolkit_assert_same( $cloud_adoption_derivatives_before_failure, $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][79]['_npcink_ai_media_optimized_derivatives'] ?? null, 'reference repair failure preserves existing derivative metadata' );
npcink_abilities_toolkit_assert_true( ! is_file( $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/2026/06/reference-repair-failure.webp' ), 'reference repair failure removes the newly materialized derivative' );
$cloud_adoption_backup_files_after_failure = glob( $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/npcink-abilities-toolkit-backups/2026/06/*' );
$cloud_adoption_backup_files_after_failure = is_array( $cloud_adoption_backup_files_after_failure ) ? $cloud_adoption_backup_files_after_failure : array();
npcink_abilities_toolkit_assert_same( $cloud_adoption_backup_files_before_failure, $cloud_adoption_backup_files_after_failure, 'reference repair failure removes the new backup without disturbing existing backups' );

$side_effect_free_metadata_before = get_post_meta( 79, '_wp_attachment_metadata', true );
$side_effect_free_generator_called = false;
$side_effect_free_file_path = $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/2026/06/side-effect-free-generation.webp';
file_put_contents( $side_effect_free_file_path, $cloud_artifact_contents );
$GLOBALS['npcink_abilities_toolkit_unit_generate_attachment_metadata_callback'] = static function () use ( &$side_effect_free_generator_called ) {
	$side_effect_free_generator_called = true;
	return array();
};
$side_effect_free_generation_method = new ReflectionMethod( Core_Write_Package::class, 'generate_attachment_metadata_for_file' );
$side_effect_free_generation_method->setAccessible( true );
$side_effect_free_result = $side_effect_free_generation_method->invoke(
	$core_write_package,
	79,
	$side_effect_free_file_path,
	false
);
unset( $GLOBALS['npcink_abilities_toolkit_unit_generate_attachment_metadata_callback'] );
npcink_abilities_toolkit_assert_same( false, $side_effect_free_generator_called, 'non-persisting attachment metadata generation never enters WordPress Core persistent sub-size generation' );
npcink_abilities_toolkit_assert_same( '2026/06/side-effect-free-generation.webp', $side_effect_free_result['file'] ?? '', 'non-persisting attachment metadata generation returns the verified main file path' );
npcink_abilities_toolkit_assert_same( array(), $side_effect_free_result['sizes'] ?? null, 'non-persisting attachment metadata generation defers sub-size regeneration' );
npcink_abilities_toolkit_assert_same( $side_effect_free_metadata_before, get_post_meta( 79, '_wp_attachment_metadata', true ), 'non-persisting attachment metadata generation leaves reviewed attachment metadata unchanged before locked CAS' );
wp_delete_file( $side_effect_free_file_path );

$attachment_metadata_failures_remaining = 2;
$generated_thumb_path = $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/2026/06/metadata-failure-150x150.webp';
$generated_original_path = $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/2026/06/metadata-failure-original.webp';
$preexisting_generated_path = $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/2026/06/preexisting-generated-size.webp';
$preexisting_generated_contents = 'preexisting-generated-size-bytes';
file_put_contents( $preexisting_generated_path, $preexisting_generated_contents );
$GLOBALS['npcink_abilities_toolkit_unit_generate_attachment_metadata_callback'] = static function ( $attachment_id, $file_path ) use ( $generated_thumb_path, $generated_original_path, $preexisting_generated_path ) {
	if ( 79 !== (int) $attachment_id || 'metadata-failure.webp' !== basename( (string) $file_path ) ) {
		return array();
	}
	file_put_contents( $generated_thumb_path, 'generated-thumb-bytes' );
	file_put_contents( $generated_original_path, 'generated-original-bytes' );
	return array(
		'file'           => '2026/06/metadata-failure.webp',
		'width'          => 1,
		'height'         => 1,
		'filesize'       => filesize( $file_path ),
		'original_image' => basename( $generated_original_path ),
		'sizes'          => array(
			'thumbnail' => array(
				'file'      => basename( $generated_thumb_path ),
				'width'     => 1,
				'height'    => 1,
				'mime-type' => 'image/webp',
			),
			'preexisting' => array(
				'file'      => basename( $preexisting_generated_path ),
				'width'     => 1,
				'height'    => 1,
				'mime-type' => 'image/webp',
			),
		),
	);
};
$GLOBALS['npcink_abilities_toolkit_unit_update_post_meta_callback'] = static function ( $post_id, $meta_key ) use ( &$attachment_metadata_failures_remaining ) {
	if ( 79 === (int) $post_id && '_wp_attachment_metadata' === (string) $meta_key && $attachment_metadata_failures_remaining > 0 ) {
		--$attachment_metadata_failures_remaining;
		return false;
	}
	return null;
};
$cloud_adoption_metadata_failure = $core_write_package->adopt_cloud_media_derivative(
	array(
		'attachment_id'                 => 79,
		'derivative_artifact'           => npcink_abilities_toolkit_cloud_artifact_fixture(
			array(
				'artifact_id'    => 'art_00000000000000000000000000000016',
				'width'          => 1,
				'height'         => 1,
				'filesize_bytes' => strlen( $cloud_artifact_contents ),
				'sha256'         => $cloud_artifact_sha256,
			)
		),
		'expected_current_relative_file' => '2026/06/workflow-diagram-image.jpg',
		'expected_current_mime_type'    => 'image/jpeg',
		'expected_derivative_mime_type' => 'image/webp',
		'file_name'                    => 'metadata-failure.webp',
		'expected_content_reference_post_ids' => $cloud_adoption_expected_post_ids,
		'expected_content_reference_post_count' => $cloud_adoption_expected_post_count,
		'expected_content_reference_replacement_count' => $cloud_adoption_expected_replacement_count,
		'commit'                       => true,
	)
);
unset( $GLOBALS['npcink_abilities_toolkit_unit_update_post_meta_callback'], $GLOBALS['npcink_abilities_toolkit_unit_generate_attachment_metadata_callback'] );
npcink_abilities_toolkit_assert_true( is_wp_error( $cloud_adoption_metadata_failure ) && 'npcink_abilities_toolkit_cloud_adoption_mutation_conflict' === $cloud_adoption_metadata_failure->get_error_code(), 'adopt-cloud-media-derivative detects attachment metadata persistence failure inside the locked WordPress meta lifecycle' );
npcink_abilities_toolkit_assert_same( '2026/06/workflow-diagram-image.jpg', get_post_meta( 79, '_wp_attached_file', true ), 'metadata failure restores the original attachment pointer' );
npcink_abilities_toolkit_assert_same( $cloud_adoption_original_content, (string) ( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][89]->post_content ?? '' ), 'metadata failure restores repaired post content' );
npcink_abilities_toolkit_assert_same( $cloud_adoption_history_before_failure, $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][79]['_npcink_ai_media_file_replacement_history'] ?? null, 'metadata failure preserves existing replacement history' );
npcink_abilities_toolkit_assert_same( $cloud_adoption_derivatives_before_failure, $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][79]['_npcink_ai_media_optimized_derivatives'] ?? null, 'metadata failure preserves existing derivative metadata' );
npcink_abilities_toolkit_assert_true( ! is_file( $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/2026/06/metadata-failure.webp' ), 'metadata failure removes the newly materialized derivative' );
npcink_abilities_toolkit_assert_true( ! is_file( $generated_thumb_path ), 'metadata failure removes generated thumbnail files from the in-memory batch manifest' );
npcink_abilities_toolkit_assert_true( ! is_file( $generated_original_path ), 'metadata failure removes generated original_image files even when metadata never persisted' );
npcink_abilities_toolkit_assert_same( $preexisting_generated_contents, is_readable( $preexisting_generated_path ) ? file_get_contents( $preexisting_generated_path ) : '', 'metadata failure cleanup never claims or deletes a generated-size path that predated the batch' );
wp_delete_file( $preexisting_generated_path );
$cloud_lifecycle_hooks = array( 'post_updated' => 0, 'save_post' => 0, 'wp_after_insert_post' => 0 );
foreach ( array_keys( $cloud_lifecycle_hooks ) as $cloud_lifecycle_hook ) {
	add_action(
		$cloud_lifecycle_hook,
		static function ( $post_id ) use ( &$cloud_lifecycle_hooks, $cloud_lifecycle_hook ) {
			if ( in_array( absint( $post_id ), array( 79, 89 ), true ) ) {
				++$cloud_lifecycle_hooks[ $cloud_lifecycle_hook ];
			}
		}
	);
}
$cloud_adoption_commit = $core_write_package->adopt_cloud_media_derivative(
	array(
		'attachment_id'                 => 79,
		'derivative_artifact'           => npcink_abilities_toolkit_cloud_artifact_fixture( array(
			'artifact_id'    => 'art_00000000000000000000000000000014',
			'expires_at'     => gmdate( 'c', time() + 600 ),
			'mime_type'      => 'image/webp',
			'format'         => 'webp',
			'width'          => 1,
			'height'         => 1,
			'filesize_bytes' => strlen( $cloud_artifact_contents ),
			'sha256'         => $cloud_artifact_sha256,
		) ),
		'expected_current_relative_file' => '2026/06/workflow-diagram-image.jpg',
		'expected_current_mime_type'    => 'image/jpeg',
		'expected_derivative_mime_type' => 'image/webp',
		'file_name'                    => 'customer-approved-diagram.webp',
		'expected_content_reference_post_ids' => $cloud_adoption_expected_post_ids,
		'expected_content_reference_post_count' => $cloud_adoption_expected_post_count,
		'expected_content_reference_replacement_count' => $cloud_adoption_expected_replacement_count,
		'batch_id'                     => 'media_optimization_retention_test',
		'optimization_profile'         => 'auto_safe.v1',
		'batch_confirmation_digest'    => 'sha256:' . str_repeat( 'a', 64 ),
		'commit'                       => true,
	)
);
unset( $GLOBALS['npcink_ai_runtime_wp_ability_context'], $GLOBALS['npcink_abilities_toolkit_unit_cloud_artifact_receive_callback'] );
npcink_abilities_toolkit_assert_true( ! is_wp_error( $cloud_adoption_commit ), 'adopt-cloud-media-derivative commit succeeds after approval' . ( is_wp_error( $cloud_adoption_commit ) ? ': ' . $cloud_adoption_commit->get_error_code() : '' ) );
npcink_abilities_toolkit_assert_same( false, $cloud_adoption_commit['dry_run'] ?? null, 'adopt-cloud-media-derivative commit exits dry-run' );
npcink_abilities_toolkit_assert_same( true, $cloud_adoption_commit['replaced'] ?? null, 'adopt-cloud-media-derivative commit replaces the attachment pointer after approval' );
npcink_abilities_toolkit_assert_same( 'media_artifact_verified_transfer.v1', $cloud_adoption_commit['transfer_evidence']['contract_version'] ?? '', 'adopt-cloud-media-derivative returns exact verified transfer evidence for Core audit intake' );
npcink_abilities_toolkit_assert_same( true, $cloud_adoption_commit['transfer_evidence']['image_decoded'] ?? null, 'adopt-cloud-media-derivative reports independent image decode verification' );
npcink_abilities_toolkit_assert_same( 'media_artifact_delivery_ack.v1', $cloud_adoption_commit['delivery_ack']['contract_version'] ?? '', 'adopt-cloud-media-derivative returns the exact Cloud ACK projection' );
npcink_abilities_toolkit_assert_same( 'verified_transfer_only', $cloud_adoption_commit['delivery_ack']['acknowledgement_scope'] ?? '', 'adopt-cloud-media-derivative keeps ACK limited to transfer proof rather than local apply proof' );
npcink_abilities_toolkit_assert_same( 'image/webp', get_post_mime_type( 79 ), 'adopt-cloud-media-derivative commit updates attachment MIME type' );
npcink_abilities_toolkit_assert_same( '2026/06/customer-approved-diagram.webp', get_post_meta( 79, '_wp_attached_file', true ), 'adopt-cloud-media-derivative commit accepts an approved custom derivative file name' );
$cloud_adoption_latest_history = get_post_meta( 79, '_npcink_ai_media_latest_file_replacement', true );
npcink_abilities_toolkit_assert_true( 1 === preg_match( '/^sha256:[a-f0-9]{64}$/', (string) ( $cloud_adoption_latest_history['new_media_fingerprint'] ?? '' ) ) && 1 === preg_match( '/^sha256:[a-f0-9]{64}$/', (string) ( $cloud_adoption_latest_history['derived_from_media_fingerprint'] ?? '' ) ), 'adopt-cloud-media-derivative records canonical source and target fingerprints in replacement lineage' );
npcink_abilities_toolkit_assert_true( in_array( (string) ( $cloud_adoption_latest_history['visual_reuse_policy'] ?? '' ), array( 'reuse', 'reuse_with_human_check', 'requires_reidentification' ), true ) && is_array( $cloud_adoption_latest_history['transform_facts'] ?? null ), 'adopt-cloud-media-derivative records transform facts and a bounded visual reuse policy' );
npcink_abilities_toolkit_assert_same( 'manual_confirmation_required', $cloud_adoption_latest_history['backup_cleanup_policy'] ?? '', 'exact-manifest media optimization keeps its backup until explicit cleanup confirmation' );
npcink_abilities_toolkit_assert_true( $cloud_lifecycle_hooks['post_updated'] >= 2 && $cloud_lifecycle_hooks['save_post'] >= 2 && $cloud_lifecycle_hooks['wp_after_insert_post'] >= 2, 'Cloud MIME and post-content writes retain the WordPress update lifecycle while each row lock is held' );
npcink_abilities_toolkit_assert_same( 1, $cloud_adoption_commit['content_reference_repairs']['updated_count'] ?? 0, 'adopt-cloud-media-derivative commit updates posts that embed the attachment URL' );
npcink_abilities_toolkit_assert_same( '2026/06/customer-approved-diagram.webp', $cloud_adoption_commit['verification']['media_current_file'] ?? '', 'adopt-cloud-media-derivative verification reports current media file' );
npcink_abilities_toolkit_assert_same( 'image/webp', $cloud_adoption_commit['verification']['media_mime_type'] ?? '', 'adopt-cloud-media-derivative verification reports current mime type' );
npcink_abilities_toolkit_assert_same( true, $cloud_adoption_commit['verification']['backup_available'] ?? null, 'adopt-cloud-media-derivative verification confirms backup availability' );
npcink_abilities_toolkit_assert_same( true, $cloud_adoption_commit['verification']['rollback_available'] ?? null, 'adopt-cloud-media-derivative verification confirms rollback availability' );
npcink_abilities_toolkit_assert_same( 89, $cloud_adoption_commit['verification']['post_references_verified'][0]['post_id'] ?? 0, 'adopt-cloud-media-derivative verification records the repaired post reference' );
npcink_abilities_toolkit_assert_same( true, $cloud_adoption_commit['verification']['post_references_verified'][0]['old_url_absent'] ?? null, 'adopt-cloud-media-derivative verification confirms old post URLs are absent' );
npcink_abilities_toolkit_assert_same( true, $cloud_adoption_commit['verification']['post_references_verified'][0]['new_url_present'] ?? null, 'adopt-cloud-media-derivative verification confirms new post URLs are present' );
npcink_abilities_toolkit_assert_true( (int) ( $cloud_adoption_commit['verification']['content_reference_actual_replacement_count'] ?? 0 ) >= 3, 'adopt-cloud-media-derivative verification records actual post content replacement count' );
npcink_abilities_toolkit_assert_true( false !== strpos( (string) ( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][89]->post_content ?? '' ), 'customer-approved-diagram.webp' ), 'adopt-cloud-media-derivative commit rewrites inline image references to the adopted WebP' );
npcink_abilities_toolkit_assert_true( false === strpos( (string) ( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][89]->post_content ?? '' ), 'workflow-diagram-image-300x162.jpg' ), 'adopt-cloud-media-derivative commit removes old sized image references from post content' );
npcink_abilities_toolkit_assert_true( false === strpos( (string) ( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][89]->post_content ?? '' ), 'workflow-diagram-image-original.jpg' ), 'adopt-cloud-media-derivative commit removes metadata original image references from post content' );
npcink_abilities_toolkit_assert_same( 'customer-approved-diagram.webp', $cloud_adoption_commit['proposed_filename'] ?? '', 'adopt-cloud-media-derivative commit records the reviewed filename proposal' );
npcink_abilities_toolkit_assert_same( 'reviewed_input', $cloud_adoption_commit['filename_policy']['source'] ?? '', 'adopt-cloud-media-derivative commit treats explicit file_name as reviewed local input' );
npcink_abilities_toolkit_assert_true( is_readable( $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/' . get_post_meta( 79, '_wp_attached_file', true ) ), 'adopt-cloud-media-derivative commit writes the local derivative file' );
npcink_abilities_toolkit_assert_true( 0 === strpos( (string) ( $cloud_adoption_commit['backup']['relative_file'] ?? '' ), 'npcink-abilities-toolkit-backups/2026/06/' ), 'adopt-cloud-media-derivative commit stores backup outside the public month media directory' );
npcink_abilities_toolkit_assert_true( false !== strpos( (string) ( $cloud_adoption_commit['backup']['relative_file'] ?? '' ), 'npcink-abilities-toolkit-cloud-backup' ), 'adopt-cloud-media-derivative commit records a backup file' );
npcink_abilities_toolkit_assert_true( is_readable( $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/' . (string) ( $cloud_adoption_commit['backup']['relative_file'] ?? '' ) ), 'adopt-cloud-media-derivative commit writes the local backup file' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][87] = (object) array(
	'ID'             => 87,
	'post_title'     => 'Rename Media Fixture',
	'post_status'    => 'inherit',
	'post_type'      => 'attachment',
	'post_excerpt'   => '',
	'post_content'   => '',
	'post_name'      => 'rename-media-fixture',
	'post_author'    => 7,
	'post_parent'    => 0,
	'post_mime_type' => 'image/jpeg',
	'post_date'      => '2026-06-02 10:00:00',
);
$rename_media_path = $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/2026/06/rename-media-fixture.jpg';
if ( ! is_dir( dirname( $rename_media_path ) ) ) {
	mkdir( dirname( $rename_media_path ), 0755, true );
}
file_put_contents( $rename_media_path, 'rename-jpeg-bytes' );
update_post_meta(
	87,
	'_wp_attachment_metadata',
	array(
		'width'    => 1200,
		'height'   => 800,
		'file'     => '2026/06/rename-media-fixture.jpg',
		'filesize' => strlen( 'rename-jpeg-bytes' ),
		'sizes'    => array(
			'medium' => array(
				'file'   => 'rename-media-fixture-300x200.jpg',
				'width'  => 300,
				'height' => 200,
			),
		),
	)
);
update_post_meta( 87, '_wp_attached_file', '2026/06/rename-media-fixture.jpg' );
$rename_preview = $core_write_package->rename_media_file(
	array(
		'attachment_id'                  => 87,
		'target_file_name'               => 'rename-media-fixture-reviewed.jpg',
		'expected_current_relative_file' => '2026/06/rename-media-fixture.jpg',
		'expected_current_md5'           => md5( 'rename-jpeg-bytes' ),
	)
);
npcink_abilities_toolkit_assert_same( true, $rename_preview['dry_run'] ?? null, 'rename-media-file defaults to dry-run preview' );
npcink_abilities_toolkit_assert_same( false, $rename_preview['renamed'] ?? null, 'rename-media-file dry-run does not move files' );
npcink_abilities_toolkit_assert_same( '2026/06/rename-media-fixture-reviewed.jpg', $rename_preview['after']['relative_file'] ?? '', 'rename-media-file dry-run plans target relative file' );
npcink_abilities_toolkit_assert_same( md5( 'rename-jpeg-bytes' ), $rename_preview['before']['content_hashes']['md5'] ?? '', 'rename-media-file dry-run includes current MD5 evidence' );
$rename_mismatch = $core_write_package->rename_media_file(
	array(
		'attachment_id'        => 87,
		'target_file_name'     => 'rename-media-fixture-reviewed.jpg',
		'expected_current_md5' => str_repeat( '0', 32 ),
	)
);
npcink_abilities_toolkit_assert_true( is_wp_error( $rename_mismatch ) && 'npcink_abilities_toolkit_current_md5_mismatch' === $rename_mismatch->get_error_code(), 'rename-media-file rejects current hash mismatches' );
$GLOBALS['npcink_ai_runtime_wp_ability_context']['context'] = array(
	'approval_commit_authorized' => true,
	'approval_id'                => 'approval-media-rename',
);
$rename_commit = $core_write_package->rename_media_file(
	array(
		'attachment_id'                  => 87,
		'target_file_name'               => 'rename-media-fixture-reviewed.jpg',
		'expected_current_relative_file' => '2026/06/rename-media-fixture.jpg',
		'expected_current_md5'           => md5( 'rename-jpeg-bytes' ),
		'commit'                         => true,
	)
);
unset( $GLOBALS['npcink_ai_runtime_wp_ability_context'] );
npcink_abilities_toolkit_assert_true( ! is_wp_error( $rename_commit ), 'rename-media-file commit succeeds after approval' . ( is_wp_error( $rename_commit ) ? ': ' . $rename_commit->get_error_code() : '' ) );
npcink_abilities_toolkit_assert_same( false, $rename_commit['dry_run'] ?? null, 'rename-media-file commit exits dry-run' );
npcink_abilities_toolkit_assert_same( true, $rename_commit['renamed'] ?? null, 'rename-media-file commit renames the attachment main file' );
npcink_abilities_toolkit_assert_same( '2026/06/rename-media-fixture-reviewed.jpg', get_post_meta( 87, '_wp_attached_file', true ), 'rename-media-file commit updates attached file pointer' );
npcink_abilities_toolkit_assert_true( ! is_readable( $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/2026/06/rename-media-fixture.jpg' ), 'rename-media-file commit moves the original main file' );
npcink_abilities_toolkit_assert_true( is_readable( $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/2026/06/rename-media-fixture-reviewed.jpg' ), 'rename-media-file commit writes the renamed main file' );
npcink_abilities_toolkit_assert_true( 0 === strpos( (string) ( $rename_commit['backup']['relative_file'] ?? '' ), 'npcink-abilities-toolkit-backups/2026/06/' ), 'rename-media-file commit stores backup outside the public month media directory' );
npcink_abilities_toolkit_assert_true( is_readable( $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/' . (string) ( $rename_commit['backup']['relative_file'] ?? '' ) ), 'rename-media-file commit writes a rollback backup file' );
$renamed_metadata = wp_get_attachment_metadata( 87 );
npcink_abilities_toolkit_assert_same( '2026/06/rename-media-fixture-reviewed.jpg', $renamed_metadata['file'] ?? '', 'rename-media-file commit updates attachment metadata file' );
npcink_abilities_toolkit_assert_same( 'rename-media-fixture-300x200.jpg', $renamed_metadata['sizes']['medium']['file'] ?? '', 'rename-media-file commit preserves existing size metadata' );
update_post_meta( 79, '_wp_attached_file', '2026/06/workflow-diagram-image-optimized.webp' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][79]->post_mime_type = 'image/webp';
update_post_meta(
	79,
	'_wp_attachment_metadata',
	array(
		'width'    => 1920,
		'height'   => 1034,
		'file'     => '2026/06/workflow-diagram-image-optimized.webp',
		'filesize' => 300000,
	)
);
update_post_meta(
	79,
	'_npcink_ai_media_file_replacement_history',
	array(
		array(
			'replacement_id'     => 'media_replace_unit',
			'status'             => 'active',
			'replaced_at_gmt'    => '2026-06-02T00:00:00+00:00',
			'rolled_back_at_gmt' => '',
			'backup_cleanup_policy' => 'manual_confirmation_required',
			'before'             => array(
				'relative_file' => '2026/06/workflow-diagram-image.jpg',
				'mime_type'     => 'image/jpeg',
				'width'         => 2600,
				'height'        => 1400,
				'media_fingerprint' => 'sha256:' . hash( 'sha256', 'original-jpeg-bytes' ),
			),
			'after'              => array(
				'relative_file' => '2026/06/workflow-diagram-image-optimized.webp',
				'mime_type'     => 'image/webp',
				'width'         => 1920,
				'height'        => 1034,
				'media_fingerprint' => 'sha256:' . hash( 'sha256', 'optimized-webp-bytes' ),
			),
			'backup'             => array(
				'relative_file'  => 'npcink-abilities-toolkit-backups/2026/06/workflow-diagram-image-npcink-abilities-toolkit-backup-media_replace_unit.jpg',
				'mime_type'      => 'image/jpeg',
				'width'          => 2600,
				'height'         => 1400,
				'filesize_bytes' => 900000,
			),
		),
	)
);
$media_restore_backup_path = $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/npcink-abilities-toolkit-backups/2026/06/workflow-diagram-image-npcink-abilities-toolkit-backup-media_replace_unit.jpg';
if ( ! is_dir( dirname( $media_restore_backup_path ) ) ) {
	mkdir( dirname( $media_restore_backup_path ), 0755, true );
}
file_put_contents( $media_restore_backup_path, 'original-jpeg-bytes' );
$media_restore_current_path = $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/2026/06/workflow-diagram-image-optimized.webp';
if ( ! is_dir( dirname( $media_restore_current_path ) ) ) {
	mkdir( dirname( $media_restore_current_path ), 0755, true );
}
file_put_contents( $media_restore_current_path, 'optimized-webp-bytes' );
$media_backups = $core_read_package->list_media_backups(
	array(
		'attachment_id' => 79,
	)
);
npcink_abilities_toolkit_assert_same( true, $media_backups['success'] ?? null, 'list-media-backups returns a success envelope' );
npcink_abilities_toolkit_assert_same( 1, $media_backups['data']['summary']['backup_count'] ?? 0, 'list-media-backups returns recorded backup count' );
npcink_abilities_toolkit_assert_same( 'media_replace_unit', $media_backups['data']['backups'][0]['backup_id'] ?? '', 'list-media-backups exposes backup id for restore' );
npcink_abilities_toolkit_assert_same( true, $media_backups['data']['backups'][0]['file_exists'] ?? null, 'list-media-backups checks backup file availability' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/restore-media-backup', $media_backups['data']['backups'][0]['restore_action']['target_ability_id'] ?? '', 'list-media-backups returns restore-media-backup action metadata' );
$media_restore_preview = $core_write_package->restore_media_backup(
	array(
		'attachment_id'                  => 79,
		'backup_id'                      => 'media_replace_unit',
		'expected_current_relative_file' => '2026/06/workflow-diagram-image-optimized.webp',
	)
);
npcink_abilities_toolkit_assert_same( true, $media_restore_preview['dry_run'] ?? null, 'restore-media-backup defaults to dry-run preview' );
npcink_abilities_toolkit_assert_same( false, $media_restore_preview['restored'] ?? null, 'restore-media-backup dry-run does not switch files' );
npcink_abilities_toolkit_assert_same( '2026/06/workflow-diagram-image.jpg', $media_restore_preview['after']['relative_file'] ?? '', 'restore-media-backup targets the original public media path' );
npcink_abilities_toolkit_assert_true( isset( $media_restore_preview['content_reference_repairs']['replacement_rule_count'] ), 'restore-media-backup previews post content reference repairs for rollback' );
npcink_abilities_toolkit_assert_true( false !== strpos( (string) ( $media_restore_preview['current_backup']['relative_file'] ?? '' ), 'npcink-abilities-toolkit-restore-backup' ), 'restore-media-backup plans a backup of the current main file before restore' );
$GLOBALS['npcink_ai_runtime_wp_ability_context']['context'] = array(
	'approval_commit_authorized' => true,
	'approval_id'                => 'approval-media-restore-failure',
);
add_filter(
	'npcink_abilities_toolkit_media_file_copy_blocked',
	static function ( $blocked, $source_path, $target_path, $context ) {
		unset( $source_path, $target_path );
		return true === $blocked || (
			is_array( $context ) &&
			'restore_media_backup' === (string) ( $context['operation'] ?? '' ) &&
			'backup_current' === (string) ( $context['step'] ?? '' )
		);
	},
	10,
	4
);
$media_restore_backup_failure = $core_write_package->restore_media_backup(
	array(
		'attachment_id'                  => 79,
		'backup_id'                      => 'media_replace_unit',
		'expected_current_relative_file' => '2026/06/workflow-diagram-image-optimized.webp',
		'commit'                         => true,
	)
);
remove_all_filters( 'npcink_abilities_toolkit_media_file_copy_blocked' );
npcink_abilities_toolkit_assert_true( is_wp_error( $media_restore_backup_failure ) && 'npcink_abilities_toolkit_media_backup_failed' === $media_restore_backup_failure->get_error_code(), 'restore-media-backup commit reports current optimized file backup failures' );
npcink_abilities_toolkit_assert_same( '2026/06/workflow-diagram-image-optimized.webp', get_post_meta( 79, '_wp_attached_file', true ), 'restore-media-backup backup failure leaves the optimized attachment pointer unchanged' );
add_filter(
	'npcink_abilities_toolkit_media_file_copy_blocked',
	static function ( $blocked, $source_path, $target_path, $context ) {
		unset( $source_path, $target_path );
		return true === $blocked || (
			is_array( $context ) &&
			'restore_media_backup' === (string) ( $context['operation'] ?? '' ) &&
			'restore_backup' === (string) ( $context['step'] ?? '' )
		);
	},
	10,
	4
);
$media_restore_copy_failure = $core_write_package->restore_media_backup(
	array(
		'attachment_id'                  => 79,
		'backup_id'                      => 'media_replace_unit',
		'expected_current_relative_file' => '2026/06/workflow-diagram-image-optimized.webp',
		'commit'                         => true,
	)
);
remove_all_filters( 'npcink_abilities_toolkit_media_file_copy_blocked' );
unset( $GLOBALS['npcink_ai_runtime_wp_ability_context'] );
npcink_abilities_toolkit_assert_true( is_wp_error( $media_restore_copy_failure ) && 'npcink_abilities_toolkit_media_restore_failed' === $media_restore_copy_failure->get_error_code(), 'restore-media-backup commit reports original backup restore copy failures' );
npcink_abilities_toolkit_assert_same( '2026/06/workflow-diagram-image-optimized.webp', get_post_meta( 79, '_wp_attached_file', true ), 'restore-media-backup copy failure leaves the optimized attachment pointer unchanged' );

