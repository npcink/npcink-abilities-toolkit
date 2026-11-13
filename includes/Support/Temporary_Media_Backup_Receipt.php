<?php
/** Read-only projection of a temporary media file group. @package NpcinkAbilitiesToolkit */
namespace Npcink_Abilities_Toolkit\Support;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Temporary_Media_Backup_Receipt {
	/** @return array<string,mixed> */
	public static function from_record( array $record ): array {
		$group = $record['temporary_backup'] ?? array();
		if ( ! is_array( $group ) || 'temporary_7_days.v1' !== ( $group['version'] ?? '' ) ) { return array(); }
		$reasons = array();
		$retained = 0;
		$complete = true;
		foreach ( $group['files'] ?? array() as $file ) {
			if ( ! in_array( $file['state'] ?? '', array( 'removed', 'missing', 'current_image' ), true ) ) { $retained += (int) ( $file['filesize_bytes'] ?? 0 ); }
			if ( ! empty( $file['reason'] ) ) { $reasons[] = (string) $file['reason']; }
			if ( 'retained' !== ( $file['state'] ?? '' ) ) { $complete = false; }
		}
		return array(
			'policy' => 'temporary_7_days.v1',
			'state' => (string) ( $group['state'] ?? '' ),
			'expires_at_gmt' => (string) ( $group['expires_at_gmt'] ?? '' ),
			'removed_bytes' => (int) ( $group['removed_bytes'] ?? 0 ),
			'retained_bytes' => $retained,
			'blocked_reasons' => array_values( array_unique( $reasons ) ),
			'restorable' => $complete && in_array( $group['state'] ?? '', array( 'retained', 'blocked' ), true ) && strtotime( (string) ( $group['expires_at_gmt'] ?? '' ) ) > time(),
		);
	}
}
