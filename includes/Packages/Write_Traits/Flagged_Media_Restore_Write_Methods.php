<?php
/**
 * Restore methods for governed flagged-media deletion manifests.
 */

namespace Npcink_Abilities_Toolkit\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait Flagged_Media_Restore_Write_Methods {
	/**
	 * Restores an attachment whose original post was deleted after a governed backup.
	 *
	 * @param array<string,mixed> $input Input args.
	 * @param int                 $attachment_id Requested attachment id.
	 * @return array<string,mixed>|\WP_Error|null
	 */
	private function restore_flagged_media_deletion_backup( array $input, int $attachment_id ) {
		$backup_id = sanitize_text_field( (string) ( $input['backup_id'] ?? '' ) );
		if ( 1 !== preg_match( '/^flagged_delete_[a-f0-9]{24}$/', $backup_id ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_backup_invalid', __( 'The governed deletion backup id is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$records  = function_exists( 'get_option' ) ? get_option( 'npcink_abilities_toolkit_flagged_media_deletion_backups', array() ) : array();
		$manifest = is_array( $records ) && is_array( $records[ $backup_id ] ?? null ) ? $records[ $backup_id ] : null;
		if ( null === $manifest || $backup_id !== (string) ( $manifest['backup_id'] ?? '' ) || 'governed_with_backup' !== (string) ( $manifest['preview']['branch'] ?? '' ) ) {
			return null;
		}

		$original_id = absint( $manifest['attachment_id'] ?? 0 );
		if ( $attachment_id > 0 && $original_id !== $attachment_id ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_restore_attachment_mismatch', __( 'The deletion backup does not match the requested attachment.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
		}

		$relative        = $this->normalize_media_relative_file( (string) ( $manifest['relative_file'] ?? '' ) );
		$backup_relative = 'npcink-abilities-toolkit-backups/flagged-media/' . $backup_id . '.bin';
		$backup_path     = $this->media_uploads_path_for_relative_file( $backup_relative );
		if ( '' === $relative || '' === $backup_path ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_backup_invalid', __( 'The governed deletion backup path is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
		}
		if ( ! is_readable( $backup_path ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_backup_not_found', __( 'The governed deletion backup file is unavailable.', 'npcink-abilities-toolkit' ), array( 'status' => 404 ) );
		}

		$preview = array(
			'action'                     => 'restore_media_backup',
			'attachment_id'              => $original_id,
			'backup_id'                  => $backup_id,
			'restore_deleted_attachment' => true,
			'target_file'                => $relative,
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( array( 'attachment_id' => $original_id, 'backup_id' => $backup_id, 'restored' => false, 'preview' => $preview ) );
		}

		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/restore-media-backup', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}
		if ( ! function_exists( 'wp_insert_attachment' ) || ! function_exists( 'wp_mkdir_p' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_restore_failed', __( 'WordPress cannot recreate the deleted attachment.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
		}

		$existing_id = absint( $manifest['restored_attachment_id'] ?? 0 );
		if ( $existing_id > 0 && 'attachment' === get_post_type( $existing_id ) ) {
			return array( 'attachment_id' => $existing_id, 'backup_id' => $backup_id, 'restored' => true, 'dry_run' => false, 'recreated' => false, 'idempotent_replay' => true );
		}

		$post              = is_array( $manifest['post'] ?? null ) ? $manifest['post'] : array();
		$post['post_status'] = 'inherit';
		$post['post_type']   = 'attachment';
		$new_id             = wp_insert_attachment( $post, '', absint( $post['post_parent'] ?? 0 ) );
		if ( ! $new_id || is_wp_error( $new_id ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_restore_failed', __( 'Recreating the deleted attachment failed.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
		}

		$target_path = $this->media_uploads_path_for_relative_file( $relative );
		if ( '' === $target_path || ! wp_mkdir_p( dirname( $target_path ) ) || ! copy( $backup_path, $target_path ) ) {
			wp_delete_attachment( $new_id, true );
			return new \WP_Error( 'npcink_abilities_toolkit_media_restore_failed', __( 'Restoring the deleted attachment file failed.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
		}

		if ( function_exists( 'update_post_meta' ) ) {
			update_post_meta( $new_id, '_wp_attached_file', $relative );
			if ( is_array( $manifest['metadata'] ?? null ) ) {
				update_post_meta( $new_id, '_wp_attachment_metadata', $manifest['metadata'] );
			}
		}
		$records[ $backup_id ]['restored_attachment_id'] = absint( $new_id );
		if ( function_exists( 'update_option' ) ) {
			update_option( 'npcink_abilities_toolkit_flagged_media_deletion_backups', $records, false );
		}

		return array( 'attachment_id' => absint( $new_id ), 'backup_id' => $backup_id, 'restored' => true, 'dry_run' => false, 'recreated' => true );
	}
}
