<?php
/**
 * Independent provider + host proof through real WordPress REST handlers.
 * Run with --skip-plugins --skip-themes --require=tests/third-party-preload.php.
 * No Core, Adapter, model calls, external HTTP, or operator approval UI.
 *
 * @package NpcinkAbilitiesToolkit
 */
if ( ! defined( 'ABSPATH' ) || ! function_exists( 'npcink_abilities_toolkit_get_registered' ) ) {
	throw new RuntimeException( 'Load the isolated WordPress proof first.' );
}
$checks = 0;
$check = static function ( $ok, $message ) use ( &$checks ) {
	++$checks;
	if ( ! $ok ) { throw new RuntimeException( $message ); }
};
$run = static function ( $id, array $input = array(), $method = 'POST' ) {
	$request = new WP_REST_Request( $method, '/wp-abilities/v1/abilities/' . $id . '/run' );
	if ( 'GET' === $method ) {
		$request->set_query_params( array( 'input' => $input ) );
	} else {
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'input' => $input ) ) );
	}
	return rest_do_request( $request );
};
$actor   = get_current_user_id();
$admins  = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
$profile = getenv( 'NPCINK_TOOLKIT_PROOF_PROFILE' ) ?: 'full';
$subscriber = 0;
$run_id  = 'Npcink independent proof ' . wp_generate_uuid4();
$count   = static function () use ( $run_id ) {
	return count( get_posts( array( 'post_type' => 'post', 'post_status' => 'any', 'title' => $run_id, 'fields' => 'ids', 'posts_per_page' => -1 ) ) );
};
try {
	$check( in_array( $profile, array( 'full', 'read_only', 'core_read' ), true ), 'An explicit supported proof profile is selected.' );
	$check( ! empty( $admins ), 'An administrator actor is required.' );
	$check( ! isset( $GLOBALS['npcink_ai_runtime_wp_ability_context'] ), 'No Npcink runtime authorization is installed.' );
	$check( realpath( NPCINK_ABILITIES_TOOLKIT_FILE ) === realpath( dirname( __DIR__ ) . '/npcink-abilities-toolkit.php' ), 'The candidate checkout is loaded.' );
	wp_set_current_user( (int) $admins[0] );
	$rows = array();
	$page = 1;
	do {
		$request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities' );
		$request->set_query_params( array( 'page' => $page, 'per_page' => 100 ) );
		$catalog = rest_do_request( $request );
		$check( 200 === $catalog->get_status(), 'Authenticated REST discovery succeeds.' );
		$rows = array_merge( $rows, $catalog->get_data() );
		$pages = (int) ( $catalog->get_headers()['X-WP-TotalPages'] ?? 1 );
	} while ( ++$page <= $pages );
	$names = array_column( $rows, 'name' );
	foreach ( array( 'acme/content-inventory-summary', 'acme/create-draft-proposal', 'npcink-abilities-toolkit/site-info' ) as $id ) {
		$check( in_array( $id, $names, true ), 'REST catalog includes ' . $id );
	}
	$check( 200 === $run( 'acme/content-inventory-summary', array(), 'GET' )->get_status(), 'Provider read executes through REST.' );
	$proposal = $run( 'acme/create-draft-proposal', array( 'title' => $run_id ) );
	$check( 200 === $proposal->get_status() && isset( $proposal->get_data()['diff'] ), 'Provider returns a review artifact through REST.' );
	$check( 0 === $count(), 'Provider proposal creates no WordPress post.' );
	wp_set_current_user( 0 );
	$check( $run( 'acme/content-inventory-summary', array(), 'GET' )->get_status() >= 400, 'Anonymous provider execution is refused.' );
	$subscriber = wp_insert_user( array( 'user_login' => 'npcink-proof-' . wp_generate_uuid4(), 'user_pass' => wp_generate_password( 32 ), 'role' => 'subscriber' ) );
	$check( ! is_wp_error( $subscriber ), 'A run-owned subscriber fixture is available.' );
	wp_set_current_user( $subscriber );
	$check( 403 === $run( 'acme/content-inventory-summary', array(), 'GET' )->get_status(), 'Authenticated subscriber cannot run privileged provider reads.' );
	$check( 403 === $run( 'acme/create-draft-proposal', array( 'title' => $run_id ) )->get_status(), 'Authenticated subscriber cannot create provider proposals.' );
	wp_set_current_user( (int) $admins[0] );

	$write_id = 'npcink-abilities-toolkit/create-draft';
	$check( in_array( $write_id, $names, true ) === ( 'full' === $profile ), 'Write discovery matches selected profile.' );
	$check( in_array( 'npcink-abilities-toolkit/list-workflow-recipes', $names, true ) === ( 'core_read' !== $profile ), 'Optional read discovery matches selected profile.' );
	if ( 'full' !== $profile ) {
		foreach ( npcink_abilities_toolkit_get_registered() as $id => $ability ) {
			if ( 0 === strpos( $id, 'npcink-abilities-toolkit/' ) ) {
				$check( 'read' === $ability['risk_level'], 'Profile exposes only built-in reads: ' . $id );
			}
		}
		$check( 404 === $run( $write_id, array( 'title' => $run_id ) )->get_status(), 'Removed write ability cannot execute via REST.' );
	} else {
		$reviewed = array( 'title' => $run_id, 'content' => '<p>Exact reviewed content.</p>', 'idempotency_key' => wp_generate_uuid4(), 'dry_run' => true, 'commit' => false );
		$preview = $run( $write_id, $reviewed );
		$check( 200 === $preview->get_status() && true === $preview->get_data()['dry_run'], 'Independent host gets a real dry-run preview.' );
		$commit = array_replace( $reviewed, array( 'dry_run' => false, 'commit' => true ) );
		$check( 403 === $run( $write_id, $commit )->get_status(), 'Commit before recorded approval is refused.' );
		$check( 0 === $count(), 'Preview and rejected commit create no posts.' );
		$host = 'Npcink_Abilities_Toolkit_Example_Host_Runtime';
		$host::record_approval( $write_id, $reviewed, get_current_user_id(), time() + 300 );
		$check( 403 === $run( $write_id, array_replace( $commit, array( 'content' => 'Unreviewed input' ) ) )->get_status(), 'Changed input cannot inherit an approval.' );
		$written = $run( $write_id, $commit );
		$payload = $written->get_data();
		$check( 200 === $written->get_status() && false === $payload['dry_run'], 'Exact approved input commits through the independent host filter.' );
		$check( $run_id === get_post_field( 'post_title', $payload['post_id'], 'raw' ), 'Committed draft belongs to this proof run.' );
		$check( $reviewed['content'] === get_post_field( 'post_content', $payload['post_id'], 'raw' ), 'Real database contains exactly the reviewed content.' );
		$check( 403 === $run( $write_id, $commit )->get_status() && 1 === $count(), 'Replay is refused without creating another draft.' );
	}
} finally {
	// Find only run-owned posts, even if a regression returned an unrelated id.
	$ids = get_posts( array( 'post_type' => 'post', 'post_status' => 'any', 'title' => $run_id, 'fields' => 'ids', 'posts_per_page' => -1 ) );
	foreach ( array_unique( $ids ) as $id ) { wp_delete_post( $id, true ); }
	wp_set_current_user( $actor );
	if ( is_int( $subscriber ) && $subscriber > 0 ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $subscriber );
	}
}
$check( 0 === $count(), 'Run-owned fixtures are removed.' );
$check( ! get_user_by( 'id', $subscriber ), 'Run-owned subscriber is removed.' );
echo 'Independent WordPress proof OK: ' . $profile . ', WordPress ' . get_bloginfo( 'version' ) . ', PHP ' . PHP_VERSION . ", {$checks} assertions\n";
