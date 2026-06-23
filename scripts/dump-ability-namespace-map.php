<?php
/**
 * Dumps legacy Magick AI to Npcink ability namespace migration candidates.
 *
 * This script is intentionally read-only. It compares legacy `magick-ai/*`
 * references with the current normalized Toolkit ability catalog and emits only
 * exact same-slug aliases. Non-matching legacy ids require an explicit ADR or
 * migration-map row before code changes.
 *
 * @package NpcinkAbilitiesToolkit
 */

require_once dirname( __DIR__ ) . '/tests/bootstrap.php';

$legacy_root = getenv( 'MAGICK_AI_LEGACY_ROOT' );
$legacy_root = is_string( $legacy_root ) && '' !== $legacy_root
	? $legacy_root
	: '/Users/muze/gitee/magick-ai-root/magick-ai';

$plugin = Npcink_Abilities_Toolkit\Plugin::instance();
$plugin->boot();

$current_ids = array_keys( $plugin->abilities()->all() );
sort( $current_ids, SORT_STRING );

$current_slugs = array();
foreach ( $current_ids as $ability_id ) {
	if ( 0 === strpos( $ability_id, 'npcink-abilities-toolkit/' ) ) {
		$current_slugs[ substr( $ability_id, strlen( 'npcink-abilities-toolkit/' ) ) ] = $ability_id;
	}
}

$legacy_ids = array();
if ( is_dir( $legacy_root ) ) {
	$iterator = new RecursiveIteratorIterator(
		new RecursiveCallbackFilterIterator(
			new RecursiveDirectoryIterator( $legacy_root, FilesystemIterator::SKIP_DOTS ),
			static function ( SplFileInfo $file ) {
				$name = $file->getFilename();
				if ( $file->isDir() && in_array( $name, array( '.git', 'vendor', 'node_modules', 'build', 'dist', 'coverage', 'test-results' ), true ) ) {
					return false;
				}

				return true;
			}
		)
	);

	foreach ( $iterator as $file ) {
		if ( ! $file instanceof SplFileInfo || ! $file->isFile() ) {
			continue;
		}

		$extension = strtolower( $file->getExtension() );
		if ( ! in_array( $extension, array( 'php', 'md', 'json', 'js', 'ts', 'tsx', 'txt', 'yml', 'yaml' ), true ) ) {
			continue;
		}

		$contents = file_get_contents( $file->getPathname() );
		if ( ! is_string( $contents ) ) {
			continue;
		}

		if ( preg_match_all( '#magick-ai/[a-z0-9][a-z0-9-]*#', $contents, $matches ) ) {
			foreach ( $matches[0] as $legacy_id ) {
				$legacy_ids[ $legacy_id ] = true;
			}
		}
	}
}

$legacy_ids = array_keys( $legacy_ids );
sort( $legacy_ids, SORT_STRING );

$direct_aliases = array();
$legacy_unmapped = array();
foreach ( $legacy_ids as $legacy_id ) {
	$slug = substr( $legacy_id, strlen( 'magick-ai/' ) );
	if ( isset( $current_slugs[ $slug ] ) ) {
		$direct_aliases[] = array(
			'legacy_ability_id'   => $legacy_id,
			'canonical_ability_id' => $current_slugs[ $slug ],
			'migration_action'    => 'direct_same_slug_alias',
		);
		continue;
	}

	$legacy_unmapped[] = $legacy_id;
}

echo wp_json_encode(
	array(
		'legacy_root'           => $legacy_root,
		'current_catalog_count' => count( $current_ids ),
		'direct_alias_count'    => count( $direct_aliases ),
		'direct_aliases'        => $direct_aliases,
		'legacy_unmapped'       => $legacy_unmapped,
	),
	JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
) . "\n";
