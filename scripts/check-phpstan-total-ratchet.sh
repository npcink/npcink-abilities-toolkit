#!/usr/bin/env bash
# PHPStan level-4 total ratchet: the repository-wide level-4 error count
# must not exceed the recorded baseline. The per-PR changed-files ratchet
# (check-phpstan-ratchet.sh) already blocks new debt in touched files; this
# release-facing gate additionally locks the global backlog floor so debt
# cannot creep back through configuration changes or accumulated drift, and
# burn-down slices ratchet the baseline downward at release time.
#
# Compatible with macOS bash 3.2: no mapfile, no associative arrays.
#
# Run with --update to record the current count as the new baseline after a
# verified burn-down or an accepted baseline reset; baseline diffs are
# reviewed like any other change.
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

BASELINE_FILE="tests/fixtures/phpstan-level4-total-baseline.txt"
UPDATE=0
for arg in "$@"; do
	case "$arg" in
		--update) UPDATE=1 ;;
		*) echo "[phpstan-total-ratchet] unknown argument: $arg" >&2; exit 2 ;;
	esac
done

if [ ! -x vendor/bin/phpstan ]; then
	echo "[phpstan-total-ratchet] vendor/bin/phpstan is not installed; skipping." >&2
	exit 0
fi

RAW_REPORT="$(mktemp "${TMPDIR:-/tmp}/npcink-phpstan-total.XXXXXX")"
cleanup() {
	rm -f -- "$RAW_REPORT"
}
trap cleanup EXIT

# Exit 0/1 mean a completed analysis (errors found is still a result); any
# other status is a tool failure whose empty stdout must not count as zero.
set +e
vendor/bin/phpstan analyse \
	--level=4 \
	--memory-limit=4G \
	--no-progress \
	--error-format=raw \
	--configuration=phpstan.neon.dist \
	2>/dev/null > "$RAW_REPORT"
PHPSTAN_STATUS=$?
set -e

if [ "$PHPSTAN_STATUS" -ne 0 ] && [ "$PHPSTAN_STATUS" -ne 1 ]; then
	echo "[phpstan-total-ratchet] phpstan exited with status ${PHPSTAN_STATUS}; failing closed instead of counting an empty report." >&2
	exit 1
fi

COUNT="$(grep -c '^' "$RAW_REPORT" || true)"

if [ "$UPDATE" -eq 1 ]; then
	printf '%s\n' "$COUNT" > "$BASELINE_FILE"
	echo "[phpstan-total-ratchet] baseline rewritten to ${COUNT}; the number must be reviewed in the pull request diff."
	exit 0
fi

if [ ! -f "$BASELINE_FILE" ]; then
	echo "[phpstan-total-ratchet] missing baseline ${BASELINE_FILE}; run: composer check:phpstan-total -- --update" >&2
	exit 1
fi

BASELINE="$(tr -d '[:space:]' < "$BASELINE_FILE")"
if ! printf '%s' "$BASELINE" | grep -Eq '^[0-9]+$'; then
	echo "[phpstan-total-ratchet] baseline is not a non-negative integer: ${BASELINE}" >&2
	exit 1
fi

if [ "$COUNT" -gt "$BASELINE" ]; then
	echo "[phpstan-total-ratchet] level-4 total grew: ${COUNT} > baseline ${BASELINE}." >&2
	echo "[phpstan-total-ratchet] fix the new findings, or rerun with --update only after an accepted decision." >&2
	exit 1
fi

echo "[phpstan-total-ratchet] level-4 total ${COUNT} within baseline ${BASELINE}."
