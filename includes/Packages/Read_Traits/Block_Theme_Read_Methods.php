<?php
/**
 * Block theme planning methods for Core_Read_Package.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides read-only block theme context and governed site configuration plans.
 */
trait Block_Theme_Read_Methods {
	/**
	 * Returns compact Site Editor context for a block theme.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_block_theme_context( $input ) {
		$input = is_array( $input ) ? $input : array();
		unset( $input );
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to read block theme context.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$theme = $this->block_theme_active_theme_summary();
		$site_context = $this->block_theme_site_context_snapshot();
		return array(
			'active_theme'         => $theme,
			'is_block_theme'       => $this->block_theme_is_active_block_theme(),
			'templates'            => $this->block_theme_template_rows( 'wp_template', array( 'single', 'page', 'front-page', 'archive', 'index' ) ),
			'template_parts'       => $this->block_theme_template_rows( 'wp_template_part', array( 'header', 'footer' ) ),
			'site_context_snapshot' => $site_context,
			'reading_settings'     => $site_context['reading_settings'],
			'template_resolution'  => $site_context['template_resolution'],
			'content_inventory'    => $site_context['content_inventory'],
			'existing_overrides'   => $site_context['existing_overrides'],
			'navigation_available' => post_type_exists( 'wp_navigation' ),
			'global_styles_note'   => 'Global styles are read-only context in this MVP; use reviewed diffs before adding a patch-global-styles write profile.',
			'write_posture'        => 'core_proposal_handoff',
		);
	}

	/**
	 * Returns parsed blocks for one wp_template post.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_template_blocks( $input ) {
		return $this->get_block_theme_entity_blocks( $input, 'wp_template' );
	}

	/**
	 * Returns parsed blocks for one wp_template_part post.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_template_part_blocks( $input ) {
		return $this->get_block_theme_entity_blocks( $input, 'wp_template_part' );
	}

	/**
	 * Inspects block theme surfaces and returns stable issue codes for planning.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function inspect_block_theme_surface( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to inspect block theme surfaces.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}
		if ( ! $this->block_theme_is_active_block_theme() ) {
			return new \WP_Error( 'npcink_abilities_toolkit_block_theme_required', __( 'The active theme is not reported as a block theme.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$intent           = sanitize_key( (string) ( $input['intent'] ?? 'add_breadcrumbs' ) );
		$target_templates = $this->block_theme_target_slugs( $input['target_templates'] ?? array( 'single', 'page', 'front-page' ), true );
		$show_on_home     = ! empty( $input['show_on_home_page'] );
		$templates        = array();
		$warnings         = array();
		$fixable_targets  = array();
		$issue_counts     = array();

		foreach ( $target_templates as $requested_template_slug ) {
			$requested_template_slug = sanitize_key( (string) $requested_template_slug );
			$template_resolution     = $this->block_theme_resolve_template_target( 'wp_template', $requested_template_slug );
			$template                = is_array( $template_resolution['entity'] ?? null ) ? $template_resolution['entity'] : null;
			if ( empty( $template ) ) {
				$warning = array(
					'target_type'         => 'wp_template',
					'slug'                => $requested_template_slug,
					'issue_codes'         => array( 'template_not_found' ),
					'reason'              => 'template_not_found',
					'template_resolution' => $this->block_theme_public_template_resolution( $template_resolution ),
				);
				$warnings[]   = $warning;
				$templates[]  = $this->block_theme_inspection_missing_template_row( $warning );
				$issue_counts['template_not_found'] = (int) ( $issue_counts['template_not_found'] ?? 0 ) + 1;
				continue;
			}

			$template_slug = sanitize_key( (string) ( $template_resolution['target_slug'] ?? $requested_template_slug ) );
			$blocks        = function_exists( 'parse_blocks' ) ? parse_blocks( (string) ( $template['content'] ?? '' ) ) : array();
			$blocks        = $this->block_theme_blocks_for_write_plan( $blocks );
			$row           = $this->block_theme_inspect_breadcrumb_template(
				$template,
				$template_resolution,
				$template_slug,
				$blocks,
				$show_on_home
			);
			if ( ! empty( $row['fixable_issue_codes'] ) && ! $this->block_theme_site_plan_accepts_template( $template_slug ) ) {
				$row['status']              = 'blocked';
				$row['fixable_issue_codes'] = array();
				$row['dual_review']         = $this->block_theme_template_dual_review( $row['issue_codes'], array() );
				$warnings[] = array(
					'target_type'         => 'wp_template',
					'slug'                => $template_slug,
					'reason'              => 'template_plan_target_not_supported',
					'template_resolution' => $this->block_theme_public_template_resolution( $template_resolution ),
				);
			}
			foreach ( $row['issue_codes'] as $issue_code ) {
				$issue_counts[ $issue_code ] = (int) ( $issue_counts[ $issue_code ] ?? 0 ) + 1;
			}
			if ( ! empty( $row['fixable_issue_codes'] ) && ! in_array( $template_slug, $fixable_targets, true ) ) {
				$fixable_targets[] = $template_slug;
			}
			$templates[] = $row;
		}

		$recommended_plan_input = empty( $fixable_targets ) ? array() : array(
			'intent'             => 'add_breadcrumbs',
			'target_templates'   => $fixable_targets,
			'separator'          => sanitize_text_field( (string) ( $input['separator'] ?? '/' ) ),
			'show_current_item'  => ! array_key_exists( 'show_current_item', $input ) || ! empty( $input['show_current_item'] ),
			'show_home_item'     => ! array_key_exists( 'show_home_item', $input ) || ! empty( $input['show_home_item'] ),
			'show_on_home_page'  => $show_on_home,
		);
		$dual_review = $this->block_theme_surface_dual_review( $templates, $warnings, $recommended_plan_input );

		return $this->build_analysis_success_response(
			array(
				'artifact_type'             => 'block_theme_surface_inspection',
				'version'                   => 1,
				'intent'                    => $intent,
				'active_theme'              => $this->block_theme_active_theme_summary(),
				'is_block_theme'            => true,
				'evaluation_mode'           => 'inspect_only',
				'direct_wordpress_write'    => false,
				'commit_execution'          => false,
				'proposal_created'          => false,
				'supported_issue_codes'     => $this->block_theme_supported_surface_issue_codes(),
				'issue_counts'              => $issue_counts,
				'affected_templates'        => $fixable_targets,
				'templates'                 => $templates,
				'warnings'                  => $warnings,
				'recommended_plan_ability_id' => empty( $recommended_plan_input ) ? '' : 'npcink-abilities-toolkit/build-block-theme-site-plan',
				'recommended_plan_input'    => $recommended_plan_input,
				'review_contract'           => $this->block_theme_surface_review_contract(),
				'dual_review'               => $dual_review,
				'next_steps'                => $this->block_theme_surface_next_steps( $dual_review ),
			),
			array(
				'source'         => 'block_theme_surface_inspector',
				'execution_mode' => 'deterministic',
			),
			'Block theme surface inspected.'
		);
	}

	/**
	 * Builds a governed block theme site plan.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function build_block_theme_site_plan( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to plan block theme changes.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$intent = sanitize_key( (string) ( $input['intent'] ?? 'add_breadcrumbs' ) );
		if ( 'customize_template_layout' === $intent ) {
			return $this->build_block_theme_template_layout_plan( $input );
		}
		if ( 'add_breadcrumbs' !== $intent ) {
			return new \WP_Error( 'npcink_abilities_toolkit_block_theme_intent_invalid', __( 'Block theme site plans currently support intent=add_breadcrumbs or intent=customize_template_layout only.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( ! $this->block_theme_is_active_block_theme() ) {
			return new \WP_Error( 'npcink_abilities_toolkit_block_theme_required', __( 'The active theme is not reported as a block theme.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$target_templates = $this->block_theme_target_slugs( $input['target_templates'] ?? array( 'single', 'page', 'front-page' ) );
		$separator        = sanitize_text_field( (string) ( $input['separator'] ?? '/' ) );
		$show_current     = ! array_key_exists( 'show_current_item', $input ) || ! empty( $input['show_current_item'] );
		$show_home        = ! array_key_exists( 'show_home_item', $input ) || ! empty( $input['show_home_item'] );
		$show_on_home     = ! empty( $input['show_on_home_page'] );
		$write_actions    = array();
		$preview          = array();
		$warnings         = array();
		$block_editor_reviews = array();
		$planned_template_slugs = array();
		$placement_contract_rows = array();
		$composition_contract_blocks = array();

		foreach ( $target_templates as $template_slug ) {
			$requested_template_slug = sanitize_key( (string) $template_slug );
			$template_resolution     = $this->block_theme_resolve_template_target( 'wp_template', $requested_template_slug );
			$template                = is_array( $template_resolution['entity'] ?? null ) ? $template_resolution['entity'] : null;
			if ( empty( $template ) ) {
				$warnings[] = array(
					'target_type'         => 'wp_template',
					'slug'                => $requested_template_slug,
					'reason'              => 'template_not_found',
					'template_resolution' => $this->block_theme_public_template_resolution( $template_resolution ),
				);
				continue;
			}

			$target_template_slug = sanitize_key( (string) ( $template_resolution['target_slug'] ?? $requested_template_slug ) );
			if ( in_array( $target_template_slug, $planned_template_slugs, true ) ) {
				$warnings[] = array(
					'target_type'         => 'wp_template',
					'slug'                => $requested_template_slug,
					'resolved_slug'       => $target_template_slug,
					'reason'              => 'template_already_planned',
					'template_resolution' => $this->block_theme_public_template_resolution( $template_resolution ),
				);
				continue;
			}
			$planned_template_slugs[] = $target_template_slug;

			$template_id     = absint( $template_resolution['target_post_id'] ?? 0 );
			$template_slug   = $target_template_slug;
			$template_theme  = sanitize_key( (string) ( $template['theme'] ?? '' ) );
			$template_title  = sanitize_text_field( (string) ( $template_resolution['target_title'] ?? $template['title'] ?? $template_slug ) );
			$template_source = sanitize_key( (string) ( $template['source'] ?? '' ) );
			$current_blocks       = function_exists( 'parse_blocks' ) ? parse_blocks( (string) ( $template['content'] ?? '' ) ) : array();
			$current_blocks       = $this->block_theme_blocks_for_write_plan( $current_blocks );
			$breadcrumb_placement = array();
			$next_blocks          = $this->block_theme_insert_breadcrumbs_block(
				$current_blocks,
				array(
					'separator'          => $separator,
					'showCurrentItem'    => $show_current,
					'showHomeItem'       => $show_home,
					'showOnHomePage'     => $show_on_home,
					'templateSlug'        => $template_slug,
				),
				$breadcrumb_placement
			);
			$placement_contract_rows[] = array_merge( array( 'slug' => $template_slug ), $breadcrumb_placement );
			foreach ( $next_blocks as $next_block ) {
				if ( is_array( $next_block ) ) {
					$composition_contract_blocks[] = $next_block;
				}
			}
			$requires_write      = $this->block_theme_blocks_require_write( $current_blocks, $next_blocks );
			$block_editor_review = $this->pattern_review_summary_for_blocks( $next_blocks, true );
			$is_existing_custom_template = $template_id > 0;
			$target_ability_id           = $is_existing_custom_template ? 'npcink-abilities-toolkit/update-template-blocks' : 'npcink-abilities-toolkit/upsert-template-blocks';
			$block_editor_quality_gate   = $this->block_editor_plan_quality_gate(
				$block_editor_review,
				array(
					'profile'      => 'site_editor_template_safety',
					'surface_kind' => 'site_editor_template',
					'editor'       => 'site_editor',
					'post_type'    => 'wp_template',
					'target_mode'  => $is_existing_custom_template ? 'update_existing' : 'create_template_override',
					'gate_id'      => 'block_editor_quality_gate_' . sanitize_key( $template_slug ),
				)
			);
			if ( ! $requires_write ) {
				$preview[] = array(
					'target_type'                    => 'wp_template',
					'post_id'                        => $template_id,
					'slug'                           => $template_slug,
					'theme'                          => $template_theme,
					'source'                         => $template_source,
					'creates_template_override'      => false,
					'would_create_template_override' => ! $is_existing_custom_template,
					'target_ability_id'              => '',
					'requires_write'                 => false,
					'no_change_reason'               => $this->block_theme_no_change_reason( $breadcrumb_placement ),
					'template_resolution'            => $this->block_theme_public_template_resolution( $template_resolution ),
					'block_count_before'             => $this->block_theme_count_blocks( $current_blocks ),
					'block_count_after'              => $this->block_theme_count_blocks( $next_blocks ),
					'inserted_block'                 => 'core/group.openclaw-breadcrumbs',
					'breadcrumb_placement'           => $breadcrumb_placement,
					'block_editor_quality_gate'      => $block_editor_quality_gate,
				);
				continue;
			}
			$action_input                = array(
				'mode'               => 'replace',
				'validate_roundtrip' => true,
				'blocks'             => $next_blocks,
			);
			if ( $is_existing_custom_template ) {
				$action_input['post_id'] = $template_id;
			} else {
				$action_input['slug']               = $template_slug;
				$action_input['theme']              = $template_theme;
				$action_input['title']              = $template_title;
				$action_input['source_template_id'] = sanitize_text_field( (string) ( $template['template_id'] ?? '' ) );
			}
			$action_id       = ( $is_existing_custom_template ? 'update-template-' : 'upsert-template-' ) . sanitize_key( $template_slug ) . '-breadcrumbs';
			$write_actions[] = $this->build_plan_action(
				$action_id,
				$target_ability_id,
				$action_input,
				array( 'site.write' ),
				'high',
				$is_existing_custom_template
					? __( 'Update one existing Site Editor template with reviewed breadcrumb blocks after Core approval.', 'npcink-abilities-toolkit' )
					: __( 'Create a reviewed Site Editor template override from the active theme file template after Core approval.', 'npcink-abilities-toolkit' )
			);
			$preview[] = array(
				'target_type'               => 'wp_template',
				'post_id'                   => $template_id,
				'slug'                      => $template_slug,
				'theme'                     => $template_theme,
				'source'                    => $template_source,
				'creates_template_override' => ! $is_existing_custom_template,
				'target_ability_id'         => $target_ability_id,
				'requires_write'            => true,
				'template_resolution'       => $this->block_theme_public_template_resolution( $template_resolution ),
				'block_count_before'        => $this->block_theme_count_blocks( $current_blocks ),
				'block_count_after'         => $this->block_theme_count_blocks( $next_blocks ),
				'inserted_block'            => 'core/group.openclaw-breadcrumbs',
				'breadcrumb_placement'      => $breadcrumb_placement,
				'block_editor_quality_gate' => $block_editor_quality_gate,
			);
			$block_editor_reviews[] = array(
				'target_type'               => 'wp_template',
				'post_id'                   => $template_id,
				'slug'                      => $template_slug,
				'theme'                     => $template_theme,
				'target_ability_id'         => $target_ability_id,
				'creates_template_override' => ! $is_existing_custom_template,
				'template_resolution'       => $this->block_theme_public_template_resolution( $template_resolution ),
				'breadcrumb_placement'      => $breadcrumb_placement,
				'review'                    => $this->block_editor_plan_review_excerpt( $block_editor_review ),
				'quality_gate'              => $block_editor_quality_gate,
			);
		}

		$batch_seed = wp_json_encode( array( $intent, $target_templates, $separator, $show_current, $show_home, $show_on_home ) );
		$batch_id   = 'block_theme_site_' . substr( md5( is_string( $batch_seed ) ? $batch_seed : $intent ), 0, 12 );
		$batch_blocking_targets = array();
		foreach ( $block_editor_reviews as $block_editor_review_row ) {
			if ( empty( $block_editor_review_row['quality_gate']['ready_for_proposal'] ) ) {
				$batch_blocking_targets[] = sanitize_key( (string) ( $block_editor_review_row['slug'] ?? '' ) );
			}
		}
		$batch_ready_for_proposal = ! empty( $write_actions ) && empty( $batch_blocking_targets );
		$block_editor_quality_gate = array(
			'gate_id'             => 'block_editor_quality_gate',
			'review_ability_id'   => 'npcink-abilities-toolkit/review-block-editor-surface',
			'profile'             => 'site_editor_template_batch',
			'surface_kind'        => 'site_editor_template',
			'editor'              => 'site_editor',
			'post_type'           => 'wp_template',
			'target_mode'         => 'update_or_create_template_override',
			'review_count'        => count( $block_editor_reviews ),
			'action_count'        => count( $write_actions ),
			'blocking_targets'    => array_values( array_filter( $batch_blocking_targets ) ),
			'ready_for_proposal'  => $batch_ready_for_proposal,
			'recommended_next_step' => $this->block_theme_batch_next_step( $batch_ready_for_proposal, $write_actions, $preview, $warnings ),
			'direct_wordpress_write' => false,
			'commit_execution'    => false,
		);

		return $this->build_analysis_success_response(
			array(
				'artifact_type'          => 'block_theme_site_plan',
				'composition_role'       => 'block_theme_site_builder_plan',
				'version'                => 1,
				'intent'                 => $intent,
				'active_theme'           => $this->block_theme_active_theme_summary(),
				'write_posture'          => 'core_proposal_handoff',
				'direct_wordpress_write' => false,
				'requires_approval'      => true,
				'dry_run'                => true,
				'commit_execution'       => false,
				'proposal_mode'          => 'batch',
				'batch_id'               => $batch_id,
				'affected_templates'     => $target_templates,
				'block_editor_surface'   => array(
					'surface_kind'      => 'site_editor_template',
					'editor'            => 'site_editor',
					'post_types'        => array( 'wp_template' ),
					'target_mode'       => 'update_or_create_template_override',
					'write_ability_ids' => array(
						'npcink-abilities-toolkit/update-template-blocks',
						'npcink-abilities-toolkit/upsert-template-blocks',
					),
				),
				'composition_contract'   => $this->gutenberg_block_composition_contract( $composition_contract_blocks, 'template' ),
				'template_placement_contract' => $this->gutenberg_block_template_placement_contract( $intent, $placement_contract_rows ),
				'preview'                => $preview,
				'warnings'               => $warnings,
				'block_editor_reviews'   => $block_editor_reviews,
				'block_editor_quality_gate' => $block_editor_quality_gate,
				'risk'                   => array(
					'level'   => 'high',
					'reasons' => array( 'site_editor_template_change', 'affects_multiple_frontend_views' ),
				),
				'write_actions'          => $write_actions,
				'action_count'           => count( $write_actions ),
				'handoff'                => array(
					'plan_ability_id'        => 'npcink-abilities-toolkit/build-block-theme-site-plan',
					'recipe_id'              => 'block_theme_site_builder_v1',
					'recipe_ref'             => 'workflow/block_theme_site_plan',
					'core_route'             => '/wp-json/npcink-governance-core/v1/proposals/from-plan',
					'final_write_path'       => 'core_proposal_required',
					'direct_wordpress_write' => false,
				),
			),
			array(
				'source'         => 'local_block_theme_site_plan',
				'execution_mode' => 'deterministic',
			),
			'Block theme site plan built.'
		);
	}

	/**
	 * Builds a governed block theme template layout plan from bounded profiles.
	 *
	 * @param array<string,mixed> $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function build_block_theme_template_layout_plan( array $input ) {
		if ( ! $this->block_theme_is_active_block_theme() ) {
			return new \WP_Error( 'npcink_abilities_toolkit_block_theme_required', __( 'The active theme is not reported as a block theme.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$intent           = 'customize_template_layout';
		$target_templates = $this->block_theme_target_slugs( $input['target_templates'] ?? array( 'single' ) );
		$requested_profile = sanitize_key( (string) ( $input['layout_profile'] ?? 'auto' ) );
		$variables        = is_array( $input['variables'] ?? null ) ? $input['variables'] : array();
		$options          = array(
			'include_breadcrumbs'     => ! array_key_exists( 'include_breadcrumbs', $input ) || ! empty( $input['include_breadcrumbs'] ),
			'show_author_date'        => ! array_key_exists( 'show_author_date', $input ) || ! empty( $input['show_author_date'] ),
			'show_featured_image'     => ! array_key_exists( 'show_featured_image', $input ) || ! empty( $input['show_featured_image'] ),
			'include_related_posts'   => ! array_key_exists( 'include_related_posts', $input ) || ! empty( $input['include_related_posts'] ),
			'include_latest_posts'    => ! array_key_exists( 'include_latest_posts', $input ) || ! empty( $input['include_latest_posts'] ),
			'include_category_links'  => ! array_key_exists( 'include_category_links', $input ) || ! empty( $input['include_category_links'] ),
			'include_cta'             => ! array_key_exists( 'include_cta', $input ) || ! empty( $input['include_cta'] ),
			'separator'               => sanitize_text_field( (string) ( $input['separator'] ?? '/' ) ),
			'show_current_item'       => ! array_key_exists( 'show_current_item', $input ) || ! empty( $input['show_current_item'] ),
			'show_home_item'          => ! array_key_exists( 'show_home_item', $input ) || ! empty( $input['show_home_item'] ),
			'show_on_home_page'       => ! empty( $input['show_on_home_page'] ),
		);

		$write_actions = array();
		$preview       = array();
		$warnings      = array();
		$block_editor_reviews = array();
		$composition_contract_blocks = array();
		$profile_rows  = array();
		$planned_template_slugs = array();
		$site_context  = $this->block_theme_site_context_snapshot();

		foreach ( $target_templates as $template_slug ) {
			$requested_template_slug = sanitize_key( (string) $template_slug );
			$template_resolution     = $this->block_theme_resolve_template_target( 'wp_template', $requested_template_slug );
			$template                = is_array( $template_resolution['entity'] ?? null ) ? $template_resolution['entity'] : null;
			if ( empty( $template ) ) {
				$warnings[] = array(
					'target_type'         => 'wp_template',
					'slug'                => $requested_template_slug,
					'reason'              => 'template_not_found',
					'template_resolution' => $this->block_theme_public_template_resolution( $template_resolution ),
				);
				continue;
			}

			$target_template_slug = sanitize_key( (string) ( $template_resolution['target_slug'] ?? $requested_template_slug ) );
			if ( in_array( $target_template_slug, $planned_template_slugs, true ) ) {
				$warnings[] = array(
					'target_type'         => 'wp_template',
					'slug'                => $requested_template_slug,
					'resolved_slug'       => $target_template_slug,
					'reason'              => 'template_already_planned',
					'template_resolution' => $this->block_theme_public_template_resolution( $template_resolution ),
				);
				continue;
			}
			$planned_template_slugs[] = $target_template_slug;

			$template_id     = absint( $template_resolution['target_post_id'] ?? 0 );
			$template_theme  = sanitize_key( (string) ( $template['theme'] ?? '' ) );
			$template_title  = sanitize_text_field( (string) ( $template_resolution['target_title'] ?? $template['title'] ?? $target_template_slug ) );
			$template_source = sanitize_key( (string) ( $template['source'] ?? '' ) );
			$current_blocks  = function_exists( 'parse_blocks' ) ? parse_blocks( (string) ( $template['content'] ?? '' ) ) : array();
			$current_blocks  = $this->block_theme_blocks_for_write_plan( $current_blocks );
			$layout_profile  = $this->block_theme_layout_profile_for_template( $requested_profile, $target_template_slug );
			$cta_resolution  = 'homepage_landing' === $layout_profile && ! empty( $options['include_cta'] ) ? $this->block_theme_resolve_homepage_cta( $variables, $site_context ) : array();
			$page_content_enabled = 'homepage_landing' === $layout_profile ? $this->block_theme_homepage_should_include_post_content( $target_template_slug, $site_context ) : true;
			$template_resolver = $this->block_theme_template_resolver_result( 'customize_template_layout', $requested_template_slug, $template_resolution, $site_context );
			$next_blocks     = $this->block_theme_template_layout_blocks( $target_template_slug, $layout_profile, $template_theme, $options, $variables, $site_context );
			$requires_write  = $this->block_theme_blocks_require_write( $current_blocks, $next_blocks );
			$layout_blocked_reason = '';
			foreach ( $next_blocks as $next_block ) {
				if ( is_array( $next_block ) ) {
					$composition_contract_blocks[] = $next_block;
				}
			}

			$is_existing_custom_template = $template_id > 0;
			$target_ability_id           = $is_existing_custom_template ? 'npcink-abilities-toolkit/update-template-blocks' : 'npcink-abilities-toolkit/upsert-template-blocks';
			$block_editor_review         = $this->pattern_review_summary_for_blocks( $next_blocks, true );
			$block_editor_quality_gate   = $this->block_editor_plan_quality_gate(
				$block_editor_review,
				array(
					'profile'      => 'site_editor_template_layout_safety',
					'surface_kind' => 'site_editor_template',
					'editor'       => 'site_editor',
					'post_type'    => 'wp_template',
					'target_mode'  => $is_existing_custom_template ? 'update_existing' : 'create_template_override',
					'gate_id'      => 'block_editor_quality_gate_' . sanitize_key( $target_template_slug ),
				)
			);
			if ( 'homepage_landing' === $layout_profile && ! empty( $options['include_cta'] ) && 'unresolved' === sanitize_key( (string) ( $cta_resolution['status'] ?? '' ) ) ) {
				$block_editor_quality_gate['blocking_finding_codes'] = array_values(
					array_unique(
						array_merge(
							is_array( $block_editor_quality_gate['blocking_finding_codes'] ?? null ) ? $block_editor_quality_gate['blocking_finding_codes'] : array(),
							array( 'cta_link_unresolved' )
						)
					)
				);
				$block_editor_quality_gate['ready_for_proposal']      = false;
				$block_editor_quality_gate['recommended_next_step']   = 'resolve_cta_link_before_proposal';
				$requires_write                                       = false;
				$layout_blocked_reason                                = 'cta_link_unresolved';
				$warnings[] = array(
					'target_type'         => 'wp_template',
					'slug'                => $target_template_slug,
					'reason'              => 'cta_link_unresolved',
					'template_resolution' => $this->block_theme_public_template_resolution( $template_resolution ),
				);
			}
			$layout_sections = $this->block_theme_template_layout_sections( $layout_profile, $options );
			if ( 'homepage_landing' === $layout_profile && ! $page_content_enabled ) {
				$layout_sections = array_values( array_diff( $layout_sections, array( 'post_content' ) ) );
			}
			$profile_rows[]  = array(
				'slug'             => $target_template_slug,
				'layout_profile'   => $layout_profile,
				'profile_id'       => $this->block_theme_template_layout_profile_version( $layout_profile ),
				'profile_version'  => $this->block_theme_template_layout_profile_version( $layout_profile ),
				'profile_allowed'  => true,
				'sections'         => $layout_sections,
				'template_resolver' => $template_resolver,
				'cta_resolution'   => $cta_resolution,
			);

			if ( ! $requires_write ) {
				$preview[] = array(
					'target_type'                    => 'wp_template',
					'post_id'                        => $template_id,
					'slug'                           => $target_template_slug,
					'theme'                          => $template_theme,
					'source'                         => $template_source,
					'creates_template_override'      => false,
					'would_create_template_override' => ! $is_existing_custom_template,
					'target_ability_id'              => '',
					'requires_write'                 => false,
					'no_change_reason'               => '' !== $layout_blocked_reason ? $layout_blocked_reason : 'template_layout_already_matches_profile',
					'template_resolution'            => $this->block_theme_public_template_resolution( $template_resolution ),
					'template_resolver'              => $template_resolver,
					'layout_profile'                 => $layout_profile,
					'layout_sections'                => $layout_sections,
					'cta_resolution'                 => $cta_resolution,
					'page_content_enabled'           => $page_content_enabled,
					'block_count_before'             => $this->block_theme_count_blocks( $current_blocks ),
					'block_count_after'              => $this->block_theme_count_blocks( $next_blocks ),
					'inserted_block'                 => 'bounded_template_layout_profile',
					'block_editor_quality_gate'      => $block_editor_quality_gate,
				);
				continue;
			}

			$action_input = array(
				'mode'               => 'replace',
				'validate_roundtrip' => true,
				'blocks'             => $next_blocks,
			);
			if ( $is_existing_custom_template ) {
				$action_input['post_id'] = $template_id;
			} else {
				$action_input['slug']               = $target_template_slug;
				$action_input['theme']              = $template_theme;
				$action_input['title']              = $template_title;
				$action_input['source_template_id'] = sanitize_text_field( (string) ( $template['template_id'] ?? '' ) );
			}

			$action_id       = ( $is_existing_custom_template ? 'update-template-' : 'upsert-template-' ) . sanitize_key( $target_template_slug ) . '-layout';
			$write_actions[] = $this->build_plan_action(
				$action_id,
				$target_ability_id,
				$action_input,
				array( 'site.write' ),
				'high',
				$is_existing_custom_template
					? __( 'Update one existing Site Editor template with a reviewed bounded layout profile after Core approval.', 'npcink-abilities-toolkit' )
					: __( 'Create a reviewed Site Editor template override from a bounded layout profile after Core approval.', 'npcink-abilities-toolkit' )
			);

			$preview[] = array(
				'target_type'               => 'wp_template',
				'post_id'                   => $template_id,
				'slug'                      => $target_template_slug,
				'theme'                     => $template_theme,
				'source'                    => $template_source,
				'creates_template_override' => ! $is_existing_custom_template,
				'target_ability_id'         => $target_ability_id,
				'requires_write'            => true,
				'template_resolution'       => $this->block_theme_public_template_resolution( $template_resolution ),
				'template_resolver'         => $template_resolver,
				'layout_profile'            => $layout_profile,
				'layout_sections'           => $layout_sections,
				'cta_resolution'            => $cta_resolution,
				'page_content_enabled'      => $page_content_enabled,
				'block_count_before'        => $this->block_theme_count_blocks( $current_blocks ),
				'block_count_after'         => $this->block_theme_count_blocks( $next_blocks ),
				'inserted_block'            => 'bounded_template_layout_profile',
				'block_editor_quality_gate' => $block_editor_quality_gate,
			);
			$block_editor_reviews[] = array(
				'target_type'               => 'wp_template',
				'post_id'                   => $template_id,
				'slug'                      => $target_template_slug,
				'theme'                     => $template_theme,
				'target_ability_id'         => $target_ability_id,
				'creates_template_override' => ! $is_existing_custom_template,
				'template_resolution'       => $this->block_theme_public_template_resolution( $template_resolution ),
				'template_resolver'         => $template_resolver,
				'layout_profile'            => $layout_profile,
				'layout_sections'           => $layout_sections,
				'cta_resolution'            => $cta_resolution,
				'page_content_enabled'      => $page_content_enabled,
				'review'                    => $this->block_editor_plan_review_excerpt( $block_editor_review ),
				'quality_gate'              => $block_editor_quality_gate,
			);
		}

		$batch_seed = wp_json_encode( array( $intent, $target_templates, $requested_profile, $options ) );
		$batch_id   = 'block_theme_layout_' . substr( md5( is_string( $batch_seed ) ? $batch_seed : $intent ), 0, 12 );
		$batch_blocking_targets = array();
		foreach ( $block_editor_reviews as $block_editor_review_row ) {
			if ( empty( $block_editor_review_row['quality_gate']['ready_for_proposal'] ) ) {
				$batch_blocking_targets[] = sanitize_key( (string) ( $block_editor_review_row['slug'] ?? '' ) );
			}
		}
		$batch_ready_for_proposal = ! empty( $write_actions ) && empty( $batch_blocking_targets );
		$block_editor_quality_gate = array(
			'gate_id'             => 'block_editor_quality_gate',
			'review_ability_id'   => 'npcink-abilities-toolkit/review-block-editor-surface',
			'profile'             => 'site_editor_template_layout_batch',
			'surface_kind'        => 'site_editor_template',
			'editor'              => 'site_editor',
			'post_type'           => 'wp_template',
			'target_mode'         => 'update_or_create_template_override',
			'review_count'        => count( $block_editor_reviews ),
			'action_count'        => count( $write_actions ),
			'blocking_targets'    => array_values( array_filter( $batch_blocking_targets ) ),
			'ready_for_proposal'  => $batch_ready_for_proposal,
			'recommended_next_step' => $this->block_theme_batch_next_step( $batch_ready_for_proposal, $write_actions, $preview, $warnings ),
			'direct_wordpress_write' => false,
			'commit_execution'    => false,
		);

		return $this->build_analysis_success_response(
			array(
				'artifact_type'          => 'block_theme_site_plan',
				'composition_role'       => 'block_theme_template_layout_plan',
				'version'                => 1,
				'intent'                 => $intent,
				'layout_profile'         => $requested_profile,
				'active_theme'           => $this->block_theme_active_theme_summary(),
				'write_posture'          => 'core_proposal_handoff',
				'direct_wordpress_write' => false,
				'requires_approval'      => true,
				'dry_run'                => true,
				'commit_execution'       => false,
				'proposal_mode'          => 'batch',
				'batch_id'               => $batch_id,
				'affected_templates'     => $target_templates,
				'site_context_snapshot'  => $site_context,
				'block_editor_surface'   => array(
					'surface_kind'      => 'site_editor_template',
					'editor'            => 'site_editor',
					'post_types'        => array( 'wp_template' ),
					'target_mode'       => 'update_or_create_template_override',
					'write_ability_ids' => array(
						'npcink-abilities-toolkit/update-template-blocks',
						'npcink-abilities-toolkit/upsert-template-blocks',
					),
				),
				'composition_contract'   => $this->gutenberg_block_composition_contract( $composition_contract_blocks, 'template' ),
				'template_layout_contract' => $this->block_theme_template_layout_contract( $profile_rows ),
				'preview'                => $preview,
				'warnings'               => $warnings,
				'block_editor_reviews'   => $block_editor_reviews,
				'block_editor_quality_gate' => $block_editor_quality_gate,
				'risk'                   => array(
					'level'   => 'high',
					'reasons' => array( 'site_editor_template_change', 'affects_multiple_frontend_views', 'replaces_template_layout' ),
				),
				'write_actions'          => $write_actions,
				'action_count'           => count( $write_actions ),
				'handoff'                => array(
					'plan_ability_id'        => 'npcink-abilities-toolkit/build-block-theme-site-plan',
					'recipe_id'              => 'block_theme_template_layout_v1',
					'recipe_ref'             => 'workflow/block_theme_site_plan',
					'core_route'             => '/wp-json/npcink-governance-core/v1/proposals/from-plan',
					'final_write_path'       => 'core_proposal_required',
					'direct_wordpress_write' => false,
				),
			),
			array(
				'source'         => 'local_block_theme_template_layout_plan',
				'execution_mode' => 'deterministic',
			),
			'Block theme template layout plan built.'
		);
	}

	/**
	 * Chooses one bounded layout profile for a template target.
	 *
	 * @param string $requested_profile Requested profile.
	 * @param string $template_slug Template slug.
	 * @return string
	 */
	private function block_theme_layout_profile_for_template( $requested_profile, $template_slug ) {
		$requested_profile = sanitize_key( (string) $requested_profile );
		$template_slug      = sanitize_key( (string) $template_slug );
		$allowed_profiles   = array( 'article_standard', 'page_standard', 'homepage_landing' );
		if ( in_array( $requested_profile, $allowed_profiles, true ) ) {
			return $requested_profile;
		}
		if ( in_array( $template_slug, array( 'front-page', 'home' ), true ) ) {
			return 'homepage_landing';
		}
		if ( 'page' === $template_slug ) {
			return 'page_standard';
		}
		return 'article_standard';
	}

