<?php
/**
 * Lightweight performance smoke for bounded read-only ability chains.
 *
 * @package NpcinkAbilitiesToolkit
 */

require_once __DIR__ . '/bootstrap.php';

use Npcink_Abilities_Toolkit\Packages\Core_Comment_Package;
use Npcink_Abilities_Toolkit\Packages\Core_Read_Package;
use Npcink_Abilities_Toolkit\Registry\Ability_Registrar;
use Npcink_Abilities_Toolkit\Registry\Annotation_Normalizer;
use Npcink_Abilities_Toolkit\Registry\Category_Registrar;
use Npcink_Abilities_Toolkit\Registry\Contract_Normalizer;
use Npcink_Abilities_Toolkit\Registry\Schema_Normalizer;

$schema_normalizer = new Schema_Normalizer();
$contract_normalizer = new Contract_Normalizer( $schema_normalizer, new Annotation_Normalizer() );
$categories = new Category_Registrar();
$abilities = new Ability_Registrar( $categories, $contract_normalizer );
$core_read_package = new Core_Read_Package( $categories, $abilities );
$core_comment_package = new Core_Comment_Package( $categories, $abilities );
$read_log_tail = new ReflectionMethod( $core_read_package, 'read_diagnostics_log_contents' );
$read_log_tail->setAccessible( true );

$GLOBALS['npcink_abilities_toolkit_unit_style_posts'] = array();
$GLOBALS['npcink_abilities_toolkit_unit_comments'] = array();
$GLOBALS['npcink_abilities_toolkit_unit_post_meta'] = array();
$GLOBALS['npcink_abilities_toolkit_unit_terms'] = array(
	'category' => array(
		(object) array(
			'term_id'     => 301,
			'name'        => 'Workflow',
			'slug'        => 'workflow',
			'description' => '',
			'count'       => 3,
			'parent'      => 0,
		),
	),
);
$GLOBALS['npcink_abilities_toolkit_unit_transients'] = array();

for ( $i = 1; $i <= 40; ++$i ) {
	$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][ $i ] = (object) array(
		'ID'            => $i,
		'post_author'   => 7,
		'post_title'    => 'Workflow performance sample ' . $i,
		'post_content'  => '<p>Workflow sample content with enough words to exercise inventory, SEO, GEO, internal link, and article preflight scans.</p><p><a href="https://example.test/?p=1">Related workflow note</a></p>',
		'post_excerpt'  => 0 === $i % 3 ? '' : 'Sample excerpt.',
		'post_name'     => 'workflow-performance-sample-' . $i,
		'post_status'   => 0 === $i % 7 ? 'draft' : 'publish',
		'post_type'     => 'post',
		'post_date'     => '2024-01-01 00:00:00',
		'post_modified' => '2024-01-0' . ( ( $i % 9 ) + 1 ) . ' 00:00:00',
	);
}

for ( $i = 1; $i <= 12; ++$i ) {
	$GLOBALS['npcink_abilities_toolkit_unit_comments'][ $i ] = (object) array(
		'comment_ID'       => $i,
		'comment_post_ID'  => 1,
		'comment_author'   => 'Reader ' . $i,
		'comment_approved' => 'hold',
		'comment_content'  => 0 === $i % 4 ? 'Buy cheap pills now.' : 'Please review this workflow question.',
	);
}

$large_log_path = sys_get_temp_dir() . '/npcink-abilities-toolkit-perf-large-log-' . getmypid() . '.log';
file_put_contents(
	$large_log_path,
	str_repeat( "[2026-06-19 00:00:00] PHP Warning: bounded diagnostics log tail smoke.\n", 4096 )
);

$targets = array(
	'catalog_fingerprint_cached' => array(
		'budget_ms' => 75,
		'callback'  => static function () use ( $abilities ) {
			$first = $abilities->catalog_fingerprint();
			for ( $i = 0; $i < 50; ++$i ) {
				if ( $first !== $abilities->catalog_fingerprint() ) {
					return array( 'success' => false );
				}
			}
			return array( 'success' => '' !== $first );
		},
	),
	'diagnostics_large_log_tail' => array(
		'budget_ms' => 75,
		'callback'  => static function () use ( $core_read_package, $read_log_tail, $large_log_path ) {
			$contents = $read_log_tail->invoke( $core_read_package, $large_log_path, 262144 );
			return array(
				'success' => is_string( $contents )
					&& strlen( $contents ) <= 262144
					&& false !== strpos( $contents, 'bounded diagnostics log tail smoke' ),
			);
		},
	),
	'content_inventory' => array(
		'budget_ms' => 500,
		'callback'  => static function () use ( $core_read_package ) {
			return $core_read_package->get_content_inventory_health(
				array(
					'post_type' => 'post',
					'status'    => 'any',
					'per_page'  => 40,
				)
			);
		},
	),
	'seo_geo_gap_cached' => array(
		'budget_ms' => 500,
		'callback'  => static function () use ( $core_read_package ) {
			$core_read_package->get_seo_geo_gap_report(
				array(
					'post_type'  => 'post',
					'status'     => 'any',
					'per_page'   => 40,
					'topic_seed' => 'workflow',
				)
			);
			return $core_read_package->get_seo_geo_gap_report(
				array(
					'post_type'  => 'post',
					'status'     => 'any',
					'per_page'   => 40,
					'topic_seed' => 'workflow',
				)
			);
		},
	),
	'article_publish_preflight' => array(
		'budget_ms' => 700,
		'callback'  => static function () use ( $core_read_package ) {
			return $core_read_package->get_article_publish_preflight_context(
				array(
					'post_id'       => 1,
					'focus_keyword' => 'workflow',
				)
			);
		},
	),
	'old_article_refresh' => array(
		'budget_ms' => 700,
		'callback'  => static function () use ( $core_read_package ) {
			return $core_read_package->get_old_article_refresh_context(
				array(
					'post_type'  => 'post',
					'status'     => 'any',
					'per_page'   => 40,
					'topic_seed' => 'workflow',
					'post_id'    => 1,
				)
			);
		},
	),
	'comment_compliance_handoff' => array(
		'budget_ms' => 250,
		'callback'  => static function () use ( $core_comment_package ) {
			return $core_comment_package->get_comment_compliance_handoff(
				array(
					'post_id'             => 1,
					'per_page'            => 12,
					'selected_comment_id' => 1,
				)
			);
		},
	),
);

$failures = 0;
$rows = array();
foreach ( $targets as $name => $target ) {
	$start = microtime( true );
	$result = call_user_func( $target['callback'] );
	$elapsed_ms = ( microtime( true ) - $start ) * 1000;
	$success = is_array( $result ) && true === ( $result['success'] ?? null );
	$within_budget = $elapsed_ms <= (float) $target['budget_ms'];
	$rows[] = sprintf(
		'%s: %.2fms budget=%sms success=%s',
		$name,
		$elapsed_ms,
		(string) $target['budget_ms'],
		$success ? 'yes' : 'no'
	);
	if ( ! $success || ! $within_budget ) {
		++$failures;
	}
}

echo "Performance smoke\n";
foreach ( $rows as $row ) {
	echo $row . "\n";
}

if ( is_readable( $large_log_path ) ) {
	wp_delete_file( $large_log_path );
}

if ( $failures > 0 ) {
	fwrite( STDERR, "FAIL: {$failures} performance smoke target(s) exceeded budget or failed.\n" );
	exit( 1 );
}

echo "OK: performance smoke targets within budget\n";
