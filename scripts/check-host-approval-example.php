<?php
/**
 * Verifies the third-party host approval example against the documented host
 * approval contract in docs/host-approval-contract.md.
 *
 * @package NpcinkAbilitiesToolkit
 */

require_once dirname( __DIR__ ) . '/tests/bootstrap.php';

$failures = array();

/**
 * Records one checker failure.
 *
 * @param string $message Failure message.
 * @return void
 */
function npcink_abilities_toolkit_host_approval_fail( $message ) {
	global $failures;
	$failures[] = (string) $message;
}

/**
 * Asserts one host approval contract expectation.
 *
 * @param mixed  $condition Condition expected to be truthy.
 * @param string $message Assertion message.
 * @return void
 */
function npcink_abilities_toolkit_host_approval_assert( $condition, $message ) {
	if ( ! $condition ) {
		npcink_abilities_toolkit_host_approval_fail( $message );
	}
}

$example_path           = dirname( __DIR__ ) . '/examples/third-party-host-approval.php';
$example_source         = (string) file_get_contents( $example_path );
$example_preview_fired  = false;
$example_preview_payload = null;

add_action(
	'npcink_abilities_toolkit_example_host_preview_payload',
	static function ( $payload, $ability ) use ( &$example_preview_fired, &$example_preview_payload ) {
		$example_preview_fired   = true;
		$example_preview_payload = $payload;
	},
	10,
	2
);

Npcink_Abilities_Toolkit\Plugin::instance()->boot();

require_once $example_path;
do_action( 'plugins_loaded' );

$ability_id = 'npcink-abilities-toolkit/create-draft';
$abilities  = npcink_abilities_toolkit_get_registered();

npcink_abilities_toolkit_host_approval_assert( isset( $abilities[ $ability_id ] ) && is_array( $abilities[ $ability_id ] ), 'create-draft ability is registered for the host approval check' );

$ability  = isset( $abilities[ $ability_id ] ) && is_array( $abilities[ $ability_id ] ) ? $abilities[ $ability_id ] : array();
$callback = $ability['execute_callback'] ?? null;

npcink_abilities_toolkit_host_approval_assert( is_callable( $callback ), 'create-draft exposes a callable execute callback through the registered surface' );
npcink_abilities_toolkit_host_approval_assert( 'write' === (string) ( $ability['risk_level'] ?? '' ), 'create-draft is write-risk' );
npcink_abilities_toolkit_host_approval_assert( true === (bool) ( $ability['requires_approval'] ?? false ), 'create-draft requires host approval metadata' );
npcink_abilities_toolkit_host_approval_assert( true === (bool) ( $ability['input_schema']['properties']['dry_run']['default'] ?? false ), 'create-draft dry_run defaults to true' );
npcink_abilities_toolkit_host_approval_assert( false === (bool) ( $ability['input_schema']['properties']['commit']['default'] ?? true ), 'create-draft commit defaults to false' );
npcink_abilities_toolkit_host_approval_assert( isset( $ability['input_schema']['properties']['idempotency_key'] ), 'create-draft declares the idempotency_key control' );

npcink_abilities_toolkit_host_approval_assert( $example_preview_fired, 'example host fires its dry-run preview payload on plugins_loaded' );
npcink_abilities_toolkit_host_approval_assert( 'dry_run_preview' === (string) ( $example_preview_payload['stage'] ?? '' ), 'example host preview payload is marked as a dry-run stage' );
npcink_abilities_toolkit_host_approval_assert( true === (bool) ( $example_preview_payload['input']['dry_run'] ?? false ), 'example host preview input keeps dry_run true' );
npcink_abilities_toolkit_host_approval_assert( '' !== (string) ( $example_preview_payload['input']['idempotency_key'] ?? '' ), 'example host preview input carries an idempotency key' );

npcink_abilities_toolkit_host_approval_assert( ! function_exists( 'has_filter' ) || false !== has_filter( 'npcink_abilities_toolkit_write_commit_allowed', array( 'Npcink_Abilities_Toolkit_Example_Host_Runtime', 'decide_commit' ) ), 'example host registers the Toolkit commit decision filter' );

/*
 * Fail closed: a commit attempt without any recorded approval must be rejected,
 * and the checker never sets the Npcink AI runtime global, so this proves the
 * third-party path stays closed on its own.
 */
npcink_abilities_toolkit_host_approval_assert( ! isset( $GLOBALS['npcink_ai_runtime_wp_ability_context'] ), 'checker proves independence from the Npcink AI runtime global' );

