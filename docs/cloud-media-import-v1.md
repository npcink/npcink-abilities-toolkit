# Cloud image import as a new attachment

`npcink-abilities-toolkit/import-cloud-media-derivative` is separate from the
replacement ability `adopt-cloud-media-derivative`. It previews by default and
requires existing host approval context for commit. It never changes the source
attachment or repairs article references.

Input: source_attachment_id (editable existing image), canonical
expected_source_media_fingerprint, exact derivative_artifact. The artifact's
source checksum must match the current source file. Existing common dry_run,
commit and idempotency_key fields retain their normal meanings.

The host owns final approval and execution idempotency. Toolkit additionally
serializes imports with a synchronous nonblocking uploads lock and stores an
artifact identity on the new attachment, so another approved request for the
same source/artifact returns the existing id and verifies its saved bytes.
This is not a workflow runtime, lease, queue or approval record.

After Addon transfer and existing exact descriptor/byte/receipt verification,
Toolkit creates a private operation directory within the current WordPress
uploads year/month, inserts a new unattached image and generates normal sizes.
Only the new attachment's main-file scaling is disabled, preserving reviewed
bytes. A fixed WordPress core image helper is loaded when REST has not loaded
it; the wporg source guard permits that exact include only in this trait.

Provenance (_npcink_cloud_import and _npcink_cloud_import_key) belongs to the
new attachment. No source metadata, file, intermediate size, article body or
featured-image field is written. Failure deletes only this operation's new
attachment/files. If deletion is vetoed, files remain with recovery_required and
the new attachment id, rather than leaving an attachment pointing at deleted
files. Unexpected process death remains a host recovery concern.

Output: attachment_id, source_attachment_id, created, original_preserved,
idempotent_replay, artifact, url, edit_link and dry_run. A dry-run is not a saved
image. Saved results add disk usage; source deletion and storage management are
outside this contract. Cloud preview expiry is separate from WordPress storage.

Verification: deterministic filesystem/WordPress-double tests cover preview with
zero transfer, host denial, real new files and metadata, replay, source mismatch
and drift, expiry, corrupt transfer, metadata/provenance failures, failed cleanup
and unchanged source bytes/sizes/metadata/article content. Installed/live evidence
is recorded by the consuming Toolbox contract, separately from fixture evidence.
