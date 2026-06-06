<?php
/**
 * Audits MCP-facing exposure metadata and host-governed write boundaries.
 *
 * @package NpcinkAbilitiesToolkit
 */

require_once dirname( __DIR__ ) . '/tests/bootstrap.php';

$failures = array();

function npcink_abilities_toolkit_mcp_exposure_fail( $message ) {
	global $failures;
	$failures[] = (string) $message;
}

function npcink_abilities_toolkit_mcp_exposure_assert( $condition, $message ) {
	if ( ! $condition ) {
		npcink_abilities_toolkit_mcp_exposure_fail( $message );
	}
}

Npcink_Abilities_Toolkit\Plugin::instance()->boot();

$abilities = npcink_abilities_toolkit_get_registered();

foreach ( $abilities as $ability_id => $ability ) {
	$ability_id = (string) $ability_id;
	$ability    = is_array( $ability ) ? $ability : array();
	$risk       = (string) ( $ability['risk_level'] ?? '' );
	$meta       = isset( $ability['meta'] ) && is_array( $ability['meta'] ) ? $ability['meta'] : array();
	$mcp        = isset( $meta['mcp'] ) && is_array( $meta['mcp'] ) ? $meta['mcp'] : array();
	$annotations = isset( $ability['annotations'] ) && is_array( $ability['annotations'] ) ? $ability['annotations'] : array();
	$server     = (string) ( $mcp['server'] ?? '' );
	$is_public  = (bool) ( $mcp['public'] ?? false );
	$is_write   = in_array( $risk, array( 'write', 'destructive' ), true );

	npcink_abilities_toolkit_mcp_exposure_assert( in_array( $risk, array( 'read', 'write', 'destructive' ), true ), "{$ability_id} has supported risk for MCP exposure" );
	npcink_abilities_toolkit_mcp_exposure_assert( $risk === (string) ( $mcp['risk'] ?? '' ), "{$ability_id} mirrors risk in MCP metadata" );
	npcink_abilities_toolkit_mcp_exposure_assert( isset( $mcp['annotations'] ) && is_array( $mcp['annotations'] ), "{$ability_id} exposes MCP annotations" );
	npcink_abilities_toolkit_mcp_exposure_assert( $annotations === $mcp['annotations'], "{$ability_id} MCP annotations mirror ability annotations" );
	npcink_abilities_toolkit_mcp_exposure_assert( $is_write ? 'npcink-abilities-toolkit-write' === $server : 'npcink-abilities-toolkit-read' === $server, "{$ability_id} routes to the expected MCP server metadata" );

	if ( $is_public ) {
		npcink_abilities_toolkit_mcp_exposure_assert( true === (bool) ( $meta['show_in_rest'] ?? false ), "{$ability_id} MCP-public abilities are also REST-discoverable" );
	}

	if ( $is_write ) {
		npcink_abilities_toolkit_mcp_exposure_assert( true === (bool) ( $ability['requires_approval'] ?? false ), "{$ability_id} MCP write-like ability requires host approval" );
		npcink_abilities_toolkit_mcp_exposure_assert( true === (bool) ( $ability['requires_confirm'] ?? false ), "{$ability_id} MCP write-like ability requires confirm" );
		npcink_abilities_toolkit_mcp_exposure_assert( false === ( $ability['input_schema']['additionalProperties'] ?? null ), "{$ability_id} MCP write-like input rejects undeclared fields" );
		npcink_abilities_toolkit_mcp_exposure_assert( true === (bool) ( $ability['input_schema']['properties']['dry_run']['default'] ?? false ), "{$ability_id} MCP write-like dry_run defaults to true" );
		npcink_abilities_toolkit_mcp_exposure_assert( false === (bool) ( $ability['input_schema']['properties']['commit']['default'] ?? true ), "{$ability_id} MCP write-like commit defaults to false" );
	}
}

if ( ! empty( $failures ) ) {
	foreach ( $failures as $failure ) {
		fwrite( STDERR, '[fail] ' . $failure . PHP_EOL );
	}
	exit( 1 );
}

echo 'MCP exposure audit: ok (' . count( $abilities ) . " abilities)\n";
