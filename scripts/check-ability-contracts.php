<?php
/**
 * Audits the registered first-party ability surface for agent-ready contracts.
 *
 * @package NpcinkAbilitiesToolkit
 */

require_once dirname( __DIR__ ) . '/tests/bootstrap.php';

$failures = array();

/**
 * Records a failed contract assertion.
 *
 * @param string $message Failure message.
 * @return void
 */
function npcink_abilities_toolkit_contract_audit_fail( $message ) {
	global $failures;
	$failures[] = (string) $message;
}

/**
 * Returns nested array data or null.
 *
 * @param array<int|string,mixed> $value Source array.
 * @param array<int,string>       $path  Nested keys.
 * @return mixed
 */
function npcink_abilities_toolkit_contract_audit_get( array $value, array $path ) {
	foreach ( $path as $key ) {
		if ( ! is_array( $value ) || ! array_key_exists( $key, $value ) ) {
			return null;
		}
		$value = $value[ $key ];
	}

	return $value;
}

/**
 * Checks a normalized schema is an object schema.
 *
 * @param string              $ability_id Ability id.
 * @param array<string,mixed> $ability    Ability definition.
 * @param string              $schema_key Schema field.
 * @return void
 */
function npcink_abilities_toolkit_contract_audit_schema( $ability_id, array $ability, $schema_key ) {
	$schema = isset( $ability[ $schema_key ] ) && is_array( $ability[ $schema_key ] )
		? $ability[ $schema_key ]
		: array();

	if ( 'object' !== (string) ( $schema['type'] ?? '' ) ) {
		npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} {$schema_key} must be an object schema" );
	}

	if ( isset( $schema['properties'] ) && ! is_array( $schema['properties'] ) ) {
		npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} {$schema_key}.properties must be an object map when present" );
	}
}

/**
 * Checks one normalized ability definition.
 *
 * @param string              $ability_id Ability id.
 * @param array<string,mixed> $ability    Ability definition.
 * @return void
 */
function npcink_abilities_toolkit_contract_audit_ability( $ability_id, array $ability ) {
	if ( $ability_id !== (string) ( $ability['ability_id'] ?? '' ) ) {
		npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} must match ability.ability_id" );
	}

	if ( false === strpos( $ability_id, '/' ) ) {
		npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} must use namespace/name format" );
	}

	foreach ( array( 'label', 'description', 'contract_version' ) as $field ) {
		if ( '' === trim( (string) ( $ability[ $field ] ?? '' ) ) ) {
			npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} is missing {$field}" );
		}
	}

	if ( ! is_callable( $ability['execute_callback'] ?? null ) ) {
		npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} execute_callback must be callable" );
	}

	if ( ! is_callable( $ability['permission_callback'] ?? null ) ) {
		npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} permission_callback must be callable" );
	}

	npcink_abilities_toolkit_contract_audit_schema( $ability_id, $ability, 'input_schema' );
	npcink_abilities_toolkit_contract_audit_schema( $ability_id, $ability, 'output_schema' );

	$risk_level = (string) ( $ability['risk_level'] ?? '' );
	if ( ! in_array( $risk_level, array( 'read', 'write', 'destructive' ), true ) ) {
		npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} has unsupported risk_level {$risk_level}" );
		return;
	}

	$is_write = in_array( $risk_level, array( 'write', 'destructive' ), true );

	if ( $is_write !== (bool) ( $ability['requires_confirm'] ?? false ) ) {
		npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} requires_confirm does not match risk level" );
	}

	if ( $is_write !== (bool) ( $ability['requires_approval'] ?? false ) ) {
		npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} requires_approval does not match risk level" );
	}

	if ( true !== (bool) npcink_abilities_toolkit_contract_audit_get( $ability, array( 'meta', 'show_in_rest' ) ) ) {
		npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} must be visible through REST metadata" );
	}

	if ( $risk_level !== (string) npcink_abilities_toolkit_contract_audit_get( $ability, array( 'meta', 'mcp', 'risk' ) ) ) {
		npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} MCP risk must mirror risk_level" );
	}

	if ( $risk_level !== (string) npcink_abilities_toolkit_contract_audit_get( $ability, array( 'meta', 'npcink', 'risk_level' ) ) ) {
		npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} Npcink risk must mirror risk_level" );
	}

	if ( $ability_id !== (string) npcink_abilities_toolkit_contract_audit_get( $ability, array( 'meta', 'npcink', 'canonical_ability_id' ) ) ) {
		npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} Npcink metadata must preserve canonical ability id" );
	}

	$annotations = isset( $ability['annotations'] ) && is_array( $ability['annotations'] )
		? $ability['annotations']
		: array();
	if ( ( 'read' === $risk_level ) !== (bool) ( $annotations['readonly'] ?? false ) ) {
		npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} readonly annotation must match risk level" );
	}

	if ( ( 'destructive' === $risk_level ) !== (bool) ( $annotations['destructive'] ?? false ) ) {
		npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} destructive annotation must match risk level" );
	}

	$properties = isset( $ability['input_schema']['properties'] ) && is_array( $ability['input_schema']['properties'] )
		? $ability['input_schema']['properties']
		: array();

	foreach ( array( 'dry_run', 'commit', 'idempotency_key' ) as $control ) {
		if ( $is_write && ! isset( $properties[ $control ] ) ) {
			npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} write input is missing {$control}" );
		}
		if ( ! $is_write && isset( $properties[ $control ] ) ) {
			npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} read input must not expose {$control}" );
		}
	}

	if ( $is_write ) {
		if ( true !== (bool) ( $properties['dry_run']['default'] ?? false ) ) {
			npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} dry_run must default to true" );
		}
		if ( false !== (bool) ( $properties['commit']['default'] ?? true ) ) {
			npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} commit must default to false" );
		}
		if ( 190 < (int) ( $properties['idempotency_key']['maxLength'] ?? 191 ) ) {
			npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} idempotency_key maxLength must be <= 190" );
		}
		if ( false !== ( $ability['input_schema']['additionalProperties'] ?? null ) ) {
			npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} write input schema must reject undeclared fields" );
		}
	}
}

