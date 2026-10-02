<?php
/**
 * Removes persistent plugin-owned options.
 *
 * @package NpcinkAbilitiesToolkit
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Deletes persistent Toolkit options from the current site.
 *
 * Expiring transients and object-cache entries are intentionally left to their
 * normal expiry/eviction lifecycle.
 *
 * @return void
 */
function npcink_abilities_toolkit_uninstall_current_site() {
	if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
		wp_clear_scheduled_hook( 'npcink_abilities_toolkit_cleanup_media_backups' );
	}
	delete_option( 'npcink_abilities_toolkit_catalog_observability_state' );
	delete_option( 'npcink_abilities_toolkit_read_cache_version' );
	delete_option( 'npcink_abilities_toolkit_media_backup_cleanup_cursor' );
	delete_option( 'npcink_abilities_toolkit_media_backup_manual_cleanup_cursor' );
	npcink_abilities_toolkit_uninstall_media_backup_artifacts();
}

/**
 * Removes media replacement history meta and backup files owned by Toolkit.
 *
 * Hosts that keep their own media lineage evidence can set the
 * npcink_abilities_toolkit_uninstall_preserve_media_backups filter to true;
 * the history meta uses the shared legacy _npcink_ai_ prefix.
 *
 * @return void
 */
function npcink_abilities_toolkit_uninstall_media_backup_artifacts() {
	$preserve = false;
	if ( function_exists( 'apply_filters' ) ) {
		$preserve = (bool) apply_filters( 'npcink_abilities_toolkit_uninstall_preserve_media_backups', false );
	}
	if ( $preserve ) {
		return;
	}

	foreach ( array( '_npcink_ai_media_file_replacement_history', '_npcink_ai_media_latest_file_replacement' ) as $npcink_abilities_toolkit_meta_key ) {
		if ( function_exists( 'delete_post_meta_by_key' ) ) {
			delete_post_meta_by_key( $npcink_abilities_toolkit_meta_key );
		}
	}

	npcink_abilities_toolkit_uninstall_remove_backups_directory();
}

/**
 * Removes the dedicated uploads backup directory tree.
 *
 * @return void
 */
function npcink_abilities_toolkit_uninstall_remove_backups_directory() {
	if ( ! function_exists( 'wp_upload_dir' ) ) {
		return;
	}

	$npcink_abilities_toolkit_uploads = wp_upload_dir();
	$npcink_abilities_toolkit_basedir = isset( $npcink_abilities_toolkit_uploads['basedir'] ) && is_string( $npcink_abilities_toolkit_uploads['basedir'] )
		? rtrim( $npcink_abilities_toolkit_uploads['basedir'], '/\\' )
		: '';
	if ( '' === $npcink_abilities_toolkit_basedir ) {
		return;
	}

	$npcink_abilities_toolkit_backup_dir = $npcink_abilities_toolkit_basedir . '/npcink-abilities-toolkit-backups';
	if ( is_dir( $npcink_abilities_toolkit_backup_dir ) ) {
		npcink_abilities_toolkit_uninstall_rrmdir( $npcink_abilities_toolkit_backup_dir );
	}
}

/**
 * Recursively removes one directory and its files.
 *
 * @param string $directory Absolute directory path.
 * @return void
 */
function npcink_abilities_toolkit_uninstall_rrmdir( $directory ) {
	$npcink_abilities_toolkit_entries = is_dir( $directory ) ? scandir( $directory ) : false;
	if ( ! is_array( $npcink_abilities_toolkit_entries ) ) {
		return;
	}

	foreach ( $npcink_abilities_toolkit_entries as $npcink_abilities_toolkit_entry ) {
		if ( '.' === $npcink_abilities_toolkit_entry || '..' === $npcink_abilities_toolkit_entry ) {
			continue;
		}
		$npcink_abilities_toolkit_path = $directory . '/' . $npcink_abilities_toolkit_entry;
		if ( is_dir( $npcink_abilities_toolkit_path ) ) {
			npcink_abilities_toolkit_uninstall_rrmdir( $npcink_abilities_toolkit_path );
			continue;
		}
		if ( function_exists( 'wp_delete_file' ) ) {
			wp_delete_file( $npcink_abilities_toolkit_path );
		} elseif ( is_file( $npcink_abilities_toolkit_path ) ) {
			@unlink( $npcink_abilities_toolkit_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
	}

	@rmdir( $directory ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
}

if ( is_multisite() && function_exists( 'get_sites' ) ) {
	$npcink_abilities_toolkit_site_offset = 0;
	$npcink_abilities_toolkit_site_limit = 100;
	do {
		$npcink_abilities_toolkit_site_ids = get_sites(
			array(
				'fields' => 'ids',
				'number' => $npcink_abilities_toolkit_site_limit,
				'offset' => $npcink_abilities_toolkit_site_offset,
			)
		);
		foreach ( $npcink_abilities_toolkit_site_ids as $npcink_abilities_toolkit_site_id ) {
			switch_to_blog( (int) $npcink_abilities_toolkit_site_id );
			npcink_abilities_toolkit_uninstall_current_site();
			restore_current_blog();
		}
		$npcink_abilities_toolkit_site_offset += count( $npcink_abilities_toolkit_site_ids );
	} while ( count( $npcink_abilities_toolkit_site_ids ) === $npcink_abilities_toolkit_site_limit );
} else {
	npcink_abilities_toolkit_uninstall_current_site();
}
