<?php
/**
 * Bounded backup evidence surviving WordPress attachment deletion.
 *
 * @package NpcinkAbilitiesToolkit
 */
namespace Npcink_Abilities_Toolkit\Support;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Shares file verification and manifest persistence between deletion and restore. */
final class Flagged_Media_Backup {
	public const OPTION = 'npcink_abilities_toolkit_flagged_media_deletion_backups';
	public const PREFIX = 'npcink-abilities-toolkit-backups/flagged-media/';
	public const MAX_RECORDS = 100;
	public const MAX_FILES = 64;
	public const MAX_BYTES = 268435456;
	/** @return \WP_Error */
	public static function error( string $detail, int $status = 409 ) {
		return new \WP_Error( 'npcink_abilities_toolkit_flagged_media_backup_failed', __( 'The flagged-media backup operation could not be verified.', 'npcink-abilities-toolkit' ), array( 'status' => $status, 'detail' => $detail ) );
	}

	public static function valid_id( string $id ): bool {
		return 1 === preg_match( '/^flagged_delete_[a-f0-9]{24}$/', $id );
	}

	public static function id( int $attachment_id, string $key ): string {
		return 'flagged_delete_' . substr( hash( 'sha256', $attachment_id . '|' . $key ), 0, 24 );
	}

	public static function retention_days(): int {
		return max( 1, min( 365, (int) apply_filters( 'npcink_abilities_toolkit_media_backup_retention_days', 30 ) ) );
	}
	/** @return array<string,mixed> */
	public static function evidence( string $id ): array {
		return array( 'backup_id' => $id, 'location' => self::PREFIX . $id, 'retention_window' => self::retention_days() . ' days', 'restore_ability_contract' => 'npcink-abilities-toolkit/restore-media-backup' );
	}
	/** @return array<string,array<string,mixed>> */
	public static function records(): array {
		$value = get_option( self::OPTION, array() );
		return is_array( $value ) ? $value : array();
	}
	/** Must be called while holding the shared backup lock. @param array<string,array<string,mixed>> $records */
	public static function persist( array $records ): bool {
		update_option( self::OPTION, $records, false );
		return get_option( self::OPTION, array() ) === $records;
	}
	/**
	 * Resolves paths with no traversal or symlink components; missing targets are allowed.
	 */
	public static function path( string $relative ): string {
		if ( '' === $relative || false !== strpos( $relative, '\\' ) || '/' === $relative[0] || preg_match( '~(^|/)(\.|\.\.)(/|$)|[\x00-\x1f:]~', $relative ) ) { return ''; }
		$upload = wp_upload_dir();
		$base = realpath( (string) $upload['basedir'] );
		if ( false === $base || ! empty( $upload['error'] ) ) { return ''; }
		$path = $base;
		foreach ( explode( '/', $relative ) as $part ) {
			if ( '' === $part ) { return ''; }
			$path .= DIRECTORY_SEPARATOR . $part;
			if ( is_link( $path ) ) { return ''; }
			$real = realpath( $path );
			if ( false !== $real && 0 !== strpos( $real, $base . DIRECTORY_SEPARATOR ) ) { return ''; }
		}
		return $path;
	}
	/**
	 * A filesystem lock serializes this bounded option, and releases on process death.
	 * No leases, retries, schedulers or task state are introduced.
	 * @param callable $callback Operation under the lock.
	 * @return mixed
	 */
	public static function locked( callable $callback ) {
		$dir = self::path( rtrim( self::PREFIX, '/' ) );
		if ( '' === $dir || ! wp_mkdir_p( $dir ) || '' === self::path( rtrim( self::PREFIX, '/' ) ) ) { return self::error( 'backup_directory_unavailable', 500 ); }
		$lock_path = self::path( self::PREFIX . '.lock' );
		$lock = '' !== $lock_path ? fopen( $lock_path, 'c+b' ) : false;
		if ( false === $lock ) { return self::error( 'backup_lock_unavailable', 500 ); }
		if ( ! flock( $lock, LOCK_EX | LOCK_NB ) ) { fclose( $lock ); return self::error( 'backup_operation_in_progress' ); }
		try { return $callback(); } finally { flock( $lock, LOCK_UN ); fclose( $lock ); }
	}
	/** @return array<int,string>|\WP_Error */
	public static function attachment_files( int $id, bool $require_existing = true ) {
		$main = (string) get_post_meta( $id, '_wp_attached_file', true );
		$metadata = (array) wp_get_attachment_metadata( $id );
		$files = array( $main );
		$dir = dirname( $main );
		$dir = '.' === $dir ? '' : $dir . '/';
		foreach ( (array) ( $metadata['sizes'] ?? array() ) as $size ) {
			$size = (array) $size;
			if ( ! empty( $size['file'] ) ) { $files[] = $dir . $size['file']; }
		}
		if ( ! empty( $metadata['original_image'] ) ) { $files[] = $dir . $metadata['original_image']; }
		foreach ( (array) get_post_meta( $id, '_wp_attachment_backup_sizes', true ) as $size ) {
			if ( is_array( $size ) && ! empty( $size['file'] ) ) { $files[] = $dir . $size['file']; }
		}
		if ( ! empty( $metadata['thumb'] ) ) { $files[] = $dir . $metadata['thumb']; }
		$files = array_values( array_unique( $files ) );
		if ( count( $files ) > self::MAX_FILES ) { return self::error( 'too_many_attachment_files' ); }
		$bytes = 0;
		foreach ( $files as $file ) {
			$path = self::path( $file );
			if ( '' === $path || ( $require_existing && ( ! is_file( $path ) || ! is_readable( $path ) ) ) || 0 === strpos( $file, 'npcink-abilities-toolkit-backups/' ) ) { return self::error( 'attachment_file_unavailable' ); }
			$bytes += is_file( $path ) ? (int) filesize( $path ) : 0;
		}
		return $bytes <= self::MAX_BYTES ? $files : self::error( 'attachment_backup_too_large' );
	}
	/** Creates only a new file and checks exact bytes. On failure removes only its own new target. */
	public static function copy_exclusive( string $source, string $target, string $hash ): bool {
		if ( ! is_file( $source ) || is_link( $source ) || hash_file( 'sha256', $source ) !== $hash || file_exists( $target ) || is_link( $target ) ) { return false; }
		if ( ! wp_mkdir_p( dirname( $target ) ) ) { return false; }
		$out = fopen( $target, 'x+b' );
		if ( false === $out ) { return false; }
		$in = fopen( $source, 'rb' );
		$copied = false !== $in && false !== stream_copy_to_stream( $in, $out );
		if ( false !== $in ) { fclose( $in ); }
		$flushed = fflush( $out );
		fclose( $out );
		if ( ! $copied || ! $flushed || hash_file( 'sha256', $target ) !== $hash ) { unlink( $target ); return false; }
		return true;
	}
	/** @param object $attachment @return array<string,mixed>|\WP_Error */
	public static function create( $attachment, string $id, string $key, string $reason ) {
		$records = self::records();
		$existing = $records[ $id ] ?? array();
		if ( ! empty( $existing ) ) {
			if ( ( $existing['key'] ?? '' ) !== $key || ( $existing['reason'] ?? '' ) !== $reason || (int) ( $existing['expires_at'] ?? 0 ) <= time() ) { return self::error( 'idempotency_conflict' ); }
			if ( 'backing_up' !== ( $existing['state'] ?? '' ) ) { return $existing; }
			if ( ! self::remove_files( $id, (array) ( $existing['files'] ?? array() ) ) ) { return self::error( 'incomplete_backup_cleanup_failed', 500 ); }
		} elseif ( count( $records ) >= self::MAX_RECORDS ) { return self::error( 'backup_capacity_reached' ); }
		$attachment_id = (int) $attachment->ID;
		$files = self::attachment_files( $attachment_id );
		if ( is_wp_error( $files ) ) { return $files; }
		$entries = array();
		foreach ( $files as $relative ) { $entries[] = array( 'relative_file' => $relative, 'sha256' => (string) hash_file( 'sha256', self::path( $relative ) ) ); }
		$post = array();
		foreach ( array( 'post_title', 'post_content', 'post_excerpt', 'post_mime_type', 'post_parent', 'post_author' ) as $field ) { $post[ $field ] = $attachment->$field ?? ''; }
		$backup_sizes = get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', true );
		$backup_sizes = is_array( $backup_sizes ) ? $backup_sizes : array();
		$record = array( 'backup_id' => $id, 'attachment_id' => $attachment_id, 'branch' => 'governed_with_backup', 'key' => $key, 'reason' => $reason, 'post' => $post, 'relative_file' => $files[0], 'metadata' => (array) wp_get_attachment_metadata( $attachment_id ), 'backup_sizes' => $backup_sizes, 'alt' => (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ), 'files' => $entries, 'expires_at' => $existing['expires_at'] ?? ( time() + self::retention_days() * 86400 ), 'state' => 'backing_up' );
		// Intent precedes any copies, so crashes remain bounded and expire normally.
		$records[ $id ] = $record;
		if ( ! self::persist( $records ) ) { return self::error( 'backup_manifest_not_persisted', 500 ); }
		foreach ( $entries as $index => $entry ) {
			$source = self::path( $entry['relative_file'] );
			$target = self::path( self::PREFIX . $id . '/' . $index . '.bin' );
			if ( '' === $source || '' === $target || ! self::copy_exclusive( $source, $target, $entry['sha256'] ) ) {
				self::remove_files( $id, $entries );
				return self::error( 'backup_copy_failed', 500 );
			}
		}
		$record['state'] = 'ready';
		$records[ $id ] = $record;
		if ( ! self::persist( $records ) ) { self::remove_files( $id, $entries ); return self::error( 'backup_result_not_persisted', 500 ); }
		return $record;
	}
	/** @param array<int,array<string,mixed>> $entries */
	public static function remove_files( string $id, array $entries ): bool {
		$ok = self::valid_id( $id );
		if ( ! $ok ) { return false; }
		foreach ( array_keys( $entries ) as $index ) {
			$path = self::path( self::PREFIX . $id . '/' . $index . '.bin' );
			if ( '' === $path ) { $ok = false; continue; }
			if ( is_file( $path ) && ! unlink( $path ) ) { $ok = false; }
		}
		$directory = self::path( self::PREFIX . $id );
		if ( $ok && '' !== $directory && is_dir( $directory ) ) { $ok = rmdir( $directory ); }
		return $ok;
	}
	/** @param array<string,mixed> $result @return array<string,mixed> */
	public static function add_cleanup_counts( array $result, bool $execute ): array {
		$counts = self::cleanup( $execute );
		$result['expired'] = (int) $result['expired'] + $counts['expired'];
		$result['removed'] = (int) $result['removed'] + $counts['removed'];
		$result['flagged_media_cleanup'] = $counts;
		return $result;
	}
	/** Uses the existing backup maintenance hook. @return array<string,mixed> */
	public static function cleanup( bool $execute ): array {
		$operation = static function () use ( $execute ): array {
			$records = self::records(); $expired = 0; $removed = 0; $failed = false;
			foreach ( $records as $id => $record ) {
				if ( ! self::valid_id( $id ) || (int) ( $record['expires_at'] ?? PHP_INT_MAX ) > time() ) { continue; }
				++$expired;
				if ( $execute ) {
					if ( self::remove_files( $id, (array) ( $record['files'] ?? array() ) ) ) { unset( $records[ $id ] ); ++$removed; } else { $failed = true; }
				}
			}
			if ( $execute && ! self::persist( $records ) ) { return array( 'expired' => $expired, 'removed' => $removed, 'status' => 'failed', 'error_code' => 'manifest_cleanup_not_persisted' ); }
			if ( $failed ) { return array( 'expired' => $expired, 'removed' => $removed, 'status' => 'failed', 'error_code' => 'backup_files_cleanup_failed' ); }
			return array( 'expired' => $expired, 'removed' => $removed, 'status' => 'completed' );
		};
		$result = $execute ? self::locked( $operation ) : $operation();
		return is_array( $result ) ? $result : array( 'expired' => 0, 'removed' => 0, 'status' => 'skipped', 'error_code' => $result->get_error_code() );
	}
}
