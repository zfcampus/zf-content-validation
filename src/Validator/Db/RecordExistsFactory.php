<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentValidation\Validator\Db;

use Interop\Container\ContainerInterface;
use Zend\Stdlib\ArrayUtils;
use Zend\Validator\Db\RecordExists;

class RecordExistsFactory
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return RecordExists
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if (isset($options['adapter'])) {
            return new RecordExists(ArrayUtils::merge(
                $options,
                ['adapter' => $container->get($options['adapter'])]
            ));
        }

        return new RecordExists($options);
    }
}
