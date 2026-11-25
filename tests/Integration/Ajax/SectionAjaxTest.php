<?php
/**
 * Tests the AJAX handlers that create and rename sections.
 *
 * @package Eight_Day_Week
 */

namespace Eight_Day_Week\Tests\Integration\Ajax;

use WP_Ajax_UnitTestCase;
use WPAjaxDieContinueException;
use WPAjaxDieStopException;

use function Eight_Day_Week\Sections\get_sections;

/**
 * Covers who can create and rename sections, and on which posts.
 */
class SectionAjaxTest extends WP_Ajax_UnitTestCase {

	/**
	 * Print issue the sections are added to.
	 *
	 * @var int
	 */
	private $issue_id;

	/**
	 * Creates a print issue.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->issue_id = self::factory()->post->create( array( 'post_type' => EDW_PRINT_ISSUE_CPT ) );
	}

	/**
	 * Logs in a user with the given role and sends a valid nonce with the request.
	 *
	 * @param string $role Role of the acting user.
	 */
	private function act_as( string $role ): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );
		$_POST['_ajax_nonce'] = wp_create_nonce( EDW_AJAX_NONCE_SLUG );
	}

	/**
	 * Runs an AJAX action whose handler ends with a JSON response.
	 *
	 * @param string $action AJAX action.
	 * @return array Decoded JSON response.
	 */
	private function json_response( string $action ): array {
		try {
			$this->_handleAjax( $action );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		return json_decode( $this->_last_response, true );
	}

	/**
	 * Counts the section posts in the database.
	 *
	 * @return int Number of sections.
	 */
	private function count_sections(): int {
		return count(
			get_posts(
				array(
					'post_type'   => EDW_SECTION_CPT,
					'post_status' => 'any',
					'fields'      => 'ids',
					'numberposts' => -1,
				)
			)
		);
	}

	/**
	 * Print Production users can't create sections.
	 */
	public function test_print_production_cannot_create_a_section(): void {
		$this->act_as( 'print_production' );
		$_POST['name']           = 'News';
		$_POST['print_issue_id'] = $this->issue_id;

		try {
			$this->_handleAjax( 'pp-create-section' );
			$this->fail( 'The request must be stopped.' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->assertSame( '-1', $e->getMessage() );
		}

		$this->assertSame( 0, $this->count_sections() );
		$this->assertSame( '', get_sections( $this->issue_id ) );
	}

	/**
	 * A section can only be created for a print issue.
	 */
	public function test_section_cannot_be_created_for_a_post_that_is_not_a_print_issue(): void {
		$this->act_as( 'print_editor' );
		$post_id                 = self::factory()->post->create();
		$_POST['name']           = 'News';
		$_POST['print_issue_id'] = $post_id;

		$response = $this->json_response( 'pp-create-section' );

		$this->assertFalse( $response['success'] );
		$this->assertSame( 0, $this->count_sections() );
		$this->assertSame( '', get_post_meta( $post_id, 'sections', true ) );
	}

	/**
	 * Print Production users can't rename sections.
	 */
	public function test_print_production_cannot_rename_a_section(): void {
		$section_id = self::factory()->post->create(
			array(
				'post_type'  => EDW_SECTION_CPT,
				'post_title' => 'News',
			)
		);
		$this->act_as( 'print_production' );
		$_POST['title']   = 'Sport';
		$_POST['post_id'] = $section_id;

		try {
			$this->_handleAjax( 'pp-update-section-title' );
			$this->fail( 'The request must be stopped.' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->assertSame( '-1', $e->getMessage() );
		}

		$this->assertSame( 'News', get_the_title( $section_id ) );
	}
}
