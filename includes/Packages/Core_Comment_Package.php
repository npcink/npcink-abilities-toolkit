<?php
/**
 * Core WordPress comment helper ability package.
 *
 * @package MagickAIAbilities
 */

namespace Magick_AI_Abilities\Packages;

use Magick_AI_Abilities\Registry\Ability_Registrar;
use Magick_AI_Abilities\Registry\Category_Registrar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers read-only comment workflow helper abilities.
 */
final class Core_Comment_Package {
	/**
	 * Category registrar.
	 *
	 * @var Category_Registrar
	 */
	private $categories;

	/**
	 * Ability registrar.
	 *
	 * @var Ability_Registrar
	 */
	private $abilities;

	/**
	 * Constructor.
	 *
	 * @param Category_Registrar $categories Category registrar.
	 * @param Ability_Registrar  $abilities Ability registrar.
	 */
	public function __construct( Category_Registrar $categories, Ability_Registrar $abilities ) {
		$this->categories = $categories;
		$this->abilities  = $abilities;
	}

	/**
	 * Registers categories and abilities.
	 *
	 * @return void
	 */
	public function boot() {
		$this->categories->add(
			'magick-ai-comments',
			array(
				'label'       => __( 'WordPress Comment Abilities', 'magick-ai-abilities' ),
				'description' => __( 'Read-only comment moderation and reply helper abilities.', 'magick-ai-abilities' ),
			)
		);

		foreach ( $this->definitions() as $ability_id => $definition ) {
			$pack = $this->comment_pack_for( $ability_id );
			if ( ! $this->should_register_comment_ability( $pack, $ability_id, $definition ) ) {
				continue;
			}

			$definition['meta'] = is_array( $definition['meta'] ?? null ) ? $definition['meta'] : array();
			$definition['meta']['magick_ai_abilities'] = is_array( $definition['meta']['magick_ai_abilities'] ?? null )
				? $definition['meta']['magick_ai_abilities']
				: array();
			$definition['meta']['magick_ai_abilities']['pack'] = $pack;
			$definition['project_to_magick_catalog'] = true;
			$this->abilities->add_readonly( $ability_id, $definition );
		}
	}

	/**
	 * Returns whether a comment helper ability should register.
	 *
	 * @param string              $pack Ability sub-pack.
	 * @param string              $ability_id Ability id.
	 * @param array<string,mixed> $definition Ability definition.
	 * @return bool
	 */
	private function should_register_comment_ability( $pack, $ability_id, array $definition ) {
		$defaults = Core_Comment_Pack_Classifier::default_packs();

		/**
		 * Filters enabled comment-helper sub-packs.
		 *
		 * @param string[] $defaults Enabled comment sub-pack slugs.
		 */
		$enabled = apply_filters( 'magick_ai_abilities_enabled_comment_packs', $defaults );
		$enabled = is_array( $enabled ) ? array_map( 'sanitize_key', $enabled ) : $defaults;
		$pack    = sanitize_key( $pack );

		/**
		 * Filters registration for a single built-in comment helper ability.
		 *
		 * @param bool                $register Whether to register the ability.
		 * @param string              $ability_id Ability id.
		 * @param string              $pack Ability sub-pack.
		 * @param array<string,mixed> $definition Ability definition.
		 */
		return (bool) apply_filters(
			'magick_ai_abilities_should_register_comment_ability',
			in_array( $pack, $enabled, true ),
			$ability_id,
			$pack,
			$definition
		);
	}

	/**
	 * Classifies a built-in comment helper ability into a coarse sub-pack.
	 *
	 * @param string $ability_id Ability id.
	 * @return string
	 */
	private function comment_pack_for( $ability_id ) {
		return Core_Comment_Pack_Classifier::classify( $ability_id );
	}

