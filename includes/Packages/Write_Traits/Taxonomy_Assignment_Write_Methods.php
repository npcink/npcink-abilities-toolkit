<?php
/**
 * Shared read-only resolution and approved creation of taxonomy terms.
 *
 * @package NpcinkAbilitiesToolkit
 */
namespace Npcink_Abilities_Toolkit\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Resolves labels without granting commit authority. */
trait Taxonomy_Assignment_Write_Methods {
	/**
	 * Resolves term IDs from explicit IDs and labels.
	 *
	 * @param string       $taxonomy Taxonomy id.
	 * @param array<mixed> $term_ids Explicit term ids.
	 * @param array<mixed> $terms Term labels.
	 * @param bool         $create_missing Whether missing labels may be created during commit.
	 * @param bool         $dry_run Whether this is a dry-run.
	 * @return array{term_ids:array<int,int>,missing:array<int,string>}|\WP_Error
	 */
	private function resolve_term_ids_for_assignment( $taxonomy, array $term_ids, array $terms, $create_missing, $dry_run ) {
		$resolved = array();
		$missing  = array();
		foreach ( $term_ids as $term_id ) {
			$term_id = absint( $term_id );
			if ( $term_id <= 0 ) {
				continue;
			}
			$term = get_term( $term_id, $taxonomy );
			if ( $term && ! is_wp_error( $term ) && is_object( $term ) && ! empty( $term->term_id ) ) {
				$resolved[] = absint( $term->term_id );
			}
		}

		foreach ( $terms as $raw_term ) {
			$term_label = sanitize_text_field( (string) $raw_term );
			if ( '' === $term_label ) {
				continue;
			}
			$term_slug = sanitize_title( $term_label );
			$term      = get_term_by( 'slug', $term_slug, $taxonomy );
			if ( ! is_object( $term ) || empty( $term->term_id ) ) {
				$term = get_term_by( 'name', $term_label, $taxonomy );
			}
			if ( is_object( $term ) && ! empty( $term->term_id ) ) {
				$resolved[] = absint( $term->term_id );
				continue;
			}
			if ( ! $create_missing ) {
				continue;
			}
			$missing[] = $term_label;
			if ( $dry_run ) {
				continue;
			}
			$cap_check = $this->check_taxonomy_capability( $taxonomy, 'manage_terms' );
			if ( is_wp_error( $cap_check ) ) {
				return $cap_check;
			}
			$created = wp_insert_term( $term_label, $taxonomy, array( 'slug' => $term_slug ) );
			if ( is_wp_error( $created ) ) {
				return $created;
			}
			$created_term_id = absint( $created['term_id'] ?? 0 );
			if ( $created_term_id > 0 ) {
				$resolved[] = $created_term_id;
			}
		}

		return array(
			'term_ids' => array_values( array_unique( array_filter( array_map( 'absint', $resolved ) ) ) ),
			'missing'  => array_values( array_unique( $missing ) ),
		);
	}
}
