<?php
/**
 * Media ALT and caption review methods for Core_Read_Package.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides review-only media ALT/caption candidates and governed apply plans.
 */
trait Media_Alt_Caption_Read_Methods {
	/**
	 * Builds a review-only ALT/caption candidate set from supplied media metadata.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function build_media_alt_caption_review_set( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'upload_files' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to review media ALT and caption candidates.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$media_snapshot = is_array( $input['media_snapshot'] ?? null ) ? $input['media_snapshot'] : array();
		if ( empty( $media_snapshot ) && is_array( $input['items'] ?? null ) ) {
			$media_snapshot = $input;
		}

		$max_items = $this->absint_value( $input['review_set_limit'] ?? ( $input['max_items'] ?? 5 ) );
		if ( 0 >= $max_items ) {
			$max_items = 5;
		}
		$max_items = max( 1, min( 10, $max_items ) );

		$image_context_evidence = is_array( $input['image_context_evidence'] ?? null ) ? $input['image_context_evidence'] : array();
		$items                  = is_array( $media_snapshot['items'] ?? null ) ? $media_snapshot['items'] : array();
		$image_context_by_id    = $this->media_alt_caption_index_image_context_evidence( $image_context_evidence );
		$source_policy          = $this->media_alt_caption_review_source_policy( $media_snapshot );
		$media_scope            = sanitize_key( (string) ( $media_snapshot['media_scope'] ?? ( 'current_article_media_metadata_only' === (string) ( $media_snapshot['snapshot_policy'] ?? '' ) ? 'current_article_used_images' : 'media_library_sample' ) ) );
		$post_context           = is_array( $media_snapshot['post_context'] ?? null ) ? $this->media_alt_caption_sanitize_payload( $media_snapshot['post_context'] ) : array();
		$selected               = array();
		$blocked                = array();
		$scanned                = 0;

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			++$scanned;
			$attachment_id = $this->absint_value( $item['attachment_id'] ?? 0 );
			if ( 0 >= $attachment_id ) {
				$blocked[] = array(
					'attachment_id'        => 0,
					'status'               => 'blocked',
					'blocked_reason'       => 'missing_attachment_id',
					'operator_next_action' => 'skip_or_adjust_media_snapshot',
				);
				continue;
			}

			$item_evidence = $image_context_by_id[ $attachment_id ] ?? array();
			if ( ! empty( $item_evidence ) ) {
				$item = $this->media_alt_caption_apply_image_context_evidence( $item, $item_evidence );
			}

			$item_status = $this->media_alt_caption_item_status( $item );
			if ( empty( $item_status['review_reasons'] ) ) {
				$blocked[] = array(
					'attachment_id'          => $attachment_id,
					'status'                 => 'blocked',
					'blocked_reason'         => 'metadata_complete_for_p0',
					'current_alt_status'     => $item_status['current_alt_status'],
					'current_caption_status' => $item_status['current_caption_status'],
					'operator_next_action'   => 'skip_or_adjust_filters',
				);
				continue;
			}

			$candidate_quality = $this->media_alt_caption_candidate_quality( $item, $item_status );
			if ( empty( $candidate_quality['alt_candidates'] ) && '' === (string) ( $candidate_quality['caption_candidate'] ?? '' ) ) {
				$blocked[] = array(
					'attachment_id'              => $attachment_id,
					'status'                     => 'blocked',
					'blocked_reason'             => 'candidate_quality_insufficient',
					'current_alt_status'         => $item_status['current_alt_status'],
					'current_caption_status'     => $item_status['current_caption_status'],
					'review_reasons'             => $item_status['review_reasons'],
					'title'                      => sanitize_text_field( (string) ( $item['title'] ?? '' ) ),
					'filename'                   => $this->sanitize_file_name_value( (string) ( $item['filename'] ?? '' ) ),
					'thumbnail_url'              => $this->esc_url_value( (string) ( $item['thumbnail_url'] ?? '' ) ),
					'url'                        => $this->esc_url_value( (string) ( $item['url'] ?? '' ) ),
					'mime_type'                  => sanitize_text_field( (string) ( $item['mime_type'] ?? '' ) ),
					'candidate_quality_flags'    => $candidate_quality['candidate_quality_flags'],
					'filtered_candidate_notes'   => $candidate_quality['filtered_candidate_notes'],
					'candidate_fact_types'       => $candidate_quality['candidate_fact_types'],
					'candidate_confidence'       => $candidate_quality['candidate_confidence'],
					'candidate_review_status'    => $candidate_quality['candidate_review_status'],
					'needs_context_confirmation' => $candidate_quality['needs_context_confirmation'],
					'candidate_quality'          => $candidate_quality['candidate_quality'],
					'candidate_quality_score'    => $candidate_quality['candidate_quality_score'],
					'candidate_quality_tier'     => $candidate_quality['candidate_quality_tier'],
					'automation_recommendation'  => $candidate_quality['automation_recommendation'],
					'visual_evidence_required'   => $candidate_quality['visual_evidence_required'],
					'operator_next_action'       => 'request_ai_vision_evidence_or_skip',
				);
				continue;
			}

			if ( count( $selected ) >= $max_items ) {
				$blocked[] = array(
					'attachment_id'          => $attachment_id,
					'status'                 => 'blocked',
					'blocked_reason'         => 'selection_limit_reached',
					'current_alt_status'     => $item_status['current_alt_status'],
					'current_caption_status' => $item_status['current_caption_status'],
					'operator_next_action'   => 'review_current_selection_then_rebuild',
				);
				continue;
			}

			$selected[] = array_merge(
				array(
					'id'                         => 'media-alt-caption:' . $attachment_id,
					'attachment_id'              => $attachment_id,
					'object_type'                => 'attachment',
					'status'                     => 'selected',
					'result_ref'                 => 'attachment:' . $attachment_id,
					'title'                      => sanitize_text_field( (string) ( $item['title'] ?? '' ) ),
					'filename'                   => $this->sanitize_file_name_value( (string) ( $item['filename'] ?? '' ) ),
					'thumbnail_url'              => $this->esc_url_value( (string) ( $item['thumbnail_url'] ?? '' ) ),
					'url'                        => $this->esc_url_value( (string) ( $item['url'] ?? '' ) ),
					'current_alt'                => sanitize_text_field( (string) ( $item['alt'] ?? '' ) ),
					'current_caption'            => $this->sanitize_metadata_text( (string) ( $item['caption'] ?? '' ) ),
					'alt_candidates'             => $candidate_quality['alt_candidates'],
					'caption_candidate'          => $candidate_quality['caption_candidate'],
					'candidate_basis'            => $candidate_quality['candidate_basis'],
					'candidate_quality_flags'    => $candidate_quality['candidate_quality_flags'],
					'filtered_candidate_notes'   => $candidate_quality['filtered_candidate_notes'],
					'candidate_fact_types'       => $candidate_quality['candidate_fact_types'],
					'candidate_confidence'       => $candidate_quality['candidate_confidence'],
					'candidate_review_status'    => $candidate_quality['candidate_review_status'],
					'needs_context_confirmation' => $candidate_quality['needs_context_confirmation'],
					'candidate_quality'          => $candidate_quality['candidate_quality'],
					'candidate_quality_score'    => $candidate_quality['candidate_quality_score'],
					'candidate_quality_tier'     => $candidate_quality['candidate_quality_tier'],
					'automation_recommendation'  => $candidate_quality['automation_recommendation'],
					'visual_evidence_required'   => $candidate_quality['visual_evidence_required'],
					'image_context_evidence'     => ! empty( $item_evidence ) ? $this->media_alt_caption_public_image_context_evidence( $item_evidence ) : array(),
					'needs_human_visual_check'   => true,
					'target_write_path'          => 'core_proposal_required',
					'direct_wordpress_write'     => false,
					'operator_next_action'       => $candidate_quality['operator_next_action'],
				),
				$item_status
			);
		}

		$quality_summary = $this->media_alt_caption_review_quality_summary( $selected, $blocked );
		$data = array(
			'contract_version'       => 'media_alt_caption_review_set.v1',
			'artifact_type'          => 'media_alt_caption_review_set',
			'mode'                   => 'governed_review_set',
			'runtime_owner'          => 'npcink-abilities-toolkit',
			'write_posture'          => 'suggestion_only',
			'final_write_path'       => 'core_proposal_required',
			'direct_wordpress_write' => false,
			'proposal_created'       => false,
			'execution_created'      => false,
			'source_ability_id'      => 'npcink-abilities-toolkit/build-media-alt-caption-review-set',
			'source_policy'          => $source_policy,
			'media_scope'            => $media_scope,
			'post_context'           => $post_context,
			'eligibility_summary'    => array(
				'scanned_count'  => $scanned,
				'eligible_count' => count( $selected ) + count(
					array_filter(
						$blocked,
						static function ( array $item ) {
							return 'selection_limit_reached' === ( $item['blocked_reason'] ?? '' );
						}
					)
				),
				'selected_count' => count( $selected ),
				'blocked_count'  => count( $blocked ),
				'max_items'      => $max_items,
			) + $quality_summary,
			'selected_items'         => $selected,
			'blocked_items'          => $blocked,
			'image_context_evidence_request' => $this->media_alt_caption_image_context_evidence_request( $blocked, $max_items ),
			'operator_next_action'   => 'review_selected_alt_caption_suggestions',
			'retryable'              => true,
			'retry_guidance'         => array(
				'retryable'            => true,
				'reason'               => 'review_set_can_be_rebuilt',
				'operator_next_action' => 'adjust_focus_or_media_filters_then_rebuild',
			),
			'safety'                 => array(
				'local_queue_created'          => false,
				'core_proposal_created'        => false,
				'direct_wordpress_write'       => false,
				'media_derivative_run_created' => false,
				'requires_human_visual_check'  => true,
			),
			'handoff'                => array(
				'current_stage'                => 'review_only',
				'accepted_selection_target'    => 'npcink-abilities-toolkit/build-media-alt-apply-plan',
				'future_apply_path'            => 'Build the ALT-only apply plan, then submit it to Core after the host verifies contract compatibility.',
				'blocked_direct_apply_reason'  => 'Toolkit review artifacts do not authorize writes; the ALT-only plan and final media update still require Core governance.',
				'direct_wordpress_write'       => false,
			),
		);

		return $this->build_analysis_success_response(
			$data,
			array(
				'source'         => 'local_media_alt_caption_review_set',
				'execution_mode' => 'deterministic',
				'plan_only'      => true,
				'readonly'       => true,
			),
			'Media ALT/caption review set built.'
		);
	}

	/**
	 * Builds one governed missing-ALT apply plan from reviewed attachment evidence.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function build_media_alt_apply_plan( $input ) {
		$input         = is_array( $input ) ? $input : array();
		$attachment_id = absint( $input['attachment_id'] ?? 0 );
		$attachment    = $attachment_id > 0 ? get_post( $attachment_id ) : null;
		if ( ! is_object( $attachment ) || 'attachment' !== (string) ( $attachment->post_type ?? '' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_alt_attachment_invalid', __( 'A valid media attachment is required.', 'npcink-abilities-toolkit' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to review this attachment.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$mime_type = sanitize_text_field( (string) get_post_mime_type( $attachment_id ) );
		if ( 0 !== strpos( $mime_type, 'image/' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_alt_image_required', __( 'The guarded ALT plan is limited to image attachments.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$expected_current_alt = (string) ( $input['expected_current_alt'] ?? '' );
		$current_alt          = (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		if ( '' !== $expected_current_alt ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_alt_missing_only', __( 'The first guarded ALT plan only fills attachments whose current ALT is empty.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
		}
		if ( $expected_current_alt !== $current_alt ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_alt_stale', __( 'The attachment ALT changed after review. Rebuild the review set.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
		}
		if ( true !== ( $input['operator_visual_review_confirmed'] ?? false ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_alt_visual_confirmation_required', __( 'Confirm that the operator reviewed the image before building the apply plan.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( 'media_alt_caption_review_set.v1' !== (string) ( $input['review_set_contract'] ?? '' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_alt_review_contract_invalid', __( 'The ALT apply plan requires the media ALT review-set v1 contract.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$alt = sanitize_text_field( (string) ( $input['alt'] ?? '' ) );
		if ( strlen( $alt ) < 3 || strlen( $alt ) > 160 || preg_match( '/https?:\/\/|generated\s+by|prompt\s*:|model\s*:|provider\s*:|profile\s*:/i', $alt ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_alt_quality_invalid', __( 'Provide a concise reviewed ALT without URLs, prompts, model names, or provider metadata.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$source_item_id = sanitize_text_field( (string) ( $input['source_item_id'] ?? ( 'media-alt-caption:' . $attachment_id ) ) );
		$evidence_refs  = array_values(
			array_filter(
				array_map(
					'sanitize_text_field',
					array_slice( is_array( $input['evidence_refs'] ?? null ) ? $input['evidence_refs'] : array(), 0, 20 )
				)
			)
		);
		$idempotency_key = 'media-alt-missing-' . $attachment_id . '-' . substr( md5( $expected_current_alt . '|' . $alt ), 0, 16 );
		$action = $this->build_plan_action(
			'update_media_alt_' . $attachment_id,
			'npcink-abilities-toolkit/update-media-details',
			array(
				'attachment_id'                     => $attachment_id,
				'alt'                               => $alt,
				'expected_current_alt'              => $expected_current_alt,
				'operator_visual_review_confirmed' => true,
				'idempotency_key'                   => $idempotency_key,
			),
			array( 'media.write' ),
			'medium',
			'Apply one operator-reviewed ALT value only while the attachment ALT remains empty.'
		);
		$action['preview'] = array(
			'artifact_type'                    => 'media_alt_apply_plan_item',
			'contract_version'                 => 'media_alt_apply_plan.v1',
			'review_set_contract'              => 'media_alt_caption_review_set.v1',
			'source_item_id'                   => $source_item_id,
			'attachment_id'                    => $attachment_id,
			'mime_type'                        => $mime_type,
			'current_alt_status'               => 'missing',
			'expected_current_alt'             => $expected_current_alt,
			'proposed_alt'                     => $alt,
			'operator_reviewed'                => true,
			'operator_visual_review_confirmed' => true,
			'evidence_refs'                    => $evidence_refs,
		);

		$data = array(
			'artifact_type'          => 'media_alt_apply_plan',
			'contract_version'       => 'media_alt_apply_plan.v1',
			'source_ability_id'      => 'npcink-abilities-toolkit/build-media-alt-apply-plan',
			'source_review_contract' => 'media_alt_caption_review_set.v1',
			'proposal_mode'          => 'single',
			'requires_approval'      => true,
			'dry_run'                => true,
			'commit_execution'       => false,
			'direct_wordpress_write' => false,
			'authorization'          => array(
				'classification' => 'core_proposal_required',
				'authority'      => 'npcink-governance-core',
			),
			'write_actions'          => array( $action ),
			'handoff'                => array(
				'plan_ability_id'       => 'npcink-abilities-toolkit/build-media-alt-apply-plan',
				'target_ability_id'     => 'npcink-abilities-toolkit/update-media-details',
				'operator_next_action'  => 'submit_plan_to_core_review',
				'approval_mode_owner'   => 'npcink-governance-core',
				'direct_wordpress_write' => false,
			),
		);

		return $this->build_analysis_success_response(
			$data,
			array(
				'source'         => 'reviewed_media_alt_apply_plan',
				'execution_mode' => 'deterministic',
				'plan_only'      => true,
				'readonly'       => true,
			),
			'Media ALT apply plan built.'
		);
	}

	/**
	 * Resolves the source policy for media ALT/caption review sets.
	 *
	 * @param array<string,mixed> $media_snapshot Media snapshot.
	 * @return string
	 */
	private function media_alt_caption_review_source_policy( array $media_snapshot ) {
		$snapshot_policy = sanitize_key( (string) ( $media_snapshot['snapshot_policy'] ?? '' ) );
		if ( 'current_article_media_metadata_only' === $snapshot_policy ) {
			return 'current_article_media_metadata_only_no_pixel_vision';
		}
		if ( 'operator_supplied_media_metadata_only' === $snapshot_policy ) {
			return 'operator_supplied_media_metadata_only_no_pixel_vision';
		}

		return 'media_library_metadata_only_no_pixel_vision';
	}

