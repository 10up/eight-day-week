<?php
/**
 * AL_Table
 *
 * @package Eight_Day_Week\Articles
 */

namespace Eight_Day_Week\Articles;

use Eight_Day_Week\User_Roles as User;

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

				$filtered = apply_filters( __NAMESPACE__ . '\article_meta_' . $column_name, false, $item, $column_name ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
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
			__NAMESPACE__ . '\article_columns', // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
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
	public function _column_title( $item, $classes = '', $data = '', $primary = false ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore, purposely overriding the parent method
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

		if ( current_user_can( 'read_post', $item->ID ) ) {
			$title .= '<a class="pi-article-view" target="_blank" href="' .
				esc_url( get_permalink( $item->ID ) ) . '">' . __( 'View', 'eight-day-week-print-workflow' ) . '</a>';
		}

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
