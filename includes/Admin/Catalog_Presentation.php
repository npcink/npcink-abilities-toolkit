<?php
/**
 * Localized descriptions of the Toolkit registry and its declared exposure.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Admin;

use Npcink_Abilities_Toolkit\Workflow\Entrypoint_Status;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Catalog_Presentation {
	/**
	 * @param array<string,mixed> $registered Toolkit registry snapshot.
	 */
	public static function registry_summary( array $registered ): string {
		$bundled = $rest = $mcp = 0;
		foreach ( array_keys( $registered ) as $id ) {
			$status   = Entrypoint_Status::describe( $id, $registered );
			$bundled += (int) ( 0 === strpos( $id, 'npcink-abilities-toolkit/' ) );
			$rest    += (int) $status['rest_exposed'];
			$mcp     += (int) $status['mcp_public'];
		}
		return sprintf(
			/* translators: 1: bundled ability count, 2: other provider count, 3: REST count, 4: MCP count. */
			__( '%1$d bundled, %2$d from other providers. Exposure metadata: REST %3$d, MCP %4$d.', 'npcink-abilities-toolkit' ),
			$bundled,
			count( $registered ) - $bundled,
			$rest,
			$mcp
		);
	}

	/**
	 * @param string $entry Entry ability ID.
	 * @param array<string,mixed> $registered Toolkit registry snapshot.
	 */
	public static function entrypoint_label( string $entry, array $registered ): string {
		$status = Entrypoint_Status::describe( $entry, $registered );
		if ( ! $status['registered'] ) {
			return __( 'Entry not registered with the current configuration.', 'npcink-abilities-toolkit' );
		}
		if ( $status['mcp_public'] ) {
			return __( 'Entry marked for MCP discovery.', 'npcink-abilities-toolkit' );
		}
		if ( $status['rest_exposed'] ) {
			return __( 'Entry marked for REST, not default MCP.', 'npcink-abilities-toolkit' );
		}
		return __( 'Entry registered without REST or MCP exposure.', 'npcink-abilities-toolkit' );
	}
}
