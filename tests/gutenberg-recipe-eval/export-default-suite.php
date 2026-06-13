<?php
/**
 * Export the built-in Gutenberg recipe evaluation suite for local AI judges.
 *
 * This script is local/offline only. It uses the unit-test WordPress shims and
 * never calls WordPress, Adapter, Core, Cloud, or model providers.
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

function npcink_gutenberg_recipe_suite_arg_map( array $script_args ): array {
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

function npcink_gutenberg_recipe_suite_fail( string $message ): void {
	fwrite( STDERR, "FAIL: {$message}\n" );
	exit( 1 );
}

function npcink_gutenberg_recipe_suite_path( string $path, string $root ): string {
	if ( str_starts_with( $path, '/' ) ) {
		return $path;
	}

	return $root . '/' . ltrim( $path, '/' );
}

$arg_map = npcink_gutenberg_recipe_suite_arg_map( $script_args );
$output  = npcink_gutenberg_recipe_suite_path( (string) ( $arg_map['output'] ?? 'tests/gutenberg-recipe-eval/generated/gutenberg-recipe-suite.json' ), $root );

$GLOBALS['npcink_abilities_toolkit_unit_is_block_theme'] = true;
$GLOBALS['npcink_abilities_toolkit_unit_style_posts']    = array(
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

$schema_normalizer     = new Schema_Normalizer();
$annotation_normalizer = new Annotation_Normalizer();
$contract_normalizer   = new Contract_Normalizer( $schema_normalizer, $annotation_normalizer );
$package_categories    = new Category_Registrar();
$package_registrar     = new Ability_Registrar( $package_categories, $contract_normalizer );
$core_read_package     = new Core_Read_Package( $package_categories, $package_registrar );
$core_read_package->boot();

$result = $core_read_package->evaluate_gutenberg_recipe_suite(
	array(
		'minimum_pass_rate'    => 1,
		'include_case_details' => true,
		'media_fixture'        => array(
			'url'           => 'https://magick-ai.local/wp-content/uploads/2026/06/preview.webp',
			'attachment_id' => 8053,
			'alt'           => 'WordPress AI governed workflow hero visual',
		),
	)
);

if ( is_wp_error( $result ) ) {
	npcink_gutenberg_recipe_suite_fail( $result->get_error_code() . ': ' . $result->get_error_message() );
}

$directory = dirname( $output );
if ( ! is_dir( $directory ) && ! mkdir( $directory, 0775, true ) && ! is_dir( $directory ) ) {
	npcink_gutenberg_recipe_suite_fail( 'Unable to create output directory: ' . $directory );
}

$encoded = wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
if ( ! is_string( $encoded ) || false === file_put_contents( $output, $encoded . "\n" ) ) {
	npcink_gutenberg_recipe_suite_fail( 'Unable to write Gutenberg recipe suite JSON: ' . $output );
}

$data = is_array( $result['data'] ?? null ) ? $result['data'] : array();
echo 'Exported Gutenberg recipe suite: ' . $output . "\n";
echo 'Cases: ' . (int) ( $data['summary']['total_cases'] ?? 0 ) . "\n";
echo 'Passed: ' . (int) ( $data['summary']['passed_cases'] ?? 0 ) . "\n";
echo 'Status: ' . (string) ( $data['suite_status'] ?? '' ) . "\n";
