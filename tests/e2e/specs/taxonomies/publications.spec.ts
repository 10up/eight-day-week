/**
 * Publications: terms of the `print_issue_publication` taxonomy can be added, renamed and
 * deleted from its term screen under Print Issues.
 */

/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import {
	addTerm,
	openTermEditor,
	uniqueSuffix,
	visitTermScreen,
} from '../../utils/helpers';

const TAXONOMY = 'print_issue_publication';

test.describe( 'Publications', () => {
	test( 'creates a term', async ( { admin, page } ) => {
		await addTerm( admin, page, TAXONOMY, `Articles ${ uniqueSuffix() }` );
	} );

	test( 'renames a term', async ( { admin, page } ) => {
		const name = `Articles ${ uniqueSuffix() }`;
		const renamed = `${ name } renamed`;
		await addTerm( admin, page, TAXONOMY, name );

		await openTermEditor( admin, page, TAXONOMY, name );
		await page.locator( '#name' ).fill( renamed );
		// The submit button reads Update or Save depending on the WordPress version.
		await page
			.getByRole( 'button', { name: /^(Update|Save)$/ } )
			.click();

		await expect( page.locator( '.notice-success' ) ).toContainText(
			'Item updated.'
		);
		await visitTermScreen( admin, TAXONOMY );
		await expect( page.locator( '#the-list' ) ).toContainText( renamed );
	} );

	test( 'deletes a term', async ( { admin, page } ) => {
		const name = `Articles ${ uniqueSuffix() }`;
		await addTerm( admin, page, TAXONOMY, name );

		await openTermEditor( admin, page, TAXONOMY, name );
		// Core asks for confirmation before deleting.
		page.once( 'dialog', ( dialog ) => dialog.accept() );
		await page.getByRole( 'link', { name: 'Delete' } ).click();

		// Core returns to the term list.
		await expect( page ).toHaveURL( /edit-tags\.php/ );
		await expect( page.locator( '#the-list' ) ).not.toContainText( name );
	} );
} );
