<?php
/**
 * Ordered regression suite part: 10-registry-plugin-boot.
 *
 * Included by tests/run.php; parts must run in the original execution order.
 *
 * @package NpcinkAbilitiesToolkit
 */

use Npcink_Abilities_Toolkit\Integration\Npcink_Catalog_Bridge;
use Npcink_Abilities_Toolkit\Packages\Core_Comment_Pack_Classifier;
use Npcink_Abilities_Toolkit\Packages\Core_Comment_Package;
use Npcink_Abilities_Toolkit\Packages\Core_Destructive_Package;
use Npcink_Abilities_Toolkit\Packages\Core_Read_Pack_Classifier;
use Npcink_Abilities_Toolkit\Packages\Core_Read_Package;
use Npcink_Abilities_Toolkit\Packages\Core_Write_Package;
use Npcink_Abilities_Toolkit\Packages\Read_Definitions\Media_Governance_Read_Definitions;
use Npcink_Abilities_Toolkit\Plugin;
use Npcink_Abilities_Toolkit\Registry\Ability_Registrar;
use Npcink_Abilities_Toolkit\Registry\Annotation_Normalizer;
use Npcink_Abilities_Toolkit\Registry\Category_Registrar;
use Npcink_Abilities_Toolkit\Registry\Contract_Normalizer;
use Npcink_Abilities_Toolkit\Registry\Schema_Normalizer;
use Npcink_Abilities_Toolkit\Rest\Contract_Controller;
use Npcink_Abilities_Toolkit\Security\Permission_Callbacks;
use Npcink_Abilities_Toolkit\Support\Gutenberg_Block_Document;

$categories = new Category_Registrar();
$registrar = new Ability_Registrar( $categories, $contract_normalizer );
npcink_abilities_toolkit_assert_true(
	$registrar->add_readonly(
		'acme/site-summary',
		array(
			'label'            => 'Site Summary',
			'description'      => 'Returns site summary.',
			'input_schema'     => array( 'type' => 'object' ),
			'output_schema'    => array( 'type' => 'object' ),
			'required_scope'   => 'cap.site.read',
			'execute_callback' => static function () {
				return array();
			},
		)
	),
	'registrar accepts namespaced readonly ability'
);
npcink_abilities_toolkit_assert_true(
	! $registrar->add_readonly(
		'invalid',
		array(
			'label' => 'Invalid',
		)
	),
	'registrar rejects unnamespaced ability'
);

add_filter(
	'npcink_abilities_toolkit_enabled_packages',
	static function ( $packages ) {
		$packages['core_write']            = false;
		$packages['core_destructive']      = false;
		$packages['core_comment']          = false;
		$packages['npcink_catalog_bridge'] = false;
		$packages['admin_test_page']       = false;
		$packages['read_cache_hooks']      = false;

		return $packages;
	}
);
$plugin = Plugin::instance();
$plugin->boot();
$loaded_textdomains = isset( $GLOBALS['npcink_abilities_toolkit_unit_loaded_textdomains'] ) && is_array( $GLOBALS['npcink_abilities_toolkit_unit_loaded_textdomains'] )
	? $GLOBALS['npcink_abilities_toolkit_unit_loaded_textdomains']
	: array();
$loaded_toolkit_textdomain = false;
foreach ( $loaded_textdomains as $loaded_textdomain ) {
	if (
		is_array( $loaded_textdomain )
		&& 'npcink-abilities-toolkit' === ( $loaded_textdomain['domain'] ?? '' )
		&& false !== strpos( (string) ( $loaded_textdomain['path'] ?? '' ), 'languages' )
	) {
		$loaded_toolkit_textdomain = true;
		break;
	}
}
npcink_abilities_toolkit_assert_true( $loaded_toolkit_textdomain, 'plugin boot loads bundled translations from the languages directory' );
$plugin_abilities = $plugin->abilities()->all();
npcink_abilities_toolkit_assert_true( isset( $plugin_abilities['npcink-abilities-toolkit/site-info'] ), 'package filter keeps enabled core read package' );
npcink_abilities_toolkit_assert_true( ! isset( $plugin_abilities['npcink-abilities-toolkit/create-draft'] ), 'package filter disables core write package' );
npcink_abilities_toolkit_assert_true( ! isset( $plugin_abilities['npcink-abilities-toolkit/delete-post-permanently'] ), 'package filter disables core destructive package' );
npcink_abilities_toolkit_assert_true( ! isset( $plugin_abilities['npcink-abilities-toolkit/get-comment-queue-health'] ), 'package filter disables core comment package' );
$rest_actions = isset( $GLOBALS['npcink_abilities_toolkit_unit_actions']['rest_api_init'] ) && is_array( $GLOBALS['npcink_abilities_toolkit_unit_actions']['rest_api_init'] )
	? $GLOBALS['npcink_abilities_toolkit_unit_actions']['rest_api_init']
	: array();
