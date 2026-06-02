<?php
/**
 * WordPress smoke test for the standalone plugin.
 *
 * Run through WP-CLI:
 * wp eval-file tests/smoke-wp.php
 *
 * @package MagickAIAbilities
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "This smoke test must run inside WordPress through WP-CLI.\n" );
	exit( 1 );
}

$GLOBALS['magick_ai_abilities_smoke_assertions'] = 0;
$magick_ai_abilities_smoke_profile = sanitize_key( (string) ( getenv( 'MAGICK_AI_ABILITIES_SMOKE_PROFILE' ) ?: 'default' ) );
if ( '' === $magick_ai_abilities_smoke_profile ) {
	$magick_ai_abilities_smoke_profile = 'default';
}
$magick_ai_abilities_smoke_run_id                 = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'smoke-', true );
$magick_ai_abilities_smoke_pattern                = 'Magick AI Abilities Smoke ' . $magick_ai_abilities_smoke_run_id;
$magick_ai_abilities_smoke_post_fixture_ids       = array();
$magick_ai_abilities_smoke_comment_fixture_ids    = array();
$magick_ai_abilities_smoke_attachment_fixture_ids = array();
$magick_ai_abilities_smoke_term_fixtures          = array();
$magick_ai_abilities_smoke_cleanup_completed      = false;

/**
 * Asserts a smoke-test condition.
 *
 * @param bool   $condition Assertion result.
 * @param string $message Assertion message.
 * @return void
 */
function magick_ai_abilities_smoke_assert( $condition, $message ) {
	++$GLOBALS['magick_ai_abilities_smoke_assertions'];

	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}

	echo "[ok] {$message}\n";
}

/**
 * Registers a post fixture for cleanup even when the smoke test fails.
 *
 * @param int $post_id Post id.
 * @return void
 */
function magick_ai_abilities_smoke_register_post_fixture( $post_id ) {
	global $magick_ai_abilities_smoke_post_fixture_ids;

	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return;
	}

	$magick_ai_abilities_smoke_post_fixture_ids[ $post_id ] = true;
}

/**
 * Registers a comment fixture for cleanup even when the smoke test fails.
 *
 * @param int $comment_id Comment id.
 * @return void
 */
function magick_ai_abilities_smoke_register_comment_fixture( $comment_id ) {
	global $magick_ai_abilities_smoke_comment_fixture_ids;

	$comment_id = (int) $comment_id;
	if ( $comment_id <= 0 ) {
		return;
	}

	$magick_ai_abilities_smoke_comment_fixture_ids[ $comment_id ] = true;
}

/**
 * Registers a media fixture for cleanup even when the smoke test fails.
 *
 * @param int $attachment_id Attachment post id.
 * @return void
 */
function magick_ai_abilities_smoke_register_attachment_fixture( $attachment_id ) {
	global $magick_ai_abilities_smoke_attachment_fixture_ids;

	$attachment_id = (int) $attachment_id;
	if ( $attachment_id <= 0 ) {
		return;
	}

	$magick_ai_abilities_smoke_attachment_fixture_ids[ $attachment_id ] = true;
}

/**
 * Registers a taxonomy term fixture for cleanup even when the smoke test fails.
 *
 * @param int    $term_id Term id.
 * @param string $taxonomy Taxonomy id.
 * @return void
 */
function magick_ai_abilities_smoke_register_term_fixture( $term_id, $taxonomy ) {
	global $magick_ai_abilities_smoke_term_fixtures;

	$term_id  = (int) $term_id;
	$taxonomy = (string) $taxonomy;
	if ( $term_id <= 0 || '' === $taxonomy ) {
		return;
	}

	$magick_ai_abilities_smoke_term_fixtures[ $taxonomy . ':' . $term_id ] = array(
		'term_id'  => $term_id,
		'taxonomy' => $taxonomy,
	);
}

/**
 * Deletes registered smoke fixtures.
 *
 * @return void
 */
function magick_ai_abilities_smoke_cleanup_fixtures() {
	global $magick_ai_abilities_smoke_post_fixture_ids, $magick_ai_abilities_smoke_comment_fixture_ids, $magick_ai_abilities_smoke_attachment_fixture_ids, $magick_ai_abilities_smoke_term_fixtures, $magick_ai_abilities_smoke_cleanup_completed;

	if ( $magick_ai_abilities_smoke_cleanup_completed ) {
		return;
	}

	$magick_ai_abilities_smoke_cleanup_completed = true;

	foreach ( array_keys( (array) $magick_ai_abilities_smoke_comment_fixture_ids ) as $comment_id ) {
		$comment_id = (int) $comment_id;
		if ( $comment_id <= 0 || ! get_comment( $comment_id ) ) {
			continue;
		}

		if ( false === wp_delete_comment( $comment_id, true ) ) {
			fwrite( STDERR, '[warn] failed to delete smoke comment fixture ' . $comment_id . "\n" );
		}
	}

	foreach ( array_keys( (array) $magick_ai_abilities_smoke_attachment_fixture_ids ) as $attachment_id ) {
		$attachment_id = (int) $attachment_id;
		if ( $attachment_id <= 0 || 'attachment' !== get_post_type( $attachment_id ) ) {
			continue;
		}

		if ( false === wp_delete_attachment( $attachment_id, true ) ) {
			fwrite( STDERR, '[warn] failed to delete smoke attachment fixture ' . $attachment_id . "\n" );
		}
	}

	foreach ( array_keys( (array) $magick_ai_abilities_smoke_post_fixture_ids ) as $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 || false === get_post_type( $post_id ) ) {
			continue;
		}

		if ( false === wp_delete_post( $post_id, true ) ) {
			fwrite( STDERR, '[warn] failed to delete smoke post fixture ' . $post_id . "\n" );
		}
	}

	foreach ( (array) $magick_ai_abilities_smoke_term_fixtures as $term_fixture ) {
		$term_id  = (int) ( $term_fixture['term_id'] ?? 0 );
		$taxonomy = (string) ( $term_fixture['taxonomy'] ?? '' );
		if ( $term_id <= 0 || '' === $taxonomy || ! term_exists( $term_id, $taxonomy ) ) {
			continue;
		}

		$deleted = wp_delete_term( $term_id, $taxonomy );
		if ( is_wp_error( $deleted ) || false === $deleted ) {
			fwrite( STDERR, '[warn] failed to delete smoke term fixture ' . $taxonomy . ':' . $term_id . "\n" );
		}
	}
}

register_shutdown_function( 'magick_ai_abilities_smoke_cleanup_fixtures' );

/**
 * Checks whether a REST collection response contains an ability name.
 *
 * @param mixed  $items REST response data.
 * @param string $name Ability name.
 * @return bool
 */
function magick_ai_abilities_smoke_has_ability_name( $items, $name ) {
	if ( ! is_array( $items ) ) {
		return false;
	}

	foreach ( $items as $item ) {
		if ( is_array( $item ) && isset( $item['name'] ) && $name === $item['name'] ) {
			return true;
		}
	}

	return false;
}

/**
 * Fetches all ability catalog pages through REST.
 *
 * @param string $namespace Optional namespace filter.
 * @param string $status_message Assertion message for the first page.
 * @return array<int,mixed>
 */
