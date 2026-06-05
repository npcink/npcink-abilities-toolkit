<?php
/**
 * Audits one or more ability catalog JSON files for ownership and governance contract drift.
 *
 * @package NpcinkAbilitiesToolkit
 */

$args = array_slice( $argv, 1 );
if ( empty( $args ) || in_array( '--help', $args, true ) || in_array( '-h', $args, true ) ) {
	fwrite( STDOUT, "Usage: php scripts/audit-ability-catalog.php <catalog.json> [other-catalog.json ...]\n" );
	fwrite( STDOUT, "Catalogs may be maps with an abilities object, lists of ability objects, or direct ability-id maps.\n" );
	exit( empty( $args ) ? 1 : 0 );
}

$failures = array();
$seen     = array();

function maa_catalog_audit_fail( $message ) {
	global $failures;
	$failures[] = (string) $message;
}

function maa_catalog_audit_is_list( array $value ) {
	if ( empty( $value ) ) {
		return true;
	}

	return array_keys( $value ) === range( 0, count( $value ) - 1 );
}

function maa_catalog_audit_read_json( $path ) {
	if ( ! is_readable( $path ) ) {
		maa_catalog_audit_fail( 'Missing readable catalog: ' . $path );
		return array();
	}

	$json = file_get_contents( $path );
	$data = json_decode( is_string( $json ) ? $json : '', true );
	if ( ! is_array( $data ) ) {
		maa_catalog_audit_fail( 'Catalog is not valid JSON: ' . $path );
		return array();
	}

	return $data;
}

function maa_catalog_audit_extract_abilities( array $catalog ) {
	if ( isset( $catalog['abilities'] ) && is_array( $catalog['abilities'] ) ) {
		$catalog = $catalog['abilities'];
	}

	$abilities = array();
	if ( maa_catalog_audit_is_list( $catalog ) ) {
		foreach ( $catalog as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$ability_id = (string) ( $item['ability_id'] ?? ( $item['id'] ?? '' ) );
			if ( '' !== $ability_id ) {
				$abilities[ $ability_id ] = $item;
			}
		}

		return $abilities;
	}

	foreach ( $catalog as $key => $item ) {
		if ( ! is_string( $key ) || ! is_array( $item ) ) {
			continue;
		}
		$ability_id = false !== strpos( $key, '/' ) ? $key : (string) ( $item['ability_id'] ?? ( $item['id'] ?? '' ) );
		if ( '' !== $ability_id ) {
			$abilities[ $ability_id ] = $item;
		}
	}

	return $abilities;
}

function maa_catalog_audit_input_properties( array $ability ) {
	if ( isset( $ability['input_schema']['properties'] ) && is_array( $ability['input_schema']['properties'] ) ) {
		return $ability['input_schema']['properties'];
	}
	if ( isset( $ability['input']['properties'] ) && is_array( $ability['input']['properties'] ) ) {
		return $ability['input']['properties'];
	}

	return array();
}

function maa_catalog_audit_get( array $value, array $path ) {
	foreach ( $path as $key ) {
		if ( ! is_array( $value ) || ! array_key_exists( $key, $value ) ) {
			return null;
		}
		$value = $value[ $key ];
	}

	return $value;
}

function maa_catalog_audit_ability( $path, $ability_id, array $ability ) {
	$risk_level = (string) ( $ability['risk_level'] ?? '' );
	if ( '' === $risk_level ) {
		maa_catalog_audit_fail( "{$path}: {$ability_id} is missing risk_level" );
		return;
	}

	if ( ! in_array( $risk_level, array( 'read', 'write', 'destructive' ), true ) ) {
		maa_catalog_audit_fail( "{$path}: {$ability_id} has unsupported risk_level {$risk_level}" );
	}

	$show_in_rest = maa_catalog_audit_get( $ability, array( 'meta', 'show_in_rest' ) );
	if ( null !== $show_in_rest && true !== (bool) $show_in_rest ) {
		maa_catalog_audit_fail( "{$path}: {$ability_id} has meta.show_in_rest=false" );
	}

	if ( ! in_array( $risk_level, array( 'write', 'destructive' ), true ) ) {
		return;
	}

	if ( true !== (bool) ( $ability['requires_approval'] ?? false ) ) {
		maa_catalog_audit_fail( "{$path}: {$ability_id} must expose requires_approval=true" );
	}

	$properties = maa_catalog_audit_input_properties( $ability );
	foreach ( array( 'dry_run', 'commit', 'idempotency_key' ) as $control ) {
		if ( ! isset( $properties[ $control ] ) || ! is_array( $properties[ $control ] ) ) {
			maa_catalog_audit_fail( "{$path}: {$ability_id} input is missing {$control}" );
		}
	}

	if ( isset( $properties['dry_run']['default'] ) && true !== (bool) $properties['dry_run']['default'] ) {
		maa_catalog_audit_fail( "{$path}: {$ability_id} dry_run must default to true" );
	}
	if ( isset( $properties['commit']['default'] ) && false !== (bool) $properties['commit']['default'] ) {
		maa_catalog_audit_fail( "{$path}: {$ability_id} commit must default to false" );
	}
	if ( isset( $properties['idempotency_key']['maxLength'] ) && 190 < (int) $properties['idempotency_key']['maxLength'] ) {
		maa_catalog_audit_fail( "{$path}: {$ability_id} idempotency_key maxLength must be <= 190" );
	}
}

foreach ( $args as $path ) {
	$catalog   = maa_catalog_audit_read_json( $path );
	$abilities = maa_catalog_audit_extract_abilities( $catalog );
	if ( empty( $abilities ) ) {
		maa_catalog_audit_fail( 'No abilities found in catalog: ' . $path );
		continue;
	}

	foreach ( $abilities as $ability_id => $ability ) {
		if ( isset( $seen[ $ability_id ] ) ) {
			maa_catalog_audit_fail( "{$ability_id} appears in both {$seen[$ability_id]} and {$path}" );
		} else {
			$seen[ $ability_id ] = $path;
		}

		maa_catalog_audit_ability( $path, $ability_id, is_array( $ability ) ? $ability : array() );
	}
}

if ( ! empty( $failures ) ) {
	foreach ( $failures as $failure ) {
		fwrite( STDERR, '[fail] ' . $failure . PHP_EOL );
	}
	exit( 1 );
}

echo 'ability catalog audit: ok (' . count( $seen ) . " abilities)\n";
