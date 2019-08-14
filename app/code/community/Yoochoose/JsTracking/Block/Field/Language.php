<?php

class Yoochoose_JsTracking_Block_Field_Language extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        if (!Mage::getStoreConfig('yoochoose/general/language')) {
            $locale = Mage::app()->getLocale()->getLocaleCode();
            $element->setValue($locale);
        }

        return parent::_getElementHtml($element);
    }

}