	/**
	 * Returns package ability definitions.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function definitions() {
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
				'execute_callback' => array( $this, 'build_comment_moderation_suggest' ),
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
				'execute_callback' => array( $this, 'compose_comment_moderation_result' ),
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
				'execute_callback' => array( $this, 'build_comment_mention_reply_suggest' ),
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
				'execute_callback' => array( $this, 'read_comment_trigger_queue' ),
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
				'execute_callback' => array( $this, 'get_comment_queue_health' ),
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
				'execute_callback' => array( $this, 'get_comment_action_priority_queue' ),
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
				'execute_callback' => array( $this, 'get_comment_compliance_handoff' ),
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
				'execute_callback' => array( $this, 'compose_comment_mention_reply_result' ),
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
				'execute_callback' => array( $this, 'build_comment_moderation_batch_suggest' ),
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
				'execute_callback' => array( $this, 'compose_comment_moderation_batch_result' ),
			),
		);
	}

	/**
	 * Builds a comment moderation suggestion.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function build_comment_moderation_suggest( $input ) {
		$input      = is_array( $input ) ? $input : array();
		$comment_id = $this->absint_value( $input['comment_id'] ?? 0 );
		if ( $comment_id <= 0 ) {
			return $this->error( 'magick_ai_comment_invalid', 'comment_id is invalid.', 400 );
		}

		$mode = sanitize_key( (string) ( $input['mode'] ?? 'suggest' ) );
		if ( ! in_array( $mode, array( 'suggest', 'action' ), true ) ) {
			return $this->error( 'magick_ai_comment_moderation_mode_invalid', 'mode only supports suggest or action.', 400 );
		}

		$allowed_actions = $this->normalize_comment_moderation_actions( $input['allowed_actions'] ?? array() );
		$action_override = sanitize_key( (string) ( $input['action_override'] ?? '' ) );
		if ( 'action' === $mode ) {
			if ( '' === $action_override ) {
				return $this->error( 'magick_ai_comment_moderation_action_required', 'action_override is required when mode=action.', 400 );
			}
			if ( ! in_array( $action_override, $allowed_actions, true ) ) {
				return $this->error( 'magick_ai_comment_moderation_action_not_allowed', 'action_override is not allowed.', 400 );
			}
			if ( 'reply' === $action_override && '' === trim( (string) ( $input['reply_text'] ?? '' ) ) ) {
				return $this->error( 'magick_ai_comment_moderation_reply_required', 'reply_text is required when action_override=reply.', 400 );
			}
		}

		$comment = $this->get_comment_object( $comment_id );
		if ( ! is_object( $comment ) ) {
			return $this->error( 'magick_ai_comment_missing', 'Comment not found.', 404 );
		}

		$content       = $this->normalize_plain_text( (string) ( $comment->comment_content ?? '' ) );
		$excerpt       = $this->trim_words( $content, 40 );
		$post_id       = $this->absint_value( $comment->comment_post_ID ?? 0 );
		$post_title    = $post_id > 0 && function_exists( 'get_the_title' ) ? sanitize_text_field( (string) get_the_title( $post_id ) ) : '';
		$classification = $this->classify_comment_content( $content );
		$recommended_action = (string) $classification['recommended_action'];
		$risk_flags = (array) $classification['risk_flags'];

		if ( ! in_array( $recommended_action, $allowed_actions, true ) ) {
			$recommended_action = in_array( 'escalate', $allowed_actions, true ) ? 'escalate' : (string) reset( $allowed_actions );
			$risk_flags[]       = 'action_constrained';
		}

		$reply_suggestion = '';
		if ( 'reply' === $recommended_action ) {
			$reply_suggestion = $this->build_reply_suggestion( $content, $risk_flags, $input );
		}

		if ( 'action' === $mode ) {
			$recommended_action = $action_override;
			$reply_suggestion   = 'reply' === $action_override ? $this->sanitize_text_value( (string) ( $input['reply_text'] ?? $reply_suggestion ) ) : $reply_suggestion;
		}

		$result = array(
			'comment_id'           => $comment_id,
			'post'                 => array(
				'post_id' => $post_id,
				'title'   => $post_title,
			),
			'comment'              => array(
				'id'      => $comment_id,
				'author'  => sanitize_text_field( (string) ( $comment->comment_author ?? '' ) ),
				'status'  => sanitize_key( (string) ( $comment->comment_approved ?? '' ) ),
				'excerpt' => $excerpt,
			),
			'mode'                 => $mode,
			'allowed_actions'      => $allowed_actions,
			'recommended_action'   => $recommended_action,
			'confidence'           => (float) $classification['confidence'],
			'risk_flags'           => array_values( array_unique( array_map( 'sanitize_key', $risk_flags ) ) ),
			'reply_suggestion'     => $reply_suggestion,
			'require_human_review' => array_key_exists( 'require_human_review', $input ) ? ! empty( $input['require_human_review'] ) : true,
		);
		$result['moderation_summary'] = $this->build_comment_moderation_summary( $result );

		return $this->success( $result, array( 'source' => 'standalone_comment_moderation_suggest' ), 'Comment moderation suggestion built.' );
	}

	/**
	 * Composes a comment moderation result.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function compose_comment_moderation_result( $input ) {
		$input          = is_array( $input ) ? $input : array();
		$suggest_result = is_array( $input['suggest_result'] ?? null ) ? $input['suggest_result'] : array();
		$mode           = sanitize_key( (string) ( $input['mode'] ?? 'suggest' ) );
		$action         = sanitize_key( (string) ( $input['action_override'] ?? $suggest_result['recommended_action'] ?? '' ) );
		$action_key     = '' !== $action ? 'action_result_' . $action : '';
		$action_result  = '' !== $action_key && is_array( $input[ $action_key ] ?? null ) ? $input[ $action_key ] : array();

		$payload = array(
			'comment_id'           => $this->absint_value( $input['comment_id'] ?? $suggest_result['comment_id'] ?? 0 ),
			'mode'                 => $mode,
			'suggest_result'       => $suggest_result,
			'recommended_action'   => $action,
			'reply_suggestion'     => $this->sanitize_text_value( (string) ( $input['reply_suggestion'] ?? $suggest_result['reply_suggestion'] ?? '' ) ),
			'reply_text'           => $this->sanitize_text_value( (string) ( $input['reply_text'] ?? '' ) ),
			'action_result'        => $action_result,
			'execution_status'     => ! empty( $action_result ) ? 'action_result_recorded' : 'suggest_only',
			'moderation_summary'   => $this->build_comment_moderation_summary( $suggest_result ),
		);

		return $this->success( $payload, array( 'source' => 'standalone_comment_moderation_result' ), 'Comment moderation result composed.' );
	}

	/**
	 * Reads a trigger queue from comments.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function read_comment_trigger_queue( $input ) {
		$input        = is_array( $input ) ? $input : array();
		$trigger_type = $this->normalize_trigger_type( $input['trigger_type'] ?? 'mention' );
		$per_page     = min( 50, max( 1, $this->absint_value( $input['per_page'] ?? 10 ) ) );
		$page         = max( 1, $this->absint_value( $input['page'] ?? 1 ) );
		$status       = sanitize_key( (string) ( $input['status'] ?? 'hold' ) );
		if ( ! in_array( $status, array( 'hold', 'approve', 'all' ), true ) ) {
			$status = 'hold';
		}

		$args = array(
			'number' => $per_page,
			'offset' => ( $page - 1 ) * $per_page,
			'status' => $status,
		);
		$post_id = $this->absint_value( $input['post_id'] ?? 0 );
		if ( $post_id > 0 ) {
			$args['post_id'] = $post_id;
		}

		$comments = function_exists( 'get_comments' ) ? get_comments( $args ) : array();
		$items    = array();
		foreach ( is_array( $comments ) ? $comments : array() as $comment ) {
			if ( ! is_object( $comment ) ) {
				continue;
			}
			$content = $this->normalize_plain_text( (string) ( $comment->comment_content ?? '' ) );
			$trigger = $this->detect_comment_mention_trigger( $content, $trigger_type );
			if ( empty( $trigger['trigger_detected'] ) ) {
				continue;
			}
			$items[] = array(
				'comment_id' => $this->absint_value( $comment->comment_ID ?? 0 ),
				'post_id'    => $this->absint_value( $comment->comment_post_ID ?? 0 ),
				'author'     => sanitize_text_field( (string) ( $comment->comment_author ?? '' ) ),
				'excerpt'    => $this->trim_words( $content, 28 ),
				'trigger'    => $trigger,
			);
		}

		return $this->success(
			array(
				'items'   => $items,
				'summary' => array(
					'trigger_type'    => $trigger_type,
					'candidate_count' => count( $items ),
					'next_action'     => ! empty( $items ) ? 'review_trigger_candidates' : 'no_trigger_candidates',
				),
			),
			array( 'source' => 'standalone_comment_trigger_queue' ),
			'Comment trigger queue built.'
		);
	}

	/**
	 * Builds a comment queue health summary.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function get_comment_queue_health( $input ) {
		$input = is_array( $input ) ? $input : array();
		$per_page = min( 100, max( 1, $this->absint_value( $input['per_page'] ?? 50 ) ) );
		$page = max( 1, $this->absint_value( $input['page'] ?? 1 ) );
		$status = sanitize_key( (string) ( $input['status'] ?? 'hold' ) );
		if ( ! in_array( $status, array( 'hold', 'approve', 'spam', 'trash', 'all' ), true ) ) {
			$status = 'hold';
		}

		$args = array(
			'number' => $per_page,
			'offset' => ( $page - 1 ) * $per_page,
			'status' => $status,
		);
		$post_id = $this->absint_value( $input['post_id'] ?? 0 );
		if ( $post_id > 0 ) {
			$args['post_id'] = $post_id;
		}

		$comments = function_exists( 'get_comments' ) ? get_comments( $args ) : array();
		$comments = is_array( $comments ) ? $comments : array();
		$items = array();
		$counts = array(
			'total'       => 0,
			'spam_risk'   => 0,
			'reply_needed' => 0,
			'escalate'    => 0,
			'low_value'   => 0,
		);
		$action_counts = array(
			'approve'  => 0,
			'spam'     => 0,
			'trash'    => 0,
			'reply'    => 0,
			'escalate' => 0,
		);

		foreach ( $comments as $comment ) {
			if ( ! is_object( $comment ) ) {
				continue;
			}
			$content = $this->normalize_plain_text( (string) ( $comment->comment_content ?? '' ) );
			$classification = $this->classify_comment_content( $content );
			$action = sanitize_key( (string) ( $classification['recommended_action'] ?? 'approve' ) );
			$risk_flags = array_values( array_unique( array_map( 'sanitize_key', (array) ( $classification['risk_flags'] ?? array() ) ) ) );
			if ( isset( $action_counts[ $action ] ) ) {
				++$action_counts[ $action ];
			}
			++$counts['total'];
			if ( in_array( 'commercial_promo', $risk_flags, true ) || 'spam' === $action ) {
				++$counts['spam_risk'];
			}
			if ( in_array( 'support_request', $risk_flags, true ) || in_array( 'simple_question', $risk_flags, true ) || 'reply' === $action ) {
				++$counts['reply_needed'];
			}
			if ( in_array( 'abusive_or_hostile', $risk_flags, true ) || 'escalate' === $action ) {
				++$counts['escalate'];
			}
			if ( in_array( 'low_value_noise', $risk_flags, true ) ) {
				++$counts['low_value'];
			}

			$items[] = array(
				'comment_id'         => $this->absint_value( $comment->comment_ID ?? 0 ),
				'post_id'            => $this->absint_value( $comment->comment_post_ID ?? 0 ),
				'author'             => sanitize_text_field( (string) ( $comment->comment_author ?? '' ) ),
				'status'             => sanitize_key( (string) ( $comment->comment_approved ?? '' ) ),
				'excerpt'            => $this->trim_words( $content, 28 ),
				'recommended_action' => $action,
				'confidence'         => (float) ( $classification['confidence'] ?? 0 ),
				'risk_flags'         => $risk_flags,
			);
		}

		$attention_count = (int) $counts['spam_risk'] + (int) $counts['reply_needed'] + (int) $counts['escalate'];
		$health_score = count( $items ) > 0
			? max( 0, 100 - min( 100, (int) round( ( $attention_count / max( 1, count( $items ) ) ) * 22 ) ) )
			: 100;

		return $this->success(
			array(
				'total'        => count( $items ),
				'page'         => $page,
				'per_page'     => $per_page,
				'health_score' => $health_score,
				'items'        => $items,
				'summary'      => array(
					'status'          => $status,
					'post_id'         => $post_id,
					'counts'          => $counts,
					'action_counts'   => $action_counts,
					'attention_count' => $attention_count,
					'next_action'     => $attention_count > 0 ? 'review_attention_items' : 'queue_clear',
				),
			),
			array(
				'source'         => 'local_comment_queue_health',
				'execution_mode' => 'deterministic',
			),
			'Comment queue health report built.'
		);
	}

	/**
	 * Builds a prioritized comment action queue.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function get_comment_action_priority_queue( $input ) {
		$input = is_array( $input ) ? $input : array();
		$per_page = min( 100, max( 1, $this->absint_value( $input['per_page'] ?? 50 ) ) );
		$page = max( 1, $this->absint_value( $input['page'] ?? 1 ) );
		$status = sanitize_key( (string) ( $input['status'] ?? 'hold' ) );
		if ( ! in_array( $status, array( 'hold', 'approve', 'spam', 'trash', 'all' ), true ) ) {
			$status = 'hold';
		}

		$args = array(
			'number' => $per_page,
			'offset' => ( $page - 1 ) * $per_page,
			'status' => $status,
		);
		$post_id = $this->absint_value( $input['post_id'] ?? 0 );
		if ( $post_id > 0 ) {
			$args['post_id'] = $post_id;
		}

		$comments = function_exists( 'get_comments' ) ? get_comments( $args ) : array();
		$items = array();
		$counts = array(
			'total'    => 0,
			'high'     => 0,
			'medium'   => 0,
			'low'      => 0,
			'reply'    => 0,
			'spam'     => 0,
			'escalate' => 0,
		);
		foreach ( is_array( $comments ) ? $comments : array() as $comment ) {
			if ( ! is_object( $comment ) ) {
				continue;
			}
			$content = $this->normalize_plain_text( (string) ( $comment->comment_content ?? '' ) );
			$classification = $this->classify_comment_content( $content );
			$action = sanitize_key( (string) ( $classification['recommended_action'] ?? 'approve' ) );
			$confidence = (float) ( $classification['confidence'] ?? 0 );
			$risk_flags = array_values( array_unique( array_map( 'sanitize_key', (array) ( $classification['risk_flags'] ?? array() ) ) ) );
			$priority_score = (int) round( $confidence * 50 );
			if ( in_array( $action, array( 'spam', 'trash', 'escalate' ), true ) ) {
				$priority_score += 35;
			} elseif ( 'reply' === $action ) {
				$priority_score += 25;
			}
			if ( in_array( 'support_request', $risk_flags, true ) || in_array( 'abusive_or_hostile', $risk_flags, true ) ) {
				$priority_score += 10;
			}
			$priority = $priority_score >= 75 ? 'high' : ( $priority_score >= 50 ? 'medium' : 'low' );
			++$counts['total'];
			++$counts[ $priority ];
			if ( isset( $counts[ $action ] ) ) {
				++$counts[ $action ];
			}
			$items[] = array(
				'comment_id'         => $this->absint_value( $comment->comment_ID ?? 0 ),
				'post_id'            => $this->absint_value( $comment->comment_post_ID ?? 0 ),
				'author'             => sanitize_text_field( (string) ( $comment->comment_author ?? '' ) ),
				'status'             => sanitize_key( (string) ( $comment->comment_approved ?? '' ) ),
				'excerpt'            => $this->trim_words( $content, 28 ),
				'recommended_action' => $action,
				'priority'           => $priority,
				'priority_score'     => $priority_score,
				'risk_flags'         => $risk_flags,
				'next_action'        => in_array( $action, array( 'approve', 'spam', 'trash', 'reply' ), true ) ? 'handoff_to_host_governed_action' : 'manual_review',
			);
		}

		usort(
			$items,
			static function ( array $a, array $b ) {
				return (int) ( $b['priority_score'] ?? 0 ) <=> (int) ( $a['priority_score'] ?? 0 );
			}
		);

		return $this->success(
			array(
				'total'   => count( $items ),
				'page'    => $page,
				'per_page' => $per_page,
				'items'   => $items,
				'summary' => array(
					'status'      => $status,
					'post_id'     => $post_id,
					'counts'      => $counts,
					'next_action' => count( $items ) > 0 ? 'review_priority_queue' : 'queue_clear',
				),
			),
			array(
				'source'         => 'local_comment_action_priority_queue',
				'execution_mode' => 'deterministic',
			),
			'Comment action priority queue built.'
		);
	}

	/**
	 * Builds one host-side comment compliance handoff bundle.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function get_comment_compliance_handoff( $input ) {
		$input = is_array( $input ) ? $input : array();
		$per_page = min( 100, max( 1, $this->absint_value( $input['per_page'] ?? 50 ) ) );
		$page = max( 1, $this->absint_value( $input['page'] ?? 1 ) );
		$status = sanitize_key( (string) ( $input['status'] ?? 'hold' ) );
		if ( ! in_array( $status, array( 'hold', 'approve', 'spam', 'trash', 'all' ), true ) ) {
			$status = 'hold';
		}
		$post_id = $this->absint_value( $input['post_id'] ?? 0 );
		$selected_comment_id = $this->absint_value( $input['selected_comment_id'] ?? 0 );
		$base_input = array(
			'post_id'  => $post_id,
			'status'   => $status,
			'per_page' => $per_page,
			'page'     => $page,
		);

		$queue_health = $this->get_comment_queue_health( $base_input );
		$priority_queue = $this->get_comment_action_priority_queue( $base_input );
		$sections = array( 'queue_health', 'priority_queue' );
		$selected_suggestion = array();
		$mention_reply = array();

		if ( $selected_comment_id > 0 ) {
			$selected_input = array_merge( $input, array( 'comment_id' => $selected_comment_id ) );
			$moderation = $this->build_comment_moderation_suggest( $selected_input );
			if ( is_array( $moderation ) ) {
				$selected_suggestion = is_array( $moderation['data'] ?? null ) ? $moderation['data'] : array();
				$sections[] = 'selected_moderation_suggestion';
			}
			$reply = $this->build_comment_mention_reply_suggest( $selected_input );
			if ( is_array( $reply ) ) {
				$mention_reply = is_array( $reply['data'] ?? null ) ? $reply['data'] : array();
				$sections[] = 'selected_reply_suggestion';
			}
		}

		$health_data = is_array( $queue_health['data'] ?? null ) ? $queue_health['data'] : array();
		$priority_data = is_array( $priority_queue['data'] ?? null ) ? $priority_queue['data'] : array();

		return $this->success(
			array(
				'recipe'               => 'workflow/wordpress_comment_compliance_handoff',
				'queue_health'         => $health_data,
				'priority_queue'       => $priority_data,
				'selected_suggestion'  => $selected_suggestion,
				'selected_reply'       => $mention_reply,
				'sections'             => $sections,
				'summary'              => array(
					'status'              => $status,
					'post_id'             => $post_id,
					'selected_comment_id' => $selected_comment_id,
					'attention_count'     => (int) ( $health_data['summary']['attention_count'] ?? 0 ),
					'priority_count'      => (int) ( $priority_data['summary']['counts']['total'] ?? 0 ),
					'section_count'       => count( $sections ),
					'next_action'         => (int) ( $health_data['summary']['attention_count'] ?? 0 ) > 0 ? 'review_comment_handoff' : 'queue_clear',
				),
			),
			array(
				'source'         => 'local_comment_compliance_handoff',
				'execution_mode' => 'deterministic',
			),
			'Comment compliance handoff built.'
		);
	}

	/**
	 * Builds a mention reply suggestion handoff.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function build_comment_mention_reply_suggest( $input ) {
		$input      = is_array( $input ) ? $input : array();
		$comment_id = $this->absint_value( $input['comment_id'] ?? 0 );
		$comment    = $this->get_comment_object( $comment_id );
		if ( $comment_id <= 0 || ! is_object( $comment ) ) {
			return $this->error( 'magick_ai_comment_missing', 'Comment not found.', 404 );
		}

		$content      = $this->normalize_plain_text( (string) ( $comment->comment_content ?? '' ) );
		$trigger_type = $this->normalize_trigger_type( $input['trigger_type'] ?? 'mention' );
		$trigger      = $this->detect_comment_mention_trigger( $content, $trigger_type );
		$reply        = ! empty( $trigger['trigger_detected'] )
			? $this->build_reply_suggestion( $content, array( $trigger_type ), $input )
			: '';

		$payload = array(
			'comment_id'       => $comment_id,
			'post_id'          => $this->absint_value( $comment->comment_post_ID ?? 0 ),
			'trigger_type'     => $trigger_type,
			'trigger'          => $trigger,
			'reply_suggestion' => $reply,
			'next_action'      => ! empty( $trigger['trigger_detected'] ) ? 'preview_reply' : 'manual_review',
		);
		$payload['mention_summary'] = $this->build_comment_mention_summary( $payload );

		return $this->success( $payload, array( 'source' => 'standalone_comment_mention_reply_suggest' ), 'Comment mention reply suggestion built.' );
	}

	/**
	 * Composes a mention reply result.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function compose_comment_mention_reply_result( $input ) {
		$input          = is_array( $input ) ? $input : array();
		$suggest_result = is_array( $input['suggest_result'] ?? null ) ? $input['suggest_result'] : array();
		$payload        = array(
			'comment_id'       => $this->absint_value( $input['comment_id'] ?? $suggest_result['comment_id'] ?? 0 ),
			'suggest_result'   => $suggest_result,
			'reply_suggestion' => $this->sanitize_text_value( (string) ( $input['reply_suggestion'] ?? $suggest_result['reply_suggestion'] ?? '' ) ),
			'next_action'      => ! empty( $suggest_result['trigger']['trigger_detected'] ) ? 'preview_reply' : 'manual_review',
		);
		$payload['mention_summary'] = $this->build_comment_mention_summary( $suggest_result );

		return $this->success( $payload, array( 'source' => 'standalone_comment_mention_reply_result' ), 'Comment mention reply result composed.' );
	}

	/**
	 * Builds batch moderation suggestions.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function build_comment_moderation_batch_suggest( $input ) {
		$input = is_array( $input ) ? $input : array();
		$mode  = sanitize_key( (string) ( $input['mode'] ?? 'suggest' ) );
		if ( 'suggest' !== $mode ) {
			return $this->error( 'magick_ai_comment_batch_mode_invalid', 'comment_moderation_batch_suggest only supports mode=suggest.', 400 );
		}

		$ids = is_array( $input['comment_ids'] ?? null ) ? $input['comment_ids'] : array();
		$ids = array_values( array_unique( array_filter( array_map( array( $this, 'absint_value' ), $ids ) ) ) );
		$limit = min( 100, max( 1, $this->absint_value( $input['limit'] ?? count( $ids ) ) ) );
		$ids = array_slice( $ids, 0, $limit );

		$items = array();
		foreach ( $ids as $comment_id ) {
			$item = $this->build_comment_moderation_suggest(
				array(
					'comment_id'           => $comment_id,
					'mode'                 => 'suggest',
					'allowed_actions'      => $input['allowed_actions'] ?? array(),
					'site_voice'           => $input['site_voice'] ?? '',
					'reply_tone'           => $input['reply_tone'] ?? '',
					'persona_profile'      => $input['persona_profile'] ?? '',
					'reply_length_policy'  => $input['reply_length_policy'] ?? '',
					'require_human_review' => $input['require_human_review'] ?? true,
				)
			);
			if ( $this->is_error( $item ) ) {
				$items[] = $this->build_comment_moderation_batch_item_from_error( $comment_id, 'read_failed', $this->error_message( $item ) );
				continue;
			}
			$data = is_array( $item['data'] ?? null ) ? $item['data'] : array();
			$data['queue_priority'] = $this->build_comment_moderation_batch_priority( $data );
			$items[] = $data;
		}

		$payload = array(
			'items'         => $items,
			'batch_summary' => $this->build_comment_moderation_batch_summary( $items ),
		);

		return $this->success( $payload, array( 'source' => 'standalone_comment_moderation_batch_suggest' ), 'Comment moderation batch suggestion built.' );
	}

	/**
	 * Composes a batch moderation result.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function compose_comment_moderation_batch_result( $input ) {
		$input        = is_array( $input ) ? $input : array();
		$batch_result = is_array( $input['batch_result'] ?? null ) ? $input['batch_result'] : array();
		$items        = is_array( $batch_result['items'] ?? null ) ? $batch_result['items'] : array();
		$payload      = array(
			'items'         => $items,
			'batch_summary' => is_array( $batch_result['batch_summary'] ?? null ) ? $batch_result['batch_summary'] : $this->build_comment_moderation_batch_summary( $items ),
			'next_action'   => 'review_individual_items',
		);

		return $this->success( $payload, array( 'source' => 'standalone_comment_moderation_batch_result' ), 'Comment moderation batch result composed.' );
	}

	/**
	 * Normalizes allowed actions.
	 *
	 * @param mixed $actions Raw actions.
	 * @return array<int,string>
	 */
	private function normalize_comment_moderation_actions( $actions ) {
		$allowed = array( 'approve', 'spam', 'trash', 'reply', 'escalate' );
		$actions = is_array( $actions ) ? $actions : array();
		$actions = array_values(
			array_unique(
				array_filter(
					array_map(
						static function ( $action ) {
							return sanitize_key( (string) $action );
						},
						$actions
					)
				)
			)
		);
		$actions = array_values(
			array_filter(
				$actions,
				static function ( $action ) use ( $allowed ) {
					return in_array( $action, $allowed, true );
				}
			)
		);

		return empty( $actions ) ? $allowed : $actions;
	}

