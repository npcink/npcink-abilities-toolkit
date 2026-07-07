<?php
/**
 * Audits the registered first-party ability surface for agent-ready contracts.
 *
 * @package NpcinkAbilitiesToolkit
 */

require_once dirname( __DIR__ ) . '/tests/bootstrap.php';

$failures = array();

/**
 * Records a failed contract assertion.
 *
 * @param string $message Failure message.
 * @return void
 */
function npcink_abilities_toolkit_contract_audit_fail( $message ) {
	global $failures;
	$failures[] = (string) $message;
}

/**
 * Returns nested array data or null.
 *
 * @param array<int|string,mixed> $value Source array.
 * @param array<int,string>       $path  Nested keys.
 * @return mixed
 */
function npcink_abilities_toolkit_contract_audit_get( array $value, array $path ) {
	foreach ( $path as $key ) {
		if ( ! is_array( $value ) || ! array_key_exists( $key, $value ) ) {
			return null;
		}
		$value = $value[ $key ];
	}

	return $value;
}

/**
 * Checks a normalized schema is an object schema.
 *
 * @param string              $ability_id Ability id.
 * @param array<string,mixed> $ability    Ability definition.
 * @param string              $schema_key Schema field.
 * @return void
 */
function npcink_abilities_toolkit_contract_audit_schema( $ability_id, array $ability, $schema_key ) {
	$schema = isset( $ability[ $schema_key ] ) && is_array( $ability[ $schema_key ] )
		? $ability[ $schema_key ]
		: array();

	if ( 'object' !== (string) ( $schema['type'] ?? '' ) ) {
		npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} {$schema_key} must be an object schema" );
	}

	if ( isset( $schema['properties'] ) && ! is_array( $schema['properties'] ) ) {
		npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} {$schema_key}.properties must be an object map when present" );
	}
}

/**
 * Returns the intentional input schema extension points.
 *
 * @return array<string,bool>
 */
