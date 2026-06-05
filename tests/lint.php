<?php
/**
 * PHP syntax lint runner using the current PHP binary.
 *
 * @package NpcinkAbilitiesToolkit
 */

$root = dirname( __DIR__ );
$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator(
		$root,
		FilesystemIterator::SKIP_DOTS
	)
);

$files = array();
foreach ( $iterator as $file ) {
	$path = $file->getPathname();
	foreach ( array( '.git', 'vendor', 'node_modules', 'dist', 'build' ) as $excluded_dir ) {
		if ( false !== strpos( $path, DIRECTORY_SEPARATOR . $excluded_dir . DIRECTORY_SEPARATOR ) ) {
			continue 2;
		}
	}
	if ( 'php' === strtolower( $file->getExtension() ) ) {
		$files[] = $path;
	}
}

sort( $files );

foreach ( $files as $file ) {
	$command = escapeshellarg( PHP_BINARY ) . ' -l ' . escapeshellarg( $file );
	passthru( $command, $status );
	if ( 0 !== $status ) {
		exit( $status );
	}
}

echo 'Linted ' . count( $files ) . " PHP files\n";
