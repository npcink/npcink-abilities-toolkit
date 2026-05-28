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

wp_set_current_user( 0 );
$anonymous_response = rest_do_request( new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities' ) );
magick_ai_abilities_smoke_assert(
	in_array( (int) $anonymous_response->get_status(), array( 401, 403 ), true ),
	'Anonymous abilities REST catalog request is blocked as expected.'
);

echo 'Smoke OK: ' . (int) $GLOBALS['magick_ai_abilities_smoke_assertions'] . " assertions\n";