function npcink_abilities_toolkit_contract_audit_additional_properties_allowlist() {
	$paths = array(
		'npcink-abilities-toolkit/adopt-cloud-media-derivative input_schema.properties.derivative_artifact',
		'npcink-abilities-toolkit/build-article-block-plan input_schema.properties.variables',
		'npcink-abilities-toolkit/build-article-optimization-apply-plan input_schema.properties.generated_excerpt',
		'npcink-abilities-toolkit/build-article-optimization-apply-plan input_schema.properties.optimization_plan',
		'npcink-abilities-toolkit/build-article-optimization-apply-plan input_schema.properties.post',
		'npcink-abilities-toolkit/build-article-optimization-apply-plan input_schema.properties.report',
		'npcink-abilities-toolkit/build-article-optimization-apply-plan input_schema.properties.seo_meta',
		'npcink-abilities-toolkit/build-article-optimization-report input_schema.properties.geo',
		'npcink-abilities-toolkit/build-article-optimization-report input_schema.properties.internal_links',
		'npcink-abilities-toolkit/build-article-optimization-report input_schema.properties.media',
		'npcink-abilities-toolkit/build-article-optimization-report input_schema.properties.post',
		'npcink-abilities-toolkit/build-article-optimization-report input_schema.properties.seo',
		'npcink-abilities-toolkit/build-article-single-optimization-suggest input_schema.properties.generated_excerpt',
		'npcink-abilities-toolkit/build-article-single-optimization-suggest input_schema.properties.generated_seo',
		'npcink-abilities-toolkit/build-article-single-optimization-suggest input_schema.properties.geo_analysis',
		'npcink-abilities-toolkit/build-article-single-optimization-suggest input_schema.properties.post',
		'npcink-abilities-toolkit/build-article-single-optimization-suggest input_schema.properties.seo_analysis',
		'npcink-abilities-toolkit/build-article-single-optimization-suggest input_schema.properties.taxonomy_context',
		'npcink-abilities-toolkit/build-article-style-profile input_schema.properties.baseline_profile',
		'npcink-abilities-toolkit/build-article-style-profile input_schema.properties.reference_profile',
		'npcink-abilities-toolkit/build-block-theme-site-plan input_schema.properties.variables',
		'npcink-abilities-toolkit/build-image-candidate-adoption-plan input_schema.properties.candidate',
		'npcink-abilities-toolkit/build-image-candidate-adoption-plan input_schema.properties.image_candidate',
		'npcink-abilities-toolkit/build-image-candidate-review-artifact input_schema.properties.image_candidates.items',
		'npcink-abilities-toolkit/build-media-alt-caption-review-set input_schema.properties.image_context_evidence.properties.items.items',
		'npcink-abilities-toolkit/build-media-alt-caption-review-set input_schema.properties.media_snapshot.properties.items.items',
		'npcink-abilities-toolkit/build-inline-image-blocks input_schema.properties.generated_inline_media.items',
		'npcink-abilities-toolkit/build-inline-image-blocks input_schema.properties.inline_plan.items',
		'npcink-abilities-toolkit/build-inline-image-blocks input_schema.properties.uploaded_inline_media.items',
		'npcink-abilities-toolkit/build-media-adoption-preflight-summary input_schema.properties.derivative_artifact',
		'npcink-abilities-toolkit/build-media-optimization-plan input_schema.properties.derivative_artifact',
		'npcink-abilities-toolkit/build-media-optimization-plan input_schema.properties.media_details_input',
		'npcink-abilities-toolkit/build-media-seo-assets input_schema.properties.article',
		'npcink-abilities-toolkit/build-media-seo-assets input_schema.properties.featured_upload',
		'npcink-abilities-toolkit/build-media-seo-assets input_schema.properties.generated_featured_upload',
		'npcink-abilities-toolkit/build-media-seo-assets input_schema.properties.generated_inline_uploads.items',
		'npcink-abilities-toolkit/build-media-seo-assets input_schema.properties.inline_uploads.items',
		'npcink-abilities-toolkit/build-media-seo-assets input_schema.properties.resolved_image_source',
		'npcink-abilities-toolkit/build-pattern-page-plan input_schema.properties.research_brief',
		'npcink-abilities-toolkit/build-pattern-page-plan input_schema.properties.review_feedback',
		'npcink-abilities-toolkit/build-pattern-page-plan input_schema.properties.variables',
		'npcink-abilities-toolkit/compose-article-draft-result input_schema.properties.ai_slop_detection',
		'npcink-abilities-toolkit/compose-article-draft-result input_schema.properties.article',
		'npcink-abilities-toolkit/compose-article-draft-result input_schema.properties.draft',
		'npcink-abilities-toolkit/compose-article-draft-result input_schema.properties.duplicate_guard',
		'npcink-abilities-toolkit/compose-article-draft-result input_schema.properties.generated_seo',
		'npcink-abilities-toolkit/compose-article-draft-result input_schema.properties.geo_analysis',
		'npcink-abilities-toolkit/compose-article-draft-result input_schema.properties.input',
		'npcink-abilities-toolkit/compose-article-draft-result input_schema.properties.metadata_plan_resolution',
		'npcink-abilities-toolkit/compose-article-draft-result input_schema.properties.quality_scoring',
		'npcink-abilities-toolkit/compose-article-draft-result input_schema.properties.review',
		'npcink-abilities-toolkit/compose-article-draft-result input_schema.properties.seo_analysis',
		'npcink-abilities-toolkit/compose-article-optimization-apply-result input_schema.properties.apply_excerpt',
		'npcink-abilities-toolkit/compose-article-optimization-apply-result input_schema.properties.apply_plan',
		'npcink-abilities-toolkit/compose-article-optimization-apply-result input_schema.properties.report',
		'npcink-abilities-toolkit/compose-article-production-result input_schema.properties.article',
		'npcink-abilities-toolkit/compose-article-production-result input_schema.properties.draft',
		'npcink-abilities-toolkit/compose-article-production-result input_schema.properties.duplicate_guard',
		'npcink-abilities-toolkit/compose-article-production-result input_schema.properties.geo_analysis',
		'npcink-abilities-toolkit/compose-article-production-result input_schema.properties.input',
		'npcink-abilities-toolkit/compose-article-production-result input_schema.properties.media',
		'npcink-abilities-toolkit/compose-article-production-result input_schema.properties.metadata_plan_resolution',
		'npcink-abilities-toolkit/compose-article-production-result input_schema.properties.publication_decision',
		'npcink-abilities-toolkit/compose-article-production-result input_schema.properties.review',
		'npcink-abilities-toolkit/compose-article-production-result input_schema.properties.seo_analysis',
		'npcink-abilities-toolkit/compose-comment-mention-reply-result input_schema.properties.suggest_result',
		'npcink-abilities-toolkit/compose-comment-moderation-batch-result input_schema.properties.batch_result',
		'npcink-abilities-toolkit/compose-comment-moderation-result input_schema.properties.action_result_approve',
		'npcink-abilities-toolkit/compose-comment-moderation-result input_schema.properties.action_result_reply',
		'npcink-abilities-toolkit/compose-comment-moderation-result input_schema.properties.action_result_spam',
		'npcink-abilities-toolkit/compose-comment-moderation-result input_schema.properties.action_result_trash',
		'npcink-abilities-toolkit/compose-comment-moderation-result input_schema.properties.suggest_result',
		'npcink-abilities-toolkit/compose-gutenberg-block-plan input_schema.properties.plan_input',
		'npcink-abilities-toolkit/create-draft input_schema.properties.meta',
		'npcink-abilities-toolkit/evaluate-gutenberg-recipe-suite input_schema.properties.cases.items.properties.hints',
		'npcink-abilities-toolkit/evaluate-gutenberg-recipe-suite input_schema.properties.cases.items.properties.plan_input',
		'npcink-abilities-toolkit/inspect-gutenberg-composition-contract input_schema.properties.blocks.items',
		'npcink-abilities-toolkit/optimize-media-metadata input_schema.properties.media_assets.items',
		'npcink-abilities-toolkit/position-inline-image-blocks input_schema.properties.existing_blocks.items',
		'npcink-abilities-toolkit/position-inline-image-blocks input_schema.properties.inline_blocks.items',
		'npcink-abilities-toolkit/position-inline-image-blocks input_schema.properties.inline_plan.items',
		'npcink-abilities-toolkit/resolve-article-publication-decision input_schema.properties.duplicate_guard',
		'npcink-abilities-toolkit/resolve-article-publication-decision input_schema.properties.review',
		'npcink-abilities-toolkit/resolve-post-metadata-plan input_schema.properties.post_metadata_plan',
		'npcink-abilities-toolkit/resolve-post-metadata-plan input_schema.properties.taxonomy_plan',
		'npcink-abilities-toolkit/review-article-output-light input_schema.properties.article',
		'npcink-abilities-toolkit/review-article-output-light input_schema.properties.media',
		'npcink-abilities-toolkit/review-article-output-light input_schema.properties.style_profile',
		'npcink-abilities-toolkit/review-block-editor-surface input_schema.properties.blocks.items',
		'npcink-abilities-toolkit/review-pattern-page input_schema.properties.blocks.items',
		'npcink-abilities-toolkit/update-post-blocks input_schema.properties.blocks.items',
		'npcink-abilities-toolkit/update-template-blocks input_schema.properties.blocks.items',
		'npcink-abilities-toolkit/update-template-part-blocks input_schema.properties.blocks.items',
		'npcink-abilities-toolkit/upsert-template-blocks input_schema.properties.blocks.items',
	);

	return array_fill_keys( $paths, true );
}

