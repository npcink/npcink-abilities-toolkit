<?php
/**
 * Page pattern planning methods for Core_Read_Package.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds proposal-ready page pattern plans without writing WordPress content.
 */
trait Page_Pattern_Read_Methods {
	/**
	 * Builds one governed page pattern write plan.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function build_pattern_page_plan( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'edit_pages' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to plan page drafts.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$pattern_id         = sanitize_key( (string) ( $input['pattern_id'] ?? 'openai-style-landing' ) );
		$style_preset       = sanitize_key( (string) ( $input['style_preset'] ?? 'minimal-dark-light' ) );
		$responsive_profile = sanitize_key( (string) ( $input['responsive_profile'] ?? 'landing_standard' ) );
		$visual_density     = sanitize_key( (string) ( $input['visual_density'] ?? 'balanced' ) );
		$media_strategy     = sanitize_key( (string) ( $input['media_strategy'] ?? 'mock_or_existing_media' ) );
		$post_type          = sanitize_key( (string) ( $input['post_type'] ?? 'page' ) );
		if ( 'page' !== $post_type ) {
			return new \WP_Error( 'npcink_abilities_toolkit_pattern_page_post_type_invalid', __( 'Pattern page plans currently support post_type=page only.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( 'openai-style-landing' !== $pattern_id ) {
			return new \WP_Error( 'npcink_abilities_toolkit_pattern_page_pattern_invalid', __( 'Pattern id is not supported.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( 'minimal-dark-light' !== $style_preset ) {
			return new \WP_Error( 'npcink_abilities_toolkit_pattern_page_style_invalid', __( 'Style preset is not supported.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( 'landing_standard' !== $responsive_profile ) {
			return new \WP_Error( 'npcink_abilities_toolkit_pattern_page_responsive_profile_invalid', __( 'Responsive profile is not supported.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( 'balanced' !== $visual_density ) {
			return new \WP_Error( 'npcink_abilities_toolkit_pattern_page_visual_density_invalid', __( 'Visual density is not supported.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( ! in_array( $media_strategy, array( 'mock_or_existing_media', 'existing_media_url' ), true ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_pattern_page_media_strategy_invalid', __( 'Media strategy is not supported.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$variables = is_array( $input['variables'] ?? null ) ? $input['variables'] : array();
		$research_brief = $this->pattern_research_brief( $input['research_brief'] ?? ( $variables['research_brief'] ?? array() ) );
		$title     = $this->pattern_text( $input['title'] ?? ( $variables['hero_title'] ?? 'WordPress AI' ), 'WordPress AI' );
		$blocks    = $this->render_openai_style_landing_blocks(
			$variables,
			array(
				'responsive_profile' => $responsive_profile,
				'visual_density'     => $visual_density,
				'media_strategy'     => $media_strategy,
				'research_brief'     => $research_brief,
			)
		);
		$design_quality     = $this->pattern_design_quality_summary( $blocks, $research_brief );
		$responsive_quality = $this->pattern_responsive_quality_summary( $blocks, $responsive_profile );
		$batch_seed         = wp_json_encode( array( $pattern_id, $style_preset, $responsive_profile, $visual_density, $media_strategy, $title, $variables ) );
		$batch_id           = 'pattern_page_' . substr( md5( is_string( $batch_seed ) ? $batch_seed : $title ), 0, 12 );
		$write_actions = array(
			$this->build_plan_action(
				'create-pattern-page',
				'npcink-abilities-toolkit/create-draft',
				array(
					'post_type'      => 'page',
					'status'         => 'draft',
					'title'          => $title,
					'content'        => '',
					'content_format' => 'html',
					'meta'           => array(
						'npcink_pattern_id'         => $pattern_id,
						'npcink_style_preset'       => $style_preset,
						'npcink_responsive_profile' => $responsive_profile,
					),
				),
				array( 'post.write' ),
				'medium',
				__( 'Create a draft page before applying reviewed Gutenberg pattern blocks.', 'npcink-abilities-toolkit' )
			),
			$this->build_plan_action(
				'update-pattern-page-blocks',
				'npcink-abilities-toolkit/update-post-blocks',
				array(
					'post_id'            => '$outputs.create-pattern-page.post_id',
					'mode'               => 'replace',
					'validate_roundtrip' => true,
					'blocks'             => $blocks,
				),
				array( 'post.write' ),
				'medium',
				__( 'Replace the draft page body with whitelist-class Gutenberg pattern blocks.', 'npcink-abilities-toolkit' )
			),
		);

		return $this->build_analysis_success_response(
			array(
				'artifact_type'          => 'pattern_page_plan',
				'composition_role'       => 'core_pattern_page_plan',
				'version'                => 1,
				'pattern_id'             => $pattern_id,
				'style_preset'           => $style_preset,
				'responsive_profile'     => $responsive_profile,
				'visual_density'         => $visual_density,
				'media_strategy'         => $media_strategy,
				'research_brief'         => $this->pattern_research_brief_summary( $research_brief ),
				'allowed_classes'        => $this->pattern_allowed_classes(),
				'write_posture'          => 'core_proposal_handoff',
				'direct_wordpress_write' => false,
				'requires_approval'      => true,
				'dry_run'                => true,
				'commit_execution'       => false,
				'proposal_mode'          => 'batch',
				'batch_id'               => $batch_id,
				'summary'                => array(
					'title'        => $title,
					'block_count'  => $this->count_pattern_blocks_recursive( $blocks ),
					'action_count' => count( $write_actions ),
				),
				'design_quality'         => $design_quality,
				'responsive_quality'     => $responsive_quality,
				'write_actions'          => $write_actions,
				'handoff'                => array(
					'plan_ability_id'        => 'npcink-abilities-toolkit/build-pattern-page-plan',
					'recipe_id'              => 'pattern_page_v1',
					'recipe_ref'             => 'workflow/pattern_page_plan',
					'core_route'             => '/wp-json/npcink-governance-core/v1/proposals/from-plan',
					'final_write_path'       => 'core_proposal_required',
					'direct_wordpress_write' => false,
				),
			),
			array(
				'source'         => 'local_pattern_page_plan',
				'execution_mode' => 'deterministic',
			),
			'Pattern page plan built.'
		);
	}

	/**
	 * Renders the built-in landing pattern as parsed Gutenberg blocks.
	 *
	 * @param array<string,mixed> $variables Pattern variables.
	 * @param array<string,string> $options Pattern options.
	 * @return array<int,array<string,mixed>>
	 */
	private function render_openai_style_landing_blocks( array $variables, array $options = array() ) {
		$eyebrow          = $this->pattern_text( $variables['eyebrow'] ?? '', 'WordPress AI Plugin' );
		$hero_title       = $this->pattern_text( $variables['hero_title'] ?? '', '把 AI 工作流带进 WordPress 内容现场' );
		$hero_description = $this->pattern_text( $variables['hero_description'] ?? '', '让内容生产、SEO 优化、媒体处理与发布协作在同一个可审计流程中完成。' );
		$primary_cta      = $this->pattern_text( $variables['primary_cta'] ?? '', '查看工作流' );
		$secondary_cta    = $this->pattern_text( $variables['secondary_cta'] ?? '', '了解能力' );
		$media_strategy   = sanitize_key( (string) ( $options['media_strategy'] ?? 'mock_or_existing_media' ) );
		$research_brief   = is_array( $options['research_brief'] ?? null ) ? $options['research_brief'] : array();
		$hero_media_url   = esc_url_raw( (string) ( $variables['hero_media_url'] ?? '' ) );
		$hero_media_alt   = $this->pattern_text( $variables['hero_media_alt'] ?? '', 'WordPress AI workflow interface' );
		$proof_points     = $this->pattern_items(
			$variables['proof_points'] ?? array(),
			$this->pattern_items(
				$research_brief['proof_points'] ?? array(),
				array(
					array(
						'title'       => 'Proposal-first',
						'description' => 'AI 只生成计划和提案，审批后才进入执行。',
					),
					array(
						'title'       => 'Gutenberg-native',
						'description' => '页面结构以 WordPress 核心块保存，编辑器可继续维护。',
					),
					array(
						'title'       => 'Auditable',
						'description' => '每次写入都关联 proposal、preflight 和审计记录。',
					),
					array(
						'title'       => 'Draft-safe',
						'description' => '默认创建草稿，不直接发布生产内容。',
					),
				),
				4
			),
			4
		);
		$comparison_angles = $this->pattern_items(
			$research_brief['comparison_angles'] ?? array(),
			array(),
			0
		);
		$section_patterns  = $this->pattern_items(
			$research_brief['section_patterns'] ?? array(),
			array(),
			0
		);
		$visual_recommendations = $this->pattern_items(
			$research_brief['visual_asset_recommendations'] ?? array(),
			array(),
			0
		);
		$faq_seed_questions = $this->pattern_faq_seed_items( $research_brief['faq_seed_questions'] ?? array() );
		$feature_fallback = array(
			array(
				'title'       => 'AI 内容草稿',
				'description' => '从主题、上下文和站点知识出发，生成结构化草稿。',
			),
			array(
				'title'       => '可审查提案',
				'description' => '所有写入先形成 proposal，再经过审批和 preflight。',
			),
			array(
				'title'       => 'Gutenberg 原生块',
				'description' => '输出标准核心块结构，便于编辑器继续维护。',
			),
		);
		if ( ! empty( $section_patterns ) ) {
			$feature_fallback = $this->pattern_merge_item_lists( $section_patterns, $feature_fallback, 3 );
		}
		$features         = $this->pattern_items(
			$variables['features'] ?? array(),
			$feature_fallback,
			3
		);
		$workflow         = $this->pattern_items(
			$variables['workflow'] ?? array(),
			array(
				array(
					'title'       => '规划',
					'description' => 'AI 生成页面结构、文案变量和写入动作。',
				),
				array(
					'title'       => '审批',
					'description' => 'Core 记录 proposal、审批状态和 commit preflight。',
				),
				array(
					'title'       => '写入',
					'description' => 'Adapter 只执行 allowlisted Gutenberg 块更新。',
				),
				array(
					'title'       => '验证',
					'description' => '回读块结构、roundtrip 和审计记录，确认写入可追踪。',
				),
			),
			4
		);
		$faq              = $this->pattern_items(
			$variables['faq'] ?? array(),
			$this->pattern_merge_item_lists(
				$faq_seed_questions,
				array(
					array(
						'title'       => '这个页面会直接发布吗？',
						'description' => '不会。Pattern 计划只创建 proposal，最终写入仍由 Core 审批和 Adapter 执行 profile 控制。',
					),
					array(
						'title'       => '编辑器里还能继续改吗？',
						'description' => '可以。页面由 Gutenberg 核心块组成，目标是保持块结构可读、可解析、可继续编辑。',
					),
					array(
						'title'       => '移动端会怎么显示？',
						'description' => '多列区块使用移动端堆叠策略，按钮和内容区保持可换行、可阅读。',
					),
				),
				3
			),
			3
		);
		$final_cta_title       = $this->pattern_text( $variables['final_cta_title'] ?? '', '从一个可审查的 AI 页面草稿开始' );
		$final_cta_description = $this->pattern_text( $variables['final_cta_description'] ?? '', '用 Gutenberg-native Pattern 生成页面计划，再通过 Core proposal 审批进入写入。' );
		$final_cta_primary     = $this->pattern_text( $variables['final_cta_primary'] ?? '', '创建页面计划' );
		$final_cta_secondary   = $this->pattern_text( $variables['final_cta_secondary'] ?? '', '查看治理流程' );

		$blocks = array(
			$this->pattern_group_block(
				'npcink-ai-page npcink-ai-hero',
				array(
					$this->pattern_columns_block(
						array(
							$this->pattern_column_block(
								array(
									$this->pattern_paragraph_block( $eyebrow, 'npcink-ai-eyebrow', $this->pattern_eyebrow_attrs(), 'color:#555555;font-size:13px;font-weight:600;line-height:1.2;text-transform:uppercase' ),
									$this->pattern_heading_block( $hero_title, 1, 'npcink-ai-title', $this->pattern_hero_title_attrs(), 'font-size:64px;font-weight:500;line-height:1;letter-spacing:0;margin-top:0;margin-bottom:0' ),
									$this->pattern_paragraph_block( $hero_description, 'npcink-ai-lede', $this->pattern_lede_attrs(), 'color:#333333;font-size:22px;line-height:1.4' ),
									$this->pattern_buttons_block(
										array(
											$this->pattern_button_block( $primary_cta, 'npcink-ai-button-primary', $this->pattern_primary_button_attrs(), 'background-color:#111111;color:#ffffff;border-radius:999px;padding-top:14px;padding-right:24px;padding-bottom:14px;padding-left:24px' ),
											$this->pattern_button_block( $secondary_cta, 'npcink-ai-button-secondary', $this->pattern_secondary_button_attrs(), 'background-color:#ffffff;color:#111111;border:1px solid #111111;border-radius:999px;padding-top:14px;padding-right:24px;padding-bottom:14px;padding-left:24px' ),
										),
										'npcink-ai-cta',
										$this->pattern_buttons_attrs()
									),
								),
								'npcink-ai-hero-copy'
							),
							$this->pattern_column_block(
								array( $this->pattern_dashboard_mock_block() ),
								'npcink-ai-hero-dashboard'
							),
						),
						'npcink-ai-hero-layout',
						$this->pattern_columns_attrs( '40px' )
					),
				),
				$this->pattern_section_attrs( '#f7f7f4', '96px', '88px', true ),
				'background-color:#f7f7f4;border-bottom-color:#e5e5e5;border-bottom-width:1px;padding-top:96px;padding-right:40px;padding-bottom:88px;padding-left:40px'
			),
			$this->pattern_group_block(
				'npcink-ai-proof-strip',
				array(
					$this->pattern_columns_block(
						array_map(
							function ( $item ) {
								return $this->pattern_column_block(
									array(
										$this->pattern_group_block(
											'npcink-ai-proof-card',
											array(
												$this->pattern_heading_block( (string) $item['title'], 3, 'npcink-ai-card-title', $this->pattern_card_title_attrs(), 'font-size:22px;font-weight:500;line-height:1.2;letter-spacing:0;margin-top:0' ),
												$this->pattern_paragraph_block( (string) $item['description'], 'npcink-ai-card-text', $this->pattern_card_text_attrs(), 'color:#454545;font-size:16px;line-height:1.55' ),
											),
											$this->pattern_line_card_attrs(),
											'border-top-color:#111111;border-top-width:1px;padding-top:22px;padding-right:0;padding-bottom:0;padding-left:0'
										),
									)
								);
							},
							$proof_points
						),
						'npcink-ai-proof-strip',
						$this->pattern_columns_attrs( '24px' )
					),
				),
				$this->pattern_section_attrs( '#ffffff', '48px', '56px', false ),
				'background-color:#ffffff;padding-top:48px;padding-right:40px;padding-bottom:56px;padding-left:40px'
			),
		);

		if ( '' !== $hero_media_url && in_array( $media_strategy, array( 'mock_or_existing_media', 'existing_media_url' ), true ) ) {
			$blocks[] = $this->pattern_group_block(
				'npcink-ai-media-section',
				array(
					$this->pattern_media_text_block(
						$hero_media_url,
						$hero_media_alt,
						array(
							$this->pattern_paragraph_block( 'Responsive Pattern', 'npcink-ai-eyebrow', $this->pattern_eyebrow_attrs(), 'color:#555555;font-size:13px;font-weight:600;line-height:1.2;text-transform:uppercase' ),
							$this->pattern_heading_block( $this->pattern_text( $variables['media_title'] ?? '', $this->pattern_research_item_title( $visual_recommendations, '视觉结构和治理流程一起交付' ) ), 2, 'npcink-ai-section-title', $this->pattern_section_title_attrs(), 'font-size:40px;font-weight:500;line-height:1.1;letter-spacing:0' ),
							$this->pattern_paragraph_block( $this->pattern_text( $variables['media_description'] ?? '', $this->pattern_research_item_description( $visual_recommendations, 'OpenClaw 提供页面意图和素材引用，Toolkit 输出响应式 Gutenberg 模块，Core 继续保留审批和审计。' ) ), 'npcink-ai-card-text', $this->pattern_card_text_attrs(), 'color:#454545;font-size:16px;line-height:1.55' ),
						),
						'npcink-ai-media-text',
						$this->pattern_media_text_attrs()
					),
				),
				$this->pattern_section_attrs( '#f7f7f4', '72px', '72px', false ),
				'background-color:#f7f7f4;padding-top:72px;padding-right:40px;padding-bottom:72px;padding-left:40px'
			);
		}

		$research_sections = array();
		if ( ! empty( $comparison_angles ) ) {
			$research_sections[] = $this->pattern_comparison_block( $comparison_angles );
		}

		$blocks = array_merge(
			$blocks,
			array(
				$this->pattern_group_block(
					'npcink-ai-feature-grid',
					array(
						$this->pattern_heading_block( $this->pattern_text( $variables['features_title'] ?? '', 'AI 内容现场的基础能力' ), 2, 'npcink-ai-section-title', $this->pattern_section_title_attrs(), 'font-size:40px;font-weight:500;line-height:1.1;letter-spacing:0' ),
						$this->pattern_columns_block(
							array_map(
								function ( $item ) {
									return $this->pattern_column_block(
										array(
											$this->pattern_group_block(
												'npcink-ai-feature-card',
												array(
													$this->pattern_heading_block( (string) $item['title'], 3, 'npcink-ai-card-title', $this->pattern_card_title_attrs(), 'font-size:22px;font-weight:500;line-height:1.2;letter-spacing:0;margin-top:0' ),
													$this->pattern_paragraph_block( (string) $item['description'], 'npcink-ai-card-text', $this->pattern_card_text_attrs(), 'color:#454545;font-size:16px;line-height:1.55' ),
												),
												$this->pattern_line_card_attrs(),
												'border-top-color:#111111;border-top-width:1px;padding-top:28px;padding-right:0;padding-bottom:0;padding-left:0'
											),
										)
									);
								},
								$features
							),
							'npcink-ai-feature-grid',
							$this->pattern_columns_attrs()
						),
					),
					$this->pattern_section_attrs( '#ffffff', '88px', '88px', false ),
					'background-color:#ffffff;padding-top:88px;padding-right:40px;padding-bottom:88px;padding-left:40px'
				),
				$this->pattern_group_block(
					'npcink-ai-workflow',
					array(
						$this->pattern_heading_block( $this->pattern_text( $variables['workflow_title'] ?? '', '从生成到发布，始终可审查' ), 2, 'npcink-ai-section-title', $this->pattern_section_title_attrs(), 'font-size:40px;font-weight:500;line-height:1.1;letter-spacing:0' ),
						$this->pattern_columns_block(
							array_map(
								function ( $item ) {
									return $this->pattern_column_block(
										array(
											$this->pattern_group_block(
												'npcink-ai-workflow-step',
												array(
													$this->pattern_heading_block( (string) $item['title'], 3, 'npcink-ai-card-title', $this->pattern_card_title_attrs(), 'font-size:22px;font-weight:500;line-height:1.2;letter-spacing:0;margin-top:0' ),
													$this->pattern_paragraph_block( (string) $item['description'], 'npcink-ai-card-text', $this->pattern_card_text_attrs(), 'color:#454545;font-size:16px;line-height:1.55' ),
												),
												$this->pattern_card_attrs(),
												'background-color:#ffffff;border-color:#dddddd;border-width:1px;border-radius:20px;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px'
											),
										)
									);
								},
								$workflow
							),
							'npcink-ai-workflow',
							$this->pattern_columns_attrs()
						),
					),
					$this->pattern_section_attrs( '#f7f7f4', '88px', '96px', false ),
					'background-color:#f7f7f4;padding-top:88px;padding-right:40px;padding-bottom:96px;padding-left:40px'
				),
			),
			$research_sections,
			array(
				$this->pattern_group_block(
					'npcink-ai-faq',
					array(
						$this->pattern_heading_block( $this->pattern_text( $variables['faq_title'] ?? '', '常见问题' ), 2, 'npcink-ai-section-title', $this->pattern_section_title_attrs(), 'font-size:40px;font-weight:500;line-height:1.1;letter-spacing:0' ),
						$this->pattern_group_block(
							'npcink-ai-faq-list',
							array_map(
								function ( $item ) {
									return $this->pattern_details_block(
										(string) $item['title'],
										(string) $item['description'],
										'npcink-ai-faq-item',
										$this->pattern_faq_item_attrs()
									);
								},
								$faq
							),
							$this->pattern_faq_list_attrs(),
							''
						),
					),
					$this->pattern_section_attrs( '#ffffff', '88px', '88px', false ),
					'background-color:#ffffff;padding-top:88px;padding-right:40px;padding-bottom:88px;padding-left:40px'
				),
				$this->pattern_group_block(
					'npcink-ai-final-cta',
					array(
						$this->pattern_heading_block( $final_cta_title, 2, 'npcink-ai-section-title', $this->pattern_section_title_attrs(), 'font-size:40px;font-weight:500;line-height:1.1;letter-spacing:0' ),
						$this->pattern_paragraph_block( $final_cta_description, 'npcink-ai-lede', $this->pattern_light_lede_attrs(), 'color:#f2f2f2;font-size:22px;line-height:1.4' ),
						$this->pattern_buttons_block(
							array(
								$this->pattern_button_block( $final_cta_primary, 'npcink-ai-button-primary', $this->pattern_primary_button_attrs(), 'background-color:#111111;color:#ffffff;border-radius:999px;padding-top:14px;padding-right:24px;padding-bottom:14px;padding-left:24px' ),
								$this->pattern_button_block( $final_cta_secondary, 'npcink-ai-button-secondary', $this->pattern_secondary_button_attrs(), 'background-color:#ffffff;color:#111111;border:1px solid #111111;border-radius:999px;padding-top:14px;padding-right:24px;padding-bottom:14px;padding-left:24px' ),
							),
							'npcink-ai-cta',
							$this->pattern_buttons_attrs( 'center' )
						),
					),
					$this->pattern_section_attrs( '#111111', '88px', '96px', false ),
					'background-color:#111111;color:#ffffff;padding-top:88px;padding-right:40px;padding-bottom:96px;padding-left:40px'
				),
			)
		);

		return $blocks;
	}

