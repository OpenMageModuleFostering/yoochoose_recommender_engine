<?php

/**
 * @category   AvS
 * @package    AvS_Yoochoose
 * @author     Andreas von Studnitz <avs@avs-webentwicklung.de>
 */

class AvS_Yoochoose_Model_Observer {
	
    const YOOCHOOSE_LICENSE_URL = 'https://admin.yoochoose.net/api/%customer_id%/license.json';

    /**
     * Update field "yoochoose_user_id" from session to
     * customer object (database) or vice verse, if customer info already exists
     *
     * @param Varien_Event_Observer $observer
     */
    public function onCustomerLogin($observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        Mage::helper('yoochoose')->mergeUserIdOnLogin();
    }

    /**
     *
     * @param Varien_Event_Observer $observer
     */
    public function adminSystemConfigChangedSectionYoochoose($observer)
    {
        $clientId = Mage::getStoreConfig('yoochoose/api/client_id');
        $licenseKey = Mage::getStoreConfig('yoochoose/api/license_key');

        if (!$clientId && !$licenseKey) return;

        try {
       		$licenseType = $this->_getLicenseType();

        	$this->_displayMessage($licenseType);
        	
        } catch(Exception $e) {
        	Mage::log('Error getting license for ['.$clientId.']. Error code ['.$e->getCode().']', Zend_Log::ERR, 'yoochoose.log');
        	Mage::logException($e);
        	
        	$licenseType = __('Error getting license [%s]', $e->getCode());
        	
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('yoochoose')->__('License could not be verified.')
            );        	
        }        	

        if ($licenseType != Mage::getStoreConfig('yoochoose/api/license_type')) {
            $this->_setConfigData('yoochoose/api/license_type', $licenseType);
        }
    }

    /**
     * Display success or error message, depending on license type
     *
     * @param string $licenseType
     */
    protected function _displayMessage($licenseType) {
        if ($licenseType && $licenseType != Mage::getStoreConfig('yoochoose/api/license_type')) {
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('yoochoose')->__('License successfully verified.')
            );
        }
    }

    /**
     * Get License Type base on Client Id and License Key
     *
     * @param string $clientId
     * @param string $licenseKey
     * @return string
     */
    protected function _getLicenseType(){
    	
    	$clientId = Mage::getStoreConfig('yoochoose/api/client_id');
    	 
    	if ( ! $clientId) {
    		return __("Client ID not set");
    	}
    	
    	Mage::log('Requesting license for ['.$clientId.']...', Zend_Log::DEBUG, 'yoochoose.log');
    	
        $url = str_replace('%customer_id%', $clientId, self::YOOCHOOSE_LICENSE_URL);
        
        $rawResponse = Mage::helper('yoochoose')->_getHttpPage($url);
        $response = Zend_Json::decode($rawResponse);
        return $response['license']['type'];
    }


    protected function _setConfigData($configPath, $value)
    {
        $setup = new Mage_Core_Model_Resource_Setup('core_setup');
        $setup->startSetup();
        $setup->setConfigData($configPath, $value);
        $setup->endSetup();
        Mage::getSingleton('core/config')->reinit();
    }
} 