	/**
	 * Classifies comment content with deterministic local rules.
	 *
	 * @param string $content Comment content.
	 * @return array<string,mixed>
	 */
	private function classify_comment_content( $content ) {
		$lower = function_exists( 'mb_strtolower' ) ? mb_strtolower( $content, 'UTF-8' ) : strtolower( $content );
		$char_count = function_exists( 'mb_strlen' ) ? mb_strlen( $content, 'UTF-8' ) : strlen( $content );
		$word_count = str_word_count( $this->strip_tags( $content ) );
		$url_count  = preg_match_all( '/(?:https?:\/\/|www\.)/i', $content, $matches );
		$url_count  = is_int( $url_count ) ? $url_count : 0;

		$promo_hit = 1 === preg_match( '/\b(?:buy now|cheap pills|online pharmacy|discount|coupon|promo|promotion|guest post|sponsored post|seo service|marketing service|casino|viagra|pills|loan|telegram|whatsapp)\b/i', $content )
			|| 1 === preg_match( '/免费流量|代发|外链|推广|优惠|引流|加微|vx|电报/u', $content );
		$abuse_hit = 1 === preg_match( '/\b(?:idiot|stupid|moron|dumb|trash site|garbage site|shut up|hate you)\b/i', $content )
			|| 1 === preg_match( '/垃圾网站|白痴|傻[逼比B]|滚蛋|去死|闭嘴|废物/u', $content );
		$support_hit = 1 === preg_match( '/\b(?:help|support|issue|problem|not working|cannot|failed|error|bug|refund|invoice|billing|login|password|account)\b/i', $content )
			|| 1 === preg_match( '/求助|无法|不能|报错|错误|故障|退款|账单|登录|密码|账号|怎么处理/u', $content );
		$question_hit = false !== strpos( $content, '?' ) || false !== strpos( $content, '？' )
			|| 1 === preg_match( '/\b(?:how|what|why|when|where|can you|could you|is there|do you)\b/i', $content )
			|| 1 === preg_match( '/请问|能否|怎么|为何|是否/u', $content );
		$low_value_hit = $char_count > 0 && $char_count <= 24 && (
			1 === preg_match( '/\b(?:nice|great|cool|good|thanks|thank you|awesome|first)\b/i', $lower )
			|| 1 === preg_match( '/谢谢|不错|很好|支持|路过|打卡/u', $content )
		);

		$result = array(
			'recommended_action' => 'approve',
			'confidence'         => 0.68,
			'risk_flags'         => array(),
		);
		if ( '' === trim( $content ) ) {
			$result['recommended_action'] = 'trash';
			$result['confidence']         = 0.97;
			$result['risk_flags'][]       = 'empty_comment';
		} elseif ( $promo_hit || $url_count >= 2 ) {
			$result['recommended_action'] = 'spam';
			$result['confidence']         = 0.94;
			$result['risk_flags'][]       = 'commercial_promo';
			if ( $url_count >= 2 ) {
				$result['risk_flags'][] = 'excessive_links';
			}
		} elseif ( $abuse_hit ) {
			$result['recommended_action'] = 'escalate';
			$result['confidence']         = 0.89;
			$result['risk_flags'][]       = 'abusive_or_hostile';
		} elseif ( $support_hit ) {
			$result['recommended_action'] = 'reply';
			$result['confidence']         = 0.84;
			$result['risk_flags'][]       = 'support_request';
		} elseif ( $question_hit && $word_count <= 18 ) {
			$result['recommended_action'] = 'reply';
			$result['confidence']         = 0.8;
			$result['risk_flags'][]       = 'simple_question';
		} elseif ( $low_value_hit ) {
			$result['risk_flags'][] = 'low_value_noise';
		}

		return $result;
	}

