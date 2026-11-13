<?php
/**
 * Reviewed Cloud media adoption.
 *
 * @package NpcinkAbilitiesToolkit
 */
namespace Npcink_Abilities_Toolkit\Packages;
use Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact;
if ( ! defined( 'ABSPATH' ) ) { exit; }
trait Cloud_Media_Adoption_Write_Methods {

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

		if ( ! $this->should_dry_run( $input ) && ! $this->media_backup_lock_active ) {
			return $this->with_media_backup_lock( function () use ( $input ) { return $this->adopt_cloud_media_derivative( $input ); } );
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
			$payload['content_reference_repairs'],
			is_array( $plan['content_reference_repair_expectations'] ?? null ) ? $plan['content_reference_repair_expectations'] : array()
		);
		if ( is_wp_error( $expectation_error ) ) {
			return $expectation_error;
		}
		$rollback_state = $this->capture_cloud_media_adoption_state(
			$attachment_id,
			$payload['content_reference_repairs'],
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
			$payload['content_reference_repairs'],
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

		$temporary = $this->plan_temporary_media_backup( $current, $input );
		if ( is_wp_error( $temporary ) ) { return $temporary; }
		$replacement_id = 'cloud_media_replace_' . gmdate( 'Ymd_His' ) . '_' . substr( md5( $attachment_id . '|' . (string) $artifact['artifact_id'] . '|' . microtime( true ) ), 0, 8 );
		$backup_suffix = sanitize_key( (string) ( $input['backup_suffix'] ?? 'npcink-abilities-toolkit-cloud-backup' ) );
		$backup_suffix = '' !== $backup_suffix ? substr( $backup_suffix, 0, 48 ) : 'npcink-abilities-toolkit-cloud-backup';
		$backup_relative = $temporary ? (string) $current['relative_file'] : $this->backup_relative_file_for_current_media( $current, $replacement_id, $backup_suffix );
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
				'expires_at_gmt' => (string) ( $temporary['expires_at_gmt'] ?? '' ),
			),
			'temporary_backup' => $temporary,
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

}
