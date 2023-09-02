<?php
/**
 * Handles the print issue functionality
 *
 * @package Eight_Day_Week
 */

namespace Eight_Day_Week\Print_Issue;

use Eight_Day_Week\User_Roles as User;
use Eight_Day_Week\Core as Core;

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

	add_action( 'Eight_Day_Week\Core\init', ns( 'register_post_type' ) );
	add_filter( 'post_updated_messages', ns( 'post_type_updated_labels' ) );

	// Remove coauthors box.
	add_action( 'add_meta_boxes', ns( 'alter_metaboxes' ) );

	// Enqueue admin-side scripts.
	a( 'admin_enqueue_scripts' );

	// General nonce for this CPT.
	add_action( 'edit_form_top', ns( 'print_issue_nonce' ) );

	add_action( 'save_post_' . EDW_PRINT_ISSUE_CPT, ns( 'save_print_issue' ), 10, 3 );

	add_filter( 'post_row_actions', ns( 'modify_print_issue_actions' ), 10, 2 );
	add_filter( 'bulk_actions-edit-' . EDW_PRINT_ISSUE_CPT, ns( 'remove_bulk_edit' ) );
	add_filter( 'display_post_states', ns( 'hide_post_states' ), 10, 2 );

	add_action( 'admin_menu', ns( 'remove_publish_box' ) );

	add_filter( 'gettext', ns( 'filter_publish_date_text' ) );

	add_filter( 'get_user_option_meta-box-order_' . EDW_PRINT_ISSUE_CPT, ns( 'get_side_metabox_order' ), 9999 );

	add_filter( 'Eight_Day_Week\User_Roles\cuc_edit_print_issue', ns( 'filter_can_edit_for_rov' ), 10 );
	add_filter( 'show_post_locked_dialog', ns( 'filter_show_post_locked_dialog_for_rov' ), 10, 1 );
	add_filter( 'update_post_metadata', ns( 'filter_metadata_no_post_locks_on_rov' ), 9999, 3 );
	add_filter( 'get_post_metadata', ns( 'filter_metadata_no_post_locks_on_rov' ), 9999, 3 );
	add_filter( 'admin_title', ns( 'filter_admin_title_for_rov' ) );
}

/**
 * Outputs a nonce for the print issue
 */
function print_issue_nonce() {
	global $post;
	wp_nonce_field( 'print-issue-' . $post->ID, 'pi-nonce', true );
}

/**
 * Adds labels for display when a print issue is updated
 *
 * @param array $messages Current messages.
 *
 * @return array Modified messages
 */
