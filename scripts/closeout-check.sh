#!/usr/bin/env bash
# Closeout check: verifies the documented post-merge steady state from
# AGENTS.md so cleanup does not depend on human memory.
#
# Passes only when:
#   - the main worktree is clean, on master, and level with origin/master;
#   - no fully-merged local topic branches remain;
#   - no remote codex/, docs/, or dependabot/ branches remain that have no
#     open pull request (squash-merged leftovers; the platform deletes
#     branches it auto-merged, so survivors are stale by definition).
#
# Auxiliary worktrees are listed for information only: this repository
# commonly uses more than one worktree during development.
#
# Compatible with macOS bash 3.2: no mapfile, no associative arrays.
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

FAILURES=0

fail() {
	echo "[closeout-check] FAIL: $*" >&2
	FAILURES=$(( FAILURES + 1 ))
}

info() {
	echo "[closeout-check] note: $*"
}

command -v git >/dev/null 2>&1 || { echo "[closeout-check] git is required" >&2; exit 1; }

# 1. Clean worktree.
if [ -n "$(git status --porcelain)" ]; then
	fail "worktree is dirty; commit, report, or clean unrelated edits explicitly."
	git status --short >&2
fi

# 2. On master.
BRANCH="$(git branch --show-current)"
if [ "$BRANCH" != "master" ]; then
	fail "current branch is '${BRANCH}', not master."
fi

# 3. Level with origin/master.
git fetch --quiet origin master 2>/dev/null || info "could not fetch origin/master; remote state unchecked."
AHEAD="$(git rev-list --count origin/master..master 2>/dev/null || echo 0)"
BEHIND="$(git rev-list --count master..origin/master 2>/dev/null || echo 0)"
[ "$AHEAD" -eq 0 ] || fail "master is ${AHEAD} commit(s) ahead of origin/master; push or open the PR before calling the stage closed."
[ "$BEHIND" -eq 0 ] || fail "master is ${BEHIND} commit(s) behind origin/master; fast-forward before calling the stage closed."

# 4. Merged local topic branches. The current branch is excluded: a freshly
# created topic branch without its own commits trivially counts as merged,
# and a branch you are standing on cannot be deleted anyway.
MERGED_LOCAL="$(git branch --merged master 2>/dev/null | sed 's/^[*+ ]*//' | grep -v '^master$' | grep -v "^${BRANCH:-}$" || true)"
if [ -n "$MERGED_LOCAL" ]; then
	fail "merged local branches remain (delete with git branch -d):"
	printf '  %s\n' $MERGED_LOCAL >&2
fi

# 5. Stale remote topic branches (no open pull request).
git fetch --quiet --prune origin 2>/dev/null || true
REMOTE_TOPICS="$(git ls-remote --heads origin 2>/dev/null | awk '{ print $2 }' | sed 's|^refs/heads/||' | grep -E '^(codex|docs|dependabot)/' || true)"
if [ -n "$REMOTE_TOPICS" ]; then
	if command -v gh >/dev/null 2>&1; then
		for rb in $REMOTE_TOPICS; do
			OPEN_PRS="$(gh pr list --repo npcink/npcink-abilities-toolkit --head "$rb" --state open --json number --jq 'length' 2>/dev/null || echo 0)"
			if [ "$OPEN_PRS" -eq 0 ]; then
				fail "remote branch '${rb}' has no open pull request; delete it or open a PR preserving its commits: git push origin --delete ${rb}"
			else
				info "remote branch '${rb}' still has an open pull request; leave it."
			fi
		done
	else
		for rb in $REMOTE_TOPICS; do
			info "remote topic branch '${rb}' present; verify it manually (gh unavailable): git push origin --delete ${rb}"
		done
	fi
fi

# Informational: auxiliary worktrees.
WORKTREE_COUNT="$(git worktree list --porcelain 2>/dev/null | grep -c '^worktree ' || true)"
[ "$WORKTREE_COUNT" -gt 1 ] && info "$WORKTREE_COUNT worktrees registered; remove only clean auxiliary ones whose branches are fully merged."

if [ "$FAILURES" -gt 0 ]; then
	echo "[closeout-check] ${FAILURES} check(s) failed." >&2
	exit 1
fi

echo "[closeout-check] steady state verified: clean master level with origin, no merged local leftovers, no stale remote topic branches."
