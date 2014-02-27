<?php

namespace ZF\ContentValidation\Validator\Db;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\MutableCreationOptionsInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Validator\Db\RecordExists;
use Zend\Stdlib\ArrayUtils;

class RecordExistsFactory implements FactoryInterface, MutableCreationOptionsInterface 
{
    use MutableCreationOptionsTrait;
    
    public function createService(ServiceLocatorInterface $serviceLocator) 
    {
        if (isset($this->options['adapter'])) {
            return new RecordExists(ArrayUtils::merge($this->options, ['adapter' => $serviceLocator->getServiceLocator()->get($this->options['adapter'])]));
        } else {
            return new RecordExists($this->options);
        }
    }
}
