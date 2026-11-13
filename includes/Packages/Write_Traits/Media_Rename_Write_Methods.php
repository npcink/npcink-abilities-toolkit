<?php
/**
 * Media rename execution and failure recovery.
 *
 * @package NpcinkAbilitiesToolkit
 */
namespace Npcink_Abilities_Toolkit\Packages;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
trait Media_Rename_Write_Methods {
	/**
	 * Executes an approved main media file rename.
	 *
	 * @param int                 $attachment_id Attachment id.
	 * @param array<string,mixed> $plan Rename plan.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function execute_media_file_rename( $attachment_id, array $plan ) {
		$attachment_id = absint( $attachment_id );
		$current = is_array( $plan['_current'] ?? null ) ? $plan['_current'] : array();
		$storage_ready = $this->validate_media_storage_commit_ready( $current );
		if ( is_wp_error( $storage_ready ) ) {
			return $storage_ready;
		}
		$current_path = (string) ( $current['file_path'] ?? '' );
		$target_relative = $this->normalize_media_relative_file( (string) ( $plan['_target_relative_file'] ?? '' ) );
		$target_path = (string) ( $plan['_target_path'] ?? '' );
		$backup_relative = $this->normalize_media_relative_file( (string) ( $plan['_backup_relative_file'] ?? '' ) );
		$backup_path = $this->media_uploads_path_for_relative_file( $backup_relative );
		if ( '' === $current_path || ! is_readable( $current_path ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_current_media_file_unavailable', __( 'The current attachment file is unavailable for rename.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
		}
		if ( '' === $target_relative || '' === $target_path ) {
			return new \WP_Error( 'npcink_abilities_toolkit_target_file_name_invalid', __( 'Target media file path is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( file_exists( $target_path ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_target_file_exists', __( 'Target media file already exists.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
		}
		if (
			'' === $backup_path ||
			! $this->ensure_media_directory( dirname( $backup_path ) ) ||
			! $this->copy_media_file(
				$current_path,
				$backup_path,
				array(
					'operation'     => 'rename_media_file',
					'step'          => 'backup_current',
					'attachment_id' => $attachment_id,
					'relative_file' => $backup_relative,
				)
			)
		) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_backup_failed', __( 'The current attachment file could not be backed up.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
		}
		$record = array( 'replacement_id' => (string) $plan['rename_id'], 'operation' => 'rename_media_file', 'status' => 'recovery_required', 'replaced_at_gmt' => gmdate( 'c' ), 'rolled_back_at_gmt' => '', 'before' => $plan['before'], 'after' => $plan['after'], 'backup' => $plan['backup'] );
		if ( ! $this->store_media_rename_record( $attachment_id, $record ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_rename_history_failed', __( 'Rename recovery evidence could not be recorded.', 'npcink-abilities-toolkit' ), array( 'status' => 500, 'backup' => $record['backup'] ) );
		}
		if (
			! $this->ensure_media_directory( dirname( $target_path ) ) ||
			! $this->move_media_file(
				$current_path,
				$target_path,
				array(
					'operation'     => 'rename_media_file',
					'step'          => 'move_current',
					'attachment_id' => $attachment_id,
					'relative_file' => $target_relative,
				)
			)
		) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_rename_failed', __( 'The current attachment file could not be renamed.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
		}

		$after = is_array( $plan['after'] ?? null ) ? $plan['after'] : array();
		$after['filesize_bytes'] = absint( filesize( $target_path ) );
		$after['media_fingerprint'] = $this->normalize_media_sha256( (string) hash_file( 'sha256', $target_path ) );
		$backup = is_array( $plan['backup'] ?? null ) ? $plan['backup'] : array();
		$backup['filesize_bytes'] = absint( filesize( $backup_path ) );
		$pointer_state = $after;
		$pointer_state['_metadata'] = $this->renamed_media_metadata( is_array( $current['metadata'] ?? null ) ? $current['metadata'] : array(), $target_relative, $after );
		$updated = $this->update_media_file_pointer( $attachment_id, $target_relative, (string) ( $after['mime_type'] ?? '' ), $pointer_state );
		if ( is_wp_error( $updated ) ) {
			return $this->recover_failed_media_rename( $attachment_id, $plan, $record, $updated );
		}
		if ( get_post_meta( $attachment_id, '_wp_attached_file', true ) !== $target_relative || get_post_meta( $attachment_id, '_wp_attachment_metadata', true ) !== $pointer_state['_metadata'] ) {
			return $this->recover_failed_media_rename( $attachment_id, $plan, $record, new \WP_Error( 'npcink_abilities_toolkit_media_pointer_unverified', __( 'Renamed attachment metadata could not be verified.', 'npcink-abilities-toolkit' ) ) );
		}
		$record['status'] = 'active';
		$record['after'] = $after;
		$record['backup'] = $backup;
		if ( ! $this->store_media_rename_record( $attachment_id, $record ) ) {
			return $this->recover_failed_media_rename( $attachment_id, $plan, $record, new \WP_Error( 'npcink_abilities_toolkit_media_rename_history_failed', __( 'Rename history could not be saved.', 'npcink-abilities-toolkit' ) ) );
		}

		return array(
			'renamed' => true,
			'after'   => $after,
			'backup'  => $backup,
		);
	}
	/**
	 * Moves a media file using WordPress filesystem-safe deletion semantics.
	 *
	 * @param string $source_path Source path.
	 * @param string $target_path Target path.
	 * @param array<string,mixed> $context Operation context.
	 * @return bool
	 */
	private function move_media_file( $source_path, $target_path, array $context = array() ) {
		$source_path = (string) $source_path;
		$target_path = (string) $target_path;
		if ( ! $this->is_media_uploads_path_allowed( $source_path ) || ! $this->is_media_uploads_path_allowed( $target_path ) ) {
			return false;
		}
		if ( '' === $source_path || '' === $target_path || ! is_readable( $source_path ) || file_exists( $target_path ) ) {
			return false;
		}
		if ( is_wp_error( $this->copy_cloud_media_file_exclusive( $source_path, $target_path, $context ) ) ) {
			return false;
		}
		if ( function_exists( 'wp_delete_file' ) ) {
			wp_delete_file( $source_path );
			return ! file_exists( $source_path ) && is_readable( $target_path );
		}
		wp_delete_file( $target_path );
		return false;
	}