function magick_ai_abilities_smoke_rest_catalog( $namespace, $status_message ) {
	$items = array();
	$page = 1;
	$total_pages = 1;

	do {
		$request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities' );
		$request->set_param( 'per_page', 100 );
		$request->set_param( 'page', $page );
		if ( '' !== $namespace ) {
			$request->set_param( 'namespace', $namespace );
		}

		$response = rest_do_request( $request );
		if ( 1 === $page ) {
			magick_ai_abilities_smoke_assert( 200 === (int) $response->get_status(), $status_message );
		} else {
			magick_ai_abilities_smoke_assert( 200 === (int) $response->get_status(), "Authenticated abilities REST catalog page {$page} returns 200." );
		}

		$data = $response->get_data();
		if ( is_array( $data ) ) {
			$items = array_merge( $items, $data );
		}

		$headers = $response->get_headers();
		$total_pages = isset( $headers['X-WP-TotalPages'] ) ? max( 1, (int) $headers['X-WP-TotalPages'] ) : $total_pages;
		++$page;
	} while ( $page <= $total_pages );

	return $items;
}

/**
 * Fetches one ability detail through REST.
 *
 * @param string $ability_id Ability id.
 * @return array<string,mixed>
 */
function magick_ai_abilities_smoke_rest_ability( $ability_id ) {
	$request  = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/' . $ability_id );
	$response = rest_do_request( $request );
	magick_ai_abilities_smoke_assert( 200 === (int) $response->get_status(), "Authenticated ability REST detail for {$ability_id} returns 200." );

	$data = $response->get_data();
	return is_array( $data ) ? $data : array();
}

/**
 * Asserts that one REST ability detail exposes governance fields.
 *
 * @param string $ability_id Ability id.
 * @param string $risk_level Expected risk.
 * @param bool   $requires_approval Expected approval requirement.
 * @return void
 */
function magick_ai_abilities_smoke_assert_rest_governance_contract( $ability_id, $risk_level, $requires_approval ) {
	$ability = magick_ai_abilities_smoke_rest_ability( $ability_id );

	magick_ai_abilities_smoke_assert( $ability_id === (string) ( $ability['name'] ?? '' ), "{$ability_id} REST detail keeps the real ability id." );
	magick_ai_abilities_smoke_assert( is_array( $ability['input_schema'] ?? null ), "{$ability_id} REST detail exposes input_schema." );
	magick_ai_abilities_smoke_assert( is_array( $ability['output_schema'] ?? null ), "{$ability_id} REST detail exposes output_schema." );
	magick_ai_abilities_smoke_assert( $risk_level === (string) ( $ability['meta']['magick']['risk_level'] ?? '' ), "{$ability_id} REST detail exposes risk_level." );
	magick_ai_abilities_smoke_assert( $requires_approval === (bool) ( $ability['meta']['magick']['requires_approval'] ?? ! $requires_approval ), "{$ability_id} REST detail exposes requires_approval." );
}

magick_ai_abilities_smoke_assert( function_exists( 'wp_register_ability' ), 'WordPress Abilities API registration function exists.' );
magick_ai_abilities_smoke_assert( function_exists( 'wp_register_ability_category' ), 'WordPress Abilities API category registration function exists.' );
magick_ai_abilities_smoke_assert( function_exists( 'magick_ai_abilities_register_readonly' ), 'Plugin public readonly registration helper is loaded.' );

if ( 'light_core_read' !== $magick_ai_abilities_smoke_profile ) {
	update_option( 'magick_ai_abilities_demo_enabled', '1' );
}

if ( function_exists( 'wp_get_abilities' ) ) {
	wp_get_abilities();
}

if ( 'light_core_read' !== $magick_ai_abilities_smoke_profile ) {
	magick_ai_abilities_smoke_assert(
		! function_exists( 'wp_has_ability' ) || wp_has_ability( 'magick-ai-abilities/site-summary' ),
		'Demo site-summary ability is registered.'
	);
}

$admin_ids = get_users(
	array(
		'role'   => 'administrator',
		'number' => 1,
		'fields' => 'ID',
	)
);
$admin_id = isset( $admin_ids[0] ) ? (int) $admin_ids[0] : 0;
magick_ai_abilities_smoke_assert( $admin_id > 0, 'An administrator user is available for authenticated REST smoke checks.' );

wp_set_current_user( $admin_id );

$abilities_data = magick_ai_abilities_smoke_rest_catalog( '', 'Authenticated abilities REST catalog returns 200.' );
$provider_abilities_data = magick_ai_abilities_smoke_rest_catalog( 'magick-ai-abilities', 'Authenticated provider namespace REST catalog returns 200.' );
if ( 'light_core_read' !== $magick_ai_abilities_smoke_profile ) {
	magick_ai_abilities_smoke_assert(
		magick_ai_abilities_smoke_has_ability_name( $provider_abilities_data, 'magick-ai-abilities/site-summary' ),
		'Authenticated abilities REST catalog contains the demo ability.'
	);
		magick_ai_abilities_smoke_assert(
			magick_ai_abilities_smoke_has_ability_name( $provider_abilities_data, 'magick-ai-abilities/wp-diagnostics-summary' ),
			'Authenticated abilities REST catalog contains the standalone diagnostics ability.'
		);
		magick_ai_abilities_smoke_assert(
			magick_ai_abilities_smoke_has_ability_name( $provider_abilities_data, 'magick-ai-abilities/wp-ops-diagnostics-detail' ),
			'Authenticated abilities REST catalog contains the standalone ops diagnostics detail ability.'
		);
		magick_ai_abilities_smoke_assert(
			magick_ai_abilities_smoke_has_ability_name( $provider_abilities_data, 'magick-ai-abilities/list-workflow-recipes' ),
			'Authenticated abilities REST catalog contains the workflow recipe discovery ability.'
		);
		magick_ai_abilities_smoke_assert_rest_governance_contract( 'magick-ai/create-draft', 'write', true );
		magick_ai_abilities_smoke_assert_rest_governance_contract( 'magick-ai/set-post-seo-meta', 'write', true );
		magick_ai_abilities_smoke_assert_rest_governance_contract( 'magick-ai/approve-comment', 'write', true );
		magick_ai_abilities_smoke_assert_rest_governance_contract( 'magick-ai-abilities/list-workflow-recipes', 'read', false );
	}

