<?php

namespace Eight_Day_Week\Plugins\Article_Status;

use Eight_Day_Week\Core as Core;
use Eight_Day_Week\Taxonomies as Tax;
use Eight_Day_Week\User_Roles as User;

function setup() {

	add_action(
		'Eight_Day_Week\Core\plugin_init',
		function () {

			function ns( $function ) {
				return __NAMESPACE__ . "\\$function";
			}

			register_taxonomy();

			add_filter( 'Eight_Day_Week\Articles\article_columns', ns( 'filter_article_columns_article_status' ), 0 );
			add_filter( 'Eight_Day_Week\Articles\article_columns', ns( 'filter_article_columns_article_images' ), 0 );
			add_filter( 'Eight_Day_Week\Articles\article_meta_article_status', ns( 'filter_article_meta_article_status' ), 10, 2 );

			add_action( 'Eight_Day_Week\Admin_Menu_Page\admin_menu', ns( 'admin_menu' ) );

			add_action( 'edw_sections_top', ns( 'output_bulk_article_status_editor' ) );
			add_action( 'wp_ajax_pp-bulk-edit-article-statuses', ns( 'bulk_edit_article_statuses_ajax' ) );
		}
	);
}

/**
 * Register the Article Status taxonomy
 */
function register_taxonomy() {
	$labels = array(
		'name'          => __( 'Article Status', 'eight-day-week-print-workflow' ),
		'singular_name' => __( 'Article Statuses', 'eight-day-week-print-workflow' ),
		'all_items'     => __( 'Article Statuses', 'eight-day-week-print-workflow' ),
		'edit_item'     => __( 'Edit Article Status', 'eight-day-week-print-workflow' ),
		'view_item'     => __( 'View Article Status', 'eight-day-week-print-workflow' ),
		'update_item'   => __( 'Update Article Status', 'eight-day-week-print-workflow' ),
		'add_new_item'  => __( 'Add New Article Status', 'eight-day-week-print-workflow' ),
		'new_item_name' => __( 'New Article Status', 'eight-day-week-print-workflow' ),
		'search_items'  => __( 'Search Article Status', 'eight-day-week-print-workflow' ),
		'not_found'     => __( 'No Article Statuses found', 'eight-day-week-print-workflow' ),
	);

	$args = array(
		'labels'             => $labels,
		// only for the backend
		'public'             => false,
		// don't show under posts menu
		// as of WP 4.4, setting show_ui to false yields "You are not allowed to manage these items."
		'show_ui'            => true,
		'show_in_menu'       => false,
		'show_in_nav_menus'  => false,
		'meta_box_cb'        => false,
		// don't show on posts
		'show_in_quick_edit' => false,
		// don't show on posts
		'meta_box_cb'        => false,
		// don't show on posts
		'show_admin_column'  => false,
		// don't allow front end querying
		'query_var'          => false,
		// don't allow front end rewriting
		'rewrite'            => false,
		'capabilities'       => array(
			'manage_terms' => 'manage_' . EDW_PRINT_ISSUE_CPT,
			'edit_terms'   => 'manage_' . EDW_PRINT_ISSUE_CPT,
			'delete_terms' => 'manage_' . EDW_PRINT_ISSUE_CPT,
			'assign_terms' => 'create_' . EDW_PRINT_ISSUE_CPT,
		),
	);

	\register_taxonomy( EDW_ARTICLE_STATUS_TAX, 'post', $args );
}

function admin_menu() {
	// Note that the &amp; is important.
	// It is required to stay in line with how edit-tags.php
	// builds the global $submenu_file, which is checked against
	// to determine whether or not the active submenu gets a "current" CSS class.
	add_submenu_page(
		EDW_ADMIN_MENU_SLUG,
		__( 'Article Statuses', 'eight-day-week-print-workflow' ),
		__( 'Article Statuses', 'eight-day-week-print-workflow' ),
		'manage_' . EDW_PRINT_ISSUE_CPT,
		'edit-tags.php?taxonomy=' . EDW_ARTICLE_STATUS_TAX . '&amp;post_type=print-issue'
	);
}

/**
 * Add Article Status to print issue table columns
 *
 * @param $columns array Incoming print issue table columns
 *
 * @return array Modified columns
 */
function filter_article_columns_article_status( $columns ) {
	$status = array(
		'post_status' => __( 'Article Status', 'eight-day-week' ),
	);

			$title_offset = array_search( 'title', array_keys( $columns ) );
	if ( $title_offset ) {
		$end     = $status + array_slice( $columns, $title_offset + 1, null );
		$columns = array_slice( $columns, 0, $title_offset + 1 ) + $end;
	} else {
		$columns += $status;
	}

	return $columns;
}

