<?php
/** Detects merged local heads that ancestry-only cleanup misses. */
declare(strict_types=1);
chdir( dirname( __DIR__ ) );

/** @param array<int,string> $args @return array{int,string} */
function closeout_command( array $args ): array {
	$lines = array();
	$status = 0;
	exec( implode( ' ', array_map( 'escapeshellarg', $args ) ) . ' 2>&1', $lines, $status );
	return array( $status, trim( implode( "\n", $lines ) ) );
}

/** @param string $message @return void */
function closeout_error( string $message ): void {
	fwrite( STDERR, '[local-branch-closeout] FAIL: ' . $message . "\n" );
}

list( $status, $current ) = closeout_command( array( 'git', 'branch', '--show-current' ) );
if ( 0 !== $status ) { closeout_error( 'cannot resolve the current branch' ); exit( 1 ); }
list( $status, $refs ) = closeout_command( array( 'git', 'for-each-ref', '--format=%(refname:short)%09%(objectname)', 'refs/heads/' ) );
if ( 0 !== $status ) { closeout_error( 'cannot inventory local branches' ); exit( 1 ); }
$candidates = array();
foreach ( explode( "\n", $refs ) as $ref ) {
	$parts = explode( "\t", $ref );
	if ( 2 !== count( $parts ) ) { continue; }
	list( $branch, $head ) = $parts;
	if ( 'master' === $branch || $current === $branch ) { continue; }
	list( $ancestor ) = closeout_command( array( 'git', 'merge-base', '--is-ancestor', $head, 'origin/master' ) );
	if ( 0 === $ancestor ) { continue; } // The shell gate handles ancestry-merged branches.
	if ( 1 !== $ancestor ) { closeout_error( 'cannot compare ' . $branch . ' with origin/master' ); exit( 1 ); }
	$candidates[ $branch ] = $head;
}
if ( array() === $candidates ) { echo "[local-branch-closeout] no non-ancestry local heads to verify.\n"; exit( 0 ); }
list( $status, $repo ) = closeout_command( array( 'gh', 'repo', 'view', '--json', 'nameWithOwner', '--jq', '.nameWithOwner' ) );
if ( 0 !== $status || ! preg_match( '#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#D', $repo ) ) {
	closeout_error( 'cannot verify PR receipts for local heads; GitHub repository lookup failed' ); exit( 1 );
}
$failed = false;
foreach ( $candidates as $branch => $head ) {
	list( $status, $json ) = closeout_command( array( 'gh', 'api', '--paginate', '--slurp', 'repos/' . $repo . '/commits/' . $head . '/pulls' ) );
	$pages = 0 === $status ? json_decode( $json ) : null;
	if ( ! is_array( $pages ) ) {
		closeout_error( $branch . ': exact-head PR receipts could not be read' ); $failed = true; continue;
	}
	$matched = false;
	foreach ( $pages as $page ) {
		if ( ! is_array( $page ) ) {
			closeout_error( $branch . ': malformed PR receipt page' ); $failed = true; continue;
		}
		foreach ( $page as $pr ) {
			if ( ! is_object( $pr ) || ! isset( $pr->head->sha, $pr->base->ref, $pr->base->repo->full_name ) || ! property_exists( $pr, 'merged_at' ) ) {
				closeout_error( $branch . ': malformed PR receipt' ); $failed = true; continue;
			}
			if ( $head !== $pr->head->sha || empty( $pr->merged_at ) || 'master' !== $pr->base->ref || $repo !== $pr->base->repo->full_name ) { continue; }
			$matched = true;
			$merge = (string) ( $pr->merge_commit_sha ?? '' );
			list( $ancestor ) = preg_match( '/^[0-9a-f]{40}$/D', $merge ) ? closeout_command( array( 'git', 'merge-base', '--is-ancestor', $merge, 'origin/master' ) ) : array( 2 );
			if ( 0 !== $ancestor ) {
				closeout_error( $branch . ': merged PR receipt is not verified on origin/master' );
			} else {
				closeout_error( $branch . ' head=' . $head . ' remains after merged PR #' . (string) ( $pr->number ?? '?' ) . ' merge=' . $merge . '; preserve the receipt/bundle and use the documented safe-deletion rule' );
			}
			$failed = true;
		}
	}
	if ( ! $matched ) { echo '[local-branch-closeout] note: ' . $branch . " has no matching merged master PR; preserve its work.\n"; }
}
exit( $failed ? 1 : 0 );
