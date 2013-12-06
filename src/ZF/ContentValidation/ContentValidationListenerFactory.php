<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentValidation;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ContentValidationListenerFactory implements FactoryInterface
{
    /**
     * @param ServiceLocatorInterface $services
     * @return ContentValidationListener
     */
    public function createService(ServiceLocatorInterface $services)
    {
        $config = [];
        if ($services->has('Config')) {
            $allConfig = $services->get('Config');
            if (isset($allConfig['zf-content-validation'])) {
                $config = $allConfig['zf-content-validation'];
            }
        }
        return new ContentValidationListener($config, $services);
    }
}