/**
 * Add Images to print issue table columns
 *
 * @param $columns array Incoming print issue table columns
 *
 * @return array Modified columns
 */
function filter_article_columns_article_images( $columns ) {
	$status = array(
		'post_img_num' => __( 'Images', 'eight-day-week' ),
	);

	/* put after char_count when available */
	$title_offset = array_search( 'char_count', array_keys( $columns ) );
	if ( $title_offset ) {
		$end     = $status + array_slice( $columns, $title_offset + 1, null );
		$columns = array_slice( $columns, 0, $title_offset + 1 ) + $end;
	} else {
		$columns += $status;
	}

	return $columns;
}

/**
 * Adds article status metadata to the article rubric
 *
 * @param $incoming
 * @param $item
 *
 * @return string The article status, or the $incoming value
 */
function filter_article_meta_article_status( $incoming, $item ) {
	$article_status = get_the_terms( $item->ID, EDW_ARTICLE_STATUS_TAX );

	if ( $article_status && ! is_wp_error( $article_status ) ) {
		return $article_status[0]->name;
	}

	return $incoming;
}

/**
 * Gets an array of article status taxonomy terms, indexed by term ID
 *
 * @return array Indexed article status terms
 */
function get_indexed_article_statuses() {
	$article_statuses = get_terms(
		EDW_ARTICLE_STATUS_TAX,
		array(
			'hide_empty' => false,

		)
	);
	if ( ! $article_statuses || is_wp_error( $article_statuses ) ) {
		return array();
	}

	$actions = array();
	foreach ( $article_statuses as $article_status ) {
		$actions[ $article_status->term_id ] = $article_status->name;
	}

	return $actions;
}

/**
 * Outputs the article status buttons/select box
 * Only does so for an print-issue-edit-capable user
 */
function output_bulk_article_status_editor() {
	if ( ! User\current_user_can_edit_print_issue() ) {
		return;
	}
	$statuses = get_indexed_article_statuses();
	?>
	<div class="alignleft actions bulkactions">
		<h3><label for="bulk-action-selector-top"><?php esc_html_e( 'Article Status', 'eight-day-week-print-workflow' ); ?></label></h3>
		<select id="bulk-action-selector-top">
			<?php if ( $statuses ) : ?>
				<?php foreach ( $statuses as $id => $status ) : ?>
					<option value="<?php echo absint( $id ); ?>"><?php echo esc_html( $status ); ?></option>
				<?php endforeach; ?>
			<?php endif; ?>
		</select>
		<button id="bulk-edit-article-status-submit" class="button button-secondary"><?php esc_html_e( 'Apply to checked', 'eight-day-week-print-workflow' ); ?></button>
		<button id="bulk-edit-article-status-apply-all" class="button button-secondary"><?php esc_html_e( 'Apply to all', 'eight-day-week-print-workflow' ); ?></button>
	</div>
	<?php
}

/**
 * Handles a request to edit articles' status
 *
 * @todo refactor into pattern so that json functions are higher up, and the handler throws exceptions insteads
 */
function bulk_edit_article_statuses_ajax() {

	Core\check_elevated_ajax_referer();

	$term_id     = absint( $_POST['status'] );
	$article_ids = $_POST['checked_articles'];

	// sanitize - only allow comma delimited integers
	if ( ! ctype_digit( str_replace( ',', '', $article_ids ) ) ) {
		\Eight_Day_Week\Core\send_json_error( array( 'message' => __( 'Invalid article IDs specified in the request.', 'eight-day-week-print-workflow' ) ) );
	}

	$article_ids = explode( ',', $article_ids );

	try {
		bulk_edit_article_statuses( $term_id, $article_ids );
	} catch ( \Exception $e ) {
		\Eight_Day_Week\Core\send_json_error( array( 'message' => $e->getMessage() ) );
	}

	\Eight_Day_Week\Core\send_json_success();
}

/**
 * Sets the designated term on all designated articles
 * Note it does *not* append terms, it replaces them
 *
 * @param string $status_term_id The term ID to add to the articles
 * @param array  $article_ids Article IDs to which to add the term
 *
 * @throws \Exception WP Error message if invalid tax specified
 */
function bulk_edit_article_statuses( $status_term_id, $article_ids ) {
	foreach ( (array) $article_ids as $article_id ) {
		$result = wp_set_object_terms( absint( $article_id ), absint( $status_term_id ), EDW_ARTICLE_STATUS_TAX, false );
		if ( is_wp_error( $result ) ) {
			throw new \Exception( $result->get_error_message() );
		}
	}
}
