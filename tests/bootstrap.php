<?php
/**
 * Lightweight test bootstrap.
 *
 * @package MagickAIAbilities
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'MAGICK_AI_ABILITIES_VERSION', '0.1.0-test' );

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		$key = strtolower( (string) $key );
		return preg_replace( '/[^a-z0-9_\-]/', '', $key );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $value ) {
		return trim( preg_replace( '/[\r\n\t]+/', ' ', (string) $value ) );
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		unset( $domain );
		return $text;
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability ) {
		return 'do_not_allow' !== $capability;
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public $code;
		public $message;
		public $data;

		public function __construct( $code = '', $message = '', $data = array() ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}
	}
}

require_once dirname( __DIR__ ) . '/includes/Registry/Schema_Normalizer.php';
require_once dirname( __DIR__ ) . '/includes/Registry/Annotation_Normalizer.php';
require_once dirname( __DIR__ ) . '/includes/Security/Permission_Callbacks.php';
require_once dirname( __DIR__ ) . '/includes/Registry/Contract_Normalizer.php';
require_once dirname( __DIR__ ) . '/includes/Registry/Category_Registrar.php';
require_once dirname( __DIR__ ) . '/includes/Registry/Ability_Registrar.php';
require_once dirname( __DIR__ ) . '/includes/Integration/Magick_Catalog_Bridge.php';
require_once dirname( __DIR__ ) . '/includes/Packages/Core_Read_Package.php';
require_once dirname( __DIR__ ) . '/includes/Packages/Core_Write_Package.php';
require_once dirname( __DIR__ ) . '/includes/Packages/Core_Destructive_Package.php';
