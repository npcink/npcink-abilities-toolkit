<?php
/**
 * Flagged-media governed deletion methods.
 * @package NpcinkAbilitiesToolkit
 */
namespace Npcink_Abilities_Toolkit\Packages\Destructive_Traits;
use Npcink_Abilities_Toolkit\Packages\Core_Read_Package;
use Npcink_Abilities_Toolkit\Support\Flagged_Media_Backup;
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait Flagged_Media_Deletion_Methods {
	/** @return array<string,mixed> */
	private function flagged_media_definition(): array {
		return array(
				'label'           => __( 'Delete Attachment', 'npcink-abilities-toolkit' ),
				'description'     => __( 'Deletes one media attachment through the dual-branch governed contract, with reference evidence, operator reason, and optional retained backup restore.', 'npcink-abilities-toolkit' ),
				'category'        => 'npcink-abilities-toolkit-write',
				'capability'      => 'delete_posts',
				'required_scopes' => array( 'media.write' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->destructive_meta( false ),
				'input_schema'    => $this->schema(
					array(
						'attachment_id' => array( 'type' => 'integer', 'minimum' => 1 ),
						'deletion_policy_branch' => array( 'type' => 'string', 'enum' => array( 'illegal_content_no_backup', 'governed_with_backup' ) ),
						'reason' => array( 'type' => 'string', 'minLength' => 1, 'maxLength' => 500 ),
						'idempotency_key' => array( 'type' => 'string', 'minLength' => 1, 'maxLength' => 190 ),
					),
					array( 'attachment_id', 'deletion_policy_branch', 'reason', 'idempotency_key' )
				),
				'output_schema'   => $this->schema(
					array(
						'attachment_id' => array( 'type' => 'integer' ),
						'deletion_policy_branch' => array( 'type' => 'string' ),
						'deleted' => array( 'type' => 'boolean' ),
						'dry_run' => array( 'type' => 'boolean' ),
						'preview' => array( 'type' => 'object', 'additionalProperties' => true ),
						'backup_id' => array( 'type' => 'string' ),
						'backup_evidence' => array( 'type' => 'object', 'additionalProperties' => true ),
					),
					array( 'attachment_id', 'deletion_policy_branch', 'deleted', 'dry_run' )
				),
				'execute_callback' => array( $this, 'delete_attachment' ),
			);
	}

	/** @param mixed $input @return array<string,mixed>|\WP_Error */
	public function delete_attachment( $input ) {
		$input = is_array( $input ) ? $input : array();
		$attachment_id = absint( $input['attachment_id'] ?? 0 );
		$branch = (string) ( $input['deletion_policy_branch'] ?? '' );
		$reason = is_string( $input['reason'] ?? null ) ? trim( $input['reason'] ) : '';
		$key = is_string( $input['idempotency_key'] ?? null ) ? trim( $input['idempotency_key'] ) : '';
		if ( ! in_array( $branch, array( 'illegal_content_no_backup', 'governed_with_backup' ), true ) || '' === $reason || strlen( $reason ) > 500 || '' === $key || strlen( $key ) > 190 ) {
			return new \WP_Error( 'npcink_abilities_toolkit_flagged_media_input_invalid', __( 'Flagged media deletion requires a valid branch, bounded reason, and idempotency key.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( ! current_user_can( 'delete_post', $attachment_id ) || ! current_user_can( 'upload_files' ) || ! current_user_can( 'manage_options' ) ) { return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'Flagged media deletion requires administrator, upload and attachment deletion permissions.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) ); }
		$attachment = $this->get_media_attachment( $attachment_id );
		if ( is_wp_error( $attachment ) ) { return $attachment; }
		$backup_id = Flagged_Media_Backup::id( $attachment_id, $key );
		$relative = (string) get_post_meta( $attachment_id, '_wp_attached_file', true );
		$path = Flagged_Media_Backup::path( $relative );
		$read = new Core_Read_Package( $this->categories, $this->abilities );
		$references = $read->flagged_media_reference_summary( $attachment_id );
		if ( is_wp_error( $references ) ) { return $references; }
		$preview = array( 'action' => 'delete_attachment', 'attachment_id' => $attachment_id, 'file' => array( 'relative_file' => sanitize_text_field( $relative ), 'mime_type' => (string) $attachment->post_mime_type, 'file_size' => '' !== $path && is_file( $path ) ? (int) filesize( $path ) : 0, 'upload_date' => (string) ( $attachment->post_date ?? '' ) ), 'referenced_by' => $references, 'branch' => $branch, 'reason' => sanitize_textarea_field( $reason ), 'idempotency_key' => $key );
		if ( 'governed_with_backup' === $branch ) {
			$files = Flagged_Media_Backup::attachment_files( $attachment_id );
			if ( is_wp_error( $files ) ) { return $files; }
			$preview['backup_evidence'] = Flagged_Media_Backup::evidence( $backup_id );
			$preview['backup_evidence']['file_count'] = count( $files );
		} else { $preview['no_backup_attestation'] = array( 'confirmed' => true, 'statement' => 'No backup, local copy, or Cloud artifact will be created for this deletion.' ); }
		$payload = array( 'attachment_id' => $attachment_id, 'deletion_policy_branch' => $branch, 'deleted' => false, 'preview' => $preview );
		if ( $this->should_dry_run( $input ) ) { return $this->dry_run_payload( $payload ); }
		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/delete-attachment', $input );
		if ( is_wp_error( $allowed ) ) { return $allowed; }
		$deletion_files = Flagged_Media_Backup::attachment_files( $attachment_id, false );
		if ( is_wp_error( $deletion_files ) ) { return $deletion_files; }
		$operation = function () use ( $deletion_files, $attachment, $attachment_id, $backup_id, $key, $reason, $branch, $payload, $preview ) {
			if ( 'governed_with_backup' === $branch ) {
				$backup = Flagged_Media_Backup::create( $attachment, $backup_id, $key, $reason );
				if ( is_wp_error( $backup ) ) { return $backup; }
				foreach ( (array) $backup['files'] as $index => $file ) {
					$source = Flagged_Media_Backup::path( (string) $file['relative_file'] );
					$copy = Flagged_Media_Backup::path( Flagged_Media_Backup::PREFIX . $backup_id . '/' . $index . '.bin' );
					if ( '' === $source || '' === $copy || ! is_file( $source ) || ! is_file( $copy ) || hash_file( 'sha256', $source ) !== $file['sha256'] || hash_file( 'sha256', $copy ) !== $file['sha256'] ) { return Flagged_Media_Backup::error( 'predelete_file_drift' ); }
				}
			}
			$deleted = wp_delete_attachment( $attachment_id, true );
			if ( ! $deleted || get_post( $attachment_id ) ) { return new \WP_Error( 'npcink_abilities_toolkit_media_delete_failed', __( 'Attachment deletion failed.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) ); }
			foreach ( $deletion_files as $relative_file ) {
				$file_path = Flagged_Media_Backup::path( $relative_file );
				if ( '' === $file_path || file_exists( $file_path ) || is_link( $file_path ) ) { return Flagged_Media_Backup::error( 'deleted_file_remains', 500 ); }
			}
			$payload['deleted'] = true;
			$payload['dry_run'] = false;
			unset( $payload['preview'] );
			if ( 'governed_with_backup' === $branch ) { $payload['backup_id'] = $backup_id; $payload['backup_evidence'] = $preview['backup_evidence']; }
			return $payload;
		};
		return 'governed_with_backup' === $branch ? Flagged_Media_Backup::locked( $operation ) : $operation();
	}
}
