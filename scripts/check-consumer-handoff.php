<?php
/**
 * Verifies that a host can build governance proposal payloads from discovered ability contracts.
 *
 * @package MagickAIAbilities
 */

require_once dirname( __DIR__ ) . '/tests/bootstrap.php';

use Magick_AI_Abilities\Packages\Core_Comment_Package;
use Magick_AI_Abilities\Packages\Core_Destructive_Package;
use Magick_AI_Abilities\Packages\Core_Read_Package;
use Magick_AI_Abilities\Packages\Core_Write_Package;
use Magick_AI_Abilities\Registry\Ability_Registrar;
use Magick_AI_Abilities\Registry\Annotation_Normalizer;
use Magick_AI_Abilities\Registry\Category_Registrar;
use Magick_AI_Abilities\Registry\Contract_Normalizer;
use Magick_AI_Abilities\Registry\Schema_Normalizer;

$failures = array();

function maa_consumer_handoff_fail( $message ) {
	global $failures;
	$failures[] = (string) $message;
}

function maa_consumer_handoff_assert( $condition, $message ) {
	if ( ! $condition ) {
		maa_consumer_handoff_fail( $message );
	}
}

function maa_consumer_handoff_json( $path ) {
	if ( ! is_readable( $path ) ) {
		maa_consumer_handoff_fail( 'Missing readable fixture: ' . $path );
		return array();
	}

	$json = file_get_contents( $path );
	$data = json_decode( is_string( $json ) ? $json : '', true );
	if ( ! is_array( $data ) ) {
		maa_consumer_handoff_fail( 'Fixture is not valid JSON object: ' . $path );
		return array();
	}

	return $data;
}

function maa_consumer_handoff_catalog() {
	$schema_normalizer     = new Schema_Normalizer();
	$annotation_normalizer = new Annotation_Normalizer();
	$contract_normalizer   = new Contract_Normalizer( $schema_normalizer, $annotation_normalizer );
	$categories            = new Category_Registrar();
	$registrar             = new Ability_Registrar( $categories, $contract_normalizer );

	$core_read_package = new Core_Read_Package( $categories, $registrar );
	$core_read_package->boot();
	$core_write_package = new Core_Write_Package( $categories, $registrar );
	$core_write_package->boot();
	$core_destructive_package = new Core_Destructive_Package( $categories, $registrar );
	$core_destructive_package->boot();
	$core_comment_package = new Core_Comment_Package( $categories, $registrar );
	$core_comment_package->boot();

	return $registrar->all();
}

function maa_consumer_handoff_proposal_payload( $ability_id, array $ability, array $input ) {
	$required_fields = isset( $ability['input_schema']['required'] ) && is_array( $ability['input_schema']['required'] )
		? $ability['input_schema']['required']
		: array();

	return array(
		'ability_id' => $ability_id,
		'title'      => 'Governed proposal for ' . $ability_id,
		'summary'    => 'Prepared from discovered ability contract metadata.',
		'input'      => $input,
		'preview'    => array(
			'ability_risk_level'    => (string) ( $ability['risk_level'] ?? '' ),
			'requires_approval'     => (bool) ( $ability['requires_approval'] ?? false ),
			'input_required_fields' => $required_fields,
		),
		'caller'     => array(
			'source'      => 'consumer-handoff-check',
			'caller_type' => 'product_plugin',
		),
	);
}

function maa_consumer_handoff_get_ability( array $abilities, $ability_id, $label ) {
	$ability_id = (string) $ability_id;
	maa_consumer_handoff_assert( isset( $abilities[ $ability_id ] ), $label . ' discovers ' . $ability_id );

	return isset( $abilities[ $ability_id ] ) && is_array( $abilities[ $ability_id ] )
		? $abilities[ $ability_id ]
		: array();
}

function maa_consumer_handoff_assert_schema_object( array $ability, $ability_id, $schema_key ) {
	$schema = isset( $ability[ $schema_key ] ) && is_array( $ability[ $schema_key ] )
		? $ability[ $schema_key ]
		: array();

	maa_consumer_handoff_assert( 'object' === (string) ( $schema['type'] ?? '' ), $ability_id . ' exposes object ' . $schema_key );
	maa_consumer_handoff_assert( isset( $schema['properties'] ) && is_array( $schema['properties'] ), $ability_id . ' exposes ' . $schema_key . ' properties' );
}

