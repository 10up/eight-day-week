# Contributing and Maintaining

First, thank you for taking the time to contribute!

The following is a set of guidelines for contributors as well as information and instructions around our maintenance process.  The two are closely tied together in terms of how we all work together and set expectations, so while you may not need to know everything in here to submit an issue or pull request, it's best to keep them in the same document.

## Ways to contribute

Contributing isn't just writing code - it's anything that improves the project.  All contributions are managed right here on GitHub.  Here are some ways you can help:

### Reporting bugs

If you're running into an issue, please take a look through [existing issues](/issues) and [open a new one](/issues/new) if needed.  If you're able, include steps to reproduce, environment information, and screenshots/screencasts as relevant.

### Suggesting enhancements

New features and enhancements are also managed via [issues](/issues).

### Pull requests

Pull requests represent a proposed solution to a specified problem.  They should always reference an issue that describes the problem and contains discussion about the problem itself.  Discussion on pull requests should be limited to the pull request itself, i.e. code review.

For more on how 10up writes and manages code, check out our [10up Engineering Best Practices](https://10up.github.io/Engineering-Best-Practices/).

## Workflow

The `develop` branch is the development branch which means it contains the next version to be released.  `stable` contains the current latest release and `master` contains the corresponding stable development version.  Always work on the `develop` branch and open up PRs against `develop`.

## Release instructions

1. Starting from `develop`, cut a release branch named `release/X.Y.Z` for your changes.
2. Version bump: Bump the version number in `eight-day-week.php` if it does not already reflect the version being released.
3. Changelog: Add/update the changelog in both `readme.txt` and `CHANGELOG.md`
4. Update the `.pot` file by running `npm run makepot`.
5. Check to be sure any new files/paths that are unnecessary in the production version are included in `.github/action-release/rsync-filter.txt`.
6. Merge: Make a non-fast-forward merge from your release branch to `develop`, then do the same for `develop` into `master`. `master` contains the stable development version.
7. SVN update: Copy files over to the trunk folder of an SVN checkout of the plugin. If the plugin banner, icon, or screenshots have changed, copy those to the top-level assets folder. Commit those changes.
8. SVN tag: Make a folder inside `tags` with the current version number, copy the contents of trunk into it, and commit with the message Tagging X.Y.Z. There is also an SVN command for tagging; however, note that it runs on the remote and requires care because the entire WordPress.org plugins repo is actually single SVN repo.
9. Check WordPress.org: Ensure that the changes are live on https://wordpress.org/plugins/eight-day-week-print-workflow/. This may take a few minutes.
10. Git tag: Tag the release in Git and push the tag to GitHub. It should now appear under releases there as well.
11. Update the [X.Y.Z milestone](https://github.com/10up/eight-day-week/milestones) with release date and link to GitHub release, then close X.Y.Z milestone
12. If any open PRs which were milestoned for X.Y.Z do not make it into the release, update their milestone.
