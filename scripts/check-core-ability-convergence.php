<?php
/**
 * Validates evidence gates for convergence with WordPress Core abilities.
 *
 * @package NpcinkAbilitiesToolkit
 */

require_once dirname( __DIR__ ) . '/tests/bootstrap.php';

$manifest_override = trim( (string) getenv( 'NPCINK_CORE_CONVERGENCE_MANIFEST' ) );
$manifest_path = '' !== $manifest_override
	? $manifest_override
	: dirname( __DIR__ ) . '/docs/wordpress-core-ability-convergence.json';
$document_path = dirname( __DIR__ ) . '/docs/wordpress-core-ability-convergence.md';
$raw_manifest  = (string) file_get_contents( $manifest_path );
$manifest      = json_decode( $raw_manifest, true );
$document      = (string) file_get_contents( $document_path );
$failures      = array();

/**
 * Records one convergence gate failure.
 *
 * @param string $message Failure message.
 * @return void
 */
function npcink_abilities_toolkit_core_convergence_fail( $message ) {
	global $failures;
	$failures[] = (string) $message;
}

if ( ! is_array( $manifest ) ) {
	npcink_abilities_toolkit_core_convergence_fail( 'Convergence manifest must be valid JSON.' );
	$manifest = array();
}
if ( 1 !== (int) ( $manifest['schema_version'] ?? 0 ) ) {
	npcink_abilities_toolkit_core_convergence_fail( 'Convergence manifest schema_version must be 1.' );
}
if ( '' === trim( (string) ( $manifest['observed_wordpress_version'] ?? '' ) ) ) {
	npcink_abilities_toolkit_core_convergence_fail( 'Convergence manifest must record the observed WordPress version.' );
}
if ( preg_match( '/ability_count|catalog_count|minimum[_ -]count/i', $raw_manifest ) ) {
	npcink_abilities_toolkit_core_convergence_fail( 'Convergence decisions must not use raw catalog-size targets.' );
}

Npcink_Abilities_Toolkit\Plugin::instance()->boot();
$abilities = npcink_abilities_toolkit_get_registered();
$rows      = is_array( $manifest['rows'] ?? null ) ? $manifest['rows'] : array();
if ( count( $rows ) < 3 ) {
	npcink_abilities_toolkit_core_convergence_fail( 'Convergence manifest must cover the observed Core baseline.' );
}

$classifications = array( 'core_preferred', 'toolkit_extension', 'compatibility_bridge', 'toolkit_unique', 'review_later' );
$core_statuses   = array( 'pre_release', 'stable', 'withdrawn' );
$comparison_values = array( 'equivalent', 'different', 'partial', 'unverified', 'not_applicable' );
$proof_values    = array( 'passed', 'failed', 'not_run', 'not_required' );
$decisions       = array( 'keep', 'prefer_core', 'compatibility_bridge', 'deprecate' );
$seen_core_ids   = array();

