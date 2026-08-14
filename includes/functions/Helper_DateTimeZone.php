<?php
/**
 * Helper_DateTimeZone
 *
 * @package Eight_Day_Week\Core
 */

namespace Eight_Day_Week\Core;

/**
 * Class Helper_DateTimeZone
 *
 * @package Eight_Day_Week\Core
 *
 * http://php.net/manual/en/function.timezone-name-from-abbr.php#89155
 */
class Helper_DateTimeZone extends \DateTimeZone {
	/**
	 * Converts a timezone hourly offset to its timezone's name.
	 *
	 * @example $offset = -5, $is_dst = 0 <=> return value = 'America/New_York'
	 *
	 * @param float $offset The timezone's offset in hours.
	 *                      Lowest value: -12 (Pacific/Kwajalein).
	 *                      Highest value: 14 (Pacific/Kiritimati).
	 * @param bool  $is_dst Is the offset for the timezone when it's in daylight
	 *                     savings time.
	 *
	 * @return string The name of the timezone: 'Asia/Tokyo', 'Europe/Paris', ...
	 */
	final public static function tzOffsetToName( $offset, $is_dst = null ) {
		if ( null === $is_dst ) {
			$is_dst = gmdate( 'I' );
		}

		$offset *= 3600;
		$zone    = timezone_name_from_abbr( '', $offset, $is_dst );

		if ( false === $zone ) {
			foreach ( timezone_abbreviations_list() as $abbr ) {
				foreach ( $abbr as $city ) {
					if ( (bool) $city['dst'] === (bool) $is_dst &&
						strlen( $city['timezone_id'] ) > 0 &&
						$city['offset'] === $offset
					) {
						$zone = $city['timezone_id'];
						break;
					}
				}

				if ( false !== $zone ) {
					break;
				}
			}
		}

		return $zone;
	}
}
