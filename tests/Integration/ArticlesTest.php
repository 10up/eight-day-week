<?php
/**
 * Tests saving the articles of a print issue's sections.
 *
 * @package Eight_Day_Week
 */

namespace Eight_Day_Week\Tests\Integration;

use WP_UnitTestCase;

use function Eight_Day_Week\Articles\save_section_articles;

/**
 * Covers how a print issue save stores each section's article list.
 */
class ArticlesTest extends WP_UnitTestCase {

	/**
	 * Print issue under test.
	 *
	 * @var int
	 */
	private $issue_id;

	/**
	 * Section of the issue.
	 *
	 * @var int
	 */
	private $section_id;

	/**
	 * Published posts used as articles.
	 *
	 * @var int[]
	 */
	private $article_ids;

	/**
	 * Creates an issue, a section and three articles, acting as an administrator.
	 */
	public function set_up(): void {
		parent::set_up();

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$this->issue_id    = self::factory()->post->create( array( 'post_type' => EDW_PRINT_ISSUE_CPT ) );
		$this->section_id  = self::factory()->post->create( array( 'post_type' => EDW_SECTION_CPT ) );
		$this->article_ids = self::factory()->post->create_many( 3 );
	}

	/**
	 * Clears the submitted form data.
	 */
	public function tear_down(): void {
		unset( $_POST['pi-article-ids'] );

		parent::tear_down();
	}

	/**
	 * Articles keep the submitted order, and a repeated article is stored once.
	 */
	public function test_save_keeps_article_order_and_drops_duplicates(): void {
		list( $first, $second, $third ) = $this->article_ids;
		$_POST['pi-article-ids']        = array(
			$this->section_id => "{$third},{$first},{$third},{$second}",
		);

		save_section_articles( $this->issue_id );

		$this->assertSame(
			"{$third},{$first},{$second}",
			get_post_meta( $this->section_id, 'articles', true )
		);
	}

	/**
	 * Article lists are only saved to section posts.
	 */
	public function test_save_ignores_lists_for_posts_that_are_not_sections(): void {
		$not_a_section           = self::factory()->post->create();
		$_POST['pi-article-ids'] = array(
			$not_a_section => (string) $this->article_ids[0],
		);

		save_section_articles( $this->issue_id );

		$this->assertSame( '', get_post_meta( $not_a_section, 'articles', true ) );
	}

	/**
	 * A list with anything but comma-separated IDs leaves the section's articles unchanged.
	 */
	public function test_save_ignores_malformed_article_lists(): void {
		update_post_meta( $this->section_id, 'articles', (string) $this->article_ids[0] );
		$_POST['pi-article-ids'] = array(
			$this->section_id => $this->article_ids[1] . ',abc',
		);

		save_section_articles( $this->issue_id );

		$this->assertSame( (string) $this->article_ids[0], get_post_meta( $this->section_id, 'articles', true ) );
	}
}
