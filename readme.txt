=== Eight Day Week Print Workflow ===
Contributors:      10up, observerteam, joshlevinson, brs14ku, jeffpaul
Tags:              print, workflow, editorial
Tested up to:      7.1
Stable tag:        1.3.0
License:           GPL-2.0-or-later
License URI:       https://spdx.org/licenses/GPL-2.0-or-later.html

Optimize publication workflows by using WordPress as your print CMS

== Description ==

Eight Day Week provides a set of tools to manage your print workflow directly in your WordPress dashboard–right where your posts are!

Primarily, it offers an interface to group, label, and manage the workflow status of posts in a printed "Issue".

**Features:**

**Create "Print Issues"**

- Add and order sections, and articles within sections
- Assign article statuses specific to your print workflow

**Limit access to Print Roles**

Two custom roles are added by this plugin to best model a real-world print team.

- The Print Editor role offers full access to the creation interfaces, such as Print Issue, Article Status, Print Publication, etc.
- The Print Production role offers read-only access to a Print Issues. The XML export tool is also available to Production users.

**View a Print Issue in "Read Only" mode**

- Circumvents the post locking feature by offering a read-only view of a print issue

**XML Export to InDesign: Classic Editor vs. Gutenberg Exports**

When exporting content from WordPress for use in InDesign, there are key differences between Classic Editor and Gutenberg (Block Editor) exports. This section highlights how each type of export behaves in InDesign and offers guidance on how to handle these differences.

- Classic Editor exports use basic HTML tags like `<p>`, `<strong>`, and `<em>`, making them straightforward for import into InDesign.
- Gutenberg exports include additional metadata such as HTML comments (`<!-- wp:paragraph -->`) that define block-level structures, which may require manual adjustments after importing into InDesign.

**Import Differences:**

- Classic Editor: Imports cleanly into InDesign without extra metadata.
- Gutenberg: May include block-related metadata, requiring users to clean up the imported content or manually adjust formatting.

**Recommendations:**

- Classic Editor: Best for simple imports with minimal manual work.
- Gutenberg: Recommended for users comfortable with removing metadata or adjusting block-based settings after the import.

== Installation ==

Eight Day Week has no settings or configurations to set up. It just works!

== Filters & Hooks ==

Eight Day Week provides a number of filters and hooks for customizing and extending the plugin.

**Modules**

Eight Day Week follows a module-style approach to many of its features. These can be turned on or off via filters, and all work independently.
These are:
Article Byline
Article Count
Article Export
Article Status
Issue Publication
Issue Status
Any of these can be disabled by returning a false value from the following filter format:
`
add_filter( 'Eight_Day_Week\Plugins\load_$plugin', '__return_false' );
`
The `$plugin` value is a slug version of the plugin name, i.e. article-byline.

**Article Table**

The information displayed in the list of articles within a Print Issue is filterable. Custom columns can be added via the following filters: `Eight_Day_Week\Articles\article_columns` and `Eight_Day_Week\Articles\article_meta_$column_name`.

Sample usage:
`
add_filter( 'Eight_Day_Week\Articles\article_columns', function( $columns ) {
    $columns['byline'] = _x( 'Byline', 'Label for multiple, comma separated authors', 'your-text-domain' );
    return $columns;
} );
add_filter( 'Eight_Day_Week\Articles\article_meta_byline', function( $incoming_value, $post ) {
    return implode( ', ', wp_list_pluck( my_get_post_authors_function( $post ), 'display_name' ) );
}
`

**Print Issue Table**

The information displayed in the list of Print Issues is filterable. Custom columns can be added via the following filter:
`Eight_Day_Week\Print_Issue_Columns\pi_columns`.  Note that this is a convenience filter, the base filter is `manage_edit-print-issue_columns`.  See `includes/functions/print-issue-columns.php` for sample usage.

**Article Export**