	/**
	 * Returns allowed pattern classes.
	 *
	 * @return string[]
	 */
	private function pattern_allowed_classes() {
		return array(
			'npcink-ai-page',
			'npcink-ai-hero',
			'npcink-ai-eyebrow',
			'npcink-ai-title',
			'npcink-ai-lede',
			'npcink-ai-cta',
			'npcink-ai-button-primary',
			'npcink-ai-button-secondary',
			'npcink-ai-hero-layout',
			'npcink-ai-hero-copy',
			'npcink-ai-hero-dashboard',
			'npcink-ai-dashboard-card',
			'npcink-ai-dashboard-label',
			'npcink-ai-dashboard-value',
			'npcink-ai-dashboard-row',
			'npcink-ai-proof-strip',
			'npcink-ai-proof-card',
			'npcink-ai-media-section',
			'npcink-ai-media-text',
			'npcink-ai-feature-grid',
			'npcink-ai-feature-card',
			'npcink-ai-workflow',
			'npcink-ai-workflow-step',
			'npcink-ai-comparison',
			'npcink-ai-comparison-grid',
			'npcink-ai-comparison-card',
			'npcink-ai-faq',
			'npcink-ai-faq-list',
			'npcink-ai-faq-item',
			'npcink-ai-final-cta',
			'npcink-ai-section-title',
			'npcink-ai-card-title',
			'npcink-ai-card-text',
		);
	}

