<?php
/**
 * Lightweight regression tests.
 *
 * @package MagickAIAbilities
 */

require_once __DIR__ . '/bootstrap.php';

use Magick_AI_Abilities\Integration\Magick_Catalog_Bridge;
use Magick_AI_Abilities\Packages\Core_Comment_Pack_Classifier;
use Magick_AI_Abilities\Packages\Core_Comment_Package;
use Magick_AI_Abilities\Packages\Core_Destructive_Package;
use Magick_AI_Abilities\Packages\Core_Read_Pack_Classifier;
use Magick_AI_Abilities\Packages\Core_Read_Package;
use Magick_AI_Abilities\Packages\Core_Write_Package;
use Magick_AI_Abilities\Plugin;
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

function maa_assert_array_omits_keys( $value, array $forbidden_keys, $path ) {
	if ( ! is_array( $value ) ) {
		return;
	}

	foreach ( $value as $key => $child ) {
		if ( is_string( $key ) ) {
			maa_assert_true( ! in_array( $key, $forbidden_keys, true ), "{$path} omits forbidden field {$key}" );
		}

		$child_path = is_string( $key ) ? "{$path}.{$key}" : "{$path}[]";
		maa_assert_array_omits_keys( $child, $forbidden_keys, $child_path );
	}
}

$admin_test_page = file_get_contents( __DIR__ . '/../includes/Admin/Test_Page.php' );
maa_assert_true( false !== strpos( $admin_test_page, 'PARENT_MENU_SLUG' ), 'admin test page knows the shared Magick AI parent slug' );
maa_assert_true( false !== strpos( $admin_test_page, "const MENU_SLUG           = 'magick-ai-abilities';" ), 'admin test page uses the canonical Abilities admin slug' );
maa_assert_true( false !== strpos( $admin_test_page, 'add_submenu_page' ), 'admin test page can attach to the shared Magick AI menu' );
maa_assert_true( false !== strpos( $admin_test_page, 'add_management_page' ), 'admin test page keeps the standalone Tools fallback' );
maa_assert_true( false !== strpos( $admin_test_page, "__( 'Magick AI Abilities', 'magick-ai-abilities' ),\n\t\t\t\t__( 'Abilities', 'magick-ai-abilities' )," ), 'admin test page registers the requested page and submenu titles when attached' );
$old_admin_slug = 'magick-ai-abilities-' . 'test';
maa_assert_true( false === strpos( $admin_test_page, $old_admin_slug ), 'admin test page no longer uses the old test admin slug' );
foreach (
	array(
		'Ability package status',
		'Registered Ability Catalog',
		'Advanced Checks',
		'REST endpoints and browser tests',
		'Demo ability control',
		'Raw ability ids',
		'render_status_summary',
		'render_ability_catalog',
	) as $required
) {
	maa_assert_true( false !== strpos( $admin_test_page, $required ), 'admin test page keeps the focused ability status surface: ' . $required );
}

$admin_surface_standard = file_get_contents( __DIR__ . '/../docs/admin-surface-standard.md' );
foreach (
	array(
		'ability-package status and smoke-test surface',
		'registered ability count',
		'per-ability signals',
		'Core proposal approval',
		'OpenClaw handoff',
		'Cloud API key',
	) as $required
) {
	maa_assert_true( false !== strpos( $admin_surface_standard, $required ), 'admin surface standard documents ability page boundary: ' . $required );
}

$main_plugin_header = file_get_contents( __DIR__ . '/../magick-ai-abilities.php' );
maa_assert_true( false !== strpos( $main_plugin_header, 'Requires at least: 7.0' ), 'main plugin header requires WordPress 7.0' );
maa_assert_true( false !== strpos( $main_plugin_header, 'Requires PHP: 8.0' ), 'main plugin header requires PHP 8.0' );

