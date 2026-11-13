<?php
/** Frozen-file cleanup regressions, using real disposable files and bounded reference stubs. */
require_once __DIR__ . '/bootstrap.php';
if ( ! defined( 'DAY_IN_SECONDS' ) ) { define( 'DAY_IN_SECONDS', 86400 ); }
if ( ! function_exists( 'maybe_unserialize' ) ) { function maybe_unserialize( $value ) { return is_string( $value ) && preg_match( '/^[aObis]:/', $value ) ? unserialize( $value, array( 'allowed_classes' => false ) ) : $value; } }
if ( ! function_exists( 'remove_filter' ) ) { function remove_filter( $hook, $callback, $priority = 10 ) { foreach ( $GLOBALS['npcink_abilities_toolkit_unit_filters'][$hook] ?? array() as $key => $filter ) { if ( $filter === $callback ) { unset( $GLOBALS['npcink_abilities_toolkit_unit_filters'][$hook][$key] ); } } } }
use Npcink_Abilities_Toolkit\Packages\Core_Write_Package;
use Npcink_Abilities_Toolkit\Registry\Category_Registrar;
use Npcink_Abilities_Toolkit\Registry\Ability_Registrar;
use Npcink_Abilities_Toolkit\Registry\Contract_Normalizer;
use Npcink_Abilities_Toolkit\Registry\Schema_Normalizer;
function temporary_check( $ok, $message ) { if ( ! $ok ) { throw new RuntimeException( $message ); } echo "PASS: $message\n"; }
class Temporary_Backup_Reference_Db extends Npcink_Abilities_Toolkit_Unit_Wpdb {
	public $options = 'wp_options';
	public $last_error = '';
	public $rows = array();
	public $referenced = false;
	public $fail = false;
	public $reference_needle = '';
	public $prepared_values = array();
	public $queries = 0;
	public function prepare( $query, ...$args ) { $this->prepared_values = $args; return parent::prepare( $query, ...$args ); }
	public function esc_like( $value ) { return addcslashes( $value, '_%\\' ); }
	public function get_results( $query ) { ++$this->queries; return $this->rows; }
	public function get_var( $query ) { ++$this->queries; if ( $this->fail ) { $this->last_error = 'fixture query failure'; return null; } return $this->referenced || ( $this->reference_needle && in_array( '%' . $this->esc_like( $this->reference_needle ) . '%', $this->prepared_values, true ) ) ? 1 : null; }
}
$categories = new Category_Registrar();
$package = new Core_Write_Package( $categories, new Ability_Registrar( $categories, new Contract_Normalizer( new Schema_Normalizer() ) ) );
$invoke = static function ( $method, ...$args ) use ( $package ) { $reflection = new ReflectionMethod( $package, $method ); $reflection->setAccessible( true ); return $reflection->invokeArgs( $package, $args ); };
$uploads = wp_upload_dir();
$dir = $uploads['basedir'] . '/temporary-backup-' . bin2hex( random_bytes( 8 ) );
mkdir( $dir, 0755, true );
$relative_dir = basename( $dir );
$original_db = $GLOBALS['wpdb'];
$db = new Temporary_Backup_Reference_Db();
$GLOBALS['wpdb'] = $db;
$id = 9071;
$files = array( 'source.png', 'source-150x150.png' );
try {
	foreach ( $files as $name ) { file_put_contents( $dir . '/' . $name, 'original-' . $name ); }
	$current = array( 'relative_file' => $relative_dir . '/source.png', 'media_fingerprint' => 'sha256:' . hash_file( 'sha256', $dir . '/source.png' ), 'metadata' => array( 'file' => $relative_dir . '/source.png', 'sizes' => array( 'thumbnail' => array( 'file' => 'source-150x150.png' ) ) ) );
	$input = array( 'attachment_id' => $id, 'backup_lifecycle' => 'temporary_7_days.v1', 'expected_current_media_fingerprint' => $current['media_fingerprint'] );
	$group = $invoke( 'plan_temporary_media_backup', $current, $input );
	temporary_check( is_array( $group ) && count( $group['files'] ) === 2 && abs( strtotime( $group['expires_at_gmt'] ) - time() - 7 * DAY_IN_SECONDS ) < 3, 'Snapshot freezes original and sizes with a seven-day window' );
	temporary_check( array() === $invoke( 'plan_temporary_media_backup', $current, array() ), 'Legacy replacements receive no new lifecycle' );
	$restore_plan = array( '_restore_source_group' => $group );
	$thumbnail_bytes = file_get_contents( $dir . '/source-150x150.png' );
	file_put_contents( $dir . '/source-150x150.png', 'changed-after-restore-planning' );
	$late_error = $invoke( 'validate_media_backup_restore_precommit_state', $id, $restore_plan, array(), array() );
	temporary_check( is_wp_error( $late_error ) && 'npcink_temporary_media_backup_changed' === $late_error->get_error_code(), 'Restore precommit revalidates original thumbnails after planning' );
	file_put_contents( $dir . '/source-150x150.png', $thumbnail_bytes );
	$restore_plan['_restore_source_group']['expires_at_gmt'] = gmdate( 'c', time() - 1 );
	$late_error = $invoke( 'validate_media_backup_restore_precommit_state', $id, $restore_plan, array(), array() );
	temporary_check( is_wp_error( $late_error ) && 'npcink_temporary_media_backup_unavailable' === $late_error->get_error_code(), 'Restore precommit rechecks the selected original recovery deadline' );
	$pending = array();
	for ( $i = 0; $i < 50; ++$i ) { $pending[] = array( 'replacement_id' => 'pending-' . $i, 'temporary_backup' => $group ); }
	$GLOBALS['npcink_abilities_toolkit_unit_post_meta'][$id]['_npcink_ai_media_file_replacement_history'] = $pending;
	temporary_check( is_wp_error( $invoke( 'plan_temporary_media_backup', $current, $input ) ), 'Ordinary replacement stops at fifty pending groups' );
	temporary_check( is_array( $invoke( 'plan_temporary_media_backup', $current, $input, 'pending-0' ) ), 'Restore may replace a selected pending group at the fifty-group limit' );
	temporary_check( is_wp_error( $invoke( 'plan_temporary_media_backup', $current, $input, 'not-a-selected-group' ) ), 'An unknown recovery cannot bypass the pending-group limit' );
	unset( $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][$id] );
	$unicode = $relative_dir . '/图像.png'; file_put_contents( $dir . '/图像.png', 'unicode-image' );
	$db->reference_needle = $relative_dir . '/%E5%9B%BE%E5%83%8F.png';
	temporary_check( 'local_reference' === $invoke( 'temporary_media_reference_blocker', $id, $unicode, 'unicode-unit' ), 'Segment-encoded Unicode URL references preserve old files' );
	$db->reference_needle = str_replace( '/', '\\/', $db->reference_needle );
	temporary_check( 'local_reference' === $invoke( 'temporary_media_reference_blocker', $id, $unicode, 'unicode-unit' ), 'JSON-escaped encoded URL references preserve old files' );
	$db->reference_needle = substr( wp_json_encode( $unicode ), 1, -1 );
	temporary_check( 'local_reference' === $invoke( 'temporary_media_reference_blocker', $id, $unicode, 'unicode-unit' ), 'Default JSON Unicode escaping protects local references' );
	$db->reference_needle = str_replace( '\\/', '/', $db->reference_needle );
	temporary_check( 'local_reference' === $invoke( 'temporary_media_reference_blocker', $id, $unicode, 'unicode-unit' ), 'JSON Unicode with unescaped slashes protects local references' );
	$db->reference_needle = '';
	$bad = $input; $bad['expected_current_media_fingerprint'] = 'sha256:' . str_repeat( '0', 64 );
	temporary_check( is_wp_error( $invoke( 'plan_temporary_media_backup', $current, $bad ) ), 'Temporary retention requires exact source evidence' );
	$link = $dir . '/linked.png';
	link( $dir . '/source.png', $link );
	temporary_check( array() === $invoke( 'temporary_media_file_snapshot', $link ), 'Hardlinked files cannot enter automatic cleanup' );
	unlink( $link );
	symlink( $dir . '/source.png', $link );
	temporary_check( array() === $invoke( 'temporary_media_file_snapshot', $link ), 'Symlink files cannot enter automatic cleanup' );
	unlink( $link );
	$linked_dir = $uploads['basedir'] . '/temporary-linked-' . bin2hex( random_bytes( 4 ) );
	symlink( $dir, $linked_dir );
	try { temporary_check( array() === $invoke( 'temporary_media_file_snapshot', $linked_dir . '/source.png' ), 'Symlink parent paths cannot enter automatic cleanup' ); } finally { unlink( $linked_dir ); }
	$record = array( 'replacement_id' => 'temporary-unit', 'temporary_backup' => $group );
	$untouched = $invoke( 'cleanup_temporary_media_group', $id, $record, true );
	temporary_check( $record === $untouched && is_file( $dir . '/source.png' ), 'Cleanup respects the recovery window' );
	$record['temporary_backup']['expires_at_gmt'] = gmdate( 'c', time() - 1 );
	temporary_check( true === $invoke( 'temporary_media_group_cleanup_eligible', $record['temporary_backup'] ), 'Expired managed groups share maintenance eligibility in preview and execution' );
	temporary_check( is_wp_error( $invoke( 'validate_temporary_media_restore', $record['temporary_backup'] ) ), 'Expired group cannot be restored even before maintenance runs' );
	$db->referenced = true;
	$blocked = $invoke( 'cleanup_temporary_media_group', $id, $record, true );
	temporary_check( 'blocked' === $blocked['temporary_backup']['state'] && 'local_reference' === $blocked['temporary_backup']['files'][0]['reason'] && is_file( $dir . '/source.png' ), 'A remaining local reference preserves old files' );
	$db->referenced = false;
	$db->fail = true;
	$blocked = $invoke( 'cleanup_temporary_media_group', $id, $record, true );
	temporary_check( 'reference_check_unavailable' === $blocked['temporary_backup']['files'][0]['reason'] && is_file( $dir . '/source.png' ), 'Query failure preserves all candidate files' );
	$db->fail = false; $db->last_error = '';
	$db->rows = array( (object) array( 'post_id' => 9902, 'meta_key' => '_wp_attached_file', 'meta_value' => $relative_dir . '/source.png' ) );
	$blocked = $invoke( 'cleanup_temporary_media_group', $id, $record, true );
	temporary_check( 'another_attachment' === $blocked['temporary_backup']['files'][0]['reason'] && is_file( $dir . '/source.png' ), 'Another media item protects its shared file' );
	// Recreate the size removed in the previous partial cleanup and recapture its identity.
	file_put_contents( $dir . '/source-150x150.png', 'original-source-150x150.png' );
	$group = $invoke( 'plan_temporary_media_backup', $current, $input );
	$record['temporary_backup'] = $group;
	$record['temporary_backup']['expires_at_gmt'] = gmdate( 'c', time() - 1 );
	$db->rows = array( (object) array( 'post_id' => $id, 'meta_key' => '_npcink_ai_media_file_replacement_history', 'meta_value' => serialize( array( array( 'replacement_id' => 'later-version', 'temporary_backup' => $group ) ) ) ) );
	$blocked = $invoke( 'cleanup_temporary_media_group', $id, $record, true );
	temporary_check( 'another_recovery' === $blocked['temporary_backup']['files'][0]['reason'], 'A newer recovery window protects a shared original' );
	$db->rows = array();
	file_put_contents( $dir . '/source.png', 'changed-original-bytes' );
	$blocked = $invoke( 'cleanup_temporary_media_group', $id, $record, true );
	temporary_check( 'file_changed' === $blocked['temporary_backup']['files'][0]['reason'] && is_file( $dir . '/source.png' ), 'Changed bytes or file identity cannot be deleted' );
	$expected_removed = strlen( 'original-source-150x150.png' );
	temporary_check( $expected_removed === $blocked['temporary_backup']['removed_bytes'], 'Partial cleanup reports only actually deleted bytes' );
	$again = $invoke( 'cleanup_temporary_media_group', $id, $blocked, true );
	temporary_check( $expected_removed === $again['temporary_backup']['removed_bytes'], 'Repeated cleanup never counts reclaimed bytes twice' );
	$group = $invoke( 'plan_temporary_media_backup', array( 'relative_file' => $relative_dir . '/source.png', 'media_fingerprint' => 'updated', 'metadata' => array( 'file' => $relative_dir . '/source.png' ) ), array( 'backup_lifecycle' => 'temporary_7_days.v1', 'expected_current_media_fingerprint' => 'updated' ) );
	$record['temporary_backup'] = $group; $record['temporary_backup']['expires_at_gmt'] = gmdate( 'c', time() - 1 );
	$GLOBALS['npcink_abilities_toolkit_unit_post_meta'][$id]['_wp_attached_file'] = $relative_dir . '/source.png';
	$protected = $invoke( 'cleanup_temporary_media_group', $id, $record, true );
	temporary_check( 'current_image' === $protected['temporary_backup']['files'][0]['state'] && is_file( $dir . '/source.png' ), 'Restored current files leave cleanup scope' );
	unset( $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][$id] );
	$record['temporary_backup']['state'] = 'restored';
	temporary_check( $record === $invoke( 'cleanup_temporary_media_group', $id, $record, true ), 'Successful restore cancels the original group cleanup' );
	$history = array_fill( 0, 25, array( 'replacement_id' => 'old', 'temporary_backup' => $group ) );
	temporary_check( 25 === count( $invoke( 'retain_temporary_media_history', $history ) ), 'Pending ownership survives the legacy twenty-row history cap' );
	$budget = new ReflectionProperty( $package, 'temporary_media_files_remaining' ); $budget->setAccessible( true );
	$exhausted = new ReflectionProperty( $package, 'temporary_media_budget_exhausted' ); $exhausted->setAccessible( true );
	$many = $record; $many['temporary_backup'] = $group; $many['temporary_backup']['files'] = array(); $many['temporary_backup']['state'] = 'retained'; $many['temporary_backup']['expires_at_gmt'] = gmdate( 'c', time() - 1 );
	for ( $i = 0; $i < 40; ++$i ) {
		$relative = $relative_dir . '/budget-' . $i . '.png'; file_put_contents( $uploads['basedir'] . '/' . $relative, 'budget-image' );
		$file = $invoke( 'temporary_media_file_snapshot', $uploads['basedir'] . '/' . $relative ); $file['relative_file'] = $relative; $file['state'] = 'retained'; $many['temporary_backup']['files'][] = $file;
	}
	$budget->setValue( $package, 32 ); $db->queries = 0;
	$partial = $invoke( 'cleanup_temporary_media_group', $id, $many, true );
	temporary_check( 32 === count( array_filter( $partial['temporary_backup']['files'], static function ( $f ) { return 'removed' === $f['state']; } ) ) && $db->queries <= 384 && ! empty( $partial['temporary_backup']['cleanup_resume'] ), 'File budget bounds all reference queries and persists partial progress' );
	$budget->setValue( $package, 32 ); $exhausted->setValue( $package, false );
	$completed = $invoke( 'cleanup_temporary_media_group', $id, $partial, true );
	temporary_check( 'cleaned' === $completed['temporary_backup']['state'] && empty( $completed['temporary_backup']['cleanup_resume'] ), 'Next pass resumes remaining files without double counting' );
	$budget->setValue( $package, 1 ); $db->referenced = true;
	$many['temporary_backup']['files'] = array_slice( $many['temporary_backup']['files'], 0, 2 );
	$partial = $invoke( 'cleanup_temporary_media_group', $id, $many, true );
	temporary_check( 1 === $partial['temporary_backup']['next_file_index'], 'Blocked prefix advances progress instead of starving later files' );
	$budget->setValue( $package, null ); $db->referenced = false;
} finally {
	$GLOBALS['wpdb'] = $original_db;
	foreach ( glob( $dir . '/*' ) as $path ) { unlink( $path ); }
	rmdir( $dir );
}
echo "Temporary media backup behavior: ok\n";