	/**
	 * Builds a group block with innerContent markers.
	 *
	 * @param string                    $class_name CSS classes.
	 * @param array<int,array<string,mixed>> $inner_blocks Inner blocks.
	 * @param array<string,mixed>       $attrs Additional block attrs.
	 * @param string                    $style_attr Wrapper style attribute.
	 * @return array<string,mixed>
	 */
	private function pattern_group_block( $class_name, array $inner_blocks, array $attrs = array(), $style_attr = '' ) {
		$class_name = $this->pattern_class_names( $class_name );
		$attrs      = array_merge( $attrs, array( 'className' => $class_name ) );
		$classes    = $this->pattern_group_classes( $attrs );
		$style_attr = $this->pattern_style_from_attrs( $attrs );
		$style_html = '' !== $style_attr ? ' style="' . $this->pattern_attr( $style_attr ) . '"' : '';
		return array(
			'blockName'    => 'core/group',
			'attrs'        => $attrs,
			'innerHTML'    => '<div class="' . $this->pattern_attr( $classes ) . '"' . $style_html . '></div>',
			'innerContent' => array_merge( array( '<div class="' . $this->pattern_attr( $classes ) . '"' . $style_html . '>' ), array_fill( 0, count( $inner_blocks ), null ), array( '</div>' ) ),
			'innerBlocks'  => array_values( $inner_blocks ),
		);
	}

	/**
	 * Builds a heading block.
	 *
	 * @param string $text Text.
	 * @param int    $level Heading level.
	 * @param string $class_name CSS classes.
	 * @param array<string,mixed> $attrs Additional block attrs.
	 * @param string $style_attr Element style attribute.
	 * @return array<string,mixed>
	 */
	private function pattern_heading_block( $text, $level, $class_name, array $attrs = array(), $style_attr = '' ) {
		$level      = max( 1, min( 6, (int) $level ) );
		$class_name = $this->pattern_class_names( $class_name );
		$attrs      = array_merge(
			$attrs,
			array(
				'level'     => $level,
				'className' => $class_name,
			)
		);
		$style_attr = $this->pattern_style_from_attrs( $attrs );
		$style_html = '' !== $style_attr ? ' style="' . $this->pattern_attr( $style_attr ) . '"' : '';
		$html       = '<h' . $level . ' class="wp-block-heading ' . $this->pattern_attr( $class_name ) . '"' . $style_html . '>' . esc_html( $text ) . '</h' . $level . '>';
		return array(
			'blockName'    => 'core/heading',
			'attrs'        => $attrs,
			'innerHTML'    => $html,
			'innerContent' => array( $html ),
			'innerBlocks'  => array(),
		);
	}

