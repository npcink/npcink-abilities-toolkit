<?php
/**
 * Core read sub-pack classifier.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classifies built-in read-only abilities into coarse host-selectable packs.
 */
final class Core_Read_Pack_Classifier {
	/**
	 * Returns the default enabled read-only sub-packs.
	 *
	 * @return string[]
	 */
	public static function default_packs() {
			return array(
				'core_wordpress_read',
				'wordpress_diagnostics',
				'workflow_definitions',
				'comment_workflow_context',
				'article_workflow_context',
			'content_operations',
			'media_governance',
			'taxonomy_governance',
			'page_governance',
			'seo_geo_support',
		);
	}

	/**
	 * Returns the explicit built-in read ability to sub-pack map.
	 *
	 * This map is the stable split point for future source-file extraction. Keep
	 * new built-in read abilities here instead of relying on name heuristics.
	 *
	 * @return array<string,string>
	 */
	public static function known_pack_map() {
			return array(
				'npcink-abilities-toolkit/site-info'                                => 'core_wordpress_read',
				'npcink-abilities-toolkit/wp-diagnostics-summary'        => 'wordpress_diagnostics',
				'npcink-abilities-toolkit/wp-ops-diagnostics-detail'     => 'wordpress_diagnostics',
				'npcink-abilities-toolkit/list-workflow-recipes'          => 'workflow_definitions',
				'npcink-abilities-toolkit/get-workflow-recipe'            => 'workflow_definitions',
				'npcink-abilities-toolkit/list-post-types'                         => 'core_wordpress_read',
			'npcink-abilities-toolkit/list-taxonomies'                         => 'core_wordpress_read',
			'npcink-abilities-toolkit/list-media'                              => 'media_governance',
			'npcink-abilities-toolkit/resolve-media-attachment-by-url'         => 'media_governance',
			'npcink-abilities-toolkit/list-terms'                              => 'taxonomy_governance',
			'npcink-abilities-toolkit/list-taxonomy-terms'                     => 'taxonomy_governance',
			'npcink-abilities-toolkit/list-categories'                         => 'taxonomy_governance',
			'npcink-abilities-toolkit/list-tags'                               => 'taxonomy_governance',
			'npcink-abilities-toolkit/get-term'                                => 'taxonomy_governance',
			'npcink-abilities-toolkit/propose-post-excerpt'                    => 'core_wordpress_read',
			'npcink-abilities-toolkit/resolve-post-metadata-plan'              => 'core_wordpress_read',
			'npcink-abilities-toolkit/route-content-intent'                    => 'page_governance',
			'npcink-abilities-toolkit/evaluate-gutenberg-recipe-suite'         => 'page_governance',
			'npcink-abilities-toolkit/get-gutenberg-block-capability-catalog'  => 'page_governance',
			'npcink-abilities-toolkit/compose-gutenberg-block-plan'            => 'page_governance',
			'npcink-abilities-toolkit/inspect-gutenberg-composition-contract'  => 'page_governance',
					'npcink-abilities-toolkit/get-block-theme-context'                 => 'page_governance',
					'npcink-abilities-toolkit/get-template-blocks'                      => 'page_governance',
					'npcink-abilities-toolkit/get-template-part-blocks'                 => 'page_governance',
					'npcink-abilities-toolkit/inspect-block-theme-surface'              => 'page_governance',
					'npcink-abilities-toolkit/build-block-theme-site-plan'              => 'page_governance',
					'npcink-abilities-toolkit/build-pattern-page-plan'                 => 'page_governance',
					'npcink-abilities-toolkit/review-pattern-page'                     => 'page_governance',
					'npcink-abilities-toolkit/review-block-editor-surface'             => 'page_governance',
			'npcink-abilities-toolkit/build-article-block-plan'                => 'article_workflow_context',
			'npcink-abilities-toolkit/list-users'                              => 'core_wordpress_read',
			'npcink-abilities-toolkit/list-comments'                           => 'comment_workflow_context',
			'npcink-abilities-toolkit/list-menus'                              => 'core_wordpress_read',
			'npcink-abilities-toolkit/get-menu'                                => 'core_wordpress_read',
			'npcink-abilities-toolkit/search-posts'                            => 'core_wordpress_read',
			'npcink-abilities-toolkit/resolve-internal-link-targets'           => 'seo_geo_support',
			'npcink-abilities-toolkit/build-inline-image-blocks'               => 'media_governance',
			'npcink-abilities-toolkit/build-media-seo-assets'                  => 'media_governance',
			'npcink-abilities-toolkit/geo-analyze'                             => 'seo_geo_support',
			'npcink-abilities-toolkit/optimize-media-metadata'                 => 'media_governance',
			'npcink-abilities-toolkit/position-inline-image-blocks'            => 'media_governance',
			'npcink-abilities-toolkit/build-article-optimization-report'       => 'article_workflow_context',
			'npcink-abilities-toolkit/seo-report-context'                      => 'seo_geo_support',
			'npcink-abilities-toolkit/read-post-optimization-context'          => 'article_workflow_context',
			'npcink-abilities-toolkit/build-article-single-optimization-suggest' => 'article_workflow_context',
			'npcink-abilities-toolkit/build-article-optimization-apply-plan'   => 'article_workflow_context',
			'npcink-abilities-toolkit/compose-article-optimization-apply-result' => 'article_workflow_context',
			'npcink-abilities-toolkit/extract-reference-post-style'            => 'article_workflow_context',
			'npcink-abilities-toolkit/extract-style-baseline'                  => 'article_workflow_context',
			'npcink-abilities-toolkit/build-article-production-fingerprint'    => 'article_workflow_context',
			'npcink-abilities-toolkit/check-article-production-duplicate'      => 'article_workflow_context',
			'npcink-abilities-toolkit/review-article-output-light'             => 'article_workflow_context',
			'npcink-abilities-toolkit/compose-article-production-result'       => 'article_workflow_context',
			'npcink-abilities-toolkit/compose-article-draft-result'            => 'article_workflow_context',
			'npcink-abilities-toolkit/resolve-article-publication-decision'    => 'article_workflow_context',
			'npcink-abilities-toolkit/build-article-style-profile'             => 'article_workflow_context',
			'npcink-abilities-toolkit/get-post-stats'                          => 'core_wordpress_read',
			'npcink-abilities-toolkit/list-revisions'                          => 'core_wordpress_read',
			'npcink-abilities-toolkit/get-post-meta'                           => 'core_wordpress_read',
			'npcink-abilities-toolkit/search-post-meta'                        => 'core_wordpress_read',
			'npcink-abilities-toolkit/list-pages'                              => 'page_governance',
			'npcink-abilities-toolkit/get-page'                                => 'page_governance',
			'npcink-abilities-toolkit/inspect-page-structure'                  => 'page_governance',
			'npcink-abilities-toolkit/list-pages-tree'                         => 'page_governance',
			'npcink-abilities-toolkit/count-posts'                             => 'core_wordpress_read',
			'npcink-abilities-toolkit/list-posts'                              => 'core_wordpress_read',
			'npcink-abilities-toolkit/get-post'                                => 'core_wordpress_read',
			'npcink-abilities-toolkit/get-post-context'                        => 'core_wordpress_read',
			'npcink-abilities-toolkit/get-content-publishing-checklist'        => 'article_workflow_context',
			'npcink-abilities-toolkit/get-content-inventory-health'            => 'content_operations',
			'npcink-abilities-toolkit/get-nonproduction-content-inventory'              => 'content_operations',
			'npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan'         => 'content_operations',
			'npcink-abilities-toolkit/build-content-inventory-fix-plan'        => 'content_operations',
			'npcink-abilities-toolkit/get-bulk-publishing-checklist'           => 'article_workflow_context',
			'npcink-abilities-toolkit/get-internal-link-opportunity-report'    => 'seo_geo_support',
			'npcink-abilities-toolkit/get-site-operations-dashboard'           => 'content_operations',
			'npcink-abilities-toolkit/get-post-publish-risk-report'            => 'article_workflow_context',
			'npcink-abilities-toolkit/get-article-publish-preflight-context'   => 'article_workflow_context',
			'npcink-abilities-toolkit/get-content-refresh-opportunities'       => 'content_operations',
			'npcink-abilities-toolkit/get-old-article-refresh-context'         => 'article_workflow_context',
			'npcink-abilities-toolkit/get-internal-link-graph-health'          => 'seo_geo_support',
				'npcink-abilities-toolkit/get-media-cleanup-opportunities'         => 'media_governance',
				'npcink-abilities-toolkit/list-media-backups'                      => 'media_governance',
				'npcink-abilities-toolkit/build-media-inventory-fix-plan'          => 'media_governance',
			'npcink-abilities-toolkit/get-taxonomy-consolidation-suggestions'  => 'taxonomy_governance',
			'npcink-abilities-toolkit/propose-post-taxonomy-terms'             => 'taxonomy_governance',
			'npcink-abilities-toolkit/get-page-structure-health'               => 'page_governance',
			'npcink-abilities-toolkit/get-seo-geo-gap-report'                  => 'seo_geo_support',
			'npcink-abilities-toolkit/get-site-style-baseline'                 => 'article_workflow_context',
			'npcink-abilities-toolkit/build-article-workflow-context'          => 'article_workflow_context',
			'npcink-abilities-toolkit/get-publishing-calendar-context'         => 'article_workflow_context',
			'npcink-abilities-toolkit/get-media-inventory-health'              => 'media_governance',
			'npcink-abilities-toolkit/get-post-seo-geo-readiness'              => 'seo_geo_support',
			'npcink-abilities-toolkit/get-site-topic-coverage-report'          => 'content_operations',
			'npcink-abilities-toolkit/get-taxonomy-inventory-health'           => 'taxonomy_governance',
			'npcink-abilities-toolkit/get-revision-change-risk-report'         => 'content_operations',
			'npcink-abilities-toolkit/resolve-url-to-post'                     => 'core_wordpress_read',
			'npcink-abilities-toolkit/get-post-blocks'                         => 'core_wordpress_read',
			'npcink-abilities-toolkit/list-post-revisions'                     => 'core_wordpress_read',
			'npcink-abilities-toolkit/build-media-reference-repair-plan'       => 'media_governance',
			'npcink-abilities-toolkit/build-media-adoption-enhancement-plan'   => 'media_governance',
			'npcink-abilities-toolkit/build-media-settings-reference-repair-plan' => 'media_governance',
			'npcink-abilities-toolkit/inspect-media-asset'                     => 'media_governance',
			'npcink-abilities-toolkit/build-media-derivative-cloud-request'    => 'media_governance',
			'npcink-abilities-toolkit/build-media-optimization-plan'           => 'media_governance',
			'npcink-abilities-toolkit/build-media-adoption-preflight-summary'  => 'media_governance',
			'npcink-abilities-toolkit/build-media-rename-plan'                 => 'media_governance',
			'npcink-abilities-toolkit/build-media-derivative-batch-plan'       => 'media_governance',
		);
	}

