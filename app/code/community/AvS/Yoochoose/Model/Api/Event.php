<?php

/**
 * @category   AvS
 * @package    AvS_Yoochoose
 * @author     Andreas von Studnitz <avs@avs-webentwicklung.de>
 */

class AvS_Yoochoose_Model_Api_Event extends AvS_Yoochoose_Model_Api
{

    /**
     * On Category View Page: Generate Tracking Pixel Data
     *
     * @return array
     */
    public function getCategoryViewTrackingPixelData() {

        $category = Mage::registry('current_category');
        $categoryId = $category->getId();

        $trackingPixelData = array(
            'ItemId'        => $categoryId,
            'ItemTypeId'    => self::ITEM_TYPE_CATEGORY,
            'CategoryPath'  => $this->_getCategoryPath(),
            'Recommended'   => 0, // only products get recommended
            'EventType'     => self::EVENT_TYPE_CLICK,
        );

        return array($trackingPixelData);
    }

    /**
     * On Product View Page: Generate Tracking Pixel Data
     *
     * @param boolean $isRecommended
     * @return array
     */
    public function getProductViewTrackingPixelData($isRecommended = false) {

        $product = Mage::registry('product');
        $productId = $product->getId();

        $trackingPixelData = array(
            'ItemId'        => $productId,
            'ItemTypeId'    => self::ITEM_TYPE_PRODUCT,
            'CategoryPath'  => $this->_getCategoryPath(),
            'Recommended'   => intval($isRecommended),
            'EventType'     => self::EVENT_TYPE_CLICK,
        );

        return array($trackingPixelData);
    }

    /**
     * On Checkout Success Page: Generate Tracking Pixel Data, one for each item
     *
     * @return array
     */
    public function getCheckoutSuccessTrackingPixelData() {

        $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
        $order = Mage::getModel('sales/order')->load($orderId);
        $items = $order->getAllItems();

        $timestamp = time();

        $trackingPixelData = array();

        foreach($items as $item) {

            if ($item->getParentItem()) {

                continue;
            }

            $trackingPixelData[] = $this->_generateItemData($item, $timestamp);
        }

        return $trackingPixelData;
    }

    /**
     * After Login: Generate Tracking Pixel Data for switching UserIds
     *
     * @return array
     */
    public function getTransferTrackingPixelData() {

        $transferInfoData = Mage::getSingleton('customer/session')->getTransferEventInfo();
        if (!is_array($transferInfoData)) return array();

        $trackingPixelData = array(
            'EventType'     => self::EVENT_TYPE_TRANSFER,
            'userId'        => $transferInfoData['old'],
            'newUserId'     => $transferInfoData['new'],
        );

        Mage::getSingleton('customer/session')->unsetData('transfer_event_info');

        return $trackingPixelData;
    }

    /**
     * Generate order item data for tracking pixel
     *
     * @param Mage_Sales_Model_Order_Item $item
     * @param int $timestamp
     * @return array
     */
    protected function _generateItemData($item, $timestamp)
    {
        $productId = $item->getProductId();

        return array(
            'ItemId'        => $productId,
            'ItemTypeId'    => self::ITEM_TYPE_PRODUCT,
            'Quantity'      => intval($item->getQtyOrdered()),
            'Price'         => intval($item->getBasePrice() * 100),
            'Currency'      => $item->getOrder()->getBaseCurrency()->getCode(),
            'Timestamp'     => $timestamp,
            'EventType'     => self::EVENT_TYPE_BUY,
        );
    }

    /**
     * Generate Url from given Params and Generic Data
     *
     * @param array $trackingPixelData
     * @return string
     */
    public function generateTrackingPixelUrl($trackingPixelData)
    {
        $baseUrl = self::YOOCHOOSE_EVENT_URL;

        $primaryParams = $this->_getPrimaryParamsString($trackingPixelData);
        $secondaryParams = $this->_getSecondaryParamsString($trackingPixelData);

        $url = $baseUrl . $primaryParams . $secondaryParams;

        return $url;
    }

    /**
     * Generate String of primary params (as directories, divided by slash
     *
     * @param array $trackingPixelData
     * @return string
     */
    protected function _getPrimaryParamsString($trackingPixelData)
    {
        $productId = self::PRODUCT_ID;
        $clientId = Mage::getStoreConfig('yoochoose/api/client_id');
        $eventType = $trackingPixelData['EventType'];
        
        $primaryAttributesArray = array(
            $productId,
            $clientId,
            $eventType,
        );

        if (isset($trackingPixelData['ItemTypeId']) && isset($trackingPixelData['ItemId'])) {
            
            $userId = $this->_getUserId();
            $itemType = $trackingPixelData['ItemTypeId'];
            $itemId = urlencode($trackingPixelData['ItemId']);

            $primaryAttributesArray[] = $userId;
            $primaryAttributesArray[] = $itemType;
            $primaryAttributesArray[] = $itemId;
        }
        
        if (isset($trackingPixelData['userId']) && isset($trackingPixelData['newUserId'])) {

            $userId = $trackingPixelData['userId'];
            $newUserId = $trackingPixelData['newUserId'];

            $primaryAttributesArray[] = $userId;
            $primaryAttributesArray[] = $newUserId;
        }

        $primaryAttributes = implode('/', $primaryAttributesArray);

        return $primaryAttributes;
    }

    /**
     * Generate String of secondary params (as default http params)
     *
     * @param array $trackingPixelData
     * @return string
     */
    protected function _getSecondaryParamsString($trackingPixelData)
    {
        $secondaryParams = $this->_getSecondaryParams($trackingPixelData);
        if (empty($secondaryParams)) {

            return '';
        }

        $secondaryParamsString = '?';
        $i = 0;
        foreach($secondaryParams as $key => $value) {

            if ($i > 0) {
                $secondaryParamsString .= '&';
            }
            $secondaryParamsString .= $key . '=' . urlencode($value);
            $i++;
        }

        return $secondaryParamsString;
    }

    /**
     * Extract secondary params from all params; they are all params which have
     * not been used yet
     *
     * @param array $trackingPixelData
     * @return array
     */
    protected function _getSecondaryParams($trackingPixelData)
    {
        unset($trackingPixelData['EventType']);
        unset($trackingPixelData['ItemTypeId']);
        unset($trackingPixelData['ItemId']);
        unset($trackingPixelData['userId']);
        unset($trackingPixelData['newUserId']);

        return $trackingPixelData;
    }
} 