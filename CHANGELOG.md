# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.2.0](https://github.com/roach-php/core/compare/v3.1.0...v3.2.0) (2024-04-04)


### Features

* add http error middleware ([f937d89](https://github.com/roach-php/core/commit/f937d89e0afd93d8f2272c89e724b52897d60690))
* enable http error middleware by default in BasicSpider ([450ed50](https://github.com/roach-php/core/commit/450ed504c556b2243ea7cc76f44161242a551a5a))

## [3.1.0](https://github.com/roach-php/core/compare/v3.0.1...v3.1.0) (2024-03-22)


### Features

* drop php 8.1 support ([738e5ee](https://github.com/roach-php/core/commit/738e5ee31258c7825ed98d643dc9d8e494833428))


### Miscellaneous Chores

* bump php version to 8.3 in pipeline ([8094704](https://github.com/roach-php/core/commit/80947047a2679395a4deda1375479f2414a77649))
* **deps-dev:** bump ergebnis/composer-normalize from 2.39.0 to 2.41.1 ([0754a57](https://github.com/roach-php/core/commit/0754a577a073f25fbbb725a8823991f1bebbdf82))
* **deps-dev:** bump ergebnis/php-cs-fixer-config from 6.11.0 to 6.20.0 ([d448bb2](https://github.com/roach-php/core/commit/d448bb242a11cd140eb00a4474d00e5219c9eea7))
* **deps-dev:** bump phpunit/phpunit from 10.4.2 to 10.5.9 ([97ec170](https://github.com/roach-php/core/commit/97ec17082766de667d0d7e51a68e337d22fcc66e))
* **deps-dev:** bump spatie/browsershot from 3.60.0 to 3.61.0 ([6f0c73e](https://github.com/roach-php/core/commit/6f0c73ef099bf7cf6f1b56693eb966bb6fc49e39))
* **deps-dev:** bump vimeo/psalm from 5.16.0 to 5.20.0 ([f71d19a](https://github.com/roach-php/core/commit/f71d19a995ab01a723d54b9610ab11d14f07aeb1))
* **deps:** bump guzzlehttp/guzzle from 7.8.0 to 7.8.1 ([bd2b7bb](https://github.com/roach-php/core/commit/bd2b7bbe4b1ff483bb26cdc658eaa879af66f0b9))
* **deps:** bump symfony/css-selector from 6.3.2 to 6.4.0 ([695119c](https://github.com/roach-php/core/commit/695119cd6e06563c0e5a8329b4f8f5cb56040996))
* **deps:** bump symfony/event-dispatcher from 6.3.2 to 6.4.2 ([b381b7e](https://github.com/roach-php/core/commit/b381b7e6e4e52f62c4960f795886eaef823c09e2))
* **deps:** bump symfony/options-resolver from 6.3.0 to 6.4.0 ([4a74369](https://github.com/roach-php/core/commit/4a743693b7fe0d0c6a8744375aa582102309c2a7))
* drop php 8.1 support ([ca85ab5](https://github.com/roach-php/core/commit/ca85ab53c93da374613a47251228bf1dd8715358))
* fix psalm errors ([653347a](https://github.com/roach-php/core/commit/653347a121d22438f23f38081a26208ca9389a10))

## [3.0.1](https://github.com/roach-php/core/compare/v3.0.0...v3.0.1) (2024-01-25)


### Bug Fixes

* fix psalm errors on latest release ([2aec066](https://github.com/roach-php/core/commit/2aec066f12d940440cbf63c8815a7182f3fc7613))
* fix race condition in SystemClock ([c9d7d46](https://github.com/roach-php/core/commit/c9d7d4661bc9af9569c59e31914fc7ac88b5a19b))

## [3.0.0](https://github.com/roach-php/core/compare/2.0.1...v3.0.0) (2024-01-05)


### ⚠ BREAKING CHANGES

* add namespace to run and scheduler ([#105](https://github.com/roach-php/core/issues/105))

### Features

* add namespace to run and scheduler ([#105](https://github.com/roach-php/core/issues/105)) ([167e824](https://github.com/roach-php/core/commit/167e824362a9507caa3cc03200eb546d51e35437))
* add request proxy middleware ([913b349](https://github.com/roach-php/core/commit/913b34901424a100e59f56ce37108391c3356ad3))
* add ResponseReceiving and ResponseReceived events ([96c9332](https://github.com/roach-php/core/commit/96c9332c796cf8c64ca8bd15885580b49354a78c))
* allow request middleware to set a response to bypass downloader ([#106](https://github.com/roach-php/core/issues/106)) ([d8ae43e](https://github.com/roach-php/core/commit/d8ae43ea5efb7f1cd729fc6dab19756beddb4702))
* php 8.3 support ([02772af](https://github.com/roach-php/core/commit/02772afc7eb2e1ec94a8bb265bec82b9ef587ea7))


### Bug Fixes

* rename integration tests so they actually get run ([33eb25e](https://github.com/roach-php/core/commit/33eb25e31769d1d3080174341efeac06cd89ee45))


### Miscellaneous Chores

* (deps): bump symfony/console from 6.2.5 to 6.2.7 ([#93](https://github.com/roach-php/core/issues/93)) ([4f535da](https://github.com/roach-php/core/commit/4f535da0d95f6e5a88bea4c1e5d9c7f3219388c6))
* (deps): bump symfony/css-selector from 6.2.5 to 6.2.7 ([20fc15b](https://github.com/roach-php/core/commit/20fc15b708c6c486b49a2c766630b42512df25c6))
* (deps): bump symfony/dom-crawler from 6.2.5 to 6.2.7 ([#92](https://github.com/roach-php/core/issues/92)) ([25e4831](https://github.com/roach-php/core/commit/25e48316e49408ad2ce120e76d56fa70be8864dc))
* add code of conduct ([ed80fbe](https://github.com/roach-php/core/commit/ed80fbe7919d0a518d54a4ba48b8cefb0565f6cc))
* add CODEOWNERS file ([9efec92](https://github.com/roach-php/core/commit/9efec92ce1ba244cbd205037e8c3463d6986f054))
* add commitlint step to pipeline ([64e785a](https://github.com/roach-php/core/commit/64e785a0a04c5697ec91f17905190cbc062512ce))
* add contribution guide ([bdb57e0](https://github.com/roach-php/core/commit/bdb57e0824cb4c883be3d9109696c74d88586644))
* add issue templates ([5c4fd81](https://github.com/roach-php/core/commit/5c4fd81a3a3caa7ee6b3ab42d69f85d0e02dbfbe))
* apply cs fixes ([1c628d0](https://github.com/roach-php/core/commit/1c628d0e898070d1b37b1741e730b756a32e377e))
* **deps-dev:** bump ergebnis/composer-normalize from 2.29.0 to 2.30.2 ([980b920](https://github.com/roach-php/core/commit/980b92040b6f6ea38907a49d296f69f9a91ab606))
* **deps-dev:** bump ergebnis/php-cs-fixer-config from 5.3.1 to 5.3.2 ([44d81c9](https://github.com/roach-php/core/commit/44d81c9855542fec1f8f1c342d666e1ca6680350))
* **deps-dev:** bump ergebnis/php-cs-fixer-config from 5.3.2 to 5.3.3 ([7b6f59f](https://github.com/roach-php/core/commit/7b6f59fab7bb75efcbacc848122ed23b840b560f))
* **deps-dev:** bump ergebnis/php-cs-fixer-config from 5.3.3 to 5.4.0 ([#113](https://github.com/roach-php/core/issues/113)) ([cfaae68](https://github.com/roach-php/core/commit/cfaae68eea8107d6fd755fb7f2e1e3d661a5ff80))
* **deps-dev:** bump ergebnis/php-cs-fixer-config from 5.4.0 to 5.7.0 ([#127](https://github.com/roach-php/core/issues/127)) ([61b2d2a](https://github.com/roach-php/core/commit/61b2d2a8fa5d1aad0907ce9c7b58c64fa94dfca6))
* **deps-dev:** bump phpunit/phpunit from 10.0.15 to 10.0.16 ([57b4d03](https://github.com/roach-php/core/commit/57b4d03bd6ca00c5b17af81ac8ebbc8382c91677))
* **deps-dev:** bump phpunit/phpunit from 10.0.16 to 10.0.18 ([a5fe27a](https://github.com/roach-php/core/commit/a5fe27ad0cadb3f74da4916f7b1a97d27abe144c))
* **deps-dev:** bump phpunit/phpunit from 10.0.18 to 10.2.0 ([#130](https://github.com/roach-php/core/issues/130)) ([355a1e6](https://github.com/roach-php/core/commit/355a1e60687d77033202cd024ef3a9177af3ffa0))
* **deps-dev:** bump phpunit/phpunit from 10.2.0 to 10.3.2 ([9aded43](https://github.com/roach-php/core/commit/9aded43194f8784fbc57de1ee8e5e98323824e30))
* **deps-dev:** bump vimeo/psalm from 5.6.0 to 5.8.0 ([b60588a](https://github.com/roach-php/core/commit/b60588ad6e60bd6eb8146466202bd8baf12ad8f0))
* **deps-dev:** bump vimeo/psalm from 5.8.0 to 5.9.0 ([#110](https://github.com/roach-php/core/issues/110)) ([0ef0d78](https://github.com/roach-php/core/commit/0ef0d781c207a2634116c422702917db062743ba))
* **deps-dev:** bump vimeo/psalm from 5.9.0 to 5.15.0 ([4835458](https://github.com/roach-php/core/commit/483545871a321b522a29629d7293e58288e8a032))
* **deps:** bump guzzlehttp/guzzle from 7.5.0 to 7.8.0 ([0c4e817](https://github.com/roach-php/core/commit/0c4e817acd3a4f253fe87db7958402277ff4ce34))
* **deps:** bump guzzlehttp/psr7 from 2.4.3 to 2.5.0 ([#120](https://github.com/roach-php/core/issues/120)) ([7572aae](https://github.com/roach-php/core/commit/7572aaefa6f1f310f2faab6d7bc7ffd4ebccbf2a))
* **deps:** bump psy/psysh from 0.11.12 to 0.11.13 ([4542cbf](https://github.com/roach-php/core/commit/4542cbf00fe2b02a86d5b2048a97556706adf44f))
* **deps:** bump psy/psysh from 0.11.13 to 0.11.18 ([#128](https://github.com/roach-php/core/issues/128)) ([8e940ef](https://github.com/roach-php/core/commit/8e940efce2d4452d03a4fb3e7dc8418968628e7d))
* **deps:** bump psy/psysh from 0.11.22 to 0.12.0 ([03da7a3](https://github.com/roach-php/core/commit/03da7a325af2f957bc30257e1b4bc4503f6bc19e))
* **deps:** bump symfony/console from 6.2.7 to 6.2.8 ([#111](https://github.com/roach-php/core/issues/111)) ([c24018c](https://github.com/roach-php/core/commit/c24018c83fd7d517f4ab9c96f2db533198df7b6a))
* **deps:** bump symfony/css-selector from 6.2.7 to 6.3.2 ([34e38b9](https://github.com/roach-php/core/commit/34e38b9e42e0c10e44b7d8b9b0b1bc30b3f66c6d))
* **deps:** bump symfony/dom-crawler from 6.2.7 to 6.2.8 ([#109](https://github.com/roach-php/core/issues/109)) ([8302a9a](https://github.com/roach-php/core/commit/8302a9a4b071c5726f78e728f3e3b4f524313025))
* **deps:** bump symfony/dom-crawler from 6.2.8 to 6.2.9 ([#118](https://github.com/roach-php/core/issues/118)) ([1099794](https://github.com/roach-php/core/commit/1099794ea5e6016f01bc0f956fa644a1a5aca4b3))
* **deps:** bump symfony/dom-crawler from 6.2.9 to 6.4.0 ([40794f8](https://github.com/roach-php/core/commit/40794f8495bdf271df2c71cd96733bf4f6743a0a))
* **deps:** bump symfony/event-dispatcher from 6.2.5 to 6.2.7 ([3a19510](https://github.com/roach-php/core/commit/3a19510e0457867f0a307c8511e2cb0ef85a8e6b))
* **deps:** bump symfony/event-dispatcher from 6.2.8 to 6.3.2 ([fdfa495](https://github.com/roach-php/core/commit/fdfa4956097b041acfcb67cfb8415a65a2425ace))
* **deps:** bump symfony/options-resolver from 6.2.5 to 6.2.7 ([#89](https://github.com/roach-php/core/issues/89)) ([fe24ee3](https://github.com/roach-php/core/commit/fe24ee3241546c3dc968537b344040dd9bb3c483))
* **deps:** bump tj-actions/branch-names in /.github/workflows ([cd125d2](https://github.com/roach-php/core/commit/cd125d2404c447db2b76ab4aa639f6353ef912ed))
* set up dependabot ([29fb85e](https://github.com/roach-php/core/commit/29fb85ee3e7db67b855a2999b1af592253043e1a))
* set up release please action ([5a7df32](https://github.com/roach-php/core/commit/5a7df32ca5fc8816fd4e7b6273eb5071f1203e00))
* update changelog ([06f7e3d](https://github.com/roach-php/core/commit/06f7e3dc3dd2a6ce806a9a82d91dc371c861074a))
* update changelog ([e896c1a](https://github.com/roach-php/core/commit/e896c1a750dc032dda859515d0d4c9a922f89ea3))
* update copyright year ([321626f](https://github.com/roach-php/core/commit/321626f20cc1eccab407f0e594ed94b231240d15))
* upgrade to phpunit 10 ([fd83095](https://github.com/roach-php/core/commit/fd830953f85f3ad7a06c04988ca39a24718c5e46))

## [3.0.0] – 2024-01-05

### Added

- Added `ProxyMiddleware` (#185)
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
