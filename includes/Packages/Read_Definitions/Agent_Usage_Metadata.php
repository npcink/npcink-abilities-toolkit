<?php
/**
 * Static agent usage metadata for read ability definitions.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Packages\Read_Definitions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Applies agent usage guidance to selected first-party abilities.
 */
final class Agent_Usage_Metadata {
	/**
	 * Adds static agent usage guidance for priority entry/read abilities.
	 *
	 * @param array<string,array<string,mixed>> $definitions Ability definitions.
	 * @return array<string,array<string,mixed>>
	 */
	public static function apply( array $definitions ) {
		foreach ( self::metadata() as $ability_id => $agent_usage ) {
			if ( isset( $definitions[ $ability_id ] ) ) {
				$definitions[ $ability_id ]['agent_usage'] = $agent_usage;
			}
		}

		return $definitions;
	}

	/**
	 * Returns static agent usage metadata keyed by ability id.
	 *
	 * @return array<string,array<string,string[]>>
	 */
	private static function metadata() {
		return array(
			'npcink-abilities-toolkit/list-workflow-recipes' => array(
				'when_to_use'     => array( 'Discover supported host-side workflow recipes before choosing a multi-step path.' ),
				'not_for'         => array( 'Do not use this to execute, schedule, approve, audit, or commit workflow steps.' ),
				'best_for'        => array( 'Selecting the right entry ability for article, refresh, comment, diagnostics, or governance handoff work.' ),
				'stopping_points' => array( 'After selecting a recipe, call the listed abilities through the host; this package does not run the workflow.' ),
			),
			'npcink-abilities-toolkit/get-workflow-recipe' => array(
				'when_to_use'     => array( 'Fetch one workflow recipe by recipe id or case id after discovery.' ),
				'not_for'         => array( 'Do not use this as a workflow execution endpoint or approval record.' ),
				'best_for'        => array( 'Reading required inputs, entry ability, expanded ability ids, and handoff boundaries for one workflow.' ),
				'stopping_points' => array( 'Stop after reading the recipe; execution, approval, audit, retry, and final writes belong to the host.' ),
			),
			'npcink-abilities-toolkit/wp-diagnostics-summary' => array(
				'when_to_use'     => array( 'Inspect a redacted WordPress-only environment summary for support or readiness triage.' ),
				'not_for'         => array( 'Do not use this for Npcink AI settings, MCP settings, secrets, filesystem paths, database names, or external probes.' ),
				'best_for'        => array( 'Checking REST, Abilities API, WordPress, PHP, theme, plugin, cron, and update context without leaking secrets.' ),
				'stopping_points' => array( 'For runtime, cloud, MCP, or secret diagnostics, hand off to the owning host or operations addon.' ),
			),
			'npcink-abilities-toolkit/wp-ops-diagnostics-detail' => array(
				'when_to_use'     => array( 'Inspect bounded WordPress operations details after the summary shows an environment or support gap.' ),
				'not_for'         => array( 'Do not use this for Npcink AI runtime state, MCP state, database names, table names, filesystem paths, secrets, unredacted logs, or external probes.' ),
				'best_for'        => array( 'Checking active plugins, optional plugin groups, caller roles/capabilities, PHP extensions, cache and rewrite state, HTTPS, server/database summaries, cron hooks, bounded log severity summaries, content types, roles, widgets, block-theme registries, search integrations, SEO/security/performance summaries, and common plugin integrations.' ),
				'stopping_points' => array( 'Stop at redacted local evidence; log review, database repair, external index checks, runtime/MCP/cloud diagnosis, and final remediation belong to the host or operator.' ),
			),
			'npcink-abilities-toolkit/search-posts' => array(
				'when_to_use'     => array( 'Find local WordPress post candidates by keyword across posts, pages, or selected post types before reading a full context bundle.' ),
				'not_for'         => array( 'Do not use this for external search indexes, web crawling, semantic retrieval, final editorial decisions, or post mutation.' ),
				'best_for'        => array( 'Bounded keyword search with post type, status, author, date, modified date, and taxonomy filters; follow with get-post-context for details.' ),
				'stopping_points' => array( 'Stop after candidate ids and summaries are selected; read details with get-post-context and route all writes through host-governed abilities.' ),
			),
			'npcink-abilities-toolkit/search-post-meta' => array(
				'when_to_use'     => array( 'Find local WordPress post candidates by explicitly named metadata keys such as SEO title, SEO description, or focus keyword.' ),
				'not_for'         => array( 'Do not use this to inspect arbitrary secrets, passwords, tokens, API keys, unbounded metadata, or to mutate metadata.' ),
				'best_for'        => array( 'Narrow exact or contains matching over up to ten non-sensitive meta keys, followed by get-post-context with scoped meta_keys when details are needed.' ),
				'stopping_points' => array( 'Stop after matched post ids and bounded meta excerpts; final metadata changes require host-governed write abilities.' ),
			),
			'npcink-abilities-toolkit/get-nonproduction-content-inventory' => array(
				'when_to_use'     => array( 'Detect bounded smoke, fixture, or nonproduction content before cleanup planning or before interpreting content, taxonomy, comment, and operations diagnostics.' ),
				'not_for'         => array( 'Do not use this to trash posts, delete terms, delete comments, or mutate detected nonproduction content.' ),
				'best_for'        => array( 'Separating real editorial data from local nonproduction artifacts before building cleanup or remediation proposals.' ),
				'stopping_points' => array( 'Stop after inventory evidence; call build-nonproduction-content-cleanup-plan for dry-run actions and require host/Core approval before any write.' ),
			),
			'npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan' => array(
				'when_to_use'     => array( 'Turn detected smoke, fixture, or nonproduction content into a bounded dry-run cleanup plan mapped to existing governed write or destructive abilities.' ),
				'not_for'         => array( 'Do not use this as a cleanup executor, approval record, trash endpoint, delete endpoint, or bypass for destructive gating.' ),
				'best_for'        => array( 'Preparing reviewable cleanup proposal input with write_actions, preview, requires_approval, and commit_execution=false.' ),
				'stopping_points' => array( 'Stop at proposal-ready plan output; Core proposal creation, approval, commit-preflight, audit, and final execution belong to the host.' ),
			),
			'npcink-abilities-toolkit/build-content-inventory-fix-plan' => array(
				'when_to_use'     => array( 'Turn bounded content inventory issues such as missing SEO metadata, slug, excerpt, title, featured image, or content gaps into a dry-run remediation plan.' ),
				'not_for'         => array( 'Do not use this to generate final editorial copy, patch post content, publish, schedule, or directly update post metadata.' ),
				'best_for'        => array( 'Mapping content health findings to existing governed write abilities with before/after preview, missing input markers, and commit_execution=false.' ),
				'stopping_points' => array( 'Stop when write_actions and preview are ready; unresolved requires_input values need review, and all writes require Core/host proposal approval.' ),
			),
			'npcink-abilities-toolkit/get-article-publish-preflight-context' => array(
				'when_to_use'     => array( 'Assemble read-only publish readiness, risk, workflow, and calendar context for one post.' ),
				'not_for'         => array( 'Do not use this to schedule, publish, rewrite, or commit post changes.' ),
				'best_for'        => array( 'Deciding whether a draft needs edits, review, scheduling, or a host-governed write proposal.' ),
				'stopping_points' => array( 'Stop before any publish, schedule, metadata, or content mutation and request host/Core approval.' ),
			),
			'npcink-abilities-toolkit/get-old-article-refresh-context' => array(
				'when_to_use'     => array( 'Find stale or under-optimized articles and collect SEO/GEO, style, and link context.' ),
				'not_for'         => array( 'Do not use this to rewrite posts, patch content, or change SEO metadata.' ),
				'best_for'        => array( 'Choosing refresh candidates and preparing a host-owned optimization plan.' ),
				'stopping_points' => array( 'Stop after candidate discovery; content generation, model calls, and writes belong to product or host workflows.' ),
			),
				'npcink-abilities-toolkit/get-media-cleanup-opportunities' => array(
					'when_to_use'     => array( 'Scan media for metadata gaps, source gaps, and likely unused assets.' ),
					'not_for'         => array( 'Do not use this to update media metadata or delete attachments.' ),
					'best_for'        => array( 'Building a review queue before media SEO enrichment or cleanup proposals.' ),
					'stopping_points' => array( 'Stop before update-media-details or delete-media-permanently and require host/Core approval.' ),
				),
				'npcink-abilities-toolkit/list-media-backups' => array(
					'when_to_use'     => array( 'List recorded backups for one attachment before preparing a media restore proposal.' ),
					'not_for'         => array( 'Do not use this for site-level restore, deleting backups, or mutating the attachment pointer.' ),
					'best_for'        => array( 'Finding the backup_id and current media evidence needed for restore-media-backup.' ),
					'stopping_points' => array( 'Stop at backup history evidence; final restore requires host/Core approval through restore-media-backup.' ),
				),
				'npcink-abilities-toolkit/build-media-inventory-fix-plan' => array(
					'when_to_use'     => array( 'Turn bounded media inventory issues such as missing alt text, captions, descriptions, source gaps, format attention, or unattached candidates into a dry-run remediation plan.' ),
				'not_for'         => array( 'Do not use this to upload files, update attachment metadata, set featured images, delete media, or override destructive gating.' ),
				'best_for'        => array( 'Mapping media health findings to existing governed media write abilities with reviewable preview, skipped destructive candidates, and commit_execution=false.' ),
				'stopping_points' => array( 'Stop at proposal-ready plan output; destructive candidates must remain gated and final writes require Core/host approval plus commit-preflight.' ),
			),
			'npcink-abilities-toolkit/build-media-optimization-plan' => array(
				'when_to_use'     => array( 'Combine reviewed media SEO metadata and a reviewed Cloud derivative artifact into one dry-run attachment optimization plan.' ),
				'not_for'         => array( 'Do not use this to call Cloud, preview artifacts, approve proposals, replace files, update metadata, or execute WordPress writes.' ),
				'best_for'        => array( 'Preparing one Core batch proposal for the user intent "optimize this media item" with metadata and derivative adoption actions.' ),
				'stopping_points' => array( 'Stop at proposal-ready write_actions; Core/host owns proposal creation, approval, commit-preflight, audit, and final execution.' ),
			),
			'npcink-abilities-toolkit/build-image-candidate-adoption-plan' => array(
				'when_to_use'     => array( 'Turn one reviewed image_candidate.v1 or reviewed remote image URL into a dry-run media import, metadata, and optional featured-image adoption plan.' ),
				'not_for'         => array( 'Do not use this to search for images, generate images, approve proposals, import files immediately, optimize derivatives, patch content references, or mutate WordPress.' ),
				'best_for'        => array( 'Toolbox, OpenClaw, and third-party plugin surfaces that already selected one image candidate and need a reusable Core-ready media adoption plan.' ),
				'stopping_points' => array( 'Stop at proposal-ready write_actions; final upload, metadata update, featured-image setting, proposal approval, and execution remain host/Core-governed.' ),
			),
			'npcink-abilities-toolkit/build-media-adoption-preflight-summary' => array(
				'when_to_use'     => array( 'Summarize whether one reviewed derivative artifact is ready to hand into a governed Core media adoption proposal.' ),
				'not_for'         => array( 'Do not use this to generate derivatives, create proposals, approve proposals, replace files, scan all settings, or execute WordPress writes.' ),
				'best_for'        => array( 'Showing a lightweight operator or agent preflight before submitting build-media-optimization-plan or adopt-cloud-media-derivative through Core governance.' ),
				'stopping_points' => array( 'Stop at evidence and next-step guidance; final proposal creation, approval, commit-preflight, adoption, and rollback history remain host/Core-owned.' ),
			),
			'npcink-abilities-toolkit/build-media-adoption-enhancement-plan' => array(
				'when_to_use'     => array( 'Turn one reviewed remote image URL into a proposal-ready import, local optimized derivative, and optional exact post-content media reference repair plan.' ),
				'not_for'         => array( 'Do not use this to search for images, generate images, approve proposals, download files immediately, or mutate WordPress.' ),
				'best_for'        => array( 'OpenClaw page-building flows that already selected a visual asset and need one Core batch proposal for local adoption, WebP-style optimization, and page URL repair.' ),
				'stopping_points' => array( 'Stop at proposal-ready write_actions; final media import, derivative generation, and post-content patching require Core/host approval plus commit-preflight.' ),
			),
				'npcink-abilities-toolkit/build-media-rename-plan' => array(
					'when_to_use'     => array( 'Turn a reviewed target media basename into proposal-ready rename-media-file plus exact post-content reference patch actions.' ),
					'not_for'         => array( 'Do not use this to choose naming policy, compute generated names without caller intent, mutate WordPress, or move files between directories.' ),
					'best_for'        => array( 'OpenClaw or host workflows that already selected a target file name and need one Core proposal that renames the file and keeps embedded image URLs working.' ),
					'stopping_points' => array( 'Stop at proposal-ready write_actions; final rename requires Core/host approval and commit-preflight.' ),
				),
			'npcink-abilities-toolkit/propose-post-taxonomy-terms' => array(
				'when_to_use'     => array( 'Build a deterministic taxonomy assignment proposal using existing terms.' ),
				'not_for'         => array( 'Do not use this to create terms, assign terms, delete terms, or mutate posts.' ),
				'best_for'        => array( 'Preparing bounded dry-run input for a host-governed set-post-terms proposal.' ),
				'stopping_points' => array( 'Stop at the returned proposal; final taxonomy writes require Core approval and host execution.' ),
			),
			'npcink-abilities-toolkit/suggest-post-taxonomy-terms' => array(
				'when_to_use'     => array( 'Rank existing category and tag candidates from current article context.' ),
				'not_for'         => array( 'Do not use this to create missing terms, assign terms, classify with an external model, or mutate posts.' ),
				'best_for'        => array( 'Toolbox, OpenClaw, or third-party support clients that need suggestion-only taxonomy candidates before a reviewed Core proposal.' ),
				'stopping_points' => array( 'Stop at candidate suggestions; accepted taxonomy writes must go through propose-post-taxonomy-terms or a host-governed set-post-terms proposal.' ),
			),
		);
	}
}
