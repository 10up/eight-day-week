<?php
/**
 * Handles the admin menu page functionality
 *
 * @package Eight_Day_Week
 */

namespace Eight_Day_Week\Admin_Menu_Page;

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

	/**
	 * Add an action hook and associate it with a callback function.
	 *
	 * @param string $func The name of the action hook to add.
	 */
	function a( $func ) {
		add_action( $func, ns( $func ) );
	}

	a( 'admin_menu' );

	// Dirty hack until https://core.trac.wordpress.org/ticket/22895 is solved.
	add_action(
		'admin_head',
		function() {
			?>
			<style type="text/css">
				a[href="removeme"]{
					display: none !important;
				}
			</style>
			<?php
		}
	);
}

/**
 * Add the admin menu pages!
 * Also provide action for 3rd party hooks
 */
function admin_menu() {

	$top_level  = _x( 'Print', 'Top level menu label', 'eight-day-week-print-workflow' );
	$pi_submenu = _x( 'Print Issues', 'Submenu label for the Print Issue CPT', 'eight-day-week-print-workflow' );

	// Top level "container", but still link to Print Issue CPT list table.
	add_menu_page( $top_level, $top_level, 'read_' . EDW_PRINT_ISSUE_CPT, EDW_ADMIN_MENU_SLUG, '', 'dashicons-media-document', 15 );

	// Duplicate CPT list table link, with more specific text as requested.
	add_submenu_page( EDW_ADMIN_MENU_SLUG, $pi_submenu, $pi_submenu, 'read_' . EDW_PRINT_ISSUE_CPT, EDW_ADMIN_MENU_SLUG );

	// Dirty hack until https://core.trac.wordpress.org/ticket/22895 is solved.
	add_submenu_page( EDW_ADMIN_MENU_SLUG, 'Dummy Submenu', 'Dummy Submenu', 'read', 'removeme' );

	do_action( __NAMESPACE__ . '\admin_menu' );

}
