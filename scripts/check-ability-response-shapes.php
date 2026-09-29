<?php
/**
 * Verifies the registered response shape signatures for every read-risk ability.
 *
 * Every read ability must be registered in
 * tests/fixtures/ability-response-shapes.json with the declared output_schema
 * top-level property names and a shape family. Entries marked runtime_verified
 * are executed with an empty input and their runtime keys must match the
 * declared keys exactly. See docs/ability-response-shapes.md for the taxonomy.
 *
 * @package NpcinkAbilitiesToolkit
 */

require_once dirname( __DIR__ ) . '/tests/bootstrap.php';

$failures = array();

/**
 * Records one shape audit failure.
 *
 * @param string $message Failure message.
 * @return void
 */
function npcink_abilities_toolkit_shape_fail( $message ) {
	global $failures;
	$failures[] = (string) $message;
}

/**
 * Asserts one shape audit expectation.
 *
 * @param mixed  $condition Condition expected to be truthy.
 * @param string $message Assertion message.
 * @return void
 */
function npcink_abilities_toolkit_shape_assert( $condition, $message ) {
	if ( ! $condition ) {
		npcink_abilities_toolkit_shape_fail( $message );
	}
}

/**
 * Derives the documented shape family from declared top-level keys.
 *
 * @param array<string> $keys Declared output keys.
 * @return string
 */
function npcink_abilities_toolkit_shape_family( array $keys ) {
	if ( in_array( 'success', $keys, true ) ) {
		return 'success_envelope';
	}
	if ( in_array( 'total', $keys, true ) && in_array( 'items', $keys, true ) ) {
		return 'paginated_collection';
	}

	return 'raw';
}

Npcink_Abilities_Toolkit\Plugin::instance()->boot();

$abilities    = npcink_abilities_toolkit_get_registered();
$fixture_path = dirname( __DIR__ ) . '/tests/fixtures/ability-response-shapes.json';
$fixture_json = is_readable( $fixture_path ) ? (string) file_get_contents( $fixture_path ) : '';
$fixture      = json_decode( $fixture_json, true );
$fixture      = is_array( $fixture ) ? $fixture : array();
$entries      = isset( $fixture['abilities'] ) && is_array( $fixture['abilities'] ) ? $fixture['abilities'] : array();

npcink_abilities_toolkit_shape_assert( 'v1' === (string) ( $fixture['schema_version'] ?? '' ), 'response shape fixture declares schema_version v1' );

$read_abilities = array();
foreach ( $abilities as $ability_id => $ability ) {
	if ( 'read' === (string) ( $ability['risk_level'] ?? '' ) ) {
		$read_abilities[ (string) $ability_id ] = $ability;
	}
}

foreach ( $read_abilities as $ability_id => $ability ) {
	$entry    = isset( $entries[ $ability_id ] ) && is_array( $entries[ $ability_id ] ) ? $entries[ $ability_id ] : null;
	$declared = array_keys( $ability['output_schema']['properties'] ?? array() );
	$label    = $ability_id;

	if ( null === $entry ) {
		npcink_abilities_toolkit_shape_fail( "{$label} is not registered in the response shape fixture; register its declared keys and family in the same change" );
		continue;
	}

	$registered_keys = isset( $entry['keys'] ) && is_array( $entry['keys'] ) ? array_map( 'strval', $entry['keys'] ) : array();
	npcink_abilities_toolkit_shape_assert( array_values( array_diff( $declared, $registered_keys ) ) === array() && array_values( array_diff( $registered_keys, $declared ) ) === array(), "{$label} fixture keys match the declared output_schema properties" );

	$expected_family = npcink_abilities_toolkit_shape_family( $declared );
	npcink_abilities_toolkit_shape_assert( $expected_family === (string) ( $entry['family'] ?? '' ), "{$label} fixture family is {$expected_family}" );

	if ( true === (bool) ( $entry['runtime_verified'] ?? false ) ) {
		$requires_input = ! empty( $ability['input_schema']['required'] );
		$callback       = $ability['execute_callback'] ?? null;
		if ( $requires_input || ! is_callable( $callback ) ) {
			npcink_abilities_toolkit_shape_fail( "{$label} is marked runtime_verified but can no longer run with an empty input; re-verify or clear the flag in the fixture" );
		} else {
			try {
				$response = call_user_func( $callback, array() );
			} catch ( Throwable $e ) {
				$response = null;
			}
			if ( ! is_array( $response ) || is_wp_error( $response ) ) {
				npcink_abilities_toolkit_shape_fail( "{$label} is marked runtime_verified but no longer returns an array with an empty input" );
			} else {
				$runtime_keys = array_keys( $response );
				npcink_abilities_toolkit_shape_assert( array_values( array_diff( $runtime_keys, $declared ) ) === array() && array_values( array_diff( $declared, $runtime_keys ) ) === array(), "{$label} runtime response keys still match the registered shape" );
			}
		}
	}
}

foreach ( array_keys( $entries ) as $ability_id ) {
	if ( ! isset( $read_abilities[ (string) $ability_id ] ) ) {
		npcink_abilities_toolkit_shape_fail( "{$ability_id} is registered in the response shape fixture but is no longer a registered read ability; remove the stale entry" );
	}
}

if ( ! empty( $failures ) ) {
	foreach ( $failures as $failure ) {
		fwrite( STDERR, '[fail] ' . $failure . PHP_EOL );
	}
	exit( 1 );
}

$verified = 0;
foreach ( $entries as $entry ) {
	if ( true === (bool) ( $entry['runtime_verified'] ?? false ) ) {
		++$verified;
	}
}
echo 'ability response shapes: ok (' . count( $read_abilities ) . ' read abilities, ' . $verified . " runtime verified)\n";
