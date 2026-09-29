# Abilities Admin Surface Standard

Status: active for `Npcink AI -> AI Ability Set` and the standalone
`Tools -> AI Ability Set` fallback.

## Purpose

The Abilities admin page is a site-operator ability status, review, check, and
connection surface. It exists to explain which WordPress abilities the site
exposes to AI clients, whether write-like abilities require host approval, and
whether safe read-only checks can run.

Developer-oriented REST values remain available, but they should not be the
default first impression.

## Two-Audience Structure

The page has exactly two tabs, split by audience rather than by surface type:

- **Overview** (default) answers the site owner in one screen: status tiles,
  a plain-language capability summary grouped by category, write posture, and
  guided next actions.
- **Developer Tools** hosts every technical surface in one anchored page:
  connection values, raw discovery fetches, catalog export, the Available
  Abilities audit table, the read-only checks, and the workflow scenario
  catalog. Legacy tab URLs (`abilities`, `checks`, `developer`, `catalog`,
  `connections`) alias to this tab so bookmarks keep working.

Tabs are cut by audience because nobody arrives wanting "a review surface" —
site owners arrive with "is it working and what can AI do here", developers
arrive with "give me endpoints and ids".

## Default View

The default page should show:

- compact environment status for WordPress Abilities API support;
- available ability count;
- write-safeguard posture for write/destructive abilities;
- host detection status;
- a plain-language capability summary grouped by category (label, capability
  counts, how many can change content) that answers "what can AI do on this
  site" without exposing technical ids;
- stable, shareable admin URLs for tabs and read-only filters;
- next actions for viewing abilities, running safe checks, using a host
  product, and opening developer access;
- a one-line workflow scenario summary (published scenario count, host-owned
  execution) linking the full catalog in the Developer Tools tab.

## Workflow Scenario Overview

The full workflow scenario catalog renders at the end of the Developer Tools
tab, after the read-only checks, through
`npcink_abilities_toolkit_get_workflow_definitions()`. The block is
documentation-only:

- it renders recipe titles, natural task examples, and entry ability ids;
- it must not add run, execute, schedule, retry, or approval affordances;
- it must not display workflow state, approval state, or audit data;
- host products remain the only execution surface.

The default overview shows only a single localized summary line with the
scenario count and a link to the Developer Tools catalog. The full card grid
stays out of the default view: site owners need the one-line answer, and the
card-level detail (contract task phrasing, entry ability ids) serves the
developer audience that already lives in that tab.

## External Documentation Links

The admin surface may link to the repository's published documentation on
GitHub. External links must:

- use stable `blob/master` documentation URLs declared as class constants;
- open in a new tab with `rel="noopener noreferrer"`;
- stay limited to integration and contract guidance (REST client quickstart,
  host approval contract) — never deep-link source code or internal tooling.

The standalone host card should point host products to the Host Approval
Contract so a site owner can hand the next step to an integrator.

## Available Abilities

The Available Abilities section is the audit table inside the Developer Tools
tab. The default ability review should use plain labels and task descriptions
first. Developer-only technical details should not appear in this
customer-facing list.

- show matching abilities in one flat table by default;
- use search, risk, category, and page-size filters to narrow the table;
- show label, description, risk, and availability;
- keep ability ids searchable, but do not render schema or callback signals in
  the main list;
- support filtering by ability name, description, category, risk, technical ID,
  and page size.

## Checks

Safe checks render as a section inside the Developer Tools tab, separated
from developer REST fetches:

- at most two official read-only ability checks may be visible: site info and
  bounded redacted diagnostics summary;
- check copy must say that the checks do not write content, call models, or
  contact external services;
- the Checks section should explain what each check proves and what it does
  not prove before the operator runs it;
- check results should default to a plain summary table that answers what
  worked and what needs attention;
- raw JSON response details may be kept behind an explicit support disclosure
  after a check runs.

## Developer Access

Connection and low-frequency details open the Developer Tools tab as its
first section:

- REST endpoint URLs should be visible with copy actions;
- the Toolkit contract endpoint should be visible as a copyable host/runtime
  discovery value;
- browser REST fetch buttons should be grouped as discovery fetches;
- copyable registered ability ID export;
- ability technical catalog with ID, category, risk, schema availability, and
  callback status;
- compatibility projection notes for Npcink AI consumers.

## AI Ability Set

The page should avoid looking like a general settings page. Plugin-list action
links, page headings, and menu labels should use abilities language rather than
Settings or Diagnostics language. Diagnostics remain a bounded check, not the
page identity.

Do not add demo/showcase execution buttons, model-call buttons, write buttons,
or workflow-run buttons to this package surface. Real workflow execution belongs
to a host product such as Npcink AI/Core, not to the capability package admin
page.

## Time Display

Ability callbacks may return UTC, ISO, or explicitly named `*_gmt` fields as
machine-readable contract values. Keep those output field names and semantics stable.

If the Abilities admin page ever shows a timestamp to a human operator, format
the visible value through the WordPress site timezone as `Y-m-d H:i:s`. Do not
print raw UTC strings, ISO timestamps, or `*_gmt` ability values directly in
the human-facing admin UI unless the label explicitly describes a
machine/debug value.

## Do Not Add

Abilities admin must not add:

- Core proposal approval, preflight, or audit workflows;
- OpenClaw handoff or Application Password generation;
- Cloud API key, runtime, billing, or entitlement controls;
- model routing, prompt/preset settings, queues, workflow runtime, MCP
  governance, or final write approval truth.

## Verification

Static contracts should assert that the page remains a user-facing ability
status/check/developer-access surface,
keeps the standalone Tools fallback, and does not become a Npcink AI runtime or
governance console.
