<?php
/**
 * Content intent routing methods for Core_Read_Package.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalizes natural-language content requests into supported Gutenberg recipes.
 */
trait Content_Intent_Router_Read_Methods {
	/**
	 * Routes one customer content intent to a supported read-only plan ability.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function route_content_intent( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to route content intents.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$prompt = $this->content_intent_text( $input['prompt'] ?? '' );
		if ( '' === $prompt ) {
			return new \WP_Error( 'npcink_abilities_toolkit_content_intent_prompt_required', __( 'A customer prompt is required.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$target_hint = sanitize_key( (string) ( $input['target_hint'] ?? 'auto' ) );
		$intent_hint = sanitize_key( (string) ( $input['intent_hint'] ?? 'auto' ) );
		$media_hint  = sanitize_key( (string) ( $input['media_hint'] ?? 'auto' ) );
		$style_hint  = sanitize_key( (string) ( $input['style_hint'] ?? 'auto' ) );
		if ( ! in_array( $target_hint, array( 'auto', 'page', 'post', 'site_template', 'template_part', 'unsupported' ), true ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_content_intent_target_hint_invalid', __( 'Target hint is not supported.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( ! in_array( $intent_hint, array( 'auto', 'create_landing_page', 'write_article', 'add_breadcrumbs', 'customize_template_layout', 'edit_template_part', 'unsupported' ), true ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_content_intent_hint_invalid', __( 'Intent hint is not supported.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( ! in_array( $media_hint, array( 'auto', 'none', 'existing_media_url', 'generated_or_existing' ), true ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_content_intent_media_hint_invalid', __( 'Media hint is not supported.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( ! in_array( $style_hint, array( 'auto', 'minimal', 'modern', 'editorial_accent' ), true ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_content_intent_style_hint_invalid', __( 'Style hint is not supported.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$signals = $this->content_intent_signals( $prompt );
		$route   = $this->content_intent_route_decision( $signals, $target_hint, $intent_hint, $prompt );
		$route['media_strategy'] = $this->content_intent_media_strategy( $signals, $media_hint, (string) $route['route'] );
		$route['style_strategy'] = $this->content_intent_style_strategy( $signals, $style_hint, (string) $route['route'] );
		$route['recommended_plan_input'] = $this->content_intent_recommended_plan_input( $route, $prompt );
		if ( ! empty( $route['supported'] ) ) {
			$route['block_capability_catalog_ability_id'] = 'npcink-abilities-toolkit/get-gutenberg-block-capability-catalog';
			$route['block_capability_catalog_id']         = 'gutenberg_native_v1';
			$route['composer_instruction']                = $this->gutenberg_block_composer_instruction( $this->content_intent_catalog_surface( $route ) );
			$route['recommended_composer_flow']           = $this->gutenberg_block_recommended_composer_flow();
		}

		return $this->build_analysis_success_response(
			array(
				'artifact_type'          => 'content_intent_route',
				'version'                => 1,
				'input_mode'             => 'natural_language_to_allowed_gutenberg_recipe',
				'prompt_excerpt'         => $this->content_intent_excerpt( $prompt ),
				'prompt_is_authorization' => false,
				'route'                  => $route,
				'signals'                => $signals,
				'supported_routes'       => $this->content_intent_supported_routes(),
				'guardrails'             => array(
					'block_capability_catalog_ability_id' => 'npcink-abilities-toolkit/get-gutenberg-block-capability-catalog',
					'block_capability_catalog_id' => 'gutenberg_native_v1',
					'composition_model'   => 'bounded_block_composition',
					'direct_wordpress_write' => false,
					'commit_execution'       => false,
					'proposal_required'      => true,
					'generic_write_executor' => false,
					'custom_css_allowed'     => false,
					'core_html_allowed'      => false,
					'default_behavior'       => 'fail_closed',
				),
				'next_steps'             => $this->content_intent_next_steps( $route ),
			),
			array(
				'source'         => 'local_content_intent_router',
				'execution_mode' => 'deterministic',
			),
			'Content intent routed.'
		);
	}

	/**
	 * Returns a sanitized prompt string.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private function content_intent_text( $value ) {
		$text = sanitize_textarea_field( (string) $value );
		$text = preg_replace( '/\s+/', ' ', $text );
		return is_string( $text ) ? trim( $text ) : '';
	}

	/**
	 * Returns a compact prompt excerpt.
	 *
	 * @param string $prompt Prompt.
	 * @return string
	 */
	private function content_intent_excerpt( $prompt ) {
		$prompt = $this->content_intent_text( $prompt );
		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $prompt, 0, 160 );
		}
		return substr( $prompt, 0, 160 );
	}

	/**
	 * Extracts routing signals from a customer prompt.
	 *
	 * @param string $prompt Prompt.
	 * @return array<string,mixed>
	 */
	private function content_intent_signals( $prompt ) {
		return array(
			'page'          => $this->content_intent_match_terms( $prompt, array( 'page', 'landing', 'landing page', 'homepage', 'home page', 'website', 'site page', '产品页', '介绍页', '落地页', '官网', '首页', '页面' ) ),
			'article'       => $this->content_intent_match_terms( $prompt, array( 'article', 'post', 'blog', 'longform', 'comparison review', 'guide', 'tutorial', '文章', '博客', '博文', '写一篇', '对比评测', '教程', '指南', '草稿' ) ),
			'site_template' => $this->content_intent_match_terms(
				$prompt,
				array(
					'template',
					'site editor',
					'block theme',
					'single template',
					'archive template',
					'template layout',
					'模板',
					'模版',
					'站点编辑器',
					'块主题',
					'主题模板',
					'主题模版',
					'文章模板',
					'文章模版',
					'页面模板',
					'页面模版',
					'首页模板',
					'首页模版',
					'文章页',
					'归档模板',
				)
			),
			'template_layout' => $this->content_intent_match_terms(
				$prompt,
				array(
					'template layout',
					'single layout',
					'page layout',
					'front page layout',
					'homepage layout',
					'customize homepage',
					'customize front page',
					'redesign template',
					'模板布局',
					'模版布局',
					'页面模板布局',
					'页面模版布局',
					'文章页布局',
					'文章模板布局',
					'文章模版布局',
					'首页布局',
					'首页模板布局',
					'首页模版布局',
					'自定义首页',
					'自定义主页',
					'自定义文章页',
					'自定义页面模板',
					'自定义页面模版',
					'改文章页',
					'文章页改成',
					'重做文章页',
					'标题下面',
					'作者和日期',
					'发布日期',
					'特色图',
					'相关文章',
					'最新文章',
					'分类入口',
					'行动按钮',
				)
			),
			'template_part' => $this->content_intent_match_terms( $prompt, array( 'template part', 'header template part', 'footer template part', 'header template', 'footer template', '页眉模板部件', '页脚模板部件', '页眉模板', '页脚模板', '模板部件', 'template_part' ) ),
			'breadcrumbs'   => $this->content_intent_match_terms( $prompt, array( 'breadcrumb', 'breadcrumbs', '面包屑', '面包屑导航' ) ),
			'navigation'    => $this->content_intent_match_terms( $prompt, array( 'navigation menu', 'nav menu', 'site navigation', 'navigation block', 'wp_navigation', '导航菜单', '站点导航', '导航栏', '主导航', '菜单' ) ),
			'global_styles' => $this->content_intent_match_terms( $prompt, array( 'global styles', 'site styles', 'style book', '全站样式', '全局样式', '站点样式', '样式书' ) ),
			'theme_json'    => $this->content_intent_match_terms( $prompt, array( 'theme.json', 'theme json' ) ),
			'custom_html'   => $this->content_intent_match_terms( $prompt, array( 'custom html', 'raw html', 'html template', 'html patch', '自定义 html', '原始 html', 'html 模板' ) ),
			'media'         => $this->content_intent_match_terms( $prompt, array( 'image', 'media', 'visual', 'illustration', 'screenshot', '图片', '配图', '视觉', '截图', '生图', '生成图', '媒体' ) ),
			'modern_style'  => $this->content_intent_match_terms( $prompt, array( 'modern', 'polished', 'landing quality', '现代', '现代化', '高级', '美观', '官网感' ) ),
			'accent_style'  => $this->content_intent_match_terms( $prompt, array( 'accent', 'editorial', 'color', 'colour', '色彩', '配色', '强调色', 'editorial-accent' ) ),
		);
	}

	/**
	 * Returns matched terms for one signal family.
	 *
	 * @param string   $prompt Prompt.
	 * @param string[] $terms Terms.
	 * @return string[]
	 */
	private function content_intent_match_terms( $prompt, array $terms ) {
		$matches = array();
		$haystack = strtolower( $prompt );
		foreach ( $terms as $term ) {
			$term = (string) $term;
			if ( '' !== $term && false !== strpos( $haystack, strtolower( $term ) ) ) {
				$matches[] = $term;
			}
		}
		return array_values( array_unique( $matches ) );
	}

	/**
	 * Builds a route decision from hints and extracted signals.
	 *
	 * @param array<string,mixed> $signals Signals.
	 * @param string              $target_hint Target hint.
	 * @param string              $intent_hint Intent hint.
	 * @return array<string,mixed>
	 */
	private function content_intent_route_decision( array $signals, $target_hint, $intent_hint, $prompt = '' ) {
		$page_score     = $this->content_intent_signal_count( $signals['page'] ?? array() );
		$article_score  = $this->content_intent_signal_count( $signals['article'] ?? array() );
		$template_score = $this->content_intent_signal_count( $signals['site_template'] ?? array() );
		$template_layout_score = $this->content_intent_signal_count( $signals['template_layout'] ?? array() );
		$part_score     = $this->content_intent_signal_count( $signals['template_part'] ?? array() );
		$breadcrumb     = $this->content_intent_signal_count( $signals['breadcrumbs'] ?? array() ) > 0;
		$navigation_score = $this->content_intent_positive_signal_count( $prompt, $signals['navigation'] ?? array() );
		$global_styles_score = $this->content_intent_positive_signal_count( $prompt, $signals['global_styles'] ?? array() );
		$theme_json_score = $this->content_intent_positive_signal_count( $prompt, $signals['theme_json'] ?? array() );
		$custom_html_score = $this->content_intent_positive_signal_count( $prompt, $signals['custom_html'] ?? array() );

		if ( $navigation_score > 0 ) {
			return $this->content_intent_unsupported_route( 'navigation_write_not_supported', true );
		}
		if ( $global_styles_score > 0 || $theme_json_score > 0 ) {
			return $this->content_intent_unsupported_route( 'global_styles_write_not_supported', true );
		}
		if ( $custom_html_score > 0 ) {
			return $this->content_intent_unsupported_route( 'custom_html_template_not_supported', true );
		}

		if ( 'page' === $target_hint || 'create_landing_page' === $intent_hint ) {
			return $this->content_intent_supported_route( 'page_landing', 'hint' );
		}
		if ( 'post' === $target_hint || 'write_article' === $intent_hint ) {
			return $this->content_intent_supported_route( 'post_article', 'hint' );
		}
		if ( $template_layout_score > 0 || 'customize_template_layout' === $intent_hint ) {
			return $this->content_intent_supported_route( 'site_template_layout', 'prompt_signal' );
		}

		if ( 'site_template' === $target_hint || 'add_breadcrumbs' === $intent_hint ) {
			return $breadcrumb || 'add_breadcrumbs' === $intent_hint
				? $this->content_intent_supported_route( 'site_template_breadcrumbs', 'hint' )
				: $this->content_intent_unsupported_route( 'site_template_intent_requires_supported_recipe', true );
		}
		if ( 'template_part' === $target_hint || 'edit_template_part' === $intent_hint ) {
			return $this->content_intent_unsupported_route( 'template_part_recipe_not_available', true );
		}
		if ( 'unsupported' === $target_hint || 'unsupported' === $intent_hint ) {
			return $this->content_intent_unsupported_route( 'caller_marked_unsupported', false );
		}

		if ( $part_score > 0 ) {
			return $this->content_intent_unsupported_route( 'template_part_recipe_not_available', true );
		}
		if ( $template_score > 0 || $breadcrumb ) {
			return $breadcrumb
				? $this->content_intent_supported_route( 'site_template_breadcrumbs', 'prompt_signal' )
				: $this->content_intent_unsupported_route( 'site_template_intent_requires_supported_recipe', true );
		}
		if ( $page_score > 0 && $article_score > 0 ) {
			return $this->content_intent_unsupported_route( 'ambiguous_page_vs_article', true );
		}
		if ( $page_score > 0 ) {
			return $this->content_intent_supported_route( 'page_landing', 'prompt_signal' );
		}
		if ( $article_score > 0 ) {
			return $this->content_intent_supported_route( 'post_article', 'prompt_signal' );
		}

		return $this->content_intent_unsupported_route( 'target_not_clear', true );
	}

	/**
	 * Counts one signal family.
	 *
	 * @param mixed $matches Matches.
	 * @return int
	 */
	private function content_intent_signal_count( $matches ) {
		return is_array( $matches ) ? count( $matches ) : 0;
	}

	/**
	 * Counts matched terms that are not expressed as negative guardrails.
	 *
	 * Natural-language clients often include constraints such as "do not write
	 * theme.json" or "不要改 global styles". Those terms should keep appearing
	 * in signals for transparency, but they must not turn an otherwise
	 * supported template layout request into an unsupported write request.
	 *
	 * @param string $prompt Prompt.
	 * @param mixed  $matches Matches.
	 * @return int
	 */
	private function content_intent_positive_signal_count( $prompt, $matches ) {
		if ( ! is_array( $matches ) ) {
			return 0;
		}

		$count = 0;
		foreach ( $matches as $match ) {
			if ( $this->content_intent_term_has_positive_occurrence( (string) $prompt, (string) $match ) ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Returns whether a term has at least one occurrence outside a negated context.
	 *
	 * @param string $prompt Prompt.
	 * @param string $term Matched term.
	 * @return bool
	 */
	private function content_intent_term_has_positive_occurrence( $prompt, $term ) {
		$haystack = strtolower( (string) $prompt );
		$needle   = strtolower( (string) $term );
		if ( '' === $needle ) {
			return false;
		}

		$offset = 0;
		while ( false !== ( $position = strpos( $haystack, $needle, $offset ) ) ) {
			if ( ! $this->content_intent_term_occurrence_is_negated( $haystack, $position ) ) {
				return true;
			}
			$offset = $position + strlen( $needle );
		}

		return false;
	}

	/**
	 * Returns whether a term occurrence is preceded by a local negation marker.
	 *
	 * @param string $haystack Lowercase prompt.
	 * @param int    $position Term position.
	 * @return bool
	 */
	private function content_intent_term_occurrence_is_negated( $haystack, $position ) {
		$start   = max( 0, (int) $position - 96 );
		$context = substr( $haystack, $start, (int) $position - $start );
		$context = preg_replace( '/\s+/', ' ', (string) $context );

		foreach (
			array(
				'do not',
				"don't",
				'must not',
				'should not',
				'not ',
				'never',
				'without',
				'avoid',
				'forbid',
				'forbidden',
				'no ',
				'不要',
				'别',
				'不得',
				'不能',
				'不许',
				'不写',
				'不改',
				'不修改',
				'禁止',
				'避免',
				'无需',
				'不用',
			) as $marker
		) {
			if ( false !== strpos( $context, $marker ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns a supported route row.
	 *
	 * @param string $route_key Route key.
	 * @param string $reason Reason.
	 * @return array<string,mixed>
	 */
	private function content_intent_supported_route( $route_key, $reason ) {
		$routes = $this->content_intent_supported_routes();
		$route  = $routes[ $route_key ] ?? array();
		$route['supported']           = true;
		$route['needs_clarification'] = false;
		$route['clarification_questions'] = array();
		$route['unsupported_reason']  = '';
		$route['decision_reason']     = sanitize_key( $reason );
		$route['confidence']          = 'hint' === $reason ? 'high' : 'medium';
		return $route;
	}

	/**
	 * Returns an unsupported route row.
	 *
	 * @param string $reason Reason.
	 * @param bool   $needs_clarification Needs clarification.
	 * @return array<string,mixed>
	 */
	private function content_intent_unsupported_route( $reason, $needs_clarification ) {
		return array(
			'route_key'               => 'unsupported',
			'surface'                 => 'unsupported',
			'target_type'             => 'unsupported',
			'route'                   => 'unsupported',
			'recipe_id'               => '',
			'plan_ability_id'         => '',
			'readback_ability_ids'    => array(),
			'final_write_ability_ids' => array(),
			'proposal_required'       => true,
			'supported'               => false,
			'needs_clarification'     => (bool) $needs_clarification,
			'clarification_questions' => (bool) $needs_clarification ? array( 'Should this be a page, an article, or a supported Site Editor template change?' ) : array(),
			'unsupported_reason'      => sanitize_key( $reason ),
			'decision_reason'         => 'fail_closed',
			'confidence'              => 'low',
		);
	}

	/**
	 * Returns supported route definitions.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function content_intent_supported_routes() {
		return array(
			'page_landing' => array(
				'route_key'               => 'page_landing',
				'surface'                 => 'post_content',
				'target_type'             => 'page',
				'route'                   => 'pattern_page_plan',
				'recipe_id'               => 'openai-style-landing',
				'plan_ability_id'         => 'npcink-abilities-toolkit/build-pattern-page-plan',
				'readback_ability_ids'    => array( 'npcink-abilities-toolkit/get-post-blocks' ),
				'final_write_ability_ids' => array( 'npcink-abilities-toolkit/create-draft', 'npcink-abilities-toolkit/update-post-blocks' ),
				'proposal_required'       => true,
			),
			'post_article' => array(
				'route_key'               => 'post_article',
				'surface'                 => 'post_content',
				'target_type'             => 'post',
				'route'                   => 'article_block_plan',
				'recipe_id'               => 'article_block_v1',
				'plan_ability_id'         => 'npcink-abilities-toolkit/build-article-block-plan',
				'readback_ability_ids'    => array( 'npcink-abilities-toolkit/get-post-blocks' ),
				'final_write_ability_ids' => array( 'npcink-abilities-toolkit/create-draft', 'npcink-abilities-toolkit/update-post-blocks' ),
				'proposal_required'       => true,
			),
			'site_template_breadcrumbs' => array(
				'route_key'               => 'site_template_breadcrumbs',
				'surface'                 => 'site_template',
				'target_type'             => 'wp_template',
				'route'                   => 'block_theme_site_plan',
				'recipe_id'               => 'block_theme_site_builder_v1',
				'plan_ability_id'         => 'npcink-abilities-toolkit/build-block-theme-site-plan',
				'readback_ability_ids'    => array( 'npcink-abilities-toolkit/get-template-blocks', 'npcink-abilities-toolkit/get-template-part-blocks' ),
				'final_write_ability_ids' => array( 'npcink-abilities-toolkit/update-template-blocks', 'npcink-abilities-toolkit/upsert-template-blocks' ),
				'proposal_required'       => true,
			),
			'site_template_layout' => array(
				'route_key'               => 'site_template_layout',
				'surface'                 => 'site_template',
				'target_type'             => 'wp_template',
				'route'                   => 'block_theme_site_plan',
				'recipe_id'               => 'block_theme_template_layout_v1',
				'plan_ability_id'         => 'npcink-abilities-toolkit/build-block-theme-site-plan',
				'readback_ability_ids'    => array( 'npcink-abilities-toolkit/get-template-blocks', 'npcink-abilities-toolkit/get-template-part-blocks' ),
				'final_write_ability_ids' => array( 'npcink-abilities-toolkit/update-template-blocks', 'npcink-abilities-toolkit/upsert-template-blocks' ),
				'proposal_required'       => true,
			),
		);
	}

	/**
	 * Chooses a media strategy for the selected route.
	 *
	 * @param array<string,mixed> $signals Signals.
	 * @param string              $media_hint Media hint.
	 * @param string              $route Route.
	 * @return string
	 */
	private function content_intent_media_strategy( array $signals, $media_hint, $route ) {
		if ( 'none' === $media_hint || 'unsupported' === $route || 'block_theme_site_plan' === $route ) {
			return 'none';
		}
		if ( 'existing_media_url' === $media_hint ) {
			return 'existing_media_url';
		}
		if ( 'generated_or_existing' === $media_hint ) {
			return 'existing_or_generated_media';
		}
		return $this->content_intent_signal_count( $signals['media'] ?? array() ) > 0 ? 'existing_or_generated_media' : 'none';
	}

	/**
	 * Chooses a style strategy for the selected route.
	 *
	 * @param array<string,mixed> $signals Signals.
	 * @param string              $style_hint Style hint.
	 * @param string              $route Route.
	 * @return string
	 */
	private function content_intent_style_strategy( array $signals, $style_hint, $route ) {
		if ( 'unsupported' === $route ) {
			return 'none';
		}
		if ( 'editorial_accent' === $style_hint || $this->content_intent_signal_count( $signals['accent_style'] ?? array() ) > 0 ) {
			return 'editorial-accent';
		}
		if ( 'modern' === $style_hint || $this->content_intent_signal_count( $signals['modern_style'] ?? array() ) > 0 ) {
			return 'gutenberg-native-modern';
		}
		return 'gutenberg-native-default';
	}

	/**
	 * Builds default plan input for the selected route.
	 *
	 * @param array<string,mixed> $route Route.
	 * @param string              $prompt Prompt.
	 * @return array<string,mixed>
	 */
	private function content_intent_recommended_plan_input( array $route, $prompt ) {
		$title = $this->content_intent_title_placeholder( $prompt );
		if ( empty( $route['supported'] ) ) {
			return array();
		}
		if ( 'pattern_page_plan' === (string) ( $route['route'] ?? '' ) ) {
			return array(
				'post_type'          => 'page',
				'status'             => 'draft',
				'title'              => $title,
				'pattern_id'         => 'openai-style-landing',
				'style_preset'       => 'minimal-dark-light',
				'color_story'        => 'editorial-accent' === (string) ( $route['style_strategy'] ?? '' ) ? 'editorial-accent' : 'minimal-dark-light',
				'responsive_profile' => 'landing_standard',
				'media_strategy'     => 'none' === (string) ( $route['media_strategy'] ?? 'none' ) ? 'mock_or_existing_media' : 'existing_media_url',
				'variables'          => array(
					'user_intent' => $this->content_intent_excerpt( $prompt ),
				),
			);
		}
		if ( 'article_block_plan' === (string) ( $route['route'] ?? '' ) ) {
			return array(
				'post_type'          => 'post',
				'status'             => 'draft',
				'title'              => $title,
				'article_template'   => 'comparison-review',
				'responsive_profile' => 'article_standard',
				'media_strategy'     => 'none' === (string) ( $route['media_strategy'] ?? 'none' ) ? 'none' : 'existing_media_url',
				'variables'          => array(
					'user_intent' => $this->content_intent_excerpt( $prompt ),
				),
			);
		}
			if ( 'block_theme_site_plan' === (string) ( $route['route'] ?? '' ) ) {
				if ( 'site_template_layout' === sanitize_key( (string) ( $route['route_key'] ?? '' ) ) ) {
					return array(
						'intent'                 => 'customize_template_layout',
						'target_templates'       => $this->content_intent_block_theme_layout_target_templates( $prompt ),
						'layout_profile'         => $this->content_intent_block_theme_layout_profile( $prompt ),
						'include_breadcrumbs'    => true,
						'show_author_date'       => true,
						'show_featured_image'    => true,
						'include_related_posts'  => true,
						'include_latest_posts'   => true,
						'include_category_links' => true,
						'include_cta'            => true,
						'separator'              => '/',
						'show_current_item'      => true,
						'show_home_item'         => true,
						'show_on_home_page'      => false,
						'variables'              => array(
							'user_intent' => $this->content_intent_excerpt( $prompt ),
						),
					);
				}
				return array(
					'intent'             => 'add_breadcrumbs',
					'target_templates'   => $this->content_intent_block_theme_target_templates( $prompt ),
					'separator'          => '/',
					'show_current_item'  => true,
					'show_home_item'     => true,
				'show_on_home_page'  => false,
			);
		}
		return array();
	}

	/**
	 * Infers layout template targets from ordinary customer language.
	 *
	 * @param string $prompt Prompt.
	 * @return string[]
	 */
	private function content_intent_block_theme_layout_target_templates( $prompt ) {
		$front_terms   = $this->content_intent_match_terms( $prompt, array( 'front page', 'homepage', 'home page', 'customize homepage', 'customize front page', '首页模板', '首页模版', '首页布局', '自定义首页', '自定义主页', '主页', '首页' ) );
		$article_terms = $this->content_intent_match_terms( $prompt, array( 'single template', 'post template', 'article template', 'single layout', '文章页布局', '文章模板布局', '文章模版布局', '文章页', '文章模板', '文章模版', '自定义文章页', '改文章页' ) );
		$page_terms    = $this->content_intent_match_terms( $prompt, array( 'page template', 'page layout', '页面模板布局', '页面模版布局', '页面模板', '页面模版', '普通页面', '页面' ) );

		$targets = array();
		if ( $this->content_intent_signal_count( $front_terms ) > 0 ) {
			$targets[] = 'front-page';
		}
		if ( $this->content_intent_signal_count( $article_terms ) > 0 ) {
			$targets[] = 'single';
		}
		if ( $this->content_intent_signal_count( $page_terms ) > 0 ) {
			$targets[] = 'page';
		}
		return empty( $targets ) ? array( 'single' ) : array_values( array_unique( $targets ) );
	}

	/**
	 * Infers a layout profile from ordinary customer language.
	 *
	 * @param string $prompt Prompt.
	 * @return string
	 */
	private function content_intent_block_theme_layout_profile( $prompt ) {
		$front_terms = $this->content_intent_match_terms( $prompt, array( 'front page', 'homepage', 'home page', '首页', '主页', '自定义首页', '自定义主页' ) );
		if ( $this->content_intent_signal_count( $front_terms ) > 0 ) {
			return 'homepage_landing';
		}
		$page_terms = $this->content_intent_match_terms( $prompt, array( 'page template', 'page layout', '页面模板', '页面模版', '页面布局', '普通页面' ) );
		if ( $this->content_intent_signal_count( $page_terms ) > 0 ) {
			return 'page_standard';
		}
		return 'article_standard';
	}

	/**
	 * Maps a route to the block capability catalog surface.
	 *
	 * @param array<string,mixed> $route Route.
	 * @return string
	 */
	private function content_intent_catalog_surface( array $route ): string {
		$target_type = sanitize_key( (string) ( $route['target_type'] ?? '' ) );
		if ( 'page' === $target_type ) {
			return 'page';
		}
		if ( 'post' === $target_type ) {
			return 'post';
		}
		if ( 'wp_template' === $target_type ) {
			return 'template';
		}
		return 'all';
	}

	/**
	 * Infers block theme template targets from ordinary customer language.
	 *
	 * @param string $prompt Prompt.
	 * @return string[]
	 */
	private function content_intent_block_theme_target_templates( $prompt ) {
		$article_terms = $this->content_intent_match_terms( $prompt, array( 'single template', 'post template', 'article template', '文章模板', '文章页', '博客文章', '博文', '文章' ) );
		$page_terms    = $this->content_intent_match_terms( $prompt, array( 'page template', 'page', '页面模板', '普通页面', '页面' ) );
		$front_terms   = $this->content_intent_match_terms( $prompt, array( 'front page', 'homepage', 'home page', '首页模板', '首页', '主页' ) );
		$archive_terms = $this->content_intent_match_terms( $prompt, array( 'archive template', 'archive page', 'archive', '归档模板', '归档页', '归档' ) );
		$site_terms    = $this->content_intent_match_terms( $prompt, array( 'site', 'website', 'whole site', 'all templates', '全站', '网站', '整站', '所有模板' ) );

		$article_score = $this->content_intent_signal_count( $article_terms );
		$page_score    = $this->content_intent_signal_count( $page_terms );
		$front_score   = $this->content_intent_signal_count( $front_terms );
		$archive_score = $this->content_intent_signal_count( $archive_terms );
		$site_score    = $this->content_intent_signal_count( $site_terms );

		if ( $site_score > 0 || ( $article_score > 0 && ( $page_score > 0 || $front_score > 0 ) ) ) {
			return array( 'single', 'page', 'front-page' );
		}
		if ( $front_score > 0 ) {
			return array( 'front-page', 'page' );
		}
		if ( $archive_score > 0 ) {
			return array( 'archive' );
		}
		if ( $page_score > 0 ) {
			return array( 'page', 'front-page' );
		}
		if ( $article_score > 0 ) {
			return array( 'single' );
		}

		return array( 'single', 'page', 'front-page' );
	}

	/**
	 * Builds a title placeholder for downstream content generation.
	 *
	 * @param string $prompt Prompt.
	 * @return string
	 */
	private function content_intent_title_placeholder( $prompt ) {
		$excerpt = $this->content_intent_excerpt( $prompt );
		return '' !== $excerpt ? $excerpt : 'Gutenberg Draft';
	}

	/**
	 * Returns next steps for a route.
	 *
	 * @param array<string,mixed> $route Route.
	 * @return string[]
	 */
	private function content_intent_next_steps( array $route ) {
		if ( empty( $route['supported'] ) ) {
			return array(
				'Ask a clarification question or choose an explicitly supported route.',
				'Do not submit a Core proposal for unsupported route output.',
			);
		}
		return array(
			'Use the plan_ability_id with the recommended_plan_input as a starting point.',
			'Let the AI fill content variables, media choices, and copy within the selected recipe.',
			'Submit only the returned plan artifact to Core /proposals/from-plan.',
			'Execute only after Core approval and Adapter commit-preflight.',
			'Verify through the listed readback abilities.',
		);
	}
}
