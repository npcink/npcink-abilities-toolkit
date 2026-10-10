<?php
/**
 * Behavioral regression for uninstall cleanup.
 *
 * @package NpcinkAbilitiesToolkit
 */

$mode = $argv[1] ?? 'single';
if ( ! in_array( $mode, array( 'single', 'multisite' ), true ) ) {
	fwrite( STDERR, "Usage: php tests/uninstall-cleanup.php single|multisite\n" );
	exit( 2 );
}

define( 'WP_UNINSTALL_PLUGIN', 'npcink-abilities-toolkit/npcink-abilities-toolkit.php' );
$GLOBALS['npcink_uninstall_deleted_options'] = array();
$GLOBALS['npcink_uninstall_switched_sites'] = array();
$GLOBALS['npcink_uninstall_restores'] = 0;
$GLOBALS['npcink_uninstall_cleared_hooks'] = array();
$GLOBALS['npcink_uninstall_deleted_meta_keys'] = array();
$GLOBALS['npcink_uninstall_deleted_user_meta_keys'] = array();
$GLOBALS['npcink_uninstall_preserve_media_backups'] = false;

$npcink_uninstall_uploads = sys_get_temp_dir() . '/npcink-uninstall-test-' . getmypid();
register_shutdown_function( static function () use ( $npcink_uninstall_uploads ) {
	if ( is_dir( $npcink_uninstall_uploads ) ) {
		$npcink_uninstall_stack = array( $npcink_uninstall_uploads );
		while ( $npcink_uninstall_stack ) {
			$npcink_uninstall_dir = array_pop( $npcink_uninstall_stack );
			foreach ( (array) scandir( $npcink_uninstall_dir ) as $npcink_uninstall_entry ) {
				if ( '.' === $npcink_uninstall_entry || '..' === $npcink_uninstall_entry ) {
					continue;
				}
				$npcink_uninstall_path = $npcink_uninstall_dir . '/' . $npcink_uninstall_entry;
				if ( is_dir( $npcink_uninstall_path ) && ! is_link( $npcink_uninstall_path ) ) {
					$npcink_uninstall_stack[] = $npcink_uninstall_path;
				} elseif ( is_link( $npcink_uninstall_path ) ) {
					@unlink( $npcink_uninstall_path );
				} elseif ( is_file( $npcink_uninstall_path ) ) {
					@unlink( $npcink_uninstall_path );
				}
			}
			@rmdir( $npcink_uninstall_dir );
		}
	}
} );
$npcink_uninstall_backups = $npcink_uninstall_uploads . '/npcink-abilities-toolkit-backups';
@mkdir( $npcink_uninstall_backups . '/2026/01', 0777, true );
@mkdir( $npcink_uninstall_uploads . '/unrelated', 0777, true );
file_put_contents( $npcink_uninstall_backups . '/2026/01/backup.jpg', 'backup-bytes' );
file_put_contents( $npcink_uninstall_backups . '/top.png', 'backup-bytes' );
file_put_contents( $npcink_uninstall_uploads . '/unrelated/keep.txt', 'keep' );
$npcink_uninstall_symlinks_supported = @symlink( $npcink_uninstall_uploads . '/unrelated', $npcink_uninstall_backups . '/linked-outside' ) !== false;

function wp_clear_scheduled_hook( $hook ) {
	$GLOBALS['npcink_uninstall_cleared_hooks'][] = (string) $hook;
	return true;
}

/**
 * Applies the bounded uninstall filter set.
 *
 * @param string $tag Filter tag.
 * @param mixed  $value Default value.
 * @return mixed
 */
function apply_filters( $tag, $value ) {
	if ( 'npcink_abilities_toolkit_uninstall_preserve_media_backups' === $tag ) {
		return $GLOBALS['npcink_uninstall_preserve_media_backups'];
	}
	if ( 'npcink_abilities_toolkit_uninstall_preserve_media_history' === $tag ) {
		return isset( $GLOBALS['npcink_uninstall_preserve_media_history'] ) ? $GLOBALS['npcink_uninstall_preserve_media_history'] : $value;
	}
	return $value;
}

/**
 * Records a post meta key deletion.
 *
 * @param string $meta_key Meta key.
 * @return bool
 */
function delete_post_meta_by_key( $meta_key ) {
	$GLOBALS['npcink_uninstall_deleted_meta_keys'][] = (string) $meta_key;
	return true;
}

/**
 * Records a bulk user meta key deletion.
 *
 * @param string $meta_type Meta type.
 * @param int    $user_id User id (ignored on delete-all).
 * @param string $meta_key Meta key.
 * @param mixed  $meta_value Meta value (ignored on delete-all).
 * @param bool   $delete_all Whether every entry is deleted.
 * @return bool
 */