	/**
	 * Builds a bounded template block tree for one profile.
	 *
	 * @param string              $template_slug Template slug.
	 * @param string              $layout_profile Layout profile.
	 * @param string              $template_theme Template theme.
	 * @param array<string,mixed> $options Layout options.
	 * @param array<string,mixed> $variables Copy variables.
	 * @param array<string,mixed> $site_context Site context snapshot.
	 * @return array<int,array<string,mixed>>
	 */
	private function block_theme_template_layout_blocks( $template_slug, $layout_profile, $template_theme, array $options, array $variables, array $site_context = array() ) {
		$template_slug   = sanitize_key( (string) $template_slug );
		$layout_profile  = sanitize_key( (string) $layout_profile );
		$template_theme   = sanitize_key( (string) $template_theme );
		$template_theme   = '' !== $template_theme ? $template_theme : sanitize_key( (string) ( $this->block_theme_active_theme_summary()['stylesheet'] ?? '' ) );
		$main_inner_blocks = array();

		if ( 'homepage_landing' === $layout_profile ) {
			$main_inner_blocks = $this->block_theme_homepage_landing_inner_blocks( $template_slug, $options, $variables, $site_context );
		} elseif ( 'page_standard' === $layout_profile ) {
			$main_inner_blocks = $this->block_theme_page_standard_inner_blocks( $template_slug, $options );
		} else {
			$main_inner_blocks = $this->block_theme_article_standard_inner_blocks( $template_slug, $options );
		}

		return array(
			$this->block_theme_template_part_block( 'header', $template_theme ),
			$this->block_theme_semantic_group_block(
				'openclaw-template-layout openclaw-template-layout-' . $layout_profile,
				$main_inner_blocks,
				array(
					'tagName' => 'main',
					'layout'  => array(
						'type' => 'constrained',
					),
					'style'   => array(
						'spacing' => array(
							'padding' => array(
								'top'    => 'var:preset|spacing|50',
								'bottom' => 'var:preset|spacing|60',
							),
						),
					),
				)
			),
			$this->block_theme_template_part_block( 'footer', $template_theme ),
		);
	}

