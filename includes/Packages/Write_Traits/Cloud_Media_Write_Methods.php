<?php
/**
 * Cloud media derivative adoption and compare-and-swap transaction write methods for Core_Write_Package.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Packages;

use Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Provides host-governed cloud media derivative adoption, atomic media mutations, and rollback writes.
 */
trait Cloud_Media_Write_Methods {
		/**
		 * Adopts one short-lived Cloud derivative artifact as a local media replacement.
		 *
		 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function adopt_cloud_media_derivative( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'upload_files' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to adopt media derivatives.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$attachment_id = absint( $input['attachment_id'] ?? 0 );
		$attachment = $this->get_media_attachment( $attachment_id );
		if ( is_wp_error( $attachment ) ) {
			return $attachment;
		}
		if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to replace this media file.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$plan = $this->build_cloud_media_derivative_adoption_plan( $attachment_id, $input );
		if ( is_wp_error( $plan ) ) {
			return $plan;
		}

		$payload = array(
			'attachment_id'      => $attachment_id,
			'replaced'           => false,
			'original_preserved' => true,
			'replacement_id'     => (string) ( $plan['replacement_id'] ?? '' ),
			'before'             => is_array( $plan['before'] ?? null ) ? $plan['before'] : array(),
			'after'              => is_array( $plan['after'] ?? null ) ? $plan['after'] : array(),
			'backup'             => is_array( $plan['backup'] ?? null ) ? $plan['backup'] : array(),
			'artifact'           => is_array( $plan['artifact'] ?? null ) ? $plan['artifact'] : array(),
			'proposed_filename'  => $this->sanitize_media_file_name( (string) ( $plan['proposed_filename'] ?? '' ) ),
			'filename_policy'    => is_array( $plan['filename_policy'] ?? null ) ? $this->sanitize_payload( $plan['filename_policy'] ) : array(),
			'content_reference_repairs' => $this->build_media_content_reference_repairs( $attachment_id, $plan, false ),
			'history'            => $this->get_media_file_replacement_history( $attachment_id ),
			'edit_link'          => $this->edit_link( $attachment_id ),
			'preview'            => array(
				'action'          => 'adopt_cloud_media_derivative',
				'attachment_id'   => $attachment_id,
				'replacement_id'  => (string) ( $plan['replacement_id'] ?? '' ),
				'artifact_id'     => (string) ( $plan['artifact']['artifact_id'] ?? '' ),
				'backup_created'  => true,
				'cloud_artifact'  => true,
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/adopt-cloud-media-derivative', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}
		$expectation_error = $this->validate_media_content_reference_repair_expectations(
			is_array( $payload['content_reference_repairs'] ?? null ) ? $payload['content_reference_repairs'] : array(),
			is_array( $plan['content_reference_repair_expectations'] ?? null ) ? $plan['content_reference_repair_expectations'] : array()
		);
		if ( is_wp_error( $expectation_error ) ) {
			return $expectation_error;
		}
		$rollback_state = $this->capture_cloud_media_adoption_state(
			$attachment_id,
			is_array( $payload['content_reference_repairs'] ?? null ) ? $payload['content_reference_repairs'] : array(),
			$plan
		);
		$materialized = $this->materialize_cloud_media_derivative_artifact( $attachment_id, $plan );
		if ( is_wp_error( $materialized ) ) {
			return $materialized;
		}
		$transfer_evidence = is_array( $materialized['_transfer_evidence'] ?? null ) ? $materialized['_transfer_evidence'] : array();
		$delivery_ack = is_array( $materialized['_delivery_ack'] ?? null ) ? $materialized['_delivery_ack'] : array();
		$created_file = is_array( $materialized['_created_file'] ?? null ) ? $materialized['_created_file'] : array();
		unset( $materialized['_transfer_evidence'], $materialized['_delivery_ack'], $materialized['_created_file'] );
		$batch_manifest = (object) array(
			'created_files' => ! empty( $created_file ) ? array( $created_file ) : array(),
			'mutations'     => array(),
		);
		$plan['_derivative'] = $materialized;
		$plan['after'] = $this->media_file_state_from_derivative( $attachment_id, $materialized );
		$plan['_cloud_batch_manifest'] = $batch_manifest;
		$precommit = $this->validate_cloud_media_adoption_precommit_state(
			$attachment_id,
			$plan,
			is_array( $payload['content_reference_repairs'] ?? null ) ? $payload['content_reference_repairs'] : array(),
			$rollback_state
		);
		if ( is_wp_error( $precommit ) ) {
			return $this->cloud_media_adoption_precommit_failure_with_discard( $precommit, $batch_manifest, $attachment_id );
		}
		$plan['_current'] = is_array( $precommit['current'] ?? null ) ? $precommit['current'] : array();
		$plan['before'] = $this->public_media_file_state( $plan['_current'] );
		$rollback_state = is_array( $precommit['rollback_state'] ?? null ) ? $precommit['rollback_state'] : $rollback_state;
		$batch_manifest->rollback_state = $rollback_state;
		$payload['before'] = $plan['before'];
		$payload['after'] = $plan['after'];
		$payload['content_reference_repairs'] = is_array( $precommit['content_reference_repairs'] ?? null ) ? $precommit['content_reference_repairs'] : array();
		$plan['_cloud_precommit_repairs'] = $payload['content_reference_repairs'];
		$plan['_cloud_precommit_state'] = $rollback_state;

		$result = $this->execute_media_file_replacement( $attachment_id, $plan );
		if ( is_wp_error( $result ) ) {
			if ( 'npcink_abilities_toolkit_cloud_adoption_precommit_drift' === $result->get_error_code() ) {
				return $this->cloud_media_adoption_precommit_failure_with_discard( $result, $batch_manifest, $attachment_id );
			}
			return $this->cloud_media_adoption_failure_with_cleanup( $result, $attachment_id, $plan, $materialized, $rollback_state );
		}
		$optimized_derivative_updated = $this->append_media_optimized_derivative( $attachment_id, $materialized, $batch_manifest );
		if ( is_wp_error( $optimized_derivative_updated ) ) {
			return $this->cloud_media_adoption_failure_with_cleanup( $optimized_derivative_updated, $attachment_id, $plan, $materialized, $rollback_state );
		}

		$payload['replaced'] = ! empty( $result['replaced'] );
		$payload['after'] = is_array( $result['after'] ?? null ) ? $result['after'] : $payload['after'];
		$payload['backup'] = is_array( $result['backup'] ?? null ) ? $result['backup'] : $payload['backup'];
		$payload['content_reference_repairs'] = is_array( $result['content_reference_repairs'] ?? null ) ? $result['content_reference_repairs'] : $payload['content_reference_repairs'];
		$payload['verification'] = $this->media_file_operation_verification( $attachment_id, $payload['after'], $payload['backup'], $payload['content_reference_repairs'] );
		$commit_verified = $this->verify_cloud_media_adoption_commit(
			$attachment_id,
			$plan,
			$materialized,
			$payload['verification']
		);
		if ( is_wp_error( $commit_verified ) ) {
			return $this->cloud_media_adoption_failure_with_cleanup( $commit_verified, $attachment_id, $plan, $materialized, $rollback_state );
		}
		if ( function_exists( 'do_action' ) ) {
			do_action( 'npcink_abilities_toolkit_media_file_version_changed', $attachment_id, array(
				'replacement_id' => (string) ( $plan['replacement_id'] ?? '' ),
				'new_media_fingerprint' => (string) ( $payload['after']['media_fingerprint'] ?? '' ),
				'derived_from_media_fingerprint' => (string) ( $payload['before']['media_fingerprint'] ?? '' ),
			) );
		}
		$payload['transfer_evidence'] = $transfer_evidence;
		$payload['delivery_ack'] = $delivery_ack;
		$payload['history'] = $this->get_media_file_replacement_history( $attachment_id );
		$payload['dry_run'] = false;
		unset( $payload['preview'] );
		return $payload;
	}

	/**
	 * Builds a local adoption plan for one Cloud derivative artifact.
	 *
	 * @param int                 $attachment_id Attachment id.
	 * @param array<string,mixed> $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function build_cloud_media_derivative_adoption_plan( $attachment_id, array $input ) {
		$attachment_id = absint( $attachment_id );
		$current = $this->current_media_file_state( $attachment_id );
		if ( is_wp_error( $current ) ) {
			return $current;
		}
		$expected_error = $this->validate_media_expected_state( $current, $input );
		if ( is_wp_error( $expected_error ) ) {
			return $expected_error;
		}

		$artifact = $this->normalize_cloud_media_derivative_artifact( is_array( $input['derivative_artifact'] ?? null ) ? $input['derivative_artifact'] : array() );
		if ( is_wp_error( $artifact ) ) {
			return $artifact;
		}
		$expected_derivative_mime = sanitize_text_field( (string) ( $input['expected_derivative_mime_type'] ?? '' ) );
		if ( '' !== $expected_derivative_mime && $expected_derivative_mime !== (string) ( $artifact['mime_type'] ?? '' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_derivative_mime_mismatch', __( 'The derivative MIME type did not match the expected value.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
		}

		$replacement_id = 'cloud_media_replace_' . gmdate( 'Ymd_His' ) . '_' . substr( md5( $attachment_id . '|' . (string) $artifact['artifact_id'] . '|' . microtime( true ) ), 0, 8 );
		$backup_suffix = sanitize_key( (string) ( $input['backup_suffix'] ?? 'npcink-abilities-toolkit-cloud-backup' ) );
		$backup_suffix = '' !== $backup_suffix ? substr( $backup_suffix, 0, 48 ) : 'npcink-abilities-toolkit-cloud-backup';
		$backup_relative = $this->backup_relative_file_for_current_media( $current, $replacement_id, $backup_suffix );
		$reviewed_file_name = $this->sanitize_media_file_name( (string) ( $input['file_name'] ?? '' ) );
		if ( '' === $reviewed_file_name ) {
			$reviewed_file_name = $this->sanitize_media_file_name( (string) ( $artifact['suggested_filename'] ?? '' ) );
		}
		$derivative = $this->cloud_artifact_derivative_state( $attachment_id, $current, $artifact, $reviewed_file_name );
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
			'artifact'       => $artifact,
			'proposed_filename' => $reviewed_file_name,
			'filename_policy' => array(
				'owner'                          => 'wordpress_write_ability_final',
				'proposed_filename'              => $reviewed_file_name,
				'final_sanitize_unique_required' => true,
				'preserve_attachment_metadata'   => true,
				'source'                         => '' !== (string) ( $input['file_name'] ?? '' ) ? 'reviewed_input' : 'cloud_artifact_suggestion',
			),
				'content_reference_repair_expectations' => $this->normalize_media_content_reference_repair_expectations( $input ),
				'batch_context' => array(
					'batch_id'                  => sanitize_text_field( (string) ( $input['batch_id'] ?? '' ) ),
					'optimization_profile'      => sanitize_text_field( (string) ( $input['optimization_profile'] ?? '' ) ),
					'batch_confirmation_digest' => $this->normalize_media_sha256( (string) ( $input['batch_confirmation_digest'] ?? '' ) ),
					'backup_cleanup_policy'     => $this->media_backup_cleanup_policy_for_input( $input ),
				),
				'_current'       => $current,
			'_derivative'    => $derivative,
			'_backup_relative_file' => $backup_relative,
		);
	}

	/**
	 * Validates a bounded Cloud derivative artifact descriptor.
	 *
	 * @param array<string,mixed> $artifact Artifact descriptor.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function normalize_cloud_media_derivative_artifact( array $artifact ) {
		return Cloud_Derivative_Artifact::normalize( $artifact );
	}

	/**
	 * Builds the future local derivative file state from a Cloud artifact.
	 *
	 * @param int                 $attachment_id Attachment id.
	 * @param array<string,mixed> $current Current media state.
	 * @param array<string,mixed> $artifact Normalized artifact.
	 * @return array<string,mixed>
	 */
	private function cloud_artifact_derivative_state( $attachment_id, array $current, array $artifact, $file_name = '' ) {
		$current_relative = $this->normalize_media_relative_file( (string) ( $current['relative_file'] ?? '' ) );
		$dir = dirname( $current_relative );
		$dir = '.' !== $dir ? trim( $dir, '/' ) : '';
		$basename = $this->sanitize_media_file_name( basename( $current_relative ) );
		$custom_basename = $this->sanitize_media_file_name( (string) $file_name );
		$stem = '' !== $custom_basename ? preg_replace( '/\.[^.]+$/', '', $custom_basename ) : preg_replace( '/\.[^.]+$/', '', $basename );
		$stem = '' !== (string) $stem ? $stem : 'attachment-' . absint( $attachment_id );
		$artifact_key = substr( sanitize_key( (string) ( $artifact['artifact_id'] ?? '' ) ), 0, 16 );
		$extension = $this->media_extension_for_mime( (string) ( $artifact['mime_type'] ?? '' ) );
		$file_basename = '' !== $custom_basename
			? $this->sanitize_media_file_name( (string) $stem . '.' . $extension )
			: $this->sanitize_media_file_name( (string) $stem . '-npcink-abilities-toolkit-cloud-' . $artifact_key . '.' . $extension );
		$relative_file = '' !== $dir ? $dir . '/' . $file_basename : $file_basename;

		return array(
			'format'          => sanitize_key( (string) ( $artifact['format'] ?? '' ) ),
			'mime_type'       => sanitize_text_field( (string) ( $artifact['mime_type'] ?? '' ) ),
			'file_basename'   => $file_basename,
			'relative_file'   => $relative_file,
			'url'             => $this->media_url_for_relative_file( $relative_file ),
			'width'           => absint( $artifact['width'] ?? 0 ),
			'height'          => absint( $artifact['height'] ?? 0 ),
			'filesize_bytes'  => absint( $artifact['filesize_bytes'] ?? 0 ),
			'generated_at_gmt' => gmdate( 'c' ),
			'source'          => 'cloud_derivative_artifact',
			'artifact_id'     => sanitize_text_field( (string) ( $artifact['artifact_id'] ?? '' ) ),
			'artifact_checksum' => Cloud_Derivative_Artifact::normalize_sha256( $artifact['sha256'] ?? '' ),
		);
	}

