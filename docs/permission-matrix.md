# Permission Matrix

Status: active for first-party write and destructive abilities.

This matrix documents the WordPress capability and host scope expected for each
write-like built-in ability. The capability is enforced by the ability package;
host scopes are metadata for Core, MCP adapters, and other governance layers.

Dry-run previews must still pass the same WordPress permission checks as final
commits so preview payloads do not leak data to callers who could not perform
the corresponding WordPress operation.

| Ability | Risk | WordPress capability | Host scope metadata |
| --- | --- | --- | --- |
| `magick-ai/create-draft` | `write` | `edit_posts` | `post.write` |
| `magick-ai/update-post` | `write` | `edit_posts` | `post.write` |
| `magick-ai/set-post-seo-meta` | `write` | `edit_posts` | `post.write` |
| `magick-ai/patch-post-content` | `write` | `edit_posts` | `post.write` |
| `magick-ai/update-post-blocks` | `write` | `edit_posts` | `post.write` |
| `magick-ai/set-post-slug` | `write` | `edit_posts` | `post.write` |
| `magick-ai/set-post-author` | `write` | `edit_posts` | `post.write` |
| `magick-ai/set-post-template` | `write` | `edit_posts` | `post.write` |
| `magick-ai/set-post-format` | `write` | `edit_posts` | `post.write` |
| `magick-ai/create-term` | `write` | `manage_categories` | `taxonomy.manage` |
| `magick-ai/update-term` | `write` | `manage_categories` | `taxonomy.manage` |
| `magick-ai/set-post-terms` | `write` | `edit_posts` | `taxonomy.manage` |
| `magick-ai/update-media-details` | `write` | `upload_files` | `media.write` |
| `magick-ai/upload-media-from-url` | `write` | `upload_files` | `media.write` |
| `magick-ai/optimize-media-asset` | `write` | `upload_files` | `media.write` |
| `magick-ai/replace-media-file` | `write` | `upload_files` | `media.write` |
| `magick-ai/restore-media-backup` | `write` | `upload_files` | `media.write` |
| `magick-ai/rename-media-file` | `write` | `upload_files` | `media.write` |
| `magick-ai/adopt-cloud-media-derivative` | `write` | `upload_files` | `media.write` |
| `magick-ai/set-post-featured-image` | `write` | `edit_posts` | `media.write` |
| `magick-ai/schedule-post` | `write` | `publish_posts` | `post.write` |
| `magick-ai/publish-post` | `write` | `publish_posts` | `post.write` |
| `magick-ai/restore-post` | `write` | `edit_posts` | `post.write` |
| `magick-ai/approve-comment` | `write` | `moderate_comments` | `comments.manage` |
| `magick-ai/reply-comment` | `write` | `moderate_comments` | `comments.manage` |
| `magick-ai/delete-term` | `destructive` | `manage_categories` | `taxonomy.manage` |
| `magick-ai/merge-terms` | `destructive` | `manage_categories` | `taxonomy.manage` |
| `magick-ai/bulk-update-post-terms` | `destructive` | `edit_posts` | `taxonomy.manage`, `post.write` |
| `magick-ai/spam-comment` | `destructive` | `moderate_comments` | `comments.manage` |
| `magick-ai/trash-comment` | `destructive` | `moderate_comments` | `comments.manage` |
| `magick-ai/delete-media-permanently` | `destructive` | `delete_posts` | `media.write` |
| `magick-ai/trash-post` | `destructive` | `delete_posts` | `post.delete` |
| `magick-ai/delete-post-permanently` | `destructive` | `delete_posts` | `post.delete` |

## Host Boundary

This plugin does not issue app keys, approve proposals, rate-limit callers, or
store governance audit trails. Those decisions belong to `magick-ai-core` or
another host runtime.