	/**
	 * Builds a group block with markup that matches the requested semantic tag.
	 *
	 * @param string                         $class_name CSS classes.
	 * @param array<int,array<string,mixed>> $inner_blocks Inner blocks.
	 * @param array<string,mixed>            $attrs Block attrs.
	 * @return array<string,mixed>
	 */
	private function block_theme_semantic_group_block( $class_name, array $inner_blocks, array $attrs = array() ) {
		$class_name = $this->pattern_class_names( $class_name );
		$attrs      = array_merge( $attrs, array( 'className' => $class_name ) );
		$classes    = $this->pattern_group_classes( $attrs );
		$style_attr = $this->pattern_style_from_attrs( $attrs );
		$style_html = '' !== $style_attr ? ' style="' . $this->pattern_attr( $style_attr ) . '"' : '';
		$tag_name   = sanitize_key( (string) ( $attrs['tagName'] ?? 'div' ) );
		$tag_name   = in_array( $tag_name, array( 'div', 'main', 'section', 'article', 'header', 'footer' ), true ) ? $tag_name : 'div';
		$open_tag   = '<' . $tag_name . ' class="' . $this->pattern_attr( $classes ) . '"' . $style_html . '>';
		$close_tag  = '</' . $tag_name . '>';
		return array(
			'blockName'    => 'core/group',
			'attrs'        => $attrs,
			'innerHTML'    => $open_tag . $close_tag,
			'innerContent' => array_merge( array( $open_tag ), array_fill( 0, count( $inner_blocks ), null ), array( $close_tag ) ),
			'innerBlocks'  => array_values( $inner_blocks ),
		);
	}

	/**
	 * Builds article template profile blocks.
	 *
	 * @param string              $template_slug Template slug.
	 * @param array<string,mixed> $options Layout options.
	 * @return array<int,array<string,mixed>>
	 */
	private function block_theme_article_standard_inner_blocks( $template_slug, array $options ) {
		$inner_blocks = array();
		if ( ! empty( $options['include_breadcrumbs'] ) ) {
			$inner_blocks[] = $this->block_theme_breadcrumbs_block(
				array(
					'separator'       => (string) ( $options['separator'] ?? '/' ),
					'showCurrentItem' => ! empty( $options['show_current_item'] ),
					'showHomeItem'    => ! empty( $options['show_home_item'] ),
					'templateSlug'     => sanitize_key( (string) $template_slug ),
				)
			);
		}

		$title_stack = array(
			$this->block_theme_dynamic_block(
				'core/post-title',
				array(
					'level' => 1,
				)
			),
		);
		if ( ! empty( $options['show_author_date'] ) ) {
			$title_stack[] = $this->pattern_group_block(
				'openclaw-template-meta',
				array(
					$this->pattern_paragraph_block( __( '作者：', 'npcink-abilities-toolkit' ), 'openclaw-template-meta-label' ),
					$this->block_theme_dynamic_block( 'core/post-author-name', array( 'isLink' => true ) ),
					$this->pattern_paragraph_block( '/', 'openclaw-template-meta-separator' ),
					$this->block_theme_dynamic_block( 'core/post-date', array( 'isLink' => false ) ),
					$this->pattern_paragraph_block( '/', 'openclaw-template-meta-separator' ),
					$this->block_theme_dynamic_block( 'core/post-terms', array( 'term' => 'category' ) ),
				),
				array(
					'layout' => array(
						'type'     => 'flex',
						'flexWrap' => 'wrap',
					),
				)
			);
		}
		$inner_blocks[] = $this->pattern_group_block(
			'openclaw-template-title-stack',
			$title_stack,
			array(
				'align' => 'wide',
				'style' => array(
					'color'   => array(
						'background' => '#FBFAF3',
					),
					'spacing' => array(
						'padding' => array(
							'top'    => 'var:preset|spacing|50',
							'right'  => 'var:preset|spacing|50',
							'bottom' => 'var:preset|spacing|50',
							'left'   => 'var:preset|spacing|50',
						),
					),
				),
			)
		);

		if ( ! empty( $options['show_featured_image'] ) ) {
			$inner_blocks[] = $this->block_theme_dynamic_block(
				'core/post-featured-image',
				array(
					'aspectRatio' => '16/9',
					'align'       => 'wide',
					'className'   => 'openclaw-template-featured-image',
				)
			);
		}

		$inner_blocks[] = $this->pattern_group_block(
			'openclaw-template-content-stack',
			array(
				$this->block_theme_dynamic_block( 'core/post-content', array( 'layout' => array( 'type' => 'constrained' ) ) ),
				$this->block_theme_dynamic_block( 'core/post-terms', array( 'term' => 'post_tag' ) ),
			),
			array(
				'style' => array(
					'spacing' => array(
						'padding' => array(
							'top'    => 'var:preset|spacing|40',
							'bottom' => 'var:preset|spacing|40',
						),
					),
				),
			)
		);

		$inner_blocks[] = $this->pattern_group_block(
			'openclaw-template-post-navigation',
			array(
				$this->block_theme_dynamic_block( 'core/post-navigation-link', array( 'type' => 'previous' ) ),
				$this->block_theme_dynamic_block( 'core/post-navigation-link', array( 'type' => 'next' ) ),
			),
			array(
				'align'  => 'wide',
				'layout' => array(
					'type'           => 'flex',
					'justifyContent' => 'space-between',
					'flexWrap'       => 'wrap',
				),
				'style'  => array(
					'color'   => array(
						'background' => '#FBFAF3',
					),
					'spacing' => array(
						'padding' => array(
							'top'    => 'var:preset|spacing|40',
							'right'  => 'var:preset|spacing|40',
							'bottom' => 'var:preset|spacing|40',
							'left'   => 'var:preset|spacing|40',
						),
					),
				),
			)
		);

		$inner_blocks[] = $this->block_theme_dynamic_block( 'core/comments' );

		if ( ! empty( $options['include_related_posts'] ) ) {
			$inner_blocks[] = $this->pattern_group_block(
				'openclaw-template-related',
				array(
					$this->pattern_heading_block( __( '相关文章', 'npcink-abilities-toolkit' ), 2, 'openclaw-template-section-title' ),
					$this->block_theme_dynamic_block(
						'core/latest-posts',
						array(
							'postsToShow'     => 3,
							'displayPostDate' => true,
						)
					),
				),
				array(
					'align' => 'wide',
					'style' => array(
						'color'   => array(
							'background' => '#F1F5F9',
						),
						'spacing' => array(
							'padding' => array(
								'top'    => 'var:preset|spacing|50',
								'right'  => 'var:preset|spacing|50',
								'bottom' => 'var:preset|spacing|50',
								'left'   => 'var:preset|spacing|50',
							),
							'margin' => array(
								'top' => 'var:preset|spacing|60',
							),
						),
					),
				)
			);
		}

		return $inner_blocks;
	}

	/**
	 * Builds page template profile blocks.
	 *
	 * @param string              $template_slug Template slug.
	 * @param array<string,mixed> $options Layout options.
	 * @return array<int,array<string,mixed>>
	 */
	private function block_theme_page_standard_inner_blocks( $template_slug, array $options ) {
		$inner_blocks = array();
		if ( ! empty( $options['include_breadcrumbs'] ) ) {
			$inner_blocks[] = $this->block_theme_breadcrumbs_block(
				array(
					'separator'       => (string) ( $options['separator'] ?? '/' ),
					'showCurrentItem' => ! empty( $options['show_current_item'] ),
					'showHomeItem'    => ! empty( $options['show_home_item'] ),
					'templateSlug'     => sanitize_key( (string) $template_slug ),
				)
			);
		}
		$inner_blocks[] = $this->block_theme_dynamic_block( 'core/post-title', array( 'level' => 1 ) );
		if ( ! empty( $options['show_featured_image'] ) ) {
			$inner_blocks[] = $this->block_theme_dynamic_block(
				'core/post-featured-image',
				array(
					'aspectRatio' => '16/9',
					'className'   => 'openclaw-template-featured-image',
				)
			);
		}
		$inner_blocks[] = $this->block_theme_dynamic_block( 'core/post-content', array( 'layout' => array( 'type' => 'constrained' ) ) );
		return $inner_blocks;
	}