	/**
	 * Downloads and stores a Cloud artifact as a bounded local derivative file.
	 *
	 * @param int                 $attachment_id Attachment id.
	 * @param array<string,mixed> $plan Adoption plan.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function materialize_cloud_media_derivative_artifact( $attachment_id, array $plan ) {
		$artifact = is_array( $plan['artifact'] ?? null ) ? $plan['artifact'] : array();
		$expires_ts = Cloud_Derivative_Artifact::expiry_timestamp( (string) ( $artifact['expires_at'] ?? '' ) );
		if ( $expires_ts <= time() ) {
			return new \WP_Error( 'npcink_abilities_toolkit_cloud_artifact_expired', __( 'The derivative artifact expired before its bytes could be adopted.', 'npcink-abilities-toolkit' ), array( 'status' => 410 ) );
		}
		$current = is_array( $plan['_current'] ?? null ) ? $plan['_current'] : array();
		$storage_ready = $this->validate_media_storage_commit_ready( $current );
		if ( is_wp_error( $storage_ready ) ) {
			return $storage_ready;
		}
		if ( ! function_exists( 'npcink_cloud_addon_receive_media_derivative_artifact' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_cloud_addon_unavailable', __( 'Cloud Addon verified artifact receiving is unavailable on this site.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
		}
		$received = npcink_cloud_addon_receive_media_derivative_artifact( $artifact, (string) ( $plan['replacement_id'] ?? '' ) );
		if ( is_wp_error( $received ) ) {
			return $received;
		}
		$received = Cloud_Derivative_Artifact::verify_received_payload( $received, $artifact );
		if ( is_wp_error( $received ) ) {
			return $received;
		}
		$contents = $received['contents'];
		$actual_filesize = $received['filesize_bytes'];
		$sha256 = $received['sha256'];
		if ( Cloud_Derivative_Artifact::expiry_timestamp( $received['expires_at'] ) <= time() ) {
			return new \WP_Error( 'npcink_abilities_toolkit_cloud_artifact_expired', __( 'The derivative artifact expired before its bytes could be adopted.', 'npcink-abilities-toolkit' ), array( 'status' => 410 ) );
		}

		$derivative = is_array( $plan['_derivative'] ?? null ) ? $plan['_derivative'] : array();
		$relative_file = $this->normalize_media_relative_file( (string) ( $derivative['relative_file'] ?? '' ) );
		$destination = $this->media_uploads_path_for_relative_file( $relative_file );
		if ( '' === $destination ) {
			return new \WP_Error( 'npcink_abilities_toolkit_cloud_derivative_path_invalid', __( 'The local derivative path is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		$destination_dir = dirname( $destination );
		if ( ! $this->ensure_media_directory( $destination_dir ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_cloud_derivative_directory_unavailable', __( 'The local derivative directory could not be created.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
		}
		$basename = $this->sanitize_media_file_name( basename( $destination ) );
		if ( function_exists( 'wp_unique_filename' ) ) {
			$basename = wp_unique_filename( $destination_dir, $basename );
			$destination = $this->trailingslashit_value( $destination_dir ) . $basename;
			$relative_dir = dirname( $relative_file );
			$relative_dir = '.' !== $relative_dir ? trim( $relative_dir, '/' ) : '';
			$relative_file = '' !== $relative_dir ? $relative_dir . '/' . $basename : $basename;
		} elseif ( file_exists( $destination ) ) {
			$basename = $this->unique_media_basename( $destination_dir, $basename );
			$destination = $this->trailingslashit_value( $destination_dir ) . $basename;
			$relative_dir = dirname( $relative_file );
			$relative_dir = '.' !== $relative_dir ? trim( $relative_dir, '/' ) : '';
			$relative_file = '' !== $relative_dir ? $relative_dir . '/' . $basename : $basename;
		}
		$created_file = $this->write_cloud_media_file_exclusive(
			$destination,
			$contents,
			array(
				'operation'     => 'adopt_cloud_media_derivative',
				'step'          => 'write_derivative',
				'attachment_id' => $attachment_id,
				'relative_file' => $relative_file,
			)
		);
		if ( is_wp_error( $created_file ) ) {
			return $created_file;
		}
		$written_size = is_readable( $destination ) ? filesize( $destination ) : false;
		$written_sha256 = is_readable( $destination ) ? hash_file( 'sha256', $destination ) : false;
		$written_image = is_readable( $destination ) ? @getimagesize( $destination ) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- a corrupt local write must fail closed without emitting warnings.
		$write_verified = is_int( $written_size )
			&& $written_size === $actual_filesize
			&& is_string( $written_sha256 )
			&& hash_equals( $sha256, $written_sha256 )
			&& is_array( $written_image )
			&& ( $written_image['mime'] ?? '' ) === $received['mime_type']
			&& ( $written_image[0] ?? 0 ) === $received['width']
			&& ( $written_image[1] ?? 0 ) === $received['height']
			&& Cloud_Derivative_Artifact::dimensions_within_limits( $written_image[0] ?? null, $written_image[1] ?? null );
		if ( ! $write_verified || Cloud_Derivative_Artifact::expiry_timestamp( $received['expires_at'] ) <= time() ) {
			$cause = new \WP_Error( 'npcink_abilities_toolkit_cloud_derivative_local_verification_failed', __( 'The local derivative file did not preserve the verified transfer facts.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
			return $this->cloud_media_created_file_failure_with_discard( $cause, $created_file );
		}

		$derivative['file_basename'] = $basename;
		$derivative['relative_file'] = $relative_file;
		$derivative['url'] = $this->media_url_for_relative_file( $relative_file );
		$derivative['filesize_bytes'] = absint( filesize( $destination ) );
		$derivative['artifact_checksum'] = $sha256;
		$derivative['media_fingerprint'] = $this->normalize_media_sha256( (string) $sha256 );
		$derivative['generated_at_gmt'] = gmdate( 'c' );
		$derivative['_transfer_evidence'] = $received['transfer_evidence'];
		$derivative['_delivery_ack'] = $received['delivery_ack'];
		$derivative['_created_file'] = $created_file;

		return $derivative;
	}

	/**
	 * Captures only the local state that this adoption batch is allowed to mutate.
	 *
	 * @param int                 $attachment_id Attachment id.
	 * @param array<string,mixed> $repairs Reviewed content repair plan.
	 * @param array<string,mixed> $plan Adoption plan.
	 * @return array<string,mixed>
	 */
	private function capture_cloud_media_adoption_state( $attachment_id, array $repairs, array $plan ) {
		$attachment_id = absint( $attachment_id );
		$meta = array();
		foreach (
			array(
				'_wp_attached_file',
				'_wp_attachment_metadata',
				'_npcink_ai_media_optimized_derivatives',
				'_npcink_ai_media_latest_optimized_derivative',
				'_npcink_ai_media_file_replacement_history',
				'_npcink_ai_media_latest_file_replacement',
			) as $meta_key
		) {
			$meta[ $meta_key ] = $this->cloud_media_post_meta_snapshot( $attachment_id, $meta_key );
		}

		$post_contents = array();
		foreach ( (array) ( $repairs['repairs'] ?? array() ) as $repair ) {
			$post_id = absint( is_array( $repair ) ? ( $repair['post_id'] ?? 0 ) : 0 );
			$post = $post_id > 0 ? get_post( $post_id ) : null;
			if ( is_object( $post ) ) {
				$post_contents[ $post_id ] = (string) ( $post->post_content ?? '' );
			}
		}

		$current = is_array( $plan['_current'] ?? null ) ? $plan['_current'] : array();
		$protected_files = $this->cloud_media_metadata_file_paths( is_array( $current['metadata'] ?? null ) ? $current['metadata'] : array() );
		$current_path = (string) ( $current['file_path'] ?? '' );
		if ( '' !== $current_path ) {
			$protected_files[] = $current_path;
		}

		return array(
			'attachment_mime_type' => function_exists( 'get_post_mime_type' ) ? (string) get_post_mime_type( $attachment_id ) : '',
			'meta'                 => $meta,
			'post_contents'        => $post_contents,
			'protected_files'      => array_values( array_unique( array_filter( $protected_files, 'is_string' ) ) ),
			'current_state'        => $this->cloud_media_current_state_snapshot( $current ),
			'current_file'         => $this->cloud_media_current_file_snapshot( $current_path ),
			'storage'              => is_array( $current['storage'] ?? null ) ? $current['storage'] : array(),
			'content_reference_repairs' => $repairs,
		);
	}