if ( 'light_core_read' === $magick_ai_abilities_smoke_profile ) {
	foreach ( array( 'magick-ai/site-info', 'magick-ai/get-post', 'magick-ai/list-posts', 'magick-ai/search-posts', 'magick-ai/search-post-meta' ) as $expected_core_read_id ) {
		magick_ai_abilities_smoke_assert(
			! function_exists( 'wp_has_ability' ) || wp_has_ability( $expected_core_read_id ),
			"Light profile keeps core WordPress read ability {$expected_core_read_id}."
		);
	}
		foreach ( array( 'magick-ai/get-site-operations-dashboard', 'magick-ai/get-test-content-inventory', 'magick-ai/build-test-content-cleanup-plan', 'magick-ai/build-content-inventory-fix-plan', 'magick-ai/build-media-inventory-fix-plan', 'magick-ai-abilities/wp-diagnostics-summary', 'magick-ai-abilities/wp-ops-diagnostics-detail', 'magick-ai-abilities/list-workflow-recipes', 'magick-ai/create-draft', 'magick-ai/get-comment-queue-health' ) as $disabled_ability_id ) {
			magick_ai_abilities_smoke_assert(
				! function_exists( 'wp_has_ability' ) || ! wp_has_ability( $disabled_ability_id ),
				"Light profile disables optional ability {$disabled_ability_id}."
		);
	}

	echo 'Smoke OK: ' . (int) $GLOBALS['magick_ai_abilities_smoke_assertions'] . " assertions\n";
	return;
}
foreach (
	array(
			'magick-ai/site-info',
			'magick-ai-abilities/list-workflow-recipes',
			'magick-ai-abilities/get-workflow-recipe',
			'magick-ai/list-post-types',
		'magick-ai/list-taxonomies',
		'magick-ai/count-posts',
		'magick-ai/list-pages-tree',
		'magick-ai/list-posts',
		'magick-ai/get-post',
		'magick-ai/get-post-context',
		'magick-ai/get-content-publishing-checklist',
		'magick-ai/get-content-inventory-health',
		'magick-ai/get-test-content-inventory',
		'magick-ai/build-test-content-cleanup-plan',
		'magick-ai/build-content-inventory-fix-plan',
		'magick-ai/get-bulk-publishing-checklist',
		'magick-ai/get-internal-link-opportunity-report',
		'magick-ai/get-site-operations-dashboard',
		'magick-ai/get-post-publish-risk-report',
		'magick-ai/get-article-publish-preflight-context',
		'magick-ai/get-content-refresh-opportunities',
		'magick-ai/get-old-article-refresh-context',
		'magick-ai/get-internal-link-graph-health',
		'magick-ai/get-media-cleanup-opportunities',
		'magick-ai/build-media-inventory-fix-plan',
		'magick-ai/get-taxonomy-consolidation-suggestions',
		'magick-ai/propose-post-taxonomy-terms',
		'magick-ai/get-page-structure-health',
		'magick-ai/get-seo-geo-gap-report',
		'magick-ai/get-site-style-baseline',
		'magick-ai/build-article-workflow-context',
		'magick-ai/get-publishing-calendar-context',
		'magick-ai/get-media-inventory-health',
		'magick-ai/inspect-media-asset',
		'magick-ai/get-post-seo-geo-readiness',
		'magick-ai/get-site-topic-coverage-report',
		'magick-ai/get-taxonomy-inventory-health',
		'magick-ai/get-revision-change-risk-report',
		'magick-ai/resolve-url-to-post',
		'magick-ai/get-post-blocks',
		'magick-ai/list-post-revisions',
		'magick-ai/list-media',
		'magick-ai/list-terms',
		'magick-ai/list-taxonomy-terms',
		'magick-ai/list-categories',
		'magick-ai/list-tags',
		'magick-ai/get-term',
		'magick-ai/propose-post-excerpt',
		'magick-ai/resolve-post-metadata-plan',
			'magick-ai/list-users',
			'magick-ai/list-comments',
			'magick-ai/build-comment-moderation-suggest',
			'magick-ai/compose-comment-moderation-result',
			'magick-ai/build-comment-mention-reply-suggest',
			'magick-ai/read-comment-trigger-queue',
			'magick-ai/get-comment-queue-health',
			'magick-ai/get-comment-action-priority-queue',
			'magick-ai/get-comment-compliance-handoff',
			'magick-ai/compose-comment-mention-reply-result',
			'magick-ai/build-comment-moderation-batch-suggest',
			'magick-ai/compose-comment-moderation-batch-result',
			'magick-ai/list-menus',
			'magick-ai/get-menu',
			'magick-ai/search-posts',
			'magick-ai/search-post-meta',
			'magick-ai/resolve-internal-link-targets',
			'magick-ai/build-inline-image-blocks',
			'magick-ai/build-media-seo-assets',
			'magick-ai/geo-analyze',
			'magick-ai/optimize-media-metadata',
			'magick-ai/position-inline-image-blocks',
			'magick-ai/build-article-optimization-report',
			'magick-ai/seo-report-context',
			'magick-ai/read-post-optimization-context',
			'magick-ai/build-article-single-optimization-suggest',
			'magick-ai/build-article-optimization-apply-plan',
			'magick-ai/compose-article-optimization-apply-result',
			'magick-ai/extract-reference-post-style',
			'magick-ai/extract-style-baseline',
			'magick-ai/build-article-production-fingerprint',
			'magick-ai/check-article-production-duplicate',
			'magick-ai/review-article-output-light',
			'magick-ai/compose-article-production-result',
			'magick-ai/compose-article-draft-result',
			'magick-ai/resolve-article-publication-decision',
			'magick-ai/build-article-style-profile',
			'magick-ai/get-post-stats',
		'magick-ai/list-revisions',
		'magick-ai/get-post-meta',
		'magick-ai/list-pages',
		'magick-ai/get-page',
		'magick-ai/inspect-page-structure',
		'magick-ai/create-draft',
		'magick-ai/update-post',
		'magick-ai/set-post-seo-meta',
		'magick-ai/patch-post-content',
		'magick-ai/update-post-blocks',
		'magick-ai/set-post-slug',
		'magick-ai/set-post-author',
		'magick-ai/set-post-template',
		'magick-ai/set-post-format',
		'magick-ai/create-term',
		'magick-ai/update-term',
		'magick-ai/set-post-terms',
		'magick-ai/update-media-details',
		'magick-ai/upload-media-from-url',
		'magick-ai/optimize-media-asset',
		'magick-ai/replace-media-file',
		'magick-ai/set-post-featured-image',
		'magick-ai/schedule-post',
		'magick-ai/publish-post',
		'magick-ai/restore-post',
		'magick-ai/approve-comment',
		'magick-ai/reply-comment',
		'magick-ai/delete-term',
		'magick-ai/merge-terms',
		'magick-ai/bulk-update-post-terms',
		'magick-ai/spam-comment',
		'magick-ai/trash-comment',
		'magick-ai/delete-media-permanently',
		'magick-ai/trash-post',
		'magick-ai/delete-post-permanently',
	) as $migrated_ability_id
) {
	magick_ai_abilities_smoke_assert(
		! function_exists( 'wp_has_ability' ) || wp_has_ability( $migrated_ability_id ),
		"WordPress registry contains core package ability {$migrated_ability_id}."
	);
}

$categories_request  = new WP_REST_Request( 'GET', '/wp-abilities/v1/categories' );
$categories_response = rest_do_request( $categories_request );
magick_ai_abilities_smoke_assert( 200 === (int) $categories_response->get_status(), 'Authenticated categories REST catalog returns 200.' );

$run_request  = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai-abilities/site-summary/run' );
$run_request->set_query_params( array( 'input' => array() ) );
$run_response = rest_do_request( $run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $run_response->get_status(), 'Authenticated demo ability run returns 200.' );

