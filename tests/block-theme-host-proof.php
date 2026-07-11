<?php
/**
 * Real-host, no-mutation proof for block-theme planning abilities.
 *
 * Run through scripts/block-theme-host-proof.sh.
 *
 * @package NpcinkAbilitiesToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "This proof must run inside WordPress through WP-CLI.\n" );
	exit( 1 );
}

$GLOBALS['npcink_abilities_toolkit_block_theme_proof_assertions'] = 0;

/**
 * Asserts one proof condition.
 *
 * @param bool   $condition Assertion result.
 * @param string $message Assertion message.
 * @return void
 */
function npcink_abilities_toolkit_block_theme_proof_assert( $condition, $message ) {
	++$GLOBALS['npcink_abilities_toolkit_block_theme_proof_assertions'];
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
	echo "[ok] {$message}\n";
}

/**
 * Returns a stable fingerprint of host surfaces that this proof must not mutate.
 *
 * @return string
 */
function npcink_abilities_toolkit_block_theme_proof_fingerprint() {
	global $wpdb;

	$posts = $wpdb->get_results(
		"SELECT ID, post_author, post_date_gmt, post_modified_gmt, post_content, post_title, post_excerpt, post_status, post_name, post_parent, post_type
		FROM {$wpdb->posts}
		WHERE post_type IN ('page', 'wp_template', 'wp_template_part')
		ORDER BY ID ASC",
		ARRAY_A
	);
	$postmeta = $wpdb->get_results(
		"SELECT pm.meta_id, pm.post_id, pm.meta_key, pm.meta_value
		FROM {$wpdb->postmeta} pm
		INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		WHERE p.post_type IN ('page', 'wp_template', 'wp_template_part')
		ORDER BY pm.meta_id ASC",
		ARRAY_A
	);
	$options = array(
		'stylesheet'     => get_option( 'stylesheet' ),
		'template'       => get_option( 'template' ),
		'show_on_front'  => get_option( 'show_on_front' ),
		'page_on_front'  => get_option( 'page_on_front' ),
		'page_for_posts' => get_option( 'page_for_posts' ),
	);

	return hash( 'sha256', (string) wp_json_encode( array( 'posts' => $posts, 'postmeta' => $postmeta, 'options' => $options ) ) );
}

/**
 * Runs one ability through the authenticated WordPress Abilities REST surface.
 *
 * @param string              $ability_id Ability id.
 * @param array<string,mixed> $input Ability input.
 * @return array<string,mixed>
 */
function npcink_abilities_toolkit_block_theme_proof_run( $ability_id, array $input ) {
	$request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/' . $ability_id . '/run' );
	$request->set_query_params( array( 'input' => $input ) );
	$response = rest_do_request( $request );
	npcink_abilities_toolkit_block_theme_proof_assert( 200 === (int) $response->get_status(), $ability_id . ' returns HTTP 200.' );
	$data = $response->get_data();
	npcink_abilities_toolkit_block_theme_proof_assert( is_array( $data ), $ability_id . ' returns an array payload.' );

	return is_array( $data ) ? $data : array();
}

/**
 * Validates governed actions and renders their proposed blocks on the real host.
 *
 * @param array<int,array<string,mixed>> $actions Plan actions.
 * @param string                         $label Proof label.
 * @return array<string,mixed>
 */
function npcink_abilities_toolkit_block_theme_proof_actions( array $actions, $label ) {
	npcink_abilities_toolkit_block_theme_proof_assert( ! empty( $actions ), $label . ' emits reviewable write targets.' );
	$target_ids     = array();
	$rendered_count = 0;

	foreach ( $actions as $action ) {
		$input = is_array( $action['input'] ?? null ) ? $action['input'] : array();
		npcink_abilities_toolkit_block_theme_proof_assert( true === (bool) ( $action['requires_approval'] ?? false ), $label . ' action requires approval.' );
		npcink_abilities_toolkit_block_theme_proof_assert( false === (bool) ( $action['commit_execution'] ?? true ), $label . ' action does not execute a commit.' );
		npcink_abilities_toolkit_block_theme_proof_assert( true === (bool) ( $input['dry_run'] ?? false ), $label . ' action stays dry-run.' );
		npcink_abilities_toolkit_block_theme_proof_assert( false === (bool) ( $input['commit'] ?? true ), $label . ' action keeps commit disabled.' );
		$target_ids[] = (string) ( $action['target_ability_id'] ?? '' );

		$blocks = is_array( $input['blocks'] ?? null ) ? $input['blocks'] : array();
		if ( empty( $blocks ) ) {
			continue;
		}
		$markup   = serialize_blocks( $blocks );
		$reparsed = parse_blocks( $markup );
		$rendered = do_blocks( $markup );
		npcink_abilities_toolkit_block_theme_proof_assert( count( $blocks ) === count( $reparsed ), $label . ' blocks survive a real Core parse round trip.' );
		npcink_abilities_toolkit_block_theme_proof_assert( '' !== trim( $rendered ), $label . ' blocks render on the active host.' );
		++$rendered_count;
	}

	return array(
		'action_count'          => count( $actions ),
		'target_ability_ids'    => array_values( array_unique( array_filter( $target_ids ) ) ),
		'rendered_action_count' => $rendered_count,
	);
}

