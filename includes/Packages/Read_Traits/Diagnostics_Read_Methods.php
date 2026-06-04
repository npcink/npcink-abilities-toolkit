<?php
/**
 * Diagnostics read methods for Core_Read_Package.
 *
 * @package MagickAIAbilities
 */

namespace Magick_AI_Abilities\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides WordPress diagnostics ability callbacks and helpers.
 */
trait Diagnostics_Read_Methods {
/**
 * Builds site diagnostics summary.
 *
 * @return array<string,mixed>
 */
private function build_site_diagnostics_summary() {
	$timezone = function_exists( 'wp_timezone_string' )
		? wp_timezone_string()
		: (string) get_option( 'timezone_string', '' );
	if ( '' === $timezone ) {
		$offset = get_option( 'gmt_offset', 0 );
		$timezone = 'UTC' . ( $offset ? sprintf( '%+g', $offset ) : '' );
	}

	return array(
		'name'                  => sanitize_text_field( (string) get_bloginfo( 'name' ) ),
		'language'              => sanitize_text_field( (string) get_locale() ),
		'timezone'              => sanitize_text_field( (string) $timezone ),
		'home_url_host'         => sanitize_text_field( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ),
		'site_url_host'         => sanitize_text_field( (string) wp_parse_url( site_url(), PHP_URL_HOST ) ),
		'is_multisite'          => function_exists( 'is_multisite' ) ? (bool) is_multisite() : false,
		'users_can_register'    => (bool) get_option( 'users_can_register', false ),
		'blog_public'           => (int) get_option( 'blog_public', 1 ),
		'permalink_mode'        => '' === (string) get_option( 'permalink_structure', '' ) ? 'plain' : 'custom',
	);
}

/**
 * Builds WordPress diagnostics summary.
 *
 * @return array<string,mixed>
 */
private function build_wordpress_diagnostics_summary() {
	$environment_type = function_exists( 'wp_get_environment_type' )
		? sanitize_key( (string) wp_get_environment_type() )
		: 'production';

	return array(
		'version'          => sanitize_text_field( (string) get_bloginfo( 'version' ) ),
		'environment_type' => $environment_type,
		'debug'            => array(
			'wp_debug'         => defined( 'WP_DEBUG' ) ? (bool) WP_DEBUG : false,
			'wp_debug_log'     => defined( 'WP_DEBUG_LOG' ) ? (bool) WP_DEBUG_LOG : false,
			'wp_debug_display' => defined( 'WP_DEBUG_DISPLAY' ) ? (bool) WP_DEBUG_DISPLAY : false,
			'script_debug'     => defined( 'SCRIPT_DEBUG' ) ? (bool) SCRIPT_DEBUG : false,
		),
		'constants'        => array(
			'disable_wp_cron' => defined( 'DISABLE_WP_CRON' ) ? (bool) DISABLE_WP_CRON : false,
		),
	);
}

/**
 * Builds PHP diagnostics summary.
 *
 * @return array<string,mixed>
 */
private function build_php_diagnostics_summary() {
	$memory_limit = (string) ini_get( 'memory_limit' );
	$upload_max_filesize = (string) ini_get( 'upload_max_filesize' );
	$post_max_size = (string) ini_get( 'post_max_size' );

	return array(
		'version'                  => sanitize_text_field( (string) phpversion() ),
		'sapi'                     => sanitize_text_field( (string) php_sapi_name() ),
		'memory_limit'             => sanitize_text_field( $memory_limit ),
		'memory_limit_bytes'       => $this->parse_ini_size_to_bytes( $memory_limit ),
		'max_execution_time'       => (int) ini_get( 'max_execution_time' ),
		'max_input_vars'           => (int) ini_get( 'max_input_vars' ),
		'post_max_size'            => sanitize_text_field( $post_max_size ),
		'post_max_size_bytes'      => $this->parse_ini_size_to_bytes( $post_max_size ),
		'upload_max_filesize'       => sanitize_text_field( $upload_max_filesize ),
		'upload_max_filesize_bytes' => $this->parse_ini_size_to_bytes( $upload_max_filesize ),
		'wp_max_upload_size_bytes'  => function_exists( 'wp_max_upload_size' ) ? (int) wp_max_upload_size() : 0,
		'extensions'                => $this->build_php_extension_diagnostics_summary(),
	);
}

/**
 * Builds PHP extension diagnostics.
 *
 * @return array<string,mixed>
 */
private function build_php_extension_diagnostics_summary() {
	$loaded = function_exists( 'get_loaded_extensions' ) ? get_loaded_extensions() : array();
	$loaded = is_array( $loaded ) ? array_map( 'strtolower', $loaded ) : array();
	$loaded = array_values( array_unique( array_map( array( $this, 'sanitize_diagnostics_identifier' ), $loaded ) ) );
	sort( $loaded );

	$common = array( 'curl', 'dom', 'exif', 'fileinfo', 'gd', 'imagick', 'intl', 'json', 'mbstring', 'mysqli', 'openssl', 'pdo', 'simplexml', 'xml', 'zip' );
	$common_status = array();
	foreach ( $common as $extension ) {
		$common_status[ $extension ] = in_array( $extension, $loaded, true );
	}

	return array(
		'included'      => true,
		'loaded_count'  => count( $loaded ),
		'loaded'        => $loaded,
		'common_status' => $common_status,
	);
}

/**
 * Builds current caller capability diagnostics.
 *
 * @return array<string,mixed>
 */
private function build_current_user_diagnostics_summary() {
	$user = function_exists( 'wp_get_current_user' ) ? wp_get_current_user() : null;
	$user_id = is_object( $user ) && isset( $user->ID ) ? absint( $user->ID ) : ( function_exists( 'get_current_user_id' ) ? absint( get_current_user_id() ) : 0 );
	$roles = is_object( $user ) && isset( $user->roles ) && is_array( $user->roles ) ? $user->roles : array();
	$roles = array_values( array_unique( array_map( array( $this, 'sanitize_diagnostics_identifier' ), $roles ) ) );
	sort( $roles );

	$allcaps = is_object( $user ) && isset( $user->allcaps ) && is_array( $user->allcaps ) ? $user->allcaps : array();
	$capabilities = array();
	foreach ( $allcaps as $capability => $granted ) {
		if ( ! $granted || ! is_string( $capability ) ) {
			continue;
		}
		$capabilities[] = $this->sanitize_diagnostics_identifier( $capability );
	}
	$capabilities = array_values( array_unique( array_filter( $capabilities ) ) );
	sort( $capabilities );

	$common_capabilities = array( 'manage_options', 'activate_plugins', 'update_plugins', 'install_plugins', 'edit_users', 'list_users', 'edit_posts', 'publish_posts', 'upload_files', 'edit_theme_options', 'switch_themes', 'unfiltered_html' );
	$common_status = array();
	foreach ( $common_capabilities as $capability ) {
		$common_status[ $capability ] = function_exists( 'current_user_can' ) ? (bool) current_user_can( $capability ) : false;
	}

	return array(
		'included'            => true,
		'user_id'             => $user_id,
		'user_login'          => is_object( $user ) && isset( $user->user_login ) ? sanitize_text_field( (string) $user->user_login ) : '',
		'display_name'        => is_object( $user ) && isset( $user->display_name ) ? sanitize_text_field( (string) $user->display_name ) : '',
		'roles'               => $roles,
		'capabilities'        => $capabilities,
		'common_capabilities' => $common_status,
		'magick_ai_permissions' => $this->build_magick_ai_permission_diagnostics_summary( $common_status ),
	);
}

/**
 * Builds local capability inferences for Magick AI-facing operations.
 *
 * @param array<string,bool> $common_status Common current_user_can() results.
 * @return array<string,mixed>
 */
private function build_magick_ai_permission_diagnostics_summary( array $common_status ) {
	$manage_options = (bool) ( $common_status['manage_options'] ?? false );
	$edit_posts = (bool) ( $common_status['edit_posts'] ?? false );
	$upload_files = (bool) ( $common_status['upload_files'] ?? false );

	return array(
		'policy_source'              => 'local_capability_inference',
		'host_policy_verified'       => false,
		'can_read_diagnostics'       => $manage_options,
		'can_read_adapter_context'   => $manage_options || $edit_posts,
		'can_run_read_abilities'     => $manage_options || $edit_posts || $upload_files,
		'can_create_proposal'        => $manage_options || $edit_posts,
		'can_approve_in_core'        => $manage_options,
		'can_manage_magick_settings' => $manage_options,
	);
}

/**
 * Builds external object cache diagnostics.
 *
 * @return array<string,mixed>
 */
private function build_object_cache_diagnostics_summary() {
	$dropin_path = defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR . '/object-cache.php' : '';
	$advanced_cache_path = defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR . '/advanced-cache.php' : '';
	$active_plugins = get_option( 'active_plugins', array() );
	$active_plugins = is_array( $active_plugins ) ? array_map( 'strtolower', $active_plugins ) : array();
	$known_cache_plugins = array(
		'redis-cache/redis-cache.php'                         => 'redis_object_cache',
		'w3-total-cache/w3-total-cache.php'                   => 'w3_total_cache',
		'wp-super-cache/wp-cache.php'                         => 'wp_super_cache',
		'wp-rocket/wp-rocket.php'                             => 'wp_rocket',
		'litespeed-cache/litespeed-cache.php'                 => 'litespeed_cache',
		'sg-cachepress/sg-cachepress.php'                     => 'siteground_optimizer',
		'object-cache-pro/object-cache-pro.php'               => 'object_cache_pro',
		'memcached-redux/memcached-redux.php'                 => 'memcached_redux',
	);
	$detected_cache_plugins = array();
	foreach ( $known_cache_plugins as $plugin_file => $label ) {
		if ( in_array( $plugin_file, $active_plugins, true ) ) {
			$detected_cache_plugins[] = $label;
		}
	}
	sort( $detected_cache_plugins );

	return array(
		'included'                => true,
		'external_object_cache'   => function_exists( 'wp_using_ext_object_cache' ) ? (bool) wp_using_ext_object_cache() : null,
		'object_cache_dropin'     => '' !== $dropin_path && file_exists( $dropin_path ),
		'advanced_cache_dropin'   => '' !== $advanced_cache_path && file_exists( $advanced_cache_path ),
		'known_cache_plugins'     => $detected_cache_plugins,
		'wp_cache_available'      => function_exists( 'wp_cache_get' ) && function_exists( 'wp_cache_set' ),
		'wp_cache_flush_runtime'  => function_exists( 'wp_cache_flush_runtime' ),
		'wp_cache_supports'       => function_exists( 'wp_cache_supports' ),
		'transient_count_estimate' => $this->estimate_transient_count(),
	);
}

/**
 * Estimates transient option count without returning option names.
 *
 * @return int|null
 */
private function estimate_transient_count() {
	global $wpdb;

	if ( ! is_object( $wpdb ) || empty( $wpdb->options ) || ! method_exists( $wpdb, 'get_var' ) || ! method_exists( $wpdb, 'esc_like' ) || ! method_exists( $wpdb, 'prepare' ) ) {
		return null;
	}

	$cache_key = 'transient_count_' . md5( (string) $wpdb->options . ':' . $this->get_read_cache_version() );
	$cache_group = 'magick_ai_abilities_diagnostics';
	if ( function_exists( 'wp_cache_get' ) ) {
		$cached = wp_cache_get( $cache_key, $cache_group );
		if ( is_array( $cached ) && array_key_exists( 'value', $cached ) ) {
			return null === $cached['value'] ? null : absint( $cached['value'] );
		}
		}

		$like = $wpdb->esc_like( '_transient_' ) . '%';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Diagnostics need a cached aggregate that WordPress does not expose through an API.
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );
		$value = null === $count ? null : absint( $count );
		if ( function_exists( 'wp_cache_set' ) ) {
		wp_cache_set( $cache_key, array( 'value' => $value ), $cache_group, 300 );
	}

