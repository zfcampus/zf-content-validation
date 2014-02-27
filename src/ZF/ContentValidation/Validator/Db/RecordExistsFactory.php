<?php

namespace ZF\ContentValidation\Validator\Db;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\MutableCreationOptionsInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Validator\Db\RecordExists;
use Zend\Stdlib\ArrayUtils;

class RecordExistsFactory implements FactoryInterface, MutableCreationOptionsInterface 
{
	/**
	 * @var array
	 */
	protected $options = [];
	
	/**
	 * Set options property
	 *
	 * @param array $options
	 */
	public function setCreationOptions(array $options)
	{
		$this->options = $options;
	}
	    
    public function createService(ServiceLocatorInterface $serviceLocator) 
    {
        if (isset($this->options['adapter'])) {
            return new RecordExists(ArrayUtils::merge($this->options, ['adapter' => $serviceLocator->getServiceLocator()->get($this->options['adapter'])]));
        } else {
            return new RecordExists($this->options);
        }
    }
}
