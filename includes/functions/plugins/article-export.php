<?php
/**
 * Handles the article export functionality
 *
 * @package Eight_Day_Week
 */

namespace Eight_Day_Week\Plugins\Article_Export;

use Eight_Day_Week\Core;
use Eight_Day_Week\Plugins\Article_Export\Article_Zip_Factory;
use Eight_Day_Week\Plugins\Article_Export\File;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Default setup routine
 *
 * @uses add_action()
 * @uses do_action()
 *
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
			add_action( 'edw_sections_top', ns( 'output_article_export_buttons' ), 11 );
			add_action( 'wp_ajax_pp-article-export', ns( 'export_articles' ) );

			add_action( 'wp_ajax_pp-article-export-update', ns( 'article_status_response' ) );

			// Add export status column.
			add_filter( 'Eight_Day_Week\Articles\article_columns', ns( 'article_export_status_column' ), 10 );

			// Retrieve export status + build string for output.
			add_filter( 'Eight_Day_Week\Articles\article_meta_export_status', ns( 'filter_export_status' ), 10, 2 );

			add_filter( __NAMESPACE__ . '\short_circuit_article_export_files', ns( 'maybe_export_article_attachments' ), 10, 2 );

			add_filter( __NAMESPACE__ . '\xml_filename', ns( 'filter_xml_filename_author' ), 10, 3 );
		}
	);
}

/**
 * Output export buttons!
 */
function output_article_export_buttons() {
	?>
	<div class="alignleft actions bulkactions article-export-buttons">
		<h3><?php esc_html_e( 'Export for InDesign', 'eight-day-week-print-workflow' ); ?></h3>
		<button id="article-export-checked" class="button button-secondary"><?php esc_html_e( 'Export checked', 'eight-day-week-print-workflow' ); ?></button>
		<button id="article-export-all" class="button button-secondary"><?php esc_html_e( 'Export all', 'eight-day-week-print-workflow' ); ?></button>
	</div>
	<?php
}

/**
 * Big ol' export article controller
 */
