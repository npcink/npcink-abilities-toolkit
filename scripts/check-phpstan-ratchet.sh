#!/usr/bin/env bash
# PHPStan level-4 ratchet: changed analyzed files must not increase the
# level-4 error count against the base revision.
#
# The repository analyzes PHP at level 3 (see phpstan.neon.dist); level 4
# carries a large pre-existing backlog recorded in
# docs/structural-split-plan.md. This gate compares, for every PHP file the
# change touches under the analyzed paths, the level-4 error count at the
# base revision against the count at HEAD, and fails when any count grows.
# Existing debt never blocks a change; new debt does.
#
# Files newly added at HEAD have no base count, so a mechanical move that
# relocates existing findings into a new file would read as growth. For
# those, the per-file check is skipped and a conservation rule applies
# instead: the total count across all changed files must not exceed the
# base total. A pure move preserves the total; genuinely new findings in a
# new file still fail the conservation rule.
#
# Compatible with macOS bash 3.2: no mapfile, no associative arrays.
#
# Set PHPSTAN_RATCHET_BASE to override the comparison base (default
# origin/master). When the base ref or vendor dependencies are unavailable
# the gate skips with exit 0 so it can run as a non-blocking CI leg.
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

BASE="${PHPSTAN_RATCHET_BASE:-origin/master}"
NEXT_LEVEL=4

git fetch --quiet origin master 2>/dev/null || true
if ! git rev-parse --verify --quiet "$BASE" >/dev/null; then
	echo "[phpstan-ratchet] base ref $BASE is not available; skipping." >&2
	exit 0
fi
if [ ! -x vendor/bin/phpstan ]; then
	echo "[phpstan-ratchet] vendor/bin/phpstan is not installed; skipping." >&2
	exit 0
fi

CHANGED_LIST="$(mktemp "${TMPDIR:-/tmp}/npcink-ratchet-changed.XXXXXX")"
HEAD_COUNTS="$(mktemp "${TMPDIR:-/tmp}/npcink-ratchet-head.XXXXXX")"
BASE_COUNTS="$(mktemp "${TMPDIR:-/tmp}/npcink-ratchet-base.XXXXXX")"
BASE_FILES=""
BASE_WORKTREE=""
cleanup() {
	# Remove unconditionally: on macOS the mktemp path (/var/folders/...) and
	# the canonical porcelain path (/private/var/folders/...) differ, so a
	# grep guard on the listed path silently skipped removal.
	if [ -n "$BASE_WORKTREE" ] && [ -d "$BASE_WORKTREE" ]; then
		git -C "$ROOT_DIR" worktree remove --force "$BASE_WORKTREE" >/dev/null 2>&1 || true
		git -C "$ROOT_DIR" worktree prune >/dev/null 2>&1 || true
	fi
	rm -f -- "$CHANGED_LIST" "$HEAD_COUNTS" "$BASE_COUNTS"
}
trap cleanup EXIT

