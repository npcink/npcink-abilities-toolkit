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
$core_write_package_source = (string) file_get_contents( dirname( __DIR__ ) . '/includes/Packages/Core_Write_Package.php' );

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

function maa_count_plan_actions_for_ability( array $actions, $ability_id ) {
	$count = 0;
	foreach ( $actions as $action ) {
		if ( $ability_id === (string) ( $action['target_ability_id'] ?? '' ) ) {
			++$count;
		}
	}

	return $count;
}

function maa_find_row_by_key( array $rows, $key, $value ) {
	foreach ( $rows as $row ) {
		if ( is_array( $row ) && (string) ( $row[ $key ] ?? '' ) === (string) $value ) {
			return $row;
		}
	}

	return array();
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

function maa_observability_events_of_kind( array $events, $event_kind ) {
	return array_values(
		array_filter(
			$events,
			static function ( $event ) use ( $event_kind ) {
				return is_array( $event ) && (string) ( $event['event_kind'] ?? '' ) === (string) $event_kind;
			}
		)
	);
}

function maa_assert_event_has_safe_event_id( array $event, $prefix, $message ) {
	$event_id = (string) ( $event['event_id'] ?? '' );
	maa_assert_true( 0 === strpos( $event_id, $prefix ), "{$message} event_id uses {$prefix} prefix" );
	maa_assert_true( 1 === preg_match( '/^[a-z0-9_]+$/', $event_id ), "{$message} event_id uses bounded id characters" );
}

function maa_assert_observability_event_is_metadata_only( array $event, $message ) {
	maa_assert_array_omits_keys(
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
maa_assert_true( false !== strpos( $admin_test_page, 'PARENT_MENU_SLUG' ), 'admin test page knows the shared Magick AI parent slug' );
maa_assert_true( false !== strpos( $admin_test_page, "const MENU_SLUG           = 'npcink-abilities-toolkit';" ), 'admin test page uses the canonical Abilities admin slug' );
maa_assert_true( false !== strpos( $admin_test_page, 'add_submenu_page' ), 'admin test page can attach to the shared Magick AI menu' );
maa_assert_true( false !== strpos( $admin_test_page, 'add_management_page' ), 'admin test page keeps the standalone Tools fallback' );
maa_assert_true( false !== strpos( $admin_test_page, "__( 'Npcink Abilities Toolkit', 'npcink-abilities-toolkit' ),\n\t\t\t\t__( 'Abilities', 'npcink-abilities-toolkit' )," ), 'admin test page registers the requested page and submenu titles when attached' );
$old_admin_slug = 'npcink-abilities-toolkit-' . 'test';
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
		'Time Display',
		'WordPress site timezone',
		'Y-m-d H:i:s',
		'Keep those output field names and semantics stable',
	) as $required
) {
	maa_assert_true( false !== strpos( $admin_surface_standard, $required ), 'admin surface standard documents ability page boundary: ' . $required );
}

$main_plugin_header = file_get_contents( __DIR__ . '/../npcink-abilities-toolkit.php' );
maa_assert_true( false !== strpos( $main_plugin_header, 'Requires at least: 7.0' ), 'main plugin header requires WordPress 7.0' );
maa_assert_true( false !== strpos( $main_plugin_header, 'Requires PHP: 8.0' ), 'main plugin header requires PHP 8.0' );

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
	maa_assert_true( false !== strpos( $readme, $required ), 'README precisely scopes host-governed ability ownership: ' . $required );
}
maa_assert_true( false !== strpos( $readme, 'docs/article-workflow-abilities-v1.md' ), 'README links the article workflow ability map.' );

