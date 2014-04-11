ZF Content Validation
=====================

[![Build Status](https://travis-ci.org/zfcampus/zf-content-validation.png)](https://travis-ci.org/zfcampus/zf-content-validation)

Introduction
------------

Module for automating validation of incoming input within a Zend Framework 2
application.

Allows the following:

- Defining named input filters
- Mapping named input filters to named controller services
- Returning an `ApiProblemResponse` with validation error messages on invalid
  input

Installation
------------

Run the following `composer` command:

```console
$ composer require "zfcampus/zf-content-validation:~1.0-dev"
```

Alternately, manually add the following to your `composer.json`, in the `require` section:

```javascript
"require": {
    "zfcampus/zf-content-validation": "~1.0-dev"
}
```

And then run `composer update` to ensure the module is installed.

Finally, add the module name to your project's `config/application.config.php` under the `modules`
key:

```php
return array(
    /* ... */
    'modules' => array(
        /* ... */
        'ZF\ContentValidation',
    ),
    /* ... */
);
```


Configuration
=============

### User Configuration

This module utilizes two user level configuration keys `zf-content-validation` and also
`input_filter_specs` (named such that this functionality can be moved into ZF2 in the future).

#### Service Name key

The `zf-content-validation` key is a mapping between controller service names as the key and
the value being an array of mappings that determine which HTTP method to respond to and what
input filter to map to for the given request.  The keys for the mapping can either be an
HTTP method (like `POST`, `GET`, etc.) or it can be the word `input_filter`, in which case this
particular mapping will be used for all HTTP methods for the matching controller service name
request.

Example where there is a default as well as a GET filter:

```php
'zf-content-validation' => array(
    'Application\\Controller\\HelloWorld' => array(
        'input_filter' => 'Application\\Controller\\HelloWorld\\Validator',
        'GET' => 'Application\\Controller\\HelloWorldGet'
    ),
),
```

#### `input_filter_spec`

`input_filter_spec` if for configuration driven creation of input filters.  They keys for
this array of configurations will be a unique name, but more often based off the service
name it is generally mapped to.  The values will be a typical input filter configuration
array, like the one from the ZF2 manual
[http://zf2.readthedocs.org/en/latest/modules/zend.input-filter.intro.html](http://zf2.readthedocs.org/en/latest/modules/zend.input-filter.intro.html).

Example:

```php
'input_filter_specs' => array(
    'Application\\Controller\\HelloWorldGet' => array(
        0 => array(
            'name' => 'name',
            'required' => true,
            'filters' => array(
                0 => array(
                    'name' => 'Zend\\Filter\\StringTrim',
                    'options' => array(),
                ),
            ),
            'validators' => array(),
            'description' => 'Hello to name',
            'allow_empty' => false,
            'continue_if_empty' => false,
        ),
    ),
```

### System Configuration

```php
'input_filters' => array(
    'abstract_factories' => array(
        'ZF\ContentValidation\InputFilter\InputFilterAbstractServiceFactory',
    ),
),
'service_manager' => array(
    'factories' => array(
        'ZF\ContentValidation\ContentValidationListener' => 'ZF\ContentValidation\ContentValidationListenerFactory',
    ),
),
'validators' => array(
    'factories' => array(
        'ZF\ContentValidation\Validator\DbRecordExists' => 'ZF\ContentValidation\Validator\Db\RecordExistsFactory',
        'ZF\ContentValidation\Validator\DbNoRecordExists' => 'ZF\ContentValidation\Validator\Db\NoRecordExistsFactory',
    ),
),
```

ZF2 Events
==========

### Listeners

#### `ZF\ContentValidation\ContentValidationListener`

This listener is attached to the `MvcEvent::EVENT_ROUTE` at priority `-650`.  It's primary purpose is
utilize the configuration in order to determine if the current request's controller service name
to be dispatched has a configured input filter.  If it does, it will travers the mappings from the
configuration file to create the appropriate input filter (from configuration or the input filter
plugin manager) in order to validate the incoming data.  This particular listener utilizes the data
from the `zf-content-negotiation` data container in order to get the deserialized content body
parameters.

ZF2 Services
============

### Service

#### `ZF\ContentValidation\InputFilter\InputFilterAbstractServiceFactory`

This abstract factory is responsible for creating and returning an appropriate input filter given
a name and the configuration from the top-level key `input_filter_specs`.

