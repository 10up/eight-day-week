<?php
/**
 * Handles the articles functionality
 *
 * @package Eight_Day_Week
 */

namespace Eight_Day_Week\Articles;

use Eight_Day_Week\Articles\AL_Table;
use Eight_Day_Week\User_Roles as User;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

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

	add_action( 'edw_section_metabox', ns( 'articles_metabox_output' ), 10, 1 );

	add_action( 'wp_ajax_pp-get-articles', ns( 'get_articles_ajax' ) );
	add_action( 'wp_ajax_pp-get-article-row', ns( 'get_article_row_ajax' ) );
	add_action( 'save_print_issue', ns( 'save_section_articles' ), 10, 1 );

	// Load publish date.
	// Use -1 priority to ensure its loaded before 3rd party plugins.
	add_filter( __NAMESPACE__ . '\article_columns', ns( 'filter_article_columns_date' ), 1 );
}

/**
 * Outputs an AL_Table + inputs to add an article
 *
 * @param int $section_id The current section ID.
 */
function articles_metabox_output( $section_id ) {
	$articles = get_existing_articles( $section_id );
	?>
	<div class="pi-articles">
		<?php existing_articles( $articles ); ?>
	</div>

	<?php
	if ( ! User\current_user_can_edit_print_issue() ) {
		return;
	}
	?>

	<button
		class="button button-secondary pi-article-add"><?php esc_html_e( 'Add Article', 'eight-day-week-print-workflow' ); ?>
	</button>
	<div class="pi-article-add-info">
		<input
			type="text"
			name="pi-article-title"
			class="pi-article-title"
			placeholder="<?php esc_attr_e( 'Search for articles by title', 'eight-day-week-print-workflow' ); ?>"
			/>
		<p class="pi-error-msg" id="pi-article-add-error"></p>
	</div>
	<input
		type="hidden"
		name="pi-article-ids[<?php echo absint( $section_id ); ?>]"
		class="pi-article-ids"
		value="<?php echo esc_attr( implode( ',', $articles ) ); ?>"
		/>
	<?php
}

/**
 * Displays an AL_Table for provided articles
 *
 * @param \WP_Post[] $articles Set of posts.
 */
function existing_articles( $articles ) {

	if ( is_array( $articles ) ) {

		$articles = array_map( 'absint', $articles );

		$table = new AL_Table( $articles );
		$table->prepare_items();
		$table->display();
	}
}

/**
 * Gets articles from a given section
 *
 * @param int $section_id Section from which to pull articles.
 *
 * @return \WP_Post[] Posts in a section
 */
function get_existing_articles( $section_id ) {
	$articles = get_post_meta( $section_id, 'articles', true );
	if ( ! $articles ) {
		return array();
	}

	return explode( ',', $articles );
}

/**
 * Handler for an ajax request to get articles by title search
 */
function get_articles_ajax() {
	if ( ! check_ajax_referer( EDW_AJAX_NONCE_SLUG, false, false ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Invalid nonce.', 'eight-day-week-print-workflow' ),
			),
			403
		);
	}

	$title = isset( $_GET['title'] ) ? sanitize_text_field( wp_unslash( $_GET['title'] ) ) : false;

	try {
		$articles = get_articles_autocomplete( $title );
	} catch ( \Exception $e ) {
		\Eight_Day_Week\Core\send_json_error( array( 'message' => $e->getMessage() ) );
	}

	\Eight_Day_Week\Core\send_json_success( array( 'articles' => $articles ) );
}

/**
 * Gets an autocomplete-compatible set of posts
 *
 * @param string $title Title with which to search.
 *
 * @return array Set of autocomplete-compatible posts
 */
function get_articles_autocomplete( $title ) {
	$articles     = get_articles( $title );
	$autocomplete = array();
	foreach ( $articles as $article ) {
		$autocomplete[] = array(
			'label' => $article->post_title,
			'value' => $article->ID,
		);
	}
	return $autocomplete;
}

/**
 * Retrieves articles based on a given title.
 *
 * @param string $title The title to search for.
 * @throws \Exception If the title is empty or not valid.
 * @return array The array of matching articles.
 */
