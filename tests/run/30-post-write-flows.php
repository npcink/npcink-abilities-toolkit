<?php
/**
 * Ordered regression suite part: 30-post-write-flows.
 *
 * Included by tests/run.php; parts must run in the original execution order.
 *
 * @package NpcinkAbilitiesToolkit
 */

use Npcink_Abilities_Toolkit\Integration\Npcink_Catalog_Bridge;
use Npcink_Abilities_Toolkit\Packages\Core_Comment_Pack_Classifier;
use Npcink_Abilities_Toolkit\Packages\Core_Comment_Package;
use Npcink_Abilities_Toolkit\Packages\Core_Destructive_Package;
use Npcink_Abilities_Toolkit\Packages\Core_Read_Pack_Classifier;
use Npcink_Abilities_Toolkit\Packages\Core_Read_Package;
use Npcink_Abilities_Toolkit\Packages\Core_Write_Package;
use Npcink_Abilities_Toolkit\Packages\Read_Definitions\Media_Governance_Read_Definitions;
use Npcink_Abilities_Toolkit\Plugin;
use Npcink_Abilities_Toolkit\Registry\Ability_Registrar;
use Npcink_Abilities_Toolkit\Registry\Annotation_Normalizer;
use Npcink_Abilities_Toolkit\Registry\Category_Registrar;
use Npcink_Abilities_Toolkit\Registry\Contract_Normalizer;
use Npcink_Abilities_Toolkit\Registry\Schema_Normalizer;
use Npcink_Abilities_Toolkit\Rest\Contract_Controller;
use Npcink_Abilities_Toolkit\Security\Permission_Callbacks;
use Npcink_Abilities_Toolkit\Support\Gutenberg_Block_Document;

