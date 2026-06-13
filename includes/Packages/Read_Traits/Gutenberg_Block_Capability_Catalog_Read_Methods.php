<?php
/**
 * Gutenberg block capability catalog methods for Core_Read_Package.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exposes bounded Gutenberg-native block composition rules for planners.
 */
trait Gutenberg_Block_Capability_Catalog_Read_Methods {
	/**
	 * Returns the governed Gutenberg block capability catalog.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_gutenberg_block_capability_catalog( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to inspect Gutenberg block capabilities.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$surface = sanitize_key( (string) ( $input['surface'] ?? 'all' ) );
		if ( ! in_array( $surface, array( 'all', 'page', 'post', 'template' ), true ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_block_catalog_surface_invalid', __( 'Block capability catalog surface is not supported.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		return $this->build_analysis_success_response(
			$this->gutenberg_block_capability_catalog( $surface ),
			array(
				'source'         => 'local_gutenberg_block_capability_catalog',
				'execution_mode' => 'deterministic',
			),
			'Gutenberg block capability catalog returned.'
		);
	}

	/**
	 * Builds the deterministic block capability catalog.
	 *
	 * @param string $surface Surface filter.
	 * @return array<string,mixed>
	 */
	private function gutenberg_block_capability_catalog( string $surface = 'all' ): array {
		$blocks = $this->gutenberg_block_capability_definitions();

		return array(
			'artifact_type'          => 'gutenberg_block_capability_catalog',
			'version'                => '1.0',
			'catalog_id'             => 'gutenberg_native_v1',
			'surface'                => sanitize_key( $surface ),
			'composition_model'      => 'bounded_block_composition',
			'composer_role'          => 'ai_selects_sections_and_core_blocks',
			'plugin_role'            => 'normalize_validate_serialize_and_handoff_to_proposal',
			'composer_instruction'    => $this->gutenberg_block_composer_instruction( $surface ),
			'recommended_composer_flow' => $this->gutenberg_block_recommended_composer_flow(),
			'direct_wordpress_write' => false,
			'commit_execution'       => false,
			'custom_css_allowed'     => false,
			'core_html_allowed'      => false,
			'non_core_blocks_allowed' => false,
			'allowed_block_names'    => array_keys( $blocks ),
			'forbidden_block_names'  => array( 'core/html', 'core/freeform' ),
			'blocks'                 => $blocks,
			'section_primitives'     => array(
				'hero',
				'media_proof',
				'feature_grid',
				'comparison',
				'article_intro',
				'article_takeaways',
				'faq',
				'final_cta',
				'breadcrumbs',
			),
			'template_placement_standards' => $this->gutenberg_block_template_placement_standards(),
			'responsive_rules'       => array(
				'columns_must_stack_on_mobile' => true,
				'max_columns_per_row'          => 4,
				'media_requires_alt'           => true,
				'prefer_media_text_for_split_media_sections' => true,
			),
			'quality_gates'          => array(
				'roundtrip_validation_required' => true,
				'invalid_block_risk_allowed'    => 'low',
				'proposal_required_for_writes'  => true,
				'draft_only_default'            => true,
			),
			'repair_policy'          => array(
				'too_long_heading' => 'shorten_copy_before_reducing_below_readable_size',
				'missing_media_alt' => 'require_alt_before_proposal',
				'non_core_block'   => 'fail_closed',
				'core_html'        => 'fail_closed',
				'mobile_columns'   => 'set_isStackedOnMobile_true_or_recompose',
			),
		);
	}

