/**
 * Helpers shared by the E2E specs.
 */

/**
 * External dependencies
 */
import { expect } from '@playwright/test';
import type { Page } from '@playwright/test';

/**
 * WordPress dependencies
 */
import type { Admin } from '@wordpress/e2e-test-utils-playwright';

/**
 * A suffix that keeps names unique across tests, retries and repeated runs
 * (the test site keeps terms and print issues between runs).
 *
 * @return {string} Unique suffix.
 */
export function uniqueSuffix(): string {
	return `${ Date.now().toString( 36 ) }${ Math.floor(
		Math.random() * 1000
	) }`;
}

/**
 * Opens a print issue taxonomy's term screen.
 *
 * @param {Admin}  admin    Admin fixture.
 * @param {string} taxonomy Taxonomy name.
 */
export async function visitTermScreen( admin: Admin, taxonomy: string ) {
	await admin.visitAdminPage(
		'edit-tags.php',
		`taxonomy=${ taxonomy }&post_type=print-issue`
	);
}

/**
 * Adds a term through the term screen's Add form.
 *
 * @param {Admin}  admin    Admin fixture.
 * @param {Page}   page     Page fixture.
 * @param {string} taxonomy Taxonomy name.
 * @param {string} name     Term name.
 */
export async function addTerm(
	admin: Admin,
	page: Page,
	taxonomy: string,
	name: string
) {
	await visitTermScreen( admin, taxonomy );
	await page.locator( '#tag-name' ).fill( name );
	await page.locator( '#submit' ).click();

	await expect( page.locator( '.notice-success' ) ).toContainText(
		'Item added.'
	);
	await expect( page.locator( '#the-list' ) ).toContainText( name );
}

/**
 * Opens the edit screen of a term listed on the term screen.
 *
 * @param {Admin}  admin    Admin fixture.
 * @param {Page}   page     Page fixture.
 * @param {string} taxonomy Taxonomy name.
 * @param {string} name     Term name.
 */
export async function openTermEditor(
	admin: Admin,
	page: Page,
	taxonomy: string,
	name: string
) {
	await visitTermScreen( admin, taxonomy );
	await page
		.locator( '#the-list a.row-title' )
		.filter( { hasText: name } )
		.click();
	await expect( page.locator( '#name' ) ).toHaveValue( name );
}

/**
 * Adds a section with one article to the open print issue, then saves it
 * with the section's Save button.
 *
 * @param {Page}   page         Page fixture, on a print issue edit screen.
 * @param {string} sectionName  Name of the new section.
 * @param {string} articleTitle Title of a published post to add.
 */
export async function addSectionWithArticle(
	page: Page,
	sectionName: string,
	articleTitle: string
) {
	await page.locator( '#pi-section-add' ).click();
	await page.locator( '#pi-section-name' ).fill( sectionName );
	await page.locator( '#pi-section-add-confirm' ).click();

	const section = page
		.locator( '.postbox[id^="pi-sections-box"]' )
		.filter( { hasText: sectionName } );
	await expect( section ).toBeVisible();

	await section.locator( '.pi-article-add' ).click();
	const search = page.waitForResponse(
		( response ) =>
			response.url().includes( 'admin-ajax.php' ) &&
			response.request().method() === 'GET'
	);
	await section
		.locator( 'input.pi-article-title' )
		.pressSequentially( articleTitle );
	expect( ( await search ).status() ).toBe( 200 );

	await page
		.locator( '.ui-menu-item' )
		.filter( { hasText: articleTitle } )
		.click();
	await expect(
		section.getByRole( 'link', { name: articleTitle } )
	).toBeVisible();

	await Promise.all( [
		page.waitForURL( /post\.php\?post=\d+&action=edit/ ),
		section.locator( '.button-primary' ).click(),
	] );
}

/**
 * Creates a print issue with one section and one article through the UI.
 *
 * @param {Admin}  admin        Admin fixture.
 * @param {Page}   page         Page fixture.
 * @param {string} title        Issue title.
 * @param {string} sectionName  Section name.
 * @param {string} articleTitle Title of a published post to add.
 * @return {Promise<number>} The new issue's ID.
 */
export async function createPrintIssue(
	admin: Admin,
	page: Page,
	title: string,
	sectionName: string,
	articleTitle: string
): Promise< number > {
	await admin.visitAdminPage( 'post-new.php', 'post_type=print-issue' );
	await page.locator( 'input#title' ).fill( title );
	await addSectionWithArticle( page, sectionName, articleTitle );

	await expect( page.locator( 'input#title' ) ).toHaveValue( title );

	return Number( new URL( page.url() ).searchParams.get( 'post' ) );
}
