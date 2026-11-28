<?php
/**
 * Request-local visibility metadata for static recipe entrypoints.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Workflow;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Entrypoint_Status {
	/**
	 * Adds optional discovery metadata to a copy of the static manifest.
	 *
	 * @param mixed $input Ability input.
	 * @param array<string,mixed> $registered Toolkit registry snapshot.
	 * @return array<string,mixed>
	 */
	public static function discovery( $input, array $registered ): array {
		$manifest = Workflow_Definition_Provider::manifest();
		$include  = is_array( $input ) ? ( $input['include_entrypoint_status'] ?? false ) : false;
		// WordPress 6.9 validates REST booleans without coercing them before execution.
		$include  = is_string( $include ) ? strtolower( $include ) : $include;
		if ( in_array( $include, array( true, 1, '1', 'true' ), true ) ) {
			foreach ( (array) $manifest['cases'] as $key => $case ) {
				$case['entrypoint_status']  = self::describe( (string) $case['entrypoint_ability_id'], $registered );
				$manifest['cases'][ $key ] = $case;
			}
		}
		return $manifest;
	}

	/**
	 * Reports registration and declared channel exposure, not caller authorization.
	 *
	 * @param string $id Entry ability ID.
	 * @param array<string,mixed> $registered Toolkit registry snapshot.
	 * @return array{registered:bool, rest_exposed:bool, mcp_public:bool}
	 */
	public static function describe( string $id, array $registered ): array {
		return array(
			'registered'   => isset( $registered[ $id ] ),
			'rest_exposed' => ! empty( $registered[ $id ]['meta']['show_in_rest'] ),
			'mcp_public'   => ! empty( $registered[ $id ]['meta']['mcp']['public'] ),
		);
	}
}
