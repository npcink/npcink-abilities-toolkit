<?php
/**
 * Gutenberg composer repair loop methods for Core_Read_Package.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds one bounded route-plan-review-repair pass without writing content.
 */
trait Gutenberg_Composer_Repair_Read_Methods {
	/**
	 * Routes a natural-language Gutenberg request, builds a plan, reviews it, and applies one bounded repair pass when needed.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function compose_gutenberg_block_plan( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to compose Gutenberg block plans.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$prompt = $this->content_intent_text( $input['prompt'] ?? '' );
		if ( '' === $prompt ) {
			return new \WP_Error( 'npcink_abilities_toolkit_gutenberg_composer_prompt_required', __( 'A natural-language content prompt is required.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$route_response = $this->route_content_intent(
			array(
				'prompt'      => $prompt,
				'target_hint' => sanitize_key( (string) ( $input['target_hint'] ?? 'auto' ) ),
				'intent_hint' => sanitize_key( (string) ( $input['intent_hint'] ?? 'auto' ) ),
				'media_hint'  => sanitize_key( (string) ( $input['media_hint'] ?? 'auto' ) ),
				'style_hint'  => sanitize_key( (string) ( $input['style_hint'] ?? 'auto' ) ),
			)
		);
		if ( is_wp_error( $route_response ) ) {
			return $route_response;
		}

		$route_data = is_array( $route_response['data'] ?? null ) ? $route_response['data'] : array();
		$route      = is_array( $route_data['route'] ?? null ) ? $route_data['route'] : array();
		$route_name = sanitize_key( (string) ( $route['route'] ?? '' ) );
		if ( empty( $route['supported'] ) ) {
			return $this->gutenberg_composer_blocked_response( $prompt, $route, array( 'route_unsupported' ) );
		}
		$composer_profile_id = $this->gutenberg_composer_resolve_profile_id(
			sanitize_key( (string) ( $input['composer_profile_id'] ?? 'auto' ) ),
			$route,
			$prompt
		);
		$composer_profile = $this->gutenberg_composer_profile_excerpt( $composer_profile_id );

		$case = array(
			'id'         => 'composer_request',
			'prompt'     => $prompt,
			'plan_input' => $this->gutenberg_composer_apply_profile_plan_defaults(
				$route_name,
				is_array( $input['plan_input'] ?? null ) ? $input['plan_input'] : array(),
				$composer_profile
			),
		);
		$media_fixture = $this->gutenberg_recipe_eval_media_fixture( $input['media_fixture'] ?? array() );
		$repair_once   = ! array_key_exists( 'repair_once', $input ) || ! empty( $input['repair_once'] );

		$initial_response = $this->gutenberg_recipe_eval_build_plan( $route, $case, $media_fixture );
		if ( is_wp_error( $initial_response ) ) {
			return $initial_response;
		}
		$initial_plan   = is_array( $initial_response['data'] ?? null ) ? $initial_response['data'] : array();
		$initial_review = $this->gutenberg_composer_plan_review( $initial_plan, $route_name, $case['plan_input'] );

		$applied_repairs = array();
		$final_response  = $initial_response;
		$final_plan      = $initial_plan;
		$final_review    = $initial_review;

		if ( $repair_once && ! empty( $initial_review['needs_revision'] ) ) {
			$repair = $this->gutenberg_composer_repair_plan_input( $route_name, $case['plan_input'], $initial_review );
			$applied_repairs = $repair['applied_repairs'];
			if ( ! empty( $applied_repairs ) ) {
				$case['plan_input'] = $repair['plan_input'];
				$final_response     = $this->gutenberg_recipe_eval_build_plan( $route, $case, $media_fixture );
				if ( is_wp_error( $final_response ) ) {
					return $final_response;
				}
				$final_plan   = is_array( $final_response['data'] ?? null ) ? $final_response['data'] : array();
				$final_review = $this->gutenberg_composer_plan_review( $final_plan, $route_name, $case['plan_input'] );
			}
		}

		$proposal_allowed = empty( $final_review['needs_revision'] ) && ! empty( $final_review['ready_for_proposal'] );

		return $this->build_analysis_success_response(
			array(
				'artifact_type'          => 'gutenberg_composer_repair_loop',
				'version'                => 1,
				'loop_id'                => 'gutenberg_composer_repair_loop_v1',
				'prompt_excerpt'         => $this->content_intent_excerpt( $prompt ),
				'prompt_is_authorization' => false,
				'route'                  => $route,
				'block_capability_catalog_ability_id' => 'npcink-abilities-toolkit/get-gutenberg-block-capability-catalog',
				'block_capability_catalog_id' => 'gutenberg_native_v1',
				'composer_profile_catalog_id' => 'gutenberg_composer_profiles_v1',
				'composer_profile_id'  => $composer_profile_id,
				'composer_profile'     => $composer_profile,
				'composer_instruction'   => $this->gutenberg_block_composer_instruction( $this->content_intent_catalog_surface( $route ) ),
				'recommended_composer_flow' => $this->gutenberg_block_recommended_composer_flow(),
				'repair_policy'          => array(
					'max_repair_passes'       => 1,
					'core_html_policy'        => 'fail_closed',
					'non_core_block_policy'   => 'fail_closed',
					'custom_css_policy'       => 'fail_closed',
					'missing_media_policy'    => 'fallback_or_request_reviewed_media_before_proposal',
					'missing_alt_policy'      => 'fill_review_alt_before_proposal',
					'section_monotony_policy' => 'switch_bounded_section_variant_or_color_story',
				),
				'initial_review'         => $initial_review,
				'final_review'           => $final_review,
				'applied_repairs'        => $applied_repairs,
				'loop_status'            => $proposal_allowed ? 'pass' : 'needs_revision',
				'proposal_allowed'       => $proposal_allowed,
				'proposal_created'       => false,
				'direct_wordpress_write' => false,
				'commit_execution'       => false,
				'plan'                   => $proposal_allowed ? $final_plan : array(),
				'blocked_plan'           => $proposal_allowed ? array() : $final_plan,
				'next_steps'             => $proposal_allowed ? array( 'submit_core_proposal_from_plan' ) : array( 'request_missing_inputs_or_manual_review' ),
			),
			array(
				'source'         => 'local_gutenberg_composer_repair_loop',
				'execution_mode' => 'deterministic_readonly',
			),
			'Gutenberg composer repair loop completed.'
		);
	}

	/**
	 * Resolves an explicit or automatic composer profile id.
	 *
	 * @param string              $requested_profile_id Requested profile id.
	 * @param array<string,mixed> $route Route.
	 * @param string              $prompt Prompt.
	 * @return string
	 */
	private function gutenberg_composer_resolve_profile_id( string $requested_profile_id, array $route, string $prompt ): string {
		$requested_profile_id = sanitize_key( $requested_profile_id );
		if ( '' !== $requested_profile_id && 'auto' !== $requested_profile_id ) {
			$profile = $this->gutenberg_composer_profile( $requested_profile_id );
			if ( ! empty( $profile ) && $this->content_intent_catalog_surface( $route ) === sanitize_key( (string) ( $profile['surface'] ?? '' ) ) ) {
				return $requested_profile_id;
			}
		}

		return $this->gutenberg_composer_profile_id_for_route( $route, $prompt );
	}

