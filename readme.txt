=== Eight Day Week Print Workflow ===
Contributors: 10up, observerteam, joshlevinson, BrentSchultz
Tags: print, workflow, editorial
Requires at least: 4.6
Tested up to: 5.5
Stable tag: 1.1.2
Requires PHP: 5.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

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

**XML Export to InDesign**

- Export XML files specifically formatted for import into InDesign

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

== Known Caveats/Issues ==

**Gutenberg exports**
Gutenberg-based exports include some additional metadata/details that a Classic Editor-based export does not.  [Gutenberg stores block data in HTML comments](https://developer.wordpress.org/block-editor/key-concepts/#delimiters-and-parsing-expression-grammar), so you'll notice those comments (in the form of `<!-- "Gutenberg block data" -->`) appearing in the Eight Day Week XML export.  Note that the XML is still valid--you can test and confirm that yourself using an [XML validator](https://www.xmlvalidation.com/)--though depending on your version of InDesign you may get different results upon importing a Gutenberg export compared to a Classic Editor export.  Our testing showed that those HTML comments in a Gutenberg export did not affect the import into InDesign however.  You can test how this works in your version of InDesign with these sample XML files: [Gutenberg XML](https://raw.githubusercontent.com/wiki/10up/eight-day-week/BlockEditor-sample.xml), [Classic Editor XML](https://raw.githubusercontent.com/wiki/10up/eight-day-week/ClassicEditor-sample.xml).  You can also test how this works with full ZIP exports of Print Issues containing a [Block Editor sample](https://raw.githubusercontent.com/wiki/10up/eight-day-week/BlockEditor-SampleExport.zip) or a [Classic Editor sample](https://raw.githubusercontent.com/wiki/10up/eight-day-week/ClassicEditor-SampleExport.zip).

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

= 1.1.2 =
* **Changed:** Plugin documentation and screenshots (props [@jeffpaul](https://profiles.wordpress.org/jeffpaul)).
* **Removed:** Translation files as this is now handled on [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/eight-day-week-print-workflow/) (props [@jeffpaul](https://profiles.wordpress.org/jeffpaul), [@helen](https://profiles.wordpress.org/helen)).
* **Fixed:** Unable to change role using upper Print Role dropdown (props [@dinhtungdu](https://profiles.wordpress.org/dinhtungdu)).
* **Fixed:** Display correct title when creating a new Section in Print Issues (props [@dinhtungdu](https://profiles.wordpress.org/dinhtungdu)).
* **Security:** Bump `websocket-extensions` from 0.1.3 to 0.1.4 (props [@dependabot](https://github.com/apps/dependabot)).
* **Security:** Bump `lodash` from 4.17.15 to 4.17.19 (props [@dependabot](https://github.com/apps/dependabot)).

= 1.1.1 =
* **Changed:** Bump WordPress version "tested up to" 5.3 (props [@adamsilverstein](https://profiles.wordpress.org/adamsilverstein)).
* **Changed:** Documentation and deploy automation updates (props [@jeffpaul](https://profiles.wordpress.org/jeffpaul)).
* **Fixed:** WordPress.org translation readiness (props [@jeffpaul](https://profiles.wordpress.org/jeffpaul), [@adamsilverstein](https://profiles.wordpress.org/adamsilverstein), [@helen](https://profiles.wordpress.org/helen)).

= 1.1.0 =
* **Added:** German translation files (props [@adamsilverstein](https://profiles.wordpress.org/adamsilverstein), Matthias Wehrlein).
* **Added:** Plugin banner and icon images (props [@chriswallace](https://profiles.wordpress.org/chriswallace)).
* **Updated:** Update dependencies in `package.json` and `composer.json` to current versions (props [@adamsilverstein](https://profiles.wordpress.org/adamsilverstein)).
* **Fixed:** DateTimeZone setup: fall back to `gmt_offset` (props [@adamsilverstein](https://profiles.wordpress.org/adamsilverstein), Jared Williams).
* **Fixed:** PHP notices w/PHP 5.6 and fatals with PHP 7.2/3 (props [@adamsilverstein](https://profiles.wordpress.org/adamsilverstein)).

= 1.0.0 =
* Initial Release.
