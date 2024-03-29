<?php
/**
 * Sets up plugin user roles
 *
 * @package Eight_Day_Week
 */

namespace Eight_Day_Week\User_Roles;

use Eight_Day_Week as PP;

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
	};

	/**
	 * Add a filter hook and associate it with a callback function.
	 *
	 * @param string $func The name of the filter hook to add.
	 */
	function f( $func ) {
		add_filter( $func, ns( $func ) );
	}

	add_action( 'Eight_Day_Week\Core\init', ns( 'add_roles' ) );

	f( 'editable_roles' );

	add_action( 'restrict_manage_users', ns( 'output_print_role_on_user_list_table' ) );

	add_action( 'load-users.php', ns( 'update_users_print_role' ) );

	// Show print role in user list table.
	add_filter( 'manage_users_columns', ns( 'add_print_role_column' ) );
	add_filter( 'manage_users_custom_column', ns( 'add_print_role_column_text' ), 10, 3 );

	add_action( 'set_user_role', ns( 're_save_print_roles' ), 10, 3 );
}

/**
 * Global storage of print prod roles
 *
 * @todo Functionize
 * @todo Filterize
 */
global $edw_roles;
$edw_roles = array( 'print_editor', 'print_production' );

/**
 * Adds the various roles used by Eight Day Week
 */
function add_roles() {
	add_print_editor_role();
	add_print_production_role();
	add_caps_to_built_in_roles();
}

/**
 * Adds Print Editor role
 */
function add_print_editor_role() {
	PP\duplicate_role(
		'subscriber',
		'print_editor',
		__( 'Print Editor', 'eight-day-week-print-workflow' ),
		get_editor_caps()
	);
}

/**
 * Gets capabilities for print editors
 *
 * @return array Capabilities for print editors
 */
function get_editor_caps() {
	$pt = EDW_PRINT_ISSUE_CPT;
	return array(
		// Grant dashboard access.
		'read'                    => true,
		// Grant all caps related to the CPT.
		'edit_post'               => true,
		"edit_{$pt}"              => true,
		"read_{$pt}"              => true,
		"delete_{$pt}"            => true,
		"edit_{$pt}s"             => true,
		"edit_others_{$pt}s"      => true,
		"publish_{$pt}s"          => true,
		"read_private_{$pt}s"     => true,
		"delete_{$pt}s"           => true,
		"delete_private_{$pt}s"   => true,
		"delete_published_{$pt}s" => true,
		"delete_others_{$pt}s"    => true,
		"edit_private_{$pt}s"     => true,
		"edit_published_{$pt}s"   => true,
		"edit_{$pt}s"             => true,
		// Custom cap for submenus.
		"manage_{$pt}"            => true,
	);
}

/**
 * Adds Print Editor role
 */
function add_print_production_role() {
	PP\duplicate_role(
		'subscriber',
		'print_production',
		__( 'Eight Day Week', 'eight-day-week-print-workflow' ),
		get_production_caps()
	);
}

/**
 * Gets capabilities for print production
 *
 * @return array Capabilities for print production
 */
function get_production_caps() {
	$pt = EDW_PRINT_ISSUE_CPT;
	return array(
		// Grants dashboard access.
		'read'                => true,
		// Read CPT.
		"read_{$pt}"          => true,
		// Read private CPT.
		"read_private_{$pt}s" => true,
		// Access post list table.
		"edit_{$pt}s"         => true,
		// Access post editor.
		"edit_{$pt}"          => true,
	);
}

/**
 * Assigns print capabilities to WP built in roles
 */
function add_caps_to_built_in_roles() {
	// Use array keys so things like edit_post => false don't come over.
	PP\add_role_caps( 'administrator', array_keys( get_editor_caps() ) );
}

/**
 * Removes print roles from the default dropdown
 *
 * @param array $roles Roles from the filter.
 *
 * @return array Non-print roles
 */
function editable_roles( $roles ) {
	global $edw_roles;
	// Remove print roles by key.
	$roles = array_diff_key( $roles, array_flip( $edw_roles ) );
	return $roles;
}

/**
 * Outputs the Print Role select on the user editor screen
 *
 * @param string $which The location of the extra table nav markup: 'top' or 'bottom'.
 */
function output_print_role_on_user_list_table( $which ) {
	global $edw_roles;

	$role_names = get_role_names();

	$select_id = 'top' === $which ? 'pp-print-role' : 'pp-print-role2';

	echo '<select id="' . esc_attr( $select_id ) . '" name="' . esc_attr( $select_id ) . '">';
	echo '<option value="-1">' . esc_html__( 'Change print role to...', 'eight-day-week-print-workflow' ) . '</option>';
	echo '<option value="remove">' . esc_html_x( 'None (remove)', 'Select option text for removing print production role.', 'eight-day-week-print-workflow' ) . '</option>';
	foreach ( (array) $edw_roles as $role ) {
		$wp_role = get_role( $role );
		if ( ! $wp_role ) {
			continue;
		}
		$name = $role_names[ $role ];
		echo '<option value="' . esc_attr( $role ) . '">' . esc_html( $name ) . '</option>';
	}
	echo '</select>';
}

/**
 * Get role names of \WP_Roles class
 *
 * For some unknown reason, the wp_roles func was undefined on staging.
 * This func is based on that one, and gets the role_names prop of \WP_Roles
 *
 * @return array Role names
 */
function get_role_names() {
	global $wp_roles;

	if ( ! isset( $wp_roles ) ) {
		$wp_roles = new \WP_Roles(); // phpcs:ignore
	}
	return $wp_roles->role_names;
}

