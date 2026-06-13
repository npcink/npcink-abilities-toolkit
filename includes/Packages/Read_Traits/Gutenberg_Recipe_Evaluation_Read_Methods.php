<?php
/**
 * Gutenberg recipe evaluation methods for Core_Read_Package.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Evaluates natural-language Gutenberg recipe routes without writing content.
 */
trait Gutenberg_Recipe_Evaluation_Read_Methods {
	/**
	 * Evaluates a batch of natural-language prompts against the governed Gutenberg recipe gates.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function evaluate_gutenberg_recipe_suite( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to evaluate Gutenberg recipes.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$cases             = $this->gutenberg_recipe_eval_cases( $input );
		$minimum_pass_rate = $this->gutenberg_recipe_eval_float( $input['minimum_pass_rate'] ?? 0.8, 0.8, 0, 1 );
		$include_details   = ! array_key_exists( 'include_case_details', $input ) || ! empty( $input['include_case_details'] );
		$media_fixture     = $this->gutenberg_recipe_eval_media_fixture( $input['media_fixture'] ?? array() );
		$case_results      = array();
		$pass_count        = 0;

		foreach ( $cases as $index => $case ) {
			$result = $this->gutenberg_recipe_eval_case( $case, $index, $media_fixture );
			if ( ! empty( $result['passed'] ) ) {
				++$pass_count;
			}
			$case_results[] = $include_details ? $result : $this->gutenberg_recipe_eval_case_excerpt( $result );
		}

		$total     = count( $case_results );
		$pass_rate = $total > 0 ? round( $pass_count / $total, 4 ) : 0;
		$failed    = max( 0, $total - $pass_count );
		$status    = $total > 0 && $pass_rate >= $minimum_pass_rate ? 'pass' : 'needs_attention';

		return $this->build_analysis_success_response(
			array(
				'artifact_type'          => 'gutenberg_recipe_suite_evaluation',
				'version'                => 1,
				'evaluation_mode'        => 'route_and_plan_only',
				'write_posture'          => 'read_only_quality_gate',
				'direct_wordpress_write' => false,
				'commit_execution'       => false,
				'proposal_created'       => false,
				'minimum_pass_rate'      => $minimum_pass_rate,
				'suite_status'           => $status,
				'summary'                => array(
					'total_cases'      => $total,
					'passed_cases'     => $pass_count,
					'failed_cases'     => $failed,
					'pass_rate'        => $pass_rate,
					'ready_for_pilot'  => 'pass' === $status,
					'recommended_next_step' => 'pass' === $status ? 'submit_sample_proposals_for_human_review' : 'fix_top_failure_class',
				),
				'failure_summary'        => $this->gutenberg_recipe_eval_failure_summary( $case_results ),
				'cases'                  => $case_results,
				'review_contract'        => $this->gutenberg_recipe_eval_review_contract(),
				'dual_review'            => $this->gutenberg_recipe_eval_suite_dual_review( $case_results, $status ),
				'guardrails'             => array(
					'allowed_plan_ability_ids' => array(
						'npcink-abilities-toolkit/build-pattern-page-plan',
						'npcink-abilities-toolkit/build-article-block-plan',
						'npcink-abilities-toolkit/build-block-theme-site-plan',
					),
					'allowed_final_write_ability_ids' => array(
						'npcink-abilities-toolkit/create-draft',
						'npcink-abilities-toolkit/update-post-blocks',
						'npcink-abilities-toolkit/update-template-blocks',
						'npcink-abilities-toolkit/upsert-template-blocks',
					),
					'core_html_allowed'      => false,
					'non_core_blocks_allowed' => false,
					'custom_css_allowed'     => false,
				),
			),
			array(
				'source'         => 'local_gutenberg_recipe_evaluation',
				'execution_mode' => 'deterministic',
			),
			'Gutenberg recipe suite evaluated.'
		);
	}

	/**
	 * Returns evaluation cases from input or the built-in smoke suite.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<int,array<string,mixed>>
	 */
	private function gutenberg_recipe_eval_cases( array $input ) {
		$raw_cases = array();
		if ( is_array( $input['cases'] ?? null ) ) {
			$raw_cases = $input['cases'];
		} elseif ( is_array( $input['prompts'] ?? null ) ) {
			foreach ( $input['prompts'] as $prompt ) {
				$raw_cases[] = array( 'prompt' => $prompt );
			}
		}

		if ( empty( $raw_cases ) ) {
			$raw_cases = array(
				array(
					'id'             => 'page_media_landing',
					'prompt'         => '帮我做一个现代官网介绍页，需要配图，手机端也要好看。',
					'expected_route' => 'pattern_page_plan',
				),
				array(
					'id'             => 'page_long_title',
					'prompt'         => '用 Gutenberg 原生块搭出现代官网介绍页，长标题也保持舒展清晰。',
					'expected_route' => 'pattern_page_plan',
				),
				array(
					'id'             => 'page_saas_homepage',
					'prompt'         => '做一个 SaaS 产品首页，突出核心能力、客户价值和移动端体验。',
					'expected_route' => 'pattern_page_plan',
				),
				array(
					'id'             => 'page_editorial_accent',
					'prompt'         => '帮我做一个有色彩强调的 editorial-accent 官网落地页。',
					'expected_route' => 'pattern_page_plan',
				),
				array(
					'id'             => 'page_no_media',
					'prompt'         => '创建一个服务介绍页面，先不要配图。',
					'expected_route' => 'pattern_page_plan',
				),
				array(
					'id'             => 'page_plugin_feature_overview',
					'prompt'         => '帮我搭一个 WordPress 插件功能介绍页面，结构清楚，适合客户浏览。',
					'expected_route' => 'pattern_page_plan',
				),
				array(
					'id'             => 'page_mobile_first_home',
					'prompt'         => '给产品做一个移动端优先的首页，标题不要挤，内容要能扫读。',
					'expected_route' => 'pattern_page_plan',
				),
				array(
					'id'             => 'article_with_media',
					'prompt'         => '写一篇介绍 Gutenberg 模块红利的文章草稿，需要配图和 FAQ。',
					'expected_route' => 'article_block_plan',
				),
				array(
					'id'             => 'article_comparison',
					'prompt'         => '写一篇对比评测文章，说明普通 AI 直接写入和 proposal-first 的区别。',
					'expected_route' => 'article_block_plan',
				),
				array(
					'id'             => 'article_how_to',
					'prompt'         => '写一篇教程，说明如何用 Gutenberg 块组织一篇长文。',
					'expected_route' => 'article_block_plan',
				),
				array(
					'id'             => 'article_blog_longform',
					'prompt'         => '帮我写一篇博客长文，主题是 WordPress 内容治理。',
					'expected_route' => 'article_block_plan',
				),
				array(
					'id'             => 'article_no_media',
					'prompt'         => '写一篇文章草稿，先不要图片，只要结构清晰。',
					'expected_route' => 'article_block_plan',
				),
				array(
					'id'             => 'article_page_vs_post_governance',
					'prompt'         => '写一篇博客文章，解释内容编辑为什么要经过 proposal 审核。',
					'expected_route' => 'article_block_plan',
				),
				array(
					'id'             => 'article_block_theme_lessons',
					'prompt'         => '写一篇文章复盘导航路径体验问题，以及 AI 应该如何检查发布前质量。',
					'expected_route' => 'article_block_plan',
				),
				array(
					'id'             => 'site_breadcrumbs_single_cn',
					'prompt'         => '给文章模板加面包屑导航。',
					'expected_route' => 'block_theme_site_plan',
				),
				array(
					'id'             => 'site_breadcrumbs_single_en',
					'prompt'         => 'Add breadcrumbs to the single template.',
					'expected_route' => 'block_theme_site_plan',
				),
				array(
					'id'             => 'site_breadcrumbs_blog_template',
					'prompt'         => '博客文章模板加面包屑导航。',
					'expected_route' => 'block_theme_site_plan',
				),
				array(
					'id'             => 'site_breadcrumbs_front_page_and_page_cn',
					'prompt'         => '把首页和页面的面包屑处理好，不要出现在页眉上方，检查下。',
					'expected_route' => 'block_theme_site_plan',
				),
				array(
					'id'             => 'site_breadcrumbs_page_position_cn',
					'prompt'         => '页面的面包屑不要在页眉上方，要放在标题附近。',
					'expected_route' => 'block_theme_site_plan',
				),
				array(
					'id'             => 'site_breadcrumbs_all_content_cn',
					'prompt'         => '文章页和普通页面都加面包屑，首页不要显示。',
					'expected_route' => 'block_theme_site_plan',
				),
				array(
					'id'             => 'site_breadcrumbs_page_templates_en',
					'prompt'         => 'Add breadcrumbs to page templates and keep them near the title, not above the header.',
					'expected_route' => 'block_theme_site_plan',
				),
				array(
					'id'                 => 'navigation_fail_closed',
					'prompt'             => '帮我直接改站点导航菜单。',
					'expected_route'     => 'unsupported',
					'expected_supported' => false,
				),
				array(
					'id'                 => 'global_styles_fail_closed',
					'prompt'             => 'Change global styles and write a theme.json color patch.',
					'expected_route'     => 'unsupported',
					'expected_supported' => false,
				),
				array(
					'id'                 => 'custom_html_fail_closed',
					'prompt'             => '直接执行一个 custom HTML template change。',
					'expected_route'     => 'unsupported',
					'expected_supported' => false,
				),
				array(
					'id'                 => 'template_part_fail_closed',
					'prompt'             => '帮我重做页眉模板部件。',
					'expected_route'     => 'unsupported',
					'expected_supported' => false,
				),
				array(
					'id'                 => 'ambiguous_page_article_fail_closed',
					'prompt'             => '帮我做一个页面文章草稿。',
					'expected_route'     => 'unsupported',
					'expected_supported' => false,
				),
				array(
					'id'                 => 'navigation_block_fail_closed',
					'prompt'             => 'Update the wp_navigation block for the main menu.',
					'expected_route'     => 'unsupported',
					'expected_supported' => false,
				),
				array(
					'id'                 => 'template_part_footer_fail_closed',
					'prompt'             => 'Edit the footer template part and add a newsletter signup.',
					'expected_route'     => 'unsupported',
					'expected_supported' => false,
				),
				array(
					'id'                 => 'navigation_breadcrumb_conflict_fail_closed',
					'prompt'             => '把导航菜单改成面包屑样式，并更新主导航。',
					'expected_route'     => 'unsupported',
					'expected_supported' => false,
				),
				array(
					'id'                 => 'raw_template_html_fail_closed',
					'prompt'             => '用 raw HTML 直接改文章模板，把结构和样式一次写进去。',
					'expected_route'     => 'unsupported',
					'expected_supported' => false,
				),
			);
		}

		$cases = array();
		foreach ( $raw_cases as $index => $raw_case ) {
			if ( is_string( $raw_case ) ) {
				$raw_case = array( 'prompt' => $raw_case );
			}
			if ( ! is_array( $raw_case ) ) {
				continue;
			}
			$prompt = $this->content_intent_text( $raw_case['prompt'] ?? '' );
			if ( '' === $prompt ) {
				continue;
			}
			$cases[] = array(
				'id'                 => sanitize_key( (string) ( $raw_case['id'] ?? 'case_' . ( $index + 1 ) ) ),
				'prompt'             => $prompt,
				'expected_route'     => sanitize_key( (string) ( $raw_case['expected_route'] ?? '' ) ),
				'expected_supported' => array_key_exists( 'expected_supported', $raw_case ) ? (bool) $raw_case['expected_supported'] : null,
				'hints'              => is_array( $raw_case['hints'] ?? null ) ? $raw_case['hints'] : array(),
				'plan_input'         => is_array( $raw_case['plan_input'] ?? null ) ? $raw_case['plan_input'] : array(),
			);
		}

		return array_slice( $cases, 0, 30 );
	}