	return $value;
}

/**
 * Builds rewrite diagnostics without reading webserver config files.
 *
 * @return array<string,mixed>
 */
private function build_rewrite_diagnostics_summary() {
	$permalink_structure = (string) get_option( 'permalink_structure', '' );
	$rewrite_rules = get_option( 'rewrite_rules', array() );
	$rewrite_rules = is_array( $rewrite_rules ) ? $rewrite_rules : array();

	return array(
		'included'            => true,
		'permalink_mode'      => '' === $permalink_structure ? 'plain' : 'custom',
		'permalink_structure' => sanitize_text_field( $permalink_structure ),
		'rewrite_rules_count' => count( $rewrite_rules ),
		'using_index_permalinks' => function_exists( 'got_url_rewrite' ) ? ! got_url_rewrite() && '' !== $permalink_structure : null,
		'webserver_config_checked' => false,
	);
}

/**
 * Builds HTTPS diagnostics without external HTTP probes.
 *
 * @return array<string,mixed>
 */
private function build_https_diagnostics_summary() {
	$home_scheme = (string) wp_parse_url( home_url(), PHP_URL_SCHEME );
	$site_scheme = (string) wp_parse_url( site_url(), PHP_URL_SCHEME );

	return array(
		'included'          => true,
		'is_ssl_request'    => function_exists( 'is_ssl' ) ? (bool) is_ssl() : null,
		'home_url_https'    => 'https' === strtolower( $home_scheme ),
		'site_url_https'    => 'https' === strtolower( $site_scheme ),
		'wp_using_https'    => function_exists( 'wp_is_using_https' ) ? (bool) wp_is_using_https() : null,
		'external_probe'    => false,
		'mixed_content_scan' => false,
	);
}

/**
 * Builds active theme diagnostics summary.
 *
 * @return array<string,mixed>
 */
private function build_theme_diagnostics_summary() {
	$theme = function_exists( 'wp_get_theme' ) ? wp_get_theme() : null;
	if ( ! $theme || ! is_object( $theme ) ) {
		return array(
			'included' => true,
			'available' => false,
		);
	}

	return array(
		'included'       => true,
		'available'      => true,
		'name'           => sanitize_text_field( (string) $theme->get( 'Name' ) ),
		'version'        => sanitize_text_field( (string) $theme->get( 'Version' ) ),
		'stylesheet'     => sanitize_key( (string) $theme->get_stylesheet() ),
		'template'       => sanitize_key( (string) $theme->get_template() ),
		'is_child_theme' => method_exists( $theme, 'parent' ) ? (bool) $theme->parent() : false,
		'is_block_theme' => function_exists( 'wp_is_block_theme' ) ? (bool) wp_is_block_theme() : null,
	);
}

/**
 * Builds plugin count diagnostics summary.
 *
 * @param array<string,mixed> $options Plugin group output controls.
 * @return array<string,mixed>
 */
private function build_plugin_diagnostics_summary( array $options = array() ) {
	$this->load_plugin_admin_functions();

	$include_active = ! array_key_exists( 'include_active', $options ) || ! empty( $options['include_active'] );
	$include_inactive = ! empty( $options['include_inactive'] );
	$include_updates = ! array_key_exists( 'include_updates', $options ) || ! empty( $options['include_updates'] );
	$include_must_use = ! array_key_exists( 'include_must_use', $options ) || ! empty( $options['include_must_use'] );
	$include_dropins = ! array_key_exists( 'include_dropins', $options ) || ! empty( $options['include_dropins'] );
	$max_plugins_per_group = max( 1, min( 500, absint( $options['max_plugins_per_group'] ?? 100 ) ) );

	$all_plugins = function_exists( 'get_plugins' ) ? get_plugins() : array();
	$all_plugins = is_array( $all_plugins ) ? $all_plugins : array();
	$active_plugins = get_option( 'active_plugins', array() );
	$active_plugins = is_array( $active_plugins ) ? $active_plugins : array();
	$network_active_plugins = function_exists( 'get_site_option' ) ? get_site_option( 'active_sitewide_plugins', array() ) : array();
	$network_active_plugins = is_array( $network_active_plugins ) ? $network_active_plugins : array();
	$mu_plugins = function_exists( 'get_mu_plugins' ) ? get_mu_plugins() : array();
	$mu_plugins = is_array( $mu_plugins ) ? $mu_plugins : array();
	$dropins = function_exists( 'get_dropins' ) ? get_dropins() : array();
	$dropins = is_array( $dropins ) ? $dropins : array();
	$active_lookup = array_flip( array_merge( $active_plugins, array_keys( $network_active_plugins ) ) );
	$inactive_plugins = array();
	foreach ( array_keys( $all_plugins ) as $plugin_file ) {
		if ( ! isset( $active_lookup[ $plugin_file ] ) ) {
			$inactive_plugins[] = $plugin_file;
		}
	}
	$update_plugins = $include_updates && function_exists( 'get_site_transient' ) ? get_site_transient( 'update_plugins' ) : false;
	$update_response = is_object( $update_plugins ) && isset( $update_plugins->response ) && is_array( $update_plugins->response )
		? $update_plugins->response
		: array();
	$update_available_plugins = array_keys( $update_response );

	return array(
		'included'             => true,
		'groups_included'      => array(
			'active'                => $include_active,
			'inactive'              => $include_inactive,
			'update_available'      => $include_updates,
			'must_use'              => $include_must_use,
			'dropins'               => $include_dropins,
		),
		'max_plugins_per_group' => $max_plugins_per_group,
		'available_count'      => count( $all_plugins ),
		'active_count'         => count( $active_plugins ),
		'network_active_count' => count( $network_active_plugins ),
		'inactive_count'       => count( $inactive_plugins ),
		'update_available_count' => count( $update_available_plugins ),
		'mu_count'             => count( $mu_plugins ),
		'dropin_count'         => count( $dropins ),
		'active_returned_count' => $include_active ? min( count( $active_plugins ), $max_plugins_per_group ) : 0,
		'inactive_returned_count' => $include_inactive ? min( count( $inactive_plugins ), $max_plugins_per_group ) : 0,
		'update_available_returned_count' => $include_updates ? min( count( $update_available_plugins ), $max_plugins_per_group ) : 0,
		'must_use_returned_count' => $include_must_use ? min( count( $mu_plugins ), $max_plugins_per_group ) : 0,
		'dropin_returned_count' => $include_dropins ? min( count( $dropins ), $max_plugins_per_group ) : 0,
		'active'               => $include_active ? $this->build_plugin_diagnostics_rows( $active_plugins, $all_plugins, 'active', $update_response, array(), $max_plugins_per_group ) : array(),
		'network_active_plugins' => $include_active ? $this->build_plugin_diagnostics_rows( array_keys( $network_active_plugins ), $all_plugins, 'network_active', $update_response, array_keys( $network_active_plugins ), $max_plugins_per_group ) : array(),
		'inactive'             => $include_inactive ? $this->build_plugin_diagnostics_rows( $inactive_plugins, $all_plugins, 'inactive', $update_response, array(), $max_plugins_per_group ) : array(),
		'update_available'     => $include_updates ? $this->build_plugin_diagnostics_rows( $update_available_plugins, $all_plugins, 'update_available', $update_response, array(), $max_plugins_per_group ) : array(),
		'must_use'             => $include_must_use ? $this->build_plugin_diagnostics_rows( array_keys( $mu_plugins ), $mu_plugins, 'must_use', array(), array(), $max_plugins_per_group ) : array(),
		'dropins'              => $include_dropins ? $this->build_dropin_diagnostics_rows( $dropins, $max_plugins_per_group ) : array(),
	);
}