	/**
	 * Builds homepage landing template profile blocks.
	 *
	 * @param string              $template_slug Template slug.
	 * @param array<string,mixed> $options Layout options.
	 * @param array<string,mixed> $variables Copy variables.
	 * @param array<string,mixed> $site_context Site context snapshot.
	 * @return array<int,array<string,mixed>>
	 */
	private function block_theme_homepage_landing_inner_blocks( $template_slug, array $options, array $variables, array $site_context ) {
		$theme            = $this->block_theme_active_theme_summary();
		$site_title       = sanitize_text_field( (string) ( function_exists( 'get_bloginfo' ) ? get_bloginfo( 'name' ) : ( $theme['name'] ?? '' ) ) );
		$site_tagline     = sanitize_text_field( (string) ( function_exists( 'get_bloginfo' ) ? get_bloginfo( 'description' ) : '' ) );
		$hero_title       = sanitize_text_field( (string) ( $variables['hero_title'] ?? ( '' !== $site_title ? $site_title : __( '首页', 'npcink-abilities-toolkit' ) ) ) );
		$hero_description = sanitize_text_field( (string) ( $variables['hero_description'] ?? ( '' !== $site_tagline ? $site_tagline : __( '欢迎来到本站。', 'npcink-abilities-toolkit' ) ) ) );
		$cta_resolution   = $this->block_theme_resolve_homepage_cta( $variables, $site_context );
		$cta_label        = sanitize_text_field( (string) ( $variables['cta_label'] ?? ( $cta_resolution['label'] ?? __( '了解更多', 'npcink-abilities-toolkit' ) ) ) );
		$cta_url          = $this->esc_url_value( (string) ( $cta_resolution['url'] ?? '' ) );
		$include_page_content = $this->block_theme_homepage_should_include_post_content( $template_slug, $site_context );
		$inner_blocks     = array(
			$this->pattern_group_block(
				'openclaw-home-hero',
				array_values(
					array_filter(
					array(
						$this->pattern_heading_block( $hero_title, 1, 'openclaw-home-hero-title' ),
						$this->pattern_paragraph_block( $hero_description, 'openclaw-home-hero-description' ),
						! empty( $options['include_cta'] ) && '' !== $cta_url ? $this->block_theme_buttons_with_url_block( $cta_label, $cta_url ) : null,
					)
					)
				),
				array(
					'align' => 'wide',
					'style' => array(
						'spacing' => array(
							'padding' => array(
								'top'    => 'var:preset|spacing|60',
								'bottom' => 'var:preset|spacing|60',
							),
						),
					),
				)
			),
		);

		if ( ! empty( $options['include_latest_posts'] ) ) {
			$latest_posts_blocks = array(
				$this->pattern_heading_block( __( '最新文章', 'npcink-abilities-toolkit' ), 2, 'openclaw-template-section-title' ),
				$this->block_theme_dynamic_block(
					'core/latest-posts',
					array(
						'postsToShow'     => 6,
						'displayPostDate' => true,
					)
				),
			);
			if ( absint( $site_context['content_inventory']['published_posts_count'] ?? 0 ) <= 0 ) {
				$latest_posts_blocks[] = $this->pattern_paragraph_block( __( '暂无文章时，此区域会在发布内容后自动显示。', 'npcink-abilities-toolkit' ), 'openclaw-home-empty-fallback' );
			}
			$inner_blocks[] = $this->pattern_group_block(
				'openclaw-home-latest-posts',
				$latest_posts_blocks
			);
		}

		if ( ! empty( $options['include_category_links'] ) ) {
			$category_blocks = array(
				$this->pattern_heading_block( __( '分类入口', 'npcink-abilities-toolkit' ), 2, 'openclaw-template-section-title' ),
				$this->block_theme_dynamic_block(
					'core/categories',
					array(
						'displayAsDropdown' => false,
						'showPostCounts'    => true,
					)
				),
			);
			if ( absint( $site_context['content_inventory']['non_empty_categories_count'] ?? 0 ) <= 0 ) {
				$category_blocks[] = $this->pattern_paragraph_block( __( '暂无分类时，此区域会在创建分类后自动显示。', 'npcink-abilities-toolkit' ), 'openclaw-home-empty-fallback' );
			}
			$inner_blocks[] = $this->pattern_group_block(
				'openclaw-home-categories',
				$category_blocks
			);
		}

		if ( $include_page_content ) {
			$inner_blocks[] = $this->block_theme_dynamic_block( 'core/post-content', array( 'layout' => array( 'type' => 'constrained' ) ) );
		}
		return $inner_blocks;
	}

	/**
	 * Returns section labels for a template layout profile.
	 *
	 * @param string              $layout_profile Layout profile.
	 * @param array<string,mixed> $options Layout options.
	 * @return string[]
	 */
	private function block_theme_template_layout_sections( $layout_profile, array $options ) {
		$layout_profile = sanitize_key( (string) $layout_profile );
		if ( 'homepage_landing' === $layout_profile ) {
			$sections = array( 'header', 'hero' );
			if ( ! empty( $options['include_latest_posts'] ) ) {
				$sections[] = 'latest_posts';
			}
			if ( ! empty( $options['include_category_links'] ) ) {
				$sections[] = 'category_links';
			}
			$sections[] = 'post_content';
			$sections[] = 'footer';
			return $sections;
		}
		$sections = array( 'header' );
		if ( ! empty( $options['include_breadcrumbs'] ) ) {
			$sections[] = 'breadcrumbs';
		}
		$sections[] = 'post_title';
		if ( ! empty( $options['show_author_date'] ) && 'article_standard' === $layout_profile ) {
			$sections[] = 'author_date';
			$sections[] = 'post_categories';
		}
		if ( ! empty( $options['show_featured_image'] ) ) {
			$sections[] = 'featured_image';
		}
		$sections[] = 'post_content';
		if ( 'article_standard' === $layout_profile ) {
			$sections[] = 'post_tags';
			$sections[] = 'post_navigation';
			$sections[] = 'comments';
		}
		if ( ! empty( $options['include_related_posts'] ) && 'article_standard' === $layout_profile ) {
			$sections[] = 'related_posts';
		}
		$sections[] = 'footer';
		return $sections;
	}

	/**
	 * Returns the accepted version id for a bounded template layout profile.
	 *
	 * @param string $layout_profile Layout profile.
	 * @return string
	 */
	private function block_theme_template_layout_profile_version( $layout_profile ) {
		$layout_profile = sanitize_key( (string) $layout_profile );
		$versions       = array(
			'article_standard' => 'article_standard@0.4',
			'page_standard'    => 'page_standard@0.1',
			'homepage_landing' => 'homepage_landing@0.2',
		);
		return (string) ( $versions[ $layout_profile ] ?? $layout_profile );
	}

	/**
	 * Returns a layout profile contract for generated template plans.
	 *
	 * @param array<int,array<string,mixed>> $profile_rows Profile rows.
	 * @return array<string,mixed>
	 */
	private function block_theme_template_layout_contract( array $profile_rows ) {
		return array(
			'catalog_id'              => 'gutenberg_native_v1',
			'catalog_version'         => '1.0',
			'surface'                 => 'template',
			'intent'                  => 'customize_template_layout',
			'placement_model'         => 'bounded_template_layout_profile',
			'accepted_profiles'       => array( 'article_standard', 'page_standard', 'homepage_landing' ),
			'accepted_profile_versions' => array( 'article_standard@0.4', 'page_standard@0.1', 'homepage_landing@0.2' ),
			'compiler_version'        => 'block_theme_profile_compiler@0.2',
			'forbidden_policy_version' => 'block_theme_safe_core_blocks@0.2',
			'forbidden_outputs'       => array( 'raw_template_html', 'core/html', 'core/freeform', 'non_core_blocks', 'custom_css', 'theme_json', 'global_styles', 'navigation_write', 'template_part_write' ),
			'contract_status'         => 'pass',
			'violation_codes'         => array(),
			'profiles'                => array_values( $profile_rows ),
		);
	}

	/**
	 * Builds a template-part reference block.
	 *
	 * @param string $slug Template part slug.
	 * @param string $theme Theme slug.
	 * @return array<string,mixed>
	 */
	private function block_theme_template_part_block( $slug, $theme ) {
		$slug  = sanitize_key( (string) $slug );
		$theme = sanitize_key( (string) $theme );
		return array(
			'blockName'    => 'core/template-part',
			'attrs'        => array_filter(
				array(
					'slug' => $slug,
					'theme' => $theme,
				)
			),
			'innerHTML'    => '',
			'innerBlocks'  => array(),
			'innerContent' => array(),
		);
	}

	/**
	 * Builds a dynamic core block placeholder.
	 *
	 * @param string              $block_name Block name.
	 * @param array<string,mixed> $attrs Block attrs.
	 * @return array<string,mixed>
	 */
	private function block_theme_dynamic_block( $block_name, array $attrs = array() ) {
		return array(
			'blockName'    => sanitize_text_field( (string) $block_name ),
			'attrs'        => $attrs,
			'innerHTML'    => '',
			'innerBlocks'  => array(),
			'innerContent' => array(),
		);
	}

	/**
	 * Builds a simple CTA buttons block with a real URL.
	 *
	 * @param string $label Button label.
	 * @param string $url Button URL.
	 * @return array<string,mixed>
	 */
	private function block_theme_buttons_with_url_block( $label, $url ) {
		$label       = sanitize_text_field( (string) $label );
		$url         = $this->esc_url_value( (string) $url );
		$button_html = '<div class="wp-block-button openclaw-home-cta-button"><a class="wp-block-button__link wp-element-button" href="' . $this->pattern_attr( $url ) . '">' . esc_html( $label ) . '</a></div>';
		$button      = array(
			'blockName'    => 'core/button',
			'attrs'        => array(
				'className' => 'openclaw-home-cta-button',
				'url'       => $url,
			),
			'innerHTML'    => $button_html,
			'innerBlocks'  => array(),
			'innerContent' => array( $button_html ),
		);
		return $this->pattern_buttons_block(
			array( $button ),
			'openclaw-home-cta',
			array(
				'layout' => array(
					'type' => 'flex',
				),
			)
		);
	}

	/**
	 * Reads parsed blocks from one Site Editor entity.
	 *
	 * @param mixed  $input Input args.
	 * @param string $post_type Expected post type.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function get_block_theme_entity_blocks( $input, $post_type ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to read block theme templates.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$post_id = absint( $input['post_id'] ?? 0 );
		$slug    = sanitize_key( (string) ( $input['slug'] ?? '' ) );
		$entity  = $this->block_theme_find_entity( $post_type, $slug, $post_id );
		if ( empty( $entity ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_block_theme_entity_not_found', __( 'Block theme entity was not found.', 'npcink-abilities-toolkit' ), array( 'status' => 404 ) );
		}
		if ( $post_type !== sanitize_key( (string) ( $entity['post_type'] ?? '' ) ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_block_theme_entity_type_invalid', __( 'Block theme entity type is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$content = (string) ( $entity['content'] ?? '' );
		$blocks  = function_exists( 'parse_blocks' ) ? parse_blocks( $content ) : array();
		return array(
			'post_id'        => absint( $entity['post_id'] ?? 0 ),
			'template_id'    => sanitize_text_field( (string) ( $entity['template_id'] ?? '' ) ),
			'post_type'      => $post_type,
			'slug'           => sanitize_key( (string) ( $entity['slug'] ?? '' ) ),
			'theme'          => sanitize_key( (string) ( $entity['theme'] ?? '' ) ),
			'source'         => sanitize_key( (string) ( $entity['source'] ?? '' ) ),
			'title'          => sanitize_text_field( (string) ( $entity['title'] ?? '' ) ),
			'block_count'    => $this->block_theme_count_blocks( $blocks ),
			'content_length' => strlen( $content ),
			'blocks'         => is_array( $blocks ) ? array_values( $blocks ) : array(),
			'edit_link'      => function_exists( 'get_edit_post_link' ) && absint( $entity['post_id'] ?? 0 ) > 0 ? $this->esc_url_value( (string) get_edit_post_link( absint( $entity['post_id'] ?? 0 ), 'raw' ) ) : '',
		);
	}

	/**
	 * Returns compact active theme metadata.
	 *
	 * @return array<string,mixed>
	 */
	private function block_theme_active_theme_summary() {
		$name = '';
		$slug = '';
		if ( function_exists( 'wp_get_theme' ) ) {
			$theme = wp_get_theme();
			if ( is_object( $theme ) ) {
				$name = method_exists( $theme, 'get' ) ? (string) $theme->get( 'Name' ) : '';
				$slug = method_exists( $theme, 'get_stylesheet' ) ? (string) $theme->get_stylesheet() : '';
			}
		}
		if ( '' === $name ) {
			$name = sanitize_text_field( (string) ( $GLOBALS['npcink_abilities_toolkit_unit_active_theme']['name'] ?? 'Active Theme' ) );
		}
		if ( '' === $slug ) {
			$slug = sanitize_key( (string) ( $GLOBALS['npcink_abilities_toolkit_unit_active_theme']['stylesheet'] ?? 'active-theme' ) );
		}
		return array(
			'name'       => $name,
			'stylesheet' => $slug,
		);
	}

	/**
	 * Returns whether the active theme is a block theme.
	 *
	 * @return bool
	 */
	private function block_theme_is_active_block_theme() {
		if ( function_exists( 'wp_is_block_theme' ) ) {
			return (bool) wp_is_block_theme();
		}
		if ( array_key_exists( 'npcink_abilities_toolkit_unit_is_block_theme', $GLOBALS ) ) {
			return ! empty( $GLOBALS['npcink_abilities_toolkit_unit_is_block_theme'] );
		}
		return false;
	}

	/**
	 * Returns the factual snapshot used by template override planning.
	 *
	 * @return array<string,mixed>
	 */
	private function block_theme_site_context_snapshot() {
		$reading_settings = $this->block_theme_reading_settings_snapshot();
		$template_resolution = array(
			'front_page' => $this->block_theme_public_template_resolution( $this->block_theme_resolve_template_target( 'wp_template', 'front-page' ) ),
			'home'       => $this->block_theme_public_template_resolution( $this->block_theme_resolve_template_target( 'wp_template', 'home' ) ),
			'index'      => $this->block_theme_public_template_resolution( $this->block_theme_resolve_template_target( 'wp_template', 'index' ) ),
		);

		return array(
			'theme'               => array_merge(
				$this->block_theme_active_theme_summary(),
				array(
					'is_block_theme'          => $this->block_theme_is_active_block_theme(),
					'theme_json_presets'      => $this->block_theme_theme_json_preset_keys(),
					'registered_block_types'  => $this->block_theme_registered_block_type_names(),
				)
			),
			'reading_settings'    => $reading_settings,
			'template_resolution' => $template_resolution,
			'content_inventory'   => $this->block_theme_content_inventory_snapshot( $reading_settings ),
			'existing_overrides'  => $this->block_theme_existing_override_snapshot( array( 'front-page', 'home', 'index', 'single', 'page', 'archive' ) ),
		);
	}