	/**
	 * Builds a paragraph block.
	 *
	 * @param string $text Text.
	 * @param string $class_name CSS classes.
	 * @param array<string,mixed> $attrs Additional block attrs.
	 * @param string $style_attr Element style attribute.
	 * @return array<string,mixed>
	 */
	private function pattern_paragraph_block( $text, $class_name, array $attrs = array(), $style_attr = '' ) {
		$class_name = $this->pattern_class_names( $class_name );
		$attrs      = array_merge( $attrs, array( 'className' => $class_name ) );
		$style_attr = $this->pattern_style_from_attrs( $attrs );
		$style_html = '' !== $style_attr ? ' style="' . $this->pattern_attr( $style_attr ) . '"' : '';
		$html       = '<p class="' . $this->pattern_attr( $class_name ) . '"' . $style_html . '>' . esc_html( $text ) . '</p>';
		return array(
			'blockName'    => 'core/paragraph',
			'attrs'        => $attrs,
			'innerHTML'    => $html,
			'innerContent' => array( $html ),
			'innerBlocks'  => array(),
		);
	}

	/**
	 * Builds a buttons block.
	 *
	 * @param array<int,array<string,mixed>> $buttons Button blocks.
	 * @param string                         $class_name CSS classes.
	 * @param array<string,mixed>            $attrs Additional block attrs.
	 * @return array<string,mixed>
	 */
	private function pattern_buttons_block( array $buttons, $class_name, array $attrs = array() ) {
		$class_name = $this->pattern_class_names( $class_name );
		$attr_class = $this->pattern_attr( $class_name );
		return array(
			'blockName'    => 'core/buttons',
			'attrs'        => array_merge( $attrs, array( 'className' => $class_name ) ),
			'innerHTML'    => '<div class="wp-block-buttons ' . $attr_class . '"></div>',
			'innerContent' => array_merge( array( '<div class="wp-block-buttons ' . $attr_class . '">' ), array_fill( 0, count( $buttons ), null ), array( '</div>' ) ),
			'innerBlocks'  => array_values( $buttons ),
		);
	}

	/**
	 * Builds a button block.
	 *
	 * @param string $text Text.
	 * @param string $class_name CSS classes.
	 * @param array<string,mixed> $attrs Additional block attrs.
	 * @param string $style_attr Link style attribute.
	 * @return array<string,mixed>
	 */
	private function pattern_button_block( $text, $class_name, array $attrs = array(), $style_attr = '' ) {
		$class_name = $this->pattern_class_names( $class_name );
		$attrs      = array_merge( $attrs, array( 'className' => $class_name ) );
		$style_attr = $this->pattern_style_from_attrs( $attrs );
		$style_html = '' !== $style_attr ? ' style="' . $this->pattern_attr( $style_attr ) . '"' : '';
		$link_class = $this->pattern_button_link_classes( $attrs );
		$html       = '<div class="wp-block-button ' . $this->pattern_attr( $class_name ) . '"><a class="' . $this->pattern_attr( $link_class ) . '"' . $style_html . '>' . esc_html( $text ) . '</a></div>';
		return array(
			'blockName'    => 'core/button',
			'attrs'        => $attrs,
			'innerHTML'    => $html,
			'innerContent' => array( $html ),
			'innerBlocks'  => array(),
		);
	}

	/**
	 * Builds a columns block.
	 *
	 * @param array<int,array<string,mixed>> $columns Column blocks.
	 * @param string                         $class_name CSS classes.
	 * @param array<string,mixed>            $attrs Additional block attrs.
	 * @return array<string,mixed>
	 */
	private function pattern_columns_block( array $columns, $class_name, array $attrs = array() ) {
		$class_name = $this->pattern_class_names( $class_name );
		$attr_class = $this->pattern_attr( $class_name );
		return array(
			'blockName'    => 'core/columns',
			'attrs'        => array_merge( $attrs, array( 'className' => $class_name ) ),
			'innerHTML'    => '<div class="wp-block-columns ' . $attr_class . '"></div>',
			'innerContent' => array_merge( array( '<div class="wp-block-columns ' . $attr_class . '">' ), array_fill( 0, count( $columns ), null ), array( '</div>' ) ),
			'innerBlocks'  => array_values( $columns ),
		);
	}

	/**
	 * Builds a column block.
	 *
	 * @param array<int,array<string,mixed>> $inner_blocks Inner blocks.
	 * @return array<string,mixed>
	 */
	private function pattern_column_block( array $inner_blocks, $class_name = '', array $attrs = array() ) {
		$class_name = $this->pattern_class_names( $class_name );
		if ( '' !== $class_name ) {
			$attrs['className'] = $class_name;
		}
		$attr_class = '' !== $class_name ? ' ' . $this->pattern_attr( $class_name ) : '';
		return array(
			'blockName'    => 'core/column',
			'attrs'        => $attrs,
			'innerHTML'    => '<div class="wp-block-column' . $attr_class . '"></div>',
			'innerContent' => array_merge( array( '<div class="wp-block-column' . $attr_class . '">' ), array_fill( 0, count( $inner_blocks ), null ), array( '</div>' ) ),
			'innerBlocks'  => array_values( $inner_blocks ),
		);
	}

	/**
	 * Builds a responsive media-text block from an existing media URL.
	 *
	 * @param string                         $media_url Media URL.
	 * @param string                         $media_alt Media alt text.
	 * @param array<int,array<string,mixed>> $inner_blocks Content blocks.
	 * @param string                         $class_name CSS classes.
	 * @param array<string,mixed>            $attrs Additional block attrs.
	 * @return array<string,mixed>
	 */
	private function pattern_media_text_block( $media_url, $media_alt, array $inner_blocks, $class_name, array $attrs = array() ) {
		$class_name  = $this->pattern_class_names( $class_name );
		$media_url   = esc_url_raw( (string) $media_url );
		$media_alt   = sanitize_text_field( (string) $media_alt );
		$attrs       = array_merge(
			array(
				'align'              => 'wide',
				'mediaType'          => 'image',
				'mediaUrl'           => $media_url,
				'mediaAlt'           => $media_alt,
				'mediaWidth'         => 48,
				'isStackedOnMobile'  => true,
			),
			$attrs,
			array( 'className' => $class_name )
		);
		$classes     = trim( 'wp-block-media-text alignwide is-stacked-on-mobile ' . $this->pattern_attr( $class_name ) );
		$style_html  = ' style="grid-template-columns:48% auto"';
		$media_html  = '<figure class="wp-block-media-text__media"><img src="' . $this->pattern_attr( $media_url ) . '" alt="' . $this->pattern_attr( $media_alt ) . '"/></figure>';
		$open_html   = '<div class="' . $this->pattern_attr( $classes ) . '"' . $style_html . '>' . $media_html . '<div class="wp-block-media-text__content">';
		$close_html  = '</div></div>';
		return array(
			'blockName'    => 'core/media-text',
			'attrs'        => $attrs,
			'innerHTML'    => '<div class="' . $this->pattern_attr( $classes ) . '"' . $style_html . '>' . $media_html . '<div class="wp-block-media-text__content"></div></div>',
			'innerContent' => array_merge( array( $open_html ), array_fill( 0, count( $inner_blocks ), null ), array( $close_html ) ),
			'innerBlocks'  => array_values( $inner_blocks ),
		);
	}

	/**
	 * Builds a details FAQ item.
	 *
	 * @param string              $summary Summary text.
	 * @param string              $body Body text.
	 * @param string              $class_name CSS classes.
	 * @param array<string,mixed> $attrs Additional block attrs.
	 * @return array<string,mixed>
	 */
	private function pattern_details_block( $summary, $body, $class_name, array $attrs = array() ) {
		$class_name = $this->pattern_class_names( $class_name );
		$summary    = $this->pattern_text( $summary, '' );
		$body_block = $this->pattern_paragraph_block( $body, 'npcink-ai-card-text', $this->pattern_card_text_attrs(), 'color:#454545;font-size:16px;line-height:1.55' );
		$attrs      = array_merge(
			$attrs,
			array(
				'summary'   => $summary,
				'className' => $class_name,
			)
		);
		$style_html = ' style="border-top-color:#dddddd;border-top-width:1px;padding-top:20px;padding-right:0;padding-bottom:20px;padding-left:0"';
		$open_html  = '<details class="wp-block-details ' . $this->pattern_attr( $class_name ) . '"' . $style_html . '><summary>' . esc_html( $summary ) . '</summary>';
		return array(
			'blockName'    => 'core/details',
			'attrs'        => $attrs,
			'innerHTML'    => $open_html . '</details>',
			'innerContent' => array( $open_html, null, '</details>' ),
			'innerBlocks'  => array( $body_block ),
		);
	}

