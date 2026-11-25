<?php
/**
 * Tests which articles go into an InDesign export.
 *
 * @package Eight_Day_Week
 */

namespace Eight_Day_Week\Tests\Integration;

use Eight_Day_Week\Plugins\Article_Export\Article_Zip_Factory;
use WP_UnitTestCase;

/**
 * Covers which articles the export zip includes.
 */
class ExportTest extends WP_UnitTestCase {

	/**
	 * Builds an export factory for the given posts.
	 *
	 * @param int[] $ids Post IDs to export.
	 * @return Article_Zip_Factory Export factory.
	 */
	private function factory_for( array $ids ): Article_Zip_Factory {
		return new Article_Zip_Factory( array_map( 'strval', $ids ), 0, 'Issue' );
	}

	/**
	 * Posts the exporting user can't read are left out of the export.
	 */
	public function test_export_skips_posts_the_user_cannot_read(): void {
		$author  = self::factory()->user->create( array( 'role' => 'author' ) );
		$public  = self::factory()->post->create();
		$private = self::factory()->post->create(
			array(
				'post_author' => $author,
				'post_status' => 'private',
			)
		);
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'print_editor' ) ) );

		$articles = $this->factory_for( array( $public, $private ) )->import_articles();

		$this->assertSame( array( $public ), array_keys( $articles ) );
	}

	/**
	 * Password-protected posts are only exported for users who can edit them.
	 */
	public function test_export_includes_password_protected_posts_only_for_users_who_can_edit_them(): void {
		$protected = self::factory()->post->create( array( 'post_password' => 'secret' ) );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'print_editor' ) ) );
		$this->assertSame( array(), $this->factory_for( array( $protected ) )->import_articles() );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->assertSame( array( $protected ), array_keys( $this->factory_for( array( $protected ) )->import_articles() ) );
	}
}
