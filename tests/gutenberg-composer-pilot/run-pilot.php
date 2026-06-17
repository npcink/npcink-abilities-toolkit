<?php
/**
 * Run the deterministic Gutenberg composer pilot suite.
 *
 * This script is local/offline only. It uses the unit-test WordPress shims and
 * never calls WordPress, Adapter, Core, Cloud, model providers, or writes.
 *
 * @package NpcinkAbilitiesToolkit
 */

require_once dirname( __DIR__ ) . '/bootstrap.php';
require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

use Npcink_Abilities_Toolkit\Packages\Core_Read_Package;
use Npcink_Abilities_Toolkit\Registry\Ability_Registrar;
use Npcink_Abilities_Toolkit\Registry\Annotation_Normalizer;
use Npcink_Abilities_Toolkit\Registry\Category_Registrar;
use Npcink_Abilities_Toolkit\Registry\Contract_Normalizer;
use Npcink_Abilities_Toolkit\Registry\Schema_Normalizer;

$root        = dirname( __DIR__, 2 );
$script_args = array_slice( $argv ?? array(), 1 );

/**
 * Parses key=value CLI args.
 *
 * @param string[] $script_args Script args.
 * @return array<string,string>
 */
function npcink_gutenberg_composer_pilot_arg_map( array $script_args ): array {
	$parsed = array();
	foreach ( $script_args as $arg ) {
		$arg = (string) $arg;
		if ( ! str_contains( $arg, '=' ) ) {
			continue;
		}
		list( $key, $value ) = explode( '=', $arg, 2 );
		$parsed[ trim( $key ) ] = trim( $value );
	}

	return $parsed;
}

/**
 * Fails the script.
 *
 * @param string $message Failure message.
 * @return void
 */
function npcink_gutenberg_composer_pilot_fail( string $message ): void {
	fwrite( STDERR, "FAIL: {$message}\n" );
	exit( 1 );
}

/**
 * Resolves a path relative to the repository root.
 *
 * @param string $path Path.
 * @param string $root Root.
 * @return string
 */
function npcink_gutenberg_composer_pilot_path( string $path, string $root ): string {
	if ( str_starts_with( $path, '/' ) ) {
		return $path;
	}

	return $root . '/' . ltrim( $path, '/' );
}

/**
 * Returns sample block theme templates for offline template composer cases.
 *
 * @return array<int,object>
 */
function npcink_gutenberg_composer_pilot_template_posts(): array {
	return array(
		608 => (object) array(
			'ID'           => 608,
			'post_type'    => 'wp_template',
			'post_status'  => 'publish',
			'post_title'   => 'Single',
			'post_content' => '<!-- wp:template-part {"slug":"header"} /--><!-- wp:group {"tagName":"main"} --><main class="wp-block-group"><!-- wp:post-title /--><!-- wp:post-content /--></main><!-- /wp:group --><!-- wp:template-part {"slug":"footer"} /-->',
			'post_excerpt' => '',
			'post_author'  => 7,
			'post_name'    => 'single',
			'post_parent'  => 0,
		),
		609 => (object) array(
			'ID'           => 609,
			'post_type'    => 'wp_template',
			'post_status'  => 'publish',
			'post_title'   => 'Page',
			'post_content' => '<!-- wp:template-part {"slug":"header"} /--><!-- wp:group {"tagName":"main"} --><main class="wp-block-group"><!-- wp:post-title /--><!-- wp:post-content /--></main><!-- /wp:group --><!-- wp:template-part {"slug":"footer"} /-->',
			'post_excerpt' => '',
			'post_author'  => 7,
			'post_name'    => 'page',
			'post_parent'  => 0,
		),
		610 => (object) array(
			'ID'           => 610,
			'post_type'    => 'wp_template',
			'post_status'  => 'publish',
			'post_title'   => 'Front Page',
			'post_content' => '<!-- wp:template-part {"slug":"header"} /--><!-- wp:group {"tagName":"main"} --><main class="wp-block-group"><!-- wp:post-title /--><!-- wp:post-content /--></main><!-- /wp:group --><!-- wp:template-part {"slug":"footer"} /-->',
			'post_excerpt' => '',
			'post_author'  => 7,
			'post_name'    => 'front-page',
			'post_parent'  => 0,
		),
	);
}

/**
 * Builds a Core read package for local offline execution.
 *
 * @return Core_Read_Package
 */
