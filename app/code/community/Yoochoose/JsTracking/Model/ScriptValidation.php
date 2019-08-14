<?php

class Yoochoose_JsTracking_Model_ScriptValidation extends Mage_Core_Model_Config_Data
{
    const SCRIPT_URL_REGEX = "/^(https:\/\/|http:\/\/|\/\/)?([a-zA-Z][\w\-]*)((\.[a-zA-Z][\w\-]*)*)(:\d+)?((\/[a-zA-Z][\w\-]*){0,2})(\/)?$/";

    public function save()
    {
        if ($this->getValue() && !preg_match(self::SCRIPT_URL_REGEX, $this->getValue())) {
            Mage::getSingleton('adminhtml/session')->addError('Unsupported URL type: (' . $this->getValue() . ')');
            return;
        }

        parent::save();
    }
}