<?php

namespace ZF\ContentValidation\Validator\Db;

trait MutableCreationOptionsTrait 
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
}
