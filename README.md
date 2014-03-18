ZF Content Validation
=====================

[![Build Status](https://travis-ci.org/zfcampus/zf-content-validation.png)](https://travis-ci.org/zfcampus/zf-content-validation)
[![Coverage Status](https://coveralls.io/repos/zfcampus/zf-content-validation/badge.png?branch=master)](https://coveralls.io/r/zfcampus/zf-content-validation)

Module for automating validation of incoming input within a Zend Framework 2
application.

Allows the following:

- Defining named input filters
- Mapping named input filters to named controller services
- Returning an `ApiProblemResponse` with validation error messages on invalid
  input

Installation
------------

You can install using:

```
curl -s https://getcomposer.org/installer | php
php composer.phar install
```