/**
 * Reads, round-trips, and renders one existing block-editor host surface.
 *
 * @param string              $ability_id Read ability id.
 * @param array<string,mixed> $input Ability input.
 * @param string              $label Proof label.
 * @return array<string,mixed>
 */
function npcink_abilities_toolkit_block_theme_proof_read_surface( $ability_id, array $input, $label ) {
	$data   = npcink_abilities_toolkit_block_theme_proof_run( $ability_id, $input );
	$blocks = is_array( $data['blocks'] ?? null ) ? $data['blocks'] : array();
	npcink_abilities_toolkit_block_theme_proof_assert( ! empty( $blocks ), $label . ' returns real host blocks.' );
	$markup   = serialize_blocks( $blocks );
	$reparsed = parse_blocks( $markup );
	$rendered = do_blocks( $markup );
	npcink_abilities_toolkit_block_theme_proof_assert( count( $blocks ) === count( $reparsed ), $label . ' survives a real Core parse round trip.' );
	npcink_abilities_toolkit_block_theme_proof_assert( '' !== trim( $rendered ), $label . ' renders on the active host.' );

	return array(
		'ability_id'  => $ability_id,
		'slug'        => (string) ( $data['slug'] ?? '' ),
		'source'      => (string) ( $data['source'] ?? '' ),
		'block_count' => (int) ( $data['block_count'] ?? count( $blocks ) ),
	);
}

if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
	fwrite( STDERR, "FAIL: The active theme is not a block theme.\n" );
	exit( 1 );
}

$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
npcink_abilities_toolkit_block_theme_proof_assert( ! empty( $admins ), 'A real administrator is available for authenticated REST proof calls.' );
wp_set_current_user( (int) reset( $admins ) );

$theme              = wp_get_theme();
$fingerprint_before = npcink_abilities_toolkit_block_theme_proof_fingerprint();
$ability_ids        = array(
	'npcink-abilities-toolkit/route-content-intent',
	'npcink-abilities-toolkit/build-pattern-page-plan',
	'npcink-abilities-toolkit/build-block-theme-site-plan',
	'npcink-abilities-toolkit/get-template-blocks',
	'npcink-abilities-toolkit/get-template-part-blocks',
);