function npcink_gutenberg_composer_pilot_core_read_package(): Core_Read_Package {
	$schema_normalizer     = new Schema_Normalizer();
	$annotation_normalizer = new Annotation_Normalizer();
	$contract_normalizer   = new Contract_Normalizer( $schema_normalizer, $annotation_normalizer );
	$package_categories    = new Category_Registrar();
	$package_registrar     = new Ability_Registrar( $package_categories, $contract_normalizer );
	$core_read_package     = new Core_Read_Package( $package_categories, $package_registrar );
	$core_read_package->boot();

	return $core_read_package;
}

/**
 * Returns deterministic pilot cases.
 *
 * @return array<int,array<string,mixed>>
 */
function npcink_gutenberg_composer_pilot_cases(): array {
	return array(
		array(
			'id'               => 'landing_modern_media',
			'prompt'           => '帮我做一个现代官网介绍页，需要配图，手机端也要好看。',
			'expected_route'   => 'pattern_page_plan',
			'expected_profile' => 'saas_landing',
		),
		array(
			'id'               => 'landing_saas_value',
			'prompt'           => '做一个 SaaS 产品首页，突出核心能力、客户价值和移动端体验。',
			'expected_route'   => 'pattern_page_plan',
			'expected_profile' => 'saas_landing',
		),
		array(
			'id'               => 'landing_editorial_accent',
			'prompt'           => '帮我做一个有色彩强调的 editorial-accent 官网落地页。',
			'expected_route'   => 'pattern_page_plan',
			'expected_profile' => 'saas_landing',
		),
		array(
			'id'               => 'landing_no_media_service',
			'prompt'           => '创建一个服务介绍页面，先不要配图，但要有清楚的功能、FAQ 和 CTA。',
			'expected_route'   => 'pattern_page_plan',
			'expected_profile' => 'saas_landing',
		),
		array(
			'id'               => 'landing_plugin_features',
			'prompt'           => '帮我搭一个 WordPress 插件功能介绍页面，结构清楚，适合客户浏览。',
			'expected_route'   => 'pattern_page_plan',
			'expected_profile' => 'saas_landing',
		),
		array(
			'id'               => 'landing_mobile_first',
			'prompt'           => '给产品做一个移动端优先的首页，标题不要挤，内容要能扫读。',
			'expected_route'   => 'pattern_page_plan',
			'expected_profile' => 'saas_landing',
		),
		array(
			'id'               => 'article_media_faq',
			'prompt'           => '写一篇介绍 Gutenberg 模块红利的文章草稿，需要配图和 FAQ。',
			'expected_route'   => 'article_block_plan',
			'expected_profile' => 'editorial_article',
		),
		array(
			'id'               => 'article_comparison',
			'prompt'           => '写一篇对比评测文章，说明普通 AI 直接写入和 proposal-first 的区别。',
			'expected_route'   => 'article_block_plan',
			'expected_profile' => 'comparison_review',
		),
		array(
			'id'               => 'article_governance',
			'prompt'           => '写一篇博客文章，解释内容编辑为什么要经过 proposal 审核。',
			'expected_route'   => 'article_block_plan',
			'expected_profile' => 'editorial_article',
		),
		array(
			'id'               => 'article_tutorial',
			'prompt'           => '写一篇教程，说明如何用 Gutenberg 块组织一篇长文。',
			'expected_route'   => 'article_block_plan',
			'expected_profile' => 'product_docs',
		),
		array(
			'id'               => 'article_product_docs',
			'prompt'           => '写一篇产品文档文章，介绍 OpenClaw 如何先生成 proposal 再执行。',
			'expected_route'   => 'article_block_plan',
			'expected_profile' => 'product_docs',
		),
		array(
			'id'               => 'template_breadcrumbs_single',
			'prompt'           => '帮我给文章模板加面包屑，保留 header footer 和正文。',
			'target_hint'      => 'site_template',
			'plan_input'       => array( 'target_templates' => array( 'single' ) ),
			'expected_route'   => 'block_theme_site_plan',
			'expected_profile' => 'block_theme_template',
		),
	);
}

/**
 * Evaluates one composer output against pilot acceptance rules.
 *
 * @param array<string,mixed> $case Case.
 * @param array<string,mixed> $result Result.
 * @return array<string,mixed>
 */
