# OSS Storage Compatibility Shim

## Purpose

WordPress sites often use third-party OSS, S3, COS, Qiniu, or CDN offload
plugins for media. Npcink Abilities Toolkit cannot control those plugins and
must not assume their internal storage, credentials, cache, or rollback
behavior.

This shim contract lets a host site declare bounded storage readiness for media
abilities without moving object-storage ownership into Toolkit.

## Boundary

Toolkit owns:

- conservative detection when an attachment URL no longer looks like the local
  uploads base URL;
- a sanitized `storage` evidence block on media inspection and planning;
- fail-closed guards for media file writes when remote storage has no reviewed
  adapter evidence;
- the `npcink_abilities_toolkit_media_storage_inspection` filter surface.

Toolkit does not own:

- OSS plugin configuration;
- bucket, region, endpoint, credential, token, or signed-header storage;
- provider SDK calls;
- source object download, derivative upload, backup restore, or CDN purge;
- final write authorization, approval records, or audit truth.

## Default Behavior

When media appears to be backed by remote object storage, Toolkit keeps
read-only inspection available but blocks media file mutation unless a host
adapter supplies reviewed storage readiness.

Common blocked reasons are:

- `remote_storage_requires_adapter`: the source cannot be read safely through
  the local uploads path;
- `remote_storage_write_requires_adapter`: the source may be readable locally,
  but write/restore behavior is controlled by remote storage.

## Filter Contract

Hosts can implement:

```php
add_filter(
	'npcink_abilities_toolkit_media_storage_inspection',
	static function ( array $storage, int $attachment_id, string $relative_file, string $url, string $attached_file ) {
		// Return sanitized storage readiness fields.
		return $storage;
	},
	10,
	5
);
```

Toolkit passes the current storage evidence, attachment id, uploads-relative
file, public URL, and attached file value. The filter should return only
bounded readiness metadata.

Accepted `storage` fields are:

| Field | Meaning |
| --- | --- |
| `provider` | `local_uploads`, `remote_object_storage`, or `unknown`. |
| `adapter` | Stable adapter id such as `acme_oss_shim`; never a credential. |
| `attachment_id` | Current attachment id. |
| `current_relative_file` | Current uploads-relative media file. |
| `canonical_url` | Current public attachment URL. |
| `local_file_readable` | Whether the local filesystem source is readable. |
| `source_read_mode` | `local_file`, `signed_url`, `public_url`, or `blocked`. |
| `write_mode` | `local_uploads`, `local_upload_then_offload`, `provider_api`, or `blocked`. |
| `restore_mode` | `local_backup`, `provider_backup`, or `blocked`. |
| `cache_purge_required` | Whether a separate host/plugin purge is required after mutation. |
| `blocked_reason` | Empty when the adapter is ready; otherwise a machine-readable reason. |

Any unsupported field value is sanitized back to a safe value. Extra fields are
not preserved.

## Adapter Rules

A shim should only clear `blocked_reason` when the host can actually perform
the relevant storage operation outside Toolkit.

Use `source_read_mode=signed_url` only when the host can produce a short-lived
source URL without exposing credentials to Toolkit, Core proposals, REST
responses, or logs.

Use `write_mode=provider_api` only when the host can write the approved
derivative to the storage provider and reconcile WordPress attachment metadata
through the existing governed ability flow.

Use `restore_mode=provider_backup` only when the host can restore from a
recorded provider-side backup. If rollback is not proven, leave restore blocked.

Set `cache_purge_required=true` when the storage/CDN layer may serve stale
objects after a file operation. Toolkit records the requirement but does not
purge caches.

## Example

See [examples/oss-storage-shim.php](../examples/oss-storage-shim.php) for a
minimal filter implementation. It is intentionally a shim, not a provider SDK
integration.
