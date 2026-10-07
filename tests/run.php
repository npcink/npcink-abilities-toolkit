<?php
/**
 * Lightweight regression tests.
 *
 * @package NpcinkAbilitiesToolkit
 */

require_once __DIR__ . '/bootstrap.php';

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

$assertions = 0;
$core_read_package_source = (string) file_get_contents( dirname( __DIR__ ) . '/includes/Packages/Core_Read_Package.php' );
$media_read_trait_source = (string) file_get_contents( dirname( __DIR__ ) . '/includes/Packages/Read_Traits/Media_Read_Methods.php' );
$media_alt_caption_read_trait_source = (string) file_get_contents( dirname( __DIR__ ) . '/includes/Packages/Read_Traits/Media_Alt_Caption_Read_Methods.php' );
$core_write_package_source = (string) file_get_contents( dirname( __DIR__ ) . '/includes/Packages/Core_Write_Package.php' );
$ability_namespace_migration_map = (string) file_get_contents( dirname( __DIR__ ) . '/docs/ability-namespace-migration-map.md' );
$ability_namespace_migration_script = (string) file_get_contents( dirname( __DIR__ ) . '/scripts/audit-legacy-ability-ids.php' );
$pr_publisher = (string) file_get_contents( dirname( __DIR__ ) . '/scripts/publish-pr.sh' );
$pr_template = (string) file_get_contents( dirname( __DIR__ ) . '/.github/pull_request_template.md' );
$composer_source = (string) file_get_contents( dirname( __DIR__ ) . '/composer.json' );

