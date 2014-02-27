<?php

namespace ZF\ContentValidation\Validator\Db;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\MutableCreationOptionsInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Validator\Db\RecordExists;

class RecordExistsFactory implements FactoryInterface, MutableCreationOptionsInterface 
{
	use MutableCreationOptionsTrait;
	
	public function createService(ServiceLocatorInterface $serviceLocator) 
	{
		$this->options['adapter'] = $serviceLocator->get($this->options['adapter']);
		return new RecordExists($this->options);
	}
}
