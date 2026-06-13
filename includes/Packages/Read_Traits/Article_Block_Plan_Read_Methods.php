<?php
/**
 * Article block planning methods for Core_Read_Package.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds proposal-ready editorial Gutenberg article plans without writing content.
 */
trait Article_Block_Plan_Read_Methods {
	/**
	 * Builds one governed Gutenberg article block write plan.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function build_article_block_plan( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to plan article drafts.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$post_type          = sanitize_key( (string) ( $input['post_type'] ?? 'post' ) );
		$status             = sanitize_key( (string) ( $input['status'] ?? 'draft' ) );
		$article_template   = sanitize_key( (string) ( $input['article_template'] ?? 'editorial-longform' ) );
		$responsive_profile = sanitize_key( (string) ( $input['responsive_profile'] ?? 'article_standard' ) );
		$media_strategy     = sanitize_key( (string) ( $input['media_strategy'] ?? 'none' ) );
		$target_post_id     = absint( $input['target_post_id'] ?? 0 );

		if ( 'post' !== $post_type ) {
			return new \WP_Error( 'npcink_abilities_toolkit_article_block_post_type_invalid', __( 'Article block plans currently support post_type=post only.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( 'draft' !== $status ) {
			return new \WP_Error( 'npcink_abilities_toolkit_article_block_status_invalid', __( 'Article block plans currently support status=draft only.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( ! in_array( $article_template, array( 'editorial-longform', 'how-to-guide', 'comparison-review' ), true ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_article_block_template_invalid', __( 'Article template is not supported.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( 'article_standard' !== $responsive_profile ) {
			return new \WP_Error( 'npcink_abilities_toolkit_article_block_responsive_profile_invalid', __( 'Responsive profile is not supported.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( ! in_array( $media_strategy, array( 'none', 'existing_media_url' ), true ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_article_block_media_strategy_invalid', __( 'Media strategy is not supported.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		$target_post = null;
		if ( $target_post_id > 0 ) {
			$target_post = get_post( $target_post_id );
			if ( ! is_object( $target_post ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_article_block_target_not_found', __( 'Target post was not found.', 'npcink-abilities-toolkit' ), array( 'status' => 404 ) );
			}
			if ( ! current_user_can( 'edit_post', $target_post_id ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to update the target post.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
			}
			if ( 'post' !== sanitize_key( (string) ( $target_post->post_type ?? '' ) ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_article_block_target_type_invalid', __( 'Target post must be a post.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
			}
			if ( 'draft' !== sanitize_key( (string) ( $target_post->post_status ?? '' ) ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_article_block_target_status_invalid', __( 'Target post must be a draft.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
			}
		}

		$variables = $this->article_block_normalized_variables( $input );
		$title     = $this->article_block_text( $input['title'] ?? ( $variables['title'] ?? 'Gutenberg Article Draft' ), 'Gutenberg Article Draft' );
		$blocks    = $this->render_editorial_article_blocks(
			$article_template,
			$variables,
			array(
				'responsive_profile' => $responsive_profile,
				'media_strategy'     => $media_strategy,
			)
		);
		$editorial_quality         = $this->article_block_editorial_quality_summary( $blocks, $article_template );
		$responsive_quality        = $this->article_block_responsive_quality_summary( $blocks, $responsive_profile );
		$block_editor_review       = $this->pattern_review_summary_for_blocks( $blocks, true );
		$block_editor_quality_gate = $this->block_editor_plan_quality_gate(
			$block_editor_review,
			array(
				'profile'      => 'article_editor_safety',
				'surface_kind' => 'post_content',
				'editor'       => 'block_editor',
				'post_type'    => 'post',
				'target_mode'  => $target_post_id > 0 ? 'update_existing' : 'create_draft',
			)
		);
		$batch_seed         = wp_json_encode( array( $title, $article_template, $responsive_profile, $media_strategy, $target_post_id, $variables ) );
		$batch_id           = 'article_block_' . substr( md5( is_string( $batch_seed ) ? $batch_seed : $title ), 0, 12 );

		if ( $target_post_id > 0 ) {
			$write_actions = array(
				$this->build_plan_action(
					'update-article-blocks',
					'npcink-abilities-toolkit/update-post-blocks',
					array(
						'post_id'            => $target_post_id,
						'mode'               => 'replace',
						'validate_roundtrip' => true,
						'blocks'             => $blocks,
					),
					array( 'post.write' ),
					'medium',
					__( 'Replace the existing draft post body with reviewed Gutenberg-native editorial blocks.', 'npcink-abilities-toolkit' )
				),
			);
		} else {
			$write_actions = array(
				$this->build_plan_action(
					'create-article-draft',
					'npcink-abilities-toolkit/create-draft',
					array(
						'post_type'      => 'post',
						'status'         => 'draft',
						'title'          => $title,
						'content'        => '',
						'content_format' => 'html',
						'meta'           => array(
							'npcink_article_template'   => $article_template,
							'npcink_responsive_profile' => $responsive_profile,
						),
					),
					array( 'post.write' ),
					'medium',
					__( 'Create a draft post before applying reviewed Gutenberg article blocks.', 'npcink-abilities-toolkit' )
				),
				$this->build_plan_action(
					'update-article-blocks',
					'npcink-abilities-toolkit/update-post-blocks',
					array(
						'post_id'            => '$outputs.create-article-draft.post_id',
						'mode'               => 'replace',
						'validate_roundtrip' => true,
						'blocks'             => $blocks,
					),
					array( 'post.write' ),
					'medium',
					__( 'Replace the draft post body with reviewed Gutenberg-native editorial blocks.', 'npcink-abilities-toolkit' )
				),
			);
		}

		return $this->build_analysis_success_response(
			array(
				'artifact_type'          => 'article_block_plan',
				'composition_role'       => 'core_article_block_plan',
				'version'                => 1,
				'article_template'       => $article_template,
				'responsive_profile'     => $responsive_profile,
				'media_strategy'         => $media_strategy,
				'block_editor_surface'   => array(
					'surface_kind'     => 'post_content',
					'editor'           => 'block_editor',
					'post_type'        => 'post',
					'target_mode'      => $target_post_id > 0 ? 'update_existing' : 'create_draft',
					'write_ability_id' => 'npcink-abilities-toolkit/update-post-blocks',
					'create_ability_id' => $target_post_id > 0 ? null : 'npcink-abilities-toolkit/create-draft',
				),
				'target_post'            => $target_post_id > 0 ? array(
					'mode'      => 'update_existing',
					'post_id'   => $target_post_id,
					'post_type' => sanitize_key( (string) ( $target_post->post_type ?? 'post' ) ),
					'status'    => sanitize_key( (string) ( $target_post->post_status ?? 'draft' ) ),
					'title'     => sanitize_text_field( (string) ( $target_post->post_title ?? '' ) ),
				) : array(
					'mode'      => 'create_draft',
					'post_id'   => 0,
					'post_type' => 'post',
					'status'    => 'draft',
					'title'     => $title,
				),
				'write_posture'          => 'core_proposal_handoff',
				'direct_wordpress_write' => false,
				'requires_approval'      => true,
				'dry_run'                => true,
					'commit_execution'       => false,
					'proposal_mode'          => 'batch',
					'batch_id'               => $batch_id,
					'summary'                => array(
						'title'        => $title,
						'block_count'  => $this->article_block_count_recursive( $blocks ),
						'action_count' => count( $write_actions ),
					),
					'editorial_quality'      => $editorial_quality,
					'responsive_quality'     => $responsive_quality,
					'block_editor_review'    => $this->block_editor_plan_review_excerpt( $block_editor_review ),
					'block_editor_quality_gate' => $block_editor_quality_gate,
					'write_actions'          => $write_actions,
				'handoff'                => array(
					'plan_ability_id'        => 'npcink-abilities-toolkit/build-article-block-plan',
					'recipe_id'              => 'article_block_v1',
					'recipe_ref'             => 'workflow/article_block_plan',
					'core_route'             => '/wp-json/npcink-governance-core/v1/proposals/from-plan',
					'final_write_path'       => 'core_proposal_required',
					'direct_wordpress_write' => false,
				),
			),
			array(
				'source'         => 'local_article_block_plan',
				'execution_mode' => 'deterministic',
			),
			'Article block plan built.'
		);
	}

	/**
	 * Normalizes article plan variables and accepts common top-level media aliases.
	 *
	 * @param array<string,mixed> $input Input args.
	 * @return array<string,mixed>
	 */
	private function article_block_normalized_variables( array $input ): array {
		$variables = is_array( $input['variables'] ?? null ) ? $input['variables'] : array();
		$media     = is_array( $input['hero_media'] ?? null ) ? $input['hero_media'] : ( is_array( $input['media'] ?? null ) ? $input['media'] : array() );

		if ( empty( $variables['hero_media_url'] ) ) {
			$media_url = $input['hero_media_url'] ?? ( $input['media_url'] ?? ( $media['url'] ?? '' ) );
			if ( '' !== (string) $media_url ) {
				$variables['hero_media_url'] = (string) $media_url;
			}
		}

		if ( empty( $variables['hero_media_attachment_id'] ) && empty( $variables['hero_media_id'] ) ) {
			$attachment_id = $input['hero_media_attachment_id'] ?? ( $input['media_attachment_id'] ?? ( $input['attachment_id'] ?? ( $input['hero_media_id'] ?? ( $input['media_id'] ?? ( $media['attachment_id'] ?? ( $media['id'] ?? 0 ) ) ) ) ) );
			if ( $this->article_media_attachment_id( $attachment_id ) > 0 ) {
				$variables['hero_media_attachment_id'] = $this->article_media_attachment_id( $attachment_id );
			}
		}

		if ( empty( $variables['hero_media_alt'] ) ) {
			$alt = $input['hero_media_alt'] ?? ( $input['media_alt'] ?? ( $input['alt'] ?? ( $media['alt'] ?? '' ) ) );
			if ( '' !== (string) $alt ) {
				$variables['hero_media_alt'] = (string) $alt;
			}
		}

		return $variables;
	}

