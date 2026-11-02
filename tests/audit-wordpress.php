<?php
/** Real WordPress persistence regressions. All writes own throwaway fixtures. */
use Npcink_Abilities_Toolkit\Registry\{Category_Registrar, Ability_Registrar, Contract_Normalizer, Schema_Normalizer};
use Npcink_Abilities_Toolkit\Packages\{Core_Read_Package, Core_Write_Package, Core_Destructive_Package};
if ( ! defined( 'ABSPATH' ) ) { throw new RuntimeException( 'Run inside WordPress.' ); }
$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
if ( ! $admins ) { throw new RuntimeException( 'An administrator actor is required.' ); }
$actor = get_current_user_id();
wp_set_current_user( (int) $admins[0] );
$c = new Category_Registrar();
$a = new Ability_Registrar( $c, new Contract_Normalizer( new Schema_Normalizer() ) );
$w = new Core_Write_Package( $c, $a );
$d = new Core_Destructive_Package( $c, $a );
$r = new Core_Read_Package( $c, $a );
$checks = 0;
$check = static function ( $ok, $message ) use ( &$checks ) { ++$checks; if ( ! $ok ) { throw new RuntimeException( $message ); } };
$posts = array();
$terms = array();
$run = 'npcink-audit-' . bin2hex( random_bytes( 8 ) );
$approval = static function ( $allowed, $id ) { return in_array( $id, array( 'npcink-abilities-toolkit/create-draft', 'npcink-abilities-toolkit/patch-post-content', 'npcink-abilities-toolkit/set-post-terms' ), true ); };
$deny = static function () { return false; };
$zone = static function () { return 'Asia/Shanghai'; };
try {
	add_filter( 'npcink_abilities_toolkit_write_commit_allowed', $approval, PHP_INT_MAX, 2 );
	$content = '<p>C:\tmp\file {"value":"a\\b"}</p>';
	$created = $w->create_draft( array( 'title' => $run, 'content' => $content, 'content_format' => 'html', 'commit' => true ) );
	$check( ! is_wp_error( $created ), 'real WordPress draft created' );
	$id = (int) $created['post_id'];
	$posts[] = $id;
	$check( get_post_field( 'post_content', $id, 'raw' ) === $content, 'real database round trip preserves backslashes' );
	$patched = $w->patch_post_content( array( 'post_id' => $id, 'operations' => array( array( 'op' => 'replace', 'find' => 'value', 'replace' => 'changed' ) ), 'commit' => true ) );
	$check( ! is_wp_error( $patched ) && get_post_field( 'post_content', $id, 'raw' ) === str_replace( 'value', 'changed', $content ), 'real patch preserves unchanged escaped content' );
	add_filter( 'npcink_abilities_toolkit_write_commit_allowed', $deny, PHP_INT_MAX );
	$term_input = array( 'post_id' => $id, 'taxonomy' => 'post_tag', 'terms' => array( $run ), 'create_missing' => true, 'commit' => true, 'dry_run' => false );
	foreach ( array( array( $w, 'set_post_terms' ), array( $d, 'bulk_update_post_terms' ) ) as $callback ) {
		$denied = call_user_func( $callback, $term_input );
		$check( is_wp_error( $denied ) && 'npcink_abilities_toolkit_host_approval_required' === $denied->get_error_code(), 'real taxonomy write denied before approval' );
		$unexpected = term_exists( $run, 'post_tag' );
		if ( $unexpected ) { $terms[] = (int) $unexpected['term_id']; }
		$check( ! $unexpected, 'real database contains no preapproval term' );
	}
	remove_filter( 'npcink_abilities_toolkit_write_commit_allowed', $deny, PHP_INT_MAX );
	$assigned = $w->set_post_terms( $term_input );
	$term = term_exists( $run, 'post_tag' );
	if ( $term ) { $terms[] = (int) $term['term_id']; }
	$check( ! is_wp_error( $assigned ) && $term && has_term( (int) $term['term_id'], 'post_tag', $id ), 'approved taxonomy creation assigns the actual term' );
	$wrong_type = $d->delete_media_permanently( array( 'attachment_id' => $id, 'commit' => true ) );
	$check( is_wp_error( $wrong_type ) && get_post( $id ), 'an existing post is not reported as a deleted attachment' );
	add_filter( 'pre_option_timezone_string', $zone );
	$preview = $w->schedule_post( array( 'post_id' => $id, 'publish_at' => '2030-01-02 09:00:00', 'timezone' => 'site' ) );
	$check( ! is_wp_error( $preview ) && '2030-01-02 09:00:00' === $preview['publish_at'] && '2030-01-02 01:00:00' === $preview['publish_at_gmt'], 'real wp_timezone preserves the site wall time' );
	$page = $r->list_post_revisions( array( 'post_id' => $id, 'per_page' => 1 ) );
	$check( ! is_wp_error( $page ) && count( $page['items'] ) <= 1 && $page['total'] >= count( $page['items'] ), 'real revision pagination returns a bounded page and total' );
} finally {
	remove_filter( 'pre_option_timezone_string', $zone );
	remove_filter( 'npcink_abilities_toolkit_write_commit_allowed', $deny, PHP_INT_MAX );
	remove_filter( 'npcink_abilities_toolkit_write_commit_allowed', $approval, PHP_INT_MAX );
	foreach ( $posts as $id ) { wp_delete_post( $id, true ); }
	foreach ( array_unique( $terms ) as $id ) { wp_delete_term( $id, 'post_tag' ); }
	wp_set_current_user( $actor );
}
echo "Audit WordPress OK: {$checks} assertions\n";
