<?php
/**
 * Article audio planning read methods.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds Core-ready article audio adoption plans without mutating WordPress.
 */
trait Article_Audio_Read_Methods {
	/**
	 * Builds one reviewed article audio adoption plan.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function build_article_audio_adoption_plan( $input ) {
		$input     = is_array( $input ) ? $input : array();
		$post_id   = absint( $input['post_id'] ?? 0 );
		$candidate = is_array( $input['audio_candidate'] ?? null ) ? $input['audio_candidate'] : ( is_array( $input['candidate'] ?? null ) ? $input['candidate'] : array() );
		$audio_url = esc_url_raw( (string) ( $candidate['url'] ?? ( $input['audio_url'] ?? '' ) ) );

		if ( $post_id <= 0 ) {
			return new \WP_Error( 'npcink_abilities_toolkit_article_audio_post_required', __( 'A post_id is required before preparing article audio adoption.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( '' === $audio_url ) {
			return new \WP_Error( 'npcink_abilities_toolkit_article_audio_url_required', __( 'An audio URL is required before preparing article audio adoption.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$kind = sanitize_key( (string) ( $candidate['candidate_type'] ?? ( $input['candidate_type'] ?? ( $candidate['kind'] ?? 'article_narration' ) ) ) );
		if ( ! in_array( $kind, array( 'article_narration', 'article_audio_summary' ), true ) ) {
			$kind = 'article_narration';
		}

		$title = sanitize_text_field( (string) ( $candidate['title'] ?? ( $candidate['name'] ?? ( 'article_audio_summary' === $kind ? __( 'Audio summary', 'npcink-abilities-toolkit' ) : __( 'Article narration', 'npcink-abilities-toolkit' ) ) ) ) );
		if ( '' === $title ) {
			$title = 'article_audio_summary' === $kind ? __( 'Audio summary', 'npcink-abilities-toolkit' ) : __( 'Article narration', 'npcink-abilities-toolkit' );
		}

		$duration_seconds = isset( $candidate['duration_seconds'] ) && is_numeric( $candidate['duration_seconds'] ) ? max( 0, (float) $candidate['duration_seconds'] ) : 0.0;
		$mime_type        = sanitize_text_field( (string) ( $candidate['mime_type'] ?? 'audio/mpeg' ) );
		$source_hash      = sanitize_text_field( (string) ( $candidate['source_content_hash'] ?? ( $input['source_content_hash'] ?? '' ) ) );
		$source_words     = absint( $candidate['source_word_count'] ?? ( $input['source_word_count'] ?? 0 ) );
		$generated_at     = sanitize_text_field( (string) ( $candidate['source_generated_at'] ?? ( $candidate['generated_at'] ?? ( $input['source_generated_at'] ?? gmdate( 'c' ) ) ) ) );
		$provider         = sanitize_key( (string) ( $candidate['provider'] ?? ( $input['provider'] ?? '' ) ) );
		$model            = sanitize_text_field( (string) ( $candidate['model'] ?? ( $input['model'] ?? '' ) ) );
		$trace_id         = sanitize_text_field( (string) ( $candidate['trace_id'] ?? ( $candidate['trace'] ?? ( $input['trace_id'] ?? '' ) ) ) );

		$ability_input = array(
			'post_id'                                    => $post_id,
			'audio_url'                                  => $audio_url,
			'audio_title'                                => $title,
			'audio_kind'                                 => $kind,
			'duration_seconds'                           => $duration_seconds,
			'mime_type'                                  => $mime_type,
			'source_content_hash'                        => $source_hash,
			'source_word_count'                          => $source_words,
			'source_generated_at'                        => $generated_at,
			'provider'                                   => $provider,
			'model'                                      => $model,
			'trace_id'                                   => $trace_id,
			'dry_run'                                    => true,
			'commit'                                     => false,
			'idempotency_key'                            => 'article-audio-adoption-' . substr( md5( $post_id . '|' . $kind . '|' . $audio_url ), 0, 16 ),
		);

		$write_actions = array(
			array(
				'action_id'         => 'adopt_article_audio',
				'target_ability_id' => 'npcink-abilities-toolkit/adopt-article-audio',
				'recipe_step'       => 'host_governed_article_audio_adoption',
				'input'             => $ability_input,
				'required_scopes'   => array( 'post.write' ),
				'risk'              => 'low',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
				'reason'            => __( 'Attach the reviewed generated audio candidate to the current post after Core approval.', 'npcink-abilities-toolkit' ),
			),
		);

		$data = array(
			'artifact_type'          => 'article_audio_adoption_plan.v1',
			'composition_role'       => 'core_article_audio_adoption_plan',
			'version'                => 1,
			'source_recipe_id'       => 'article_audio_adoption_v1',
			'source_recipe_ref'      => 'workflow/article_audio_adoption',
			'source_recipe_provider' => 'npcink-abilities-toolkit',
			'write_posture'          => 'core_proposal_handoff',
			'direct_wordpress_write' => false,
			'batch_id'               => 'article_audio_adoption_' . substr( md5( $post_id . '|' . $kind . '|' . $audio_url ), 0, 12 ),
			'target_post_id'         => $post_id,
			'requires_approval'      => true,
			'dry_run'                => true,
			'commit_execution'       => false,
			'action_count'           => 1,
			'risk'                   => array( 'level' => 'low' ),
			'preview'                => array(
				array(
					'action_id'        => 'adopt_article_audio',
					'post_id'          => $post_id,
					'audio_url'        => $audio_url,
					'audio_title'      => $title,
					'audio_kind'       => $kind,
					'duration_seconds' => $duration_seconds,
					'mime_type'        => $mime_type,
					'provider'         => $provider,
					'model'            => $model,
					'trace_id'         => $trace_id,
				),
			),
			'write_actions'          => $write_actions,
			'handoff'                => array(
				'plan_ability_id'        => 'npcink-abilities-toolkit/build-article-audio-adoption-plan',
				'recipe_id'              => 'article_audio_adoption_v1',
				'recipe_ref'             => 'workflow/article_audio_adoption',
				'core_route'             => '/wp-json/npcink-governance-core/v1/proposals/from-plan',
				'final_write_path'       => 'core_proposal_required',
				'direct_wordpress_write' => false,
			),
		);

		return $this->build_analysis_success_response(
			$data,
			array(
				'source'         => 'local_article_audio_adoption_plan',
				'execution_mode' => 'deterministic',
				'plan_only'      => true,
				'readonly'       => true,
			),
			__( 'Article audio adoption plan is ready for Core proposal intake.', 'npcink-abilities-toolkit' )
		);
	}
}
