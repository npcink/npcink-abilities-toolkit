<?php
/** Failure and boundary regressions execute independently of the large fixture suite. */
function wp_timezone() { return new DateTimeZone( $GLOBALS['audit_timezone'] ?? 'UTC' ); }
function get_posts( $args ) {
	$GLOBALS['audit_query_args'] = $args;
	return array_slice( $GLOBALS['audit_reference_posts'] ?? array(), 0, $args['posts_per_page'] );
}
class WP_Query {
	public $posts = array();
	public $found_posts = 0;
	public function __construct( $args ) {
		$GLOBALS['audit_query_args'] = $args;
		if ( 'revision' === $args['post_type'] ) {
			$this->found_posts = 5000;
			$this->posts = array( (object) array( 'ID' => 999, 'post_parent' => 101, 'post_author' => 0, 'post_title' => 'Revision' ) );
		}
	}
}
require __DIR__ . '/bootstrap.php';
use Npcink_Abilities_Toolkit\Registry\{Category_Registrar, Ability_Registrar, Contract_Normalizer, Schema_Normalizer};
use Npcink_Abilities_Toolkit\Packages\{Core_Read_Package, Core_Write_Package};
use Npcink_Abilities_Toolkit\Support\Publish_Time;
$c = new Category_Registrar();
$a = new Ability_Registrar( $c, new Contract_Normalizer( new Schema_Normalizer() ) );
$r = new Core_Read_Package( $c, $a );
$w = new Core_Write_Package( $c, $a );
$assertions = 0;
$check = static function ( $ok, $message ) use ( &$assertions ) {
	++$assertions;
	if ( ! $ok ) { throw new RuntimeException( $message ); }
};
$invoke = static function ( $object, $method, ...$args ) {
	$reflection = new ReflectionMethod( $object, $method );
	$reflection->setAccessible( true );
	return $reflection->invoke( $object, ...$args );
};
// Scan the production query branch, including a reference just outside its limit.
for ( $i = 1; $i <= 51; ++$i ) {
	$GLOBALS['audit_reference_posts'][] = (object) array( 'ID' => $i, 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Post', 'post_content' => 51 === $i ? '<img class="wp-image-97" />' : '' );
}
$row = array( 'title' => 'Content Assistant Test Image', 'parent_post_id' => 0 );
$policy = $invoke( $r, 'media_delete_candidate_policy', 97, $row, false, true, array() );
$check( ! $policy['allowed'] && 'reference_scan_incomplete' === $policy['blocked_reason'], 'unknown references never authorize a deletion candidate' );
$check( 51 === $GLOBALS['audit_query_args']['posts_per_page'], 'reference scan includes one sentinel row' );
$GLOBALS['audit_reference_posts'] = array_slice( $GLOBALS['audit_reference_posts'], 0, 50 );
$policy = $invoke( $r, 'media_delete_candidate_policy', 97, $row, false, true, array() );
$check( $policy['allowed'], 'a completed empty scan preserves opted-in candidate behavior' );
$GLOBALS['audit_reference_posts'][0]->post_status = 'private';
$GLOBALS['audit_reference_posts'][0]->post_title = 'Audit confidential';
$GLOBALS['audit_reference_posts'][0]->post_content = '<img class="wp-image-97" />';
$GLOBALS['npcink_abilities_toolkit_unit_current_user_caps']['read_post'] = false;
$usage = $invoke( $r, 'build_media_usage_context', 97 );
$check( array() === $usage['content_reference_posts'] && 1 === $usage['content_reference_count'], 'hidden reference details are omitted while existence blocks deletion' );
$check( false === strpos( json_encode( $usage ), 'Audit confidential' ), 'private title cannot enter usage output' );
unset( $GLOBALS['npcink_abilities_toolkit_unit_current_user_caps'] );

$schema = ( new Schema_Normalizer() )->normalize( array( 'type' => 'object', 'properties' => array( 'displayName' => 'string', 'displayname' => 'number', 'foo.bar' => 'integer', '标题' => 'string', '' => 'boolean' ), 'required' => array( 'displayName' ) ) );
$check( array( 'displayName', 'displayname', 'foo.bar', '标题', '' ) === array_keys( $schema['properties'] ), 'normalization preserves every legal JSON property name without aliases' );
$check( array( 'type' => 'string' ) === $schema['properties']['displayName'] && array( 'displayName' ) === $schema['required'], 'shorthand normalizes without changing required names' );

$GLOBALS['npcink_ai_runtime_wp_ability_context']['context']['approval_commit_authorized'] = true;
$content = '<p>C:\tmp\file {"value":"a\\b"}</p>';
$created = $w->create_draft( array( 'title' => 'Slash round trip', 'content' => $content, 'content_format' => 'html', 'commit' => true ) );
$check( ! is_wp_error( $created ), 'content fixture creation succeeds' );
$id = $created['post_id'];
$check( get_post( $id )->post_content === $content, 'create-draft survives the WordPress unslash boundary' );
$result = $w->patch_post_content( array( 'post_id' => $id, 'operations' => array( array( 'op' => 'replace', 'find' => 'value', 'replace' => 'newvalue' ) ), 'commit' => true ) );
$check( ! is_wp_error( $result ) && str_replace( 'value', 'newvalue', $content ) === get_post( $id )->post_content, 'patch preserves slashes in both changed and unchanged regions' );

$GLOBALS['audit_timezone'] = 'Asia/Shanghai';
$time = Publish_Time::parse( '2030-01-02 09:00:00', 'site' );
$check( array( 'local' => '2030-01-02 09:00:00', 'gmt' => '2030-01-02 01:00:00' ) === $time, 'site wall time is parsed in the site timezone' );
$time = Publish_Time::parse( '2030-01-02T09:00:00+08:00', 'utc' );
$check( '2030-01-02 01:00:00' === $time['gmt'], 'explicit timestamp offset takes precedence' );
$time = Publish_Time::parse( '2030-01-02 09:00:00', 'utc' );
$check( '2030-01-02 17:00:00' === $time['local'], 'UTC wall time converts to the site timezone' );
$check( '2030-01-02 09:00:00' === Publish_Time::parse( '2030-01-02T09:00:00Z', 'site' )['gmt'], 'RFC3339 Z is parsed as UTC' );
$GLOBALS['audit_timezone'] = 'America/New_York';
foreach ( array( '2030-01-02T09:00:00+14:59', 'tomorrow', '2030-02-30 09:00:00', '2030-03-10 02:30:00', '2030-11-03 01:30:00', '2020-01-01 00:00:00' ) as $invalid ) {
	$check( is_wp_error( Publish_Time::parse( $invalid, 'site' ) ), 'reject invalid, past, missing or ambiguous wall time: ' . $invalid );
}
$check( ! is_wp_error( Publish_Time::parse( '2030-11-03T01:30:00-04:00', 'site' ) ), 'an explicit offset resolves a repeated DST time' );

$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][101] = (object) array( 'ID' => 101, 'post_type' => 'post', 'post_status' => 'draft' );
foreach ( array( 'list_revisions', 'list_post_revisions' ) as $method ) {
	$result = $r->$method( array( 'post_id' => 101, 'per_page' => 1, 'page' => 2 ) );
	$check( 5000 === $result['total'] && 1 === count( $result['items'] ), $method . ' preserves total with one loaded revision' );
	$check( 1 === $GLOBALS['audit_query_args']['posts_per_page'] && 2 === $GLOBALS['audit_query_args']['paged'], $method . ' paginates in the query' );
}
$plugin = Npcink_Abilities_Toolkit\Plugin::instance();
$plugin->bump_read_cache_version();
$key = $invoke( $r, 'build_read_cache_key', 'content_inventory_health', array() );
$invoke( $r, 'set_cached_read_response', $key, array( 'success' => true, 'data' => array( 'state' => 'intermediate' ) ), 'audit' );
$plugin->bump_read_cache_version();
$new_key = $invoke( $r, 'build_read_cache_key', 'content_inventory_health', array() );
$check( $key !== $new_key && null === $invoke( $r, 'get_cached_read_response', $new_key ), 'write-read-write invalidates an intermediate cached report' );
echo "OK: {$assertions} audit boundary regressions\n";
