<?php
/**
 * Lightweight regression tests.
 *
 * @package MagickAIAbilities
 */

require_once __DIR__ . '/bootstrap.php';

use Magick_AI_Abilities\Integration\Magick_Catalog_Bridge;
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
	'magick-ai/list-users',
	'magick-ai/list-comments',
	'magick-ai/list-menus',
	'magick-ai/get-menu',
	'magick-ai/search-posts',
	'magick-ai/get-post-stats',
	'magick-ai/list-revisions',
	'magick-ai/get-post-meta',
	'magick-ai/list-pages',
	'magick-ai/get-page',
	'magick-ai/inspect-page-structure',
);
$migrated_write_ability_ids = array(
	'magick-ai/set-post-slug',
	'magick-ai/set-post-author',
	'magick-ai/set-post-template',
	'magick-ai/set-post-format',
	'magick-ai/create-term',
	'magick-ai/update-term',
	'magick-ai/update-media-details',
	'magick-ai/approve-comment',
	'magick-ai/reply-comment',
);
$migrated_destructive_ability_ids = array(
	'magick-ai/delete-term',
	'magick-ai/merge-terms',
	'magick-ai/spam-comment',
	'magick-ai/trash-comment',
	'magick-ai/delete-media-permanently',
	'magick-ai/trash-post',
	'magick-ai/delete-post-permanently',
);
maa_assert_true( isset( $package_categories->all()['magick-ai-data'] ), 'core read package registers the legacy magick-ai-data category for compatibility' );
maa_assert_true( isset( $package_categories->all()['magick-ai-pages'] ), 'core read package registers the legacy magick-ai-pages category for compatibility' );
maa_assert_true( isset( $package_categories->all()['magick-ai-write'] ), 'core write package registers the legacy magick-ai-write category for compatibility' );
maa_assert_true( isset( $package_categories->all()['magick-ai-abilities-diagnostics'] ), 'core read package registers the standalone diagnostics category' );
maa_assert_true( isset( $package_abilities['magick-ai-abilities/wp-diagnostics-summary'] ), 'core read package owns standalone wp-diagnostics-summary ability' );
maa_assert_same( 'magick-ai-abilities-diagnostics', $package_abilities['magick-ai-abilities/wp-diagnostics-summary']['category'], 'wp-diagnostics-summary uses standalone diagnostics category' );
foreach ( $migrated_read_ability_ids as $migrated_ability_id ) {
	maa_assert_true( isset( $package_abilities[ $migrated_ability_id ] ), "core read package owns migrated {$migrated_ability_id} ability" );
	maa_assert_package_read_ability_contract( $migrated_ability_id, $package_abilities[ $migrated_ability_id ] );
}
maa_assert_same( true, $package_abilities['magick-ai/site-info']['project_to_magick_catalog'], 'migrated core read abilities project into Magick AI catalog' );
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
$inspect_page_structure = $package_abilities['magick-ai/inspect-page-structure'];
maa_assert_same( 'magick-ai-pages', $inspect_page_structure['category'], 'inspect-page-structure uses page category' );
maa_assert_same( 1, $inspect_page_structure['input_schema']['properties']['max_pages']['minimum'] ?? null, 'inspect-page-structure max_pages minimum is 1' );
maa_assert_same( 100, $inspect_page_structure['input_schema']['properties']['max_pages']['maximum'] ?? null, 'inspect-page-structure max_pages maximum is 100' );
maa_assert_same( 50, $inspect_page_structure['input_schema']['properties']['max_pages']['default'] ?? null, 'inspect-page-structure max_pages default is 50' );
$proposal_excerpt = $package_abilities['magick-ai/propose-post-excerpt'];
maa_assert_same( true, $proposal_excerpt['annotations']['readonly'], 'propose-post-excerpt remains proposal-only and readonly' );
maa_assert_same( false, $proposal_excerpt['requires_confirm'], 'propose-post-excerpt does not perform a final write' );
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
