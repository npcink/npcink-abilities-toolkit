<?php
/**
 * Temporary media file groups and existing maintenance integration.
 *
 * @package NpcinkAbilitiesToolkit
 */
namespace Npcink_Abilities_Toolkit\Packages;
if ( ! defined( 'ABSPATH' ) ) { exit; }
trait Temporary_Media_Backup_Write_Methods {

	/**
	 * Removes expired hidden media backup files while retaining history evidence.
	 *
	 * This is maintenance only; it is not a workflow queue or a write ability.
	 *
	 * @param bool $execute Whether to remove files and persist expiry markers. False returns a read-only preview.
	 * @param bool $include_manual Whether a confirmed maintenance run may process manual-confirmation records.
	 * @return array<string,mixed>
	 */
	public function cleanup_expired_media_backups( bool $execute = true, bool $include_manual = false ): array {
		if ( $execute && ! $this->media_backup_lock_active ) {
			$result = $this->with_media_backup_lock( function () use ( $include_manual ) { return $this->cleanup_expired_media_backups( true, $include_manual ); } );
			if ( ! is_wp_error( $result ) ) { return $result; }
			return array( 'retention_days' => self::MEDIA_BACKUP_RETENTION_DAYS, 'expired' => 0, 'removed' => 0, 'processed_attachments' => 0, 'has_more' => false, 'next_cursor' => 0, 'error_code' => $result->get_error_code() );
		}
		$this->temporary_media_files_remaining = 32;
		$budget_exhausted = false;
		$retention_days = (int) apply_filters( 'npcink_abilities_toolkit_media_backup_retention_days', self::MEDIA_BACKUP_RETENTION_DAYS );
		$retention_days = max( 1, min( 365, $retention_days ) );
		$cutoff = time() - ( $retention_days * ( defined( 'DAY_IN_SECONDS' ) ? DAY_IN_SECONDS : 86400 ) );
		$removed = 0;
		$expired = 0;

		$cursor_option = $include_manual ? self::MEDIA_BACKUP_MANUAL_CLEANUP_CURSOR_OPTION : self::MEDIA_BACKUP_CLEANUP_CURSOR_OPTION;
		$cursor = function_exists( 'get_option' ) ? absint( get_option( $cursor_option, 0 ) ) : 0;
		$batch = $this->media_backup_cleanup_attachment_ids_after( $cursor );
		if ( empty( $batch ) && $cursor > 0 ) {
			$cursor = 0;
			$batch = $this->media_backup_cleanup_attachment_ids_after( 0 );
		}
		$has_more = count( $batch ) > self::MAX_MEDIA_BACKUP_CLEANUP_ATTACHMENTS;
		$batch = array_slice( $batch, 0, self::MAX_MEDIA_BACKUP_CLEANUP_ATTACHMENTS );
		$processed_attachments = 0;
		$last_complete = $cursor;

		foreach ( $batch as $attachment_id ) {
			$history = $this->get_media_file_replacement_history( absint( $attachment_id ) );
			$previous_history = $history;
			$changed = false;
			++$processed_attachments;
			$start = 0;
			foreach ( $history as $index => $row ) { if ( ! empty( $row['temporary_backup']['cleanup_resume'] ) ) { $start = $index; break; } }
			foreach ( array_keys( $history ) as $index ) {
				if ( $index < $start ) { continue; }
				$record =& $history[ $index ];
				if ( ! empty( $record['temporary_backup'] ) ) {
					if ( $this->temporary_media_group_cleanup_eligible( $record['temporary_backup'] ) ) { ++$expired; }
					$updated = $this->cleanup_temporary_media_group( (int) $attachment_id, $record, $execute );
					$budget_exhausted = ! empty( $updated['temporary_backup']['cleanup_resume'] );
					if ( $execute && $updated !== $record ) {
						$removed_count = static function ( $row ) { return count( array_filter( $row['temporary_backup']['files'], static function ( $file ) { return 'removed' === ( $file['state'] ?? '' ); } ) ); };
						$removed += max( 0, $removed_count( $updated ) - $removed_count( $record ) );
						$record = $updated; $changed = true;
					}
					if ( $budget_exhausted ) { break; }
					continue;
				}
				if ( 'backup_expired' === (string) ( $record['status'] ?? '' ) ) {
					continue;
				}
				$policy = (string) ( $record['backup_cleanup_policy'] ?? '' );
				if ( '' !== $policy && self::MEDIA_BACKUP_CLEANUP_AUTOMATIC !== $policy && self::MEDIA_BACKUP_CLEANUP_MANUAL !== $policy ) {
					continue;
				}
				if ( self::MEDIA_BACKUP_CLEANUP_MANUAL === $policy && ! $include_manual ) {
					continue;
				}
				$backup = is_array( $record['backup'] ?? null ) ? $record['backup'] : array();
				$relative = $this->normalize_media_relative_file( (string) ( $backup['relative_file'] ?? '' ) );
				if ( '' === $relative || 0 !== strpos( $relative, 'npcink-abilities-toolkit-backups/' ) ) {
					continue;
				}
				$created = strtotime( (string) ( $record['replaced_at_gmt'] ?? '' ) );
				if ( false === $created || $created > $cutoff ) {
					continue;
				}
				++$expired;
				if ( ! $execute ) {
					continue;
				}
				$path = $this->media_uploads_path_for_relative_file( $relative );
				$file_existed = '' !== $path && is_file( $path );
				$file_removed = ! $file_existed;
				if ( ! $file_removed && function_exists( 'wp_delete_file' ) ) {
					wp_delete_file( $path );
					$file_removed = ! is_file( $path );
				}
				if ( ! $file_removed ) {
					continue;
				}
				if ( $file_existed ) {
					++$removed;
				}
				$record['status'] = 'backup_expired';
				$record['backup']['file_exists'] = false;
				$record['backup']['expired_at_gmt'] = gmdate( 'c' );
				$changed = true;
			}
			unset( $record );
			if ( $changed && function_exists( 'update_post_meta' ) ) {
				update_post_meta( absint( $attachment_id ), '_npcink_ai_media_file_replacement_history', $history, $previous_history );
			}
			if ( $budget_exhausted ) { $has_more = true; break; }
			$last_complete = (int) $attachment_id;
		}
		$this->temporary_media_files_remaining = null;

		$next_cursor = 0;
		if ( $execute ) {
			$next_cursor = $has_more ? $last_complete : 0;
			if ( $next_cursor > 0 && function_exists( 'update_option' ) ) {
				update_option( $cursor_option, $next_cursor, false );
			} elseif ( function_exists( 'delete_option' ) ) {
				delete_option( $cursor_option );
			}
		}

		return \Npcink_Abilities_Toolkit\Support\Flagged_Media_Backup::add_cleanup_counts( array( 'retention_days' => $retention_days, 'expired' => $expired, 'removed' => $removed, 'processed_attachments' => $processed_attachments, 'has_more' => $has_more, 'next_cursor' => $next_cursor ), $execute );
	}

