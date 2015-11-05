<?php

namespace Eight_Day_Week\Plugins\Issue_Publication;

use Eight_Day_Week\Core as Core;
use Eight_Day_Week\Taxonomies as Tax;

function setup() {

	add_action( 'Eight_Day_Week\Core\plugin_init', function () {

		function ns( $function ) {
			return __NAMESPACE__ . "\\$function";
		}

		register_taxonomy();
		add_action( 'add_meta_boxes', function(){
			Tax\add_taxonomy_dropdown_meta_box( 'print_issue_publication' );
		} );

		add_action( 'Eight_Day_Week\Admin_Menu_Page\admin_menu', ns( 'admin_menu' ), 1 );

	} );

}

/**
 * Adds the Issue Publication submenu to the parent print issue menu
 */
function admin_menu() {
	add_submenu_page( EDW_ADMIN_MENU_SLUG, 'Publications', 'Publications', 'manage_' . EDW_PRINT_ISSUE_CPT, 'edit-tags.php?taxonomy=print_issue_publication&amp;post_type=' . EDW_PRINT_ISSUE_CPT );
}

/**
 * Register Publication Taxonomy
 *
 * @uses register_taxonomy
 * @return void
 */
function register_taxonomy() {
	$labels   = [
		'name'                       => __( 'Publications', 'eight-day-week' ),
		'singular_name'              => __( 'Publication', 'eight-day-week' ),
		'search_items'               => __( 'Search Publications', 'eight-day-week' ),
		'all_items'                  => __( 'All Publications', 'eight-day-week' ),
		'edit_item'                  => __( 'Edit Publication', 'eight-day-week' ),
		'update_item'                => __( 'Update Publication', 'eight-day-week' ),
		'add_new_item'               => __( 'Add New Publication', 'eight-day-week' ),
		'new_item_name'              => __( 'New Publication Name', 'eight-day-week' ),
		'menu_name'                  => __( 'Publication', 'eight-day-week' ),
		'separate_items_with_commas' => '',
		'choose_from_most_used'      => __( 'Choose a Publication', 'eight-day-week' ),
		'not_found'                  => __( 'No Publications found.', 'eight-day-week' )
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

	\register_taxonomy( 'print_issue_publication', EDW_PRINT_ISSUE_CPT, $args );
}