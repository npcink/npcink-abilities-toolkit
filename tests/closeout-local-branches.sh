#!/usr/bin/env bash
set -euo pipefail
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TASK_DIR="$(mktemp -d "${TMPDIR:-/tmp}/npcink-closeout-test.XXXXXX")"
trap 'rm -rf "$TASK_DIR"' EXIT
mkdir -p "$TASK_DIR/bin" "$TASK_DIR/repo/scripts"
cp "$ROOT_DIR/scripts/check-local-branch-closeout.php" "$TASK_DIR/repo/scripts/"
git init --quiet --bare "$TASK_DIR/remote.git"
git init --quiet -b master "$TASK_DIR/repo"
git -C "$TASK_DIR/repo" config user.name 'Closeout Regression'
git -C "$TASK_DIR/repo" config user.email 'closeout@example.invalid'
git -C "$TASK_DIR/repo" add scripts/check-local-branch-closeout.php
git -C "$TASK_DIR/repo" commit --quiet -m baseline
git -C "$TASK_DIR/repo" remote add origin "$TASK_DIR/remote.git"
git -C "$TASK_DIR/repo" switch --quiet -c codex/squash
printf 'topic payload\n' > "$TASK_DIR/repo/payload.txt"
git -C "$TASK_DIR/repo" add payload.txt
git -C "$TASK_DIR/repo" commit --quiet -m topic
topic_head="$(git -C "$TASK_DIR/repo" rev-parse HEAD)"
git -C "$TASK_DIR/repo" switch --quiet master
git -C "$TASK_DIR/repo" merge --squash codex/squash >/dev/null
git -C "$TASK_DIR/repo" commit --quiet -m 'squash topic'
git -C "$TASK_DIR/repo" push --quiet -u origin master
merged_revision="$(git -C "$TASK_DIR/repo" rev-parse HEAD)"
refs_before="$(git -C "$TASK_DIR/repo" for-each-ref --format='%(refname) %(objectname)')"
cat > "$TASK_DIR/bin/gh" <<'FAKE'
#!/usr/bin/env bash
set -euo pipefail
if [[ "${CLOSEOUT_TEST_FAILURE:-}" == "network" ]]; then exit 1; fi
if [[ "${1:-}" == "repo" ]]; then printf 'fixture/toolkit\n'; exit 0; fi
cat "$CLOSEOUT_TEST_RECEIPTS"
FAKE
chmod +x "$TASK_DIR/bin/gh"
export PATH="$TASK_DIR/bin:$PATH"
export CLOSEOUT_TEST_RECEIPTS="$TASK_DIR/receipts.json"
checks=0
run_check() {
 local expected="$1" description="$2" status=0
 php "$TASK_DIR/repo/scripts/check-local-branch-closeout.php" > "$TASK_DIR/result.log" 2>&1 || status=$?
 if [[ "$status" != "$expected" ]]; then
  cat "$TASK_DIR/result.log" >&2
  echo "FAIL: $description (expected $expected, got $status)" >&2
  exit 1
 fi
 checks=$((checks + 1))
}
write_receipt() {
 python3 - "$CLOSEOUT_TEST_RECEIPTS" "$topic_head" "${1:-$merged_revision}" "${2:-master}" "${3:-fixture/toolkit}" "${4:-merged}" <<'PY'
import json,sys
path,head,merge,base,repo,state=sys.argv[1:]
pr={'number':42,'head':{'sha':head},'base':{'ref':base,'repo':{'full_name':repo}},'merged_at':'2026-11-02T00:00:00Z' if state=='merged' else None,'merge_commit_sha':merge}
with open(path,'w') as f: json.dump([[pr]],f)
PY
}
printf '[[]]\n' > "$CLOSEOUT_TEST_RECEIPTS"
run_check 0 'unpublished topic is preserved'
write_receipt
run_check 1 'squash-merged exact head blocks closeout'
grep -Fq "head=$topic_head remains after merged PR #42 merge=$merged_revision" "$TASK_DIR/result.log"
write_receipt "$topic_head"
run_check 1 'receipt must identify a merge on origin/master'
write_receipt "$merged_revision" release
run_check 0 'merge into another base does not mark master cleanup complete'
write_receipt "$merged_revision" master another/repo
run_check 0 'receipt from a different repository cannot mark this branch merged'
write_receipt "$merged_revision" master fixture/toolkit open
run_check 0 'open pull request preserves its local head'
write_receipt
python3 - "$CLOSEOUT_TEST_RECEIPTS" "$merged_revision" <<'PY'
import json,sys
p=sys.argv[1]
a=json.load(open(p)); a[0][0]['head']['sha']=sys.argv[2]
with open(p,'w') as f: json.dump(a,f)
PY
run_check 0 'a matching branch name with a different head is insufficient'
write_receipt
python3 - "$CLOSEOUT_TEST_RECEIPTS" <<'PY'
import json,sys
p=sys.argv[1]; a=json.load(open(p))
with open(p,'w') as f: json.dump([[],a[0]],f)
PY
run_check 1 'receipt on a later API page is detected'
printf '{}\n' > "$CLOSEOUT_TEST_RECEIPTS"
run_check 1 'malformed API shape fails closed'
export CLOSEOUT_TEST_FAILURE=network
run_check 1 'unavailable GitHub lookup cannot claim successful cleanup'
unset CLOSEOUT_TEST_FAILURE
[[ "$refs_before" == "$(git -C "$TASK_DIR/repo" for-each-ref --format='%(refname) %(objectname)')" ]]
# Ancestry-only heads remain the shell gate's responsibility and need no API call.
git clone --quiet --branch master "$TASK_DIR/remote.git" "$TASK_DIR/ancestry"
git -C "$TASK_DIR/ancestry" branch codex/ancestor
export CLOSEOUT_TEST_FAILURE=network
php "$TASK_DIR/ancestry/scripts/check-local-branch-closeout.php" > "$TASK_DIR/result.log" 2>&1
unset CLOSEOUT_TEST_FAILURE
checks=$((checks + 1))
echo "OK: $checks local branch closeout cases; refs unchanged"