	/** @var int|null Remaining temporary-file candidates in this maintenance call. */
	private $temporary_media_files_remaining = null;

	/** @var bool Prevent cooperative restore/cleanup overlap. */
	private $media_backup_lock_active = false;

	/** @return mixed */
	private function with_media_backup_lock( callable $callback ) {
		$uploads = wp_upload_dir( null, false );
		$path = (string) $uploads['basedir'] . '/.npcink-media-backup.lock';
		if ( ! empty( $uploads['error'] ) || ! $this->is_media_uploads_path_allowed( $path ) || is_link( $path ) ) {
			return new \WP_Error( 'npcink_media_backup_lock_unavailable', 'Media maintenance is unavailable.', array( 'status' => 409 ) );
		}
		$lock = @fopen( $path, 'c+b' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen,WordPress.PHP.NoSilencedErrors.Discouraged -- synchronous nonblocking lock, no scheduler.
		if ( ! $lock ) { return new \WP_Error( 'npcink_media_backup_lock_unavailable', 'Media maintenance is unavailable.', array( 'status' => 409 ) ); }
		try {
			if ( ! flock( $lock, LOCK_EX | LOCK_NB ) ) { return new \WP_Error( 'npcink_media_backup_busy', 'Media maintenance is busy. Retry later.', array( 'status' => 409 ) ); }
			$opened = fstat( $lock );
			$named = lstat( $path );
			if ( ! $opened || ! $named || $opened['ino'] !== $named['ino'] || $opened['dev'] !== $named['dev'] || ( $named['mode'] & 0170000 ) !== 0100000 ) {
				return new \WP_Error( 'npcink_media_backup_lock_unavailable', 'Media maintenance is unavailable.', array( 'status' => 409 ) );
			}
			$this->media_backup_lock_active = true;
			return $callback();
		} finally {
			$this->media_backup_lock_active = false;
			flock( $lock, LOCK_UN );
			fclose( $lock ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- paired lock release.
		}
	}

	/** Freeze only metadata-owned local files, never a filename glob. @return array<string,mixed>|\WP_Error */
	private function plan_temporary_media_backup( array $current, array $input, string $restoring_replacement_id = '' ) {
		if ( ! isset( $input['backup_lifecycle'] ) ) { return array(); }
		if ( 'temporary_7_days.v1' !== $input['backup_lifecycle'] || ! empty( $input['batch_id'] ) || ( $input['expected_current_media_fingerprint'] ?? '' ) !== ( $current['media_fingerprint'] ?? null ) ) {
			return new \WP_Error( 'npcink_temporary_media_backup_invalid', 'Temporary backup requires an exact reviewed single-image source.', array( 'status' => 409 ) );
		}
		$pending = array_filter( $this->get_media_file_replacement_history( (int) ( $input['attachment_id'] ?? 0 ) ), static function ( $row ) use ( $restoring_replacement_id ) { return ( '' === $restoring_replacement_id || ( $row['replacement_id'] ?? '' ) !== $restoring_replacement_id ) && in_array( $row['temporary_backup']['state'] ?? '', array( 'retained', 'blocked' ), true ); } );
		if ( count( $pending ) >= 50 ) { return new \WP_Error( 'npcink_temporary_media_backup_limit', 'Too many retained image versions. Resolve existing cleanup before replacing again.', array( 'status' => 409 ) ); }
		$metadata = is_array( $current['metadata'] ?? null ) ? $current['metadata'] : array();
		$metadata['file'] = (string) $current['relative_file'];
		$paths = $this->cloud_media_metadata_file_paths( $metadata );
		if ( ! $paths || count( $paths ) > 64 ) { return new \WP_Error( 'npcink_temporary_media_backup_invalid', 'The image file set cannot be safely retained.', array( 'status' => 409 ) ); }
		$uploads = wp_upload_dir( null, false );
		$base = rtrim( (string) $uploads['basedir'], '/' ) . '/';
		$files = array();
		foreach ( $paths as $path ) {
			$file = $this->temporary_media_file_snapshot( $path );
			if ( ! $file ) { return new \WP_Error( 'npcink_temporary_media_backup_invalid', 'An original image file is missing or unsafe.', array( 'status' => 409 ) ); }
			$file['relative_file'] = substr( $path, strlen( $base ) );
			$file['state'] = 'retained';
			$files[] = $file;
		}
		return array( 'version' => 'temporary_7_days.v1', 'state' => 'retained', 'expires_at_gmt' => gmdate( 'c', time() + 7 * DAY_IN_SECONDS ), 'metadata' => $metadata, 'files' => $files, 'removed_bytes' => 0 );
	}

	/** @return array<string,mixed> Empty means unsafe or unavailable. */
	private function temporary_media_file_snapshot( string $path ): array {
		clearstatcache( true, $path );
		if ( ! $this->is_media_uploads_path_allowed( $path ) || is_link( $path ) || ! is_readable( $path ) ) { return array(); }
		$base = rtrim( $this->media_uploads_base_dir(), '/' );
		for ( $parent = dirname( $path ); strlen( $parent ) > strlen( $base ); $parent = dirname( $parent ) ) { if ( is_link( $parent ) ) { return array(); } }
		$stat = lstat( $path );
		$sha = hash_file( 'sha256', $path );
		if ( ! $stat || ( $stat['mode'] & 0170000 ) !== 0100000 || (int) $stat['nlink'] !== 1 || ! is_string( $sha ) ) { return array(); }
		return array( 'device' => (int) $stat['dev'], 'inode' => (int) $stat['ino'], 'filesize_bytes' => (int) $stat['size'], 'sha256' => $sha );
	}

	/** @return true|\WP_Error */
	private function validate_temporary_media_restore( array $group, bool $check_expiry = true ) {
		if ( 'temporary_7_days.v1' !== ( $group['version'] ?? '' ) || ! in_array( $group['state'] ?? '', array( 'retained', 'blocked' ), true ) || ! is_array( $group['files'] ?? null ) || ! $group['files'] || ( $check_expiry && strtotime( (string) ( $group['expires_at_gmt'] ?? '' ) ) <= time() ) ) {
			return new \WP_Error( 'npcink_temporary_media_backup_unavailable', 'The original image recovery window is no longer available.', array( 'status' => 409 ) );
		}
		foreach ( $group['files'] as $file ) {
			$path = $this->media_uploads_path_for_relative_file( (string) ( $file['relative_file'] ?? '' ) );
			$current = $this->temporary_media_file_snapshot( $path );
			foreach ( array( 'device', 'inode', 'filesize_bytes', 'sha256' ) as $key ) {
				if ( ! $current || ( $file[ $key ] ?? null ) !== $current[ $key ] ) { return new \WP_Error( 'npcink_temporary_media_backup_changed', 'An original image file changed or is missing. Recovery was stopped.', array( 'status' => 409 ) ); }
			}
		}
		return true;
	}

	/** Recheck the selected original group separately from the superseded-current backup. @return true|\WP_Error */
	private function validate_temporary_media_restore_source( array $plan ) {
		return ! empty( $plan['_restore_source_group'] ) ? $this->validate_temporary_media_restore( $plan['_restore_source_group'] ) : true;
	}

	/** Pending file groups must never disappear behind the legacy 20-row history cap. @return array<int,array<string,mixed>> */
	private function retain_temporary_media_history( array $history ): array {
		$keep = array();
		$cutoff = max( 0, count( $history ) - 20 );
		foreach ( $history as $i => $record ) {
			if ( $i >= $cutoff || in_array( $record['temporary_backup']['state'] ?? '', array( 'retained', 'blocked' ), true ) ) { $keep[] = $record; }
		}
		return $keep;
	}

	/** Current files, other recoveries and local references prevent destructive cleanup. */
	private function temporary_media_reference_blocker( int $id, string $relative, string $replacement_id ): string {
		global $wpdb;
		if ( ! is_object( $wpdb ) || ! method_exists( $wpdb, 'get_results' ) || ! method_exists( $wpdb, 'esc_like' ) ) { return 'reference_check_unavailable'; }
		$path = $this->media_uploads_path_for_relative_file( $relative );
		$metadata = wp_get_attachment_metadata( $id );
		if ( get_post_meta( $id, '_wp_attached_file', true ) === $relative || in_array( $path, $this->cloud_media_metadata_file_paths( is_array( $metadata ) ? $metadata : array() ), true ) ) { return 'current_image'; }
		$like = '%' . $wpdb->esc_like( basename( $relative ) ) . '%';
		$wpdb->last_error = '';
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE meta_key IN ('_wp_attached_file','_wp_attachment_metadata','_wp_attachment_backup_sizes','_npcink_ai_media_file_replacement_history') AND meta_value LIKE %s LIMIT 501", $like ) );
		if ( ! is_array( $rows ) || $wpdb->last_error || count( $rows ) > 500 ) { return 'reference_check_unavailable'; }
		foreach ( $rows as $row ) {
			$other_id = (int) $row->post_id;
			$value = maybe_unserialize( $row->meta_value );
			if ( '_wp_attached_file' === $row->meta_key && $relative === $value ) { return 'another_attachment'; }
			if ( '_wp_attachment_metadata' === $row->meta_key && is_array( $value ) && in_array( $path, $this->cloud_media_metadata_file_paths( $value ), true ) ) { return 'another_attachment'; }
			if ( '_wp_attachment_backup_sizes' === $row->meta_key && is_array( $value ) ) {
				$main = (string) get_post_meta( $other_id, '_wp_attached_file', true );
				foreach ( $value as $size ) { if ( is_array( $size ) && dirname( $main ) . '/' . ( $size['file'] ?? '' ) === $relative ) { return 'another_attachment'; } }
			}
			if ( '_npcink_ai_media_file_replacement_history' !== $row->meta_key || ! is_array( $value ) ) { continue; }
			foreach ( $value as $record ) {
				if ( ! is_array( $record ) || ( $other_id === $id && ( $record['replacement_id'] ?? '' ) === $replacement_id ) ) { continue; }
				$group = $record['temporary_backup'] ?? array();
				if ( $group ) {
					if ( ! in_array( $group['state'] ?? '', array( 'retained', 'blocked' ), true ) || strtotime( (string) ( $group['expires_at_gmt'] ?? '' ) ) <= time() ) { continue; }
					foreach ( $group['files'] ?? array() as $file ) { if ( ( $file['relative_file'] ?? '' ) === $relative && 'removed' !== ( $file['state'] ?? '' ) ) { return 'another_recovery'; } }
				} elseif ( 'backup_expired' !== ( $record['status'] ?? '' ) && ( $record['backup']['relative_file'] ?? '' ) === $relative ) { return 'another_recovery'; }
			}
		}
		// Batch all exact representations into one scan per table, including default JSON Unicode escaping.
		$encoded = implode( '/', array_map( 'rawurlencode', explode( '/', $relative ) ) );
		$json = wp_json_encode( $relative );
		if ( ! is_string( $json ) ) { return 'reference_check_unavailable'; }
		$json = substr( $json, 1, -1 );
		$needles = array_unique( array( $relative, str_replace( '/', '\\/', $relative ), rawurlencode( $relative ), $encoded, str_replace( '/', '\\/', $encoded ), $json, str_replace( '\\/', '/', $json ) ) );
		$post_clauses = $value_clauses = $post_args = $value_args = array();
		foreach ( $needles as $needle ) {
			$like = '%' . $wpdb->esc_like( $needle ) . '%';
			$post_clauses[] = '(post_content LIKE %s OR post_excerpt LIKE %s)';
			$post_args[] = $like; $post_args[] = $like;
			$value_clauses[] = 'meta_value LIKE %s'; $value_args[] = $like;
		}
		$queries = array(
			$wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE " . implode( ' OR ', $post_clauses ) . ' LIMIT 1', ...$post_args ),
			$wpdb->prepare( "SELECT meta_id FROM {$wpdb->postmeta} WHERE meta_key NOT IN ('_wp_attached_file','_wp_attachment_metadata','_wp_attachment_backup_sizes','_npcink_ai_media_file_replacement_history','_npcink_ai_media_latest_file_replacement','_npcink_ai_media_optimized_derivatives','_npcink_ai_media_latest_optimized_derivative') AND (" . implode( ' OR ', $value_clauses ) . ') LIMIT 1', ...$value_args ),
			$wpdb->prepare( "SELECT option_id FROM {$wpdb->options} WHERE option_name NOT LIKE %s AND (" . str_replace( 'meta_value', 'option_value', implode( ' OR ', $value_clauses ) ) . ') LIMIT 1', $wpdb->esc_like( '_transient_' ) . '%', ...$value_args ),
		);
		foreach ( $queries as $query ) {
			$wpdb->last_error = '';
			$found = $wpdb->get_var( $query );
			if ( $wpdb->last_error ) { return 'reference_check_unavailable'; }
			if ( $found ) { return 'local_reference'; }
		}
		return '';
	}

	/** The maintenance preview and executor share the same expiry boundary. */
	private function temporary_media_group_cleanup_eligible( array $group ): bool {
		$expires = strtotime( (string) ( $group['expires_at_gmt'] ?? '' ) );
		return 'temporary_7_days.v1' === ( $group['version'] ?? '' ) && false !== $expires && $expires <= time() && is_array( $group['files'] ?? null ) && count( $group['files'] ) <= 64 && in_array( $group['state'] ?? '', array( 'retained', 'blocked' ), true );
	}

	/** Mutates only frozen file entries. Returns truthful measured bytes, not estimates. */
	private function cleanup_temporary_media_group( int $id, array $record, bool $execute ): array {
		$group = $record['temporary_backup'];
		if ( ! $this->temporary_media_group_cleanup_eligible( $group ) ) { return $record; }
		$start = min( count( $group['files'] ), max( 0, (int) ( $group['next_file_index'] ?? 0 ) ) );
		unset( $group['cleanup_resume'], $group['next_file_index'] );
		foreach ( array_keys( $group['files'] ) as $index ) {
			if ( $index < $start ) { continue; }
			$file =& $group['files'][ $index ];
			if ( in_array( $file['state'] ?? '', array( 'removed', 'missing', 'current_image' ), true ) ) { continue; }
			if ( null !== $this->temporary_media_files_remaining ) {
				if ( $this->temporary_media_files_remaining <= 0 ) {
					$group['next_file_index'] = $index; $group['cleanup_resume'] = true;
					break;
				}
				--$this->temporary_media_files_remaining;
			}
			$relative = (string) ( $file['relative_file'] ?? '' );
			$path = $this->media_uploads_path_for_relative_file( $relative );
			$reason = $this->temporary_media_reference_blocker( $id, $relative, (string) $record['replacement_id'] );
			if ( 'current_image' === $reason ) { $file['state'] = 'current_image'; $file['reason'] = ''; continue; }
			if ( ! $reason && ! file_exists( $path ) && ! is_link( $path ) ) { $file['state'] = 'missing'; $file['reason'] = ''; continue; }
			$current = $this->temporary_media_file_snapshot( $path );
			foreach ( array( 'device', 'inode', 'filesize_bytes', 'sha256' ) as $key ) { if ( ! $current || ( $file[ $key ] ?? null ) !== $current[ $key ] ) { $reason = $reason ?: 'file_changed'; } }
			$file['reason'] = $reason;
			if ( $reason ) { continue; }
			if ( ! $execute ) { continue; }
			// A second guard after filters/queries prevents known cooperative state drift.
			if ( $this->temporary_media_reference_blocker( $id, $relative, (string) $record['replacement_id'] ) || $current !== $this->temporary_media_file_snapshot( $path ) ) { $file['reason'] = 'state_changed'; continue; }
			$guard = function ( $candidate ) use ( $path, $current, $id, $relative, $record ) { return $candidate === $path && $current === $this->temporary_media_file_snapshot( $path ) && ! $this->temporary_media_reference_blocker( $id, $relative, (string) $record['replacement_id'] ) ? $path : ''; };
			add_filter( 'wp_delete_file', $guard, PHP_INT_MAX );
			try { wp_delete_file( $path ); } finally { remove_filter( 'wp_delete_file', $guard, PHP_INT_MAX ); }
			clearstatcache( true, $path );
			if ( file_exists( $path ) || is_link( $path ) ) { $file['reason'] = 'delete_failed'; continue; }
			$file['state'] = 'removed';
			$group['removed_bytes'] += (int) $file['filesize_bytes'];
		}
		unset( $file );
		if ( $execute ) {
			$remaining = array_filter( $group['files'], static function ( $file ) { return ! in_array( $file['state'] ?? '', array( 'removed', 'missing', 'current_image' ), true ); } );
			$group['state'] = $remaining ? ( array_filter( array_column( $remaining, 'reason' ) ) ? 'blocked' : 'retained' ) : 'cleaned';
		}
		$record['temporary_backup'] = $group;
		return $record;
	}

}
