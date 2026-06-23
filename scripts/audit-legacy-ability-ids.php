<?php
/**
 * Audits active project files for retired Magick AI ability identifiers.
 *
 * @package NpcinkAbilitiesToolkit
 */

$root = dirname( __DIR__ );

$skip_dirs = array(
	'.git',
	'build',
	'coverage',
	'dist',
	'node_modules',
	'vendor',
);

$skip_files = array(
	'docs/ability-namespace-migration-map.md',
	'scripts/audit-legacy-ability-ids.php',
);

$allowed_extensions = array(
	'json',
	'md',
	'php',
	'txt',
	'yaml',
	'yml',
);

$legacy_references = array();

$iterator = new RecursiveIteratorIterator(
	new RecursiveCallbackFilterIterator(
		new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ),
		static function ( SplFileInfo $file ) use ( $skip_dirs ) {
			return ! ( $file->isDir() && in_array( $file->getFilename(), $skip_dirs, true ) );
		}
	)
);

foreach ( $iterator as $file ) {
	if ( ! $file instanceof SplFileInfo || ! $file->isFile() ) {
		continue;
	}

	$relative_path = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $root ) + 1 ) );
	if ( in_array( $relative_path, $skip_files, true ) || 0 === strpos( $relative_path, 'docs/archive/' ) ) {
		continue;
	}

	$extension = strtolower( $file->getExtension() );
	if ( ! in_array( $extension, $allowed_extensions, true ) ) {
		continue;
	}

	$contents = file_get_contents( $file->getPathname() );
	if ( ! is_string( $contents ) || '' === $contents ) {
		continue;
	}

	if ( preg_match_all( '#(?<![A-Za-z0-9._/-])magick-ai/(?:workflows/[a-z0-9][a-z0-9-]*|[a-z0-9][a-z0-9-]*)(?![A-Za-z0-9._/-])#', $contents, $matches, PREG_OFFSET_CAPTURE ) ) {
		foreach ( $matches[0] as $match ) {
			$line = substr_count( substr( $contents, 0, (int) $match[1] ), "\n" ) + 1;
			$legacy_references[] = array(
				'file'          => $relative_path,
				'line'          => $line,
				'legacy_id'     => $match[0],
				'required_fix'  => 'replace_with_canonical_npcink_abilities_toolkit_id',
			);
		}
	}
}

echo json_encode(
	array(
		'status'                   => empty( $legacy_references ) ? 'pass' : 'fail',
		'canonical_namespace'      => 'npcink-abilities-toolkit',
		'compatibility_aliases'    => false,
		'legacy_active_references' => $legacy_references,
	),
	JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
) . "\n";

if ( ! empty( $legacy_references ) ) {
	exit( 1 );
}