	/**
	 * Evaluates one prompt.
	 *
	 * @param array<string,mixed> $case Case.
	 * @param int                 $index Case index.
	 * @param array<string,mixed> $media_fixture Media fixture.
	 * @return array<string,mixed>
	 */
	private function gutenberg_recipe_eval_case( array $case, $index, array $media_fixture ) {
		$route_input = array_merge(
			array( 'prompt' => (string) ( $case['prompt'] ?? '' ) ),
			$this->gutenberg_recipe_eval_route_hints( $case['hints'] ?? array() )
		);
		$route_response = $this->route_content_intent( $route_input );
		$failures       = array();
		$plan_summary   = array();
		$block_summary  = array(
			'core_html_count'  => 0,
			'non_core_blocks'  => array(),
			'total_blocks'     => 0,
		);

		if ( is_wp_error( $route_response ) ) {
			return array(
				'id'             => (string) ( $case['id'] ?? 'case_' . ( $index + 1 ) ),
				'prompt_excerpt' => $this->content_intent_excerpt( (string) ( $case['prompt'] ?? '' ) ),
				'passed'         => false,
				'route'          => 'error',
				'failure_codes'  => array( $route_response->get_error_code() ),
				'dual_review'    => $this->gutenberg_recipe_eval_case_dual_review( array( $route_response->get_error_code() ), array() ),
			);
		}

		$route_data = is_array( $route_response['data'] ?? null ) ? $route_response['data'] : array();
		$route      = is_array( $route_data['route'] ?? null ) ? $route_data['route'] : array();
		$route_name = sanitize_key( (string) ( $route['route'] ?? '' ) );
		$supported  = ! empty( $route['supported'] );

		$expected_supported = $case['expected_supported'];
		if ( null !== $expected_supported && (bool) $expected_supported !== $supported ) {
			$failures[] = 'route_supported_mismatch';
		}
		if ( '' !== (string) $case['expected_route'] && (string) $case['expected_route'] !== $route_name ) {
			$failures[] = 'route_mismatch';
		}
		if ( ! empty( $route_data['write_actions'] ) ) {
			$failures[] = 'router_emitted_write_actions';
		}

		if ( ! $supported ) {
			if ( false !== $expected_supported ) {
				$failures[] = 'unsupported_route';
			}
			return array(
				'id'             => (string) ( $case['id'] ?? 'case_' . ( $index + 1 ) ),
				'prompt_excerpt' => $this->content_intent_excerpt( (string) ( $case['prompt'] ?? '' ) ),
				'passed'         => empty( $failures ),
				'route'          => $route_name,
				'route_key'      => sanitize_key( (string) ( $route['route_key'] ?? '' ) ),
				'supported'      => false,
				'needs_clarification' => ! empty( $route['needs_clarification'] ),
				'failure_codes'  => array_values( array_unique( $failures ) ),
				'plan_summary'   => array(),
				'block_summary'  => $block_summary,
				'dual_review'    => $this->gutenberg_recipe_eval_case_dual_review( $failures, array() ),
			);
		}

		$plan_response = $this->gutenberg_recipe_eval_build_plan( $route, $case, $media_fixture );
		if ( is_wp_error( $plan_response ) ) {
			$failures[] = $plan_response->get_error_code();
		} else {
			$plan_data     = is_array( $plan_response['data'] ?? null ) ? $plan_response['data'] : array();
			$blocks        = $this->gutenberg_recipe_eval_extract_blocks( $plan_data );
			$block_summary = $this->gutenberg_recipe_eval_block_summary( $blocks );
			$plan_summary  = $this->gutenberg_recipe_eval_plan_summary( $plan_data, $block_summary );
			$failures      = array_merge( $failures, $this->gutenberg_recipe_eval_plan_failures( $route_name, $plan_data, $block_summary ) );
		}

		return array(
			'id'             => (string) ( $case['id'] ?? 'case_' . ( $index + 1 ) ),
			'prompt_excerpt' => $this->content_intent_excerpt( (string) ( $case['prompt'] ?? '' ) ),
			'passed'         => empty( $failures ),
			'route'          => $route_name,
			'route_key'      => sanitize_key( (string) ( $route['route_key'] ?? '' ) ),
			'supported'      => true,
			'plan_ability_id' => sanitize_text_field( (string) ( $route['plan_ability_id'] ?? '' ) ),
			'failure_codes'  => array_values( array_unique( array_filter( $failures ) ) ),
			'plan_summary'   => $plan_summary,
			'block_summary'  => $block_summary,
			'dual_review'    => $this->gutenberg_recipe_eval_case_dual_review( $failures, $plan_summary ),
		);
	}

