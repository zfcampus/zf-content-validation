<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentValidation\Validator\Db;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\ArrayUtils;
use Zend\Validator\Db\NoRecordExists;

class NoRecordExistsFactory implements FactoryInterface
{
    /**
     * Required for v2 compatibility.
     *
     * @var null|array
     */
    private $options;

    /**
     * Create and return a NoRecordExists validator.
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     * @return NoRecordExists
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if (isset($options['adapter'])) {
            return new NoRecordExists(ArrayUtils::merge(
                $options,
                ['adapter' => $container->get($options['adapter'])]
            ));
        }

        return new NoRecordExists($options);
    }

    /**
     * Create and return a NoRecordExists validator (v2).
     *
     * Provided for backwards compatibility; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $validators
     * @return NoRecordExists
     */
    public function createService(ServiceLocatorInterface $validators)
    {
        $container = $validators->getServiceLocator() ?: $validators;
        return $this($container, NoRecordExists::class, $this->options);
    }

    /**
     * Set options property
     *
     * Implemented for backwards compatibility.
     *
     * @param array $options
     */
    public function setCreationOptions(array $options)
    {
        $this->options = $options;
    }
}
