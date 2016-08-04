# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 1.3.4 - 2016-08-04

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#81](https://github.com/zfcampus/zf-content-validation/pull/81) fixes an
  issue with how data was being returned from the content validation listener
  when raw data was to be used, and unknown fields allowed. In cases where the
  data was an indexed array (which happens with zf-apigility-admin when submitting
  an input filter to the API), the data and unknown values, which were
  identical, were being merged before return. Since raw data always contains all
  unknown values, we now return before merging.

## 1.3.3 - 2016-07-26

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#78](https://github.com/zfcampus/zf-content-validation/pull/78) updates
  `ContentValidationListener::isCollection()` to check strictly for absence of
  an identifier when determining if a collection was requested. Previously, a
  `0` identifier would be incorrectly flagged as a request for a collection,
  which would pull the collection input filter instead of the entity input
  filter.

## 1.3.2 - 2016-07-21

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#76](https://github.com/zfcampus/zf-content-validation/pull/76) Added FactoryInterface
  for zend-servicemanager 2.x and updated factory key names for dbRecordExists and
  dbNoRecordExists validators.

## 1.3.1 - 2016-07-19

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#71](https://github.com/zfcampus/zf-content-validation/pull/71) corrected
  typo in module configuration which was leading to fatal errors (class not found).

## 1.3.0 - 2016-07-13

### Added

- [#64](https://github.com/zfcampus/zf-content-validation/pull/64) adds support
  for providing input filters for `GET` requests; these will validate query
  parameters. Configuration is exactly as it is for other HTTP methods.
- [#66](https://github.com/zfcampus/zf-content-validation/pull/66) and
  [#67](https://github.com/zfcampus/zf-content-validation/pull/67) add support
  for v3 releases of Zend Framework components, while retaining backwards
  compatibility with v2 releases.

### Deprecated

- Nothing.

### Removed

- [#67](https://github.com/zfcampus/zf-content-validation/pull/67) removes
  support for PHP 5.5.

### Fixed

- [#65](https://github.com/zfcampus/zf-content-validation/pull/65) adds the
  ability to specify the flags `allows_only_fields_in_filter` and `use_raw_data`
  in combination to ensure that raw data will not contain any keys not defined
  in the input filters. (Previously, `allows_only_fields_in_filter` was ignored
  when `use_raw_data` was specified.)

## 1.2.1 - 2016-07-12

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#63](https://github.com/zfcampus/zf-content-validation/pull/63) provides a
  fix to ensure that numeric keys in validation collections are preserved when
  merging file data into the set.