function maa_consumer_handoff_assert_rest_metadata( array $ability, $ability_id ) {
	$meta        = isset( $ability['meta'] ) && is_array( $ability['meta'] ) ? $ability['meta'] : array();
	$magick_meta = isset( $meta['magick'] ) && is_array( $meta['magick'] ) ? $meta['magick'] : array();
	$mcp_meta    = isset( $meta['mcp'] ) && is_array( $meta['mcp'] ) ? $meta['mcp'] : array();

	maa_consumer_handoff_assert( true === (bool) ( $meta['show_in_rest'] ?? false ), $ability_id . ' is discoverable through Abilities API REST metadata' );
	maa_consumer_handoff_assert( $ability_id === (string) ( $magick_meta['canonical_ability_id'] ?? '' ), $ability_id . ' metadata preserves canonical ability id' );
	maa_consumer_handoff_assert( (string) ( $ability['risk_level'] ?? '' ) === (string) ( $magick_meta['risk_level'] ?? '' ), $ability_id . ' metadata mirrors risk level' );
	maa_consumer_handoff_assert( (string) ( $ability['risk_level'] ?? '' ) === (string) ( $mcp_meta['risk'] ?? '' ), $ability_id . ' MCP metadata mirrors risk level' );
}

function maa_consumer_handoff_assert_read_surface_ability( array $abilities, $ability_id, $surface_id ) {
	$ability = maa_consumer_handoff_get_ability( $abilities, $ability_id, $surface_id . ' read/proposal surface' );
	if ( empty( $ability ) ) {
		return;
	}

	maa_consumer_handoff_assert( 'read' === (string) ( $ability['risk_level'] ?? '' ), $ability_id . ' remains read-risk for consumer discovery' );
	maa_consumer_handoff_assert( false === (bool) ( $ability['requires_approval'] ?? true ), $ability_id . ' does not require approval as a read/proposal surface' );
	maa_consumer_handoff_assert( false === (bool) ( $ability['requires_confirm'] ?? true ), $ability_id . ' does not require confirm as a read/proposal surface' );
	maa_consumer_handoff_assert_rest_metadata( $ability, $ability_id );
	maa_consumer_handoff_assert_schema_object( $ability, $ability_id, 'input_schema' );
	maa_consumer_handoff_assert_schema_object( $ability, $ability_id, 'output_schema' );
	foreach ( array( 'dry_run', 'commit', 'idempotency_key' ) as $control ) {
		maa_consumer_handoff_assert( ! isset( $ability['input_schema']['properties'][ $control ] ), $ability_id . ' does not expose write control ' . $control );
	}
}

function maa_consumer_handoff_assert_write_target_ability( array $abilities, $ability_id, $surface_id ) {
	$ability = maa_consumer_handoff_get_ability( $abilities, $ability_id, $surface_id . ' write target' );
	if ( empty( $ability ) ) {
		return;
	}

	$risk_level = (string) ( $ability['risk_level'] ?? '' );
	maa_consumer_handoff_assert( in_array( $risk_level, array( 'write', 'destructive' ), true ), $ability_id . ' is a governed write/destructive target' );
	maa_consumer_handoff_assert( true === (bool) ( $ability['requires_approval'] ?? false ), $ability_id . ' requires host approval' );
	maa_consumer_handoff_assert( true === (bool) ( $ability['requires_confirm'] ?? false ), $ability_id . ' requires host confirm' );
	maa_consumer_handoff_assert_rest_metadata( $ability, $ability_id );
	maa_consumer_handoff_assert_schema_object( $ability, $ability_id, 'input_schema' );
	maa_consumer_handoff_assert_schema_object( $ability, $ability_id, 'output_schema' );
	foreach ( array( 'dry_run', 'commit', 'idempotency_key' ) as $control ) {
		maa_consumer_handoff_assert( isset( $ability['input_schema']['properties'][ $control ] ), $ability_id . ' exposes governance control ' . $control );
	}
}

$fixture_path = dirname( __DIR__ ) . '/tests/fixtures/core-governance-consumer-acceptance.json';
$fixture      = maa_consumer_handoff_json( $fixture_path );
$abilities    = maa_consumer_handoff_catalog();

maa_consumer_handoff_assert( 'v1' === (string) ( $fixture['schema_version'] ?? '' ), 'consumer acceptance fixture uses schema_version v1' );
maa_consumer_handoff_assert( isset( $fixture['abilities'] ) && is_array( $fixture['abilities'] ), 'consumer acceptance fixture has abilities map' );
maa_consumer_handoff_assert( isset( $fixture['harvest_surfaces'] ) && is_array( $fixture['harvest_surfaces'] ), 'consumer acceptance fixture has harvest surfaces map' );