/**
 * Audits intentional additionalProperties usage in input schemas.
 *
 * @param string              $ability_id Ability id.
 * @param array<string,mixed> $schema Schema node.
 * @param string              $path Schema path.
 * @param array<string,bool>  $allowlist Allowed paths.
 * @return void
 */
function npcink_abilities_toolkit_contract_audit_additional_properties( $ability_id, array $schema, $path, array $allowlist ) {
	if ( array_key_exists( 'additionalProperties', $schema ) && false !== $schema['additionalProperties'] ) {
		$key = $ability_id . ' ' . $path;
		if ( ! isset( $allowlist[ $key ] ) ) {
			npcink_abilities_toolkit_contract_audit_fail( "{$key} must be added to the additionalProperties allowlist or tightened" );
		}
	}

	if ( isset( $schema['properties'] ) && is_array( $schema['properties'] ) ) {
		foreach ( $schema['properties'] as $property => $property_schema ) {
			if ( is_array( $property_schema ) ) {
				npcink_abilities_toolkit_contract_audit_additional_properties( $ability_id, $property_schema, $path . '.properties.' . (string) $property, $allowlist );
			}
		}
	}

	if ( isset( $schema['items'] ) && is_array( $schema['items'] ) ) {
		npcink_abilities_toolkit_contract_audit_additional_properties( $ability_id, $schema['items'], $path . '.items', $allowlist );
	}
}

/**
 * Checks one normalized ability definition.
 *
 * @param string              $ability_id Ability id.
 * @param array<string,mixed> $ability    Ability definition.
 * @return void
 */
