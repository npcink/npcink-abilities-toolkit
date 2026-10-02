#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
GUARD="$ROOT_DIR/scripts/check-wporg-svn-freshness.sh"
TMPDIR_ROOT="$(mktemp -d)"
FAKE_BIN="$TMPDIR_ROOT/bin"
FAKE_WC="$TMPDIR_ROOT/wporg-svn-wc/npcink-abilities-toolkit"

cleanup() {
	rm -rf "$TMPDIR_ROOT"
}
trap cleanup EXIT

mkdir -p "$FAKE_BIN" "$FAKE_WC/.svn"

cat > "$FAKE_BIN/svn" <<SCRIPT
#!/usr/bin/env bash
mode="\${FAKE_SVN_MODE:?FAKE_SVN_MODE not set}"
case "\$mode" in
	fresh)
		printf 'Status against revision: 3723664\n'
		;;
	stale)
		printf 'M       *  3581125   trunk/readme.txt\n'
		printf '        *  3581121   trunk/includes\n'
		printf 'A  trunk/untracked-phantom.php\n'
		printf 'Status against revision: 3723624\n'
		;;
	star-in-path)
		printf 'M         3581125   trunk/foo*bar.php\n'
		printf 'Status against revision: 3723664\n'
		;;
	no-trailer)
		# Exit 0 with no status trailer at all.
		;;
	network-error)
		echo 'svn: E175013: Unable to connect to a repository at URL' >&2
		exit 1
		;;
	*)
		echo "unknown fake svn mode: \$mode" >&2
		exit 1
		;;
esac
SCRIPT
chmod +x "$FAKE_BIN/svn"

PATH="$FAKE_BIN:$PATH" FAKE_SVN_MODE=fresh bash "$GUARD" "$FAKE_WC" \
	| grep -q "current with remote revision 3723664" \
	|| { echo "Freshness guard did not accept a current working copy." >&2; exit 1; }

if PATH="$FAKE_BIN:$PATH" FAKE_SVN_MODE=stale bash "$GUARD" "$FAKE_WC" >/dev/null 2>"$TMPDIR_ROOT/stale.err"; then
	echo "Freshness guard accepted a stale working copy." >&2
	exit 1
fi
grep -q "stale relative to the WordPress.org remote" "$TMPDIR_ROOT/stale.err" \
	|| { echo "Stale rejection did not explain the failure." >&2; exit 1; }

if ! PATH="$FAKE_BIN:$PATH" FAKE_SVN_MODE=star-in-path bash "$GUARD" "$FAKE_WC" >/dev/null 2>&1; then
	echo "Freshness guard treated a literal '*' in a path as an out-of-date marker." >&2
	exit 1
fi

if PATH="$FAKE_BIN:$PATH" FAKE_SVN_MODE=no-trailer bash "$GUARD" "$FAKE_WC" >/dev/null 2>&1; then
	echo "Freshness guard accepted output without a remote revision trailer." >&2
	exit 1
fi

if PATH="$FAKE_BIN:$PATH" FAKE_SVN_MODE=network-error bash "$GUARD" "$FAKE_WC" >/dev/null 2>&1; then
	echo "Freshness guard accepted an unreachable SVN remote." >&2
	exit 1
fi

if bash "$GUARD" "$TMPDIR_ROOT/does-not-exist" >/dev/null 2>&1; then
	echo "Freshness guard accepted a missing working copy path." >&2
	exit 1
fi

if bash "$GUARD" >/dev/null 2>&1; then
	echo "Freshness guard accepted a missing working copy argument." >&2
	exit 1
fi

echo "OK: wporg svn freshness guard"
