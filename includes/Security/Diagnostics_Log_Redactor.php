<?php
/**
 * Redacts diagnostic text before it enters messages or summary fingerprints.
 *
 * @package NpcinkAbilitiesToolkit
 */
namespace Npcink_Abilities_Toolkit\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Handles common header, query-string and structured log credential forms. */
final class Diagnostics_Log_Redactor {
	/**
	 * @param string $line Diagnostic line.
	 * @return string
	 */
	public static function redact( $line ) {
		$line = sanitize_text_field( $line );
		$keys = '(?:api[_-]?key|(?:access[_-]?|refresh[_-]?)?token|(?:client[_-]?)?secret|password|pass|pwd|authorization)';
		$line = preg_replace( '/([?&](?:key|' . $keys . ')=)[^\s&]+/i', '$1[REDACTED]', $line );
		// Redact the whole header value, including authentication scheme and credentials.
		$line = preg_replace( '/(authorization\s*[:=]\s*)(?:Bearer|Basic)\s+[^\s,;]+/i', '$1[REDACTED]', $line );
		$line = preg_replace( '/(["\']' . $keys . '["\']\s*[:=]\s*)(?:"(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\')/i', '$1"[REDACTED]"', $line );
		$line = preg_replace( '/(' . $keys . '\s*[:=]\s*)[^\s,;]+/i', '$1[REDACTED]', $line );
		$line = preg_replace( '#(?<![a-z0-9])(?:[a-z]:[\\\\/]|\\\\\\\\)[^\s"\'<>;,]+#i', '[REDACTED_PATH]', $line );
		$line = preg_replace( '#phar://[^\s\'"]+#', 'phar://[REDACTED_PATH]', $line );
		$line = preg_replace( '#/(?:private/)?tmp/[^\s:\']+#', '[TMP_PATH]', $line );
		$line = preg_replace( '#/[^\s:]+/wp-content/#', '[ABSPATH]/wp-content/', $line );
		$line = preg_replace( '#/Users/[^\s:]+/#', '[HOME]/', $line );
		$line = preg_replace( '#/[^\s:\']+\.php#', '[PHP_FILE]', $line );
		return is_string( $line ) ? $line : '';
	}
}
