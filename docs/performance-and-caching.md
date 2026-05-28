# Performance And Caching

Status: active for 0.3 stabilization.

This project improves host efficiency without becoming a workflow runtime. The
performance strategy is to reduce repeated read calls, keep expensive scans
bounded, and cache only read-only data that is safe to recompute.

## Strategy

1. Prefer read-only aggregator abilities for common host workflows.
2. Keep every list or scan bounded by `per_page`, `page`, or explicit limits.
3. Cache only deterministic read-only reports.
4. Include the current user in cache keys when output can depend on capability
   checks.
5. Invalidate by version bump on WordPress content, term, and attachment
   changes instead of deleting individual transient keys.
6. Measure the same bounded ability chains before broadening the ability
   surface.

## Aggregator Abilities

These abilities reduce host round trips while staying read-only:

- `magick-ai/get-article-publish-preflight-context`
- `magick-ai/get-old-article-refresh-context`
- `magick-ai/get-comment-compliance-handoff`
- `magick-ai/get-site-operations-dashboard`
- `magick-ai/build-article-workflow-context`

They do not store workflow state, approve writes, schedule work, or commit
WordPress mutations. They only return context bundles that a host can use for
its own approval and orchestration.

## Cache Pilot

The first cache pilot covers:

- `magick-ai/get-content-inventory-health`
- `magick-ai/get-seo-geo-gap-report`

The cache uses WordPress transients with a short TTL and a versioned key. The
cache key includes:

- ability cache namespace;
- normalized bounded input;
- current user id;
- `magick_ai_abilities_read_cache_version`.

The version is bumped from plugin hooks for post, term, and attachment changes.
This keeps stale read-only reports bounded without making cache entries a
canonical truth source.

Do not cache:

- write or destructive abilities;
- dry-run previews that include approval-sensitive host context;
- data that depends on secrets, runtime credentials, quota, audit, or model
  routing;
- unbounded scans.

## Performance Smoke

Run the isolated performance smoke with:

```bash
composer perf:smoke
```

The smoke measures representative bounded chains:

- content inventory health;
- cached SEO/GEO gap report;
- article publish preflight context;
- old article refresh context;
- comment compliance handoff.

Budgets are intentionally conservative and local. They catch accidental
explosions in scan cost or aggregator composition, not production latency.

For a real WordPress site, also run:

```bash
WP_PATH=/path/to/wordpress composer smoke:wp
```

Record real-site smoke results in release verification when a Local WP site is
available.
