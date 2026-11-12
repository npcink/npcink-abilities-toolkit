<?php
/**
 * Governed Cloud image import. Source attachments are read-only.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Packages;

use Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Saves one reviewed Cloud artifact as a distinct attachment. */
trait Cloud_Media_Import_Write_Methods {
	/** @return array<string,mixed> Governed write definition. */
	private function cloud_media_import_definition(): array {
		return array(
					'label' => __( 'Save Cloud Image As New Media', 'npcink-abilities-toolkit' ),
					'description' => __( 'Imports a verified Cloud image as a new attachment after host approval. Preserves the source and existing article references.', 'npcink-abilities-toolkit' ),
					'category' => 'npcink-abilities-toolkit-write',
					'capability' => 'upload_files',
					'required_scopes' => array( 'media.write' ),
					'channels' => array( 'agent', 'mcp' ),
					'meta' => $this->write_meta(),
					'input_schema' => $this->schema( array(
						'source_attachment_id' => array( 'type' => 'integer', 'minimum' => 1 ),
						'expected_source_media_fingerprint' => array( 'type' => 'string', 'pattern' => '^sha256:[0-9a-f]{64}$' ),
						'derivative_artifact' => Cloud_Derivative_Artifact::schema(),
					), array( 'source_attachment_id', 'expected_source_media_fingerprint', 'derivative_artifact' ) ),
					'output_schema' => $this->schema( array(
						'attachment_id' => array( 'type' => 'integer' ),
						'source_attachment_id' => array( 'type' => 'integer' ),
						'created' => array( 'type' => 'boolean' ),
						'original_preserved' => array( 'type' => 'boolean' ),
						'idempotent_replay' => array( 'type' => 'boolean' ),
						'url' => array( 'type' => 'string' ),
						'edit_link' => array( 'type' => 'string' ),
						'artifact' => Cloud_Derivative_Artifact::schema(),
						'dry_run' => array( 'type' => 'boolean' ),
					), array( 'attachment_id', 'source_attachment_id', 'created', 'original_preserved', 'dry_run' ) ),
					'execute_callback' => array( $this, 'import_cloud_media_derivative' ),
				);
	}

