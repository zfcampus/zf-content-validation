<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentValidation\InputFilter;

use RuntimeException;
use Zend\InputFilter\CollectionInputFilter;
use Zend\InputFilter\Exception\InvalidArgumentException as InputFilterInvalidArgumentException;

class PatchCollectionInputFilter extends CollectionInputFilter
{
    /**
     * Overriding due to visibility of $data
     *
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        $inputFilter = $this->getInputFilter();
        $valid = true;

        if ($this->getCount() < 1) {
            if ($this->isRequired) {
                $valid = false;
            }
        }

        if (count($this->data) < $this->getCount()) {
            $valid = false;
        }

        if (empty($this->data)) {
            $this->clearValues();
            $this->clearRawValues();

            return $valid;
        }

        foreach ($this->data as $key => $data) {
            if (!is_array($data)) {
                $data = array();
            }

            try {
                $inputFilter->setValidationGroup(array_keys($data));
            } catch (InputFilterInvalidArgumentException $ex) {
                $pattern = '/expects a list of valid input names; "(?P<field>[^"]+)" was not found/';
                $matched = preg_match($pattern, $ex->getMessage(), $matches);
                if (!$matched) {
                    throw new RuntimeException('Unknown error occurred during validation', 400, $ex);
                }

                throw new RuntimeException(
                    sprintf('Unrecognized field "%s"', $matches['field']),
                    400
                );
            }

            $inputFilter->setData($data);

            if ($inputFilter->isValid()) {
                $this->validInputs[$key] = $inputFilter->getValidInput();
            } else {
                $valid = false;
                $this->collectionMessages[$key] = $inputFilter->getMessages();
                $this->invalidInputs[$key] = $inputFilter->getInvalidInput();
            }

            $this->collectionValues[$key] = $inputFilter->getValues();
            $this->collectionRawValues[$key] = $inputFilter->getRawValues();
        }

        return $valid;
    }
}