/**
 * Builds bounded plugin rows without absolute filesystem paths.
 *
 * @param array<int|string,mixed>        $plugin_files Plugin file identifiers.
 * @param array<string,array<string,mixed>> $plugin_data Plugin metadata keyed by file.
 * @param string                         $status Plugin status.
 * @param array<string,mixed>            $update_response Update metadata keyed by plugin file.
 * @param array<int,string>              $network_active_plugins Network-active plugin files.
 * @param int                            $max_rows Maximum rows to return.
 * @return array<int,array<string,mixed>>
 */
private function build_plugin_diagnostics_rows( array $plugin_files, array $plugin_data, $status, array $update_response = array(), array $network_active_plugins = array(), $max_rows = 100 ) {
	$rows = array();
	$network_active_lookup = array_flip( $network_active_plugins );
	$max_rows = max( 1, min( 500, absint( $max_rows ) ) );
	foreach ( $plugin_files as $plugin_file ) {
		if ( count( $rows ) >= $max_rows ) {
			break;
		}
		$plugin_file = is_string( $plugin_file ) ? $plugin_file : '';
		if ( '' === $plugin_file ) {
			continue;
		}

		$data = isset( $plugin_data[ $plugin_file ] ) && is_array( $plugin_data[ $plugin_file ] )
			? $plugin_data[ $plugin_file ]
			: array();
		$update = isset( $update_response[ $plugin_file ] ) && is_object( $update_response[ $plugin_file ] )
			? $update_response[ $plugin_file ]
			: null;
		$dependencies = $this->parse_plugin_dependency_slugs( (string) ( $data['RequiresPlugins'] ?? '' ) );
		$rows[] = array(
			'slug'        => $this->derive_plugin_slug( $plugin_file ),
			'plugin_file' => $this->redact_diagnostics_path_identifier( $plugin_file ),
			'name'        => sanitize_text_field( (string) ( $data['Name'] ?? $data['Title'] ?? '' ) ),
			'version'     => sanitize_text_field( (string) ( $data['Version'] ?? '' ) ),
			'author'      => sanitize_text_field( wp_strip_all_tags( (string) ( $data['Author'] ?? '' ) ) ),
			'status'      => sanitize_key( (string) $status ),
			'network_active' => isset( $network_active_lookup[ $plugin_file ] ) || 'network_active' === $status,
			'must_use'    => 'must_use' === $status,
			'requires_wp' => sanitize_text_field( (string) ( $data['RequiresWP'] ?? '' ) ),
			'requires_php' => sanitize_text_field( (string) ( $data['RequiresPHP'] ?? '' ) ),
			'dependencies' => $dependencies,
			'dependency_count' => count( $dependencies ),
			'is_magick_ai' => $this->is_magick_ai_plugin_hint( $plugin_file, $data ),
			'update_available' => is_object( $update ),
			'latest_version'   => is_object( $update ) ? sanitize_text_field( (string) ( $update->new_version ?? '' ) ) : '',
		);
	}

	return $rows;
}

/**
 * Builds bounded drop-in rows without absolute filesystem paths.
 *
 * @param array<string,array<string,mixed>> $dropins Drop-in metadata.
 * @param int                              $max_rows Maximum rows to return.
 * @return array<int,array<string,mixed>>
 */
private function build_dropin_diagnostics_rows( array $dropins, $max_rows = 100 ) {
	$rows = array();
	$max_rows = max( 1, min( 500, absint( $max_rows ) ) );
	foreach ( $dropins as $dropin_file => $dropin ) {
		if ( count( $rows ) >= $max_rows ) {
			break;
		}
		$dropin = is_array( $dropin ) ? $dropin : array();
		$rows[] = array(
			'slug'        => $this->derive_plugin_slug( (string) $dropin_file ),
			'plugin_file' => $this->redact_diagnostics_path_identifier( (string) $dropin_file ),
			'name'        => sanitize_text_field( (string) ( $dropin['Name'] ?? $dropin_file ) ),
			'status'      => 'dropin',
			'dropin'      => true,
			'is_magick_ai' => $this->is_magick_ai_plugin_hint( (string) $dropin_file, $dropin ),
		);
	}

	return $rows;
}

/**
 * Derives a stable plugin slug from a plugin file identifier.
 *
 * @param string $plugin_file Plugin file identifier.
 * @return string
 */
private function derive_plugin_slug( $plugin_file ) {
	$plugin_file = trim( (string) $plugin_file );
	if ( '' === $plugin_file ) {
		return '';
	}

	$slug = false !== strpos( $plugin_file, '/' ) ? dirname( $plugin_file ) : basename( $plugin_file, '.php' );
	return $this->sanitize_diagnostics_identifier( $slug );
}

/**
 * Parses a comma-separated Requires Plugins header into bounded slugs.
 *
 * @param string $requires_plugins Requires Plugins header.
 * @return string[]
 */
private function parse_plugin_dependency_slugs( $requires_plugins ) {
	$requires_plugins = trim( (string) $requires_plugins );
	if ( '' === $requires_plugins ) {
		return array();
	}

	$dependencies = array();
	foreach ( explode( ',', $requires_plugins ) as $dependency ) {
		$dependency = $this->sanitize_diagnostics_identifier( $dependency );
		if ( '' !== $dependency ) {
			$dependencies[] = $dependency;
		}
	}
	$dependencies = array_values( array_unique( $dependencies ) );
	sort( $dependencies );

	return $dependencies;
}

/**
 * Detects whether plugin metadata likely belongs to the Magick AI family.
 *
 * @param string              $plugin_file Plugin file identifier.
 * @param array<string,mixed> $data Plugin metadata.
 * @return bool
 */
private function is_magick_ai_plugin_hint( $plugin_file, array $data ) {
	$haystack = strtolower(
		(string) $plugin_file . ' ' .
		(string) ( $data['Name'] ?? '' ) . ' ' .
		(string) ( $data['Title'] ?? '' ) . ' ' .
		(string) ( $data['TextDomain'] ?? '' )
	);

	return false !== strpos( $haystack, 'magick-ai' ) || false !== strpos( $haystack, 'magick ai' );
}

/**
 * Builds REST API diagnostics summary without making HTTP requests.
 *
 * @return array<string,mixed>
 */
private function build_rest_api_diagnostics_summary() {
	$routes = array();
	if ( function_exists( 'rest_get_server' ) ) {
		$server = rest_get_server();
		if ( is_object( $server ) && method_exists( $server, 'get_routes' ) ) {
			$routes = $server->get_routes();
			$routes = is_array( $routes ) ? $routes : array();
		}
	}

	return array(
		'available'                 => function_exists( 'rest_url' ) && function_exists( 'rest_get_server' ),
		'url_host'                  => function_exists( 'rest_url' ) ? sanitize_text_field( (string) wp_parse_url( rest_url(), PHP_URL_HOST ) ) : '',
		'route_count'               => count( $routes ),
		'wp_abilities_routes_found' => $this->routes_include_prefix( $routes, '/wp-abilities/v1' ),
	);
}

/**
 * Builds Abilities API diagnostics summary.
 *
 * @return array<string,mixed>
 */
private function build_abilities_api_diagnostics_summary() {
	return array(
		'register_ability_available'          => function_exists( 'wp_register_ability' ),
		'register_category_available'         => function_exists( 'wp_register_ability_category' ),
		'get_ability_available'               => function_exists( 'wp_get_ability' ),
		'has_ability_available'               => function_exists( 'wp_has_ability' ),
		'get_category_available'              => function_exists( 'wp_get_ability_category' ),
		'has_category_available'              => function_exists( 'wp_has_ability_category' ),
		'diagnostics_summary_registered'      => function_exists( 'wp_has_ability' ) ? (bool) wp_has_ability( 'npcink-abilities-toolkit/wp-diagnostics-summary' ) : null,
		'legacy_site_info_registered'         => function_exists( 'wp_has_ability' ) ? (bool) wp_has_ability( 'magick-ai/site-info' ) : null,
	);
}

/**
 * Builds cron diagnostics summary.
 *
 * @return array<string,mixed>
 */
private function build_cron_diagnostics_summary() {
	$total = 0;
	$next_timestamp = 0;
	if ( function_exists( '_get_cron_array' ) ) {
		$cron_array = _get_cron_array();
		$cron_array = is_array( $cron_array ) ? $cron_array : array();
		foreach ( $cron_array as $timestamp => $hooks ) {
			$timestamp = absint( $timestamp );
			if ( $timestamp > 0 && ( 0 === $next_timestamp || $timestamp < $next_timestamp ) ) {
				$next_timestamp = $timestamp;
			}
			if ( ! is_array( $hooks ) ) {
				continue;
			}
			foreach ( $hooks as $events ) {
				if ( is_array( $events ) ) {
					$total += count( $events );
				}
			}
		}
	}

	return array(
		'included'               => true,
		'disabled'               => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
		'scheduled_events_total' => $total,
		'next_event_gmt'         => $next_timestamp > 0 ? gmdate( 'Y-m-d H:i:s', $next_timestamp ) : '',
	);
}

/**
 * Builds update diagnostics summary from current update data only.
 *
 * @return array<string,mixed>
 */
private function build_updates_diagnostics_summary() {
	$update_data = function_exists( 'wp_get_update_data' ) ? wp_get_update_data() : array();
	$counts = is_array( $update_data['counts'] ?? null ) ? $update_data['counts'] : array();

	return array(
		'included'     => true,
		'total'        => absint( $counts['total'] ?? 0 ),
		'wordpress'    => absint( $counts['wordpress'] ?? 0 ),
		'plugins'      => absint( $counts['plugins'] ?? 0 ),
		'themes'       => absint( $counts['themes'] ?? 0 ),
		'translations' => absint( $counts['translations'] ?? 0 ),
	);
}

/**
 * Builds server diagnostics without exposing filesystem paths.
 *
 * @return array<string,mixed>
 */
