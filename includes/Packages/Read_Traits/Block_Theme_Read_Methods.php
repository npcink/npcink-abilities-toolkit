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
		return array(
			'active_theme'         => $theme,
			'is_block_theme'       => $this->block_theme_is_active_block_theme(),
			'templates'            => $this->block_theme_template_rows( 'wp_template', array( 'single', 'page', 'archive', 'index' ) ),
			'template_parts'       => $this->block_theme_template_rows( 'wp_template_part', array( 'header', 'footer' ) ),
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
		if ( 'add_breadcrumbs' !== $intent ) {
			return new \WP_Error( 'npcink_abilities_toolkit_block_theme_intent_invalid', __( 'Block theme site plans currently support intent=add_breadcrumbs only.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( ! $this->block_theme_is_active_block_theme() ) {
			return new \WP_Error( 'npcink_abilities_toolkit_block_theme_required', __( 'The active theme is not reported as a block theme.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$target_templates = $this->block_theme_target_slugs( $input['target_templates'] ?? array( 'single', 'page' ) );
		$separator        = sanitize_text_field( (string) ( $input['separator'] ?? '/' ) );
		$show_current     = ! array_key_exists( 'show_current_item', $input ) || ! empty( $input['show_current_item'] );
		$show_home        = ! array_key_exists( 'show_home_item', $input ) || ! empty( $input['show_home_item'] );
		$show_on_home     = ! empty( $input['show_on_home_page'] );
		$write_actions    = array();
		$preview          = array();
		$warnings         = array();
		$block_editor_reviews = array();

		foreach ( $target_templates as $template_slug ) {
			$template = $this->block_theme_find_entity( 'wp_template', $template_slug, 0 );
			if ( empty( $template ) ) {
				$warnings[] = array(
					'target_type' => 'wp_template',
					'slug'        => $template_slug,
					'reason'      => 'template_not_found',
				);
				continue;
			}

			$template_id     = absint( $template['post_id'] ?? 0 );
			$template_slug   = sanitize_key( (string) ( $template['slug'] ?? $template_slug ) );
			$template_theme  = sanitize_key( (string) ( $template['theme'] ?? '' ) );
			$template_title  = sanitize_text_field( (string) ( $template['title'] ?? $template_slug ) );
			$template_source = sanitize_key( (string) ( $template['source'] ?? '' ) );
			$current_blocks = function_exists( 'parse_blocks' ) ? parse_blocks( (string) ( $template['content'] ?? '' ) ) : array();
			$current_blocks = $this->block_theme_blocks_for_write_plan( $current_blocks );
			$next_blocks    = $this->block_theme_insert_breadcrumbs_block(
				$current_blocks,
				array(
					'separator'          => $separator,
					'showCurrentItem'    => $show_current,
					'showHomeItem'       => $show_home,
					'showOnHomePage'     => $show_on_home,
				)
			);
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
				'block_count_before'        => $this->block_theme_count_blocks( $current_blocks ),
				'block_count_after'         => $this->block_theme_count_blocks( $next_blocks ),
				'inserted_block'            => 'core/group.openclaw-breadcrumbs',
				'block_editor_quality_gate' => $block_editor_quality_gate,
			);
			$block_editor_reviews[] = array(
				'target_type'               => 'wp_template',
				'post_id'                   => $template_id,
				'slug'                      => $template_slug,
				'theme'                     => $template_theme,
				'target_ability_id'         => $target_ability_id,
				'creates_template_override' => ! $is_existing_custom_template,
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
			'recommended_next_step' => $batch_ready_for_proposal ? 'submit_core_proposal' : 'revise_block_theme_site_plan',
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
	private function block_theme_target_slugs( $targets ) {
		$targets = is_array( $targets ) ? $targets : array( 'single', 'page' );
		$allowed = array( 'single', 'page', 'archive', 'index' );
		$normalized = array();
		foreach ( $targets as $target ) {
			$slug = sanitize_key( (string) $target );
			if ( in_array( $slug, $allowed, true ) && ! in_array( $slug, $normalized, true ) ) {
				$normalized[] = $slug;
			}
		}
		return empty( $normalized ) ? array( 'single', 'page' ) : $normalized;
	}

		/**
		 * Inserts a Core-block breadcrumb scaffold at the beginning unless present.
	 *
	 * @param mixed               $blocks Existing blocks.
	 * @param array<string,mixed> $attrs Breadcrumb attributes.
	 * @return array<int,array<string,mixed>>
	 */
		private function block_theme_insert_breadcrumbs_block( $blocks, array $attrs ) {
			$blocks = is_array( $blocks ) ? array_values( $blocks ) : array();
			foreach ( $blocks as $block ) {
				if ( is_array( $block ) && false !== strpos( ' ' . (string) ( $block['attrs']['className'] ?? '' ) . ' ', ' openclaw-breadcrumbs ' ) ) {
					return $blocks;
				}
			}

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

			array_unshift(
				$blocks,
				array(
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
				)
			);
		return $blocks;
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
