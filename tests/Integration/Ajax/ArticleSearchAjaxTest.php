<?php
/**
 * Tests the AJAX article search used to add articles to a section.
 *
 * @package Eight_Day_Week
 */

namespace Eight_Day_Week\Tests\Integration\Ajax;

use WP_Ajax_UnitTestCase;
use WPAjaxDieContinueException;

/**
 * Covers who can search for articles and which posts the search returns.
 */
class ArticleSearchAjaxTest extends WP_Ajax_UnitTestCase {

	/**
	 * Logs in a user with the given role and sends a valid nonce with the request.
	 *
	 * @param string $role Role of the acting user.
	 */
	private function act_as( string $role ): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );
		$_GET['_ajax_nonce'] = wp_create_nonce( EDW_AJAX_NONCE_SLUG );
	}

	/**
	 * Searches for articles by title.
	 *
	 * @param string $title Title to search for.
	 * @return array Decoded JSON response.
	 */
	private function search( string $title ): array {
		$_GET['title'] = $title;

		try {
			$this->_handleAjax( 'pp-get-articles' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		return json_decode( $this->_last_response, true );
	}

	/**
	 * Gets the post IDs a successful search returned.
	 *
	 * @param array $response Decoded JSON response.
	 * @return int[] Post IDs.
	 */
	private function found_ids( array $response ): array {
		$this->assertTrue( $response['success'] );

		return array_map( 'intval', wp_list_pluck( $response['data']['articles'], 'value' ) );
	}

	/**
	 * Print Production users can't search for articles to add.
	 */
	public function test_print_production_cannot_search_articles(): void {
		self::factory()->post->create( array( 'post_title' => 'Harbour news' ) );
		$this->act_as( 'print_production' );

		$response = $this->search( 'Harbour' );

		$this->assertFalse( $response['success'] );
		$this->assertArrayNotHasKey( 'articles', (array) $response['data'] );
	}

	/**
	 * The search returns only articles the user can read, and only article post types.
	 */
	public function test_search_returns_only_readable_articles(): void {
		$author    = self::factory()->user->create( array( 'role' => 'author' ) );
		$published = self::factory()->post->create( array( 'post_title' => 'Harbour news' ) );
		self::factory()->post->create(
			array(
				'post_title'  => 'Harbour secrets',
				'post_status' => 'private',
				'post_author' => $author,
			)
		);
		self::factory()->post->create(
			array(
				'post_title' => 'Harbour page',
				'post_type'  => 'page',
			)
		);
		$this->act_as( 'print_editor' );

		$this->assertSame( array( $published ), $this->found_ids( $this->search( 'Harbour' ) ) );
	}

	/**
	 * The article post types filter decides which post types the search covers.
	 */
	public function test_article_post_types_filter_adds_post_types_to_the_search(): void {
		$page = self::factory()->post->create(
			array(
				'post_title' => 'Harbour page',
				'post_type'  => 'page',
			)
		);
		add_filter(
			'Eight_Day_Week\Articles\post_types',
			static function () {
				return array( 'page' );
			}
		);
		$this->act_as( 'print_editor' );

		$this->assertSame( array( $page ), $this->found_ids( $this->search( 'Harbour' ) ) );
	}
}
