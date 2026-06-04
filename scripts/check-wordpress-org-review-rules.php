<?php
/**
 * Guards against WordPress.org review patterns that previously blocked approval.
 *
 * @package MagickAIAbilities
 */

$root     = dirname( __DIR__ );
$failures = array();

/**
 * Records a failed review rule.
 *
 * @param string $message Failure message.
 * @return void
 */
function maa_wporg_fail( $message ) {
	global $failures;
	$failures[] = (string) $message;
}

/**
 * Reads a text file.
 *
 * @param string $path File path.
 * @return string
 */
function maa_wporg_read( $path ) {
	if ( ! is_readable( $path ) ) {
		maa_wporg_fail( 'Missing readable file: ' . $path );
		return '';
	}

	$contents = file_get_contents( $path );
	return is_string( $contents ) ? $contents : '';
}

/**
 * Returns plugin PHP files that ship in the WordPress.org package.
 *
 * @return array<int,string>
 */
function maa_wporg_plugin_php_files() {
	global $root;

	$files = glob( $root . '/*.php' );
	$files = is_array( $files ) ? $files : array();

	foreach ( array( 'includes' ) as $directory ) {
		$path = $root . '/' . $directory;
		if ( ! is_dir( $path ) ) {
			continue;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(
				$path,
				FilesystemIterator::SKIP_DOTS
			)
		);
		foreach ( $iterator as $file ) {
			if ( 'php' === strtolower( $file->getExtension() ) ) {
				$files[] = $file->getPathname();
			}
		}
	}

	sort( $files );
	return $files;
}

$rules = array(
	'Do not build paths into wp-admin/includes; rely on loaded WordPress APIs or packaged plugin files.' => '/wp-admin\/includes\//',
	'Do not require core files through ABSPATH; package-owned files should use plugin_dir_path constants.' => '/require_once\s+ABSPATH/',
	'Do not ship inline admin styles through wp_add_inline_style(); use assets/*.css.' => '/wp_add_inline_style\s*\(/',
	'Do not ship inline admin scripts through wp_add_inline_script(); use assets/*.js or data attributes.' => '/wp_add_inline_script\s*\(/',
	'Do not output raw script tags from PHP admin views; enqueue scripts.' => '/<\s*\/?\s*script\b/i',
	'Do not output raw style tags from PHP admin views; enqueue styles.' => '/<\s*\/?\s*style\b/i',
	'Do not read $_GET directly in plugin views; route reads through nonce-verified helpers.' => '/\$_GET\s*\[/',
);

foreach ( maa_wporg_plugin_php_files() as $file ) {
	$relative = str_replace( $root . '/', '', $file );
	$contents = maa_wporg_read( $file );

	foreach ( $rules as $message => $pattern ) {
		if ( preg_match( $pattern, $contents ) ) {
			maa_wporg_fail( $relative . ': ' . $message );
		}
	}
}

if ( ! empty( $failures ) ) {
	foreach ( $failures as $failure ) {
		fwrite( STDERR, '[fail] ' . $failure . PHP_EOL );
	}
	exit( 1 );
}

echo "WordPress.org review guard: ok\n";