The export of posts in a Print Issue is highly customizable, from the file name of the zip, to the file name of the individual files, to the contents of the files themselves.  The best reference would be to read through `includes/functions/plugins/article-export.php`.  [Here's](https://github.com/10up/eight-day-week/wiki/Sample-Eight-Day-Week-filters-for-the-Observer) a few examples used on the *Observer*.

**Sample Eight Day Week filters for the Observer**

Examples from Observer's eight-day-week-filters.php:

`
<?php

add_filter( 'Eight_Day_Week\Plugins\Article_Export\xml_outer_elements', function( $elements, $article ) {
	$elements['subHeadline'] = get_post_meta( $article->ID, 'nyo_dek', true );
	return $elements;
}, 10, 2 );

add_filter( 'Eight_Day_Week\Plugins\Article_Export\xml_outer_elements', function( $elements, $article ) {
	if( function_exists( '\Eight_Day_Week\Plugins\Article_Byline\get_article_byline' ) ) {
		$elements['byline']      = \Eight_Day_Week\Plugins\Article_Byline\get_article_byline( $article );
	}
	return $elements;
}, 10, 2 );

add_filter( 'Eight_Day_Week\Plugins\Article_Export\xpath_extract', function( $extract ) {
	$extract[] = [
		'tag_name'  => 'pullQuote',
		'container' => 'pullQuotes',
		'query'     => '//p[contains(@class, "pullquote")]'
	];
	return $extract;
} );

add_filter( 'Eight_Day_Week\Plugins\Article_Export\dom', function ( $dom ) {
	$swap_tag_name = 'emphasized';

	$extract_map = [
		'strong' => [
			'solo'  => 'bold',
			'inner' => 'em'
		],
		'em'     => [
			'solo'  => 'italics',
			'inner' => 'strong'
		],
	];

	foreach ( $extract_map as $tag_name => $map ) {
		$nodes  = $dom->getElementsByTagName( $tag_name );
		$length = $nodes->length;

		for ( $i = $length; -- $i >= 0; ) {
			$el         = $nodes->item( $i );
			$emphasized = $el->getElementsByTagName( $map['inner'] );
			if ( $emphasized->length ) {
				$em            = $dom->createElement( $swap_tag_name );
				$em->nodeValue = $el->nodeValue;
				try {
					$el->parentNode->replaceChild( $em, $el );
				} catch ( \Exception $e ) {

				}
				continue;
			}

			$new            = $dom->createElement( $map['solo'] );
			$new->nodeValue = $el->nodeValue;
			try {
				$el->parentNode->replaceChild( $new, $el );
			} catch ( \Exception $e ) {

			}

		}

	}

	return $dom;

} );
`

== Known Caveats/Issues ==

**Gutenberg exports**
Gutenberg-based exports include some additional metadata/details that a Classic Editor-based export does not.  [Gutenberg stores block data in HTML comments](https://developer.wordpress.org/block-editor/key-concepts/#delimiters-and-parsing-expression-grammar), so you'll notice those comments (in the form of `<!-- "Gutenberg block data" -->`) appearing in the Eight Day Week XML export.  Note that the XML is still valid--you can test and confirm that yourself using an [XML validator](https://www.xmlvalidation.com/)--though depending on your version of InDesign you may get different results upon importing a Gutenberg export compared to a Classic Editor export.  Our testing showed that those HTML comments in a Gutenberg export did not affect the import into InDesign however.  You can test how this works in your version of InDesign with these sample XML files: [Gutenberg XML](https://raw.githubusercontent.com/wiki/10up/eight-day-week/BlockEditor-sample.xml), [Classic Editor XML](https://raw.githubusercontent.com/wiki/10up/eight-day-week/ClassicEditor-sample.xml).  You can also test how this works with full ZIP exports of Print Issues containing a [Block Editor sample](https://raw.githubusercontent.com/wiki/10up/eight-day-week/BlockEditor-SampleExport.zip) or a [Classic Editor sample](https://raw.githubusercontent.com/wiki/10up/eight-day-week/ClassicEditor-SampleExport.zip).

== Frequently Asked Questions ==

= Where do I report security bugs found in this plugin? =

Please report security bugs found in the source code of the Eight Day Week Print Workflow plugin through the [Patchstack Vulnerability Disclosure  Program](https://patchstack.com/database/vdp/597230e2-c7e4-4ec0-8484-85c6fa9d1bf9).  The Patchstack team will assist you with verification, CVE assignment, and notify the developers of this plugin.

== Screenshots ==

1. The Print Issue list table.
2. The Print Issue editor showing the sections and contained articles, buttons to add sections & articles, and options for the Publication, Issue Status, and Issue Date. Each section has a Save button for convenience; all Save buttons simply save the entire Print Issue.
3. A Print Issue in "read only view". This view allows anyone with access to view a Print Issue without inducing a post lock, or being locked out by another editor. Note that the Export tools are still available in read only view.
4. The Issue Statuses category page.
5. The Publications category page.
6. The Article Status category page.
7. The Users list table, showing the "Change print role to..." dropdown and "Print Role" column.
8. A sample article XML export for InDesign.

== Changelog ==

= 1.3.0 - 2025-05-xx =
**Note that this release bumps the WordPress minimum version from 5.5 to 6.7.**

* **Security:** Resolve GHSA-88cc-x3jq-vwjx (props [@dkotter](https://github.com/dkotter), [@peterwilsoncc](https://github.com/peterwilsoncc), [@jeffpaul](https://github.com/jeffpaul) via [GHSA-88cc-x3jq-vwjx](https://github.com/10up/eight-day-week/security/advisories/GHSA-88cc-x3jq-vwjx)).
* **Security:** Bump `phpunit/phpunit` from 9.5.28 to 9.6.33 (props [@dkotter](https://github.com/dkotter), [@dependabot](https://github.com/apps/dependabot) via [#189](https://github.com/10up/eight-day-week/pull/189)).
* **Security:** Bump `immutable` from 5.1.4 to 5.1.5 (props [@dkotter](https://github.com/dkotter), [@dependabot](https://github.com/apps/dependabot) via [#192](https://github.com/10up/eight-day-week/pull/192)).
* **Changed:** Update to support WordPress 7.0 (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#183](https://github.com/10up/eight-day-week/pull/183), [#199](https://github.com/10up/eight-day-week/pull/199)).
* **Changed:** Minimum supported version of WordPress is now 6.7 (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#184](https://github.com/10up/eight-day-week/pull/184)).
* **Changed:** Update npm dependencies (props [@dkotter](https://github.com/dkotter), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#186](https://github.com/10up/eight-day-week/pull/186)).
* **Changed:** Bump `lodash` from 4.17.21 to 4.17.23 (props [@dkotter](https://github.com/dkotter), [@dependabot](https://github.com/apps/dependabot) via [#187](https://github.com/10up/eight-day-week/pull/187)).
* **Changed:** Bump `qs` from 6.14.1 to 6.14.2 (props [@dkotter](https://github.com/dkotter), [@dependabot](https://github.com/apps/dependabot) via [#190](https://github.com/10up/eight-day-week/pull/190)).
* **Changed:** Bump `systeminformation` from 5.30.5 to 5.31.6 (props [@dkotter](https://github.com/dkotter), [@dependabot](https://github.com/apps/dependabot) via [#191](https://github.com/10up/eight-day-week/pull/191), [#201](https://github.com/10up/eight-day-week/pull/201)).
* **Changed:** Bump `picomatch` from 4.0.3 to 4.0.4, `postcss` from 8.5.6 to 8.5.14, `simple-git` from 3.30.0 to 3.36.0 (props [@dkotter](https://github.com/dkotter), [@dependabot](https://github.com/apps/dependabot) via [#200](https://github.com/10up/eight-day-week/pull/200)).

= 1.2.6 - 2025-12-16 =

* **Security:** Resolve GHSA-c5vw-3gpx-gv22 data exposure to authenticated users (thank you Patchstack for responsibly disclosing this issue; props [@kmgalanakis](https://github.com/kmgalanakis), [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul), [@peterwilsoncc](https://github.com/peterwilsoncc) via [GHSA-c5vw-3gpx-gv22](https://github.com/10up/eight-day-week/security/advisories/GHSA-c5vw-3gpx-gv22)).
* **Added:** Expand E2E tests to increase coverage  (props [@sudip-md](https://github.com/sudip-md), [@jeffpaul](https://github.com/jeffpaul), [@iamdharmesh](https://github.com/iamdharmesh) via [#148](https://github.com/10up/eight-day-week/pull/148)).
* **Changed:** Bump WordPress "tested up to" version 6.8 (props [@Sourabh208](https://github.com/Sourabh208), [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#171](https://github.com/10up/eight-day-week/pull/171)).
* **Changed:** Bump tar-fs from 2.1.1 to 2.1.2 (props [@dependabot](https://github.com/apps/dependabot), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#170](https://github.com/10up/eight-day-week/pull/170)).
* **Changed:** Update CTA to Fueled (props [@jeffpaul](https://github.com/jeffpaul) via [#6ce451a2](https://github.com/10up/eight-day-week/commit/6ce451a22ad65b8518200e81fe33c2d65d448d9d)).

= 1.2.5 - 2025-02-03 =
**Note that this release bumps the WordPress minimum version from 5.7 to 6.5.**

* **Added:** Documentation updates comparing Classic Editor vs. Gutenberg (Block Editor) XML exports for InDesign imports (props [@frankiebordone](https://github.com/frankiebordone), [@dkotter](https://github.com/dkotter) via [#159](https://github.com/10up/eight-day-week/pull/159), [#160](https://github.com/10up/eight-day-week/pull/160)).
* **Changed:** Bump WordPress "tested up to" version to 6.7 (props [@qasumitbagthariya](https://github.com/qasumitbagthariya), [@sudip-md](https://github.com/sudip-md), [@zamanq](https://github.com/zamanq), [@jeffpaul](https://github.com/jeffpaul) via [#144](https://github.com/10up/eight-day-week/pull/144), [#152](https://github.com/10up/eight-day-week/pull/152), [#153](https://github.com/10up/eight-day-week/pull/153), [#163](https://github.com/10up/eight-day-week/pull/163), [#165](https://github.com/10up/eight-day-week/pull/165)).
* **Changed:** Bump WordPress minimum from 5.7 to 6.5 (props [@qasumitbagthariya](https://github.com/qasumitbagthariya), [@sudip-md](https://github.com/sudip-md), [@zamanq](https://github.com/zamanq), [@jeffpaul](https://github.com/jeffpaul) via [#144](https://github.com/10up/eight-day-week/pull/144), [#152](https://github.com/10up/eight-day-week/pull/152), [#153](https://github.com/10up/eight-day-week/pull/153)).
* **Security:** Bump `braces` from 3.0.2 to 3.0.3 and `ws` from 6.2.2 to 6.2.3 (props [@dependabot](https://github.com/apps/dependabot), [@iamdharmesh](https://github.com/iamdharmesh) via [#150](https://github.com/10up/eight-day-week/pull/150)).

= 1.2.4 - 2024-02-29 =
* **Added:** Support for the WordPress.org plugin preview (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#137](https://github.com/10up/eight-day-week/pull/137)).
* **Changed:** Bump WordPress "tested up to" version to 6.4 (props [@dhanendran](https://github.com/dhanendran), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#136](https://github.com/10up/eight-day-week/pull/136)).
* **Fixed:** Undefined array key PHP warning (props [@dhanendran](https://github.com/dhanendran), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#136](https://github.com/10up/eight-day-week/pull/136)).

[View historical changelog details here](https://github.com/10up/eight-day-week/blob/develop/CHANGELOG.md).

== Upgrade Notice ==

= 1.3.0 =

This includes a security update. Please update promptly.

= 1.2.6 =

This includes a security update for a data exposure issue to authenticated users. Please upgrade promptly.

= 1.2.5 =

Note that this release bumps the WordPress minimum version from 5.7 to 6.5

= 1.2.1 =

* Note that this version bumps the minimum PHP version from 5.6 to 7.4 and the minimum WordPress version from 4.6 to 5.7.