add_filter(
	'npcink_abilities_toolkit_enabled_comment_packs',
	static function () {
		return array( 'comment_queue_context' );
	}
);
$filtered_comment_categories = new Category_Registrar();
$filtered_comment_registrar = new Ability_Registrar( $filtered_comment_categories, $contract_normalizer );
$filtered_comment_package = new Core_Comment_Package( $filtered_comment_categories, $filtered_comment_registrar );
$filtered_comment_package->boot();
$filtered_comment_abilities = $filtered_comment_registrar->all();
npcink_abilities_toolkit_assert_true( isset( $filtered_comment_abilities['npcink-abilities-toolkit/get-comment-queue-health'] ), 'comment pack filter keeps queue helper ability' );
npcink_abilities_toolkit_assert_true( ! isset( $filtered_comment_abilities['npcink-abilities-toolkit/get-comment-compliance-handoff'] ), 'comment pack filter removes handoff helper ability' );
remove_all_filters( 'npcink_abilities_toolkit_enabled_comment_packs' );
foreach ( $migrated_write_ability_ids as $migrated_write_ability_id ) {
	npcink_abilities_toolkit_assert_true( isset( $package_abilities[ $migrated_write_ability_id ] ), "core write package owns migrated {$migrated_write_ability_id} ability" );
	npcink_abilities_toolkit_assert_package_write_ability_contract( $migrated_write_ability_id, $package_abilities[ $migrated_write_ability_id ] );
}
foreach ( $priority_write_implementation_posture_ids as $posture_ability_id ) {
	npcink_abilities_toolkit_assert_true( isset( $package_abilities[ $posture_ability_id ] ), "core write package owns priority implementation posture ability {$posture_ability_id}" );
	npcink_abilities_toolkit_assert_write_like_implementation_posture( $posture_ability_id, $package_abilities[ $posture_ability_id ] );
}
foreach ( $migrated_destructive_ability_ids as $migrated_destructive_ability_id ) {
	npcink_abilities_toolkit_assert_true( isset( $package_abilities[ $migrated_destructive_ability_id ] ), "core destructive package owns migrated {$migrated_destructive_ability_id} ability" );
	npcink_abilities_toolkit_assert_package_destructive_ability_contract( $migrated_destructive_ability_id, $package_abilities[ $migrated_destructive_ability_id ] );
}
npcink_abilities_toolkit_assert_same(
	array( 'taxonomy', 'name' ),
	$package_abilities['npcink-abilities-toolkit/create-term']['input_schema']['required'] ?? array(),
	'create-term preserves migrated required taxonomy/name schema'
);
npcink_abilities_toolkit_assert_same(
	array( 'taxonomy', 'term_id' ),
	$package_abilities['npcink-abilities-toolkit/update-term']['input_schema']['required'] ?? array(),
	'update-term preserves migrated required taxonomy/term_id schema'
);
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'] = array(
	501 => (object) array(
		'ID' => 501,
		'post_type' => 'post',
		'post_status' => 'draft',
		'post_title' => 'Original title',
		'post_content' => '<p>Original body marker.</p>',
		'post_excerpt' => '',
			'post_author' => 7,
			'post_name' => 'original-title',
			'post_parent' => 0,
		),
	601 => (object) array(
		'ID' => 601,
		'post_type' => 'wp_template',
		'post_status' => 'publish',
		'post_title' => 'Single',
		'post_content' => '<!-- wp:template-part {"slug":"header"} /--><!-- wp:group {"tagName":"main"} --><main class="wp-block-group"><!-- wp:post-title /--><!-- wp:post-content /--></main><!-- /wp:group --><!-- wp:template-part {"slug":"footer"} /-->',
		'post_excerpt' => '',
		'post_author' => 7,
		'post_name' => 'single',
		'post_parent' => 0,
	),
	602 => (object) array(
		'ID' => 602,
		'post_type' => 'wp_template',
		'post_status' => 'publish',
		'post_title' => 'Page',
		'post_content' => '<!-- wp:template-part {"slug":"header"} /--><!-- wp:group {"tagName":"main"} --><main class="wp-block-group"><!-- wp:post-title /--><!-- wp:post-content /--></main><!-- /wp:group --><!-- wp:template-part {"slug":"footer"} /-->',
		'post_excerpt' => '',
		'post_author' => 7,
		'post_name' => 'page',
		'post_parent' => 0,
	),
	604 => (object) array(
		'ID' => 604,
		'post_type' => 'wp_template',
		'post_status' => 'publish',
		'post_title' => 'Front Page',
		'post_content' => '<!-- wp:group {"className":"openclaw-breadcrumbs"} --><div class="wp-block-group openclaw-breadcrumbs"><!-- wp:paragraph {"className":"openclaw-breadcrumbs__trail"} --><p class="openclaw-breadcrumbs__trail">Home / Current item</p><!-- /wp:paragraph --></div><!-- /wp:group --><!-- wp:template-part {"slug":"header"} /--><!-- wp:group {"tagName":"main"} --><main class="wp-block-group"><!-- wp:post-title /--><!-- wp:post-content /--></main><!-- /wp:group -->',
		'post_excerpt' => '',
		'post_author' => 7,
		'post_name' => 'front-page',
		'post_parent' => 0,
	),
	603 => (object) array(
		'ID' => 603,
		'post_type' => 'wp_template_part',
		'post_status' => 'publish',
		'post_title' => 'Header',
		'post_content' => '<!-- wp:group --><div class="wp-block-group"><!-- wp:site-title /--></div><!-- /wp:group -->',
		'post_excerpt' => '',
		'post_author' => 7,
		'post_name' => 'header',
		'post_parent' => 0,
	),
	);
	$GLOBALS['npcink_abilities_toolkit_unit_is_block_theme'] = true;
	$GLOBALS['npcink_abilities_toolkit_unit_active_theme'] = array(
		'name'       => 'Unit Block Theme',
		'stylesheet' => 'unit-block-theme',
	);