foreach ( $rows as $index => $row ) {
	if ( ! is_array( $row ) ) {
		npcink_abilities_toolkit_core_convergence_fail( "Convergence row {$index} must be an object." );
		continue;
	}
	$core_id        = (string) ( $row['core_ability_id'] ?? '' );
	$classification = (string) ( $row['classification'] ?? '' );
	$core_status    = (string) ( $row['core_contract_status'] ?? '' );
	$decision       = (string) ( $row['decision'] ?? '' );
	if ( 0 !== strpos( $core_id, 'core/' ) ) {
		npcink_abilities_toolkit_core_convergence_fail( "Convergence row {$index} must reference a core/* ability." );
	}
	if ( isset( $seen_core_ids[ $core_id ] ) ) {
		npcink_abilities_toolkit_core_convergence_fail( "Duplicate Core ability row: {$core_id}." );
	}
	$seen_core_ids[ $core_id ] = true;
	if ( ! in_array( $classification, $classifications, true ) ) {
		npcink_abilities_toolkit_core_convergence_fail( "{$core_id} has an invalid classification." );
	}
	if ( ! in_array( $core_status, $core_statuses, true ) ) {
		npcink_abilities_toolkit_core_convergence_fail( "{$core_id} has an invalid Core contract status." );
	}
	if ( ! in_array( $decision, $decisions, true ) ) {
		npcink_abilities_toolkit_core_convergence_fail( "{$core_id} has an invalid decision." );
	}

	$toolkit_ids = is_array( $row['toolkit_ability_ids'] ?? null ) ? $row['toolkit_ability_ids'] : array();
	if ( empty( $toolkit_ids ) ) {
		npcink_abilities_toolkit_core_convergence_fail( "{$core_id} must reference at least one Toolkit ability." );
	}
	foreach ( $toolkit_ids as $toolkit_id ) {
		$toolkit_id = (string) $toolkit_id;
		if ( ! isset( $abilities[ $toolkit_id ] ) ) {
			npcink_abilities_toolkit_core_convergence_fail( "{$core_id} references missing Toolkit ability {$toolkit_id}." );
		}
		if ( false === strpos( $document, $toolkit_id ) ) {
			npcink_abilities_toolkit_core_convergence_fail( "Convergence document is missing Toolkit ability {$toolkit_id}." );
		}
	}
	if ( false === strpos( $document, $core_id ) || false === strpos( $document, '`' . $classification . '`' ) ) {
		npcink_abilities_toolkit_core_convergence_fail( "Convergence document is not synchronized for {$core_id}." );
	}

	$comparison = is_array( $row['comparison'] ?? null ) ? $row['comparison'] : array();
	foreach ( array( 'input_schema', 'output_schema', 'permissions', 'error_envelope', 'write_posture' ) as $dimension ) {
		if ( ! in_array( (string) ( $comparison[ $dimension ] ?? '' ), $comparison_values, true ) ) {
			npcink_abilities_toolkit_core_convergence_fail( "{$core_id} comparison.{$dimension} is missing or invalid." );
		}
	}

	$proof = is_array( $row['consumer_proof'] ?? null ) ? $row['consumer_proof'] : array();
	foreach ( array( 'adapter', 'toolbox' ) as $consumer ) {
		if ( ! in_array( (string) ( $proof[ $consumer ] ?? '' ), $proof_values, true ) ) {
			npcink_abilities_toolkit_core_convergence_fail( "{$core_id} consumer proof for {$consumer} is missing or invalid." );
		}
	}

	if ( 'deprecate' === $decision ) {
		if ( 'stable' !== $core_status ) {
			npcink_abilities_toolkit_core_convergence_fail( "{$core_id} cannot deprecate Toolkit abilities before the Core contract is stable." );
		}
		foreach ( $comparison as $dimension => $value ) {
			if ( 'equivalent' !== $value ) {
				npcink_abilities_toolkit_core_convergence_fail( "{$core_id} cannot deprecate while comparison.{$dimension} is not equivalent." );
			}
		}
		if ( 'passed' !== (string) ( $proof['adapter'] ?? '' ) || 'passed' !== (string) ( $proof['toolbox'] ?? '' ) ) {
			npcink_abilities_toolkit_core_convergence_fail( "{$core_id} cannot deprecate without Adapter and Toolbox consumer proof." );
		}
		if ( ! in_array( $classification, array( 'core_preferred', 'compatibility_bridge' ), true ) ) {
			npcink_abilities_toolkit_core_convergence_fail( "{$core_id} deprecation requires core_preferred or compatibility_bridge classification." );
		}
		if ( (int) ( $row['minimum_overlap_releases'] ?? 0 ) < 1 ) {
			npcink_abilities_toolkit_core_convergence_fail( "{$core_id} deprecation requires at least one overlap release." );
		}
	}

	foreach ( array( 'migration', 'rollback' ) as $text_field ) {
		if ( '' === trim( (string) ( $row[ $text_field ] ?? '' ) ) ) {
			npcink_abilities_toolkit_core_convergence_fail( "{$core_id} must document {$text_field}." );
		}
	}
}

if ( ! empty( $failures ) ) {
	foreach ( $failures as $failure ) {
		fwrite( STDERR, '[fail] ' . $failure . PHP_EOL );
	}
	exit( 1 );
}

echo 'WordPress Core ability convergence: ok (' . count( $rows ) . " observed Core abilities)\n";
