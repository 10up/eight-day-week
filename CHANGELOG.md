# Changelog

All notable changes to this project will be documented in this file, per [the Keep a Changelog standard](http://keepachangelog.com/).

## [Unreleased]

## [1.3.0] - 2025-05-xx
**Note that this release bumps the WordPress minimum version from 5.5 to 6.7.**

### Security
- Resolve GHSA-88cc-x3jq-vwjx (props [@dkotter](https://github.com/dkotter), [@peterwilsoncc](https://github.com/peterwilsoncc), [@jeffpaul](https://github.com/jeffpaul) via [GHSA-88cc-x3jq-vwjx](https://github.com/10up/eight-day-week/security/advisories/GHSA-88cc-x3jq-vwjx)).
- Bump `phpunit/phpunit` from 9.5.28 to 9.6.33 (props [@dkotter](https://github.com/dkotter), [@dependabot](https://github.com/apps/dependabot) via [#189](https://github.com/10up/eight-day-week/pull/189)).
- Bump `immutable` from 5.1.4 to 5.1.5 (props [@dkotter](https://github.com/dkotter), [@dependabot](https://github.com/apps/dependabot) via [#192](https://github.com/10up/eight-day-week/pull/192)).

### Changed
- Update to support WordPress 7.0 (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#183](https://github.com/10up/eight-day-week/pull/183), [#199](https://github.com/10up/eight-day-week/pull/199)).
- Minimum supported version of WordPress is now 6.7 (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#184](https://github.com/10up/eight-day-week/pull/184)).
- Update npm dependencies (props [@dkotter](https://github.com/dkotter), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#186](https://github.com/10up/eight-day-week/pull/186)).
- Bump `lodash` from 4.17.21 to 4.17.23 (props [@dkotter](https://github.com/dkotter), [@dependabot](https://github.com/apps/dependabot) via [#187](https://github.com/10up/eight-day-week/pull/187)).
- Bump `qs` from 6.14.1 to 6.14.2 (props [@dkotter](https://github.com/dkotter), [@dependabot](https://github.com/apps/dependabot) via [#190](https://github.com/10up/eight-day-week/pull/190)).
- Bump `systeminformation` from 5.30.5 to 5.31.6 (props [@dkotter](https://github.com/dkotter), [@dependabot](https://github.com/apps/dependabot) via [#191](https://github.com/10up/eight-day-week/pull/191), [#201](https://github.com/10up/eight-day-week/pull/201)).
- Bump `picomatch` from 4.0.3 to 4.0.4, `postcss` from 8.5.6 to 8.5.14, `simple-git` from 3.30.0 to 3.36.0 (props [@dkotter](https://github.com/dkotter), [@dependabot](https://github.com/apps/dependabot) via [#200](https://github.com/10up/eight-day-week/pull/200)).

### Developer
- Bump `@wordpress/env` from 10.38.0 to 11.5.0, `autoprefixer` from 10.4.23 to 10.5.0, `cypress` from 15.9.0 to 15.14.2, `cypress-mochawesome-reporter` from 3.5.1 to 4.0.2, `grunt` from 1.6.1 to 1.6.2, `grunt-contrib-qunit` from 3.1.0 to 10.2.0, `grunt-sass` from 4.0.1 to 4.1.0, `mochawesome-json-to-md` from 0.7.2 to 2.2.0 and `sass` from 1.97.2 to 1.99.0 (props [@dkotter](https://github.com/dkotter), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#198](https://github.com/10up/eight-day-week/pull/198)).
- Add a `.nvmrc` file to ensure we are using a correct node version (props [@dkotter](https://github.com/dkotter), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#198](https://github.com/10up/eight-day-week/pull/198)).
- Ensure our GitHub Action workflows are all up-to-date (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#194](https://github.com/10up/eight-day-week/pull/194)).
- Add new GitHub Action workflow to run Plugin Checks (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#194](https://github.com/10up/eight-day-week/pull/194)).
- Address all existing PHPCS warnings and errors (props [@dkotter](https://github.com/dkotter), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#195](https://github.com/10up/eight-day-week/pull/195)).
- Fix errors being flagged by WordPress plugin check (props [@dkotter](https://github.com/dkotter), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#196](https://github.com/10up/eight-day-week/pull/196)).


## [1.2.6] - 2025-12-16
### Security
- Resolve GHSA-c5vw-3gpx-gv22 data exposure to authenticated users (thank you Patchstack for responsibly disclosing this issue; props [@kmgalanakis](https://github.com/kmgalanakis), [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul), [@peterwilsoncc](https://github.com/peterwilsoncc) via [GHSA-c5vw-3gpx-gv22](https://github.com/10up/eight-day-week/security/advisories/GHSA-c5vw-3gpx-gv22)).

### Added
- Expand E2E tests to increase coverage  (props [@sudip-md](https://github.com/sudip-md), [@jeffpaul](https://github.com/jeffpaul), [@iamdharmesh](https://github.com/iamdharmesh) via [#148](https://github.com/10up/eight-day-week/pull/148)).

### Changed
- Bump WordPress "tested up to" version 6.8 (props [@Sourabh208](https://github.com/Sourabh208), [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#171](https://github.com/10up/eight-day-week/pull/171)).
- Bump tar-fs from 2.1.1 to 2.1.2 (props [@dependabot](https://github.com/apps/dependabot), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#170](https://github.com/10up/eight-day-week/pull/170)).
- Update CTA to Fueled (props [@jeffpaul](https://github.com/jeffpaul) via [#6ce451a2](https://github.com/10up/eight-day-week/commit/6ce451a22ad65b8518200e81fe33c2d65d448d9d)).

### Developer
- Update all third-party actions our workflows rely on to use versions based on specific commit hashes (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#168](https://github.com/10up/eight-day-week/pull/168)).
- Update workflow permissions for various GitHub actions (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#176](https://github.com/10up/eight-day-week/pull/176), [#177](https://github.com/10up/eight-day-week/pull/177)).

## [1.2.5] - 2025-02-03
**Note that this release bumps the WordPress minimum version from 5.7 to 6.5.**

### Added
- Documentation updates comparing Classic Editor vs. Gutenberg (Block Editor) XML exports for InDesign imports (props [@frankiebordone](https://github.com/frankiebordone), [@dkotter](https://github.com/dkotter) via [#159](https://github.com/10up/eight-day-week/pull/159), [#160](https://github.com/10up/eight-day-week/pull/160)).

### Changed
- Bump WordPress "tested up to" version to 6.7 (props [@qasumitbagthariya](https://github.com/qasumitbagthariya), [@sudip-md](https://github.com/sudip-md), [@zamanq](https://github.com/zamanq), [@jeffpaul](https://github.com/jeffpaul) via [#144](https://github.com/10up/eight-day-week/pull/144), [#152](https://github.com/10up/eight-day-week/pull/152), [#153](https://github.com/10up/eight-day-week/pull/153), [#163](https://github.com/10up/eight-day-week/pull/163), [#165](https://github.com/10up/eight-day-week/pull/165)).
- Bump WordPress minimum from 5.7 to 6.5 (props [@qasumitbagthariya](https://github.com/qasumitbagthariya), [@sudip-md](https://github.com/sudip-md), [@zamanq](https://github.com/zamanq), [@jeffpaul](https://github.com/jeffpaul) via [#144](https://github.com/10up/eight-day-week/pull/144), [#152](https://github.com/10up/eight-day-week/pull/152), [#153](https://github.com/10up/eight-day-week/pull/153)).

### Security
- Bump `braces` from 3.0.2 to 3.0.3 and `ws` from 6.2.2 to 6.2.3 (props [@dependabot](https://github.com/apps/dependabot), [@iamdharmesh](https://github.com/iamdharmesh) via [#150](https://github.com/10up/eight-day-week/pull/150)).

### Developer
- Upgrade `download-artifact` from v3 to v4 (props [@iamdharmesh](https://github.com/iamdharmesh), [@jeffpaul](https://github.com/jeffpaul) via [#142](https://github.com/10up/eight-day-week/pull/142)).
- Replaced `lee-dohm/no-response` with `actions/stale` to help with closing no-response/stale issues (props [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter) via [#143](https://github.com/10up/eight-day-week/pull/143)).
- Add "Testing" section to the "CONTRIBUTING.md" file (props [@kmgalanakis](https://github.com/kmgalanakis) via [#145](https://github.com/10up/eight-day-week/pull/145)).
- Add Repo Automator GitHub Action (props [@iamdharmesh](https://github.com/iamdharmesh), [@jeffpaul](https://github.com/jeffpaul) via [#146](https://github.com/10up/eight-day-week/pull/146)).
- Update README with WordPress Playground badge, banner image and other minor updates (props [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter) via [#156](https://github.com/10up/eight-day-week/pull/156), [#157](https://github.com/10up/eight-day-week/pull/157), [#158](https://github.com/10up/eight-day-week/pull/158)).

## [1.2.4] - 2024-02-29
### Added
- Support for the WordPress.org plugin preview (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#137](https://github.com/10up/eight-day-week/pull/137)).

### Changed
- Bump WordPress "tested up to" version to 6.4 (props [@dhanendran](https://github.com/dhanendran), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#136](https://github.com/10up/eight-day-week/pull/136)).

### Fixed
- Undefined array key PHP warning (props [@dhanendran](https://github.com/dhanendran), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#136](https://github.com/10up/eight-day-week/pull/136)).

## [1.2.3] - 2023-09-20
### Added
- Error handling for environments that don't match our minimum PHP version (props [@bmarshall511](https://github.com/bmarshall511), [@iamdharmesh](https://github.com/iamdharmesh), [@dkotter](https://github.com/dkotter), [@vikrampm1](https://github.com/vikrampm1) via [#132](https://github.com/10up/eight-day-week/pull/132)).

### Fixed
- Ensure multiple articles can be saved within each Print Issue section (props [@dkotter](https://github.com/dkotter), [@xLesy](https://github.com/xLesy), [@iamdharmesh](https://github.com/iamdharmesh) via [#131](https://github.com/10up/eight-day-week/pull/131)).
- Ensure the article status shows correctly and can be bulk edited (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh) via [#131](https://github.com/10up/eight-day-week/pull/131)).
- Ensure our E2E tests run properly on Cypress 13 (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh) via [#131](https://github.com/10up/eight-day-week/pull/131)).

### Security
- Bump `@cypress/request` from 2.88.12 to 3.0.1 and `cypress` from 10.3.0 to 13.1.0 (props [@dependabot](https://github.com/apps/dependabot) via [#129](https://github.com/10up/eight-day-week/pull/129)).

## [1.2.2] - 2023-09-06
### Added
- Add proper labels to the Issue Status taxonomy (props [@jayedul](https://github.com/jayedul), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#112](https://github.com/10up/eight-day-week/pull/112)).
- Ensure images and their captions are included in the export file (props [@bmarshall511](https://github.com/bmarshall511), [@peterwilsoncc](https://github.com/peterwilsoncc), [@sksaju](https://github.com/sksaju), [@dkotter](https://github.com/dkotter) via [#117](https://github.com/10up/eight-day-week/pull/117)).
- Github Action to check for PHP coding and compatibility standards (props [@Sidsector9](https://github.com/Sidsector9), [@jeffpaul](https://github.com/jeffpaul), [@cadic](https://github.com/cadic), [@faisal-alvi](https://github.com/faisal-alvi) via [#109](https://github.com/10up/eight-day-week/pull/109)).
- GitHub Action summary added to our Cypress E2E test reports (props [@jayedul](https://github.com/jayedul), [@ravinderk](https://github.com/ravinderk) via [#119](https://github.com/10up/eight-day-week/pull/119))

### Changed
- Run our E2E tests on the zip that is generated by our `Build release zip` action (props [@jayedul](https://github.com/jayedul), [@dkotter](https://github.com/dkotter) via [#113](https://github.com/10up/eight-day-week/pull/113)).
- Bump WordPress "tested up to" version to 6.3 (props [@jayedul](https://github.com/jayedul), [@dkotter](https://github.com/dkotter), [@zamanq](https://github.com/zamanq), [@faisal-alvi](https://github.com/faisal-alvi) via [#102](https://github.com/10up/eight-day-week/pull/102), [#115](https://github.com/10up/eight-day-week/pull/115), [#127](https://github.com/10up/eight-day-week/pull/127), [#128](https://github.com/10up/eight-day-week/pull/128)).
- Update the Dependency Review GitHub Action (props [@jeffpaul](https://github.com/jeffpaul) via [#116](https://github.com/10up/eight-day-week/pull/116)).

### Fixed
- Various code updates to ensure we better conform to the WordPress coding standards (props [@bmarshall511](https://github.com/bmarshall511), [@Sidsector9](https://github.com/Sidsector9) via [#120](https://github.com/10up/eight-day-week/pull/120)).
- Ensure proper message is shown when changing a user's print role (props [@zamanq](https://github.com/zamanq), [@faisal-alvi](https://github.com/faisal-alvi) via [#128](https://github.com/10up/eight-day-week/pull/128)).

### Security
- Bump `simple-git` from 3.15.1 to 3.16.0 (props [@dependabot](https://github.com/apps/dependabot) via [#110](https://github.com/10up/eight-day-week/pull/110)).
- Bump `http-cache-semantics` from 4.1.0 to 4.1.1 (props [@dependabot](https://github.com/apps/dependabot) via [#111](https://github.com/10up/eight-day-week/pull/111)).
- Bump `tough-cookie` from 2.5.0 to 4.1.3 and `@cypress/request` from 2.88.10 to 2.88.12 (props [@dependabot](https://github.com/apps/dependabot) via [#122](https://github.com/10up/eight-day-week/pull/122)).

## [1.2.1] - 2023-01-09
**Note that this release bumps the WordPress minimum version from 4.6 to 5.7 and the PHP minimum version from 5.6 to 7.4.**

### Added
- Setup E2E tests using Cypress (props [@dhanendran](https://github.com/dhanendran), [@iamdharmesh](https://github.com/iamdharmesh) via [#92](https://github.com/10up/eight-day-week/pull/92)).
- Filter example usages from the Observer (props [@jeffpaul](https://github.com/jeffpaul), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#97](https://github.com/10up/eight-day-week/pull/97)).

### Changed
- Bump WordPress minimum version from 4.6 to 5.7 and PHP minimum version from 5.6 to 7.4 (props [@zamanq](https://github.com/zamanq), [@cadic](https://github.com/cadic), [@jeffpaul](https://github.com/jeffpaul) via [#96](https://github.com/10up/eight-day-week/pull/96)).
- Update Support Level from `Active` to `Stable` (props [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter) via [#94](https://github.com/10up/eight-day-week/pull/94)).
- Bump WordPress "tested up to" version to 6.1 (props [@jayedul](https://github.com/jayedul), [@dkotter](https://github.com/dkotter) via [#102](https://github.com/10up/eight-day-week/pull/102)).

### Security
- Remove `shelljs` and bump `grunt-contrib-jshint` from 2.1.0 to 3.2.0 (props [@dependabot](https://github.com/apps/dependabot) via [#99](https://github.com/10up/eight-day-week/pull/99)).
- Bump `got` from 10.7.0 to 11.8.5 and `@wordpress/env` from 4.9.0 to 5.7.0 (props [@dependabot](https://github.com/apps/dependabot) via [#100](https://github.com/10up/eight-day-week/pull/100)).
- Bump `simple-git` from 3.10.0 to 3.15.1 (props [@dependabot](https://github.com/apps/dependabot) via [#103](https://github.com/10up/eight-day-week/pull/103)).

## [1.2.0] - 2022-06-23
### Added
- Dependency security scanning (props [@jeffpaul](https://github.com/jeffpaul), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#81](https://github.com/10up/eight-day-week/pull/81)).

### Changed
- Bump WordPress version "tested up to" 6.0 (props [@jeffpaul](https://github.com/jeffpaul), [@mohitwp](https://github.com/mohitwp), [@peterwilsoncc](https://github.com/peterwilsoncc), [@cadic](https://github.com/cadic), [@dinhtungdu](https://github.com/dinhtungdu), [@vikrampm1](https://github.com/vikrampm1) via [#78](https://github.com/10up/eight-day-week/pull/78), [#86](https://github.com/10up/eight-day-week/pull/86), [#87](https://github.com/10up/eight-day-week/pull/87)).

### Security
- Bump `simple-get` from 3.1.0 to 3.1.1 (props [@dependabot](https://github.com/apps/dependabot) via [#82](https://github.com/10up/eight-day-week/pull/82)).
- Bump `grunt` from 1.3.0 to 1.5.3 (props [@dependabot](https://github.com/apps/dependabot) via [#84](https://github.com/10up/eight-day-week/pull/84), [#88](https://github.com/10up/eight-day-week/pull/88)).

## [1.1.3] - 2021-12-15
### Changed
- Bump WordPress version "tested up to" 5.8 (props [@barneyjeffries](https://github.com/barneyjeffries), [@jeffpaul](https://github.com/jeffpaul) via [#74](https://github.com/10up/eight-day-week/pull/74)).

### Fixed
- Windows compatibility: Use `DIRECTORY_SEPARATOR` instead of slash in filepaths (props [@mnelson4](https://github.com/mnelson4), [@dinhtungdu](https://github.com/dinhtungdu), [@Intelligent2013](https://github.com/Intelligent2013), [@samthinkbox](https://github.com/samthinkbox) via [#73](https://github.com/10up/eight-day-week/pull/73)).

### Security
- Bump `bl` from 1.2.2 to 1.2.3 (props [@dependabot](https://github.com/apps/dependabot) via [#66](https://github.com/10up/eight-day-week/pull/66)).
- Bump `ini` from 1.3.5 to 1.3.7 (props [@dependabot](https://github.com/apps/dependabot) via [#67](https://github.com/10up/eight-day-week/pull/67)).
- Bump `grunt` from 1.0.4 to 1.3.0 (props [@dependabot](https://github.com/apps/dependabot) via [#69](https://github.com/10up/eight-day-week/pull/69)).
- Bump `lodash` from 4.17.19 to 4.17.21 (props [@dependabot](https://github.com/apps/dependabot) via [#70](https://github.com/10up/eight-day-week/pull/70)).
- Bump `ws` from 6.2.1 to 6.2.2 (props [@dependabot](https://github.com/apps/dependabot) via [#71](https://github.com/10up/eight-day-week/pull/71)).
- Bump `path-parse` from 1.0.6 to 1.0.7 (props [@dependabot](https://github.com/apps/dependabot) via [#72](https://github.com/10up/eight-day-week/pull/72)).

## [1.1.2] - 2020-10-08
### Changed
- Plugin documentation and screenshots (props [@jeffpaul](https://github.com/jeffpaul) via [#56](https://github.com/10up/eight-day-week/pull/56), [#61](https://github.com/10up/eight-day-week/pull/61)).

### Removed
- Translation files as this is now handled on [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/eight-day-week-print-workflow/) (props [@jeffpaul](https://github.com/jeffpaul), [@helen](https://github.com/helen) via [#60](https://github.com/10up/eight-day-week/pull/60)).

### Fixed
- Unable to change role using upper Print Role dropdown (props [@dinhtungdu](https://github.com/dinhtungdu) via [#58](https://github.com/10up/eight-day-week/pull/58)).
- Display correct title when creating a new Section in Print Issues (props [@dinhtungdu](https://github.com/dinhtungdu) via [#62](https://github.com/10up/eight-day-week/pull/62)).

### Security
- Bump `websocket-extensions` from 0.1.3 to 0.1.4 (props [@dependabot](https://github.com/apps/dependabot) via [#55](https://github.com/10up/eight-day-week/pull/55)).
- Bump `lodash` from 4.17.15 to 4.17.19 (props [@dependabot](https://github.com/apps/dependabot) via [#59](https://github.com/10up/eight-day-week/pull/59)).

## [1.1.1] - 2019-11-22
### Changed
- Bump WordPress version "tested up to" 5.3 (props [@adamsilverstein](https://github.com/adamsilverstein) via [#45](https://github.com/10up/eight-day-week/pull/45)).
- Documentation and deploy automation updates (props [@jeffpaul](https://github.com/jeffpaul) via [#38](https://github.com/10up/eight-day-week/pull/38), [#39](https://github.com/10up/eight-day-week/pull/39), [#42](https://github.com/10up/eight-day-week/pull/42), [#46](https://github.com/10up/eight-day-week/pull/46), [#48](https://github.com/10up/eight-day-week/pull/48), [#49](https://github.com/10up/eight-day-week/pull/49), [#50](https://github.com/10up/eight-day-week/pull/50)).

### Fixed
- WordPress.org translation readiness (props [@jeffpaul](https://github.com/jeffpaul), [@adamsilverstein](https://github.com/adamsilverstein), [@helen](https://github.com/helen) via [#41](https://github.com/10up/eight-day-week/pull/41)).

## [1.1.0] - 2019-07-26
### Added
- German translation files (props [@adamsilverstein](https://github.com/adamsilverstein), [@maryisdead](https://github.com/maryisdead) via [#31](https://github.com/10up/eight-day-week/pull/31)).
- Plugin banner and icon images (props [@chriswallace](https://github.com/chriswallace) via [#30](https://github.com/10up/eight-day-week/pull/30)).

### Updated
- Update dependencies in `package.json` and `composer.json` to current versions (props [@adamsilverstein](https://github.com/adamsilverstein) via [#28](https://github.com/10up/eight-day-week/pull/28)).

### Fixed
- DateTimeZone setup: fall back to `gmt_offset` (props [@adamsilverstein](https://github.com/adamsilverstein), [@Jared-Williams](https://github.com/Jared-Williams) via [#32](https://github.com/10up/eight-day-week/pull/32)).
- PHP notices w/PHP 5.6 and fatals with PHP 7.2/3 (props [@adamsilverstein](https://github.com/adamsilverstein) via [#28](https://github.com/10up/eight-day-week/pull/28)).

## [1.0.0] - 2015-11-16
- Initial Release

[Unreleased]: https://github.com/10up/eight-day-week/compare/trunk...develop
[1.3.0]: https://github.com/10up/eight-day-week/compare/1.2.6...1.3.0
[1.2.6]: https://github.com/10up/eight-day-week/compare/1.2.5...1.2.6
[1.2.5]: https://github.com/10up/eight-day-week/compare/1.2.4...1.2.5
[1.2.4]: https://github.com/10up/eight-day-week/compare/1.2.3...1.2.4
[1.2.3]: https://github.com/10up/eight-day-week/compare/1.2.2...1.2.3
[1.2.2]: https://github.com/10up/eight-day-week/compare/1.2.1...1.2.2
[1.2.1]: https://github.com/10up/eight-day-week/compare/1.2.0...1.2.1
[1.2.0]: https://github.com/10up/eight-day-week/compare/1.1.3...1.2.0
[1.1.3]: https://github.com/10up/eight-day-week/compare/1.1.2...1.1.3
[1.1.2]: https://github.com/10up/eight-day-week/compare/1.1.1...1.1.2
[1.1.1]: https://github.com/10up/eight-day-week/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/10up/eight-day-week/compare/9057a7f...1.1.0
[1.0.0]: https://github.com/10up/eight-day-week/commit/9057a7f310068676ef8a15e0ba0a395273f1cb98
