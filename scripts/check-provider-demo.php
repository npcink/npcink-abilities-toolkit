<?php
/**
 * Verifies the demo provider plugin against the public helper contract.
 *
 * @package NpcinkAbilitiesToolkit
 */

require_once dirname( __DIR__ ) . '/tests/bootstrap.php';

$failures = array();

function npcink_abilities_toolkit_provider_demo_fail( $message ) {
	global $failures;
	$failures[] = (string) $message;
}

function npcink_abilities_toolkit_provider_demo_assert( $condition, $message ) {
	if ( ! $condition ) {
		npcink_abilities_toolkit_provider_demo_fail( $message );
	}
}

Npcink_Abilities_Toolkit\Plugin::instance()->boot();

require_once dirname( __DIR__ ) . '/examples/demo-plugin/npcink-abilities-toolkit-demo.php';
do_action( 'plugins_loaded' );

$abilities = npcink_abilities_toolkit_get_registered();
$catalog   = apply_filters( 'npcink_ai_open_platform_ability_catalog', array(), array( 'source' => 'provider-demo-check' ) );

$readonly  = isset( $abilities['acme/content-inventory-summary'] ) && is_array( $abilities['acme/content-inventory-summary'] ) ? $abilities['acme/content-inventory-summary'] : array();
$projected = isset( $abilities['acme/projected-site-summary'] ) && is_array( $abilities['acme/projected-site-summary'] ) ? $abilities['acme/projected-site-summary'] : array();
$proposal  = isset( $abilities['acme/create-draft-proposal'] ) && is_array( $abilities['acme/create-draft-proposal'] ) ? $abilities['acme/create-draft-proposal'] : array();

npcink_abilities_toolkit_provider_demo_assert( ! empty( $readonly ), 'demo registers read-only inventory ability' );
npcink_abilities_toolkit_provider_demo_assert( ! empty( $projected ), 'demo registers projected read-only ability' );
npcink_abilities_toolkit_provider_demo_assert( ! empty( $proposal ), 'demo registers write proposal ability' );

foreach ( array( 'acme/content-inventory-summary' => $readonly, 'acme/projected-site-summary' => $projected ) as $ability_id => $ability ) {
	npcink_abilities_toolkit_provider_demo_assert( 'third_party' === (string) ( $ability['source'] ?? '' ), "{$ability_id} remains third-party source" );
	npcink_abilities_toolkit_provider_demo_assert( 'read' === (string) ( $ability['risk_level'] ?? '' ), "{$ability_id} is read-risk" );
	npcink_abilities_toolkit_provider_demo_assert( false === (bool) ( $ability['requires_approval'] ?? true ), "{$ability_id} does not require approval" );
	npcink_abilities_toolkit_provider_demo_assert( true === (bool) ( $ability['meta']['show_in_rest'] ?? false ), "{$ability_id} is REST-discoverable" );
	npcink_abilities_toolkit_provider_demo_assert( false === (bool) ( $ability['project_to_npcink_catalog'] ?? true ) || 'acme/projected-site-summary' === $ability_id, "{$ability_id} does not project unless explicitly opted in" );
}

npcink_abilities_toolkit_provider_demo_assert( true === (bool) ( $projected['project_to_npcink_catalog'] ?? false ), 'projected demo ability opts into Npcink catalog projection' );
npcink_abilities_toolkit_provider_demo_assert( isset( $catalog['acme_projected-site-summary'] ), 'projected demo ability appears in thin Npcink catalog projection' );
npcink_abilities_toolkit_provider_demo_assert( ! isset( $catalog['acme_content-inventory-summary'] ), 'non-projected demo ability stays out of Npcink catalog projection' );

npcink_abilities_toolkit_provider_demo_assert( 'write' === (string) ( $proposal['risk_level'] ?? '' ), 'write proposal is write-risk for host governance' );
npcink_abilities_toolkit_provider_demo_assert( false === (bool) ( $proposal['project_to_npcink_catalog'] ?? true ), 'write proposal does not project by default' );
npcink_abilities_toolkit_provider_demo_assert( true === (bool) ( $proposal['requires_approval'] ?? false ), 'write proposal requires host approval metadata' );
npcink_abilities_toolkit_provider_demo_assert( false === ( $proposal['input_schema']['additionalProperties'] ?? null ), 'write proposal rejects undeclared fields after normalization' );
npcink_abilities_toolkit_provider_demo_assert( true === (bool) ( $proposal['input_schema']['properties']['dry_run']['default'] ?? false ), 'write proposal dry_run defaults to true' );
npcink_abilities_toolkit_provider_demo_assert( false === (bool) ( $proposal['input_schema']['properties']['commit']['default'] ?? true ), 'write proposal commit defaults to false' );

if ( ! empty( $failures ) ) {
	foreach ( $failures as $failure ) {
		fwrite( STDERR, '[fail] ' . $failure . PHP_EOL );
	}
	exit( 1 );
}

echo "provider demo smoke: ok\n";