	/**
	 * Builds a plan for a routed case.
	 *
	 * @param array<string,mixed> $route Route.
	 * @param array<string,mixed> $case Case.
	 * @param array<string,mixed> $media_fixture Media fixture.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function gutenberg_recipe_eval_build_plan( array $route, array $case, array $media_fixture ) {
		$plan_input = is_array( $route['recommended_plan_input'] ?? null ) ? $route['recommended_plan_input'] : array();
		$plan_input = array_merge( $plan_input, is_array( $case['plan_input'] ?? null ) ? $case['plan_input'] : array() );
		$plan_input = $this->gutenberg_recipe_eval_apply_media_fixture( $plan_input, (string) ( $route['route'] ?? '' ), $media_fixture );
		$route_name = (string) ( $route['route'] ?? '' );

		if ( 'pattern_page_plan' === $route_name ) {
			$plan_input['pattern_id'] = (string) ( $plan_input['pattern_id'] ?? 'openai-style-landing' );
			return $this->build_pattern_page_plan( $plan_input );
		}
		if ( 'article_block_plan' === $route_name ) {
			$plan_input['title']            = (string) ( $plan_input['title'] ?? 'Gutenberg Article Draft' );
			$plan_input['article_template'] = (string) ( $plan_input['article_template'] ?? 'comparison-review' );
			return $this->build_article_block_plan( $plan_input );
		}
		if ( 'block_theme_site_plan' === $route_name ) {
			$plan_input['intent'] = (string) ( $plan_input['intent'] ?? 'add_breadcrumbs' );
			return $this->build_block_theme_site_plan( $plan_input );
		}

		return new \WP_Error( 'npcink_abilities_toolkit_recipe_eval_route_not_supported', __( 'The routed recipe is not supported by the evaluator.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
	}

	/**
	 * Returns allowed route hints.
	 *
	 * @param mixed $hints Hints.
	 * @return array<string,string>
	 */
	private function gutenberg_recipe_eval_route_hints( $hints ) {
		$hints   = is_array( $hints ) ? $hints : array();
		$allowed = array( 'target_hint', 'intent_hint', 'media_hint', 'style_hint' );
		$out     = array();
		foreach ( $allowed as $key ) {
			if ( isset( $hints[ $key ] ) ) {
				$out[ $key ] = sanitize_key( (string) $hints[ $key ] );
			}
		}
		return $out;
	}

