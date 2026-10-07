#!/usr/bin/env bash
# Closeout check: verifies the documented post-merge steady state from
# AGENTS.md so cleanup does not depend on human memory.
#
# Passes only when:
#   - the main worktree is clean, on master, and level with origin/master
#     (remote state is fail-closed: unverifiable is not level);
#   - no fully-merged local topic branches remain;
#   - no remote codex/, docs/, or dependabot/ branches remain that have no
#     open pull request (squash-merged leftovers; the platform deletes
#     branches it auto-merged, so survivors are stale by definition).
#
# Auxiliary worktrees are listed for information only: this repository
# commonly uses more than one worktree during development.
#
# Compatible with macOS bash 3.2: no mapfile, no associative arrays, no
# process substitution required for the counting loops.
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
if [ -z "$BRANCH" ]; then
	fail "detached HEAD; check out master before calling the stage closed."
elif [ "$BRANCH" != "master" ]; then
	fail "current branch is '${BRANCH}', not master."
fi

# 3. Level with origin/master. Fail closed: if the remote state cannot be
# verified, the stage is not closed.
if ! git fetch --quiet origin master 2>/dev/null; then
	fail "cannot fetch origin/master; remote sync is unverified."
elif ! git rev-parse --verify --quiet origin/master >/dev/null; then
	fail "origin/master is unavailable after fetch; remote sync is unverified."
else
	AHEAD="$(git rev-list --count origin/master..master)"
	BEHIND="$(git rev-list --count master..origin/master)"
	[ "$AHEAD" -eq 0 ] || fail "master is ${AHEAD} commit(s) ahead of origin/master; push or open the PR before calling the stage closed."
	[ "$BEHIND" -eq 0 ] || fail "master is ${BEHIND} commit(s) behind origin/master; fast-forward before calling the stage closed."
fi

# 4. Merged local topic branches. The current branch is excluded: a freshly
# created topic branch without its own commits trivially counts as merged,
# and a branch you are standing on cannot be deleted anyway. Fixed-string
# matching keeps branch names with regex metacharacters literal.
MERGED_LOCAL="$(git branch --merged master 2>/dev/null | sed 's/^[*+ ]*//' | grep -v -F -x 'master' | grep -v -F -x "${BRANCH:-__none__}" || true)"
if [ -n "$MERGED_LOCAL" ]; then
	fail "merged local branches remain (delete with git branch -d):"
	while IFS= read -r merged_branch; do
		[ -n "$merged_branch" ] && printf '  %s\n' "$merged_branch" >&2
	done <<< "$MERGED_LOCAL"
fi

# 5. Stale remote topic branches (no open pull request). gh resolves the
# repository from the origin remote itself, so forked URLs, ports, and
# non-github.com hosts keep working without parsing the remote URL. One call
# lists every open pull-request head; a gh failure degrades to an
# informational note instead of a stale verdict, because a wrong stale
# verdict recommends a destructive deletion.
git fetch --quiet --prune origin 2>/dev/null || true
REMOTE_TOPICS="$(git ls-remote --heads origin 2>/dev/null | awk '{ print $2 }' | sed 's|^refs/heads/||' | grep -E '^(codex|docs|dependabot)/' || true)"
if [ -n "$REMOTE_TOPICS" ]; then
	if command -v gh >/dev/null 2>&1; then
		if ! OPEN_HEADS="$(gh pr list --state open --limit 100 --json headRefName --jq '.[].headRefName' 2>/dev/null)"; then
			info "gh could not list pull requests; verify remote topic branches manually."
			REMOTE_TOPICS=""
		fi
	else
		info "gh is unavailable; verify remote topic branches manually."
		REMOTE_TOPICS=""
	fi
fi
if [ -n "$REMOTE_TOPICS" ]; then
	while IFS= read -r remote_branch; do
		[ -n "$remote_branch" ] || continue
		if printf '%s\n' "$OPEN_HEADS" | grep -q -F -x "$remote_branch"; then
			info "remote branch '${remote_branch}' still has an open pull request; leave it."
		else
			fail "remote branch '${remote_branch}' has no open pull request; delete it or open a PR preserving its commits: git push origin --delete ${remote_branch}"
		fi
	done <<< "$REMOTE_TOPICS"
fi

# Informational: auxiliary worktrees.
WORKTREE_COUNT="$(git worktree list --porcelain 2>/dev/null | grep -c '^worktree ' || true)"
WORKTREE_COUNT="${WORKTREE_COUNT:-0}"
[ "$WORKTREE_COUNT" -gt 1 ] && info "$WORKTREE_COUNT worktrees registered; remove only clean auxiliary ones whose branches are fully merged."

if [ "$FAILURES" -gt 0 ]; then
	echo "[closeout-check] ${FAILURES} check(s) failed." >&2
	exit 1
fi

echo "[closeout-check] steady state verified: clean master level with origin, no merged local leftovers, no stale remote topic branches."
