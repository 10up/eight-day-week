<?php
/**
 * Tests saving the section order through core's meta box order request.
 *
 * @package Eight_Day_Week
 */

namespace Eight_Day_Week\Tests\Integration\Ajax;

use WP_Ajax_UnitTestCase;
use WPAjaxDieContinueException;
use WPAjaxDieStopException;

/**
 * Covers where meta box order is stored for print issues and for other screens.
 */
class SectionOrderAjaxTest extends WP_Ajax_UnitTestCase {

	/**
	 * Hooks core's meta box order handler behind the plugin's.
	 *
	 * The base class registers core's handlers only once per class, and they are
	 * gone after the first test restores the saved hooks.
	 */
	public function set_up(): void {
		parent::set_up();

		require_once ABSPATH . 'wp-admin/includes/ajax-actions.php';
		add_action( 'wp_ajax_meta-box-order', 'wp_ajax_meta_box_order', 1 );
	}

	/**
	 * Logs in a user with the given role and sends a valid meta box order nonce.
	 *
	 * @param string $role Role of the acting user.
	 * @return int User ID.
	 */
	private function act_as( string $role ): int {
		$user_id = self::factory()->user->create( array( 'role' => $role ) );
		wp_set_current_user( $user_id );
		$_POST['_ajax_nonce'] = wp_create_nonce( 'meta-box-order' );

		return $user_id;
	}

	/**
	 * Sends a meta box order request.
	 *
	 * @param string $page  Screen the order belongs to.
	 * @param array  $order Meta box order by context.
	 * @return string The die message, or the JSON response when the handler sent one.
	 */
	private function send_order( string $page, array $order ): string {
		$_POST['page']  = $page;
		$_POST['order'] = $order;

		try {
			$this->_handleAjax( 'meta-box-order' );
		} catch ( WPAjaxDieStopException $e ) {
			return $e->getMessage();
		} catch ( WPAjaxDieContinueException $e ) {
			return $this->_last_response;
		}

		$this->fail( 'The request must end with a die.' );
	}

	/**
	 * Builds the order core sends for a print issue's edit screen.
	 *
	 * @param int $issue_id   Print issue ID.
	 * @param int $section_id Section ID.
	 * @return array Meta box order.
	 */
	private function issue_order( int $issue_id, int $section_id ): array {
		return array(
			'normal' => "pi-sections-template-{$issue_id},pi-sections-box-{$section_id}",
			'side'   => 'submitdiv',
		);
	}

	/**
	 * A Print Editor's section order is saved on the print issue, not on the user.
	 */
	public function test_print_editor_saves_section_order_on_the_issue(): void {
		$issue_id   = self::factory()->post->create( array( 'post_type' => EDW_PRINT_ISSUE_CPT ) );
		$section_id = self::factory()->post->create( array( 'post_type' => EDW_SECTION_CPT ) );
		$user_id    = $this->act_as( 'print_editor' );
		$order      = $this->issue_order( $issue_id, $section_id );

		$this->assertSame( '1', $this->send_order( EDW_PRINT_ISSUE_CPT, $order ) );

		$this->assertSame( $order, get_post_meta( $issue_id, 'section-order', true ) );
		$this->assertSame( '', get_user_meta( $user_id, 'meta-box-order_' . EDW_PRINT_ISSUE_CPT, true ) );
	}

	/**
	 * Print Production users can't reorder an issue's sections.
	 */
	public function test_print_production_cannot_reorder_sections(): void {
		$issue_id   = self::factory()->post->create( array( 'post_type' => EDW_PRINT_ISSUE_CPT ) );
		$section_id = self::factory()->post->create( array( 'post_type' => EDW_SECTION_CPT ) );
		$user_id    = $this->act_as( 'print_production' );

		$this->assertSame( '-1', $this->send_order( EDW_PRINT_ISSUE_CPT, $this->issue_order( $issue_id, $section_id ) ) );

		$this->assertSame( '', get_post_meta( $issue_id, 'section-order', true ) );
		$this->assertSame( '', get_user_meta( $user_id, 'meta-box-order_' . EDW_PRINT_ISSUE_CPT, true ) );
	}

	/**
	 * Meta box order on other screens is still saved per user by core.
	 */
	public function test_other_screens_keep_core_meta_box_order(): void {
		$user_id = $this->act_as( 'editor' );
		$order   = array(
			'normal' => 'postcustom,commentstatusdiv',
			'side'   => 'submitdiv',
		);

		// Core's own response differs between WordPress versions; the saved order is what counts.
		$this->send_order( 'post', $order );

		$this->assertSame( $order, get_user_meta( $user_id, 'meta-box-order_post', true ) );
	}
}