	/**
	 * Applies safe profile defaults to the case-specific plan input.
	 *
	 * @param string              $route_name Route name.
	 * @param array<string,mixed> $plan_input Plan input.
	 * @param array<string,mixed> $profile Profile excerpt.
	 * @return array<string,mixed>
	 */
	private function gutenberg_composer_apply_profile_plan_defaults( string $route_name, array $plan_input, array $profile ): array {
		$profile_id = sanitize_key( (string) ( $profile['profile_id'] ?? '' ) );
		$defaults   = is_array( $profile['plan_defaults'] ?? null ) ? $profile['plan_defaults'] : array();
		if ( empty( $defaults ) ) {
			return $plan_input;
		}

		if ( 'pattern_page_plan' === $route_name ) {
			foreach ( array( 'pattern_id', 'color_story', 'section_variant_hints' ) as $key ) {
				if ( ! array_key_exists( $key, $plan_input ) && array_key_exists( $key, $defaults ) ) {
					$plan_input[ $key ] = $defaults[ $key ];
				}
			}
		} elseif ( 'article_block_plan' === $route_name ) {
			if ( ! array_key_exists( 'article_template', $plan_input ) && ! empty( $defaults['article_template'] ) ) {
				$plan_input['article_template'] = (string) $defaults['article_template'];
			}
		} elseif ( 'block_theme_site_plan' === $route_name && 'block_theme_template' === $profile_id ) {
			if ( ! array_key_exists( 'layout_profile', $plan_input ) && ! empty( $defaults['layout_profile'] ) ) {
				$plan_input['layout_profile'] = (string) $defaults['layout_profile'];
			}
		}

		$plan_input['composer_profile_id'] = $profile_id;
		return $plan_input;
	}

