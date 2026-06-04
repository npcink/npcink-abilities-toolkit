<?php
/**
 * Checks that this standalone plugin does not drift back into Magick AI runtime ownership.
 *
 * @package MagickAIAbilities
 */

$root = dirname( __DIR__ );
$failures = array();

/**
 * Records a failed boundary assertion.
 *
 * @param string $message Failure message.
 * @return void
 */
function maa_boundary_fail( $message ) {
	global $failures;
	$failures[] = (string) $message;
}

/**
 * Reads a UTF-8 text file.
 *
 * @param string $path File path.
 * @return string
 */
function maa_boundary_read( $path ) {
	if ( ! is_readable( $path ) ) {
		maa_boundary_fail( 'Missing readable file: ' . $path );
		return '';
	}

	$contents = file_get_contents( $path );
	return is_string( $contents ) ? $contents : '';
}

/**
 * Returns PHP files below a directory.
 *
 * @param string $directory Directory path.
 * @return array<int,string>
 */
function maa_boundary_php_files( $directory ) {
	if ( ! is_dir( $directory ) ) {
		return array();
	}

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator(
			$directory,
			FilesystemIterator::SKIP_DOTS
		)
	);
	$files = array();
	foreach ( $iterator as $file ) {
		if ( 'php' === strtolower( $file->getExtension() ) ) {
			$files[] = $file->getPathname();
		}
	}
	sort( $files );

	return $files;
}

$contract_path = $root . '/docs/magick-ai-project-split-contract.md';
$contract      = maa_boundary_read( $contract_path );

foreach (
	array(
		'independent WordPress Abilities API package plugin',
		'Magick AI is an optional consumer',
		'Final commit authorization',
		'Duplicate Registration Rule',
		'Dependency Rule',
		'composer check:boundary',
	) as $required_text
) {
	if ( false === strpos( $contract, $required_text ) ) {
		maa_boundary_fail( 'Project split contract is missing required text: ' . $required_text );
	}
}

$readme = maa_boundary_read( $root . '/README.md' );
if ( false === strpos( $readme, 'docs/magick-ai-project-split-contract.md' ) ) {
	maa_boundary_fail( 'README must link to docs/magick-ai-project-split-contract.md.' );
}

$forbidden_patterns = array(
	'magick-ai-root',
	'/includes/abilities/',
	'magick_ai_core_run_capability',
	'magick_ai_execute_runtime_bridge',
	'magick_ai_dispatch_capability',
	'MAI_Capability_Request',
	'class-rest-open-platform',
);

foreach ( maa_boundary_php_files( $root . '/includes' ) as $file ) {
	$relative = str_replace( $root . '/', '', $file );
	$contents = maa_boundary_read( $file );

	foreach ( $forbidden_patterns as $pattern ) {
		if ( false !== strpos( $contents, $pattern ) ) {
			maa_boundary_fail( $relative . ' contains forbidden Magick AI runtime dependency pattern: ' . $pattern );
		}
	}
}

if ( ! empty( $failures ) ) {
	foreach ( $failures as $failure ) {
		fwrite( STDERR, '[fail] ' . $failure . PHP_EOL );
	}
	exit( 1 );
}

echo "npcink-abilities-toolkit project boundary: ok\n";
