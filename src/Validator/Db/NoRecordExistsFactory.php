<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentValidation\Validator\Db;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\MutableCreationOptionsInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\ArrayUtils;
use Zend\Validator\Db\NoRecordExists;

class NoRecordExistsFactory implements FactoryInterface, MutableCreationOptionsInterface
{
    /**
     * @var array
     */
    protected $options = array();

    /**
     * Set options property
     *
     * @param array $options
     */
    public function setCreationOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * @param ServiceLocatorInterface $validators
     * @return NoRecordExists
     */
    public function createService(ServiceLocatorInterface $validators)
    {
        if (isset($this->options['adapter'])) {
            return new NoRecordExists(ArrayUtils::merge(
                $this->options,
                array('adapter' => $validators->getServiceLocator()->get($this->options['adapter']))
            ));
        }

        return new NoRecordExists($this->options);
    }
}
