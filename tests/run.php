<?php
/**
 * Lightweight regression tests.
 *
 * @package MagickAIAbilities
 */

require_once __DIR__ . '/bootstrap.php';

use Magick_AI_Abilities\Integration\Magick_Catalog_Bridge;
use Magick_AI_Abilities\Packages\Core_Comment_Package;
use Magick_AI_Abilities\Packages\Core_Destructive_Package;
use Magick_AI_Abilities\Packages\Core_Read_Package;
use Magick_AI_Abilities\Packages\Core_Write_Package;
use Magick_AI_Abilities\Registry\Ability_Registrar;
use Magick_AI_Abilities\Registry\Annotation_Normalizer;
use Magick_AI_Abilities\Registry\Category_Registrar;
use Magick_AI_Abilities\Registry\Contract_Normalizer;
use Magick_AI_Abilities\Registry\Schema_Normalizer;

$assertions = 0;

function maa_assert_true( $condition, $message ) {
	global $assertions;
	++$assertions;
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

function maa_assert_same( $expected, $actual, $message ) {
	maa_assert_true( $expected === $actual, $message . ' Expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) );
}

function maa_assert_package_read_ability_contract( $ability_id, $definition ) {
	$definition = is_array( $definition ) ? $definition : array();
	maa_assert_same( true, $definition['annotations']['readonly'] ?? null, "{$ability_id} is readonly" );
	maa_assert_same( false, $definition['annotations']['destructive'] ?? null, "{$ability_id} is not destructive" );
	maa_assert_same( 'read', $definition['risk_level'] ?? '', "{$ability_id} risk is read" );
	maa_assert_same( true, $definition['meta']['show_in_rest'] ?? null, "{$ability_id} is shown in REST" );
	maa_assert_same( 'official', $definition['source'] ?? '', "{$ability_id} is an official migrated ability" );
	maa_assert_true( is_callable( $definition['execute_callback'] ?? null ), "{$ability_id} execute callback is callable" );
	maa_assert_true( is_array( $definition['input_schema'] ?? null ), "{$ability_id} has an input schema" );
	maa_assert_true( is_array( $definition['output_schema'] ?? null ), "{$ability_id} has an output schema" );
}

function maa_assert_package_write_ability_contract( $ability_id, $definition ) {
	$definition = is_array( $definition ) ? $definition : array();
	maa_assert_same( false, $definition['annotations']['readonly'] ?? null, "{$ability_id} is not readonly" );
	maa_assert_same( false, $definition['annotations']['destructive'] ?? null, "{$ability_id} is not destructive" );
	maa_assert_same( 'write', $definition['risk_level'] ?? '', "{$ability_id} risk is write" );
	maa_assert_same( true, $definition['requires_confirm'] ?? null, "{$ability_id} requires host approval" );
	maa_assert_same( true, $definition['meta']['show_in_rest'] ?? null, "{$ability_id} is shown in REST" );
	maa_assert_same( 'magick-ai-write', $definition['category'] ?? '', "{$ability_id} uses write category" );
	maa_assert_same( 'official', $definition['source'] ?? '', "{$ability_id} is an official migrated write ability" );
	maa_assert_same( true, $definition['project_to_magick_catalog'] ?? null, "{$ability_id} projects into Magick AI catalog" );
	maa_assert_true( is_callable( $definition['execute_callback'] ?? null ), "{$ability_id} execute callback is callable" );
	maa_assert_true( is_array( $definition['input_schema'] ?? null ), "{$ability_id} has an input schema" );
	maa_assert_true( is_array( $definition['output_schema'] ?? null ), "{$ability_id} has an output schema" );
	maa_assert_same( true, $definition['meta']['mcp']['public'] ?? null, "{$ability_id} is MCP-public for governed write server discovery" );
	maa_assert_same( 'magick-ai-write', $definition['meta']['mcp']['server'] ?? '', "{$ability_id} belongs on governed write server" );
}

function maa_assert_package_destructive_ability_contract( $ability_id, $definition ) {
	$definition = is_array( $definition ) ? $definition : array();
	maa_assert_same( false, $definition['annotations']['readonly'] ?? null, "{$ability_id} is not readonly" );
	maa_assert_same( true, $definition['annotations']['destructive'] ?? null, "{$ability_id} is destructive" );
	maa_assert_same( 'destructive', $definition['risk_level'] ?? '', "{$ability_id} risk is destructive" );
	maa_assert_same( true, $definition['requires_confirm'] ?? null, "{$ability_id} requires host approval" );
	maa_assert_same( true, $definition['meta']['show_in_rest'] ?? null, "{$ability_id} is shown in REST" );
	maa_assert_same( 'magick-ai-write', $definition['category'] ?? '', "{$ability_id} keeps legacy write category" );
	maa_assert_same( 'official', $definition['source'] ?? '', "{$ability_id} is an official migrated destructive ability" );
	maa_assert_same( true, $definition['project_to_magick_catalog'] ?? null, "{$ability_id} projects into Magick AI catalog" );
	maa_assert_true( is_callable( $definition['execute_callback'] ?? null ), "{$ability_id} execute callback is callable" );
	maa_assert_true( is_array( $definition['input_schema'] ?? null ), "{$ability_id} has an input schema" );
	maa_assert_true( is_array( $definition['output_schema'] ?? null ), "{$ability_id} has an output schema" );
	maa_assert_same( 'magick-ai-write', $definition['meta']['mcp']['server'] ?? '', "{$ability_id} belongs on governed write server" );
	maa_assert_same( 'destructive', $definition['meta']['mcp']['risk'] ?? '', "{$ability_id} MCP risk is destructive" );
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
maa_assert_same( 'string', $normalized_schema['properties']['title']['type'], 'schema shorthand string is normalized' );
maa_assert_same( 'number', $normalized_schema['properties']['meta']['properties']['score']['type'], 'nested schema shorthand is normalized' );

$destructive_annotations = $annotation_normalizer->normalize(
	array( 'instructions' => "Use carefully.\nNever skip review." ),
	'destructive'
);
maa_assert_same( false, $destructive_annotations['readonly'], 'destructive annotation is not readonly' );
maa_assert_same( true, $destructive_annotations['destructive'], 'destructive annotation is destructive' );
maa_assert_same( false, $destructive_annotations['idempotent'], 'destructive annotation is not idempotent' );
maa_assert_same( 'Use carefully. Never skip review.', $destructive_annotations['instructions'], 'annotation instructions are sanitized' );

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
maa_assert_same( 'acme/sitesummary', $readonly['ability_id'], 'ability id is lowercased and stripped to machine-safe characters' );
maa_assert_same( true, $readonly['annotations']['readonly'], 'readonly ability annotation is readonly' );
maa_assert_same( false, $readonly['annotations']['destructive'], 'readonly ability is not destructive' );
maa_assert_same( 'read', $readonly['risk_level'], 'readonly ability risk is read' );
maa_assert_same( true, $readonly['meta']['show_in_rest'], 'readonly ability defaults to show_in_rest' );
maa_assert_same( 'read', $readonly['meta']['mcp']['risk'], 'readonly mcp risk is read' );

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
maa_assert_same( false, $write['annotations']['readonly'], 'write proposal is not readonly' );
maa_assert_same( 'write', $write['risk_level'], 'write proposal risk is write' );
maa_assert_same( true, $write['requires_confirm'], 'write proposal requires confirmation' );
maa_assert_same( 'magick-ai-abilities-write', $write['category'], 'write proposal default category is write category' );

$categories = new Category_Registrar();
$registrar = new Ability_Registrar( $categories, $contract_normalizer );
maa_assert_true(
	$registrar->add_readonly(
		'acme/site-summary',
		array(
			'label'            => 'Site Summary',
			'description'      => 'Returns site summary.',
			'input_schema'     => array( 'type' => 'object' ),
			'output_schema'    => array( 'type' => 'object' ),
			'required_scope'   => 'cap.site.read',
			'execute_callback' => static function () {
				return array();
			},
		)
	),
	'registrar accepts namespaced readonly ability'
);
maa_assert_true(
	! $registrar->add_readonly(
		'invalid',
		array(
			'label' => 'Invalid',
		)
	),
	'registrar rejects unnamespaced ability'
);

maa_assert_true(
	$registrar->add_write_host_governed(
		'acme/host-write',
		array(
			'label'            => 'Host Write',
			'description'      => 'Host-governed write.',
			'input_schema'     => array( 'type' => 'object' ),
			'output_schema'    => array( 'type' => 'object' ),
			'execute_callback' => static function () {
				return array( 'dry_run' => true );
			},
		)
	),
	'registrar accepts host-governed write ability'
);
$host_write = $registrar->all()['acme/host-write'];
maa_assert_same( 'write_host', $host_write['mode'], 'host-governed write mode is preserved' );
maa_assert_same( 'write', $host_write['risk_level'], 'host-governed write risk is write' );
maa_assert_same( true, $host_write['requires_confirm'], 'host-governed write requires confirmation' );

$bridge = new Magick_Catalog_Bridge( $registrar );
$catalog = $bridge->filter_catalog( array(), array() );
maa_assert_true( ! isset( $catalog['acme_site-summary'] ), 'catalog bridge does not project provider abilities by default' );

maa_assert_true(
	$registrar->add_readonly(
		'acme/projected-summary',
		array(
			'label'                     => 'Projected Summary',
			'description'               => 'Provider ability explicitly projected for Magick AI compatibility.',
			'project_to_magick_catalog' => true,
			'input_schema'              => array( 'type' => 'object' ),
			'output_schema'             => array( 'type' => 'object' ),
			'execute_callback'          => static function () {
				return array();
			},
		)
	),
	'registrar accepts provider ability with explicit Magick AI projection'
);
$catalog = $bridge->filter_catalog( array(), array() );
maa_assert_true( isset( $catalog['acme_projected-summary'] ), 'catalog bridge projects opted-in provider ability' );
maa_assert_same( 'wp_ability', $catalog['acme_projected-summary']['executor_type'], 'catalog bridge uses wp_ability executor' );
maa_assert_same( 'acme/projected-summary', $catalog['acme_projected-summary']['wp_ability_id'], 'catalog bridge keeps wp ability id' );
maa_assert_same( false, $catalog['acme_projected-summary']['open_api_enabled'], 'catalog bridge does not publish Open API by default' );
maa_assert_same( true, $catalog['acme_projected-summary']['skip_catalog_manifest_fallback'], 'catalog bridge preserves provider contract over host default manifest fallback' );
maa_assert_same( true, $catalog['acme_projected-summary']['show_in_rest'], 'catalog bridge sets top-level show_in_rest for host catalog normalization' );

maa_assert_true(
	$registrar->add_readonly(
		'magick-ai/official-summary',
		array(
			'label'                     => 'Official Summary',
			'description'               => 'Official ability mirrored from a host plugin.',
			'source'                    => 'official',
			'project_to_magick_catalog' => false,
			'input_schema'              => array( 'type' => 'object' ),
			'output_schema'             => array( 'type' => 'object' ),
			'execute_callback'          => static function () {
				return array();
			},
		)
	),
	'registrar accepts official mirrored readonly ability'
);
$catalog = $bridge->filter_catalog( array(), array() );
maa_assert_true( ! isset( $catalog['magick-ai_official-summary'] ), 'catalog bridge does not project official mirrored abilities' );

$package_categories = new Category_Registrar();
$package_registrar = new Ability_Registrar( $package_categories, $contract_normalizer );
$core_read_package = new Core_Read_Package( $package_categories, $package_registrar );
$core_read_package->boot();
$core_write_package = new Core_Write_Package( $package_categories, $package_registrar );
$core_write_package->boot();
$core_destructive_package = new Core_Destructive_Package( $package_categories, $package_registrar );
$core_destructive_package->boot();
$core_comment_package = new Core_Comment_Package( $package_categories, $package_registrar );
$core_comment_package->boot();
$package_abilities = $package_registrar->all();
$migrated_read_ability_ids = array(
	'magick-ai/site-info',
	'magick-ai/list-post-types',
	'magick-ai/list-taxonomies',
	'magick-ai/count-posts',
	'magick-ai/list-pages-tree',
	'magick-ai/list-posts',
	'magick-ai/get-post',
	'magick-ai/resolve-url-to-post',
	'magick-ai/get-post-blocks',
	'magick-ai/list-post-revisions',
	'magick-ai/list-media',
	'magick-ai/list-terms',
	'magick-ai/list-taxonomy-terms',
	'magick-ai/list-categories',
	'magick-ai/list-tags',
	'magick-ai/get-term',
	'magick-ai/propose-post-excerpt',
	'magick-ai/resolve-post-metadata-plan',
	'magick-ai/list-users',
	'magick-ai/list-comments',
	'magick-ai/build-comment-moderation-suggest',
	'magick-ai/compose-comment-moderation-result',
	'magick-ai/build-comment-mention-reply-suggest',
	'magick-ai/read-comment-trigger-queue',
	'magick-ai/compose-comment-mention-reply-result',
	'magick-ai/build-comment-moderation-batch-suggest',
	'magick-ai/compose-comment-moderation-batch-result',
	'magick-ai/list-menus',
	'magick-ai/get-menu',
	'magick-ai/search-posts',
	'magick-ai/resolve-internal-link-targets',
	'magick-ai/build-inline-image-blocks',
	'magick-ai/build-media-seo-assets',
	'magick-ai/geo-analyze',
	'magick-ai/optimize-media-metadata',
	'magick-ai/position-inline-image-blocks',
	'magick-ai/build-article-optimization-report',
	'magick-ai/seo-report-context',
	'magick-ai/read-post-optimization-context',
	'magick-ai/build-article-single-optimization-suggest',
	'magick-ai/build-article-optimization-apply-plan',
	'magick-ai/compose-article-optimization-apply-result',
	'magick-ai/extract-reference-post-style',
	'magick-ai/extract-style-baseline',
	'magick-ai/build-article-production-fingerprint',
	'magick-ai/check-article-production-duplicate',
	'magick-ai/review-article-output-light',
	'magick-ai/compose-article-production-result',
	'magick-ai/compose-article-draft-result',
	'magick-ai/resolve-article-publication-decision',
	'magick-ai/build-article-style-profile',
	'magick-ai/get-post-stats',
	'magick-ai/list-revisions',
	'magick-ai/get-post-meta',
	'magick-ai/list-pages',
	'magick-ai/get-page',
	'magick-ai/inspect-page-structure',
);
$migrated_write_ability_ids = array(
	'magick-ai/create-draft',
	'magick-ai/update-post',
	'magick-ai/set-post-seo-meta',
	'magick-ai/patch-post-content',
	'magick-ai/update-post-blocks',
	'magick-ai/set-post-slug',
	'magick-ai/set-post-author',
	'magick-ai/set-post-template',
	'magick-ai/set-post-format',
	'magick-ai/create-term',
	'magick-ai/update-term',
	'magick-ai/set-post-terms',
	'magick-ai/update-media-details',
	'magick-ai/upload-media-from-url',
	'magick-ai/set-post-featured-image',
	'magick-ai/schedule-post',
	'magick-ai/publish-post',
	'magick-ai/restore-post',
	'magick-ai/approve-comment',
	'magick-ai/reply-comment',
);
$migrated_destructive_ability_ids = array(
	'magick-ai/delete-term',
	'magick-ai/merge-terms',
	'magick-ai/bulk-update-post-terms',
	'magick-ai/spam-comment',
	'magick-ai/trash-comment',
	'magick-ai/delete-media-permanently',
	'magick-ai/trash-post',
	'magick-ai/delete-post-permanently',
);
maa_assert_true( isset( $package_categories->all()['magick-ai-data'] ), 'core read package registers the legacy magick-ai-data category for compatibility' );
maa_assert_true( isset( $package_categories->all()['magick-ai-pages'] ), 'core read package registers the legacy magick-ai-pages category for compatibility' );
maa_assert_true( isset( $package_categories->all()['magick-ai-comments'] ), 'core comment package registers the standalone comments category' );
maa_assert_true( isset( $package_categories->all()['magick-ai-write'] ), 'core write package registers the legacy magick-ai-write category for compatibility' );
maa_assert_true( isset( $package_categories->all()['magick-ai-abilities-diagnostics'] ), 'core read package registers the standalone diagnostics category' );
maa_assert_true( isset( $package_abilities['magick-ai-abilities/wp-diagnostics-summary'] ), 'core read package owns standalone wp-diagnostics-summary ability' );
maa_assert_same( 'magick-ai-abilities-diagnostics', $package_abilities['magick-ai-abilities/wp-diagnostics-summary']['category'], 'wp-diagnostics-summary uses standalone diagnostics category' );
foreach ( $migrated_read_ability_ids as $migrated_ability_id ) {
	maa_assert_true( isset( $package_abilities[ $migrated_ability_id ] ), "core read package owns migrated {$migrated_ability_id} ability" );
	maa_assert_package_read_ability_contract( $migrated_ability_id, $package_abilities[ $migrated_ability_id ] );
}
maa_assert_same( true, $package_abilities['magick-ai/site-info']['project_to_magick_catalog'], 'migrated core read abilities project into Magick AI catalog' );
maa_assert_same( 'magick-ai-comments', $package_abilities['magick-ai/build-comment-moderation-suggest']['category'], 'comment helper abilities use the standalone comments category' );
maa_assert_same( false, $package_abilities['magick-ai-abilities/wp-diagnostics-summary']['project_to_magick_catalog'], 'standalone diagnostics ability does not project into Magick AI by default' );
maa_assert_true( ! isset( $package_abilities['magick-ai/create-page'] ), 'create-page is not migrated as a readonly ability' );
maa_assert_true( ! isset( $package_abilities['magick-ai/update-page'] ), 'update-page is not migrated as a readonly ability' );
foreach ( $migrated_write_ability_ids as $migrated_write_ability_id ) {
	maa_assert_true( isset( $package_abilities[ $migrated_write_ability_id ] ), "core write package owns migrated {$migrated_write_ability_id} ability" );
	maa_assert_package_write_ability_contract( $migrated_write_ability_id, $package_abilities[ $migrated_write_ability_id ] );
}
foreach ( $migrated_destructive_ability_ids as $migrated_destructive_ability_id ) {
	maa_assert_true( isset( $package_abilities[ $migrated_destructive_ability_id ] ), "core destructive package owns migrated {$migrated_destructive_ability_id} ability" );
	maa_assert_package_destructive_ability_contract( $migrated_destructive_ability_id, $package_abilities[ $migrated_destructive_ability_id ] );
}
maa_assert_same(
	array( 'taxonomy', 'name' ),
	$package_abilities['magick-ai/create-term']['input_schema']['required'] ?? array(),
	'create-term preserves migrated required taxonomy/name schema'
);
maa_assert_same(
	array( 'taxonomy', 'term_id' ),
	$package_abilities['magick-ai/update-term']['input_schema']['required'] ?? array(),
	'update-term preserves migrated required taxonomy/term_id schema'
);
$GLOBALS['maa_unit_style_posts'] = array(
	501 => (object) array(
		'ID' => 501,
		'post_type' => 'post',
		'post_status' => 'draft',
		'post_title' => 'Original title',
		'post_content' => '<p>Original body marker.</p>',
		'post_excerpt' => '',
		'post_author' => 7,
		'post_name' => 'original-title',
		'post_parent' => 0,
	),
);
$create_preview = $core_write_package->create_draft(
	array(
		'title' => 'Preview title',
		'content' => 'Preview body',
		'dry_run' => true,
	)
);
maa_assert_same( true, $create_preview['dry_run'] ?? null, 'create-draft defaults to governed dry-run preview when requested' );
maa_assert_same( 'create_draft', $create_preview['preview']['action'] ?? '', 'create-draft dry-run reports preview action' );

$GLOBALS['magick_ai_runtime_wp_ability_context'] = array( 'context' => array( 'approval_commit_authorized' => true ) );
$created = $core_write_package->create_draft(
	array(
		'title' => 'Migrated Draft',
		'content' => "# Migrated Draft\n\n![Alt](https://example.test/image.jpg)\n\nBody text.",
		'content_format' => 'markdown',
		'commit' => true,
		'meta' => array( 'source' => 'unit' ),
	)
);
unset( $GLOBALS['magick_ai_runtime_wp_ability_context'] );
maa_assert_same( false, $created['dry_run'] ?? null, 'create-draft commit returns a committed payload' );
maa_assert_same( 'markdown', $created['content_format'] ?? '', 'create-draft preserves migrated markdown content_format reporting' );
$created_post = get_post( (int) ( $created['post_id'] ?? 0 ) );
maa_assert_true( is_object( $created_post ), 'create-draft commit creates a draft post in the standalone package' );
maa_assert_true( false === strpos( (string) ( $created_post->post_content ?? '' ), '<h1>Migrated Draft</h1>' ), 'create-draft strips a duplicate leading title heading after migration' );
maa_assert_true( false !== strpos( (string) ( $created_post->post_content ?? '' ), '<img src="https://example.test/image.jpg" alt="Alt" />' ), 'create-draft converts markdown image syntax after migration' );

$update_preview = $core_write_package->update_post(
	array(
		'post_id' => 501,
		'content' => "## Updated heading\n\nUpdated body.",
		'dry_run' => true,
	)
);
maa_assert_same( true, $update_preview['dry_run'] ?? null, 'update-post returns a governed dry-run preview after migration' );
maa_assert_same( 'markdown', $update_preview['changes']['content']['content_format'] ?? '', 'update-post auto-detects markdown content after migration' );

$GLOBALS['maa_unit_post_meta'][501]['_yoast_wpseo_title'] = 'Old SEO title';
$seo_preview = $core_write_package->set_post_seo_meta(
	array(
		'post_id' => 501,
		'seo_title' => 'New SEO title',
		'seo_description' => 'New SEO description',
		'dry_run' => true,
	)
);
maa_assert_same( true, $seo_preview['dry_run'] ?? null, 'set-post-seo-meta returns a governed dry-run preview after migration' );
maa_assert_same( 'yoast', $seo_preview['provider'] ?? '', 'set-post-seo-meta detects existing Yoast-style SEO metadata after migration' );
$GLOBALS['magick_ai_runtime_wp_ability_context'] = array( 'context' => array( 'approval_commit_authorized' => true ) );
$seo_written = $core_write_package->set_post_seo_meta(
	array(
		'post_id' => 501,
		'seo_title' => 'Committed SEO title',
		'seo_description' => 'Committed SEO description',
		'commit' => true,
	)
);
unset( $GLOBALS['magick_ai_runtime_wp_ability_context'] );
maa_assert_same( false, $seo_written['dry_run'] ?? null, 'set-post-seo-meta commit returns a committed payload after migration' );
maa_assert_same( 'Committed SEO title', $GLOBALS['maa_unit_post_meta'][501]['_yoast_wpseo_title'] ?? '', 'set-post-seo-meta writes SEO title through standalone fallback metadata keys' );
maa_assert_same( 'Committed SEO description', $GLOBALS['maa_unit_post_meta'][501]['_yoast_wpseo_metadesc'] ?? '', 'set-post-seo-meta writes SEO description through standalone fallback metadata keys' );

$patch_preview = $core_write_package->patch_post_content(
	array(
		'post_id' => 501,
		'operations' => array(
			array(
				'op' => 'replace',
				'find' => 'Original body marker',
				'replace' => 'Patched body marker',
			),
		),
		'dry_run' => true,
	)
);
maa_assert_same( true, $patch_preview['dry_run'] ?? null, 'patch-post-content returns a governed dry-run preview after migration' );
maa_assert_same( 1, $patch_preview['patch_preview'][0]['applied'] ?? null, 'patch-post-content reports applied operation count after migration' );

$blocks_preview = $core_write_package->update_post_blocks(
	array(
		'post_id' => 501,
		'blocks' => array(
			array(
				'blockName' => 'core/paragraph',
				'innerHTML' => '<p>Block body.</p>',
			),
		),
		'dry_run' => true,
	)
);
maa_assert_same( true, $blocks_preview['dry_run'] ?? null, 'update-post-blocks returns a governed dry-run preview after migration' );
maa_assert_same( true, $blocks_preview['validation']['valid'] ?? null, 'update-post-blocks validates serialized blocks after migration' );
unset( $GLOBALS['maa_unit_style_posts'], $GLOBALS['maa_unit_post_meta'] );
$inspect_page_structure = $package_abilities['magick-ai/inspect-page-structure'];
maa_assert_same( 'magick-ai-pages', $inspect_page_structure['category'], 'inspect-page-structure uses page category' );
maa_assert_same( 1, $inspect_page_structure['input_schema']['properties']['max_pages']['minimum'] ?? null, 'inspect-page-structure max_pages minimum is 1' );
maa_assert_same( 100, $inspect_page_structure['input_schema']['properties']['max_pages']['maximum'] ?? null, 'inspect-page-structure max_pages maximum is 100' );
maa_assert_same( 50, $inspect_page_structure['input_schema']['properties']['max_pages']['default'] ?? null, 'inspect-page-structure max_pages default is 50' );
$proposal_excerpt = $package_abilities['magick-ai/propose-post-excerpt'];
maa_assert_same( true, $proposal_excerpt['annotations']['readonly'], 'propose-post-excerpt remains proposal-only and readonly' );
maa_assert_same( false, $proposal_excerpt['requires_confirm'], 'propose-post-excerpt does not perform a final write' );
$GLOBALS['maa_unit_comments'] = array(
	11 => (object) array(
		'comment_ID' => 11,
		'comment_post_ID' => 77,
		'comment_author' => 'Promo Bot',
		'comment_approved' => 'hold',
		'comment_content' => 'Buy now discount coupon https://example.test https://promo.example.test',
	),
	12 => (object) array(
		'comment_ID' => 12,
		'comment_post_ID' => 77,
		'comment_author' => 'Reader',
		'comment_approved' => 'hold',
		'comment_content' => '@admin 请问这个报错怎么处理？',
	),
);
$comment_suggest = $core_comment_package->build_comment_moderation_suggest(
	array(
		'comment_id' => 11,
	)
);
maa_assert_same( true, $comment_suggest['success'] ?? null, 'build-comment-moderation-suggest returns a success envelope' );
maa_assert_same( 'spam', $comment_suggest['data']['recommended_action'] ?? '', 'build-comment-moderation-suggest flags promotional comments as spam' );
maa_assert_true( in_array( 'commercial_promo', $comment_suggest['data']['risk_flags'] ?? array(), true ), 'build-comment-moderation-suggest exposes commercial promo risk flag' );
$GLOBALS['maa_unit_comments'][13] = (object) array(
	'comment_ID'      => 13,
	'comment_post_ID' => 77,
	'comment_author'  => 'Pharmacy Bot',
	'comment_approved' => 'hold',
	'comment_content' => 'Buy cheap pills now',
);
$pharmacy_comment_suggest = $core_comment_package->build_comment_moderation_suggest(
	array(
		'comment_id' => 13,
	)
);
maa_assert_same( 'spam', $pharmacy_comment_suggest['data']['recommended_action'] ?? '', 'build-comment-moderation-suggest flags pharmacy spam without relying on links' );
$comment_result = $core_comment_package->compose_comment_moderation_result(
	array(
		'comment_id' => 11,
		'mode' => 'suggest',
		'suggest_result' => $comment_suggest['data'],
	)
);
maa_assert_same( true, $comment_result['success'] ?? null, 'compose-comment-moderation-result returns a success envelope' );
maa_assert_same( 'spam', $comment_result['data']['recommended_action'] ?? '', 'compose-comment-moderation-result keeps recommended action' );
$mention_suggest = $core_comment_package->build_comment_mention_reply_suggest(
	array(
		'comment_id' => 12,
		'trigger_type' => 'mention',
	)
);
maa_assert_same( true, $mention_suggest['success'] ?? null, 'build-comment-mention-reply-suggest returns a success envelope' );
maa_assert_same( true, $mention_suggest['data']['trigger']['trigger_detected'] ?? null, 'build-comment-mention-reply-suggest detects mention trigger' );
$trigger_queue = $core_comment_package->read_comment_trigger_queue(
	array(
		'post_id' => 77,
		'trigger_type' => 'mention',
		'status' => 'hold',
	)
);
maa_assert_same( 1, $trigger_queue['data']['summary']['candidate_count'] ?? null, 'read-comment-trigger-queue returns detected mention candidates' );
$batch_suggest = $core_comment_package->build_comment_moderation_batch_suggest(
	array(
		'comment_ids' => array( 11, 12 ),
	)
);
maa_assert_same( true, $batch_suggest['success'] ?? null, 'build-comment-moderation-batch-suggest returns a success envelope' );
maa_assert_same( 2, $batch_suggest['data']['batch_summary']['counts']['total'] ?? null, 'build-comment-moderation-batch-suggest counts batch items' );
$batch_result = $core_comment_package->compose_comment_moderation_batch_result(
	array(
		'batch_result' => $batch_suggest['data'],
	)
);
maa_assert_same( 'review_individual_items', $batch_result['data']['next_action'] ?? '', 'compose-comment-moderation-batch-result keeps single-item handoff' );
$resolved_metadata_plan = $core_read_package->resolve_post_metadata_plan(
	array(
		'post_metadata_plan' => array(
			'excerpt_mode' => 'explicit',
			'excerpt' => '这是新的摘要。',
			'slug_mode' => 'explicit',
			'slug' => 'New Canonical Slug!',
			'categories' => array( '3', '5', '5', 'bad' ),
			'tags' => array(),
			'publish_at' => '2030-01-02 03:04:05',
			'author_id' => 12,
			'template' => 'landing.php',
			'format' => 'image',
		),
		'taxonomy_plan' => array(
			'categories' => array( '1' ),
			'tags' => array( '8', '13' ),
		),
		'generated_excerpt' => '备用摘要',
		'generated_slug' => 'fallback-slug',
	)
);
maa_assert_same( true, $resolved_metadata_plan['success'] ?? null, 'resolve-post-metadata-plan returns a success envelope' );
maa_assert_same( '这是新的摘要。', $resolved_metadata_plan['data']['excerpt'] ?? '', 'resolve-post-metadata-plan preserves explicit excerpt' );
maa_assert_same( 'new-canonical-slug', $resolved_metadata_plan['data']['slug'] ?? '', 'resolve-post-metadata-plan sanitizes explicit slug' );
maa_assert_same( array( 3, 5 ), $resolved_metadata_plan['data']['categories'] ?? array(), 'resolve-post-metadata-plan prefers metadata categories' );
maa_assert_same( array( 8, 13 ), $resolved_metadata_plan['data']['tags'] ?? array(), 'resolve-post-metadata-plan falls back to taxonomy tags' );
maa_assert_same( '2030-01-02 03:04:05', $resolved_metadata_plan['data']['publish_at'] ?? '', 'resolve-post-metadata-plan preserves publish_at handoff' );
maa_assert_same( 12, $resolved_metadata_plan['data']['author_id'] ?? 0, 'resolve-post-metadata-plan normalizes author_id handoff' );
maa_assert_same( 'landing.php', $resolved_metadata_plan['data']['template'] ?? '', 'resolve-post-metadata-plan preserves template handoff' );
maa_assert_same( 'image', $resolved_metadata_plan['data']['format'] ?? '', 'resolve-post-metadata-plan normalizes format handoff' );
$inline_blocks = $core_read_package->build_inline_image_blocks(
	array(
		'uploaded_inline_media' => array(
			array(
				'attachment_id' => 44,
				'url' => 'https://example.test/alpha.jpg',
				'alt' => 'Fallback alt',
			),
		),
		'inline_plan' => array(
			array(
				'alt' => 'Inline alt',
				'caption' => 'Inline caption',
				'placement_key' => 'alpha-hero',
			),
		),
	)
);
maa_assert_same( true, $inline_blocks['success'] ?? null, 'build-inline-image-blocks returns a success envelope' );
maa_assert_same( 1, $inline_blocks['data']['summary']['count'] ?? 0, 'build-inline-image-blocks counts generated blocks' );
maa_assert_same( 'core/image', $inline_blocks['data']['blocks'][0]['blockName'] ?? '', 'build-inline-image-blocks emits Gutenberg image blocks' );
maa_assert_same( 'magick-ai-inline-image alpha-hero', $inline_blocks['data']['blocks'][0]['attrs']['className'] ?? '', 'build-inline-image-blocks preserves placement class key' );
$media_assets = $core_read_package->build_media_seo_assets(
	array(
		'article' => array( 'title' => 'Article title' ),
		'resolved_image_source' => array(
			'featured' => array(
				'image_origin' => 'ai_generated',
				'prompt' => 'Generated hero prompt',
				'role' => 'featured',
			),
			'inline' => array(
				array(
					'provider_hint' => 'pexels',
					'provider_title' => 'Provider title',
					'section_heading' => 'Inline section',
				),
			),
		),
		'generated_featured_upload' => array(
			'attachment_id' => 88,
			'url' => 'https://example.test/generated.jpg',
			'file_name' => 'generated.jpg',
		),
		'inline_uploads' => array(
			array(
				'attachment_id' => 89,
				'url' => 'https://example.test/inline.jpg',
				'file_name' => 'inline.jpg',
			),
		),
	)
);
maa_assert_same( true, $media_assets['success'] ?? null, 'build-media-seo-assets returns a success envelope' );
maa_assert_same( 2, $media_assets['data']['summary']['asset_count'] ?? 0, 'build-media-seo-assets counts featured plus inline assets' );
maa_assert_same( 'ai_generated', $media_assets['data']['items'][0]['image_origin'] ?? '', 'build-media-seo-assets preserves generated image origin' );
maa_assert_same( 'public_free', $media_assets['data']['items'][1]['image_origin'] ?? '', 'build-media-seo-assets infers public-free provider origin' );
$geo_analysis = $core_read_package->geo_analyze(
	array(
		'title' => 'WordPress AI visibility',
		'content' => 'WordPress AI visibility 是什么？It helps answer boxes reuse concise article sections. Teams should add FAQ blocks and direct summaries.',
		'excerpt' => '',
		'focus_keyword' => 'AI visibility',
	)
);
maa_assert_same( true, $geo_analysis['success'] ?? null, 'geo-analyze returns a success envelope' );
maa_assert_same( 1, $geo_analysis['data']['summary']['faq_candidate_count'] ?? 0, 'geo-analyze extracts question candidates' );
$optimized_media = $core_read_package->optimize_media_metadata(
	array(
		'article_title' => 'Article title',
		'article_excerpt' => 'Article excerpt',
		'focus_keyword' => 'media SEO',
		'media_assets' => array(
			array(
				'attachment_id' => 90,
				'url' => 'https://example.test/manual.jpg',
				'mime_type' => 'image/jpeg',
				'file_name' => 'manual.jpg',
				'vision_fallback_allowed' => true,
			),
		),
	)
);
maa_assert_same( true, $optimized_media['success'] ?? null, 'optimize-media-metadata returns a success envelope' );
maa_assert_same( 1, $optimized_media['data']['summary']['missing_alt_count'] ?? 0, 'optimize-media-metadata counts missing alt text' );
$positioned_blocks = $core_read_package->position_inline_image_blocks(
	array(
		'existing_blocks' => array(
			array(
				'blockName' => 'core/paragraph',
				'attrs' => array(),
				'innerHTML' => '<p>Intro</p>',
				'innerBlocks' => array(),
			),
			array(
				'blockName' => 'core/heading',
				'attrs' => array( 'anchor' => 'target-heading' ),
				'innerHTML' => '<h2>Target heading</h2>',
				'innerBlocks' => array(),
			),
		),
		'inline_blocks' => $inline_blocks['data']['blocks'] ?? array(),
		'inline_plan' => array(
			array(
				'placement_key' => 'alpha-hero',
				'placement' => 'after_heading',
				'section_anchor' => 'target-heading',
				'section_heading' => 'Target heading',
			),
		),
	)
);
maa_assert_same( true, $positioned_blocks['success'] ?? null, 'position-inline-image-blocks returns a success envelope' );
maa_assert_same( 1, $positioned_blocks['data']['summary']['positioned_count'] ?? 0, 'position-inline-image-blocks positions matching inline blocks' );
maa_assert_same( 'core/image', $positioned_blocks['data']['blocks'][2]['blockName'] ?? '', 'position-inline-image-blocks inserts after matching heading' );
$article_production_fingerprint = $core_read_package->build_article_production_fingerprint(
	array(
		'topic'              => 'Local article production',
		'image_mode'         => 'featured_only',
		'publish_mode'       => 'draft',
		'content_format'     => 'html',
		'voice_profile'      => 'practical_editorial',
		'opening_style'      => 'scene',
		'structure_style'    => 'asymmetric',
		'reference_post_ids' => array( 9, 3 ),
	)
);
$article_production_fingerprint_reordered = $core_read_package->build_article_production_fingerprint(
	array(
		'topic'              => 'Local article production',
		'image_mode'         => 'featured_only',
		'publish_mode'       => 'draft',
		'content_format'     => 'html',
		'voice_profile'      => 'practical_editorial',
		'opening_style'      => 'scene',
		'structure_style'    => 'asymmetric',
		'reference_post_ids' => array( 3, 9 ),
	)
);
maa_assert_same( true, $article_production_fingerprint['success'] ?? null, 'build-article-production-fingerprint returns a success envelope' );
maa_assert_same( 16, strlen( (string) ( $article_production_fingerprint['data']['production_fingerprint'] ?? '' ) ), 'build-article-production-fingerprint emits a compact 16-character hash' );
maa_assert_same(
	$article_production_fingerprint['data']['production_fingerprint'] ?? '',
	$article_production_fingerprint_reordered['data']['production_fingerprint'] ?? null,
	'build-article-production-fingerprint sorts reference post ids for stable dedupe'
);
$article_duplicate_check = $core_read_package->check_article_production_duplicate(
	array(
		'production_fingerprint' => (string) ( $article_production_fingerprint['data']['production_fingerprint'] ?? '' ),
		'write_guard_mode'       => 'preserve_manual_edits',
	)
);
maa_assert_same( true, $article_duplicate_check['success'] ?? null, 'check-article-production-duplicate returns a success envelope' );
maa_assert_same( false, $article_duplicate_check['data']['duplicate_found'] ?? null, 'check-article-production-duplicate stays readonly when no WordPress lookup is available' );
maa_assert_same( false, $article_duplicate_check['data']['skip_recommended'] ?? null, 'check-article-production-duplicate does not recommend skipping without a duplicate' );
$article_review_light = $core_read_package->review_article_output_light(
	array(
		'article' => array(
			'content' => implode(
				"\n\n",
				array(
					'<p>首先，AI 写作正在快速发展，因此我们需要全面认识它的优势与不足。</p>',
					'<p>其次，AI 写作可以提高效率，因此企业应该结合实际情况进行合理应用。</p>',
					'<p>最后，AI 写作未来前景广阔，因此团队需要持续优化流程与质量控制。</p>',
					'<p>综上所述，AI 写作具有重要价值，因此我们应当积极拥抱并持续探索。</p>',
				)
			),
		),
		'style_profile' => array(
			'resolved_opening_style' => 'scene',
			'resolved_voice_profile' => 'experiential_editorial',
		),
		'platform_profile' => 'wechat',
		'human_signals' => array(),
		'image_mode' => 'featured_only',
		'media' => array(
			'featured_attached' => true,
		),
	)
);
$article_review_data = is_array( $article_review_light['data'] ?? null ) ? $article_review_light['data'] : array();
$article_ai_risk_review = is_array( $article_review_data['ai_risk_review'] ?? null ) ? $article_review_data['ai_risk_review'] : array();
$article_ai_risk_keys = array();
foreach ( is_array( $article_ai_risk_review['items'] ?? null ) ? $article_ai_risk_review['items'] : array() as $risk_item ) {
	$risk_item = is_array( $risk_item ) ? $risk_item : array();
	$article_ai_risk_keys[] = sanitize_key( (string) ( $risk_item['key'] ?? '' ) );
}
maa_assert_same( true, $article_review_light['success'] ?? null, 'review-article-output-light returns a success envelope' );
maa_assert_same( true, $article_review_data['needs_human_review'] ?? null, 'review-article-output-light flags template-heavy output for review' );
maa_assert_same( 'high', $article_review_data['template_risk_level'] ?? '', 'review-article-output-light preserves high template risk semantics' );
maa_assert_true( in_array( 'human_signal_gap', $article_ai_risk_keys, true ), 'review-article-output-light reports missing human signal risk' );
maa_assert_true( in_array( 'evidence_gap', $article_ai_risk_keys, true ), 'review-article-output-light reports missing evidence risk' );
$reference_style_review = $core_read_package->review_article_output_light(
	array(
		'article' => array(
			'content' => "<p>最近我们在一次编辑复盘里突然意识到，文章一旦写得太顺，反而像机器。</p>\n\n<p>我后来会故意先把现场判断扔出来，再补背景，因为团队实操和踩坑本来就不是教科书顺序。</p>\n\n<p>我们照着这个节奏改完以后，内容没那么完美，但至少终于像人写的了。</p>",
		),
		'style_profile' => array(
			'resolved_opening_style' => 'scene',
			'resolved_voice_profile' => 'experiential_editorial',
		),
		'image_mode' => 'featured_only',
		'media' => array(
			'featured_attached' => true,
		),
	)
);
maa_assert_same( false, $reference_style_review['data']['needs_human_review'] ?? null, 'review-article-output-light relaxes missing evidence risks when reference style strongly matches' );
maa_assert_same( 'ready_for_editorial_review', $reference_style_review['data']['next_action'] ?? '', 'review-article-output-light keeps matching reference-style output reviewable' );
$production_result = $core_read_package->compose_article_production_result(
	array(
		'input' => array(
			'topic'              => 'Local article production',
			'image_mode'         => 'featured_and_inline',
			'publish_mode'       => 'draft',
			'reference_post_ids' => array( 9, 3 ),
			'write_guard_mode'   => 'preserve_manual_edits',
		),
		'draft' => array(
			'post_id'      => 99,
			'edit_link'    => 'https://example.test/edit/99',
			'preview_link' => 'https://example.test/preview/99',
			'public_link'  => 'https://example.test/article/99',
		),
		'media' => array(
			'featured_attached' => true,
			'position_inline_image_blocks' => array(
				'summary' => array(
					'positioned_count' => 1,
					'appended_count'   => 1,
				),
			),
		),
		'review' => array(
			'writing_naturalness'   => 70,
			'style_match_score'     => 94,
			'image_relevance_score' => 82,
			'needs_human_review'    => false,
			'next_action'           => 'needs_layout_review',
			'template_risk_level'   => 'medium',
			'signals'               => array(
				'anti_template_findings' => array( '段落层次偏少，容易显得像模板摘要。' ),
				'style_findings'         => array(),
			),
		),
		'duplicate_guard' => array(
			'production_fingerprint' => 'abc123def4567890',
			'duplicate_found'        => true,
			'skip_recommended'       => true,
			'summary_text'           => '检测到同指纹文章 #88《已有草稿》，建议先复核现有稿件。',
			'duplicate_candidate'    => array(
				'post_id' => 88,
				'title'   => '已有草稿',
			),
		),
	)
);
$production_data = is_array( $production_result['data'] ?? null ) ? $production_result['data'] : array();
maa_assert_same( true, $production_result['success'] ?? null, 'compose-article-production-result returns a success envelope' );
maa_assert_same( 'degraded', $production_data['result_mode'] ?? '', 'compose-article-production-result exposes degraded result semantics' );
maa_assert_true( in_array( 'inline_position_partial_fallback', (array) ( $production_data['degraded_reasons'] ?? array() ), true ), 'compose-article-production-result preserves inline fallback degradation' );
maa_assert_true( in_array( 'duplicate_production_candidate', (array) ( $production_data['degraded_reasons'] ?? array() ), true ), 'compose-article-production-result preserves duplicate guard degradation' );
maa_assert_same( 'abc123def4567890', $production_data['production_fingerprint'] ?? '', 'compose-article-production-result preserves duplicate fingerprint' );
maa_assert_same( true, $production_data['skip_recommended'] ?? null, 'compose-article-production-result preserves duplicate skip recommendation' );
maa_assert_true( in_array( 'duplicate_candidate_detected', (array) ( $production_data['completed_stages'] ?? array() ), true ), 'compose-article-production-result records duplicate candidate stage' );
maa_assert_true( false !== strpos( (string) ( $production_data['summary_text'] ?? '' ), '检测到可复用旧稿' ), 'compose-article-production-result summarizes duplicate reuse handoff' );
$production_result_fallback = $core_read_package->compose_article_production_result(
	array(
		'input' => array(
			'topic'              => 'Local article production',
			'image_mode'         => 'featured_only',
			'publish_mode'       => 'draft',
			'content_format'     => 'html',
			'voice_profile'      => 'practical_editorial',
			'opening_style'      => 'scene',
			'structure_style'    => 'asymmetric',
			'reference_post_ids' => array( 3, 9 ),
		),
	)
);
maa_assert_same(
	(string) ( $article_production_fingerprint['data']['production_fingerprint'] ?? '' ),
	(string) ( $production_result_fallback['data']['production_fingerprint'] ?? '' ),
	'compose-article-production-result fills a stable fingerprint when duplicate guard omits one'
);
$GLOBALS['maa_unit_style_posts'] = array(
	11 => (object) array(
		'ID'           => 11,
		'post_author'  => 7,
		'post_title'   => '现场复盘',
		'post_content' => "<p>最近我们在一次编辑复盘里发现，开头先放现场判断更自然。</p>\n\n<p>我和团队实操时会保留一点踩坑细节，让文章不像模板。</p>\n\n<p>下一步建议继续保留这种判断节奏。</p>",
	),
	12 => (object) array(
		'ID'           => 12,
		'post_author'  => 7,
		'post_title'   => '团队笔记',
		'post_content' => "<p>在一次上线后，我们把数据指标和人工判断放在一起看。</p>\n\n<p>团队实操中最有价值的是先说取舍，再补背景。</p>\n\n<p>可以把这种写法沉淀成编辑基线。</p>",
	),
	13 => (object) array(
		'ID'           => 13,
		'post_author'  => 3,
		'post_title'   => '站点样本',
		'post_content' => "<p>如果只看单篇文章，很容易误判站点风格。</p>\n\n<p>把最近内容合起来看，结构和语气会更稳定。</p>\n\n<p>总的来说，基线应该轻量而可复用。</p>",
	),
);
$GLOBALS['maa_unit_transients'] = array();
$reference_style = $core_read_package->extract_reference_post_style(
	array(
		'reference_post_ids' => array( 11, 12 ),
	)
);
maa_assert_same( true, $reference_style['success'] ?? null, 'extract-reference-post-style returns a success envelope' );
maa_assert_same( 2, $reference_style['data']['profile']['sample_count'] ?? null, 'extract-reference-post-style profiles two reference samples' );
maa_assert_same( 'scene', $reference_style['data']['profile']['dominant_opening_style'] ?? '', 'extract-reference-post-style detects scene-led openings' );
maa_assert_same( 'action', $reference_style['data']['profile']['dominant_ending_style'] ?? '', 'extract-reference-post-style detects action endings' );
maa_assert_true( false !== strpos( (string) ( $reference_style['data']['profile']['style_brief'] ?? '' ), '段落平均长度约' ), 'extract-reference-post-style returns a compact style brief' );
$GLOBALS['maa_unit_transients'] = array();
$author_baseline = $core_read_package->extract_style_baseline(
	array(
		'mode'      => 'author_recent',
		'author_id' => 7,
		'limit'     => 4,
	)
);
maa_assert_same( true, $author_baseline['success'] ?? null, 'extract-style-baseline returns a success envelope' );
maa_assert_same( 'author_recent', $author_baseline['data']['source'] ?? '', 'extract-style-baseline keeps author source when author samples exist' );
maa_assert_same( 2, $author_baseline['data']['profile']['sample_count'] ?? null, 'extract-style-baseline profiles author samples' );
$GLOBALS['maa_unit_transients'] = array();
$site_baseline = $core_read_package->extract_style_baseline(
	array(
		'mode'  => 'site_recent',
		'limit' => 3,
	)
);
maa_assert_same( 'site_recent', $site_baseline['data']['source'] ?? '', 'extract-style-baseline keeps site source for site baselines' );
maa_assert_same( 3, $site_baseline['data']['profile']['sample_count'] ?? null, 'extract-style-baseline profiles site samples' );
$optimization_report = $core_read_package->build_article_optimization_report(
	array(
		'post'           => array(
			'id'        => 42,
			'title'     => 'Optimization target',
			'status'    => 'publish',
			'edit_link' => 'https://example.test/wp-admin/post.php?post=42&action=edit',
		),
		'seo'            => array(
			'recommendations' => array(
				array(
					'type'     => 'keyword',
					'priority' => 'high',
					'title'    => '补焦点关键词',
					'detail'   => '首段补齐关键词。',
				),
			),
		),
		'geo'            => array(
			'recommendations' => array(
				array(
					'type'     => 'answerability',
					'priority' => 'medium',
					'title'    => '补答案摘要',
					'detail'   => '增加 answer-first 段落。',
				),
			),
		),
		'internal_links' => array(
			'placement_plan' => array(
				array(
					'anchor_text' => '内部链接锚点',
					'reason'      => '连接相关旧文。',
				),
			),
		),
		'media'          => array(
			'assets' => array(
				array(
					'issues' => array( '缺少 alt', '缺少 caption' ),
				),
			),
		),
	)
);
maa_assert_same( true, $optimization_report['success'] ?? null, 'build-article-optimization-report returns a success envelope' );
maa_assert_same( 42, $optimization_report['data']['post']['post_id'] ?? null, 'build-article-optimization-report normalizes post id' );
maa_assert_same( 'needs_attention', $optimization_report['data']['summary']['status'] ?? '', 'build-article-optimization-report marks high-priority reports for attention' );
maa_assert_same( 4, $optimization_report['data']['summary']['total_recommendations'] ?? null, 'build-article-optimization-report merges SEO, GEO, internal link, and media recommendations' );
$seo_report_context = $core_read_package->build_seo_report_context(
	array(
		'input'         => '<p>这是一段偏短的正文，用来触发内容深度建议。</p>',
		'focus_keyword' => '本地能力包',
	)
);
maa_assert_same( true, $seo_report_context['success'] ?? null, 'seo-report-context returns a success envelope' );
maa_assert_same( 'local_seo_report_context', $seo_report_context['meta']['source'] ?? '', 'seo-report-context records deterministic source metadata' );
maa_assert_same( 1, $seo_report_context['data']['summary']['high_priority_count'] ?? null, 'seo-report-context counts missing focus keyword as high priority' );
maa_assert_true( 88 > (int) ( $seo_report_context['data']['score'] ?? 88 ), 'seo-report-context lowers score when checks fail' );
$GLOBALS['maa_unit_style_posts'][77] = (object) array(
	'ID'           => 77,
	'post_title'   => 'Optimization Context Post',
	'post_status'  => 'draft',
	'post_type'    => 'post',
	'post_excerpt' => '',
	'post_content' => 'Optimization context content.',
	'post_name'    => 'optimization-context-post',
);
$post_context = $core_read_package->read_post_optimization_context(
	array(
		'post_id' => 77,
	)
);
maa_assert_same( true, $post_context['success'] ?? null, 'read-post-optimization-context returns a success envelope' );
maa_assert_same( 77, $post_context['data']['id'] ?? null, 'read-post-optimization-context reads the requested post id' );
maa_assert_same( 'optimization-context-post', $post_context['data']['slug'] ?? '', 'read-post-optimization-context exposes slug for optimization workflows' );
maa_assert_same( 'standard', $post_context['data']['format'] ?? '', 'read-post-optimization-context defaults empty post format to standard' );
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
maa_assert_same( true, $single_suggest['success'] ?? null, 'build-article-single-optimization-suggest returns a success envelope' );
maa_assert_same( array( 'excerpt', 'seo_title', 'seo_description', 'slug' ), $single_suggest['data']['summary']['safe_apply_fields'] ?? array(), 'build-article-single-optimization-suggest keeps low-risk safe apply fields' );
maa_assert_true( ! empty( $single_suggest['data']['content_improvements'] ), 'build-article-single-optimization-suggest emits content improvements' );
maa_assert_true( ! empty( $single_suggest['data']['seo_improvements'] ), 'build-article-single-optimization-suggest emits SEO improvements' );
maa_assert_true( ! empty( $single_suggest['data']['geo_improvements'] ), 'build-article-single-optimization-suggest emits GEO improvements' );
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
maa_assert_same( true, $apply_plan['success'] ?? null, 'build-article-optimization-apply-plan returns a success envelope' );
maa_assert_same( true, $apply_plan['data']['actions']['excerpt']['apply_generate'] ?? null, 'build-article-optimization-apply-plan marks generated excerpt as safe apply when explicitly requested' );
maa_assert_same( array( 'update_excerpt' ), $apply_plan['data']['summary']['safe_apply_supported'] ?? array(), 'build-article-optimization-apply-plan exposes safe apply action summary' );
$apply_result = $core_read_package->compose_article_optimization_apply_result(
	array(
		'report'        => array(
			'summary' => array(
				'status' => 'needs_attention',
			),
		),
		'apply_plan'    => $apply_plan['data'],
		'apply_excerpt' => array(
			'updated' => true,
			'post_id' => 77,
			'changes' => array(
				'excerpt' => 'Generated excerpt.',
			),
		),
	)
);
maa_assert_same( true, $apply_result['success'] ?? null, 'compose-article-optimization-apply-result returns a success envelope' );
maa_assert_same( 'partial_apply', $apply_result['data']['summary']['result_mode'] ?? '', 'compose-article-optimization-apply-result marks partial apply when excerpt changed' );
maa_assert_same( 1, $apply_result['data']['summary']['applied_count'] ?? null, 'compose-article-optimization-apply-result counts applied changes' );
$draft_result = $core_read_package->compose_article_draft_result(
	array(
		'input' => array(
			'topic'            => 'Local draft',
			'preview_only'     => true,
			'platform_profile' => 'wechat',
			'human_signals'    => array( '案例/数据来源：内部复盘记录' ),
		),
		'draft' => array(
			'post_id'      => 0,
			'preview_link' => 'https://example.test/preview/draft',
		),
		'generated_seo' => array(
			'title'       => 'Local draft SEO title',
			'description' => 'Local draft SEO description',
		),
		'metadata_plan_resolution' => array(
			'slug' => 'local-draft',
		),
		'seo_analysis' => array(
			'overall_score' => 72,
		),
		'geo_analysis' => array(
			'geo_score' => 82,
		),
		'quality_scoring' => array(
			'overall_score' => 58,
			'improvement_suggestions' => array( '补充更具体的真实案例。' ),
		),
		'review' => array(
			'needs_human_review'  => true,
			'next_action'         => 'needs_human_review',
			'template_risk_level' => 'medium',
		),
	)
);
$draft_data = is_array( $draft_result['data'] ?? null ) ? $draft_result['data'] : array();
maa_assert_same( true, $draft_result['success'] ?? null, 'compose-article-draft-result returns a success envelope' );
maa_assert_same( true, $draft_data['draft']['preview_only'] ?? null, 'compose-article-draft-result preserves preview-only mode' );
maa_assert_same( false, $draft_data['draft']['real_draft_created'] ?? null, 'compose-article-draft-result does not claim a real draft in preview mode' );
maa_assert_same( 'review_preview', $draft_data['handoff']['next_action'] ?? '', 'compose-article-draft-result keeps preview handoff local to draft workflow' );
maa_assert_same( 'workflow/wordpress_article_draft', $draft_data['handoff']['recommended_entry'] ?? '', 'compose-article-draft-result keeps draft recommended entry for preview-only output' );
maa_assert_same( '内部复盘记录', $draft_data['source_references'][0] ?? '', 'compose-article-draft-result extracts source references from human signals' );
$publication_decision = $core_read_package->resolve_article_publication_decision(
	array(
		'publish_mode' => 'schedule',
		'review'       => array( 'needs_human_review' => true ),
	)
);
maa_assert_same( true, $publication_decision['success'] ?? null, 'resolve-article-publication-decision returns a success envelope' );
maa_assert_same( 'schedule', $publication_decision['data']['requested_publish_mode'] ?? '', 'resolve-article-publication-decision preserves requested mode' );
maa_assert_same( 'review', $publication_decision['data']['effective_publish_mode'] ?? '', 'resolve-article-publication-decision routes blocked schedules to review' );
maa_assert_same( true, $publication_decision['data']['publish_blocked'] ?? null, 'resolve-article-publication-decision marks human-review gate as blocked' );
maa_assert_same( 'quality_review_requires_handoff', $publication_decision['data']['gate_reason'] ?? '', 'resolve-article-publication-decision records quality gate reason' );
$template_publication_decision = $core_read_package->resolve_article_publication_decision(
	array(
		'publish_mode' => 'publish',
		'review'       => array(
			'needs_human_review' => true,
			'template_risk_level' => 'high',
		),
	)
);
maa_assert_same( 'template_style_requires_handoff', $template_publication_decision['data']['gate_reason'] ?? '', 'resolve-article-publication-decision records high template risk gate reason' );
$duplicate_publication_decision = $core_read_package->resolve_article_publication_decision(
	array(
		'publish_mode'     => 'publish',
		'duplicate_guard'  => array( 'skip_recommended' => true ),
	)
);
maa_assert_same( 'duplicate_production_candidate', $duplicate_publication_decision['data']['gate_reason'] ?? '', 'resolve-article-publication-decision records duplicate gate reason' );
$draft_publication_decision = $core_read_package->resolve_article_publication_decision( array( 'publish_mode' => 'unexpected' ) );
maa_assert_same( 'draft', $draft_publication_decision['data']['requested_publish_mode'] ?? '', 'resolve-article-publication-decision falls back invalid mode to draft' );
maa_assert_same( false, $draft_publication_decision['data']['publish_blocked'] ?? null, 'resolve-article-publication-decision leaves draft unblocked' );
$article_style_profile = $core_read_package->build_article_style_profile(
	array(
		'reference_profile' => array(
			'dominant_voice_profile'   => 'experiential_editorial',
			'dominant_opening_style'   => 'scene',
			'structure_style'          => 'reference-first',
			'style_brief'              => 'Reference article favors lived experience.',
		),
		'baseline_profile' => array(
			'dominant_voice_profile'   => 'practical_editorial',
			'dominant_opening_style'   => 'direct_judgement',
			'structure_style'          => 'baseline-short-paragraphs',
			'style_brief'              => 'Site baseline favors practical judgement.',
		),
		'voice_profile'     => 'practical_editorial',
		'opening_style'     => 'scene',
		'structure_style'   => 'alternating paragraph lengths',
	)
);
maa_assert_same( true, $article_style_profile['success'] ?? null, 'build-article-style-profile returns a success envelope' );
maa_assert_same( 'practical_editorial', $article_style_profile['data']['profile']['resolved_voice_profile'] ?? '', 'build-article-style-profile preserves explicit voice override' );
maa_assert_same( 'scene', $article_style_profile['data']['profile']['resolved_opening_style'] ?? '', 'build-article-style-profile preserves explicit opening override' );
maa_assert_same( 'alternating paragraph lengths', $article_style_profile['data']['profile']['resolved_structure_style'] ?? '', 'build-article-style-profile preserves explicit structure override' );
maa_assert_true( false !== strpos( (string) ( $article_style_profile['data']['profile']['style_brief'] ?? '' ), 'Reference article favors lived experience.' ), 'build-article-style-profile carries reference brief' );
maa_assert_true( false !== strpos( (string) ( $article_style_profile['data']['profile']['style_brief'] ?? '' ), 'Site baseline favors practical judgement.' ), 'build-article-style-profile carries baseline brief' );
$reference_first_style_profile = $core_read_package->build_article_style_profile(
	array(
		'reference_profile' => array(
			'dominant_voice_profile' => 'reference_voice',
			'dominant_opening_style' => 'reference_opening',
			'style_brief' => 'Repeated brief.',
		),
		'baseline_profile' => array(
			'dominant_voice_profile' => 'baseline_voice',
			'dominant_opening_style' => 'baseline_opening',
			'style_brief' => 'Repeated brief.',
		),
	)
);
maa_assert_same( 'reference_voice', $reference_first_style_profile['data']['profile']['resolved_voice_profile'] ?? '', 'build-article-style-profile prefers reference voice over baseline when no explicit override exists' );
maa_assert_same( 'reference_opening', $reference_first_style_profile['data']['profile']['resolved_opening_style'] ?? '', 'build-article-style-profile prefers reference opening over baseline when no explicit override exists' );
maa_assert_same( 'Repeated brief.', $reference_first_style_profile['data']['profile']['style_brief'] ?? '', 'build-article-style-profile deduplicates repeated style briefs' );
$package_bridge = new Magick_Catalog_Bridge( $package_registrar );
$package_catalog = $package_bridge->filter_catalog( array(), array() );
foreach ( $migrated_read_ability_ids as $migrated_ability_id ) {
	$catalog_key = str_replace( '/', '_', $migrated_ability_id );
	maa_assert_true( isset( $package_catalog[ $catalog_key ] ), "catalog bridge projects migrated {$migrated_ability_id}" );
	maa_assert_same( 'wp_ability', $package_catalog[ $catalog_key ]['executor_type'], "{$migrated_ability_id} catalog entry executes through wp_ability" );
	maa_assert_same( false, $package_catalog[ $catalog_key ]['open_api_enabled'], "{$migrated_ability_id} catalog projection does not publish Open API by default" );
}
foreach ( $migrated_write_ability_ids as $migrated_write_ability_id ) {
	$catalog_key = str_replace( '/', '_', $migrated_write_ability_id );
	maa_assert_true( isset( $package_catalog[ $catalog_key ] ), "catalog bridge projects migrated {$migrated_write_ability_id}" );
	maa_assert_same( 'wp_ability', $package_catalog[ $catalog_key ]['executor_type'], "{$migrated_write_ability_id} catalog entry executes through wp_ability" );
	maa_assert_same( true, $package_catalog[ $catalog_key ]['requires_confirm'], "{$migrated_write_ability_id} catalog projection requires confirmation" );
	maa_assert_same( 'write', $package_catalog[ $catalog_key ]['risk_level'], "{$migrated_write_ability_id} catalog projection is write risk" );
	maa_assert_same( 'allow_write', $package_catalog[ $catalog_key ]['write_mode'], "{$migrated_write_ability_id} catalog projection keeps write mode" );
	maa_assert_same( true, $package_catalog[ $catalog_key ]['show_in_rest'], "{$migrated_write_ability_id} catalog projection exposes show_in_rest for host normalization" );
	maa_assert_same( false, $package_catalog[ $catalog_key ]['open_api_enabled'], "{$migrated_write_ability_id} catalog projection does not publish Open API by default" );
	maa_assert_same( true, $package_catalog[ $catalog_key ]['skip_catalog_manifest_fallback'], "{$migrated_write_ability_id} catalog projection skips host default manifest fallback" );
}
foreach ( $migrated_destructive_ability_ids as $migrated_destructive_ability_id ) {
	$catalog_key = str_replace( '/', '_', $migrated_destructive_ability_id );
	maa_assert_true( isset( $package_catalog[ $catalog_key ] ), "catalog bridge projects migrated {$migrated_destructive_ability_id}" );
	maa_assert_same( 'wp_ability', $package_catalog[ $catalog_key ]['executor_type'], "{$migrated_destructive_ability_id} catalog entry executes through wp_ability" );
	maa_assert_same( true, $package_catalog[ $catalog_key ]['requires_confirm'], "{$migrated_destructive_ability_id} catalog projection requires confirmation" );
	maa_assert_same( 'destructive', $package_catalog[ $catalog_key ]['risk_level'], "{$migrated_destructive_ability_id} catalog projection is destructive risk" );
	maa_assert_same( 'allow_write', $package_catalog[ $catalog_key ]['write_mode'], "{$migrated_destructive_ability_id} catalog projection keeps write mode" );
	maa_assert_same( true, $package_catalog[ $catalog_key ]['tool_policy']['allow_destructive'] ?? null, "{$migrated_destructive_ability_id} catalog projection allows destructive only under policy" );
	maa_assert_same( false, $package_catalog[ $catalog_key ]['open_api_enabled'], "{$migrated_destructive_ability_id} catalog projection does not publish Open API by default" );
	maa_assert_same( true, $package_catalog[ $catalog_key ]['skip_catalog_manifest_fallback'], "{$migrated_destructive_ability_id} catalog projection skips host default manifest fallback" );
}
maa_assert_true( ! isset( $package_catalog['magick-ai-abilities_wp-diagnostics-summary'] ), 'catalog bridge does not project standalone diagnostics ability' );

echo "OK: {$assertions} assertions\n";