function export_articles() {

	Core\check_ajax_referer();

	if ( ! isset( $_POST['article_ids'] ) ) {
		die( esc_html__( 'No article IDs sent', 'eight-day-week-print-workflow' ) );
	}

	$article_ids = sanitize_text_field( wp_unslash( $_POST['article_ids'] ) );

	$print_issue_id    = isset( $_POST['print_issue_id'] ) ? absint( $_POST['print_issue_id'] ) : false;
	$print_issue_title = isset( $_POST['print_issue_title'] ) ? sanitize_text_field( wp_unslash( $_POST['print_issue_title'] ) ) : false;

	// Sanitize - only allow comma delimited integers.
	if ( ! ctype_digit( str_replace( ',', '', $article_ids ) ) ) {
		die( esc_html__( 'Invalid article IDs specified in the request.', 'eight-day-week-print-workflow' ) );
	}

	$article_ids = explode( ',', $article_ids );

	do_action( __NAMESPACE__ . '\before_export_articles', $article_ids, $print_issue_id, $print_issue_title ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

	try {
		$factory = new Article_Zip_Factory( $article_ids, $print_issue_id, $print_issue_title );
	} catch ( \Exception $e ) {
		Core\send_json_error( $e->getMessage() );
		return;
	}

	try {
		$factory->output_zip();
	} catch ( \Exception $e ) {
		Core\send_json_error( $e->getMessage() );
	}

	do_action( __NAMESPACE__ . '\_after_export_articles', $article_ids, $print_issue_id, $print_issue_title ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
}

/**
 * Adds the 'Export Status' column to the given array of columns.
 *
 * @param array $columns The array of columns to add the 'Export Status' column to.
 * @return array The modified array of columns.
 */
function article_export_status_column( $columns ) {
	$columns['export_status'] = __( 'Export Status', 'eight-day-week-print-workflow' );
	return $columns;
}

/**
 * Updates post meta with export status and retrieves the built status string
 *
 * @param array $article_ids Post IDs.
 *
 * @uses set_export_status
 * @uses get_export_status
 *
 * @return string The export status
 */
function set_and_get_export_status( $article_ids ) {
	set_export_status( $article_ids );
	return get_export_status( reset( $article_ids ) );
}

/**
 * Manages the ajax response for setting/getting article export status
 */
function article_status_response() {
	Core\check_ajax_referer();
	try {
		$export_status = article_status_handler();
		Core\send_json_success( array( 'export_status' => $export_status ) );
	} catch ( \Exception $e ) {
		Core\send_json_error();
	}
}

/**
 * Pulls data from the http request and sets/gets article export status
 *
 * @uses set_and_get_export_status
 *
 * @return string The export status
 * @throws \Exception Points of failure in the process.
 */
function article_status_handler() {

	if ( ! isset( $_POST['article_ids'] ) ) {
		throw new \Exception( esc_html__( 'No article IDs sent', 'eight-day-week-print-workflow' ) );
	}

	$article_ids = sanitize_text_field( wp_unslash( $_POST['article_ids'] ) );

	// Sanitize - only allow comma delimited integers.
	if ( ! ctype_digit( str_replace( ',', '', $article_ids ) ) ) {
		throw new \Exception( esc_html__( 'Invalid article IDs specified in the request.', 'eight-day-week-print-workflow' ) );
	}

	$article_ids = explode( ',', $article_ids );

	return set_and_get_export_status( $article_ids );
}

/**
 * Updates each exported article with meta
 * about who last exported it, and when
 *
 * @param array $article_ids IDs of articles that were exported.
 */
function set_export_status( $article_ids ) {
	$article_ids = array_map( 'absint', $article_ids );
	foreach ( $article_ids as $article_id ) {
		update_post_meta( $article_id, 'export_status_timestamp', time() );
		update_post_meta( $article_id, 'export_status_user_id', get_current_user_id() );
	}
}

/**
 * Gets the article's export status
 *
 * @param bool     $incoming The incoming meta value (false by default).
 * @param \WP_Post $article The current post.
 *
 * @return string The article's export status, or the incoming value
 */
function filter_export_status( $incoming, $article ) {
	$status = get_export_status( $article->ID );
	return $status ? $status : $incoming;
}

/**
 * Builds the export status string
 *
 * @param int $article_id The article post ID.
 *
 * @return string The export status
 */
function get_export_status( $article_id ) {

	$timestamp = get_post_meta( $article_id, 'export_status_timestamp', true );

	if ( ! $timestamp ) {
		return false;
	}

	$user_id = get_post_meta( $article_id, 'export_status_user_id', true );
	$user    = get_user_by( 'id', absint( $user_id ) );

	$zone = new \DateTimeZone( Core\get_timezone() );

	$export_datetime = new \DateTime( 'now', $zone );
	$export_datetime->setTimestamp( $timestamp );

	$now = new \DateTime( 'now', $zone );

	$not_today = $export_datetime->diff( $now )->format( '%R%a' );

	if ( ! $not_today ) {
		/* translators: 1: date when the export happened, 2: user's display name */
		$export_status = sprintf( esc_html__( 'Exported on %1$s by %2$s', 'eight-day-week-print-workflow' ), $export_datetime->format( get_option( 'date_format' ) ), $user->display_name );
	} else {
		/* translators: 1: time when the export happened, 2: user's display name */
		$export_status = sprintf( esc_html__( 'Exported at %1$s by %2$s', 'eight-day-week-print-workflow' ), $export_datetime->format( _x( 'g:ia', 'Format for article export timestamp', 'eight-day-week' ) ), $user->display_name );
	}

	return $export_status;
}

/**
 * Returns a set of Files if there are documents attached to provided article
 *
 * @param mixed|bool $incoming  The previous filter value.
 * @param \WP_Post   $article The post to retrieve attachments from.
 *
 * @return File[] Set of Attachment Files, or $incoming value
 */
function maybe_export_article_attachments( $incoming, $article ) {

	add_filter( 'posts_where', __NAMESPACE__ . '\filter_article_export_attachments_where' );

	$attachments = new \WP_Query(
		array(
			'post_type'              => 'attachment',
			'post_parent'            => absint( $article->ID ),
			'posts_per_page'         => 20,
			'post_status'            => 'inherit',
			// Meta will be used, so only disable term cache.
			'update_post_term_cache' => false,
		)
	);

	remove_filter( 'posts_where', __NAMESPACE__ . '\filter_article_export_attachments_where' );

	if ( $attachments->have_posts() ) {
		$incoming = get_document_files( $attachments->posts );
	}

	return $incoming;
}

/**
 * Filter the WHERE clause for the article export attachments query.
 *
 * This function appends a condition to the WHERE clause of the query to exclude
 * attachments with a post_mime_type starting with 'image'.
 *
 * @param string $where The original WHERE clause of the query.
 * @return string The modified WHERE clause with the additional condition.
 */
function filter_article_export_attachments_where( $where ) {
	return $where . " AND post_mime_type NOT LIKE 'image%'";
}

/**
 * Gets a set of Files[] from attachments
 *
 * @param \WP_Post[] $attachments Attachments of a parent article.
 *
 * @return File[] Attachment files
 */
function get_document_files( $attachments ) {
	return array_map(
		static function ( $attachment ) {
			return get_attachment_file( $attachment );
		},
		$attachments
	);
}

/**
 * Retrieves the attachment file for a given attachment.
 *
 * @param mixed $attachment The attachment object or ID.
 * @throws \Exception If the attachment file cannot be retrieved.
 * @return File The attachment file as a File object.
 */
function get_attachment_file( $attachment ) {
	return new File( get_attached_file( $attachment->ID ) );
}

/**
 * Adds a headline (title) element to the root of the XML document
 *
 * @param array    $elements Incoming elements.
 * @param \WP_Post $article The current post.
 *
 * @return array Modified elements
 */
function filter_xml_elements_headline( $elements, $article ) {
	$elements['headline'] = get_the_title( $article );
	return $elements;
}

/**
 * Prefix the xml filename with the post author's name
 *
 * @param string   $file_name The original file name.
 * @param \WP_Post $article The post.
 * @param object   $xml Custom DOMDocument wrapper object.
 *
 * @return string The modified filename
 */
function filter_xml_filename_author( $file_name, $article, $xml ) {
	$author = $xml->root_element->getAttribute( 'author' );
	if ( $author ) {
		$file_name = $author . '.' . $file_name;
	}

	return $file_name;
}
