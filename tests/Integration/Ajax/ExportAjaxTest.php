<?php
/**
 * Tests access to the AJAX article export.
 *
 * @package Eight_Day_Week
 */

namespace Eight_Day_Week\Tests\Integration\Ajax;

use WP_Ajax_UnitTestCase;
use WPAjaxDieStopException;

/**
 * Covers who can download an InDesign export.
 */
class ExportAjaxTest extends WP_Ajax_UnitTestCase {

	/**
	 * Users who can't work with print issues can't export articles.
	 */
	public function test_user_without_print_issue_access_cannot_export(): void {
		$post_id = self::factory()->post->create();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$_POST['_ajax_nonce'] = wp_create_nonce( EDW_AJAX_NONCE_SLUG );
		$_POST['article_ids'] = (string) $post_id;

		try {
			$this->_handleAjax( 'pp-article-export' );
			$this->fail( 'The request must be stopped.' );
		} catch ( WPAjaxDieStopException $e ) {
			// A stop means the handler ended before sending any output.
			$this->assertSame( '-1', $e->getMessage() );
		}
	}
}
