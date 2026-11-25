/**
 * Users: the Print Role bulk action on the Users screen adds a print role to
 * a user and removes it again.
 */

/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import { uniqueSuffix } from '../../utils/helpers';

test.describe( 'Print role', () => {
	test.afterAll( async ( { requestUtils } ) => {
		await requestUtils.deleteAllUsers();
	} );

	test( 'adds and removes the Print Editor role', async ( {
		admin,
		page,
		requestUtils,
	} ) => {
		const username = `print${ uniqueSuffix() }`;
		const user = await requestUtils.createUser( {
			username,
			email: `${ username }@example.com`,
			password: 'password',
			roles: [ 'editor' ],
		} );
		const printRole = page.locator( `#user-${ user.id } td.print_role` );

		// Applies the top Print Role select to the user through the bulk form.
		const changePrintRole = async ( role: string ) => {
			await admin.visitAdminPage( 'users.php' );
			await page.locator( `#user_${ user.id }` ).check();
			await page.locator( '#new_role' ).selectOption( 'editor' );
			await page.locator( '#pp-print-role' ).selectOption( role );
			await page.locator( '#changeit' ).click();
			await expect( page.locator( '#message' ) ).toContainText(
				'Changed roles.'
			);
		};

		await changePrintRole( 'print_editor' );
		await expect( printRole ).toContainText( 'Print Editor' );

		await changePrintRole( 'remove' );
		await expect( printRole ).not.toContainText( 'Print Editor' );
	} );
} );
