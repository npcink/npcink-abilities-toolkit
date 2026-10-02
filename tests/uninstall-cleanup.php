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
$GLOBALS['npcink_uninstall_preserve_media_backups'] = false;

$npcink_uninstall_uploads = sys_get_temp_dir() . '/npcink-uninstall-test-' . getmypid();
$npcink_uninstall_backups = $npcink_uninstall_uploads . '/npcink-abilities-toolkit-backups';
@mkdir( $npcink_uninstall_backups . '/2026/01', 0777, true );
@mkdir( $npcink_uninstall_uploads . '/unrelated', 0777, true );
file_put_contents( $npcink_uninstall_backups . '/2026/01/backup.jpg', 'backup-bytes' );
file_put_contents( $npcink_uninstall_backups . '/top.png', 'backup-bytes' );
file_put_contents( $npcink_uninstall_uploads . '/unrelated/keep.txt', 'keep' );

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
 * Returns the isolated test uploads directory.
 *
 * @return array<string,mixed>
 */
function wp_upload_dir() {
	return array( 'basedir' => $GLOBALS['npcink_uninstall_uploads'] );
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
$expected_meta_deletions = 'multisite' === $mode ? array_merge( $expected_meta_keys, $expected_meta_keys ) : $expected_meta_keys;
if ( $expected_meta_deletions !== $GLOBALS['npcink_uninstall_deleted_meta_keys'] ) {
	fwrite( STDERR, 'Unexpected uninstall meta deletions: ' . json_encode( $GLOBALS['npcink_uninstall_deleted_meta_keys'] ) . "\n" );
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

$GLOBALS['npcink_uninstall_preserve_media_backups'] = true;
@mkdir( $npcink_uninstall_backups . '/2026/01', 0777, true );
file_put_contents( $npcink_uninstall_backups . '/2026/01/backup.jpg', 'backup-bytes' );
npcink_abilities_toolkit_uninstall_media_backup_artifacts();
if ( ! is_file( $npcink_uninstall_backups . '/2026/01/backup.jpg' ) ) {
	fwrite( STDERR, "Preserve filter did not keep host-owned media backups.\n" );
	exit( 1 );
}

npcink_abilities_toolkit_uninstall_rrmdir( $npcink_uninstall_uploads );

echo 'OK: uninstall cleanup (' . $mode . ")\n";