	/**
	 * Revalidates all mutable WordPress truth after Cloud receive and local materialization.
	 *
	 * No attachment, metadata, backup, history, or post-content write may happen
	 * before this second optimistic-concurrency gate succeeds.
	 *
	 * @param int                 $attachment_id Attachment id.
	 * @param array<string,mixed> $plan Materialized adoption plan.
	 * @param array<string,mixed> $reviewed_repairs Repair plan captured before receive.
	 * @param array<string,mixed> $rollback_state State captured before receive.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function validate_cloud_media_adoption_precommit_state( $attachment_id, array $plan, array $reviewed_repairs, array $rollback_state ) {
		$current = $this->current_media_file_state( absint( $attachment_id ) );
		if ( is_wp_error( $current ) ) {
			return new \WP_Error(
				'npcink_abilities_toolkit_cloud_adoption_precommit_drift',
				__( 'The attachment changed while the Cloud derivative was being received.', 'npcink-abilities-toolkit' ),
				array( 'status' => 409, 'drift_fields' => array( 'attachment_state' ), 'cause' => $current->get_error_code() )
			);
		}

		$fresh_plan = $plan;
		$fresh_plan['_current'] = $current;
		$fresh_plan['before'] = $this->public_media_file_state( $current );
		$fresh_repairs = $this->build_media_content_reference_repairs( $attachment_id, $fresh_plan, false );
		$expectation_error = $this->validate_media_content_reference_repair_expectations(
			$fresh_repairs,
			is_array( $plan['content_reference_repair_expectations'] ?? null ) ? $plan['content_reference_repair_expectations'] : array()
		);
		$fresh_state = $this->capture_cloud_media_adoption_state( $attachment_id, $fresh_repairs, $fresh_plan );
		$drift_fields = array();

		if ( ( $rollback_state['current_state'] ?? null ) !== ( $fresh_state['current_state'] ?? null ) ) {
			$drift_fields[] = 'attachment_state';
		}
		if ( ( $rollback_state['current_file'] ?? null ) !== ( $fresh_state['current_file'] ?? null ) ) {
			$drift_fields[] = 'attachment_file';
		}
		if ( ( $rollback_state['meta'] ?? null ) !== ( $fresh_state['meta'] ?? null ) ) {
			$drift_fields[] = 'attachment_meta';
		}
		if ( ( $rollback_state['attachment_mime_type'] ?? null ) !== ( $fresh_state['attachment_mime_type'] ?? null ) ) {
			$drift_fields[] = 'attachment_mime_type';
		}
		if ( ( $rollback_state['storage'] ?? null ) !== ( $fresh_state['storage'] ?? null ) ) {
			$drift_fields[] = 'storage';
		}
		if ( ( $rollback_state['post_contents'] ?? null ) !== ( $fresh_state['post_contents'] ?? null ) ) {
			$drift_fields[] = 'post_contents';
		}
		if ( $reviewed_repairs !== $fresh_repairs || ( $rollback_state['content_reference_repairs'] ?? null ) !== $fresh_repairs ) {
			$drift_fields[] = 'content_reference_repairs';
		}
		if ( is_wp_error( $expectation_error ) ) {
			$drift_fields[] = 'content_reference_repair_expectations';
		}

		$drift_fields = array_values( array_unique( $drift_fields ) );
		if ( ! empty( $drift_fields ) ) {
			return new \WP_Error(
				'npcink_abilities_toolkit_cloud_adoption_precommit_drift',
				__( 'The attachment or its reviewed content references changed while the Cloud derivative was being received.', 'npcink-abilities-toolkit' ),
				array(
					'status'       => 409,
					'drift_fields' => $drift_fields,
					'cause'        => is_wp_error( $expectation_error ) ? $expectation_error->get_error_code() : '',
				)
			);
		}

		return array(
			'current'                   => $current,
			'content_reference_repairs' => $fresh_repairs,
			'rollback_state'            => $fresh_state,
		);
	}

	/**
	 * Builds a strict, serialization-safe projection of current attachment state.
	 *
	 * @param array<string,mixed> $current Current state.
	 * @return array<string,mixed>
	 */
	private function cloud_media_current_state_snapshot( array $current ) {
		return array(
			'relative_file'  => (string) ( $current['relative_file'] ?? '' ),
			'url'            => (string) ( $current['url'] ?? '' ),
			'file_basename'  => (string) ( $current['file_basename'] ?? '' ),
			'file_path'      => (string) ( $current['file_path'] ?? '' ),
			'mime_type'      => (string) ( $current['mime_type'] ?? '' ),
			'width'          => absint( $current['width'] ?? 0 ),
			'height'         => absint( $current['height'] ?? 0 ),
			'filesize_bytes' => absint( $current['filesize_bytes'] ?? 0 ),
			'metadata'       => is_array( $current['metadata'] ?? null ) ? $current['metadata'] : array(),
			'storage'        => is_array( $current['storage'] ?? null ) ? $current['storage'] : array(),
		);
	}

	/**
	 * Captures current source bytes so in-place file changes also trigger drift.
	 *
	 * @param string $path Current attachment path.
	 * @return array<string,mixed>
	 */
	private function cloud_media_current_file_snapshot( $path ) {
		$path = (string) $path;
		$sha256 = '' !== $path && is_readable( $path ) ? hash_file( 'sha256', $path ) : false;

		return array(
			'path'           => $path,
			'readable'       => '' !== $path && is_readable( $path ),
			'filesize_bytes' => '' !== $path && is_readable( $path ) ? absint( filesize( $path ) ) : 0,
			'sha256'         => is_string( $sha256 ) ? $sha256 : '',
		);
	}

	/**
	 * Compares file bytes while allowing the snapshots to describe different paths.
	 *
	 * @param array<string,mixed> $expected Expected file snapshot.
	 * @param array<string,mixed> $actual Actual file snapshot.
	 * @return bool
	 */
	private function cloud_media_file_bytes_match( array $expected, array $actual ) {
		return ! empty( $expected['readable'] )
			&& ! empty( $actual['readable'] )
			&& absint( $expected['filesize_bytes'] ?? 0 ) === absint( $actual['filesize_bytes'] ?? 0 )
			&& '' !== (string) ( $expected['sha256'] ?? '' )
			&& (string) ( $expected['sha256'] ?? '' ) === (string) ( $actual['sha256'] ?? '' );
	}