	/**
	 * Indexes reviewed visual context evidence by attachment id.
	 *
	 * @param array<string,mixed> $image_context_evidence Evidence payload.
	 * @return array<int,array<string,mixed>>
	 */
	private function media_alt_caption_index_image_context_evidence( array $image_context_evidence ) {
		if (
			'image_context_evidence.v1' !== (string) ( $image_context_evidence['contract_version'] ?? '' )
			|| 'suggestion_only' !== (string) ( $image_context_evidence['write_posture'] ?? '' )
			|| false !== (bool) ( $image_context_evidence['direct_wordpress_write'] ?? true )
		) {
			return array();
		}

		$items   = is_array( $image_context_evidence['items'] ?? null ) ? $image_context_evidence['items'] : array();
		$indexed = array();
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$attachment_id = $this->absint_value( $item['attachment_id'] ?? 0 );
			if ( 0 >= $attachment_id ) {
				continue;
			}
			$item['contract_version']         = 'image_context_evidence.v1';
			$item['source']                   = sanitize_key( (string) ( $item['source'] ?? 'cloud_or_host_runtime' ) );
			$item['write_posture']            = 'suggestion_only';
			$item['direct_wordpress_write']   = false;
			$item['needs_human_visual_check'] = true;
			$indexed[ $attachment_id ]        = $this->media_alt_caption_sanitize_payload( $item );
		}

