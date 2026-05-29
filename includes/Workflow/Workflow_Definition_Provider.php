<?php
/**
 * Static workflow definition provider.
 *
 * @package MagickAIAbilities
 */

namespace Magick_AI_Abilities\Workflow;

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
			'purpose'        => 'AI consumer task replay and workflow definition cases for the first three workflow bundle entry points.',
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
			'article_publish_preflight'      => array(
				'definition_kind'                => 'workflow_recipe',
				'contract_version'               => 'v1',
				'title'                          => 'Article publish preflight',
				'natural_tasks'                  => array(
					'Check whether this draft is ready to publish.',
					'Review the article before scheduling it.',
					'Find publication risks and calendar pressure for this post.',
				),
				'preferred_ability_id'           => 'magick-ai/get-article-publish-preflight-context',
				'entrypoint_ability_id'          => 'magick-ai/get-article-publish-preflight-context',
				'expanded_ability_ids'           => array(
					'magick-ai/get-post-context',
					'magick-ai/get-content-publishing-checklist',
					'magick-ai/get-post-publish-risk-report',
					'magick-ai/build-article-workflow-context',
					'magick-ai/get-publishing-calendar-context',
				),
				'recipe_id'                      => 'workflow/wordpress_article_publish_preflight',
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
					'magick-ai/schedule-post',
					'magick-ai/publish-post',
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
				'preferred_ability_id'           => 'magick-ai/get-old-article-refresh-context',
				'entrypoint_ability_id'          => 'magick-ai/get-old-article-refresh-context',
				'expanded_ability_ids'           => array(
					'magick-ai/get-content-refresh-opportunities',
					'magick-ai/get-seo-geo-gap-report',
					'magick-ai/get-site-style-baseline',
					'magick-ai/get-internal-link-graph-health',
					'magick-ai/get-internal-link-opportunity-report',
				),
				'recipe_id'                      => 'workflow/wordpress_old_article_refresh_discovery',
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
					'magick-ai/patch-post-content',
					'magick-ai/update-post',
					'magick-ai/update-post-blocks',
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
				'preferred_ability_id'           => 'magick-ai/get-comment-compliance-handoff',
				'entrypoint_ability_id'          => 'magick-ai/get-comment-compliance-handoff',
				'expanded_ability_ids'           => array(
					'magick-ai/get-comment-queue-health',
					'magick-ai/get-comment-action-priority-queue',
					'magick-ai/build-comment-moderation-suggest',
					'magick-ai/build-comment-mention-reply-suggest',
					'magick-ai/compose-comment-moderation-result',
				),
				'recipe_id'                      => 'workflow/wordpress_comment_compliance_handoff',
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
					'magick-ai/approve-comment',
					'magick-ai/reply-comment',
					'magick-ai/spam-comment',
					'magick-ai/trash-comment',
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
