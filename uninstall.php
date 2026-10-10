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
	npcink_abilities_toolkit_uninstall_admin_user_meta();
	npcink_abilities_toolkit_uninstall_media_backup_artifacts();
}

/**
 * Removes per-admin notice state left behind by the admin surface.
 *
 * @return void
 */
function npcink_abilities_toolkit_uninstall_admin_user_meta() {
	if ( ! function_exists( 'delete_metadata' ) ) {
		return;
	}

	delete_metadata( 'user', 0, 'npcink_abilities_toolkit_welcome_pending', '', true );
	delete_metadata( 'user', 0, 'npcink_abilities_toolkit_health_notices_dismissed', '', true );
}

/**
 * Removes media replacement history meta and backup files owned by Toolkit.
 *
 * Two filters control what stays behind: hosts that keep their own media
 * lineage evidence can set npcink_abilities_toolkit_uninstall_preserve_media_backups
 * to keep the backup files, and npcink_abilities_toolkit_uninstall_preserve_media_history
 * (which now defaults to preserving) to keep the history meta. The history
 * meta uses the shared legacy _npcink_ai_ prefix, so hosts integrating with a
 * predecessor plugin keep it unless they explicitly opt out.
 *
 * @return void
 */
function npcink_abilities_toolkit_uninstall_media_backup_artifacts() {
	$preserve = false;
	if ( function_exists( 'apply_filters' ) ) {
		$preserve = (bool) apply_filters( 'npcink_abilities_toolkit_uninstall_preserve_media_backups', false );
		$preserve_history = (bool) apply_filters( 'npcink_abilities_toolkit_uninstall_preserve_media_history', true );
	} else {
		$preserve_history = true;
	}

	if ( ! $preserve_history ) {
		foreach ( array( '_npcink_ai_media_file_replacement_history', '_npcink_ai_media_latest_file_replacement' ) as $npcink_abilities_toolkit_meta_key ) {
			if ( function_exists( 'delete_post_meta_by_key' ) ) {
				delete_post_meta_by_key( $npcink_abilities_toolkit_meta_key );
			}
		}
	}

	if ( $preserve ) {
		return;
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
	if ( is_link( $npcink_abilities_toolkit_backup_dir ) ) {
		// Never descend through a symlinked backup directory; remove the link only.
		npcink_abilities_toolkit_uninstall_delete_file( $npcink_abilities_toolkit_backup_dir );
		return;
	}
	if ( is_dir( $npcink_abilities_toolkit_backup_dir ) ) {
		npcink_abilities_toolkit_uninstall_rrmdir( $npcink_abilities_toolkit_backup_dir );
	}
}

/**
 * Deletes one file or symlink through the WordPress file API.
 *
 * Deletion is skipped when the WordPress file API is unavailable (standalone
 * harness contexts) rather than falling back to raw unlink() calls, so the
 * packaged plugin keeps using WordPress APIs only.
 *
 * @param string $path Absolute file path.
 * @return void
 */
function npcink_abilities_toolkit_uninstall_delete_file( $path ) {
	if ( function_exists( 'wp_delete_file' ) ) {
		wp_delete_file( $path );
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
		if ( is_link( $npcink_abilities_toolkit_path ) ) {
			// Treat symlinks as files: delete the link itself, never its target.
			npcink_abilities_toolkit_uninstall_delete_file( $npcink_abilities_toolkit_path );
			continue;
		}
		if ( is_dir( $npcink_abilities_toolkit_path ) ) {
			npcink_abilities_toolkit_uninstall_rrmdir( $npcink_abilities_toolkit_path );
			continue;
		}
		if ( is_file( $npcink_abilities_toolkit_path ) ) {
			npcink_abilities_toolkit_uninstall_delete_file( $npcink_abilities_toolkit_path );
		}
	}

	// WordPress ships no directory-removal API; the Filesystem abstraction
	// cannot be loaded from uninstall without wp-admin path assumptions, and
	// this manual recursion is the symlink-safe implementation under test.
	@rmdir( $directory ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
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