		return $indexed;
	}

	/**
	 * Applies reviewed visual context evidence to one media row.
	 *
	 * @param array<string,mixed> $item Media row.
	 * @param array<string,mixed> $evidence Evidence row.
	 * @return array<string,mixed>
	 */
	private function media_alt_caption_apply_image_context_evidence( array $item, array $evidence ) {
		$summary = $this->media_alt_caption_clean_candidate( (string) ( $evidence['visual_summary'] ?? ( $evidence['alt_text_basis'] ?? '' ) ) );
		$scene   = $this->media_alt_caption_clean_candidate( (string) ( $evidence['scene'] ?? '' ) );
		$objects = $this->media_alt_caption_sanitize_string_list( $evidence['objects'] ?? ( $evidence['subject_tags'] ?? array() ) );
		$text    = $this->media_alt_caption_sanitize_string_list( $evidence['text_seen'] ?? ( $evidence['visible_text'] ?? array() ) );

		if ( '' !== $summary ) {
			$item['image_context_visual_summary'] = $summary;
		}
		if ( '' !== $scene ) {
			$item['image_context_scene'] = $scene;
		}
		if ( ! empty( $objects ) ) {
			$item['image_context_objects_summary'] = implode( ', ', array_slice( $objects, 0, 8 ) );
		}
		if ( ! empty( $text ) ) {
			$item['image_context_text_seen'] = implode( ', ', array_slice( $text, 0, 5 ) );
		}

		return $item;
	}

	/**
	 * Returns the public evidence subset for operator review.
	 *
	 * @param array<string,mixed> $evidence Evidence row.
	 * @return array<string,mixed>
	 */
	private function media_alt_caption_public_image_context_evidence( array $evidence ) {
		return array(
			'contract_version'         => 'image_context_evidence.v1',
			'source'                   => sanitize_key( (string) ( $evidence['source'] ?? 'cloud_or_host_runtime' ) ),
			'visual_summary'           => $this->media_alt_caption_trim_chars( $this->media_alt_caption_clean_candidate( (string) ( $evidence['visual_summary'] ?? ( $evidence['alt_text_basis'] ?? '' ) ) ), 180 ),
			'scene'                    => $this->media_alt_caption_trim_chars( $this->media_alt_caption_clean_candidate( (string) ( $evidence['scene'] ?? '' ) ), 120 ),
			'objects'                  => array_slice( $this->media_alt_caption_sanitize_string_list( $evidence['objects'] ?? ( $evidence['subject_tags'] ?? array() ) ), 0, 8 ),
			'text_seen'                => array_slice( $this->media_alt_caption_sanitize_string_list( $evidence['text_seen'] ?? ( $evidence['visible_text'] ?? array() ) ), 0, 5 ),
			'confidence'               => sanitize_text_field( (string) ( $evidence['confidence'] ?? '' ) ),
			'write_posture'            => 'suggestion_only',
			'direct_wordpress_write'   => false,
			'needs_human_visual_check' => true,
		);
	}

	/**
	 * Builds an optional Cloud/host evidence request for insufficient metadata rows.
	 *
	 * @param array<int,array<string,mixed>> $blocked Blocked rows.
	 * @param int                           $max_items Max request rows.
	 * @return array<string,mixed>
	 */
	private function media_alt_caption_image_context_evidence_request( array $blocked, $max_items ) {
		$items = array();
		foreach ( $blocked as $item ) {
			if ( ! is_array( $item ) || 'candidate_quality_insufficient' !== (string) ( $item['blocked_reason'] ?? '' ) ) {
				continue;
			}
			$attachment_id = $this->absint_value( $item['attachment_id'] ?? 0 );
			if ( 0 >= $attachment_id ) {
				continue;
			}
			$items[] = array(
				'attachment_id'            => $attachment_id,
				'title'                    => sanitize_text_field( (string) ( $item['title'] ?? '' ) ),
				'filename'                 => $this->sanitize_file_name_value( (string) ( $item['filename'] ?? '' ) ),
				'thumbnail_url'            => $this->esc_url_value( (string) ( $item['thumbnail_url'] ?? '' ) ),
				'url'                      => $this->esc_url_value( (string) ( $item['url'] ?? '' ) ),
				'mime_type'                => sanitize_text_field( (string) ( $item['mime_type'] ?? '' ) ),
				'current_alt_status'       => sanitize_key( (string) ( $item['current_alt_status'] ?? '' ) ),
				'current_caption_status'   => sanitize_key( (string) ( $item['current_caption_status'] ?? '' ) ),
				'candidate_quality_flags'  => $this->media_alt_caption_sanitize_string_list( $item['candidate_quality_flags'] ?? array() ),
				'filtered_candidate_notes' => $this->media_alt_caption_sanitize_string_list( $item['filtered_candidate_notes'] ?? array() ),
			);
			if ( count( $items ) >= min( 10, max( 1, (int) $max_items ) ) ) {
				break;
			}
		}

		if ( empty( $items ) ) {
			return array();
		}

		return array(
			'contract_version'           => 'image_context_evidence_request.v1',
			'artifact_type'              => 'image_context_evidence_request',
			'runtime_owner'              => 'cloud_or_host_runtime',
			'write_posture'              => 'suggestion_only',
			'direct_wordpress_write'     => false,
			'proposal_created'           => false,
			'execution_created'          => false,
			'no_local_model'             => true,
			'no_media_write'             => true,
			'source_policy'              => 'bounded_media_urls_for_visual_context_only',
			'expected_response_contract' => 'image_context_evidence.v1',
			'requested_count'            => count( $items ),
			'max_items'                  => min( 10, max( 1, (int) $max_items ) ),
			'items'                      => $items,
			'operator_next_action'       => 'request_cloud_image_context_evidence',
		);
	}

	/**
	 * Computes current ALT/caption status for one media row.
	 *
	 * @param array<string,mixed> $item Media row.
	 * @return array<string,mixed>
	 */
	private function media_alt_caption_item_status( array $item ) {
		$alt     = trim( sanitize_text_field( (string) ( $item['alt'] ?? '' ) ) );
		$caption = trim( $this->sanitize_metadata_text( (string) ( $item['caption'] ?? '' ) ) );
		$title   = trim( sanitize_text_field( (string) ( $item['title'] ?? '' ) ) );

		$current_alt_status = 'present';
		$review_reasons     = array();
		if ( '' === $alt ) {
			$current_alt_status = 'missing';
			$review_reasons[]   = 'missing_alt';
		} elseif ( $this->media_alt_caption_candidate_is_too_short( $alt ) || $this->media_alt_caption_is_filename_like( $alt, $item ) ) {
			$current_alt_status = 'weak';
			$review_reasons[]   = 'weak_alt';
		}

		$current_caption_status = '' === $caption ? 'missing' : 'present';
		if ( '' === $caption ) {
			$review_reasons[] = 'missing_caption';
		}
		if ( '' !== $title && $this->media_alt_caption_is_filename_like( $title, $item ) ) {
			$review_reasons[] = 'filename_like_title';
		}

		return array(
			'current_alt_status'     => $current_alt_status,
			'current_caption_status' => $current_caption_status,
			'review_reasons'         => array_values( array_unique( $review_reasons ) ),
		);
	}

	/**
	 * Builds candidate quality metadata for one media row.
	 *
	 * @param array<string,mixed> $item Media row.
	 * @param array<string,mixed> $item_status Status row.
	 * @return array<string,mixed>
	 */
	private function media_alt_caption_candidate_quality( array $item, array $item_status ) {
		$flags                      = array();
		$notes                      = array();
		$basis                      = array();
		$fact_types                 = array();
		$alt_candidates             = array();
		$caption_candidate          = '';
		$candidate_confidence       = 'low';
		$needs_context_confirmation = false;

		if ( 'present' !== (string) ( $item_status['current_alt_status'] ?? '' ) ) {
			foreach ( array( 'image_context_visual_summary', 'image_context_scene', 'image_context_objects_summary', 'description', 'caption', 'title', 'filename' ) as $field ) {
				$value     = 'filename' === $field ? $this->media_alt_caption_filename_descriptor( (string) ( $item['filename'] ?? '' ) ) : (string) ( $item[ $field ] ?? '' );
				$candidate = $this->media_alt_caption_clean_candidate( $value );
				if ( '' === $candidate ) {
					continue;
				}
				$rejection = $this->media_alt_caption_candidate_rejection_reason( $candidate, $item, 'alt' );
				if ( '' !== $rejection ) {
					$flags[] = $rejection;
					$notes[] = 'filtered_alt_' . $field . ':' . $rejection;
					continue;
				}
				$context_profile            = $this->media_alt_caption_candidate_context_profile( $candidate, $item, $field );
				$flags                      = array_merge( $flags, $context_profile['candidate_quality_flags'] );
				$notes                      = array_merge( $notes, $context_profile['filtered_candidate_notes'] );
				$fact_types                 = array_merge( $fact_types, $context_profile['candidate_fact_types'] );
				$candidate_confidence       = $this->media_alt_caption_merge_candidate_confidence( $candidate_confidence, (string) $context_profile['candidate_confidence'] );
				$needs_context_confirmation = $needs_context_confirmation || (bool) $context_profile['needs_context_confirmation'];
				$alt_candidates[]           = $this->media_alt_caption_trim_chars( $candidate, 140 );
				$basis[]                    = 'alt:' . $field;
			}
		}

		if ( 'missing' === (string) ( $item_status['current_caption_status'] ?? '' ) ) {
			foreach ( array( 'image_context_visual_summary', 'image_context_scene', 'description', 'alt', 'title' ) as $field ) {
				$candidate = $this->media_alt_caption_clean_candidate( (string) ( $item[ $field ] ?? '' ) );
				if ( '' === $candidate ) {
					continue;
				}
				$rejection = $this->media_alt_caption_candidate_rejection_reason( $candidate, $item, 'caption' );
				if ( '' !== $rejection ) {
					$flags[] = $rejection;
					$notes[] = 'filtered_caption_' . $field . ':' . $rejection;
					continue;
				}
				$context_profile            = $this->media_alt_caption_candidate_context_profile( $candidate, $item, $field );
				$flags                      = array_merge( $flags, $context_profile['candidate_quality_flags'] );
				$notes                      = array_merge( $notes, $context_profile['filtered_candidate_notes'] );
				$fact_types                 = array_merge( $fact_types, $context_profile['candidate_fact_types'] );
				$candidate_confidence       = $this->media_alt_caption_merge_candidate_confidence( $candidate_confidence, (string) $context_profile['candidate_confidence'] );
				$needs_context_confirmation = $needs_context_confirmation || (bool) $context_profile['needs_context_confirmation'];
				$caption_candidate          = $this->media_alt_caption_trim_chars( $this->media_alt_caption_sentence( $candidate ), 180 );
				$basis[]                    = 'caption:' . $field;
				break;
			}
		} else {
			$flags[] = 'caption_redundant';
			$notes[] = 'filtered_caption_existing:caption_redundant';
		}

		if ( empty( $alt_candidates ) && '' === $caption_candidate ) {
			$flags[] = 'metadata_insufficient';
		}

		$alt_candidates          = array_slice( array_values( array_unique( array_filter( $alt_candidates ) ) ), 0, 2 );
		$candidate_review_status = $needs_context_confirmation ? 'needs_context_confirmation' : 'ready_for_review';
		$operator_next_action    = $needs_context_confirmation ? 'confirm_context_terms_or_edit_alt' : 'visually_review_alt_caption';
		if ( empty( $alt_candidates ) && '' !== $caption_candidate ) {
			$candidate_review_status = 'caption_review_only';
			$operator_next_action    = 'review_caption_manually_or_skip_alt_handoff';
		}

		$assessment = $this->media_alt_caption_candidate_quality_assessment(
			$alt_candidates,
			$caption_candidate,
			$flags,
			$fact_types,
			$candidate_confidence,
			$candidate_review_status,
			$needs_context_confirmation
		);

		return array(
			'alt_candidates'             => $alt_candidates,
			'caption_candidate'          => $caption_candidate,
			'candidate_basis'            => array_values( array_unique( $basis ) ),
			'candidate_quality_flags'    => array_values( array_unique( array_filter( $flags ) ) ),
			'filtered_candidate_notes'   => array_values( array_unique( array_filter( $notes ) ) ),
			'candidate_fact_types'       => array_values( array_unique( array_filter( $fact_types ) ) ),
			'candidate_confidence'       => $needs_context_confirmation ? 'context_required' : $candidate_confidence,
			'candidate_review_status'    => $candidate_review_status,
			'needs_context_confirmation' => $needs_context_confirmation,
			'candidate_quality'          => $assessment,
			'candidate_quality_score'    => $assessment['score'],
			'candidate_quality_tier'     => $assessment['tier'],
			'automation_recommendation'  => $assessment['automation_recommendation'],
			'visual_evidence_required'   => $assessment['visual_evidence_required'],
			'operator_next_action'       => $operator_next_action,
		);
	}

	/**
	 * Builds machine-readable quality guidance for review automation.
	 *
	 * @param array<int,string> $alt_candidates ALT candidates.
	 * @param string            $caption_candidate Caption candidate.
	 * @param array<int,string> $flags Quality flags.
	 * @param array<int,string> $fact_types Fact source types.
	 * @param string            $confidence Candidate confidence.
	 * @param string            $candidate_review_status Review status.
	 * @param bool              $needs_context_confirmation Whether context needs confirmation.
	 * @return array<string,mixed>
	 */
	private function media_alt_caption_candidate_quality_assessment( array $alt_candidates, $caption_candidate, array $flags, array $fact_types, $confidence, $candidate_review_status, $needs_context_confirmation ) {
		$has_alt     = ! empty( $alt_candidates );
		$has_caption = '' !== (string) $caption_candidate;
		$fact_types  = array_values( array_unique( array_filter( $fact_types ) ) );
		$flags       = array_values( array_unique( array_filter( $flags ) ) );

		$score                      = 60;
		$tier                       = 'review';
		$basis_summary              = 'context_only';
		$visual_evidence_required   = false;
		$automation_recommendation  = 'visually_review_alt_caption';

		if ( ! $has_alt && ! $has_caption ) {
			$score                     = 0;
			$tier                      = 'insufficient';
			$basis_summary             = 'insufficient_metadata';
			$visual_evidence_required  = true;
			$automation_recommendation = 'request_visual_evidence_or_skip';
		} elseif ( 'caption_review_only' === (string) $candidate_review_status ) {
			$score                     = 35;
			$tier                      = 'caption_only';
			$basis_summary             = 'caption_only';
			$automation_recommendation = 'review_caption_manually_or_skip_alt_handoff';
		} elseif ( $needs_context_confirmation ) {
			$score                     = 50;
			$tier                      = 'context_required';
			$basis_summary             = 'context_requires_confirmation';
			$automation_recommendation = 'confirm_context_terms_or_edit_alt';
			} elseif ( in_array( 'visual_fact', $fact_types, true ) && $has_alt ) {
				$score                     = 90;
				$tier                      = 'ready';
				$basis_summary             = 'visual_evidence';
				$automation_recommendation = 'eligible_for_local_preview_after_visual_check';
			} elseif ( in_array( 'metadata_fact', $fact_types, true ) && $has_alt ) {
				$score                     = 75;
				$tier                      = 'ready';
				$basis_summary             = 'metadata_evidence';
				$automation_recommendation = 'eligible_for_local_preview_after_visual_check';
		} elseif ( $has_alt ) {
			$score                     = 55;
			$tier                      = 'review';
			$basis_summary             = 'context_only';
			$automation_recommendation = 'visually_review_or_request_visual_evidence';
		}

		return array(
			'score'                     => $score,
			'tier'                      => $tier,
			'basis_summary'             => $basis_summary,
			'primary_alt_candidate'     => $has_alt ? (string) $alt_candidates[0] : '',
			'automation_recommendation' => $automation_recommendation,
			'visual_evidence_required'  => $visual_evidence_required,
			'confidence'                => $needs_context_confirmation ? 'context_required' : sanitize_key( (string) $confidence ),
			'fact_types'                => $fact_types,
			'flags'                     => $flags,
		);
	}

	/**
	 * Summarizes review quality buckets for UI and eval tooling.
	 *
	 * @param array<int,array<string,mixed>> $selected Selected rows.
	 * @param array<int,array<string,mixed>> $blocked Blocked rows.
	 * @return array<string,int>
	 */
	private function media_alt_caption_review_quality_summary( array $selected, array $blocked ) {
			$summary = array(
				'local_preview_candidate_count' => 0,
				'context_confirmation_count'    => 0,
			'caption_review_only_count'     => 0,
			'visual_evidence_request_count' => 0,
			'insufficient_quality_count'    => 0,
		);

		foreach ( $selected as $item ) {
			$quality = is_array( $item['candidate_quality'] ?? null ) ? $item['candidate_quality'] : array();
			$tier    = sanitize_key( (string) ( $quality['tier'] ?? ( $item['candidate_quality_tier'] ?? '' ) ) );
				if ( 'ready' === $tier ) {
					++$summary['local_preview_candidate_count'];
			} elseif ( 'context_required' === $tier ) {
				++$summary['context_confirmation_count'];
			} elseif ( 'caption_only' === $tier ) {
				++$summary['caption_review_only_count'];
			}
		}

		foreach ( $blocked as $item ) {
			if ( 'candidate_quality_insufficient' !== (string) ( $item['blocked_reason'] ?? '' ) ) {
				continue;
			}
			++$summary['insufficient_quality_count'];
			$quality = is_array( $item['candidate_quality'] ?? null ) ? $item['candidate_quality'] : array();
			if ( true === (bool) ( $quality['visual_evidence_required'] ?? ( $item['visual_evidence_required'] ?? false ) ) ) {
				++$summary['visual_evidence_request_count'];
			}
		}

		return $summary;
	}

	/**
	 * Returns a rejection reason for one candidate.
	 *
	 * @param string              $candidate Candidate text.
	 * @param array<string,mixed> $item Media row.
	 * @param string              $target_field Target metadata field.
	 * @return string
	 */
	private function media_alt_caption_candidate_rejection_reason( $candidate, array $item, $target_field ) {
		$candidate = $this->media_alt_caption_clean_candidate( (string) $candidate );
		if ( '' === $candidate ) {
			return 'metadata_insufficient';
		}
		if ( $this->media_alt_caption_is_runtime_provenance_text( $candidate ) ) {
			return 'runtime_provenance';
		}
		if ( $this->media_alt_caption_is_url_or_source_text( $candidate ) ) {
			return 'source_attribution_or_url';
		}
		if ( $this->media_alt_caption_is_camera_default( $candidate ) ) {
			return 'camera_default';
		}
		if ( $this->media_alt_caption_is_filename_like( $candidate, $item ) ) {
			return 'filename_like';
		}
		if ( $this->media_alt_caption_is_duplicate_metadata( $candidate, $item, (string) $target_field ) ) {
			return 'caption' === $target_field ? 'caption_redundant' : 'metadata_duplicate';
		}
		if ( $this->media_alt_caption_is_too_generic_candidate( $candidate ) ) {
			return 'too_generic';
		}
		if ( $this->media_alt_caption_has_metadata_conflict( $candidate, $item ) ) {
			return 'metadata_conflict';
		}
		if ( $this->media_alt_caption_candidate_is_too_short( $candidate ) ) {
			return 'too_generic';
		}

		return '';
	}

	/**
	 * Classifies candidate evidence basis.
	 *
	 * @param string              $candidate Candidate text.
	 * @param array<string,mixed> $item Media row.
	 * @param string              $source_field Source field.
	 * @return array<string,mixed>
	 */
	private function media_alt_caption_candidate_context_profile( $candidate, array $item, $source_field ) {
		$source_field               = sanitize_key( (string) $source_field );
		$is_visual_evidence         = 0 === strpos( $source_field, 'image_context_' );
		$fact_types                 = array();
		$flags                      = array();
		$notes                      = array();
		$confidence                 = 'low';
		$needs_context_confirmation = false;

		if ( $is_visual_evidence ) {
			$fact_types[] = 'visual_fact';
			$confidence   = 'high';
		} elseif ( in_array( $source_field, array( 'alt', 'caption', 'description' ), true ) ) {
			$fact_types[] = 'metadata_fact';
			$confidence   = 'medium';
		} else {
			$fact_types[] = 'context_only';
			$confidence   = 'low';
		}

		if ( ! $is_visual_evidence && $this->media_alt_caption_candidate_needs_context_confirmation( (string) $candidate ) ) {
			$needs_context_confirmation = true;
			$fact_types[]               = 'context_only';
			$flags[]                    = 'needs_context_confirmation';
			$notes[]                    = 'context_' . $source_field . ':needs_context_confirmation';
		}

		return array(
			'candidate_fact_types'       => array_values( array_unique( $fact_types ) ),
			'candidate_quality_flags'    => $flags,
			'filtered_candidate_notes'   => $notes,
			'candidate_confidence'       => $confidence,
			'needs_context_confirmation' => $needs_context_confirmation,
		);
	}

	/**
	 * Merges confidence labels.
	 *
	 * @param string $current Current confidence.
	 * @param string $next Next confidence.
	 * @return string
	 */
	private function media_alt_caption_merge_candidate_confidence( $current, $next ) {
		$ranks        = array( 'low' => 1, 'medium' => 2, 'high' => 3 );
		$current_rank = $ranks[ $current ] ?? 0;
		$next_rank    = $ranks[ $next ] ?? 0;

		return $next_rank > $current_rank ? $next : $current;
	}

	/**
	 * Detects location or proper-name claims that need operator confirmation.
	 *
	 * @param string $candidate Candidate text.
	 * @return bool
	 */
	private function media_alt_caption_candidate_needs_context_confirmation( $candidate ) {
		$candidate = $this->media_alt_caption_clean_candidate( (string) $candidate );
		if ( '' === $candidate ) {
			return false;
		}
		if ( preg_match( '/\b(in|near|at|outside of|from)\s+[A-Z][A-Za-z]+(?:\s+[A-Z][A-Za-z]+){0,3}\b/', $candidate ) ) {
			return true;
		}
		if ( preg_match( '/,\s*[A-Z][A-Za-z]+(?:\s+[A-Z][A-Za-z]+)?\b/', $candidate ) ) {
			return true;
		}

		preg_match_all( '/\b[A-Z][a-z]{2,}\b/', $candidate, $matches );
		$terms = array();
		foreach ( (array) ( $matches[0] ?? array() ) as $term ) {
			$normalized = strtolower( (string) $term );
			if ( in_array( $normalized, array( 'abstract', 'approval', 'beach', 'big', 'image', 'rocky', 'rocks', 'sea', 'visual', 'windmill', 'wordpress' ), true ) ) {
				continue;
			}
			$terms[] = $normalized;
		}

		return count( array_unique( $terms ) ) >= 2;
	}

	/**
	 * Cleans one ALT/caption candidate.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	private function media_alt_caption_clean_candidate( $value ) {
		$value = trim( $this->sanitize_metadata_text( wp_strip_all_tags( (string) $value ) ) );
		$value = preg_replace( '/\s+/u', ' ', $value ) ?? $value;

		return trim( $value );
	}

	/**
	 * Normalizes candidate text for comparisons.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	private function media_alt_caption_normalized_candidate( $value ) {
		$value = strtolower( $this->media_alt_caption_clean_candidate( (string) $value ) );
		$value = preg_replace( '/https?:\/\/\S+/i', '', $value ) ?? $value;
		$value = preg_replace( '/\.[a-z0-9]{2,5}\b/i', '', $value ) ?? $value;
		$value = preg_replace( '/[^a-z0-9\x{4e00}-\x{9fff}]+/u', ' ', $value ) ?? $value;
		$value = preg_replace( '/\s+/u', ' ', $value ) ?? $value;

		return trim( $value );
	}

	/**
	 * Detects duplicate metadata candidates.
	 *
	 * @param string              $candidate Candidate text.
	 * @param array<string,mixed> $item Media row.
	 * @param string              $target_field Target field.
	 * @return bool
	 */
	private function media_alt_caption_is_duplicate_metadata( $candidate, array $item, $target_field ) {
		$normalized = $this->media_alt_caption_normalized_candidate( (string) $candidate );
		if ( '' === $normalized ) {
			return false;
		}

		$fields = 'caption' === $target_field ? array( 'title', 'alt', 'caption' ) : array( 'alt', 'title' );
		foreach ( $fields as $field ) {
			$source = $this->media_alt_caption_normalized_candidate( (string) ( $item[ $field ] ?? '' ) );
			if ( '' !== $source && $normalized === $source ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Detects URL/source attribution text.
	 *
	 * @param string $value Value.
	 * @return bool
	 */
	private function media_alt_caption_is_url_or_source_text( $value ) {
		$value = trim( (string) $value );
		if ( preg_match( '/https?:\/\/|www\.|^\S+\.(com|net|org|cn|io|ai)(\/|$)/i', $value ) ) {
			return true;
		}

		return (bool) preg_match( '/\b(source|credit|credits|photo by|photograph by|image source|via|unsplash|pexels|pixabay|getty|shutterstock|istock)\b/i', $value );
	}

	/**
	 * Detects model/provider provenance text.
	 *
	 * @param string $value Value.
	 * @return bool
	 */
	private function media_alt_caption_is_runtime_provenance_text( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return false;
		}

		$patterns = array(
			'/\b(generated|created|produced|made)\s+(by|with|using)\b/i',
			'/\b(prompt|model|provider|profile|seed|negative prompt)\s*:/i',
			'/\b(npcink cloud|cloud scene image|gpt|dall[- ]?e|midjourney|stable diffusion|flux|grok|imagen)\b.*\b(prompt|generated|created|using|model)\b/i',
			'/\busing\s+[A-Z][A-Za-z0-9 ._-]{2,80}\s+on\s+\d{4}[\/-]\d{1,2}/',
			'/^(由|通过|使用).{0,40}(生成|创建|模型|提示词)/u',
			'/(提示词|模型|由.*生成|由.*创建)\s*[:：]/u',
		);
		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $value ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Detects camera/file default labels.
	 *
	 * @param string $value Value.
	 * @return bool
	 */
	private function media_alt_caption_is_camera_default( $value ) {
		return (bool) preg_match( '/^(olympus digital camera|canon digital camera|nikon digital camera|dscn?\d+|img[_ -]?\d+|p\d{7}|sam_\d+|image[_ -]?\d+)$/i', trim( (string) $value ) );
	}

	/**
	 * Detects candidates too short for safe ALT use.
	 *
	 * @param string $value Value.
	 * @return bool
	 */
	private function media_alt_caption_candidate_is_too_short( $value ) {
		$normalized = $this->media_alt_caption_normalized_candidate( (string) $value );
		if ( '' === $normalized ) {
			return true;
		}
		if ( preg_match( '/\p{Han}/u', $normalized ) ) {
			return $this->media_alt_caption_text_length( $normalized ) < 4;
		}

		return $this->media_alt_caption_text_length( $normalized ) < 18;
	}

	/**
	 * Detects generic placeholder candidates.
	 *
	 * @param string $value Value.
	 * @return bool
	 */
	private function media_alt_caption_is_too_generic_candidate( $value ) {
		$normalized = $this->media_alt_caption_normalized_candidate( (string) $value );
		if ( '' === $normalized ) {
			return true;
		}
		if ( $this->media_alt_caption_candidate_is_too_short( $normalized ) ) {
			return true;
		}

		$generic_patterns = array(
			'/^(featured image|horizontal featured image|vertical featured image|hero image|image|photo|screenshot|visual|wallpaper)$/i',
			'/^(add|write|review|provide|create)\s+(concise\s+)?(alt|caption|description)/i',
			'/\b(add concise alt text|after visual review|needs visible context|image needs visible context)\b/i',
			'/\b(click here|read more|learn more|take this)\b/i',
			'/\blorem ipsum\b/i',
		);
		foreach ( $generic_patterns as $pattern ) {
			if ( preg_match( $pattern, (string) $value ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Detects simple metadata conflicts.
	 *
	 * @param string              $candidate Candidate text.
	 * @param array<string,mixed> $item Media row.
	 * @return bool
	 */
	private function media_alt_caption_has_metadata_conflict( $candidate, array $item ) {
		$candidate = $this->media_alt_caption_normalized_candidate( (string) $candidate );
		$evidence  = $this->media_alt_caption_normalized_candidate(
			implode(
				' ',
				array(
					(string) ( $item['title'] ?? '' ),
					(string) ( $item['alt'] ?? '' ),
					(string) ( $item['caption'] ?? '' ),
					(string) ( $item['description'] ?? '' ),
					$this->media_alt_caption_filename_descriptor( (string) ( $item['filename'] ?? '' ) ),
				)
			)
		);

		if ( '' === $candidate || '' === $evidence ) {
			return false;
		}

		foreach ( array( array( 'horizontal', 'vertical' ), array( 'portrait', 'landscape' ) ) as $pair ) {
			if ( false !== strpos( $candidate, $pair[0] ) && false !== strpos( $evidence, $pair[1] ) ) {
				return true;
			}
			if ( false !== strpos( $candidate, $pair[1] ) && false !== strpos( $evidence, $pair[0] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Adds sentence punctuation when needed.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private function media_alt_caption_sentence( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}
		if ( preg_match( '/[.!?。！？]$/u', $value ) ) {
			return $value;
		}

		return $value . '.';
	}

	/**
	 * Converts a filename into a weak descriptor.
	 *
	 * @param string $filename File name.
	 * @return string
	 */
	private function media_alt_caption_filename_descriptor( $filename ) {
		$value = preg_replace( '/\.[a-z0-9]{2,5}$/i', '', (string) $filename );
		$value = preg_replace( '/[-_]+/', ' ', is_string( $value ) ? $value : '' );
		$value = preg_replace( '/\b\d{2,5}x\d{2,5}\b/i', '', is_string( $value ) ? $value : '' );
		$value = preg_replace( '/\s+/', ' ', is_string( $value ) ? $value : '' );

		return sanitize_text_field( trim( (string) $value ) );
	}

	/**
	 * Detects candidates that merely repeat the filename.
	 *
	 * @param string              $value Value.
	 * @param array<string,mixed> $item Media row.
	 * @return bool
	 */
	private function media_alt_caption_is_filename_like( $value, array $item ) {
		$value = strtolower( trim( (string) $value ) );
		if ( '' === $value ) {
			return false;
		}

		$filename = strtolower( $this->media_alt_caption_filename_descriptor( (string) ( $item['filename'] ?? '' ) ) );
		if ( '' !== $filename && $value === $filename ) {
			return true;
		}

		return (bool) preg_match( '/^(img|dsc|image|photo|screenshot|screen-shot)[-_ ]?\d+$/i', $value );
	}

	/**
	 * Trims text by characters when mbstring is available.
	 *
	 * @param string $value Value.
	 * @param int    $max_chars Max chars.
	 * @return string
	 */
	private function media_alt_caption_trim_chars( $value, $max_chars ) {
		$value     = trim( (string) $value );
		$max_chars = max( 1, (int) $max_chars );
		if ( '' === $value ) {
			return '';
		}
		if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
			return mb_strlen( $value, 'UTF-8' ) > $max_chars ? mb_substr( $value, 0, $max_chars, 'UTF-8' ) : $value;
		}

		return strlen( $value ) > $max_chars ? substr( $value, 0, $max_chars ) : $value;
	}

	/**
	 * Counts text characters when mbstring is available.
	 *
	 * @param string $value Value.
	 * @return int
	 */
	private function media_alt_caption_text_length( $value ) {
		return function_exists( 'mb_strlen' ) ? mb_strlen( (string) $value, 'UTF-8' ) : strlen( (string) $value );
	}

	/**
	 * Sanitizes a string list.
	 *
	 * @param mixed $value Raw list.
	 * @return array<int,string>
	 */
	private function media_alt_caption_sanitize_string_list( $value ) {
		$items = is_array( $value ) ? $value : array_filter( array_map( 'trim', explode( "\n", (string) $value ) ) );
		return array_values(
			array_filter(
				array_map(
					function ( $item ) {
						return $this->sanitize_metadata_text( (string) $item );
					},
					$items
				),
				static function ( $item ) {
					return '' !== $item;
				}
			)
		);
	}

	/**
	 * Sanitizes bounded nested payload values for review artifacts.
	 *
	 * @param mixed $value Raw value.
	 * @param int   $depth Depth.
	 * @return mixed
	 */
	private function media_alt_caption_sanitize_payload( $value, $depth = 0 ) {
		if ( $depth >= 6 ) {
			return is_array( $value ) ? array() : $this->media_alt_caption_trim_chars( $this->sanitize_metadata_text( (string) $value ), 500 );
		}
		if ( is_array( $value ) ) {
			$sanitized = array();
			$count     = 0;
			foreach ( $value as $key => $child ) {
				if ( $count >= 100 ) {
					break;
				}
				$sanitized[ is_string( $key ) ? sanitize_key( $key ) : $key ] = $this->media_alt_caption_sanitize_payload( $child, $depth + 1 );
				++$count;
			}
			return $sanitized;
		}
		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
			return $value;
		}

		return $this->media_alt_caption_trim_chars( $this->sanitize_metadata_text( (string) $value ), 500 );
	}
}