foreach ( $ability_ids as $ability_id ) {
	$detail = rest_do_request( new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/' . $ability_id ) );
	npcink_abilities_toolkit_block_theme_proof_assert( 200 === (int) $detail->get_status(), 'WordPress Abilities API discovers ' . $ability_id . '.' );
}

$template_readback = npcink_abilities_toolkit_block_theme_proof_read_surface(
	'npcink-abilities-toolkit/get-template-blocks',
	array( 'slug' => 'single' ),
	'Real single template'
);
$template_part_readback = npcink_abilities_toolkit_block_theme_proof_read_surface(
	'npcink-abilities-toolkit/get-template-part-blocks',
	array( 'slug' => 'header' ),
	'Real header template part'
);

$pattern_route = npcink_abilities_toolkit_block_theme_proof_run(
	'npcink-abilities-toolkit/route-content-intent',
	array( 'prompt' => 'Build a modern landing page with native responsive Gutenberg blocks.' )
);
npcink_abilities_toolkit_block_theme_proof_assert( 'pattern_page_plan' === (string) ( $pattern_route['data']['route']['route'] ?? '' ), 'Real host routes landing-page intent to the pattern page plan.' );
npcink_abilities_toolkit_block_theme_proof_assert( false === (bool) ( $pattern_route['data']['prompt_is_authorization'] ?? true ), 'Landing-page prompt is not authorization.' );

$pattern_plan = npcink_abilities_toolkit_block_theme_proof_run(
	'npcink-abilities-toolkit/build-pattern-page-plan',
	array(
		'title'          => 'Real Host Pattern Proof',
		'pattern_id'     => 'openai-style-landing',
		'style_preset'   => 'minimal-dark-light',
		'media_strategy' => 'mock_or_existing_media',
		'variables'      => array(
			'eyebrow'          => 'Host proof',
			'hero_title'       => 'Gutenberg-native planning on a real WordPress host',
			'hero_description' => 'This plan is parsed and rendered without creating a page.',
		),
	)
);
npcink_abilities_toolkit_block_theme_proof_assert( 'pattern_page_plan' === (string) ( $pattern_plan['data']['artifact_type'] ?? '' ), 'Pattern plan keeps its artifact type.' );
$pattern_evidence = npcink_abilities_toolkit_block_theme_proof_actions( (array) ( $pattern_plan['data']['write_actions'] ?? array() ), 'Pattern page plan' );

$theme_route = npcink_abilities_toolkit_block_theme_proof_run(
	'npcink-abilities-toolkit/route-content-intent',
	array( 'prompt' => '把首页改造成一个基础落地页：顶部有清晰的大标题和简短介绍，下面有一个行动按钮，再下面展示最新文章和分类入口。不要改导航，不要改 global styles，不要写 theme.json，不要写主题文件，不要输出 raw template HTML，只通过块主题模板 proposal 来处理。' )
);
npcink_abilities_toolkit_block_theme_proof_assert( 'block_theme_site_plan' === (string) ( $theme_route['data']['route']['route'] ?? '' ), 'Real host routes bounded template intent to the block theme site plan.' );

$theme_plan = npcink_abilities_toolkit_block_theme_proof_run(
	'npcink-abilities-toolkit/build-block-theme-site-plan',
	array(
		'intent'           => 'customize_template_layout',
		'target_templates' => array( 'single' ),
		'layout_profile'   => 'article_standard',
	)
);
npcink_abilities_toolkit_block_theme_proof_assert( 'block_theme_site_plan' === (string) ( $theme_plan['data']['artifact_type'] ?? '' ), 'Block theme plan keeps its artifact type.' );
npcink_abilities_toolkit_block_theme_proof_assert( true === (bool) ( $theme_plan['data']['block_editor_quality_gate']['ready_for_proposal'] ?? false ), 'Real-host template plan passes its proposal quality gate.' );
$theme_evidence = npcink_abilities_toolkit_block_theme_proof_actions( (array) ( $theme_plan['data']['write_actions'] ?? array() ), 'Block theme site plan' );

$fingerprint_after = npcink_abilities_toolkit_block_theme_proof_fingerprint();
npcink_abilities_toolkit_block_theme_proof_assert( $fingerprint_before === $fingerprint_after, 'Pages, templates, template parts, and reading settings are unchanged after the proof.' );

$artifact = array(
	'status'             => 'pass',
	'generated_at'       => gmdate( 'c' ),
	'wordpress_version'  => get_bloginfo( 'version' ),
	'theme'              => array(
		'name'        => $theme->get( 'Name' ),
		'stylesheet'  => $theme->get_stylesheet(),
		'version'     => $theme->get( 'Version' ),
		'block_theme' => true,
	),
	'ability_ids'        => $ability_ids,
	'template_readback'  => $template_readback,
	'template_part_readback' => $template_part_readback,
	'pattern_plan'       => $pattern_evidence,
	'block_theme_plan'   => $theme_evidence,
	'fingerprint_before' => $fingerprint_before,
	'fingerprint_after'  => $fingerprint_after,
	'no_mutation'        => true,
	'assertions'         => $GLOBALS['npcink_abilities_toolkit_block_theme_proof_assertions'],
);
$artifact_path = (string) getenv( 'BLOCK_THEME_HOST_PROOF_ARTIFACT' );
if ( '' !== $artifact_path ) {
	$written = file_put_contents( $artifact_path, (string) wp_json_encode( $artifact, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n" );
	if ( false === $written ) {
		fwrite( STDERR, "FAIL: Unable to write the block-theme host proof artifact.\n" );
		exit( 1 );
	}
}

echo 'Block-theme real host proof OK: ' . (int) $artifact['assertions'] . " assertions\n";
