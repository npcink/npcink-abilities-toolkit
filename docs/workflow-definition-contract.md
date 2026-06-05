# Workflow Definition Contract

Status: proposed for the 0.3 stabilization line.

Workflow definitions are machine-readable recipe guidance for hosts that compose
WordPress Abilities API abilities. They are not a workflow runtime and they are
not a second registry.

## Core Principle

`npcink-abilities-toolkit` may publish read-only, declarative workflow definitions
that describe recommended ability chains, handoff shapes, required scopes,
expected output sections, failure posture, and host-governed write boundaries.

The consuming host owns execution state, policy, approval, audit, quota, model
or prompt selection, and final WordPress mutation.

If a field would require this package to remember runtime state, make a routing
decision, enforce host policy, schedule work, retry work, approve a write, or
commit a final write, that field does not belong in a workflow definition here.

## Definition Shape

The production provider lives in
`includes/Workflow/Workflow_Definition_Provider.php`. Hosts can discover the
same manifest through:

- `npcink_abilities_toolkit_get_workflow_definitions()`;
- `npcink_abilities_toolkit_get_workflow_definition( $recipe_id )`;
- `npcink-abilities-toolkit/list-workflow-recipes`;
- `npcink-abilities-toolkit/get-workflow-recipe`.

The replay fixture at `tests/fixtures/agent-workflow-replay.json` mirrors the
production provider for consumer-side tests. It remains intentionally small while
the contract stabilizes.

Each case should define:

- `definition_kind`: `workflow_recipe`.
- `contract_version`: contract version for the case, currently `v1`.
- `recipe_id`: documentation-level recipe id, such as
  `workflow/wordpress_article_publish_preflight`.
- `title`: human-readable recipe label.
- `natural_tasks`: example user tasks a host can use for routing tests.
- `preferred_ability_id`: preferred bundled read-only entrypoint.
- `entrypoint_ability_id`: same ability id as `preferred_ability_id`, included
  for workflow-definition consumers that do not use replay terminology.
- `expanded_ability_ids`: individual read or proposal abilities a host may call
  when it needs a step-by-step chain instead of the bundle.
- `required_scope`: scope required by the preferred entrypoint.
- `required_inputs`: required input keys for the preferred entrypoint.
- `expected_sections`: output sections the host can expect from the preferred
  bundle.
- `handoff`: structured description of what the package hands to the host.
- `failure_policy`: `fail_closed`.
- `disallowed_default_ability_ids`: write or destructive abilities that a host
  must not pick as the default task entrypoint.
- `host_governed_write_boundary`: `true`.

## Handoff Shape

`handoff` should stay descriptive:

- `kind`: `context`, `suggestion`, `preview`, or `approval_request`.
- `owner`: `host`.
- `next_action`: host-side action hint.

The handoff shape must not imply that this package owns persisted workflow
state, approval storage, audit records, or final commit records.

## Forbidden Fields

Workflow definitions in this package must not include runtime or governance
fields such as:

- `workflow_state`
- `execution_state`
- `schedule`
- `scheduler`
- `retry_policy`
- `queue`
- `lease`
- `model`
- `model_routing`
- `prompt`
- `prompt_registry`
- `approval_store`
- `approval_policy`
- `audit_log`
- `quota`
- `commit_policy`
- `final_write_authority`

If a host needs any of these, it should derive or store them in the consuming
host runtime.

## Validation

`composer test` should keep workflow definition checks focused on hard
boundaries:

- the fixture is valid JSON with schema version `v1`;
- each preferred entrypoint points to a registered read-risk ability;
- entrypoint `requires_confirm` is `false`;
- required scope and required inputs match the preferred entrypoint contract;
- disallowed default abilities exist and are write-like;
- the test fixture matches the production provider;
- forbidden runtime and governance fields are absent.

Tests should not lock down natural-language descriptions unless those strings
are part of a consumer-facing compatibility guarantee.