	/** @param int $id @param array<string,mixed> $record @return bool */
	private function store_media_rename_record( $id, array $record ) {
		$history = array_values( array_filter( $this->get_media_file_replacement_history( $id ), static function ( $row ) use ( $record ) { return ( $row['replacement_id'] ?? '' ) !== $record['replacement_id']; } ) );
		$history[] = $record;
		$history = $this->retain_temporary_media_history( $history );
		update_post_meta( $id, '_npcink_ai_media_file_replacement_history', $history );
		update_post_meta( $id, '_npcink_ai_media_latest_file_replacement', $record );
		return get_post_meta( $id, '_npcink_ai_media_file_replacement_history', true ) === $history
			&& get_post_meta( $id, '_npcink_ai_media_latest_file_replacement', true ) === $record;
	}

	/**
	 * Restores the original file and metadata, retaining recovery evidence on failure.
	 * @param int $id Attachment id.
	 * @param array<string,mixed> $plan Reviewed plan.
	 * @param array<string,mixed> $record Backup record.
	 * @param \WP_Error $cause Original failure.
	 * @return \WP_Error
	 */
	private function recover_failed_media_rename( $id, array $plan, array $record, \WP_Error $cause ) {
		$current = (array) $plan['_current'];
		$target = (string) $plan['_target_path'];
		$original = (string) $current['file_path'];
		$expected = (string) ( $plan['before']['content_hashes']['sha256'] ?? '' );
		$hash = is_readable( $target ) ? (string) hash_file( 'sha256', $target ) : '';
		$restored = '' !== $hash && $this->normalize_media_sha256( $hash ) === $this->normalize_media_sha256( $expected ) && $this->move_media_file( $target, $original, array( 'step' => 'rollback_rename' ) );
		if ( $restored ) {
			update_post_meta( $id, '_wp_attached_file', (string) $current['relative_file'] );
			update_post_meta( $id, '_wp_attachment_metadata', (array) $current['metadata'] );
			$mime_restored = (string) get_post_mime_type( $id ) === (string) $current['mime_type'] || ! is_wp_error( wp_update_post( wp_slash( array( 'ID' => $id, 'post_mime_type' => $current['mime_type'] ) ), true ) );
			$restored = $mime_restored && get_post_meta( $id, '_wp_attached_file', true ) === $current['relative_file'] && get_post_meta( $id, '_wp_attachment_metadata', true ) === $current['metadata'];
		}
		$record['status'] = $restored ? 'rolled_back' : 'recovery_required';
		$record['rolled_back_at_gmt'] = $restored ? gmdate( 'c' ) : '';
		$recorded = $this->store_media_rename_record( $id, $record );
		return new \WP_Error( $cause->get_error_code(), $cause->get_error_message(), array( 'status' => 500, 'rolled_back' => $restored, 'recovery_required' => ! $restored, 'recovery_recorded' => $recorded, 'replacement_id' => $record['replacement_id'], 'backup' => $record['backup'] ) );
	}
}
