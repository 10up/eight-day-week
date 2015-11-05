<?php
namespace Eight_Day_Week\Core;

/**
 * Default setup routine
 *
 * @uses add_action()
 * @uses do_action()
 *
 * @return void
 */
function setup() {
	function ns( $function ) {
		return __NAMESPACE__ . "\\$function";
	}

	add_action( 'init', ns( 'init' ) );
	add_action( __NAMESPACE__ . '\init', ns( 'i18n' ) );

	//activate current version of this plugin
	add_action( 'admin_init', ns( 'activate' ) );

	do_action( __NAMESPACE__ . '\loaded' );

	//add data to wp_send_json_*
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
		do_action ( __NAMESPACE__ . '\admin_init' );
	}

	//this should be used by plugins, so that they're guaranteed that all core functionality has been loaded
	do_action( __NAMESPACE__ . '\plugin_init' );
}

function i18n() {
	$locale = apply_filters( 'plugin_locale', get_locale(), 'eight-day-week' );
	load_textdomain( 'eight-day-week', WP_LANG_DIR . '/print-production/print-production-' . $locale . '.mo' );
	load_plugin_textdomain( 'eight-day-week', false, plugin_basename( EDW_PATH ) . '/languages/' );
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

	$stored_version = get_option( 'edw_activated');

	if( EDW_VERSION === $stored_version ) {
		return;
	}

	// init has already been tacked onto the `init` hook, so all CPTs should be loaded
	flush_rewrite_rules();

	do_action( 'edw_activate', $stored_version, EDW_VERSION );

	// Store the version in an option so we know the last activated version (and that it's been activated)
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
 * @param array $data Data to send in the response
 */
function send_json_success( $data = [] ) {
	wp_send_json_success( apply_filters( 'pp-ajax-data', $data ) );
}

/**
 * Send a json error response + tacked on data
 * @param array $data Data to send in the response
 */
function send_json_error( $data = [] ) {
	wp_send_json_error( apply_filters( 'pp-ajax-data', $data ) );
}

/**
 * Tacks a nonce onto designated data,
 * and converts a string response to a message array
 *
 * @param array|object $data Data to combine
 *
 * @return array Data + nonce
 */
function tack_on_ajax_response( $data = [] ) {
	$data = ( is_array( $data ) || is_object( $data ) ? $data : [ 'message' => $data ] );

	$nonce = create_nonce();
	if( is_array( $data ) ) {
		$data['_ajax_nonce'] = $nonce;
		$data['message'] = esc_html( $data['message'] );
	} else {
		$data->_ajax_nonce = $nonce;
		$data->message = esc_html( $data->message );
	}

	return $data;
}

/**
 * Checks the ajax referer based on a constant nonce slug
 */
function check_ajax_referer( $query_arg = false, $die = false ) {
	\check_ajax_referer( EDW_AJAX_NONCE_SLUG, $query_arg, $die );
}

/**
 * Checks the ajax referer based on a constant nonce slug
 * And ensures the request comes from an elevated (print editor) user
 */
function check_elevated_ajax_referer( $action = false, $query_arg = false, $die = false ) {
	check_ajax_referer( $query_arg, $die );

	//if the user didn't pass in an $action, check $_POST. One can pass an empty string to use no cap/action
	$action = false === $action ? $_POST['action'] : sanitize_text_field( $action );

	$elevated = \Eight_Day_Week\User_Roles\current_user_can_edit_print_issue( $action );

	if ( ! $elevated ) {
		wp_die( -1 );
	}
}

/**
 * Gets a URL depending on environment
 *
 * @param string $file_name The name of the file to return
 * @param string $js_or_css Whether retrieving JS or CSS
 *
 * @return string The final URL
 */
function get_asset_url ( $file_name, $js_or_css ) {
	$url = EDW_URL . 'assets/';

	if ( $js_or_css === 'js' ) {
		$url .= 'js/';
		$url .= EDW_PRODUCTION ? '' : 'src/';
		$ext = '.js';
	} else {
		$url .= 'css/';
		$ext = '.css';
	}

	$url .= $file_name;

	$url .= EDW_PRODUCTION ? '.min' : '';

	$url .= $ext;

	return esc_url( $url );
}

/**
 * Gets the timezone stored in the DB, or a default
 *
 * @return mixed|string|void
 */
function get_timezone() {
	$timezone = get_option( 'timezone_string' );
	return apply_filters( __NAMESPACE__ . '\timezone' , $timezone ? $timezone : 'America/New_York' );
}