$has_contract_controller_action = false;
foreach ( $rest_actions as $rest_action ) {
	if ( is_array( $rest_action ) && isset( $rest_action[0], $rest_action[1] ) && $rest_action[0] instanceof Contract_Controller && 'register_routes' === $rest_action[1] ) {
		$has_contract_controller_action = true;
		break;
	}
}
npcink_abilities_toolkit_assert_true( $has_contract_controller_action, 'plugin registers the runtime contract route on rest_api_init' );
$contract_controller = new Contract_Controller();
$GLOBALS['npcink_abilities_toolkit_unit_current_user_caps'] = array( 'manage_options' => false );
npcink_abilities_toolkit_assert_same( false, $contract_controller->can_read_contract(), 'runtime contract route rejects callers without manage_options' );
$GLOBALS['npcink_abilities_toolkit_unit_current_user_caps'] = array( 'manage_options' => true );
npcink_abilities_toolkit_assert_same( true, $contract_controller->can_read_contract(), 'runtime contract route allows callers with manage_options' );
unset( $GLOBALS['npcink_abilities_toolkit_unit_current_user_caps'] );
$runtime_contract    = $contract_controller->contract();
npcink_abilities_toolkit_assert_same( 'npcink_abilities_toolkit_contract.v1', $runtime_contract['schema_version'] ?? '', 'runtime contract exposes the schema version' );
npcink_abilities_toolkit_assert_same( '0.1.0-test', $runtime_contract['plugin_version'] ?? '', 'runtime contract exposes the plugin version' );
npcink_abilities_toolkit_assert_same( '1', $runtime_contract['runtime_contract_endpoint_version'] ?? '', 'runtime contract exposes endpoint version' );
npcink_abilities_toolkit_assert_same( 'npcink_abilities_toolkit', $runtime_contract['compatibility']['contract_family'] ?? '', 'runtime contract exposes compatibility family' );
npcink_abilities_toolkit_assert_same( '1', $runtime_contract['compatibility']['minimum_adapter_contract_version'] ?? '', 'runtime contract exposes Adapter compatibility floor' );
npcink_abilities_toolkit_assert_same( true, $runtime_contract['compatibility']['metadata_only'] ?? null, 'runtime contract is metadata-only' );
npcink_abilities_toolkit_assert_same( true, $runtime_contract['compatibility']['wordpress_abilities_api_required'] ?? null, 'runtime contract declares WordPress Abilities API requirement' );
npcink_abilities_toolkit_assert_same( count( $plugin_abilities ), $runtime_contract['ability_count'] ?? null, 'runtime contract ability count follows the registered package profile' );
npcink_abilities_toolkit_assert_same( 0, $runtime_contract['ability_risk_counts']['write'] ?? null, 'runtime contract honors disabled write package in the active profile' );
npcink_abilities_toolkit_assert_same( 0, $runtime_contract['ability_risk_counts']['destructive'] ?? null, 'runtime contract honors disabled destructive package in the active profile' );
npcink_abilities_toolkit_assert_same( count( $plugin_abilities ), array_sum( (array) ( $runtime_contract['ability_risk_counts'] ?? array() ) ), 'runtime contract risk counts add up to the ability count' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit', $runtime_contract['catalog']['ability_definitions_owner'] ?? '', 'runtime contract names Toolkit as ability definitions owner' );
npcink_abilities_toolkit_assert_same( 'wordpress_abilities_api', $runtime_contract['catalog']['ability_catalog_source'] ?? '', 'runtime contract points hosts to WordPress Abilities API catalog' );
npcink_abilities_toolkit_assert_true( isset( $runtime_contract['abilities']['npcink-abilities-toolkit/site-info'] ), 'runtime contract exposes the machine-readable Ability projection' );
$site_info_contract = $runtime_contract['abilities']['npcink-abilities-toolkit/site-info'] ?? array();
npcink_abilities_toolkit_assert_true( 0 === strpos( (string) ( $site_info_contract['schema_hash'] ?? '' ), 'sha256:' ), 'Ability projection exposes a stable schema hash' );
npcink_abilities_toolkit_assert_same( 'registered', $site_info_contract['verification_state'] ?? '', 'Ability projection exposes registered verification state' );
npcink_abilities_toolkit_assert_same( 'read_only', $site_info_contract['write_posture'] ?? '', 'read Ability projection exposes read-only write posture' );
npcink_abilities_toolkit_assert_same( true, $runtime_contract['schema_controls']['callback_free_hashes'] ?? null, 'runtime contract exposes callback-free schema hashes' );
npcink_abilities_toolkit_assert_same( true, $runtime_contract['write_controls']['dry_run_default'] ?? null, 'runtime contract keeps dry-run as the default write posture' );
npcink_abilities_toolkit_assert_same( false, $runtime_contract['write_controls']['commit_default'] ?? null, 'runtime contract keeps commit disabled by default' );
npcink_abilities_toolkit_assert_same( true, $runtime_contract['write_controls']['host_governed_writes'] ?? null, 'runtime contract keeps write authority host-governed' );
npcink_abilities_toolkit_assert_same( 'wordpress_abilities_api', $runtime_contract['execution_controls']['read_execution_surface'] ?? '', 'runtime contract keeps read execution on WordPress Abilities API' );
npcink_abilities_toolkit_assert_same( 'host_runtime_after_governance', $runtime_contract['execution_controls']['write_execution_surface'] ?? '', 'runtime contract leaves write execution to host runtime after governance' );
npcink_abilities_toolkit_assert_same( false, $runtime_contract['execution_controls']['approval_storage'] ?? true, 'runtime contract excludes approval storage from Toolkit' );
npcink_abilities_toolkit_assert_same( false, $runtime_contract['execution_controls']['audit_truth'] ?? true, 'runtime contract excludes audit truth from Toolkit' );
npcink_abilities_toolkit_assert_same( 'host_governance_layer', $runtime_contract['boundary']['approval_truth_owner'] ?? '', 'runtime contract leaves approval truth with the host governance layer' );
npcink_abilities_toolkit_assert_same( 'host_governance_layer', $runtime_contract['boundary']['audit_truth_owner'] ?? '', 'runtime contract leaves audit truth with the host governance layer' );
npcink_abilities_toolkit_assert_same( false, $runtime_contract['forbidden_payloads']['approval_records'] ?? true, 'runtime contract forbids approval records' );
npcink_abilities_toolkit_assert_same( false, $runtime_contract['forbidden_payloads']['audit_records'] ?? true, 'runtime contract forbids audit records' );
npcink_abilities_toolkit_assert_same( false, $runtime_contract['forbidden_payloads']['runtime_state'] ?? true, 'runtime contract forbids runtime state' );
foreach ( array( 'ability_ids_hash', 'ability_contracts_hash', 'workflow_recipes_hash' ) as $hash_key ) {
	npcink_abilities_toolkit_assert_true( 0 === strpos( (string) ( $runtime_contract[ $hash_key ] ?? '' ), 'sha256:' ), "runtime contract exposes {$hash_key} as a sha256 digest" );
}
npcink_abilities_toolkit_assert_array_omits_keys(
	$runtime_contract,
	array(
		'callback',
		'execute_callback',
		'permission_callback',
		'secret',
		'token',
	),
	'runtime contract'
);
$runtime_contract_json = wp_json_encode( $runtime_contract );
foreach ( array( 'execute_callback', 'permission_callback', 'Closure', '/Users/muze' ) as $forbidden_fragment ) {
	npcink_abilities_toolkit_assert_true( false === strpos( (string) $runtime_contract_json, $forbidden_fragment ), 'runtime contract JSON omits internal fragment ' . $forbidden_fragment );
}
remove_all_filters( 'npcink_abilities_toolkit_enabled_packages' );

$GLOBALS['npcink_abilities_toolkit_unit_options']['npcink_abilities_toolkit_read_cache_version'] = 7;
$plugin->bump_read_cache_version_for_post_meta( 9, 42, 'woocommerce_order_total', '99' );
npcink_abilities_toolkit_assert_same( 7, $GLOBALS['npcink_abilities_toolkit_unit_options']['npcink_abilities_toolkit_read_cache_version'], 'unrelated high-frequency meta traffic stays outside the read-cache invalidation scope' );
$plugin->bump_read_cache_version_for_post_meta( 1, 42, '_yoast_wpseo_title', 'seo' );
npcink_abilities_toolkit_assert_same( 8, $GLOBALS['npcink_abilities_toolkit_unit_options']['npcink_abilities_toolkit_read_cache_version'], 'post-meta writes outside save_post bump the read-cache version' );
$plugin->bump_read_cache_version_for_post_meta( 1, 42, '_edit_lock', 'lock' );
npcink_abilities_toolkit_assert_same( 8, $GLOBALS['npcink_abilities_toolkit_unit_options']['npcink_abilities_toolkit_read_cache_version'], 'edit-lock meta churn does not invalidate the read cache' );
$plugin->bump_read_cache_version_for_post_meta( 2, 42, '_yoast_wpseo_metadesc', 'desc' );
npcink_abilities_toolkit_assert_same( 8, $GLOBALS['npcink_abilities_toolkit_unit_options']['npcink_abilities_toolkit_read_cache_version'], 'bulk meta writes bump the read-cache version once per request' );
$plugin->bump_read_cache_version_for_post_meta( 3, 42, '_npcink_toolbox_article_audio_url', 'https://example.com/a.mp3' );
npcink_abilities_toolkit_assert_same( 8, $GLOBALS['npcink_abilities_toolkit_unit_options']['npcink_abilities_toolkit_read_cache_version'], 'toolkit-owned media meta stays watched and stays debounced within one request' );
unset( $GLOBALS['npcink_abilities_toolkit_unit_options']['npcink_abilities_toolkit_read_cache_version'] );

$health_notices = new Npcink_Abilities_Toolkit\Admin\Health_Notices( $registrar );
$health_notices->boot();
$health_notice_actions = isset( $GLOBALS['npcink_abilities_toolkit_unit_actions']['admin_notices'] ) && is_array( $GLOBALS['npcink_abilities_toolkit_unit_actions']['admin_notices'] )
	? $GLOBALS['npcink_abilities_toolkit_unit_actions']['admin_notices']
	: array();
$has_health_notice_render = false;
foreach ( $health_notice_actions as $health_action ) {
	if ( is_array( $health_action ) && isset( $health_action[0] ) && $health_action[0] instanceof Npcink_Abilities_Toolkit\Admin\Health_Notices && 'render_notices' === $health_action[1] ) {
		$has_health_notice_render = true;
		break;
	}
}
npcink_abilities_toolkit_assert_true( $has_health_notice_render, 'health notices register the admin_notices surface' );
$populated_notices = $health_notices->get_active_notices();
npcink_abilities_toolkit_assert_true( ! isset( $populated_notices['catalog_empty'] ), 'health notices stay silent about an empty catalog when the catalog is populated' );
npcink_abilities_toolkit_assert_true( isset( $populated_notices['abilities_api_missing'] ), 'health notices fail loud when an Abilities API registration function is unavailable' );
$empty_categories   = new Category_Registrar();
$empty_registrar    = new Ability_Registrar( $empty_categories, $contract_normalizer );
$empty_notices      = ( new Npcink_Abilities_Toolkit\Admin\Health_Notices( $empty_registrar ) )->get_active_notices();
npcink_abilities_toolkit_assert_true( isset( $empty_notices['catalog_empty'] ), 'health notices fail loud when the ability catalog is empty' );

if ( ! function_exists( 'wp_verify_nonce' ) ) {
	/**
	 * Accepts only the well-known test nonce value for one action.
	 *
	 * @param string $nonce Nonce value.
	 * @param string $action Nonce action.
	 * @return bool
	 */
	function wp_verify_nonce( $nonce, $action = '' ) {
		return '_valid_' . $action === (string) $nonce;
	}
}
if ( ! function_exists( 'get_user_meta' ) ) {
	/**
	 * Reads unit user meta.
	 *
	 * @param int    $user_id User id.
	 * @param string $key Meta key.
	 * @return mixed
	 */
	function get_user_meta( $user_id, $key = '' ) {
		return isset( $GLOBALS['npcink_abilities_toolkit_unit_user_meta'][ (int) $user_id ][ (string) $key ] )
			? $GLOBALS['npcink_abilities_toolkit_unit_user_meta'][ (int) $user_id ][ (string) $key ]
			: '';
	}
}
if ( ! function_exists( 'update_user_meta' ) ) {
	/**
	 * Writes unit user meta.
	 *
	 * @param int    $user_id User id.
	 * @param string $key Meta key.
	 * @param mixed  $value Meta value.
	 * @return bool
	 */
	function update_user_meta( $user_id, $key, $value ) {
		$GLOBALS['npcink_abilities_toolkit_unit_user_meta'][ (int) $user_id ][ (string) $key ] = $value;
		return true;
	}
}
if ( ! function_exists( 'get_current_screen' ) ) {
	/**
	 * Returns the plugins screen for notice rendering assertions.
	 *
	 * @return object
	 */
	function get_current_screen() {
		return (object) array( 'id' => 'plugins' );
	}
}
if ( ! function_exists( 'esc_attr' ) ) {
	/**
	 * Passes through attribute values.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	function esc_attr( $value ) {
		return (string) $value;
	}
}
if ( ! function_exists( 'esc_attr__' ) ) {
	/**
	 * Returns the raw attribute string.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function esc_attr__( $text ) {
		return (string) $text;
	}
}
if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * Returns the raw html string.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function esc_html__( $text ) {
		return (string) $text;
	}
}
if ( ! function_exists( 'esc_url' ) ) {
	/**
	 * Passes through url values.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	function esc_url( $value ) {
		return (string) $value;
	}
}
if ( ! function_exists( 'wp_nonce_url' ) ) {
	/**
	 * Returns the unit nonce URL.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	function wp_nonce_url( $url ) {
		return $url . '&_wpnonce=unit';
	}
}
if ( ! function_exists( 'admin_url' ) ) {
	/**
	 * Returns a unit admin URL.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	function admin_url( $path = '' ) {
		return 'https://unit.test/wp-admin/' . ltrim( (string) $path, '/' );
	}
}
$empty_health_notices = new Npcink_Abilities_Toolkit\Admin\Health_Notices( $empty_registrar );
$GLOBALS['npcink_abilities_toolkit_unit_current_user_caps'] = array( 'manage_options' => false );
$GLOBALS['npcink_abilities_toolkit_unit_user_meta'] = array();
$_REQUEST['notice'] = 'catalog_empty';
$_REQUEST['_wpnonce'] = '_valid_npcink_abilities_toolkit_dismiss_health_notice';
$empty_health_notices->handle_dismiss_request();
npcink_abilities_toolkit_assert_same( array(), $GLOBALS['npcink_abilities_toolkit_unit_user_meta'], 'health notice dismissal is rejected without manage_options' );
$GLOBALS['npcink_abilities_toolkit_unit_current_user_caps'] = array( 'manage_options' => true );
$_REQUEST['_wpnonce'] = 'expired-nonce';
$empty_health_notices->handle_dismiss_request();
npcink_abilities_toolkit_assert_same( array(), $GLOBALS['npcink_abilities_toolkit_unit_user_meta'], 'health notice dismissal with an invalid nonce never reaches the user-meta write' );
$_REQUEST['_wpnonce'] = '_valid_npcink_abilities_toolkit_dismiss_health_notice';
$empty_health_notices->handle_dismiss_request();
npcink_abilities_toolkit_assert_same( array( '7' => array( 'npcink_abilities_toolkit_health_notices_dismissed' => array( 'catalog_empty' => '0.1.0-test' ) ) ), $GLOBALS['npcink_abilities_toolkit_unit_user_meta'], 'health notice dismissal persists per-user with the dismissing plugin version' );
unset( $_REQUEST['_wpnonce'], $_REQUEST['notice'] );
ob_start();
$GLOBALS['npcink_abilities_toolkit_unit_current_user_caps'] = array( 'manage_options' => true );
$empty_health_notices->render_notices();
$rendered_notices_html = (string) ob_get_clean();
npcink_abilities_toolkit_assert_true( false === strpos( $rendered_notices_html, 'no abilities are registered on this site yet' ), 'dismissed health notices stay hidden on supported screens' );
$stub_registrar_notices = new Npcink_Abilities_Toolkit\Admin\Health_Notices( $registrar );
$GLOBALS['npcink_abilities_toolkit_unit_user_meta'] = array();
ob_start();
$stub_registrar_notices->render_notices();
$rendered_stub_html = (string) ob_get_clean();
npcink_abilities_toolkit_assert_true( false !== strpos( $rendered_stub_html, 'Abilities API registration functions are unavailable' ), 'undismissed health notices render on supported screens' );
unset( $GLOBALS['npcink_abilities_toolkit_unit_current_user_caps'], $GLOBALS['npcink_abilities_toolkit_unit_user_meta'] );

$enabled_packages = $plugin->get_enabled_packages();
npcink_abilities_toolkit_assert_same( 7, count( $enabled_packages ), 'package enable map resolves the full built-in default map without host filters' );
npcink_abilities_toolkit_assert_same( true, $enabled_packages['core_read'] ?? null, 'package enable map keeps the core read package enabled by default' );
npcink_abilities_toolkit_assert_same( true, $enabled_packages['core_destructive'] ?? null, 'package enable map keeps destructive abilities on by default; hosts opt out through the filter' );

$catalog_honesty_controller = new Contract_Controller();
npcink_abilities_toolkit_assert_same( false, $catalog_honesty_controller->contract()['compatibility']['ability_catalog_available'] ?? null, 'runtime contract reports ability_catalog_available honestly when the Abilities API catalog route is absent' );
if ( ! function_exists( 'rest_get_server' ) ) {
	/**
	 * Supplies a live catalog route for contract honesty assertions.
	 *
	 * @return object
	 */
	function rest_get_server() {
		return new class() {
			/**
			 * Returns the fake route table.
			 *
			 * @return array<string,mixed>
			 */
			public function get_routes() {
				return array( '/wp-abilities/v1/abilities' => array() );
			}
		};
	}
}
npcink_abilities_toolkit_assert_same( true, $catalog_honesty_controller->contract()['compatibility']['ability_catalog_available'] ?? null, 'runtime contract reports ability_catalog_available when the Abilities API catalog route is live' );

if ( ! class_exists( 'WP_REST_Response' ) ) {
	/**
	 * Minimal response stand-in for cache-header assertions.
	 */
	class WP_REST_Response {
		/**
		 * Response data.
		 *
		 * @var mixed
		 */
		public $data;

		/**
		 * Response headers.
		 *
		 * @var array<string,string>
		 */
		private $headers = array();

		/**
		 * Constructor.
		 *
		 * @param mixed $data Response data.
		 */
		public function __construct( $data = null ) {
			$this->data = $data;
		}

		/**
		 * Sets response headers.
		 *
		 * @param array<string,string> $headers Headers.
		 * @return void
		 */
		public function set_headers( $headers ) {
			$this->headers = $headers;
		}

		/**
		 * Returns response headers.
		 *
		 * @return array<string,string>
		 */
		public function get_headers() {
			return $this->headers;
		}
	}
}
$etag_response = $catalog_honesty_controller->serve_contract();
npcink_abilities_toolkit_assert_true( $etag_response instanceof WP_REST_Response, 'contract route serves a cache-validated response object' );
$etag_headers  = $etag_response instanceof WP_REST_Response ? $etag_response->get_headers() : array();
npcink_abilities_toolkit_assert_true( 0 === strpos( (string) ( $etag_headers['ETag'] ?? '' ), '"sha256:' ), 'contract ETag is a quoted sha256 digest' );
npcink_abilities_toolkit_assert_same( 'private, no-cache', $etag_headers['Cache-Control'] ?? '', 'contract response forces ETag revalidation on every poll by default' );
$second_etag_response = $catalog_honesty_controller->serve_contract();
npcink_abilities_toolkit_assert_same( $etag_headers['ETag'], $second_etag_response instanceof WP_REST_Response ? $second_etag_response->get_headers()['ETag'] : '', 'contract ETag stays stable for identical payloads' );

$not_modified_request = new class() {
	/**
	 * Returns the contract route.
	 *
	 * @return string
	 */
	public function get_route() {
		return '/npcink-abilities-toolkit/v1/contract';
	}
};

if ( ! function_exists( 'status_header' ) ) {
	/**
	 * Records the last emitted status header.
	 *
	 * @param int $status HTTP status.
	 * @return bool
	 */
	function status_header( $status ) {
		$GLOBALS['npcink_abilities_toolkit_unit_status_header'] = (int) $status;
		return true;
	}
}
$_SERVER['HTTP_IF_NONE_MATCH'] = (string) ( $etag_headers['ETag'] ?? '' );
npcink_abilities_toolkit_assert_same( true, $catalog_honesty_controller->maybe_send_not_modified( false, $etag_response, $not_modified_request, null ), 'contract endpoint answers a matching If-None-Match as served-not-modified' );
npcink_abilities_toolkit_assert_same( 304, $GLOBALS['npcink_abilities_toolkit_unit_status_header'] ?? 0, 'contract endpoint emits a 304 status when If-None-Match matches' );
unset( $GLOBALS['npcink_abilities_toolkit_unit_status_header'] );
$_SERVER['HTTP_IF_NONE_MATCH'] = '"sha256:different"';
npcink_abilities_toolkit_assert_same( false, $catalog_honesty_controller->maybe_send_not_modified( false, $etag_response, $not_modified_request, null ), 'contract endpoint keeps serving the body when If-None-Match differs' );
npcink_abilities_toolkit_assert_same( null, $GLOBALS['npcink_abilities_toolkit_unit_status_header'] ?? null, 'mismatched If-None-Match never emits a 304 status' );
unset( $_SERVER['HTTP_IF_NONE_MATCH'] );

npcink_abilities_toolkit_assert_true(
	$registrar->add_write_host_governed(
		'acme/host-write',
		array(
			'label'            => 'Host Write',
			'description'      => 'Host-governed write.',
			'input_schema'     => array( 'type' => 'object' ),
			'output_schema'    => array( 'type' => 'object' ),
			'execute_callback' => static function () {
				return array( 'dry_run' => true );
			},
		)
	),
	'registrar accepts host-governed write ability'
);
$host_write = $registrar->all()['acme/host-write'];
npcink_abilities_toolkit_assert_same( 'write_host', $host_write['mode'], 'host-governed write mode is preserved' );
npcink_abilities_toolkit_assert_same( 'write', $host_write['risk_level'], 'host-governed write risk is write' );
npcink_abilities_toolkit_assert_same( true, $host_write['requires_confirm'], 'host-governed write requires confirmation' );

$GLOBALS['npcink_abilities_toolkit_unit_options'] = array();
$GLOBALS['npcink_abilities_toolkit_unit_transients'] = array();
$GLOBALS['npcink_abilities_toolkit_unit_registered_abilities'] = array();
$GLOBALS['npcink_abilities_toolkit_unit_observability_events'] = array();
add_action(
	'npcink_abilities_toolkit_observability_event',
	static function ( $event ) {
		$GLOBALS['npcink_abilities_toolkit_unit_observability_events'][] = $event;
	}
);

$observability_categories = new Category_Registrar();
$observability_registrar = new Ability_Registrar( $observability_categories, $contract_normalizer );
npcink_abilities_toolkit_assert_true(
	$observability_registrar->add_readonly(
		'acme/observable-summary',
		array(
			'label'            => 'Observable Summary',
			'description'      => 'Returns observable summary.',
			'category'         => 'acme-observability',
			'input_schema'     => array( 'type' => 'object' ),
			'output_schema'    => array( 'type' => 'object' ),
			'meta'             => array(
				'secret'  => 'must-not-leak',
				'payload' => array( 'raw' => true ),
			),
			'execute_callback' => static function () {
				return array( 'ok' => true );
			},
		)
	),
	'observability registrar accepts test ability'
);
npcink_abilities_toolkit_assert_true(
	$observability_registrar->add_readonly(
		'acme/observable-wp-error',
		array(
			'label'            => 'Observable WP Error',
			'description'      => 'Returns observable WP error.',
			'input_schema'     => array( 'type' => 'object' ),
			'output_schema'    => array( 'type' => 'object' ),
			'execute_callback' => static function () {
				return new WP_Error( 'acme_callback_failed', 'Raw error message should not be emitted.', array( 'payload_json' => 'must-not-leak' ) );
			},
		)
	),
	'observability registrar accepts WP_Error callback ability'
);
npcink_abilities_toolkit_assert_true(
	$observability_registrar->add_readonly(
		'acme/observable-exception',
		array(
			'label'            => 'Observable Exception',
			'description'      => 'Throws observable exception.',
			'input_schema'     => array( 'type' => 'object' ),
			'output_schema'    => array( 'type' => 'object' ),
			'execute_callback' => static function () {
				throw new RuntimeException( 'Raw exception message should not be emitted.' );
			},
		)
	),
	'observability registrar accepts throwing callback ability'
);
$first_hash = $observability_registrar->catalog_fingerprint();
$observability_registrar->register_with_wordpress();
$catalog_events = npcink_abilities_toolkit_observability_events_of_kind( $GLOBALS['npcink_abilities_toolkit_unit_observability_events'], 'abilities.catalog.changed' );
npcink_abilities_toolkit_assert_same( 1, count( $catalog_events ), 'first bootstrap emits one catalog changed event' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit', $catalog_events[0]['plugin_slug'] ?? '', 'catalog event carries plugin slug' );
npcink_abilities_toolkit_assert_same( 'ok', $catalog_events[0]['status'] ?? '', 'catalog event status is ok' );
npcink_abilities_toolkit_assert_same( 'local', $catalog_events[0]['source'] ?? '', 'catalog event source remains local' );
npcink_abilities_toolkit_assert_same( 3, $catalog_events[0]['ability_count'] ?? 0, 'catalog event carries ability count' );
npcink_abilities_toolkit_assert_same( $first_hash, $catalog_events[0]['catalog_hash'] ?? '', 'catalog event carries current catalog hash' );
npcink_abilities_toolkit_assert_event_has_safe_event_id( $catalog_events[0], 'catalog_', 'catalog event' );
npcink_abilities_toolkit_assert_observability_event_is_metadata_only( $catalog_events[0], 'catalog event payload' );

$GLOBALS['npcink_abilities_toolkit_unit_registered_abilities'] = array();
$observability_registrar->register_with_wordpress();
$catalog_events = npcink_abilities_toolkit_observability_events_of_kind( $GLOBALS['npcink_abilities_toolkit_unit_observability_events'], 'abilities.catalog.changed' );
npcink_abilities_toolkit_assert_same( 1, count( $catalog_events ), 'repeated bootstrap does not repeat catalog changed event for the same hash' );
npcink_abilities_toolkit_assert_same( 0, count( npcink_abilities_toolkit_observability_events_of_kind( $GLOBALS['npcink_abilities_toolkit_unit_observability_events'], 'abilities.ability.registered' ) ), 'ability add no longer emits per-ability registered events' );
npcink_abilities_toolkit_assert_same( 0, count( npcink_abilities_toolkit_observability_events_of_kind( $GLOBALS['npcink_abilities_toolkit_unit_observability_events'], 'abilities.ability.wordpress_registered' ) ), 'WordPress registration no longer emits per-ability events' );

npcink_abilities_toolkit_assert_true(
	$observability_registrar->add_readonly(
		'acme/observable-detail',
		array(
			'label'            => 'Observable Detail',
			'description'      => 'Returns observable detail.',
			'input_schema'     => array( 'type' => 'object' ),
			'output_schema'    => array( 'type' => 'object' ),
			'execute_callback' => static function () {
				return array( 'detail' => true );
			},
		)
	),
	'observability registrar accepts changed catalog ability'
);
$changed_hash = $observability_registrar->catalog_fingerprint();
npcink_abilities_toolkit_assert_true( $first_hash !== $changed_hash, 'catalog hash changes when ability catalog changes' );
$GLOBALS['npcink_abilities_toolkit_unit_registered_abilities'] = array();
$observability_registrar->register_with_wordpress();
$catalog_events = npcink_abilities_toolkit_observability_events_of_kind( $GLOBALS['npcink_abilities_toolkit_unit_observability_events'], 'abilities.catalog.changed' );
npcink_abilities_toolkit_assert_same( 2, count( $catalog_events ), 'changed catalog hash emits one additional catalog changed event' );
npcink_abilities_toolkit_assert_same( $changed_hash, $catalog_events[1]['catalog_hash'] ?? '', 'changed catalog event carries new hash' );
npcink_abilities_toolkit_assert_same( $first_hash, $catalog_events[1]['previous_catalog_hash'] ?? '', 'changed catalog event carries previous hash' );
$GLOBALS['npcink_abilities_toolkit_unit_registered_abilities'] = array();
$observability_registrar->register_with_wordpress();
$catalog_events = npcink_abilities_toolkit_observability_events_of_kind( $GLOBALS['npcink_abilities_toolkit_unit_observability_events'], 'abilities.catalog.changed' );
npcink_abilities_toolkit_assert_same( 2, count( $catalog_events ), 'same changed catalog hash is rate limited after first emit' );

$GLOBALS['npcink_abilities_toolkit_unit_options'][ Ability_Registrar::CATALOG_STATE_OPTION ] = array(
	'catalog_hash'   => $changed_hash,
	'emitted_at'     => '2026-06-01T00:00:00+00:00',
	'plugin_version' => '0.0.0-old',
	'reason'         => 'bootstrap',
);
$old_version_rate_limit_key = Ability_Registrar::CATALOG_RATE_LIMIT_PREFIX . substr( hash( 'sha256', $changed_hash . '|0.0.0-old' ), 0, 40 );
$GLOBALS['npcink_abilities_toolkit_unit_transients'][ $old_version_rate_limit_key ] = '2026-06-01T00:00:00+00:00';
$GLOBALS['npcink_abilities_toolkit_unit_registered_abilities'] = array();
$observability_registrar->register_with_wordpress();
$catalog_events = npcink_abilities_toolkit_observability_events_of_kind( $GLOBALS['npcink_abilities_toolkit_unit_observability_events'], 'abilities.catalog.changed' );
npcink_abilities_toolkit_assert_same( 3, count( $catalog_events ), 'version change emits catalog changed even when old hash transient exists' );
npcink_abilities_toolkit_assert_same( $changed_hash, $catalog_events[2]['catalog_hash'] ?? '', 'version-change catalog event keeps unchanged hash' );
npcink_abilities_toolkit_assert_true( ! isset( $catalog_events[2]['previous_catalog_hash'] ), 'version-change same-hash catalog event omits previous hash' );
npcink_abilities_toolkit_assert_same( NPCINK_ABILITIES_TOOLKIT_VERSION, $GLOBALS['npcink_abilities_toolkit_unit_options'][ Ability_Registrar::CATALOG_STATE_OPTION ]['plugin_version'] ?? '', 'version-change emit updates catalog state version' );

$callback = $GLOBALS['npcink_abilities_toolkit_unit_registered_abilities']['acme/observable-summary']['execute_callback'] ?? null;
npcink_abilities_toolkit_assert_true( is_callable( $callback ), 'registered ability keeps callable observed execute callback' );
$callback_result = call_user_func( $callback, array( 'raw_callback_input' => 'super-secret-callback-input' ) );
npcink_abilities_toolkit_assert_same( array( 'ok' => true ), $callback_result, 'observed callback returns original result' );
$callback_events = npcink_abilities_toolkit_observability_events_of_kind( $GLOBALS['npcink_abilities_toolkit_unit_observability_events'], 'abilities.callback.completed' );
npcink_abilities_toolkit_assert_same( 1, count( $callback_events ), 'callback execution still emits behavior observability event' );
npcink_abilities_toolkit_assert_same( 'acme/observable-summary', $callback_events[0]['ability_id'] ?? '', 'callback event carries ability id' );
npcink_abilities_toolkit_assert_same( 'ok', $callback_events[0]['status'] ?? '', 'callback event carries successful status' );
npcink_abilities_toolkit_assert_event_has_safe_event_id( $callback_events[0], 'ability_cb_', 'callback completed event' );
npcink_abilities_toolkit_assert_observability_event_is_metadata_only( $callback_events[0], 'callback completed event payload' );
npcink_abilities_toolkit_assert_true( false === strpos( wp_json_encode( $callback_events[0] ), 'super-secret-callback-input' ), 'callback completed event omits raw callback input values' );

$wp_error_callback = $GLOBALS['npcink_abilities_toolkit_unit_registered_abilities']['acme/observable-wp-error']['execute_callback'] ?? null;
npcink_abilities_toolkit_assert_true( is_callable( $wp_error_callback ), 'registered WP_Error ability keeps callable observed execute callback' );
$wp_error_result = call_user_func( $wp_error_callback, array( 'payload_json' => 'super-secret-callback-input' ) );
npcink_abilities_toolkit_assert_true( is_wp_error( $wp_error_result ), 'observed WP_Error callback returns original error result' );
$failed_callback_events = npcink_abilities_toolkit_observability_events_of_kind( $GLOBALS['npcink_abilities_toolkit_unit_observability_events'], 'abilities.callback.failed' );
npcink_abilities_toolkit_assert_same( 1, count( $failed_callback_events ), 'WP_Error callback emits one failed callback event' );
npcink_abilities_toolkit_assert_same( 'acme/observable-wp-error', $failed_callback_events[0]['ability_id'] ?? '', 'WP_Error failed event carries ability id' );
npcink_abilities_toolkit_assert_same( 'error', $failed_callback_events[0]['status'] ?? '', 'WP_Error failed event carries error status' );
npcink_abilities_toolkit_assert_same( 'abilities.callback_error', $failed_callback_events[0]['error_code'] ?? '', 'WP_Error failed event uses stable error code' );
npcink_abilities_toolkit_assert_same( 'acme_callback_failed', $failed_callback_events[0]['status_detail'] ?? '', 'WP_Error failed event carries redacted status detail' );
npcink_abilities_toolkit_assert_event_has_safe_event_id( $failed_callback_events[0], 'ability_cb_', 'WP_Error failed event' );
npcink_abilities_toolkit_assert_observability_event_is_metadata_only( $failed_callback_events[0], 'WP_Error failed event payload' );
npcink_abilities_toolkit_assert_true( false === strpos( wp_json_encode( $failed_callback_events[0] ), 'super-secret-callback-input' ), 'WP_Error failed event omits raw callback input values' );
npcink_abilities_toolkit_assert_true( false === strpos( wp_json_encode( $failed_callback_events[0] ), 'Raw error message should not be emitted.' ), 'WP_Error failed event omits raw error message' );

$exception_callback = $GLOBALS['npcink_abilities_toolkit_unit_registered_abilities']['acme/observable-exception']['execute_callback'] ?? null;
npcink_abilities_toolkit_assert_true( is_callable( $exception_callback ), 'registered exception ability keeps callable observed execute callback' );
try {
	call_user_func( $exception_callback, array( 'payload_json' => 'super-secret-callback-input' ) );
	npcink_abilities_toolkit_assert_true( false, 'observed exception callback rethrows original exception' );
} catch ( RuntimeException $exception ) {
	npcink_abilities_toolkit_assert_same( 'Raw exception message should not be emitted.', $exception->getMessage(), 'observed exception callback rethrows original exception message locally' );
}
$failed_callback_events = npcink_abilities_toolkit_observability_events_of_kind( $GLOBALS['npcink_abilities_toolkit_unit_observability_events'], 'abilities.callback.failed' );
npcink_abilities_toolkit_assert_same( 2, count( $failed_callback_events ), 'throwing callback emits one additional failed callback event' );
npcink_abilities_toolkit_assert_same( 'acme/observable-exception', $failed_callback_events[1]['ability_id'] ?? '', 'exception failed event carries ability id' );
npcink_abilities_toolkit_assert_same( 'abilities.callback_error', $failed_callback_events[1]['error_code'] ?? '', 'exception failed event uses stable error code' );
npcink_abilities_toolkit_assert_same( 'runtimeexception', $failed_callback_events[1]['status_detail'] ?? '', 'exception failed event carries redacted exception class' );
npcink_abilities_toolkit_assert_event_has_safe_event_id( $failed_callback_events[1], 'ability_cb_', 'exception failed event' );
npcink_abilities_toolkit_assert_observability_event_is_metadata_only( $failed_callback_events[1], 'exception failed event payload' );
npcink_abilities_toolkit_assert_true( false === strpos( wp_json_encode( $failed_callback_events[1] ), 'super-secret-callback-input' ), 'exception failed event omits raw callback input values' );
npcink_abilities_toolkit_assert_true( false === strpos( wp_json_encode( $failed_callback_events[1] ), 'Raw exception message should not be emitted.' ), 'exception failed event omits raw exception message' );
$callback_events = npcink_abilities_toolkit_observability_events_of_kind( $GLOBALS['npcink_abilities_toolkit_unit_observability_events'], 'abilities.callback.completed' );
npcink_abilities_toolkit_assert_same( 1, count( $callback_events ), 'failed callbacks do not add completed callback events' );

$bridge = new Npcink_Catalog_Bridge( $registrar );
$catalog = $bridge->filter_catalog( array(), array() );
npcink_abilities_toolkit_assert_true( ! isset( $catalog['acme_site-summary'] ), 'catalog bridge does not project provider abilities by default' );

npcink_abilities_toolkit_assert_true(
	$registrar->add_readonly(
		'acme/projected-summary',
		array(
			'label'                     => 'Projected Summary',
			'description'               => 'Provider ability explicitly projected for Npcink AI compatibility.',
			'project_to_npcink_catalog' => true,
			'input_schema'              => array( 'type' => 'object' ),
			'output_schema'             => array( 'type' => 'object' ),
			'execute_callback'          => static function () {
				return array();
			},
		)
	),
	'registrar accepts provider ability with explicit Npcink AI projection'
);
$catalog = $bridge->filter_catalog( array(), array() );
npcink_abilities_toolkit_assert_true( isset( $catalog['acme_projected-summary'] ), 'catalog bridge projects opted-in provider ability' );
npcink_abilities_toolkit_assert_same( 'wp_ability', $catalog['acme_projected-summary']['executor_type'], 'catalog bridge uses wp_ability executor' );
npcink_abilities_toolkit_assert_same( 'acme/projected-summary', $catalog['acme_projected-summary']['wp_ability_id'], 'catalog bridge keeps wp ability id' );
npcink_abilities_toolkit_assert_same( true, $catalog['acme_projected-summary']['show_in_rest'], 'catalog bridge sets top-level show_in_rest for host catalog normalization' );
npcink_abilities_toolkit_assert_true( ! isset( $catalog['acme_projected-summary']['open_api_enabled'] ), 'catalog bridge does not own Open API routing policy' );
npcink_abilities_toolkit_assert_true( ! isset( $catalog['acme_projected-summary']['skip_catalog_manifest_fallback'] ), 'catalog bridge does not own host manifest fallback policy' );
npcink_abilities_toolkit_assert_true( ! isset( $catalog['acme_projected-summary']['backend_priority'] ), 'catalog bridge does not own backend priority policy' );

npcink_abilities_toolkit_assert_true(
	$registrar->add_write_host_governed(
		'acme/projected-host-write',
		array(
			'label'                     => 'Projected Host Write',
			'description'               => 'Provider write ability explicitly projected for Npcink AI compatibility.',
			'project_to_npcink_catalog' => true,
			'input_schema'              => array( 'type' => 'object' ),
			'output_schema'             => array( 'type' => 'object' ),
			'execute_callback'          => static function () {
				return array( 'dry_run' => true );
			},
		)
	),
	'registrar accepts projected host-governed write ability'
);
$catalog = $bridge->filter_catalog( array(), array() );
npcink_abilities_toolkit_assert_same( 'wp_ability', $catalog['acme_projected-host-write']['executor_type'] ?? '', 'catalog bridge projects host-governed write as wp_ability' );
npcink_abilities_toolkit_assert_same( true, $catalog['acme_projected-host-write']['requires_confirm'] ?? null, 'catalog bridge carries confirmation requirement for projected host-governed write' );
npcink_abilities_toolkit_assert_true( ! isset( $catalog['acme_projected-host-write']['tool_policy'] ), 'catalog bridge does not own projected host-governed write tool policy' );
npcink_abilities_toolkit_assert_true( ! isset( $catalog['acme_projected-host-write']['skip_catalog_manifest_fallback'] ), 'catalog bridge does not own projected host-governed write fallback policy' );

npcink_abilities_toolkit_assert_true(
	$registrar->add_destructive_host_governed(
		'acme/projected-delete-post',
		array(
			'label'                     => 'Projected Delete Post',
			'description'               => 'Provider destructive ability explicitly projected for Npcink AI compatibility.',
			'project_to_npcink_catalog' => true,
			'input_schema'              => array( 'type' => 'object' ),
			'output_schema'             => array( 'type' => 'object' ),
			'execute_callback'          => static function () {
				return array( 'dry_run' => true );
			},
		)
	),
	'registrar accepts projected destructive host-governed ability'
);
$catalog = $bridge->filter_catalog( array(), array() );
npcink_abilities_toolkit_assert_same( 'wp_ability', $catalog['acme_projected-delete-post']['executor_type'] ?? '', 'catalog bridge projects destructive ability as wp_ability' );
npcink_abilities_toolkit_assert_same( true, $catalog['acme_projected-delete-post']['requires_confirm'] ?? null, 'catalog bridge carries confirmation requirement for projected destructive ability' );
npcink_abilities_toolkit_assert_same( 'destructive', $catalog['acme_projected-delete-post']['risk_level'] ?? '', 'catalog bridge carries projected destructive risk' );
npcink_abilities_toolkit_assert_true( ! isset( $catalog['acme_projected-delete-post']['tool_policy'] ), 'catalog bridge does not own projected destructive tool policy' );
npcink_abilities_toolkit_assert_true( ! isset( $catalog['acme_projected-delete-post']['skip_catalog_manifest_fallback'] ), 'catalog bridge does not own projected destructive fallback policy' );

npcink_abilities_toolkit_assert_true(
	$registrar->add_readonly(
		'npcink-abilities-toolkit/official-summary',
		array(
			'label'                     => 'Official Summary',
			'description'               => 'Official ability mirrored from a host plugin.',
			'source'                    => 'official',
			'project_to_npcink_catalog' => false,
			'input_schema'              => array( 'type' => 'object' ),
			'output_schema'             => array( 'type' => 'object' ),
			'execute_callback'          => static function () {
				return array();
			},
		)
	),
	'registrar accepts official mirrored readonly ability'
);
$catalog = $bridge->filter_catalog( array(), array() );
npcink_abilities_toolkit_assert_true( ! isset( $catalog['npcink-abilities-toolkit_official-summary'] ), 'catalog bridge does not project official mirrored abilities' );