	/**
	 * Returns one exact post-meta value and whether the row existed.
	 *
	 * @param int    $post_id Post id.
	 * @param string $meta_key Meta key.
	 * @return array{exists:bool,value:mixed}
	 */
	private function cloud_media_post_meta_snapshot( $post_id, $meta_key ) {
		$post_id = absint( $post_id );
		$meta_key = (string) $meta_key;
		if (
			isset( $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][ $post_id ] )
			&& is_array( $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][ $post_id ] )
			&& array_key_exists( $meta_key, $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][ $post_id ] )
		) {
			return array(
				'exists' => true,
				'value'  => $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][ $post_id ][ $meta_key ],
			);
		}
		$exists = function_exists( 'metadata_exists' ) && metadata_exists( 'post', $post_id, $meta_key );

		return array(
			'exists' => $exists,
			'value'  => $exists && function_exists( 'get_post_meta' ) ? get_post_meta( $post_id, $meta_key, true ) : null,
		);
	}

	/**
	 * Restores one exact post-meta snapshot.
	 *
	 * @param int                       $post_id Post id.
	 * @param string                    $meta_key Meta key.
	 * @param array{exists:bool,value:mixed} $snapshot Snapshot.
	 * @return bool
	 */
	private function restore_cloud_media_post_meta_snapshot( $post_id, $meta_key, array $snapshot ) {
		if ( ! empty( $snapshot['exists'] ) ) {
			update_post_meta( absint( $post_id ), (string) $meta_key, $snapshot['value'] ?? null );
		} elseif ( function_exists( 'delete_post_meta' ) ) {
			delete_post_meta( absint( $post_id ), (string) $meta_key );
		}
		$current = $this->cloud_media_post_meta_snapshot( $post_id, $meta_key );

		return (bool) $current['exists'] === (bool) ( $snapshot['exists'] ?? false )
			&& ( empty( $snapshot['exists'] ) || $current['value'] === ( $snapshot['value'] ?? null ) );
	}

	/**
	 * Converts attachment metadata file references to bounded uploads paths.
	 *
	 * @param array<string,mixed> $metadata Attachment metadata.
	 * @return array<int,string>
	 */
	private function cloud_media_metadata_file_paths( array $metadata ) {
		$relative_file = $this->normalize_media_relative_file( (string) ( $metadata['file'] ?? '' ) );
		$dir = dirname( $relative_file );
		$dir = '.' !== $dir ? trim( $dir, '/' ) : '';
		$relative_files = array( $relative_file );
		foreach ( (array) ( $metadata['sizes'] ?? array() ) as $size ) {
			$basename = $this->sanitize_media_file_name( is_array( $size ) ? (string) ( $size['file'] ?? '' ) : '' );
			if ( '' !== $basename ) {
				$relative_files[] = '' !== $dir ? $dir . '/' . $basename : $basename;
			}
		}
		$original_image = $this->sanitize_media_file_name( (string) ( $metadata['original_image'] ?? '' ) );
		if ( '' !== $original_image ) {
			$relative_files[] = '' !== $dir ? $dir . '/' . $original_image : $original_image;
		}

		$paths = array();
		foreach ( array_unique( array_filter( $relative_files ) ) as $relative ) {
			$path = $this->media_uploads_path_for_relative_file( $relative );
			if ( '' !== $path ) {
				$paths[] = $path;
			}
		}

		return $paths;
	}

	/**
	 * Restores all local truth after a failed Cloud adoption attempt.
	 *
	 * @param int                 $attachment_id Attachment id.
	 * @param array<string,mixed> $plan Adoption plan.
	 * @param array<string,mixed> $materialized Materialized derivative.
	 * @param array<string,mixed> $rollback_state Captured state.
	 * @return true|\WP_Error
	 */
	private function cleanup_failed_cloud_media_adoption( $attachment_id, array $plan, array $materialized, array $rollback_state ) {
		$attachment_id = absint( $attachment_id );
		unset( $materialized );
		$failures = array();
		$conflicts = array();
		$mutations = is_object( $plan['_cloud_batch_manifest'] ?? null ) && isset( $plan['_cloud_batch_manifest']->mutations ) && is_array( $plan['_cloud_batch_manifest']->mutations )
			? $plan['_cloud_batch_manifest']->mutations
			: array();

		foreach ( (array) ( $rollback_state['post_contents'] ?? array() ) as $post_id => $post_content ) {
			$post = get_post( absint( $post_id ) );
			$current_content = is_object( $post ) ? (string) ( $post->post_content ?? '' ) : '';
			if ( $current_content === (string) $post_content ) {
				continue;
			}
			$mutation = is_array( $mutations[ 'post_content:' . absint( $post_id ) ] ?? null ) ? $mutations[ 'post_content:' . absint( $post_id ) ] : array();
			if ( empty( $mutation ) || $current_content !== (string) ( $mutation['after'] ?? '' ) ) {
				$conflicts[] = 'post_content:' . absint( $post_id );
				continue;
			}
			$result = $this->cloud_media_atomic_compare_and_swap_post_field( absint( $post_id ), 'post_content', $current_content, (string) $post_content );
			if ( $this->cloud_media_transaction_rollback_unconfirmed( $result ) ) {
				return $this->cloud_media_unknown_transaction_cleanup_conflict();
			}
			$post = get_post( absint( $post_id ) );
			if ( is_wp_error( $result ) || ! is_object( $post ) || (string) ( $post->post_content ?? '' ) !== (string) $post_content ) {
				$conflicts[] = 'post_content:' . absint( $post_id );
			}
		}

		$original_mime = (string) ( $rollback_state['attachment_mime_type'] ?? '' );
		$current_mime = (string) get_post_mime_type( $attachment_id );
		if ( $current_mime !== $original_mime ) {
			$mime_mutation = is_array( $mutations[ 'mime:' . $attachment_id ] ?? null ) ? $mutations[ 'mime:' . $attachment_id ] : array();
			if ( empty( $mime_mutation ) || $current_mime !== (string) ( $mime_mutation['after'] ?? '' ) ) {
				$conflicts[] = 'attachment_mime_type';
			} else {
				$mime_result = $this->cloud_media_atomic_compare_and_swap_post_field( $attachment_id, 'post_mime_type', $current_mime, $original_mime );
				if ( $this->cloud_media_transaction_rollback_unconfirmed( $mime_result ) ) {
					return $this->cloud_media_unknown_transaction_cleanup_conflict();
				}
				if ( is_wp_error( $mime_result ) || (string) get_post_mime_type( $attachment_id ) !== $original_mime ) {
					$conflicts[] = 'attachment_mime_type';
				}
			}
		}

		foreach ( (array) ( $rollback_state['meta'] ?? array() ) as $meta_key => $snapshot ) {
			if ( ! is_array( $snapshot ) ) {
				$failures[] = 'meta:' . sanitize_key( (string) $meta_key );
				continue;
			}
			$current = $this->cloud_media_post_meta_snapshot( $attachment_id, (string) $meta_key );
			if ( $current === $snapshot ) {
				continue;
			}
			$mutation_id = 'meta:' . $attachment_id . ':' . (string) $meta_key;
			$mutation = is_array( $mutations[ $mutation_id ] ?? null ) ? $mutations[ $mutation_id ] : array();
			$batch_after = is_array( $mutation['after'] ?? null ) ? $mutation['after'] : array();
			if ( empty( $mutation ) || $current !== $batch_after ) {
				$conflicts[] = 'meta:' . sanitize_key( (string) $meta_key );
				continue;
			}
			$restored = $this->cloud_media_atomic_compare_and_swap_post_meta( $attachment_id, (string) $meta_key, $batch_after, $snapshot );
			if ( $this->cloud_media_transaction_rollback_unconfirmed( $restored ) ) {
				return $this->cloud_media_unknown_transaction_cleanup_conflict();
			}
			if ( is_wp_error( $restored ) || $this->cloud_media_post_meta_snapshot( $attachment_id, (string) $meta_key ) !== $snapshot ) {
				$conflicts[] = 'meta:' . sanitize_key( (string) $meta_key );
			}
		}

		$batch_manifest = $plan['_cloud_batch_manifest'] ?? null;
		if ( empty( $conflicts ) && is_object( $batch_manifest ) ) {
			$overwritten_cleanup = $this->restore_media_backup_overwritten_files( $batch_manifest );
			if ( is_wp_error( $overwritten_cleanup ) ) {
				$overwritten_data = $overwritten_cleanup->get_error_data();
				foreach ( (array) ( is_array( $overwritten_data ) ? ( $overwritten_data['conflicts'] ?? array() ) : array() ) as $conflict ) {
					$conflicts[] = 'file:' . $this->sanitize_media_file_name( (string) $conflict );
				}
				foreach ( (array) ( is_array( $overwritten_data ) ? ( $overwritten_data['failures'] ?? array() ) : array() ) as $failure ) {
					$failures[] = 'file:' . $this->sanitize_media_file_name( (string) $failure );
				}
			}
		}
		if ( ! empty( $conflicts ) ) {
			// A concurrent WordPress value may reference any batch file. Preserve the
			// complete bounded manifest for diagnosis instead of risking a broken URL.
		} elseif ( is_object( $batch_manifest ) ) {
			$manifest_cleanup = $this->cleanup_cloud_media_batch_manifest( $batch_manifest, $attachment_id );
			if ( is_wp_error( $manifest_cleanup ) ) {
				$manifest_data = $manifest_cleanup->get_error_data();
				if ( 'npcink_abilities_toolkit_cloud_adoption_cleanup_conflict' === $manifest_cleanup->get_error_code() ) {
					foreach ( (array) ( is_array( $manifest_data ) ? ( $manifest_data['conflicts'] ?? array() ) : array() ) as $conflict ) {
						$conflicts[] = 'file:' . sanitize_file_name( (string) $conflict );
					}
				} else {
					foreach ( (array) ( is_array( $manifest_data ) ? ( $manifest_data['failures'] ?? array() ) : array() ) as $failure ) {
						$failures[] = 'file:' . sanitize_file_name( (string) $failure );
					}
				}
			}
		} else {
			$failures[] = 'batch_manifest_missing';
		}

		if ( ! empty( $failures ) ) {
			return new \WP_Error(
				'npcink_abilities_toolkit_cloud_adoption_cleanup_failed',
				__( 'The failed Cloud media adoption could not be fully rolled back.', 'npcink-abilities-toolkit' ),
				array( 'status' => 500, 'failures' => $failures )
			);
		}
		if ( ! empty( $conflicts ) ) {
			return new \WP_Error(
				'npcink_abilities_toolkit_cloud_adoption_cleanup_conflict',
				__( 'Concurrent WordPress state prevented full Cloud adoption compensation.', 'npcink-abilities-toolkit' ),
				array( 'status' => 409, 'conflicts' => array_values( array_unique( $conflicts ) ) )
			);
		}

		return true;
	}

	/**
	 * Discards only this batch's materialized files after precommit drift.
	 *
	 * This path intentionally does not invoke compensating state restoration:
	 * the current WordPress values belong to the concurrent request that won.
	 *
	 * @param \WP_Error $cause Precommit drift failure.
	 * @param mixed     $batch_manifest Batch manifest object.
	 * @param int       $attachment_id Attachment id.
	 * @return \WP_Error
	 */
	private function cloud_media_adoption_precommit_failure_with_discard( \WP_Error $cause, $batch_manifest, $attachment_id ) {
		$cleaned = $this->cleanup_cloud_media_batch_manifest( $batch_manifest, absint( $attachment_id ) );
		if ( ! is_wp_error( $cleaned ) ) {
			return $cause;
		}
		if ( 'npcink_abilities_toolkit_cloud_adoption_cleanup_conflict' === $cleaned->get_error_code() ) {
			return new \WP_Error(
				'npcink_abilities_toolkit_cloud_adoption_cleanup_conflict',
				$cleaned->get_error_message(),
				array(
					'status'    => 409,
					'cause'     => $cause->get_error_code(),
					'conflicts' => (array) ( is_array( $cleaned->get_error_data() ) ? ( $cleaned->get_error_data()['conflicts'] ?? array() ) : array() ),
				)
			);
		}

		return new \WP_Error(
			'npcink_abilities_toolkit_cloud_adoption_cleanup_failed',
			$cleaned->get_error_message(),
			array(
				'status'        => 500,
				'cause'         => $cause->get_error_code(),
				'cleanup_error' => $cleaned->get_error_data(),
			)
		);
	}

	/**
	 * Preserves the original failure when compensation succeeds.
	 *
	 * @param \WP_Error           $cause Original failure.
	 * @param int                 $attachment_id Attachment id.
	 * @param array<string,mixed> $plan Adoption plan.
	 * @param array<string,mixed> $materialized Materialized derivative.
	 * @param array<string,mixed> $rollback_state Captured state.
	 * @return \WP_Error
	 */
	private function cloud_media_adoption_failure_with_cleanup( \WP_Error $cause, $attachment_id, array $plan, array $materialized, array $rollback_state ) {
		$cause_data = $cause->get_error_data();
		$cause_stage = is_array( $cause_data ) ? (string) ( $cause_data['stage'] ?? '' ) : '';
		if (
			'npcink_abilities_toolkit_cloud_adoption_mutation_conflict' === $cause->get_error_code()
			&& strlen( $cause_stage ) >= strlen( '_rollback_failed' )
			&& '_rollback_failed' === substr( $cause_stage, -strlen( '_rollback_failed' ) )
		) {
			return new \WP_Error(
				'npcink_abilities_toolkit_cloud_adoption_cleanup_conflict',
				__( 'The WordPress transaction rollback could not be confirmed, so Cloud adoption compensation was stopped.', 'npcink-abilities-toolkit' ),
				array(
					'status'    => 409,
					'cause'     => $cause->get_error_code(),
					'conflicts' => array( 'wordpress_transaction_state_unknown' ),
				)
			);
		}
		$cleaned = $this->cleanup_failed_cloud_media_adoption( $attachment_id, $plan, $materialized, $rollback_state );
		if ( is_wp_error( $cleaned ) ) {
			if ( 'npcink_abilities_toolkit_cloud_adoption_cleanup_conflict' === $cleaned->get_error_code() ) {
				return new \WP_Error(
					'npcink_abilities_toolkit_cloud_adoption_cleanup_conflict',
					$cleaned->get_error_message(),
					array(
						'status'    => 409,
						'cause'     => $cause->get_error_code(),
						'conflicts' => (array) ( is_array( $cleaned->get_error_data() ) ? ( $cleaned->get_error_data()['conflicts'] ?? array() ) : array() ),
					)
				);
			}
			return new \WP_Error(
				'npcink_abilities_toolkit_cloud_adoption_cleanup_failed',
				$cleaned->get_error_message(),
				array(
					'status'        => 500,
					'cause'         => $cause->get_error_code(),
					'cleanup_error' => $cleaned->get_error_data(),
				)
			);
		}

		return $cause;
	}

	/**
	 * Verifies the full local commit before the ability reports success.
	 *
	 * @param int                 $attachment_id Attachment id.
	 * @param array<string,mixed> $plan Adoption plan.
	 * @param array<string,mixed> $materialized Materialized derivative.
	 * @param array<string,mixed> $verification Operation verification.
	 * @return true|\WP_Error
	 */
	private function verify_cloud_media_adoption_commit( $attachment_id, array $plan, array $materialized, array $verification ) {
		$current = $this->current_media_file_state( $attachment_id );
		if ( is_wp_error( $current ) ) {
			return $current;
		}
		$expected_relative = $this->normalize_media_relative_file( (string) ( $materialized['relative_file'] ?? '' ) );
		$expected_mime = (string) ( $materialized['mime_type'] ?? '' );
		$path = (string) ( $current['file_path'] ?? '' );
		if ( $expected_relative !== (string) ( $current['relative_file'] ?? '' ) || $expected_mime !== (string) ( $current['mime_type'] ?? '' ) || '' === $path || ! is_readable( $path ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_cloud_adoption_commit_mismatch', __( 'The adopted attachment pointer did not match the verified derivative.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
		}
		$attachment_metadata = is_array( $current['metadata'] ?? null ) ? $current['metadata'] : array();
		if (
			$expected_relative !== $this->normalize_media_relative_file( (string) ( $attachment_metadata['file'] ?? '' ) )
			|| (int) ( $materialized['width'] ?? 0 ) !== (int) ( $attachment_metadata['width'] ?? 0 )
			|| (int) ( $materialized['height'] ?? 0 ) !== (int) ( $attachment_metadata['height'] ?? 0 )
			|| (int) ( $materialized['filesize_bytes'] ?? 0 ) !== (int) ( $attachment_metadata['filesize'] ?? 0 )
		) {
			return new \WP_Error( 'npcink_abilities_toolkit_cloud_adoption_attachment_metadata_mismatch', __( 'The attachment metadata did not match the verified derivative.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
		}
		$image = @getimagesize( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- final verification must fail closed without emitting warnings.
		$sha256 = is_readable( $path ) ? hash_file( 'sha256', $path ) : false;
		if (
			filesize( $path ) !== (int) ( $materialized['filesize_bytes'] ?? 0 )
			|| ! is_string( $sha256 )
			|| ! hash_equals( (string) ( $materialized['artifact_checksum'] ?? '' ), $sha256 )
			|| ! is_array( $image )
			|| ( $image['mime'] ?? '' ) !== $expected_mime
			|| ( $image[0] ?? 0 ) !== (int) ( $materialized['width'] ?? 0 )
			|| ( $image[1] ?? 0 ) !== (int) ( $materialized['height'] ?? 0 )
			|| ! Cloud_Derivative_Artifact::dimensions_within_limits( $image[0] ?? null, $image[1] ?? null )
		) {
			return new \WP_Error( 'npcink_abilities_toolkit_cloud_adoption_file_verification_failed', __( 'The committed media file no longer matches the verified transfer bytes.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
		}

		$replacement_id = (string) ( $plan['replacement_id'] ?? '' );
		$backup_relative = $this->normalize_media_relative_file( (string) ( $plan['_backup_relative_file'] ?? '' ) );
		$backup_path = $this->media_uploads_path_for_relative_file( $backup_relative );
		$original_path = (string) ( is_array( $plan['_current'] ?? null ) ? ( $plan['_current']['file_path'] ?? '' ) : '' );
		$backup_matches_original = '' !== $backup_path
			&& '' !== $original_path
			&& is_readable( $backup_path )
			&& is_readable( $original_path )
			&& filesize( $backup_path ) === filesize( $original_path )
			&& hash_equals( (string) hash_file( 'sha256', $original_path ), (string) hash_file( 'sha256', $backup_path ) );
		if ( ! $backup_matches_original ) {
			return new \WP_Error( 'npcink_abilities_toolkit_cloud_adoption_backup_verification_failed', __( 'The local rollback backup did not match the original media bytes.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
		}
		$history = $this->find_media_file_replacement_history( $attachment_id, $replacement_id );
		$latest_replacement = $this->cloud_media_post_meta_snapshot( $attachment_id, '_npcink_ai_media_latest_file_replacement' );
		$latest_derivative = $this->cloud_media_post_meta_snapshot( $attachment_id, '_npcink_ai_media_latest_optimized_derivative' );
		$artifact_id = (string) ( $materialized['artifact_id'] ?? '' );
		$derivative_recorded = false;
		foreach ( $this->get_media_optimized_derivatives( $attachment_id ) as $derivative ) {
			if ( $artifact_id === (string) ( $derivative['artifact_id'] ?? '' ) && $expected_relative === (string) ( $derivative['relative_file'] ?? '' ) ) {
				$derivative_recorded = true;
				break;
			}
		}
		if (
			empty( $history )
			|| ! $latest_replacement['exists']
			|| $replacement_id !== (string) ( is_array( $latest_replacement['value'] ) ? ( $latest_replacement['value']['replacement_id'] ?? '' ) : '' )
			|| ! $derivative_recorded
			|| ! $latest_derivative['exists']
			|| $artifact_id !== (string) ( is_array( $latest_derivative['value'] ) ? ( $latest_derivative['value']['artifact_id'] ?? '' ) : '' )
		) {
			return new \WP_Error( 'npcink_abilities_toolkit_cloud_adoption_metadata_verification_failed', __( 'The local derivative and rollback metadata were not recorded completely.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
		}

		if ( empty( $verification['media_file_matches_expected'] ) || empty( $verification['media_mime_type_matches_expected'] ) || empty( $verification['backup_available'] ) || empty( $verification['rollback_available'] ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_cloud_adoption_rollback_verification_failed', __( 'The local media adoption did not retain a verified rollback path.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
		}
		foreach ( (array) ( $verification['post_references_verified'] ?? array() ) as $post_reference ) {
			if ( ! is_array( $post_reference ) || empty( $post_reference['old_url_absent'] ) || empty( $post_reference['new_url_present'] ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_cloud_adoption_reference_verification_failed', __( 'A local content reference repair could not be verified.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
			}
		}

		return true;
	}

	/**
	 * Captures filesystem identities before WordPress generates attachment sizes.
	 *
	 * @param string $directory Directory path.
	 * @return array<string,array<string,mixed>>
	 */
	private function cloud_media_directory_file_snapshot( $directory ) {
		$directory = (string) $directory;
		$files = is_dir( $directory ) ? glob( $this->trailingslashit_value( $directory ) . '*' ) : array();
		$files = is_array( $files ) ? $files : array();
		$snapshot = array();
		foreach ( $files as $path ) {
			if ( is_string( $path ) && is_file( $path ) ) {
				$snapshot[ $path ] = $this->cloud_media_created_file_record( $path );
			}
		}

		return $snapshot;
	}

	/**
	 * Adds newly generated sizes/original-image files to the batch manifest.
	 *
	 * Files that existed before generation are deliberately never claimed by the
	 * batch, so compensation cannot delete another request's file.
	 *
	 * @param mixed                                   $manifest Batch manifest object.
	 * @param array<string,mixed>                     $metadata Generated attachment metadata.
	 * @param array<string,array<string,mixed>>       $pre_generation_files Files present before generation.
	 * @return void
	 */
	private function track_cloud_media_generated_files( $manifest, array $metadata, array $pre_generation_files ) {
		foreach ( $this->cloud_media_metadata_file_paths( $metadata ) as $path ) {
			if ( isset( $pre_generation_files[ $path ] ) || ! is_file( $path ) ) {
				continue;
			}
			$this->add_cloud_media_created_file_to_manifest( $manifest, $this->cloud_media_created_file_record( $path ) );
		}
	}

	/**
	 * Creates one Cloud adoption file without ever overwriting an existing path.
	 *
	 * @param string              $target_path Target path.
	 * @param string              $contents File contents.
	 * @param array<string,mixed> $context Operation context.
	 * @return array<string,mixed>|\WP_Error Created-file ownership record or error.
	 */
	private function write_cloud_media_file_exclusive( $target_path, $contents, array $context = array() ) {
		$target_path = (string) $target_path;
		$contents = (string) $contents;
		if ( ! $this->is_media_uploads_path_allowed( $target_path ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_cloud_derivative_path_invalid', __( 'The local derivative path is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		$blocked = apply_filters( 'npcink_abilities_toolkit_media_file_write_blocked', false, $target_path, strlen( $contents ), $context );
		if ( true === $blocked ) {
			return new \WP_Error( 'npcink_abilities_toolkit_cloud_derivative_write_failed', __( 'The local derivative file could not be written.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
		}

		$handle = @fopen( $target_path, 'x+b' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- WP_Filesystem cannot guarantee atomic exclusive creation; conflicts are expected and fail closed.
		if ( false === $handle ) {
			return new \WP_Error( 'npcink_abilities_toolkit_cloud_derivative_target_conflict', __( 'The local derivative target was created by another request.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
		}
		$stat = fstat( $handle );
		$created_file = $this->cloud_media_created_file_record( $target_path, is_array( $stat ) ? $stat : array() );
		$written = 0;
		$length = strlen( $contents );
		while ( $written < $length ) {
			$chunk = fwrite( $handle, substr( $contents, $written ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- preserve exclusive open-handle ownership for the complete write.
			if ( false === $chunk || 0 === $chunk ) {
				break;
			}
			$written += $chunk;
		}
		$flushed = fflush( $handle );
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- closes the exclusive creation handle above.
		if ( $written !== $length || ! $flushed ) {
			$cause = new \WP_Error( 'npcink_abilities_toolkit_cloud_derivative_write_failed', __( 'The local derivative file could not be written.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
			return $this->cloud_media_created_file_failure_with_discard( $cause, $created_file );
		}

		return $created_file;
	}

	/**
	 * Copies a Cloud adoption backup through an exclusive destination handle.
	 *
	 * @param string              $source_path Source path.
	 * @param string              $target_path Target path.
	 * @param array<string,mixed> $context Operation context.
	 * @return array<string,mixed>|\WP_Error Created-file ownership record or error.
	 */
	private function copy_cloud_media_file_exclusive( $source_path, $target_path, array $context = array() ) {
		$source_path = (string) $source_path;
		$target_path = (string) $target_path;
		if ( ! is_readable( $source_path ) || ! $this->is_media_uploads_path_allowed( $target_path ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_backup_failed', __( 'The current attachment file could not be backed up.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
		}
		$blocked = apply_filters( 'npcink_abilities_toolkit_media_file_copy_blocked', false, $source_path, $target_path, $context );
		if ( true === $blocked ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_backup_failed', __( 'The current attachment file could not be backed up.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
		}
		$source = @fopen( $source_path, 'rb' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- paired handles are required for exclusive destination creation; errors become bounded WP_Error values.
		if ( false === $source ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_backup_failed', __( 'The current attachment file could not be opened for backup.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
		}
		$target = @fopen( $target_path, 'x+b' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- WP_Filesystem cannot guarantee atomic exclusive creation; conflicts fail closed.
		if ( false === $target ) {
			fclose( $source ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- closes the paired source handle after an exclusive-create conflict.
			return new \WP_Error( 'npcink_abilities_toolkit_media_backup_failed', __( 'The current attachment backup target is unavailable.', 'npcink-abilities-toolkit' ), array( 'status' => file_exists( $target_path ) ? 409 : 500 ) );
		}
		$stat = fstat( $target );
		$created_file = $this->cloud_media_created_file_record( $target_path, is_array( $stat ) ? $stat : array() );
		$copied = stream_copy_to_stream( $source, $target );
		$flushed = fflush( $target );
		fclose( $source ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- closes the paired source handle after streaming.
		fclose( $target ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- closes the exclusive destination handle after streaming.
		$source_size = filesize( $source_path );
		$target_size = is_readable( $target_path ) ? filesize( $target_path ) : false;
		if ( false === $copied || ! $flushed || $source_size !== $target_size ) {
			$cause = new \WP_Error( 'npcink_abilities_toolkit_media_backup_failed', __( 'The current attachment file could not be backed up.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
			return $this->cloud_media_created_file_failure_with_discard( $cause, $created_file );
		}

		return $created_file;
	}

	/**
	 * Records the filesystem identity of a file exclusively created by this batch.
	 *
	 * @param string              $path Absolute path.
	 * @param array<string,mixed> $stat Optional open-handle stat.
	 * @return array<string,mixed>
	 */
	private function cloud_media_created_file_record( $path, array $stat = array() ) {
		if ( empty( $stat ) ) {
			$read_stat = @lstat( (string) $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- missing files are represented as an empty identity.
			$stat = is_array( $read_stat ) ? $read_stat : array();
		}

		return array(
			'path'                  => (string) $path,
			'device'                => isset( $stat['dev'] ) ? (int) $stat['dev'] : -1,
			'inode'                 => isset( $stat['ino'] ) ? (int) $stat['ino'] : -1,
			'created_by_this_batch' => true,
		);
	}

	/**
	 * Adds a created file to the mutable in-memory Cloud adoption manifest.
	 *
	 * @param mixed               $manifest Batch manifest object.
	 * @param array<string,mixed> $created_file Created-file record.
	 * @return void
	 */
	private function add_cloud_media_created_file_to_manifest( $manifest, array $created_file ) {
		if ( ! is_object( $manifest ) || empty( $created_file['created_by_this_batch'] ) ) {
			return;
		}
		$created_files = isset( $manifest->created_files ) && is_array( $manifest->created_files ) ? $manifest->created_files : array();
		foreach ( $created_files as $existing ) {
			if ( is_array( $existing ) && (string) ( $existing['path'] ?? '' ) === (string) ( $created_file['path'] ?? '' ) && (int) ( $existing['inode'] ?? -1 ) === (int) ( $created_file['inode'] ?? -1 ) ) {
				return;
			}
		}
		$created_files[] = $created_file;
		$manifest->created_files = $created_files;
	}

	/**
	 * Applies one attachment meta mutation only while its exact reviewed value remains current.
	 *
	 * @param mixed  $manifest Batch manifest object.
	 * @param int    $post_id Attachment id.
	 * @param string $meta_key Meta key.
	 * @param mixed  $after Batch value.
	 * @return true|\WP_Error
	 */
	private function cloud_media_cas_update_post_meta( $manifest, $post_id, $meta_key, $after ) {
		$post_id = absint( $post_id );
		$meta_key = (string) $meta_key;
		$mutation_id = 'meta:' . $post_id . ':' . $meta_key;
		$mutations = is_object( $manifest ) && isset( $manifest->mutations ) && is_array( $manifest->mutations ) ? $manifest->mutations : array();
		$existing_mutation = is_array( $mutations[ $mutation_id ] ?? null ) ? $mutations[ $mutation_id ] : array();
		$rollback_state = is_object( $manifest ) && isset( $manifest->rollback_state ) && is_array( $manifest->rollback_state ) ? $manifest->rollback_state : array();
		$before = ! empty( $existing_mutation )
			? ( is_array( $existing_mutation['after'] ?? null ) ? $existing_mutation['after'] : array() )
			: ( is_array( $rollback_state['meta'][ $meta_key ] ?? null ) ? $rollback_state['meta'][ $meta_key ] : array() );
		if ( empty( $before ) ) {
			return $this->cloud_media_mutation_conflict( 'meta:' . sanitize_key( $meta_key ) );
		}
		$after_snapshot = array( 'exists' => true, 'value' => $after );
		$mutations[ $mutation_id ] = array(
			'kind'     => 'meta',
			'post_id'  => $post_id,
			'meta_key' => $meta_key,
			'before'   => ! empty( $existing_mutation ) ? $existing_mutation['before'] : $before,
			'after'    => $after_snapshot,
		);
		$manifest->mutations = $mutations;

		return $this->cloud_media_atomic_compare_and_swap_post_meta( $post_id, $meta_key, $before, $after_snapshot );
	}

	/**
	 * Changes one allowlisted attachment meta value under an attachment row lock.
	 *
	 * Raw postmeta reads provide byte-exact comparison while the WordPress Meta
	 * API remains the only writer so hooks and cache invalidation still run.
	 *
	 * @param int                       $post_id Attachment id.
	 * @param string                    $meta_key Allowlisted meta key.
	 * @param array{exists:bool,value:mixed} $before Expected snapshot.
	 * @param array{exists:bool,value:mixed} $after Replacement snapshot.
	 * @return true|\WP_Error
	 */
	private function cloud_media_atomic_compare_and_swap_post_meta( $post_id, $meta_key, array $before, array $after ) {
		global $wpdb;
		$post_id = absint( $post_id );
		$meta_key = (string) $meta_key;
		$allowed_meta_keys = array(
			'_wp_attached_file',
			'_wp_attachment_metadata',
			'_npcink_ai_media_file_replacement_history',
			'_npcink_ai_media_latest_file_replacement',
			'_npcink_ai_media_optimized_derivatives',
			'_npcink_ai_media_latest_optimized_derivative',
		);
		if ( $this->cloud_media_transaction_poisoned ) {
			return $this->cloud_media_mutation_conflict( 'meta:' . sanitize_key( $meta_key ), 'transaction_unusable_rollback_failed' );
		}
		if ( $this->cloud_media_transaction_active ) {
			return $this->cloud_media_mutation_conflict( 'meta:' . sanitize_key( $meta_key ), 'transaction_reentrant' );
		}
		if (
			! in_array( $meta_key, $allowed_meta_keys, true )
			|| ! array_key_exists( 'exists', $before )
			|| ! array_key_exists( 'exists', $after )
			|| ! is_bool( $before['exists'] )
			|| ! is_bool( $after['exists'] )
			|| ! is_object( $wpdb )
			|| ! isset( $wpdb->posts, $wpdb->postmeta )
			|| ! method_exists( $wpdb, 'query' )
			|| ! method_exists( $wpdb, 'prepare' )
			|| ! method_exists( $wpdb, 'get_row' )
			|| ! method_exists( $wpdb, 'get_results' )
			|| ! function_exists( 'maybe_serialize' )
			|| ! function_exists( 'update_post_meta' )
			|| ! function_exists( 'add_post_meta' )
			|| ! function_exists( 'delete_post_meta' )
		) {
			return new \WP_Error( 'npcink_abilities_toolkit_cloud_adoption_atomic_update_unavailable', __( 'Atomic WordPress metadata mutation support is unavailable.', 'npcink-abilities-toolkit' ), array( 'status' => 500, 'field' => sanitize_key( $meta_key ) ) );
		}

		$this->cloud_media_transaction_active = true;
		try {
			if ( false === $wpdb->query( 'START TRANSACTION' ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- the attachment lock serializes local Cloud batches.
				return $this->cloud_media_rollback_locked_post_meta_mutation( $post_id, $meta_key, 'transaction_start_failed' );
			}
			$parent_sql = $wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE ID = %d FOR UPDATE", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- the table comes from wpdb.
				$post_id
			);
			$parent_row = $wpdb->get_row( $parent_sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- $parent_sql is prepared immediately above and provides the attachment serialization lock.
			if ( ! is_object( $parent_row ) || $post_id !== absint( $parent_row->ID ?? 0 ) ) {
				return $this->cloud_media_rollback_locked_post_meta_mutation( $post_id, $meta_key, 'attachment_lock_failed' );
			}
			$meta_sql = $wpdb->prepare(
				"SELECT meta_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s ORDER BY meta_id ASC FOR UPDATE", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table comes from wpdb and values are prepared.
				$post_id,
				$meta_key
			);
			$before_rows = $wpdb->get_results( $meta_sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- $meta_sql is prepared immediately above; raw locked values are required for byte-exact comparison.
			if ( ! is_array( $before_rows ) || ! $this->cloud_media_locked_post_meta_rows_match( $before_rows, $meta_key, $before ) ) {
				return $this->cloud_media_rollback_locked_post_meta_mutation( $post_id, $meta_key, 'locked_value_drift' );
			}
			$before_meta_id = ! empty( $before['exists'] ) && isset( $before_rows[0]->meta_id ) ? absint( $before_rows[0]->meta_id ) : 0;

			if ( ! empty( $after['exists'] ) ) {
				$written = ! empty( $before['exists'] )
					? update_post_meta( $post_id, $meta_key, $after['value'] ?? null )
					: add_post_meta( $post_id, $meta_key, $after['value'] ?? null, true );
			} else {
				$written = ! empty( $before['exists'] ) && delete_post_meta( $post_id, $meta_key );
			}
			if ( false === $written ) {
				return $this->cloud_media_rollback_locked_post_meta_mutation( $post_id, $meta_key, 'wordpress_meta_update_failed' );
			}

			$after_rows = $wpdb->get_results( $meta_sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- reuse the prepared locked-row query to verify the exact committed candidate.
			if ( ! is_array( $after_rows ) || ! $this->cloud_media_locked_post_meta_rows_match( $after_rows, $meta_key, $after ) ) {
				return $this->cloud_media_rollback_locked_post_meta_mutation( $post_id, $meta_key, 'wordpress_meta_write_drift' );
			}
			if ( ! empty( $before['exists'] ) && ! empty( $after['exists'] ) && $before_meta_id !== absint( $after_rows[0]->meta_id ?? 0 ) ) {
				return $this->cloud_media_rollback_locked_post_meta_mutation( $post_id, $meta_key, 'wordpress_meta_identity_drift' );
			}

			if ( false === $wpdb->query( 'COMMIT' ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- commit closes the bounded attachment/meta lock transaction.
				return $this->cloud_media_rollback_locked_post_meta_mutation( $post_id, $meta_key, 'transaction_commit_failed' );
			}
			$this->cloud_media_clean_post_meta_cache( $post_id );
		} catch ( \Throwable $exception ) {
			unset( $exception );
			return $this->cloud_media_rollback_locked_post_meta_mutation( $post_id, $meta_key, 'transaction_exception' );
		} finally {
			$this->cloud_media_transaction_active = false;
		}

		return true;
	}

	/**
	 * Checks an exact raw postmeta row set against one logical snapshot.
	 *
	 * @param array<int,mixed>           $rows Locked raw rows.
	 * @param string                     $meta_key Expected exact key.
	 * @param array{exists:bool,value:mixed} $snapshot Expected snapshot.
	 * @return bool
	 */
	private function cloud_media_locked_post_meta_rows_match( array $rows, $meta_key, array $snapshot ) {
		if ( empty( $snapshot['exists'] ) ) {
			return empty( $rows );
		}
		if ( 1 !== count( $rows ) || ! is_object( $rows[0] ) ) {
			return false;
		}

		return absint( $rows[0]->meta_id ?? 0 ) > 0
			&& (string) ( $rows[0]->meta_key ?? '' ) === (string) $meta_key
			&& (string) ( $rows[0]->meta_value ?? '' ) === (string) maybe_serialize( $snapshot['value'] ?? null );
	}

	/**
	 * Rolls back one locked postmeta mutation and clears its object cache.
	 *
	 * @param int    $post_id Attachment id.
	 * @param string $meta_key Meta key.
	 * @param string $stage Failure stage.
	 * @return \WP_Error
	 */
	private function cloud_media_rollback_locked_post_meta_mutation( $post_id, $meta_key, $stage ) {
		global $wpdb;
		$rolled_back = false;
		try {
			$rolled_back = is_object( $wpdb ) && method_exists( $wpdb, 'query' ) && false !== $wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- rollback is mandatory transaction compensation.
		} catch ( \Throwable $exception ) {
			unset( $exception );
		}
		$this->cloud_media_clean_post_meta_cache( absint( $post_id ) );
		if ( ! $rolled_back ) {
			$this->cloud_media_transaction_poisoned = true;
			$stage .= '_rollback_failed';
		}

		return $this->cloud_media_mutation_conflict( 'meta:' . sanitize_key( (string) $meta_key ), $stage );
	}

	/**
	 * Clears the postmeta cache after raw transaction commit or rollback.
	 *
	 * @param int $post_id Post id.
	 * @return void
	 */
	private function cloud_media_clean_post_meta_cache( $post_id ) {
		if ( function_exists( 'wp_cache_delete' ) ) {
			wp_cache_delete( absint( $post_id ), 'post_meta' );
		}
		if ( function_exists( 'clean_post_cache' ) ) {
			clean_post_cache( absint( $post_id ) );
		}
	}

	/**
	 * Applies the attachment MIME mutation only while the reviewed MIME remains current.
	 *
	 * @param mixed  $manifest Batch manifest object.
	 * @param int    $attachment_id Attachment id.
	 * @param string $after Batch MIME value.
	 * @return true|\WP_Error
	 */
	private function cloud_media_cas_update_attachment_mime( $manifest, $attachment_id, $after ) {
		$attachment_id = absint( $attachment_id );
		$mutation_id = 'mime:' . $attachment_id;
		$mutations = is_object( $manifest ) && isset( $manifest->mutations ) && is_array( $manifest->mutations ) ? $manifest->mutations : array();
		$existing_mutation = is_array( $mutations[ $mutation_id ] ?? null ) ? $mutations[ $mutation_id ] : array();
		$rollback_state = is_object( $manifest ) && isset( $manifest->rollback_state ) && is_array( $manifest->rollback_state ) ? $manifest->rollback_state : array();
		$before = ! empty( $existing_mutation ) ? (string) ( $existing_mutation['after'] ?? '' ) : (string) ( $rollback_state['attachment_mime_type'] ?? '' );
		$current = function_exists( 'get_post_mime_type' ) ? (string) get_post_mime_type( $attachment_id ) : '';
		if ( $current !== $before ) {
			return $this->cloud_media_mutation_conflict( 'attachment_mime_type' );
		}
		$mutations[ $mutation_id ] = array(
			'kind'    => 'mime',
			'post_id' => $attachment_id,
			'before'  => ! empty( $existing_mutation ) ? $existing_mutation['before'] : $before,
			'after'   => (string) $after,
		);
		$manifest->mutations = $mutations;
		$updated = $this->cloud_media_atomic_compare_and_swap_post_field( $attachment_id, 'post_mime_type', $before, (string) $after );
		if ( is_wp_error( $updated ) ) {
			return $updated;
		}
		if ( (string) get_post_mime_type( $attachment_id ) !== (string) $after ) {
			return $this->cloud_media_mutation_conflict( 'attachment_mime_type' );
		}

		return true;
	}

	/**
	 * Applies one reviewed post-content mutation with an exact expected-before guard.
	 *
	 * @param mixed  $manifest Batch manifest object.
	 * @param int    $post_id Post id.
	 * @param string $expected_before Expected reviewed content.
	 * @param string $after Batch content.
	 * @return true|\WP_Error
	 */
	private function cloud_media_cas_update_post_content( $manifest, $post_id, $expected_before, $after ) {
		$post_id = absint( $post_id );
		$mutation_id = 'post_content:' . $post_id;
		$mutations = is_object( $manifest ) && isset( $manifest->mutations ) && is_array( $manifest->mutations ) ? $manifest->mutations : array();
		$existing_mutation = is_array( $mutations[ $mutation_id ] ?? null ) ? $mutations[ $mutation_id ] : array();
		$rollback_state = is_object( $manifest ) && isset( $manifest->rollback_state ) && is_array( $manifest->rollback_state ) ? $manifest->rollback_state : array();
		$reviewed_before = ! empty( $existing_mutation ) ? (string) ( $existing_mutation['after'] ?? '' ) : (string) ( $rollback_state['post_contents'][ $post_id ] ?? '' );
		$post = get_post( $post_id );
		$current = is_object( $post ) ? (string) ( $post->post_content ?? '' ) : '';
		if ( $current !== $reviewed_before || (string) $expected_before !== $reviewed_before ) {
			return $this->cloud_media_mutation_conflict( 'post_content:' . $post_id );
		}
		$mutations[ $mutation_id ] = array(
			'kind'    => 'post_content',
			'post_id' => $post_id,
			'before'  => ! empty( $existing_mutation ) ? $existing_mutation['before'] : $reviewed_before,
			'after'   => (string) $after,
		);
		$manifest->mutations = $mutations;
		$updated = $this->cloud_media_atomic_compare_and_swap_post_field( $post_id, 'post_content', $reviewed_before, (string) $after );
		if ( is_wp_error( $updated ) ) {
			return $updated;
		}
		$post = get_post( $post_id );
		if ( ! is_object( $post ) || (string) ( $post->post_content ?? '' ) !== (string) $after ) {
			return $this->cloud_media_mutation_conflict( 'post_content:' . $post_id );
		}

		return true;
	}

	/**
	 * Changes one allowlisted wp_posts field under a row lock through WordPress lifecycle APIs.
	 *
	 * @param int    $post_id Post id.
	 * @param string $field post_mime_type or post_content.
	 * @param string $before Expected current value.
	 * @param string $after Replacement value.
	 * @return true|\WP_Error
	 */
	private function cloud_media_atomic_compare_and_swap_post_field( $post_id, $field, $before, $after ) {
		global $wpdb;
		$field = (string) $field;
		$post_id = absint( $post_id );
		if ( $this->cloud_media_transaction_poisoned ) {
			return $this->cloud_media_mutation_conflict( $field . ':' . $post_id, 'transaction_unusable_rollback_failed' );
		}
		if ( $this->cloud_media_transaction_active ) {
			return $this->cloud_media_mutation_conflict( $field . ':' . $post_id, 'transaction_reentrant' );
		}
		if (
			! in_array( $field, array( 'post_mime_type', 'post_content' ), true )
			|| ! is_object( $wpdb )
			|| ! isset( $wpdb->posts )
			|| ! method_exists( $wpdb, 'query' )
			|| ! method_exists( $wpdb, 'prepare' )
			|| ! method_exists( $wpdb, 'get_row' )
			|| ! function_exists( 'wp_update_post' )
		) {
			return new \WP_Error( 'npcink_abilities_toolkit_cloud_adoption_atomic_update_unavailable', __( 'Atomic WordPress post mutation support is unavailable.', 'npcink-abilities-toolkit' ), array( 'status' => 500, 'field' => sanitize_key( $field ) ) );
		}

		$this->cloud_media_transaction_active = true;
		try {
			if ( false === $wpdb->query( 'START TRANSACTION' ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- bounded row lock is required for lifecycle-safe compare-and-swap.
				return $this->cloud_media_rollback_locked_post_mutation( $field, $post_id, 'transaction_start_failed' );
			}
			$select_sql = $wpdb->prepare(
				"SELECT `{$field}` FROM {$wpdb->posts} WHERE ID = %d FOR UPDATE", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- field is allowlisted and the table comes from wpdb.
				$post_id
			);
			$locked_row = $wpdb->get_row( $select_sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- $select_sql is prepared immediately above and provides the transaction row lock.
			if ( ! is_object( $locked_row ) || ! property_exists( $locked_row, $field ) || (string) $locked_row->{$field} !== (string) $before ) {
				return $this->cloud_media_rollback_locked_post_mutation( $field, $post_id, 'locked_value_drift' );
			}
			if ( function_exists( 'clean_post_cache' ) ) {
				clean_post_cache( $post_id );
			}

			$updated = wp_update_post( array( 'ID' => $post_id, $field => (string) $after ), true );
			if ( is_wp_error( $updated ) || $post_id !== absint( $updated ) ) {
				return $this->cloud_media_rollback_locked_post_mutation( $field, $post_id, 'wordpress_update_failed' );
			}

			$written_row = $wpdb->get_row( $select_sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- reuse the prepared row-lock query for exact verification.
			if ( ! is_object( $written_row ) || ! property_exists( $written_row, $field ) || (string) $written_row->{$field} !== (string) $after ) {
				return $this->cloud_media_rollback_locked_post_mutation( $field, $post_id, 'wordpress_write_drift' );
			}

			if ( false === $wpdb->query( 'COMMIT' ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- commit closes the bounded row-lock transaction.
				return $this->cloud_media_rollback_locked_post_mutation( $field, $post_id, 'transaction_commit_failed' );
			}
			if ( function_exists( 'clean_post_cache' ) ) {
				clean_post_cache( $post_id );
			}
		} catch ( \Throwable $exception ) {
			unset( $exception );
			return $this->cloud_media_rollback_locked_post_mutation( $field, $post_id, 'transaction_exception' );
		} finally {
			$this->cloud_media_transaction_active = false;
		}

		return true;
	}

	/**
	 * Rolls back one bounded row-lock mutation and returns a conflict.
	 *
	 * @param string $field Allowlisted post field.
	 * @param int    $post_id Post id.
	 * @param string $stage Failure stage.
	 * @return \WP_Error
	 */
	private function cloud_media_rollback_locked_post_mutation( $field, $post_id, $stage ) {
		global $wpdb;
		$rolled_back = false;
		try {
			$rolled_back = is_object( $wpdb ) && method_exists( $wpdb, 'query' ) && false !== $wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- rollback is mandatory compensation for this bounded transaction.
		} catch ( \Throwable $exception ) {
			unset( $exception );
		}
		if ( function_exists( 'clean_post_cache' ) ) {
			clean_post_cache( absint( $post_id ) );
		}
		if ( ! $rolled_back ) {
			$this->cloud_media_transaction_poisoned = true;
			$stage .= '_rollback_failed';
		}

		return $this->cloud_media_mutation_conflict( (string) $field . ':' . absint( $post_id ), $stage );
	}

	/**
	 * Returns one bounded logical mutation conflict.
	 *
	 * @param string $field Safe field identifier.
	 * @param string $stage Bounded failure stage.
	 * @return \WP_Error
	 */
	private function cloud_media_mutation_conflict( $field, $stage = 'compare' ) {
		return new \WP_Error(
			'npcink_abilities_toolkit_cloud_adoption_mutation_conflict',
			__( 'WordPress state changed before the Cloud adoption mutation could commit.', 'npcink-abilities-toolkit' ),
			array( 'status' => 409, 'field' => sanitize_text_field( (string) $field ), 'stage' => sanitize_key( (string) $stage ) )
		);
	}

	/**
	 * Detects an unconfirmed transaction rollback that forbids further cleanup.
	 *
	 * @param mixed $result Mutation result.
	 * @return bool
	 */
	private function cloud_media_transaction_rollback_unconfirmed( $result ) {
		if ( ! is_wp_error( $result ) ) {
			return false;
		}
		$data = $result->get_error_data();
		$stage = is_array( $data ) ? (string) ( $data['stage'] ?? '' ) : '';

		return strlen( $stage ) >= strlen( '_rollback_failed' )
			&& '_rollback_failed' === substr( $stage, -strlen( '_rollback_failed' ) );
	}

	/**
	 * Returns the stop-all cleanup result for an unknown transaction state.
	 *
	 * @return \WP_Error
	 */
	private function cloud_media_unknown_transaction_cleanup_conflict() {
		return new \WP_Error(
			'npcink_abilities_toolkit_cloud_adoption_cleanup_conflict',
			__( 'The WordPress transaction rollback could not be confirmed, so Cloud adoption compensation was stopped.', 'npcink-abilities-toolkit' ),
			array( 'status' => 409, 'conflicts' => array( 'wordpress_transaction_state_unknown' ) )
		);
	}

	/**
	 * Deletes a file only while its device/inode still matches this batch.
	 *
	 * @param array<string,mixed> $created_file Created-file record.
	 * @return bool
	 */
	private function discard_cloud_media_created_file( array $created_file ) {
		$path = (string) ( $created_file['path'] ?? '' );
		if ( empty( $created_file['created_by_this_batch'] ) || '' === $path || ! $this->is_media_uploads_path_allowed( $path ) ) {
			return false;
		}
		if ( ! is_file( $path ) ) {
			return true;
		}
		$current_stat = @lstat( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- identity mismatch means an external file must be preserved.
		if ( ! is_array( $current_stat ) ) {
			return true;
		}
		if ( (int) ( $created_file['device'] ?? -1 ) !== (int) ( $current_stat['dev'] ?? -2 ) || (int) ( $created_file['inode'] ?? -1 ) !== (int) ( $current_stat['ino'] ?? -2 ) ) {
			return true;
		}
		wp_delete_file( $path );

		return ! is_file( $path );
	}

	/**
	 * Removes every file known to have been created by this batch.
	 *
	 * @param mixed $manifest Batch manifest object.
	 * @param int   $attachment_id Optional attachment id for current-reference protection.
	 * @return true|\WP_Error
	 */
	private function cleanup_cloud_media_batch_manifest( $manifest, $attachment_id = 0 ) {
		if ( ! is_object( $manifest ) ) {
			return true;
		}
		$failures = array();
		$conflicts = array();
		$protected_paths = array();
		$attachment_id = absint( $attachment_id );
		if ( $attachment_id > 0 ) {
			$current_relative = $this->normalize_media_relative_file( (string) get_post_meta( $attachment_id, '_wp_attached_file', true ) );
			$current_path = '' !== $current_relative ? $this->media_uploads_path_for_relative_file( $current_relative ) : '';
			if ( '' !== $current_path ) {
				$protected_paths[ $current_path ] = true;
			}
			$current_metadata = wp_get_attachment_metadata( $attachment_id );
			foreach ( $this->cloud_media_metadata_file_paths( is_array( $current_metadata ) ? $current_metadata : array() ) as $metadata_path ) {
				$protected_paths[ $metadata_path ] = true;
			}
		}
		$created_files = isset( $manifest->created_files ) && is_array( $manifest->created_files ) ? array_reverse( $manifest->created_files ) : array();
		foreach ( $created_files as $created_file ) {
			$path = (string) ( is_array( $created_file ) ? ( $created_file['path'] ?? '' ) : '' );
			if ( '' !== $path && isset( $protected_paths[ $path ] ) ) {
				$conflicts[] = basename( $path );
			}
		}
		if ( ! empty( $conflicts ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_cloud_adoption_cleanup_conflict', __( 'Current WordPress media state still references files from the failed Cloud adoption batch.', 'npcink-abilities-toolkit' ), array( 'status' => 409, 'conflicts' => $conflicts ) );
		}
		foreach ( $created_files as $created_file ) {
			$path = (string) ( is_array( $created_file ) ? ( $created_file['path'] ?? '' ) : '' );
			if ( ! is_array( $created_file ) || ! $this->discard_cloud_media_created_file( $created_file ) ) {
				$failures[] = basename( $path );
			}
		}
		if ( ! empty( $failures ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_cloud_adoption_cleanup_failed', __( 'The failed Cloud media adoption could not remove all files created by its batch.', 'npcink-abilities-toolkit' ), array( 'status' => 500, 'failures' => $failures ) );
		}
		return true;
	}

	/**
	 * Preserves a file-creation failure only after its owned file is discarded.
	 *
	 * @param \WP_Error           $cause Original failure.
	 * @param array<string,mixed> $created_file Created-file record.
	 * @return \WP_Error
	 */
	private function cloud_media_created_file_failure_with_discard( \WP_Error $cause, array $created_file ) {
		if ( $this->discard_cloud_media_created_file( $created_file ) ) {
			return $cause;
		}

		return new \WP_Error( 'npcink_abilities_toolkit_cloud_adoption_cleanup_failed', __( 'The failed Cloud media file could not be safely removed.', 'npcink-abilities-toolkit' ), array( 'status' => 500, 'cause' => $cause->get_error_code() ) );
	}
}