while IFS= read -r changed_path; do
	case "$changed_path" in
		includes/*|npcink-abilities-toolkit.php) printf '%s\n' "$changed_path" >> "$CHANGED_LIST" ;;
	esac
done < <(git diff --name-only --diff-filter=ACMRT "$BASE"...HEAD -- '*.php')

if [ ! -s "$CHANGED_LIST" ]; then
	echo "[phpstan-ratchet] no analyzed PHP files changed against $BASE; skipping."
	exit 0
fi

# Writes "<relative path> <error count>" lines for the given files.
# Runs in the current directory's tree with its own vendor and config.
write_counts() {
	local counts_file="$1"
	shift
	local report
	report="$(vendor/bin/phpstan analyse \
		--level="$NEXT_LEVEL" \
		--memory-limit=4G \
		--no-progress \
		--error-format=json \
		--configuration=phpstan.neon.dist \
		"$@" 2>/dev/null || true)"

	if [ -z "$report" ]; then
		return 1
	fi

	local analyzed_file
	for analyzed_file in "$@"; do
		local relative_path
		relative_path="${analyzed_file#./}"
		local count
		count="$(printf '%s' "$report" | php -r '
			$report = json_decode( (string) stream_get_contents( STDIN ), true );
			if ( ! is_array( $report ) ) { echo "0"; exit; }
			$relative = $argv[1];
			$count = 0;
			foreach ( $report["files"] ?? array() as $path => $file ) {
				// Path keys may be absolute; match by exact suffix plus containment.
				if ( false !== strpos( $path, $relative ) && substr( $path, -strlen( $relative ) ) === $relative ) {
					$entry = $file["messages"] ?? $file["errors"] ?? array();
					$count += is_array( $entry ) ? count( $entry ) : (int) $entry;
				}
			}
			echo (string) $count;
		' "$relative_path")"
		printf '%s %s\n' "$relative_path" "$count" >> "$counts_file"
	done
}

# HEAD counts.
cd "$ROOT_DIR"
HEAD_FILES="$(cat "$CHANGED_LIST" | sed 's/^/.\//' | tr '\n' ' ')"
# shellcheck disable=SC2086
if ! write_counts "$HEAD_COUNTS" $HEAD_FILES; then
	echo "[phpstan-ratchet] phpstan produced no report at HEAD; failing closed." >&2
	exit 1
fi

# Base counts in a detached worktree of the base revision.
BASE_WORKTREE="$(mktemp -d "${TMPDIR:-/tmp}/npcink-toolkit-ratchet.XXXXXX")"
# Canonicalize so later paths match git's porcelain output.
BASE_WORKTREE="$(cd "$BASE_WORKTREE" && pwd -P)"
git worktree add --detach --quiet "$BASE_WORKTREE" "$BASE^{commit}"

if ( cd "$BASE_WORKTREE" && composer install --quiet --no-interaction --no-progress >/dev/null 2>&1 ); then
	while IFS= read -r changed_file; do
		if git -C "$BASE_WORKTREE" cat-file -e "HEAD:$changed_file" 2>/dev/null; then
			BASE_FILES="$BASE_FILES ./$changed_file"
		fi
	done < "$CHANGED_LIST"
	if [ -n "$BASE_FILES" ]; then
		# shellcheck disable=SC2086
		( cd "$BASE_WORKTREE" && write_counts "$BASE_COUNTS" $BASE_FILES ) || true
	fi
else
	echo "[phpstan-ratchet] composer install failed in the base worktree; comparing against zero baseline." >&2
fi

FAILED=0
BASE_TOTAL=0
HEAD_TOTAL=0
printf '[phpstan-ratchet] level %s counts against %s:\n' "$NEXT_LEVEL" "$BASE"
while IFS= read -r changed_file; do
	head_count="$(awk -v f="$changed_file" '$1 == f { print $2 }' "$HEAD_COUNTS")"
	base_raw="$(awk -v f="$changed_file" '$1 == f { print $2 }' "$BASE_COUNTS")"
	head_count="${head_count:-0}"
	base_count="${base_raw:-0}"
	HEAD_TOTAL=$(( HEAD_TOTAL + head_count ))
	BASE_TOTAL=$(( BASE_TOTAL + base_count ))

	verdict="ok"
	if [ -z "$base_raw" ]; then
		# New at HEAD: guarded by the conservation rule below, not per-file.
		verdict="new (total-guarded)"
	elif [ "$head_count" -gt "$base_count" ]; then
		verdict="GREW"
		FAILED=1
	fi
	printf '  %-64s base=%-4s head=%-4s %s\n' "$changed_file" "$base_count" "$head_count" "$verdict"
done < "$CHANGED_LIST"

if [ "$HEAD_TOTAL" -gt "$BASE_TOTAL" ]; then
	echo "[phpstan-ratchet] FAIL: changed-file level $NEXT_LEVEL total grew: head $HEAD_TOTAL > base $BASE_TOTAL." >&2
	FAILED=1
fi

if [ "$FAILED" -ne 0 ]; then
	echo "[phpstan-ratchet] FAIL: level $NEXT_LEVEL error count grew in changed files." >&2
	exit 1
fi

echo "[phpstan-ratchet] ok: no changed file increased its level $NEXT_LEVEL error count (changed-file total $HEAD_TOTAL <= base $BASE_TOTAL)."
