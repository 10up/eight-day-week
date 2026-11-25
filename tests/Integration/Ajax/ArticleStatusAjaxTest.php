<?php
/**
 * Tests the AJAX handler that sets article statuses in bulk.
 *
 * @package Eight_Day_Week
 */

namespace Eight_Day_Week\Tests\Integration\Ajax;

use WP_Ajax_UnitTestCase;
use WPAjaxDieContinueException;
use WPAjaxDieStopException;

/**
 * Covers who can set article statuses, and on which posts.
 */
class ArticleStatusAjaxTest extends WP_Ajax_UnitTestCase {

	/**
	 * Article status term to apply.
	 *
	 * @var int
	 */
	private $status_id;

	/**
	 * Creates an article status.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->status_id = self::factory()->term->create(
			array(
				'taxonomy' => EDW_ARTICLE_STATUS_TAX,
				'name'     => 'Ready',
			)
		);
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
	 * Gets the article status term IDs of a post.
	 *
	 * @param int $post_id Post ID.
	 * @return int[] Term IDs.
	 */
	private function statuses_of( int $post_id ): array {
		return wp_get_object_terms( $post_id, EDW_ARTICLE_STATUS_TAX, array( 'fields' => 'ids' ) );
	}

	/**
	 * Print Production users can't set article statuses.
	 */
	public function test_print_production_cannot_set_article_statuses(): void {
		$post_id = self::factory()->post->create();
		$this->act_as( 'print_production' );
		$_POST['status']           = $this->status_id;
		$_POST['checked_articles'] = (string) $post_id;

		try {
			$this->_handleAjax( 'pp-bulk-edit-article-statuses' );
			$this->fail( 'The request must be stopped.' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->assertSame( '-1', $e->getMessage() );
		}

		$this->assertSame( array(), $this->statuses_of( $post_id ) );
	}

	/**
	 * Article statuses can only be set on article post types.
	 */
	public function test_article_status_is_not_set_on_other_post_types(): void {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$this->act_as( 'administrator' );
		$_POST['status']           = $this->status_id;
		$_POST['checked_articles'] = (string) $page_id;

		try {
			$this->_handleAjax( 'pp-bulk-edit-article-statuses' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		$this->assertFalse( json_decode( $this->_last_response, true )['success'] );
		$this->assertSame( array(), $this->statuses_of( $page_id ) );
	}

	/**
	 * Setting a status replaces each article's previous status.
	 */
	public function test_status_replaces_the_previous_status_of_each_article(): void {
		$previous = self::factory()->term->create( array( 'taxonomy' => EDW_ARTICLE_STATUS_TAX ) );
		$post_ids = self::factory()->post->create_many( 2 );
		foreach ( $post_ids as $post_id ) {
			wp_set_object_terms( $post_id, $previous, EDW_ARTICLE_STATUS_TAX );
		}
		$this->act_as( 'administrator' );
		$_POST['status']           = $this->status_id;
		$_POST['checked_articles'] = implode( ',', $post_ids );

		try {
			$this->_handleAjax( 'pp-bulk-edit-article-statuses' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		$this->assertTrue( json_decode( $this->_last_response, true )['success'] );
		foreach ( $post_ids as $post_id ) {
			$this->assertSame( array( $this->status_id ), $this->statuses_of( $post_id ) );
		}
	}
}
