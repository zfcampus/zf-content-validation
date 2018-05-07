<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\ContentValidation\TestAsset;

use Zend\InputFilter\InputFilter;

class CustomValidationInputFilter extends InputFilter
{
    public function isValid($context = null)
    {
        return true;
    }
}