	/**
	 * Returns front-page reading settings relevant to template resolution.
	 *
	 * @return array<string,mixed>
	 */
	private function block_theme_reading_settings_snapshot() {
		$show_on_front = function_exists( 'get_option' ) ? sanitize_key( (string) get_option( 'show_on_front', 'posts' ) ) : 'posts';
		$page_on_front = function_exists( 'get_option' ) ? absint( get_option( 'page_on_front', 0 ) ) : 0;
		$page_for_posts = function_exists( 'get_option' ) ? absint( get_option( 'page_for_posts', 0 ) ) : 0;
		return array(
			'show_on_front' => 'page' === $show_on_front ? 'page' : 'posts',
			'page_on_front' => $page_on_front,
			'page_for_posts' => $page_for_posts,
		);
	}

	/**
	 * Returns compact content inventory for homepage planning.
	 *
	 * @param array<string,mixed> $reading_settings Reading settings.
	 * @return array<string,mixed>
	 */
	private function block_theme_content_inventory_snapshot( array $reading_settings ) {
		return array(
			'published_posts_count'      => $this->block_theme_count_posts_by_type( 'post' ),
			'published_pages_count'      => $this->block_theme_count_posts_by_type( 'page' ),
			'non_empty_categories_count' => $this->block_theme_non_empty_categories_count(),
			'candidate_cta_pages'        => $this->block_theme_candidate_cta_pages( $reading_settings ),
		);
	}

	/**
	 * Counts published posts for a post type.
	 *
	 * @param string $post_type Post type.
	 * @return int
	 */
	private function block_theme_count_posts_by_type( $post_type ) {
		$post_type = sanitize_key( (string) $post_type );
		$count     = 0;
		foreach ( $this->block_theme_posts_for_context( array( $post_type ) ) as $post ) {
			if ( is_object( $post ) && 'publish' === (string) ( $post->post_status ?? '' ) ) {
				++$count;
			}
		}
		return $count;
	}

	/**
	 * Returns non-empty category count when taxonomy APIs are available.
	 *
	 * @return int
	 */
	private function block_theme_non_empty_categories_count() {
		if ( function_exists( 'get_terms' ) ) {
			$terms = get_terms(
				array(
					'taxonomy'   => 'category',
					'hide_empty' => true,
					'fields'     => 'ids',
				)
			);
			return is_array( $terms ) ? count( $terms ) : 0;
		}
		$terms = isset( $GLOBALS['npcink_abilities_toolkit_unit_terms'] ) && is_array( $GLOBALS['npcink_abilities_toolkit_unit_terms'] )
			? $GLOBALS['npcink_abilities_toolkit_unit_terms']
			: array();
		$count = 0;
		foreach ( $terms as $term ) {
			if ( is_object( $term ) && 'category' === (string) ( $term->taxonomy ?? '' ) && (int) ( $term->count ?? 0 ) > 0 ) {
				++$count;
			}
		}
		return $count;
	}

	/**
	 * Returns candidate pages for CTA resolution.
	 *
	 * @param array<string,mixed> $reading_settings Reading settings.
	 * @return array<int,array<string,mixed>>
	 */
	private function block_theme_candidate_cta_pages( array $reading_settings ) {
		$candidates = array();
		$page_for_posts = absint( $reading_settings['page_for_posts'] ?? 0 );
		if ( $page_for_posts > 0 ) {
			$post = function_exists( 'get_post' ) ? get_post( $page_for_posts ) : null;
			if ( is_object( $post ) && 'page' === (string) ( $post->post_type ?? '' ) && 'publish' === (string) ( $post->post_status ?? '' ) ) {
				$candidates[] = $this->block_theme_candidate_cta_page_row( $post, 'posts_page', __( '查看最新文章', 'npcink-abilities-toolkit' ) );
			}
		}

		$shop_page_id = function_exists( 'get_option' ) ? absint( get_option( 'woocommerce_shop_page_id', 0 ) ) : 0;
		if ( $shop_page_id > 0 ) {
			$post = function_exists( 'get_post' ) ? get_post( $shop_page_id ) : null;
			if ( is_object( $post ) && 'page' === (string) ( $post->post_type ?? '' ) && 'publish' === (string) ( $post->post_status ?? '' ) ) {
				$candidates[] = $this->block_theme_candidate_cta_page_row( $post, 'woocommerce_shop', __( '查看商店', 'npcink-abilities-toolkit' ) );
			}
		}

		$priority_slugs = array( 'contact', 'contacts', 'services', 'service', 'shop', 'blog', 'about', 'about-us' );
		foreach ( $this->block_theme_posts_for_context( array( 'page' ) ) as $post ) {
			if ( ! is_object( $post ) || 'publish' !== (string) ( $post->post_status ?? '' ) ) {
				continue;
			}
			$slug = sanitize_key( (string) ( $post->post_name ?? '' ) );
			if ( in_array( $slug, $priority_slugs, true ) ) {
				$candidates[] = $this->block_theme_candidate_cta_page_row( $post, 'slug_match_' . $slug );
			}
		}

		$deduped = array();
		$seen    = array();
		foreach ( $candidates as $candidate ) {
			$id = absint( $candidate['id'] ?? 0 );
			if ( $id <= 0 || in_array( $id, $seen, true ) ) {
				continue;
			}
			$seen[]    = $id;
			$deduped[] = $candidate;
		}
		return array_values( $deduped );
	}

	/**
	 * Builds one CTA page candidate row.
	 *
	 * @param object $post Page post.
	 * @param string $reason Candidate reason.
	 * @param string $label Optional CTA label.
	 * @return array<string,mixed>
	 */
	private function block_theme_candidate_cta_page_row( $post, $reason, $label = '' ) {
		$title = sanitize_text_field( (string) ( $post->post_title ?? '' ) );
		return array(
			'id'     => absint( $post->ID ?? 0 ),
			'slug'   => sanitize_key( (string) ( $post->post_name ?? '' ) ),
			'title'  => $title,
			'url'    => $this->block_theme_post_url( $post ),
			'label'  => '' !== $label ? sanitize_text_field( $label ) : sprintf(
				/* translators: %s: page title. */
				__( '查看%s', 'npcink-abilities-toolkit' ),
				$title
			),
			'reason' => sanitize_key( (string) $reason ),
		);
	}

	/**
	 * Returns a public URL for a post without requiring permalink APIs in tests.
	 *
	 * @param object $post Post object.
	 * @return string
	 */
	private function block_theme_post_url( $post ) {
		if ( function_exists( 'get_permalink' ) ) {
			$url = get_permalink( absint( $post->ID ?? 0 ) );
			if ( is_string( $url ) && '' !== $url ) {
				return $this->esc_url_value( $url );
			}
		}
		$slug = sanitize_title( (string) ( $post->post_name ?? '' ) );
		return '' !== $slug ? '/' . $slug . '/' : '/?page_id=' . absint( $post->ID ?? 0 );
	}

	/**
	 * Resolves homepage CTA without falling back to placeholders.
	 *
	 * @param array<string,mixed> $variables Copy variables.
	 * @param array<string,mixed> $site_context Site context.
	 * @return array<string,mixed>
	 */
	private function block_theme_resolve_homepage_cta( array $variables, array $site_context ) {
		$explicit_url = $this->esc_url_value( (string) ( $variables['cta_url'] ?? '' ) );
		if ( '' !== $explicit_url ) {
			return array(
				'status' => 'resolved',
				'source' => 'user_url',
				'url'    => $explicit_url,
				'label'  => sanitize_text_field( (string) ( $variables['cta_label'] ?? __( '了解更多', 'npcink-abilities-toolkit' ) ) ),
			);
		}

		$candidates = is_array( $site_context['content_inventory']['candidate_cta_pages'] ?? null ) ? $site_context['content_inventory']['candidate_cta_pages'] : array();
		$candidate  = is_array( $candidates[0] ?? null ) ? $candidates[0] : array();
		$url        = $this->esc_url_value( (string) ( $candidate['url'] ?? '' ) );
		if ( '' !== $url ) {
			return array(
				'status'       => 'resolved',
				'source'       => sanitize_key( (string) ( $candidate['reason'] ?? 'candidate_page' ) ),
				'url'          => $url,
				'label'        => sanitize_text_field( (string) ( $variables['cta_label'] ?? ( $candidate['label'] ?? __( '了解更多', 'npcink-abilities-toolkit' ) ) ) ),
				'target_page_id' => absint( $candidate['id'] ?? 0 ),
			);
		}

		return array(
			'status' => 'unresolved',
			'source' => 'no_candidate_page',
			'url'    => '',
			'label'  => sanitize_text_field( (string) ( $variables['cta_label'] ?? __( '了解更多', 'npcink-abilities-toolkit' ) ) ),
			'requires_user_input' => true,
			'issue_code' => 'cta_link_unresolved',
		);
	}

	/**
	 * Returns whether homepage profile should include the static page content slot.
	 *
	 * @param string $template_slug Template slug.
	 * @param array<string,mixed> $site_context Site context.
	 * @return bool
	 */
	private function block_theme_homepage_should_include_post_content( $template_slug, array $site_context ) {
		$template_slug    = sanitize_key( (string) $template_slug );
		$reading_settings = is_array( $site_context['reading_settings'] ?? null ) ? $site_context['reading_settings'] : array();
		return 'front-page' === $template_slug
			&& 'page' === sanitize_key( (string) ( $reading_settings['show_on_front'] ?? 'posts' ) )
			&& absint( $reading_settings['page_on_front'] ?? 0 ) > 0;
	}

	/**
	 * Returns deterministic template resolver metadata for proposal previews.
	 *
	 * @param string $intent Intent.
	 * @param string $requested_slug Requested slug.
	 * @param array<string,mixed> $resolution Resolution.
	 * @param array<string,mixed> $site_context Site context.
	 * @return array<string,mixed>
	 */
	private function block_theme_template_resolver_result( $intent, $requested_slug, array $resolution, array $site_context ) {
		unset( $site_context );
		$requested_slug = sanitize_key( (string) $requested_slug );
		$target_slug    = sanitize_key( (string) ( $resolution['target_slug'] ?? $requested_slug ) );
		$creates        = ! empty( $resolution['creates_template_override'] );
		return array(
			'intent'          => sanitize_key( (string) $intent ),
			'target_template' => array(
				'post_type' => 'wp_template',
				'slug'      => $target_slug,
				'operation' => $creates ? 'create_override' : 'update_existing_override',
				'reason'    => $this->block_theme_template_resolver_reason( $requested_slug, $resolution ),
			),
			'fallback_chain'  => is_array( $resolution['candidates'] ?? null ) ? array_values( array_map( 'sanitize_key', $resolution['candidates'] ) ) : array( $requested_slug ),
			'affected_routes' => $this->block_theme_affected_routes_for_template( $target_slug ),
			'warnings'        => array(),
		);
	}

	/**
	 * Returns a resolver reason string.
	 *
	 * @param string $requested_slug Requested slug.
	 * @param array<string,mixed> $resolution Resolution.
	 * @return string
	 */
	private function block_theme_template_resolver_reason( $requested_slug, array $resolution ) {
		$strategy = sanitize_key( (string) ( $resolution['strategy'] ?? '' ) );
		if ( 'front-page' === sanitize_key( (string) $requested_slug ) ) {
			return 'User asked to customize the site homepage; front-page has highest precedence for the site front page.';
		}
		if ( 'home' === sanitize_key( (string) $requested_slug ) ) {
			return 'User asked to customize the blog home; home is the WordPress posts index template before index fallback.';
		}
		return '' !== $strategy ? 'Template resolver selected strategy=' . $strategy . '.' : 'Template resolver selected the requested template.';
	}

	/**
	 * Returns affected frontend routes for a template slug.
	 *
	 * @param string $slug Template slug.
	 * @return string[]
	 */
	private function block_theme_affected_routes_for_template( $slug ) {
		$slug = sanitize_key( (string) $slug );
		if ( 'front-page' === $slug ) {
			return array( '/' );
		}
		if ( 'home' === $slug ) {
			return array( '/blog/', '/' );
		}
		if ( 'single' === $slug ) {
			return array( 'single posts' );
		}
		if ( 'page' === $slug ) {
			return array( 'pages' );
		}
		return array( $slug );
	}

	/**
	 * Returns existing custom template override hashes.
	 *
	 * @param string[] $slugs Template slugs.
	 * @return array<string,array<string,mixed>>
	 */
	private function block_theme_existing_override_snapshot( array $slugs ) {
		$rows = array();
		foreach ( $slugs as $slug ) {
			$slug   = sanitize_key( (string) $slug );
			$entity = $this->block_theme_find_entity( 'wp_template', $slug, 0 );
			$is_custom = is_array( $entity ) && 'custom' === sanitize_key( (string) ( $entity['source'] ?? '' ) ) && absint( $entity['post_id'] ?? 0 ) > 0;
			$content = $is_custom ? (string) ( $entity['content'] ?? '' ) : '';
			$rows[ $slug ] = array(
				'exists'       => $is_custom,
				'post_id'      => $is_custom ? absint( $entity['post_id'] ?? 0 ) : 0,
				'source'       => $is_custom ? 'custom' : '',
				'content_hash' => '' !== $content ? hash( 'sha256', $content ) : '',
			);
		}
		return $rows;
	}

	/**
	 * Returns posts for context snapshots.
	 *
	 * @param string[] $post_types Post types.
	 * @return array<int,object>
	 */
	private function block_theme_posts_for_context( array $post_types ) {
		if ( ! function_exists( 'get_posts' ) ) {
			return array();
		}
		$posts = get_posts(
			array(
				'post_type'        => $post_types,
				'post_status'      => array( 'publish' ),
				'posts_per_page'   => 100,
				'suppress_filters' => false,
			)
		);
		return is_array( $posts ) ? array_values( array_filter( $posts, 'is_object' ) ) : array();
	}

	/**
	 * Returns theme.json preset groups exposed in the current runtime.
	 *
	 * @return string[]
	 */
	private function block_theme_theme_json_preset_keys() {
		if ( ! function_exists( 'wp_get_global_settings' ) ) {
			return array();
		}
		$settings = wp_get_global_settings();
		$keys     = array();
		foreach ( array( 'spacing', 'color', 'typography', 'layout' ) as $key ) {
			if ( is_array( $settings ) && ! empty( $settings[ $key ] ) ) {
				$keys[] = $key;
			}
		}
		return $keys;
	}

	/**
	 * Returns registered block names or the bounded fallback catalog.
	 *
	 * @return string[]
	 */
	private function block_theme_registered_block_type_names() {
		if ( class_exists( '\WP_Block_Type_Registry' ) && method_exists( '\WP_Block_Type_Registry', 'get_instance' ) ) {
			$registry = \WP_Block_Type_Registry::get_instance();
			if ( is_object( $registry ) && method_exists( $registry, 'get_all_registered' ) ) {
				return array_values( array_map( 'sanitize_text_field', array_keys( (array) $registry->get_all_registered() ) ) );
			}
		}
		return array(
			'core/template-part',
			'core/group',
			'core/heading',
			'core/paragraph',
			'core/buttons',
			'core/button',
			'core/latest-posts',
			'core/categories',
			'core/post-content',
		);
	}

