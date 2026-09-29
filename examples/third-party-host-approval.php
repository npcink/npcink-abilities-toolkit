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
 * nothing until this host records an approval decision for one ability id and
 * idempotency key pair. The file itself never performs a final write, never
 * stores approvals in the database, and never handles provider credentials.
 *
 * @package NpcinkAbilitiesToolkitExample
 */

final class Npcink_Abilities_Toolkit_Example_Host_Runtime {

	/**
	 * Approval decisions this example host recorded in memory:
	 * "<ability_id>:<idempotency_key>" => true.
	 *
	 * @var array<string,true>
	 */
	private static $recorded_approvals = array();

	/**
	 * Registers the Toolkit commit decision filter.
	 *
	 * @return void
	 */
	public static function boot() {
		if ( ! function_exists( 'add_filter' ) ) {
			return;
		}

		add_filter( 'npcink_abilities_toolkit_write_commit_allowed', array( __CLASS__, 'decide_commit' ), 10, 4 );
	}

	/**
	 * Records this host's own approval decision after its governance flow passes.
	 *
	 * A real host derives this decision from its approval records, caller
	 * identity, and scope checks. The contract point is that the decision is
	 * recorded by the host before the commit attempt, never inferred from the
	 * write request itself.
	 *
	 * @param string $ability_id Ability id.
	 * @param string $idempotency_key Host idempotency key for replay protection.
	 * @return void
	 */
	public static function record_approval( $ability_id, $idempotency_key ) {
		$idempotency_key = (string) $idempotency_key;
		if ( '' === $idempotency_key ) {
			return;
		}

		self::$recorded_approvals[ (string) $ability_id . ':' . $idempotency_key ] = true;
	}

	/**
	 * Fail-closed commit decision for the Toolkit write gate.
	 *
	 * @param bool                $allowed Whether another runtime already authorized this commit.
	 * @param string              $ability_id Ability id.
	 * @param array<string,mixed> $input Write input, including idempotency_key.
	 * @param array<string,mixed> $runtime_context Host runtime context from the global channel.
	 * @return bool True only when this host recorded an approval for this ability and key.
	 */
	public static function decide_commit( $allowed, $ability_id, $input, $runtime_context ) {
		if ( $allowed ) {
			// Another host runtime already authorized this commit; this host does not veto it.
			return true;
		}

		$idempotency_key = is_array( $input ) ? (string) ( $input['idempotency_key'] ?? '' ) : '';
		if ( '' === $idempotency_key ) {
			// No replay-protection evidence, so the commit stays rejected.
			return false;
		}

		return isset( self::$recorded_approvals[ (string) $ability_id . ':' . $idempotency_key ] );
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
		 *    Npcink_Abilities_Toolkit_Example_Host_Runtime::record_approval( $ability_id, $idempotency_key )
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