npcink_abilities_toolkit_assert_same( 262144, $package_abilities['npcink-abilities-toolkit/create-draft']['input_schema']['properties']['content']['maxLength'] ?? null, 'create-draft bounds content input size' );
npcink_abilities_toolkit_assert_same( 262144, $package_abilities['npcink-abilities-toolkit/update-post']['input_schema']['properties']['content']['maxLength'] ?? null, 'update-post bounds content input size' );
npcink_abilities_toolkit_assert_same( 20, $package_abilities['npcink-abilities-toolkit/patch-post-content']['input_schema']['properties']['operations']['maxItems'] ?? null, 'patch-post-content bounds operation count' );
npcink_abilities_toolkit_assert_same( 262144, $package_abilities['npcink-abilities-toolkit/patch-post-content']['input_schema']['properties']['operations']['items']['properties']['replace']['maxLength'] ?? null, 'patch-post-content bounds replacement size' );
npcink_abilities_toolkit_assert_same( 200, $package_abilities['npcink-abilities-toolkit/update-post-blocks']['input_schema']['properties']['blocks']['maxItems'] ?? null, 'update-post-blocks bounds submitted block count' );
$create_preview = $core_write_package->create_draft(
	array(
		'title' => 'Preview title',
		'content' => 'Preview body',
		'dry_run' => true,
	)
);
npcink_abilities_toolkit_assert_same( true, $create_preview['dry_run'] ?? null, 'create-draft defaults to governed dry-run preview when requested' );
npcink_abilities_toolkit_assert_same( 'create_draft', $create_preview['preview']['action'] ?? '', 'create-draft dry-run reports preview action' );
$oversized_create_preview = $core_write_package->create_draft(
	array(
		'title'   => 'Oversized preview',
		'content' => str_repeat( 'x', 262145 ),
		'dry_run' => true,
	)
);
npcink_abilities_toolkit_assert_true( is_wp_error( $oversized_create_preview ), 'create-draft rejects oversized content before preview generation' );
npcink_abilities_toolkit_assert_same( 'npcink_abilities_toolkit_input_too_large', $oversized_create_preview->code ?? '', 'create-draft oversized content fails with stable code' );

	$GLOBALS['npcink_ai_runtime_wp_ability_context'] = array( 'context' => array( 'approval_commit_authorized' => true ) );
	$posts_before_non_commit_write = count( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'] );
	$non_commit_create = $core_write_package->create_draft(
		array(
			'title'   => 'Must Stay Preview',
			'content' => 'Explicitly disabling dry run cannot replace commit intent.',
			'dry_run' => false,
			'commit'  => false,
		)
	);
	npcink_abilities_toolkit_assert_same( true, $non_commit_create['dry_run'] ?? null, 'create-draft keeps dry-run when commit is explicitly false' );
	npcink_abilities_toolkit_assert_same( $posts_before_non_commit_write, count( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'] ), 'create-draft does not mutate when commit is explicitly false' );
	$conflicting_write_controls = $core_write_package->create_draft(
		array(
			'title'   => 'Conflicting Controls',
			'content' => 'Dry-run wins when write controls conflict.',
			'dry_run' => true,
			'commit'  => true,
		)
	);
	npcink_abilities_toolkit_assert_same( true, $conflicting_write_controls['dry_run'] ?? null, 'create-draft keeps dry-run when commit conflicts with dry_run=true' );
	npcink_abilities_toolkit_assert_same( $posts_before_non_commit_write, count( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'] ), 'create-draft does not mutate when write controls conflict' );
	$destructive_status_before_non_commit = (string) ( get_post( 501 )->post_status ?? '' );
	$non_commit_trash = $core_destructive_package->trash_post(
		array(
			'post_id' => 501,
			'dry_run' => false,
			'commit'  => false,
		)
	);
	npcink_abilities_toolkit_assert_same( true, $non_commit_trash['dry_run'] ?? null, 'destructive package keeps dry-run when commit is explicitly false' );
	npcink_abilities_toolkit_assert_same( $destructive_status_before_non_commit, (string) ( get_post( 501 )->post_status ?? '' ), 'destructive package does not mutate when commit is explicitly false' );
	$created = $core_write_package->create_draft(
	array(
		'title' => 'Migrated Draft',
		'content' => "# Migrated Draft\n\n![Alt](https://example.test/image.jpg)\n\nBody text.",
		'content_format' => 'markdown',
		'commit' => true,
		'meta' => array( 'source' => 'unit' ),
	)
);
unset( $GLOBALS['npcink_ai_runtime_wp_ability_context'] );
npcink_abilities_toolkit_assert_same( false, $created['dry_run'] ?? null, 'create-draft commit returns a committed payload' );
npcink_abilities_toolkit_assert_same( 'markdown', $created['content_format'] ?? '', 'create-draft preserves migrated markdown content_format reporting' );
$created_post = get_post( (int) ( $created['post_id'] ?? 0 ) );
npcink_abilities_toolkit_assert_true( is_object( $created_post ), 'create-draft commit creates a draft post in the standalone package' );
npcink_abilities_toolkit_assert_true( false === strpos( (string) ( $created_post->post_content ?? '' ), '<h1>Migrated Draft</h1>' ), 'create-draft strips a duplicate leading title heading after migration' );
npcink_abilities_toolkit_assert_true( false !== strpos( (string) ( $created_post->post_content ?? '' ), '<img src="https://example.test/image.jpg" alt="Alt" />' ), 'create-draft converts markdown image syntax after migration' );

$update_preview = $core_write_package->update_post(
	array(
		'post_id' => 501,
		'content' => "## Updated heading\n\nUpdated body.",
		'dry_run' => true,
	)
);
npcink_abilities_toolkit_assert_same( true, $update_preview['dry_run'] ?? null, 'update-post returns a governed dry-run preview after migration' );
npcink_abilities_toolkit_assert_same( 'markdown', $update_preview['changes']['content']['content_format'] ?? '', 'update-post auto-detects markdown content after migration' );

$GLOBALS['npcink_abilities_toolkit_unit_post_meta'][501]['_yoast_wpseo_title'] = 'Old SEO title';
$seo_preview = $core_write_package->set_post_seo_meta(
	array(
		'post_id' => 501,
		'seo_title' => 'New SEO title',
		'seo_description' => 'New SEO description',
		'dry_run' => true,
	)
);
npcink_abilities_toolkit_assert_same( true, $seo_preview['dry_run'] ?? null, 'set-post-seo-meta returns a governed dry-run preview after migration' );
npcink_abilities_toolkit_assert_same( 'yoast', $seo_preview['provider'] ?? '', 'set-post-seo-meta detects existing Yoast-style SEO metadata after migration' );
$seo_missing_fields = $core_write_package->set_post_seo_meta(
	array(
		'post_id' => 501,
		'dry_run' => true,
	)
);
npcink_abilities_toolkit_assert_true( is_wp_error( $seo_missing_fields ), 'set-post-seo-meta rejects requests without explicit metadata fields' );
npcink_abilities_toolkit_assert_same( 'npcink_abilities_toolkit_no_changes', $seo_missing_fields->code ?? '', 'set-post-seo-meta no-change request fails with a stable code' );
$seo_title_only_preview = $core_write_package->set_post_seo_meta(
	array(
		'post_id'   => 501,
		'seo_title' => 'Title-only preview',
		'dry_run'   => true,
	)
);
npcink_abilities_toolkit_assert_same( array( 'seo_title' ), $seo_title_only_preview['preview']['changed_fields'] ?? array(), 'set-post-seo-meta preview reports only explicit changed fields' );
$GLOBALS['npcink_ai_runtime_wp_ability_context'] = array( 'context' => array( 'approval_commit_authorized' => true ) );
$seo_written = $core_write_package->set_post_seo_meta(
	array(
		'post_id' => 501,
		'seo_title' => 'Committed SEO title',
		'seo_description' => 'Committed SEO description',
		'commit' => true,
	)
);
unset( $GLOBALS['npcink_ai_runtime_wp_ability_context'] );
npcink_abilities_toolkit_assert_same( false, $seo_written['dry_run'] ?? null, 'set-post-seo-meta commit returns a committed payload after migration' );
npcink_abilities_toolkit_assert_same( 'Committed SEO title', $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][501]['_yoast_wpseo_title'] ?? '', 'set-post-seo-meta writes SEO title through standalone fallback metadata keys' );
npcink_abilities_toolkit_assert_same( 'Committed SEO description', $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][501]['_yoast_wpseo_metadesc'] ?? '', 'set-post-seo-meta writes SEO description through standalone fallback metadata keys' );
$GLOBALS['npcink_ai_runtime_wp_ability_context'] = array( 'context' => array( 'approval_commit_authorized' => true ) );
$seo_title_only_written = $core_write_package->set_post_seo_meta(
	array(
		'post_id'   => 501,
		'seo_title' => 'Only title changed',
		'commit'    => true,
	)
);
unset( $GLOBALS['npcink_ai_runtime_wp_ability_context'] );
npcink_abilities_toolkit_assert_same( false, $seo_title_only_written['dry_run'] ?? null, 'set-post-seo-meta title-only commit returns committed payload' );
npcink_abilities_toolkit_assert_same( 'Only title changed', $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][501]['_yoast_wpseo_title'] ?? '', 'set-post-seo-meta title-only commit writes title' );
npcink_abilities_toolkit_assert_same( 'Committed SEO description', $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][501]['_yoast_wpseo_metadesc'] ?? '', 'set-post-seo-meta title-only commit preserves description' );
$article_audio_preview = $core_write_package->adopt_article_audio(
	array(
		'post_id'             => 501,
		'audio_url'           => 'https://cloud.example.test/audio/article-narration.mp3',
		'audio_title'         => 'Reviewed narration',
		'audio_kind'          => 'article_narration',
		'duration_seconds'    => 185.25,
		'mime_type'           => 'audio/mpeg',
		'source_content_hash' => 'sha256:article-source',
		'source_word_count'   => 1234,
		'source_generated_at' => '2026-06-24T09:30:00Z',
		'provider'            => 'minimax',
		'model'               => 'speech-2.8-turbo',
		'trace_id'            => 'trace_audio_456',
		'dry_run'             => true,
	)
);
npcink_abilities_toolkit_assert_same( true, $article_audio_preview['dry_run'] ?? null, 'adopt-article-audio returns a governed dry-run preview' );
npcink_abilities_toolkit_assert_same( false, $article_audio_preview['updated'] ?? null, 'adopt-article-audio dry-run does not update metadata' );
npcink_abilities_toolkit_assert_same( true, $article_audio_preview['preview']['no_content_write'] ?? null, 'adopt-article-audio preview records that content is untouched' );
npcink_abilities_toolkit_assert_same( true, in_array( '_npcink_toolbox_article_audio_url', $article_audio_preview['audio_meta_keys'] ?? array(), true ), 'adopt-article-audio previews the bounded article audio URL meta key' );
$GLOBALS['npcink_ai_runtime_wp_ability_context'] = array( 'context' => array( 'approval_commit_authorized' => true ) );
$article_audio_written = $core_write_package->adopt_article_audio(
	array(
		'post_id'             => 501,
		'audio_url'           => 'https://cloud.example.test/audio/article-narration.mp3',
		'audio_title'         => 'Reviewed narration',
		'audio_kind'          => 'article_narration',
		'duration_seconds'    => 185.25,
		'mime_type'           => 'audio/mpeg',
		'source_content_hash' => 'sha256:article-source',
		'source_word_count'   => 1234,
		'source_generated_at' => '2026-06-24T09:30:00Z',
		'provider'            => 'minimax',
		'model'               => 'speech-2.8-turbo',
		'trace_id'            => 'trace_audio_456',
		'commit'              => true,
	)
);
unset( $GLOBALS['npcink_ai_runtime_wp_ability_context'] );
npcink_abilities_toolkit_assert_same( false, $article_audio_written['dry_run'] ?? null, 'adopt-article-audio commit returns a committed payload after approval' );
npcink_abilities_toolkit_assert_same( true, $article_audio_written['updated'] ?? null, 'adopt-article-audio commit reports metadata update' );
npcink_abilities_toolkit_assert_same( 'https://cloud.example.test/audio/article-narration.mp3', $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][501]['_npcink_toolbox_article_audio_url'] ?? '', 'adopt-article-audio writes the article audio URL meta' );
npcink_abilities_toolkit_assert_same( 'article_narration', $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][501]['_npcink_toolbox_article_audio_kind'] ?? '', 'adopt-article-audio writes the article audio kind meta' );
npcink_abilities_toolkit_assert_same( 'sha256:article-source', $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][501]['_npcink_toolbox_article_audio_source_content_hash'] ?? '', 'adopt-article-audio writes freshness source hash meta' );
npcink_abilities_toolkit_assert_same( 'minimax', $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][501]['_npcink_toolbox_article_audio_provider'] ?? '', 'adopt-article-audio writes provider evidence meta' );
$article_audio_upload_dir = sys_get_temp_dir() . '/npcink-abilities-toolkit-audio-import-' . getmypid();
$GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] = $article_audio_upload_dir;
$GLOBALS['npcink_abilities_toolkit_unit_upload_baseurl'] = 'https://origin.example.test/wp-content/uploads';
$GLOBALS['npcink_abilities_toolkit_unit_http_responses'] = array(
	'https://cloud.example.test/audio/signed-runtime-url' => array(
		'status'       => 200,
		'content_type' => 'audio/mpeg',
		'body'         => str_repeat( 'a', 128 ),
	),
);
$GLOBALS['npcink_ai_runtime_wp_ability_context'] = array( 'context' => array( 'approval_commit_authorized' => true ) );
	$article_audio_imported = $core_write_package->adopt_article_audio(
	array(
		'post_id'             => 501,
		'audio_url'           => 'https://cloud.example.test/audio/signed-runtime-url',
		'audio_title'         => 'Imported narration',
		'audio_kind'          => 'article_narration',
		'duration_seconds'    => 45,
		'mime_type'           => 'audio/mpeg',
		'source_content_hash' => 'sha256:article-source-import',
		'source_word_count'   => 456,
		'source_generated_at' => '2026-06-24T10:00:00Z',
		'provider'            => 'minimax',
		'model'               => 'speech-2.8-turbo',
		'trace_id'            => 'trace_audio_import',
		'import_media'        => true,
		'media_file_name'     => 'reviewed-narration',
		'commit'              => true,
	)
);
unset( $GLOBALS['npcink_ai_runtime_wp_ability_context'] );
unset( $GLOBALS['npcink_abilities_toolkit_unit_http_responses'] );
unset( $GLOBALS['npcink_abilities_toolkit_unit_upload_basedir'] );
unset( $GLOBALS['npcink_abilities_toolkit_unit_upload_baseurl'] );
npcink_abilities_toolkit_assert_true( ! is_wp_error( $article_audio_imported ), 'adopt-article-audio imports media after approval' . ( is_wp_error( $article_audio_imported ) ? ': ' . $article_audio_imported->get_error_code() : '' ) );
npcink_abilities_toolkit_assert_same( 'wordpress_media_library', $article_audio_imported['storage_mode'] ?? '', 'adopt-article-audio import reports WordPress media library storage' );
npcink_abilities_toolkit_assert_true( (int) ( $article_audio_imported['attachment_id'] ?? 0 ) > 0, 'adopt-article-audio import creates an attachment' );
npcink_abilities_toolkit_assert_true( false !== strpos( (string) ( $article_audio_imported['local_audio_url'] ?? '' ), 'reviewed-narration.mp3' ), 'adopt-article-audio import resolves a local audio URL with an audio extension' );
npcink_abilities_toolkit_assert_same( (string) ( $article_audio_imported['attachment_id'] ?? 0 ), (string) ( $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][501]['_npcink_toolbox_article_audio_attachment_id'] ?? '' ), 'adopt-article-audio import writes the local attachment id meta' );
npcink_abilities_toolkit_assert_same( $article_audio_imported['local_audio_url'] ?? '', $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][501]['_npcink_toolbox_article_audio_url'] ?? '', 'adopt-article-audio import switches playback URL to the local media URL' );
npcink_abilities_toolkit_assert_true( is_readable( $article_audio_upload_dir . '/reviewed-narration.mp3' ), 'adopt-article-audio import writes the audio file into uploads' );
$GLOBALS['npcink_abilities_toolkit_unit_comments'][11] = (object) array(
	'comment_ID'       => 11,
	'comment_post_ID'  => 77,
	'comment_author'   => 'Permission Fixture',
	'comment_approved' => 'hold',
	'comment_content'  => 'Pending moderation.',
);
$GLOBALS['npcink_abilities_toolkit_unit_current_user_caps'] = array( 'moderate_comments' => false );
$comment_permission_denied = $core_write_package->approve_comment(
	array(
		'comment_id' => 11,
		'dry_run'    => true,
	)
);
unset( $GLOBALS['npcink_abilities_toolkit_unit_current_user_caps'] );
npcink_abilities_toolkit_assert_true( is_wp_error( $comment_permission_denied ), 'approve-comment enforces moderate_comments before dry-run preview' );
npcink_abilities_toolkit_assert_same( 'npcink_abilities_toolkit_permission_denied', $comment_permission_denied->code ?? '', 'approve-comment permission denial has stable error code' );

