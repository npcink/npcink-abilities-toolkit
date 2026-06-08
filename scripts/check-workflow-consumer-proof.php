<?php
/**
 * Verifies that host consumers can select workflow recipes without runtime coupling.
 *
 * @package NpcinkAbilitiesToolkit
 */

require_once dirname( __DIR__ ) . '/tests/bootstrap.php';

$failures = array();

function npcink_abilities_toolkit_workflow_consumer_fail( $message ) {
	global $failures;
	$failures[] = (string) $message;
}

function npcink_abilities_toolkit_workflow_consumer_assert( $condition, $message ) {
	if ( ! $condition ) {
		npcink_abilities_toolkit_workflow_consumer_fail( $message );
	}
}

function npcink_abilities_toolkit_workflow_consumer_find_forbidden_field( $value, array $forbidden_keys, $path = '$' ) {
	if ( ! is_array( $value ) ) {
		return '';
	}

	foreach ( $value as $key => $child ) {
		if ( is_string( $key ) && in_array( $key, $forbidden_keys, true ) ) {
			return $path . '.' . $key;
		}

		$child_path = is_string( $key ) ? $path . '.' . $key : $path . '[]';
		$found      = npcink_abilities_toolkit_workflow_consumer_find_forbidden_field( $child, $forbidden_keys, $child_path );
		if ( '' !== $found ) {
			return $found;
		}
	}

	return '';
}

function npcink_abilities_toolkit_workflow_consumer_tokens( $text ) {
	$parts = preg_split( '/[^a-z0-9]+/', strtolower( (string) $text ) );
	$parts = is_array( $parts ) ? $parts : array();
	$stop  = array_fill_keys(
		array(
			'a',
			'an',
			'and',
			'any',
			'as',
			'at',
			'before',
			'by',
			'can',
			'for',
			'from',
			'in',
			'into',
			'is',
			'it',
			'of',
			'or',
			'that',
			'the',
			'then',
			'this',
			'to',
			'with',
			'without',
		),
		true
	);
	$tokens = array();

	foreach ( $parts as $part ) {
		if ( strlen( $part ) < 3 || isset( $stop[ $part ] ) ) {
			continue;
		}
		$tokens[ $part ] = true;
	}

	return array_keys( $tokens );
}

function npcink_abilities_toolkit_workflow_consumer_case_corpus( array $case ) {
	$values = array(
		(string) ( $case['title'] ?? '' ),
		(string) ( $case['recipe_id'] ?? '' ),
		(string) ( $case['entrypoint_ability_id'] ?? '' ),
	);

	foreach ( array( 'natural_tasks', 'expected_sections', 'expanded_ability_ids' ) as $field ) {
		foreach ( (array) ( $case[ $field ] ?? array() ) as $value ) {
			$values[] = (string) $value;
		}
	}

	return implode( ' ', $values );
}

function npcink_abilities_toolkit_workflow_consumer_route_task( $task, array $cases ) {
	$task_tokens = npcink_abilities_toolkit_workflow_consumer_tokens( $task );
	$scores      = array();

	foreach ( $cases as $case_id => $case ) {
		$case       = is_array( $case ) ? $case : array();
		$case_terms = npcink_abilities_toolkit_workflow_consumer_tokens( npcink_abilities_toolkit_workflow_consumer_case_corpus( $case ) );
		$score      = count( array_intersect( $task_tokens, $case_terms ) );

		foreach ( (array) ( $case['natural_tasks'] ?? array() ) as $natural_task ) {
			if ( strtolower( trim( (string) $task ) ) === strtolower( trim( (string) $natural_task ) ) ) {
				$score += 1000;
			}
		}

		$scores[ (string) $case_id ] = $score;
	}

	arsort( $scores, SORT_NUMERIC );
	$case_ids = array_keys( $scores );

	return array(
		'case_id'      => (string) ( $case_ids[0] ?? '' ),
		'score'        => (int) ( $scores[ $case_ids[0] ?? '' ] ?? 0 ),
		'second_score' => (int) ( $scores[ $case_ids[1] ?? '' ] ?? 0 ),
	);
}