	/**
	 * Builds a safe blocked composer response.
	 *
	 * @param string              $prompt Prompt.
	 * @param array<string,mixed> $route Route.
	 * @param string[]            $finding_codes Finding codes.
	 * @return array<string,mixed>
	 */
	private function gutenberg_composer_blocked_response( string $prompt, array $route, array $finding_codes ): array {
		$finding_codes = array_values( array_unique( array_filter( array_map( 'sanitize_key', $finding_codes ) ) ) );
		return $this->build_analysis_success_response(
			array(
				'artifact_type'          => 'gutenberg_composer_repair_loop',
				'version'                => 1,
				'loop_id'                => 'gutenberg_composer_repair_loop_v1',
				'prompt_excerpt'         => $this->content_intent_excerpt( $prompt ),
				'prompt_is_authorization' => false,
				'route'                  => $route,
				'initial_review'         => array(
					'status'             => 'blocked',
					'ready_for_proposal' => false,
					'needs_revision'     => true,
					'finding_codes'      => $finding_codes,
				),
				'final_review'           => array(
					'status'             => 'blocked',
					'ready_for_proposal' => false,
					'needs_revision'     => true,
					'finding_codes'      => $finding_codes,
				),
				'applied_repairs'        => array(),
				'loop_status'            => 'blocked',
				'proposal_allowed'       => false,
				'proposal_created'       => false,
				'direct_wordpress_write' => false,
				'commit_execution'       => false,
				'plan'                   => array(),
				'blocked_plan'           => array(),
				'next_steps'             => array( 'clarify_or_choose_supported_route' ),
			),
			array(
				'source'         => 'local_gutenberg_composer_repair_loop',
				'execution_mode' => 'deterministic_readonly',
			),
			'Gutenberg composer route blocked.'
		);
	}

	/**
	 * Builds compact review metadata for one generated plan.
	 *
	 * @param array<string,mixed> $plan_data Plan data.
	 * @param string              $route_name Route name.
	 * @param array<string,mixed> $raw_plan_input Original or repaired plan input.
	 * @return array<string,mixed>
	 */
	private function gutenberg_composer_plan_review( array $plan_data, string $route_name, array $raw_plan_input ): array {
		$blocks        = $this->gutenberg_recipe_eval_extract_blocks( $plan_data );
		$block_summary = $this->gutenberg_recipe_eval_block_summary( $blocks );
		$plan_summary  = $this->gutenberg_recipe_eval_plan_summary( $plan_data, $block_summary );
		$finding_codes = $this->gutenberg_recipe_eval_plan_failures( $route_name, $plan_data, $block_summary );

		foreach ( $this->gutenberg_composer_quality_finding_codes( $plan_data ) as $code ) {
			$finding_codes[] = $code;
		}
		foreach ( $this->gutenberg_composer_raw_input_finding_codes( $route_name, $raw_plan_input ) as $code ) {
			$finding_codes[] = $code;
		}

		$finding_codes      = $this->gutenberg_composer_filter_finding_codes_by_route(
			array_values( array_unique( array_filter( array_map( 'sanitize_key', $finding_codes ) ) ) ),
			$route_name
		);
		$blocking_findings  = array_values(
			array_intersect(
				$finding_codes,
				array(
					'core_html_detected',
					'non_core_blocks_detected',
					'unexpected_write_ability',
					'direct_wordpress_write_true',
					'commit_execution_true',
					'page_shape_variety_low',
					'page_copy_fit_rejected',
					'article_mobile_stack_missing',
					'template_placement_contract_failed',
					'editor_invalid_block_risk',
					'responsive_risk_detected',
					'image_alt_missing',
					'media_alt_missing',
					'hero_media_missing',
					'missing_reviewed_media',
					'section_variety_low',
					'native_style_density_low',
					'color_story_monochrome',
					'hero_title_too_long',
				)
			)
		);
		$ready_for_proposal = $this->gutenberg_recipe_eval_ready_for_proposal( $plan_data ) && empty( $blocking_findings );

		return array(
			'status'                  => $ready_for_proposal ? 'pass' : 'needs_revision',
			'route'                   => $route_name,
			'ready_for_proposal'      => $ready_for_proposal,
			'needs_revision'          => ! $ready_for_proposal,
			'finding_codes'           => $finding_codes,
			'blocking_finding_codes'  => $blocking_findings,
			'plan_summary'            => $plan_summary,
			'block_summary'           => $block_summary,
			'direct_wordpress_write'  => ! empty( $plan_data['direct_wordpress_write'] ),
			'commit_execution'        => ! empty( $plan_data['commit_execution'] ),
			'proposal_created'        => false,
		);
	}

