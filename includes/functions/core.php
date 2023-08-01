<?php
/**
 * Sets up the core functionality
 *
 * @package Eight_Day_Week
 */

namespace Eight_Day_Week\Core;

/**
 * Default setup routine
 *
 * @uses add_action()
 * @uses do_action()
 */
function setup() {
	/**
	 * A function that returns the fully qualified namespace of a given function.
	 *
	 * @param string $func The name of the function.
	 * @return string The fully qualified namespace of the function.
	 */
	function ns( $func ) {
		return __NAMESPACE__ . "\\$func";
	}

	add_action( 'init', ns( 'init' ) );
	add_action( __NAMESPACE__ . '\init', ns( 'i18n' ) );

	// Activate current version of this plugin.
	add_action( 'admin_init', ns( 'activate' ) );

	do_action( __NAMESPACE__ . '\loaded' );

	// Add data to wp_send_json_*.
	add_filter( 'pp-ajax-data', ns( 'tack_on_ajax_response' ) );
}

/**
 * Initializes the plugin and fires an action other plugins can hook into.
 *
 * @uses do_action()
 *
 * @return void
 */
function init() {
	do_action( __NAMESPACE__ . '\init' );
	if ( is_admin() ) {
		do_action( __NAMESPACE__ . '\admin_init' );
	}

	// This should be used by plugins, so that they're guaranteed that all core functionality has been loaded.
	do_action( __NAMESPACE__ . '\plugin_init' );
}

/**
 * Loads the internationalization (i18n) files
 */
function i18n() {
	$locale = apply_filters( 'plugin_locale', get_locale(), 'eight-day-week-print-workflow' );
	load_textdomain( 'eight-day-week-print-workflow', WP_LANG_DIR . '/eight-day-week-print-workflow/eight-day-week-print-workflow-' . $locale . '.mo' );
	load_plugin_textdomain( 'eight-day-week-print-workflow', false, plugin_basename( EDW_PATH ) . '/languages/' );
}

/**
 * Activate the plugin
 *
 * Checks the version number constant against an option stored in the DB
 * And fires off its processes if they aren't equal
 *
 * @uses init()
 * @uses flush_rewrite_rules()
 *
 * @return void
 */
function activate() {

	$stored_version = get_option( 'edw_activated' );

	if ( EDW_VERSION === $stored_version ) {
		return;
	}

	// Init has already been tacked onto the `init` hook, so all CPTs should be loaded.
	flush_rewrite_rules();

	do_action( 'edw_activate', $stored_version, EDW_VERSION );

	// Store the version in an option so we know the last activated version (and that it's been activated).
	update_option( 'edw_activated', EDW_VERSION );
}

/**
 * Utility for a global nonce
 *
 * @todo support extra parameters for more secure nonce, i.e. issue ID, intent, etc.
 *
 * @return string The nonce
 */
function create_nonce() {
	return wp_create_nonce( EDW_AJAX_NONCE_SLUG );
}

/**
 * Send a json success response + tacked on data
 *
 * @param array $data Data to send in the response.
 */
function send_json_success( $data = array() ) {
	wp_send_json_success( apply_filters( 'pp-ajax-data', $data ) );
}

/**
 * Send a json error response + tacked on data
 *
 * @param array $data Data to send in the response.
 */
function send_json_error( $data = array() ) {
	wp_send_json_error( apply_filters( 'pp-ajax-data', $data ) );
}

/**
 * Tacks a nonce onto designated data,
 * and converts a string response to a sanitized message in an array
 *
 * @param array|object $data Data to combine.
 *
 * @return array Data + nonce
 */
function tack_on_ajax_response( $data = array() ) {
	$data = ( is_array( $data ) || is_object( $data ) ? $data : array( 'message' => $data ) );

	$nonce = create_nonce();
	if ( is_array( $data ) ) {
		$data['_ajax_nonce'] = $nonce;
		if ( isset( $data['message'] ) ) {
			$data['message'] = esc_html( $data['message'] );
		}
	} else {
		$data->_ajax_nonce = $nonce;
		if ( property_exists( $data, 'message' ) ) {
			$data->message = esc_html( $data->message );
		}
	}

	return $data;
}


/**
 * Checks the AJAX referer.
 *
 * @param bool $query_arg The query argument.
 * @param bool $kill Whether to die if the referer check fails.
 */
function check_ajax_referer( $query_arg = false, $kill = false ) {
	\check_ajax_referer( EDW_AJAX_NONCE_SLUG, $query_arg, $kill );
}


/**
 * Check if the AJAX referer is valid and the user has elevated privileges.
 *
 * @param string|false $action The action string or false if not provided. If false, the action is obtained from $_POST['action'].
 * @param string|false $query_arg The query argument string or false if not provided.
 * @param bool         $kill Whether to die if the referer check fails.
 */
function check_elevated_ajax_referer( $action = false, $query_arg = false, $kill = false ) {
	check_ajax_referer( $query_arg, $kill );

	// If the user didn't pass in an $action, check $_POST. One can pass an empty string to use no cap/action.
	$action = false === $action && isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : sanitize_text_field( $action );

	$elevated = \Eight_Day_Week\User_Roles\current_user_can_edit_print_issue( $action );

	if ( ! $elevated ) {
		wp_die( -1 );
	}
}

/**
 * Gets a URL depending on environment
 *
 * @param string $file_name The name of the file to return.
 * @param string $js_or_css Whether retrieving JS or CSS.
 *
 * @return string The final URL
 */
function get_asset_url( $file_name, $js_or_css ) {
	$url = EDW_URL . 'assets/';

	if ( 'js' === $js_or_css ) {
		$url .= 'js/';
		$url .= EDW_PRODUCTION ? '' : 'src/';
		$ext  = '.js';
	} else {
		$url .= 'css/';
		$ext  = '.css';
	}

	$url .= $file_name;

	$url .= EDW_PRODUCTION ? '.min' : '';

	$url .= $ext;

	return esc_url( $url );
}

/**
 * Gets the timezone stored in the DB, or a default
 *
 * Accounts for storage of GMT offset vs Timezone
 *
 * @return mixed|string|void
 */
function get_timezone() {
	$timezone = get_option( 'timezone_string' );
	if ( ! $timezone ) {
		$timezone = new Helper_DateTimeZone( Helper_DateTimeZone::tzOffsetToName( get_offset() ) );
		$timezone = $timezone->getName();
	}
	return apply_filters( __NAMESPACE__ . '\timezone', $timezone ? $timezone : 'America/New_York' );
}

/**
 * Gets the GMT offset from the DB
 *
 * @return string The stored GMT offset
 */
function get_offset() {
	return get_option( 'gmt_offset' );
}

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
