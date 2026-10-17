<?php
/**
 * Internal-link read ability definition.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Packages\Read_Definitions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Provides the existing internal-link resolver contract. */
final class Internal_Link_Read_Definitions {
	/**
	 * Returns the resolver definition with its existing callback owner.
	 *
	 * @param object $callbacks Callback owner.
	 * @return array<string,array<string,mixed>>
	 */
	public static function definitions( $callbacks ) {
		return array(
			'npcink-abilities-toolkit/resolve-internal-link-targets' => array(
				'label'            => __( 'Resolve Internal Link Targets', 'npcink-abilities-toolkit' ),
				'description'      => __( 'Finds local published content that can serve as internal-link targets for a post draft or optimization workflow.', 'npcink-abilities-toolkit' ),
				'category'         => 'npcink-abilities-toolkit-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'post.read',
				'required_scopes'  => array( 'post.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'current_post_id' => array( 'type' => 'integer', 'minimum' => 1 ),
						'post_type'       => array( 'type' => 'string' ),
						'query'           => array( 'type' => 'string' ),
						'title'           => array( 'type' => 'string' ),
						'content'         => array( 'type' => 'string' ),
						'content_text'    => array( 'type' => 'string' ),
						'excerpt'         => array( 'type' => 'string' ),
						'selected_text'   => array( 'type' => 'string' ),
						'selected_block_text' => array( 'type' => 'string' ),
						'user_instruction' => array( 'type' => 'string' ),
						'focus_keyword'   => array( 'type' => 'string' ),
						'keywords'        => array(
							'type'  => array( 'array', 'null' ),
							'items' => array( 'type' => 'string' ),
						),
						'related_content_evidence' => array(
							'type'  => array( 'array', 'null' ),
							'items' => array(
								'type'                 => 'object',
								'properties'           => array(
									'post_id'      => array( 'type' => 'integer', 'minimum' => 1 ),
									'id'           => array( 'type' => array( 'integer', 'string', 'null' ) ),
									'title'        => array( 'type' => 'string' ),
									'anchor_or_context' => array( 'type' => 'string', 'maxLength' => 80 ),
									'candidate_relevance' => array( 'type' => 'string', 'enum' => array( '', 'strong', 'review', 'weak' ) ),
									'name'         => array( 'type' => 'string' ),
									'url'          => array( 'type' => 'string' ),
									'permalink'    => array( 'type' => 'string' ),
									'link'         => array( 'type' => 'string' ),
									'source_url'   => array( 'type' => 'string' ),
									'excerpt'      => array( 'type' => 'string' ),
									'snippet'      => array( 'type' => 'string' ),
									'reason'       => array( 'type' => 'string' ),
									'score'        => array( 'type' => array( 'number', 'integer', 'null' ) ),
									'evidence_ref' => array( 'type' => 'string' ),
									'incoming_count' => array( 'type' => 'integer', 'minimum' => 0 ),
									'link_graph_issues' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
									'shared_terms' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
									'graph_evidence_available' => array( 'type' => 'boolean' ),
								),
								'additionalProperties' => false,
							),
						),
						'content_blocks' => array(
							'type'  => array( 'array', 'null' ),
							'maxItems' => 80,
							'items' => array(
								'type'                 => 'object',
								'properties'           => array(
									'client_id'  => array( 'type' => 'string' ),
									'block_name' => array( 'type' => 'string' ),
									'text'       => array( 'type' => 'string' ),
								),
								'additionalProperties' => false,
							),
						),
						'candidate_source' => array( 'type' => 'string', 'maxLength' => 64 ),
						'candidate_limit' => array( 'type' => array( 'integer', 'null' ), 'minimum' => 1, 'maximum' => 8, 'default' => 8 ),
						'max_targets'     => array( 'type' => array( 'integer', 'null' ), 'minimum' => 1, 'maximum' => 6, 'default' => 3 ),
					),
					'required'             => array( 'title' ),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array(
							'type'                 => 'object',
							'properties'           => array(
								'targets'        => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
								'placement_plan' => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
								'no_link_zones'  => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
								'internal_link_candidates' => array( 'type' => 'object', 'additionalProperties' => true ),
								'summary'        => array( 'type' => 'object', 'additionalProperties' => true ),
							),
							'additionalProperties' => false,
						),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $callbacks, 'resolve_internal_link_targets' ),
			),
		);
	}
}