	/**
	 * Applies a reviewed media fixture to post-content plans.
	 *
	 * @param array<string,mixed> $plan_input Plan input.
	 * @param string              $route_name Route name.
	 * @param array<string,mixed> $media_fixture Fixture.
	 * @return array<string,mixed>
	 */
	private function gutenberg_recipe_eval_apply_media_fixture( array $plan_input, $route_name, array $media_fixture ) {
		if ( empty( $media_fixture['url'] ) || ! in_array( $route_name, array( 'pattern_page_plan', 'article_block_plan' ), true ) ) {
			return $plan_input;
		}
		$variables = is_array( $plan_input['variables'] ?? null ) ? $plan_input['variables'] : array();
		if ( empty( $variables['hero_media_url'] ) ) {
			$variables['hero_media_url'] = (string) $media_fixture['url'];
		}
		if ( empty( $variables['hero_media_attachment_id'] ) && ! empty( $media_fixture['attachment_id'] ) ) {
			$variables['hero_media_attachment_id'] = absint( $media_fixture['attachment_id'] );
		}
		if ( empty( $variables['hero_media_alt'] ) && ! empty( $media_fixture['alt'] ) ) {
			$variables['hero_media_alt'] = (string) $media_fixture['alt'];
		}
		$plan_input['variables']      = $variables;
		$plan_input['media_strategy'] = 'existing_media_url';
		return $plan_input;
	}

