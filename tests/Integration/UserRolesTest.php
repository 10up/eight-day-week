<?php
/**
 * Tests the print roles Eight Day Week adds.
 *
 * @package Eight_Day_Week
 */

namespace Eight_Day_Week\Tests\Integration;

use WP_UnitTestCase;

use function Eight_Day_Week\User_Roles\add_roles;
use function Eight_Day_Week\User_Roles\current_user_can_edit_print_issue;

/**
 * Covers who may edit print issues and how print roles survive role changes.
 */
class UserRolesTest extends WP_UnitTestCase {

	/**
	 * Rebuilds the print roles from the current code before each test.
	 *
	 * The plugin's add_roles() merges capabilities into a saved role but never
	 * removes one, and saved roles can outlive the test install, so a capability
	 * dropped from the role definitions would otherwise still be granted here.
	 */
	public function set_up(): void {
		parent::set_up();

		remove_role( 'print_editor' );
		remove_role( 'print_production' );
		add_roles();
	}

	/**
	 * Print Production users can open issues but not change them; Print Editors can.
	 *
	 * Every write handler is gated on this check, so a change to either role's
	 * capabilities shows up here.
	 */
	public function test_only_print_editors_can_edit_print_issues(): void {
		$production = self::factory()->user->create( array( 'role' => 'print_production' ) );
		$editor     = self::factory()->user->create( array( 'role' => 'print_editor' ) );

		wp_set_current_user( $production );
		$this->assertFalse( current_user_can_edit_print_issue() );

		wp_set_current_user( $editor );
		$this->assertTrue( current_user_can_edit_print_issue() );
	}

	/**
	 * Changing a user's built-in role keeps the print role they already had.
	 *
	 * Core's set_role() strips every role, so the plugin adds the print role back.
	 */
	public function test_changing_built_in_role_keeps_print_role(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		$user    = get_userdata( $user_id );
		$user->add_role( 'print_editor' );

		$user->set_role( 'author' );

		$roles = get_userdata( $user_id )->roles;
		$this->assertContains( 'author', $roles );
		$this->assertNotContains( 'editor', $roles );
		$this->assertContains( 'print_editor', $roles );
	}
}