	/**
	 * Returns block-level composition capabilities.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function gutenberg_block_capability_definitions(): array {
		return array(
			'core/group' => array(
				'roles'              => array( 'section', 'container', 'card', 'band' ),
				'allowed_attrs'      => array( 'align', 'className', 'layout', 'style', 'tagName' ),
				'allowed_style_paths' => array( 'spacing.padding', 'spacing.margin', 'color.background', 'color.text', 'border.color', 'border.width', 'border.radius' ),
				'composition'        => array( 'may_contain_inner_blocks' => true ),
			),
			'core/columns' => array(
				'roles'              => array( 'responsive_row', 'comparison', 'feature_grid' ),
				'allowed_attrs'      => array( 'className', 'style', 'isStackedOnMobile', 'verticalAlignment' ),
				'required_attrs'     => array( 'isStackedOnMobile' ),
				'composition'        => array( 'allowed_children' => array( 'core/column' ) ),
			),
			'core/column' => array(
				'roles'              => array( 'responsive_column' ),
				'allowed_attrs'      => array( 'className', 'style', 'verticalAlignment', 'width' ),
				'composition'        => array( 'may_contain_inner_blocks' => true ),
			),
			'core/heading' => array(
				'roles'              => array( 'section_title', 'hero_title', 'card_title' ),
				'allowed_attrs'      => array( 'level', 'className', 'style', 'textAlign' ),
				'allowed_style_paths' => array( 'typography.fontSize', 'typography.lineHeight', 'typography.fontWeight', 'typography.letterSpacing', 'color.text' ),
				'content_rules'      => array( 'hero_title_display_units_max' => 34 ),
			),
			'core/paragraph' => array(
				'roles'              => array( 'body_copy', 'eyebrow', 'lede', 'caption', 'status_label' ),
				'allowed_attrs'      => array( 'className', 'style', 'fontSize', 'textColor', 'backgroundColor', 'align' ),
				'allowed_style_paths' => array( 'typography.fontSize', 'typography.lineHeight', 'color.text', 'color.background', 'spacing.padding', 'border.radius' ),
			),
			'core/buttons' => array(
				'roles'              => array( 'cta_group' ),
				'allowed_attrs'      => array( 'className', 'layout', 'style' ),
				'composition'        => array( 'allowed_children' => array( 'core/button' ) ),
			),
			'core/button' => array(
				'roles'              => array( 'primary_cta', 'secondary_cta' ),
				'allowed_attrs'      => array( 'className', 'style', 'backgroundColor', 'textColor', 'width' ),
				'allowed_style_paths' => array( 'border.radius', 'spacing.padding', 'color.background', 'color.text' ),
			),
			'core/image' => array(
				'roles'              => array( 'hero_media', 'article_media', 'supporting_visual' ),
				'allowed_attrs'      => array( 'id', 'url', 'alt', 'sizeSlug', 'linkDestination', 'className', 'style' ),
				'required_attrs_when_present' => array( 'alt' ),
				'media_rules'        => array( 'prefer_attachment_id' => true, 'temporary_cloud_preview_url_allowed' => false ),
			),
			'core/media-text' => array(
				'roles'              => array( 'split_media_section', 'proof_section' ),
				'allowed_attrs'      => array( 'mediaId', 'mediaUrl', 'mediaType', 'mediaAlt', 'mediaPosition', 'verticalAlignment', 'className', 'style' ),
				'required_attrs_when_present' => array( 'mediaAlt' ),
				'responsive'         => array( 'stacks_on_mobile_by_core_block' => true ),
			),
			'core/details' => array(
				'roles'              => array( 'faq_item', 'disclosure' ),
				'allowed_attrs'      => array( 'className', 'showContent', 'style' ),
			),
			'core/list' => array(
				'roles'              => array( 'takeaways', 'steps', 'checklist' ),
				'allowed_attrs'      => array( 'className', 'ordered', 'style' ),
			),
			'core/separator' => array(
				'roles'              => array( 'divider' ),
				'allowed_attrs'      => array( 'className', 'style', 'opacity' ),
			),
			'core/spacer' => array(
				'roles'              => array( 'controlled_spacing' ),
				'allowed_attrs'      => array( 'height', 'style' ),
			),
			'core/post-title' => array(
				'roles'              => array( 'template_title_anchor' ),
				'allowed_attrs'      => array( 'className', 'level', 'style', 'textAlign' ),
				'surface'            => 'template',
			),
			'core/query-title' => array(
				'roles'              => array( 'archive_title_anchor' ),
				'allowed_attrs'      => array( 'className', 'type', 'style', 'textAlign' ),
				'surface'            => 'template',
			),
			'core/post-content' => array(
				'roles'              => array( 'template_content_slot' ),
				'allowed_attrs'      => array( 'className', 'layout', 'style' ),
				'surface'            => 'template',
			),
			'core/template-part' => array(
				'roles'              => array( 'template_part_reference' ),
				'allowed_attrs'      => array( 'slug', 'theme', 'tagName', 'className' ),
				'surface'            => 'template',
			),
			'core/pattern' => array(
				'roles'              => array( 'template_pattern_reference' ),
				'allowed_attrs'      => array( 'slug' ),
				'surface'            => 'template',
			),
			'core/post-featured-image' => array(
				'roles'              => array( 'template_featured_image' ),
				'allowed_attrs'      => array( 'className', 'style', 'aspectRatio', 'height', 'width' ),
				'surface'            => 'template',
			),
			'core/post-terms' => array(
				'roles'              => array( 'template_terms' ),
				'allowed_attrs'      => array( 'term', 'className', 'style' ),
				'surface'            => 'template',
			),
		);
	}

	/**
	 * Returns a compact composition contract for one generated block tree.
	 *
	 * @param array<int,array<string,mixed>> $blocks Blocks.
	 * @param string                         $surface Surface label.
	 * @return array<string,mixed>
	 */
	private function gutenberg_block_composition_contract( array $blocks, string $surface ): array {
		$summary = $this->gutenberg_block_capability_compliance_summary( $blocks );
		return array(
			'catalog_id'             => 'gutenberg_native_v1',
			'catalog_version'        => '1.0',
			'surface'                => sanitize_key( $surface ),
			'composition_model'      => 'bounded_block_composition',
			'plugin_role'            => 'normalize_validate_serialize_and_handoff_to_proposal',
			'ai_role'                => 'select_allowed_core_blocks_and_section_order',
			'composer_instruction'    => $this->gutenberg_block_composer_instruction( $surface ),
			'recommended_composer_flow' => $this->gutenberg_block_recommended_composer_flow(),
			'core_html_allowed'      => false,
			'non_core_blocks_allowed' => false,
			'custom_css_allowed'     => false,
			'contract_status'        => $summary['contract_status'],
			'used_block_names'       => $summary['used_block_names'],
			'forbidden_block_names'  => $summary['forbidden_block_names'],
			'non_core_blocks'        => $summary['non_core_blocks'],
			'responsive_rules'       => array(
				'columns_must_stack_on_mobile' => true,
				'columns_without_mobile_stack' => $summary['columns_without_mobile_stack'],
			),
			'media_rules'            => array(
				'media_requires_alt'       => true,
				'media_missing_alt_count'  => $summary['media_missing_alt_count'],
				'temporary_cloud_preview_url_count' => $summary['temporary_cloud_preview_url_count'],
			),
		);
	}