	/**
	 * Renders editorial article blocks from whitelisted variables.
	 *
	 * @param string              $article_template Template id.
	 * @param array<string,mixed> $variables Variables.
	 * @param array<string,string> $options Options.
	 * @return array<int,array<string,mixed>>
	 */
	private function render_editorial_article_blocks( $article_template, array $variables, array $options = array() ) {
		$dek            = $this->article_block_text( $variables['dek'] ?? '', '这是一篇使用 Gutenberg 原生模块组织的文章草稿，便于编辑、审阅和移动端阅读。' );
		$intro          = $this->article_block_text( $variables['intro'] ?? '', '文章先给出背景和核心判断，再用清晰的小节、要点和 FAQ 帮助读者快速理解。' );
		$media_strategy = sanitize_key( (string) ( $options['media_strategy'] ?? 'none' ) );
		$hero_media_url = esc_url_raw( (string) ( $variables['hero_media_url'] ?? '' ) );
		$hero_media_attachment_id = $this->article_media_attachment_id( $variables['hero_media_attachment_id'] ?? ( $variables['hero_media_id'] ?? 0 ) );
		$hero_media_alt = $this->article_block_text( $variables['hero_media_alt'] ?? '', 'Article illustration' );
		$takeaways      = $this->article_block_strings(
			$variables['takeaways'] ?? array(),
			array(
				'Gutenberg 块让文章结构更清楚，后续编辑也更自然。',
				'图片、FAQ、对比区和步骤区应优先使用核心块，而不是自定义 HTML。',
				'写入仍然通过 proposal 审批和 Adapter allowlist 执行。',
			),
			3,
			6
		);
		$sections       = $this->article_block_sections(
			$variables['sections'] ?? array(),
			$this->article_block_default_sections( $article_template ),
			3,
			6
		);
		$faq            = $this->article_block_items(
			$variables['faq'] ?? array(),
			array(
				array(
					'title'       => '文章会直接发布吗？',
					'description' => '不会。计划只生成 draft 写入动作，仍需要 Core proposal 审批。',
				),
				array(
					'title'       => '编辑器里还能继续修改吗？',
					'description' => '可以。输出使用 WordPress 核心块，目标是让编辑器继续维护内容。',
				),
			),
			2,
			5
		);

		$blocks = array(
			$this->article_paragraph_block( $dek, $this->article_lede_attrs(), 'font-size:22px;line-height:1.45' ),
			$this->article_paragraph_block( $intro ),
		);

		if ( 'existing_media_url' === $media_strategy && '' !== $hero_media_url ) {
			$blocks[] = $this->article_image_block( $hero_media_url, $hero_media_alt, $hero_media_attachment_id );
		}

		$blocks[] = $this->article_group_block(
			array(
				$this->article_heading_block( '核心要点', 2 ),
				$this->article_list_block( $takeaways ),
			),
			$this->article_callout_attrs(),
			'background-color:#f7f7f4;border-color:#e5e5e5;border-width:1px;border-radius:16px;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px'
		);

		foreach ( $sections as $section ) {
			$blocks[] = $this->article_heading_block( (string) $section['title'], 2 );
			foreach ( $section['paragraphs'] as $paragraph ) {
				$blocks[] = $this->article_paragraph_block( (string) $paragraph );
			}
			if ( ! empty( $section['bullets'] ) ) {
				$blocks[] = $this->article_list_block( $section['bullets'] );
			}
		}

		if ( 'comparison-review' === $article_template ) {
			$blocks[] = $this->article_comparison_columns_block(
				$this->article_block_items(
					$variables['comparisons'] ?? array(),
					array(
						array(
							'title'       => '方案 A',
							'description' => '适合追求最小改动和快速上线的场景。',
						),
						array(
							'title'       => '方案 B',
							'description' => '适合需要更强结构化和长期可维护性的场景。',
						),
					),
					2,
					3
				)
			);
		}

		if ( ! empty( $variables['quote'] ) ) {
			$quote = is_array( $variables['quote'] ) ? $variables['quote'] : array( 'text' => (string) $variables['quote'] );
			$blocks[] = $this->article_quote_block(
				$this->article_block_text( $quote['text'] ?? '', '' ),
				$this->article_block_text( $quote['cite'] ?? '', '' )
			);
		}

		$blocks[] = $this->article_separator_block();
		$blocks[] = $this->article_heading_block( '常见问题', 2 );
		foreach ( $faq as $item ) {
			$blocks[] = $this->article_details_block( (string) $item['title'], (string) $item['description'] );
		}

		$conclusion = $this->article_block_text( $variables['conclusion'] ?? '', '下一步应先在草稿中审阅结构、图片和 FAQ，再进入正式编辑流程。' );
		$blocks[]   = $this->article_heading_block( '结论', 2 );
		$blocks[]   = $this->article_paragraph_block( $conclusion );

		return $blocks;
	}

