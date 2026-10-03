<?php
/**
 * Ordered regression suite part: 95-content-intent-routing.
 *
 * Included by tests/run.php; parts must run in the original execution order.
 *
 * @package NpcinkAbilitiesToolkit
 */

use Npcink_Abilities_Toolkit\Integration\Npcink_Catalog_Bridge;
use Npcink_Abilities_Toolkit\Packages\Core_Comment_Pack_Classifier;
use Npcink_Abilities_Toolkit\Packages\Core_Comment_Package;
use Npcink_Abilities_Toolkit\Packages\Core_Destructive_Package;
use Npcink_Abilities_Toolkit\Packages\Core_Read_Pack_Classifier;
use Npcink_Abilities_Toolkit\Packages\Core_Read_Package;
use Npcink_Abilities_Toolkit\Packages\Core_Write_Package;
use Npcink_Abilities_Toolkit\Packages\Read_Definitions\Media_Governance_Read_Definitions;
use Npcink_Abilities_Toolkit\Plugin;
use Npcink_Abilities_Toolkit\Registry\Ability_Registrar;
use Npcink_Abilities_Toolkit\Registry\Annotation_Normalizer;
use Npcink_Abilities_Toolkit\Registry\Category_Registrar;
use Npcink_Abilities_Toolkit\Registry\Contract_Normalizer;
use Npcink_Abilities_Toolkit\Registry\Schema_Normalizer;
use Npcink_Abilities_Toolkit\Rest\Contract_Controller;
use Npcink_Abilities_Toolkit\Security\Permission_Callbacks;
use Npcink_Abilities_Toolkit\Support\Gutenberg_Block_Document;

