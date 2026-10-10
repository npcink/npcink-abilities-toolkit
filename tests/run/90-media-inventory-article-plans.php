<?php
/**
 * Ordered regression suite part: 90-media-inventory-article-plans.
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

$GLOBALS['npcink_abilities_toolkit_unit_options']['theme_builder_media_setting'] = array(
	'hero' => array(
		'image' => 'https://example.test/wp-content/uploads/2026/06/workflow-diagram-image.jpg',
	),
);
$GLOBALS['npcink_abilities_toolkit_unit_theme_mods']['header_image'] = '/wp-content/uploads/2026/06/workflow-diagram-image.jpg';
$media_settings_reference_plan = $core_read_package->build_media_settings_reference_repair_plan(
	array(
		'attachment_id'    => 79,
		'replacement_id'   => 'media_replace_unit',
		'option_names'     => array( 'theme_builder_media_setting' ),
		'theme_mod_names'  => array( 'header_image' ),
		'min_width'        => 64,
		'min_height'       => 64,
	)
);
npcink_abilities_toolkit_assert_same( true, $media_settings_reference_plan['success'] ?? null, 'build-media-settings-reference-repair-plan returns a success envelope' );
npcink_abilities_toolkit_assert_same( false, $media_settings_reference_plan['data']['commit_execution'] ?? null, 'media settings reference repair plan does not execute commits' );
npcink_abilities_toolkit_assert_same( 2, $media_settings_reference_plan['data']['action_count'] ?? 0, 'media settings reference repair plan builds option and theme mod patch actions' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/patch-setting-value', $media_settings_reference_plan['data']['write_actions'][0]['target_ability_id'] ?? '', 'media settings reference repair plan reuses patch-setting-value' );
npcink_abilities_toolkit_assert_same( 'theme_builder_media_setting', $media_settings_reference_plan['data']['write_actions'][0]['input']['target_name'] ?? '', 'media settings reference repair targets the option name' );
npcink_abilities_toolkit_assert_same( 'header_image', $media_settings_reference_plan['data']['write_actions'][1]['input']['target_name'] ?? '', 'media settings reference repair targets the theme mod name' );
$media_settings_excluded_plan = $core_read_package->build_media_settings_reference_repair_plan(
	array(
		'attachment_id'     => 79,
		'replacement_id'    => 'media_replace_unit',
		'option_names'      => array( 'theme_builder_media_setting' ),
		'include_theme_mods' => false,
		'excluded_formats'  => array( 'jpg' ),
	)
);
npcink_abilities_toolkit_assert_same( true, $media_settings_excluded_plan['success'] ?? null, 'media settings reference repair accepts excluded format policy' );
npcink_abilities_toolkit_assert_same( 0, $media_settings_excluded_plan['data']['action_count'] ?? 1, 'media settings reference repair does not build actions for excluded source formats' );
npcink_abilities_toolkit_assert_same( 'source_format_excluded', $media_settings_excluded_plan['data']['manual_review'][0]['reason'] ?? '', 'media settings reference repair sends excluded formats to manual review' );
	$patch_setting_preview = $core_write_package->patch_setting_value(
		array(
			'target_type' => 'option',
			'target_name' => 'theme_builder_media_setting',
			'operations'  => $media_settings_reference_plan['data']['write_actions'][0]['input']['operations'] ?? array(),
			'dry_run'     => true,
		)
	);
	npcink_abilities_toolkit_assert_true( is_wp_error( $patch_setting_preview ), 'patch-setting-value rejects settings that the host has not allowlisted' );
	npcink_abilities_toolkit_assert_same( 'npcink_abilities_toolkit_setting_target_not_allowed', $patch_setting_preview->get_error_code() ?? '', 'patch-setting-value uses a stable not-allowlisted error code' );
	add_filter(
		'npcink_abilities_toolkit_patchable_setting_targets',
		static function ( $targets ) {
			$targets = is_array( $targets ) ? $targets : array();
			$targets['option'] = array( 'theme_builder_media_setting', 'oversized_setting_value' );
			$targets['theme_mod'] = array( 'header_image' );
			return $targets;
		}
	);
	$blocked_sensitive_setting = $core_write_package->patch_setting_value(
		array(
			'target_type' => 'option',
			'target_name' => 'vendor_api_token',
			'operations'  => array( array( 'op' => 'replace', 'find' => 'old', 'replace' => 'new' ) ),
			'dry_run'     => true,
		)
	);
	npcink_abilities_toolkit_assert_true( is_wp_error( $blocked_sensitive_setting ), 'patch-setting-value blocks sensitive setting names even when a host filter allows them' );
	npcink_abilities_toolkit_assert_same( 'npcink_abilities_toolkit_setting_target_blocked', $blocked_sensitive_setting->get_error_code() ?? '', 'patch-setting-value uses a stable sensitive-setting error code' );
	$patch_setting_preview = $core_write_package->patch_setting_value(
		array(
		'target_type' => 'option',
		'target_name' => 'theme_builder_media_setting',
		'operations'  => $media_settings_reference_plan['data']['write_actions'][0]['input']['operations'] ?? array(),
		'dry_run'     => true,
	)
);
	npcink_abilities_toolkit_assert_same( true, $patch_setting_preview['dry_run'] ?? null, 'patch-setting-value returns a governed dry-run preview' );
	npcink_abilities_toolkit_assert_same( 1, $patch_setting_preview['patch_preview'][0]['applied'] ?? null, 'patch-setting-value reports applied operation count' );
	npcink_abilities_toolkit_assert_true( ! isset( $patch_setting_preview['diff_preview']['before_fragment'] ) && ! isset( $patch_setting_preview['diff_preview']['after_fragment'] ), 'patch-setting-value does not expose stored setting fragments in its diff' );
	npcink_abilities_toolkit_assert_true( ! isset( $patch_setting_preview['impact_ranges'][0]['before_preview'] ) && ! isset( $patch_setting_preview['impact_ranges'][0]['after_preview'] ), 'patch-setting-value does not expose stored setting fragments in impact ranges' );
$GLOBALS['npcink_abilities_toolkit_unit_options']['oversized_setting_value'] = str_repeat( 'x', 262145 );
$oversized_patch_setting = $core_write_package->patch_setting_value(
	array(
		'target_type' => 'option',
		'target_name' => 'oversized_setting_value',
		'operations'  => array(
			array(
				'op'      => 'replace',
				'find'    => 'x',
				'replace' => 'y',
			),
		),
		'dry_run'     => true,
	)
);
unset( $GLOBALS['npcink_abilities_toolkit_unit_options']['oversized_setting_value'] );
npcink_abilities_toolkit_assert_true( is_wp_error( $oversized_patch_setting ), 'patch-setting-value rejects oversized setting values before recursive patching' );
npcink_abilities_toolkit_assert_same( 'npcink_abilities_toolkit_input_too_large', $oversized_patch_setting->code ?? '', 'patch-setting-value oversized setting value fails with stable code' );
$GLOBALS['npcink_ai_runtime_wp_ability_context'] = array( 'context' => array( 'approval_commit_authorized' => true ) );
$patch_setting_commit = $core_write_package->patch_setting_value(
	array(
		'target_type' => 'theme_mod',
		'target_name' => 'header_image',
		'operations'  => $media_settings_reference_plan['data']['write_actions'][1]['input']['operations'] ?? array(),
		'commit'      => true,
	)
);
unset( $GLOBALS['npcink_ai_runtime_wp_ability_context'] );
	npcink_abilities_toolkit_assert_same( false, $patch_setting_commit['dry_run'] ?? null, 'patch-setting-value commit exits dry-run after approval' );
	npcink_abilities_toolkit_assert_true( false !== strpos( (string) get_theme_mod( 'header_image', '' ), 'workflow-diagram-image-optimized.webp' ), 'patch-setting-value commits exact theme mod URL replacement' );
	remove_all_filters( 'npcink_abilities_toolkit_patchable_setting_targets' );
$media_health = $core_read_package->get_media_inventory_health(
	array(
		'mime_type' => 'image',
		'per_page'  => 5,
		'stable_order' => 'id_asc',
	)
);
npcink_abilities_toolkit_assert_same( true, $media_health['success'] ?? null, 'get-media-inventory-health returns a success envelope' );
npcink_abilities_toolkit_assert_true( (int) ( $media_health['data']['summary']['scanned_count'] ?? 0 ) >= 1, 'get-media-inventory-health scans local media rows' );
npcink_abilities_toolkit_assert_true( isset( $media_health['data']['issue_counts']['missing_alt'] ), 'get-media-inventory-health counts missing alt text' );
npcink_abilities_toolkit_assert_same( 'id_asc', $media_health['data']['summary']['stable_order'] ?? '', 'get-media-inventory-health reports the stable order used for resumable scans' );
$media_health_row = npcink_abilities_toolkit_find_row_by_key( (array) ( $media_health['data']['items'] ?? array() ), 'attachment_id', 79 );
npcink_abilities_toolkit_assert_same( true, $media_health_row['format_inspection']['format_plan']['needs_attention'] ?? null, 'get-media-inventory-health includes format inspection attention state' );
npcink_abilities_toolkit_assert_true( in_array( 'legacy_image_format', (array) ( $media_health_row['format_inspection']['warnings'] ?? array() ), true ), 'get-media-inventory-health includes legacy format warning' );
$media_projection_rows = $core_read_package->get_media_inventory_health(
	array(
		'mime_type'      => 'image',
		'attachment_ids' => array( 79 ),
		'per_page'       => 20,
	)
);
npcink_abilities_toolkit_assert_same( 1, count( (array) ( $media_projection_rows['data']['items'] ?? array() ) ), 'get-media-inventory-health can revalidate a bounded attachment-id selection' );
npcink_abilities_toolkit_assert_same( 79, $media_projection_rows['data']['items'][0]['attachment_id'] ?? 0, 'media inventory attachment-id selection preserves the requested local identity' );
npcink_abilities_toolkit_assert_true( 71 === strlen( (string) ( $media_projection_rows['data']['items'][0]['media_fingerprint'] ?? '' ) ) && 0 === strpos( (string) ( $media_projection_rows['data']['items'][0]['media_fingerprint'] ?? '' ), 'sha256:' ), 'media inventory rows expose a canonical SHA-256 revision fingerprint for rebuildable Cloud projections' );
$media_cleanup = $core_read_package->get_media_cleanup_opportunities(
	array(
		'mime_type' => 'image',
		'per_page'  => 5,
	)
);
npcink_abilities_toolkit_assert_same( true, $media_cleanup['success'] ?? null, 'get-media-cleanup-opportunities returns a success envelope' );
npcink_abilities_toolkit_assert_true( (int) ( $media_cleanup['data']['summary']['opportunity_count'] ?? 0 ) >= 1, 'get-media-cleanup-opportunities finds cleanup opportunities' );
npcink_abilities_toolkit_assert_true( isset( $media_cleanup['data']['issue_counts']['possibly_unattached'] ), 'get-media-cleanup-opportunities counts unattached media' );
$media_fix_plan = $core_read_package->build_media_inventory_fix_plan(
	array(
		'attachment_ids'  => array( 79 ),
		'issue_types'     => array( 'missing_alt', 'missing_caption', 'missing_description', 'format_attention', 'possibly_unattached' ),
		'article_title'   => 'Workflow automation',
		'article_excerpt' => 'Workflow automation improves repeatable editorial operations.',
		'focus_keyword'   => 'workflow',
	)
);
npcink_abilities_toolkit_assert_same( true, $media_fix_plan['success'] ?? null, 'build-media-inventory-fix-plan returns a success envelope' );
npcink_abilities_toolkit_assert_same( true, $media_fix_plan['data']['requires_approval'] ?? null, 'media inventory fix plan requires approval' );
npcink_abilities_toolkit_assert_same( false, $media_fix_plan['data']['commit_execution'] ?? null, 'media inventory fix plan does not execute commits' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/update-media-details', $media_fix_plan['data']['write_actions'][0]['target_ability_id'] ?? '', 'media inventory fix plan reuses update-media-details' );
npcink_abilities_toolkit_assert_same( 0, npcink_abilities_toolkit_count_plan_actions_for_ability( (array) ( $media_fix_plan['data']['write_actions'] ?? array() ), 'npcink-abilities-toolkit/delete-media-permanently' ), 'media inventory fix plan does not map parentless media to delete actions by default' );
npcink_abilities_toolkit_assert_same( false, $media_fix_plan['data']['write_actions'][0]['commit_execution'] ?? null, 'media metadata plan action does not execute commits' );
npcink_abilities_toolkit_assert_true( isset( $media_fix_plan['data']['preview'][0]['before']['alt'] ), 'media inventory fix plan returns before preview' );
npcink_abilities_toolkit_assert_true( isset( $media_fix_plan['data']['preview'][0]['after_suggestion']['alt'] ), 'media inventory fix plan returns after suggestion preview' );
npcink_abilities_toolkit_assert_same( true, $media_fix_plan['data']['manual_review'][0]['format_plan']['should_convert'] ?? null, 'media inventory fix plan carries format inspection recommendations into manual review' );
npcink_abilities_toolkit_assert_same( 'legacy_image_format', $media_fix_plan['data']['manual_review'][0]['format_governance']['detected_reason'] ?? '', 'media inventory fix plan records a format attention detected reason' );
npcink_abilities_toolkit_assert_same( 'generate_optimized_derivative', $media_fix_plan['data']['manual_review'][0]['format_governance']['suggested_operation'] ?? '', 'media inventory fix plan suggests a lightweight future operation for format attention' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/build-media-derivative-cloud-request', $media_fix_plan['data']['manual_review'][0]['format_governance']['target_future_ability'] ?? '', 'media inventory fix plan points format attention at the read-only Cloud request planner without mapping it' );
npcink_abilities_toolkit_assert_same( false, $media_fix_plan['data']['manual_review'][0]['format_governance']['write_action_generated'] ?? null, 'media inventory fix plan keeps format attention read-only' );
npcink_abilities_toolkit_assert_same( 'high', $media_fix_plan['data']['manual_review'][0]['format_governance']['estimated_risk'] ?? '', 'media inventory fix plan marks format asset work as high risk' );
npcink_abilities_toolkit_assert_same( 0, npcink_abilities_toolkit_count_plan_actions_for_ability( (array) ( $media_fix_plan['data']['write_actions'] ?? array() ), 'npcink-abilities-toolkit/build-media-derivative-cloud-request' ), 'media inventory fix plan does not map format attention to the Cloud request planner as a write action' );
npcink_abilities_toolkit_assert_same( 0, npcink_abilities_toolkit_count_plan_actions_for_ability( (array) ( $media_fix_plan['data']['write_actions'] ?? array() ), 'npcink-abilities-toolkit/optimize-media-asset' ), 'media inventory fix plan does not map format attention to optimize-media-asset' );
npcink_abilities_toolkit_assert_same( 0, npcink_abilities_toolkit_count_plan_actions_for_ability( (array) ( $media_fix_plan['data']['write_actions'] ?? array() ), 'npcink-abilities-toolkit/convert-media-format' ), 'media inventory fix plan does not map format attention to convert-media-format' );
npcink_abilities_toolkit_assert_same( 0, npcink_abilities_toolkit_count_plan_actions_for_ability( (array) ( $media_fix_plan['data']['write_actions'] ?? array() ), 'npcink-abilities-toolkit/replace-media-file' ), 'media inventory fix plan does not map format attention to replace-media-file' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/delete-media-permanently', $media_fix_plan['data']['skipped_destructive_candidates'][0]['target_ability_id'] ?? '', 'media inventory fix plan skips destructive candidates by default' );
npcink_abilities_toolkit_assert_same( 'delete_candidates_not_enabled', $media_fix_plan['data']['skipped_destructive_candidates'][0]['blocked_reason'] ?? '', 'media inventory fix plan explains default destructive skip reason' );
$media_delete_plan = $core_read_package->build_media_inventory_fix_plan(
	array(
		'attachment_ids'              => array( 79 ),
		'issue_types'                 => array( 'possibly_unattached' ),
		'include_delete_candidates'   => true,
	)
);
npcink_abilities_toolkit_assert_same( 0, count( (array) ( $media_delete_plan['data']['write_actions'] ?? array() ) ), 'media inventory fix plan does not map delete candidates without unattached nonproduction-media opt-in' );
npcink_abilities_toolkit_assert_same( 'unattached_nonproduction_media_not_enabled', $media_delete_plan['data']['skipped_destructive_candidates'][0]['blocked_reason'] ?? '', 'media inventory fix plan requires explicit parentless nonproduction-media opt-in for destructive media deletes' );
$media_parentless_delete_plan = $core_read_package->build_media_inventory_fix_plan(
	array(
		'attachment_ids'              => array( 79 ),
		'issue_types'                 => array( 'possibly_unattached' ),
		'include_delete_candidates'   => true,
		'include_trash_parent_media'  => true,
	)
);
npcink_abilities_toolkit_assert_same( 0, count( (array) ( $media_parentless_delete_plan['data']['write_actions'] ?? array() ) ), 'media inventory fix plan does not map parentless media to delete actions' );
npcink_abilities_toolkit_assert_same( 'unattached_nonproduction_media_not_enabled', $media_parentless_delete_plan['data']['skipped_destructive_candidates'][0]['blocked_reason'] ?? '', 'media inventory fix plan requires explicit unattached nonproduction-media opt-in for parentless destructive media deletes' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][96] = (object) array(
	'ID'             => 96,
	'post_title'     => 'Playwright Native Media ALT 1776483949900',
	'post_status'    => 'inherit',
	'post_type'      => 'attachment',
	'post_excerpt'   => '',
	'post_content'   => '',
	'post_name'      => 'playwright-native-media-alt-1776483949900',
	'post_author'    => 7,
	'post_parent'    => 0,
	'post_mime_type' => 'image/png',
);
$media_parentless_test_delete_plan = $core_read_package->build_media_inventory_fix_plan(
	array(
		'attachment_ids'                 => array( 96 ),
		'issue_types'                    => array( 'possibly_unattached' ),
		'include_delete_candidates'      => true,
		'include_unattached_nonproduction_media'  => true,
	)
);
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/delete-media-permanently', $media_parentless_test_delete_plan['data']['write_actions'][0]['target_ability_id'] ?? '', 'media inventory fix plan maps eligible parentless nonproduction media to delete action only with explicit opt-in' );
npcink_abilities_toolkit_assert_same( 'high', $media_parentless_test_delete_plan['data']['write_actions'][0]['risk'] ?? '', 'eligible parentless nonproduction media delete candidate is marked high risk' );
npcink_abilities_toolkit_assert_same( false, $media_parentless_test_delete_plan['data']['write_actions'][0]['commit_execution'] ?? null, 'eligible parentless nonproduction media delete candidate remains proposal-only' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][97] = (object) array(
	'ID'             => 97,
	'post_title'     => 'Content Assistant Test Image 1776483949901',
	'post_status'    => 'inherit',
	'post_type'      => 'attachment',
	'post_excerpt'   => '',
	'post_content'   => '',
	'post_name'      => 'content-assistant-test-image-1776483949901',
	'post_author'    => 7,
	'post_parent'    => 0,
	'post_mime_type' => 'image/png',
);
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][98] = (object) array(
	'ID'           => 98,
	'post_title'   => 'Editorial Draft With Parentless Test Media',
	'post_status'  => 'draft',
	'post_type'    => 'post',
	'post_content' => '<!-- wp:image {"id":97} --><figure class="wp-block-image"><img class="wp-image-97" /></figure><!-- /wp:image -->',
	'post_name'    => 'editorial-draft-with-parentless-nonproduction-media',
	'post_author'  => 7,
	'post_parent'  => 0,
);
$referenced_parentless_test_delete_plan = $core_read_package->build_media_inventory_fix_plan(
	array(
		'attachment_ids'                 => array( 97 ),
		'issue_types'                    => array( 'possibly_unattached' ),
		'include_delete_candidates'      => true,
		'include_unattached_nonproduction_media'  => true,
	)
);
npcink_abilities_toolkit_assert_same( 0, count( (array) ( $referenced_parentless_test_delete_plan['data']['write_actions'] ?? array() ) ), 'media inventory fix plan blocks parentless nonproduction media referenced by live content' );
npcink_abilities_toolkit_assert_same( 'referenced_by_live_content', $referenced_parentless_test_delete_plan['data']['skipped_destructive_candidates'][0]['blocked_reason'] ?? '', 'media inventory fix plan reports parentless live reference policy failure' );
npcink_abilities_toolkit_assert_true( (int) ( $referenced_parentless_test_delete_plan['data']['skipped_destructive_candidates'][0]['policy_checks']['live_reference_count'] ?? 0 ) >= 1, 'media inventory fix plan records parentless live reference count for blocked media delete' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][99] = (object) array(
	'ID'             => 99,
	'post_title'     => 'Production Launch Diagram',
	'post_status'    => 'inherit',
	'post_type'      => 'attachment',
	'post_excerpt'   => '',
	'post_content'   => '',
	'post_name'      => 'production-launch-diagram',
	'post_author'    => 7,
	'post_parent'    => 0,
	'post_mime_type' => 'image/png',
);
$parentless_production_delete_plan = $core_read_package->build_media_inventory_fix_plan(
	array(
		'attachment_ids'                 => array( 99 ),
		'issue_types'                    => array( 'possibly_unattached' ),
		'include_delete_candidates'      => true,
		'include_unattached_nonproduction_media'  => true,
	)
);
npcink_abilities_toolkit_assert_same( 0, count( (array) ( $parentless_production_delete_plan['data']['write_actions'] ?? array() ) ), 'media inventory fix plan blocks parentless media whose title is not nonproduction content' );
npcink_abilities_toolkit_assert_same( 'media_not_nonproduction_content', $parentless_production_delete_plan['data']['skipped_destructive_candidates'][0]['blocked_reason'] ?? '', 'media inventory fix plan reports parentless media nonproduction-pattern policy failure' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][91] = (object) array(
	'ID'           => 91,
	'post_title'   => 'Runtime Smoke Media Parent',
	'post_status'  => 'trash',
	'post_type'    => 'post',
	'post_content' => 'Runtime smoke parent post for media cleanup policy.',
	'post_name'    => 'runtime-smoke-media-parent',
	'post_author'  => 7,
	'post_parent'  => 0,
);
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][92] = (object) array(
	'ID'             => 92,
	'post_title'     => 'Runtime Smoke Media Image',
	'post_status'    => 'inherit',
	'post_type'      => 'attachment',
	'post_excerpt'   => '',
	'post_content'   => '',
	'post_name'      => 'runtime-smoke-media-image',
	'post_author'    => 7,
	'post_parent'    => 91,
	'post_mime_type' => 'image/jpeg',
);
$eligible_media_delete_plan = $core_read_package->build_media_inventory_fix_plan(
	array(
		'attachment_ids'              => array( 92 ),
		'issue_types'                 => array( 'possibly_unattached' ),
		'include_delete_candidates'   => true,
		'include_trash_parent_media'  => true,
	)
);
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/delete-media-permanently', $eligible_media_delete_plan['data']['write_actions'][0]['target_ability_id'] ?? '', 'media inventory fix plan maps eligible trash-parent nonproduction media to delete action' );
npcink_abilities_toolkit_assert_same( 'high', $eligible_media_delete_plan['data']['write_actions'][0]['risk'] ?? '', 'eligible media delete candidate is marked high risk' );
npcink_abilities_toolkit_assert_same( 'trash', $eligible_media_delete_plan['data']['preview'][0]['parent_post_status'] ?? '', 'eligible media delete policy records trashed parent status in preview' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][93] = (object) array(
	'ID'             => 93,
	'post_title'     => 'Production Diagram Image',
	'post_status'    => 'inherit',
	'post_type'      => 'attachment',
	'post_excerpt'   => '',
	'post_content'   => '',
	'post_name'      => 'production-diagram-image',
	'post_author'    => 7,
	'post_parent'    => 91,
	'post_mime_type' => 'image/jpeg',
);
$blocked_media_title_plan = $core_read_package->build_media_inventory_fix_plan(
	array(
		'attachment_ids'              => array( 93 ),
		'issue_types'                 => array( 'possibly_unattached' ),
		'include_delete_candidates'   => true,
		'include_trash_parent_media'  => true,
	)
);
npcink_abilities_toolkit_assert_same( 0, count( (array) ( $blocked_media_title_plan['data']['write_actions'] ?? array() ) ), 'media inventory fix plan blocks trash-parent media whose own title is not nonproduction content' );
npcink_abilities_toolkit_assert_same( 'media_not_nonproduction_content', $blocked_media_title_plan['data']['skipped_destructive_candidates'][0]['blocked_reason'] ?? '', 'media inventory fix plan reports media nonproduction-pattern policy failure' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][94] = (object) array(
	'ID'             => 94,
	'post_title'     => 'Runtime Smoke Referenced Image',
	'post_status'    => 'inherit',
	'post_type'      => 'attachment',
	'post_excerpt'   => '',
	'post_content'   => '',
	'post_name'      => 'runtime-smoke-referenced-image',
	'post_author'    => 7,
	'post_parent'    => 91,
	'post_mime_type' => 'image/jpeg',
);
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][95] = (object) array(
	'ID'           => 95,
	'post_title'   => 'Editorial Draft',
	'post_status'  => 'draft',
	'post_type'    => 'post',
	'post_content' => '<!-- wp:image {"id":94} --><figure class="wp-block-image"><img class="wp-image-94" /></figure><!-- /wp:image -->',
	'post_name'    => 'editorial-draft',
	'post_author'  => 7,
	'post_parent'  => 0,
);
$referenced_media_delete_plan = $core_read_package->build_media_inventory_fix_plan(
	array(
		'attachment_ids'              => array( 94 ),
		'issue_types'                 => array( 'possibly_unattached' ),
		'include_delete_candidates'   => true,
		'include_trash_parent_media'  => true,
	)
);
npcink_abilities_toolkit_assert_same( 0, count( (array) ( $referenced_media_delete_plan['data']['write_actions'] ?? array() ) ), 'media inventory fix plan blocks trash-parent media referenced by live content' );
npcink_abilities_toolkit_assert_same( 'referenced_by_live_content', $referenced_media_delete_plan['data']['skipped_destructive_candidates'][0]['blocked_reason'] ?? '', 'media inventory fix plan reports live reference policy failure' );
npcink_abilities_toolkit_assert_true( (int) ( $referenced_media_delete_plan['data']['skipped_destructive_candidates'][0]['policy_checks']['live_reference_count'] ?? 0 ) >= 1, 'media inventory fix plan records live reference count for blocked media delete' );
$seo_geo_readiness = $core_read_package->get_post_seo_geo_readiness(
	array(
		'post_id'       => 77,
		'focus_keyword' => 'workflow',
	)
);
npcink_abilities_toolkit_assert_same( true, $seo_geo_readiness['success'] ?? null, 'get-post-seo-geo-readiness returns a success envelope' );
npcink_abilities_toolkit_assert_same( 77, $seo_geo_readiness['data']['post']['post_id'] ?? null, 'get-post-seo-geo-readiness keeps post id' );
npcink_abilities_toolkit_assert_true( isset( $seo_geo_readiness['data']['readiness_score'] ), 'get-post-seo-geo-readiness returns a readiness score' );
$topic_coverage = $core_read_package->get_site_topic_coverage_report(
	array(
		'post_type'  => 'post',
		'status'     => 'any',
		'per_page'   => 5,
		'topic_seed' => 'workflow',
	)
);
npcink_abilities_toolkit_assert_same( true, $topic_coverage['success'] ?? null, 'get-site-topic-coverage-report returns a success envelope' );
npcink_abilities_toolkit_assert_true( (int) ( $topic_coverage['data']['summary']['scanned_count'] ?? 0 ) >= 1, 'get-site-topic-coverage-report scans local posts' );
npcink_abilities_toolkit_assert_true( ! empty( $topic_coverage['data']['topics'] ), 'get-site-topic-coverage-report returns topic rows' );
$GLOBALS['npcink_abilities_toolkit_unit_terms'] = array(
	'category' => array(
		(object) array(
			'term_id'     => 301,
			'name'        => 'Workflow',
			'slug'        => 'workflow',
			'description' => '',
			'count'       => 0,
			'parent'      => 0,
		),
		(object) array(
			'term_id'     => 302,
			'name'        => 'Operations',
			'slug'        => 'operations',
			'description' => 'Operational notes.',
			'count'       => 3,
			'parent'      => 0,
		),
	),
	'post_tag' => array(
		(object) array(
			'term_id'     => 401,
			'name'        => 'AI Workflow',
			'slug'        => 'ai-workflow',
			'description' => '',
			'count'       => 0,
			'parent'      => 0,
		),
		(object) array(
			'term_id'     => 402,
			'name'        => 'AI workflow',
			'slug'        => 'ai-workflow-2',
			'description' => '',
			'count'       => 2,
			'parent'      => 0,
		),
	),
);
$taxonomy_health = $core_read_package->get_taxonomy_inventory_health(
	array(
		'taxonomy' => 'category',
		'per_page' => 5,
	)
);
npcink_abilities_toolkit_assert_same( true, $taxonomy_health['success'] ?? null, 'get-taxonomy-inventory-health returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'category', $taxonomy_health['data']['taxonomy'] ?? '', 'get-taxonomy-inventory-health keeps taxonomy name' );
npcink_abilities_toolkit_assert_true( isset( $taxonomy_health['data']['issue_counts']['missing_description'] ), 'get-taxonomy-inventory-health counts missing descriptions' );
npcink_abilities_toolkit_assert_true( isset( $taxonomy_health['data']['issue_counts']['unused_term'] ), 'get-taxonomy-inventory-health counts unused terms' );
$taxonomy_consolidation = $core_read_package->get_taxonomy_consolidation_suggestions(
	array(
		'taxonomy' => 'post_tag',
		'per_page' => 10,
	)
);
npcink_abilities_toolkit_assert_same( true, $taxonomy_consolidation['success'] ?? null, 'get-taxonomy-consolidation-suggestions returns a success envelope' );
npcink_abilities_toolkit_assert_true( (int) ( $taxonomy_consolidation['data']['summary']['suggestion_count'] ?? 0 ) >= 1, 'get-taxonomy-consolidation-suggestions returns suggestions' );
npcink_abilities_toolkit_assert_same( 'duplicate_or_near_duplicate', $taxonomy_consolidation['data']['suggestions'][1]['type'] ?? '', 'get-taxonomy-consolidation-suggestions detects duplicate term groups' );
$post_taxonomy_suggestions = $core_read_package->suggest_post_taxonomy_terms(
	array(
		'taxonomy'              => 'both',
		'title'                 => 'AI Workflow planning guide',
		'excerpt'               => 'A workflow operations guide for editorial teams.',
		'query'                 => 'AI workflow operations',
		'related_term_evidence' => array(
			array(
				'term_id'         => 401,
				'taxonomy'        => 'post_tag',
				'name'            => 'AI Workflow',
				'source_count'    => 1,
				'source_post_ids' => array( 77 ),
				'source_titles'   => array( 'Related AI workflow case study' ),
				'source_refs'     => array( 'site_knowledge:77' ),
				'max_similarity'  => 0.91,
			),
		),
	)
);
npcink_abilities_toolkit_assert_same( true, $post_taxonomy_suggestions['success'] ?? null, 'suggest-post-taxonomy-terms returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'article_taxonomy_suggestions.v1', $post_taxonomy_suggestions['data']['artifact_type'] ?? '', 'suggest-post-taxonomy-terms returns article taxonomy suggestions' );
npcink_abilities_toolkit_assert_same( 'suggestion_only', $post_taxonomy_suggestions['data']['write_posture'] ?? '', 'suggest-post-taxonomy-terms stays suggestion-only' );
npcink_abilities_toolkit_assert_same( 'core_proposal_required', $post_taxonomy_suggestions['data']['final_write_path'] ?? '', 'suggest-post-taxonomy-terms requires Core proposal for writes' );
npcink_abilities_toolkit_assert_same( 'AI Workflow', $post_taxonomy_suggestions['data']['tag_candidates'][0]['name'] ?? '', 'suggest-post-taxonomy-terms ranks matching existing tags' );
$taxonomy_recommendation_labels = array_map(
	static function ( array $candidate ): string {
		return (string) ( $candidate['label'] ?? '' );
	},
	is_array( $post_taxonomy_suggestions['data']['recommendation_candidates'] ?? null ) ? $post_taxonomy_suggestions['data']['recommendation_candidates'] : array()
);
npcink_abilities_toolkit_assert_true( in_array( 'Existing tag', $taxonomy_recommendation_labels, true ), 'suggest-post-taxonomy-terms exposes recommendation candidate labels' );
npcink_abilities_toolkit_assert_true( in_array( 'related_site_knowledge_term', $post_taxonomy_suggestions['data']['tag_candidates'][0]['match_signals'] ?? array(), true ), 'suggest-post-taxonomy-terms keeps related evidence as ranking signal' );
npcink_abilities_toolkit_assert_same( true, $post_taxonomy_suggestions['data']['selection_policy']['new_terms_deferred'] ?? null, 'suggest-post-taxonomy-terms defers new term creation' );
$taxonomy_tag_review_set = $core_read_package->build_taxonomy_tag_review_set(
	array(
		'taxonomy'              => 'both',
		'title'                 => 'AI Workflow planning guide',
		'excerpt'               => 'A workflow operations guide for editorial teams.',
		'query'                 => 'AI workflow operations',
		'review_set_limit'      => 1,
		'related_term_evidence' => array(
			array(
				'term_id'         => 401,
				'taxonomy'        => 'post_tag',
				'name'            => 'AI Workflow',
				'source_count'    => 1,
				'source_post_ids' => array( 77 ),
				'source_titles'   => array( 'Related AI workflow case study' ),
				'source_refs'     => array( 'site_knowledge:77' ),
				'max_similarity'  => 0.91,
			),
		),
	)
);
npcink_abilities_toolkit_assert_same( true, $taxonomy_tag_review_set['success'] ?? null, 'build-taxonomy-tag-review-set returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'taxonomy_tag_review_set.v1', $taxonomy_tag_review_set['data']['contract_version'] ?? '', 'taxonomy/tag review set declares the reusable contract' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/build-taxonomy-tag-review-set', $taxonomy_tag_review_set['data']['source_ability_id'] ?? '', 'taxonomy/tag review set records Toolkit source ability' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/suggest-post-taxonomy-terms', $taxonomy_tag_review_set['data']['source_candidate_ability_id'] ?? '', 'taxonomy/tag review set records the source candidate ranker' );
npcink_abilities_toolkit_assert_same( false, $taxonomy_tag_review_set['data']['direct_wordpress_write'] ?? null, 'taxonomy/tag review set does not directly write WordPress' );
npcink_abilities_toolkit_assert_same( false, $taxonomy_tag_review_set['data']['proposal_created'] ?? null, 'taxonomy/tag review set does not create proposals' );
npcink_abilities_toolkit_assert_same( false, $taxonomy_tag_review_set['data']['safety']['term_creation_allowed'] ?? null, 'taxonomy/tag review set does not authorize term creation' );
npcink_abilities_toolkit_assert_same( false, $taxonomy_tag_review_set['data']['safety']['term_assignment_allowed'] ?? null, 'taxonomy/tag review set does not authorize term assignment' );
npcink_abilities_toolkit_assert_same( 'AI Workflow', $taxonomy_tag_review_set['data']['selected_items'][0]['name'] ?? '', 'taxonomy/tag review set selects the strongest existing term' );
npcink_abilities_toolkit_assert_same( true, $taxonomy_tag_review_set['data']['selected_items'][0]['needs_operator_review'] ?? null, 'taxonomy/tag review set requires operator review' );
$taxonomy_tag_blocked_reasons = array_values( array_map( 'strval', array_column( $taxonomy_tag_review_set['data']['blocked_items'] ?? array(), 'blocked_reason' ) ) );
npcink_abilities_toolkit_assert_true( in_array( 'review_set_limit_reached', $taxonomy_tag_blocked_reasons, true ), 'taxonomy/tag review set reports over-limit rows as blocked' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/build-content-metadata-apply-plan', $taxonomy_tag_review_set['data']['handoff']['accepted_selection_target'] ?? '', 'taxonomy/tag review set points accepted selections to the content metadata apply planner' );
$GLOBALS['npcink_abilities_toolkit_unit_post_terms'][77]['post_tag'] = array(
	(object) array(
		'term_id' => 401,
		'name'    => 'AI Workflow',
		'slug'    => 'ai-workflow',
		'count'   => 0,
	),
);
$post_taxonomy_proposal = $core_read_package->propose_post_taxonomy_terms(
	array(
		'post_id'            => 77,
		'taxonomy'           => 'post_tag',
		'mode'               => 'append',
		'candidate_terms'    => array( 'AI workflow', 'Unknown Topic' ),
		'candidate_term_ids' => array( 402 ),
	)
);
npcink_abilities_toolkit_assert_same( true, $post_taxonomy_proposal['success'] ?? null, 'propose-post-taxonomy-terms returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/set-post-terms', $post_taxonomy_proposal['data']['proposal']['target_ability_id'] ?? '', 'post taxonomy proposal targets set-post-terms' );
npcink_abilities_toolkit_assert_same( false, $post_taxonomy_proposal['data']['proposal']['commit_execution'] ?? null, 'post taxonomy proposal does not execute commits' );
npcink_abilities_toolkit_assert_same( array( 401, 402 ), $post_taxonomy_proposal['data']['proposed_term_ids'] ?? array(), 'post taxonomy proposal computes proposed terms from current and matched candidates' );
npcink_abilities_toolkit_assert_same( 'Unknown Topic', $post_taxonomy_proposal['data']['unmatched_terms'][0]['value'] ?? '', 'post taxonomy proposal reports unmatched term names' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][81] = (object) array(
	'ID'           => 81,
	'post_title'   => 'Landing Page',
	'post_status'  => 'publish',
	'post_type'    => 'page',
	'post_excerpt' => '',
	'post_content' => '<p>Short landing page.</p>',
	'post_name'    => 'landing-page',
	'post_author'  => 7,
);
$page_structure = $core_read_package->get_page_structure_health(
	array(
		'page_id' => 81,
	)
);
npcink_abilities_toolkit_assert_same( true, $page_structure['success'] ?? null, 'get-page-structure-health returns a success envelope' );
npcink_abilities_toolkit_assert_same( 1, $page_structure['data']['summary']['pages_with_issues'] ?? null, 'get-page-structure-health counts pages with issues' );
npcink_abilities_toolkit_assert_true( in_array( 'missing_cta', $page_structure['data']['items'][0]['issues'] ?? array(), true ), 'get-page-structure-health detects missing CTA' );
$seo_geo_gap = $core_read_package->get_seo_geo_gap_report(
	array(
		'post_type'  => 'post',
		'status'     => 'any',
		'per_page'   => 5,
		'topic_seed' => 'workflow',
	)
);
npcink_abilities_toolkit_assert_same( true, $seo_geo_gap['success'] ?? null, 'get-seo-geo-gap-report returns a success envelope' );
npcink_abilities_toolkit_assert_true( (int) ( $seo_geo_gap['data']['summary']['gap_count'] ?? 0 ) >= 1, 'get-seo-geo-gap-report reports gaps from refresh and coverage scans' );
$seo_geo_gap_cached = $core_read_package->get_seo_geo_gap_report(
	array(
		'post_type'  => 'post',
		'status'     => 'any',
		'per_page'   => 5,
		'topic_seed' => 'workflow',
	)
);
npcink_abilities_toolkit_assert_same( true, $seo_geo_gap_cached['meta']['cache_hit'] ?? null, 'get-seo-geo-gap-report uses the bounded read cache on repeated calls' );
$site_style_baseline = $core_read_package->get_site_style_baseline(
	array(
		'mode'  => 'site_recent',
		'limit' => 3,
	)
);
npcink_abilities_toolkit_assert_same( true, $site_style_baseline['success'] ?? null, 'get-site-style-baseline returns a success envelope' );
npcink_abilities_toolkit_assert_true( isset( $site_style_baseline['data']['profile'] ), 'get-site-style-baseline returns a profile payload' );
$workflow_context = $core_read_package->build_article_workflow_context(
	array(
		'workflow'   => 'publish',
		'post_id'    => 77,
		'topic_seed' => 'workflow',
	)
);
npcink_abilities_toolkit_assert_same( true, $workflow_context['success'] ?? null, 'build-article-workflow-context returns a success envelope' );
npcink_abilities_toolkit_assert_true( in_array( 'post_context', $workflow_context['data']['sections'] ?? array(), true ), 'build-article-workflow-context includes post context when post_id is provided' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][82] = (object) array(
	'ID'            => 82,
	'post_title'    => 'Scheduled Workflow Post',
	'post_status'   => 'future',
	'post_type'     => 'post',
	'post_excerpt'  => '',
	'post_content'  => 'Scheduled workflow content.',
	'post_name'     => 'scheduled-workflow-post',
	'post_author'   => 7,
	'post_date'     => '2030-01-02 03:04:05',
	'post_modified' => '2030-01-01 03:04:05',
);
$publishing_calendar = $core_read_package->get_publishing_calendar_context(
	array(
		'post_type'   => 'post',
		'window_days' => 365,
		'per_page'    => 5,
	)
);
npcink_abilities_toolkit_assert_same( true, $publishing_calendar['success'] ?? null, 'get-publishing-calendar-context returns a success envelope' );
npcink_abilities_toolkit_assert_true( isset( $publishing_calendar['data']['status_counts']['future'] ), 'get-publishing-calendar-context returns status counts' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][771] = (object) array(
	'ID'                => 771,
	'post_title'        => 'Previous Optimization Context',
	'post_status'       => 'inherit',
	'post_type'         => 'revision',
	'post_excerpt'      => '',
	'post_content'      => '<!-- wp:paragraph --><p>Previous optimization context content with more detail.</p><!-- /wp:paragraph -->',
	'post_name'         => '77-revision-v1',
	'post_author'       => 7,
	'post_parent'       => 77,
	'post_modified'     => '2024-01-03 03:04:05',
	'post_modified_gmt' => '2024-01-03 03:04:05',
);
$revision_risk = $core_read_package->get_revision_change_risk_report(
	array(
		'post_id'       => 77,
		'max_revisions' => 5,
	)
);
npcink_abilities_toolkit_assert_same( true, $revision_risk['success'] ?? null, 'get-revision-change-risk-report returns a success envelope' );
npcink_abilities_toolkit_assert_same( 77, $revision_risk['data']['post']['post_id'] ?? null, 'get-revision-change-risk-report keeps post id' );
npcink_abilities_toolkit_assert_true( in_array( 'title_changed', $revision_risk['data']['risk_flags'] ?? array(), true ), 'get-revision-change-risk-report detects title changes' );
$single_suggest = $core_read_package->build_article_single_optimization_suggest(
	array(
		'post'              => array(
			'id'         => 77,
			'title'      => 'Optimization Context Post',
			'excerpt'    => '',
			'slug'       => 'optimization-context-post',
			'content'    => '本文介绍 WordPress optimization workflow 的使用方式。',
			'categories' => array( 'Workflows' ),
			'tags'       => array( 'SEO' ),
			'seo'        => array(
				'title'       => '',
				'description' => '',
			),
		),
		'generated_excerpt' => array(
			'proposal_text' => 'Optimization workflow excerpt suggestion.',
		),
		'generated_seo'     => array(
			'meta_title'       => 'Optimization SEO Title',
			'meta_description' => 'Optimization SEO Description',
		),
		'seo_analysis'      => array(
			'recommendations' => array(
				array(
					'type'     => 'keyword',
					'priority' => 'high',
					'title'    => '补齐焦点关键词',
					'detail'   => '在首屏补齐焦点关键词。',
				),
			),
		),
		'geo_analysis'      => array(
			'recommendations' => array(
				array(
					'type'     => 'faq_candidate',
					'priority' => 'medium',
					'title'    => '补 FAQ',
					'detail'   => '增加问答块。',
				),
			),
		),
		'focus_keyword'     => 'canonical seo',
		'keywords'          => array( 'canonical seo', 'workflow' ),
	)
);
npcink_abilities_toolkit_assert_same( true, $single_suggest['success'] ?? null, 'build-article-single-optimization-suggest returns a success envelope' );
npcink_abilities_toolkit_assert_same( array( 'excerpt', 'seo_title', 'seo_description', 'slug' ), $single_suggest['data']['summary']['safe_apply_fields'] ?? array(), 'build-article-single-optimization-suggest keeps low-risk safe apply fields' );
npcink_abilities_toolkit_assert_true( ! empty( $single_suggest['data']['content_improvements'] ), 'build-article-single-optimization-suggest emits content improvements' );
npcink_abilities_toolkit_assert_true( ! empty( $single_suggest['data']['seo_improvements'] ), 'build-article-single-optimization-suggest emits SEO improvements' );
npcink_abilities_toolkit_assert_true( ! empty( $single_suggest['data']['geo_improvements'] ), 'build-article-single-optimization-suggest emits GEO improvements' );
$apply_plan = $core_read_package->build_article_optimization_apply_plan(
	array(
		'post'              => array(
			'id'      => 77,
			'title'   => 'Optimization Context Post',
			'status'  => 'draft',
			'excerpt' => 'Current excerpt.',
		),
		'report'            => array(
			'summary' => array(
				'status'                => 'needs_attention',
				'high_priority_count'   => 1,
				'total_recommendations' => 3,
			),
			'geo'     => array(
				'summary' => array(
					'faq_candidate_count' => 2,
				),
			),
		),
		'optimization_plan' => array(
			'excerpt_mode' => 'apply',
			'seo_mode'     => 'suggest',
		),
		'generated_excerpt' => array(
			'proposal_text' => 'Generated excerpt.',
		),
	)
);
npcink_abilities_toolkit_assert_same( true, $apply_plan['success'] ?? null, 'build-article-optimization-apply-plan returns a success envelope' );
npcink_abilities_toolkit_assert_same( true, $apply_plan['data']['actions']['excerpt']['apply_generate'] ?? null, 'build-article-optimization-apply-plan marks generated excerpt as safe apply when explicitly requested' );
npcink_abilities_toolkit_assert_same( array( 'update_excerpt' ), $apply_plan['data']['summary']['safe_apply_supported'] ?? array(), 'build-article-optimization-apply-plan exposes safe apply action summary' );
npcink_abilities_toolkit_assert_same( 'article_optimization_apply_plan', $apply_plan['data']['artifact_type'] ?? '', 'build-article-optimization-apply-plan declares a Core-ready artifact type' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/recipes/article-optimization', $apply_plan['data']['source_recipe_ref'] ?? '', 'build-article-optimization-apply-plan carries the article optimization recipe ref' );
npcink_abilities_toolkit_assert_same( true, $apply_plan['data']['requires_approval'] ?? null, 'build-article-optimization-apply-plan requires host approval' );
npcink_abilities_toolkit_assert_same( true, $apply_plan['data']['dry_run'] ?? null, 'build-article-optimization-apply-plan is dry-run only' );
npcink_abilities_toolkit_assert_same( false, $apply_plan['data']['commit_execution'] ?? null, 'build-article-optimization-apply-plan does not execute commits' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/build-article-optimization-apply-plan', $apply_plan['data']['handoff']['plan_ability_id'] ?? '', 'build-article-optimization-apply-plan identifies itself for Core from-plan intake' );
$apply_plan_write_actions = is_array( $apply_plan['data']['write_actions'] ?? null ) ? $apply_plan['data']['write_actions'] : array();
npcink_abilities_toolkit_assert_same( 1, count( $apply_plan_write_actions ), 'build-article-optimization-apply-plan emits one safe excerpt write action when requested' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/update-post', $apply_plan_write_actions[0]['target_ability_id'] ?? '', 'build-article-optimization-apply-plan targets update-post for excerpt changes' );
npcink_abilities_toolkit_assert_same( 77, $apply_plan_write_actions[0]['input']['post_id'] ?? null, 'build-article-optimization-apply-plan includes the target post id' );
npcink_abilities_toolkit_assert_same( 'Generated excerpt.', $apply_plan_write_actions[0]['input']['excerpt'] ?? '', 'build-article-optimization-apply-plan includes the reviewed excerpt' );
npcink_abilities_toolkit_assert_same( true, $apply_plan_write_actions[0]['input']['dry_run'] ?? null, 'build-article-optimization-apply-plan write action is dry-run' );
npcink_abilities_toolkit_assert_same( false, $apply_plan_write_actions[0]['input']['commit'] ?? null, 'build-article-optimization-apply-plan write action does not request commit' );
$content_metadata_apply_plan = $core_read_package->build_content_metadata_apply_plan(
	array(
		'post_id'                => 77,
		'excerpt'                => 'Reviewed excerpt for Core proposal.',
		'category_ids'           => array( 3, 5, 5 ),
		'tag_ids'                => array( 8, 13 ),
		'category_mode'          => 'replace',
		'tag_mode'               => 'append',
		'evidence_refs'          => array( 'content-metadata-delta:excerpt', 'content-metadata-delta:taxonomy' ),
		'content_metadata_delta' => array(
			'source' => 'unit-test',
		),
		'new_term_candidates'    => array(
			array(
				'taxonomy' => 'post_tag',
				'name'     => 'Deferred vocabulary gap',
			),
		),
	)
);
npcink_abilities_toolkit_assert_same( true, $content_metadata_apply_plan['success'] ?? null, 'build-content-metadata-apply-plan returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'content_metadata_apply_plan', $content_metadata_apply_plan['data']['artifact_type'] ?? '', 'build-content-metadata-apply-plan declares a Core-ready artifact type' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit', $content_metadata_apply_plan['data']['source_recipe_provider'] ?? '', 'build-content-metadata-apply-plan is owned by Toolkit' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/build-content-metadata-apply-plan', $content_metadata_apply_plan['data']['handoff']['plan_ability_id'] ?? '', 'build-content-metadata-apply-plan identifies the Toolkit plan ability for Core from-plan intake' );
npcink_abilities_toolkit_assert_same( true, $content_metadata_apply_plan['data']['requires_approval'] ?? null, 'build-content-metadata-apply-plan requires host approval' );
npcink_abilities_toolkit_assert_same( true, $content_metadata_apply_plan['data']['dry_run'] ?? null, 'build-content-metadata-apply-plan is dry-run only' );
npcink_abilities_toolkit_assert_same( false, $content_metadata_apply_plan['data']['commit_execution'] ?? null, 'build-content-metadata-apply-plan does not execute commits' );
npcink_abilities_toolkit_assert_same( 'core_proposal_required', $content_metadata_apply_plan['data']['authorization']['classification'] ?? '', 'build-content-metadata-apply-plan carries proposal-required classification evidence' );
npcink_abilities_toolkit_assert_same( array( 3, 5 ), $content_metadata_apply_plan['data']['accepted_choices']['category_ids'] ?? array(), 'build-content-metadata-apply-plan normalizes selected category ids' );
npcink_abilities_toolkit_assert_same( 'manual_review_only_no_create_term_action', $content_metadata_apply_plan['data']['accepted_choices']['new_term_policy'] ?? '', 'build-content-metadata-apply-plan preserves new terms as review-only notes' );
$content_metadata_write_actions = is_array( $content_metadata_apply_plan['data']['write_actions'] ?? null ) ? $content_metadata_apply_plan['data']['write_actions'] : array();
npcink_abilities_toolkit_assert_same( 3, count( $content_metadata_write_actions ), 'build-content-metadata-apply-plan emits excerpt, category, and tag proposal actions' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/update-post', $content_metadata_write_actions[0]['target_ability_id'] ?? '', 'build-content-metadata-apply-plan targets update-post for excerpt changes' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/set-post-terms', $content_metadata_write_actions[1]['target_ability_id'] ?? '', 'build-content-metadata-apply-plan targets set-post-terms for category changes' );
npcink_abilities_toolkit_assert_same( false, $content_metadata_write_actions[1]['input']['create_missing'] ?? null, 'build-content-metadata-apply-plan never creates missing category terms' );
npcink_abilities_toolkit_assert_same( true, $content_metadata_write_actions[1]['input']['dry_run'] ?? null, 'build-content-metadata-apply-plan category action is dry-run' );
npcink_abilities_toolkit_assert_same( false, $content_metadata_write_actions[1]['input']['commit'] ?? null, 'build-content-metadata-apply-plan category action does not request commit' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/set-post-terms', $content_metadata_write_actions[2]['target_ability_id'] ?? '', 'build-content-metadata-apply-plan targets set-post-terms for tag changes' );
npcink_abilities_toolkit_assert_same( false, $content_metadata_write_actions[2]['input']['create_missing'] ?? null, 'build-content-metadata-apply-plan never creates missing tag terms' );
npcink_abilities_toolkit_assert_same( 1, count( $content_metadata_apply_plan['data']['manual_review'] ?? array() ), 'build-content-metadata-apply-plan records new-term candidates as manual review only' );
$article_audio_adoption_plan = $core_read_package->build_article_audio_adoption_plan(
	array(
		'post_id'              => 501,
		'candidate_type'       => 'article_audio_summary',
		'audio_url'            => 'https://cloud.example.test/audio/article-summary.mp3',
		'source_content_hash'  => 'sha256:article-source',
		'source_word_count'    => 1234,
		'source_generated_at'  => '2026-06-24T09:30:00Z',
		'import_media'         => true,
		'media_file_name'      => 'reviewed-audio-summary',
		'audio_candidate'      => array(
			'title'            => 'Reviewed audio summary',
			'duration_seconds' => 42.5,
			'mime_type'        => 'audio/mpeg',
			'provider'         => 'minimax',
			'model'            => 'speech-2.8-turbo',
			'trace_id'         => 'trace_audio_123',
		),
	)
);
npcink_abilities_toolkit_assert_same( true, $article_audio_adoption_plan['success'] ?? null, 'build-article-audio-adoption-plan returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'article_audio_adoption_plan.v1', $article_audio_adoption_plan['data']['artifact_type'] ?? '', 'article audio adoption plan declares the versioned artifact type' );
npcink_abilities_toolkit_assert_same( false, $article_audio_adoption_plan['data']['direct_wordpress_write'] ?? null, 'article audio adoption plan does not directly write WordPress' );
npcink_abilities_toolkit_assert_same( false, $article_audio_adoption_plan['data']['commit_execution'] ?? null, 'article audio adoption plan keeps commit execution disabled' );
npcink_abilities_toolkit_assert_same( true, $article_audio_adoption_plan['meta']['readonly'] ?? null, 'article audio adoption plan remains read-only' );
$article_audio_write_actions = is_array( $article_audio_adoption_plan['data']['write_actions'] ?? null ) ? $article_audio_adoption_plan['data']['write_actions'] : array();
npcink_abilities_toolkit_assert_same( 1, count( $article_audio_write_actions ), 'article audio adoption plan emits one post-meta write action' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/adopt-article-audio', $article_audio_write_actions[0]['target_ability_id'] ?? '', 'article audio adoption plan targets the governed audio adoption ability' );
npcink_abilities_toolkit_assert_same( 'low', $article_audio_write_actions[0]['risk'] ?? '', 'article audio adoption action is low risk' );
npcink_abilities_toolkit_assert_same( true, $article_audio_write_actions[0]['requires_approval'] ?? null, 'article audio adoption action still requires governance approval' );
npcink_abilities_toolkit_assert_same( 501, $article_audio_write_actions[0]['input']['post_id'] ?? 0, 'article audio adoption action includes the target post id' );
npcink_abilities_toolkit_assert_same( 'https://cloud.example.test/audio/article-summary.mp3', $article_audio_write_actions[0]['input']['audio_url'] ?? '', 'article audio adoption action includes the reviewed audio url' );
npcink_abilities_toolkit_assert_same( 'article_audio_summary', $article_audio_write_actions[0]['input']['audio_kind'] ?? '', 'article audio adoption action preserves the candidate type' );
npcink_abilities_toolkit_assert_same( true, $article_audio_write_actions[0]['input']['import_media'] ?? null, 'article audio adoption action carries the local media import decision' );
npcink_abilities_toolkit_assert_same( 'reviewed-audio-summary', $article_audio_write_actions[0]['input']['media_file_name'] ?? '', 'article audio adoption action carries the reviewed audio file name' );
npcink_abilities_toolkit_assert_same( true, $article_audio_write_actions[0]['input']['dry_run'] ?? null, 'article audio adoption action is dry-run' );
npcink_abilities_toolkit_assert_same( false, $article_audio_write_actions[0]['input']['commit'] ?? null, 'article audio adoption action does not request commit' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/build-article-audio-adoption-plan', $article_audio_adoption_plan['data']['handoff']['plan_ability_id'] ?? '', 'article audio adoption plan identifies the Toolkit Core handoff ability' );
$article_block_plan = $core_read_package->build_article_block_plan(
	array(
		'title'              => 'Gutenberg Article Draft',
		'article_template'   => 'comparison-review',
		'responsive_profile' => 'article_standard',
		'media_strategy'     => 'existing_media_url',
		'variables'          => array(
			'dek'            => '用 Gutenberg 原生模块组织文章，让编辑、审查和移动端阅读都更稳定。',
			'intro'          => '文章计划应该和页面 Pattern 分开处理，重点放在语义结构和可编辑性。',
			'hero_media_url' => 'https://example.test/wp-content/uploads/2026/06/article-hero.jpg',
			'hero_media_attachment_id' => 9053,
			'hero_media_alt' => 'Article hero preview',
			'takeaways'      => array(
				'文章使用核心块，不依赖自定义 CSS。',
				'对比区使用 columns 并在移动端堆叠。',
				'FAQ 使用 details 块，编辑器可以继续维护。',
			),
			'sections'       => array(
				array(
					'title'      => '文章块红利',
					'paragraphs' => array( 'Gutenberg 让文章从纯 HTML 变成可回读、可编辑的内容结构。' ),
					'bullets'    => array( '标题层级', '要点列表', '图片和 FAQ' ),
				),
				array(
					'title'      => '治理路径',
					'paragraphs' => array( 'AI 只生成计划，写入仍然经过 proposal 审批。' ),
				),
				array(
					'title'      => '响应式验收',
					'paragraphs' => array( '移动端重点检查图片、对比区和 FAQ 是否正常换行。' ),
				),
			),
			'comparisons'    => array(
				array(
					'title'       => '纯 HTML',
					'description' => '短期自由，但编辑器维护成本更高。',
				),
				array(
					'title'       => 'Gutenberg blocks',
					'description' => '结构更稳定，也更适合审查和二次编辑。',
				),
			),
			'faq'            => array(
				array(
					'title'       => '会直接发布吗？',
					'description' => '不会，只创建 draft proposal。',
				),
				array(
					'title'       => '能继续编辑吗？',
					'description' => '可以，内容由核心块组成。',
				),
			),
		),
	)
);
npcink_abilities_toolkit_assert_same( true, $article_block_plan['success'] ?? null, 'build-article-block-plan returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'article_block_plan', $article_block_plan['data']['artifact_type'] ?? '', 'build-article-block-plan declares an article block artifact type' );
npcink_abilities_toolkit_assert_same( 'comparison-review', $article_block_plan['data']['article_template'] ?? '', 'build-article-block-plan preserves the article template' );
npcink_abilities_toolkit_assert_same( 'article_standard', $article_block_plan['data']['responsive_profile'] ?? '', 'build-article-block-plan preserves the responsive profile' );
npcink_abilities_toolkit_assert_same( 'existing_media_url', $article_block_plan['data']['media_strategy'] ?? '', 'build-article-block-plan preserves media strategy' );
npcink_abilities_toolkit_assert_same( 'post_content', $article_block_plan['data']['block_editor_surface']['surface_kind'] ?? '', 'build-article-block-plan declares a post content block-editor surface' );
npcink_abilities_toolkit_assert_same( 'block_editor', $article_block_plan['data']['block_editor_surface']['editor'] ?? '', 'build-article-block-plan declares the block editor surface owner' );
npcink_abilities_toolkit_assert_same( 'post', $article_block_plan['data']['block_editor_surface']['post_type'] ?? '', 'build-article-block-plan declares the article post type surface' );
npcink_abilities_toolkit_assert_same( 'create_draft', $article_block_plan['data']['block_editor_surface']['target_mode'] ?? '', 'build-article-block-plan reports create draft surface mode by default' );
npcink_abilities_toolkit_assert_same( false, $article_block_plan['data']['direct_wordpress_write'] ?? null, 'build-article-block-plan does not directly write WordPress' );
npcink_abilities_toolkit_assert_same( false, $article_block_plan['data']['commit_execution'] ?? null, 'build-article-block-plan keeps commit execution disabled' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/build-article-block-plan', $article_block_plan['data']['handoff']['plan_ability_id'] ?? '', 'build-article-block-plan identifies itself for Core from-plan intake' );
npcink_abilities_toolkit_assert_same( '1.0', $article_block_plan['data']['editorial_quality']['pattern_version'] ?? '', 'build-article-block-plan reports the v1 editorial pattern version' );
npcink_abilities_toolkit_assert_same( 'gutenberg_native_editorial', $article_block_plan['data']['editorial_quality']['style_strategy'] ?? '', 'build-article-block-plan reports editorial Gutenberg-native strategy' );
npcink_abilities_toolkit_assert_same( true, $article_block_plan['data']['editorial_quality']['uses_native_blocks'] ?? null, 'build-article-block-plan reports native block usage' );
npcink_abilities_toolkit_assert_same( true, $article_block_plan['data']['editorial_quality']['has_takeaways'] ?? null, 'build-article-block-plan reports takeaways' );
npcink_abilities_toolkit_assert_same( true, $article_block_plan['data']['editorial_quality']['has_faq'] ?? null, 'build-article-block-plan reports FAQ' );
npcink_abilities_toolkit_assert_same( true, $article_block_plan['data']['editorial_quality']['has_comparison_columns'] ?? null, 'build-article-block-plan reports comparison columns' );
npcink_abilities_toolkit_assert_same( true, $article_block_plan['data']['editorial_quality']['has_hero_media_attachment_id'] ?? null, 'build-article-block-plan reports hero media attachment binding' );
npcink_abilities_toolkit_assert_same( false, $article_block_plan['data']['editorial_quality']['custom_css_required'] ?? true, 'build-article-block-plan reports no custom CSS requirement' );
npcink_abilities_toolkit_assert_same( 'article_standard', $article_block_plan['data']['responsive_quality']['responsive_profile'] ?? '', 'build-article-block-plan reports responsive profile quality' );
npcink_abilities_toolkit_assert_same( true, $article_block_plan['data']['responsive_quality']['uses_core_responsive_blocks'] ?? null, 'build-article-block-plan reports core responsive blocks' );
	npcink_abilities_toolkit_assert_same( true, $article_block_plan['data']['responsive_quality']['uses_mobile_stack'] ?? null, 'build-article-block-plan reports mobile column stacking' );
	npcink_abilities_toolkit_assert_same( true, $article_block_plan['data']['responsive_quality']['has_responsive_media'] ?? null, 'build-article-block-plan reports responsive media' );
	npcink_abilities_toolkit_assert_same( 2, $article_block_plan['data']['responsive_quality']['max_columns_per_row'] ?? 0, 'build-article-block-plan reports bounded comparison columns' );
	npcink_abilities_toolkit_assert_same( 'block_editor_surface_review', $article_block_plan['data']['block_editor_review']['artifact_type'] ?? '', 'build-article-block-plan includes a block-editor self-review excerpt' );
	npcink_abilities_toolkit_assert_same( 'gutenberg_native_v1', $article_block_plan['data']['composition_contract']['catalog_id'] ?? '', 'build-article-block-plan references the Gutenberg block capability catalog' );
	npcink_abilities_toolkit_assert_same( 'bounded_block_composition', $article_block_plan['data']['composition_contract']['composition_model'] ?? '', 'build-article-block-plan uses bounded block composition' );
	npcink_abilities_toolkit_assert_same( 'pass', $article_block_plan['data']['composition_contract']['contract_status'] ?? '', 'build-article-block-plan passes the block composition contract' );
	npcink_abilities_toolkit_assert_same( array(), $article_block_plan['data']['composition_contract']['non_core_blocks'] ?? array( 'unexpected' ), 'build-article-block-plan reports no non-core block contract violations' );
	npcink_abilities_toolkit_assert_true( in_array( 'core/image', $article_block_plan['data']['composition_contract']['used_block_names'] ?? array(), true ), 'build-article-block-plan contract records core image usage' );
	npcink_abilities_toolkit_assert_same( 'gutenberg_native_block_composer_v1', $article_block_plan['data']['composition_contract']['composer_instruction']['instruction_id'] ?? '', 'build-article-block-plan exposes AI composer instructions through the contract' );
	npcink_abilities_toolkit_assert_same( true, $article_block_plan['data']['block_editor_review']['media_quality']['has_hero_media_attachment_id'] ?? null, 'build-article-block-plan review reports hero media attachment ids present' );
	npcink_abilities_toolkit_assert_same( false, $article_block_plan['data']['block_editor_review']['media_quality']['has_temporary_cloud_preview_url'] ?? true, 'build-article-block-plan review reports no temporary Cloud preview URLs' );
	npcink_abilities_toolkit_assert_same( 'article_editor_safety', $article_block_plan['data']['block_editor_quality_gate']['profile'] ?? '', 'build-article-block-plan uses an article editor-safety quality gate' );
	npcink_abilities_toolkit_assert_same( true, $article_block_plan['data']['block_editor_quality_gate']['ready_for_proposal'] ?? null, 'build-article-block-plan marks editor-safe article blocks ready for proposal' );
	npcink_abilities_toolkit_assert_same( false, $article_block_plan['data']['block_editor_quality_gate']['commit_execution'] ?? null, 'build-article-block-plan quality gate does not execute commits' );
	$article_block_actions = is_array( $article_block_plan['data']['write_actions'] ?? null ) ? $article_block_plan['data']['write_actions'] : array();
npcink_abilities_toolkit_assert_same( 2, count( $article_block_actions ), 'build-article-block-plan emits create and block update actions' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/create-draft', $article_block_actions[0]['target_ability_id'] ?? '', 'build-article-block-plan first creates a draft post' );
npcink_abilities_toolkit_assert_same( 'post', $article_block_actions[0]['input']['post_type'] ?? '', 'build-article-block-plan create action targets a post' );
npcink_abilities_toolkit_assert_same( 'draft', $article_block_actions[0]['input']['status'] ?? '', 'build-article-block-plan create action stays draft-only' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/update-post-blocks', $article_block_actions[1]['target_ability_id'] ?? '', 'build-article-block-plan second action updates Gutenberg blocks' );
npcink_abilities_toolkit_assert_same( '$outputs.create-article-draft.post_id', $article_block_actions[1]['input']['post_id'] ?? '', 'build-article-block-plan uses exact output reference for the new post id' );
$article_blocks = is_array( $article_block_actions[1]['input']['blocks'] ?? null ) ? $article_block_actions[1]['input']['blocks'] : array();
$article_markup = wp_json_encode( $article_blocks );
npcink_abilities_toolkit_assert_true( is_string( $article_markup ) && false !== strpos( $article_markup, '"blockName":"core\\/image"' ), 'build-article-block-plan uses core image for existing media' );
npcink_abilities_toolkit_assert_true( is_string( $article_markup ) && false !== strpos( $article_markup, '"id":9053' ), 'build-article-block-plan binds hero image to the reviewed attachment id' );
npcink_abilities_toolkit_assert_true( is_string( $article_markup ) && false !== strpos( $article_markup, 'class=\"wp-image-9053\"' ), 'build-article-block-plan serializes wp-image attachment classes' );
npcink_abilities_toolkit_assert_true( is_string( $article_markup ) && false !== strpos( $article_markup, '"blockName":"core\\/details"' ), 'build-article-block-plan uses details blocks for FAQ' );
npcink_abilities_toolkit_assert_true( is_string( $article_markup ) && false !== strpos( $article_markup, '"blockName":"core\\/columns"' ), 'build-article-block-plan uses columns for comparison sections' );
npcink_abilities_toolkit_assert_true( is_string( $article_markup ) && false !== strpos( $article_markup, '"isStackedOnMobile":true' ), 'build-article-block-plan stacks comparison columns on mobile' );
npcink_abilities_toolkit_assert_true( is_string( $article_markup ) && false !== strpos( $article_markup, 'wp-block-group has-border-color has-background' ), 'build-article-block-plan emits Gutenberg support classes for styled group blocks' );
npcink_abilities_toolkit_assert_true( is_string( $article_markup ) && false !== strpos( $article_markup, 'border-color:#e5e5e5;border-width:1px;border-radius:16px;background-color:#f7f7f4;padding-top:24px' ), 'build-article-block-plan serializes styled group wrappers from attrs' );
$article_block_plan_top_level_media = $core_read_package->build_article_block_plan(
	array(
		'title'                    => 'Gutenberg Article Top-Level Media',
		'article_template'         => 'comparison-review',
		'responsive_profile'       => 'article_standard',
		'media_strategy'           => 'existing_media_url',
		'hero_media_url'           => 'https://magick-ai.local/wp-content/uploads/2026/06/preview.webp',
		'hero_media_attachment_id' => 8053,
		'hero_media_alt'           => 'WordPress AI governed workflow hero visual',
		'variables'                => array(
			'user_intent' => '写一篇介绍 Gutenberg 模块红利的文章草稿，需要配图和 FAQ。',
		),
	)
);
npcink_abilities_toolkit_assert_same( true, $article_block_plan_top_level_media['success'] ?? null, 'build-article-block-plan accepts top-level reviewed media inputs' );
npcink_abilities_toolkit_assert_same( true, $article_block_plan_top_level_media['data']['editorial_quality']['has_hero_media_attachment_id'] ?? null, 'build-article-block-plan reports top-level media attachment binding' );
npcink_abilities_toolkit_assert_same( true, $article_block_plan_top_level_media['data']['responsive_quality']['has_responsive_media'] ?? null, 'build-article-block-plan reports responsive media for top-level media inputs' );
npcink_abilities_toolkit_assert_same( true, $article_block_plan_top_level_media['data']['block_editor_review']['media_quality']['has_hero_media_attachment_id'] ?? null, 'build-article-block-plan review reports top-level media attachment ids present' );
$article_top_level_media_actions = is_array( $article_block_plan_top_level_media['data']['write_actions'] ?? null ) ? $article_block_plan_top_level_media['data']['write_actions'] : array();
$article_top_level_media_blocks  = is_array( $article_top_level_media_actions[1]['input']['blocks'] ?? null ) ? $article_top_level_media_actions[1]['input']['blocks'] : array();
$article_top_level_media_markup  = wp_json_encode( $article_top_level_media_blocks );
npcink_abilities_toolkit_assert_true( is_string( $article_top_level_media_markup ) && false !== strpos( $article_top_level_media_markup, '"blockName":"core\\/image"' ), 'build-article-block-plan uses core image for top-level existing media' );
npcink_abilities_toolkit_assert_true( is_string( $article_top_level_media_markup ) && false !== strpos( $article_top_level_media_markup, '"id":8053' ), 'build-article-block-plan binds top-level media to the reviewed attachment id' );
npcink_abilities_toolkit_assert_true( is_string( $article_top_level_media_markup ) && false !== strpos( $article_top_level_media_markup, 'class=\"wp-image-8053\"' ), 'build-article-block-plan serializes top-level media wp-image attachment classes' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][280975] = (object) array(
	'ID'           => 280975,
	'post_type'    => 'post',
	'post_status'  => 'draft',
	'post_title'   => 'Existing Article Draft',
	'post_content' => '<!-- wp:paragraph --><p>Existing article draft body.</p><!-- /wp:paragraph -->',
);
$target_article_block_plan = $core_read_package->build_article_block_plan(
	array(
		'title'            => 'Update Existing Article',
		'target_post_id'   => 280975,
		'article_template' => 'editorial-longform',
		'variables'        => array(
			'title' => 'Update an existing Gutenberg article draft',
		),
	)
);
npcink_abilities_toolkit_assert_same( true, $target_article_block_plan['success'] ?? null, 'build-article-block-plan accepts an existing draft post target' );
npcink_abilities_toolkit_assert_same( 'update_existing', $target_article_block_plan['data']['target_post']['mode'] ?? '', 'build-article-block-plan reports existing draft post update mode' );
npcink_abilities_toolkit_assert_same( 280975, $target_article_block_plan['data']['target_post']['post_id'] ?? 0, 'build-article-block-plan preserves the target draft post id' );
npcink_abilities_toolkit_assert_same( 'draft', $target_article_block_plan['data']['target_post']['status'] ?? '', 'build-article-block-plan reports the target draft post status' );
npcink_abilities_toolkit_assert_same( 'update_existing', $target_article_block_plan['data']['block_editor_surface']['target_mode'] ?? '', 'build-article-block-plan reports update surface mode for target drafts' );
npcink_abilities_toolkit_assert_same( 1, $target_article_block_plan['data']['summary']['action_count'] ?? 0, 'build-article-block-plan emits one action when updating an existing draft post' );
$target_article_actions = is_array( $target_article_block_plan['data']['write_actions'] ?? null ) ? $target_article_block_plan['data']['write_actions'] : array();
npcink_abilities_toolkit_assert_same( 1, count( $target_article_actions ), 'build-article-block-plan omits create-draft when target_post_id is supplied' );
npcink_abilities_toolkit_assert_same( 'update-article-blocks', $target_article_actions[0]['action_id'] ?? '', 'build-article-block-plan keeps the update action id for existing drafts' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/update-post-blocks', $target_article_actions[0]['target_ability_id'] ?? '', 'build-article-block-plan targets update-post-blocks for existing article drafts' );
npcink_abilities_toolkit_assert_same( 280975, $target_article_actions[0]['input']['post_id'] ?? 0, 'build-article-block-plan uses the concrete target draft post id' );
npcink_abilities_toolkit_assert_same( false, $target_article_actions[0]['input']['commit'] ?? true, 'build-article-block-plan keeps existing draft update actions non-committing' );
npcink_abilities_toolkit_assert_same( true, $target_article_actions[0]['input']['dry_run'] ?? false, 'build-article-block-plan keeps existing draft update actions dry-run by default' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][280976] = (object) array(
	'ID'           => 280976,
	'post_type'    => 'post',
	'post_status'  => 'publish',
	'post_title'   => 'Published Article',
	'post_content' => '<!-- wp:paragraph --><p>Published article body.</p><!-- /wp:paragraph -->',
);
$published_target_article_block_plan = $core_read_package->build_article_block_plan(
	array(
		'title'            => 'Reject Published Article Target',
		'target_post_id'   => 280976,
		'article_template' => 'editorial-longform',
	)
);
npcink_abilities_toolkit_assert_true( is_wp_error( $published_target_article_block_plan ) && 'npcink_abilities_toolkit_article_block_target_status_invalid' === $published_target_article_block_plan->get_error_code(), 'build-article-block-plan rejects published target posts for replacement proposals' );


$media_relative_file_normalizer = new ReflectionMethod( Core_Read_Package::class, 'normalize_media_relative_file' );
$media_relative_file_normalizer->setAccessible( true );
npcink_abilities_toolkit_assert_same( '', $media_relative_file_normalizer->invoke( $core_read_package, '../../wp-config.php' ), 'media relative file normalization rejects traversal segments' );
npcink_abilities_toolkit_assert_same( '', $media_relative_file_normalizer->invoke( $core_read_package, '..' ), 'media relative file normalization rejects a bare parent segment' );
npcink_abilities_toolkit_assert_same( '2026/10/foo.webp', $media_relative_file_normalizer->invoke( $core_read_package, '/2026//10/foo.webp' ), 'media relative file normalization keeps contained paths and collapses duplicate separators' );

$media_relative_file_normalizer_trailing = new ReflectionMethod( Core_Read_Package::class, 'normalize_media_relative_file' );
$media_relative_file_normalizer_trailing->setAccessible( true );
npcink_abilities_toolkit_assert_same( '', $media_relative_file_normalizer_trailing->invoke( $core_read_package, '2026/..' ), 'media relative file normalization rejects a trailing parent segment' );
npcink_abilities_toolkit_assert_same( '', $media_relative_file_normalizer_trailing->invoke( $core_read_package, '2026/./10/a.webp' ), 'media relative file normalization rejects dot segments' );
$write_relative_file_normalizer = new ReflectionMethod( Core_Write_Package::class, 'normalize_media_relative_file' );
$write_relative_file_normalizer->setAccessible( true );
npcink_abilities_toolkit_assert_same( '', $write_relative_file_normalizer->invoke( $core_write_package, '../../wp-config.php' ), 'write-side media relative file normalization rejects traversal' );
npcink_abilities_toolkit_assert_same( '', $write_relative_file_normalizer->invoke( $core_write_package, '2026/..' ), 'write-side media relative file normalization rejects a trailing parent segment' );
npcink_abilities_toolkit_assert_same( '2026/10/foo.webp', $write_relative_file_normalizer->invoke( $core_write_package, '2026/10/foo.webp' ), 'write-side media relative file normalization keeps contained paths' );
