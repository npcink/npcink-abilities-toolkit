<?php
/**
 * Checks that this standalone plugin does not drift back into Npcink AI runtime ownership.
 *
 * @package NpcinkAbilitiesToolkit
 */

$root = dirname( __DIR__ );
$failures = array();

/**
 * Records a failed boundary assertion.
 *
 * @param string $message Failure message.
 * @return void
 */
function npcink_abilities_toolkit_boundary_fail( $message ) {
	global $failures;
	$failures[] = (string) $message;
}

/**
 * Reads a UTF-8 text file.
 *
 * @param string $path File path.
 * @return string
 */
function npcink_abilities_toolkit_boundary_read( $path ) {
	if ( ! is_readable( $path ) ) {
		npcink_abilities_toolkit_boundary_fail( 'Missing readable file: ' . $path );
		return '';
	}

	$contents = file_get_contents( $path );
	return is_string( $contents ) ? $contents : '';
}

/**
 * Returns PHP files below a directory.
 *
 * @param string $directory Directory path.
 * @return array<int,string>
 */
function npcink_abilities_toolkit_boundary_php_files( $directory ) {
	if ( ! is_dir( $directory ) ) {
		return array();
	}

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator(
			$directory,
			FilesystemIterator::SKIP_DOTS
		)
	);
	$files = array();
	foreach ( $iterator as $file ) {
		if ( 'php' === strtolower( $file->getExtension() ) ) {
			$files[] = $file->getPathname();
		}
	}
	sort( $files );

	return $files;
}

/**
 * Fails when a text file contains a forbidden fragment.
 *
 * @param string   $path File path.
 * @param string[] $patterns Forbidden text fragments.
 * @param string   $context Context label.
 * @return void
 */
function npcink_abilities_toolkit_boundary_assert_absent( $path, array $patterns, $context ) {
	$contents = npcink_abilities_toolkit_boundary_read( $path );
	$relative = str_replace( dirname( __DIR__ ) . '/', '', $path );

	foreach ( $patterns as $pattern ) {
		if ( false !== strpos( $contents, $pattern ) ) {
			npcink_abilities_toolkit_boundary_fail( $relative . ' contains forbidden ' . $context . ' fragment: ' . $pattern );
		}
	}
}

/**
 * Finds a forbidden key in nested array data.
 *
 * @param mixed    $value Value to inspect.
 * @param string[] $forbidden_keys Forbidden keys.
 * @param string   $path Human-readable path.
 * @return string
 */
function npcink_abilities_toolkit_boundary_find_forbidden_key( $value, array $forbidden_keys, $path = '$' ) {
	if ( ! is_array( $value ) ) {
		return '';
	}

	foreach ( $value as $key => $child ) {
		$child_path = is_string( $key ) ? $path . '.' . $key : $path . '[]';
		if ( is_string( $key ) && in_array( $key, $forbidden_keys, true ) ) {
			return $child_path;
		}

		$found = npcink_abilities_toolkit_boundary_find_forbidden_key( $child, $forbidden_keys, $child_path );
		if ( '' !== $found ) {
			return $found;
		}
	}

	return '';
}

/**
 * Returns whether text contains a phrase after whitespace normalization.
 *
 * @param string $haystack Text to search.
 * @param string $needle   Required phrase.
 * @return bool
 */
function npcink_abilities_toolkit_boundary_contains_phrase( $haystack, $needle ) {
	$haystack = preg_replace( '/\s+/', ' ', (string) $haystack );
	$needle   = preg_replace( '/\s+/', ' ', (string) $needle );

	return is_string( $haystack ) && is_string( $needle ) && false !== strpos( $haystack, $needle );
}

$contract_path = $root . '/docs/npcink-ai-project-split-contract.md';
$contract      = npcink_abilities_toolkit_boundary_read( $contract_path );

foreach (
	array(
		'independent WordPress Abilities API package plugin',
		'Npcink AI is an optional consumer',
		'Final commit authorization',
		'Duplicate Registration Rule',
		'Dependency Rule',
		'composer check:boundary',
	) as $required_text
) {
	if ( false === strpos( $contract, $required_text ) ) {
		npcink_abilities_toolkit_boundary_fail( 'Project split contract is missing required text: ' . $required_text );
	}
}

$readme = npcink_abilities_toolkit_boundary_read( $root . '/README.md' );
if ( false === strpos( $readme, 'docs/npcink-ai-project-split-contract.md' ) ) {
	npcink_abilities_toolkit_boundary_fail( 'README must link to docs/npcink-ai-project-split-contract.md.' );
}
foreach (
	array(
		'not a workflow registry',
		'They are not a workflow registry, execution engine, scheduler, approval store, audit store, model router, prompt registry, or final write authority',
		'is a package inspection helper for registered Toolkit abilities',
		'not an authoritative replacement for WordPress Abilities API discovery or execution',
		'upload-media-from-url',
		'defaults to a dry-run preview',
		'stores no provider credentials, remote-service configuration, model routing, or cloud execution truth',
	) as $required_readme_boundary
) {
	if ( ! npcink_abilities_toolkit_boundary_contains_phrase( $readme, $required_readme_boundary ) ) {
		npcink_abilities_toolkit_boundary_fail( 'README is missing workflow metadata boundary text: ' . $required_readme_boundary );
	}
}