$unapproved_commit = is_callable( $callback )
	? call_user_func(
		$callback,
		array(
			'title'           => 'Host approval contract check - unapproved',
			'content'         => '<p>Must stay rejected without a host approval decision.</p>',
			'commit'          => true,
			'dry_run'         => false,
			'idempotency_key' => 'host-approval-check-unapproved',
		)
	)
	: null;

npcink_abilities_toolkit_host_approval_assert( is_wp_error( $unapproved_commit ), 'unapproved commit attempt fails closed' );
npcink_abilities_toolkit_host_approval_assert( 'npcink_abilities_toolkit_host_approval_required' === (string) ( $unapproved_commit->code ?? '' ), 'unapproved commit fails with the stable host_approval_required code' );
npcink_abilities_toolkit_host_approval_assert( 403 === (int) ( $unapproved_commit->get_error_data()['status'] ?? 0 ), 'unapproved commit fails with HTTP 403' );
npcink_abilities_toolkit_host_approval_assert( true === (bool) ( $unapproved_commit->get_error_data()['host_governed'] ?? false ), 'unapproved commit error is marked host_governed' );

$default_preview = is_callable( $callback )
	? call_user_func(
		$callback,
		array(
			'title'   => 'Host approval contract check - default preview',
			'content' => '<p>Default posture is a dry-run preview.</p>',
		)
	)
	: null;

npcink_abilities_toolkit_host_approval_assert( is_array( $default_preview ) && ! is_wp_error( $default_preview ), 'default run returns a preview payload' );
npcink_abilities_toolkit_host_approval_assert( true === (bool) ( $default_preview['dry_run'] ?? false ), 'default run keeps dry_run true' );
npcink_abilities_toolkit_host_approval_assert( true === (bool) ( $default_preview['host_governed'] ?? false ), 'default run is marked host_governed' );
npcink_abilities_toolkit_host_approval_assert( true === (bool) ( $default_preview['commit_required'] ?? false ), 'default run is marked commit_required' );
npcink_abilities_toolkit_host_approval_assert( 0 === (int) ( $default_preview['post_id'] ?? -1 ), 'default run does not create a post' );

/*
 * Authorized path: the example host records its own approval, then the same
 * commit input succeeds through the Toolkit gate without any Npcink AI global.
 */
