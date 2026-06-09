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

		$pattern_id   = sanitize_key( (string) ( $input['pattern_id'] ?? 'openai-style-landing' ) );
		$style_preset = sanitize_key( (string) ( $input['style_preset'] ?? 'minimal-dark-light' ) );
		$post_type    = sanitize_key( (string) ( $input['post_type'] ?? 'page' ) );
		if ( 'page' !== $post_type ) {
			return new \WP_Error( 'npcink_abilities_toolkit_pattern_page_post_type_invalid', __( 'Pattern page plans currently support post_type=page only.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( 'openai-style-landing' !== $pattern_id ) {
			return new \WP_Error( 'npcink_abilities_toolkit_pattern_page_pattern_invalid', __( 'Pattern id is not supported.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( 'minimal-dark-light' !== $style_preset ) {
			return new \WP_Error( 'npcink_abilities_toolkit_pattern_page_style_invalid', __( 'Style preset is not supported.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$variables = is_array( $input['variables'] ?? null ) ? $input['variables'] : array();
		$title     = $this->pattern_text( $input['title'] ?? ( $variables['hero_title'] ?? 'WordPress AI' ), 'WordPress AI' );
		$blocks    = $this->render_openai_style_landing_blocks( $variables );
		$batch_seed = wp_json_encode( array( $pattern_id, $style_preset, $title, $variables ) );
		$batch_id   = 'pattern_page_' . substr( md5( is_string( $batch_seed ) ? $batch_seed : $title ), 0, 12 );
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
						'npcink_pattern_id'   => $pattern_id,
						'npcink_style_preset' => $style_preset,
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
	 * @return array<int,array<string,mixed>>
	 */
	private function render_openai_style_landing_blocks( array $variables ) {
		$eyebrow          = $this->pattern_text( $variables['eyebrow'] ?? '', 'WordPress AI Plugin' );
		$hero_title       = $this->pattern_text( $variables['hero_title'] ?? '', '把 AI 工作流带进 WordPress 内容现场' );
		$hero_description = $this->pattern_text( $variables['hero_description'] ?? '', '让内容生产、SEO 优化、媒体处理与发布协作在同一个可审计流程中完成。' );
		$primary_cta      = $this->pattern_text( $variables['primary_cta'] ?? '', '查看工作流' );
		$secondary_cta    = $this->pattern_text( $variables['secondary_cta'] ?? '', '了解能力' );
		$features         = $this->pattern_items(
			$variables['features'] ?? array(),
			array(
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
			)
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
			)
		);

		return array(
			$this->pattern_group_block(
				'npcink-ai-page npcink-ai-hero',
				array(
					$this->pattern_paragraph_block( $eyebrow, 'npcink-ai-eyebrow' ),
					$this->pattern_heading_block( $hero_title, 1, 'npcink-ai-title' ),
					$this->pattern_paragraph_block( $hero_description, 'npcink-ai-lede' ),
					$this->pattern_buttons_block(
						array(
							$this->pattern_button_block( $primary_cta, 'npcink-ai-button-primary' ),
							$this->pattern_button_block( $secondary_cta, 'npcink-ai-button-secondary' ),
						),
						'npcink-ai-cta'
					),
				)
			),
			$this->pattern_group_block(
				'npcink-ai-feature-grid',
				array_map(
					function ( $item ) {
						return $this->pattern_group_block(
							'npcink-ai-feature-card',
							array(
								$this->pattern_heading_block( (string) $item['title'], 3, 'npcink-ai-card-title' ),
								$this->pattern_paragraph_block( (string) $item['description'], 'npcink-ai-card-text' ),
							)
						);
					},
					$features
				)
			),
			$this->pattern_group_block(
				'npcink-ai-workflow',
				array_merge(
					array(
						$this->pattern_heading_block( $this->pattern_text( $variables['workflow_title'] ?? '', '从生成到发布，始终可审查' ), 2, 'npcink-ai-section-title' ),
					),
					array_map(
						function ( $item ) {
							return $this->pattern_group_block(
								'npcink-ai-workflow-step',
								array(
									$this->pattern_heading_block( (string) $item['title'], 3, 'npcink-ai-card-title' ),
									$this->pattern_paragraph_block( (string) $item['description'], 'npcink-ai-card-text' ),
								)
							);
						},
						$workflow
					)
				)
			),
		);
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
			'npcink-ai-feature-grid',
			'npcink-ai-feature-card',
			'npcink-ai-workflow',
			'npcink-ai-workflow-step',
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
	 * @return array<string,mixed>
	 */
	private function pattern_group_block( $class_name, array $inner_blocks ) {
		$class_name = $this->pattern_class_names( $class_name );
		$attr_class = $this->pattern_attr( $class_name );
		return array(
			'blockName'    => 'core/group',
			'attrs'        => array( 'className' => $class_name ),
			'innerHTML'    => '<div class="wp-block-group ' . $attr_class . '"></div>',
			'innerContent' => array_merge( array( '<div class="wp-block-group ' . $attr_class . '">' ), array_fill( 0, count( $inner_blocks ), null ), array( '</div>' ) ),
			'innerBlocks'  => array_values( $inner_blocks ),
		);
	}

	/**
	 * Builds a heading block.
	 *
	 * @param string $text Text.
	 * @param int    $level Heading level.
	 * @param string $class_name CSS classes.
	 * @return array<string,mixed>
	 */
	private function pattern_heading_block( $text, $level, $class_name ) {
		$level      = max( 1, min( 6, (int) $level ) );
		$class_name = $this->pattern_class_names( $class_name );
		$html       = '<h' . $level . ' class="wp-block-heading ' . $this->pattern_attr( $class_name ) . '">' . esc_html( $text ) . '</h' . $level . '>';
		return array(
			'blockName'    => 'core/heading',
			'attrs'        => array(
				'level'     => $level,
				'className' => $class_name,
			),
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
	 * @return array<string,mixed>
	 */
	private function pattern_paragraph_block( $text, $class_name ) {
		$class_name = $this->pattern_class_names( $class_name );
		$html       = '<p class="' . $this->pattern_attr( $class_name ) . '">' . esc_html( $text ) . '</p>';
		return array(
			'blockName'    => 'core/paragraph',
			'attrs'        => array( 'className' => $class_name ),
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
	 * @return array<string,mixed>
	 */
	private function pattern_buttons_block( array $buttons, $class_name ) {
		$class_name = $this->pattern_class_names( $class_name );
		$attr_class = $this->pattern_attr( $class_name );
		return array(
			'blockName'    => 'core/buttons',
			'attrs'        => array( 'className' => $class_name ),
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
	 * @return array<string,mixed>
	 */
	private function pattern_button_block( $text, $class_name ) {
		$class_name = $this->pattern_class_names( $class_name );
		$html       = '<div class="wp-block-button ' . $this->pattern_attr( $class_name ) . '"><a class="wp-block-button__link wp-element-button">' . esc_html( $text ) . '</a></div>';
		return array(
			'blockName'    => 'core/button',
			'attrs'        => array( 'className' => $class_name ),
			'innerHTML'    => $html,
			'innerContent' => array( $html ),
			'innerBlocks'  => array(),
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
	 * Normalizes variable item arrays.
	 *
	 * @param mixed                         $items Raw items.
	 * @param array<int,array<string,string>> $fallback Fallback items.
	 * @return array<int,array<string,string>>
	 */
	private function pattern_items( $items, array $fallback ) {
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
		return ! empty( $normalized ) ? $normalized : $fallback;
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
