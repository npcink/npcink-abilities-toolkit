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

$forbidden_patterns = array(
	'magick-ai-root',
	'/includes/abilities/',
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