	/**
	 * Returns template-level placement standards for Site Editor plans.
	 *
	 * @return array<string,mixed>
	 */
	private function gutenberg_block_template_placement_standards(): array {
		return array(
			'breadcrumbs' => array(
				'surface'                 => 'template',
				'placement_model'         => 'bounded_template_anchor_placement',
				'required_container'      => 'main',
				'preferred_position'      => 'before',
				'preferred_anchor_blocks' => array( 'core/post-title', 'core/query-title' ),
				'accepted_strategies'     => array( 'before_post_title_in_main', 'home_page_disabled' ),
				'fallback_strategy'       => 'manual_review_before_template_start_fallback',
				'home_template_policy'    => 'hide_by_default_unless_show_on_home_page_true',
				'forbidden_placements'    => array(
					'above_header',
					'inside_template_part',
					'template_start_fallback',
				),
			),
		);
	}

	/**
	 * Returns a compact placement contract for a block theme plan.
	 *
	 * @param string              $intent Intent.
	 * @param array<int,mixed>    $placements Placement rows.
	 * @return array<string,mixed>
	 */
	private function gutenberg_block_template_placement_contract( string $intent, array $placements ): array {
		$intent    = sanitize_key( $intent );
		$standards = $this->gutenberg_block_template_placement_standards();
		$standard  = is_array( $standards['breadcrumbs'] ?? null ) ? $standards['breadcrumbs'] : array();
		$accepted  = is_array( $standard['accepted_strategies'] ?? null ) ? $standard['accepted_strategies'] : array();
		$anchors   = is_array( $standard['preferred_anchor_blocks'] ?? null ) ? $standard['preferred_anchor_blocks'] : array();
		$violations = array();
		$rows       = array();

		foreach ( $placements as $placement ) {
			if ( ! is_array( $placement ) ) {
				continue;
			}
			$strategy = sanitize_key( (string) ( $placement['strategy'] ?? '' ) );
			$anchor   = sanitize_text_field( (string) ( $placement['inserted_before'] ?? '' ) );
			$status   = sanitize_key( (string) ( $placement['status'] ?? '' ) );
			$slug     = sanitize_key( (string) ( $placement['slug'] ?? '' ) );
			$valid_strategy = in_array( $strategy, $accepted, true );
			$valid_anchor   = '' === $anchor || in_array( $anchor, $anchors, true );
			if ( ! $valid_strategy ) {
				$violations[] = 'placement_strategy_not_allowed';
			}
			if ( ! $valid_anchor ) {
				$violations[] = 'placement_anchor_not_allowed';
			}
			$rows[] = array(
				'slug'             => $slug,
				'status'           => $status,
				'strategy'         => $strategy,
				'inserted_before'  => $anchor,
				'strategy_allowed' => $valid_strategy,
				'anchor_allowed'   => $valid_anchor,
			);
		}

		$violations = array_values( array_unique( array_filter( $violations ) ) );

		return array(
			'catalog_id'              => 'gutenberg_native_v1',
			'catalog_version'         => '1.0',
			'surface'                 => 'template',
			'intent'                  => $intent,
			'placement_model'         => (string) ( $standard['placement_model'] ?? 'bounded_template_anchor_placement' ),
			'required_container'      => (string) ( $standard['required_container'] ?? 'main' ),
			'preferred_position'      => (string) ( $standard['preferred_position'] ?? 'before' ),
			'preferred_anchor_blocks' => $anchors,
			'accepted_strategies'     => $accepted,
			'forbidden_placements'    => is_array( $standard['forbidden_placements'] ?? null ) ? $standard['forbidden_placements'] : array(),
			'contract_status'         => empty( $violations ) ? 'pass' : 'needs_revision',
			'violation_codes'         => $violations,
			'placements'              => $rows,
		);
	}

