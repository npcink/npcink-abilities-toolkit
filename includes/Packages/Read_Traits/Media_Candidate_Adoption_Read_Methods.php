<?php
/**
 * Media image-candidate review and adoption methods for Core_Read_Package.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides review-only image candidate artifacts and governed adoption plans.
 */
trait Media_Candidate_Adoption_Read_Methods {
	/**
	 * Builds a review-only artifact for already retrieved image candidates.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function build_image_candidate_review_artifact( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'upload_files' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to review image candidates.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$raw_candidates = is_array( $input['image_candidates'] ?? null ) ? $input['image_candidates'] : array();
		$candidate_limit = max( 1, min( 12, $this->absint_value( $input['candidate_limit'] ?? 8 ) ) );
		$target_field = sanitize_key( (string) ( $input['target_field'] ?? 'featured_image' ) );
		if ( ! in_array( $target_field, array( 'featured_image', 'paragraph_image', 'inline_image', 'setting_image' ), true ) ) {
			$target_field = 'featured_image';
		}

		$items = array();
		foreach ( array_slice( $raw_candidates, 0, $candidate_limit ) as $candidate ) {
			if ( ! is_array( $candidate ) ) {
				continue;
			}
			$normalized = $this->normalize_image_candidate_adoption_contract( $candidate );
			$image_url = $this->image_candidate_first_non_empty_url(
				array(
					$normalized['download_url'] ?? '',
					$normalized['regular_url'] ?? '',
					$normalized['small_url'] ?? '',
					$normalized['url'] ?? '',
				)
			);
			$title = sanitize_text_field( (string) ( $normalized['title'] ?? $normalized['alt_description'] ?? $normalized['description'] ?? $normalized['prompt'] ?? '' ) );
			if ( '' === $title && '' === $image_url ) {
				continue;
			}
			$items[] = $normalized;
		}

		$recommendation_candidates = $this->build_image_candidate_recommendation_projections( $items, $target_field );

		$data = array(
			'artifact_type'              => 'image_candidate_review.v1',
			'candidate_type'             => 'image_candidates',
			'candidate_contract'         => 'image_candidate.v1',
			'projection_contract'        => 'recommendation_candidate.v1',
			'write_posture'              => 'suggestion_only',
			'final_write_path'           => 'core_proposal_required',
			'direct_wordpress_write'     => false,
			'source_ability_id'          => 'npcink-abilities-toolkit/build-image-candidate-review-artifact',
			'adoption_plan_ability_id'   => 'npcink-abilities-toolkit/build-image-candidate-adoption-plan',
			'items'                      => array_values( $items ),
			'recommendation_candidates'  => $recommendation_candidates,
			'review_policy'              => array(
				'image_retrieval_owner'      => 'host_or_provider_surface',
				'provider_runtime_included'  => false,
				'download_or_import_included' => false,
				'automatic_media_import'     => false,
				'license_review_required'    => true,
				'attribution_preserved'      => true,
			),
			'handoff'                    => array(
				'plan_ability_id'        => 'npcink-abilities-toolkit/build-image-candidate-adoption-plan',
				'final_write_path'       => 'core_proposal_required',
				'direct_wordpress_write' => false,
				'blocked_actions'        => array(
					'no_provider_search_in_toolkit',
					'no_ai_image_generation_in_toolkit',
					'no_media_import_in_review_artifact',
					'no_featured_image_write_in_review_artifact',
				),
			),
			'summary'                    => array(
				'candidate_count'                => count( $items ),
				'recommendation_candidate_count' => count( $recommendation_candidates ),
				'target_field'                   => $target_field,
			),
		);

		return $this->build_analysis_success_response(
			$data,
			array(
				'source'         => 'local_image_candidate_review_artifact',
				'execution_mode' => 'deterministic',
				'plan_only'      => true,
				'readonly'       => true,
			),
			'Image candidate review artifact built.'
		);
	}

	/**
	 * Builds one proposal-ready plan for adopting a reviewed image candidate.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function build_image_candidate_adoption_plan( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'upload_files' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to build image candidate adoption plans.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$raw_candidate = $input['image_candidate'] ?? ( $input['candidate'] ?? array() );
		if ( is_string( $raw_candidate ) ) {
			$decoded = json_decode( $raw_candidate, true );
			$raw_candidate = is_array( $decoded ) ? $decoded : array();
		}
		$candidate = is_array( $raw_candidate ) ? $raw_candidate : array();
		if ( empty( $candidate ) ) {
			$direct_url = $this->image_candidate_first_non_empty_url(
				array(
					$input['download_url'] ?? '',
					$input['image_url'] ?? '',
					$input['url'] ?? '',
				)
			);
			if ( '' !== $direct_url ) {
				$candidate = array(
					'download_url'          => $direct_url,
					'thumbnail_url'         => $input['thumbnail_url'] ?? '',
					'source_url'            => $input['source_url'] ?? '',
					'source_type'           => $input['source_type'] ?? 'external',
					'provider'              => $input['provider'] ?? 'manual',
					'provider_origin'       => $input['provider_origin'] ?? 'toolkit',
					'title'                 => $input['title'] ?? '',
					'description'           => $input['description'] ?? ( $input['alt'] ?? '' ),
					'alt_description'       => $input['alt'] ?? '',
					'attribution'           => $input['attribution_text'] ?? '',
					'photographer'          => $input['photographer_name'] ?? '',
					'prompt'                => $input['prompt'] ?? '',
					'model'                 => $input['model'] ?? '',
					'license_review_status' => $input['license_review_status'] ?? '',
					'warnings'              => $this->image_candidate_sanitize_string_list( $input['warnings'] ?? array() ),
				);
			}
		}
		if ( empty( $candidate ) ) {
			return new \WP_Error(
				'npcink_abilities_toolkit_image_candidate_required',
				__( 'A selected image URL or image_candidate object is required before building an adoption plan.', 'npcink-abilities-toolkit' ),
				array( 'status' => 400 )
			);
		}

		$candidate = $this->normalize_image_candidate_adoption_contract( $candidate );
		$image_url = $this->image_candidate_first_non_empty_url(
			array(
				$candidate['download_url'] ?? '',
				$candidate['regular_url'] ?? '',
				$candidate['small_url'] ?? '',
				$candidate['url'] ?? '',
			)
		);
		if ( '' === $image_url ) {
			return new \WP_Error(
				'npcink_abilities_toolkit_image_candidate_url_missing',
				__( 'The selected image candidate must include a download_url, regular_url, small_url, or url.', 'npcink-abilities-toolkit' ),
				array( 'status' => 400 )
			);
		}
		if ( function_exists( 'wp_http_validate_url' ) && ! wp_http_validate_url( $image_url ) ) {
			return new \WP_Error(
				'npcink_abilities_toolkit_image_candidate_url_blocked',
				__( 'The selected image candidate URL is not allowed.', 'npcink-abilities-toolkit' ),
				array( 'status' => 400 )
			);
		}

		$post_id = $this->absint_value( $input['post_id'] ?? 0 );
		if ( $post_id > 0 && ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to attach image candidate media to this post.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$set_featured_image = $post_id > 0 && ! empty( $input['set_featured_image'] );
		$title             = trim( sanitize_text_field( (string) ( $input['title'] ?? $candidate['title'] ?? $candidate['description'] ?? __( 'Selected image candidate', 'npcink-abilities-toolkit' ) ) ) );
		$alt               = trim( sanitize_textarea_field( (string) ( $input['alt'] ?? $candidate['alt_description'] ?? $candidate['description'] ?? $title ) ) );
		$description       = trim( sanitize_textarea_field( (string) ( $input['description'] ?? $candidate['description'] ?? $alt ) ) );
		$attribution       = trim( sanitize_textarea_field( (string) ( $input['attribution_text'] ?? $candidate['attribution'] ?? '' ) ) );
		$source_type       = sanitize_key( (string) ( $candidate['source_type'] ?? 'external' ) );
		if ( ! in_array( $source_type, array( 'owned', 'ai_generated', 'stock', 'external', 'manual_upload', 'test' ), true ) ) {
			$source_type = 'external';
		}
		if ( 'manual_upload' === $source_type ) {
			$source_type = 'owned';
		}

		$source_url        = $this->esc_url_value( (string) ( $candidate['source_url'] ?? $candidate['html_url'] ?? '' ) );
		$photographer      = sanitize_text_field( (string) ( $candidate['photographer'] ?? $candidate['photographer_name'] ?? '' ) );
		$file_name         = $this->sanitize_file_name_value( (string) ( $input['file_name'] ?? $candidate['file_name'] ?? $candidate['suggested_filename'] ?? '' ) );
		$asset_persistence = is_array( $candidate['asset_persistence'] ?? null )
			? $this->image_candidate_sanitize_payload( $candidate['asset_persistence'] )
			: $this->image_candidate_asset_persistence_policy( $image_url, $candidate );
		$is_temporary_generated_url = 'temporary_provider_url' === (string) ( $asset_persistence['status'] ?? '' );
		$adoption_risk              = $is_temporary_generated_url ? 'high' : 'medium';
		$adoption_notes             = $this->image_candidate_sanitize_string_list( $candidate['warnings'] ?? array() );
		if ( $is_temporary_generated_url ) {
			$adoption_notes[] = __( 'The selected generated image URL may expire before delayed approval. Approve promptly or regenerate before import.', 'npcink-abilities-toolkit' );
		}
		$filename_policy = array(
			'owner'                          => 'wordpress_write_ability_final',
			'proposed_filename'              => $file_name,
			'final_sanitize_unique_required' => true,
			'preserve_attachment_metadata'   => true,
			'source'                         => '' !== $file_name ? 'reviewed_or_candidate_suggestion' : 'wordpress_default',
		);

		$upload_id   = 'upload_image_candidate';
		$metadata_id = 'update_image_candidate_details';
		$upload_input = array(
			'url'               => $image_url,
			'title'             => $title,
			'file_name'         => $file_name,
			'alt'               => $alt,
			'caption'           => $attribution,
			'description'       => $description,
			'source_type'       => $source_type,
			'source_page_url'   => $source_url,
			'photographer_name' => $photographer,
			'attribution_text'  => $attribution,
			'copyright_notice'  => sanitize_text_field( (string) ( $input['copyright_notice'] ?? $candidate['copyright_notice'] ?? '' ) ),
			'dry_run'           => true,
			'commit'            => false,
			'idempotency_key'   => 'image-candidate-upload-' . substr( md5( $image_url . '|' . $post_id ), 0, 12 ),
		);
		if ( $post_id > 0 ) {
			$upload_input['attach_to_post_id'] = $post_id;
		}

		$write_actions = array(
			array(
				'action_id'           => $upload_id,
				'target_ability_id'   => 'npcink-abilities-toolkit/upload-media-from-url',
				'recipe_step'         => 'host_governed_upload_image_candidate',
				'input'               => $upload_input,
				'source_asset_policy' => $asset_persistence,
				'adoption_notes'      => array_values( array_unique( $adoption_notes ) ),
				'risk'                => $adoption_risk,
				'requires_approval'   => true,
				'commit_execution'    => false,
				'proposal_ready'      => true,
				'reason'              => __( 'Import the reviewed image candidate into the media library after Core approval.', 'npcink-abilities-toolkit' ),
			),
			array(
				'action_id'           => $metadata_id,
				'target_ability_id'   => 'npcink-abilities-toolkit/update-media-details',
				'recipe_step'         => 'host_governed_update_image_candidate_metadata',
				'depends_on'          => array( $upload_id ),
				'input'               => array(
					'attachment_id'     => '$outputs.' . $upload_id . '.attachment_id',
					'title'             => $title,
					'alt'               => $alt,
					'caption'           => $attribution,
					'description'       => $description,
					'source_type'       => $source_type,
					'source_page_url'   => $source_url,
					'photographer_name' => $photographer,
					'attribution_text'  => $attribution,
					'copyright_notice'  => sanitize_text_field( (string) ( $input['copyright_notice'] ?? $candidate['copyright_notice'] ?? '' ) ),
					'dry_run'           => true,
					'commit'            => false,
					'idempotency_key'   => 'image-candidate-details-' . substr( md5( $image_url . '|' . $post_id ), 0, 12 ),
				),
				'source_asset_policy' => $asset_persistence,
				'adoption_notes'      => array_values( array_unique( $adoption_notes ) ),
				'risk'                => $adoption_risk,
				'requires_approval'   => true,
				'commit_execution'    => false,
				'proposal_ready'      => true,
				'reason'              => __( 'Apply reviewed image candidate metadata after media import.', 'npcink-abilities-toolkit' ),
			),
		);

		if ( $set_featured_image ) {
			$write_actions[] = array(
				'action_id'         => 'set_image_candidate_featured_image',
				'target_ability_id' => 'npcink-abilities-toolkit/set-post-featured-image',
				'recipe_step'       => 'host_governed_set_image_candidate_featured_image',
				'depends_on'        => array( $upload_id ),
				'input'             => array(
					'post_id'         => $post_id,
					'attachment_id'   => '$outputs.' . $upload_id . '.attachment_id',
					'dry_run'         => true,
					'commit'          => false,
					'idempotency_key' => 'image-candidate-featured-' . substr( md5( $image_url . '|' . $post_id ), 0, 12 ),
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
				'reason'            => __( 'Set the imported image candidate as the post featured image after Core approval.', 'npcink-abilities-toolkit' ),
			);
		}

		$data = array(
			'artifact_type'              => 'image_candidate_adoption_plan',
			'composition_role'           => 'core_image_candidate_adoption_plan',
			'version'                    => 1,
			'candidate_contract_version' => 'image_candidate.v1',
			'source_recipe_id'           => 'image_candidate_adoption_v1',
			'source_recipe_ref'          => 'workflow/image_candidate_adoption',
			'source_recipe_provider'     => 'npcink-abilities-toolkit',
			'recipe_execution'           => 'local_operator_orchestration',
			'write_posture'              => 'core_proposal_handoff',
			'direct_wordpress_write'     => false,
			'proposed_filename'          => $file_name,
			'filename_policy'            => $filename_policy,
			'source_asset_policy'        => $asset_persistence,
			'adoption_notes'             => array_values( array_unique( $adoption_notes ) ),
			'batch_id'                   => 'image_candidate_adoption_' . substr( md5( $image_url . '|' . $post_id . '|' . wp_json_encode( $write_actions ) ), 0, 12 ),
			'requires_approval'          => true,
			'dry_run'                    => true,
			'commit_execution'           => false,
			'proposal_mode'              => 'batch',
			'batch_approval'             => true,
			'action_count'               => count( $write_actions ),
			'selected_image_candidate'   => $this->image_candidate_sanitize_payload( $candidate ),
			'preview'                    => array(
				array(
					'action_id'           => $upload_id,
					'image_url'           => $image_url,
					'thumbnail_url'       => $this->esc_url_value( (string) ( $candidate['thumbnail_url'] ?? $image_url ) ),
					'source_type'         => $source_type,
					'provider'            => sanitize_key( (string) ( $candidate['provider'] ?? 'external' ) ),
					'provider_origin'     => sanitize_key( (string) ( $candidate['provider_origin'] ?? 'toolkit' ) ),
					'proposed_filename'   => $file_name,
					'filename_policy'     => $filename_policy,
					'post_id'             => $post_id,
					'set_featured_image'  => $set_featured_image,
					'attribution'         => $attribution,
					'source_asset_policy' => $asset_persistence,
				),
			),
			'write_actions'              => $write_actions,
			'handoff'                    => array(
				'plan_ability_id'        => 'npcink-abilities-toolkit/build-image-candidate-adoption-plan',
				'recipe_id'              => 'image_candidate_adoption_v1',
				'recipe_ref'             => 'workflow/image_candidate_adoption',
				'core_route'             => '/wp-json/npcink-governance-core/v1/proposals/from-plan',
				'final_write_path'       => 'core_proposal_required',
				'direct_wordpress_write' => false,
			),
		);

		return $this->build_analysis_success_response(
			$data,
			array(
				'source'         => 'local_image_candidate_adoption_plan',
				'execution_mode' => 'deterministic',
				'plan_only'      => true,
				'readonly'       => true,
			),
			'Image candidate adoption plan built.'
		);
	}

	/**
	 * Normalizes one reviewed image candidate into the shared image_candidate.v1 shape.
	 *
	 * @param array<string,mixed> $candidate Raw candidate.
	 * @return array<string,mixed>
	 */
	private function normalize_image_candidate_adoption_contract( array $candidate ): array {
		$provider = sanitize_key( (string) ( $candidate['provider'] ?? 'external' ) );
		$source_type = sanitize_key( (string) ( $candidate['source_type'] ?? '' ) );
		if ( '' === $source_type ) {
			if ( 'ai_generated' === $provider ) {
				$source_type = 'ai_generated';
			} elseif ( in_array( $provider, array( 'unsplash', 'pixabay', 'pexels' ), true ) ) {
				$source_type = 'stock';
			} else {
				$source_type = 'external';
			}
		}

		$download_url = $this->image_candidate_first_non_empty_url(
			array(
				$candidate['download_url'] ?? '',
				$candidate['regular_url'] ?? '',
				$candidate['url'] ?? '',
				$candidate['image_url'] ?? '',
				$candidate['generated_image_url'] ?? '',
				$candidate['output_url'] ?? '',
				$candidate['small_url'] ?? '',
				$candidate['urls']['regular'] ?? '',
				$candidate['urls']['full'] ?? '',
				$candidate['src']['large'] ?? '',
				$candidate['src']['original'] ?? '',
			)
		);
		$thumbnail_url = $this->image_candidate_first_non_empty_url(
			array(
				$candidate['thumbnail_url'] ?? '',
				$candidate['thumb_url'] ?? '',
				$candidate['small_url'] ?? '',
				$candidate['urls']['small'] ?? '',
				$candidate['urls']['thumb'] ?? '',
				$candidate['src']['medium'] ?? '',
				$candidate['src']['tiny'] ?? '',
				$download_url,
			)
		);
		$source_url = $this->esc_url_value( (string) ( $candidate['source_url'] ?? $candidate['html_url'] ?? $candidate['links']['html'] ?? $candidate['url'] ?? '' ) );
		$prompt = trim( sanitize_textarea_field( (string) ( $candidate['prompt'] ?? $candidate['generation_prompt'] ?? '' ) ) );
		$model = sanitize_text_field( (string) ( $candidate['model'] ?? $candidate['generation_model'] ?? '' ) );
		$license_review_status = $this->normalize_image_candidate_license_review_status( (string) ( $candidate['license_review_status'] ?? '' ), $source_type );
		$provider_origin = sanitize_key( (string) ( $candidate['provider_origin'] ?? 'toolkit' ) );
		$warnings = $this->image_candidate_sanitize_string_list( $candidate['warnings'] ?? array() );
		$match_reason = sanitize_textarea_field( (string) ( $candidate['match_reason'] ?? $candidate['reason'] ?? $candidate['recommendation_reason'] ?? '' ) );
		$match_score = is_numeric( $candidate['match_score'] ?? null ) ? (float) $candidate['match_score'] : null;
		$recommended_use = sanitize_key( (string) ( $candidate['recommended_use'] ?? $candidate['image_use'] ?? $candidate['best_use'] ?? '' ) );
		if ( ! in_array( $recommended_use, array( 'featured_image', 'paragraph_image', 'inline_image', 'setting_image', 'not_recommended' ), true ) ) {
			$recommended_use = '';
		}
		$visual_keywords = $this->image_candidate_sanitize_string_list( $candidate['visual_keywords'] ?? $candidate['keywords'] ?? array() );
		$quality_tags = $this->image_candidate_sanitize_string_list( $candidate['quality_tags'] ?? $candidate['match_tags'] ?? array() );
		$risk_flags = $this->image_candidate_sanitize_string_list( $candidate['risk_flags'] ?? $candidate['review_flags'] ?? array() );
		$seo_suggestions = is_array( $candidate['seo_suggestions'] ?? null )
			? $this->image_candidate_sanitize_payload( $candidate['seo_suggestions'] )
			: ( is_array( $candidate['media_seo'] ?? null ) ? $this->image_candidate_sanitize_payload( $candidate['media_seo'] ) : array() );
		$asset_persistence = is_array( $candidate['asset_persistence'] ?? null )
			? $this->image_candidate_sanitize_payload( $candidate['asset_persistence'] )
			: array();
		$file_name = $this->sanitize_file_name_value( (string) ( $candidate['file_name'] ?? '' ) );
		$suggested_filename = $this->sanitize_file_name_value( (string) ( $candidate['suggested_filename'] ?? $file_name ) );
		if ( '' === $file_name && '' !== $suggested_filename ) {
			$file_name = $suggested_filename;
		}
		$filename_basis = is_array( $candidate['filename_basis'] ?? null )
			? $this->image_candidate_sanitize_payload( $candidate['filename_basis'] )
			: array(
				'owner'                          => 'wordpress_write_ability_final',
				'strategy'                       => 'candidate_suggested_filename',
				'final_sanitize_unique_required' => true,
			);

		$candidate['contract_version']              = 'image_candidate.v1';
		$candidate['source_type']                   = $source_type;
		$candidate['provider']                      = $provider;
		$candidate['provider_origin']               = '' !== $provider_origin ? $provider_origin : 'toolkit';
		$candidate['download_url']                  = $download_url;
		$candidate['thumbnail_url']                 = $thumbnail_url;
		$candidate['source_url']                    = $source_url;
		$candidate['regular_url']                   = $this->esc_url_value( (string) ( $candidate['regular_url'] ?? $candidate['urls']['regular'] ?? $download_url ) );
		$candidate['small_url']                     = $this->esc_url_value( (string) ( $candidate['small_url'] ?? $candidate['urls']['small'] ?? $thumbnail_url ) );
		$candidate['html_url']                      = $this->esc_url_value( (string) ( $candidate['html_url'] ?? $candidate['links']['html'] ?? $source_url ) );
		$candidate['download_location']             = $this->esc_url_value( (string) ( $candidate['download_location'] ?? $candidate['links']['download_location'] ?? '' ) );
		$candidate['photographer']                  = sanitize_text_field( (string) ( $candidate['photographer'] ?? $candidate['user']['name'] ?? '' ) );
		$candidate['photographer_url']              = $this->esc_url_value( (string) ( $candidate['photographer_url'] ?? $candidate['user']['links']['html'] ?? '' ) );
		$candidate['prompt']                        = $prompt;
		$candidate['model']                         = $model;
		$candidate['license_review_status']         = $license_review_status;
		$candidate['requires_human_license_review'] = 'not_required' !== $license_review_status;
		$candidate['warnings']                      = $warnings;
		$candidate['match_reason']                  = $match_reason;
		$candidate['match_score']                   = $match_score;
		$candidate['recommended_use']               = $recommended_use;
		$candidate['visual_keywords']               = $visual_keywords;
		$candidate['quality_tags']                  = array_slice( $quality_tags, 0, 6 );
		$candidate['risk_flags']                    = array_slice( $risk_flags, 0, 6 );
		$candidate['seo_suggestions']               = $seo_suggestions;
		if ( array() !== $asset_persistence ) {
			$candidate['asset_persistence'] = $asset_persistence;
		}
		$candidate['file_name']          = $file_name;
		$candidate['suggested_filename'] = '' !== $suggested_filename ? $suggested_filename : $file_name;
		$candidate['filename_basis']     = $filename_basis;
		$candidate['provenance']         = array(
			'provider'            => $provider,
			'provider_origin'     => $candidate['provider_origin'],
			'source_type'         => $source_type,
			'source_url'          => $source_url,
			'download_location'   => $candidate['download_location'],
			'photographer'        => $candidate['photographer'],
			'generation_provider' => sanitize_key( (string) ( $candidate['generation_provider'] ?? $candidate['provider_name'] ?? '' ) ),
			'generation_model'    => $model,
		);

		return $candidate;
	}

