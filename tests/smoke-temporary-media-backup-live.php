<?php
/** Opt-in installed maintenance acceptance, restricted to this run's disposable attachment. */
if ( ! defined( 'ABSPATH' ) || '1' !== getenv( 'NPCINK_TEMPORARY_BACKUP_LIVE' ) ) { throw new RuntimeException( 'Explicit installed temporary-backup smoke opt-in is required.' ); }
$users = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
wp_set_current_user( $users[0]->ID );
$categories = new \Npcink_Abilities_Toolkit\Registry\Category_Registrar();
$registrar = new \Npcink_Abilities_Toolkit\Registry\Ability_Registrar( $categories, new \Npcink_Abilities_Toolkit\Registry\Contract_Normalizer( new \Npcink_Abilities_Toolkit\Registry\Schema_Normalizer() ) );
$package = new \Npcink_Abilities_Toolkit\Packages\Core_Write_Package( $categories, $registrar );
$invoke = static function ( $method, ...$args ) use ( $package ) { $reflection = new ReflectionMethod( $package, $method ); $reflection->setAccessible( true ); return $reflection->invokeArgs( $package, $args ); };
$check = static function ( $ok, $message ) { if ( ! $ok ) { throw new RuntimeException( $message ); } echo "PASS: $message\n"; };
$id = 0; $shared_id = 0; $scope = null; $block_delete = null;
$uploads = wp_upload_dir(); $name = 'temporary-backup-smoke-' . wp_generate_uuid4();
$dir = $uploads['basedir'] . '/' . $name;
$backup_dir = $uploads['basedir'] . '/npcink-abilities-toolkit-backups/' . $name;
$option = 'npcink_temporary_backup_smoke_' . wp_generate_password( 10, false );
$cursor_name = 'npcink_abilities_toolkit_media_backup_cleanup_cursor';
$old_cursor = get_option( $cursor_name, null );
try {
	wp_mkdir_p( $dir ); wp_mkdir_p( $backup_dir );
	$path = $dir . '/source.png'; $image = imagecreatetruecolor( 640, 360 ); imagepng( $image, $path );
	$id = wp_insert_attachment( array( 'post_mime_type' => 'image/png', 'post_title' => 'Disposable temporary cleanup acceptance', 'post_status' => 'inherit' ), $path );
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $path ) );
	$source_state = $invoke( 'current_media_file_state', $id );
	$group = $invoke( 'plan_temporary_media_backup', $source_state, array( 'attachment_id' => $id, 'backup_lifecycle' => 'temporary_7_days.v1', 'expected_current_media_fingerprint' => $source_state['media_fingerprint'] ) );
	$check( is_array( $group ) && count( $group['files'] ) > 1, 'Installed snapshot captures original and generated sizes' );
	$expected_bytes = array_sum( array_column( $group['files'], 'filesize_bytes' ) );
	copy( $path, $dir . '/current.png' );
	update_attached_file( $id, $dir . '/current.png' );
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $dir . '/current.png' ) );
	copy( $path, $backup_dir . '/legacy.png' );
	$record = array( 'replacement_id' => 'temporary-installed', 'status' => 'active', 'replaced_at_gmt' => gmdate( 'c' ), 'before' => array( 'relative_file' => $name . '/source.png' ), 'backup' => array( 'relative_file' => $name . '/source.png' ), 'temporary_backup' => $group );
	$legacy = array( 'replacement_id' => 'legacy-installed', 'status' => 'active', 'replaced_at_gmt' => gmdate( 'c', time() - 8 * DAY_IN_SECONDS ), 'backup_cleanup_policy' => 'manual_confirmation_required', 'backup' => array( 'relative_file' => 'npcink-abilities-toolkit-backups/' . $name . '/legacy.png' ) );
	$save = static function ( $rows ) use ( $id ) { update_post_meta( $id, '_npcink_ai_media_file_replacement_history', $rows ); };
	$get = static function () use ( $id ) { return get_post_meta( $id, '_npcink_ai_media_file_replacement_history', true ); };
	$save( array( $record, $legacy ) );
	$scope = static function ( $query ) use ( $id ) { if ( '_npcink_ai_media_file_replacement_history' === $query->get( 'meta_key' ) && $query->get( 'npcink_abilities_toolkit_cleanup_after_id' ) !== '' ) { $query->set( 'post__in', array( $id ) ); } };
	add_action( 'pre_get_posts', $scope );
	$package->cleanup_expired_media_backups();
	$check( is_file( $path ) && $get() === array( $record, $legacy ), 'Actual maintenance leaves the active seven-day window and legacy history unchanged' );
	$record['temporary_backup']['expires_at_gmt'] = gmdate( 'c', time() - 1 );
	$later = $record; $later['replacement_id'] = 'later-installed'; $later['temporary_backup']['expires_at_gmt'] = gmdate( 'c', time() + DAY_IN_SECONDS );
	$save( array( $record, $later, $legacy ) );
	$before = $get(); $package->cleanup_expired_media_backups( false );
	$check( $get() === $before && is_file( $path ), 'Cleanup preview performs no file or metadata writes' );
	$package->cleanup_expired_media_backups();
	$check( is_file( $path ) && 'another_recovery' === $get()[0]['temporary_backup']['files'][0]['reason'], 'Continuous replacement protects a newer recovery window' );
	$save( array( $record, $legacy ) );
	$shared_id = wp_insert_attachment( array( 'post_mime_type' => 'image/png', 'post_title' => 'Disposable shared-file protection', 'post_status' => 'inherit' ), $path );
	$package->cleanup_expired_media_backups();
	$check( is_file( $path ) && 'another_attachment' === $get()[0]['temporary_backup']['files'][0]['reason'], 'Installed database scan preserves a file shared with another attachment' );
	update_attached_file( $shared_id, $dir . '/current.png' );
	update_option( $option, array( 'theme_image' => wp_get_attachment_url( $shared_id ) . '?old=' . $name . '/source.png' ), false );
	$package->cleanup_expired_media_backups();
	$check( is_file( $path ) && 'local_reference' === $get()[0]['temporary_backup']['files'][0]['reason'], 'A theme/options reference blocks old-file deletion' );
	delete_option( $option );
	$bytes = file_get_contents( $path ); file_put_contents( $path, $bytes . 'external-change' );
	$package->cleanup_expired_media_backups();
	$check( is_file( $path ) && 'file_changed' === $get()[0]['temporary_backup']['files'][0]['reason'], 'Changed old bytes are preserved for review' );
	file_put_contents( $path, $bytes );
	$block_delete = static function ( $file ) use ( $path ) { return $file === $path ? '' : $file; };
	add_filter( 'wp_delete_file', $block_delete );
	$package->cleanup_expired_media_backups();
	$check( is_file( $path ) && 'delete_failed' === $get()[0]['temporary_backup']['files'][0]['reason'], 'Deletion failure remains visible and retryable' );
	remove_filter( 'wp_delete_file', $block_delete ); $block_delete = null;
	$block_delete = static function ( $file ) use ( $path, $dir ) { return $file === $path ? $dir . '/current.png' : $file; };
	add_filter( 'wp_delete_file', $block_delete );
	$package->cleanup_expired_media_backups();
	$check( is_file( $path ) && is_file( $dir . '/current.png' ) && 'delete_failed' === $get()[0]['temporary_backup']['files'][0]['reason'], 'Delete filters cannot redirect cleanup to the current image' );
	remove_filter( 'wp_delete_file', $block_delete ); $block_delete = null;
	$package->cleanup_expired_media_backups();
	$receipt = $get()[0]['temporary_backup'];
	$check( ! is_file( $path ) && 'cleaned' === $receipt['state'] && $expected_bytes === $receipt['removed_bytes'], 'Retry removes exactly the frozen old file set and reports actual reclaimed bytes' );
	$package->cleanup_expired_media_backups();
	$check( $get()[0]['temporary_backup']['removed_bytes'] === $expected_bytes && is_file( $dir . '/current.png' ) && is_file( $backup_dir . '/legacy.png' ) && $get()[1] === $legacy, 'Repeated maintenance preserves current files and historical manual backups without double-counting' );
	$check( glob( $dir . '/source*' ) === array(), 'No old original or thumbnail files remain after successful cleanup' );
} finally {
	if ( $block_delete ) { remove_filter( 'wp_delete_file', $block_delete ); }
	if ( $scope ) { remove_action( 'pre_get_posts', $scope ); }
	delete_option( $option );
	if ( null === $old_cursor ) { delete_option( $cursor_name ); } else { update_option( $cursor_name, $old_cursor ); }
	foreach ( array( $shared_id, $id ) as $attachment_id ) { if ( $attachment_id > 0 ) { wp_delete_attachment( $attachment_id, true ); } }
	foreach ( array( $dir, $backup_dir ) as $owned ) { if ( is_dir( $owned ) ) { foreach ( glob( $owned . '/*' ) as $file ) { wp_delete_file( $file ); } rmdir( $owned ); } }
}
echo "Installed temporary media maintenance: ok\n";
