<?php
/**
 * Cold plugin bootstrap performance guard.
 *
 * @package NpcinkAbilitiesToolkit
 */

define( 'ABSPATH', __DIR__ . '/' );

function plugin_dir_path( $file ) {
	return dirname( (string) $file ) . '/';
}

function add_action() {
	return true;
}

$started = microtime( true );
$memory  = memory_get_usage( true );
require_once dirname( __DIR__ ) . '/npcink-abilities-toolkit.php';
$elapsed_ms = ( microtime( true ) - $started ) * 1000;
$memory_mb  = ( memory_get_usage( true ) - $memory ) / 1048576;

$max_ms = 25;
$max_memory_mb = 4;
printf( "plugin_bootstrap: %.2fms memory_delta=%.2fMB budgets=%dms/%.1fMB\n", $elapsed_ms, $memory_mb, $max_ms, $max_memory_mb );

if ( $elapsed_ms > $max_ms || $memory_mb > $max_memory_mb ) {
	fwrite( STDERR, "FAIL: plugin bootstrap exceeded its cold-load budget.\n" );
	exit( 1 );
}

echo "OK: plugin bootstrap stays within the cold-load budget\n";
