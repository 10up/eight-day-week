<?php
/**
 * Handles the article byline functionality
 *
 * @package Eight_Day_Week
 */

namespace Eight_Day_Week\Plugins\Article_Byline;

use Eight_Day_Week\Core as Core;

/**
 * Default setup routine
 *
 * @uses add_action
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

			// Use -1 priority to ensure its loaded before 3rd party plugins.
			add_filter( 'Eight_Day_Week\Articles\article_columns', ns( 'filter_article_columns_byline' ), 0 );
			add_filter( 'Eight_Day_Week\Articles\article_meta_byline', ns( 'filter_article_meta_byline' ), 10, 2 );
		}
	);
}

/**
 * Adds the Article Byline column to the article rubric
 *
 * @param array $columns Existing columns.
 *
 * @return array modified columns
 */
function filter_article_columns_byline( $columns ) {
	$columns['byline'] = _x( 'Byline', 'Label for multiple, comma separated authors', 'eight-day-week-print-workflow' );
	return $columns;
}

/**
 * Adds the article byline metadata to the article rubric
 *
 * @param string   $incoming Incoming meta value.
 * @param \WP_Post $article Current article.
 *
 * @return array|string
 */
function filter_article_meta_byline( $incoming, $article ) {
	return get_article_byline( $article );
}

/**
 * Gets a "byline" for a post's authors
 *
 * Byline = comma separated string
 *
 * @param \WP_Post $article Current article.
 *
 * @return bool|string Author byline string, false if no authors assigned
 */
function get_article_byline( $article ) {
	$byline = get_authors( $article->ID );

	if ( $byline && ! is_wp_error( $byline ) ) {
		$byline = implode( ', ', wp_list_pluck( $byline, 'display_name' ) );
	}

	return $byline;
}

/**
 * Get byline (authors) of a post
 * Dependency: Co-authors-plus plugin
 *
 * @param int $post_id The post ID to get authors from.
 *
 * @return array of \WP_Users
 */
function get_authors( $post_id ) {
	$authors = array();

	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}

	if ( function_exists( 'get_coauthors' ) ) {
		$coauthors = get_coauthors( $post_id );
		if ( ! is_wp_error( $coauthors ) ) {
			$authors = $coauthors;
		}
	} else {
		$author = get_user_by( 'id', get_post_field( 'post_author', $post_id ) );
		if ( $author ) {
			$authors = array( $author );
		}
	}

	return $authors;
}