	/**
	 * Returns compliance details against the block capability catalog.
	 *
	 * @param array<int,array<string,mixed>> $blocks Blocks.
	 * @return array<string,mixed>
	 */
	private function gutenberg_block_capability_compliance_summary( array $blocks ): array {
		$catalog_names    = array_flip( array_keys( $this->gutenberg_block_capability_definitions() ) );
		$used             = array();
		$non_core         = array();
		$forbidden        = array();
		$columns_unstacked = 0;
		$media_missing_alt = 0;
		$temp_urls        = 0;

		$walk = function ( array $items ) use ( &$walk, &$used, &$non_core, &$forbidden, &$columns_unstacked, &$media_missing_alt, &$temp_urls, $catalog_names ): void {
			foreach ( $items as $block ) {
				if ( ! is_array( $block ) ) {
					continue;
				}
				$name = (string) ( $block['blockName'] ?? '' );
				if ( '' !== $name ) {
					$used[] = $name;
					if ( in_array( $name, array( 'core/html', 'core/freeform' ), true ) ) {
						$forbidden[] = $name;
					}
					if ( 0 !== strpos( $name, 'core/' ) || ! isset( $catalog_names[ $name ] ) ) {
						$non_core[] = $name;
					}
				}
				if ( 'core/columns' === $name && false === ( $block['attrs']['isStackedOnMobile'] ?? true ) ) {
					++$columns_unstacked;
				}
				if ( in_array( $name, array( 'core/image', 'core/media-text' ), true ) ) {
					$alt = 'core/media-text' === $name ? (string) ( $block['attrs']['mediaAlt'] ?? '' ) : (string) ( $block['attrs']['alt'] ?? '' );
					if ( '' === trim( $alt ) ) {
						++$media_missing_alt;
					}
					$url = 'core/media-text' === $name ? (string) ( $block['attrs']['mediaUrl'] ?? '' ) : (string) ( $block['attrs']['url'] ?? '' );
					if ( false !== strpos( $url, 'temporary' ) || false !== strpos( $url, 'cloud-preview' ) ) {
						++$temp_urls;
					}
				}
				if ( is_array( $block['innerBlocks'] ?? null ) ) {
					$walk( array_values( $block['innerBlocks'] ) );
				}
			}
		};
		$walk( $blocks );

		$used      = array_values( array_unique( array_filter( $used ) ) );
		$non_core  = array_values( array_unique( array_filter( $non_core ) ) );
		$forbidden = array_values( array_unique( array_filter( $forbidden ) ) );
		$status    = empty( $non_core ) && empty( $forbidden ) && 0 === $columns_unstacked && 0 === $media_missing_alt && 0 === $temp_urls ? 'pass' : 'needs_revision';

		return array(
			'contract_status'                   => $status,
			'used_block_names'                  => $used,
			'non_core_blocks'                   => $non_core,
			'forbidden_block_names'             => $forbidden,
			'columns_without_mobile_stack'      => $columns_unstacked,
			'media_missing_alt_count'           => $media_missing_alt,
			'temporary_cloud_preview_url_count' => $temp_urls,
		);
	}