$diagnostics_run_request  = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai-abilities/wp-diagnostics-summary/run' );
$diagnostics_run_request->set_query_params( array( 'input' => array() ) );
	$diagnostics_run_response = rest_do_request( $diagnostics_run_request );
	magick_ai_abilities_smoke_assert( 200 === (int) $diagnostics_run_response->get_status(), 'Authenticated diagnostics ability run returns 200.' );

	$ops_diagnostics_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai-abilities/wp-ops-diagnostics-detail/run' );
	$ops_diagnostics_run_request->set_query_params( array( 'input' => array( 'max_cron_events' => 5, 'max_plugins_per_group' => 5 ) ) );
	$ops_diagnostics_run_response = rest_do_request( $ops_diagnostics_run_request );
	magick_ai_abilities_smoke_assert( 200 === (int) $ops_diagnostics_run_response->get_status(), 'Authenticated ops diagnostics detail ability run returns 200.' );
	$ops_diagnostics_run_data = $ops_diagnostics_run_response->get_data();
	magick_ai_abilities_smoke_assert( isset( $ops_diagnostics_run_data['plugins']['groups_included']['inactive'] ) && false === $ops_diagnostics_run_data['plugins']['groups_included']['inactive'], 'Ops diagnostics omits inactive plugin rows by default.' );
	magick_ai_abilities_smoke_assert( isset( $ops_diagnostics_run_data['plugins']['max_plugins_per_group'] ) && 5 === (int) $ops_diagnostics_run_data['plugins']['max_plugins_per_group'], 'Ops diagnostics honors plugin group row bounds.' );
	magick_ai_abilities_smoke_assert( isset( $ops_diagnostics_run_data['error_log']['summary']['fatal_count'] ), 'Ops diagnostics returns error log severity summary without log contents.' );
	magick_ai_abilities_smoke_assert( isset( $ops_diagnostics_run_data['error_log']['source_summary'] ) && is_array( $ops_diagnostics_run_data['error_log']['source_summary'] ), 'Ops diagnostics returns error log source summary.' );
	magick_ai_abilities_smoke_assert( isset( $ops_diagnostics_run_data['error_log']['top_messages'] ) && is_array( $ops_diagnostics_run_data['error_log']['top_messages'] ), 'Ops diagnostics returns error log top message summary.' );
	if ( ! empty( $ops_diagnostics_run_data['error_log']['source_summary'] ) ) {
		$ops_diagnostics_first_source = reset( $ops_diagnostics_run_data['error_log']['source_summary'] );
		magick_ai_abilities_smoke_assert( is_array( $ops_diagnostics_first_source ) && array_key_exists( 'message_fingerprint', $ops_diagnostics_first_source ), 'Ops diagnostics source summary includes message fingerprints when entries exist.' );
	}

	$workflow_recipes_run_request  = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai-abilities/list-workflow-recipes/run' );
	$workflow_recipes_run_request->set_query_params( array( 'input' => array() ) );
	$workflow_recipes_run_response = rest_do_request( $workflow_recipes_run_request );
	magick_ai_abilities_smoke_assert( 200 === (int) $workflow_recipes_run_response->get_status(), 'Authenticated workflow recipe discovery ability run returns 200.' );
	$workflow_recipes_run_data = $workflow_recipes_run_response->get_data();
	magick_ai_abilities_smoke_assert( isset( $workflow_recipes_run_data['cases']['article_publish_preflight'] ), 'Workflow recipe discovery returns publish preflight definition.' );

	$workflow_recipe_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai-abilities/get-workflow-recipe/run' );
	$workflow_recipe_run_request->set_query_params(
		array(
			'input' => array(
				'recipe_id' => 'workflow/wordpress_comment_compliance_handoff',
			),
		)
	);
	$workflow_recipe_run_response = rest_do_request( $workflow_recipe_run_request );
	magick_ai_abilities_smoke_assert( 200 === (int) $workflow_recipe_run_response->get_status(), 'Authenticated workflow recipe detail ability run returns 200.' );
	$workflow_recipe_run_data = $workflow_recipe_run_response->get_data();
	magick_ai_abilities_smoke_assert( 'magick-ai/get-comment-compliance-handoff' === (string) ( $workflow_recipe_run_data['entrypoint_ability_id'] ?? '' ), 'Workflow recipe detail returns comment handoff entrypoint.' );

	$smoke_post_id = wp_insert_post(
		array(
			'post_type'    => 'post',
			'post_status'  => 'draft',
			'post_title'   => $magick_ai_abilities_smoke_pattern . ' Context Post',
			'post_content' => '<!-- wp:paragraph --><p>This local smoke post verifies the post context and publishing checklist abilities.</p><!-- /wp:paragraph -->',
			'post_excerpt' => 'Smoke context post.',
		),
		true
	);
	magick_ai_abilities_smoke_assert( ! is_wp_error( $smoke_post_id ) && (int) $smoke_post_id > 0, 'Temporary smoke post is available for post-context ability runs.' );
	magick_ai_abilities_smoke_register_post_fixture( (int) $smoke_post_id );
	update_post_meta( (int) $smoke_post_id, '_magick_ai_smoke_search_marker', 'local smoke metadata search marker' );

$post_context_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/get-post-context/run' );
$post_context_run_request->set_query_params(
	array(
		'input' => array(
			'post_id'       => (int) $smoke_post_id,
			'include_meta'  => false,
			'include_terms' => false,
		),
	)
);
$post_context_run_response = rest_do_request( $post_context_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $post_context_run_response->get_status(), 'Authenticated post context ability run returns 200.' );
$post_context_run_data = $post_context_run_response->get_data();

$search_posts_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/search-posts/run' );
$search_posts_run_request->set_query_params(
	array(
		'input' => array(
			'search'    => 'context',
			'post_types' => array( 'post' ),
			'statuses'  => array( 'draft' ),
			'per_page'  => 5,
		),
	)
);
$search_posts_run_response = rest_do_request( $search_posts_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $search_posts_run_response->get_status(), 'Authenticated post keyword search ability run returns 200.' );
$search_posts_run_data = $search_posts_run_response->get_data();
magick_ai_abilities_smoke_assert( ! empty( $search_posts_run_data['items'] ), 'Post keyword search returns the local smoke post candidate.' );

$search_meta_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/search-post-meta/run' );
$search_meta_run_request->set_query_params(
	array(
		'input' => array(
			'search'    => 'metadata search marker',
			'meta_keys' => array( '_magick_ai_smoke_search_marker' ),
			'statuses'  => array( 'draft' ),
			'per_page'  => 5,
		),
	)
);
$search_meta_run_response = rest_do_request( $search_meta_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $search_meta_run_response->get_status(), 'Authenticated post meta search ability run returns 200.' );
$search_meta_run_data = $search_meta_run_response->get_data();
magick_ai_abilities_smoke_assert( ! empty( $search_meta_run_data['items'][0]['matched_meta_keys'] ), 'Post meta search returns matched meta keys.' );

$publishing_checklist_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/get-content-publishing-checklist/run' );
$publishing_checklist_run_request->set_query_params(
	array(
		'input' => array(
			'post_id' => (int) $smoke_post_id,
		),
	)
);
$publishing_checklist_run_response = rest_do_request( $publishing_checklist_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $publishing_checklist_run_response->get_status(), 'Authenticated publishing checklist ability run returns 200.' );
$publishing_checklist_run_data = $publishing_checklist_run_response->get_data();