	/**
	 * Default sections by article template.
	 *
	 * @param string $article_template Template id.
	 * @return array<int,array<string,mixed>>
	 */
	private function article_block_default_sections( $article_template ) {
		if ( 'how-to-guide' === $article_template ) {
			return array(
				array(
					'title'      => '准备工作',
					'paragraphs' => array( '先明确目标、输入材料和审核边界，避免把文章写入变成不可追踪的直接修改。' ),
					'bullets'    => array( '确认标题和读者意图', '准备已有媒体 URL 和 alt 文案', '保持 draft-only 写入' ),
				),
				array(
					'title'      => '执行步骤',
					'paragraphs' => array( '按章节组织内容，再把列表、图片和 FAQ 拆成独立 Gutenberg 核心块。' ),
					'bullets'    => array( '生成块计划', '提交 proposal', '审批后写入草稿', '回读块结构' ),
				),
				array(
					'title'      => '验收标准',
					'paragraphs' => array( '验收重点是编辑器不提示无效区块、移动端不横向溢出、图片和 FAQ 都能继续编辑。' ),
				),
			);
		}

		if ( 'comparison-review' === $article_template ) {
			return array(
				array(
					'title'      => '评估维度',
					'paragraphs' => array( '对比文章应先交代评价标准，再给出优劣势，而不是只堆结论。' ),
					'bullets'    => array( '可维护性', '编辑体验', '响应式表现', '治理边界' ),
				),
				array(
					'title'      => '主要差异',
					'paragraphs' => array( '把差异写进段落和对比卡片，帮助读者在移动端也能快速扫描。' ),
				),
				array(
					'title'      => '适用建议',
					'paragraphs' => array( '选择方案时优先考虑长期编辑成本和审批可追踪性，而不是短期视觉效果。' ),
				),
			);
		}

		return array(
			array(
				'title'      => '背景',
				'paragraphs' => array( 'Gutenberg 原生块可以把文章拆成可编辑、可回读、可审计的内容单元。' ),
			),
			array(
				'title'      => '为什么值得做',
				'paragraphs' => array( '文章不是只需要正文文本。图片、要点、引用、FAQ 和对比区都可以成为结构化块，提升阅读体验。' ),
				'bullets'    => array( '编辑器可继续维护', '移动端布局更稳定', '审稿时结构更清楚' ),
			),
			array(
				'title'      => '落地方式',
				'paragraphs' => array( 'AI 生成文章块计划，Core 接收 proposal，Adapter 只执行审批后的草稿和块更新动作。' ),
			),
		);
	}

