# Temporary replacement file groups v1

Operator-approved scope, 2026-11-13: new single-image Cloud adoption may opt into
`backup_lifecycle=temporary_7_days.v1`. Omission preserves the legacy contract.
Adapter passes this optional field only through the explicit adoption profile;
Core approval/preflight and Toolkit commit authorization remain required.

Toolkit freezes the source main file, metadata-owned sizes and original_image
(maximum 64 regular local files) with device/inode/size/SHA-256 in the existing
replacement history. Files stay at their original paths for seven days; no extra
original copy is created. Up to 50 pending groups per attachment are allowed;
pending groups survive the legacy 20-row history display cap.

Existing list-media-backups adds a temporary_backup receipt: policy, state,
expires_at_gmt, restorable, removed_bytes, retained_bytes and blocked_reasons.
Restore eligibility is advisory until the restore ability revalidates every
file and deadline. Restore reuses the exact saved original metadata/sizes and
marks that group restored, outside cleanup scope. The superseded current version
gets a new seven-day group. Recognized article thumbnail references still
consolidate to the main URL; restore is not byte-exact post-text undo.

Existing daily backup maintenance deletes only recorded files after expiry.
A nonblocking uploads flock serializes cooperative Cloud adoption, restore and
cleanup. Each delete checks canonical containment, parent/file symlinks,
hardlinks, file identity/size/hash, current metadata, other attachment uses,
other active recoveries, and exact uploads-relative path variants in local
posts/meta/options. Candidate scans fail closed above 500 rows or on DB errors.
A final wp_delete_file filter rejects redirected deletion and rechecks state;
failed deletion remains retryable. History updates use a previous-value guard.
Uncooperative external filesystem/database writers are not globally locked.

Blocked reasons are another_attachment, another_recovery, local_reference,
reference_check_unavailable, file_changed, state_changed and delete_failed.
Changed/unsafe files are retained; actual successful deletes alone increment
removed_bytes. Missing files add no claimed savings. Current files become
terminal current_image entries, and restored groups never delete their files.
Repeated maintenance is idempotent. Cron may run after the seven-day deadline;
that deadline ends recovery, not a promise of immediate cleanup. Reported bytes
are deleted logical file sizes, not physical disk accounting. External/CDN and
arbitrary plugin-owned references are outside detection; users review these
before replacement. Historical backup directory and retention/manual policies
are unchanged.

No new schedule, queue, Cloud storage owner, batch restore, orphan scan or
storage-management surface is added. The replacement/adoption/temporary traits
are extracted from Core/Cloud/backup traits to respect existing file ceilings;
public ability ids and existing legacy contracts remain unchanged.

Verification: test:temporary-media-backup covers actual disposable files,
seven-day eligibility, references/query failure, identity drift, symlink/hardlink
rejection, actual partial bytes, idempotence, current-file protection and history
retention. tests/smoke-temporary-media-backup-live.php is explicit opt-in, scopes
the public maintenance query to its own fixture, restores the original cursor
and tests installed WP database/delete filters. Local evidence is not production
or natural seven-day Cron observation.