	/**
	 * Builds lightweight recommendation projections for image candidate review.
	 *
	 * @param array<int,array<string,mixed>> $items Normalized image candidates.
	 * @param string                         $target_field Target field.
	 * @return array<int,array<string,mixed>>
	 */
	private function build_image_candidate_recommendation_projections( array $items, string $target_field ): array {
		$candidates = array();
		foreach ( array_slice( $items, 0, 12 ) as $index => $item ) {
			$title = sanitize_text_field( (string) ( $item['title'] ?? $item['alt_description'] ?? $item['description'] ?? $item['prompt'] ?? '' ) );
			$url = $this->image_candidate_first_non_empty_url(
				array(
					$item['download_url'] ?? '',
					$item['regular_url'] ?? '',
					$item['small_url'] ?? '',
					$item['url'] ?? '',
				)
			);
			if ( '' === $title && '' === $url ) {
				continue;
			}

			$warnings = array();
			foreach ( array( 'warnings', 'risk_flags', 'quality_tags' ) as $key ) {
				$warnings = array_merge( $warnings, $this->image_candidate_sanitize_string_list( $item[ $key ] ?? array() ) );
			}
			$license_status = sanitize_key( (string) ( $item['license_review_status'] ?? '' ) );
			if ( '' !== $license_status && 'not_required' !== $license_status && 'reviewed' !== $license_status ) {
				$warnings[] = __( 'Image license or source needs human confirmation.', 'npcink-abilities-toolkit' );
			}

			$match_score = is_numeric( $item['match_score'] ?? null ) ? (float) $item['match_score'] : 0.0;
			$quality_score = $match_score > 0
				? ( $match_score <= 1 ? 55 + (int) round( max( 0, min( 1, $match_score ) ) * 35 ) : max( 0, min( 90, (int) round( $match_score ) ) ) )
				: 65;
			if ( ! empty( $warnings ) ) {
				$quality_score = min( $quality_score, 65 );
			}
			if ( '' === $url ) {
				$quality_score = min( $quality_score, 45 );
				$warnings[] = __( 'A usable image URL is missing.', 'npcink-abilities-toolkit' );
			}
			if ( empty( $warnings ) ) {
				$warnings[] = __( 'Keep the full image_candidate.v1 evidence for license, attribution, and adoption-plan review.', 'npcink-abilities-toolkit' );
			}

			$source_ref = sanitize_text_field( (string) ( $item['id'] ?? '' ) );
			if ( '' === $source_ref ) {
				$source_ref = '' !== $url ? $url : 'image_candidate_' . ( $index + 1 );
			}

			$candidates[] = array(
				'id'                   => 'image_candidate_' . ( $index + 1 ),
				'kind'                 => 'image',
				'label'                => '' !== $title ? $title : __( 'Image candidate', 'npcink-abilities-toolkit' ),
				'value'                => '' !== $url ? $url : $title,
				'reason'               => sanitize_text_field( (string) ( $item['match_reason'] ?? $item['reason'] ?? '' ) ),
				'confidence'           => $match_score > 0 && $match_score <= 1 ? $match_score : null,
				'target_field'         => $target_field,
				'action_policy'        => 'core_proposal_required',
				'quality_status'       => $quality_score >= 50 ? 'review' : 'weak',
				'quality_score'        => $quality_score,
				'quality_issues'       => array_values( array_unique( $warnings ) ),
				'evidence_refs'        => array_values(
					array_filter(
						array(
							'' !== (string) ( $item['provider'] ?? '' ) ? 'image_provider:' . sanitize_key( (string) $item['provider'] ) : '',
							'' !== (string) ( $item['source_type'] ?? '' ) ? 'image_source_type:' . sanitize_key( (string) $item['source_type'] ) : '',
						)
					)
				),
				'source_candidate_ref' => $source_ref,
			);
		}

		return $candidates;
	}

