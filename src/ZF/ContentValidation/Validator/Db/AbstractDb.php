<?php

namespace ZF\ContentValidation\Validator\Db;

use Zend\Validator\Db\AbstractDb as AbstractDbValidator;
use Traversable;
use Zend\Db\Adapter\Adapter as DbAdapter;
use Zend\Db\Sql\Select;
use Zend\Stdlib\ArrayUtils;
use Zend\Validator\Exception;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

abstract class AbstractDb extends AbstractDbValidator implements ServiceLocatorAwareInterface
{
	use ServiceLocatorAwareTrait;
	
	public function __construct($options = null)
	{
		if ($options instanceof Traversable) {
			$options = ArrayUtils::iteratorToArray($options);
		}
		
		if (isset($this->messageTemplates)) {
			$this->abstractOptions['messageTemplates'] = $this->messageTemplates;
		}
		
		if (isset($this->messageVariables)) {
			$this->abstractOptions['messageVariables'] = $this->messageVariables;
		}
		
		if ($options instanceof Select) {
			$this->setSelect($options);
			return;
		}
	
		if ($options instanceof Traversable) {
			$options = ArrayUtils::iteratorToArray($options);
		} elseif (func_num_args() > 1) {
			$options       = func_get_args();
			$firstArgument = array_shift($options);
			if (is_array($firstArgument)) {
				$temp = ArrayUtils::iteratorToArray($firstArgument);
			} else {
				$temp['table'] = $firstArgument;
			}
	
			$temp['field'] = array_shift($options);
	
			if (!empty($options)) {
				$temp['exclude'] = array_shift($options);
			}
	
			if (!empty($options)) {
				$temp['adapter'] = array_shift($options);
			}
	
			$options = $temp;
		}
	
		if (!array_key_exists('table', $options) && !array_key_exists('schema', $options)) {
			throw new Exception\InvalidArgumentException('Table or Schema option missing!');
		}
	
		if (!array_key_exists('field', $options)) {
			throw new Exception\InvalidArgumentException('Field option missing!');
		}
	
		if (array_key_exists('adapter', $options) && is_string($options['adapter'])) {
			$adapter = $this->getServiceLocator()->get($options['adapter']);
			if (!($adapter instanceof DbAdapter)) {
				throw new Exception\InvalidArgumentException('DbAdapter service not valid!');
			}
			$this->setAdapter($adapter);
		}
	
		if (array_key_exists('exclude', $options)) {
			$this->setExclude($options['exclude']);
		}
	
		$this->setField($options['field']);
		if (array_key_exists('table', $options)) {
			$this->setTable($options['table']);
		}
	
		if (array_key_exists('schema', $options)) {
			$this->setSchema($options['schema']);
		}
	}
}