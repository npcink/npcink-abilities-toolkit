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
- **Developer Tools** hosts every technical surface behind one WordPress
  standard `.subsubsub` sub-navigation cut by developer task: Connection
  (endpoints, discovery fetches, catalog export), Ability Catalog (the
  Available Abilities audit table), Checks (read-only site checks), and
  Workflow scenarios. Only the active sub-section renders. Legacy tab URLs
  (`abilities`, `checks`, `developer`, `catalog`, `connections`) alias to
  this tab with the matching sub-section so bookmarks keep working.

Navigation discipline: **every navigation target has exactly one entry** on
the overview. Before adding a link, card, or button, check whether an
existing entry already reaches that target; merge or remove instead of
adding a second path.

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
- next actions for viewing abilities, running safe checks, reviewing
  workflow scenarios, and using a host product, rendered as one compact
  button row.

The concrete always-visible vs foldable split for Overview is locked in
[Overview Information Architecture](#overview-information-architecture).

## Overview Information Architecture

The Overview tab is the site-operator status and review screen for **AI Ability
Set**. In one pass it must make clear: what the site exposes to AI clients,
write / host-approval posture, whether a host product is present, how ability
packages and read-only mode shape that surface, and where to go next for audit,
checks, scenarios, or connection values.

Quiet density is welcome. **Positioning decides what stays visible; do not
delete positioning facts only to look simpler.** Important facts stay
always-on. Long tutorials, full contracts, and developer-side detail may fold,
use a quiet `ⓘ` / `title` affordance, or link out. User-visible chrome and
copy must remain localized through the plugin text domain (including
Simplified Chinese).

### Always visible

These blocks must remain on the Overview first screen. Do not hide them behind
disclosures in the name of decluttering:

- **Four status metrics:** Status, Available abilities, Write safeguards, and
  Host. WordPress runtime version may appear as one quiet meta line under the
  metrics, not as a competing tile or a disclosure stack.
- **Category composition:** a plain-language capability summary grouped by
  category, with counts and host-approval marks for abilities that can change
  content (no technical ability ids on Overview).
- **Ability packages:** the full On/Off map for built-in packages; a visible
  dry-run / host-approval note when write or destructive packages are enabled;
  and the read-only mode control that can force write-like packages Off.
- **Next actions (four entries in one compact button row):** View Abilities,
  Open Checks, View N Workflow Scenarios (when scenarios exist), and View
  Connection Info. Keep a single entry per target (see Navigation discipline
  above).

### May fold, use ⓘ, or link out

These must not compete with the always-visible facts on first paint:

- page-header long orientation copy (title / screen-reader text is enough);
- package “how to turn bundles on or off” tutorial detail (`ⓘ` or docs link);
- the full Host Approval Contract body (external docs link only);
- Developer Tools check purpose tables, raw JSON support disclosures, and
  deep environment / REST detail that already live outside Overview.

### Density rule

Collapse secondary tutorial and contract material; never collapse the
operator facts listed under Always visible. A quieter style (muted meta,
inline `ⓘ`, compact chips) is fine; an empty or numbers-only Overview that
omits host posture, package state, category write marks, or next-action
entries is not.

## Workflow Scenario Overview

The full workflow scenario catalog renders at the end of the Developer Tools
tab, after the read-only checks, through
`npcink_abilities_toolkit_get_workflow_definitions()`. The block is
documentation-only:

- it renders recipe titles, natural task examples, and entry ability ids;
- it must not add run, execute, schedule, retry, or approval affordances;
- it must not display workflow state, approval state, or audit data;
- host products remain the only execution surface.

The default overview carries the scenario count as a button in the action
row ("View N Workflow Scenarios") linking the Developer Tools catalog. The
full card grid stays out of the default view: site owners need the one-line
answer, and the card-level detail (contract task phrasing, entry ability
ids) serves the developer audience that already lives in that tab.

## External Documentation Links

The admin surface may link to the repository's published documentation on
GitHub. External links must:

- use stable `blob/master` documentation URLs declared as class constants;
- open in a new tab with `rel="noopener noreferrer"`;
- stay limited to integration and contract guidance (REST client quickstart,
  host approval contract, troubleshooting) — never deep-link source code or
  internal tooling.

The standalone host card should point host products to the Host Approval
Contract so a site owner can hand the next step to an integrator. Overview
attention notes may link the troubleshooting guide because every attention
message describes a failure the troubleshooting doc explains how to resolve.

## Available Abilities

The Available Abilities section is the audit table inside the Developer Tools
tab. The default ability review should use plain labels and task descriptions
first. Developer-only technical details should not appear in this
customer-facing list.

- show matching abilities in one flat table by default;
- use a Quiet catalog toolbar: primary search (+ reset), with risk, category,
  and page-size under **More filters** (auto-open when those params are set);
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
  not prove before the operator runs it (purpose table may sit behind Check
  details per Developer Tools Information Architecture);
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

## Developer Tools Information Architecture

Developer Tools is the integrator surface: endpoints, the ability catalog, and
read-only checks. Quiet density is welcome. **Positioning decides what stays
visible** — ops and key connection values stay always-on; long copy, raw JSON,
purpose tables, and environment footnotes fold or use `ⓘ` / `title`.
User-visible chrome stays localized (including Simplified Chinese). Each
Developer Tools sub-view keeps **at most one solid primary button**.

### Always visible

**Connection**

- One page heading only: **Connection values** (subnav already says Connection —
  do not repeat a visible Connection h2);
- Connection values table (Abilities / Categories / Contract endpoints + Copy);
- one faint docs-only link row under the table (REST client quickstart · Host
  Approval Contract) — never share a visual band with discovery actions;
- a separate ops band: small **Raw discovery fetches** title, then Fetch
  Abilities · Fetch Categories, then folded Discovery response / Catalog export
  as compact text disclosures (not empty gray cards).

**Ability Catalog**

- Quiet toolbar primary row: search + Reset (Apply remains screen-reader /
  Enter fallback; selects still submit on change);
- matching ability table (label, description, risk, availability);
- result meta and pagination.

**Checks**

- safe-check actions with a single solid primary (**Run Site Info**); secondary
  **Run Redacted Diagnostics**;
- check results summary area (empty state until a run).

### May fold, use ⓘ, or live under More filters

- long orientation copy on Connection / Catalog / Checks (title / SR / ⓘ);
- Risk, Category, and Per page under **More filters** (force-open when set);
- Check details purpose table ("what it proves" / "what it does not prove");
- Discovery response textarea and Copy response;
- Catalog export (Ability ID export + technical catalog) and raw JSON support
  disclosures after checks;
- auth / environment footnotes on discovery fetches.

### Density rule

Collapse secondary tutorial, raw payloads, and purpose tables; never collapse
endpoint values, discovery fetch actions, catalog search, or the one primary
check action. A quieter style is fine; a Connection screen that hides endpoints
or a Catalog that buries search is not.

## AI Ability Set

The page should avoid looking like a general settings page. Plugin-list action
links, page headings, and menu labels should use abilities language rather than
Settings or Diagnostics language. Diagnostics remain a bounded check, not the
page identity. The on-page heading must match the registered menu label
("AI Ability Set") so the clicked destination is confirmed on arrival.

Host products that want the `Npcink AI -> AI Ability Set` placement must
register the shared `npcink-ai` top-level menu at an `admin_menu` priority
lower than 40, because the Toolkit submenu attaches at priority 40 and falls
back to Tools when the parent menu is not registered yet.

A one-time post-activation welcome notice may point operators to the status
page. It must be per-admin, must clear after the status page is visited or the
notice is dismissed, must stay silent for activations without an admin session
(CLI, network bulk activation), and must not become a recurring nag.

Workflow scenario cards localize recipe titles and natural task examples at
render time with the plugin text domain. The recipe payloads themselves
(REST discovery, contract projection, replay fixtures) remain untranslated
English contract data.

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
