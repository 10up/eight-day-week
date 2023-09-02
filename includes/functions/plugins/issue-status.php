<?php
/**
 * Handles the issue status
 *
 * @package Eight_Day_Week
 */

namespace Eight_Day_Week\Plugins\Issue_Status;

use Eight_Day_Week\Core as Core;
use Eight_Day_Week\Taxonomies as Tax;

/**
 * Default setup routine
 *
 * @uses add_action
 * @return void
 */
function setup() {

	add_action(
		'Eight_Day_Week\Core\plugin_init',
		function () {
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

			register_taxonomy();
			add_action(
				'add_meta_boxes',
				function () {
					Tax\add_taxonomy_dropdown_meta_box( 'print_issue_status' );
				}
			);

			add_action( 'restrict_manage_posts', ns( 'add_issue_status_filters' ) );
			add_filter( 'Eight_Day_Week\Print_Issue_Columns\pi_columns', ns( 'filter_pi_columns_issue_status' ) );
			add_action( 'Eight_Day_Week\Admin_Menu_Page\admin_menu', ns( 'admin_menu' ), 0 );
		}
	);
}

/**
 * Register Issue Status Taxonomy
 *
 * @uses register_taxonomy
 * @return void
 */
function register_taxonomy() {
	$labels = array(
		'name'                       => __( 'Issue Statuses', 'eight-day-week-print-workflow' ),
		'singular_name'              => __( 'Issue Status', 'eight-day-week-print-workflow' ),
		'search_items'               => __( 'Search Issue Statuses', 'eight-day-week-print-workflow' ),
		'all_items'                  => __( 'All Issue Statuses', 'eight-day-week-print-workflow' ),
		'edit_item'                  => __( 'Edit Issue Status', 'eight-day-week-print-workflow' ),
		'view_item'                  => __( 'View Issue Status', 'eight-day-week-print-workflow' ),
		'add_or_remove_items'        => __( 'Add or Remove Issue Status', 'eight-day-week-print-workflow' ),
		'update_item'                => __( 'Update Issue Status', 'eight-day-week-print-workflow' ),
		'add_new_item'               => __( 'Add New Issue Status', 'eight-day-week-print-workflow' ),
		'new_item_name'              => __( 'New Issue Status Name', 'eight-day-week-print-workflow' ),
		'menu_name'                  => __( 'Issue Status', 'eight-day-week-print-workflow' ),
		'separate_items_with_commas' => '',
		'choose_from_most_used'      => __( 'Choose from an existing Issue Status', 'eight-day-week-print-workflow' ),
		'not_found'                  => __( 'No Issue Statuses found.', 'eight-day-week-print-workflow' ),
		'name_field_description'     => __( 'The name is how it appears when editing print issues.', 'eight-day-week-print-workflow' ),
		'slug_field_description'     => __( 'The computer code for the status. Unless you are sharing the statuses between systems, this can usually be left blank. It is usually all lowercase and contains only letters, numbers, and hyphens.', 'eight-day-week-print-workflow' ),
		'parent_field_description'   => __( 'Assign a parent issue status to create a hierarchy.', 'eight-day-week-print-workflow' ),
		'popular_items'              => __( 'Popular Parent Issue Statuses', 'eight-day-week-print-workflow' ),
		'parent_item'                => __( 'Parent Issue Status', 'eight-day-week-print-workflow' ),
		'parent_item_colon'          => __( 'Parent Issue Status Colon', 'eight-day-week-print-workflow' ),
		'no_terms'                   => __( 'No Issue Status', 'eight-day-week-print-workflow' ),
		'back_to_items'              => __( 'Back to Issue Statuses', 'eight-day-week-print-workflow' ),
	);

	$args = array(
		'hierarchical'       => true,
		'labels'             => $labels,
		'show_ui'            => true,
		'show_admin_column'  => true,
		'query_var'          => true,
		'show_tagcloud'      => false,
		'rewrite'            => false,
		'public'             => true,
		'publicly_queryable' => false,
		'capabilities'       => array( 'manage_' . EDW_PRINT_ISSUE_CPT ),
	);

	\register_taxonomy( 'print_issue_status', EDW_PRINT_ISSUE_CPT, $args );
}

/**
 * Outputs a select to filter the print issue rubric by issue status
 */
function add_issue_status_filters() {
	global $typenow;

	// Display on our custom post type only.
	if ( EDW_PRINT_ISSUE_CPT === $typenow ) {

		$tax_slug = 'print_issue_status';
		$tax_obj  = get_taxonomy( $tax_slug );
		$tax_name = $tax_obj->labels->name;

		// Print issues are private, so hide_empty must be false
		// as private posts don't count towards term count.
		$terms = get_terms( $tax_slug, array( 'hide_empty' => false ) );

		echo '<select name="' . esc_attr( $tax_slug ) . '" id="' . esc_attr( $tax_slug ) . '" class="postform">';
		/* translators: %s: Name of the taxonomy. Select option for filtering all issue statuses. */
		echo '<option value="">' . sprintf( esc_html_x( 'Show All %s', 'Select option for filtering all issue statuses', 'eight-day-week-print-workflow' ), esc_html( $tax_name ) ) . '</option>';
		foreach ( $terms as $term ) {
			echo '<option value="' . esc_attr( $term->slug ) . '"' . selected( isset( $_GET[ $tax_slug ] ) ? sanitize_text_field( wp_unslash( $_GET[ $tax_slug ] ) ) : '', $term->slug ) . '>' . esc_html( $term->name ) . '</option>';
		}
		echo '</select>';
	}
}

/**
 * Adds the Issue Status submenu to the parent print issue menu
 */
function admin_menu() {
	add_submenu_page( EDW_ADMIN_MENU_SLUG, __( 'Issue Statuses', 'eight-day-week-print-workflow' ), __( 'Issue Statuses', 'eight-day-week-print-workflow' ), 'manage_' . EDW_PRINT_ISSUE_CPT, 'edit-tags.php?taxonomy=print_issue_status&amp;post_type=' . EDW_PRINT_ISSUE_CPT );
}

/**
 * Add Issue Status to print issue table columns
 *
 * @param array $columns Incoming print issue table columns.
 *
 * @return array Modified columns
 */
function filter_pi_columns_issue_status( $columns ) {

	$status = array(
		'taxonomy-print_issue_status' => __( 'Status', 'eight-day-week-print-workflow' ),
	);

	// Put status after date.
	$title_offset = array_search( 'custom-date', array_keys( $columns ), true );
	if ( $title_offset ) {
		$end     = $status + array_slice( $columns, $title_offset + 1, null );
		$columns = array_slice( $columns, 0, $title_offset + 1 ) + $end;
	} else {
		$columns += $status;
	}

	return $columns;
}
