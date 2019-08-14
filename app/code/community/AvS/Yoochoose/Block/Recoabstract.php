<?php


abstract class AvS_Yoochoose_Block_Recoabstract extends Mage_Catalog_Block_Product_Abstract {
	
	protected $_itemArrayManual = false;
	protected $_itemArrayYoochoose = false;

	 
    /**
     * Request Recommendations from Yoochoose Api and transform them to an array
     * of products
     *
     * @return array
     */
    public function getItemArray() {
    	$api = $this->getApi();
   	
    	$columns = $this->getColumnCount();

    	
        return $api->mergeItemArrays($this->getItemArrayManual(), $this->getItemArrayYoochoose(), $columns);
    }
    
    
    public function getRowCount() {
    	$api = $this->getApi();
    	
    	return $api->getRowCount();
    }
    
    
    private function getItemArrayYoochoose() {
    	if ($this->_itemArrayYoochoose === false) {
    		
    		$api = $this->getApi();
    		$manual = $this->getItemArrayManual();
    		$isActive = Mage::helper('yoochoose')->isActive() && $this->isYoochooseEnabled();
    		
    		$columns = $this->getColumnCount();
    		
            if ($isActive && count($manual) < $api->getMaxNumberProducts($columns)) {
            	$this->_itemArrayYoochoose = $api->getRecommendedProducts($columns);
            } else {
            	$this->_itemArrayYoochoose = array();
            }
    	}
    	return $this->_itemArrayYoochoose;
    }
    
    
    private function getItemArrayManual() {
    	if ($this->_itemArrayManual === false) {
    		
    		$api = $this->getApi();
    		$isActive = Mage::helper('yoochoose')->isActive();
    		
            if ($this->isPreferManual() || ! $isActive) {
                $this->_itemArrayManual = $api->getManualItems();
            } else {
            	$this->_itemArrayManual = array();
            }
    	}
    	return $this->_itemArrayManual;
    }
    
    
    public function generateRenderPixelUrl() {
    	
    	$final = $this->getItemArray();
    	$yoo   = $this->getItemArrayYoochoose();
    	
    	$follow = array();
    	
    	$api = $this->getApi();
    	
    	foreach ($yoo as $item) {
    		if ($api->findProduct($final, $item->getEntityId())) {
    			$follow[] = $item->getEntityId();
    		}
    	}
    	
    	if (empty($follow)) {
    		Mage::log("No items to render. No pixel", Zend_Log::DEBUG, 'yoochoose.log');
    		return null;
    	}

        return $this->renderPixelUrl($follow);
    }
    
    
    /**
     * Generate String of primary params (as directories, divided by slash
     *
     * @param array $trackingPixelData
     * @return string
     */
    private function renderPixelUrl($itemIds) {
    	
        $clientId = Mage::getStoreConfig('yoochoose/api/client_id');

        $api = $this->getApi();
        
        $userId = $api->getUserId();
        $itemType = $itemType = Mage::getStoreConfig('yoochoose/api/item_type');
            
        $baseUrl = Mage::getStoreConfig('yoochoose/api/tracker_server');
    	$baseUrl = rtrim($baseUrl, "/");
    	
        $result = $baseUrl.
        		'/api/'.urlencode($clientId).
        		'/'.AvS_Yoochoose_Model_Api::EVENT_TYPE_RENDER.
		        '/'.urlencode($userId).
        		'/'.$itemType.
        		'/'.implode(",", $itemIds);
        
        Mage::log("Generating render tracking pixel: [$result]...", Zend_Log::DEBUG, 'yoochoose.log');

        return $result;
    }
    
    
    /**
     * Generate URL from given Params and Generic Data
     *
     * @param array $trackingPixelData
     * @return string
     */
    public function generateTrackingPixelUrl($trackingPixelData) {
        return Mage::getSingleton('yoochoose/api_event')->generateTrackingPixelUrl($trackingPixelData);
    }

    
    /** If manual set recommendations must be shown in the box */
    abstract protected function isPreferManual();
    
    
    /** If yoochoose recommendations must be shown in the box */
    abstract protected function isYoochooseEnabled();
    
    
    /** Instance of the Yoochoose API object. See in Model/Api/Recommendation */
    abstract protected function getApi();
    
    
    public function getProductUrl($product, $additional = array()) {
    	
    	$api = $this->getApi();
    	
    	// if product manually added, it does not create FOLLOW event.
		if ( ! $api->findProduct($this->getItemArrayManual(), $product->getEntityId())) {
        	if (isset($additional['_query'])) {
	            $query =& $additional['_query'];
	            $query['recommended'] = $api->getScenario();
            } else {
          		$additional['_query'] = array('recommended' => $api->getScenario());
            }
        }
        
        return parent::getProductUrl($product, $additional);
    }
    
}