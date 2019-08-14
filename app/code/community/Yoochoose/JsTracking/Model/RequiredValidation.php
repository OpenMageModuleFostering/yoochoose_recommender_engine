<?php

class Yoochoose_JsTracking_Model_RequiredValidation extends Mage_Core_Model_Config_Data
{

    public function save()
    {
        $value = trim($this->getValue());
        $name = $this->getField();
        if (empty($value)) {
            Mage::throwException('Filed ' . $name . ' must not be empty.');
        }

        if ($name === 'itemtypeid') {
            if (!filter_var($value, FILTER_VALIDATE_INT) || $value < 0) {
                Mage::throwException('Filed ' . $name . ' must be an integer greater than 0.');
            }
        }

        parent::save();
    }

}