function npcink_abilities_toolkit_contract_audit_ability( $ability_id, array $ability ) {
	if ( $ability_id !== (string) ( $ability['ability_id'] ?? '' ) ) {
		npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} must match ability.ability_id" );
	}

	if ( false === strpos( $ability_id, '/' ) ) {
		npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} must use namespace/name format" );
	}

	foreach ( array( 'label', 'description', 'contract_version' ) as $field ) {
		if ( '' === trim( (string) ( $ability[ $field ] ?? '' ) ) ) {
			npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} is missing {$field}" );
		}
	}

	if ( ! is_callable( $ability['execute_callback'] ?? null ) ) {
		npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} execute_callback must be callable" );
	}

	if ( ! is_callable( $ability['permission_callback'] ?? null ) ) {
		npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} permission_callback must be callable" );
	}

	npcink_abilities_toolkit_contract_audit_schema( $ability_id, $ability, 'input_schema' );
	npcink_abilities_toolkit_contract_audit_schema( $ability_id, $ability, 'output_schema' );
	if ( isset( $ability['input_schema'] ) && is_array( $ability['input_schema'] ) ) {
		npcink_abilities_toolkit_contract_audit_additional_properties(
			$ability_id,
			$ability['input_schema'],
			'input_schema',
			npcink_abilities_toolkit_contract_audit_additional_properties_allowlist()
		);
	}

	$risk_level = (string) ( $ability['risk_level'] ?? '' );
	if ( ! in_array( $risk_level, array( 'read', 'write', 'destructive' ), true ) ) {
		npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} has unsupported risk_level {$risk_level}" );
		return;
	}

	$is_write = in_array( $risk_level, array( 'write', 'destructive' ), true );

	if ( $is_write !== (bool) ( $ability['requires_confirm'] ?? false ) ) {
		npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} requires_confirm does not match risk level" );
	}

	if ( $is_write !== (bool) ( $ability['requires_approval'] ?? false ) ) {
		npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} requires_approval does not match risk level" );
	}

	if ( true !== (bool) npcink_abilities_toolkit_contract_audit_get( $ability, array( 'meta', 'show_in_rest' ) ) ) {
		npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} must be visible through REST metadata" );
	}

	if ( $risk_level !== (string) npcink_abilities_toolkit_contract_audit_get( $ability, array( 'meta', 'mcp', 'risk' ) ) ) {
		npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} MCP risk must mirror risk_level" );
	}

	if ( $risk_level !== (string) npcink_abilities_toolkit_contract_audit_get( $ability, array( 'meta', 'npcink', 'risk_level' ) ) ) {
		npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} Npcink risk must mirror risk_level" );
	}

	if ( $ability_id !== (string) npcink_abilities_toolkit_contract_audit_get( $ability, array( 'meta', 'npcink', 'canonical_ability_id' ) ) ) {
		npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} Npcink metadata must preserve canonical ability id" );
	}

	$annotations = isset( $ability['annotations'] ) && is_array( $ability['annotations'] )
		? $ability['annotations']
		: array();
	if ( ( 'read' === $risk_level ) !== (bool) ( $annotations['readonly'] ?? false ) ) {
		npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} readonly annotation must match risk level" );
	}

	if ( ( 'destructive' === $risk_level ) !== (bool) ( $annotations['destructive'] ?? false ) ) {
		npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} destructive annotation must match risk level" );
	}

	$properties = isset( $ability['input_schema']['properties'] ) && is_array( $ability['input_schema']['properties'] )
		? $ability['input_schema']['properties']
		: array();

	foreach ( array( 'dry_run', 'commit', 'idempotency_key' ) as $control ) {
		if ( $is_write && ! isset( $properties[ $control ] ) ) {
			npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} write input is missing {$control}" );
		}
		if ( ! $is_write && isset( $properties[ $control ] ) ) {
			npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} read input must not expose {$control}" );
		}
	}

	if ( $is_write ) {
		if ( true !== (bool) ( $properties['dry_run']['default'] ?? false ) ) {
			npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} dry_run must default to true" );
		}
		if ( false !== (bool) ( $properties['commit']['default'] ?? true ) ) {
			npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} commit must default to false" );
		}
		if ( 190 < (int) ( $properties['idempotency_key']['maxLength'] ?? 191 ) ) {
			npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} idempotency_key maxLength must be <= 190" );
		}
		if ( false !== ( $ability['input_schema']['additionalProperties'] ?? null ) ) {
			npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} write input schema must reject undeclared fields" );
		}
	}
}

