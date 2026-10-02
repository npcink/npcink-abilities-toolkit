#!/usr/bin/env bash
set -euo pipefail

SVN_WC="${1:-}"

if [[ -z "$SVN_WC" || ! -d "$SVN_WC/.svn" ]]; then
	echo "Usage: check-wporg-svn-freshness.sh SVN_WORKING_COPY" >&2
	exit 2
fi

svn_error_file="$(mktemp)"
if ! remote_status="$(LC_ALL=C svn status -u --non-interactive "$SVN_WC" 2>"$svn_error_file")"; then
	echo "svn status -u failed while verifying: $SVN_WC" >&2
	cat "$svn_error_file" >&2
	echo "One common cause is the WordPress.org SVN remote being unreachable; check network access to plugins.svn.wordpress.org." >&2
	rm -f "$svn_error_file"
	exit 1
fi
rm -f "$svn_error_file"

# svn status -u marks out-of-date paths with "*" in column 9.
stale_paths="$(printf '%s\n' "$remote_status" | awk 'substr($0, 9, 1) == "*" { print }')"
if [[ -n "$stale_paths" ]]; then
	echo "SVN working copy is stale relative to the WordPress.org remote:" >&2
	printf '%s\n' "$stale_paths" >&2
	echo "Refresh it before preparing a release:" >&2
	echo "  svn update \"$SVN_WC\"" >&2
	echo "or replace it with a fresh checkout of the plugin repository:" >&2
	echo "  rm -rf \"$SVN_WC\" && svn checkout <repository-url> \"$SVN_WC\"" >&2
	exit 1
fi

head_revision="$(printf '%s\n' "$remote_status" | awk '/^Status against revision:/ { print $NF }')"
if [[ -z "$head_revision" ]]; then
	echo "Could not read the remote HEAD revision from svn status -u output." >&2
	exit 1
fi

echo "WordPress.org SVN working copy is current with remote revision $head_revision."
