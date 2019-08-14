<?php

class Yoochoose_JsTracking_Block_Field_Readonly extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        switch ($element->getId()) {
            case 'yoochoose_general_endpoint':
                if (!Mage::getStoreConfig('yoochoose/general/endpoint_overwrite') && !$element->getValue()) {
                    $url = Mage::getStoreConfig('web/unsecure/base_url');
                    $element->setValue($url);
                }

                break;
            case 'yoochoose_general_design':
                if (!Mage::getStoreConfig('yoochoose/general/endpoint_overwrite') && !$element->getValue()) {
                    $theme = Mage::getSingleton('core/design_package')->getTheme('frontend');
                    $element->setValue($theme);
                }

                break;
        }

        $element->setReadonly('true');

        return parent::_getElementHtml($element);
    }

}
