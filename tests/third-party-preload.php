<?php
/**
 * WP-CLI --require loader: isolate this checkout and reference providers from
 * the site's active plugins and MU plugins without editing their activation.
 *
 * @package NpcinkAbilitiesToolkit
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || defined( 'WPMU_PLUGIN_DIR' ) ) {
	throw new RuntimeException( 'Use WP-CLI --require before WordPress loads; WPMU_PLUGIN_DIR must be unset.' );
}
$proof_dir = sys_get_temp_dir() . '/npcink-third-party-' . bin2hex( random_bytes( 8 ) );
if ( ! mkdir( $proof_dir, 0700 ) ) { throw new RuntimeException( 'Cannot create isolated MU directory.' ); }
$proof_loader = $proof_dir . '/proof.php';
register_shutdown_function( static function () use ( $proof_loader, $proof_dir ) {
	if ( is_file( $proof_loader ) ) { unlink( $proof_loader ); }
	rmdir( $proof_dir );
} );
$fixture = __DIR__ . '/fixtures/third-party-loader.php';
if ( false === file_put_contents( $proof_loader, '<?php require ' . var_export( $fixture, true ) . ';' ) ) {
	throw new RuntimeException( 'Cannot write isolated loader.' );
}
define( 'WPMU_PLUGIN_DIR', $proof_dir );