$smoke_candidate_id = wp_insert_post(
	array(
		'post_type'    => 'post',
		'post_status'  => 'publish',
		'post_title'   => $magick_ai_abilities_smoke_pattern . ' Internal Link Candidate',
		'post_content' => '<p>This published smoke post provides a related ability workflow candidate for internal link opportunity checks.</p>',
	),
	true
);
magick_ai_abilities_smoke_assert( ! is_wp_error( $smoke_candidate_id ) && (int) $smoke_candidate_id > 0, 'Temporary smoke candidate post is available for internal-link ability runs.' );
magick_ai_abilities_smoke_register_post_fixture( (int) $smoke_candidate_id );

$inventory_health_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/get-content-inventory-health/run' );
$inventory_health_run_request->set_query_params(
	array(
		'input' => array(
			'post_type' => 'post',
			'status'    => 'any',
			'per_page'  => 10,
		),
	)
);
$inventory_health_run_response = rest_do_request( $inventory_health_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $inventory_health_run_response->get_status(), 'Authenticated content inventory health ability run returns 200.' );

$test_inventory_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/get-test-content-inventory/run' );
$test_inventory_run_request->set_query_params(
	array(
		'input' => array(
			'patterns' => array( $magick_ai_abilities_smoke_pattern ),
			'per_page' => 10,
		),
	)
);
$test_inventory_run_response = rest_do_request( $test_inventory_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $test_inventory_run_response->get_status(), 'Authenticated test content inventory ability run returns 200.' );
$test_inventory_run_data = $test_inventory_run_response->get_data();
magick_ai_abilities_smoke_assert( true === ( $test_inventory_run_data['data']['detected'] ?? null ), 'Test content inventory detects smoke content.' );

$test_cleanup_plan_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/build-test-content-cleanup-plan/run' );
$test_cleanup_plan_run_request->set_query_params(
	array(
		'input' => array(
			'patterns'    => array( $magick_ai_abilities_smoke_pattern ),
			'max_actions' => 5,
		),
	)
);
$test_cleanup_plan_run_response = rest_do_request( $test_cleanup_plan_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $test_cleanup_plan_run_response->get_status(), 'Authenticated test content cleanup plan ability run returns 200.' );
$test_cleanup_plan_run_data = $test_cleanup_plan_run_response->get_data();
magick_ai_abilities_smoke_assert( false === ( $test_cleanup_plan_run_data['data']['commit_execution'] ?? null ), 'Test content cleanup plan does not execute commits.' );
magick_ai_abilities_smoke_assert( 'batch' === (string) ( $test_cleanup_plan_run_data['data']['proposal_mode'] ?? '' ), 'Test content cleanup plan requests batch proposal mode.' );
magick_ai_abilities_smoke_assert( true === (bool) ( $test_cleanup_plan_run_data['data']['batch_approval'] ?? false ), 'Test content cleanup plan requests one approval for the generated batch.' );

$content_fix_plan_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/build-content-inventory-fix-plan/run' );
$content_fix_plan_run_request->set_query_params(
	array(
		'input' => array(
			'post_ids'    => array( (int) $smoke_post_id ),
			'issue_types' => array( 'seo_title', 'seo_description', 'featured_media' ),
			'max_actions' => 5,
		),
	)
);
$content_fix_plan_run_response = rest_do_request( $content_fix_plan_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $content_fix_plan_run_response->get_status(), 'Authenticated content inventory fix plan ability run returns 200.' );
$content_fix_plan_run_data = $content_fix_plan_run_response->get_data();
magick_ai_abilities_smoke_assert( false === ( $content_fix_plan_run_data['data']['commit_execution'] ?? null ), 'Content inventory fix plan does not execute commits.' );

$bulk_checklist_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/get-bulk-publishing-checklist/run' );
$bulk_checklist_run_request->set_query_params(
	array(
		'input' => array(
			'post_ids' => array( (int) $smoke_post_id, (int) $smoke_candidate_id ),
		),
	)
);
$bulk_checklist_run_response = rest_do_request( $bulk_checklist_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $bulk_checklist_run_response->get_status(), 'Authenticated bulk publishing checklist ability run returns 200.' );

$internal_link_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/get-internal-link-opportunity-report/run' );
$internal_link_run_request->set_query_params(
	array(
		'input' => array(
			'post_id'       => (int) $smoke_post_id,
			'focus_keyword' => 'ability workflow',
			'max_targets'   => 3,
		),
	)
);
$internal_link_run_response = rest_do_request( $internal_link_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $internal_link_run_response->get_status(), 'Authenticated internal link opportunity ability run returns 200.' );
$internal_link_run_data = $internal_link_run_response->get_data();

$site_operations_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/get-site-operations-dashboard/run' );
$site_operations_run_request->set_query_params(
	array(
		'input' => array(
			'post_type' => 'post',
			'per_page'  => 10,
		),
	)
);
$site_operations_run_response = rest_do_request( $site_operations_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $site_operations_run_response->get_status(), 'Authenticated site operations dashboard ability run returns 200.' );

$publish_risk_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/get-post-publish-risk-report/run' );
$publish_risk_run_request->set_query_params(
	array(
		'input' => array(
			'post_id'       => (int) $smoke_post_id,
			'focus_keyword' => 'ability workflow',
		),
	)
);
$publish_risk_run_response = rest_do_request( $publish_risk_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $publish_risk_run_response->get_status(), 'Authenticated post publish risk report ability run returns 200.' );
$publish_risk_run_data = $publish_risk_run_response->get_data();

$article_publish_preflight_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/get-article-publish-preflight-context/run' );
$article_publish_preflight_run_request->set_query_params(
	array(
		'input' => array(
			'post_id'       => (int) $smoke_post_id,
			'focus_keyword' => 'ability workflow',
			'window_days'   => 30,
		),
	)
);
$article_publish_preflight_run_response = rest_do_request( $article_publish_preflight_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $article_publish_preflight_run_response->get_status(), 'Authenticated article publish preflight context ability run returns 200.' );
$article_publish_preflight_run_data = $article_publish_preflight_run_response->get_data();

$refresh_opportunities_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/get-content-refresh-opportunities/run' );
$refresh_opportunities_run_request->set_query_params(
	array(
		'input' => array(
			'post_type'      => 'post',
			'status'         => 'any',
			'per_page'       => 10,
			'min_word_count' => 200,
		),
	)
);
$refresh_opportunities_run_response = rest_do_request( $refresh_opportunities_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $refresh_opportunities_run_response->get_status(), 'Authenticated content refresh opportunities ability run returns 200.' );
$refresh_opportunities_run_data = $refresh_opportunities_run_response->get_data();

$link_graph_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/get-internal-link-graph-health/run' );
$link_graph_run_request->set_query_params(
	array(
		'input' => array(
			'post_type' => 'post',
			'status'    => 'any',
			'per_page'  => 10,
		),
	)
);
$link_graph_run_response = rest_do_request( $link_graph_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $link_graph_run_response->get_status(), 'Authenticated internal link graph health ability run returns 200.' );
$link_graph_run_data = $link_graph_run_response->get_data();