	/**
	 * Normalizes image candidate license review status.
	 *
	 * @param string $status Raw status.
	 * @param string $source_type Candidate source type.
	 * @return string
	 */
	private function normalize_image_candidate_license_review_status( string $status, string $source_type ): string {
		$status = sanitize_key( $status );
		if ( in_array( $status, array( 'required', 'reviewed', 'not_required' ), true ) ) {
			return $status;
		}
		if ( in_array( $status, array( 'needs_human_review', 'needs_review', 'human_review_required' ), true ) ) {
			return 'required';
		}
		if ( 'owned' === $source_type ) {
			return 'not_required';
		}
		return 'required';
	}

	/**
	 * Returns the first non-empty sanitized URL.
	 *
	 * @param array<int,mixed> $urls Candidate URLs.
	 * @return string
	 */
	private function image_candidate_first_non_empty_url( array $urls ): string {
		foreach ( $urls as $url ) {
			$clean = $this->esc_url_value( (string) $url );
			if ( '' !== $clean ) {
				return $clean;
			}
		}

		return '';
	}

	/**
	 * Builds source persistence policy for image candidate adoption.
	 *
	 * @param string              $url Candidate URL.
	 * @param array<string,mixed> $candidate Candidate data.
	 * @return array<string,mixed>
	 */
	private function image_candidate_asset_persistence_policy( string $url, array $candidate ): array {
		$expires_at = sanitize_text_field( (string) ( $candidate['expires_at'] ?? $candidate['url_expires_at'] ?? '' ) );
		$is_temporary = $this->is_temporary_image_candidate_url( $url );
		$status = $is_temporary ? 'temporary_provider_url' : 'remote_url';
		if ( '' !== $expires_at ) {
			$status = 'temporary_provider_url';
		}

		return array(
			'status'             => $status,
			'expires_at'         => $expires_at,
			'requires_local_copy' => true,
			'adoption_timing'    => 'temporary_provider_url' === $status ? 'adopt_promptly_or_regenerate' : 'core_import_on_approval',
			'owner'              => 'core_upload_ability_final',
		);
	}

