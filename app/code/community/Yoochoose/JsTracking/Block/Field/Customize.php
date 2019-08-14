<?php

class Yoochoose_JsTracking_Block_Field_Customize extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /*
     * Set template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('yoochoose/system/config/customize.phtml');
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
     * Returns yoochoose admin configuration url
     * 
     * @return string
     */
    public function getYoochooseLink()
    {
        $customerId = Mage::getStoreConfig('yoochoose/general/customer_id');
        $plugin = Mage::getStoreConfig('yoochoose/general/plugin_id');

        return 'https://admin.yoochoose.net?customer_id=' . $customerId . '#plugin/configuration' . ($plugin ? '/' . $plugin : '');
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
            'label' => $this->helper('adminhtml')->__('Open Yoochoose Dashboard'),
            'onclick' => '',
        ));

        return $button->toHtml();
    }

}

