<?php
/**
 * Ordered regression suite part: 20-package-definitions.
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

$package_categories = new Category_Registrar();
$package_registrar = new Ability_Registrar( $package_categories, $contract_normalizer );
$block_document = new Gutenberg_Block_Document();
$core_read_package = new Core_Read_Package( $package_categories, $package_registrar );
$core_read_package->boot();
$core_write_package = new Core_Write_Package( $package_categories, $package_registrar );
$core_write_package->boot();
$media_reuse_policy_method = new ReflectionMethod( Core_Write_Package::class, 'media_visual_reuse_policy_from_facts' );
if ( PHP_VERSION_ID < 80100 ) {
	$media_reuse_policy_method->setAccessible( true );
}
npcink_abilities_toolkit_assert_same( 'reuse', $media_reuse_policy_method->invoke( $core_write_package, array( 'encoding_mode' => 'lossless', 'resize_applied' => false ) ), 'lossless encoding without resize directly reuses visual evidence' );
npcink_abilities_toolkit_assert_same( 'reuse_with_human_check', $media_reuse_policy_method->invoke( $core_write_package, array( 'encoding_mode' => 'lossy', 'source_width' => 1000, 'source_height' => 800, 'output_width' => 750, 'output_height' => 600 ) ), 'lossy compression or exactly 25 percent scaling requires human visual confirmation' );
npcink_abilities_toolkit_assert_same( 'requires_reidentification', $media_reuse_policy_method->invoke( $core_write_package, array( 'encoding_mode' => 'lossy', 'source_width' => 1000, 'source_height' => 800, 'output_width' => 749, 'output_height' => 600 ) ), 'scaling beyond the 25 percent limit requires fresh visual identification' );
npcink_abilities_toolkit_assert_same( 'requires_reidentification', $media_reuse_policy_method->invoke( $core_write_package, array( 'crop_applied' => true, 'source_width' => 1000, 'source_height' => 800, 'output_width' => 1000, 'output_height' => 800 ) ), 'crop transforms cannot reuse prior visual evidence' );
npcink_abilities_toolkit_assert_same( 'requires_reidentification', $media_reuse_policy_method->invoke( $core_write_package, array( 'alpha_preserved' => false, 'source_width' => 1000, 'source_height' => 800, 'output_width' => 1000, 'output_height' => 800 ) ), 'loss of transparency cannot reuse prior visual evidence' );
$core_destructive_package = new Core_Destructive_Package( $package_categories, $package_registrar );
$core_destructive_package->boot();
$core_comment_package = new Core_Comment_Package( $package_categories, $package_registrar );
$core_comment_package->boot();
$package_abilities = $package_registrar->all();
$migrated_read_ability_ids = array(
	'npcink-abilities-toolkit/site-info',
	'npcink-abilities-toolkit/list-post-types',
	'npcink-abilities-toolkit/list-taxonomies',
	'npcink-abilities-toolkit/count-posts',
	'npcink-abilities-toolkit/list-pages-tree',
	'npcink-abilities-toolkit/list-posts',
	'npcink-abilities-toolkit/get-post',
	'npcink-abilities-toolkit/resolve-url-to-post',
	'npcink-abilities-toolkit/get-post-blocks',
	'npcink-abilities-toolkit/list-post-revisions',
	'npcink-abilities-toolkit/list-media',
	'npcink-abilities-toolkit/resolve-media-attachment-by-url',
	'npcink-abilities-toolkit/list-terms',
	'npcink-abilities-toolkit/list-taxonomy-terms',
	'npcink-abilities-toolkit/list-categories',
	'npcink-abilities-toolkit/list-tags',
	'npcink-abilities-toolkit/get-term',
	'npcink-abilities-toolkit/propose-post-excerpt',
	'npcink-abilities-toolkit/resolve-post-metadata-plan',
	'npcink-abilities-toolkit/list-users',
	'npcink-abilities-toolkit/list-comments',
	'npcink-abilities-toolkit/build-comment-moderation-suggest',
	'npcink-abilities-toolkit/compose-comment-moderation-result',
	'npcink-abilities-toolkit/build-comment-mention-reply-suggest',
	'npcink-abilities-toolkit/read-comment-trigger-queue',
	'npcink-abilities-toolkit/compose-comment-mention-reply-result',
	'npcink-abilities-toolkit/build-comment-moderation-batch-suggest',
	'npcink-abilities-toolkit/compose-comment-moderation-batch-result',
	'npcink-abilities-toolkit/list-menus',
	'npcink-abilities-toolkit/get-menu',
	'npcink-abilities-toolkit/search-posts',
	'npcink-abilities-toolkit/resolve-internal-link-targets',
	'npcink-abilities-toolkit/build-inline-image-blocks',
	'npcink-abilities-toolkit/build-media-seo-assets',
	'npcink-abilities-toolkit/build-media-derivative-batch-plan',
	'npcink-abilities-toolkit/geo-analyze',
	'npcink-abilities-toolkit/optimize-media-metadata',
	'npcink-abilities-toolkit/position-inline-image-blocks',
	'npcink-abilities-toolkit/build-article-optimization-report',
	'npcink-abilities-toolkit/seo-report-context',
	'npcink-abilities-toolkit/read-post-optimization-context',
	'npcink-abilities-toolkit/build-article-single-optimization-suggest',
	'npcink-abilities-toolkit/build-article-optimization-apply-plan',
	'npcink-abilities-toolkit/build-content-metadata-apply-plan',
	'npcink-abilities-toolkit/build-article-audio-adoption-plan',
	'npcink-abilities-toolkit/compose-article-optimization-apply-result',
	'npcink-abilities-toolkit/extract-reference-post-style',
	'npcink-abilities-toolkit/extract-style-baseline',
	'npcink-abilities-toolkit/build-article-production-fingerprint',
	'npcink-abilities-toolkit/check-article-production-duplicate',
	'npcink-abilities-toolkit/review-article-output-light',
	'npcink-abilities-toolkit/compose-article-production-result',
	'npcink-abilities-toolkit/compose-article-draft-result',
	'npcink-abilities-toolkit/resolve-article-publication-decision',
	'npcink-abilities-toolkit/build-article-style-profile',
	'npcink-abilities-toolkit/get-post-stats',
	'npcink-abilities-toolkit/list-revisions',
	'npcink-abilities-toolkit/get-post-meta',
	'npcink-abilities-toolkit/list-pages',
	'npcink-abilities-toolkit/get-page',
	'npcink-abilities-toolkit/inspect-page-structure',
);
	$new_read_ability_ids = array(
		'npcink-abilities-toolkit/wp-ops-diagnostics-detail',
		'npcink-abilities-toolkit/list-workflow-recipes',
		'npcink-abilities-toolkit/get-workflow-recipe',
		'npcink-abilities-toolkit/get-post-context',
	'npcink-abilities-toolkit/get-content-publishing-checklist',
	'npcink-abilities-toolkit/get-content-inventory-health',
	'npcink-abilities-toolkit/get-nonproduction-content-inventory',
	'npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan',
	'npcink-abilities-toolkit/build-content-inventory-fix-plan',
	'npcink-abilities-toolkit/search-post-meta',
	'npcink-abilities-toolkit/get-bulk-publishing-checklist',
	'npcink-abilities-toolkit/get-internal-link-opportunity-report',
	'npcink-abilities-toolkit/get-site-operations-dashboard',
	'npcink-abilities-toolkit/get-post-publish-risk-report',
	'npcink-abilities-toolkit/get-article-publish-preflight-context',
	'npcink-abilities-toolkit/get-content-refresh-opportunities',
	'npcink-abilities-toolkit/get-old-article-refresh-context',
	'npcink-abilities-toolkit/get-internal-link-graph-health',
			'npcink-abilities-toolkit/get-media-cleanup-opportunities',
			'npcink-abilities-toolkit/list-media-backups',
		'npcink-abilities-toolkit/build-media-inventory-fix-plan',
		'npcink-abilities-toolkit/build-media-reference-repair-plan',
		'npcink-abilities-toolkit/build-media-adoption-enhancement-plan',
		'npcink-abilities-toolkit/build-image-candidate-review-artifact',
		'npcink-abilities-toolkit/build-media-settings-reference-repair-plan',
		'npcink-abilities-toolkit/build-media-optimization-plan',
		'npcink-abilities-toolkit/build-media-rename-plan',
		'npcink-abilities-toolkit/get-taxonomy-consolidation-suggestions',
		'npcink-abilities-toolkit/suggest-post-taxonomy-terms',
		'npcink-abilities-toolkit/propose-post-taxonomy-terms',
		'npcink-abilities-toolkit/get-page-structure-health',
		'npcink-abilities-toolkit/route-content-intent',
		'npcink-abilities-toolkit/build-pattern-page-plan',
		'npcink-abilities-toolkit/review-pattern-page',
		'npcink-abilities-toolkit/review-block-editor-surface',
	'npcink-abilities-toolkit/get-seo-geo-gap-report',
	'npcink-abilities-toolkit/get-site-style-baseline',
	'npcink-abilities-toolkit/build-article-workflow-context',
	'npcink-abilities-toolkit/get-publishing-calendar-context',
	'npcink-abilities-toolkit/get-media-inventory-health',
	'npcink-abilities-toolkit/inspect-media-asset',
	'npcink-abilities-toolkit/build-media-derivative-cloud-request',
	'npcink-abilities-toolkit/build-media-alt-apply-plan',
	'npcink-abilities-toolkit/get-post-seo-geo-readiness',
	'npcink-abilities-toolkit/get-site-topic-coverage-report',
	'npcink-abilities-toolkit/get-taxonomy-inventory-health',
	'npcink-abilities-toolkit/get-revision-change-risk-report',
);
$new_comment_ability_ids = array(
	'npcink-abilities-toolkit/get-comment-queue-health',
	'npcink-abilities-toolkit/get-comment-action-priority-queue',
	'npcink-abilities-toolkit/get-comment-compliance-handoff',
);
$migrated_write_ability_ids = array(
	'npcink-abilities-toolkit/create-draft',
	'npcink-abilities-toolkit/update-post',
	'npcink-abilities-toolkit/set-post-seo-meta',
	'npcink-abilities-toolkit/patch-post-content',
	'npcink-abilities-toolkit/patch-setting-value',
	'npcink-abilities-toolkit/update-post-blocks',
	'npcink-abilities-toolkit/set-post-slug',
	'npcink-abilities-toolkit/set-post-author',
	'npcink-abilities-toolkit/set-post-template',
	'npcink-abilities-toolkit/set-post-format',
	'npcink-abilities-toolkit/create-term',
	'npcink-abilities-toolkit/update-term',
	'npcink-abilities-toolkit/set-post-terms',
	'npcink-abilities-toolkit/update-media-details',
	'npcink-abilities-toolkit/upload-media-from-url',
	'npcink-abilities-toolkit/optimize-media-asset',
	'npcink-abilities-toolkit/replace-media-file',
	'npcink-abilities-toolkit/restore-media-backup',
	'npcink-abilities-toolkit/rename-media-file',
	'npcink-abilities-toolkit/set-post-featured-image',
	'npcink-abilities-toolkit/schedule-post',
	'npcink-abilities-toolkit/publish-post',
	'npcink-abilities-toolkit/restore-post',
	'npcink-abilities-toolkit/approve-comment',
	'npcink-abilities-toolkit/reply-comment',
	'npcink-abilities-toolkit/adopt-article-audio',
);
$priority_write_implementation_posture_ids = array(
	'npcink-abilities-toolkit/create-draft',
	'npcink-abilities-toolkit/update-post',
	'npcink-abilities-toolkit/update-post-blocks',
	'npcink-abilities-toolkit/set-post-terms',
	'npcink-abilities-toolkit/update-media-details',
);
$migrated_destructive_ability_ids = array(
	'npcink-abilities-toolkit/delete-term',
	'npcink-abilities-toolkit/merge-terms',
	'npcink-abilities-toolkit/bulk-update-post-terms',
	'npcink-abilities-toolkit/spam-comment',
	'npcink-abilities-toolkit/trash-comment',
	'npcink-abilities-toolkit/delete-media-permanently',
	'npcink-abilities-toolkit/trash-post',
	'npcink-abilities-toolkit/delete-post-permanently',
);
$core_governance_snapshot_path = __DIR__ . '/../fixtures/core-governance-catalog-snapshot.json';
$core_governance_snapshot_json = file_get_contents( $core_governance_snapshot_path );
npcink_abilities_toolkit_assert_true( false !== $core_governance_snapshot_json, 'core governance catalog snapshot fixture is readable' );
$core_governance_expected_snapshot = json_decode( (string) $core_governance_snapshot_json, true );
npcink_abilities_toolkit_assert_true( is_array( $core_governance_expected_snapshot ), 'core governance catalog snapshot fixture decodes as an object' );
npcink_abilities_toolkit_assert_same(
	$core_governance_expected_snapshot,
	npcink_abilities_toolkit_core_governance_catalog_snapshot(
		$package_abilities,
		array_keys( (array) ( $core_governance_expected_snapshot['abilities'] ?? array() ) )
	),
	'core governance catalog snapshot matches normalized package definitions'
);
$core_snapshot_doc = file_get_contents( __DIR__ . '/../../docs/core-governance-catalog-snapshot.md' );
npcink_abilities_toolkit_assert_true( is_string( $core_snapshot_doc ) && false !== strpos( $core_snapshot_doc, 'tests/fixtures/core-governance-catalog-snapshot.json' ), 'core governance catalog snapshot doc points to fixture' );
$permission_matrix_doc = file_get_contents( __DIR__ . '/../../docs/permission-matrix.md' );
npcink_abilities_toolkit_assert_true( is_string( $permission_matrix_doc ) && false !== strpos( $permission_matrix_doc, 'Dry-run previews must still pass the same WordPress permission checks' ), 'permission matrix documents dry-run permission boundary' );
$schema_audit_doc = file_get_contents( __DIR__ . '/../../docs/schema-boundary-audit.md' );
npcink_abilities_toolkit_assert_true( is_string( $schema_audit_doc ) && false !== strpos( $schema_audit_doc, 'REST ability details expose' ), 'schema boundary audit documents REST exposure verification' );
$smoke_wp = file_get_contents( __DIR__ . '/../smoke-wp.php' );
npcink_abilities_toolkit_assert_true( is_string( $smoke_wp ) && false !== strpos( $smoke_wp, 'register_shutdown_function' ), 'WordPress smoke runs fixture cleanup on shutdown' );
npcink_abilities_toolkit_assert_true( is_string( $smoke_wp ) && false !== strpos( $smoke_wp, 'npcink_abilities_toolkit_smoke_register_post_fixture' ), 'WordPress smoke registers post fixtures for cleanup' );
npcink_abilities_toolkit_assert_true( is_string( $smoke_wp ) && false !== strpos( $smoke_wp, 'npcink_abilities_toolkit_smoke_register_comment_fixture' ), 'WordPress smoke registers comment fixtures for cleanup' );
npcink_abilities_toolkit_assert_true( is_string( $smoke_wp ) && false !== strpos( $smoke_wp, 'npcink_abilities_toolkit_smoke_register_attachment_fixture' ), 'WordPress smoke registers media fixtures for cleanup' );
npcink_abilities_toolkit_assert_true( is_string( $smoke_wp ) && false !== strpos( $smoke_wp, '_npcink_abilities_toolkit_smoke_fixture_run_id' ), 'WordPress smoke tags media fixtures with a run id' );
npcink_abilities_toolkit_assert_true( is_string( $smoke_wp ) && false !== strpos( $smoke_wp, 'npcink_abilities_toolkit_smoke_known_media_fixture_leak_ids' ), 'WordPress smoke detects reserved-prefix media leaks' );
npcink_abilities_toolkit_assert_true( is_string( $smoke_wp ) && false !== strpos( $smoke_wp, 'npcink_abilities_toolkit_smoke_register_term_fixture' ), 'WordPress smoke registers taxonomy term fixtures for cleanup' );
npcink_abilities_toolkit_assert_true( is_string( $smoke_wp ) && false !== strpos( $smoke_wp, 'wp_delete_post' ), 'WordPress smoke permanently deletes post fixtures' );
npcink_abilities_toolkit_assert_true( is_string( $smoke_wp ) && false !== strpos( $smoke_wp, 'wp_delete_comment' ), 'WordPress smoke permanently deletes comment fixtures' );
npcink_abilities_toolkit_assert_true( is_string( $smoke_wp ) && false !== strpos( $smoke_wp, 'wp_delete_attachment' ), 'WordPress smoke permanently deletes media fixtures' );
npcink_abilities_toolkit_assert_true( is_string( $smoke_wp ) && false !== strpos( $smoke_wp, 'wp_delete_term' ), 'WordPress smoke deletes taxonomy term fixtures' );
npcink_abilities_toolkit_assert_true( is_string( $smoke_wp ) && false !== strpos( $smoke_wp, 'Smoke media fixture is deleted after smoke.' ), 'WordPress smoke asserts media fixtures are gone at the end' );
npcink_abilities_toolkit_assert_true( is_string( $smoke_wp ) && false !== strpos( $smoke_wp, 'Smoke leaves no registered or reserved-prefix media fixtures behind.' ), 'WordPress smoke asserts no reserved-prefix media fixtures remain at the end' );
$core_consumer_example = file_get_contents( __DIR__ . '/../../examples/core-governance-consumer.php' );
npcink_abilities_toolkit_assert_true( is_string( $core_consumer_example ) && false !== strpos( $core_consumer_example, 'npcink_abilities_toolkit_get_registered' ), 'core governance consumer example uses ability discovery' );
npcink_abilities_toolkit_assert_true( is_string( $core_consumer_example ) && false !== strpos( $core_consumer_example, "'ability_id' => \$ability_id" ), 'core governance consumer example prepares a real ability proposal payload' );
npcink_abilities_toolkit_assert_true( isset( $package_categories->all()['npcink-abilities-toolkit-data'] ), 'core read package registers the legacy npcink-abilities-toolkit-data category for compatibility' );
npcink_abilities_toolkit_assert_true( isset( $package_categories->all()['npcink-abilities-toolkit-pages'] ), 'core read package registers the legacy npcink-abilities-toolkit-pages category for compatibility' );
npcink_abilities_toolkit_assert_true( isset( $package_categories->all()['npcink-abilities-toolkit-comments'] ), 'core comment package registers the standalone comments category' );
npcink_abilities_toolkit_assert_true( isset( $package_categories->all()['npcink-abilities-toolkit-write'] ), 'core write package registers the legacy npcink-abilities-toolkit-write category for compatibility' );
npcink_abilities_toolkit_assert_true( isset( $package_categories->all()['npcink-abilities-toolkit-diagnostics'] ), 'core read package registers the standalone diagnostics category' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/wp-diagnostics-summary'] ), 'core read package owns standalone wp-diagnostics-summary ability' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit-diagnostics', $package_abilities['npcink-abilities-toolkit/wp-diagnostics-summary']['category'], 'wp-diagnostics-summary uses standalone diagnostics category' );
npcink_abilities_toolkit_assert_same( false, $package_abilities['npcink-abilities-toolkit/wp-diagnostics-summary']['input_schema']['properties']['include_current_user']['default'] ?? null, 'wp-diagnostics-summary omits current user details by default' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail'] ), 'core read package owns standalone wp-ops-diagnostics-detail ability' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit-diagnostics', $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['category'], 'wp-ops-diagnostics-detail uses standalone diagnostics category' );
npcink_abilities_toolkit_assert_true( false !== strpos( $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['description'] ?? '', 'plugin' ), 'ops diagnostics description mentions plugin details' );
npcink_abilities_toolkit_assert_same( 'summary', $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['profile']['default'] ?? null, 'ops diagnostics defaults to the summary profile' );
npcink_abilities_toolkit_assert_same( array( 'summary', 'detail', 'forensics' ), $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['profile']['enum'] ?? array(), 'ops diagnostics exposes bounded diagnostic profiles' );
npcink_abilities_toolkit_assert_same( 50, $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['max_cron_events']['maximum'] ?? null, 'ops diagnostics bounds returned cron events' );
npcink_abilities_toolkit_assert_same( false, $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['include_log_contents']['default'] ?? null, 'ops diagnostics does not include log contents by default' );
npcink_abilities_toolkit_assert_same( false, $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['include_current_user']['default'] ?? null, 'ops diagnostics omits current user details by default' );
npcink_abilities_toolkit_assert_same( false, $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['include_database']['default'] ?? null, 'ops diagnostics omits database table status by default' );
npcink_abilities_toolkit_assert_true( ! isset( $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['include_log_tail'] ), 'ops diagnostics uses one log contents control' );
npcink_abilities_toolkit_assert_same( false, $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['include_inactive_plugins']['default'] ?? null, 'ops diagnostics omits inactive plugin rows by default' );
npcink_abilities_toolkit_assert_same( false, $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['include_plugin_updates']['default'] ?? null, 'ops diagnostics omits plugin update rows by default' );
npcink_abilities_toolkit_assert_same( 500, $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['max_plugins_per_group']['maximum'] ?? null, 'ops diagnostics bounds plugin rows per group' );
npcink_abilities_toolkit_assert_same( 200, $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['tail_lines']['maximum'] ?? null, 'ops diagnostics bounds returned log tail lines' );
npcink_abilities_toolkit_assert_same( 10080, $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['since_minutes']['maximum'] ?? null, 'ops diagnostics bounds log since window' );
npcink_abilities_toolkit_assert_true( in_array( 'warning', $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['severity']['items']['enum'] ?? array(), true ), 'ops diagnostics supports log severity filtering' );
npcink_abilities_toolkit_assert_same( false, $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['include_integrations']['default'] ?? null, 'ops diagnostics omits integration diagnostics by default' );
npcink_abilities_toolkit_assert_true( in_array( 'plugins', $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['output_schema']['required'] ?? array(), true ), 'ops diagnostics output requires plugins section' );
npcink_abilities_toolkit_assert_true( in_array( 'profile', $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['output_schema']['required'] ?? array(), true ), 'ops diagnostics output requires profile section' );
npcink_abilities_toolkit_assert_true( in_array( 'current_user', $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['output_schema']['required'] ?? array(), true ), 'ops diagnostics output requires current user section' );
npcink_abilities_toolkit_assert_true( in_array( 'integrations', $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['output_schema']['required'] ?? array(), true ), 'ops diagnostics output requires integrations section' );
npcink_abilities_toolkit_assert_true( in_array( 'seo_summary', $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['output_schema']['required'] ?? array(), true ), 'ops diagnostics output requires SEO summary section' );
$parse_log_entry = new ReflectionMethod( $core_read_package, 'parse_diagnostics_log_entry' );
$parse_log_entry->setAccessible( true );
$summarize_log_sources = new ReflectionMethod( $core_read_package, 'summarize_diagnostics_log_sources' );
$summarize_log_sources->setAccessible( true );
$summarize_top_messages = new ReflectionMethod( $core_read_package, 'summarize_diagnostics_top_messages' );
$summarize_top_messages->setAccessible( true );
$plugin_log_entry = $parse_log_entry->invoke( $core_read_package, '[30-May-2026 10:39:34 UTC] PHP Deprecated: Test in /srv/app/public/wp-content/plugins/plugin-check/check.php on line 10' );
$theme_log_entry = $parse_log_entry->invoke( $core_read_package, '[30-May-2026 10:40:34 UTC] PHP Warning: Test in /srv/app/public/wp-content/themes/twentytwentyfour/functions.php on line 20' );
$phar_log_entry = $parse_log_entry->invoke( $core_read_package, '[30-May-2026 10:41:34 UTC] PHP Deprecated: Using null as an array offset is deprecated in phar:///tmp/wp-cli.phar/vendor/file.php on line 30' );
$home_path_log_entry = $parse_log_entry->invoke( $core_read_package, '[30-May-2026 10:42:34 UTC] PHP Warning: mysqli_real_connect(): (HY000/2002): No such file or directory in /Users/muze/Local Sites/npcink-abilities-toolkit/app/public/wp-includes/class-wpdb.php on line 1990' );
npcink_abilities_toolkit_assert_same( 'plugin', $plugin_log_entry['source_type'] ?? '', 'ops diagnostics detects plugin log source type' );
npcink_abilities_toolkit_assert_same( 'plugin-check', $plugin_log_entry['source_hint'] ?? '', 'ops diagnostics detects plugin log source hint' );
npcink_abilities_toolkit_assert_same( 'Test', $plugin_log_entry['message_fingerprint'] ?? '', 'ops diagnostics fingerprints plugin log messages without path noise' );
npcink_abilities_toolkit_assert_same( 'theme', $theme_log_entry['source_type'] ?? '', 'ops diagnostics detects theme log source type' );
npcink_abilities_toolkit_assert_same( 'twentytwentyfour', $theme_log_entry['source_hint'] ?? '', 'ops diagnostics detects theme log source hint' );
npcink_abilities_toolkit_assert_same( 'phar', $phar_log_entry['source_type'] ?? '', 'ops diagnostics detects phar log source type' );
npcink_abilities_toolkit_assert_same( 'wp-cli', $phar_log_entry['source_hint'] ?? '', 'ops diagnostics detects wp-cli log source hint' );
npcink_abilities_toolkit_assert_same( 'wp-cli.phar', $phar_log_entry['source_basename'] ?? '', 'ops diagnostics exposes safe phar basename hint' );
npcink_abilities_toolkit_assert_same( 'wp-cli.phar', $phar_log_entry['phar_hint'] ?? '', 'ops diagnostics exposes safe phar hint' );
npcink_abilities_toolkit_assert_same( 'Using null as an array offset is deprecated', $phar_log_entry['message_fingerprint'] ?? '', 'ops diagnostics fingerprints phar messages without path noise' );
npcink_abilities_toolkit_assert_same( 'mysqli_real_connect(): (HY000/N): No such file or directory', $home_path_log_entry['message_fingerprint'] ?? '', 'ops diagnostics fingerprints home path messages without path noise' );
$log_source_summary = $summarize_log_sources->invoke( $core_read_package, array( $plugin_log_entry, $plugin_log_entry, $theme_log_entry, $phar_log_entry ) );
npcink_abilities_toolkit_assert_same( 'plugin', $log_source_summary[0]['source_type'] ?? '', 'ops diagnostics source summary sorts most frequent source first' );
npcink_abilities_toolkit_assert_same( 'plugin-check', $log_source_summary[0]['source_hint'] ?? '', 'ops diagnostics source summary groups by source hint' );
npcink_abilities_toolkit_assert_same( 'deprecated', $log_source_summary[0]['severity'] ?? '', 'ops diagnostics source summary groups by severity' );
npcink_abilities_toolkit_assert_same( 'Test', $log_source_summary[0]['message_fingerprint'] ?? '', 'ops diagnostics source summary includes message fingerprints' );
npcink_abilities_toolkit_assert_same( 2, $log_source_summary[0]['count'] ?? 0, 'ops diagnostics source summary counts repeated source entries' );
$log_top_messages = $summarize_top_messages->invoke( $core_read_package, array( $phar_log_entry, $phar_log_entry, $plugin_log_entry ) );
npcink_abilities_toolkit_assert_same( 'Using null as an array offset is deprecated', $log_top_messages[0]['fingerprint'] ?? '', 'ops diagnostics top messages sort repeated fingerprints first' );
npcink_abilities_toolkit_assert_same( 'wp-cli.phar', $log_top_messages[0]['phar_hint'] ?? '', 'ops diagnostics top messages include safe phar hint' );
npcink_abilities_toolkit_assert_same( 2, $log_top_messages[0]['count'] ?? 0, 'ops diagnostics top messages count repeated fingerprints' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/list-posts']['input_schema']['properties']['modified_after'] ), 'list-posts supports modified date filtering' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/list-posts']['input_schema']['properties']['taxonomy'] ), 'list-posts supports taxonomy filtering' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/search-posts']['input_schema']['properties']['post_types'] ), 'search-posts supports multiple post types' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/search-posts']['input_schema']['properties']['statuses'] ), 'search-posts supports multiple statuses' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/search-posts']['input_schema']['properties']['taxonomy'] ), 'search-posts supports taxonomy filtering' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/search-posts']['input_schema']['properties']['modified_after'] ), 'search-posts supports modified date filtering' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/search-posts']['output_schema']['properties']['filters'] ), 'search-posts returns applied filters' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/search-posts']['output_schema']['properties']['items']['items']['properties']['matched_fields'] ), 'search-posts returns matched field hints' );
npcink_abilities_toolkit_assert_same( array( 'search', 'meta_keys' ), $package_abilities['npcink-abilities-toolkit/search-post-meta']['input_schema']['required'] ?? array(), 'search-post-meta requires search and explicit meta keys' );
npcink_abilities_toolkit_assert_same( array( 'post_id', 'meta_key' ), $package_abilities['npcink-abilities-toolkit/get-post-meta']['input_schema']['required'] ?? array(), 'get-post-meta requires one explicit safe meta key' );
npcink_abilities_toolkit_assert_same( 10, $package_abilities['npcink-abilities-toolkit/search-post-meta']['input_schema']['properties']['meta_keys']['maxItems'] ?? null, 'search-post-meta bounds meta key count' );
npcink_abilities_toolkit_assert_same( false, $package_abilities['npcink-abilities-toolkit/search-post-meta']['input_schema']['additionalProperties'] ?? null, 'search-post-meta rejects undeclared inputs' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/search-post-meta']['output_schema']['properties']['items']['items']['properties']['matched_meta_keys'] ), 'search-post-meta returns matched meta keys' );
npcink_abilities_toolkit_assert_true( in_array( 'tree', $package_abilities['npcink-abilities-toolkit/get-menu']['output_schema']['required'] ?? array(), true ), 'get-menu returns a menu tree' );
foreach ( $migrated_read_ability_ids as $migrated_ability_id ) {
	npcink_abilities_toolkit_assert_true( isset( $package_abilities[ $migrated_ability_id ] ), "core read package owns migrated {$migrated_ability_id} ability" );
	npcink_abilities_toolkit_assert_package_read_ability_contract( $migrated_ability_id, $package_abilities[ $migrated_ability_id ] );
}
foreach ( $new_read_ability_ids as $new_read_ability_id ) {
	npcink_abilities_toolkit_assert_true( isset( $package_abilities[ $new_read_ability_id ] ), "core read package owns new {$new_read_ability_id} ability" );
	npcink_abilities_toolkit_assert_package_read_ability_contract( $new_read_ability_id, $package_abilities[ $new_read_ability_id ] );
}
foreach ( $new_comment_ability_ids as $new_comment_ability_id ) {
	npcink_abilities_toolkit_assert_true( isset( $package_abilities[ $new_comment_ability_id ] ), "core comment package owns new {$new_comment_ability_id} ability" );
	npcink_abilities_toolkit_assert_package_read_ability_contract( $new_comment_ability_id, $package_abilities[ $new_comment_ability_id ] );
}
npcink_abilities_toolkit_assert_same( true, $package_abilities['npcink-abilities-toolkit/site-info']['project_to_npcink_catalog'], 'migrated core read abilities project into Npcink AI catalog' );
npcink_abilities_toolkit_assert_same( true, $package_abilities['npcink-abilities-toolkit/get-post-context']['project_to_npcink_catalog'], 'new official post context ability projects into Npcink AI catalog' );
npcink_abilities_toolkit_assert_same( true, $package_abilities['npcink-abilities-toolkit/get-post-context']['input_schema']['properties']['include_blocks']['default'] ?? null, 'get-post-context includes blocks by default' );
npcink_abilities_toolkit_assert_same( false, $package_abilities['npcink-abilities-toolkit/get-content-publishing-checklist']['requires_confirm'], 'publishing checklist remains readonly' );
npcink_abilities_toolkit_assert_same( 100, $package_abilities['npcink-abilities-toolkit/get-content-inventory-health']['input_schema']['properties']['per_page']['maximum'] ?? null, 'inventory health scan is bounded to 100 posts per page' );
npcink_abilities_toolkit_assert_same( 100, $package_abilities['npcink-abilities-toolkit/get-nonproduction-content-inventory']['input_schema']['properties']['per_page']['maximum'] ?? null, 'nonproduction content inventory scan is bounded to 100 items per section' );
npcink_abilities_toolkit_assert_same( 200, $package_abilities['npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan']['input_schema']['properties']['max_actions']['maximum'] ?? null, 'nonproduction content cleanup plan bounds planned actions to Adapter batch execution limit' );
npcink_abilities_toolkit_assert_same( true, $package_abilities['npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan']['input_schema']['properties']['include_posts']['default'] ?? null, 'nonproduction content cleanup plan exposes include_posts control' );
npcink_abilities_toolkit_assert_true( ! isset( $package_abilities['npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan']['input_schema']['properties']['mode'] ), 'nonproduction content cleanup plan does not expose unused mode input' );
npcink_abilities_toolkit_assert_same( 100, $package_abilities['npcink-abilities-toolkit/build-content-inventory-fix-plan']['input_schema']['properties']['max_actions']['maximum'] ?? null, 'content inventory fix plan bounds planned actions' );
npcink_abilities_toolkit_assert_same( array( 'post.read' ), $package_abilities['npcink-abilities-toolkit/build-content-inventory-fix-plan']['required_scopes'] ?? array(), 'content inventory fix plan remains a read-scope planning ability' );
foreach ( array( 'npcink-abilities-toolkit/get-nonproduction-content-inventory', 'npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan', 'npcink-abilities-toolkit/build-content-inventory-fix-plan' ) as $planning_agent_usage_id ) {
	npcink_abilities_toolkit_assert_true( ! empty( $package_abilities[ $planning_agent_usage_id ]['agent_usage']['when_to_use'] ), "{$planning_agent_usage_id} exposes agent usage guidance" );
	npcink_abilities_toolkit_assert_true( ! empty( $package_abilities[ $planning_agent_usage_id ]['agent_usage']['stopping_points'] ), "{$planning_agent_usage_id} exposes agent stopping points" );
}
npcink_abilities_toolkit_assert_same( 50, $package_abilities['npcink-abilities-toolkit/get-bulk-publishing-checklist']['input_schema']['properties']['post_ids']['maxItems'] ?? null, 'bulk publishing checklist is bounded to 50 posts' );
npcink_abilities_toolkit_assert_same( 10, $package_abilities['npcink-abilities-toolkit/get-internal-link-opportunity-report']['input_schema']['properties']['max_targets']['maximum'] ?? null, 'internal link opportunity report bounds target count' );
npcink_abilities_toolkit_assert_same( 8, $package_abilities['npcink-abilities-toolkit/resolve-internal-link-targets']['input_schema']['properties']['candidate_limit']['maximum'] ?? null, 'internal link target resolver bounds editor candidate count' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/resolve-internal-link-targets']['input_schema']['properties']['related_content_evidence'] ), 'internal link target resolver accepts supplied related-content evidence' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/resolve-internal-link-targets']['output_schema']['properties']['data']['properties']['internal_link_candidates'] ), 'internal link target resolver declares reusable editor candidate artifact output' );
npcink_abilities_toolkit_assert_same( 100, $package_abilities['npcink-abilities-toolkit/get-site-operations-dashboard']['input_schema']['properties']['per_page']['maximum'] ?? null, 'site operations dashboard is bounded to 100 posts per page' );
npcink_abilities_toolkit_assert_same( array( 'post_id' ), $package_abilities['npcink-abilities-toolkit/get-post-publish-risk-report']['input_schema']['required'] ?? array(), 'post publish risk report requires post_id' );
npcink_abilities_toolkit_assert_same( array( 'post_id' ), $package_abilities['npcink-abilities-toolkit/get-article-publish-preflight-context']['input_schema']['required'] ?? array(), 'article publish preflight context requires post_id' );
npcink_abilities_toolkit_assert_same( 100, $package_abilities['npcink-abilities-toolkit/get-content-refresh-opportunities']['input_schema']['properties']['per_page']['maximum'] ?? null, 'content refresh opportunities scan is bounded to 100 posts per page' );
npcink_abilities_toolkit_assert_same( 100, $package_abilities['npcink-abilities-toolkit/get-old-article-refresh-context']['input_schema']['properties']['per_page']['maximum'] ?? null, 'old article refresh context scan is bounded to 100 posts per page' );
npcink_abilities_toolkit_assert_same( 100, $package_abilities['npcink-abilities-toolkit/get-internal-link-graph-health']['input_schema']['properties']['per_page']['maximum'] ?? null, 'internal link graph health scan is bounded to 100 posts per page' );
npcink_abilities_toolkit_assert_same( 100, $package_abilities['npcink-abilities-toolkit/get-media-cleanup-opportunities']['input_schema']['properties']['per_page']['maximum'] ?? null, 'media cleanup opportunities scan is bounded to 100 assets per page' );
npcink_abilities_toolkit_assert_same( 100, $package_abilities['npcink-abilities-toolkit/build-media-inventory-fix-plan']['input_schema']['properties']['max_actions']['maximum'] ?? null, 'media inventory fix plan bounds planned actions' );
npcink_abilities_toolkit_assert_same( false, $package_abilities['npcink-abilities-toolkit/build-media-inventory-fix-plan']['input_schema']['properties']['include_trash_parent_media']['default'] ?? null, 'media inventory fix plan keeps trash-parent delete opt-in disabled by default' );
npcink_abilities_toolkit_assert_same( false, $package_abilities['npcink-abilities-toolkit/build-media-inventory-fix-plan']['input_schema']['properties']['include_unattached_nonproduction_media']['default'] ?? null, 'media inventory fix plan keeps parentless nonproduction-media delete opt-in disabled by default' );
npcink_abilities_toolkit_assert_same( array( 'media.read' ), $package_abilities['npcink-abilities-toolkit/build-media-inventory-fix-plan']['required_scopes'] ?? array(), 'media inventory fix plan remains a read-scope planning ability' );
npcink_abilities_toolkit_assert_same( 'media_governance', $package_abilities['npcink-abilities-toolkit/build-media-adoption-enhancement-plan']['meta']['npcink_abilities_toolkit']['pack'] ?? '', 'media adoption enhancement plan is classified as media governance' );
npcink_abilities_toolkit_assert_true( ! empty( $package_abilities['npcink-abilities-toolkit/build-media-inventory-fix-plan']['agent_usage']['when_to_use'] ), 'media inventory fix plan exposes agent usage guidance' );
npcink_abilities_toolkit_assert_true( ! empty( $package_abilities['npcink-abilities-toolkit/build-media-inventory-fix-plan']['agent_usage']['stopping_points'] ), 'media inventory fix plan exposes agent stopping points' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-media-optimization-plan'] ), 'build-media-optimization-plan is registered as a read-only planning ability' );
npcink_abilities_toolkit_assert_same( array( 'media.read' ), $package_abilities['npcink-abilities-toolkit/build-media-optimization-plan']['required_scopes'] ?? array(), 'media optimization plan remains a read-scope planning ability' );
npcink_abilities_toolkit_assert_same( array( 'attachment_id', 'media_details_input', 'derivative_artifact' ), $package_abilities['npcink-abilities-toolkit/build-media-optimization-plan']['input_schema']['required'] ?? array(), 'media optimization plan requires metadata and artifact evidence' );
$media_derivative_artifact_schema = $package_abilities['npcink-abilities-toolkit/build-media-optimization-plan']['input_schema']['properties']['derivative_artifact'] ?? array();
npcink_abilities_toolkit_assert_same( array( 'artifact_id', 'expires_at', 'mime_type', 'format', 'width', 'height', 'filesize_bytes', 'sha256', 'suggested_filename', 'filename_basis', 'processing_warnings', 'transform_facts' ), $media_derivative_artifact_schema['required'] ?? array(), 'media optimization artifacts require the exact v3 local proposal descriptor' );
npcink_abilities_toolkit_assert_same( false, $media_derivative_artifact_schema['additionalProperties'] ?? null, 'media optimization artifacts reject undeclared descriptor fields at the public ability boundary' );
npcink_abilities_toolkit_assert_same( array( 'owner', 'strategy', 'final_sanitize_unique_required' ), $media_derivative_artifact_schema['properties']['filename_basis']['required'] ?? array(), 'media optimization artifact schemas require the complete nested WordPress filename authority basis' );
npcink_abilities_toolkit_assert_same( 26214400, $media_derivative_artifact_schema['properties']['filesize_bytes']['maximum'] ?? 0, 'media optimization artifact evidence stays within the shared 25 MiB local limit' );
npcink_abilities_toolkit_assert_same( 8192, $media_derivative_artifact_schema['properties']['width']['maximum'] ?? 0, 'media optimization artifact schemas match the Cloud 8192-pixel axis limit' );
$transform_facts_schema = $media_derivative_artifact_schema['properties']['transform_facts'] ?? array();
npcink_abilities_toolkit_assert_same( array_keys( npcink_abilities_toolkit_cloud_artifact_fixture()['transform_facts'] ), $transform_facts_schema['required'] ?? array(), 'Cloud v3 transformation facts require the complete ordered fact set' );
npcink_abilities_toolkit_assert_same( 'string', $transform_facts_schema['properties']['source_checksum']['type'] ?? '', 'Cloud v2 source checksums remain scalar strings in the WordPress ability schema' );
npcink_abilities_toolkit_assert_same( 'integer', $transform_facts_schema['properties']['source_width']['type'] ?? '', 'Cloud v2 dimensions remain scalar integers in the WordPress ability schema' );
npcink_abilities_toolkit_assert_same( 'boolean', $transform_facts_schema['properties']['decodable']['type'] ?? '', 'Cloud v2 decode evidence remains a scalar boolean in the WordPress ability schema' );
npcink_abilities_toolkit_assert_same( false, $transform_facts_schema['additionalProperties'] ?? null, 'Cloud v2 transformation facts reject undeclared fields' );
$cloud_adoption_output_schema = $package_abilities['npcink-abilities-toolkit/adopt-cloud-media-derivative']['output_schema']['properties'] ?? array();
npcink_abilities_toolkit_assert_same( array( true ), $cloud_adoption_output_schema['transfer_evidence']['properties']['image_decoded']['enum'] ?? array(), 'verified transfer schemas expose image_decoded as true-only evidence using the supported enum keyword' );
npcink_abilities_toolkit_assert_same( array( true ), $cloud_adoption_output_schema['delivery_ack']['properties']['checksum_verified']['enum'] ?? array(), 'delivery ACK schemas expose checksum_verified as true-only evidence using the supported enum keyword' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-media-optimization-plan']['input_schema']['properties']['file_name'] ), 'media optimization plan accepts a reviewed custom derivative file name' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-media-adoption-preflight-summary'] ), 'build-media-adoption-preflight-summary is registered as a read-only summary ability' );
npcink_abilities_toolkit_assert_same( array( 'media.read', 'post.read' ), $package_abilities['npcink-abilities-toolkit/build-media-adoption-preflight-summary']['required_scopes'] ?? array(), 'media adoption preflight summary reads media and bounded post references' );
npcink_abilities_toolkit_assert_same( array( 'attachment_id' ), $package_abilities['npcink-abilities-toolkit/build-media-adoption-preflight-summary']['input_schema']['required'] ?? array(), 'media adoption preflight summary only requires an attachment id' );
npcink_abilities_toolkit_assert_same( false, $package_abilities['npcink-abilities-toolkit/build-media-adoption-preflight-summary']['input_schema']['properties']['include_settings_scan']['default'] ?? null, 'media adoption preflight summary keeps settings scans disabled by default' );
npcink_abilities_toolkit_assert_same( 'media_governance', $package_abilities['npcink-abilities-toolkit/build-media-adoption-preflight-summary']['meta']['npcink_abilities_toolkit']['pack'] ?? '', 'media adoption preflight summary is classified as media governance' );
npcink_abilities_toolkit_assert_true( ! empty( $package_abilities['npcink-abilities-toolkit/build-media-adoption-preflight-summary']['agent_usage']['stopping_points'] ), 'media adoption preflight summary exposes agent stopping points' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-media-rename-plan'] ), 'build-media-rename-plan is registered as a read-only planning ability' );
npcink_abilities_toolkit_assert_same( array( 'media.read', 'post.read' ), $package_abilities['npcink-abilities-toolkit/build-media-rename-plan']['required_scopes'] ?? array(), 'media rename plan reads media and post references' );
npcink_abilities_toolkit_assert_same( array( 'attachment_id', 'target_file_name' ), $package_abilities['npcink-abilities-toolkit/build-media-rename-plan']['input_schema']['required'] ?? array(), 'media rename plan requires attachment and target filename' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-pattern-page-plan'] ), 'build-pattern-page-plan is registered as a read-only planning ability' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/route-content-intent'] ), 'route-content-intent is registered as a read-only intent routing ability' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/evaluate-gutenberg-recipe-suite'] ), 'evaluate-gutenberg-recipe-suite is registered as a read-only recipe evaluation ability' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/get-gutenberg-block-capability-catalog'] ), 'get-gutenberg-block-capability-catalog is registered as a read-only composition contract ability' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/compose-gutenberg-block-plan'] ), 'compose-gutenberg-block-plan is registered as a read-only composer repair ability' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/inspect-gutenberg-composition-contract'] ), 'inspect-gutenberg-composition-contract is registered as a read-only composition contract inspection ability' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/inspect-block-theme-surface'] ), 'inspect-block-theme-surface is registered as a read-only block theme inspection ability' );
npcink_abilities_toolkit_assert_same( array( 'post.read' ), $package_abilities['npcink-abilities-toolkit/route-content-intent']['required_scopes'] ?? array(), 'content intent router remains a read-scope ability' );
npcink_abilities_toolkit_assert_same( array( 'prompt' ), $package_abilities['npcink-abilities-toolkit/route-content-intent']['input_schema']['required'] ?? array(), 'content intent router only requires the natural-language prompt' );
npcink_abilities_toolkit_assert_same( array( 'auto', 'page', 'post', 'site_template', 'template_part', 'unsupported' ), $package_abilities['npcink-abilities-toolkit/route-content-intent']['input_schema']['properties']['target_hint']['enum'] ?? array(), 'content intent router exposes bounded target hints' );
npcink_abilities_toolkit_assert_same( array( 'post.read', 'site.read' ), $package_abilities['npcink-abilities-toolkit/evaluate-gutenberg-recipe-suite']['required_scopes'] ?? array(), 'Gutenberg recipe evaluation advertises post and Site Editor read scopes' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/evaluate-gutenberg-recipe-suite']['input_schema']['properties']['cases'] ), 'Gutenberg recipe evaluation accepts explicit test cases' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/evaluate-gutenberg-recipe-suite']['input_schema']['properties']['media_fixture'] ), 'Gutenberg recipe evaluation accepts a reviewed media fixture' );
npcink_abilities_toolkit_assert_same( array( 'post.read', 'site.read' ), $package_abilities['npcink-abilities-toolkit/get-gutenberg-block-capability-catalog']['required_scopes'] ?? array(), 'Gutenberg block capability catalog advertises post and Site Editor read scopes' );
npcink_abilities_toolkit_assert_same( array( 'all', 'page', 'post', 'template' ), $package_abilities['npcink-abilities-toolkit/get-gutenberg-block-capability-catalog']['input_schema']['properties']['surface']['enum'] ?? array(), 'Gutenberg block capability catalog exposes bounded surfaces' );
npcink_abilities_toolkit_assert_same( array( 'post.read', 'site.read' ), $package_abilities['npcink-abilities-toolkit/compose-gutenberg-block-plan']['required_scopes'] ?? array(), 'Gutenberg composer repair loop advertises post and Site Editor read scopes' );
npcink_abilities_toolkit_assert_same( array( 'prompt' ), $package_abilities['npcink-abilities-toolkit/compose-gutenberg-block-plan']['input_schema']['required'] ?? array(), 'Gutenberg composer repair loop only requires a natural-language prompt' );
npcink_abilities_toolkit_assert_same( array( 'auto', 'saas_landing', 'editorial_article', 'comparison_review', 'product_docs', 'block_theme_template' ), $package_abilities['npcink-abilities-toolkit/compose-gutenberg-block-plan']['input_schema']['properties']['composer_profile_id']['enum'] ?? array(), 'Gutenberg composer repair loop exposes bounded composer profiles' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/compose-gutenberg-block-plan']['input_schema']['properties']['plan_input'] ), 'Gutenberg composer repair loop accepts bounded planner input overrides' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/compose-gutenberg-block-plan']['input_schema']['properties']['repair_once'] ), 'Gutenberg composer repair loop exposes one-pass repair control' );
npcink_abilities_toolkit_assert_same( 'page_governance', $package_abilities['npcink-abilities-toolkit/compose-gutenberg-block-plan']['meta']['npcink_abilities_toolkit']['pack'] ?? '', 'Gutenberg composer repair loop is classified as page governance' );
npcink_abilities_toolkit_assert_same( array( 'post.read', 'site.read' ), $package_abilities['npcink-abilities-toolkit/inspect-gutenberg-composition-contract']['required_scopes'] ?? array(), 'Gutenberg composition contract inspection advertises post and Site Editor read scopes' );
npcink_abilities_toolkit_assert_same( array( 'post_content', 'site_editor_template', 'site_editor_template_part', 'blocks_input' ), $package_abilities['npcink-abilities-toolkit/inspect-gutenberg-composition-contract']['input_schema']['properties']['surface_kind']['enum'] ?? array(), 'Gutenberg composition contract inspection exposes bounded surface kinds' );
npcink_abilities_toolkit_assert_same( array( 'none', 'breadcrumbs' ), $package_abilities['npcink-abilities-toolkit/inspect-gutenberg-composition-contract']['input_schema']['properties']['placement_check']['enum'] ?? array(), 'Gutenberg composition contract inspection exposes bounded placement checks' );
npcink_abilities_toolkit_assert_same( 'page_governance', $package_abilities['npcink-abilities-toolkit/inspect-gutenberg-composition-contract']['meta']['npcink_abilities_toolkit']['pack'] ?? '', 'Gutenberg composition contract inspection is classified as page governance' );
$gutenberg_block_catalog = $core_read_package->get_gutenberg_block_capability_catalog( array( 'surface' => 'all' ) );
npcink_abilities_toolkit_assert_same( true, $gutenberg_block_catalog['success'] ?? null, 'get-gutenberg-block-capability-catalog returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'gutenberg_block_capability_catalog', $gutenberg_block_catalog['data']['artifact_type'] ?? '', 'Gutenberg block capability catalog declares its artifact type' );
npcink_abilities_toolkit_assert_same( 'gutenberg_native_v1', $gutenberg_block_catalog['data']['catalog_id'] ?? '', 'Gutenberg block capability catalog exposes the native catalog id' );
npcink_abilities_toolkit_assert_same( 'bounded_block_composition', $gutenberg_block_catalog['data']['composition_model'] ?? '', 'Gutenberg block capability catalog describes bounded block composition' );
npcink_abilities_toolkit_assert_same( false, $gutenberg_block_catalog['data']['direct_wordpress_write'] ?? null, 'Gutenberg block capability catalog never writes WordPress' );
npcink_abilities_toolkit_assert_same( false, $gutenberg_block_catalog['data']['commit_execution'] ?? null, 'Gutenberg block capability catalog never commits execution' );
npcink_abilities_toolkit_assert_same( false, $gutenberg_block_catalog['data']['core_html_allowed'] ?? true, 'Gutenberg block capability catalog forbids core/html' );
npcink_abilities_toolkit_assert_same( false, $gutenberg_block_catalog['data']['non_core_blocks_allowed'] ?? true, 'Gutenberg block capability catalog forbids non-core blocks' );
npcink_abilities_toolkit_assert_same( false, $gutenberg_block_catalog['data']['custom_css_allowed'] ?? true, 'Gutenberg block capability catalog forbids custom CSS as a base composition mechanism' );
npcink_abilities_toolkit_assert_same( 'gutenberg_native_block_composer_v1', $gutenberg_block_catalog['data']['composer_instruction']['instruction_id'] ?? '', 'Gutenberg block capability catalog exposes stable AI composer instructions' );
npcink_abilities_toolkit_assert_same( 'gutenberg_composer_profiles_v1', $gutenberg_block_catalog['data']['composer_profile_catalog_id'] ?? '', 'Gutenberg block capability catalog exposes the composer profile catalog id' );
npcink_abilities_toolkit_assert_true( isset( $gutenberg_block_catalog['data']['composer_profiles']['saas_landing'] ), 'Gutenberg block capability catalog includes the SaaS landing composer profile' );
npcink_abilities_toolkit_assert_true( isset( $gutenberg_block_catalog['data']['composer_profiles']['editorial_article'] ), 'Gutenberg block capability catalog includes the editorial article composer profile' );
npcink_abilities_toolkit_assert_true( isset( $gutenberg_block_catalog['data']['composer_profiles']['comparison_review'] ), 'Gutenberg block capability catalog includes the comparison review composer profile' );
npcink_abilities_toolkit_assert_true( isset( $gutenberg_block_catalog['data']['composer_profiles']['product_docs'] ), 'Gutenberg block capability catalog includes the product docs composer profile' );
npcink_abilities_toolkit_assert_true( isset( $gutenberg_block_catalog['data']['composer_profiles']['block_theme_template'] ), 'Gutenberg block capability catalog includes the block theme template composer profile' );
npcink_abilities_toolkit_assert_same( 'high', $gutenberg_block_catalog['data']['composer_profiles']['saas_landing']['quality_targets']['section_variance'] ?? '', 'SaaS landing profile encodes high section variance' );
npcink_abilities_toolkit_assert_true( in_array( 'core/html', $gutenberg_block_catalog['data']['composer_profiles']['saas_landing']['forbidden_outputs'] ?? array(), true ), 'SaaS landing profile forbids core/html output' );
npcink_abilities_toolkit_assert_true( in_array( 'core/html', $gutenberg_block_catalog['data']['composer_instruction']['ai_must_not_choose'] ?? array(), true ), 'Gutenberg block composer instructions forbid raw HTML blocks' );
npcink_abilities_toolkit_assert_same( 'inspect_catalog', $gutenberg_block_catalog['data']['recommended_composer_flow'][0]['step'] ?? '', 'Gutenberg block capability catalog tells composers to inspect the catalog first' );
npcink_abilities_toolkit_assert_same( 'bounded_template_anchor_placement', $gutenberg_block_catalog['data']['template_placement_standards']['breadcrumbs']['placement_model'] ?? '', 'Gutenberg block capability catalog exposes bounded template placement standards' );
npcink_abilities_toolkit_assert_true( in_array( 'core/post-title', $gutenberg_block_catalog['data']['template_placement_standards']['breadcrumbs']['preferred_anchor_blocks'] ?? array(), true ), 'Gutenberg block capability catalog allows post title anchors for breadcrumbs' );
npcink_abilities_toolkit_assert_true( in_array( 'core/query-title', $gutenberg_block_catalog['data']['template_placement_standards']['breadcrumbs']['preferred_anchor_blocks'] ?? array(), true ), 'Gutenberg block capability catalog allows query title anchors for breadcrumbs' );
npcink_abilities_toolkit_assert_true( in_array( 'core/group', $gutenberg_block_catalog['data']['allowed_block_names'] ?? array(), true ), 'Gutenberg block capability catalog allows core/group composition' );
npcink_abilities_toolkit_assert_true( in_array( 'core/image', $gutenberg_block_catalog['data']['allowed_block_names'] ?? array(), true ), 'Gutenberg block capability catalog allows core/image composition' );
npcink_abilities_toolkit_assert_true( in_array( 'core/media-text', $gutenberg_block_catalog['data']['allowed_block_names'] ?? array(), true ), 'Gutenberg block capability catalog allows core/media-text composition' );
npcink_abilities_toolkit_assert_true( in_array( 'core/html', $gutenberg_block_catalog['data']['forbidden_block_names'] ?? array(), true ), 'Gutenberg block capability catalog explicitly forbids core/html' );
npcink_abilities_toolkit_assert_same( true, $gutenberg_block_catalog['data']['responsive_rules']['columns_must_stack_on_mobile'] ?? null, 'Gutenberg block capability catalog requires mobile-stacked columns' );
npcink_abilities_toolkit_assert_same( 'fail_closed', $gutenberg_block_catalog['data']['repair_policy']['non_core_block'] ?? '', 'Gutenberg block capability catalog fails closed on non-core blocks' );
$valid_template_contract_inspection = $core_read_package->inspect_gutenberg_composition_contract(
	array(
		'surface_kind'    => 'site_editor_template',
		'post_type'       => 'wp_template',
		'slug'            => 'single',
		'placement_check' => 'breadcrumbs',
		'blocks'          => array(
			array(
				'blockName'   => 'core/template-part',
				'attrs'       => array( 'slug' => 'header' ),
				'innerBlocks' => array(),
			),
			array(
				'blockName'   => 'core/group',
				'attrs'       => array( 'tagName' => 'main' ),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/group',
						'attrs'       => array( 'className' => 'openclaw-breadcrumbs' ),
						'innerBlocks' => array(
							array(
								'blockName'    => 'core/paragraph',
								'attrs'        => array(),
								'innerHTML'    => '<p><a href="/">Home</a> / Current item</p>',
								'innerContent' => array( '<p><a href="/">Home</a> / Current item</p>' ),
								'innerBlocks'  => array(),
							),
						),
					),
					array(
						'blockName'   => 'core/post-title',
						'attrs'       => array(),
						'innerBlocks' => array(),
					),
					array(
						'blockName'   => 'core/post-content',
						'attrs'       => array(),
						'innerBlocks' => array(),
					),
				),
			),
			array(
				'blockName'   => 'core/template-part',
				'attrs'       => array( 'slug' => 'footer' ),
				'innerBlocks' => array(),
			),
		),
	)
);
npcink_abilities_toolkit_assert_same( true, $valid_template_contract_inspection['success'] ?? null, 'inspect-gutenberg-composition-contract returns a success envelope for proposed template blocks' );
npcink_abilities_toolkit_assert_same( 'gutenberg_composition_contract_inspection', $valid_template_contract_inspection['data']['artifact_type'] ?? '', 'inspect-gutenberg-composition-contract declares its artifact type' );
npcink_abilities_toolkit_assert_same( 'site_editor_template', $valid_template_contract_inspection['data']['block_editor_surface']['surface_kind'] ?? '', 'composition contract inspection identifies Site Editor template surfaces' );
npcink_abilities_toolkit_assert_same( 'pass', $valid_template_contract_inspection['data']['contract_status'] ?? '', 'composition contract inspection passes valid template blocks' );
npcink_abilities_toolkit_assert_same( 'pass', $valid_template_contract_inspection['data']['composition_contract']['contract_status'] ?? '', 'composition contract inspection passes the block composition contract' );
npcink_abilities_toolkit_assert_same( 'pass', $valid_template_contract_inspection['data']['template_placement_contract']['contract_status'] ?? '', 'composition contract inspection passes valid breadcrumb placement' );
npcink_abilities_toolkit_assert_same( 'core/post-title', $valid_template_contract_inspection['data']['template_placement_contract']['placements'][0]['inserted_before'] ?? '', 'composition contract inspection records the matched title anchor' );
npcink_abilities_toolkit_assert_same( false, $valid_template_contract_inspection['data']['direct_wordpress_write'] ?? null, 'composition contract inspection never writes WordPress' );
npcink_abilities_toolkit_assert_same( false, $valid_template_contract_inspection['data']['commit_execution'] ?? null, 'composition contract inspection never commits execution' );
npcink_abilities_toolkit_assert_true( in_array( 'no_changes_required', $valid_template_contract_inspection['data']['recommended_next_actions'] ?? array(), true ), 'composition contract inspection reports no change when contracts pass' );
$article_title_stack_contract_inspection = $core_read_package->inspect_gutenberg_composition_contract(
	array(
		'surface_kind'    => 'site_editor_template',
		'post_type'       => 'wp_template',
		'slug'            => 'single',
		'placement_check' => 'breadcrumbs',
		'blocks'          => array(
			array(
				'blockName'   => 'core/template-part',
				'attrs'       => array( 'slug' => 'header' ),
				'innerBlocks' => array(),
			),
			array(
				'blockName'   => 'core/group',
				'attrs'       => array( 'tagName' => 'main' ),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/group',
						'attrs'       => array( 'className' => 'openclaw-breadcrumbs' ),
						'innerBlocks' => array(),
					),
					array(
						'blockName'   => 'core/group',
						'attrs'       => array( 'className' => 'openclaw-template-title-stack' ),
						'innerBlocks' => array(
							array(
								'blockName'   => 'core/post-title',
								'attrs'       => array(),
								'innerBlocks' => array(),
							),
						),
					),
					array(
						'blockName'   => 'core/post-content',
						'attrs'       => array(),
						'innerBlocks' => array(),
					),
				),
			),
			array(
				'blockName'   => 'core/template-part',
				'attrs'       => array( 'slug' => 'footer' ),
				'innerBlocks' => array(),
			),
		),
	)
);
npcink_abilities_toolkit_assert_same( 'pass', $article_title_stack_contract_inspection['data']['contract_status'] ?? '', 'composition contract inspection passes breadcrumbs before an article title stack' );
npcink_abilities_toolkit_assert_same( 'pass', $article_title_stack_contract_inspection['data']['template_placement_contract']['contract_status'] ?? '', 'composition contract inspection accepts title-stack breadcrumb placement' );
$homepage_contract_inspection = $core_read_package->inspect_gutenberg_composition_contract(
	array(
		'surface_kind'       => 'site_editor_template',
		'post_type'          => 'wp_template',
		'slug'               => 'front-page',
		'placement_check'    => 'breadcrumbs',
		'show_on_home_page'  => false,
		'blocks'             => array(
			array(
				'blockName'   => 'core/template-part',
				'attrs'       => array( 'slug' => 'header' ),
				'innerBlocks' => array(),
			),
			array(
				'blockName'   => 'core/group',
				'attrs'       => array( 'tagName' => 'main' ),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/group',
						'attrs'       => array( 'className' => 'openclaw-breadcrumbs' ),
						'innerBlocks' => array(),
					),
					array(
						'blockName'   => 'core/post-title',
						'attrs'       => array(),
						'innerBlocks' => array(),
					),
				),
			),
		),
	)
);
npcink_abilities_toolkit_assert_same( 'needs_revision', $homepage_contract_inspection['data']['contract_status'] ?? '', 'composition contract inspection flags homepage breadcrumbs when home display is disabled' );
npcink_abilities_toolkit_assert_true( in_array( 'template_placement_contract_failed', $homepage_contract_inspection['data']['violation_codes'] ?? array(), true ), 'composition contract inspection reports homepage breadcrumb placement violations' );
npcink_abilities_toolkit_assert_true( in_array( 'build_block_theme_site_plan', $homepage_contract_inspection['data']['recommended_next_actions'] ?? array(), true ), 'composition contract inspection recommends the Site Editor planner for homepage placement fixes' );
$invalid_contract_inspection = $core_read_package->inspect_gutenberg_composition_contract(
	array(
		'surface_kind'    => 'post_content',
		'post_type'       => 'page',
		'placement_check' => 'none',
		'blocks'          => array(
			array(
				'blockName'    => 'core/html',
				'attrs'        => array(),
				'innerHTML'    => '<div style="position:absolute">Unsafe raw section</div>',
				'innerContent' => array( '<div style="position:absolute">Unsafe raw section</div>' ),
				'innerBlocks'  => array(),
			),
		),
	)
);
npcink_abilities_toolkit_assert_same( true, $invalid_contract_inspection['success'] ?? null, 'inspect-gutenberg-composition-contract returns a success envelope for invalid proposed blocks' );
npcink_abilities_toolkit_assert_same( 'needs_revision', $invalid_contract_inspection['data']['contract_status'] ?? '', 'composition contract inspection rejects invalid proposed blocks' );
npcink_abilities_toolkit_assert_true( in_array( 'core/html', $invalid_contract_inspection['data']['composition_contract']['forbidden_block_names'] ?? array(), true ), 'composition contract inspection reports forbidden core/html blocks' );
npcink_abilities_toolkit_assert_true( in_array( 'composition_contract_failed', $invalid_contract_inspection['data']['violation_codes'] ?? array(), true ), 'composition contract inspection reports a composition violation code' );
npcink_abilities_toolkit_assert_true( in_array( 'revise_block_composition', $invalid_contract_inspection['data']['recommended_next_actions'] ?? array(), true ), 'composition contract inspection recommends composition revision when blocks violate the catalog' );
npcink_abilities_toolkit_assert_same( array( 'site.read' ), $package_abilities['npcink-abilities-toolkit/inspect-block-theme-surface']['required_scopes'] ?? array(), 'block theme surface inspection advertises Site Editor read scope' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/inspect-block-theme-surface']['input_schema']['properties']['target_templates'] ), 'block theme surface inspection accepts target templates' );
npcink_abilities_toolkit_assert_same( 1, $package_abilities['npcink-abilities-toolkit/build-article-block-plan']['input_schema']['properties']['target_post_id']['minimum'] ?? null, 'article block plan accepts a bounded target post id for existing draft updates' );
npcink_abilities_toolkit_assert_same( array( 'post.read' ), $package_abilities['npcink-abilities-toolkit/build-pattern-page-plan']['required_scopes'] ?? array(), 'pattern page plan remains a read-scope planning ability' );
npcink_abilities_toolkit_assert_same( array( 'pattern_id' ), $package_abilities['npcink-abilities-toolkit/build-pattern-page-plan']['input_schema']['required'] ?? array(), 'pattern page plan only requires the pattern id and can infer title from variables' );
npcink_abilities_toolkit_assert_same( 1, $package_abilities['npcink-abilities-toolkit/build-pattern-page-plan']['input_schema']['properties']['target_post_id']['minimum'] ?? null, 'pattern page plan accepts a bounded target page id for existing draft updates' );
npcink_abilities_toolkit_assert_same( array( 'landing_standard' ), $package_abilities['npcink-abilities-toolkit/build-pattern-page-plan']['input_schema']['properties']['responsive_profile']['enum'] ?? array(), 'pattern page plan exposes a bounded responsive profile' );
npcink_abilities_toolkit_assert_same( array( 'minimal-dark-light', 'editorial-accent' ), $package_abilities['npcink-abilities-toolkit/build-pattern-page-plan']['input_schema']['properties']['color_story']['enum'] ?? array(), 'pattern page plan exposes bounded color stories' );
npcink_abilities_toolkit_assert_same( array( 'balanced' ), $package_abilities['npcink-abilities-toolkit/build-pattern-page-plan']['input_schema']['properties']['visual_density']['enum'] ?? array(), 'pattern page plan exposes a bounded visual density' );
npcink_abilities_toolkit_assert_same( array( 'mock_or_existing_media', 'existing_media_url' ), $package_abilities['npcink-abilities-toolkit/build-pattern-page-plan']['input_schema']['properties']['media_strategy']['enum'] ?? array(), 'pattern page plan exposes bounded media strategies' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-pattern-page-plan']['input_schema']['properties']['research_brief'] ), 'pattern page plan accepts an optional landing page research brief' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-pattern-page-plan']['input_schema']['properties']['review_feedback'] ), 'pattern page plan accepts optional review feedback for revision loops' );
	npcink_abilities_toolkit_assert_same( array( 'center-title-two-cards', 'left-title-two-cards' ), $package_abilities['npcink-abilities-toolkit/build-pattern-page-plan']['input_schema']['properties']['section_variant_hints']['properties']['comparison']['enum'] ?? array(), 'pattern page plan exposes bounded section variant hints' );
	npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/review-pattern-page'] ), 'review-pattern-page is registered as a read-only page quality review ability' );
	npcink_abilities_toolkit_assert_same( array( 'post.read' ), $package_abilities['npcink-abilities-toolkit/review-pattern-page']['required_scopes'] ?? array(), 'pattern page review remains a read-scope ability' );
	npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/review-pattern-page']['input_schema']['properties']['post_id'] ), 'pattern page review accepts a post id' );
	npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/review-pattern-page']['input_schema']['properties']['blocks'] ), 'pattern page review accepts proposed blocks before write' );
	npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/review-block-editor-surface'] ), 'review-block-editor-surface is registered as a read-only block surface review ability' );
	npcink_abilities_toolkit_assert_same( array( 'post.read', 'site.read' ), $package_abilities['npcink-abilities-toolkit/review-block-editor-surface']['required_scopes'] ?? array(), 'block surface review advertises post and Site Editor read scopes' );
	npcink_abilities_toolkit_assert_same( array( 'post_content', 'site_editor_template', 'site_editor_template_part', 'blocks_input' ), $package_abilities['npcink-abilities-toolkit/review-block-editor-surface']['input_schema']['properties']['surface_kind']['enum'] ?? array(), 'block surface review exposes bounded surface kinds' );
	npcink_abilities_toolkit_assert_same( array( 'post', 'page', 'wp_template', 'wp_template_part' ), $package_abilities['npcink-abilities-toolkit/review-block-editor-surface']['input_schema']['properties']['post_type']['enum'] ?? array(), 'block surface review exposes bounded post types' );
	npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/get-block-theme-context'] ), 'get-block-theme-context is registered as a read-only Site Editor context ability' );
	npcink_abilities_toolkit_assert_same( array( 'site.read' ), $package_abilities['npcink-abilities-toolkit/get-block-theme-context']['required_scopes'] ?? array(), 'block theme context remains a site read ability' );
	npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-block-theme-site-plan'] ), 'build-block-theme-site-plan is registered as a read-only planning ability' );
	npcink_abilities_toolkit_assert_same( array( 'site.read' ), $package_abilities['npcink-abilities-toolkit/build-block-theme-site-plan']['required_scopes'] ?? array(), 'block theme site plan remains a read-scope planning ability' );
	npcink_abilities_toolkit_assert_same( array( 'add_breadcrumbs', 'customize_template_layout' ), $package_abilities['npcink-abilities-toolkit/build-block-theme-site-plan']['input_schema']['properties']['intent']['enum'] ?? array(), 'block theme site plan exposes breadcrumbs and bounded template layout intents' );
	npcink_abilities_toolkit_assert_same( array( 'auto', 'article_standard', 'page_standard', 'homepage_landing' ), $package_abilities['npcink-abilities-toolkit/build-block-theme-site-plan']['input_schema']['properties']['layout_profile']['enum'] ?? array(), 'block theme site plan exposes bounded layout profiles' );
	npcink_abilities_toolkit_assert_same( array( 'single', 'page', 'front-page', 'home', 'index' ), $package_abilities['npcink-abilities-toolkit/build-block-theme-site-plan']['input_schema']['properties']['target_templates']['items']['enum'] ?? array(), 'block theme site plan accepts Core-compatible content templates including front-page' );
	npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/update-template-blocks'] ), 'update-template-blocks is registered as a governed write ability' );
	npcink_abilities_toolkit_assert_same( array( 'site.write' ), $package_abilities['npcink-abilities-toolkit/update-template-blocks']['required_scopes'] ?? array(), 'template block updates require site.write scope' );
	npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/upsert-template-blocks'] ), 'upsert-template-blocks is registered as a governed template override write ability' );
	npcink_abilities_toolkit_assert_same( array( 'site.write' ), $package_abilities['npcink-abilities-toolkit/upsert-template-blocks']['required_scopes'] ?? array(), 'template override upserts require site.write scope' );
	npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/update-template-part-blocks'] ), 'update-template-part-blocks is registered as a governed write ability' );
npcink_abilities_toolkit_assert_same( array( 'owned', 'ai_generated', 'stock', 'external', 'test' ), $package_abilities['npcink-abilities-toolkit/update-media-details']['input_schema']['properties']['source_type']['enum'] ?? array(), 'update-media-details accepts canonical media source_type values' );
npcink_abilities_toolkit_assert_same( 'external', $package_abilities['npcink-abilities-toolkit/upload-media-from-url']['input_schema']['properties']['source_type']['default'] ?? '', 'upload-media-from-url defaults remote imports to external source type' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/upload-media-from-url']['input_schema']['properties']['file_name'] ), 'upload-media-from-url accepts an approved custom media file name' );
npcink_abilities_toolkit_assert_same( array( 'webp', 'jpeg', 'png' ), $package_abilities['npcink-abilities-toolkit/optimize-media-asset']['input_schema']['properties']['preferred_format']['enum'] ?? array(), 'optimize-media-asset exposes bounded derivative formats' );
npcink_abilities_toolkit_assert_same( 82, $package_abilities['npcink-abilities-toolkit/optimize-media-asset']['input_schema']['properties']['quality']['default'] ?? null, 'optimize-media-asset defaults to quality 82' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/optimize-media-asset']['output_schema']['properties']['derivative_url'] ), 'optimize-media-asset exposes a top-level derivative_url for batch output references' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/inspect-media-asset']['output_schema']['properties']['data']['properties']['storage'] ), 'inspect-media-asset exposes media storage preflight evidence' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-media-derivative-cloud-request']['output_schema']['properties']['data']['properties']['storage'] ), 'media derivative cloud request exposes storage preflight evidence' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-media-optimization-plan']['input_schema']['properties']['storage_preflight'] ), 'media optimization plans accept reviewed storage preflight evidence' );
npcink_abilities_toolkit_assert_true( ! isset( $package_abilities['npcink-abilities-toolkit/replace-media-file']['input_schema']['properties']['mode'] ), 'replace-media-file does not expose media restore modes' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit-backup', $package_abilities['npcink-abilities-toolkit/replace-media-file']['input_schema']['properties']['backup_suffix']['default'] ?? '', 'replace-media-file defaults to explicit Npcink backup suffix' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/replace-media-file']['input_schema']['properties']['expected_storage_provider'] ), 'replace-media-file accepts storage provider drift guards' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/replace-media-file']['output_schema']['properties']['content_reference_repairs'] ), 'replace-media-file exposes post content reference repair preview evidence' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/replace-media-file']['output_schema']['properties']['verification'] ), 'replace-media-file exposes execution verification summary' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/list-media-backups'] ), 'list-media-backups is registered as a read-only media history ability' );
npcink_abilities_toolkit_assert_same( array( 'attachment_id' ), $package_abilities['npcink-abilities-toolkit/list-media-backups']['input_schema']['required'] ?? array(), 'list-media-backups requires attachment id' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/restore-media-backup'] ), 'restore-media-backup is registered as a governed write ability' );
npcink_abilities_toolkit_assert_same( array( 'attachment_id', 'backup_id' ), $package_abilities['npcink-abilities-toolkit/restore-media-backup']['input_schema']['required'] ?? array(), 'restore-media-backup requires attachment and backup id' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/restore-media-backup']['input_schema']['properties']['expected_storage_adapter'] ), 'restore-media-backup accepts storage adapter drift guards' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/restore-media-backup']['output_schema']['properties']['content_reference_repairs'] ), 'restore-media-backup exposes post content reference repair evidence' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/restore-media-backup']['output_schema']['properties']['verification'] ), 'restore-media-backup exposes execution verification summary' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/rename-media-file'] ), 'rename-media-file is registered as a local write ability' );
npcink_abilities_toolkit_assert_same( array( 'attachment_id', 'target_file_name' ), $package_abilities['npcink-abilities-toolkit/rename-media-file']['input_schema']['required'] ?? array(), 'rename-media-file requires attachment and target filename' );
npcink_abilities_toolkit_assert_same( array( 'fail', 'unique' ), $package_abilities['npcink-abilities-toolkit/rename-media-file']['input_schema']['properties']['conflict_mode']['enum'] ?? array(), 'rename-media-file exposes bounded conflict modes' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit-rename-backup', $package_abilities['npcink-abilities-toolkit/rename-media-file']['input_schema']['properties']['backup_suffix']['default'] ?? '', 'rename-media-file defaults to explicit rename backup suffix' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/adopt-cloud-media-derivative'] ), 'adopt-cloud-media-derivative is registered as a local write ability' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit-cloud-backup', $package_abilities['npcink-abilities-toolkit/adopt-cloud-media-derivative']['input_schema']['properties']['backup_suffix']['default'] ?? '', 'adopt-cloud-media-derivative defaults to explicit Cloud backup suffix' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/adopt-cloud-media-derivative']['input_schema']['properties']['file_name'] ), 'adopt-cloud-media-derivative accepts an approved custom derivative file name' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/adopt-cloud-media-derivative']['input_schema']['properties']['expected_content_reference_post_ids'] ), 'adopt-cloud-media-derivative accepts reviewed content reference post id expectations' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/adopt-cloud-media-derivative']['input_schema']['properties']['expected_content_reference_replacement_count'] ), 'adopt-cloud-media-derivative accepts reviewed content reference replacement count expectations' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/adopt-cloud-media-derivative']['input_schema']['properties']['storage_preflight'] ), 'adopt-cloud-media-derivative accepts reviewed storage preflight evidence' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/adopt-cloud-media-derivative']['output_schema']['properties']['proposed_filename'] ) && isset( $package_abilities['npcink-abilities-toolkit/adopt-cloud-media-derivative']['output_schema']['properties']['filename_policy'] ), 'adopt-cloud-media-derivative exposes filename proposal evidence in its output schema' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/adopt-cloud-media-derivative']['output_schema']['properties']['content_reference_repairs'] ), 'adopt-cloud-media-derivative exposes post content reference repair preview evidence' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/adopt-cloud-media-derivative']['output_schema']['properties']['verification'] ), 'adopt-cloud-media-derivative exposes execution verification summary' );
npcink_abilities_toolkit_assert_same( array( 'attachment_id', 'derivative_artifact' ), $package_abilities['npcink-abilities-toolkit/adopt-cloud-media-derivative']['input_schema']['required'] ?? array(), 'adopt-cloud-media-derivative requires attachment and artifact evidence' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-media-reference-repair-plan'] ), 'build-media-reference-repair-plan is registered as a read-only planning ability' );
npcink_abilities_toolkit_assert_same( array( 'attachment_id' ), $package_abilities['npcink-abilities-toolkit/build-media-reference-repair-plan']['input_schema']['required'] ?? array(), 'build-media-reference-repair-plan requires attachment id' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-media-adoption-enhancement-plan'] ), 'build-media-adoption-enhancement-plan is registered as a read-only planning ability' );
npcink_abilities_toolkit_assert_same( array( 'media.read', 'post.read' ), $package_abilities['npcink-abilities-toolkit/build-media-adoption-enhancement-plan']['required_scopes'] ?? array(), 'media adoption enhancement plan reads media and post references' );
npcink_abilities_toolkit_assert_same( array( 'url' ), $package_abilities['npcink-abilities-toolkit/build-media-adoption-enhancement-plan']['input_schema']['required'] ?? array(), 'media adoption enhancement plan requires a reviewed remote URL' );
npcink_abilities_toolkit_assert_same( array( 'webp', 'jpeg', 'png' ), $package_abilities['npcink-abilities-toolkit/build-media-adoption-enhancement-plan']['input_schema']['properties']['preferred_format']['enum'] ?? array(), 'media adoption enhancement plan exposes bounded local derivative formats' );
npcink_abilities_toolkit_assert_true( ! isset( $package_abilities['npcink-abilities-toolkit/build-media-adoption-enhancement-plan']['input_schema']['properties']['commit'] ), 'media adoption enhancement plan does not expose a commit control' );
npcink_abilities_toolkit_assert_true( ! isset( $package_abilities['npcink-abilities-toolkit/build-media-adoption-enhancement-plan']['input_schema']['properties']['dry_run'] ), 'media adoption enhancement plan does not expose write dry_run control' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-image-candidate-review-artifact'] ), 'build-image-candidate-review-artifact is registered as a read-only review ability' );
npcink_abilities_toolkit_assert_same( array( 'media.read' ), $package_abilities['npcink-abilities-toolkit/build-image-candidate-review-artifact']['required_scopes'] ?? array(), 'image candidate review artifact only reads media candidate evidence' );
npcink_abilities_toolkit_assert_same( 12, $package_abilities['npcink-abilities-toolkit/build-image-candidate-review-artifact']['input_schema']['properties']['image_candidates']['maxItems'] ?? null, 'image candidate review artifact bounds candidate evidence input' );
npcink_abilities_toolkit_assert_true( ! isset( $package_abilities['npcink-abilities-toolkit/build-image-candidate-review-artifact']['input_schema']['properties']['commit'] ), 'image candidate review artifact does not expose a commit control' );
npcink_abilities_toolkit_assert_true( ! isset( $package_abilities['npcink-abilities-toolkit/build-image-candidate-review-artifact']['input_schema']['properties']['provider'] ), 'image candidate review artifact does not expose provider runtime selection' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-media-alt-caption-review-set'] ), 'build-media-alt-caption-review-set is registered as a read-only review ability' );
npcink_abilities_toolkit_assert_same( 'media_governance', $package_abilities['npcink-abilities-toolkit/build-media-alt-caption-review-set']['meta']['npcink_abilities_toolkit']['pack'] ?? '', 'media ALT/caption review set is classified as media governance' );
npcink_abilities_toolkit_assert_same( array( 'media.read' ), $package_abilities['npcink-abilities-toolkit/build-media-alt-caption-review-set']['required_scopes'] ?? array(), 'media ALT/caption review set only reads supplied media evidence' );
npcink_abilities_toolkit_assert_true( ! isset( $package_abilities['npcink-abilities-toolkit/build-media-alt-caption-review-set']['input_schema']['properties']['commit'] ), 'media ALT/caption review set does not expose a commit control' );
npcink_abilities_toolkit_assert_true( ! isset( $package_abilities['npcink-abilities-toolkit/build-media-alt-caption-review-set']['input_schema']['properties']['provider'] ), 'media ALT/caption review set does not expose provider runtime selection' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-media-alt-apply-plan'] ), 'build-media-alt-apply-plan is registered as a read-only governed planner' );
npcink_abilities_toolkit_assert_same( 'media_governance', $package_abilities['npcink-abilities-toolkit/build-media-alt-apply-plan']['meta']['npcink_abilities_toolkit']['pack'] ?? '', 'media ALT apply plan is classified as media governance' );
npcink_abilities_toolkit_assert_same( array( 'attachment_id', 'alt', 'expected_current_alt', 'operator_visual_review_confirmed', 'review_set_contract' ), $package_abilities['npcink-abilities-toolkit/build-media-alt-apply-plan']['input_schema']['required'] ?? array(), 'media ALT apply plan requires reviewed value, old value, visual confirmation, and review contract' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/update-media-details']['input_schema']['properties']['expected_current_alt'] ), 'update-media-details exposes the optional ALT old-value guard' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/update-media-details']['input_schema']['properties']['operator_visual_review_confirmed'] ), 'update-media-details exposes guarded ALT visual confirmation' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-image-candidate-adoption-plan'] ), 'build-image-candidate-adoption-plan is registered as a read-only planning ability' );
npcink_abilities_toolkit_assert_same( array( 'media.read', 'post.read' ), $package_abilities['npcink-abilities-toolkit/build-image-candidate-adoption-plan']['required_scopes'] ?? array(), 'image candidate adoption plan reads media and post references' );
npcink_abilities_toolkit_assert_same( array(), $package_abilities['npcink-abilities-toolkit/build-image-candidate-adoption-plan']['input_schema']['required'] ?? array(), 'image candidate adoption plan accepts image_candidate or direct URL input' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-image-candidate-adoption-plan']['input_schema']['properties']['set_featured_image'] ), 'image candidate adoption plan exposes optional featured image planning' );
npcink_abilities_toolkit_assert_true( ! isset( $package_abilities['npcink-abilities-toolkit/build-image-candidate-adoption-plan']['input_schema']['properties']['commit'] ), 'image candidate adoption plan does not expose a commit control' );
npcink_abilities_toolkit_assert_true( ! isset( $package_abilities['npcink-abilities-toolkit/build-image-candidate-adoption-plan']['input_schema']['properties']['dry_run'] ), 'image candidate adoption plan does not expose write dry_run control' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-media-settings-reference-repair-plan'] ), 'build-media-settings-reference-repair-plan is registered as a read-only planning ability' );
npcink_abilities_toolkit_assert_same( array( 'attachment_id' ), $package_abilities['npcink-abilities-toolkit/build-media-settings-reference-repair-plan']['input_schema']['required'] ?? array(), 'build-media-settings-reference-repair-plan requires attachment id' );
npcink_abilities_toolkit_assert_same( array( 'svg', 'gif', 'ico', 'pdf' ), $package_abilities['npcink-abilities-toolkit/build-media-settings-reference-repair-plan']['input_schema']['properties']['excluded_formats']['default'] ?? array(), 'media settings reference repair defaults excluded formats' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/patch-setting-value'] ), 'patch-setting-value is registered as a local write ability' );
npcink_abilities_toolkit_assert_same( array( 'target_type', 'target_name', 'operations' ), $package_abilities['npcink-abilities-toolkit/patch-setting-value']['input_schema']['required'] ?? array(), 'patch-setting-value requires a setting target and operations' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/resolve-media-attachment-by-url'] ), 'resolve-media-attachment-by-url is registered as a read-only media resolver' );
npcink_abilities_toolkit_assert_same( array( 'media.read' ), $package_abilities['npcink-abilities-toolkit/resolve-media-attachment-by-url']['required_scopes'] ?? array(), 'resolve-media-attachment-by-url remains a read-scope ability' );
npcink_abilities_toolkit_assert_same( array( 'url' ), $package_abilities['npcink-abilities-toolkit/resolve-media-attachment-by-url']['input_schema']['required'] ?? array(), 'resolve-media-attachment-by-url requires a URL' );
npcink_abilities_toolkit_assert_same( 20, $package_abilities['npcink-abilities-toolkit/resolve-media-attachment-by-url']['input_schema']['properties']['max_candidates']['maximum'] ?? null, 'resolve-media-attachment-by-url bounds candidates to 20' );
npcink_abilities_toolkit_assert_true( ! isset( $package_abilities['npcink-abilities-toolkit/resolve-media-attachment-by-url']['input_schema']['properties']['commit'] ), 'resolve-media-attachment-by-url does not expose a commit control' );
npcink_abilities_toolkit_assert_true( ! isset( $package_abilities['npcink-abilities-toolkit/resolve-media-attachment-by-url']['input_schema']['properties']['dry_run'] ), 'resolve-media-attachment-by-url does not expose write dry_run control' );
npcink_abilities_toolkit_assert_same( 1920, $package_abilities['npcink-abilities-toolkit/inspect-media-asset']['input_schema']['properties']['target_max_width']['default'] ?? null, 'inspect-media-asset defaults to a 1920px max width target' );
npcink_abilities_toolkit_assert_same( array( 'webp', 'avif', 'original' ), $package_abilities['npcink-abilities-toolkit/inspect-media-asset']['input_schema']['properties']['preferred_format']['enum'] ?? array(), 'inspect-media-asset exposes bounded preferred output formats' );
npcink_abilities_toolkit_assert_same( array( 'media.read' ), $package_abilities['npcink-abilities-toolkit/build-media-derivative-cloud-request']['required_scopes'] ?? array(), 'media derivative cloud request remains a read-scope planning ability' );
npcink_abilities_toolkit_assert_same( array( 'attachment_id' ), $package_abilities['npcink-abilities-toolkit/build-media-derivative-cloud-request']['input_schema']['required'] ?? array(), 'media derivative cloud request requires an attachment id' );
npcink_abilities_toolkit_assert_same( array( 'webp', 'avif', 'jpeg', 'png', 'original' ), $package_abilities['npcink-abilities-toolkit/build-media-derivative-cloud-request']['input_schema']['properties']['preferred_format']['enum'] ?? array(), 'media derivative cloud request exposes bounded preferred output formats' );
npcink_abilities_toolkit_assert_same( 82, $package_abilities['npcink-abilities-toolkit/build-media-derivative-cloud-request']['input_schema']['properties']['quality']['default'] ?? null, 'media derivative cloud request defaults to quality 82' );
npcink_abilities_toolkit_assert_same( array( 'aspect_ratio' ), $package_abilities['npcink-abilities-toolkit/build-media-derivative-cloud-request']['input_schema']['properties']['crop']['properties']['type']['enum'] ?? array(), 'media derivative cloud request supports bounded aspect-ratio crop plans' );
npcink_abilities_toolkit_assert_same( array( 'top_left', 'top', 'top_right', 'left', 'center', 'right', 'bottom_left', 'bottom', 'bottom_right' ), $package_abilities['npcink-abilities-toolkit/build-media-derivative-cloud-request']['input_schema']['properties']['crop']['properties']['position']['enum'] ?? array(), 'media derivative cloud request exposes bounded crop positions' );
npcink_abilities_toolkit_assert_same( array( 'image', 'text' ), $package_abilities['npcink-abilities-toolkit/build-media-derivative-cloud-request']['input_schema']['properties']['watermark']['properties']['type']['enum'] ?? array(), 'media derivative cloud request supports image and text watermark plans' );
npcink_abilities_toolkit_assert_same( array( 'top_left', 'top_right', 'bottom_left', 'bottom_right', 'center' ), $package_abilities['npcink-abilities-toolkit/build-media-derivative-cloud-request']['input_schema']['properties']['watermark']['properties']['position']['enum'] ?? array(), 'media derivative cloud request exposes bounded watermark positions' );
npcink_abilities_toolkit_assert_same( 0.75, $package_abilities['npcink-abilities-toolkit/build-media-derivative-cloud-request']['input_schema']['properties']['watermark']['properties']['opacity']['default'] ?? null, 'media derivative cloud request defaults watermark opacity' );
npcink_abilities_toolkit_assert_same( 18, $package_abilities['npcink-abilities-toolkit/build-media-derivative-cloud-request']['input_schema']['properties']['watermark']['properties']['scale_percent']['default'] ?? null, 'media derivative cloud request defaults watermark scale' );
npcink_abilities_toolkit_assert_same( 'AI', $package_abilities['npcink-abilities-toolkit/build-media-derivative-cloud-request']['input_schema']['properties']['watermark']['properties']['text']['default'] ?? null, 'media derivative cloud request defaults text watermark content' );
npcink_abilities_toolkit_assert_same( 48, $package_abilities['npcink-abilities-toolkit/build-media-derivative-cloud-request']['input_schema']['properties']['watermark']['properties']['font_size']['default'] ?? null, 'media derivative cloud request defaults text watermark font size' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-media-derivative-batch-plan'] ), 'media derivative batch plan is registered as a read-only planning ability' );
npcink_abilities_toolkit_assert_same( array( 'media.read' ), $package_abilities['npcink-abilities-toolkit/build-media-derivative-batch-plan']['required_scopes'] ?? array(), 'media derivative batch plan remains a read-scope planning ability' );
npcink_abilities_toolkit_assert_same( array( 'auto_safe' ), $package_abilities['npcink-abilities-toolkit/build-media-derivative-batch-plan']['input_schema']['properties']['optimization_mode']['enum'] ?? array(), 'media derivative batch plan exposes only the auto-safe mode' );
npcink_abilities_toolkit_assert_same( array( 'auto_safe.v1' ), $package_abilities['npcink-abilities-toolkit/build-media-derivative-batch-plan']['input_schema']['properties']['optimization_profile']['enum'] ?? array(), 'media derivative batch plan pins the automatic policy version' );
npcink_abilities_toolkit_assert_same( array( 'jpeg', 'png', 'webp' ), $package_abilities['npcink-abilities-toolkit/build-media-derivative-batch-plan']['input_schema']['properties']['image_types']['items']['enum'] ?? array(), 'media derivative batch plan exposes only supported static image types' );
npcink_abilities_toolkit_assert_true( ! isset( $package_abilities['npcink-abilities-toolkit/build-media-derivative-batch-plan']['input_schema']['properties']['target_format'] ) && ! isset( $package_abilities['npcink-abilities-toolkit/build-media-derivative-batch-plan']['input_schema']['properties']['crop'] ) && ! isset( $package_abilities['npcink-abilities-toolkit/build-media-derivative-batch-plan']['input_schema']['properties']['watermark'] ), 'media derivative batch plan keeps manual format, crop, and watermark controls out of one-click input' );
npcink_abilities_toolkit_assert_same( 1000, $package_abilities['npcink-abilities-toolkit/build-media-derivative-batch-plan']['input_schema']['properties']['max_items']['maximum'] ?? null, 'media derivative batch plan bounds candidates to 1000 items' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-media-derivative-batch-plan']['output_schema']['properties']['data']['properties']['eligibility_summary'] ), 'media derivative batch plan declares eligibility summary output' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-media-derivative-batch-plan']['output_schema']['properties']['data']['properties']['blocked_items'] ), 'media derivative batch plan declares blocked items output' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-media-derivative-batch-plan']['output_schema']['properties']['data']['properties']['retry_guidance'] ), 'media derivative batch plan declares retry guidance output' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-media-derivative-batch-plan']['output_schema']['properties']['data']['properties']['operator_next_action'] ), 'media derivative batch plan declares operator next action output' );
npcink_abilities_toolkit_assert_true( ! isset( $package_abilities['npcink-abilities-toolkit/build-media-derivative-batch-plan']['input_schema']['properties']['commit'] ), 'media derivative batch plan does not expose a commit control' );
npcink_abilities_toolkit_assert_true( ! isset( $package_abilities['npcink-abilities-toolkit/build-media-derivative-batch-plan']['input_schema']['properties']['dry_run'] ), 'media derivative batch plan does not expose write dry_run control' );
npcink_abilities_toolkit_assert_same( 100, $package_abilities['npcink-abilities-toolkit/get-taxonomy-consolidation-suggestions']['input_schema']['properties']['per_page']['maximum'] ?? null, 'taxonomy consolidation suggestions scan is bounded to 100 terms per page' );
npcink_abilities_toolkit_assert_same( array( 'both', 'category', 'post_tag' ), $package_abilities['npcink-abilities-toolkit/suggest-post-taxonomy-terms']['input_schema']['properties']['taxonomy']['enum'] ?? array(), 'post taxonomy suggestions support both category and tag candidates' );
npcink_abilities_toolkit_assert_same( 20, $package_abilities['npcink-abilities-toolkit/suggest-post-taxonomy-terms']['input_schema']['properties']['related_term_evidence']['maxItems'] ?? null, 'post taxonomy suggestions bound related term evidence' );
npcink_abilities_toolkit_assert_true( ! isset( $package_abilities['npcink-abilities-toolkit/suggest-post-taxonomy-terms']['input_schema']['properties']['commit'] ), 'post taxonomy suggestions do not expose a commit control' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-taxonomy-tag-review-set'] ), 'build-taxonomy-tag-review-set is registered as a read-only review ability' );
npcink_abilities_toolkit_assert_same( 'taxonomy_governance', $package_abilities['npcink-abilities-toolkit/build-taxonomy-tag-review-set']['meta']['npcink_abilities_toolkit']['pack'] ?? '', 'taxonomy/tag review set is classified as taxonomy governance' );
npcink_abilities_toolkit_assert_same( array( 'post.read', 'taxonomy.read' ), $package_abilities['npcink-abilities-toolkit/build-taxonomy-tag-review-set']['required_scopes'] ?? array(), 'taxonomy/tag review set reads post context and taxonomy evidence' );
npcink_abilities_toolkit_assert_same( 20, $package_abilities['npcink-abilities-toolkit/build-taxonomy-tag-review-set']['input_schema']['properties']['review_set_limit']['maximum'] ?? null, 'taxonomy/tag review set bounds selected review rows' );
npcink_abilities_toolkit_assert_true( ! isset( $package_abilities['npcink-abilities-toolkit/build-taxonomy-tag-review-set']['input_schema']['properties']['commit'] ), 'taxonomy/tag review set does not expose a commit control' );
npcink_abilities_toolkit_assert_true( ! isset( $package_abilities['npcink-abilities-toolkit/build-taxonomy-tag-review-set']['input_schema']['properties']['provider'] ), 'taxonomy/tag review set does not expose provider runtime selection' );
npcink_abilities_toolkit_assert_same( array( 'post_id' ), $package_abilities['npcink-abilities-toolkit/propose-post-taxonomy-terms']['input_schema']['required'] ?? array(), 'post taxonomy proposal requires post_id' );
npcink_abilities_toolkit_assert_same( 20, $package_abilities['npcink-abilities-toolkit/propose-post-taxonomy-terms']['input_schema']['properties']['candidate_terms']['maxItems'] ?? null, 'post taxonomy proposal bounds candidate term names' );
npcink_abilities_toolkit_assert_same( 100, $package_abilities['npcink-abilities-toolkit/get-page-structure-health']['input_schema']['properties']['max_pages']['maximum'] ?? null, 'page structure health scan is bounded to 100 pages' );
npcink_abilities_toolkit_assert_same( 100, $package_abilities['npcink-abilities-toolkit/get-seo-geo-gap-report']['input_schema']['properties']['per_page']['maximum'] ?? null, 'SEO/GEO gap report scan is bounded to 100 posts per page' );
npcink_abilities_toolkit_assert_same( 5, $package_abilities['npcink-abilities-toolkit/get-site-style-baseline']['input_schema']['properties']['limit']['maximum'] ?? null, 'site style baseline is bounded to 5 samples' );
npcink_abilities_toolkit_assert_same( array( 'new_article', 'refresh', 'publish' ), $package_abilities['npcink-abilities-toolkit/build-article-workflow-context']['input_schema']['properties']['workflow']['enum'] ?? array(), 'article workflow context supports known workflow modes' );
npcink_abilities_toolkit_assert_same( 365, $package_abilities['npcink-abilities-toolkit/get-publishing-calendar-context']['input_schema']['properties']['window_days']['maximum'] ?? null, 'publishing calendar window is bounded to 365 days' );
npcink_abilities_toolkit_assert_same( 100, $package_abilities['npcink-abilities-toolkit/get-media-inventory-health']['input_schema']['properties']['per_page']['maximum'] ?? null, 'media inventory health scan is bounded to 100 assets per page' );
npcink_abilities_toolkit_assert_same( 20, $package_abilities['npcink-abilities-toolkit/get-media-inventory-health']['input_schema']['properties']['attachment_ids']['maxItems'] ?? null, 'media inventory attachment-id revalidation is bounded to 20 assets' );
npcink_abilities_toolkit_assert_same( array( 'date_desc', 'id_asc' ), $package_abilities['npcink-abilities-toolkit/get-media-inventory-health']['input_schema']['properties']['stable_order']['enum'] ?? array(), 'media inventory exposes only the bounded date or stable ID ordering modes' );
npcink_abilities_toolkit_assert_same( array( 'post_id' ), $package_abilities['npcink-abilities-toolkit/get-post-seo-geo-readiness']['input_schema']['required'] ?? array(), 'post SEO/GEO readiness requires post_id' );
npcink_abilities_toolkit_assert_same( 100, $package_abilities['npcink-abilities-toolkit/get-site-topic-coverage-report']['input_schema']['properties']['per_page']['maximum'] ?? null, 'site topic coverage scan is bounded to 100 posts per page' );
npcink_abilities_toolkit_assert_same( 100, $package_abilities['npcink-abilities-toolkit/get-taxonomy-inventory-health']['input_schema']['properties']['per_page']['maximum'] ?? null, 'taxonomy inventory health scan is bounded to 100 terms per page' );
npcink_abilities_toolkit_assert_same( array( 'post_id' ), $package_abilities['npcink-abilities-toolkit/get-revision-change-risk-report']['input_schema']['required'] ?? array(), 'revision change risk report requires post_id' );
npcink_abilities_toolkit_assert_same( 100, $package_abilities['npcink-abilities-toolkit/get-comment-queue-health']['input_schema']['properties']['per_page']['maximum'] ?? null, 'comment queue health scan is bounded to 100 comments per page' );
npcink_abilities_toolkit_assert_same( 100, $package_abilities['npcink-abilities-toolkit/get-comment-action-priority-queue']['input_schema']['properties']['per_page']['maximum'] ?? null, 'comment action priority queue scan is bounded to 100 comments per page' );
npcink_abilities_toolkit_assert_same( 100, $package_abilities['npcink-abilities-toolkit/get-comment-compliance-handoff']['input_schema']['properties']['per_page']['maximum'] ?? null, 'comment compliance handoff scan is bounded to 100 comments per page' );
npcink_abilities_toolkit_assert_same( array(), $package_abilities['npcink-abilities-toolkit/build-comment-mention-reply-suggest']['input_schema']['required'] ?? array(), 'comment mention reply suggestions accept either comment_id or supplied comment text' );
npcink_abilities_toolkit_assert_same( 1200, $package_abilities['npcink-abilities-toolkit/build-comment-mention-reply-suggest']['input_schema']['properties']['comment_text']['maxLength'] ?? null, 'comment mention reply suggestions bound supplied comment text' );
	npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit-comments', $package_abilities['npcink-abilities-toolkit/build-comment-moderation-suggest']['category'], 'comment helper abilities use the standalone comments category' );
	npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit-comments', $package_abilities['npcink-abilities-toolkit/get-comment-queue-health']['category'], 'comment queue health uses the standalone comments category' );
	npcink_abilities_toolkit_assert_same( false, $package_abilities['npcink-abilities-toolkit/wp-diagnostics-summary']['project_to_npcink_catalog'], 'standalone diagnostics ability does not project into Npcink AI by default' );
	npcink_abilities_toolkit_assert_same( false, $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['project_to_npcink_catalog'], 'standalone ops diagnostics ability does not project into Npcink AI by default' );
	npcink_abilities_toolkit_assert_same( false, $package_abilities['npcink-abilities-toolkit/list-workflow-recipes']['project_to_npcink_catalog'], 'workflow recipe discovery ability does not project into Npcink AI by default' );
	npcink_abilities_toolkit_assert_same( 'wordpress_diagnostics', $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['meta']['npcink_abilities_toolkit']['pack'] ?? '', 'ops diagnostics detail is classified as WordPress diagnostics' );
	npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit-workflows', $package_abilities['npcink-abilities-toolkit/list-workflow-recipes']['category'], 'workflow recipe discovery uses standalone workflow category' );
	npcink_abilities_toolkit_assert_same( 'workflow_definitions', $package_abilities['npcink-abilities-toolkit/list-workflow-recipes']['meta']['npcink_abilities_toolkit']['pack'] ?? '', 'workflow recipe discovery is classified as workflow definitions' );
npcink_abilities_toolkit_assert_same( 'core_wordpress_read', $package_abilities['npcink-abilities-toolkit/site-info']['meta']['npcink_abilities_toolkit']['pack'] ?? '', 'site-info is classified as a core WordPress read ability' );
npcink_abilities_toolkit_assert_same( 'content_operations', $package_abilities['npcink-abilities-toolkit/get-site-operations-dashboard']['meta']['npcink_abilities_toolkit']['pack'] ?? '', 'site operations dashboard is classified outside core WordPress reads' );
npcink_abilities_toolkit_assert_same( 'content_operations', $package_abilities['npcink-abilities-toolkit/build-content-inventory-fix-plan']['meta']['npcink_abilities_toolkit']['pack'] ?? '', 'content inventory fix plan is classified as content operations' );
npcink_abilities_toolkit_assert_same( 'media_governance', $package_abilities['npcink-abilities-toolkit/build-media-inventory-fix-plan']['meta']['npcink_abilities_toolkit']['pack'] ?? '', 'media inventory fix plan is classified as media governance' );
npcink_abilities_toolkit_assert_same( 'taxonomy_governance', $package_abilities['npcink-abilities-toolkit/suggest-post-taxonomy-terms']['meta']['npcink_abilities_toolkit']['pack'] ?? '', 'post taxonomy suggestions are classified as taxonomy governance' );
npcink_abilities_toolkit_assert_same( 'taxonomy_governance', $package_abilities['npcink-abilities-toolkit/build-taxonomy-tag-review-set']['meta']['npcink_abilities_toolkit']['pack'] ?? '', 'taxonomy/tag review set is classified as taxonomy governance' );
npcink_abilities_toolkit_assert_same( 'taxonomy_governance', $package_abilities['npcink-abilities-toolkit/propose-post-taxonomy-terms']['meta']['npcink_abilities_toolkit']['pack'] ?? '', 'post taxonomy proposal is classified as taxonomy governance' );
	npcink_abilities_toolkit_assert_same( 'page_governance', $package_abilities['npcink-abilities-toolkit/build-pattern-page-plan']['meta']['npcink_abilities_toolkit']['pack'] ?? '', 'pattern page plan is classified as page governance' );
	npcink_abilities_toolkit_assert_same( 'page_governance', $package_abilities['npcink-abilities-toolkit/route-content-intent']['meta']['npcink_abilities_toolkit']['pack'] ?? '', 'content intent router is classified as page governance' );
	npcink_abilities_toolkit_assert_same( 'page_governance', $package_abilities['npcink-abilities-toolkit/evaluate-gutenberg-recipe-suite']['meta']['npcink_abilities_toolkit']['pack'] ?? '', 'Gutenberg recipe evaluation is classified as page governance' );
	npcink_abilities_toolkit_assert_same( 'page_governance', $package_abilities['npcink-abilities-toolkit/inspect-block-theme-surface']['meta']['npcink_abilities_toolkit']['pack'] ?? '', 'block theme surface inspection is classified as page governance' );
	npcink_abilities_toolkit_assert_same( 'page_governance', $package_abilities['npcink-abilities-toolkit/review-pattern-page']['meta']['npcink_abilities_toolkit']['pack'] ?? '', 'pattern page review is classified as page governance' );
	npcink_abilities_toolkit_assert_same( 'page_governance', $package_abilities['npcink-abilities-toolkit/build-block-theme-site-plan']['meta']['npcink_abilities_toolkit']['pack'] ?? '', 'block theme site plan is classified as page governance' );
npcink_abilities_toolkit_assert_same( 'comment_queue_context', $package_abilities['npcink-abilities-toolkit/get-comment-queue-health']['meta']['npcink_abilities_toolkit']['pack'] ?? '', 'comment queue health is classified as a comment queue helper' );
	$expected_mcp_public_read_ability_ids = array(
		'npcink-abilities-toolkit/get-workflow-recipe',
		'npcink-abilities-toolkit/list-post-types',
		'npcink-abilities-toolkit/list-taxonomies',
		'npcink-abilities-toolkit/list-workflow-recipes',
		'npcink-abilities-toolkit/site-info',
	);
	$mcp_public_read_ability_ids = array();
	foreach ( $package_abilities as $ability_id => $definition ) {
		if ( 'read' === (string) ( $definition['risk_level'] ?? '' ) && true === (bool) ( $definition['meta']['mcp']['public'] ?? false ) ) {
			$mcp_public_read_ability_ids[] = (string) $ability_id;
		}
	}
	sort( $mcp_public_read_ability_ids );
	npcink_abilities_toolkit_assert_same( $expected_mcp_public_read_ability_ids, $mcp_public_read_ability_ids, 'default MCP-public read surface stays limited to approved entrypoint abilities' );
	foreach ( $expected_mcp_public_read_ability_ids as $ability_id ) {
		npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit-read', $package_abilities[ $ability_id ]['meta']['mcp']['server'] ?? '', "{$ability_id} belongs on the read MCP server" );
	}
	npcink_abilities_toolkit_assert_same( false, $package_abilities['npcink-abilities-toolkit/wp-diagnostics-summary']['meta']['mcp']['public'] ?? null, 'diagnostics summary stays out of default MCP discovery' );
	npcink_abilities_toolkit_assert_same( false, $package_abilities['npcink-abilities-toolkit/get-site-operations-dashboard']['meta']['mcp']['public'] ?? null, 'site operations dashboard stays out of default MCP discovery' );
	$core_read_definition_ids = array_keys( $core_read_package->definitions() );
	$media_governance_definition_ids = array(
		'npcink-abilities-toolkit/list-media',
		'npcink-abilities-toolkit/resolve-media-attachment-by-url',
		'npcink-abilities-toolkit/build-inline-image-blocks',
		'npcink-abilities-toolkit/build-media-seo-assets',
		'npcink-abilities-toolkit/optimize-media-metadata',
		'npcink-abilities-toolkit/position-inline-image-blocks',
		'npcink-abilities-toolkit/get-media-cleanup-opportunities',
		'npcink-abilities-toolkit/list-media-backups',
		'npcink-abilities-toolkit/build-media-inventory-fix-plan',
		'npcink-abilities-toolkit/build-media-reference-repair-plan',
		'npcink-abilities-toolkit/build-media-adoption-enhancement-plan',
		'npcink-abilities-toolkit/build-image-candidate-adoption-plan',
		'npcink-abilities-toolkit/build-image-candidate-review-artifact',
		'npcink-abilities-toolkit/build-media-alt-caption-review-set',
		'npcink-abilities-toolkit/build-media-alt-apply-plan',
		'npcink-abilities-toolkit/build-media-settings-reference-repair-plan',
		'npcink-abilities-toolkit/get-media-inventory-health',
		'npcink-abilities-toolkit/inspect-media-asset',
		'npcink-abilities-toolkit/build-media-derivative-cloud-request',
		'npcink-abilities-toolkit/build-media-optimization-plan',
		'npcink-abilities-toolkit/build-media-adoption-preflight-summary',
		'npcink-abilities-toolkit/build-media-rename-plan',
		'npcink-abilities-toolkit/build-media-derivative-batch-plan',
	);
	$media_governance_definitions = Media_Governance_Read_Definitions::definitions( $core_read_package );
	npcink_abilities_toolkit_assert_same( $media_governance_definition_ids, array_keys( $media_governance_definitions ), 'media governance definition provider owns the expected 23 definitions in stable source order' );
	npcink_abilities_toolkit_assert_same( 116, count( $core_read_definition_ids ), 'core read package preserves the 116-definition public surface after media provider split' );
	npcink_abilities_toolkit_assert_same( array_keys( Core_Read_Pack_Classifier::known_pack_map() ), $core_read_definition_ids, 'core read package preserves the classifier map as the complete 116-definition public order' );
	npcink_abilities_toolkit_assert_true( false !== strpos( $core_read_package_source, 'Media_Governance_Read_Definitions::definitions( $this )' ), 'core read package composes media governance definitions through the dedicated provider' );
	foreach ( $media_governance_definition_ids as $media_ability_id ) {
		$inline_definition_pattern = "/'" . preg_quote( $media_ability_id, '/' ) . "'\\s*=>\\s*array\\s*\\(/";
		npcink_abilities_toolkit_assert_same( 0, preg_match( $inline_definition_pattern, $core_read_package_source ), "core read package no longer inlines {$media_ability_id}" );
	}
	npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/site-info', $core_read_definition_ids[0] ?? '', 'core read definitions keep site-info first after provider split' );
	npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/wp-diagnostics-summary', $core_read_definition_ids[1] ?? '', 'core read definitions keep diagnostics second after provider split' );
	npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/wp-ops-diagnostics-detail', $core_read_definition_ids[2] ?? '', 'core read definitions keep ops diagnostics after diagnostics summary' );
	npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/list-workflow-recipes', $core_read_definition_ids[3] ?? '', 'core read definitions keep workflow list after diagnostics' );
	npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/get-workflow-recipe', $core_read_definition_ids[4] ?? '', 'core read definitions keep workflow get after workflow list' );
	npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/list-post-types', $core_read_definition_ids[5] ?? '', 'core read definitions keep post types after workflow definitions' );
		npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/list-media', $core_read_definition_ids[7] ?? '', 'core read definitions keep media governance order after provider split' );
		npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/resolve-media-attachment-by-url', $core_read_definition_ids[8] ?? '', 'core read definitions keep media URL resolver near media inventory' );
	$taxonomy_suggest_index = array_search( 'npcink-abilities-toolkit/suggest-post-taxonomy-terms', $core_read_definition_ids, true );
	$taxonomy_review_index = array_search( 'npcink-abilities-toolkit/build-taxonomy-tag-review-set', $core_read_definition_ids, true );
	$metadata_plan_index = array_search( 'npcink-abilities-toolkit/resolve-post-metadata-plan', $core_read_definition_ids, true );
	$route_content_intent_index = array_search( 'npcink-abilities-toolkit/route-content-intent', $core_read_definition_ids, true );
	$recipe_evaluation_index = array_search( 'npcink-abilities-toolkit/evaluate-gutenberg-recipe-suite', $core_read_definition_ids, true );
	$block_catalog_index = array_search( 'npcink-abilities-toolkit/get-gutenberg-block-capability-catalog', $core_read_definition_ids, true );
	$block_composer_index = array_search( 'npcink-abilities-toolkit/compose-gutenberg-block-plan', $core_read_definition_ids, true );
	$composition_contract_index = array_search( 'npcink-abilities-toolkit/inspect-gutenberg-composition-contract', $core_read_definition_ids, true );
	$block_theme_context_index = array_search( 'npcink-abilities-toolkit/get-block-theme-context', $core_read_definition_ids, true );
	$block_theme_surface_index = array_search( 'npcink-abilities-toolkit/inspect-block-theme-surface', $core_read_definition_ids, true );
	$block_theme_site_plan_index = array_search( 'npcink-abilities-toolkit/build-block-theme-site-plan', $core_read_definition_ids, true );
	$pattern_page_plan_index = array_search( 'npcink-abilities-toolkit/build-pattern-page-plan', $core_read_definition_ids, true );
	$pattern_page_review_index = array_search( 'npcink-abilities-toolkit/review-pattern-page', $core_read_definition_ids, true );
	$block_surface_review_index = array_search( 'npcink-abilities-toolkit/review-block-editor-surface', $core_read_definition_ids, true );
	$article_block_plan_index = array_search( 'npcink-abilities-toolkit/build-article-block-plan', $core_read_definition_ids, true );
	npcink_abilities_toolkit_assert_true( false !== $taxonomy_suggest_index && false !== $taxonomy_review_index && $taxonomy_suggest_index < $taxonomy_review_index, 'core read definitions keep taxonomy review-set builder after taxonomy suggestions' );
	npcink_abilities_toolkit_assert_true( false !== $taxonomy_review_index && false !== $metadata_plan_index && $taxonomy_review_index < $metadata_plan_index, 'core read definitions keep taxonomy review-set builder before metadata planning' );
	npcink_abilities_toolkit_assert_true( false !== $metadata_plan_index && false !== $route_content_intent_index && $metadata_plan_index < $route_content_intent_index, 'core read definitions keep content intent routing after metadata planning' );
	npcink_abilities_toolkit_assert_true( false !== $route_content_intent_index && false !== $recipe_evaluation_index && $route_content_intent_index < $recipe_evaluation_index, 'core read definitions keep recipe evaluation next to intent routing' );
	npcink_abilities_toolkit_assert_true( false !== $recipe_evaluation_index && false !== $block_catalog_index && $recipe_evaluation_index < $block_catalog_index, 'core read definitions keep the block composition catalog before concrete planners' );
	npcink_abilities_toolkit_assert_true( false !== $block_catalog_index && false !== $block_composer_index && $block_catalog_index < $block_composer_index, 'core read definitions keep the composer repair loop after the block catalog' );
	npcink_abilities_toolkit_assert_true( false !== $block_composer_index && false !== $composition_contract_index && $block_composer_index < $composition_contract_index, 'core read definitions keep composition contract inspection after the composer loop' );
	npcink_abilities_toolkit_assert_true( false !== $composition_contract_index && false !== $block_theme_context_index && $composition_contract_index < $block_theme_context_index, 'core read definitions keep block theme context near page planning' );
	npcink_abilities_toolkit_assert_true( false !== $block_theme_context_index && false !== $block_theme_surface_index && $block_theme_context_index < $block_theme_surface_index, 'core read definitions keep block theme inspection before site planning' );
	npcink_abilities_toolkit_assert_true( false !== $block_theme_surface_index && false !== $block_theme_site_plan_index && $block_theme_surface_index < $block_theme_site_plan_index, 'core read definitions keep block theme site planning before pattern page planning' );
	npcink_abilities_toolkit_assert_true( false !== $block_theme_site_plan_index && false !== $pattern_page_plan_index && $block_theme_site_plan_index < $pattern_page_plan_index, 'core read definitions keep pattern page planning near block theme planning' );
	npcink_abilities_toolkit_assert_true( false !== $pattern_page_plan_index && false !== $pattern_page_review_index && $pattern_page_plan_index < $pattern_page_review_index, 'core read definitions keep pattern page review near pattern page planning' );
	npcink_abilities_toolkit_assert_true( false !== $pattern_page_review_index && false !== $block_surface_review_index && $pattern_page_review_index < $block_surface_review_index, 'core read definitions keep block surface review near pattern page review' );
	npcink_abilities_toolkit_assert_true( false !== $block_surface_review_index && false !== $article_block_plan_index && $block_surface_review_index < $article_block_plan_index, 'core read definitions keep article block planning near pattern page planning' );
		npcink_abilities_toolkit_assert_true( false !== array_search( 'npcink-abilities-toolkit/list-media-backups', $core_read_definition_ids, true ), 'core read definitions include media backup history discovery' );
		$url_resolver_index = array_search( 'npcink-abilities-toolkit/resolve-url-to-post', $core_read_definition_ids, true );
		$revision_list_index = array_search( 'npcink-abilities-toolkit/list-post-revisions', $core_read_definition_ids, true );
		npcink_abilities_toolkit_assert_true( false !== $url_resolver_index, 'core read definitions include URL resolver after provider split' );
		npcink_abilities_toolkit_assert_true( false !== $revision_list_index, 'core read definitions include revision list after provider split' );
		npcink_abilities_toolkit_assert_true( false !== $url_resolver_index && false !== $revision_list_index && $url_resolver_index < $revision_list_index, 'core read definitions keep URL resolver before revision list after provider split' );
$core_comment_definition_ids = array_keys( $core_comment_package->definitions() );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/build-comment-moderation-suggest', $core_comment_definition_ids[0] ?? '', 'core comment definitions keep moderation suggestion first after provider split' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/get-comment-compliance-handoff', $core_comment_definition_ids[6] ?? '', 'core comment definitions keep compliance handoff order after provider split' );
foreach ( array_keys( $core_read_package->definitions() ) as $known_read_ability_id ) {
	npcink_abilities_toolkit_assert_true(
		isset( Core_Read_Pack_Classifier::known_pack_map()[ $known_read_ability_id ] ),
		"core read ability {$known_read_ability_id} has an explicit sub-pack map entry"
	);
}
foreach ( array_keys( $core_comment_package->definitions() ) as $known_comment_ability_id ) {
	npcink_abilities_toolkit_assert_true(
		isset( Core_Comment_Pack_Classifier::known_pack_map()[ $known_comment_ability_id ] ),
		"core comment ability {$known_comment_ability_id} has an explicit sub-pack map entry"
	);
}
npcink_abilities_toolkit_assert_true( ! isset( $package_abilities['npcink-abilities-toolkit/create-page'] ), 'create-page is not migrated as a readonly ability' );
npcink_abilities_toolkit_assert_true( ! isset( $package_abilities['npcink-abilities-toolkit/update-page'] ), 'update-page is not migrated as a readonly ability' );

add_filter(
	'npcink_abilities_toolkit_enabled_read_packs',
	static function () {
		return array( 'core_wordpress_read' );
	}
);
$filtered_read_categories = new Category_Registrar();
$filtered_read_registrar = new Ability_Registrar( $filtered_read_categories, $contract_normalizer );
$filtered_read_package = new Core_Read_Package( $filtered_read_categories, $filtered_read_registrar );
$filtered_read_package->boot();
$filtered_read_abilities = $filtered_read_registrar->all();
	npcink_abilities_toolkit_assert_true( isset( $filtered_read_abilities['npcink-abilities-toolkit/site-info'] ), 'core read pack filter keeps generic site-info ability' );
	npcink_abilities_toolkit_assert_true( ! isset( $filtered_read_abilities['npcink-abilities-toolkit/get-site-operations-dashboard'] ), 'core read pack filter removes operations helper ability' );
	npcink_abilities_toolkit_assert_true( ! isset( $filtered_read_abilities['npcink-abilities-toolkit/wp-diagnostics-summary'] ), 'core read pack filter removes diagnostics helper ability' );
	npcink_abilities_toolkit_assert_true( ! isset( $filtered_read_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail'] ), 'core read pack filter removes ops diagnostics helper ability' );
	npcink_abilities_toolkit_assert_true( ! isset( $filtered_read_abilities['npcink-abilities-toolkit/list-workflow-recipes'] ), 'core read pack filter removes workflow definition discovery ability' );
remove_all_filters( 'npcink_abilities_toolkit_enabled_read_packs' );