	/**
	 * Returns whether an image candidate URL appears temporary.
	 *
	 * @param string $url Candidate URL.
	 * @return bool
	 */
	private function is_temporary_image_candidate_url( string $url ): bool {
		$url = strtolower( trim( $url ) );
		if ( '' === $url ) {
			return false;
		}
		foreach ( array( 'xai-tmp', '/tmp-', 'tmp-imgen', 'temporary', 'expires=' ) as $needle ) {
			if ( false !== strpos( $url, $needle ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Sanitizes a list of text strings.
	 *
	 * @param mixed $value Raw list or newline text.
	 * @return array<int,string>
	 */
	private function image_candidate_sanitize_string_list( $value ): array {
		$items = is_array( $value ) ? $value : array_filter( array_map( 'trim', explode( "\n", (string) $value ) ) );
		return array_values(
			array_filter(
				array_map(
					static fn( $item ): string => sanitize_textarea_field( (string) $item ),
					$items
				),
				static fn( string $item ): bool => '' !== $item
			)
		);
	}

	/**
	 * Sanitizes bounded payload trees for plan evidence.
	 *
	 * @param mixed $value Raw value.
	 * @param int   $depth Current depth.
	 * @return mixed
	 */
	private function image_candidate_sanitize_payload( $value, int $depth = 0 ) {
		if ( $depth >= 6 ) {
			return is_array( $value ) ? array() : $this->image_candidate_bounded_text( (string) $value, 2000 );
		}

		if ( is_array( $value ) ) {
			$sanitized = array();
			$count = 0;
			foreach ( $value as $key => $child ) {
				if ( $count >= 80 ) {
					break;
				}
				$sanitized[ is_string( $key ) ? sanitize_key( $key ) : $key ] = $this->image_candidate_sanitize_payload( $child, $depth + 1 );
				++$count;
			}
			return $sanitized;
		}

		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
			return $value;
		}

		return $this->image_candidate_bounded_text( (string) $value, 2000 );
	}

	/**
	 * Sanitizes and truncates one text value.
	 *
	 * @param string $value Raw value.
	 * @param int    $max_chars Max characters.
	 * @return string
	 */
	private function image_candidate_bounded_text( string $value, int $max_chars ): string {
		$value = sanitize_textarea_field( $value );
		$max_chars = max( 1, $max_chars );
		if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) && mb_strlen( $value ) > $max_chars ) {
			return mb_substr( $value, 0, $max_chars );
		}
		if ( strlen( $value ) > $max_chars ) {
			return substr( $value, 0, $max_chars );
		}

		return $value;
	}

	/**
	 * Builds one proposal-ready plan for importing, optimizing, and optionally repairing page media references.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function build_media_adoption_enhancement_plan( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'upload_files' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to build media adoption enhancement plans.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$url = esc_url_raw( (string) ( $input['url'] ?? '' ) );
		if ( '' === $url ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_url_required', __( 'Media URL is required.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( function_exists( 'wp_http_validate_url' ) && ! wp_http_validate_url( $url ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_url_blocked', __( 'Media URL is not allowed.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$post_id = $this->absint_value( $input['post_id'] ?? 0 );
		if ( $post_id > 0 && ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to repair media references for this post.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}
		$attach_to_post_id = $this->absint_value( $input['attach_to_post_id'] ?? $post_id );
		if ( $attach_to_post_id > 0 && ! current_user_can( 'edit_post', $attach_to_post_id ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to attach media to this post.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$file_name = $this->normalize_media_optimization_file_name( (string) ( $input['file_name'] ?? '' ) );
		if ( is_wp_error( $file_name ) ) {
			return $file_name;
		}
		$preferred_format = sanitize_key( (string) ( $input['preferred_format'] ?? 'webp' ) );
		if ( ! in_array( $preferred_format, array( 'webp', 'jpeg', 'png' ), true ) ) {
			$preferred_format = 'webp';
		}
		$target_max_width = max( 320, min( 7680, $this->absint_value( $input['target_max_width'] ?? 1920 ) ) );
		$quality = max( 1, min( 100, $this->absint_value( $input['quality'] ?? 82 ) ) );
		$derivative_suffix = sanitize_key( (string) ( $input['derivative_suffix'] ?? 'optimized' ) );
		if ( '' === $derivative_suffix ) {
			$derivative_suffix = 'optimized';
		}

		$title = sanitize_text_field( (string) ( $input['title'] ?? '' ) );
		$alt = sanitize_text_field( (string) ( $input['alt'] ?? '' ) );
		$caption = sanitize_textarea_field( (string) ( $input['caption'] ?? '' ) );
		$description = sanitize_textarea_field( (string) ( $input['description'] ?? '' ) );
		$source_metadata = $this->media_source_metadata_with_defaults(
			array(
				'source_type'       => $this->normalize_media_source_type( $input['source_type'] ?? 'external', 'external' ),
				'source_page_url'   => $this->esc_url_value( (string) ( $input['source_page_url'] ?? $url ) ),
				'photographer_name' => sanitize_text_field( (string) ( $input['photographer_name'] ?? '' ) ),
				'attribution_text'  => sanitize_textarea_field( (string) ( $input['attribution_text'] ?? '' ) ),
				'copyright_notice'  => sanitize_textarea_field( (string) ( $input['copyright_notice'] ?? '' ) ),
			)
		);

		$batch_seed = wp_json_encode( array( $url, $post_id, $attach_to_post_id, $file_name, $preferred_format, $target_max_width, $quality ) );
		$batch_id = 'media_adoption_enhancement_' . substr( md5( is_string( $batch_seed ) ? $batch_seed : $url ), 0, 12 );
		$upload_action_id = 'upload-media-asset';
		$optimize_action_id = 'optimize-media-asset';

		$upload_input = array(
			'url'               => $url,
			'title'             => $title,
			'file_name'         => $file_name,
			'alt'               => $alt,
			'caption'           => $caption,
			'description'       => $description,
			'source_type'       => $source_metadata['source_type'],
			'source_page_url'   => $source_metadata['source_page_url'],
			'photographer_name' => $source_metadata['photographer_name'],
			'attribution_text'  => $source_metadata['attribution_text'],
			'copyright_notice'  => $source_metadata['copyright_notice'],
			'dry_run'           => true,
			'commit'            => false,
		);
		if ( $attach_to_post_id > 0 ) {
			$upload_input['attach_to_post_id'] = $attach_to_post_id;
		}

		$actions = array(
			array(
				'action_id'         => $upload_action_id,
				'target_ability_id' => 'npcink-abilities-toolkit/upload-media-from-url',
				'depends_on'        => array(),
				'input'             => $upload_input,
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
				'reason'            => __( 'Import the reviewed remote media asset into the local WordPress media library.', 'npcink-abilities-toolkit' ),
			),
			array(
				'action_id'         => $optimize_action_id,
				'target_ability_id' => 'npcink-abilities-toolkit/optimize-media-asset',
				'depends_on'        => array( $upload_action_id ),
				'input'             => array(
					'attachment_id'     => '$outputs.' . $upload_action_id . '.attachment_id',
					'target_max_width'  => $target_max_width,
					'preferred_format'  => $preferred_format,
					'quality'           => $quality,
					'derivative_suffix' => $derivative_suffix,
					'dry_run'           => true,
					'commit'            => false,
				),
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
				'reason'            => __( 'Generate an optimized derivative while preserving the imported original media file.', 'npcink-abilities-toolkit' ),
			),
		);

		$manual_review = array();
		$reference_preview = array();
		$old_url = esc_url_raw( (string) ( $input['old_url'] ?? '' ) );
		$include_reference_repair = ! array_key_exists( 'include_reference_repair', $input ) || ! empty( $input['include_reference_repair'] );
		if ( $include_reference_repair && $post_id > 0 && '' !== $old_url ) {
			$post = get_post( $post_id );
			$content = is_object( $post ) ? (string) $post->post_content : '';
			$old_url_count = '' !== $content ? substr_count( $content, $old_url ) : 0;
			if ( $old_url_count > 0 ) {
				$max_replacements = max( 1, min( 20, $this->absint_value( $input['max_replacements_per_post'] ?? 20 ) ) );
				$actions[] = array(
					'action_id'         => 'repair-post-media-reference',
					'target_ability_id' => 'npcink-abilities-toolkit/patch-post-content',
					'depends_on'        => array( $optimize_action_id ),
					'input'             => array(
						'post_id'    => $post_id,
						'operations' => array(
							array(
								'op'      => 'replace',
								'find'    => $old_url,
								'replace' => '$outputs.' . $optimize_action_id . '.derivative_url',
								'limit'   => min( $max_replacements, $old_url_count ),
							),
						),
						'dry_run'    => true,
						'commit'     => false,
					),
					'requires_approval' => true,
					'commit_execution'  => false,
					'proposal_ready'    => true,
					'reason'            => __( 'Replace reviewed hard-coded post media URLs with the optimized derivative URL after approval.', 'npcink-abilities-toolkit' ),
				);
				$reference_preview[] = array(
					'post_id'       => $post_id,
					'old_url'       => $old_url,
					'new_url_ref'   => '$outputs.' . $optimize_action_id . '.derivative_url',
					'match_count'   => $old_url_count,
					'planned_limit' => min( $max_replacements, $old_url_count ),
				);
			} else {
				$manual_review[] = array(
					'post_id' => $post_id,
					'reason'  => 'old_url_not_found_in_post_content',
					'old_url' => $old_url,
				);
			}
		}

		$data = array(
			'artifact_type'          => 'media_adoption_enhancement_plan',
			'plan_contract_version'  => '1.0',
			'batch_id'               => $batch_id,
			'proposal_mode'          => 'batch',
			'batch_approval'         => true,
			'requires_approval'      => true,
			'commit_execution'       => false,
			'dry_run'                => true,
			'direct_wordpress_write' => false,
			'action_count'           => count( $actions ),
			'write_actions'          => $actions,
			'media'                  => array(
				'url'               => $url,
				'file_name'         => $file_name,
				'title'             => $title,
				'alt'               => $alt,
				'source_type'       => $source_metadata['source_type'],
				'attach_to_post_id' => $attach_to_post_id,
			),
			'optimization'           => array(
				'preferred_format'  => $preferred_format,
				'target_max_width'  => $target_max_width,
				'quality'           => $quality,
				'derivative_suffix' => $derivative_suffix,
			),
			'reference_repair'       => array(
				'enabled' => $include_reference_repair,
				'post_id' => $post_id,
				'old_url' => $old_url,
				'preview' => $reference_preview,
			),
			'manual_review'          => $manual_review,
			'proposal_ready'         => true,
			'handoff'                => array(
				'plan_ability_id'      => 'npcink-abilities-toolkit/build-media-adoption-enhancement-plan',
				'preferred_core_route' => 'POST /proposals/from-plan',
			),
			'risk'                   => array(
				'level'  => ! empty( $manual_review ) ? 'medium' : 'low',
				'reason' => ! empty( $manual_review ) ? 'The media can be imported and optimized, but one requested reference repair needs review.' : 'All planned writes are explicit governed media or exact post-content actions.',
			),
		);

		return $this->build_analysis_success_response(
			$data,
			array(
				'source'         => 'local_media_adoption_enhancement_plan',
				'execution_mode' => 'deterministic',
				'plan_only'      => true,
				'readonly'       => true,
			),
			'Media adoption enhancement plan built.'
		);
	}
}