$patch_preview = $core_write_package->patch_post_content(
	array(
		'post_id' => 501,
		'operations' => array(
			array(
				'op' => 'replace',
				'find' => 'Original body marker',
				'replace' => 'Patched body marker',
			),
		),
		'dry_run' => true,
	)
);
npcink_abilities_toolkit_assert_same( true, $patch_preview['dry_run'] ?? null, 'patch-post-content returns a governed dry-run preview after migration' );
npcink_abilities_toolkit_assert_same( 1, $patch_preview['patch_preview'][0]['applied'] ?? null, 'patch-post-content reports applied operation count after migration' );
$too_many_patch_operations = $core_write_package->patch_post_content(
	array(
		'post_id'    => 501,
		'operations' => array_fill(
			0,
			21,
			array(
				'op'      => 'replace',
				'find'    => 'Original body marker',
				'replace' => 'Patched body marker',
			)
		),
		'dry_run'    => true,
	)
);
npcink_abilities_toolkit_assert_true( is_wp_error( $too_many_patch_operations ), 'patch-post-content rejects oversized operation lists before diff generation' );
npcink_abilities_toolkit_assert_same( 'npcink_abilities_toolkit_patch_operations_too_many', $too_many_patch_operations->code ?? '', 'patch-post-content oversized operation list fails with stable code' );
$original_patch_post_content = $GLOBALS['npcink_abilities_toolkit_unit_style_posts'][501]->post_content ?? '';
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][501]->post_content = str_repeat( 'x', 262145 );
$oversized_existing_post_patch = $core_write_package->patch_post_content(
	array(
		'post_id'    => 501,
		'operations' => array(
			array(
				'op'      => 'replace',
				'find'    => 'x',
				'replace' => 'y',
			),
		),
		'dry_run'    => true,
	)
);
$GLOBALS['npcink_abilities_toolkit_unit_style_posts'][501]->post_content = $original_patch_post_content;
npcink_abilities_toolkit_assert_true( is_wp_error( $oversized_existing_post_patch ), 'patch-post-content rejects oversized existing content before patching' );
npcink_abilities_toolkit_assert_same( 'npcink_abilities_toolkit_input_too_large', $oversized_existing_post_patch->code ?? '', 'patch-post-content oversized existing content fails with stable code' );

