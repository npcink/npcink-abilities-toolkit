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
	 * Classifies a built-in read ability into a coarse sub-pack.
	 *
	 * @param string $ability_id Ability id.
	 * @return string
	 */
	public static function classify( $ability_id ) {
		$ability_id = (string) $ability_id;

		if ( 'magick-ai-abilities/wp-diagnostics-summary' === $ability_id ) {
			return 'wordpress_diagnostics';
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
