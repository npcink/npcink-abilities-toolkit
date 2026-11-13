<?php
/**
 * Bounded media replacement and restore planning.
 *
 * @package NpcinkAbilitiesToolkit
 */
namespace Npcink_Abilities_Toolkit\Packages;
if ( ! defined( 'ABSPATH' ) ) { exit; }
trait Media_Replacement_Write_Methods {

	/**
	 * Builds a replacement plan from a recorded optimized derivative.
	 *
	 * @param int                 $attachment_id Attachment id.
	 * @param array<string,mixed> $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function build_media_file_replacement_plan( $attachment_id, array $input ) {
		$attachment_id = absint( $attachment_id );
		$current = $this->current_media_file_state( $attachment_id );
		if ( is_wp_error( $current ) ) {
			return $current;
	}
	$expected_error = $this->validate_media_expected_state( $current, $input );
	if ( is_wp_error( $expected_error ) ) {
		return $expected_error;
	}

	$derivative_relative = $this->normalize_media_relative_file( (string) ( $input['derivative_relative_file'] ?? '' ) );
	if ( '' === $derivative_relative ) {
		return new \WP_Error( 'npcink_abilities_toolkit_derivative_required', __( 'A derivative_relative_file is required for media file replacement.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
	}
	$derivative = $this->find_media_optimized_derivative( $attachment_id, $derivative_relative );
	if ( empty( $derivative ) ) {
		return new \WP_Error( 'npcink_abilities_toolkit_derivative_not_recorded', __( 'The requested derivative is not recorded for this attachment.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
	}
	$expected_derivative_mime = sanitize_text_field( (string) ( $input['expected_derivative_mime_type'] ?? '' ) );
	if ( '' !== $expected_derivative_mime && $expected_derivative_mime !== (string) ( $derivative['mime_type'] ?? '' ) ) {
		return new \WP_Error( 'npcink_abilities_toolkit_derivative_mime_mismatch', __( 'The derivative MIME type did not match the expected value.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
	}

	$replacement_id = 'media_replace_' . gmdate( 'Ymd_His' ) . '_' . substr( md5( $attachment_id . '|' . $derivative_relative . '|' . microtime( true ) ), 0, 8 );
	$backup_suffix = sanitize_key( (string) ( $input['backup_suffix'] ?? 'npcink-abilities-toolkit-backup' ) );
	$backup_suffix = '' !== $backup_suffix ? substr( $backup_suffix, 0, 48 ) : 'npcink-abilities-toolkit-backup';
	$backup_relative = $this->backup_relative_file_for_current_media( $current, $replacement_id, $backup_suffix );
	$after = $this->media_file_state_from_derivative( $attachment_id, $derivative );

	return array(
		'replacement_id' => $replacement_id,
		'before'         => $this->public_media_file_state( $current ),
		'after'          => $after,
		'backup'         => array(
			'relative_file' => $backup_relative,
			'url'           => $this->media_url_for_relative_file( $backup_relative ),
			'mime_type'     => (string) ( $current['mime_type'] ?? '' ),
			'width'         => absint( $current['width'] ?? 0 ),
			'height'        => absint( $current['height'] ?? 0 ),
		),
		'_current'       => $current,
		'_derivative'    => $derivative,
			'_backup_relative_file' => $backup_relative,
		);
	}

		/**
		 * Builds a governed rename plan for the current attachment main file.
		 *
		 * @param int                 $attachment_id Attachment id.
		 * @param array<string,mixed> $input Input args.
		 * @return array<string,mixed>|\WP_Error
		 */
	private function build_media_file_rename_plan( $attachment_id, array $input ) {
		$attachment_id = absint( $attachment_id );
		$current = $this->current_media_file_state( $attachment_id );
		if ( is_wp_error( $current ) ) {
			return $current;
		}
		$expected_error = $this->validate_media_expected_state( $current, $input );
		if ( is_wp_error( $expected_error ) ) {
			return $expected_error;
		}
		$hash_error = $this->validate_media_expected_hashes( $current, $input );
		if ( is_wp_error( $hash_error ) ) {
			return $hash_error;
		}

		$current_relative = $this->normalize_media_relative_file( (string) ( $current['relative_file'] ?? '' ) );
		$current_path = (string) ( $current['file_path'] ?? '' );
		if ( '' === $current_relative || '' === $current_path ) {
			return new \WP_Error( 'npcink_abilities_toolkit_current_media_file_unavailable', __( 'Current attachment file metadata is unavailable.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
		}

		$raw_target = trim( (string) ( $input['target_file_name'] ?? '' ) );
		if ( '' === $raw_target || basename( str_replace( '\\', '/', $raw_target ) ) !== $raw_target ) {
			return new \WP_Error( 'npcink_abilities_toolkit_target_file_name_invalid', __( 'Target file name must be a file basename, not a path.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		$target_basename = $this->sanitize_media_file_name( $raw_target );
		if ( '' === $target_basename ) {
			return new \WP_Error( 'npcink_abilities_toolkit_target_file_name_invalid', __( 'Target file name is invalid after sanitization.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$current_basename = $this->sanitize_media_file_name( basename( $current_relative ) );
		$current_extension = strtolower( pathinfo( $current_basename, PATHINFO_EXTENSION ) );
		$target_extension = strtolower( pathinfo( $target_basename, PATHINFO_EXTENSION ) );
		if ( '' === $target_extension && '' !== $current_extension ) {
			$target_basename .= '.' . $current_extension;
			$target_extension = $current_extension;
		}
		if ( '' !== $current_extension && $target_extension !== $current_extension ) {
			return new \WP_Error( 'npcink_abilities_toolkit_target_extension_mismatch', __( 'Target file extension must match the current media file extension.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( $target_basename === $current_basename ) {
			return new \WP_Error( 'npcink_abilities_toolkit_no_changes', __( 'Target file name matches the current file name.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$dir = dirname( $current_relative );
		$dir = '.' !== $dir ? trim( $dir, '/' ) : '';
		$target_dir = dirname( $current_path );
		$conflict_mode = sanitize_key( (string) ( $input['conflict_mode'] ?? 'fail' ) );
		$conflict_mode = in_array( $conflict_mode, array( 'fail', 'unique' ), true ) ? $conflict_mode : 'fail';
		$target_path = $this->trailingslashit_value( $target_dir ) . $target_basename;
		if ( file_exists( $target_path ) ) {
			if ( 'unique' !== $conflict_mode ) {
				return new \WP_Error( 'npcink_abilities_toolkit_target_file_exists', __( 'Target media file already exists.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
			}
			$target_basename = $this->unique_media_basename( $target_dir, $target_basename );
		}

		$target_relative = '' !== $dir ? $dir . '/' . $target_basename : $target_basename;
		$target_path = $this->media_uploads_path_for_relative_file( $target_relative );
		$rename_id = 'media_rename_' . gmdate( 'Ymd_His' ) . '_' . substr( md5( $attachment_id . '|' . $current_relative . '|' . $target_relative . '|' . microtime( true ) ), 0, 8 );
		$backup_suffix = sanitize_key( (string) ( $input['backup_suffix'] ?? 'npcink-abilities-toolkit-rename-backup' ) );
		$backup_suffix = '' !== $backup_suffix ? substr( $backup_suffix, 0, 48 ) : 'npcink-abilities-toolkit-rename-backup';
		$backup_relative = $this->backup_relative_file_for_current_media( $current, $rename_id, $backup_suffix );
		$hashes = $this->media_content_hashes_for_state( $current );
		$before = $this->public_media_file_state( $current );
		$before['content_hashes'] = $hashes;
		$after = $before;
		$after['relative_file'] = $target_relative;
		$after['url'] = $this->media_url_for_relative_file( $target_relative );
		$after['file_basename'] = $target_basename;

		return array(
			'rename_id'      => $rename_id,
			'conflict_mode'  => $conflict_mode,
			'before'         => $before,
			'after'          => $after,
			'backup'         => array(
				'relative_file' => $backup_relative,
				'url'           => $this->media_url_for_relative_file( $backup_relative ),
				'mime_type'     => (string) ( $current['mime_type'] ?? '' ),
				'width'         => absint( $current['width'] ?? 0 ),
				'height'        => absint( $current['height'] ?? 0 ),
				'content_hashes' => $hashes,
			),
			'_current'       => $current,
			'_target_relative_file' => $target_relative,
			'_target_path'   => $target_path,
			'_backup_relative_file' => $backup_relative,
		);
	}

		/**
		 * Builds a restore plan that copies a recorded backup back to its original path.
		 *
		 * @param int                 $attachment_id Attachment id.
		 * @param array<string,mixed> $input Input args.
		 * @return array<string,mixed>|\WP_Error
		 */
	private function build_media_backup_restore_plan( $attachment_id, array $input ) {
		$attachment_id = absint( $attachment_id );
		$current = $this->current_media_file_state( $attachment_id );
		if ( is_wp_error( $current ) ) {
			return $current;
		}
		$expected_error = $this->validate_media_expected_state( $current, $input );
		if ( is_wp_error( $expected_error ) ) {
			return $expected_error;
		}

		$backup_id = sanitize_text_field( (string) ( $input['backup_id'] ?? '' ) );
		if ( '' === $backup_id ) {
			return new \WP_Error( 'npcink_abilities_toolkit_backup_id_required', __( 'A backup_id is required for media backup restore.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		$history = $this->find_media_file_replacement_history( $attachment_id, $backup_id );
		if ( empty( $history ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_replacement_not_found', __( 'Replacement history was not found for restore.', 'npcink-abilities-toolkit' ), array( 'status' => 404 ) );
		}

		$temporary = is_array( $history['temporary_backup'] ?? null ) ? $history['temporary_backup'] : array();
		if ( $temporary ) {
			$valid = $this->validate_temporary_media_restore( $temporary );
			if ( is_wp_error( $valid ) ) { return $valid; }
		}
		$backup = is_array( $history['backup'] ?? null ) ? $history['backup'] : array();
		$backup_relative = $this->normalize_media_relative_file( (string) ( $backup['relative_file'] ?? '' ) );
		$backup_path = $this->media_uploads_path_for_relative_file( $backup_relative );
		if ( '' === $backup_relative || '' === $backup_path || ! is_readable( $backup_path ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_backup_file_unavailable', __( 'The backup file is unavailable for restore.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
		}

		$original = is_array( $history['before'] ?? null ) ? $history['before'] : array();
		$target_relative = $this->normalize_media_relative_file( (string) ( $original['relative_file'] ?? '' ) );
		$target_path = $this->media_uploads_path_for_relative_file( $target_relative );
		if ( '' === $target_relative || '' === $target_path ) {
			return new \WP_Error( 'npcink_abilities_toolkit_restore_target_unavailable', __( 'The original media file path is unavailable for restore.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
		}

		$conflict_mode = sanitize_key( (string) ( $input['target_conflict_mode'] ?? 'fail' ) );
		$conflict_mode = in_array( $conflict_mode, array( 'fail', 'overwrite' ), true ) ? $conflict_mode : 'fail';
		if ( file_exists( $target_path ) && 'overwrite' !== $conflict_mode && md5_file( $target_path ) !== md5_file( $backup_path ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_restore_target_exists', __( 'The original media file path already exists with different content.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
		}

		$restore_temporary = $temporary ? $this->plan_temporary_media_backup( $current, array( 'attachment_id' => $attachment_id, 'backup_lifecycle' => 'temporary_7_days.v1', 'expected_current_media_fingerprint' => $current['media_fingerprint'] ) ) : array();
		if ( is_wp_error( $restore_temporary ) ) { return $restore_temporary; }
		$restore_id = 'media_restore_' . gmdate( 'Ymd_His' ) . '_' . substr( md5( $attachment_id . '|' . $backup_id . '|' . $target_relative . '|' . microtime( true ) ), 0, 8 );
		$current_backup_relative = $restore_temporary ? (string) $current['relative_file'] : $this->backup_relative_file_for_current_media( $current, $restore_id, 'npcink-abilities-toolkit-restore-backup' );
		$after = array(
			'relative_file'  => $target_relative,
			'url'            => $this->media_url_for_relative_file( $target_relative ),
			'file_basename'  => $this->sanitize_media_file_name( basename( $target_relative ) ),
			'mime_type'      => sanitize_text_field( (string) ( $backup['mime_type'] ?? $original['mime_type'] ?? '' ) ),
			'width'          => absint( $backup['width'] ?? $original['width'] ?? 0 ),
			'height'         => absint( $backup['height'] ?? $original['height'] ?? 0 ),
			'filesize_bytes' => absint( filesize( $backup_path ) ),
		);

		return array(
			'replacement_id' => $backup_id,
			'restore_id'     => $restore_id,
			'conflict_mode'  => $conflict_mode,
			'before'         => $this->public_media_file_state( $current ),
			'after'          => $after,
			'backup'         => $backup,
			'current_backup' => array(
				'relative_file' => $current_backup_relative,
				'url'           => $this->media_url_for_relative_file( $current_backup_relative ),
				'mime_type'     => (string) ( $current['mime_type'] ?? '' ),
				'width'         => absint( $current['width'] ?? 0 ),
				'height'        => absint( $current['height'] ?? 0 ),
			),
			'_current'       => $current,
			'_history'       => $history,
			'temporary_backup' => $restore_temporary,
			'_restore_metadata' => $temporary['metadata'] ?? null,
			'_backup_relative_file' => $backup_relative,
			'_backup_path'   => $backup_path,
			'_backup_file_snapshot' => $this->cloud_media_current_file_snapshot( $backup_path ),
			'_target_relative_file' => $target_relative,
			'_target_path'   => $target_path,
			'_target_file_snapshot' => $this->cloud_media_current_file_snapshot( $target_path ),
			'_current_backup_relative_file' => $current_backup_relative,
		);
	}

		/**
		 * Executes a file replacement by switching the attachment pointer.
	 *
	 * @param int                 $attachment_id Attachment id.
	 * @param array<string,mixed> $plan Replacement plan.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function execute_media_file_replacement( $attachment_id, array $plan ) {
		$attachment_id = absint( $attachment_id );
		$current = is_array( $plan['_current'] ?? null ) ? $plan['_current'] : array();
		$derivative = is_array( $plan['_derivative'] ?? null ) ? $plan['_derivative'] : array();
		$storage_ready = $this->validate_media_storage_commit_ready( $current );
		if ( is_wp_error( $storage_ready ) ) {
			return $storage_ready;
		}
		$current_path = (string) ( $current['file_path'] ?? '' );
		$content_reference_repairs = $this->build_media_content_reference_repairs( $attachment_id, $plan, false );
		$expectation_error = $this->validate_media_content_reference_repair_expectations(
			$content_reference_repairs,
			is_array( $plan['content_reference_repair_expectations'] ?? null ) ? $plan['content_reference_repair_expectations'] : array()
		);
		if ( is_wp_error( $expectation_error ) ) {
			return $expectation_error;
		}
		$permission_error = $this->validate_media_content_reference_repair_permissions( $content_reference_repairs );
		if ( is_wp_error( $permission_error ) ) {
			return $permission_error;
		}
	$backup_relative = $this->normalize_media_relative_file( (string) ( $plan['_backup_relative_file'] ?? '' ) );
	$backup_path = $this->media_uploads_path_for_relative_file( $backup_relative );
	$derivative_relative = $this->normalize_media_relative_file( (string) ( $derivative['relative_file'] ?? '' ) );
	$derivative_path = $this->media_uploads_path_for_relative_file( $derivative_relative );
	$batch_manifest = $plan['_cloud_batch_manifest'] ?? null;
	if ( '' === $current_path || ! is_readable( $current_path ) ) {
		return new \WP_Error( 'npcink_abilities_toolkit_current_media_file_unavailable', __( 'The current attachment file is unavailable for backup.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
	}
	if ( '' === $derivative_path || ! is_readable( $derivative_path ) ) {
		return new \WP_Error( 'npcink_abilities_toolkit_derivative_file_unavailable', __( 'The derivative file is unavailable for replacement.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
	}
	$fresh_current = $this->current_media_file_state( $attachment_id );
	if ( is_wp_error( $fresh_current ) ) {
		return new \WP_Error( 'npcink_abilities_toolkit_media_replace_precommit_drift', __( 'The current media file changed after the replacement was reviewed.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
	}
	$planned_fingerprint = $this->normalize_media_sha256( (string) ( $current['media_fingerprint'] ?? '' ) );
	$fresh_fingerprint = $this->normalize_media_sha256( (string) ( $fresh_current['media_fingerprint'] ?? '' ) );
	if ( '' === $planned_fingerprint || $planned_fingerprint !== $fresh_fingerprint ) {
		return new \WP_Error( 'npcink_abilities_toolkit_media_replace_precommit_drift', __( 'The current media file changed after the replacement was reviewed.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
	}
	$current = $fresh_current;
	$current_path = (string) ( $current['file_path'] ?? $current_path );
	$backup_dir_ready = '' !== $backup_path && $this->ensure_media_directory( dirname( $backup_path ) );
	$backup_context = array(
		'operation'     => 'replace_media_file',
		'step'          => 'backup_current',
		'attachment_id' => $attachment_id,
		'relative_file' => $backup_relative,
	);
	if ( ! empty( $plan['temporary_backup'] ) ) {
		$backup_succeeded = $backup_path === $current_path;
	} elseif ( is_object( $batch_manifest ) && '' !== $backup_path && $backup_dir_ready ) {
		$backup_created_file = $this->copy_cloud_media_file_exclusive( $current_path, $backup_path, $backup_context );
		if ( is_wp_error( $backup_created_file ) ) {
			return $backup_created_file;
		}
		$this->add_cloud_media_created_file_to_manifest( $batch_manifest, $backup_created_file );
		$backup_succeeded = true;
	} else {
		$backup_succeeded = '' !== $backup_path && $backup_dir_ready && $this->copy_media_file( $current_path, $backup_path, $backup_context );
	}
	if ( ! $backup_succeeded ) {
		return new \WP_Error( 'npcink_abilities_toolkit_media_backup_failed', __( 'The current attachment file could not be backed up.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
	}
	if ( is_object( $batch_manifest ) ) {
		$late_precommit = $this->validate_cloud_media_adoption_precommit_state(
			$attachment_id,
			$plan,
			is_array( $plan['_cloud_precommit_repairs'] ?? null ) ? $plan['_cloud_precommit_repairs'] : array(),
			is_array( $plan['_cloud_precommit_state'] ?? null ) ? $plan['_cloud_precommit_state'] : array()
		);
		if ( is_wp_error( $late_precommit ) ) {
			return $late_precommit;
		}
		$current = is_array( $late_precommit['current'] ?? null ) ? $late_precommit['current'] : $current;
		$current_path = (string) ( $current['file_path'] ?? $current_path );
		$content_reference_repairs = is_array( $late_precommit['content_reference_repairs'] ?? null ) ? $late_precommit['content_reference_repairs'] : $content_reference_repairs;
	}

	$after = is_array( $plan['after'] ?? null ) ? $plan['after'] : array();
	$after['filesize_bytes'] = absint( filesize( $derivative_path ) );
	$after['media_fingerprint'] = $this->normalize_media_sha256( (string) hash_file( 'sha256', $derivative_path ) );
	$backup = is_array( $plan['backup'] ?? null ) ? $plan['backup'] : array();
	$backup['filesize_bytes'] = absint( filesize( $backup_path ) );
	$updated = $this->update_media_file_pointer( $attachment_id, $derivative_relative, (string) ( $after['mime_type'] ?? '' ), $after, $batch_manifest );
	if ( is_wp_error( $updated ) ) {
		return $updated;
	}
	$content_reference_repairs = $this->apply_media_content_reference_repairs( $content_reference_repairs, $batch_manifest );
	if ( is_wp_error( $content_reference_repairs ) ) {
		return $content_reference_repairs;
	}
	if ( ! empty( $plan['temporary_backup'] ) ) {
		$valid = $this->validate_temporary_media_restore( $plan['temporary_backup'], false );
		if ( is_wp_error( $valid ) ) { return $valid; }
	}
	$history_updated = $this->append_media_file_replacement_history(
		$attachment_id,
		array(
			'temporary_backup' => $plan['temporary_backup'] ?? array(),
			'replacement_id'     => (string) ( $plan['replacement_id'] ?? '' ),
			'status'             => 'active',
			'replaced_at_gmt'    => gmdate( 'c' ),
			'rolled_back_at_gmt' => '',
			'before'             => is_array( $plan['before'] ?? null ) ? $plan['before'] : array(),
			'after'              => $after,
			'backup'             => $backup,
			'new_media_fingerprint' => (string) $after['media_fingerprint'],
			'derived_from_media_fingerprint' => (string) ( $plan['before']['media_fingerprint'] ?? '' ),
			'transform_type' => $this->media_transform_type_from_facts( is_array( $plan['artifact']['transform_facts'] ?? null ) ? $plan['artifact']['transform_facts'] : array() ),
			'visual_reuse_policy' => $this->media_visual_reuse_policy_from_facts( is_array( $plan['artifact']['transform_facts'] ?? null ) ? $plan['artifact']['transform_facts'] : array() ),
				'transform_facts' => is_array( $plan['artifact']['transform_facts'] ?? null ) ? $plan['artifact']['transform_facts'] : array(),
				'batch_id' => sanitize_text_field( (string) ( $plan['batch_context']['batch_id'] ?? '' ) ),
				'optimization_profile' => sanitize_text_field( (string) ( $plan['batch_context']['optimization_profile'] ?? '' ) ),
				'batch_confirmation_digest' => $this->normalize_media_sha256( (string) ( $plan['batch_context']['batch_confirmation_digest'] ?? '' ) ),
				'backup_cleanup_policy' => (string) ( $plan['batch_context']['backup_cleanup_policy'] ?? self::MEDIA_BACKUP_CLEANUP_AUTOMATIC ),
			),
		$batch_manifest
	);
	if ( is_wp_error( $history_updated ) ) {
		return $history_updated;
	}
	return array(
		'replaced' => true,
		'rolled_back' => false,
		'after'    => $after,
			'backup'   => $backup,
			'content_reference_repairs' => $content_reference_repairs,
		);
	}


}