$old_article_refresh_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/get-old-article-refresh-context/run' );
$old_article_refresh_run_request->set_query_params(
	array(
		'input' => array(
			'post_type'     => 'post',
			'status'        => 'any',
			'per_page'      => 10,
			'topic_seed'    => 'ability workflow',
			'post_id'       => (int) $smoke_post_id,
			'focus_keyword' => 'ability workflow',
		),
	)
);
$old_article_refresh_run_response = rest_do_request( $old_article_refresh_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $old_article_refresh_run_response->get_status(), 'Authenticated old article refresh context ability run returns 200.' );
$old_article_refresh_run_data = $old_article_refresh_run_response->get_data();

$smoke_attachment_id = wp_insert_post(
	array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'post_title'     => $magick_ai_abilities_smoke_pattern . ' Media Asset',
		'post_mime_type' => 'image/jpeg',
		'post_excerpt'   => '',
		'post_content'   => '',
	),
	true
);
magick_ai_abilities_smoke_assert( ! is_wp_error( $smoke_attachment_id ) && (int) $smoke_attachment_id > 0, 'Temporary smoke media asset is available for media inventory ability runs.' );
magick_ai_abilities_smoke_register_attachment_fixture( (int) $smoke_attachment_id );
update_post_meta( (int) $smoke_attachment_id, '_wp_attached_file', '2026/06/magick-ai-abilities-smoke-media-asset.jpg' );
update_post_meta(
	(int) $smoke_attachment_id,
	'_wp_attachment_metadata',
	array(
		'width'    => 2600,
		'height'   => 1400,
		'file'     => '2026/06/magick-ai-abilities-smoke-media-asset.jpg',
		'filesize' => 900000,
	)
);

$media_health_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/get-media-inventory-health/run' );
$media_health_run_request->set_query_params(
	array(
		'input' => array(
			'mime_type' => 'image',
			'per_page'  => 10,
		),
	)
);
$media_health_run_response = rest_do_request( $media_health_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $media_health_run_response->get_status(), 'Authenticated media inventory health ability run returns 200.' );

$media_inspect_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/inspect-media-asset/run' );
$media_inspect_run_request->set_query_params(
	array(
		'input' => array(
			'attachment_id' => (int) $smoke_attachment_id,
		),
	)
);
$media_inspect_run_response = rest_do_request( $media_inspect_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $media_inspect_run_response->get_status(), 'Authenticated media asset inspection ability run returns 200.' );
$media_inspect_run_data = $media_inspect_run_response->get_data();
magick_ai_abilities_smoke_assert( true === ( $media_inspect_run_data['meta']['readonly'] ?? null ), 'Media asset inspection remains read-only.' );

$media_optimize_run_request = new WP_REST_Request( 'POST', '/wp-abilities/v1/abilities/magick-ai/optimize-media-asset/run' );
$media_optimize_run_request->set_header( 'Content-Type', 'application/json' );
$media_optimize_run_request->set_body(
	wp_json_encode(
		array(
			'input' => array(
				'attachment_id' => (int) $smoke_attachment_id,
				'dry_run'       => true,
			),
		)
	)
);
$media_optimize_run_response = rest_do_request( $media_optimize_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $media_optimize_run_response->get_status(), 'Authenticated media asset optimization dry-run ability run returns 200.' );
$media_optimize_run_data = $media_optimize_run_response->get_data();
magick_ai_abilities_smoke_assert( true === ( $media_optimize_run_data['dry_run'] ?? null ), 'Media asset optimization defaults to governed dry-run.' );
magick_ai_abilities_smoke_assert( false === ( $media_optimize_run_data['replace_original'] ?? null ), 'Media asset optimization dry-run does not replace originals.' );
update_post_meta(
	(int) $smoke_attachment_id,
	'_magick_ai_media_optimized_derivatives',
	array(
		array(
			'format'           => 'webp',
			'mime_type'        => 'image/webp',
			'file_basename'    => 'magick-ai-abilities-smoke-media-asset-optimized.webp',
			'relative_file'    => '2026/06/magick-ai-abilities-smoke-media-asset-optimized.webp',
			'url'              => content_url( 'uploads/2026/06/magick-ai-abilities-smoke-media-asset-optimized.webp' ),
			'width'            => 1920,
			'height'           => 1034,
			'quality'          => 82,
			'filesize_bytes'   => 300000,
			'generated_at_gmt' => gmdate( 'c' ),
		),
	)
);
$media_replace_run_request = new WP_REST_Request( 'POST', '/wp-abilities/v1/abilities/magick-ai/replace-media-file/run' );
$media_replace_run_request->set_header( 'Content-Type', 'application/json' );
$media_replace_run_request->set_body(
	wp_json_encode(
		array(
			'input' => array(
				'attachment_id'                 => (int) $smoke_attachment_id,
				'derivative_relative_file'      => '2026/06/magick-ai-abilities-smoke-media-asset-optimized.webp',
				'expected_current_relative_file' => '2026/06/magick-ai-abilities-smoke-media-asset.jpg',
				'dry_run'                       => true,
			),
		)
	)
);
$media_replace_run_response = rest_do_request( $media_replace_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $media_replace_run_response->get_status(), 'Authenticated media file replacement dry-run ability run returns 200.' );
$media_replace_run_data = $media_replace_run_response->get_data();
magick_ai_abilities_smoke_assert( true === ( $media_replace_run_data['dry_run'] ?? null ), 'Media file replacement defaults to governed dry-run.' );
magick_ai_abilities_smoke_assert( true === ( $media_replace_run_data['original_preserved'] ?? null ), 'Media file replacement plans a preserved backup.' );

$media_cleanup_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/get-media-cleanup-opportunities/run' );
$media_cleanup_run_request->set_query_params(
	array(
		'input' => array(
			'mime_type' => 'image',
			'per_page'  => 10,
		),
	)
);
$media_cleanup_run_response = rest_do_request( $media_cleanup_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $media_cleanup_run_response->get_status(), 'Authenticated media cleanup opportunities ability run returns 200.' );

$media_fix_plan_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/build-media-inventory-fix-plan/run' );
$media_fix_plan_run_request->set_query_params(
	array(
		'input' => array(
			'attachment_ids'  => array( (int) $smoke_attachment_id ),
			'article_title'   => $magick_ai_abilities_smoke_pattern,
			'article_excerpt' => 'Smoke media metadata context.',
			'focus_keyword'   => 'ability workflow',
			'max_actions'     => 5,
		),
	)
);
$media_fix_plan_run_response = rest_do_request( $media_fix_plan_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $media_fix_plan_run_response->get_status(), 'Authenticated media inventory fix plan ability run returns 200.' );
$media_fix_plan_run_data = $media_fix_plan_run_response->get_data();
magick_ai_abilities_smoke_assert( false === ( $media_fix_plan_run_data['data']['commit_execution'] ?? null ), 'Media inventory fix plan does not execute commits.' );

$seo_geo_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/get-post-seo-geo-readiness/run' );
$seo_geo_run_request->set_query_params(
	array(
		'input' => array(
			'post_id'       => (int) $smoke_post_id,
			'focus_keyword' => 'ability workflow',
		),
	)
);
$seo_geo_run_response = rest_do_request( $seo_geo_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $seo_geo_run_response->get_status(), 'Authenticated post SEO/GEO readiness ability run returns 200.' );

