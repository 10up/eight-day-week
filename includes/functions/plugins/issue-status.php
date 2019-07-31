<?php

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

	add_action( 'Eight_Day_Week\Core\plugin_init', function () {

		function ns( $function ) {
			return __NAMESPACE__ . "\\$function";
		}

		function a( $function ) {
			add_action( $function, ns( $function ) );
		}

		register_taxonomy();
		add_action( 'add_meta_boxes', function () {
			Tax\add_taxonomy_dropdown_meta_box( 'print_issue_status' );
		} );

		add_action( 'restrict_manage_posts', ns( 'add_issue_status_filters' ) );
		add_filter( 'Eight_Day_Week\Print_Issue_Columns\pi_columns', ns( 'filter_pi_columns_issue_status' ) );
		add_action( 'Eight_Day_Week\Admin_Menu_Page\admin_menu', ns( 'admin_menu' ), 0 );
	} );

}


/**
 * Register Issue Status Taxonomy
 *
 * @uses register_taxonomy
 * @return void
 */
function register_taxonomy() {
	$labels   = [
		'name'                       => __( 'Issue Statuses', 'eight-day-week-print-workflow' ),
		'singular_name'              => __( 'Issue Status', 'eight-day-week-print-workflow' ),
		'search_items'               => __( 'Search Issue Statuses', 'eight-day-week-print-workflow' ),
		'all_items'                  => __( 'All Issue Statuses', 'eight-day-week-print-workflow' ),
		'edit_item'                  => __( 'Edit Issue Status', 'eight-day-week-print-workflow' ),
		'update_item'                => __( 'Update Issue Status', 'eight-day-week-print-workflow' ),
		'add_new_item'               => __( 'Add New Issue Status', 'eight-day-week-print-workflow' ),
		'new_item_name'              => __( 'New Issue Status Name', 'eight-day-week-print-workflow' ),
		'menu_name'                  => __( 'Issue Status', 'eight-day-week-print-workflow' ),
		'separate_items_with_commas' => '',
		'choose_from_most_used'      => __( 'Choose from an existing Issue Status', 'eight-day-week-print-workflow' ),
		'not_found'                  => __( 'No Issue Statuses found.', 'eight-day-week-print-workflow' ),
	];

	$args = [
		'hierarchical'       => true,
		'labels'             => $labels,
		'show_ui'            => true,
		'show_admin_column'  => true,
		'query_var'          => true,
		'show_tagcloud'      => false,
		'rewrite'            => false,
		'public'             => true,
		'publicly_queryable' => false,
		'capabilities'       => [ 'manage_' . EDW_PRINT_ISSUE_CPT ]
	];

	\register_taxonomy( 'print_issue_status', EDW_PRINT_ISSUE_CPT, $args );
}

/**
 * Outputs a select to filter the print issue rubric by issue status
 */
function add_issue_status_filters() {
	global $typenow;

	//Display on our custom post type only
	if ( $typenow == EDW_PRINT_ISSUE_CPT ) {

		$tax_slug = 'print_issue_status';
		$tax_obj  = get_taxonomy( $tax_slug );
		$tax_name = $tax_obj->labels->name;

		//print issues are private, so hide_empty must be false
		//as private posts don't count towards term count
		$terms = get_terms( $tax_slug, [ 'hide_empty' => false ] );

		echo '<select name="' . esc_attr( $tax_slug ) . '" id="' . esc_attr( $tax_slug ) . '" class="postform">';
		echo '<option value="">' . sprintf( esc_html_x( 'Show All %s', 'Select option for filtering all issue statuses', 'eight-day-week-print-workflow' ) , esc_html( $tax_name ) ) . '</option>';
		foreach ( $terms as $term ) {
			echo '<option value="' . esc_attr( $term->slug ) . '"' . selected( isset( $_GET[ $tax_slug ] ) ? $_GET[ $tax_slug ] : '', $term->slug ) . '>' . esc_html( $term->name ) . '</option>';
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
 * @param $columns array Incoming print issue table columns
 *
 * @return array Modified columns
 */
function filter_pi_columns_issue_status( $columns ) {

	$status = [
		'taxonomy-print_issue_status' => __( 'Status', 'eight-day-week' ),
	];

	//put status after date
	$title_offset = array_search( 'custom-date', array_keys( $columns ) );
	if ( $title_offset ) {
		$end = $status + array_slice( $columns, $title_offset + 1, null );
		$columns = array_slice( $columns, 0, $title_offset + 1 ) + $end;
	} else {
		$columns += $status;
	}

	return $columns;
}
