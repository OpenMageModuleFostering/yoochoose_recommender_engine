<?php

/**
 * @category   AvS
 * @package    AvS_Yoochoose
 * @author     Andreas von Studnitz <avs@avs-webentwicklung.de>
 */

class AvS_Yoochoose_Model_Observer
{
    const YOOCHOOSE_LICENSE_URL = 'https://config.yoochoose.net/ebl/%customer_id%/license.json';
    const YOOCHOOSE_SUMMARY_URL = 'https://config.yoochoose.net/rest/%customer_id%/counter/summary.json';

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

        $licenseType = $this->_getLicenseType($clientId, $licenseKey);

        $this->_displayMessage($licenseType);

        if ($licenseType != Mage::getStoreConfig('yoochoose/api/license_type')) {
            $this->_setConfigData('yoochoose/api/license_type', $licenseType);
        }
    }

    /**
     * Display success or error message, depending on license type
     *
     * @param string $licenseType
     */
    protected function _displayMessage($licenseType)
    {
        if ($licenseType && $licenseType != Mage::getStoreConfig('yoochoose/api/license_type')) {
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('yoochoose')->__('License successfully verified.')
            );
        } else if (!$licenseType) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('yoochoose')->__('License could not be verified.')
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
    protected function _getLicenseType($clientId, $licenseKey)
    {
        $url = str_replace('%customer_id%', $clientId, self::YOOCHOOSE_LICENSE_URL);
        try {
            $rawResponse = Mage::helper('yoochoose')->_getHttpsPage($url, $clientId, $licenseKey);
            $response = Zend_Json::decode($rawResponse);
            return $response['license']['type'];
        }
        catch(Exception $e) {

            // authentication failed
            return '';
        }
    }

    public function updateStats()
    {
        $clientId = Mage::getStoreConfig('yoochoose/api/client_id');
        $licenseKey = Mage::getStoreConfig('yoochoose/api/license_key');

        if (!$clientId && !$licenseKey) return;

        $stats = $this->_getStats($clientId, $licenseKey);

        $statsHtml = $this->_generateStatsHtml($stats);
        if ($statsHtml) {
            $this->_setConfigData('yoochoose/general/stats', $statsHtml);
        }

        return $statsHtml;
    }

    /**
     * Get Statistics bases on Client Id and License Key
     *
     * @param string $clientId
     * @param string $licenseKey
     * @return array
     */
    protected function _getStats($clientId, $licenseKey)
    {
        $url = str_replace('%customer_id%', $clientId, self::YOOCHOOSE_SUMMARY_URL);
        try {
            $rawResponse = Mage::helper('yoochoose')->_getHttpsPage($url, $clientId, $licenseKey);
            $response = Zend_Json::decode($rawResponse);
            return $response;
        }
        catch(Exception $e) {

            // authentication failed
            return array();
        }
    }

    /**
     * Generate Statistics HTML for display in configuration
     *
     * @param array $stats
     * @return string
     */
    protected function _generateStatsHtml($stats)
    {
        $statsHtml = '<table>';
        $statsLines = array();
        $baseSorting = 6;
        
        foreach($stats as $key => $singleStat) {

            switch ($key) {

                case 'EVENT_1':

                    $label = Mage::helper('yoochoose')->__('Registered Clicks');
                    $sorting = 0;
                    break;

                case 'EVENT_2':

                    $label = Mage::helper('yoochoose')->__('Registered Buys');
                    $sorting = 1;
                    break;

                case 'RECO_also_purchased':

                    $label = Mage::helper('yoochoose')->__('"Also purchased" recommendations');
                    $sorting = 3;
                    break;

                case 'RECO_also_clicked':

                    $label = Mage::helper('yoochoose')->__('"Also clicked" recommendations');
                    $sorting = 4;
                    break;

                case 'RECO_top_selling':

                    $label = Mage::helper('yoochoose')->__('"Top selling" recommendations');
                    $sorting = 5;
                    break;

                case 'DELIVERED_RECOS_also_purchased':
                case 'DELIVERED_RECOS_also_clicked':
                case 'DELIVERED_RECOS_top_selling':

                    continue;

                default:

                    $label = $key;
                    $sorting = $baseSorting;
                    $baseSorting++;
                    break;
            }

            $statsLines[$sorting] = '<tr><td>' . $label . ':&nbsp;</td><td>' . $singleStat['count'] . '</td></tr>';
        }

        $statsLines[2] = '<tr><td>----------</td><td>&nbsp;</td></tr>';

        ksort($statsLines);
        $statsHtml .= implode("\n", $statsLines);

        $statsHtml .= '</table>';

        return $statsHtml;
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