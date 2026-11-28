<?php
/**
 * Boots each profile in its own process to prove the public registration set.
 *
 * @package NpcinkAbilitiesToolkit
 */
require_once __DIR__ . '/bootstrap.php';

$profile = $argv[1] ?? 'full';
$mode    = $argv[2] ?? '';
add_filter( 'npcink_abilities_toolkit_registration_profile', static function () use ( $profile ) { return $profile; } );
if ( 'media' === $mode ) {
	add_filter( 'npcink_abilities_toolkit_enabled_read_packs', static fn() => array( 'core_wordpress_read', 'media_governance', 'workflow_definitions' ) );
} else {
	// A broad per-ability filter must not widen the named profile.
	add_filter( 'npcink_abilities_toolkit_should_register_read_ability', static function () { return true; } );
}
if ( 'operator' === $mode ) {
	$GLOBALS['npcink_abilities_toolkit_unit_options']['npcink_abilities_toolkit_read_only_mode'] = '1';
}
if ( 'host' === $mode ) {
	add_filter( 'npcink_abilities_toolkit_enabled_packages', static function ( $packages ) {
		$packages['core_write'] = false;
		$packages['core_read']  = false;
		return $packages;
	} );
}
Npcink_Abilities_Toolkit\Plugin::instance()->boot();
npcink_abilities_toolkit_register_write_proposal( 'acme/profile-proposal', array(
	'label' => 'External proposal', 'description' => 'Profiles only narrow built-in packs.',
	'input_schema' => array( 'type' => 'object' ), 'output_schema' => array( 'type' => 'object' ),
	'execute_callback' => static function () { return array( 'preview' => array() ); },
) );
$abilities = npcink_abilities_toolkit_get_registered();
$failures  = array();
$check = static function ( $ok, $message ) use ( &$failures ) {
	if ( ! $ok ) { $failures[] = $message; }
};
$core_only = ! in_array( $profile, array( 'full', 'read_only' ), true );
$writes    = 'full' === $profile && '' === $mode;
$check( isset( $abilities['npcink-abilities-toolkit/create-draft'] ) === $writes, 'write registration follows profile, host and operator narrowing' );
$check( isset( $abilities['npcink-abilities-toolkit/delete-post-permanently'] ) === ( 'full' === $profile && 'operator' !== $mode ), 'destructive registration follows profile and operator choice' );
$check( isset( $abilities['npcink-abilities-toolkit/site-info'] ) === ( 'host' !== $mode ), 'host can still disable core reads' );
$check( isset( $abilities['npcink-abilities-toolkit/list-workflow-recipes'] ) === ( ! $core_only && 'host' !== $mode ), 'workflow discovery follows read breadth' );
$check( isset( $abilities['npcink-abilities-toolkit/wp-diagnostics-summary'] ) === ( ! $core_only && ! in_array( $mode, array( 'host', 'media' ), true ) ), 'optional diagnostics cannot leak past a narrowed profile' );
$check( isset( $abilities['acme/profile-proposal'] ), 'third-party provider registration stays independent' );
foreach ( $abilities as $id => $ability ) {
	if ( 0 !== strpos( $id, 'npcink-abilities-toolkit/' ) || $writes || ( 'host' === $mode && 'destructive' === $ability['risk_level'] ) ) { continue; }
	$check( 'read' === $ability['risk_level'], 'read-only profile has no built-in write-like abilities: ' . $id );
}
$packages = Npcink_Abilities_Toolkit\Plugin::instance()->get_enabled_packages();
$check( true === $packages['admin_test_page'], 'profiles keep the operator status page' );
$check( ! $core_only || false === $packages['npcink_catalog_bridge'], 'core-read profile has no optional Npcink projection bridge' );
if ( isset( $abilities['npcink-abilities-toolkit/list-workflow-recipes'] ) ) {
	$callback = $abilities['npcink-abilities-toolkit/list-workflow-recipes']['execute_callback'];
	$manifest = npcink_abilities_toolkit_get_workflow_definitions();
	$check( $manifest === $callback( array() ), 'default discovery preserves the static manifest' );
	$check( $manifest === $callback( array( 'include_entrypoint_status' => false ) ), 'explicit false preserves the static manifest' );
	$rows = $callback( array( 'include_entrypoint_status' => true ) )['cases'];
	$check( array_keys( $manifest['cases'] ) === array_keys( $rows ), 'visibility does not hide static recipes' );
	$check( array( 'registered' => true, 'rest_exposed' => true, 'mcp_public' => false ) === $rows['media_optimization']['entrypoint_status'], 'media plan is registered for REST but not default MCP' );
	$check( ( 'media' !== $mode ) === $rows['diagnostics_triage']['entrypoint_status']['registered'], 'disabled diagnostics remains visible as an unregistered entrypoint' );
	$check( $manifest === npcink_abilities_toolkit_get_workflow_definitions(), 'optional status never alters the static provider' );
	$check( false !== strpos( Npcink_Abilities_Toolkit\Admin\Catalog_Presentation::registry_summary( $abilities ), '1 from other providers' ), 'the overview counts the independent provider separately' );
	$label = Npcink_Abilities_Toolkit\Admin\Catalog_Presentation::entrypoint_label( $rows['diagnostics_triage']['entrypoint_ability_id'], $abilities );
	$check( ( 'media' === $mode ) === ( false !== strpos( $label, 'not registered' ) ), 'the overview explains when the entrypoint was not registered' );
}
if ( $failures ) {
	fwrite( STDERR, implode( "\n", $failures ) . "\n" );
	exit( 1 );
}
echo "OK: registration profile {$profile} {$mode}\n";