/**
 * Checks that priority agent entry and write abilities expose static usage guidance.
 *
 * @param array<string,array<string,mixed>> $abilities Registered abilities.
 * @return void
 */
function npcink_abilities_toolkit_contract_audit_agent_usage( array $abilities ) {
	$required_agent_usage = array(
		'npcink-abilities-toolkit/list-workflow-recipes',
		'npcink-abilities-toolkit/get-workflow-recipe',
		'npcink-abilities-toolkit/wp-diagnostics-summary',
		'npcink-abilities-toolkit/get-article-publish-preflight-context',
		'npcink-abilities-toolkit/get-old-article-refresh-context',
		'npcink-abilities-toolkit/get-media-cleanup-opportunities',
		'npcink-abilities-toolkit/propose-post-taxonomy-terms',
		'npcink-abilities-toolkit/get-comment-compliance-handoff',
		'npcink-abilities-toolkit/create-draft',
		'npcink-abilities-toolkit/set-post-seo-meta',
		'npcink-abilities-toolkit/update-media-details',
		'npcink-abilities-toolkit/approve-comment',
		'npcink-abilities-toolkit/delete-media-permanently',
	);

	foreach ( $required_agent_usage as $ability_id ) {
		if ( ! isset( $abilities[ $ability_id ] ) || ! is_array( $abilities[ $ability_id ] ) ) {
			npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} is missing from the registered ability set" );
			continue;
		}

		$ability    = $abilities[ $ability_id ];
		$usage      = isset( $ability['agent_usage'] ) && is_array( $ability['agent_usage'] )
			? $ability['agent_usage']
			: array();
		$meta_usage = npcink_abilities_toolkit_contract_audit_get( $ability, array( 'meta', 'agent_usage' ) );

		if ( $usage !== $meta_usage ) {
			npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} meta.agent_usage must mirror agent_usage" );
		}

		foreach ( array( 'when_to_use', 'not_for', 'best_for', 'stopping_points' ) as $field ) {
			if ( empty( $usage[ $field ] ) || ! is_array( $usage[ $field ] ) ) {
				npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} agent_usage.{$field} must contain at least one item" );
			}
		}
	}
}

/**
 * Checks workflow recipes only reference registered ability ids.
 *
 * @param array<string,array<string,mixed>> $abilities Registered abilities.
 * @return void
 */
function npcink_abilities_toolkit_contract_audit_workflow_recipes( array $abilities ) {
	$manifest = npcink_abilities_toolkit_get_workflow_definitions();
	$cases    = isset( $manifest['cases'] ) && is_array( $manifest['cases'] )
		? $manifest['cases']
		: array();

	foreach ( $cases as $case_id => $case ) {
		if ( ! is_array( $case ) ) {
			npcink_abilities_toolkit_contract_audit_fail( "workflow case {$case_id} must be an object" );
			continue;
		}

		foreach ( array( 'recipe_id', 'title', 'entrypoint_ability_id', 'failure_policy' ) as $field ) {
			if ( '' === trim( (string) ( $case[ $field ] ?? '' ) ) ) {
				npcink_abilities_toolkit_contract_audit_fail( "workflow case {$case_id} is missing {$field}" );
			}
		}

		$referenced = array_merge(
			array( (string) ( $case['entrypoint_ability_id'] ?? '' ), (string) ( $case['preferred_ability_id'] ?? '' ) ),
			isset( $case['expanded_ability_ids'] ) && is_array( $case['expanded_ability_ids'] ) ? $case['expanded_ability_ids'] : array()
		);

		foreach ( array_filter( array_unique( $referenced ) ) as $ability_id ) {
			if ( ! isset( $abilities[ $ability_id ] ) ) {
				npcink_abilities_toolkit_contract_audit_fail( "workflow case {$case_id} references missing ability {$ability_id}" );
				continue;
			}

			if ( 'read' !== (string) ( $abilities[ $ability_id ]['risk_level'] ?? '' ) ) {
				npcink_abilities_toolkit_contract_audit_fail( "workflow case {$case_id} must reference read-only recipe abilities; {$ability_id} is not read" );
			}
		}
	}
}

Npcink_Abilities_Toolkit\Plugin::instance()->boot();

$abilities = npcink_abilities_toolkit_get_registered();
if ( empty( $abilities ) ) {
	npcink_abilities_toolkit_contract_audit_fail( 'No registered abilities found after default boot.' );
}

foreach ( $abilities as $ability_id => $ability ) {
	npcink_abilities_toolkit_contract_audit_ability( (string) $ability_id, is_array( $ability ) ? $ability : array() );
}

npcink_abilities_toolkit_contract_audit_agent_usage( $abilities );
npcink_abilities_toolkit_contract_audit_workflow_recipes( $abilities );

if ( ! empty( $failures ) ) {
	foreach ( $failures as $failure ) {
		fwrite( STDERR, '[fail] ' . $failure . PHP_EOL );
	}
	exit( 1 );
}

echo 'ability contract readiness: ok (' . count( $abilities ) . " abilities)\n";
