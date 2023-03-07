# Changelog

## Unreleased
### Fixed
- Fixed a bug that still allowed duplicates to appear in output.

## 2.0.1 - 2023-03-04
### Added
- Added PHPStan check and did some tidying up to clear level 5.
- Added a `composer run phpstan` script.

### Fixed
- Fixed a class name that prevented CLI-based extraction from working.

## 2.0.0 - 2023-03-04
### Added
- Added the option to ignore duplicate items, which is enabled by default.
- Added a browser-based demo for testing the parser with JSON output.
- Added initial tests with Pest and a GitHub Actions pipeline to automate them.
- Added `composer run test` and `composer run demo` scripts for convenience.

### Changed
- Changed name to “Dekindler” and namespace from `mattstein\utilities` to `mattstein\dekindler`.
- Renamed the `extractor` command to `dekindler`.
- Renamed `ExtractKindleClippingsCommand` class to `ExtractCommand`.
- Renamed `KindleClippingExtractor` class to `Extractor`.
- Renamed `KindleClippingWriter` class to `Writer`.
- Refactored for more resilient parsing with various found examples.

## 1.1.0 - 2023-02-22
### Added
- Added support for location-only highlights that don’t have a page number.
- Updated for PHP 8.2.

## 1.0.0 - 2021-12-26
### Added
- Initial CLI tool release.
