<?php

/**
 * @category   AvS
 * @package    AvS_Yoochoose
 * @author     Andreas von Studnitz <avs@avs-webentwicklung.de>
 */

class AvS_Yoochoose_Model_Api_Recommendation_Crossselling extends AvS_Yoochoose_Model_Api_Recommendation {
	
    protected $_cartProductIds = array();

    // manual collection
    protected $_itemCollection = false;
    
    
    /**
     * Gets configured maximum number of recommended products
     */
    public function getRowCount() {
        return Mage::getStoreConfig('yoochoose/crossselling/row_count');
    }
    
    
    public function getScenario() {
    	return Mage::getStoreConfig('yoochoose/crossselling/scenario');
    }

 
    protected function getContext() {
        $cartProductIds = $this->_getCartProductIds();
        return $cartProductIds;
    }
    
    
    public function getManualItems() {
    	
        if ($this->_itemCollection === false) {
	    	$productIds = $this->_getCartProductIds();
	    	
	    	if (! empty($productIds)) {
		    	
		    	$link = Mage::getSingleton('catalog/product_link')
					->useCrossSellLinks();            // very important, this sets the linkTypeId to 5
					
				$collection = $link->getProductCollection()
					->addProductFilter($productIds)
					->addExcludeProductFilter($productIds)
					->addAttributeToSelect(array('name', 'url_key', 'url_path'));
			
				$ids = array();
					
				foreach ($collection as $cp) {
					$ids[] = $cp->getId();
				}
				
				$result = $this->loadProducts($ids);
		        
		        $this->_itemCollection = $result;
	    	} else {
	    		$this->_itemCollection = array();
	    	}
        }
	        
        return $this->_itemCollection;

    }
    

    /**
     * Get all Product Ids of customer cart
     *
     * @return array
     */
    protected function _getCartProductIds() {
    	
        if (empty($this->_cartProductIds)) {

            $checkoutSession = Mage::getSingleton('checkout/session');
            $cartItems = $checkoutSession->getQuote()->getAllItems();

            foreach($cartItems as $item) {
                if ($item->getParentItem()) {
                    continue;
                }

                $this->_cartProductIds[] = $item->getProductId();
            }
        }

        return $this->_cartProductIds;
    }

} 