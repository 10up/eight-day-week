# Changelog

All notable changes to this project will be documented in this file, per [the Keep a Changelog standard](http://keepachangelog.com/).

## [Unreleased]

## [1.2.0] - 2021-06-21
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
[1.2.0]: https://github.com/10up/eight-day-week/compare/1.1.3...1.2.0
[1.1.3]: https://github.com/10up/eight-day-week/compare/1.1.2...1.1.3
[1.1.2]: https://github.com/10up/eight-day-week/compare/1.1.1...1.1.2
[1.1.1]: https://github.com/10up/eight-day-week/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/10up/eight-day-week/compare/9057a7f...1.1.0
[1.0.0]: https://github.com/10up/eight-day-week/commit/9057a7f310068676ef8a15e0ba0a395273f1cb98
