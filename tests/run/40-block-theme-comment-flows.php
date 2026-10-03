<?php
/**
 * Ordered regression suite part: 40-block-theme-comment-flows.
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

$GLOBALS['npcink_ai_runtime_wp_ability_context'] = array( 'context' => array( 'approval_commit_authorized' => true ) );
$nested_blocks_written = $core_write_package->update_post_blocks(
	array(
		'post_id'            => 501,
		'validate_roundtrip' => false,
		'blocks'             => array(
			array(
				'blockName'    => 'core/group',
				'attrs'        => array(),
				'innerHTML'    => '<div class="wp-block-group"></div>',
				'innerContent' => array( '<div class="wp-block-group">', null, '</div>' ),
				'innerBlocks'  => array(
					array(
						'blockName'    => 'core/paragraph',
						'attrs'        => array(),
						'innerHTML'    => '<p>Nested body.</p>',
						'innerContent' => array( '<p>Nested body.</p>' ),
						'innerBlocks'  => array(),
					),
				),
			),
		),
		'commit'            => true,
	)
);
unset( $GLOBALS['npcink_ai_runtime_wp_ability_context'] );
npcink_abilities_toolkit_assert_same( false, $nested_blocks_written['dry_run'] ?? null, 'update-post-blocks commit returns a committed payload for nested parsed blocks' );
	$nested_content = (string) ( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][501]->post_content ?? '' );
	npcink_abilities_toolkit_assert_true( false !== strpos( $nested_content, '<div class="wp-block-group"><!-- wp:paragraph -->' ), 'update-post-blocks serializes innerBlocks at innerContent null markers' );
	npcink_abilities_toolkit_assert_true( false === strpos( $nested_content, '</div><!-- wp:paragraph -->' ), 'update-post-blocks does not append innerBlocks after the parent wrapper' );
	$block_theme_context = $core_read_package->get_block_theme_context( array() );
	npcink_abilities_toolkit_assert_same( true, $block_theme_context['is_block_theme'] ?? null, 'get-block-theme-context reports active block theme state' );
	npcink_abilities_toolkit_assert_same( 3, count( $block_theme_context['templates'] ?? array() ), 'get-block-theme-context lists available template entities including front-page' );
	npcink_abilities_toolkit_assert_same( 'posts', $block_theme_context['reading_settings']['show_on_front'] ?? '', 'get-block-theme-context exposes front page reading mode' );
	npcink_abilities_toolkit_assert_same( 'front-page', $block_theme_context['template_resolution']['front_page']['target_slug'] ?? '', 'get-block-theme-context exposes homepage template resolution' );
	npcink_abilities_toolkit_assert_true( ! empty( $block_theme_context['existing_overrides']['front-page']['content_hash'] ?? '' ), 'get-block-theme-context exposes existing template override hashes' );
	npcink_abilities_toolkit_assert_true( is_array( $block_theme_context['content_inventory']['candidate_cta_pages'] ?? null ), 'get-block-theme-context exposes CTA candidate inventory' );
	$template_blocks = $core_read_package->get_template_blocks( array( 'slug' => 'single' ) );
	npcink_abilities_toolkit_assert_same( 601, $template_blocks['post_id'] ?? 0, 'get-template-blocks resolves a template by slug' );
	npcink_abilities_toolkit_assert_true( ( $template_blocks['block_count'] ?? 0 ) > 0, 'get-template-blocks parses template blocks' );
	$template_surface_review = $core_read_package->review_block_editor_surface(
		array(
			'post_type' => 'wp_template',
			'slug'      => 'single',
		)
	);
	npcink_abilities_toolkit_assert_same( true, $template_surface_review['success'] ?? null, 'review-block-editor-surface reviews Site Editor templates by slug' );
	npcink_abilities_toolkit_assert_same( 'block_editor_surface_review', $template_surface_review['data']['artifact_type'] ?? '', 'review-block-editor-surface declares a generic block surface review artifact' );
	npcink_abilities_toolkit_assert_same( 'site_editor_template', $template_surface_review['data']['block_editor_surface']['surface_kind'] ?? '', 'review-block-editor-surface identifies template surfaces' );
	npcink_abilities_toolkit_assert_same( 'site_editor', $template_surface_review['data']['block_editor_surface']['editor'] ?? '', 'review-block-editor-surface reports Site Editor ownership' );
	npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/get-template-blocks', $template_surface_review['data']['block_editor_surface']['read_ability_id'] ?? '', 'review-block-editor-surface points template reads at get-template-blocks' );
	npcink_abilities_toolkit_assert_same( false, $template_surface_review['data']['direct_wordpress_write'] ?? null, 'review-block-editor-surface does not write Site Editor templates' );
	npcink_abilities_toolkit_assert_true( in_array( 'plan_site_editor_block_change', $template_surface_review['data']['next_actions'] ?? array(), true ), 'review-block-editor-surface recommends governed Site Editor planning for template changes' );
	$block_theme_plan = $core_read_package->build_block_theme_site_plan(
		array(
			'intent' => 'add_breadcrumbs',
			'target_templates' => array( 'single', 'page' ),
			'separator' => '>',
		)
	);
	npcink_abilities_toolkit_assert_same( true, $block_theme_plan['success'] ?? null, 'build-block-theme-site-plan returns a success envelope' );
	npcink_abilities_toolkit_assert_same( 'block_theme_site_plan', $block_theme_plan['data']['artifact_type'] ?? '', 'build-block-theme-site-plan declares block theme site artifact type' );
	npcink_abilities_toolkit_assert_same( 'site_editor_template', $block_theme_plan['data']['block_editor_surface']['surface_kind'] ?? '', 'build-block-theme-site-plan declares a Site Editor template surface' );
	npcink_abilities_toolkit_assert_same( 'site_editor', $block_theme_plan['data']['block_editor_surface']['editor'] ?? '', 'build-block-theme-site-plan declares the Site Editor owner' );
	npcink_abilities_toolkit_assert_same( array( 'wp_template' ), $block_theme_plan['data']['block_editor_surface']['post_types'] ?? array(), 'build-block-theme-site-plan declares template post types' );
	npcink_abilities_toolkit_assert_same( 'update_or_create_template_override', $block_theme_plan['data']['block_editor_surface']['target_mode'] ?? '', 'build-block-theme-site-plan reports template override target mode' );
	npcink_abilities_toolkit_assert_same( false, $block_theme_plan['data']['direct_wordpress_write'] ?? null, 'build-block-theme-site-plan does not directly write WordPress' );
	npcink_abilities_toolkit_assert_same( false, $block_theme_plan['data']['commit_execution'] ?? null, 'build-block-theme-site-plan keeps commit execution disabled' );
	npcink_abilities_toolkit_assert_same( 'site_editor_template_batch', $block_theme_plan['data']['block_editor_quality_gate']['profile'] ?? '', 'build-block-theme-site-plan exposes a Site Editor template batch quality gate' );
	npcink_abilities_toolkit_assert_same( true, $block_theme_plan['data']['block_editor_quality_gate']['ready_for_proposal'] ?? null, 'build-block-theme-site-plan marks reviewed template changes ready for proposal' );
	npcink_abilities_toolkit_assert_same( false, $block_theme_plan['data']['block_editor_quality_gate']['commit_execution'] ?? null, 'build-block-theme-site-plan quality gate does not execute commits' );
	npcink_abilities_toolkit_assert_same( 2, count( $block_theme_plan['data']['block_editor_reviews'] ?? array() ), 'build-block-theme-site-plan reviews each generated template block change' );
	$block_theme_actions = is_array( $block_theme_plan['data']['write_actions'] ?? null ) ? $block_theme_plan['data']['write_actions'] : array();
	npcink_abilities_toolkit_assert_same( 2, count( $block_theme_actions ), 'build-block-theme-site-plan emits one action per found template target' );
	npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/update-template-blocks', $block_theme_actions[0]['target_ability_id'] ?? '', 'block theme site plan targets template block writes' );
	npcink_abilities_toolkit_assert_same( 'core/template-part', $block_theme_actions[0]['input']['blocks'][0]['blockName'] ?? '', 'block theme site plan keeps the header template part first' );
	npcink_abilities_toolkit_assert_same( 'core/group', $block_theme_actions[0]['input']['blocks'][1]['blockName'] ?? '', 'block theme site plan keeps main content as the second top-level block' );
	npcink_abilities_toolkit_assert_same( 'main', $block_theme_actions[0]['input']['blocks'][1]['attrs']['tagName'] ?? '', 'block theme site plan targets the main content container' );
	npcink_abilities_toolkit_assert_same( 'openclaw-breadcrumbs', $block_theme_actions[0]['input']['blocks'][1]['innerBlocks'][0]['attrs']['className'] ?? '', 'block theme site plan inserts breadcrumbs inside main before the title' );
	npcink_abilities_toolkit_assert_same( 'core/post-title', $block_theme_actions[0]['input']['blocks'][1]['innerBlocks'][1]['blockName'] ?? '', 'block theme site plan keeps the post title after breadcrumbs' );
	npcink_abilities_toolkit_assert_same( 'before_post_title_in_main', $block_theme_plan['data']['preview'][0]['breadcrumb_placement']['strategy'] ?? '', 'block theme site plan reports semantic breadcrumb placement' );
	npcink_abilities_toolkit_assert_same( 'gutenberg_native_v1', $block_theme_plan['data']['composition_contract']['catalog_id'] ?? '', 'build-block-theme-site-plan references the Gutenberg block capability catalog' );
	npcink_abilities_toolkit_assert_same( 'template', $block_theme_plan['data']['composition_contract']['surface'] ?? '', 'build-block-theme-site-plan scopes the block composition contract to templates' );
	npcink_abilities_toolkit_assert_same( 'pass', $block_theme_plan['data']['composition_contract']['contract_status'] ?? '', 'build-block-theme-site-plan passes the block composition contract' );
	npcink_abilities_toolkit_assert_same( 'bounded_template_anchor_placement', $block_theme_plan['data']['template_placement_contract']['placement_model'] ?? '', 'build-block-theme-site-plan uses bounded template placement standards' );
	npcink_abilities_toolkit_assert_same( 'pass', $block_theme_plan['data']['template_placement_contract']['contract_status'] ?? '', 'build-block-theme-site-plan passes the template placement contract' );
	npcink_abilities_toolkit_assert_same( 'core/post-title', $block_theme_plan['data']['template_placement_contract']['placements'][0]['inserted_before'] ?? '', 'build-block-theme-site-plan records the approved title anchor in the placement contract' );
	npcink_abilities_toolkit_assert_same( true, $block_theme_plan['data']['preview'][0]['block_editor_quality_gate']['ready_for_proposal'] ?? null, 'block theme site plan preview carries the per-template quality gate' );
	$template_layout_plan = $core_read_package->build_block_theme_site_plan(
		array(
			'intent'           => 'customize_template_layout',
			'target_templates' => array( 'single' ),
			'layout_profile'   => 'article_standard',
		)
	);
	$template_layout_actions = is_array( $template_layout_plan['data']['write_actions'] ?? null ) ? $template_layout_plan['data']['write_actions'] : array();
	$template_layout_blocks_json = wp_json_encode( $template_layout_actions[0]['input']['blocks'] ?? array() );
	$template_layout_blocks_json = is_string( $template_layout_blocks_json ) ? $template_layout_blocks_json : '';
	npcink_abilities_toolkit_assert_same( true, $template_layout_plan['success'] ?? null, 'build-block-theme-site-plan accepts bounded template layout intent' );
	npcink_abilities_toolkit_assert_same( 'customize_template_layout', $template_layout_plan['data']['intent'] ?? '', 'template layout plan reports its intent' );
	npcink_abilities_toolkit_assert_same( 'block_theme_template_layout_plan', $template_layout_plan['data']['composition_role'] ?? '', 'template layout plan declares a layout composition role' );
	npcink_abilities_toolkit_assert_same( 1, count( $template_layout_actions ), 'template layout plan emits one action for the requested template' );
	npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/update-template-blocks', $template_layout_actions[0]['target_ability_id'] ?? '', 'template layout plan targets template block writes' );
	npcink_abilities_toolkit_assert_same( 'core/template-part', $template_layout_actions[0]['input']['blocks'][0]['blockName'] ?? '', 'template layout plan keeps the header template part first' );
	npcink_abilities_toolkit_assert_same( 'main', $template_layout_actions[0]['input']['blocks'][1]['attrs']['tagName'] ?? '', 'template layout plan places the bounded profile inside main' );
	npcink_abilities_toolkit_assert_true( false !== strpos( $template_layout_blocks_json, 'post-title' ), 'template layout plan includes the post title block' );
	npcink_abilities_toolkit_assert_true( false !== strpos( $template_layout_blocks_json, 'post-author-name' ), 'template layout plan includes the author block' );
	npcink_abilities_toolkit_assert_true( false !== strpos( $template_layout_blocks_json, 'post-date' ), 'template layout plan includes the post date block' );
	npcink_abilities_toolkit_assert_true( false !== strpos( $template_layout_blocks_json, 'post-terms' ), 'template layout plan includes taxonomy term blocks' );
	npcink_abilities_toolkit_assert_true( false !== strpos( $template_layout_blocks_json, 'post-featured-image' ), 'template layout plan includes the featured image block' );
	npcink_abilities_toolkit_assert_true( false !== strpos( $template_layout_blocks_json, 'post-content' ), 'template layout plan includes the post content slot' );
	npcink_abilities_toolkit_assert_true( false !== strpos( $template_layout_blocks_json, 'post-navigation-link' ), 'template layout plan includes previous/next post navigation' );
	npcink_abilities_toolkit_assert_true( false !== strpos( $template_layout_blocks_json, 'core\\/comments' ), 'template layout plan includes the comments block' );
	npcink_abilities_toolkit_assert_true( false !== strpos( $template_layout_blocks_json, 'latest-posts' ), 'template layout plan includes related/latest posts' );
	npcink_abilities_toolkit_assert_true( false !== strpos( $template_layout_blocks_json, '#FBFAF3' ), 'template layout plan gives article title and navigation native background styles' );
	npcink_abilities_toolkit_assert_true( false !== strpos( $template_layout_blocks_json, '#F1F5F9' ), 'template layout plan gives related posts a distinct native background style' );
	npcink_abilities_toolkit_assert_true( false !== strpos( $template_layout_blocks_json, 'var(--wp--preset--spacing--50)' ), 'template layout plan serializes preset spacing tokens as valid CSS variables in static markup' );
	npcink_abilities_toolkit_assert_true( false === strpos( $template_layout_blocks_json, 'core\\/html' ), 'template layout plan does not emit raw HTML blocks' );
	npcink_abilities_toolkit_assert_same( 'pass', $template_layout_plan['data']['composition_contract']['contract_status'] ?? '', 'template layout plan passes the block composition contract' );
	npcink_abilities_toolkit_assert_same( 'bounded_template_layout_profile', $template_layout_plan['data']['template_layout_contract']['placement_model'] ?? '', 'template layout plan reports bounded layout profile contract' );
	npcink_abilities_toolkit_assert_same( 'block_theme_profile_compiler@0.3', $template_layout_plan['data']['template_layout_contract']['compiler_version'] ?? '', 'template layout plan reports the bounded profile compiler version' );
	npcink_abilities_toolkit_assert_same( 'block_theme_safe_core_blocks@0.2', $template_layout_plan['data']['template_layout_contract']['forbidden_policy_version'] ?? '', 'template layout plan reports the safe core block policy version' );
	npcink_abilities_toolkit_assert_true( in_array( 'article_standard@0.4', $template_layout_plan['data']['template_layout_contract']['accepted_profile_versions'] ?? array(), true ), 'template layout contract accepts article_standard@0.4' );
	npcink_abilities_toolkit_assert_true( in_array( 'page_standard@0.2', $template_layout_plan['data']['template_layout_contract']['accepted_profile_versions'] ?? array(), true ), 'template layout contract accepts page_standard@0.2' );
	npcink_abilities_toolkit_assert_true( in_array( 'homepage_landing@0.3', $template_layout_plan['data']['template_layout_contract']['accepted_profile_versions'] ?? array(), true ), 'template layout contract accepts homepage_landing@0.3' );
	npcink_abilities_toolkit_assert_same( 'pass', $template_layout_plan['data']['template_layout_contract']['contract_status'] ?? '', 'template layout plan passes the layout profile contract' );
	npcink_abilities_toolkit_assert_same( 'article_standard@0.4', $template_layout_plan['data']['template_layout_contract']['profiles'][0]['profile_version'] ?? '', 'template layout profile row records article_standard@0.4' );
	npcink_abilities_toolkit_assert_same( 'replace_template_layout_with_preserved_template_parts', $template_layout_plan['data']['template_layout_contract']['profiles'][0]['operation'] ?? '', 'template layout profile row declares the accepted Core intake operation' );
	npcink_abilities_toolkit_assert_true( in_array( 'post_navigation', $template_layout_plan['data']['template_layout_contract']['profiles'][0]['modules'] ?? array(), true ), 'template layout profile row declares post navigation as a module' );
	npcink_abilities_toolkit_assert_true( in_array( 'comments', $template_layout_plan['data']['template_layout_contract']['profiles'][0]['modules'] ?? array(), true ), 'template layout profile row declares comments as a module' );
	npcink_abilities_toolkit_assert_true( in_array( 'core/post-navigation-link', $template_layout_plan['data']['template_layout_contract']['profiles'][0]['allowed_blocks'] ?? array(), true ), 'template layout profile row allows post navigation blocks' );
	npcink_abilities_toolkit_assert_true( in_array( 'core/comments', $template_layout_plan['data']['template_layout_contract']['profiles'][0]['allowed_blocks'] ?? array(), true ), 'template layout profile row allows comments blocks' );
	npcink_abilities_toolkit_assert_true( in_array( 'theme_json', $template_layout_plan['data']['template_layout_contract']['profiles'][0]['forbidden_outputs'] ?? array(), true ), 'template layout profile row preserves forbidden output policy' );
	npcink_abilities_toolkit_assert_same( 'article_standard', $template_layout_plan['data']['preview'][0]['layout_profile'] ?? '', 'template layout plan preview records the article profile' );
	$template_layout_finding_codes = $template_layout_plan['data']['preview'][0]['block_editor_quality_gate']['finding_codes'] ?? array();
	npcink_abilities_toolkit_assert_true( ! in_array( 'hero_media_missing', $template_layout_finding_codes, true ), 'article template quality gate omits landing-only hero media finding' );
	npcink_abilities_toolkit_assert_true( ! in_array( 'bento_grid_missing', $template_layout_finding_codes, true ), 'article template quality gate omits landing-only bento finding' );
	npcink_abilities_toolkit_assert_true( ! in_array( 'faq_missing', $template_layout_finding_codes, true ), 'article template quality gate omits landing-only FAQ finding' );
	npcink_abilities_toolkit_assert_true( ! in_array( 'final_cta_missing', $template_layout_finding_codes, true ), 'article template quality gate omits landing-only final CTA finding' );
	npcink_abilities_toolkit_assert_same( true, $template_layout_plan['data']['preview'][0]['block_editor_quality_gate']['ready_for_proposal'] ?? null, 'template layout plan preview carries a passing per-template quality gate' );
	$page_layout_plan = $core_read_package->build_block_theme_site_plan(
		array(
			'intent'           => 'customize_template_layout',
			'target_templates' => array( 'page' ),
			'layout_profile'   => 'page_standard',
		)
	);
	$page_layout_actions = is_array( $page_layout_plan['data']['write_actions'] ?? null ) ? $page_layout_plan['data']['write_actions'] : array();
	$page_layout_blocks_json = wp_json_encode( $page_layout_actions[0]['input']['blocks'] ?? array() );
	$page_layout_blocks_json = is_string( $page_layout_blocks_json ) ? $page_layout_blocks_json : '';
	npcink_abilities_toolkit_assert_same( true, $page_layout_plan['success'] ?? null, 'page standard template layout plan returns a success envelope' );
	npcink_abilities_toolkit_assert_same( 'page_standard@0.2', $page_layout_plan['data']['template_layout_contract']['profiles'][0]['profile_version'] ?? '', 'page standard profile row records page_standard@0.2' );
	npcink_abilities_toolkit_assert_true( false !== strpos( $page_layout_blocks_json, 'openclaw-template-page-title-band' ), 'page standard layout includes a title band class for visual review' );
	npcink_abilities_toolkit_assert_true( false !== strpos( $page_layout_blocks_json, 'openclaw-template-media-band' ), 'page standard layout includes a media band class for visual review' );
	npcink_abilities_toolkit_assert_true( false !== strpos( $page_layout_blocks_json, 'openclaw-template-page-content-panel' ), 'page standard layout includes a content panel class for visual review' );
	npcink_abilities_toolkit_assert_true( false !== strpos( $page_layout_blocks_json, '#F7F8EF' ), 'page standard layout gives the title band a native background style' );
	npcink_abilities_toolkit_assert_true( false !== strpos( $page_layout_blocks_json, '#111827' ), 'page standard layout gives the media band a contrast background style' );
	$homepage_unresolved_cta_plan = $core_read_package->build_block_theme_site_plan(
		array(
			'intent'           => 'customize_template_layout',
			'target_templates' => array( 'front-page' ),
			'layout_profile'   => 'homepage_landing',
			'include_cta'      => true,
		)
	);
	npcink_abilities_toolkit_assert_same( true, $homepage_unresolved_cta_plan['success'] ?? null, 'homepage layout plan still returns a reviewable envelope when CTA cannot be resolved' );
	npcink_abilities_toolkit_assert_same( 0, count( $homepage_unresolved_cta_plan['data']['write_actions'] ?? array() ), 'homepage layout plan emits no write action when CTA link is unresolved' );
	npcink_abilities_toolkit_assert_same( 'cta_link_unresolved', $homepage_unresolved_cta_plan['data']['warnings'][0]['reason'] ?? '', 'homepage layout plan reports unresolved CTA links' );
	npcink_abilities_toolkit_assert_same( 'cta_link_unresolved', $homepage_unresolved_cta_plan['data']['preview'][0]['no_change_reason'] ?? '', 'homepage layout preview explains unresolved CTA blocking' );
	npcink_abilities_toolkit_assert_same( false, $homepage_unresolved_cta_plan['data']['preview'][0]['block_editor_quality_gate']['ready_for_proposal'] ?? true, 'homepage layout preview is not proposal-ready without a CTA URL' );
	npcink_abilities_toolkit_assert_same( 'resolve_cta_link_before_proposal', $homepage_unresolved_cta_plan['data']['preview'][0]['block_editor_quality_gate']['recommended_next_step'] ?? '', 'homepage layout preview asks for CTA resolution before proposal' );
	$homepage_layout_fixture_posts  = $GLOBALS['npcink_abilities_toolkit_unit_style_posts'];
	$homepage_layout_fixture_options = $GLOBALS['npcink_abilities_toolkit_unit_options'] ?? array();
	$GLOBALS['npcink_abilities_toolkit_unit_options'] = array(
		'show_on_front'  => 'posts',
		'page_for_posts' => 702,
	);
	$GLOBALS['npcink_abilities_toolkit_unit_style_posts'] = array(
		701 => (object) array(
			'ID' => 701,
			'post_type' => 'wp_template',
			'post_status' => 'publish',
			'post_title' => 'Front Page',
			'post_content' => '<!-- wp:template-part {"slug":"header"} /--><!-- wp:group {"tagName":"main"} --><main class="wp-block-group"><!-- wp:post-content /--></main><!-- /wp:group --><!-- wp:template-part {"slug":"footer"} /-->',
			'post_excerpt' => '',
			'post_author' => 7,
			'post_name' => 'front-page',
			'post_parent' => 0,
		),
		702 => (object) array(
			'ID' => 702,
			'post_type' => 'page',
			'post_status' => 'publish',
			'post_title' => 'Blog',
			'post_content' => 'Blog page.',
			'post_excerpt' => '',
			'post_author' => 7,
			'post_name' => 'blog',
			'post_parent' => 0,
		),
		703 => (object) array(
			'ID' => 703,
			'post_type' => 'post',
			'post_status' => 'publish',
			'post_title' => 'Published Post',
			'post_content' => 'Published post.',
			'post_excerpt' => '',
			'post_author' => 7,
			'post_name' => 'published-post',
			'post_parent' => 0,
		),
	);
	$homepage_posts_plan = $core_read_package->build_block_theme_site_plan(
		array(
			'intent'           => 'customize_template_layout',
			'target_templates' => array( 'front-page' ),
			'layout_profile'   => 'homepage_landing',
		)
	);
	$homepage_posts_actions = is_array( $homepage_posts_plan['data']['write_actions'] ?? null ) ? $homepage_posts_plan['data']['write_actions'] : array();
	$homepage_posts_blocks_json = wp_json_encode( $homepage_posts_actions[0]['input']['blocks'] ?? array() );
	$homepage_posts_blocks_json = is_string( $homepage_posts_blocks_json ) ? $homepage_posts_blocks_json : '';
	npcink_abilities_toolkit_assert_same( 1, count( $homepage_posts_actions ), 'homepage posts-front layout emits one write action when CTA resolves to the posts page' );
	npcink_abilities_toolkit_assert_same( 'resolved', $homepage_posts_plan['data']['preview'][0]['cta_resolution']['status'] ?? '', 'homepage posts-front layout resolves CTA from site context' );
	npcink_abilities_toolkit_assert_same( 'posts_page', $homepage_posts_plan['data']['preview'][0]['cta_resolution']['source'] ?? '', 'homepage posts-front layout prefers the configured posts page for CTA' );
	npcink_abilities_toolkit_assert_same( false, $homepage_posts_plan['data']['preview'][0]['page_content_enabled'] ?? true, 'homepage posts-front layout does not include static page content' );
	npcink_abilities_toolkit_assert_true( ! in_array( 'post_content', $homepage_posts_plan['data']['preview'][0]['layout_sections'] ?? array(), true ), 'homepage posts-front layout sections omit post_content' );
	npcink_abilities_toolkit_assert_true( false !== strpos( $homepage_posts_blocks_json, 'blog' ), 'homepage posts-front layout points CTA at the resolved blog page' );
	npcink_abilities_toolkit_assert_true( false === strpos( $homepage_posts_blocks_json, 'core\\/post-content' ), 'homepage posts-front layout omits core/post-content' );
	$GLOBALS['npcink_abilities_toolkit_unit_options'] = array(
		'show_on_front' => 'page',
		'page_on_front' => 704,
	);
	$GLOBALS['npcink_abilities_toolkit_unit_style_posts'] = array(
		701 => $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][701],
		704 => (object) array(
			'ID' => 704,
			'post_type' => 'page',
			'post_status' => 'publish',
			'post_title' => 'Front Page Fixture',
			'post_content' => 'Static front page.',
			'post_excerpt' => '',
			'post_author' => 7,
			'post_name' => 'front-page-fixture',
			'post_parent' => 0,
		),
		705 => (object) array(
			'ID' => 705,
			'post_type' => 'page',
			'post_status' => 'publish',
			'post_title' => 'Contact',
			'post_content' => 'Contact page.',
			'post_excerpt' => '',
			'post_author' => 7,
			'post_name' => 'contact',
			'post_parent' => 0,
		),
	);
	$homepage_static_plan = $core_read_package->build_block_theme_site_plan(
		array(
			'intent'           => 'customize_template_layout',
			'target_templates' => array( 'front-page' ),
			'layout_profile'   => 'homepage_landing',
		)
	);
	$homepage_static_actions = is_array( $homepage_static_plan['data']['write_actions'] ?? null ) ? $homepage_static_plan['data']['write_actions'] : array();
	$homepage_static_blocks_json = wp_json_encode( $homepage_static_actions[0]['input']['blocks'] ?? array() );
	$homepage_static_blocks_json = is_string( $homepage_static_blocks_json ) ? $homepage_static_blocks_json : '';
	npcink_abilities_toolkit_assert_same( 1, count( $homepage_static_actions ), 'homepage static-front layout emits one write action when CTA resolves to a candidate page' );
	npcink_abilities_toolkit_assert_same( true, $homepage_static_plan['data']['preview'][0]['page_content_enabled'] ?? false, 'homepage static-front layout includes static page content' );
	npcink_abilities_toolkit_assert_true( in_array( 'post_content', $homepage_static_plan['data']['preview'][0]['layout_sections'] ?? array(), true ), 'homepage static-front layout sections include post_content' );
	npcink_abilities_toolkit_assert_same( 'slug_match_contact', $homepage_static_plan['data']['preview'][0]['cta_resolution']['source'] ?? '', 'homepage static-front layout resolves CTA to the contact page' );
	npcink_abilities_toolkit_assert_true( false !== strpos( $homepage_static_blocks_json, 'core\\/post-content' ), 'homepage static-front layout includes core/post-content' );
	npcink_abilities_toolkit_assert_true( false !== strpos( $homepage_static_blocks_json, 'contact' ), 'homepage static-front layout points CTA at the contact page' );
	npcink_abilities_toolkit_assert_same( 'homepage_landing@0.3', $homepage_static_plan['data']['template_layout_contract']['profiles'][0]['profile_version'] ?? '', 'homepage landing profile row records homepage_landing@0.3' );
	npcink_abilities_toolkit_assert_true( false !== strpos( $homepage_static_blocks_json, 'openclaw-home-hero' ), 'homepage landing layout preserves hero section class for visual review' );
	npcink_abilities_toolkit_assert_true( false !== strpos( $homepage_static_blocks_json, 'openclaw-home-latest-posts' ), 'homepage landing layout preserves latest posts section class for visual review' );
	npcink_abilities_toolkit_assert_true( false !== strpos( $homepage_static_blocks_json, 'openclaw-home-categories' ), 'homepage landing layout preserves categories section class for visual review' );
	npcink_abilities_toolkit_assert_true( false !== strpos( $homepage_static_blocks_json, '#0F172A' ), 'homepage landing layout gives the hero a contrast background style' );
	npcink_abilities_toolkit_assert_true( false !== strpos( $homepage_static_blocks_json, '#EEF6F1' ), 'homepage landing layout gives category links a distinct native background style' );
	npcink_abilities_toolkit_assert_true( false !== strpos( $homepage_static_blocks_json, '#FFF7ED' ), 'homepage landing layout gives page content a distinct native background style' );
	$GLOBALS['npcink_abilities_toolkit_unit_style_posts'] = $homepage_layout_fixture_posts;
	$GLOBALS['npcink_abilities_toolkit_unit_options']     = $homepage_layout_fixture_options;
	$valid_page_template_posts_fixture = $GLOBALS['npcink_abilities_toolkit_unit_style_posts'];
	$GLOBALS['npcink_abilities_toolkit_unit_style_posts'] = array(
		608 => (object) array(
			'ID' => 608,
			'post_type' => 'wp_template',
			'post_status' => 'publish',
			'post_title' => 'Page',
			'post_content' => '<!-- wp:template-part {"slug":"header"} /--><!-- wp:group {"tagName":"main"} --><main class="wp-block-group"><!-- wp:group --><div class="wp-block-group"><!-- wp:paragraph /--><!-- wp:group {"className":"openclaw-breadcrumbs"} --><div class="wp-block-group openclaw-breadcrumbs"></div><!-- /wp:group --><!-- wp:post-title /--><!-- wp:post-content /--></div><!-- /wp:group --></main><!-- /wp:group --><!-- wp:template-part {"slug":"footer"} /-->',
			'post_excerpt' => '',
			'post_author' => 7,
			'post_name' => 'page',
			'post_parent' => 0,
		),
	);
	$valid_page_template_plan = $core_read_package->build_block_theme_site_plan(
		array(
			'intent' => 'add_breadcrumbs',
			'target_templates' => array( 'page' ),
		)
	);
	$GLOBALS['npcink_abilities_toolkit_unit_style_posts'] = $valid_page_template_posts_fixture;
	npcink_abilities_toolkit_assert_same( true, $valid_page_template_plan['success'] ?? null, 'build-block-theme-site-plan accepts already-valid nested page breadcrumb placement' );
	npcink_abilities_toolkit_assert_same( 0, count( $valid_page_template_plan['data']['write_actions'] ?? array() ), 'block theme site plan emits no action when page breadcrumbs are already before the title in main' );
	npcink_abilities_toolkit_assert_same( false, $valid_page_template_plan['data']['preview'][0]['requires_write'] ?? true, 'block theme site plan marks already-valid page templates as no-write previews' );
	npcink_abilities_toolkit_assert_same( 'already_valid', $valid_page_template_plan['data']['preview'][0]['breadcrumb_placement']['status'] ?? '', 'block theme site plan reports already-valid page breadcrumb placement' );
	npcink_abilities_toolkit_assert_same( 'breadcrumbs_already_before_post_title', $valid_page_template_plan['data']['preview'][0]['no_change_reason'] ?? '', 'block theme site plan explains no-write page plans' );
	npcink_abilities_toolkit_assert_same( 'no_changes_required', $valid_page_template_plan['data']['block_editor_quality_gate']['recommended_next_step'] ?? '', 'block theme site plan batch gate reports no changes when every target is already valid' );
	$GLOBALS['npcink_abilities_toolkit_unit_style_posts'] = array(
		608 => (object) array(
			'ID' => 608,
			'post_type' => 'wp_template',
			'post_status' => 'publish',
			'post_title' => 'Page',
			'post_content' => '<!-- wp:template-part {"slug":"header"} /--><!-- wp:group {"tagName":"main"} --><main class="wp-block-group"><!-- wp:group --><div class="wp-block-group"><!-- wp:paragraph /--><!-- wp:group {"className":"openclaw-breadcrumbs"} --><div class="wp-block-group openclaw-breadcrumbs"></div><!-- /wp:group --><!-- wp:post-title /--><!-- wp:post-content /--></div><!-- /wp:group --></main><!-- /wp:group --><!-- wp:template-part {"slug":"footer"} /-->',
			'post_excerpt' => '',
			'post_author' => 7,
			'post_name' => 'page',
			'post_parent' => 0,
		),
	);
	$valid_page_surface_inspection = $core_read_package->inspect_block_theme_surface(
		array(
			'intent' => 'add_breadcrumbs',
			'target_templates' => array( 'page' ),
		)
	);
	$GLOBALS['npcink_abilities_toolkit_unit_style_posts'] = array(
		609 => (object) array(
			'ID' => 609,
			'post_type' => 'wp_template',
			'post_status' => 'publish',
			'post_title' => 'Page',
			'post_content' => '<!-- wp:group {"className":"openclaw-breadcrumbs"} --><div class="wp-block-group openclaw-breadcrumbs"></div><!-- /wp:group --><!-- wp:template-part {"slug":"header"} /--><!-- wp:group {"tagName":"main"} --><main class="wp-block-group"><!-- wp:post-title /--><!-- wp:post-content /--></main><!-- /wp:group -->',
			'post_excerpt' => '',
			'post_author' => 7,
			'post_name' => 'page',
			'post_parent' => 0,
		),
	);
	$misplaced_page_surface_inspection = $core_read_package->inspect_block_theme_surface(
		array(
			'intent' => 'add_breadcrumbs',
			'target_templates' => array( 'page' ),
		)
	);
	$GLOBALS['npcink_abilities_toolkit_unit_style_posts'] = $valid_page_template_posts_fixture;
	npcink_abilities_toolkit_assert_same( true, $valid_page_surface_inspection['success'] ?? null, 'inspect-block-theme-surface returns a success envelope for valid templates' );
	npcink_abilities_toolkit_assert_same( 'block_theme_surface_inspection', $valid_page_surface_inspection['data']['artifact_type'] ?? '', 'block theme surface inspection declares its artifact type' );
	npcink_abilities_toolkit_assert_same( 2, $valid_page_surface_inspection['data']['review_contract']['reviewer_count'] ?? 0, 'block theme surface inspection exposes a two-reviewer contract' );
	npcink_abilities_toolkit_assert_same( array(), $valid_page_surface_inspection['data']['templates'][0]['issue_codes'] ?? array( 'unexpected' ), 'block theme surface inspection reports no issues for valid breadcrumb placement' );
	npcink_abilities_toolkit_assert_same( 'no_changes_required', $valid_page_surface_inspection['data']['dual_review']['consensus']['recommended_next_step'] ?? '', 'block theme surface inspection consensus reports no changes for valid templates' );
	npcink_abilities_toolkit_assert_same( array(), $valid_page_surface_inspection['data']['recommended_plan_input'] ?? array( 'unexpected' ), 'block theme surface inspection does not recommend a plan for valid templates' );
	npcink_abilities_toolkit_assert_same( true, $misplaced_page_surface_inspection['success'] ?? null, 'inspect-block-theme-surface returns a success envelope for misplaced templates' );
	npcink_abilities_toolkit_assert_true( in_array( 'breadcrumb_above_header', $misplaced_page_surface_inspection['data']['templates'][0]['issue_codes'] ?? array(), true ), 'block theme surface inspection detects breadcrumbs above the header' );
	npcink_abilities_toolkit_assert_true( in_array( 'breadcrumb_not_before_title', $misplaced_page_surface_inspection['data']['templates'][0]['issue_codes'] ?? array(), true ), 'block theme surface inspection detects breadcrumbs not before the post title' );
	npcink_abilities_toolkit_assert_same( 'build_block_theme_site_plan', $misplaced_page_surface_inspection['data']['dual_review']['consensus']['recommended_next_step'] ?? '', 'block theme surface inspection consensus recommends a minimal plan for fixable issues' );
	npcink_abilities_toolkit_assert_same( array( 'page' ), $misplaced_page_surface_inspection['data']['recommended_plan_input']['target_templates'] ?? array(), 'block theme surface inspection recommends only affected templates for planning' );
	$GLOBALS['npcink_abilities_toolkit_unit_style_posts'] = array(
		610 => (object) array(
			'ID' => 610,
			'post_type' => 'wp_template',
			'post_status' => 'publish',
			'post_title' => 'Archive',
			'post_content' => '<!-- wp:template-part {"slug":"header"} /--><!-- wp:group {"tagName":"main"} --><main class="wp-block-group"><!-- wp:query /--></main><!-- /wp:group --><!-- wp:template-part {"slug":"footer"} /-->',
			'post_excerpt' => '',
			'post_author' => 7,
			'post_name' => 'archive',
			'post_parent' => 0,
		),
	);
	$archive_surface_inspection = $core_read_package->inspect_block_theme_surface(
		array(
			'intent' => 'add_breadcrumbs',
			'target_templates' => array( 'archive' ),
		)
	);
	$GLOBALS['npcink_abilities_toolkit_unit_style_posts'] = $valid_page_template_posts_fixture;
	npcink_abilities_toolkit_assert_same( true, $archive_surface_inspection['success'] ?? null, 'inspect-block-theme-surface can inspect archive templates' );
	npcink_abilities_toolkit_assert_true( in_array( 'breadcrumb_missing', $archive_surface_inspection['data']['templates'][0]['issue_codes'] ?? array(), true ), 'block theme surface inspection reports archive breadcrumb issues' );
	npcink_abilities_toolkit_assert_same( 'blocked', $archive_surface_inspection['data']['templates'][0]['status'] ?? '', 'block theme surface inspection blocks archive template planning handoff' );
	npcink_abilities_toolkit_assert_same( array(), $archive_surface_inspection['data']['templates'][0]['fixable_issue_codes'] ?? array( 'unexpected' ), 'block theme surface inspection does not mark archive issues as planner-fixable' );
	npcink_abilities_toolkit_assert_same( '', $archive_surface_inspection['data']['recommended_plan_ability_id'] ?? 'unexpected', 'block theme surface inspection does not recommend a plan ability for archive targets' );
	npcink_abilities_toolkit_assert_same( array(), $archive_surface_inspection['data']['recommended_plan_input'] ?? array( 'unexpected' ), 'block theme surface inspection does not recommend archive plan input' );
	npcink_abilities_toolkit_assert_same( 'template_plan_target_not_supported', $archive_surface_inspection['data']['warnings'][0]['reason'] ?? '', 'block theme surface inspection explains archive planning is unsupported' );
	$front_page_breadcrumb_plan = $core_read_package->build_block_theme_site_plan(
		array(
			'intent' => 'add_breadcrumbs',
			'target_templates' => array( 'front-page' ),
			'show_on_home_page' => false,
		)
	);
	$front_page_breadcrumb_actions = is_array( $front_page_breadcrumb_plan['data']['write_actions'] ?? null ) ? $front_page_breadcrumb_plan['data']['write_actions'] : array();
	npcink_abilities_toolkit_assert_same( true, $front_page_breadcrumb_plan['success'] ?? null, 'build-block-theme-site-plan accepts front-page templates' );
	npcink_abilities_toolkit_assert_same( 'removed', $front_page_breadcrumb_plan['data']['preview'][0]['breadcrumb_placement']['status'] ?? '', 'block theme site plan removes existing breadcrumbs from front-page when homepage breadcrumbs are disabled' );
	npcink_abilities_toolkit_assert_same( 'home_page_disabled', $front_page_breadcrumb_plan['data']['preview'][0]['breadcrumb_placement']['strategy'] ?? '', 'block theme site plan reports disabled homepage breadcrumb placement' );
	npcink_abilities_toolkit_assert_same( 'core/template-part', $front_page_breadcrumb_actions[0]['input']['blocks'][0]['blockName'] ?? '', 'block theme site plan removes misplaced front-page breadcrumbs before the header' );
	npcink_abilities_toolkit_assert_true( false === strpos( wp_json_encode( $front_page_breadcrumb_actions[0]['input']['blocks'] ?? array() ), 'openclaw-breadcrumbs' ), 'block theme site plan does not leave breadcrumbs in front-page blocks when disabled' );
	$home_template_style_posts_fixture = $GLOBALS['npcink_abilities_toolkit_unit_style_posts'];
	$home_template_options_fixture     = $GLOBALS['npcink_abilities_toolkit_unit_options'] ?? array();
	$GLOBALS['npcink_abilities_toolkit_unit_options']['show_on_front'] = 'page';
	$GLOBALS['npcink_abilities_toolkit_unit_options']['page_on_front'] = 700;
	$GLOBALS['npcink_abilities_toolkit_unit_style_posts'] = array(
		607 => (object) array(
			'ID' => 607,
			'post_type' => 'wp_template',
			'post_status' => 'publish',
			'post_title' => 'Page',
			'post_content' => '<!-- wp:group {"className":"openclaw-breadcrumbs"} --><div class="wp-block-group openclaw-breadcrumbs"><!-- wp:paragraph {"className":"openclaw-breadcrumbs__trail"} --><p class="openclaw-breadcrumbs__trail">Home / Current item</p><!-- /wp:paragraph --></div><!-- /wp:group --><!-- wp:template-part {"slug":"header"} /--><!-- wp:group {"tagName":"main"} --><main class="wp-block-group"><!-- wp:post-title /--><!-- wp:post-content /--></main><!-- /wp:group -->',
			'post_excerpt' => '',
			'post_author' => 7,
			'post_name' => 'page',
			'post_parent' => 0,
		),
		700 => (object) array(
			'ID' => 700,
			'post_type' => 'page',
			'post_status' => 'publish',
			'post_title' => 'Front Page Fixture',
			'post_content' => 'Static front page content.',
			'post_excerpt' => '',
			'post_author' => 7,
			'post_name' => 'front-page-fixture',
			'post_parent' => 0,
		),
	);
	$front_page_fallback_plan = $core_read_package->build_block_theme_site_plan(
		array(
			'intent' => 'add_breadcrumbs',
			'target_templates' => array( 'front-page' ),
			'show_on_home_page' => false,
		)
	);
	$front_page_fallback_actions = is_array( $front_page_fallback_plan['data']['write_actions'] ?? null ) ? $front_page_fallback_plan['data']['write_actions'] : array();
	npcink_abilities_toolkit_assert_same( true, $front_page_fallback_plan['success'] ?? null, 'build-block-theme-site-plan resolves a missing front-page template from the actual static homepage template stack' );
	npcink_abilities_toolkit_assert_same( 1, count( $front_page_fallback_actions ), 'block theme site plan emits one action for resolved front-page fallback templates' );
	npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/upsert-template-blocks', $front_page_fallback_actions[0]['target_ability_id'] ?? '', 'block theme site plan creates a front-page override when the concrete front-page template is missing' );
	npcink_abilities_toolkit_assert_same( 'front-page', $front_page_fallback_actions[0]['input']['slug'] ?? '', 'block theme site plan keeps the requested front-page target slug for fallback plans' );
	npcink_abilities_toolkit_assert_same( 'unit-block-theme//page', $front_page_fallback_actions[0]['input']['source_template_id'] ?? '', 'block theme site plan records the source page template id for front-page fallback plans' );
	npcink_abilities_toolkit_assert_same( 'front-page', $front_page_fallback_plan['data']['preview'][0]['template_resolution']['requested_slug'] ?? '', 'block theme site plan reports the requested homepage target slug' );
	npcink_abilities_toolkit_assert_same( 'page', $front_page_fallback_plan['data']['preview'][0]['template_resolution']['source_slug'] ?? '', 'block theme site plan reports the source template slug used for homepage fallback' );
	npcink_abilities_toolkit_assert_same( 'static_front_page_page_template_fallback', $front_page_fallback_plan['data']['preview'][0]['template_resolution']['strategy'] ?? '', 'block theme site plan reports the static homepage page-template fallback strategy' );
	npcink_abilities_toolkit_assert_same( true, $front_page_fallback_plan['data']['preview'][0]['template_resolution']['creates_template_override'] ?? null, 'block theme site plan reports that homepage fallback creates a template override' );
	npcink_abilities_toolkit_assert_same( 'removed', $front_page_fallback_plan['data']['preview'][0]['breadcrumb_placement']['status'] ?? '', 'block theme site plan removes inherited breadcrumbs from disabled homepage fallback plans' );
	npcink_abilities_toolkit_assert_true( false === strpos( wp_json_encode( $front_page_fallback_actions[0]['input']['blocks'] ?? array() ), 'openclaw-breadcrumbs' ), 'block theme site plan does not carry breadcrumbs into disabled homepage fallback blocks' );
	$GLOBALS['npcink_abilities_toolkit_unit_style_posts'] = array(
		607 => (object) array(
			'ID' => 607,
			'post_type' => 'wp_template',
			'post_status' => 'publish',
			'post_title' => 'Page',
			'post_content' => '<!-- wp:template-part {"slug":"header"} /--><!-- wp:group {"tagName":"main"} --><main class="wp-block-group"><!-- wp:post-title /--><!-- wp:post-content /--></main><!-- /wp:group -->',
			'post_excerpt' => '',
			'post_author' => 7,
			'post_name' => 'page',
			'post_parent' => 0,
		),
		700 => (object) array(
			'ID' => 700,
			'post_type' => 'page',
			'post_status' => 'publish',
			'post_title' => 'Front Page Fixture',
			'post_content' => 'Static front page content.',
			'post_excerpt' => '',
			'post_author' => 7,
			'post_name' => 'front-page-fixture',
			'post_parent' => 0,
		),
	);
	$front_page_fallback_noop_plan = $core_read_package->build_block_theme_site_plan(
		array(
			'intent' => 'add_breadcrumbs',
			'target_templates' => array( 'front-page' ),
			'show_on_home_page' => false,
		)
	);
	npcink_abilities_toolkit_assert_same( true, $front_page_fallback_noop_plan['success'] ?? null, 'build-block-theme-site-plan resolves no-op front-page fallback plans' );
	npcink_abilities_toolkit_assert_same( 0, count( $front_page_fallback_noop_plan['data']['write_actions'] ?? array() ), 'block theme site plan does not create a front-page override when homepage breadcrumbs are already absent' );
	npcink_abilities_toolkit_assert_same( false, $front_page_fallback_noop_plan['data']['preview'][0]['requires_write'] ?? true, 'block theme site plan marks absent homepage breadcrumbs as a no-write preview' );
	npcink_abilities_toolkit_assert_same( 'homepage_breadcrumbs_already_absent', $front_page_fallback_noop_plan['data']['preview'][0]['no_change_reason'] ?? '', 'block theme site plan explains no-write front-page fallback plans' );
	npcink_abilities_toolkit_assert_same( false, $front_page_fallback_noop_plan['data']['preview'][0]['creates_template_override'] ?? true, 'block theme site plan does not claim to create a front-page override for no-write fallback plans' );
	npcink_abilities_toolkit_assert_same( 'no_changes_required', $front_page_fallback_noop_plan['data']['block_editor_quality_gate']['recommended_next_step'] ?? '', 'block theme site plan batch gate reports no changes for no-op homepage fallback plans' );
	$GLOBALS['npcink_abilities_toolkit_unit_style_posts'] = array(
		700 => (object) array(
			'ID' => 700,
			'post_type' => 'page',
			'post_status' => 'publish',
			'post_title' => 'Front Page Fixture',
			'post_content' => 'Static front page content.',
			'post_excerpt' => '',
			'post_author' => 7,
			'post_name' => 'front-page-fixture',
			'post_parent' => 0,
		),
	);
	$front_page_unresolved_plan = $core_read_package->build_block_theme_site_plan(
		array(
			'intent' => 'add_breadcrumbs',
			'target_templates' => array( 'front-page' ),
			'show_on_home_page' => false,
		)
	);
	$GLOBALS['npcink_abilities_toolkit_unit_style_posts'] = $home_template_style_posts_fixture;
	$GLOBALS['npcink_abilities_toolkit_unit_options']     = $home_template_options_fixture;
	npcink_abilities_toolkit_assert_same( true, $front_page_unresolved_plan['success'] ?? null, 'build-block-theme-site-plan still returns a plan envelope when homepage template resolution fails' );
	npcink_abilities_toolkit_assert_same( 0, count( $front_page_unresolved_plan['data']['write_actions'] ?? array() ), 'block theme site plan emits no write actions when no homepage template fallback exists' );
	npcink_abilities_toolkit_assert_same( 'template_not_found', $front_page_unresolved_plan['data']['warnings'][0]['reason'] ?? '', 'block theme site plan reports template_not_found when homepage template resolution has no source' );
	npcink_abilities_toolkit_assert_same( 'home_template_unresolved', $front_page_unresolved_plan['data']['warnings'][0]['template_resolution']['strategy'] ?? '', 'block theme site plan exposes unresolved homepage template resolution metadata' );
	$block_theme_style_posts_fixture = $GLOBALS['npcink_abilities_toolkit_unit_style_posts'];
	$GLOBALS['npcink_abilities_toolkit_unit_style_posts'] = array(
		606 => (object) array(
			'ID' => 606,
			'post_type' => 'wp_template',
			'post_status' => 'publish',
			'post_title' => 'Single',
			'post_content' => '<!-- wp:group {"className":"openclaw-breadcrumbs"} --><div class="wp-block-group openclaw-breadcrumbs"><!-- wp:paragraph {"className":"openclaw-breadcrumbs__trail"} --><p class="openclaw-breadcrumbs__trail">Home / Current item</p><!-- /wp:paragraph --></div><!-- /wp:group --><!-- wp:template-part {"slug":"header"} /--><!-- wp:group {"tagName":"main"} --><main class="wp-block-group"><!-- wp:post-title /--><!-- wp:post-content /--></main><!-- /wp:group -->',
			'post_excerpt' => '',
			'post_author' => 7,
			'post_name' => 'single',
		),
	);
	$relocated_breadcrumb_plan = $core_read_package->build_block_theme_site_plan(
		array(
			'intent' => 'add_breadcrumbs',
			'target_templates' => array( 'single' ),
		)
	);
	$GLOBALS['npcink_abilities_toolkit_unit_style_posts'] = $block_theme_style_posts_fixture;
	$relocated_breadcrumb_actions = is_array( $relocated_breadcrumb_plan['data']['write_actions'] ?? null ) ? $relocated_breadcrumb_plan['data']['write_actions'] : array();
	npcink_abilities_toolkit_assert_same( true, $relocated_breadcrumb_plan['success'] ?? null, 'build-block-theme-site-plan accepts a template with misplaced breadcrumbs' );
	npcink_abilities_toolkit_assert_same( 'relocated', $relocated_breadcrumb_plan['data']['preview'][0]['breadcrumb_placement']['status'] ?? '', 'block theme site plan reports misplaced breadcrumbs as relocated' );
	npcink_abilities_toolkit_assert_same( 'before_post_title_in_main', $relocated_breadcrumb_plan['data']['preview'][0]['breadcrumb_placement']['strategy'] ?? '', 'block theme site plan relocates misplaced breadcrumbs before the title in main' );
	npcink_abilities_toolkit_assert_same( 'core/template-part', $relocated_breadcrumb_actions[0]['input']['blocks'][0]['blockName'] ?? '', 'block theme site plan removes misplaced top-level breadcrumbs before the header' );
	npcink_abilities_toolkit_assert_same( 'openclaw-breadcrumbs', $relocated_breadcrumb_actions[0]['input']['blocks'][1]['innerBlocks'][0]['attrs']['className'] ?? '', 'block theme site plan moves existing breadcrumbs inside main before the title' );
	npcink_abilities_toolkit_assert_same( 'core/post-title', $relocated_breadcrumb_actions[0]['input']['blocks'][1]['innerBlocks'][1]['blockName'] ?? '', 'block theme site plan preserves post title after relocated breadcrumbs' );
	$template_update_output_schema = $package_abilities['npcink-abilities-toolkit/update-template-blocks']['output_schema'] ?? array();
	$template_preview = $core_write_package->update_template_blocks(
		array(
			'post_id' => 601,
			'blocks'  => $block_theme_actions[0]['input']['blocks'] ?? array(),
			'dry_run' => true,
		)
	);
	npcink_abilities_toolkit_assert_same( true, $template_preview['dry_run'] ?? null, 'update-template-blocks returns a governed dry-run preview' );
	npcink_abilities_toolkit_assert_same( 'wp_template', $template_preview['post_type'] ?? '', 'update-template-blocks reports the template post type' );
	npcink_abilities_toolkit_assert_output_schema_declares_payload_keys( $block_document, $template_update_output_schema, $template_preview, 'update-template-blocks dry-run' );
	$GLOBALS['npcink_ai_runtime_wp_ability_context'] = array( 'context' => array( 'approval_commit_authorized' => true ) );
	$template_written = $core_write_package->update_template_blocks(
		array(
			'post_id'            => 601,
			'validate_roundtrip' => false,
			'blocks'             => $block_theme_actions[0]['input']['blocks'] ?? array(),
			'commit'             => true,
		)
	);
	$unsafe_media_upload = $core_write_package->upload_media_from_url(
		array(
			'url'       => 'https://cloud.example.test/audio/signed-runtime-url',
			'file_name' => 'unsafe.php',
			'commit'    => true,
		)
	);
	npcink_abilities_toolkit_assert_true( is_wp_error( $unsafe_media_upload ), 'upload-media-from-url rejects an unapproved media extension before it reaches uploads' );
	npcink_abilities_toolkit_assert_same( 'npcink_abilities_toolkit_media_type_blocked', $unsafe_media_upload->get_error_code() ?? '', 'upload-media-from-url reports the blocked media type' );
	npcink_abilities_toolkit_assert_true( ! file_exists( $article_audio_upload_dir . '/unsafe.php' ), 'upload-media-from-url never copies an invalid remote file into uploads' );
	unset( $GLOBALS['npcink_ai_runtime_wp_ability_context'] );
	npcink_abilities_toolkit_assert_same( false, $template_written['dry_run'] ?? null, 'update-template-blocks commits after host approval' );
	npcink_abilities_toolkit_assert_output_schema_declares_payload_keys( $block_document, $template_update_output_schema, $template_written, 'update-template-blocks commit' );
	$template_written_content = (string) ( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][601]->post_content ?? '' );
	$template_written_header_position = strpos( $template_written_content, 'wp:template-part {"slug":"header"}' );
	$template_written_breadcrumb_position = strpos( $template_written_content, 'openclaw-breadcrumbs' );
	$template_written_title_position = strpos( $template_written_content, 'wp:post-title' );
	npcink_abilities_toolkit_assert_true( false !== $template_written_breadcrumb_position, 'update-template-blocks writes breadcrumb scaffold markup' );
	npcink_abilities_toolkit_assert_true( false !== $template_written_header_position, 'update-template-blocks writes the header template part marker' );
	npcink_abilities_toolkit_assert_true( false !== $template_written_title_position, 'update-template-blocks writes the post title marker' );
	npcink_abilities_toolkit_assert_true( $template_written_breadcrumb_position > $template_written_header_position, 'update-template-blocks does not write breadcrumbs before the header template part' );
	npcink_abilities_toolkit_assert_true( $template_written_breadcrumb_position < $template_written_title_position, 'update-template-blocks writes breadcrumbs before the post title' );
	$template_part_preview = $core_write_package->update_template_part_blocks(
		array(
			'post_id' => 603,
			'blocks' => array(
				array(
					'blockName' => 'core/group',
					'innerHTML' => '<div class="wp-block-group"></div>',
				),
			),
			'dry_run' => true,
		)
	);
	npcink_abilities_toolkit_assert_same( true, $template_part_preview['dry_run'] ?? null, 'update-template-part-blocks returns a governed dry-run preview' );
	npcink_abilities_toolkit_assert_same( 'wp_template_part', $template_part_preview['post_type'] ?? '', 'update-template-part-blocks reports the template part post type' );
	$GLOBALS['npcink_abilities_toolkit_unit_style_posts'] = array(
		501 => $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][501],
	);
	$GLOBALS['npcink_abilities_toolkit_unit_file_templates'] = array(
		array(
			'type'    => 'wp_template',
			'id'      => 'unit-block-theme//single',
			'theme'   => 'unit-block-theme',
			'slug'    => 'single',
			'source'  => 'theme',
			'title'   => 'Single',
			'content' => "<!-- wp:group --><div class=\"wp-block-group\"><!-- wp:post-title /--><!-- wp:post-content /--></div><!-- /wp:group -->\n\n<!-- wp:template-part {\"slug\":\"footer\"} /-->\n",
		),
	);
	$file_template_context = $core_read_package->get_block_theme_context( array() );
	npcink_abilities_toolkit_assert_same( 1, count( $file_template_context['templates'] ?? array() ), 'get-block-theme-context lists file-backed block templates' );
	npcink_abilities_toolkit_assert_same( 'theme', $file_template_context['templates'][0]['source'] ?? '', 'get-block-theme-context reports file-backed template source' );
	npcink_abilities_toolkit_assert_same( 0, $file_template_context['templates'][0]['post_id'] ?? -1, 'file-backed templates do not pretend to have a post id' );
	$file_template_plan = $core_read_package->build_block_theme_site_plan(
		array(
			'intent'           => 'add_breadcrumbs',
			'target_templates' => array( 'single' ),
		)
	);
	$file_template_actions = is_array( $file_template_plan['data']['write_actions'] ?? null ) ? $file_template_plan['data']['write_actions'] : array();
	npcink_abilities_toolkit_assert_same( 1, count( $file_template_actions ), 'build-block-theme-site-plan emits an action for a file-backed template' );
	npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/upsert-template-blocks', $file_template_actions[0]['target_ability_id'] ?? '', 'file-backed block theme plan targets template override upsert' );
	npcink_abilities_toolkit_assert_same( 'single', $file_template_actions[0]['input']['slug'] ?? '', 'file-backed block theme plan carries template slug into upsert input' );
	foreach ( (array) ( $file_template_actions[0]['input']['blocks'] ?? array() ) as $file_template_action_block ) {
		npcink_abilities_toolkit_assert_true( is_array( $file_template_action_block ) && ! empty( $file_template_action_block['blockName'] ), 'file-backed block theme plan omits whitespace-only freeform spacer blocks' );
	}
	$upsert_preview = $core_write_package->upsert_template_blocks(
		array(
			'slug'   => 'single',
			'theme'  => 'unit-block-theme',
			'title'  => 'Single',
			'blocks' => $file_template_actions[0]['input']['blocks'] ?? array(),
			'dry_run' => true,
		)
	);
	npcink_abilities_toolkit_assert_same( true, $upsert_preview['dry_run'] ?? null, 'upsert-template-blocks returns a governed dry-run preview' );
	npcink_abilities_toolkit_assert_same( true, $upsert_preview['created'] ?? null, 'upsert-template-blocks previews template override creation when no post exists' );
	$GLOBALS['npcink_ai_runtime_wp_ability_context'] = array( 'context' => array( 'approval_commit_authorized' => true ) );
	$upsert_written = $core_write_package->upsert_template_blocks(
		array(
			'slug'               => 'single',
			'theme'              => 'unit-block-theme',
			'title'              => 'Single',
			'validate_roundtrip' => false,
			'blocks'             => $file_template_actions[0]['input']['blocks'] ?? array(),
			'commit'             => true,
		)
	);
	unset( $GLOBALS['npcink_ai_runtime_wp_ability_context'] );
	npcink_abilities_toolkit_assert_same( false, $upsert_written['dry_run'] ?? null, 'upsert-template-blocks commits after host approval' );
	npcink_abilities_toolkit_assert_same( true, $upsert_written['created'] ?? null, 'upsert-template-blocks creates a custom template override' );
	npcink_abilities_toolkit_assert_true( ! empty( $upsert_written['post_id'] ), 'upsert-template-blocks returns the created template post id' );
	npcink_abilities_toolkit_assert_same( 'single', (string) ( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][ $upsert_written['post_id'] ]->post_name ?? '' ), 'upsert-template-blocks stores the template slug as the post name' );
	npcink_abilities_toolkit_assert_true( false !== strpos( (string) ( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][ $upsert_written['post_id'] ]->post_content ?? '' ), 'openclaw-breadcrumbs' ), 'upsert-template-blocks writes breadcrumb scaffold markup' );
	unset( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'], $GLOBALS['npcink_abilities_toolkit_unit_post_meta'], $GLOBALS['npcink_abilities_toolkit_unit_is_block_theme'], $GLOBALS['npcink_abilities_toolkit_unit_active_theme'], $GLOBALS['npcink_abilities_toolkit_unit_file_templates'] );
$inspect_page_structure = $package_abilities['npcink-abilities-toolkit/inspect-page-structure'];
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit-pages', $inspect_page_structure['category'], 'inspect-page-structure uses page category' );
npcink_abilities_toolkit_assert_same( 1, $inspect_page_structure['input_schema']['properties']['max_pages']['minimum'] ?? null, 'inspect-page-structure max_pages minimum is 1' );
npcink_abilities_toolkit_assert_same( 100, $inspect_page_structure['input_schema']['properties']['max_pages']['maximum'] ?? null, 'inspect-page-structure max_pages maximum is 100' );
npcink_abilities_toolkit_assert_same( 50, $inspect_page_structure['input_schema']['properties']['max_pages']['default'] ?? null, 'inspect-page-structure max_pages default is 50' );
$proposal_excerpt = $package_abilities['npcink-abilities-toolkit/propose-post-excerpt'];
npcink_abilities_toolkit_assert_same( true, $proposal_excerpt['annotations']['readonly'], 'propose-post-excerpt remains proposal-only and readonly' );
npcink_abilities_toolkit_assert_same( false, $proposal_excerpt['requires_confirm'], 'propose-post-excerpt does not perform a final write' );
$GLOBALS['npcink_abilities_toolkit_unit_comments'] = array(
	11 => (object) array(
		'comment_ID' => 11,
		'comment_post_ID' => 77,
		'comment_author' => 'Promo Bot',
		'comment_approved' => 'hold',
		'comment_content' => 'Buy now discount coupon https://example.test https://promo.example.test',
	),
	12 => (object) array(
		'comment_ID' => 12,
		'comment_post_ID' => 77,
		'comment_author' => 'Reader',
		'comment_approved' => 'hold',
		'comment_content' => '@admin 请问这个报错怎么处理？',
	),
);
$comment_suggest = $core_comment_package->build_comment_moderation_suggest(
	array(
		'comment_id' => 11,
	)
);
npcink_abilities_toolkit_assert_same( true, $comment_suggest['success'] ?? null, 'build-comment-moderation-suggest returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'spam', $comment_suggest['data']['recommended_action'] ?? '', 'build-comment-moderation-suggest flags promotional comments as spam' );
npcink_abilities_toolkit_assert_true( in_array( 'commercial_promo', $comment_suggest['data']['risk_flags'] ?? array(), true ), 'build-comment-moderation-suggest exposes commercial promo risk flag' );
$GLOBALS['npcink_abilities_toolkit_unit_comments'][13] = (object) array(
	'comment_ID'      => 13,
	'comment_post_ID' => 77,
	'comment_author'  => 'Pharmacy Bot',
	'comment_approved' => 'hold',
	'comment_content' => 'Buy cheap pills now',
);
$pharmacy_comment_suggest = $core_comment_package->build_comment_moderation_suggest(
	array(
		'comment_id' => 13,
	)
);
npcink_abilities_toolkit_assert_same( 'spam', $pharmacy_comment_suggest['data']['recommended_action'] ?? '', 'build-comment-moderation-suggest flags pharmacy spam without relying on links' );
$comment_result = $core_comment_package->compose_comment_moderation_result(
	array(
		'comment_id' => 11,
		'mode' => 'suggest',
		'suggest_result' => $comment_suggest['data'],
	)
);
npcink_abilities_toolkit_assert_same( true, $comment_result['success'] ?? null, 'compose-comment-moderation-result returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'spam', $comment_result['data']['recommended_action'] ?? '', 'compose-comment-moderation-result keeps recommended action' );
$mention_suggest = $core_comment_package->build_comment_mention_reply_suggest(
	array(
		'comment_id' => 12,
		'trigger_type' => 'mention',
	)
);
npcink_abilities_toolkit_assert_same( true, $mention_suggest['success'] ?? null, 'build-comment-mention-reply-suggest returns a success envelope' );
npcink_abilities_toolkit_assert_same( true, $mention_suggest['data']['trigger']['trigger_detected'] ?? null, 'build-comment-mention-reply-suggest detects mention trigger' );
npcink_abilities_toolkit_assert_true( ! empty( $mention_suggest['data']['reply_options'] ), 'build-comment-mention-reply-suggest returns review-only reply options' );
npcink_abilities_toolkit_assert_same( 'acknowledge_and_answer', $mention_suggest['data']['reply_options'][0]['id'] ?? '', 'build-comment-mention-reply-suggest keeps stable reply option ids' );
npcink_abilities_toolkit_assert_true( '' !== (string) ( $mention_suggest['data']['reply_options'][0]['label'] ?? '' ), 'build-comment-mention-reply-suggest exposes reply option labels' );
npcink_abilities_toolkit_assert_true( '' !== (string) ( $mention_suggest['data']['reply_options'][0]['reason'] ?? '' ), 'build-comment-mention-reply-suggest exposes reply option reasons' );
$text_reply_suggest = $core_comment_package->build_comment_mention_reply_suggest(
	array(
		'post_id'        => 77,
		'post_title'    => 'Comment Reply Context',
		'comment_text'  => 'Could you share more detail about how this workflow handles review?',
		'comment_author' => 'Reader',
		'trigger_type'  => 'support_request',
		'always_suggest' => true,
	)
);
npcink_abilities_toolkit_assert_same( true, $text_reply_suggest['success'] ?? null, 'build-comment-mention-reply-suggest accepts operator-supplied comment text' );
npcink_abilities_toolkit_assert_same( 'operator_supplied_comment_text', $text_reply_suggest['data']['comment']['source'] ?? '', 'operator-supplied comment reply suggestions preserve source boundary' );
npcink_abilities_toolkit_assert_same( false, $text_reply_suggest['data']['direct_wordpress_write'] ?? true, 'operator-supplied comment reply suggestions remain no-write' );
npcink_abilities_toolkit_assert_same( 'preview_reply', $text_reply_suggest['data']['mention_summary']['next_action'] ?? '', 'operator-supplied comment reply suggestions summarize preview reply as the next action' );
$trigger_queue = $core_comment_package->read_comment_trigger_queue(
	array(
		'post_id' => 77,
		'trigger_type' => 'mention',
		'status' => 'hold',
	)
);
npcink_abilities_toolkit_assert_same( 1, $trigger_queue['data']['summary']['candidate_count'] ?? null, 'read-comment-trigger-queue returns detected mention candidates' );
$comment_queue_health = $core_comment_package->get_comment_queue_health(
	array(
		'post_id'  => 77,
		'status'   => 'hold',
		'per_page' => 10,
	)
);
npcink_abilities_toolkit_assert_same( true, $comment_queue_health['success'] ?? null, 'get-comment-queue-health returns a success envelope' );
npcink_abilities_toolkit_assert_same( 3, $comment_queue_health['data']['summary']['counts']['total'] ?? null, 'get-comment-queue-health counts queued comments' );
npcink_abilities_toolkit_assert_true( (int) ( $comment_queue_health['data']['summary']['counts']['spam_risk'] ?? 0 ) >= 1, 'get-comment-queue-health counts spam-risk comments' );
npcink_abilities_toolkit_assert_true( (int) ( $comment_queue_health['data']['summary']['counts']['reply_needed'] ?? 0 ) >= 1, 'get-comment-queue-health counts reply-needed comments' );
$comment_action_queue = $core_comment_package->get_comment_action_priority_queue(
	array(
		'post_id'  => 77,
		'status'   => 'hold',
		'per_page' => 10,
	)
);
npcink_abilities_toolkit_assert_same( true, $comment_action_queue['success'] ?? null, 'get-comment-action-priority-queue returns a success envelope' );
npcink_abilities_toolkit_assert_same( 3, $comment_action_queue['data']['summary']['counts']['total'] ?? null, 'get-comment-action-priority-queue counts queued comments' );
npcink_abilities_toolkit_assert_true( (int) ( $comment_action_queue['data']['items'][0]['priority_score'] ?? 0 ) >= (int) ( $comment_action_queue['data']['items'][1]['priority_score'] ?? 0 ), 'get-comment-action-priority-queue sorts high-priority items first' );
$comment_handoff = $core_comment_package->get_comment_compliance_handoff(
	array(
		'post_id'             => 77,
		'status'              => 'hold',
		'per_page'            => 10,
		'selected_comment_id' => 12,
	)
);
npcink_abilities_toolkit_assert_same( true, $comment_handoff['success'] ?? null, 'get-comment-compliance-handoff returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/recipes/comment-compliance-handoff', $comment_handoff['data']['recipe'] ?? '', 'get-comment-compliance-handoff declares its recipe id' );
npcink_abilities_toolkit_assert_true( in_array( 'selected_moderation_suggestion', $comment_handoff['data']['sections'] ?? array(), true ), 'get-comment-compliance-handoff includes selected moderation suggestion' );
$batch_suggest = $core_comment_package->build_comment_moderation_batch_suggest(
	array(
		'comment_ids' => array( 11, 12 ),
	)
);
npcink_abilities_toolkit_assert_same( true, $batch_suggest['success'] ?? null, 'build-comment-moderation-batch-suggest returns a success envelope' );
npcink_abilities_toolkit_assert_same( 2, $batch_suggest['data']['batch_summary']['counts']['total'] ?? null, 'build-comment-moderation-batch-suggest counts batch items' );
$batch_result = $core_comment_package->compose_comment_moderation_batch_result(
	array(
		'batch_result' => $batch_suggest['data'],
	)
);
npcink_abilities_toolkit_assert_same( 'review_individual_items', $batch_result['data']['next_action'] ?? '', 'compose-comment-moderation-batch-result keeps single-item handoff' );
$resolved_metadata_plan = $core_read_package->resolve_post_metadata_plan(
	array(
		'post_metadata_plan' => array(
			'excerpt_mode' => 'explicit',
			'excerpt' => '这是新的摘要。',
			'slug_mode' => 'explicit',
			'slug' => 'New Canonical Slug!',
			'categories' => array( '3', '5', '5', 'bad' ),
			'tags' => array(),
			'publish_at' => '2030-01-02 03:04:05',
			'author_id' => 12,
			'template' => 'landing.php',
			'format' => 'image',
		),
		'taxonomy_plan' => array(
			'categories' => array( '1' ),
			'tags' => array( '8', '13' ),
		),
		'generated_excerpt' => '备用摘要',
		'generated_slug' => 'fallback-slug',
	)
);
npcink_abilities_toolkit_assert_same( true, $resolved_metadata_plan['success'] ?? null, 'resolve-post-metadata-plan returns a success envelope' );
npcink_abilities_toolkit_assert_same( '这是新的摘要。', $resolved_metadata_plan['data']['excerpt'] ?? '', 'resolve-post-metadata-plan preserves explicit excerpt' );
npcink_abilities_toolkit_assert_same( 'new-canonical-slug', $resolved_metadata_plan['data']['slug'] ?? '', 'resolve-post-metadata-plan sanitizes explicit slug' );
npcink_abilities_toolkit_assert_same( array( 3, 5 ), $resolved_metadata_plan['data']['categories'] ?? array(), 'resolve-post-metadata-plan prefers metadata categories' );
npcink_abilities_toolkit_assert_same( array( 8, 13 ), $resolved_metadata_plan['data']['tags'] ?? array(), 'resolve-post-metadata-plan falls back to taxonomy tags' );
npcink_abilities_toolkit_assert_same( '2030-01-02 03:04:05', $resolved_metadata_plan['data']['publish_at'] ?? '', 'resolve-post-metadata-plan preserves publish_at handoff' );
npcink_abilities_toolkit_assert_same( 12, $resolved_metadata_plan['data']['author_id'] ?? 0, 'resolve-post-metadata-plan normalizes author_id handoff' );
npcink_abilities_toolkit_assert_same( 'landing.php', $resolved_metadata_plan['data']['template'] ?? '', 'resolve-post-metadata-plan preserves template handoff' );
npcink_abilities_toolkit_assert_same( 'image', $resolved_metadata_plan['data']['format'] ?? '', 'resolve-post-metadata-plan normalizes format handoff' );
$inline_blocks = $core_read_package->build_inline_image_blocks(
	array(
		'uploaded_inline_media' => array(
			array(
				'attachment_id' => 44,
				'url' => 'https://example.test/alpha.jpg',
				'alt' => 'Fallback alt',
			),
		),
		'inline_plan' => array(
			array(
				'alt' => 'Inline alt',
				'caption' => 'Inline caption',
				'placement_key' => 'alpha-hero',
			),
		),
	)
);
npcink_abilities_toolkit_assert_same( true, $inline_blocks['success'] ?? null, 'build-inline-image-blocks returns a success envelope' );
npcink_abilities_toolkit_assert_same( 1, $inline_blocks['data']['summary']['count'] ?? 0, 'build-inline-image-blocks counts generated blocks' );
npcink_abilities_toolkit_assert_same( 'core/image', $inline_blocks['data']['blocks'][0]['blockName'] ?? '', 'build-inline-image-blocks emits Gutenberg image blocks' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit-inline-image alpha-hero', $inline_blocks['data']['blocks'][0]['attrs']['className'] ?? '', 'build-inline-image-blocks preserves placement class key' );
