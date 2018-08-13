# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 1.6.1 - 2018-08-13

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#99](https://github.com/zfcampus/zf-content-validation/pull/99) fixes how the `ContentValidationListener` chooses an input filter
  on `DELETE` requests. Starting in 1.6.0, it erroneously would fall back to the
  default input filter if no `DELETE` or `DELETE_COLLECTION` input filter was
  found. However, this was a backwards incompatible change from previous
  versions, which would not apply the default input filter for `DELETE`
  requests. As such, a new check was added to provide the previous behavior in
  such situations.

## 1.6.0 - 2018-08-02

### Added

- [#98](https://github.com/zfcampus/zf-content-validation/pull/98) adds support for specifying controller-specific input filters for
  `DELETE` requests, using the keys `DELETE` and `DELETE_COLLECTION`. The input
  filter associated with the key will be applied to the request body.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.5.0 - 2018-05-29

### Added

- [#96](https://github.com/zfcampus/zf-content-validation/pull/96) adds the ability to specify different input filters for _collections_ (versus
  entities). These may be indicated using `<METHOD>_COLLECTION` within your resource input filter configuration.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.4.0 - 2018-05-07

### Added

- [#95](https://github.com/zfcampus/zf-content-validation/pull/95) adds support for PHP 7.1 and 7.2.

### Changed

- [#93](https://github.com/zfcampus/zf-content-validation/pull/93) modifies `ContentValidationListener::onRoute()` to set the event parameter
  `ZF\ContentValidation\ParameterData` with the data it has aggregated prior to emitting the event
  `ContentValidationListener::EVENT_BEFORE_VALIDATE`. It also now pulls that parameter immediately
  prior to validating data, merging its value with the original data aggregated, and using the
  result when validating. This feature allows you to manipulate the data being validated prior
  to validation; one use case is to inject route matches into the data set to validate.

### Deprecated

- Nothing.

### Removed

- [#95](https://github.com/zfcampus/zf-content-validation/pull/95) removes support for HHVM.

### Fixed

- Nothing.

## 1.3.6 - 2017-11-06

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#88](https://github.com/zfcampus/zf-content-validation/issues/88) fixes an
  issue when handling PATCH requests in JSON formats that include a blank field
  name. Prior to the patch, these would raise a 400 error with a stack trace;
  with the fix, we now issue a 400 error with the message 'Unrecognized field
  ""'.

## 1.3.5 - 2016-08-18

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#83](https://github.com/zfcampus/zf-content-validation/pull/83) fixes how
  input filters are processed when performing GET collections to REST endpoints.
  This ability was introduce in 1.3.0, but failed to take into account that
  query string arguments cannot properly represent multiple entities, which led
  to any input filter for a `ZF\Rest\Resource::fetchAll()` operation to be
  invalid. The change introduced treats such input filters such that they
  do not operate as a `CollectionInputFilter`; as such, they should only be used
  to retrieve values that will be used to sort or filter the collection
  returned.
- [#84](https://github.com/zfcampus/zf-content-validation/pull/84) fixes how
  unknown fields are pulled from `CollectionInputFilter` instances when
  reporting their presence for Problem Details. Previously, it would report only
  the index of the collection set, and not the values from each; with this
  release, it now reports the which sets had unknown values, along with the
  unexpected values detected.

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
