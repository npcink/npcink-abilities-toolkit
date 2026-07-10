<?php
/**
 * Internal class autoloader.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads Toolkit implementation classes only when their host path needs them.
 */
final class Autoloader {
	/**
	 * Whether the autoloader has already been registered.
	 *
	 * @var bool
	 */
	private static $registered = false;

	/**
	 * Registers the internal namespace autoloader.
	 *
	 * @return void
	 */
	public static function register() {
		if ( self::$registered ) {
			return;
		}

		spl_autoload_register( array( self::class, 'autoload' ) );
		self::$registered = true;
	}

	/**
	 * Loads one class or trait from the Toolkit namespace.
	 *
	 * @param string $class Fully-qualified class or trait name.
	 * @return void
	 */
	public static function autoload( $class ) {
		$prefix = __NAMESPACE__ . '\\';
		if ( 0 !== strpos( (string) $class, $prefix ) ) {
			return;
		}

		$relative = substr( (string) $class, strlen( $prefix ) );
		if ( false === $relative || '' === $relative ) {
			return;
		}

		$root = defined( 'NPCINK_ABILITIES_TOOLKIT_DIR' )
			? rtrim( (string) NPCINK_ABILITIES_TOOLKIT_DIR, '/\\' ) . '/'
			: dirname( __DIR__ ) . '/';
		$file = $root . 'includes/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( 0 === strpos( $relative, 'Packages\\' ) && str_ends_with( $relative, '_Read_Methods' ) ) {
			$file = $root . 'includes/Packages/Read_Traits/' . basename( str_replace( '\\', '/', $relative ) ) . '.php';
		}
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
}
