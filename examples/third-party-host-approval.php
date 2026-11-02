<?php
/**
 * Example third-party host runtime that governs Toolkit write commits.
 *
 * This example is not part of Npcink AI and never calls Npcink AI. It shows
 * how any host product can keep dry-run previews as the default and authorize
 * final commits through the Toolkit commit filter after its own approval
 * decision. See docs/host-approval-contract.md for the full contract.
 *
 * The example is fail-closed by construction: the commit filter authorizes
 * nothing until this host records approval for the exact input, ability id,
 * idempotency key, caller and expiry. The file itself never performs a final write, never
 * stores approvals in the database, and never handles provider credentials.
 *
 * @package NpcinkAbilitiesToolkitExample
 */

final class Npcink_Abilities_Toolkit_Example_Host_Runtime {
	/**
	 * Host-owned, request-local approvals. Production hosts must use their own
	 * durable approval records and atomic replay protection across requests.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	private static $recorded_approvals = array();

	/** @return void */
	public static function boot() {
		add_filter( 'npcink_abilities_toolkit_write_commit_allowed', array( __CLASS__, 'decide_commit' ), 10, 4 );
	}

	/**
	 * Called by the host only AFTER its approval, identity and scope checks.
	 * Never derive an approval from a commit request or expose this as a route.
	 *
	 * @param string              $ability_id Reviewed ability.
	 * @param array<string,mixed> $reviewed_input Exact reviewed ability input.
	 * @param int                 $user_id Approved WordPress caller.
	 * @param int                 $expires_at Host approval expiry (Unix seconds).
	 * @return void
	 */
	public static function record_approval( $ability_id, array $reviewed_input, $user_id, $expires_at ) {
		$key = $reviewed_input['idempotency_key'] ?? '';
		if ( ! is_string( $key ) || '' === $key || strlen( $key ) > 190 || $user_id <= 0 || $expires_at <= time() ) {
			return;
		}
		self::$recorded_approvals[ $ability_id . ':' . $key ] = array(
			'input'      => self::reviewed_values( $reviewed_input ),
			'user_id'    => (int) $user_id,
			'expires_at' => (int) $expires_at,
		);
	}

	/**
	 * Binds this host's decision to the ability, key, exact input, caller and
	 * expiry. Consumes it once, before dispatch; failures need a new review.
	 * Another host's strictly boolean authorization remains its responsibility.
	 *
	 * @param bool                $allowed Authorization from another host.
	 * @param string              $ability_id Ability id.
	 * @param array<string,mixed> $input Current ability input.
	 * @param array<string,mixed> $runtime_context Other host's context (unused).
	 * @return bool
	 */
	public static function decide_commit( $allowed, $ability_id, $input, $runtime_context ) {
		if ( true === $allowed ) {
			return true;
		}
		$key = $input['idempotency_key'] ?? '';
		if ( ! is_string( $key ) || '' === $key ) {
			return false;
		}
		$record_key = $ability_id . ':' . $key;
		$approval   = self::$recorded_approvals[ $record_key ] ?? null;
		if ( null === $approval || $approval['expires_at'] <= time()
			|| $approval['user_id'] !== get_current_user_id()
			|| $approval['input'] !== self::reviewed_values( $input ) ) {
			return false;
		}
		unset( self::$recorded_approvals[ $record_key ] );
		return true;
	}

	/**
	 * Preview/commit controls change at dispatch; every other reviewed value,
	 * including the idempotency key, stays bound. Top-level key order is free.
	 *
	 * @param array<string,mixed> $input Ability input.
	 * @return array<string,mixed>
	 */
	private static function reviewed_values( array $input ) {
		unset( $input['dry_run'], $input['commit'] );
		ksort( $input );
		return $input;
	}
}

add_action(
	'plugins_loaded',
	static function () {
		if ( ! function_exists( 'npcink_abilities_toolkit_get_registered' ) ) {
			return;
		}

		Npcink_Abilities_Toolkit_Example_Host_Runtime::boot();

		$abilities  = npcink_abilities_toolkit_get_registered();
		$ability_id = 'npcink-abilities-toolkit/create-draft';
		if ( ! isset( $abilities[ $ability_id ] ) || ! is_array( $abilities[ $ability_id ] ) ) {
			return;
		}

		$ability = $abilities[ $ability_id ];
		if ( 'write' !== (string) ( $ability['risk_level'] ?? '' ) || true !== (bool) ( $ability['requires_approval'] ?? false ) ) {
			return;
		}

		$idempotency_key = 'example-host-runtime-draft-001';
		$preview_payload = array(
			'ability_id' => $ability_id,
			'stage'      => 'dry_run_preview',
			'input'      => array(
				'title'           => 'Draft previewed by an independent host runtime',
				'content'         => '<p>The host shows this preview on its own approval surface.</p>',
				'dry_run'         => true,
				'idempotency_key' => $idempotency_key,
			),
			'governance' => array(
				'host_source'          => 'example-third-party-host',
				'approval_owner'       => 'host_governance_layer',
				'commit_authorization' => 'npcink_abilities_toolkit_write_commit_allowed filter after a recorded approval',
			),
		);

		/**
		 * A real host would:
		 *
		 * 1. show the dry-run preview on its own approval surface;
		 * 2. record its decision with
		 *    Npcink_Abilities_Toolkit_Example_Host_Runtime::record_approval( $ability_id, $reviewed_input, get_current_user_id(), time() + 300 )
		 *    only after its governance checks pass;
		 * 3. re-run the ability with commit=true, dry_run=false, and the same
		 *    idempotency_key. The Toolkit commit gate then asks this filter, and
		 *    the final write happens under the host's authority and audit trail.
		 *
		 * This example never executes that commit itself.
		 */
		do_action( 'npcink_abilities_toolkit_example_host_preview_payload', $preview_payload, $ability );
	}
);