	/**
	 * Builds a group block.
	 *
	 * @param array<int,array<string,mixed>> $inner_blocks Inner blocks.
	 * @param array<string,mixed>            $attrs Attrs.
	 * @param string                         $style_attr Style attribute.
	 * @return array<string,mixed>
	 */
	private function article_group_block( array $inner_blocks, array $attrs = array(), $style_attr = '' ) {
		$classes    = $this->article_group_classes( $attrs );
		$style_attr = $this->article_style_from_attrs( $attrs );
		$style_html = '' !== $style_attr ? ' style="' . $this->article_block_attr( $style_attr ) . '"' : '';
		return array(
			'blockName'    => 'core/group',
			'attrs'        => $attrs,
			'innerHTML'    => '<div class="' . $this->article_block_attr( $classes ) . '"' . $style_html . '></div>',
			'innerContent' => array_merge( array( '<div class="' . $this->article_block_attr( $classes ) . '"' . $style_html . '>' ), array_fill( 0, count( $inner_blocks ), null ), array( '</div>' ) ),
			'innerBlocks'  => array_values( $inner_blocks ),
		);
	}

	/**
	 * Builds a heading block.
	 *
	 * @param string $text Text.
	 * @param int    $level Level.
	 * @return array<string,mixed>
	 */
	private function article_heading_block( $text, $level ) {
		$level = max( 2, min( 4, (int) $level ) );
		$html  = '<h' . $level . ' class="wp-block-heading">' . esc_html( $text ) . '</h' . $level . '>';
		return array(
			'blockName'    => 'core/heading',
			'attrs'        => array( 'level' => $level ),
			'innerHTML'    => $html,
			'innerContent' => array( $html ),
			'innerBlocks'  => array(),
		);
	}