	/**
	 * Builds a short reply suggestion.
	 *
	 * @param string $content Comment content.
	 * @param array  $risk_flags Risk flags.
	 * @param array  $input Input args.
	 * @return string
	 */
	private function build_reply_suggestion( $content, array $risk_flags, array $input ) {
		$site_voice = $this->sanitize_text_value( (string) ( $input['site_voice'] ?? '' ) );
		$prefix     = '' !== $site_voice ? $site_voice . ' ' : '';
		if ( in_array( 'support_request', $risk_flags, true ) ) {
			return $prefix . 'Thanks for the details. We will check this and follow up with the next step.';
		}
		if ( in_array( 'simple_question', $risk_flags, true ) ) {
			return $prefix . 'Thanks for asking. We will answer this as directly as possible.';
		}
		return $prefix . 'Thanks for the comment. We will review it and reply if more detail is needed.';
	}

	/**
	 * Detects comment trigger type.
	 *
	 * @param string $content Comment content.
	 * @param string $trigger_type Trigger type.
	 * @return array<string,mixed>
	 */
	private function detect_comment_mention_trigger( $content, $trigger_type ) {
		$trigger_type = $this->normalize_trigger_type( $trigger_type );
		$detected = false;
		$reason = 'no_trigger_detected';
		if ( 'mention' === $trigger_type ) {
			$detected = 1 === preg_match( '/@[\p{L}\p{N}_-]+/u', $content ) || 1 === preg_match( '/管理员|作者|博主|小编/u', $content );
			$reason   = $detected ? 'mention_detected' : $reason;
		} elseif ( 'followup' === $trigger_type ) {
			$detected = 1 === preg_match( '/\b(?:follow up|again|still|any update)\b/i', $content ) || 1 === preg_match( '/继续|还有|补充|更新了吗/u', $content );
			$reason   = $detected ? 'followup_detected' : $reason;
		} else {
			$classification = $this->classify_comment_content( $content );
			$detected = in_array( 'support_request', (array) $classification['risk_flags'], true );
			$reason   = $detected ? 'support_request_detected' : $reason;
		}

		return array(
			'trigger_type'      => $trigger_type,
			'trigger_detected'  => $detected,
			'confidence'        => $detected ? 0.82 : 0.35,
			'primary_reason'    => $reason,
		);
	}