	/**
	 * Extracts plan blocks from Core-ready write actions.
	 *
	 * @param array<string,mixed> $plan_data Plan data.
	 * @return array<int,array<string,mixed>>
	 */
	private function gutenberg_recipe_eval_extract_blocks( array $plan_data ) {
		$actions = is_array( $plan_data['write_actions'] ?? null ) ? $plan_data['write_actions'] : array();
		$blocks  = array();
		foreach ( $actions as $action ) {
			if ( ! is_array( $action ) ) {
				continue;
			}
			$action_blocks = is_array( $action['input']['blocks'] ?? null ) ? $action['input']['blocks'] : array();
			if ( ! empty( $action_blocks ) ) {
				$blocks = array_merge( $blocks, $action_blocks );
			}
		}
		return $blocks;
	}

	/**
	 * Returns a compact plan summary.
	 *
	 * @param array<string,mixed> $plan_data Plan data.
	 * @param array<string,mixed> $block_summary Block summary.
	 * @return array<string,mixed>
	 */
	private function gutenberg_recipe_eval_plan_summary( array $plan_data, array $block_summary ) {
		$actions = is_array( $plan_data['write_actions'] ?? null ) ? $plan_data['write_actions'] : array();
		$summary = array(
			'artifact_type'          => sanitize_key( (string) ( $plan_data['artifact_type'] ?? '' ) ),
			'proposal_mode'          => sanitize_key( (string) ( $plan_data['proposal_mode'] ?? '' ) ),
			'direct_wordpress_write' => ! empty( $plan_data['direct_wordpress_write'] ),
			'commit_execution'       => ! empty( $plan_data['commit_execution'] ),
			'requires_approval'      => ! empty( $plan_data['requires_approval'] ),
			'action_count'           => count( $actions ),
			'write_ability_ids'      => $this->gutenberg_recipe_eval_write_ability_ids( $actions ),
			'block_count'            => absint( $block_summary['total_blocks'] ?? 0 ),
			'ready_for_proposal'     => $this->gutenberg_recipe_eval_ready_for_proposal( $plan_data ),
			'quality_gate_status'    => sanitize_key( (string) ( $plan_data['block_editor_quality_gate']['recommended_next_step'] ?? ( $plan_data['quality_review']['review_status'] ?? '' ) ) ),
		);

		$no_change_context = $this->gutenberg_recipe_eval_no_change_context( $plan_data );
		if ( ! empty( $no_change_context['no_change_count'] ) ) {
			$summary['no_change_context'] = $no_change_context;
		}

		return $summary;
	}