	/**
	 * Returns compact Site Editor post rows.
	 *
	 * @param string   $post_type Post type.
	 * @param string[] $preferred_slugs Preferred slugs.
	 * @return array<int,array<string,mixed>>
	 */
	private function block_theme_template_rows( $post_type, array $preferred_slugs ) {
		$rows = array();
		foreach ( $preferred_slugs as $slug ) {
			$entity = $this->block_theme_find_entity( $post_type, $slug, 0 );
			if ( empty( $entity ) ) {
				continue;
			}
			$content = (string) ( $entity['content'] ?? '' );
			$blocks  = function_exists( 'parse_blocks' ) ? parse_blocks( $content ) : array();
			$rows[]  = array(
				'post_id'        => absint( $entity['post_id'] ?? 0 ),
				'template_id'    => sanitize_text_field( (string) ( $entity['template_id'] ?? '' ) ),
				'post_type'      => $post_type,
				'slug'           => sanitize_key( (string) ( $entity['slug'] ?? $slug ) ),
				'theme'          => sanitize_key( (string) ( $entity['theme'] ?? '' ) ),
				'source'         => sanitize_key( (string) ( $entity['source'] ?? '' ) ),
				'title'          => sanitize_text_field( (string) ( $entity['title'] ?? '' ) ),
				'block_count'    => $this->block_theme_count_blocks( $blocks ),
				'content_length' => strlen( $content ),
			);
		}
		return $rows;
	}

	/**
	 * Finds a Site Editor template entity by id or slug.
	 *
	 * @param string $post_type Post type.
	 * @param string $slug Slug.
	 * @param int    $post_id Post id.
	 * @return array<string,mixed>|null
	 */
	private function block_theme_find_entity( $post_type, $slug, $post_id ) {
		$post_type = sanitize_key( (string) $post_type );
		$post_id   = absint( $post_id );
		if ( $post_id > 0 ) {
			$post = get_post( $post_id );
			return is_object( $post ) && $post_type === sanitize_key( (string) ( $post->post_type ?? '' ) ) ? $this->block_theme_entity_from_post( $post, $post_type ) : null;
		}

		$slug  = sanitize_key( (string) $slug );
		if ( function_exists( 'get_block_templates' ) ) {
			$templates = get_block_templates(
				'' === $slug ? array() : array( 'slug__in' => array( $slug ) ),
				$post_type
			);
			foreach ( is_array( $templates ) ? $templates : array() as $template ) {
				$entity = $this->block_theme_entity_from_block_template( $template, $post_type );
				if ( empty( $entity ) ) {
					continue;
				}
				if ( '' === $slug || $slug === sanitize_key( (string) ( $entity['slug'] ?? '' ) ) ) {
					return $entity;
				}
			}
		}

		$posts = function_exists( 'get_posts' )
			? get_posts(
				array(
					'post_type'        => $post_type,
					'post_status'      => array( 'publish', 'draft', 'auto-draft' ),
					'posts_per_page'   => 50,
					'suppress_filters' => false,
				)
			)
			: array();
		foreach ( is_array( $posts ) ? $posts : array() as $post ) {
			if ( ! is_object( $post ) ) {
				continue;
			}
			if ( $post_type !== sanitize_key( (string) ( $post->post_type ?? '' ) ) ) {
				continue;
			}
			$post_slug = sanitize_key( (string) ( $post->post_name ?? '' ) );
			$template_slug = $this->block_theme_slug_from_post_name( (string) ( $post->post_name ?? '' ) );
			$post_title = sanitize_key( (string) ( $post->post_title ?? '' ) );
			if ( '' === $slug || $slug === $post_slug || $slug === $template_slug || $slug === $post_title ) {
				return $this->block_theme_entity_from_post( $post, $post_type );
			}
		}
		return null;
	}

	/**
	 * Resolves a requested template target to an existing source template.
	 *
	 * @param string $post_type Template post type.
	 * @param string $requested_slug Requested target slug.
	 * @return array<string,mixed>
	 */
	private function block_theme_resolve_template_target( $post_type, $requested_slug ) {
		$post_type       = sanitize_key( (string) $post_type );
		$requested_slug  = sanitize_key( (string) $requested_slug );
		$direct_template = $this->block_theme_find_entity( $post_type, $requested_slug, 0 );
		if ( ! empty( $direct_template ) ) {
			return array(
				'entity'                    => $direct_template,
				'requested_slug'            => $requested_slug,
				'target_slug'               => sanitize_key( (string) ( $direct_template['slug'] ?? $requested_slug ) ),
				'source_slug'               => sanitize_key( (string) ( $direct_template['slug'] ?? $requested_slug ) ),
				'target_post_id'            => absint( $direct_template['post_id'] ?? 0 ),
				'target_title'              => sanitize_text_field( (string) ( $direct_template['title'] ?? $requested_slug ) ),
				'source_template_id'        => sanitize_text_field( (string) ( $direct_template['template_id'] ?? '' ) ),
				'source_post_id'            => absint( $direct_template['post_id'] ?? 0 ),
				'strategy'                  => 'direct',
				'creates_template_override' => false,
				'candidates'                => array( $requested_slug ),
			);
		}

		if ( ! in_array( $requested_slug, array( 'front-page', 'home' ), true ) ) {
			return array(
				'entity'         => null,
				'requested_slug' => $requested_slug,
				'target_slug'    => $requested_slug,
				'strategy'       => 'not_found',
				'candidates'     => array( $requested_slug ),
			);
		}

		$candidates = $this->block_theme_home_template_candidates( $requested_slug );
		foreach ( $candidates as $candidate ) {
			$candidate_slug = sanitize_key( (string) ( $candidate['slug'] ?? '' ) );
			if ( '' === $candidate_slug || $requested_slug === $candidate_slug ) {
				continue;
			}
			$template = $this->block_theme_find_entity( $post_type, $candidate_slug, 0 );
			if ( empty( $template ) ) {
				continue;
			}
			return array(
				'entity'                    => $template,
				'requested_slug'            => $requested_slug,
				'target_slug'               => $requested_slug,
				'source_slug'               => sanitize_key( (string) ( $template['slug'] ?? $candidate_slug ) ),
				'target_post_id'            => 0,
				'target_title'              => $this->block_theme_template_title_for_slug( $requested_slug ),
				'source_template_id'        => sanitize_text_field( (string) ( $template['template_id'] ?? '' ) ),
				'source_post_id'            => absint( $template['post_id'] ?? 0 ),
				'strategy'                  => sanitize_key( (string) ( $candidate['strategy'] ?? 'home_template_fallback' ) ),
				'creates_template_override' => true,
				'candidates'                => $this->block_theme_template_candidate_slugs( $candidates ),
			);
		}

		return array(
			'entity'         => null,
			'requested_slug' => $requested_slug,
			'target_slug'    => $requested_slug,
			'strategy'       => 'home_template_unresolved',
			'candidates'     => $this->block_theme_template_candidate_slugs( $candidates ),
		);
	}

	/**
	 * Returns unique candidate slugs from template candidate metadata.
	 *
	 * @param array<int,array<string,string>> $candidates Candidate metadata.
	 * @return string[]
	 */
	private function block_theme_template_candidate_slugs( array $candidates ) {
		$slugs = array();
		foreach ( $candidates as $candidate ) {
			$slug = sanitize_key( (string) ( $candidate['slug'] ?? '' ) );
			if ( '' !== $slug && ! in_array( $slug, $slugs, true ) ) {
				$slugs[] = $slug;
			}
		}
		return $slugs;
	}

	/**
	 * Returns homepage template fallback candidates for a requested home target.
	 *
	 * @param string $requested_slug Requested target slug.
	 * @return array<int,array<string,string>>
	 */
	private function block_theme_home_template_candidates( $requested_slug ) {
		$requested_slug = sanitize_key( (string) $requested_slug );
		$candidates     = array(
			array(
				'slug'     => $requested_slug,
				'strategy' => 'direct',
			),
		);

		if ( 'front-page' === $requested_slug ) {
			$show_on_front = function_exists( 'get_option' ) ? (string) get_option( 'show_on_front', 'posts' ) : 'posts';
			if ( 'page' === $show_on_front ) {
				$front_page_id = function_exists( 'get_option' ) ? absint( get_option( 'page_on_front', 0 ) ) : 0;
				$front_page    = $front_page_id > 0 && function_exists( 'get_post' ) ? get_post( $front_page_id ) : null;
				$front_slug    = is_object( $front_page ) ? sanitize_title( (string) ( $front_page->post_name ?? '' ) ) : '';
				if ( '' !== $front_slug ) {
					$candidates[] = array(
						'slug'     => 'page-' . $front_slug,
						'strategy' => 'static_front_page_slug_fallback',
					);
				}
				if ( $front_page_id > 0 ) {
					$candidates[] = array(
						'slug'     => 'page-' . $front_page_id,
						'strategy' => 'static_front_page_id_fallback',
					);
				}
				$candidates[] = array(
					'slug'     => 'page',
					'strategy' => 'static_front_page_page_template_fallback',
				);
				$candidates[] = array(
					'slug'     => 'singular',
					'strategy' => 'static_front_page_singular_template_fallback',
				);
			} else {
				$candidates[] = array(
					'slug'     => 'home',
					'strategy' => 'posts_front_page_home_template_fallback',
				);
			}
		} elseif ( 'home' === $requested_slug ) {
			$candidates[] = array(
				'slug'     => 'index',
				'strategy' => 'home_index_template_fallback',
			);
		}

		$candidates[] = array(
			'slug'     => 'index',
			'strategy' => 'index_template_fallback',
		);

		$deduped = array();
		$seen    = array();
		foreach ( $candidates as $candidate ) {
			$slug = sanitize_key( (string) ( $candidate['slug'] ?? '' ) );
			if ( '' === $slug || in_array( $slug, $seen, true ) ) {
				continue;
			}
			$seen[]              = $slug;
			$candidate['slug']   = $slug;
			$deduped[]           = $candidate;
		}
		return $deduped;
	}

	/**
	 * Returns a human-friendly template title for a target slug.
	 *
	 * @param string $slug Template slug.
	 * @return string
	 */
	private function block_theme_template_title_for_slug( $slug ) {
		$slug = sanitize_key( (string) $slug );
		if ( 'front-page' === $slug ) {
			return __( 'Front Page', 'npcink-abilities-toolkit' );
		}
		if ( 'home' === $slug ) {
			return __( 'Blog Home', 'npcink-abilities-toolkit' );
		}
		return ucwords( str_replace( '-', ' ', $slug ) );
	}

	/**
	 * Returns public template resolution metadata.
	 *
	 * @param array<string,mixed> $resolution Resolution.
	 * @return array<string,mixed>
	 */
	private function block_theme_public_template_resolution( array $resolution ) {
		return array(
			'requested_slug'            => sanitize_key( (string) ( $resolution['requested_slug'] ?? '' ) ),
			'target_slug'               => sanitize_key( (string) ( $resolution['target_slug'] ?? '' ) ),
			'source_slug'               => sanitize_key( (string) ( $resolution['source_slug'] ?? '' ) ),
			'strategy'                  => sanitize_key( (string) ( $resolution['strategy'] ?? '' ) ),
			'creates_template_override' => ! empty( $resolution['creates_template_override'] ),
			'source_template_id'        => sanitize_text_field( (string) ( $resolution['source_template_id'] ?? '' ) ),
			'source_post_id'            => absint( $resolution['source_post_id'] ?? 0 ),
			'candidates'                => is_array( $resolution['candidates'] ?? null ) ? array_values( array_map( 'sanitize_key', $resolution['candidates'] ) ) : array(),
		);
	}

	/**
	 * Builds a Site Editor entity row from a custom template post.
	 *
	 * @param object $post Post object.
	 * @param string $post_type Expected post type.
	 * @return array<string,mixed>
	 */
	private function block_theme_entity_from_post( $post, $post_type ) {
		$post_id = absint( $post->ID ?? 0 );
		$theme   = $this->block_theme_active_theme_summary();
		$slug    = $this->block_theme_slug_from_post_name( (string) ( $post->post_name ?? '' ) );
		return array(
			'post_id'     => $post_id,
			'template_id' => sanitize_text_field( (string) ( $theme['stylesheet'] ?? '' ) . '//' . $slug ),
			'post_type'   => sanitize_key( (string) $post_type ),
			'slug'        => $slug,
			'theme'       => sanitize_key( (string) ( $theme['stylesheet'] ?? '' ) ),
			'source'      => 'custom',
			'title'       => sanitize_text_field( (string) ( $post->post_title ?? $slug ) ),
			'content'     => (string) ( $post->post_content ?? '' ),
		);
	}

	/**
	 * Builds a Site Editor entity row from a WP_Block_Template object.
	 *
	 * @param object $template Block template object.
	 * @param string $post_type Expected template post type.
	 * @return array<string,mixed>|null
	 */
	private function block_theme_entity_from_block_template( $template, $post_type ) {
		if ( ! is_object( $template ) ) {
			return null;
		}
		$slug = sanitize_key( (string) ( $template->slug ?? '' ) );
		if ( '' === $slug ) {
			$slug = $this->block_theme_slug_from_post_name( (string) ( $template->id ?? '' ) );
		}
		if ( '' === $slug ) {
			return null;
		}
		$title = $template->title ?? $slug;
		if ( is_object( $title ) && isset( $title->rendered ) ) {
			$title = (string) $title->rendered;
		}
		return array(
			'post_id'     => absint( $template->wp_id ?? 0 ),
			'template_id' => sanitize_text_field( (string) ( $template->id ?? '' ) ),
			'post_type'   => sanitize_key( (string) ( $template->type ?? $post_type ) ),
			'slug'        => $slug,
			'theme'       => sanitize_key( (string) ( $template->theme ?? ( $this->block_theme_active_theme_summary()['stylesheet'] ?? '' ) ) ),
			'source'      => sanitize_key( (string) ( $template->source ?? '' ) ),
			'title'       => sanitize_text_field( (string) $title ),
			'content'     => (string) ( $template->content ?? '' ),
		);
	}

	/**
	 * Extracts the template slug from a raw post_name or template id.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	private function block_theme_slug_from_post_name( $value ) {
		$value = (string) $value;
		if ( false !== strpos( $value, '//' ) ) {
			$parts = explode( '//', $value );
			$value = (string) end( $parts );
		}
		return sanitize_key( $value );
	}

	/**
	 * Normalizes target template slugs.
	 *
	 * @param mixed $targets Raw targets.
	 * @return string[]
	 */
	private function block_theme_target_slugs( $targets, bool $allow_archive = false ) {
		$targets = is_array( $targets ) ? $targets : array( 'single', 'page', 'front-page' );
		$allowed = $this->block_theme_site_plan_target_slugs();
		if ( $allow_archive ) {
			$allowed[] = 'archive';
		}
		$normalized = array();
		foreach ( $targets as $target ) {
			$slug = sanitize_key( (string) $target );
			if ( in_array( $slug, $allowed, true ) && ! in_array( $slug, $normalized, true ) ) {
				$normalized[] = $slug;
			}
		}
		return empty( $normalized ) ? array( 'single', 'page', 'front-page' ) : $normalized;
	}

