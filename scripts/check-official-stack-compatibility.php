<?php
/**
 * Verifies the local catalog shape expected by the official WordPress AI stack.
 *
 * @package NpcinkAbilitiesToolkit
 */

require_once dirname( __DIR__ ) . '/tests/bootstrap.php';

$failures = array();

function npcink_abilities_toolkit_official_stack_fail( $message ) {
	global $failures;
	$failures[] = (string) $message;
}

function npcink_abilities_toolkit_official_stack_assert( $condition, $message ) {
	if ( ! $condition ) {
		npcink_abilities_toolkit_official_stack_fail( $message );
	}
}

function npcink_abilities_toolkit_official_stack_schema_is_object( array $ability, $ability_id, $schema_key ) {
	$schema = isset( $ability[ $schema_key ] ) && is_array( $ability[ $schema_key ] )
		? $ability[ $schema_key ]
		: array();

	npcink_abilities_toolkit_official_stack_assert( 'object' === (string) ( $schema['type'] ?? '' ), "{$ability_id} {$schema_key} is an object schema" );
	npcink_abilities_toolkit_official_stack_assert( ! isset( $schema['properties'] ) || is_array( $schema['properties'] ), "{$ability_id} {$schema_key}.properties is a map when present" );
}

Npcink_Abilities_Toolkit\Plugin::instance()->boot();

$abilities = npcink_abilities_toolkit_get_registered();
$manifest  = npcink_abilities_toolkit_get_workflow_definitions();
$header    = (string) file_get_contents( dirname( __DIR__ ) . '/npcink-abilities-toolkit.php' );
$composer  = json_decode( (string) file_get_contents( dirname( __DIR__ ) . '/composer.json' ), true );
$composer  = is_array( $composer ) ? $composer : array();

npcink_abilities_toolkit_official_stack_assert( false !== strpos( $header, 'Requires at least: 7.0' ), 'plugin header targets a WordPress version with Abilities API availability' );
npcink_abilities_toolkit_official_stack_assert( 'wordpress-plugin' === (string) ( $composer['type'] ?? '' ), 'composer declares wordpress-plugin type' );
npcink_abilities_toolkit_official_stack_assert( ! isset( $composer['require']['wordpress/ai'] ), 'official AI plugin is not a runtime dependency' );
npcink_abilities_toolkit_official_stack_assert( ! isset( $composer['require']['wordpress/mcp-adapter'] ), 'official MCP Adapter is not a runtime dependency' );
npcink_abilities_toolkit_official_stack_assert( count( $abilities ) >= 100, 'catalog exposes the first-party ability pack surface' );

foreach ( $abilities as $ability_id => $ability ) {
	$ability_id = (string) $ability_id;
	$ability    = is_array( $ability ) ? $ability : array();
	$meta       = isset( $ability['meta'] ) && is_array( $ability['meta'] ) ? $ability['meta'] : array();
	$mcp_meta   = isset( $meta['mcp'] ) && is_array( $meta['mcp'] ) ? $meta['mcp'] : array();
	$npcink     = isset( $meta['npcink'] ) && is_array( $meta['npcink'] ) ? $meta['npcink'] : array();
	$risk_level = (string) ( $ability['risk_level'] ?? '' );

	npcink_abilities_toolkit_official_stack_assert( false !== strpos( $ability_id, '/' ), "{$ability_id} uses namespace/name id for Abilities API discovery" );
	npcink_abilities_toolkit_official_stack_assert( '' !== trim( (string) ( $ability['label'] ?? '' ) ), "{$ability_id} has a label for official explorer surfaces" );
	npcink_abilities_toolkit_official_stack_assert( '' !== trim( (string) ( $ability['description'] ?? '' ) ), "{$ability_id} has a description for official explorer surfaces" );
	npcink_abilities_toolkit_official_stack_assert( '' !== trim( (string) ( $ability['category'] ?? '' ) ), "{$ability_id} has a category" );
	npcink_abilities_toolkit_official_stack_assert( true === (bool) ( $meta['show_in_rest'] ?? false ), "{$ability_id} is visible through Abilities API REST metadata" );
	npcink_abilities_toolkit_official_stack_assert( is_callable( $ability['permission_callback'] ?? null ), "{$ability_id} has an executable WordPress permission callback" );
	npcink_abilities_toolkit_official_stack_assert( is_callable( $ability['execute_callback'] ?? null ), "{$ability_id} has an executable callback" );
	npcink_abilities_toolkit_official_stack_assert( $risk_level === (string) ( $mcp_meta['risk'] ?? '' ), "{$ability_id} mirrors risk in MCP metadata" );
	npcink_abilities_toolkit_official_stack_assert( $ability_id === (string) ( $npcink['wp_ability_id'] ?? '' ), "{$ability_id} preserves canonical wp_ability_id metadata" );
	npcink_abilities_toolkit_official_stack_schema_is_object( $ability, $ability_id, 'input_schema' );
	npcink_abilities_toolkit_official_stack_schema_is_object( $ability, $ability_id, 'output_schema' );
}

foreach (
	array(
		'npcink-abilities-toolkit/list-workflow-recipes',
		'npcink-abilities-toolkit/get-workflow-recipe',
		'npcink-abilities-toolkit/site-info',
		'npcink-abilities-toolkit/wp-diagnostics-summary',
		'npcink-abilities-toolkit/create-draft',
	) as $ability_id
) {
	npcink_abilities_toolkit_official_stack_assert( isset( $abilities[ $ability_id ] ), "official stack compatibility keeps priority ability {$ability_id}" );
}

npcink_abilities_toolkit_official_stack_assert( 'v1' === (string) ( $manifest['schema_version'] ?? '' ), 'workflow manifest has a stable schema version' );
npcink_abilities_toolkit_official_stack_assert( isset( $manifest['cases'] ) && is_array( $manifest['cases'] ) && count( $manifest['cases'] ) >= 5, 'workflow manifest publishes host-discoverable recipe cases' );

if ( ! empty( $failures ) ) {
	foreach ( $failures as $failure ) {
		fwrite( STDERR, '[fail] ' . $failure . PHP_EOL );
	}
	exit( 1 );
}

echo 'official WordPress AI stack compatibility: ok (' . count( $abilities ) . " abilities)\n";
