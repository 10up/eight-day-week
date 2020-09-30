# Changelog

All notable changes to this project will be documented in this file, per [the Keep a Changelog standard](http://keepachangelog.com/).

## [Unreleased]

## [1.1.2] - TBD
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
[1.1.2]: https://github.com/10up/eight-day-week/compare/1.1.1...1.1.2
[1.1.1]: https://github.com/10up/eight-day-week/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/10up/eight-day-week/compare/9057a7f...1.1.0
[1.0.0]: https://github.com/10up/eight-day-week/commit/9057a7f310068676ef8a15e0ba0a395273f1cb98
