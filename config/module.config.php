<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

return array(
    'input_filter_specs' => array(
        /*
         * An array of service name => config pairs.
         *
         * Service names must be unique, and will be the name by which the
         * input filter will be retrieved. The configuration is any valid
         * configuration for an input filter, as shown in the manual:
         *
         * - http://zf2.readthedocs.org/en/latest/modules/zend.input-filter.intro.html
         */
    ),
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
    'zf-content-validation' => array(
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
         */
    ),
);
