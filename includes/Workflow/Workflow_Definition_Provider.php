<?php
/**
 * Static workflow definition provider.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Workflow;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides read-only workflow recipe definitions for host discovery.
 */
final class Workflow_Definition_Provider {
	/**
	 * Returns the workflow definition manifest.
	 *
	 * @return array<string,mixed>
	 */
	public static function manifest() {
		return array(
			'schema_version' => 'v1',
			'purpose'        => 'AI consumer task replay and workflow definition cases for local host-side ability recipes.',
			'cases'          => self::definitions(),
		);
	}

	/**
	 * Returns workflow definitions keyed by case id.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function definitions() {
		return array(
			'article_draft'             => array(
				'definition_kind'                => 'workflow_recipe',
				'contract_version'               => 'v1',
				'title'                          => 'Article draft handoff',
				'natural_tasks'                  => array(
					'Prepare a reviewed article draft plan.',
					'Compose article metadata, links, media SEO, and review signals before creating a draft.',
					'Build a local article draft handoff without using cloud writing.',
				),
				'preferred_ability_id'           => 'npcink-abilities-toolkit/compose-article-draft-result',
				'entrypoint_ability_id'          => 'npcink-abilities-toolkit/compose-article-draft-result',
				'expanded_ability_ids'           => array(
					'npcink-abilities-toolkit/resolve-post-metadata-plan',
					'npcink-abilities-toolkit/resolve-internal-link-targets',
					'npcink-abilities-toolkit/build-inline-image-blocks',
					'npcink-abilities-toolkit/build-media-seo-assets',
					'npcink-abilities-toolkit/review-article-output-light',
					'npcink-abilities-toolkit/compose-article-draft-result',
				),
				'recipe_id'                      => 'npcink-abilities-toolkit/recipes/article-draft',
				'recipe_aliases'                 => array( 'article_draft_v1' ),
				'required_scope'                 => 'cap.text.extract',
				'required_inputs'                => array(),
				'expected_sections'              => array(
					'article',
					'draft',
					'metadata_plan_resolution',
					'review',
					'handoff',
				),
				'handoff'                        => array(
					'kind'        => 'suggestion',
					'owner'       => 'host',
					'next_action' => 'review_local_article_draft_then_request_core_approval_for_create_draft',
				),
				'failure_policy'                 => 'fail_closed',
				'disallowed_default_ability_ids' => array(
					'npcink-abilities-toolkit/create-draft',
					'npcink-abilities-toolkit/update-post',
					'npcink-abilities-toolkit/patch-post-content',
					'npcink-abilities-toolkit/publish-post',
				),
				'host_governed_write_boundary'   => true,
			),
			'article_publish_preflight' => array(
				'definition_kind'                => 'workflow_recipe',
				'contract_version'               => 'v1',
				'title'                          => 'Article publish preflight',
				'natural_tasks'                  => array(
					'Check whether this draft is ready to publish.',
					'Review the article before scheduling it.',
					'Find publication risks and calendar pressure for this post.',
				),
				'preferred_ability_id'           => 'npcink-abilities-toolkit/get-article-publish-preflight-context',
				'entrypoint_ability_id'          => 'npcink-abilities-toolkit/get-article-publish-preflight-context',
				'expanded_ability_ids'           => array(
					'npcink-abilities-toolkit/get-post-context',
					'npcink-abilities-toolkit/get-content-publishing-checklist',
					'npcink-abilities-toolkit/get-post-publish-risk-report',
					'npcink-abilities-toolkit/build-article-workflow-context',
					'npcink-abilities-toolkit/get-publishing-calendar-context',
				),
				'recipe_id'                      => 'npcink-abilities-toolkit/recipes/article-publish-preflight',
				'required_scope'                 => 'post.read',
				'required_inputs'                => array( 'post_id' ),
				'expected_sections'              => array(
					'post_context',
					'publishing_checklist',
					'publish_risk',
					'workflow_context',
					'publishing_calendar',
				),
				'handoff'                        => array(
					'kind'        => 'context',
					'owner'       => 'host',
					'next_action' => 'review_publish_readiness_then_request_host_approval_for_any_write',
				),
				'failure_policy'                 => 'fail_closed',
				'disallowed_default_ability_ids' => array(
					'npcink-abilities-toolkit/schedule-post',
					'npcink-abilities-toolkit/publish-post',
				),
				'host_governed_write_boundary'   => true,
			),
			'article_optimization'      => array(
				'definition_kind'                => 'workflow_recipe',
				'contract_version'               => 'v1',
				'title'                          => 'Existing article optimization',
				'natural_tasks'                  => array(
					'Review this existing article for optimization opportunities.',
					'Prepare SEO, GEO, excerpt, and slug suggestions for a post.',
					'Build a safe apply plan before asking a host to patch article content.',
				),
				'preferred_ability_id'           => 'npcink-abilities-toolkit/read-post-optimization-context',
				'entrypoint_ability_id'          => 'npcink-abilities-toolkit/read-post-optimization-context',
				'expanded_ability_ids'           => array(
					'npcink-abilities-toolkit/read-post-optimization-context',
					'npcink-abilities-toolkit/seo-report-context',
					'npcink-abilities-toolkit/build-article-single-optimization-suggest',
					'npcink-abilities-toolkit/build-article-optimization-apply-plan',
					'npcink-abilities-toolkit/compose-article-optimization-apply-result',
				),
				'recipe_id'                      => 'npcink-abilities-toolkit/recipes/article-optimization',
				'required_scope'                 => 'post.read',
				'required_inputs'                => array( 'post_id' ),
				'expected_sections'              => array(
					'post_context',
					'seo_report',
					'optimization_suggestion',
					'apply_plan',
					'handoff',
				),
				'handoff'                        => array(
					'kind'        => 'suggestion',
					'owner'       => 'host',
					'next_action' => 'review_optimization_apply_plan_then_request_host_approval_for_any_post_write',
				),
				'failure_policy'                 => 'fail_closed',
				'disallowed_default_ability_ids' => array(
					'npcink-abilities-toolkit/patch-post-content',
					'npcink-abilities-toolkit/set-post-seo-meta',
					'npcink-abilities-toolkit/update-post-blocks',
				),
				'host_governed_write_boundary'   => true,
			),
			'media_optimization'        => array(
				'definition_kind'                => 'workflow_recipe',
				'contract_version'               => 'v1',
				'title'                          => 'Governed media optimization',
				'natural_tasks'                  => array(
					'Prepare a reviewed media optimization plan for one attachment.',
					'Build metadata and derivative adoption actions before asking Core for approval.',
					'Preview media replacement and content reference repairs without changing WordPress.',
				),
				'preferred_ability_id'           => 'npcink-abilities-toolkit/build-media-optimization-plan',
				'entrypoint_ability_id'          => 'npcink-abilities-toolkit/build-media-optimization-plan',
				'expanded_ability_ids'           => array(
					'npcink-abilities-toolkit/build-media-optimization-plan',
				),
				'recipe_id'                      => 'npcink-abilities-toolkit/recipes/media-optimization',
				'recipe_aliases'                 => array( 'media_optimization_v1' ),
				'required_scope'                 => 'media.read',
				'required_inputs'                => array( 'attachment_id', 'media_details_input', 'derivative_artifact' ),
				'expected_sections'              => array(
					'artifact_type',
					'proposal_mode',
					'write_actions',
					'derivative_preview',
					'content_reference_repairs_preview',
				),
				'handoff'                        => array(
					'kind'        => 'approval_request',
					'owner'       => 'host',
					'next_action' => 'submit_reviewed_media_optimization_plan_to_core_then_stop',
				),
				'failure_policy'                 => 'fail_closed',
				'disallowed_default_ability_ids' => array(
					'npcink-abilities-toolkit/update-media-details',
					'npcink-abilities-toolkit/adopt-cloud-media-derivative',
				),
				'host_governed_write_boundary'   => true,
			),
			'article_media_handoff'     => array(
				'definition_kind'                => 'workflow_recipe',
				'contract_version'               => 'v1',
				'title'                          => 'Article media handoff',
				'natural_tasks'                  => array(
					'Prepare media SEO assets for this article.',
					'Build inline image blocks and placement guidance before importing media.',
					'Create a media handoff that a host can review before any upload or metadata write.',
				),
				'preferred_ability_id'           => 'npcink-abilities-toolkit/build-media-seo-assets',
				'entrypoint_ability_id'          => 'npcink-abilities-toolkit/build-media-seo-assets',
				'expanded_ability_ids'           => array(
					'npcink-abilities-toolkit/get-post-context',
					'npcink-abilities-toolkit/build-inline-image-blocks',
					'npcink-abilities-toolkit/build-media-seo-assets',
					'npcink-abilities-toolkit/position-inline-image-blocks',
				),
				'recipe_id'                      => 'npcink-abilities-toolkit/recipes/article-media-handoff',
				'required_scope'                 => 'media.read',
				'required_inputs'                => array(),
				'expected_sections'              => array(
					'post_context',
					'media_assets',
					'inline_blocks',
					'positioned_blocks',
					'handoff',
				),
				'handoff'                        => array(
					'kind'        => 'suggestion',
					'owner'       => 'host',
					'next_action' => 'review_media_assets_then_request_host_approval_for_upload_or_metadata_writes',
				),
				'failure_policy'                 => 'fail_closed',
				'disallowed_default_ability_ids' => array(
					'npcink-abilities-toolkit/upload-media-from-url',
					'npcink-abilities-toolkit/update-media-details',
					'npcink-abilities-toolkit/set-post-featured-image',
				),
				'host_governed_write_boundary'   => true,
			),
			'old_article_refresh_discovery' => array(
				'definition_kind'                => 'workflow_recipe',
				'contract_version'               => 'v1',
				'title'                          => 'Old article refresh discovery',
				'natural_tasks'                  => array(
					'Find old articles that need refreshing.',
					'Discover SEO and GEO gaps across existing posts.',
					'Choose candidate posts for an article refresh plan.',
				),
				'preferred_ability_id'           => 'npcink-abilities-toolkit/get-old-article-refresh-context',
				'entrypoint_ability_id'          => 'npcink-abilities-toolkit/get-old-article-refresh-context',
				'expanded_ability_ids'           => array(
					'npcink-abilities-toolkit/get-content-refresh-opportunities',
					'npcink-abilities-toolkit/get-seo-geo-gap-report',
					'npcink-abilities-toolkit/get-site-style-baseline',
					'npcink-abilities-toolkit/get-internal-link-graph-health',
					'npcink-abilities-toolkit/get-internal-link-opportunity-report',
				),
				'recipe_id'                      => 'npcink-abilities-toolkit/recipes/old-article-refresh-discovery',
				'required_scope'                 => 'post.read',
				'required_inputs'                => array(),
				'expected_sections'              => array(
					'refresh_opportunities',
					'seo_geo_gap_report',
					'site_style_baseline',
					'internal_link_graph_health',
				),
				'handoff'                        => array(
					'kind'        => 'context',
					'owner'       => 'host',
					'next_action' => 'choose_refresh_candidates_then_open_host_governed_optimization',
				),
				'failure_policy'                 => 'fail_closed',
				'disallowed_default_ability_ids' => array(
					'npcink-abilities-toolkit/patch-post-content',
					'npcink-abilities-toolkit/update-post',
					'npcink-abilities-toolkit/update-post-blocks',
				),
				'host_governed_write_boundary'   => true,
			),
			'comment_compliance_handoff'    => array(
				'definition_kind'                => 'workflow_recipe',
				'contract_version'               => 'v1',
				'title'                          => 'Comment compliance handoff',
				'natural_tasks'                  => array(
					'Triage comments waiting for moderation.',
					'Prepare moderation suggestions without approving anything.',
					'Build a reply or moderation handoff for a selected comment.',
				),
				'preferred_ability_id'           => 'npcink-abilities-toolkit/get-comment-compliance-handoff',
				'entrypoint_ability_id'          => 'npcink-abilities-toolkit/get-comment-compliance-handoff',
				'expanded_ability_ids'           => array(
					'npcink-abilities-toolkit/get-comment-queue-health',
					'npcink-abilities-toolkit/get-comment-action-priority-queue',
					'npcink-abilities-toolkit/build-comment-moderation-suggest',
					'npcink-abilities-toolkit/build-comment-mention-reply-suggest',
					'npcink-abilities-toolkit/compose-comment-moderation-result',
				),
				'recipe_id'                      => 'npcink-abilities-toolkit/recipes/comment-compliance-handoff',
				'required_scope'                 => 'comments.manage',
				'required_inputs'                => array(),
				'expected_sections'              => array(
					'queue_health',
					'priority_queue',
					'selected_moderation_suggestion',
				),
				'handoff'                        => array(
					'kind'        => 'context',
					'owner'       => 'host',
					'next_action' => 'review_comment_suggestions_then_request_host_approval_for_any_action',
				),
				'failure_policy'                 => 'fail_closed',
				'disallowed_default_ability_ids' => array(
					'npcink-abilities-toolkit/approve-comment',
					'npcink-abilities-toolkit/reply-comment',
					'npcink-abilities-toolkit/spam-comment',
					'npcink-abilities-toolkit/trash-comment',
				),
				'host_governed_write_boundary'   => true,
			),
		);
	}

	/**
	 * Returns one workflow definition by recipe id or case id.
	 *
	 * @param string $id Recipe id or case id.
	 * @return array<string,mixed>|null
	 */
	public static function get( $id ) {
		$id = (string) $id;
		foreach ( self::definitions() as $case_id => $definition ) {
			if ( $id === $case_id || $id === (string) ( $definition['recipe_id'] ?? '' ) ) {
				return $definition;
			}
			if ( isset( $definition['recipe_aliases'] ) && is_array( $definition['recipe_aliases'] ) && in_array( $id, $definition['recipe_aliases'], true ) ) {
				return $definition;
			}
		}

		return null;
	}

	/**
	 * Returns forbidden runtime/governance field names.
	 *
	 * @return string[]
	 */
	public static function forbidden_field_keys() {
		return array(
			'workflow_state',
			'execution_state',
			'schedule',
			'scheduler',
			'retry_policy',
			'queue',
			'lease',
			'model',
			'model_routing',
			'prompt',
			'prompt_registry',
			'approval_store',
			'approval_policy',
			'audit_log',
			'quota',
			'commit_policy',
			'final_write_authority',
		);
	}
}