	/**
	 * Builds the hero proposal preview card.
	 *
	 * @return array<string,mixed>
	 */
	private function pattern_dashboard_mock_block() {
		return $this->pattern_group_block(
			'npcink-ai-dashboard-card',
			array(
				$this->pattern_paragraph_block( 'Proposal preview', 'npcink-ai-dashboard-label', $this->pattern_eyebrow_attrs(), 'color:#555555;font-size:13px;font-weight:600;line-height:1.2;text-transform:uppercase' ),
				$this->pattern_heading_block( 'Manual approval required', 3, 'npcink-ai-dashboard-value', $this->pattern_card_title_attrs(), 'font-size:24px;font-weight:500;line-height:1.2;letter-spacing:0;margin-top:0' ),
				$this->pattern_dashboard_row_block( 'Status', 'Pending proposal' ),
				$this->pattern_dashboard_row_block( 'Write actions', '2' ),
				$this->pattern_dashboard_row_block( 'Blocks', 'Gutenberg-native' ),
				$this->pattern_dashboard_row_block( 'Validation', 'Roundtrip ready' ),
			),
			$this->pattern_dashboard_card_attrs(),
			'background-color:#ffffff;border-color:#dcdcdc;border-width:1px;border-radius:24px;padding-top:28px;padding-right:28px;padding-bottom:28px;padding-left:28px'
		);
	}

	/**
	 * Builds one row inside the hero dashboard mock.
	 *
	 * @param string $label Row label.
	 * @param string $value Row value.
	 * @return array<string,mixed>
	 */
	private function pattern_dashboard_row_block( $label, $value ) {
		return $this->pattern_group_block(
			'npcink-ai-dashboard-row',
			array(
				$this->pattern_paragraph_block( $label, 'npcink-ai-dashboard-label', $this->pattern_small_muted_text_attrs(), 'color:#666666;font-size:14px;line-height:1.4' ),
				$this->pattern_paragraph_block( $value, 'npcink-ai-dashboard-value', $this->pattern_small_strong_text_attrs(), 'color:#111111;font-size:15px;font-weight:600;line-height:1.4' ),
			),
			$this->pattern_dashboard_row_attrs(),
			'border-top-color:#e5e5e5;border-top-width:1px;padding-top:12px;padding-right:0;padding-bottom:0;padding-left:0'
		);
	}

	/**
	 * Builds an optional research-backed comparison section.
	 *
	 * @param array<int,array<string,string>> $items Comparison angle items.
	 * @return array<string,mixed>
	 */
	private function pattern_comparison_block( array $items ) {
		$items = array_slice( $items, 0, 3 );
		return $this->pattern_group_block(
			'npcink-ai-comparison',
			array(
				$this->pattern_heading_block( __( '研究驱动的页面取舍', 'npcink-abilities-toolkit' ), 2, 'npcink-ai-section-title', $this->pattern_section_title_attrs(), 'font-size:40px;font-weight:500;line-height:1.1;letter-spacing:0' ),
				$this->pattern_columns_block(
					array_map(
						function ( $item ) {
							return $this->pattern_column_block(
								array(
									$this->pattern_group_block(
										'npcink-ai-comparison-card',
										array(
											$this->pattern_heading_block( (string) $item['title'], 3, 'npcink-ai-card-title', $this->pattern_card_title_attrs(), 'font-size:22px;font-weight:500;line-height:1.2;letter-spacing:0;margin-top:0' ),
											$this->pattern_paragraph_block( (string) $item['description'], 'npcink-ai-card-text', $this->pattern_card_text_attrs(), 'color:#454545;font-size:16px;line-height:1.55' ),
										),
										$this->pattern_card_attrs(),
										'background-color:#ffffff;border-color:#dddddd;border-width:1px;border-radius:20px;padding-top:28px;padding-right:28px;padding-bottom:28px;padding-left:28px'
									),
								)
							);
						},
						$items
					),
					'npcink-ai-comparison-grid',
					$this->pattern_columns_attrs()
				),
			),
			$this->pattern_section_attrs( '#f7f7f4', '88px', '88px', false ),
			'background-color:#f7f7f4;padding-top:88px;padding-right:40px;padding-bottom:88px;padding-left:40px'
		);
	}

	/**
	 * Section attrs using Gutenberg native layout, spacing, color, and border supports.
	 *
	 * @param string $background Background color.
	 * @param string $padding_top Top padding.
	 * @param string $padding_bottom Bottom padding.
	 * @param bool   $with_bottom_border Whether to add bottom border.
	 * @return array<string,mixed>
	 */
	private function pattern_section_attrs( $background, $padding_top, $padding_bottom, $with_bottom_border ) {
		$attrs = array(
			'align'  => 'full',
			'layout' => array(
				'type'        => 'constrained',
				'contentSize' => '1120px',
			),
			'style'  => array(
				'spacing' => array(
					'padding' => array(
						'top'    => (string) $padding_top,
						'right'  => '40px',
						'bottom' => (string) $padding_bottom,
						'left'   => '40px',
					),
				),
				'color'   => array(
					'background' => (string) $background,
				),
			),
		);
		if ( $with_bottom_border ) {
			$attrs['style']['border'] = array(
				'bottom' => array(
					'color' => '#e5e5e5',
					'width' => '1px',
				),
			);
		}
		return $attrs;
	}

	/**
	 * Hero title typography attrs.
	 *
	 * @return array<string,mixed>
	 */
	private function pattern_hero_title_attrs() {
		return array(
			'style' => array(
				'typography' => array(
					'fontSize'      => '64px',
					'lineHeight'     => '1',
					'letterSpacing' => '0',
					'fontWeight'    => '500',
				),
			),
		);
	}

	/**
	 * Eyebrow paragraph attrs.
	 *
	 * @return array<string,mixed>
	 */
	private function pattern_eyebrow_attrs() {
		return array(
			'style' => array(
				'color'      => array( 'text' => '#555555' ),
				'typography' => array(
					'fontSize'      => '13px',
					'lineHeight'     => '1.2',
					'letterSpacing' => '0',
					'fontWeight'    => '600',
					'textTransform' => 'uppercase',
				),
			),
		);
	}

	/**
	 * Lede paragraph attrs.
	 *
	 * @return array<string,mixed>
	 */
	private function pattern_lede_attrs() {
		return array(
			'style' => array(
				'color'      => array( 'text' => '#333333' ),
				'typography' => array(
					'fontSize'  => '22px',
					'lineHeight' => '1.4',
				),
			),
		);
	}

	/**
	 * Light lede paragraph attrs for dark sections.
	 *
	 * @return array<string,mixed>
	 */
	private function pattern_light_lede_attrs() {
		return array(
			'style' => array(
				'color'      => array( 'text' => '#f2f2f2' ),
				'typography' => array(
					'fontSize'  => '22px',
					'lineHeight' => '1.4',
				),
			),
		);
	}

	/**
	 * Section title typography attrs.
	 *
	 * @return array<string,mixed>
	 */
	private function pattern_section_title_attrs() {
		return array(
			'style' => array(
				'typography' => array(
					'fontSize'      => '40px',
					'lineHeight'     => '1.1',
					'letterSpacing' => '0',
					'fontWeight'    => '500',
				),
			),
		);
	}

	/**
	 * Card wrapper attrs.
	 *
	 * @return array<string,mixed>
	 */
	private function pattern_card_attrs() {
		return array(
			'style' => array(
				'spacing' => array(
					'padding' => array(
						'top'    => '28px',
						'right'  => '28px',
						'bottom' => '28px',
						'left'   => '28px',
					),
				),
				'border'  => array(
					'color'  => '#dddddd',
					'width'  => '1px',
					'radius' => '20px',
				),
				'color'   => array(
					'background' => '#ffffff',
				),
			),
		);
	}

	/**
	 * Top-line card attrs.
	 *
	 * @return array<string,mixed>
	 */
	private function pattern_line_card_attrs() {
		return array(
			'style' => array(
				'spacing' => array(
					'padding' => array(
						'top'    => '28px',
						'right'  => '0',
						'bottom' => '0',
						'left'   => '0',
					),
				),
				'border'  => array(
					'top' => array(
						'color' => '#111111',
						'width' => '1px',
					),
				),
			),
		);
	}