	/**
	 * Builds moderation summary.
	 *
	 * @param array $result Result payload.
	 * @return array<string,mixed>
	 */
	private function build_comment_moderation_summary( array $result ) {
		$confidence = (float) ( $result['confidence'] ?? 0 );
		$risk_flags = is_array( $result['risk_flags'] ?? null ) ? $result['risk_flags'] : array();
		return array(
			'recommended_action' => sanitize_key( (string) ( $result['recommended_action'] ?? '' ) ),
			'confidence_band'    => $confidence >= 0.85 ? 'high' : ( $confidence >= 0.65 ? 'medium' : 'low' ),
			'primary_reason'     => ! empty( $risk_flags ) ? sanitize_key( (string) reset( $risk_flags ) ) : 'no_major_risk',
			'risk_flags'         => array_values( array_map( 'sanitize_key', $risk_flags ) ),
		);
	}

	/**
	 * Builds mention summary.
	 *
	 * @param array $result Result payload.
	 * @return array<string,mixed>
	 */
	private function build_comment_mention_summary( array $result ) {
		$trigger = is_array( $result['trigger'] ?? null ) ? $result['trigger'] : array();
		return array(
			'trigger_detected' => ! empty( $trigger['trigger_detected'] ),
			'trigger_type'     => sanitize_key( (string) ( $trigger['trigger_type'] ?? $result['trigger_type'] ?? '' ) ),
			'next_action'      => ! empty( $trigger['trigger_detected'] ) ? 'preview_reply' : 'manual_review',
		);
	}

