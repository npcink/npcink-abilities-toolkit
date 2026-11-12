<?php
/** Real filesystem tests for source-preserving imports with stateful WP doubles. */
function wp_upload_dir() {
	$root = $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'];
	return array( 'basedir' => $root, 'path' => $root, 'baseurl' => 'https://example.test/uploads', 'url' => 'https://example.test/uploads', 'error' => false );
}
function get_attached_file( $id ) { return $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] . '/' . get_post_meta( $id, '_wp_attached_file', true ); }
function wp_generate_uuid4() { return bin2hex( random_bytes( 16 ) ); }
function remove_filter( $name, $callback, $priority = 10 ) { remove_all_filters( $name ); }
function wp_update_attachment_metadata( $id, $metadata ) { return update_post_meta( $id, '_wp_attachment_metadata', $metadata ); }
function wp_delete_attachment( $id, $force = false ) {
	if ( ! empty( $GLOBALS['import_delete_blocked'] ) ) { return false; }
	unset( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][ $id ], $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][ $id ] );
	return (object) array( 'ID' => $id );
}
function get_posts( $args = array() ) {
	$found = array();
	foreach ( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'] as $id => $post ) {
		if ( 'attachment' === $post->post_type && get_post_meta( $id, $args['meta_key'], true ) === $args['meta_value'] ) { $found[] = $id; }
	}
	return array_slice( $found, 0, 1 );
}
function add_post_meta( $id, $key, $value, $unique = false ) {
	if ( $unique && metadata_exists( 'post', $id, $key ) ) { return false; }
	return update_post_meta( $id, $key, $value );
}
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/fixtures/cloud-artifact-functions.php';
use Npcink_Abilities_Toolkit\Registry\{Category_Registrar, Ability_Registrar, Contract_Normalizer, Schema_Normalizer};
use Npcink_Abilities_Toolkit\Packages\Core_Write_Package;
$c = new Category_Registrar();
$w = new Core_Write_Package( $c, new Ability_Registrar( $c, new Contract_Normalizer( new Schema_Normalizer() ) ) );
$check = static function ( $ok, $message ) { if ( ! $ok ) { throw new RuntimeException( $message ); } echo "PASS: $message\n"; };
$root = sys_get_temp_dir() . '/npcink-import-' . bin2hex( random_bytes( 8 ) );
mkdir( $root );
$GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] = $root;
// A real decodable one-pixel PNG; transfer facts must agree with these bytes.
$bytes = base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j2ioAAAAASUVORK5CYII=' );
file_put_contents( $root . '/source.png', $bytes );
file_put_contents( $root . '/source-size.png', 'unchanged intermediate' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][87] = (object) array( 'ID' => 87, 'post_type' => 'attachment', 'post_status' => 'inherit', 'post_mime_type' => 'image/png' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][88] = (object) array( 'ID' => 88, 'post_type' => 'post', 'post_status' => 'publish', 'post_content' => '<img src="source.png" class="wp-image-87">' );
$GLOBALS['npcink_abilities_toolkit_unit_post_meta'][87] = array( '_wp_attached_file' => 'source.png', '_wp_attachment_metadata' => array( 'file' => 'source.png', 'sizes' => array( 'thumbnail' => array( 'file' => 'source-size.png' ) ) ) );
$before_meta = $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][87];
$before_post = serialize( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][88] );
$artifact = npcink_abilities_toolkit_cloud_artifact_fixture( array( 'mime_type' => 'image/png', 'format' => 'png', 'width' => 1, 'height' => 1, 'filesize_bytes' => strlen( $bytes ), 'sha256' => hash( 'sha256', $bytes ), 'suggested_filename' => 'processed.png' ) );
$artifact['transform_facts']['source_checksum'] = 'sha256:' . hash( 'sha256', $bytes );
$input = array( 'source_attachment_id' => 87, 'expected_source_media_fingerprint' => $artifact['transform_facts']['source_checksum'], 'derivative_artifact' => $artifact );
$receives = 0;
$GLOBALS['npcink_abilities_toolkit_unit_cloud_artifact_receive_callback'] = static function ( $descriptor ) use ( $bytes, &$receives ) { ++$receives; return npcink_abilities_toolkit_cloud_receive_fixture( $descriptor, $bytes ); };
$GLOBALS['npcink_abilities_toolkit_unit_generate_attachment_metadata_callback'] = static function ( $id, $file ) {
	file_put_contents( dirname( $file ) . '/result-1x1.png', 'generated size' );
	return array( 'width' => 1, 'height' => 1, 'file' => basename( dirname( $file ) ) . '/' . basename( $file ), 'sizes' => array( 'thumbnail' => array( 'file' => 'result-1x1.png' ) ) );
};
try {
	$preview = $w->import_cloud_media_derivative( $input );
	$check( ! is_wp_error( $preview ) && $preview['dry_run'] && 0 === $receives && 0 === count( glob( $root . '/npcink-processed-*' ) ), 'preview has no transfer or writes: ' . ( is_wp_error( $preview ) ? $preview->get_error_code() : 'ok' ) );
	$blocked = $w->import_cloud_media_derivative( $input + array( 'commit' => true ) );
	$check( is_wp_error( $blocked ) && 'npcink_abilities_toolkit_host_approval_required' === $blocked->get_error_code() && 0 === $receives, 'commit requires host approval before transfer' );
	$GLOBALS['npcink_ai_runtime_wp_ability_context']['context']['approval_commit_authorized'] = true;
	$other_lock = fopen( $root . '/.npcink-media-import.lock', 'c+b' );
	flock( $other_lock, LOCK_EX | LOCK_NB );
	$busy = $w->import_cloud_media_derivative( $input + array( 'commit' => true ) );
	$check( is_wp_error( $busy ) && 'npcink_abilities_toolkit_cloud_import_busy' === $busy->get_error_code() && 0 === $receives, 'concurrent save fails before transfer or new files' );
	flock( $other_lock, LOCK_UN );
	fclose( $other_lock );
	$result = $w->import_cloud_media_derivative( $input + array( 'commit' => true ) );
	$check( ! is_wp_error( $result ), 'approved verified result creates new media: ' . ( is_wp_error( $result ) ? $result->get_error_code() : 'ok' ) );
	$id = $result['attachment_id'];
	$check( 87 !== $id && $result['created'] && file_get_contents( get_attached_file( $id ) ) === $bytes && wp_get_attachment_metadata( $id )['width'] === 1, 'new attachment owns reviewed bytes and image metadata' );
	$again = $w->import_cloud_media_derivative( $input + array( 'commit' => true ) );
	$check( $again['attachment_id'] === $id && $again['idempotent_replay'] && 1 === $receives && 1 === count( glob( $root . '/npcink-processed-*' ) ), 'repeat save returns same attachment without transfer or duplicates' );
	$bad = $input; $bad['derivative_artifact']['transform_facts']['source_checksum'] = 'sha256:' . str_repeat( '0', 64 );
	$check( is_wp_error( $w->import_cloud_media_derivative( $bad + array( 'commit' => true ) ) ), 'wrong source binding is rejected' );
	file_put_contents( $root . '/source.png', 'changed' );
	$check( is_wp_error( $w->import_cloud_media_derivative( $input + array( 'commit' => true ) ) ), 'source drift is rejected' );
	file_put_contents( $root . '/source.png', $bytes );
	foreach ( array( 'expired', 'checksum', 'metadata', 'exception', 'provenance', 'cleanup' ) as $failure ) {
		$case = $input;
		$case['derivative_artifact']['artifact_id'] = 'art_' . md5( $failure );
		if ( 'expired' === $failure ) { $case['derivative_artifact']['expires_at'] = gmdate( 'c', time() - 60 ); }
		if ( 'checksum' === $failure ) { $GLOBALS['npcink_abilities_toolkit_unit_cloud_artifact_receive_callback'] = static fn( $a ) => npcink_abilities_toolkit_cloud_receive_fixture( $a, 'corrupt' ); }
		if ( in_array( $failure, array( 'metadata', 'cleanup' ), true ) ) { $GLOBALS['npcink_abilities_toolkit_unit_generate_attachment_metadata_callback'] = static fn() => array(); }
		if ( 'exception' === $failure ) { $GLOBALS['npcink_abilities_toolkit_unit_generate_attachment_metadata_callback'] = static function () { throw new RuntimeException( 'fixture metadata exception' ); }; }
		if ( 'provenance' === $failure ) { $GLOBALS['npcink_abilities_toolkit_unit_update_post_meta_callback'] = static fn( $id, $key ) => '_npcink_cloud_import' === $key ? false : null; }
		$GLOBALS['import_delete_blocked'] = 'cleanup' === $failure;
		$failed = $w->import_cloud_media_derivative( $case + array( 'commit' => true ) );
		$check( is_wp_error( $failed ), "$failure fails closed" );
		if ( 'cleanup' === $failure ) {
			$check( 'npcink_abilities_toolkit_cloud_import_metadata_failed' === $failed->get_error_code(), 'cleanup preserves the original metadata failure code' );
			$check( ! empty( $failed->get_error_data()['recovery_required'] ), 'failed deletion reports recovery and keeps the new attachment files' );
		} else {
			$check( 1 === count( glob( $root . '/npcink-processed-*' ) ), "$failure leaves no new files" );
		}
		$GLOBALS['npcink_abilities_toolkit_unit_cloud_artifact_receive_callback'] = static fn( $a ) => npcink_abilities_toolkit_cloud_receive_fixture( $a, $bytes );
		$GLOBALS['npcink_abilities_toolkit_unit_generate_attachment_metadata_callback'] = static fn() => array( 'width' => 1, 'height' => 1, 'sizes' => array() );
		unset( $GLOBALS['npcink_abilities_toolkit_unit_update_post_meta_callback'] );
	}
	$check( $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][87] === $before_meta && serialize( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][88] ) === $before_post && file_get_contents( $root . '/source.png' ) === $bytes && file_get_contents( $root . '/source-size.png' ) === 'unchanged intermediate', 'source bytes, sizes, metadata and article references remain identical' );
} finally {
	$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
	foreach ( $files as $file ) { $file->isDir() ? rmdir( $file->getPathname() ) : unlink( $file->getPathname() ); }
	rmdir( $root );
}
echo "OK: Cloud save-as-new safety\n";
