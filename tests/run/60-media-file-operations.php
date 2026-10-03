<?php
/**
 * Ordered regression suite part: 60-media-file-operations.
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

$empty_meta_key = '_npcink_ai_media_latest_file_replacement';
update_post_meta( 79, $empty_meta_key, '' );
$empty_meta_race_injected = false;
$GLOBALS['npcink_abilities_toolkit_unit_wpdb_postmeta_select_for_update_callback'] = static function ( $post_id, $meta_key ) use ( &$empty_meta_race_injected, $empty_meta_key ) {
	if ( ! $empty_meta_race_injected && 79 === (int) $post_id && $empty_meta_key === (string) $meta_key ) {
		$empty_meta_race_injected = true;
		$GLOBALS['npcink_abilities_toolkit_unit_post_meta'][79][ $empty_meta_key ] = 'external-empty-value-winner';
	}
	return null;
};
$empty_meta_race = $cloud_meta_cas_method->invoke(
	$core_write_package,
	79,
	$empty_meta_key,
	array( 'exists' => true, 'value' => '' ),
	array( 'exists' => true, 'value' => 'batch-empty-replacement' )
);
unset( $GLOBALS['npcink_abilities_toolkit_unit_wpdb_postmeta_select_for_update_callback'] );
npcink_abilities_toolkit_assert_true( is_wp_error( $empty_meta_race ) && 'npcink_abilities_toolkit_cloud_adoption_mutation_conflict' === $empty_meta_race->get_error_code(), 'locked raw postmeta CAS rejects an external write when the reviewed previous value was empty' );
npcink_abilities_toolkit_assert_same( 'external-empty-value-winner', get_post_meta( 79, $empty_meta_key, true ), 'empty-value postmeta race preserves the external winner byte-for-byte' );
delete_post_meta( 79, $empty_meta_key );

$case_meta_key = '_npcink_ai_media_latest_optimized_derivative';
update_post_meta( 79, $case_meta_key, 'lowercase-value' );
$case_meta_race_injected = false;
$GLOBALS['npcink_abilities_toolkit_unit_wpdb_postmeta_select_for_update_callback'] = static function ( $post_id, $meta_key ) use ( &$case_meta_race_injected, $case_meta_key ) {
	if ( ! $case_meta_race_injected && 79 === (int) $post_id && $case_meta_key === (string) $meta_key ) {
		$case_meta_race_injected = true;
		$GLOBALS['npcink_abilities_toolkit_unit_post_meta'][79][ $case_meta_key ] = 'LOWERCASE-VALUE';
	}
	return null;
};
$case_meta_race = $cloud_meta_cas_method->invoke(
	$core_write_package,
	79,
	$case_meta_key,
	array( 'exists' => true, 'value' => 'lowercase-value' ),
	array( 'exists' => true, 'value' => 'batch-case-replacement' )
);
unset( $GLOBALS['npcink_abilities_toolkit_unit_wpdb_postmeta_select_for_update_callback'] );
npcink_abilities_toolkit_assert_true( is_wp_error( $case_meta_race ), 'locked raw postmeta CAS rejects case-only external value drift despite case-insensitive database collations' );
npcink_abilities_toolkit_assert_same( 'LOWERCASE-VALUE', get_post_meta( 79, $case_meta_key, true ), 'case-only postmeta race preserves the external winner byte-for-byte' );
delete_post_meta( 79, $case_meta_key );

$missing_meta_key = '_npcink_ai_media_optimized_derivatives';
delete_post_meta( 79, $missing_meta_key );
$missing_meta_race_injected = false;
$GLOBALS['npcink_abilities_toolkit_unit_wpdb_postmeta_select_for_update_callback'] = static function ( $post_id, $meta_key ) use ( &$missing_meta_race_injected, $missing_meta_key ) {
	if ( ! $missing_meta_race_injected && 79 === (int) $post_id && $missing_meta_key === (string) $meta_key ) {
		$missing_meta_race_injected = true;
		$GLOBALS['npcink_abilities_toolkit_unit_post_meta'][79][ $missing_meta_key ] = 'external-second-batch';
		npcink_abilities_toolkit_unit_meta_id( 79, $missing_meta_key );
	}
	return null;
};
$missing_meta_race = $cloud_meta_cas_method->invoke(
	$core_write_package,
	79,
	$missing_meta_key,
	array( 'exists' => false, 'value' => null ),
	array( 'exists' => true, 'value' => 'losing-first-batch' )
);
unset( $GLOBALS['npcink_abilities_toolkit_unit_wpdb_postmeta_select_for_update_callback'] );
npcink_abilities_toolkit_assert_true( is_wp_error( $missing_meta_race ), 'attachment parent-row serialization forces a missing-key contender to re-read and reject the committed second-batch value' );
npcink_abilities_toolkit_assert_same( 'external-second-batch', get_post_meta( 79, $missing_meta_key, true ), 'missing-key postmeta race never creates or overwrites a second logical row' );
delete_post_meta( 79, $missing_meta_key );

$reentrant_meta_key = '_npcink_ai_media_latest_optimized_derivative';
update_post_meta( 79, $reentrant_meta_key, 'outer-before' );
$reentrant_meta_result = null;
$GLOBALS['npcink_abilities_toolkit_unit_update_post_meta_callback'] = static function ( $post_id, $meta_key ) use ( &$reentrant_meta_result, $reentrant_meta_key, $cloud_meta_cas_method, $core_write_package ) {
	if ( null === $reentrant_meta_result && 79 === (int) $post_id && $reentrant_meta_key === (string) $meta_key ) {
		$reentrant_meta_result = $cloud_meta_cas_method->invoke(
			$core_write_package,
			79,
			$reentrant_meta_key,
			array( 'exists' => true, 'value' => 'outer-before' ),
			array( 'exists' => true, 'value' => 'nested-write' )
		);
	}
	return null;
};
$outer_meta_result = $cloud_meta_cas_method->invoke(
	$core_write_package,
	79,
	$reentrant_meta_key,
	array( 'exists' => true, 'value' => 'outer-before' ),
	array( 'exists' => true, 'value' => 'outer-after' )
);
unset( $GLOBALS['npcink_abilities_toolkit_unit_update_post_meta_callback'] );
$reentrant_meta_data = is_wp_error( $reentrant_meta_result ) ? $reentrant_meta_result->get_error_data() : array();
npcink_abilities_toolkit_assert_true( true === $outer_meta_result && is_wp_error( $reentrant_meta_result ), 'WordPress meta hooks cannot open a nested Cloud media transaction on the same package instance' );
npcink_abilities_toolkit_assert_same( 'transaction_reentrant', $reentrant_meta_data['stage'] ?? '', 'nested Cloud media transaction attempts return a bounded reentrancy conflict before START TRANSACTION' );
npcink_abilities_toolkit_assert_same( 'outer-after', get_post_meta( 79, $reentrant_meta_key, true ), 'reentrancy guard preserves the outer locked WordPress meta lifecycle' );
delete_post_meta( 79, $reentrant_meta_key );

$cloud_post_field_cas_method = new ReflectionMethod( Core_Write_Package::class, 'cloud_media_atomic_compare_and_swap_post_field' );
$cloud_post_field_cas_method->setAccessible( true );
$reentrant_post_result = null;
$GLOBALS['npcink_abilities_toolkit_unit_wp_update_post_callback'] = static function ( array $postarr ) use ( &$reentrant_post_result, $cloud_post_field_cas_method, $core_write_package ) {
	if ( null === $reentrant_post_result && 79 === (int) ( $postarr['ID'] ?? 0 ) && array_key_exists( 'post_mime_type', $postarr ) ) {
		$reentrant_post_result = $cloud_post_field_cas_method->invoke( $core_write_package, 79, 'post_mime_type', 'image/jpeg', 'image/png' );
	}
	return null;
};
$outer_post_result = $cloud_post_field_cas_method->invoke( $core_write_package, 79, 'post_mime_type', 'image/jpeg', 'image/webp' );
unset( $GLOBALS['npcink_abilities_toolkit_unit_wp_update_post_callback'] );
$reentrant_post_data = is_wp_error( $reentrant_post_result ) ? $reentrant_post_result->get_error_data() : array();
npcink_abilities_toolkit_assert_true( true === $outer_post_result && is_wp_error( $reentrant_post_result ), 'wp_update_post hooks cannot open a nested Cloud media transaction on the same package instance' );
npcink_abilities_toolkit_assert_same( 'transaction_reentrant', $reentrant_post_data['stage'] ?? '', 'wp_update_post reentrancy is rejected before nested START TRANSACTION can implicitly commit the outer transaction' );
npcink_abilities_toolkit_assert_same( 'image/webp', get_post_mime_type( 79 ), 'wp_update_post reentrancy guard preserves the outer locked post-field lifecycle' );
$restore_post_result = $cloud_post_field_cas_method->invoke( $core_write_package, 79, 'post_mime_type', 'image/webp', 'image/jpeg' );
npcink_abilities_toolkit_assert_same( true, $restore_post_result, 'post-field transaction fixture restores the attachment MIME after reentrancy coverage' );

$GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] = sys_get_temp_dir() . '/npcink-abilities-toolkit-media-' . getmypid();
$workflow_media_path = $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/2026/06/workflow-diagram-image.jpg';
if ( ! is_dir( dirname( $workflow_media_path ) ) ) {
	mkdir( dirname( $workflow_media_path ), 0755, true );
}
file_put_contents( $workflow_media_path, 'original-jpeg-bytes' );
$rollback_unknown_relative = '2026/06/rollback-unknown.webp';
$rollback_unknown_path = $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/' . $rollback_unknown_relative;
file_put_contents( $rollback_unknown_path, 'rollback-unknown-bytes' );
$rollback_unknown_stat = lstat( $rollback_unknown_path );
$rollback_unknown_original_pointer = (string) get_post_meta( 79, '_wp_attached_file', true );
update_post_meta( 79, '_wp_attached_file', $rollback_unknown_relative );
$rollback_unknown_manifest = (object) array(
	'created_files' => array(
		array(
			'path'                  => $rollback_unknown_path,
			'device'                => (int) ( $rollback_unknown_stat['dev'] ?? -1 ),
			'inode'                 => (int) ( $rollback_unknown_stat['ino'] ?? -1 ),
			'created_by_this_batch' => true,
		),
	),
	'mutations' => array(
		'meta:79:_wp_attached_file' => array(
			'kind'     => 'meta',
			'post_id'  => 79,
			'meta_key' => '_wp_attached_file',
			'before'   => array( 'exists' => true, 'value' => $rollback_unknown_original_pointer ),
			'after'    => array( 'exists' => true, 'value' => $rollback_unknown_relative ),
		),
	),
);
$rollback_unknown_cleanup_method = new ReflectionMethod( Core_Write_Package::class, 'cleanup_failed_cloud_media_adoption' );
$rollback_unknown_cleanup_method->setAccessible( true );
$rollback_unknown_package = new Core_Write_Package( $package_categories, $package_registrar );
$GLOBALS['npcink_abilities_toolkit_unit_update_post_meta_callback'] = static function ( $post_id, $meta_key ) {
	return 79 === (int) $post_id && '_wp_attached_file' === (string) $meta_key ? false : null;
};
$GLOBALS['npcink_abilities_toolkit_unit_wpdb_query_callback'] = static function ( $sql ) {
	return 'ROLLBACK' === strtoupper( trim( (string) $sql ) ) ? false : null;
};
$rollback_unknown_cleanup = $rollback_unknown_cleanup_method->invoke(
	$rollback_unknown_package,
	79,
	array( '_cloud_batch_manifest' => $rollback_unknown_manifest ),
	array(),
	array(
		'attachment_mime_type' => 'image/jpeg',
		'post_contents'         => array(),
		'meta'                  => array(
			'_wp_attached_file' => array( 'exists' => true, 'value' => $rollback_unknown_original_pointer ),
		),
	)
);
unset( $GLOBALS['npcink_abilities_toolkit_unit_update_post_meta_callback'], $GLOBALS['npcink_abilities_toolkit_unit_wpdb_query_callback'] );
$GLOBALS['wpdb']->query( 'ROLLBACK' );
$rollback_unknown_data = is_wp_error( $rollback_unknown_cleanup ) ? $rollback_unknown_cleanup->get_error_data() : array();
npcink_abilities_toolkit_assert_true( is_wp_error( $rollback_unknown_cleanup ) && 'npcink_abilities_toolkit_cloud_adoption_cleanup_conflict' === $rollback_unknown_cleanup->get_error_code(), 'cleanup stops immediately when a locked postmeta rollback cannot be confirmed' );
npcink_abilities_toolkit_assert_same( array( 'wordpress_transaction_state_unknown' ), $rollback_unknown_data['conflicts'] ?? array(), 'unknown cleanup transaction state returns one bounded conflict reason' );
npcink_abilities_toolkit_assert_same( $rollback_unknown_relative, get_post_meta( 79, '_wp_attached_file', true ), 'unknown cleanup transaction state preserves the current pointer for operator diagnosis' );
npcink_abilities_toolkit_assert_true( is_file( $rollback_unknown_path ), 'unknown cleanup transaction state skips all manifest file deletion' );
update_post_meta( 79, '_wp_attached_file', $rollback_unknown_original_pointer );
wp_delete_file( $rollback_unknown_path );
$media_url_resolution = $core_read_package->resolve_media_attachment_by_url(
	array(
		'url' => 'https://example.test/wp-content/uploads/2026/06/workflow-diagram-image.jpg',
	)
);
npcink_abilities_toolkit_assert_same( true, $media_url_resolution['success'] ?? null, 'resolve-media-attachment-by-url returns a success envelope for exact uploads URLs' );
npcink_abilities_toolkit_assert_same( true, $media_url_resolution['data']['readonly'] ?? null, 'resolve-media-attachment-by-url is read-only' );
npcink_abilities_toolkit_assert_same( 'resolved', $media_url_resolution['data']['match_status'] ?? '', 'resolve-media-attachment-by-url resolves one exact attachment match' );
npcink_abilities_toolkit_assert_same( 79, $media_url_resolution['data']['attachment_id'] ?? 0, 'resolve-media-attachment-by-url returns the matched attachment id' );
npcink_abilities_toolkit_assert_same( false, $media_url_resolution['data']['boundary']['wordpress_write_included'] ?? null, 'resolve-media-attachment-by-url does not write WordPress data' );
$media_size_url_resolution = $core_read_package->resolve_media_attachment_by_url(
	array(
		'url' => 'https://example.test/wp-content/uploads/2026/06/workflow-diagram-image-300x162.jpg',
	)
);
npcink_abilities_toolkit_assert_same( true, $media_size_url_resolution['success'] ?? null, 'resolve-media-attachment-by-url returns a success envelope for metadata size URLs' );
npcink_abilities_toolkit_assert_same( 79, $media_size_url_resolution['data']['attachment_id'] ?? 0, 'resolve-media-attachment-by-url resolves a metadata size URL to the parent attachment' );
npcink_abilities_toolkit_assert_same( 'metadata_size_file', $media_size_url_resolution['data']['candidates'][0]['match_type'] ?? '', 'resolve-media-attachment-by-url records size variant evidence' );
$external_media_url_resolution = $core_read_package->resolve_media_attachment_by_url(
	array(
		'url' => 'https://cdn.example.invalid/wp-content/uploads/2026/06/workflow-diagram-image.jpg',
	)
);
npcink_abilities_toolkit_assert_true( is_wp_error( $external_media_url_resolution ) && 'npcink_abilities_toolkit_media_url_external' === $external_media_url_resolution->get_error_code(), 'resolve-media-attachment-by-url rejects external uploads-looking URLs' );
$media_inspection = $core_read_package->inspect_media_asset(
	array(
		'attachment_id'              => 79,
		'target_max_width'           => 1920,
		'large_file_threshold_bytes' => 524288,
		'preferred_format'           => 'webp',
	)
);
npcink_abilities_toolkit_assert_same( true, $media_inspection['success'] ?? null, 'inspect-media-asset returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'jpeg', $media_inspection['data']['source_format'] ?? '', 'inspect-media-asset resolves JPEG source format' );
npcink_abilities_toolkit_assert_same( true, $media_inspection['data']['format_plan']['should_convert'] ?? null, 'inspect-media-asset recommends conversion for legacy JPEG' );
npcink_abilities_toolkit_assert_same( true, $media_inspection['data']['format_plan']['should_resize'] ?? null, 'inspect-media-asset recommends resizing over-wide images' );
npcink_abilities_toolkit_assert_same( true, $media_inspection['data']['format_plan']['should_compress'] ?? null, 'inspect-media-asset recommends compression for large images' );
npcink_abilities_toolkit_assert_same( 'webp', $media_inspection['data']['format_plan']['recommended_format'] ?? '', 'inspect-media-asset recommends WebP by default' );
npcink_abilities_toolkit_assert_same( '2026/06/workflow-diagram-image.jpg', $media_inspection['data']['current_relative_file'] ?? '', 'inspect-media-asset returns current relative file for guarded writes' );
npcink_abilities_toolkit_assert_same( true, $media_inspection['data']['content_hashes']['available'] ?? null, 'inspect-media-asset returns available content hashes when the file is readable' );
npcink_abilities_toolkit_assert_same( md5( 'original-jpeg-bytes' ), $media_inspection['data']['content_hashes']['md5'] ?? '', 'inspect-media-asset returns current file MD5' );
npcink_abilities_toolkit_assert_same( hash( 'sha256', 'original-jpeg-bytes' ), $media_inspection['data']['content_hashes']['sha256'] ?? '', 'inspect-media-asset returns current file SHA-256' );
npcink_abilities_toolkit_assert_same( 'local_uploads', $media_inspection['data']['storage']['provider'] ?? '', 'inspect-media-asset reports local uploads storage by default' );
npcink_abilities_toolkit_assert_same( 'local_file', $media_inspection['data']['storage']['source_read_mode'] ?? '', 'inspect-media-asset reports local file source read mode when readable' );
npcink_abilities_toolkit_assert_same( '', $media_inspection['data']['storage']['blocked_reason'] ?? '', 'inspect-media-asset leaves local readable media unblocked' );
$GLOBALS['npcink_abilities_toolkit_unit_upload_baseurl'] = 'https://origin.example.test/wp-content/uploads';
$remote_storage_inspection = $core_read_package->inspect_media_asset(
	array(
		'attachment_id' => 79,
	)
);
unset( $GLOBALS['npcink_abilities_toolkit_unit_upload_baseurl'] );
npcink_abilities_toolkit_assert_same( true, $remote_storage_inspection['success'] ?? null, 'inspect-media-asset returns success for remote-storage-looking media' );
npcink_abilities_toolkit_assert_same( 'remote_object_storage', $remote_storage_inspection['data']['storage']['provider'] ?? '', 'inspect-media-asset detects attachment URLs outside the uploads base as remote object storage' );
npcink_abilities_toolkit_assert_same( 'remote_storage_write_requires_adapter', $remote_storage_inspection['data']['storage']['blocked_reason'] ?? '', 'inspect-media-asset blocks remote storage writes until a host adapter is present' );
$media_cloud_request = $core_read_package->build_media_derivative_cloud_request(
	array(
		'attachment_id'              => 79,
		'target_max_width'           => 1920,
		'large_file_threshold_bytes' => 524288,
		'preferred_format'           => 'webp',
		'quality'                    => 82,
		'crop'                       => array(
			'type'         => 'aspect_ratio',
			'aspect_ratio' => '16:9',
			'position'     => 'center',
		),
	)
);
npcink_abilities_toolkit_assert_same( true, $media_cloud_request['success'] ?? null, 'build-media-derivative-cloud-request returns a success envelope' );
npcink_abilities_toolkit_assert_same( true, $media_cloud_request['data']['readonly'] ?? null, 'media derivative cloud request is read-only' );
npcink_abilities_toolkit_assert_same( 'media_derivative_cloud_request.v1', $media_cloud_request['data']['request_contract_version'] ?? '', 'media derivative cloud request exposes a versioned contract' );
npcink_abilities_toolkit_assert_same( 'local_uploads', $media_cloud_request['data']['storage']['provider'] ?? '', 'media derivative cloud request carries storage preflight evidence' );
npcink_abilities_toolkit_assert_same( false, $media_cloud_request['data']['blocked'] ?? null, 'media derivative cloud request is not blocked for local readable media' );
npcink_abilities_toolkit_assert_same( 'local_file', $media_cloud_request['data']['cloud_execution']['source_read_mode'] ?? '', 'media derivative cloud request carries source read mode for host transport' );
npcink_abilities_toolkit_assert_same( 'generate_optimized_media_derivative', $media_cloud_request['data']['cloud_job_payload']['job_type'] ?? '', 'media derivative cloud request targets derivative generation' );
npcink_abilities_toolkit_assert_same( 'webp', $media_cloud_request['data']['cloud_job_payload']['target_format'] ?? '', 'media derivative cloud request exposes Cloud target format' );
npcink_abilities_toolkit_assert_same( 1920, $media_cloud_request['data']['cloud_job_payload']['max_width'] ?? 0, 'media derivative cloud request exposes Cloud max width' );
npcink_abilities_toolkit_assert_same( 82, $media_cloud_request['data']['cloud_job_payload']['quality'] ?? 0, 'media derivative cloud request exposes Cloud quality' );
npcink_abilities_toolkit_assert_same( 'aspect_ratio', $media_cloud_request['data']['cloud_job_payload']['crop']['type'] ?? '', 'media derivative cloud request preserves crop type' );
npcink_abilities_toolkit_assert_same( '16:9', $media_cloud_request['data']['cloud_job_payload']['crop']['aspect_ratio'] ?? '', 'media derivative cloud request preserves crop aspect ratio' );
npcink_abilities_toolkit_assert_same( 'center', $media_cloud_request['data']['cloud_job_payload']['crop']['position'] ?? '', 'media derivative cloud request preserves crop position' );
npcink_abilities_toolkit_assert_same( 'webp', $media_cloud_request['data']['cloud_job_payload']['requested_derivative']['format'] ?? '', 'media derivative cloud request preserves preferred format' );
npcink_abilities_toolkit_assert_same( 1920, $media_cloud_request['data']['cloud_job_payload']['requested_derivative']['max_width'] ?? 0, 'media derivative cloud request preserves target max width' );
npcink_abilities_toolkit_assert_same( true, $media_cloud_request['data']['cloud_execution']['source_upload_required'] ?? null, 'media derivative cloud request requires host-provided source upload' );
npcink_abilities_toolkit_assert_same( false, $media_cloud_request['data']['cloud_execution']['credentials_included'] ?? null, 'media derivative cloud request does not include credentials' );
npcink_abilities_toolkit_assert_same( false, $media_cloud_request['data']['cloud_execution']['authorization_included'] ?? null, 'media derivative cloud request does not include authorization headers' );
npcink_abilities_toolkit_assert_same( false, $media_cloud_request['data']['cloud_execution']['signed_headers_included'] ?? null, 'media derivative cloud request does not include signed headers' );
npcink_abilities_toolkit_assert_same( 'local_wordpress_host', $media_cloud_request['data']['local_adoption']['final_write_owner'] ?? '', 'media derivative cloud request leaves final writes local' );
npcink_abilities_toolkit_assert_same( false, $media_cloud_request['data']['local_adoption']['wordpress_write_included'] ?? null, 'media derivative cloud request does not write WordPress' );
$GLOBALS['npcink_abilities_toolkit_unit_upload_baseurl'] = 'https://origin.example.test/wp-content/uploads';
$remote_storage_cloud_request = $core_read_package->build_media_derivative_cloud_request(
	array(
		'attachment_id' => 79,
	)
);
unset( $GLOBALS['npcink_abilities_toolkit_unit_upload_baseurl'] );
npcink_abilities_toolkit_assert_same( true, $remote_storage_cloud_request['success'] ?? null, 'media derivative cloud request returns success for remote-storage-looking media' );
npcink_abilities_toolkit_assert_same( true, $remote_storage_cloud_request['data']['blocked'] ?? null, 'media derivative cloud request blocks remote storage without a host adapter' );
npcink_abilities_toolkit_assert_same( 'remote_storage_write_requires_adapter', $remote_storage_cloud_request['data']['blocked_reason'] ?? '', 'media derivative cloud request explains missing remote storage adapter' );
add_filter(
	'npcink_abilities_toolkit_media_storage_inspection',
	static function ( $storage, $attachment_id ) {
		if ( 79 !== (int) $attachment_id ) {
			return $storage;
		}
		$storage['provider']             = 'remote_object_storage';
		$storage['adapter']              = 'aliyun_oss_mock';
		$storage['source_read_mode']     = 'signed_url';
		$storage['write_mode']           = 'provider_api';
		$storage['restore_mode']         = 'provider_backup';
		$storage['cache_purge_required'] = true;
		$storage['blocked_reason']       = '';
		return $storage;
	},
	10,
	2
);
$GLOBALS['npcink_abilities_toolkit_unit_upload_baseurl'] = 'https://origin.example.test/wp-content/uploads';
$mock_oss_cloud_request = $core_read_package->build_media_derivative_cloud_request(
	array(
		'attachment_id' => 79,
	)
);
unset( $GLOBALS['npcink_abilities_toolkit_unit_upload_baseurl'] );
remove_all_filters( 'npcink_abilities_toolkit_media_storage_inspection' );
npcink_abilities_toolkit_assert_same( true, $mock_oss_cloud_request['success'] ?? null, 'media derivative cloud request accepts mock OSS storage adapter readiness' );
npcink_abilities_toolkit_assert_same( false, $mock_oss_cloud_request['data']['blocked'] ?? null, 'mock OSS storage adapter clears remote storage block for review planning' );
npcink_abilities_toolkit_assert_same( 'aliyun_oss_mock', $mock_oss_cloud_request['data']['storage']['adapter'] ?? '', 'mock OSS storage adapter identity is preserved in storage evidence' );
$mock_oss_optimization_plan = $core_read_package->build_media_optimization_plan(
	array(
		'attachment_id'       => 79,
		'media_details_input' => array(
			'title'       => 'Mock OSS optimized workflow diagram',
			'alt'         => 'Mock OSS optimized workflow diagram alt text.',
			'caption'     => 'Mock OSS optimized workflow diagram caption.',
			'description' => 'Mock OSS optimized workflow diagram description.',
			'source_type' => 'owned',
		),
		'derivative_artifact' => npcink_abilities_toolkit_cloud_artifact_fixture( array(
			'artifact_id'    => 'art_00000000000000000000000000000001',
			'expires_at'     => gmdate( 'c', time() + 600 ),
			'mime_type'      => 'image/webp',
			'format'         => 'webp',
			'width'          => 1600,
			'height'         => 862,
			'filesize_bytes' => 12345,
			'sha256'         => hash( 'sha256', 'mock-oss-webp' ),
		) ),
		'storage_preflight'  => $mock_oss_cloud_request['data']['storage'],
	)
);
npcink_abilities_toolkit_assert_same( true, $mock_oss_optimization_plan['success'] ?? null, 'media optimization plan accepts mock OSS storage preflight evidence' );
npcink_abilities_toolkit_assert_same( 'remote_object_storage', $mock_oss_optimization_plan['data']['write_actions'][1]['input']['expected_storage_provider'] ?? '', 'media optimization plan carries expected storage provider into derivative adoption action' );
npcink_abilities_toolkit_assert_same( 'aliyun_oss_mock', $mock_oss_optimization_plan['data']['write_actions'][1]['input']['expected_storage_adapter'] ?? '', 'media optimization plan carries expected storage adapter into derivative adoption action' );
npcink_abilities_toolkit_assert_same( 'provider_api', $mock_oss_optimization_plan['data']['write_actions'][1]['input']['storage_preflight']['write_mode'] ?? '', 'media optimization plan preserves reviewed provider write mode evidence' );
$media_cloud_request_with_watermark = $core_read_package->build_media_derivative_cloud_request(
	array(
		'attachment_id'              => 79,
		'target_max_width'           => 1600,
		'large_file_threshold_bytes' => 524288,
		'preferred_format'           => 'png',
		'quality'                    => 90,
		'watermark'                  => array(
			'type'          => 'image',
			'artifact_id'   => 'art_00000000000000000000000000000002',
			'position'      => 'top_right',
			'opacity'       => 0.5,
			'scale_percent' => 22,
			'margin_px'     => 16,
		),
	)
);
npcink_abilities_toolkit_assert_same( true, $media_cloud_request_with_watermark['success'] ?? null, 'build-media-derivative-cloud-request accepts optional image watermark plans' );
npcink_abilities_toolkit_assert_same( 'png', $media_cloud_request_with_watermark['data']['cloud_job_payload']['target_format'] ?? '', 'watermarked media derivative request exposes Cloud target format' );
npcink_abilities_toolkit_assert_same( 'image', $media_cloud_request_with_watermark['data']['cloud_job_payload']['watermark']['type'] ?? '', 'watermarked media derivative request preserves watermark type' );
npcink_abilities_toolkit_assert_same( 'art_00000000000000000000000000000002', $media_cloud_request_with_watermark['data']['cloud_job_payload']['watermark']['artifact_id'] ?? '', 'watermarked media derivative request preserves watermark artifact reference' );
npcink_abilities_toolkit_assert_same( 'top_right', $media_cloud_request_with_watermark['data']['cloud_job_payload']['watermark']['position'] ?? '', 'watermarked media derivative request preserves watermark position' );
npcink_abilities_toolkit_assert_same( 0.5, $media_cloud_request_with_watermark['data']['cloud_job_payload']['watermark']['opacity'] ?? null, 'watermarked media derivative request preserves watermark opacity' );
npcink_abilities_toolkit_assert_same( 22, $media_cloud_request_with_watermark['data']['cloud_job_payload']['watermark']['scale_percent'] ?? 0, 'watermarked media derivative request preserves watermark scale' );
npcink_abilities_toolkit_assert_same( false, $media_cloud_request_with_watermark['data']['local_adoption']['wordpress_write_included'] ?? null, 'watermarked media derivative request still does not write WordPress' );
$media_cloud_request_with_text_watermark = $core_read_package->build_media_derivative_cloud_request(
	array(
		'attachment_id'    => 79,
		'preferred_format' => 'webp',
		'watermark'        => array(
			'type'       => 'text',
			'text'       => '<strong>AI</strong>',
			'position'   => 'top_right',
			'opacity'    => 0.75,
			'font_size'  => 48,
			'color'      => '#ffffff',
			'background' => 'rgba(0,0,0,0.35)',
			'margin_px'  => 24,
		),
	)
);
npcink_abilities_toolkit_assert_same( true, $media_cloud_request_with_text_watermark['success'] ?? null, 'build-media-derivative-cloud-request accepts optional text watermark plans' );
npcink_abilities_toolkit_assert_same( 'text', $media_cloud_request_with_text_watermark['data']['cloud_job_payload']['watermark']['type'] ?? '', 'text watermarked media derivative request preserves watermark type' );
npcink_abilities_toolkit_assert_same( 'AI', $media_cloud_request_with_text_watermark['data']['cloud_job_payload']['watermark']['text'] ?? '', 'text watermarked media derivative request normalizes plain text content' );
npcink_abilities_toolkit_assert_same( 'top_right', $media_cloud_request_with_text_watermark['data']['cloud_job_payload']['watermark']['position'] ?? '', 'text watermarked media derivative request preserves watermark position' );
npcink_abilities_toolkit_assert_same( 48, $media_cloud_request_with_text_watermark['data']['cloud_job_payload']['watermark']['font_size'] ?? 0, 'text watermarked media derivative request preserves bounded font size' );
npcink_abilities_toolkit_assert_same( '#FFFFFF', $media_cloud_request_with_text_watermark['data']['cloud_job_payload']['watermark']['color'] ?? '', 'text watermarked media derivative request normalizes hex color' );
npcink_abilities_toolkit_assert_same( 'rgba(0,0,0,0.35)', $media_cloud_request_with_text_watermark['data']['cloud_job_payload']['watermark']['background'] ?? '', 'text watermarked media derivative request preserves bounded rgba background' );
npcink_abilities_toolkit_assert_true( ! isset( $media_cloud_request_with_text_watermark['data']['cloud_job_payload']['watermark']['artifact_id'] ), 'text watermarked media derivative request does not require a watermark artifact id' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][90] = (object) array(
	'ID'           => 90,
	'post_title'   => 'Media Optimization Reference Preview Candidate',
	'post_status'  => 'publish',
	'post_type'    => 'post',
	'post_excerpt' => '',
	'post_content' => '<!-- wp:image {"id":79,"sizeSlug":"large"} --><figure class="wp-block-image size-large"><img src="https://example.test/wp-content/uploads/2026/06/workflow-diagram-image-300x162.jpg" srcset="https://example.test/wp-content/uploads/2026/06/workflow-diagram-image-300x162.jpg 300w, https://example.test/wp-content/uploads/2026/06/workflow-diagram-image.jpg 2600w" alt="Workflow diagram" class="wp-image-79" /></figure><!-- /wp:image -->',
	'post_name'    => 'media-optimization-reference-preview-candidate',
	'post_author'  => 7,
);
$invalid_integrity_artifact = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::normalize(
	npcink_abilities_toolkit_cloud_artifact_fixture( array(
		'artifact_id'    => 'art_00000000000000000000000000000003',
		'expires_at'     => gmdate( 'c', time() + 600 ),
		'mime_type'      => 'image/webp',
		'format'         => 'webp',
		'width'          => 1600,
		'height'         => 862,
		'filesize_bytes' => 210000,
		'sha256'         => 'not-a-sha256',
	) )
);
npcink_abilities_toolkit_assert_true( is_wp_error( $invalid_integrity_artifact ) && 'npcink_abilities_toolkit_cloud_artifact_sha256_required' === $invalid_integrity_artifact->get_error_code(), 'shared derivative artifact contract rejects missing or malformed SHA-256 evidence' );
$valid_transform_facts_artifact = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::normalize( npcink_abilities_toolkit_cloud_artifact_fixture() );
npcink_abilities_toolkit_assert_true( ! is_wp_error( $valid_transform_facts_artifact ), 'shared derivative artifact contract accepts complete Cloud v3 scalar transformation facts' );
npcink_abilities_toolkit_assert_same( npcink_abilities_toolkit_cloud_artifact_fixture()['transform_facts'], $valid_transform_facts_artifact['transform_facts'] ?? array(), 'shared derivative artifact normalization preserves Cloud v3 transformation facts' );
$invalid_transform_fact_type = npcink_abilities_toolkit_cloud_artifact_fixture();
$invalid_transform_fact_type['transform_facts']['source_checksum'] = array();
$invalid_transform_fact_type = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::normalize( $invalid_transform_fact_type );
npcink_abilities_toolkit_assert_true( is_wp_error( $invalid_transform_fact_type ) && 'npcink_abilities_toolkit_cloud_artifact_transform_facts_checksum_invalid' === $invalid_transform_fact_type->get_error_code(), 'shared derivative artifact contract rejects non-scalar Cloud v2 checksum facts' );
$unknown_transform_fact = npcink_abilities_toolkit_cloud_artifact_fixture();
$unknown_transform_fact['transform_facts']['provider_detail'] = 'forbidden';
$unknown_transform_fact = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::normalize( $unknown_transform_fact );
npcink_abilities_toolkit_assert_true( is_wp_error( $unknown_transform_fact ) && 'npcink_abilities_toolkit_cloud_artifact_transform_facts_fields_invalid' === $unknown_transform_fact->get_error_code(), 'shared derivative artifact contract rejects undeclared Cloud v2 transformation facts' );
$mismatched_transform_fact = npcink_abilities_toolkit_cloud_artifact_fixture();
$mismatched_transform_fact['transform_facts']['output_width'] = 1599;
$mismatched_transform_fact = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::normalize( $mismatched_transform_fact );
npcink_abilities_toolkit_assert_true( is_wp_error( $mismatched_transform_fact ) && 'npcink_abilities_toolkit_cloud_artifact_transform_facts_output_mismatch' === $mismatched_transform_fact->get_error_code(), 'shared derivative artifact contract rejects transformation facts that disagree with the reviewed Artifact' );
$relative_expiry_artifact = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::normalize(
	npcink_abilities_toolkit_cloud_artifact_fixture( array(
		'artifact_id'    => 'art_00000000000000000000000000000004',
		'expires_at'     => 'tomorrow',
		'mime_type'      => 'image/webp',
		'format'         => 'webp',
		'width'          => 1600,
		'height'         => 862,
		'filesize_bytes' => 210000,
		'sha256'         => hash( 'sha256', 'relative-expiry' ),
	) )
);
npcink_abilities_toolkit_assert_true( is_wp_error( $relative_expiry_artifact ) && 'npcink_abilities_toolkit_cloud_artifact_expiry_required' === $relative_expiry_artifact->get_error_code(), 'shared derivative artifact contract rejects relative timestamps outside the RFC3339 schema' );
$utc_z_expiry_artifact = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::normalize(
	npcink_abilities_toolkit_cloud_artifact_fixture(
		array(
			'artifact_id' => 'art_0000000000000000000000000000001f',
			'expires_at'  => gmdate( 'Y-m-d\TH:i:s\Z', time() + 600 ),
		)
	)
);
npcink_abilities_toolkit_assert_true( ! is_wp_error( $utc_z_expiry_artifact ), 'shared derivative artifact contract accepts canonical UTC Z timestamps' );
$microsecond_expiry = gmdate( 'Y-m-d\TH:i:s', time() + 600 ) . '.123456+00:00';
$microsecond_expiry_artifact = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::normalize(
	npcink_abilities_toolkit_cloud_artifact_fixture(
		array(
			'artifact_id' => 'art_00000000000000000000000000000022',
			'expires_at'  => $microsecond_expiry,
		)
	)
);
npcink_abilities_toolkit_assert_same( $microsecond_expiry, $microsecond_expiry_artifact['expires_at'] ?? '', 'shared derivative artifact contract preserves the exact validated UTC microsecond expiry for delivery ACK binding' );
$non_utc_expiry_artifact = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::normalize(
	npcink_abilities_toolkit_cloud_artifact_fixture(
		array(
			'artifact_id' => 'art_00000000000000000000000000000020',
			'expires_at'  => gmdate( 'Y-m-d\TH:i:s', time() + 600 ) . '+08:00',
		)
	)
);
npcink_abilities_toolkit_assert_true( is_wp_error( $non_utc_expiry_artifact ) && 'npcink_abilities_toolkit_cloud_artifact_expiry_required' === $non_utc_expiry_artifact->get_error_code(), 'shared derivative artifact contract rejects non-UTC offsets' );
$invalid_calendar_expiry_artifact = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::normalize(
	npcink_abilities_toolkit_cloud_artifact_fixture(
		array(
			'artifact_id' => 'art_00000000000000000000000000000021',
			'expires_at'  => '2026-02-30T12:00:00+00:00',
		)
	)
);
npcink_abilities_toolkit_assert_true( is_wp_error( $invalid_calendar_expiry_artifact ) && 'npcink_abilities_toolkit_cloud_artifact_expiry_required' === $invalid_calendar_expiry_artifact->get_error_code(), 'shared derivative artifact contract rejects impossible UTC calendar timestamps' );
$negative_dimension_artifact = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::normalize(
	npcink_abilities_toolkit_cloud_artifact_fixture( array(
		'artifact_id'    => 'art_00000000000000000000000000000005',
		'expires_at'     => gmdate( 'c', time() + 600 ),
		'mime_type'      => 'image/webp',
		'format'         => 'webp',
		'width'          => -1,
		'height'         => 862,
		'filesize_bytes' => 210000,
		'sha256'         => hash( 'sha256', 'negative-dimension' ),
	) )
);
npcink_abilities_toolkit_assert_true( is_wp_error( $negative_dimension_artifact ) && 'npcink_abilities_toolkit_cloud_artifact_dimensions_invalid' === $negative_dimension_artifact->get_error_code(), 'shared derivative artifact contract rejects negative dimensions even through the direct PHP seam' );
$non_integer_dimension_artifact = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::normalize(
	npcink_abilities_toolkit_cloud_artifact_fixture( array(
		'artifact_id'    => 'art_00000000000000000000000000000006',
		'expires_at'     => gmdate( 'c', time() + 600 ),
		'mime_type'      => 'image/webp',
		'format'         => 'webp',
		'width'          => true,
		'height'         => 862.5,
		'filesize_bytes' => 210000,
		'sha256'         => hash( 'sha256', 'non-integer-dimension' ),
	) )
);
npcink_abilities_toolkit_assert_true( is_wp_error( $non_integer_dimension_artifact ) && 'npcink_abilities_toolkit_cloud_artifact_dimensions_invalid' === $non_integer_dimension_artifact->get_error_code(), 'shared derivative artifact contract rejects booleans and floats instead of coercing them into dimensions' );
$max_axis_dimension_artifact = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::normalize(
	npcink_abilities_toolkit_cloud_artifact_fixture(
		array(
			'artifact_id' => 'art_00000000000000000000000000000019',
			'width'       => 8192,
			'height'      => 1,
		)
	)
);
npcink_abilities_toolkit_assert_true( ! is_wp_error( $max_axis_dimension_artifact ), 'shared derivative artifact contract accepts the 8192-pixel axis boundary when decoded pixel area is legal' );
$oversized_axis_artifact = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::normalize(
	npcink_abilities_toolkit_cloud_artifact_fixture(
		array(
			'artifact_id' => 'art_0000000000000000000000000000001a',
			'width'       => 8193,
			'height'      => 1,
		)
	)
);
npcink_abilities_toolkit_assert_true( is_wp_error( $oversized_axis_artifact ) && 'npcink_abilities_toolkit_cloud_artifact_dimensions_invalid' === $oversized_axis_artifact->get_error_code(), 'shared derivative artifact contract rejects an 8193-pixel axis' );
$oversized_area_artifact = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::normalize(
	npcink_abilities_toolkit_cloud_artifact_fixture(
		array(
			'artifact_id' => 'art_0000000000000000000000000000001b',
			'width'       => 4096,
			'height'      => 4097,
		)
	)
);
npcink_abilities_toolkit_assert_true( is_wp_error( $oversized_area_artifact ) && 'npcink_abilities_toolkit_cloud_artifact_dimensions_invalid' === $oversized_area_artifact->get_error_code(), 'shared derivative artifact contract rejects decoded pixel area above 16,777,216 even when each axis is legal' );
$non_integer_filesize_artifact = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::normalize(
	npcink_abilities_toolkit_cloud_artifact_fixture( array(
		'artifact_id'    => 'art_00000000000000000000000000000007',
		'expires_at'     => gmdate( 'c', time() + 600 ),
		'mime_type'      => 'image/webp',
		'format'         => 'webp',
		'width'          => 1600,
		'height'         => 862,
		'filesize_bytes' => true,
		'sha256'         => hash( 'sha256', 'non-integer-filesize' ),
	) )
);
npcink_abilities_toolkit_assert_true( is_wp_error( $non_integer_filesize_artifact ) && 'npcink_abilities_toolkit_cloud_artifact_filesize_invalid' === $non_integer_filesize_artifact->get_error_code(), 'shared derivative artifact contract rejects non-integer byte counts through the direct PHP seam' );
$mismatched_format_artifact = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::normalize(
	npcink_abilities_toolkit_cloud_artifact_fixture( array(
		'artifact_id'    => 'art_00000000000000000000000000000008',
		'expires_at'     => gmdate( 'c', time() + 600 ),
		'mime_type'      => 'image/webp',
		'format'         => 'png',
		'width'          => 1600,
		'height'         => 862,
		'filesize_bytes' => 210000,
		'sha256'         => hash( 'sha256', 'mismatched-format' ),
	) )
);
npcink_abilities_toolkit_assert_true( is_wp_error( $mismatched_format_artifact ) && 'npcink_abilities_toolkit_cloud_artifact_format_mismatch' === $mismatched_format_artifact->get_error_code(), 'shared derivative artifact contract rejects MIME and format contradictions' );
$legacy_url_artifact = npcink_abilities_toolkit_cloud_artifact_fixture( array( 'download_url' => 'https://cloud.invalid/legacy' ) );
$legacy_url_artifact = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::normalize( $legacy_url_artifact );
npcink_abilities_toolkit_assert_true( is_wp_error( $legacy_url_artifact ) && 'npcink_abilities_toolkit_cloud_artifact_descriptor_fields_invalid' === $legacy_url_artifact->get_error_code(), 'shared derivative artifact contract rejects legacy URL fields instead of normalizing them' );
$noncanonical_id_artifact = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::normalize(
	npcink_abilities_toolkit_cloud_artifact_fixture( array( 'artifact_id' => 'art_not_canonical' ) )
);
npcink_abilities_toolkit_assert_true( is_wp_error( $noncanonical_id_artifact ) && 'npcink_abilities_toolkit_cloud_artifact_id_invalid' === $noncanonical_id_artifact->get_error_code(), 'shared derivative artifact contract requires canonical art-prefixed 32-hex ids' );
$missing_filename_basis_artifact = npcink_abilities_toolkit_cloud_artifact_fixture();
unset( $missing_filename_basis_artifact['filename_basis'] );
$missing_filename_basis_artifact = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::normalize( $missing_filename_basis_artifact );
npcink_abilities_toolkit_assert_true( is_wp_error( $missing_filename_basis_artifact ) && 'npcink_abilities_toolkit_cloud_artifact_descriptor_fields_invalid' === $missing_filename_basis_artifact->get_error_code(), 'shared derivative artifact contract requires all exact 11 descriptor fields' );
$invalid_filename_basis_artifact = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::normalize(
	npcink_abilities_toolkit_cloud_artifact_fixture(
		array(
			'filename_basis' => array(
				'owner'                          => 'cloud',
				'strategy'                       => 'cloud_decides_filename',
				'final_sanitize_unique_required' => false,
			),
		)
	)
);
npcink_abilities_toolkit_assert_true( is_wp_error( $invalid_filename_basis_artifact ) && 'npcink_abilities_toolkit_cloud_artifact_filename_basis_value_invalid' === $invalid_filename_basis_artifact->get_error_code(), 'shared derivative artifact contract preserves final filename authority in WordPress' );
$sanitized_filename_artifact = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::normalize(
	npcink_abilities_toolkit_cloud_artifact_fixture( array( 'suggested_filename' => '../cloud-artifact.webp' ) )
);
npcink_abilities_toolkit_assert_true( is_wp_error( $sanitized_filename_artifact ) && 'npcink_abilities_toolkit_cloud_artifact_suggested_filename_invalid' === $sanitized_filename_artifact->get_error_code(), 'shared derivative artifact contract rejects suggested filenames that would change during sanitization' );
$received_fixture_bytes = base64_decode( 'UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AAAAAA', true );
$received_fixture_artifact = npcink_abilities_toolkit_cloud_artifact_fixture(
	array(
		'artifact_id'    => 'art_aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
		'width'          => 1,
		'height'         => 1,
		'filesize_bytes' => strlen( $received_fixture_bytes ),
		'sha256'         => hash( 'sha256', $received_fixture_bytes ),
	)
);
$received_fixture_payload = npcink_abilities_toolkit_cloud_receive_fixture( $received_fixture_artifact, $received_fixture_bytes );
$late_ack_now = time() - 899;
$late_ack_deadline = gmdate( 'c', $late_ack_now + 900 );
$late_acknowledged_at = gmdate( 'c', $late_ack_now + 899 );
$late_ack_artifact_expires_at = gmdate( 'c', $late_ack_now + 1800 );
$late_ack_proposal = npcink_abilities_toolkit_cloud_artifact_fixture(
	array(
		'artifact_id'    => 'art_00000000000000000000000000000022',
		'expires_at'     => gmdate( 'c', $late_ack_now + 1800 ),
		'width'          => 1,
		'height'         => 1,
		'filesize_bytes' => strlen( $received_fixture_bytes ),
		'sha256'         => hash( 'sha256', $received_fixture_bytes ),
	)
);
$late_ack_payload = npcink_abilities_toolkit_cloud_receive_fixture( $late_ack_proposal, $received_fixture_bytes );
$late_ack_payload['expires_at'] = $late_ack_artifact_expires_at;
$late_ack_payload['transfer_evidence']['ack_deadline_at'] = $late_ack_deadline;
$late_ack_payload['delivery_ack']['acknowledged_at'] = $late_acknowledged_at;
$late_ack_payload['delivery_ack']['artifact_expires_at'] = $late_ack_artifact_expires_at;
$late_ack_result = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::verify_received_payload( $late_ack_payload, $late_ack_proposal );
npcink_abilities_toolkit_assert_true( ! is_wp_error( $late_ack_result ), 'receive validation accepts an ACK just before its deadline while retaining the unchanged proposal expiry beyond that deadline' );
$aged_ack_now = time();
$aged_ack_proposal = npcink_abilities_toolkit_cloud_artifact_fixture(
	array(
		'artifact_id'    => 'art_00000000000000000000000000000023',
		'expires_at'     => gmdate( 'c', $aged_ack_now + 600 ),
		'width'          => 1,
		'height'         => 1,
		'filesize_bytes' => strlen( $received_fixture_bytes ),
		'sha256'         => hash( 'sha256', $received_fixture_bytes ),
	)
);
$aged_ack_payload = npcink_abilities_toolkit_cloud_receive_fixture( $aged_ack_proposal, $received_fixture_bytes );
$aged_ack_payload['delivery_ack']['acknowledged_at'] = gmdate( 'c', $aged_ack_now - 360 );
$aged_ack_payload['transfer_evidence']['ack_deadline_at'] = gmdate( 'c', $aged_ack_now - 350 );
$aged_ack_result = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::verify_received_payload( $aged_ack_payload, $aged_ack_proposal );
npcink_abilities_toolkit_assert_true( ! is_wp_error( $aged_ack_result ), 'the same local11 artifact remains receivable six minutes after ACK because ACK does not shorten its original TTL' );
$expired_original_ttl_proposal = $aged_ack_proposal;
$expired_original_ttl_proposal['expires_at'] = gmdate( 'c', $aged_ack_now - 1 );
$expired_original_ttl_payload = $aged_ack_payload;
$expired_original_ttl_payload['expires_at'] = $expired_original_ttl_proposal['expires_at'];
$expired_original_ttl_payload['delivery_ack']['artifact_expires_at'] = $expired_original_ttl_proposal['expires_at'];
$expired_original_ttl_result = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::verify_received_payload( $expired_original_ttl_payload, $expired_original_ttl_proposal );
npcink_abilities_toolkit_assert_true( is_wp_error( $expired_original_ttl_result ) && 'npcink_abilities_toolkit_cloud_artifact_expired' === $expired_original_ttl_result->get_error_code(), 'the unchanged local11 artifact fails closed at its original expiry' );
$expiry_mismatch_payload = $received_fixture_payload;
$expiry_mismatch_payload['expires_at'] = gmdate( 'c', time() + 240 );
$expiry_mismatch_result = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::verify_received_payload( $expiry_mismatch_payload, $received_fixture_artifact );
npcink_abilities_toolkit_assert_true( is_wp_error( $expiry_mismatch_result ) && 'npcink_abilities_toolkit_cloud_artifact_received_expiry_invalid' === $expiry_mismatch_result->get_error_code(), 'receive validation rejects a received expiry that shortens the original local11 lifetime' );
$incomplete_transfer_payload = $received_fixture_payload;
$incomplete_transfer_payload['transfer_evidence']['image_decoded'] = false;
$incomplete_transfer_result = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::verify_received_payload( $incomplete_transfer_payload, $received_fixture_artifact );
npcink_abilities_toolkit_assert_true( is_wp_error( $incomplete_transfer_result ) && 'npcink_abilities_toolkit_cloud_artifact_transfer_verification_incomplete' === $incomplete_transfer_result->get_error_code(), 'receive validation requires every local transfer verification flag to be true' );
$invalid_ack_scope_payload = $received_fixture_payload;
$invalid_ack_scope_payload['delivery_ack']['acknowledgement_scope'] = 'apply_proof';
$invalid_ack_scope_result = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::verify_received_payload( $invalid_ack_scope_payload, $received_fixture_artifact );
npcink_abilities_toolkit_assert_true( is_wp_error( $invalid_ack_scope_result ) && 'npcink_abilities_toolkit_cloud_artifact_delivery_ack_contract_invalid' === $invalid_ack_scope_result->get_error_code(), 'receive validation rejects treating Cloud ACK as local apply proof' );
$extra_receive_field_payload = $received_fixture_payload;
$extra_receive_field_payload['download_url'] = 'https://cloud.invalid/legacy';
$extra_receive_field_result = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::verify_received_payload( $extra_receive_field_payload, $received_fixture_artifact );
npcink_abilities_toolkit_assert_true( is_wp_error( $extra_receive_field_result ) && 'npcink_abilities_toolkit_cloud_artifact_received_payload_fields_invalid' === $extra_receive_field_result->get_error_code(), 'receive validation rejects undeclared legacy delivery fields' );
$non_image_bytes = 'not-an-image';
$non_image_artifact = npcink_abilities_toolkit_cloud_artifact_fixture(
	array(
		'artifact_id'    => 'art_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
		'width'          => 1,
		'height'         => 1,
		'filesize_bytes' => strlen( $non_image_bytes ),
		'sha256'         => hash( 'sha256', $non_image_bytes ),
	)
);
$non_image_result = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::verify_received_payload(
	npcink_abilities_toolkit_cloud_receive_fixture( $non_image_artifact, $non_image_bytes ),
	$non_image_artifact
);
npcink_abilities_toolkit_assert_true( is_wp_error( $non_image_result ) && 'npcink_abilities_toolkit_cloud_artifact_image_decode_failed' === $non_image_result->get_error_code(), 'receive validation independently rejects bytes that getimagesize cannot decode' );
$media_optimization_plan = $core_read_package->build_media_optimization_plan(
	array(
		'attachment_id'                 => 79,
		'media_details_input'           => array(
			'title'       => 'Optimized workflow diagram',
			'alt'         => 'AI generated workflow diagram',
			'caption'     => 'AI generated workflow diagram.',
			'description' => 'Optimized media metadata for a workflow diagram.',
			'source_type' => 'ai_generated',
		),
		'derivative_artifact'           => npcink_abilities_toolkit_cloud_artifact_fixture( array(
			'artifact_id'        => 'art_00000000000000000000000000000009',
			'expires_at'         => gmdate( 'c', time() + 600 ),
			'mime_type'          => 'image/webp',
			'format'             => 'webp',
			'width'              => 1600,
			'height'             => 862,
			'filesize_bytes'     => 210000,
			'sha256'             => hash( 'sha256', 'media-optimization-plan-artifact' ),
			'suggested_filename' => 'workflow-diagram-cloud-plan.webp',
		) ),
		'file_name'                     => 'f553110d20d666349676892b1b0fbeb7.webp',
		'expected_current_mime_type'    => 'image/jpeg',
		'expected_derivative_mime_type' => 'image/webp',
	)
);
npcink_abilities_toolkit_assert_same( true, $media_optimization_plan['success'] ?? null, 'build-media-optimization-plan returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'media_optimization_plan', $media_optimization_plan['data']['artifact_type'] ?? '', 'media optimization plan declares Core media optimization artifact type' );
npcink_abilities_toolkit_assert_same( 'batch', $media_optimization_plan['data']['proposal_mode'] ?? '', 'media optimization plan requests batch proposal mode' );
npcink_abilities_toolkit_assert_same( true, $media_optimization_plan['data']['batch_approval'] ?? null, 'media optimization plan requests one Core approval' );
npcink_abilities_toolkit_assert_same( 2, count( (array) ( $media_optimization_plan['data']['write_actions'] ?? array() ) ), 'media optimization plan includes metadata and derivative actions' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/update-media-details', $media_optimization_plan['data']['write_actions'][0]['target_ability_id'] ?? '', 'media optimization plan starts with metadata action' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/adopt-cloud-media-derivative', $media_optimization_plan['data']['write_actions'][1]['target_ability_id'] ?? '', 'media optimization plan includes Cloud derivative adoption action' );
npcink_abilities_toolkit_assert_same( hash( 'sha256', 'media-optimization-plan-artifact' ), $media_optimization_plan['data']['write_actions'][1]['input']['derivative_artifact']['sha256'] ?? '', 'media optimization plan binds the governed adoption action to the reviewed artifact SHA-256' );
$planned_derivative_artifact = $media_optimization_plan['data']['write_actions'][1]['input']['derivative_artifact'] ?? array();
$write_derivative_schema = $package_abilities['npcink-abilities-toolkit/adopt-cloud-media-derivative']['input_schema']['properties']['derivative_artifact'] ?? array();
npcink_abilities_toolkit_assert_same( array(), array_values( array_diff( array_keys( $planned_derivative_artifact ), array_keys( (array) ( $write_derivative_schema['properties'] ?? array() ) ) ) ), 'media optimization plan emits no derivative artifact fields rejected by the target write ability schema' );
npcink_abilities_toolkit_assert_same( array(), array_values( array_diff( (array) ( $write_derivative_schema['required'] ?? array() ), array_keys( $planned_derivative_artifact ) ) ), 'media optimization plan emits every derivative artifact field required by the target write ability schema' );
npcink_abilities_toolkit_assert_true( ! isset( $planned_derivative_artifact['expires_ts'] ), 'media optimization plan keeps parsed expiry timestamps internal instead of leaking undeclared action input' );
npcink_abilities_toolkit_assert_same( 'f553110d20d666349676892b1b0fbeb7.webp', $media_optimization_plan['data']['write_actions'][1]['input']['file_name'] ?? '', 'media optimization plan passes reviewed derivative file_name into adoption action' );
npcink_abilities_toolkit_assert_same( array( 90 ), $media_optimization_plan['data']['write_actions'][1]['input']['expected_content_reference_post_ids'] ?? array(), 'media optimization plan carries reviewed content reference post targets into adoption input' );
npcink_abilities_toolkit_assert_same( 1, $media_optimization_plan['data']['write_actions'][1]['input']['expected_content_reference_post_count'] ?? null, 'media optimization plan carries reviewed content reference post count into adoption input' );
npcink_abilities_toolkit_assert_same( $media_optimization_plan['data']['derivative_preview']['content_reference_repairs']['replacement_count'] ?? null, $media_optimization_plan['data']['write_actions'][1]['input']['expected_content_reference_replacement_count'] ?? null, 'media optimization plan carries reviewed content reference replacement count into adoption input' );
npcink_abilities_toolkit_assert_same( 0, npcink_abilities_toolkit_count_plan_actions_for_ability( (array) ( $media_optimization_plan['data']['write_actions'] ?? array() ), 'npcink-abilities-toolkit/patch-post-content' ), 'media optimization plan keeps post-content repair inside derivative adoption evidence' );
npcink_abilities_toolkit_assert_same( false, $media_optimization_plan['data']['commit_execution'] ?? null, 'media optimization plan does not execute commits' );
npcink_abilities_toolkit_assert_same( true, $media_optimization_plan['meta']['readonly'] ?? null, 'media optimization plan remains read-only' );
npcink_abilities_toolkit_assert_same( 1, $media_optimization_plan['data']['derivative_preview']['content_reference_repairs']['post_count'] ?? 0, 'media optimization plan previews post content reference repairs inside derivative evidence' );
npcink_abilities_toolkit_assert_same( 90, $media_optimization_plan['data']['derivative_preview']['content_reference_repairs']['repairs'][0]['post_id'] ?? 0, 'media optimization reference preview targets the referencing post' );
npcink_abilities_toolkit_assert_true( (int) ( $media_optimization_plan['data']['derivative_preview']['content_reference_repairs']['replacement_count'] ?? 0 ) >= 2, 'media optimization reference preview includes old main and sized image references' );
npcink_abilities_toolkit_assert_true( (int) ( $media_optimization_plan['data']['derivative_preview']['content_reference_repairs']['replacement_rule_count'] ?? 0 ) >= 2, 'media optimization reference preview distinguishes replacement rule count' );
npcink_abilities_toolkit_assert_true( (int) ( $media_optimization_plan['data']['derivative_preview']['content_reference_repairs']['actual_replacement_count'] ?? 0 ) >= 1, 'media optimization reference preview reports actual replacement count' );
npcink_abilities_toolkit_assert_true( isset( $media_optimization_plan['data']['derivative_preview']['content_reference_repairs']['unmatched_rules'] ), 'media optimization reference preview exposes unmatched rules' );
npcink_abilities_toolkit_assert_true( false !== strpos( (string) ( $media_optimization_plan['data']['derivative_preview']['content_reference_repairs']['repairs'][0]['operations'][0]['replace'] ?? '' ), 'f553110d20d666349676892b1b0fbeb7.webp' ), 'media optimization reference preview replaces inline URLs with the reviewed derivative filename' );
npcink_abilities_toolkit_assert_same( $media_optimization_plan['data']['derivative_preview']['content_reference_repairs'], $media_optimization_plan['data']['content_reference_repairs_preview'] ?? array(), 'media optimization plan exposes content reference repairs at top level for Core review summaries' );
$media_adoption_preflight_summary = $core_read_package->build_media_adoption_preflight_summary(
	array(
		'attachment_id'        => 79,
		'derivative_artifact'  => npcink_abilities_toolkit_cloud_artifact_fixture( array(
			'artifact_id'        => 'art_0000000000000000000000000000000a',
			'expires_at'         => gmdate( 'c', time() + 600 ),
			'mime_type'          => 'image/webp',
			'format'             => 'webp',
			'width'              => 1600,
			'height'             => 862,
			'filesize_bytes'     => 210000,
			'sha256'             => hash( 'sha256', 'media-preflight-artifact' ),
			'processing_warnings' => array( 'source_resized' ),
		) ),
		'file_name'            => 'f553110d20d666349676892b1b0fbeb7.webp',
		'include_settings_scan' => true,
	)
);
npcink_abilities_toolkit_assert_same( true, $media_adoption_preflight_summary['success'] ?? null, 'media adoption preflight summary returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'media_adoption_preflight_summary', $media_adoption_preflight_summary['data']['artifact_type'] ?? '', 'media adoption preflight summary declares its artifact type' );
npcink_abilities_toolkit_assert_same( true, $media_adoption_preflight_summary['data']['readonly'] ?? null, 'media adoption preflight summary is read-only' );
npcink_abilities_toolkit_assert_same( false, $media_adoption_preflight_summary['data']['direct_wordpress_write'] ?? null, 'media adoption preflight summary declares no direct WordPress write' );
npcink_abilities_toolkit_assert_same( false, $media_adoption_preflight_summary['data']['proposal_created'] ?? null, 'media adoption preflight summary does not create Core proposals' );
npcink_abilities_toolkit_assert_same( false, $media_adoption_preflight_summary['data']['cloud_call_included'] ?? null, 'media adoption preflight summary does not call Cloud' );
npcink_abilities_toolkit_assert_true( ! isset( $media_adoption_preflight_summary['data']['write_actions'] ), 'media adoption preflight summary does not expose write actions' );
npcink_abilities_toolkit_assert_same( 'art_0000000000000000000000000000000a', $media_adoption_preflight_summary['data']['derivative']['artifact_id'] ?? '', 'media adoption preflight summary includes derivative artifact evidence' );
npcink_abilities_toolkit_assert_same( true, $media_adoption_preflight_summary['data']['readiness']['can_submit_core_proposal'] ?? null, 'media adoption preflight summary marks artifact-backed adoption as proposal-ready' );
npcink_abilities_toolkit_assert_same( 1, $media_adoption_preflight_summary['data']['content_reference_summary']['post_count'] ?? 0, 'media adoption preflight summary includes bounded content reference impact' );
npcink_abilities_toolkit_assert_true( in_array( 'settings_reference_scan_deferred', (array) ( $media_adoption_preflight_summary['data']['warnings'] ?? array() ), true ), 'media adoption preflight summary defers settings scans to the dedicated repair plan ability' );
npcink_abilities_toolkit_assert_true( in_array( 'submit_media_optimization_proposal', (array) ( $media_adoption_preflight_summary['data']['next_steps'] ?? array() ), true ), 'media adoption preflight summary points to governed Core proposal submission' );
$media_adoption_preflight_missing_artifact = $core_read_package->build_media_adoption_preflight_summary(
	array(
		'attachment_id' => 79,
	)
);
npcink_abilities_toolkit_assert_same( true, $media_adoption_preflight_missing_artifact['success'] ?? null, 'media adoption preflight summary succeeds without derivative artifact' );
npcink_abilities_toolkit_assert_same( false, $media_adoption_preflight_missing_artifact['data']['derivative']['available'] ?? null, 'media adoption preflight summary reports a missing derivative artifact' );
npcink_abilities_toolkit_assert_same( false, $media_adoption_preflight_missing_artifact['data']['readiness']['can_submit_core_proposal'] ?? null, 'media adoption preflight summary blocks proposal readiness without artifact evidence' );
npcink_abilities_toolkit_assert_true( in_array( 'generate_derivative_preview', (array) ( $media_adoption_preflight_missing_artifact['data']['next_steps'] ?? array() ), true ), 'media adoption preflight summary points missing artifacts to preview generation' );
unset( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][90] );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][790] = (object) array(
	'ID'             => 790,
	'post_title'     => 'Scaled unique suffix source image',
	'post_status'    => 'inherit',
	'post_type'      => 'attachment',
	'post_excerpt'   => '',
	'post_content'   => '',
	'post_name'      => 'photo-scaled-1',
	'post_author'    => 7,
	'post_parent'    => 0,
	'post_mime_type' => 'image/jpeg',
);
update_post_meta(
	790,
	'_wp_attachment_metadata',
	array(
		'width'          => 2600,
		'height'         => 1463,
		'file'           => '2026/06/photo-scaled-1.jpg',
		'filesize'       => 940000,
		'original_image' => 'photo-scaled.jpg',
		'sizes'          => array(),
	)
);
update_post_meta( 790, '_wp_attached_file', '2026/06/photo-scaled-1.jpg' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][791] = (object) array(
	'ID'           => 791,
	'post_title'   => 'Media Optimization Original Reference Candidate',
	'post_status'  => 'publish',
	'post_type'    => 'post',
	'post_excerpt' => '',
	'post_content' => '<figure><img src="https://example.test/wp-content/uploads/2026/06/photo-scaled.jpg" srcset="https://example.test/wp-content/uploads/2026/06/photo-scaled.jpg 2600w" class="wp-image-790" /></figure>',
	'post_name'    => 'media-optimization-original-reference-candidate',
	'post_author'  => 7,
);
$media_optimization_original_plan = $core_read_package->build_media_optimization_plan(
	array(
		'attachment_id'                 => 790,
		'media_details_input'           => array(
			'title'       => 'Optimized original reference photo',
			'alt'         => 'Optimized original reference photo',
			'caption'     => 'Optimized original reference photo.',
			'description' => 'Optimized media metadata for an original reference photo.',
			'source_type' => 'ai_generated',
		),
		'derivative_artifact'           => npcink_abilities_toolkit_cloud_artifact_fixture( array(
			'artifact_id'        => 'art_0000000000000000000000000000000b',
			'expires_at'         => gmdate( 'c', time() + 600 ),
			'mime_type'          => 'image/webp',
			'format'             => 'webp',
			'width'              => 1920,
			'height'             => 1080,
			'filesize_bytes'     => 126482,
			'sha256'             => hash( 'sha256', 'media-original-reference-artifact' ),
			'suggested_filename' => 'photo-optimized.webp',
		) ),
		'expected_current_mime_type'    => 'image/jpeg',
		'expected_derivative_mime_type' => 'image/webp',
	)
);
npcink_abilities_toolkit_assert_same( true, $media_optimization_original_plan['success'] ?? null, 'media optimization plan succeeds for unique-suffixed current files' );
npcink_abilities_toolkit_assert_same( 1, $media_optimization_original_plan['data']['derivative_preview']['content_reference_repairs']['post_count'] ?? 0, 'media optimization plan previews repairs for metadata original_image references' );
npcink_abilities_toolkit_assert_same( 791, $media_optimization_original_plan['data']['derivative_preview']['content_reference_repairs']['repairs'][0]['post_id'] ?? 0, 'media optimization original reference preview targets the post using the pre-unique filename' );
npcink_abilities_toolkit_assert_true( false !== strpos( (string) ( $media_optimization_original_plan['data']['derivative_preview']['content_reference_repairs']['repairs'][0]['operations'][0]['find'] ?? '' ), 'photo-scaled.jpg' ), 'media optimization original reference preview finds the pre-unique filename' );
npcink_abilities_toolkit_assert_true( false !== strpos( (string) ( $media_optimization_original_plan['data']['derivative_preview']['content_reference_repairs']['repairs'][0]['operations'][0]['replace'] ?? '' ), 'photo-optimized.webp' ), 'media optimization original reference preview replaces with the derivative filename' );
unset( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][790], $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][791] );
$cloud_media_adoption_sources = $core_write_package_source . "\n" . $cloud_media_write_trait;
npcink_abilities_toolkit_assert_true( false !== strpos( $cloud_media_adoption_sources, 'npcink_cloud_addon_receive_media_derivative_artifact' ), 'adopt-cloud-media-derivative uses the verified Cloud Addon receive seam' );
npcink_abilities_toolkit_assert_true( false === strpos( $cloud_media_adoption_sources, 'npcink_cloud_addon_download_media_derivative_artifact' ) && false === strpos( $cloud_media_adoption_sources, 'npcink_abilities_toolkit_cloud_media_derivative_artifact_download' ), 'adopt-cloud-media-derivative removes legacy download helper and filter seams' );
npcink_abilities_toolkit_assert_true( false !== strpos( $core_write_package_source, 'npcink_abilities_toolkit_media_file_write_blocked' ), 'media write execution exposes a bounded failure-injection hook for verification tests' );
npcink_abilities_toolkit_assert_true( false !== strpos( $core_write_package_source, 'npcink_abilities_toolkit_media_file_copy_blocked' ), 'media copy execution exposes a bounded failure-injection hook for verification tests' );
npcink_abilities_toolkit_assert_true( false !== strpos( $core_write_package_source, 'is_media_uploads_path_allowed' ), 'media file helpers enforce uploads path containment' );
npcink_abilities_toolkit_assert_true( false !== strpos( $core_write_package_source, 'generate_attachment_metadata_for_file' ), 'media uploads share an explicit attachment metadata persistence helper' );
npcink_abilities_toolkit_assert_true( false !== strpos( $core_write_package_source, 'minimal_attachment_metadata_for_file' ), 'media uploads persist minimal dimensions when full WordPress image helpers are unavailable' );
$write_media_file_reflection = new ReflectionMethod( $core_write_package, 'write_media_file' );
$write_media_file_reflection->setAccessible( true );
$outside_uploads_path = sys_get_temp_dir() . '/npcink-abilities-toolkit-outside-uploads-' . getmypid() . '.jpg';
npcink_abilities_toolkit_assert_same( false, $write_media_file_reflection->invoke( $core_write_package, $outside_uploads_path, 'outside', array() ), 'media write helper rejects targets outside uploads basedir' );
npcink_abilities_toolkit_assert_true( ! is_readable( $outside_uploads_path ), 'outside uploads path containment test does not create a file' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][88] = (object) array(
	'ID'           => 88,
	'post_title'   => 'Rename Reference Candidate',
	'post_status'  => 'publish',
	'post_type'    => 'post',
	'post_excerpt' => '',
	'post_content' => '<!-- wp:image {"id":79,"sizeSlug":"full"} --><figure class="wp-block-image size-full"><img src="https://example.test/wp-content/uploads/2026/06/workflow-diagram-image.jpg" alt="Workflow diagram" class="wp-image-79" /></figure><!-- /wp:image -->',
	'post_name'    => 'rename-reference-candidate',
	'post_author'  => 7,
);
$media_rename_plan = $core_read_package->build_media_rename_plan(
	array(
		'attachment_id'        => 79,
		'target_file_name'     => 'workflow-diagram-image-reviewed',
		'expected_current_md5' => md5( 'original-jpeg-bytes' ),
	)
);
npcink_abilities_toolkit_assert_same( true, $media_rename_plan['success'] ?? null, 'build-media-rename-plan returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'media_rename_plan', $media_rename_plan['data']['artifact_type'] ?? '', 'media rename plan declares Core media rename artifact type' );
npcink_abilities_toolkit_assert_same( 'batch', $media_rename_plan['data']['proposal_mode'] ?? '', 'media rename plan batches rename with exact content reference updates' );
npcink_abilities_toolkit_assert_same( true, $media_rename_plan['data']['batch_approval'] ?? null, 'media rename plan requests one approval for rename and reference updates' );
npcink_abilities_toolkit_assert_same( 2, count( (array) ( $media_rename_plan['data']['write_actions'] ?? array() ) ), 'media rename plan includes rename and post reference patch actions' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/rename-media-file', $media_rename_plan['data']['write_actions'][0]['target_ability_id'] ?? '', 'media rename plan targets rename-media-file' );
npcink_abilities_toolkit_assert_same( 'workflow-diagram-image-reviewed.jpg', $media_rename_plan['data']['write_actions'][0]['input']['target_file_name'] ?? '', 'media rename plan appends the current extension when omitted' );
npcink_abilities_toolkit_assert_same( md5( 'original-jpeg-bytes' ), $media_rename_plan['data']['write_actions'][0]['input']['expected_current_md5'] ?? '', 'media rename plan carries MD5 guard into write action' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/patch-post-content', $media_rename_plan['data']['write_actions'][1]['target_ability_id'] ?? '', 'media rename plan patches post content references after rename' );
npcink_abilities_toolkit_assert_same( 88, $media_rename_plan['data']['write_actions'][1]['input']['post_id'] ?? 0, 'media rename plan targets the post that embeds the renamed image URL' );
npcink_abilities_toolkit_assert_true( false !== strpos( (string) ( $media_rename_plan['data']['write_actions'][1]['input']['operations'][0]['find'] ?? '' ), 'workflow-diagram-image.jpg' ), 'media rename plan finds the old image URL in post content' );
npcink_abilities_toolkit_assert_true( false !== strpos( (string) ( $media_rename_plan['data']['write_actions'][1]['input']['operations'][0]['replace'] ?? '' ), 'workflow-diagram-image-reviewed.jpg' ), 'media rename plan replaces post content with the renamed image URL' );
npcink_abilities_toolkit_assert_same( 1, $media_rename_plan['data']['reference_repair']['action_count'] ?? 0, 'media rename plan reports one exact reference repair action' );
npcink_abilities_toolkit_assert_same( false, $media_rename_plan['data']['commit_execution'] ?? null, 'media rename plan does not execute commits' );
npcink_abilities_toolkit_assert_same( true, $media_rename_plan['meta']['readonly'] ?? null, 'media rename plan remains read-only' );
unset( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][88] );
$media_rename_plan_invalid_hash = $core_read_package->build_media_rename_plan(
	array(
		'attachment_id'        => 79,
		'target_file_name'     => 'workflow-diagram-image-reviewed.jpg',
		'expected_current_md5' => 'not-a-valid-md5',
	)
);
npcink_abilities_toolkit_assert_true( is_wp_error( $media_rename_plan_invalid_hash ) && 'npcink_abilities_toolkit_expected_md5_invalid' === $media_rename_plan_invalid_hash->get_error_code(), 'media rename plan rejects invalid expected MD5 values' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][84] = (object) array(
	'ID'             => 84,
	'post_title'     => 'April Campaign JPEG',
	'post_status'    => 'inherit',
	'post_type'      => 'attachment',
	'post_excerpt'   => '',
	'post_content'   => '',
	'post_name'      => 'april-campaign-jpeg',
	'post_author'    => 7,
	'post_parent'    => 0,
	'post_mime_type' => 'image/jpeg',
	'post_date'      => '2026-04-12 10:00:00',
);
update_post_meta(
	84,
	'_wp_attachment_metadata',
	array(
		'width'    => 1800,
		'height'   => 1000,
		'file'     => '2026/04/april-campaign-jpeg.jpg',
		'filesize' => 700000,
	)
);
update_post_meta( 84, '_wp_attached_file', '2026/04/april-campaign-jpeg.jpg' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][85] = (object) array(
	'ID'             => 85,
	'post_title'     => 'April Existing PNG',
	'post_status'    => 'inherit',
	'post_type'      => 'attachment',
	'post_excerpt'   => '',
	'post_content'   => '',
	'post_name'      => 'april-existing-png',
	'post_author'    => 7,
	'post_parent'    => 0,
	'post_mime_type' => 'image/png',
	'post_date'      => '2026-04-18 09:00:00',
);
update_post_meta(
	85,
	'_wp_attachment_metadata',
	array(
		'width'    => 1600,
		'height'   => 900,
		'file'     => '2026/04/april-existing-png.png',
		'filesize' => 600000,
	)
);
update_post_meta( 85, '_wp_attached_file', '2026/04/april-existing-png.png' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][86] = (object) array(
	'ID'             => 86,
	'post_title'     => 'May Campaign JPEG',
	'post_status'    => 'inherit',
	'post_type'      => 'attachment',
	'post_excerpt'   => '',
	'post_content'   => '',
	'post_name'      => 'may-campaign-jpeg',
	'post_author'    => 7,
	'post_parent'    => 0,
	'post_mime_type' => 'image/jpeg',
	'post_date'      => '2026-05-02 10:00:00',
);
update_post_meta(
	86,
	'_wp_attachment_metadata',
	array(
		'width'    => 1700,
		'height'   => 950,
		'file'     => '2026/05/may-campaign-jpeg.jpg',
		'filesize' => 650000,
	)
);
update_post_meta( 86, '_wp_attached_file', '2026/05/may-campaign-jpeg.jpg' );
$batch_media_files = array(
	84 => array( '2026/04/april-campaign-jpeg.jpg', 'april-jpeg-bytes' ),
	85 => array( '2026/04/april-existing-png.png', 'april-png-bytes' ),
	86 => array( '2026/05/may-campaign-jpeg.jpg', 'may-jpeg-bytes' ),
);
foreach ( $batch_media_files as $batch_attachment_id => $batch_media_file ) {
	$batch_media_path = $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/' . $batch_media_file[0];
	if ( ! is_dir( dirname( $batch_media_path ) ) ) {
		mkdir( dirname( $batch_media_path ), 0755, true );
	}
	file_put_contents( $batch_media_path, $batch_media_file[1] );
	update_post_meta( $batch_attachment_id, '_wp_attached_file', $batch_media_path );
}
$media_derivative_batch_plan = $core_read_package->build_media_derivative_batch_plan(
	array(
		'date_from'  => '2026-04-01',
		'date_to'    => '2026-04-30 23:59:59',
		'image_types' => array( 'jpeg' ),
		'max_items'  => 10,
	)
);
npcink_abilities_toolkit_assert_same( true, $media_derivative_batch_plan['success'] ?? null, 'media derivative batch plan returns a success envelope' );
npcink_abilities_toolkit_assert_same( true, $media_derivative_batch_plan['data']['readonly'] ?? null, 'media derivative batch plan is read-only' );
npcink_abilities_toolkit_assert_same( 'dry_run', $media_derivative_batch_plan['data']['plan_mode'] ?? '', 'media derivative batch plan returns a dry-run plan mode' );
npcink_abilities_toolkit_assert_same( false, $media_derivative_batch_plan['data']['commit_execution'] ?? null, 'media derivative batch plan does not execute commits' );
npcink_abilities_toolkit_assert_same( true, $media_derivative_batch_plan['data']['requires_approval'] ?? null, 'media derivative batch plan requires approval before adoption' );
npcink_abilities_toolkit_assert_same( 'toolbox_media_optimization_manifest.v1', $media_derivative_batch_plan['data']['plan_contract_version'] ?? '', 'media derivative batch plan emits the exact one-click optimization manifest contract' );
npcink_abilities_toolkit_assert_same( 1, $media_derivative_batch_plan['data']['summary']['candidate_count'] ?? 0, 'media derivative batch plan selects one April JPEG candidate from the requested types' );
npcink_abilities_toolkit_assert_same( 1, $media_derivative_batch_plan['data']['eligibility_summary']['eligible_count'] ?? 0, 'media derivative batch plan reports eligible count in eligibility summary' );
npcink_abilities_toolkit_assert_same( 1, $media_derivative_batch_plan['data']['eligibility_summary']['blocked_count'] ?? 0, 'media derivative batch plan reports the filtered PNG in the eligibility summary' );
npcink_abilities_toolkit_assert_same( true, $media_derivative_batch_plan['data']['retryable'] ?? null, 'media derivative batch plan is retryable as a rebuildable review set' );
npcink_abilities_toolkit_assert_true( is_string( $media_derivative_batch_plan['data']['operator_next_action'] ?? null ) && '' !== $media_derivative_batch_plan['data']['operator_next_action'], 'media derivative batch plan provides operator next action guidance' );
npcink_abilities_toolkit_assert_same( 84, $media_derivative_batch_plan['data']['candidates'][0]['attachment_id'] ?? 0, 'media derivative batch plan candidate comes from the April date range' );
npcink_abilities_toolkit_assert_same( 'eligible', $media_derivative_batch_plan['data']['candidates'][0]['status'] ?? '', 'media derivative batch plan candidate carries eligible status' );
npcink_abilities_toolkit_assert_same( 'attachment:84', $media_derivative_batch_plan['data']['candidates'][0]['result_ref'] ?? '', 'media derivative batch plan candidate carries a stable result reference' );
npcink_abilities_toolkit_assert_same( 'webp', $media_derivative_batch_plan['data']['candidates'][0]['cloud_request_input']['preferred_format'] ?? '', 'media derivative batch plan fixes one-click output to WebP' );
npcink_abilities_toolkit_assert_same( 'auto_safe', $media_derivative_batch_plan['data']['candidates'][0]['cloud_request_input']['optimization_mode'] ?? '', 'media derivative batch plan uses the Cloud auto-safe mode' );
npcink_abilities_toolkit_assert_same( 'auto_safe.v1', $media_derivative_batch_plan['data']['candidates'][0]['cloud_request_input']['optimization_profile'] ?? '', 'media derivative batch plan pins the auto-safe policy version' );
npcink_abilities_toolkit_assert_same( 'sha256:' . hash( 'sha256', 'april-jpeg-bytes' ), $media_derivative_batch_plan['data']['candidates'][0]['media_fingerprint'] ?? '', 'media derivative batch plan freezes the current file SHA-256' );
npcink_abilities_toolkit_assert_array_omits_keys( $media_derivative_batch_plan['data']['candidates'][0]['cloud_request_input'] ?? array(), array( 'quality', 'crop', 'watermark' ), 'media derivative batch auto-safe request input' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/build-media-derivative-cloud-request', $media_derivative_batch_plan['data']['candidates'][0]['cloud_request_ability'] ?? '', 'media derivative batch plan points to the existing single-image cloud request ability' );
$media_derivative_execution_plan = implode( ' ', (array) ( $media_derivative_batch_plan['data']['execution_plan']['steps'] ?? array() ) );
npcink_abilities_toolkit_assert_true( false !== strpos( $media_derivative_execution_plan, 'source SHA-256 fingerprints' ) && false !== strpos( $media_derivative_execution_plan, 'browser foreground' ), 'media derivative batch plan freezes file versions and remains foreground-driven' );
npcink_abilities_toolkit_assert_true( false === strpos( $media_derivative_execution_plan, 'media-derivative-runs' ) && false === strpos( $media_derivative_execution_plan, 'media-derivative-proposal-payload' ) && false === strpos( $media_derivative_execution_plan, 'Adapter POST' ), 'media derivative batch plan omits retired Adapter execution seams' );
npcink_abilities_toolkit_assert_same( 'source_format_filtered', $media_derivative_batch_plan['data']['skipped'][0]['reason'] ?? '', 'media derivative batch plan explains image type filtering' );
npcink_abilities_toolkit_assert_same( 'source_format_filtered', $media_derivative_batch_plan['data']['blocked_items'][0]['blocked_reason'] ?? '', 'media derivative batch plan exposes filtered media as blocked items' );
npcink_abilities_toolkit_assert_same( 10, $media_derivative_batch_plan['data']['execution_plan']['batch_size_recommendation'] ?? 0, 'media derivative batch plan recommends ten-item execution chunks' );
npcink_abilities_toolkit_assert_array_omits_keys( $media_derivative_batch_plan['data'], array( 'write_actions', 'wordpress_write_decision', 'approval_decision', 'commit' ), 'media derivative batch plan output' );
$media_derivative_batch_plan_bounded = $core_read_package->build_media_derivative_batch_plan(
	array(
		'attachment_ids' => array( 84, 86 ),
		'max_items'      => 1,
	)
);
npcink_abilities_toolkit_assert_same( 1, $media_derivative_batch_plan_bounded['data']['summary']['candidate_count'] ?? 0, 'media derivative batch plan enforces max_items' );
$media_derivative_batch_plan_resize = $core_read_package->build_media_derivative_batch_plan(
	array(
		'attachment_ids' => array( 84 ),
		'resize_mode'    => 'fit',
	)
);
npcink_abilities_toolkit_assert_same( 'fit', $media_derivative_batch_plan_resize['data']['filters']['resize_mode'] ?? '', 'media derivative batch plan records the optional 1920-pixel resize choice' );
npcink_abilities_toolkit_assert_same( 'fit', $media_derivative_batch_plan_resize['data']['candidates'][0]['cloud_request_input']['resize_mode'] ?? '', 'media derivative batch plan carries the optional resize choice to Cloud' );
$media_optimization_preview = $core_write_package->optimize_media_asset(
	array(
		'attachment_id'     => 79,
		'target_max_width'  => 1920,
		'preferred_format'  => 'webp',
		'quality'           => 82,
		'derivative_suffix' => 'optimized',
	)
);
npcink_abilities_toolkit_assert_same( true, $media_optimization_preview['dry_run'] ?? null, 'optimize-media-asset defaults to dry-run preview' );
npcink_abilities_toolkit_assert_same( false, $media_optimization_preview['optimized'] ?? null, 'optimize-media-asset dry-run does not generate a file' );
npcink_abilities_toolkit_assert_same( true, $media_optimization_preview['original_preserved'] ?? null, 'optimize-media-asset preserves original asset' );
npcink_abilities_toolkit_assert_same( false, $media_optimization_preview['replace_original'] ?? null, 'optimize-media-asset never replaces the original file' );
npcink_abilities_toolkit_assert_same( 'webp', $media_optimization_preview['derivative']['format'] ?? '', 'optimize-media-asset plans WebP derivative by default' );
npcink_abilities_toolkit_assert_same( 1920, $media_optimization_preview['derivative']['width'] ?? 0, 'optimize-media-asset plans bounded derivative width' );
npcink_abilities_toolkit_assert_true( ! isset( $media_optimization_preview['media_fingerprint'] ) && ( ! isset( $media_optimization_preview['history'] ) || ! is_array( $media_optimization_preview['history'] ) || ! isset( $media_optimization_preview['history']['new_media_fingerprint'] ) ), 'media optimization preview writes no current fingerprint or replacement lineage before an explicit commit' );
update_post_meta(
	79,
	'_npcink_ai_media_optimized_derivatives',
	array(
		array(
			'format'           => 'webp',
			'mime_type'        => 'image/webp',
			'file_basename'    => 'workflow-diagram-image-optimized.webp',
			'relative_file'    => '2026/06/workflow-diagram-image-optimized.webp',
			'url'              => 'https://example.test/wp-content/uploads/2026/06/workflow-diagram-image-optimized.webp',
			'width'            => 1920,
			'height'           => 1034,
			'quality'          => 82,
			'filesize_bytes'   => 300000,
			'generated_at_gmt' => '2026-06-02T00:00:00+00:00',
		),
	)
);
$media_replace_preview = $core_write_package->replace_media_file(
	array(
		'attachment_id'                 => 79,
		'derivative_relative_file'      => '2026/06/workflow-diagram-image-optimized.webp',
		'expected_current_relative_file' => '2026/06/workflow-diagram-image.jpg',
		'expected_current_mime_type'    => 'image/jpeg',
		'expected_derivative_mime_type' => 'image/webp',
	)
);
npcink_abilities_toolkit_assert_same( true, $media_replace_preview['dry_run'] ?? null, 'replace-media-file defaults to dry-run preview' );
npcink_abilities_toolkit_assert_same( false, $media_replace_preview['replaced'] ?? null, 'replace-media-file dry-run does not switch files' );
npcink_abilities_toolkit_assert_same( true, $media_replace_preview['original_preserved'] ?? null, 'replace-media-file keeps original backup intent in dry-run' );
npcink_abilities_toolkit_assert_same( '2026/06/workflow-diagram-image-optimized.webp', $media_replace_preview['after']['relative_file'] ?? '', 'replace-media-file uses recorded optimized derivative as target' );
npcink_abilities_toolkit_assert_true( 0 === strpos( (string) ( $media_replace_preview['backup']['relative_file'] ?? '' ), 'npcink-abilities-toolkit-backups/2026/06/' ), 'replace-media-file plans backups in the dedicated Npcink uploads backup directory' );
npcink_abilities_toolkit_assert_true( false !== strpos( (string) ( $media_replace_preview['backup']['relative_file'] ?? '' ), 'npcink-abilities-toolkit-backup' ), 'replace-media-file plans a Npcink backup file' );
$media_replace_derivative_path = $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/2026/06/workflow-diagram-image-optimized.webp';
file_put_contents( $media_replace_derivative_path, 'optimized-webp-bytes' );
$media_replace_plan_method = new ReflectionMethod( Core_Write_Package::class, 'build_media_file_replacement_plan' );
$media_replace_execute_method = new ReflectionMethod( Core_Write_Package::class, 'execute_media_file_replacement' );
if ( PHP_VERSION_ID < 80100 ) {
	$media_replace_plan_method->setAccessible( true );
	$media_replace_execute_method->setAccessible( true );
}
$media_replace_drift_plan = $media_replace_plan_method->invoke( $core_write_package, 79, array( 'derivative_relative_file' => '2026/06/workflow-diagram-image-optimized.webp' ) );
file_put_contents( $workflow_media_path, 'third-party-replacement-after-preview' );
$media_replace_drift_result = $media_replace_execute_method->invoke( $core_write_package, 79, $media_replace_drift_plan );
npcink_abilities_toolkit_assert_true( is_wp_error( $media_replace_drift_result ) && 'npcink_abilities_toolkit_media_replace_precommit_drift' === $media_replace_drift_result->get_error_code(), 'replace-media-file rejects source-byte drift immediately before the file switch' );
file_put_contents( $workflow_media_path, 'original-jpeg-bytes' );
$cloud_artifact_contents = base64_decode( 'UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AAAAAA', true );
npcink_abilities_toolkit_assert_true( is_string( $cloud_artifact_contents ), 'Cloud derivative fixture decodes into real WebP bytes' );
$cloud_artifact_sha256 = hash( 'sha256', $cloud_artifact_contents );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][89] = (object) array(
	'ID'           => 89,
	'post_title'   => 'Cloud Derivative Inline Reference',
	'post_status'  => 'publish',
	'post_type'    => 'post',
	'post_excerpt' => '',
	'post_content' => '<!-- wp:image {"id":79,"sizeSlug":"large"} --><figure class="wp-block-image size-large"><img src="https://example.test/wp-content/uploads/2026/06/workflow-diagram-image-300x162.jpg" srcset="https://example.test/wp-content/uploads/2026/06/workflow-diagram-image-300x162.jpg 300w, https://example.test/wp-content/uploads/2026/06/workflow-diagram-image.jpg 2600w, https://example.test/wp-content/uploads/2026/06/workflow-diagram-image-original.jpg 2600w" alt="Workflow diagram" class="wp-image-79" /></figure><!-- /wp:image -->',
	'post_name'    => 'cloud-derivative-inline-reference',
	'post_author'  => 7,
);
$cloud_adoption_preview = $core_write_package->adopt_cloud_media_derivative(
	array(
		'attachment_id'                 => 79,
		'derivative_artifact'           => npcink_abilities_toolkit_cloud_artifact_fixture( array(
			'artifact_id'    => 'art_0000000000000000000000000000000c',
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
	)
);
npcink_abilities_toolkit_assert_same( true, $cloud_adoption_preview['dry_run'] ?? null, 'adopt-cloud-media-derivative defaults to dry-run preview' );
npcink_abilities_toolkit_assert_same( false, $cloud_adoption_preview['replaced'] ?? null, 'adopt-cloud-media-derivative dry-run does not switch files' );
npcink_abilities_toolkit_assert_same( 'art_0000000000000000000000000000000c', $cloud_adoption_preview['artifact']['artifact_id'] ?? '', 'adopt-cloud-media-derivative preserves artifact evidence' );
npcink_abilities_toolkit_assert_same( '2026/06/cloud-artifact.webp', $cloud_adoption_preview['after']['relative_file'] ?? '', 'adopt-cloud-media-derivative plans the canonical Cloud suggestion for final local review' );
npcink_abilities_toolkit_assert_same( 1, $cloud_adoption_preview['content_reference_repairs']['post_count'] ?? 0, 'adopt-cloud-media-derivative previews post content reference repairs for embedded attachment URLs' );
npcink_abilities_toolkit_assert_true( (int) ( $cloud_adoption_preview['content_reference_repairs']['replacement_count'] ?? 0 ) >= 3, 'adopt-cloud-media-derivative preview includes old main, sized, and original image references' );
$cloud_adoption_expected_post_ids = array_values( array_map( 'absint', array_column( (array) ( $cloud_adoption_preview['content_reference_repairs']['repairs'] ?? array() ), 'post_id' ) ) );
$cloud_adoption_expected_post_count = absint( $cloud_adoption_preview['content_reference_repairs']['post_count'] ?? 0 );
$cloud_adoption_expected_replacement_count = absint( $cloud_adoption_preview['content_reference_repairs']['replacement_count'] ?? 0 );
$cloud_suggested_filename_preview = $core_write_package->adopt_cloud_media_derivative(
	array(
		'attachment_id'                 => 79,
		'derivative_artifact'           => npcink_abilities_toolkit_cloud_artifact_fixture( array(
			'artifact_id'         => 'art_0000000000000000000000000000000d',
			'expires_at'          => gmdate( 'c', time() + 600 ),
			'mime_type'           => 'image/webp',
			'format'              => 'webp',
			'width'               => 1,
			'height'              => 1,
			'filesize_bytes'      => strlen( $cloud_artifact_contents ),
			'sha256'              => $cloud_artifact_sha256,
			'suggested_filename'  => 'cloud-suggested-file.webp',
		) ),
		'expected_current_relative_file' => '2026/06/workflow-diagram-image.jpg',
		'expected_current_mime_type'    => 'image/jpeg',
		'expected_derivative_mime_type' => 'image/webp',
	)
);
npcink_abilities_toolkit_assert_same( 'cloud-suggested-file.webp', $cloud_suggested_filename_preview['proposed_filename'] ?? '', 'adopt-cloud-media-derivative adopts a sanitized Cloud suggested filename as local proposal evidence' );
npcink_abilities_toolkit_assert_same( 'cloud_artifact_suggestion', $cloud_suggested_filename_preview['filename_policy']['source'] ?? '', 'adopt-cloud-media-derivative marks Cloud filenames as suggestions, not write decisions' );
npcink_abilities_toolkit_assert_same( true, $cloud_suggested_filename_preview['filename_policy']['final_sanitize_unique_required'] ?? null, 'adopt-cloud-media-derivative requires final WordPress-side filename finalization' );
npcink_abilities_toolkit_assert_same( 'format_checksum', $cloud_suggested_filename_preview['artifact']['filename_basis']['strategy'] ?? '', 'direct PHP artifact normalization preserves the exact local filename basis' );
$expired_cloud_adoption = $core_write_package->adopt_cloud_media_derivative(
	array(
		'attachment_id'       => 79,
		'derivative_artifact' => npcink_abilities_toolkit_cloud_artifact_fixture( array(
			'artifact_id' => 'art_0000000000000000000000000000000e',
			'expires_at'  => gmdate( 'c', time() - 60 ),
			'mime_type'   => 'image/webp',
			'format'      => 'webp',
		) ),
	)
);
npcink_abilities_toolkit_assert_true( is_wp_error( $expired_cloud_adoption ) && 'npcink_abilities_toolkit_cloud_artifact_expired' === $expired_cloud_adoption->get_error_code(), 'adopt-cloud-media-derivative rejects expired artifacts' );
$GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] = sys_get_temp_dir() . '/npcink-abilities-toolkit-cloud-adoption-' . getmypid();
$current_media_path = $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/2026/06/workflow-diagram-image.jpg';
mkdir( dirname( $current_media_path ), 0755, true );
file_put_contents( $current_media_path, 'original-jpeg-bytes' );
$cloud_artifact_receive_count = 0;
$GLOBALS['npcink_abilities_toolkit_unit_cloud_artifact_receive_callback'] = static function ( array $artifact ) use ( $cloud_artifact_contents, &$cloud_artifact_receive_count ) {
	++$cloud_artifact_receive_count;
	return npcink_abilities_toolkit_cloud_receive_fixture( $artifact, $cloud_artifact_contents );
};
$GLOBALS['npcink_ai_runtime_wp_ability_context']['context'] = array(
	'approval_commit_authorized' => true,
	'approval_id'                => 'approval-cloud-media-adoption',
);
$valid_cloud_artifact_receive_callback = $GLOBALS['npcink_abilities_toolkit_unit_cloud_artifact_receive_callback'];
$GLOBALS['npcink_abilities_toolkit_unit_cloud_artifact_receive_callback'] = static function ( array $artifact ) use ( $cloud_artifact_contents, &$cloud_artifact_receive_count ) {
	++$cloud_artifact_receive_count;
	return npcink_abilities_toolkit_cloud_receive_fixture( $artifact, $cloud_artifact_contents, array( 'sha256' => 'invalid-transport-digest' ) );
};
$cloud_adoption_invalid_transport_digest = $core_write_package->adopt_cloud_media_derivative(
	array(
		'attachment_id'                 => 79,
		'derivative_artifact'           => npcink_abilities_toolkit_cloud_artifact_fixture( array(
			'artifact_id'    => 'art_0000000000000000000000000000000f',
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
		'expected_content_reference_post_ids' => $cloud_adoption_expected_post_ids,
		'expected_content_reference_post_count' => $cloud_adoption_expected_post_count,
		'expected_content_reference_replacement_count' => $cloud_adoption_expected_replacement_count,
		'commit'                       => true,
	)
);
npcink_abilities_toolkit_assert_true( is_wp_error( $cloud_adoption_invalid_transport_digest ) && 'npcink_abilities_toolkit_cloud_artifact_received_facts_invalid' === $cloud_adoption_invalid_transport_digest->get_error_code(), 'adopt-cloud-media-derivative rejects malformed receive facts' );
$GLOBALS['npcink_abilities_toolkit_unit_cloud_artifact_receive_callback'] = $valid_cloud_artifact_receive_callback;
$cloud_artifact_receive_count = 0;
$cloud_adoption_size_mismatch = $core_write_package->adopt_cloud_media_derivative(
	array(
		'attachment_id'                 => 79,
		'derivative_artifact'           => npcink_abilities_toolkit_cloud_artifact_fixture( array(
			'artifact_id'    => 'art_00000000000000000000000000000010',
			'expires_at'     => gmdate( 'c', time() + 600 ),
			'mime_type'      => 'image/webp',
			'format'         => 'webp',
			'width'          => 1,
			'height'         => 1,
			'filesize_bytes' => strlen( $cloud_artifact_contents ) + 1,
			'sha256'         => $cloud_artifact_sha256,
		) ),
		'expected_current_relative_file' => '2026/06/workflow-diagram-image.jpg',
		'expected_current_mime_type'    => 'image/jpeg',
		'expected_derivative_mime_type' => 'image/webp',
		'expected_content_reference_post_ids' => $cloud_adoption_expected_post_ids,
		'expected_content_reference_post_count' => $cloud_adoption_expected_post_count,
		'expected_content_reference_replacement_count' => $cloud_adoption_expected_replacement_count,
		'commit'                       => true,
	)
);
npcink_abilities_toolkit_assert_true( is_wp_error( $cloud_adoption_size_mismatch ) && 'npcink_abilities_toolkit_cloud_artifact_filesize_mismatch' === $cloud_adoption_size_mismatch->get_error_code(), 'adopt-cloud-media-derivative rejects received bytes whose size differs from approved artifact evidence' );
npcink_abilities_toolkit_assert_same( 1, $cloud_artifact_receive_count, 'artifact size integrity is checked immediately after verified receiving' );
npcink_abilities_toolkit_assert_same( '2026/06/workflow-diagram-image.jpg', get_post_meta( 79, '_wp_attached_file', true ), 'artifact size mismatch leaves the attachment pointer unchanged' );
$cloud_artifact_receive_count = 0;
$cloud_adoption_drift = $core_write_package->adopt_cloud_media_derivative(
	array(
		'attachment_id'                 => 79,
		'derivative_artifact'           => npcink_abilities_toolkit_cloud_artifact_fixture( array(
			'artifact_id'    => 'art_00000000000000000000000000000011',
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
		'expected_content_reference_post_ids' => array( 999 ),
		'expected_content_reference_post_count' => $cloud_adoption_expected_post_count,
		'expected_content_reference_replacement_count' => $cloud_adoption_expected_replacement_count,
		'commit'                       => true,
	)
);
npcink_abilities_toolkit_assert_true( is_wp_error( $cloud_adoption_drift ) && 'npcink_abilities_toolkit_media_reference_repair_expectation_mismatch' === $cloud_adoption_drift->get_error_code(), 'adopt-cloud-media-derivative blocks commit when reviewed content reference targets drift' );
npcink_abilities_toolkit_assert_same( 0, $cloud_artifact_receive_count, 'adopt-cloud-media-derivative checks content reference drift before receiving the Cloud artifact' );
$precommit_original_relative_file = (string) get_post_meta( 79, '_wp_attached_file', true );
$precommit_original_metadata = wp_get_attachment_metadata( 79 );
$precommit_original_mime = (string) get_post_mime_type( 79 );
$precommit_original_post_content = (string) ( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][89]->post_content ?? '' );
$precommit_history_before = $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][79]['_npcink_ai_media_file_replacement_history'] ?? null;
$precommit_derivatives_before = $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][79]['_npcink_ai_media_optimized_derivatives'] ?? null;
$precommit_latest_derivative_before = $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][79]['_npcink_ai_media_latest_optimized_derivative'] ?? null;
$precommit_backup_files_before = glob( $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/npcink-abilities-toolkit-backups/2026/06/*' );
$precommit_backup_files_before = is_array( $precommit_backup_files_before ) ? $precommit_backup_files_before : array();
$concurrent_relative_file = '2026/06/concurrent-external-state.jpg';
$concurrent_path = $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/' . $concurrent_relative_file;
$concurrent_contents = 'concurrent-external-state-bytes';
$concurrent_post_content = '<p>Concurrent editor state must survive the losing Cloud adoption.</p>';
$cloud_artifact_receive_count = 0;
$GLOBALS['npcink_abilities_toolkit_unit_cloud_artifact_receive_callback'] = static function ( array $artifact ) use ( $cloud_artifact_contents, &$cloud_artifact_receive_count, $concurrent_relative_file, $concurrent_path, $concurrent_contents, $concurrent_post_content ) {
	++$cloud_artifact_receive_count;
	file_put_contents( $concurrent_path, $concurrent_contents );
	update_post_meta( 79, '_wp_attached_file', $concurrent_relative_file );
	update_post_meta(
		79,
		'_wp_attachment_metadata',
		array(
			'file'     => $concurrent_relative_file,
			'width'    => 777,
			'height'   => 555,
			'filesize' => strlen( $concurrent_contents ),
			'sizes'    => array(),
		)
	);
	wp_update_post( array( 'ID' => 79, 'post_mime_type' => 'image/png' ), true );
	wp_update_post( array( 'ID' => 89, 'post_content' => $concurrent_post_content ), true );
	return npcink_abilities_toolkit_cloud_receive_fixture( $artifact, $cloud_artifact_contents );
};
$cloud_adoption_precommit_drift = $core_write_package->adopt_cloud_media_derivative(
	array(
		'attachment_id'                 => 79,
		'derivative_artifact'           => npcink_abilities_toolkit_cloud_artifact_fixture(
			array(
				'artifact_id'    => 'art_00000000000000000000000000000017',
				'width'          => 1,
				'height'         => 1,
				'filesize_bytes' => strlen( $cloud_artifact_contents ),
				'sha256'         => $cloud_artifact_sha256,
			)
		),
		'expected_current_relative_file' => '2026/06/workflow-diagram-image.jpg',
		'expected_current_mime_type'    => 'image/jpeg',
		'expected_derivative_mime_type' => 'image/webp',
		'file_name'                    => 'precommit-drift.webp',
		'expected_content_reference_post_ids' => $cloud_adoption_expected_post_ids,
		'expected_content_reference_post_count' => $cloud_adoption_expected_post_count,
		'expected_content_reference_replacement_count' => $cloud_adoption_expected_replacement_count,
		'commit'                       => true,
	)
);
$precommit_drift_data = is_wp_error( $cloud_adoption_precommit_drift ) ? $cloud_adoption_precommit_drift->get_error_data() : array();
$precommit_drift_fields = is_array( $precommit_drift_data ) ? (array) ( $precommit_drift_data['drift_fields'] ?? array() ) : array();
npcink_abilities_toolkit_assert_true( is_wp_error( $cloud_adoption_precommit_drift ) && 'npcink_abilities_toolkit_cloud_adoption_precommit_drift' === $cloud_adoption_precommit_drift->get_error_code(), 'adopt-cloud-media-derivative fails closed when attachment truth drifts during verified receive' );
npcink_abilities_toolkit_assert_same( 409, $precommit_drift_data['status'] ?? 0, 'receive-time attachment drift returns a conflict instead of overwriting concurrent state' );
npcink_abilities_toolkit_assert_true( in_array( 'attachment_meta', $precommit_drift_fields, true ) && in_array( 'attachment_mime_type', $precommit_drift_fields, true ) && in_array( 'post_contents', $precommit_drift_fields, true ), 'precommit drift evidence identifies attachment meta, MIME, and post-content changes' );
npcink_abilities_toolkit_assert_same( 1, $cloud_artifact_receive_count, 'precommit drift is detected after verified Cloud receive' );
npcink_abilities_toolkit_assert_same( $concurrent_relative_file, get_post_meta( 79, '_wp_attached_file', true ), 'precommit drift cleanup preserves the concurrent attachment pointer' );
npcink_abilities_toolkit_assert_same( 'image/png', get_post_mime_type( 79 ), 'precommit drift cleanup preserves the concurrent attachment MIME type' );
npcink_abilities_toolkit_assert_same( $concurrent_post_content, (string) ( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][89]->post_content ?? '' ), 'precommit drift cleanup preserves concurrent post content' );
npcink_abilities_toolkit_assert_same( $concurrent_contents, is_readable( $concurrent_path ) ? file_get_contents( $concurrent_path ) : '', 'precommit drift cleanup preserves the concurrent attachment bytes' );
npcink_abilities_toolkit_assert_true( ! is_file( $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/2026/06/precommit-drift.webp' ), 'precommit drift cleanup deletes only the derivative created by the losing batch' );
npcink_abilities_toolkit_assert_same( $precommit_history_before, $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][79]['_npcink_ai_media_file_replacement_history'] ?? null, 'precommit drift creates no replacement history' );
npcink_abilities_toolkit_assert_same( $precommit_derivatives_before, $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][79]['_npcink_ai_media_optimized_derivatives'] ?? null, 'precommit drift creates no optimized derivative metadata' );
npcink_abilities_toolkit_assert_same( $precommit_latest_derivative_before, $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][79]['_npcink_ai_media_latest_optimized_derivative'] ?? null, 'precommit drift creates no latest derivative projection' );
$precommit_backup_files_after = glob( $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/npcink-abilities-toolkit-backups/2026/06/*' );
$precommit_backup_files_after = is_array( $precommit_backup_files_after ) ? $precommit_backup_files_after : array();
npcink_abilities_toolkit_assert_same( $precommit_backup_files_before, $precommit_backup_files_after, 'precommit drift creates no local backup' );
update_post_meta( 79, '_wp_attached_file', $precommit_original_relative_file );
update_post_meta( 79, '_wp_attachment_metadata', $precommit_original_metadata );
wp_update_post( array( 'ID' => 79, 'post_mime_type' => $precommit_original_mime ), true );
wp_update_post( array( 'ID' => 89, 'post_content' => $precommit_original_post_content ), true );
wp_delete_file( $concurrent_path );
$GLOBALS['npcink_abilities_toolkit_unit_cloud_artifact_receive_callback'] = $valid_cloud_artifact_receive_callback;