	/**
	 * Builds a batch item from an error.
	 *
	 * @param int    $comment_id Comment id.
	 * @param string $reason Reason code.
	 * @param string $message Message.
	 * @return array<string,mixed>
	 */
	private function build_comment_moderation_batch_item_from_error( $comment_id, $reason, $message ) {
		return array(
			'comment_id'         => $this->absint_value( $comment_id ),
			'status'             => 'skipped',
			'error_reason'       => sanitize_key( (string) $reason ),
			'message'            => $this->sanitize_text_value( $message ),
			'queue_priority'     => 'low',
			'moderation_summary' => array(
				'recommended_action' => 'escalate',
				'confidence_band'    => 'low',
				'primary_reason'     => sanitize_key( (string) $reason ),
				'risk_flags'         => array( sanitize_key( (string) $reason ) ),
			),
		);
	}

	/**
	 * Builds batch queue priority.
	 *
	 * @param array $item Item payload.
	 * @return string
	 */
	private function build_comment_moderation_batch_priority( array $item ) {
		$action = sanitize_key( (string) ( $item['recommended_action'] ?? '' ) );
		$confidence = (float) ( $item['confidence'] ?? 0 );
		if ( in_array( $action, array( 'spam', 'trash', 'escalate' ), true ) || $confidence >= 0.85 ) {
			return 'high';
		}
		if ( 'reply' === $action || $confidence >= 0.65 ) {
			return 'medium';
		}
		return 'low';
	}