$blocks_preview = $core_write_package->update_post_blocks(
	array(
		'post_id' => 501,
		'blocks' => array(
			array(
				'blockName' => 'core/paragraph',
				'innerHTML' => '<p>Block body.</p>',
			),
		),
		'dry_run' => true,
	)
);
npcink_abilities_toolkit_assert_same( true, $blocks_preview['dry_run'] ?? null, 'update-post-blocks returns a governed dry-run preview after migration' );
npcink_abilities_toolkit_assert_same( true, $blocks_preview['validation']['valid'] ?? null, 'update-post-blocks validates serialized blocks after migration' );
$too_many_blocks = $core_write_package->update_post_blocks(
	array(
		'post_id' => 501,
		'blocks'  => array_fill(
			0,
			201,
			array(
				'blockName' => 'core/paragraph',
				'innerHTML' => '<p>Block body.</p>',
			)
		),
		'dry_run' => true,
	)
);
npcink_abilities_toolkit_assert_true( is_wp_error( $too_many_blocks ), 'update-post-blocks rejects block trees over the bounded block count' );
npcink_abilities_toolkit_assert_same( 'npcink_abilities_toolkit_blocks_invalid', $too_many_blocks->code ?? '', 'update-post-blocks oversized block tree fails with stable code' );
npcink_abilities_toolkit_assert_same( 'block_count_exceeded', $too_many_blocks->data['errors'][0]['error'] ?? '', 'update-post-blocks reports block count overflow detail' );
$deep_block = array(
	'blockName'   => 'core/group',
	'innerHTML'   => '<div class="wp-block-group"></div>',
	'innerBlocks' => array(),
);
for ( $deep_index = 0; $deep_index < 9; ++$deep_index ) {
	$deep_block = array(
		'blockName'   => 'core/group',
		'innerHTML'   => '<div class="wp-block-group"></div>',
		'innerBlocks' => array( $deep_block ),
	);
}
$too_deep_blocks = $core_write_package->update_post_blocks(
	array(
		'post_id' => 501,
		'blocks'  => array( $deep_block ),
		'dry_run' => true,
	)
);
npcink_abilities_toolkit_assert_true( is_wp_error( $too_deep_blocks ), 'update-post-blocks rejects excessively deep block trees' );
npcink_abilities_toolkit_assert_same( 'npcink_abilities_toolkit_blocks_invalid', $too_deep_blocks->code ?? '', 'update-post-blocks deep block tree fails with stable code' );


