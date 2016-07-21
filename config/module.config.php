<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentValidation;

use Zend\InputFilter\InputFilterAbstractServiceFactory;
use Zend\ServiceManager\Factory\InvokableFactory;

return [
    'controller_plugins' => [
        'aliases' => [
            'getinputfilter' => InputFilter\InputFilterPlugin::class,
            'getInputfilter' => InputFilter\InputFilterPlugin::class,
            'getInputFilter' => InputFilter\InputFilterPlugin::class,
        ],
        'factories' => [
            InputFilter\InputFilterPlugin::class => InvokableFactory::class,
        ],
    ],
    'input_filter_specs' => [
        /*
         * An array of service name => config pairs.
         *
         * Service names must be unique, and will be the name by which the
         * input filter will be retrieved. The configuration is any valid
         * configuration for an input filter, as shown in the manual:
         *
         * - https://docs.zendframework.com/zend-inputfilter/intro/
         */
    ],
    'input_filters' => [
        'abstract_factories' => [
            InputFilterAbstractServiceFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            ContentValidationListener::class => ContentValidationListenerFactory::class,
        ],
    ],
    'validators' => [
        'factories' => [
            'ZF\ContentValidation\Validator\DbRecordExists' => Validator\Db\RecordExistsFactory::class,
            'ZF\ContentValidation\Validator\DbNoRecordExists' => Validator\Db\NoRecordExistsFactory::class,
        ],
    ],
    'zf-content-validation' => [
        'methods_without_bodies' => [],
        /*
         * An array of controller service name => config pairs.
         *
         * The configuration *must* include at least *one* of:
         *
         * - input_filter: the name of an input filter service to use with the
         *   given controller, AND/OR
         *
         * - a key named after one of the HTTP methods POST, PATCH, or PUT. The
         *   value must be the name of an input filter service to use.
         *
         * When determining an input filter to use, precedence will be given to
         * any configured for specific HTTP methods, and will fall back to the
         * "input_filter" key, when defined, otherwise. If none can be determined,
         * the module will assume no validation is defined, and that the content
         * provided is valid.
         *
         * Additionally, you can define either of the following two keys to
         * further define application validation behavior:
         *
         * - use_raw_data: if NOT present, raw data is ALWAYS injected into
         *   the "BodyParams" container (defined by zf-content-negotiation).
         *   If this key is present and a boolean false, then the validated,
         *   filtered data from the input filter will be used instead.
         *
         * - allows_only_fields_in_filter: if present, and use_raw_data is
         *   boolean false, the value of this flag will define whether or
         *   not additional fields present in the payload will be merged
         *   with the filtered data.
         */
    ],
];
