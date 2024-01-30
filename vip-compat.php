<?php
/**
 * Handles the VIP functionality.
 *
 * @package Eight_Day_Week
 */

namespace Eight_Day_Week;

/**
 * Retrieves the URL to the plugins directory or a specific plugin file.
 *
 * This function checks if the function wpcom_vip_plugins_url() exists and if it does, it calls that function to get the URL. Otherwise, it falls back to the \plugins_url() function.
 *
 * @param string $file The file path within the plugins directory. Empty string by default.
 * @return string The URL to the plugins directory or a specific plugin file.
 */
function plugins_url( $file ) {
	if ( function_exists( 'wpcom_vip_plugins_url' ) ) {
		return wpcom_vip_plugins_url( '', '', $file );
	}

	return \plugins_url( '/', $file );
}

/**
 * Duplicate a role and its capabilities.
 *
 * @param mixed  $from_role The role to duplicate from.
 * @param mixed  $to_role The role to duplicate to.
 * @param string $to_role_name The name of the duplicated role.
 * @param array  $new_caps The capabilities to add to the duplicated role.
 */
function duplicate_role( $from_role, $to_role, $to_role_name, $new_caps ) {
	if ( function_exists( 'wpcom_vip_duplicate_role' ) ) {
		wpcom_vip_duplicate_role( $from_role, $to_role, $to_role_name, $new_caps );
	} else {
		$caps = array_merge( get_role_caps( $from_role ), $new_caps );
		add_role( $to_role, $to_role_name, $caps );
	}
}

/**
 * Get a list of capabilities for a role.
 *
 * @param string $role Role name.
 *
 * @return array Array of caps for the role
 */
function get_role_caps( $role ) {
	if ( function_exists( 'wpcom_vip_get_role_caps' ) ) {
		$caps = wpcom_vip_get_role_caps( $role );
	} else {
		$caps     = array();
		$role_obj = get_role( $role );

		if ( $role_obj && isset( $role_obj->capabilities ) ) {
			$caps = $role_obj->capabilities;
		}
	}

	return $caps;
}

/**
 * Add a new role
 *
 * Usage: add_role( 'super-editor', 'Super Editor', array( 'level_0' => true ) );
 *
 * @param string $role Role name.
 * @param string $name Display name for the role.
 * @param array  $capabilities Key/value array of capabilities for the role.
 */
function add_role( $role, $name, $capabilities ) {
	if ( function_exists( 'wpcom_vip_add_role' ) ) {
		wpcom_vip_add_role( $role, $name, $capabilities );
	} else {
		global $wp_user_roles;

		$role_obj = get_role( $role );

		if ( ! $role_obj ) {
			\add_role( $role, $name, $capabilities );

			if ( ! isset( $wp_user_roles[ $role ] ) ) {
				$wp_user_roles[ $role ] = array( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					'name'         => $name,
					'capabilities' => $capabilities,
				);
			}
		} else {
			merge_role_caps( $role, $capabilities );
		}
	}
}

/**
 * Add capabilities to an existing role
 *
 * Usage: add_role_caps( 'contributor', array( 'upload_files' ) );
 *
 * @param string $role Role name.
 * @param array  $caps Capabilities to add to the role.
 */
function add_role_caps( $role, $caps ) {
	if ( function_exists( 'wpcom_vip_add_role_caps' ) ) {
		wpcom_vip_add_role_caps( $role, $caps );
	} else {
		$filtered_caps = array();
		foreach ( (array) $caps as $cap ) {
			$filtered_caps[ $cap ] = true;
		}
		merge_role_caps( $role, $filtered_caps );
	}
}

/**
 * Add new or change existing capabilities for a given role
 *
 * Usage: merge_role_caps( 'author', array( 'publish_posts' => false ) );
 *
 * @param string $role Role name.
 * @param array  $caps Key/value array of capabilities for this role.
 */
function merge_role_caps( $role, $caps ) {
	if ( function_exists( 'wpcom_vip_merge_role_caps' ) ) {
		wpcom_vip_merge_role_caps( $role, $caps );
	} else {
		global $wp_user_roles;

		$role_obj = get_role( $role );

		if ( ! $role_obj ) {
			return;
		}

		$current_caps = (array) get_role_caps( $role );
		$new_caps     = array_merge( $current_caps, (array) $caps );

		foreach ( $new_caps as $cap => $role_can ) {
			if ( $role_can ) {
				$role_obj->add_cap( $cap );
			} else {
				$role_obj->remove_cap( $cap );
			}
		}

		if ( isset( $wp_user_roles[ $role ] ) ) {
			$wp_user_roles[ $role ]['capabilities'] = array_merge( $current_caps, (array) $caps ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}
}