	/**
	 * Returns template slugs accepted by the governed site plan ability.
	 *
	 * @return string[]
	 */
	private function block_theme_site_plan_target_slugs() {
		return array( 'single', 'page', 'front-page', 'home', 'index' );
	}

	/**
	 * Returns whether the governed site planner accepts a template slug.
	 *
	 * @param string $template_slug Template slug.
	 * @return bool
	 */
	private function block_theme_site_plan_accepts_template( $template_slug ) {
		return in_array( sanitize_key( (string) $template_slug ), $this->block_theme_site_plan_target_slugs(), true );
	}

	/**
	 * Returns a missing-template inspection row.
	 *
	 * @param array<string,mixed> $warning Warning row.
	 * @return array<string,mixed>
	 */
	private function block_theme_inspection_missing_template_row( array $warning ) {
		return array(
			'target_type'         => 'wp_template',
			'slug'                => sanitize_key( (string) ( $warning['slug'] ?? '' ) ),
			'status'              => 'blocked',
			'issue_codes'         => array( 'template_not_found' ),
			'fixable_issue_codes' => array(),
			'template_resolution' => is_array( $warning['template_resolution'] ?? null ) ? $warning['template_resolution'] : array(),
			'summary'             => array(
				'breadcrumb_count' => 0,
				'post_title_count' => 0,
				'block_count'      => 0,
				'top_level_blocks' => array(),
			),
			'dual_review'         => $this->block_theme_template_dual_review( array( 'template_not_found' ), array() ),
		);
	}

	/**
	 * Inspects one template for supported breadcrumb surface issues.
	 *
	 * @param array<string,mixed> $template Template entity.
	 * @param array<string,mixed> $template_resolution Resolution metadata.
	 * @param string              $template_slug Target template slug.
	 * @param array<int,mixed>    $blocks Parsed blocks.
	 * @param bool                $show_on_home Whether breadcrumbs are allowed on home templates.
	 * @return array<string,mixed>
	 */
	private function block_theme_inspect_breadcrumb_template( array $template, array $template_resolution, $template_slug, array $blocks, $show_on_home ) {
		$template_slug        = sanitize_key( (string) $template_slug );
		$breadcrumb_count     = $this->block_theme_count_matching_blocks( $blocks, 'breadcrumbs' );
		$post_title_count     = $this->block_theme_count_matching_blocks( $blocks, 'post_title' );
		$issue_codes          = array();
		$is_home_template     = in_array( $template_slug, array( 'front-page', 'home' ), true );
		$breadcrumbs_valid    = $this->block_theme_breadcrumbs_are_before_post_title_in_main( $blocks );

		if ( $is_home_template && ! $show_on_home ) {
			if ( $breadcrumb_count > 0 ) {
				$issue_codes[] = 'homepage_breadcrumb_should_be_hidden';
			}
		} elseif ( 0 === $breadcrumb_count ) {
			$issue_codes[] = 'breadcrumb_missing';
		} elseif ( ! $breadcrumbs_valid ) {
			if ( $this->block_theme_has_top_level_breadcrumb_before_header( $blocks ) ) {
				$issue_codes[] = 'breadcrumb_above_header';
			}
			$issue_codes[] = 'breadcrumb_not_before_title';
		}

		if ( ! $is_home_template && 0 === $post_title_count ) {
			$issue_codes[] = 'post_title_not_found';
		}

		$issue_codes          = array_values( array_unique( array_filter( $issue_codes ) ) );
		$fixable_issue_codes = array_values( array_intersect( $issue_codes, $this->block_theme_supported_surface_issue_codes() ) );
		$status              = empty( $issue_codes ) ? 'pass' : ( empty( $fixable_issue_codes ) ? 'blocked' : 'needs_fix' );

		return array(
			'target_type'         => 'wp_template',
			'post_id'             => absint( $template_resolution['target_post_id'] ?? 0 ),
			'slug'                => $template_slug,
			'theme'               => sanitize_key( (string) ( $template['theme'] ?? '' ) ),
			'source'              => sanitize_key( (string) ( $template['source'] ?? '' ) ),
			'status'              => $status,
			'issue_codes'         => $issue_codes,
			'fixable_issue_codes' => $fixable_issue_codes,
			'template_resolution' => $this->block_theme_public_template_resolution( $template_resolution ),
			'summary'             => array(
				'breadcrumb_count'                    => $breadcrumb_count,
				'post_title_count'                    => $post_title_count,
				'breadcrumb_before_post_title_in_main' => $breadcrumbs_valid,
				'block_count'                         => $this->block_theme_count_blocks( $blocks ),
				'top_level_blocks'                    => $this->block_theme_top_level_block_summaries( $blocks ),
			),
			'dual_review'         => $this->block_theme_template_dual_review( $issue_codes, $fixable_issue_codes ),
		);
	}

	/**
	 * Returns stable issue codes this inspector can map to the breadcrumb planner.
	 *
	 * @return string[]
	 */
	private function block_theme_supported_surface_issue_codes() {
		return array(
			'breadcrumb_missing',
			'breadcrumb_above_header',
			'breadcrumb_not_before_title',
			'homepage_breadcrumb_should_be_hidden',
		);
	}

	/**
	 * Returns deterministic dual-review metadata for one template.
	 *
	 * @param string[] $issue_codes Issue codes.
	 * @param string[] $fixable_issue_codes Fixable issue codes.
	 * @return array<string,mixed>
	 */
	private function block_theme_template_dual_review( array $issue_codes, array $fixable_issue_codes ) {
		$has_issues  = ! empty( $issue_codes );
		$has_fixable = ! empty( $fixable_issue_codes );
		return array(
			'planner_reviewer' => array(
				'reviewer_id' => 'layout_issue_reviewer',
				'decision'    => $has_issues ? 'needs_fix' : 'pass',
				'issue_codes' => array_values( $issue_codes ),
			),
			'safety_reviewer'  => array(
				'reviewer_id'             => 'governance_boundary_reviewer',
				'decision'                => $has_issues && ! $has_fixable ? 'blocked' : 'pass',
				'fixable_issue_codes'     => array_values( $fixable_issue_codes ),
				'direct_wordpress_write'  => false,
				'proposal_required'       => $has_fixable,
				'allowed_plan_ability_id' => $has_fixable ? 'npcink-abilities-toolkit/build-block-theme-site-plan' : '',
			),
			'consensus'        => array(
				'decision'              => $has_fixable ? 'build_minimal_plan' : ( $has_issues ? 'needs_human_review' : 'no_changes_required' ),
				'recommended_next_step' => $has_fixable ? 'build_block_theme_site_plan' : ( $has_issues ? 'manual_review' : 'no_changes_required' ),
			),
		);
	}

	/**
	 * Returns the suite-level dual review.
	 *
	 * @param array<int,mixed> $templates Template rows.
	 * @param array<int,mixed> $warnings Warning rows.
	 * @param array<string,mixed> $recommended_plan_input Plan input.
	 * @return array<string,mixed>
	 */
	private function block_theme_surface_dual_review( array $templates, array $warnings, array $recommended_plan_input ) {
		$issue_codes = array();
		foreach ( $templates as $template ) {
			foreach ( is_array( $template['issue_codes'] ?? null ) ? $template['issue_codes'] : array() as $issue_code ) {
				$issue_codes[] = sanitize_key( (string) $issue_code );
			}
		}
		$issue_codes = array_values( array_unique( array_filter( $issue_codes ) ) );
		$has_plan    = ! empty( $recommended_plan_input );
		return array(
			'planner_reviewer' => array(
				'reviewer_id' => 'surface_issue_reviewer',
				'decision'    => empty( $issue_codes ) ? 'pass' : 'needs_fix',
				'issue_codes' => $issue_codes,
			),
			'safety_reviewer'  => array(
				'reviewer_id'             => 'governance_boundary_reviewer',
				'decision'                => empty( $warnings ) ? 'pass' : 'needs_attention',
				'direct_wordpress_write'  => false,
				'proposal_created'        => false,
				'proposal_required'       => $has_plan,
				'allowed_plan_ability_id' => $has_plan ? 'npcink-abilities-toolkit/build-block-theme-site-plan' : '',
			),
			'consensus'        => array(
				'decision'              => $has_plan ? 'build_minimal_plan' : ( empty( $issue_codes ) ? 'no_changes_required' : 'needs_human_review' ),
				'recommended_next_step' => $has_plan ? 'build_block_theme_site_plan' : ( empty( $issue_codes ) ? 'no_changes_required' : 'manual_review' ),
			),
		);
	}

	/**
	 * Returns the review contract used by the deterministic dual reviewers.
	 *
	 * @return array<string,mixed>
	 */
	private function block_theme_surface_review_contract() {
		return array(
			'reviewer_count' => 2,
			'pattern_source' => 'article_summary_coverage_check_style',
			'reviewers'      => array(
				array(
					'reviewer_id' => 'surface_issue_reviewer',
					'checks'      => array( 'breadcrumb_presence', 'breadcrumb_position', 'homepage_visibility', 'template_resolution' ),
				),
				array(
					'reviewer_id' => 'governance_boundary_reviewer',
					'checks'      => array( 'read_only_inspection', 'no_proposal_creation', 'minimal_plan_only', 'supported_issue_codes_only' ),
				),
			),
		);
	}

	/**
	 * Returns recommended next steps for a surface inspection.
	 *
	 * @param array<string,mixed> $dual_review Dual-review metadata.
	 * @return string[]
	 */
	private function block_theme_surface_next_steps( array $dual_review ) {
		$next_step = sanitize_key( (string) ( $dual_review['consensus']['recommended_next_step'] ?? '' ) );
		if ( 'build_block_theme_site_plan' === $next_step ) {
			return array( 'build_block_theme_site_plan', 'review_generated_write_actions', 'submit_core_proposal_only_if_actions_remain' );
		}
		if ( 'no_changes_required' === $next_step ) {
			return array( 'report_no_changes_required' );
		}
		return array( 'manual_review_required' );
	}

	/**
	 * Returns compact top-level block summaries.
	 *
	 * @param array<int,mixed> $blocks Blocks.
	 * @return array<int,array<string,mixed>>
	 */
	private function block_theme_top_level_block_summaries( array $blocks ) {
		$summaries = array();
		foreach ( array_values( $blocks ) as $index => $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			$summaries[] = array(
				'index'     => (int) $index,
				'blockName' => sanitize_text_field( (string) ( $block['blockName'] ?? '' ) ),
				'className' => sanitize_text_field( (string) ( $block['attrs']['className'] ?? '' ) ),
				'tagName'   => sanitize_key( (string) ( $block['attrs']['tagName'] ?? '' ) ),
				'slug'      => sanitize_key( (string) ( $block['attrs']['slug'] ?? '' ) ),
			);
		}
		return $summaries;
	}

