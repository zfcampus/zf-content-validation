<?php

namespace ZF\ContentValidation\Validator\Db;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\MutableCreationOptionsInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Validator\Db\NoRecordExists;

class NoRecordExistsFactory implements FactoryInterface, MutableCreationOptionsInterface 
{
	use MutableCreationOptionsTrait;
	
	public function createService(ServiceLocatorInterface $serviceLocator) 
	{
		$this->options['adapter'] = $serviceLocator->getServiceLocator()->get($this->options['adapter']);
		return new NoRecordExists($this->options);
	}
}
