<?php
/**
 * Centralized user-facing documentation links.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds documentation URLs from one filterable base.
 *
 * The default base points at the public GitHub repository. Sites and hosts
 * that keep a reachable mirror (for example where GitHub is unreliable from
 * the site's network) repoint every admin help link with one filter instead
 * of patching each screen.
 */
final class Docs_Links {
	const DEFAULT_BASE = 'https://github.com/npcink/npcink-abilities-toolkit/blob/master';

	/**
	 * Returns the troubleshooting guide URL.
	 *
	 * @return string
	 */
	public static function troubleshooting() {
		return self::url( '/docs/troubleshooting.md' );
	}

	/**
	 * Returns the REST client quickstart URL.
	 *
	 * @return string
	 */
	public static function quickstart() {
		return self::url( '/docs/rest-client-quickstart.md' );
	}

	/**
	 * Returns the host approval contract URL.
	 *
	 * @return string
	 */
	public static function host_contract() {
		return self::url( '/docs/host-approval-contract.md' );
	}

	/**
	 * Returns one documentation URL under the filtered base.
	 *
	 * @param string $path Repository-relative blob path, starting with a slash.
	 * @return string
	 */
	public static function url( $path ) {
		$base = self::DEFAULT_BASE;
		if ( function_exists( 'apply_filters' ) ) {
			/**
			 * Filters the base URL for admin documentation links.
			 *
			 * Repoint this at a reachable mirror of the public repository when
			 * the default host is not reachable from the site's network. The
			 * value is a URL prefix; a trailing slash is trimmed.
			 *
			 * @param string $base Default documentation base URL.
			 */
			$filtered = apply_filters( 'npcink_abilities_toolkit_docs_base_url', $base );
			if ( is_string( $filtered ) && '' !== $filtered ) {
				$base = rtrim( $filtered, '/' );
			}
		}

		return $base . $path;
	}
}