Npcink_Abilities_Toolkit\Plugin::instance()->boot();

$abilities      = npcink_abilities_toolkit_get_registered();
$manifest       = npcink_abilities_toolkit_get_workflow_definitions();
$fixture_path   = dirname( __DIR__ ) . '/tests/fixtures/agent-workflow-replay.json';
$fixture_json   = is_readable( $fixture_path ) ? file_get_contents( $fixture_path ) : '';
$fixture        = json_decode( is_string( $fixture_json ) ? $fixture_json : '', true );
$fixture        = is_array( $fixture ) ? $fixture : array();
$forbidden_keys = Npcink_Abilities_Toolkit\Workflow\Workflow_Definition_Provider::forbidden_field_keys();
$cases          = isset( $manifest['cases'] ) && is_array( $manifest['cases'] ) ? $manifest['cases'] : array();

npcink_abilities_toolkit_workflow_consumer_assert( $fixture === $manifest, 'replay fixture matches the production workflow definition provider' );
npcink_abilities_toolkit_workflow_consumer_assert( count( $cases ) >= 5, 'host workflow replay exposes the five stabilization recipes' );

foreach ( $cases as $case_id => $case ) {
	$case = is_array( $case ) ? $case : array();
	$case_label = (string) $case_id;
	$entrypoint = (string) ( $case['entrypoint_ability_id'] ?? '' );
	$preferred  = (string) ( $case['preferred_ability_id'] ?? '' );
	$failure    = (string) ( $case['failure_policy'] ?? '' );
	$forbidden  = npcink_abilities_toolkit_workflow_consumer_find_forbidden_field( $case, $forbidden_keys );

	npcink_abilities_toolkit_workflow_consumer_assert( '' === $forbidden, "{$case_label} omits host-runtime field {$forbidden}" );
	npcink_abilities_toolkit_workflow_consumer_assert( '' !== trim( (string) ( $case['recipe_id'] ?? '' ) ), "{$case_label} has a recipe id" );
	npcink_abilities_toolkit_workflow_consumer_assert( '' !== $entrypoint, "{$case_label} has an entrypoint ability" );
	npcink_abilities_toolkit_workflow_consumer_assert( $entrypoint === $preferred, "{$case_label} prefers the bundled entrypoint ability" );
	npcink_abilities_toolkit_workflow_consumer_assert( false !== strpos( $failure, 'fail_closed' ), "{$case_label} uses fail-closed failure policy" );
	npcink_abilities_toolkit_workflow_consumer_assert( ! empty( $case['natural_tasks'] ) && is_array( $case['natural_tasks'] ), "{$case_label} maps natural tasks to the entrypoint" );
	npcink_abilities_toolkit_workflow_consumer_assert( ! empty( $case['expected_sections'] ) && is_array( $case['expected_sections'] ), "{$case_label} names expected output sections" );
	npcink_abilities_toolkit_workflow_consumer_assert( array_key_exists( 'required_inputs', $case ) && is_array( $case['required_inputs'] ), "{$case_label} names required input keys" );
	npcink_abilities_toolkit_workflow_consumer_assert( isset( $abilities[ $entrypoint ] ), "{$case_label} entrypoint is registered" );

	foreach ( (array) ( $case['natural_tasks'] ?? array() ) as $task ) {
		$route = npcink_abilities_toolkit_workflow_consumer_route_task( $task, $cases );
		npcink_abilities_toolkit_workflow_consumer_assert( $case_label === $route['case_id'], "{$case_label} natural task routes to its own recipe" );
		npcink_abilities_toolkit_workflow_consumer_assert( $route['score'] > $route['second_score'], "{$case_label} natural task route is unambiguous" );
	}

	if ( isset( $abilities[ $entrypoint ] ) && is_array( $abilities[ $entrypoint ] ) ) {
		$ability = $abilities[ $entrypoint ];
		$meta    = isset( $ability['meta'] ) && is_array( $ability['meta'] ) ? $ability['meta'] : array();
		$mcp     = isset( $meta['mcp'] ) && is_array( $meta['mcp'] ) ? $meta['mcp'] : array();

		npcink_abilities_toolkit_workflow_consumer_assert( 'read' === (string) ( $ability['risk_level'] ?? '' ), "{$case_label} entrypoint is read-risk" );
		npcink_abilities_toolkit_workflow_consumer_assert( false === (bool) ( $ability['requires_approval'] ?? true ), "{$case_label} entrypoint does not require host approval" );
		npcink_abilities_toolkit_workflow_consumer_assert( false === (bool) ( $ability['requires_confirm'] ?? true ), "{$case_label} entrypoint does not require confirmation" );
		npcink_abilities_toolkit_workflow_consumer_assert( true === (bool) ( $meta['show_in_rest'] ?? false ), "{$case_label} entrypoint is REST-discoverable" );
		npcink_abilities_toolkit_workflow_consumer_assert( false === (bool) ( $mcp['public'] ?? false ), "{$case_label} entrypoint is not promoted into the default MCP-public allowlist" );
		npcink_abilities_toolkit_workflow_consumer_assert( (string) ( $case['required_scope'] ?? '' ) === (string) ( $ability['required_scope'] ?? '' ), "{$case_label} entrypoint scope matches recipe scope" );
		foreach ( (array) ( $case['required_inputs'] ?? array() ) as $input_key ) {
			npcink_abilities_toolkit_workflow_consumer_assert( isset( $ability['input_schema']['properties'][ $input_key ] ) || in_array( $input_key, (array) ( $ability['input_schema']['required'] ?? array() ), true ), "{$case_label} entrypoint schema exposes {$input_key}" );
		}
	}

	foreach ( (array) ( $case['expanded_ability_ids'] ?? array() ) as $ability_id ) {
		npcink_abilities_toolkit_workflow_consumer_assert( isset( $abilities[ $ability_id ] ), "{$case_label} expanded ability {$ability_id} is registered" );
		if ( isset( $abilities[ $ability_id ] ) && is_array( $abilities[ $ability_id ] ) ) {
			npcink_abilities_toolkit_workflow_consumer_assert( 'read' === (string) ( $abilities[ $ability_id ]['risk_level'] ?? '' ), "{$case_label} expanded ability {$ability_id} is read-risk" );
		}
	}

	foreach ( (array) ( $case['disallowed_default_ability_ids'] ?? array() ) as $ability_id ) {
		npcink_abilities_toolkit_workflow_consumer_assert( isset( $abilities[ $ability_id ] ), "{$case_label} disallowed write target {$ability_id} is registered" );
		if ( isset( $abilities[ $ability_id ] ) && is_array( $abilities[ $ability_id ] ) ) {
			npcink_abilities_toolkit_workflow_consumer_assert( 'read' !== (string) ( $abilities[ $ability_id ]['risk_level'] ?? '' ), "{$case_label} disallowed target {$ability_id} is write-like" );
			npcink_abilities_toolkit_workflow_consumer_assert( true === (bool) ( $abilities[ $ability_id ]['requires_approval'] ?? false ), "{$case_label} disallowed target {$ability_id} requires host approval" );
		}
		npcink_abilities_toolkit_workflow_consumer_assert( ! in_array( $ability_id, (array) ( $case['expanded_ability_ids'] ?? array() ), true ), "{$case_label} does not include {$ability_id} in the expanded read chain" );
		npcink_abilities_toolkit_workflow_consumer_assert( $ability_id !== $entrypoint, "{$case_label} does not select {$ability_id} as default entrypoint" );
	}
}

if ( ! empty( $failures ) ) {
	foreach ( $failures as $failure ) {
		fwrite( STDERR, '[fail] ' . $failure . PHP_EOL );
	}
	exit( 1 );
}

echo 'workflow consumer proof: ok (' . count( $cases ) . " recipes)\n";