$GLOBALS['npcink_abilities_toolkit_unit_terms_map'] = array(
	301 => (object) array( 'term_id' => 301, 'name' => 'Merge Target', 'taxonomy' => 'category' ),
	302 => (object) array( 'term_id' => 302, 'name' => 'Merge Source', 'taxonomy' => 'category' ),
);
$GLOBALS['npcink_abilities_toolkit_unit_term_objects'] = array( 302 => array( 77, 78 ) );
$GLOBALS['npcink_abilities_toolkit_unit_set_object_terms'] = array();
$GLOBALS['npcink_abilities_toolkit_unit_deleted_terms'] = array();
$GLOBALS['npcink_ai_runtime_wp_ability_context']['context'] = array(
	'approval_commit_authorized' => true,
	'approval_id'                => 'approval-merge-terms',
);
$merge_result = $core_destructive_package->merge_terms(
	array(
		'taxonomy'        => 'category',
		'target_term_id'  => 301,
		'source_term_ids' => array( 302 ),
		'commit'          => true,
	)
);
unset( $GLOBALS['npcink_ai_runtime_wp_ability_context'] );
npcink_abilities_toolkit_assert_true( is_array( $merge_result ), 'merge-terms commits an authorized merge over editable objects' );
npcink_abilities_toolkit_assert_same( array( 302 ), $merge_result['removed_term_ids'] ?? null, 'merge-terms removes the fully merged source term' );
npcink_abilities_toolkit_assert_same( 2, count( $GLOBALS['npcink_abilities_toolkit_unit_set_object_terms'] ?? array() ), 'merge-terms reassigns every object of the merged source term' );
npcink_abilities_toolkit_assert_same( array( 302 ), $GLOBALS['npcink_abilities_toolkit_unit_deleted_terms'] ?? null, 'merge-terms deletes the merged source term' );

