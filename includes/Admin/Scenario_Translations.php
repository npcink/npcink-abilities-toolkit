<?php
/**
 * Literal translation map for the workflow scenario card view.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Localizes workflow recipe display strings through literal msgids.
 *
 * The recipe payloads are untranslated English contract data. This map gives
 * the admin card view literal translation calls, so extraction tooling can
 * rediscover every scenario string and the packaged plugin passes the
 * WordPress.org single-string-literal review rule, while the discovery
 * payloads stay locale-independent. Unknown or future recipe text falls back
 * to the original contract string.
 */
final class Scenario_Translations {
	/**
	 * Returns the localized display string for one recipe text.
	 *
	 * @param string $text English contract text.
	 * @return string
	 */
	public static function translate( $text ) {
		$map = self::map();

		return isset( $map[ $text ] ) ? $map[ $text ] : $text;
	}

	/**
	 * Returns the literal translation map keyed by the English recipe text.
	 *
	 * @return array<string,string>
	 */
	private static function map() {
		return array(
			'Article draft handoff' => __( 'Article draft handoff', 'npcink-abilities-toolkit' ),
			'Prepare a reviewed article draft plan.' => __( 'Prepare a reviewed article draft plan.', 'npcink-abilities-toolkit' ),
			'Compose article metadata, links, media SEO, and review signals before creating a draft.' => __( 'Compose article metadata, links, media SEO, and review signals before creating a draft.', 'npcink-abilities-toolkit' ),
			'Build a local article draft handoff without using cloud writing.' => __( 'Build a local article draft handoff without using cloud writing.', 'npcink-abilities-toolkit' ),
			'Article publish preflight' => __( 'Article publish preflight', 'npcink-abilities-toolkit' ),
			'Check whether this draft is ready to publish.' => __( 'Check whether this draft is ready to publish.', 'npcink-abilities-toolkit' ),
			'Review the article before scheduling it.' => __( 'Review the article before scheduling it.', 'npcink-abilities-toolkit' ),
			'Find publication risks and calendar pressure for this post.' => __( 'Find publication risks and calendar pressure for this post.', 'npcink-abilities-toolkit' ),
			'Existing article optimization' => __( 'Existing article optimization', 'npcink-abilities-toolkit' ),
			'Review this existing article for optimization opportunities.' => __( 'Review this existing article for optimization opportunities.', 'npcink-abilities-toolkit' ),
			'Prepare SEO, GEO, excerpt, and slug suggestions for a post.' => __( 'Prepare SEO, GEO, excerpt, and slug suggestions for a post.', 'npcink-abilities-toolkit' ),
			'Build a safe apply plan before asking a host to patch article content.' => __( 'Build a safe apply plan before asking a host to patch article content.', 'npcink-abilities-toolkit' ),
			'Governed media optimization' => __( 'Governed media optimization', 'npcink-abilities-toolkit' ),
			'Prepare a reviewed media optimization plan for one attachment.' => __( 'Prepare a reviewed media optimization plan for one attachment.', 'npcink-abilities-toolkit' ),
			'Build metadata and derivative adoption actions before asking Core for approval.' => __( 'Build metadata and derivative adoption actions before asking Core for approval.', 'npcink-abilities-toolkit' ),
			'Preview media replacement and content reference repairs without changing WordPress.' => __( 'Preview media replacement and content reference repairs without changing WordPress.', 'npcink-abilities-toolkit' ),
			'Article media handoff' => __( 'Article media handoff', 'npcink-abilities-toolkit' ),
			'Prepare media SEO assets for this article.' => __( 'Prepare media SEO assets for this article.', 'npcink-abilities-toolkit' ),
			'Build inline image blocks and placement guidance before importing media.' => __( 'Build inline image blocks and placement guidance before importing media.', 'npcink-abilities-toolkit' ),
			'Create a media handoff that a host can review before any upload or metadata write.' => __( 'Create a media handoff that a host can review before any upload or metadata write.', 'npcink-abilities-toolkit' ),
			'Old article refresh discovery' => __( 'Old article refresh discovery', 'npcink-abilities-toolkit' ),
			'Find old articles that need refreshing.' => __( 'Find old articles that need refreshing.', 'npcink-abilities-toolkit' ),
			'Discover SEO and GEO gaps across existing posts.' => __( 'Discover SEO and GEO gaps across existing posts.', 'npcink-abilities-toolkit' ),
			'Choose candidate posts for an article refresh plan.' => __( 'Choose candidate posts for an article refresh plan.', 'npcink-abilities-toolkit' ),
			'Article production mainline' => __( 'Article production mainline', 'npcink-abilities-toolkit' ),
			'Run the article production mainline with duplicate guard and quality review.' => __( 'Run the article production mainline with duplicate guard and quality review.', 'npcink-abilities-toolkit' ),
			'Build the production fingerprint and check duplicate candidates before drafting continues.' => __( 'Build the production fingerprint and check duplicate candidates before drafting continues.', 'npcink-abilities-toolkit' ),
			'Resolve the publication decision and stop before any host governed publish write.' => __( 'Resolve the publication decision and stop before any host governed publish write.', 'npcink-abilities-toolkit' ),
			'Media alt and SEO enrichment' => __( 'Media alt and SEO enrichment', 'npcink-abilities-toolkit' ),
			'Find attachments with missing alt text in the media library.' => __( 'Find attachments with missing alt text in the media library.', 'npcink-abilities-toolkit' ),
			'Summarize media inventory health gaps for alt and caption enrichment.' => __( 'Summarize media inventory health gaps for alt and caption enrichment.', 'npcink-abilities-toolkit' ),
			'Prepare deterministic title, alt, and caption suggestions for attachment metadata.' => __( 'Prepare deterministic title, alt, and caption suggestions for attachment metadata.', 'npcink-abilities-toolkit' ),
			'Comment compliance handoff' => __( 'Comment compliance handoff', 'npcink-abilities-toolkit' ),
			'Triage comments waiting for moderation.' => __( 'Triage comments waiting for moderation.', 'npcink-abilities-toolkit' ),
			'Prepare moderation suggestions without approving anything.' => __( 'Prepare moderation suggestions without approving anything.', 'npcink-abilities-toolkit' ),
			'Build a reply or moderation handoff for a selected comment.' => __( 'Build a reply or moderation handoff for a selected comment.', 'npcink-abilities-toolkit' ),
			'Media governance scan' => __( 'Media governance scan', 'npcink-abilities-toolkit' ),
			'Find media library cleanup opportunities such as unused attachments and orphans.' => __( 'Find media library cleanup opportunities such as unused attachments and orphans.', 'npcink-abilities-toolkit' ),
			'Plan inventory fixes, renames, and derivative batch cleanup for governed media files.' => __( 'Plan inventory fixes, renames, and derivative batch cleanup for governed media files.', 'npcink-abilities-toolkit' ),
			'Draft a media governance review without deleting or renaming anything yet.' => __( 'Draft a media governance review without deleting or renaming anything yet.', 'npcink-abilities-toolkit' ),
			'Site operations scan' => __( 'Site operations scan', 'npcink-abilities-toolkit' ),
			'Run a site operations scan across content, taxonomy, and page structure.' => __( 'Run a site operations scan across content, taxonomy, and page structure.', 'npcink-abilities-toolkit' ),
			'Show site-wide attention areas from the operations dashboard.' => __( 'Show site-wide attention areas from the operations dashboard.', 'npcink-abilities-toolkit' ),
			'Check operations readiness for content, taxonomy, and pages in one pass.' => __( 'Check operations readiness for content, taxonomy, and pages in one pass.', 'npcink-abilities-toolkit' ),
			'Diagnostics triage' => __( 'Diagnostics triage', 'npcink-abilities-toolkit' ),
			'Collect redacted WordPress diagnostics for a support ticket.' => __( 'Collect redacted WordPress diagnostics for a support ticket.', 'npcink-abilities-toolkit' ),
			'Run an environment triage before deeper agent work.' => __( 'Run an environment triage before deeper agent work.', 'npcink-abilities-toolkit' ),
			'Summarize runtime, plugin, and cache diagnostics for support triage.' => __( 'Summarize runtime, plugin, and cache diagnostics for support triage.', 'npcink-abilities-toolkit' ),
		);
	}
}
