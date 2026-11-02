<?php
/**
 * Strict publication time parsing.
 *
 * @package NpcinkAbilitiesToolkit
 */
namespace Npcink_Abilities_Toolkit\Support;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Publish_Time {
	/**
	 * Explicit RFC3339 offsets take precedence over the timezone selector.
	 * @param string $raw Timestamp.
	 * @param string $mode site or utc for timestamps without an offset.
	 * @return array{local:string,gmt:string}|\WP_Error
	 */
	public static function parse( $raw, $mode ) {
		$site = wp_timezone();
		$utc = new \DateTimeZone( 'UTC' );
		$explicit = (bool) preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:Z|[+-](?:(?:0\d|1[0-3]):[0-5]\d|14:00))$/D', $raw );
		if ( ! $explicit && ! preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/D', $raw ) ) {
			return self::invalid();
		}
		$zone = 'utc' === $mode ? $utc : $site;
		$date = \DateTimeImmutable::createFromFormat( $explicit ? '!Y-m-d\TH:i:sP' : '!Y-m-d H:i:s', $raw, $zone );
		$errors = \DateTimeImmutable::getLastErrors();
		if ( false === $date || ( false !== $errors && ( $errors['warning_count'] || $errors['error_count'] ) ) || ( ! $explicit && $date->format( 'Y-m-d H:i:s' ) !== $raw ) ) {
			return self::invalid();
		}
		if ( ! $explicit ) {
			// A repeated DST wall time denotes two instants. Require an explicit offset.
			$wall = \DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $raw, $utc );
			if ( false === $wall ) {
				return self::invalid();
			}
			$wall_timestamp = $wall->getTimestamp();
			$matches = array();
			foreach ( (array) $zone->getTransitions( $date->getTimestamp() - 86400, $date->getTimestamp() + 86400 ) as $transition ) {
				$instant = $wall_timestamp - (int) $transition['offset'];
				if ( ( new \DateTimeImmutable( '@' . $instant ) )->setTimezone( $zone )->format( 'Y-m-d H:i:s' ) === $raw ) {
					$matches[ $instant ] = true;
				}
			}
			if ( count( $matches ) > 1 ) {
				return self::invalid();
			}
		}
		if ( $date->getTimestamp() <= time() ) {
			return new \WP_Error( 'npcink_abilities_toolkit_publish_at_past', __( 'publish_at must be in the future.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		return array( 'local' => $date->setTimezone( $site )->format( 'Y-m-d H:i:s' ), 'gmt' => $date->setTimezone( $utc )->format( 'Y-m-d H:i:s' ) );
	}

	/** @return \WP_Error */
	private static function invalid() {
		return new \WP_Error( 'npcink_abilities_toolkit_publish_at_invalid', __( 'Use an unambiguous Y-m-d H:i:s time or RFC3339 timestamp with an explicit offset.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
	}
}