$GLOBALS['npcink_abilities_toolkit_unit_current_user_caps'] = array( 'edit_post' => false );
$GLOBALS['npcink_abilities_toolkit_unit_set_object_terms'] = array();
$GLOBALS['npcink_abilities_toolkit_unit_deleted_terms'] = array();
$GLOBALS['npcink_ai_runtime_wp_ability_context']['context'] = array(
	'approval_commit_authorized' => true,
	'approval_id'                => 'approval-merge-terms-blocked',
);
$blocked_merge = $core_destructive_package->merge_terms(
	array(
		'taxonomy'        => 'category',
		'target_term_id'  => 301,
		'source_term_ids' => array( 302 ),
		'commit'          => true,
	)
);
unset( $GLOBALS['npcink_abilities_toolkit_unit_current_user_caps'], $GLOBALS['npcink_ai_runtime_wp_ability_context'] );
npcink_abilities_toolkit_assert_true( is_array( $blocked_merge ), 'merge-terms still returns a payload when its objects are not editable' );
npcink_abilities_toolkit_assert_same( array(), $blocked_merge['removed_term_ids'] ?? null, 'merge-terms keeps the source term when the caller cannot edit its objects' );
npcink_abilities_toolkit_assert_same( array(), $GLOBALS['npcink_abilities_toolkit_unit_set_object_terms'] ?? null, 'merge-terms reassigns no object the caller cannot edit' );
npcink_abilities_toolkit_assert_same( array(), $GLOBALS['npcink_abilities_toolkit_unit_deleted_terms'] ?? null, 'merge-terms never deletes a source term blocked by object permissions' );
unset( $GLOBALS['npcink_abilities_toolkit_unit_terms_map'], $GLOBALS['npcink_abilities_toolkit_unit_term_objects'], $GLOBALS['npcink_abilities_toolkit_unit_set_object_terms'], $GLOBALS['npcink_abilities_toolkit_unit_deleted_terms'] );