	/**
	 * Builds batch summary.
	 *
	 * @param array $items Items.
	 * @return array<string,mixed>
	 */
	private function build_comment_moderation_batch_summary( array $items ) {
		$counts = array(
			'total'    => count( $items ),
			'approve'  => 0,
			'spam'     => 0,
			'trash'    => 0,
			'reply'    => 0,
			'escalate' => 0,
			'skipped'  => 0,
		);
		foreach ( $items as $item ) {
			$action = sanitize_key( (string) ( $item['recommended_action'] ?? ( ( 'skipped' === ( $item['status'] ?? '' ) ) ? 'skipped' : '' ) ) );
			if ( isset( $counts[ $action ] ) ) {
				++$counts[ $action ];
			}
		}

		return array(
			'counts'         => $counts,
			'next_action'    => 'review_individual_items',
			'primary_reason' => 'Batch suggest is read-only. Review individual items, then return to single-item comment_moderation(action) for any write.',
		);
	}

	/**
	 * Normalizes trigger type.
	 *
	 * @param mixed $trigger_type Trigger type.
	 * @return string
	 */
	private function normalize_trigger_type( $trigger_type ) {
		$trigger_type = sanitize_key( (string) $trigger_type );
		return in_array( $trigger_type, array( 'mention', 'followup', 'support_request' ), true ) ? $trigger_type : 'mention';
	}

