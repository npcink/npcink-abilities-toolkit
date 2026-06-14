<?php
/**
 * Export Gutenberg recipe evaluation results into Promptfoo AI-judge CSV cases.
 *
 * This script is local/offline only. It reads a saved
 * evaluate-gutenberg-recipe-suite response and prepares model-graded cases.
 * It never calls WordPress, Cloud, Core, or Adapter.
 *
 * @package NpcinkAbilitiesToolkit
 */

$root        = dirname( __DIR__, 2 );
$script_args = array_slice( $argv ?? array(), 1 );

function npcink_gutenberg_recipe_judge_arg_map( array $script_args ): array {
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

function npcink_gutenberg_recipe_judge_fail( string $message ): void {
	fwrite( STDERR, "FAIL: {$message}\n" );
	exit( 1 );
}

function npcink_gutenberg_recipe_judge_path( string $path, string $root ): string {
	if ( str_starts_with( $path, '/' ) ) {
		return $path;
	}

	return $root . '/' . ltrim( $path, '/' );
}

function npcink_gutenberg_recipe_judge_text( string $value, int $max_chars = 3000 ): string {
	$value = trim( preg_replace( '/\s+/u', ' ', $value ) ?? $value );
	if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
		return mb_strlen( $value, 'UTF-8' ) > $max_chars ? mb_substr( $value, 0, $max_chars, 'UTF-8' ) : $value;
	}

	return strlen( $value ) > $max_chars ? substr( $value, 0, $max_chars ) : $value;
}

function npcink_gutenberg_recipe_judge_json( $value, int $max_chars = 3000 ): string {
	$json = wp_json_encode( $value );
	if ( ! is_string( $json ) ) {
		$json = json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	}

	return npcink_gutenberg_recipe_judge_text( is_string( $json ) ? $json : '', $max_chars );
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES, $depth );
	}
}

$arg_map = npcink_gutenberg_recipe_judge_arg_map( $script_args );
$input   = npcink_gutenberg_recipe_judge_path( (string) ( $arg_map['input'] ?? 'tests/gutenberg-recipe-eval/generated/gutenberg-recipe-suite.json' ), $root );
$output  = npcink_gutenberg_recipe_judge_path( (string) ( $arg_map['output'] ?? 'tests/gutenberg-recipe-eval/generated/promptfoo-judge-cases.csv' ), $root );
$limit   = max( 0, min( 200, (int) ( $arg_map['limit'] ?? 50 ) ) );

if ( ! is_file( $input ) ) {
	npcink_gutenberg_recipe_judge_fail( 'Gutenberg recipe evaluation JSON not found: ' . $input );
}

$decoded = json_decode( (string) file_get_contents( $input ), true );
if ( ! is_array( $decoded ) ) {
	npcink_gutenberg_recipe_judge_fail( 'Input JSON is invalid: ' . $input );
}

$data = is_array( $decoded['data'] ?? null ) ? $decoded['data'] : $decoded;
if ( ! is_array( $data['cases'] ?? null ) ) {
	npcink_gutenberg_recipe_judge_fail( 'Input JSON is missing data.cases or cases: ' . $input );
}

$directory = dirname( $output );
if ( ! is_dir( $directory ) && ! mkdir( $directory, 0775, true ) && ! is_dir( $directory ) ) {
	npcink_gutenberg_recipe_judge_fail( 'Unable to create output directory: ' . $directory );
}

$handle = fopen( $output, 'wb' );
if ( false === $handle ) {
	npcink_gutenberg_recipe_judge_fail( 'Unable to write Promptfoo judge cases: ' . $output );
}

fputcsv(
	$handle,
	array(
		'case_id',
		'prompt_excerpt',
		'route',
		'supported',
		'passed',
		'failure_codes',
		'plan_summary',
		'block_summary',
		'suite_status',
		'pass_rate',
	),
	',',
	'"',
	'\\'
);

$written = 0;
foreach ( $data['cases'] as $case ) {
	if ( ! is_array( $case ) ) {
		continue;
	}
	if ( $limit > 0 && $written >= $limit ) {
		break;
	}

	fputcsv(
		$handle,
		array(
			(string) ( $case['id'] ?? '' ),
			npcink_gutenberg_recipe_judge_text( (string) ( $case['prompt_excerpt'] ?? '' ), 500 ),
			(string) ( $case['route'] ?? '' ),
			empty( $case['supported'] ) ? 'false' : 'true',
			empty( $case['passed'] ) ? 'false' : 'true',
			npcink_gutenberg_recipe_judge_json( $case['failure_codes'] ?? array(), 800 ),
			npcink_gutenberg_recipe_judge_json( $case['plan_summary'] ?? array(), 2600 ),
			npcink_gutenberg_recipe_judge_json( $case['block_summary'] ?? array(), 1800 ),
			(string) ( $data['suite_status'] ?? '' ),
			(string) ( $data['summary']['pass_rate'] ?? '' ),
		),
		',',
		'"',
		'\\'
	);
	++$written;
}

fclose( $handle );

echo 'Exported Promptfoo Gutenberg recipe judge cases: ' . $output . "\n";
echo 'Cases: ' . $written . "\n";