/**
 * Subtracts built (editable) roles from a user to get any leftovers
 *
 * Also ensures that only print roles are returned
 * by comparing against the $edw_roles array
 *
 * @param \WP_User $user User to query.
 *
 * @return array Print Roles
 */
function get_user_print_roles( $user ) {
	global $edw_roles;
	$current_roles = $user->roles;
	$roles         = get_editable_roles();

	// Flip to check values as if they were keys (to match WP_Roles->roles format).
	// Flip back to get back indexed array for later.
	$non_built_in = array_flip( array_diff_key( array_flip( $current_roles ), $roles ) );
	if ( ! $non_built_in || ! is_array( $non_built_in ) ) {
		return array();
	}
	return array_intersect( $edw_roles, $non_built_in );
}

/**
 * Add/remove print roles to a user
 */
function update_users_print_role() {

	global $edw_roles;

	if ( ! isset( $_GET['pp-print-role'] ) || ! isset( $_GET['pp-print-role2'] ) ) {
		return;
	}

	// Don't proccess the default option.
	if ( -1 === $_GET['pp-print-role'] && -1 === $_GET['pp-print-role2'] ) {
		return;
	}

	if ( empty( $_GET['users'] ) ) {
		return;
	}

	if ( ! current_user_can( 'promote_users' ) ) {
		wp_die( esc_html__( 'You can&#8217;t edit that user.', 'eight-day-week-print-workflow' ) );
	}

	check_admin_referer( 'bulk-users' );

	$new_role = -1 !== $_GET['pp-print-role'] ? sanitize_text_field( wp_unslash( $_GET['pp-print-role'] ) ) : sanitize_text_field( wp_unslash( $_GET['pp-print-role2'] ) );

	// Validate requested print role.
	if ( 'remove' !== $new_role && ! in_array( $new_role, $edw_roles, true ) ) {
		wp_die( esc_html__( 'You can&#8217;t give users that print role.', 'eight-day-week-print-workflow' ) );
	}

	$userids = array_map( 'intval', wp_unslash( $_GET['users'] ) );

	foreach ( $userids as $id ) {

		$id = (int) $id;

		if ( ! current_user_can( 'promote_user', $id ) ) {
			wp_die( esc_html__( 'You can&#8217;t edit that user.' ) );
		}

		// If the user doesn't already belong to the blog, bail.
		if ( is_multisite() && ! is_user_member_of_blog( $id ) ) {
			wp_die( esc_html__( 'Something went wrong.' ), 403 );
		}

		$user = get_userdata( $id );

		// Remove all previous roles.
		foreach ( (array) $edw_roles as $role_to_remove ) {
			$user->remove_role( $role_to_remove );
		}

		// Add new role.
		if ( 'remove' !== $new_role ) {
			$user->add_role( sanitize_text_field( $new_role ) );
		}
	}

	$redirect  = '';
	$query_arg = 'pp-print-role';
	if ( ! empty( get_query_var( $query_arg ) ) ) {
		$redirect = remove_query_arg( array( $query_arg ) );
	}

	$redirect = add_query_arg( array( 'update' => 'promote' ), $redirect );
	wp_safe_redirect( $redirect );
	exit;
}

/**
 * Add a user's stripped print role back
 *
 * By default, if a user has their built-in role changed
 * WP uses set_role, which strips the user's print role
 * This hooks onto that action, and adds the print role back.
 *
 * @param int    $user_id The ID of the user.
 * @param string $new_role The new role to be added.
 * @param array  $old_roles The array of old roles.
 */
function re_save_print_roles( $user_id, $new_role, $old_roles ) {
	global $edw_roles;

	// If adding a print role, we're done here.
	if ( in_array( $new_role, $edw_roles, true ) ) {
		return;
	}

	$user = get_userdata( $user_id );

	// If a user has a print role already, bail.
	if ( array_intersect( $edw_roles, $user->roles ) ) {
		return;
	}

	$old_print_roles = array_intersect( $edw_roles, $old_roles );

	// Get the first print role.
	$old_print_role = reset( $old_print_roles );

	$user->add_role( $old_print_role );

}

/**
 * Determines whether or not the current user can edit print issues
 *
 * Allows filter override so 3rd parties can determine
 *
 * @param string $capability A specific capability to check against.
 *
 * @return bool Whether or not the current user can edit print issues
 */
function current_user_can_edit_print_issue( $capability = '' ) {
	$default = current_user_can( 'publish_' . EDW_PRINT_ISSUE_CPT . 's' );
	return apply_filters( __NAMESPACE__ . '\cuc_edit_print_issue', $default, $capability );
}

/**
 * Add the print role column to the user rubric
 *
 * @param array $columns Incoming columns.
 *
 * @return array
 */
function add_print_role_column( $columns ) {
	$columns['print_role'] = __( 'Print Role', 'eight-day-week-print-workflow' );
	return $columns;
}

/**
 * Add the print role text to the user rubric
 *
 * @param string $value  Incoming value.
 * @param string $column_name Current column's name.
 * @param int    $user_id  The current user's ID.
 *
 * @return string Modified value
 */
function add_print_role_column_text( $value, $column_name, $user_id ) {

	$user = get_userdata( $user_id );

	if ( 'print_role' === $column_name ) {
		$role       = get_user_print_roles( $user );
		$role_names = get_role_names();
		return $role ? $role_names[ reset( $role ) ] : '';
	}

	return $value;
}
