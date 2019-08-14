<?php

/**
 * @category   AvS
 * @package    AvS_Yoochoose
 * @author     Andreas von Studnitz <avs@avs-webentwicklung.de>
 */

class AvS_Yoochoose_Model_Api_Event extends AvS_Yoochoose_Model_Api {
	
	
	
    public function getRenderedTrackingPixelData($ids = array(), $scenario = null) {
    	
    	$itemType = Mage::getStoreConfig('yoochoose/api/item_type');
    	$result = array();

    	if (! empty($ids)) {
	        $trackingPixelData = array(
	            'ItemId'        => implode(",", $ids),
	            'ItemTypeId'    => $itemType,
	            'EventType'     => self::EVENT_TYPE_RENDER,
	        	'scenario'      => $scenario
	        );
	        $result[] = $trackingPixelData;
    	}
        
        return $result;
    }

	
    /**
     * On Product View Page: Generate Tracking Pixel Data
     *
     * @param boolean $isRecommended
     * @return array
     */
    public function getProductViewTrackingPixelData($isRecommended = null) {
    	
    	$itemType = Mage::getStoreConfig('yoochoose/api/item_type');

        $product = Mage::registry('product');
        $productId = $product->getId();
        
        $result = array();

        $trackingPixelData = array(
            'ItemId'        => $productId,
            'ItemTypeId'    => $itemType,
            'EventType'     => self::EVENT_TYPE_CLICK,
	        'categorypath'  => $this->_getCategoryPath()
        );
        
        $result[] = $trackingPixelData;
        
        if ($isRecommended) {
	        $trackingPixelData = array(
	            'ItemId'        => $productId,
	            'ItemTypeId'    => $itemType,
	            'EventType'     => self::EVENT_TYPE_FOLLOW,
	       		'scenario'      => $isRecommended
	        );
	        
	        $result[] = $trackingPixelData;
        }

        return $result;
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

            $itemData = $this->_generateItemData($item, $timestamp);
            if ($itemData) {
            	$trackingPixelData[] = $itemData;
            } 
        }

        return $trackingPixelData;
    }

    
    /**
     * Generate order item data for tracking pixel
     *
     * @param Mage_Sales_Model_Order_Item $item
     * @param int $timestamp
     * @return array
     */
    protected function _generateItemData($item, $timestamp) {
    	
    	$itemType = Mage::getStoreConfig('yoochoose/api/item_type');
        $productId = $item->getProductId();
        $product = $item->getProduct();
        
        $visibility = $product->getVisibility();

        if ($visibility == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE) {
        	Mage::log("Product [$productId] is not visible. A part of a configurable?", Zend_Log::DEBUG, 'yoochoose.log');
        	
   			$parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
			if (isset($parentIds[0])) {
				Mage::log("Product [$productId] is a child product of a configurable one. Parents: ".implode(",", $parentIds).". Using the first parent for tracking.", Zend_Log::DEBUG, 'yoochoose.log');
				
				$productId = $parentIds[0];
			} else {
				Mage::log("No paren found for [$productId]. Skipping...", Zend_Log::DEBUG, 'yoochoose.log');
				return null;
			}
        }
        
        $currency = $item->getOrder()->getBaseCurrency()->getCode();
        
        return array(
            'ItemId'        => $productId,
            'ItemTypeId'    => $itemType,
            'EventType'     => self::EVENT_TYPE_BUY,
            'quantity'      => intval($item->getQtyOrdered()),
            'fullprice'     => intval($item->getBasePrice()).$currency
        );
    }

    
    /**
     * Generate Url from given Params and Generic Data
     *
     * @param array $trackingPixelData
     * @return string
     */
    public function generateTrackingPixelUrl($trackingPixelData) {
    	
    	$baseUrl = Mage::getStoreConfig('yoochoose/api/tracker_server');
    	$baseUrl = rtrim($baseUrl, "/");

        $primaryParams = $this->_getPrimaryParamsString($trackingPixelData);
        $secondaryParams = $this->_getSecondaryParamsString($trackingPixelData);

        $url = $baseUrl .'/api/'. $primaryParams . $secondaryParams;
        
        Mage::log("Generating tracking pixel: [$url]...", Zend_Log::DEBUG, 'yoochoose.log');

        return $url;
    }

    
    /**
     * Generate String of primary params (as directories, divided by slash
     *
     * @param array $trackingPixelData
     * @return string
     */
    protected function _getPrimaryParamsString($trackingPixelData) {
    	
        $clientId = Mage::getStoreConfig('yoochoose/api/client_id');
        $eventType = $trackingPixelData['EventType'];
        
        $primaryAttributesArray = array(
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

        $primaryAttributes = implode('/', $primaryAttributesArray);

        return $primaryAttributes;
    }
    

    /**
     * Generate String of secondary params (as default http params)
     *
     * @param array $trackingPixelData
     * @return string
     */
    protected function _getSecondaryParamsString($trackingPixelData) {
    	
        $secondaryParams = $this->_getSecondaryParams($trackingPixelData);

        $params = array();
        
        foreach($secondaryParams as $key => $value) {
			if ($value !== '') {
	            $params[$key] = $value;
			}
        }
		if (empty($params)) {
			return '';
		} else {
        	return '?'.http_build_query($params);
		}
    }
    

    /**
     * Extract secondary params from all params; they are all params which have
     * not been used yet
     *
     * @param array $trackingPixelData
     * @return array
     */
    protected function _getSecondaryParams($trackingPixelData) {
        if (isset($trackingPixelData['EventType'])) unset($trackingPixelData['EventType']);
        unset($trackingPixelData['ItemTypeId']);
        unset($trackingPixelData['ItemId']);
        unset($trackingPixelData['userId']);
        unset($trackingPixelData['newUserId']);

        return $trackingPixelData;
    }
} 