	/**
	 * Builds a paragraph block.
	 *
	 * @param string              $text Text.
	 * @param array<string,mixed> $attrs Attrs.
	 * @param string              $style_attr Style attribute.
	 * @return array<string,mixed>
	 */
	private function article_paragraph_block( $text, array $attrs = array(), $style_attr = '' ) {
		$style_attr = $this->article_style_from_attrs( $attrs );
		$style_html = '' !== $style_attr ? ' style="' . $this->article_block_attr( $style_attr ) . '"' : '';
		$html       = '<p' . $style_html . '>' . esc_html( $text ) . '</p>';
		return array(
			'blockName'    => 'core/paragraph',
			'attrs'        => $attrs,
			'innerHTML'    => $html,
			'innerContent' => array( $html ),
			'innerBlocks'  => array(),
		);
	}

	/**
	 * Builds a list block.
	 *
	 * @param string[] $items Items.
	 * @return array<string,mixed>
	 */
	private function article_list_block( array $items ) {
		$list_items = '';
		foreach ( $items as $item ) {
			$list_items .= '<li>' . esc_html( $item ) . '</li>';
		}
		$html = '<ul class="wp-block-list">' . $list_items . '</ul>';
		return array(
			'blockName'    => 'core/list',
			'attrs'        => array(),
			'innerHTML'    => $html,
			'innerContent' => array( $html ),
			'innerBlocks'  => array(),
		);
	}

	/**
	 * Builds an image block from an existing media URL.
	 *
	 * @param string $url URL.
	 * @param string $alt Alt text.
	 * @param int    $attachment_id Attachment id.
	 * @return array<string,mixed>
	 */
	private function article_image_block( $url, $alt, $attachment_id = 0 ) {
		$url           = esc_url_raw( (string) $url );
		$alt           = sanitize_text_field( (string) $alt );
		$attachment_id = $this->article_media_attachment_id( $attachment_id );
		$image_class   = $attachment_id > 0 ? ' class="wp-image-' . $attachment_id . '"' : '';
		$html          = '<figure class="wp-block-image size-large"><img src="' . $this->article_block_attr( $url ) . '" alt="' . $this->article_block_attr( $alt ) . '"' . $image_class . '/></figure>';
		$attrs         = array(
			'url'             => $url,
			'alt'             => $alt,
			'sizeSlug'        => 'large',
			'linkDestination' => 'none',
		);
		if ( $attachment_id > 0 ) {
			$attrs['id'] = $attachment_id;
		}
		return array(
			'blockName'    => 'core/image',
			'attrs'        => $attrs,
			'innerHTML'    => $html,
			'innerContent' => array( $html ),
			'innerBlocks'  => array(),
		);
	}

	/**
	 * Builds a responsive comparison columns block.
	 *
	 * @param array<int,array<string,string>> $items Items.
	 * @return array<string,mixed>
	 */
	private function article_comparison_columns_block( array $items ) {
		$columns = array();
		foreach ( $items as $item ) {
			$columns[] = array(
				'blockName'    => 'core/column',
				'attrs'        => array(),
				'innerHTML'    => '<div class="wp-block-column"></div>',
				'innerContent' => array( '<div class="wp-block-column">', null, '</div>' ),
				'innerBlocks'  => array(
					$this->article_group_block(
						array(
							$this->article_heading_block( (string) $item['title'], 3 ),
							$this->article_paragraph_block( (string) $item['description'] ),
						),
						$this->article_card_attrs(),
						'border-color:#dddddd;border-width:1px;border-radius:16px;padding-top:20px;padding-right:20px;padding-bottom:20px;padding-left:20px'
					),
				),
			);
		}

		return array(
			'blockName'    => 'core/columns',
			'attrs'        => array(
				'isStackedOnMobile' => true,
				'style'             => array(
					'spacing' => array( 'blockGap' => '20px' ),
				),
			),
			'innerHTML'    => '<div class="wp-block-columns"></div>',
			'innerContent' => array_merge( array( '<div class="wp-block-columns">' ), array_fill( 0, count( $columns ), null ), array( '</div>' ) ),
			'innerBlocks'  => $columns,
		);
	}