$article_workflow_doc = file_get_contents( __DIR__ . '/../docs/article-workflow-abilities-v1.md' );
foreach (
	array(
		'article_draft_v1',
		'workflow/wordpress_article_draft',
		'article_assistant_workbench',
		'magick-ai/create-draft',
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
	maa_assert_true( false !== strpos( $article_workflow_doc, $required ), 'article workflow ability map preserves boundary: ' . $required );
}

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
maa_assert_same( 'npcink-abilities-toolkit-write', $write['category'], 'write proposal default category is write category' );
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

$GLOBALS['maa_unit_options'] = array();
$GLOBALS['maa_unit_transients'] = array();
$GLOBALS['maa_unit_registered_abilities'] = array();
$GLOBALS['maa_unit_observability_events'] = array();
add_action(
	'magick_ai_observability_event',
	static function ( $event ) {
		$GLOBALS['maa_unit_observability_events'][] = $event;
	}
);

$observability_categories = new Category_Registrar();
$observability_registrar = new Ability_Registrar( $observability_categories, $contract_normalizer );
maa_assert_true(
	$observability_registrar->add_readonly(
		'acme/observable-summary',
		array(
			'label'            => 'Observable Summary',
			'description'      => 'Returns observable summary.',
			'category'         => 'acme-observability',
			'input_schema'     => array( 'type' => 'object' ),
			'output_schema'    => array( 'type' => 'object' ),
			'meta'             => array(
				'secret'  => 'must-not-leak',
				'payload' => array( 'raw' => true ),
			),
			'execute_callback' => static function () {
				return array( 'ok' => true );
			},
		)
	),
	'observability registrar accepts test ability'
);
maa_assert_true(
	$observability_registrar->add_readonly(
		'acme/observable-wp-error',
		array(
			'label'            => 'Observable WP Error',
			'description'      => 'Returns observable WP error.',
			'input_schema'     => array( 'type' => 'object' ),
			'output_schema'    => array( 'type' => 'object' ),
			'execute_callback' => static function () {
				return new WP_Error( 'acme_callback_failed', 'Raw error message should not be emitted.', array( 'payload_json' => 'must-not-leak' ) );
			},
		)
	),
	'observability registrar accepts WP_Error callback ability'
);
maa_assert_true(
	$observability_registrar->add_readonly(
		'acme/observable-exception',
		array(
			'label'            => 'Observable Exception',
			'description'      => 'Throws observable exception.',
			'input_schema'     => array( 'type' => 'object' ),
			'output_schema'    => array( 'type' => 'object' ),
			'execute_callback' => static function () {
				throw new RuntimeException( 'Raw exception message should not be emitted.' );
			},
		)
	),
	'observability registrar accepts throwing callback ability'
);
$first_hash = $observability_registrar->catalog_fingerprint();
$observability_registrar->register_with_wordpress();
$catalog_events = maa_observability_events_of_kind( $GLOBALS['maa_unit_observability_events'], 'abilities.catalog.changed' );
maa_assert_same( 1, count( $catalog_events ), 'first bootstrap emits one catalog changed event' );
maa_assert_same( 'npcink-abilities-toolkit', $catalog_events[0]['plugin_slug'] ?? '', 'catalog event carries plugin slug' );
maa_assert_same( 'ok', $catalog_events[0]['status'] ?? '', 'catalog event status is ok' );
maa_assert_same( 'local', $catalog_events[0]['source'] ?? '', 'catalog event source remains local' );
maa_assert_same( 3, $catalog_events[0]['ability_count'] ?? 0, 'catalog event carries ability count' );
maa_assert_same( $first_hash, $catalog_events[0]['catalog_hash'] ?? '', 'catalog event carries current catalog hash' );
maa_assert_event_has_safe_event_id( $catalog_events[0], 'catalog_', 'catalog event' );
maa_assert_observability_event_is_metadata_only( $catalog_events[0], 'catalog event payload' );

$GLOBALS['maa_unit_registered_abilities'] = array();
$observability_registrar->register_with_wordpress();
$catalog_events = maa_observability_events_of_kind( $GLOBALS['maa_unit_observability_events'], 'abilities.catalog.changed' );
maa_assert_same( 1, count( $catalog_events ), 'repeated bootstrap does not repeat catalog changed event for the same hash' );
maa_assert_same( 0, count( maa_observability_events_of_kind( $GLOBALS['maa_unit_observability_events'], 'abilities.ability.registered' ) ), 'ability add no longer emits per-ability registered events' );
maa_assert_same( 0, count( maa_observability_events_of_kind( $GLOBALS['maa_unit_observability_events'], 'abilities.ability.wordpress_registered' ) ), 'WordPress registration no longer emits per-ability events' );

maa_assert_true(
	$observability_registrar->add_readonly(
		'acme/observable-detail',
		array(
			'label'            => 'Observable Detail',
			'description'      => 'Returns observable detail.',
			'input_schema'     => array( 'type' => 'object' ),
			'output_schema'    => array( 'type' => 'object' ),
			'execute_callback' => static function () {
				return array( 'detail' => true );
			},
		)
	),
	'observability registrar accepts changed catalog ability'
);
$changed_hash = $observability_registrar->catalog_fingerprint();
maa_assert_true( $first_hash !== $changed_hash, 'catalog hash changes when ability catalog changes' );
$GLOBALS['maa_unit_registered_abilities'] = array();
$observability_registrar->register_with_wordpress();
$catalog_events = maa_observability_events_of_kind( $GLOBALS['maa_unit_observability_events'], 'abilities.catalog.changed' );
maa_assert_same( 2, count( $catalog_events ), 'changed catalog hash emits one additional catalog changed event' );
maa_assert_same( $changed_hash, $catalog_events[1]['catalog_hash'] ?? '', 'changed catalog event carries new hash' );
maa_assert_same( $first_hash, $catalog_events[1]['previous_catalog_hash'] ?? '', 'changed catalog event carries previous hash' );
$GLOBALS['maa_unit_registered_abilities'] = array();
$observability_registrar->register_with_wordpress();
$catalog_events = maa_observability_events_of_kind( $GLOBALS['maa_unit_observability_events'], 'abilities.catalog.changed' );
maa_assert_same( 2, count( $catalog_events ), 'same changed catalog hash is rate limited after first emit' );

$GLOBALS['maa_unit_options'][ Ability_Registrar::CATALOG_STATE_OPTION ] = array(
	'catalog_hash'   => $changed_hash,
	'emitted_at'     => '2026-06-01T00:00:00+00:00',
	'plugin_version' => '0.0.0-old',
	'reason'         => 'bootstrap',
);
$old_version_rate_limit_key = Ability_Registrar::CATALOG_RATE_LIMIT_PREFIX . substr( hash( 'sha256', $changed_hash . '|0.0.0-old' ), 0, 40 );
$GLOBALS['maa_unit_transients'][ $old_version_rate_limit_key ] = '2026-06-01T00:00:00+00:00';
$GLOBALS['maa_unit_registered_abilities'] = array();
$observability_registrar->register_with_wordpress();
$catalog_events = maa_observability_events_of_kind( $GLOBALS['maa_unit_observability_events'], 'abilities.catalog.changed' );
maa_assert_same( 3, count( $catalog_events ), 'version change emits catalog changed even when old hash transient exists' );
maa_assert_same( $changed_hash, $catalog_events[2]['catalog_hash'] ?? '', 'version-change catalog event keeps unchanged hash' );
maa_assert_true( ! isset( $catalog_events[2]['previous_catalog_hash'] ), 'version-change same-hash catalog event omits previous hash' );
maa_assert_same( MAGICK_AI_ABILITIES_VERSION, $GLOBALS['maa_unit_options'][ Ability_Registrar::CATALOG_STATE_OPTION ]['plugin_version'] ?? '', 'version-change emit updates catalog state version' );

$callback = $GLOBALS['maa_unit_registered_abilities']['acme/observable-summary']['execute_callback'] ?? null;
maa_assert_true( is_callable( $callback ), 'registered ability keeps callable observed execute callback' );
$callback_result = call_user_func( $callback, array( 'raw_callback_input' => 'super-secret-callback-input' ) );
maa_assert_same( array( 'ok' => true ), $callback_result, 'observed callback returns original result' );
$callback_events = maa_observability_events_of_kind( $GLOBALS['maa_unit_observability_events'], 'abilities.callback.completed' );
maa_assert_same( 1, count( $callback_events ), 'callback execution still emits behavior observability event' );
maa_assert_same( 'acme/observable-summary', $callback_events[0]['ability_id'] ?? '', 'callback event carries ability id' );
maa_assert_same( 'ok', $callback_events[0]['status'] ?? '', 'callback event carries successful status' );
maa_assert_event_has_safe_event_id( $callback_events[0], 'ability_cb_', 'callback completed event' );
maa_assert_observability_event_is_metadata_only( $callback_events[0], 'callback completed event payload' );
maa_assert_true( false === strpos( wp_json_encode( $callback_events[0] ), 'super-secret-callback-input' ), 'callback completed event omits raw callback input values' );

$wp_error_callback = $GLOBALS['maa_unit_registered_abilities']['acme/observable-wp-error']['execute_callback'] ?? null;
maa_assert_true( is_callable( $wp_error_callback ), 'registered WP_Error ability keeps callable observed execute callback' );
$wp_error_result = call_user_func( $wp_error_callback, array( 'payload_json' => 'super-secret-callback-input' ) );
maa_assert_true( is_wp_error( $wp_error_result ), 'observed WP_Error callback returns original error result' );
$failed_callback_events = maa_observability_events_of_kind( $GLOBALS['maa_unit_observability_events'], 'abilities.callback.failed' );
maa_assert_same( 1, count( $failed_callback_events ), 'WP_Error callback emits one failed callback event' );
maa_assert_same( 'acme/observable-wp-error', $failed_callback_events[0]['ability_id'] ?? '', 'WP_Error failed event carries ability id' );
maa_assert_same( 'error', $failed_callback_events[0]['status'] ?? '', 'WP_Error failed event carries error status' );
maa_assert_same( 'abilities.callback_error', $failed_callback_events[0]['error_code'] ?? '', 'WP_Error failed event uses stable error code' );
maa_assert_same( 'acme_callback_failed', $failed_callback_events[0]['status_detail'] ?? '', 'WP_Error failed event carries redacted status detail' );
maa_assert_event_has_safe_event_id( $failed_callback_events[0], 'ability_cb_', 'WP_Error failed event' );
maa_assert_observability_event_is_metadata_only( $failed_callback_events[0], 'WP_Error failed event payload' );
maa_assert_true( false === strpos( wp_json_encode( $failed_callback_events[0] ), 'super-secret-callback-input' ), 'WP_Error failed event omits raw callback input values' );
maa_assert_true( false === strpos( wp_json_encode( $failed_callback_events[0] ), 'Raw error message should not be emitted.' ), 'WP_Error failed event omits raw error message' );

$exception_callback = $GLOBALS['maa_unit_registered_abilities']['acme/observable-exception']['execute_callback'] ?? null;
maa_assert_true( is_callable( $exception_callback ), 'registered exception ability keeps callable observed execute callback' );
try {
	call_user_func( $exception_callback, array( 'payload_json' => 'super-secret-callback-input' ) );
	maa_assert_true( false, 'observed exception callback rethrows original exception' );
} catch ( RuntimeException $exception ) {
	maa_assert_same( 'Raw exception message should not be emitted.', $exception->getMessage(), 'observed exception callback rethrows original exception message locally' );
}
$failed_callback_events = maa_observability_events_of_kind( $GLOBALS['maa_unit_observability_events'], 'abilities.callback.failed' );
maa_assert_same( 2, count( $failed_callback_events ), 'throwing callback emits one additional failed callback event' );
maa_assert_same( 'acme/observable-exception', $failed_callback_events[1]['ability_id'] ?? '', 'exception failed event carries ability id' );
maa_assert_same( 'abilities.callback_error', $failed_callback_events[1]['error_code'] ?? '', 'exception failed event uses stable error code' );
maa_assert_same( 'runtimeexception', $failed_callback_events[1]['status_detail'] ?? '', 'exception failed event carries redacted exception class' );
maa_assert_event_has_safe_event_id( $failed_callback_events[1], 'ability_cb_', 'exception failed event' );
maa_assert_observability_event_is_metadata_only( $failed_callback_events[1], 'exception failed event payload' );
maa_assert_true( false === strpos( wp_json_encode( $failed_callback_events[1] ), 'super-secret-callback-input' ), 'exception failed event omits raw callback input values' );
maa_assert_true( false === strpos( wp_json_encode( $failed_callback_events[1] ), 'Raw exception message should not be emitted.' ), 'exception failed event omits raw exception message' );
$callback_events = maa_observability_events_of_kind( $GLOBALS['maa_unit_observability_events'], 'abilities.callback.completed' );
maa_assert_same( 1, count( $callback_events ), 'failed callbacks do not add completed callback events' );

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
	'magick-ai/resolve-media-attachment-by-url',
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
	'magick-ai/build-media-derivative-batch-plan',
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
		'npcink-abilities-toolkit/wp-ops-diagnostics-detail',
		'npcink-abilities-toolkit/list-workflow-recipes',
		'npcink-abilities-toolkit/get-workflow-recipe',
		'magick-ai/get-post-context',
	'magick-ai/get-content-publishing-checklist',
	'magick-ai/get-content-inventory-health',
	'magick-ai/get-test-content-inventory',
	'magick-ai/build-test-content-cleanup-plan',
	'magick-ai/build-content-inventory-fix-plan',
	'magick-ai/search-post-meta',
	'magick-ai/get-bulk-publishing-checklist',
	'magick-ai/get-internal-link-opportunity-report',
	'magick-ai/get-site-operations-dashboard',
	'magick-ai/get-post-publish-risk-report',
	'magick-ai/get-article-publish-preflight-context',
	'magick-ai/get-content-refresh-opportunities',
	'magick-ai/get-old-article-refresh-context',
	'magick-ai/get-internal-link-graph-health',
			'magick-ai/get-media-cleanup-opportunities',
			'magick-ai/list-media-backups',
			'magick-ai/build-media-inventory-fix-plan',
		'magick-ai/build-media-reference-repair-plan',
		'magick-ai/build-media-settings-reference-repair-plan',
		'magick-ai/build-media-optimization-plan',
		'magick-ai/build-media-rename-plan',
		'magick-ai/get-taxonomy-consolidation-suggestions',
		'magick-ai/propose-post-taxonomy-terms',
		'magick-ai/get-page-structure-health',
	'magick-ai/get-seo-geo-gap-report',
	'magick-ai/get-site-style-baseline',
	'magick-ai/build-article-workflow-context',
	'magick-ai/get-publishing-calendar-context',
	'magick-ai/get-media-inventory-health',
	'magick-ai/inspect-media-asset',
	'magick-ai/build-media-derivative-cloud-request',
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
	'magick-ai/patch-setting-value',
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
	'magick-ai/optimize-media-asset',
		'magick-ai/replace-media-file',
		'magick-ai/restore-media-backup',
		'magick-ai/rename-media-file',
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
$smoke_wp = file_get_contents( __DIR__ . '/smoke-wp.php' );
maa_assert_true( is_string( $smoke_wp ) && false !== strpos( $smoke_wp, 'register_shutdown_function' ), 'WordPress smoke runs fixture cleanup on shutdown' );
maa_assert_true( is_string( $smoke_wp ) && false !== strpos( $smoke_wp, 'magick_ai_abilities_smoke_register_post_fixture' ), 'WordPress smoke registers post fixtures for cleanup' );
maa_assert_true( is_string( $smoke_wp ) && false !== strpos( $smoke_wp, 'magick_ai_abilities_smoke_register_comment_fixture' ), 'WordPress smoke registers comment fixtures for cleanup' );
maa_assert_true( is_string( $smoke_wp ) && false !== strpos( $smoke_wp, 'magick_ai_abilities_smoke_register_attachment_fixture' ), 'WordPress smoke registers media fixtures for cleanup' );
maa_assert_true( is_string( $smoke_wp ) && false !== strpos( $smoke_wp, '_magick_ai_abilities_smoke_fixture_run_id' ), 'WordPress smoke tags media fixtures with a run id' );
maa_assert_true( is_string( $smoke_wp ) && false !== strpos( $smoke_wp, 'magick_ai_abilities_smoke_known_media_fixture_leak_ids' ), 'WordPress smoke detects reserved-prefix media leaks' );
maa_assert_true( is_string( $smoke_wp ) && false !== strpos( $smoke_wp, 'magick_ai_abilities_smoke_register_term_fixture' ), 'WordPress smoke registers taxonomy term fixtures for cleanup' );
maa_assert_true( is_string( $smoke_wp ) && false !== strpos( $smoke_wp, 'wp_delete_post' ), 'WordPress smoke permanently deletes post fixtures' );
maa_assert_true( is_string( $smoke_wp ) && false !== strpos( $smoke_wp, 'wp_delete_comment' ), 'WordPress smoke permanently deletes comment fixtures' );
maa_assert_true( is_string( $smoke_wp ) && false !== strpos( $smoke_wp, 'wp_delete_attachment' ), 'WordPress smoke permanently deletes media fixtures' );
maa_assert_true( is_string( $smoke_wp ) && false !== strpos( $smoke_wp, 'wp_delete_term' ), 'WordPress smoke deletes taxonomy term fixtures' );
maa_assert_true( is_string( $smoke_wp ) && false !== strpos( $smoke_wp, 'Smoke media fixture is deleted after smoke.' ), 'WordPress smoke asserts media fixtures are gone at the end' );
maa_assert_true( is_string( $smoke_wp ) && false !== strpos( $smoke_wp, 'Smoke leaves no registered or reserved-prefix media fixtures behind.' ), 'WordPress smoke asserts no reserved-prefix media fixtures remain at the end' );
$core_consumer_example = file_get_contents( __DIR__ . '/../examples/core-governance-consumer.php' );
maa_assert_true( is_string( $core_consumer_example ) && false !== strpos( $core_consumer_example, 'magick_ai_abilities_get_registered' ), 'core governance consumer example uses ability discovery' );
maa_assert_true( is_string( $core_consumer_example ) && false !== strpos( $core_consumer_example, "'ability_id' => \$ability_id" ), 'core governance consumer example prepares a real ability proposal payload' );
maa_assert_true( isset( $package_categories->all()['magick-ai-data'] ), 'core read package registers the legacy magick-ai-data category for compatibility' );
maa_assert_true( isset( $package_categories->all()['magick-ai-pages'] ), 'core read package registers the legacy magick-ai-pages category for compatibility' );
maa_assert_true( isset( $package_categories->all()['magick-ai-comments'] ), 'core comment package registers the standalone comments category' );
maa_assert_true( isset( $package_categories->all()['magick-ai-write'] ), 'core write package registers the legacy magick-ai-write category for compatibility' );
maa_assert_true( isset( $package_categories->all()['npcink-abilities-toolkit-diagnostics'] ), 'core read package registers the standalone diagnostics category' );
maa_assert_true( isset( $package_abilities['npcink-abilities-toolkit/wp-diagnostics-summary'] ), 'core read package owns standalone wp-diagnostics-summary ability' );
maa_assert_same( 'npcink-abilities-toolkit-diagnostics', $package_abilities['npcink-abilities-toolkit/wp-diagnostics-summary']['category'], 'wp-diagnostics-summary uses standalone diagnostics category' );
maa_assert_true( isset( $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail'] ), 'core read package owns standalone wp-ops-diagnostics-detail ability' );
maa_assert_same( 'npcink-abilities-toolkit-diagnostics', $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['category'], 'wp-ops-diagnostics-detail uses standalone diagnostics category' );
maa_assert_true( false !== strpos( $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['description'] ?? '', 'plugin' ), 'ops diagnostics description mentions plugin details' );
maa_assert_same( 50, $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['max_cron_events']['maximum'] ?? null, 'ops diagnostics bounds returned cron events' );
maa_assert_same( false, $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['include_log_contents']['default'] ?? null, 'ops diagnostics does not include log contents by default' );
maa_assert_true( ! isset( $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['include_log_tail'] ), 'ops diagnostics uses one log contents control' );
maa_assert_same( false, $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['include_inactive_plugins']['default'] ?? null, 'ops diagnostics omits inactive plugin rows by default' );
maa_assert_same( true, $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['include_plugin_updates']['default'] ?? null, 'ops diagnostics includes plugin update rows by default' );
maa_assert_same( 500, $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['max_plugins_per_group']['maximum'] ?? null, 'ops diagnostics bounds plugin rows per group' );
maa_assert_same( 200, $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['tail_lines']['maximum'] ?? null, 'ops diagnostics bounds returned log tail lines' );
maa_assert_same( 10080, $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['since_minutes']['maximum'] ?? null, 'ops diagnostics bounds log since window' );
maa_assert_true( in_array( 'warning', $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['severity']['items']['enum'] ?? array(), true ), 'ops diagnostics supports log severity filtering' );
maa_assert_same( true, $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['include_integrations']['default'] ?? null, 'ops diagnostics includes integration diagnostics by default' );
maa_assert_true( in_array( 'plugins', $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['output_schema']['required'] ?? array(), true ), 'ops diagnostics output requires plugins section' );
maa_assert_true( in_array( 'current_user', $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['output_schema']['required'] ?? array(), true ), 'ops diagnostics output requires current user section' );
maa_assert_true( in_array( 'integrations', $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['output_schema']['required'] ?? array(), true ), 'ops diagnostics output requires integrations section' );
maa_assert_true( in_array( 'seo_summary', $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['output_schema']['required'] ?? array(), true ), 'ops diagnostics output requires SEO summary section' );
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
maa_assert_true( isset( $package_abilities['magick-ai/search-posts']['input_schema']['properties']['post_types'] ), 'search-posts supports multiple post types' );
maa_assert_true( isset( $package_abilities['magick-ai/search-posts']['input_schema']['properties']['statuses'] ), 'search-posts supports multiple statuses' );
maa_assert_true( isset( $package_abilities['magick-ai/search-posts']['input_schema']['properties']['taxonomy'] ), 'search-posts supports taxonomy filtering' );
maa_assert_true( isset( $package_abilities['magick-ai/search-posts']['input_schema']['properties']['modified_after'] ), 'search-posts supports modified date filtering' );
maa_assert_true( isset( $package_abilities['magick-ai/search-posts']['output_schema']['properties']['filters'] ), 'search-posts returns applied filters' );
maa_assert_true( isset( $package_abilities['magick-ai/search-posts']['output_schema']['properties']['items']['items']['properties']['matched_fields'] ), 'search-posts returns matched field hints' );
maa_assert_same( array( 'search', 'meta_keys' ), $package_abilities['magick-ai/search-post-meta']['input_schema']['required'] ?? array(), 'search-post-meta requires search and explicit meta keys' );
maa_assert_same( 10, $package_abilities['magick-ai/search-post-meta']['input_schema']['properties']['meta_keys']['maxItems'] ?? null, 'search-post-meta bounds meta key count' );
maa_assert_same( false, $package_abilities['magick-ai/search-post-meta']['input_schema']['additionalProperties'] ?? null, 'search-post-meta rejects undeclared inputs' );
maa_assert_true( isset( $package_abilities['magick-ai/search-post-meta']['output_schema']['properties']['items']['items']['properties']['matched_meta_keys'] ), 'search-post-meta returns matched meta keys' );
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
maa_assert_same( 200, $package_abilities['magick-ai/build-test-content-cleanup-plan']['input_schema']['properties']['max_actions']['maximum'] ?? null, 'test content cleanup plan bounds planned actions to Adapter batch execution limit' );
maa_assert_same( true, $package_abilities['magick-ai/build-test-content-cleanup-plan']['input_schema']['properties']['include_posts']['default'] ?? null, 'test content cleanup plan exposes include_posts control' );
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
maa_assert_same( false, $package_abilities['magick-ai/build-media-inventory-fix-plan']['input_schema']['properties']['include_trash_parent_media']['default'] ?? null, 'media inventory fix plan keeps trash-parent delete opt-in disabled by default' );
maa_assert_same( false, $package_abilities['magick-ai/build-media-inventory-fix-plan']['input_schema']['properties']['include_unattached_test_media']['default'] ?? null, 'media inventory fix plan keeps parentless test-media delete opt-in disabled by default' );
maa_assert_same( array( 'media.read' ), $package_abilities['magick-ai/build-media-inventory-fix-plan']['required_scopes'] ?? array(), 'media inventory fix plan remains a read-scope planning ability' );
maa_assert_true( ! empty( $package_abilities['magick-ai/build-media-inventory-fix-plan']['agent_usage']['when_to_use'] ), 'media inventory fix plan exposes agent usage guidance' );
maa_assert_true( ! empty( $package_abilities['magick-ai/build-media-inventory-fix-plan']['agent_usage']['stopping_points'] ), 'media inventory fix plan exposes agent stopping points' );
maa_assert_true( isset( $package_abilities['magick-ai/build-media-optimization-plan'] ), 'build-media-optimization-plan is registered as a read-only planning ability' );
maa_assert_same( array( 'media.read' ), $package_abilities['magick-ai/build-media-optimization-plan']['required_scopes'] ?? array(), 'media optimization plan remains a read-scope planning ability' );
maa_assert_same( array( 'attachment_id', 'media_details_input', 'derivative_artifact' ), $package_abilities['magick-ai/build-media-optimization-plan']['input_schema']['required'] ?? array(), 'media optimization plan requires metadata and artifact evidence' );
maa_assert_true( isset( $package_abilities['magick-ai/build-media-rename-plan'] ), 'build-media-rename-plan is registered as a read-only planning ability' );
maa_assert_same( array( 'media.read', 'post.read' ), $package_abilities['magick-ai/build-media-rename-plan']['required_scopes'] ?? array(), 'media rename plan reads media and post references' );
maa_assert_same( array( 'attachment_id', 'target_file_name' ), $package_abilities['magick-ai/build-media-rename-plan']['input_schema']['required'] ?? array(), 'media rename plan requires attachment and target filename' );
maa_assert_same( array( 'owned', 'ai_generated', 'stock', 'external', 'test' ), $package_abilities['magick-ai/update-media-details']['input_schema']['properties']['source_type']['enum'] ?? array(), 'update-media-details accepts canonical media source_type values' );
maa_assert_same( 'external', $package_abilities['magick-ai/upload-media-from-url']['input_schema']['properties']['source_type']['default'] ?? '', 'upload-media-from-url defaults remote imports to external source type' );
maa_assert_true( isset( $package_abilities['magick-ai/upload-media-from-url']['input_schema']['properties']['file_name'] ), 'upload-media-from-url accepts an approved custom media file name' );
maa_assert_same( array( 'webp', 'jpeg', 'png' ), $package_abilities['magick-ai/optimize-media-asset']['input_schema']['properties']['preferred_format']['enum'] ?? array(), 'optimize-media-asset exposes bounded derivative formats' );
maa_assert_same( 82, $package_abilities['magick-ai/optimize-media-asset']['input_schema']['properties']['quality']['default'] ?? null, 'optimize-media-asset defaults to quality 82' );
maa_assert_true( ! isset( $package_abilities['magick-ai/replace-media-file']['input_schema']['properties']['mode'] ), 'replace-media-file does not expose media restore modes' );
maa_assert_same( 'magick-ai-backup', $package_abilities['magick-ai/replace-media-file']['input_schema']['properties']['backup_suffix']['default'] ?? '', 'replace-media-file defaults to explicit Magick backup suffix' );
maa_assert_true( isset( $package_abilities['magick-ai/replace-media-file']['output_schema']['properties']['content_reference_repairs'] ), 'replace-media-file exposes post content reference repair preview evidence' );
maa_assert_true( isset( $package_abilities['magick-ai/list-media-backups'] ), 'list-media-backups is registered as a read-only media history ability' );
maa_assert_same( array( 'attachment_id' ), $package_abilities['magick-ai/list-media-backups']['input_schema']['required'] ?? array(), 'list-media-backups requires attachment id' );
maa_assert_true( isset( $package_abilities['magick-ai/restore-media-backup'] ), 'restore-media-backup is registered as a governed write ability' );
maa_assert_same( array( 'attachment_id', 'backup_id' ), $package_abilities['magick-ai/restore-media-backup']['input_schema']['required'] ?? array(), 'restore-media-backup requires attachment and backup id' );
maa_assert_true( isset( $package_abilities['magick-ai/rename-media-file'] ), 'rename-media-file is registered as a local write ability' );
maa_assert_same( array( 'attachment_id', 'target_file_name' ), $package_abilities['magick-ai/rename-media-file']['input_schema']['required'] ?? array(), 'rename-media-file requires attachment and target filename' );
maa_assert_same( array( 'fail', 'unique' ), $package_abilities['magick-ai/rename-media-file']['input_schema']['properties']['conflict_mode']['enum'] ?? array(), 'rename-media-file exposes bounded conflict modes' );
maa_assert_same( 'magick-ai-rename-backup', $package_abilities['magick-ai/rename-media-file']['input_schema']['properties']['backup_suffix']['default'] ?? '', 'rename-media-file defaults to explicit rename backup suffix' );
maa_assert_true( isset( $package_abilities['magick-ai/adopt-cloud-media-derivative'] ), 'adopt-cloud-media-derivative is registered as a local write ability' );
maa_assert_same( 'magick-ai-cloud-backup', $package_abilities['magick-ai/adopt-cloud-media-derivative']['input_schema']['properties']['backup_suffix']['default'] ?? '', 'adopt-cloud-media-derivative defaults to explicit Cloud backup suffix' );
maa_assert_true( isset( $package_abilities['magick-ai/adopt-cloud-media-derivative']['input_schema']['properties']['file_name'] ), 'adopt-cloud-media-derivative accepts an approved custom derivative file name' );
maa_assert_true( isset( $package_abilities['magick-ai/adopt-cloud-media-derivative']['output_schema']['properties']['proposed_filename'] ) && isset( $package_abilities['magick-ai/adopt-cloud-media-derivative']['output_schema']['properties']['filename_policy'] ), 'adopt-cloud-media-derivative exposes filename proposal evidence in its output schema' );
maa_assert_true( isset( $package_abilities['magick-ai/adopt-cloud-media-derivative']['output_schema']['properties']['content_reference_repairs'] ), 'adopt-cloud-media-derivative exposes post content reference repair preview evidence' );
maa_assert_same( array( 'attachment_id', 'derivative_artifact' ), $package_abilities['magick-ai/adopt-cloud-media-derivative']['input_schema']['required'] ?? array(), 'adopt-cloud-media-derivative requires attachment and artifact evidence' );
maa_assert_true( isset( $package_abilities['magick-ai/build-media-reference-repair-plan'] ), 'build-media-reference-repair-plan is registered as a read-only planning ability' );
maa_assert_same( array( 'attachment_id' ), $package_abilities['magick-ai/build-media-reference-repair-plan']['input_schema']['required'] ?? array(), 'build-media-reference-repair-plan requires attachment id' );
maa_assert_true( isset( $package_abilities['magick-ai/build-media-settings-reference-repair-plan'] ), 'build-media-settings-reference-repair-plan is registered as a read-only planning ability' );
maa_assert_same( array( 'attachment_id' ), $package_abilities['magick-ai/build-media-settings-reference-repair-plan']['input_schema']['required'] ?? array(), 'build-media-settings-reference-repair-plan requires attachment id' );
maa_assert_same( array( 'svg', 'gif', 'ico', 'pdf' ), $package_abilities['magick-ai/build-media-settings-reference-repair-plan']['input_schema']['properties']['excluded_formats']['default'] ?? array(), 'media settings reference repair defaults excluded formats' );
maa_assert_true( isset( $package_abilities['magick-ai/patch-setting-value'] ), 'patch-setting-value is registered as a local write ability' );
maa_assert_same( array( 'target_type', 'target_name', 'operations' ), $package_abilities['magick-ai/patch-setting-value']['input_schema']['required'] ?? array(), 'patch-setting-value requires a setting target and operations' );
maa_assert_true( isset( $package_abilities['magick-ai/resolve-media-attachment-by-url'] ), 'resolve-media-attachment-by-url is registered as a read-only media resolver' );
maa_assert_same( array( 'media.read' ), $package_abilities['magick-ai/resolve-media-attachment-by-url']['required_scopes'] ?? array(), 'resolve-media-attachment-by-url remains a read-scope ability' );
maa_assert_same( array( 'url' ), $package_abilities['magick-ai/resolve-media-attachment-by-url']['input_schema']['required'] ?? array(), 'resolve-media-attachment-by-url requires a URL' );
maa_assert_same( 20, $package_abilities['magick-ai/resolve-media-attachment-by-url']['input_schema']['properties']['max_candidates']['maximum'] ?? null, 'resolve-media-attachment-by-url bounds candidates to 20' );
maa_assert_true( ! isset( $package_abilities['magick-ai/resolve-media-attachment-by-url']['input_schema']['properties']['commit'] ), 'resolve-media-attachment-by-url does not expose a commit control' );
maa_assert_true( ! isset( $package_abilities['magick-ai/resolve-media-attachment-by-url']['input_schema']['properties']['dry_run'] ), 'resolve-media-attachment-by-url does not expose write dry_run control' );
maa_assert_same( 1920, $package_abilities['magick-ai/inspect-media-asset']['input_schema']['properties']['target_max_width']['default'] ?? null, 'inspect-media-asset defaults to a 1920px max width target' );
maa_assert_same( array( 'webp', 'avif', 'original' ), $package_abilities['magick-ai/inspect-media-asset']['input_schema']['properties']['preferred_format']['enum'] ?? array(), 'inspect-media-asset exposes bounded preferred output formats' );
maa_assert_same( array( 'media.read' ), $package_abilities['magick-ai/build-media-derivative-cloud-request']['required_scopes'] ?? array(), 'media derivative cloud request remains a read-scope planning ability' );
maa_assert_same( array( 'attachment_id' ), $package_abilities['magick-ai/build-media-derivative-cloud-request']['input_schema']['required'] ?? array(), 'media derivative cloud request requires an attachment id' );
maa_assert_same( array( 'webp', 'avif', 'jpeg', 'png', 'original' ), $package_abilities['magick-ai/build-media-derivative-cloud-request']['input_schema']['properties']['preferred_format']['enum'] ?? array(), 'media derivative cloud request exposes bounded preferred output formats' );
maa_assert_same( 82, $package_abilities['magick-ai/build-media-derivative-cloud-request']['input_schema']['properties']['quality']['default'] ?? null, 'media derivative cloud request defaults to quality 82' );
maa_assert_same( array( 'image', 'text' ), $package_abilities['magick-ai/build-media-derivative-cloud-request']['input_schema']['properties']['watermark']['properties']['type']['enum'] ?? array(), 'media derivative cloud request supports image and text watermark plans' );
maa_assert_same( array( 'top_left', 'top_right', 'bottom_left', 'bottom_right', 'center' ), $package_abilities['magick-ai/build-media-derivative-cloud-request']['input_schema']['properties']['watermark']['properties']['position']['enum'] ?? array(), 'media derivative cloud request exposes bounded watermark positions' );
maa_assert_same( 0.75, $package_abilities['magick-ai/build-media-derivative-cloud-request']['input_schema']['properties']['watermark']['properties']['opacity']['default'] ?? null, 'media derivative cloud request defaults watermark opacity' );
maa_assert_same( 18, $package_abilities['magick-ai/build-media-derivative-cloud-request']['input_schema']['properties']['watermark']['properties']['scale_percent']['default'] ?? null, 'media derivative cloud request defaults watermark scale' );
maa_assert_same( 'AI', $package_abilities['magick-ai/build-media-derivative-cloud-request']['input_schema']['properties']['watermark']['properties']['text']['default'] ?? null, 'media derivative cloud request defaults text watermark content' );
maa_assert_same( 48, $package_abilities['magick-ai/build-media-derivative-cloud-request']['input_schema']['properties']['watermark']['properties']['font_size']['default'] ?? null, 'media derivative cloud request defaults text watermark font size' );
maa_assert_true( isset( $package_abilities['magick-ai/build-media-derivative-batch-plan'] ), 'media derivative batch plan is registered as a read-only planning ability' );
maa_assert_same( array( 'media.read' ), $package_abilities['magick-ai/build-media-derivative-batch-plan']['required_scopes'] ?? array(), 'media derivative batch plan remains a read-scope planning ability' );
maa_assert_same( array( 'webp', 'avif', 'jpeg', 'png', 'original' ), $package_abilities['magick-ai/build-media-derivative-batch-plan']['input_schema']['properties']['target_format']['enum'] ?? array(), 'media derivative batch plan exposes bounded target formats' );
maa_assert_same( 50, $package_abilities['magick-ai/build-media-derivative-batch-plan']['input_schema']['properties']['max_items']['maximum'] ?? null, 'media derivative batch plan bounds candidates to 50 items' );
maa_assert_true( ! isset( $package_abilities['magick-ai/build-media-derivative-batch-plan']['input_schema']['properties']['commit'] ), 'media derivative batch plan does not expose a commit control' );
maa_assert_true( ! isset( $package_abilities['magick-ai/build-media-derivative-batch-plan']['input_schema']['properties']['dry_run'] ), 'media derivative batch plan does not expose write dry_run control' );
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
	maa_assert_same( false, $package_abilities['npcink-abilities-toolkit/wp-diagnostics-summary']['project_to_magick_catalog'], 'standalone diagnostics ability does not project into Magick AI by default' );
	maa_assert_same( false, $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['project_to_magick_catalog'], 'standalone ops diagnostics ability does not project into Magick AI by default' );
	maa_assert_same( false, $package_abilities['npcink-abilities-toolkit/list-workflow-recipes']['project_to_magick_catalog'], 'workflow recipe discovery ability does not project into Magick AI by default' );
	maa_assert_same( 'wordpress_diagnostics', $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['meta']['magick_ai_abilities']['pack'] ?? '', 'ops diagnostics detail is classified as WordPress diagnostics' );
	maa_assert_same( 'npcink-abilities-toolkit-workflows', $package_abilities['npcink-abilities-toolkit/list-workflow-recipes']['category'], 'workflow recipe discovery uses standalone workflow category' );
	maa_assert_same( 'workflow_definitions', $package_abilities['npcink-abilities-toolkit/list-workflow-recipes']['meta']['magick_ai_abilities']['pack'] ?? '', 'workflow recipe discovery is classified as workflow definitions' );
maa_assert_same( 'core_wordpress_read', $package_abilities['magick-ai/site-info']['meta']['magick_ai_abilities']['pack'] ?? '', 'site-info is classified as a core WordPress read ability' );
maa_assert_same( 'content_operations', $package_abilities['magick-ai/get-site-operations-dashboard']['meta']['magick_ai_abilities']['pack'] ?? '', 'site operations dashboard is classified outside core WordPress reads' );
maa_assert_same( 'content_operations', $package_abilities['magick-ai/build-content-inventory-fix-plan']['meta']['magick_ai_abilities']['pack'] ?? '', 'content inventory fix plan is classified as content operations' );
maa_assert_same( 'media_governance', $package_abilities['magick-ai/build-media-inventory-fix-plan']['meta']['magick_ai_abilities']['pack'] ?? '', 'media inventory fix plan is classified as media governance' );
maa_assert_same( 'taxonomy_governance', $package_abilities['magick-ai/propose-post-taxonomy-terms']['meta']['magick_ai_abilities']['pack'] ?? '', 'post taxonomy proposal is classified as taxonomy governance' );
maa_assert_same( 'comment_queue_context', $package_abilities['magick-ai/get-comment-queue-health']['meta']['magick_ai_abilities']['pack'] ?? '', 'comment queue health is classified as a comment queue helper' );
	$core_read_definition_ids = array_keys( $core_read_package->definitions() );
	maa_assert_same( 'magick-ai/site-info', $core_read_definition_ids[0] ?? '', 'core read definitions keep site-info first after provider split' );
	maa_assert_same( 'npcink-abilities-toolkit/wp-diagnostics-summary', $core_read_definition_ids[1] ?? '', 'core read definitions keep diagnostics second after provider split' );
	maa_assert_same( 'npcink-abilities-toolkit/wp-ops-diagnostics-detail', $core_read_definition_ids[2] ?? '', 'core read definitions keep ops diagnostics after diagnostics summary' );
	maa_assert_same( 'npcink-abilities-toolkit/list-workflow-recipes', $core_read_definition_ids[3] ?? '', 'core read definitions keep workflow list after diagnostics' );
	maa_assert_same( 'npcink-abilities-toolkit/get-workflow-recipe', $core_read_definition_ids[4] ?? '', 'core read definitions keep workflow get after workflow list' );
	maa_assert_same( 'magick-ai/list-post-types', $core_read_definition_ids[5] ?? '', 'core read definitions keep post types after workflow definitions' );
		maa_assert_same( 'magick-ai/list-media', $core_read_definition_ids[7] ?? '', 'core read definitions keep media governance order after provider split' );
		maa_assert_same( 'magick-ai/resolve-media-attachment-by-url', $core_read_definition_ids[8] ?? '', 'core read definitions keep media URL resolver near media inventory' );
		maa_assert_true( false !== array_search( 'magick-ai/list-media-backups', $core_read_definition_ids, true ), 'core read definitions include media backup history discovery' );
		maa_assert_same( 'magick-ai/resolve-url-to-post', $core_read_definition_ids[82] ?? '', 'core read definitions keep URL resolver order after provider split' );
		maa_assert_same( 'magick-ai/list-post-revisions', $core_read_definition_ids[84] ?? '', 'core read definitions keep revision list last after provider split' );
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
	maa_assert_true( ! isset( $filtered_read_abilities['npcink-abilities-toolkit/wp-diagnostics-summary'] ), 'core read pack filter removes diagnostics helper ability' );
	maa_assert_true( ! isset( $filtered_read_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail'] ), 'core read pack filter removes ops diagnostics helper ability' );
	maa_assert_true( ! isset( $filtered_read_abilities['npcink-abilities-toolkit/list-workflow-recipes'] ), 'core read pack filter removes workflow definition discovery ability' );
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
maa_assert_same( 'ai_generated', $media_assets['data']['items'][0]['source_type'] ?? '', 'build-media-seo-assets maps generated images to ai_generated source type' );
maa_assert_same( 'AI-generated by site operator', $media_assets['data']['items'][0]['attribution_text'] ?? '', 'build-media-seo-assets adds generated image attribution default' );
maa_assert_same( 'Generated asset for this site', $media_assets['data']['items'][0]['copyright_notice'] ?? '', 'build-media-seo-assets adds generated image copyright default' );
maa_assert_same( 'public_free', $media_assets['data']['items'][1]['image_origin'] ?? '', 'build-media-seo-assets infers public-free provider origin' );
maa_assert_same( 'stock', $media_assets['data']['items'][1]['source_type'] ?? '', 'build-media-seo-assets maps public-free provider images to stock source type' );
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
maa_assert_same( 'owned', $optimized_media['data']['assets'][0]['suggestions']['source_type'] ?? '', 'optimize-media-metadata includes source_type in media detail suggestions' );
maa_assert_same( 'Owned asset for this site', $optimized_media['data']['assets'][0]['suggestions']['copyright_notice'] ?? '', 'optimize-media-metadata adds owned media copyright default' );
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
$GLOBALS['maa_unit_style_posts'][81] = (object) array(
	'ID'           => 81,
	'post_title'   => 'Core Plan Bridge Content Candidate',
	'post_status'  => 'draft',
	'post_type'    => 'post',
	'post_excerpt' => '',
	'post_content' => 'Temporary plan bridge fixture content for local validation.',
	'post_name'    => 'core-plan-bridge-content-candidate',
	'post_author'  => 7,
	'post_modified' => '2026-05-30 10:30:00',
);
$default_test_inventory = $core_read_package->get_test_content_inventory(
	array(
		'per_page' => 10,
	)
);
$default_plan_bridge_match = null;
foreach ( (array) ( $default_test_inventory['data']['posts']['items'] ?? array() ) as $default_test_inventory_item ) {
	if ( 81 === (int) ( $default_test_inventory_item['post_id'] ?? 0 ) ) {
		$default_plan_bridge_match = $default_test_inventory_item;
		break;
	}
}
maa_assert_same( true, $default_test_inventory['data']['detected'] ?? null, 'get-test-content-inventory detects Core Plan Bridge fixtures by default' );
maa_assert_same( 'core plan bridge content candidate', $default_plan_bridge_match['matched_pattern'] ?? '', 'get-test-content-inventory includes Core Plan Bridge fixture patterns by default' );
$cleanup_plan = $core_read_package->build_test_content_cleanup_plan(
	array(
		'patterns'    => array( 'Core Governance' ),
		'max_actions' => 5,
	)
);
maa_assert_same( true, $cleanup_plan['success'] ?? null, 'build-test-content-cleanup-plan returns a success envelope' );
maa_assert_same( 'batch', $cleanup_plan['data']['proposal_mode'] ?? '', 'test content cleanup plan requests batch proposal intake' );
maa_assert_same( true, $cleanup_plan['data']['batch_approval'] ?? null, 'test content cleanup plan requests one approval for the generated action batch' );
maa_assert_same( 'magick-ai/trash-post', $cleanup_plan['data']['write_actions'][0]['target_ability_id'] ?? '', 'test content cleanup plan reuses trash-post' );
maa_assert_same( false, $cleanup_plan['data']['write_actions'][0]['commit_execution'] ?? null, 'test content cleanup plan does not execute commits' );
$default_cleanup_plan = $core_read_package->build_test_content_cleanup_plan(
	array(
		'max_actions' => 5,
	)
);
$default_cleanup_plan_bridge_action = null;
foreach ( (array) ( $default_cleanup_plan['data']['write_actions'] ?? array() ) as $default_cleanup_action ) {
	if ( 81 === (int) ( $default_cleanup_action['input']['post_id'] ?? 0 ) ) {
		$default_cleanup_plan_bridge_action = $default_cleanup_action;
		break;
	}
}
maa_assert_same( 'magick-ai/trash-post', $default_cleanup_plan_bridge_action['target_ability_id'] ?? '', 'test content cleanup plan includes Core Plan Bridge fixture posts by default' );
$terms_only_cleanup_plan = $core_read_package->build_test_content_cleanup_plan(
	array(
		'include_posts'    => false,
		'include_terms'    => false,
		'include_comments' => false,
		'max_actions'      => 5,
	)
);
maa_assert_same( 0, count( (array) ( $terms_only_cleanup_plan['data']['preview']['posts'] ?? array() ) ), 'test content cleanup plan honors include_posts=false' );
maa_assert_same( 0, count( (array) ( $terms_only_cleanup_plan['data']['write_actions'] ?? array() ) ), 'test content cleanup plan does not generate post actions when include_posts=false' );
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
	'post_parent'    => 0,
	'post_mime_type' => 'image/jpeg',
);
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
	update_post_meta( 79, '_wp_attached_file', '2026/06/workflow-diagram-image.jpg' );
	$GLOBALS['maa_unit_upload_basedir'] = sys_get_temp_dir() . '/magick-ai-abilities-media-' . getmypid();
	$workflow_media_path = $GLOBALS['maa_unit_upload_basedir'] . '/2026/06/workflow-diagram-image.jpg';
	if ( ! is_dir( dirname( $workflow_media_path ) ) ) {
		mkdir( dirname( $workflow_media_path ), 0755, true );
	}
	file_put_contents( $workflow_media_path, 'original-jpeg-bytes' );
	$media_url_resolution = $core_read_package->resolve_media_attachment_by_url(
		array(
			'url' => 'https://example.test/wp-content/uploads/2026/06/workflow-diagram-image.jpg',
		)
);
maa_assert_same( true, $media_url_resolution['success'] ?? null, 'resolve-media-attachment-by-url returns a success envelope for exact uploads URLs' );
maa_assert_same( true, $media_url_resolution['data']['readonly'] ?? null, 'resolve-media-attachment-by-url is read-only' );
maa_assert_same( 'resolved', $media_url_resolution['data']['match_status'] ?? '', 'resolve-media-attachment-by-url resolves one exact attachment match' );
maa_assert_same( 79, $media_url_resolution['data']['attachment_id'] ?? 0, 'resolve-media-attachment-by-url returns the matched attachment id' );
maa_assert_same( false, $media_url_resolution['data']['boundary']['wordpress_write_included'] ?? null, 'resolve-media-attachment-by-url does not write WordPress data' );
$media_size_url_resolution = $core_read_package->resolve_media_attachment_by_url(
	array(
		'url' => 'https://example.test/wp-content/uploads/2026/06/workflow-diagram-image-300x162.jpg',
	)
);
maa_assert_same( true, $media_size_url_resolution['success'] ?? null, 'resolve-media-attachment-by-url returns a success envelope for metadata size URLs' );
maa_assert_same( 79, $media_size_url_resolution['data']['attachment_id'] ?? 0, 'resolve-media-attachment-by-url resolves a metadata size URL to the parent attachment' );
maa_assert_same( 'metadata_size_file', $media_size_url_resolution['data']['candidates'][0]['match_type'] ?? '', 'resolve-media-attachment-by-url records size variant evidence' );
$external_media_url_resolution = $core_read_package->resolve_media_attachment_by_url(
	array(
		'url' => 'https://cdn.example.invalid/wp-content/uploads/2026/06/workflow-diagram-image.jpg',
	)
);
maa_assert_true( is_wp_error( $external_media_url_resolution ) && 'magick_ai_abilities_media_url_external' === $external_media_url_resolution->get_error_code(), 'resolve-media-attachment-by-url rejects external uploads-looking URLs' );
$media_inspection = $core_read_package->inspect_media_asset(
	array(
		'attachment_id'              => 79,
		'target_max_width'           => 1920,
		'large_file_threshold_bytes' => 524288,
		'preferred_format'           => 'webp',
	)
);
maa_assert_same( true, $media_inspection['success'] ?? null, 'inspect-media-asset returns a success envelope' );
maa_assert_same( 'jpeg', $media_inspection['data']['source_format'] ?? '', 'inspect-media-asset resolves JPEG source format' );
maa_assert_same( true, $media_inspection['data']['format_plan']['should_convert'] ?? null, 'inspect-media-asset recommends conversion for legacy JPEG' );
maa_assert_same( true, $media_inspection['data']['format_plan']['should_resize'] ?? null, 'inspect-media-asset recommends resizing over-wide images' );
maa_assert_same( true, $media_inspection['data']['format_plan']['should_compress'] ?? null, 'inspect-media-asset recommends compression for large images' );
maa_assert_same( 'webp', $media_inspection['data']['format_plan']['recommended_format'] ?? '', 'inspect-media-asset recommends WebP by default' );
maa_assert_same( '2026/06/workflow-diagram-image.jpg', $media_inspection['data']['current_relative_file'] ?? '', 'inspect-media-asset returns current relative file for guarded writes' );
maa_assert_same( true, $media_inspection['data']['content_hashes']['available'] ?? null, 'inspect-media-asset returns available content hashes when the file is readable' );
maa_assert_same( md5( 'original-jpeg-bytes' ), $media_inspection['data']['content_hashes']['md5'] ?? '', 'inspect-media-asset returns current file MD5' );
maa_assert_same( hash( 'sha256', 'original-jpeg-bytes' ), $media_inspection['data']['content_hashes']['sha256'] ?? '', 'inspect-media-asset returns current file SHA-256' );
$media_cloud_request = $core_read_package->build_media_derivative_cloud_request(
	array(
		'attachment_id'              => 79,
		'target_max_width'           => 1920,
		'large_file_threshold_bytes' => 524288,
		'preferred_format'           => 'webp',
		'quality'                    => 82,
	)
);
maa_assert_same( true, $media_cloud_request['success'] ?? null, 'build-media-derivative-cloud-request returns a success envelope' );
maa_assert_same( true, $media_cloud_request['data']['readonly'] ?? null, 'media derivative cloud request is read-only' );
maa_assert_same( 'media_derivative_cloud_request.v1', $media_cloud_request['data']['request_contract_version'] ?? '', 'media derivative cloud request exposes a versioned contract' );
maa_assert_same( 'generate_optimized_media_derivative', $media_cloud_request['data']['cloud_job_payload']['job_type'] ?? '', 'media derivative cloud request targets derivative generation' );
maa_assert_same( 'webp', $media_cloud_request['data']['cloud_job_payload']['target_format'] ?? '', 'media derivative cloud request exposes Cloud target format' );
maa_assert_same( 1920, $media_cloud_request['data']['cloud_job_payload']['max_width'] ?? 0, 'media derivative cloud request exposes Cloud max width' );
maa_assert_same( 82, $media_cloud_request['data']['cloud_job_payload']['quality'] ?? 0, 'media derivative cloud request exposes Cloud quality' );
maa_assert_same( 'webp', $media_cloud_request['data']['cloud_job_payload']['requested_derivative']['format'] ?? '', 'media derivative cloud request preserves preferred format' );
maa_assert_same( 1920, $media_cloud_request['data']['cloud_job_payload']['requested_derivative']['max_width'] ?? 0, 'media derivative cloud request preserves target max width' );
maa_assert_same( true, $media_cloud_request['data']['cloud_execution']['source_upload_required'] ?? null, 'media derivative cloud request requires host-provided source upload' );
maa_assert_same( false, $media_cloud_request['data']['cloud_execution']['credentials_included'] ?? null, 'media derivative cloud request does not include credentials' );
maa_assert_same( false, $media_cloud_request['data']['cloud_execution']['authorization_included'] ?? null, 'media derivative cloud request does not include authorization headers' );
maa_assert_same( false, $media_cloud_request['data']['cloud_execution']['signed_headers_included'] ?? null, 'media derivative cloud request does not include signed headers' );
maa_assert_same( 'local_wordpress_host', $media_cloud_request['data']['local_adoption']['final_write_owner'] ?? '', 'media derivative cloud request leaves final writes local' );
maa_assert_same( false, $media_cloud_request['data']['local_adoption']['wordpress_write_included'] ?? null, 'media derivative cloud request does not write WordPress' );
$media_cloud_request_with_watermark = $core_read_package->build_media_derivative_cloud_request(
	array(
		'attachment_id'              => 79,
		'target_max_width'           => 1600,
		'large_file_threshold_bytes' => 524288,
		'preferred_format'           => 'png',
		'quality'                    => 90,
		'watermark'                  => array(
			'type'          => 'image',
			'artifact_id'   => 'artifact_logo_123',
			'position'      => 'top_right',
			'opacity'       => 0.5,
			'scale_percent' => 22,
			'margin_px'     => 16,
		),
	)
);
maa_assert_same( true, $media_cloud_request_with_watermark['success'] ?? null, 'build-media-derivative-cloud-request accepts optional image watermark plans' );
maa_assert_same( 'png', $media_cloud_request_with_watermark['data']['cloud_job_payload']['target_format'] ?? '', 'watermarked media derivative request exposes Cloud target format' );
maa_assert_same( 'image', $media_cloud_request_with_watermark['data']['cloud_job_payload']['watermark']['type'] ?? '', 'watermarked media derivative request preserves watermark type' );
maa_assert_same( 'artifact_logo_123', $media_cloud_request_with_watermark['data']['cloud_job_payload']['watermark']['artifact_id'] ?? '', 'watermarked media derivative request preserves watermark artifact reference' );
maa_assert_same( 'top_right', $media_cloud_request_with_watermark['data']['cloud_job_payload']['watermark']['position'] ?? '', 'watermarked media derivative request preserves watermark position' );
maa_assert_same( 0.5, $media_cloud_request_with_watermark['data']['cloud_job_payload']['watermark']['opacity'] ?? null, 'watermarked media derivative request preserves watermark opacity' );
maa_assert_same( 22, $media_cloud_request_with_watermark['data']['cloud_job_payload']['watermark']['scale_percent'] ?? 0, 'watermarked media derivative request preserves watermark scale' );
maa_assert_same( false, $media_cloud_request_with_watermark['data']['local_adoption']['wordpress_write_included'] ?? null, 'watermarked media derivative request still does not write WordPress' );
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
maa_assert_same( true, $media_cloud_request_with_text_watermark['success'] ?? null, 'build-media-derivative-cloud-request accepts optional text watermark plans' );
maa_assert_same( 'text', $media_cloud_request_with_text_watermark['data']['cloud_job_payload']['watermark']['type'] ?? '', 'text watermarked media derivative request preserves watermark type' );
maa_assert_same( 'AI', $media_cloud_request_with_text_watermark['data']['cloud_job_payload']['watermark']['text'] ?? '', 'text watermarked media derivative request normalizes plain text content' );
maa_assert_same( 'top_right', $media_cloud_request_with_text_watermark['data']['cloud_job_payload']['watermark']['position'] ?? '', 'text watermarked media derivative request preserves watermark position' );
maa_assert_same( 48, $media_cloud_request_with_text_watermark['data']['cloud_job_payload']['watermark']['font_size'] ?? 0, 'text watermarked media derivative request preserves bounded font size' );
maa_assert_same( '#FFFFFF', $media_cloud_request_with_text_watermark['data']['cloud_job_payload']['watermark']['color'] ?? '', 'text watermarked media derivative request normalizes hex color' );
maa_assert_same( 'rgba(0,0,0,0.35)', $media_cloud_request_with_text_watermark['data']['cloud_job_payload']['watermark']['background'] ?? '', 'text watermarked media derivative request preserves bounded rgba background' );
maa_assert_true( ! isset( $media_cloud_request_with_text_watermark['data']['cloud_job_payload']['watermark']['artifact_id'] ), 'text watermarked media derivative request does not require a watermark artifact id' );
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
		'derivative_artifact'           => array(
			'artifact_id'    => 'art_cloud_media_123',
			'expires_at'     => gmdate( 'c', time() + 600 ),
			'mime_type'      => 'image/webp',
			'format'         => 'webp',
			'width'          => 1600,
			'height'         => 862,
			'filesize_bytes' => 210000,
		),
		'expected_current_mime_type'    => 'image/jpeg',
		'expected_derivative_mime_type' => 'image/webp',
	)
);
maa_assert_same( true, $media_optimization_plan['success'] ?? null, 'build-media-optimization-plan returns a success envelope' );
maa_assert_same( 'media_optimization_plan', $media_optimization_plan['data']['artifact_type'] ?? '', 'media optimization plan declares Core media optimization artifact type' );
maa_assert_same( 'batch', $media_optimization_plan['data']['proposal_mode'] ?? '', 'media optimization plan requests batch proposal mode' );
maa_assert_same( true, $media_optimization_plan['data']['batch_approval'] ?? null, 'media optimization plan requests one Core approval' );
maa_assert_same( 2, count( (array) ( $media_optimization_plan['data']['write_actions'] ?? array() ) ), 'media optimization plan includes metadata and derivative actions' );
maa_assert_same( 'magick-ai/update-media-details', $media_optimization_plan['data']['write_actions'][0]['target_ability_id'] ?? '', 'media optimization plan starts with metadata action' );
maa_assert_same( 'magick-ai/adopt-cloud-media-derivative', $media_optimization_plan['data']['write_actions'][1]['target_ability_id'] ?? '', 'media optimization plan includes Cloud derivative adoption action' );
maa_assert_same( false, $media_optimization_plan['data']['commit_execution'] ?? null, 'media optimization plan does not execute commits' );
maa_assert_same( true, $media_optimization_plan['meta']['readonly'] ?? null, 'media optimization plan remains read-only' );
maa_assert_true( false !== strpos( $core_write_package_source, 'magick_ai_abilities_cloud_media_derivative_artifact_download' ), 'adopt-cloud-media-derivative exposes a bounded artifact download filter for integration smoke tests' );
$GLOBALS['maa_unit_style_posts'][88] = (object) array(
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
maa_assert_same( true, $media_rename_plan['success'] ?? null, 'build-media-rename-plan returns a success envelope' );
maa_assert_same( 'media_rename_plan', $media_rename_plan['data']['artifact_type'] ?? '', 'media rename plan declares Core media rename artifact type' );
maa_assert_same( 'batch', $media_rename_plan['data']['proposal_mode'] ?? '', 'media rename plan batches rename with exact content reference updates' );
maa_assert_same( true, $media_rename_plan['data']['batch_approval'] ?? null, 'media rename plan requests one approval for rename and reference updates' );
maa_assert_same( 2, count( (array) ( $media_rename_plan['data']['write_actions'] ?? array() ) ), 'media rename plan includes rename and post reference patch actions' );
maa_assert_same( 'magick-ai/rename-media-file', $media_rename_plan['data']['write_actions'][0]['target_ability_id'] ?? '', 'media rename plan targets rename-media-file' );
maa_assert_same( 'workflow-diagram-image-reviewed.jpg', $media_rename_plan['data']['write_actions'][0]['input']['target_file_name'] ?? '', 'media rename plan appends the current extension when omitted' );
maa_assert_same( md5( 'original-jpeg-bytes' ), $media_rename_plan['data']['write_actions'][0]['input']['expected_current_md5'] ?? '', 'media rename plan carries MD5 guard into write action' );
maa_assert_same( 'magick-ai/patch-post-content', $media_rename_plan['data']['write_actions'][1]['target_ability_id'] ?? '', 'media rename plan patches post content references after rename' );
maa_assert_same( 88, $media_rename_plan['data']['write_actions'][1]['input']['post_id'] ?? 0, 'media rename plan targets the post that embeds the renamed image URL' );
maa_assert_true( false !== strpos( (string) ( $media_rename_plan['data']['write_actions'][1]['input']['operations'][0]['find'] ?? '' ), 'workflow-diagram-image.jpg' ), 'media rename plan finds the old image URL in post content' );
maa_assert_true( false !== strpos( (string) ( $media_rename_plan['data']['write_actions'][1]['input']['operations'][0]['replace'] ?? '' ), 'workflow-diagram-image-reviewed.jpg' ), 'media rename plan replaces post content with the renamed image URL' );
maa_assert_same( 1, $media_rename_plan['data']['reference_repair']['action_count'] ?? 0, 'media rename plan reports one exact reference repair action' );
maa_assert_same( false, $media_rename_plan['data']['commit_execution'] ?? null, 'media rename plan does not execute commits' );
maa_assert_same( true, $media_rename_plan['meta']['readonly'] ?? null, 'media rename plan remains read-only' );
unset( $GLOBALS['maa_unit_style_posts'][88] );
$media_rename_plan_invalid_hash = $core_read_package->build_media_rename_plan(
	array(
		'attachment_id'        => 79,
		'target_file_name'     => 'workflow-diagram-image-reviewed.jpg',
		'expected_current_md5' => 'not-a-valid-md5',
	)
);
maa_assert_true( is_wp_error( $media_rename_plan_invalid_hash ) && 'magick_ai_abilities_expected_md5_invalid' === $media_rename_plan_invalid_hash->get_error_code(), 'media rename plan rejects invalid expected MD5 values' );
$GLOBALS['maa_unit_style_posts'][84] = (object) array(
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
$GLOBALS['maa_unit_style_posts'][85] = (object) array(
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
$GLOBALS['maa_unit_style_posts'][86] = (object) array(
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
$media_derivative_batch_plan = $core_read_package->build_media_derivative_batch_plan(
	array(
		'date_from'     => '2026-04-01',
		'date_to'       => '2026-04-30 23:59:59',
		'target_format' => 'png',
		'max_items'     => 10,
	)
);
maa_assert_same( true, $media_derivative_batch_plan['success'] ?? null, 'media derivative batch plan returns a success envelope' );
maa_assert_same( true, $media_derivative_batch_plan['data']['readonly'] ?? null, 'media derivative batch plan is read-only' );
maa_assert_same( 'dry_run', $media_derivative_batch_plan['data']['plan_mode'] ?? '', 'media derivative batch plan returns a dry-run plan mode' );
maa_assert_same( false, $media_derivative_batch_plan['data']['commit_execution'] ?? null, 'media derivative batch plan does not execute commits' );
maa_assert_same( true, $media_derivative_batch_plan['data']['requires_approval'] ?? null, 'media derivative batch plan requires approval before adoption' );
maa_assert_same( 1, $media_derivative_batch_plan['data']['summary']['candidate_count'] ?? 0, 'media derivative batch plan selects one April JPEG candidate for PNG conversion' );
maa_assert_same( 84, $media_derivative_batch_plan['data']['candidates'][0]['attachment_id'] ?? 0, 'media derivative batch plan candidate comes from the April date range' );
maa_assert_same( 'png', $media_derivative_batch_plan['data']['candidates'][0]['cloud_request_input']['preferred_format'] ?? '', 'media derivative batch plan prepares PNG single-image request input' );
maa_assert_same( 'magick-ai/build-media-derivative-cloud-request', $media_derivative_batch_plan['data']['candidates'][0]['cloud_request_ability'] ?? '', 'media derivative batch plan points to the existing single-image cloud request ability' );
maa_assert_same( 'already_target_format', $media_derivative_batch_plan['data']['skipped'][0]['reason'] ?? '', 'media derivative batch plan skips images already in the target format' );
maa_assert_array_omits_keys( $media_derivative_batch_plan['data'], array( 'write_actions', 'wordpress_write_decision', 'approval_decision', 'commit' ), 'media derivative batch plan output' );
$media_derivative_batch_plan_bounded = $core_read_package->build_media_derivative_batch_plan(
	array(
		'attachment_ids' => array( 84, 86 ),
		'target_format'  => 'png',
		'max_items'      => 1,
	)
);
maa_assert_same( 1, $media_derivative_batch_plan_bounded['data']['summary']['candidate_count'] ?? 0, 'media derivative batch plan enforces max_items' );
$media_derivative_batch_plan_text_watermark = $core_read_package->build_media_derivative_batch_plan(
	array(
		'attachment_ids' => array( 84 ),
		'target_format'  => 'webp',
		'max_items'      => 1,
		'watermark'      => array(
			'type'     => 'text',
			'text'     => 'AI',
			'position' => 'top_right',
		),
	)
);
maa_assert_same( true, $media_derivative_batch_plan_text_watermark['success'] ?? null, 'media derivative batch plan accepts text watermark input' );
maa_assert_same( 'text', $media_derivative_batch_plan_text_watermark['data']['candidates'][0]['cloud_request_input']['watermark']['type'] ?? '', 'media derivative batch plan carries text watermark requests into candidate cloud inputs' );
maa_assert_same( 'AI', $media_derivative_batch_plan_text_watermark['data']['candidates'][0]['cloud_request_input']['watermark']['text'] ?? '', 'media derivative batch plan carries text watermark content into candidate cloud inputs' );
maa_assert_true( ! isset( $media_derivative_batch_plan_text_watermark['data']['candidates'][0]['cloud_request_input']['watermark']['artifact_id'] ), 'media derivative batch plan text watermark does not require an artifact id' );
$media_derivative_batch_plan_excluded = $core_read_package->build_media_derivative_batch_plan(
	array(
		'attachment_ids'    => array( 84 ),
		'target_format'     => 'png',
		'exclude_formats'   => array( 'jpeg' ),
	)
);
maa_assert_same( 0, $media_derivative_batch_plan_excluded['data']['summary']['candidate_count'] ?? 1, 'media derivative batch plan honors excluded source formats' );
maa_assert_same( 'source_format_excluded', $media_derivative_batch_plan_excluded['data']['skipped'][0]['reason'] ?? '', 'media derivative batch plan explains excluded source formats' );
$media_derivative_batch_plan_invalid = $core_read_package->build_media_derivative_batch_plan(
	array(
		'attachment_ids' => array( 84 ),
		'target_format'  => 'tiff',
	)
);
maa_assert_true( is_wp_error( $media_derivative_batch_plan_invalid ) && 'magick_ai_abilities_media_derivative_target_format_invalid' === $media_derivative_batch_plan_invalid->get_error_code(), 'media derivative batch plan rejects invalid target formats' );
$media_optimization_preview = $core_write_package->optimize_media_asset(
	array(
		'attachment_id'     => 79,
		'target_max_width'  => 1920,
		'preferred_format'  => 'webp',
		'quality'           => 82,
		'derivative_suffix' => 'optimized',
	)
);
maa_assert_same( true, $media_optimization_preview['dry_run'] ?? null, 'optimize-media-asset defaults to dry-run preview' );
maa_assert_same( false, $media_optimization_preview['optimized'] ?? null, 'optimize-media-asset dry-run does not generate a file' );
maa_assert_same( true, $media_optimization_preview['original_preserved'] ?? null, 'optimize-media-asset preserves original asset' );
maa_assert_same( false, $media_optimization_preview['replace_original'] ?? null, 'optimize-media-asset never replaces the original file' );
maa_assert_same( 'webp', $media_optimization_preview['derivative']['format'] ?? '', 'optimize-media-asset plans WebP derivative by default' );
maa_assert_same( 1920, $media_optimization_preview['derivative']['width'] ?? 0, 'optimize-media-asset plans bounded derivative width' );
update_post_meta(
	79,
	'_magick_ai_media_optimized_derivatives',
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
maa_assert_same( true, $media_replace_preview['dry_run'] ?? null, 'replace-media-file defaults to dry-run preview' );
maa_assert_same( false, $media_replace_preview['replaced'] ?? null, 'replace-media-file dry-run does not switch files' );
maa_assert_same( true, $media_replace_preview['original_preserved'] ?? null, 'replace-media-file keeps original backup intent in dry-run' );
maa_assert_same( '2026/06/workflow-diagram-image-optimized.webp', $media_replace_preview['after']['relative_file'] ?? '', 'replace-media-file uses recorded optimized derivative as target' );
maa_assert_true( 0 === strpos( (string) ( $media_replace_preview['backup']['relative_file'] ?? '' ), 'magick-ai-backups/2026/06/' ), 'replace-media-file plans backups in the dedicated Magick uploads backup directory' );
maa_assert_true( false !== strpos( (string) ( $media_replace_preview['backup']['relative_file'] ?? '' ), 'magick-ai-backup' ), 'replace-media-file plans a Magick backup file' );
$cloud_artifact_contents = 'cloud-webp-derivative-bytes';
$cloud_artifact_sha256 = hash( 'sha256', $cloud_artifact_contents );
$GLOBALS['maa_unit_style_posts'][89] = (object) array(
	'ID'           => 89,
	'post_title'   => 'Cloud Derivative Inline Reference',
	'post_status'  => 'publish',
	'post_type'    => 'post',
	'post_excerpt' => '',
	'post_content' => '<!-- wp:image {"id":79,"sizeSlug":"large"} --><figure class="wp-block-image size-large"><img src="https://example.test/wp-content/uploads/2026/06/workflow-diagram-image-300x162.jpg" srcset="https://example.test/wp-content/uploads/2026/06/workflow-diagram-image-300x162.jpg 300w, https://example.test/wp-content/uploads/2026/06/workflow-diagram-image.jpg 2600w" alt="Workflow diagram" class="wp-image-79" /></figure><!-- /wp:image -->',
	'post_name'    => 'cloud-derivative-inline-reference',
	'post_author'  => 7,
);
$cloud_adoption_preview = $core_write_package->adopt_cloud_media_derivative(
	array(
		'attachment_id'                 => 79,
		'derivative_artifact'           => array(
			'artifact_id'    => 'art_cloud_media_123',
			'expires_at'     => gmdate( 'c', time() + 600 ),
			'mime_type'      => 'image/webp',
			'format'         => 'webp',
			'width'          => 1600,
			'height'         => 862,
			'filesize_bytes' => strlen( $cloud_artifact_contents ),
			'checksum'       => 'sha256:' . $cloud_artifact_sha256,
		),
		'expected_current_relative_file' => '2026/06/workflow-diagram-image.jpg',
		'expected_current_mime_type'    => 'image/jpeg',
		'expected_derivative_mime_type' => 'image/webp',
	)
);
maa_assert_same( true, $cloud_adoption_preview['dry_run'] ?? null, 'adopt-cloud-media-derivative defaults to dry-run preview' );
maa_assert_same( false, $cloud_adoption_preview['replaced'] ?? null, 'adopt-cloud-media-derivative dry-run does not switch files' );
maa_assert_same( 'art_cloud_media_123', $cloud_adoption_preview['artifact']['artifact_id'] ?? '', 'adopt-cloud-media-derivative preserves artifact evidence' );
maa_assert_true( false !== strpos( (string) ( $cloud_adoption_preview['after']['relative_file'] ?? '' ), 'workflow-diagram-image-magick-ai-cloud-' ), 'adopt-cloud-media-derivative plans a local derivative filename' );
maa_assert_same( 1, $cloud_adoption_preview['content_reference_repairs']['post_count'] ?? 0, 'adopt-cloud-media-derivative previews post content reference repairs for embedded attachment URLs' );
maa_assert_true( (int) ( $cloud_adoption_preview['content_reference_repairs']['replacement_count'] ?? 0 ) >= 2, 'adopt-cloud-media-derivative preview includes old main and sized image references' );
$cloud_suggested_filename_preview = $core_write_package->adopt_cloud_media_derivative(
	array(
		'attachment_id'                 => 79,
		'derivative_artifact'           => array(
			'artifact_id'         => 'art_cloud_media_suggested',
			'expires_at'          => gmdate( 'c', time() + 600 ),
			'mime_type'           => 'image/webp',
			'format'              => 'webp',
			'width'               => 1600,
			'height'              => 862,
			'filesize_bytes'      => strlen( $cloud_artifact_contents ),
			'checksum'            => 'sha256:' . $cloud_artifact_sha256,
			'suggested_filename'  => 'cloud-suggested-file.webp',
			'filename_basis'      => array(
				'owner'                          => 'wordpress_write_ability_final',
				'strategy'                       => 'format_checksum',
				'final_sanitize_unique_required' => true,
			),
		),
		'expected_current_relative_file' => '2026/06/workflow-diagram-image.jpg',
		'expected_current_mime_type'    => 'image/jpeg',
		'expected_derivative_mime_type' => 'image/webp',
	)
);
maa_assert_same( 'cloud-suggested-file.webp', $cloud_suggested_filename_preview['proposed_filename'] ?? '', 'adopt-cloud-media-derivative adopts a sanitized Cloud suggested filename as local proposal evidence' );
maa_assert_same( 'cloud_artifact_suggestion', $cloud_suggested_filename_preview['filename_policy']['source'] ?? '', 'adopt-cloud-media-derivative marks Cloud filenames as suggestions, not write decisions' );
maa_assert_same( true, $cloud_suggested_filename_preview['filename_policy']['final_sanitize_unique_required'] ?? null, 'adopt-cloud-media-derivative requires final WordPress-side filename finalization' );
$expired_cloud_adoption = $core_write_package->adopt_cloud_media_derivative(
	array(
		'attachment_id'       => 79,
		'derivative_artifact' => array(
			'artifact_id' => 'art_expired_media_123',
			'expires_at'  => gmdate( 'c', time() - 60 ),
			'mime_type'   => 'image/webp',
			'format'      => 'webp',
		),
	)
);
maa_assert_true( is_wp_error( $expired_cloud_adoption ) && 'magick_ai_abilities_cloud_artifact_expired' === $expired_cloud_adoption->get_error_code(), 'adopt-cloud-media-derivative rejects expired artifacts' );
$GLOBALS['maa_unit_upload_basedir'] = sys_get_temp_dir() . '/magick-ai-abilities-cloud-adoption-' . getmypid();
$current_media_path = $GLOBALS['maa_unit_upload_basedir'] . '/2026/06/workflow-diagram-image.jpg';
mkdir( dirname( $current_media_path ), 0755, true );
file_put_contents( $current_media_path, 'original-jpeg-bytes' );
$GLOBALS['maa_unit_cloud_artifact_download_callback'] = static function ( array $artifact ) use ( $cloud_artifact_contents, $cloud_artifact_sha256 ) {
	return array(
		'artifact_id'    => (string) ( $artifact['artifact_id'] ?? '' ),
		'contents'       => $cloud_artifact_contents,
		'mime_type'      => 'image/webp',
		'filesize_bytes' => strlen( $cloud_artifact_contents ),
		'sha256'         => $cloud_artifact_sha256,
		'expires_at'     => (string) ( $artifact['expires_at'] ?? '' ),
	);
};
$GLOBALS['magick_ai_runtime_wp_ability_context']['context'] = array(
	'approval_commit_authorized' => true,
	'approval_id'                => 'approval-cloud-media-adoption',
);
$cloud_adoption_commit = $core_write_package->adopt_cloud_media_derivative(
	array(
		'attachment_id'                 => 79,
		'derivative_artifact'           => array(
			'artifact_id'    => 'art_cloud_media_commit',
			'expires_at'     => gmdate( 'c', time() + 600 ),
			'mime_type'      => 'image/webp',
			'format'         => 'webp',
			'width'          => 1600,
			'height'         => 862,
			'filesize_bytes' => strlen( $cloud_artifact_contents ),
			'checksum'       => 'sha256:' . $cloud_artifact_sha256,
		),
		'expected_current_relative_file' => '2026/06/workflow-diagram-image.jpg',
		'expected_current_mime_type'    => 'image/jpeg',
		'expected_derivative_mime_type' => 'image/webp',
		'file_name'                    => 'customer-approved-diagram.webp',
		'commit'                       => true,
	)
);
unset( $GLOBALS['magick_ai_runtime_wp_ability_context'], $GLOBALS['maa_unit_cloud_artifact_download_callback'] );
maa_assert_true( ! is_wp_error( $cloud_adoption_commit ), 'adopt-cloud-media-derivative commit succeeds after approval' . ( is_wp_error( $cloud_adoption_commit ) ? ': ' . $cloud_adoption_commit->get_error_code() : '' ) );
maa_assert_same( false, $cloud_adoption_commit['dry_run'] ?? null, 'adopt-cloud-media-derivative commit exits dry-run' );
maa_assert_same( true, $cloud_adoption_commit['replaced'] ?? null, 'adopt-cloud-media-derivative commit replaces the attachment pointer after approval' );
maa_assert_same( 'image/webp', get_post_mime_type( 79 ), 'adopt-cloud-media-derivative commit updates attachment MIME type' );
maa_assert_same( '2026/06/customer-approved-diagram.webp', get_post_meta( 79, '_wp_attached_file', true ), 'adopt-cloud-media-derivative commit accepts an approved custom derivative file name' );
maa_assert_same( 1, $cloud_adoption_commit['content_reference_repairs']['updated_count'] ?? 0, 'adopt-cloud-media-derivative commit updates posts that embed the attachment URL' );
maa_assert_true( false !== strpos( (string) ( $GLOBALS['maa_unit_style_posts'][89]->post_content ?? '' ), 'customer-approved-diagram.webp' ), 'adopt-cloud-media-derivative commit rewrites inline image references to the adopted WebP' );
maa_assert_true( false === strpos( (string) ( $GLOBALS['maa_unit_style_posts'][89]->post_content ?? '' ), 'workflow-diagram-image-300x162.jpg' ), 'adopt-cloud-media-derivative commit removes old sized image references from post content' );
maa_assert_same( 'customer-approved-diagram.webp', $cloud_adoption_commit['proposed_filename'] ?? '', 'adopt-cloud-media-derivative commit records the reviewed filename proposal' );
maa_assert_same( 'reviewed_input', $cloud_adoption_commit['filename_policy']['source'] ?? '', 'adopt-cloud-media-derivative commit treats explicit file_name as reviewed local input' );
maa_assert_true( is_readable( $GLOBALS['maa_unit_upload_basedir'] . '/' . get_post_meta( 79, '_wp_attached_file', true ) ), 'adopt-cloud-media-derivative commit writes the local derivative file' );
maa_assert_true( 0 === strpos( (string) ( $cloud_adoption_commit['backup']['relative_file'] ?? '' ), 'magick-ai-backups/2026/06/' ), 'adopt-cloud-media-derivative commit stores backup outside the public month media directory' );
maa_assert_true( false !== strpos( (string) ( $cloud_adoption_commit['backup']['relative_file'] ?? '' ), 'magick-ai-cloud-backup' ), 'adopt-cloud-media-derivative commit records a backup file' );
maa_assert_true( is_readable( $GLOBALS['maa_unit_upload_basedir'] . '/' . (string) ( $cloud_adoption_commit['backup']['relative_file'] ?? '' ) ), 'adopt-cloud-media-derivative commit writes the local backup file' );
$GLOBALS['maa_unit_style_posts'][87] = (object) array(
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
$rename_media_path = $GLOBALS['maa_unit_upload_basedir'] . '/2026/06/rename-media-fixture.jpg';
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
maa_assert_same( true, $rename_preview['dry_run'] ?? null, 'rename-media-file defaults to dry-run preview' );
maa_assert_same( false, $rename_preview['renamed'] ?? null, 'rename-media-file dry-run does not move files' );
maa_assert_same( '2026/06/rename-media-fixture-reviewed.jpg', $rename_preview['after']['relative_file'] ?? '', 'rename-media-file dry-run plans target relative file' );
maa_assert_same( md5( 'rename-jpeg-bytes' ), $rename_preview['before']['content_hashes']['md5'] ?? '', 'rename-media-file dry-run includes current MD5 evidence' );
$rename_mismatch = $core_write_package->rename_media_file(
	array(
		'attachment_id'        => 87,
		'target_file_name'     => 'rename-media-fixture-reviewed.jpg',
		'expected_current_md5' => str_repeat( '0', 32 ),
	)
);
maa_assert_true( is_wp_error( $rename_mismatch ) && 'magick_ai_abilities_current_md5_mismatch' === $rename_mismatch->get_error_code(), 'rename-media-file rejects current hash mismatches' );
$GLOBALS['magick_ai_runtime_wp_ability_context']['context'] = array(
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
unset( $GLOBALS['magick_ai_runtime_wp_ability_context'] );
maa_assert_true( ! is_wp_error( $rename_commit ), 'rename-media-file commit succeeds after approval' . ( is_wp_error( $rename_commit ) ? ': ' . $rename_commit->get_error_code() : '' ) );
maa_assert_same( false, $rename_commit['dry_run'] ?? null, 'rename-media-file commit exits dry-run' );
maa_assert_same( true, $rename_commit['renamed'] ?? null, 'rename-media-file commit renames the attachment main file' );
maa_assert_same( '2026/06/rename-media-fixture-reviewed.jpg', get_post_meta( 87, '_wp_attached_file', true ), 'rename-media-file commit updates attached file pointer' );
maa_assert_true( ! is_readable( $GLOBALS['maa_unit_upload_basedir'] . '/2026/06/rename-media-fixture.jpg' ), 'rename-media-file commit moves the original main file' );
maa_assert_true( is_readable( $GLOBALS['maa_unit_upload_basedir'] . '/2026/06/rename-media-fixture-reviewed.jpg' ), 'rename-media-file commit writes the renamed main file' );
maa_assert_true( 0 === strpos( (string) ( $rename_commit['backup']['relative_file'] ?? '' ), 'magick-ai-backups/2026/06/' ), 'rename-media-file commit stores backup outside the public month media directory' );
maa_assert_true( is_readable( $GLOBALS['maa_unit_upload_basedir'] . '/' . (string) ( $rename_commit['backup']['relative_file'] ?? '' ) ), 'rename-media-file commit writes a rollback backup file' );
$renamed_metadata = wp_get_attachment_metadata( 87 );
maa_assert_same( '2026/06/rename-media-fixture-reviewed.jpg', $renamed_metadata['file'] ?? '', 'rename-media-file commit updates attachment metadata file' );
maa_assert_same( 'rename-media-fixture-300x200.jpg', $renamed_metadata['sizes']['medium']['file'] ?? '', 'rename-media-file commit preserves existing size metadata' );
update_post_meta( 79, '_wp_attached_file', '2026/06/workflow-diagram-image-optimized.webp' );
$GLOBALS['maa_unit_style_posts'][79]->post_mime_type = 'image/webp';
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
	'_magick_ai_media_file_replacement_history',
	array(
		array(
			'replacement_id'     => 'media_replace_unit',
			'status'             => 'active',
			'replaced_at_gmt'    => '2026-06-02T00:00:00+00:00',
			'rolled_back_at_gmt' => '',
			'before'             => array(
				'relative_file' => '2026/06/workflow-diagram-image.jpg',
				'mime_type'     => 'image/jpeg',
				'width'         => 2600,
				'height'        => 1400,
			),
			'after'              => array(
				'relative_file' => '2026/06/workflow-diagram-image-optimized.webp',
				'mime_type'     => 'image/webp',
				'width'         => 1920,
				'height'        => 1034,
			),
			'backup'             => array(
				'relative_file'  => 'magick-ai-backups/2026/06/workflow-diagram-image-magick-ai-backup-media_replace_unit.jpg',
				'mime_type'      => 'image/jpeg',
				'width'          => 2600,
				'height'         => 1400,
				'filesize_bytes' => 900000,
			),
		),
	)
);
$media_restore_backup_path = $GLOBALS['maa_unit_upload_basedir'] . '/magick-ai-backups/2026/06/workflow-diagram-image-magick-ai-backup-media_replace_unit.jpg';
if ( ! is_dir( dirname( $media_restore_backup_path ) ) ) {
	mkdir( dirname( $media_restore_backup_path ), 0755, true );
}
file_put_contents( $media_restore_backup_path, 'original-jpeg-bytes' );
$media_restore_current_path = $GLOBALS['maa_unit_upload_basedir'] . '/2026/06/workflow-diagram-image-optimized.webp';
if ( ! is_dir( dirname( $media_restore_current_path ) ) ) {
	mkdir( dirname( $media_restore_current_path ), 0755, true );
}
file_put_contents( $media_restore_current_path, 'optimized-webp-bytes' );
$media_backups = $core_read_package->list_media_backups(
	array(
		'attachment_id' => 79,
	)
);
maa_assert_same( true, $media_backups['success'] ?? null, 'list-media-backups returns a success envelope' );
maa_assert_same( 1, $media_backups['data']['summary']['backup_count'] ?? 0, 'list-media-backups returns recorded backup count' );
maa_assert_same( 'media_replace_unit', $media_backups['data']['backups'][0]['backup_id'] ?? '', 'list-media-backups exposes backup id for restore' );
maa_assert_same( true, $media_backups['data']['backups'][0]['file_exists'] ?? null, 'list-media-backups checks backup file availability' );
maa_assert_same( 'magick-ai/restore-media-backup', $media_backups['data']['backups'][0]['restore_action']['target_ability_id'] ?? '', 'list-media-backups returns restore-media-backup action metadata' );
$media_restore_preview = $core_write_package->restore_media_backup(
	array(
		'attachment_id'                  => 79,
		'backup_id'                      => 'media_replace_unit',
		'expected_current_relative_file' => '2026/06/workflow-diagram-image-optimized.webp',
	)
);
maa_assert_same( true, $media_restore_preview['dry_run'] ?? null, 'restore-media-backup defaults to dry-run preview' );
maa_assert_same( false, $media_restore_preview['restored'] ?? null, 'restore-media-backup dry-run does not switch files' );
maa_assert_same( '2026/06/workflow-diagram-image.jpg', $media_restore_preview['after']['relative_file'] ?? '', 'restore-media-backup targets the original public media path' );
maa_assert_true( false !== strpos( (string) ( $media_restore_preview['current_backup']['relative_file'] ?? '' ), 'magick-ai-restore-backup' ), 'restore-media-backup plans a backup of the current main file before restore' );
update_post_meta( 79, '_wp_attached_file', '2026/06/workflow-diagram-image.jpg' );
$GLOBALS['maa_unit_style_posts'][79]->post_mime_type = 'image/jpeg';
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
$GLOBALS['maa_unit_style_posts'][83] = (object) array(
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
maa_assert_same( true, $media_reference_repair_plan['success'] ?? null, 'build-media-reference-repair-plan returns a success envelope' );
maa_assert_same( false, $media_reference_repair_plan['data']['commit_execution'] ?? null, 'media reference repair plan does not execute commits' );
maa_assert_same( 1, $media_reference_repair_plan['data']['action_count'] ?? 0, 'media reference repair plan builds one post patch action' );
maa_assert_same( 'magick-ai/patch-post-content', $media_reference_repair_plan['data']['write_actions'][0]['target_ability_id'] ?? '', 'media reference repair plan reuses patch-post-content' );
maa_assert_same( 83, $media_reference_repair_plan['data']['write_actions'][0]['input']['post_id'] ?? 0, 'media reference repair action targets the referencing post' );
maa_assert_same( 'replace', $media_reference_repair_plan['data']['write_actions'][0]['input']['operations'][0]['op'] ?? '', 'media reference repair action uses replace operations' );
maa_assert_true( false !== strpos( (string) ( $media_reference_repair_plan['data']['write_actions'][0]['input']['operations'][0]['find'] ?? '' ), 'workflow-diagram-image.jpg' ), 'media reference repair action finds old media URL' );
maa_assert_true( false !== strpos( (string) ( $media_reference_repair_plan['data']['write_actions'][0]['input']['operations'][0]['replace'] ?? '' ), 'workflow-diagram-image-optimized.webp' ), 'media reference repair action replaces with new media URL' );
maa_assert_same( 'old_sized_variant_reference_detected', $media_reference_repair_plan['data']['manual_review'][0]['reason'] ?? '', 'media reference repair plan sends old size variants to manual review' );
$GLOBALS['maa_unit_options']['theme_builder_media_setting'] = array(
	'hero' => array(
		'image' => 'https://example.test/wp-content/uploads/2026/06/workflow-diagram-image.jpg',
	),
);
$GLOBALS['maa_unit_theme_mods']['header_image'] = '/wp-content/uploads/2026/06/workflow-diagram-image.jpg';
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
maa_assert_same( true, $media_settings_reference_plan['success'] ?? null, 'build-media-settings-reference-repair-plan returns a success envelope' );
maa_assert_same( false, $media_settings_reference_plan['data']['commit_execution'] ?? null, 'media settings reference repair plan does not execute commits' );
maa_assert_same( 2, $media_settings_reference_plan['data']['action_count'] ?? 0, 'media settings reference repair plan builds option and theme mod patch actions' );
maa_assert_same( 'magick-ai/patch-setting-value', $media_settings_reference_plan['data']['write_actions'][0]['target_ability_id'] ?? '', 'media settings reference repair plan reuses patch-setting-value' );
maa_assert_same( 'theme_builder_media_setting', $media_settings_reference_plan['data']['write_actions'][0]['input']['target_name'] ?? '', 'media settings reference repair targets the option name' );
maa_assert_same( 'header_image', $media_settings_reference_plan['data']['write_actions'][1]['input']['target_name'] ?? '', 'media settings reference repair targets the theme mod name' );
$media_settings_excluded_plan = $core_read_package->build_media_settings_reference_repair_plan(
	array(
		'attachment_id'     => 79,
		'replacement_id'    => 'media_replace_unit',
		'option_names'      => array( 'theme_builder_media_setting' ),
		'include_theme_mods' => false,
		'excluded_formats'  => array( 'jpg' ),
	)
);
maa_assert_same( true, $media_settings_excluded_plan['success'] ?? null, 'media settings reference repair accepts excluded format policy' );
maa_assert_same( 0, $media_settings_excluded_plan['data']['action_count'] ?? 1, 'media settings reference repair does not build actions for excluded source formats' );
maa_assert_same( 'source_format_excluded', $media_settings_excluded_plan['data']['manual_review'][0]['reason'] ?? '', 'media settings reference repair sends excluded formats to manual review' );
$patch_setting_preview = $core_write_package->patch_setting_value(
	array(
		'target_type' => 'option',
		'target_name' => 'theme_builder_media_setting',
		'operations'  => $media_settings_reference_plan['data']['write_actions'][0]['input']['operations'] ?? array(),
		'dry_run'     => true,
	)
);
maa_assert_same( true, $patch_setting_preview['dry_run'] ?? null, 'patch-setting-value returns a governed dry-run preview' );
maa_assert_same( 1, $patch_setting_preview['patch_preview'][0]['applied'] ?? null, 'patch-setting-value reports applied operation count' );
$GLOBALS['magick_ai_runtime_wp_ability_context'] = array( 'context' => array( 'approval_commit_authorized' => true ) );
$patch_setting_commit = $core_write_package->patch_setting_value(
	array(
		'target_type' => 'theme_mod',
		'target_name' => 'header_image',
		'operations'  => $media_settings_reference_plan['data']['write_actions'][1]['input']['operations'] ?? array(),
		'commit'      => true,
	)
);
unset( $GLOBALS['magick_ai_runtime_wp_ability_context'] );
maa_assert_same( false, $patch_setting_commit['dry_run'] ?? null, 'patch-setting-value commit exits dry-run after approval' );
maa_assert_true( false !== strpos( (string) get_theme_mod( 'header_image', '' ), 'workflow-diagram-image-optimized.webp' ), 'patch-setting-value commits exact theme mod URL replacement' );
$media_health = $core_read_package->get_media_inventory_health(
	array(
		'mime_type' => 'image',
		'per_page'  => 5,
	)
);
maa_assert_same( true, $media_health['success'] ?? null, 'get-media-inventory-health returns a success envelope' );
maa_assert_true( (int) ( $media_health['data']['summary']['scanned_count'] ?? 0 ) >= 1, 'get-media-inventory-health scans local media rows' );
maa_assert_true( isset( $media_health['data']['issue_counts']['missing_alt'] ), 'get-media-inventory-health counts missing alt text' );
$media_health_row = maa_find_row_by_key( (array) ( $media_health['data']['items'] ?? array() ), 'attachment_id', 79 );
maa_assert_same( true, $media_health_row['format_inspection']['format_plan']['needs_attention'] ?? null, 'get-media-inventory-health includes format inspection attention state' );
maa_assert_true( in_array( 'legacy_image_format', (array) ( $media_health_row['format_inspection']['warnings'] ?? array() ), true ), 'get-media-inventory-health includes legacy format warning' );
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
		'issue_types'     => array( 'missing_alt', 'missing_caption', 'missing_description', 'format_attention', 'possibly_unattached' ),
		'article_title'   => 'Workflow automation',
		'article_excerpt' => 'Workflow automation improves repeatable editorial operations.',
		'focus_keyword'   => 'workflow',
	)
);
maa_assert_same( true, $media_fix_plan['success'] ?? null, 'build-media-inventory-fix-plan returns a success envelope' );
maa_assert_same( true, $media_fix_plan['data']['requires_approval'] ?? null, 'media inventory fix plan requires approval' );
maa_assert_same( false, $media_fix_plan['data']['commit_execution'] ?? null, 'media inventory fix plan does not execute commits' );
maa_assert_same( 'magick-ai/update-media-details', $media_fix_plan['data']['write_actions'][0]['target_ability_id'] ?? '', 'media inventory fix plan reuses update-media-details' );
maa_assert_same( 0, maa_count_plan_actions_for_ability( (array) ( $media_fix_plan['data']['write_actions'] ?? array() ), 'magick-ai/delete-media-permanently' ), 'media inventory fix plan does not map parentless media to delete actions by default' );
maa_assert_same( false, $media_fix_plan['data']['write_actions'][0]['commit_execution'] ?? null, 'media metadata plan action does not execute commits' );
maa_assert_true( isset( $media_fix_plan['data']['preview'][0]['before']['alt'] ), 'media inventory fix plan returns before preview' );
maa_assert_true( isset( $media_fix_plan['data']['preview'][0]['after_suggestion']['alt'] ), 'media inventory fix plan returns after suggestion preview' );
maa_assert_same( true, $media_fix_plan['data']['manual_review'][0]['format_plan']['should_convert'] ?? null, 'media inventory fix plan carries format inspection recommendations into manual review' );
maa_assert_same( 'legacy_image_format', $media_fix_plan['data']['manual_review'][0]['format_governance']['detected_reason'] ?? '', 'media inventory fix plan records a format attention detected reason' );
maa_assert_same( 'generate_optimized_derivative', $media_fix_plan['data']['manual_review'][0]['format_governance']['suggested_operation'] ?? '', 'media inventory fix plan suggests a lightweight future operation for format attention' );
maa_assert_same( 'magick-ai/build-media-derivative-cloud-request', $media_fix_plan['data']['manual_review'][0]['format_governance']['target_future_ability'] ?? '', 'media inventory fix plan points format attention at the read-only Cloud request planner without mapping it' );
maa_assert_same( false, $media_fix_plan['data']['manual_review'][0]['format_governance']['write_action_generated'] ?? null, 'media inventory fix plan keeps format attention read-only' );
maa_assert_same( 'high', $media_fix_plan['data']['manual_review'][0]['format_governance']['estimated_risk'] ?? '', 'media inventory fix plan marks format asset work as high risk' );
maa_assert_same( 0, maa_count_plan_actions_for_ability( (array) ( $media_fix_plan['data']['write_actions'] ?? array() ), 'magick-ai/build-media-derivative-cloud-request' ), 'media inventory fix plan does not map format attention to the Cloud request planner as a write action' );
maa_assert_same( 0, maa_count_plan_actions_for_ability( (array) ( $media_fix_plan['data']['write_actions'] ?? array() ), 'magick-ai/optimize-media-asset' ), 'media inventory fix plan does not map format attention to optimize-media-asset' );
maa_assert_same( 0, maa_count_plan_actions_for_ability( (array) ( $media_fix_plan['data']['write_actions'] ?? array() ), 'magick-ai/convert-media-format' ), 'media inventory fix plan does not map format attention to convert-media-format' );
maa_assert_same( 0, maa_count_plan_actions_for_ability( (array) ( $media_fix_plan['data']['write_actions'] ?? array() ), 'magick-ai/replace-media-file' ), 'media inventory fix plan does not map format attention to replace-media-file' );
maa_assert_same( 'magick-ai/delete-media-permanently', $media_fix_plan['data']['skipped_destructive_candidates'][0]['target_ability_id'] ?? '', 'media inventory fix plan skips destructive candidates by default' );
maa_assert_same( 'delete_candidates_not_enabled', $media_fix_plan['data']['skipped_destructive_candidates'][0]['blocked_reason'] ?? '', 'media inventory fix plan explains default destructive skip reason' );
$media_delete_plan = $core_read_package->build_media_inventory_fix_plan(
	array(
		'attachment_ids'              => array( 79 ),
		'issue_types'                 => array( 'possibly_unattached' ),
		'include_delete_candidates'   => true,
	)
);
maa_assert_same( 0, count( (array) ( $media_delete_plan['data']['write_actions'] ?? array() ) ), 'media inventory fix plan does not map delete candidates without unattached test-media opt-in' );
maa_assert_same( 'unattached_test_media_not_enabled', $media_delete_plan['data']['skipped_destructive_candidates'][0]['blocked_reason'] ?? '', 'media inventory fix plan requires explicit parentless test-media opt-in for destructive media deletes' );
$media_parentless_delete_plan = $core_read_package->build_media_inventory_fix_plan(
	array(
		'attachment_ids'              => array( 79 ),
		'issue_types'                 => array( 'possibly_unattached' ),
		'include_delete_candidates'   => true,
		'include_trash_parent_media'  => true,
	)
);
maa_assert_same( 0, count( (array) ( $media_parentless_delete_plan['data']['write_actions'] ?? array() ) ), 'media inventory fix plan does not map parentless media to delete actions' );
maa_assert_same( 'unattached_test_media_not_enabled', $media_parentless_delete_plan['data']['skipped_destructive_candidates'][0]['blocked_reason'] ?? '', 'media inventory fix plan requires explicit unattached test-media opt-in for parentless destructive media deletes' );
$GLOBALS['maa_unit_style_posts'][96] = (object) array(
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
		'include_unattached_test_media'  => true,
	)
);
maa_assert_same( 'magick-ai/delete-media-permanently', $media_parentless_test_delete_plan['data']['write_actions'][0]['target_ability_id'] ?? '', 'media inventory fix plan maps eligible parentless test media to delete action only with explicit opt-in' );
maa_assert_same( 'high', $media_parentless_test_delete_plan['data']['write_actions'][0]['risk'] ?? '', 'eligible parentless test media delete candidate is marked high risk' );
maa_assert_same( false, $media_parentless_test_delete_plan['data']['write_actions'][0]['commit_execution'] ?? null, 'eligible parentless test media delete candidate remains proposal-only' );
$GLOBALS['maa_unit_style_posts'][97] = (object) array(
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
$GLOBALS['maa_unit_style_posts'][98] = (object) array(
	'ID'           => 98,
	'post_title'   => 'Editorial Draft With Parentless Test Media',
	'post_status'  => 'draft',
	'post_type'    => 'post',
	'post_content' => '<!-- wp:image {"id":97} --><figure class="wp-block-image"><img class="wp-image-97" /></figure><!-- /wp:image -->',
	'post_name'    => 'editorial-draft-with-parentless-test-media',
	'post_author'  => 7,
	'post_parent'  => 0,
);
$referenced_parentless_test_delete_plan = $core_read_package->build_media_inventory_fix_plan(
	array(
		'attachment_ids'                 => array( 97 ),
		'issue_types'                    => array( 'possibly_unattached' ),
		'include_delete_candidates'      => true,
		'include_unattached_test_media'  => true,
	)
);
maa_assert_same( 0, count( (array) ( $referenced_parentless_test_delete_plan['data']['write_actions'] ?? array() ) ), 'media inventory fix plan blocks parentless test media referenced by live content' );
maa_assert_same( 'referenced_by_live_content', $referenced_parentless_test_delete_plan['data']['skipped_destructive_candidates'][0]['blocked_reason'] ?? '', 'media inventory fix plan reports parentless live reference policy failure' );
maa_assert_true( (int) ( $referenced_parentless_test_delete_plan['data']['skipped_destructive_candidates'][0]['policy_checks']['live_reference_count'] ?? 0 ) >= 1, 'media inventory fix plan records parentless live reference count for blocked media delete' );
$GLOBALS['maa_unit_style_posts'][99] = (object) array(
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
		'include_unattached_test_media'  => true,
	)
);
maa_assert_same( 0, count( (array) ( $parentless_production_delete_plan['data']['write_actions'] ?? array() ) ), 'media inventory fix plan blocks parentless media whose title is not test content' );
maa_assert_same( 'media_not_test_content', $parentless_production_delete_plan['data']['skipped_destructive_candidates'][0]['blocked_reason'] ?? '', 'media inventory fix plan reports parentless media test-pattern policy failure' );
$GLOBALS['maa_unit_style_posts'][91] = (object) array(
	'ID'           => 91,
	'post_title'   => 'Runtime Smoke Media Parent',
	'post_status'  => 'trash',
	'post_type'    => 'post',
	'post_content' => 'Runtime smoke parent post for media cleanup policy.',
	'post_name'    => 'runtime-smoke-media-parent',
	'post_author'  => 7,
	'post_parent'  => 0,
);
$GLOBALS['maa_unit_style_posts'][92] = (object) array(
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
maa_assert_same( 'magick-ai/delete-media-permanently', $eligible_media_delete_plan['data']['write_actions'][0]['target_ability_id'] ?? '', 'media inventory fix plan maps eligible trash-parent test media to delete action' );
maa_assert_same( 'high', $eligible_media_delete_plan['data']['write_actions'][0]['risk'] ?? '', 'eligible media delete candidate is marked high risk' );
maa_assert_same( 'trash', $eligible_media_delete_plan['data']['preview'][0]['parent_post_status'] ?? '', 'eligible media delete policy records trashed parent status in preview' );
$GLOBALS['maa_unit_style_posts'][93] = (object) array(
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
maa_assert_same( 0, count( (array) ( $blocked_media_title_plan['data']['write_actions'] ?? array() ) ), 'media inventory fix plan blocks trash-parent media whose own title is not test content' );
maa_assert_same( 'media_not_test_content', $blocked_media_title_plan['data']['skipped_destructive_candidates'][0]['blocked_reason'] ?? '', 'media inventory fix plan reports media test-pattern policy failure' );
$GLOBALS['maa_unit_style_posts'][94] = (object) array(
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
$GLOBALS['maa_unit_style_posts'][95] = (object) array(
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
maa_assert_same( 0, count( (array) ( $referenced_media_delete_plan['data']['write_actions'] ?? array() ) ), 'media inventory fix plan blocks trash-parent media referenced by live content' );
maa_assert_same( 'referenced_by_live_content', $referenced_media_delete_plan['data']['skipped_destructive_candidates'][0]['blocked_reason'] ?? '', 'media inventory fix plan reports live reference policy failure' );
maa_assert_true( (int) ( $referenced_media_delete_plan['data']['skipped_destructive_candidates'][0]['policy_checks']['live_reference_count'] ?? 0 ) >= 1, 'media inventory fix plan records live reference count for blocked media delete' );
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
maa_assert_true( ! isset( $package_catalog['npcink-abilities-toolkit_wp-diagnostics-summary'] ), 'catalog bridge does not project standalone diagnostics ability' );
maa_assert_true( ! isset( $package_catalog['npcink-abilities-toolkit_wp-ops-diagnostics-detail'] ), 'catalog bridge does not project standalone ops diagnostics ability' );

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
	'article_draft'                  => array(
		'ability_id'         => 'magick-ai/compose-article-draft-result',
		'recipe_id'          => 'workflow/wordpress_article_draft',
		'recipe_aliases'     => array( 'article_draft_v1' ),
		'required_scope'     => 'cap.text.extract',
		'required_inputs'    => array(),
		'expected_sections'  => array( 'article', 'draft', 'metadata_plan_resolution', 'review', 'handoff' ),
		'expanded_abilities' => array(
			'magick-ai/resolve-post-metadata-plan',
			'magick-ai/resolve-internal-link-targets',
			'magick-ai/build-inline-image-blocks',
			'magick-ai/build-media-seo-assets',
			'magick-ai/review-article-output-light',
			'magick-ai/compose-article-draft-result',
		),
		'handoff_kind'       => 'suggestion',
		'disallowed_default' => array( 'magick-ai/create-draft', 'magick-ai/update-post', 'magick-ai/patch-post-content', 'magick-ai/publish-post' ),
	),
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
maa_assert_same( array_keys( $expected_workflow_replay_cases ), array_keys( $workflow_replay['cases'] ), 'agent workflow replay fixture keeps the approved local recipe cases in order' );
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
	if ( isset( $expected_case['recipe_aliases'] ) ) {
		maa_assert_same( $expected_case['recipe_aliases'], $case['recipe_aliases'] ?? array(), "agent workflow replay case {$case_id} keeps recipe aliases" );
	}
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

$workflow_list = call_user_func( $package_abilities['npcink-abilities-toolkit/list-workflow-recipes']['execute_callback'], array() );
maa_assert_same( $workflow_manifest, $workflow_list, 'workflow recipe discovery ability returns provider manifest' );
$workflow_draft_alias = call_user_func( $package_abilities['npcink-abilities-toolkit/get-workflow-recipe']['execute_callback'], array( 'recipe_id' => 'article_draft_v1' ) );
maa_assert_same( $workflow_manifest['cases']['article_draft'], $workflow_draft_alias, 'workflow recipe detail ability resolves article_draft_v1 alias' );
$workflow_get = call_user_func( $package_abilities['npcink-abilities-toolkit/get-workflow-recipe']['execute_callback'], array( 'recipe_id' => 'workflow/wordpress_comment_compliance_handoff' ) );
maa_assert_same( $workflow_manifest['cases']['comment_compliance_handoff'], $workflow_get, 'workflow recipe detail ability resolves recipe id' );
$workflow_missing = call_user_func( $package_abilities['npcink-abilities-toolkit/get-workflow-recipe']['execute_callback'], array( 'recipe_id' => 'workflow/missing' ) );
maa_assert_true( is_wp_error( $workflow_missing ), 'workflow recipe detail ability fails closed for missing recipe' );

echo "OK: {$assertions} assertions\n";