	/**
	 * Returns concise AI composer instructions for a Gutenberg surface.
	 *
	 * @param string $surface Surface label.
	 * @return array<string,mixed>
	 */
	private function gutenberg_block_composer_instruction( string $surface = 'all' ): array {
		return array(
			'instruction_id' => 'gutenberg_native_block_composer_v1',
			'surface'        => sanitize_key( $surface ),
			'objective'      => 'compose_with_allowed_core_blocks_not_raw_html',
			'ai_may_choose'  => array(
				'section_order',
				'allowed_core_blocks',
				'copy',
				'native_block_attrs_within_catalog',
				'media_slot_usage',
			),
			'ai_must_not_choose' => array(
				'core/html',
				'core/freeform',
				'non_core_blocks',
				'custom_css',
				'direct_wordpress_write',
				'publish_status',
			),
			'preferred_process' => array(
				'read_block_capability_catalog',
				'route_natural_language_intent',
				'compose_sections_from_primitives',
				'use_only_allowed_core_blocks_and_attrs',
				'run_quality_gate',
				'create_proposal_for_human_review',
			),
			'fallback_policy' => 'fail_closed_when_intent_or_block_contract_is_unsupported',
		);
	}

	/**
	 * Returns the recommended ability flow for AI composers.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function gutenberg_block_recommended_composer_flow(): array {
		return array(
			array(
				'step'       => 'inspect_catalog',
				'ability_id' => 'npcink-abilities-toolkit/get-gutenberg-block-capability-catalog',
				'write'      => false,
			),
			array(
				'step'       => 'route_intent',
				'ability_id' => 'npcink-abilities-toolkit/route-content-intent',
				'write'      => false,
			),
			array(
				'step'       => 'build_plan',
				'ability_id' => 'route.plan_ability_id',
				'write'      => false,
			),
			array(
				'step'       => 'submit_proposal',
				'endpoint'   => '/wp-json/npcink-governance-core/v1/proposals/from-plan',
				'write'      => false,
			),
			array(
				'step'       => 'execute_after_approval',
				'endpoint'   => '/wp-json/npcink-governance-core/v1/proposals/{proposal_id}/execute',
				'write'      => true,
				'requires_approval' => true,
			),
			array(
				'step'       => 'readback_verify',
				'ability_id' => 'route.readback_ability_ids',
				'write'      => false,
			),
		);
	}
}