$approved_key = 'host-approval-check-approved-001';
$reviewed_input = array(
	'title'           => 'Host approval contract check - approved',
	'content'         => '<p>Authorized through the third-party host filter.</p>',
	'dry_run'         => true,
	'commit'          => false,
	'idempotency_key' => $approved_key,
);
$commit_input = array_replace( $reviewed_input, array( 'dry_run' => false, 'commit' => true ) );
$host = 'Npcink_Abilities_Toolkit_Example_Host_Runtime';
$host::record_approval( $ability_id, $reviewed_input, get_current_user_id(), time() + 300 );
$before = count( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'] ?? array() );
foreach ( array( 'title', 'content', 'idempotency_key' ) as $field ) {
	$changed = array_replace( $commit_input, array( $field => 'unreviewed replacement' ) );
	$result  = call_user_func( $callback, $changed );
	npcink_abilities_toolkit_host_approval_assert( is_wp_error( $result ), "changed {$field} cannot inherit approval" );
}
npcink_abilities_toolkit_host_approval_assert( false === $host::decide_commit( false, 'other/ability', $commit_input, array() ), 'approval cannot cross ability ids' );
npcink_abilities_toolkit_host_approval_assert( $before === count( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'] ?? array() ), 'rejected changed inputs create no posts' );

$approved_commit = call_user_func( $callback, $commit_input );

npcink_abilities_toolkit_host_approval_assert( is_array( $approved_commit ) && ! is_wp_error( $approved_commit ), 'approved commit attempt passes the gate' );
npcink_abilities_toolkit_host_approval_assert( false === (bool) ( $approved_commit['dry_run'] ?? true ), 'approved commit returns a committed payload' );
npcink_abilities_toolkit_host_approval_assert( 0 < (int) ( $approved_commit['post_id'] ?? 0 ), 'approved commit creates the draft post' );

$replayed = call_user_func( $callback, $commit_input );
npcink_abilities_toolkit_host_approval_assert( is_wp_error( $replayed ), 'consumed approval cannot create a second draft' );
$host::record_approval( $ability_id, $reviewed_input, get_current_user_id() + 1, time() + 300 );
npcink_abilities_toolkit_host_approval_assert( is_wp_error( call_user_func( $callback, $commit_input ) ), 'another caller cannot use approval' );
$host::record_approval( $ability_id, $reviewed_input, get_current_user_id(), time() + 1 );
usleep( 1100000 );
npcink_abilities_toolkit_host_approval_assert( is_wp_error( call_user_func( $callback, $commit_input ) ), 'expired approval cannot authorize a commit' );

/* Non-boolean host decisions must never authorize write or destructive calls. */
$trash = $abilities['npcink-abilities-toolkit/trash-post']['execute_callback'];
$trash_input = array( 'post_id' => $approved_commit['post_id'], 'dry_run' => false, 'commit' => true );
foreach ( array( 'false', 'true', 1, array( 'allowed' => true ), new WP_Error( 'host_failed' ) ) as $invalid_decision ) {
	$invalid_filter = static function () use ( $invalid_decision ) { return $invalid_decision; };
	add_filter( 'npcink_abilities_toolkit_write_commit_allowed', $invalid_filter, 20 );
	foreach ( array( array( $callback, $commit_input ), array( $trash, $trash_input ) ) as $case ) {
		$result = call_user_func( $case[0], $case[1] );
		npcink_abilities_toolkit_host_approval_assert( is_wp_error( $result ) && 'npcink_abilities_toolkit_host_approval_required' === $result->code, 'non-boolean filter decision reaches the approval gate and fails closed for writes and destruction' );
	}
	remove_all_filters( 'npcink_abilities_toolkit_write_commit_allowed' );
	$host::boot();
	$GLOBALS['npcink_ai_runtime_wp_ability_context'] = array( 'context' => array( 'approval_commit_authorized' => $invalid_decision ) );
	foreach ( array( array( $callback, $commit_input ), array( $trash, $trash_input ) ) as $case ) {
		$result = call_user_func( $case[0], $case[1] );
		npcink_abilities_toolkit_host_approval_assert( is_wp_error( $result ) && 'npcink_abilities_toolkit_host_approval_required' === $result->code, 'non-boolean runtime decision reaches the approval gate and fails closed for writes and destruction' );
	}
	unset( $GLOBALS['npcink_ai_runtime_wp_ability_context'] );
}
npcink_abilities_toolkit_host_approval_assert( 'draft' === get_post( $approved_commit['post_id'] )->post_status, 'rejected destructive calls leave the approved draft intact' );
npcink_abilities_toolkit_host_approval_assert( true === $host::decide_commit( true, 'other/ability', array(), array() ), 'example preserves another host boolean approval' );

$other_key_commit = is_callable( $callback )
	? call_user_func(
		$callback,
		array(
			'title'           => 'Host approval contract check - other key',
			'content'         => '<p>A different idempotency key must not inherit an approval.</p>',
			'commit'          => true,
			'dry_run'         => false,
			'idempotency_key' => 'host-approval-check-other-key',
		)
	)
	: null;

npcink_abilities_toolkit_host_approval_assert( is_wp_error( $other_key_commit ), 'commit with an unapproved idempotency key still fails closed' );
npcink_abilities_toolkit_host_approval_assert( 'npcink_abilities_toolkit_host_approval_required' === (string) ( $other_key_commit->code ?? '' ), 'unapproved key failure uses the stable host_approval_required code' );

/*
 * Static contract checks on the example source: it must demonstrate the filter
 * path and must not smuggle in credentials, the Npcink global channel, or its
 * own final write implementation.
 */
npcink_abilities_toolkit_host_approval_assert( false !== strpos( $example_source, 'npcink_abilities_toolkit_write_commit_allowed' ), 'example source references the Toolkit commit filter' );
npcink_abilities_toolkit_host_approval_assert( false !== strpos( $example_source, 'record_approval' ), 'example source records host approval decisions before commits' );
npcink_abilities_toolkit_host_approval_assert( false === strpos( $example_source, 'npcink_ai_runtime_wp_ability_context' ), 'example source does not depend on the Npcink AI runtime global' );
foreach ( array( 'api_key', 'access_key', 'client_secret', 'password', 'private_key' ) as $forbidden ) {
	npcink_abilities_toolkit_host_approval_assert( false === stripos( $example_source, $forbidden ), "example source does not handle {$forbidden} material" );
}
foreach ( array( 'wp_insert_post', 'wp_update_post', 'wp_delete_post', 'wp_delete_attachment' ) as $write_function ) {
	npcink_abilities_toolkit_host_approval_assert( false === strpos( $example_source, $write_function ), "example source does not call {$write_function} directly" );
}

if ( ! empty( $failures ) ) {
	foreach ( $failures as $failure ) {
		fwrite( STDERR, '[fail] ' . $failure . PHP_EOL );
	}
	exit( 1 );
}

echo "third-party host approval example: ok\n";