$topic_coverage_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/get-site-topic-coverage-report/run' );
$topic_coverage_run_request->set_query_params(
	array(
		'input' => array(
			'post_type'  => 'post',
			'status'     => 'any',
			'per_page'   => 10,
			'topic_seed' => 'ability workflow',
		),
	)
);
$topic_coverage_run_response = rest_do_request( $topic_coverage_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $topic_coverage_run_response->get_status(), 'Authenticated site topic coverage ability run returns 200.' );

$taxonomy_health_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/get-taxonomy-inventory-health/run' );
$taxonomy_health_run_request->set_query_params(
	array(
		'input' => array(
			'taxonomy' => 'category',
			'per_page' => 10,
		),
	)
);
$taxonomy_health_run_response = rest_do_request( $taxonomy_health_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $taxonomy_health_run_response->get_status(), 'Authenticated taxonomy inventory health ability run returns 200.' );

$taxonomy_consolidation_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/get-taxonomy-consolidation-suggestions/run' );
$taxonomy_consolidation_run_request->set_query_params(
	array(
		'input' => array(
			'taxonomy' => 'post_tag',
			'per_page' => 10,
		),
	)
);
$taxonomy_consolidation_run_response = rest_do_request( $taxonomy_consolidation_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $taxonomy_consolidation_run_response->get_status(), 'Authenticated taxonomy consolidation suggestions ability run returns 200.' );

$smoke_tag_result = function_exists( 'wp_insert_term' )
	? wp_insert_term( 'Ability Workflow Smoke ' . $magick_ai_abilities_smoke_run_id, 'post_tag' )
	: new WP_Error( 'missing_wp_insert_term', 'wp_insert_term unavailable.' );
$smoke_tag_created = ! is_wp_error( $smoke_tag_result );
if ( is_wp_error( $smoke_tag_result ) && 'term_exists' === (string) $smoke_tag_result->get_error_code() ) {
	$smoke_tag_error_data = $smoke_tag_result->get_error_data();
	$smoke_tag_id = (int) ( is_array( $smoke_tag_error_data ) ? ( $smoke_tag_error_data['term_id'] ?? 0 ) : $smoke_tag_error_data );
} else {
	$smoke_tag_id = (int) ( is_array( $smoke_tag_result ) ? ( $smoke_tag_result['term_id'] ?? 0 ) : 0 );
}
magick_ai_abilities_smoke_assert( $smoke_tag_id > 0, 'Temporary smoke tag is available for taxonomy proposal ability runs.' );
if ( ! empty( $smoke_tag_created ) ) {
	magick_ai_abilities_smoke_register_term_fixture( (int) $smoke_tag_id, 'post_tag' );
}

$post_taxonomy_proposal_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/propose-post-taxonomy-terms/run' );
$post_taxonomy_proposal_run_request->set_query_params(
	array(
		'input' => array(
			'post_id'            => (int) $smoke_post_id,
			'taxonomy'           => 'post_tag',
			'mode'               => 'append',
			'candidate_term_ids' => array( $smoke_tag_id ),
			'candidate_terms'    => array( 'Unmatched Smoke Topic' ),
		),
	)
);
$post_taxonomy_proposal_run_response = rest_do_request( $post_taxonomy_proposal_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $post_taxonomy_proposal_run_response->get_status(), 'Authenticated post taxonomy terms proposal ability run returns 200.' );
$post_taxonomy_proposal_run_data = $post_taxonomy_proposal_run_response->get_data();
magick_ai_abilities_smoke_assert( 'magick-ai/set-post-terms' === (string) ( $post_taxonomy_proposal_run_data['data']['proposal']['target_ability_id'] ?? '' ), 'Post taxonomy terms proposal targets set-post-terms.' );
magick_ai_abilities_smoke_assert( false === ( $post_taxonomy_proposal_run_data['data']['proposal']['commit_execution'] ?? null ), 'Post taxonomy terms proposal does not execute commits.' );

$revision_risk_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/get-revision-change-risk-report/run' );
$revision_risk_run_request->set_query_params(
	array(
		'input' => array(
			'post_id'       => (int) $smoke_post_id,
			'max_revisions' => 5,
		),
	)
);
$revision_risk_run_response = rest_do_request( $revision_risk_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $revision_risk_run_response->get_status(), 'Authenticated revision change risk ability run returns 200.' );

$smoke_page_id = wp_insert_post(
	array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => $magick_ai_abilities_smoke_pattern . ' Structure Page',
		'post_content' => '<p>This local smoke page verifies page structure health.</p>',
	),
	true
);
magick_ai_abilities_smoke_assert( ! is_wp_error( $smoke_page_id ) && (int) $smoke_page_id > 0, 'Temporary smoke page is available for page structure ability runs.' );
magick_ai_abilities_smoke_register_post_fixture( (int) $smoke_page_id );

$page_structure_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/get-page-structure-health/run' );
$page_structure_run_request->set_query_params(
	array(
		'input' => array(
			'page_id' => (int) $smoke_page_id,
		),
	)
);
$page_structure_run_response = rest_do_request( $page_structure_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $page_structure_run_response->get_status(), 'Authenticated page structure health ability run returns 200.' );

$seo_geo_gap_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/get-seo-geo-gap-report/run' );
$seo_geo_gap_run_request->set_query_params(
	array(
		'input' => array(
			'post_type'  => 'post',
			'status'     => 'any',
			'per_page'   => 10,
			'topic_seed' => 'ability workflow',
		),
	)
);
$seo_geo_gap_run_response = rest_do_request( $seo_geo_gap_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $seo_geo_gap_run_response->get_status(), 'Authenticated SEO/GEO gap report ability run returns 200.' );
$seo_geo_gap_run_data = $seo_geo_gap_run_response->get_data();

$site_style_baseline_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/get-site-style-baseline/run' );
$site_style_baseline_run_request->set_query_params(
	array(
		'input' => array(
			'mode'  => 'site_recent',
			'limit' => 3,
		),
	)
);
$site_style_baseline_run_response = rest_do_request( $site_style_baseline_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $site_style_baseline_run_response->get_status(), 'Authenticated site style baseline ability run returns 200.' );
$site_style_baseline_run_data = $site_style_baseline_run_response->get_data();

$article_workflow_context_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/build-article-workflow-context/run' );
$article_workflow_context_run_request->set_query_params(
	array(
		'input' => array(
			'workflow'   => 'publish',
			'post_id'    => (int) $smoke_post_id,
			'topic_seed' => 'ability workflow',
		),
	)
);
$article_workflow_context_run_response = rest_do_request( $article_workflow_context_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $article_workflow_context_run_response->get_status(), 'Authenticated article workflow context ability run returns 200.' );
$article_workflow_context_run_data = $article_workflow_context_run_response->get_data();

$publishing_calendar_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/get-publishing-calendar-context/run' );
$publishing_calendar_run_request->set_query_params(
	array(
		'input' => array(
			'post_type'   => 'post',
			'window_days' => 30,
			'per_page'    => 10,
		),
	)
);
$publishing_calendar_run_response = rest_do_request( $publishing_calendar_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $publishing_calendar_run_response->get_status(), 'Authenticated publishing calendar context ability run returns 200.' );
$publishing_calendar_run_data = $publishing_calendar_run_response->get_data();

