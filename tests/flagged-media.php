<?php
/** Behavioral and fault-injection regressions through the public callbacks. */
function update_option( $name, $value, $autoload = null ) {
	if ( isset( $GLOBALS['flagged_option_fault'] ) && ( $GLOBALS['flagged_option_fault'] )( $name, $value ) ) { return false; }
	$GLOBALS['npcink_abilities_toolkit_unit_options'][ $name ] = $value;
	return true;
}
function wp_slash( $value ) { return $value; }
function wp_delete_attachment( $id, $force = false ) {
	if ( ! empty( $GLOBALS['flagged_delete_fault'] ) ) { return false; }
	$post = get_post( $id );
	if ( ! $post ) { return false; }
	$files = \Npcink_Abilities_Toolkit\Support\Flagged_Media_Backup::attachment_files( (int) $id );
	if ( ! is_wp_error( $files ) ) {
		foreach ( $files as $relative ) { $path = \Npcink_Abilities_Toolkit\Support\Flagged_Media_Backup::path( $relative ); if ( is_file( $path ) ) { unlink( $path ); } }
	}
	unset( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][ $id ], $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][ $id ] );
	return $post;
}
require __DIR__ . '/bootstrap.php';
use Npcink_Abilities_Toolkit\Packages\Core_Write_Package;
use Npcink_Abilities_Toolkit\Packages\Core_Destructive_Package;
use Npcink_Abilities_Toolkit\Packages\Core_Read_Package;
use Npcink_Abilities_Toolkit\Registry\Ability_Registrar;
use Npcink_Abilities_Toolkit\Registry\Category_Registrar;
use Npcink_Abilities_Toolkit\Registry\Contract_Normalizer;
use Npcink_Abilities_Toolkit\Registry\Schema_Normalizer;
use Npcink_Abilities_Toolkit\Support\Flagged_Media_Backup as Backup;
$checks = 0;
function check_flagged( $condition, $message ) {
	global $checks;
	++$checks;
	if ( ! $condition ) { throw new RuntimeException( $message ); }
}
function flagged_fixture( $id, $name ) {
	$base = $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'];
	wp_mkdir_p( $base . '/2026/10' );
	foreach ( array( '.jpg', '-small.jpg', '-original.jpg' ) as $suffix ) { file_put_contents( $base . '/2026/10/' . $name . $suffix, $name . $suffix ); }
	wp_insert_post( array( 'ID' => $id, 'post_type' => 'attachment', 'post_status' => 'inherit', 'post_mime_type' => 'image/jpeg', 'post_title' => 'Fixture', 'post_author' => 7 ) );
	update_post_meta( $id, '_wp_attached_file', '2026/10/' . $name . '.jpg' );
	update_post_meta( $id, '_wp_attachment_metadata', array( 'file' => '2026/10/' . $name . '.jpg', 'sizes' => array( 'small' => array( 'file' => $name . '-small.jpg' ) ), 'original_image' => $name . '-original.jpg' ) );
	return array( 'attachment_id' => $id, 'deletion_policy_branch' => 'governed_with_backup', 'reason' => 'Operator reviewed fixture', 'idempotency_key' => 'fixture-' . $name, 'dry_run' => false, 'commit' => true );
}
function remove_flagged_fixture_tree( $dir ) {
	if ( is_dir( $dir ) && ! is_link( $dir ) ) { foreach ( scandir( $dir ) as $name ) { if ( '.' !== $name && '..' !== $name ) { remove_flagged_fixture_tree( $dir . '/' . $name ); } } rmdir( $dir ); }
	elseif ( file_exists( $dir ) || is_link( $dir ) ) { unlink( $dir ); }
}
$base = sys_get_temp_dir() . '/npcink-flagged-test-' . bin2hex( random_bytes( 5 ) );
$GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] = $base;
$categories = new Category_Registrar();
$registrar = new Ability_Registrar( $categories, new Contract_Normalizer( new Schema_Normalizer() ) );
$write = new Core_Write_Package( $categories, $registrar );
$delete = new Core_Destructive_Package( $categories, $registrar );
$read = new Core_Read_Package( $categories, $registrar );
try {
	$input = flagged_fixture( 987, 'first' );
	$preview = $delete->delete_attachment( array_merge( $input, array( 'dry_run' => true, 'commit' => false ) ) );
	check_flagged( ! is_wp_error( $preview ) && false === $preview['deleted'], 'delete preview is read-only' );
	check_flagged( empty( Backup::records() ) && ! is_dir( $base . '/' . Backup::PREFIX ), 'preview creates no backup artifacts' );
	check_flagged( false === $preview['preview']['referenced_by']['scan_complete'], 'bounded reference scan cannot claim exhaustive discovery' );
	$plan_input = array( 'attachment_id' => 987, 'deletion_policy_branch' => 'governed_with_backup', 'reason' => 'Operator reviewed fixture', 'deletion_request_key' => 'plan-fixture' );
	$plan = $read->build_flagged_media_removal_plan( $plan_input );
	check_flagged( ! is_wp_error( $plan ) && 'flagged_media_removal_plan.v1' === $plan['data']['contract_version'] && $plan['data']['batch_approval'], 'planning entry emits the Core batch contract' );
	check_flagged( empty( Backup::records() ) && get_post( 987 ) && 1 === count( $plan['data']['write_actions'] ), 'planning emits one preview delete without backup or mutation' );
	$illegal_plan_input = array_merge( $plan_input, array( 'deletion_policy_branch' => 'illegal_content_no_backup' ) );
	check_flagged( is_wp_error( $read->build_flagged_media_removal_plan( $illegal_plan_input ) ), 'illegal planning requires explicit operator confirmation' );
	$illegal_plan = $read->build_flagged_media_removal_plan( array_merge( $illegal_plan_input, array( 'no_backup_confirmed' => true ) ) );
	check_flagged( ! is_wp_error( $illegal_plan ) && isset( $illegal_plan['data']['flagged_media_removal']['no_backup_attestation'] ) && ! isset( $illegal_plan['data']['flagged_media_removal']['backup_evidence'] ), 'illegal planning carries only the no-backup branch evidence' );
	$url = wp_get_attachment_url( 987 );
	wp_insert_post( array( 'ID' => 990, 'post_type' => 'post', 'post_status' => 'draft', 'post_content' => 'Reference ' . $url ) );
	$patch = array( 'target_ability_id' => 'npcink-abilities-toolkit/patch-post-content', 'post_id' => 990, 'operations' => array( array( 'op' => 'replace', 'find' => $url, 'replace' => '' ) ) );
	$patched_plan = $read->build_flagged_media_removal_plan( array_merge( $plan_input, array( 'reference_patches' => array( $patch ) ) ) );
	check_flagged( ! is_wp_error( $patched_plan ) && 2 === count( $patched_plan['data']['write_actions'] ) && 'delete_attachment' === $patched_plan['data']['write_actions'][1]['action_id'] && 'Reference ' . $url === get_post( 990 )->post_content, 'reviewed patches precede deletion and planning never mutates content' );
	$patch['operations'][0]['find'] = 'https://unrelated.example/image.jpg';
	check_flagged( is_wp_error( $read->build_flagged_media_removal_plan( array_merge( $plan_input, array( 'reference_patches' => array( $patch ) ) ) ) ), 'planning rejects unrelated URL removal' );
	check_flagged( is_wp_error( $read->build_flagged_media_removal_plan( array_merge( $plan_input, array( 'reference_patches' => array_fill( 0, 11, $patch ) ) ) ) ), 'planning bounds reference actions at ten' );

	check_flagged( is_wp_error( $delete->delete_attachment( $input ) ), 'delete commit requires host authority' );
	$GLOBALS['npcink_ai_runtime_wp_ability_context']['context'] = array( 'approval_commit_authorized' => true );
	$GLOBALS['flagged_option_fault'] = static function ( $name, $value ) { return Backup::OPTION === $name; };
	$result = $delete->delete_attachment( $input );
	check_flagged( is_wp_error( $result ) && get_post( 987 ) && is_file( $base . '/2026/10/first.jpg' ), 'manifest failure preserves original attachment and file' );
	unset( $GLOBALS['flagged_option_fault'] );
	$result = $delete->delete_attachment( $input );
	check_flagged( ! is_wp_error( $result ) && $result['deleted'] && ! get_post( 987 ), 'governed commit actually deletes attachment' );
	$backup_id = $result['backup_id'];
	check_flagged( $backup_id === $result['backup_evidence']['backup_id'], 'commit returns usable backup lookup evidence' );
	check_flagged( 3 === count( Backup::records()[ $backup_id ]['files'] ) && ! is_file( $base . '/2026/10/first-small.jpg' ), 'backup includes derivatives removed by WordPress' );
	$restore = array( 'attachment_id' => 987, 'backup_id' => $backup_id, 'dry_run' => false, 'commit' => true );
	$wrong_entry = $write->replace_media_file( $restore );
	check_flagged( is_wp_error( $wrong_entry ) && ! get_post( 987 ), 'replace entry cannot recreate deleted attachments' );
	$GLOBALS['npcink_abilities_toolkit_unit_current_user_caps'] = array( 'upload_files' => true, 'manage_options' => false );
	check_flagged( is_wp_error( $write->restore_media_backup( $restore ) ), 'recreation cannot bypass administrator authority' );
	unset( $GLOBALS['npcink_abilities_toolkit_unit_current_user_caps'] );
	$dry = $write->restore_media_backup( array_merge( $restore, array( 'dry_run' => true, 'commit' => false ) ) );
	check_flagged( ! is_wp_error( $dry ) && $dry['dry_run'] && false === $dry['restored'] && isset( $dry['rolled_back'], $dry['original_preserved'] ), 'public restore entry previews deleted attachment with schema-required fields' );
	check_flagged( ! is_file( $base . '/2026/10/first.jpg' ), 'restore preview does not copy files' );
	file_put_contents( $base . '/2026/10/first.jpg', 'unrelated-owner' );
	$conflict = $write->restore_media_backup( array_merge( $restore, array( 'target_conflict_mode' => 'overwrite' ) ) );
	check_flagged( is_wp_error( $conflict ) && 'unrelated-owner' === file_get_contents( $base . '/2026/10/first.jpg' ), 'deleted restore refuses overwrite even when requested' );
	unlink( $base . '/2026/10/first.jpg' );
	$copy_path = Backup::path( Backup::PREFIX . $backup_id . '/0.bin' );
	$bytes = file_get_contents( $copy_path );
	file_put_contents( $copy_path, 'corrupt' );
	check_flagged( is_wp_error( $write->restore_media_backup( $restore ) ), 'corrupt backup refuses restore before mutation' );
	file_put_contents( $copy_path, $bytes );
	$outside = $base . '-outside'; file_put_contents( $outside, $bytes ); unlink( $copy_path ); symlink( $outside, $copy_path );
	check_flagged( is_wp_error( $write->restore_media_backup( $restore ) ), 'symlink backup file is rejected' );
	unlink( $copy_path ); file_put_contents( $copy_path, $bytes ); unlink( $outside );
	$GLOBALS['npcink_abilities_toolkit_unit_update_post_meta_callback'] = static function ( $id, $key, $value ) { return '_wp_attached_file' === $key ? false : null; };
	$failed = $write->restore_media_backup( $restore );
	check_flagged( is_wp_error( $failed ) && true === $failed->get_error_data()['rolled_back'] && ! is_file( $base . '/2026/10/first.jpg' ), 'metadata failure compensates new files/post and preserves backup' );
	unset( $GLOBALS['npcink_abilities_toolkit_unit_update_post_meta_callback'] );
	$GLOBALS['flagged_option_fault'] = static function ( $name, $value ) { foreach ( (array) $value as $record ) { if ( is_array( $record ) && 'restored' === ( $record['state'] ?? '' ) ) { return true; } } return false; };
	$failed = $write->restore_media_backup( $restore );
	check_flagged( is_wp_error( $failed ) && $failed->get_error_data()['rolled_back'], 'result persistence failure compensates rather than reporting success' );
	unset( $GLOBALS['flagged_option_fault'] );
	$restored = $write->restore_media_backup( $restore );
	check_flagged( ! is_wp_error( $restored ) && $restored['restored'] && $restored['recreated'] && $restored['attachment_id'] !== 987, 'restore creates a new attachment through the real public entry' );
	check_flagged( 'first-small.jpg' === file_get_contents( $base . '/2026/10/first-small.jpg' ) && 'first-original.jpg' === file_get_contents( $base . '/2026/10/first-original.jpg' ), 'restore copies every derivative exactly' );
	check_flagged( '2026/10/first.jpg' === get_post_meta( $restored['attachment_id'], '_wp_attached_file', true ), 'new attachment metadata points to restored file' );
	$again = $write->restore_media_backup( $restore );
	check_flagged( ! is_wp_error( $again ) && $again['idempotent_replay'] && $again['attachment_id'] === $restored['attachment_id'], 'restore replay creates no duplicate attachment' );
	file_put_contents( $base . '/2026/10/first-small.jpg', 'drift' );
	check_flagged( is_wp_error( $write->restore_media_backup( $restore ) ), 'replay checks current files instead of blindly claiming success' );
	$illegal = flagged_fixture( 988, 'illegal' ); $illegal['deletion_policy_branch'] = 'illegal_content_no_backup';
	$before = Backup::records();
	$removed = $delete->delete_attachment( $illegal );
	check_flagged( ! is_wp_error( $removed ) && $removed['deleted'] && $before === Backup::records() && ! isset( $removed['backup_id'] ), 'illegal branch creates no backup or retained content record' );
	$records = Backup::records(); $records[ $backup_id ]['expires_at'] = time() - 1; Backup::persist( $records );
	check_flagged( is_wp_error( $write->restore_media_backup( $restore ) ), 'expired backups refuse restore before cleanup runs' );
	$cleanup = $write->cleanup_expired_media_backups( false );
	check_flagged( $cleanup['expired'] >= 1 && is_file( $copy_path ), 'existing cleanup preview includes retained deletion backups' );
	$cleanup = $write->cleanup_expired_media_backups();
	check_flagged( $cleanup['removed'] >= 1 && empty( Backup::records() ) && ! is_file( $copy_path ), 'existing maintenance expires files and manifests' );
	check_flagged( is_file( $base . '/2026/10/first.jpg' ), 'cleanup never removes restored current media' );
	$lock = fopen( Backup::path( Backup::PREFIX . '.lock' ), 'c+b' ); flock( $lock, LOCK_EX );
	check_flagged( is_wp_error( Backup::locked( static function () { return true; } ) ), 'concurrent backup operation fails closed' );
	flock( $lock, LOCK_UN ); fclose( $lock );
	echo "OK: {$checks} flagged-media behavioral checks\n";
} finally { remove_flagged_fixture_tree( $base ); }
