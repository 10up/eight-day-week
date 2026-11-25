<?php
/**
 * Tests the print roles Eight Day Week adds.
 *
 * @package Eight_Day_Week
 */

namespace Eight_Day_Week\Tests\Integration;

use WPDieException;
use WP_UnitTestCase;

use function Eight_Day_Week\User_Roles\add_roles;
use function Eight_Day_Week\User_Roles\current_user_can_edit_print_issue;
use function Eight_Day_Week\User_Roles\update_users_print_role;

/**
 * Covers who may edit print issues, how print roles survive role changes and
 * which roles the Users screen's Print Role select may grant.
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

		// Creating a role also fills the $wp_user_roles global, which makes every
		// later roles reload (multisite switches sites) use it instead of the
		// database. Clear it so later tests see the full set of roles.
		unset( $GLOBALS['wp_user_roles'] );
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

	/**
	 * Submits the Users screen's Print Role bulk form and returns the wp_die() message.
	 *
	 * @param string $role       Role picked in the top Print Role select.
	 * @param int    $target_id  User the role is applied to.
	 * @return string The wp_die() message.
	 */
	private function submit_print_role( string $role, int $target_id ): string {
		$_GET['pp-print-role']  = $role;
		$_GET['pp-print-role2'] = '-1';
		$_GET['users']          = array( (string) $target_id );
		$_REQUEST['_wpnonce']   = wp_create_nonce( 'bulk-users' );

		// The handler redirects and exits once roles change; fail instead of ending the run.
		add_filter(
			'wp_redirect',
			static function () {
				throw new WPDieException( 'Print roles were changed.' );
			}
		);

		try {
			update_users_print_role();
		} catch ( WPDieException $e ) {
			return $e->getMessage();
		} finally {
			unset( $_GET['pp-print-role'], $_GET['pp-print-role2'], $_GET['users'], $_REQUEST['_wpnonce'] );
		}

		$this->fail( 'The request must be refused.' );
	}

	/**
	 * The Print Role select can only grant print roles.
	 */
	public function test_print_role_select_cannot_grant_other_roles(): void {
		$target = self::factory()->user->create( array( 'role' => 'author' ) );
		$admin  = self::factory()->user->create( array( 'role' => 'administrator' ) );
		if ( is_multisite() ) {
			grant_super_admin( $admin );
		}
		wp_set_current_user( $admin );

		$message = $this->submit_print_role( 'administrator', $target );

		$this->assertStringContainsString( 'give users that print role', $message );
		$this->assertSame( array( 'author' ), get_userdata( $target )->roles );
	}
}
