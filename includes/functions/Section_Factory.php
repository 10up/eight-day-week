<?php
/**
 * Section_Factory
 *
 * @package Eight_Day_Week
 */

namespace Eight_Day_Week\Sections;

use Eight_Day_Week\Core;
use Eight_Day_Week\Sections\Section;

use function Eight_Day_Week\Sections\get_sections;
use function Eight_Day_Week\Sections\set_print_issue_sections;

/**
 * Class Section_Factory
 *
 * @package Eight_Day_Week\Sections
 *
 * Factory that creates + updates sections
 *
 * @todo Refactor this (possibly trash it). Was just an experiment, really.
 */
class Section_Factory {

	/**
	 * Creates a section
	 *
	 * @param string $name The name of the section (title).
	 *
	 * @return int|Section|\WP_Error
	 */
	public static function create( $name ) {

		$info       = array(
			'post_title' => $name,
			'post_type'  => EDW_SECTION_CPT,
		);
		$section_id = wp_insert_post( $info );
		if ( $section_id ) {
			return new Section( $section_id );
		}

		return $section_id;
	}

	/**
	 * Assigns a section to a print issue.
	 *
	 * @param mixed $section The section to be assigned.
	 * @param mixed $print_issue The print issue to assign the section to.
	 * @return mixed The updated sections.
	 */
	public static function assign_to_print_issue( $section, $print_issue ) {
		$current_sections = get_sections( $print_issue->ID );
		$new_sections     = $current_sections ? $current_sections . ',' . $section->ID : $section->ID;
		set_print_issue_sections( $new_sections, $print_issue->ID );
		return $new_sections;
	}

	/**
	 * Creates an AJAX request handler for creating a section.
	 *
	 * @throws \Exception When the print issue ID is invalid or an exception is thrown during section creation.
	 */
	public static function create_ajax() {

		Core\check_elevated_ajax_referer();

		$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : false;
		if ( ! $name ) {
			Core\send_json_error( array( 'message' => __( 'Please enter a section name.', 'eight-day-week-print-workflow' ) ) );
		}

		$print_issue_id = isset( $_POST['print_issue_id'] ) ? absint( $_POST['print_issue_id'] ) : false;

		$print_issue = get_post( $print_issue_id );
		if ( ! $print_issue ) {
			throw new \Exception( 'Invalid print issue specified.' );
		}

		try {
			$section = self::create( $name );
		} catch ( \Exception $e ) {
			// Let the whoops message run its course.
			$section = null;
		}

		if ( $section instanceof Section ) {
			self::assign_to_print_issue( $section, $print_issue );
			Core\send_json_success( array( 'section_id' => $section->ID ) );
		}

		Core\send_json_error( array( 'message' => __( 'Whoops! Something went awry.', 'eight-day-week-print-workflow' ) ) );
	}

	/**
	 * Handles an ajax request to update a section's title
	 *
	 * @todo refactor to use exceptions and one json response vs pepper-style
	 */
	public static function update_title_ajax() {

		Core\check_elevated_ajax_referer();

		$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : false;
		if ( ! $title ) {
			Core\send_json_error( array( 'message' => __( 'Please enter a section name.', 'eight-day-week-print-workflow' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? sanitize_text_field( wp_unslash( $_POST['post_id'] ) ) : false;
		if ( ! $post_id ) {
			Core\send_json_error( array( 'message' => __( 'Whoops! This section appears to be invalid.', 'eight-day-week-print-workflow' ) ) );
		}
		try {
			self::update_title( $title, $post_id );
		} catch ( \Exception $e ) {
			Core\send_json_error( array( 'message' => $e->getMessage() ) );
		}
		Core\send_json_success();
	}

	/**
	 * Updates a section's title
	 *
	 * @param string $title The new title.
	 * @param int    $id The section ID.
	 */
	public static function update_title( $title, $id ) {
		$section = new Section( $id );
		$section->update_title( $title );
	}
}