function delete_metadata( $meta_type, $user_id, $meta_key, $meta_value = '', $delete_all = false ) {
	unset( $user_id, $meta_value, $delete_all );
	if ( 'user' === (string) $meta_type ) {
		$GLOBALS['npcink_uninstall_deleted_user_meta_keys'][] = (string) $meta_key;
	}
	return true;
}

/**
 * Returns the isolated test uploads directory.
 *
 * @return array<string,mixed>
 */
function wp_upload_dir() {
	return array( 'basedir' => $GLOBALS['npcink_uninstall_uploads'] );
}

/**
 * Deletes a file like WordPress core: void return, unlink under the hood.
 *
 * @param string $file Absolute file path.
 * @return void
 */
function wp_delete_file( $file ) {
	if ( is_file( $file ) || is_link( $file ) ) {
		@unlink( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}
}

/**
 * Records an option deletion.
 *
 * @param string $option Option name.
 * @return bool
 */
function delete_option( $option ) {
	$GLOBALS['npcink_uninstall_deleted_options'][] = (string) $option;
	return true;
}

/**
 * Reports the requested test mode.
 *
 * @return bool
 */
function is_multisite() {
	return 'multisite' === $GLOBALS['npcink_uninstall_mode'];
}

/**
 * Supplies one bounded page of test sites.
 *
 * @param array<string,mixed> $args Query arguments.
 * @return array<int,int>
 */
function get_sites( array $args ) {
	return 0 === (int) ( $args['offset'] ?? 0 ) ? array( 11, 22 ) : array();
}

/**
 * Records a blog switch.
 *
 * @param int $site_id Site id.
 * @return bool
 */
function switch_to_blog( $site_id ) {
	$GLOBALS['npcink_uninstall_switched_sites'][] = (int) $site_id;
	return true;
}

/**
 * Records a blog restore.
 *
 * @return bool
 */
function restore_current_blog() {
	++$GLOBALS['npcink_uninstall_restores'];
	return true;
}

$GLOBALS['npcink_uninstall_mode'] = $mode;
$GLOBALS['npcink_uninstall_uploads'] = $npcink_uninstall_uploads;
require dirname( __DIR__ ) . '/uninstall.php';

$expected_options = array(
	'npcink_abilities_toolkit_catalog_observability_state',
	'npcink_abilities_toolkit_read_cache_version',
	'npcink_abilities_toolkit_media_backup_cleanup_cursor',
	'npcink_abilities_toolkit_media_backup_manual_cleanup_cursor',
);
$expected_deleted = 'multisite' === $mode ? array_merge( $expected_options, $expected_options ) : $expected_options;
if ( $expected_deleted !== $GLOBALS['npcink_uninstall_deleted_options'] ) {
	fwrite( STDERR, 'Unexpected uninstall deletions: ' . json_encode( $GLOBALS['npcink_uninstall_deleted_options'] ) . "\n" );
	exit( 1 );
}
$expected_cleared_hooks = 'multisite' === $mode
	? array( 'npcink_abilities_toolkit_cleanup_media_backups', 'npcink_abilities_toolkit_cleanup_media_backups' )
	: array( 'npcink_abilities_toolkit_cleanup_media_backups' );
if ( $expected_cleared_hooks !== $GLOBALS['npcink_uninstall_cleared_hooks'] ) {
	fwrite( STDERR, "Uninstall did not clear the media backup cleanup cron.\n" );
	exit( 1 );
}
if ( 'multisite' === $mode ) {
	if ( array( 11, 22 ) !== $GLOBALS['npcink_uninstall_switched_sites'] || 2 !== $GLOBALS['npcink_uninstall_restores'] ) {
		fwrite( STDERR, "Multisite uninstall did not restore every visited site.\n" );
		exit( 1 );
	}
} elseif ( array() !== $GLOBALS['npcink_uninstall_switched_sites'] || 0 !== $GLOBALS['npcink_uninstall_restores'] ) {
	fwrite( STDERR, "Single-site uninstall unexpectedly switched sites.\n" );
	exit( 1 );
}

$expected_meta_keys = array( '_npcink_ai_media_file_replacement_history', '_npcink_ai_media_latest_file_replacement' );
if ( array() !== $GLOBALS['npcink_uninstall_deleted_meta_keys'] ) {
	fwrite( STDERR, 'Default uninstall must preserve the shared legacy media history meta: ' . json_encode( $GLOBALS['npcink_uninstall_deleted_meta_keys'] ) . "\n" );
	exit( 1 );
}
$expected_user_meta_keys = array( 'npcink_abilities_toolkit_welcome_pending', 'npcink_abilities_toolkit_health_notices_dismissed' );
$expected_user_meta_deletions = 'multisite' === $mode ? array_merge( $expected_user_meta_keys, $expected_user_meta_keys ) : $expected_user_meta_keys;
if ( $expected_user_meta_deletions !== $GLOBALS['npcink_uninstall_deleted_user_meta_keys'] ) {
	fwrite( STDERR, 'Unexpected uninstall user meta deletions: ' . json_encode( $GLOBALS['npcink_uninstall_deleted_user_meta_keys'] ) . "\n" );
	exit( 1 );
}
if ( is_dir( $npcink_uninstall_backups ) ) {
	fwrite( STDERR, "Uninstall did not remove the media backups directory.\n" );
	exit( 1 );
}
if ( ! is_file( $npcink_uninstall_uploads . '/unrelated/keep.txt' ) || is_dir( $npcink_uninstall_uploads . '/unrelated' ) === false ) {
	fwrite( STDERR, "Uninstall removed uploads content outside its backups directory.\n" );
	exit( 1 );
}
if ( $npcink_uninstall_symlinks_supported && is_link( $npcink_uninstall_backups . '/linked-outside' ) ) {
	fwrite( STDERR, "Uninstall left a symlinked backup entry behind.\n" );
	exit( 1 );
}

$npcink_uninstall_linked_root = $npcink_uninstall_backups;
@mkdir( $npcink_uninstall_uploads . '/linked-root-target', 0777, true );
file_put_contents( $npcink_uninstall_uploads . '/linked-root-target/keep.txt', 'keep' );
$npcink_uninstall_root_symlink_created = @symlink( $npcink_uninstall_uploads . '/linked-root-target', $npcink_uninstall_linked_root ) !== false;
npcink_abilities_toolkit_uninstall_remove_backups_directory();
if ( $npcink_uninstall_root_symlink_created ) {
	if ( is_link( $npcink_uninstall_linked_root ) ) {
		fwrite( STDERR, "Uninstall did not remove a symlinked backups directory link.\n" );
		exit( 1 );
	}
	if ( ! is_file( $npcink_uninstall_uploads . '/linked-root-target/keep.txt' ) ) {
		fwrite( STDERR, "Uninstall descended through a symlinked backups directory.\n" );
		exit( 1 );
	}
} elseif ( is_link( $npcink_uninstall_linked_root ) ) {
	fwrite( STDERR, "Symlink fixtures could not be created on this platform; the symlink safeguards ran against the regular tree only.\n" );
}
npcink_abilities_toolkit_uninstall_rrmdir( $npcink_uninstall_uploads . '/linked-root-target' );

$GLOBALS['npcink_uninstall_preserve_media_backups'] = true;
$GLOBALS['npcink_uninstall_preserve_media_history'] = true;
@mkdir( $npcink_uninstall_backups . '/2026/01', 0777, true );
file_put_contents( $npcink_uninstall_backups . '/2026/01/backup.jpg', 'backup-bytes' );
$npcink_uninstall_deleted_meta_keys_backup = $GLOBALS['npcink_uninstall_deleted_meta_keys'];
npcink_abilities_toolkit_uninstall_media_backup_artifacts();
if ( ! is_file( $npcink_uninstall_backups . '/2026/01/backup.jpg' ) ) {
	fwrite( STDERR, "Preserve filters did not keep host-owned media backups and history.\n" );
	exit( 1 );
}
if ( $GLOBALS['npcink_uninstall_deleted_meta_keys'] !== $npcink_uninstall_deleted_meta_keys_backup ) {
	fwrite( STDERR, "Preserve history filter did not keep the media history meta.\n" );
	exit( 1 );
}

$GLOBALS['npcink_uninstall_preserve_media_history'] = true;
$GLOBALS['npcink_uninstall_preserve_media_backups'] = false;
npcink_abilities_toolkit_uninstall_media_backup_artifacts();
if ( is_file( $npcink_uninstall_backups . '/2026/01/backup.jpg' ) || is_dir( $npcink_uninstall_backups ) ) {
	fwrite( STDERR, "History preservation must not keep backup files on disk.\n" );
	exit( 1 );
}
if ( $GLOBALS['npcink_uninstall_deleted_meta_keys'] !== $npcink_uninstall_deleted_meta_keys_backup ) {
	fwrite( STDERR, "History-only preservation still deleted the media history meta.\n" );
	exit( 1 );
}

$GLOBALS['npcink_uninstall_preserve_media_history'] = false;
$npcink_uninstall_deleted_meta_keys_optout = $GLOBALS['npcink_uninstall_deleted_meta_keys'];
npcink_abilities_toolkit_uninstall_media_backup_artifacts();
if ( array_merge( $npcink_uninstall_deleted_meta_keys_optout, $expected_meta_keys ) !== $GLOBALS['npcink_uninstall_deleted_meta_keys'] ) {
	fwrite( STDERR, "Explicit history opt-out must delete the shared legacy media history meta.\n" );
	exit( 1 );
}
unset( $GLOBALS['npcink_uninstall_preserve_media_backups'], $GLOBALS['npcink_uninstall_preserve_media_history'] );

npcink_abilities_toolkit_uninstall_rrmdir( $npcink_uninstall_uploads );

echo 'OK: uninstall cleanup (' . $mode . ")\n";