	/**
	 * Builds a quote block.
	 *
	 * @param string $text Text.
	 * @param string $cite Citation.
	 * @return array<string,mixed>
	 */
	private function article_quote_block( $text, $cite = '' ) {
		$text = $this->article_block_text( $text, '' );
		if ( '' === $text ) {
			return $this->article_paragraph_block( '' );
		}
		$cite_html = '' !== $cite ? '<cite>' . esc_html( $cite ) . '</cite>' : '';
		$html      = '<blockquote class="wp-block-quote"><p>' . esc_html( $text ) . '</p>' . $cite_html . '</blockquote>';
		return array(
			'blockName'    => 'core/quote',
			'attrs'        => array(),
			'innerHTML'    => $html,
			'innerContent' => array( $html ),
			'innerBlocks'  => array(),
		);
	}

	/**
	 * Builds a separator block.
	 *
	 * @return array<string,mixed>
	 */
	private function article_separator_block() {
		$html = '<hr class="wp-block-separator has-alpha-channel-opacity"/>';
		return array(
			'blockName'    => 'core/separator',
			'attrs'        => array(),
			'innerHTML'    => $html,
			'innerContent' => array( $html ),
			'innerBlocks'  => array(),
		);
	}

	/**
	 * Builds a details block.
	 *
	 * @param string $summary Summary.
	 * @param string $body Body.
	 * @return array<string,mixed>
	 */
	private function article_details_block( $summary, $body ) {
		$summary    = $this->article_block_text( $summary, '' );
		$body_block = $this->article_paragraph_block( $body );
		$open_html  = '<details class="wp-block-details"><summary>' . esc_html( $summary ) . '</summary>';
		return array(
			'blockName'    => 'core/details',
			'attrs'        => array( 'summary' => $summary ),
			'innerHTML'    => $open_html . '</details>',
			'innerContent' => array( $open_html, null, '</details>' ),
			'innerBlocks'  => array( $body_block ),
		);
	}

	/**
	 * Lede typography attrs.
	 *
	 * @return array<string,mixed>
	 */
	private function article_lede_attrs() {
		return array(
			'style' => array(
				'typography' => array(
					'fontSize'  => '22px',
					'lineHeight' => '1.45',
				),
			),
		);
	}

	/**
	 * Callout group attrs.
	 *
	 * @return array<string,mixed>
	 */
	private function article_callout_attrs() {
		return array(
			'style' => array(
				'spacing' => array(
					'padding' => array(
						'top'    => '24px',
						'right'  => '24px',
						'bottom' => '24px',
						'left'   => '24px',
					),
				),
				'border'  => array(
					'color'  => '#e5e5e5',
					'width'  => '1px',
					'radius' => '16px',
				),
				'color'   => array(
					'background' => '#f7f7f4',
				),
			),
		);
	}

	/**
	 * Card group attrs.
	 *
	 * @return array<string,mixed>
	 */
	private function article_card_attrs() {
		return array(
			'style' => array(
				'spacing' => array(
					'padding' => array(
						'top'    => '20px',
						'right'  => '20px',
						'bottom' => '20px',
						'left'   => '20px',
					),
				),
				'border'  => array(
					'color'  => '#dddddd',
					'width'  => '1px',
					'radius' => '16px',
				),
			),
		);
	}

	/**
	 * Normalizes text.
	 *
	 * @param mixed  $value Value.
	 * @param string $fallback Fallback.
	 * @return string
	 */
	private function article_block_text( $value, $fallback ) {
		$text = sanitize_text_field( (string) $value );
		return '' !== $text ? $text : sanitize_text_field( (string) $fallback );
	}

	/**
	 * Normalizes string list input.
	 *
	 * @param mixed    $items Items.
	 * @param string[] $fallback Fallback.
	 * @param int      $min_items Min item count.
	 * @param int      $max_items Max item count.
	 * @return string[]
	 */
	private function article_block_strings( $items, array $fallback, $min_items, $max_items ) {
		$items      = is_array( $items ) ? array_values( $items ) : array();
		$normalized = array();
		foreach ( $items as $item ) {
			$text = $this->article_block_text( $item, '' );
			if ( '' !== $text ) {
				$normalized[] = $text;
			}
			if ( count( $normalized ) >= $max_items ) {
				break;
			}
		}
		foreach ( $fallback as $item ) {
			if ( count( $normalized ) >= $min_items ) {
				break;
			}
			$text = $this->article_block_text( $item, '' );
			if ( '' !== $text && ! in_array( $text, $normalized, true ) ) {
				$normalized[] = $text;
			}
		}
		return array_slice( $normalized, 0, $max_items );
	}

