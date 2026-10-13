<?php
/** Flagged-media governed deletion methods. */
namespace Npcink_Abilities_Toolkit\Packages\Destructive_Traits;
if ( ! defined( 'ABSPATH' ) ) { exit; }
trait Flagged_Media_Deletion_Methods {
	public function delete_attachment( $input ) {
		$input = is_array( $input ) ? $input : array();
		$attachment_id = absint( $input['attachment_id'] ?? 0 );
		$branch = sanitize_key( (string) ( $input['deletion_policy_branch'] ?? '' ) );
		$reason = is_string( $input['reason'] ?? null ) ? trim( $input['reason'] ) : '';
		$idempotency = is_string( $input['idempotency_key'] ?? null ) ? sanitize_text_field( $input['idempotency_key'] ) : '';
		if ( ! in_array( $branch, array( 'illegal_content_no_backup', 'governed_with_backup' ), true ) || '' === $reason || '' === $idempotency ) {
			return new \WP_Error( 'npcink_abilities_toolkit_flagged_media_input_invalid', __( 'Flagged media deletion requires a valid branch, reason, and idempotency key.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		$attachment = $this->get_media_attachment( $attachment_id );
		if ( is_wp_error( $attachment ) ) { return $attachment; }
		if ( ! current_user_can( 'delete_post', $attachment_id ) ) { return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to delete this attachment.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) ); }
		$relative_file = function_exists( 'get_post_meta' ) ? (string) get_post_meta( $attachment_id, '_wp_attached_file', true ) : '';
		$upload = function_exists( 'wp_upload_dir' ) ? wp_upload_dir() : array();
		$absolute_file = '' !== $relative_file && ! empty( $upload['basedir'] ) ? trailingslashit( $upload['basedir'] ) . ltrim( $relative_file, '/' ) : '';
		$backup_id = 'flagged_delete_' . substr( hash( 'sha256', $attachment_id . '|' . $idempotency ), 0, 24 );
		$preview = array( 'action' => 'delete_attachment', 'attachment_id' => $attachment_id, 'file' => array( 'relative_file' => sanitize_text_field( $relative_file ), 'mime_type' => sanitize_text_field( (string) ( $attachment->post_mime_type ?? '' ) ), 'file_size' => '' !== $absolute_file && is_file( $absolute_file ) ? (int) filesize( $absolute_file ) : 0 ), 'referenced_by' => array( 'posts' => array(), 'settings' => array() ), 'branch' => $branch, 'reason' => sanitize_textarea_field( $reason ), 'idempotency_key' => $idempotency );
		if ( 'governed_with_backup' === $branch ) { $preview['backup_evidence'] = array( 'location' => 'npcink-abilities-toolkit-backups/flagged-media/' . $backup_id, 'retention_window' => '30 days', 'restore_ability_contract' => 'npcink-abilities-toolkit/restore-media-backup' ); } else { $preview['no_backup_attestation'] = array( 'confirmed' => true, 'statement' => 'No backup, local copy, or Cloud artifact will be created for this deletion.' ); }
		$payload = array( 'attachment_id' => $attachment_id, 'deletion_policy_branch' => $branch, 'deleted' => false, 'preview' => $preview );
		if ( $this->should_dry_run( $input ) ) { return $this->dry_run_payload( $payload ); }
		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/delete-attachment', $input );
		if ( is_wp_error( $allowed ) ) { return $allowed; }
		if ( 'governed_with_backup' === $branch ) { $backup = $this->create_flagged_media_deletion_backup( $attachment, $absolute_file, $relative_file, $backup_id, $preview ); if ( is_wp_error( $backup ) ) { return $backup; } }
		$deleted = function_exists( 'wp_delete_attachment' ) ? wp_delete_attachment( $attachment_id, true ) : wp_delete_post( $attachment_id, true );
		if ( ! $deleted ) { return new \WP_Error( 'npcink_abilities_toolkit_media_delete_failed', __( 'Attachment deletion failed.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) ); }
		$payload['deleted'] = true; $payload['dry_run'] = false; unset( $payload['preview'] ); return $payload;
	}
	private function create_flagged_media_deletion_backup( $attachment, string $absolute_file, string $relative_file, string $backup_id, array $preview ) {
		$upload = function_exists( 'wp_upload_dir' ) ? wp_upload_dir() : array(); $base = ! empty( $upload['basedir'] ) ? trailingslashit( $upload['basedir'] ) . 'npcink-abilities-toolkit-backups/flagged-media' : '';
		if ( '' === $base || ! is_file( $absolute_file ) || ! function_exists( 'wp_mkdir_p' ) || ! wp_mkdir_p( $base ) ) { return new \WP_Error( 'npcink_abilities_toolkit_media_backup_failed', __( 'Could not create the governed flagged-media backup.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) ); }
		$backup_file = trailingslashit( $base ) . $backup_id . '.bin'; if ( ! copy( $absolute_file, $backup_file ) ) { return new \WP_Error( 'npcink_abilities_toolkit_media_backup_failed', __( 'Could not copy the governed flagged-media backup.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) ); }
		$manifest = array( 'backup_id' => $backup_id, 'attachment_id' => absint( $attachment->ID ?? 0 ), 'post' => array( 'post_title' => (string) ( $attachment->post_title ?? '' ), 'post_content' => (string) ( $attachment->post_content ?? '' ), 'post_excerpt' => (string) ( $attachment->post_excerpt ?? '' ), 'post_mime_type' => (string) ( $attachment->post_mime_type ?? '' ), 'post_parent' => absint( $attachment->post_parent ?? 0 ) ), 'relative_file' => $relative_file, 'backup_file' => 'npcink-abilities-toolkit-backups/flagged-media/' . $backup_id . '.bin', 'metadata' => function_exists( 'wp_get_attachment_metadata' ) ? wp_get_attachment_metadata( absint( $attachment->ID ?? 0 ) ) : array(), 'preview' => $preview, 'created_at_gmt' => gmdate( 'c' ) );
		$records = function_exists( 'get_option' ) ? get_option( 'npcink_abilities_toolkit_flagged_media_deletion_backups', array() ) : array(); $records = is_array( $records ) ? $records : array(); $records[ $backup_id ] = $manifest; if ( function_exists( 'update_option' ) ) { update_option( 'npcink_abilities_toolkit_flagged_media_deletion_backups', $records, false ); } return true;
	}
}