$page_intent_route = $core_read_package->route_content_intent(
	array(
		'prompt' => '帮我做一个现代官网介绍页，需要配图，手机端也要好看。',
	)
);
npcink_abilities_toolkit_assert_same( true, $page_intent_route['success'] ?? null, 'route-content-intent returns a success envelope for page intents' );
npcink_abilities_toolkit_assert_same( 'content_intent_route', $page_intent_route['data']['artifact_type'] ?? '', 'route-content-intent declares a routing artifact type' );
npcink_abilities_toolkit_assert_same( false, $page_intent_route['data']['prompt_is_authorization'] ?? true, 'route-content-intent never treats prompts as authorization' );
npcink_abilities_toolkit_assert_same( 'pattern_page_plan', $page_intent_route['data']['route']['route'] ?? '', 'route-content-intent maps landing page prompts to pattern page plans' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/build-pattern-page-plan', $page_intent_route['data']['route']['plan_ability_id'] ?? '', 'route-content-intent selects the pattern page plan ability' );
npcink_abilities_toolkit_assert_same( 'existing_or_generated_media', $page_intent_route['data']['route']['media_strategy'] ?? '', 'route-content-intent detects visual media needs from page prompts' );
npcink_abilities_toolkit_assert_same( 'gutenberg-native-modern', $page_intent_route['data']['route']['style_strategy'] ?? '', 'route-content-intent detects modern style requests' );
npcink_abilities_toolkit_assert_same( 'page', $page_intent_route['data']['route']['recommended_plan_input']['post_type'] ?? '', 'route-content-intent recommends page plan input for landing pages' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/get-gutenberg-block-capability-catalog', $page_intent_route['data']['route']['block_capability_catalog_ability_id'] ?? '', 'route-content-intent points page composers at the block capability catalog' );
npcink_abilities_toolkit_assert_same( 'page', $page_intent_route['data']['route']['composer_instruction']['surface'] ?? '', 'route-content-intent scopes page composer instructions to the page surface' );
npcink_abilities_toolkit_assert_same( 'saas_landing', $page_intent_route['data']['route']['recommended_composer_profile_id'] ?? '', 'route-content-intent recommends the SaaS landing composer profile for page prompts' );
npcink_abilities_toolkit_assert_same( 'high', $page_intent_route['data']['route']['recommended_composer_profile']['quality_targets']['section_variance'] ?? '', 'route-content-intent exposes page composer profile quality targets' );
npcink_abilities_toolkit_assert_same( 'inspect_catalog', $page_intent_route['data']['route']['recommended_composer_flow'][0]['step'] ?? '', 'route-content-intent recommends catalog inspection before planning' );
npcink_abilities_toolkit_assert_same( 'bounded_block_composition', $page_intent_route['data']['guardrails']['composition_model'] ?? '', 'route-content-intent guardrails report bounded block composition' );
npcink_abilities_toolkit_assert_true( ! isset( $page_intent_route['data']['write_actions'] ), 'route-content-intent does not create write actions' );

$article_intent_route = $core_read_package->route_content_intent(
	array(
		'prompt' => '写一篇对比评测文章，加一张配图。',
	)
);
npcink_abilities_toolkit_assert_same( 'article_block_plan', $article_intent_route['data']['route']['route'] ?? '', 'route-content-intent maps article prompts to article block plans' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/build-article-block-plan', $article_intent_route['data']['route']['plan_ability_id'] ?? '', 'route-content-intent selects the article block plan ability' );
npcink_abilities_toolkit_assert_same( 'post', $article_intent_route['data']['route']['recommended_plan_input']['post_type'] ?? '', 'route-content-intent recommends post plan input for article prompts' );
npcink_abilities_toolkit_assert_same( 'existing_media_url', $article_intent_route['data']['route']['recommended_plan_input']['media_strategy'] ?? '', 'route-content-intent keeps article media in the existing-media URL lane' );
npcink_abilities_toolkit_assert_same( 'post', $article_intent_route['data']['route']['composer_instruction']['surface'] ?? '', 'route-content-intent scopes article composer instructions to the post surface' );
npcink_abilities_toolkit_assert_same( 'comparison_review', $article_intent_route['data']['route']['recommended_composer_profile_id'] ?? '', 'route-content-intent recommends the comparison review composer profile for comparison articles' );

$template_intent_route = $core_read_package->route_content_intent(
	array(
		'prompt' => '给文章模板加面包屑导航。',
	)
);
npcink_abilities_toolkit_assert_same( 'block_theme_site_plan', $template_intent_route['data']['route']['route'] ?? '', 'route-content-intent maps supported template breadcrumb prompts to block theme site plans' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/build-block-theme-site-plan', $template_intent_route['data']['route']['plan_ability_id'] ?? '', 'route-content-intent selects the block theme site plan ability' );
npcink_abilities_toolkit_assert_same( 'add_breadcrumbs', $template_intent_route['data']['route']['recommended_plan_input']['intent'] ?? '', 'route-content-intent recommends the only supported block theme intent' );
npcink_abilities_toolkit_assert_same( array( 'single' ), $template_intent_route['data']['route']['recommended_plan_input']['target_templates'] ?? array(), 'route-content-intent scopes article template breadcrumbs to single by natural language' );
npcink_abilities_toolkit_assert_same( 'block_theme_template', $template_intent_route['data']['route']['recommended_composer_profile_id'] ?? '', 'route-content-intent recommends the block theme template composer profile for template prompts' );

$page_template_intent_route = $core_read_package->route_content_intent(
	array(
		'prompt' => '页面也加上面包屑导航，位置不要跑到页眉前面。',
	)
);
npcink_abilities_toolkit_assert_same( 'block_theme_site_plan', $page_template_intent_route['data']['route']['route'] ?? '', 'route-content-intent maps page breadcrumb prompts to block theme site plans' );
npcink_abilities_toolkit_assert_same( array( 'page', 'front-page' ), $page_template_intent_route['data']['route']['recommended_plan_input']['target_templates'] ?? array(), 'route-content-intent scopes page breadcrumb prompts to page and front-page templates' );

$archive_template_intent_route = $core_read_package->route_content_intent(
	array(
		'prompt' => '给归档模板加面包屑导航，并检查不要跑到页眉上方。',
	)
);
npcink_abilities_toolkit_assert_same( 'unsupported', $archive_template_intent_route['data']['route']['route'] ?? '', 'route-content-intent rejects archive template write prompts before Core handoff' );
npcink_abilities_toolkit_assert_same( 'archive_template_write_not_supported', $archive_template_intent_route['data']['route']['unsupported_reason'] ?? '', 'route-content-intent explains archive template write prompts are outside the Core-compatible handoff' );

$site_template_intent_route = $core_read_package->route_content_intent(
	array(
		'prompt' => '给网站加面包屑导航。',
	)
);
npcink_abilities_toolkit_assert_same( array( 'single', 'page', 'front-page' ), $site_template_intent_route['data']['route']['recommended_plan_input']['target_templates'] ?? array(), 'route-content-intent expands site breadcrumb prompts to common content templates' );

$article_template_layout_intent_route = $core_read_package->route_content_intent(
	array(
		'prompt' => '帮我把文章页改成更专业的布局：顶部有面包屑，标题下面显示作者和日期，下面是特色图和正文，底部放相关文章。',
	)
);
npcink_abilities_toolkit_assert_same( 'block_theme_site_plan', $article_template_layout_intent_route['data']['route']['route'] ?? '', 'route-content-intent maps article template layout requests to block theme site plans' );
npcink_abilities_toolkit_assert_same( 'site_template_layout', $article_template_layout_intent_route['data']['route']['route_key'] ?? '', 'route-content-intent uses the template layout route key for article template layouts' );
npcink_abilities_toolkit_assert_same( 'customize_template_layout', $article_template_layout_intent_route['data']['route']['recommended_plan_input']['intent'] ?? '', 'route-content-intent recommends the bounded template layout intent for article templates' );
npcink_abilities_toolkit_assert_same( array( 'single' ), $article_template_layout_intent_route['data']['route']['recommended_plan_input']['target_templates'] ?? array(), 'route-content-intent scopes article template layouts to single' );
npcink_abilities_toolkit_assert_same( 'article_standard', $article_template_layout_intent_route['data']['route']['recommended_plan_input']['layout_profile'] ?? '', 'route-content-intent recommends the article layout profile' );

$homepage_template_layout_intent_route = $core_read_package->route_content_intent(
	array(
		'prompt' => '帮我自定义首页：顶部放一个大标题和介绍，下面展示最新文章、分类入口和一个行动按钮。',
	)
);
npcink_abilities_toolkit_assert_same( 'block_theme_site_plan', $homepage_template_layout_intent_route['data']['route']['route'] ?? '', 'route-content-intent maps homepage template layout requests to block theme site plans' );
npcink_abilities_toolkit_assert_same( 'customize_template_layout', $homepage_template_layout_intent_route['data']['route']['recommended_plan_input']['intent'] ?? '', 'route-content-intent recommends the bounded template layout intent for homepage layouts' );
npcink_abilities_toolkit_assert_same( array( 'front-page' ), $homepage_template_layout_intent_route['data']['route']['recommended_plan_input']['target_templates'] ?? array(), 'route-content-intent scopes homepage layouts to front-page' );
npcink_abilities_toolkit_assert_same( 'homepage_landing', $homepage_template_layout_intent_route['data']['route']['recommended_plan_input']['layout_profile'] ?? '', 'route-content-intent recommends the homepage layout profile' );

$homepage_template_layout_guardrailed_intent_route = $core_read_package->route_content_intent(
	array(
		'prompt' => '把首页改造成一个基础落地页：顶部有清晰的大标题和简短介绍，下面有一个行动按钮，再下面展示最新文章和分类入口。不要改导航，不要改 global styles，不要写 theme.json，不要写主题文件，不要输出 raw template HTML，只通过块主题模板 proposal 来处理。',
	)
);
npcink_abilities_toolkit_assert_same( 'block_theme_site_plan', $homepage_template_layout_guardrailed_intent_route['data']['route']['route'] ?? '', 'route-content-intent keeps homepage layout supported when unsupported surfaces are negated guardrails' );
npcink_abilities_toolkit_assert_same( 'site_template_layout', $homepage_template_layout_guardrailed_intent_route['data']['route']['route_key'] ?? '', 'route-content-intent keeps guardrailed homepage layout on the template layout route' );
npcink_abilities_toolkit_assert_same( 'customize_template_layout', $homepage_template_layout_guardrailed_intent_route['data']['route']['recommended_plan_input']['intent'] ?? '', 'route-content-intent recommends template layout for guardrailed homepage prompts' );
npcink_abilities_toolkit_assert_same( array( 'front-page' ), $homepage_template_layout_guardrailed_intent_route['data']['route']['recommended_plan_input']['target_templates'] ?? array(), 'route-content-intent scopes guardrailed homepage layout prompts to front-page' );
npcink_abilities_toolkit_assert_same( 'homepage_landing', $homepage_template_layout_guardrailed_intent_route['data']['route']['recommended_plan_input']['layout_profile'] ?? '', 'route-content-intent recommends homepage landing for guardrailed homepage prompts' );

$template_part_intent_route = $core_read_package->route_content_intent(
	array(
		'prompt' => '帮我重做页眉模板部件。',
	)
);
npcink_abilities_toolkit_assert_same( 'unsupported', $template_part_intent_route['data']['route']['route'] ?? '', 'route-content-intent fails closed for template part edits without a recipe' );
npcink_abilities_toolkit_assert_same( true, $template_part_intent_route['data']['route']['needs_clarification'] ?? false, 'route-content-intent asks for clarification for unsupported template parts' );
npcink_abilities_toolkit_assert_same( 'template_part_recipe_not_available', $template_part_intent_route['data']['route']['unsupported_reason'] ?? '', 'route-content-intent reports the missing template part recipe' );

$template_part_breadcrumb_intent_route = $core_read_package->route_content_intent(
	array(
		'prompt' => '在页眉模板部件里加面包屑导航。',
	)
);
npcink_abilities_toolkit_assert_same( 'unsupported', $template_part_breadcrumb_intent_route['data']['route']['route'] ?? '', 'route-content-intent fails closed for template part breadcrumb requests' );
npcink_abilities_toolkit_assert_same( 'template_part_recipe_not_available', $template_part_breadcrumb_intent_route['data']['route']['unsupported_reason'] ?? '', 'route-content-intent does not route template part breadcrumb requests into template writes' );

$navigation_intent_route = $core_read_package->route_content_intent(
	array(
		'prompt' => 'Change the navigation menu and add a Products link.',
	)
);
npcink_abilities_toolkit_assert_same( 'unsupported', $navigation_intent_route['data']['route']['route'] ?? '', 'route-content-intent fails closed for navigation menu writes' );
npcink_abilities_toolkit_assert_same( '', $navigation_intent_route['data']['route']['plan_ability_id'] ?? 'unexpected', 'route-content-intent does not select a plan ability for navigation menu writes' );
npcink_abilities_toolkit_assert_same( 'navigation_write_not_supported', $navigation_intent_route['data']['route']['unsupported_reason'] ?? '', 'route-content-intent reports unsupported navigation writes precisely' );
npcink_abilities_toolkit_assert_true( ! isset( $navigation_intent_route['data']['write_actions'] ), 'route-content-intent does not create navigation write actions' );

$global_styles_intent_route = $core_read_package->route_content_intent(
	array(
		'prompt' => 'Change global styles and write a theme.json color patch.',
	)
);
npcink_abilities_toolkit_assert_same( 'unsupported', $global_styles_intent_route['data']['route']['route'] ?? '', 'route-content-intent fails closed for global style writes' );
npcink_abilities_toolkit_assert_same( '', $global_styles_intent_route['data']['route']['plan_ability_id'] ?? 'unexpected', 'route-content-intent does not select a plan ability for global style writes' );
npcink_abilities_toolkit_assert_same( 'global_styles_write_not_supported', $global_styles_intent_route['data']['route']['unsupported_reason'] ?? '', 'route-content-intent reports unsupported global style writes precisely' );
npcink_abilities_toolkit_assert_true( ! isset( $global_styles_intent_route['data']['write_actions'] ), 'route-content-intent does not create global style write actions' );

$custom_html_intent_route = $core_read_package->route_content_intent(
	array(
		'prompt' => 'Directly execute a custom HTML template change.',
	)
);
npcink_abilities_toolkit_assert_same( 'unsupported', $custom_html_intent_route['data']['route']['route'] ?? '', 'route-content-intent fails closed for custom HTML template writes' );
npcink_abilities_toolkit_assert_same( '', $custom_html_intent_route['data']['route']['plan_ability_id'] ?? 'unexpected', 'route-content-intent does not select a plan ability for custom HTML template writes' );
npcink_abilities_toolkit_assert_same( 'custom_html_template_not_supported', $custom_html_intent_route['data']['route']['unsupported_reason'] ?? '', 'route-content-intent reports unsupported custom HTML template writes precisely' );
npcink_abilities_toolkit_assert_true( ! isset( $custom_html_intent_route['data']['write_actions'] ), 'route-content-intent does not create custom HTML template write actions' );

$ambiguous_intent_route = $core_read_package->route_content_intent(
	array(
		'prompt' => '帮我做一个页面文章草稿。',
	)
);
npcink_abilities_toolkit_assert_same( 'unsupported', $ambiguous_intent_route['data']['route']['route'] ?? '', 'route-content-intent fails closed for ambiguous page/article prompts' );
npcink_abilities_toolkit_assert_same( 'ambiguous_page_vs_article', $ambiguous_intent_route['data']['route']['unsupported_reason'] ?? '', 'route-content-intent reports page/article ambiguity' );

$recipe_eval_posts_fixture = $GLOBALS['npcink_abilities_toolkit_unit_style_posts'];
$recipe_eval_options_fixture = $GLOBALS['npcink_abilities_toolkit_unit_options'] ?? array();
$recipe_eval_block_theme_fixture = $GLOBALS['npcink_abilities_toolkit_unit_is_block_theme'] ?? null;
$GLOBALS['npcink_abilities_toolkit_unit_options'] = array(
	'show_on_front'  => 'posts',
	'page_for_posts' => 611,
);
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'] = array(
	608 => (object) array(
		'ID'           => 608,
		'post_type'    => 'wp_template',
		'post_status'  => 'publish',
		'post_title'   => 'Single',
		'post_content' => '<!-- wp:template-part {"slug":"header"} /--><!-- wp:group {"tagName":"main"} --><main class="wp-block-group"><!-- wp:post-title /--><!-- wp:post-content /--></main><!-- /wp:group --><!-- wp:template-part {"slug":"footer"} /-->',
		'post_excerpt' => '',
		'post_author'  => 7,
		'post_name'    => 'single',
		'post_parent'  => 0,
	),
	609 => (object) array(
		'ID'           => 609,
		'post_type'    => 'wp_template',
		'post_status'  => 'publish',
		'post_title'   => 'Page',
		'post_content' => '<!-- wp:template-part {"slug":"header"} /--><!-- wp:group {"tagName":"main"} --><main class="wp-block-group"><!-- wp:post-title /--><!-- wp:post-content /--></main><!-- /wp:group --><!-- wp:template-part {"slug":"footer"} /-->',
		'post_excerpt' => '',
		'post_author'  => 7,
		'post_name'    => 'page',
		'post_parent'  => 0,
	),
	610 => (object) array(
		'ID'           => 610,
		'post_type'    => 'wp_template',
		'post_status'  => 'publish',
		'post_title'   => 'Front Page',
		'post_content' => '<!-- wp:template-part {"slug":"header"} /--><!-- wp:group {"tagName":"main"} --><main class="wp-block-group"><!-- wp:post-title /--><!-- wp:post-content /--></main><!-- /wp:group --><!-- wp:template-part {"slug":"footer"} /-->',
		'post_excerpt' => '',
		'post_author'  => 7,
		'post_name'    => 'front-page',
		'post_parent'  => 0,
	),
	611 => (object) array(
		'ID'           => 611,
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'Blog',
		'post_content' => 'Blog page fixture.',
		'post_excerpt' => '',
		'post_author'  => 7,
		'post_name'    => 'blog',
		'post_parent'  => 0,
	),
);
$GLOBALS['npcink_abilities_toolkit_unit_is_block_theme'] = true;
$gutenberg_recipe_eval = $core_read_package->evaluate_gutenberg_recipe_suite(
	array(
		'minimum_pass_rate' => 1,
		'media_fixture'     => array(
			'url'           => 'https://magick-ai.local/wp-content/uploads/2026/06/preview.webp',
			'attachment_id' => 8053,
			'alt'           => 'WordPress AI governed workflow hero visual',
		),
		'cases'             => array(
			array(
				'id'             => 'eval_page_landing',
				'prompt'         => '帮我做一个现代官网介绍页，需要配图，手机端也要好看。',
				'expected_route' => 'pattern_page_plan',
			),
			array(
				'id'             => 'eval_article',
				'prompt'         => '写一篇对比评测文章，加一张配图和 FAQ。',
				'expected_route' => 'article_block_plan',
			),
			array(
				'id'             => 'eval_template',
				'prompt'         => '给文章模板加面包屑导航。',
				'expected_route' => 'block_theme_site_plan',
			),
			array(
				'id'                 => 'eval_navigation_fail_closed',
				'prompt'             => 'Change the navigation menu and add a Products link.',
				'expected_route'     => 'unsupported',
				'expected_supported' => false,
			),
		),
	)
);
$gutenberg_recipe_default_eval = $core_read_package->evaluate_gutenberg_recipe_suite(
	array(
		'minimum_pass_rate'    => 1,
		'include_case_details' => false,
		'media_fixture'        => array(
			'url'           => 'https://magick-ai.local/wp-content/uploads/2026/06/preview.webp',
			'attachment_id' => 8053,
			'alt'           => 'WordPress AI governed workflow hero visual',
		),
	)
);
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'] = $recipe_eval_posts_fixture;
$GLOBALS['npcink_abilities_toolkit_unit_options']     = $recipe_eval_options_fixture;
if ( null === $recipe_eval_block_theme_fixture ) {
	unset( $GLOBALS['npcink_abilities_toolkit_unit_is_block_theme'] );
} else {
	$GLOBALS['npcink_abilities_toolkit_unit_is_block_theme'] = $recipe_eval_block_theme_fixture;
}
npcink_abilities_toolkit_assert_same( true, $gutenberg_recipe_eval['success'] ?? null, 'evaluate-gutenberg-recipe-suite returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'gutenberg_recipe_suite_evaluation', $gutenberg_recipe_eval['data']['artifact_type'] ?? '', 'Gutenberg recipe evaluation declares its artifact type' );
npcink_abilities_toolkit_assert_same( 'route_and_plan_only', $gutenberg_recipe_eval['data']['evaluation_mode'] ?? '', 'Gutenberg recipe evaluation only routes and builds plans' );
npcink_abilities_toolkit_assert_same( false, $gutenberg_recipe_eval['data']['direct_wordpress_write'] ?? null, 'Gutenberg recipe evaluation does not write WordPress content' );
npcink_abilities_toolkit_assert_same( false, $gutenberg_recipe_eval['data']['commit_execution'] ?? null, 'Gutenberg recipe evaluation does not execute commits' );
npcink_abilities_toolkit_assert_same( false, $gutenberg_recipe_eval['data']['proposal_created'] ?? null, 'Gutenberg recipe evaluation does not create proposals' );
npcink_abilities_toolkit_assert_same( 'pass', $gutenberg_recipe_eval['data']['suite_status'] ?? '', 'Gutenberg recipe evaluation passes when every case clears the gates' );
npcink_abilities_toolkit_assert_same( 2, $gutenberg_recipe_eval['data']['review_contract']['reviewer_count'] ?? 0, 'Gutenberg recipe evaluation exposes a two-reviewer contract' );
npcink_abilities_toolkit_assert_same( 'pass', $gutenberg_recipe_eval['data']['dual_review']['consensus']['decision'] ?? '', 'Gutenberg recipe evaluation consensus passes clean suites' );
npcink_abilities_toolkit_assert_same( 4, $gutenberg_recipe_eval['data']['summary']['total_cases'] ?? 0, 'Gutenberg recipe evaluation counts evaluated cases' );
npcink_abilities_toolkit_assert_same( 4, $gutenberg_recipe_eval['data']['summary']['passed_cases'] ?? 0, 'Gutenberg recipe evaluation counts passed cases' );
npcink_abilities_toolkit_assert_same( 1.0, $gutenberg_recipe_eval['data']['summary']['pass_rate'] ?? 0, 'Gutenberg recipe evaluation reports pass rate' );
$gutenberg_recipe_eval_cases = is_array( $gutenberg_recipe_eval['data']['cases'] ?? null ) ? $gutenberg_recipe_eval['data']['cases'] : array();
npcink_abilities_toolkit_assert_same( 'pattern_page_plan', $gutenberg_recipe_eval_cases[0]['route'] ?? '', 'Gutenberg recipe evaluation routes page cases to Pattern plans' );
npcink_abilities_toolkit_assert_same( 0, $gutenberg_recipe_eval_cases[0]['block_summary']['core_html_count'] ?? -1, 'Gutenberg recipe evaluation rejects core/html in page plans' );
npcink_abilities_toolkit_assert_same( array(), $gutenberg_recipe_eval_cases[0]['block_summary']['non_core_blocks'] ?? array( 'unexpected' ), 'Gutenberg recipe evaluation rejects non-core page blocks' );
npcink_abilities_toolkit_assert_same( 'pass', $gutenberg_recipe_eval_cases[0]['dual_review']['recipe_fit_reviewer']['decision'] ?? '', 'Gutenberg recipe evaluation case reviewer passes a valid page recipe' );
npcink_abilities_toolkit_assert_same( 'pass', $gutenberg_recipe_eval_cases[0]['dual_review']['governance_boundary_reviewer']['decision'] ?? '', 'Gutenberg recipe evaluation governance reviewer passes read-only plans' );
npcink_abilities_toolkit_assert_same( 'article_block_plan', $gutenberg_recipe_eval_cases[1]['route'] ?? '', 'Gutenberg recipe evaluation routes article cases to article plans' );
npcink_abilities_toolkit_assert_same( 'block_theme_site_plan', $gutenberg_recipe_eval_cases[2]['route'] ?? '', 'Gutenberg recipe evaluation routes template cases to block theme plans' );
npcink_abilities_toolkit_assert_same( 'pass', $gutenberg_recipe_eval_cases[2]['plan_summary']['template_placement_contract']['contract_status'] ?? '', 'Gutenberg recipe evaluation exports passing template placement contracts for AI judges' );
npcink_abilities_toolkit_assert_same( 'bounded_template_anchor_placement', $gutenberg_recipe_eval_cases[2]['plan_summary']['template_placement_contract']['placement_model'] ?? '', 'Gutenberg recipe evaluation exports the template placement model for AI judges' );
npcink_abilities_toolkit_assert_same( true, $gutenberg_recipe_eval_cases[2]['plan_summary']['template_placement_contract']['placements'][0]['anchor_allowed'] ?? null, 'Gutenberg recipe evaluation exposes allowed template anchors for AI judges' );
if ( 'no_changes_required' === ( $gutenberg_recipe_eval_cases[2]['plan_summary']['quality_gate_status'] ?? '' ) ) {
	npcink_abilities_toolkit_assert_true( ( $gutenberg_recipe_eval_cases[2]['plan_summary']['no_change_context']['no_change_count'] ?? 0 ) > 0, 'Gutenberg recipe evaluation explains template no-op cases for AI judges' );
	npcink_abilities_toolkit_assert_true( in_array( 'breadcrumbs_already_before_post_title', $gutenberg_recipe_eval_cases[2]['plan_summary']['no_change_context']['no_change_reasons'] ?? array(), true ), 'Gutenberg recipe evaluation exports stable template no-op reasons' );
}
npcink_abilities_toolkit_assert_same( 'unsupported', $gutenberg_recipe_eval_cases[3]['route'] ?? '', 'Gutenberg recipe evaluation keeps unsupported navigation requests fail-closed' );
npcink_abilities_toolkit_assert_same( array(), $gutenberg_recipe_eval['data']['failure_summary']['failure_count_by_code'] ?? array( 'unexpected' ), 'Gutenberg recipe evaluation reports no failure codes for passing suites' );
npcink_abilities_toolkit_assert_same( true, $gutenberg_recipe_default_eval['success'] ?? null, 'default Gutenberg recipe evaluation returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'pass', $gutenberg_recipe_default_eval['data']['suite_status'] ?? '', 'default Gutenberg recipe evaluation suite passes' );
npcink_abilities_toolkit_assert_same( 30, $gutenberg_recipe_default_eval['data']['summary']['total_cases'] ?? 0, 'default Gutenberg recipe evaluation covers 30 natural-language cases' );
npcink_abilities_toolkit_assert_same( 30, $gutenberg_recipe_default_eval['data']['summary']['passed_cases'] ?? 0, 'default Gutenberg recipe evaluation passes every built-in case' );
npcink_abilities_toolkit_assert_same( 1.0, $gutenberg_recipe_default_eval['data']['summary']['pass_rate'] ?? 0, 'default Gutenberg recipe evaluation reports a full pass rate under fixtures' );
npcink_abilities_toolkit_assert_same( array(), $gutenberg_recipe_default_eval['data']['failure_summary']['failure_count_by_code'] ?? array( 'unexpected' ), 'default Gutenberg recipe evaluation reports no built-in failure codes' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/get-gutenberg-block-capability-catalog', $gutenberg_recipe_default_eval['data']['guardrails']['block_capability_catalog_ability_id'] ?? '', 'Gutenberg recipe evaluation points agents at the block capability catalog' );
npcink_abilities_toolkit_assert_same( 'gutenberg_native_v1', $gutenberg_recipe_default_eval['data']['guardrails']['block_capability_catalog_id'] ?? '', 'Gutenberg recipe evaluation reports the block capability catalog id' );
npcink_abilities_toolkit_assert_same( 'bounded_block_composition', $gutenberg_recipe_default_eval['data']['guardrails']['composition_model'] ?? '', 'Gutenberg recipe evaluation reports bounded block composition' );
$gutenberg_recipe_composer = file_get_contents( dirname( dirname( __DIR__ ) ) . '/composer.json' );
npcink_abilities_toolkit_assert_true( is_string( $gutenberg_recipe_composer ) && false !== strpos( $gutenberg_recipe_composer, 'smoke:block-theme-host-proof' ), 'Composer exposes the explicit real block-theme host proof command' );
npcink_abilities_toolkit_assert_true( is_string( $gutenberg_recipe_composer ) && false !== strpos( $gutenberg_recipe_composer, 'eval:gutenberg-recipe:suite' ), 'Composer exposes Gutenberg recipe suite export command' );
npcink_abilities_toolkit_assert_true( is_string( $gutenberg_recipe_composer ) && false !== strpos( $gutenberg_recipe_composer, 'eval:gutenberg-recipe:judge:eval-lab' ), 'Composer exposes Gutenberg recipe eval-lab judge wrapper command' );
npcink_abilities_toolkit_assert_true( is_string( $gutenberg_recipe_composer ) && false !== strpos( $gutenberg_recipe_composer, 'export-default-suite.php' ), 'Gutenberg recipe eval-lab wrapper exports the latest default suite before judging' );
npcink_abilities_toolkit_assert_true( is_string( $gutenberg_recipe_composer ) && false !== strpos( $gutenberg_recipe_composer, 'task=gutenberg_judge_cross' ), 'Gutenberg recipe eval-lab wrapper calls the Eval Lab Gutenberg task registry' );
npcink_abilities_toolkit_assert_true( is_string( $gutenberg_recipe_composer ) && false === strpos( $gutenberg_recipe_composer, 'sk-' ), 'Gutenberg recipe eval-lab wrapper does not contain committed API keys' );
$gutenberg_recipe_eval_lab_wrapper = file_get_contents( dirname( dirname( __DIR__ ) ) . '/scripts/eval-lab.sh' );
npcink_abilities_toolkit_assert_true( is_string( $gutenberg_recipe_eval_lab_wrapper ) && false !== strpos( $gutenberg_recipe_eval_lab_wrapper, 'NPCINK_EVAL_LAB_PATH' ), 'Gutenberg recipe eval-lab wrapper keeps provider calls outside Toolkit' );
npcink_abilities_toolkit_assert_true( is_string( $gutenberg_recipe_eval_lab_wrapper ) && false !== strpos( $gutenberg_recipe_eval_lab_wrapper, 'COMPOSER_PROCESS_TIMEOUT' ), 'Gutenberg recipe eval-lab wrapper permits long provider-backed triad runs' );
npcink_abilities_toolkit_assert_true( is_string( $gutenberg_recipe_eval_lab_wrapper ) && false === strpos( $gutenberg_recipe_eval_lab_wrapper, 'API_KEY' ), 'Eval Lab wrapper does not read provider keys in the plugin repo' );
$gutenberg_recipe_manual_review = json_decode( (string) file_get_contents( dirname( dirname( __DIR__ ) ) . '/tests/gutenberg-recipe-eval/manual-review-cases.json' ), true );
$gutenberg_recipe_challenge_cases = json_decode( (string) file_get_contents( dirname( dirname( __DIR__ ) ) . '/tests/gutenberg-recipe-eval/challenge-cases.json' ), true );
npcink_abilities_toolkit_assert_same( 'gutenberg_recipe_manual_review_queue', $gutenberg_recipe_manual_review['artifact_type'] ?? '', 'Gutenberg recipe eval keeps a manual adjudication queue' );
npcink_abilities_toolkit_assert_same( array(), $gutenberg_recipe_manual_review['items'] ?? array( 'unexpected' ), 'Gutenberg recipe manual adjudication queue starts empty after clean triad runs' );
npcink_abilities_toolkit_assert_same( 'gutenberg_recipe_challenge_cases', $gutenberg_recipe_challenge_cases['artifact_type'] ?? '', 'Gutenberg recipe eval keeps challenge cases separate from the all-green suite' );
npcink_abilities_toolkit_assert_true( count( $gutenberg_recipe_challenge_cases['cases'] ?? array() ) >= 10, 'Gutenberg recipe challenge cases cover boundary and ambiguity prompts' );
$composer_repair_loop = $core_read_package->compose_gutenberg_block_plan(
	array(
		'prompt'      => '帮我做一个现代官网介绍页，需要配图，手机端也要好看。',
		'target_hint' => 'page',
		'plan_input'  => array(
			'pattern_id'     => 'openai-style-landing',
			'media_strategy' => 'existing_media_url',
			'variables'      => array(
				'hero_title'     => 'A very long Gutenberg native landing page headline that should be shortened before proposal handoff because it would be too dense inside the hero section',
				'hero_media_url' => 'https://magick-ai.local/wp-content/uploads/2026/06/composer-repair-dashboard.jpg',
			),
		),
	)
);
npcink_abilities_toolkit_assert_same( true, $composer_repair_loop['success'] ?? null, 'compose-gutenberg-block-plan returns a success envelope for repairable page requests' );
npcink_abilities_toolkit_assert_same( 'gutenberg_composer_repair_loop', $composer_repair_loop['data']['artifact_type'] ?? '', 'compose-gutenberg-block-plan declares the composer repair artifact type' );
npcink_abilities_toolkit_assert_same( 'gutenberg_native_v1', $composer_repair_loop['data']['block_capability_catalog_id'] ?? '', 'compose-gutenberg-block-plan references the Gutenberg block catalog' );
npcink_abilities_toolkit_assert_same( false, $composer_repair_loop['data']['proposal_created'] ?? true, 'compose-gutenberg-block-plan does not create Core proposals' );
npcink_abilities_toolkit_assert_same( false, $composer_repair_loop['data']['direct_wordpress_write'] ?? true, 'compose-gutenberg-block-plan does not write WordPress' );
npcink_abilities_toolkit_assert_true( in_array( 'hero_title_too_long', $composer_repair_loop['data']['initial_review']['finding_codes'] ?? array(), true ), 'compose-gutenberg-block-plan reports overlong hero title findings before repair' );
npcink_abilities_toolkit_assert_true( in_array( 'media_alt_missing', $composer_repair_loop['data']['initial_review']['finding_codes'] ?? array(), true ), 'compose-gutenberg-block-plan reports missing media alt findings before repair' );
$composer_repair_codes = array_map(
	static function ( $repair ) {
		return is_array( $repair ) ? (string) ( $repair['repair_code'] ?? '' ) : '';
	},
	is_array( $composer_repair_loop['data']['applied_repairs'] ?? null ) ? $composer_repair_loop['data']['applied_repairs'] : array()
);
npcink_abilities_toolkit_assert_true( in_array( 'shorten_overlong_heading', $composer_repair_codes, true ), 'compose-gutenberg-block-plan applies an overlong heading repair' );
npcink_abilities_toolkit_assert_true( in_array( 'fill_missing_media_alt', $composer_repair_codes, true ), 'compose-gutenberg-block-plan applies a missing media alt repair' );
npcink_abilities_toolkit_assert_same( true, $composer_repair_loop['data']['final_review']['ready_for_proposal'] ?? null, 'compose-gutenberg-block-plan marks repaired page plans proposal eligible' );
npcink_abilities_toolkit_assert_same( true, $composer_repair_loop['data']['proposal_allowed'] ?? null, 'compose-gutenberg-block-plan allows proposal handoff only after the final gate passes' );
$composer_repaired_plan = is_array( $composer_repair_loop['data']['plan'] ?? null ) ? $composer_repair_loop['data']['plan'] : array();
npcink_abilities_toolkit_assert_same( 'pattern_page_plan', $composer_repaired_plan['artifact_type'] ?? '', 'compose-gutenberg-block-plan exposes the repaired page plan as the proposal candidate' );

$composer_missing_media_repair = $core_read_package->compose_gutenberg_block_plan(
	array(
		'prompt'      => '写一篇介绍 Gutenberg 模块红利的文章草稿，需要配图和 FAQ。',
		'target_hint' => 'post',
		'plan_input'  => array(
			'title'            => 'Composer Missing Media Article',
			'article_template' => 'comparison-review',
			'media_strategy'   => 'existing_media_url',
		),
	)
);
npcink_abilities_toolkit_assert_same( true, $composer_missing_media_repair['success'] ?? null, 'compose-gutenberg-block-plan returns a success envelope for missing media article requests' );
npcink_abilities_toolkit_assert_true( in_array( 'missing_reviewed_media', $composer_missing_media_repair['data']['initial_review']['finding_codes'] ?? array(), true ), 'compose-gutenberg-block-plan reports missing reviewed media before repair' );
$composer_missing_media_repair_codes = array_map(
	static function ( $repair ) {
		return is_array( $repair ) ? (string) ( $repair['repair_code'] ?? '' ) : '';
	},
	is_array( $composer_missing_media_repair['data']['applied_repairs'] ?? null ) ? $composer_missing_media_repair['data']['applied_repairs'] : array()
);
npcink_abilities_toolkit_assert_true( in_array( 'fallback_to_no_media_article_structure', $composer_missing_media_repair_codes, true ), 'compose-gutenberg-block-plan applies a missing article media fallback repair' );
npcink_abilities_toolkit_assert_same( true, $composer_missing_media_repair['data']['proposal_allowed'] ?? null, 'compose-gutenberg-block-plan marks repaired missing-media article plans proposal eligible' );

$composer_product_docs = $core_read_package->compose_gutenberg_block_plan(
	array(
		'prompt'              => '写一篇教程，说明如何用 Gutenberg 块组织一篇长文。',
		'composer_profile_id' => 'product_docs',
	)
);
npcink_abilities_toolkit_assert_same( true, $composer_product_docs['success'] ?? null, 'compose-gutenberg-block-plan accepts explicit product docs composer profiles' );
npcink_abilities_toolkit_assert_same( 'product_docs', $composer_product_docs['data']['composer_profile_id'] ?? '', 'compose-gutenberg-block-plan preserves compatible explicit composer profile ids' );
npcink_abilities_toolkit_assert_same( 'how-to-guide', $composer_product_docs['data']['plan']['article_template'] ?? '', 'product docs composer profile selects the how-to article template by default' );
npcink_abilities_toolkit_assert_same( true, $composer_product_docs['data']['proposal_allowed'] ?? null, 'product docs composer profile output is proposal eligible after the final gate' );

$composer_pilot_prompts = array(
	'帮我做一个现代官网介绍页，需要配图，手机端也要好看。',
	'做一个 SaaS 产品首页，突出核心能力、客户价值和移动端体验。',
	'帮我做一个有色彩强调的 editorial-accent 官网落地页。',
	'创建一个服务介绍页面，先不要配图，但要有清楚的功能、FAQ 和 CTA。',
	'帮我搭一个 WordPress 插件功能介绍页面，结构清楚，适合客户浏览。',
	'给产品做一个移动端优先的首页，标题不要挤，内容要能扫读。',
	'写一篇介绍 Gutenberg 模块红利的文章草稿，需要配图和 FAQ。',
	'写一篇对比评测文章，说明普通 AI 直接写入和 proposal-first 的区别。',
	'写一篇博客文章，解释内容编辑为什么要经过 proposal 审核。',
	'写一篇教程，说明如何用 Gutenberg 块组织一篇长文。',
	'写一篇产品文档文章，介绍 OpenClaw 如何先生成 proposal 再执行。',
	'帮我写一篇博客长文，主题是 WordPress 内容治理。',
);
$composer_pilot_passed = 0;
foreach ( $composer_pilot_prompts as $composer_pilot_prompt ) {
	$composer_pilot_result = $core_read_package->compose_gutenberg_block_plan(
		array(
			'prompt'        => $composer_pilot_prompt,
			'media_fixture' => array(
				'url'           => 'https://magick-ai.local/wp-content/uploads/2026/06/pilot-hero.jpg',
				'attachment_id' => 8053,
				'alt'           => 'OpenClaw Gutenberg pilot visual',
			),
		)
	);
	if ( true === ( $composer_pilot_result['success'] ?? null ) && true === ( $composer_pilot_result['data']['proposal_allowed'] ?? null ) && false === ( $composer_pilot_result['data']['proposal_created'] ?? true ) ) {
		++$composer_pilot_passed;
	}
}
npcink_abilities_toolkit_assert_same( 12, $composer_pilot_passed, 'compose-gutenberg-block-plan passes a twelve-task natural-language pilot without creating proposals' );