	/**
	 * Returns compact no-op evidence for plans where the target already matches.
	 *
	 * @param array<string,mixed> $plan_data Plan data.
	 * @return array<string,mixed>
	 */
	private function gutenberg_recipe_eval_no_change_context( array $plan_data ) {
		$preview = is_array( $plan_data['preview'] ?? null ) ? $plan_data['preview'] : array();
		if ( empty( $preview ) ) {
			return array(
				'no_change_count'   => 0,
				'no_change_reasons' => array(),
				'previews'          => array(),
			);
		}

		$reasons  = array();
		$previews = array();
		foreach ( $preview as $row ) {
			if ( ! is_array( $row ) || ! array_key_exists( 'requires_write', $row ) || ! empty( $row['requires_write'] ) ) {
				continue;
			}
			$reason = sanitize_key( (string) ( $row['no_change_reason'] ?? '' ) );
			if ( '' === $reason ) {
				continue;
			}

			$placement  = is_array( $row['breadcrumb_placement'] ?? null ) ? $row['breadcrumb_placement'] : array();
			$resolution = is_array( $row['template_resolution'] ?? null ) ? $row['template_resolution'] : array();
			$reasons[]  = $reason;
			$previews[] = array(
				'slug'                => sanitize_key( (string) ( $row['slug'] ?? '' ) ),
				'requires_write'      => false,
				'no_change_reason'    => $reason,
				'breadcrumb_status'   => sanitize_key( (string) ( $placement['status'] ?? '' ) ),
				'breadcrumb_strategy' => sanitize_key( (string) ( $placement['strategy'] ?? '' ) ),
				'requested_slug'      => sanitize_key( (string) ( $resolution['requested_slug'] ?? '' ) ),
				'source_slug'         => sanitize_key( (string) ( $resolution['source_slug'] ?? '' ) ),
			);
		}

		return array(
			'no_change_count'   => count( $previews ),
			'no_change_reasons' => array_values( array_unique( array_filter( $reasons ) ) ),
			'previews'          => array_slice( $previews, 0, 5 ),
		);
	}

	/**
	 * Returns plan failure codes.
	 *
	 * @param string              $route_name Route name.
	 * @param array<string,mixed> $plan_data Plan data.
	 * @param array<string,mixed> $block_summary Block summary.
	 * @return string[]
	 */
	private function gutenberg_recipe_eval_plan_failures( $route_name, array $plan_data, array $block_summary ) {
		$failures = array();
		$actions  = is_array( $plan_data['write_actions'] ?? null ) ? $plan_data['write_actions'] : array();
		if ( ! empty( $plan_data['direct_wordpress_write'] ) ) {
			$failures[] = 'direct_wordpress_write_true';
		}
		if ( ! empty( $plan_data['commit_execution'] ) ) {
			$failures[] = 'commit_execution_true';
		}
		if ( (int) ( $block_summary['core_html_count'] ?? 0 ) > 0 ) {
			$failures[] = 'core_html_detected';
		}
		if ( ! empty( $block_summary['non_core_blocks'] ) ) {
			$failures[] = 'non_core_blocks_detected';
		}
		$unexpected_write_ids = array_diff( $this->gutenberg_recipe_eval_write_ability_ids( $actions ), $this->gutenberg_recipe_eval_allowed_write_ids( $route_name ) );
		if ( ! empty( $unexpected_write_ids ) ) {
			$failures[] = 'unexpected_write_ability';
		}
		if ( 'pattern_page_plan' === $route_name ) {
			$design_quality = is_array( $plan_data['design_quality'] ?? null ) ? $plan_data['design_quality'] : array();
			if ( (int) ( $design_quality['section_shape_variety'] ?? 0 ) < 4 ) {
				$failures[] = 'page_shape_variety_low';
			}
			if ( ! empty( $plan_data['copy_quality'] ) && 'rejected' === sanitize_key( (string) ( $plan_data['copy_quality']['hero_title_fit'] ?? '' ) ) ) {
				$failures[] = 'page_copy_fit_rejected';
			}
		}
		if ( 'article_block_plan' === $route_name ) {
			if ( empty( $plan_data['responsive_quality']['uses_mobile_stack'] ) ) {
				$failures[] = 'article_mobile_stack_missing';
			}
		}
		if ( 'block_theme_site_plan' === $route_name && empty( $actions ) && 'no_changes_required' !== sanitize_key( (string) ( $plan_data['block_editor_quality_gate']['recommended_next_step'] ?? '' ) ) ) {
			$failures[] = 'template_plan_no_actions_without_noop_status';
		}
		return $failures;
	}

	/**
	 * Returns block summary.
	 *
	 * @param array<int,array<string,mixed>> $blocks Blocks.
	 * @return array<string,mixed>
	 */
	private function gutenberg_recipe_eval_block_summary( array $blocks ) {
		$summary = array(
			'total_blocks'     => 0,
			'core_html_count'  => 0,
			'non_core_blocks'  => array(),
			'columns_count'    => 0,
			'columns_mobile_stack_ok' => true,
		);
		$this->gutenberg_recipe_eval_walk_blocks( $blocks, $summary );
		$summary['non_core_blocks'] = array_values( array_unique( $summary['non_core_blocks'] ) );
		return $summary;
	}

