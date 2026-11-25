/**
 * Admin: the dashboard loads with the plugin active, and the plugin can be
 * activated from the Plugins screen.
 */

/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe( 'Admin', () => {
	// Leave the plugin active for later specs even if a step fails.
	test.afterAll( async ( { requestUtils } ) => {
		await requestUtils.activatePlugin( 'eight-day-week' );
	} );

	test( 'opens the dashboard', async ( { admin, page } ) => {
		await admin.visitAdminPage( '/' );

		await expect(
			page.getByRole( 'heading', { level: 1, name: 'Dashboard' } )
		).toBeVisible();
	} );

	test( 'activates the plugin when it is deactivated', async ( {
		admin,
		page,
		requestUtils,
	} ) => {
		// The row's slug depends on how the plugin was installed.
		const row = page.locator( '#the-list tr[data-slug^="eight-day-week"]' );

		await requestUtils.deactivatePlugin( 'eight-day-week' );
		await admin.visitAdminPage( 'plugins.php' );
		await row
			.getByRole( 'link', { name: 'Activate Eight Day Week' } )
			.click();

		await expect( page.getByText( 'Plugin activated.' ) ).toBeVisible();
		await expect(
			row.getByRole( 'link', { name: 'Deactivate Eight Day Week' } )
		).toBeVisible();
	} );
} );