function npcink_gutenberg_composer_pilot_evaluate_case( array $case, array $result ): array {
	$data          = is_array( $result['data'] ?? null ) ? $result['data'] : array();
	$route         = is_array( $data['route'] ?? null ) ? $data['route'] : array();
	$final_review  = is_array( $data['final_review'] ?? null ) ? $data['final_review'] : array();
	$block_summary = is_array( $final_review['block_summary'] ?? null ) ? $final_review['block_summary'] : array();
	$failures      = array();

	if ( empty( $result['success'] ) ) {
		$failures[] = 'result_not_success';
	}
	if ( 'gutenberg_composer_repair_loop' !== (string) ( $data['artifact_type'] ?? '' ) ) {
		$failures[] = 'artifact_type_mismatch';
	}
	if ( empty( $route['supported'] ) ) {
		$failures[] = 'route_not_supported';
	}
	if ( (string) ( $case['expected_route'] ?? '' ) !== (string) ( $route['route'] ?? '' ) ) {
		$failures[] = 'route_mismatch';
	}
	if ( (string) ( $case['expected_profile'] ?? '' ) !== (string) ( $data['composer_profile_id'] ?? '' ) ) {
		$failures[] = 'composer_profile_mismatch';
	}
	if ( 'npcink-abilities-toolkit/get-gutenberg-block-capability-catalog' !== (string) ( $data['block_capability_catalog_ability_id'] ?? '' ) ) {
		$failures[] = 'catalog_ability_missing';
	}
	if ( 'gutenberg_native_v1' !== (string) ( $data['block_capability_catalog_id'] ?? '' ) ) {
		$failures[] = 'catalog_id_mismatch';
	}
	if ( 'gutenberg_native_block_composer_v1' !== (string) ( $data['composer_instruction']['instruction_id'] ?? '' ) ) {
		$failures[] = 'composer_instruction_missing';
	}
	if ( empty( $data['recommended_composer_flow'] ) || ! is_array( $data['recommended_composer_flow'] ) ) {
		$failures[] = 'recommended_composer_flow_missing';
	}
	if ( empty( $data['proposal_allowed'] ) ) {
		$failures[] = 'proposal_not_allowed';
	}
	if ( ! empty( $data['proposal_created'] ) ) {
		$failures[] = 'proposal_created_in_readonly_pilot';
	}
	if ( ! empty( $data['direct_wordpress_write'] ) ) {
		$failures[] = 'direct_wordpress_write_true';
	}
	if ( ! empty( $data['commit_execution'] ) ) {
		$failures[] = 'commit_execution_true';
	}
	if ( 'pass' !== (string) ( $final_review['status'] ?? '' ) ) {
		$failures[] = 'final_review_not_pass';
	}
	if ( empty( $final_review['ready_for_proposal'] ) ) {
		$failures[] = 'final_review_not_ready';
	}
	if ( ! empty( $final_review['blocking_finding_codes'] ) ) {
		$failures[] = 'blocking_findings_present';
	}
	if ( ! empty( $block_summary['core_html_count'] ) ) {
		$failures[] = 'core_html_detected';
	}
	if ( ! empty( $block_summary['non_core_blocks'] ) ) {
		$failures[] = 'non_core_blocks_detected';
	}

	return array(
		'id'                      => (string) ( $case['id'] ?? '' ),
		'prompt'                  => (string) ( $case['prompt'] ?? '' ),
		'passed'                  => empty( $failures ),
		'failure_codes'           => $failures,
		'route'                   => (string) ( $route['route'] ?? '' ),
		'expected_route'          => (string) ( $case['expected_route'] ?? '' ),
		'composer_profile_id'     => (string) ( $data['composer_profile_id'] ?? '' ),
		'expected_profile'        => (string) ( $case['expected_profile'] ?? '' ),
		'proposal_allowed'        => ! empty( $data['proposal_allowed'] ),
		'initial_finding_codes'   => is_array( $data['initial_review']['finding_codes'] ?? null ) ? array_values( $data['initial_review']['finding_codes'] ) : array(),
		'final_finding_codes'     => is_array( $final_review['finding_codes'] ?? null ) ? array_values( $final_review['finding_codes'] ) : array(),
		'blocking_finding_codes'  => is_array( $final_review['blocking_finding_codes'] ?? null ) ? array_values( $final_review['blocking_finding_codes'] ) : array(),
		'applied_repairs'         => is_array( $data['applied_repairs'] ?? null ) ? array_values( $data['applied_repairs'] ) : array(),
		'plan_summary'            => is_array( $final_review['plan_summary'] ?? null ) ? $final_review['plan_summary'] : array(),
		'block_summary'           => $block_summary,
		'readonly_contract'       => array(
			'proposal_created'       => ! empty( $data['proposal_created'] ),
			'direct_wordpress_write' => ! empty( $data['direct_wordpress_write'] ),
			'commit_execution'       => ! empty( $data['commit_execution'] ),
		),
	);
}