	/**
	 * Walks block trees and updates a summary.
	 *
	 * @param array<int,mixed>     $blocks Blocks.
	 * @param array<string,mixed> $summary Summary.
	 * @return void
	 */
	private function gutenberg_recipe_eval_walk_blocks( array $blocks, array &$summary ) {
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			$block_name = (string) ( $block['blockName'] ?? '' );
			if ( '' === $block_name ) {
				continue;
			}
			++$summary['total_blocks'];
			if ( 'core/html' === $block_name ) {
				++$summary['core_html_count'];
			}
			if ( 0 !== strpos( $block_name, 'core/' ) ) {
				$summary['non_core_blocks'][] = $block_name;
			}
			if ( 'core/columns' === $block_name ) {
				++$summary['columns_count'];
				if ( false === ( $block['attrs']['isStackedOnMobile'] ?? true ) ) {
					$summary['columns_mobile_stack_ok'] = false;
				}
			}
			$this->gutenberg_recipe_eval_walk_blocks( is_array( $block['innerBlocks'] ?? null ) ? $block['innerBlocks'] : array(), $summary );
		}
	}

	/**
	 * Returns unique target write ability ids.
	 *
	 * @param array<int,mixed> $actions Actions.
	 * @return string[]
	 */
	private function gutenberg_recipe_eval_write_ability_ids( array $actions ) {
		$ids = array();
		foreach ( $actions as $action ) {
			if ( is_array( $action ) && isset( $action['target_ability_id'] ) ) {
				$ids[] = (string) $action['target_ability_id'];
			}
		}
		return array_values( array_unique( array_filter( $ids ) ) );
	}

	/**
	 * Returns allowed final write abilities for a route.
	 *
	 * @param string $route_name Route.
	 * @return string[]
	 */
	private function gutenberg_recipe_eval_allowed_write_ids( $route_name ) {
		if ( 'block_theme_site_plan' === $route_name ) {
			return array( 'npcink-abilities-toolkit/update-template-blocks', 'npcink-abilities-toolkit/upsert-template-blocks' );
		}
		return array( 'npcink-abilities-toolkit/create-draft', 'npcink-abilities-toolkit/update-post-blocks' );
	}

	/**
	 * Returns ready-for-proposal status from known plan gates.
	 *
	 * @param array<string,mixed> $plan_data Plan data.
	 * @return bool
	 */
	private function gutenberg_recipe_eval_ready_for_proposal( array $plan_data ) {
		if ( isset( $plan_data['block_editor_quality_gate']['ready_for_proposal'] ) ) {
			return ! empty( $plan_data['block_editor_quality_gate']['ready_for_proposal'] );
		}
		if ( isset( $plan_data['revision_strategy']['ready_for_proposal'] ) ) {
			return ! empty( $plan_data['revision_strategy']['ready_for_proposal'] );
		}
		if ( isset( $plan_data['quality_review']['review_status'] ) ) {
			return 'pass' === sanitize_key( (string) $plan_data['quality_review']['review_status'] );
		}
		return true;
	}

	/**
	 * Returns a compact case result.
	 *
	 * @param array<string,mixed> $result Full case result.
	 * @return array<string,mixed>
	 */
	private function gutenberg_recipe_eval_case_excerpt( array $result ) {
		return array(
			'id'            => (string) ( $result['id'] ?? '' ),
			'passed'        => ! empty( $result['passed'] ),
			'route'         => sanitize_key( (string) ( $result['route'] ?? '' ) ),
			'failure_codes' => is_array( $result['failure_codes'] ?? null ) ? array_values( $result['failure_codes'] ) : array(),
			'dual_review'   => is_array( $result['dual_review'] ?? null ) ? $result['dual_review'] : array(),
		);
	}

	/**
	 * Returns the deterministic two-reviewer contract for recipe evaluation.
	 *
	 * @return array<string,mixed>
	 */
	private function gutenberg_recipe_eval_review_contract() {
		return array(
			'reviewer_count' => 2,
			'pattern_source' => 'article_summary_coverage_check_style',
			'reviewers'      => array(
				array(
					'reviewer_id' => 'recipe_fit_reviewer',
					'checks'      => array( 'expected_route', 'supported_status', 'plan_quality', 'block_quality' ),
				),
				array(
					'reviewer_id' => 'governance_boundary_reviewer',
					'checks'      => array( 'read_only_evaluation', 'no_proposal_creation', 'no_commit_execution', 'allowed_write_abilities' ),
				),
			),
		);
	}

	/**
	 * Returns deterministic dual review for one evaluated case.
	 *
	 * @param string[]            $failure_codes Failure codes.
	 * @param array<string,mixed> $plan_summary Plan summary.
	 * @return array<string,mixed>
	 */
	private function gutenberg_recipe_eval_case_dual_review( array $failure_codes, array $plan_summary ) {
		$failure_codes       = array_values( array_unique( array_filter( array_map( 'sanitize_key', $failure_codes ) ) ) );
		$governance_failures = array_values(
			array_intersect(
				$failure_codes,
				array(
					'direct_wordpress_write_true',
					'commit_execution_true',
					'router_emitted_write_actions',
					'unexpected_write_ability',
				)
			)
		);
		return array(
			'recipe_fit_reviewer' => array(
				'reviewer_id'   => 'recipe_fit_reviewer',
				'decision'      => empty( $failure_codes ) ? 'pass' : 'needs_fix',
				'failure_codes' => $failure_codes,
			),
			'governance_boundary_reviewer' => array(
				'reviewer_id'             => 'governance_boundary_reviewer',
				'decision'                => empty( $governance_failures ) ? 'pass' : 'blocked',
				'failure_codes'           => $governance_failures,
				'direct_wordpress_write'  => ! empty( $plan_summary['direct_wordpress_write'] ),
				'commit_execution'        => ! empty( $plan_summary['commit_execution'] ),
				'proposal_created'        => false,
			),
			'consensus'            => array(
				'decision'              => empty( $failure_codes ) ? 'pass' : ( empty( $governance_failures ) ? 'fix_recipe' : 'blocked' ),
				'recommended_next_step' => empty( $failure_codes ) ? 'keep_recipe' : ( empty( $governance_failures ) ? 'fix_top_failure_class' : 'stop_and_fix_boundary' ),
			),
		);
	}

	/**
	 * Returns deterministic dual review for the whole suite.
	 *
	 * @param array<int,array<string,mixed>> $case_results Case results.
	 * @param string                         $status Suite status.
	 * @return array<string,mixed>
	 */
	private function gutenberg_recipe_eval_suite_dual_review( array $case_results, $status ) {
		$all_failures = array();
		foreach ( $case_results as $case_result ) {
			foreach ( is_array( $case_result['failure_codes'] ?? null ) ? $case_result['failure_codes'] : array() as $failure_code ) {
				$all_failures[] = sanitize_key( (string) $failure_code );
			}
		}
		return $this->gutenberg_recipe_eval_case_dual_review( $all_failures, array( 'suite_status' => sanitize_key( (string) $status ) ) );
	}

	/**
	 * Returns failure counts by code.
	 *
	 * @param array<int,array<string,mixed>> $case_results Results.
	 * @return array<string,mixed>
	 */
	private function gutenberg_recipe_eval_failure_summary( array $case_results ) {
		$counts = array();
		foreach ( $case_results as $result ) {
			foreach ( is_array( $result['failure_codes'] ?? null ) ? $result['failure_codes'] : array() as $code ) {
				$code = sanitize_key( (string) $code );
				if ( '' === $code ) {
					continue;
				}
				$counts[ $code ] = ( $counts[ $code ] ?? 0 ) + 1;
			}
		}
		arsort( $counts );
		$top_failure_code = '';
		foreach ( $counts as $code => $count ) {
			unset( $count );
			$top_failure_code = (string) $code;
			break;
		}
		return array(
			'failure_count_by_code' => $counts,
			'top_failure_code'      => $top_failure_code,
		);
	}

	/**
	 * Normalizes a media fixture.
	 *
	 * @param mixed $fixture Fixture.
	 * @return array<string,mixed>
	 */
	private function gutenberg_recipe_eval_media_fixture( $fixture ) {
		$fixture = is_array( $fixture ) ? $fixture : array();
		return array(
			'url'           => esc_url_raw( (string) ( $fixture['url'] ?? '' ) ),
			'attachment_id' => absint( $fixture['attachment_id'] ?? 0 ),
			'alt'           => sanitize_text_field( (string) ( $fixture['alt'] ?? 'Gutenberg recipe evaluation media' ) ),
		);
	}

	/**
	 * Bounds float input.
	 *
	 * @param mixed $value Value.
	 * @param float $default Default.
	 * @param float $min Min.
	 * @param float $max Max.
	 * @return float
	 */
	private function gutenberg_recipe_eval_float( $value, $default, $min, $max ) {
		$value = is_numeric( $value ) ? (float) $value : (float) $default;
		return min( (float) $max, max( (float) $min, $value ) );
	}
}