function maa_schema_contract_fingerprint( array $schema ) {
	$properties = array();
	foreach ( (array) ( $schema['properties'] ?? array() ) as $property_key => $property_schema ) {
		if ( ! is_string( $property_key ) || ! is_array( $property_schema ) ) {
			continue;
		}

		$fingerprint = array();
		foreach ( array( 'type', 'enum', 'default', 'minimum', 'maximum', 'minLength', 'maxLength' ) as $field ) {
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

function maa_core_governance_catalog_snapshot( array $abilities, array $ability_ids ) {
	$snapshot = array(
		'schema_version' => 'v1',
		'purpose'        => 'Core governance handoff contract snapshot for high-value abilities.',
		'abilities'      => array(),
	);

	foreach ( $ability_ids as $ability_id ) {
		$definition = $abilities[ $ability_id ] ?? array();
		maa_assert_true( is_array( $definition ), "snapshot ability {$ability_id} exists" );

		$snapshot['abilities'][ $ability_id ] = array(
			'category'          => (string) ( $definition['category'] ?? '' ),
			'risk_level'        => (string) ( $definition['risk_level'] ?? '' ),
			'requires_confirm'  => (bool) ( $definition['requires_confirm'] ?? false ),
			'requires_approval' => (bool) ( $definition['requires_approval'] ?? false ),
			'capability'        => (string) ( $definition['capability'] ?? '' ),
			'required_scope'    => (string) ( $definition['required_scope'] ?? '' ),
			'required_scopes'   => (array) ( $definition['required_scopes'] ?? array() ),
			'input'             => maa_schema_contract_fingerprint( is_array( $definition['input_schema'] ?? null ) ? $definition['input_schema'] : array() ),
			'output'            => maa_schema_contract_fingerprint( is_array( $definition['output_schema'] ?? null ) ? $definition['output_schema'] : array() ),
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

function maa_assert_package_read_ability_contract( $ability_id, $definition ) {
	$definition = is_array( $definition ) ? $definition : array();
	maa_assert_same( true, $definition['annotations']['readonly'] ?? null, "{$ability_id} is readonly" );
	maa_assert_same( false, $definition['annotations']['destructive'] ?? null, "{$ability_id} is not destructive" );
	maa_assert_same( 'read', $definition['risk_level'] ?? '', "{$ability_id} risk is read" );
	maa_assert_same( false, $definition['requires_approval'] ?? null, "{$ability_id} does not require host approval" );
	maa_assert_same( false, $definition['meta']['magick']['requires_approval'] ?? null, "{$ability_id} Magick metadata does not require approval" );
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
	maa_assert_same( true, $definition['requires_approval'] ?? null, "{$ability_id} exposes requires_approval for governance consumers" );
	maa_assert_same( true, $definition['meta']['magick']['requires_approval'] ?? null, "{$ability_id} Magick metadata requires approval" );
	maa_assert_same( true, $definition['meta']['show_in_rest'] ?? null, "{$ability_id} is shown in REST" );
	maa_assert_same( 'magick-ai-write', $definition['category'] ?? '', "{$ability_id} uses write category" );
	maa_assert_same( 'official', $definition['source'] ?? '', "{$ability_id} is an official migrated write ability" );
	maa_assert_same( true, $definition['project_to_magick_catalog'] ?? null, "{$ability_id} projects into Magick AI catalog" );
	maa_assert_true( is_callable( $definition['execute_callback'] ?? null ), "{$ability_id} execute callback is callable" );
	maa_assert_true( is_array( $definition['input_schema'] ?? null ), "{$ability_id} has an input schema" );
	maa_assert_true( is_array( $definition['output_schema'] ?? null ), "{$ability_id} has an output schema" );
	maa_assert_same( false, $definition['input_schema']['additionalProperties'] ?? null, "{$ability_id} input schema rejects undeclared fields" );
	maa_assert_same( true, $definition['input_schema']['properties']['dry_run']['default'] ?? null, "{$ability_id} dry_run defaults to preview" );
	maa_assert_same( false, $definition['input_schema']['properties']['commit']['default'] ?? null, "{$ability_id} commit defaults to false" );
	maa_assert_same( 190, $definition['input_schema']['properties']['idempotency_key']['maxLength'] ?? null, "{$ability_id} idempotency key is bounded" );
	maa_assert_same( true, $definition['meta']['mcp']['public'] ?? null, "{$ability_id} is MCP-public for governed write server discovery" );
	maa_assert_same( 'magick-ai-write', $definition['meta']['mcp']['server'] ?? '', "{$ability_id} belongs on governed write server" );
}

function maa_assert_package_destructive_ability_contract( $ability_id, $definition ) {
	$definition = is_array( $definition ) ? $definition : array();
	maa_assert_same( false, $definition['annotations']['readonly'] ?? null, "{$ability_id} is not readonly" );
	maa_assert_same( true, $definition['annotations']['destructive'] ?? null, "{$ability_id} is destructive" );
	maa_assert_same( 'destructive', $definition['risk_level'] ?? '', "{$ability_id} risk is destructive" );
	maa_assert_same( true, $definition['requires_confirm'] ?? null, "{$ability_id} requires host approval" );
	maa_assert_same( true, $definition['requires_approval'] ?? null, "{$ability_id} exposes requires_approval for governance consumers" );
	maa_assert_same( true, $definition['meta']['magick']['requires_approval'] ?? null, "{$ability_id} Magick metadata requires approval" );
	maa_assert_same( true, $definition['meta']['show_in_rest'] ?? null, "{$ability_id} is shown in REST" );
	maa_assert_same( 'magick-ai-write', $definition['category'] ?? '', "{$ability_id} keeps legacy write category" );
	maa_assert_same( 'official', $definition['source'] ?? '', "{$ability_id} is an official migrated destructive ability" );
	maa_assert_same( true, $definition['project_to_magick_catalog'] ?? null, "{$ability_id} projects into Magick AI catalog" );
	maa_assert_true( is_callable( $definition['execute_callback'] ?? null ), "{$ability_id} execute callback is callable" );
	maa_assert_true( is_array( $definition['input_schema'] ?? null ), "{$ability_id} has an input schema" );
	maa_assert_true( is_array( $definition['output_schema'] ?? null ), "{$ability_id} has an output schema" );
	maa_assert_same( false, $definition['input_schema']['additionalProperties'] ?? null, "{$ability_id} input schema rejects undeclared fields" );
	maa_assert_same( true, $definition['input_schema']['properties']['dry_run']['default'] ?? null, "{$ability_id} dry_run defaults to preview" );
	maa_assert_same( false, $definition['input_schema']['properties']['commit']['default'] ?? null, "{$ability_id} commit defaults to false" );
	maa_assert_same( 190, $definition['input_schema']['properties']['idempotency_key']['maxLength'] ?? null, "{$ability_id} idempotency key is bounded" );
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
maa_assert_true( ! isset( $readonly['input_schema']['properties']['dry_run'] ), 'readonly input schema does not include write dry_run control' );
maa_assert_true( ! isset( $readonly['input_schema']['properties']['commit'] ), 'readonly input schema does not include write commit control' );
maa_assert_true( ! isset( $readonly['input_schema']['properties']['idempotency_key'] ), 'readonly input schema does not include write idempotency_key control' );
maa_assert_true( ! isset( $readonly['output_schema']['properties']['commit_required'] ), 'readonly output schema does not include write commit_required field' );

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
maa_assert_same( true, $write['requires_approval'], 'write proposal exposes approval requirement alias' );
maa_assert_same( 'magick-ai-abilities-write', $write['category'], 'write proposal default category is write category' );
foreach ( array( 'dry_run', 'commit', 'idempotency_key' ) as $write_control_property ) {
	maa_assert_true(
		isset( $write['input_schema']['properties'][ $write_control_property ] ),
		"write proposal input schema includes {$write_control_property} control"
	);
}
maa_assert_same( true, $write['input_schema']['properties']['dry_run']['default'] ?? null, 'write proposal dry_run defaults to preview' );
maa_assert_same( false, $write['input_schema']['properties']['commit']['default'] ?? null, 'write proposal commit defaults to false' );
maa_assert_same( 190, $write['input_schema']['properties']['idempotency_key']['maxLength'] ?? null, 'write proposal idempotency key is bounded' );
foreach ( array( 'dry_run', 'host_governed', 'commit_required', 'preview' ) as $write_output_property ) {
	maa_assert_true(
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
maa_assert_same( true, $destructive['annotations']['destructive'], 'destructive host ability is destructive' );
maa_assert_same( 'destructive', $destructive['risk_level'], 'destructive host ability risk is destructive' );
maa_assert_same( true, $destructive['requires_confirm'], 'destructive host ability requires confirmation' );
maa_assert_same( true, $destructive['requires_approval'], 'destructive host ability exposes approval requirement alias' );
foreach ( array( 'dry_run', 'commit', 'idempotency_key' ) as $destructive_control_property ) {
	maa_assert_true(
		isset( $destructive['input_schema']['properties'][ $destructive_control_property ] ),
		"destructive input schema includes {$destructive_control_property} control"
	);
}
maa_assert_same( true, $destructive['input_schema']['properties']['dry_run']['default'] ?? null, 'destructive dry_run defaults to preview' );
maa_assert_same( false, $destructive['input_schema']['properties']['commit']['default'] ?? null, 'destructive commit defaults to false' );
maa_assert_same( 190, $destructive['input_schema']['properties']['idempotency_key']['maxLength'] ?? null, 'destructive idempotency key is bounded' );
foreach ( array( 'dry_run', 'host_governed', 'commit_required', 'preview' ) as $destructive_output_property ) {
	maa_assert_true(
		isset( $destructive['output_schema']['properties'][ $destructive_output_property ] ),
		"destructive output schema includes {$destructive_output_property} field"
	);
}

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

add_filter(
	'magick_ai_abilities_enabled_packages',
	static function ( $packages ) {
		$packages['core_write']            = false;
		$packages['core_destructive']      = false;
		$packages['core_comment']          = false;
		$packages['magick_catalog_bridge'] = false;
		$packages['admin_test_page']       = false;
		$packages['read_cache_hooks']      = false;

		return $packages;
	}
);
$plugin = Plugin::instance();
$plugin->boot();
$plugin_abilities = $plugin->abilities()->all();
maa_assert_true( isset( $plugin_abilities['magick-ai/site-info'] ), 'package filter keeps enabled core read package' );
maa_assert_true( ! isset( $plugin_abilities['magick-ai/create-draft'] ), 'package filter disables core write package' );
maa_assert_true( ! isset( $plugin_abilities['magick-ai/delete-post-permanently'] ), 'package filter disables core destructive package' );
maa_assert_true( ! isset( $plugin_abilities['magick-ai/get-comment-queue-health'] ), 'package filter disables core comment package' );
remove_all_filters( 'magick_ai_abilities_enabled_packages' );

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
maa_assert_same( true, $catalog['acme_projected-summary']['show_in_rest'], 'catalog bridge sets top-level show_in_rest for host catalog normalization' );
maa_assert_true( ! isset( $catalog['acme_projected-summary']['open_api_enabled'] ), 'catalog bridge does not own Open API routing policy' );
maa_assert_true( ! isset( $catalog['acme_projected-summary']['skip_catalog_manifest_fallback'] ), 'catalog bridge does not own host manifest fallback policy' );
maa_assert_true( ! isset( $catalog['acme_projected-summary']['backend_priority'] ), 'catalog bridge does not own backend priority policy' );

maa_assert_true(
	$registrar->add_write_host_governed(
		'acme/projected-host-write',
		array(
			'label'                     => 'Projected Host Write',
			'description'               => 'Provider write ability explicitly projected for Magick AI compatibility.',
			'project_to_magick_catalog' => true,
			'input_schema'              => array( 'type' => 'object' ),
			'output_schema'             => array( 'type' => 'object' ),
			'execute_callback'          => static function () {
				return array( 'dry_run' => true );
			},
		)
	),
	'registrar accepts projected host-governed write ability'
);
$catalog = $bridge->filter_catalog( array(), array() );
maa_assert_same( 'wp_ability', $catalog['acme_projected-host-write']['executor_type'] ?? '', 'catalog bridge projects host-governed write as wp_ability' );
maa_assert_same( true, $catalog['acme_projected-host-write']['requires_confirm'] ?? null, 'catalog bridge carries confirmation requirement for projected host-governed write' );
maa_assert_true( ! isset( $catalog['acme_projected-host-write']['tool_policy'] ), 'catalog bridge does not own projected host-governed write tool policy' );
maa_assert_true( ! isset( $catalog['acme_projected-host-write']['skip_catalog_manifest_fallback'] ), 'catalog bridge does not own projected host-governed write fallback policy' );

maa_assert_true(
	$registrar->add_destructive_host_governed(
		'acme/projected-delete-post',
		array(
			'label'                     => 'Projected Delete Post',
			'description'               => 'Provider destructive ability explicitly projected for Magick AI compatibility.',
			'project_to_magick_catalog' => true,
			'input_schema'              => array( 'type' => 'object' ),
			'output_schema'             => array( 'type' => 'object' ),
			'execute_callback'          => static function () {
				return array( 'dry_run' => true );
			},
		)
	),
	'registrar accepts projected destructive host-governed ability'
);
$catalog = $bridge->filter_catalog( array(), array() );
maa_assert_same( 'wp_ability', $catalog['acme_projected-delete-post']['executor_type'] ?? '', 'catalog bridge projects destructive ability as wp_ability' );
maa_assert_same( true, $catalog['acme_projected-delete-post']['requires_confirm'] ?? null, 'catalog bridge carries confirmation requirement for projected destructive ability' );
maa_assert_same( 'destructive', $catalog['acme_projected-delete-post']['risk_level'] ?? '', 'catalog bridge carries projected destructive risk' );
maa_assert_true( ! isset( $catalog['acme_projected-delete-post']['tool_policy'] ), 'catalog bridge does not own projected destructive tool policy' );
maa_assert_true( ! isset( $catalog['acme_projected-delete-post']['skip_catalog_manifest_fallback'] ), 'catalog bridge does not own projected destructive fallback policy' );

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
	$new_read_ability_ids = array(
		'magick-ai-abilities/wp-ops-diagnostics-detail',
		'magick-ai-abilities/list-workflow-recipes',
		'magick-ai-abilities/get-workflow-recipe',
		'magick-ai/get-post-context',
	'magick-ai/get-content-publishing-checklist',
	'magick-ai/get-content-inventory-health',
	'magick-ai/get-test-content-inventory',
	'magick-ai/build-test-content-cleanup-plan',
	'magick-ai/build-content-inventory-fix-plan',
	'magick-ai/get-bulk-publishing-checklist',
	'magick-ai/get-internal-link-opportunity-report',
	'magick-ai/get-site-operations-dashboard',
	'magick-ai/get-post-publish-risk-report',
	'magick-ai/get-article-publish-preflight-context',
	'magick-ai/get-content-refresh-opportunities',
	'magick-ai/get-old-article-refresh-context',
	'magick-ai/get-internal-link-graph-health',
	'magick-ai/get-media-cleanup-opportunities',
	'magick-ai/build-media-inventory-fix-plan',
	'magick-ai/get-taxonomy-consolidation-suggestions',
	'magick-ai/propose-post-taxonomy-terms',
	'magick-ai/get-page-structure-health',
	'magick-ai/get-seo-geo-gap-report',
	'magick-ai/get-site-style-baseline',
	'magick-ai/build-article-workflow-context',
	'magick-ai/get-publishing-calendar-context',
	'magick-ai/get-media-inventory-health',
	'magick-ai/get-post-seo-geo-readiness',
	'magick-ai/get-site-topic-coverage-report',
	'magick-ai/get-taxonomy-inventory-health',
	'magick-ai/get-revision-change-risk-report',
);
$new_comment_ability_ids = array(
	'magick-ai/get-comment-queue-health',
	'magick-ai/get-comment-action-priority-queue',
	'magick-ai/get-comment-compliance-handoff',
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
$core_governance_snapshot_path = __DIR__ . '/fixtures/core-governance-catalog-snapshot.json';
$core_governance_snapshot_json = file_get_contents( $core_governance_snapshot_path );
maa_assert_true( false !== $core_governance_snapshot_json, 'core governance catalog snapshot fixture is readable' );
$core_governance_expected_snapshot = json_decode( (string) $core_governance_snapshot_json, true );
maa_assert_true( is_array( $core_governance_expected_snapshot ), 'core governance catalog snapshot fixture decodes as an object' );
maa_assert_same(
	$core_governance_expected_snapshot,
	maa_core_governance_catalog_snapshot(
		$package_abilities,
		array_keys( (array) ( $core_governance_expected_snapshot['abilities'] ?? array() ) )
	),
	'core governance catalog snapshot matches normalized package definitions'
);
$core_snapshot_doc = file_get_contents( __DIR__ . '/../docs/core-governance-catalog-snapshot.md' );
maa_assert_true( is_string( $core_snapshot_doc ) && false !== strpos( $core_snapshot_doc, 'tests/fixtures/core-governance-catalog-snapshot.json' ), 'core governance catalog snapshot doc points to fixture' );
$permission_matrix_doc = file_get_contents( __DIR__ . '/../docs/permission-matrix.md' );
maa_assert_true( is_string( $permission_matrix_doc ) && false !== strpos( $permission_matrix_doc, 'Dry-run previews must still pass the same WordPress permission checks' ), 'permission matrix documents dry-run permission boundary' );
$schema_audit_doc = file_get_contents( __DIR__ . '/../docs/schema-boundary-audit.md' );
maa_assert_true( is_string( $schema_audit_doc ) && false !== strpos( $schema_audit_doc, 'REST ability details expose' ), 'schema boundary audit documents REST exposure verification' );
$core_consumer_example = file_get_contents( __DIR__ . '/../examples/core-governance-consumer.php' );
maa_assert_true( is_string( $core_consumer_example ) && false !== strpos( $core_consumer_example, 'magick_ai_abilities_get_registered' ), 'core governance consumer example uses ability discovery' );
maa_assert_true( is_string( $core_consumer_example ) && false !== strpos( $core_consumer_example, "'ability_id' => \$ability_id" ), 'core governance consumer example prepares a real ability proposal payload' );
maa_assert_true( isset( $package_categories->all()['magick-ai-data'] ), 'core read package registers the legacy magick-ai-data category for compatibility' );
maa_assert_true( isset( $package_categories->all()['magick-ai-pages'] ), 'core read package registers the legacy magick-ai-pages category for compatibility' );
maa_assert_true( isset( $package_categories->all()['magick-ai-comments'] ), 'core comment package registers the standalone comments category' );
maa_assert_true( isset( $package_categories->all()['magick-ai-write'] ), 'core write package registers the legacy magick-ai-write category for compatibility' );
maa_assert_true( isset( $package_categories->all()['magick-ai-abilities-diagnostics'] ), 'core read package registers the standalone diagnostics category' );
maa_assert_true( isset( $package_abilities['magick-ai-abilities/wp-diagnostics-summary'] ), 'core read package owns standalone wp-diagnostics-summary ability' );
maa_assert_same( 'magick-ai-abilities-diagnostics', $package_abilities['magick-ai-abilities/wp-diagnostics-summary']['category'], 'wp-diagnostics-summary uses standalone diagnostics category' );
maa_assert_true( isset( $package_abilities['magick-ai-abilities/wp-ops-diagnostics-detail'] ), 'core read package owns standalone wp-ops-diagnostics-detail ability' );
maa_assert_same( 'magick-ai-abilities-diagnostics', $package_abilities['magick-ai-abilities/wp-ops-diagnostics-detail']['category'], 'wp-ops-diagnostics-detail uses standalone diagnostics category' );
maa_assert_true( false !== strpos( $package_abilities['magick-ai-abilities/wp-ops-diagnostics-detail']['description'] ?? '', 'plugin' ), 'ops diagnostics description mentions plugin details' );
maa_assert_same( 50, $package_abilities['magick-ai-abilities/wp-ops-diagnostics-detail']['input_schema']['properties']['max_cron_events']['maximum'] ?? null, 'ops diagnostics bounds returned cron events' );
maa_assert_same( false, $package_abilities['magick-ai-abilities/wp-ops-diagnostics-detail']['input_schema']['properties']['include_log_contents']['default'] ?? null, 'ops diagnostics does not include log contents by default' );
maa_assert_true( ! isset( $package_abilities['magick-ai-abilities/wp-ops-diagnostics-detail']['input_schema']['properties']['include_log_tail'] ), 'ops diagnostics uses one log contents control' );
maa_assert_same( false, $package_abilities['magick-ai-abilities/wp-ops-diagnostics-detail']['input_schema']['properties']['include_inactive_plugins']['default'] ?? null, 'ops diagnostics omits inactive plugin rows by default' );
maa_assert_same( true, $package_abilities['magick-ai-abilities/wp-ops-diagnostics-detail']['input_schema']['properties']['include_plugin_updates']['default'] ?? null, 'ops diagnostics includes plugin update rows by default' );
maa_assert_same( 500, $package_abilities['magick-ai-abilities/wp-ops-diagnostics-detail']['input_schema']['properties']['max_plugins_per_group']['maximum'] ?? null, 'ops diagnostics bounds plugin rows per group' );
maa_assert_same( 200, $package_abilities['magick-ai-abilities/wp-ops-diagnostics-detail']['input_schema']['properties']['tail_lines']['maximum'] ?? null, 'ops diagnostics bounds returned log tail lines' );
maa_assert_same( 10080, $package_abilities['magick-ai-abilities/wp-ops-diagnostics-detail']['input_schema']['properties']['since_minutes']['maximum'] ?? null, 'ops diagnostics bounds log since window' );
maa_assert_true( in_array( 'warning', $package_abilities['magick-ai-abilities/wp-ops-diagnostics-detail']['input_schema']['properties']['severity']['items']['enum'] ?? array(), true ), 'ops diagnostics supports log severity filtering' );
maa_assert_same( true, $package_abilities['magick-ai-abilities/wp-ops-diagnostics-detail']['input_schema']['properties']['include_integrations']['default'] ?? null, 'ops diagnostics includes integration diagnostics by default' );
maa_assert_true( in_array( 'plugins', $package_abilities['magick-ai-abilities/wp-ops-diagnostics-detail']['output_schema']['required'] ?? array(), true ), 'ops diagnostics output requires plugins section' );
maa_assert_true( in_array( 'current_user', $package_abilities['magick-ai-abilities/wp-ops-diagnostics-detail']['output_schema']['required'] ?? array(), true ), 'ops diagnostics output requires current user section' );
maa_assert_true( in_array( 'integrations', $package_abilities['magick-ai-abilities/wp-ops-diagnostics-detail']['output_schema']['required'] ?? array(), true ), 'ops diagnostics output requires integrations section' );
maa_assert_true( in_array( 'seo_summary', $package_abilities['magick-ai-abilities/wp-ops-diagnostics-detail']['output_schema']['required'] ?? array(), true ), 'ops diagnostics output requires SEO summary section' );
$parse_log_entry = new ReflectionMethod( $core_read_package, 'parse_diagnostics_log_entry' );
$parse_log_entry->setAccessible( true );
$summarize_log_sources = new ReflectionMethod( $core_read_package, 'summarize_diagnostics_log_sources' );
$summarize_log_sources->setAccessible( true );
$summarize_top_messages = new ReflectionMethod( $core_read_package, 'summarize_diagnostics_top_messages' );
$summarize_top_messages->setAccessible( true );
$plugin_log_entry = $parse_log_entry->invoke( $core_read_package, '[30-May-2026 10:39:34 UTC] PHP Deprecated: Test in /srv/app/public/wp-content/plugins/plugin-check/check.php on line 10' );
$theme_log_entry = $parse_log_entry->invoke( $core_read_package, '[30-May-2026 10:40:34 UTC] PHP Warning: Test in /srv/app/public/wp-content/themes/twentytwentyfour/functions.php on line 20' );
$phar_log_entry = $parse_log_entry->invoke( $core_read_package, '[30-May-2026 10:41:34 UTC] PHP Deprecated: Using null as an array offset is deprecated in phar:///tmp/wp-cli.phar/vendor/file.php on line 30' );
$home_path_log_entry = $parse_log_entry->invoke( $core_read_package, '[30-May-2026 10:42:34 UTC] PHP Warning: mysqli_real_connect(): (HY000/2002): No such file or directory in /Users/muze/Local Sites/magick-ai/app/public/wp-includes/class-wpdb.php on line 1990' );
maa_assert_same( 'plugin', $plugin_log_entry['source_type'] ?? '', 'ops diagnostics detects plugin log source type' );
maa_assert_same( 'plugin-check', $plugin_log_entry['source_hint'] ?? '', 'ops diagnostics detects plugin log source hint' );
maa_assert_same( 'Test', $plugin_log_entry['message_fingerprint'] ?? '', 'ops diagnostics fingerprints plugin log messages without path noise' );
maa_assert_same( 'theme', $theme_log_entry['source_type'] ?? '', 'ops diagnostics detects theme log source type' );
maa_assert_same( 'twentytwentyfour', $theme_log_entry['source_hint'] ?? '', 'ops diagnostics detects theme log source hint' );
maa_assert_same( 'phar', $phar_log_entry['source_type'] ?? '', 'ops diagnostics detects phar log source type' );
maa_assert_same( 'wp-cli', $phar_log_entry['source_hint'] ?? '', 'ops diagnostics detects wp-cli log source hint' );
maa_assert_same( 'wp-cli.phar', $phar_log_entry['source_basename'] ?? '', 'ops diagnostics exposes safe phar basename hint' );
maa_assert_same( 'wp-cli.phar', $phar_log_entry['phar_hint'] ?? '', 'ops diagnostics exposes safe phar hint' );
maa_assert_same( 'Using null as an array offset is deprecated', $phar_log_entry['message_fingerprint'] ?? '', 'ops diagnostics fingerprints phar messages without path noise' );
maa_assert_same( 'mysqli_real_connect(): (HY000/N): No such file or directory', $home_path_log_entry['message_fingerprint'] ?? '', 'ops diagnostics fingerprints home path messages without path noise' );
$log_source_summary = $summarize_log_sources->invoke( $core_read_package, array( $plugin_log_entry, $plugin_log_entry, $theme_log_entry, $phar_log_entry ) );
maa_assert_same( 'plugin', $log_source_summary[0]['source_type'] ?? '', 'ops diagnostics source summary sorts most frequent source first' );
maa_assert_same( 'plugin-check', $log_source_summary[0]['source_hint'] ?? '', 'ops diagnostics source summary groups by source hint' );
maa_assert_same( 'deprecated', $log_source_summary[0]['severity'] ?? '', 'ops diagnostics source summary groups by severity' );
maa_assert_same( 'Test', $log_source_summary[0]['message_fingerprint'] ?? '', 'ops diagnostics source summary includes message fingerprints' );
maa_assert_same( 2, $log_source_summary[0]['count'] ?? 0, 'ops diagnostics source summary counts repeated source entries' );
$log_top_messages = $summarize_top_messages->invoke( $core_read_package, array( $phar_log_entry, $phar_log_entry, $plugin_log_entry ) );
maa_assert_same( 'Using null as an array offset is deprecated', $log_top_messages[0]['fingerprint'] ?? '', 'ops diagnostics top messages sort repeated fingerprints first' );
maa_assert_same( 'wp-cli.phar', $log_top_messages[0]['phar_hint'] ?? '', 'ops diagnostics top messages include safe phar hint' );
maa_assert_same( 2, $log_top_messages[0]['count'] ?? 0, 'ops diagnostics top messages count repeated fingerprints' );
maa_assert_true( isset( $package_abilities['magick-ai/list-posts']['input_schema']['properties']['modified_after'] ), 'list-posts supports modified date filtering' );
maa_assert_true( isset( $package_abilities['magick-ai/list-posts']['input_schema']['properties']['taxonomy'] ), 'list-posts supports taxonomy filtering' );
maa_assert_true( in_array( 'tree', $package_abilities['magick-ai/get-menu']['output_schema']['required'] ?? array(), true ), 'get-menu returns a menu tree' );
foreach ( $migrated_read_ability_ids as $migrated_ability_id ) {
	maa_assert_true( isset( $package_abilities[ $migrated_ability_id ] ), "core read package owns migrated {$migrated_ability_id} ability" );
	maa_assert_package_read_ability_contract( $migrated_ability_id, $package_abilities[ $migrated_ability_id ] );
}
foreach ( $new_read_ability_ids as $new_read_ability_id ) {
	maa_assert_true( isset( $package_abilities[ $new_read_ability_id ] ), "core read package owns new {$new_read_ability_id} ability" );
	maa_assert_package_read_ability_contract( $new_read_ability_id, $package_abilities[ $new_read_ability_id ] );
}
foreach ( $new_comment_ability_ids as $new_comment_ability_id ) {
	maa_assert_true( isset( $package_abilities[ $new_comment_ability_id ] ), "core comment package owns new {$new_comment_ability_id} ability" );
	maa_assert_package_read_ability_contract( $new_comment_ability_id, $package_abilities[ $new_comment_ability_id ] );
}
maa_assert_same( true, $package_abilities['magick-ai/site-info']['project_to_magick_catalog'], 'migrated core read abilities project into Magick AI catalog' );
maa_assert_same( true, $package_abilities['magick-ai/get-post-context']['project_to_magick_catalog'], 'new official post context ability projects into Magick AI catalog' );
maa_assert_same( true, $package_abilities['magick-ai/get-post-context']['input_schema']['properties']['include_blocks']['default'] ?? null, 'get-post-context includes blocks by default' );
maa_assert_same( false, $package_abilities['magick-ai/get-content-publishing-checklist']['requires_confirm'], 'publishing checklist remains readonly' );
maa_assert_same( 100, $package_abilities['magick-ai/get-content-inventory-health']['input_schema']['properties']['per_page']['maximum'] ?? null, 'inventory health scan is bounded to 100 posts per page' );
maa_assert_same( 100, $package_abilities['magick-ai/get-test-content-inventory']['input_schema']['properties']['per_page']['maximum'] ?? null, 'test content inventory scan is bounded to 100 items per section' );
maa_assert_same( 100, $package_abilities['magick-ai/build-test-content-cleanup-plan']['input_schema']['properties']['max_actions']['maximum'] ?? null, 'test content cleanup plan bounds planned actions' );
maa_assert_true( ! isset( $package_abilities['magick-ai/build-test-content-cleanup-plan']['input_schema']['properties']['mode'] ), 'test content cleanup plan does not expose unused mode input' );
maa_assert_same( 100, $package_abilities['magick-ai/build-content-inventory-fix-plan']['input_schema']['properties']['max_actions']['maximum'] ?? null, 'content inventory fix plan bounds planned actions' );
maa_assert_same( array( 'post.read' ), $package_abilities['magick-ai/build-content-inventory-fix-plan']['required_scopes'] ?? array(), 'content inventory fix plan remains a read-scope planning ability' );
foreach ( array( 'magick-ai/get-test-content-inventory', 'magick-ai/build-test-content-cleanup-plan', 'magick-ai/build-content-inventory-fix-plan' ) as $planning_agent_usage_id ) {
	maa_assert_true( ! empty( $package_abilities[ $planning_agent_usage_id ]['agent_usage']['when_to_use'] ), "{$planning_agent_usage_id} exposes agent usage guidance" );
	maa_assert_true( ! empty( $package_abilities[ $planning_agent_usage_id ]['agent_usage']['stopping_points'] ), "{$planning_agent_usage_id} exposes agent stopping points" );
}
maa_assert_same( 50, $package_abilities['magick-ai/get-bulk-publishing-checklist']['input_schema']['properties']['post_ids']['maxItems'] ?? null, 'bulk publishing checklist is bounded to 50 posts' );
maa_assert_same( 10, $package_abilities['magick-ai/get-internal-link-opportunity-report']['input_schema']['properties']['max_targets']['maximum'] ?? null, 'internal link opportunity report bounds target count' );
maa_assert_same( 100, $package_abilities['magick-ai/get-site-operations-dashboard']['input_schema']['properties']['per_page']['maximum'] ?? null, 'site operations dashboard is bounded to 100 posts per page' );
maa_assert_same( array( 'post_id' ), $package_abilities['magick-ai/get-post-publish-risk-report']['input_schema']['required'] ?? array(), 'post publish risk report requires post_id' );
maa_assert_same( array( 'post_id' ), $package_abilities['magick-ai/get-article-publish-preflight-context']['input_schema']['required'] ?? array(), 'article publish preflight context requires post_id' );
maa_assert_same( 100, $package_abilities['magick-ai/get-content-refresh-opportunities']['input_schema']['properties']['per_page']['maximum'] ?? null, 'content refresh opportunities scan is bounded to 100 posts per page' );
maa_assert_same( 100, $package_abilities['magick-ai/get-old-article-refresh-context']['input_schema']['properties']['per_page']['maximum'] ?? null, 'old article refresh context scan is bounded to 100 posts per page' );
maa_assert_same( 100, $package_abilities['magick-ai/get-internal-link-graph-health']['input_schema']['properties']['per_page']['maximum'] ?? null, 'internal link graph health scan is bounded to 100 posts per page' );
maa_assert_same( 100, $package_abilities['magick-ai/get-media-cleanup-opportunities']['input_schema']['properties']['per_page']['maximum'] ?? null, 'media cleanup opportunities scan is bounded to 100 assets per page' );
maa_assert_same( 100, $package_abilities['magick-ai/build-media-inventory-fix-plan']['input_schema']['properties']['max_actions']['maximum'] ?? null, 'media inventory fix plan bounds planned actions' );
maa_assert_same( array( 'media.read' ), $package_abilities['magick-ai/build-media-inventory-fix-plan']['required_scopes'] ?? array(), 'media inventory fix plan remains a read-scope planning ability' );
maa_assert_true( ! empty( $package_abilities['magick-ai/build-media-inventory-fix-plan']['agent_usage']['when_to_use'] ), 'media inventory fix plan exposes agent usage guidance' );
maa_assert_true( ! empty( $package_abilities['magick-ai/build-media-inventory-fix-plan']['agent_usage']['stopping_points'] ), 'media inventory fix plan exposes agent stopping points' );
maa_assert_same( 100, $package_abilities['magick-ai/get-taxonomy-consolidation-suggestions']['input_schema']['properties']['per_page']['maximum'] ?? null, 'taxonomy consolidation suggestions scan is bounded to 100 terms per page' );
maa_assert_same( array( 'post_id' ), $package_abilities['magick-ai/propose-post-taxonomy-terms']['input_schema']['required'] ?? array(), 'post taxonomy proposal requires post_id' );
maa_assert_same( 20, $package_abilities['magick-ai/propose-post-taxonomy-terms']['input_schema']['properties']['candidate_terms']['maxItems'] ?? null, 'post taxonomy proposal bounds candidate term names' );
maa_assert_same( 100, $package_abilities['magick-ai/get-page-structure-health']['input_schema']['properties']['max_pages']['maximum'] ?? null, 'page structure health scan is bounded to 100 pages' );
maa_assert_same( 100, $package_abilities['magick-ai/get-seo-geo-gap-report']['input_schema']['properties']['per_page']['maximum'] ?? null, 'SEO/GEO gap report scan is bounded to 100 posts per page' );
maa_assert_same( 5, $package_abilities['magick-ai/get-site-style-baseline']['input_schema']['properties']['limit']['maximum'] ?? null, 'site style baseline is bounded to 5 samples' );
maa_assert_same( array( 'new_article', 'refresh', 'publish' ), $package_abilities['magick-ai/build-article-workflow-context']['input_schema']['properties']['workflow']['enum'] ?? array(), 'article workflow context supports known workflow modes' );
maa_assert_same( 365, $package_abilities['magick-ai/get-publishing-calendar-context']['input_schema']['properties']['window_days']['maximum'] ?? null, 'publishing calendar window is bounded to 365 days' );
maa_assert_same( 100, $package_abilities['magick-ai/get-media-inventory-health']['input_schema']['properties']['per_page']['maximum'] ?? null, 'media inventory health scan is bounded to 100 assets per page' );
maa_assert_same( array( 'post_id' ), $package_abilities['magick-ai/get-post-seo-geo-readiness']['input_schema']['required'] ?? array(), 'post SEO/GEO readiness requires post_id' );
maa_assert_same( 100, $package_abilities['magick-ai/get-site-topic-coverage-report']['input_schema']['properties']['per_page']['maximum'] ?? null, 'site topic coverage scan is bounded to 100 posts per page' );
maa_assert_same( 100, $package_abilities['magick-ai/get-taxonomy-inventory-health']['input_schema']['properties']['per_page']['maximum'] ?? null, 'taxonomy inventory health scan is bounded to 100 terms per page' );
maa_assert_same( array( 'post_id' ), $package_abilities['magick-ai/get-revision-change-risk-report']['input_schema']['required'] ?? array(), 'revision change risk report requires post_id' );
maa_assert_same( 100, $package_abilities['magick-ai/get-comment-queue-health']['input_schema']['properties']['per_page']['maximum'] ?? null, 'comment queue health scan is bounded to 100 comments per page' );
maa_assert_same( 100, $package_abilities['magick-ai/get-comment-action-priority-queue']['input_schema']['properties']['per_page']['maximum'] ?? null, 'comment action priority queue scan is bounded to 100 comments per page' );
maa_assert_same( 100, $package_abilities['magick-ai/get-comment-compliance-handoff']['input_schema']['properties']['per_page']['maximum'] ?? null, 'comment compliance handoff scan is bounded to 100 comments per page' );
	maa_assert_same( 'magick-ai-comments', $package_abilities['magick-ai/build-comment-moderation-suggest']['category'], 'comment helper abilities use the standalone comments category' );
	maa_assert_same( 'magick-ai-comments', $package_abilities['magick-ai/get-comment-queue-health']['category'], 'comment queue health uses the standalone comments category' );
	maa_assert_same( false, $package_abilities['magick-ai-abilities/wp-diagnostics-summary']['project_to_magick_catalog'], 'standalone diagnostics ability does not project into Magick AI by default' );
	maa_assert_same( false, $package_abilities['magick-ai-abilities/wp-ops-diagnostics-detail']['project_to_magick_catalog'], 'standalone ops diagnostics ability does not project into Magick AI by default' );
	maa_assert_same( false, $package_abilities['magick-ai-abilities/list-workflow-recipes']['project_to_magick_catalog'], 'workflow recipe discovery ability does not project into Magick AI by default' );
	maa_assert_same( 'wordpress_diagnostics', $package_abilities['magick-ai-abilities/wp-ops-diagnostics-detail']['meta']['magick_ai_abilities']['pack'] ?? '', 'ops diagnostics detail is classified as WordPress diagnostics' );
	maa_assert_same( 'magick-ai-abilities-workflows', $package_abilities['magick-ai-abilities/list-workflow-recipes']['category'], 'workflow recipe discovery uses standalone workflow category' );
	maa_assert_same( 'workflow_definitions', $package_abilities['magick-ai-abilities/list-workflow-recipes']['meta']['magick_ai_abilities']['pack'] ?? '', 'workflow recipe discovery is classified as workflow definitions' );
maa_assert_same( 'core_wordpress_read', $package_abilities['magick-ai/site-info']['meta']['magick_ai_abilities']['pack'] ?? '', 'site-info is classified as a core WordPress read ability' );
maa_assert_same( 'content_operations', $package_abilities['magick-ai/get-site-operations-dashboard']['meta']['magick_ai_abilities']['pack'] ?? '', 'site operations dashboard is classified outside core WordPress reads' );
maa_assert_same( 'content_operations', $package_abilities['magick-ai/build-content-inventory-fix-plan']['meta']['magick_ai_abilities']['pack'] ?? '', 'content inventory fix plan is classified as content operations' );
maa_assert_same( 'media_governance', $package_abilities['magick-ai/build-media-inventory-fix-plan']['meta']['magick_ai_abilities']['pack'] ?? '', 'media inventory fix plan is classified as media governance' );
maa_assert_same( 'taxonomy_governance', $package_abilities['magick-ai/propose-post-taxonomy-terms']['meta']['magick_ai_abilities']['pack'] ?? '', 'post taxonomy proposal is classified as taxonomy governance' );
maa_assert_same( 'comment_queue_context', $package_abilities['magick-ai/get-comment-queue-health']['meta']['magick_ai_abilities']['pack'] ?? '', 'comment queue health is classified as a comment queue helper' );
	$core_read_definition_ids = array_keys( $core_read_package->definitions() );
	maa_assert_same( 'magick-ai/site-info', $core_read_definition_ids[0] ?? '', 'core read definitions keep site-info first after provider split' );
	maa_assert_same( 'magick-ai-abilities/wp-diagnostics-summary', $core_read_definition_ids[1] ?? '', 'core read definitions keep diagnostics second after provider split' );
	maa_assert_same( 'magick-ai-abilities/wp-ops-diagnostics-detail', $core_read_definition_ids[2] ?? '', 'core read definitions keep ops diagnostics after diagnostics summary' );
	maa_assert_same( 'magick-ai-abilities/list-workflow-recipes', $core_read_definition_ids[3] ?? '', 'core read definitions keep workflow list after diagnostics' );
	maa_assert_same( 'magick-ai-abilities/get-workflow-recipe', $core_read_definition_ids[4] ?? '', 'core read definitions keep workflow get after workflow list' );
	maa_assert_same( 'magick-ai/list-post-types', $core_read_definition_ids[5] ?? '', 'core read definitions keep post types after workflow definitions' );
	maa_assert_same( 'magick-ai/list-media', $core_read_definition_ids[7] ?? '', 'core read definitions keep media governance order after provider split' );
	maa_assert_same( 'magick-ai/resolve-url-to-post', $core_read_definition_ids[79] ?? '', 'core read definitions keep URL resolver order after provider split' );
	maa_assert_same( 'magick-ai/list-post-revisions', $core_read_definition_ids[81] ?? '', 'core read definitions keep revision list last after provider split' );
$core_comment_definition_ids = array_keys( $core_comment_package->definitions() );
maa_assert_same( 'magick-ai/build-comment-moderation-suggest', $core_comment_definition_ids[0] ?? '', 'core comment definitions keep moderation suggestion first after provider split' );
maa_assert_same( 'magick-ai/get-comment-compliance-handoff', $core_comment_definition_ids[6] ?? '', 'core comment definitions keep compliance handoff order after provider split' );
foreach ( array_keys( $core_read_package->definitions() ) as $known_read_ability_id ) {
	maa_assert_true(
		isset( Core_Read_Pack_Classifier::known_pack_map()[ $known_read_ability_id ] ),
		"core read ability {$known_read_ability_id} has an explicit sub-pack map entry"
	);
}
foreach ( array_keys( $core_comment_package->definitions() ) as $known_comment_ability_id ) {
	maa_assert_true(
		isset( Core_Comment_Pack_Classifier::known_pack_map()[ $known_comment_ability_id ] ),
		"core comment ability {$known_comment_ability_id} has an explicit sub-pack map entry"
	);
}
maa_assert_true( ! isset( $package_abilities['magick-ai/create-page'] ), 'create-page is not migrated as a readonly ability' );
maa_assert_true( ! isset( $package_abilities['magick-ai/update-page'] ), 'update-page is not migrated as a readonly ability' );

add_filter(
	'magick_ai_abilities_enabled_read_packs',
	static function () {
		return array( 'core_wordpress_read' );
	}
);
$filtered_read_categories = new Category_Registrar();
$filtered_read_registrar = new Ability_Registrar( $filtered_read_categories, $contract_normalizer );
$filtered_read_package = new Core_Read_Package( $filtered_read_categories, $filtered_read_registrar );
$filtered_read_package->boot();
$filtered_read_abilities = $filtered_read_registrar->all();
	maa_assert_true( isset( $filtered_read_abilities['magick-ai/site-info'] ), 'core read pack filter keeps generic site-info ability' );
	maa_assert_true( ! isset( $filtered_read_abilities['magick-ai/get-site-operations-dashboard'] ), 'core read pack filter removes operations helper ability' );
	maa_assert_true( ! isset( $filtered_read_abilities['magick-ai-abilities/wp-diagnostics-summary'] ), 'core read pack filter removes diagnostics helper ability' );
	maa_assert_true( ! isset( $filtered_read_abilities['magick-ai-abilities/wp-ops-diagnostics-detail'] ), 'core read pack filter removes ops diagnostics helper ability' );
	maa_assert_true( ! isset( $filtered_read_abilities['magick-ai-abilities/list-workflow-recipes'] ), 'core read pack filter removes workflow definition discovery ability' );
remove_all_filters( 'magick_ai_abilities_enabled_read_packs' );

add_filter(
	'magick_ai_abilities_enabled_comment_packs',
	static function () {
		return array( 'comment_queue_context' );
	}
);
$filtered_comment_categories = new Category_Registrar();
$filtered_comment_registrar = new Ability_Registrar( $filtered_comment_categories, $contract_normalizer );
$filtered_comment_package = new Core_Comment_Package( $filtered_comment_categories, $filtered_comment_registrar );
$filtered_comment_package->boot();
$filtered_comment_abilities = $filtered_comment_registrar->all();
maa_assert_true( isset( $filtered_comment_abilities['magick-ai/get-comment-queue-health'] ), 'comment pack filter keeps queue helper ability' );
maa_assert_true( ! isset( $filtered_comment_abilities['magick-ai/get-comment-compliance-handoff'] ), 'comment pack filter removes handoff helper ability' );
remove_all_filters( 'magick_ai_abilities_enabled_comment_packs' );
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
$seo_missing_fields = $core_write_package->set_post_seo_meta(
	array(
		'post_id' => 501,
		'dry_run' => true,
	)
);
maa_assert_true( is_wp_error( $seo_missing_fields ), 'set-post-seo-meta rejects requests without explicit metadata fields' );
maa_assert_same( 'magick_ai_abilities_no_changes', $seo_missing_fields->code ?? '', 'set-post-seo-meta no-change request fails with a stable code' );
$seo_title_only_preview = $core_write_package->set_post_seo_meta(
	array(
		'post_id'   => 501,
		'seo_title' => 'Title-only preview',
		'dry_run'   => true,
	)
);
maa_assert_same( array( 'seo_title' ), $seo_title_only_preview['preview']['changed_fields'] ?? array(), 'set-post-seo-meta preview reports only explicit changed fields' );
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
$GLOBALS['magick_ai_runtime_wp_ability_context'] = array( 'context' => array( 'approval_commit_authorized' => true ) );
$seo_title_only_written = $core_write_package->set_post_seo_meta(
	array(
		'post_id'   => 501,
		'seo_title' => 'Only title changed',
		'commit'    => true,
	)
);
unset( $GLOBALS['magick_ai_runtime_wp_ability_context'] );
maa_assert_same( false, $seo_title_only_written['dry_run'] ?? null, 'set-post-seo-meta title-only commit returns committed payload' );
maa_assert_same( 'Only title changed', $GLOBALS['maa_unit_post_meta'][501]['_yoast_wpseo_title'] ?? '', 'set-post-seo-meta title-only commit writes title' );
maa_assert_same( 'Committed SEO description', $GLOBALS['maa_unit_post_meta'][501]['_yoast_wpseo_metadesc'] ?? '', 'set-post-seo-meta title-only commit preserves description' );
$GLOBALS['maa_unit_comments'][11] = (object) array(
	'comment_ID'       => 11,
	'comment_post_ID'  => 77,
	'comment_author'   => 'Permission Fixture',
	'comment_approved' => 'hold',
	'comment_content'  => 'Pending moderation.',
);
$GLOBALS['maa_unit_current_user_caps'] = array( 'moderate_comments' => false );
$comment_permission_denied = $core_write_package->approve_comment(
	array(
		'comment_id' => 11,
		'dry_run'    => true,
	)
);
unset( $GLOBALS['maa_unit_current_user_caps'] );
maa_assert_true( is_wp_error( $comment_permission_denied ), 'approve-comment enforces moderate_comments before dry-run preview' );
maa_assert_same( 'magick_ai_abilities_permission_denied', $comment_permission_denied->code ?? '', 'approve-comment permission denial has stable error code' );

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
$comment_queue_health = $core_comment_package->get_comment_queue_health(
	array(
		'post_id'  => 77,
		'status'   => 'hold',
		'per_page' => 10,
	)
);
maa_assert_same( true, $comment_queue_health['success'] ?? null, 'get-comment-queue-health returns a success envelope' );
maa_assert_same( 3, $comment_queue_health['data']['summary']['counts']['total'] ?? null, 'get-comment-queue-health counts queued comments' );
maa_assert_true( (int) ( $comment_queue_health['data']['summary']['counts']['spam_risk'] ?? 0 ) >= 1, 'get-comment-queue-health counts spam-risk comments' );
maa_assert_true( (int) ( $comment_queue_health['data']['summary']['counts']['reply_needed'] ?? 0 ) >= 1, 'get-comment-queue-health counts reply-needed comments' );
$comment_action_queue = $core_comment_package->get_comment_action_priority_queue(
	array(
		'post_id'  => 77,
		'status'   => 'hold',
		'per_page' => 10,
	)
);
maa_assert_same( true, $comment_action_queue['success'] ?? null, 'get-comment-action-priority-queue returns a success envelope' );
maa_assert_same( 3, $comment_action_queue['data']['summary']['counts']['total'] ?? null, 'get-comment-action-priority-queue counts queued comments' );
maa_assert_true( (int) ( $comment_action_queue['data']['items'][0]['priority_score'] ?? 0 ) >= (int) ( $comment_action_queue['data']['items'][1]['priority_score'] ?? 0 ), 'get-comment-action-priority-queue sorts high-priority items first' );
$comment_handoff = $core_comment_package->get_comment_compliance_handoff(
	array(
		'post_id'             => 77,
		'status'              => 'hold',
		'per_page'            => 10,
		'selected_comment_id' => 12,
	)
);
maa_assert_same( true, $comment_handoff['success'] ?? null, 'get-comment-compliance-handoff returns a success envelope' );
maa_assert_same( 'workflow/wordpress_comment_compliance_handoff', $comment_handoff['data']['recipe'] ?? '', 'get-comment-compliance-handoff declares its recipe id' );
maa_assert_true( in_array( 'selected_moderation_suggestion', $comment_handoff['data']['sections'] ?? array(), true ), 'get-comment-compliance-handoff includes selected moderation suggestion' );
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
$GLOBALS['maa_unit_post_meta'][77]['_yoast_wpseo_title'] = 'Optimization SEO Title';
$GLOBALS['maa_unit_post_meta'][77]['_yoast_wpseo_metadesc'] = 'Optimization SEO Description';
$agent_post_context = $core_read_package->get_post_context(
	array(
		'post_id'      => 77,
		'include_meta' => true,
		'meta_keys'    => array( '_yoast_wpseo_title' ),
	)
);
maa_assert_same( true, $agent_post_context['success'] ?? null, 'get-post-context returns a success envelope' );
maa_assert_same( 77, $agent_post_context['data']['post']['id'] ?? null, 'get-post-context reads the requested post id' );
maa_assert_same( 'Optimization context content.', $agent_post_context['data']['post']['content'] ?? '', 'get-post-context includes post content by default' );
maa_assert_same( 1, $agent_post_context['data']['stats']['block_count'] ?? null, 'get-post-context falls back to a freeform block for plain content' );
maa_assert_same( 'Optimization SEO Title', $agent_post_context['data']['meta']['_yoast_wpseo_title'] ?? '', 'get-post-context supports scoped metadata reads' );
$publishing_checklist = $core_read_package->get_content_publishing_checklist(
	array(
		'post_id' => 77,
	)
);
maa_assert_same( true, $publishing_checklist['success'] ?? null, 'get-content-publishing-checklist returns a success envelope' );
maa_assert_same( false, $publishing_checklist['data']['ready'] ?? null, 'get-content-publishing-checklist blocks thin content from ready state' );
maa_assert_true( in_array( 'content', $publishing_checklist['data']['missing'] ?? array(), true ), 'get-content-publishing-checklist reports missing content depth' );
maa_assert_true( in_array( 'excerpt', $publishing_checklist['data']['warnings'] ?? array(), true ), 'get-content-publishing-checklist reports missing excerpt as warning' );
$GLOBALS['maa_unit_style_posts'][78] = (object) array(
	'ID'           => 78,
	'post_title'   => 'Internal Link Candidate',
	'post_status'  => 'publish',
	'post_type'    => 'post',
	'post_excerpt' => 'Candidate excerpt.',
	'post_content' => 'Optimization workflow candidate content with enough body text to support an internal link opportunity report for related workflow articles.',
	'post_name'    => 'internal-link-candidate',
	'post_author'  => 7,
	'post_modified' => '2024-01-02 03:04:05',
);
$inventory_health = $core_read_package->get_content_inventory_health(
	array(
		'post_type' => 'post',
		'status'    => 'any',
		'per_page'  => 5,
		'page'      => 1,
	)
);
maa_assert_same( true, $inventory_health['success'] ?? null, 'get-content-inventory-health returns a success envelope' );
maa_assert_true( (int) ( $inventory_health['data']['summary']['scanned_count'] ?? 0 ) > 0, 'get-content-inventory-health scans bounded inventory rows' );
maa_assert_true( isset( $inventory_health['data']['health_score'] ), 'get-content-inventory-health returns a health score' );
$inventory_health_cached = $core_read_package->get_content_inventory_health(
	array(
		'post_type' => 'post',
		'status'    => 'any',
		'per_page'  => 5,
		'page'      => 1,
	)
);
maa_assert_same( true, $inventory_health_cached['meta']['cache_hit'] ?? null, 'get-content-inventory-health uses the bounded read cache on repeated calls' );
$GLOBALS['maa_unit_style_posts'][79] = (object) array(
	'ID'           => 79,
	'post_title'   => 'Core Governance Comment Smoke',
	'post_status'  => 'draft',
	'post_type'    => 'post',
	'post_excerpt' => '',
	'post_content' => 'Temporary smoke content for local validation.',
	'post_name'    => 'core-governance-comment-smoke',
	'post_author'  => 7,
	'post_modified' => '2026-05-30 10:00:00',
);
$test_inventory = $core_read_package->get_test_content_inventory(
	array(
		'patterns' => array( 'Core Governance' ),
		'per_page' => 10,
	)
);
maa_assert_same( true, $test_inventory['success'] ?? null, 'get-test-content-inventory returns a success envelope' );
maa_assert_same( true, $test_inventory['data']['detected'] ?? null, 'get-test-content-inventory detects matching smoke content' );
maa_assert_same( 'Core Governance', $test_inventory['data']['posts']['items'][0]['matched_pattern'] ?? '', 'get-test-content-inventory returns matched pattern' );
$cleanup_plan = $core_read_package->build_test_content_cleanup_plan(
	array(
		'patterns'    => array( 'Core Governance' ),
		'max_actions' => 5,
	)
);
maa_assert_same( true, $cleanup_plan['success'] ?? null, 'build-test-content-cleanup-plan returns a success envelope' );
maa_assert_same( 'magick-ai/trash-post', $cleanup_plan['data']['write_actions'][0]['target_ability_id'] ?? '', 'test content cleanup plan reuses trash-post' );
maa_assert_same( false, $cleanup_plan['data']['write_actions'][0]['commit_execution'] ?? null, 'test content cleanup plan does not execute commits' );
$GLOBALS['maa_unit_style_posts'][80] = (object) array(
	'ID'           => 80,
	'post_title'   => 'Inventory Fix Candidate',
	'post_status'  => 'draft',
	'post_type'    => 'post',
	'post_excerpt' => '',
	'post_content' => 'Inventory fix candidate content with enough text to generate a deterministic excerpt and metadata description for review planning.',
	'post_name'    => '',
	'post_author'  => 7,
	'post_modified' => '2026-05-30 11:00:00',
);
$content_fix_plan = $core_read_package->build_content_inventory_fix_plan(
	array(
		'post_ids'    => array( 80 ),
		'issue_types' => array( 'seo_title', 'seo_description', 'slug', 'excerpt' ),
	)
);
maa_assert_same( true, $content_fix_plan['success'] ?? null, 'build-content-inventory-fix-plan returns a success envelope' );
maa_assert_same( true, $content_fix_plan['data']['requires_approval'] ?? null, 'content inventory fix plan requires approval' );
maa_assert_same( 'magick-ai/set-post-seo-meta', $content_fix_plan['data']['write_actions'][0]['target_ability_id'] ?? '', 'content inventory fix plan reuses SEO write ability' );
maa_assert_same( false, $content_fix_plan['data']['write_actions'][0]['commit_execution'] ?? null, 'content inventory fix plan does not execute commits' );
maa_assert_true( isset( $content_fix_plan['data']['preview'][0]['before']['seo_title'] ), 'content inventory fix plan returns before preview' );
maa_assert_true( isset( $content_fix_plan['data']['preview'][0]['after_suggestion']['seo_title'] ), 'content inventory fix plan returns after suggestion preview' );
$GLOBALS['maa_unit_style_posts'][81] = (object) array(
	'ID'           => 81,
	'post_title'   => '',
	'post_status'  => 'draft',
	'post_type'    => 'post',
	'post_excerpt' => 'Has excerpt.',
	'post_content' => 'Inventory fix title candidate content with enough words to avoid unrelated body content warnings during planning.',
	'post_name'    => 'inventory-fix-title-candidate',
	'post_author'  => 7,
	'post_modified' => '2026-05-30 11:10:00',
);
$title_fix_plan = $core_read_package->build_content_inventory_fix_plan(
	array(
		'post_ids'    => array( 81 ),
		'issue_types' => array( 'title' ),
	)
);
maa_assert_same( 'magick-ai/update-post', $title_fix_plan['data']['write_actions'][0]['target_ability_id'] ?? '', 'content inventory fix plan maps missing title to update-post' );
maa_assert_same( array( 'title' ), $title_fix_plan['data']['write_actions'][0]['requires_input'] ?? array(), 'content inventory title plan requires a reviewed title input' );
$bulk_checklist = $core_read_package->get_bulk_publishing_checklist(
	array(
		'post_ids' => array( 77, 78, 77 ),
	)
);
maa_assert_same( true, $bulk_checklist['success'] ?? null, 'get-bulk-publishing-checklist returns a success envelope' );
maa_assert_same( 2, $bulk_checklist['data']['total'] ?? null, 'get-bulk-publishing-checklist deduplicates post ids' );
maa_assert_true( (int) ( $bulk_checklist['data']['blocked_count'] ?? 0 ) >= 1, 'get-bulk-publishing-checklist counts blocked posts' );
$internal_link_report = $core_read_package->get_internal_link_opportunity_report(
	array(
		'post_id'       => 77,
		'focus_keyword' => 'workflow',
		'max_targets'   => 3,
	)
);
maa_assert_same( true, $internal_link_report['success'] ?? null, 'get-internal-link-opportunity-report returns a success envelope' );
maa_assert_same( 77, $internal_link_report['data']['source_post']['post_id'] ?? null, 'get-internal-link-opportunity-report keeps source post id' );
maa_assert_true( (int) ( $internal_link_report['data']['summary']['candidate_count'] ?? 0 ) >= 1, 'get-internal-link-opportunity-report finds local candidate posts in isolated tests' );
$GLOBALS['maa_unit_comments'][21] = (object) array(
	'comment_ID'       => 21,
	'comment_post_ID'  => 77,
	'comment_author'   => 'Ops Reader',
	'comment_approved' => 'hold',
	'comment_content'  => 'Please review this operations comment.',
);
$GLOBALS['maa_unit_terms'] = array(
	'category' => array(
		(object) array(
			'term_id'     => 301,
			'name'        => 'Workflow',
			'slug'        => 'workflow',
			'description' => '',
			'count'       => 0,
			'parent'      => 0,
		),
	),
);
$site_operations = $core_read_package->get_site_operations_dashboard(
	array(
		'post_type' => 'post',
		'per_page'  => 5,
	)
);
maa_assert_same( true, $site_operations['success'] ?? null, 'get-site-operations-dashboard returns a success envelope' );
maa_assert_true( isset( $site_operations['data']['status_counts']['draft'] ), 'get-site-operations-dashboard returns status counts' );
maa_assert_true( (int) ( $site_operations['data']['comments']['pending_count'] ?? 0 ) >= 1, 'get-site-operations-dashboard counts pending comments' );
$publish_risk = $core_read_package->get_post_publish_risk_report(
	array(
		'post_id'       => 77,
		'focus_keyword' => 'workflow',
	)
);
maa_assert_same( true, $publish_risk['success'] ?? null, 'get-post-publish-risk-report returns a success envelope' );
maa_assert_same( 77, $publish_risk['data']['post']['post_id'] ?? null, 'get-post-publish-risk-report keeps post id' );
maa_assert_true( (int) ( $publish_risk['data']['risk_score'] ?? 0 ) > 0, 'get-post-publish-risk-report returns a positive risk score for incomplete drafts' );
$article_publish_preflight = $core_read_package->get_article_publish_preflight_context(
	array(
		'post_id'       => 77,
		'focus_keyword' => 'workflow',
		'window_days'   => 30,
	)
);
maa_assert_same( true, $article_publish_preflight['success'] ?? null, 'get-article-publish-preflight-context returns a success envelope' );
maa_assert_same( 'workflow/wordpress_article_publish_preflight', $article_publish_preflight['data']['recipe'] ?? '', 'get-article-publish-preflight-context declares its recipe id' );
maa_assert_true( in_array( 'publish_risk', $article_publish_preflight['data']['sections'] ?? array(), true ), 'get-article-publish-preflight-context includes publish risk' );
$refresh_opportunities = $core_read_package->get_content_refresh_opportunities(
	array(
		'post_type'      => 'post',
		'status'         => 'any',
		'per_page'       => 5,
		'stale_days'     => 30,
		'min_word_count' => 200,
	)
);
maa_assert_same( true, $refresh_opportunities['success'] ?? null, 'get-content-refresh-opportunities returns a success envelope' );
maa_assert_true( (int) ( $refresh_opportunities['data']['summary']['opportunity_count'] ?? 0 ) >= 1, 'get-content-refresh-opportunities finds refresh candidates' );
maa_assert_true( isset( $refresh_opportunities['data']['issue_counts']['thin_content'] ), 'get-content-refresh-opportunities counts thin content' );
$GLOBALS['maa_unit_style_posts'][77]->post_content = 'Optimization context content with <a href="https://example.test/?p=78">workflow candidate</a>.';
$internal_link_graph = $core_read_package->get_internal_link_graph_health(
	array(
		'post_type' => 'post',
		'status'    => 'any',
		'per_page'  => 5,
	)
);
maa_assert_same( true, $internal_link_graph['success'] ?? null, 'get-internal-link-graph-health returns a success envelope' );
maa_assert_true( (int) ( $internal_link_graph['data']['summary']['scanned_count'] ?? 0 ) >= 1, 'get-internal-link-graph-health scans local posts' );
maa_assert_true( isset( $internal_link_graph['data']['issue_counts']['orphan_post'] ), 'get-internal-link-graph-health counts orphan posts' );
$old_article_refresh = $core_read_package->get_old_article_refresh_context(
	array(
		'post_type'     => 'post',
		'status'        => 'any',
		'per_page'      => 5,
		'topic_seed'    => 'workflow',
		'post_id'       => 77,
		'focus_keyword' => 'workflow',
	)
);
maa_assert_same( true, $old_article_refresh['success'] ?? null, 'get-old-article-refresh-context returns a success envelope' );
maa_assert_same( 'workflow/wordpress_old_article_refresh_discovery', $old_article_refresh['data']['recipe'] ?? '', 'get-old-article-refresh-context declares its recipe id' );
maa_assert_true( in_array( 'seo_geo_gap_report', $old_article_refresh['data']['sections'] ?? array(), true ), 'get-old-article-refresh-context includes SEO/GEO gaps' );
$GLOBALS['maa_unit_style_posts'][79] = (object) array(
	'ID'             => 79,
	'post_title'     => 'Workflow diagram image',
	'post_status'    => 'inherit',
	'post_type'      => 'attachment',
	'post_excerpt'   => '',
	'post_content'   => '',
	'post_name'      => 'workflow-diagram-image',
	'post_author'    => 7,
	'post_mime_type' => 'image/jpeg',
);
$media_health = $core_read_package->get_media_inventory_health(
	array(
		'mime_type' => 'image',
		'per_page'  => 5,
	)
);
maa_assert_same( true, $media_health['success'] ?? null, 'get-media-inventory-health returns a success envelope' );
maa_assert_true( (int) ( $media_health['data']['summary']['scanned_count'] ?? 0 ) >= 1, 'get-media-inventory-health scans local media rows' );
maa_assert_true( isset( $media_health['data']['issue_counts']['missing_alt'] ), 'get-media-inventory-health counts missing alt text' );
$media_cleanup = $core_read_package->get_media_cleanup_opportunities(
	array(
		'mime_type' => 'image',
		'per_page'  => 5,
	)
);
maa_assert_same( true, $media_cleanup['success'] ?? null, 'get-media-cleanup-opportunities returns a success envelope' );
maa_assert_true( (int) ( $media_cleanup['data']['summary']['opportunity_count'] ?? 0 ) >= 1, 'get-media-cleanup-opportunities finds cleanup opportunities' );
maa_assert_true( isset( $media_cleanup['data']['issue_counts']['possibly_unattached'] ), 'get-media-cleanup-opportunities counts unattached media' );
$media_fix_plan = $core_read_package->build_media_inventory_fix_plan(
	array(
		'attachment_ids'  => array( 79 ),
		'issue_types'     => array( 'missing_alt', 'missing_caption', 'missing_description', 'possibly_unattached' ),
		'article_title'   => 'Workflow automation',
		'article_excerpt' => 'Workflow automation improves repeatable editorial operations.',
		'focus_keyword'   => 'workflow',
	)
);
maa_assert_same( true, $media_fix_plan['success'] ?? null, 'build-media-inventory-fix-plan returns a success envelope' );
maa_assert_same( true, $media_fix_plan['data']['requires_approval'] ?? null, 'media inventory fix plan requires approval' );
maa_assert_same( false, $media_fix_plan['data']['commit_execution'] ?? null, 'media inventory fix plan does not execute commits' );
maa_assert_same( 'magick-ai/update-media-details', $media_fix_plan['data']['write_actions'][0]['target_ability_id'] ?? '', 'media inventory fix plan reuses update-media-details' );
maa_assert_same( false, $media_fix_plan['data']['write_actions'][0]['commit_execution'] ?? null, 'media metadata plan action does not execute commits' );
maa_assert_true( isset( $media_fix_plan['data']['preview'][0]['before']['alt'] ), 'media inventory fix plan returns before preview' );
maa_assert_true( isset( $media_fix_plan['data']['preview'][0]['after_suggestion']['alt'] ), 'media inventory fix plan returns after suggestion preview' );
maa_assert_same( 'magick-ai/delete-media-permanently', $media_fix_plan['data']['skipped_destructive_candidates'][0]['target_ability_id'] ?? '', 'media inventory fix plan skips destructive candidates by default' );
$media_delete_plan = $core_read_package->build_media_inventory_fix_plan(
	array(
		'attachment_ids'              => array( 79 ),
		'issue_types'                 => array( 'possibly_unattached' ),
		'include_delete_candidates'   => true,
	)
);
maa_assert_same( 'magick-ai/delete-media-permanently', $media_delete_plan['data']['write_actions'][0]['target_ability_id'] ?? '', 'media inventory fix plan can explicitly map delete candidates' );
maa_assert_same( 'high', $media_delete_plan['data']['write_actions'][0]['risk'] ?? '', 'media delete candidate is marked high risk' );
$seo_geo_readiness = $core_read_package->get_post_seo_geo_readiness(
	array(
		'post_id'       => 77,
		'focus_keyword' => 'workflow',
	)
);
maa_assert_same( true, $seo_geo_readiness['success'] ?? null, 'get-post-seo-geo-readiness returns a success envelope' );
maa_assert_same( 77, $seo_geo_readiness['data']['post']['post_id'] ?? null, 'get-post-seo-geo-readiness keeps post id' );
maa_assert_true( isset( $seo_geo_readiness['data']['readiness_score'] ), 'get-post-seo-geo-readiness returns a readiness score' );
$topic_coverage = $core_read_package->get_site_topic_coverage_report(
	array(
		'post_type'  => 'post',
		'status'     => 'any',
		'per_page'   => 5,
		'topic_seed' => 'workflow',
	)
);
maa_assert_same( true, $topic_coverage['success'] ?? null, 'get-site-topic-coverage-report returns a success envelope' );
maa_assert_true( (int) ( $topic_coverage['data']['summary']['scanned_count'] ?? 0 ) >= 1, 'get-site-topic-coverage-report scans local posts' );
maa_assert_true( ! empty( $topic_coverage['data']['topics'] ), 'get-site-topic-coverage-report returns topic rows' );
$GLOBALS['maa_unit_terms'] = array(
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
maa_assert_same( true, $taxonomy_health['success'] ?? null, 'get-taxonomy-inventory-health returns a success envelope' );
maa_assert_same( 'category', $taxonomy_health['data']['taxonomy'] ?? '', 'get-taxonomy-inventory-health keeps taxonomy name' );
maa_assert_true( isset( $taxonomy_health['data']['issue_counts']['missing_description'] ), 'get-taxonomy-inventory-health counts missing descriptions' );
maa_assert_true( isset( $taxonomy_health['data']['issue_counts']['unused_term'] ), 'get-taxonomy-inventory-health counts unused terms' );
$taxonomy_consolidation = $core_read_package->get_taxonomy_consolidation_suggestions(
	array(
		'taxonomy' => 'post_tag',
		'per_page' => 10,
	)
);
maa_assert_same( true, $taxonomy_consolidation['success'] ?? null, 'get-taxonomy-consolidation-suggestions returns a success envelope' );
maa_assert_true( (int) ( $taxonomy_consolidation['data']['summary']['suggestion_count'] ?? 0 ) >= 1, 'get-taxonomy-consolidation-suggestions returns suggestions' );
maa_assert_same( 'duplicate_or_near_duplicate', $taxonomy_consolidation['data']['suggestions'][1]['type'] ?? '', 'get-taxonomy-consolidation-suggestions detects duplicate term groups' );
$GLOBALS['maa_unit_post_terms'][77]['post_tag'] = array(
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
maa_assert_same( true, $post_taxonomy_proposal['success'] ?? null, 'propose-post-taxonomy-terms returns a success envelope' );
maa_assert_same( 'magick-ai/set-post-terms', $post_taxonomy_proposal['data']['proposal']['target_ability_id'] ?? '', 'post taxonomy proposal targets set-post-terms' );
maa_assert_same( false, $post_taxonomy_proposal['data']['proposal']['commit_execution'] ?? null, 'post taxonomy proposal does not execute commits' );
maa_assert_same( array( 401, 402 ), $post_taxonomy_proposal['data']['proposed_term_ids'] ?? array(), 'post taxonomy proposal computes proposed terms from current and matched candidates' );
maa_assert_same( 'Unknown Topic', $post_taxonomy_proposal['data']['unmatched_terms'][0]['value'] ?? '', 'post taxonomy proposal reports unmatched term names' );
$GLOBALS['maa_unit_style_posts'][81] = (object) array(
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
maa_assert_same( true, $page_structure['success'] ?? null, 'get-page-structure-health returns a success envelope' );
maa_assert_same( 1, $page_structure['data']['summary']['pages_with_issues'] ?? null, 'get-page-structure-health counts pages with issues' );
maa_assert_true( in_array( 'missing_cta', $page_structure['data']['items'][0]['issues'] ?? array(), true ), 'get-page-structure-health detects missing CTA' );
$seo_geo_gap = $core_read_package->get_seo_geo_gap_report(
	array(
		'post_type'  => 'post',
		'status'     => 'any',
		'per_page'   => 5,
		'topic_seed' => 'workflow',
	)
);
maa_assert_same( true, $seo_geo_gap['success'] ?? null, 'get-seo-geo-gap-report returns a success envelope' );
maa_assert_true( (int) ( $seo_geo_gap['data']['summary']['gap_count'] ?? 0 ) >= 1, 'get-seo-geo-gap-report reports gaps from refresh and coverage scans' );
$seo_geo_gap_cached = $core_read_package->get_seo_geo_gap_report(
	array(
		'post_type'  => 'post',
		'status'     => 'any',
		'per_page'   => 5,
		'topic_seed' => 'workflow',
	)
);
maa_assert_same( true, $seo_geo_gap_cached['meta']['cache_hit'] ?? null, 'get-seo-geo-gap-report uses the bounded read cache on repeated calls' );
$site_style_baseline = $core_read_package->get_site_style_baseline(
	array(
		'mode'  => 'site_recent',
		'limit' => 3,
	)
);
maa_assert_same( true, $site_style_baseline['success'] ?? null, 'get-site-style-baseline returns a success envelope' );
maa_assert_true( isset( $site_style_baseline['data']['profile'] ), 'get-site-style-baseline returns a profile payload' );
$workflow_context = $core_read_package->build_article_workflow_context(
	array(
		'workflow'   => 'publish',
		'post_id'    => 77,
		'topic_seed' => 'workflow',
	)
);
maa_assert_same( true, $workflow_context['success'] ?? null, 'build-article-workflow-context returns a success envelope' );
maa_assert_true( in_array( 'post_context', $workflow_context['data']['sections'] ?? array(), true ), 'build-article-workflow-context includes post context when post_id is provided' );
$GLOBALS['maa_unit_style_posts'][82] = (object) array(
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
maa_assert_same( true, $publishing_calendar['success'] ?? null, 'get-publishing-calendar-context returns a success envelope' );
maa_assert_true( isset( $publishing_calendar['data']['status_counts']['future'] ), 'get-publishing-calendar-context returns status counts' );
$GLOBALS['maa_unit_style_posts'][771] = (object) array(
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
maa_assert_same( true, $revision_risk['success'] ?? null, 'get-revision-change-risk-report returns a success envelope' );
maa_assert_same( 77, $revision_risk['data']['post']['post_id'] ?? null, 'get-revision-change-risk-report keeps post id' );
maa_assert_true( in_array( 'title_changed', $revision_risk['data']['risk_flags'] ?? array(), true ), 'get-revision-change-risk-report detects title changes' );
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
	maa_assert_true( ! isset( $package_catalog[ $catalog_key ]['open_api_enabled'] ), "{$migrated_ability_id} catalog projection does not own Open API policy" );
}
foreach ( $migrated_write_ability_ids as $migrated_write_ability_id ) {
	$catalog_key = str_replace( '/', '_', $migrated_write_ability_id );
	maa_assert_true( isset( $package_catalog[ $catalog_key ] ), "catalog bridge projects migrated {$migrated_write_ability_id}" );
	maa_assert_same( 'wp_ability', $package_catalog[ $catalog_key ]['executor_type'], "{$migrated_write_ability_id} catalog entry executes through wp_ability" );
	maa_assert_same( true, $package_catalog[ $catalog_key ]['requires_confirm'], "{$migrated_write_ability_id} catalog projection requires confirmation" );
	maa_assert_same( 'write', $package_catalog[ $catalog_key ]['risk_level'], "{$migrated_write_ability_id} catalog projection is write risk" );
	maa_assert_same( true, $package_catalog[ $catalog_key ]['show_in_rest'], "{$migrated_write_ability_id} catalog projection exposes show_in_rest for host normalization" );
	maa_assert_true( ! isset( $package_catalog[ $catalog_key ]['write_mode'] ), "{$migrated_write_ability_id} catalog projection does not own write mode policy" );
	maa_assert_true( ! isset( $package_catalog[ $catalog_key ]['open_api_enabled'] ), "{$migrated_write_ability_id} catalog projection does not own Open API policy" );
	maa_assert_true( ! isset( $package_catalog[ $catalog_key ]['skip_catalog_manifest_fallback'] ), "{$migrated_write_ability_id} catalog projection does not own host fallback policy" );
}
foreach ( $migrated_destructive_ability_ids as $migrated_destructive_ability_id ) {
	$catalog_key = str_replace( '/', '_', $migrated_destructive_ability_id );
	maa_assert_true( isset( $package_catalog[ $catalog_key ] ), "catalog bridge projects migrated {$migrated_destructive_ability_id}" );
	maa_assert_same( 'wp_ability', $package_catalog[ $catalog_key ]['executor_type'], "{$migrated_destructive_ability_id} catalog entry executes through wp_ability" );
	maa_assert_same( true, $package_catalog[ $catalog_key ]['requires_confirm'], "{$migrated_destructive_ability_id} catalog projection requires confirmation" );
	maa_assert_same( 'destructive', $package_catalog[ $catalog_key ]['risk_level'], "{$migrated_destructive_ability_id} catalog projection is destructive risk" );
	maa_assert_true( ! isset( $package_catalog[ $catalog_key ]['write_mode'] ), "{$migrated_destructive_ability_id} catalog projection does not own write mode policy" );
	maa_assert_true( ! isset( $package_catalog[ $catalog_key ]['tool_policy'] ), "{$migrated_destructive_ability_id} catalog projection does not own destructive tool policy" );
	maa_assert_true( ! isset( $package_catalog[ $catalog_key ]['open_api_enabled'] ), "{$migrated_destructive_ability_id} catalog projection does not own Open API policy" );
	maa_assert_true( ! isset( $package_catalog[ $catalog_key ]['skip_catalog_manifest_fallback'] ), "{$migrated_destructive_ability_id} catalog projection does not own host fallback policy" );
}
maa_assert_true( ! isset( $package_catalog['magick-ai-abilities_wp-diagnostics-summary'] ), 'catalog bridge does not project standalone diagnostics ability' );
maa_assert_true( ! isset( $package_catalog['magick-ai-abilities_wp-ops-diagnostics-detail'] ), 'catalog bridge does not project standalone ops diagnostics ability' );

$workflow_replay_path = __DIR__ . '/fixtures/agent-workflow-replay.json';
$workflow_replay_json = file_get_contents( $workflow_replay_path );
maa_assert_true( false !== $workflow_replay_json, 'agent workflow replay fixture is readable' );
$workflow_replay = json_decode( (string) $workflow_replay_json, true );
maa_assert_true( is_array( $workflow_replay ), 'agent workflow replay fixture decodes as an object' );
$workflow_manifest = \Magick_AI_Abilities\Workflow\Workflow_Definition_Provider::manifest();
maa_assert_same( $workflow_manifest, $workflow_replay, 'agent workflow replay fixture matches production workflow definition provider' );
maa_assert_same( $workflow_manifest, magick_ai_abilities_get_workflow_definitions(), 'public workflow definitions helper matches provider manifest' );
maa_assert_same( $workflow_manifest['cases']['article_publish_preflight'], magick_ai_abilities_get_workflow_definition( 'workflow/wordpress_article_publish_preflight' ), 'public workflow definition helper resolves recipe id' );
maa_assert_same( 'v1', $workflow_replay['schema_version'] ?? '', 'agent workflow replay fixture schema is v1' );
maa_assert_true( is_array( $workflow_replay['cases'] ?? null ), 'agent workflow replay fixture exposes cases' );
$forbidden_workflow_definition_fields = \Magick_AI_Abilities\Workflow\Workflow_Definition_Provider::forbidden_field_keys();
maa_assert_array_omits_keys( $workflow_replay, $forbidden_workflow_definition_fields, 'agent workflow replay fixture' );
$expected_workflow_replay_cases = array(
	'article_publish_preflight'      => array(
		'ability_id'         => 'magick-ai/get-article-publish-preflight-context',
		'recipe_id'          => 'workflow/wordpress_article_publish_preflight',
		'required_scope'     => 'post.read',
		'required_inputs'    => array( 'post_id' ),
		'expected_sections'  => array( 'post_context', 'publishing_checklist', 'publish_risk', 'workflow_context', 'publishing_calendar' ),
		'expanded_abilities' => array(
			'magick-ai/get-post-context',
			'magick-ai/get-content-publishing-checklist',
			'magick-ai/get-post-publish-risk-report',
			'magick-ai/build-article-workflow-context',
			'magick-ai/get-publishing-calendar-context',
		),
		'handoff_kind'       => 'context',
		'disallowed_default' => array( 'magick-ai/schedule-post', 'magick-ai/publish-post' ),
	),
	'old_article_refresh_discovery' => array(
		'ability_id'         => 'magick-ai/get-old-article-refresh-context',
		'recipe_id'          => 'workflow/wordpress_old_article_refresh_discovery',
		'required_scope'     => 'post.read',
		'required_inputs'    => array(),
		'expected_sections'  => array( 'refresh_opportunities', 'seo_geo_gap_report', 'site_style_baseline', 'internal_link_graph_health' ),
		'expanded_abilities' => array(
			'magick-ai/get-content-refresh-opportunities',
			'magick-ai/get-seo-geo-gap-report',
			'magick-ai/get-site-style-baseline',
			'magick-ai/get-internal-link-graph-health',
			'magick-ai/get-internal-link-opportunity-report',
		),
		'handoff_kind'       => 'context',
		'disallowed_default' => array( 'magick-ai/patch-post-content', 'magick-ai/update-post', 'magick-ai/update-post-blocks' ),
	),
	'comment_compliance_handoff'    => array(
		'ability_id'         => 'magick-ai/get-comment-compliance-handoff',
		'recipe_id'          => 'workflow/wordpress_comment_compliance_handoff',
		'required_scope'     => 'comments.manage',
		'required_inputs'    => array(),
		'expected_sections'  => array( 'queue_health', 'priority_queue', 'selected_moderation_suggestion' ),
		'expanded_abilities' => array(
			'magick-ai/get-comment-queue-health',
			'magick-ai/get-comment-action-priority-queue',
			'magick-ai/build-comment-moderation-suggest',
			'magick-ai/build-comment-mention-reply-suggest',
			'magick-ai/compose-comment-moderation-result',
		),
		'handoff_kind'       => 'context',
		'disallowed_default' => array( 'magick-ai/approve-comment', 'magick-ai/reply-comment', 'magick-ai/spam-comment', 'magick-ai/trash-comment' ),
	),
);
maa_assert_same( array_keys( $expected_workflow_replay_cases ), array_keys( $workflow_replay['cases'] ), 'agent workflow replay fixture keeps the three approved stabilization cases in order' );
foreach ( $expected_workflow_replay_cases as $case_id => $expected_case ) {
	$case = $workflow_replay['cases'][ $case_id ] ?? array();
	maa_assert_true( is_array( $case ), "agent workflow replay case {$case_id} is an object" );
	maa_assert_same( 'workflow_recipe', $case['definition_kind'] ?? '', "agent workflow replay case {$case_id} is a workflow recipe definition" );
	maa_assert_same( 'v1', $case['contract_version'] ?? '', "agent workflow replay case {$case_id} uses definition contract v1" );
	maa_assert_true( is_array( $case['natural_tasks'] ?? null ), "agent workflow replay case {$case_id} exposes natural task examples" );
	maa_assert_true( count( $case['natural_tasks'] ) >= 3, "agent workflow replay case {$case_id} keeps at least three natural task examples" );
	maa_assert_same( $expected_case['ability_id'], $case['preferred_ability_id'] ?? '', "agent workflow replay case {$case_id} prefers the bundle ability" );
	maa_assert_same( $expected_case['ability_id'], $case['entrypoint_ability_id'] ?? '', "agent workflow replay case {$case_id} exposes the preferred bundle as entrypoint" );
	maa_assert_same( $expected_case['recipe_id'], $case['recipe_id'] ?? '', "agent workflow replay case {$case_id} keeps the recipe id" );
	maa_assert_same( $expected_case['required_scope'], $case['required_scope'] ?? '', "agent workflow replay case {$case_id} keeps the required scope" );
	maa_assert_same( $expected_case['required_inputs'], $case['required_inputs'] ?? array(), "agent workflow replay case {$case_id} keeps required inputs" );
	maa_assert_same( $expected_case['expected_sections'], $case['expected_sections'] ?? array(), "agent workflow replay case {$case_id} keeps expected output sections" );
	maa_assert_same( $expected_case['expanded_abilities'], $case['expanded_ability_ids'] ?? array(), "agent workflow replay case {$case_id} keeps expanded ability chain" );
	maa_assert_true( is_array( $case['handoff'] ?? null ), "agent workflow replay case {$case_id} exposes a structured handoff" );
	maa_assert_same( $expected_case['handoff_kind'], $case['handoff']['kind'] ?? '', "agent workflow replay case {$case_id} keeps handoff kind" );
	maa_assert_same( 'host', $case['handoff']['owner'] ?? '', "agent workflow replay case {$case_id} keeps host-owned handoff" );
	maa_assert_true( is_string( $case['handoff']['next_action'] ?? null ) && '' !== $case['handoff']['next_action'], "agent workflow replay case {$case_id} keeps a host next action hint" );
	maa_assert_same( 'fail_closed', $case['failure_policy'] ?? '', "agent workflow replay case {$case_id} fails closed" );
	maa_assert_same( $expected_case['disallowed_default'], $case['disallowed_default_ability_ids'] ?? array(), "agent workflow replay case {$case_id} keeps disallowed default write abilities" );
	maa_assert_same( true, $case['host_governed_write_boundary'] ?? null, "agent workflow replay case {$case_id} keeps host-governed write boundary" );
	maa_assert_true( isset( $package_abilities[ $expected_case['ability_id'] ] ), "agent workflow replay case {$case_id} points to a registered ability" );
	$entrypoint_ability = $package_abilities[ $expected_case['ability_id'] ];
	maa_assert_same( 'read', $entrypoint_ability['risk_level'] ?? '', "agent workflow replay case {$case_id} points to a read-risk bundle" );
	maa_assert_same( false, $entrypoint_ability['requires_confirm'] ?? null, "agent workflow replay case {$case_id} points to a read-only bundle without confirmation" );
	maa_assert_same( $expected_case['required_scope'], $entrypoint_ability['required_scope'] ?? '', "agent workflow replay case {$case_id} matches registered ability scope" );
	maa_assert_same( $expected_case['required_inputs'], $entrypoint_ability['input_schema']['required'] ?? array(), "agent workflow replay case {$case_id} required inputs match the entrypoint schema" );
	$case_catalog_key = str_replace( '/', '_', $expected_case['ability_id'] );
	maa_assert_same( 'wp_ability', $package_catalog[ $case_catalog_key ]['executor_type'] ?? '', "agent workflow replay case {$case_id} is projected for wp_ability execution" );
	foreach ( $expected_case['expanded_abilities'] as $expanded_ability_id ) {
		maa_assert_true( isset( $package_abilities[ $expanded_ability_id ] ), "agent workflow replay case {$case_id} references known expanded ability {$expanded_ability_id}" );
		maa_assert_true( 'destructive' !== ( $package_abilities[ $expanded_ability_id ]['risk_level'] ?? '' ), "agent workflow replay case {$case_id} expanded ability {$expanded_ability_id} is not destructive" );
	}
	foreach ( $expected_case['disallowed_default'] as $disallowed_ability_id ) {
		maa_assert_true( $disallowed_ability_id !== $expected_case['ability_id'], "agent workflow replay case {$case_id} does not disallow its preferred bundle" );
		maa_assert_true( isset( $package_abilities[ $disallowed_ability_id ] ), "agent workflow replay case {$case_id} references a known disallowed default ability {$disallowed_ability_id}" );
		maa_assert_true( 'read' !== ( $package_abilities[ $disallowed_ability_id ]['risk_level'] ?? 'read' ), "agent workflow replay case {$case_id} disallowed default {$disallowed_ability_id} is write-like" );
	}
}

$workflow_list = call_user_func( $package_abilities['magick-ai-abilities/list-workflow-recipes']['execute_callback'], array() );
maa_assert_same( $workflow_manifest, $workflow_list, 'workflow recipe discovery ability returns provider manifest' );
$workflow_get = call_user_func( $package_abilities['magick-ai-abilities/get-workflow-recipe']['execute_callback'], array( 'recipe_id' => 'workflow/wordpress_comment_compliance_handoff' ) );
maa_assert_same( $workflow_manifest['cases']['comment_compliance_handoff'], $workflow_get, 'workflow recipe detail ability resolves recipe id' );
$workflow_missing = call_user_func( $package_abilities['magick-ai-abilities/get-workflow-recipe']['execute_callback'], array( 'recipe_id' => 'workflow/missing' ) );
maa_assert_true( is_wp_error( $workflow_missing ), 'workflow recipe detail ability fails closed for missing recipe' );

echo "OK: {$assertions} assertions\n";
