<?php
/**
 * Handles creating the print issue columns
 *
 * @package eight-day-week
 */

namespace Eight_Day_Week\Print_Issue_Columns;

use Eight_Day_Week\Core as Core;

/**
 * Default setup routine
 *
 * @uses add_action, add_filter
 * @return void
 */
function setup() {
	function ns( $function ) {
		return __NAMESPACE__ . "\\$function";
	}

	add_filter( 'manage_edit-' . EDW_PRINT_ISSUE_CPT . '_columns', ns( 'print_issue_cpt_columns' ) );
	add_action( 'manage_' . EDW_PRINT_ISSUE_CPT . '_posts_custom_column', ns( 'populate_print_issue_cpt_columns' ), 10, 2 );
	add_filter( 'manage_edit-' . EDW_PRINT_ISSUE_CPT . '_sortable_columns', ns( 'print_issue_sortable_columns' ), 10, 2 );
}

/**
 * Add columns to Print Issue List Table
 *
 * @param array $columns
 *
 * @return array post list table columns
 */
function print_issue_cpt_columns( $columns ) {

	$custom                = array();
	$custom['cb']          = $columns['cb'];
	$custom['title']       = $columns['title'];
	$custom['custom-date'] = __( 'Issue Date', 'eight-day-week-print-workflow' );
	$custom['modified']    = __( 'Last Modified', 'eight-day-week-print-workflow' );

	return apply_filters( __NAMESPACE__ . '\pi_columns', $custom );
}

/**
 * Manage sortable columns on print issue CPT
 *
 * @uses manage_edit-print-issue_sortable_columns
 * @return $columns post list table object
 */
function print_issue_sortable_columns( $columns ) {
	$columns['modified'] = 'modified';
	return $columns;
}

/**
 * Populate # of Articles on Print Issue post list table
 *
 * @uses manage_print-issue_posts_custom_column, get_post_meta
 * @return void
 */
function populate_print_issue_cpt_columns( $colname, $post_id ) {

	$post = get_post( $post_id );

	if ( 'modified' === $colname ) {
		$t_time = get_the_time( __( 'Y/m/d g:i:s a' ) );
		$m_time = $post->post_date;
		$time   = get_post_time( 'G', true, $post );

		$time_diff = time() - $time;

		if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
			$h_time = sprintf( __( '%s ago' ), human_time_diff( $time ) );
		} else {
			$h_time = mysql2date( __( 'Y/m/d' ), $m_time );
		}
		echo esc_html( $h_time );
	}
	if ( 'custom-date' === $colname ) {
		echo esc_html( get_the_date() );
	}
}