foreach ( (array) ( $fixture['abilities'] ?? array() ) as $ability_id => $expected ) {
	$expected = is_array( $expected ) ? $expected : array();
	maa_consumer_handoff_assert( isset( $abilities[ $ability_id ] ), 'discovered catalog includes ' . $ability_id );
	if ( ! isset( $abilities[ $ability_id ] ) || ! is_array( $abilities[ $ability_id ] ) ) {
		continue;
	}

	$ability = $abilities[ $ability_id ];
	maa_consumer_handoff_assert( (string) ( $expected['risk_level'] ?? '' ) === (string) ( $ability['risk_level'] ?? '' ), $ability_id . ' risk_level matches consumer fixture' );
	maa_consumer_handoff_assert( (bool) ( $expected['requires_approval'] ?? false ) === (bool) ( $ability['requires_approval'] ?? false ), $ability_id . ' requires_approval matches consumer fixture' );
	foreach ( (array) ( $expected['required_scopes'] ?? array() ) as $required_scope ) {
		maa_consumer_handoff_assert( in_array( $required_scope, (array) ( $ability['required_scopes'] ?? array() ), true ), $ability_id . ' exposes required scope ' . $required_scope );
	}
	foreach ( (array) ( $expected['required_input'] ?? array() ) as $required_input ) {
		maa_consumer_handoff_assert( in_array( $required_input, (array) ( $ability['input_schema']['required'] ?? array() ), true ), $ability_id . ' exposes required input ' . $required_input );
	}
	foreach ( (array) ( $expected['must_have_input_controls'] ?? array() ) as $control ) {
		maa_consumer_handoff_assert( isset( $ability['input_schema']['properties'][ $control ] ), $ability_id . ' input schema exposes governance control ' . $control );
	}
}

foreach ( (array) ( $fixture['harvest_surfaces'] ?? array() ) as $surface_id => $surface ) {
	$surface = is_array( $surface ) ? $surface : array();
	foreach ( (array) ( $surface['read_or_proposal_abilities'] ?? array() ) as $ability_id ) {
		maa_consumer_handoff_assert_read_surface_ability( $abilities, $ability_id, (string) $surface_id );
	}
	foreach ( (array) ( $surface['write_targets'] ?? array() ) as $ability_id ) {
		maa_consumer_handoff_assert_write_target_ability( $abilities, $ability_id, (string) $surface_id );
	}
}

$draft_ability = isset( $abilities['magick-ai/create-draft'] ) && is_array( $abilities['magick-ai/create-draft'] ) ? $abilities['magick-ai/create-draft'] : array();
$proposal      = maa_consumer_handoff_proposal_payload(
	'magick-ai/create-draft',
	$draft_ability,
	array(
		'title'   => 'Draft prepared through Core governance',
		'content' => '<p>Draft body prepared by the consumer handoff check.</p>',
		'dry_run' => true,
	)
);

foreach ( (array) ( $fixture['abilities']['magick-ai/create-draft']['proposal_required_keys'] ?? array() ) as $required_key ) {
	maa_consumer_handoff_assert( array_key_exists( $required_key, $proposal ), 'proposal payload includes ' . $required_key );
}
maa_consumer_handoff_assert( true === (bool) ( $proposal['input']['dry_run'] ?? false ), 'proposal input defaults to dry-run preview' );
maa_consumer_handoff_assert( ! isset( $proposal['input']['commit'] ) || false === (bool) $proposal['input']['commit'], 'proposal input does not request commit by default' );
maa_consumer_handoff_assert( true === (bool) ( $proposal['preview']['requires_approval'] ?? false ), 'proposal preview carries approval requirement' );
maa_consumer_handoff_assert( 'write' === (string) ( $proposal['preview']['ability_risk_level'] ?? '' ), 'proposal preview carries write risk' );

$example_path = dirname( __DIR__ ) . '/examples/core-governance-consumer.php';
$example      = is_readable( $example_path ) ? file_get_contents( $example_path ) : '';
maa_consumer_handoff_assert( is_string( $example ) && false !== strpos( $example, 'magick_ai_abilities_get_registered' ), 'consumer example discovers abilities through public helper' );
maa_consumer_handoff_assert( is_string( $example ) && false !== strpos( $example, 'magick-ai-core/v1/proposals' ), 'consumer example documents Core proposal endpoint' );

if ( ! empty( $failures ) ) {
	foreach ( $failures as $failure ) {
		fwrite( STDERR, '[fail] ' . $failure . PHP_EOL );
	}
	exit( 1 );
}

echo "core governance consumer handoff: ok\n";
