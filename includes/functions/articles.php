<?php
/**
 * Handles the articles functionality
 *
 * @package Eight_Day_Week
 */

namespace Eight_Day_Week\Articles;

use Eight_Day_Week\Core as Core;
use Eight_Day_Week\User_Roles as User;

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

if ( ! class_exists( '\WP_Posts_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php';
}

/**
 * Class AL_Table
 *
 * @package Eight_Day_Week\Articles
 *
 * Article List Table extending WP_Posts_List_Table to display a print issue's articles
 */
class AL_Table extends \WP_Posts_List_Table {

	/**
	 * Article IDs
	 *
	 * @var int[] Current set of article IDs
	 */
	public $article_ids;

	/**
	 * Sets object properties and calls parent constructor
	 *
	 * @param int[] $article_ids IDs of articles to display.
	 */
	public function __construct( $article_ids ) {
		$this->article_ids = $article_ids;
		parent::__construct(
			array(
				'screen' => EDW_PRINT_ISSUE_CPT,
			)
		);
	}

	/**
	 * Outputs the fallback meta for a column. called when a method on the class
	 * doesn't exist that matches the column_name, i.e for $column_name = 'foo';
	 * and $this->column_foo and $this->_column_foo aren't valid methods.
	 *
	 * @param mixed  $item The item from which to retrieve the value.
	 * @param string $column_name The name of the column.
	 * @return mixed The value of the specified column from the item.
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			default:
				if ( ! is_object( $item ) ) {
					return '';
				}

				if ( property_exists( $item, $column_name ) ) {
					return $item->$column_name;
				}

				$filtered = apply_filters( __NAMESPACE__ . '\article_meta_' . $column_name, false, $item, $column_name );
				if ( $filtered ) {
					return $filtered;
				}

				// Try post meta.
				$meta = get_post_meta( $item->ID, $column_name, true );
				if ( $meta ) {
					return $meta;
				}

				return '';
		}
	}

	/**
	 * Gets the columns for the table
	 * Provides a filter for 3rd party columns
	 *
	 * @return array Columns
	 */
	public function get_columns() {
		return apply_filters(
			__NAMESPACE__ . '\article_columns',
			array(
				'cb'    => '<input type="checkbox" />',
				'title' => _x( 'Article', 'eight-day-week-print-workflow' ),
			)
		);
	}

	/**
	 * Before displaying items, prep them!
	 *
	 * Gets the columns (and sets the internal headers property)
	 * Gets the \WP_Post for each article ID in the current object's set
	 */
	public function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = array();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		foreach ( $this->article_ids as $id ) {
			$post = get_post( $id );

			$post_img_num   = 0;
			$gallery_images = get_post_galleries( $post, true );
			if ( ! empty( $gallery_images ) ) {
				foreach ( $gallery_images as $single ) {
					$post->post_content .= $single;
				}
			}
			$post_img_num = (int) preg_match_all( '/<img[^>]*>/', $post->post_content, $matches );
			if ( has_post_thumbnail( $post->ID ) ) {
				++$post_img_num;
			}
			$post->post_img_num = $post_img_num;

			$this->items[] = $post;
		}
	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @param object $item The current item.
	 * @param int    $level The current item's level (parent relationship level).
	 */
	public function single_row( $item, $level = 0 ) {
		if ( property_exists( $item, 'ID' ) ) {
			echo '<tr data-article-id="' . absint( $item->ID ) . '">';
		} else {
			echo '<tr>';
		}
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Overrides parent method so no bulk actions appear
	 *
	 * @return array Empty array
	 */
	public function get_bulk_actions() {
		return array();
	}

	/**
	 * Override parent method to just check for the emptines of the items property
	 *
	 * @return bool Whether or not the table has items
	 */
	public function has_items() {
		return ! empty( $this->items );
	}


	/**
	 * Loops through the given array of posts and calls the single_row() method for each post.
	 *
	 * @param array $posts An array of posts to loop through and display.
	 * @param int   $level The level of the rows to display.
	 */
	public function display_rows( $posts = array(), $level = 0 ) {
		foreach ( $this->items as $article ) {
			$this->single_row( $article );
		}
	}

	/**
	 * Display a tablenav.
	 *
	 * @param string $which The position of the tablenav (top or bottom).
	 */
	public function display_tablenav( $which ) {
	}

	/**
	 * Display the article title
	 * Override the parent method to be moar simpler
	 *
	 * @param \WP_Post $item The current post.
	 * @param string   $classes The posts's css classes.
	 * @param string   $data The posts's data-attributes.
	 * @param string   $primary (shrug) Unused here, just keeping in line with parent class.
	 *
	 * @return string
	 */
	public function _column_title( $item, $classes = '', $data = '', $primary = false ) {
		$html  = '<td class="' . esc_attr( $classes ) . ' page-title" ' . esc_attr( $data ) . '>';
		$html .= $this->column_title( $item );
		$html .= '</td>';
		return $html;
	}

	/**
	 * Gets the checkbox for each row
	 *
	 * @param \WP_Post $item The current post.
	 *
	 * @return string HTML for checkbox
	 */
	public function column_cb( $item ) {
		return '<input type="checkbox" class="article-status" name="article-status[]" value="' . ( isset( $item->ID ) ? absint( $item->ID ) : '' ) . '" />';
	}

	/**
	 * Gets the post title + actions for the post
	 *
	 * @param \WP_Post $item The current post.
	 *
	 * @return string The posts's title
	 */
	public function column_title( $item ) {

		if ( current_user_can( 'edit_post', $item->ID ) ) {
			$title = '<a class="pi-article-title" href="' . esc_url( get_edit_post_link( $item->ID ) ) .
					'">' . esc_html( get_the_title( $item->ID ) ) . '</a>';
		} else {
			$title = esc_html( get_the_title( $item->ID ) );
		}

		$title .= '<a class="pi-article-view" target="_blank" href="' .
					esc_url( get_permalink( $item->ID ) ) . '">' . __( 'View', 'eight-day-week-print-workflow' ) . '</a>';

		// Don't give remove link to print prod users.
		if ( User\current_user_can_edit_print_issue() ) {
			$title .= '<a class="pi-article-remove" href="javascript:;" data-article-id="' .
						absint( $item->ID ) . '">Remove</a>';
		}

		return $title;
	}

	/**
	 * Gets the table properties of a post row
	 *
	 * @param \WP_Post $item The current post.
	 *
	 * @return \stdClass Object representing the post's tabular data
	 */
	public function get_data( $item ) {
		$data = new \stdClass();
		foreach ( (array) $this->get_columns() as $key => $title ) {
			$default = $this->column_default( $item, $key );

			// Using object buffering because some WP_Posts_List_Table methods output instead of return.
			ob_start();
			if ( method_exists( $this, "_column_$key" ) ) {
				$method = "_column_$key";
				echo esc_html( $this->$method( $item ) );
			} elseif ( ! property_exists( $this, $key ) && method_exists( $this, "column_$key" ) ) {
				$method = "column_$key";
				echo esc_html( $this->$method( $item ) );
			} elseif ( $default ) {
				echo esc_html( $default );
			}
			$data->$key = ob_get_clean();
		}
		return $data;
	}

	/**
	 * Display rows if there are items to show
	 * Overrides parent method so there's no placeholder
	 */
	public function display_rows_or_placeholder() {
		if ( $this->has_items() ) {
			$this->display_rows();
		}
	}

	/**
	 * Returns html for a single table row
	 *
	 * @param \WP_Post $item The current post.
	 *
	 * @return string THe post's table row
	 */
	public function get_single_row( $item ) {
		ob_start();
		$this->single_row( $item );
		return ob_get_clean();
	}

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
		throw new \Exception( __( 'Please enter a valid/non-empty title.', 'eight-day-week-print-workflow' ) );
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
	remove_filter( 'posts_where', __NAMESPACE__ . '\\title_filter', 10, 2 );

	if ( ! $articles->posts ) {
		throw new \Exception( __( 'No matching articles found.', 'eight-day-week-print-workflow' ) );
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

	\Eight_Day_Week\Core\check_ajax_referer();

	$article_id    = isset( $_GET['article_id'] ) ? absint( $_GET['article_id'] ) : false;
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

	if ( ! isset( $_POST['pi-article-ids'] ) ) {
		return;
	}

	$article_ids_sets = wp_unslash( array_map( 'intval', $_POST['pi-article-ids'] ) );
	if ( ! is_array( $article_ids_sets ) ) {
		return;
	}

	// Remove print issue template.
	if ( isset( $article_ids_sets[ $post_id ] ) ) {
		unset( $article_ids_sets[ $post_id ] );
	}

	foreach ( $article_ids_sets as $section_id => $article_ids ) {
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

		update_post_meta( absint( $section_id ), 'articles', implode( ',', $article_ids ) );
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