$readme_txt = npcink_abilities_toolkit_boundary_read( $root . '/readme.txt' );
if ( ! npcink_abilities_toolkit_boundary_contains_phrase( $readme_txt, 'They are not a workflow registry, execution engine, scheduler, approval store, audit store, model router, prompt registry, or final write authority.' ) ) {
	npcink_abilities_toolkit_boundary_fail( 'readme.txt is missing workflow metadata boundary text.' );
}

$contributing = npcink_abilities_toolkit_boundary_read( $root . '/CONTRIBUTING.md' );
foreach (
	array(
		'Toolkit is the canonical owner of reusable static workflow definitions',
		'read-only workflow definition helpers that expose static recipe metadata',
		'workflow state, scheduling, retries, queues, leases, approval stores, audit',
	) as $required_contributing_boundary
) {
	if ( ! npcink_abilities_toolkit_boundary_contains_phrase( $contributing, $required_contributing_boundary ) ) {
		npcink_abilities_toolkit_boundary_fail( 'CONTRIBUTING is missing workflow metadata boundary text: ' . $required_contributing_boundary );
	}
}

$agents = npcink_abilities_toolkit_boundary_read( $root . '/AGENTS.md' );
if ( ! npcink_abilities_toolkit_boundary_contains_phrase( $agents, 'This repository is the canonical owner of reusable, static workflow definitions' ) ) {
	npcink_abilities_toolkit_boundary_fail( 'AGENTS.md is missing canonical workflow definition ownership text.' );
}
if ( ! npcink_abilities_toolkit_boundary_contains_phrase( $agents, 'Workflow definition helpers in this repository are static, read-only recipe metadata' ) ) {
	npcink_abilities_toolkit_boundary_fail( 'AGENTS.md is missing workflow metadata boundary text.' );
}
if ( ! npcink_abilities_toolkit_boundary_contains_phrase( $agents, 'Product UI, market packaging, China-market site-owner workflows, commercial onboarding, and end-user toolbox experiences belong in consuming products' ) ) {
	npcink_abilities_toolkit_boundary_fail( 'AGENTS.md is missing product-surface ownership boundary text.' );
}

$workflow_contract = npcink_abilities_toolkit_boundary_read( $root . '/docs/workflow-definition-contract.md' );
foreach (
	array(
		'Status: active v1 contract for the current release line',
		'is the canonical owner of reusable workflow definitions in the Npcink stack',
		'not a workflow execution log, host registry, queue record, retry plan, approval record, audit record, or runtime state store',
	) as $required_workflow_contract_boundary
) {
	if ( ! npcink_abilities_toolkit_boundary_contains_phrase( $workflow_contract, $required_workflow_contract_boundary ) ) {
		npcink_abilities_toolkit_boundary_fail( 'Workflow definition contract is missing boundary text: ' . $required_workflow_contract_boundary );
	}
}

$documented_workflow_forbidden_fields = array(
	'workflow_state',
	'execution_state',
	'schedule',
	'scheduler',
	'retry_policy',
	'queue',
	'lease',
	'model',
	'model_routing',
	'prompt',
	'prompt_registry',
	'approval_store',
	'approval_policy',
	'audit_log',
	'quota',
	'commit_policy',
	'final_write_authority',
);
foreach ( $documented_workflow_forbidden_fields as $documented_workflow_forbidden_field ) {
	if ( false === strpos( $workflow_contract, '`' . $documented_workflow_forbidden_field . '`' ) ) {
		npcink_abilities_toolkit_boundary_fail( 'Workflow definition contract is missing forbidden field: ' . $documented_workflow_forbidden_field );
	}
}

$workflow_recipes = npcink_abilities_toolkit_boundary_read( $root . '/docs/workflow-recipes.md' );
foreach (
	array(
		'reference recipe list, not a runtime owned by this package',
		'contract discovery for static recipe metadata, not synchronization with a host workflow registry and not an execution obligation',
		'This file must remain declarative only',
	) as $required_workflow_recipe_boundary
) {
	if ( ! npcink_abilities_toolkit_boundary_contains_phrase( $workflow_recipes, $required_workflow_recipe_boundary ) ) {
		npcink_abilities_toolkit_boundary_fail( 'Workflow recipes doc is missing boundary text: ' . $required_workflow_recipe_boundary );
	}
}