	/**
	 * Removes page-only review signals from non-page composer routes.
	 *
	 * @param string[] $finding_codes Finding codes.
	 * @param string   $route_name Route name.
	 * @return string[]
	 */
	private function gutenberg_composer_filter_finding_codes_by_route( array $finding_codes, string $route_name ): array {
		if ( 'article_block_plan' !== $route_name ) {
			return $finding_codes;
		}

		$page_only_codes = array(
			'hero_media_missing',
			'bento_grid_missing',
			'comparison_missing',
			'final_cta_missing',
			'section_variety_low',
			'native_style_density_low',
			'color_story_monochrome',
			'page_shape_variety_low',
			'page_copy_fit_rejected',
			'responsive_risk_detected',
		);

		return array_values( array_diff( $finding_codes, $page_only_codes ) );
	}

	/**
	 * Returns quality finding codes exposed by known plan review fields.
	 *
	 * @param array<string,mixed> $plan_data Plan data.
	 * @return string[]
	 */
	private function gutenberg_composer_quality_finding_codes( array $plan_data ): array {
		$codes = array();
		foreach ( array( 'quality_review', 'block_editor_review', 'block_editor_quality_gate', 'revision_strategy' ) as $key ) {
			$section = is_array( $plan_data[ $key ] ?? null ) ? $plan_data[ $key ] : array();
			foreach ( array( 'finding_codes', 'blocking_finding_codes', 'current_finding_codes', 'remaining_finding_codes' ) as $codes_key ) {
				foreach ( is_array( $section[ $codes_key ] ?? null ) ? $section[ $codes_key ] : array() as $code ) {
					$code = sanitize_key( (string) $code );
					if ( '' !== $code ) {
						$codes[] = $code;
					}
				}
			}
		}
		$contract = is_array( $plan_data['composition_contract'] ?? null ) ? $plan_data['composition_contract'] : array();
		if ( 'pass' !== sanitize_key( (string) ( $contract['contract_status'] ?? 'pass' ) ) ) {
			$codes[] = 'composition_contract_failed';
		}
		if ( ! empty( $contract['forbidden_block_names'] ) ) {
			$codes[] = 'forbidden_blocks_detected';
		}
		if ( ! empty( $contract['non_core_blocks'] ) ) {
			$codes[] = 'non_core_blocks_detected';
		}
		if ( (int) ( $contract['media_rules']['media_missing_alt_count'] ?? 0 ) > 0 ) {
			$codes[] = 'image_alt_missing';
		}
		return array_values( array_unique( $codes ) );
	}

	/**
	 * Returns repairable finding codes from caller-supplied plan inputs.
	 *
	 * @param string              $route_name Route name.
	 * @param array<string,mixed> $raw_plan_input Plan input.
	 * @return string[]
	 */
	private function gutenberg_composer_raw_input_finding_codes( string $route_name, array $raw_plan_input ): array {
		$codes     = array();
		$variables = is_array( $raw_plan_input['variables'] ?? null ) ? $raw_plan_input['variables'] : array();
		$title     = (string) ( $variables['hero_title'] ?? ( $raw_plan_input['title'] ?? '' ) );
		if ( '' !== $title && $this->strlen_value( $title ) > 90 ) {
			$codes[] = 'hero_title_too_long';
		}
		$media_strategy = sanitize_key( (string) ( $raw_plan_input['media_strategy'] ?? '' ) );
		$media_url      = (string) ( $variables['hero_media_url'] ?? ( $raw_plan_input['hero_media_url'] ?? '' ) );
		$media_alt      = (string) ( $variables['hero_media_alt'] ?? ( $raw_plan_input['hero_media_alt'] ?? '' ) );
		if ( in_array( $route_name, array( 'pattern_page_plan', 'article_block_plan' ), true ) && 'existing_media_url' === $media_strategy ) {
			if ( '' === trim( $media_url ) ) {
				$codes[] = 'missing_reviewed_media';
			} elseif ( '' === trim( $media_alt ) ) {
				$codes[] = 'media_alt_missing';
			}
		}
		return array_values( array_unique( $codes ) );
	}

