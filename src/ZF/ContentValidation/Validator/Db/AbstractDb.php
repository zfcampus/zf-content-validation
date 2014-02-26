<?php

namespace ZF\ContentValidation\Validator\Db;

use Zend\Validator\Db\AbstractDb as AbstractDbValidator;
use Zend\Db\Adapter\Adapter as DbAdapter;
use Zend\Validator\Exception;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

abstract class AbstractDb extends AbstractDbValidator implements ServiceLocatorAwareInterface
{
	use ServiceLocatorAwareTrait;
	
	public function setAdapter($adapter)
	{
		$adapterService = $this->getServiceLocator()->get($adapter);
		if (!($adapterService instanceof DbAdapter)) {
			throw new Exception\InvalidArgumentException('DbAdapter service not valid!');
		}
		$this->adapter = $adapterService;
		return $this;
	}
}