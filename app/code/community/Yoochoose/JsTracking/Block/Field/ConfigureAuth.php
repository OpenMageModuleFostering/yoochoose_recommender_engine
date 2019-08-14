<?php

class Yoochoose_JsTracking_Block_Field_ConfigureAuth extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /*
     * Set template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('yoochoose/system/config/configure_auth.phtml');
    }

    /**
     * Return element html
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }

    /**
     * Return ajax url for button
     *
     * @return string
     */
    public function getAjaxConfigureUrl()
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/adminhtml_yoochoose/configure');
    }

    /**
     * Generate button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
            'id' => 'yoochoose_button',
            'label' => $this->helper('adminhtml')->__('Configure'),
            'onclick' => 'javascript:yc_configure_auth(); return false;'
        ));

        return $button->toHtml();
    }

}