function get_articles( $title ) {
	if ( ! $title ) {
		throw new \Exception( esc_html__( 'Please enter a valid/non-empty title.', 'eight-day-week-print-workflow' ) );
	}

	$post_types = apply_filters( __NAMESPACE__ . '\\post_types', array( 'post' ) );

	$args = array(
		'search_by_title'        => sanitize_text_field( $title ),
		'posts_per_page'         => 20,
		'post_type'              => $post_types,
		'post_status'            => 'any',
		'order'                  => 'DESC',
		'orderby'                => 'post_date',
		'update_post_meta_cache' => false,
		'no_found_rows'          => true,
	);

	add_filter( 'posts_where', __NAMESPACE__ . '\\title_filter', 10, 2 );
	$articles = new \WP_Query( $args );
	remove_filter( 'posts_where', __NAMESPACE__ . '\\title_filter', 10 );

	foreach ( $articles->posts as $key => $post ) {
		// Exclude posts the user cannot read.
		if ( ! current_user_can( 'read_post', $post->ID ) ) {
			unset( $articles->posts[ $key ] );
		}
	}

	if ( ! $articles->posts ) {
		throw new \Exception( esc_html__( 'No matching articles found.', 'eight-day-week-print-workflow' ) );
	}

	return $articles->posts;
}

/**
 * Filters the WP_Query to search by wildcard title
 *
 * @param string    $where existing WHERE SQL clause.
 * @param \WP_Query $wp_query The current query.
 *
 * @return string Modified WHERE SQL clause
 */
function title_filter( $where, $wp_query ) {
	global $wpdb;
	$title = $wp_query->get( 'search_by_title' );
	if ( $title ) {
		/*using the esc_like() in here instead of other esc_sql()*/
		$title  = $wpdb->esc_like( $title );
		$title  = ' \'%' . $title . '%\'';
		$where .= ' AND ' . $wpdb->posts . '.post_title LIKE ' . $title;
	}

	return $where;
}

/**
 * Handles a request for the HTML of a new, post-specific AL_Table row
 */
function get_article_row_ajax() {
	check_ajax_referer( EDW_AJAX_NONCE_SLUG, false, false );

	$article_id = isset( $_GET['article_id'] ) ? absint( wp_unslash( $_GET['article_id'] ) ) : false;

	if ( ! $article_id || ! get_post( $article_id ) || ! current_user_can( 'read_post', $article_id ) ) {
		\Eight_Day_Week\Core\send_json_error( array( 'message' => __( 'Invalid article ID.', 'eight-day-week-print-workflow' ) ) );
	}

	$article_table = new AL_Table( array( $article_id ) );
	$article_table->prepare_items();

	$article_data       = new \stdClass();
	$article_data->data = $article_table->get_data( $article_table->items[0] );
	$article_data->html = $article_table->get_single_row( $article_table->items[0] );

	\Eight_Day_Week\Core\send_json_success( $article_data );
}

/**
 * Saves articles to a section
 *
 * @param int $post_id The current post ID.
 */
function save_section_articles( $post_id ) {

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified in save_print_issues().
	if ( ! isset( $_POST['pi-article-ids'] ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified in save_print_issues().
	$article_ids_sets = map_deep( wp_unslash( $_POST['pi-article-ids'] ), 'sanitize_text_field' );

	if ( ! is_array( $article_ids_sets ) ) {
		return;
	}

	// Remove print issue template.
	if ( isset( $article_ids_sets[ $post_id ] ) ) {
		unset( $article_ids_sets[ $post_id ] );
	}

	foreach ( $article_ids_sets as $section_id => $article_ids ) {
		$section_id = absint( $section_id );

		// Validate section.
		$section = get_post( $section_id );
		if ( ! $section_id || ! $section ) {
			continue;
		}

		// Sanitize - only allow comma delimited integers.
		if ( ! empty( $article_ids ) && ! ctype_digit( str_replace( ',', '', $article_ids ) ) ) {
			continue;
		}

		// Remove dup IDs.
		$article_ids = explode( ',', $article_ids );
		$article_ids = array_unique( $article_ids );

		update_post_meta( $section_id, 'articles', implode( ',', $article_ids ) );
	}
}

/**
 * Adds the publish date to the print issue rubric
 *
 * @param array $columns Incoming columns.
 *
 * @return array Modified columns
 */
function filter_article_columns_date( $columns ) {
	$columns['date'] = __( 'Publish Date', 'eight-day-week-print-workflow' );
	return $columns;
}