	/**
	 * Applies at most one bounded repair pass to plan input.
	 *
	 * @param string              $route_name Route name.
	 * @param array<string,mixed> $plan_input Plan input.
	 * @param array<string,mixed> $review Review metadata.
	 * @return array<string,mixed>
	 */
	private function gutenberg_composer_repair_plan_input( string $route_name, array $plan_input, array $review ): array {
		$finding_codes   = is_array( $review['finding_codes'] ?? null ) ? array_values( array_filter( array_map( 'sanitize_key', $review['finding_codes'] ) ) ) : array();
		$variables       = is_array( $plan_input['variables'] ?? null ) ? $plan_input['variables'] : array();
		$applied_repairs = array();

		if ( in_array( 'hero_title_too_long', $finding_codes, true ) ) {
			$raw_title = (string) ( $variables['hero_title'] ?? ( $plan_input['title'] ?? '' ) );
			if ( '' !== $raw_title ) {
				$short_title = $this->gutenberg_composer_shorten_text( $raw_title, 82 );
				if ( isset( $variables['hero_title'] ) ) {
					$variables['hero_title'] = $short_title;
				} else {
					$plan_input['title'] = $short_title;
				}
				$applied_repairs[] = $this->gutenberg_composer_repair_row( 'shorten_overlong_heading', array( 'hero_title_too_long' ) );
			}
		}

		if ( in_array( 'media_alt_missing', $finding_codes, true ) || in_array( 'image_alt_missing', $finding_codes, true ) ) {
			if ( empty( $variables['hero_media_alt'] ) && ( ! empty( $variables['hero_media_url'] ) || ! empty( $plan_input['hero_media_url'] ) ) ) {
				$variables['hero_media_alt'] = 'Reviewed Gutenberg content visual';
				$applied_repairs[] = $this->gutenberg_composer_repair_row( 'fill_missing_media_alt', array( 'media_alt_missing', 'image_alt_missing' ) );
			}
		}

		if ( in_array( 'missing_reviewed_media', $finding_codes, true ) || in_array( 'hero_media_missing', $finding_codes, true ) ) {
			if ( 'pattern_page_plan' === $route_name ) {
				$plan_input['media_strategy'] = 'mock_or_existing_media';
				$applied_repairs[] = $this->gutenberg_composer_repair_row( 'fallback_to_native_mock_media_panel', array( 'missing_reviewed_media', 'hero_media_missing' ) );
			} elseif ( 'article_block_plan' === $route_name ) {
				$plan_input['media_strategy'] = 'none';
				$applied_repairs[] = $this->gutenberg_composer_repair_row( 'fallback_to_no_media_article_structure', array( 'missing_reviewed_media', 'hero_media_missing' ) );
			}
		}

		if ( 'pattern_page_plan' === $route_name && ( in_array( 'section_variety_low', $finding_codes, true ) || in_array( 'page_shape_variety_low', $finding_codes, true ) || in_array( 'color_story_monochrome', $finding_codes, true ) ) ) {
			if ( empty( $plan_input['color_story'] ) ) {
				$plan_input['color_story'] = 'editorial-accent';
			}
			$plan_input['section_variant_hints'] = array( 'comparison' => 'left-title-two-cards' );
			$applied_repairs[] = $this->gutenberg_composer_repair_row( 'increase_section_shape_variety', array( 'section_variety_low', 'page_shape_variety_low', 'color_story_monochrome' ) );
		}

		$plan_input['variables'] = $variables;

		return array(
			'plan_input'       => $plan_input,
			'applied_repairs'  => $applied_repairs,
			'applied_repair_codes' => array_values(
				array_map(
					static function ( $repair ) {
						return is_array( $repair ) ? (string) ( $repair['repair_code'] ?? '' ) : '';
					},
					$applied_repairs
				)
			),
		);
	}

	/**
	 * Builds one repair row.
	 *
	 * @param string   $repair_code Repair code.
	 * @param string[] $finding_codes Finding codes.
	 * @return array<string,mixed>
	 */
	private function gutenberg_composer_repair_row( string $repair_code, array $finding_codes ): array {
		return array(
			'repair_code'   => sanitize_key( $repair_code ),
			'finding_codes' => array_values( array_unique( array_filter( array_map( 'sanitize_key', $finding_codes ) ) ) ),
			'applied'       => true,
		);
	}

	/**
	 * Shortens a title without requiring viewport-scaled typography.
	 *
	 * @param string $text Text.
	 * @param int    $max_chars Max chars.
	 * @return string
	 */
	private function gutenberg_composer_shorten_text( string $text, int $max_chars ): string {
		$text = trim( preg_replace( '/\s+/', ' ', $text ) ?? $text );
		if ( $this->strlen_value( $text ) <= $max_chars ) {
			return $text;
		}
		return rtrim( $this->substr_value( $text, 0, max( 12, $max_chars ) ), " \t\n\r\0\x0B,.;:，。；：" );
	}
}
