<?php
/** Reference provider/host boot in an isolated WordPress request. */
$proof_profile = getenv( 'NPCINK_TOOLKIT_PROOF_PROFILE' ) ?: 'full';
add_filter( 'npcink_abilities_toolkit_registration_profile', static function () use ( $proof_profile ) { return $proof_profile; } );
// Keep site-owned state unchanged, including a previously enabled operator switch.
add_filter( 'pre_option_npcink_abilities_toolkit_read_only_mode', static function () { return ''; } );
add_filter( 'npcink_abilities_toolkit_enabled_packages', static function ( $packages ) {
	$packages['read_cache_hooks'] = false;
	return $packages;
} );
add_filter( 'pre_schedule_event', static function () { return false; } );
add_filter( 'pre_clear_scheduled_hook', static function () { return 0; } );
add_filter( 'pre_update_option_npcink_abilities_toolkit_catalog_observability_state', static function ( $value, $old ) { return $old; }, 10, 2 );
require dirname( __DIR__, 2 ) . '/npcink-abilities-toolkit.php';
require dirname( __DIR__, 2 ) . '/examples/demo-plugin/npcink-abilities-toolkit-demo.php';
require dirname( __DIR__, 2 ) . '/examples/third-party-host-approval.php';
