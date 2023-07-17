<?php
/**
 * Displays the number of articles in a print issue on the print issue rubric
 *
 * @package Eight_Day_Week
 */

namespace Eight_Day_Week\Plugins\Article_Count;

use Eight_Day_Week\Core as Core;

/**
 * Default setup routine
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

			add_filter( 'manage_edit-' . EDW_PRINT_ISSUE_CPT . '_columns', ns( 'print_issue_cpt_columns' ) );
			add_action( 'manage_' . EDW_PRINT_ISSUE_CPT . '_posts_custom_column', ns( 'populate_print_issue_cpt_columns' ), 10, 2 );
			add_action( 'save_print_issue', ns( 'bust_num_articles_cache' ) );
		}
	);
}

/**
 * Adds the Article Count column to the print issue rubric
 *
 * @param array $custom Existing columns.
 *
 * @return array modified columns
 */
function print_issue_cpt_columns( $custom ) {
	$custom['num_articles'] = __( '# of Articles', 'eight-day-week-print-workflow' );

	return $custom;
}

/**
 * Adds the article byline metadata to the article rubric
 *
 * Caches the result with wp_cache_*
 *
 * @param string $colname The name of the current column.
 * @param int    $post_id The ID of the current post.
 */
function populate_print_issue_cpt_columns( $colname, $post_id ) {
	if ( 'num_articles' === $colname ) {

		$cache_key     = get_num_articles_cache_key( $post_id );
		$article_count = wp_cache_get( $cache_key );

		if ( false === $article_count ) {

			$article_count = get_num_articles( $post_id );

			wp_cache_set( $cache_key, $article_count );
		}

		echo absint( $article_count );
	}
}

/**
 * Gets the total # of articles in a print issue
 * These span across all the print issue's sections
 *
 * @param int $post_id The current post ID.
 *
 * @return int The number of articles in a print issue
 */
function get_num_articles( $post_id ) {
	$sections = get_post_meta( $post_id, 'sections', true );

	// Sanitize - only allow comma delimited integers.
	if ( ! ctype_digit( str_replace( ',', '', $sections ) ) ) {
		return 0;
	}

	$sections = explode( ',', $sections );

	$article_count = array();

	if ( count( $sections ) > 0 ) {
		foreach ( $sections as $section_id ) {
			$articles = get_post_meta( $section_id, 'articles', true );
			if ( $articles ) {
				$article_list                 = explode( ',', $articles );
				$article_count[ $section_id ] = count( $article_list );
			} else {
				$article_count[ $section_id ] = 0;
			}
		}
	}

	return array_sum( $article_count );
}

/**
 * Builds the cache key for the article count
 *
 * @param int $post_id The current post ID.
 *
 * @return string The cache key specific to the specified post
 */
function get_num_articles_cache_key( $post_id ) {
	return EDW_PRINT_ISSUE_CPT . '-' . $post_id . '-num-articles';
}

/**
 * Clears the article count cache when a print issue is saved
 *
 * @param int $post_id The post ID being saved.
 */
function bust_num_articles_cache( $post_id ) {
	wp_cache_delete( get_num_articles_cache_key( $post_id ) );
}
