<?php
/** Isolated failure tests use real callbacks and stateful taxonomy API doubles. */
function get_term_by( $field, $value, $taxonomy ) {
	foreach ( $GLOBALS['audit_terms'] ?? array() as $term ) {
		if ( $term->$field === $value && $term->taxonomy === $taxonomy ) {
			return $term;
		}
	}
	return false;
}
function wp_insert_term( $name, $taxonomy, $args ) {
	$id = 900 + count( $GLOBALS['audit_terms'] ?? array() );
	$GLOBALS['audit_terms'][ $id ] = (object) array( 'term_id' => $id, 'name' => $name, 'slug' => $args['slug'], 'taxonomy' => $taxonomy );
	return array( 'term_id' => $id );
}
function get_objects_in_term( $ids, $taxonomy ) {
	return 'enumerate' === ( $GLOBALS['audit_fail'] ?? '' ) ? new WP_Error( 'enumerate_failed', 'failure' ) : array( 77 );
}
function wp_get_object_terms( $id, $taxonomy, $args ) {
	return 'read' === ( $GLOBALS['audit_fail'] ?? '' ) ? new WP_Error( 'read_failed', 'failure' ) : ( $GLOBALS['audit_relations'] ?? array( 302 ) );
}
function wp_set_object_terms( $id, $terms, $taxonomy, $append = false ) {
	if ( 'write' === ( $GLOBALS['audit_fail'] ?? '' ) ) {
		return new WP_Error( 'write_failed', 'failure' );
	}
	if ( 'no_op' !== ( $GLOBALS['audit_fail'] ?? '' ) ) {
		$GLOBALS['audit_relations'] = $terms;
	}
	return $terms;
}
class WP_Query {
	public $posts = array( 77 );
	public function __construct( $args ) {}
}
require __DIR__ . '/bootstrap.php';
use Npcink_Abilities_Toolkit\Registry\{Category_Registrar, Ability_Registrar, Contract_Normalizer, Schema_Normalizer};
use Npcink_Abilities_Toolkit\Packages\{Core_Write_Package, Core_Destructive_Package};
$categories = new Category_Registrar();
$abilities = new Ability_Registrar( $categories, new Contract_Normalizer( new Schema_Normalizer() ) );
$write = new Core_Write_Package( $categories, $abilities );
$destructive = new Core_Destructive_Package( $categories, $abilities );
$check = static function ( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
};
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][77] = (object) array( 'ID' => 77, 'post_type' => 'post', 'post_status' => 'draft' );
foreach ( array( array( $write, 'set_post_terms' ), array( $destructive, 'bulk_update_post_terms' ) ) as $callback ) {
	$GLOBALS['audit_terms'] = array();
	unset( $GLOBALS['npcink_ai_runtime_wp_ability_context'] );
	$input = array( 'post_id' => 77, 'taxonomy' => 'category', 'terms' => array( 'Approval Test' ), 'create_missing' => true, 'commit' => true, 'dry_run' => false );
	$result = call_user_func( $callback, $input );
	$check( is_wp_error( $result ) && 'npcink_abilities_toolkit_host_approval_required' === $result->get_error_code(), $callback[1] . ' denies missing approval' );
	$check( array() === $GLOBALS['audit_terms'], $callback[1] . ' creates no term before approval' );
	$GLOBALS['npcink_ai_runtime_wp_ability_context']['context']['approval_commit_authorized'] = true;
	$result = call_user_func( $callback, $input );
	$check( ! is_wp_error( $result ) && 1 === count( $GLOBALS['audit_terms'] ), $callback[1] . ' creates a missing term only after approval' );
}
foreach ( array( 'enumerate', 'read', 'write', 'no_op', '' ) as $failure ) {
	$GLOBALS['audit_fail'] = $failure;
	$GLOBALS['audit_relations'] = array( 302 );
	$GLOBALS['npcink_abilities_toolkit_unit_deleted_terms'] = array();
	$result = $destructive->merge_terms( array( 'taxonomy' => 'category', 'target_term_id' => 301, 'source_term_ids' => array( 302 ), 'commit' => true ) );
	$check( ! is_wp_error( $result ), 'merge exposes a per-source result' );
	$check( ( '' === $failure ? array( 302 ) : array() ) === $GLOBALS['npcink_abilities_toolkit_unit_deleted_terms'], 'source survives ' . $failure );
	$check( ( '' === $failure ) === empty( $result['failures'] ), 'merge reports ' . $failure );
}
$result = $destructive->delete_media_permanently( array( 'attachment_id' => 77, 'commit' => true ) );
$check( is_wp_error( $result ) && get_post( 77 ), 'a nonattachment never reports successful deletion' );
echo "OK: taxonomy approval and failure invariants\n";