	/**
	 * Normalizes title/description item arrays.
	 *
	 * @param mixed                         $items Items.
	 * @param array<int,array<string,string>> $fallback Fallback.
	 * @param int                           $min_items Min item count.
	 * @param int                           $max_items Max item count.
	 * @return array<int,array<string,string>>
	 */
	private function article_block_items( $items, array $fallback, $min_items, $max_items ) {
		$items      = is_array( $items ) ? array_values( $items ) : array();
		$normalized = array();
		foreach ( $items as $item ) {
			$item        = is_array( $item ) ? $item : array();
			$title       = $this->article_block_text( $item['title'] ?? '', '' );
			$description = $this->article_block_text( $item['description'] ?? '', '' );
			if ( '' === $title || '' === $description ) {
				continue;
			}
			$normalized[] = array(
				'title'       => $title,
				'description' => $description,
			);
			if ( count( $normalized ) >= $max_items ) {
				break;
			}
		}
		foreach ( $fallback as $item ) {
			if ( count( $normalized ) >= $min_items ) {
				break;
			}
			$title       = $this->article_block_text( $item['title'] ?? '', '' );
			$description = $this->article_block_text( $item['description'] ?? '', '' );
			if ( '' !== $title && '' !== $description ) {
				$normalized[] = array(
					'title'       => $title,
					'description' => $description,
				);
			}
		}
		return array_slice( $normalized, 0, $max_items );
	}

	/**
	 * Normalizes article sections.
	 *
	 * @param mixed                         $sections Sections.
	 * @param array<int,array<string,mixed>> $fallback Fallback sections.
	 * @param int                           $min_items Min item count.
	 * @param int                           $max_items Max item count.
	 * @return array<int,array<string,mixed>>
	 */
	private function article_block_sections( $sections, array $fallback, $min_items, $max_items ) {
		$sections   = is_array( $sections ) ? array_values( $sections ) : array();
		$normalized = array();
		foreach ( $sections as $section ) {
			$section    = is_array( $section ) ? $section : array();
			$title      = $this->article_block_text( $section['title'] ?? '', '' );
			$paragraphs = $this->article_block_strings( $section['paragraphs'] ?? array( $section['body'] ?? '' ), array(), 1, 4 );
			$bullets    = $this->article_block_strings( $section['bullets'] ?? array(), array(), 0, 6 );
			if ( '' === $title || empty( $paragraphs ) ) {
				continue;
			}
			$normalized[] = array(
				'title'      => $title,
				'paragraphs' => $paragraphs,
				'bullets'    => $bullets,
			);
			if ( count( $normalized ) >= $max_items ) {
				break;
			}
		}
		foreach ( $fallback as $section ) {
			if ( count( $normalized ) >= $min_items ) {
				break;
			}
			$normalized[] = array(
				'title'      => $this->article_block_text( $section['title'] ?? '', '' ),
				'paragraphs' => $this->article_block_strings( $section['paragraphs'] ?? array(), array(), 1, 4 ),
				'bullets'    => $this->article_block_strings( $section['bullets'] ?? array(), array(), 0, 6 ),
			);
		}
		return array_slice( $normalized, 0, $max_items );
	}

	/**
	 * Returns deterministic editorial quality signals.
	 *
	 * @param array<int,array<string,mixed>> $blocks Blocks.
	 * @param string                         $article_template Template.
	 * @return array<string,mixed>
	 */
	private function article_block_editorial_quality_summary( array $blocks, $article_template ) {
		return array(
			'pattern_version'          => '1.0',
			'article_template'         => sanitize_key( (string) $article_template ),
			'style_strategy'           => 'gutenberg_native_editorial',
			'uses_native_blocks'       => true,
			'heading_hierarchy_valid'  => true,
			'has_intro'                => $this->article_block_has_name( $blocks, 'core/paragraph' ),
			'has_takeaways'            => $this->article_block_has_name( $blocks, 'core/list' ),
			'has_faq'                  => $this->article_block_has_name( $blocks, 'core/details' ),
			'has_comparison_columns'   => $this->article_block_has_name( $blocks, 'core/columns' ),
			'has_hero_media_attachment_id' => $this->article_block_has_image_attachment_binding( $blocks ),
			'custom_css_required'      => false,
			'block_count'              => $this->article_block_count_recursive( $blocks ),
		);
	}

	/**
	 * Normalizes an optional media attachment id for article image blocks.
	 *
	 * @param mixed $value Attachment id candidate.
	 * @return int
	 */
	private function article_media_attachment_id( $value ): int {
		$attachment_id = absint( $value );
		return $attachment_id > 0 ? $attachment_id : 0;
	}