	/**
	 * Gets a comment object.
	 *
	 * @param int $comment_id Comment id.
	 * @return object|null
	 */
	private function get_comment_object( $comment_id ) {
		if ( ! function_exists( 'get_comment' ) ) {
			return null;
		}
		$comment = get_comment( $this->absint_value( $comment_id ) );
		return is_object( $comment ) ? $comment : null;
	}

	/**
	 * Builds a success envelope.
	 *
	 * @param array  $data Data payload.
	 * @param array  $meta Meta payload.
	 * @param string $message Message.
	 * @return array<string,mixed>
	 */
	private function success( array $data, array $meta, $message ) {
		return array(
			'success' => true,
			'data'    => $data,
			'meta'    => $meta,
			'message' => $message,
		);
	}

	/**
	 * Builds an error.
	 *
	 * @param string $code Error code.
	 * @param string $message Error message.
	 * @param int    $status HTTP status.
	 * @return \WP_Error
	 */
	private function error( $code, $message, $status ) {
		return new \WP_Error( $code, $message, array( 'status' => $status ) );
	}

	/**
	 * Returns whether value is WP_Error-like.
	 *
	 * @param mixed $value Value.
	 * @return bool
	 */
	private function is_error( $value ) {
		return function_exists( 'is_wp_error' ) ? is_wp_error( $value ) : $value instanceof \WP_Error;
	}

	/**
	 * Reads an error message.
	 *
	 * @param mixed $error Error value.
	 * @return string
	 */
	private function error_message( $error ) {
		if ( is_object( $error ) && method_exists( $error, 'get_error_message' ) ) {
			return (string) $error->get_error_message();
		}
		return is_object( $error ) && isset( $error->message ) ? (string) $error->message : 'Unknown error.';
	}

	/**
	 * Normalizes plain text.
	 *
	 * @param string $value Text.
	 * @return string
	 */
	private function normalize_plain_text( $value ) {
		$value = $this->strip_tags( $value );
		$value = preg_replace( '/\s+/u', ' ', (string) $value );
		return trim( (string) $value );
	}

	/**
	 * Strips tags.
	 *
	 * @param string $value Text.
	 * @return string
	 */
	private function strip_tags( $value ) {
		return function_exists( 'wp_strip_all_tags' ) ? wp_strip_all_tags( (string) $value ) : strip_tags( (string) $value );
	}

	/**
	 * Trims words.
	 *
	 * @param string $value Text.
	 * @param int    $words Word count.
	 * @return string
	 */
	private function trim_words( $value, $words ) {
		return function_exists( 'wp_trim_words' )
			? sanitize_text_field( wp_trim_words( $value, $words ) )
			: sanitize_text_field( implode( ' ', array_slice( preg_split( '/\s+/', trim( $value ) ), 0, $words ) ) );
	}

	/**
	 * Sanitizes a text value.
	 *
	 * @param string $value Raw text.
	 * @return string
	 */
	private function sanitize_text_value( $value ) {
		return sanitize_text_field( (string) $value );
	}

	/**
	 * Absint fallback.
	 *
	 * @param mixed $value Value.
	 * @return int
	 */
	private function absint_value( $value ) {
		return function_exists( 'absint' ) ? absint( $value ) : max( 0, (int) $value );
	}
}
