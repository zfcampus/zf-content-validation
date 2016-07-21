<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentValidation;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ContentValidationListenerFactory implements FactoryInterface
{
    /**
     * Create and return a ContentValidationListener instance.
     *
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     * @return ContentValidationListener
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $contentValidationConfig = isset($config['zf-content-validation']) ? $config['zf-content-validation'] : [];
        $restServices = $this->getRestServicesFromConfig($config);

        return new ContentValidationListener(
            $contentValidationConfig,
            $container->get('InputFilterManager'),
            $restServices
        );
    }

    /**
     * Create and return a ContentValidationListener instance (v2).
     *
     * Provided for backwards compatibility; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return ContentValidationListener
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, ContentValidationListener::class);
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
        $restServices = [];
        if (! isset($config['zf-rest'])) {
            return $restServices;
        }

        foreach ($config['zf-rest'] as $controllerService => $restConfig) {
            if (! isset($restConfig['route_identifier_name'])) {
                continue;
            }
            $restServices[$controllerService] = $restConfig['route_identifier_name'];
        }

        return $restServices;
    }
}
