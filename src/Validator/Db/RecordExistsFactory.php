<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentValidation\Validator\Db;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Validator\Db\RecordExists;

class RecordExistsFactory implements FactoryInterface
{
    /**
     * Required for v2 compatibility.
     *
     * @var null|array
     */
    private $options;

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     * @return RecordExists
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if ($container instanceof AbstractPluginManager
            && ! method_exists($container, 'configure')
        ) {
            $container = $container->getServiceLocator() ?: $container;
        }

        if (isset($options['adapter'])) {
            $options['adapter'] = $container->get($options['adapter']);
        }

        return new RecordExists($options);
    }

    /**
     * Create and return a RecordExists validator (v2 compatibility)
     *
     * @param ServiceLocatorInterface $container
     * @param null|string $name
     * @param null|string $requestedName
     * @return RecordExists
     */
    public function createService(ServiceLocatorInterface $container, $name = null, $requestedName = null)
    {
        $requestedName = $requestedName ?: RecordExists::class;

        if ($container instanceof AbstractPluginManager) {
            $container = $container->getServiceLocator() ?: $container;
        }

        return $this($container, $requestedName, $this->options);
    }

    /**
     * Allow injecting options at build time; required for v2 compatibility.
     *
     * @param array $options
     */
    public function setCreationOptions(array $options)
    {
        $this->options = $options;
    }
}
