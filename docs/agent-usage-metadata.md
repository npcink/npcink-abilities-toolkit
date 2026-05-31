# Agent Usage Metadata

Status: active contract guidance for the `0.5.x` line.

`agent_usage` is static guidance for agents, MCP tool descriptions, adapters,
and product plugins. It helps a caller choose the right ability and stop at the
right boundary. It is not runtime policy.

## Shape

Ability definitions may provide `agent_usage` at the top level:

```php
'agent_usage' => array(
	'when_to_use'     => array( '...' ),
	'not_for'         => array( '...' ),
	'best_for'        => array( '...' ),
	'stopping_points' => array( '...' ),
),
```

The normalizer accepts the same shape from `meta.agent_usage`. Normalized
contracts expose the guidance at both:

- `agent_usage`
- `meta.agent_usage`

The fields are:

| Field | Meaning |
| --- | --- |
| `when_to_use` | User intents or task situations where the ability is appropriate. |
| `not_for` | Similar-looking tasks that should use a different ability or host workflow. |
| `best_for` | The strongest use case, especially for agents choosing between nearby abilities. |
| `stopping_points` | Boundaries where the agent must stop and hand off to Core, a host, or a human. |

## Rules

- Keep values short, literal, and action-oriented.
- Describe ability behavior, not UI affordances.
- Do not add model, provider, route, approval, or channel policy here.
- Do not make channel-private copies of this guidance; channels may project it,
  but this package remains the canonical source.
- `stopping_points` must be explicit for proposal, write, destructive, and
  workflow-entry abilities.

## Non-Goals

`agent_usage` must not become:

- a model router;
- an approval policy;
- an OpenClaw registry;
- an MCP runtime configuration;
- a workflow scheduler;
- a prompt or preset registry;
- a second source of write-control truth.

## Initial Required Coverage

`composer check:contracts` requires `agent_usage` for a small set of entry,
proposal, write, destructive, and diagnostics abilities where agent misuse is
most likely. This avoids adding maintenance weight to all abilities at once.

The initial set is:

- `magick-ai-abilities/list-workflow-recipes`
- `magick-ai-abilities/get-workflow-recipe`
- `magick-ai-abilities/wp-diagnostics-summary`
- `magick-ai-abilities/wp-ops-diagnostics-detail`
- `magick-ai/get-article-publish-preflight-context`
- `magick-ai/get-old-article-refresh-context`
- `magick-ai/get-media-cleanup-opportunities`
- `magick-ai/propose-post-taxonomy-terms`
- `magick-ai/get-comment-compliance-handoff`
- `magick-ai/create-draft`
- `magick-ai/set-post-seo-meta`
- `magick-ai/update-media-details`
- `magick-ai/approve-comment`
- `magick-ai/delete-media-permanently`

Future additions should be driven by a failed or confusing consumer proof, not
by broad catalog expansion.
