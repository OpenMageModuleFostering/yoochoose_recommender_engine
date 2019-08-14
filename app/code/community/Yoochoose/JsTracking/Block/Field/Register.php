<?php

class Yoochoose_JsTracking_Block_Field_Register extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /*
     * Set template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('yoochoose/system/config/register.phtml');
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


    public function getYoochooseLink()
    {
        $code = Mage::getSingleton('adminhtml/config_data')->getStore();
        $storeId = Mage::getModel('core/store')->load($code)->getId();
        $language = Mage::getStoreConfig('general/locale/code', $storeId);
        return 'https://admin.yoochoose.net/login.html?product=magento_Direct&lang=' . substr($language, 0, strpos($language, '_'));
    }
    
    public function getYoochooseRegistration()
    {
        $data = array();
        $code = Mage::getSingleton('adminhtml/config_data')->getStore();
        $storeId = Mage::getModel('core/store')->load($code)->getId();
        $admin = Mage::getModel('admin/session')->getUser();
        $locale = Mage::getStoreConfig('general/locale/code', $storeId);
        
        $data['account.firstName'] = $data['billing.firstName'] = $admin->getFirstname();
        $data['account.lastName'] = $data['billing.lastName'] = $admin->getLastname();
        $data['account.email'] = $data['billing.email'] = $admin->getEmail();
        $data['booking.website'] = Mage::helper('core/url')->getHomeUrl();
        $data['booking.timeZone'] = Mage::getStoreConfig('general/locale/timezone', $storeId);
        $data['booking.lang'] = substr($locale, 0, strpos($locale, '_'));
        $data['billing.countryCode'] = substr($locale, strpos($locale, '_') + 1);
        
        return json_encode($data);
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
            'label' => $this->helper('adminhtml')->__('Register new Yoochoose account'),
            'onclick' => 'javascript:yc_register(); return false;'
        ));

        return $button->toHtml();
    }

}