$smoke_comment_id = wp_insert_comment(
	array(
		'comment_post_ID'      => (int) $smoke_post_id,
		'comment_author'       => $magick_ai_abilities_smoke_pattern,
		'comment_author_email' => 'smoke@example.test',
		'comment_content'      => '@admin please check this smoke comment queue item.',
		'comment_approved'     => '0',
	)
);
magick_ai_abilities_smoke_assert( (int) $smoke_comment_id > 0, 'Temporary smoke comment is available for comment queue ability runs.' );
magick_ai_abilities_smoke_register_comment_fixture( (int) $smoke_comment_id );

$comment_queue_health_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/get-comment-queue-health/run' );
$comment_queue_health_run_request->set_query_params(
	array(
		'input' => array(
			'post_id'  => (int) $smoke_post_id,
			'status'   => 'hold',
			'per_page' => 10,
		),
	)
);
$comment_queue_health_run_response = rest_do_request( $comment_queue_health_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $comment_queue_health_run_response->get_status(), 'Authenticated comment queue health ability run returns 200.' );
$comment_queue_health_run_data = $comment_queue_health_run_response->get_data();

$comment_action_queue_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/get-comment-action-priority-queue/run' );
$comment_action_queue_run_request->set_query_params(
	array(
		'input' => array(
			'post_id'  => (int) $smoke_post_id,
			'status'   => 'hold',
			'per_page' => 10,
		),
	)
);
$comment_action_queue_run_response = rest_do_request( $comment_action_queue_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $comment_action_queue_run_response->get_status(), 'Authenticated comment action priority queue ability run returns 200.' );
$comment_action_queue_run_data = $comment_action_queue_run_response->get_data();

$comment_compliance_handoff_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/get-comment-compliance-handoff/run' );
$comment_compliance_handoff_run_request->set_query_params(
	array(
		'input' => array(
			'post_id'             => (int) $smoke_post_id,
			'status'              => 'hold',
			'per_page'            => 10,
			'selected_comment_id' => (int) $smoke_comment_id,
		),
	)
);
$comment_compliance_handoff_run_response = rest_do_request( $comment_compliance_handoff_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $comment_compliance_handoff_run_response->get_status(), 'Authenticated comment compliance handoff ability run returns 200.' );
$comment_compliance_handoff_run_data = $comment_compliance_handoff_run_response->get_data();

$comment_moderation_suggest_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/build-comment-moderation-suggest/run' );
$comment_moderation_suggest_run_request->set_query_params(
	array(
		'input' => array(
			'comment_id'      => (int) $smoke_comment_id,
			'mode'            => 'suggest',
			'allowed_actions' => array( 'approve', 'reply', 'escalate', 'spam', 'trash' ),
		),
	)
);
$comment_moderation_suggest_run_response = rest_do_request( $comment_moderation_suggest_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $comment_moderation_suggest_run_response->get_status(), 'Authenticated comment moderation suggestion ability run returns 200.' );
$comment_moderation_suggest_run_data = $comment_moderation_suggest_run_response->get_data();

$comment_mention_suggest_run_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/build-comment-mention-reply-suggest/run' );
$comment_mention_suggest_run_request->set_query_params(
	array(
		'input' => array(
			'comment_id'   => (int) $smoke_comment_id,
			'trigger_type' => 'mention',
		),
	)
);
$comment_mention_suggest_run_response = rest_do_request( $comment_mention_suggest_run_request );
magick_ai_abilities_smoke_assert( 200 === (int) $comment_mention_suggest_run_response->get_status(), 'Authenticated comment mention reply suggestion ability run returns 200.' );
$comment_mention_suggest_run_data = $comment_mention_suggest_run_response->get_data();

magick_ai_abilities_smoke_assert(
	true === ( $post_context_run_data['success'] ?? null )
	&& true === ( $publishing_checklist_run_data['success'] ?? null )
	&& true === ( $publish_risk_run_data['success'] ?? null )
	&& true === ( $article_publish_preflight_run_data['success'] ?? null )
	&& true === ( $article_workflow_context_run_data['success'] ?? null )
	&& true === ( $publishing_calendar_run_data['success'] ?? null ),
	'Publishing preflight workflow returns success envelopes across context, checklist, risk, preflight bundle, workflow context, and calendar.'
);
magick_ai_abilities_smoke_assert(
	in_array( 'publish_risk', (array) ( $article_workflow_context_run_data['data']['sections'] ?? array() ), true ),
	'Publishing preflight workflow context includes publish risk section.'
);
magick_ai_abilities_smoke_assert(
	true === ( $refresh_opportunities_run_data['success'] ?? null )
	&& true === ( $seo_geo_gap_run_data['success'] ?? null )
	&& true === ( $site_style_baseline_run_data['success'] ?? null )
	&& true === ( $link_graph_run_data['success'] ?? null )
	&& true === ( $internal_link_run_data['success'] ?? null )
	&& true === ( $old_article_refresh_run_data['success'] ?? null ),
	'Content refresh workflow returns success envelopes across refresh, SEO/GEO gap, style, link graph, link opportunity, and refresh bundle context.'
);
magick_ai_abilities_smoke_assert(
	true === ( $comment_queue_health_run_data['success'] ?? null )
	&& true === ( $comment_action_queue_run_data['success'] ?? null )
	&& true === ( $comment_compliance_handoff_run_data['success'] ?? null )
	&& true === ( $comment_moderation_suggest_run_data['success'] ?? null )
	&& true === ( $comment_mention_suggest_run_data['success'] ?? null ),
	'Comment compliance workflow returns success envelopes across queue, priority, handoff bundle, moderation suggestion, and reply handoff.'
);
magick_ai_abilities_smoke_assert(
	true === ( $comment_mention_suggest_run_data['data']['trigger']['trigger_detected'] ?? null ),
	'Comment compliance workflow detects the smoke mention reply trigger.'
);

magick_ai_abilities_smoke_cleanup_fixtures();
magick_ai_abilities_smoke_assert( null === get_comment( (int) $smoke_comment_id ), 'Smoke comment fixture is deleted after smoke.' );
magick_ai_abilities_smoke_assert( false === get_post_type( (int) $smoke_page_id ), 'Smoke page fixture is deleted after smoke.' );
magick_ai_abilities_smoke_assert( false === get_post_type( (int) $smoke_attachment_id ), 'Smoke media fixture is deleted after smoke.' );
magick_ai_abilities_smoke_assert( false === get_post_type( (int) $smoke_candidate_id ), 'Smoke candidate post fixture is deleted after smoke.' );
magick_ai_abilities_smoke_assert( false === get_post_type( (int) $smoke_post_id ), 'Smoke context post fixture is deleted after smoke.' );
if ( ! empty( $smoke_tag_created ) ) {
	magick_ai_abilities_smoke_assert( 0 === (int) term_exists( (int) $smoke_tag_id, 'post_tag' ), 'Smoke tag fixture is deleted after smoke.' );
}

wp_set_current_user( 0 );
$anonymous_response = rest_do_request( new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities' ) );
magick_ai_abilities_smoke_assert(
	in_array( (int) $anonymous_response->get_status(), array( 401, 403 ), true ),
	'Anonymous abilities REST catalog request is blocked as expected.'
);

echo 'Smoke OK: ' . (int) $GLOBALS['magick_ai_abilities_smoke_assertions'] . " assertions\n";
