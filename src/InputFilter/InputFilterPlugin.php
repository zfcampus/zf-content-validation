<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentValidation\InputFilter;

use Zend\InputFilter\InputFilterInterface;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Mvc\InjectApplicationEventInterface;

class InputFilterPlugin extends AbstractPlugin
{
    public function __invoke()
    {
        $controller = $this->getController();
        if (! $controller instanceof InjectApplicationEventInterface) {
            return null;
        }

        $event       = $controller->getEvent();
        $inputFilter = $event->getParam(__NAMESPACE__);

        if (! $inputFilter instanceof InputFilterInterface) {
            return null;
        }

        return $inputFilter;
    }
}
