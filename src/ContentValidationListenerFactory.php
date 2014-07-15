<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
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
        $contentValidationConfig = array();
        $restServices            = array();

        if ($services->has('Config')) {
            $config = $services->get('Config');
            if (isset($config['zf-content-validation'])) {
                $contentValidationConfig = $config['zf-content-validation'];
            }
            $restServices = $this->getRestServicesFromConfig($config);
        }

        return new ContentValidationListener(
            $contentValidationConfig,
            $services->get('InputFilterManager'),
            $restServices
        );
    }

    /**
     * Generate the list of REST services for the listener
     *
     * Looks for zf-rest configuration, and creates a list of controller
     * service / identifier name pairs to pass to the listener.
     *
     * @param array $config
     * @return array
     */
    protected function getRestServicesFromConfig(array $config)
    {
        $restServices = array();
        if (!isset($config['zf-rest'])) {
            return $restServices;
        }

        foreach ($config['zf-rest'] as $controllerService => $restConfig) {
            if (!isset($restConfig['route_identifier_name'])) {
                continue;
            }
            $restServices[$controllerService] = $restConfig['route_identifier_name'];
        }

        return $restServices;
    }
}