	/**
	 * Hero dashboard card attrs.
	 *
	 * @return array<string,mixed>
	 */
	private function pattern_dashboard_card_attrs() {
		return array(
			'style' => array(
				'spacing' => array(
					'padding' => array(
						'top'    => '28px',
						'right'  => '28px',
						'bottom' => '28px',
						'left'   => '28px',
					),
				),
				'border'  => array(
					'color'  => '#dcdcdc',
					'width'  => '1px',
					'radius' => '24px',
				),
				'color'   => array(
					'background' => '#ffffff',
				),
			),
		);
	}

	/**
	 * Dashboard row attrs.
	 *
	 * @return array<string,mixed>
	 */
	private function pattern_dashboard_row_attrs() {
		return array(
			'style' => array(
				'spacing' => array(
					'padding' => array(
						'top'    => '12px',
						'right'  => '0',
						'bottom' => '0',
						'left'   => '0',
					),
				),
				'border'  => array(
					'top' => array(
						'color' => '#e5e5e5',
						'width' => '1px',
					),
				),
			),
		);
	}

	/**
	 * Card title attrs.
	 *
	 * @return array<string,mixed>
	 */
	private function pattern_card_title_attrs() {
		return array(
			'style' => array(
				'typography' => array(
					'fontSize'      => '22px',
					'lineHeight'     => '1.2',
					'letterSpacing' => '0',
					'fontWeight'    => '500',
				),
			),
		);
	}

	/**
	 * Card body text attrs.
	 *
	 * @return array<string,mixed>
	 */
	private function pattern_card_text_attrs() {
		return array(
			'style' => array(
				'color'      => array( 'text' => '#454545' ),
				'typography' => array(
					'fontSize'  => '16px',
					'lineHeight' => '1.55',
				),
			),
		);
	}

	/**
	 * Small muted text attrs.
	 *
	 * @return array<string,mixed>
	 */
	private function pattern_small_muted_text_attrs() {
		return array(
			'style' => array(
				'color'      => array( 'text' => '#666666' ),
				'typography' => array(
					'fontSize'  => '14px',
					'lineHeight' => '1.4',
				),
			),
		);
	}

	/**
	 * Small strong text attrs.
	 *
	 * @return array<string,mixed>
	 */
	private function pattern_small_strong_text_attrs() {
		return array(
			'style' => array(
				'color'      => array( 'text' => '#111111' ),
				'typography' => array(
					'fontSize'   => '15px',
					'lineHeight'  => '1.4',
					'fontWeight' => '600',
				),
			),
		);
	}

	/**
	 * Buttons layout attrs.
	 *
	 * @return array<string,mixed>
	 */
	private function pattern_buttons_attrs( $justify = 'left' ) {
		return array(
			'layout' => array(
				'type'           => 'flex',
				'justifyContent' => sanitize_key( (string) $justify ),
			),
			'style'  => array(
				'spacing' => array(
					'blockGap' => '12px',
				),
			),
		);
	}

	/**
	 * Primary button attrs.
	 *
	 * @return array<string,mixed>
	 */
	private function pattern_primary_button_attrs() {
		return array(
			'style' => array(
				'border'  => array( 'radius' => '999px' ),
				'color'   => array(
					'background' => '#111111',
					'text'       => '#ffffff',
				),
				'spacing' => array(
					'padding' => array(
						'top'    => '14px',
						'right'  => '24px',
						'bottom' => '14px',
						'left'   => '24px',
					),
				),
			),
		);
	}

	/**
	 * Secondary button attrs.
	 *
	 * @return array<string,mixed>
	 */
	private function pattern_secondary_button_attrs() {
		return array(
			'style' => array(
				'border'  => array(
					'color'  => '#111111',
					'width'  => '1px',
					'radius' => '999px',
				),
				'color'   => array(
					'background' => '#ffffff',
					'text'       => '#111111',
				),
				'spacing' => array(
					'padding' => array(
						'top'    => '14px',
						'right'  => '24px',
						'bottom' => '14px',
						'left'   => '24px',
					),
				),
			),
		);
	}

	/**
	 * Columns layout attrs.
	 *
	 * @return array<string,mixed>
	 */
	private function pattern_columns_attrs( $gap = '24px' ) {
		return array(
			'isStackedOnMobile' => true,
			'style'             => array(
				'spacing' => array(
					'blockGap' => (string) $gap,
				),
			),
		);
	}

	/**
	 * Media-text layout attrs.
	 *
	 * @return array<string,mixed>
	 */
	private function pattern_media_text_attrs() {
		return array(
			'style' => array(
				'spacing' => array(
					'blockGap' => '40px',
				),
			),
		);
	}

	/**
	 * FAQ list wrapper attrs.
	 *
	 * @return array<string,mixed>
	 */
	private function pattern_faq_list_attrs() {
		return array(
			'layout' => array(
				'type'        => 'constrained',
				'contentSize' => '880px',
			),
			'style'  => array(
				'spacing' => array(
					'blockGap' => '0',
				),
			),
		);
	}

	/**
	 * FAQ item attrs.
	 *
	 * @return array<string,mixed>
	 */
	private function pattern_faq_item_attrs() {
		return array(
			'style' => array(
				'spacing' => array(
					'padding' => array(
						'top'    => '20px',
						'right'  => '0',
						'bottom' => '20px',
						'left'   => '0',
					),
				),
				'border'  => array(
					'top' => array(
						'color' => '#dddddd',
						'width' => '1px',
					),
				),
			),
		);
	}

	/**
	 * Normalizes text with fallback.
	 *
	 * @param mixed  $value Value.
	 * @param string $fallback Fallback.
	 * @return string
	 */
	private function pattern_text( $value, $fallback ) {
		$text = sanitize_text_field( (string) $value );
		return '' !== $text ? $text : sanitize_text_field( (string) $fallback );
	}

	/**
	 * Normalizes a landing page research brief into bounded page-planning hints.
	 *
	 * @param mixed $brief Raw research brief.
	 * @return array<string,mixed>
	 */
	private function pattern_research_brief( $brief ) {
		$brief = is_array( $brief ) ? $brief : array();
		if ( 'landing_page_research_brief' !== sanitize_key( (string) ( $brief['artifact_type'] ?? '' ) ) ) {
			return array();
		}
		if ( false !== (bool) ( $brief['direct_wordpress_write'] ?? false ) ) {
			return array();
		}

		return array(
			'artifact_type'                 => 'landing_page_research_brief',
			'write_posture'                 => sanitize_key( (string) ( $brief['write_posture'] ?? 'suggestion_only' ) ),
			'direct_wordpress_write'        => false,
			'source_count'                  => absint( $brief['source_count'] ?? 0 ),
			'section_patterns'              => $this->pattern_items( $brief['section_patterns'] ?? array(), array(), 0 ),
			'visual_asset_recommendations' => $this->pattern_items( $brief['visual_asset_recommendations'] ?? array(), array(), 0 ),
			'proof_points'                  => $this->pattern_items( $brief['proof_points'] ?? array(), array(), 0 ),
			'comparison_angles'             => $this->pattern_items( $brief['comparison_angles'] ?? array(), array(), 0 ),
			'faq_seed_questions'            => $this->pattern_faq_seed_items( $brief['faq_seed_questions'] ?? array() ),
		);
	}

	/**
	 * Returns a compact public-safe research brief summary.
	 *
	 * @param array<string,mixed> $brief Normalized research brief.
	 * @return array<string,mixed>
	 */
	private function pattern_research_brief_summary( array $brief ) {
		$research_backed = ! empty( $brief );
		return array(
			'research_backed'                      => $research_backed,
			'artifact_type'                        => $research_backed ? 'landing_page_research_brief' : '',
			'source_count'                         => absint( $brief['source_count'] ?? 0 ),
			'section_pattern_count'                => count( (array) ( $brief['section_patterns'] ?? array() ) ),
			'visual_asset_recommendation_count'   => count( (array) ( $brief['visual_asset_recommendations'] ?? array() ) ),
			'proof_point_count'                    => count( (array) ( $brief['proof_points'] ?? array() ) ),
			'comparison_angle_count'               => count( (array) ( $brief['comparison_angles'] ?? array() ) ),
			'faq_seed_question_count'              => count( (array) ( $brief['faq_seed_questions'] ?? array() ) ),
			'reference_copying_allowed'            => false,
			'direct_wordpress_write'               => false,
		);
	}

	/**
	 * Normalizes FAQ seed questions into title/description items.
	 *
	 * @param mixed $items Raw FAQ seeds.
	 * @return array<int,array<string,string>>
	 */
	private function pattern_faq_seed_items( $items ) {
		$items      = is_array( $items ) ? array_values( $items ) : array();
		$normalized = array();
		foreach ( $items as $item ) {
			$item        = is_array( $item ) ? $item : array();
			$title       = $this->pattern_text( $item['title'] ?? ( $item['question'] ?? '' ), '' );
			$description = $this->pattern_text( $item['description'] ?? ( $item['answer'] ?? '' ), '用审查后的页面证据回答这个问题。' );
			if ( '' === $title || '' === $description ) {
				continue;
			}
			$normalized[] = array(
				'title'       => $title,
				'description' => $description,
			);
			if ( count( $normalized ) >= 4 ) {
				break;
			}
		}
		return $normalized;
	}

