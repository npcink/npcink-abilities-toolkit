# Media Format Attention Boundary

## Status

Accepted for the current media governance path.

## Context

`format_attention` is not ordinary media metadata. It can involve image
dimensions, compression, format conversion, derivative generation, file
replacement, backup, rollback, cache behavior, and attachment metadata
regeneration. Those concerns are heavier than `magick-ai/update-media-details`,
which only updates reviewable media text and source metadata.

Mapping `format_attention` directly to a write action inside
`magick-ai/build-media-inventory-fix-plan` would blur the boundary between a
read-only inventory plan and file-asset mutation.

## Decision

Keep `format_attention` in `manual_review` for now.

The media inventory fix plan may include read-only diagnostics such as:

- detected format reasons, for example `legacy_image_format`, `image_too_wide`,
  or `large_file`;
- suggested operation labels such as `generate_optimized_derivative`,
  `compress`, or `consider_format_conversion`;
- a future target ability hint such as
  `magick-ai/build-media-derivative-cloud-request`;
- explicit `write_action_generated=false`;
- high-risk classification for file-asset work.

It must not map `format_attention` to `magick-ai/update-media-details`, because
format work is not metadata-only.

It must not map `format_attention` directly to asset write abilities from the
read-only fix plan. Hosts may use the diagnostic output to start a separate
approval flow.

## Current Abilities-Side Cloud Handoff

`magick-ai/build-media-derivative-cloud-request` is the local abilities-side
handoff for Cloud derivative processing. It is intentionally read-only:

- it inspects the attachment and builds a bounded
  `generate_optimized_media_derivative` request contract;
- it does not upload the source file;
- it does not call Cloud;
- it does not include credentials, Authorization headers, signed headers,
  callback URLs, or presigned source URLs;
- it does not write WordPress media records or attachment metadata.

The Cloud addon or host transport layer owns upload, signing, and dispatch. The
Cloud worker owns derivative generation. The local WordPress host owns final
approval, recording, replacement, rollback, and metadata writes.

## Current Backup Storage Policy

Media operation backups are stored under `wp-content/uploads/magick-ai-backups/`
while preserving the current upload subdirectory under that base. For example,
an attachment main file in `2026/06/` receives backups in
`magick-ai-backups/2026/06/`.

This keeps operational rollback artifacts out of normal public month media
directories without relying on host-specific hidden directory behavior. Rollback
metadata stores the concrete uploads-relative backup path, so recovery should
read from recorded history rather than reconstructing a path from the current
filename. The decision is recorded in
[ADR 0003](adr/0003-media-backup-directory-policy.md).

## Future Ability Shape

If file-asset work becomes necessary, prefer separate abilities with narrow
contracts:

- `magick-ai/optimize-media-asset`: first candidate for resize/compress or
  optimized derivative generation. Keep original assets preserved.
- `magick-ai/convert-media-format`: format conversion such as WebP or AVIF.
  This should start as derivative generation, not direct replacement.
- `magick-ai/replace-media-file`: highest-risk path for switching an
  attachment file to an approved derivative. Require backup, approval, and
  strict preview.
- `magick-ai/list-media-backups`: read-only backup history discovery for one
  attachment before any restore proposal is prepared.
- `magick-ai/restore-media-backup`: host-approved restore path that copies a
  recorded backup back to the original attachment path, restoring the original
  public media URL.
- `magick-ai/adopt-cloud-media-derivative`: approved local adoption path for a
  non-expired Cloud derivative artifact. Require artifact evidence, backup,
  rollback metadata, and host approval before commit.

Recommended rollout order:

1. Read-only inspection and inventory diagnostics.
2. Dry-run derivative preview that does not replace the original.
3. Host-approved commit for derivative generation.
4. Separate replacement and restore flows only after backup and cache behavior
   are documented.

## Guardrails

- Keep `build-media-inventory-fix-plan` proposal-only.
- Keep `format_attention` as manual review, not a write action.
- Do not use `update-media-details` for resize, compression, conversion, or
  file replacement.
- Do not replace original files by default.
- Store media operation backups in the dedicated Magick AI uploads backup
  directory, not beside normal month-directory media assets.
- Do not introduce queue, cache, CDN, or rollback ownership into the read-only
  inventory path.
- Treat actual file mutation as high risk and host-approved.
