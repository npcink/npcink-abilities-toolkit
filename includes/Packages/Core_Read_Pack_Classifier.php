<?php
/**
 * Core read sub-pack classifier.
 *
 * @package MagickAIAbilities
 */

namespace Magick_AI_Abilities\Packages;

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
				'magick-ai/site-info'                                => 'core_wordpress_read',
				'magick-ai-abilities/wp-diagnostics-summary'        => 'wordpress_diagnostics',
				'magick-ai-abilities/list-workflow-recipes'          => 'workflow_definitions',
				'magick-ai-abilities/get-workflow-recipe'            => 'workflow_definitions',
				'magick-ai/list-post-types'                         => 'core_wordpress_read',
			'magick-ai/list-taxonomies'                         => 'core_wordpress_read',
			'magick-ai/list-media'                              => 'media_governance',
			'magick-ai/list-terms'                              => 'taxonomy_governance',
			'magick-ai/list-taxonomy-terms'                     => 'taxonomy_governance',
			'magick-ai/list-categories'                         => 'taxonomy_governance',
			'magick-ai/list-tags'                               => 'taxonomy_governance',
			'magick-ai/get-term'                                => 'taxonomy_governance',
			'magick-ai/propose-post-excerpt'                    => 'core_wordpress_read',
			'magick-ai/resolve-post-metadata-plan'              => 'core_wordpress_read',
			'magick-ai/list-users'                              => 'core_wordpress_read',
			'magick-ai/list-comments'                           => 'comment_workflow_context',
			'magick-ai/list-menus'                              => 'core_wordpress_read',
			'magick-ai/get-menu'                                => 'core_wordpress_read',
			'magick-ai/search-posts'                            => 'core_wordpress_read',
			'magick-ai/resolve-internal-link-targets'           => 'seo_geo_support',
			'magick-ai/build-inline-image-blocks'               => 'media_governance',
			'magick-ai/build-media-seo-assets'                  => 'media_governance',
			'magick-ai/geo-analyze'                             => 'seo_geo_support',
			'magick-ai/optimize-media-metadata'                 => 'media_governance',
			'magick-ai/position-inline-image-blocks'            => 'media_governance',
			'magick-ai/build-article-optimization-report'       => 'article_workflow_context',
			'magick-ai/seo-report-context'                      => 'seo_geo_support',
			'magick-ai/read-post-optimization-context'          => 'article_workflow_context',
			'magick-ai/build-article-single-optimization-suggest' => 'article_workflow_context',
			'magick-ai/build-article-optimization-apply-plan'   => 'article_workflow_context',
			'magick-ai/compose-article-optimization-apply-result' => 'article_workflow_context',
			'magick-ai/extract-reference-post-style'            => 'article_workflow_context',
			'magick-ai/extract-style-baseline'                  => 'article_workflow_context',
			'magick-ai/build-article-production-fingerprint'    => 'article_workflow_context',
			'magick-ai/check-article-production-duplicate'      => 'article_workflow_context',
			'magick-ai/review-article-output-light'             => 'article_workflow_context',
			'magick-ai/compose-article-production-result'       => 'article_workflow_context',
			'magick-ai/compose-article-draft-result'            => 'article_workflow_context',
			'magick-ai/resolve-article-publication-decision'    => 'article_workflow_context',
			'magick-ai/build-article-style-profile'             => 'article_workflow_context',
			'magick-ai/get-post-stats'                          => 'core_wordpress_read',
			'magick-ai/list-revisions'                          => 'core_wordpress_read',
			'magick-ai/get-post-meta'                           => 'core_wordpress_read',
			'magick-ai/list-pages'                              => 'page_governance',
			'magick-ai/get-page'                                => 'page_governance',
			'magick-ai/inspect-page-structure'                  => 'page_governance',
			'magick-ai/list-pages-tree'                         => 'page_governance',
			'magick-ai/count-posts'                             => 'core_wordpress_read',
			'magick-ai/list-posts'                              => 'core_wordpress_read',
			'magick-ai/get-post'                                => 'core_wordpress_read',
			'magick-ai/get-post-context'                        => 'core_wordpress_read',
			'magick-ai/get-content-publishing-checklist'        => 'article_workflow_context',
			'magick-ai/get-content-inventory-health'            => 'content_operations',
			'magick-ai/get-bulk-publishing-checklist'           => 'article_workflow_context',
			'magick-ai/get-internal-link-opportunity-report'    => 'seo_geo_support',
			'magick-ai/get-site-operations-dashboard'           => 'content_operations',
			'magick-ai/get-post-publish-risk-report'            => 'article_workflow_context',
			'magick-ai/get-article-publish-preflight-context'   => 'article_workflow_context',
			'magick-ai/get-content-refresh-opportunities'       => 'content_operations',
			'magick-ai/get-old-article-refresh-context'         => 'article_workflow_context',
			'magick-ai/get-internal-link-graph-health'          => 'seo_geo_support',
			'magick-ai/get-media-cleanup-opportunities'         => 'media_governance',
			'magick-ai/get-taxonomy-consolidation-suggestions'  => 'taxonomy_governance',
			'magick-ai/get-page-structure-health'               => 'page_governance',
			'magick-ai/get-seo-geo-gap-report'                  => 'seo_geo_support',
			'magick-ai/get-site-style-baseline'                 => 'article_workflow_context',
			'magick-ai/build-article-workflow-context'          => 'article_workflow_context',
			'magick-ai/get-publishing-calendar-context'         => 'article_workflow_context',
			'magick-ai/get-media-inventory-health'              => 'media_governance',
			'magick-ai/get-post-seo-geo-readiness'              => 'seo_geo_support',
			'magick-ai/get-site-topic-coverage-report'          => 'content_operations',
			'magick-ai/get-taxonomy-inventory-health'           => 'taxonomy_governance',
			'magick-ai/get-revision-change-risk-report'         => 'content_operations',
			'magick-ai/resolve-url-to-post'                     => 'core_wordpress_read',
			'magick-ai/get-post-blocks'                         => 'core_wordpress_read',
			'magick-ai/list-post-revisions'                     => 'core_wordpress_read',
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
