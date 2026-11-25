/**
 * Print issues: an issue is built from sections of existing posts, edited and
 * exported as a zip.
 */

/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import {
	addSectionWithArticle,
	createPrintIssue,
	uniqueSuffix,
} from '../../utils/helpers';

// The published post the article search finds.
const ARTICLE_TITLE = 'Hello from the print desk';

test.describe( 'Print issue', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		await requestUtils.createPost( {
			title: ARTICLE_TITLE,
			status: 'publish',
		} );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();
	} );

	test( 'creates an issue with a section and an article', async ( {
		admin,
		page,
	} ) => {
		const title = `Print Title ${ uniqueSuffix() }`;

		await createPrintIssue(
			admin,
			page,
			title,
			'Section title 1',
			ARTICLE_TITLE
		);

		// The saved issue lists the section and its article.
		const section = page
			.locator( '.postbox[id^="pi-sections-box"]' )
			.filter( { hasText: 'Section title 1' } );
		await expect(
			section.getByRole( 'link', { name: ARTICLE_TITLE } )
		).toBeVisible();
	} );

	test( 'renames an issue and adds a second section', async ( {
		admin,
		page,
	} ) => {
		const suffix = uniqueSuffix();
		const title = `Print Title ${ suffix }`;
		const renamed = `Print Title ${ suffix } edited`;
		const issueId = await createPrintIssue(
			admin,
			page,
			title,
			'Section title 1',
			ARTICLE_TITLE
		);

		await admin.visitAdminPage(
			'post.php',
			`post=${ issueId }&action=edit`
		);
		await page.locator( 'input#title' ).fill( renamed );
		await addSectionWithArticle( page, 'Section title 2', ARTICLE_TITLE );

		await expect( page.locator( 'input#title' ) ).toHaveValue( renamed );
		for ( const name of [ 'Section title 1', 'Section title 2' ] ) {
			const section = page
				.locator( '.postbox[id^="pi-sections-box"]' )
				.filter( { hasText: name } );
			await expect(
				section.getByRole( 'link', { name: ARTICLE_TITLE } )
			).toBeVisible();
		}
	} );

	test( 'exports the issue articles as a zip', async ( { admin, page } ) => {
		const title = `Print Title ${ uniqueSuffix() }`;
		const issueId = await createPrintIssue(
			admin,
			page,
			title,
			'Section title 1',
			ARTICLE_TITLE
		);

		await admin.visitAdminPage(
			'post.php',
			`post=${ issueId }&action=edit`
		);
		const download = page.waitForEvent( 'download' );
		await page.locator( '#article-export-all' ).click();

		expect( ( await download ).suggestedFilename() ).toMatch(
			new RegExp( `^Issue-${ title.replace( / /g, '-' ) }-exported-on-.*\\.zip$` )
		);
	} );
} );