	/**
	 * Previews by default; only the existing host approval seam permits commit.
	 *
	 * @param mixed $input Ability input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function import_cloud_media_derivative( $input ) {
		$input = is_array( $input ) ? $input : array();
		$source_id = absint( $input['source_attachment_id'] ?? 0 );
		$source = get_post( $source_id );
		if ( ! current_user_can( 'upload_files' ) || ! $source_id || ! current_user_can( 'edit_post', $source_id ) ) {
			return $this->cloud_import_error( 'permission_denied', 403 );
		}
		if ( ! $source || 'attachment' !== $source->post_type || ! str_starts_with( (string) $source->post_mime_type, 'image/' ) ) {
			return $this->cloud_import_error( 'source_invalid', 400 );
		}
		$artifact = Cloud_Derivative_Artifact::normalize( (array) ( $input['derivative_artifact'] ?? array() ) );
		if ( is_wp_error( $artifact ) ) {
			return $artifact;
		}
		$fingerprint = (string) ( $input['expected_source_media_fingerprint'] ?? '' );
		if ( ! preg_match( '/^sha256:[0-9a-f]{64}$/D', $fingerprint ) || $artifact['transform_facts']['source_checksum'] !== $fingerprint ) {
			return $this->cloud_import_error( 'source_mismatch', 409 );
		}
		$source_path = (string) get_attached_file( $source_id );
		if ( ! is_readable( $source_path ) || ! hash_equals( $fingerprint, 'sha256:' . hash_file( 'sha256', $source_path ) ) ) {
			return $this->cloud_import_error( 'source_changed', 409 );
		}
		$payload = array(
			'attachment_id' => 0,
			'source_attachment_id' => $source_id,
			'created' => false,
			'original_preserved' => true,
			'artifact' => $artifact,
			'dry_run' => true,
		);
		if ( $this->should_dry_run( $input ) ) {
			return $payload;
		}
		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/import-cloud-media-derivative', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) || ! is_dir( $uploads['basedir'] ) ) {
			return $this->cloud_import_error( 'uploads_unavailable', 500 );
		}
		// One synchronous critical section, no jobs, leases or approval records.
		$lock_path = $uploads['basedir'] . '/.npcink-media-import.lock';
		if ( is_link( $lock_path ) ) {
			return $this->cloud_import_error( 'lock_unavailable', 409 );
		}
		$lock = @fopen( $lock_path, 'c+b' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- nonblocking filesystem lock; no queued execution.
		if ( false === $lock ) {
			return $this->cloud_import_error( 'lock_unavailable', 409 );
		}
		try {
			if ( ! flock( $lock, LOCK_EX | LOCK_NB ) ) {
				return $this->cloud_import_error( 'busy', 409 );
			}
			$opened = fstat( $lock );
			$named = lstat( $lock_path );
			if ( ! $opened || ! $named || ( $opened['mode'] & 0170000 ) !== 0100000 || $opened['ino'] !== $named['ino'] || $opened['dev'] !== $named['dev'] || ( $named['mode'] & 0170000 ) !== 0100000 ) {
				return $this->cloud_import_error( 'lock_unavailable', 409 );
			}
			$import_key = hash( 'sha256', $source_id . '|' . $fingerprint . '|' . $artifact['artifact_id'] . '|' . $artifact['sha256'] );
			$existing = get_posts( array(
				'post_type' => 'attachment', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids',
				'meta_key' => '_npcink_cloud_import_key', 'meta_value' => $import_key,
			) );
			if ( $existing ) {
				$id = (int) $existing[0];
				$path = (string) get_attached_file( $id );
				if ( ! is_readable( $path ) || ! hash_equals( $artifact['sha256'], (string) hash_file( 'sha256', $path ) ) ) {
					return $this->cloud_import_error( 'saved_result_changed', 409 );
				}
				return $this->cloud_import_result( $payload, $id, true );
			}
			return $this->create_cloud_import_attachment( $payload, $uploads, $source_path, $fingerprint, $import_key );
		} finally {
			flock( $lock, LOCK_UN );
			fclose( $lock ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- release the synchronous lock.
		}
	}

	/**
	 * Creates only new files inside an operation-owned uploads subdirectory.
	 *
	 * @param array<string,mixed> $payload Validated preview.
	 * @param array<string,mixed> $uploads WordPress uploads locations.
	 * @param string $source_path Source main file.
	 * @param string $fingerprint Expected source SHA.
	 * @param string $import_key Artifact identity.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function create_cloud_import_attachment( array $payload, array $uploads, string $source_path, string $fingerprint, string $import_key ) {
		$artifact = $payload['artifact'];
		if ( Cloud_Derivative_Artifact::expiry_timestamp( $artifact['expires_at'] ) <= time() ) {
			return $this->cloud_import_error( 'artifact_expired', 410 );
		}
		if ( ! function_exists( 'npcink_cloud_addon_receive_media_derivative_artifact' ) ) {
			return $this->cloud_import_error( 'addon_unavailable', 409 );
		}
		$received = npcink_cloud_addon_receive_media_derivative_artifact( $artifact, 'media-import-' . $import_key );
		if ( is_wp_error( $received ) ) {
			return $received;
		}
		$received = Cloud_Derivative_Artifact::verify_received_payload( $received, $artifact );
		if ( is_wp_error( $received ) ) {
			return $received;
		}
		if ( Cloud_Derivative_Artifact::expiry_timestamp( $artifact['expires_at'] ) <= time() || ! is_readable( $source_path ) || ! hash_equals( $fingerprint, 'sha256:' . hash_file( 'sha256', $source_path ) ) ) {
			return $this->cloud_import_error( 'review_stale', 409 );
		}
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
		// Dedicated directory prevents thumbnail collisions with existing media.
		$dir = $uploads['path'] . '/npcink-processed-' . wp_generate_uuid4();
		if ( ! $this->is_media_uploads_path_allowed( $dir . '/result.' . $artifact['format'] ) || ! mkdir( $dir, 0755 ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- exclusive operation-owned directory.
			return $this->cloud_import_error( 'directory_failed', 500 );
		}
		$path = $dir . '/result.' . $artifact['format'];
		$id = 0;
		try {
			$created = $this->write_cloud_media_file_exclusive( $path, $received['contents'] );
			$result = is_wp_error( $created ) ? $created : $this->finish_cloud_import_attachment( $payload, $path, $source_path, $fingerprint, $import_key, $id );
		} catch ( \Throwable $error ) {
			$result = $this->cloud_import_error( 'operation_failed', 500 );
		}
		if ( ! is_wp_error( $result ) ) {
			return $result;
		}
		// Preserve the original failure; recovery facts describe only our new data.
		$recovery = array();
		if ( $id > 0 && ! wp_delete_attachment( $id, true ) ) {
			$recovery = array( 'attachment_id' => $id, 'recovery_required' => true );
		} else {
			foreach ( glob( $dir . '/*' ) ?: array() as $file ) {
				if ( is_file( $file ) && ! is_link( $file ) ) { wp_delete_file( $file ); }
			}
			if ( glob( $dir . '/*' ) || ! @rmdir( $dir ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- report failure without exposing absolute filesystem paths.
				$recovery = array( 'attachment_id' => 0, 'directory' => basename( $dir ), 'recovery_required' => true );
			}
		}
		if ( $recovery ) {
			return new \WP_Error( $result->get_error_code(), $result->get_error_message(), array_merge( (array) $result->get_error_data(), $recovery ) );
		}
		return $result;
	}

	/**
	 * @param array<string,mixed> $payload Reviewed result.
	 * @param string $path New main file.
	 * @param string $source_path Read-only source.
	 * @param string $fingerprint Source checksum.
	 * @param string $import_key Artifact identity.
	 * @param int $id New attachment id, also retained for failure cleanup.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function finish_cloud_import_attachment( array $payload, string $path, string $source_path, string $fingerprint, string $import_key, int &$id ) {
		$artifact = $payload['artifact'];
		$inserted = wp_insert_attachment( array(
			'post_mime_type' => $artifact['mime_type'],
			'post_title' => sanitize_text_field( pathinfo( $artifact['suggested_filename'], PATHINFO_FILENAME ) ),
			'post_status' => 'inherit',
		), $path, 0, true );
		if ( is_wp_error( $inserted ) ) {
			return $inserted;
		}
		$id = (int) $inserted;
		// Keep reviewed bytes as the new main file; WordPress may make sizes.
		$no_scale = static function ( $threshold, $imagesize, $file, $attachment_id ) use ( $id ) { return $id === (int) $attachment_id ? false : $threshold; };
		add_filter( 'big_image_size_threshold', $no_scale, PHP_INT_MAX, 4 );
		try {
			$metadata = wp_generate_attachment_metadata( $id, $path );
		} finally {
			remove_filter( 'big_image_size_threshold', $no_scale, PHP_INT_MAX );
		}
		wp_update_attachment_metadata( $id, $metadata );
		if ( empty( $metadata['width'] ) || empty( $metadata['height'] ) || wp_get_attachment_metadata( $id ) !== $metadata || (string) get_attached_file( $id ) !== $path || ! is_readable( $path ) || ! hash_equals( $artifact['sha256'], (string) hash_file( 'sha256', $path ) ) ) {
			return $this->cloud_import_error( 'metadata_failed', 500 );
		}
		if ( Cloud_Derivative_Artifact::expiry_timestamp( $artifact['expires_at'] ) <= time() || ! is_readable( $source_path ) || ! hash_equals( $fingerprint, 'sha256:' . hash_file( 'sha256', $source_path ) ) ) {
			return $this->cloud_import_error( 'source_changed', 409 );
		}
		$provenance = array( 'source_attachment_id' => $payload['source_attachment_id'], 'source_fingerprint' => $fingerprint, 'artifact' => $artifact );
		if ( ! add_post_meta( $id, '_npcink_cloud_import', $provenance, true ) || ! add_post_meta( $id, '_npcink_cloud_import_key', $import_key, true ) ) {
			return $this->cloud_import_error( 'provenance_failed', 500 );
		}
		return $this->cloud_import_result( $payload, $id, false );
	}

	/**
	 * @param array<string,mixed> $payload Preview payload.
	 * @param int $id New attachment.
	 * @param bool $replay Whether already saved.
	 * @return array<string,mixed>
	 */
	private function cloud_import_result( array $payload, int $id, bool $replay ): array {
		return array_merge( $payload, array( 'attachment_id' => $id, 'created' => ! $replay, 'idempotent_replay' => $replay, 'url' => (string) wp_get_attachment_url( $id ), 'edit_link' => $this->edit_link( $id ), 'dry_run' => false ) );
	}

	/**
	 * @param string $reason Bounded diagnostic code.
	 * @param int $status HTTP status.
	 * @return \WP_Error
	 */
	private function cloud_import_error( string $reason, int $status ): \WP_Error {
		return new \WP_Error( 'npcink_abilities_toolkit_cloud_import_' . $reason, __( 'The processed image could not be saved as new media. Review the source and result before trying again.', 'npcink-abilities-toolkit' ), array( 'status' => $status, 'reason' => $reason ) );
	}
}
