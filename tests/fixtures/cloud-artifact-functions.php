<?php
/** Shared deterministic Cloud transfer fixtures. */
function npcink_abilities_toolkit_cloud_artifact_fixture( array $overrides = array() ) {
	$artifact = array_merge(
		array(
			'artifact_id'        => 'art_ffffffffffffffffffffffffffffffff',
			'expires_at'         => gmdate( 'c', time() + 600 ),
			'mime_type'          => 'image/webp',
			'format'             => 'webp',
			'width'              => 1600,
			'height'             => 862,
			'filesize_bytes'     => 210000,
			'sha256'             => hash( 'sha256', 'cloud-artifact-fixture' ),
			'suggested_filename' => 'cloud-artifact.webp',
			'filename_basis'     => array(
				'owner'                          => 'wordpress_write_ability_final',
				'strategy'                       => 'format_checksum',
				'final_sanitize_unique_required' => true,
			),
			'processing_warnings' => array(),
		),
		$overrides
	);
	if ( ! array_key_exists( 'transform_facts', $overrides ) ) {
		$source_filesize = 260000;
		$output_filesize = (int) $artifact['filesize_bytes'];
		$artifact['transform_facts'] = array(
			'source_checksum' => 'sha256:' . hash( 'sha256', 'source-fixture' ),
			'output_checksum' => 'sha256:' . (string) $artifact['sha256'],
			'source_format' => 'jpeg',
			'output_format' => (string) $artifact['format'],
			'source_mime_type' => 'image/jpeg',
			'output_mime_type' => (string) $artifact['mime_type'],
			'source_width' => 1600,
			'source_height' => 862,
			'output_width' => (int) $artifact['width'],
			'output_height' => (int) $artifact['height'],
			'source_filesize_bytes' => $source_filesize,
			'output_filesize_bytes' => $output_filesize,
			'source_frame_count' => 1,
			'output_frame_count' => 1,
			'source_has_alpha' => false,
			'output_has_alpha' => false,
			'alpha_preserved' => true,
			'decodable' => true,
			'crop_applied' => false,
			'watermark_applied' => false,
			'resize_applied' => 1600 !== (int) $artifact['width'] || 862 !== (int) $artifact['height'],
			'encoding_mode' => 'png' === $artifact['format'] ? 'lossless' : 'lossy',
			'savings_basis_points' => max( 0, (int) ( ( $source_filesize - $output_filesize ) * 10000 / $source_filesize ) ),
			'optimization_profile' => 'auto_safe.v1',
			'source_class' => 'opaque',
			'effective_quality' => 82,
			'quality_metric' => 'ssim',
			'quality_score' => 0.99,
			'quality_threshold' => 0.985,
			'color_profile_normalized' => false,
			'qualified' => true,
			'decision_reasons' => array( 'qualified' ),
		);
	}

	return $artifact;
}

function npcink_abilities_toolkit_cloud_receive_fixture( array $artifact, $contents, array $overrides = array() ) {
	$contents = (string) $contents;
	$artifact_expiry = \Npcink_Abilities_Toolkit\Support\Cloud_Derivative_Artifact::expiry_timestamp( (string) ( $artifact['expires_at'] ?? '' ) );
	$received_expiry = gmdate( 'c', $artifact_expiry );
	$ack_deadline = gmdate( 'c', min( time() + 900, $artifact_expiry - 1 ) );
	$sha256 = hash( 'sha256', $contents );
	$delivery_id = 'mdl_eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee';
	$checksum = 'sha256:' . $sha256;

	return array_replace_recursive(
		array(
			'artifact_id'    => (string) ( $artifact['artifact_id'] ?? '' ),
			'contents'       => $contents,
			'mime_type'      => (string) ( $artifact['mime_type'] ?? '' ),
			'width'          => (int) ( $artifact['width'] ?? 0 ),
			'height'         => (int) ( $artifact['height'] ?? 0 ),
			'filesize_bytes' => (int) ( $artifact['filesize_bytes'] ?? 0 ),
			'sha256'         => (string) ( $artifact['sha256'] ?? '' ),
			'expires_at'     => $received_expiry,
			'transfer_evidence' => array(
				'contract_version'      => 'media_artifact_verified_transfer.v1',
				'artifact_id'           => (string) ( $artifact['artifact_id'] ?? '' ),
				'delivery_id'           => $delivery_id,
				'received_byte_size'    => strlen( $contents ),
				'received_checksum'     => $checksum,
				'byte_size_verified'    => true,
				'checksum_verified'     => true,
				'content_type_verified' => true,
				'image_decoded'         => true,
				'dimensions_verified'   => true,
				'ack_deadline_at'       => $ack_deadline,
			),
			'delivery_ack' => array(
				'contract_version'      => 'media_artifact_delivery_ack.v1',
				'delivery_id'           => $delivery_id,
				'artifact_id'           => (string) ( $artifact['artifact_id'] ?? '' ),
				'status'                => 'acknowledged',
				'received_byte_size'    => strlen( $contents ),
				'received_checksum'     => $checksum,
				'byte_size_verified'    => true,
				'checksum_verified'     => true,
				'acknowledged_at'       => gmdate( 'c', time() - 360 ),
				'artifact_expires_at'   => $received_expiry,
				'idempotent_replay'     => false,
				'acknowledgement_scope' => 'verified_transfer_only',
			),
		),
		$overrides
	);
}

