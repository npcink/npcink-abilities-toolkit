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
	 * Inspects one Gutenberg block surface against the composition contract.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function inspect_gutenberg_composition_contract( $input ) {
		$input            = is_array( $input ) ? $input : array();
		$surface_result   = $this->gutenberg_block_contract_surface_blocks( $input );
		if ( is_wp_error( $surface_result ) ) {
			return $surface_result;
		}

		$blocks          = is_array( $surface_result['blocks'] ?? null ) ? $surface_result['blocks'] : array();
		$surface         = is_array( $surface_result['surface'] ?? null ) ? $surface_result['surface'] : array();
		$surface_label   = sanitize_key( (string) ( $surface_result['contract_surface'] ?? 'all' ) );
		$placement_check = sanitize_key( (string) ( $input['placement_check'] ?? 'breadcrumbs' ) );
		$composition     = $this->gutenberg_block_composition_contract( $blocks, $surface_label );
		$placement       = array();

		if ( 'breadcrumbs' === $placement_check && 'template' === $surface_label ) {
			$placement = $this->gutenberg_block_current_template_placement_contract(
				'add_breadcrumbs',
				sanitize_key( (string) ( $surface['slug'] ?? '' ) ),
				$blocks,
				! empty( $input['show_on_home_page'] )
			);
		}

		$contract_status = 'pass' === (string) ( $composition['contract_status'] ?? '' ) ? 'pass' : 'needs_revision';
		$violation_codes = array();
		if ( 'pass' !== (string) ( $composition['contract_status'] ?? '' ) ) {
			$violation_codes[] = 'composition_contract_failed';
		}
		if ( ! empty( $placement ) && 'pass' !== (string) ( $placement['contract_status'] ?? '' ) ) {
			$contract_status   = 'needs_revision';
			$violation_codes[] = 'template_placement_contract_failed';
			$violation_codes   = array_merge( $violation_codes, is_array( $placement['violation_codes'] ?? null ) ? $placement['violation_codes'] : array() );
		}

		$violation_codes = array_values( array_unique( array_filter( array_map( 'sanitize_key', $violation_codes ) ) ) );

		$data = array(
			'artifact_type'          => 'gutenberg_composition_contract_inspection',
			'version'                => 1,
			'source'                 => sanitize_key( (string) ( $surface_result['source'] ?? 'blocks_input' ) ),
			'block_editor_surface'   => $surface,
			'block_count'            => $this->gutenberg_block_count_blocks( $blocks ),
			'top_level_count'        => count( $blocks ),
			'content_length'         => absint( $surface_result['content_length'] ?? 0 ),
			'contract_status'        => $contract_status,
			'violation_codes'        => $violation_codes,
			'composition_contract'    => $composition,
			'direct_wordpress_write' => false,
			'commit_execution'       => false,
			'server_side_contract_only' => true,
			'editor_validation_note' => 'Server-side contract inspection checks parsed blocks and known template anchors only; Gutenberg editor save() validation still requires opening the editor.',
			'recommended_next_actions' => $this->gutenberg_block_contract_next_actions( $composition, $placement, $surface ),
		);

		if ( ! empty( $placement ) ) {
			$data['template_placement_contract'] = $placement;
		}

		return $this->build_analysis_success_response(
			$data,
			array(
				'source'         => 'local_gutenberg_composition_contract_inspection',
				'execution_mode' => 'deterministic_readonly',
			),
			'Gutenberg composition contract inspection built.'
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
	 * Resolves blocks for one contract inspection surface.
	 *
	 * @param array<string,mixed> $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function gutenberg_block_contract_surface_blocks( array $input ) {
		$surface_kind   = sanitize_key( (string) ( $input['surface_kind'] ?? '' ) );
		$post_type      = sanitize_key( (string) ( $input['post_type'] ?? '' ) );
		$post_id        = absint( $input['post_id'] ?? 0 );
		$slug           = sanitize_key( (string) ( $input['slug'] ?? '' ) );
		$blocks         = array();
		$content        = '';
		$content_length = 0;
		$source         = 'blocks_input';
		$surface        = array(
			'surface_kind' => '' !== $surface_kind ? $surface_kind : 'blocks_input',
			'editor'       => 'block_editor',
			'post_type'    => '' !== $post_type ? $post_type : '',
			'post_id'      => 0,
			'slug'         => '',
			'source'       => '',
			'target_mode'  => 'inspect_blocks_input',
		);

		if ( is_array( $input['blocks'] ?? null ) ) {
			$is_template_blocks = in_array( $surface_kind, array( 'site_editor_template', 'site_editor_template_part' ), true ) || in_array( $post_type, array( 'wp_template', 'wp_template_part' ), true );
			if ( $is_template_blocks && ! current_user_can( 'edit_theme_options' ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to inspect block theme templates.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
			}
			if ( ! $is_template_blocks && ! current_user_can( 'edit_posts' ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to inspect proposed blocks.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
			}
			$blocks = $this->pattern_review_normalize_blocks( $input['blocks'] );
			if ( $is_template_blocks ) {
				$surface['surface_kind'] = 'wp_template_part' === $post_type || 'site_editor_template_part' === $surface_kind ? 'site_editor_template_part' : 'site_editor_template';
				$surface['editor']       = 'site_editor';
				$surface['post_type']    = '' !== $post_type ? $post_type : ( 'site_editor_template_part' === $surface['surface_kind'] ? 'wp_template_part' : 'wp_template' );
				$surface['slug']         = $slug;
			}
		} else {
			if ( '' === $post_type && $post_id > 0 ) {
				$post = get_post( $post_id );
				if ( ! $post ) {
					return new \WP_Error( 'npcink_abilities_toolkit_post_not_found', __( 'Post was not found.', 'npcink-abilities-toolkit' ), array( 'status' => 404 ) );
				}
				$post_type = sanitize_key( (string) ( $post->post_type ?? '' ) );
			}

			if ( in_array( $surface_kind, array( 'site_editor_template', 'site_editor_template_part' ), true ) && '' === $post_type ) {
				$post_type = 'site_editor_template_part' === $surface_kind ? 'wp_template_part' : 'wp_template';
			}

			if ( in_array( $post_type, array( 'wp_template', 'wp_template_part' ), true ) ) {
				if ( ! current_user_can( 'edit_theme_options' ) ) {
					return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to inspect block theme templates.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
				}
				$entity = $this->block_theme_find_entity( $post_type, $slug, $post_id );
				if ( empty( $entity ) ) {
					return new \WP_Error( 'npcink_abilities_toolkit_block_theme_entity_not_found', __( 'Block theme entity was not found.', 'npcink-abilities-toolkit' ), array( 'status' => 404 ) );
				}
				$content        = (string) ( $entity['content'] ?? '' );
				$content_length = strlen( $content );
				$parsed_blocks  = function_exists( 'parse_blocks' ) ? parse_blocks( $content ) : array();
				$blocks         = $this->pattern_review_normalize_blocks( is_array( $parsed_blocks ) ? $parsed_blocks : array() );
				$source         = 'site_editor_entity';
				$surface        = array(
					'surface_kind'      => 'wp_template_part' === $post_type ? 'site_editor_template_part' : 'site_editor_template',
					'editor'            => 'site_editor',
					'post_type'         => $post_type,
					'post_id'           => absint( $entity['post_id'] ?? 0 ),
					'slug'              => sanitize_key( (string) ( $entity['slug'] ?? '' ) ),
					'template_id'       => sanitize_text_field( (string) ( $entity['template_id'] ?? '' ) ),
					'theme'             => sanitize_key( (string) ( $entity['theme'] ?? '' ) ),
					'source'            => sanitize_key( (string) ( $entity['source'] ?? '' ) ),
					'title'             => sanitize_text_field( (string) ( $entity['title'] ?? '' ) ),
					'target_mode'       => 'inspect_existing',
					'read_ability_id'   => 'wp_template_part' === $post_type ? 'npcink-abilities-toolkit/get-template-part-blocks' : 'npcink-abilities-toolkit/get-template-blocks',
					'write_ability_ids' => 'wp_template_part' === $post_type ? array( 'npcink-abilities-toolkit/update-template-part-blocks' ) : array( 'npcink-abilities-toolkit/update-template-blocks', 'npcink-abilities-toolkit/upsert-template-blocks' ),
				);
			} else {
				if ( ! in_array( $post_type, array( 'post', 'page' ), true ) ) {
					return new \WP_Error( 'npcink_abilities_toolkit_block_editor_surface_type_invalid', __( 'Block editor surface type is not supported.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
				}
				$post = get_post( $post_id );
				if ( ! $post ) {
					return new \WP_Error( 'npcink_abilities_toolkit_post_not_found', __( 'Post was not found.', 'npcink-abilities-toolkit' ), array( 'status' => 404 ) );
				}
				if ( ! current_user_can( 'edit_post', $post_id ) ) {
					return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to inspect this post.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
				}
				$content        = (string) ( $post->post_content ?? '' );
				$content_length = strlen( $content );
				$parsed_blocks  = function_exists( 'parse_blocks' ) ? parse_blocks( $content ) : array();
				$blocks         = $this->pattern_review_normalize_blocks( is_array( $parsed_blocks ) ? $parsed_blocks : array() );
				$source         = 'post_content';
				$surface        = array(
					'surface_kind'     => 'post_content',
					'editor'           => 'block_editor',
					'post_type'        => $post_type,
					'post_id'          => $post_id,
					'slug'             => sanitize_title( (string) ( $post->post_name ?? '' ) ),
					'status'           => sanitize_key( (string) ( $post->post_status ?? '' ) ),
					'title'            => sanitize_text_field( (string) ( $post->post_title ?? '' ) ),
					'target_mode'      => 'inspect_existing',
					'read_ability_id'  => 'npcink-abilities-toolkit/get-post-blocks',
					'write_ability_id' => 'npcink-abilities-toolkit/update-post-blocks',
					'plan_ability_id'  => 'page' === $post_type ? 'npcink-abilities-toolkit/build-pattern-page-plan' : 'npcink-abilities-toolkit/build-article-block-plan',
				);
			}
		}

		if ( empty( $blocks ) && ! empty( $content ) ) {
			$blocks[] = array(
				'blockName'    => 'core/freeform',
				'attrs'        => array(),
				'innerHTML'    => $content,
				'innerContent' => array( $content ),
				'innerBlocks'  => array(),
			);
		}

		$contract_surface = 'all';
		if ( 'post_content' === (string) ( $surface['surface_kind'] ?? '' ) && in_array( (string) ( $surface['post_type'] ?? '' ), array( 'post', 'page' ), true ) ) {
			$contract_surface = (string) $surface['post_type'];
		} elseif ( in_array( (string) ( $surface['surface_kind'] ?? '' ), array( 'site_editor_template', 'site_editor_template_part' ), true ) ) {
			$contract_surface = 'template';
		}

		return array(
			'blocks'           => $blocks,
			'content_length'   => $content_length,
			'source'           => $source,
			'surface'          => $surface,
			'contract_surface' => $contract_surface,
		);
	}

	/**
	 * Builds a placement contract for currently parsed template blocks.
	 *
	 * @param string                   $intent Intent.
	 * @param string                   $slug Template slug.
	 * @param array<int,array<mixed>>  $blocks Blocks.
	 * @param bool                     $show_on_home_page Whether breadcrumbs should show on home templates.
	 * @return array<string,mixed>
	 */
	private function gutenberg_block_current_template_placement_contract( string $intent, string $slug, array $blocks, bool $show_on_home_page ): array {
		$slug             = sanitize_key( $slug );
		$breadcrumb_count = $this->gutenberg_block_count_blocks_by_match( $blocks, 'breadcrumbs' );
		$is_home_template = in_array( $slug, array( 'front-page', 'home' ), true );
		$anchor           = '';
		$placement        = array(
			'slug'            => $slug,
			'status'          => 'missing',
			'strategy'        => 'missing_breadcrumb',
			'container'       => '',
			'inserted_before' => '',
		);

		if ( $is_home_template && ! $show_on_home_page && 0 === $breadcrumb_count ) {
			$placement = array(
				'slug'            => $slug,
				'status'          => 'skipped',
				'strategy'        => 'home_page_disabled',
				'container'       => 'home_template',
				'inserted_before' => '',
			);
		} elseif ( $is_home_template && ! $show_on_home_page && $breadcrumb_count > 0 ) {
			$placement = array(
				'slug'            => $slug,
				'status'          => 'misplaced',
				'strategy'        => 'home_page_breadcrumb_present',
				'container'       => 'home_template',
				'inserted_before' => '',
			);
		} elseif ( $this->gutenberg_block_breadcrumbs_before_title_anchor_in_main( $blocks, $anchor ) ) {
			$placement = array(
				'slug'            => $slug,
				'status'          => 'already_valid',
				'strategy'        => 'before_post_title_in_main',
				'container'       => 'main',
				'inserted_before' => $anchor,
			);
		} elseif ( $breadcrumb_count > 0 && $this->gutenberg_block_has_top_level_breadcrumb_before_header( $blocks ) ) {
			$placement = array(
				'slug'            => $slug,
				'status'          => 'misplaced',
				'strategy'        => 'above_header',
				'container'       => 'template_root',
				'inserted_before' => '',
			);
		} elseif ( $breadcrumb_count > 0 ) {
			$placement = array(
				'slug'            => $slug,
				'status'          => 'misplaced',
				'strategy'        => 'breadcrumb_not_before_title',
				'container'       => 'unknown',
				'inserted_before' => '',
			);
		}

		return $this->gutenberg_block_template_placement_contract( $intent, array( $placement ) );
	}

	/**
	 * Returns next action codes from contract status.
	 *
	 * @param array<string,mixed> $composition Composition contract.
	 * @param array<string,mixed> $placement Placement contract.
	 * @param array<string,mixed> $surface Surface metadata.
	 * @return array<int,string>
	 */
	private function gutenberg_block_contract_next_actions( array $composition, array $placement, array $surface ): array {
		$next_actions = array();
		if ( 'pass' !== (string) ( $composition['contract_status'] ?? '' ) ) {
			$next_actions[] = 'revise_block_composition';
		}
		if ( ! empty( $placement ) && 'pass' !== (string) ( $placement['contract_status'] ?? '' ) ) {
			$next_actions[] = 'build_block_theme_site_plan';
		}
		if ( empty( $next_actions ) ) {
			$next_actions[] = 'no_changes_required';
		}
		if ( 'blocks_input' === (string) ( $surface['surface_kind'] ?? '' ) && 'no_changes_required' !== $next_actions[0] ) {
			$next_actions[] = 'choose_target_block_editor_surface';
		}
		return array_values( array_unique( $next_actions ) );
	}

	/**
	 * Counts all parsed blocks.
	 *
	 * @param array<int,mixed> $blocks Blocks.
	 * @return int
	 */
	private function gutenberg_block_count_blocks( array $blocks ): int {
		$count = 0;
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			++$count;
			$count += $this->gutenberg_block_count_blocks( is_array( $block['innerBlocks'] ?? null ) ? $block['innerBlocks'] : array() );
		}
		return $count;
	}

	/**
	 * Counts blocks matching a supported contract role.
	 *
	 * @param array<int,mixed> $blocks Blocks.
	 * @param string           $match Match name.
	 * @return int
	 */
	private function gutenberg_block_count_blocks_by_match( array $blocks, string $match ): int {
		$count = 0;
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			if ( 'breadcrumbs' === $match && $this->gutenberg_block_is_breadcrumbs_block( $block ) ) {
				++$count;
			}
			$count += $this->gutenberg_block_count_blocks_by_match( is_array( $block['innerBlocks'] ?? null ) ? $block['innerBlocks'] : array(), $match );
		}
		return $count;
	}

	/**
	 * Reports whether breadcrumbs are immediately before a title anchor in main.
	 *
	 * @param mixed  $blocks Blocks.
	 * @param string $anchor Matched title anchor.
	 * @param bool   $inside_main Whether current recursion is inside main.
	 * @return bool
	 */
	private function gutenberg_block_breadcrumbs_before_title_anchor_in_main( $blocks, &$anchor = '', bool $inside_main = false ): bool {
		$blocks = is_array( $blocks ) ? $blocks : array();
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			$block_is_main = $this->block_theme_is_main_container( $block );
			$in_main_tree  = $inside_main || $block_is_main;
			if ( $in_main_tree ) {
				$inner_blocks = is_array( $block['innerBlocks'] ?? null ) ? array_values( $block['innerBlocks'] ) : array();
				foreach ( $inner_blocks as $index => $inner_block ) {
					if ( ! is_array( $inner_block ) || ! $this->gutenberg_block_is_title_anchor_block( $inner_block ) ) {
						continue;
					}
					$previous = $inner_blocks[ $index - 1 ] ?? null;
					if ( $index > 0 && is_array( $previous ) && $this->gutenberg_block_is_breadcrumbs_block( $previous ) ) {
						$anchor = (string) ( $inner_block['blockName'] ?? '' );
						return true;
					}
				}
			}
			if ( $this->gutenberg_block_breadcrumbs_before_title_anchor_in_main( $block['innerBlocks'] ?? array(), $anchor, $in_main_tree ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns whether a top-level breadcrumb appears before a header template part.
	 *
	 * @param array<int,mixed> $blocks Blocks.
	 * @return bool
	 */
	private function gutenberg_block_has_top_level_breadcrumb_before_header( array $blocks ): bool {
		$breadcrumb_index = null;
		$header_index     = null;
		foreach ( array_values( $blocks ) as $index => $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			if ( null === $breadcrumb_index && $this->gutenberg_block_is_breadcrumbs_block( $block ) ) {
				$breadcrumb_index = (int) $index;
			}
			if ( null === $header_index && 'core/template-part' === (string) ( $block['blockName'] ?? '' ) && 'header' === sanitize_key( (string) ( $block['attrs']['slug'] ?? '' ) ) ) {
				$header_index = (int) $index;
			}
		}
		return null !== $breadcrumb_index && null !== $header_index && $breadcrumb_index < $header_index;
	}

	/**
	 * Reports whether one block is a supported title anchor.
	 *
	 * @param array<string,mixed> $block Parsed block.
	 * @return bool
	 */
	private function gutenberg_block_is_title_anchor_block( array $block ): bool {
		return in_array( (string) ( $block['blockName'] ?? '' ), array( 'core/post-title', 'core/query-title' ), true );
	}

	/**
	 * Reports whether one parsed block is the OpenClaw breadcrumbs block.
	 *
	 * @param array<string,mixed> $block Parsed block.
	 * @return bool
	 */
	private function gutenberg_block_is_breadcrumbs_block( array $block ): bool {
		return false !== strpos( ' ' . (string) ( $block['attrs']['className'] ?? '' ) . ' ', ' openclaw-breadcrumbs ' );
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