/**
 * Checks that priority agent entry and write abilities expose static usage guidance.
 *
 * @param array<string,array<string,mixed>> $abilities Registered abilities.
 * @return void
 */
function npcink_abilities_toolkit_contract_audit_agent_usage( array $abilities ) {
	$required_agent_usage = array(
		'npcink-abilities-toolkit/list-workflow-recipes',
		'npcink-abilities-toolkit/get-workflow-recipe',
		'npcink-abilities-toolkit/wp-diagnostics-summary',
		'npcink-abilities-toolkit/get-article-publish-preflight-context',
		'npcink-abilities-toolkit/get-old-article-refresh-context',
		'npcink-abilities-toolkit/get-media-cleanup-opportunities',
		'npcink-abilities-toolkit/propose-post-taxonomy-terms',
		'npcink-abilities-toolkit/get-comment-compliance-handoff',
		'npcink-abilities-toolkit/create-draft',
		'npcink-abilities-toolkit/set-post-seo-meta',
		'npcink-abilities-toolkit/update-media-details',
		'npcink-abilities-toolkit/approve-comment',
		'npcink-abilities-toolkit/delete-media-permanently',
	);

	foreach ( $required_agent_usage as $ability_id ) {
		if ( ! isset( $abilities[ $ability_id ] ) || ! is_array( $abilities[ $ability_id ] ) ) {
			npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} is missing from the registered ability set" );
			continue;
		}

		$ability    = $abilities[ $ability_id ];
		$usage      = isset( $ability['agent_usage'] ) && is_array( $ability['agent_usage'] )
			? $ability['agent_usage']
			: array();
		$meta_usage = npcink_abilities_toolkit_contract_audit_get( $ability, array( 'meta', 'agent_usage' ) );

		if ( $usage !== $meta_usage ) {
			npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} meta.agent_usage must mirror agent_usage" );
		}

		foreach ( array( 'when_to_use', 'not_for', 'best_for', 'stopping_points' ) as $field ) {
			if ( empty( $usage[ $field ] ) || ! is_array( $usage[ $field ] ) ) {
				npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} agent_usage.{$field} must contain at least one item" );
			}
		}
	}
}

/**
 * Checks that priority write abilities expose implementation posture metadata.
 *
 * @param array<string,array<string,mixed>> $abilities Registered abilities.
 * @return void
 */
function npcink_abilities_toolkit_contract_audit_implementation_posture( array $abilities ) {
	$required_posture = array(
		'npcink-abilities-toolkit/create-draft',
		'npcink-abilities-toolkit/update-post',
		'npcink-abilities-toolkit/update-post-blocks',
		'npcink-abilities-toolkit/set-post-terms',
		'npcink-abilities-toolkit/update-media-details',
	);

	foreach ( $required_posture as $ability_id ) {
		if ( ! isset( $abilities[ $ability_id ] ) || ! is_array( $abilities[ $ability_id ] ) ) {
			npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} is missing from the registered ability set" );
			continue;
		}

		$ability        = $abilities[ $ability_id ];
		$posture        = isset( $ability['implementation_posture'] ) && is_array( $ability['implementation_posture'] )
			? $ability['implementation_posture']
			: array();
		$meta_posture   = npcink_abilities_toolkit_contract_audit_get( $ability, array( 'meta', 'implementation_posture' ) );
		$npcink_posture = npcink_abilities_toolkit_contract_audit_get( $ability, array( 'meta', 'npcink', 'implementation_posture' ) );

		if ( empty( $posture ) ) {
			npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} must expose implementation_posture" );
			continue;
		}
		if ( $posture !== $meta_posture || $posture !== $npcink_posture ) {
			npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} implementation_posture must be mirrored into meta and meta.npcink" );
		}

		$expected = array(
			'schema_version'            => 'npcink_abilities_toolkit_implementation_posture.v1',
			'implementation_owner'      => 'npcink-abilities-toolkit',
			'execution_surface'         => 'wordpress_abilities_api_host_governed',
			'write_posture'             => 'host_governed_dry_run_first',
			'commit_authority'          => 'host_runtime_approval_context_required',
			'final_authorization_owner' => 'host_governance_layer',
			'approval_truth_owner'      => 'host_governance_layer',
			'audit_truth_owner'         => 'host_governance_layer',
		);
		foreach ( $expected as $field => $value ) {
			if ( $value !== (string) ( $posture[ $field ] ?? '' ) ) {
				npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} implementation_posture.{$field} must be {$value}" );
			}
		}

		if ( true !== (bool) ( $posture['dry_run_default'] ?? false ) ) {
			npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} implementation_posture.dry_run_default must be true" );
		}
		if ( false !== (bool) ( $posture['commit_default'] ?? true ) ) {
			npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} implementation_posture.commit_default must be false" );
		}
		if ( false !== (bool) ( $posture['direct_wordpress_write_default'] ?? true ) ) {
			npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} implementation_posture.direct_wordpress_write_default must be false" );
		}
		foreach ( array( 'reference_patterns', 'verification_contract', 'required_host_evidence' ) as $list_field ) {
			if ( empty( $posture[ $list_field ] ) || ! is_array( $posture[ $list_field ] ) ) {
				npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} implementation_posture.{$list_field} must contain at least one item" );
			}
		}
		foreach ( array( 'workflow_runtime', 'queue_or_scheduler', 'model_routing', 'provider_credentials', 'approval_storage', 'audit_storage' ) as $forbidden_flag ) {
			if ( false !== (bool) ( $posture[ $forbidden_flag ] ?? true ) ) {
				npcink_abilities_toolkit_contract_audit_fail( "{$ability_id} implementation_posture.{$forbidden_flag} must be false" );
			}
		}
	}
}

