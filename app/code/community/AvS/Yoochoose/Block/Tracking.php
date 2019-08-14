<?php

/**
 * @category   AvS
 * @package    AvS_Yoochoose
 * @author     Andreas von Studnitz <avs@avs-webentwicklung.de>
 */

class AvS_Yoochoose_Block_Tracking extends Mage_Core_Block_Template
{
    /**
     * Request object
     *
     * @var Zend_Controller_Request_Abstract
     */
    protected $_request;

    protected $_trackingPixelData = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_request = Mage::app()->getRequest();
    }

    /**
     * Generate Yoochoose Tracking Pixel(s) Data depending on request
     * 
     * @return array
     */
    public function getTrackingPixelData()
    {
        if (!is_array($this->_trackingPixelData)) {

            $this->_trackingPixelData = array();

            switch($this->_getFullActionName()) {

                case 'catalog_category_view':

                    $this->_trackingPixelData = Mage::getSingleton('yoochoose/api_event')->getCategoryViewTrackingPixelData();
                    break;

                case 'catalog_product_view':

                    $isRecommended = $this->_isRecommended();
                    $this->_trackingPixelData = Mage::getSingleton('yoochoose/api_event')->getProductViewTrackingPixelData($isRecommended);
                    break;

                case 'checkout_onepage_success':
                case 'checkout_multishipping_success':

                    $this->_trackingPixelData = Mage::getSingleton('yoochoose/api_event')->getCheckoutSuccessTrackingPixelData();
                    break;
            }

            if (is_array(Mage::getSingleton('customer/session')->getTransferEventInfo())) {

                $this->_trackingPixelData[] = Mage::getSingleton('yoochoose/api_event')->getTransferTrackingPixelData();
            }
        }

        return $this->_trackingPixelData;
    }

    protected function _isRecommended() {

        return $this->_getRequest()->getParam('recommended') == 1;
    }

    /**
     * Generate Url from given Params and Generic Data
     *
     * @param array $trackingPixelData
     * @return string
     */
    public function generateTrackingPixelUrl($trackingPixelData)
    {
        return Mage::getSingleton('yoochoose/api_event')->generateTrackingPixelUrl($trackingPixelData);
    }

    /**
     * Retrieve full bane of current action current controller and
     * current module
     *
     * @param   string $delimiter
     * @return  string
     */
    protected function _getFullActionName($delimiter='_')
    {
        return $this->_getRequest()->getRequestedRouteName().$delimiter.
            $this->_getRequest()->getRequestedControllerName().$delimiter.
            $this->_getRequest()->getRequestedActionName();
    }

    /**
     * Retrieve request object
     *
     * @return Mage_Core_Controller_Request_Http
     */
    protected function _getRequest()
    {
        return $this->_request;
    }

}
