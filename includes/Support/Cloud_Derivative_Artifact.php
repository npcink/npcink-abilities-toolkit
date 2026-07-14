<?php
/**
 * Shared Cloud media derivative artifact contract.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalizes exact, reviewable evidence for one short-lived media artifact.
 */
final class Cloud_Derivative_Artifact {
	public const MAX_FILESIZE_BYTES = 26214400;
	public const MAX_DIMENSION       = 32768;

	/**
	 * Returns the public ability input schema for a derivative artifact.
	 *
	 * @return array<string,mixed>
	 */
	public static function schema() {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'artifact_id'        => array( 'type' => 'string', 'minLength' => 1, 'maxLength' => 191 ),
				'run_id'             => array( 'type' => 'string', 'maxLength' => 191 ),
				'expires_at'         => array( 'type' => 'string', 'format' => 'date-time' ),
				'mime_type'          => array( 'type' => 'string', 'enum' => array( 'image/webp', 'image/avif', 'image/jpeg', 'image/png' ) ),
				'format'             => array( 'type' => 'string', 'enum' => array( 'webp', 'avif', 'jpeg', 'jpg', 'png' ) ),
				'width'              => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => self::MAX_DIMENSION ),
				'height'             => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => self::MAX_DIMENSION ),
				'filesize_bytes'     => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => self::MAX_FILESIZE_BYTES ),
				'sha256'             => array( 'type' => 'string', 'pattern' => '^[a-fA-F0-9]{64}$' ),
				'checksum'           => array( 'type' => 'string', 'pattern' => '^(?:sha256:)?[a-fA-F0-9]{64}$' ),
				'download_url'       => array( 'type' => 'string' ),
				'suggested_filename' => array( 'type' => 'string', 'maxLength' => 120 ),
				'filename_basis'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'owner'                          => array( 'type' => 'string', 'maxLength' => 80 ),
						'strategy'                       => array( 'type' => 'string', 'maxLength' => 80 ),
						'final_sanitize_unique_required' => array( 'type' => 'boolean' ),
					),
					'additionalProperties' => false,
				),
				'processing_warnings' => array(
					'type'     => 'array',
					'maxItems' => 20,
					'items'    => array( 'type' => 'string', 'maxLength' => 200 ),
				),
			),
			'required'             => array( 'artifact_id', 'expires_at', 'mime_type', 'width', 'height', 'filesize_bytes', 'sha256' ),
			'additionalProperties' => false,
		);
	}

	/**
	 * Validates and normalizes one derivative artifact descriptor.
	 *
	 * The direct PHP seam accepts either sha256 or the legacy sha256-prefixed
	 * checksum field, then always emits both canonical forms. REST callers use
	 * the stricter public schema and must provide sha256 explicitly.
	 *
	 * @param mixed $artifact Raw artifact descriptor.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function normalize( $artifact ) {
		$artifact = is_array( $artifact ) ? $artifact : array();
		$artifact_id = sanitize_text_field( (string) ( $artifact['artifact_id'] ?? $artifact['id'] ?? '' ) );
		if ( '' === $artifact_id || strlen( $artifact_id ) > 191 ) {
			return self::error( 'id_required', __( 'A bounded derivative artifact_id is required.', 'npcink-abilities-toolkit' ) );
		}

		$expires_at = sanitize_text_field( (string) ( $artifact['expires_at'] ?? '' ) );
		$expires_ts = self::expiry_timestamp( $expires_at );
		if ( $expires_ts <= 0 ) {
			return self::error( 'expiry_required', __( 'A valid derivative artifact expiry is required.', 'npcink-abilities-toolkit' ) );
		}
		if ( $expires_ts <= time() ) {
			return self::error( 'expired', __( 'The derivative artifact has expired. Generate a new preview before adoption.', 'npcink-abilities-toolkit' ), 410 );
		}

		$mime_type = strtolower( sanitize_text_field( (string) ( $artifact['mime_type'] ?? '' ) ) );
		$format_by_mime = array(
			'image/webp' => 'webp',
			'image/avif' => 'avif',
			'image/jpeg' => 'jpeg',
			'image/png'  => 'png',
		);
		if ( ! isset( $format_by_mime[ $mime_type ] ) ) {
			return self::error( 'mime_invalid', __( 'The derivative artifact MIME type is not supported for media replacement.', 'npcink-abilities-toolkit' ) );
		}

		$format = strtolower( sanitize_key( (string) ( $artifact['format'] ?? '' ) ) );
		$format = 'jpg' === $format ? 'jpeg' : $format;
		if ( '' === $format || 'original' === $format ) {
			$format = $format_by_mime[ $mime_type ];
		}
		if ( $format !== $format_by_mime[ $mime_type ] ) {
			return self::error( 'format_mismatch', __( 'The derivative artifact format does not match its MIME type.', 'npcink-abilities-toolkit' ) );
		}

		$width  = is_int( $artifact['width'] ?? null ) ? $artifact['width'] : 0;
		$height = is_int( $artifact['height'] ?? null ) ? $artifact['height'] : 0;
		if ( $width <= 0 || $height <= 0 || $width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION ) {
			return self::error( 'dimensions_invalid', __( 'Derivative artifact dimensions must be present and within the local limit.', 'npcink-abilities-toolkit' ) );
		}

		$raw_filesize = $artifact['filesize_bytes'] ?? $artifact['size_bytes'] ?? null;
		$filesize_bytes = is_int( $raw_filesize ) ? $raw_filesize : 0;
		if ( $filesize_bytes <= 0 || $filesize_bytes > self::MAX_FILESIZE_BYTES ) {
			return self::error( 'filesize_invalid', __( 'Derivative artifact size must be present and within the local limit.', 'npcink-abilities-toolkit' ) );
		}

		$raw_sha256 = (string) ( $artifact['sha256'] ?? '' );
		$raw_checksum = (string) ( $artifact['checksum'] ?? '' );
		$sha256 = self::normalize_sha256( '' !== trim( $raw_sha256 ) ? $raw_sha256 : $raw_checksum );
		if ( '' === $sha256 ) {
			return self::error( 'sha256_required', __( 'A valid derivative artifact SHA-256 digest is required.', 'npcink-abilities-toolkit' ) );
		}
		$checksum_sha256 = self::normalize_sha256( $raw_checksum );
		if ( '' !== trim( $raw_checksum ) && '' === $checksum_sha256 ) {
			return self::error( 'checksum_invalid', __( 'The derivative artifact checksum is invalid.', 'npcink-abilities-toolkit' ) );
		}
		if ( '' !== $checksum_sha256 && $checksum_sha256 !== $sha256 ) {
			return self::error( 'checksum_conflict', __( 'The derivative artifact checksum fields do not match.', 'npcink-abilities-toolkit' ), 409 );
		}

		$normalized = array(
			'artifact_id'     => $artifact_id,
			'expires_at'      => gmdate( 'c', (int) $expires_ts ),
			'mime_type'       => $mime_type,
			'format'          => $format,
			'width'           => $width,
			'height'          => $height,
			'filesize_bytes'  => $filesize_bytes,
			'checksum'        => 'sha256:' . $sha256,
			'sha256'          => $sha256,
		);
		foreach ( array( 'run_id', 'download_url' ) as $field ) {
			$value = sanitize_text_field( (string) ( $artifact[ $field ] ?? '' ) );
			if ( '' !== $value ) {
				$normalized[ $field ] = substr( $value, 0, 191 );
			}
		}
		$suggested_filename = self::sanitize_file_name( (string) ( $artifact['suggested_filename'] ?? '' ) );
		if ( '' !== $suggested_filename ) {
			$normalized['suggested_filename'] = substr( $suggested_filename, 0, 120 );
		}
		$normalized['processing_warnings'] = array_slice(
			array_values(
				array_filter(
					array_map(
						static function ( $warning ) {
							$warning = sanitize_text_field( (string) $warning );

							return function_exists( 'mb_substr' ) ? mb_substr( $warning, 0, 200 ) : substr( $warning, 0, 200 );
						},
						(array) ( $artifact['processing_warnings'] ?? array() )
					)
				)
			),
			0,
			20
		);
		return $normalized;
	}

	/**
	 * Normalizes one SHA-256 value.
	 *
	 * @param string $value Raw digest.
	 * @return string
	 */
	public static function normalize_sha256( $value ) {
		$value = strtolower( trim( sanitize_text_field( (string) $value ) ) );
		if ( 0 === strpos( $value, 'sha256:' ) ) {
			$value = substr( $value, 7 );
		}

		return 1 === preg_match( '/^[a-f0-9]{64}$/', $value ) ? $value : '';
	}

	/**
	 * Parses one strict RFC3339 timestamp without relative-date fallbacks.
	 *
	 * @param string $value Raw timestamp.
	 * @return int
	 */
	public static function expiry_timestamp( $value ) {
		$value = trim( (string) $value );
		$formats = false !== strpos( $value, '.' )
			? array( 'Y-m-d\TH:i:s.uP' )
			: array( 'Y-m-d\TH:i:sP' );
		foreach ( $formats as $format ) {
			$parsed = \DateTimeImmutable::createFromFormat( $format, $value );
			$errors = \DateTimeImmutable::getLastErrors();
			$has_errors = is_array( $errors ) && ( (int) ( $errors['warning_count'] ?? 0 ) > 0 || (int) ( $errors['error_count'] ?? 0 ) > 0 );
			if ( $parsed instanceof \DateTimeImmutable && ! $has_errors ) {
				return $parsed->getTimestamp();
			}
		}

		return 0;
	}

	/**
	 * Sanitizes one optional basename in WordPress and isolated tests.
	 *
	 * @param string $value Raw basename.
	 * @return string
	 */
	private static function sanitize_file_name( $value ) {
		if ( function_exists( 'sanitize_file_name' ) ) {
			return sanitize_file_name( (string) $value );
		}

		$value = basename( (string) $value );

		return (string) preg_replace( '/[^A-Za-z0-9._-]/', '', $value );
	}

	/**
	 * Returns one stable non-secret validation error.
	 *
	 * @param string $suffix Error suffix.
	 * @param string $message Error message.
	 * @param int    $status HTTP status.
	 * @return \WP_Error
	 */
	private static function error( $suffix, $message, $status = 400 ) {
		return new \WP_Error(
			'npcink_abilities_toolkit_cloud_artifact_' . sanitize_key( (string) $suffix ),
			$message,
			array( 'status' => absint( $status ) )
		);
	}
}
