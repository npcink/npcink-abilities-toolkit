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
use Npcink_Abilities_Toolkit\Plugin;
use Npcink_Abilities_Toolkit\Registry\Ability_Registrar;
use Npcink_Abilities_Toolkit\Registry\Annotation_Normalizer;
use Npcink_Abilities_Toolkit\Registry\Category_Registrar;
use Npcink_Abilities_Toolkit\Registry\Contract_Normalizer;
use Npcink_Abilities_Toolkit\Registry\Schema_Normalizer;

$assertions = 0;
$core_write_package_source = (string) file_get_contents( dirname( __DIR__ ) . '/includes/Packages/Core_Write_Package.php' );

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

function npcink_abilities_toolkit_count_plan_actions_for_ability( array $actions, $ability_id ) {
	$count = 0;
	foreach ( $actions as $action ) {
		if ( $ability_id === (string) ( $action['target_ability_id'] ?? '' ) ) {
			++$count;
		}
	}

	return $count;
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
npcink_abilities_toolkit_assert_true( false !== strpos( $admin_test_page, 'PARENT_MENU_SLUG' ), 'admin test page knows the shared Npcink AI parent slug' );
npcink_abilities_toolkit_assert_true( false !== strpos( $admin_test_page, "const PARENT_MENU_SLUG    = 'npcink-ai';" ), 'admin test page targets the shared Npcink AI parent menu.' );
npcink_abilities_toolkit_assert_true( false !== strpos( $admin_test_page, "const MENU_SLUG           = 'npcink-abilities-toolkit';" ), 'admin test page uses the canonical Abilities admin slug' );
npcink_abilities_toolkit_assert_true( false !== strpos( $admin_test_page, '$hook_suffixes' ), 'admin test page stores real WordPress hook suffixes for asset loading.' );
npcink_abilities_toolkit_assert_true( false !== strpos( $admin_test_page, 'Next actions' ), 'admin overview provides a clear post-install next action area.' );
npcink_abilities_toolkit_assert_true( false !== strpos( $admin_test_page, 'Open Catalog' ) && false !== strpos( $admin_test_page, 'Open REST Checks' ), 'admin overview links post-install users to catalog and REST checks.' );
npcink_abilities_toolkit_assert_true( false !== strpos( $admin_test_page, 'get_callback_issue_count' ), 'admin overview summarizes callback readiness before catalog inspection.' );
npcink_abilities_toolkit_assert_true( false !== strpos( $admin_test_page, 'add_submenu_page' ), 'admin test page can attach to the shared Npcink AI menu' );
npcink_abilities_toolkit_assert_true( false !== strpos( $admin_test_page, 'add_management_page' ), 'admin test page keeps the standalone Tools fallback' );
npcink_abilities_toolkit_assert_true( false !== strpos( $admin_test_page, "__( 'Npcink Abilities Toolkit', 'npcink-abilities-toolkit' ),\n\t\t\t\t__( 'Abilities', 'npcink-abilities-toolkit' )," ), 'admin test page registers the requested page and submenu titles when attached' );
$old_admin_slug = 'npcink-abilities-toolkit-' . 'test';
npcink_abilities_toolkit_assert_true( false === strpos( $admin_test_page, $old_admin_slug ), 'admin test page no longer uses the old test admin slug' );
foreach (
	array(
		'Confirm package readiness',
		'Package',
		'Callback issues',
		'Next actions',
		'Connect a host',
		'Final write approval stays with the host runtime',
		'Registered Ability Catalog',
		'Connection values',
		'Copy Abilities Endpoint',
		'Read-only ability checks',
		'bounded admin input',
		'omits plugin rows, current-user details, updates, and cron details',
		'npcink_abilities_toolkit_readonly_check',
		'data-npcink-abilities-toolkit-readonly-check',
		'Run Site Info',
		'Run Diagnostics Summary',
		'Catalog export',
		'REST discovery checks',
		'Ability ID export',
		'render_status_summary',
		'render_ability_catalog',
	) as $required
) {
	npcink_abilities_toolkit_assert_true( false !== strpos( $admin_test_page, $required ), 'admin test page keeps the focused ability status surface: ' . $required );
}

$admin_surface_standard = file_get_contents( __DIR__ . '/../docs/admin-surface-standard.md' );
foreach (
	array(
		'ability-package status and REST-check surface',
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
	npcink_abilities_toolkit_assert_true( false !== strpos( $admin_surface_standard, $required ), 'admin surface standard documents ability page boundary: ' . $required );
}

$main_plugin_header = file_get_contents( __DIR__ . '/../npcink-abilities-toolkit.php' );
npcink_abilities_toolkit_assert_true( false !== strpos( $main_plugin_header, 'Requires at least: 7.0' ), 'main plugin header requires WordPress 7.0' );
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
		'workflow/wordpress_article_draft',
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

$categories = new Category_Registrar();
$registrar = new Ability_Registrar( $categories, $contract_normalizer );
npcink_abilities_toolkit_assert_true(
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
npcink_abilities_toolkit_assert_true(
	! $registrar->add_readonly(
		'invalid',
		array(
			'label' => 'Invalid',
		)
	),
	'registrar rejects unnamespaced ability'
);

add_filter(
	'npcink_abilities_toolkit_enabled_packages',
	static function ( $packages ) {
		$packages['core_write']            = false;
		$packages['core_destructive']      = false;
		$packages['core_comment']          = false;
		$packages['npcink_catalog_bridge'] = false;
		$packages['admin_test_page']       = false;
		$packages['read_cache_hooks']      = false;

		return $packages;
	}
);
$plugin = Plugin::instance();
$plugin->boot();
$plugin_abilities = $plugin->abilities()->all();
npcink_abilities_toolkit_assert_true( isset( $plugin_abilities['npcink-abilities-toolkit/site-info'] ), 'package filter keeps enabled core read package' );
npcink_abilities_toolkit_assert_true( ! isset( $plugin_abilities['npcink-abilities-toolkit/create-draft'] ), 'package filter disables core write package' );
npcink_abilities_toolkit_assert_true( ! isset( $plugin_abilities['npcink-abilities-toolkit/delete-post-permanently'] ), 'package filter disables core destructive package' );
npcink_abilities_toolkit_assert_true( ! isset( $plugin_abilities['npcink-abilities-toolkit/get-comment-queue-health'] ), 'package filter disables core comment package' );
remove_all_filters( 'npcink_abilities_toolkit_enabled_packages' );

npcink_abilities_toolkit_assert_true(
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
npcink_abilities_toolkit_assert_same( 'write_host', $host_write['mode'], 'host-governed write mode is preserved' );
npcink_abilities_toolkit_assert_same( 'write', $host_write['risk_level'], 'host-governed write risk is write' );
npcink_abilities_toolkit_assert_same( true, $host_write['requires_confirm'], 'host-governed write requires confirmation' );

$GLOBALS['npcink_abilities_toolkit_unit_options'] = array();
$GLOBALS['npcink_abilities_toolkit_unit_transients'] = array();
$GLOBALS['npcink_abilities_toolkit_unit_registered_abilities'] = array();
$GLOBALS['npcink_abilities_toolkit_unit_observability_events'] = array();
add_action(
	'npcink_abilities_toolkit_observability_event',
	static function ( $event ) {
		$GLOBALS['npcink_abilities_toolkit_unit_observability_events'][] = $event;
	}
);

$observability_categories = new Category_Registrar();
$observability_registrar = new Ability_Registrar( $observability_categories, $contract_normalizer );
npcink_abilities_toolkit_assert_true(
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
npcink_abilities_toolkit_assert_true(
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
npcink_abilities_toolkit_assert_true(
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
$catalog_events = npcink_abilities_toolkit_observability_events_of_kind( $GLOBALS['npcink_abilities_toolkit_unit_observability_events'], 'abilities.catalog.changed' );
npcink_abilities_toolkit_assert_same( 1, count( $catalog_events ), 'first bootstrap emits one catalog changed event' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit', $catalog_events[0]['plugin_slug'] ?? '', 'catalog event carries plugin slug' );
npcink_abilities_toolkit_assert_same( 'ok', $catalog_events[0]['status'] ?? '', 'catalog event status is ok' );
npcink_abilities_toolkit_assert_same( 'local', $catalog_events[0]['source'] ?? '', 'catalog event source remains local' );
npcink_abilities_toolkit_assert_same( 3, $catalog_events[0]['ability_count'] ?? 0, 'catalog event carries ability count' );
npcink_abilities_toolkit_assert_same( $first_hash, $catalog_events[0]['catalog_hash'] ?? '', 'catalog event carries current catalog hash' );
npcink_abilities_toolkit_assert_event_has_safe_event_id( $catalog_events[0], 'catalog_', 'catalog event' );
npcink_abilities_toolkit_assert_observability_event_is_metadata_only( $catalog_events[0], 'catalog event payload' );

$GLOBALS['npcink_abilities_toolkit_unit_registered_abilities'] = array();
$observability_registrar->register_with_wordpress();
$catalog_events = npcink_abilities_toolkit_observability_events_of_kind( $GLOBALS['npcink_abilities_toolkit_unit_observability_events'], 'abilities.catalog.changed' );
npcink_abilities_toolkit_assert_same( 1, count( $catalog_events ), 'repeated bootstrap does not repeat catalog changed event for the same hash' );
npcink_abilities_toolkit_assert_same( 0, count( npcink_abilities_toolkit_observability_events_of_kind( $GLOBALS['npcink_abilities_toolkit_unit_observability_events'], 'abilities.ability.registered' ) ), 'ability add no longer emits per-ability registered events' );
npcink_abilities_toolkit_assert_same( 0, count( npcink_abilities_toolkit_observability_events_of_kind( $GLOBALS['npcink_abilities_toolkit_unit_observability_events'], 'abilities.ability.wordpress_registered' ) ), 'WordPress registration no longer emits per-ability events' );

npcink_abilities_toolkit_assert_true(
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
npcink_abilities_toolkit_assert_true( $first_hash !== $changed_hash, 'catalog hash changes when ability catalog changes' );
$GLOBALS['npcink_abilities_toolkit_unit_registered_abilities'] = array();
$observability_registrar->register_with_wordpress();
$catalog_events = npcink_abilities_toolkit_observability_events_of_kind( $GLOBALS['npcink_abilities_toolkit_unit_observability_events'], 'abilities.catalog.changed' );
npcink_abilities_toolkit_assert_same( 2, count( $catalog_events ), 'changed catalog hash emits one additional catalog changed event' );
npcink_abilities_toolkit_assert_same( $changed_hash, $catalog_events[1]['catalog_hash'] ?? '', 'changed catalog event carries new hash' );
npcink_abilities_toolkit_assert_same( $first_hash, $catalog_events[1]['previous_catalog_hash'] ?? '', 'changed catalog event carries previous hash' );
$GLOBALS['npcink_abilities_toolkit_unit_registered_abilities'] = array();
$observability_registrar->register_with_wordpress();
$catalog_events = npcink_abilities_toolkit_observability_events_of_kind( $GLOBALS['npcink_abilities_toolkit_unit_observability_events'], 'abilities.catalog.changed' );
npcink_abilities_toolkit_assert_same( 2, count( $catalog_events ), 'same changed catalog hash is rate limited after first emit' );

$GLOBALS['npcink_abilities_toolkit_unit_options'][ Ability_Registrar::CATALOG_STATE_OPTION ] = array(
	'catalog_hash'   => $changed_hash,
	'emitted_at'     => '2026-06-01T00:00:00+00:00',
	'plugin_version' => '0.0.0-old',
	'reason'         => 'bootstrap',
);
$old_version_rate_limit_key = Ability_Registrar::CATALOG_RATE_LIMIT_PREFIX . substr( hash( 'sha256', $changed_hash . '|0.0.0-old' ), 0, 40 );
$GLOBALS['npcink_abilities_toolkit_unit_transients'][ $old_version_rate_limit_key ] = '2026-06-01T00:00:00+00:00';
$GLOBALS['npcink_abilities_toolkit_unit_registered_abilities'] = array();
$observability_registrar->register_with_wordpress();
$catalog_events = npcink_abilities_toolkit_observability_events_of_kind( $GLOBALS['npcink_abilities_toolkit_unit_observability_events'], 'abilities.catalog.changed' );
npcink_abilities_toolkit_assert_same( 3, count( $catalog_events ), 'version change emits catalog changed even when old hash transient exists' );
npcink_abilities_toolkit_assert_same( $changed_hash, $catalog_events[2]['catalog_hash'] ?? '', 'version-change catalog event keeps unchanged hash' );
npcink_abilities_toolkit_assert_true( ! isset( $catalog_events[2]['previous_catalog_hash'] ), 'version-change same-hash catalog event omits previous hash' );
npcink_abilities_toolkit_assert_same( NPCINK_ABILITIES_TOOLKIT_VERSION, $GLOBALS['npcink_abilities_toolkit_unit_options'][ Ability_Registrar::CATALOG_STATE_OPTION ]['plugin_version'] ?? '', 'version-change emit updates catalog state version' );

$callback = $GLOBALS['npcink_abilities_toolkit_unit_registered_abilities']['acme/observable-summary']['execute_callback'] ?? null;
npcink_abilities_toolkit_assert_true( is_callable( $callback ), 'registered ability keeps callable observed execute callback' );
$callback_result = call_user_func( $callback, array( 'raw_callback_input' => 'super-secret-callback-input' ) );
npcink_abilities_toolkit_assert_same( array( 'ok' => true ), $callback_result, 'observed callback returns original result' );
$callback_events = npcink_abilities_toolkit_observability_events_of_kind( $GLOBALS['npcink_abilities_toolkit_unit_observability_events'], 'abilities.callback.completed' );
npcink_abilities_toolkit_assert_same( 1, count( $callback_events ), 'callback execution still emits behavior observability event' );
npcink_abilities_toolkit_assert_same( 'acme/observable-summary', $callback_events[0]['ability_id'] ?? '', 'callback event carries ability id' );
npcink_abilities_toolkit_assert_same( 'ok', $callback_events[0]['status'] ?? '', 'callback event carries successful status' );
npcink_abilities_toolkit_assert_event_has_safe_event_id( $callback_events[0], 'ability_cb_', 'callback completed event' );
npcink_abilities_toolkit_assert_observability_event_is_metadata_only( $callback_events[0], 'callback completed event payload' );
npcink_abilities_toolkit_assert_true( false === strpos( wp_json_encode( $callback_events[0] ), 'super-secret-callback-input' ), 'callback completed event omits raw callback input values' );

$wp_error_callback = $GLOBALS['npcink_abilities_toolkit_unit_registered_abilities']['acme/observable-wp-error']['execute_callback'] ?? null;
npcink_abilities_toolkit_assert_true( is_callable( $wp_error_callback ), 'registered WP_Error ability keeps callable observed execute callback' );
$wp_error_result = call_user_func( $wp_error_callback, array( 'payload_json' => 'super-secret-callback-input' ) );
npcink_abilities_toolkit_assert_true( is_wp_error( $wp_error_result ), 'observed WP_Error callback returns original error result' );
$failed_callback_events = npcink_abilities_toolkit_observability_events_of_kind( $GLOBALS['npcink_abilities_toolkit_unit_observability_events'], 'abilities.callback.failed' );
npcink_abilities_toolkit_assert_same( 1, count( $failed_callback_events ), 'WP_Error callback emits one failed callback event' );
npcink_abilities_toolkit_assert_same( 'acme/observable-wp-error', $failed_callback_events[0]['ability_id'] ?? '', 'WP_Error failed event carries ability id' );
npcink_abilities_toolkit_assert_same( 'error', $failed_callback_events[0]['status'] ?? '', 'WP_Error failed event carries error status' );
npcink_abilities_toolkit_assert_same( 'abilities.callback_error', $failed_callback_events[0]['error_code'] ?? '', 'WP_Error failed event uses stable error code' );
npcink_abilities_toolkit_assert_same( 'acme_callback_failed', $failed_callback_events[0]['status_detail'] ?? '', 'WP_Error failed event carries redacted status detail' );
npcink_abilities_toolkit_assert_event_has_safe_event_id( $failed_callback_events[0], 'ability_cb_', 'WP_Error failed event' );
npcink_abilities_toolkit_assert_observability_event_is_metadata_only( $failed_callback_events[0], 'WP_Error failed event payload' );
npcink_abilities_toolkit_assert_true( false === strpos( wp_json_encode( $failed_callback_events[0] ), 'super-secret-callback-input' ), 'WP_Error failed event omits raw callback input values' );
npcink_abilities_toolkit_assert_true( false === strpos( wp_json_encode( $failed_callback_events[0] ), 'Raw error message should not be emitted.' ), 'WP_Error failed event omits raw error message' );

$exception_callback = $GLOBALS['npcink_abilities_toolkit_unit_registered_abilities']['acme/observable-exception']['execute_callback'] ?? null;
npcink_abilities_toolkit_assert_true( is_callable( $exception_callback ), 'registered exception ability keeps callable observed execute callback' );
try {
	call_user_func( $exception_callback, array( 'payload_json' => 'super-secret-callback-input' ) );
	npcink_abilities_toolkit_assert_true( false, 'observed exception callback rethrows original exception' );
} catch ( RuntimeException $exception ) {
	npcink_abilities_toolkit_assert_same( 'Raw exception message should not be emitted.', $exception->getMessage(), 'observed exception callback rethrows original exception message locally' );
}
$failed_callback_events = npcink_abilities_toolkit_observability_events_of_kind( $GLOBALS['npcink_abilities_toolkit_unit_observability_events'], 'abilities.callback.failed' );
npcink_abilities_toolkit_assert_same( 2, count( $failed_callback_events ), 'throwing callback emits one additional failed callback event' );
npcink_abilities_toolkit_assert_same( 'acme/observable-exception', $failed_callback_events[1]['ability_id'] ?? '', 'exception failed event carries ability id' );
npcink_abilities_toolkit_assert_same( 'abilities.callback_error', $failed_callback_events[1]['error_code'] ?? '', 'exception failed event uses stable error code' );
npcink_abilities_toolkit_assert_same( 'runtimeexception', $failed_callback_events[1]['status_detail'] ?? '', 'exception failed event carries redacted exception class' );
npcink_abilities_toolkit_assert_event_has_safe_event_id( $failed_callback_events[1], 'ability_cb_', 'exception failed event' );
npcink_abilities_toolkit_assert_observability_event_is_metadata_only( $failed_callback_events[1], 'exception failed event payload' );
npcink_abilities_toolkit_assert_true( false === strpos( wp_json_encode( $failed_callback_events[1] ), 'super-secret-callback-input' ), 'exception failed event omits raw callback input values' );
npcink_abilities_toolkit_assert_true( false === strpos( wp_json_encode( $failed_callback_events[1] ), 'Raw exception message should not be emitted.' ), 'exception failed event omits raw exception message' );
$callback_events = npcink_abilities_toolkit_observability_events_of_kind( $GLOBALS['npcink_abilities_toolkit_unit_observability_events'], 'abilities.callback.completed' );
npcink_abilities_toolkit_assert_same( 1, count( $callback_events ), 'failed callbacks do not add completed callback events' );

$bridge = new Npcink_Catalog_Bridge( $registrar );
$catalog = $bridge->filter_catalog( array(), array() );
npcink_abilities_toolkit_assert_true( ! isset( $catalog['acme_site-summary'] ), 'catalog bridge does not project provider abilities by default' );

npcink_abilities_toolkit_assert_true(
	$registrar->add_readonly(
		'acme/projected-summary',
		array(
			'label'                     => 'Projected Summary',
			'description'               => 'Provider ability explicitly projected for Npcink AI compatibility.',
			'project_to_npcink_catalog' => true,
			'input_schema'              => array( 'type' => 'object' ),
			'output_schema'             => array( 'type' => 'object' ),
			'execute_callback'          => static function () {
				return array();
			},
		)
	),
	'registrar accepts provider ability with explicit Npcink AI projection'
);
$catalog = $bridge->filter_catalog( array(), array() );
npcink_abilities_toolkit_assert_true( isset( $catalog['acme_projected-summary'] ), 'catalog bridge projects opted-in provider ability' );
npcink_abilities_toolkit_assert_same( 'wp_ability', $catalog['acme_projected-summary']['executor_type'], 'catalog bridge uses wp_ability executor' );
npcink_abilities_toolkit_assert_same( 'acme/projected-summary', $catalog['acme_projected-summary']['wp_ability_id'], 'catalog bridge keeps wp ability id' );
npcink_abilities_toolkit_assert_same( true, $catalog['acme_projected-summary']['show_in_rest'], 'catalog bridge sets top-level show_in_rest for host catalog normalization' );
npcink_abilities_toolkit_assert_true( ! isset( $catalog['acme_projected-summary']['open_api_enabled'] ), 'catalog bridge does not own Open API routing policy' );
npcink_abilities_toolkit_assert_true( ! isset( $catalog['acme_projected-summary']['skip_catalog_manifest_fallback'] ), 'catalog bridge does not own host manifest fallback policy' );
npcink_abilities_toolkit_assert_true( ! isset( $catalog['acme_projected-summary']['backend_priority'] ), 'catalog bridge does not own backend priority policy' );

npcink_abilities_toolkit_assert_true(
	$registrar->add_write_host_governed(
		'acme/projected-host-write',
		array(
			'label'                     => 'Projected Host Write',
			'description'               => 'Provider write ability explicitly projected for Npcink AI compatibility.',
			'project_to_npcink_catalog' => true,
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
npcink_abilities_toolkit_assert_same( 'wp_ability', $catalog['acme_projected-host-write']['executor_type'] ?? '', 'catalog bridge projects host-governed write as wp_ability' );
npcink_abilities_toolkit_assert_same( true, $catalog['acme_projected-host-write']['requires_confirm'] ?? null, 'catalog bridge carries confirmation requirement for projected host-governed write' );
npcink_abilities_toolkit_assert_true( ! isset( $catalog['acme_projected-host-write']['tool_policy'] ), 'catalog bridge does not own projected host-governed write tool policy' );
npcink_abilities_toolkit_assert_true( ! isset( $catalog['acme_projected-host-write']['skip_catalog_manifest_fallback'] ), 'catalog bridge does not own projected host-governed write fallback policy' );

npcink_abilities_toolkit_assert_true(
	$registrar->add_destructive_host_governed(
		'acme/projected-delete-post',
		array(
			'label'                     => 'Projected Delete Post',
			'description'               => 'Provider destructive ability explicitly projected for Npcink AI compatibility.',
			'project_to_npcink_catalog' => true,
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
npcink_abilities_toolkit_assert_same( 'wp_ability', $catalog['acme_projected-delete-post']['executor_type'] ?? '', 'catalog bridge projects destructive ability as wp_ability' );
npcink_abilities_toolkit_assert_same( true, $catalog['acme_projected-delete-post']['requires_confirm'] ?? null, 'catalog bridge carries confirmation requirement for projected destructive ability' );
npcink_abilities_toolkit_assert_same( 'destructive', $catalog['acme_projected-delete-post']['risk_level'] ?? '', 'catalog bridge carries projected destructive risk' );
npcink_abilities_toolkit_assert_true( ! isset( $catalog['acme_projected-delete-post']['tool_policy'] ), 'catalog bridge does not own projected destructive tool policy' );
npcink_abilities_toolkit_assert_true( ! isset( $catalog['acme_projected-delete-post']['skip_catalog_manifest_fallback'] ), 'catalog bridge does not own projected destructive fallback policy' );

npcink_abilities_toolkit_assert_true(
	$registrar->add_readonly(
		'npcink-abilities-toolkit/official-summary',
		array(
			'label'                     => 'Official Summary',
			'description'               => 'Official ability mirrored from a host plugin.',
			'source'                    => 'official',
			'project_to_npcink_catalog' => false,
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
npcink_abilities_toolkit_assert_true( ! isset( $catalog['npcink-abilities-toolkit_official-summary'] ), 'catalog bridge does not project official mirrored abilities' );

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
		'npcink-abilities-toolkit/build-media-settings-reference-repair-plan',
		'npcink-abilities-toolkit/build-media-optimization-plan',
		'npcink-abilities-toolkit/build-media-rename-plan',
		'npcink-abilities-toolkit/get-taxonomy-consolidation-suggestions',
		'npcink-abilities-toolkit/propose-post-taxonomy-terms',
		'npcink-abilities-toolkit/get-page-structure-health',
		'npcink-abilities-toolkit/build-pattern-page-plan',
	'npcink-abilities-toolkit/get-seo-geo-gap-report',
	'npcink-abilities-toolkit/get-site-style-baseline',
	'npcink-abilities-toolkit/build-article-workflow-context',
	'npcink-abilities-toolkit/get-publishing-calendar-context',
	'npcink-abilities-toolkit/get-media-inventory-health',
	'npcink-abilities-toolkit/inspect-media-asset',
	'npcink-abilities-toolkit/build-media-derivative-cloud-request',
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
$core_governance_snapshot_path = __DIR__ . '/fixtures/core-governance-catalog-snapshot.json';
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
$core_snapshot_doc = file_get_contents( __DIR__ . '/../docs/core-governance-catalog-snapshot.md' );
npcink_abilities_toolkit_assert_true( is_string( $core_snapshot_doc ) && false !== strpos( $core_snapshot_doc, 'tests/fixtures/core-governance-catalog-snapshot.json' ), 'core governance catalog snapshot doc points to fixture' );
$permission_matrix_doc = file_get_contents( __DIR__ . '/../docs/permission-matrix.md' );
npcink_abilities_toolkit_assert_true( is_string( $permission_matrix_doc ) && false !== strpos( $permission_matrix_doc, 'Dry-run previews must still pass the same WordPress permission checks' ), 'permission matrix documents dry-run permission boundary' );
$schema_audit_doc = file_get_contents( __DIR__ . '/../docs/schema-boundary-audit.md' );
npcink_abilities_toolkit_assert_true( is_string( $schema_audit_doc ) && false !== strpos( $schema_audit_doc, 'REST ability details expose' ), 'schema boundary audit documents REST exposure verification' );
$smoke_wp = file_get_contents( __DIR__ . '/smoke-wp.php' );
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
$core_consumer_example = file_get_contents( __DIR__ . '/../examples/core-governance-consumer.php' );
npcink_abilities_toolkit_assert_true( is_string( $core_consumer_example ) && false !== strpos( $core_consumer_example, 'npcink_abilities_toolkit_get_registered' ), 'core governance consumer example uses ability discovery' );
npcink_abilities_toolkit_assert_true( is_string( $core_consumer_example ) && false !== strpos( $core_consumer_example, "'ability_id' => \$ability_id" ), 'core governance consumer example prepares a real ability proposal payload' );
npcink_abilities_toolkit_assert_true( isset( $package_categories->all()['npcink-abilities-toolkit-data'] ), 'core read package registers the legacy npcink-abilities-toolkit-data category for compatibility' );
npcink_abilities_toolkit_assert_true( isset( $package_categories->all()['npcink-abilities-toolkit-pages'] ), 'core read package registers the legacy npcink-abilities-toolkit-pages category for compatibility' );
npcink_abilities_toolkit_assert_true( isset( $package_categories->all()['npcink-abilities-toolkit-comments'] ), 'core comment package registers the standalone comments category' );
npcink_abilities_toolkit_assert_true( isset( $package_categories->all()['npcink-abilities-toolkit-write'] ), 'core write package registers the legacy npcink-abilities-toolkit-write category for compatibility' );
npcink_abilities_toolkit_assert_true( isset( $package_categories->all()['npcink-abilities-toolkit-diagnostics'] ), 'core read package registers the standalone diagnostics category' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/wp-diagnostics-summary'] ), 'core read package owns standalone wp-diagnostics-summary ability' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit-diagnostics', $package_abilities['npcink-abilities-toolkit/wp-diagnostics-summary']['category'], 'wp-diagnostics-summary uses standalone diagnostics category' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail'] ), 'core read package owns standalone wp-ops-diagnostics-detail ability' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit-diagnostics', $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['category'], 'wp-ops-diagnostics-detail uses standalone diagnostics category' );
npcink_abilities_toolkit_assert_true( false !== strpos( $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['description'] ?? '', 'plugin' ), 'ops diagnostics description mentions plugin details' );
npcink_abilities_toolkit_assert_same( 50, $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['max_cron_events']['maximum'] ?? null, 'ops diagnostics bounds returned cron events' );
npcink_abilities_toolkit_assert_same( false, $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['include_log_contents']['default'] ?? null, 'ops diagnostics does not include log contents by default' );
npcink_abilities_toolkit_assert_true( ! isset( $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['include_log_tail'] ), 'ops diagnostics uses one log contents control' );
npcink_abilities_toolkit_assert_same( false, $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['include_inactive_plugins']['default'] ?? null, 'ops diagnostics omits inactive plugin rows by default' );
npcink_abilities_toolkit_assert_same( true, $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['include_plugin_updates']['default'] ?? null, 'ops diagnostics includes plugin update rows by default' );
npcink_abilities_toolkit_assert_same( 500, $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['max_plugins_per_group']['maximum'] ?? null, 'ops diagnostics bounds plugin rows per group' );
npcink_abilities_toolkit_assert_same( 200, $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['tail_lines']['maximum'] ?? null, 'ops diagnostics bounds returned log tail lines' );
npcink_abilities_toolkit_assert_same( 10080, $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['since_minutes']['maximum'] ?? null, 'ops diagnostics bounds log since window' );
npcink_abilities_toolkit_assert_true( in_array( 'warning', $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['severity']['items']['enum'] ?? array(), true ), 'ops diagnostics supports log severity filtering' );
npcink_abilities_toolkit_assert_same( true, $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['input_schema']['properties']['include_integrations']['default'] ?? null, 'ops diagnostics includes integration diagnostics by default' );
npcink_abilities_toolkit_assert_true( in_array( 'plugins', $package_abilities['npcink-abilities-toolkit/wp-ops-diagnostics-detail']['output_schema']['required'] ?? array(), true ), 'ops diagnostics output requires plugins section' );
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
npcink_abilities_toolkit_assert_true( ! empty( $package_abilities['npcink-abilities-toolkit/build-media-inventory-fix-plan']['agent_usage']['when_to_use'] ), 'media inventory fix plan exposes agent usage guidance' );
npcink_abilities_toolkit_assert_true( ! empty( $package_abilities['npcink-abilities-toolkit/build-media-inventory-fix-plan']['agent_usage']['stopping_points'] ), 'media inventory fix plan exposes agent stopping points' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-media-optimization-plan'] ), 'build-media-optimization-plan is registered as a read-only planning ability' );
npcink_abilities_toolkit_assert_same( array( 'media.read' ), $package_abilities['npcink-abilities-toolkit/build-media-optimization-plan']['required_scopes'] ?? array(), 'media optimization plan remains a read-scope planning ability' );
npcink_abilities_toolkit_assert_same( array( 'attachment_id', 'media_details_input', 'derivative_artifact' ), $package_abilities['npcink-abilities-toolkit/build-media-optimization-plan']['input_schema']['required'] ?? array(), 'media optimization plan requires metadata and artifact evidence' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-media-optimization-plan']['input_schema']['properties']['file_name'] ), 'media optimization plan accepts a reviewed custom derivative file name' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-media-rename-plan'] ), 'build-media-rename-plan is registered as a read-only planning ability' );
npcink_abilities_toolkit_assert_same( array( 'media.read', 'post.read' ), $package_abilities['npcink-abilities-toolkit/build-media-rename-plan']['required_scopes'] ?? array(), 'media rename plan reads media and post references' );
npcink_abilities_toolkit_assert_same( array( 'attachment_id', 'target_file_name' ), $package_abilities['npcink-abilities-toolkit/build-media-rename-plan']['input_schema']['required'] ?? array(), 'media rename plan requires attachment and target filename' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-pattern-page-plan'] ), 'build-pattern-page-plan is registered as a read-only planning ability' );
npcink_abilities_toolkit_assert_same( array( 'post.read' ), $package_abilities['npcink-abilities-toolkit/build-pattern-page-plan']['required_scopes'] ?? array(), 'pattern page plan remains a read-scope planning ability' );
npcink_abilities_toolkit_assert_same( array( 'title', 'pattern_id' ), $package_abilities['npcink-abilities-toolkit/build-pattern-page-plan']['input_schema']['required'] ?? array(), 'pattern page plan requires title and pattern id' );
npcink_abilities_toolkit_assert_same( array( 'landing_standard' ), $package_abilities['npcink-abilities-toolkit/build-pattern-page-plan']['input_schema']['properties']['responsive_profile']['enum'] ?? array(), 'pattern page plan exposes a bounded responsive profile' );
npcink_abilities_toolkit_assert_same( array( 'balanced' ), $package_abilities['npcink-abilities-toolkit/build-pattern-page-plan']['input_schema']['properties']['visual_density']['enum'] ?? array(), 'pattern page plan exposes a bounded visual density' );
npcink_abilities_toolkit_assert_same( array( 'mock_or_existing_media', 'existing_media_url' ), $package_abilities['npcink-abilities-toolkit/build-pattern-page-plan']['input_schema']['properties']['media_strategy']['enum'] ?? array(), 'pattern page plan exposes bounded media strategies' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-pattern-page-plan']['input_schema']['properties']['research_brief'] ), 'pattern page plan accepts an optional landing page research brief' );
npcink_abilities_toolkit_assert_same( array( 'owned', 'ai_generated', 'stock', 'external', 'test' ), $package_abilities['npcink-abilities-toolkit/update-media-details']['input_schema']['properties']['source_type']['enum'] ?? array(), 'update-media-details accepts canonical media source_type values' );
npcink_abilities_toolkit_assert_same( 'external', $package_abilities['npcink-abilities-toolkit/upload-media-from-url']['input_schema']['properties']['source_type']['default'] ?? '', 'upload-media-from-url defaults remote imports to external source type' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/upload-media-from-url']['input_schema']['properties']['file_name'] ), 'upload-media-from-url accepts an approved custom media file name' );
npcink_abilities_toolkit_assert_same( array( 'webp', 'jpeg', 'png' ), $package_abilities['npcink-abilities-toolkit/optimize-media-asset']['input_schema']['properties']['preferred_format']['enum'] ?? array(), 'optimize-media-asset exposes bounded derivative formats' );
npcink_abilities_toolkit_assert_same( 82, $package_abilities['npcink-abilities-toolkit/optimize-media-asset']['input_schema']['properties']['quality']['default'] ?? null, 'optimize-media-asset defaults to quality 82' );
npcink_abilities_toolkit_assert_true( ! isset( $package_abilities['npcink-abilities-toolkit/replace-media-file']['input_schema']['properties']['mode'] ), 'replace-media-file does not expose media restore modes' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit-backup', $package_abilities['npcink-abilities-toolkit/replace-media-file']['input_schema']['properties']['backup_suffix']['default'] ?? '', 'replace-media-file defaults to explicit Npcink backup suffix' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/replace-media-file']['output_schema']['properties']['content_reference_repairs'] ), 'replace-media-file exposes post content reference repair preview evidence' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/replace-media-file']['output_schema']['properties']['verification'] ), 'replace-media-file exposes execution verification summary' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/list-media-backups'] ), 'list-media-backups is registered as a read-only media history ability' );
npcink_abilities_toolkit_assert_same( array( 'attachment_id' ), $package_abilities['npcink-abilities-toolkit/list-media-backups']['input_schema']['required'] ?? array(), 'list-media-backups requires attachment id' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/restore-media-backup'] ), 'restore-media-backup is registered as a governed write ability' );
npcink_abilities_toolkit_assert_same( array( 'attachment_id', 'backup_id' ), $package_abilities['npcink-abilities-toolkit/restore-media-backup']['input_schema']['required'] ?? array(), 'restore-media-backup requires attachment and backup id' );
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
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/adopt-cloud-media-derivative']['output_schema']['properties']['proposed_filename'] ) && isset( $package_abilities['npcink-abilities-toolkit/adopt-cloud-media-derivative']['output_schema']['properties']['filename_policy'] ), 'adopt-cloud-media-derivative exposes filename proposal evidence in its output schema' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/adopt-cloud-media-derivative']['output_schema']['properties']['content_reference_repairs'] ), 'adopt-cloud-media-derivative exposes post content reference repair preview evidence' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/adopt-cloud-media-derivative']['output_schema']['properties']['verification'] ), 'adopt-cloud-media-derivative exposes execution verification summary' );
npcink_abilities_toolkit_assert_same( array( 'attachment_id', 'derivative_artifact' ), $package_abilities['npcink-abilities-toolkit/adopt-cloud-media-derivative']['input_schema']['required'] ?? array(), 'adopt-cloud-media-derivative requires attachment and artifact evidence' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-media-reference-repair-plan'] ), 'build-media-reference-repair-plan is registered as a read-only planning ability' );
npcink_abilities_toolkit_assert_same( array( 'attachment_id' ), $package_abilities['npcink-abilities-toolkit/build-media-reference-repair-plan']['input_schema']['required'] ?? array(), 'build-media-reference-repair-plan requires attachment id' );
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
npcink_abilities_toolkit_assert_same( array( 'image', 'text' ), $package_abilities['npcink-abilities-toolkit/build-media-derivative-cloud-request']['input_schema']['properties']['watermark']['properties']['type']['enum'] ?? array(), 'media derivative cloud request supports image and text watermark plans' );
npcink_abilities_toolkit_assert_same( array( 'top_left', 'top_right', 'bottom_left', 'bottom_right', 'center' ), $package_abilities['npcink-abilities-toolkit/build-media-derivative-cloud-request']['input_schema']['properties']['watermark']['properties']['position']['enum'] ?? array(), 'media derivative cloud request exposes bounded watermark positions' );
npcink_abilities_toolkit_assert_same( 0.75, $package_abilities['npcink-abilities-toolkit/build-media-derivative-cloud-request']['input_schema']['properties']['watermark']['properties']['opacity']['default'] ?? null, 'media derivative cloud request defaults watermark opacity' );
npcink_abilities_toolkit_assert_same( 18, $package_abilities['npcink-abilities-toolkit/build-media-derivative-cloud-request']['input_schema']['properties']['watermark']['properties']['scale_percent']['default'] ?? null, 'media derivative cloud request defaults watermark scale' );
npcink_abilities_toolkit_assert_same( 'AI', $package_abilities['npcink-abilities-toolkit/build-media-derivative-cloud-request']['input_schema']['properties']['watermark']['properties']['text']['default'] ?? null, 'media derivative cloud request defaults text watermark content' );
npcink_abilities_toolkit_assert_same( 48, $package_abilities['npcink-abilities-toolkit/build-media-derivative-cloud-request']['input_schema']['properties']['watermark']['properties']['font_size']['default'] ?? null, 'media derivative cloud request defaults text watermark font size' );
npcink_abilities_toolkit_assert_true( isset( $package_abilities['npcink-abilities-toolkit/build-media-derivative-batch-plan'] ), 'media derivative batch plan is registered as a read-only planning ability' );
npcink_abilities_toolkit_assert_same( array( 'media.read' ), $package_abilities['npcink-abilities-toolkit/build-media-derivative-batch-plan']['required_scopes'] ?? array(), 'media derivative batch plan remains a read-scope planning ability' );
npcink_abilities_toolkit_assert_same( array( 'webp', 'avif', 'jpeg', 'png', 'original' ), $package_abilities['npcink-abilities-toolkit/build-media-derivative-batch-plan']['input_schema']['properties']['target_format']['enum'] ?? array(), 'media derivative batch plan exposes bounded target formats' );
npcink_abilities_toolkit_assert_same( 50, $package_abilities['npcink-abilities-toolkit/build-media-derivative-batch-plan']['input_schema']['properties']['max_items']['maximum'] ?? null, 'media derivative batch plan bounds candidates to 50 items' );
npcink_abilities_toolkit_assert_true( ! isset( $package_abilities['npcink-abilities-toolkit/build-media-derivative-batch-plan']['input_schema']['properties']['commit'] ), 'media derivative batch plan does not expose a commit control' );
npcink_abilities_toolkit_assert_true( ! isset( $package_abilities['npcink-abilities-toolkit/build-media-derivative-batch-plan']['input_schema']['properties']['dry_run'] ), 'media derivative batch plan does not expose write dry_run control' );
npcink_abilities_toolkit_assert_same( 100, $package_abilities['npcink-abilities-toolkit/get-taxonomy-consolidation-suggestions']['input_schema']['properties']['per_page']['maximum'] ?? null, 'taxonomy consolidation suggestions scan is bounded to 100 terms per page' );
npcink_abilities_toolkit_assert_same( array( 'post_id' ), $package_abilities['npcink-abilities-toolkit/propose-post-taxonomy-terms']['input_schema']['required'] ?? array(), 'post taxonomy proposal requires post_id' );
npcink_abilities_toolkit_assert_same( 20, $package_abilities['npcink-abilities-toolkit/propose-post-taxonomy-terms']['input_schema']['properties']['candidate_terms']['maxItems'] ?? null, 'post taxonomy proposal bounds candidate term names' );
npcink_abilities_toolkit_assert_same( 100, $package_abilities['npcink-abilities-toolkit/get-page-structure-health']['input_schema']['properties']['max_pages']['maximum'] ?? null, 'page structure health scan is bounded to 100 pages' );
npcink_abilities_toolkit_assert_same( 100, $package_abilities['npcink-abilities-toolkit/get-seo-geo-gap-report']['input_schema']['properties']['per_page']['maximum'] ?? null, 'SEO/GEO gap report scan is bounded to 100 posts per page' );
npcink_abilities_toolkit_assert_same( 5, $package_abilities['npcink-abilities-toolkit/get-site-style-baseline']['input_schema']['properties']['limit']['maximum'] ?? null, 'site style baseline is bounded to 5 samples' );
npcink_abilities_toolkit_assert_same( array( 'new_article', 'refresh', 'publish' ), $package_abilities['npcink-abilities-toolkit/build-article-workflow-context']['input_schema']['properties']['workflow']['enum'] ?? array(), 'article workflow context supports known workflow modes' );
npcink_abilities_toolkit_assert_same( 365, $package_abilities['npcink-abilities-toolkit/get-publishing-calendar-context']['input_schema']['properties']['window_days']['maximum'] ?? null, 'publishing calendar window is bounded to 365 days' );
npcink_abilities_toolkit_assert_same( 100, $package_abilities['npcink-abilities-toolkit/get-media-inventory-health']['input_schema']['properties']['per_page']['maximum'] ?? null, 'media inventory health scan is bounded to 100 assets per page' );
npcink_abilities_toolkit_assert_same( array( 'post_id' ), $package_abilities['npcink-abilities-toolkit/get-post-seo-geo-readiness']['input_schema']['required'] ?? array(), 'post SEO/GEO readiness requires post_id' );
npcink_abilities_toolkit_assert_same( 100, $package_abilities['npcink-abilities-toolkit/get-site-topic-coverage-report']['input_schema']['properties']['per_page']['maximum'] ?? null, 'site topic coverage scan is bounded to 100 posts per page' );
npcink_abilities_toolkit_assert_same( 100, $package_abilities['npcink-abilities-toolkit/get-taxonomy-inventory-health']['input_schema']['properties']['per_page']['maximum'] ?? null, 'taxonomy inventory health scan is bounded to 100 terms per page' );
npcink_abilities_toolkit_assert_same( array( 'post_id' ), $package_abilities['npcink-abilities-toolkit/get-revision-change-risk-report']['input_schema']['required'] ?? array(), 'revision change risk report requires post_id' );
npcink_abilities_toolkit_assert_same( 100, $package_abilities['npcink-abilities-toolkit/get-comment-queue-health']['input_schema']['properties']['per_page']['maximum'] ?? null, 'comment queue health scan is bounded to 100 comments per page' );
npcink_abilities_toolkit_assert_same( 100, $package_abilities['npcink-abilities-toolkit/get-comment-action-priority-queue']['input_schema']['properties']['per_page']['maximum'] ?? null, 'comment action priority queue scan is bounded to 100 comments per page' );
npcink_abilities_toolkit_assert_same( 100, $package_abilities['npcink-abilities-toolkit/get-comment-compliance-handoff']['input_schema']['properties']['per_page']['maximum'] ?? null, 'comment compliance handoff scan is bounded to 100 comments per page' );
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
npcink_abilities_toolkit_assert_same( 'taxonomy_governance', $package_abilities['npcink-abilities-toolkit/propose-post-taxonomy-terms']['meta']['npcink_abilities_toolkit']['pack'] ?? '', 'post taxonomy proposal is classified as taxonomy governance' );
npcink_abilities_toolkit_assert_same( 'page_governance', $package_abilities['npcink-abilities-toolkit/build-pattern-page-plan']['meta']['npcink_abilities_toolkit']['pack'] ?? '', 'pattern page plan is classified as page governance' );
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
	npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/site-info', $core_read_definition_ids[0] ?? '', 'core read definitions keep site-info first after provider split' );
	npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/wp-diagnostics-summary', $core_read_definition_ids[1] ?? '', 'core read definitions keep diagnostics second after provider split' );
	npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/wp-ops-diagnostics-detail', $core_read_definition_ids[2] ?? '', 'core read definitions keep ops diagnostics after diagnostics summary' );
	npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/list-workflow-recipes', $core_read_definition_ids[3] ?? '', 'core read definitions keep workflow list after diagnostics' );
	npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/get-workflow-recipe', $core_read_definition_ids[4] ?? '', 'core read definitions keep workflow get after workflow list' );
	npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/list-post-types', $core_read_definition_ids[5] ?? '', 'core read definitions keep post types after workflow definitions' );
		npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/list-media', $core_read_definition_ids[7] ?? '', 'core read definitions keep media governance order after provider split' );
		npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/resolve-media-attachment-by-url', $core_read_definition_ids[8] ?? '', 'core read definitions keep media URL resolver near media inventory' );
		npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/build-pattern-page-plan', $core_read_definition_ids[16] ?? '', 'core read definitions keep pattern page planning near metadata planning' );
		npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/build-article-block-plan', $core_read_definition_ids[17] ?? '', 'core read definitions keep article block planning near pattern page planning' );
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

add_filter(
	'npcink_abilities_toolkit_enabled_comment_packs',
	static function () {
		return array( 'comment_queue_context' );
	}
);
$filtered_comment_categories = new Category_Registrar();
$filtered_comment_registrar = new Ability_Registrar( $filtered_comment_categories, $contract_normalizer );
$filtered_comment_package = new Core_Comment_Package( $filtered_comment_categories, $filtered_comment_registrar );
$filtered_comment_package->boot();
$filtered_comment_abilities = $filtered_comment_registrar->all();
npcink_abilities_toolkit_assert_true( isset( $filtered_comment_abilities['npcink-abilities-toolkit/get-comment-queue-health'] ), 'comment pack filter keeps queue helper ability' );
npcink_abilities_toolkit_assert_true( ! isset( $filtered_comment_abilities['npcink-abilities-toolkit/get-comment-compliance-handoff'] ), 'comment pack filter removes handoff helper ability' );
remove_all_filters( 'npcink_abilities_toolkit_enabled_comment_packs' );
foreach ( $migrated_write_ability_ids as $migrated_write_ability_id ) {
	npcink_abilities_toolkit_assert_true( isset( $package_abilities[ $migrated_write_ability_id ] ), "core write package owns migrated {$migrated_write_ability_id} ability" );
	npcink_abilities_toolkit_assert_package_write_ability_contract( $migrated_write_ability_id, $package_abilities[ $migrated_write_ability_id ] );
}
foreach ( $migrated_destructive_ability_ids as $migrated_destructive_ability_id ) {
	npcink_abilities_toolkit_assert_true( isset( $package_abilities[ $migrated_destructive_ability_id ] ), "core destructive package owns migrated {$migrated_destructive_ability_id} ability" );
	npcink_abilities_toolkit_assert_package_destructive_ability_contract( $migrated_destructive_ability_id, $package_abilities[ $migrated_destructive_ability_id ] );
}
npcink_abilities_toolkit_assert_same(
	array( 'taxonomy', 'name' ),
	$package_abilities['npcink-abilities-toolkit/create-term']['input_schema']['required'] ?? array(),
	'create-term preserves migrated required taxonomy/name schema'
);
npcink_abilities_toolkit_assert_same(
	array( 'taxonomy', 'term_id' ),
	$package_abilities['npcink-abilities-toolkit/update-term']['input_schema']['required'] ?? array(),
	'update-term preserves migrated required taxonomy/term_id schema'
);
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'] = array(
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
npcink_abilities_toolkit_assert_same( true, $create_preview['dry_run'] ?? null, 'create-draft defaults to governed dry-run preview when requested' );
npcink_abilities_toolkit_assert_same( 'create_draft', $create_preview['preview']['action'] ?? '', 'create-draft dry-run reports preview action' );

$GLOBALS['npcink_ai_runtime_wp_ability_context'] = array( 'context' => array( 'approval_commit_authorized' => true ) );
$created = $core_write_package->create_draft(
	array(
		'title' => 'Migrated Draft',
		'content' => "# Migrated Draft\n\n![Alt](https://example.test/image.jpg)\n\nBody text.",
		'content_format' => 'markdown',
		'commit' => true,
		'meta' => array( 'source' => 'unit' ),
	)
);
unset( $GLOBALS['npcink_ai_runtime_wp_ability_context'] );
npcink_abilities_toolkit_assert_same( false, $created['dry_run'] ?? null, 'create-draft commit returns a committed payload' );
npcink_abilities_toolkit_assert_same( 'markdown', $created['content_format'] ?? '', 'create-draft preserves migrated markdown content_format reporting' );
$created_post = get_post( (int) ( $created['post_id'] ?? 0 ) );
npcink_abilities_toolkit_assert_true( is_object( $created_post ), 'create-draft commit creates a draft post in the standalone package' );
npcink_abilities_toolkit_assert_true( false === strpos( (string) ( $created_post->post_content ?? '' ), '<h1>Migrated Draft</h1>' ), 'create-draft strips a duplicate leading title heading after migration' );
npcink_abilities_toolkit_assert_true( false !== strpos( (string) ( $created_post->post_content ?? '' ), '<img src="https://example.test/image.jpg" alt="Alt" />' ), 'create-draft converts markdown image syntax after migration' );

$update_preview = $core_write_package->update_post(
	array(
		'post_id' => 501,
		'content' => "## Updated heading\n\nUpdated body.",
		'dry_run' => true,
	)
);
npcink_abilities_toolkit_assert_same( true, $update_preview['dry_run'] ?? null, 'update-post returns a governed dry-run preview after migration' );
npcink_abilities_toolkit_assert_same( 'markdown', $update_preview['changes']['content']['content_format'] ?? '', 'update-post auto-detects markdown content after migration' );

$GLOBALS['npcink_abilities_toolkit_unit_post_meta'][501]['_yoast_wpseo_title'] = 'Old SEO title';
$seo_preview = $core_write_package->set_post_seo_meta(
	array(
		'post_id' => 501,
		'seo_title' => 'New SEO title',
		'seo_description' => 'New SEO description',
		'dry_run' => true,
	)
);
npcink_abilities_toolkit_assert_same( true, $seo_preview['dry_run'] ?? null, 'set-post-seo-meta returns a governed dry-run preview after migration' );
npcink_abilities_toolkit_assert_same( 'yoast', $seo_preview['provider'] ?? '', 'set-post-seo-meta detects existing Yoast-style SEO metadata after migration' );
$seo_missing_fields = $core_write_package->set_post_seo_meta(
	array(
		'post_id' => 501,
		'dry_run' => true,
	)
);
npcink_abilities_toolkit_assert_true( is_wp_error( $seo_missing_fields ), 'set-post-seo-meta rejects requests without explicit metadata fields' );
npcink_abilities_toolkit_assert_same( 'npcink_abilities_toolkit_no_changes', $seo_missing_fields->code ?? '', 'set-post-seo-meta no-change request fails with a stable code' );
$seo_title_only_preview = $core_write_package->set_post_seo_meta(
	array(
		'post_id'   => 501,
		'seo_title' => 'Title-only preview',
		'dry_run'   => true,
	)
);
npcink_abilities_toolkit_assert_same( array( 'seo_title' ), $seo_title_only_preview['preview']['changed_fields'] ?? array(), 'set-post-seo-meta preview reports only explicit changed fields' );
$GLOBALS['npcink_ai_runtime_wp_ability_context'] = array( 'context' => array( 'approval_commit_authorized' => true ) );
$seo_written = $core_write_package->set_post_seo_meta(
	array(
		'post_id' => 501,
		'seo_title' => 'Committed SEO title',
		'seo_description' => 'Committed SEO description',
		'commit' => true,
	)
);
unset( $GLOBALS['npcink_ai_runtime_wp_ability_context'] );
npcink_abilities_toolkit_assert_same( false, $seo_written['dry_run'] ?? null, 'set-post-seo-meta commit returns a committed payload after migration' );
npcink_abilities_toolkit_assert_same( 'Committed SEO title', $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][501]['_yoast_wpseo_title'] ?? '', 'set-post-seo-meta writes SEO title through standalone fallback metadata keys' );
npcink_abilities_toolkit_assert_same( 'Committed SEO description', $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][501]['_yoast_wpseo_metadesc'] ?? '', 'set-post-seo-meta writes SEO description through standalone fallback metadata keys' );
$GLOBALS['npcink_ai_runtime_wp_ability_context'] = array( 'context' => array( 'approval_commit_authorized' => true ) );
$seo_title_only_written = $core_write_package->set_post_seo_meta(
	array(
		'post_id'   => 501,
		'seo_title' => 'Only title changed',
		'commit'    => true,
	)
);
unset( $GLOBALS['npcink_ai_runtime_wp_ability_context'] );
npcink_abilities_toolkit_assert_same( false, $seo_title_only_written['dry_run'] ?? null, 'set-post-seo-meta title-only commit returns committed payload' );
npcink_abilities_toolkit_assert_same( 'Only title changed', $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][501]['_yoast_wpseo_title'] ?? '', 'set-post-seo-meta title-only commit writes title' );
npcink_abilities_toolkit_assert_same( 'Committed SEO description', $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][501]['_yoast_wpseo_metadesc'] ?? '', 'set-post-seo-meta title-only commit preserves description' );
$GLOBALS['npcink_abilities_toolkit_unit_comments'][11] = (object) array(
	'comment_ID'       => 11,
	'comment_post_ID'  => 77,
	'comment_author'   => 'Permission Fixture',
	'comment_approved' => 'hold',
	'comment_content'  => 'Pending moderation.',
);
$GLOBALS['npcink_abilities_toolkit_unit_current_user_caps'] = array( 'moderate_comments' => false );
$comment_permission_denied = $core_write_package->approve_comment(
	array(
		'comment_id' => 11,
		'dry_run'    => true,
	)
);
unset( $GLOBALS['npcink_abilities_toolkit_unit_current_user_caps'] );
npcink_abilities_toolkit_assert_true( is_wp_error( $comment_permission_denied ), 'approve-comment enforces moderate_comments before dry-run preview' );
npcink_abilities_toolkit_assert_same( 'npcink_abilities_toolkit_permission_denied', $comment_permission_denied->code ?? '', 'approve-comment permission denial has stable error code' );

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
npcink_abilities_toolkit_assert_same( true, $patch_preview['dry_run'] ?? null, 'patch-post-content returns a governed dry-run preview after migration' );
npcink_abilities_toolkit_assert_same( 1, $patch_preview['patch_preview'][0]['applied'] ?? null, 'patch-post-content reports applied operation count after migration' );

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
npcink_abilities_toolkit_assert_same( true, $blocks_preview['dry_run'] ?? null, 'update-post-blocks returns a governed dry-run preview after migration' );
npcink_abilities_toolkit_assert_same( true, $blocks_preview['validation']['valid'] ?? null, 'update-post-blocks validates serialized blocks after migration' );

$GLOBALS['npcink_ai_runtime_wp_ability_context'] = array( 'context' => array( 'approval_commit_authorized' => true ) );
$nested_blocks_written = $core_write_package->update_post_blocks(
	array(
		'post_id'            => 501,
		'validate_roundtrip' => false,
		'blocks'             => array(
			array(
				'blockName'    => 'core/group',
				'attrs'        => array(),
				'innerHTML'    => '<div class="wp-block-group"></div>',
				'innerContent' => array( '<div class="wp-block-group">', null, '</div>' ),
				'innerBlocks'  => array(
					array(
						'blockName'    => 'core/paragraph',
						'attrs'        => array(),
						'innerHTML'    => '<p>Nested body.</p>',
						'innerContent' => array( '<p>Nested body.</p>' ),
						'innerBlocks'  => array(),
					),
				),
			),
		),
		'commit'            => true,
	)
);
unset( $GLOBALS['npcink_ai_runtime_wp_ability_context'] );
npcink_abilities_toolkit_assert_same( false, $nested_blocks_written['dry_run'] ?? null, 'update-post-blocks commit returns a committed payload for nested parsed blocks' );
$nested_content = (string) ( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][501]->post_content ?? '' );
npcink_abilities_toolkit_assert_true( false !== strpos( $nested_content, '<div class="wp-block-group"><!-- wp:paragraph -->' ), 'update-post-blocks serializes innerBlocks at innerContent null markers' );
npcink_abilities_toolkit_assert_true( false === strpos( $nested_content, '</div><!-- wp:paragraph -->' ), 'update-post-blocks does not append innerBlocks after the parent wrapper' );
unset( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'], $GLOBALS['npcink_abilities_toolkit_unit_post_meta'] );
$inspect_page_structure = $package_abilities['npcink-abilities-toolkit/inspect-page-structure'];
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit-pages', $inspect_page_structure['category'], 'inspect-page-structure uses page category' );
npcink_abilities_toolkit_assert_same( 1, $inspect_page_structure['input_schema']['properties']['max_pages']['minimum'] ?? null, 'inspect-page-structure max_pages minimum is 1' );
npcink_abilities_toolkit_assert_same( 100, $inspect_page_structure['input_schema']['properties']['max_pages']['maximum'] ?? null, 'inspect-page-structure max_pages maximum is 100' );
npcink_abilities_toolkit_assert_same( 50, $inspect_page_structure['input_schema']['properties']['max_pages']['default'] ?? null, 'inspect-page-structure max_pages default is 50' );
$proposal_excerpt = $package_abilities['npcink-abilities-toolkit/propose-post-excerpt'];
npcink_abilities_toolkit_assert_same( true, $proposal_excerpt['annotations']['readonly'], 'propose-post-excerpt remains proposal-only and readonly' );
npcink_abilities_toolkit_assert_same( false, $proposal_excerpt['requires_confirm'], 'propose-post-excerpt does not perform a final write' );
$GLOBALS['npcink_abilities_toolkit_unit_comments'] = array(
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
npcink_abilities_toolkit_assert_same( true, $comment_suggest['success'] ?? null, 'build-comment-moderation-suggest returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'spam', $comment_suggest['data']['recommended_action'] ?? '', 'build-comment-moderation-suggest flags promotional comments as spam' );
npcink_abilities_toolkit_assert_true( in_array( 'commercial_promo', $comment_suggest['data']['risk_flags'] ?? array(), true ), 'build-comment-moderation-suggest exposes commercial promo risk flag' );
$GLOBALS['npcink_abilities_toolkit_unit_comments'][13] = (object) array(
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
npcink_abilities_toolkit_assert_same( 'spam', $pharmacy_comment_suggest['data']['recommended_action'] ?? '', 'build-comment-moderation-suggest flags pharmacy spam without relying on links' );
$comment_result = $core_comment_package->compose_comment_moderation_result(
	array(
		'comment_id' => 11,
		'mode' => 'suggest',
		'suggest_result' => $comment_suggest['data'],
	)
);
npcink_abilities_toolkit_assert_same( true, $comment_result['success'] ?? null, 'compose-comment-moderation-result returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'spam', $comment_result['data']['recommended_action'] ?? '', 'compose-comment-moderation-result keeps recommended action' );
$mention_suggest = $core_comment_package->build_comment_mention_reply_suggest(
	array(
		'comment_id' => 12,
		'trigger_type' => 'mention',
	)
);
npcink_abilities_toolkit_assert_same( true, $mention_suggest['success'] ?? null, 'build-comment-mention-reply-suggest returns a success envelope' );
npcink_abilities_toolkit_assert_same( true, $mention_suggest['data']['trigger']['trigger_detected'] ?? null, 'build-comment-mention-reply-suggest detects mention trigger' );
$trigger_queue = $core_comment_package->read_comment_trigger_queue(
	array(
		'post_id' => 77,
		'trigger_type' => 'mention',
		'status' => 'hold',
	)
);
npcink_abilities_toolkit_assert_same( 1, $trigger_queue['data']['summary']['candidate_count'] ?? null, 'read-comment-trigger-queue returns detected mention candidates' );
$comment_queue_health = $core_comment_package->get_comment_queue_health(
	array(
		'post_id'  => 77,
		'status'   => 'hold',
		'per_page' => 10,
	)
);
npcink_abilities_toolkit_assert_same( true, $comment_queue_health['success'] ?? null, 'get-comment-queue-health returns a success envelope' );
npcink_abilities_toolkit_assert_same( 3, $comment_queue_health['data']['summary']['counts']['total'] ?? null, 'get-comment-queue-health counts queued comments' );
npcink_abilities_toolkit_assert_true( (int) ( $comment_queue_health['data']['summary']['counts']['spam_risk'] ?? 0 ) >= 1, 'get-comment-queue-health counts spam-risk comments' );
npcink_abilities_toolkit_assert_true( (int) ( $comment_queue_health['data']['summary']['counts']['reply_needed'] ?? 0 ) >= 1, 'get-comment-queue-health counts reply-needed comments' );
$comment_action_queue = $core_comment_package->get_comment_action_priority_queue(
	array(
		'post_id'  => 77,
		'status'   => 'hold',
		'per_page' => 10,
	)
);
npcink_abilities_toolkit_assert_same( true, $comment_action_queue['success'] ?? null, 'get-comment-action-priority-queue returns a success envelope' );
npcink_abilities_toolkit_assert_same( 3, $comment_action_queue['data']['summary']['counts']['total'] ?? null, 'get-comment-action-priority-queue counts queued comments' );
npcink_abilities_toolkit_assert_true( (int) ( $comment_action_queue['data']['items'][0]['priority_score'] ?? 0 ) >= (int) ( $comment_action_queue['data']['items'][1]['priority_score'] ?? 0 ), 'get-comment-action-priority-queue sorts high-priority items first' );
$comment_handoff = $core_comment_package->get_comment_compliance_handoff(
	array(
		'post_id'             => 77,
		'status'              => 'hold',
		'per_page'            => 10,
		'selected_comment_id' => 12,
	)
);
npcink_abilities_toolkit_assert_same( true, $comment_handoff['success'] ?? null, 'get-comment-compliance-handoff returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'workflow/wordpress_comment_compliance_handoff', $comment_handoff['data']['recipe'] ?? '', 'get-comment-compliance-handoff declares its recipe id' );
npcink_abilities_toolkit_assert_true( in_array( 'selected_moderation_suggestion', $comment_handoff['data']['sections'] ?? array(), true ), 'get-comment-compliance-handoff includes selected moderation suggestion' );
$batch_suggest = $core_comment_package->build_comment_moderation_batch_suggest(
	array(
		'comment_ids' => array( 11, 12 ),
	)
);
npcink_abilities_toolkit_assert_same( true, $batch_suggest['success'] ?? null, 'build-comment-moderation-batch-suggest returns a success envelope' );
npcink_abilities_toolkit_assert_same( 2, $batch_suggest['data']['batch_summary']['counts']['total'] ?? null, 'build-comment-moderation-batch-suggest counts batch items' );
$batch_result = $core_comment_package->compose_comment_moderation_batch_result(
	array(
		'batch_result' => $batch_suggest['data'],
	)
);
npcink_abilities_toolkit_assert_same( 'review_individual_items', $batch_result['data']['next_action'] ?? '', 'compose-comment-moderation-batch-result keeps single-item handoff' );
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
npcink_abilities_toolkit_assert_same( true, $resolved_metadata_plan['success'] ?? null, 'resolve-post-metadata-plan returns a success envelope' );
npcink_abilities_toolkit_assert_same( '这是新的摘要。', $resolved_metadata_plan['data']['excerpt'] ?? '', 'resolve-post-metadata-plan preserves explicit excerpt' );
npcink_abilities_toolkit_assert_same( 'new-canonical-slug', $resolved_metadata_plan['data']['slug'] ?? '', 'resolve-post-metadata-plan sanitizes explicit slug' );
npcink_abilities_toolkit_assert_same( array( 3, 5 ), $resolved_metadata_plan['data']['categories'] ?? array(), 'resolve-post-metadata-plan prefers metadata categories' );
npcink_abilities_toolkit_assert_same( array( 8, 13 ), $resolved_metadata_plan['data']['tags'] ?? array(), 'resolve-post-metadata-plan falls back to taxonomy tags' );
npcink_abilities_toolkit_assert_same( '2030-01-02 03:04:05', $resolved_metadata_plan['data']['publish_at'] ?? '', 'resolve-post-metadata-plan preserves publish_at handoff' );
npcink_abilities_toolkit_assert_same( 12, $resolved_metadata_plan['data']['author_id'] ?? 0, 'resolve-post-metadata-plan normalizes author_id handoff' );
npcink_abilities_toolkit_assert_same( 'landing.php', $resolved_metadata_plan['data']['template'] ?? '', 'resolve-post-metadata-plan preserves template handoff' );
npcink_abilities_toolkit_assert_same( 'image', $resolved_metadata_plan['data']['format'] ?? '', 'resolve-post-metadata-plan normalizes format handoff' );
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
npcink_abilities_toolkit_assert_same( true, $inline_blocks['success'] ?? null, 'build-inline-image-blocks returns a success envelope' );
npcink_abilities_toolkit_assert_same( 1, $inline_blocks['data']['summary']['count'] ?? 0, 'build-inline-image-blocks counts generated blocks' );
npcink_abilities_toolkit_assert_same( 'core/image', $inline_blocks['data']['blocks'][0]['blockName'] ?? '', 'build-inline-image-blocks emits Gutenberg image blocks' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit-inline-image alpha-hero', $inline_blocks['data']['blocks'][0]['attrs']['className'] ?? '', 'build-inline-image-blocks preserves placement class key' );
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
npcink_abilities_toolkit_assert_same( true, $media_assets['success'] ?? null, 'build-media-seo-assets returns a success envelope' );
npcink_abilities_toolkit_assert_same( 2, $media_assets['data']['summary']['asset_count'] ?? 0, 'build-media-seo-assets counts featured plus inline assets' );
npcink_abilities_toolkit_assert_same( 'ai_generated', $media_assets['data']['items'][0]['image_origin'] ?? '', 'build-media-seo-assets preserves generated image origin' );
npcink_abilities_toolkit_assert_same( 'ai_generated', $media_assets['data']['items'][0]['source_type'] ?? '', 'build-media-seo-assets maps generated images to ai_generated source type' );
npcink_abilities_toolkit_assert_same( 'AI-generated by site operator', $media_assets['data']['items'][0]['attribution_text'] ?? '', 'build-media-seo-assets adds generated image attribution default' );
npcink_abilities_toolkit_assert_same( 'Generated asset for this site', $media_assets['data']['items'][0]['copyright_notice'] ?? '', 'build-media-seo-assets adds generated image copyright default' );
npcink_abilities_toolkit_assert_same( 'public_free', $media_assets['data']['items'][1]['image_origin'] ?? '', 'build-media-seo-assets infers public-free provider origin' );
npcink_abilities_toolkit_assert_same( 'stock', $media_assets['data']['items'][1]['source_type'] ?? '', 'build-media-seo-assets maps public-free provider images to stock source type' );
$geo_analysis = $core_read_package->geo_analyze(
	array(
		'title' => 'WordPress AI visibility',
		'content' => 'WordPress AI visibility 是什么？It helps answer boxes reuse concise article sections. Teams should add FAQ blocks and direct summaries.',
		'excerpt' => '',
		'focus_keyword' => 'AI visibility',
	)
);
npcink_abilities_toolkit_assert_same( true, $geo_analysis['success'] ?? null, 'geo-analyze returns a success envelope' );
npcink_abilities_toolkit_assert_same( 1, $geo_analysis['data']['summary']['faq_candidate_count'] ?? 0, 'geo-analyze extracts question candidates' );
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
npcink_abilities_toolkit_assert_same( true, $optimized_media['success'] ?? null, 'optimize-media-metadata returns a success envelope' );
npcink_abilities_toolkit_assert_same( 1, $optimized_media['data']['summary']['missing_alt_count'] ?? 0, 'optimize-media-metadata counts missing alt text' );
npcink_abilities_toolkit_assert_same( 'owned', $optimized_media['data']['assets'][0]['suggestions']['source_type'] ?? '', 'optimize-media-metadata includes source_type in media detail suggestions' );
npcink_abilities_toolkit_assert_same( 'Owned asset for this site', $optimized_media['data']['assets'][0]['suggestions']['copyright_notice'] ?? '', 'optimize-media-metadata adds owned media copyright default' );
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
npcink_abilities_toolkit_assert_same( true, $positioned_blocks['success'] ?? null, 'position-inline-image-blocks returns a success envelope' );
npcink_abilities_toolkit_assert_same( 1, $positioned_blocks['data']['summary']['positioned_count'] ?? 0, 'position-inline-image-blocks positions matching inline blocks' );
npcink_abilities_toolkit_assert_same( 'core/image', $positioned_blocks['data']['blocks'][2]['blockName'] ?? '', 'position-inline-image-blocks inserts after matching heading' );
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
npcink_abilities_toolkit_assert_same( true, $article_production_fingerprint['success'] ?? null, 'build-article-production-fingerprint returns a success envelope' );
npcink_abilities_toolkit_assert_same( 16, strlen( (string) ( $article_production_fingerprint['data']['production_fingerprint'] ?? '' ) ), 'build-article-production-fingerprint emits a compact 16-character hash' );
npcink_abilities_toolkit_assert_same(
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
npcink_abilities_toolkit_assert_same( true, $article_duplicate_check['success'] ?? null, 'check-article-production-duplicate returns a success envelope' );
npcink_abilities_toolkit_assert_same( false, $article_duplicate_check['data']['duplicate_found'] ?? null, 'check-article-production-duplicate stays readonly when no WordPress lookup is available' );
npcink_abilities_toolkit_assert_same( false, $article_duplicate_check['data']['skip_recommended'] ?? null, 'check-article-production-duplicate does not recommend skipping without a duplicate' );
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
npcink_abilities_toolkit_assert_same( true, $article_review_light['success'] ?? null, 'review-article-output-light returns a success envelope' );
npcink_abilities_toolkit_assert_same( true, $article_review_data['needs_human_review'] ?? null, 'review-article-output-light flags template-heavy output for review' );
npcink_abilities_toolkit_assert_same( 'high', $article_review_data['template_risk_level'] ?? '', 'review-article-output-light preserves high template risk semantics' );
npcink_abilities_toolkit_assert_true( in_array( 'human_signal_gap', $article_ai_risk_keys, true ), 'review-article-output-light reports missing human signal risk' );
npcink_abilities_toolkit_assert_true( in_array( 'evidence_gap', $article_ai_risk_keys, true ), 'review-article-output-light reports missing evidence risk' );
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
npcink_abilities_toolkit_assert_same( false, $reference_style_review['data']['needs_human_review'] ?? null, 'review-article-output-light relaxes missing evidence risks when reference style strongly matches' );
npcink_abilities_toolkit_assert_same( 'ready_for_editorial_review', $reference_style_review['data']['next_action'] ?? '', 'review-article-output-light keeps matching reference-style output reviewable' );
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
npcink_abilities_toolkit_assert_same( true, $production_result['success'] ?? null, 'compose-article-production-result returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'degraded', $production_data['result_mode'] ?? '', 'compose-article-production-result exposes degraded result semantics' );
npcink_abilities_toolkit_assert_true( in_array( 'inline_position_partial_fallback', (array) ( $production_data['degraded_reasons'] ?? array() ), true ), 'compose-article-production-result preserves inline fallback degradation' );
npcink_abilities_toolkit_assert_true( in_array( 'duplicate_production_candidate', (array) ( $production_data['degraded_reasons'] ?? array() ), true ), 'compose-article-production-result preserves duplicate guard degradation' );
npcink_abilities_toolkit_assert_same( 'abc123def4567890', $production_data['production_fingerprint'] ?? '', 'compose-article-production-result preserves duplicate fingerprint' );
npcink_abilities_toolkit_assert_same( true, $production_data['skip_recommended'] ?? null, 'compose-article-production-result preserves duplicate skip recommendation' );
npcink_abilities_toolkit_assert_true( in_array( 'duplicate_candidate_detected', (array) ( $production_data['completed_stages'] ?? array() ), true ), 'compose-article-production-result records duplicate candidate stage' );
npcink_abilities_toolkit_assert_true( false !== strpos( (string) ( $production_data['summary_text'] ?? '' ), '检测到可复用旧稿' ), 'compose-article-production-result summarizes duplicate reuse handoff' );
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
npcink_abilities_toolkit_assert_same(
	(string) ( $article_production_fingerprint['data']['production_fingerprint'] ?? '' ),
	(string) ( $production_result_fallback['data']['production_fingerprint'] ?? '' ),
	'compose-article-production-result fills a stable fingerprint when duplicate guard omits one'
);
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'] = array(
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
$GLOBALS['npcink_abilities_toolkit_unit_transients'] = array();
$reference_style = $core_read_package->extract_reference_post_style(
	array(
		'reference_post_ids' => array( 11, 12 ),
	)
);
npcink_abilities_toolkit_assert_same( true, $reference_style['success'] ?? null, 'extract-reference-post-style returns a success envelope' );
npcink_abilities_toolkit_assert_same( 2, $reference_style['data']['profile']['sample_count'] ?? null, 'extract-reference-post-style profiles two reference samples' );
npcink_abilities_toolkit_assert_same( 'scene', $reference_style['data']['profile']['dominant_opening_style'] ?? '', 'extract-reference-post-style detects scene-led openings' );
npcink_abilities_toolkit_assert_same( 'action', $reference_style['data']['profile']['dominant_ending_style'] ?? '', 'extract-reference-post-style detects action endings' );
npcink_abilities_toolkit_assert_true( false !== strpos( (string) ( $reference_style['data']['profile']['style_brief'] ?? '' ), '段落平均长度约' ), 'extract-reference-post-style returns a compact style brief' );
$GLOBALS['npcink_abilities_toolkit_unit_transients'] = array();
$author_baseline = $core_read_package->extract_style_baseline(
	array(
		'mode'      => 'author_recent',
		'author_id' => 7,
		'limit'     => 4,
	)
);
npcink_abilities_toolkit_assert_same( true, $author_baseline['success'] ?? null, 'extract-style-baseline returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'author_recent', $author_baseline['data']['source'] ?? '', 'extract-style-baseline keeps author source when author samples exist' );
npcink_abilities_toolkit_assert_same( 2, $author_baseline['data']['profile']['sample_count'] ?? null, 'extract-style-baseline profiles author samples' );
$GLOBALS['npcink_abilities_toolkit_unit_transients'] = array();
$site_baseline = $core_read_package->extract_style_baseline(
	array(
		'mode'  => 'site_recent',
		'limit' => 3,
	)
);
npcink_abilities_toolkit_assert_same( 'site_recent', $site_baseline['data']['source'] ?? '', 'extract-style-baseline keeps site source for site baselines' );
npcink_abilities_toolkit_assert_same( 3, $site_baseline['data']['profile']['sample_count'] ?? null, 'extract-style-baseline profiles site samples' );
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
npcink_abilities_toolkit_assert_same( true, $optimization_report['success'] ?? null, 'build-article-optimization-report returns a success envelope' );
npcink_abilities_toolkit_assert_same( 42, $optimization_report['data']['post']['post_id'] ?? null, 'build-article-optimization-report normalizes post id' );
npcink_abilities_toolkit_assert_same( 'needs_attention', $optimization_report['data']['summary']['status'] ?? '', 'build-article-optimization-report marks high-priority reports for attention' );
npcink_abilities_toolkit_assert_same( 4, $optimization_report['data']['summary']['total_recommendations'] ?? null, 'build-article-optimization-report merges SEO, GEO, internal link, and media recommendations' );
$seo_report_context = $core_read_package->build_seo_report_context(
	array(
		'input'         => '<p>这是一段偏短的正文，用来触发内容深度建议。</p>',
		'focus_keyword' => '本地能力包',
	)
);
npcink_abilities_toolkit_assert_same( true, $seo_report_context['success'] ?? null, 'seo-report-context returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'local_seo_report_context', $seo_report_context['meta']['source'] ?? '', 'seo-report-context records deterministic source metadata' );
npcink_abilities_toolkit_assert_same( 1, $seo_report_context['data']['summary']['high_priority_count'] ?? null, 'seo-report-context counts missing focus keyword as high priority' );
npcink_abilities_toolkit_assert_true( 88 > (int) ( $seo_report_context['data']['score'] ?? 88 ), 'seo-report-context lowers score when checks fail' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][77] = (object) array(
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
npcink_abilities_toolkit_assert_same( true, $post_context['success'] ?? null, 'read-post-optimization-context returns a success envelope' );
npcink_abilities_toolkit_assert_same( 77, $post_context['data']['id'] ?? null, 'read-post-optimization-context reads the requested post id' );
npcink_abilities_toolkit_assert_same( 'optimization-context-post', $post_context['data']['slug'] ?? '', 'read-post-optimization-context exposes slug for optimization workflows' );
npcink_abilities_toolkit_assert_same( 'standard', $post_context['data']['format'] ?? '', 'read-post-optimization-context defaults empty post format to standard' );
$GLOBALS['npcink_abilities_toolkit_unit_post_meta'][77]['_yoast_wpseo_title'] = 'Optimization SEO Title';
$GLOBALS['npcink_abilities_toolkit_unit_post_meta'][77]['_yoast_wpseo_metadesc'] = 'Optimization SEO Description';
$agent_post_context = $core_read_package->get_post_context(
	array(
		'post_id'      => 77,
		'include_meta' => true,
		'meta_keys'    => array( '_yoast_wpseo_title' ),
	)
);
npcink_abilities_toolkit_assert_same( true, $agent_post_context['success'] ?? null, 'get-post-context returns a success envelope' );
npcink_abilities_toolkit_assert_same( 77, $agent_post_context['data']['post']['id'] ?? null, 'get-post-context reads the requested post id' );
npcink_abilities_toolkit_assert_same( 'Optimization context content.', $agent_post_context['data']['post']['content'] ?? '', 'get-post-context includes post content by default' );
npcink_abilities_toolkit_assert_same( 1, $agent_post_context['data']['stats']['block_count'] ?? null, 'get-post-context falls back to a freeform block for plain content' );
npcink_abilities_toolkit_assert_same( 'Optimization SEO Title', $agent_post_context['data']['meta']['_yoast_wpseo_title'] ?? '', 'get-post-context supports scoped metadata reads' );
$publishing_checklist = $core_read_package->get_content_publishing_checklist(
	array(
		'post_id' => 77,
	)
);
npcink_abilities_toolkit_assert_same( true, $publishing_checklist['success'] ?? null, 'get-content-publishing-checklist returns a success envelope' );
npcink_abilities_toolkit_assert_same( false, $publishing_checklist['data']['ready'] ?? null, 'get-content-publishing-checklist blocks thin content from ready state' );
npcink_abilities_toolkit_assert_true( in_array( 'content', $publishing_checklist['data']['missing'] ?? array(), true ), 'get-content-publishing-checklist reports missing content depth' );
npcink_abilities_toolkit_assert_true( in_array( 'excerpt', $publishing_checklist['data']['warnings'] ?? array(), true ), 'get-content-publishing-checklist reports missing excerpt as warning' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][78] = (object) array(
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
npcink_abilities_toolkit_assert_same( true, $inventory_health['success'] ?? null, 'get-content-inventory-health returns a success envelope' );
npcink_abilities_toolkit_assert_true( (int) ( $inventory_health['data']['summary']['scanned_count'] ?? 0 ) > 0, 'get-content-inventory-health scans bounded inventory rows' );
npcink_abilities_toolkit_assert_true( isset( $inventory_health['data']['health_score'] ), 'get-content-inventory-health returns a health score' );
$inventory_health_cached = $core_read_package->get_content_inventory_health(
	array(
		'post_type' => 'post',
		'status'    => 'any',
		'per_page'  => 5,
		'page'      => 1,
	)
);
npcink_abilities_toolkit_assert_same( true, $inventory_health_cached['meta']['cache_hit'] ?? null, 'get-content-inventory-health uses the bounded read cache on repeated calls' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][79] = (object) array(
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
$test_inventory = $core_read_package->get_nonproduction_content_inventory(
	array(
		'patterns' => array( 'Core Governance' ),
		'per_page' => 10,
	)
);
npcink_abilities_toolkit_assert_same( true, $test_inventory['success'] ?? null, 'get-nonproduction-content-inventory returns a success envelope' );
npcink_abilities_toolkit_assert_same( true, $test_inventory['data']['detected'] ?? null, 'get-nonproduction-content-inventory detects matching smoke content' );
npcink_abilities_toolkit_assert_same( 'Core Governance', $test_inventory['data']['posts']['items'][0]['matched_pattern'] ?? '', 'get-nonproduction-content-inventory returns matched pattern' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][81] = (object) array(
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
$default_test_inventory = $core_read_package->get_nonproduction_content_inventory(
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
npcink_abilities_toolkit_assert_same( true, $default_test_inventory['data']['detected'] ?? null, 'get-nonproduction-content-inventory detects Core Plan Bridge fixtures by default' );
npcink_abilities_toolkit_assert_same( 'core plan bridge content candidate', $default_plan_bridge_match['matched_pattern'] ?? '', 'get-nonproduction-content-inventory includes Core Plan Bridge fixture patterns by default' );
$cleanup_plan = $core_read_package->build_nonproduction_content_cleanup_plan(
	array(
		'patterns'    => array( 'Core Governance' ),
		'max_actions' => 5,
	)
);
npcink_abilities_toolkit_assert_same( true, $cleanup_plan['success'] ?? null, 'build-nonproduction-content-cleanup-plan returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'batch', $cleanup_plan['data']['proposal_mode'] ?? '', 'nonproduction content cleanup plan requests batch proposal intake' );
npcink_abilities_toolkit_assert_same( true, $cleanup_plan['data']['batch_approval'] ?? null, 'nonproduction content cleanup plan requests one approval for the generated action batch' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/trash-post', $cleanup_plan['data']['write_actions'][0]['target_ability_id'] ?? '', 'nonproduction content cleanup plan reuses trash-post' );
npcink_abilities_toolkit_assert_same( false, $cleanup_plan['data']['write_actions'][0]['commit_execution'] ?? null, 'nonproduction content cleanup plan does not execute commits' );
$default_cleanup_plan = $core_read_package->build_nonproduction_content_cleanup_plan(
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
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/trash-post', $default_cleanup_plan_bridge_action['target_ability_id'] ?? '', 'nonproduction content cleanup plan includes Core Plan Bridge fixture posts by default' );
$terms_only_cleanup_plan = $core_read_package->build_nonproduction_content_cleanup_plan(
	array(
		'include_posts'    => false,
		'include_terms'    => false,
		'include_comments' => false,
		'max_actions'      => 5,
	)
);
npcink_abilities_toolkit_assert_same( 0, count( (array) ( $terms_only_cleanup_plan['data']['preview']['posts'] ?? array() ) ), 'nonproduction content cleanup plan honors include_posts=false' );
npcink_abilities_toolkit_assert_same( 0, count( (array) ( $terms_only_cleanup_plan['data']['write_actions'] ?? array() ) ), 'nonproduction content cleanup plan does not generate post actions when include_posts=false' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][80] = (object) array(
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
npcink_abilities_toolkit_assert_same( true, $content_fix_plan['success'] ?? null, 'build-content-inventory-fix-plan returns a success envelope' );
npcink_abilities_toolkit_assert_same( true, $content_fix_plan['data']['requires_approval'] ?? null, 'content inventory fix plan requires approval' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/set-post-seo-meta', $content_fix_plan['data']['write_actions'][0]['target_ability_id'] ?? '', 'content inventory fix plan reuses SEO write ability' );
npcink_abilities_toolkit_assert_same( false, $content_fix_plan['data']['write_actions'][0]['commit_execution'] ?? null, 'content inventory fix plan does not execute commits' );
npcink_abilities_toolkit_assert_true( isset( $content_fix_plan['data']['preview'][0]['before']['seo_title'] ), 'content inventory fix plan returns before preview' );
npcink_abilities_toolkit_assert_true( isset( $content_fix_plan['data']['preview'][0]['after_suggestion']['seo_title'] ), 'content inventory fix plan returns after suggestion preview' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][81] = (object) array(
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
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/update-post', $title_fix_plan['data']['write_actions'][0]['target_ability_id'] ?? '', 'content inventory fix plan maps missing title to update-post' );
npcink_abilities_toolkit_assert_same( array( 'title' ), $title_fix_plan['data']['write_actions'][0]['requires_input'] ?? array(), 'content inventory title plan requires a reviewed title input' );
$bulk_checklist = $core_read_package->get_bulk_publishing_checklist(
	array(
		'post_ids' => array( 77, 78, 77 ),
	)
);
npcink_abilities_toolkit_assert_same( true, $bulk_checklist['success'] ?? null, 'get-bulk-publishing-checklist returns a success envelope' );
npcink_abilities_toolkit_assert_same( 2, $bulk_checklist['data']['total'] ?? null, 'get-bulk-publishing-checklist deduplicates post ids' );
npcink_abilities_toolkit_assert_true( (int) ( $bulk_checklist['data']['blocked_count'] ?? 0 ) >= 1, 'get-bulk-publishing-checklist counts blocked posts' );
$internal_link_report = $core_read_package->get_internal_link_opportunity_report(
	array(
		'post_id'       => 77,
		'focus_keyword' => 'workflow',
		'max_targets'   => 3,
	)
);
npcink_abilities_toolkit_assert_same( true, $internal_link_report['success'] ?? null, 'get-internal-link-opportunity-report returns a success envelope' );
npcink_abilities_toolkit_assert_same( 77, $internal_link_report['data']['source_post']['post_id'] ?? null, 'get-internal-link-opportunity-report keeps source post id' );
npcink_abilities_toolkit_assert_true( (int) ( $internal_link_report['data']['summary']['candidate_count'] ?? 0 ) >= 1, 'get-internal-link-opportunity-report finds local candidate posts in isolated tests' );
$GLOBALS['npcink_abilities_toolkit_unit_comments'][21] = (object) array(
	'comment_ID'       => 21,
	'comment_post_ID'  => 77,
	'comment_author'   => 'Ops Reader',
	'comment_approved' => 'hold',
	'comment_content'  => 'Please review this operations comment.',
);
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
	),
);
$site_operations = $core_read_package->get_site_operations_dashboard(
	array(
		'post_type' => 'post',
		'per_page'  => 5,
	)
);
npcink_abilities_toolkit_assert_same( true, $site_operations['success'] ?? null, 'get-site-operations-dashboard returns a success envelope' );
npcink_abilities_toolkit_assert_true( isset( $site_operations['data']['status_counts']['draft'] ), 'get-site-operations-dashboard returns status counts' );
npcink_abilities_toolkit_assert_true( (int) ( $site_operations['data']['comments']['pending_count'] ?? 0 ) >= 1, 'get-site-operations-dashboard counts pending comments' );
$publish_risk = $core_read_package->get_post_publish_risk_report(
	array(
		'post_id'       => 77,
		'focus_keyword' => 'workflow',
	)
);
npcink_abilities_toolkit_assert_same( true, $publish_risk['success'] ?? null, 'get-post-publish-risk-report returns a success envelope' );
npcink_abilities_toolkit_assert_same( 77, $publish_risk['data']['post']['post_id'] ?? null, 'get-post-publish-risk-report keeps post id' );
npcink_abilities_toolkit_assert_true( (int) ( $publish_risk['data']['risk_score'] ?? 0 ) > 0, 'get-post-publish-risk-report returns a positive risk score for incomplete drafts' );
$article_publish_preflight = $core_read_package->get_article_publish_preflight_context(
	array(
		'post_id'       => 77,
		'focus_keyword' => 'workflow',
		'window_days'   => 30,
	)
);
npcink_abilities_toolkit_assert_same( true, $article_publish_preflight['success'] ?? null, 'get-article-publish-preflight-context returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'workflow/wordpress_article_publish_preflight', $article_publish_preflight['data']['recipe'] ?? '', 'get-article-publish-preflight-context declares its recipe id' );
npcink_abilities_toolkit_assert_true( in_array( 'publish_risk', $article_publish_preflight['data']['sections'] ?? array(), true ), 'get-article-publish-preflight-context includes publish risk' );
$refresh_opportunities = $core_read_package->get_content_refresh_opportunities(
	array(
		'post_type'      => 'post',
		'status'         => 'any',
		'per_page'       => 5,
		'stale_days'     => 30,
		'min_word_count' => 200,
	)
);
npcink_abilities_toolkit_assert_same( true, $refresh_opportunities['success'] ?? null, 'get-content-refresh-opportunities returns a success envelope' );
npcink_abilities_toolkit_assert_true( (int) ( $refresh_opportunities['data']['summary']['opportunity_count'] ?? 0 ) >= 1, 'get-content-refresh-opportunities finds refresh candidates' );
npcink_abilities_toolkit_assert_true( isset( $refresh_opportunities['data']['issue_counts']['thin_content'] ), 'get-content-refresh-opportunities counts thin content' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][77]->post_content = 'Optimization context content with <a href="https://example.test/?p=78">workflow candidate</a>.';
$internal_link_graph = $core_read_package->get_internal_link_graph_health(
	array(
		'post_type' => 'post',
		'status'    => 'any',
		'per_page'  => 5,
	)
);
npcink_abilities_toolkit_assert_same( true, $internal_link_graph['success'] ?? null, 'get-internal-link-graph-health returns a success envelope' );
npcink_abilities_toolkit_assert_true( (int) ( $internal_link_graph['data']['summary']['scanned_count'] ?? 0 ) >= 1, 'get-internal-link-graph-health scans local posts' );
npcink_abilities_toolkit_assert_true( isset( $internal_link_graph['data']['issue_counts']['orphan_post'] ), 'get-internal-link-graph-health counts orphan posts' );
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
npcink_abilities_toolkit_assert_same( true, $old_article_refresh['success'] ?? null, 'get-old-article-refresh-context returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'workflow/wordpress_old_article_refresh_discovery', $old_article_refresh['data']['recipe'] ?? '', 'get-old-article-refresh-context declares its recipe id' );
npcink_abilities_toolkit_assert_true( in_array( 'seo_geo_gap_report', $old_article_refresh['data']['sections'] ?? array(), true ), 'get-old-article-refresh-context includes SEO/GEO gaps' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][79] = (object) array(
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
		'width'          => 2600,
		'height'         => 1400,
		'file'           => '2026/06/workflow-diagram-image.jpg',
		'filesize'       => 900000,
		'original_image' => 'workflow-diagram-image-original.jpg',
		'sizes'          => array(
			'medium' => array(
				'file'   => 'workflow-diagram-image-300x162.jpg',
				'width'  => 300,
				'height' => 162,
			),
		),
	)
);
update_post_meta( 79, '_wp_attached_file', '2026/06/workflow-diagram-image.jpg' );
$GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] = sys_get_temp_dir() . '/npcink-abilities-toolkit-media-' . getmypid();
$workflow_media_path = $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/2026/06/workflow-diagram-image.jpg';
if ( ! is_dir( dirname( $workflow_media_path ) ) ) {
	mkdir( dirname( $workflow_media_path ), 0755, true );
}
file_put_contents( $workflow_media_path, 'original-jpeg-bytes' );
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
$media_cloud_request = $core_read_package->build_media_derivative_cloud_request(
	array(
		'attachment_id'              => 79,
		'target_max_width'           => 1920,
		'large_file_threshold_bytes' => 524288,
		'preferred_format'           => 'webp',
		'quality'                    => 82,
	)
);
npcink_abilities_toolkit_assert_same( true, $media_cloud_request['success'] ?? null, 'build-media-derivative-cloud-request returns a success envelope' );
npcink_abilities_toolkit_assert_same( true, $media_cloud_request['data']['readonly'] ?? null, 'media derivative cloud request is read-only' );
npcink_abilities_toolkit_assert_same( 'media_derivative_cloud_request.v1', $media_cloud_request['data']['request_contract_version'] ?? '', 'media derivative cloud request exposes a versioned contract' );
npcink_abilities_toolkit_assert_same( 'generate_optimized_media_derivative', $media_cloud_request['data']['cloud_job_payload']['job_type'] ?? '', 'media derivative cloud request targets derivative generation' );
npcink_abilities_toolkit_assert_same( 'webp', $media_cloud_request['data']['cloud_job_payload']['target_format'] ?? '', 'media derivative cloud request exposes Cloud target format' );
npcink_abilities_toolkit_assert_same( 1920, $media_cloud_request['data']['cloud_job_payload']['max_width'] ?? 0, 'media derivative cloud request exposes Cloud max width' );
npcink_abilities_toolkit_assert_same( 82, $media_cloud_request['data']['cloud_job_payload']['quality'] ?? 0, 'media derivative cloud request exposes Cloud quality' );
npcink_abilities_toolkit_assert_same( 'webp', $media_cloud_request['data']['cloud_job_payload']['requested_derivative']['format'] ?? '', 'media derivative cloud request preserves preferred format' );
npcink_abilities_toolkit_assert_same( 1920, $media_cloud_request['data']['cloud_job_payload']['requested_derivative']['max_width'] ?? 0, 'media derivative cloud request preserves target max width' );
npcink_abilities_toolkit_assert_same( true, $media_cloud_request['data']['cloud_execution']['source_upload_required'] ?? null, 'media derivative cloud request requires host-provided source upload' );
npcink_abilities_toolkit_assert_same( false, $media_cloud_request['data']['cloud_execution']['credentials_included'] ?? null, 'media derivative cloud request does not include credentials' );
npcink_abilities_toolkit_assert_same( false, $media_cloud_request['data']['cloud_execution']['authorization_included'] ?? null, 'media derivative cloud request does not include authorization headers' );
npcink_abilities_toolkit_assert_same( false, $media_cloud_request['data']['cloud_execution']['signed_headers_included'] ?? null, 'media derivative cloud request does not include signed headers' );
npcink_abilities_toolkit_assert_same( 'local_wordpress_host', $media_cloud_request['data']['local_adoption']['final_write_owner'] ?? '', 'media derivative cloud request leaves final writes local' );
npcink_abilities_toolkit_assert_same( false, $media_cloud_request['data']['local_adoption']['wordpress_write_included'] ?? null, 'media derivative cloud request does not write WordPress' );
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
npcink_abilities_toolkit_assert_same( true, $media_cloud_request_with_watermark['success'] ?? null, 'build-media-derivative-cloud-request accepts optional image watermark plans' );
npcink_abilities_toolkit_assert_same( 'png', $media_cloud_request_with_watermark['data']['cloud_job_payload']['target_format'] ?? '', 'watermarked media derivative request exposes Cloud target format' );
npcink_abilities_toolkit_assert_same( 'image', $media_cloud_request_with_watermark['data']['cloud_job_payload']['watermark']['type'] ?? '', 'watermarked media derivative request preserves watermark type' );
npcink_abilities_toolkit_assert_same( 'artifact_logo_123', $media_cloud_request_with_watermark['data']['cloud_job_payload']['watermark']['artifact_id'] ?? '', 'watermarked media derivative request preserves watermark artifact reference' );
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
			'artifact_id'        => 'art_cloud_media_123',
			'expires_at'         => gmdate( 'c', time() + 600 ),
			'mime_type'          => 'image/webp',
			'format'             => 'webp',
			'width'              => 1600,
			'height'             => 862,
			'filesize_bytes'     => 210000,
			'suggested_filename' => 'workflow-diagram-cloud-plan.webp',
		),
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
		'derivative_artifact'           => array(
			'artifact_id'        => 'art_cloud_media_original_reference',
			'expires_at'         => gmdate( 'c', time() + 600 ),
			'mime_type'          => 'image/webp',
			'format'             => 'webp',
			'width'              => 1920,
			'height'             => 1080,
			'filesize_bytes'     => 126482,
			'suggested_filename' => 'photo-optimized.webp',
		),
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
npcink_abilities_toolkit_assert_true( false !== strpos( $core_write_package_source, 'npcink_abilities_toolkit_cloud_media_derivative_artifact_download' ), 'adopt-cloud-media-derivative exposes a bounded artifact download filter for integration smoke tests' );
npcink_abilities_toolkit_assert_true( false !== strpos( $core_write_package_source, 'npcink_abilities_toolkit_media_file_write_blocked' ), 'media write execution exposes a bounded failure-injection hook for verification tests' );
npcink_abilities_toolkit_assert_true( false !== strpos( $core_write_package_source, 'npcink_abilities_toolkit_media_file_copy_blocked' ), 'media copy execution exposes a bounded failure-injection hook for verification tests' );
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
$media_derivative_batch_plan = $core_read_package->build_media_derivative_batch_plan(
	array(
		'date_from'     => '2026-04-01',
		'date_to'       => '2026-04-30 23:59:59',
		'target_format' => 'png',
		'max_items'     => 10,
	)
);
npcink_abilities_toolkit_assert_same( true, $media_derivative_batch_plan['success'] ?? null, 'media derivative batch plan returns a success envelope' );
npcink_abilities_toolkit_assert_same( true, $media_derivative_batch_plan['data']['readonly'] ?? null, 'media derivative batch plan is read-only' );
npcink_abilities_toolkit_assert_same( 'dry_run', $media_derivative_batch_plan['data']['plan_mode'] ?? '', 'media derivative batch plan returns a dry-run plan mode' );
npcink_abilities_toolkit_assert_same( false, $media_derivative_batch_plan['data']['commit_execution'] ?? null, 'media derivative batch plan does not execute commits' );
npcink_abilities_toolkit_assert_same( true, $media_derivative_batch_plan['data']['requires_approval'] ?? null, 'media derivative batch plan requires approval before adoption' );
npcink_abilities_toolkit_assert_same( 1, $media_derivative_batch_plan['data']['summary']['candidate_count'] ?? 0, 'media derivative batch plan selects one April JPEG candidate for PNG conversion' );
npcink_abilities_toolkit_assert_same( 84, $media_derivative_batch_plan['data']['candidates'][0]['attachment_id'] ?? 0, 'media derivative batch plan candidate comes from the April date range' );
npcink_abilities_toolkit_assert_same( 'png', $media_derivative_batch_plan['data']['candidates'][0]['cloud_request_input']['preferred_format'] ?? '', 'media derivative batch plan prepares PNG single-image request input' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/build-media-derivative-cloud-request', $media_derivative_batch_plan['data']['candidates'][0]['cloud_request_ability'] ?? '', 'media derivative batch plan points to the existing single-image cloud request ability' );
npcink_abilities_toolkit_assert_same( 'already_target_format', $media_derivative_batch_plan['data']['skipped'][0]['reason'] ?? '', 'media derivative batch plan skips images already in the target format' );
npcink_abilities_toolkit_assert_array_omits_keys( $media_derivative_batch_plan['data'], array( 'write_actions', 'wordpress_write_decision', 'approval_decision', 'commit' ), 'media derivative batch plan output' );
$media_derivative_batch_plan_bounded = $core_read_package->build_media_derivative_batch_plan(
	array(
		'attachment_ids' => array( 84, 86 ),
		'target_format'  => 'png',
		'max_items'      => 1,
	)
);
npcink_abilities_toolkit_assert_same( 1, $media_derivative_batch_plan_bounded['data']['summary']['candidate_count'] ?? 0, 'media derivative batch plan enforces max_items' );
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
npcink_abilities_toolkit_assert_same( true, $media_derivative_batch_plan_text_watermark['success'] ?? null, 'media derivative batch plan accepts text watermark input' );
npcink_abilities_toolkit_assert_same( 'text', $media_derivative_batch_plan_text_watermark['data']['candidates'][0]['cloud_request_input']['watermark']['type'] ?? '', 'media derivative batch plan carries text watermark requests into candidate cloud inputs' );
npcink_abilities_toolkit_assert_same( 'AI', $media_derivative_batch_plan_text_watermark['data']['candidates'][0]['cloud_request_input']['watermark']['text'] ?? '', 'media derivative batch plan carries text watermark content into candidate cloud inputs' );
npcink_abilities_toolkit_assert_true( ! isset( $media_derivative_batch_plan_text_watermark['data']['candidates'][0]['cloud_request_input']['watermark']['artifact_id'] ), 'media derivative batch plan text watermark does not require an artifact id' );
$media_derivative_batch_plan_excluded = $core_read_package->build_media_derivative_batch_plan(
	array(
		'attachment_ids'    => array( 84 ),
		'target_format'     => 'png',
		'exclude_formats'   => array( 'jpeg' ),
	)
);
npcink_abilities_toolkit_assert_same( 0, $media_derivative_batch_plan_excluded['data']['summary']['candidate_count'] ?? 1, 'media derivative batch plan honors excluded source formats' );
npcink_abilities_toolkit_assert_same( 'source_format_excluded', $media_derivative_batch_plan_excluded['data']['skipped'][0]['reason'] ?? '', 'media derivative batch plan explains excluded source formats' );
$media_derivative_batch_plan_invalid = $core_read_package->build_media_derivative_batch_plan(
	array(
		'attachment_ids' => array( 84 ),
		'target_format'  => 'tiff',
	)
);
npcink_abilities_toolkit_assert_true( is_wp_error( $media_derivative_batch_plan_invalid ) && 'npcink_abilities_toolkit_media_derivative_target_format_invalid' === $media_derivative_batch_plan_invalid->get_error_code(), 'media derivative batch plan rejects invalid target formats' );
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
$cloud_artifact_contents = 'cloud-webp-derivative-bytes';
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
npcink_abilities_toolkit_assert_same( true, $cloud_adoption_preview['dry_run'] ?? null, 'adopt-cloud-media-derivative defaults to dry-run preview' );
npcink_abilities_toolkit_assert_same( false, $cloud_adoption_preview['replaced'] ?? null, 'adopt-cloud-media-derivative dry-run does not switch files' );
npcink_abilities_toolkit_assert_same( 'art_cloud_media_123', $cloud_adoption_preview['artifact']['artifact_id'] ?? '', 'adopt-cloud-media-derivative preserves artifact evidence' );
npcink_abilities_toolkit_assert_true( false !== strpos( (string) ( $cloud_adoption_preview['after']['relative_file'] ?? '' ), 'workflow-diagram-image-npcink-abilities-toolkit-cloud-' ), 'adopt-cloud-media-derivative plans a local derivative filename' );
npcink_abilities_toolkit_assert_same( 1, $cloud_adoption_preview['content_reference_repairs']['post_count'] ?? 0, 'adopt-cloud-media-derivative previews post content reference repairs for embedded attachment URLs' );
npcink_abilities_toolkit_assert_true( (int) ( $cloud_adoption_preview['content_reference_repairs']['replacement_count'] ?? 0 ) >= 3, 'adopt-cloud-media-derivative preview includes old main, sized, and original image references' );
$cloud_adoption_expected_post_ids = array_values( array_map( 'absint', array_column( (array) ( $cloud_adoption_preview['content_reference_repairs']['repairs'] ?? array() ), 'post_id' ) ) );
$cloud_adoption_expected_post_count = absint( $cloud_adoption_preview['content_reference_repairs']['post_count'] ?? 0 );
$cloud_adoption_expected_replacement_count = absint( $cloud_adoption_preview['content_reference_repairs']['replacement_count'] ?? 0 );
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
npcink_abilities_toolkit_assert_same( 'cloud-suggested-file.webp', $cloud_suggested_filename_preview['proposed_filename'] ?? '', 'adopt-cloud-media-derivative adopts a sanitized Cloud suggested filename as local proposal evidence' );
npcink_abilities_toolkit_assert_same( 'cloud_artifact_suggestion', $cloud_suggested_filename_preview['filename_policy']['source'] ?? '', 'adopt-cloud-media-derivative marks Cloud filenames as suggestions, not write decisions' );
npcink_abilities_toolkit_assert_same( true, $cloud_suggested_filename_preview['filename_policy']['final_sanitize_unique_required'] ?? null, 'adopt-cloud-media-derivative requires final WordPress-side filename finalization' );
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
npcink_abilities_toolkit_assert_true( is_wp_error( $expired_cloud_adoption ) && 'npcink_abilities_toolkit_cloud_artifact_expired' === $expired_cloud_adoption->get_error_code(), 'adopt-cloud-media-derivative rejects expired artifacts' );
$GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] = sys_get_temp_dir() . '/npcink-abilities-toolkit-cloud-adoption-' . getmypid();
$current_media_path = $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/2026/06/workflow-diagram-image.jpg';
mkdir( dirname( $current_media_path ), 0755, true );
file_put_contents( $current_media_path, 'original-jpeg-bytes' );
$cloud_artifact_download_count = 0;
$GLOBALS['npcink_abilities_toolkit_unit_cloud_artifact_download_callback'] = static function ( array $artifact ) use ( $cloud_artifact_contents, $cloud_artifact_sha256, &$cloud_artifact_download_count ) {
	++$cloud_artifact_download_count;
	return array(
		'artifact_id'    => (string) ( $artifact['artifact_id'] ?? '' ),
		'contents'       => $cloud_artifact_contents,
		'mime_type'      => 'image/webp',
		'filesize_bytes' => strlen( $cloud_artifact_contents ),
		'sha256'         => $cloud_artifact_sha256,
		'expires_at'     => (string) ( $artifact['expires_at'] ?? '' ),
	);
};
$GLOBALS['npcink_ai_runtime_wp_ability_context']['context'] = array(
	'approval_commit_authorized' => true,
	'approval_id'                => 'approval-cloud-media-adoption',
);
$cloud_adoption_drift = $core_write_package->adopt_cloud_media_derivative(
	array(
		'attachment_id'                 => 79,
		'derivative_artifact'           => array(
			'artifact_id'    => 'art_cloud_media_drift',
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
		'expected_content_reference_post_ids' => array( 999 ),
		'expected_content_reference_post_count' => $cloud_adoption_expected_post_count,
		'expected_content_reference_replacement_count' => $cloud_adoption_expected_replacement_count,
		'commit'                       => true,
	)
);
npcink_abilities_toolkit_assert_true( is_wp_error( $cloud_adoption_drift ) && 'npcink_abilities_toolkit_media_reference_repair_expectation_mismatch' === $cloud_adoption_drift->get_error_code(), 'adopt-cloud-media-derivative blocks commit when reviewed content reference targets drift' );
npcink_abilities_toolkit_assert_same( 0, $cloud_artifact_download_count, 'adopt-cloud-media-derivative checks content reference drift before downloading the Cloud artifact' );
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
		'derivative_artifact'           => array(
			'artifact_id'    => 'art_cloud_media_write_failure',
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
		'derivative_artifact'           => array(
			'artifact_id'    => 'art_cloud_media_backup_failure',
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
		'expected_content_reference_post_ids' => $cloud_adoption_expected_post_ids,
		'expected_content_reference_post_count' => $cloud_adoption_expected_post_count,
		'expected_content_reference_replacement_count' => $cloud_adoption_expected_replacement_count,
		'commit'                       => true,
	)
);
unset( $GLOBALS['npcink_ai_runtime_wp_ability_context'], $GLOBALS['npcink_abilities_toolkit_unit_cloud_artifact_download_callback'] );
npcink_abilities_toolkit_assert_true( ! is_wp_error( $cloud_adoption_commit ), 'adopt-cloud-media-derivative commit succeeds after approval' . ( is_wp_error( $cloud_adoption_commit ) ? ': ' . $cloud_adoption_commit->get_error_code() : '' ) );
npcink_abilities_toolkit_assert_same( false, $cloud_adoption_commit['dry_run'] ?? null, 'adopt-cloud-media-derivative commit exits dry-run' );
npcink_abilities_toolkit_assert_same( true, $cloud_adoption_commit['replaced'] ?? null, 'adopt-cloud-media-derivative commit replaces the attachment pointer after approval' );
npcink_abilities_toolkit_assert_same( 'image/webp', get_post_mime_type( 79 ), 'adopt-cloud-media-derivative commit updates attachment MIME type' );
npcink_abilities_toolkit_assert_same( '2026/06/customer-approved-diagram.webp', get_post_meta( 79, '_wp_attached_file', true ), 'adopt-cloud-media-derivative commit accepts an approved custom derivative file name' );
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
npcink_abilities_toolkit_assert_same( true, $patch_setting_preview['dry_run'] ?? null, 'patch-setting-value returns a governed dry-run preview' );
npcink_abilities_toolkit_assert_same( 1, $patch_setting_preview['patch_preview'][0]['applied'] ?? null, 'patch-setting-value reports applied operation count' );
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
$media_health = $core_read_package->get_media_inventory_health(
	array(
		'mime_type' => 'image',
		'per_page'  => 5,
	)
);
npcink_abilities_toolkit_assert_same( true, $media_health['success'] ?? null, 'get-media-inventory-health returns a success envelope' );
npcink_abilities_toolkit_assert_true( (int) ( $media_health['data']['summary']['scanned_count'] ?? 0 ) >= 1, 'get-media-inventory-health scans local media rows' );
npcink_abilities_toolkit_assert_true( isset( $media_health['data']['issue_counts']['missing_alt'] ), 'get-media-inventory-health counts missing alt text' );
$media_health_row = npcink_abilities_toolkit_find_row_by_key( (array) ( $media_health['data']['items'] ?? array() ), 'attachment_id', 79 );
npcink_abilities_toolkit_assert_same( true, $media_health_row['format_inspection']['format_plan']['needs_attention'] ?? null, 'get-media-inventory-health includes format inspection attention state' );
npcink_abilities_toolkit_assert_true( in_array( 'legacy_image_format', (array) ( $media_health_row['format_inspection']['warnings'] ?? array() ), true ), 'get-media-inventory-health includes legacy format warning' );
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
npcink_abilities_toolkit_assert_same( 'workflow/wordpress_article_optimization', $apply_plan['data']['source_recipe_ref'] ?? '', 'build-article-optimization-apply-plan carries the article optimization recipe ref' );
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
npcink_abilities_toolkit_assert_same( false, $article_block_plan['data']['direct_wordpress_write'] ?? null, 'build-article-block-plan does not directly write WordPress' );
npcink_abilities_toolkit_assert_same( false, $article_block_plan['data']['commit_execution'] ?? null, 'build-article-block-plan keeps commit execution disabled' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/build-article-block-plan', $article_block_plan['data']['handoff']['plan_ability_id'] ?? '', 'build-article-block-plan identifies itself for Core from-plan intake' );
npcink_abilities_toolkit_assert_same( '1.0', $article_block_plan['data']['editorial_quality']['pattern_version'] ?? '', 'build-article-block-plan reports the v1 editorial pattern version' );
npcink_abilities_toolkit_assert_same( 'gutenberg_native_editorial', $article_block_plan['data']['editorial_quality']['style_strategy'] ?? '', 'build-article-block-plan reports editorial Gutenberg-native strategy' );
npcink_abilities_toolkit_assert_same( true, $article_block_plan['data']['editorial_quality']['uses_native_blocks'] ?? null, 'build-article-block-plan reports native block usage' );
npcink_abilities_toolkit_assert_same( true, $article_block_plan['data']['editorial_quality']['has_takeaways'] ?? null, 'build-article-block-plan reports takeaways' );
npcink_abilities_toolkit_assert_same( true, $article_block_plan['data']['editorial_quality']['has_faq'] ?? null, 'build-article-block-plan reports FAQ' );
npcink_abilities_toolkit_assert_same( true, $article_block_plan['data']['editorial_quality']['has_comparison_columns'] ?? null, 'build-article-block-plan reports comparison columns' );
npcink_abilities_toolkit_assert_same( false, $article_block_plan['data']['editorial_quality']['custom_css_required'] ?? true, 'build-article-block-plan reports no custom CSS requirement' );
npcink_abilities_toolkit_assert_same( 'article_standard', $article_block_plan['data']['responsive_quality']['responsive_profile'] ?? '', 'build-article-block-plan reports responsive profile quality' );
npcink_abilities_toolkit_assert_same( true, $article_block_plan['data']['responsive_quality']['uses_core_responsive_blocks'] ?? null, 'build-article-block-plan reports core responsive blocks' );
npcink_abilities_toolkit_assert_same( true, $article_block_plan['data']['responsive_quality']['uses_mobile_stack'] ?? null, 'build-article-block-plan reports mobile column stacking' );
npcink_abilities_toolkit_assert_same( true, $article_block_plan['data']['responsive_quality']['has_responsive_media'] ?? null, 'build-article-block-plan reports responsive media' );
npcink_abilities_toolkit_assert_same( 2, $article_block_plan['data']['responsive_quality']['max_columns_per_row'] ?? 0, 'build-article-block-plan reports bounded comparison columns' );
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
npcink_abilities_toolkit_assert_true( is_string( $article_markup ) && false !== strpos( $article_markup, '"blockName":"core\\/details"' ), 'build-article-block-plan uses details blocks for FAQ' );
npcink_abilities_toolkit_assert_true( is_string( $article_markup ) && false !== strpos( $article_markup, '"blockName":"core\\/columns"' ), 'build-article-block-plan uses columns for comparison sections' );
npcink_abilities_toolkit_assert_true( is_string( $article_markup ) && false !== strpos( $article_markup, '"isStackedOnMobile":true' ), 'build-article-block-plan stacks comparison columns on mobile' );
npcink_abilities_toolkit_assert_true( is_string( $article_markup ) && false !== strpos( $article_markup, 'wp-block-group has-border-color has-background' ), 'build-article-block-plan emits Gutenberg support classes for styled group blocks' );
npcink_abilities_toolkit_assert_true( is_string( $article_markup ) && false !== strpos( $article_markup, 'border-color:#e5e5e5;border-width:1px;border-radius:16px;background-color:#f7f7f4;padding-top:24px' ), 'build-article-block-plan serializes styled group wrappers from attrs' );
$pattern_page_plan = $core_read_package->build_pattern_page_plan(
	array(
		'title'              => 'WordPress AI',
		'pattern_id'         => 'openai-style-landing',
		'style_preset'       => 'minimal-dark-light',
		'responsive_profile' => 'landing_standard',
		'visual_density'     => 'balanced',
		'media_strategy'     => 'existing_media_url',
		'variables'          => array(
			'eyebrow'          => 'WordPress AI Plugin',
			'hero_title'       => '把 AI 工作流带进 WordPress 内容现场',
			'hero_description' => '让内容生产、SEO 优化、媒体处理与发布协作在同一个可审计流程中完成。',
			'primary_cta'      => '查看工作流',
			'secondary_cta'    => '了解能力',
			'hero_media_url'   => 'https://example.test/wp-content/uploads/2026/06/wordpress-ai-dashboard.jpg',
			'hero_media_alt'   => 'WordPress AI dashboard preview',
			'features'         => array(
				array(
					'title'       => 'AI 内容草稿',
					'description' => '从主题和上下文生成结构化草稿。',
				),
			),
		),
	)
);
npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['success'] ?? null, 'build-pattern-page-plan returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'pattern_page_plan', $pattern_page_plan['data']['artifact_type'] ?? '', 'build-pattern-page-plan declares a pattern page artifact type' );
npcink_abilities_toolkit_assert_same( 'openai-style-landing', $pattern_page_plan['data']['pattern_id'] ?? '', 'build-pattern-page-plan preserves the selected pattern id' );
npcink_abilities_toolkit_assert_same( 'minimal-dark-light', $pattern_page_plan['data']['style_preset'] ?? '', 'build-pattern-page-plan preserves the selected style preset' );
npcink_abilities_toolkit_assert_same( 'landing_standard', $pattern_page_plan['data']['responsive_profile'] ?? '', 'build-pattern-page-plan preserves the responsive profile' );
npcink_abilities_toolkit_assert_same( 'balanced', $pattern_page_plan['data']['visual_density'] ?? '', 'build-pattern-page-plan preserves visual density' );
npcink_abilities_toolkit_assert_same( 'existing_media_url', $pattern_page_plan['data']['media_strategy'] ?? '', 'build-pattern-page-plan preserves media strategy' );
npcink_abilities_toolkit_assert_same( false, $pattern_page_plan['data']['direct_wordpress_write'] ?? null, 'build-pattern-page-plan does not directly write WordPress' );
npcink_abilities_toolkit_assert_same( false, $pattern_page_plan['data']['commit_execution'] ?? null, 'build-pattern-page-plan keeps commit execution disabled' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/build-pattern-page-plan', $pattern_page_plan['data']['handoff']['plan_ability_id'] ?? '', 'build-pattern-page-plan identifies itself for Core from-plan intake' );
npcink_abilities_toolkit_assert_same( '2.0', $pattern_page_plan['data']['design_quality']['pattern_version'] ?? '', 'build-pattern-page-plan reports the v2 Pattern quality version' );
npcink_abilities_toolkit_assert_same( 'gutenberg_native', $pattern_page_plan['data']['design_quality']['style_strategy'] ?? '', 'build-pattern-page-plan reports Gutenberg-native style strategy' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['data']['design_quality']['uses_native_styles'] ?? null, 'build-pattern-page-plan reports native style usage' );
npcink_abilities_toolkit_assert_same( 7, $pattern_page_plan['data']['design_quality']['section_count'] ?? 0, 'build-pattern-page-plan reports seven v2 sections when media is supplied' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['data']['design_quality']['has_split_hero'] ?? null, 'build-pattern-page-plan reports split hero' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['data']['design_quality']['has_dashboard_mock'] ?? null, 'build-pattern-page-plan reports dashboard mock' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['data']['design_quality']['has_proof_strip'] ?? null, 'build-pattern-page-plan reports proof strip' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['data']['design_quality']['has_media_text'] ?? null, 'build-pattern-page-plan reports media-text section' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['data']['design_quality']['has_faq'] ?? null, 'build-pattern-page-plan reports FAQ section' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['data']['design_quality']['has_final_cta'] ?? null, 'build-pattern-page-plan reports final CTA' );
npcink_abilities_toolkit_assert_same( false, $pattern_page_plan['data']['design_quality']['custom_css_required'] ?? true, 'build-pattern-page-plan reports no custom CSS requirement' );
npcink_abilities_toolkit_assert_same( 'landing_standard', $pattern_page_plan['data']['responsive_quality']['responsive_profile'] ?? '', 'build-pattern-page-plan reports responsive profile quality' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['data']['responsive_quality']['uses_mobile_stack'] ?? null, 'build-pattern-page-plan reports mobile column stacking' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['data']['responsive_quality']['uses_core_responsive_blocks'] ?? null, 'build-pattern-page-plan reports core responsive blocks' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['data']['responsive_quality']['has_media_section'] ?? null, 'build-pattern-page-plan reports media section responsiveness' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['data']['responsive_quality']['has_faq'] ?? null, 'build-pattern-page-plan reports responsive FAQ' );
npcink_abilities_toolkit_assert_same( 4, $pattern_page_plan['data']['responsive_quality']['max_columns_per_row'] ?? 0, 'build-pattern-page-plan reports bounded max columns per row' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['data']['responsive_quality']['button_groups_use_flex_layout'] ?? null, 'build-pattern-page-plan reports flex button groups' );
npcink_abilities_toolkit_assert_same( false, $pattern_page_plan['data']['responsive_quality']['custom_css_required'] ?? true, 'build-pattern-page-plan reports responsive output without custom CSS' );
$pattern_page_actions = is_array( $pattern_page_plan['data']['write_actions'] ?? null ) ? $pattern_page_plan['data']['write_actions'] : array();
npcink_abilities_toolkit_assert_same( 2, count( $pattern_page_actions ), 'build-pattern-page-plan emits create and block update actions' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/create-draft', $pattern_page_actions[0]['target_ability_id'] ?? '', 'build-pattern-page-plan first creates a draft page' );
npcink_abilities_toolkit_assert_same( 'page', $pattern_page_actions[0]['input']['post_type'] ?? '', 'build-pattern-page-plan create action targets a page' );
npcink_abilities_toolkit_assert_same( 'draft', $pattern_page_actions[0]['input']['status'] ?? '', 'build-pattern-page-plan create action stays draft-only' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/update-post-blocks', $pattern_page_actions[1]['target_ability_id'] ?? '', 'build-pattern-page-plan second action updates Gutenberg blocks' );
npcink_abilities_toolkit_assert_same( '$outputs.create-pattern-page.post_id', $pattern_page_actions[1]['input']['post_id'] ?? '', 'build-pattern-page-plan uses exact output reference for the new page id' );
$pattern_blocks = is_array( $pattern_page_actions[1]['input']['blocks'] ?? null ) ? $pattern_page_actions[1]['input']['blocks'] : array();
npcink_abilities_toolkit_assert_same( 'core/group', $pattern_blocks[0]['blockName'] ?? '', 'build-pattern-page-plan renders core group blocks' );
npcink_abilities_toolkit_assert_true( in_array( null, $pattern_blocks[0]['innerContent'] ?? array(), true ), 'build-pattern-page-plan group blocks include innerContent null markers' );
npcink_abilities_toolkit_assert_true( in_array( 'npcink-ai-hero', $pattern_page_plan['data']['allowed_classes'] ?? array(), true ), 'build-pattern-page-plan exposes a class whitelist' );
npcink_abilities_toolkit_assert_same( 'npcink-ai-page npcink-ai-hero', $pattern_blocks[0]['attrs']['className'] ?? '', 'build-pattern-page-plan applies only whitelisted page classes' );
npcink_abilities_toolkit_assert_same( 'full', $pattern_blocks[0]['attrs']['align'] ?? '', 'build-pattern-page-plan uses full-width Gutenberg sections' );
npcink_abilities_toolkit_assert_same( 'constrained', $pattern_blocks[0]['attrs']['layout']['type'] ?? '', 'build-pattern-page-plan uses constrained section layout' );
npcink_abilities_toolkit_assert_same( '1120px', $pattern_blocks[0]['attrs']['layout']['contentSize'] ?? '', 'build-pattern-page-plan sets native content width' );
npcink_abilities_toolkit_assert_same( '96px', $pattern_blocks[0]['attrs']['style']['spacing']['padding']['top'] ?? '', 'build-pattern-page-plan sets native hero spacing' );
npcink_abilities_toolkit_assert_same( '#f7f7f4', $pattern_blocks[0]['attrs']['style']['color']['background'] ?? '', 'build-pattern-page-plan sets native section background' );
npcink_abilities_toolkit_assert_true( false !== strpos( (string) ( $pattern_blocks[0]['innerHTML'] ?? '' ), 'has-background' ), 'build-pattern-page-plan emits Gutenberg background support class for hero group' );
$pattern_hero_layout = is_array( $pattern_blocks[0]['innerBlocks'][0] ?? null ) ? $pattern_blocks[0]['innerBlocks'][0] : array();
npcink_abilities_toolkit_assert_same( 'core/columns', $pattern_hero_layout['blockName'] ?? '', 'build-pattern-page-plan uses a split hero columns block' );
npcink_abilities_toolkit_assert_same( 'npcink-ai-hero-layout', $pattern_hero_layout['attrs']['className'] ?? '', 'build-pattern-page-plan marks the split hero layout' );
$pattern_hero_copy = is_array( $pattern_hero_layout['innerBlocks'][0]['innerBlocks'] ?? null ) ? $pattern_hero_layout['innerBlocks'][0]['innerBlocks'] : array();
npcink_abilities_toolkit_assert_same( '64px', $pattern_hero_copy[1]['attrs']['style']['typography']['fontSize'] ?? '', 'build-pattern-page-plan sets native hero title typography' );
npcink_abilities_toolkit_assert_same( '1', $pattern_hero_copy[1]['attrs']['style']['typography']['lineHeight'] ?? '', 'build-pattern-page-plan sets native hero title line height' );
$pattern_buttons = is_array( $pattern_hero_copy[3]['innerBlocks'] ?? null ) ? $pattern_hero_copy[3]['innerBlocks'] : array();
npcink_abilities_toolkit_assert_same( '999px', $pattern_buttons[0]['attrs']['style']['border']['radius'] ?? '', 'build-pattern-page-plan sets native button radius' );
npcink_abilities_toolkit_assert_same( '#111111', $pattern_buttons[0]['attrs']['style']['color']['background'] ?? '', 'build-pattern-page-plan sets native primary button color' );
$pattern_markup = wp_json_encode( $pattern_blocks );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, 'font-size:64px;font-weight:500;letter-spacing:0;line-height:1' ), 'build-pattern-page-plan serializes heading typography in Gutenberg save order' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, 'wp-block-button__link has-text-color has-background wp-element-button' ), 'build-pattern-page-plan emits Gutenberg support classes for primary button links' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, '"blockName":"core\\/columns"' ), 'build-pattern-page-plan uses native columns for feature and workflow sections' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, '"isStackedOnMobile":true' ), 'build-pattern-page-plan stacks columns on mobile' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, '"blockName":"core\\/media-text"' ), 'build-pattern-page-plan uses media-text when existing media is supplied' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, '"blockName":"core\\/details"' ), 'build-pattern-page-plan uses details blocks for FAQ' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, 'npcink-ai-dashboard-card' ), 'build-pattern-page-plan includes a dashboard mock card' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, 'npcink-ai-proof-strip' ), 'build-pattern-page-plan includes a proof strip' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, 'npcink-ai-media-text' ), 'build-pattern-page-plan includes a media-text class handle' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, 'npcink-ai-faq-item' ), 'build-pattern-page-plan includes FAQ item class handles' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, 'npcink-ai-final-cta' ), 'build-pattern-page-plan includes a final CTA' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, '"top":{"color":"#111111","width":"1px"}' ), 'build-pattern-page-plan uses native top-line card border attrs' );
$research_backed_pattern_page_plan = $core_read_package->build_pattern_page_plan(
	array(
		'title'              => 'Research Backed WordPress AI',
		'pattern_id'         => 'openai-style-landing',
		'style_preset'       => 'minimal-dark-light',
		'responsive_profile' => 'landing_standard',
		'visual_density'     => 'balanced',
		'media_strategy'     => 'existing_media_url',
		'research_brief'     => array(
			'artifact_type'                 => 'landing_page_research_brief',
			'write_posture'                 => 'suggestion_only',
			'direct_wordpress_write'        => false,
			'source_count'                  => 3,
			'section_patterns'              => array(
				array(
					'title'       => 'Evidence-led hero',
					'description' => 'Open with product proof and reviewed workflow context before detailed feature cards.',
				),
			),
			'visual_asset_recommendations' => array(
				array(
					'title'       => 'Proposal dashboard visual',
					'description' => 'Use a reviewed product interface image that shows approval status and block validation.',
				),
			),
			'proof_points'                  => array(
				array(
					'title'       => 'Reference-backed proof',
					'description' => 'Show why reviewable drafts matter before asking visitors to compare features.',
				),
			),
			'comparison_angles'             => array(
				array(
					'title'       => 'Direct automation',
					'description' => 'Fast, but hard to audit when writes skip proposal review.',
				),
				array(
					'title'       => 'Proposal-first pages',
					'description' => 'Keeps final WordPress changes reviewable, reversible, and traceable.',
				),
			),
			'faq_seed_questions'            => array(
				array(
					'question' => 'Can the page use external research safely?',
					'answer'   => 'Yes, when references are summarized as evidence and not copied into the page.',
				),
			),
		),
		'variables'          => array(
			'eyebrow'          => 'WordPress AI Plugin',
			'hero_title'       => 'Research-backed Gutenberg landing page',
			'hero_description' => 'Cloud search evidence shapes the brief while Toolkit keeps Gutenberg output native.',
			'hero_media_url'   => 'https://example.test/wp-content/uploads/2026/06/research-backed-dashboard.jpg',
			'hero_media_alt'   => 'Research-backed WordPress AI dashboard preview',
		),
	)
);
npcink_abilities_toolkit_assert_same( true, $research_backed_pattern_page_plan['success'] ?? null, 'build-pattern-page-plan accepts a reviewed landing page research brief' );
npcink_abilities_toolkit_assert_same( true, $research_backed_pattern_page_plan['data']['research_brief']['research_backed'] ?? null, 'build-pattern-page-plan reports research-backed page planning' );
npcink_abilities_toolkit_assert_same( 3, $research_backed_pattern_page_plan['data']['research_brief']['source_count'] ?? 0, 'build-pattern-page-plan preserves compact research source count' );
npcink_abilities_toolkit_assert_same( 2, $research_backed_pattern_page_plan['data']['research_brief']['comparison_angle_count'] ?? 0, 'build-pattern-page-plan counts comparison angles from research brief' );
npcink_abilities_toolkit_assert_same( false, $research_backed_pattern_page_plan['data']['research_brief']['reference_copying_allowed'] ?? true, 'build-pattern-page-plan keeps reference copying disabled' );
npcink_abilities_toolkit_assert_same( true, $research_backed_pattern_page_plan['data']['design_quality']['research_backed'] ?? null, 'build-pattern-page-plan design quality marks research-backed output' );
npcink_abilities_toolkit_assert_same( true, $research_backed_pattern_page_plan['data']['design_quality']['has_comparison_section'] ?? null, 'build-pattern-page-plan adds a comparison section from research angles' );
npcink_abilities_toolkit_assert_same( 8, $research_backed_pattern_page_plan['data']['design_quality']['section_count'] ?? 0, 'build-pattern-page-plan adds one research-backed comparison section when media is supplied' );
$research_backed_actions = is_array( $research_backed_pattern_page_plan['data']['write_actions'] ?? null ) ? $research_backed_pattern_page_plan['data']['write_actions'] : array();
$research_backed_blocks = is_array( $research_backed_actions[1]['input']['blocks'] ?? null ) ? $research_backed_actions[1]['input']['blocks'] : array();
$research_backed_markup = wp_json_encode( $research_backed_blocks );
npcink_abilities_toolkit_assert_true( is_string( $research_backed_markup ) && false !== strpos( $research_backed_markup, 'npcink-ai-comparison' ), 'build-pattern-page-plan serializes research-backed comparison section blocks' );
npcink_abilities_toolkit_assert_true( is_string( $research_backed_markup ) && false !== strpos( $research_backed_markup, 'Reference-backed proof' ), 'build-pattern-page-plan uses research proof points' );
npcink_abilities_toolkit_assert_true( is_string( $research_backed_markup ) && false !== strpos( $research_backed_markup, 'Proposal dashboard visual' ), 'build-pattern-page-plan uses visual asset recommendations for media copy' );
npcink_abilities_toolkit_assert_true( is_string( $research_backed_markup ) && false !== strpos( $research_backed_markup, 'Can the page use external research safely?' ), 'build-pattern-page-plan uses FAQ seeds from research brief' );
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
npcink_abilities_toolkit_assert_same( true, $apply_result['success'] ?? null, 'compose-article-optimization-apply-result returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'partial_apply', $apply_result['data']['summary']['result_mode'] ?? '', 'compose-article-optimization-apply-result marks partial apply when excerpt changed' );
npcink_abilities_toolkit_assert_same( 1, $apply_result['data']['summary']['applied_count'] ?? null, 'compose-article-optimization-apply-result counts applied changes' );
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
npcink_abilities_toolkit_assert_same( true, $draft_result['success'] ?? null, 'compose-article-draft-result returns a success envelope' );
npcink_abilities_toolkit_assert_same( true, $draft_data['draft']['preview_only'] ?? null, 'compose-article-draft-result preserves preview-only mode' );
npcink_abilities_toolkit_assert_same( false, $draft_data['draft']['real_draft_created'] ?? null, 'compose-article-draft-result does not claim a real draft in preview mode' );
npcink_abilities_toolkit_assert_same( 'review_preview', $draft_data['handoff']['next_action'] ?? '', 'compose-article-draft-result keeps preview handoff local to draft workflow' );
npcink_abilities_toolkit_assert_same( 'workflow/wordpress_article_draft', $draft_data['handoff']['recommended_entry'] ?? '', 'compose-article-draft-result keeps draft recommended entry for preview-only output' );
npcink_abilities_toolkit_assert_same( '内部复盘记录', $draft_data['source_references'][0] ?? '', 'compose-article-draft-result extracts source references from human signals' );
$publication_decision = $core_read_package->resolve_article_publication_decision(
	array(
		'publish_mode' => 'schedule',
		'review'       => array( 'needs_human_review' => true ),
	)
);
npcink_abilities_toolkit_assert_same( true, $publication_decision['success'] ?? null, 'resolve-article-publication-decision returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'schedule', $publication_decision['data']['requested_publish_mode'] ?? '', 'resolve-article-publication-decision preserves requested mode' );
npcink_abilities_toolkit_assert_same( 'review', $publication_decision['data']['effective_publish_mode'] ?? '', 'resolve-article-publication-decision routes blocked schedules to review' );
npcink_abilities_toolkit_assert_same( true, $publication_decision['data']['publish_blocked'] ?? null, 'resolve-article-publication-decision marks human-review gate as blocked' );
npcink_abilities_toolkit_assert_same( 'quality_review_requires_handoff', $publication_decision['data']['gate_reason'] ?? '', 'resolve-article-publication-decision records quality gate reason' );
$template_publication_decision = $core_read_package->resolve_article_publication_decision(
	array(
		'publish_mode' => 'publish',
		'review'       => array(
			'needs_human_review' => true,
			'template_risk_level' => 'high',
		),
	)
);
npcink_abilities_toolkit_assert_same( 'template_style_requires_handoff', $template_publication_decision['data']['gate_reason'] ?? '', 'resolve-article-publication-decision records high template risk gate reason' );
$duplicate_publication_decision = $core_read_package->resolve_article_publication_decision(
	array(
		'publish_mode'     => 'publish',
		'duplicate_guard'  => array( 'skip_recommended' => true ),
	)
);
npcink_abilities_toolkit_assert_same( 'duplicate_production_candidate', $duplicate_publication_decision['data']['gate_reason'] ?? '', 'resolve-article-publication-decision records duplicate gate reason' );
$draft_publication_decision = $core_read_package->resolve_article_publication_decision( array( 'publish_mode' => 'unexpected' ) );
npcink_abilities_toolkit_assert_same( 'draft', $draft_publication_decision['data']['requested_publish_mode'] ?? '', 'resolve-article-publication-decision falls back invalid mode to draft' );
npcink_abilities_toolkit_assert_same( false, $draft_publication_decision['data']['publish_blocked'] ?? null, 'resolve-article-publication-decision leaves draft unblocked' );
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
npcink_abilities_toolkit_assert_same( true, $article_style_profile['success'] ?? null, 'build-article-style-profile returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'practical_editorial', $article_style_profile['data']['profile']['resolved_voice_profile'] ?? '', 'build-article-style-profile preserves explicit voice override' );
npcink_abilities_toolkit_assert_same( 'scene', $article_style_profile['data']['profile']['resolved_opening_style'] ?? '', 'build-article-style-profile preserves explicit opening override' );
npcink_abilities_toolkit_assert_same( 'alternating paragraph lengths', $article_style_profile['data']['profile']['resolved_structure_style'] ?? '', 'build-article-style-profile preserves explicit structure override' );
npcink_abilities_toolkit_assert_true( false !== strpos( (string) ( $article_style_profile['data']['profile']['style_brief'] ?? '' ), 'Reference article favors lived experience.' ), 'build-article-style-profile carries reference brief' );
npcink_abilities_toolkit_assert_true( false !== strpos( (string) ( $article_style_profile['data']['profile']['style_brief'] ?? '' ), 'Site baseline favors practical judgement.' ), 'build-article-style-profile carries baseline brief' );
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
npcink_abilities_toolkit_assert_same( 'reference_voice', $reference_first_style_profile['data']['profile']['resolved_voice_profile'] ?? '', 'build-article-style-profile prefers reference voice over baseline when no explicit override exists' );
npcink_abilities_toolkit_assert_same( 'reference_opening', $reference_first_style_profile['data']['profile']['resolved_opening_style'] ?? '', 'build-article-style-profile prefers reference opening over baseline when no explicit override exists' );
npcink_abilities_toolkit_assert_same( 'Repeated brief.', $reference_first_style_profile['data']['profile']['style_brief'] ?? '', 'build-article-style-profile deduplicates repeated style briefs' );
$package_bridge = new Npcink_Catalog_Bridge( $package_registrar );
$package_catalog = $package_bridge->filter_catalog( array(), array() );
foreach ( $migrated_read_ability_ids as $migrated_ability_id ) {
	$catalog_key = str_replace( '/', '_', $migrated_ability_id );
	npcink_abilities_toolkit_assert_true( isset( $package_catalog[ $catalog_key ] ), "catalog bridge projects migrated {$migrated_ability_id}" );
	npcink_abilities_toolkit_assert_same( 'wp_ability', $package_catalog[ $catalog_key ]['executor_type'], "{$migrated_ability_id} catalog entry executes through wp_ability" );
	npcink_abilities_toolkit_assert_true( ! isset( $package_catalog[ $catalog_key ]['open_api_enabled'] ), "{$migrated_ability_id} catalog projection does not own Open API policy" );
}
foreach ( $migrated_write_ability_ids as $migrated_write_ability_id ) {
	$catalog_key = str_replace( '/', '_', $migrated_write_ability_id );
	npcink_abilities_toolkit_assert_true( isset( $package_catalog[ $catalog_key ] ), "catalog bridge projects migrated {$migrated_write_ability_id}" );
	npcink_abilities_toolkit_assert_same( 'wp_ability', $package_catalog[ $catalog_key ]['executor_type'], "{$migrated_write_ability_id} catalog entry executes through wp_ability" );
	npcink_abilities_toolkit_assert_same( true, $package_catalog[ $catalog_key ]['requires_confirm'], "{$migrated_write_ability_id} catalog projection requires confirmation" );
	npcink_abilities_toolkit_assert_same( 'write', $package_catalog[ $catalog_key ]['risk_level'], "{$migrated_write_ability_id} catalog projection is write risk" );
	npcink_abilities_toolkit_assert_same( true, $package_catalog[ $catalog_key ]['show_in_rest'], "{$migrated_write_ability_id} catalog projection exposes show_in_rest for host normalization" );
	npcink_abilities_toolkit_assert_true( ! isset( $package_catalog[ $catalog_key ]['write_mode'] ), "{$migrated_write_ability_id} catalog projection does not own write mode policy" );
	npcink_abilities_toolkit_assert_true( ! isset( $package_catalog[ $catalog_key ]['open_api_enabled'] ), "{$migrated_write_ability_id} catalog projection does not own Open API policy" );
	npcink_abilities_toolkit_assert_true( ! isset( $package_catalog[ $catalog_key ]['skip_catalog_manifest_fallback'] ), "{$migrated_write_ability_id} catalog projection does not own host fallback policy" );
}
foreach ( $migrated_destructive_ability_ids as $migrated_destructive_ability_id ) {
	$catalog_key = str_replace( '/', '_', $migrated_destructive_ability_id );
	npcink_abilities_toolkit_assert_true( isset( $package_catalog[ $catalog_key ] ), "catalog bridge projects migrated {$migrated_destructive_ability_id}" );
	npcink_abilities_toolkit_assert_same( 'wp_ability', $package_catalog[ $catalog_key ]['executor_type'], "{$migrated_destructive_ability_id} catalog entry executes through wp_ability" );
	npcink_abilities_toolkit_assert_same( true, $package_catalog[ $catalog_key ]['requires_confirm'], "{$migrated_destructive_ability_id} catalog projection requires confirmation" );
	npcink_abilities_toolkit_assert_same( 'destructive', $package_catalog[ $catalog_key ]['risk_level'], "{$migrated_destructive_ability_id} catalog projection is destructive risk" );
	npcink_abilities_toolkit_assert_true( ! isset( $package_catalog[ $catalog_key ]['write_mode'] ), "{$migrated_destructive_ability_id} catalog projection does not own write mode policy" );
	npcink_abilities_toolkit_assert_true( ! isset( $package_catalog[ $catalog_key ]['tool_policy'] ), "{$migrated_destructive_ability_id} catalog projection does not own destructive tool policy" );
	npcink_abilities_toolkit_assert_true( ! isset( $package_catalog[ $catalog_key ]['open_api_enabled'] ), "{$migrated_destructive_ability_id} catalog projection does not own Open API policy" );
	npcink_abilities_toolkit_assert_true( ! isset( $package_catalog[ $catalog_key ]['skip_catalog_manifest_fallback'] ), "{$migrated_destructive_ability_id} catalog projection does not own host fallback policy" );
}
npcink_abilities_toolkit_assert_true( ! isset( $package_catalog['npcink-abilities-toolkit_wp-diagnostics-summary'] ), 'catalog bridge does not project standalone diagnostics ability' );
npcink_abilities_toolkit_assert_true( ! isset( $package_catalog['npcink-abilities-toolkit_wp-ops-diagnostics-detail'] ), 'catalog bridge does not project standalone ops diagnostics ability' );

$workflow_replay_path = __DIR__ . '/fixtures/agent-workflow-replay.json';
$workflow_replay_json = file_get_contents( $workflow_replay_path );
npcink_abilities_toolkit_assert_true( false !== $workflow_replay_json, 'agent workflow replay fixture is readable' );
$workflow_replay = json_decode( (string) $workflow_replay_json, true );
npcink_abilities_toolkit_assert_true( is_array( $workflow_replay ), 'agent workflow replay fixture decodes as an object' );
$workflow_manifest = \Npcink_Abilities_Toolkit\Workflow\Workflow_Definition_Provider::manifest();
npcink_abilities_toolkit_assert_same( $workflow_manifest, $workflow_replay, 'agent workflow replay fixture matches production workflow definition provider' );
npcink_abilities_toolkit_assert_same( $workflow_manifest, npcink_abilities_toolkit_get_workflow_definitions(), 'public workflow definitions helper matches provider manifest' );
npcink_abilities_toolkit_assert_same( $workflow_manifest['cases']['article_publish_preflight'], npcink_abilities_toolkit_get_workflow_definition( 'workflow/wordpress_article_publish_preflight' ), 'public workflow definition helper resolves recipe id' );
npcink_abilities_toolkit_assert_same( 'v1', $workflow_replay['schema_version'] ?? '', 'agent workflow replay fixture schema is v1' );
npcink_abilities_toolkit_assert_true( is_array( $workflow_replay['cases'] ?? null ), 'agent workflow replay fixture exposes cases' );
$forbidden_workflow_definition_fields = \Npcink_Abilities_Toolkit\Workflow\Workflow_Definition_Provider::forbidden_field_keys();
npcink_abilities_toolkit_assert_array_omits_keys( $workflow_replay, $forbidden_workflow_definition_fields, 'agent workflow replay fixture' );
$expected_workflow_replay_cases = array(
	'article_draft'                  => array(
		'ability_id'         => 'npcink-abilities-toolkit/compose-article-draft-result',
		'recipe_id'          => 'workflow/wordpress_article_draft',
		'recipe_aliases'     => array( 'article_draft_v1' ),
		'required_scope'     => 'cap.text.extract',
		'required_inputs'    => array(),
		'expected_sections'  => array( 'article', 'draft', 'metadata_plan_resolution', 'review', 'handoff' ),
		'expanded_abilities' => array(
			'npcink-abilities-toolkit/resolve-post-metadata-plan',
			'npcink-abilities-toolkit/resolve-internal-link-targets',
			'npcink-abilities-toolkit/build-inline-image-blocks',
			'npcink-abilities-toolkit/build-media-seo-assets',
			'npcink-abilities-toolkit/review-article-output-light',
			'npcink-abilities-toolkit/compose-article-draft-result',
		),
		'handoff_kind'       => 'suggestion',
		'disallowed_default' => array( 'npcink-abilities-toolkit/create-draft', 'npcink-abilities-toolkit/update-post', 'npcink-abilities-toolkit/patch-post-content', 'npcink-abilities-toolkit/publish-post' ),
	),
	'article_publish_preflight'      => array(
		'ability_id'         => 'npcink-abilities-toolkit/get-article-publish-preflight-context',
		'recipe_id'          => 'workflow/wordpress_article_publish_preflight',
		'required_scope'     => 'post.read',
		'required_inputs'    => array( 'post_id' ),
		'expected_sections'  => array( 'post_context', 'publishing_checklist', 'publish_risk', 'workflow_context', 'publishing_calendar' ),
		'expanded_abilities' => array(
			'npcink-abilities-toolkit/get-post-context',
			'npcink-abilities-toolkit/get-content-publishing-checklist',
			'npcink-abilities-toolkit/get-post-publish-risk-report',
			'npcink-abilities-toolkit/build-article-workflow-context',
			'npcink-abilities-toolkit/get-publishing-calendar-context',
		),
		'handoff_kind'       => 'context',
		'disallowed_default' => array( 'npcink-abilities-toolkit/schedule-post', 'npcink-abilities-toolkit/publish-post' ),
	),
	'article_optimization'           => array(
		'ability_id'         => 'npcink-abilities-toolkit/read-post-optimization-context',
		'recipe_id'          => 'workflow/wordpress_article_optimization',
		'required_scope'     => 'post.read',
		'required_inputs'    => array( 'post_id' ),
		'expected_sections'  => array( 'post_context', 'seo_report', 'optimization_suggestion', 'apply_plan', 'handoff' ),
		'expanded_abilities' => array(
			'npcink-abilities-toolkit/read-post-optimization-context',
			'npcink-abilities-toolkit/seo-report-context',
			'npcink-abilities-toolkit/build-article-single-optimization-suggest',
			'npcink-abilities-toolkit/build-article-optimization-apply-plan',
			'npcink-abilities-toolkit/compose-article-optimization-apply-result',
		),
		'handoff_kind'       => 'suggestion',
		'disallowed_default' => array( 'npcink-abilities-toolkit/patch-post-content', 'npcink-abilities-toolkit/set-post-seo-meta', 'npcink-abilities-toolkit/update-post-blocks' ),
	),
	'article_media_handoff'          => array(
		'ability_id'         => 'npcink-abilities-toolkit/build-media-seo-assets',
		'recipe_id'          => 'workflow/wordpress_article_media_handoff',
		'required_scope'     => 'media.read',
		'required_inputs'    => array(),
		'expected_sections'  => array( 'post_context', 'media_assets', 'inline_blocks', 'positioned_blocks', 'handoff' ),
		'expanded_abilities' => array(
			'npcink-abilities-toolkit/get-post-context',
			'npcink-abilities-toolkit/build-inline-image-blocks',
			'npcink-abilities-toolkit/build-media-seo-assets',
			'npcink-abilities-toolkit/position-inline-image-blocks',
		),
		'handoff_kind'       => 'suggestion',
		'disallowed_default' => array( 'npcink-abilities-toolkit/upload-media-from-url', 'npcink-abilities-toolkit/update-media-details', 'npcink-abilities-toolkit/set-post-featured-image' ),
	),
	'old_article_refresh_discovery' => array(
		'ability_id'         => 'npcink-abilities-toolkit/get-old-article-refresh-context',
		'recipe_id'          => 'workflow/wordpress_old_article_refresh_discovery',
		'required_scope'     => 'post.read',
		'required_inputs'    => array(),
		'expected_sections'  => array( 'refresh_opportunities', 'seo_geo_gap_report', 'site_style_baseline', 'internal_link_graph_health' ),
		'expanded_abilities' => array(
			'npcink-abilities-toolkit/get-content-refresh-opportunities',
			'npcink-abilities-toolkit/get-seo-geo-gap-report',
			'npcink-abilities-toolkit/get-site-style-baseline',
			'npcink-abilities-toolkit/get-internal-link-graph-health',
			'npcink-abilities-toolkit/get-internal-link-opportunity-report',
		),
		'handoff_kind'       => 'context',
		'disallowed_default' => array( 'npcink-abilities-toolkit/patch-post-content', 'npcink-abilities-toolkit/update-post', 'npcink-abilities-toolkit/update-post-blocks' ),
	),
	'comment_compliance_handoff'    => array(
		'ability_id'         => 'npcink-abilities-toolkit/get-comment-compliance-handoff',
		'recipe_id'          => 'workflow/wordpress_comment_compliance_handoff',
		'required_scope'     => 'comments.manage',
		'required_inputs'    => array(),
		'expected_sections'  => array( 'queue_health', 'priority_queue', 'selected_moderation_suggestion' ),
		'expanded_abilities' => array(
			'npcink-abilities-toolkit/get-comment-queue-health',
			'npcink-abilities-toolkit/get-comment-action-priority-queue',
			'npcink-abilities-toolkit/build-comment-moderation-suggest',
			'npcink-abilities-toolkit/build-comment-mention-reply-suggest',
			'npcink-abilities-toolkit/compose-comment-moderation-result',
		),
		'handoff_kind'       => 'context',
		'disallowed_default' => array( 'npcink-abilities-toolkit/approve-comment', 'npcink-abilities-toolkit/reply-comment', 'npcink-abilities-toolkit/spam-comment', 'npcink-abilities-toolkit/trash-comment' ),
	),
);
npcink_abilities_toolkit_assert_same( array_keys( $expected_workflow_replay_cases ), array_keys( $workflow_replay['cases'] ), 'agent workflow replay fixture keeps the approved local recipe cases in order' );
foreach ( $expected_workflow_replay_cases as $case_id => $expected_case ) {
	$case = $workflow_replay['cases'][ $case_id ] ?? array();
	npcink_abilities_toolkit_assert_true( is_array( $case ), "agent workflow replay case {$case_id} is an object" );
	npcink_abilities_toolkit_assert_same( 'workflow_recipe', $case['definition_kind'] ?? '', "agent workflow replay case {$case_id} is a workflow recipe definition" );
	npcink_abilities_toolkit_assert_same( 'v1', $case['contract_version'] ?? '', "agent workflow replay case {$case_id} uses definition contract v1" );
	npcink_abilities_toolkit_assert_true( is_array( $case['natural_tasks'] ?? null ), "agent workflow replay case {$case_id} exposes natural task examples" );
	npcink_abilities_toolkit_assert_true( count( $case['natural_tasks'] ) >= 3, "agent workflow replay case {$case_id} keeps at least three natural task examples" );
	npcink_abilities_toolkit_assert_same( $expected_case['ability_id'], $case['preferred_ability_id'] ?? '', "agent workflow replay case {$case_id} prefers the bundle ability" );
	npcink_abilities_toolkit_assert_same( $expected_case['ability_id'], $case['entrypoint_ability_id'] ?? '', "agent workflow replay case {$case_id} exposes the preferred bundle as entrypoint" );
	npcink_abilities_toolkit_assert_same( $expected_case['recipe_id'], $case['recipe_id'] ?? '', "agent workflow replay case {$case_id} keeps the recipe id" );
	if ( isset( $expected_case['recipe_aliases'] ) ) {
		npcink_abilities_toolkit_assert_same( $expected_case['recipe_aliases'], $case['recipe_aliases'] ?? array(), "agent workflow replay case {$case_id} keeps recipe aliases" );
	}
	npcink_abilities_toolkit_assert_same( $expected_case['required_scope'], $case['required_scope'] ?? '', "agent workflow replay case {$case_id} keeps the required scope" );
	npcink_abilities_toolkit_assert_same( $expected_case['required_inputs'], $case['required_inputs'] ?? array(), "agent workflow replay case {$case_id} keeps required inputs" );
	npcink_abilities_toolkit_assert_same( $expected_case['expected_sections'], $case['expected_sections'] ?? array(), "agent workflow replay case {$case_id} keeps expected output sections" );
	npcink_abilities_toolkit_assert_same( $expected_case['expanded_abilities'], $case['expanded_ability_ids'] ?? array(), "agent workflow replay case {$case_id} keeps expanded ability chain" );
	npcink_abilities_toolkit_assert_true( is_array( $case['handoff'] ?? null ), "agent workflow replay case {$case_id} exposes a structured handoff" );
	npcink_abilities_toolkit_assert_same( $expected_case['handoff_kind'], $case['handoff']['kind'] ?? '', "agent workflow replay case {$case_id} keeps handoff kind" );
	npcink_abilities_toolkit_assert_same( 'host', $case['handoff']['owner'] ?? '', "agent workflow replay case {$case_id} keeps host-owned handoff" );
	npcink_abilities_toolkit_assert_true( is_string( $case['handoff']['next_action'] ?? null ) && '' !== $case['handoff']['next_action'], "agent workflow replay case {$case_id} keeps a host next action hint" );
	npcink_abilities_toolkit_assert_same( 'fail_closed', $case['failure_policy'] ?? '', "agent workflow replay case {$case_id} fails closed" );
	npcink_abilities_toolkit_assert_same( $expected_case['disallowed_default'], $case['disallowed_default_ability_ids'] ?? array(), "agent workflow replay case {$case_id} keeps disallowed default write abilities" );
	npcink_abilities_toolkit_assert_same( true, $case['host_governed_write_boundary'] ?? null, "agent workflow replay case {$case_id} keeps host-governed write boundary" );
	npcink_abilities_toolkit_assert_true( isset( $package_abilities[ $expected_case['ability_id'] ] ), "agent workflow replay case {$case_id} points to a registered ability" );
	$entrypoint_ability = $package_abilities[ $expected_case['ability_id'] ];
	npcink_abilities_toolkit_assert_same( 'read', $entrypoint_ability['risk_level'] ?? '', "agent workflow replay case {$case_id} points to a read-risk bundle" );
	npcink_abilities_toolkit_assert_same( false, $entrypoint_ability['requires_confirm'] ?? null, "agent workflow replay case {$case_id} points to a read-only bundle without confirmation" );
	npcink_abilities_toolkit_assert_same( $expected_case['required_scope'], $entrypoint_ability['required_scope'] ?? '', "agent workflow replay case {$case_id} matches registered ability scope" );
	npcink_abilities_toolkit_assert_same( $expected_case['required_inputs'], $entrypoint_ability['input_schema']['required'] ?? array(), "agent workflow replay case {$case_id} required inputs match the entrypoint schema" );
	$case_catalog_key = str_replace( '/', '_', $expected_case['ability_id'] );
	npcink_abilities_toolkit_assert_same( 'wp_ability', $package_catalog[ $case_catalog_key ]['executor_type'] ?? '', "agent workflow replay case {$case_id} is projected for wp_ability execution" );
	foreach ( $expected_case['expanded_abilities'] as $expanded_ability_id ) {
		npcink_abilities_toolkit_assert_true( isset( $package_abilities[ $expanded_ability_id ] ), "agent workflow replay case {$case_id} references known expanded ability {$expanded_ability_id}" );
		npcink_abilities_toolkit_assert_true( 'destructive' !== ( $package_abilities[ $expanded_ability_id ]['risk_level'] ?? '' ), "agent workflow replay case {$case_id} expanded ability {$expanded_ability_id} is not destructive" );
	}
	foreach ( $expected_case['disallowed_default'] as $disallowed_ability_id ) {
		npcink_abilities_toolkit_assert_true( $disallowed_ability_id !== $expected_case['ability_id'], "agent workflow replay case {$case_id} does not disallow its preferred bundle" );
		npcink_abilities_toolkit_assert_true( isset( $package_abilities[ $disallowed_ability_id ] ), "agent workflow replay case {$case_id} references a known disallowed default ability {$disallowed_ability_id}" );
		npcink_abilities_toolkit_assert_true( 'read' !== ( $package_abilities[ $disallowed_ability_id ]['risk_level'] ?? 'read' ), "agent workflow replay case {$case_id} disallowed default {$disallowed_ability_id} is write-like" );
	}
}

$workflow_list = call_user_func( $package_abilities['npcink-abilities-toolkit/list-workflow-recipes']['execute_callback'], array() );
npcink_abilities_toolkit_assert_same( $workflow_manifest, $workflow_list, 'workflow recipe discovery ability returns provider manifest' );
$workflow_draft_alias = call_user_func( $package_abilities['npcink-abilities-toolkit/get-workflow-recipe']['execute_callback'], array( 'recipe_id' => 'article_draft_v1' ) );
npcink_abilities_toolkit_assert_same( $workflow_manifest['cases']['article_draft'], $workflow_draft_alias, 'workflow recipe detail ability resolves article_draft_v1 alias' );
$workflow_get = call_user_func( $package_abilities['npcink-abilities-toolkit/get-workflow-recipe']['execute_callback'], array( 'recipe_id' => 'workflow/wordpress_comment_compliance_handoff' ) );
npcink_abilities_toolkit_assert_same( $workflow_manifest['cases']['comment_compliance_handoff'], $workflow_get, 'workflow recipe detail ability resolves recipe id' );
$workflow_missing = call_user_func( $package_abilities['npcink-abilities-toolkit/get-workflow-recipe']['execute_callback'], array( 'recipe_id' => 'workflow/missing' ) );
npcink_abilities_toolkit_assert_true( is_wp_error( $workflow_missing ), 'workflow recipe detail ability fails closed for missing recipe' );

echo "OK: {$assertions} assertions\n";
