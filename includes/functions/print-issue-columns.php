<?php
/**
 * Handles the print issue columns
 *
 * @package Eight_Day_Week
 */

namespace Eight_Day_Week\Print_Issue_Columns;

use Eight_Day_Week\Core as Core;

/**
 * Default setup routine
 *
 * @uses add_action, add_filter
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

	add_filter( 'manage_edit-' . EDW_PRINT_ISSUE_CPT . '_columns', ns( 'print_issue_cpt_columns' ) );
	add_action( 'manage_' . EDW_PRINT_ISSUE_CPT . '_posts_custom_column', ns( 'populate_print_issue_cpt_columns' ), 10, 2 );
	add_filter( 'manage_edit-' . EDW_PRINT_ISSUE_CPT . '_sortable_columns', ns( 'print_issue_sortable_columns' ), 10, 2 );
}

/**
 * Add columns to Print Issue List Table
 *
 * @param array $columns Post list table columns.
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
 * Adds the 'modified' column to the sortable columns array.
 *
 * @param array $columns The array of sortable columns.
 * @return array The updated array of sortable columns.
 */
function print_issue_sortable_columns( $columns ) {
	$columns['modified'] = 'modified';
	return $columns;
}


/**
 * Populates the print issue custom post type columns.
 *
 * @param string $colname The name of the column.
 * @param int    $post_id The ID of the post.
 */
function populate_print_issue_cpt_columns( $colname, $post_id ) {

	$post = get_post( $post_id );

	if ( 'modified' === $colname ) {
		$t_time = get_the_time( __( 'Y/m/d g:i:s a' ) );
		$m_time = $post->post_date;
		$time   = get_post_time( 'G', true, $post );

		$time_diff = time() - $time;

		if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
			/* translators: %s: Time difference in human readable format, i.e. "1 hour", "5 minutes", "2 days". */
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
