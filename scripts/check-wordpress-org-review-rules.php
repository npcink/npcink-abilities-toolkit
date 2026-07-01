<?php
/**
 * Guards against WordPress.org review patterns that previously blocked approval.
 *
 * @package NpcinkAbilitiesToolkit
 */

$root     = dirname( __DIR__ );
$failures = array();

/**
 * Records a failed review rule.
 *
 * @param string $message Failure message.
 * @return void
 */
function npcink_abilities_toolkit_wporg_fail( $message ) {
	global $failures;
	$failures[] = (string) $message;
}

/**
 * Reads a text file.
 *
 * @param string $path File path.
 * @return string
 */
function npcink_abilities_toolkit_wporg_read( $path ) {
	if ( ! is_readable( $path ) ) {
		npcink_abilities_toolkit_wporg_fail( 'Missing readable file: ' . $path );
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
function npcink_abilities_toolkit_wporg_plugin_php_files() {
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

/**
 * Returns bundled locale files keyed by locale and extension.
 *
 * @return array<string,array<string,string>>
 */
function npcink_abilities_toolkit_wporg_locale_files() {
	global $root;

	$files = glob( $root . '/languages/npcink-abilities-toolkit-*.*' );
	$files = is_array( $files ) ? $files : array();
	$locales = array();

	foreach ( $files as $file ) {
		if ( ! preg_match( '/npcink-abilities-toolkit-([A-Za-z_]+)\.(po|mo)$/', basename( $file ), $matches ) ) {
			continue;
		}

		$locale = $matches[1];
		$ext    = $matches[2];
		if ( ! isset( $locales[ $locale ] ) ) {
			$locales[ $locale ] = array();
		}
		$locales[ $locale ][ $ext ] = $file;
	}

	ksort( $locales );
	return $locales;
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

foreach ( npcink_abilities_toolkit_wporg_plugin_php_files() as $file ) {
	$relative = str_replace( $root . '/', '', $file );
	$contents = npcink_abilities_toolkit_wporg_read( $file );

	foreach ( $rules as $message => $pattern ) {
		if ( preg_match( $pattern, $contents ) ) {
			npcink_abilities_toolkit_wporg_fail( $relative . ': ' . $message );
		}
	}
}

$expected_locales = array( 'de_DE', 'es_ES', 'fr_FR', 'ja', 'ko_KR', 'pt_BR', 'zh_CN' );
$forbidden_locales = array(
	'zh_TW' => 'Traditional Chinese starter locale was removed because it was incomplete.',
);
$locale_files = npcink_abilities_toolkit_wporg_locale_files();

foreach ( $forbidden_locales as $locale => $reason ) {
	if ( isset( $locale_files[ $locale ] ) ) {
		npcink_abilities_toolkit_wporg_fail( "languages: {$locale} must not ship as a bundled starter locale. {$reason}" );
	}
}

foreach ( $expected_locales as $locale ) {
	if ( ! isset( $locale_files[ $locale ] ) ) {
		npcink_abilities_toolkit_wporg_fail( "languages: missing expected bundled starter locale {$locale}." );
		continue;
	}
	foreach ( array( 'po', 'mo' ) as $ext ) {
		if ( empty( $locale_files[ $locale ][ $ext ] ) ) {
			npcink_abilities_toolkit_wporg_fail( "languages: {$locale} is missing matching .{$ext} file." );
		}
	}
}

foreach ( $locale_files as $locale => $extensions ) {
	if ( ! in_array( $locale, $expected_locales, true ) && ! isset( $forbidden_locales[ $locale ] ) ) {
		npcink_abilities_toolkit_wporg_fail( "languages: unexpected bundled starter locale {$locale}; document and add it to the release guard before shipping." );
	}
	if ( isset( $extensions['po'], $extensions['mo'] ) ) {
		continue;
	}
	npcink_abilities_toolkit_wporg_fail( "languages: {$locale} must ship .po and .mo files together." );
}

if ( ! empty( $failures ) ) {
	foreach ( $failures as $failure ) {
		fwrite( STDERR, '[fail] ' . $failure . PHP_EOL );
	}
	exit( 1 );
}

echo "WordPress.org review guard: ok\n";