	/**
	 * Returns whether an article block tree includes image attachment bindings.
	 *
	 * @param array<int,array<string,mixed>> $blocks Blocks.
	 * @return bool
	 */
	private function article_block_has_image_attachment_binding( array $blocks ): bool {
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			$attrs = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : array();
			if ( 'core/image' === (string) ( $block['blockName'] ?? '' ) && $this->article_media_attachment_id( $attrs['id'] ?? 0 ) > 0 ) {
				return true;
			}
			if ( $this->article_block_has_image_attachment_binding( is_array( $block['innerBlocks'] ?? null ) ? $block['innerBlocks'] : array() ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns deterministic responsive quality signals.
	 *
	 * @param array<int,array<string,mixed>> $blocks Blocks.
	 * @param string                         $responsive_profile Responsive profile.
	 * @return array<string,mixed>
	 */
	private function article_block_responsive_quality_summary( array $blocks, $responsive_profile ) {
		return array(
			'responsive_profile'          => sanitize_key( (string) $responsive_profile ),
			'uses_core_responsive_blocks' => $this->article_block_has_name( $blocks, 'core/columns' ) || $this->article_block_has_name( $blocks, 'core/image' ),
			'uses_mobile_stack'           => $this->article_block_all_columns_stack_on_mobile( $blocks ),
			'has_responsive_media'        => $this->article_block_has_name( $blocks, 'core/image' ),
			'has_faq'                     => $this->article_block_has_name( $blocks, 'core/details' ),
			'max_columns_per_row'         => $this->article_block_max_columns_per_row( $blocks ),
			'custom_css_required'         => false,
		);
	}

	/**
	 * Returns whether a tree contains a block.
	 *
	 * @param array<int,array<string,mixed>> $blocks Blocks.
	 * @param string                         $block_name Block name.
	 * @return bool
	 */
	private function article_block_has_name( array $blocks, $block_name ) {
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			if ( $block_name === (string) ( $block['blockName'] ?? '' ) ) {
				return true;
			}
			if ( $this->article_block_has_name( is_array( $block['innerBlocks'] ?? null ) ? $block['innerBlocks'] : array(), $block_name ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns whether all columns stack on mobile.
	 *
	 * @param array<int,array<string,mixed>> $blocks Blocks.
	 * @return bool
	 */
	private function article_block_all_columns_stack_on_mobile( array $blocks ) {
		$found_columns = false;
		return $this->article_block_all_columns_stack_on_mobile_walk( $blocks, $found_columns );
	}

	/**
	 * Walks columns and tracks mobile stack attrs.
	 *
	 * @param array<int,array<string,mixed>> $blocks Blocks.
	 * @param bool                           $found_columns Found columns.
	 * @return bool
	 */
	private function article_block_all_columns_stack_on_mobile_walk( array $blocks, &$found_columns ) {
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
			if ( ! $this->article_block_all_columns_stack_on_mobile_walk( is_array( $block['innerBlocks'] ?? null ) ? $block['innerBlocks'] : array(), $found_columns ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Returns max columns per row.
	 *
	 * @param array<int,array<string,mixed>> $blocks Blocks.
	 * @return int
	 */
	private function article_block_max_columns_per_row( array $blocks ) {
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
			$max = max( $max, $this->article_block_max_columns_per_row( is_array( $block['innerBlocks'] ?? null ) ? $block['innerBlocks'] : array() ) );
		}
		return $max;
	}

	/**
	 * Counts nested blocks.
	 *
	 * @param array<int,array<string,mixed>> $blocks Blocks.
	 * @return int
	 */
	private function article_block_count_recursive( array $blocks ) {
		$count = 0;
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			++$count;
			$count += $this->article_block_count_recursive( is_array( $block['innerBlocks'] ?? null ) ? $block['innerBlocks'] : array() );
		}
		return $count;
	}

	/**
	 * Builds the core/group wrapper classes expected by Gutenberg save().
	 *
	 * @param array<string,mixed> $attrs Block attrs.
	 * @return string
	 */
	private function article_group_classes( array $attrs ) {
		$classes = array( 'wp-block-group' );
		$style   = is_array( $attrs['style'] ?? null ) ? $attrs['style'] : array();
		$color   = is_array( $style['color'] ?? null ) ? $style['color'] : array();
		$border  = is_array( $style['border'] ?? null ) ? $style['border'] : array();
		if ( ! empty( $border['color'] ) ) {
			$classes[] = 'has-border-color';
		}
		if ( ! empty( $color['text'] ) ) {
			$classes[] = 'has-text-color';
		}
		if ( ! empty( $color['background'] ) ) {
			$classes[] = 'has-background';
		}
		return implode( ' ', $classes );
	}

	/**
	 * Serializes common Gutenberg support attrs into the save() style order.
	 *
	 * @param array<string,mixed> $attrs Block attrs.
	 * @return string
	 */
	private function article_style_from_attrs( array $attrs ) {
		$style = is_array( $attrs['style'] ?? null ) ? $attrs['style'] : array();
		$css   = array();

		$border = is_array( $style['border'] ?? null ) ? $style['border'] : array();
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
	 * Escapes an attribute value.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private function article_block_attr( $value ) {
		return function_exists( 'esc_attr' ) ? esc_attr( $value ) : htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
	}
}