	/**
	 * Counts matching blocks.
	 *
	 * @param array<int,mixed> $blocks Blocks.
	 * @param string           $match Match kind.
	 * @return int
	 */
	private function block_theme_count_matching_blocks( array $blocks, $match ) {
		$count = 0;
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			if ( ( 'breadcrumbs' === $match && $this->block_theme_is_breadcrumbs_block( $block ) ) || ( 'post_title' === $match && 'core/post-title' === (string) ( $block['blockName'] ?? '' ) ) ) {
				++$count;
			}
			$count += $this->block_theme_count_matching_blocks( is_array( $block['innerBlocks'] ?? null ) ? $block['innerBlocks'] : array(), $match );
		}
		return $count;
	}

	/**
	 * Returns whether a top-level breadcrumb block appears before the header.
	 *
	 * @param array<int,mixed> $blocks Blocks.
	 * @return bool
	 */
	private function block_theme_has_top_level_breadcrumb_before_header( array $blocks ) {
		$breadcrumb_index = null;
		$header_index     = null;
		foreach ( array_values( $blocks ) as $index => $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			if ( null === $breadcrumb_index && $this->block_theme_is_breadcrumbs_block( $block ) ) {
				$breadcrumb_index = (int) $index;
			}
			if ( null === $header_index && 'core/template-part' === (string) ( $block['blockName'] ?? '' ) && 'header' === sanitize_key( (string) ( $block['attrs']['slug'] ?? '' ) ) ) {
				$header_index = (int) $index;
			}
		}
		return null !== $breadcrumb_index && null !== $header_index && $breadcrumb_index < $header_index;
	}

	/**
	 * Returns whether a planned block tree differs from the current tree.
	 *
	 * @param array<int,array<string,mixed>> $current_blocks Current blocks.
	 * @param array<int,array<string,mixed>> $next_blocks Planned blocks.
	 * @return bool
	 */
	private function block_theme_blocks_require_write( array $current_blocks, array $next_blocks ) {
		$current = wp_json_encode( $current_blocks );
		$next    = wp_json_encode( $next_blocks );
		return ( is_string( $current ) ? $current : '' ) !== ( is_string( $next ) ? $next : '' );
	}

	/**
	 * Returns a public reason when a target needs no write action.
	 *
	 * @param array<string,mixed> $placement Breadcrumb placement report.
	 * @return string
	 */
	private function block_theme_no_change_reason( array $placement ) {
		$status   = sanitize_key( (string) ( $placement['status'] ?? '' ) );
		$strategy = sanitize_key( (string) ( $placement['strategy'] ?? '' ) );
		if ( 'already_valid' === $status ) {
			return 'breadcrumbs_already_before_post_title';
		}
		if ( 'skipped' === $status && 'home_page_disabled' === $strategy ) {
			return 'homepage_breadcrumbs_already_absent';
		}
		return 'blocks_already_match_plan';
	}

	/**
	 * Returns the batch next step from action, preview, and warning state.
	 *
	 * @param bool                 $ready_for_proposal Whether write actions are ready.
	 * @param array<int,mixed>     $write_actions Write actions.
	 * @param array<int,mixed>     $preview Preview rows.
	 * @param array<int,mixed>     $warnings Warning rows.
	 * @return string
	 */
	private function block_theme_batch_next_step( $ready_for_proposal, array $write_actions, array $preview, array $warnings ) {
		if ( $ready_for_proposal ) {
			return 'submit_core_proposal';
		}
		if ( empty( $write_actions ) && ! empty( $preview ) && empty( $warnings ) ) {
			return 'no_changes_required';
		}
		return 'revise_block_theme_site_plan';
	}

	/**
	 * Inserts a Core-block breadcrumb scaffold near the post title unless present.
	 *
	 * @param mixed               $blocks Existing blocks.
	 * @param array<string,mixed> $attrs Breadcrumb attributes.
	 * @param array<string,mixed> $placement Placement report.
	 * @return array<int,array<string,mixed>>
	 */
	private function block_theme_insert_breadcrumbs_block( $blocks, array $attrs, array &$placement = array() ) {
		$blocks = is_array( $blocks ) ? array_values( $blocks ) : array();
		$is_home_template = in_array( sanitize_key( (string) ( $attrs['templateSlug'] ?? '' ) ), array( 'front-page', 'home' ), true );
		if ( $is_home_template && empty( $attrs['showOnHomePage'] ) ) {
			$existing_breadcrumb_block = null;
			$blocks                    = $this->block_theme_extract_breadcrumbs_block( $blocks, $existing_breadcrumb_block );
			$placement                 = array(
				'status'          => is_array( $existing_breadcrumb_block ) ? 'removed' : 'skipped',
				'strategy'        => 'home_page_disabled',
				'container'       => 'home_template',
				'inserted_before' => '',
			);
			return $blocks;
		}

		if ( $this->block_theme_breadcrumbs_are_before_post_title_in_main( $blocks ) ) {
			$placement = array(
				'status'          => 'already_valid',
				'strategy'        => 'before_post_title_in_main',
				'container'       => 'main',
				'inserted_before' => 'core/post-title',
			);
			return $blocks;
		}

		$existing_breadcrumb_block = null;
		$blocks                    = $this->block_theme_extract_breadcrumbs_block( $blocks, $existing_breadcrumb_block );
		$breadcrumb_block          = is_array( $existing_breadcrumb_block ) ? $existing_breadcrumb_block : $this->block_theme_breadcrumbs_block( $attrs );
		$placement_status          = is_array( $existing_breadcrumb_block ) ? 'relocated' : 'inserted';
		$inserted                  = false;
		$next_blocks               = $this->block_theme_insert_breadcrumbs_before_title_in_main( $blocks, $breadcrumb_block, $inserted );
		if ( $inserted ) {
			$placement = array(
				'status'          => $placement_status,
				'strategy'        => 'before_post_title_in_main',
				'container'       => 'main',
				'inserted_before' => 'core/post-title',
			);
			return $next_blocks;
		}

		$next_blocks = $this->block_theme_insert_breadcrumbs_before_post_title( $blocks, $breadcrumb_block, $inserted );
		if ( $inserted ) {
			$placement = array(
				'status'          => $placement_status,
				'strategy'        => 'before_post_title',
				'container'       => 'nearest_post_title_parent',
				'inserted_before' => 'core/post-title',
			);
			return $next_blocks;
		}

		array_unshift( $blocks, $breadcrumb_block );
		$placement = array(
			'status'   => $placement_status,
			'strategy' => 'template_start_fallback',
		);
		return $blocks;
	}

	/**
	 * Reports whether breadcrumbs already sit directly before post title in main.
	 *
	 * @param mixed $blocks Blocks.
	 * @return bool
	 */
	private function block_theme_breadcrumbs_are_before_post_title_in_main( $blocks, $inside_main = false ) {
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
					if ( ! is_array( $inner_block ) || 'core/post-title' !== (string) ( $inner_block['blockName'] ?? '' ) ) {
						continue;
					}
					return $index > 0 && is_array( $inner_blocks[ $index - 1 ] ?? null ) && $this->block_theme_is_breadcrumbs_block( $inner_blocks[ $index - 1 ] );
				}
			}
			if ( $this->block_theme_breadcrumbs_are_before_post_title_in_main( $block['innerBlocks'] ?? array(), $in_main_tree ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Extracts the first breadcrumbs block and removes duplicate breadcrumb blocks.
	 *
	 * @param array<int,array<string,mixed>> $blocks Blocks.
	 * @param array<string,mixed>|null       $found First found breadcrumb block.
	 * @return array<int,array<string,mixed>>
	 */
	private function block_theme_extract_breadcrumbs_block( array $blocks, &$found ) {
		$clean_blocks = array();
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			if ( $this->block_theme_is_breadcrumbs_block( $block ) ) {
				if ( null === $found ) {
					$found = $block;
				}
				continue;
			}
			$clean_blocks[] = $this->block_theme_extract_breadcrumbs_from_block( $block, $found );
		}
		return $clean_blocks;
	}

	/**
	 * Removes breadcrumbs from one block's children while preserving first found.
	 *
	 * @param array<string,mixed>      $block Parent block.
	 * @param array<string,mixed>|null $found First found breadcrumb block.
	 * @return array<string,mixed>
	 */
	private function block_theme_extract_breadcrumbs_from_block( array $block, &$found ) {
		if ( ! is_array( $block['innerBlocks'] ?? null ) ) {
			return $block;
		}

		$inner_blocks       = array_values( $block['innerBlocks'] );
		$clean_inner_blocks = array();
		$removed_indices    = array();
		foreach ( $inner_blocks as $inner_index => $inner_block ) {
			if ( ! is_array( $inner_block ) ) {
				continue;
			}
			if ( $this->block_theme_is_breadcrumbs_block( $inner_block ) ) {
				if ( null === $found ) {
					$found = $inner_block;
				}
				$removed_indices[] = (int) $inner_index;
				continue;
			}
			$clean_inner_blocks[] = $this->block_theme_extract_breadcrumbs_from_block( $inner_block, $found );
		}

		$block['innerBlocks'] = $clean_inner_blocks;
		if ( ! empty( $removed_indices ) && is_array( $block['innerContent'] ?? null ) ) {
			$inner_content = array_values( $block['innerContent'] );
			rsort( $removed_indices );
			foreach ( $removed_indices as $removed_index ) {
				$null_offset = $this->block_theme_inner_content_null_offset_for_child_index( $inner_content, $removed_index );
				if ( array_key_exists( $null_offset, $inner_content ) && null === $inner_content[ $null_offset ] ) {
					array_splice( $inner_content, $null_offset, 1 );
				}
			}
			$block['innerContent'] = $inner_content;
		}
		return $block;
	}

	/**
	 * Reports whether one parsed block is the OpenClaw breadcrumbs block.
	 *
	 * @param array<string,mixed> $block Parsed block.
	 * @return bool
	 */
	private function block_theme_is_breadcrumbs_block( array $block ) {
		return false !== strpos( ' ' . (string) ( $block['attrs']['className'] ?? '' ) . ' ', ' openclaw-breadcrumbs ' );
	}

	/**
	 * Builds the breadcrumb block tree.
	 *
	 * @param array<string,mixed> $attrs Breadcrumb attributes.
	 * @return array<string,mixed>
	 */
	private function block_theme_breadcrumbs_block( array $attrs ) {
		$separator     = sanitize_text_field( (string) ( $attrs['separator'] ?? '/' ) );
		$show_current  = ! array_key_exists( 'showCurrentItem', $attrs ) || ! empty( $attrs['showCurrentItem'] );
		$show_home     = ! array_key_exists( 'showHomeItem', $attrs ) || ! empty( $attrs['showHomeItem'] );
		$home_url      = function_exists( 'home_url' ) ? (string) home_url( '/' ) : '/';
		$trail_content = array();
		if ( $show_home ) {
			$trail_content[] = '<a href="' . $this->esc_url_value( $home_url ) . '">' . esc_html( __( 'Home', 'npcink-abilities-toolkit' ) ) . '</a>';
		}
		if ( $show_current ) {
			if ( ! empty( $trail_content ) ) {
				$trail_content[] = '<span aria-hidden="true">' . esc_html( $separator ) . '</span>';
			}
			$trail_content[] = '<span class="openclaw-breadcrumbs__current" aria-current="page">' . esc_html( __( 'Current item', 'npcink-abilities-toolkit' ) ) . '</span>';
		}
		$paragraph_html = '<p class="openclaw-breadcrumbs__trail">' . implode( ' ', $trail_content ) . '</p>';

		return array(
			'blockName'    => 'core/group',
			'attrs'        => array(
				'className' => 'openclaw-breadcrumbs',
				'layout'    => array(
					'type'     => 'flex',
					'flexWrap' => 'wrap',
				),
			),
			'innerHTML'    => '',
			'innerBlocks'  => array(
				array(
					'blockName'    => 'core/paragraph',
					'attrs'        => array(
						'className' => 'openclaw-breadcrumbs__trail',
					),
					'innerHTML'    => $paragraph_html,
					'innerBlocks'  => array(),
					'innerContent' => array( $paragraph_html ),
				),
			),
			'innerContent' => array( "\n", null, "\n" ),
		);
	}

	/**
	 * Inserts breadcrumbs before post title inside the first main container.
	 *
	 * @param array<int,array<string,mixed>> $blocks Blocks.
	 * @param array<string,mixed>            $breadcrumb_block Breadcrumb block.
	 * @param bool                           $inserted Whether insertion happened.
	 * @return array<int,array<string,mixed>>
	 */
	private function block_theme_insert_breadcrumbs_before_title_in_main( array $blocks, array $breadcrumb_block, &$inserted ) {
		foreach ( $blocks as $index => $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			if ( $this->block_theme_is_main_container( $block ) ) {
				$inner_blocks = is_array( $block['innerBlocks'] ?? null ) ? array_values( $block['innerBlocks'] ) : array();
				foreach ( $inner_blocks as $inner_index => $inner_block ) {
					if ( is_array( $inner_block ) && 'core/post-title' === (string) ( $inner_block['blockName'] ?? '' ) ) {
						$blocks[ $index ] = $this->block_theme_insert_inner_block_at( $block, $inner_index, $breadcrumb_block );
						$inserted         = true;
						return $blocks;
					}
				}
			}
			if ( is_array( $block['innerBlocks'] ?? null ) ) {
				$block['innerBlocks'] = $this->block_theme_insert_breadcrumbs_before_title_in_main( array_values( $block['innerBlocks'] ), $breadcrumb_block, $inserted );
				$blocks[ $index ]     = $block;
				if ( $inserted ) {
					return $blocks;
				}
			}
		}
		return $blocks;
	}

	/**
	 * Inserts breadcrumbs before the first post title anywhere in the tree.
	 *
	 * @param array<int,array<string,mixed>> $blocks Blocks.
	 * @param array<string,mixed>            $breadcrumb_block Breadcrumb block.
	 * @param bool                           $inserted Whether insertion happened.
	 * @return array<int,array<string,mixed>>
	 */
	private function block_theme_insert_breadcrumbs_before_post_title( array $blocks, array $breadcrumb_block, &$inserted ) {
		foreach ( $blocks as $index => $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			if ( 'core/post-title' === (string) ( $block['blockName'] ?? '' ) ) {
				array_splice( $blocks, $index, 0, array( $breadcrumb_block ) );
				$inserted = true;
				return $blocks;
			}
			if ( is_array( $block['innerBlocks'] ?? null ) ) {
				$inner_blocks = array_values( $block['innerBlocks'] );
				foreach ( $inner_blocks as $inner_index => $inner_block ) {
					if ( is_array( $inner_block ) && 'core/post-title' === (string) ( $inner_block['blockName'] ?? '' ) ) {
						$blocks[ $index ] = $this->block_theme_insert_inner_block_at( $block, $inner_index, $breadcrumb_block );
						$inserted         = true;
						return $blocks;
					}
				}
				$block['innerBlocks'] = $this->block_theme_insert_breadcrumbs_before_post_title( $inner_blocks, $breadcrumb_block, $inserted );
				$blocks[ $index ]     = $block;
				if ( $inserted ) {
					return $blocks;
				}
			}
		}
		return $blocks;
	}

	/**
	 * Reports whether one parsed block is a main content container.
	 *
	 * @param array<string,mixed> $block Parsed block.
	 * @return bool
	 */
	private function block_theme_is_main_container( array $block ) {
		return 'main' === sanitize_key( (string) ( $block['attrs']['tagName'] ?? '' ) );
	}

	/**
	 * Inserts one inner block and keeps innerContent null placeholders aligned.
	 *
	 * @param array<string,mixed> $block Parent block.
	 * @param int                 $inner_index Inner block index.
	 * @param array<string,mixed> $insert_block Block to insert.
	 * @return array<string,mixed>
	 */
	private function block_theme_insert_inner_block_at( array $block, $inner_index, array $insert_block ) {
		$inner_blocks = is_array( $block['innerBlocks'] ?? null ) ? array_values( $block['innerBlocks'] ) : array();
		$inner_index  = max( 0, min( (int) $inner_index, count( $inner_blocks ) ) );
		array_splice( $inner_blocks, $inner_index, 0, array( $insert_block ) );
		$block['innerBlocks'] = $inner_blocks;

		$inner_content = is_array( $block['innerContent'] ?? null ) ? array_values( $block['innerContent'] ) : array();
		if ( empty( $inner_content ) ) {
			$block['innerContent'] = array_fill( 0, count( $inner_blocks ), null );
			return $block;
		}

		$null_offset = $this->block_theme_inner_content_null_offset_for_child_index( $inner_content, $inner_index );
		array_splice( $inner_content, $null_offset, 0, array( null ) );
		$block['innerContent'] = $inner_content;
		return $block;
	}

	/**
	 * Returns the innerContent offset where a child placeholder should be inserted.
	 *
	 * @param array<int,mixed> $inner_content Inner content.
	 * @param int              $child_index Child block index.
	 * @return int
	 */
	private function block_theme_inner_content_null_offset_for_child_index( array $inner_content, $child_index ) {
		$seen_children = 0;
		foreach ( $inner_content as $offset => $content ) {
			if ( null !== $content ) {
				continue;
			}
			if ( $seen_children >= $child_index ) {
				return (int) $offset;
			}
			++$seen_children;
		}
		return count( $inner_content );
	}

	/**
	 * Removes parse_blocks() whitespace-only freeform nodes from write plans.
	 *
	 * File-backed templates often parse into real blocks plus null blockName
	 * spacer nodes. The write abilities intentionally require named blocks, so
	 * only discard empty spacer nodes here and keep non-empty freeform content
	 * visible to validation.
	 *
	 * @param mixed $blocks Parsed blocks.
	 * @return array<int,array<string,mixed>>
	 */
	private function block_theme_blocks_for_write_plan( $blocks ) {
		$blocks     = is_array( $blocks ) ? array_values( $blocks ) : array();
		$normalized = array();
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			$block_name = (string) ( $block['blockName'] ?? '' );
			if ( '' === $block_name && $this->block_theme_is_empty_freeform_block( $block ) ) {
				continue;
			}
			if ( is_array( $block['innerBlocks'] ?? null ) ) {
				$block['innerBlocks'] = $this->block_theme_blocks_for_write_plan( $block['innerBlocks'] );
			}
			$normalized[] = $block;
		}
		return $normalized;
	}

	/**
	 * Reports whether a parsed freeform block carries only whitespace.
	 *
	 * @param array<string,mixed> $block Parsed block.
	 * @return bool
	 */
	private function block_theme_is_empty_freeform_block( array $block ) {
		$html = (string) ( $block['innerHTML'] ?? '' );
		if ( '' !== trim( $html ) ) {
			return false;
		}
		$inner_content = is_array( $block['innerContent'] ?? null ) ? $block['innerContent'] : array();
		foreach ( $inner_content as $content ) {
			if ( null !== $content && '' !== trim( (string) $content ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Counts a block tree recursively.
	 *
	 * @param mixed $blocks Blocks.
	 * @return int
	 */
	private function block_theme_count_blocks( $blocks ) {
		$blocks = is_array( $blocks ) ? $blocks : array();
		$total  = 0;
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			++$total;
			$total += $this->block_theme_count_blocks( $block['innerBlocks'] ?? array() );
		}
		return $total;
	}
}