/**
 * Checks workflow recipes only reference registered ability ids.
 *
 * @param array<string,array<string,mixed>> $abilities Registered abilities.
 * @return void
 */
function npcink_abilities_toolkit_contract_audit_workflow_recipes( array $abilities ) {
	$manifest = npcink_abilities_toolkit_get_workflow_definitions();
	$cases    = isset( $manifest['cases'] ) && is_array( $manifest['cases'] )
		? $manifest['cases']
		: array();

	foreach ( $cases as $case_id => $case ) {
		if ( ! is_array( $case ) ) {
			npcink_abilities_toolkit_contract_audit_fail( "workflow case {$case_id} must be an object" );
			continue;
		}

		foreach ( array( 'recipe_id', 'title', 'entrypoint_ability_id', 'failure_policy' ) as $field ) {
			if ( '' === trim( (string) ( $case[ $field ] ?? '' ) ) ) {
				npcink_abilities_toolkit_contract_audit_fail( "workflow case {$case_id} is missing {$field}" );
			}
		}

		$referenced = array_merge(
			array( (string) ( $case['entrypoint_ability_id'] ?? '' ), (string) ( $case['preferred_ability_id'] ?? '' ) ),
			isset( $case['expanded_ability_ids'] ) && is_array( $case['expanded_ability_ids'] ) ? $case['expanded_ability_ids'] : array()
		);

		foreach ( array_filter( array_unique( $referenced ) ) as $ability_id ) {
			if ( ! isset( $abilities[ $ability_id ] ) ) {
				npcink_abilities_toolkit_contract_audit_fail( "workflow case {$case_id} references missing ability {$ability_id}" );
				continue;
			}

			if ( 'read' !== (string) ( $abilities[ $ability_id ]['risk_level'] ?? '' ) ) {
				npcink_abilities_toolkit_contract_audit_fail( "workflow case {$case_id} must reference read-only recipe abilities; {$ability_id} is not read" );
			}
		}
	}
}

Npcink_Abilities_Toolkit\Plugin::instance()->boot();

$abilities = npcink_abilities_toolkit_get_registered();
if ( empty( $abilities ) ) {
	npcink_abilities_toolkit_contract_audit_fail( 'No registered abilities found after default boot.' );
}

foreach ( $abilities as $ability_id => $ability ) {
	npcink_abilities_toolkit_contract_audit_ability( (string) $ability_id, is_array( $ability ) ? $ability : array() );
}

npcink_abilities_toolkit_contract_audit_agent_usage( $abilities );
npcink_abilities_toolkit_contract_audit_implementation_posture( $abilities );
npcink_abilities_toolkit_contract_audit_workflow_recipes( $abilities );

if ( ! empty( $failures ) ) {
	foreach ( $failures as $failure ) {
		fwrite( STDERR, '[fail] ' . $failure . PHP_EOL );
	}
	exit( 1 );
}

echo 'ability contract readiness: ok (' . count( $abilities ) . " abilities)\n";
