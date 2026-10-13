<?php
/** Public Abilities API delete/restore smoke. Run only against disposable fixtures. */
use Npcink_Abilities_Toolkit\Support\Flagged_Media_Backup as Backup;
if ( ! defined( 'ABSPATH' ) || ! function_exists( 'wp_get_ability' ) ) { throw new RuntimeException( 'WordPress Abilities API is required.' ); }
$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
if ( empty( $admins ) ) { throw new RuntimeException( 'An administrator fixture actor is required.' ); }
wp_set_current_user( (int) $admins[0] );
$delete = wp_get_ability( 'npcink-abilities-toolkit/delete-attachment' );
$restore = wp_get_ability( 'npcink-abilities-toolkit/restore-media-backup' );
$builder = wp_get_ability( 'npcink-abilities-toolkit/build-flagged-media-removal-plan' );
if ( ! $delete || ! $restore || ! $builder ) { throw new RuntimeException( 'Flagged-media abilities are not registered.' ); }
$checks = 0;
$check = static function ( $condition, $message ) use ( &$checks ) { ++$checks; if ( ! $condition ) { throw new RuntimeException( $message ); } };
$ids = array(); $paths = array(); $backup_ids = array();
$run = 'npcink-flagged-smoke-' . bin2hex( random_bytes( 6 ) );
$upload = wp_upload_dir();
$dir = $upload['basedir'] . '/' . $run; wp_mkdir_p( $dir );
try {
	foreach ( array( 'governed_with_backup', 'illegal_content_no_backup' ) as $branch ) {
		$main = $run . '/' . $branch . '.jpg'; $thumb = $branch . '-small.jpg'; $original = $branch . '-original.jpg';
		foreach ( array( $main, $run . '/' . $thumb, $run . '/' . $original ) as $relative ) { $paths[] = $upload['basedir'] . '/' . $relative; file_put_contents( end( $paths ), 'fixture ' . $relative ); }
		$post = array( 'post_title' => 'Smoke "quoted" \\ title', 'post_content' => 'Fixture \\ content', 'post_excerpt' => 'Caption', 'post_mime_type' => 'image/jpeg' );
		$id = wp_insert_attachment( wp_slash( $post ), $upload['basedir'] . '/' . $main, 0, true );
		$check( ! is_wp_error( $id ) && $id > 0, 'fixture attachment inserted' ); $ids[] = $id;
		$metadata = array( 'file' => $main, 'width' => 20, 'height' => 20, 'sizes' => array( 'thumbnail' => array( 'file' => $thumb, 'width' => 10, 'height' => 10, 'mime-type' => 'image/jpeg' ) ), 'original_image' => $original );
		wp_update_attachment_metadata( $id, $metadata ); update_post_meta( $id, '_wp_attachment_image_alt', 'Fixture \\ ALT' );
		$alt = get_post_meta( $id, '_wp_attachment_image_alt', true );
		$input = array( 'attachment_id' => $id, 'deletion_policy_branch' => $branch, 'reason' => 'Reviewed smoke fixture only', 'idempotency_key' => $run . '-' . $branch );
		$before = Backup::records();
		$plan_input = $input;
		$plan_input['deletion_request_key'] = $plan_input['idempotency_key']; unset( $plan_input['idempotency_key'] );
		$plan_input['no_backup_confirmed'] = 'illegal_content_no_backup' === $branch;
		$plan = $builder->execute( $plan_input );
		$check( ! is_wp_error( $plan ) && $plan['data']['batch_approval'] && $plan['data']['dry_run'], 'registered builder produces manual batch without mutation' );
		if ( class_exists( '\Npcink\GovernanceCore\Governance\Plan_Contract_Validator' ) ) {
			$validator = new \Npcink\GovernanceCore\Governance\Plan_Contract_Validator();
			$check( true === $validator->validate( 'npcink-abilities-toolkit/build-flagged-media-removal-plan', $plan['data'] ), 'actual Core validator accepts Toolkit branch plan' );
		}

		$preview = $delete->execute( $input );
		$check( ! is_wp_error( $preview ) && $preview['dry_run'] && ! $preview['deleted'], 'registered delete ability defaults to preview' );
		$check( $before === Backup::records() && is_file( $upload['basedir'] . '/' . $main ), 'preview preserves original and backup state' );
		$GLOBALS['npcink_ai_runtime_wp_ability_context']['context'] = array( 'approval_commit_authorized' => true );
		$removed = $delete->execute( array_merge( $input, array( 'dry_run' => false, 'commit' => true ) ) );
		$check( ! is_wp_error( $removed ) && $removed['deleted'] && ! get_post( $id ), 'registered callback deletes fixture' );
		$check( ! is_file( $upload['basedir'] . '/' . $main ) && ! is_file( $dir . '/' . $thumb ) && ! is_file( $dir . '/' . $original ), 'WordPress removes main, subsize and original files' );
		if ( 'illegal_content_no_backup' === $branch ) { $check( $before === Backup::records() && ! isset( $removed['backup_id'] ), 'illegal branch leaves no newly retained backup evidence' ); continue; }
		$backup_id = $removed['backup_id']; $backup_ids[] = $backup_id;
		$restore_input = array( 'attachment_id' => $id, 'backup_id' => $backup_id );
		$dry = $restore->execute( $restore_input );
		$check( ! is_wp_error( $dry ) && $dry['dry_run'] && ! $dry['restored'], 'registered restore previews deleted attachment' );
		file_put_contents( $upload['basedir'] . '/' . $main, 'conflict' );
		$conflict = $restore->execute( array_merge( $restore_input, array( 'dry_run' => false, 'commit' => true ) ) );
		$check( is_wp_error( $conflict ) && 'conflict' === file_get_contents( $upload['basedir'] . '/' . $main ), 'restore refuses existing unrelated file' ); unlink( $upload['basedir'] . '/' . $main );
		$restored = $restore->execute( array_merge( $restore_input, array( 'dry_run' => false, 'commit' => true ) ) );
		$check( ! is_wp_error( $restored ) && $restored['restored'] && $restored['recreated'], 'registered restore recreates deleted attachment' );
		$new_id = $restored['attachment_id']; $ids[] = $new_id;
		$check( $new_id !== $id && $metadata === wp_get_attachment_metadata( $new_id ), 'new ID and complete original metadata' );
		$check( $post['post_title'] === get_post( $new_id )->post_title && $post['post_content'] === get_post( $new_id )->post_content && $alt === get_post_meta( $new_id, '_wp_attachment_image_alt', true ), 'WordPress escaping round trip preserves original text' );
		foreach ( array( $main, $run . '/' . $thumb, $run . '/' . $original ) as $relative ) { $check( 'fixture ' . $relative === file_get_contents( $upload['basedir'] . '/' . $relative ), 'all attachment bytes restored' ); }
		$replay = $restore->execute( array_merge( $restore_input, array( 'dry_run' => false, 'commit' => true ) ) );
		$check( ! is_wp_error( $replay ) && $replay['idempotent_replay'] && $new_id === $replay['attachment_id'], 'registered restore replay creates no duplicate' );
	}
} finally {
	unset( $GLOBALS['npcink_ai_runtime_wp_ability_context'] );
	foreach ( $ids as $id ) { if ( get_post( $id ) ) { wp_delete_attachment( $id, true ); } }
	foreach ( $paths as $path ) { if ( is_file( $path ) ) { unlink( $path ); } }
	Backup::locked( static function () use ( $backup_ids ) { $records = Backup::records(); foreach ( $backup_ids as $id ) { if ( isset( $records[ $id ] ) ) { Backup::remove_files( $id, $records[ $id ]['files'] ); unset( $records[ $id ] ); } } Backup::persist( $records ); } );
	if ( is_dir( $dir ) ) { rmdir( $dir ); }
}
echo "OK: {$checks} real WordPress flagged-media checks; source=" . realpath( NPCINK_ABILITIES_TOOLKIT_FILE ) . "\n";
