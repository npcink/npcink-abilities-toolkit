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
- a future target ability hint;
- explicit `write_action_generated=false`;
- high-risk classification for file-asset work.

It must not map `format_attention` to `magick-ai/update-media-details`, because
format work is not metadata-only.

It must not map `format_attention` directly to asset write abilities from the
read-only fix plan. Hosts may use the diagnostic output to start a separate
approval flow.

## Future Ability Shape

If file-asset work becomes necessary, prefer separate abilities with narrow
contracts:

- `magick-ai/optimize-media-asset`: first candidate for resize/compress or
  optimized derivative generation. Keep original assets preserved.
- `magick-ai/convert-media-format`: format conversion such as WebP or AVIF.
  This should start as derivative generation, not direct replacement.
- `magick-ai/replace-media-file`: highest-risk path for switching an
  attachment file. Require backup, rollback, approval, and strict preview.

Recommended rollout order:

1. Read-only inspection and inventory diagnostics.
2. Dry-run derivative preview that does not replace the original.
3. Host-approved commit for derivative generation.
4. Separate replacement or rollback flow only after backup and cache behavior
   are documented.

## Guardrails

- Keep `build-media-inventory-fix-plan` proposal-only.
- Keep `format_attention` as manual review, not a write action.
- Do not use `update-media-details` for resize, compression, conversion, or
  file replacement.
- Do not replace original files by default.
- Do not introduce queue, cache, CDN, or rollback ownership into the read-only
  inventory path.
- Treat actual file mutation as high risk and host-approved.

