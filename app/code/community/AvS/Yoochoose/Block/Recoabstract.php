<?php


class MagentoArray extends ArrayObject {

	public function __construct($data) {
		parent::__construct($data);
	}
	
	public function getSize() {
		return $this->count();
	}
}


class MagentoItemsCollection {
	
	private $data;

	public function __construct($data) {
		$this->data = $data;
	}
	
	public function getItems() {
		return new MagentoArray($this->data);
	}
	
	public function getSize() {
		return count($this->data);
	}
}


abstract class AvS_Yoochoose_Block_Recoabstract extends Mage_Catalog_Block_Product_Abstract {
	
	protected $_itemArrayManual = false;
	protected $_itemArrayYoochoose = false;
	
	protected $_itemArrayMerged = false;

	 
    /**
     * Request Recommendations from Yoochoose Api and transform them to an array
     * of products
     *
     * @return array
     */
    public function getItemArray() {
    	$api = $this->getApi();
   	
    	$columns = $this->getColumnCount();

    	if ($this->_itemArrayMerged === false) {
    		$this->_itemArrayMerged = $api->mergeItemArrays($this->getItemArrayManual(), $this->getItemArrayYoochoose(), $columns);
    		
    		$render = $this->generateRenderPixelObjects();
    		
    		$tracking_block = $this->getLayout()->getBlock('yoochoose.tracking');
    		
    		$data = $tracking_block->getData("render_urls");
    		
    		if (is_array($data)) {
    			$data = array_merge($data, $render);
    		} else {
    			$data = $render;
    		}
    		
    		$tracking_block->setData("render_urls", $data);
    	}
        return $this->_itemArrayMerged;
    }
    
    
    /** Compatibility with native magento templates. */
    public function getItems() {
    	return new MagentoArray($this->getItemArray());
    }
    
    
    /** Compatibility with native magento templates. */
    public function getItemCollection() {
    	return new MagentoItemsCollection($this->getItemArray());
    }
    
    
    /** Compatibility with native magento templates. */
    public function getProductCollection() {
    	return new MagentoItemsCollection($this->getItemArray());
    }
    
    
    /** Compatibility with native magento templates. */
    public function getItemCount() {
		return count($this->getItemArray());
    }
    
    
    /** Overwrite me, if you want to overwrite the block header
     */
	protected function oldHeaderKey() {
    	return null;
    }
    
    
    /** Overwrite me, if you want to overwrite the block header
     */
    protected function newHeaderKey() {
    	return null;
    }
    
    
    public function __() {
    	$args = func_get_args();
    	$oldKey = $this->oldHeaderKey();
    	
    	if ($oldKey && count($args) == 1 && $args[0] == $oldKey) {
    		return parent::__($this->newHeaderKey());
    	} else {
    		if (version_compare(PHP_VERSION, '5.3.0') < 0) {
    			return parent::__($args[0]);
    		} else { 
				return call_user_func_array('parent::__', $args);
    		}
    	}
    }
    

    // used in ULTIMO template
    public function getBlockName() {
    	if ($this->newHeaderKey()) {
    		return parent::__($this->newHeaderKey());
    	} else {
    		return parent::getBlockName();
    	}
    }
    
    
    private $item_iterator = null;
    
    public function resetItemsIterator() {
    	$this->item_iterator = $this->getItems();
        reset($this->item_iterator);
    }
    

    public function getIterableItem() {
    	if ($this->item_iterator == null) {
    		$this->resetItemsIterator();
    	}
        $item = current($this->item_iterator);
        next($this->item_iterator);
        return $item;
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
    
    
    
    
    private function idsToRender() {
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
    	
    	return $follow;
    } 
    
    
    private function generateRenderPixelObjects() {
    	$ids = $this->idsToRender();
    	
    	$api = $this->getApi();
    	$eventlib = Mage::getSingleton('yoochoose/api_event');
    	
    	return $eventlib->getRenderedTrackingPixelData($ids, $api->getScenario());
    }
    
    
    /** Compatibility! Used in old yoochoose templates. */
    public function generateRenderPixelUrl() {
        return $this->renderPixelUrl($this->idsToRender());
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