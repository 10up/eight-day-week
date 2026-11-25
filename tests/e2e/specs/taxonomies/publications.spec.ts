/**
 * Publications: terms of the `print_issue_publication` taxonomy can be added from its term
 * screen under Print Issues.
 */

/**
 * WordPress dependencies
 */
import { test } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import { addTerm, uniqueSuffix } from '../../utils/helpers';

const TAXONOMY = 'print_issue_publication';

test.describe( 'Publications', () => {
	test( 'creates a term', async ( { admin, page } ) => {
		await addTerm( admin, page, TAXONOMY, `Articles ${ uniqueSuffix() }` );
	} );
} );