function post_type_updated_labels( $messages ) {
	global $post;
	$post_id = $post->ID;

	$permalink        = get_permalink( $post_id );
	$page_preview_url = apply_filters( 'preview_post_link', add_query_arg( 'preview', 'true', $permalink ), $post );

	$singular                        = __( 'Print Issue', 'eight-day-week-print-workflow' );
	$messages[ EDW_PRINT_ISSUE_CPT ] = array(
		0  => '', // Unused. Messages start at index 1.
		1  => __( 'Print Issue updated.', 'eight-day-week-print-workflow' ),
		2  => __( 'Custom field updated.' ),
		3  => __( 'Custom field deleted.' ),
		4  => __( 'Print Issue updated.', 'eight-day-week-print-workflow' ),
		/* translators: %s: The date and time of the revision. */
		5  => isset( $_GET['revision'] ) ? sprintf( __( 'Print Issue restored to revision from %s', 'eight-day-week-print-workflow' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6  => __( 'Print Issue published.', 'eight-day-week-print-workflow' ),
		7  => __( 'Print Issue saved.', 'eight-day-week-print-workflow' ),
		8  => __( 'Print Issue submitted.', 'eight-day-week-print-workflow' ),
		9  => __( 'Print Issue scheduled', 'eight-day-week-print-workflow' ),
		10 => __( 'Print Issue draft updated', 'eight-day-week-print-workflow' ),
	);

	return $messages;
}

/**
 * Register print issue CPT
 */
function register_post_type() {
	$labels = array(
		'name'               => __( 'Print Issues', 'eight-day-week-print-workflow' ),
		'singular_name'      => __( 'Print Issue', 'eight-day-week-print-workflow' ),
		'add_new_item'       => __( 'Add New Print Issue', 'eight-day-week-print-workflow' ),
		'edit_item'          => __( 'Edit Print Issue', 'eight-day-week-print-workflow' ),
		'new_item'           => __( 'New Print Issue', 'eight-day-week-print-workflow' ),
		'view_item'          => __( 'View Print Issue', 'eight-day-week-print-workflow' ),
		'search_items'       => __( 'Search Print Issues', 'eight-day-week-print-workflow' ),
		'not_found'          => __( 'No Print Issues found', 'eight-day-week-print-workflow' ),
		'not_found_in_trash' => __( 'No Print Issues found in Trash', 'eight-day-week-print-workflow' ),
	);

	// Post type args.
	$capability_type = EDW_PRINT_ISSUE_CPT;
	$args            = array(
		'labels'              => $labels,
		'exclude_from_search' => true,
		'show_in_nav_menus'   => false,
		'show_ui'             => true,
		'show_in_menu'        => false,
		'show_in_admin_bar'   => false,
		'capabilities'        => array(
			'edit_post'              => "edit_{$capability_type}",
			'read_post'              => "read_{$capability_type}",
			'delete_post'            => "delete_{$capability_type}",
			'edit_posts'             => "edit_{$capability_type}s",
			'edit_others_posts'      => "edit_others_{$capability_type}s",
			'publish_posts'          => "publish_{$capability_type}s",
			'read_private_posts'     => "read_private_{$capability_type}s",
			'delete_posts'           => "delete_{$capability_type}s",
			'delete_private_posts'   => "delete_private_{$capability_type}s",
			'delete_published_posts' => "delete_published_{$capability_type}s",
			'delete_others_posts'    => "delete_others_{$capability_type}s",
			'edit_private_posts'     => "edit_private_{$capability_type}s",
			'edit_published_posts'   => "edit_published_{$capability_type}s",
			'create_posts'           => "edit_others_{$capability_type}s",
		),
		'map_meta_cap'        => false,
		'supports'            => array(
			'title',
			'author',
		),
	);

	\register_post_type( EDW_PRINT_ISSUE_CPT, $args );
}

/**
 * Removes unneeded metaboxes
 */
function alter_metaboxes() {

	global $coauthors_plus;

	if ( EDW_PRINT_ISSUE_CPT === get_post_type() ) {

		// Remove co authors plus metabox.
		if (
			is_object( $coauthors_plus )
			&& property_exists( $coauthors_plus, 'coauthors_meta_box_name' )
		) {
			remove_meta_box( $coauthors_plus->coauthors_meta_box_name, get_post_type(), 'normal' );
		}

		// Remove built in authors metabox.
		remove_meta_box( 'authordiv', EDW_PRINT_ISSUE_CPT, 'normal' );

	}
}

/**
 * Enqueues this plugin's scripts when appropriate
 *
 * @param string $hook The current screen hook.
 */
function admin_enqueue_scripts( $hook ) {

	if ( ( 'post.php' === $hook || 'post-new.php' === $hook ) && EDW_PRINT_ISSUE_CPT === get_post_type() ) {

		wp_enqueue_style( 'edw-admin', \Eight_Day_Week\Core\get_asset_url( 'style', 'css' ), array(), EDW_VERSION );

		wp_register_script(
			'edw-admin',
			\Eight_Day_Week\Core\get_asset_url( 'scripts', 'js' ),
			array(
				'jquery',
				'wp-util',
				'jquery-ui-autocomplete',
			),
			EDW_VERSION,
			true
		);

		wp_localize_script(
			'edw-admin',
			'EDW_Vars',
			array(
				'progress_img'         => '<img class="edw-loading" src="' .
											esc_url( includes_url( 'images/spinner-2x.gif' ) ) .
											'" alt="..." />',
				'nonce'                => \Eight_Day_Week\Core\create_nonce(),
				'cuc_edit_print_issue' => User\current_user_can_edit_print_issue(),
				'rov'                  => is_read_only_view(),
			)
		);

		wp_enqueue_script( 'jquery-ui-autocomplete' );
		wp_enqueue_script( 'edw-admin' );

	}
}

/**
 * Saves print issue metadata
 * Namely by providing a hook for other parts to hook + save
 *
 * @param int      $post_id The current post ID.
 * @param \WP_Post $post the current post.
 * @param bool     $update Is it an update.
 */
function save_print_issue( $post_id, $post, $update ) {

	// Bail if the nonce isn't present.
	if ( ! isset( $_POST['pi-nonce'] ) ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! User\current_user_can_edit_print_issue() ) {
		return;
	}

	// Verify general nonce.
	check_admin_referer( 'print-issue-' . $post_id, 'pi-nonce' );

	// Allow other parts to hook.
	do_action( 'save_print_issue', $post_id, $post, $update );
}

/**
 * Modifies the "action" links on Print Issue table
 *
 * 1. Remove "Quick Edit" from the Print Issue list table
 * 2. Change "Edit" To "View" for print prod. This still links to the editor.
 *
 * @param array    $actions Actions.
 * @param \WP_Post $post The post.
 *
 * @return array Modified actions
 */
function modify_print_issue_actions( $actions, $post ) {
	if ( EDW_PRINT_ISSUE_CPT !== $post->post_type ) {
		return $actions;
	}

	unset( $actions['inline hide-if-no-js'] );

	if ( ! User\current_user_can_edit_print_issue() ) {
		$actions = array();
	}

	$actions['edit_view'] = get_rov_link( $post );

	return $actions;
}

/**
 * Remove "Edit" from the Print Issue bulk actions dropdown
 *
 * @param array $actions Actions.
 *
 * @return array Modified actions
 */
function remove_bulk_edit( $actions ) {
	unset( $actions['edit'] );

	return $actions;
}

/**
 * Remove the entire "Publish" box for print prod users
 */
function remove_publish_box() {
	if ( ! User\current_user_can_edit_print_issue() ) {
		remove_meta_box( 'submitdiv', EDW_PRINT_ISSUE_CPT, 'side' );
	}
}

/**
 * Changes the text of the publish date to fit print issue idea
 *
 * Uses a somewhat questionable means to do so...via i18n
 *
 * @param string $text Text to translate.
 *
 * @return string Translated text
 */
function filter_publish_date_text( $text ) {

	global $typenow;

	if (
		is_admin() &&
		isset( $_GET['post'] ) &&
		( isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) &&
		(
			EDW_PRINT_ISSUE_CPT === $typenow ||
			(
				isset( $_REQUEST['screen_id'] ) &&
				EDW_PRINT_ISSUE_CPT === $_REQUEST['screen_id']
			) ||
			EDW_PRINT_ISSUE_CPT === get_post_type( absint( $_GET['post'] ) )
		) ||
		isset( $_GET['post_type'] ) && EDW_PRINT_ISSUE_CPT === $_GET['post_type']
	) {

		if ( 'Publish <b>immediately</b>' === $text ) {
			return 'Issue Date:';
		}

		switch ( $text ) {
			case 'Published on: <b>%1$s</b>':
			case 'Publish on: <b>%1$s</b>':
			case 'Schedule for: <b>%1$s</b>':
			case 'Scheduled for: <b>%1$s</b>':
				$text = 'Issue Date: <b>%1$s</b>';
				break;
			case 'Publish on:':
			case 'Schedule for:':
			case 'Published on:':
				$text = 'Issue Date:';
				break;
			case 'Schedule':
			case 'Publish':
			case 'Update':
				$text = 'Save';
				break;
			case '%1$s %2$s, %3$s @ %4$s : %5$s':
				// WP 4.3.1.
			case '%1$s %2$s, %3$s @ %4$s:%5$s':
				$text = '%1$s %2$s, %3$s <hide>@ %4$s : %5$s</hide>';
				break;
			case 'M j, Y @ H:i':
				$text = 'M j, Y <\h\i\d\e> @ H:i</\h\i\d\e>';
				break;
		}
	}

	return $text;
}

/**
 * Gives explicit order to the side metaboxes of a print issue
 * Namely, make the submit div always last
 *
 * @param bool|array $order Incoming order, may be false.
 *
 * @return array The ordered order
 */
function get_side_metabox_order( $order ) {

	global $wp_meta_boxes;

	if ( false === $order ) {

		// Get all registered metaboxes, but only the KEYS (slugs).
		$wpmb_order = array_keys_recursive( $wp_meta_boxes['print-issue'] );

		foreach ( $wpmb_order as $location => $priorities ) {

			// Remove empty locations.
			$priorities = array_filter( $priorities );

			// Initialize the string for this index (needed because concat is used below).
			$order[ $location ] = array();

			foreach ( $priorities as $priority => $boxes ) {
				$keys               = array_keys( $boxes );
				$order[ $location ] = array_merge( $order[ $location ], $keys );
			}

			// Make submit div last.
			if ( 'side' === $location ) {
				// Remove submitdiv by value.
				unset( $order[ $location ][ array_flip( $order[ $location ] )['submitdiv'] ] );
				$order[ $location ][] = 'submitdiv';
			}

			// WP expects a comma separated string of metabox slugs give it to 'em.
			$order[ $location ] = implode( ',', $order[ $location ] );
		}
	} else {

		// If there is an order, it's an associative array of comma separated strings,
		// [ 'location' => 'box1,box2,box3' ].

		// We're just dealing with the side order here.
		$side = explode( ',', $order['side'] );

		// So we search for submitdiv metabox.
		$side_key = array_search( 'submitdiv', $side, true );

		// And move it to the end.
		if ( false !== $side_key ) {
			$save = $side[ $side_key ];
			unset( $side[ $side_key ] );
			$side[]        = $save;
			$order['side'] = implode( ',', $side );
		}
	}

	return $order;
}

/**
 * Yields keys of an array, recursively
 *
 * @param array $ary The mouth from which to pull the teeth.
 * @param int   $max_depth The max number of recursions to perform.
 * @param int   $depth The current depth of the array (used in recursion).
 * @param array $array_keys The current set of array keys (used in recursion).
 *
 * @return array
 */
function array_keys_recursive( $ary, $max_depth = INF, $depth = 0, $array_keys = array() ) {
	if ( $depth < $max_depth ) {
		++$depth;
		$keys = array_keys( $ary );
		foreach ( $keys as $key ) {
			if ( is_array( $ary[ $key ] ) ) {
				$array_keys[ $key ] = array_keys_recursive( $ary[ $key ], $max_depth, $depth );
			}
		}
	}

	return $array_keys;
}

/**
 * Filters the cuc edit check for the "read only view"
 *
 * @param bool $can_edit Incoming value.
 *
 * @return bool False if ROV is active, else incoming value
 */
function filter_can_edit_for_rov( $can_edit ) {

	if ( is_read_only_view() ) {
		$can_edit = false;
	}

	return $can_edit;
}

/**
 * Determines whether or not ROV is active
 *
 * Uses a $_GET parameter to determine state
 *
 * @return bool Whether or not ROV is active
 */
function is_read_only_view() {
	return isset( $_GET['view'] ) && 'ro' === $_GET['view'];
}

/**
 * Gets an HTML rov hyperlink to a print issue
 *
 * @param \WP_Post $post The current post.
 *
 * @return string The rov HTML hyperlink
 */
function get_rov_link( $post ) {
	return '<a href="' . esc_url( get_rov_url( $post ) ) . '">' . esc_html_x( 'View', 'Link text to view a print issue', 'eight-day-week-print-workflow' ) . '</a>';
}

/**
 * Gets the ROV URL to a print issue
 *
 * @param \WP_Post $post The current post.
 *
 * @return string The ROV URL
 */
function get_rov_url( $post ) {
	return esc_url( add_query_arg( 'view', rawurlencode( 'ro' ), get_edit_post_link( $post->ID ) ) );
}

/**
 * Determines whether or not post locking dialogs should be output
 *
 * @param bool $show Incoming value.
 *
 * @return bool Whether or not to show post locking dialogs
 */
function filter_show_post_locked_dialog_for_rov( $show ) {

	if ( EDW_PRINT_ISSUE_CPT === get_post_type() ) {
		if ( is_read_only_view() || ! User\current_user_can_edit_print_issue() ) {
			$show = false;
		} else {
			add_filter( 'preview_post_link', __NAMESPACE__ . '\filter_preview_post_link_for_rov', 10, 2 );
		}
	}

	return $show;
}

/**
 * Filters the preview link so it yields the ROV
 *
 * @param string   $preview Incoming preview URL.
 * @param \WP_Post $post Current post.
 *
 * @return string Modified preview link
 */
function filter_preview_post_link_for_rov( $preview, $post ) {
	return get_rov_url( $post );
}

/**
 * Prevents print production users from inducing a lock state/locked dialog
 *
 * Overrides get/set post meta to ensure that:
 * 1. A PP user never sets the lock on a PI they are viewing
 * 2. A PP user never sees the lock dialog on a PI they are viewing, when an editor has taken over/edited that post
 *
 * @param mixed  $orig The original value of the metadata.
 * @param int    $object_id The ID of the object whose metadata is being filtered.
 * @param string $meta_key The key of the metadata being filtered.
 * @return mixed The filtered value of the metadata.
 */
function filter_metadata_no_post_locks_on_rov( $orig, $object_id, $meta_key ) {
	if ( '_edit_lock' === $meta_key && EDW_PRINT_ISSUE_CPT === get_post_type( $object_id ) ) {
		if ( ! User\current_user_can_edit_print_issue() ) {
			$orig = false;
		}
	}
	return $orig;
}

/**
 * Modifies the <title> element to reflect RO view on PI editor screen
 *
 * @param string $title Incoming title.
 *
 * @return string Modified title
 */
function filter_admin_title_for_rov( $title ) {
	if ( EDW_PRINT_ISSUE_CPT === get_post_type() ) {
		if ( is_read_only_view() || ! User\current_user_can_edit_print_issue() ) {
			$title = esc_html__( 'Print Issue (Read Only)', 'eight-day-week-print-workflow' );
		}
	}

	return $title;
}

/**
 * Disables post states for print issue CPT
 *
 * Mainly used in the CPT List Table
 *
 * @param array    $post_states States of the post.
 * @param \WP_Post $post Current post.
 *
 * @return array Maybe modified post states
 */
function hide_post_states( $post_states, $post ) {

	if ( EDW_PRINT_ISSUE_CPT === get_post_type( $post ) ) {
		$post_states = array();
	}

	return $post_states;
}