	/**
	 * Merges item lists without duplicate titles.
	 *
	 * @param array<int,array<string,string>> $primary Primary items.
	 * @param array<int,array<string,string>> $fallback Fallback items.
	 * @param int                             $min_items Minimum item count before stopping fallback.
	 * @return array<int,array<string,string>>
	 */
	private function pattern_merge_item_lists( array $primary, array $fallback, $min_items ) {
		$merged = array();
		foreach ( array_merge( $primary, $fallback ) as $item ) {
			$title       = $this->pattern_text( $item['title'] ?? '', '' );
			$description = $this->pattern_text( $item['description'] ?? '', '' );
			if ( '' === $title || '' === $description ) {
				continue;
			}
			$already_present = false;
			foreach ( $merged as $existing ) {
				if ( $title === (string) ( $existing['title'] ?? '' ) ) {
					$already_present = true;
					break;
				}
			}
			if ( ! $already_present ) {
				$merged[] = array(
					'title'       => $title,
					'description' => $description,
				);
			}
			if ( count( $merged ) >= 6 ) {
				break;
			}
		}
		return array_slice( $merged, 0, max( 0, (int) $min_items, count( $primary ) ) );
	}

	/**
	 * Returns a title from the first research item when available.
	 *
	 * @param array<int,array<string,string>> $items Items.
	 * @param string                          $fallback Fallback.
	 * @return string
	 */
	private function pattern_research_item_title( array $items, $fallback ) {
		return $this->pattern_text( $items[0]['title'] ?? '', $fallback );
	}

	/**
	 * Returns a description from the first research item when available.
	 *
	 * @param array<int,array<string,string>> $items Items.
	 * @param string                          $fallback Fallback.
	 * @return string
	 */
	private function pattern_research_item_description( array $items, $fallback ) {
		return $this->pattern_text( $items[0]['description'] ?? '', $fallback );
	}

	/**
	 * Normalizes variable item arrays.
	 *
	 * @param mixed                         $items Raw items.
	 * @param array<int,array<string,string>> $fallback Fallback items.
	 * @param int                           $min_items Minimum item count.
	 * @return array<int,array<string,string>>
	 */
	private function pattern_items( $items, array $fallback, $min_items = 1 ) {
		$items      = is_array( $items ) ? array_values( $items ) : array();
		$normalized = array();
		foreach ( $items as $item ) {
			$item        = is_array( $item ) ? $item : array();
			$title       = $this->pattern_text( $item['title'] ?? '', '' );
			$description = $this->pattern_text( $item['description'] ?? '', '' );
			if ( '' === $title || '' === $description ) {
				continue;
			}
			$normalized[] = array(
				'title'       => $title,
				'description' => $description,
			);
			if ( count( $normalized ) >= 6 ) {
				break;
			}
		}
		foreach ( $fallback as $item ) {
			if ( count( $normalized ) >= $min_items ) {
				break;
			}
			$title       = $this->pattern_text( $item['title'] ?? '', '' );
			$description = $this->pattern_text( $item['description'] ?? '', '' );
			if ( '' === $title || '' === $description ) {
				continue;
			}
			$already_present = false;
			foreach ( $normalized as $existing ) {
				if ( $title === (string) ( $existing['title'] ?? '' ) ) {
					$already_present = true;
					break;
				}
			}
			if ( ! $already_present ) {
				$normalized[] = array(
					'title'       => $title,
					'description' => $description,
				);
			}
		}
		return ! empty( $normalized ) ? array_slice( $normalized, 0, 6 ) : $fallback;
	}

	/**
	 * Returns deterministic Pattern quality signals for proposal preflight review.
	 *
	 * @param array<int,array<string,mixed>> $blocks Blocks.
	 * @return array<string,mixed>
	 */
	private function pattern_design_quality_summary( array $blocks, array $research_brief = array() ) {
		return array(
			'pattern_version'       => '2.0',
			'style_strategy'        => 'gutenberg_native',
			'uses_native_styles'    => true,
			'research_backed'       => ! empty( $research_brief ),
			'research_source_count' => absint( $research_brief['source_count'] ?? 0 ),
			'top_level_count'       => count( $blocks ),
			'section_count'         => count( $blocks ),
			'block_count'           => $this->count_pattern_blocks_recursive( $blocks ),
			'has_split_hero'        => $this->pattern_has_class_name( $blocks, 'npcink-ai-hero-layout' ),
			'has_dashboard_mock'    => $this->pattern_has_class_name( $blocks, 'npcink-ai-dashboard-card' ),
			'has_proof_strip'       => $this->pattern_has_class_name( $blocks, 'npcink-ai-proof-strip' ),
			'has_media_text'        => $this->pattern_has_block_name( $blocks, 'core/media-text' ),
			'has_comparison_section' => $this->pattern_has_class_name( $blocks, 'npcink-ai-comparison' ),
			'has_faq'               => $this->pattern_has_block_name( $blocks, 'core/details' ),
			'has_final_cta'         => $this->pattern_has_class_name( $blocks, 'npcink-ai-final-cta' ),
			'has_columns'           => $this->pattern_has_block_name( $blocks, 'core/columns' ),
			'custom_css_required'   => false,
		);
	}

	/**
	 * Returns deterministic responsive quality signals for proposal preflight review.
	 *
	 * @param array<int,array<string,mixed>> $blocks Blocks.
	 * @param string                         $responsive_profile Responsive profile.
	 * @return array<string,mixed>
	 */
	private function pattern_responsive_quality_summary( array $blocks, $responsive_profile ) {
		return array(
			'responsive_profile'           => sanitize_key( (string) $responsive_profile ),
			'uses_mobile_stack'            => $this->pattern_all_columns_stack_on_mobile( $blocks ),
			'uses_core_responsive_blocks'  => $this->pattern_has_block_name( $blocks, 'core/columns' ) || $this->pattern_has_block_name( $blocks, 'core/media-text' ),
			'has_media_section'            => $this->pattern_has_block_name( $blocks, 'core/media-text' ),
			'has_faq'                      => $this->pattern_has_block_name( $blocks, 'core/details' ),
			'max_columns_per_row'          => $this->pattern_max_columns_per_row( $blocks ),
			'button_groups_use_flex_layout' => $this->pattern_button_groups_use_flex_layout( $blocks ),
			'custom_css_required'          => false,
		);
	}

