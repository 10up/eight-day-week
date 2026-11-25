<?php
/**
 * Tests saving the sections of a print issue.
 *
 * @package Eight_Day_Week
 */

namespace Eight_Day_Week\Tests\Integration;

use WP_UnitTestCase;

use function Eight_Day_Week\Sections\get_sections;
use function Eight_Day_Week\Sections\update_print_issue_sections;

/**
 * Covers which sections a print issue save keeps, stores and deletes.
 */
class SectionsTest extends WP_UnitTestCase {

	/**
	 * Print issue under test.
	 *
	 * @var int
	 */
	private $issue_id;

	/**
	 * Section kept by the save.
	 *
	 * @var int
	 */
	private $kept_section_id;

	/**
	 * Section dropped by the save.
	 *
	 * @var int
	 */
	private $dropped_section_id;

	/**
	 * Creates an issue with two sections.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->issue_id           = self::factory()->post->create( array( 'post_type' => EDW_PRINT_ISSUE_CPT ) );
		$this->kept_section_id    = self::factory()->post->create( array( 'post_type' => EDW_SECTION_CPT ) );
		$this->dropped_section_id = self::factory()->post->create( array( 'post_type' => EDW_SECTION_CPT ) );

		update_post_meta( $this->issue_id, 'sections', $this->kept_section_id . ',' . $this->dropped_section_id );
	}

	/**
	 * Clears the submitted form data.
	 */
	public function tear_down(): void {
		unset( $_POST['pi-section-ids'] );

		parent::tear_down();
	}

	/**
	 * A section left out of the submitted list is deleted; the others stay.
	 */
	public function test_save_deletes_sections_removed_from_the_issue(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'print_editor' ) ) );
		$_POST['pi-section-ids'] = (string) $this->kept_section_id;

		update_print_issue_sections( $this->issue_id );

		$this->assertNull( get_post( $this->dropped_section_id ), 'A removed section must be deleted.' );
		$this->assertNotNull( get_post( $this->kept_section_id ) );
		$this->assertSame( (string) $this->kept_section_id, get_sections( $this->issue_id ) );
	}

	/**
	 * Only section posts are ever deleted, even when another post ID is in the stored list.
	 */
	public function test_save_never_deletes_posts_that_are_not_sections(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'print_editor' ) ) );
		$post_id = self::factory()->post->create();
		update_post_meta( $this->issue_id, 'sections', $this->kept_section_id . ',' . $post_id );
		$_POST['pi-section-ids'] = (string) $this->kept_section_id;

		update_print_issue_sections( $this->issue_id );

		$this->assertNotNull( get_post( $post_id ), 'Saving sections must not delete other posts.' );
	}

	/**
	 * Submitted IDs that aren't sections are left out of the stored list.
	 */
	public function test_save_stores_only_section_ids(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'print_editor' ) ) );
		$post_id                 = self::factory()->post->create();
		$_POST['pi-section-ids'] = $this->kept_section_id . ',' . $post_id;

		update_print_issue_sections( $this->issue_id );

		$this->assertSame( (string) $this->kept_section_id, get_sections( $this->issue_id ) );
	}

	/**
	 * Print Production users can't change or delete an issue's sections.
	 */
	public function test_print_production_cannot_change_sections(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'print_production' ) ) );
		$_POST['pi-section-ids'] = '';

		update_print_issue_sections( $this->issue_id );

		$this->assertNotNull( get_post( $this->dropped_section_id ) );
		$this->assertSame(
			$this->kept_section_id . ',' . $this->dropped_section_id,
			get_sections( $this->issue_id )
		);
	}
}