private function build_server_diagnostics_detail() {
	$software = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( (string) wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '';
	$document_root_present = ! empty( $_SERVER['DOCUMENT_ROOT'] );

	return array(
		'included'              => true,
		'php_sapi'              => sanitize_text_field( (string) php_sapi_name() ),
		'server_software'       => $software,
		'os_family'             => PHP_OS_FAMILY,
		'environment_type'      => function_exists( 'wp_get_environment_type' ) ? sanitize_key( (string) wp_get_environment_type() ) : 'production',
		'home_url_host'         => sanitize_text_field( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ),
		'site_url_host'         => sanitize_text_field( (string) wp_parse_url( site_url(), PHP_URL_HOST ) ),
		'document_root_present' => $document_root_present,
		'document_root_redacted' => $document_root_present,
	);
}

/**
 * Builds redacted database diagnostics.
 *
 * @return array<string,mixed>
 */
private function build_database_diagnostics_detail() {
	global $wpdb;

	$available = is_object( $wpdb );
	$version = '';
	if ( $available && method_exists( $wpdb, 'db_version' ) ) {
		$version = sanitize_text_field( (string) $wpdb->db_version() );
	}
	$server_info = '';
	if ( $available && method_exists( $wpdb, 'db_server_info' ) ) {
		$server_info = sanitize_text_field( (string) $wpdb->db_server_info() );
	}

	$tables = $this->read_database_table_status_rows();
	$table_count = is_array( $tables ) ? count( $tables ) : null;
	$total_size_bytes = 0;
	if ( is_array( $tables ) ) {
		foreach ( $tables as $table ) {
			if ( is_array( $table ) ) {
				$total_size_bytes += absint( $table['Data_length'] ?? 0 );
				$total_size_bytes += absint( $table['Index_length'] ?? 0 );
			} elseif ( is_object( $table ) ) {
				$total_size_bytes += absint( $table->Data_length ?? 0 );
				$total_size_bytes += absint( $table->Index_length ?? 0 );
			}
		}
	}

	$engine = 'unknown';
	$engine_source = '' !== $server_info ? $server_info : $version;
	if ( false !== stripos( $engine_source, 'mariadb' ) ) {
		$engine = 'mariadb';
	} elseif ( '' !== $engine_source ) {
		$engine = 'mysql';
	}

	return array(
		'included'                => true,
		'available'               => $available,
		'engine'                  => $engine,
		'version'                 => $version,
		'server_info'             => $server_info,
		'table_count'             => $table_count,
		'estimated_size_bytes'    => is_array( $tables ) ? $total_size_bytes : null,
		'database_name_redacted'  => true,
		'table_prefix_redacted'   => true,
		'table_names_redacted'    => true,
	);
}

/**
 * Reads database table status rows when WPDB is available.
 *
 * @return array<mixed>|null
 */
private function read_database_table_status_rows() {
	global $wpdb;

	if ( ! is_object( $wpdb ) || ! method_exists( $wpdb, 'get_results' ) ) {
		return null;
	}

	$cache_key = 'table_status_' . md5( (string) ( $wpdb->prefix ?? '' ) . ':' . $this->get_read_cache_version() );
	$cache_group = 'magick_ai_abilities_diagnostics';
	if ( function_exists( 'wp_cache_get' ) ) {
		$cached = wp_cache_get( $cache_key, $cache_group );
		if ( is_array( $cached ) && array_key_exists( 'rows', $cached ) ) {
			return is_array( $cached['rows'] ) ? $cached['rows'] : null;
		}
		}

		$output_type = defined( 'ARRAY_A' ) ? ARRAY_A : 'ARRAY_A';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Diagnostics table-size summaries require SHOW TABLE STATUS and are cached for bounded reads.
		$rows = $wpdb->get_results( 'SHOW TABLE STATUS', $output_type );
		$rows = is_array( $rows ) ? $rows : null;
		if ( function_exists( 'wp_cache_set' ) ) {
		wp_cache_set( $cache_key, array( 'rows' => $rows ), $cache_group, 300 );
	}

	return $rows;
}

/**
 * Builds bounded cron event diagnostics.
 *
 * @param int $max_events Maximum number of events to include.
 * @return array<string,mixed>
 */
private function build_cron_events_diagnostics_detail( $max_events ) {
	$total = 0;
	$events = array();
	$cron_array = function_exists( '_get_cron_array' ) ? _get_cron_array() : array();
	$cron_array = is_array( $cron_array ) ? $cron_array : array();
	ksort( $cron_array );

	foreach ( $cron_array as $timestamp => $hooks ) {
		$timestamp = absint( $timestamp );
		if ( ! is_array( $hooks ) ) {
			continue;
		}
		foreach ( $hooks as $hook => $instances ) {
			if ( ! is_array( $instances ) ) {
				continue;
			}
			foreach ( $instances as $instance ) {
				++$total;
				if ( count( $events ) >= $max_events ) {
					continue;
				}
				$instance = is_array( $instance ) ? $instance : array();
				$args = isset( $instance['args'] ) && is_array( $instance['args'] ) ? $instance['args'] : array();
				$events[] = array(
					'hook'         => sanitize_key( (string) $hook ),
					'next_run_gmt' => $timestamp > 0 ? gmdate( 'Y-m-d H:i:s', $timestamp ) : '',
					'schedule'     => sanitize_key( (string) ( $instance['schedule'] ?? '' ) ),
					'interval'     => absint( $instance['interval'] ?? 0 ),
					'args_count'   => count( $args ),
				);
			}
		}
	}

	return array(
		'included'               => true,
		'disabled'               => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
		'scheduled_events_total' => $total,
		'returned_events_count'  => count( $events ),
		'events'                 => $events,
		'args_redacted'          => true,
	);
}

/**
 * Builds error log diagnostics, optionally tailing redacted contents.
 *
 * @param bool $include_contents Include a bounded tail of log contents.
 * @param int      $tail_lines Number of tail lines to include.
 * @param int      $since_minutes Only include parsed entries since this many minutes ago. Zero disables filtering.
 * @param string[] $severity_filter Severity filter.
 * @return array<string,mixed>
 */
private function build_error_log_diagnostics_detail( $include_contents = false, $tail_lines = 50, $since_minutes = 0, array $severity_filter = array() ) {
	$wp_debug_log = defined( 'WP_DEBUG_LOG' ) ? WP_DEBUG_LOG : false;
	$log_path = '';
	if ( is_string( $wp_debug_log ) && '' !== $wp_debug_log ) {
		$log_path = $wp_debug_log;
	} elseif ( $wp_debug_log && defined( 'WP_CONTENT_DIR' ) ) {
		$log_path = WP_CONTENT_DIR . '/debug.log';
	} else {
		$ini_error_log = (string) ini_get( 'error_log' );
		$log_path = '' !== $ini_error_log ? $ini_error_log : '';
	}

	$exists = '' !== $log_path && file_exists( $log_path );
	$readable = $exists && is_readable( $log_path );
	$size_bytes = $exists ? filesize( $log_path ) : false;
	$modified = $exists ? filemtime( $log_path ) : false;
	$summary_entries = array();
	$tail_entries = array();
	if ( $readable ) {
		$summary_entries = $this->read_redacted_log_tail_entries( $log_path, $tail_lines, $since_minutes, $severity_filter );
		if ( $include_contents ) {
			$tail_entries = $summary_entries;
		}
	}
	$contents = array();
	foreach ( $tail_entries as $entry ) {
		$contents[] = (string) ( $entry['line'] ?? '' );
	}

	return array(
		'included'              => true,
		'wp_debug'              => defined( 'WP_DEBUG' ) ? (bool) WP_DEBUG : false,
		'wp_debug_log'          => (bool) $wp_debug_log,
		'php_log_configured'    => '' !== (string) ini_get( 'error_log' ),
		'log_file_hint'         => '' !== $log_path ? $this->redact_diagnostics_path_identifier( $log_path ) : '',
		'log_exists'            => $exists,
		'log_readable'          => $readable,
		'log_size_bytes'        => false === $size_bytes ? null : absint( $size_bytes ),
		'log_modified_gmt'      => false === $modified ? '' : gmdate( 'Y-m-d H:i:s', absint( $modified ) ),
		'contents_included'     => $include_contents && $readable,
		'tail_lines_requested'  => absint( $tail_lines ),
		'tail_lines_returned'   => count( $tail_entries ),
		'since_minutes'         => absint( $since_minutes ),
		'severity_filter'       => $severity_filter,
		'contents_redacted'     => $include_contents,
		'contents'              => $contents,
		'tail_entries'          => $tail_entries,
		'summary'               => $this->summarize_diagnostics_log_entries( $summary_entries ),
		'source_summary'        => $this->summarize_diagnostics_log_sources( $summary_entries ),
		'top_messages'          => $this->summarize_diagnostics_top_messages( $summary_entries ),
	);
}

/**
 * Normalizes a diagnostics log severity filter.
 *
 * @param mixed $severity Severity input.
 * @return string[]
 */
private function normalize_diagnostics_log_severity_filter( $severity ) {
	$allowed = array( 'fatal', 'error', 'warning', 'deprecated', 'notice', 'info', 'unknown' );
	$values = is_array( $severity ) ? $severity : ( '' !== (string) $severity ? array( $severity ) : array() );
	$normalized = array();
	foreach ( $values as $value ) {
		$value = sanitize_key( (string) $value );
		if ( in_array( $value, $allowed, true ) ) {
			$normalized[] = $value;
		}
	}
	$normalized = array_values( array_unique( $normalized ) );
	sort( $normalized );

	return $normalized;
}

/**
 * Reads, redacts, parses, and filters a bounded tail from a log file.
 *
 * @param string   $path Log path.
 * @param int      $tail_lines Number of lines.
 * @param int      $since_minutes Only include parsed entries since this many minutes ago.
 * @param string[] $severity_filter Severity filter.
 * @return array<int,array<string,mixed>>
 */
private function read_redacted_log_tail_entries( $path, $tail_lines, $since_minutes = 0, array $severity_filter = array() ) {
	$path = (string) $path;
	$tail_lines = max( 1, min( 200, absint( $tail_lines ) ) );
	if ( '' === $path || ! is_readable( $path ) ) {
		return array();
	}

	$max_bytes = 262144;
	$contents = $this->read_diagnostics_log_contents( $path, $max_bytes );
	if ( '' === $contents ) {
		return array();
	}

	$lines = preg_split( "/\r\n|\n|\r/", $contents );
	$lines = is_array( $lines ) ? $lines : array();
	$lines = array_values(
		array_filter(
			$lines,
			static function ( $line ) {
				return '' !== trim( (string) $line );
			}
		)
	);
	$lines = array_slice( $lines, -1 * $tail_lines );
	$since_timestamp = absint( $since_minutes ) > 0 ? time() - ( absint( $since_minutes ) * 60 ) : 0;
	$severity_lookup = array_flip( $severity_filter );
	$entries = array();
	foreach ( $lines as $line ) {
		$entry = $this->parse_diagnostics_log_entry( $line );
		if ( $since_timestamp > 0 && ! empty( $entry['timestamp_unix'] ) && absint( $entry['timestamp_unix'] ) < $since_timestamp ) {
			continue;
		}
		if ( ! empty( $severity_lookup ) && ! isset( $severity_lookup[ $entry['severity'] ] ) ) {
			continue;
		}
		unset( $entry['timestamp_unix'] );
		$entries[] = $entry;
	}

	return $entries;
}

/**
 * Reads bounded log contents through WordPress filesystem APIs.
 *
 * @param string $path Log path.
 * @param int    $max_bytes Maximum bytes to retain from the end.
 * @return string
 */
private function read_diagnostics_log_contents( $path, $max_bytes ) {
	if ( ! function_exists( 'WP_Filesystem' ) ) {
		return '';
	}

	global $wp_filesystem;
	if ( ! is_object( $wp_filesystem ) ) {
		WP_Filesystem();
	}
	if ( ! is_object( $wp_filesystem ) || ! method_exists( $wp_filesystem, 'get_contents' ) ) {
		return '';
	}

	$contents = $wp_filesystem->get_contents( (string) $path );
	if ( ! is_string( $contents ) ) {
		return '';
	}

	$max_bytes = max( 1, absint( $max_bytes ) );
	return strlen( $contents ) > $max_bytes ? substr( $contents, -1 * $max_bytes ) : $contents;
}

	/**
	 * Parses one log line into a redacted diagnostics entry.
 *
 * @param mixed $line Raw line.
 * @return array<string,mixed>
 */
private function parse_diagnostics_log_entry( $line ) {
	$source = $this->detect_diagnostics_log_source( $line );
	$redacted_line = $this->redact_diagnostics_log_line( $line );
	$message = $redacted_line;
	$timestamp_gmt = '';
	$timestamp_unix = 0;
	if ( preg_match( '/^\[([^\]]+)\]\s*(.*)$/', $redacted_line, $matches ) ) {
		$parsed_timestamp = strtotime( (string) $matches[1] );
		$timestamp_unix = false === $parsed_timestamp ? 0 : absint( $parsed_timestamp );
		$timestamp_gmt = $timestamp_unix > 0 ? gmdate( 'Y-m-d H:i:s', $timestamp_unix ) : '';
		$message = (string) $matches[2];
	}

	return array(
		'timestamp_gmt'       => $timestamp_gmt,
		'timestamp_unix'      => $timestamp_unix,
		'severity'            => $this->detect_diagnostics_log_severity( $message ),
		'source_type'         => $source['source_type'],
		'source_hint'         => $source['source_hint'],
		'source_basename'     => $source['source_basename'],
		'phar_hint'           => $source['phar_hint'],
		'message_fingerprint' => $this->fingerprint_diagnostics_log_message( $message ),
		'message'             => sanitize_text_field( $message ),
		'line'                => $redacted_line,
	);
}

/**
 * Detects a safe source hint from a raw log line before path redaction.
 *
 * @param mixed $line Raw line.
 * @return array{source_type:string,source_hint:string,source_basename:string,phar_hint:string}
 */
private function detect_diagnostics_log_source( $line ) {
	$line = str_replace( '\\', '/', (string) $line );
	$lower = strtolower( $line );
	$phar_basename = $this->detect_diagnostics_phar_basename( $line );

	if ( preg_match( '#wp-content/(?:plugins|mu-plugins)/([^/\\s:\'"]+)#i', $line, $matches ) ) {
		return array(
			'source_type'     => 'plugin',
			'source_hint'     => $this->sanitize_diagnostics_source_hint( $matches[1] ),
			'source_basename' => '',
			'phar_hint'       => '',
		);
	}

	if ( preg_match( '#wp-content/themes/([^/\\s:\'"]+)#i', $line, $matches ) ) {
		return array(
			'source_type'     => 'theme',
			'source_hint'     => $this->sanitize_diagnostics_source_hint( $matches[1] ),
			'source_basename' => '',
			'phar_hint'       => '',
		);
	}

	if ( false !== strpos( $lower, 'plugin-check' ) || false !== strpos( $lower, 'plugin check' ) ) {
		return array(
			'source_type'     => 'plugin',
			'source_hint'     => 'plugin-check',
			'source_basename' => '',
			'phar_hint'       => '',
		);
	}

	if ( false !== strpos( $lower, 'wp-cli.phar' ) || false !== strpos( $lower, 'wp-cli' ) ) {
		return array(
			'source_type'     => 'phar',
			'source_hint'     => 'wp-cli',
			'source_basename' => '' !== $phar_basename ? $phar_basename : '',
			'phar_hint'       => '' !== $phar_basename ? $phar_basename : '',
		);
	}

	if ( false !== strpos( $lower, 'composer.phar' ) || false !== strpos( $lower, 'composer' ) ) {
		return array(
			'source_type'     => 'phar',
			'source_hint'     => 'composer',
			'source_basename' => '' !== $phar_basename ? $phar_basename : '',
			'phar_hint'       => '' !== $phar_basename ? $phar_basename : '',
		);
	}

	if ( preg_match( '#phar://[^\\s:\'"]*/([^/\\s:\'"]+\\.phar)#i', $line, $matches ) ) {
		$source_basename = $this->sanitize_diagnostics_source_basename( $matches[1] );
		return array(
			'source_type'     => 'phar',
			'source_hint'     => $this->sanitize_diagnostics_source_hint( preg_replace( '/\\.phar$/i', '', (string) $matches[1] ) ),
			'source_basename' => $source_basename,
			'phar_hint'       => $source_basename,
		);
	}

	if (
		false !== strpos( $lower, '/wp-includes/' )
		|| false !== strpos( $lower, '/wp-admin/' )
		|| false !== strpos( $lower, '/wp-load.php' )
		|| false !== strpos( $lower, '/wp-settings.php' )
	) {
		return array(
			'source_type'     => 'core',
			'source_hint'     => 'wordpress-core',
			'source_basename' => '',
			'phar_hint'       => '',
		);
	}

	return array(
		'source_type'     => 'unknown',
		'source_hint'     => 'unknown',
		'source_basename' => '',
		'phar_hint'       => '',
	);
}

/**
 * Detects a safe PHAR basename hint without exposing its full path.
 *
 * @param string $line Raw log line.
 * @return string
 */
private function detect_diagnostics_phar_basename( $line ) {
	if ( preg_match( '#(?:phar://)?[^\\s:\'"]*/([^/\\s:\'"]+\\.phar)#i', (string) $line, $matches ) ) {
		return $this->sanitize_diagnostics_source_basename( $matches[1] );
	}

	if ( preg_match( '#\\b([a-z0-9_.-]+\\.phar)\\b#i', (string) $line, $matches ) ) {
		return $this->sanitize_diagnostics_source_basename( $matches[1] );
	}

	return '';
}

/**
 * Sanitizes a log source hint without exposing paths.
 *
 * @param mixed $value Raw source hint.
 * @return string
 */
private function sanitize_diagnostics_source_hint( $value ) {
	$value = basename( str_replace( '\\', '/', (string) $value ) );
	$value = $this->sanitize_diagnostics_identifier( $value );

	return '' !== $value ? $value : 'unknown';
}

/**
 * Sanitizes a source basename hint without exposing paths.
 *
 * @param mixed $value Raw source basename.
 * @return string
 */
private function sanitize_diagnostics_source_basename( $value ) {
	$value = basename( str_replace( '\\', '/', (string) $value ) );
	$value = $this->sanitize_diagnostics_identifier( $value );

	return '' !== $value ? $value : '';
}

/**
 * Builds a stable, redacted message fingerprint for aggregation.
 *
 * @param mixed $message Redacted log message.
 * @return string
 */
private function fingerprint_diagnostics_log_message( $message ) {
	$fingerprint = sanitize_text_field( (string) $message );
	$fingerprint = preg_replace( '/^PHP\\s+(?:Fatal\\s+error|Parse\\s+error|Recoverable\\s+fatal\\s+error|Deprecated|Warning|Notice|Strict\\s+Standards|Error):\\s*/i', '', $fingerprint );
	$fingerprint = preg_replace( '/\\s+in\\s+(?:phar:\\/\\/\\[REDACTED_PATH\\]|\\[ABSPATH\\]|\\[HOME\\]|\\[TMP_PATH\\]|\\[PHP_FILE\\]).*?\\s+on\\s+line\\s+\\d+\\s*$/i', '', $fingerprint );
	$fingerprint = preg_replace( '/\\s+on\\s+line\\s+\\d+\\s*$/i', '', $fingerprint );
	$fingerprint = preg_replace( '/\\b\\d+\\b/', 'N', $fingerprint );
	$fingerprint = preg_replace( '/\\s+/', ' ', $fingerprint );
	$fingerprint = trim( is_string( $fingerprint ) ? $fingerprint : '' );
	if ( '' === $fingerprint ) {
		$fingerprint = 'unknown';
	}

	return substr( $fingerprint, 0, 160 );
}

/**
 * Detects severity from a redacted log message.
 *
 * @param string $message Redacted message.
 * @return string
 */
private function detect_diagnostics_log_severity( $message ) {
	$message = strtolower( (string) $message );
	if ( false !== strpos( $message, 'fatal error' ) || false !== strpos( $message, 'parse error' ) ) {
		return 'fatal';
	}
	if ( false !== strpos( $message, 'deprecated' ) ) {
		return 'deprecated';
	}
	if ( false !== strpos( $message, 'warning' ) ) {
		return 'warning';
	}
	if ( false !== strpos( $message, 'notice' ) ) {
		return 'notice';
	}
	if ( false !== strpos( $message, 'error' ) ) {
		return 'error';
	}
	if ( false !== strpos( $message, 'info' ) ) {
		return 'info';
	}

	return 'unknown';
}

/**
 * Summarizes returned log entries by severity.
 *
 * @param array<int,array<string,mixed>> $entries Parsed entries.
 * @return array<string,mixed>
 */
private function summarize_diagnostics_log_entries( array $entries ) {
	$by_severity = array(
		'fatal'      => 0,
		'error'      => 0,
		'warning'    => 0,
		'deprecated' => 0,
		'notice'     => 0,
		'info'       => 0,
		'unknown'    => 0,
	);
	$latest_by_severity = array(
		'fatal'      => '',
		'error'      => '',
		'warning'    => '',
		'deprecated' => '',
		'notice'     => '',
		'info'       => '',
		'unknown'    => '',
	);
	foreach ( $entries as $entry ) {
		$severity = sanitize_key( (string) ( $entry['severity'] ?? 'unknown' ) );
		if ( ! isset( $by_severity[ $severity ] ) ) {
			$severity = 'unknown';
		}
		++$by_severity[ $severity ];
		$timestamp_gmt = sanitize_text_field( (string) ( $entry['timestamp_gmt'] ?? '' ) );
		if ( '' !== $timestamp_gmt && ( '' === $latest_by_severity[ $severity ] || strcmp( $timestamp_gmt, $latest_by_severity[ $severity ] ) > 0 ) ) {
			$latest_by_severity[ $severity ] = $timestamp_gmt;
		}
	}

	return array(
		'returned_lines' => count( $entries ),
		'fatal_count'   => $by_severity['fatal'],
		'error_count'   => $by_severity['error'],
		'warning_count' => $by_severity['warning'],
		'deprecated_count' => $by_severity['deprecated'],
		'notice_count'  => $by_severity['notice'],
		'info_count'    => $by_severity['info'],
		'unknown_count' => $by_severity['unknown'],
		'latest_fatal_at' => $latest_by_severity['fatal'],
		'latest_error_at' => $latest_by_severity['error'],
		'latest_warning_at' => $latest_by_severity['warning'],
		'latest_deprecated_at' => $latest_by_severity['deprecated'],
		'latest_notice_at' => $latest_by_severity['notice'],
		'summary_source' => 'bounded_tail',
		'by_severity'   => $by_severity,
	);
}

/**
 * Summarizes returned log entries by safe source hint and severity.
 *
 * @param array<int,array<string,mixed>> $entries Parsed entries.
 * @return array<int,array<string,mixed>>
 */
private function summarize_diagnostics_log_sources( array $entries ) {
	$summary = array();

	foreach ( $entries as $entry ) {
		$source_type = sanitize_key( (string) ( $entry['source_type'] ?? 'unknown' ) );
		if ( ! in_array( $source_type, array( 'plugin', 'theme', 'core', 'phar', 'unknown' ), true ) ) {
			$source_type = 'unknown';
		}

		$source_hint = $this->sanitize_diagnostics_source_hint( $entry['source_hint'] ?? 'unknown' );
		$severity = sanitize_key( (string) ( $entry['severity'] ?? 'unknown' ) );
		if ( ! in_array( $severity, array( 'fatal', 'error', 'warning', 'deprecated', 'notice', 'info', 'unknown' ), true ) ) {
			$severity = 'unknown';
		}

		$message_fingerprint = sanitize_text_field( (string) ( $entry['message_fingerprint'] ?? 'unknown' ) );
		$message_fingerprint = '' !== $message_fingerprint ? $message_fingerprint : 'unknown';
		$source_basename = $this->sanitize_diagnostics_source_basename( $entry['source_basename'] ?? '' );
		$phar_hint = $this->sanitize_diagnostics_source_basename( $entry['phar_hint'] ?? '' );

		$key = $source_type . '|' . $source_hint . '|' . $severity . '|' . $message_fingerprint . '|' . $source_basename;
		if ( ! isset( $summary[ $key ] ) ) {
			$summary[ $key ] = array(
				'source_type'         => $source_type,
				'source_hint'         => $source_hint,
				'severity'            => $severity,
				'message_fingerprint' => $message_fingerprint,
				'count'               => 0,
				'latest_at'           => '',
			);
			if ( '' !== $source_basename ) {
				$summary[ $key ]['source_basename'] = $source_basename;
			}
			if ( '' !== $phar_hint ) {
				$summary[ $key ]['phar_hint'] = $phar_hint;
			}
		}

		++$summary[ $key ]['count'];
		$timestamp_gmt = sanitize_text_field( (string) ( $entry['timestamp_gmt'] ?? '' ) );
		if ( '' !== $timestamp_gmt && ( '' === $summary[ $key ]['latest_at'] || strcmp( $timestamp_gmt, $summary[ $key ]['latest_at'] ) > 0 ) ) {
			$summary[ $key ]['latest_at'] = $timestamp_gmt;
		}
	}

	$summary = array_values( $summary );
	usort(
		$summary,
		static function ( $left, $right ) {
			$count_compare = (int) ( $right['count'] ?? 0 ) <=> (int) ( $left['count'] ?? 0 );
			if ( 0 !== $count_compare ) {
				return $count_compare;
			}

			return strcmp( (string) ( $right['latest_at'] ?? '' ), (string) ( $left['latest_at'] ?? '' ) );
		}
	);

	return array_slice( $summary, 0, 20 );
}

/**
 * Summarizes returned log entries by message fingerprint and source.
 *
 * @param array<int,array<string,mixed>> $entries Parsed entries.
 * @return array<int,array<string,mixed>>
 */
private function summarize_diagnostics_top_messages( array $entries ) {
	$summary = array();

	foreach ( $entries as $entry ) {
		$severity = sanitize_key( (string) ( $entry['severity'] ?? 'unknown' ) );
		if ( ! in_array( $severity, array( 'fatal', 'error', 'warning', 'deprecated', 'notice', 'info', 'unknown' ), true ) ) {
			$severity = 'unknown';
		}

		$fingerprint = sanitize_text_field( (string) ( $entry['message_fingerprint'] ?? 'unknown' ) );
		$fingerprint = '' !== $fingerprint ? $fingerprint : 'unknown';
		$source_type = sanitize_key( (string) ( $entry['source_type'] ?? 'unknown' ) );
		if ( ! in_array( $source_type, array( 'plugin', 'theme', 'core', 'phar', 'unknown' ), true ) ) {
			$source_type = 'unknown';
		}
		$source_hint = $this->sanitize_diagnostics_source_hint( $entry['source_hint'] ?? 'unknown' );
		$source_basename = $this->sanitize_diagnostics_source_basename( $entry['source_basename'] ?? '' );
		$phar_hint = $this->sanitize_diagnostics_source_basename( $entry['phar_hint'] ?? '' );

		$key = $severity . '|' . $fingerprint . '|' . $source_type . '|' . $source_hint . '|' . $source_basename;
		if ( ! isset( $summary[ $key ] ) ) {
			$summary[ $key ] = array(
				'severity'            => $severity,
				'fingerprint'         => $fingerprint,
				'message_fingerprint' => $fingerprint,
				'count'               => 0,
				'latest_at'           => '',
				'source_type'         => $source_type,
				'source_hint'         => $source_hint,
			);
			if ( '' !== $source_basename ) {
				$summary[ $key ]['source_basename'] = $source_basename;
			}
			if ( '' !== $phar_hint ) {
				$summary[ $key ]['phar_hint'] = $phar_hint;
			}
		}

		++$summary[ $key ]['count'];
		$timestamp_gmt = sanitize_text_field( (string) ( $entry['timestamp_gmt'] ?? '' ) );
		if ( '' !== $timestamp_gmt && ( '' === $summary[ $key ]['latest_at'] || strcmp( $timestamp_gmt, $summary[ $key ]['latest_at'] ) > 0 ) ) {
			$summary[ $key ]['latest_at'] = $timestamp_gmt;
		}
	}

	$summary = array_values( $summary );
	usort(
		$summary,
		static function ( $left, $right ) {
			$count_compare = (int) ( $right['count'] ?? 0 ) <=> (int) ( $left['count'] ?? 0 );
			if ( 0 !== $count_compare ) {
				return $count_compare;
			}

			return strcmp( (string) ( $right['latest_at'] ?? '' ), (string) ( $left['latest_at'] ?? '' ) );
		}
	);

	return array_slice( $summary, 0, 20 );
}

/**
 * Redacts sensitive tokens and absolute paths from one diagnostics log line.
 *
 * @param mixed $line Raw line.
 * @return string
 */
private function redact_diagnostics_log_line( $line ) {
	$line = sanitize_text_field( (string) $line );
	$line = preg_replace( '/([?&](?:key|token|secret|password|pass|pwd|api_key|apikey)=)[^\\s&]+/i', '$1[REDACTED]', $line );
	$line = preg_replace( '/((?:api[_-]?key|token|secret|password|authorization)\\s*[=:]\\s*)[^\\s,;]+/i', '$1[REDACTED]', $line );
	$line = preg_replace( '#phar://[^\\s\'"]+#', 'phar://[REDACTED_PATH]', $line );
	$line = preg_replace( '#/(?:private/)?tmp/[^\\s:\']+#', '[TMP_PATH]', $line );
	$line = preg_replace( '#/[^\\s:]+/wp-content/#', '[ABSPATH]/wp-content/', $line );
	$line = preg_replace( '#/Users/[^\\s:]+/#', '[HOME]/', $line );
	$line = preg_replace( '#/[^\\s:\']+\\.php#', '[PHP_FILE]', $line );

	return is_string( $line ) ? $line : '';
}

/**
 * Builds custom post type diagnostics.
 *
 * @return array<string,mixed>
 */
private function build_content_type_diagnostics_detail() {
	$post_types = function_exists( 'get_post_types' ) ? get_post_types( array(), 'objects' ) : array();
	$post_types = is_array( $post_types ) ? $post_types : array();
	$items = array();
	foreach ( $post_types as $post_type => $object ) {
		if ( ! is_object( $object ) ) {
			continue;
		}
		$items[] = array(
			'name'         => sanitize_key( (string) $post_type ),
			'label'        => sanitize_text_field( (string) ( $object->label ?? $post_type ) ),
			'public'       => (bool) ( $object->public ?? false ),
			'show_in_rest' => (bool) ( $object->show_in_rest ?? false ),
			'hierarchical' => (bool) ( $object->hierarchical ?? false ),
		);
	}

	return array(
		'included'        => true,
		'post_type_count' => count( $items ),
		'post_types'      => $items,
	);
}

/**
 * Builds role and capability diagnostics.
 *
 * @return array<string,mixed>
 */
private function build_roles_diagnostics_detail() {
	$wp_roles = function_exists( 'wp_roles' ) ? wp_roles() : null;
	$roles = is_object( $wp_roles ) && isset( $wp_roles->roles ) && is_array( $wp_roles->roles ) ? $wp_roles->roles : array();
	$items = array();
	foreach ( $roles as $role_key => $role ) {
		$role = is_array( $role ) ? $role : array();
		$capabilities = array();
		foreach ( (array) ( $role['capabilities'] ?? array() ) as $capability => $granted ) {
			if ( $granted && is_string( $capability ) ) {
				$capabilities[] = $this->sanitize_diagnostics_identifier( $capability );
			}
		}
		$capabilities = array_values( array_unique( array_filter( $capabilities ) ) );
		sort( $capabilities );
		$items[] = array(
			'role'         => sanitize_key( (string) $role_key ),
			'name'         => sanitize_text_field( (string) ( $role['name'] ?? $role_key ) ),
			'capabilities' => $capabilities,
		);
	}

	return array(
		'included'   => true,
		'role_count' => count( $items ),
		'roles'      => $items,
	);
}

/**
 * Builds widget and sidebar diagnostics.
 *
 * @return array<string,mixed>
 */
private function build_widgets_diagnostics_detail() {
	global $wp_registered_sidebars, $wp_registered_widgets;

	$sidebars = is_array( $wp_registered_sidebars ) ? $wp_registered_sidebars : array();
	$widgets = is_array( $wp_registered_widgets ) ? $wp_registered_widgets : array();
	$sidebar_items = array();
	foreach ( $sidebars as $sidebar_id => $sidebar ) {
		$sidebar = is_array( $sidebar ) ? $sidebar : array();
		$sidebar_items[] = array(
			'id'          => sanitize_key( (string) $sidebar_id ),
			'name'        => sanitize_text_field( (string) ( $sidebar['name'] ?? $sidebar_id ) ),
			'description' => sanitize_text_field( (string) ( $sidebar['description'] ?? '' ) ),
		);
	}

	$widget_items = array();
	foreach ( $widgets as $widget_id => $widget ) {
		$widget = is_array( $widget ) ? $widget : array();
		$widget_items[] = array(
			'id'   => sanitize_key( (string) $widget_id ),
			'name' => sanitize_text_field( (string) ( $widget['name'] ?? $widget_id ) ),
		);
		if ( count( $widget_items ) >= 100 ) {
			break;
		}
	}

	return array(
		'included'                => true,
		'sidebar_count'           => count( $sidebar_items ),
		'sidebars'                => $sidebar_items,
		'registered_widget_count' => count( $widgets ),
		'widgets_returned_count'  => count( $widget_items ),
		'widgets'                 => $widget_items,
	);
}

/**
 * Builds block-theme related diagnostics.
 *
 * @return array<string,mixed>
 */
private function build_block_theme_diagnostics_detail() {
	$block_count = null;
	$block_items = array();
	if ( class_exists( '\WP_Block_Type_Registry' ) && method_exists( '\WP_Block_Type_Registry', 'get_instance' ) ) {
		$registry = \WP_Block_Type_Registry::get_instance();
		if ( is_object( $registry ) && method_exists( $registry, 'get_all_registered' ) ) {
			$blocks = $registry->get_all_registered();
			$block_count = is_array( $blocks ) ? count( $blocks ) : null;
			if ( is_array( $blocks ) ) {
				foreach ( array_keys( $blocks ) as $block_name ) {
					$block_items[] = $this->sanitize_diagnostics_identifier( $block_name );
					if ( count( $block_items ) >= 100 ) {
						break;
					}
				}
			}
		}
	}

	$pattern_count = null;
	$pattern_items = array();
	if ( class_exists( '\WP_Block_Patterns_Registry' ) && method_exists( '\WP_Block_Patterns_Registry', 'get_instance' ) ) {
		$registry = \WP_Block_Patterns_Registry::get_instance();
		if ( is_object( $registry ) && method_exists( $registry, 'get_all_registered' ) ) {
			$patterns = $registry->get_all_registered();
			$pattern_count = is_array( $patterns ) ? count( $patterns ) : null;
			if ( is_array( $patterns ) ) {
				foreach ( $patterns as $pattern ) {
					$pattern = is_array( $pattern ) ? $pattern : array();
					$pattern_items[] = array(
						'name'       => $this->sanitize_diagnostics_identifier( $pattern['name'] ?? '' ),
						'title'      => sanitize_text_field( (string) ( $pattern['title'] ?? '' ) ),
						'categories' => array_values( array_map( array( $this, 'sanitize_diagnostics_identifier' ), (array) ( $pattern['categories'] ?? array() ) ) ),
					);
					if ( count( $pattern_items ) >= 100 ) {
						break;
					}
				}
			}
		}
	}

	$style_variations = array();
	if ( class_exists( '\WP_Theme_JSON_Resolver' ) && method_exists( '\WP_Theme_JSON_Resolver', 'get_style_variations' ) ) {
		try {
			$style_variations = \WP_Theme_JSON_Resolver::get_style_variations();
		} catch ( \Throwable $error ) {
			$style_variations = array();
		}
	}
	$style_variations = is_array( $style_variations ) ? $style_variations : array();
	$style_items = array();
	foreach ( $style_variations as $variation ) {
		$variation = is_array( $variation ) ? $variation : array();
		$style_items[] = array(
			'title' => sanitize_text_field( (string) ( $variation['title'] ?? '' ) ),
		);
		if ( count( $style_items ) >= 50 ) {
			break;
		}
	}

	return array(
		'included'                 => true,
		'is_block_theme'           => function_exists( 'wp_is_block_theme' ) ? (bool) wp_is_block_theme() : null,
		'registered_block_count'   => $block_count,
		'registered_blocks_returned_count' => count( $block_items ),
		'registered_blocks'        => $block_items,
		'registered_pattern_count' => $pattern_count,
		'registered_patterns_returned_count' => count( $pattern_items ),
		'registered_patterns'      => $pattern_items,
		'global_style_variation_count' => count( $style_variations ),
		'global_style_variations_returned_count' => count( $style_items ),
		'global_style_variations'  => $style_items,
		'global_styles_available'  => function_exists( 'wp_get_global_styles' ) || function_exists( 'wp_get_global_settings' ),
		'global_styles_content_included' => false,
	);
}

/**
 * Builds search diagnostics.
 *
 * @return array<string,mixed>
 */
private function build_search_diagnostics_detail() {
	$active_plugins = get_option( 'active_plugins', array() );
	$active_plugins = is_array( $active_plugins ) ? array_map( 'strtolower', $active_plugins ) : array();
	$known_plugins = array(
		'elasticpress/elasticpress.php' => 'elasticpress',
		'relevanssi/relevanssi.php'     => 'relevanssi',
		'searchwp/index.php'            => 'searchwp',
		'woocommerce/woocommerce.php'   => 'woocommerce_product_search_possible',
	);
	$detected = array();
	foreach ( $known_plugins as $plugin_file => $label ) {
		if ( in_array( $plugin_file, $active_plugins, true ) ) {
			$detected[] = $label;
		}
	}
	if ( class_exists( '\ElasticPress' ) && ! in_array( 'elasticpress', $detected, true ) ) {
		$detected[] = 'elasticpress';
	}
	if ( function_exists( 'relevanssi_do_query' ) && ! in_array( 'relevanssi', $detected, true ) ) {
		$detected[] = 'relevanssi';
	}
	if ( class_exists( '\SearchWP' ) && ! in_array( 'searchwp', $detected, true ) ) {
		$detected[] = 'searchwp';
	}

	sort( $detected );

	return array(
		'included'                  => true,
		'core_search_available'     => true,
		'known_search_integrations' => $detected,
		'external_index_status'     => empty( $detected ) ? 'not_detected' : 'detected',
		'index_health_verified'     => false,
	);
}

/**
 * Builds integration compatibility diagnostics for common content plugins.
 *
 * @param array<string,mixed> $plugins Plugin diagnostics.
 * @return array<string,mixed>
 */
private function build_integrations_diagnostics_detail( array $plugins ) {
	$active_plugin_files = $this->active_plugin_files_from_diagnostics( $plugins );
	$known_integrations = array(
		'woocommerce/woocommerce.php'                  => 'woocommerce',
		'advanced-custom-fields/acf.php'               => 'acf',
		'advanced-custom-fields-pro/acf.php'           => 'acf_pro',
		'easy-digital-downloads/easy-digital-downloads.php' => 'easy_digital_downloads',
		'memberpress/memberpress.php'                  => 'memberpress',
		'learnpress/learnpress.php'                    => 'learnpress',
		'tutor/tutor.php'                              => 'tutor_lms',
	);
	$detected = array();
	foreach ( $known_integrations as $plugin_file => $label ) {
		if ( in_array( $plugin_file, $active_plugin_files, true ) ) {
			$detected[] = $label;
		}
	}
	if ( class_exists( '\WooCommerce' ) && ! in_array( 'woocommerce', $detected, true ) ) {
		$detected[] = 'woocommerce';
	}
	if ( function_exists( 'acf' ) && ! in_array( 'acf', $detected, true ) && ! in_array( 'acf_pro', $detected, true ) ) {
		$detected[] = 'acf';
	}
	sort( $detected );

	return array(
		'included'             => true,
		'detected'             => $detected,
		'woocommerce_active'   => in_array( 'woocommerce', $detected, true ),
		'acf_active'           => in_array( 'acf', $detected, true ) || in_array( 'acf_pro', $detected, true ),
		'custom_post_types'    => $this->build_public_custom_post_type_names(),
		'deep_plugin_state_checked' => false,
	);
}

/**
 * Returns active plugin file identifiers from plugin diagnostics.
 *
 * @param array<string,mixed> $plugins Plugin diagnostics.
 * @return string[]
 */
private function active_plugin_files_from_diagnostics( array $plugins ) {
	$active_plugin_files = array();
	foreach ( (array) ( $plugins['active'] ?? array() ) as $plugin ) {
		if ( is_array( $plugin ) && ! empty( $plugin['plugin_file'] ) ) {
			$active_plugin_files[] = strtolower( (string) $plugin['plugin_file'] );
		}
	}

	return array_values( array_unique( $active_plugin_files ) );
}

/**
 * Returns public custom post type names.
 *
 * @return string[]
 */
private function build_public_custom_post_type_names() {
	$post_types = function_exists( 'get_post_types' ) ? get_post_types( array( 'public' => true ), 'names' ) : array();
	$post_types = is_array( $post_types ) ? $post_types : array();
	$names = array();
	foreach ( $post_types as $post_type ) {
		$post_type = sanitize_key( (string) $post_type );
		if ( '' !== $post_type && ! in_array( $post_type, array( 'post', 'page', 'attachment' ), true ) ) {
			$names[] = $post_type;
		}
	}
	sort( $names );

	return $names;
}

/**
 * Builds a compact SEO diagnostics summary.
 *
 * @param array<string,mixed> $plugins Plugin diagnostics.
 * @param array<string,mixed> $rewrite Rewrite diagnostics.
 * @return array<string,mixed>
 */
private function build_seo_diagnostics_summary( array $plugins, array $rewrite ) {
	$active_plugin_files = $this->active_plugin_files_from_diagnostics( $plugins );

	$seo_plugin_hints = array();
	$known_seo_plugins = array(
		'wordpress-seo/wp-seo.php'                    => 'yoast_seo',
		'seo-by-rank-math/rank-math.php'              => 'rank_math',
		'all-in-one-seo-pack/all_in_one_seo_pack.php' => 'aioseo',
		'autodescription/autodescription.php'         => 'the_seo_framework',
	);
	foreach ( $known_seo_plugins as $plugin_file => $label ) {
		if ( in_array( $plugin_file, $active_plugin_files, true ) ) {
			$seo_plugin_hints[] = $label;
		}
	}

	return array(
		'included'           => true,
		'site_title_present' => '' !== trim( (string) get_bloginfo( 'name' ) ),
		'tagline_present'    => '' !== trim( (string) get_bloginfo( 'description' ) ),
		'blog_public'        => (int) get_option( 'blog_public', 1 ),
		'permalink_mode'     => sanitize_key( (string) ( $rewrite['permalink_mode'] ?? '' ) ),
		'known_seo_plugins'  => $seo_plugin_hints,
		'detected_provider'   => $this->detect_seo_provider(),
		'meta_key_map'        => $this->seo_meta_keys( $this->detect_seo_provider() ),
		'core_sitemap_available' => function_exists( 'wp_sitemaps_get_server' ),
		'robots_txt_filter_available' => function_exists( 'has_filter' ) ? (bool) has_filter( 'robots_txt' ) : null,
		'external_audit_run' => false,
	);
}

/**
 * Builds a compact security diagnostics summary.
 *
 * @param array<string,mixed> $https HTTPS diagnostics.
 * @param array<string,mixed> $current_user Current user diagnostics.
 * @return array<string,mixed>
 */
private function build_security_diagnostics_summary( array $https, array $current_user ) {
	return array(
		'included'              => true,
		'https_configured'      => ! empty( $https['home_url_https'] ) && ! empty( $https['site_url_https'] ),
		'wp_debug_display'      => defined( 'WP_DEBUG_DISPLAY' ) ? (bool) WP_DEBUG_DISPLAY : false,
		'users_can_register'    => (bool) get_option( 'users_can_register', false ),
		'file_edit_disabled'    => defined( 'DISALLOW_FILE_EDIT' ) ? (bool) DISALLOW_FILE_EDIT : false,
		'caller_manage_options' => (bool) ( $current_user['common_capabilities']['manage_options'] ?? false ),
		'external_scan_run'     => false,
	);
}

/**
 * Builds a compact performance diagnostics summary.
 *
 * @param array<string,mixed> $object_cache Object cache diagnostics.
 * @param array<string,mixed> $php PHP diagnostics.
 * @param array<string,mixed> $cron Cron diagnostics.
 * @param array<string,mixed> $updates Update diagnostics.
 * @return array<string,mixed>
 */
private function build_performance_diagnostics_summary( array $object_cache, array $php, array $cron, array $updates ) {
	return array(
		'included'               => true,
		'external_object_cache'  => $object_cache['external_object_cache'] ?? null,
		'object_cache_dropin'    => $object_cache['object_cache_dropin'] ?? null,
		'memory_limit_bytes'     => absint( $php['memory_limit_bytes'] ?? 0 ),
		'max_execution_time'     => absint( $php['max_execution_time'] ?? 0 ),
		'cron_disabled'          => (bool) ( $cron['disabled'] ?? false ),
		'scheduled_events_total' => absint( $cron['scheduled_events_total'] ?? 0 ),
		'updates_total'          => absint( $updates['total'] ?? 0 ),
		'profile_run'            => false,
	);
}

/**
 * Loads plugin admin helpers if available.
 *
 * @return void
 */
private function load_plugin_admin_functions() {
	if ( function_exists( 'get_plugins' ) && function_exists( 'get_mu_plugins' ) ) {
		return;
	}
}

/**
 * Checks whether REST routes include a prefix.
 *
 * @param array<mixed> $routes REST routes.
 * @param string       $prefix Route prefix.
 * @return bool
 */
private function routes_include_prefix( array $routes, $prefix ) {
	$prefix = (string) $prefix;
	foreach ( array_keys( $routes ) as $route ) {
		if ( 0 === strpos( (string) $route, $prefix ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Sanitizes an identifier-like diagnostics value.
 *
 * @param mixed $value Raw value.
 * @return string
 */
private function sanitize_diagnostics_identifier( $value ) {
	$value = strtolower( trim( (string) $value ) );
	$value = preg_replace( '/[^a-z0-9_\\-\\.\\/]/', '', $value );

	return is_string( $value ) ? $value : '';
}

/**
 * Redacts absolute filesystem paths into relative hints.
 *
 * @param mixed $path Raw path or plugin identifier.
 * @return string
 */
private function redact_diagnostics_path_identifier( $path ) {
	$path = str_replace( '\\', '/', trim( (string) $path ) );
	if ( '' === $path ) {
		return '';
	}

	$anchors = array();
	foreach ( array( 'WP_PLUGIN_DIR', 'WPMU_PLUGIN_DIR', 'WP_CONTENT_DIR', 'ABSPATH' ) as $constant ) {
		if ( defined( $constant ) ) {
			$anchors[] = str_replace( '\\', '/', rtrim( (string) constant( $constant ), '/' ) );
		}
	}
	foreach ( array_filter( $anchors ) as $anchor ) {
		if ( 0 === strpos( $path, $anchor . '/' ) ) {
			$path = ltrim( substr( $path, strlen( $anchor ) ), '/' );
			break;
		}
	}

	$known_segments = array( '/wp-content/plugins/', '/wp-content/mu-plugins/', '/wp-content/' );
	foreach ( $known_segments as $segment ) {
		$position = strpos( $path, $segment );
		if ( false !== $position ) {
			$path = substr( $path, $position + strlen( $segment ) );
			break;
		}
	}

	if ( 0 === strpos( $path, '/' ) ) {
		$path = basename( $path );
	}

	return sanitize_text_field( $path );
}

/**
 * Parses shorthand ini sizes to bytes.
 *
 * @param string $value Ini size value.
 * @return int
 */
private function parse_ini_size_to_bytes( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return 0;
	}

	$unit = strtolower( substr( $value, -1 ) );
	$number = (float) $value;
	switch ( $unit ) {
		case 'g':
			$number *= 1024;
			// Fall through.
		case 'm':
			$number *= 1024;
			// Fall through.
		case 'k':
			$number *= 1024;
			break;
	}

	return max( 0, (int) $number );
}

}