	/**
	 * Returns whether a block tree contains a block name.
	 *
	 * @param array<int,array<string,mixed>> $blocks Blocks.
	 * @param string                         $block_name Block name.
	 * @return bool
	 */
	private function pattern_has_block_name( array $blocks, $block_name ) {
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			if ( $block_name === (string) ( $block['blockName'] ?? '' ) ) {
				return true;
			}
			if ( $this->pattern_has_block_name( is_array( $block['innerBlocks'] ?? null ) ? $block['innerBlocks'] : array(), $block_name ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns whether a block tree contains a class token.
	 *
	 * @param array<int,array<string,mixed>> $blocks Blocks.
	 * @param string                         $class_name Class name.
	 * @return bool
	 */
	private function pattern_has_class_name( array $blocks, $class_name ) {
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			$attrs   = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : array();
			$classes = preg_split( '/\s+/', (string) ( $attrs['className'] ?? '' ) );
			if ( in_array( $class_name, is_array( $classes ) ? $classes : array(), true ) ) {
				return true;
			}
			if ( $this->pattern_has_class_name( is_array( $block['innerBlocks'] ?? null ) ? $block['innerBlocks'] : array(), $class_name ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns whether every columns block is configured to stack on mobile.
	 *
	 * @param array<int,array<string,mixed>> $blocks Blocks.
	 * @return bool
	 */
	private function pattern_all_columns_stack_on_mobile( array $blocks ) {
		$found_columns = false;
		return $this->pattern_all_columns_stack_on_mobile_walk( $blocks, $found_columns ) && $found_columns;
	}

	/**
	 * Walks columns blocks and tracks whether they stack on mobile.
	 *
	 * @param array<int,array<string,mixed>> $blocks Blocks.
	 * @param bool                           $found_columns Whether a columns block was found.
	 * @return bool
	 */
	private function pattern_all_columns_stack_on_mobile_walk( array $blocks, &$found_columns ) {
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			if ( 'core/columns' === (string) ( $block['blockName'] ?? '' ) ) {
				$found_columns = true;
				$attrs         = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : array();
				if ( true !== ( $attrs['isStackedOnMobile'] ?? null ) ) {
					return false;
				}
			}
			if ( ! $this->pattern_all_columns_stack_on_mobile_walk( is_array( $block['innerBlocks'] ?? null ) ? $block['innerBlocks'] : array(), $found_columns ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Returns the largest direct column count inside a columns block.
	 *
	 * @param array<int,array<string,mixed>> $blocks Blocks.
	 * @return int
	 */
	private function pattern_max_columns_per_row( array $blocks ) {
		$max = 0;
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			if ( 'core/columns' === (string) ( $block['blockName'] ?? '' ) ) {
				$column_count = 0;
				foreach ( is_array( $block['innerBlocks'] ?? null ) ? $block['innerBlocks'] : array() as $inner_block ) {
					if ( is_array( $inner_block ) && 'core/column' === (string) ( $inner_block['blockName'] ?? '' ) ) {
						++$column_count;
					}
				}
				$max = max( $max, $column_count );
			}
			$max = max( $max, $this->pattern_max_columns_per_row( is_array( $block['innerBlocks'] ?? null ) ? $block['innerBlocks'] : array() ) );
		}
		return $max;
	}

	/**
	 * Returns whether button groups use core flex layout attrs.
	 *
	 * @param array<int,array<string,mixed>> $blocks Blocks.
	 * @return bool
	 */
	private function pattern_button_groups_use_flex_layout( array $blocks ) {
		$found_buttons = false;
		return $this->pattern_button_groups_use_flex_layout_walk( $blocks, $found_buttons ) && $found_buttons;
	}

	/**
	 * Walks button groups and tracks whether they use flex layout.
	 *
	 * @param array<int,array<string,mixed>> $blocks Blocks.
	 * @param bool                           $found_buttons Whether a buttons block was found.
	 * @return bool
	 */
	private function pattern_button_groups_use_flex_layout_walk( array $blocks, &$found_buttons ) {
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			if ( 'core/buttons' === (string) ( $block['blockName'] ?? '' ) ) {
				$found_buttons = true;
				$attrs         = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : array();
				$layout        = is_array( $attrs['layout'] ?? null ) ? $attrs['layout'] : array();
				if ( 'flex' !== (string) ( $layout['type'] ?? '' ) ) {
					return false;
				}
			}
			if ( ! $this->pattern_button_groups_use_flex_layout_walk( is_array( $block['innerBlocks'] ?? null ) ? $block['innerBlocks'] : array(), $found_buttons ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Builds the core/group wrapper classes expected by Gutenberg save().
	 *
	 * @param array<string,mixed> $attrs Block attrs.
	 * @return string
	 */
	private function pattern_group_classes( array $attrs ) {
		$classes = array( 'wp-block-group' );
		$align   = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) ( $attrs['align'] ?? '' ) );
		$align   = is_string( $align ) ? $align : '';
		if ( '' !== $align ) {
			$classes[] = 'align' . $align;
		}
		$class_name = $this->pattern_class_names( (string) ( $attrs['className'] ?? '' ) );
		foreach ( preg_split( '/\s+/', $class_name ) ?: array() as $class ) {
			if ( '' !== $class ) {
				$classes[] = $class;
			}
		}
		$style = is_array( $attrs['style'] ?? null ) ? $attrs['style'] : array();
		$color = is_array( $style['color'] ?? null ) ? $style['color'] : array();
		$border = is_array( $style['border'] ?? null ) ? $style['border'] : array();
		if ( ! empty( $border['color'] ) ) {
			$classes[] = 'has-border-color';
		}
		if ( ! empty( $color['text'] ) ) {
			$classes[] = 'has-text-color';
		}
		if ( ! empty( $color['background'] ) ) {
			$classes[] = 'has-background';
		}
		return implode( ' ', array_values( array_unique( $classes ) ) );
	}

	/**
	 * Builds the core/button link classes expected by Gutenberg save().
	 *
	 * @param array<string,mixed> $attrs Block attrs.
	 * @return string
	 */
	private function pattern_button_link_classes( array $attrs ) {
		$classes = array( 'wp-block-button__link' );
		$style   = is_array( $attrs['style'] ?? null ) ? $attrs['style'] : array();
		$color   = is_array( $style['color'] ?? null ) ? $style['color'] : array();
		$border  = is_array( $style['border'] ?? null ) ? $style['border'] : array();
		if ( ! empty( $color['text'] ) ) {
			$classes[] = 'has-text-color';
		}
		if ( ! empty( $color['background'] ) ) {
			$classes[] = 'has-background';
		}
		if ( ! empty( $border['color'] ) ) {
			$classes[] = 'has-border-color';
		}
		$classes[] = 'wp-element-button';
		return implode( ' ', $classes );
	}

	/**
	 * Serializes common Gutenberg support attrs into the save() style order.
	 *
	 * @param array<string,mixed> $attrs Block attrs.
	 * @return string
	 */
	private function pattern_style_from_attrs( array $attrs ) {
		$style = is_array( $attrs['style'] ?? null ) ? $attrs['style'] : array();
		$css   = array();

		$border = is_array( $style['border'] ?? null ) ? $style['border'] : array();
		foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
			$side_border = is_array( $border[ $side ] ?? null ) ? $border[ $side ] : array();
			if ( ! empty( $side_border['color'] ) ) {
				$css[] = 'border-' . $side . '-color:' . (string) $side_border['color'];
			}
			if ( ! empty( $side_border['width'] ) ) {
				$css[] = 'border-' . $side . '-width:' . (string) $side_border['width'];
			}
		}
		if ( ! empty( $border['color'] ) ) {
			$css[] = 'border-color:' . (string) $border['color'];
		}
		if ( ! empty( $border['width'] ) ) {
			$css[] = 'border-width:' . (string) $border['width'];
		}
		if ( ! empty( $border['radius'] ) ) {
			$css[] = 'border-radius:' . (string) $border['radius'];
		}

		$color = is_array( $style['color'] ?? null ) ? $style['color'] : array();
		if ( ! empty( $color['background'] ) ) {
			$css[] = 'background-color:' . (string) $color['background'];
		}
		if ( ! empty( $color['text'] ) ) {
			$css[] = 'color:' . (string) $color['text'];
		}

		$typography = is_array( $style['typography'] ?? null ) ? $style['typography'] : array();
		if ( ! empty( $typography['fontSize'] ) ) {
			$css[] = 'font-size:' . (string) $typography['fontSize'];
		}
		if ( ! empty( $typography['fontWeight'] ) ) {
			$css[] = 'font-weight:' . (string) $typography['fontWeight'];
		}
		if ( isset( $typography['letterSpacing'] ) && '' !== (string) $typography['letterSpacing'] ) {
			$css[] = 'letter-spacing:' . (string) $typography['letterSpacing'];
		}
		if ( ! empty( $typography['lineHeight'] ) ) {
			$css[] = 'line-height:' . (string) $typography['lineHeight'];
		}
		if ( ! empty( $typography['textTransform'] ) ) {
			$css[] = 'text-transform:' . (string) $typography['textTransform'];
		}

		$spacing = is_array( $style['spacing'] ?? null ) ? $style['spacing'] : array();
		$padding = is_array( $spacing['padding'] ?? null ) ? $spacing['padding'] : array();
		foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
			if ( isset( $padding[ $side ] ) && '' !== (string) $padding[ $side ] ) {
				$css[] = 'padding-' . $side . ':' . (string) $padding[ $side ];
			}
		}

		return implode( ';', $css );
	}

	/**
	 * Filters class names to the pattern whitelist.
	 *
	 * @param string $class_names Class names.
	 * @return string
	 */
	private function pattern_class_names( $class_names ) {
		$allowed = array_flip( $this->pattern_allowed_classes() );
		$classes = preg_split( '/\s+/', (string) $class_names );
		$kept    = array();
		foreach ( is_array( $classes ) ? $classes : array() as $class_name ) {
			$class_name = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $class_name );
			$class_name = is_string( $class_name ) ? $class_name : '';
			if ( '' !== $class_name && isset( $allowed[ $class_name ] ) ) {
				$kept[] = $class_name;
			}
		}
		return implode( ' ', array_values( array_unique( $kept ) ) );
	}

	/**
	 * Escapes an HTML attribute value.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private function pattern_attr( $value ) {
		return function_exists( 'esc_attr' ) ? esc_attr( $value ) : htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Counts nested pattern blocks.
	 *
	 * @param array<int,array<string,mixed>> $blocks Blocks.
	 * @return int
	 */
	private function count_pattern_blocks_recursive( array $blocks ) {
		$count = 0;
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			++$count;
			$count += $this->count_pattern_blocks_recursive( is_array( $block['innerBlocks'] ?? null ) ? $block['innerBlocks'] : array() );
		}
		return $count;
	}
}