function npcink_abilities_toolkit_assert_true( $condition, $message ) {
	global $assertions;
	++$assertions;
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

function npcink_abilities_toolkit_assert_same( $expected, $actual, $message ) {
	npcink_abilities_toolkit_assert_true( $expected === $actual, $message . ' Expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) );
}

npcink_abilities_toolkit_assert_same( false, current_user_can( 'manage_option' ), 'unit capability defaults fail closed for unknown or misspelled capabilities' );
$unknown_capability_callback = Permission_Callbacks::for_capability( 'manage_option' );
npcink_abilities_toolkit_assert_same( false, $unknown_capability_callback(), 'permission callbacks preserve fail-closed behavior for unknown capabilities' );

npcink_abilities_toolkit_assert_true(
	false !== strpos( $composer_source, '"pr:publish": "bash scripts/publish-pr.sh"' )
	&& false !== strpos( $pr_template, '## Scope' )
	&& false !== strpos( $pr_template, '## Boundary' )
	&& false !== strpos( $pr_template, '## Verification' )
	&& false !== strpos( $pr_template, '## Risk' )
	&& false !== strpos( $pr_publisher, 'git status --porcelain' )
	&& false !== strpos( $pr_publisher, 'git merge-base --is-ancestor "origin/${base_branch}" HEAD' )
	&& false !== strpos( $pr_publisher, '--body-file "${body_path}"' )
	&& false !== strpos( $pr_publisher, '--auto --squash --match-head-commit "${head_sha}"' )
	&& false === strpos( $pr_publisher, '--delete-branch' ),
	'PR publisher validates the checked-in body contract and preserves protected multi-worktree merging.'
);

function npcink_abilities_toolkit_assert_output_schema_declares_payload_keys( Gutenberg_Block_Document $document, array $schema, array $payload, $message ) {
	npcink_abilities_toolkit_assert_same( false, $schema['additionalProperties'] ?? null, "{$message} keeps a strict output schema" );
	npcink_abilities_toolkit_assert_same( array(), $document->output_schema_missing_payload_keys( $schema, $payload ), "{$message} output schema declares returned payload keys" );
}

function npcink_abilities_toolkit_count_plan_actions_for_ability( array $actions, $ability_id ) {
	$count = 0;
	foreach ( $actions as $action ) {
		if ( $ability_id === (string) ( $action['target_ability_id'] ?? '' ) ) {
			++$count;
		}
	}

	return $count;
}

function npcink_abilities_toolkit_cloud_artifact_fixture( array $overrides = array() ) {
	$artifact = array_merge(
		array(
			'artifact_id'        => 'art_ffffffffffffffffffffffffffffffff',
			'expires_at'         => gmdate( 'c', time() + 600 ),
			'mime_type'          => 'image/webp',
			'format'             => 'webp',
			'width'              => 1600,
			'height'             => 862,
			'filesize_bytes'     => 210000,
			'sha256'             => hash( 'sha256', 'cloud-artifact-fixture' ),
			'suggested_filename' => 'cloud-artifact.webp',
			'filename_basis'     => array(
				'owner'                          => 'wordpress_write_ability_final',
				'strategy'                       => 'format_checksum',
				'final_sanitize_unique_required' => true,
			),
			'processing_warnings' => array(),
		),
		$overrides
	);
	if ( ! array_key_exists( 'transform_facts', $overrides ) ) {
		$source_filesize = 260000;
		$output_filesize = (int) $artifact['filesize_bytes'];
		$artifact['transform_facts'] = array(
			'source_checksum' => 'sha256:' . hash( 'sha256', 'source-fixture' ),
			'output_checksum' => 'sha256:' . (string) $artifact['sha256'],
			'source_format' => 'jpeg',
			'output_format' => (string) $artifact['format'],
			'source_mime_type' => 'image/jpeg',
			'output_mime_type' => (string) $artifact['mime_type'],
			'source_width' => 1600,
			'source_height' => 862,
			'output_width' => (int) $artifact['width'],
			'output_height' => (int) $artifact['height'],
			'source_filesize_bytes' => $source_filesize,
			'output_filesize_bytes' => $output_filesize,
			'source_frame_count' => 1,
			'output_frame_count' => 1,
			'source_has_alpha' => false,
			'output_has_alpha' => false,
			'alpha_preserved' => true,
			'decodable' => true,
			'crop_applied' => false,
			'watermark_applied' => false,
			'resize_applied' => 1600 !== (int) $artifact['width'] || 862 !== (int) $artifact['height'],
			'encoding_mode' => 'png' === $artifact['format'] ? 'lossless' : 'lossy',
			'savings_basis_points' => max( 0, (int) ( ( $source_filesize - $output_filesize ) * 10000 / $source_filesize ) ),
			'optimization_profile' => 'auto_safe.v1',
			'source_class' => 'opaque',
			'effective_quality' => 82,
			'quality_metric' => 'ssim',
			'quality_score' => 0.99,
			'quality_threshold' => 0.985,
			'color_profile_normalized' => false,
			'qualified' => true,
			'decision_reasons' => array( 'qualified' ),
		);
	}

	return $artifact;
}

function npcink_abilities_toolkit_cloud_receive_fixture( array $artifact, $contents, array $overrides = array() ) {
	$contents = (string) $contents;
	$artifact_expiry = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::expiry_timestamp( (string) ( $artifact['expires_at'] ?? '' ) );
	$received_expiry = gmdate( 'c', $artifact_expiry );
	$ack_deadline = gmdate( 'c', min( time() + 900, $artifact_expiry - 1 ) );
	$sha256 = hash( 'sha256', $contents );
	$delivery_id = 'mdl_eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee';
	$checksum = 'sha256:' . $sha256;

	return array_replace_recursive(
		array(
			'artifact_id'    => (string) ( $artifact['artifact_id'] ?? '' ),
			'contents'       => $contents,
			'mime_type'      => (string) ( $artifact['mime_type'] ?? '' ),
			'width'          => (int) ( $artifact['width'] ?? 0 ),
			'height'         => (int) ( $artifact['height'] ?? 0 ),
			'filesize_bytes' => (int) ( $artifact['filesize_bytes'] ?? 0 ),
			'sha256'         => (string) ( $artifact['sha256'] ?? '' ),
			'expires_at'     => $received_expiry,
			'transfer_evidence' => array(
				'contract_version'      => 'media_artifact_verified_transfer.v1',
				'artifact_id'           => (string) ( $artifact['artifact_id'] ?? '' ),
				'delivery_id'           => $delivery_id,
				'received_byte_size'    => strlen( $contents ),
				'received_checksum'     => $checksum,
				'byte_size_verified'    => true,
				'checksum_verified'     => true,
				'content_type_verified' => true,
				'image_decoded'         => true,
				'dimensions_verified'   => true,
				'ack_deadline_at'       => $ack_deadline,
			),
			'delivery_ack' => array(
				'contract_version'      => 'media_artifact_delivery_ack.v1',
				'delivery_id'           => $delivery_id,
				'artifact_id'           => (string) ( $artifact['artifact_id'] ?? '' ),
				'status'                => 'acknowledged',
				'received_byte_size'    => strlen( $contents ),
				'received_checksum'     => $checksum,
				'byte_size_verified'    => true,
				'checksum_verified'     => true,
				'acknowledged_at'       => gmdate( 'c', time() - 360 ),
				'artifact_expires_at'   => $received_expiry,
				'idempotent_replay'     => false,
				'acknowledgement_scope' => 'verified_transfer_only',
			),
		),
		$overrides
	);
}

function npcink_abilities_toolkit_find_row_by_key( array $rows, $key, $value ) {
	foreach ( $rows as $row ) {
		if ( is_array( $row ) && (string) ( $row[ $key ] ?? '' ) === (string) $value ) {
			return $row;
		}
	}

	return array();
}

function npcink_abilities_toolkit_assert_array_omits_keys( $value, array $forbidden_keys, $path ) {
	if ( ! is_array( $value ) ) {
		return;
	}

	foreach ( $value as $key => $child ) {
		if ( is_string( $key ) ) {
			npcink_abilities_toolkit_assert_true( ! in_array( $key, $forbidden_keys, true ), "{$path} omits forbidden field {$key}" );
		}

		$child_path = is_string( $key ) ? "{$path}.{$key}" : "{$path}[]";
		npcink_abilities_toolkit_assert_array_omits_keys( $child, $forbidden_keys, $child_path );
	}
}

npcink_abilities_toolkit_assert_true( false !== strpos( $ability_namespace_migration_map, 'https://magick-ai.local/' ), 'Ability namespace migration map names magick-ai.local as the primary local acceptance site.' );
npcink_abilities_toolkit_assert_true( false !== strpos( $ability_namespace_migration_map, 'temporary isolation fixtures' ), 'Ability namespace migration map keeps npcink.local as a temporary isolation fixture.' );
npcink_abilities_toolkit_assert_true( false !== strpos( $ability_namespace_migration_map, 'is invalid for new runtime calls' ) && false !== strpos( $ability_namespace_migration_map, 'npcink-abilities-toolkit/create-draft' ), 'Ability namespace migration map declares old create-draft invalid for new calls.' );
npcink_abilities_toolkit_assert_true( false !== strpos( $ability_namespace_migration_map, 'Cloud runtime/detail' ), 'Ability namespace migration map keeps Cloud out of ability truth ownership.' );
npcink_abilities_toolkit_assert_true( false !== strpos( $ability_namespace_migration_script, 'compatibility_aliases' ), 'Ability namespace audit script reports whether compatibility aliases are enabled.' );

function npcink_abilities_toolkit_observability_events_of_kind( array $events, $event_kind ) {
	return array_values(
		array_filter(
			$events,
			static function ( $event ) use ( $event_kind ) {
				return is_array( $event ) && (string) ( $event['event_kind'] ?? '' ) === (string) $event_kind;
			}
		)
	);
}

function npcink_abilities_toolkit_assert_event_has_safe_event_id( array $event, $prefix, $message ) {
	$event_id = (string) ( $event['event_id'] ?? '' );
	npcink_abilities_toolkit_assert_true( 0 === strpos( $event_id, $prefix ), "{$message} event_id uses {$prefix} prefix" );
	npcink_abilities_toolkit_assert_true( 1 === preg_match( '/^[a-z0-9_]+$/', $event_id ), "{$message} event_id uses bounded id characters" );
}

function npcink_abilities_toolkit_assert_observability_event_is_metadata_only( array $event, $message ) {
	npcink_abilities_toolkit_assert_array_omits_keys(
		$event,
		array(
			'args',
			'auth',
			'authorization',
			'callback',
			'callback_input',
			'content',
			'cookie',
			'definition',
			'execute_callback',
			'input',
			'nonce',
			'payload',
			'payload_json',
			'permission_callback',
			'prompt',
			'raw',
			'raw_callback_input',
			'request',
			'response',
			'secret',
			'token',
		),
		$message
	);
}

$admin_test_page = file_get_contents( __DIR__ . '/../includes/Admin/Test_Page.php' );
$autoloader_source = file_get_contents( __DIR__ . '/../includes/Autoloader.php' );
$core_write_source = file_get_contents( __DIR__ . '/../includes/Packages/Core_Write_Package.php' );
$cloud_media_write_trait = file_get_contents( __DIR__ . '/../includes/Packages/Write_Traits/Cloud_Media_Write_Methods.php' );
$media_backup_write_trait = file_get_contents( __DIR__ . '/../includes/Packages/Write_Traits/Media_Backup_Write_Methods.php' );
$media_reference_discovery_write_trait = file_get_contents( __DIR__ . '/../includes/Packages/Write_Traits/Media_Reference_Discovery_Write_Methods.php' );
$post_attribute_write_trait = file_get_contents( __DIR__ . '/../includes/Packages/Write_Traits/Post_Attribute_Write_Methods.php' );
$site_editor_write_trait = file_get_contents( __DIR__ . '/../includes/Packages/Write_Traits/Site_Editor_Write_Methods.php' );
$structural_split_plan = file_get_contents( __DIR__ . '/../docs/structural-split-plan.md' );
npcink_abilities_toolkit_assert_true( is_string( $autoloader_source ) && false !== strpos( $autoloader_source, '_Write_Methods' ) && false !== strpos( $autoloader_source, 'Packages/Write_Traits/' ), 'internal autoloader maps write method traits lazily' );
npcink_abilities_toolkit_assert_true( is_string( $core_write_source ) && false !== strpos( $core_write_source, 'use Post_Attribute_Write_Methods;' ), 'Core write package composes the post attribute write trait' );
npcink_abilities_toolkit_assert_true( is_string( $core_write_source ) && false === strpos( $core_write_source, 'public function set_post_slug(' ), 'Core write package does not duplicate moved post attribute methods' );
foreach ( array( 'set_post_slug', 'set_post_author', 'set_post_template', 'set_post_format' ) as $moved_post_attribute_method ) {
	npcink_abilities_toolkit_assert_true( is_string( $post_attribute_write_trait ) && false !== strpos( $post_attribute_write_trait, 'function ' . $moved_post_attribute_method . '(' ), 'post attribute trait owns moved method: ' . $moved_post_attribute_method );
}
npcink_abilities_toolkit_assert_true( is_string( $core_write_source ) && false !== strpos( $core_write_source, 'use Site_Editor_Write_Methods;' ), 'Core write package composes the Site Editor write trait' );
foreach ( array( 'update_post_blocks', 'update_template_blocks', 'upsert_template_blocks', 'update_template_part_blocks', 'update_site_editor_entity_blocks', 'normalize_template_slug', 'active_theme_stylesheet', 'find_template_override_post', 'normalize_blocks_input', 'count_blocks_recursive', 'serialize_blocks_native' ) as $moved_site_editor_method ) {
	npcink_abilities_toolkit_assert_true( is_string( $core_write_source ) && false === strpos( $core_write_source, 'function ' . $moved_site_editor_method . '(' ), 'Core write package does not duplicate moved Site Editor method: ' . $moved_site_editor_method );
	npcink_abilities_toolkit_assert_true( is_string( $site_editor_write_trait ) && false !== strpos( $site_editor_write_trait, 'function ' . $moved_site_editor_method . '(' ), 'Site Editor trait owns moved method: ' . $moved_site_editor_method );
}
foreach ( array( 'npcink-abilities-toolkit/update-post-blocks', 'npcink-abilities-toolkit/update-template-blocks', 'npcink-abilities-toolkit/upsert-template-blocks', 'npcink-abilities-toolkit/update-template-part-blocks' ) as $site_editor_ability_id ) {
	npcink_abilities_toolkit_assert_true( is_string( $core_write_source ) && false !== strpos( $core_write_source, "'" . $site_editor_ability_id . "' => array(" ), 'Core write package remains the definition owner for Site Editor ability: ' . $site_editor_ability_id );
}
npcink_abilities_toolkit_assert_true( is_string( $core_write_source ) && false !== strpos( $core_write_source, 'use Cloud_Media_Write_Methods;' ), 'Core write package composes the cloud media write trait' );
foreach ( array( 'adopt_cloud_media_derivative', 'build_cloud_media_derivative_adoption_plan', 'normalize_cloud_media_derivative_artifact', 'cloud_artifact_derivative_state', 'materialize_cloud_media_derivative_artifact', 'capture_cloud_media_adoption_state', 'validate_cloud_media_adoption_precommit_state', 'cloud_media_current_state_snapshot', 'cloud_media_current_file_snapshot', 'cloud_media_file_bytes_match', 'cloud_media_post_meta_snapshot', 'restore_cloud_media_post_meta_snapshot', 'cloud_media_metadata_file_paths', 'cleanup_failed_cloud_media_adoption', 'cloud_media_adoption_precommit_failure_with_discard', 'cloud_media_adoption_failure_with_cleanup', 'verify_cloud_media_adoption_commit', 'cloud_media_directory_file_snapshot', 'track_cloud_media_generated_files', 'write_cloud_media_file_exclusive', 'copy_cloud_media_file_exclusive', 'cloud_media_created_file_record', 'add_cloud_media_created_file_to_manifest', 'cloud_media_cas_update_post_meta', 'cloud_media_atomic_compare_and_swap_post_meta', 'cloud_media_locked_post_meta_rows_match', 'cloud_media_rollback_locked_post_meta_mutation', 'cloud_media_clean_post_meta_cache', 'cloud_media_cas_update_attachment_mime', 'cloud_media_cas_update_post_content', 'cloud_media_atomic_compare_and_swap_post_field', 'cloud_media_rollback_locked_post_mutation', 'cloud_media_mutation_conflict', 'cloud_media_transaction_rollback_unconfirmed', 'cloud_media_unknown_transaction_cleanup_conflict', 'discard_cloud_media_created_file', 'cleanup_cloud_media_batch_manifest', 'cloud_media_created_file_failure_with_discard' ) as $moved_cloud_media_method ) {
	npcink_abilities_toolkit_assert_true( is_string( $core_write_source ) && false === strpos( $core_write_source, 'function ' . $moved_cloud_media_method . '(' ), 'Core write package does not duplicate moved cloud media method: ' . $moved_cloud_media_method );
	npcink_abilities_toolkit_assert_true( is_string( $cloud_media_write_trait ) && false !== strpos( $cloud_media_write_trait, 'function ' . $moved_cloud_media_method . '(' ), 'cloud media trait owns moved method: ' . $moved_cloud_media_method );
}
npcink_abilities_toolkit_assert_true( is_string( $core_write_source ) && false !== strpos( $core_write_source, "'npcink-abilities-toolkit/adopt-cloud-media-derivative' => array(" ), 'Core write package remains the definition owner for the cloud media adoption ability' );
npcink_abilities_toolkit_assert_true( is_string( $core_write_source ) && false !== strpos( $core_write_source, 'use Media_Backup_Write_Methods;' ), 'Core write package composes the media backup write trait' );
$media_governance_sources = $core_write_source . "\n" . $media_backup_write_trait . "\n" . $cloud_media_write_trait;
npcink_abilities_toolkit_assert_true( false !== strpos( $media_governance_sources, "hash_file( 'sha256'" ) && false !== strpos( $media_governance_sources, "'metadata_fingerprint'" ) && false !== strpos( $media_governance_sources, "'new_media_fingerprint'" ) && false !== strpos( $media_governance_sources, "'derived_from_media_fingerprint'" ), 'media write paths use real SHA-256 content fingerprints, keep metadata fingerprints separate, and record replacement lineage' );
npcink_abilities_toolkit_assert_true( false !== strpos( $media_governance_sources, "'transform_type'" ) && false !== strpos( $media_governance_sources, "'visual_reuse_policy'" ) && false !== strpos( $media_governance_sources, "'transform_facts'" ) && false !== strpos( $media_governance_sources, 'media_visual_reuse_policy_from_facts' ), 'media replacement history records transform facts and the bounded visual-evidence reuse policy' );
npcink_abilities_toolkit_assert_true( false !== strpos( $media_governance_sources, 'media_file_operation_is_verified' ) && false !== strpos( $media_governance_sources, "do_action( 'npcink_abilities_toolkit_media_file_version_changed'" ), 'media replacement and restore publish a version-change event only through final verification' );
foreach ( array( 'cleanup_expired_media_backups', 'media_backup_cleanup_attachment_ids_after', 'preview_expired_media_backups', 'cleanup_media_backups', 'replace_media_file', 'restore_media_backup', 'validate_media_backup_restore_precommit_state', 'restore_media_backup_overwritten_files', 'media_backup_restore_failure_with_cleanup', 'execute_media_backup_restore', 'copy_media_backup_restore_target', 'record_media_backup_restore_history', 'finalize_media_backup_restore_files', 'current_media_file_state', 'public_media_file_state', 'get_media_file_replacement_history', 'find_media_file_replacement_history', 'append_media_file_replacement_history', 'media_transform_type_from_facts', 'media_backup_cleanup_policy_for_input', 'media_visual_reuse_policy_from_facts', 'mark_media_file_replacement_rolled_back', 'update_media_file_pointer', 'backup_relative_file_for_current_media' ) as $moved_media_backup_method ) {
	npcink_abilities_toolkit_assert_true( is_string( $core_write_source ) && false === strpos( $core_write_source, 'function ' . $moved_media_backup_method . '(' ), 'Core write package does not duplicate moved media backup method: ' . $moved_media_backup_method );
	npcink_abilities_toolkit_assert_true( is_string( $media_backup_write_trait ) && false !== strpos( $media_backup_write_trait, 'function ' . $moved_media_backup_method . '(' ), 'media backup trait owns moved method: ' . $moved_media_backup_method );
}
foreach ( array( 'npcink-abilities-toolkit/replace-media-file', 'npcink-abilities-toolkit/restore-media-backup', 'npcink-abilities-toolkit/cleanup-media-backups' ) as $media_backup_ability_id ) {
	npcink_abilities_toolkit_assert_true( is_string( $core_write_source ) && false !== strpos( $core_write_source, "'" . $media_backup_ability_id . "' => array(" ), 'Core write package remains the definition owner for media backup ability: ' . $media_backup_ability_id );
}
npcink_abilities_toolkit_assert_true( is_string( $core_write_source ) && false !== strpos( $core_write_source, 'use Media_Reference_Discovery_Write_Methods;' ), 'Core write package composes the media reference discovery trait' );
foreach ( array( 'media_content_reference_pairs_for_plan', 'media_content_reference_dynamic_sized_pairs', 'media_content_reference_source_relative_files', 'media_content_reference_without_unique_suffix', 'merge_media_content_reference_pairs', 'media_content_reference_candidate_posts', 'media_content_reference_needles', 'media_content_reference_url_path' ) as $moved_media_reference_discovery_method ) {
	npcink_abilities_toolkit_assert_true( is_string( $core_write_source ) && false === strpos( $core_write_source, 'function ' . $moved_media_reference_discovery_method . '(' ), 'Core write package does not duplicate moved media reference discovery method: ' . $moved_media_reference_discovery_method );
	npcink_abilities_toolkit_assert_true( is_string( $media_reference_discovery_write_trait ) && false !== strpos( $media_reference_discovery_write_trait, 'function ' . $moved_media_reference_discovery_method . '(' ), 'media reference discovery trait owns moved method: ' . $moved_media_reference_discovery_method );
}
foreach ( array( 'build_media_content_reference_repairs', 'validate_media_content_reference_repair_permissions', 'apply_media_content_reference_repairs', 'media_file_operation_verification' ) as $retained_media_reference_write_method ) {
	npcink_abilities_toolkit_assert_true( is_string( $core_write_source ) && false !== strpos( $core_write_source, 'function ' . $retained_media_reference_write_method . '(' ), 'Core write package retains media reference governance method: ' . $retained_media_reference_write_method );
}
foreach ( array( 'Baseline Inventory', 'Accepted Sequence', 'Gate Per Slice', 'Core_Write_Package.php', 'tests/run.php', 'Post_Attribute_Write_Methods', 'Site_Editor_Write_Methods', 'Media_Reference_Discovery_Write_Methods' ) as $required_structural_split_text ) {
	npcink_abilities_toolkit_assert_true( is_string( $structural_split_plan ) && false !== strpos( $structural_split_plan, $required_structural_split_text ), 'structural split plan preserves incremental extraction guidance: ' . $required_structural_split_text );
}
npcink_abilities_toolkit_assert_true( false !== strpos( $core_read_package_source, 'use Media_Alt_Caption_Read_Methods;' ), 'Core read package composes the media ALT/caption read trait' );
$moved_media_alt_caption_methods = array(
	'build_media_alt_caption_review_set',
	'build_media_alt_apply_plan',
	'media_alt_caption_review_source_policy',
	'media_alt_caption_index_image_context_evidence',
	'media_alt_caption_apply_image_context_evidence',
	'media_alt_caption_public_image_context_evidence',
	'media_alt_caption_image_context_evidence_request',
	'media_alt_caption_item_status',
	'media_alt_caption_candidate_quality',
	'media_alt_caption_candidate_quality_assessment',
	'media_alt_caption_review_quality_summary',
	'media_alt_caption_candidate_rejection_reason',
	'media_alt_caption_candidate_context_profile',
	'media_alt_caption_merge_candidate_confidence',
	'media_alt_caption_candidate_needs_context_confirmation',
	'media_alt_caption_clean_candidate',
	'media_alt_caption_normalized_candidate',
	'media_alt_caption_is_duplicate_metadata',
	'media_alt_caption_is_url_or_source_text',
	'media_alt_caption_is_runtime_provenance_text',
	'media_alt_caption_is_camera_default',
	'media_alt_caption_candidate_is_too_short',
	'media_alt_caption_is_too_generic_candidate',
	'media_alt_caption_has_metadata_conflict',
	'media_alt_caption_sentence',
	'media_alt_caption_filename_descriptor',
	'media_alt_caption_is_filename_like',
	'media_alt_caption_trim_chars',
	'media_alt_caption_text_length',
	'media_alt_caption_sanitize_string_list',
	'media_alt_caption_sanitize_payload',
);
foreach ( $moved_media_alt_caption_methods as $moved_media_alt_caption_method ) {
	npcink_abilities_toolkit_assert_true( false === strpos( $media_read_trait_source, 'function ' . $moved_media_alt_caption_method . '(' ), 'general media read trait does not duplicate moved ALT/caption method: ' . $moved_media_alt_caption_method );
	npcink_abilities_toolkit_assert_true( false !== strpos( $media_alt_caption_read_trait_source, 'function ' . $moved_media_alt_caption_method . '(' ), 'media ALT/caption read trait owns moved method: ' . $moved_media_alt_caption_method );
}
npcink_abilities_toolkit_assert_true( false !== strpos( $core_read_package_source, 'use Media_Candidate_Adoption_Read_Methods;' ), 'Core read package composes the media candidate adoption read trait' );
$media_candidate_adoption_trait_source = (string) file_get_contents( dirname( __DIR__ ) . '/includes/Packages/Read_Traits/Media_Candidate_Adoption_Read_Methods.php' );
$moved_media_candidate_adoption_methods = array(
	'build_image_candidate_review_artifact',
	'build_image_candidate_adoption_plan',
	'normalize_image_candidate_adoption_contract',
	'build_image_candidate_recommendation_projections',
	'normalize_image_candidate_license_review_status',
	'image_candidate_first_non_empty_url',
	'image_candidate_asset_persistence_policy',
	'is_temporary_image_candidate_url',
	'image_candidate_sanitize_string_list',
	'image_candidate_sanitize_payload',
	'image_candidate_bounded_text',
	'build_media_adoption_enhancement_plan',
);
foreach ( $moved_media_candidate_adoption_methods as $moved_media_candidate_adoption_method ) {
	npcink_abilities_toolkit_assert_true( false === strpos( $media_read_trait_source, 'function ' . $moved_media_candidate_adoption_method . '(' ), 'general media read trait does not duplicate moved candidate/adoption method: ' . $moved_media_candidate_adoption_method );
	npcink_abilities_toolkit_assert_true( false !== strpos( $media_candidate_adoption_trait_source, 'function ' . $moved_media_candidate_adoption_method . '(' ), 'media candidate adoption read trait owns moved method: ' . $moved_media_candidate_adoption_method );
}
$admin_css = file_get_contents( __DIR__ . '/../assets/admin.css' );
$admin_js = file_get_contents( __DIR__ . '/../assets/admin.js' );
$plugin_source = file_get_contents( __DIR__ . '/../includes/Plugin.php' );
$docs_readme = (string) file_get_contents( __DIR__ . '/../docs/README.md' );
$third_party_plugin_guide = (string) file_get_contents( __DIR__ . '/../docs/third-party-plugin-guide.md' );
$public_api_doc = (string) file_get_contents( __DIR__ . '/../docs/public-api.md' );
$ability_contract_reuse_readiness = (string) file_get_contents( __DIR__ . '/../docs/archive/ability-contract-reuse-readiness-2026-07-08.md' );
$media_boundary_doc = (string) file_get_contents( __DIR__ . '/../docs/media-format-attention-boundary.md' );
$oss_storage_shim_doc = (string) file_get_contents( __DIR__ . '/../docs/oss-storage-compatibility-shim.md' );
$oss_storage_shim_example = (string) file_get_contents( __DIR__ . '/../examples/oss-storage-shim.php' );
npcink_abilities_toolkit_assert_true( false !== strpos( $docs_readme, 'OSS Storage Compatibility Shim' ), 'documentation guide links the OSS storage shim contract' );
npcink_abilities_toolkit_assert_true( false !== strpos( $docs_readme, 'Ability Contract Reuse Readiness - 2026-07-08' ), 'documentation guide links the ability contract reuse readiness record' );
npcink_abilities_toolkit_assert_true( false !== strpos( (string) file_get_contents( __DIR__ . '/../README.md' ), 'ability-contract-reuse-readiness-2026-07-08.md' ), 'root README links the ability contract reuse readiness record' );
foreach (
	array(
		'Ability Contract Reuse Readiness',
		'ability_contracts',
		'proposal_handoff',
		'execution_profiles',
		'product_surface',
		'signed_transport',
		'runtime_detail',
		'No new Toolkit ability or runtime code is needed for this pass.',
		'implementation_posture',
		'meta.npcink.implementation_posture',
		'composer check:contracts',
		'composer check:consumer',
		'npcink-abilities-toolkit/create-draft',
		'npcink-abilities-toolkit/update-post-blocks',
		'npcink-abilities-toolkit/set-post-terms',
		'npcink-abilities-toolkit/update-media-details',
		'Stop and write a boundary note or ADR',
		'final WordPress mutation policy',
		'npcink-ai-client-adapter',
		'composer analyse:phpstan',
	) as $required_reuse_readiness_text
) {
	npcink_abilities_toolkit_assert_true( false !== strpos( $ability_contract_reuse_readiness, $required_reuse_readiness_text ), 'ability contract reuse readiness keeps required text: ' . $required_reuse_readiness_text );
}
npcink_abilities_toolkit_assert_true( false !== strpos( $third_party_plugin_guide, 'npcink_abilities_toolkit_media_storage_inspection' ), 'third-party guide documents the media storage inspection shim' );
npcink_abilities_toolkit_assert_true( false !== strpos( $public_api_doc, 'npcink_abilities_toolkit_media_storage_inspection' ), 'public API documents the media storage inspection filter' );
npcink_abilities_toolkit_assert_true( false !== strpos( $media_boundary_doc, 'oss-storage-compatibility-shim.md' ), 'media boundary links the OSS storage shim contract' );
npcink_abilities_toolkit_assert_true( false !== strpos( $oss_storage_shim_doc, 'Toolkit does not own' ), 'OSS storage shim contract preserves Toolkit ownership boundaries' );
npcink_abilities_toolkit_assert_true( false !== strpos( $oss_storage_shim_doc, 'remote_storage_write_requires_adapter' ), 'OSS storage shim contract keeps fail-closed blocked reasons' );
npcink_abilities_toolkit_assert_true( false !== strpos( $oss_storage_shim_doc, 'Extra fields' ) && false !== strpos( $oss_storage_shim_doc, 'not preserved' ), 'OSS storage shim contract documents bounded preserved fields' );
npcink_abilities_toolkit_assert_true( false !== strpos( $oss_storage_shim_example, 'npcink_abilities_toolkit_media_storage_inspection' ), 'OSS storage shim example uses the public storage inspection filter' );
npcink_abilities_toolkit_assert_true( false !== strpos( $oss_storage_shim_example, "'provider_api'" ), 'OSS storage shim example declares provider API readiness only through metadata' );
npcink_abilities_toolkit_assert_true( false === strpos( strtolower( $oss_storage_shim_doc . $oss_storage_shim_example ), 'access_key' ), 'OSS storage shim docs and example do not mention provider access keys' );
npcink_abilities_toolkit_assert_true( false !== strpos( $admin_test_page, 'PARENT_MENU_SLUG' ), 'admin test page knows the shared Npcink AI parent slug' );
npcink_abilities_toolkit_assert_true( false !== strpos( $admin_test_page, "const PARENT_MENU_SLUG    = 'npcink-ai';" ), 'admin test page targets the shared Npcink AI parent menu.' );
npcink_abilities_toolkit_assert_true( false !== strpos( $admin_test_page, "const MENU_SLUG           = 'npcink-abilities-toolkit';" ), 'admin test page uses the canonical Abilities admin slug' );
npcink_abilities_toolkit_assert_true( false !== strpos( $admin_test_page, 'npcink-ai-tabs npcink-abilities-toolkit-tabs' ) && false !== strpos( $admin_test_page, 'npcink-ai-tab-active' ) && false !== strpos( $admin_test_page, 'aria-current="page"' ), 'admin test page uses the shared Npcink AI tab visual standard.' );
npcink_abilities_toolkit_assert_true( false === strpos( $admin_test_page, 'nav-tab-wrapper' ) && false === strpos( $admin_test_page, 'nav-tab-active' ), 'admin test page no longer uses boxed WordPress nav tabs.' );
npcink_abilities_toolkit_assert_true( false !== strpos( $admin_css, '.npcink-ai-tabs' ) && false !== strpos( $admin_css, '.npcink-ai-tab-active' ), 'admin CSS includes the shared Npcink AI tab visual standard.' );
npcink_abilities_toolkit_assert_true( false !== strpos( $plugin_source, 'plugin_action_links_' ) && false !== strpos( $plugin_source, 'filter_plugin_action_links' ), 'plugin screen exposes an abilities shortcut when the admin page is enabled.' );
npcink_abilities_toolkit_assert_true( false !== strpos( $plugin_source, "esc_html__( 'View Abilities', 'npcink-abilities-toolkit' )" ), 'plugin screen abilities shortcut uses the plugin text domain.' );
npcink_abilities_toolkit_assert_true( false !== strpos( $plugin_source, 'menu_page_url' ) && false !== strpos( $plugin_source, 'admin.php?page=npcink-abilities-toolkit' ) && false !== strpos( $plugin_source, 'tools.php?page=npcink-abilities-toolkit' ), 'plugin screen abilities shortcut targets the registered menu page or standalone Tools fallback.' );
npcink_abilities_toolkit_assert_true( false !== strpos( $admin_test_page, '$hook_suffixes' ), 'admin test page stores real WordPress hook suffixes for asset loading.' );
npcink_abilities_toolkit_assert_true( false !== strpos( $admin_test_page, 'Next actions' ), 'admin overview provides a clear post-install next action area.' );
npcink_abilities_toolkit_assert_true( false !== strpos( $admin_test_page, 'View Abilities' ) && false !== strpos( $admin_test_page, 'Open Checks' ) && false !== strpos( $admin_test_page, 'View Connection Info' ), 'admin overview links post-install users to distinct ability tasks; the card containers and duplicate card headings were removed in favor of a single button row whose labels carry the same task semantics.' );
npcink_abilities_toolkit_assert_true( false !== strpos( $admin_test_page, 'get_callback_issue_count' ), 'admin overview summarizes callback readiness before catalog inspection.' );
npcink_abilities_toolkit_assert_true( false !== strpos( $admin_test_page, 'render_package_status' ) && false !== strpos( $admin_test_page, 'npcink-abilities-toolkit-packages__write-note' ), 'admin overview shows the read-only ability package map with a visible write-safeguard note.' );
npcink_abilities_toolkit_assert_true( false !== strpos( $plugin_source, 'function get_enabled_packages' ), 'plugin exposes the resolved package enable map for admin display.' );
npcink_abilities_toolkit_assert_true( false !== strpos( $admin_test_page, 'add_submenu_page' ), 'admin test page can attach to the shared Npcink AI menu' );
npcink_abilities_toolkit_assert_true( false !== strpos( $admin_test_page, 'add_management_page' ), 'admin test page keeps the standalone Tools fallback' );
npcink_abilities_toolkit_assert_true( false !== strpos( $admin_test_page, "__( 'AI Ability Set', 'npcink-abilities-toolkit' ),\n\t\t\t\t__( 'AI Ability Set', 'npcink-abilities-toolkit' )," ), 'admin test page registers translated abilities page and menu titles when attached' );
npcink_abilities_toolkit_assert_true( false !== strpos( $admin_test_page, 'npcink-abilities-toolkit-status__detail' ) && false === strpos( $admin_test_page, 'role="listitem" title=' ), 'admin status tiles show their detail text visibly instead of hover-only titles.' );
npcink_abilities_toolkit_assert_true( false !== strpos( $admin_test_page, 'No workflow scenarios are published on this site yet' ), 'admin workflow scenario view renders an explicit empty state.' );
npcink_abilities_toolkit_assert_true( false !== strpos( $admin_test_page, 'data-request-failed-label' ) && false !== strpos( $admin_test_page, 'data-copy-failed-label' ), 'admin page passes translated failure labels to its script.' );
npcink_abilities_toolkit_assert_true( substr_count( $admin_test_page, 'id="npcink-abilities-toolkit-admin-output"' ) <= 1 && false !== strpos( $admin_test_page, 'data-npcink-abilities-toolkit-output' ), 'admin page keeps a unique output element id and marks outputs with a data attribute.' );
npcink_abilities_toolkit_assert_true( false !== strpos( $admin_js, 'restoreLabel' ) && false !== strpos( $admin_js, 'copyFailedLabel' ) && false !== strpos( $admin_js, 'requestFailedLabel' ), 'admin script restores copy labels, reports copy failures, and prefixes request failures.' );
npcink_abilities_toolkit_assert_true( false !== strpos( $admin_js, 'button.disabled = busy' ) && false !== strpos( $admin_js, 'aria-busy' ) && false !== strpos( $admin_js, 'setButtonBusy' ), 'admin script disables request buttons while a request runs through one shared busy helper.' );
npcink_abilities_toolkit_assert_true( false === strpos( $admin_test_page, 'npcink_abilities_toolkit_nonce' ), 'admin diagnostic tab and filter URLs stay stable without one-time GET nonces.' );
$old_admin_slug = 'npcink-abilities-toolkit-' . 'test';
npcink_abilities_toolkit_assert_true( false === strpos( $admin_test_page, $old_admin_slug ), 'admin test page no longer uses the old test admin slug' );
foreach (
	array(
		'Review the WordPress abilities this site exposes to AI clients',
		'Site ability status',
		'Write safeguards',
		'host approval',
		'has_host_menu',
		'Next actions',
		'Open Checks',
			'This plugin exposes WordPress abilities',
			'Available AI Abilities',
			'Ability name, description, category, or technical ID',
			'What it allows',
			'Developer-only ability IDs and schema signals are kept in Developer Access',
			'Requires host approval',
			'$allowed_per_page = array( 5, 10, 25, 50, 100 )',
			"'npcink_abilities_toolkit_per_page', '10'",
		'Site Checks',
		'What it proves',
		'What it does not prove',
		'The site can safely return basic WordPress information',
		'It does not call an AI model',
		'The site can return a support-friendly environment summary',
		'It does not expose secrets',
		'Use Available Abilities to review what the site exposes',
		'Run Redacted Diagnostics',
		'Check results',
		'plain summary table',
		'No check has run yet',
		'npcink-abilities-toolkit-check-summary',
		'npcink-abilities-toolkit-check-summary-body',
		'Raw response for support',
		'Full JSON is kept here for support and developer troubleshooting',
		'get_check_summary_labels',
		'data-check-summary-labels',
		'Developer Access',
		'Connection values',
		'Raw discovery fetches',
		'Copy Abilities Endpoint',
		'Copy Contract Endpoint',
		'npcink-abilities-toolkit-contract-endpoint',
		'Most site users do not need this tab',
		'npcink_abilities_toolkit_readonly_check',
		'data-npcink-abilities-toolkit-readonly-check',
			'Run Site Info',
			'Catalog export',
			'Ability ID export',
			'Ability technical catalog',
			'Technical IDs, categories, schema availability, and callback status',
			'Ability ID',
			'Home URL and Site URL differ',
			'render_status_summary',
		'render_ability_catalog',
		'render_site_checks',
		'render_developer_access',
	) as $required
) {
	npcink_abilities_toolkit_assert_true( false !== strpos( $admin_test_page, $required ), 'admin test page keeps the user-facing ability surface: ' . $required );
}

foreach (
	array(
		'render_workflow_scenarios',
		'npcink-abilities-toolkit-scenarios',
		'DOCS_QUICKSTART_URL',
		'DOCS_HOST_CONTRACT_URL',
		'docs/rest-client-quickstart.md',
		'docs/host-approval-contract.md',
		'target="_blank" rel="noopener noreferrer"',
	) as $required
) {
	npcink_abilities_toolkit_assert_true( false !== strpos( $admin_test_page, $required ), 'admin test page keeps the workflow scenario overview and documentation links: ' . $required );
}
npcink_abilities_toolkit_assert_true( false === strpos( $admin_test_page, 'data-npcink-abilities-toolkit-run-recipe' ), 'admin workflow scenario overview stays read-only without recipe run affordances' );

foreach (
	array(
		'render_technical_tab',
		'npcink-abilities-toolkit-capability-summary',
		"=> 'technical'",
		'npcink-abilities-toolkit-ability-catalog',
		'Developer Tools',
		'get_technical_subs',
		'get_active_sub',
		'npcink_abilities_toolkit_sub',
		'npcink-abilities-toolkit-subnav',
	) as $required
) {
	npcink_abilities_toolkit_assert_true( false !== strpos( $admin_test_page, $required ), 'admin test page keeps the two-audience structure: ' . $required );
}

foreach (
	array(
		'setCheckSummary',
		'summarizeReadonlyPayload',
		'npcink-abilities-toolkit-check-summary-body',
		'data-check-summary-labels',
		'REST ' . "' + payload.status",
	) as $required
) {
	npcink_abilities_toolkit_assert_true( false !== strpos( $admin_js, $required ), 'admin check JavaScript keeps the plain summary result renderer: ' . $required );
}

$admin_surface_standard = file_get_contents( __DIR__ . '/../docs/admin-surface-standard.md' );
foreach (
	array(
		'site-operator ability status',
		'AI Ability Set',
		'available ability count',
		'write-safeguard posture',
		'Available Abilities',
		'Developer Access',
		'contract endpoint should be visible as a copyable host/runtime',
		'stable, shareable admin URLs',
		'Core proposal approval',
		'OpenClaw handoff',
		'Cloud API key',
		'Time Display',
		'WordPress site timezone',
		'Y-m-d H:i:s',
		'Keep those output field names and semantics stable',
	) as $required
) {
	npcink_abilities_toolkit_assert_true( false !== strpos( $admin_surface_standard, $required ), 'admin surface standard documents ability page boundary: ' . $required );
}

$plugin_readme = file_get_contents( __DIR__ . '/../readme.txt' );
foreach (
	array(
		'Third-Party Integration Quickstart',
		'npcink_abilities_toolkit_register_readonly',
		'npcink_abilities_toolkit_register_write_proposal',
		'Third-party provider callbacks should not perform final host-governed commits',
		'/wp-json/npcink-abilities-toolkit/v1/contract',
		'does not replace the WordPress Abilities API',
		'does not run abilities',
		'https://github.com/npcink/npcink-abilities-toolkit',
		'If the `wp-abilities/v1` REST routes are missing',
		'Abilities API baseline or compatibility plugin',
		'== Installation ==',
		'Frequently Asked Questions',
		'Does this plugin run AI models?',
		'Will this plugin change my posts, media, terms, comments, or settings by itself?',
		'What do the Safe Checks prove?',
		'What does uninstalling remove?',
		'npcink_abilities_toolkit_uninstall_preserve_media_backups',
		'Screenshots',
		'Overview tab: site ability status tiles',
		'Ability Catalog view under Developer Tools',
		'Checks view under Developer Tools',
		'Connection view under Developer Tools',
		'Refresh the SVN screenshot assets',
		'Source-level verification commands for contributors',
	) as $required
) {
	npcink_abilities_toolkit_assert_true( is_string( $plugin_readme ) && false !== strpos( $plugin_readme, $required ), 'packaged readme keeps third-party integration guidance: ' . $required );
}

$host_proof_status = file_get_contents( __DIR__ . '/../docs/host-proof-status.md' );
$block_theme_host_proof = file_get_contents( __DIR__ . '/block-theme-host-proof.php' );
$block_theme_host_proof_runner = file_get_contents( __DIR__ . '/../scripts/block-theme-host-proof.sh' );
foreach (
	array(
		'route-content-intent',
		'build-pattern-page-plan',
		'build-block-theme-site-plan',
		'fingerprint_before',
		'fingerprint_after',
		'postmeta',
		'no_mutation',
		'block_theme_proof_read_surface',
		'parse_blocks',
		'do_blocks',
		"'dry_run'",
		"'commit'",
	) as $required
) {
	npcink_abilities_toolkit_assert_true( is_string( $block_theme_host_proof ) && false !== strpos( $block_theme_host_proof, $required ), 'block-theme host proof preserves real-host no-mutation evidence: ' . $required );
}
npcink_abilities_toolkit_assert_true( is_string( $block_theme_host_proof_runner ) && false !== strpos( $block_theme_host_proof_runner, 'WP_CLI_MYSQL_SOCKET' ), 'block-theme host proof runner supports Local MySQL sockets' );
npcink_abilities_toolkit_assert_true( is_string( $block_theme_host_proof_runner ) && false !== strpos( $block_theme_host_proof_runner, 'Set WP_PATH' ), 'block-theme host proof requires an explicit real WordPress target' );
foreach (
	array(
		'Closed Proof State And Observation Queue',
		'All three proof targets in the ledger are closed',
		'Keep Toolkit in freeze/observe mode and do not add first-party abilities',
		'Real-host proof passed on 2026-07-11',
		'No new Toolkit ability gap was found',
		'examples and long-form docs outside the release zip',
		'does not block basic third-party provider',
	) as $required
) {
	npcink_abilities_toolkit_assert_true( is_string( $host_proof_status ) && false !== strpos( $host_proof_status, $required ), 'host proof status keeps the closed proof state: ' . $required );
}

$next_stage_standard = file_get_contents( __DIR__ . '/../docs/next-stage-operating-standard.md' );
foreach (
	array(
		'Current stage update',
		'intent-routing proofs are closed-loop proven',
		'There is no active proof target',
		'Do not ship the repository `docs/`, `examples/`, or scripts',
	) as $required
) {
	npcink_abilities_toolkit_assert_true( is_string( $next_stage_standard ) && false !== strpos( $next_stage_standard, $required ), 'next-stage standard keeps freeze/observe direction: ' . $required );
}

$translation_template = file_get_contents( __DIR__ . '/../languages/npcink-abilities-toolkit.pot' );
$traditional_chinese_po = __DIR__ . '/../languages/npcink-abilities-toolkit-zh_TW.po';
$traditional_chinese_mo = __DIR__ . '/../languages/npcink-abilities-toolkit-zh_TW.mo';
npcink_abilities_toolkit_assert_true( ! file_exists( $traditional_chinese_po ) && ! file_exists( $traditional_chinese_mo ), 'bundled translations do not include an incomplete Traditional Chinese locale pack' );
foreach (
	array(
		'Standalone WordPress Abilities API package toolkit for safely exposing agent-callable abilities.',
		'View Abilities',
		'AI Ability Set',
		'Available AI Abilities',
		'Developer Access',
		'Run Redacted Diagnostics',
		'Connection values',
		'Raw discovery fetches',
		'Contract Endpoint',
		'Copy Contract Endpoint',
		'Details',
		'Raw response for support',
		'Adopt Article Audio',
		'Writes reviewed generated narration or audio summary metadata to one post after host approval, or returns a dry-run preview by default.',
	) as $required
) {
	npcink_abilities_toolkit_assert_true( is_string( $translation_template ) && false !== strpos( $translation_template, $required ), 'translation template includes admin diagnostics string: ' . $required );
}

$main_plugin_header = file_get_contents( __DIR__ . '/../npcink-abilities-toolkit.php' );
npcink_abilities_toolkit_assert_true( false !== strpos( $main_plugin_header, 'Requires at least: 6.9' ), 'main plugin header requires WordPress 6.9' );
npcink_abilities_toolkit_assert_true( false !== strpos( $main_plugin_header, 'Requires PHP: 8.0' ), 'main plugin header requires PHP 8.0' );

$readme = file_get_contents( __DIR__ . '/../README.md' );
foreach (
	array(
		'first-party host-governed dry-run/write and destructive callbacks',
		'host-governed WordPress write/destructive ability packages',
		'Host-governed callbacks default to dry-run previews',
		'A real commit requires approval context',
		'admission, approval storage, audit truth, or final commit authorization',
	) as $required
) {
	npcink_abilities_toolkit_assert_true( false !== strpos( $readme, $required ), 'README precisely scopes host-governed ability ownership: ' . $required );
}
npcink_abilities_toolkit_assert_true( false !== strpos( $readme, 'docs/article-workflow-abilities-v1.md' ), 'README links the article workflow ability map.' );

$article_workflow_doc = file_get_contents( __DIR__ . '/../docs/article-workflow-abilities-v1.md' );
foreach (
	array(
		'article_draft_v1',
		'npcink-abilities-toolkit/recipes/article-draft',
		'article_assistant_workbench',
		'npcink-abilities-toolkit/create-draft',
		'does not provide a cloud writer',
		'Toolbox owns the operator workbench',
		'Core owns proposal intake',
		'Complexity Budget',
		'not a writing product',
		'Do not add Abilities-owned article generation',
		'hosted article drafting',
		'OpenClaw benefits from the same map without receiving a special bypass',
		'Cloud Addon should not implement cloud article writing',
	) as $required
) {
	npcink_abilities_toolkit_assert_true( false !== strpos( $article_workflow_doc, $required ), 'article workflow ability map preserves boundary: ' . $required );
}

$docs_readme = file_get_contents( __DIR__ . '/../docs/README.md' );
npcink_abilities_toolkit_assert_true( is_string( $docs_readme ) && false !== strpos( $docs_readme, 'pattern-page-reference-spike.md' ), 'docs guide links the pattern page reference spike' );
$pattern_page_reference_doc = file_get_contents( __DIR__ . '/../docs/pattern-page-reference-spike.md' );
foreach (
	array(
		'build-pattern-page-plan',
		'WordPress Core Block Patterns',
		'Spectra, CoBlocks, Getwid, Gutenverse, and Extendify',
		'AI Page Builders',
		'Do not add a third-party block dependency',
		'core-block-only',
		'Gutenberg-native',
		'design_quality',
		'custom_css_required',
		'proposal-bound and draft-safe',
		'Do not start by adding a generic visual DSL',
	) as $required
) {
	npcink_abilities_toolkit_assert_true( is_string( $pattern_page_reference_doc ) && false !== strpos( $pattern_page_reference_doc, $required ), 'pattern page reference spike preserves boundary: ' . $required );
}

function npcink_abilities_toolkit_schema_contract_fingerprint( array $schema ) {
	$properties = array();
	foreach ( (array) ( $schema['properties'] ?? array() ) as $property_key => $property_schema ) {
		if ( ! is_string( $property_key ) || ! is_array( $property_schema ) ) {
			continue;
		}

		$fingerprint = array();
		foreach ( array( 'type', 'enum', 'default', 'minimum', 'maximum', 'minLength', 'maxLength', 'maxItems' ) as $field ) {
			if ( array_key_exists( $field, $property_schema ) ) {
				$fingerprint[ $field ] = $property_schema[ $field ];
			}
		}
		if ( array_key_exists( 'additionalProperties', $property_schema ) ) {
			$additional_properties = $property_schema['additionalProperties'];
			$fingerprint['additionalProperties'] = is_array( $additional_properties )
				? array_intersect_key( $additional_properties, array( 'type' => true ) )
				: $additional_properties;
		}

		$properties[ $property_key ] = $fingerprint;
	}

	return array(
		'required'             => (array) ( $schema['required'] ?? array() ),
		'additionalProperties' => $schema['additionalProperties'] ?? null,
		'properties'           => $properties,
	);
}

function npcink_abilities_toolkit_core_governance_catalog_snapshot( array $abilities, array $ability_ids ) {
	$snapshot = array(
		'schema_version' => 'v1',
		'purpose'        => 'Core governance handoff contract snapshot for high-value abilities.',
		'abilities'      => array(),
	);

	foreach ( $ability_ids as $ability_id ) {
		$definition = $abilities[ $ability_id ] ?? array();
		npcink_abilities_toolkit_assert_true( is_array( $definition ), "snapshot ability {$ability_id} exists" );

		$snapshot['abilities'][ $ability_id ] = array(
			'category'          => (string) ( $definition['category'] ?? '' ),
			'risk_level'        => (string) ( $definition['risk_level'] ?? '' ),
			'requires_confirm'  => (bool) ( $definition['requires_confirm'] ?? false ),
			'requires_approval' => (bool) ( $definition['requires_approval'] ?? false ),
			'capability'        => (string) ( $definition['capability'] ?? '' ),
			'required_scope'    => (string) ( $definition['required_scope'] ?? '' ),
			'required_scopes'   => (array) ( $definition['required_scopes'] ?? array() ),
			'input'             => npcink_abilities_toolkit_schema_contract_fingerprint( is_array( $definition['input_schema'] ?? null ) ? $definition['input_schema'] : array() ),
			'output'            => npcink_abilities_toolkit_schema_contract_fingerprint( is_array( $definition['output_schema'] ?? null ) ? $definition['output_schema'] : array() ),
			'meta'              => array(
				'show_in_rest' => (bool) ( $definition['meta']['show_in_rest'] ?? false ),
				'mcp_public'   => (bool) ( $definition['meta']['mcp']['public'] ?? false ),
				'mcp_server'   => (string) ( $definition['meta']['mcp']['server'] ?? '' ),
				'mcp_risk'     => (string) ( $definition['meta']['mcp']['risk'] ?? '' ),
			),
		);
	}

	return $snapshot;
}

function npcink_abilities_toolkit_assert_package_read_ability_contract( $ability_id, $definition ) {
	$definition = is_array( $definition ) ? $definition : array();
	npcink_abilities_toolkit_assert_same( true, $definition['annotations']['readonly'] ?? null, "{$ability_id} is readonly" );
	npcink_abilities_toolkit_assert_same( false, $definition['annotations']['destructive'] ?? null, "{$ability_id} is not destructive" );
	npcink_abilities_toolkit_assert_same( 'read', $definition['risk_level'] ?? '', "{$ability_id} risk is read" );
	npcink_abilities_toolkit_assert_same( false, $definition['requires_approval'] ?? null, "{$ability_id} does not require host approval" );
	npcink_abilities_toolkit_assert_same( false, $definition['meta']['npcink']['requires_approval'] ?? null, "{$ability_id} Npcink metadata does not require approval" );
	npcink_abilities_toolkit_assert_same( true, $definition['meta']['show_in_rest'] ?? null, "{$ability_id} is shown in REST" );
	npcink_abilities_toolkit_assert_same( 'official', $definition['source'] ?? '', "{$ability_id} is an official migrated ability" );
	npcink_abilities_toolkit_assert_true( is_callable( $definition['execute_callback'] ?? null ), "{$ability_id} execute callback is callable" );
	npcink_abilities_toolkit_assert_true( is_array( $definition['input_schema'] ?? null ), "{$ability_id} has an input schema" );
	npcink_abilities_toolkit_assert_true( is_array( $definition['output_schema'] ?? null ), "{$ability_id} has an output schema" );
}

function npcink_abilities_toolkit_assert_package_write_ability_contract( $ability_id, $definition ) {
	$definition = is_array( $definition ) ? $definition : array();
	npcink_abilities_toolkit_assert_same( false, $definition['annotations']['readonly'] ?? null, "{$ability_id} is not readonly" );
	npcink_abilities_toolkit_assert_same( false, $definition['annotations']['destructive'] ?? null, "{$ability_id} is not destructive" );
	npcink_abilities_toolkit_assert_same( 'write', $definition['risk_level'] ?? '', "{$ability_id} risk is write" );
	npcink_abilities_toolkit_assert_same( true, $definition['requires_confirm'] ?? null, "{$ability_id} requires host approval" );
	npcink_abilities_toolkit_assert_same( true, $definition['requires_approval'] ?? null, "{$ability_id} exposes requires_approval for governance consumers" );
	npcink_abilities_toolkit_assert_same( true, $definition['meta']['npcink']['requires_approval'] ?? null, "{$ability_id} Npcink metadata requires approval" );
	npcink_abilities_toolkit_assert_same( true, $definition['meta']['show_in_rest'] ?? null, "{$ability_id} is shown in REST" );
	npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit-write', $definition['category'] ?? '', "{$ability_id} uses write category" );
	npcink_abilities_toolkit_assert_same( 'official', $definition['source'] ?? '', "{$ability_id} is an official migrated write ability" );
	npcink_abilities_toolkit_assert_same( true, $definition['project_to_npcink_catalog'] ?? null, "{$ability_id} projects into Npcink AI catalog" );
	npcink_abilities_toolkit_assert_true( is_callable( $definition['execute_callback'] ?? null ), "{$ability_id} execute callback is callable" );
	npcink_abilities_toolkit_assert_true( is_array( $definition['input_schema'] ?? null ), "{$ability_id} has an input schema" );
	npcink_abilities_toolkit_assert_true( is_array( $definition['output_schema'] ?? null ), "{$ability_id} has an output schema" );
	npcink_abilities_toolkit_assert_same( false, $definition['input_schema']['additionalProperties'] ?? null, "{$ability_id} input schema rejects undeclared fields" );
	npcink_abilities_toolkit_assert_same( true, $definition['input_schema']['properties']['dry_run']['default'] ?? null, "{$ability_id} dry_run defaults to preview" );
	npcink_abilities_toolkit_assert_same( false, $definition['input_schema']['properties']['commit']['default'] ?? null, "{$ability_id} commit defaults to false" );
	npcink_abilities_toolkit_assert_same( 190, $definition['input_schema']['properties']['idempotency_key']['maxLength'] ?? null, "{$ability_id} idempotency key is bounded" );
	npcink_abilities_toolkit_assert_same( true, $definition['meta']['mcp']['public'] ?? null, "{$ability_id} is MCP-public for governed write server discovery" );
	npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit-write', $definition['meta']['mcp']['server'] ?? '', "{$ability_id} belongs on governed write server" );
	npcink_abilities_toolkit_assert_write_like_implementation_posture( $ability_id, $definition );
}

function npcink_abilities_toolkit_assert_write_like_implementation_posture( $ability_id, $definition ) {
	$definition = is_array( $definition ) ? $definition : array();
	$posture    = isset( $definition['implementation_posture'] ) && is_array( $definition['implementation_posture'] )
		? $definition['implementation_posture']
		: array();

	npcink_abilities_toolkit_assert_same( $posture, $definition['meta']['implementation_posture'] ?? array(), "{$ability_id} mirrors implementation_posture into meta" );
	npcink_abilities_toolkit_assert_same( $posture, $definition['meta']['npcink']['implementation_posture'] ?? array(), "{$ability_id} mirrors implementation_posture into Npcink metadata" );
	npcink_abilities_toolkit_assert_same( 'npcink_abilities_toolkit_implementation_posture.v1', $posture['schema_version'] ?? '', "{$ability_id} exposes implementation posture schema version" );
	npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit', $posture['implementation_owner'] ?? '', "{$ability_id} keeps Toolkit as implementation owner" );
	npcink_abilities_toolkit_assert_same( 'wordpress_abilities_api_host_governed', $posture['execution_surface'] ?? '', "{$ability_id} exposes host-governed Abilities API execution surface" );
	npcink_abilities_toolkit_assert_same( 'host_governed_dry_run_first', $posture['write_posture'] ?? '', "{$ability_id} declares dry-run-first write posture" );
	npcink_abilities_toolkit_assert_same( 'host_runtime_approval_context_required', $posture['commit_authority'] ?? '', "{$ability_id} leaves commit authority with the host runtime" );
	npcink_abilities_toolkit_assert_same( 'host_governance_layer', $posture['final_authorization_owner'] ?? '', "{$ability_id} leaves final authorization with the host governance layer" );
	npcink_abilities_toolkit_assert_same( 'host_governance_layer', $posture['approval_truth_owner'] ?? '', "{$ability_id} leaves approval truth with the host governance layer" );
	npcink_abilities_toolkit_assert_same( 'host_governance_layer', $posture['audit_truth_owner'] ?? '', "{$ability_id} leaves audit truth with the host governance layer" );
	npcink_abilities_toolkit_assert_same( false, $posture['direct_wordpress_write_default'] ?? true, "{$ability_id} does not default to direct WordPress mutation" );
	npcink_abilities_toolkit_assert_same( true, $posture['dry_run_default'] ?? null, "{$ability_id} implementation posture defaults to dry-run" );
	npcink_abilities_toolkit_assert_same( false, $posture['commit_default'] ?? true, "{$ability_id} implementation posture disables commit by default" );
	npcink_abilities_toolkit_assert_true( ! empty( $posture['reference_patterns'] ) && is_array( $posture['reference_patterns'] ), "{$ability_id} names reference implementation patterns" );
	npcink_abilities_toolkit_assert_true( ! empty( $posture['verification_contract'] ) && is_array( $posture['verification_contract'] ), "{$ability_id} names verification contract evidence" );
	npcink_abilities_toolkit_assert_true( ! empty( $posture['required_host_evidence'] ) && is_array( $posture['required_host_evidence'] ), "{$ability_id} names required host evidence" );
	foreach ( array( 'workflow_runtime', 'queue_or_scheduler', 'model_routing', 'provider_credentials', 'approval_storage', 'audit_storage' ) as $forbidden_flag ) {
		npcink_abilities_toolkit_assert_same( false, $posture[ $forbidden_flag ] ?? true, "{$ability_id} implementation posture excludes {$forbidden_flag}" );
	}
}

function npcink_abilities_toolkit_assert_package_destructive_ability_contract( $ability_id, $definition ) {
	$definition = is_array( $definition ) ? $definition : array();
	npcink_abilities_toolkit_assert_same( false, $definition['annotations']['readonly'] ?? null, "{$ability_id} is not readonly" );
	npcink_abilities_toolkit_assert_same( true, $definition['annotations']['destructive'] ?? null, "{$ability_id} is destructive" );
	npcink_abilities_toolkit_assert_same( 'destructive', $definition['risk_level'] ?? '', "{$ability_id} risk is destructive" );
	npcink_abilities_toolkit_assert_same( true, $definition['requires_confirm'] ?? null, "{$ability_id} requires host approval" );
	npcink_abilities_toolkit_assert_same( true, $definition['requires_approval'] ?? null, "{$ability_id} exposes requires_approval for governance consumers" );
	npcink_abilities_toolkit_assert_same( true, $definition['meta']['npcink']['requires_approval'] ?? null, "{$ability_id} Npcink metadata requires approval" );
	npcink_abilities_toolkit_assert_same( true, $definition['meta']['show_in_rest'] ?? null, "{$ability_id} is shown in REST" );
	npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit-write', $definition['category'] ?? '', "{$ability_id} keeps legacy write category" );
	npcink_abilities_toolkit_assert_same( 'official', $definition['source'] ?? '', "{$ability_id} is an official migrated destructive ability" );
	npcink_abilities_toolkit_assert_same( true, $definition['project_to_npcink_catalog'] ?? null, "{$ability_id} projects into Npcink AI catalog" );
	npcink_abilities_toolkit_assert_true( is_callable( $definition['execute_callback'] ?? null ), "{$ability_id} execute callback is callable" );
	npcink_abilities_toolkit_assert_true( is_array( $definition['input_schema'] ?? null ), "{$ability_id} has an input schema" );
	npcink_abilities_toolkit_assert_true( is_array( $definition['output_schema'] ?? null ), "{$ability_id} has an output schema" );
	npcink_abilities_toolkit_assert_same( false, $definition['input_schema']['additionalProperties'] ?? null, "{$ability_id} input schema rejects undeclared fields" );
	npcink_abilities_toolkit_assert_same( true, $definition['input_schema']['properties']['dry_run']['default'] ?? null, "{$ability_id} dry_run defaults to preview" );
	npcink_abilities_toolkit_assert_same( false, $definition['input_schema']['properties']['commit']['default'] ?? null, "{$ability_id} commit defaults to false" );
	npcink_abilities_toolkit_assert_same( 190, $definition['input_schema']['properties']['idempotency_key']['maxLength'] ?? null, "{$ability_id} idempotency key is bounded" );
	npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit-write', $definition['meta']['mcp']['server'] ?? '', "{$ability_id} belongs on governed write server" );
	npcink_abilities_toolkit_assert_same( 'destructive', $definition['meta']['mcp']['risk'] ?? '', "{$ability_id} MCP risk is destructive" );
	npcink_abilities_toolkit_assert_write_like_implementation_posture( $ability_id, $definition );
}

$schema_normalizer = new Schema_Normalizer();
$annotation_normalizer = new Annotation_Normalizer();
$normalized_schema = $schema_normalizer->normalize(
	array(
		'type'       => 'object',
		'properties' => array(
			'title' => 'string',
			'meta'  => array(
				'type'       => 'object',
				'properties' => array(
					'score' => 'number',
				),
			),
		),
	)
);
npcink_abilities_toolkit_assert_same( 'string', $normalized_schema['properties']['title']['type'], 'schema shorthand string is normalized' );
npcink_abilities_toolkit_assert_same( 'number', $normalized_schema['properties']['meta']['properties']['score']['type'], 'nested schema shorthand is normalized' );

$destructive_annotations = $annotation_normalizer->normalize(
	array( 'instructions' => "Use carefully.\nNever skip review." ),
	'destructive'
);
npcink_abilities_toolkit_assert_same( false, $destructive_annotations['readonly'], 'destructive annotation is not readonly' );
npcink_abilities_toolkit_assert_same( true, $destructive_annotations['destructive'], 'destructive annotation is destructive' );
npcink_abilities_toolkit_assert_same( false, $destructive_annotations['idempotent'], 'destructive annotation is not idempotent' );
npcink_abilities_toolkit_assert_same( 'Use carefully. Never skip review.', $destructive_annotations['instructions'], 'annotation instructions are sanitized' );

$contract_normalizer = new Contract_Normalizer( $schema_normalizer, $annotation_normalizer );
$readonly = $contract_normalizer->normalize(
	'Acme/Site Summary',
	array(
		'label'            => 'Site Summary',
		'description'      => 'Returns site summary.',
		'channels'         => array( 'abilities_rest', 'mcp' ),
		'input_schema'     => array( 'type' => 'object' ),
		'output_schema'    => array( 'type' => 'object' ),
		'execute_callback' => static function () {
			return array();
		},
	),
	'readonly'
);
npcink_abilities_toolkit_assert_same( 'acme/sitesummary', $readonly['ability_id'], 'ability id is lowercased and stripped to machine-safe characters' );
npcink_abilities_toolkit_assert_same( true, $readonly['annotations']['readonly'], 'readonly ability annotation is readonly' );
npcink_abilities_toolkit_assert_same( false, $readonly['annotations']['destructive'], 'readonly ability is not destructive' );
npcink_abilities_toolkit_assert_same( 'read', $readonly['risk_level'], 'readonly ability risk is read' );
npcink_abilities_toolkit_assert_same( true, $readonly['meta']['show_in_rest'], 'readonly ability defaults to show_in_rest' );
npcink_abilities_toolkit_assert_same( 'read', $readonly['meta']['mcp']['risk'], 'readonly mcp risk is read' );
npcink_abilities_toolkit_assert_true( ! isset( $readonly['input_schema']['properties']['dry_run'] ), 'readonly input schema does not include write dry_run control' );
npcink_abilities_toolkit_assert_true( ! isset( $readonly['input_schema']['properties']['commit'] ), 'readonly input schema does not include write commit control' );
npcink_abilities_toolkit_assert_true( ! isset( $readonly['input_schema']['properties']['idempotency_key'] ), 'readonly input schema does not include write idempotency_key control' );
npcink_abilities_toolkit_assert_true( ! isset( $readonly['output_schema']['properties']['commit_required'] ), 'readonly output schema does not include write commit_required field' );

$write = $contract_normalizer->normalize(
	'acme/create-draft-proposal',
	array(
		'label'            => 'Create Draft Proposal',
		'description'      => 'Builds a draft proposal.',
		'input_schema'     => array( 'type' => 'object' ),
		'output_schema'    => array( 'type' => 'object' ),
		'execute_callback' => static function () {
			return array( 'proposal_id' => 'test' );
		},
	),
	'write_proposal'
);
npcink_abilities_toolkit_assert_same( false, $write['annotations']['readonly'], 'write proposal is not readonly' );
npcink_abilities_toolkit_assert_same( 'write', $write['risk_level'], 'write proposal risk is write' );
npcink_abilities_toolkit_assert_same( true, $write['requires_confirm'], 'write proposal requires confirmation' );
npcink_abilities_toolkit_assert_same( true, $write['requires_approval'], 'write proposal exposes approval requirement alias' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit-write', $write['category'], 'write proposal default category is write category' );
foreach ( array( 'dry_run', 'commit', 'idempotency_key' ) as $write_control_property ) {
	npcink_abilities_toolkit_assert_true(
		isset( $write['input_schema']['properties'][ $write_control_property ] ),
		"write proposal input schema includes {$write_control_property} control"
	);
}
npcink_abilities_toolkit_assert_same( true, $write['input_schema']['properties']['dry_run']['default'] ?? null, 'write proposal dry_run defaults to preview' );
npcink_abilities_toolkit_assert_same( false, $write['input_schema']['properties']['commit']['default'] ?? null, 'write proposal commit defaults to false' );
npcink_abilities_toolkit_assert_same( 190, $write['input_schema']['properties']['idempotency_key']['maxLength'] ?? null, 'write proposal idempotency key is bounded' );
foreach ( array( 'dry_run', 'host_governed', 'commit_required', 'preview' ) as $write_output_property ) {
	npcink_abilities_toolkit_assert_true(
		isset( $write['output_schema']['properties'][ $write_output_property ] ),
		"write proposal output schema includes {$write_output_property} field"
	);
}

$destructive = $contract_normalizer->normalize(
	'acme/delete-post',
	array(
		'label'            => 'Delete Post',
		'description'      => 'Deletes a post through a host-governed path.',
		'input_schema'     => array( 'type' => 'object' ),
		'output_schema'    => array( 'type' => 'object' ),
		'execute_callback' => static function () {
			return array( 'dry_run' => true );
		},
	),
	'destructive_host'
);
npcink_abilities_toolkit_assert_same( true, $destructive['annotations']['destructive'], 'destructive host ability is destructive' );
npcink_abilities_toolkit_assert_same( 'destructive', $destructive['risk_level'], 'destructive host ability risk is destructive' );
npcink_abilities_toolkit_assert_same( true, $destructive['requires_confirm'], 'destructive host ability requires confirmation' );
npcink_abilities_toolkit_assert_same( true, $destructive['requires_approval'], 'destructive host ability exposes approval requirement alias' );
foreach ( array( 'dry_run', 'commit', 'idempotency_key' ) as $destructive_control_property ) {
	npcink_abilities_toolkit_assert_true(
		isset( $destructive['input_schema']['properties'][ $destructive_control_property ] ),
		"destructive input schema includes {$destructive_control_property} control"
	);
}
npcink_abilities_toolkit_assert_same( true, $destructive['input_schema']['properties']['dry_run']['default'] ?? null, 'destructive dry_run defaults to preview' );
npcink_abilities_toolkit_assert_same( false, $destructive['input_schema']['properties']['commit']['default'] ?? null, 'destructive commit defaults to false' );
npcink_abilities_toolkit_assert_same( 190, $destructive['input_schema']['properties']['idempotency_key']['maxLength'] ?? null, 'destructive idempotency key is bounded' );
foreach ( array( 'dry_run', 'host_governed', 'commit_required', 'preview' ) as $destructive_output_property ) {
	npcink_abilities_toolkit_assert_true(
		isset( $destructive['output_schema']['properties'][ $destructive_output_property ] ),
		"destructive output schema includes {$destructive_output_property} field"
	);
}


// The suite body is split into ordered parts; state is shared through
// globals and top-level variables, so the include order below is load-bearing.
require __DIR__ . '/run/10-registry-plugin-boot.php';
require __DIR__ . '/run/20-package-definitions.php';
require __DIR__ . '/run/30-post-write-flows.php';
require __DIR__ . '/run/40-block-theme-comment-flows.php';
require __DIR__ . '/run/50-read-planning-enrichment.php';
require __DIR__ . '/run/60-media-file-operations.php';
require __DIR__ . '/run/70-cloud-media-backups.php';
require __DIR__ . '/run/80-media-restore-alt-plans.php';
require __DIR__ . '/run/90-media-inventory-article-plans.php';
require __DIR__ . '/run/95-content-intent-routing.php';
require __DIR__ . '/run/99-page-patterns-closeout.php';

echo "OK: {$assertions} assertions\n";