$arg_map = npcink_gutenberg_composer_pilot_arg_map( $script_args );
$output  = npcink_gutenberg_composer_pilot_path( (string) ( $arg_map['output'] ?? 'tests/gutenberg-composer-pilot/generated/gutenberg-composer-pilot.json' ), $root );
$limit   = max( 0, min( 200, (int) ( $arg_map['limit'] ?? 0 ) ) );

$GLOBALS['npcink_abilities_toolkit_unit_is_block_theme'] = true;
$GLOBALS['npcink_abilities_toolkit_unit_style_posts']    = npcink_gutenberg_composer_pilot_template_posts();

$core_read_package = npcink_gutenberg_composer_pilot_core_read_package();
$cases             = npcink_gutenberg_composer_pilot_cases();
if ( $limit > 0 ) {
	$cases = array_slice( $cases, 0, $limit );
}

$rows = array();
foreach ( $cases as $case ) {
	$input = array(
		'prompt'        => (string) ( $case['prompt'] ?? '' ),
		'target_hint'   => (string) ( $case['target_hint'] ?? 'auto' ),
		'intent_hint'   => (string) ( $case['intent_hint'] ?? 'auto' ),
		'media_hint'    => (string) ( $case['media_hint'] ?? 'auto' ),
		'style_hint'    => (string) ( $case['style_hint'] ?? 'auto' ),
		'plan_input'    => is_array( $case['plan_input'] ?? null ) ? $case['plan_input'] : array(),
		'media_fixture' => array(
			'url'           => 'https://magick-ai.local/wp-content/uploads/2026/06/preview.webp',
			'attachment_id' => 8053,
			'alt'           => 'WordPress AI governed workflow hero visual',
		),
	);
	$result = $core_read_package->compose_gutenberg_block_plan( $input );
	if ( is_wp_error( $result ) ) {
		$result = array(
			'success' => false,
			'data'    => array(
				'error_code'    => $result->get_error_code(),
				'error_message' => $result->get_error_message(),
			),
		);
	}
	$rows[] = npcink_gutenberg_composer_pilot_evaluate_case( $case, is_array( $result ) ? $result : array() );
}

$passed = 0;
foreach ( $rows as $row ) {
	if ( ! empty( $row['passed'] ) ) {
		++$passed;
	}
}

$report = array(
	'artifact_type' => 'gutenberg_composer_pilot_report',
	'version'       => 1,
	'generated_at'  => gmdate( 'c' ),
	'summary'       => array(
		'total_cases'  => count( $rows ),
		'passed_cases' => $passed,
		'failed_cases' => count( $rows ) - $passed,
		'pass_rate'    => count( $rows ) > 0 ? $passed / count( $rows ) : 0,
	),
	'acceptance_contract' => array(
		'catalog_required'       => true,
		'composer_profile_required' => true,
		'proposal_allowed_required' => true,
		'proposal_created_allowed' => false,
		'direct_wordpress_write_allowed' => false,
		'commit_execution_allowed' => false,
		'core_html_allowed'      => false,
		'non_core_blocks_allowed' => false,
	),
	'cases'        => $rows,
);

$directory = dirname( $output );
if ( ! is_dir( $directory ) && ! mkdir( $directory, 0775, true ) && ! is_dir( $directory ) ) {
	npcink_gutenberg_composer_pilot_fail( 'Unable to create output directory: ' . $directory );
}

$encoded = wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
if ( ! is_string( $encoded ) || false === file_put_contents( $output, $encoded . "\n" ) ) {
	npcink_gutenberg_composer_pilot_fail( 'Unable to write Gutenberg composer pilot JSON: ' . $output );
}

echo 'Gutenberg composer pilot: ' . ( count( $rows ) === $passed ? 'pass' : 'fail' ) . "\n";
echo 'Cases: ' . count( $rows ) . "\n";
echo 'Passed: ' . $passed . "\n";
echo 'Failed: ' . ( count( $rows ) - $passed ) . "\n";
echo 'Report: ' . $output . "\n";

if ( count( $rows ) !== $passed ) {
	foreach ( $rows as $row ) {
		if ( empty( $row['passed'] ) ) {
			echo '- ' . (string) ( $row['id'] ?? '' ) . ': ' . implode( ',', is_array( $row['failure_codes'] ?? null ) ? $row['failure_codes'] : array() ) . "\n";
		}
	}
	exit( 1 );
}