$positioning_plan = npcink_abilities_toolkit_boundary_read( $root . '/docs/project-positioning-and-next-stage-plan.md' );
if ( ! npcink_abilities_toolkit_boundary_contains_phrase( $positioning_plan, 'Status: historical planning baseline' ) ) {
	npcink_abilities_toolkit_boundary_fail( 'Project positioning plan must be marked as historical planning baseline.' );
}
if ( ! npcink_abilities_toolkit_boundary_contains_phrase( $positioning_plan, 'Do not cite this document as the current boundary authority' ) ) {
	npcink_abilities_toolkit_boundary_fail( 'Project positioning plan must warn against current-boundary citation.' );
}

$forbidden_patterns = array(
	'magick-ai-root',
	'/includes/abilities/',
	'Npcink\\GovernanceCore',
	'Npcink\\OpenClawAdapter',
	'npcink_ai_core_run_capability',
	'npcink_ai_execute_runtime_bridge',
	'npcink_ai_dispatch_capability',
	'MAI_Capability_Request',
	'class-rest-open-platform',
);

foreach ( npcink_abilities_toolkit_boundary_php_files( $root . '/includes' ) as $file ) {
	$relative = str_replace( $root . '/', '', $file );
	$contents = npcink_abilities_toolkit_boundary_read( $file );

	foreach ( $forbidden_patterns as $pattern ) {
		if ( false !== strpos( $contents, $pattern ) ) {
			npcink_abilities_toolkit_boundary_fail( $relative . ' contains forbidden Npcink AI runtime dependency pattern: ' . $pattern );
		}
	}
}

$functions_path = $root . '/includes/functions.php';
npcink_abilities_toolkit_boundary_assert_absent(
	$functions_path,
	array(
		'npcink_abilities_toolkit_register_write_host',
		'npcink_abilities_toolkit_register_destructive',
		'npcink_abilities_toolkit_register_host_governed',
	),
	'public host-governed commit helper'
);

$workflow_path = $root . '/includes/Workflow/Workflow_Definition_Provider.php';
foreach (
	array(
		'forbidden_field_keys',
		"'workflow_state'",
		"'execution_state'",
		"'schedule'",
		"'scheduler'",
		"'retry_policy'",
		"'queue'",
		"'lease'",
		"'model'",
		"'model_routing'",
		"'prompt'",
		"'prompt_registry'",
		"'approval_store'",
		"'approval_policy'",
		"'audit_log'",
		"'quota'",
		"'commit_policy'",
		"'final_write_authority'",
) as $required_workflow_guard
) {
	if ( false === strpos( npcink_abilities_toolkit_boundary_read( $workflow_path ), $required_workflow_guard ) ) {
		npcink_abilities_toolkit_boundary_fail( 'Workflow definition provider is missing boundary guard: ' . $required_workflow_guard );
	}
}
foreach ( $documented_workflow_forbidden_fields as $documented_workflow_forbidden_field ) {
	if ( false === strpos( npcink_abilities_toolkit_boundary_read( $workflow_path ), "'" . $documented_workflow_forbidden_field . "'" ) ) {
		npcink_abilities_toolkit_boundary_fail( 'Workflow definition provider is missing documented forbidden field: ' . $documented_workflow_forbidden_field );
	}
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $root . '/' );
}
require_once $workflow_path;

$workflow_class = 'Npcink_Abilities_Toolkit\\Workflow\\Workflow_Definition_Provider';
if ( class_exists( $workflow_class ) ) {
	$workflow_cases  = call_user_func( array( $workflow_class, 'definitions' ) );
	$forbidden_keys  = call_user_func( array( $workflow_class, 'forbidden_field_keys' ) );
	$forbidden_field = npcink_abilities_toolkit_boundary_find_forbidden_key( $workflow_cases, $forbidden_keys, 'workflow_definitions' );
	if ( '' !== $forbidden_field ) {
		npcink_abilities_toolkit_boundary_fail( 'Workflow definitions contain forbidden runtime/governance field: ' . $forbidden_field );
	}
} else {
	npcink_abilities_toolkit_boundary_fail( 'Workflow definition provider class could not be loaded for boundary checks.' );
}

npcink_abilities_toolkit_boundary_assert_absent(
	$root . '/includes/Integration/Npcink_Catalog_Bridge.php',
	array(
		'backend_priority',
		'routing_policy',
		'openapi_exposure',
		'mcp_policy',
		'quota_policy',
		'approval_policy',
		'catalog_fallback',
		'tool_policy',
		'gateway_policy',
		'workflow_execution',
		'approval_store',
		'audit_log',
	),
	'projection ownership'
);

npcink_abilities_toolkit_boundary_assert_absent(
	$root . '/includes/Admin/Test_Page.php',
	array(
		'Run workflow',
		'Run Workflow',
		'workflow-run',
		'Model call',
		'model-call',
		'Provider key',
		'provider-key',
		'Cloud API key',
		'Approve proposal',
		'Commit changes',
		'Commit write',
	),
	'admin control-plane'
);

if ( ! empty( $failures ) ) {
	foreach ( $failures as $failure ) {
		fwrite( STDERR, '[fail] ' . $failure . PHP_EOL );
	}
	exit( 1 );
}

echo "npcink-abilities-toolkit project boundary: ok\n";
