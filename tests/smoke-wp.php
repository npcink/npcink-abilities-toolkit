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

magick_ai_abilities_smoke_assert( function_exists( 'wp_register_ability' ), 'WordPress Abilities API registration function exists.' );
magick_ai_abilities_smoke_assert( function_exists( 'wp_register_ability_category' ), 'WordPress Abilities API category registration function exists.' );
magick_ai_abilities_smoke_assert( function_exists( 'magick_ai_abilities_register_readonly' ), 'Plugin public readonly registration helper is loaded.' );

update_option( 'magick_ai_abilities_demo_enabled', '1' );

if ( function_exists( 'wp_get_abilities' ) ) {
	wp_get_abilities();
}

magick_ai_abilities_smoke_assert(
	! function_exists( 'wp_has_ability' ) || wp_has_ability( 'magick-ai-abilities/site-summary' ),
	'Demo site-summary ability is registered.'
);

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

$abilities_request  = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities' );
$abilities_response = rest_do_request( $abilities_request );
magick_ai_abilities_smoke_assert( 200 === (int) $abilities_response->get_status(), 'Authenticated abilities REST catalog returns 200.' );

$provider_abilities_request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities' );
$provider_abilities_request->set_param( 'namespace', 'magick-ai-abilities' );
$provider_abilities_request->set_param( 'per_page', 100 );
$provider_abilities_response = rest_do_request( $provider_abilities_request );
magick_ai_abilities_smoke_assert( 200 === (int) $provider_abilities_response->get_status(), 'Authenticated provider namespace REST catalog returns 200.' );

$provider_abilities_data = $provider_abilities_response->get_data();
magick_ai_abilities_smoke_assert(
	magick_ai_abilities_smoke_has_ability_name( $provider_abilities_data, 'magick-ai-abilities/site-summary' ),
	'Authenticated abilities REST catalog contains the demo ability.'
);
magick_ai_abilities_smoke_assert(
	magick_ai_abilities_smoke_has_ability_name( $provider_abilities_data, 'magick-ai-abilities/wp-diagnostics-summary' ),
	'Authenticated abilities REST catalog contains the standalone diagnostics ability.'
);
foreach (
	array(
		'magick-ai/site-info',
		'magick-ai/list-post-types',
		'magick-ai/list-taxonomies',
		'magick-ai/count-posts',
		'magick-ai/list-pages-tree',
		'magick-ai/list-posts',
		'magick-ai/get-post',
		'magick-ai/get-post-context',
		'magick-ai/get-content-publishing-checklist',
		'magick-ai/get-content-inventory-health',
		'magick-ai/get-bulk-publishing-checklist',
		'magick-ai/get-internal-link-opportunity-report',
		'magick-ai/get-site-operations-dashboard',
		'magick-ai/get-post-publish-risk-report',
		'magick-ai/get-content-refresh-opportunities',
		'magick-ai/get-internal-link-graph-health',
		'magick-ai/get-media-cleanup-opportunities',
		'magick-ai/get-taxonomy-consolidation-suggestions',
		'magick-ai/get-page-structure-health',
		'magick-ai/get-seo-geo-gap-report',
		'magick-ai/get-site-style-baseline',
		'magick-ai/build-article-workflow-context',
		'magick-ai/get-publishing-calendar-context',
		'magick-ai/get-media-inventory-health',
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
			'magick-ai/compose-comment-mention-reply-result',
			'magick-ai/build-comment-moderation-batch-suggest',
			'magick-ai/compose-comment-moderation-batch-result',
			'magick-ai/list-menus',
			'magick-ai/get-menu',
			'magick-ai/search-posts',
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

$smoke_post_id = wp_insert_post(
	array(
		'post_type'    => 'post',
		'post_status'  => 'draft',
		'post_title'   => 'Magick AI Abilities Smoke Context Post',
		'post_content' => '<!-- wp:paragraph --><p>This local smoke post verifies the post context and publishing checklist abilities.</p><!-- /wp:paragraph -->',
		'post_excerpt' => 'Smoke context post.',
	),
	true
);
magick_ai_abilities_smoke_assert( ! is_wp_error( $smoke_post_id ) && (int) $smoke_post_id > 0, 'Temporary smoke post is available for post-context ability runs.' );

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

$smoke_candidate_id = wp_insert_post(
	array(
		'post_type'    => 'post',
		'post_status'  => 'publish',
		'post_title'   => 'Magick AI Abilities Smoke Internal Link Candidate',
		'post_content' => '<p>This published smoke post provides a related ability workflow candidate for internal link opportunity checks.</p>',
	),
	true
);
magick_ai_abilities_smoke_assert( ! is_wp_error( $smoke_candidate_id ) && (int) $smoke_candidate_id > 0, 'Temporary smoke candidate post is available for internal-link ability runs.' );

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

$smoke_attachment_id = wp_insert_post(
	array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'post_title'     => 'Magick AI Abilities Smoke Media Asset',
		'post_mime_type' => 'image/jpeg',
		'post_excerpt'   => '',
		'post_content'   => '',
	),
	true
);
magick_ai_abilities_smoke_assert( ! is_wp_error( $smoke_attachment_id ) && (int) $smoke_attachment_id > 0, 'Temporary smoke media asset is available for media inventory ability runs.' );

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
		'post_title'   => 'Magick AI Abilities Smoke Structure Page',
		'post_content' => '<p>This local smoke page verifies page structure health.</p>',
	),
	true
);
magick_ai_abilities_smoke_assert( ! is_wp_error( $smoke_page_id ) && (int) $smoke_page_id > 0, 'Temporary smoke page is available for page structure ability runs.' );

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

$smoke_comment_id = wp_insert_comment(
	array(
		'comment_post_ID'      => (int) $smoke_post_id,
		'comment_author'       => 'Magick AI Abilities Smoke',
		'comment_author_email' => 'smoke@example.test',
		'comment_content'      => '@admin please check this smoke comment queue item.',
		'comment_approved'     => '0',
	)
);
magick_ai_abilities_smoke_assert( (int) $smoke_comment_id > 0, 'Temporary smoke comment is available for comment queue ability runs.' );

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

wp_delete_comment( (int) $smoke_comment_id, true );
wp_delete_post( (int) $smoke_page_id, true );
wp_delete_post( (int) $smoke_attachment_id, true );
wp_delete_post( (int) $smoke_candidate_id, true );
wp_delete_post( (int) $smoke_post_id, true );

wp_set_current_user( 0 );
$anonymous_response = rest_do_request( new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities' ) );
magick_ai_abilities_smoke_assert(
	in_array( (int) $anonymous_response->get_status(), array( 401, 403 ), true ),
	'Anonymous abilities REST catalog request is blocked as expected.'
);

echo 'Smoke OK: ' . (int) $GLOBALS['magick_ai_abilities_smoke_assertions'] . " assertions\n";
