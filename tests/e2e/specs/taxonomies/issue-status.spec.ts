/**
 * Issue Status: terms of the `print_issue_status` taxonomy can be added from its term
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

const TAXONOMY = 'print_issue_status';

test.describe( 'Issue Status', () => {
	test( 'creates a term', async ( { admin, page } ) => {
		await addTerm( admin, page, TAXONOMY, `Active ${ uniqueSuffix() }` );
	} );
} );
