<?php
/**
 * Media backup lifecycle, restore transaction, and replacement lineage write methods for Core_Write_Package.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Provides host-governed media backup cleanup, backup restore, and file replacement lineage writes.
 */
trait Media_Backup_Write_Methods {
	/**
	 * Removes expired hidden media backup files while retaining history evidence.
	 *
	 * This is maintenance only; it is not a workflow queue or a write ability.
	 *
	 * @param bool $execute Whether to remove files and persist expiry markers. False returns a read-only preview.
	 * @param bool $include_manual Whether a confirmed maintenance run may process manual-confirmation records.
	 * @return array<string,int|bool>
	 */
	public function cleanup_expired_media_backups( bool $execute = true, bool $include_manual = false ): array {
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
		$processed_attachments = count( $batch );

		foreach ( $batch as $attachment_id ) {
			$history = $this->get_media_file_replacement_history( absint( $attachment_id ) );
			$changed = false;
			foreach ( $history as &$record ) {
				if ( ! is_array( $record ) || 'backup_expired' === (string) ( $record['status'] ?? '' ) ) {
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
					$file_removed = (bool) wp_delete_file( $path );
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
				update_post_meta( absint( $attachment_id ), '_npcink_ai_media_file_replacement_history', $history );
			}
		}

		$next_cursor = 0;
		if ( $execute ) {
			$next_cursor = $has_more ? absint( end( $batch ) ) : 0;
			if ( $next_cursor > 0 && function_exists( 'update_option' ) ) {
				update_option( $cursor_option, $next_cursor, false );
			} elseif ( function_exists( 'delete_option' ) ) {
				delete_option( $cursor_option );
			}
		}

		return array( 'retention_days' => $retention_days, 'expired' => $expired, 'removed' => $removed, 'processed_attachments' => $processed_attachments, 'has_more' => $has_more, 'next_cursor' => $next_cursor );
	}

	/**
	 * Returns one stable ID-ordered cleanup window plus a look-ahead row.
	 *
	 * @param int $after_id Last attachment ID processed by an earlier run.
	 * @return array<int,int>
	 */
	private function media_backup_cleanup_attachment_ids_after( int $after_id ): array {
		if ( ! function_exists( 'get_posts' ) ) {
			return array();
		}

		$where_filter = static function ( $where, $query ) use ( $after_id ) {
			if ( $after_id <= 0 || ! is_object( $query ) || ! method_exists( $query, 'get' ) || absint( $query->get( 'npcink_abilities_toolkit_cleanup_after_id' ) ) !== $after_id ) {
				return $where;
			}
			global $wpdb;
			if ( ! isset( $wpdb->posts ) ) {
				return $where;
			}
			return $where . $wpdb->prepare( " AND {$wpdb->posts}.ID > %d", $after_id );
		};

		if ( $after_id > 0 && function_exists( 'add_filter' ) ) {
			add_filter( 'posts_where', $where_filter, 10, 2 );
		}
		try {
			$attachment_ids = get_posts(
				array(
					'post_type'              => 'attachment',
					'post_status'            => 'inherit',
					'posts_per_page'         => self::MAX_MEDIA_BACKUP_CLEANUP_ATTACHMENTS + 1,
					'fields'                 => 'ids',
					'orderby'                => 'ID',
					'order'                  => 'ASC',
					'no_found_rows'          => true,
					'suppress_filters'       => false,
					'update_post_meta_cache' => true,
					'update_post_term_cache' => false,
					'meta_key'               => '_npcink_ai_media_file_replacement_history',
					'npcink_abilities_toolkit_cleanup_after_id' => $after_id,
				)
			);
		} finally {
			if ( $after_id > 0 && function_exists( 'remove_filter' ) ) {
				remove_filter( 'posts_where', $where_filter, 10 );
			}
		}

		$attachment_ids = is_array( $attachment_ids ) ? array_map( 'absint', $attachment_ids ) : array();
		$attachment_ids = array_values( array_filter( array_unique( $attachment_ids ), static fn( $attachment_id ) => $attachment_id > $after_id ) );
		sort( $attachment_ids, SORT_NUMERIC );
		return $attachment_ids;
	}

	/**
	 * Returns an expiry preview without deleting files or changing history.
	 *
	 * @return array<string,int>
	 */
	public function preview_expired_media_backups(): array {
		return $this->cleanup_expired_media_backups( false, true );
	}

	/**
	 * Previews or removes expired media backups after host confirmation.
	 *
	 * @param mixed $input Ability input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function cleanup_media_backups( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to clean up media backups.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$preview = $this->preview_expired_media_backups();
		$payload = array(
			'retention_days' => absint( $preview['retention_days'] ?? 30 ),
			'expired'        => absint( $preview['expired'] ?? 0 ),
			'removed'        => 0,
			'processed_attachments' => absint( $preview['processed_attachments'] ?? 0 ),
			'has_more'       => ! empty( $preview['has_more'] ),
			'preview'        => array(
				'action'       => 'cleanup_expired_media_backups',
				'expired'      => absint( $preview['expired'] ?? 0 ),
				'retention_days' => absint( $preview['retention_days'] ?? 30 ),
				'current_media_preserved' => true,
			),
			'dry_run'        => true,
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}

		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/cleanup-media-backups', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$result = $this->cleanup_expired_media_backups( true, true );
		return array_merge( $payload, array(
			'retention_days' => absint( $result['retention_days'] ?? $payload['retention_days'] ),
				'expired'        => absint( $result['expired'] ?? 0 ),
				'removed'        => absint( $result['removed'] ?? 0 ),
				'processed_attachments' => absint( $result['processed_attachments'] ?? 0 ),
				'has_more'       => ! empty( $result['has_more'] ),
			'dry_run'        => false,
			'preview'        => array_merge( $payload['preview'], array( 'executed' => true ) ),
		) );
	}

	/**
	 * Replaces one attachment main file through a recorded local derivative.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function replace_media_file( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'upload_files' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to replace media files.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$attachment_id = absint( $input['attachment_id'] ?? 0 );
		$attachment = $this->get_media_attachment( $attachment_id );
		if ( is_wp_error( $attachment ) ) {
			return $attachment;
		}
		if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to replace this media file.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$plan = $this->build_media_file_replacement_plan( $attachment_id, $input );
		if ( is_wp_error( $plan ) ) {
			return $plan;
		}

		$payload = array(
			'attachment_id'      => $attachment_id,
			'mode'               => 'replace',
			'replaced'           => false,
			'rolled_back'        => false,
			'original_preserved' => true,
			'replacement_id'     => (string) ( $plan['replacement_id'] ?? '' ),
			'before'             => is_array( $plan['before'] ?? null ) ? $plan['before'] : array(),
			'after'              => is_array( $plan['after'] ?? null ) ? $plan['after'] : array(),
			'backup'             => is_array( $plan['backup'] ?? null ) ? $plan['backup'] : array(),
			'content_reference_repairs' => $this->build_media_content_reference_repairs( $attachment_id, $plan, false ),
			'history'            => $this->get_media_file_replacement_history( $attachment_id ),
			'edit_link'          => $this->edit_link( $attachment_id ),
			'preview'            => array(
				'action'          => 'replace_media_file',
				'attachment_id'   => $attachment_id,
				'replacement_id'  => (string) ( $plan['replacement_id'] ?? '' ),
				'backup_created'  => true,
				'rollback_ready'  => false,
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/replace-media-file', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$result = $this->execute_media_file_replacement( $attachment_id, $plan );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$payload['replaced'] = ! empty( $result['replaced'] );
		$payload['rolled_back'] = ! empty( $result['rolled_back'] );
		$payload['after'] = is_array( $result['after'] ?? null ) ? $result['after'] : $payload['after'];
		$payload['backup'] = is_array( $result['backup'] ?? null ) ? $result['backup'] : $payload['backup'];
		$payload['content_reference_repairs'] = is_array( $result['content_reference_repairs'] ?? null ) ? $result['content_reference_repairs'] : $payload['content_reference_repairs'];
		$payload['verification'] = $this->media_file_operation_verification( $attachment_id, $payload['after'], $payload['backup'], $payload['content_reference_repairs'] );
		if ( function_exists( 'do_action' ) && $this->media_file_operation_is_verified( $payload['verification'] ) ) {
			do_action( 'npcink_abilities_toolkit_media_file_version_changed', $attachment_id, array(
				'replacement_id' => (string) ( $payload['replacement_id'] ?? '' ),
				'new_media_fingerprint' => (string) ( $payload['after']['media_fingerprint'] ?? '' ),
				'derived_from_media_fingerprint' => (string) ( $payload['before']['media_fingerprint'] ?? '' ),
			) );
		}
		$payload['history'] = $this->get_media_file_replacement_history( $attachment_id );
		$payload['dry_run'] = false;
		unset( $payload['preview'] );
		return $payload;
	}

	/**
	 * Restores one attachment by copying a recorded backup to its original path.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function restore_media_backup( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'upload_files' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to restore media backups.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$attachment_id = absint( $input['attachment_id'] ?? 0 );
		$attachment = $this->get_media_attachment( $attachment_id );
		if ( is_wp_error( $attachment ) ) {
			return $attachment;
		}
		if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to restore this media file.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$backup_id = sanitize_text_field( (string) ( $input['backup_id'] ?? '' ) );
		if ( '' === $backup_id ) {
			return new \WP_Error( 'npcink_abilities_toolkit_backup_id_required', __( 'A backup_id is required for media backup restore.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		$plan = $this->build_media_backup_restore_plan( $attachment_id, $input );
		if ( is_wp_error( $plan ) ) {
			return $plan;
		}

		$payload = array(
			'attachment_id'      => $attachment_id,
			'backup_id'          => $backup_id,
			'replacement_id'     => (string) ( $plan['replacement_id'] ?? '' ),
			'restored'           => false,
			'rolled_back'        => false,
			'original_preserved' => true,
			'before'             => is_array( $plan['before'] ?? null ) ? $plan['before'] : array(),
			'after'              => is_array( $plan['after'] ?? null ) ? $plan['after'] : array(),
			'backup'             => is_array( $plan['backup'] ?? null ) ? $plan['backup'] : array(),
			'current_backup'     => is_array( $plan['current_backup'] ?? null ) ? $plan['current_backup'] : array(),
			'content_reference_repairs' => $this->build_media_content_reference_repairs( $attachment_id, $plan, false ),
			'history'            => $this->get_media_file_replacement_history( $attachment_id ),
			'edit_link'          => $this->edit_link( $attachment_id ),
			'preview'            => array(
				'action'         => 'restore_media_backup',
				'attachment_id'  => $attachment_id,
				'backup_id'      => $backup_id,
				'restore_ready'  => true,
				'target_file'    => (string) ( $plan['_target_relative_file'] ?? '' ),
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/restore-media-backup', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}
		$rollback_state = $this->capture_cloud_media_adoption_state(
			$attachment_id,
			is_array( $payload['content_reference_repairs'] ?? null ) ? $payload['content_reference_repairs'] : array(),
			$plan
		);
		$batch_manifest = (object) array(
			'created_files'    => array(),
			'mutations'        => array(),
			'overwritten_files' => array(),
			'rollback_state'   => $rollback_state,
		);
		$plan['_cloud_batch_manifest'] = $batch_manifest;
		$precommit = $this->validate_media_backup_restore_precommit_state(
			$attachment_id,
			$plan,
			is_array( $payload['content_reference_repairs'] ?? null ) ? $payload['content_reference_repairs'] : array(),
			$rollback_state
		);
		if ( is_wp_error( $precommit ) ) {
			return $precommit;
		}
		$plan['_current'] = is_array( $precommit['current'] ?? null ) ? $precommit['current'] : array();
		$plan['before'] = $this->public_media_file_state( $plan['_current'] );
		$rollback_state = is_array( $precommit['rollback_state'] ?? null ) ? $precommit['rollback_state'] : $rollback_state;
		$batch_manifest->rollback_state = $rollback_state;
		$payload['before'] = $plan['before'];
		$payload['content_reference_repairs'] = is_array( $precommit['content_reference_repairs'] ?? null ) ? $precommit['content_reference_repairs'] : array();
		$plan['_restore_precommit_repairs'] = $payload['content_reference_repairs'];
		$plan['_restore_precommit_state'] = $rollback_state;

		$result = $this->execute_media_backup_restore( $attachment_id, $plan );
		if ( is_wp_error( $result ) ) {
			return $this->media_backup_restore_failure_with_cleanup( $result, $attachment_id, $plan, $rollback_state );
		}

		$payload['restored'] = ! empty( $result['restored'] );
		$payload['rolled_back'] = ! empty( $result['rolled_back'] );
		$payload['after'] = is_array( $result['after'] ?? null ) ? $result['after'] : $payload['after'];
		$payload['backup'] = is_array( $result['backup'] ?? null ) ? $result['backup'] : $payload['backup'];
		$payload['current_backup'] = is_array( $result['current_backup'] ?? null ) ? $result['current_backup'] : $payload['current_backup'];
		$payload['content_reference_repairs'] = is_array( $result['content_reference_repairs'] ?? null ) ? $result['content_reference_repairs'] : $payload['content_reference_repairs'];
		$payload['verification'] = $this->media_file_operation_verification( $attachment_id, $payload['after'], $payload['current_backup'], $payload['content_reference_repairs'] );
		if ( function_exists( 'do_action' ) && $this->media_file_operation_is_verified( $payload['verification'] ) ) {
			do_action( 'npcink_abilities_toolkit_media_file_version_changed', $attachment_id, array(
				'replacement_id' => (string) ( $payload['replacement_id'] ?? '' ),
				'new_media_fingerprint' => (string) ( $payload['after']['media_fingerprint'] ?? '' ),
				'derived_from_media_fingerprint' => (string) ( $payload['before']['media_fingerprint'] ?? '' ),
			) );
		}
		$payload['history'] = $this->get_media_file_replacement_history( $attachment_id );
		$payload['dry_run'] = false;
		unset( $payload['preview'] );
		return $payload;
	}

	/**
	 * Reuses the exact attachment/reference drift gate for a local backup restore.
	 *
	 * @param int                 $attachment_id Attachment id.
	 * @param array<string,mixed> $plan Restore plan.
	 * @param array<string,mixed> $reviewed_repairs Reviewed reference repairs.
	 * @param array<string,mixed> $rollback_state Captured local state.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function validate_media_backup_restore_precommit_state( $attachment_id, array $plan, array $reviewed_repairs, array $rollback_state ) {
		$result = $this->validate_cloud_media_adoption_precommit_state( $attachment_id, $plan, $reviewed_repairs, $rollback_state );
		$drift_fields = array();
		if ( is_wp_error( $result ) ) {
			$data = $result->get_error_data();
			$drift_fields = (array) ( is_array( $data ) ? ( $data['drift_fields'] ?? array() ) : array() );
		}
		if ( ( $plan['_backup_file_snapshot'] ?? null ) !== $this->cloud_media_current_file_snapshot( (string) ( $plan['_backup_path'] ?? '' ) ) ) {
			$drift_fields[] = 'backup_file';
		}
		if ( ( $plan['_target_file_snapshot'] ?? null ) !== $this->cloud_media_current_file_snapshot( (string) ( $plan['_target_path'] ?? '' ) ) ) {
			$drift_fields[] = 'restore_target_file';
		}
		$drift_fields = array_values( array_unique( $drift_fields ) );
		if ( empty( $drift_fields ) && ! is_wp_error( $result ) ) {
			return $result;
		}
		return new \WP_Error(
			'npcink_abilities_toolkit_media_restore_precommit_drift',
			__( 'The attachment or its reviewed content references changed before the media backup restore could commit.', 'npcink-abilities-toolkit' ),
			array(
				'status'       => 409,
				'drift_fields' => $drift_fields,
				'cause'        => is_wp_error( $result ) ? $result->get_error_code() : '',
			)
		);
	}

	/**
	 * Restores files that a local media restore overwrote before a later failure.
	 *
	 * @return true|\WP_Error
	 */
	private function restore_media_backup_overwritten_files( $manifest ) {
		$overwritten = is_object( $manifest ) && isset( $manifest->overwritten_files ) && is_array( $manifest->overwritten_files ) ? array_reverse( $manifest->overwritten_files ) : array();
		$failures = array();
		$conflicts = array();
		foreach ( $overwritten as $entry ) {
			$entry = is_array( $entry ) ? $entry : array();
			$target_path = (string) ( $entry['target_path'] ?? '' );
			$compensation_path = (string) ( $entry['compensation_path'] ?? '' );
			$after = is_array( $entry['after'] ?? null ) ? $entry['after'] : array();
			if ( empty( $after ) || $after !== $this->cloud_media_current_file_snapshot( $target_path ) ) {
				$conflicts[] = basename( $target_path );
				continue;
			}
			if ( ! is_readable( $compensation_path ) || ! $this->copy_media_file( $compensation_path, $target_path, array( 'operation' => 'restore_media_backup', 'step' => 'compensate_restore_target' ) ) ) {
				$failures[] = basename( $target_path );
				continue;
			}
			$before = is_array( $entry['before'] ?? null ) ? $entry['before'] : array();
			if ( $before !== $this->cloud_media_current_file_snapshot( $target_path ) ) {
				$failures[] = basename( $target_path );
			}
		}
		if ( ! empty( $conflicts ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_cloud_adoption_cleanup_conflict', __( 'Concurrent filesystem state prevented media restore compensation.', 'npcink-abilities-toolkit' ), array( 'status' => 409, 'conflicts' => $conflicts ) );
		}
		if ( ! empty( $failures ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_cloud_adoption_cleanup_failed', __( 'The failed media restore could not restore every overwritten file.', 'npcink-abilities-toolkit' ), array( 'status' => 500, 'failures' => $failures ) );
		}
		return true;
	}

	/**
	 * Preserves a restore failure when state and file compensation succeed.
	 *
	 * @return \WP_Error
	 */
	private function media_backup_restore_failure_with_cleanup( \WP_Error $cause, $attachment_id, array $plan, array $rollback_state ) {
		if ( 'npcink_abilities_toolkit_media_restore_precommit_drift' === $cause->get_error_code() ) {
			return $this->cloud_media_adoption_precommit_failure_with_discard(
				$cause,
				$plan['_cloud_batch_manifest'] ?? null,
				$attachment_id
			);
		}
		return $this->cloud_media_adoption_failure_with_cleanup( $cause, $attachment_id, $plan, array(), $rollback_state );
	}

		/**
		 * Executes an approved restore by copying a backup to its original path.
		 *
		 * @param int                 $attachment_id Attachment id.
		 * @param array<string,mixed> $plan Restore plan.
		 * @return array<string,mixed>|\WP_Error
		 */
	private function execute_media_backup_restore( $attachment_id, array $plan ) {
			$attachment_id = absint( $attachment_id );
			$current = is_array( $plan['_current'] ?? null ) ? $plan['_current'] : array();
			$storage_ready = $this->validate_media_storage_commit_ready( $current );
			if ( is_wp_error( $storage_ready ) ) {
				return $storage_ready;
			}
			$current_path = (string) ( $current['file_path'] ?? '' );
			$backup_path = (string) ( $plan['_backup_path'] ?? '' );
			$target_relative = $this->normalize_media_relative_file( (string) ( $plan['_target_relative_file'] ?? '' ) );
			$target_path = (string) ( $plan['_target_path'] ?? '' );
			$current_backup_relative = $this->normalize_media_relative_file( (string) ( $plan['_current_backup_relative_file'] ?? '' ) );
			$current_backup_path = $this->media_uploads_path_for_relative_file( $current_backup_relative );
			$batch_manifest = $plan['_cloud_batch_manifest'] ?? null;
			$content_reference_repairs = $this->build_media_content_reference_repairs( $attachment_id, $plan, false );
			$permission_error = $this->validate_media_content_reference_repair_permissions( $content_reference_repairs );
			if ( is_wp_error( $permission_error ) ) {
				return $permission_error;
			}
			if ( '' === $current_path || ! is_readable( $current_path ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_current_media_file_unavailable', __( 'The current attachment file is unavailable for restore backup.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
			}
			if ( '' === $backup_path || ! is_readable( $backup_path ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_backup_file_unavailable', __( 'The backup file is unavailable for restore.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
			}
			if ( '' === $target_relative || '' === $target_path ) {
				return new \WP_Error( 'npcink_abilities_toolkit_restore_target_unavailable', __( 'The original media file path is unavailable for restore.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
			}
			if ( file_exists( $target_path ) && 'overwrite' !== (string) ( $plan['conflict_mode'] ?? 'fail' ) && md5_file( $target_path ) !== md5_file( $backup_path ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_restore_target_exists', __( 'The original media file path already exists with different content.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
			}
			$current_backup_context = array(
				'operation'     => 'restore_media_backup',
				'step'          => 'backup_current',
				'attachment_id' => $attachment_id,
				'relative_file' => $current_backup_relative,
			);
			$current_backup_created = '' !== $current_backup_path && $this->ensure_media_directory( dirname( $current_backup_path ) )
				? $this->copy_cloud_media_file_exclusive( $current_path, $current_backup_path, $current_backup_context )
				: new \WP_Error( 'npcink_abilities_toolkit_media_backup_failed', __( 'The current attachment file could not be backed up before restore.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
			if ( is_wp_error( $current_backup_created ) ) {
				return $current_backup_created;
			}
			$this->add_cloud_media_created_file_to_manifest( $batch_manifest, $current_backup_created );

			$late_precommit = $this->validate_media_backup_restore_precommit_state(
				$attachment_id,
				$plan,
				is_array( $plan['_restore_precommit_repairs'] ?? null ) ? $plan['_restore_precommit_repairs'] : array(),
				is_array( $plan['_restore_precommit_state'] ?? null ) ? $plan['_restore_precommit_state'] : array()
			);
			if ( is_wp_error( $late_precommit ) ) {
				return $late_precommit;
			}
			$current = is_array( $late_precommit['current'] ?? null ) ? $late_precommit['current'] : $current;
			$current_path = (string) ( $current['file_path'] ?? $current_path );
			$content_reference_repairs = is_array( $late_precommit['content_reference_repairs'] ?? null ) ? $late_precommit['content_reference_repairs'] : $content_reference_repairs;

			$target_copy = $this->copy_media_backup_restore_target( $backup_path, $target_path, $target_relative, $attachment_id, $plan, $batch_manifest );
			if ( is_wp_error( $target_copy ) ) {
				return $target_copy;
			}

			$after = is_array( $plan['after'] ?? null ) ? $plan['after'] : array();
			$after['filesize_bytes'] = absint( filesize( $target_path ) );
			$after['media_fingerprint'] = $this->normalize_media_sha256( (string) hash_file( 'sha256', $target_path ) );
			$current_backup = is_array( $plan['current_backup'] ?? null ) ? $plan['current_backup'] : array();
			$current_backup['filesize_bytes'] = absint( filesize( $current_backup_path ) );
			$updated = $this->update_media_file_pointer( $attachment_id, $target_relative, (string) ( $after['mime_type'] ?? '' ), $after, $batch_manifest );
			if ( is_wp_error( $updated ) ) {
				return $updated;
			}
			$content_reference_repairs = $this->apply_media_content_reference_repairs( $content_reference_repairs, $batch_manifest );
			if ( is_wp_error( $content_reference_repairs ) ) {
				return $content_reference_repairs;
			}
			$history_updated = $this->record_media_backup_restore_history(
				$attachment_id,
				(string) ( $plan['replacement_id'] ?? '' ),
				array(
					'replacement_id'     => (string) ( $plan['restore_id'] ?? '' ),
					'operation'          => 'restore_media_backup',
					'status'             => 'active',
					'replaced_at_gmt'    => gmdate( 'c' ),
					'rolled_back_at_gmt' => '',
					'restored_from'      => (string) ( $plan['replacement_id'] ?? '' ),
					'before'             => is_array( $plan['before'] ?? null ) ? $plan['before'] : array(),
					'after'              => $after,
					'backup'             => $current_backup,
					'new_media_fingerprint' => (string) ( $after['media_fingerprint'] ?? '' ),
					'derived_from_media_fingerprint' => (string) ( $plan['before']['media_fingerprint'] ?? '' ),
					'transform_type' => 'restore',
					'visual_reuse_policy' => 'requires_reidentification',
					'transform_facts' => array( 'restore_from_backup' => true ),
					'backup_cleanup_policy' => self::MEDIA_BACKUP_CLEANUP_MANUAL === (string) ( $plan['_history']['backup_cleanup_policy'] ?? '' )
						? self::MEDIA_BACKUP_CLEANUP_MANUAL
						: self::MEDIA_BACKUP_CLEANUP_AUTOMATIC,
				),
				$batch_manifest
			);
			if ( is_wp_error( $history_updated ) ) {
				return $history_updated;
			}
			$finalized = $this->finalize_media_backup_restore_files( $batch_manifest );
			if ( is_wp_error( $finalized ) ) {
				return $finalized;
			}

			return array(
				'restored'       => true,
				'rolled_back'    => true,
				'after'          => $after,
				'backup'         => is_array( $plan['backup'] ?? null ) ? $plan['backup'] : array(),
				'current_backup' => $current_backup,
				'content_reference_repairs' => $content_reference_repairs,
			);
		}

	/**
	 * Copies a selected backup to the restore target and records file compensation.
	 *
	 * @return true|\WP_Error
	 */
	private function copy_media_backup_restore_target( $backup_path, $target_path, $target_relative, $attachment_id, array $plan, $batch_manifest ) {
		$backup_path = (string) $backup_path;
		$target_path = (string) $target_path;
		$target_relative = $this->normalize_media_relative_file( $target_relative );
		$attachment_id = absint( $attachment_id );
		if ( '' === $target_path || ! $this->ensure_media_directory( dirname( $target_path ) ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_restore_failed', __( 'The backup file could not be restored to the original path.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
		}
		$context = array(
			'operation'     => 'restore_media_backup',
			'step'          => 'restore_backup',
			'attachment_id' => $attachment_id,
			'relative_file' => $target_relative,
		);
		$expected_backup = is_array( $plan['_backup_file_snapshot'] ?? null ) ? $plan['_backup_file_snapshot'] : array();
		$expected_target = is_array( $plan['_target_file_snapshot'] ?? null ) ? $plan['_target_file_snapshot'] : array();
		$drift_fields = array();
		if ( $expected_backup !== $this->cloud_media_current_file_snapshot( $backup_path ) ) {
			$drift_fields[] = 'backup_file';
		}
		if ( $expected_target !== $this->cloud_media_current_file_snapshot( $target_path ) ) {
			$drift_fields[] = 'restore_target_file';
		}
		if ( ! empty( $drift_fields ) ) {
			return new \WP_Error(
				'npcink_abilities_toolkit_media_restore_precommit_drift',
				__( 'The selected backup or restore target changed before the restore copy began.', 'npcink-abilities-toolkit' ),
				array( 'status' => 409, 'drift_fields' => $drift_fields )
			);
		}
		if ( ! file_exists( $target_path ) ) {
			$created = $this->copy_cloud_media_file_exclusive( $backup_path, $target_path, $context );
			if ( is_wp_error( $created ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_media_restore_failed', __( 'The backup file could not be restored to the original path.', 'npcink-abilities-toolkit' ), array( 'status' => 500, 'cause' => $created->get_error_code() ) );
			}
			$this->add_cloud_media_created_file_to_manifest( $batch_manifest, $created );
			if ( ! $this->cloud_media_file_bytes_match( $expected_backup, $this->cloud_media_current_file_snapshot( $target_path ) ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_media_restore_failed', __( 'The restored file did not match the reviewed backup bytes.', 'npcink-abilities-toolkit' ), array( 'status' => 409, 'cause' => 'backup_file_drift' ) );
			}
			return true;
		}

		$compensation_relative = $this->backup_relative_file_for_current_media(
			array( 'relative_file' => $target_relative ),
			(string) ( $plan['restore_id'] ?? '' ) . '-target',
			'npcink-abilities-toolkit-restore-compensation'
		);
		$compensation_path = $this->media_uploads_path_for_relative_file( $compensation_relative );
		$compensation = '' !== $compensation_path && $this->ensure_media_directory( dirname( $compensation_path ) )
			? $this->copy_cloud_media_file_exclusive( $target_path, $compensation_path, array_merge( $context, array( 'step' => 'backup_restore_target', 'relative_file' => $compensation_relative ) ) )
			: new \WP_Error( 'npcink_abilities_toolkit_media_restore_failed', __( 'The existing restore target could not be preserved for compensation.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
		if ( is_wp_error( $compensation ) ) {
			return $compensation;
		}
		$this->add_cloud_media_created_file_to_manifest( $batch_manifest, $compensation );
		if (
			$expected_target !== $this->cloud_media_current_file_snapshot( $target_path )
			|| ! $this->cloud_media_file_bytes_match( $expected_target, $this->cloud_media_current_file_snapshot( $compensation_path ) )
		) {
			return new \WP_Error(
				'npcink_abilities_toolkit_media_restore_precommit_drift',
				__( 'The restore target changed while its compensation copy was being created.', 'npcink-abilities-toolkit' ),
				array( 'status' => 409, 'drift_fields' => array( 'restore_target_file' ) )
			);
		}
		$overwritten = is_object( $batch_manifest ) && isset( $batch_manifest->overwritten_files ) && is_array( $batch_manifest->overwritten_files ) ? $batch_manifest->overwritten_files : array();
		$overwritten_entry = array(
			'target_path'       => $target_path,
			'compensation_path' => $compensation_path,
			'before'            => $expected_target,
		);
		$copied = $this->copy_media_file( $backup_path, $target_path, $context );
		$overwritten_entry['after'] = $this->cloud_media_current_file_snapshot( $target_path );
		if ( $overwritten_entry['after'] !== $expected_target ) {
			$overwritten[] = $overwritten_entry;
			$batch_manifest->overwritten_files = $overwritten;
		}
		if ( ! $copied ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_restore_failed', __( 'The backup file could not be restored to the original path.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
		}
		if ( ! $this->cloud_media_file_bytes_match( $expected_backup, $overwritten_entry['after'] ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_restore_failed', __( 'The restored file did not match the reviewed backup bytes.', 'npcink-abilities-toolkit' ), array( 'status' => 409, 'cause' => 'backup_file_drift' ) );
		}
		return true;
	}

	/**
	 * Marks the selected replacement rolled back and appends the new restore record.
	 *
	 * @return true|\WP_Error
	 */
	private function record_media_backup_restore_history( $attachment_id, $replacement_id, array $record, $batch_manifest ) {
		$attachment_id = absint( $attachment_id );
		$replacement_id = sanitize_text_field( (string) $replacement_id );
		$history = $this->get_media_file_replacement_history( $attachment_id );
		$matched = false;
		foreach ( $history as &$existing ) {
			if ( $replacement_id === (string) ( $existing['replacement_id'] ?? '' ) ) {
				$existing['status'] = 'rolled_back';
				$existing['rolled_back_at_gmt'] = gmdate( 'c' );
				$matched = true;
			}
		}
		unset( $existing );
		if ( ! $matched ) {
			return new \WP_Error( 'npcink_abilities_toolkit_replacement_not_found', __( 'Replacement history was not found for restore.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
		}
		$history[] = $record;
		$history = array_slice( $history, -20 );
		$history_updated = $this->cloud_media_cas_update_post_meta( $batch_manifest, $attachment_id, '_npcink_ai_media_file_replacement_history', $history );
		if ( is_wp_error( $history_updated ) ) {
			return $history_updated;
		}
		return $this->cloud_media_cas_update_post_meta( $batch_manifest, $attachment_id, '_npcink_ai_media_latest_file_replacement', $record );
	}

	/**
	 * Removes transient compensation copies after every restore mutation succeeds.
	 *
	 * The new rollback backup and a newly created restore target remain durable.
	 *
	 * @return true|\WP_Error
	 */
	private function finalize_media_backup_restore_files( $manifest ) {
		if ( ! is_object( $manifest ) ) {
			return true;
		}
		$overwritten = isset( $manifest->overwritten_files ) && is_array( $manifest->overwritten_files ) ? $manifest->overwritten_files : array();
		$compensation_paths = array();
		foreach ( $overwritten as $entry ) {
			$path = (string) ( is_array( $entry ) ? ( $entry['compensation_path'] ?? '' ) : '' );
			if ( '' !== $path ) {
				$compensation_paths[ $path ] = true;
			}
		}
		$created_files = isset( $manifest->created_files ) && is_array( $manifest->created_files ) ? $manifest->created_files : array();
		$remaining = array();
		foreach ( $created_files as $created_file ) {
			$path = (string) ( is_array( $created_file ) ? ( $created_file['path'] ?? '' ) : '' );
			if ( ! isset( $compensation_paths[ $path ] ) ) {
				$remaining[] = $created_file;
				continue;
			}
			if ( ! is_array( $created_file ) || ! $this->discard_cloud_media_created_file( $created_file ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_media_restore_finalize_failed', __( 'The completed media restore could not remove its transient compensation file.', 'npcink-abilities-toolkit' ), array( 'status' => 500, 'file' => basename( $path ) ) );
			}
		}
		$manifest->created_files = $remaining;
		$manifest->overwritten_files = array();
		return true;
	}

	/**
	 * Returns current attachment file state with internal path for commit checks.
	 *
	 * @param int $attachment_id Attachment id.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function current_media_file_state( $attachment_id ) {
		$attachment_id = absint( $attachment_id );
		$metadata = function_exists( 'wp_get_attachment_metadata' ) ? wp_get_attachment_metadata( $attachment_id ) : array();
		$metadata = is_array( $metadata ) ? $metadata : array();
		$relative_file = $this->normalize_media_relative_file( function_exists( 'get_post_meta' ) ? (string) get_post_meta( $attachment_id, '_wp_attached_file', true ) : '' );
		if ( '' === $relative_file ) {
			$relative_file = $this->normalize_media_relative_file( (string) ( $metadata['file'] ?? '' ) );
		}
		$file_path = function_exists( 'get_attached_file' ) ? (string) get_attached_file( $attachment_id ) : '';
		if ( '' !== $file_path && '' !== $relative_file && ! is_readable( $file_path ) ) {
			$file_path = $this->media_uploads_path_for_relative_file( $relative_file );
		}
		if ( '' === $file_path && '' !== $relative_file ) {
			$file_path = $this->media_uploads_path_for_relative_file( $relative_file );
		}
		$mime_type = function_exists( 'get_post_mime_type' ) ? sanitize_text_field( (string) get_post_mime_type( $attachment_id ) ) : '';
		if ( '' === $relative_file && '' === $file_path ) {
			return new \WP_Error( 'npcink_abilities_toolkit_current_media_file_unavailable', __( 'Current attachment file metadata is unavailable.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
		}
		$public_url = function_exists( 'wp_get_attachment_url' ) ? esc_url_raw( (string) wp_get_attachment_url( $attachment_id ) ) : '';
		if ( '' === $public_url && '' !== $relative_file ) {
			$public_url = $this->media_url_for_relative_file( $relative_file );
		}
		$storage = $this->build_media_storage_state( $attachment_id, $relative_file, $file_path, $public_url );
		$hashes = $this->media_content_hashes_for_state( array( 'file_path' => $file_path ) );

		return array(
			'relative_file'   => $relative_file,
			'url'             => $public_url,
			'file_basename'   => $this->sanitize_media_file_name( basename( '' !== $relative_file ? $relative_file : $file_path ) ),
			'file_path'       => $file_path,
			'mime_type'       => $mime_type,
			'width'           => absint( $metadata['width'] ?? 0 ),
			'height'          => absint( $metadata['height'] ?? 0 ),
			'filesize_bytes'  => ( '' !== $file_path && is_readable( $file_path ) ) ? absint( filesize( $file_path ) ) : absint( $metadata['filesize'] ?? 0 ),
			'media_fingerprint' => $this->normalize_media_sha256( (string) ( $hashes['sha256'] ?? '' ) ),
			'metadata_fingerprint' => hash( 'sha256', wp_json_encode( $metadata ) ),
			'metadata'        => $metadata,
			'storage'         => $storage,
		);
	}

		/**
		 * Builds safe public state from an internal media file state.
		 *
		 * @param array<string,mixed> $state Internal state.
	 * @return array<string,mixed>
	 */
	private function public_media_file_state( array $state ) {
		return array(
			'relative_file'  => $this->normalize_media_relative_file( (string) ( $state['relative_file'] ?? '' ) ),
			'url'            => esc_url_raw( (string) ( $state['url'] ?? '' ) ),
			'file_basename'  => $this->sanitize_media_file_name( (string) ( $state['file_basename'] ?? '' ) ),
			'mime_type'      => sanitize_text_field( (string) ( $state['mime_type'] ?? '' ) ),
			'width'          => absint( $state['width'] ?? 0 ),
			'height'         => absint( $state['height'] ?? 0 ),
			'filesize_bytes' => absint( $state['filesize_bytes'] ?? 0 ),
			'media_fingerprint' => $this->normalize_media_sha256( (string) ( $state['media_fingerprint'] ?? '' ) ),
			'metadata_fingerprint' => sanitize_text_field( (string) ( $state['metadata_fingerprint'] ?? '' ) ),
			'storage'        => is_array( $state['storage'] ?? null ) ? $this->sanitize_media_storage_state( $state['storage'] ) : array(),
		);
	}

	/**
	 * Returns replacement history records.
	 *
	 * @param int $attachment_id Attachment id.
	 * @return array<int,array<string,mixed>>
	 */
	private function get_media_file_replacement_history( $attachment_id ) {
		$attachment_id = absint( $attachment_id );
		if ( isset( $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][ $attachment_id ]['_npcink_ai_media_file_replacement_history'] ) && is_array( $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][ $attachment_id ]['_npcink_ai_media_file_replacement_history'] ) ) {
			return array_values( array_filter( $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][ $attachment_id ]['_npcink_ai_media_file_replacement_history'], 'is_array' ) );
		}
		$history = function_exists( 'get_post_meta' ) ? get_post_meta( $attachment_id, '_npcink_ai_media_file_replacement_history', true ) : array();
		return is_array( $history ) ? array_values( array_filter( $history, 'is_array' ) ) : array();
	}

	/**
	 * Finds one replacement history record.
	 *
	 * @param int    $attachment_id Attachment id.
	 * @param string $replacement_id Replacement id.
	 * @return array<string,mixed>
	 */
	private function find_media_file_replacement_history( $attachment_id, $replacement_id ) {
		$replacement_id = sanitize_text_field( (string) $replacement_id );
		foreach ( $this->get_media_file_replacement_history( $attachment_id ) as $record ) {
			if ( $replacement_id === (string) ( $record['replacement_id'] ?? '' ) ) {
				return $record;
			}
		}

		return array();
	}

	/**
	 * Appends one replacement history record.
	 *
	 * @param int                 $attachment_id Attachment id.
	 * @param array<string,mixed> $record History record.
	 * @param mixed               $batch_manifest Optional Cloud adoption mutation manifest.
	 * @return true|\WP_Error
	 */
	private function append_media_file_replacement_history( $attachment_id, array $record, $batch_manifest = null ) {
		$history = $this->get_media_file_replacement_history( $attachment_id );
		$history[] = $record;
		$history = array_slice( $history, -20 );
		if ( is_object( $batch_manifest ) ) {
			$history_updated = $this->cloud_media_cas_update_post_meta( $batch_manifest, $attachment_id, '_npcink_ai_media_file_replacement_history', $history );
			if ( is_wp_error( $history_updated ) ) {
				return $history_updated;
			}
			$latest_updated = $this->cloud_media_cas_update_post_meta( $batch_manifest, $attachment_id, '_npcink_ai_media_latest_file_replacement', $record );
			if ( is_wp_error( $latest_updated ) ) {
				return $latest_updated;
			}
		} elseif ( function_exists( 'update_post_meta' ) ) {
			update_post_meta( absint( $attachment_id ), '_npcink_ai_media_file_replacement_history', $history );
			update_post_meta( absint( $attachment_id ), '_npcink_ai_media_latest_file_replacement', $record );
		}

		return true;
	}

	private function media_transform_type_from_facts( array $facts ) {
		if ( ! empty( $facts['crop_applied'] ) ) { return 'crop'; }
		if ( ! empty( $facts['watermark_applied'] ) ) { return 'watermark'; }
		if ( ! empty( $facts['resize_applied'] ) ) { return 'resize'; }
		return 'encoding';
	}

	/**
	 * Freezes the host-selected backup cleanup policy into a replacement record.
	 * Unknown values fail closed to manual confirmation.
	 *
	 * @param array<string,mixed> $input Adoption input.
	 * @return string
	 */
	private function media_backup_cleanup_policy_for_input( array $input ): string {
		$requested = sanitize_key( (string) ( $input['backup_cleanup_policy'] ?? '' ) );
		if ( 'automatic' === $requested || self::MEDIA_BACKUP_CLEANUP_AUTOMATIC === $requested ) {
			$requested = self::MEDIA_BACKUP_CLEANUP_AUTOMATIC;
		} else {
			$requested = self::MEDIA_BACKUP_CLEANUP_MANUAL;
		}

		$policy = apply_filters( 'npcink_abilities_toolkit_media_backup_cleanup_policy', $requested, $input );
		return self::MEDIA_BACKUP_CLEANUP_AUTOMATIC === $policy ? self::MEDIA_BACKUP_CLEANUP_AUTOMATIC : self::MEDIA_BACKUP_CLEANUP_MANUAL;
	}

	private function media_visual_reuse_policy_from_facts( array $facts ) {
		if ( empty( $facts ) || ! empty( $facts['crop_applied'] ) || ! empty( $facts['watermark_applied'] ) || ( isset( $facts['alpha_preserved'] ) && ! $facts['alpha_preserved'] ) ) {
			return 'requires_reidentification';
		}
		if ( 'lossless' === (string) ( $facts['encoding_mode'] ?? '' ) && empty( $facts['resize_applied'] ) ) {
			return 'reuse';
		}
		$source_width = absint( $facts['source_width'] ?? 0 );
		$source_height = absint( $facts['source_height'] ?? 0 );
		$output_width = absint( $facts['output_width'] ?? 0 );
		$output_height = absint( $facts['output_height'] ?? 0 );
		if ( $source_width > 0 && $source_height > 0 && $output_width >= (int) ceil( $source_width * 0.75 ) && $output_height >= (int) ceil( $source_height * 0.75 ) ) {
			return 'reuse_with_human_check';
		}
		return 'requires_reidentification';
	}

	/**
	 * Marks a replacement history record rolled back.
	 *
	 * @param int    $attachment_id Attachment id.
	 * @param string $replacement_id Replacement id.
	 * @return void
	 */
	private function mark_media_file_replacement_rolled_back( $attachment_id, $replacement_id ) {
		$replacement_id = sanitize_text_field( (string) $replacement_id );
		$history = $this->get_media_file_replacement_history( $attachment_id );
		foreach ( $history as &$record ) {
			if ( $replacement_id === (string) ( $record['replacement_id'] ?? '' ) ) {
				$record['status'] = 'rolled_back';
				$record['rolled_back_at_gmt'] = gmdate( 'c' );
			}
		}
		unset( $record );
		if ( function_exists( 'update_post_meta' ) ) {
			update_post_meta( absint( $attachment_id ), '_npcink_ai_media_file_replacement_history', $history );
		}
	}

	/**
	 * Updates attachment file pointer and metadata.
	 *
	 * @param int                 $attachment_id Attachment id.
	 * @param string              $relative_file Uploads-relative file.
	 * @param string              $mime_type MIME type.
	 * @param array<string,mixed> $state Public file state.
	 * @param mixed               $batch_manifest Optional Cloud adoption batch manifest.
	 * @return true|\WP_Error
	 */
	private function update_media_file_pointer( $attachment_id, $relative_file, $mime_type, array $state, $batch_manifest = null ) {
		$attachment_id = absint( $attachment_id );
		$relative_file = $this->normalize_media_relative_file( $relative_file );
		$file_path = $this->media_uploads_path_for_relative_file( $relative_file );
		if ( '' === $relative_file || '' === $file_path ) {
			return new \WP_Error( 'npcink_abilities_toolkit_replacement_path_invalid', __( 'Replacement file path is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( is_object( $batch_manifest ) ) {
			$pointer_updated = $this->cloud_media_cas_update_post_meta( $batch_manifest, $attachment_id, '_wp_attached_file', $relative_file );
			if ( is_wp_error( $pointer_updated ) ) {
				return $pointer_updated;
			}
		} elseif ( function_exists( 'update_post_meta' ) ) {
			update_post_meta( $attachment_id, '_wp_attached_file', $relative_file );
		}
		$normalized_mime_type = sanitize_text_field( (string) $mime_type );
		$updated = is_object( $batch_manifest )
			? $this->cloud_media_cas_update_attachment_mime( $batch_manifest, $attachment_id, $normalized_mime_type )
			: wp_update_post( array( 'ID' => $attachment_id, 'post_mime_type' => $normalized_mime_type ), true );
		if ( is_wp_error( $updated ) ) {
			return $updated;
		}
			$metadata = array(
				'file'     => $relative_file,
				'width'    => absint( $state['width'] ?? 0 ),
				'height'   => absint( $state['height'] ?? 0 ),
				'filesize' => absint( $state['filesize_bytes'] ?? 0 ),
				'sizes'    => array(),
			);
			if ( is_array( $state['_metadata'] ?? null ) ) {
				$metadata = $state['_metadata'];
			} elseif ( is_readable( $file_path ) ) {
				$pre_generation_files = is_object( $batch_manifest ) ? $this->cloud_media_directory_file_snapshot( dirname( $file_path ) ) : array();
				$generated = $this->generate_attachment_metadata_for_file( $attachment_id, $file_path, ! is_object( $batch_manifest ) );
				if ( ! empty( $generated ) ) {
					$metadata = $generated;
				}
				if ( is_object( $batch_manifest ) ) {
					$this->track_cloud_media_generated_files( $batch_manifest, $metadata, $pre_generation_files );
				}
			}
		if ( is_object( $batch_manifest ) ) {
			$metadata_updated = $this->cloud_media_cas_update_post_meta( $batch_manifest, $attachment_id, '_wp_attachment_metadata', $metadata );
			if ( is_wp_error( $metadata_updated ) ) {
				return $metadata_updated;
			}
		} elseif ( function_exists( 'update_post_meta' ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_metadata', $metadata );
		}

			return true;
		}

		/**
		 * Builds a backup relative file in the dedicated uploads backup directory.
		 *
		 * @param array<string,mixed> $current Current media state.
	 * @param string              $replacement_id Replacement id.
	 * @param string              $backup_suffix Backup suffix.
	 * @return string
	 */
	private function backup_relative_file_for_current_media( array $current, $replacement_id, $backup_suffix ) {
		$current_relative = $this->normalize_media_relative_file( (string) ( $current['relative_file'] ?? '' ) );
		$current_dir = dirname( $current_relative );
		$current_dir = '.' !== $current_dir ? trim( $current_dir, '/' ) : '';
		$backup_dir = 'npcink-abilities-toolkit-backups' . ( '' !== $current_dir ? '/' . $current_dir : '' );
		$basename = $this->sanitize_media_file_name( basename( $current_relative ) );
		$stem = preg_replace( '/\.[^.]+$/', '', $basename );
		$extension = pathinfo( $basename, PATHINFO_EXTENSION );
		$backup_name = $this->sanitize_media_file_name( (string) $stem . '-' . sanitize_key( (string) $backup_suffix ) . '-' . sanitize_key( (string) $replacement_id ) . ( '' !== $extension ? '.' . $extension : '' ) );
		return $backup_dir . '/' . $backup_name;
	}
}