	/**
	 * Classifies a built-in read ability into a coarse sub-pack.
	 *
	 * @param string $ability_id Ability id.
	 * @return string
	 */
	public static function classify( $ability_id ) {
		$ability_id = (string) $ability_id;
		$known      = self::known_pack_map();

		if ( isset( $known[ $ability_id ] ) ) {
			return $known[ $ability_id ];
		}

		if ( false !== strpos( $ability_id, 'comment' ) ) {
			return 'comment_workflow_context';
		}

		if ( false !== strpos( $ability_id, 'media' ) || false !== strpos( $ability_id, 'image' ) ) {
			return 'media_governance';
		}

		if ( false !== strpos( $ability_id, 'term' ) || false !== strpos( $ability_id, 'taxonomy' ) || false !== strpos( $ability_id, 'categor' ) || false !== strpos( $ability_id, 'tag' ) ) {
			return 'taxonomy_governance';
		}

		if ( false !== strpos( $ability_id, 'page' ) ) {
			return 'page_governance';
		}

		if ( false !== strpos( $ability_id, 'seo' ) || false !== strpos( $ability_id, 'geo' ) || false !== strpos( $ability_id, 'link' ) ) {
			return 'seo_geo_support';
		}

		if ( false !== strpos( $ability_id, 'article' ) || false !== strpos( $ability_id, 'style' ) || false !== strpos( $ability_id, 'publish' ) || false !== strpos( $ability_id, 'workflow' ) || false !== strpos( $ability_id, 'optimization' ) ) {
			return 'article_workflow_context';
		}

		if ( false !== strpos( $ability_id, 'inventory' ) || false !== strpos( $ability_id, 'operations' ) || false !== strpos( $ability_id, 'health' ) || false !== strpos( $ability_id, 'calendar' ) || false !== strpos( $ability_id, 'refresh' ) || false !== strpos( $ability_id, 'risk' ) || false !== strpos( $ability_id, 'coverage' ) ) {
			return 'content_operations';
		}

		return 'core_wordpress_read';
	}
}
