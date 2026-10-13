<?php
/**
 * Deleted-attachment variant of the existing host-governed restore ability.
 * @package NpcinkAbilitiesToolkit
 */
namespace Npcink_Abilities_Toolkit\Packages;
use Npcink_Abilities_Toolkit\Support\Flagged_Media_Backup;
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait Flagged_Media_Restore_Write_Methods {
	/** @param mixed $input @return array<string,mixed>|\WP_Error */
	public function restore_media_backup( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( Flagged_Media_Backup::valid_id( (string) ( $input['backup_id'] ?? '' ) ) ) {
			return $this->restore_flagged_media_deletion_backup( $input, absint( $input['attachment_id'] ?? 0 ) );
		}
		return $this->restore_existing_media_backup( $input );
	}

	/** @param array<string,mixed> $input @return array<string,mixed>|\WP_Error */
	private function restore_flagged_media_deletion_backup( array $input, int $attachment_id ) {
		if ( ! current_user_can( 'upload_files' ) || ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'Recreating a deleted attachment requires administrator and upload permissions.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}
		$backup_id = (string) ( $input['backup_id'] ?? '' );
		$operation = function () use ( $input, $attachment_id, $backup_id ) {
			$records = Flagged_Media_Backup::records();
			$manifest = $records[ $backup_id ] ?? array();
			if ( $attachment_id <= 0 || $attachment_id !== (int) ( $manifest['attachment_id'] ?? 0 ) || $backup_id !== ( $manifest['backup_id'] ?? '' ) || 'governed_with_backup' !== ( $manifest['branch'] ?? '' ) ) { return Flagged_Media_Backup::error( 'backup_attachment_mismatch' ); }
			if ( (int) ( $manifest['expires_at'] ?? 0 ) <= time() ) { return Flagged_Media_Backup::error( 'backup_expired', 410 ); }
			$files = (array) ( $manifest['files'] ?? array() );
			if ( empty( $files ) || count( $files ) > Flagged_Media_Backup::MAX_FILES ) { return Flagged_Media_Backup::error( 'backup_files_invalid' ); }
			foreach ( $files as $index => $file ) {
				$source = Flagged_Media_Backup::path( Flagged_Media_Backup::PREFIX . $backup_id . '/' . $index . '.bin' );
				$target = Flagged_Media_Backup::path( (string) ( $file['relative_file'] ?? '' ) );
				if ( '' === $source || '' === $target || ! is_file( $source ) || hash_file( 'sha256', $source ) !== ( $file['sha256'] ?? '' ) ) { return Flagged_Media_Backup::error( 'backup_integrity_failed' ); }
			}
			$existing_id = (int) ( $manifest['restored_attachment_id'] ?? 0 );
			if ( get_post( $attachment_id ) ) { return Flagged_Media_Backup::error( 'original_attachment_still_exists' ); }
			$payload = array( 'attachment_id' => $attachment_id, 'backup_id' => $backup_id, 'restored' => false, 'rolled_back' => false, 'original_preserved' => true, 'recreated' => false, 'idempotent_replay' => false );
			if ( $existing_id > 0 && 'restored' === ( $manifest['state'] ?? '' ) ) {
				$post = get_post( $existing_id );
				if ( ! $post || 'attachment' !== $post->post_type || (string) get_post_meta( $existing_id, '_wp_attached_file', true ) !== (string) $manifest['relative_file'] ) { return Flagged_Media_Backup::error( 'restored_attachment_drift' ); }
				foreach ( $files as $file ) {
					$target = Flagged_Media_Backup::path( (string) $file['relative_file'] );
					if ( '' === $target || ! is_file( $target ) || hash_file( 'sha256', $target ) !== $file['sha256'] ) { return Flagged_Media_Backup::error( 'restored_file_drift' ); }
				}
				$payload['attachment_id'] = $existing_id;
				$payload['idempotent_replay'] = true;
			} else {
				if ( 'restoring' === ( $manifest['state'] ?? '' ) ) { return Flagged_Media_Backup::error( 'restore_recovery_required' ); }
				foreach ( $files as $file ) {
					$target = Flagged_Media_Backup::path( (string) $file['relative_file'] );
					if ( file_exists( $target ) || is_link( $target ) ) { return Flagged_Media_Backup::error( 'restore_target_conflict' ); }
				}
			}
			if ( $this->should_dry_run( $input ) ) {
				$payload['preview'] = array( 'action' => 'restore_media_backup', 'restore_deleted_attachment' => true, 'original_attachment_id' => $attachment_id, 'file_count' => count( $files ), 'target_file' => $manifest['relative_file'] );
				return $this->dry_run_payload( $payload );
			}
			$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/restore-media-backup', $input );
			if ( is_wp_error( $allowed ) ) { return $allowed; }
			if ( $payload['idempotent_replay'] ) { $payload['restored'] = true; $payload['dry_run'] = false; return $payload; }

			// Persist an intent before mutation. A crash leaves an explicit recovery block.
			$manifest['state'] = 'restoring';
			$records[ $backup_id ] = $manifest;
			if ( ! Flagged_Media_Backup::persist( $records ) ) { return Flagged_Media_Backup::error( 'restore_intent_not_persisted', 500 ); }
			$created = array();
			$new_id = 0;
			$failure = '';
			foreach ( $files as $index => $file ) {
				$source = Flagged_Media_Backup::path( Flagged_Media_Backup::PREFIX . $backup_id . '/' . $index . '.bin' );
				$target = Flagged_Media_Backup::path( (string) $file['relative_file'] );
				if ( '' === $source || '' === $target || ! Flagged_Media_Backup::copy_exclusive( $source, $target, (string) $file['sha256'] ) ) { $failure = 'restore_copy_failed'; break; }
				$created[] = $target;
			}
			if ( '' === $failure ) {
				$post = (array) $manifest['post'];
				$post['post_status'] = 'inherit';
				$inserted = wp_insert_attachment( wp_slash( $post ), '', (int) ( $post['post_parent'] ?? 0 ), true );
				if ( is_wp_error( $inserted ) ) { $failure = 'restore_insert_failed'; } else { $new_id = (int) $inserted; }
			}
			if ( '' === $failure ) {
				foreach ( array( '_wp_attached_file' => $manifest['relative_file'], '_wp_attachment_metadata' => $manifest['metadata'], '_wp_attachment_backup_sizes' => $manifest['backup_sizes'], '_wp_attachment_image_alt' => $manifest['alt'] ) as $key => $value ) {
					update_post_meta( $new_id, $key, wp_slash( $value ) );
					if ( get_post_meta( $new_id, $key, true ) !== $value ) { $failure = 'restore_metadata_failed'; break; }
				}
			}
			if ( '' === $failure ) {
				$manifest['state'] = 'restored';
				$manifest['restored_attachment_id'] = $new_id;
				$records[ $backup_id ] = $manifest;
				if ( ! Flagged_Media_Backup::persist( $records ) ) { $failure = 'restore_result_not_persisted'; }
			}
			if ( '' !== $failure ) {
				// Remove only new resources owned by this invocation; the backup stays intact.
				if ( $new_id > 0 ) { wp_delete_attachment( $new_id, true ); }
				$rolled_back = $new_id <= 0 || ! get_post( $new_id );
				foreach ( $created as $path ) {
					if ( is_file( $path ) && ! unlink( $path ) ) { $rolled_back = false; }
				}
				if ( $rolled_back ) {
					unset( $manifest['state'], $manifest['restored_attachment_id'] );
					$records[ $backup_id ] = $manifest;
					$rolled_back = Flagged_Media_Backup::persist( $records );
				}
				return new \WP_Error( 'npcink_abilities_toolkit_flagged_media_restore_failed', __( 'Deleted attachment restoration failed.', 'npcink-abilities-toolkit' ), array( 'status' => 500, 'detail' => $failure, 'rolled_back' => $rolled_back, 'backup_id' => $backup_id, 'recovery_attachment_id' => $new_id ) );
			}
			$payload['attachment_id'] = $new_id;
			$payload['restored'] = true;
			$payload['recreated'] = true;
			$payload['dry_run'] = false;
			return $payload;
		};
		return $this->should_dry_run( $input ) ? $operation() : Flagged_Media_Backup::locked( $operation );
	}
}
