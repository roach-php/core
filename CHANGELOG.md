# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.0] – 2024-01-05

### Added

- Added namespace to run and scheduler (#105) (167e824)
- Added `ResponseReceiving` and `ResponseReceived` events (96c9332)
- Allow request middleware to set a response to bypass downloader (#106) (d8ae43e)
- Added PHP 8.3 support (02772af)

### Fixed

- Fixed bug where integration tests wouldn't get run (33eb25e)

## [2.0.1] – 2023-02-17

### Fixed

- Fixed version incompatibility with `sebastian/version` depenency

## [2.0.0] – 2023-02-06

### Added

- Added `userAgent` option to `ExecuteJavascriptMiddleware` (#82)
- Added `delay` option to `ExecuteJavascriptMiddleware` (#72)

### Changed

- Dropped PHP 8.0 support
- Updated various dependencies

## [1.1.1] — 2022-09-09

### Changed

- `ExecuteJavascriptMiddleware` now uses `waitUntilNetworkIdle` before returning the response body (#56)

## [1.1.0] — 2022-06-22

### Added

- Added a way to define custom item classes as well as item processors which only process certain
  types of items (#47)

### Changed

- Fixed deprecation warning in console commands for `symfony/console:^6.1` (#44)

## [1.0.0] — 2022-04-19

### Added

- Added `Roach::collectSpider` method to start a spider run and return all scraped items.
- Added `array $context` parameter to `Roach::startSpider` and `Roach::collectSpider` to pass arbitrary
  context data to a spider when starting a run.
- Added `roach:run <spider>` command to start a spider through the CLI.
- Added `Roach::fake()` method to test that a run for a given spider was started

### Changed

- Requests dropped by downloader middleware are no longer affected by `requestDelay` (fixes #27)
- Move `spatie/browsershot` from a `require` to `suggest` as it's only necessary if the `ExecuteJavascriptMiddleware` is used.
  Remove `ext-exif` as a dependency for the same reason.

### Removed

- Removed default command from CLI. To start the REPL, you now need to explicitly invoke the `roach:shell <url>` command, instead.

## [0.2.0] - 2021-12-28

### Added

- Added `ExecuteJavascriptMiddleware` to retrieve a page’s body after executing Javascript (#7)

## [0.1.0] - 2021-12-27

### Added

- Initial release

[3.0.0]: https://github.com/roach-php/core/compare/2.0.1...3.0.0
[2.0.1]: https://github.com/roach-php/core/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/roach-php/core/compare/1.1.1...2.0.0
[1.1.1]: https://github.com/roach-php/core/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/roach-php/core/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/roach-php/core/compare/0.2.0...1.0.0
[0.2.0]: https://github.com/roach-php/core/compare/0.1.0...0.2.0
