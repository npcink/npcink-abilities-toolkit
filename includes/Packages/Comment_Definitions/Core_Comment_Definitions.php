<?php
/**
 * Core comment helper ability definitions.
 *
 * @package MagickAIAbilities
 */

namespace Magick_AI_Abilities\Packages\Comment_Definitions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides built-in comment helper ability definitions.
 */
final class Core_Comment_Definitions {
	/**
	 * Returns comment helper ability definitions.
	 *
	 * @param object $callbacks Callback owner.
	 * @return array<string,array<string,mixed>>
	 */
	public static function definitions( $callbacks ) {
		$success_schema = array(
			'type'       => 'object',
			'properties' => array(
				'success' => array( 'type' => 'boolean' ),
				'data'    => array( 'type' => 'object', 'additionalProperties' => true ),
				'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
				'message' => array( 'type' => 'string' ),
			),
			'required'   => array( 'success', 'data' ),
		);

		$comment_style_properties = array(
			'site_voice'           => array( 'type' => array( 'string', 'null' ) ),
			'reply_tone'           => array( 'type' => array( 'string', 'null' ) ),
			'persona_profile'      => array( 'type' => array( 'string', 'null' ) ),
			'reply_length_policy'  => array( 'type' => array( 'string', 'null' ) ),
			'require_human_review' => array( 'type' => array( 'boolean', 'null' ) ),
		);

		return array(
			'magick-ai/build-comment-moderation-suggest' => array(
				'label'            => __( 'Build Comment Moderation Suggestion', 'magick-ai-abilities' ),
				'description'      => __( 'Reads one comment and builds a deterministic moderation suggestion without writing.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-comments',
				'capability'       => 'moderate_comments',
				'required_scope'   => 'comments.manage',
				'required_scopes'  => array( 'comments.manage', 'cap.text.generate' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'annotations'      => array(
					'instructions' => 'Internal workflow helper only. Build one deterministic comment moderation suggestion and validate suggest/action inputs.',
				),
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array_merge(
						array(
							'comment_id'      => array( 'type' => 'integer', 'minimum' => 1 ),
							'mode'            => array(
								'type'    => 'string',
								'enum'    => array( 'suggest', 'action' ),
								'default' => 'suggest',
							),
							'allowed_actions' => array(
								'type'  => array( 'array', 'null' ),
								'items' => array(
									'type' => 'string',
									'enum' => array( 'approve', 'spam', 'trash', 'reply', 'escalate' ),
								),
							),
							'action_override' => array(
								'type' => array( 'string', 'null' ),
								'enum' => array( 'approve', 'spam', 'trash', 'reply', 'escalate', null ),
							),
							'reply_text'      => array( 'type' => array( 'string', 'null' ) ),
						),
						$comment_style_properties
					),
					'required'             => array( 'comment_id' ),
					'additionalProperties' => false,
				),
				'output_schema'    => $success_schema,
				'execute_callback' => array( $callbacks, 'build_comment_moderation_suggest' ),
			),
			'magick-ai/compose-comment-moderation-result' => array(
				'label'            => __( 'Compose Comment Moderation Result', 'magick-ai-abilities' ),
				'description'      => __( 'Composes one canonical comment moderation suggest/action result envelope.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-comments',
				'capability'       => 'moderate_comments',
				'required_scope'   => 'comments.manage',
				'required_scopes'  => array( 'comments.manage' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'annotations'      => array(
					'instructions' => 'Internal workflow helper only. Keep one canonical result envelope for comment moderation suggest/action runs.',
				),
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'comment_id'            => array( 'type' => 'integer', 'minimum' => 1 ),
						'mode'                  => array(
							'type'    => 'string',
							'enum'    => array( 'suggest', 'action' ),
							'default' => 'suggest',
						),
						'suggest_result'        => array( 'type' => 'object', 'additionalProperties' => true ),
						'reply_suggestion'      => array( 'type' => array( 'string', 'null' ) ),
						'reply_text'            => array( 'type' => array( 'string', 'null' ) ),
						'action_override'       => array(
							'type' => array( 'string', 'null' ),
							'enum' => array( 'approve', 'spam', 'trash', 'reply', 'escalate', null ),
						),
						'action_result_approve' => array( 'type' => array( 'object', 'array', 'null' ), 'additionalProperties' => true ),
						'action_result_spam'    => array( 'type' => array( 'object', 'array', 'null' ), 'additionalProperties' => true ),
						'action_result_trash'   => array( 'type' => array( 'object', 'array', 'null' ), 'additionalProperties' => true ),
						'action_result_reply'   => array( 'type' => array( 'object', 'array', 'null' ), 'additionalProperties' => true ),
					),
					'required'             => array( 'comment_id', 'mode', 'suggest_result' ),
					'additionalProperties' => false,
				),
				'output_schema'    => $success_schema,
				'execute_callback' => array( $callbacks, 'compose_comment_moderation_result' ),
			),
			'magick-ai/build-comment-mention-reply-suggest' => array(
				'label'            => __( 'Build Comment Mention Reply Suggestion', 'magick-ai-abilities' ),
				'description'      => __( 'Detects a comment mention/followup trigger and prepares a reply suggestion handoff without writing.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-comments',
				'capability'       => 'moderate_comments',
				'required_scope'   => 'comments.manage',
				'required_scopes'  => array( 'comments.manage', 'cap.text.generate' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'annotations'      => array(
					'instructions' => 'Internal workflow helper only. Detect comment mention/followup trigger and prepare a reply-suggestion handoff without writing.',
				),
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array_merge(
						array(
							'comment_id'   => array( 'type' => 'integer', 'minimum' => 1 ),
							'trigger_type' => array(
								'type'    => 'string',
								'enum'    => array( 'mention', 'followup', 'support_request' ),
								'default' => 'mention',
							),
						),
						$comment_style_properties
					),
					'required'             => array( 'comment_id' ),
					'additionalProperties' => false,
				),
				'output_schema'    => $success_schema,
				'execute_callback' => array( $callbacks, 'build_comment_mention_reply_suggest' ),
			),
			'magick-ai/read-comment-trigger-queue' => array(
				'label'            => __( 'Read Comment Trigger Queue', 'magick-ai-abilities' ),
				'description'      => __( 'Reads existing comments and builds a trigger candidate queue without writing.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-comments',
				'capability'       => 'moderate_comments',
				'required_scope'   => 'comments.manage',
				'required_scopes'  => array( 'comments.manage' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'annotations'      => array(
					'instructions' => 'Internal read layer only. Build one trigger-candidate queue projection from existing comments without writing.',
				),
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_id'      => array( 'type' => 'integer', 'minimum' => 1 ),
						'status'       => array(
							'type'    => 'string',
							'enum'    => array( 'hold', 'approve', 'all' ),
							'default' => 'hold',
						),
						'trigger_type' => array(
							'type'    => 'string',
							'enum'    => array( 'mention', 'followup', 'support_request' ),
							'default' => 'mention',
						),
						'per_page'     => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10 ),
						'page'         => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
					),
					'additionalProperties' => false,
				),
				'output_schema'    => $success_schema,
				'execute_callback' => array( $callbacks, 'read_comment_trigger_queue' ),
			),
			'magick-ai/get-comment-queue-health' => array(
				'label'            => __( 'Get Comment Queue Health', 'magick-ai-abilities' ),
				'description'      => __( 'Reads a bounded comment queue and summarizes moderation, reply, and escalation health without writing.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-comments',
				'capability'       => 'moderate_comments',
				'required_scope'   => 'comments.manage',
				'required_scopes'  => array( 'comments.manage' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'annotations'      => array(
					'instructions' => 'Internal read layer only. Summarize comment queue health and do not execute moderation actions.',
				),
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_id'  => array( 'type' => 'integer', 'minimum' => 1 ),
						'status'   => array(
							'type'    => 'string',
							'enum'    => array( 'hold', 'approve', 'spam', 'trash', 'all' ),
							'default' => 'hold',
						),
						'per_page' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 50 ),
						'page'     => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
					),
					'additionalProperties' => false,
				),
				'output_schema'    => $success_schema,
				'execute_callback' => array( $callbacks, 'get_comment_queue_health' ),
			),
			'magick-ai/get-comment-action-priority-queue' => array(
				'label'            => __( 'Get Comment Action Priority Queue', 'magick-ai-abilities' ),
				'description'      => __( 'Reads a bounded comment queue and returns prioritized read-only moderation and reply handoff candidates.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-comments',
				'capability'       => 'moderate_comments',
				'required_scope'   => 'comments.manage',
				'required_scopes'  => array( 'comments.manage' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'annotations'      => array(
					'instructions' => 'Internal read layer only. Prioritize comment action handoffs and do not execute moderation actions.',
				),
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_id'  => array( 'type' => 'integer', 'minimum' => 1 ),
						'status'   => array(
							'type'    => 'string',
							'enum'    => array( 'hold', 'approve', 'spam', 'trash', 'all' ),
							'default' => 'hold',
						),
						'per_page' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 50 ),
						'page'     => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
					),
					'additionalProperties' => false,
				),
				'output_schema'    => $success_schema,
				'execute_callback' => array( $callbacks, 'get_comment_action_priority_queue' ),
			),
			'magick-ai/get-comment-compliance-handoff' => array(
				'label'            => __( 'Get Comment Compliance Handoff', 'magick-ai-abilities' ),
				'description'      => __( 'Aggregates comment queue health, action priority rows, and optional selected-comment suggestions for host-side compliance workflows without writing.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-comments',
				'capability'       => 'moderate_comments',
				'required_scope'   => 'comments.manage',
				'required_scopes'  => array( 'comments.manage' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'annotations'      => array(
					'instructions' => 'Internal read layer only. Build a comment compliance handoff bundle and do not execute moderation actions.',
				),
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array_merge(
						array(
							'post_id'             => array( 'type' => 'integer', 'minimum' => 1 ),
							'status'              => array(
								'type'    => 'string',
								'enum'    => array( 'hold', 'approve', 'spam', 'trash', 'all' ),
								'default' => 'hold',
							),
							'per_page'            => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 50 ),
							'page'                => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
							'selected_comment_id' => array( 'type' => 'integer', 'minimum' => 1 ),
						),
						$comment_style_properties
					),
					'additionalProperties' => false,
				),
				'output_schema'    => $success_schema,
				'execute_callback' => array( $callbacks, 'get_comment_compliance_handoff' ),
			),
			'magick-ai/compose-comment-mention-reply-result' => array(
				'label'            => __( 'Compose Comment Mention Reply Result', 'magick-ai-abilities' ),
				'description'      => __( 'Composes one canonical mention-triggered comment reply suggestion envelope.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-comments',
				'capability'       => 'moderate_comments',
				'required_scope'   => 'comments.manage',
				'required_scopes'  => array( 'comments.manage' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'annotations'      => array(
					'instructions' => 'Internal workflow helper only. Keep one canonical result envelope for mention-triggered comment reply suggestions.',
				),
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'comment_id'       => array( 'type' => 'integer', 'minimum' => 1 ),
						'suggest_result'   => array( 'type' => 'object', 'additionalProperties' => true ),
						'reply_suggestion' => array( 'type' => array( 'string', 'null' ) ),
					),
					'required'             => array( 'comment_id', 'suggest_result' ),
					'additionalProperties' => false,
				),
				'output_schema'    => $success_schema,
				'execute_callback' => array( $callbacks, 'compose_comment_mention_reply_result' ),
			),
			'magick-ai/build-comment-moderation-batch-suggest' => array(
				'label'            => __( 'Build Comment Moderation Batch Suggestion', 'magick-ai-abilities' ),
				'description'      => __( 'Builds read-only moderation suggestions for a bounded list of comments.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-comments',
				'capability'       => 'moderate_comments',
				'required_scope'   => 'comments.manage',
				'required_scopes'  => array( 'comments.manage', 'cap.text.generate' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'annotations'      => array(
					'instructions' => 'Internal workflow helper only. Build one read-only batch suggestion payload and do not execute comment actions.',
				),
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array_merge(
						array(
							'comment_ids'     => array(
								'type'  => 'array',
								'items' => array( 'type' => 'integer', 'minimum' => 1 ),
							),
							'mode'            => array(
								'type'    => 'string',
								'enum'    => array( 'suggest' ),
								'default' => 'suggest',
							),
							'allowed_actions' => array(
								'type'  => 'array',
								'items' => array(
									'type' => 'string',
									'enum' => array( 'approve', 'spam', 'trash', 'reply', 'escalate' ),
								),
							),
							'limit'           => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100 ),
						),
						$comment_style_properties
					),
					'required'             => array( 'comment_ids' ),
					'additionalProperties' => false,
				),
				'output_schema'    => $success_schema,
				'execute_callback' => array( $callbacks, 'build_comment_moderation_batch_suggest' ),
			),
			'magick-ai/compose-comment-moderation-batch-result' => array(
				'label'            => __( 'Compose Comment Moderation Batch Result', 'magick-ai-abilities' ),
				'description'      => __( 'Composes one canonical batch comment moderation suggestion result envelope.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-comments',
				'capability'       => 'moderate_comments',
				'required_scope'   => 'comments.manage',
				'required_scopes'  => array( 'comments.manage' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'annotations'      => array(
					'instructions' => 'Internal workflow helper only. Keep one canonical batch result envelope for comment moderation suggest runs.',
				),
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'batch_result' => array( 'type' => 'object', 'additionalProperties' => true ),
					),
					'required'             => array( 'batch_result' ),
					'additionalProperties' => false,
				),
				'output_schema'    => $success_schema,
				'execute_callback' => array( $callbacks, 'compose_comment_moderation_batch_result' ),
			),
		);
	}
}
