<?php
/**
 * Ordered regression suite part: 99-page-patterns-closeout.
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

$pattern_page_plan = $core_read_package->build_pattern_page_plan(
	array(
		'title'              => 'WordPress AI',
		'pattern_id'         => 'openai-style-landing',
		'style_preset'       => 'minimal-dark-light',
		'responsive_profile' => 'landing_standard',
		'visual_density'     => 'balanced',
		'media_strategy'     => 'existing_media_url',
		'section_variant_hints' => array(
			'comparison' => 'center-title-two-cards',
		),
		'variables'          => array(
			'eyebrow'          => 'WordPress AI Plugin',
			'hero_title'       => '把 AI 工作流带进 WordPress 内容现场',
			'hero_description' => '让内容生产、SEO 优化、媒体处理与发布协作在同一个可审计流程中完成。',
			'primary_cta'      => '查看工作流',
			'secondary_cta'    => '了解能力',
			'hero_media_url'   => 'https://magick-ai.local/wp-content/uploads/2026/06/wordpress-ai-dashboard.jpg',
			'hero_media_attachment_id' => 8053,
			'hero_media_alt'   => 'WordPress AI dashboard preview',
			'features'         => array(
				array(
					'title'       => 'AI 内容草稿',
					'description' => '从主题和上下文生成结构化草稿。',
				),
			),
		),
	)
);
npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['success'] ?? null, 'build-pattern-page-plan returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'pattern_page_plan', $pattern_page_plan['data']['artifact_type'] ?? '', 'build-pattern-page-plan declares a pattern page artifact type' );
npcink_abilities_toolkit_assert_same( 'openai-style-landing', $pattern_page_plan['data']['pattern_id'] ?? '', 'build-pattern-page-plan preserves the selected pattern id' );
npcink_abilities_toolkit_assert_same( 'minimal-dark-light', $pattern_page_plan['data']['style_preset'] ?? '', 'build-pattern-page-plan preserves the selected style preset' );
npcink_abilities_toolkit_assert_same( 'minimal-dark-light', $pattern_page_plan['data']['color_story'] ?? '', 'build-pattern-page-plan preserves the default color story' );
npcink_abilities_toolkit_assert_same( 'landing_standard', $pattern_page_plan['data']['responsive_profile'] ?? '', 'build-pattern-page-plan preserves the responsive profile' );
npcink_abilities_toolkit_assert_same( 'balanced', $pattern_page_plan['data']['visual_density'] ?? '', 'build-pattern-page-plan preserves visual density' );
npcink_abilities_toolkit_assert_same( 'existing_media_url', $pattern_page_plan['data']['media_strategy'] ?? '', 'build-pattern-page-plan preserves media strategy' );
npcink_abilities_toolkit_assert_same( 'post_content', $pattern_page_plan['data']['block_editor_surface']['surface_kind'] ?? '', 'build-pattern-page-plan declares a post content block-editor surface' );
npcink_abilities_toolkit_assert_same( 'block_editor', $pattern_page_plan['data']['block_editor_surface']['editor'] ?? '', 'build-pattern-page-plan declares the block editor surface owner' );
npcink_abilities_toolkit_assert_same( 'page', $pattern_page_plan['data']['block_editor_surface']['post_type'] ?? '', 'build-pattern-page-plan declares the page post type surface' );
npcink_abilities_toolkit_assert_same( 'create_draft', $pattern_page_plan['data']['block_editor_surface']['target_mode'] ?? '', 'build-pattern-page-plan reports create draft surface mode by default' );
npcink_abilities_toolkit_assert_same( 'center-title-two-cards', $pattern_page_plan['data']['section_variant_hints']['comparison'] ?? '', 'build-pattern-page-plan preserves bounded section variant hints' );
npcink_abilities_toolkit_assert_same( false, $pattern_page_plan['data']['direct_wordpress_write'] ?? null, 'build-pattern-page-plan does not directly write WordPress' );
npcink_abilities_toolkit_assert_same( false, $pattern_page_plan['data']['commit_execution'] ?? null, 'build-pattern-page-plan keeps commit execution disabled' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/build-pattern-page-plan', $pattern_page_plan['data']['handoff']['plan_ability_id'] ?? '', 'build-pattern-page-plan identifies itself for Core from-plan intake' );
npcink_abilities_toolkit_assert_same( 'openclaw_recipes.ai_image_ratio_crop_media_adoption', $pattern_page_plan['data']['handoff']['media_recipe_ref'] ?? '', 'build-pattern-page-plan points media handoff at the AI image crop adoption recipe' );
$pattern_media_slots = is_array( $pattern_page_plan['data']['media_slots'] ?? null ) ? $pattern_page_plan['data']['media_slots'] : array();
npcink_abilities_toolkit_assert_same( 'hero_media', $pattern_media_slots[0]['id'] ?? '', 'build-pattern-page-plan declares a hero media slot' );
npcink_abilities_toolkit_assert_same( 'hero_media_url', $pattern_media_slots[0]['variable'] ?? '', 'build-pattern-page-plan maps hero media slot to hero_media_url variable' );
npcink_abilities_toolkit_assert_same( '16:9', $pattern_media_slots[0]['target_aspect_ratio'] ?? '', 'build-pattern-page-plan declares hero media target aspect ratio' );
npcink_abilities_toolkit_assert_same( 'aspect_ratio', $pattern_media_slots[0]['crop']['type'] ?? '', 'build-pattern-page-plan declares bounded crop type for hero media' );
npcink_abilities_toolkit_assert_same( '16:9', $pattern_media_slots[0]['crop']['aspect_ratio'] ?? '', 'build-pattern-page-plan carries hero aspect ratio into crop guidance' );
npcink_abilities_toolkit_assert_same( 'ai_image_ratio_crop_media_adoption', $pattern_media_slots[0]['recommended_recipe_id'] ?? '', 'build-pattern-page-plan recommends the AI image crop adoption recipe for hero media' );
npcink_abilities_toolkit_assert_same( 'https://magick-ai.local/wp-content/uploads/2026/06/wordpress-ai-dashboard.jpg', $pattern_media_slots[0]['existing_media_url'] ?? '', 'build-pattern-page-plan echoes reviewed existing hero media URL in media slot metadata' );
npcink_abilities_toolkit_assert_same( 'hero_media_attachment_id', $pattern_media_slots[0]['attachment_id_variable'] ?? '', 'build-pattern-page-plan declares the hero media attachment id variable' );
npcink_abilities_toolkit_assert_same( 8053, $pattern_media_slots[0]['existing_media_attachment_id'] ?? 0, 'build-pattern-page-plan echoes reviewed hero media attachment id in media slot metadata' );
npcink_abilities_toolkit_assert_same( true, $pattern_media_slots[0]['media_input_valid'] ?? null, 'build-pattern-page-plan marks reviewed media URLs valid' );
npcink_abilities_toolkit_assert_same( true, $pattern_media_slots[0]['media_input_has_attachment_id'] ?? null, 'build-pattern-page-plan marks reviewed media attachment ids present' );
npcink_abilities_toolkit_assert_same( '3.0', $pattern_page_plan['data']['design_quality']['pattern_version'] ?? '', 'build-pattern-page-plan reports the v3 Pattern quality version' );
npcink_abilities_toolkit_assert_same( 'gutenberg_native_v1', $pattern_page_plan['data']['design_quality']['design_system'] ?? '', 'build-pattern-page-plan reports the Gutenberg-native design system contract' );
npcink_abilities_toolkit_assert_same( 'media_first_saas_landing', $pattern_page_plan['data']['design_quality']['recipe_variant'] ?? '', 'build-pattern-page-plan reports a media-first recipe variant when reviewed hero media is supplied' );
npcink_abilities_toolkit_assert_true( '' !== (string) ( $pattern_page_plan['data']['design_quality']['variant_reason'] ?? '' ), 'build-pattern-page-plan explains why the recipe variant was selected' );
npcink_abilities_toolkit_assert_same( 'gutenberg_native', $pattern_page_plan['data']['design_quality']['style_strategy'] ?? '', 'build-pattern-page-plan reports Gutenberg-native style strategy' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['data']['design_quality']['uses_native_styles'] ?? null, 'build-pattern-page-plan reports native style usage' );
npcink_abilities_toolkit_assert_same( 'minimal-dark-light', $pattern_page_plan['data']['design_quality']['color_story'] ?? '', 'build-pattern-page-plan reports the native color story in design quality' );
npcink_abilities_toolkit_assert_same( false, $pattern_page_plan['data']['design_quality']['has_editorial_accent'] ?? true, 'build-pattern-page-plan does not report editorial accents for the default monochrome story' );
npcink_abilities_toolkit_assert_true( (int) ( $pattern_page_plan['data']['design_quality']['section_shape_variety'] ?? 0 ) >= 4, 'build-pattern-page-plan reports enough section shape variety for landing pages' );
npcink_abilities_toolkit_assert_true( (float) ( $pattern_page_plan['data']['design_quality']['media_coverage_score'] ?? 0 ) >= 0.6, 'build-pattern-page-plan reports media coverage for modern landing pages' );
npcink_abilities_toolkit_assert_true( (float) ( $pattern_page_plan['data']['design_quality']['template_similarity_score'] ?? 1 ) <= 0.75, 'build-pattern-page-plan reports bounded template similarity risk' );
npcink_abilities_toolkit_assert_same( false, $pattern_page_plan['data']['design_quality']['uses_core_html'] ?? true, 'build-pattern-page-plan reports no core/html usage' );
npcink_abilities_toolkit_assert_same( false, $pattern_page_plan['data']['design_quality']['uses_non_core_blocks'] ?? true, 'build-pattern-page-plan reports no non-core block usage' );
npcink_abilities_toolkit_assert_same( 8, $pattern_page_plan['data']['design_quality']['section_count'] ?? 0, 'build-pattern-page-plan reports eight v2 sections when media is supplied' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['data']['design_quality']['has_split_hero'] ?? null, 'build-pattern-page-plan reports split hero' );
npcink_abilities_toolkit_assert_same( false, $pattern_page_plan['data']['design_quality']['has_dashboard_mock'] ?? null, 'build-pattern-page-plan does not report dashboard mock when reviewed hero media is supplied' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['data']['design_quality']['has_hero_media'] ?? null, 'build-pattern-page-plan reports reviewed hero media in the split hero' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['data']['design_quality']['has_hero_media_attachment_id'] ?? null, 'build-pattern-page-plan reports reviewed hero media attachment binding' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['data']['design_quality']['has_proof_strip'] ?? null, 'build-pattern-page-plan reports proof strip' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['data']['design_quality']['has_bento_grid'] ?? null, 'build-pattern-page-plan reports the Gutenberg-native Bento feature grid' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['data']['design_quality']['has_media_text'] ?? null, 'build-pattern-page-plan reports media-text section' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['data']['design_quality']['has_comparison_section'] ?? null, 'build-pattern-page-plan reports the default proposal-first comparison section' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['data']['design_quality']['has_faq'] ?? null, 'build-pattern-page-plan reports FAQ section' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['data']['design_quality']['has_final_cta'] ?? null, 'build-pattern-page-plan reports final CTA' );
npcink_abilities_toolkit_assert_same( false, $pattern_page_plan['data']['design_quality']['custom_css_required'] ?? true, 'build-pattern-page-plan reports no custom CSS requirement' );
npcink_abilities_toolkit_assert_same( 'landing_standard', $pattern_page_plan['data']['responsive_quality']['responsive_profile'] ?? '', 'build-pattern-page-plan reports responsive profile quality' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['data']['responsive_quality']['uses_mobile_stack'] ?? null, 'build-pattern-page-plan reports mobile column stacking' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['data']['responsive_quality']['uses_core_responsive_blocks'] ?? null, 'build-pattern-page-plan reports core responsive blocks' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['data']['responsive_quality']['has_media_section'] ?? null, 'build-pattern-page-plan reports media section responsiveness' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['data']['responsive_quality']['has_faq'] ?? null, 'build-pattern-page-plan reports responsive FAQ' );
npcink_abilities_toolkit_assert_same( 4, $pattern_page_plan['data']['responsive_quality']['max_columns_per_row'] ?? 0, 'build-pattern-page-plan reports bounded max columns per row' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['data']['responsive_quality']['button_groups_use_flex_layout'] ?? null, 'build-pattern-page-plan reports flex button groups' );
npcink_abilities_toolkit_assert_same( false, $pattern_page_plan['data']['responsive_quality']['custom_css_required'] ?? true, 'build-pattern-page-plan reports responsive output without custom CSS' );
npcink_abilities_toolkit_assert_same( false, $pattern_page_plan['data']['quality_feedback']['feedback_received'] ?? true, 'build-pattern-page-plan reports no review feedback on first-generation plans' );
	npcink_abilities_toolkit_assert_same( 'pass', $pattern_page_plan['data']['quality_review']['review_status'] ?? '', 'build-pattern-page-plan self-reviews generated Pattern blocks' );
	npcink_abilities_toolkit_assert_true( (int) ( $pattern_page_plan['data']['quality_review']['score'] ?? 0 ) >= 80, 'build-pattern-page-plan self-review scores generated Pattern blocks above threshold' );
	npcink_abilities_toolkit_assert_same( 'gutenberg_native_v1', $pattern_page_plan['data']['composition_contract']['catalog_id'] ?? '', 'build-pattern-page-plan references the Gutenberg block capability catalog' );
	npcink_abilities_toolkit_assert_same( 'bounded_block_composition', $pattern_page_plan['data']['composition_contract']['composition_model'] ?? '', 'build-pattern-page-plan uses bounded block composition' );
	npcink_abilities_toolkit_assert_same( 'pass', $pattern_page_plan['data']['composition_contract']['contract_status'] ?? '', 'build-pattern-page-plan passes the block composition contract' );
	npcink_abilities_toolkit_assert_same( array(), $pattern_page_plan['data']['composition_contract']['forbidden_block_names'] ?? array( 'unexpected' ), 'build-pattern-page-plan reports no forbidden block contract violations' );
	npcink_abilities_toolkit_assert_true( in_array( 'core/media-text', $pattern_page_plan['data']['composition_contract']['used_block_names'] ?? array(), true ), 'build-pattern-page-plan contract records media-text usage' );
	npcink_abilities_toolkit_assert_same( 'gutenberg_native_block_composer_v1', $pattern_page_plan['data']['composition_contract']['composer_instruction']['instruction_id'] ?? '', 'build-pattern-page-plan exposes AI composer instructions through the contract' );
	npcink_abilities_toolkit_assert_same( 'landing_design', $pattern_page_plan['data']['block_editor_quality_gate']['profile'] ?? '', 'build-pattern-page-plan uses the full landing design quality gate' );
	npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['data']['block_editor_quality_gate']['ready_for_proposal'] ?? null, 'build-pattern-page-plan marks high-quality blocks ready through the block-editor quality gate' );
	npcink_abilities_toolkit_assert_same( false, $pattern_page_plan['data']['block_editor_quality_gate']['commit_execution'] ?? null, 'build-pattern-page-plan quality gate does not execute commits' );
	npcink_abilities_toolkit_assert_same( 8, $pattern_page_plan['data']['quality_review']['layout_fingerprint']['section_count'] ?? 0, 'build-pattern-page-plan self-review includes a layout fingerprint' );
npcink_abilities_toolkit_assert_true( in_array( 'center', $pattern_page_plan['data']['quality_review']['layout_fingerprint']['alignment_mix'] ?? array(), true ), 'build-pattern-page-plan self-review sees centered section alignment' );
$pattern_self_review_finding_codes = array_map(
	static function ( $finding ) {
		return is_array( $finding ) ? (string) ( $finding['code'] ?? '' ) : '';
	},
	is_array( $pattern_page_plan['data']['quality_review']['visual_quality_findings'] ?? null ) ? $pattern_page_plan['data']['quality_review']['visual_quality_findings'] : array()
);
npcink_abilities_toolkit_assert_true( in_array( 'color_story_monochrome', $pattern_self_review_finding_codes, true ), 'build-pattern-page-plan self-review flags monochrome native color stories as a non-blocking visual finding' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_plan['data']['revision_strategy']['ready_for_proposal'] ?? null, 'build-pattern-page-plan marks high-quality generated plans ready for Core proposal handoff' );
npcink_abilities_toolkit_assert_same( 'submit_core_proposal', $pattern_page_plan['data']['revision_strategy']['recommended_next_step'] ?? '', 'build-pattern-page-plan recommends Core proposal handoff when self-review passes' );
$pattern_page_actions = is_array( $pattern_page_plan['data']['write_actions'] ?? null ) ? $pattern_page_plan['data']['write_actions'] : array();
npcink_abilities_toolkit_assert_same( 2, count( $pattern_page_actions ), 'build-pattern-page-plan emits create and block update actions' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/create-draft', $pattern_page_actions[0]['target_ability_id'] ?? '', 'build-pattern-page-plan first creates a draft page' );
npcink_abilities_toolkit_assert_same( 'page', $pattern_page_actions[0]['input']['post_type'] ?? '', 'build-pattern-page-plan create action targets a page' );
npcink_abilities_toolkit_assert_same( 'draft', $pattern_page_actions[0]['input']['status'] ?? '', 'build-pattern-page-plan create action stays draft-only' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/update-post-blocks', $pattern_page_actions[1]['target_ability_id'] ?? '', 'build-pattern-page-plan second action updates Gutenberg blocks' );
npcink_abilities_toolkit_assert_same( '$outputs.create-pattern-page.post_id', $pattern_page_actions[1]['input']['post_id'] ?? '', 'build-pattern-page-plan uses exact output reference for the new page id' );
$pattern_blocks = is_array( $pattern_page_actions[1]['input']['blocks'] ?? null ) ? $pattern_page_actions[1]['input']['blocks'] : array();
npcink_abilities_toolkit_assert_same( 'core/group', $pattern_blocks[0]['blockName'] ?? '', 'build-pattern-page-plan renders core group blocks' );
npcink_abilities_toolkit_assert_true( in_array( null, $pattern_blocks[0]['innerContent'] ?? array(), true ), 'build-pattern-page-plan group blocks include innerContent null markers' );
npcink_abilities_toolkit_assert_true( in_array( 'npcink-ai-hero', $pattern_page_plan['data']['allowed_classes'] ?? array(), true ), 'build-pattern-page-plan exposes a class whitelist' );
npcink_abilities_toolkit_assert_true( in_array( 'npcink-ai-feature-bento', $pattern_page_plan['data']['allowed_classes'] ?? array(), true ), 'build-pattern-page-plan exposes the Bento feature class handle' );
npcink_abilities_toolkit_assert_true( in_array( 'npcink-ai-feature-spotlight', $pattern_page_plan['data']['allowed_classes'] ?? array(), true ), 'build-pattern-page-plan exposes the Bento spotlight class handle' );
npcink_abilities_toolkit_assert_true( in_array( 'npcink-ai-visual-delta', $pattern_page_plan['data']['allowed_classes'] ?? array(), true ), 'build-pattern-page-plan exposes the visual-delta class handle for review revisions' );
npcink_abilities_toolkit_assert_same( 'npcink-ai-page npcink-ai-hero', $pattern_blocks[0]['attrs']['className'] ?? '', 'build-pattern-page-plan applies only whitelisted page classes' );
npcink_abilities_toolkit_assert_same( 'full', $pattern_blocks[0]['attrs']['align'] ?? '', 'build-pattern-page-plan uses full-width Gutenberg sections' );
npcink_abilities_toolkit_assert_same( 'constrained', $pattern_blocks[0]['attrs']['layout']['type'] ?? '', 'build-pattern-page-plan uses constrained section layout' );
npcink_abilities_toolkit_assert_same( '1120px', $pattern_blocks[0]['attrs']['layout']['contentSize'] ?? '', 'build-pattern-page-plan sets native content width' );
npcink_abilities_toolkit_assert_same( '96px', $pattern_blocks[0]['attrs']['style']['spacing']['padding']['top'] ?? '', 'build-pattern-page-plan sets native hero spacing' );
npcink_abilities_toolkit_assert_same( '#f7f7f4', $pattern_blocks[0]['attrs']['style']['color']['background'] ?? '', 'build-pattern-page-plan sets native section background' );
npcink_abilities_toolkit_assert_true( false !== strpos( (string) ( $pattern_blocks[0]['innerHTML'] ?? '' ), 'has-background' ), 'build-pattern-page-plan emits Gutenberg background support class for hero group' );
$pattern_hero_layout = is_array( $pattern_blocks[0]['innerBlocks'][0] ?? null ) ? $pattern_blocks[0]['innerBlocks'][0] : array();
npcink_abilities_toolkit_assert_same( 'core/columns', $pattern_hero_layout['blockName'] ?? '', 'build-pattern-page-plan uses a split hero columns block' );
npcink_abilities_toolkit_assert_same( 'npcink-ai-hero-layout', $pattern_hero_layout['attrs']['className'] ?? '', 'build-pattern-page-plan marks the split hero layout' );
$pattern_hero_visual = is_array( $pattern_hero_layout['innerBlocks'][1]['innerBlocks'][0] ?? null ) ? $pattern_hero_layout['innerBlocks'][1]['innerBlocks'][0] : array();
npcink_abilities_toolkit_assert_same( 'core/group', $pattern_hero_visual['blockName'] ?? '', 'build-pattern-page-plan wraps reviewed hero media in a native group panel' );
npcink_abilities_toolkit_assert_same( 'npcink-ai-dashboard-card npcink-ai-hero-media-card', $pattern_hero_visual['attrs']['className'] ?? '', 'build-pattern-page-plan marks the hero media panel with whitelisted classes' );
npcink_abilities_toolkit_assert_same( 'core/image', $pattern_hero_visual['innerBlocks'][0]['blockName'] ?? '', 'build-pattern-page-plan places reviewed media in the hero visual column' );
npcink_abilities_toolkit_assert_same( 'https://magick-ai.local/wp-content/uploads/2026/06/wordpress-ai-dashboard.jpg', $pattern_hero_visual['innerBlocks'][0]['attrs']['url'] ?? '', 'build-pattern-page-plan uses the reviewed hero media URL in the hero image block' );
npcink_abilities_toolkit_assert_same( 8053, $pattern_hero_visual['innerBlocks'][0]['attrs']['id'] ?? 0, 'build-pattern-page-plan binds the reviewed hero image block to the attachment id' );
$pattern_hero_copy = is_array( $pattern_hero_layout['innerBlocks'][0]['innerBlocks'] ?? null ) ? $pattern_hero_layout['innerBlocks'][0]['innerBlocks'] : array();
npcink_abilities_toolkit_assert_same( '56px', $pattern_hero_copy[1]['attrs']['style']['typography']['fontSize'] ?? '', 'build-pattern-page-plan sets native hero title typography' );
npcink_abilities_toolkit_assert_same( '1.08', $pattern_hero_copy[1]['attrs']['style']['typography']['lineHeight'] ?? '', 'build-pattern-page-plan sets native hero title line height' );
$pattern_buttons = is_array( $pattern_hero_copy[3]['innerBlocks'] ?? null ) ? $pattern_hero_copy[3]['innerBlocks'] : array();
npcink_abilities_toolkit_assert_same( '999px', $pattern_buttons[0]['attrs']['style']['border']['radius'] ?? '', 'build-pattern-page-plan sets native button radius' );
npcink_abilities_toolkit_assert_same( '#111111', $pattern_buttons[0]['attrs']['style']['color']['background'] ?? '', 'build-pattern-page-plan sets native primary button color' );
$pattern_markup = wp_json_encode( $pattern_blocks );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, 'font-size:56px;font-weight:500;letter-spacing:0;line-height:1.08' ), 'build-pattern-page-plan serializes heading typography in Gutenberg save order' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, 'wp-block-button__link has-text-color has-background wp-element-button' ), 'build-pattern-page-plan emits Gutenberg support classes for primary button links' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, '"blockName":"core\\/columns"' ), 'build-pattern-page-plan uses native columns for feature and workflow sections' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, '"isStackedOnMobile":true' ), 'build-pattern-page-plan stacks columns on mobile' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, '"blockName":"core\\/media-text"' ), 'build-pattern-page-plan uses media-text when existing media is supplied' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, '"mediaId":8053' ), 'build-pattern-page-plan binds media-text to the reviewed attachment id' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, '"blockName":"core\\/image"' ), 'build-pattern-page-plan uses core image for hero media when supplied' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, '"id":8053' ), 'build-pattern-page-plan serializes image attachment id attrs' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, 'class=\"wp-image-8053\"' ), 'build-pattern-page-plan serializes wp-image attachment classes' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, '"blockName":"core\\/details"' ), 'build-pattern-page-plan uses details blocks for FAQ' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, 'npcink-ai-dashboard-card' ), 'build-pattern-page-plan includes a styled hero visual card' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false === strpos( $pattern_markup, 'npcink-ai-dashboard-mock' ), 'build-pattern-page-plan omits the dashboard mock when reviewed hero media is supplied' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, 'npcink-ai-hero-media-card' ), 'build-pattern-page-plan includes a hero media card class handle' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, 'npcink-ai-proof-strip' ), 'build-pattern-page-plan includes a proof strip' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, 'npcink-ai-media-text' ), 'build-pattern-page-plan includes a media-text class handle' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, 'npcink-ai-feature-bento' ), 'build-pattern-page-plan includes a Gutenberg-native Bento feature layout' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, 'npcink-ai-feature-spotlight' ), 'build-pattern-page-plan includes a dark spotlight feature card' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, 'border-color:#111111;border-width:1px;border-radius:24px;background-color:#111111;color:#ffffff' ), 'build-pattern-page-plan serializes the spotlight card with native color and border styles' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, 'npcink-ai-comparison' ), 'build-pattern-page-plan includes a default proposal-first comparison section' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, 'npcink-ai-comparison-card' ), 'build-pattern-page-plan includes comparison cards' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, 'npcink-ai-section-title-center' ), 'build-pattern-page-plan uses the centered comparison title variant by default' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, 'has-text-align-center' ), 'build-pattern-page-plan serializes centered heading classes for Gutenberg validation' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, 'background-color:#111111;color:#ffffff;padding-top:88px;padding-right:40px;padding-bottom:88px;padding-left:40px' ), 'build-pattern-page-plan renders the comparison section as a native dark contrast band' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, 'OpenClaw proposal-first' ), 'build-pattern-page-plan includes the proposal-first comparison side' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, 'npcink-ai-faq-item' ), 'build-pattern-page-plan includes FAQ item class handles' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, 'npcink-ai-final-cta' ), 'build-pattern-page-plan includes a final CTA' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, 'wp-block-heading has-text-align-center has-text-color npcink-ai-section-title npcink-ai-section-title-light' ), 'build-pattern-page-plan centers the final CTA heading and emits Gutenberg text color classes' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, 'has-text-align-center has-text-color npcink-ai-lede' ), 'build-pattern-page-plan centers the final CTA description and emits Gutenberg text color classes' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, 'background-color:#111111;color:#ffffff;padding-top:88px;padding-right:40px;padding-bottom:96px;padding-left:40px' ), 'build-pattern-page-plan makes the dark final CTA text color explicit' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, 'border-radius:999px;background-color:#ffffff;color:#111111;padding-top:14px;padding-right:24px;padding-bottom:14px;padding-left:24px' ), 'build-pattern-page-plan renders a visible primary CTA button on dark bands' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, 'border-color:#ffffff;border-width:1px;border-radius:999px;background-color:#111111;color:#ffffff;padding-top:14px;padding-right:24px;padding-bottom:14px;padding-left:24px' ), 'build-pattern-page-plan renders a matching secondary CTA button on dark bands' );
npcink_abilities_toolkit_assert_true( is_string( $pattern_markup ) && false !== strpos( $pattern_markup, '"top":{"color":"#111111","width":"1px"}' ), 'build-pattern-page-plan uses native top-line card border attrs' );
$long_hero_pattern_page_plan = $core_read_package->build_pattern_page_plan(
	array(
		'title'              => 'Long Hero Copy Fit',
		'pattern_id'         => 'openai-style-landing',
		'style_preset'       => 'minimal-dark-light',
		'responsive_profile' => 'landing_standard',
		'visual_density'     => 'balanced',
		'media_strategy'     => 'existing_media_url',
		'variables'          => array(
			'hero_title'       => '用 Gutenberg 原生块搭出现代官网介绍页，长标题也保持舒展清晰',
			'hero_media_url'   => 'https://magick-ai.local/wp-content/uploads/2026/06/wordpress-ai-dashboard.jpg',
			'hero_media_attachment_id' => 8053,
			'hero_media_alt'   => 'WordPress AI dashboard preview',
		),
	)
);
npcink_abilities_toolkit_assert_same( true, $long_hero_pattern_page_plan['success'] ?? null, 'build-pattern-page-plan accepts imprecise long natural-language hero titles' );
npcink_abilities_toolkit_assert_same( 'revised', $long_hero_pattern_page_plan['data']['copy_quality']['hero_title_fit'] ?? '', 'build-pattern-page-plan revises overlong hero titles before rendering blocks' );
npcink_abilities_toolkit_assert_same( '用 Gutenberg 原生块搭出现代官网', $long_hero_pattern_page_plan['data']['copy_quality']['hero_title'] ?? '', 'build-pattern-page-plan compacts long Chinese hero titles into a display-safe H1' );
npcink_abilities_toolkit_assert_same( true, $long_hero_pattern_page_plan['data']['copy_quality']['hero_title_changed'] ?? null, 'build-pattern-page-plan reports hero title copy changes' );
npcink_abilities_toolkit_assert_true( (int) ( $long_hero_pattern_page_plan['data']['copy_quality']['hero_title_display_units'] ?? 99 ) <= 34, 'build-pattern-page-plan keeps revised hero titles within the display budget' );
$long_hero_actions = is_array( $long_hero_pattern_page_plan['data']['write_actions'] ?? null ) ? $long_hero_pattern_page_plan['data']['write_actions'] : array();
$long_hero_blocks = is_array( $long_hero_actions[1]['input']['blocks'] ?? null ) ? $long_hero_actions[1]['input']['blocks'] : array();
$long_hero_layout = is_array( $long_hero_blocks[0]['innerBlocks'][0] ?? null ) ? $long_hero_blocks[0]['innerBlocks'][0] : array();
$long_hero_copy = is_array( $long_hero_layout['innerBlocks'][0]['innerBlocks'] ?? null ) ? $long_hero_layout['innerBlocks'][0]['innerBlocks'] : array();
$long_hero_heading_html = (string) ( $long_hero_copy[1]['innerHTML'] ?? '' );
npcink_abilities_toolkit_assert_true( false !== strpos( $long_hero_heading_html, '用 Gutenberg 原生块搭出现代官网</h1>' ), 'build-pattern-page-plan writes the fitted Hero title into Gutenberg blocks' );
npcink_abilities_toolkit_assert_true( false === strpos( $long_hero_heading_html, '长标题也保持舒展清晰</h1>' ), 'build-pattern-page-plan does not serialize prompt-like long clauses into the Hero H1' );
$inferred_title_pattern_page_plan = $core_read_package->build_pattern_page_plan(
	array(
		'pattern_id'         => 'openai-style-landing',
		'style_preset'       => 'minimal-dark-light',
		'responsive_profile' => 'landing_standard',
		'visual_density'     => 'balanced',
		'media_strategy'     => 'existing_media_url',
		'variables'          => array(
			'hero_title'       => '用 Gutenberg 原生块搭出现代官网介绍页，长标题也保持舒展清晰',
			'hero_media_url'   => 'https://magick-ai.local/wp-content/uploads/2026/06/wordpress-ai-dashboard.jpg',
			'hero_media_attachment_id' => 8053,
			'hero_media_alt'   => 'WordPress AI dashboard preview',
		),
	)
);
$inferred_title_actions = is_array( $inferred_title_pattern_page_plan['data']['write_actions'] ?? null ) ? $inferred_title_pattern_page_plan['data']['write_actions'] : array();
npcink_abilities_toolkit_assert_same( true, $inferred_title_pattern_page_plan['success'] ?? null, 'build-pattern-page-plan accepts natural-language page requests without a top-level title' );
npcink_abilities_toolkit_assert_same( '用 Gutenberg 原生块搭出现代官网', $inferred_title_pattern_page_plan['data']['target_post']['title'] ?? '', 'build-pattern-page-plan infers the draft title from the fitted Hero title' );
npcink_abilities_toolkit_assert_same( '用 Gutenberg 原生块搭出现代官网', $inferred_title_actions[0]['input']['title'] ?? '', 'build-pattern-page-plan writes the inferred title into the create-draft action' );
npcink_abilities_toolkit_assert_same( 'revised', $inferred_title_pattern_page_plan['data']['copy_quality']['hero_title_fit'] ?? '', 'build-pattern-page-plan still reports copy fit when title is inferred' );
$accent_pattern_page_plan = $core_read_package->build_pattern_page_plan(
	array(
		'title'              => 'Accent WordPress AI',
		'pattern_id'         => 'openai-style-landing',
		'style_preset'       => 'minimal-dark-light',
		'color_story'        => 'editorial-accent',
		'responsive_profile' => 'landing_standard',
		'visual_density'     => 'balanced',
		'media_strategy'     => 'existing_media_url',
		'section_variant_hints' => array(
			'comparison' => 'center-title-two-cards',
		),
		'variables'          => array(
			'hero_title'     => 'Accent Gutenberg landing page',
			'hero_media_url' => 'https://magick-ai.local/wp-content/uploads/2026/06/accent-dashboard.jpg',
			'hero_media_alt' => 'Accent WordPress AI dashboard preview',
		),
	)
);
$accent_actions = is_array( $accent_pattern_page_plan['data']['write_actions'] ?? null ) ? $accent_pattern_page_plan['data']['write_actions'] : array();
$accent_blocks = is_array( $accent_actions[1]['input']['blocks'] ?? null ) ? $accent_actions[1]['input']['blocks'] : array();
$accent_markup = wp_json_encode( $accent_blocks );
npcink_abilities_toolkit_assert_same( true, $accent_pattern_page_plan['success'] ?? null, 'build-pattern-page-plan accepts the editorial accent color story' );
npcink_abilities_toolkit_assert_same( 'editorial-accent', $accent_pattern_page_plan['data']['color_story'] ?? '', 'build-pattern-page-plan preserves the editorial accent color story' );
npcink_abilities_toolkit_assert_same( 'editorial-accent', $accent_pattern_page_plan['data']['design_quality']['color_story'] ?? '', 'build-pattern-page-plan reports the editorial accent story in design quality' );
npcink_abilities_toolkit_assert_same( true, $accent_pattern_page_plan['data']['design_quality']['has_editorial_accent'] ?? null, 'build-pattern-page-plan marks editorial accent pages as visually accented' );
npcink_abilities_toolkit_assert_true( (int) ( $accent_pattern_page_plan['data']['design_quality']['accent_surface_count'] ?? 0 ) >= 4, 'build-pattern-page-plan counts accent surfaces in design quality' );
npcink_abilities_toolkit_assert_same( '#f4f8f7', $accent_blocks[0]['attrs']['style']['color']['background'] ?? '', 'build-pattern-page-plan applies the accent hero background natively' );
$accent_hero_layout = is_array( $accent_blocks[0]['innerBlocks'][0] ?? null ) ? $accent_blocks[0]['innerBlocks'][0] : array();
$accent_hero_copy = is_array( $accent_hero_layout['innerBlocks'][0]['innerBlocks'] ?? null ) ? $accent_hero_layout['innerBlocks'][0]['innerBlocks'] : array();
$accent_buttons = is_array( $accent_hero_copy[3]['innerBlocks'] ?? null ) ? $accent_hero_copy[3]['innerBlocks'] : array();
npcink_abilities_toolkit_assert_same( '#102b2d', $accent_buttons[0]['attrs']['style']['color']['background'] ?? '', 'build-pattern-page-plan uses the accent contrast color for the primary hero button' );
npcink_abilities_toolkit_assert_same( '#102b2d', $accent_buttons[1]['attrs']['style']['border']['color'] ?? '', 'build-pattern-page-plan uses the accent contrast color for the secondary hero button border' );
npcink_abilities_toolkit_assert_true( is_string( $accent_markup ) && false !== strpos( $accent_markup, 'color:#2f6f68;font-size:13px' ), 'build-pattern-page-plan serializes the active accent color on eyebrow copy' );
npcink_abilities_toolkit_assert_true( is_string( $accent_markup ) && false !== strpos( $accent_markup, '"top":{"color":"#2f6f68","width":"1px"}' ), 'build-pattern-page-plan applies the accent color to proof card top rules' );
npcink_abilities_toolkit_assert_true( is_string( $accent_markup ) && false !== strpos( $accent_markup, 'border-color:#102b2d;border-width:1px;border-radius:24px;background-color:#102b2d;color:#ffffff' ), 'build-pattern-page-plan uses the accent contrast color for the feature spotlight card' );
npcink_abilities_toolkit_assert_true( is_string( $accent_markup ) && false !== strpos( $accent_markup, 'border-color:#2f6f68;border-width:1px;border-radius:20px;background-color:#153f42;color:#ffffff' ), 'build-pattern-page-plan gives the accent comparison card a distinct native background' );
npcink_abilities_toolkit_assert_true( is_string( $accent_markup ) && false !== strpos( $accent_markup, 'background-color:#dfeee9;padding-top:72px;padding-right:40px;padding-bottom:72px;padding-left:40px' ), 'build-pattern-page-plan applies the accent media section background natively' );
npcink_abilities_toolkit_assert_true( is_string( $accent_markup ) && false !== strpos( $accent_markup, 'background-color:#102b2d;color:#ffffff;padding-top:88px;padding-right:40px;padding-bottom:96px;padding-left:40px' ), 'build-pattern-page-plan applies the accent dark final CTA background natively' );
npcink_abilities_toolkit_assert_true( is_string( $accent_markup ) && false !== strpos( $accent_markup, 'border-color:#ffffff;border-width:1px;border-radius:999px;background-color:#102b2d;color:#ffffff;padding-top:14px;padding-right:24px;padding-bottom:14px;padding-left:24px' ), 'build-pattern-page-plan keeps the secondary CTA visible on accent dark bands' );
npcink_abilities_toolkit_assert_true( (int) ( $accent_pattern_page_plan['data']['quality_review']['layout_fingerprint']['accent_color_count'] ?? 0 ) >= 1, 'build-pattern-page-plan review fingerprint detects accent color usage' );
$accent_review_finding_codes = array_map(
	static function ( $finding ) {
		return is_array( $finding ) ? (string) ( $finding['code'] ?? '' ) : '';
	},
	is_array( $accent_pattern_page_plan['data']['quality_review']['visual_quality_findings'] ?? null ) ? $accent_pattern_page_plan['data']['quality_review']['visual_quality_findings'] : array()
);
npcink_abilities_toolkit_assert_true( ! in_array( 'color_story_monochrome', $accent_review_finding_codes, true ), 'build-pattern-page-plan does not flag the editorial accent story as monochrome' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][280973] = (object) array(
	'ID'           => 280973,
	'post_type'    => 'page',
	'post_status'  => 'draft',
	'post_title'   => 'Existing Pattern Draft',
	'post_content' => '<!-- wp:paragraph --><p>Existing draft body.</p><!-- /wp:paragraph -->',
);
$target_pattern_page_plan = $core_read_package->build_pattern_page_plan(
	array(
		'title'              => 'Update Existing WordPress AI',
		'target_post_id'     => 280973,
		'pattern_id'         => 'openai-style-landing',
		'style_preset'       => 'minimal-dark-light',
		'color_story'        => 'editorial-accent',
		'responsive_profile' => 'landing_standard',
		'visual_density'     => 'balanced',
		'media_strategy'     => 'existing_media_url',
		'variables'          => array(
			'hero_title'     => 'Update an existing Gutenberg draft',
			'hero_media_url' => 'https://magick-ai.local/wp-content/uploads/2026/06/existing-target-dashboard.jpg',
			'hero_media_alt' => 'Existing target WordPress AI dashboard preview',
		),
	)
);
npcink_abilities_toolkit_assert_same( true, $target_pattern_page_plan['success'] ?? null, 'build-pattern-page-plan accepts an existing draft page target' );
npcink_abilities_toolkit_assert_same( 'update_existing', $target_pattern_page_plan['data']['target_post']['mode'] ?? '', 'build-pattern-page-plan reports existing draft update mode' );
npcink_abilities_toolkit_assert_same( 280973, $target_pattern_page_plan['data']['target_post']['post_id'] ?? 0, 'build-pattern-page-plan preserves the target draft id' );
npcink_abilities_toolkit_assert_same( 'draft', $target_pattern_page_plan['data']['target_post']['status'] ?? '', 'build-pattern-page-plan reports the target draft status' );
npcink_abilities_toolkit_assert_same( 'update_existing', $target_pattern_page_plan['data']['block_editor_surface']['target_mode'] ?? '', 'build-pattern-page-plan reports update surface mode for target drafts' );
npcink_abilities_toolkit_assert_same( 1, $target_pattern_page_plan['data']['summary']['action_count'] ?? 0, 'build-pattern-page-plan emits one action when updating an existing draft page' );
$target_pattern_actions = is_array( $target_pattern_page_plan['data']['write_actions'] ?? null ) ? $target_pattern_page_plan['data']['write_actions'] : array();
npcink_abilities_toolkit_assert_same( 1, count( $target_pattern_actions ), 'build-pattern-page-plan omits create-draft when target_post_id is supplied' );
npcink_abilities_toolkit_assert_same( 'update-pattern-page-blocks', $target_pattern_actions[0]['action_id'] ?? '', 'build-pattern-page-plan keeps the update action id for existing drafts' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/update-post-blocks', $target_pattern_actions[0]['target_ability_id'] ?? '', 'build-pattern-page-plan targets update-post-blocks for existing drafts' );
npcink_abilities_toolkit_assert_same( 280973, $target_pattern_actions[0]['input']['post_id'] ?? 0, 'build-pattern-page-plan uses the concrete target draft id' );
npcink_abilities_toolkit_assert_same( false, $target_pattern_actions[0]['input']['commit'] ?? true, 'build-pattern-page-plan keeps existing draft update actions non-committing' );
npcink_abilities_toolkit_assert_same( true, $target_pattern_actions[0]['input']['dry_run'] ?? false, 'build-pattern-page-plan keeps existing draft update actions dry-run by default' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][280974] = (object) array(
	'ID'           => 280974,
	'post_type'    => 'page',
	'post_status'  => 'publish',
	'post_title'   => 'Published Pattern Page',
	'post_content' => '<!-- wp:paragraph --><p>Published body.</p><!-- /wp:paragraph -->',
);
$published_target_pattern_page_plan = $core_read_package->build_pattern_page_plan(
	array(
		'title'          => 'Reject Published Target',
		'target_post_id' => 280974,
		'pattern_id'     => 'openai-style-landing',
	)
);
npcink_abilities_toolkit_assert_true( is_wp_error( $published_target_pattern_page_plan ) && 'npcink_abilities_toolkit_pattern_page_target_status_invalid' === $published_target_pattern_page_plan->get_error_code(), 'build-pattern-page-plan rejects published target pages for replacement proposals' );
$placeholder_media_pattern_page_plan = $core_read_package->build_pattern_page_plan(
	array(
		'title'              => 'Placeholder Media Pattern',
		'pattern_id'         => 'openai-style-landing',
		'style_preset'       => 'minimal-dark-light',
		'responsive_profile' => 'landing_standard',
		'visual_density'     => 'balanced',
		'media_strategy'     => 'existing_media_url',
		'variables'          => array(
			'hero_title'     => 'Placeholder media should not render',
			'hero_media_url' => 'https://example.test/wp-content/uploads/2026/06/placeholder-dashboard.jpg',
			'hero_media_alt' => 'Placeholder dashboard preview',
		),
	)
);
npcink_abilities_toolkit_assert_same( true, $placeholder_media_pattern_page_plan['success'] ?? null, 'build-pattern-page-plan accepts placeholder media inputs for repair planning' );
$placeholder_media_slots = is_array( $placeholder_media_pattern_page_plan['data']['media_slots'] ?? null ) ? $placeholder_media_pattern_page_plan['data']['media_slots'] : array();
npcink_abilities_toolkit_assert_same( '', $placeholder_media_slots[0]['existing_media_url'] ?? 'unexpected', 'build-pattern-page-plan does not echo placeholder media URLs as reviewed media' );
npcink_abilities_toolkit_assert_same( false, $placeholder_media_slots[0]['media_input_valid'] ?? null, 'build-pattern-page-plan marks placeholder media URLs invalid' );
$placeholder_actions = is_array( $placeholder_media_pattern_page_plan['data']['write_actions'] ?? null ) ? $placeholder_media_pattern_page_plan['data']['write_actions'] : array();
$placeholder_blocks = is_array( $placeholder_actions[1]['input']['blocks'] ?? null ) ? $placeholder_actions[1]['input']['blocks'] : array();
$placeholder_markup = wp_json_encode( $placeholder_blocks );
npcink_abilities_toolkit_assert_same( true, $placeholder_media_pattern_page_plan['data']['design_quality']['has_dashboard_mock'] ?? null, 'build-pattern-page-plan falls back to the dashboard mock for placeholder media URLs' );
npcink_abilities_toolkit_assert_same( false, $placeholder_media_pattern_page_plan['data']['design_quality']['has_hero_media'] ?? null, 'build-pattern-page-plan does not report placeholder media as hero media' );
npcink_abilities_toolkit_assert_true( is_string( $placeholder_markup ) && false === strpos( $placeholder_markup, 'placeholder-dashboard.jpg' ), 'build-pattern-page-plan does not serialize placeholder media URLs into blocks' );
npcink_abilities_toolkit_assert_true( is_string( $placeholder_markup ) && false !== strpos( $placeholder_markup, 'npcink-ai-dashboard-mock' ), 'build-pattern-page-plan serializes a mock panel instead of a broken image' );
$left_variant_pattern_page_plan = $core_read_package->build_pattern_page_plan(
	array(
		'title'              => 'Left Variant Pattern',
		'pattern_id'         => 'openai-style-landing',
		'style_preset'       => 'minimal-dark-light',
		'responsive_profile' => 'landing_standard',
		'visual_density'     => 'balanced',
		'media_strategy'     => 'existing_media_url',
		'section_variant_hints' => array(
			'comparison' => 'left-title-two-cards',
		),
		'variables'          => array(
			'hero_title'     => 'Left comparison variant',
			'hero_media_url' => 'https://magick-ai.local/wp-content/uploads/2026/06/left-variant-dashboard.jpg',
			'hero_media_alt' => 'Left comparison variant dashboard preview',
		),
	)
);
$left_variant_actions = is_array( $left_variant_pattern_page_plan['data']['write_actions'] ?? null ) ? $left_variant_pattern_page_plan['data']['write_actions'] : array();
$left_variant_blocks = is_array( $left_variant_actions[1]['input']['blocks'] ?? null ) ? $left_variant_actions[1]['input']['blocks'] : array();
$left_variant_markup = wp_json_encode( $left_variant_blocks );
npcink_abilities_toolkit_assert_same( true, $left_variant_pattern_page_plan['success'] ?? null, 'build-pattern-page-plan accepts the left comparison title variant' );
npcink_abilities_toolkit_assert_same( 'left-title-two-cards', $left_variant_pattern_page_plan['data']['section_variant_hints']['comparison'] ?? '', 'build-pattern-page-plan preserves the left comparison title variant' );
npcink_abilities_toolkit_assert_true( is_string( $left_variant_markup ) && false === strpos( $left_variant_markup, 'npcink-ai-section-title-center' ), 'build-pattern-page-plan omits centered title class for the left comparison variant' );
$pattern_page_review = $core_read_package->review_pattern_page(
	array(
		'blocks' => $pattern_blocks,
	)
);
npcink_abilities_toolkit_assert_same( true, $pattern_page_review['success'] ?? null, 'review-pattern-page returns a success envelope for proposed blocks' );
npcink_abilities_toolkit_assert_same( 'pattern_page_review', $pattern_page_review['data']['artifact_type'] ?? '', 'review-pattern-page declares a pattern page review artifact' );
npcink_abilities_toolkit_assert_same( 'blocks_input', $pattern_page_review['data']['source'] ?? '', 'review-pattern-page can review blocks before they are written' );
npcink_abilities_toolkit_assert_same( false, $pattern_page_review['data']['direct_wordpress_write'] ?? null, 'review-pattern-page does not write WordPress' );
npcink_abilities_toolkit_assert_same( false, $pattern_page_review['data']['commit_execution'] ?? null, 'review-pattern-page keeps commit execution disabled' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_review['data']['server_side_review_only'] ?? null, 'review-pattern-page identifies server-side review limits' );
npcink_abilities_toolkit_assert_same( 'pass', $pattern_page_review['data']['review_status'] ?? '', 'review-pattern-page passes the v3 native pattern blocks' );
npcink_abilities_toolkit_assert_true( (int) ( $pattern_page_review['data']['score'] ?? 0 ) >= 80, 'review-pattern-page scores the v3 native pattern above the pass threshold' );
npcink_abilities_toolkit_assert_same( 8, $pattern_page_review['data']['top_level_count'] ?? 0, 'review-pattern-page reports top-level sections' );
npcink_abilities_toolkit_assert_same( 101, $pattern_page_review['data']['block_count'] ?? 0, 'review-pattern-page reports recursive block count' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_review['data']['design_quality']['has_bento_grid'] ?? null, 'review-pattern-page carries Bento design quality' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_review['data']['design_quality']['has_hero_media'] ?? null, 'review-pattern-page carries hero media design quality' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_review['data']['design_quality']['has_hero_media_attachment_id'] ?? null, 'review-pattern-page carries hero media attachment binding quality' );
npcink_abilities_toolkit_assert_true( (int) ( $pattern_page_review['data']['design_quality']['section_shape_variety'] ?? 0 ) >= 5, 'review-pattern-page reports section shape variety' );
npcink_abilities_toolkit_assert_true( (int) ( $pattern_page_review['data']['design_quality']['native_style_density'] ?? 0 ) >= 40, 'review-pattern-page reports native style density' );
npcink_abilities_toolkit_assert_same( 'low', $pattern_page_review['data']['responsive_quality']['responsive_risk_level'] ?? '', 'review-pattern-page reports low responsive risk for stacked native columns' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_review['data']['media_quality']['image_alt_complete'] ?? null, 'review-pattern-page reports complete image alt text' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_review['data']['media_quality']['has_hero_media_url'] ?? null, 'review-pattern-page reports local hero media URLs present' );
npcink_abilities_toolkit_assert_same( true, $pattern_page_review['data']['media_quality']['has_hero_media_attachment_id'] ?? null, 'review-pattern-page reports hero media attachment ids present' );
npcink_abilities_toolkit_assert_same( false, $pattern_page_review['data']['media_quality']['has_temporary_cloud_preview_url'] ?? true, 'review-pattern-page reports no temporary Cloud preview URLs' );
npcink_abilities_toolkit_assert_same( 'low', $pattern_page_review['data']['editor_risk']['invalid_block_risk_level'] ?? '', 'review-pattern-page reports low server-observable invalid block risk' );
npcink_abilities_toolkit_assert_same( 8, $pattern_page_review['data']['layout_fingerprint']['section_count'] ?? 0, 'review-pattern-page reports layout fingerprint section count' );
npcink_abilities_toolkit_assert_same( 'center', $pattern_page_review['data']['layout_fingerprint']['comparison_title_alignment'] ?? '', 'review-pattern-page reports centered comparison title alignment' );
npcink_abilities_toolkit_assert_true( in_array( 'center', $pattern_page_review['data']['layout_fingerprint']['alignment_mix'] ?? array(), true ), 'review-pattern-page reports centered alignment in the alignment mix' );
$pattern_page_review_finding_codes = array_map(
	static function ( $finding ) {
		return is_array( $finding ) ? (string) ( $finding['code'] ?? '' ) : '';
	},
	is_array( $pattern_page_review['data']['visual_quality_findings'] ?? null ) ? $pattern_page_review['data']['visual_quality_findings'] : array()
);
npcink_abilities_toolkit_assert_true( in_array( 'color_story_monochrome', $pattern_page_review_finding_codes, true ), 'review-pattern-page reports monochrome color stories as a non-blocking visual finding' );
npcink_abilities_toolkit_assert_true( in_array( 'preview_page_in_editor', $pattern_page_review['data']['next_actions'] ?? array(), true ), 'review-pattern-page recommends final editor preview after a pass' );
$color_revised_pattern_page_plan = $core_read_package->build_pattern_page_plan(
	array(
		'title'              => 'Color Revised WordPress AI',
		'pattern_id'         => 'openai-style-landing',
		'style_preset'       => 'minimal-dark-light',
		'responsive_profile' => 'landing_standard',
		'visual_density'     => 'balanced',
		'media_strategy'     => 'existing_media_url',
		'review_feedback'    => $pattern_page_review['data'],
		'variables'          => array(
			'hero_title'     => 'Color-revised Gutenberg landing page',
			'hero_media_url' => 'https://magick-ai.local/wp-content/uploads/2026/06/color-revised-dashboard.jpg',
			'hero_media_alt' => 'Color-revised WordPress AI dashboard preview',
		),
	)
);
npcink_abilities_toolkit_assert_same( true, $color_revised_pattern_page_plan['success'] ?? null, 'build-pattern-page-plan accepts monochrome review feedback for color revision' );
npcink_abilities_toolkit_assert_same( 'editorial-accent', $color_revised_pattern_page_plan['data']['color_story'] ?? '', 'build-pattern-page-plan upgrades monochrome feedback to the editorial accent story when no color story is explicit' );
npcink_abilities_toolkit_assert_same( true, $color_revised_pattern_page_plan['data']['design_quality']['has_editorial_accent'] ?? null, 'build-pattern-page-plan reports editorial accents after monochrome feedback revision' );
$color_revised_goals = array_map(
	static function ( $goal ) {
		return is_array( $goal ) ? (string) ( $goal['goal'] ?? '' ) : '';
	},
	is_array( $color_revised_pattern_page_plan['data']['quality_feedback']['revision_goals'] ?? null ) ? $color_revised_pattern_page_plan['data']['quality_feedback']['revision_goals'] : array()
);
npcink_abilities_toolkit_assert_true( in_array( 'increase_color_rhythm', $color_revised_goals, true ), 'build-pattern-page-plan converts monochrome findings into a bounded color rhythm revision goal' );
npcink_abilities_toolkit_assert_true( in_array( 'color_story_monochrome', $color_revised_pattern_page_plan['data']['revision_strategy']['applied_finding_codes'] ?? array(), true ), 'build-pattern-page-plan reports the monochrome finding as addressed by the accent revision' );
npcink_abilities_toolkit_assert_true( ! in_array( 'color_story_monochrome', $color_revised_pattern_page_plan['data']['revision_strategy']['current_finding_codes'] ?? array(), true ), 'build-pattern-page-plan removes the monochrome finding from current review after accent revision' );
$block_surface_blocks_review = $core_read_package->review_block_editor_surface(
	array(
		'surface_kind' => 'blocks_input',
		'blocks'       => $pattern_blocks,
	)
);
npcink_abilities_toolkit_assert_same( true, $block_surface_blocks_review['success'] ?? null, 'review-block-editor-surface reviews proposed blocks before write' );
npcink_abilities_toolkit_assert_same( 'blocks_input', $block_surface_blocks_review['data']['block_editor_surface']['surface_kind'] ?? '', 'review-block-editor-surface identifies proposed block input' );
npcink_abilities_toolkit_assert_same( 'review_blocks_input', $block_surface_blocks_review['data']['block_editor_surface']['target_mode'] ?? '', 'review-block-editor-surface reports proposed block review mode' );
npcink_abilities_toolkit_assert_same( 'pass', $block_surface_blocks_review['data']['review_status'] ?? '', 'review-block-editor-surface reuses quality review for proposed blocks' );
npcink_abilities_toolkit_assert_true( in_array( 'choose_target_block_editor_surface', $block_surface_blocks_review['data']['next_actions'] ?? array(), true ), 'review-block-editor-surface asks callers to choose a target surface for proposed blocks' );
$paragraph_only_surface_review = $core_read_package->review_block_editor_surface(
	array(
		'surface_kind' => 'blocks_input',
		'blocks'       => array(
			array(
				'blockName'    => 'core/paragraph',
				'attrs'        => array(),
				'innerBlocks'  => array(),
				'innerHTML'    => '<p>Adapter verification paragraph.</p>',
				'innerContent' => array( '<p>Adapter verification paragraph.</p>' ),
			),
		),
	)
);
$paragraph_only_finding_codes = array_map(
	static function ( $finding ) {
		return is_array( $finding ) ? (string) ( $finding['code'] ?? '' ) : '';
	},
	is_array( $paragraph_only_surface_review['data']['findings'] ?? null ) ? $paragraph_only_surface_review['data']['findings'] : array()
);
npcink_abilities_toolkit_assert_same( false, $paragraph_only_surface_review['data']['design_quality']['has_split_hero'] ?? null, 'review-block-editor-surface detects missing split hero in paragraph-only blocks' );
npcink_abilities_toolkit_assert_true( ! in_array( 'split_hero_present', $paragraph_only_finding_codes, true ), 'review-block-editor-surface does not report split hero pass finding when no split hero exists' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][280977] = (object) array(
	'ID'           => 280977,
	'post_type'    => 'page',
	'post_status'  => 'draft',
	'post_title'   => 'Surface Review Page',
	'post_content' => '<!-- wp:heading --><h2>Surface review</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Review this page through the generic block editor surface ability.</p><!-- /wp:paragraph -->',
);
$page_surface_review = $core_read_package->review_block_editor_surface(
	array(
		'post_id' => 280977,
	)
);
npcink_abilities_toolkit_assert_same( true, $page_surface_review['success'] ?? null, 'review-block-editor-surface reviews pages by post id' );
npcink_abilities_toolkit_assert_same( 'post_content', $page_surface_review['data']['block_editor_surface']['surface_kind'] ?? '', 'review-block-editor-surface identifies post content surfaces' );
npcink_abilities_toolkit_assert_same( 'page', $page_surface_review['data']['block_editor_surface']['post_type'] ?? '', 'review-block-editor-surface reports the page post type' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/get-post-blocks', $page_surface_review['data']['block_editor_surface']['read_ability_id'] ?? '', 'review-block-editor-surface points post content reads at get-post-blocks' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/build-pattern-page-plan', $page_surface_review['data']['block_editor_surface']['plan_ability_id'] ?? '', 'review-block-editor-surface points page revisions at the page Pattern planner' );
npcink_abilities_toolkit_assert_same( false, $page_surface_review['data']['direct_wordpress_write'] ?? null, 'review-block-editor-surface does not write pages' );
npcink_abilities_toolkit_assert_true( in_array( 'revise_pattern_page_plan', $page_surface_review['data']['next_actions'] ?? array(), true ), 'review-block-editor-surface recommends the page Pattern revision loop for page surfaces' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][280978] = (object) array(
	'ID'           => 280978,
	'post_type'    => 'post',
	'post_status'  => 'draft',
	'post_title'   => 'Surface Review Article',
	'post_content' => '<!-- wp:heading --><h2>Article surface review</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Review this article through the generic block editor surface ability.</p><!-- /wp:paragraph -->',
);
$article_surface_review = $core_read_package->review_block_editor_surface(
	array(
		'post_id' => 280978,
	)
);
npcink_abilities_toolkit_assert_same( true, $article_surface_review['success'] ?? null, 'review-block-editor-surface reviews posts by post id' );
npcink_abilities_toolkit_assert_same( 'post', $article_surface_review['data']['block_editor_surface']['post_type'] ?? '', 'review-block-editor-surface reports the article post type' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/build-article-block-plan', $article_surface_review['data']['block_editor_surface']['plan_ability_id'] ?? '', 'review-block-editor-surface points article revisions at the article block planner' );
npcink_abilities_toolkit_assert_true( in_array( 'revise_article_block_plan', $article_surface_review['data']['next_actions'] ?? array(), true ), 'review-block-editor-surface recommends the article block revision loop for post surfaces' );
$custom_html_pattern_review = $core_read_package->review_pattern_page(
	array(
		'blocks' => array(
			array(
				'blockName'    => 'core/html',
				'attrs'        => array(),
				'innerHTML'    => '<div style="display:grid">Unsafe custom section</div>',
				'innerContent' => array( '<div style="display:grid">Unsafe custom section</div>' ),
				'innerBlocks'  => array(),
			),
		),
	)
);
npcink_abilities_toolkit_assert_same( 'needs_revision', $custom_html_pattern_review['data']['review_status'] ?? '', 'review-pattern-page flags custom HTML-only patterns for revision' );
npcink_abilities_toolkit_assert_same( 'medium', $custom_html_pattern_review['data']['editor_risk']['invalid_block_risk_level'] ?? '', 'review-pattern-page reports custom HTML as medium editor risk' );
npcink_abilities_toolkit_assert_true( in_array( 'revise_pattern_page_plan', $custom_html_pattern_review['data']['next_actions'] ?? array(), true ), 'review-pattern-page recommends revising risky pattern plans' );
$review_revised_pattern_page_plan = $core_read_package->build_pattern_page_plan(
	array(
		'title'              => 'Revised WordPress AI',
		'pattern_id'         => 'openai-style-landing',
		'style_preset'       => 'minimal-dark-light',
		'responsive_profile' => 'landing_standard',
		'visual_density'     => 'balanced',
		'media_strategy'     => 'existing_media_url',
		'review_feedback'    => $custom_html_pattern_review['data'],
		'variables'          => array(
			'eyebrow'          => 'WordPress AI Plugin',
			'hero_title'       => 'Review-revised Gutenberg landing page',
			'hero_description' => 'Previous review findings are converted into bounded Pattern revision goals.',
			'hero_media_url'   => 'https://magick-ai.local/wp-content/uploads/2026/06/review-revised-dashboard.jpg',
			'hero_media_alt'   => 'Review-revised WordPress AI dashboard preview',
		),
	)
);
npcink_abilities_toolkit_assert_same( true, $review_revised_pattern_page_plan['success'] ?? null, 'build-pattern-page-plan accepts review feedback from a previous Pattern review' );
npcink_abilities_toolkit_assert_same( true, $review_revised_pattern_page_plan['data']['quality_feedback']['feedback_received'] ?? null, 'build-pattern-page-plan reports received review feedback' );
npcink_abilities_toolkit_assert_same( 'needs_revision', $review_revised_pattern_page_plan['data']['quality_feedback']['source_review_status'] ?? '', 'build-pattern-page-plan preserves previous review status in feedback summary' );
npcink_abilities_toolkit_assert_true( in_array( 'editor_invalid_block_risk', $review_revised_pattern_page_plan['data']['quality_feedback']['finding_codes'] ?? array(), true ), 'build-pattern-page-plan carries previous editor-risk finding code' );
$review_revised_goals = array_map(
	static function ( $goal ) {
		return is_array( $goal ) ? (string) ( $goal['goal'] ?? '' ) : '';
	},
	is_array( $review_revised_pattern_page_plan['data']['quality_feedback']['revision_goals'] ?? null ) ? $review_revised_pattern_page_plan['data']['quality_feedback']['revision_goals'] : array()
);
npcink_abilities_toolkit_assert_true( in_array( 'avoid_invalid_blocks', $review_revised_goals, true ), 'build-pattern-page-plan converts editor-risk feedback into a bounded revision goal' );
npcink_abilities_toolkit_assert_same( 'pass', $review_revised_pattern_page_plan['data']['quality_review']['review_status'] ?? '', 'build-pattern-page-plan self-review passes after applying Pattern revision defaults' );
npcink_abilities_toolkit_assert_true( in_array( 'editor_invalid_block_risk', $review_revised_pattern_page_plan['data']['revision_strategy']['applied_finding_codes'] ?? array(), true ), 'build-pattern-page-plan reports previous editor-risk finding as addressed by the new plan' );
npcink_abilities_toolkit_assert_true( in_array( 'native_style_density_low', $review_revised_pattern_page_plan['data']['revision_strategy']['applied_finding_codes'] ?? array(), true ), 'build-pattern-page-plan reports previous native-style finding as addressed by the new plan' );
npcink_abilities_toolkit_assert_same( array(), $review_revised_pattern_page_plan['data']['revision_strategy']['remaining_finding_codes'] ?? array( 'unexpected' ), 'build-pattern-page-plan reports no remaining previous findings after a high-quality revision' );
npcink_abilities_toolkit_assert_same( true, $review_revised_pattern_page_plan['data']['revision_strategy']['ready_for_proposal'] ?? null, 'build-pattern-page-plan marks review-revised plans ready for Core proposal handoff' );
$review_revised_actions = is_array( $review_revised_pattern_page_plan['data']['write_actions'] ?? null ) ? $review_revised_pattern_page_plan['data']['write_actions'] : array();
$review_revised_blocks = is_array( $review_revised_actions[1]['input']['blocks'] ?? null ) ? $review_revised_actions[1]['input']['blocks'] : array();
$review_revised_markup = wp_json_encode( $review_revised_blocks );
npcink_abilities_toolkit_assert_same( '4.0', $review_revised_pattern_page_plan['data']['design_quality']['pattern_version'] ?? '', 'build-pattern-page-plan upgrades feedback revisions to the v4 visual-delta Pattern quality version' );
npcink_abilities_toolkit_assert_same( 'strong_revision', $review_revised_pattern_page_plan['data']['design_quality']['visual_delta_mode'] ?? '', 'build-pattern-page-plan marks feedback revisions as strong visual revisions' );
npcink_abilities_toolkit_assert_same( true, $review_revised_pattern_page_plan['data']['design_quality']['has_visual_delta_section'] ?? null, 'build-pattern-page-plan reports the visual-delta feature section' );
npcink_abilities_toolkit_assert_same( true, $review_revised_pattern_page_plan['data']['design_quality']['has_feature_proof_row'] ?? null, 'build-pattern-page-plan reports the added feature proof row' );
npcink_abilities_toolkit_assert_true( is_string( $review_revised_markup ) && false !== strpos( $review_revised_markup, 'npcink-ai-feature-grid npcink-ai-visual-delta' ), 'build-pattern-page-plan serializes the feedback revision as a visibly different feature section' );
npcink_abilities_toolkit_assert_true( is_string( $review_revised_markup ) && false !== strpos( $review_revised_markup, 'background-color:#111111;padding-top:88px;padding-right:40px;padding-bottom:88px;padding-left:40px' ), 'build-pattern-page-plan renders the visual-delta feature section as a native dark band' );
npcink_abilities_toolkit_assert_true( is_string( $review_revised_markup ) && false !== strpos( $review_revised_markup, 'npcink-ai-feature-proof-row' ), 'build-pattern-page-plan adds a native proof row to make feedback revisions visually distinct' );
npcink_abilities_toolkit_assert_true( is_string( $review_revised_markup ) && false !== strpos( $review_revised_markup, 'background-color:#ffffff;color:#111111' ), 'build-pattern-page-plan resets light visual-delta card text color instead of inheriting dark-section text' );
npcink_abilities_toolkit_assert_true( is_string( $review_revised_markup ) && false !== strpos( $review_revised_markup, 'border-color:#3a3a3a;border-width:1px;border-radius:20px;background-color:#1f1f1f;color:#ffffff;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px' ), 'build-pattern-page-plan gives proof row cards complete native padding' );
$research_backed_pattern_page_plan = $core_read_package->build_pattern_page_plan(
	array(
		'title'              => 'Research Backed WordPress AI',
		'pattern_id'         => 'openai-style-landing',
		'style_preset'       => 'minimal-dark-light',
		'responsive_profile' => 'landing_standard',
		'visual_density'     => 'balanced',
		'media_strategy'     => 'existing_media_url',
		'research_brief'     => array(
			'artifact_type'                 => 'landing_page_research_brief',
			'write_posture'                 => 'suggestion_only',
			'direct_wordpress_write'        => false,
			'source_count'                  => 3,
			'section_patterns'              => array(
				array(
					'title'       => 'Evidence-led hero',
					'description' => 'Open with product proof and reviewed workflow context before detailed feature cards.',
				),
			),
			'visual_asset_recommendations' => array(
				array(
					'title'       => 'Proposal dashboard visual',
					'description' => 'Use a reviewed product interface image that shows approval status and block validation.',
				),
			),
			'proof_points'                  => array(
				array(
					'title'       => 'Reference-backed proof',
					'description' => 'Show why reviewable drafts matter before asking visitors to compare features.',
				),
			),
			'comparison_angles'             => array(
				array(
					'title'       => 'Direct automation',
					'description' => 'Fast, but hard to audit when writes skip proposal review.',
				),
				array(
					'title'       => 'Proposal-first pages',
					'description' => 'Keeps final WordPress changes reviewable, reversible, and traceable.',
				),
			),
			'faq_seed_questions'            => array(
				array(
					'question' => 'Can the page use external research safely?',
					'answer'   => 'Yes, when references are summarized as evidence and not copied into the page.',
				),
			),
		),
		'variables'          => array(
			'eyebrow'          => 'WordPress AI Plugin',
			'hero_title'       => 'Research-backed Gutenberg landing page',
			'hero_description' => 'Cloud search evidence shapes the brief while Toolkit keeps Gutenberg output native.',
			'hero_media_url'   => 'https://magick-ai.local/wp-content/uploads/2026/06/research-backed-dashboard.jpg',
			'hero_media_alt'   => 'Research-backed WordPress AI dashboard preview',
		),
	)
);
npcink_abilities_toolkit_assert_same( true, $research_backed_pattern_page_plan['success'] ?? null, 'build-pattern-page-plan accepts a reviewed landing page research brief' );
npcink_abilities_toolkit_assert_same( true, $research_backed_pattern_page_plan['data']['research_brief']['research_backed'] ?? null, 'build-pattern-page-plan reports research-backed page planning' );
npcink_abilities_toolkit_assert_same( 3, $research_backed_pattern_page_plan['data']['research_brief']['source_count'] ?? 0, 'build-pattern-page-plan preserves compact research source count' );
npcink_abilities_toolkit_assert_same( 2, $research_backed_pattern_page_plan['data']['research_brief']['comparison_angle_count'] ?? 0, 'build-pattern-page-plan counts comparison angles from research brief' );
npcink_abilities_toolkit_assert_same( false, $research_backed_pattern_page_plan['data']['research_brief']['reference_copying_allowed'] ?? true, 'build-pattern-page-plan keeps reference copying disabled' );
npcink_abilities_toolkit_assert_same( true, $research_backed_pattern_page_plan['data']['design_quality']['research_backed'] ?? null, 'build-pattern-page-plan design quality marks research-backed output' );
npcink_abilities_toolkit_assert_same( true, $research_backed_pattern_page_plan['data']['design_quality']['has_comparison_section'] ?? null, 'build-pattern-page-plan adds a comparison section from research angles' );
npcink_abilities_toolkit_assert_same( 8, $research_backed_pattern_page_plan['data']['design_quality']['section_count'] ?? 0, 'build-pattern-page-plan adds one research-backed comparison section when media is supplied' );
$research_backed_actions = is_array( $research_backed_pattern_page_plan['data']['write_actions'] ?? null ) ? $research_backed_pattern_page_plan['data']['write_actions'] : array();
$research_backed_blocks = is_array( $research_backed_actions[1]['input']['blocks'] ?? null ) ? $research_backed_actions[1]['input']['blocks'] : array();
$research_backed_markup = wp_json_encode( $research_backed_blocks );
npcink_abilities_toolkit_assert_true( is_string( $research_backed_markup ) && false !== strpos( $research_backed_markup, 'npcink-ai-comparison' ), 'build-pattern-page-plan serializes research-backed comparison section blocks' );
npcink_abilities_toolkit_assert_true( is_string( $research_backed_markup ) && false !== strpos( $research_backed_markup, 'Reference-backed proof' ), 'build-pattern-page-plan uses research proof points' );
npcink_abilities_toolkit_assert_true( is_string( $research_backed_markup ) && false !== strpos( $research_backed_markup, 'Proposal dashboard visual' ), 'build-pattern-page-plan uses visual asset recommendations for media copy' );
npcink_abilities_toolkit_assert_true( is_string( $research_backed_markup ) && false !== strpos( $research_backed_markup, 'Can the page use external research safely?' ), 'build-pattern-page-plan uses FAQ seeds from research brief' );
$apply_result = $core_read_package->compose_article_optimization_apply_result(
	array(
		'report'        => array(
			'summary' => array(
				'status' => 'needs_attention',
			),
		),
		'apply_plan'    => $apply_plan['data'],
		'apply_excerpt' => array(
			'updated' => true,
			'post_id' => 77,
			'changes' => array(
				'excerpt' => 'Generated excerpt.',
			),
		),
	)
);
npcink_abilities_toolkit_assert_same( true, $apply_result['success'] ?? null, 'compose-article-optimization-apply-result returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'partial_apply', $apply_result['data']['summary']['result_mode'] ?? '', 'compose-article-optimization-apply-result marks partial apply when excerpt changed' );
npcink_abilities_toolkit_assert_same( 1, $apply_result['data']['summary']['applied_count'] ?? null, 'compose-article-optimization-apply-result counts applied changes' );
$draft_result = $core_read_package->compose_article_draft_result(
	array(
		'input' => array(
			'topic'            => 'Local draft',
			'preview_only'     => true,
			'platform_profile' => 'wechat',
			'human_signals'    => array( '案例/数据来源：内部复盘记录' ),
		),
		'draft' => array(
			'post_id'      => 0,
			'preview_link' => 'https://example.test/preview/draft',
		),
		'generated_seo' => array(
			'title'       => 'Local draft SEO title',
			'description' => 'Local draft SEO description',
		),
		'metadata_plan_resolution' => array(
			'slug' => 'local-draft',
		),
		'seo_analysis' => array(
			'overall_score' => 72,
		),
		'geo_analysis' => array(
			'geo_score' => 82,
		),
		'quality_scoring' => array(
			'overall_score' => 58,
			'improvement_suggestions' => array( '补充更具体的真实案例。' ),
		),
		'review' => array(
			'needs_human_review'  => true,
			'next_action'         => 'needs_human_review',
			'template_risk_level' => 'medium',
		),
	)
);
$draft_data = is_array( $draft_result['data'] ?? null ) ? $draft_result['data'] : array();
npcink_abilities_toolkit_assert_same( true, $draft_result['success'] ?? null, 'compose-article-draft-result returns a success envelope' );
npcink_abilities_toolkit_assert_same( true, $draft_data['draft']['preview_only'] ?? null, 'compose-article-draft-result preserves preview-only mode' );
npcink_abilities_toolkit_assert_same( false, $draft_data['draft']['real_draft_created'] ?? null, 'compose-article-draft-result does not claim a real draft in preview mode' );
npcink_abilities_toolkit_assert_same( 'review_preview', $draft_data['handoff']['next_action'] ?? '', 'compose-article-draft-result keeps preview handoff local to draft workflow' );
npcink_abilities_toolkit_assert_same( 'npcink-abilities-toolkit/recipes/article-draft', $draft_data['handoff']['recommended_entry'] ?? '', 'compose-article-draft-result keeps draft recommended entry for preview-only output' );
npcink_abilities_toolkit_assert_same( '内部复盘记录', $draft_data['source_references'][0] ?? '', 'compose-article-draft-result extracts source references from human signals' );
$publication_decision = $core_read_package->resolve_article_publication_decision(
	array(
		'publish_mode' => 'schedule',
		'review'       => array( 'needs_human_review' => true ),
	)
);
npcink_abilities_toolkit_assert_same( true, $publication_decision['success'] ?? null, 'resolve-article-publication-decision returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'schedule', $publication_decision['data']['requested_publish_mode'] ?? '', 'resolve-article-publication-decision preserves requested mode' );
npcink_abilities_toolkit_assert_same( 'review', $publication_decision['data']['effective_publish_mode'] ?? '', 'resolve-article-publication-decision routes blocked schedules to review' );
npcink_abilities_toolkit_assert_same( true, $publication_decision['data']['publish_blocked'] ?? null, 'resolve-article-publication-decision marks human-review gate as blocked' );
npcink_abilities_toolkit_assert_same( 'quality_review_requires_handoff', $publication_decision['data']['gate_reason'] ?? '', 'resolve-article-publication-decision records quality gate reason' );
$template_publication_decision = $core_read_package->resolve_article_publication_decision(
	array(
		'publish_mode' => 'publish',
		'review'       => array(
			'needs_human_review' => true,
			'template_risk_level' => 'high',
		),
	)
);
npcink_abilities_toolkit_assert_same( 'template_style_requires_handoff', $template_publication_decision['data']['gate_reason'] ?? '', 'resolve-article-publication-decision records high template risk gate reason' );
$duplicate_publication_decision = $core_read_package->resolve_article_publication_decision(
	array(
		'publish_mode'     => 'publish',
		'duplicate_guard'  => array( 'skip_recommended' => true ),
	)
);
npcink_abilities_toolkit_assert_same( 'duplicate_production_candidate', $duplicate_publication_decision['data']['gate_reason'] ?? '', 'resolve-article-publication-decision records duplicate gate reason' );
$draft_publication_decision = $core_read_package->resolve_article_publication_decision( array( 'publish_mode' => 'unexpected' ) );
npcink_abilities_toolkit_assert_same( 'draft', $draft_publication_decision['data']['requested_publish_mode'] ?? '', 'resolve-article-publication-decision falls back invalid mode to draft' );
npcink_abilities_toolkit_assert_same( false, $draft_publication_decision['data']['publish_blocked'] ?? null, 'resolve-article-publication-decision leaves draft unblocked' );
$article_style_profile = $core_read_package->build_article_style_profile(
	array(
		'reference_profile' => array(
			'dominant_voice_profile'   => 'experiential_editorial',
			'dominant_opening_style'   => 'scene',
			'structure_style'          => 'reference-first',
			'style_brief'              => 'Reference article favors lived experience.',
		),
		'baseline_profile' => array(
			'dominant_voice_profile'   => 'practical_editorial',
			'dominant_opening_style'   => 'direct_judgement',
			'structure_style'          => 'baseline-short-paragraphs',
			'style_brief'              => 'Site baseline favors practical judgement.',
		),
		'voice_profile'     => 'practical_editorial',
		'opening_style'     => 'scene',
		'structure_style'   => 'alternating paragraph lengths',
	)
);
npcink_abilities_toolkit_assert_same( true, $article_style_profile['success'] ?? null, 'build-article-style-profile returns a success envelope' );
npcink_abilities_toolkit_assert_same( 'practical_editorial', $article_style_profile['data']['profile']['resolved_voice_profile'] ?? '', 'build-article-style-profile preserves explicit voice override' );
npcink_abilities_toolkit_assert_same( 'scene', $article_style_profile['data']['profile']['resolved_opening_style'] ?? '', 'build-article-style-profile preserves explicit opening override' );
npcink_abilities_toolkit_assert_same( 'alternating paragraph lengths', $article_style_profile['data']['profile']['resolved_structure_style'] ?? '', 'build-article-style-profile preserves explicit structure override' );
npcink_abilities_toolkit_assert_true( false !== strpos( (string) ( $article_style_profile['data']['profile']['style_brief'] ?? '' ), 'Reference article favors lived experience.' ), 'build-article-style-profile carries reference brief' );
npcink_abilities_toolkit_assert_true( false !== strpos( (string) ( $article_style_profile['data']['profile']['style_brief'] ?? '' ), 'Site baseline favors practical judgement.' ), 'build-article-style-profile carries baseline brief' );
$reference_first_style_profile = $core_read_package->build_article_style_profile(
	array(
		'reference_profile' => array(
			'dominant_voice_profile' => 'reference_voice',
			'dominant_opening_style' => 'reference_opening',
			'style_brief' => 'Repeated brief.',
		),
		'baseline_profile' => array(
			'dominant_voice_profile' => 'baseline_voice',
			'dominant_opening_style' => 'baseline_opening',
			'style_brief' => 'Repeated brief.',
		),
	)
);
npcink_abilities_toolkit_assert_same( 'reference_voice', $reference_first_style_profile['data']['profile']['resolved_voice_profile'] ?? '', 'build-article-style-profile prefers reference voice over baseline when no explicit override exists' );
npcink_abilities_toolkit_assert_same( 'reference_opening', $reference_first_style_profile['data']['profile']['resolved_opening_style'] ?? '', 'build-article-style-profile prefers reference opening over baseline when no explicit override exists' );
npcink_abilities_toolkit_assert_same( 'Repeated brief.', $reference_first_style_profile['data']['profile']['style_brief'] ?? '', 'build-article-style-profile deduplicates repeated style briefs' );
$package_bridge = new Npcink_Catalog_Bridge( $package_registrar );
$package_catalog = $package_bridge->filter_catalog( array(), array() );
foreach ( $migrated_read_ability_ids as $migrated_ability_id ) {
	$catalog_key = str_replace( '/', '_', $migrated_ability_id );
	npcink_abilities_toolkit_assert_true( isset( $package_catalog[ $catalog_key ] ), "catalog bridge projects migrated {$migrated_ability_id}" );
	npcink_abilities_toolkit_assert_same( 'wp_ability', $package_catalog[ $catalog_key ]['executor_type'], "{$migrated_ability_id} catalog entry executes through wp_ability" );
	npcink_abilities_toolkit_assert_true( ! isset( $package_catalog[ $catalog_key ]['open_api_enabled'] ), "{$migrated_ability_id} catalog projection does not own Open API policy" );
}
foreach ( $migrated_write_ability_ids as $migrated_write_ability_id ) {
	$catalog_key = str_replace( '/', '_', $migrated_write_ability_id );
	npcink_abilities_toolkit_assert_true( isset( $package_catalog[ $catalog_key ] ), "catalog bridge projects migrated {$migrated_write_ability_id}" );
	npcink_abilities_toolkit_assert_same( 'wp_ability', $package_catalog[ $catalog_key ]['executor_type'], "{$migrated_write_ability_id} catalog entry executes through wp_ability" );
	npcink_abilities_toolkit_assert_same( true, $package_catalog[ $catalog_key ]['requires_confirm'], "{$migrated_write_ability_id} catalog projection requires confirmation" );
	npcink_abilities_toolkit_assert_same( 'write', $package_catalog[ $catalog_key ]['risk_level'], "{$migrated_write_ability_id} catalog projection is write risk" );
	npcink_abilities_toolkit_assert_same( true, $package_catalog[ $catalog_key ]['show_in_rest'], "{$migrated_write_ability_id} catalog projection exposes show_in_rest for host normalization" );
	npcink_abilities_toolkit_assert_true( ! isset( $package_catalog[ $catalog_key ]['write_mode'] ), "{$migrated_write_ability_id} catalog projection does not own write mode policy" );
	npcink_abilities_toolkit_assert_true( ! isset( $package_catalog[ $catalog_key ]['open_api_enabled'] ), "{$migrated_write_ability_id} catalog projection does not own Open API policy" );
	npcink_abilities_toolkit_assert_true( ! isset( $package_catalog[ $catalog_key ]['skip_catalog_manifest_fallback'] ), "{$migrated_write_ability_id} catalog projection does not own host fallback policy" );
}
foreach ( $migrated_destructive_ability_ids as $migrated_destructive_ability_id ) {
	$catalog_key = str_replace( '/', '_', $migrated_destructive_ability_id );
	npcink_abilities_toolkit_assert_true( isset( $package_catalog[ $catalog_key ] ), "catalog bridge projects migrated {$migrated_destructive_ability_id}" );
	npcink_abilities_toolkit_assert_same( 'wp_ability', $package_catalog[ $catalog_key ]['executor_type'], "{$migrated_destructive_ability_id} catalog entry executes through wp_ability" );
	npcink_abilities_toolkit_assert_same( true, $package_catalog[ $catalog_key ]['requires_confirm'], "{$migrated_destructive_ability_id} catalog projection requires confirmation" );
	npcink_abilities_toolkit_assert_same( 'destructive', $package_catalog[ $catalog_key ]['risk_level'], "{$migrated_destructive_ability_id} catalog projection is destructive risk" );
	npcink_abilities_toolkit_assert_true( ! isset( $package_catalog[ $catalog_key ]['write_mode'] ), "{$migrated_destructive_ability_id} catalog projection does not own write mode policy" );
	npcink_abilities_toolkit_assert_true( ! isset( $package_catalog[ $catalog_key ]['tool_policy'] ), "{$migrated_destructive_ability_id} catalog projection does not own destructive tool policy" );
	npcink_abilities_toolkit_assert_true( ! isset( $package_catalog[ $catalog_key ]['open_api_enabled'] ), "{$migrated_destructive_ability_id} catalog projection does not own Open API policy" );
	npcink_abilities_toolkit_assert_true( ! isset( $package_catalog[ $catalog_key ]['skip_catalog_manifest_fallback'] ), "{$migrated_destructive_ability_id} catalog projection does not own host fallback policy" );
}
npcink_abilities_toolkit_assert_true( ! isset( $package_catalog['npcink-abilities-toolkit_wp-diagnostics-summary'] ), 'catalog bridge does not project standalone diagnostics ability' );
npcink_abilities_toolkit_assert_true( ! isset( $package_catalog['npcink-abilities-toolkit_wp-ops-diagnostics-detail'] ), 'catalog bridge does not project standalone ops diagnostics ability' );

$workflow_replay_path = __DIR__ . '/../fixtures/agent-workflow-replay.json';
$workflow_replay_json = file_get_contents( $workflow_replay_path );
npcink_abilities_toolkit_assert_true( false !== $workflow_replay_json, 'agent workflow replay fixture is readable' );
$workflow_replay = json_decode( (string) $workflow_replay_json, true );
npcink_abilities_toolkit_assert_true( is_array( $workflow_replay ), 'agent workflow replay fixture decodes as an object' );
$workflow_manifest = \Npcink_Abilities_Toolkit\Workflow\Workflow_Definition_Provider::manifest();
npcink_abilities_toolkit_assert_same( $workflow_manifest, $workflow_replay, 'agent workflow replay fixture matches production workflow definition provider' );
npcink_abilities_toolkit_assert_same( $workflow_manifest, npcink_abilities_toolkit_get_workflow_definitions(), 'public workflow definitions helper matches provider manifest' );
npcink_abilities_toolkit_assert_same( $workflow_manifest['cases']['article_publish_preflight'], npcink_abilities_toolkit_get_workflow_definition( 'npcink-abilities-toolkit/recipes/article-publish-preflight' ), 'public workflow definition helper resolves recipe id' );
npcink_abilities_toolkit_assert_same( 'v1', $workflow_replay['schema_version'] ?? '', 'agent workflow replay fixture schema is v1' );
npcink_abilities_toolkit_assert_true( is_array( $workflow_replay['cases'] ?? null ), 'agent workflow replay fixture exposes cases' );
$forbidden_workflow_definition_fields = \Npcink_Abilities_Toolkit\Workflow\Workflow_Definition_Provider::forbidden_field_keys();
npcink_abilities_toolkit_assert_array_omits_keys( $workflow_replay, $forbidden_workflow_definition_fields, 'agent workflow replay fixture' );
$expected_workflow_replay_cases = array(
	'article_draft'                  => array(
		'ability_id'         => 'npcink-abilities-toolkit/compose-article-draft-result',
		'recipe_id'          => 'npcink-abilities-toolkit/recipes/article-draft',
		'recipe_aliases'     => array( 'article_draft_v1' ),
		'required_scope'     => 'cap.text.extract',
		'required_inputs'    => array(),
		'expected_sections'  => array( 'article', 'draft', 'metadata_plan_resolution', 'review', 'handoff' ),
		'expanded_abilities' => array(
			'npcink-abilities-toolkit/resolve-post-metadata-plan',
			'npcink-abilities-toolkit/resolve-internal-link-targets',
			'npcink-abilities-toolkit/build-inline-image-blocks',
			'npcink-abilities-toolkit/build-media-seo-assets',
			'npcink-abilities-toolkit/review-article-output-light',
			'npcink-abilities-toolkit/compose-article-draft-result',
		),
		'handoff_kind'       => 'suggestion',
		'disallowed_default' => array( 'npcink-abilities-toolkit/create-draft', 'npcink-abilities-toolkit/update-post', 'npcink-abilities-toolkit/patch-post-content', 'npcink-abilities-toolkit/publish-post' ),
	),
	'article_publish_preflight'      => array(
		'ability_id'         => 'npcink-abilities-toolkit/get-article-publish-preflight-context',
		'recipe_id'          => 'npcink-abilities-toolkit/recipes/article-publish-preflight',
		'required_scope'     => 'post.read',
		'required_inputs'    => array( 'post_id' ),
		'expected_sections'  => array( 'post_context', 'publishing_checklist', 'publish_risk', 'workflow_context', 'publishing_calendar' ),
		'expanded_abilities' => array(
			'npcink-abilities-toolkit/get-post-context',
			'npcink-abilities-toolkit/get-content-publishing-checklist',
			'npcink-abilities-toolkit/get-post-publish-risk-report',
			'npcink-abilities-toolkit/build-article-workflow-context',
			'npcink-abilities-toolkit/get-publishing-calendar-context',
		),
		'handoff_kind'       => 'context',
		'disallowed_default' => array( 'npcink-abilities-toolkit/schedule-post', 'npcink-abilities-toolkit/publish-post' ),
	),
	'article_optimization'           => array(
		'ability_id'         => 'npcink-abilities-toolkit/read-post-optimization-context',
		'recipe_id'          => 'npcink-abilities-toolkit/recipes/article-optimization',
		'required_scope'     => 'post.read',
		'required_inputs'    => array( 'post_id' ),
		'expected_sections'  => array( 'post_context', 'seo_report', 'optimization_suggestion', 'apply_plan', 'handoff' ),
		'expanded_abilities' => array(
			'npcink-abilities-toolkit/read-post-optimization-context',
			'npcink-abilities-toolkit/seo-report-context',
			'npcink-abilities-toolkit/build-article-single-optimization-suggest',
			'npcink-abilities-toolkit/build-article-optimization-apply-plan',
			'npcink-abilities-toolkit/compose-article-optimization-apply-result',
		),
		'handoff_kind'       => 'suggestion',
		'disallowed_default' => array( 'npcink-abilities-toolkit/patch-post-content', 'npcink-abilities-toolkit/set-post-seo-meta', 'npcink-abilities-toolkit/update-post-blocks' ),
	),
	'media_optimization'             => array(
		'ability_id'         => 'npcink-abilities-toolkit/build-media-optimization-plan',
		'recipe_id'          => 'npcink-abilities-toolkit/recipes/media-optimization',
		'recipe_aliases'     => array( 'media_optimization_v1' ),
		'required_scope'     => 'media.read',
		'required_inputs'    => array( 'attachment_id', 'media_details_input', 'derivative_artifact' ),
		'expected_sections'  => array( 'artifact_type', 'proposal_mode', 'write_actions', 'derivative_preview', 'content_reference_repairs_preview' ),
		'expanded_abilities' => array( 'npcink-abilities-toolkit/build-media-optimization-plan' ),
		'handoff_kind'       => 'approval_request',
		'disallowed_default' => array( 'npcink-abilities-toolkit/update-media-details', 'npcink-abilities-toolkit/adopt-cloud-media-derivative' ),
	),
	'article_media_handoff'          => array(
		'ability_id'         => 'npcink-abilities-toolkit/build-media-seo-assets',
		'recipe_id'          => 'npcink-abilities-toolkit/recipes/article-media-handoff',
		'required_scope'     => 'media.read',
		'required_inputs'    => array(),
		'expected_sections'  => array( 'post_context', 'media_assets', 'inline_blocks', 'positioned_blocks', 'handoff' ),
		'expanded_abilities' => array(
			'npcink-abilities-toolkit/get-post-context',
			'npcink-abilities-toolkit/build-inline-image-blocks',
			'npcink-abilities-toolkit/build-media-seo-assets',
			'npcink-abilities-toolkit/position-inline-image-blocks',
		),
		'handoff_kind'       => 'suggestion',
		'disallowed_default' => array( 'npcink-abilities-toolkit/upload-media-from-url', 'npcink-abilities-toolkit/update-media-details', 'npcink-abilities-toolkit/set-post-featured-image' ),
	),
	'old_article_refresh_discovery' => array(
		'ability_id'         => 'npcink-abilities-toolkit/get-old-article-refresh-context',
		'recipe_id'          => 'npcink-abilities-toolkit/recipes/old-article-refresh-discovery',
		'required_scope'     => 'post.read',
		'required_inputs'    => array(),
		'expected_sections'  => array( 'refresh_opportunities', 'seo_geo_gap_report', 'site_style_baseline', 'internal_link_graph_health' ),
		'expanded_abilities' => array(
			'npcink-abilities-toolkit/get-content-refresh-opportunities',
			'npcink-abilities-toolkit/get-seo-geo-gap-report',
			'npcink-abilities-toolkit/get-site-style-baseline',
			'npcink-abilities-toolkit/get-internal-link-graph-health',
			'npcink-abilities-toolkit/get-internal-link-opportunity-report',
		),
		'handoff_kind'       => 'context',
		'disallowed_default' => array( 'npcink-abilities-toolkit/patch-post-content', 'npcink-abilities-toolkit/update-post', 'npcink-abilities-toolkit/update-post-blocks' ),
	),
	'article_production'            => array(
		'ability_id'         => 'npcink-abilities-toolkit/build-article-production-fingerprint',
		'recipe_id'          => 'npcink-abilities-toolkit/recipes/article-production',
		'required_scope'     => 'cap.text.generate',
		'required_inputs'    => array(),
		'expected_sections'  => array( 'production_fingerprint', 'duplicate_candidate', 'review', 'publication_decision', 'handoff' ),
		'expanded_abilities' => array(
			'npcink-abilities-toolkit/extract-style-baseline',
			'npcink-abilities-toolkit/build-article-production-fingerprint',
			'npcink-abilities-toolkit/check-article-production-duplicate',
			'npcink-abilities-toolkit/review-article-output-light',
			'npcink-abilities-toolkit/build-media-seo-assets',
			'npcink-abilities-toolkit/resolve-article-publication-decision',
			'npcink-abilities-toolkit/compose-article-production-result',
		),
		'handoff_kind'       => 'suggestion',
		'disallowed_default' => array( 'npcink-abilities-toolkit/publish-post', 'npcink-abilities-toolkit/schedule-post', 'npcink-abilities-toolkit/update-post', 'npcink-abilities-toolkit/patch-post-content' ),
	),
	'media_seo_enrichment'          => array(
		'ability_id'         => 'npcink-abilities-toolkit/get-media-inventory-health',
		'recipe_id'          => 'npcink-abilities-toolkit/recipes/media-seo-handoff',
		'required_scope'     => 'media.read',
		'required_inputs'    => array(),
		'expected_sections'  => array( 'media_inventory', 'inventory_health', 'media_seo_assets', 'metadata_suggestions' ),
		'expanded_abilities' => array(
			'npcink-abilities-toolkit/list-media',
			'npcink-abilities-toolkit/get-media-inventory-health',
			'npcink-abilities-toolkit/build-media-seo-assets',
			'npcink-abilities-toolkit/optimize-media-metadata',
		),
		'handoff_kind'       => 'suggestion',
		'disallowed_default' => array( 'npcink-abilities-toolkit/update-media-details' ),
	),
	'comment_compliance_handoff'    => array(
		'ability_id'         => 'npcink-abilities-toolkit/get-comment-compliance-handoff',
		'recipe_id'          => 'npcink-abilities-toolkit/recipes/comment-compliance-handoff',
		'required_scope'     => 'comments.manage',
		'required_inputs'    => array(),
		'expected_sections'  => array( 'queue_health', 'priority_queue', 'selected_moderation_suggestion' ),
		'expanded_abilities' => array(
			'npcink-abilities-toolkit/get-comment-queue-health',
			'npcink-abilities-toolkit/get-comment-action-priority-queue',
			'npcink-abilities-toolkit/build-comment-moderation-suggest',
			'npcink-abilities-toolkit/build-comment-mention-reply-suggest',
			'npcink-abilities-toolkit/compose-comment-moderation-result',
		),
		'handoff_kind'       => 'context',
		'disallowed_default' => array( 'npcink-abilities-toolkit/approve-comment', 'npcink-abilities-toolkit/reply-comment', 'npcink-abilities-toolkit/spam-comment', 'npcink-abilities-toolkit/trash-comment' ),
	),
	'media_governance_scan'         => array(
		'ability_id'         => 'npcink-abilities-toolkit/get-media-cleanup-opportunities',
		'recipe_id'          => 'npcink-abilities-toolkit/recipes/media-governance-scan',
		'required_scope'     => 'media.read',
		'required_inputs'    => array(),
		'expected_sections'  => array( 'cleanup_opportunities', 'inventory_fix_plan', 'rename_plan', 'derivative_batch_plan' ),
		'expanded_abilities' => array(
			'npcink-abilities-toolkit/get-media-cleanup-opportunities',
			'npcink-abilities-toolkit/build-media-inventory-fix-plan',
			'npcink-abilities-toolkit/build-media-rename-plan',
			'npcink-abilities-toolkit/build-media-derivative-batch-plan',
		),
		'handoff_kind'       => 'context',
		'disallowed_default' => array( 'npcink-abilities-toolkit/update-media-details', 'npcink-abilities-toolkit/delete-media-permanently' ),
	),
	'site_operations_scan'          => array(
		'ability_id'         => 'npcink-abilities-toolkit/get-site-operations-dashboard',
		'recipe_id'          => 'npcink-abilities-toolkit/recipes/site-operations-scan',
		'required_scope'     => 'post.read',
		'required_inputs'    => array(),
		'expected_sections'  => array( 'operations_dashboard', 'content_inventory', 'media_inventory', 'taxonomy_inventory', 'page_structure' ),
		'expanded_abilities' => array(
			'npcink-abilities-toolkit/site-info',
			'npcink-abilities-toolkit/get-site-operations-dashboard',
			'npcink-abilities-toolkit/get-content-inventory-health',
			'npcink-abilities-toolkit/get-media-inventory-health',
			'npcink-abilities-toolkit/get-taxonomy-inventory-health',
			'npcink-abilities-toolkit/get-page-structure-health',
		),
		'handoff_kind'       => 'context',
		'disallowed_default' => array( 'npcink-abilities-toolkit/patch-post-content', 'npcink-abilities-toolkit/update-media-details' ),
	),
	'diagnostics_triage'            => array(
		'ability_id'            => 'npcink-abilities-toolkit/wp-diagnostics-summary',
		'recipe_id'             => 'npcink-abilities-toolkit/recipes/diagnostics-triage',
		'required_scope'        => '',
		'required_inputs'       => array(),
		'expected_sections'     => array( 'environment_summary', 'ops_detail', 'site_context' ),
		'expanded_abilities'    => array(
			'npcink-abilities-toolkit/wp-diagnostics-summary',
			'npcink-abilities-toolkit/wp-ops-diagnostics-detail',
			'npcink-abilities-toolkit/site-info',
		),
		'handoff_kind'          => 'context',
		'disallowed_default'    => array( 'npcink-abilities-toolkit/create-draft' ),
		'local_only_entrypoint' => true,
	),
);
npcink_abilities_toolkit_assert_same( array_keys( $expected_workflow_replay_cases ), array_keys( $workflow_replay['cases'] ), 'agent workflow replay fixture keeps the approved local recipe cases in order' );
foreach ( $expected_workflow_replay_cases as $case_id => $expected_case ) {
	$case = $workflow_replay['cases'][ $case_id ] ?? array();
	npcink_abilities_toolkit_assert_true( is_array( $case ), "agent workflow replay case {$case_id} is an object" );
	npcink_abilities_toolkit_assert_same( 'workflow_recipe', $case['definition_kind'] ?? '', "agent workflow replay case {$case_id} is a workflow recipe definition" );
	npcink_abilities_toolkit_assert_same( 'v1', $case['contract_version'] ?? '', "agent workflow replay case {$case_id} uses definition contract v1" );
	npcink_abilities_toolkit_assert_true( is_array( $case['natural_tasks'] ?? null ), "agent workflow replay case {$case_id} exposes natural task examples" );
	npcink_abilities_toolkit_assert_true( count( $case['natural_tasks'] ) >= 3, "agent workflow replay case {$case_id} keeps at least three natural task examples" );
	npcink_abilities_toolkit_assert_same( $expected_case['ability_id'], $case['preferred_ability_id'] ?? '', "agent workflow replay case {$case_id} prefers the bundle ability" );
	npcink_abilities_toolkit_assert_same( $expected_case['ability_id'], $case['entrypoint_ability_id'] ?? '', "agent workflow replay case {$case_id} exposes the preferred bundle as entrypoint" );
	npcink_abilities_toolkit_assert_same( $expected_case['recipe_id'], $case['recipe_id'] ?? '', "agent workflow replay case {$case_id} keeps the recipe id" );
	if ( isset( $expected_case['recipe_aliases'] ) ) {
		npcink_abilities_toolkit_assert_same( $expected_case['recipe_aliases'], $case['recipe_aliases'] ?? array(), "agent workflow replay case {$case_id} keeps recipe aliases" );
	}
	npcink_abilities_toolkit_assert_same( $expected_case['required_scope'], $case['required_scope'] ?? '', "agent workflow replay case {$case_id} keeps the required scope" );
	npcink_abilities_toolkit_assert_same( $expected_case['required_inputs'], $case['required_inputs'] ?? array(), "agent workflow replay case {$case_id} keeps required inputs" );
	npcink_abilities_toolkit_assert_same( $expected_case['expected_sections'], $case['expected_sections'] ?? array(), "agent workflow replay case {$case_id} keeps expected output sections" );
	npcink_abilities_toolkit_assert_same( $expected_case['expanded_abilities'], $case['expanded_ability_ids'] ?? array(), "agent workflow replay case {$case_id} keeps expanded ability chain" );
	npcink_abilities_toolkit_assert_true( is_array( $case['handoff'] ?? null ), "agent workflow replay case {$case_id} exposes a structured handoff" );
	npcink_abilities_toolkit_assert_same( $expected_case['handoff_kind'], $case['handoff']['kind'] ?? '', "agent workflow replay case {$case_id} keeps handoff kind" );
	npcink_abilities_toolkit_assert_same( 'host', $case['handoff']['owner'] ?? '', "agent workflow replay case {$case_id} keeps host-owned handoff" );
	npcink_abilities_toolkit_assert_true( is_string( $case['handoff']['next_action'] ?? null ) && '' !== $case['handoff']['next_action'], "agent workflow replay case {$case_id} keeps a host next action hint" );
	npcink_abilities_toolkit_assert_same( 'fail_closed', $case['failure_policy'] ?? '', "agent workflow replay case {$case_id} fails closed" );
	npcink_abilities_toolkit_assert_same( $expected_case['disallowed_default'], $case['disallowed_default_ability_ids'] ?? array(), "agent workflow replay case {$case_id} keeps disallowed default write abilities" );
	npcink_abilities_toolkit_assert_same( true, $case['host_governed_write_boundary'] ?? null, "agent workflow replay case {$case_id} keeps host-governed write boundary" );
	npcink_abilities_toolkit_assert_true( isset( $package_abilities[ $expected_case['ability_id'] ] ), "agent workflow replay case {$case_id} points to a registered ability" );
	$entrypoint_ability = $package_abilities[ $expected_case['ability_id'] ];
	npcink_abilities_toolkit_assert_same( 'read', $entrypoint_ability['risk_level'] ?? '', "agent workflow replay case {$case_id} points to a read-risk bundle" );
	npcink_abilities_toolkit_assert_same( false, $entrypoint_ability['requires_confirm'] ?? null, "agent workflow replay case {$case_id} points to a read-only bundle without confirmation" );
	npcink_abilities_toolkit_assert_same( $expected_case['required_scope'], $entrypoint_ability['required_scope'] ?? '', "agent workflow replay case {$case_id} matches registered ability scope" );
	npcink_abilities_toolkit_assert_same( $expected_case['required_inputs'], $entrypoint_ability['input_schema']['required'] ?? array(), "agent workflow replay case {$case_id} required inputs match the entrypoint schema" );
	$case_catalog_key = str_replace( '/', '_', $expected_case['ability_id'] );
	if ( empty( $expected_case['local_only_entrypoint'] ) ) {
		npcink_abilities_toolkit_assert_same( 'wp_ability', $package_catalog[ $case_catalog_key ]['executor_type'] ?? '', "agent workflow replay case {$case_id} is projected for wp_ability execution" );
	} else {
		npcink_abilities_toolkit_assert_true( ! isset( $package_catalog[ $case_catalog_key ] ), "agent workflow replay case {$case_id} keeps its local-only entrypoint out of the catalog projection" );
	}
	foreach ( $expected_case['expanded_abilities'] as $expanded_ability_id ) {
		npcink_abilities_toolkit_assert_true( isset( $package_abilities[ $expanded_ability_id ] ), "agent workflow replay case {$case_id} references known expanded ability {$expanded_ability_id}" );
		npcink_abilities_toolkit_assert_true( 'destructive' !== ( $package_abilities[ $expanded_ability_id ]['risk_level'] ?? '' ), "agent workflow replay case {$case_id} expanded ability {$expanded_ability_id} is not destructive" );
	}
	foreach ( $expected_case['disallowed_default'] as $disallowed_ability_id ) {
		npcink_abilities_toolkit_assert_true( $disallowed_ability_id !== $expected_case['ability_id'], "agent workflow replay case {$case_id} does not disallow its preferred bundle" );
		npcink_abilities_toolkit_assert_true( isset( $package_abilities[ $disallowed_ability_id ] ), "agent workflow replay case {$case_id} references a known disallowed default ability {$disallowed_ability_id}" );
		npcink_abilities_toolkit_assert_true( 'read' !== ( $package_abilities[ $disallowed_ability_id ]['risk_level'] ?? 'read' ), "agent workflow replay case {$case_id} disallowed default {$disallowed_ability_id} is write-like" );
	}
}

$workflow_list = call_user_func( $package_abilities['npcink-abilities-toolkit/list-workflow-recipes']['execute_callback'], array() );
npcink_abilities_toolkit_assert_same( $workflow_manifest, $workflow_list, 'workflow recipe discovery ability returns provider manifest' );
$workflow_draft_alias = call_user_func( $package_abilities['npcink-abilities-toolkit/get-workflow-recipe']['execute_callback'], array( 'recipe_id' => 'article_draft_v1' ) );
npcink_abilities_toolkit_assert_same( $workflow_manifest['cases']['article_draft'], $workflow_draft_alias, 'workflow recipe detail ability resolves article_draft_v1 alias' );
$workflow_media_optimization_alias = call_user_func( $package_abilities['npcink-abilities-toolkit/get-workflow-recipe']['execute_callback'], array( 'recipe_id' => 'media_optimization_v1' ) );
npcink_abilities_toolkit_assert_same( $workflow_manifest['cases']['media_optimization'], $workflow_media_optimization_alias, 'workflow recipe detail ability resolves media_optimization_v1 alias' );
$workflow_get = call_user_func( $package_abilities['npcink-abilities-toolkit/get-workflow-recipe']['execute_callback'], array( 'recipe_id' => 'npcink-abilities-toolkit/recipes/comment-compliance-handoff' ) );
npcink_abilities_toolkit_assert_same( $workflow_manifest['cases']['comment_compliance_handoff'], $workflow_get, 'workflow recipe detail ability resolves recipe id' );
$workflow_missing = call_user_func( $package_abilities['npcink-abilities-toolkit/get-workflow-recipe']['execute_callback'], array( 'recipe_id' => 'workflow/missing' ) );
npcink_abilities_toolkit_assert_true( is_wp_error( $workflow_missing ), 'workflow recipe detail ability fails closed for missing recipe' );

// Media backup cleanup is maintenance-only: preview is read-only and
// execution is restricted to the dedicated backup root.
$cleanup_uploads = sys_get_temp_dir() . '/npcink-abilities-toolkit-cleanup-' . getmypid();
$GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] = $cleanup_uploads;
wp_mkdir_p( $cleanup_uploads . '/2026/06' );
wp_mkdir_p( $cleanup_uploads . '/npcink-abilities-toolkit-backups/2026/06' );
$cleanup_current_relative = '2026/06/cleanup-current.jpg';
$cleanup_backup_relative = 'npcink-abilities-toolkit-backups/2026/06/cleanup-original.jpg';
$cleanup_current_path = $cleanup_uploads . '/' . $cleanup_current_relative;
$cleanup_backup_path = $cleanup_uploads . '/' . $cleanup_backup_relative;
file_put_contents( $cleanup_current_path, 'current-media-bytes' );
file_put_contents( $cleanup_backup_path, 'original-media-bytes' );
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][777] = (object) array(
	'ID' => 777,
	'post_type' => 'attachment',
	'post_status' => 'inherit',
	'post_title' => 'Cleanup fixture',
);
$cleanup_history = array(
	array(
		'replacement_id' => 'cleanup-expired',
		'replaced_at_gmt' => gmdate( 'c', time() - ( 60 * 86400 ) ),
		'status' => 'completed',
		'backup_cleanup_policy' => 'automatic_after_retention',
		'backup' => array( 'relative_file' => $cleanup_backup_relative, 'file_exists' => true ),
	),
	array(
		'replacement_id' => 'cleanup-current',
		'replaced_at_gmt' => gmdate( 'c' ),
		'status' => 'completed',
		'backup' => array( 'relative_file' => '2026/06/not-yet-expired.jpg', 'file_exists' => true ),
	),
	array(
		'replacement_id' => 'cleanup-outside-root',
		'replaced_at_gmt' => gmdate( 'c', time() - ( 60 * 86400 ) ),
		'status' => 'completed',
		'backup' => array( 'relative_file' => '2026/06/not-a-backup.jpg', 'file_exists' => true ),
	),
);
update_post_meta( 777, '_npcink_ai_media_file_replacement_history', $cleanup_history );
update_post_meta( 777, '_wp_attached_file', $cleanup_current_relative );
// Isolate the maintenance scan from the larger fixture corpus above.
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'] = array( 777 => $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][777] );
$GLOBALS['npcink_abilities_toolkit_unit_post_meta'] = array( 777 => $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][777] );
$cleanup_preview = $core_write_package->preview_expired_media_backups();
npcink_abilities_toolkit_assert_same( 30, $cleanup_preview['retention_days'] ?? 0, 'media backup cleanup uses the default 30-day retention period' );
npcink_abilities_toolkit_assert_same( 1, $cleanup_preview['expired'] ?? 0, 'media backup cleanup preview finds only the expired dedicated backup' );
npcink_abilities_toolkit_assert_same( 0, $cleanup_preview['removed'] ?? -1, 'media backup cleanup preview never removes files' );
npcink_abilities_toolkit_assert_true( is_file( $cleanup_backup_path ), 'media backup cleanup preview leaves the expired backup in place' );
$cleanup_result = $core_write_package->cleanup_expired_media_backups( true );
npcink_abilities_toolkit_assert_same( 1, $cleanup_result['expired'] ?? 0, 'media backup cleanup execution processes the expired backup' );
npcink_abilities_toolkit_assert_same( 1, $cleanup_result['removed'] ?? 0, 'media backup cleanup removes one expired dedicated backup' );
npcink_abilities_toolkit_assert_true( ! is_file( $cleanup_backup_path ), 'media backup cleanup removes the expired backup file' );
npcink_abilities_toolkit_assert_true( is_file( $cleanup_current_path ), 'media backup cleanup never removes the current attachment file' );
$cleanup_history_after = get_post_meta( 777, '_npcink_ai_media_file_replacement_history', true );
npcink_abilities_toolkit_assert_same( 'backup_expired', $cleanup_history_after[0]['status'] ?? '', 'media backup cleanup retains history and marks the backup expired' );
npcink_abilities_toolkit_assert_same( 'completed', $cleanup_history_after[1]['status'] ?? '', 'media backup cleanup leaves non-expired history unchanged' );
npcink_abilities_toolkit_assert_same( 'completed', $cleanup_history_after[2]['status'] ?? '', 'media backup cleanup ignores files outside the dedicated backup root' );
$cleanup_repeat = $core_write_package->cleanup_expired_media_backups( true );
npcink_abilities_toolkit_assert_same( 0, $cleanup_repeat['expired'] ?? -1, 'media backup cleanup is idempotent after an expired backup is marked' );
@unlink( $cleanup_current_path );

// The persisted ID cursor advances beyond the first 500 attachments instead
// of rescanning an unchanged meta-key population forever.
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'] = array();
$GLOBALS['npcink_abilities_toolkit_unit_post_meta'] = array();
for ( $cleanup_fixture_id = 1000; $cleanup_fixture_id < 1502; ++$cleanup_fixture_id ) {
	$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][ $cleanup_fixture_id ] = (object) array(
		'ID'          => $cleanup_fixture_id,
		'post_type'   => 'attachment',
		'post_status' => 'inherit',
	);
	update_post_meta(
		$cleanup_fixture_id,
		'_npcink_ai_media_file_replacement_history',
		array(
			array(
				'replacement_id'  => 'cleanup-' . $cleanup_fixture_id,
				'replaced_at_gmt' => gmdate( 'c', time() - ( 60 * 86400 ) ),
				'status'          => 'completed',
				'backup'          => array( 'relative_file' => 'npcink-abilities-toolkit-backups/2026/06/missing-' . $cleanup_fixture_id . '.jpg', 'file_exists' => true ),
			),
		)
	);
}
$bounded_cleanup_first = $core_write_package->cleanup_expired_media_backups( true );
npcink_abilities_toolkit_assert_same( 500, $bounded_cleanup_first['processed_attachments'] ?? 0, 'media backup cleanup processes at most 500 attachments in one run' );
npcink_abilities_toolkit_assert_same( true, $bounded_cleanup_first['has_more'] ?? false, 'media backup cleanup reports the look-ahead attachment' );
npcink_abilities_toolkit_assert_same( 1499, $bounded_cleanup_first['next_cursor'] ?? 0, 'media backup cleanup persists a stable attachment ID cursor' );
npcink_abilities_toolkit_assert_same( 'completed', $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][1500]['_npcink_ai_media_file_replacement_history'][0]['status'] ?? '', 'the first bounded cleanup run does not process attachment 501' );
$bounded_cleanup_second = $core_write_package->cleanup_expired_media_backups( true );
npcink_abilities_toolkit_assert_same( 2, $bounded_cleanup_second['processed_attachments'] ?? 0, 'the next cleanup run resumes after the stable cursor' );
npcink_abilities_toolkit_assert_same( false, $bounded_cleanup_second['has_more'] ?? true, 'the final cleanup window reports no remaining attachments' );
npcink_abilities_toolkit_assert_same( 'backup_expired', $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][1501]['_npcink_ai_media_file_replacement_history'][0]['status'] ?? '', 'the stable cursor eventually reaches the final attachment' );
npcink_abilities_toolkit_assert_same( false, isset( $GLOBALS['npcink_abilities_toolkit_unit_options']['npcink_abilities_toolkit_media_backup_cleanup_cursor'] ), 'the cleanup cursor resets after a complete scan cycle' );

