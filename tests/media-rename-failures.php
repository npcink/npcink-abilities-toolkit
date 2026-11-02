<?php
/** Rename failures exercise actual file operations and stateful API doubles. */
require __DIR__ . '/bootstrap.php';
use Npcink_Abilities_Toolkit\Registry\{Category_Registrar, Ability_Registrar, Contract_Normalizer, Schema_Normalizer};
use Npcink_Abilities_Toolkit\Packages\Core_Write_Package;
$c = new Category_Registrar();
$w = new Core_Write_Package( $c, new Ability_Registrar( $c, new Contract_Normalizer( new Schema_Normalizer() ) ) );
$check = static function ( $ok, $message ) { if ( ! $ok ) { throw new RuntimeException( $message ); } };
$root = sys_get_temp_dir() . '/npcink-rename-failures-' . bin2hex( random_bytes( 8 ) );
mkdir( $root, 0755, true );
$GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] = $root;
$GLOBALS['npcink_ai_runtime_wp_ability_context']['context']['approval_commit_authorized'] = true;
try {
	foreach ( array( 'mime', 'pointer', 'metadata', 'history', 'latest_history', 'rollback' ) as $failure ) {
		mkdir( $root . '/' . $failure );
		$old = $failure . '/old.jpg';
		$new = $failure . '/new.jpg';
		file_put_contents( $root . '/' . $old, 'original media bytes' );
		$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][87] = (object) array( 'ID' => 87, 'post_type' => 'attachment', 'post_status' => 'inherit', 'post_mime_type' => 'image/jpeg' );
		$GLOBALS['npcink_abilities_toolkit_unit_post_meta'][87] = array( '_wp_attached_file' => $old, '_wp_attachment_metadata' => array( 'file' => $old, 'sizes' => array(), 'width' => 20, 'height' => 20 ) );
		$failed = false;
		$history_writes = 0;
		$GLOBALS['npcink_abilities_toolkit_unit_wp_update_post_callback'] = static function () use ( $failure, &$failed ) {
			if ( in_array( $failure, array( 'mime', 'rollback' ), true ) && ! $failed ) {
				$failed = true;
				return new WP_Error( 'mime_failed', 'Injected MIME failure' );
			}
			return null;
		};
		$GLOBALS['npcink_abilities_toolkit_unit_update_post_meta_callback'] = static function ( $id, $key ) use ( $failure, &$failed, &$history_writes ) {
			if ( '_npcink_ai_media_file_replacement_history' === $key ) { ++$history_writes; }
			if ( ! $failed && ( ( 'pointer' === $failure && '_wp_attached_file' === $key ) || ( 'metadata' === $failure && '_wp_attachment_metadata' === $key ) || ( 'history' === $failure && '_npcink_ai_media_file_replacement_history' === $key && 2 === $history_writes ) || ( 'latest_history' === $failure && '_npcink_ai_media_latest_file_replacement' === $key && 2 === $history_writes ) ) ) {
				$failed = true;
				return false;
			}
			return null;
		};
		$block_rollback = static function ( $blocked, $source, $target, $context ) use ( $failure ) { return $blocked || ( 'rollback' === $failure && 'rollback_rename' === ( $context['step'] ?? '' ) ); };
		add_filter( 'npcink_abilities_toolkit_media_file_copy_blocked', $block_rollback, 10, 4 );
		$result = $w->rename_media_file( array( 'attachment_id' => 87, 'target_file_name' => 'new.jpg', 'commit' => true ) );
		remove_all_filters( 'npcink_abilities_toolkit_media_file_copy_blocked' );
		$check( is_wp_error( $result ), 'rename reports ' . $failure );
		$data = $result->get_error_data();
		$check( is_readable( $root . '/' . $data['backup']['relative_file'] ), 'error contains a usable backup for ' . $failure );
		$history = get_post_meta( 87, '_npcink_ai_media_file_replacement_history', true );
		$check( is_array( $history ) && $history[0]['replacement_id'] === $data['replacement_id'], 'recovery is discoverable after ' . $failure );
		$check( get_post_meta( 87, '_npcink_ai_media_latest_file_replacement', true ) === $history[0], 'latest record follows recovered history after ' . $failure );
		if ( 'rollback' === $failure ) {
			$check( $data['recovery_required'] && ! $data['rolled_back'], 'failed compensation is explicit' );
		} else {
			$check( $data['rolled_back'] && is_readable( $root . '/' . $old ) && ! file_exists( $root . '/' . $new ), 'file rollback after ' . $failure );
			$check( $old === get_post_meta( 87, '_wp_attached_file', true ) && $old === wp_get_attachment_metadata( 87 )['file'], 'metadata rollback after ' . $failure );
		}
		unset( $GLOBALS['npcink_abilities_toolkit_unit_wp_update_post_callback'], $GLOBALS['npcink_abilities_toolkit_unit_update_post_meta_callback'] );
	}
	$source = $root . '/race-source.jpg';
	$target = $root . '/race-target.jpg';
	file_put_contents( $source, 'source bytes' );
	add_filter( 'npcink_abilities_toolkit_media_file_copy_blocked', static function ( $blocked, $from, $to ) {
		file_put_contents( $to, 'concurrent owner bytes' );
		return false;
	}, 10, 3 );
	$method = new ReflectionMethod( $w, 'move_media_file' );
	$method->setAccessible( true );
	$check( ! $method->invoke( $w, $source, $target, array() ), 'exclusive creation rejects an interleaved target' );
	$check( file_get_contents( $target ) === 'concurrent owner bytes' && file_exists( $source ), 'conflict preserves both owners files' );
} finally {
	$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
	foreach ( $files as $file ) { $file->isDir() ? rmdir( $file->getPathname() ) : unlink( $file->getPathname() ); }
	rmdir( $root );
}
echo "OK: media rename compensation and target conflicts\n";
