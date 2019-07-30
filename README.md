# ![Eight Day Week Print Workflow](https://github.com/10up/eight-day-week/blob/develop/.wordpress-org/banner-1544x500.png "Eight Day Week Print Workflow")

> Optimize publication workflows by using WordPress as your print CMS.  Eight Day Week provides a set of tools to manage your print workflow directly in your WordPress dashboard–right where your posts are!  Primarily, it offers an interface to group, label, and manage the workflow status of posts in a printed "Issue".

[![Support Level](https://img.shields.io/badge/support-active-green.svg)](#support-level) [![Release Version](https://img.shields.io/github/release/10up/eight-day-week.svg)](https://github.com/10up/eight-day-week/releases/latest) ![WordPress tested up to version](https://img.shields.io/badge/WordPress-v4.4%20tested-success.svg) [![GPLv2 License](https://img.shields.io/github/license/10up/eight-day-week.svg)](https://github.com/10up/eight-day-week/blob/develop/LICENSE.md)

## Table of Contents  
* [Features](#features)
* [Requirements](#requirements)
* [Installation](#installation)
* [Filters & Hooks](#filters--hooks)
* [Screenshots](#screenshots)
* [Changelog](#changelog)
* [Contributing](#contributing)

## Features

### Create "Print Issues"

- Add and order sections, and articles within sections
- Assign article statuses specific to your print workflow

![The Print Issue editor, showing the sections and contained articles, as well as several modules at play.](http://ps.w.org/eight-day-week-print-workflow/assets/screenshot-2.png)

### Limit access to Print Roles

Two custom roles are added by this plugin to best model a real-world print team.

- The Print Editor role offers full access to the creation interfaces, such as Print Issue, Article Status, Print Publication, etc.
- The Print Production role offers read-only access to a Print Issues. The XML export tool is also available to Production users.

### View a Print Issue in "Read Only" mode

- Circumvents the post locking feature by offering a read-only view of a print issue

![A Print Issue in "read only view". This view allows anyone with access to view a Print Issue without inducing a post lock, or being locked out by another editor. Note that the Export tools are still available in read only view.](http://ps.w.org/eight-day-week-print-workflow/assets/screenshot-4.png)

### XML Export to InDesign

- Export XML files specifically formatted for import into InDesign

## Requirements

* PHP 5.6+
* [WordPress](http://wordpress.org) 4.3+

## Installation

Eight Day Week has no settings or configurations to set up. It just works!

## Filters & Hooks
Eight Day Week provides a number of filters and hooks for customizing and extending the plugin.

### Modules

Eight Day Week follows a module-style approach to many of its features. These can be turned on or off via filters, and all work independently.
These are:
Article Byline
Article Count
Article Export
Article Status
Issue Publication
Issue Status
Any of these can be disabled by returning a falsey value from the following filter format:
`add_filter( 'Eight_Day_Week\Plugins\load_$plugin', '__return_false' );`
The `$plugin` value is a slug version of the plugin name, i.e. article-byline.

### Article Table

The information displayed in the list of articles within a Print Issue is filterable. Custom columns can be added via the following filters: `Eight_Day_Week\Articles\article_columns` and `Eight_Day_Week\Articles\article_meta_$column_name`.

Sample usage:

	add_filter( 'Eight_Day_Week\Articles\article_columns', function( $columns ) {
	    $columns['byline'] = _x( 'Byline', 'Label for multiple, comma separated authors', 'your-text-domain' );
	    return $columns;
	} );
	add_filter( 'Eight_Day_Week\Articles\article_meta_byline', function( $incoming_value, $post ) {
	    return implode( ', ', wp_list_pluck( my_get_post_authors_function( $post ), 'display_name' ) );
	}

![Further down the page of the Print Issue Editor, showing buttons to add sections & articles. Each section has a Save button for convenience; all Save buttons simply save the entire Issue.](http://ps.w.org/eight-day-week-print-workflow/assets/screenshot-3.png)

### Print Issue Table

The information displayed in the list of Print Issues is filterable. Custom columns can be added via the following filter:
`Eight_Day_Week\Print_Issue_Columns\pi_columns`.  Note that this is a convenience filter, the base filter is `manage_edit-print-issue_columns`.  See `includes/functions/print-issue-columns.php` for sample usage.

![The Print Issue list table](http://ps.w.org/eight-day-week-print-workflow/assets/screenshot-1.png)

### Article Export

The export of posts in a Print Issue is highly customizeable, from the file name of the zip, to the file name of the individual files, to the contents of the files themselves.  The best reference would be to read through `includes/functions/plugins/article-export.php`.  [Here's](https://gist.github.com/joshlevinson/4a2c3ed78b21b3c54eba) a few examples used on the *Observer*.

## Support Level

**Active:** 10up is actively working on this, and we expect to continue work for the foreseeable future including keeping tested up to the most recent version of WordPress.  Bug reports, feature requests, questions, and pull requests are welcome.

## Changelog

A complete listing of all notable changes to Eight Day Week are documented in [CHANGELOG.md](https://github.com/10up/eight-day-week/blob/develop/CHANGELOG.md).

## Contributing

Please read [CODE_OF_CONDUCT.md](https://github.com/10up/eight-day-week/blob/develop/CODE_OF_CONDUCT.md) for details on our code of conduct, [CONTRIBUTING.md](https://github.com/10up/eight-day-week/blob/develop/CONTRIBUTING.md) for details on the process for submitting pull requests to us, and [CONTRIBUTORS.md](https://github.com/10up/eight-day-week/blob/develop/CONTRIBUTORS.md) for a listing of contributors to Eight Day Week.

## Like what you see?

<a href="http://10up.com/contact/"><img src="https://10updotcom-wpengine.s3.amazonaws.com/uploads/2016/10/10up-Github-Banner.png" width="850" alt="Work with us at 10up"></a>
