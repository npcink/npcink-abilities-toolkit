<?php
/**
 * Structure ratchet: guards the single-file size ceiling for issue #89.
 *
 * Every analyzed PHP file must stay at or below its recorded peak line count;
 * files without a baseline entry (new files) must stay at or below the
 * new-file cap. Slices that shrink a file should regenerate the baseline with
 * `--update` so the lower size becomes the new ceiling; baseline diffs are
 * reviewed like any other change. Existing debt never blocks a change; growth
 * of recorded debt does.
 *
 * @package NpcinkAbilitiesToolkit
 */

$root            = dirname( __DIR__ );
$baseline_path   = $root . '/tests/fixtures/structure-ratchet-baseline.json';
$new_file_cap    = 1500;
$update_baseline = in_array( '--update', $argv, true );
$failures        = array();

/**
 * Records a failed ratchet assertion.
 *
 * @param string $message Failure message.
 * @return void
 */
function npcink_abilities_toolkit_structure_fail( $message ) {
	global $failures;
	$failures[] = (string) $message;
}

/**
 * Counts the lines of a PHP file. Fails closed: an unreadable file must not
 * silently pass the gate, and `--update` must not record a bogus 0 ceiling.
 *
 * @param string $path Absolute file path.
 * @return int
 */
function npcink_abilities_toolkit_structure_line_count( $path ) {
	$lines = file( $path, FILE_IGNORE_NEW_LINES );
	if ( false === $lines ) {
		fwrite( STDERR, "[structure-ratchet] cannot read: {$path}\n" );
		exit( 1 );
	}
	return count( $lines );
}

/**
 * Returns analyzed PHP files: everything under includes/ plus the plugin
 * bootstrap, matching the PHPStan analyzed paths.
 *
 * @param string $root Repository root.
 * @return array<int,string> Absolute paths.
 */
function npcink_abilities_toolkit_structure_files( $root ) {
	$files = array();

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $root . '/includes', FilesystemIterator::SKIP_DOTS )
	);
	foreach ( $iterator as $file_info ) {
		if ( $file_info->isFile() && 'php' === $file_info->getExtension() ) {
			$files[] = $file_info->getPathname();
		}
	}

	$bootstrap = $root . '/npcink-abilities-toolkit.php';
	if ( is_file( $bootstrap ) ) {
		$files[] = $bootstrap;
	}

	sort( $files );
	return $files;
}

$current = array();
foreach ( npcink_abilities_toolkit_structure_files( $root ) as $path ) {
	$relative                    = substr( $path, strlen( $root ) + 1 );
	$current[ $relative ]        = npcink_abilities_toolkit_structure_line_count( $path );
}

if ( $update_baseline ) {
	$payload = array(
		'new_file_cap' => $new_file_cap,
		'files'        => $current,
	);
	$encoded = json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
	if ( false === file_put_contents( $baseline_path, $encoded ) ) {
		fwrite( STDERR, "[structure-ratchet] cannot write baseline: {$baseline_path}\n" );
		exit( 1 );
	}
	fwrite( STDOUT, '[structure-ratchet] baseline rewritten with ' . count( $current ) . " files; regenerate never raises a ceiling silently - review the diff.\n" );
	exit( 0 );
}

if ( ! is_readable( $baseline_path ) ) {
	fwrite( STDERR, "[structure-ratchet] missing baseline {$baseline_path}; run `composer check:structure-ratchet -- --update` once and commit it.\n" );
	exit( 1 );
}

$decoded = json_decode( (string) file_get_contents( $baseline_path ), true );
if ( ! is_array( $decoded ) || ! isset( $decoded['files'] ) || ! is_array( $decoded['files'] ) ) {
	fwrite( STDERR, "[structure-ratchet] baseline is malformed: {$baseline_path}\n" );
	exit( 1 );
}

$baseline_files = $decoded['files'];
$cap            = isset( $decoded['new_file_cap'] ) ? (int) $decoded['new_file_cap'] : $new_file_cap;

foreach ( $current as $relative => $line_count ) {
	if ( ! isset( $baseline_files[ $relative ] ) ) {
		// Carry a vanished baseline entry across a move or rename (same
		// basename, old path gone) so recorded debt follows the file instead
		// of the new path being misread as a fresh capped file. First match
		// wins; ambiguous basename collisions should regenerate the baseline.
		$basename = basename( $relative );
		foreach ( $baseline_files as $old_path => $old_ceiling ) {
			if ( basename( $old_path ) === $basename && ! isset( $current[ $old_path ] ) ) {
				$baseline_files[ $relative ] = $old_ceiling;
				break;
			}
		}
	}

	if ( isset( $baseline_files[ $relative ] ) ) {
		$ceiling = (int) $baseline_files[ $relative ];
		if ( $line_count > $ceiling ) {
			npcink_abilities_toolkit_structure_fail(
				"{$relative} grew to {$line_count} lines, above its recorded ceiling of {$ceiling}; split or shrink the file, then rerun with --update inside the same pull request only when the shrink is intentional."
			);
		}
		continue;
	}

	if ( $line_count > $cap ) {
		npcink_abilities_toolkit_structure_fail(
			"{$relative} is a new {$line_count}-line file, above the {$cap}-line new-file cap; split it before merging."
		);
	}
}

if ( ! empty( $failures ) ) {
	foreach ( $failures as $failure ) {
		fwrite( STDERR, "[structure-ratchet] {$failure}\n" );
	}
	exit( 1 );
}

fwrite( STDOUT, '[structure-ratchet] ' . count( $current ) . " analyzed files within their recorded ceilings (new-file cap {$cap}).\n" );
exit( 0 );
