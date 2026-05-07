<?php
/**
 * Section
 *
 * @package Eight_Day_Week\Sections
 */

namespace Eight_Day_Week\Sections;

/**
 * Class Section
 *
 * @package Eight_Day_Week\Sections
 *
 * Class that represents a section object + offers utility functions for it
 *
 * @todo Refactor this (possibly trash it). Was just an experiment, really.
 */
class Section {

	/**
	 * Section post ID
	 *
	 * @var int The section's post ID
	 */
	public $ID;

	/**
	 * The section post object
	 *
	 * @var \WP_Post The section's post
	 */
	private $post_object;

	/**
	 * Ingests a section based on a post ID
	 *
	 * @param int $id The section's post ID.
	 */
	public function __construct( $id ) {
		$this->ID = absint( $id );
		$this->import_post();
		$this->import_post_info();
	}

	/**
	 * Sets the object's post_object property
	 *
	 * @throws \Exception Invalid post ID supplied.
	 */
	private function import_post() {
		$post = get_post( $this->ID );
		if ( ! $post instanceof \WP_Post ) {
			throw new \Exception( esc_html__( 'Invalid post ID supplied', 'eight-day-week-print-workflow' ) );
		}
		$this->post_object = $post;
	}

	/**
	 * Ingests the \WP_Post
	 * by duplicating its properties to this object's properties
	 *
	 * @todo Refactor away, unnecessary to have/perform
	 */
	private function import_post_info() {

		$info = $this->post_object;

		if ( is_object( $info ) ) {
			$info = get_object_vars( $info );
		}
		if ( is_array( $info ) ) {
			foreach ( $info as $key => $value ) {
				if ( ! empty( $key ) ) {
					$this->$key = $value;
				} elseif ( ! empty( $key ) && ! method_exists( $this, $key ) ) {
					$this->$key = $value;
				}
			}
		}
	}

	/**
	 * Updates the section
	 *
	 * @uses wp_update_post
	 * @todo Refactor away, just use wp_update_post
	 *
	 * @param mixed $args The arguments to update the post.
	 * @throws \Exception Failed to update section %d.
	 * @return mixed The result of updating the post.
	 */
	public function update( $args ) {
		$result = wp_update_post( $args );
		if ( $result ) {
			return $result;
		}
		/* translators: %d: The ID of the section that failed to update. */
		throw new \Exception( sprintf( esc_html__( 'Failed to update section %d', 'eight-day-week-print-workflow' ), absint( $this->ID ) ) );
	}

	/**
	 * Updates a section's title
	 *
	 * @param string $title The new title to set.
	 * @throws \Exception If the title is empty or invalid.
	 * @return void
	 */
	public function update_title( $title ) {
		if ( ! $title ) {
			throw new \Exception( esc_html__( 'Please supply a valid, non-empty title', 'eight-day-week-print-workflow' ) );
		}
		$title  = sanitize_text_field( $title );
		$args   = array(
			'ID'         => $this->ID,
			'post_title' => $title,
		);
		$result = $this->update( $args );
	}
}
