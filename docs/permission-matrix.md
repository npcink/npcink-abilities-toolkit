# Permission Matrix

Status: active for first-party write and destructive abilities.

This matrix documents the WordPress capability and host scope expected for each
write-like built-in ability. The capability is enforced by the ability package;
host scopes are metadata for Core, MCP adapters, and other governance layers.

Dry-run previews must still pass the same WordPress permission checks as final
commits so preview payloads do not leak data to callers who could not perform
the corresponding WordPress operation.

## Scope Semantics

Abilities declare authorization surfaces through `required_scope` /
`required_scopes` plus a WordPress `capability`. The semantics are:

- **Scope-gated** (`required_scope` non-empty): the host governance layer is
  expected to check the declared scope in addition to the WordPress
  capability. All write and destructive abilities are scope-gated.
- **Capability-gated** (`required_scope` and `required_scopes` both empty):
  the WordPress capability is the only authorization surface and there is no
  host-side scope expectation. An empty scope is therefore a deliberate
  decision, not an omission: `composer check:contracts` enforces that every
  capability-gated ability appears on the explicit capability-gated list in
  `scripts/check-ability-contracts.php`, so a new ability must either declare
  a scope or consciously join that list. The current capability-gated set is
  read-only (site inventory reads, taxonomy and comment listing, page
  inspection, diagnostics, and the workflow recipe discovery pair).

| Ability | Risk | WordPress capability | Host scope metadata |
| --- | --- | --- | --- |
| `npcink-abilities-toolkit/create-draft` | `write` | `edit_posts` | `post.write` |
| `npcink-abilities-toolkit/update-post` | `write` | `edit_posts` | `post.write` |
| `npcink-abilities-toolkit/set-post-seo-meta` | `write` | `edit_posts` | `post.write` |
| `npcink-abilities-toolkit/patch-post-content` | `write` | `edit_posts` | `post.write` |
| `npcink-abilities-toolkit/update-post-blocks` | `write` | `edit_posts` | `post.write` |
| `npcink-abilities-toolkit/set-post-slug` | `write` | `edit_posts` | `post.write` |
| `npcink-abilities-toolkit/set-post-author` | `write` | `edit_posts` | `post.write` |
| `npcink-abilities-toolkit/set-post-template` | `write` | `edit_posts` | `post.write` |
| `npcink-abilities-toolkit/set-post-format` | `write` | `edit_posts` | `post.write` |
| `npcink-abilities-toolkit/create-term` | `write` | `manage_categories` | `taxonomy.manage` |
| `npcink-abilities-toolkit/update-term` | `write` | `manage_categories` | `taxonomy.manage` |
| `npcink-abilities-toolkit/set-post-terms` | `write` | `edit_posts` | `taxonomy.manage` |
| `npcink-abilities-toolkit/update-media-details` | `write` | `upload_files` | `media.write` |
| `npcink-abilities-toolkit/upload-media-from-url` | `write` | `upload_files` | `media.write` |
| `npcink-abilities-toolkit/optimize-media-asset` | `write` | `upload_files` | `media.write` |
| `npcink-abilities-toolkit/replace-media-file` | `write` | `upload_files` | `media.write` |
| `npcink-abilities-toolkit/restore-media-backup` | `write` | `upload_files` | `media.write` |
| `npcink-abilities-toolkit/rename-media-file` | `write` | `upload_files` | `media.write` |
| `npcink-abilities-toolkit/adopt-cloud-media-derivative` | `write` | `upload_files` | `media.write` |
| `npcink-abilities-toolkit/set-post-featured-image` | `write` | `edit_posts` | `media.write` |
| `npcink-abilities-toolkit/schedule-post` | `write` | `publish_posts` | `post.write` |
| `npcink-abilities-toolkit/publish-post` | `write` | `publish_posts` | `post.write` |
| `npcink-abilities-toolkit/restore-post` | `write` | `edit_posts` | `post.write` |
| `npcink-abilities-toolkit/approve-comment` | `write` | `moderate_comments` | `comments.manage` |
| `npcink-abilities-toolkit/reply-comment` | `write` | `moderate_comments` | `comments.manage` |
| `npcink-abilities-toolkit/delete-term` | `destructive` | `manage_categories` | `taxonomy.manage` |
| `npcink-abilities-toolkit/merge-terms` | `destructive` | `manage_categories` | `taxonomy.manage` |
| `npcink-abilities-toolkit/bulk-update-post-terms` | `destructive` | `edit_posts` | `taxonomy.manage`, `post.write` |
| `npcink-abilities-toolkit/spam-comment` | `destructive` | `moderate_comments` | `comments.manage` |
| `npcink-abilities-toolkit/trash-comment` | `destructive` | `moderate_comments` | `comments.manage` |
| `npcink-abilities-toolkit/delete-media-permanently` | `destructive` | `delete_posts` | `media.write` |
| `npcink-abilities-toolkit/trash-post` | `destructive` | `delete_posts` | `post.delete` |
| `npcink-abilities-toolkit/delete-post-permanently` | `destructive` | `delete_posts` | `post.delete` |

## Host Boundary

This plugin does not issue app keys, approve proposals, rate-limit callers, or
store governance audit trails. Those decisions belong to `npcink-ai-core` or
another host runtime.
