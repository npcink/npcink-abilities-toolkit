<?php
/**
 * Example media storage shim for object-storage-backed WordPress uploads.
 *
 * This file does not integrate with a real OSS plugin. It shows the only
 * contract a site-owned adapter should expose to Npcink Abilities Toolkit:
 * sanitized storage readiness metadata. Provider credentials, bucket names,
 * signed headers, SDK clients, upload calls, and cache purge calls stay in the
 * site-owned storage plugin or host adapter.
 *
 * @package NpcinkAbilitiesToolkitExample
 */

add_filter(
	'npcink_abilities_toolkit_media_storage_inspection',
	static function ( $storage, $attachment_id, $relative_file, $url, $attached_file ) {
		$storage = is_array( $storage ) ? $storage : array();

		if ( 'remote_object_storage' !== (string) ( $storage['provider'] ?? '' ) ) {
			return $storage;
		}

		/*
		 * Replace this placeholder with a site-owned readiness check, for
		 * example a small wrapper around the active offload plugin. Keep the
		 * real SDK/client and all secrets outside Toolkit.
		 */
		$adapter_ready = function_exists( 'acme_media_storage_adapter_ready' )
			&& acme_media_storage_adapter_ready( (int) $attachment_id, (string) $relative_file, (string) $url, (string) $attached_file );

		if ( ! $adapter_ready ) {
			$storage['adapter']        = 'acme_oss_shim';
			$storage['blocked_reason'] = 'remote_storage_write_requires_adapter';
			return $storage;
		}

		return array_merge(
			$storage,
			array(
				'provider'             => 'remote_object_storage',
				'adapter'              => 'acme_oss_shim',
				'source_read_mode'     => 'signed_url',
				'write_mode'           => 'provider_api',
				'restore_mode'         => 'provider_backup',
				'cache_purge_required' => true,
				'blocked_reason'       => '',
			)
		);
	},
	10,
	5
);
